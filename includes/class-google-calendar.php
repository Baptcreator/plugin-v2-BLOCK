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
     * Client Google Calendar simplifié
     */
    private $simple_client = null;

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
        
        // Initialiser immédiatement le client simplifié
        $this->init_google_client();
    }

    /**
     * Initialiser le client Google (version simplifiée)
     */
    public function init_google_client()
    {
        // Utiliser la version simplifiée sans dépendances
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'google-calendar-simple.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'google-calendar-simple.php';
            $this->simple_client = RestaurantBooking_Google_Calendar_Simple::get_instance();
            return true;
        }
        
        return false;
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
        if (!$this->simple_client) {
            return false;
        }

        return $this->simple_client->get_auth_url();
    }

    /**
     * Gérer l'autorisation OAuth2
     */
    public function handle_auth()
    {
        if (!$this->simple_client) {
            wp_die(__('Service Google Calendar non disponible', 'restaurant-booking'));
        }

        // Utiliser la méthode de la classe simplifiée
        $this->simple_client->handle_auth();
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
        if (!$this->simple_client) {
            wp_send_json_error(__('Service Google Calendar non disponible', 'restaurant-booking'));
        }

        // Utiliser la méthode de la classe simplifiée
        $this->simple_client->sync_calendar();
    }

    /**
     * Synchroniser depuis Google Calendar vers WordPress
     */
    private function sync_from_google()
    {
        $settings = $this->get_google_settings();
        $calendar_id = $settings['calendar_id'];
        
        // Récupérer les événements depuis le début du mois courant
        $time_min = date('c', strtotime('first day of this month'));
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
            
            // Gérer les événements toute la journée vs créneaux horaires
            $is_all_day = !empty($start->getDate());
            $event_date = $is_all_day ? $start->getDate() : date('Y-m-d', strtotime($start->getDateTime()));
            
            // Récupérer les heures pour les créneaux spécifiques
            $start_time = null;
            $end_time = null;
            if (!$is_all_day) {
                $start_time = date('H:i:s', strtotime($start->getDateTime()));
                $end_time = date('H:i:s', strtotime($end->getDateTime()));
            }
            
            // Vérifier si c'est un événement de blocage ou marqué comme "Occupé"
            $summary = $event->getSummary();
            $is_busy_event = false;
            
            // Vérifier les mots-clés de blocage
            if (strpos(strtolower($summary), 'block') !== false || 
                strpos(strtolower($summary), 'restaurant') !== false ||
                strpos(strtolower($summary), 'remorque') !== false) {
                $is_busy_event = true;
            }
            
            // Vérifier si l'événement est marqué comme "Occupé" dans Google Calendar
            $attendees = $event->getAttendees();
            if ($attendees) {
                foreach ($attendees as $attendee) {
                    if ($attendee->getResponseStatus() === 'busy') {
                        $is_busy_event = true;
                        break;
                    }
                }
            }
            
            // Vérifier le statut de disponibilité de l'événement
            $transparency = $event->getTransparency();
            if ($transparency === 'opaque') { // "Occupé" dans Google Calendar
                $is_busy_event = true;
            }
            
            if ($is_busy_event) {
                
                // Marquer comme non disponible dans WordPress avec créneaux horaires
                $blocked_reason = 'Synchronisé depuis Google Calendar';
                if ($transparency === 'opaque') {
                    $blocked_reason = 'Journée bloquée (Occupé)';
                } elseif (strpos(strtolower($summary), 'block') !== false) {
                    $blocked_reason = 'Journée bloquée manuellement';
                }
                
                $data = array(
                    'date' => $event_date,
                    'service_type' => $this->detect_service_type($summary),
                    'is_available' => 0,
                    'blocked_reason' => $blocked_reason,
                    'notes' => $summary,
                    'created_by' => 0,
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'google_event_id' => $event->getId(),
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                );
                
                $format = array('%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s');
                
                $wpdb->replace($wpdb->prefix . 'restaurant_availability', $data, $format);
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
     * Récupérer les disponibilités d'un mois pour le widget calendrier
     */
    public function get_month_availability($year, $month, $service_type = 'both')
    {
        global $wpdb;
        
        // Récupérer les données depuis la table synchronisée
        $start_date = sprintf('%04d-%02d-01', $year, $month);
        $end_date = date('Y-m-t', strtotime($start_date));
        
        $availability_data = array();
        
        // Adapter la requête selon le service_type demandé
        if ($service_type === 'both' || $service_type === 'all') {
            // Récupérer tous les événements
            $results = $wpdb->get_results($wpdb->prepare("
                SELECT date, service_type, is_available, blocked_reason, notes, google_event_id,
                       start_time, end_time
                FROM {$wpdb->prefix}restaurant_availability 
                WHERE date BETWEEN %s AND %s
                AND is_available = 0
                ORDER BY date ASC, start_time ASC
            ", $start_date, $end_date), ARRAY_A);
        } else {
            // Récupérer pour un service spécifique + 'both' + 'all'
            $results = $wpdb->get_results($wpdb->prepare("
                SELECT date, service_type, is_available, blocked_reason, notes, google_event_id,
                       start_time, end_time
                FROM {$wpdb->prefix}restaurant_availability 
                WHERE date BETWEEN %s AND %s
                AND is_available = 0
                AND (service_type = %s OR service_type = 'both' OR service_type = 'all')
                ORDER BY date ASC, start_time ASC
            ", $start_date, $end_date, $service_type), ARRAY_A);
        }
        
        foreach ($results as $row) {
            $date = $row['date'];
            
            if (!isset($availability_data[$date])) {
                $availability_data[$date] = array(
                    'is_fully_blocked' => false,
                    'blocked_slots' => array(),
                    'events' => array(),
                    'has_google_events' => false
                );
            }
            
            // Ajouter l'événement à la liste
            $event_info = array(
                'is_available' => $row['is_available'],
                'blocked_reason' => $row['blocked_reason'],
                'notes' => $row['notes'],
                'google_event_id' => $row['google_event_id'] ?? '',
                'start_time' => $row['start_time'],
                'end_time' => $row['end_time'],
                'service_type' => $row['service_type']
            );
            
            $availability_data[$date]['events'][] = $event_info;
            
            // Marquer si c'est un événement Google Calendar
            if (!empty($row['google_event_id'])) {
                $availability_data[$date]['has_google_events'] = true;
            }
            
            if ($row['is_available'] == 0) {
                if (empty($row['start_time']) && empty($row['end_time'])) {
                    // Blocage toute la journée
                    $availability_data[$date]['is_fully_blocked'] = true;
                } else {
                    // Blocage par créneau horaire
                    $availability_data[$date]['blocked_slots'][] = array(
                        'type' => 'time_slot',
                        'start_time' => $row['start_time'],
                        'end_time' => $row['end_time'],
                        'reason' => $row['blocked_reason'],
                        'is_google_sync' => !empty($row['google_event_id'])
                    );
                }
            }
        }
        
        return $availability_data;
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
     * Obtenir les disponibilités avec créneaux horaires pour une date
     */
    public function get_date_availability_slots($date, $service_type = 'both')
    {
        global $wpdb;

        $where_service = '';
        $params = array($date);
        
        if ($service_type !== 'both') {
            $where_service = 'AND (service_type = %s OR service_type = "both")';
            $params[] = $service_type;
        }

        $sql = "SELECT * FROM {$wpdb->prefix}restaurant_availability 
                WHERE date = %s 
                AND is_available = 0 
                $where_service
                ORDER BY start_time ASC";

        $blocked_slots = $wpdb->get_results($wpdb->prepare($sql, $params));

        $result = array(
            'date' => $date,
            'service_type' => $service_type,
            'blocked_slots' => array(),
            'is_fully_blocked' => false
        );

        foreach ($blocked_slots as $slot) {
            if (is_null($slot->start_time) && is_null($slot->end_time)) {
                // Journée entière bloquée
                $result['is_fully_blocked'] = true;
                $result['blocked_slots'][] = array(
                    'type' => 'full_day',
                    'reason' => $slot->blocked_reason,
                    'notes' => $slot->notes
                );
            } else {
                // Créneau spécifique bloqué
                $result['blocked_slots'][] = array(
                    'type' => 'time_slot',
                    'start_time' => $slot->start_time,
                    'end_time' => $slot->end_time,
                    'reason' => $slot->blocked_reason,
                    'notes' => $slot->notes
                );
            }
        }

        return $result;
    }


    /**
     * Vérifier la connexion
     */
    public function test_connection()
    {
        if (!$this->simple_client) {
            return array('status' => 'error', 'message' => 'Service non disponible');
        }

        return $this->simple_client->test_connection();
    }
}
