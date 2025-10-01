<?php
/**
 * Migration pour corriger les problèmes de collations de base de données
 *
 * @package RestaurantBooking
 * @since 3.0.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Migration_Fix_Collations
{
    /**
     * Exécuter la migration
     */
    public static function run()
    {
        global $wpdb;
        
        // Log du début de la migration
        if (class_exists('RestaurantBooking_Logger')) {
            RestaurantBooking_Logger::info("Début migration correction collations");
        }
        
        $tables_to_fix = [
            'restaurant_beer_types',
            'restaurant_wine_types',
            'restaurant_products',
            'restaurant_categories'
        ];
        
        $target_collation = 'utf8mb4_unicode_ci';
        
        foreach ($tables_to_fix as $table_name) {
            $full_table_name = $wpdb->prefix . $table_name;
            
            // Vérifier si la table existe
            $table_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM information_schema.tables 
                 WHERE table_schema = %s AND table_name = %s",
                DB_NAME,
                $full_table_name
            ));
            
            if (!$table_exists) {
                continue;
            }
            
            // Obtenir les colonnes de type texte
            $columns = $wpdb->get_results($wpdb->prepare(
                "SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_SET_NAME, COLLATION_NAME
                 FROM information_schema.columns 
                 WHERE table_schema = %s AND table_name = %s 
                 AND DATA_TYPE IN ('varchar', 'text', 'char', 'longtext', 'mediumtext', 'tinytext')
                 AND COLLATION_NAME IS NOT NULL",
                DB_NAME,
                $full_table_name
            ));
            
            foreach ($columns as $column) {
                if ($column->COLLATION_NAME !== $target_collation) {
                    // Construire la requête ALTER TABLE
                    $column_type = strtoupper($column->DATA_TYPE);
                    
                    // Obtenir la taille de la colonne pour VARCHAR
                    if ($column_type === 'VARCHAR') {
                        $column_length = $wpdb->get_var($wpdb->prepare(
                            "SELECT CHARACTER_MAXIMUM_LENGTH 
                             FROM information_schema.columns 
                             WHERE table_schema = %s AND table_name = %s AND column_name = %s",
                            DB_NAME,
                            $full_table_name,
                            $column->COLUMN_NAME
                        ));
                        $column_type .= "({$column_length})";
                    }
                    
                    $sql = "ALTER TABLE `{$full_table_name}` 
                            MODIFY COLUMN `{$column->COLUMN_NAME}` {$column_type} 
                            CHARACTER SET utf8mb4 COLLATE {$target_collation}";
                    
                    $result = $wpdb->query($sql);
                    
                    if ($result === false) {
                        if (class_exists('RestaurantBooking_Logger')) {
                            RestaurantBooking_Logger::error("Erreur correction collation", [
                                'table' => $full_table_name,
                                'column' => $column->COLUMN_NAME,
                                'error' => $wpdb->last_error
                            ]);
                        }
                    } else {
                        if (class_exists('RestaurantBooking_Logger')) {
                            RestaurantBooking_Logger::info("Collation corrigée", [
                                'table' => $full_table_name,
                                'column' => $column->COLUMN_NAME,
                                'old_collation' => $column->COLLATION_NAME,
                                'new_collation' => $target_collation
                            ]);
                        }
                    }
                }
            }
            
            // Corriger la collation par défaut de la table
            $wpdb->query("ALTER TABLE `{$full_table_name}` DEFAULT CHARACTER SET utf8mb4 COLLATE {$target_collation}");
        }
        
        // Marquer la migration comme terminée
        update_option('restaurant_booking_migration_fix_collations', '1');
        
        if (class_exists('RestaurantBooking_Logger')) {
            RestaurantBooking_Logger::info("Migration correction collations terminée avec succès");
        }
        
        return true;
    }
    
    /**
     * Vérifier si la migration est nécessaire
     */
    public static function is_needed()
    {
        return !get_option('restaurant_booking_migration_fix_collations', false);
    }
}
