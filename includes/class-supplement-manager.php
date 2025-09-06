<?php
/**
 * Classe de gestion des suppléments de produits
 *
 * @package RestaurantBooking
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Supplement_Manager
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
        add_action('init', array($this, 'init'));
    }

    /**
     * Initialisation
     */
    public function init()
    {
        // Hooks pour l'admin
        if (is_admin()) {
            add_action('wp_ajax_create_product_supplement', array($this, 'ajax_create_supplement'));
            add_action('wp_ajax_update_product_supplement', array($this, 'ajax_update_supplement'));
            add_action('wp_ajax_delete_product_supplement', array($this, 'ajax_delete_supplement'));
            add_action('wp_ajax_get_product_supplements', array($this, 'ajax_get_product_supplements'));
        }

        // Hooks pour le frontend
        add_action('wp_ajax_validate_supplement_quantity', array($this, 'ajax_validate_supplement_quantity'));
        add_action('wp_ajax_nopriv_validate_supplement_quantity', array($this, 'ajax_validate_supplement_quantity'));
    }

    /**
     * Créer un nouveau supplément
     */
    public static function create($data)
    {
        global $wpdb;

        // Validation des données obligatoires
        $required_fields = array('product_id', 'name', 'price');
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

        // Préparer les données pour l'insertion
        $supplement_data = array(
            'product_id' => (int) $data['product_id'],
            'name' => sanitize_text_field($data['name']),
            'price' => (float) $data['price'],
            'max_quantity' => isset($data['max_quantity']) && !empty($data['max_quantity']) ? (int) $data['max_quantity'] : null,
            'display_order' => isset($data['display_order']) ? (int) $data['display_order'] : 0,
            'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
            'created_at' => current_time('mysql')
        );

        // Insérer en base de données
        $result = $wpdb->insert(
            $wpdb->prefix . 'restaurant_product_supplements',
            $supplement_data,
            array('%d', '%s', '%f', '%d', '%d', '%d', '%s')
        );

        if ($result === false) {
            RestaurantBooking_Logger::error('Erreur lors de la création du supplément', array(
                'data' => $data,
                'error' => $wpdb->last_error
            ));
            return new WP_Error('db_error', __('Erreur lors de la création du supplément', 'restaurant-booking'));
        }

        $supplement_id = $wpdb->insert_id;

        // Log de la création
        RestaurantBooking_Logger::info("Nouveau supplément créé: {$data['name']}", array(
            'supplement_id' => $supplement_id,
            'product_id' => $data['product_id']
        ));

        return $supplement_id;
    }

    /**
     * Obtenir un supplément par ID
     */
    public static function get($supplement_id)
    {
        global $wpdb;

        $supplement = $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, p.name as product_name 
             FROM {$wpdb->prefix}restaurant_product_supplements s
             LEFT JOIN {$wpdb->prefix}restaurant_products p ON s.product_id = p.id
             WHERE s.id = %d",
            $supplement_id
        ), ARRAY_A);

        if (!$supplement) {
            return null;
        }

        // Convertir les types
        $supplement['price'] = (float) $supplement['price'];
        $supplement['max_quantity'] = $supplement['max_quantity'] ? (int) $supplement['max_quantity'] : null;
        $supplement['display_order'] = (int) $supplement['display_order'];
        $supplement['is_active'] = (bool) $supplement['is_active'];

        return $supplement;
    }

    /**
     * Mettre à jour un supplément
     */
    public static function update($supplement_id, $data)
    {
        global $wpdb;

        // Vérifier que le supplément existe
        $existing_supplement = self::get($supplement_id);
        if (!$existing_supplement) {
            return new WP_Error('supplement_not_found', __('Supplément introuvable', 'restaurant-booking'));
        }

        // Préparer les données à mettre à jour
        $update_data = array();
        $format = array();

        $updatable_fields = array(
            'name' => '%s',
            'price' => '%f',
            'max_quantity' => '%d',
            'display_order' => '%d',
            'is_active' => '%d'
        );

        foreach ($updatable_fields as $field => $field_format) {
            if (isset($data[$field])) {
                switch ($field) {
                    case 'name':
                        $update_data[$field] = sanitize_text_field($data[$field]);
                        break;
                    case 'price':
                        $update_data[$field] = (float) $data[$field];
                        break;
                    case 'max_quantity':
                        $update_data[$field] = $data[$field] ? (int) $data[$field] : null;
                        break;
                    case 'display_order':
                        $update_data[$field] = (int) $data[$field];
                        break;
                    case 'is_active':
                        $update_data[$field] = (bool) $data[$field];
                        break;
                }
                $format[] = $field_format;
            }
        }

        if (empty($update_data)) {
            return new WP_Error('no_data', __('Aucune donnée à mettre à jour', 'restaurant-booking'));
        }

        $update_data['updated_at'] = current_time('mysql');
        $format[] = '%s';

        // Effectuer la mise à jour
        $result = $wpdb->update(
            $wpdb->prefix . 'restaurant_product_supplements',
            $update_data,
            array('id' => $supplement_id),
            $format,
            array('%d')
        );

        if ($result === false) {
            RestaurantBooking_Logger::error('Erreur lors de la mise à jour du supplément', array(
                'supplement_id' => $supplement_id,
                'data' => $data,
                'error' => $wpdb->last_error
            ));
            return new WP_Error('db_error', __('Erreur lors de la mise à jour', 'restaurant-booking'));
        }

        // Log de la mise à jour
        RestaurantBooking_Logger::info("Supplément mis à jour: {$existing_supplement['name']}", array(
            'supplement_id' => $supplement_id,
            'updated_fields' => array_keys($update_data)
        ));

        return true;
    }

    /**
     * Supprimer un supplément
     */
    public static function delete($supplement_id)
    {
        global $wpdb;

        $supplement = self::get($supplement_id);
        if (!$supplement) {
            return new WP_Error('supplement_not_found', __('Supplément introuvable', 'restaurant-booking'));
        }

        $result = $wpdb->delete(
            $wpdb->prefix . 'restaurant_product_supplements',
            array('id' => $supplement_id),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Erreur lors de la suppression', 'restaurant-booking'));
        }

        RestaurantBooking_Logger::info("Supplément supprimé: {$supplement['name']}", array(
            'supplement_id' => $supplement_id
        ));

        return true;
    }

    /**
     * Obtenir les suppléments d'un produit
     */
    public static function get_by_product($product_id, $active_only = true)
    {
        global $wpdb;

        $where_clause = "WHERE product_id = %d";
        $params = array($product_id);

        if ($active_only) {
            $where_clause .= " AND is_active = 1";
        }

        $supplements = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}restaurant_product_supplements 
             $where_clause 
             ORDER BY display_order ASC, name ASC",
            $params
        ), ARRAY_A);

        // Convertir les types
        foreach ($supplements as &$supplement) {
            $supplement['price'] = (float) $supplement['price'];
            $supplement['max_quantity'] = $supplement['max_quantity'] ? (int) $supplement['max_quantity'] : null;
            $supplement['display_order'] = (int) $supplement['display_order'];
            $supplement['is_active'] = (bool) $supplement['is_active'];
        }

        return $supplements;
    }

    /**
     * Valider la quantité d'un supplément
     */
    public static function validate_supplement_quantity($supplement_id, $requested_quantity, $product_quantity)
    {
        $supplement = self::get($supplement_id);
        
        if (!$supplement) {
            return new WP_Error('supplement_not_found', __('Supplément introuvable', 'restaurant-booking'));
        }

        if (!$supplement['is_active']) {
            return new WP_Error('supplement_inactive', __('Supplément non disponible', 'restaurant-booking'));
        }

        // Vérifier la quantité maximum du supplément
        if ($supplement['max_quantity'] && $requested_quantity > $supplement['max_quantity']) {
            return new WP_Error('supplement_max_exceeded', 
                sprintf(__('Quantité maximum pour %s : %d', 'restaurant-booking'), 
                    $supplement['name'], 
                    $supplement['max_quantity']
                )
            );
        }

        // Vérifier que la quantité du supplément ne dépasse pas celle du produit principal
        if ($requested_quantity > $product_quantity) {
            return new WP_Error('supplement_exceeds_product', 
                sprintf(__('La quantité de %s ne peut pas dépasser celle du produit principal (%d)', 'restaurant-booking'), 
                    $supplement['name'], 
                    $product_quantity
                )
            );
        }

        return true;
    }

    /**
     * Calculer le prix total des suppléments
     */
    public static function calculate_supplements_total($supplements_selection)
    {
        $total = 0;

        foreach ($supplements_selection as $supplement_id => $quantity) {
            if ($quantity <= 0) {
                continue;
            }

            $supplement = self::get($supplement_id);
            if ($supplement && $supplement['is_active']) {
                $total += $supplement['price'] * $quantity;
            }
        }

        return $total;
    }

    /**
     * Obtenir les suppléments avec leurs produits pour l'admin
     */
    public static function get_list_with_products($args = array())
    {
        global $wpdb;

        $defaults = array(
            'product_id' => '',
            'is_active' => '',
            'search' => '',
            'orderby' => 'product_name',
            'order' => 'ASC',
            'limit' => 50,
            'offset' => 0
        );

        $args = wp_parse_args($args, $defaults);

        // Construire la requête
        $where_conditions = array();
        $params = array();

        if (!empty($args['product_id'])) {
            $where_conditions[] = 's.product_id = %d';
            $params[] = $args['product_id'];
        }

        if ($args['is_active'] !== '') {
            $where_conditions[] = 's.is_active = %d';
            $params[] = (int) $args['is_active'];
        }

        if (!empty($args['search'])) {
            $where_conditions[] = '(s.name LIKE %s OR p.name LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }

        // Requête de comptage
        $count_sql = "SELECT COUNT(*) 
                      FROM {$wpdb->prefix}restaurant_product_supplements s
                      LEFT JOIN {$wpdb->prefix}restaurant_products p ON s.product_id = p.id
                      $where_clause";
        if (!empty($params)) {
            $count_sql = $wpdb->prepare($count_sql, $params);
        }
        $total = $wpdb->get_var($count_sql);

        // Requête principale
        $orderby_map = array(
            'product_name' => 'p.name',
            'supplement_name' => 's.name',
            'price' => 's.price',
            'display_order' => 's.display_order'
        );
        
        $orderby_field = isset($orderby_map[$args['orderby']]) ? $orderby_map[$args['orderby']] : 's.display_order';
        $order = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';

        $sql = "SELECT s.*, p.name as product_name, p.price as product_price
                FROM {$wpdb->prefix}restaurant_product_supplements s
                LEFT JOIN {$wpdb->prefix}restaurant_products p ON s.product_id = p.id
                $where_clause 
                ORDER BY $orderby_field $order 
                LIMIT %d OFFSET %d";

        $params[] = $args['limit'];
        $params[] = $args['offset'];

        $supplements = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);

        // Convertir les types
        foreach ($supplements as &$supplement) {
            $supplement['price'] = (float) $supplement['price'];
            $supplement['product_price'] = (float) $supplement['product_price'];
            $supplement['max_quantity'] = $supplement['max_quantity'] ? (int) $supplement['max_quantity'] : null;
            $supplement['display_order'] = (int) $supplement['display_order'];
            $supplement['is_active'] = (bool) $supplement['is_active'];
        }

        return array(
            'supplements' => $supplements,
            'total' => (int) $total,
            'pages' => ceil($total / $args['limit'])
        );
    }

    /**
     * AJAX: Créer un supplément
     */
    public function ajax_create_supplement()
    {
        check_ajax_referer('restaurant_booking_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Permissions insuffisantes', 'restaurant-booking'));
        }

        $data = array(
            'product_id' => (int) $_POST['product_id'],
            'name' => sanitize_text_field($_POST['name']),
            'price' => (float) $_POST['price'],
            'max_quantity' => !empty($_POST['max_quantity']) ? (int) $_POST['max_quantity'] : null,
            'display_order' => (int) $_POST['display_order'],
            'is_active' => isset($_POST['is_active']) ? (bool) $_POST['is_active'] : true
        );

        $result = self::create($data);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(array(
            'supplement_id' => $result,
            'message' => __('Supplément créé avec succès', 'restaurant-booking')
        ));
    }

    /**
     * AJAX: Mettre à jour un supplément
     */
    public function ajax_update_supplement()
    {
        check_ajax_referer('restaurant_booking_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Permissions insuffisantes', 'restaurant-booking'));
        }

        $supplement_id = (int) $_POST['supplement_id'];
        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'price' => (float) $_POST['price'],
            'max_quantity' => !empty($_POST['max_quantity']) ? (int) $_POST['max_quantity'] : null,
            'display_order' => (int) $_POST['display_order'],
            'is_active' => isset($_POST['is_active']) ? (bool) $_POST['is_active'] : true
        );

        $result = self::update($supplement_id, $data);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(array(
            'message' => __('Supplément mis à jour avec succès', 'restaurant-booking')
        ));
    }

    /**
     * AJAX: Supprimer un supplément
     */
    public function ajax_delete_supplement()
    {
        check_ajax_referer('restaurant_booking_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Permissions insuffisantes', 'restaurant-booking'));
        }

        $supplement_id = (int) $_POST['supplement_id'];
        $result = self::delete($supplement_id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(array(
            'message' => __('Supplément supprimé avec succès', 'restaurant-booking')
        ));
    }

    /**
     * AJAX: Obtenir les suppléments d'un produit
     */
    public function ajax_get_product_supplements()
    {
        $product_id = (int) $_POST['product_id'];
        $supplements = self::get_by_product($product_id, true);

        wp_send_json_success($supplements);
    }

    /**
     * AJAX: Valider la quantité d'un supplément
     */
    public function ajax_validate_supplement_quantity()
    {
        $supplement_id = (int) $_POST['supplement_id'];
        $requested_quantity = (int) $_POST['requested_quantity'];
        $product_quantity = (int) $_POST['product_quantity'];

        $result = self::validate_supplement_quantity($supplement_id, $requested_quantity, $product_quantity);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(array(
            'valid' => true,
            'message' => __('Quantité valide', 'restaurant-booking')
        ));
    }
}
