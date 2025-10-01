<?php
/**
 * Migration v4 - Nettoyage des colonnes obsolètes
 *
 * @package RestaurantBooking
 * @since 4.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Migration_V4_Cleanup
{
    /**
     * Version de cette migration
     */
    const VERSION = '4.0.0';
    
    /**
     * Exécuter la migration de nettoyage
     */
    public static function run()
    {
        RestaurantBooking_Logger::info('Début de la migration v4 - Nettoyage des colonnes obsolètes');
        
        try {
            self::migrate_old_accompaniment_data();
            self::remove_obsolete_columns();
            self::update_version();
            
            RestaurantBooking_Logger::info('Migration v4 terminée avec succès');
            return true;
            
        } catch (Exception $e) {
            RestaurantBooking_Logger::error('Erreur lors de la migration v4: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Migrer les anciennes données d'accompagnement vers le nouveau système
     */
    private static function migrate_old_accompaniment_data()
    {
        global $wpdb;
        
        RestaurantBooking_Logger::info('Migration des anciennes données d\'accompagnement...');
        
        // Récupérer tous les accompagnements avec des anciennes données
        $accompaniments = $wpdb->get_results("
            SELECT id, name, sauce_options, has_chimichurri, accompaniment_type
            FROM {$wpdb->prefix}restaurant_products p
            INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
            WHERE c.type = 'accompagnement' 
            AND (p.sauce_options IS NOT NULL OR p.has_chimichurri = 1)
        ");
        
        foreach ($accompaniments as $accompaniment) {
            // Migrer les options de sauce JSON vers les nouvelles tables
            if (!empty($accompaniment->sauce_options)) {
                $sauce_options = json_decode($accompaniment->sauce_options, true);
                if (is_array($sauce_options)) {
                    self::migrate_sauce_options($accompaniment->id, $sauce_options);
                }
            }
            
            // Migrer l'option chimichurri spécifique
            if ($accompaniment->has_chimichurri) {
                self::migrate_chimichurri_option($accompaniment->id);
            }
        }
        
        RestaurantBooking_Logger::info('Migration des données terminée');
    }
    
    /**
     * Migrer les options de sauce JSON vers les nouvelles tables
     */
    private static function migrate_sauce_options($product_id, $sauce_options)
    {
        // Créer une option "Sauces" gratuite avec les sauces comme sous-options
        $option_id = RestaurantBooking_Accompaniment_Option_Manager::create_option(array(
            'product_id' => $product_id,
            'option_name' => 'Sauces',
            'option_price' => 0.00
        ));
        
        if (!is_wp_error($option_id)) {
            foreach ($sauce_options as $sauce) {
                $sauce_name = is_array($sauce) ? $sauce['name'] : $sauce;
                RestaurantBooking_Accompaniment_Option_Manager::create_suboption(array(
                    'option_id' => $option_id,
                    'suboption_name' => $sauce_name
                ));
            }
        }
    }
    
    /**
     * Migrer l'option chimichurri spécifique
     */
    private static function migrate_chimichurri_option($product_id)
    {
        // Récupérer le prix chimichurri depuis les options
        $chimichurri_price = get_option('restaurant_booking_chimichurri_price', 1.00);
        
        RestaurantBooking_Accompaniment_Option_Manager::create_option(array(
            'product_id' => $product_id,
            'option_name' => 'Enrobée sauce chimichurri',
            'option_price' => floatval($chimichurri_price)
        ));
    }
    
    /**
     * Supprimer les colonnes obsolètes
     */
    private static function remove_obsolete_columns()
    {
        global $wpdb;
        
        RestaurantBooking_Logger::info('Suppression des colonnes obsolètes...');
        
        $table_products = $wpdb->prefix . 'restaurant_products';
        
        // Colonnes complètement obsolètes à supprimer
        $columns_to_remove = array(
            'image_ids',                    // Jamais utilisée
            'keg_size_10l_price',          // Remplacée par keg_sizes JSON
            'keg_size_20l_price',          // Remplacée par keg_sizes JSON
            'has_multiple_supplements',     // Jamais utilisée
            'has_chimichurri',             // Remplacée par système d'options
            'sauce_options',               // Remplacée par tables d'options
            'accompaniment_type'           // Redondante avec nouveau système
            // NOTE: wine_category est maintenant utilisée par la nouvelle structure unifiée
        );
        
        foreach ($columns_to_remove as $column) {
            if (self::column_exists($table_products, $column)) {
                $sql = "ALTER TABLE `$table_products` DROP COLUMN `$column`";
                $result = $wpdb->query($sql);
                
                if ($result !== false) {
                    RestaurantBooking_Logger::info("Colonne supprimée: $column");
                } else {
                    RestaurantBooking_Logger::error("Erreur lors de la suppression de la colonne: $column - " . $wpdb->last_error);
                }
            }
        }
    }
    
    /**
     * Vérifier si une colonne existe
     */
    private static function column_exists($table, $column)
    {
        global $wpdb;
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
            DB_NAME, $table, $column
        ));
        
        return $result > 0;
    }
    
    /**
     * Mettre à jour la version de migration
     */
    private static function update_version()
    {
        update_option('restaurant_booking_migration_version', self::VERSION);
        RestaurantBooking_Logger::info('Version de migration mise à jour: ' . self::VERSION);
    }
    
    /**
     * Vérifier si cette migration doit être exécutée
     */
    public static function needs_migration()
    {
        $current_version = get_option('restaurant_booking_migration_version', '1.0.0');
        return version_compare($current_version, self::VERSION, '<');
    }
}
