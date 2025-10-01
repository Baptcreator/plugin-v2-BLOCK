<?php
/**
 * Classe de migration pour la version 3 - Support des nouvelles fonctionnalités
 *
 * @package RestaurantBooking
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Migration_V3
{
    /**
     * Exécuter la migration v3
     */
    public static function migrate()
    {
        global $wpdb;
        
        RestaurantBooking_Logger::info('Début de la migration v3');
        
        try {
            self::create_new_tables();
            self::modify_existing_tables();
            self::update_version();
            
            RestaurantBooking_Logger::info('Migration v3 terminée avec succès');
            return true;
            
        } catch (Exception $e) {
            RestaurantBooking_Logger::error('Erreur lors de la migration v3: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Créer les nouvelles tables
     */
    private static function create_new_tables()
    {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table des options d'accompagnements
        $table_accompaniment_options = $wpdb->prefix . 'restaurant_accompaniment_options';
        $sql_accompaniment_options = "CREATE TABLE $table_accompaniment_options (
            id int(11) NOT NULL AUTO_INCREMENT,
            product_id int(11) NOT NULL,
            option_name varchar(255) NOT NULL,
            option_price decimal(10,2) NOT NULL DEFAULT 0.00,
            display_order int(11) NOT NULL DEFAULT 0,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY is_active (is_active),
            KEY display_order (display_order)
        ) $charset_collate;";
        
        // Table des sous-options d'accompagnements
        $table_accompaniment_suboptions = $wpdb->prefix . 'restaurant_accompaniment_suboptions';
        $sql_accompaniment_suboptions = "CREATE TABLE $table_accompaniment_suboptions (
            id int(11) NOT NULL AUTO_INCREMENT,
            option_id int(11) NOT NULL,
            suboption_name varchar(255) NOT NULL,
            display_order int(11) NOT NULL DEFAULT 0,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY option_id (option_id),
            KEY is_active (is_active),
            KEY display_order (display_order)
        ) $charset_collate;";
        
        // Table des tailles de boissons (pour les contenances multiples)
        $table_beverage_sizes = $wpdb->prefix . 'restaurant_beverage_sizes';
        $sql_beverage_sizes = "CREATE TABLE $table_beverage_sizes (
            id int(11) NOT NULL AUTO_INCREMENT,
            product_id int(11) NOT NULL,
            size_cl int(11) NOT NULL,
            size_label varchar(50) NOT NULL,
            price decimal(10,2) NOT NULL DEFAULT 0.00,
            image_id bigint(20) DEFAULT NULL,
            is_featured tinyint(1) NOT NULL DEFAULT 0,
            display_order int(11) NOT NULL DEFAULT 0,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY is_active (is_active),
            KEY display_order (display_order)
        ) $charset_collate;";
        
        // Table des tailles de fûts (pour les contenances multiples)
        $table_keg_sizes = $wpdb->prefix . 'restaurant_keg_sizes';
        $sql_keg_sizes = "CREATE TABLE $table_keg_sizes (
            id int(11) NOT NULL AUTO_INCREMENT,
            product_id int(11) NOT NULL,
            liters int(11) NOT NULL,
            price decimal(10,2) NOT NULL DEFAULT 0.00,
            image_id bigint(20) DEFAULT NULL,
            is_featured tinyint(1) NOT NULL DEFAULT 0,
            display_order int(11) NOT NULL DEFAULT 0,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY is_active (is_active),
            KEY display_order (display_order)
        ) $charset_collate;";
        
        // Table des contenances disponibles (pour les fûts)
        $table_available_containers = $wpdb->prefix . 'restaurant_available_containers';
        $sql_available_containers = "CREATE TABLE $table_available_containers (
            id int(11) NOT NULL AUTO_INCREMENT,
            liters int(11) NOT NULL,
            label varchar(50) NOT NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            display_order int(11) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY liters (liters),
            KEY is_active (is_active),
            KEY display_order (display_order)
        ) $charset_collate;";
        
        // Table des suppléments multiples (pour plats signature et buffets)
        $table_product_supplements_v2 = $wpdb->prefix . 'restaurant_product_supplements_v2';
        $sql_product_supplements_v2 = "CREATE TABLE $table_product_supplements_v2 (
            id int(11) NOT NULL AUTO_INCREMENT,
            product_id int(11) NOT NULL,
            supplement_name varchar(255) NOT NULL,
            supplement_price decimal(10,2) NOT NULL DEFAULT 0.00,
            max_quantity int(11) DEFAULT NULL,
            display_order int(11) NOT NULL DEFAULT 0,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY is_active (is_active),
            KEY display_order (display_order)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Exécuter les créations
        dbDelta($sql_accompaniment_options);
        dbDelta($sql_accompaniment_suboptions);
        dbDelta($sql_beverage_sizes);
        dbDelta($sql_keg_sizes);
        dbDelta($sql_available_containers);
        dbDelta($sql_product_supplements_v2);
        
        // Initialiser les contenances par défaut
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-container-manager.php';
        RestaurantBooking_Container_Manager::init_default_containers();
        
        RestaurantBooking_Logger::info('Nouvelles tables v3 créées');
    }
    
    /**
     * Modifier les tables existantes
     */
    private static function modify_existing_tables()
    {
        global $wpdb;
        
        // Ajouter des colonnes à la table products si elles n'existent pas
        $table_products = $wpdb->prefix . 'restaurant_products';
        
        // Vérifier que image_id existe d'abord
        $has_image_id = $wpdb->get_results("SHOW COLUMNS FROM $table_products LIKE 'image_id'");
        if (empty($has_image_id)) {
            // Ajouter image_id d'abord si elle n'existe pas
            $wpdb->query("ALTER TABLE $table_products ADD COLUMN image_id bigint(20) DEFAULT NULL AFTER supplement_price");
        }
        
        // Vérifier et ajouter la colonne pour plusieurs images
        $has_multiple_images = $wpdb->get_results("SHOW COLUMNS FROM $table_products LIKE 'image_ids'");
        if (empty($has_multiple_images)) {
            $wpdb->query("ALTER TABLE $table_products ADD COLUMN image_ids json DEFAULT NULL AFTER image_id");
        }
        
        // Vérifier et ajouter la colonne pour marquer les produits avec options d'accompagnement
        $has_accompaniment_options = $wpdb->get_results("SHOW COLUMNS FROM $table_products LIKE 'has_accompaniment_options'");
        if (empty($has_accompaniment_options)) {
            $wpdb->query("ALTER TABLE $table_products ADD COLUMN has_accompaniment_options tinyint(1) NOT NULL DEFAULT 0 AFTER has_supplement");
        }
        
        // Vérifier et ajouter la colonne pour marquer les boissons avec tailles multiples
        $has_multiple_sizes = $wpdb->get_results("SHOW COLUMNS FROM $table_products LIKE 'has_multiple_sizes'");
        if (empty($has_multiple_sizes)) {
            $wpdb->query("ALTER TABLE $table_products ADD COLUMN has_multiple_sizes tinyint(1) NOT NULL DEFAULT 0 AFTER volume_cl");
        }
        
        // Vérifier et ajouter la colonne pour marquer les produits avec suppléments multiples
        $has_multiple_supplements = $wpdb->get_results("SHOW COLUMNS FROM $table_products LIKE 'has_multiple_supplements'");
        if (empty($has_multiple_supplements)) {
            $wpdb->query("ALTER TABLE $table_products ADD COLUMN has_multiple_supplements tinyint(1) NOT NULL DEFAULT 0 AFTER has_multiple_sizes");
        }
        
        RestaurantBooking_Logger::info('Tables existantes modifiées pour v3');
    }
    
    /**
     * Mettre à jour la version
     */
    private static function update_version()
    {
        update_option('restaurant_booking_db_version', '3.0.0');
        update_option('restaurant_booking_migration_v3_done', true);
    }
    
    /**
     * Vérifier si la migration v3 est nécessaire
     */
    public static function needs_migration()
    {
        return !get_option('restaurant_booking_migration_v3_done', false);
    }
    
    /**
     * Forcer la migration (pour les tests)
     */
    public static function force_migrate()
    {
        delete_option('restaurant_booking_migration_v3_done');
        return self::migrate();
    }
}
