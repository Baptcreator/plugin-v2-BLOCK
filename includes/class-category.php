<?php
/**
 * Classe de gestion des catégories
 *
 * @package RestaurantBooking
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Category
{
    /**
     * Instance unique
     */
    private static $instance = null;

    /**
     * Types de catégories disponibles
     */
    const CATEGORY_TYPES = array(
        'plat_signature' => 'Plats Signature',
        'mini_boss' => 'Mini Boss',
        'accompagnement' => 'Accompagnements',
        'buffet_sale' => 'Buffet Salé',
        'buffet_sucre' => 'Buffet Sucré',
        'soft' => 'Boissons Soft',
        'vin_blanc' => 'Vins Blancs',
        'vin_rouge' => 'Vins Rouges',
        'vin_rose' => 'Vins Rosés',
        'cremant' => 'Crémants',
        'biere' => 'Bières',
        'fut' => 'Fûts',
        'option_restaurant' => 'Options Restaurant',
        'option_remorque' => 'Options Remorque'
    );

    /**
     * Types de services
     */
    const SERVICE_TYPES = array(
        'restaurant' => 'Restaurant uniquement',
        'remorque' => 'Remorque uniquement',
        'both' => 'Les deux services'
    );

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
        // Rien pour le moment
    }

    /**
     * Créer une nouvelle catégorie
     */
    public static function create($data)
    {
        global $wpdb;

        // Validation des données obligatoires
        $required_fields = array('name', 'type');
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return new WP_Error('missing_field', sprintf(__('Le champ %s est obligatoire', 'restaurant-booking'), $field));
            }
        }

        // Vérifier que le type est valide
        if (!array_key_exists($data['type'], self::CATEGORY_TYPES)) {
            return new WP_Error('invalid_type', __('Type de catégorie invalide', 'restaurant-booking'));
        }

        // Générer le slug s'il n'est pas fourni
        $slug = isset($data['slug']) && !empty($data['slug']) 
            ? sanitize_title($data['slug'])
            : sanitize_title($data['name']);

        // Vérifier l'unicité du slug
        $existing_slug = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}restaurant_categories WHERE slug = %s",
            $slug
        ));

        if ($existing_slug > 0) {
            $slug = $slug . '-' . time();
        }

        // Préparer les données pour l'insertion
        $category_data = array(
            'name' => sanitize_text_field($data['name']),
            'slug' => $slug,
            'type' => sanitize_text_field($data['type']),
            'service_type' => isset($data['service_type']) ? sanitize_text_field($data['service_type']) : 'both',
            'description' => isset($data['description']) ? wp_kses_post($data['description']) : '',
            'is_required' => isset($data['is_required']) ? (bool) $data['is_required'] : false,
            'min_selection' => isset($data['min_selection']) ? (int) $data['min_selection'] : 0,
            'max_selection' => isset($data['max_selection']) && !empty($data['max_selection']) ? (int) $data['max_selection'] : null,
            'min_per_person' => isset($data['min_per_person']) ? (bool) $data['min_per_person'] : false,
            'display_order' => isset($data['display_order']) ? (int) $data['display_order'] : 0,
            'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
            'created_at' => current_time('mysql')
        );

        // Insérer en base de données
        $result = $wpdb->insert(
            $wpdb->prefix . 'restaurant_categories',
            $category_data,
            array('%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%s')
        );

        if ($result === false) {
            RestaurantBooking_Logger::error('Erreur lors de la création de la catégorie', array(
                'data' => $data,
                'error' => $wpdb->last_error
            ));
            return new WP_Error('db_error', __('Erreur lors de la création de la catégorie', 'restaurant-booking'));
        }

        $category_id = $wpdb->insert_id;

        // Log de la création
        RestaurantBooking_Logger::info("Nouvelle catégorie créée: {$data['name']}", array(
            'category_id' => $category_id,
            'type' => $data['type']
        ));

        return $category_id;
    }

    /**
     * Obtenir une catégorie par ID
     */
    public static function get($category_id)
    {
        global $wpdb;

        $category = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}restaurant_categories WHERE id = %d",
            $category_id
        ), ARRAY_A);

        if (!$category) {
            return null;
        }

        // Convertir les types
        $category['is_required'] = (bool) $category['is_required'];
        $category['min_per_person'] = (bool) $category['min_per_person'];
        $category['is_active'] = (bool) $category['is_active'];
        $category['max_selection'] = $category['max_selection'] ? (int) $category['max_selection'] : null;

        return $category;
    }

    /**
     * Obtenir une catégorie par slug
     */
    public static function get_by_slug($slug)
    {
        global $wpdb;

        $category = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}restaurant_categories WHERE slug = %s",
            $slug
        ), ARRAY_A);

        if (!$category) {
            return null;
        }

        // Convertir les types
        $category['is_required'] = (bool) $category['is_required'];
        $category['min_per_person'] = (bool) $category['min_per_person'];
        $category['is_active'] = (bool) $category['is_active'];
        $category['max_selection'] = $category['max_selection'] ? (int) $category['max_selection'] : null;

        return $category;
    }

    /**
     * Mettre à jour une catégorie
     */
    public static function update($category_id, $data)
    {
        global $wpdb;

        // Vérifier que la catégorie existe
        $existing_category = self::get($category_id);
        if (!$existing_category) {
            return new WP_Error('category_not_found', __('Catégorie introuvable', 'restaurant-booking'));
        }

        // Préparer les données à mettre à jour
        $update_data = array();
        $format = array();

        $updatable_fields = array(
            'name' => '%s',
            'slug' => '%s',
            'type' => '%s',
            'service_type' => '%s',
            'description' => '%s',
            'is_required' => '%d',
            'min_selection' => '%d',
            'max_selection' => '%d',
            'min_per_person' => '%d',
            'display_order' => '%d',
            'is_active' => '%d'
        );

        foreach ($updatable_fields as $field => $field_format) {
            if (isset($data[$field])) {
                switch ($field) {
                    case 'name':
                    case 'slug':
                    case 'type':
                    case 'service_type':
                        $update_data[$field] = sanitize_text_field($data[$field]);
                        break;
                    case 'description':
                        $update_data[$field] = wp_kses_post($data[$field]);
                        break;
                    case 'min_selection':
                    case 'max_selection':
                    case 'display_order':
                        $update_data[$field] = $data[$field] ? (int) $data[$field] : null;
                        break;
                    case 'is_required':
                    case 'min_per_person':
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

        // Vérifier l'unicité du slug si modifié
        if (isset($update_data['slug']) && $update_data['slug'] !== $existing_category['slug']) {
            $existing_slug = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}restaurant_categories WHERE slug = %s AND id != %d",
                $update_data['slug'],
                $category_id
            ));

            if ($existing_slug > 0) {
                return new WP_Error('slug_exists', __('Ce slug existe déjà', 'restaurant-booking'));
            }
        }

        $update_data['updated_at'] = current_time('mysql');
        $format[] = '%s';

        // Effectuer la mise à jour
        $result = $wpdb->update(
            $wpdb->prefix . 'restaurant_categories',
            $update_data,
            array('id' => $category_id),
            $format,
            array('%d')
        );

        if ($result === false) {
            RestaurantBooking_Logger::error('Erreur lors de la mise à jour de la catégorie', array(
                'category_id' => $category_id,
                'data' => $data,
                'error' => $wpdb->last_error
            ));
            return new WP_Error('db_error', __('Erreur lors de la mise à jour', 'restaurant-booking'));
        }

        // Log de la mise à jour
        RestaurantBooking_Logger::info("Catégorie mise à jour: {$existing_category['name']}", array(
            'category_id' => $category_id,
            'updated_fields' => array_keys($update_data)
        ));

        return true;
    }

    /**
     * Supprimer une catégorie
     */
    public static function delete($category_id)
    {
        global $wpdb;

        $category = self::get($category_id);
        if (!$category) {
            return new WP_Error('category_not_found', __('Catégorie introuvable', 'restaurant-booking'));
        }

        // Vérifier s'il y a des produits dans cette catégorie
        $product_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}restaurant_products WHERE category_id = %d",
            $category_id
        ));

        if ($product_count > 0) {
            return new WP_Error('category_has_products', __('Impossible de supprimer une catégorie contenant des produits', 'restaurant-booking'));
        }

        $result = $wpdb->delete(
            $wpdb->prefix . 'restaurant_categories',
            array('id' => $category_id),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Erreur lors de la suppression', 'restaurant-booking'));
        }

        RestaurantBooking_Logger::info("Catégorie supprimée: {$category['name']}", array(
            'category_id' => $category_id
        ));

        return true;
    }

    /**
     * Lister les catégories avec filtres
     */
    public static function get_list($args = array())
    {
        global $wpdb;

        $defaults = array(
            'service_type' => '',
            'type' => '',
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

        if (!empty($args['service_type'])) {
            $where_conditions[] = '(service_type = %s OR service_type = "both")';
            $params[] = $args['service_type'];
        }

        if (!empty($args['type'])) {
            $where_conditions[] = 'type = %s';
            $params[] = $args['type'];
        }

        if ($args['is_active'] !== '') {
            $where_conditions[] = 'is_active = %d';
            $params[] = (int) $args['is_active'];
        }

        if (!empty($args['search'])) {
            $where_conditions[] = '(name LIKE %s OR description LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }

        // Requête de comptage
        $count_sql = "SELECT COUNT(*) FROM {$wpdb->prefix}restaurant_categories $where_clause";
        if (!empty($params)) {
            $count_sql = $wpdb->prepare($count_sql, $params);
        }
        $total = $wpdb->get_var($count_sql);

        // Requête principale
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        $sql = "SELECT * FROM {$wpdb->prefix}restaurant_categories 
                $where_clause 
                ORDER BY $orderby 
                LIMIT %d OFFSET %d";

        $params[] = $args['limit'];
        $params[] = $args['offset'];

        $categories = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);

        // Convertir les types pour chaque catégorie
        foreach ($categories as &$category) {
            $category['is_required'] = (bool) $category['is_required'];
            $category['min_per_person'] = (bool) $category['min_per_person'];
            $category['is_active'] = (bool) $category['is_active'];
            $category['max_selection'] = $category['max_selection'] ? (int) $category['max_selection'] : null;
            
            // Ajouter le nombre de produits
            $category['product_count'] = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}restaurant_products WHERE category_id = %d",
                $category['id']
            ));
        }

        return array(
            'categories' => $categories,
            'total' => (int) $total,
            'pages' => ceil($total / $args['limit'])
        );
    }

    /**
     * Obtenir les catégories actives pour un service
     */
    public static function get_active_for_service($service_type)
    {
        global $wpdb;

        $categories = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}restaurant_categories 
            WHERE (service_type = %s OR service_type = 'both')
            AND is_active = 1
            ORDER BY display_order ASC, name ASC
        ", $service_type), ARRAY_A);

        // Convertir les types et ajouter le nombre de produits
        foreach ($categories as &$category) {
            $category['is_required'] = (bool) $category['is_required'];
            $category['min_per_person'] = (bool) $category['min_per_person'];
            $category['is_active'] = (bool) $category['is_active'];
            $category['max_selection'] = $category['max_selection'] ? (int) $category['max_selection'] : null;
            
            $category['product_count'] = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}restaurant_products WHERE category_id = %d AND is_active = 1",
                $category['id']
            ));
        }

        return $categories;
    }

    /**
     * Obtenir les types de catégories disponibles
     */
    public static function get_available_types()
    {
        return self::CATEGORY_TYPES;
    }

    /**
     * Obtenir les types de services disponibles
     */
    public static function get_service_types()
    {
        return self::SERVICE_TYPES;
    }

    /**
     * Réorganiser l'ordre d'affichage des catégories
     */
    public static function reorder($category_orders)
    {
        global $wpdb;

        $wpdb->query('START TRANSACTION');

        try {
            foreach ($category_orders as $category_id => $order) {
                $result = $wpdb->update(
                    $wpdb->prefix . 'restaurant_categories',
                    array('display_order' => (int) $order, 'updated_at' => current_time('mysql')),
                    array('id' => (int) $category_id),
                    array('%d', '%s'),
                    array('%d')
                );

                if ($result === false) {
                    throw new Exception("Erreur lors de la mise à jour de l'ordre pour la catégorie $category_id");
                }
            }

            $wpdb->query('COMMIT');
            
            RestaurantBooking_Logger::info('Ordre des catégories mis à jour', array(
                'categories' => array_keys($category_orders)
            ));

            return true;
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            
            RestaurantBooking_Logger::error('Erreur lors du réordonnancement: ' . $e->getMessage());
            
            return new WP_Error('reorder_error', $e->getMessage());
        }
    }

    /**
     * Dupliquer une catégorie
     */
    public static function duplicate($category_id)
    {
        $original = self::get($category_id);
        if (!$original) {
            return new WP_Error('category_not_found', __('Catégorie introuvable', 'restaurant-booking'));
        }

        // Préparer les données pour la duplication
        $duplicate_data = $original;
        unset($duplicate_data['id'], $duplicate_data['created_at'], $duplicate_data['updated_at']);

        $duplicate_data['name'] = $original['name'] . ' (Copie)';
        $duplicate_data['slug'] = $original['slug'] . '-copie-' . time();

        return self::create($duplicate_data);
    }

    /**
     * Obtenir les statistiques des catégories
     */
    public static function get_statistics()
    {
        global $wpdb;

        $stats = array();

        // Nombre de catégories par service
        $by_service = $wpdb->get_results("
            SELECT service_type, COUNT(*) as count
            FROM {$wpdb->prefix}restaurant_categories 
            WHERE is_active = 1
            GROUP BY service_type
        ", ARRAY_A);

        $stats['by_service'] = array();
        foreach ($by_service as $service) {
            $stats['by_service'][$service['service_type']] = (int) $service['count'];
        }

        // Nombre de catégories par type
        $by_type = $wpdb->get_results("
            SELECT type, COUNT(*) as count
            FROM {$wpdb->prefix}restaurant_categories 
            WHERE is_active = 1
            GROUP BY type
        ", ARRAY_A);

        $stats['by_type'] = array();
        foreach ($by_type as $type) {
            $stats['by_type'][$type['type']] = (int) $type['count'];
        }

        // Catégories avec le plus de produits
        $top_categories = $wpdb->get_results("
            SELECT c.name, c.type, COUNT(p.id) as product_count
            FROM {$wpdb->prefix}restaurant_categories c
            LEFT JOIN {$wpdb->prefix}restaurant_products p ON c.id = p.category_id AND p.is_active = 1
            WHERE c.is_active = 1
            GROUP BY c.id
            ORDER BY product_count DESC
            LIMIT 5
        ", ARRAY_A);

        $stats['top_categories'] = $top_categories;

        return $stats;
    }

    /**
     * Valider les données d'une catégorie
     */
    public static function validate($data)
    {
        $errors = array();

        // Nom obligatoire
        if (empty($data['name'])) {
            $errors['name'] = __('Le nom est obligatoire', 'restaurant-booking');
        }

        // Type obligatoire et valide
        if (empty($data['type'])) {
            $errors['type'] = __('Le type est obligatoire', 'restaurant-booking');
        } elseif (!array_key_exists($data['type'], self::CATEGORY_TYPES)) {
            $errors['type'] = __('Type de catégorie invalide', 'restaurant-booking');
        }

        // Service type valide
        if (!empty($data['service_type']) && !array_key_exists($data['service_type'], self::SERVICE_TYPES)) {
            $errors['service_type'] = __('Type de service invalide', 'restaurant-booking');
        }

        // Validation des contraintes numériques
        if (isset($data['min_selection']) && $data['min_selection'] < 0) {
            $errors['min_selection'] = __('Le minimum de sélections ne peut pas être négatif', 'restaurant-booking');
        }

        if (isset($data['max_selection']) && $data['max_selection'] < 0) {
            $errors['max_selection'] = __('Le maximum de sélections ne peut pas être négatif', 'restaurant-booking');
        }

        if (isset($data['min_selection']) && isset($data['max_selection']) 
            && $data['max_selection'] > 0 && $data['min_selection'] > $data['max_selection']) {
            $errors['max_selection'] = __('Le maximum doit être supérieur au minimum', 'restaurant-booking');
        }

        return empty($errors) ? true : $errors;
    }
}
