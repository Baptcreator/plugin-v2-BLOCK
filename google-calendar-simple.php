<?php
/**
 * Intégration Google Calendar simplifiée sans dépendances
 * Version légère utilisant uniquement cURL et les API REST de Google
 */

class RestaurantBooking_Google_Calendar_Simple
{
    private static $instance = null;
    private $client_id;
    private $client_secret;
    private $access_token;
    private $refresh_token;
    private $calendar_id;
    
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct()
    {
        $this->client_id = get_option('restaurant_booking_google_client_id', '');
        $this->client_secret = get_option('restaurant_booking_google_client_secret', '');
        $this->access_token = get_option('restaurant_booking_google_access_token', '');
        $this->refresh_token = get_option('restaurant_booking_google_refresh_token', '');
        $this->calendar_id = get_option('restaurant_booking_google_calendar_id', 'primary');
    }
    
    /**
     * Obtenir l'URL d'autorisation
     */
    public function get_auth_url()
    {
        if (empty($this->client_id)) {
            return false;
        }
        
        $params = array(
            'client_id' => $this->client_id,
            'redirect_uri' => admin_url('admin-ajax.php?action=google_calendar_auth'),
            'scope' => 'https://www.googleapis.com/auth/calendar',
            'response_type' => 'code',
            'access_type' => 'offline',
            'prompt' => 'consent'
        );
        
        return 'https://accounts.google.com/o/oauth2/auth?' . http_build_query($params);
    }
    
    /**
     * Gérer l'autorisation OAuth2
     */
    public function handle_auth()
    {
        if (!isset($_GET['code'])) {
            wp_die(__('Code d\'autorisation manquant', 'restaurant-booking'));
        }
        
        $code = sanitize_text_field($_GET['code']);
        
        // Échanger le code contre un token d'accès
        $token_data = $this->exchange_code_for_token($code);
        
        if ($token_data && isset($token_data['access_token'])) {
            update_option('restaurant_booking_google_access_token', $token_data['access_token']);
            $this->access_token = $token_data['access_token'];
            
            if (isset($token_data['refresh_token'])) {
                update_option('restaurant_booking_google_refresh_token', $token_data['refresh_token']);
                $this->refresh_token = $token_data['refresh_token'];
            }
            
            wp_redirect(admin_url('admin.php?page=restaurant-booking-calendar&tab=google&auth=success'));
        } else {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-calendar&tab=google&auth=error'));
        }
        exit;
    }
    
    /**
     * Échanger le code d'autorisation contre un token
     */
    private function exchange_code_for_token($code)
    {
        $url = 'https://oauth2.googleapis.com/token';
        
        $data = array(
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => admin_url('admin-ajax.php?action=google_calendar_auth')
        );
        
        return $this->make_http_request($url, $data, 'POST');
    }
    
    /**
     * Rafraîchir le token d'accès
     */
    private function refresh_access_token()
    {
        if (empty($this->refresh_token)) {
            return false;
        }
        
        $url = 'https://oauth2.googleapis.com/token';
        
        $data = array(
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'refresh_token' => $this->refresh_token,
            'grant_type' => 'refresh_token'
        );
        
        $response = $this->make_http_request($url, $data, 'POST');
        
        if ($response && isset($response['access_token'])) {
            $this->access_token = $response['access_token'];
            update_option('restaurant_booking_google_access_token', $response['access_token']);
            return true;
        }
        
        return false;
    }
    
    /**
     * Lister les événements du calendrier
     */
    public function list_events($time_min = null, $time_max = null)
    {
        if (empty($this->access_token)) {
            return false;
        }
        
        $params = array(
            'singleEvents' => 'true',
            'orderBy' => 'startTime'
        );
        
        if ($time_min) {
            $params['timeMin'] = $time_min;
        }
        
        if ($time_max) {
            $params['timeMax'] = $time_max;
        }
        
        $url = "https://www.googleapis.com/calendar/v3/calendars/{$this->calendar_id}/events?" . http_build_query($params);
        
        $response = $this->make_http_request($url, null, 'GET', array(
            'Authorization: Bearer ' . $this->access_token
        ));
        
        // Si le token a expiré, essayer de le rafraîchir
        if (!$response && $this->refresh_access_token()) {
            $response = $this->make_http_request($url, null, 'GET', array(
                'Authorization: Bearer ' . $this->access_token
            ));
        }
        
        return $response;
    }
    
    /**
     * Créer un événement
     */
    public function create_event($title, $date, $description = '')
    {
        if (empty($this->access_token)) {
            return false;
        }
        
        $event_data = array(
            'summary' => $title,
            'description' => $description,
            'start' => array(
                'date' => $date
            ),
            'end' => array(
                'date' => $date
            )
        );
        
        $url = "https://www.googleapis.com/calendar/v3/calendars/{$this->calendar_id}/events";
        
        return $this->make_http_request($url, $event_data, 'POST', array(
            'Authorization: Bearer ' . $this->access_token,
            'Content-Type: application/json'
        ));
    }
    
    /**
     * Synchronisation bidirectionnelle
     */
    public function sync_calendar()
    {
        try {
            // Synchroniser depuis Google vers WordPress
            $this->sync_from_google();
            
            // Synchroniser depuis WordPress vers Google  
            $this->sync_to_google();
            
            // Si c'est une requête AJAX, renvoyer JSON
            if (wp_doing_ajax()) {
                wp_send_json_success(__('Synchronisation terminée', 'restaurant-booking'));
            }
            
            // Sinon, c'est une requête POST normale, ne rien faire (la page se rechargera)
            return true;
            
        } catch (Exception $e) {
            if (wp_doing_ajax()) {
                wp_send_json_error($e->getMessage());
            }
            
            // Pour les requêtes POST, on peut logger l'erreur
            if (function_exists('RestaurantBooking_Logger')) {
                RestaurantBooking_Logger::log('Erreur synchronisation: ' . $e->getMessage(), 'error');
            }
            
            return false;
        }
    }
    
    /**
     * Synchroniser depuis Google vers WordPress
     */
    private function sync_from_google()
    {
        // Récupérer les événements depuis le début du mois courant au lieu d'aujourd'hui
        $time_min = date('c', strtotime('first day of this month'));
        $time_max = date('c', strtotime('+3 months'));
        
        $events_response = $this->list_events($time_min, $time_max);
        
        if (!$events_response || !isset($events_response['items'])) {
            return;
        }
        
        global $wpdb;
        
        foreach ($events_response['items'] as $event) {
            if (!isset($event['summary'])) {
                continue;
            }
            
            $summary = strtolower($event['summary']);
            
            // Vérifier si c'est un événement de blocage
            if (strpos($summary, 'block') !== false || 
                strpos($summary, 'restaurant') !== false ||
                strpos($summary, 'remorque') !== false) {
                
                $start = $event['start'];
                $end = $event['end'];
                
                // Gérer les événements toute la journée vs créneaux horaires
                $is_all_day = isset($start['date']);
                $event_date = $is_all_day ? $start['date'] : date('Y-m-d', strtotime($start['dateTime']));
                
                // Récupérer les heures pour les créneaux spécifiques
                $start_time = null;
                $end_time = null;
                if (!$is_all_day && isset($start['dateTime']) && isset($end['dateTime'])) {
                    $start_time = date('H:i:s', strtotime($start['dateTime']));
                    $end_time = date('H:i:s', strtotime($end['dateTime']));
                }
                
                // Vérifier quelles colonnes existent dans la table
                $columns = $wpdb->get_col("DESCRIBE {$wpdb->prefix}restaurant_availability", 0);
                
                // Données de base (toujours présentes)
                $data = array(
                    'date' => $event_date,
                    'service_type' => $this->detect_service_type($event['summary']),
                    'is_available' => 0,  // 0 = Non disponible (bloqué)
                    'blocked_reason' => 'Synchronisé depuis Google Calendar',
                    'notes' => $event['summary'],
                    'created_by' => 0,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                );
                
                // Ajouter les nouvelles colonnes seulement si elles existent
                if (in_array('start_time', $columns)) {
                    $data['start_time'] = $start_time;
                }
                if (in_array('end_time', $columns)) {
                    $data['end_time'] = $end_time;
                }
                if (in_array('google_event_id', $columns)) {
                    $data['google_event_id'] = $event['id'];
                }
                
                $wpdb->replace($wpdb->prefix . 'restaurant_availability', $data);
            }
        }
    }
    
    /**
     * Synchroniser depuis WordPress vers Google
     */
    private function sync_to_google()
    {
        global $wpdb;
        
        // Vérifier si la colonne google_event_id existe
        $columns = $wpdb->get_col("DESCRIBE {$wpdb->prefix}restaurant_availability", 0);
        $has_google_event_id = in_array('google_event_id', $columns);
        
        // Construire la requête en fonction des colonnes disponibles
        if ($has_google_event_id) {
            $blocked_dates = $wpdb->get_results($wpdb->prepare("
                SELECT * FROM {$wpdb->prefix}restaurant_availability 
                WHERE is_available = 0 
                AND date >= %s 
                AND date <= %s
                AND (google_event_id IS NULL OR google_event_id = '')
            ", date('Y-m-d'), date('Y-m-d', strtotime('+3 months'))));
        } else {
            // Si la colonne n'existe pas, récupérer tous les blocages
            $blocked_dates = $wpdb->get_results($wpdb->prepare("
                SELECT * FROM {$wpdb->prefix}restaurant_availability 
                WHERE is_available = 0 
                AND date >= %s 
                AND date <= %s
            ", date('Y-m-d'), date('Y-m-d', strtotime('+3 months'))));
        }

        foreach ($blocked_dates as $blocked) {
            $title = 'Block & Co - ' . ucfirst($blocked->service_type) . ' indisponible';
            $description = $blocked->notes . "\n\nSynchronisé depuis WordPress";
            
            $result = $this->create_event($title, $blocked->date, $description);
            
            if ($result && isset($result['id'])) {
                // Mettre à jour avec l'ID de l'événement Google
                $wpdb->update(
                    $wpdb->prefix . 'restaurant_availability',
                    array('google_event_id' => $result['id']),
                    array('id' => $blocked->id)
                );
            }
        }
    }
    
    /**
     * Détecter le type de service
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
     * Tester la connexion
     */
    public function test_connection()
    {
        if (empty($this->access_token)) {
            return array('status' => 'error', 'message' => 'Token d\'accès manquant');
        }
        
        $response = $this->list_events();
        
        if ($response) {
            return array('status' => 'success', 'message' => 'Connexion réussie');
        } else {
            return array('status' => 'error', 'message' => 'Erreur de connexion');
        }
    }
    
    /**
     * Faire une requête HTTP
     */
    private function make_http_request($url, $data = null, $method = 'GET', $headers = array())
    {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                if (in_array('Content-Type: application/json', $headers)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                } else {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                }
            }
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response === false || $http_code >= 400) {
            return false;
        }
        
        return json_decode($response, true);
    }
}

