<?php
/**
 * Gestionnaire des options d'accompagnements
 *
 * @package RestaurantBooking
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Accompaniment_Option_Manager
{
    /**
     * Instance unique
     */
    private static $instance = null;
    
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
        // Actions AJAX pour la gestion des options
        add_action('wp_ajax_restaurant_add_accompaniment_option', array($this, 'ajax_add_option'));
        add_action('wp_ajax_restaurant_delete_accompaniment_option', array($this, 'ajax_delete_option'));
        add_action('wp_ajax_restaurant_add_accompaniment_suboption', array($this, 'ajax_add_suboption'));
        add_action('wp_ajax_restaurant_delete_accompaniment_suboption', array($this, 'ajax_delete_suboption'));
    }
    
    /**
     * Créer une option d'accompagnement
     */
    public static function create_option($data)
    {
        global $wpdb;

        // Validation des données obligatoires
        $required_fields = array('product_id', 'option_name', 'option_price');
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || ($field !== 'option_price' && empty($data[$field]))) {
                return new WP_Error('missing_field', sprintf(__('Le champ %s est obligatoire', 'restaurant-booking'), $field));
            }
        }

        // Vérifier que le produit existe
        $product_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}restaurant_products WHERE id = %d AND is_active = 1",
            $data['product_id']
        ));

        if (!$product_exists) {
            return new WP_Error('invalid_product', __('Produit invalide', 'restaurant-booking'));
        }

        // Préparer les données pour l'insertion
        $option_data = array(
            'product_id' => (int) $data['product_id'],
            'option_name' => sanitize_text_field($data['option_name']),
            'option_price' => (float) $data['option_price'],
            'display_order' => isset($data['display_order']) ? (int) $data['display_order'] : 0,
            'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
            'created_at' => current_time('mysql')
        );

        // Insérer en base de données
        $result = $wpdb->insert(
            $wpdb->prefix . 'restaurant_accompaniment_options',
            $option_data,
            array('%d', '%s', '%f', '%d', '%d', '%s')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Erreur lors de la création de l\'option', 'restaurant-booking'));
        }

        return $wpdb->insert_id;
    }
    
    /**
     * Créer une sous-option d'accompagnement
     */
    public static function create_suboption($data)
    {
        global $wpdb;

        // Validation des données obligatoires
        $required_fields = array('option_id', 'suboption_name');
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return new WP_Error('missing_field', sprintf(__('Le champ %s est obligatoire', 'restaurant-booking'), $field));
            }
        }

        // Vérifier que l'option existe
        $option_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}restaurant_accompaniment_options WHERE id = %d AND is_active = 1",
            $data['option_id']
        ));

        if (!$option_exists) {
            return new WP_Error('invalid_option', __('Option invalide', 'restaurant-booking'));
        }

        // Préparer les données pour l'insertion
        $suboption_data = array(
            'option_id' => (int) $data['option_id'],
            'suboption_name' => sanitize_text_field($data['suboption_name']),
            'display_order' => isset($data['display_order']) ? (int) $data['display_order'] : 0,
            'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
            'created_at' => current_time('mysql')
        );

        // Insérer en base de données
        $result = $wpdb->insert(
            $wpdb->prefix . 'restaurant_accompaniment_suboptions',
            $suboption_data,
            array('%d', '%s', '%d', '%d', '%s')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Erreur lors de la création de la sous-option', 'restaurant-booking'));
        }

        return $wpdb->insert_id;
    }
    
    /**
     * Obtenir les options d'un produit
     */
    public static function get_product_options($product_id)
    {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}restaurant_accompaniment_options 
             WHERE product_id = %d AND is_active = 1 
             ORDER BY display_order ASC, option_name ASC",
            $product_id
        ));
    }
    
    /**
     * Obtenir les sous-options d'une option
     */
    public static function get_option_suboptions($option_id)
    {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}restaurant_accompaniment_suboptions 
             WHERE option_id = %d AND is_active = 1 
             ORDER BY display_order ASC, suboption_name ASC",
            $option_id
        ));
    }
    
    /**
     * Supprimer une option
     */
    public static function delete_option($option_id)
    {
        global $wpdb;
        
        // Supprimer d'abord les sous-options
        $wpdb->delete(
            $wpdb->prefix . 'restaurant_accompaniment_suboptions',
            array('option_id' => $option_id),
            array('%d')
        );
        
        // Supprimer l'option
        return $wpdb->delete(
            $wpdb->prefix . 'restaurant_accompaniment_options',
            array('id' => $option_id),
            array('%d')
        );
    }
    
    /**
     * Supprimer une sous-option
     */
    public static function delete_suboption($suboption_id)
    {
        global $wpdb;
        
        return $wpdb->delete(
            $wpdb->prefix . 'restaurant_accompaniment_suboptions',
            array('id' => $suboption_id),
            array('%d')
        );
    }
    
    /**
     * AJAX - Ajouter une option
     */
    public function ajax_add_option()
    {
        check_ajax_referer('restaurant_booking_admin', 'nonce');
        
        if (!current_user_can('manage_restaurant_quotes')) {
            wp_die(__('Permissions insuffisantes', 'restaurant-booking'));
        }
        
        $result = self::create_option($_POST);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(array('option_id' => $result));
        }
    }
    
    /**
     * AJAX - Supprimer une option
     */
    public function ajax_delete_option()
    {
        check_ajax_referer('restaurant_booking_admin', 'nonce');
        
        if (!current_user_can('manage_restaurant_quotes')) {
            wp_die(__('Permissions insuffisantes', 'restaurant-booking'));
        }
        
        $option_id = intval($_POST['option_id']);
        $result = self::delete_option($option_id);
        
        if ($result === false) {
            wp_send_json_error(__('Erreur lors de la suppression', 'restaurant-booking'));
        } else {
            wp_send_json_success();
        }
    }
    
    /**
     * AJAX - Ajouter une sous-option
     */
    public function ajax_add_suboption()
    {
        check_ajax_referer('restaurant_booking_admin', 'nonce');
        
        if (!current_user_can('manage_restaurant_quotes')) {
            wp_die(__('Permissions insuffisantes', 'restaurant-booking'));
        }
        
        $result = self::create_suboption($_POST);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(array('suboption_id' => $result));
        }
    }
    
    /**
     * AJAX - Supprimer une sous-option
     */
    public function ajax_delete_suboption()
    {
        check_ajax_referer('restaurant_booking_admin', 'nonce');
        
        if (!current_user_can('manage_restaurant_quotes')) {
            wp_die(__('Permissions insuffisantes', 'restaurant-booking'));
        }
        
        $suboption_id = intval($_POST['suboption_id']);
        $result = self::delete_suboption($suboption_id);
        
        if ($result === false) {
            wp_send_json_error(__('Erreur lors de la suppression', 'restaurant-booking'));
        } else {
            wp_send_json_success();
        }
    }
}
