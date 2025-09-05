<?php
/**
 * Classe de gestion des produits
 *
 * @package RestaurantBooking
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Product
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
        // Hooks d'initialisation
        add_action('init', array($this, 'init'));
    }

    /**
     * Initialisation
     */
    public function init()
    {
        // Rien pour le moment
    }

    /**
     * Créer un nouveau produit
     */
    public static function create($data)
    {
        global $wpdb;

        // Validation des données obligatoires
        $required_fields = array('category_id', 'name', 'price');
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return new WP_Error('missing_field', sprintf(__('Le champ %s est obligatoire', 'restaurant-booking'), $field));
            }
        }

        // Vérifier que la catégorie existe
        $category_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}restaurant_categories WHERE id = %d AND is_active = 1",
            $data['category_id']
        ));

        if (!$category_exists) {
            return new WP_Error('invalid_category', __('Catégorie invalide', 'restaurant-booking'));
        }

        // Préparer les données pour l'insertion
        $product_data = array(
            'category_id' => (int) $data['category_id'],
            'name' => sanitize_text_field($data['name']),
            'description' => isset($data['description']) ? wp_kses_post($data['description']) : '',
            'short_description' => isset($data['short_description']) ? sanitize_text_field($data['short_description']) : '',
            'price' => (float) $data['price'],
            'unit_type' => isset($data['unit_type']) ? sanitize_text_field($data['unit_type']) : 'piece',
            'unit_label' => isset($data['unit_label']) ? sanitize_text_field($data['unit_label']) : '/pièce',
            'min_quantity' => isset($data['min_quantity']) ? (int) $data['min_quantity'] : 1,
            'max_quantity' => isset($data['max_quantity']) && !empty($data['max_quantity']) ? (int) $data['max_quantity'] : null,
            'has_supplement' => isset($data['has_supplement']) ? (bool) $data['has_supplement'] : false,
            'supplement_name' => isset($data['supplement_name']) ? sanitize_text_field($data['supplement_name']) : null,
            'supplement_price' => isset($data['supplement_price']) ? (float) $data['supplement_price'] : 0.00,
            'image_url' => isset($data['image_url']) ? esc_url_raw($data['image_url']) : null,
            'alcohol_degree' => isset($data['alcohol_degree']) && !empty($data['alcohol_degree']) ? (float) $data['alcohol_degree'] : null,
            'volume_cl' => isset($data['volume_cl']) && !empty($data['volume_cl']) ? (int) $data['volume_cl'] : null,
            'display_order' => isset($data['display_order']) ? (int) $data['display_order'] : 0,
            'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
            'created_at' => current_time('mysql')
        );

        // Insérer en base de données
        $result = $wpdb->insert(
            $wpdb->prefix . 'restaurant_products',
            $product_data,
            array('%d', '%s', '%s', '%s', '%f', '%s', '%s', '%d', '%d', '%d', '%s', '%f', '%s', '%f', '%d', '%d', '%d', '%s')
        );

        if ($result === false) {
            RestaurantBooking_Logger::error('Erreur lors de la création du produit', array(
                'data' => $data,
                'error' => $wpdb->last_error
            ));
            return new WP_Error('db_error', __('Erreur lors de la création du produit', 'restaurant-booking'));
        }

        $product_id = $wpdb->insert_id;

        // Log de la création
        RestaurantBooking_Logger::info("Nouveau produit créé: {$data['name']}", array(
            'product_id' => $product_id,
            'category_id' => $data['category_id']
        ));

        return $product_id;
    }

    /**
     * Obtenir un produit par ID
     */
    public static function get($product_id)
    {
        global $wpdb;

        $product = $wpdb->get_row($wpdb->prepare(
            "SELECT p.*, c.name as category_name, c.type as category_type, c.service_type
             FROM {$wpdb->prefix}restaurant_products p
             LEFT JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
             WHERE p.id = %d",
            $product_id
        ), ARRAY_A);

        if (!$product) {
            return null;
        }

        // Convertir les types
        $product['price'] = (float) $product['price'];
        $product['supplement_price'] = (float) $product['supplement_price'];
        $product['alcohol_degree'] = $product['alcohol_degree'] ? (float) $product['alcohol_degree'] : null;
        $product['volume_cl'] = $product['volume_cl'] ? (int) $product['volume_cl'] : null;
        $product['has_supplement'] = (bool) $product['has_supplement'];
        $product['is_active'] = (bool) $product['is_active'];

        return $product;
    }

    /**
     * Mettre à jour un produit
     */
    public static function update($product_id, $data)
    {
        global $wpdb;

        // Vérifier que le produit existe
        $existing_product = self::get($product_id);
        if (!$existing_product) {
            return new WP_Error('product_not_found', __('Produit introuvable', 'restaurant-booking'));
        }

        // Préparer les données à mettre à jour
        $update_data = array();
        $format = array();

        $updatable_fields = array(
            'category_id' => '%d',
            'name' => '%s',
            'description' => '%s',
            'short_description' => '%s',
            'price' => '%f',
            'unit_type' => '%s',
            'unit_label' => '%s',
            'min_quantity' => '%d',
            'max_quantity' => '%d',
            'has_supplement' => '%d',
            'supplement_name' => '%s',
            'supplement_price' => '%f',
            'image_url' => '%s',
            'alcohol_degree' => '%f',
            'volume_cl' => '%d',
            'display_order' => '%d',
            'is_active' => '%d'
        );

        foreach ($updatable_fields as $field => $field_format) {
            if (isset($data[$field])) {
                switch ($field) {
                    case 'name':
                    case 'unit_type':
                    case 'unit_label':
                    case 'supplement_name':
                        $update_data[$field] = sanitize_text_field($data[$field]);
                        break;
                    case 'description':
                        $update_data[$field] = wp_kses_post($data[$field]);
                        break;
                    case 'short_description':
                        $update_data[$field] = sanitize_text_field($data[$field]);
                        break;
                    case 'image_url':
                        $update_data[$field] = esc_url_raw($data[$field]);
                        break;
                    case 'price':
                    case 'supplement_price':
                    case 'alcohol_degree':
                        $update_data[$field] = (float) $data[$field];
                        break;
                    case 'category_id':
                    case 'min_quantity':
                    case 'max_quantity':
                    case 'volume_cl':
                    case 'display_order':
                        $update_data[$field] = $data[$field] ? (int) $data[$field] : null;
                        break;
                    case 'has_supplement':
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
            $wpdb->prefix . 'restaurant_products',
            $update_data,
            array('id' => $product_id),
            $format,
            array('%d')
        );

        if ($result === false) {
            RestaurantBooking_Logger::error('Erreur lors de la mise à jour du produit', array(
                'product_id' => $product_id,
                'data' => $data,
                'error' => $wpdb->last_error
            ));
            return new WP_Error('db_error', __('Erreur lors de la mise à jour', 'restaurant-booking'));
        }

        // Log de la mise à jour
        RestaurantBooking_Logger::info("Produit mis à jour: {$existing_product['name']}", array(
            'product_id' => $product_id,
            'updated_fields' => array_keys($update_data)
        ));

        return true;
    }

    /**
     * Supprimer un produit
     */
    public static function delete($product_id)
    {
        global $wpdb;

        $product = self::get($product_id);
        if (!$product) {
            return new WP_Error('product_not_found', __('Produit introuvable', 'restaurant-booking'));
        }

        $result = $wpdb->delete(
            $wpdb->prefix . 'restaurant_products',
            array('id' => $product_id),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Erreur lors de la suppression', 'restaurant-booking'));
        }

        RestaurantBooking_Logger::info("Produit supprimé: {$product['name']}", array(
            'product_id' => $product_id
        ));

        return true;
    }

    /**
     * Lister les produits avec filtres
     */
    public static function get_list($args = array())
    {
        global $wpdb;

        $defaults = array(
            'category_id' => '',
            'service_type' => '',
            'is_active' => '',
            'search' => '',
            'orderby' => 'display_order',
            'order' => 'ASC',
            'limit' => 50,
            'offset' => 0
        );

        $args = wp_parse_args($args, $defaults);

        // Construire la requête
        $where_conditions = array();
        $params = array();

        if (!empty($args['category_id'])) {
            $where_conditions[] = 'p.category_id = %d';
            $params[] = $args['category_id'];
        }

        if (!empty($args['service_type'])) {
            $where_conditions[] = '(c.service_type = %s OR c.service_type = "both")';
            $params[] = $args['service_type'];
        }

        if ($args['is_active'] !== '') {
            $where_conditions[] = 'p.is_active = %d';
            $params[] = (int) $args['is_active'];
        }

        if (!empty($args['search'])) {
            $where_conditions[] = '(p.name LIKE %s OR p.description LIKE %s)';
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
                      FROM {$wpdb->prefix}restaurant_products p
                      LEFT JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
                      $where_clause";
        if (!empty($params)) {
            $count_sql = $wpdb->prepare($count_sql, $params);
        }
        $total = $wpdb->get_var($count_sql);

        // Requête principale
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        $sql = "SELECT p.*, c.name as category_name, c.type as category_type, c.service_type
                FROM {$wpdb->prefix}restaurant_products p
                LEFT JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
                $where_clause 
                ORDER BY $orderby 
                LIMIT %d OFFSET %d";

        $params[] = $args['limit'];
        $params[] = $args['offset'];

        $products = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);

        // Convertir les types pour chaque produit
        foreach ($products as &$product) {
            $product['price'] = (float) $product['price'];
            $product['supplement_price'] = (float) $product['supplement_price'];
            $product['alcohol_degree'] = $product['alcohol_degree'] ? (float) $product['alcohol_degree'] : null;
            $product['volume_cl'] = $product['volume_cl'] ? (int) $product['volume_cl'] : null;
            $product['has_supplement'] = (bool) $product['has_supplement'];
            $product['is_active'] = (bool) $product['is_active'];
        }

        return array(
            'products' => $products,
            'total' => (int) $total,
            'pages' => ceil($total / $args['limit'])
        );
    }

    /**
     * Obtenir les produits par catégorie pour un service
     */
    public static function get_by_service_type($service_type)
    {
        global $wpdb;

        $products = $wpdb->get_results($wpdb->prepare("
            SELECT p.*, c.name as category_name, c.type as category_type, c.service_type,
                   c.is_required, c.min_selection, c.max_selection, c.min_per_person
            FROM {$wpdb->prefix}restaurant_products p
            INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
            WHERE (c.service_type = %s OR c.service_type = 'both')
            AND p.is_active = 1 AND c.is_active = 1
            ORDER BY c.display_order ASC, p.display_order ASC, p.name ASC
        ", $service_type), ARRAY_A);

        // Organiser par catégories
        $result = array();
        foreach ($products as $product) {
            $category_type = $product['category_type'];
            
            if (!isset($result[$category_type])) {
                $result[$category_type] = array(
                    'category_info' => array(
                        'name' => $product['category_name'],
                        'type' => $product['category_type'],
                        'is_required' => (bool) $product['is_required'],
                        'min_selection' => (int) $product['min_selection'],
                        'max_selection' => $product['max_selection'] ? (int) $product['max_selection'] : null,
                        'min_per_person' => (bool) $product['min_per_person']
                    ),
                    'products' => array()
                );
            }

            // Convertir les types
            $product['price'] = (float) $product['price'];
            $product['supplement_price'] = (float) $product['supplement_price'];
            $product['alcohol_degree'] = $product['alcohol_degree'] ? (float) $product['alcohol_degree'] : null;
            $product['volume_cl'] = $product['volume_cl'] ? (int) $product['volume_cl'] : null;
            $product['has_supplement'] = (bool) $product['has_supplement'];

            $result[$category_type]['products'][] = $product;
        }

        return $result;
    }

    /**
     * Dupliquer un produit
     */
    public static function duplicate($product_id)
    {
        $original = self::get($product_id);
        if (!$original) {
            return new WP_Error('product_not_found', __('Produit introuvable', 'restaurant-booking'));
        }

        // Préparer les données pour la duplication
        $duplicate_data = $original;
        unset($duplicate_data['id'], $duplicate_data['created_at'], $duplicate_data['updated_at']);
        unset($duplicate_data['category_name'], $duplicate_data['category_type'], $duplicate_data['service_type']);

        $duplicate_data['name'] = $original['name'] . ' (Copie)';

        return self::create($duplicate_data);
    }

    /**
     * Importer des produits depuis un CSV
     */
    public static function import_from_csv($file_path, $category_mapping = array())
    {
        if (!file_exists($file_path) || !is_readable($file_path)) {
            return new WP_Error('file_error', __('Fichier introuvable ou illisible', 'restaurant-booking'));
        }

        $handle = fopen($file_path, 'r');
        if (!$handle) {
            return new WP_Error('file_error', __('Impossible d\'ouvrir le fichier', 'restaurant-booking'));
        }

        $imported = 0;
        $errors = array();
        $line = 0;

        // Lire la première ligne (en-têtes)
        $headers = fgetcsv($handle, 1000, ',');
        if (!$headers) {
            fclose($handle);
            return new WP_Error('file_error', __('Format de fichier invalide', 'restaurant-booking'));
        }

        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            $line++;
            
            if (count($data) !== count($headers)) {
                $errors[] = "Ligne $line: nombre de colonnes incorrect";
                continue;
            }

            $product_data = array_combine($headers, $data);
            
            // Mapper la catégorie si nécessaire
            if (isset($category_mapping[$product_data['category_id']])) {
                $product_data['category_id'] = $category_mapping[$product_data['category_id']];
            }

            $result = self::create($product_data);
            if (is_wp_error($result)) {
                $errors[] = "Ligne $line: " . $result->get_error_message();
            } else {
                $imported++;
            }
        }

        fclose($handle);

        RestaurantBooking_Logger::info("Import CSV terminé", array(
            'imported' => $imported,
            'errors' => count($errors),
            'file' => basename($file_path)
        ));

        return array(
            'imported' => $imported,
            'errors' => $errors,
            'total_lines' => $line
        );
    }

    /**
     * Exporter les produits vers un CSV
     */
    public static function export_to_csv($args = array())
    {
        $products = self::get_list(array_merge($args, array('limit' => 9999)));

        $csv_data = array();
        $csv_data[] = array(
            'id', 'category_id', 'category_name', 'name', 'description', 'short_description',
            'price', 'unit_type', 'unit_label', 'min_quantity', 'max_quantity',
            'has_supplement', 'supplement_name', 'supplement_price',
            'image_url', 'alcohol_degree', 'volume_cl', 'display_order', 'is_active'
        );

        foreach ($products['products'] as $product) {
            $csv_data[] = array(
                $product['id'],
                $product['category_id'],
                $product['category_name'],
                $product['name'],
                $product['description'],
                $product['short_description'],
                $product['price'],
                $product['unit_type'],
                $product['unit_label'],
                $product['min_quantity'],
                $product['max_quantity'],
                $product['has_supplement'] ? 1 : 0,
                $product['supplement_name'],
                $product['supplement_price'],
                $product['image_url'],
                $product['alcohol_degree'],
                $product['volume_cl'],
                $product['display_order'],
                $product['is_active'] ? 1 : 0
            );
        }

        return $csv_data;
    }

    /**
     * Valider les contraintes de sélection pour une catégorie
     */
    public static function validate_category_selection($category_type, $selected_products, $guest_count)
    {
        global $wpdb;

        $category = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}restaurant_categories WHERE type = %s AND is_active = 1",
            $category_type
        ), ARRAY_A);

        if (!$category) {
            return true; // Catégorie inexistante, pas de validation
        }

        $errors = array();
        $total_selected = count($selected_products);

        // Vérifier si la sélection est obligatoire
        if ($category['is_required'] && $total_selected === 0) {
            $errors[] = RestaurantBooking_Settings::get('error_selection_required', 'Sélection obligatoire');
        }

        // Vérifier le minimum de sélections
        if ($category['min_selection'] > 0 && $total_selected < $category['min_selection']) {
            $errors[] = sprintf(__('Minimum %d sélections requises', 'restaurant-booking'), $category['min_selection']);
        }

        // Vérifier le maximum de sélections
        if ($category['max_selection'] && $total_selected > $category['max_selection']) {
            $errors[] = sprintf(__('Maximum %d sélections autorisées', 'restaurant-booking'), $category['max_selection']);
        }

        // Vérifier le minimum par personne
        if ($category['min_per_person'] && $guest_count > 0) {
            $total_quantity = array_sum($selected_products);
            if ($total_quantity < $guest_count) {
                $errors[] = sprintf(__('Minimum 1 par convive requis (%d manquants)', 'restaurant-booking'), $guest_count - $total_quantity);
            }
        }

        return empty($errors) ? true : $errors;
    }
}
