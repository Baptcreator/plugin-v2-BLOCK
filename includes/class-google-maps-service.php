<?php
/**
 * Service Google Maps Distance Matrix API
 *
 * @package RestaurantBooking
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Google_Maps_Service
{
    /**
     * Instance unique
     */
    private static $instance = null;

    /**
     * URL de base de l'API Distance Matrix
     */
    const API_BASE_URL = 'https://maps.googleapis.com/maps/api/distancematrix/json';

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
     * Constructeur privé
     */
    private function __construct()
    {
        // Hooks AJAX pour le test de l'API
        add_action('wp_ajax_test_google_maps_api', array($this, 'ajax_test_api'));
    }

    /**
     * Calculer la distance entre deux codes postaux
     *
     * @param string $origin_postal_code Code postal d'origine
     * @param string $destination_postal_code Code postal de destination
     * @return array|WP_Error Résultat avec distance, durée et détails
     */
    public function calculate_distance($origin_postal_code, $destination_postal_code)
    {
        // Vérifier que l'API est configurée
        $api_key = get_option('restaurant_booking_google_maps_api_key', '');
        if (empty($api_key)) {
            return new WP_Error('no_api_key', __('Clé API Google Maps non configurée', 'restaurant-booking'));
        }

        // Valider les codes postaux
        if (!$this->validate_postal_code($origin_postal_code) || !$this->validate_postal_code($destination_postal_code)) {
            return new WP_Error('invalid_postal_code', __('Code postal invalide', 'restaurant-booking'));
        }

        // Construire l'URL de l'API
        $url = $this->build_api_url($origin_postal_code, $destination_postal_code, $api_key);

        // Effectuer la requête
        $response = $this->make_api_request($url);

        if (is_wp_error($response)) {
            return $response;
        }

        // Parser la réponse
        return $this->parse_api_response($response);
    }

    /**
     * Calculer la distance depuis le restaurant
     *
     * @param string $destination_postal_code Code postal de destination
     * @return array|WP_Error Résultat avec distance, durée et détails
     */
    public function calculate_distance_from_restaurant($destination_postal_code)
    {
        // Utiliser l'adresse complète si disponible, sinon le code postal
        $restaurant_address = get_option('restaurant_booking_restaurant_address', '');
        if (!empty($restaurant_address)) {
            $origin = $restaurant_address;
        } else {
            $restaurant_postal_code = get_option('restaurant_booking_restaurant_postal_code', '67000');
            $origin = $restaurant_postal_code . ',France';
        }
        
        $destination = $destination_postal_code . ',France';
        
        return $this->calculate_distance_with_addresses($origin, $destination);
    }

    /**
     * Calculer la distance entre deux adresses (au lieu de codes postaux)
     *
     * @param string $origin_address Adresse d'origine
     * @param string $destination_address Adresse de destination
     * @return array|WP_Error Résultat avec distance, durée et détails
     */
    public function calculate_distance_with_addresses($origin_address, $destination_address)
    {
        // Vérifier que l'API est configurée
        $api_key = get_option('restaurant_booking_google_maps_api_key', '');
        if (empty($api_key)) {
            return new WP_Error('no_api_key', __('Clé API Google Maps non configurée', 'restaurant-booking'));
        }

        // Construire l'URL de l'API avec les adresses
        $url = $this->build_api_url_with_addresses($origin_address, $destination_address, $api_key);

        // Effectuer la requête
        $response = $this->make_api_request($url);

        if (is_wp_error($response)) {
            return $response;
        }

        // Parser la réponse
        return $this->parse_api_response($response);
    }

    /**
     * Construire l'URL de l'API avec adresses complètes
     *
     * @param string $origin_address Adresse d'origine
     * @param string $destination_address Adresse de destination
     * @param string $api_key Clé API
     * @return string URL complète
     */
    private function build_api_url_with_addresses($origin_address, $destination_address, $api_key)
    {
        $params = array(
            'origins' => $origin_address,
            'destinations' => $destination_address,
            'units' => 'metric',
            'mode' => 'driving',
            'language' => 'fr',
            'key' => $api_key
        );

        return self::API_BASE_URL . '?' . http_build_query($params);
    }

    /**
     * Construire l'URL de l'API (ancienne méthode avec codes postaux)
     *
     * @param string $origin Code postal d'origine
     * @param string $destination Code postal de destination
     * @param string $api_key Clé API
     * @return string URL complète
     */
    private function build_api_url($origin, $destination, $api_key)
    {
        $params = array(
            'origins' => $origin . ',France',
            'destinations' => $destination . ',France',
            'units' => 'metric',
            'mode' => 'driving',
            'language' => 'fr',
            'key' => $api_key
        );

        return self::API_BASE_URL . '?' . http_build_query($params);
    }

    /**
     * Effectuer la requête API
     *
     * @param string $url URL de l'API
     * @return array|WP_Error Réponse de l'API ou erreur
     */
    private function make_api_request($url)
    {
        // Vérifier le cache
        $cache_key = 'gmaps_' . md5($url);
        $cached_response = wp_cache_get($cache_key, 'restaurant_booking_maps');
        
        if ($cached_response !== false) {
            RestaurantBooking_Logger::info('Distance récupérée depuis le cache', array('cache_key' => $cache_key));
            return $cached_response;
        }

        // Effectuer la requête
        $response = wp_remote_get($url, array(
            'timeout' => 10,
            'headers' => array(
                'User-Agent' => 'Restaurant Booking Plugin'
            )
        ));

        if (is_wp_error($response)) {
            RestaurantBooking_Logger::error('Erreur requête Google Maps API', array(
                'error' => $response->get_error_message(),
                'url' => $url
            ));
            return new WP_Error('api_request_failed', __('Erreur de connexion à l\'API Google Maps', 'restaurant-booking'));
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            RestaurantBooking_Logger::error('Erreur HTTP Google Maps API', array(
                'response_code' => $response_code,
                'url' => $url
            ));
            return new WP_Error('api_http_error', sprintf(__('Erreur HTTP %d de l\'API Google Maps', 'restaurant-booking'), $response_code));
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            RestaurantBooking_Logger::error('Erreur JSON Google Maps API', array(
                'json_error' => json_last_error_msg(),
                'body' => $body
            ));
            return new WP_Error('api_json_error', __('Réponse invalide de l\'API Google Maps', 'restaurant-booking'));
        }

        // Mettre en cache pour 1 heure
        wp_cache_set($cache_key, $data, 'restaurant_booking_maps', HOUR_IN_SECONDS);

        return $data;
    }

    /**
     * Parser la réponse de l'API
     *
     * @param array $response Réponse de l'API
     * @return array|WP_Error Données parsées ou erreur
     */
    private function parse_api_response($response)
    {
        // Vérifier le statut global
        if (!isset($response['status']) || $response['status'] !== 'OK') {
            $error_message = $this->get_api_error_message($response['status'] ?? 'UNKNOWN_ERROR');
            RestaurantBooking_Logger::error('Erreur statut Google Maps API', array(
                'status' => $response['status'] ?? 'UNKNOWN',
                'response' => $response
            ));
            return new WP_Error('api_status_error', $error_message);
        }

        // Vérifier qu'il y a des résultats
        if (!isset($response['rows'][0]['elements'][0])) {
            return new WP_Error('no_results', __('Aucun résultat trouvé', 'restaurant-booking'));
        }

        $element = $response['rows'][0]['elements'][0];

        // Vérifier le statut de l'élément
        if ($element['status'] !== 'OK') {
            $error_message = $this->get_element_error_message($element['status']);
            return new WP_Error('element_error', $error_message);
        }

        // Extraire les données
        $distance_km = round($element['distance']['value'] / 1000, 1);
        $duration_minutes = round($element['duration']['value'] / 60);

        $result = array(
            'distance_km' => $distance_km,
            'distance_text' => $element['distance']['text'],
            'duration_minutes' => $duration_minutes,
            'duration_text' => $element['duration']['text'],
            'origin' => $response['origin_addresses'][0] ?? '',
            'destination' => $response['destination_addresses'][0] ?? ''
        );

        RestaurantBooking_Logger::info('Distance calculée avec Google Maps', $result);

        return $result;
    }

    /**
     * Obtenir le message d'erreur pour un statut API
     *
     * @param string $status Statut de l'API
     * @return string Message d'erreur
     */
    private function get_api_error_message($status)
    {
        $messages = array(
            'INVALID_REQUEST' => __('Requête invalide', 'restaurant-booking'),
            'MAX_ELEMENTS_EXCEEDED' => __('Trop d\'éléments dans la requête', 'restaurant-booking'),
            'OVER_DAILY_LIMIT' => __('Quota quotidien dépassé', 'restaurant-booking'),
            'OVER_QUERY_LIMIT' => __('Limite de requêtes dépassée', 'restaurant-booking'),
            'REQUEST_DENIED' => __('Requête refusée - vérifiez votre clé API', 'restaurant-booking'),
            'UNKNOWN_ERROR' => __('Erreur inconnue du serveur', 'restaurant-booking')
        );

        return $messages[$status] ?? sprintf(__('Erreur API: %s', 'restaurant-booking'), $status);
    }

    /**
     * Obtenir le message d'erreur pour un statut d'élément
     *
     * @param string $status Statut de l'élément
     * @return string Message d'erreur
     */
    private function get_element_error_message($status)
    {
        $messages = array(
            'NOT_FOUND' => __('Adresse non trouvée', 'restaurant-booking'),
            'ZERO_RESULTS' => __('Aucun itinéraire trouvé', 'restaurant-booking'),
            'MAX_ROUTE_LENGTH_EXCEEDED' => __('Itinéraire trop long', 'restaurant-booking')
        );

        return $messages[$status] ?? sprintf(__('Erreur élément: %s', 'restaurant-booking'), $status);
    }

    /**
     * Valider un code postal français
     *
     * @param string $postal_code Code postal à valider
     * @return bool True si valide
     */
    private function validate_postal_code($postal_code)
    {
        return preg_match('/^[0-9]{5}$/', $postal_code);
    }

    /**
     * AJAX: Tester l'API Google Maps
     */
    public function ajax_test_api()
    {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['nonce'], 'test_google_maps_api')) {
            wp_send_json_error(__('Token de sécurité invalide', 'restaurant-booking'));
        }

        // Vérifier les permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissions insuffisantes', 'restaurant-booking'));
        }

        $api_key = sanitize_text_field($_POST['api_key']);
        $restaurant_address = sanitize_text_field($_POST['restaurant_address']);

        if (empty($api_key)) {
            wp_send_json_error(__('Clé API manquante', 'restaurant-booking'));
        }

        // Sauvegarder temporairement la clé et l'adresse pour le test
        $old_api_key = get_option('restaurant_booking_google_maps_api_key', '');
        $old_address = get_option('restaurant_booking_restaurant_address', '');
        update_option('restaurant_booking_google_maps_api_key', $api_key);
        update_option('restaurant_booking_restaurant_address', $restaurant_address);

        // Tester avec un code postal de test (Paris)
        $test_postal_code = '75001';
        $result = $this->calculate_distance_from_restaurant($test_postal_code);

        // Restaurer les anciennes valeurs
        update_option('restaurant_booking_google_maps_api_key', $old_api_key);
        update_option('restaurant_booking_restaurant_address', $old_address);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        $message = sprintf(
            __('API fonctionnelle ! Test: %s → %s = %s km (%s)', 'restaurant-booking'),
            $restaurant_postal,
            $test_postal_code,
            $result['distance_km'],
            $result['duration_text']
        );

        $details = sprintf(
            __('Distance: %s<br>Durée: %s<br>Origine: %s<br>Destination: %s', 'restaurant-booking'),
            $result['distance_text'],
            $result['duration_text'],
            $result['origin'],
            $result['destination']
        );

        wp_send_json_success(array(
            'message' => $message,
            'details' => $details
        ));
    }
}
