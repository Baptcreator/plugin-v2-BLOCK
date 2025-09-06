<?php
/**
 * Classe de calcul des distances par code postal
 *
 * @package RestaurantBooking
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Distance_Calculator
{
    /**
     * Instance unique
     */
    private static $instance = null;

    /**
     * Code postal du restaurant (Strasbourg)
     */
    const RESTAURANT_POSTAL_CODE = '67000';

    /**
     * Coordonnées du restaurant (Strasbourg)
     */
    const RESTAURANT_LAT = 48.5734;
    const RESTAURANT_LNG = 7.7521;

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
        add_action('init', array($this, 'init'));
    }

    /**
     * Initialisation
     */
    public function init()
    {
        // Hooks AJAX
        add_action('wp_ajax_calculate_delivery_distance', array($this, 'ajax_calculate_delivery_distance'));
        add_action('wp_ajax_nopriv_calculate_delivery_distance', array($this, 'ajax_calculate_delivery_distance'));
        add_action('wp_ajax_get_delivery_zones', array($this, 'ajax_get_delivery_zones'));
        add_action('wp_ajax_nopriv_get_delivery_zones', array($this, 'ajax_get_delivery_zones'));
    }

    /**
     * Calculer la distance entre deux codes postaux
     */
    public static function calculate_distance($postal_code_from, $postal_code_to)
    {
        // Obtenir les coordonnées des codes postaux
        $coords_from = self::get_postal_code_coordinates($postal_code_from);
        $coords_to = self::get_postal_code_coordinates($postal_code_to);

        if (!$coords_from || !$coords_to) {
            return new WP_Error('invalid_postal_code', __('Code postal invalide', 'restaurant-booking'));
        }

        // Calculer la distance en utilisant la formule de Haversine
        $distance = self::haversine_distance(
            $coords_from['lat'], $coords_from['lng'],
            $coords_to['lat'], $coords_to['lng']
        );

        return round($distance);
    }

    /**
     * Calculer la distance depuis le restaurant
     */
    public static function calculate_distance_from_restaurant($postal_code)
    {
        $coords = self::get_postal_code_coordinates($postal_code);
        
        if (!$coords) {
            return new WP_Error('invalid_postal_code', __('Code postal invalide', 'restaurant-booking'));
        }

        $distance = self::haversine_distance(
            self::RESTAURANT_LAT, self::RESTAURANT_LNG,
            $coords['lat'], $coords['lng']
        );

        return round($distance);
    }

    /**
     * Obtenir les coordonnées d'un code postal
     */
    private static function get_postal_code_coordinates($postal_code)
    {
        // Nettoyer le code postal
        $postal_code = preg_replace('/[^0-9]/', '', $postal_code);
        
        if (strlen($postal_code) !== 5) {
            return false;
        }

        // Cache des coordonnées
        $cache_key = 'postal_coords_' . $postal_code;
        $cached_coords = wp_cache_get($cache_key, 'restaurant_booking');
        
        if ($cached_coords !== false) {
            return $cached_coords;
        }

        // Base de données simplifiée des codes postaux français
        // En production, utiliser une vraie base de données ou API
        $coords = self::get_french_postal_coordinates($postal_code);
        
        if ($coords) {
            // Mettre en cache pour 24h
            wp_cache_set($cache_key, $coords, 'restaurant_booking', 24 * HOUR_IN_SECONDS);
        }

        return $coords;
    }

    /**
     * Base de données simplifiée des coordonnées françaises
     */
    private static function get_french_postal_coordinates($postal_code)
    {
        // Coordonnées approximatives par département
        $dept_coords = array(
            '01' => array('lat' => 46.2044, 'lng' => 5.2255),  // Ain
            '02' => array('lat' => 49.5667, 'lng' => 3.6167),  // Aisne
            '03' => array('lat' => 46.5667, 'lng' => 2.6000),  // Allier
            '04' => array('lat' => 44.0833, 'lng' => 6.2333),  // Alpes-de-Haute-Provence
            '05' => array('lat' => 44.6667, 'lng' => 6.0833),  // Hautes-Alpes
            '06' => array('lat' => 43.7000, 'lng' => 7.2667),  // Alpes-Maritimes
            '07' => array('lat' => 44.7333, 'lng' => 4.6000),  // Ardèche
            '08' => array('lat' => 49.7667, 'lng' => 4.7167),  // Ardennes
            '09' => array('lat' => 43.0000, 'lng' => 1.6000),  // Ariège
            '10' => array('lat' => 48.3000, 'lng' => 4.0833),  // Aube
            '11' => array('lat' => 43.2167, 'lng' => 2.3500),  // Aude
            '12' => array('lat' => 44.3500, 'lng' => 2.5667),  // Aveyron
            '13' => array('lat' => 43.5333, 'lng' => 5.1333),  // Bouches-du-Rhône
            '14' => array('lat' => 49.1833, 'lng' => -0.3667), // Calvados
            '15' => array('lat' => 45.0333, 'lng' => 2.4167),  // Cantal
            '16' => array('lat' => 45.6500, 'lng' => 0.1500),  // Charente
            '17' => array('lat' => 45.7500, 'lng' => -0.6333), // Charente-Maritime
            '18' => array('lat' => 47.0833, 'lng' => 2.4000),  // Cher
            '19' => array('lat' => 45.2667, 'lng' => 1.7667),  // Corrèze
            '21' => array('lat' => 47.3167, 'lng' => 5.0167),  // Côte-d'Or
            '22' => array('lat' => 48.5167, 'lng' => -2.7667), // Côtes-d'Armor
            '23' => array('lat' => 46.1667, 'lng' => 1.8667),  // Creuse
            '24' => array('lat' => 45.1833, 'lng' => 0.7167),  // Dordogne
            '25' => array('lat' => 47.2500, 'lng' => 6.0333),  // Doubs
            '26' => array('lat' => 44.7333, 'lng' => 5.0500),  // Drôme
            '27' => array('lat' => 49.0167, 'lng' => 1.1500),  // Eure
            '28' => array('lat' => 48.4500, 'lng' => 1.4833),  // Eure-et-Loir
            '29' => array('lat' => 48.1000, 'lng' => -4.1000), // Finistère
            '30' => array('lat' => 43.8333, 'lng' => 4.3667),  // Gard
            '31' => array('lat' => 43.6000, 'lng' => 1.4333),  // Haute-Garonne
            '32' => array('lat' => 43.6500, 'lng' => 0.5833),  // Gers
            '33' => array('lat' => 44.8333, 'lng' => -0.5667), // Gironde
            '34' => array('lat' => 43.6167, 'lng' => 3.8667),  // Hérault
            '35' => array('lat' => 48.1167, 'lng' => -1.6833), // Ille-et-Vilaine
            '36' => array('lat' => 46.8167, 'lng' => 1.6833),  // Indre
            '37' => array('lat' => 47.3833, 'lng' => 0.6833),  // Indre-et-Loire
            '38' => array('lat' => 45.1667, 'lng' => 5.7167),  // Isère
            '39' => array('lat' => 46.6833, 'lng' => 5.9000),  // Jura
            '40' => array('lat' => 44.0000, 'lng' => -0.7833), // Landes
            '41' => array('lat' => 47.5833, 'lng' => 1.3333),  // Loir-et-Cher
            '42' => array('lat' => 45.4333, 'lng' => 4.3833),  // Loire
            '43' => array('lat' => 45.0433, 'lng' => 3.8850),  // Haute-Loire
            '44' => array('lat' => 47.2167, 'lng' => -1.5500), // Loire-Atlantique
            '45' => array('lat' => 47.9000, 'lng' => 1.9000),  // Loiret
            '46' => array('lat' => 44.4500, 'lng' => 1.4333),  // Lot
            '47' => array('lat' => 44.2000, 'lng' => 0.6167),  // Lot-et-Garonne
            '48' => array('lat' => 44.5167, 'lng' => 3.5000),  // Lozère
            '49' => array('lat' => 47.4667, 'lng' => -0.5500), // Maine-et-Loire
            '50' => array('lat' => 49.1167, 'lng' => -1.0833), // Manche
            '51' => array('lat' => 49.0433, 'lng' => 4.0317),  // Marne
            '52' => array('lat' => 48.1167, 'lng' => 5.1333),  // Haute-Marne
            '53' => array('lat' => 48.0667, 'lng' => -0.7667), // Mayenne
            '54' => array('lat' => 48.6833, 'lng' => 6.1833),  // Meurthe-et-Moselle
            '55' => array('lat' => 49.1667, 'lng' => 5.3833),  // Meuse
            '56' => array('lat' => 47.7500, 'lng' => -2.7500), // Morbihan
            '57' => array('lat' => 49.1167, 'lng' => 6.1667),  // Moselle
            '58' => array('lat' => 47.2167, 'lng' => 3.1667),  // Nièvre
            '59' => array('lat' => 50.6333, 'lng' => 3.0667),  // Nord
            '60' => array('lat' => 49.4167, 'lng' => 2.0833),  // Oise
            '61' => array('lat' => 48.7333, 'lng' => 0.0833),  // Orne
            '62' => array('lat' => 50.4167, 'lng' => 2.8333),  // Pas-de-Calais
            '63' => array('lat' => 45.7833, 'lng' => 3.0833),  // Puy-de-Dôme
            '64' => array('lat' => 43.3000, 'lng' => -0.3667), // Pyrénées-Atlantiques
            '65' => array('lat' => 43.2333, 'lng' => 0.0667),  // Hautes-Pyrénées
            '66' => array('lat' => 42.7000, 'lng' => 2.8833),  // Pyrénées-Orientales
            '67' => array('lat' => 48.5734, 'lng' => 7.7521),  // Bas-Rhin (Strasbourg)
            '68' => array('lat' => 47.7500, 'lng' => 7.3333),  // Haut-Rhin
            '69' => array('lat' => 45.7500, 'lng' => 4.8500),  // Rhône
            '70' => array('lat' => 47.6167, 'lng' => 6.1500),  // Haute-Saône
            '71' => array('lat' => 46.7833, 'lng' => 4.8333),  // Saône-et-Loire
            '72' => array('lat' => 48.0000, 'lng' => 0.2000),  // Sarthe
            '73' => array('lat' => 45.5667, 'lng' => 6.3167),  // Savoie
            '74' => array('lat' => 46.0667, 'lng' => 6.1167),  // Haute-Savoie
            '75' => array('lat' => 48.8566, 'lng' => 2.3522),  // Paris
            '76' => array('lat' => 49.4333, 'lng' => 1.0833),  // Seine-Maritime
            '77' => array('lat' => 48.8500, 'lng' => 2.6500),  // Seine-et-Marne
            '78' => array('lat' => 48.8000, 'lng' => 2.1333),  // Yvelines
            '79' => array('lat' => 46.3167, 'lng' => -0.4667), // Deux-Sèvres
            '80' => array('lat' => 49.8833, 'lng' => 2.3000),  // Somme
            '81' => array('lat' => 43.9283, 'lng' => 2.1475),  // Tarn
            '82' => array('lat' => 44.0167, 'lng' => 1.3500),  // Tarn-et-Garonne
            '83' => array('lat' => 43.4667, 'lng' => 6.2333),  // Var
            '84' => array('lat' => 44.0500, 'lng' => 5.0500),  // Vaucluse
            '85' => array('lat' => 46.6667, 'lng' => -1.4333), // Vendée
            '86' => array('lat' => 46.5833, 'lng' => 0.3333),  // Vienne
            '87' => array('lat' => 45.8333, 'lng' => 1.2667),  // Haute-Vienne
            '88' => array('lat' => 48.1667, 'lng' => 6.4500),  // Vosges
            '89' => array('lat' => 47.7983, 'lng' => 3.5681),  // Yonne
            '90' => array('lat' => 47.6333, 'lng' => 6.8667),  // Territoire de Belfort
            '91' => array('lat' => 48.6333, 'lng' => 2.4333),  // Essonne
            '92' => array('lat' => 48.8167, 'lng' => 2.2167),  // Hauts-de-Seine
            '93' => array('lat' => 48.9167, 'lng' => 2.4500),  // Seine-Saint-Denis
            '94' => array('lat' => 48.7833, 'lng' => 2.4500),  // Val-de-Marne
            '95' => array('lat' => 49.0333, 'lng' => 2.0833),  // Val-d'Oise
        );

        $dept_code = substr($postal_code, 0, 2);
        
        return isset($dept_coords[$dept_code]) ? $dept_coords[$dept_code] : false;
    }

    /**
     * Calculer la distance avec la formule de Haversine
     */
    private static function haversine_distance($lat1, $lng1, $lat2, $lng2)
    {
        $earth_radius = 6371; // Rayon de la Terre en kilomètres

        $lat1_rad = deg2rad($lat1);
        $lng1_rad = deg2rad($lng1);
        $lat2_rad = deg2rad($lat2);
        $lng2_rad = deg2rad($lng2);

        $delta_lat = $lat2_rad - $lat1_rad;
        $delta_lng = $lng2_rad - $lng1_rad;

        $a = sin($delta_lat / 2) * sin($delta_lat / 2) +
             cos($lat1_rad) * cos($lat2_rad) *
             sin($delta_lng / 2) * sin($delta_lng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earth_radius * $c;
    }

    /**
     * Obtenir le supplément de livraison selon la distance
     */
    public static function get_delivery_supplement($distance_km)
    {
        global $wpdb;

        // Obtenir les zones de livraison
        $zones = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}restaurant_delivery_zones 
             WHERE is_active = 1 
             ORDER BY distance_min ASC",
            ARRAY_A
        );

        foreach ($zones as $zone) {
            if ($distance_km >= $zone['distance_min'] && $distance_km <= $zone['distance_max']) {
                return array(
                    'zone' => $zone,
                    'supplement' => (float) $zone['delivery_price'],
                    'zone_name' => $zone['zone_name']
                );
            }
        }

        // Si aucune zone trouvée, vérifier si c'est hors limite
        $max_distance = RestaurantBooking_Settings::get('remorque_max_delivery_distance', 150);
        if ($distance_km > $max_distance) {
            return new WP_Error('distance_exceeded', 
                sprintf(__('Distance maximum de livraison dépassée (%d km)', 'restaurant-booking'), $max_distance)
            );
        }

        // Zone par défaut (gratuite)
        return array(
            'zone' => null,
            'supplement' => 0.00,
            'zone_name' => __('Zone gratuite', 'restaurant-booking')
        );
    }

    /**
     * Valider un code postal
     */
    public static function validate_postal_code($postal_code)
    {
        // Nettoyer le code postal
        $postal_code = preg_replace('/[^0-9]/', '', $postal_code);
        
        // Vérifier le format français (5 chiffres)
        if (!preg_match('/^[0-9]{5}$/', $postal_code)) {
            return new WP_Error('invalid_format', __('Le code postal doit contenir 5 chiffres', 'restaurant-booking'));
        }

        // Vérifier que le code postal existe dans notre base
        $coords = self::get_postal_code_coordinates($postal_code);
        if (!$coords) {
            return new WP_Error('postal_not_found', __('Code postal non trouvé', 'restaurant-booking'));
        }

        return true;
    }

    /**
     * Obtenir les zones de livraison actives
     */
    public static function get_delivery_zones()
    {
        global $wpdb;

        $zones = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}restaurant_delivery_zones 
             WHERE is_active = 1 
             ORDER BY distance_min ASC",
            ARRAY_A
        );

        foreach ($zones as &$zone) {
            $zone['delivery_price'] = (float) $zone['delivery_price'];
            $zone['distance_min'] = (int) $zone['distance_min'];
            $zone['distance_max'] = (int) $zone['distance_max'];
            $zone['is_active'] = (bool) $zone['is_active'];
        }

        return $zones;
    }

    /**
     * AJAX: Calculer la distance de livraison
     */
    public function ajax_calculate_delivery_distance()
    {
        $postal_code = sanitize_text_field($_POST['postal_code']);

        // Valider le code postal
        $validation = self::validate_postal_code($postal_code);
        if (is_wp_error($validation)) {
            wp_send_json_error($validation->get_error_message());
        }

        // Calculer la distance
        $distance = self::calculate_distance_from_restaurant($postal_code);
        if (is_wp_error($distance)) {
            wp_send_json_error($distance->get_error_message());
        }

        // Obtenir le supplément
        $delivery_info = self::get_delivery_supplement($distance);
        if (is_wp_error($delivery_info)) {
            wp_send_json_error($delivery_info->get_error_message());
        }

        wp_send_json_success(array(
            'postal_code' => $postal_code,
            'distance_km' => $distance,
            'delivery_supplement' => $delivery_info['supplement'],
            'zone_name' => $delivery_info['zone_name'],
            'zone_info' => $delivery_info['zone']
        ));
    }

    /**
     * AJAX: Obtenir les zones de livraison
     */
    public function ajax_get_delivery_zones()
    {
        $zones = self::get_delivery_zones();
        wp_send_json_success($zones);
    }
}
