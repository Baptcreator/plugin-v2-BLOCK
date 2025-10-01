<?php
/**
 * Classe de gestion des boissons et contenances
 *
 * @package RestaurantBooking
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Beverage_Manager
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
     * Obtenir toutes les catégories de boissons
     */
    public static function get_beverage_categories()
    {
        global $wpdb;
        
        // Récupérer tous les types de catégories de boissons
        $types = $wpdb->get_col("
            SELECT DISTINCT c.type 
            FROM {$wpdb->prefix}restaurant_categories c
            WHERE c.type IN ('soft', 'biere_bouteille', 'fut') 
            OR c.type LIKE 'vin_%' 
            OR c.type = 'cremant'
            ORDER BY c.type ASC
        ");
        
        // Fallback vers les types par défaut si aucun résultat
        return $types ?: array('soft', 'vin_blanc', 'vin_rouge', 'vin_rose', 'cremant', 'biere_bouteille', 'fut');
    }

    /**
     * Initialisation
     */
    public function init()
    {
        // Hooks pour l'admin
        if (is_admin()) {
            add_action('wp_ajax_create_beverage_size', array($this, 'ajax_create_beverage_size'));
            add_action('wp_ajax_update_beverage_size', array($this, 'ajax_update_beverage_size'));
            add_action('wp_ajax_delete_beverage_size', array($this, 'ajax_delete_beverage_size'));
            add_action('wp_ajax_get_beverage_sizes', array($this, 'ajax_get_beverage_sizes'));
            add_action('wp_ajax_toggle_product_featured', array($this, 'ajax_toggle_product_featured'));
        }

        // Hooks pour le frontend
        add_action('wp_ajax_get_featured_beverages', array($this, 'ajax_get_featured_beverages'));
        add_action('wp_ajax_nopriv_get_featured_beverages', array($this, 'ajax_get_featured_beverages'));
    }

    /**
     * Créer une nouvelle taille de boisson
     */
    public static function create_beverage_size($data)
    {
        global $wpdb;

        // Validation des données obligatoires
        $required_fields = array('product_id', 'size_cl', 'size_label', 'price');
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || ($field !== 'price' && empty($data[$field]))) {
                return new WP_Error('missing_field', sprintf(__('Le champ %s est obligatoire', 'restaurant-booking'), $field));
            }
        }

        // Vérifier que le produit existe et est une boisson
        $product = $wpdb->get_row($wpdb->prepare(
            "SELECT p.*, c.type FROM {$wpdb->prefix}restaurant_products p
             LEFT JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
             WHERE p.id = %d AND p.is_active = 1",
            $data['product_id']
        ));

        if (!$product) {
            return new WP_Error('invalid_product', __('Produit invalide', 'restaurant-booking'));
        }

        $beverage_categories = self::get_beverage_categories();
        if (!in_array($product->type, $beverage_categories)) {
            return new WP_Error('not_beverage', __('Ce produit n\'est pas une boisson', 'restaurant-booking'));
        }

        // Vérifier que cette taille n'existe pas déjà pour ce produit
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}restaurant_beverage_sizes 
             WHERE product_id = %d AND size_cl = %d",
            $data['product_id'], $data['size_cl']
        ));

        if ($existing > 0) {
            return new WP_Error('size_exists', __('Cette taille existe déjà pour ce produit', 'restaurant-booking'));
        }

        // Préparer les données pour l'insertion
        $size_data = array(
            'product_id' => (int) $data['product_id'],
            'size_cl' => (int) $data['size_cl'],
            'size_label' => sanitize_text_field($data['size_label']),
            'price' => (float) $data['price'],
            'display_order' => isset($data['display_order']) ? (int) $data['display_order'] : 0,
            'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
            'created_at' => current_time('mysql')
        );

        // Insérer en base de données
        $result = $wpdb->insert(
            $wpdb->prefix . 'restaurant_beverage_sizes',
            $size_data,
            array('%d', '%d', '%s', '%f', '%d', '%d', '%s')
        );

        if ($result === false) {
            RestaurantBooking_Logger::error('Erreur lors de la création de la taille de boisson', array(
                'data' => $data,
                'error' => $wpdb->last_error
            ));
            return new WP_Error('db_error', __('Erreur lors de la création de la taille', 'restaurant-booking'));
        }

        $size_id = $wpdb->insert_id;

        // Log de la création
        RestaurantBooking_Logger::info("Nouvelle taille de boisson créée: {$data['size_label']}", array(
            'size_id' => $size_id,
            'product_id' => $data['product_id']
        ));

        return $size_id;
    }

    /**
     * Obtenir une taille de boisson par ID
     */
    public static function get_beverage_size($size_id)
    {
        global $wpdb;

        $size = $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, p.name as product_name 
             FROM {$wpdb->prefix}restaurant_beverage_sizes s
             LEFT JOIN {$wpdb->prefix}restaurant_products p ON s.product_id = p.id
             WHERE s.id = %d",
            $size_id
        ), ARRAY_A);

        if (!$size) {
            return null;
        }

        // Convertir les types
        $size['size_cl'] = (int) $size['size_cl'];
        $size['price'] = (float) $size['price'];
        $size['display_order'] = (int) $size['display_order'];
        $size['is_active'] = (bool) $size['is_active'];

        return $size;
    }

    /**
     * Mettre à jour une taille de boisson
     */
    public static function update_beverage_size($size_id, $data)
    {
        global $wpdb;

        // Vérifier que la taille existe
        $existing_size = self::get_beverage_size($size_id);
        if (!$existing_size) {
            return new WP_Error('size_not_found', __('Taille de boisson introuvable', 'restaurant-booking'));
        }

        // Préparer les données à mettre à jour
        $update_data = array();
        $format = array();

        $updatable_fields = array(
            'size_cl' => '%d',
            'size_label' => '%s',
            'price' => '%f',
            'display_order' => '%d',
            'is_active' => '%d'
        );

        foreach ($updatable_fields as $field => $field_format) {
            if (isset($data[$field])) {
                switch ($field) {
                    case 'size_cl':
                        // Vérifier que cette nouvelle taille n'existe pas déjà
                        if ($data[$field] != $existing_size['size_cl']) {
                            $existing = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$wpdb->prefix}restaurant_beverage_sizes 
                                 WHERE product_id = %d AND size_cl = %d AND id != %d",
                                $existing_size['product_id'], $data[$field], $size_id
                            ));
                            if ($existing > 0) {
                                return new WP_Error('size_exists', __('Cette taille existe déjà pour ce produit', 'restaurant-booking'));
                            }
                        }
                        $update_data[$field] = (int) $data[$field];
                        break;
                    case 'size_label':
                        $update_data[$field] = sanitize_text_field($data[$field]);
                        break;
                    case 'price':
                        $update_data[$field] = (float) $data[$field];
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
            $wpdb->prefix . 'restaurant_beverage_sizes',
            $update_data,
            array('id' => $size_id),
            $format,
            array('%d')
        );

        if ($result === false) {
            RestaurantBooking_Logger::error('Erreur lors de la mise à jour de la taille', array(
                'size_id' => $size_id,
                'data' => $data,
                'error' => $wpdb->last_error
            ));
            return new WP_Error('db_error', __('Erreur lors de la mise à jour', 'restaurant-booking'));
        }

        return true;
    }

    /**
     * Supprimer une taille de boisson
     */
    public static function delete_beverage_size($size_id)
    {
        global $wpdb;

        $size = self::get_beverage_size($size_id);
        if (!$size) {
            return new WP_Error('size_not_found', __('Taille de boisson introuvable', 'restaurant-booking'));
        }

        $result = $wpdb->delete(
            $wpdb->prefix . 'restaurant_beverage_sizes',
            array('id' => $size_id),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Erreur lors de la suppression', 'restaurant-booking'));
        }

        RestaurantBooking_Logger::info("Taille de boisson supprimée: {$size['size_label']}", array(
            'size_id' => $size_id
        ));

        return true;
    }

    /**
     * Obtenir les tailles d'une boisson
     */
    public static function get_beverage_sizes($product_id, $active_only = true)
    {
        global $wpdb;

        $where_clause = "WHERE product_id = %d";
        $params = array($product_id);

        if ($active_only) {
            $where_clause .= " AND is_active = 1";
        }

        $sizes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}restaurant_beverage_sizes 
             $where_clause 
             ORDER BY size_cl ASC",
            $params
        ), ARRAY_A);

        // Convertir les types
        foreach ($sizes as &$size) {
            $size['size_cl'] = (int) $size['size_cl'];
            $size['price'] = (float) $size['price'];
            $size['display_order'] = (int) $size['display_order'];
            $size['is_active'] = (bool) $size['is_active'];
        }

        return $sizes;
    }

    /**
     * Basculer le statut "featured" d'un produit
     */
    public static function toggle_product_featured($product_id)
    {
        global $wpdb;

        $current_status = $wpdb->get_var($wpdb->prepare(
            "SELECT is_featured FROM {$wpdb->prefix}restaurant_products WHERE id = %d",
            $product_id
        ));

        if ($current_status === null) {
            return new WP_Error('product_not_found', __('Produit introuvable', 'restaurant-booking'));
        }

        $new_status = $current_status ? 0 : 1;

        $result = $wpdb->update(
            $wpdb->prefix . 'restaurant_products',
            array('is_featured' => $new_status),
            array('id' => $product_id),
            array('%d'),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Erreur lors du changement de statut', 'restaurant-booking'));
        }

        return $new_status;
    }

    /**
     * Obtenir les boissons en suggestion
     */
    public static function get_featured_beverages($service_type = 'both')
    {
        global $wpdb;

        $where_conditions = array(
            'p.is_active = 1',
            'c.is_active = 1',
            'p.is_featured = 1'
        );

        $params = array();

        // Filtrer par type de service
        if ($service_type !== 'both') {
            $where_conditions[] = '(c.service_type = %s OR c.service_type = "both")';
            $params[] = $service_type;
        }

        // Filtrer les catégories de boissons
        $beverage_categories = self::get_beverage_categories();
        $placeholders = implode(',', array_fill(0, count($beverage_categories), '%s'));
        $where_conditions[] = "c.type IN ($placeholders)";
        $params = array_merge($params, $beverage_categories);

        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

        $beverages = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, c.name as category_name, c.type as category_type
             FROM {$wpdb->prefix}restaurant_products p
             INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
             $where_clause
             ORDER BY c.display_order ASC, p.display_order ASC, p.name ASC",
            $params
        ), ARRAY_A);

        // Ajouter les tailles pour chaque boisson
        foreach ($beverages as &$beverage) {
            $beverage['price'] = (float) $beverage['price'];
            $beverage['is_featured'] = (bool) $beverage['is_featured'];
            $beverage['sizes'] = self::get_beverage_sizes($beverage['id'], true);
        }

        return $beverages;
    }

    /**
     * Obtenir les boissons par catégorie avec tailles
     */
    public static function get_beverages_by_category($category_type, $service_type = 'both')
    {
        global $wpdb;

        $where_conditions = array(
            'p.is_active = 1',
            'c.is_active = 1',
            'c.type = %s'
        );

        $params = array($category_type);

        // Filtrer par type de service
        if ($service_type !== 'both') {
            $where_conditions[] = '(c.service_type = %s OR c.service_type = "both")';
            $params[] = $service_type;
        }

        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

        $beverages = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, c.name as category_name, c.type as category_type
             FROM {$wpdb->prefix}restaurant_products p
             INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
             $where_clause
             ORDER BY p.is_featured DESC, p.display_order ASC, p.name ASC",
            $params
        ), ARRAY_A);

        // Ajouter les tailles pour chaque boisson
        foreach ($beverages as &$beverage) {
            $beverage['price'] = (float) $beverage['price'];
            $beverage['is_featured'] = (bool) $beverage['is_featured'];
            $beverage['sizes'] = self::get_beverage_sizes($beverage['id'], true);
            
            // Pour les fûts, ajouter les prix spéciaux
            if ($category_type === 'fut') {
                $beverage['keg_size_10l_price'] = $beverage['keg_size_10l_price'] ? (float) $beverage['keg_size_10l_price'] : null;
                $beverage['keg_size_20l_price'] = $beverage['keg_size_20l_price'] ? (float) $beverage['keg_size_20l_price'] : null;
            }
        }

        return $beverages;
    }

    /**
     * Calculer le prix d'une sélection de boissons
     */
    public static function calculate_beverages_total($beverages_selection)
    {
        global $wpdb;
        $total = 0;

        foreach ($beverages_selection as $selection) {
            if (!isset($selection['product_id']) || !isset($selection['quantity']) || $selection['quantity'] <= 0) {
                continue;
            }

            $product_id = (int) $selection['product_id'];
            $quantity = (int) $selection['quantity'];

            // Si une taille spécifique est sélectionnée
            if (isset($selection['size_id']) && !empty($selection['size_id'])) {
                $size = self::get_beverage_size($selection['size_id']);
                if ($size && $size['is_active']) {
                    $total += $size['price'] * $quantity;
                }
            }
            // Si c'est un fût avec taille spécifiée
            elseif (isset($selection['keg_size']) && !empty($selection['keg_size'])) {
                $product = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}restaurant_products WHERE id = %d AND is_active = 1",
                    $product_id
                ), ARRAY_A);

                if ($product) {
                    $price_field = 'keg_size_' . $selection['keg_size'] . 'l_price';
                    if (isset($product[$price_field]) && $product[$price_field] > 0) {
                        $total += (float) $product[$price_field] * $quantity;
                    }
                }
            }
            // Prix de base du produit
            else {
                $product = $wpdb->get_row($wpdb->prepare(
                    "SELECT price FROM {$wpdb->prefix}restaurant_products WHERE id = %d AND is_active = 1",
                    $product_id
                ), ARRAY_A);

                if ($product) {
                    $total += (float) $product['price'] * $quantity;
                }
            }
        }

        return $total;
    }

    /**
     * AJAX: Créer une taille de boisson
     */
    public function ajax_create_beverage_size()
    {
        check_ajax_referer('restaurant_booking_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Permissions insuffisantes', 'restaurant-booking'));
        }

        $data = array(
            'product_id' => (int) $_POST['product_id'],
            'size_cl' => (int) $_POST['size_cl'],
            'size_label' => sanitize_text_field($_POST['size_label']),
            'price' => (float) $_POST['price'],
            'display_order' => (int) $_POST['display_order'],
            'is_active' => isset($_POST['is_active']) ? (bool) $_POST['is_active'] : true
        );

        $result = self::create_beverage_size($data);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(array(
            'size_id' => $result,
            'message' => __('Taille créée avec succès', 'restaurant-booking')
        ));
    }

    /**
     * AJAX: Mettre à jour une taille de boisson
     */
    public function ajax_update_beverage_size()
    {
        check_ajax_referer('restaurant_booking_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Permissions insuffisantes', 'restaurant-booking'));
        }

        $size_id = (int) $_POST['size_id'];
        $data = array(
            'size_cl' => (int) $_POST['size_cl'],
            'size_label' => sanitize_text_field($_POST['size_label']),
            'price' => (float) $_POST['price'],
            'display_order' => (int) $_POST['display_order'],
            'is_active' => isset($_POST['is_active']) ? (bool) $_POST['is_active'] : true
        );

        $result = self::update_beverage_size($size_id, $data);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(array(
            'message' => __('Taille mise à jour avec succès', 'restaurant-booking')
        ));
    }

    /**
     * AJAX: Supprimer une taille de boisson
     */
    public function ajax_delete_beverage_size()
    {
        check_ajax_referer('restaurant_booking_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Permissions insuffisantes', 'restaurant-booking'));
        }

        $size_id = (int) $_POST['size_id'];
        $result = self::delete_beverage_size($size_id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(array(
            'message' => __('Taille supprimée avec succès', 'restaurant-booking')
        ));
    }

    /**
     * AJAX: Obtenir les tailles d'une boisson
     */
    public function ajax_get_beverage_sizes()
    {
        $product_id = (int) $_POST['product_id'];
        $sizes = self::get_beverage_sizes($product_id, true);

        wp_send_json_success($sizes);
    }

    /**
     * AJAX: Basculer le statut featured d'un produit
     */
    public function ajax_toggle_product_featured()
    {
        check_ajax_referer('restaurant_booking_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Permissions insuffisantes', 'restaurant-booking'));
        }

        $product_id = (int) $_POST['product_id'];
        $result = self::toggle_product_featured($product_id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(array(
            'new_status' => $result,
            'message' => __('Statut mis à jour', 'restaurant-booking')
        ));
    }

    /**
     * AJAX: Obtenir les boissons en suggestion
     */
    public function ajax_get_featured_beverages()
    {
        $service_type = isset($_POST['service_type']) ? sanitize_text_field($_POST['service_type']) : 'both';
        $beverages = self::get_featured_beverages($service_type);

        wp_send_json_success($beverages);
    }
}
