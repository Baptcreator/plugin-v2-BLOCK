<?php
/**
 * Gestionnaire des tailles de boissons
 *
 * @package RestaurantBooking
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Beverage_Size_Manager
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
        // Actions AJAX pour la gestion des tailles
        add_action('wp_ajax_restaurant_add_beverage_size', array($this, 'ajax_add_size'));
        add_action('wp_ajax_restaurant_delete_beverage_size', array($this, 'ajax_delete_size'));
        add_action('wp_ajax_restaurant_update_beverage_size', array($this, 'ajax_update_size'));
    }
    
    /**
     * Créer une taille de boisson
     */
    public static function create_size($data)
    {
        global $wpdb;

        // Validation des données obligatoires
        $required_fields = array('product_id', 'size_cl', 'size_label', 'price');
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || ($field !== 'price' && empty($data[$field]))) {
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

        // Vérifier que cette taille n'existe pas déjà pour ce produit
        $existing_size = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}restaurant_beverage_sizes 
             WHERE product_id = %d AND size_cl = %d AND is_active = 1",
            $data['product_id'], $data['size_cl']
        ));

        if ($existing_size > 0) {
            return new WP_Error('duplicate_size', __('Cette taille existe déjà pour ce produit', 'restaurant-booking'));
        }

        // Préparer les données pour l'insertion
        $size_data = array(
            'product_id' => (int) $data['product_id'],
            'size_cl' => (int) $data['size_cl'],
            'size_label' => sanitize_text_field($data['size_label']),
            'price' => (float) $data['price'],
            'image_id' => isset($data['image_id']) && !empty($data['image_id']) ? (int) $data['image_id'] : null,
            'is_featured' => isset($data['is_featured']) ? (bool) $data['is_featured'] : false,
            'display_order' => isset($data['display_order']) ? (int) $data['display_order'] : 0,
            'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
            'created_at' => current_time('mysql')
        );

        // Insérer en base de données
        $result = $wpdb->insert(
            $wpdb->prefix . 'restaurant_beverage_sizes',
            $size_data,
            array('%d', '%d', '%s', '%f', '%d', '%d', '%d', '%d', '%s')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Erreur lors de la création de la taille', 'restaurant-booking'));
        }

        // Marquer le produit comme ayant des tailles multiples
        $wpdb->update(
            $wpdb->prefix . 'restaurant_products',
            array('has_multiple_sizes' => 1),
            array('id' => $data['product_id']),
            array('%d'),
            array('%d')
        );

        return $wpdb->insert_id;
    }
    
    /**
     * Mettre à jour une taille de boisson
     */
    public static function update_size($size_id, $data)
    {
        global $wpdb;

        // Vérifier que la taille existe
        $existing_size = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}restaurant_beverage_sizes WHERE id = %d",
            $size_id
        ));

        if (!$existing_size) {
            return new WP_Error('invalid_size', __('Taille invalide', 'restaurant-booking'));
        }

        // Préparer les données pour la mise à jour
        $update_data = array();
        $update_format = array();

        if (isset($data['size_cl'])) {
            $update_data['size_cl'] = (int) $data['size_cl'];
            $update_format[] = '%d';
        }
        if (isset($data['size_label'])) {
            $update_data['size_label'] = sanitize_text_field($data['size_label']);
            $update_format[] = '%s';
        }
        if (isset($data['price'])) {
            $update_data['price'] = (float) $data['price'];
            $update_format[] = '%f';
        }
        if (isset($data['image_id'])) {
            $update_data['image_id'] = !empty($data['image_id']) ? (int) $data['image_id'] : null;
            $update_format[] = '%d';
        }
        if (isset($data['is_featured'])) {
            $update_data['is_featured'] = (bool) $data['is_featured'];
            $update_format[] = '%d';
        }
        if (isset($data['display_order'])) {
            $update_data['display_order'] = (int) $data['display_order'];
            $update_format[] = '%d';
        }
        if (isset($data['is_active'])) {
            $update_data['is_active'] = (bool) $data['is_active'];
            $update_format[] = '%d';
        }

        $update_data['updated_at'] = current_time('mysql');
        $update_format[] = '%s';

        // Effectuer la mise à jour
        $result = $wpdb->update(
            $wpdb->prefix . 'restaurant_beverage_sizes',
            $update_data,
            array('id' => $size_id),
            $update_format,
            array('%d')
        );

        return $result !== false;
    }
    
    /**
     * Obtenir les tailles d'un produit
     */
    public static function get_product_sizes($product_id, $active_only = true)
    {
        global $wpdb;
        
        $where_clause = "WHERE product_id = %d";
        $params = array($product_id);
        
        if ($active_only) {
            $where_clause .= " AND is_active = 1";
        }
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}restaurant_beverage_sizes 
             $where_clause 
             ORDER BY display_order ASC, size_cl ASC",
            $params
        ));
    }
    
    /**
     * Obtenir une taille par ID
     */
    public static function get_size($size_id)
    {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}restaurant_beverage_sizes WHERE id = %d",
            $size_id
        ));
    }
    
    /**
     * Supprimer une taille
     */
    public static function delete_size($size_id)
    {
        global $wpdb;
        
        // Obtenir les infos de la taille avant suppression
        $size = self::get_size($size_id);
        if (!$size) {
            return false;
        }
        
        // Supprimer la taille
        $result = $wpdb->delete(
            $wpdb->prefix . 'restaurant_beverage_sizes',
            array('id' => $size_id),
            array('%d')
        );
        
        // Vérifier s'il reste des tailles pour ce produit
        $remaining_sizes = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}restaurant_beverage_sizes 
             WHERE product_id = %d AND is_active = 1",
            $size->product_id
        ));
        
        // Si plus de tailles, démarquer le produit
        if ($remaining_sizes == 0) {
            $wpdb->update(
                $wpdb->prefix . 'restaurant_products',
                array('has_multiple_sizes' => 0),
                array('id' => $size->product_id),
                array('%d'),
                array('%d')
            );
        }
        
        return $result !== false;
    }
    
    /**
     * AJAX - Ajouter une taille
     */
    public function ajax_add_size()
    {
        check_ajax_referer('restaurant_booking_admin', 'nonce');
        
        if (!current_user_can('manage_restaurant_quotes')) {
            wp_die(__('Permissions insuffisantes', 'restaurant-booking'));
        }
        
        $result = self::create_size($_POST);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(array('size_id' => $result));
        }
    }
    
    /**
     * AJAX - Mettre à jour une taille
     */
    public function ajax_update_size()
    {
        check_ajax_referer('restaurant_booking_admin', 'nonce');
        
        if (!current_user_can('manage_restaurant_quotes')) {
            wp_die(__('Permissions insuffisantes', 'restaurant-booking'));
        }
        
        $size_id = intval($_POST['size_id']);
        $result = self::update_size($size_id, $_POST);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success();
        }
    }
    
    /**
     * AJAX - Supprimer une taille
     */
    public function ajax_delete_size()
    {
        check_ajax_referer('restaurant_booking_admin', 'nonce');
        
        if (!current_user_can('manage_restaurant_quotes')) {
            wp_die(__('Permissions insuffisantes', 'restaurant-booking'));
        }
        
        $size_id = intval($_POST['size_id']);
        $result = self::delete_size($size_id);
        
        if ($result === false) {
            wp_send_json_error(__('Erreur lors de la suppression', 'restaurant-booking'));
        } else {
            wp_send_json_success();
        }
    }
}
