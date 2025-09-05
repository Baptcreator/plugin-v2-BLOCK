<?php
/**
 * Classe d'intégration Google Calendar API
 *
 * @package RestaurantBooking
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Google_Calendar
{
    /**
     * Instance unique
     */
    private static $instance = null;

    /**
     * Client Google API
     */
    private $client = null;

    /**
     * Service Google Calendar
     */
    private $service = null;

    /**
     * Obtenir l'instance unique
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructeur
     */
    private function __construct()
    {
        add_action('init', array($this, 'init_google_client'));
        add_action('wp_ajax_google_calendar_auth', array($this, 'handle_auth'));
        add_action('wp_ajax_google_calendar_sync', array($this, 'sync_calendar'));
        add_action('restaurant_booking_hourly_sync', array($this, 'hourly_sync'));
    }

    /**
     * Initialiser le client Google
     */
    public function init_google_client()
    {
        // Vérifier si la bibliothèque Google API est disponible
        if (!class_exists('Google_Client')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-warning"><p>';
                echo __('Google API Client Library non trouvée. Installez-la via Composer : composer require google/apiclient', 'restaurant-booking');
                echo '</p></div>';
            });
            return false;
        }

        $settings = $this->get_google_settings();
        
        if (empty($settings['client_id']) || empty($settings['client_secret'])) {
            return false;
        }

        try {
            $this->client = new Google_Client();
            $this->client->setClientId($settings['client_id']);
            $this->client->setClientSecret($settings['client_secret']);
            $this->client->setRedirectUri($settings['redirect_uri']);
            $this->client->addScope(Google_Service_Calendar::CALENDAR);
            $this->client->setAccessType('offline');
            $this->client->setPrompt('consent');

            // Utiliser le token d'accès s'il existe
            if (!empty($settings['access_token'])) {
                $this->client->setAccessToken($settings['access_token']);
                
                // Rafraîchir le token si nécessaire
                if ($this->client->isAccessTokenExpired()) {
                    $this->refresh_access_token();
                }
            }

            $this->service = new Google_Service_Calendar($this->client);
            
            return true;
        } catch (Exception $e) {
            RestaurantBooking_Logger::log('Erreur initialisation Google Calendar: ' . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Obtenir les paramètres Google Calendar
     */
    private function get_google_settings()
    {
        return array(
            'client_id' => get_option('restaurant_booking_google_client_id', ''),
            'client_secret' => get_option('restaurant_booking_google_client_secret', ''),
            'redirect_uri' => admin_url('admin-ajax.php?action=google_calendar_auth'),
            'access_token' => get_option('restaurant_booking_google_access_token', ''),
            'refresh_token' => get_option('restaurant_booking_google_refresh_token', ''),
            'calendar_id' => get_option('restaurant_booking_google_calendar_id', 'primary')
        );
    }

    /**
     * Obtenir l'URL d'autorisation
     */
    public function get_auth_url()
    {
        if (!$this->client) {
            return false;
        }

        return $this->client->createAuthUrl();
    }

    /**
     * Gérer l'autorisation OAuth2
     */
    public function handle_auth()
    {
        if (!isset($_GET['code'])) {
            wp_die(__('Code d\'autorisation manquant', 'restaurant-booking'));
        }

        try {
            $token = $this->client->fetchAccessTokenWithAuthCode($_GET['code']);
            
            if (isset($token['error'])) {
                throw new Exception('Erreur OAuth: ' . $token['error_description']);
            }

            // Sauvegarder les tokens
            update_option('restaurant_booking_google_access_token', json_encode($token));
            
            if (isset($token['refresh_token'])) {
                update_option('restaurant_booking_google_refresh_token', $token['refresh_token']);
            }

            // Rediriger vers les paramètres
            wp_redirect(admin_url('admin.php?page=restaurant-booking-google-settings&auth=success'));
            exit;

        } catch (Exception $e) {
            RestaurantBooking_Logger::log('Erreur autorisation Google: ' . $e->getMessage(), 'error');
            wp_redirect(admin_url('admin.php?page=restaurant-booking-google-settings&auth=error'));
            exit;
        }
    }

    /**
     * Rafraîchir le token d'accès
     */
    private function refresh_access_token()
    {
        $settings = $this->get_google_settings();
        
        if (empty($settings['refresh_token'])) {
            return false;
        }

        try {
            $this->client->refreshToken($settings['refresh_token']);
            $new_token = $this->client->getAccessToken();
            
            update_option('restaurant_booking_google_access_token', json_encode($new_token));
            
            return true;
        } catch (Exception $e) {
            RestaurantBooking_Logger::log('Erreur refresh token: ' . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Synchronisation bidirectionnelle
     */
    public function sync_calendar()
    {
        if (!$this->service) {
            wp_send_json_error(__('Service Google Calendar non disponible', 'restaurant-booking'));
        }

        try {
            // 1. Synchroniser depuis Google vers WordPress
            $this->sync_from_google();
            
            // 2. Synchroniser depuis WordPress vers Google  
            $this->sync_to_google();
            
            wp_send_json_success(__('Synchronisation terminée', 'restaurant-booking'));
            
        } catch (Exception $e) {
            RestaurantBooking_Logger::log('Erreur sync calendar: ' . $e->getMessage(), 'error');
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Synchroniser depuis Google Calendar vers WordPress
     */
    private function sync_from_google()
    {
        $settings = $this->get_google_settings();
        $calendar_id = $settings['calendar_id'];
        
        // Récupérer les événements des 3 prochains mois
        $time_min = date('c');
        $time_max = date('c', strtotime('+3 months'));
        
        $events = $this->service->events->listEvents($calendar_id, array(
            'timeMin' => $time_min,
            'timeMax' => $time_max,
            'singleEvents' => true,
            'orderBy' => 'startTime'
        ));

        global $wpdb;
        
        foreach ($events->getItems() as $event) {
            $start = $event->getStart();
            $end = $event->getEnd();
            
            // Gérer les événements toute la journée
            $event_date = $start->getDate() ? $start->getDate() : date('Y-m-d', strtotime($start->getDateTime()));
            
            // Vérifier si c'est un événement de blocage Block & Co
            $summary = $event->getSummary();
            if (strpos(strtolower($summary), 'block') !== false || 
                strpos(strtolower($summary), 'restaurant') !== false ||
                strpos(strtolower($summary), 'remorque') !== false) {
                
                // Marquer comme non disponible dans WordPress
                $wpdb->replace(
                    $wpdb->prefix . 'restaurant_availability',
                    array(
                        'date' => $event_date,
                        'service_type' => $this->detect_service_type($summary),
                        'is_available' => 0,
                        'blocked_reason' => 'Synchronisé depuis Google Calendar',
                        'notes' => $summary,
                        'created_by' => 0,
                        'created_at' => current_time('mysql'),
                        'updated_at' => current_time('mysql')
                    ),
                    array('%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s')
                );
            }
        }
    }

    /**
     * Synchroniser depuis WordPress vers Google Calendar
     */
    private function sync_to_google()
    {
        global $wpdb;
        
        // Récupérer les disponibilités WordPress non synchronisées
        $blocked_dates = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}restaurant_availability 
            WHERE is_available = 0 
            AND date >= %s 
            AND date <= %s
        ", date('Y-m-d'), date('Y-m-d', strtotime('+3 months'))));

        $settings = $this->get_google_settings();
        $calendar_id = $settings['calendar_id'];

        foreach ($blocked_dates as $blocked) {
            // Vérifier si l'événement existe déjà
            $existing_events = $this->service->events->listEvents($calendar_id, array(
                'timeMin' => $blocked->date . 'T00:00:00Z',
                'timeMax' => $blocked->date . 'T23:59:59Z',
                'q' => 'Block & Co - ' . $blocked->service_type
            ));

            if (count($existing_events->getItems()) == 0) {
                // Créer un nouvel événement
                $event = new Google_Service_Calendar_Event();
                $event->setSummary('Block & Co - ' . ucfirst($blocked->service_type) . ' indisponible');
                $event->setDescription($blocked->notes . "\n\nSynchronisé depuis WordPress");

                $start = new Google_Service_Calendar_EventDateTime();
                $start->setDate($blocked->date);
                $event->setStart($start);

                $end = new Google_Service_Calendar_EventDateTime();
                $end->setDate($blocked->date);
                $event->setEnd($end);

                $this->service->events->insert($calendar_id, $event);
            }
        }
    }

    /**
     * Détecter le type de service depuis le titre de l'événement
     */
    private function detect_service_type($summary)
    {
        $summary_lower = strtolower($summary);
        
        if (strpos($summary_lower, 'restaurant') !== false) {
            return 'restaurant';
        } elseif (strpos($summary_lower, 'remorque') !== false) {
            return 'remorque';
        } else {
            return 'both';
        }
    }

    /**
     * Synchronisation horaire automatique
     */
    public function hourly_sync()
    {
        if ($this->service) {
            $this->sync_calendar();
        }
    }

    /**
     * Créer un événement dans Google Calendar
     */
    public function create_event($date, $service_type, $title, $description = '')
    {
        if (!$this->service) {
            return false;
        }

        try {
            $settings = $this->get_google_settings();
            $calendar_id = $settings['calendar_id'];

            $event = new Google_Service_Calendar_Event();
            $event->setSummary($title);
            $event->setDescription($description);

            $start = new Google_Service_Calendar_EventDateTime();
            $start->setDate($date);
            $event->setStart($start);

            $end = new Google_Service_Calendar_EventDateTime();
            $end->setDate($date);
            $event->setEnd($end);

            $created_event = $this->service->events->insert($calendar_id, $event);
            
            return $created_event->getId();
        } catch (Exception $e) {
            RestaurantBooking_Logger::log('Erreur création événement: ' . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Supprimer un événement de Google Calendar
     */
    public function delete_event($event_id)
    {
        if (!$this->service) {
            return false;
        }

        try {
            $settings = $this->get_google_settings();
            $calendar_id = $settings['calendar_id'];
            
            $this->service->events->delete($calendar_id, $event_id);
            return true;
        } catch (Exception $e) {
            RestaurantBooking_Logger::log('Erreur suppression événement: ' . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Vérifier la connexion
     */
    public function test_connection()
    {
        if (!$this->service) {
            return array('status' => 'error', 'message' => 'Service non disponible');
        }

        try {
            $calendar_list = $this->service->calendarList->listCalendarList();
            return array(
                'status' => 'success', 
                'message' => 'Connexion réussie',
                'calendars' => $calendar_list->getItems()
            );
        } catch (Exception $e) {
            return array(
                'status' => 'error', 
                'message' => $e->getMessage()
            );
        }
    }
}
