<?php
/**
 * Migration pour créer la table des sous-catégories
 * 
 * Cette migration crée wp_restaurant_subcategories pour gérer
 * les types de bières, vins, etc. de manière centralisée
 * 
 * @package RestaurantBooking
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Migration_Create_Subcategories {
    
    /**
     * Vérifier si la migration est nécessaire
     */
    public static function is_migration_needed() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'restaurant_subcategories';
        return $wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name;
    }
    
    /**
     * Exécuter la migration
     */
    public static function migrate() {
        global $wpdb;
        
        try {
            RestaurantBooking_Logger::info('Début migration sous-catégories', array(
                'migration' => 'create_subcategories'
            ));
            
            $table_name = $wpdb->prefix . 'restaurant_subcategories';
            
            // Créer la table des sous-catégories
            $sql = "CREATE TABLE $table_name (
                id int(11) NOT NULL AUTO_INCREMENT,
                parent_category_id int(11) NOT NULL,
                subcategory_name varchar(100) NOT NULL,
                subcategory_slug varchar(50) NOT NULL,
                subcategory_key varchar(50) NOT NULL,
                display_order int(11) DEFAULT 0,
                is_active tinyint(1) DEFAULT 1,
                created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY parent_category_id (parent_category_id),
                KEY subcategory_key (subcategory_key),
                UNIQUE KEY unique_parent_key (parent_category_id, subcategory_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            // Insérer les sous-catégories par défaut
            self::insert_default_subcategories();
            
            // Migrer les données existantes
            self::migrate_existing_data();
            
            RestaurantBooking_Logger::info('Migration sous-catégories terminée avec succès');
            
        } catch (Exception $e) {
            RestaurantBooking_Logger::error('Erreur lors de la migration sous-catégories', array(
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ));
            throw $e;
        }
    }
    
    /**
     * Insérer les sous-catégories par défaut
     */
    private static function insert_default_subcategories() {
        global $wpdb;
        
        $categories_table = $wpdb->prefix . 'restaurant_categories';
        $subcategories_table = $wpdb->prefix . 'restaurant_subcategories';
        
        // Récupérer les IDs des catégories principales
        $beer_category_id = $wpdb->get_var("SELECT id FROM $categories_table WHERE type = 'biere_bouteille'");
        $keg_category_id = $wpdb->get_var("SELECT id FROM $categories_table WHERE type = 'fut'");
        $wine_blanc_id = $wpdb->get_var("SELECT id FROM $categories_table WHERE type = 'vin_blanc'");
        $wine_rouge_id = $wpdb->get_var("SELECT id FROM $categories_table WHERE type = 'vin_rouge'");
        
        // Types de bières (partagés entre bouteilles et fûts)
        $beer_types = array(
            array('key' => 'blonde', 'name' => 'Blonde', 'order' => 1),
            array('key' => 'blanche', 'name' => 'Blanche', 'order' => 2),
            array('key' => 'brune', 'name' => 'Brune', 'order' => 3),
            array('key' => 'ipa', 'name' => 'IPA', 'order' => 4),
            array('key' => 'ambree', 'name' => 'Ambrée', 'order' => 5),
            array('key' => 'pils', 'name' => 'Pils', 'order' => 6)
        );
        
        // Insérer pour les bières bouteilles
        if ($beer_category_id) {
            foreach ($beer_types as $type) {
                $wpdb->insert(
                    $subcategories_table,
                    array(
                        'parent_category_id' => $beer_category_id,
                        'subcategory_name' => $type['name'],
                        'subcategory_slug' => sanitize_title($type['name']),
                        'subcategory_key' => $type['key'],
                        'display_order' => $type['order'],
                        'is_active' => 1
                    ),
                    array('%d', '%s', '%s', '%s', '%d', '%d')
                );
            }
        }
        
        // Insérer pour les fûts (même types)
        if ($keg_category_id) {
            foreach ($beer_types as $type) {
                $wpdb->insert(
                    $subcategories_table,
                    array(
                        'parent_category_id' => $keg_category_id,
                        'subcategory_name' => $type['name'],
                        'subcategory_slug' => sanitize_title($type['name']),
                        'subcategory_key' => $type['key'],
                        'display_order' => $type['order'],
                        'is_active' => 1
                    ),
                    array('%d', '%s', '%s', '%s', '%d', '%d')
                );
            }
        }
        
        // Types de vins
        $wine_types = array(
            array('parent_id' => $wine_blanc_id, 'key' => 'sec', 'name' => 'Sec', 'order' => 1),
            array('parent_id' => $wine_blanc_id, 'key' => 'demi_sec', 'name' => 'Demi-sec', 'order' => 2),
            array('parent_id' => $wine_blanc_id, 'key' => 'moelleux', 'name' => 'Moelleux', 'order' => 3),
            array('parent_id' => $wine_rouge_id, 'key' => 'leger', 'name' => 'Léger', 'order' => 1),
            array('parent_id' => $wine_rouge_id, 'key' => 'corsé', 'name' => 'Corsé', 'order' => 2),
            array('parent_id' => $wine_rouge_id, 'key' => 'tannique', 'name' => 'Tannique', 'order' => 3)
        );
        
        foreach ($wine_types as $type) {
            if ($type['parent_id']) {
                $wpdb->insert(
                    $subcategories_table,
                    array(
                        'parent_category_id' => $type['parent_id'],
                        'subcategory_name' => $type['name'],
                        'subcategory_slug' => sanitize_title($type['name']),
                        'subcategory_key' => $type['key'],
                        'display_order' => $type['order'],
                        'is_active' => 1
                    ),
                    array('%d', '%s', '%s', '%s', '%d', '%d')
                );
            }
        }
        
        RestaurantBooking_Logger::info('Sous-catégories par défaut insérées', array(
            'beer_types' => count($beer_types) * 2, // bouteilles + fûts
            'wine_types' => count($wine_types)
        ));
    }
    
    /**
     * Migrer les données existantes depuis beer_category
     */
    private static function migrate_existing_data() {
        global $wpdb;
        
        $products_table = $wpdb->prefix . 'restaurant_products';
        $categories_table = $wpdb->prefix . 'restaurant_categories';
        
        // Récupérer les produits avec beer_category existantes
        $existing_beers = $wpdb->get_results("
            SELECT p.id, p.beer_category, c.id as category_id, c.type
            FROM $products_table p
            LEFT JOIN $categories_table c ON p.category_id = c.id
            WHERE p.beer_category IS NOT NULL 
            AND p.beer_category != ''
            AND c.type IN ('biere_bouteille', 'fut')
        ");
        
        if (!empty($existing_beers)) {
            RestaurantBooking_Logger::info('Migration des beer_category existantes', array(
                'products_count' => count($existing_beers)
            ));
            
            foreach ($existing_beers as $beer) {
                // Les beer_category sont déjà correctes, pas besoin de les modifier
                // Elles correspondent aux subcategory_key de notre nouvelle table
                RestaurantBooking_Logger::debug('Produit avec beer_category trouvé', array(
                    'product_id' => $beer->id,
                    'beer_category' => $beer->beer_category,
                    'category_type' => $beer->type
                ));
            }
        }
    }
}
