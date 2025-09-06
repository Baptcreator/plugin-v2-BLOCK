<?php
/**
 * Gestionnaire des suppléments multiples pour produits
 *
 * @package RestaurantBooking
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Product_Supplement_Manager
{
    private static $instance = null;

    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('wp_ajax_add_product_supplement', array($this, 'ajax_add_supplement'));
        add_action('wp_ajax_update_product_supplement', array($this, 'ajax_update_supplement'));
        add_action('wp_ajax_delete_product_supplement', array($this, 'ajax_delete_supplement'));
        add_action('wp_ajax_get_product_supplements', array($this, 'ajax_get_supplements'));
    }

    /**
     * Créer un nouveau supplément pour un produit
     */
    public function create_supplement($product_id, $supplement_name, $supplement_price, $max_quantity = null, $display_order = 0)
    {
        global $wpdb;

        $result = $wpdb->insert(
            $wpdb->prefix . 'restaurant_product_supplements_v2',
            array(
                'product_id' => $product_id,
                'supplement_name' => $supplement_name,
                'supplement_price' => $supplement_price,
                'max_quantity' => $max_quantity,
                'display_order' => $display_order,
                'is_active' => 1,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array('%d', '%s', '%f', '%d', '%d', '%d', '%s', '%s')
        );

        if ($result === false) {
            return new WP_Error('db_error', 'Erreur lors de la création du supplément: ' . $wpdb->last_error);
        }

        return $wpdb->insert_id;
    }

    /**
     * Mettre à jour un supplément
     */
    public function update_supplement($supplement_id, $data)
    {
        global $wpdb;

        $data['updated_at'] = current_time('mysql');

        $result = $wpdb->update(
            $wpdb->prefix . 'restaurant_product_supplements_v2',
            $data,
            array('id' => $supplement_id),
            array('%s', '%f', '%d', '%d', '%d', '%s'),
            array('%d')
        );

        return $result !== false;
    }

    /**
     * Supprimer un supplément
     */
    public function delete_supplement($supplement_id)
    {
        global $wpdb;

        $result = $wpdb->delete(
            $wpdb->prefix . 'restaurant_product_supplements_v2',
            array('id' => $supplement_id),
            array('%d')
        );

        return $result !== false;
    }

    /**
     * Récupérer tous les suppléments d'un produit
     */
    public function get_product_supplements($product_id)
    {
        global $wpdb;

        $supplements = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}restaurant_product_supplements_v2 
             WHERE product_id = %d AND is_active = 1 
             ORDER BY display_order ASC, supplement_name ASC",
            $product_id
        ), ARRAY_A);

        return $supplements ? $supplements : array();
    }

    /**
     * AJAX: Ajouter un supplément
     */
    public function ajax_add_supplement()
    {
        check_ajax_referer('restaurant_booking_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Accès refusé');
        }

        $product_id = intval($_POST['product_id']);
        $supplement_name = sanitize_text_field($_POST['supplement_name']);
        $supplement_price = floatval($_POST['supplement_price']);
        $max_quantity = !empty($_POST['max_quantity']) ? intval($_POST['max_quantity']) : null;

        $supplement_id = $this->create_supplement($product_id, $supplement_name, $supplement_price, $max_quantity);

        if (is_wp_error($supplement_id)) {
            wp_send_json_error($supplement_id->get_error_message());
        }

        $supplement = $this->get_supplement_by_id($supplement_id);
        wp_send_json_success($supplement);
    }

    /**
     * AJAX: Supprimer un supplément
     */
    public function ajax_delete_supplement()
    {
        check_ajax_referer('restaurant_booking_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Accès refusé');
        }

        $supplement_id = intval($_POST['supplement_id']);
        $result = $this->delete_supplement($supplement_id);

        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Erreur lors de la suppression');
        }
    }

    /**
     * AJAX: Récupérer les suppléments d'un produit
     */
    public function ajax_get_supplements()
    {
        check_ajax_referer('restaurant_booking_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Accès refusé');
        }

        $product_id = intval($_POST['product_id']);
        $supplements = $this->get_product_supplements($product_id);

        wp_send_json_success($supplements);
    }

    /**
     * Récupérer un supplément par ID
     */
    private function get_supplement_by_id($supplement_id)
    {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}restaurant_product_supplements_v2 WHERE id = %d",
            $supplement_id
        ), ARRAY_A);
    }
}
