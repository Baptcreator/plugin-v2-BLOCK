<?php
/**
 * Migration complète pour corriger les échappements multiples d'apostrophes
 * Version sécurisée qui ne casse rien
 * 
 * @package RestaurantBooking
 * @since 3.0.2
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Fix_Escaped_Quotes_Complete_Migration
{
    /**
     * Exécuter la migration complète
     */
    public static function run()
    {
        $migration_key = 'restaurant_booking_fix_escaped_quotes_complete_v1';
        
        // Vérifier si la migration a déjà été exécutée
        if (get_option($migration_key, false)) {
            return;
        }
        
        // Log du début
        if (class_exists('RestaurantBooking_Logger')) {
            RestaurantBooking_Logger::info('Début migration complète correction échappements');
        }
        
        $updated_count = 0;
        
        // 1. Nettoyer les options unifiées (déjà fait par l'ancienne migration)
        $updated_count += self::clean_unified_options();
        
        // 2. Nettoyer les tables de produits
        $updated_count += self::clean_products_table();
        
        // 3. Nettoyer les tables de catégories
        $updated_count += self::clean_categories_table();
        
        // 4. Nettoyer les autres tables avec du texte
        $updated_count += self::clean_other_tables();
        
        // Marquer la migration comme terminée
        update_option($migration_key, true);
        
        // Log de fin
        if (class_exists('RestaurantBooking_Logger')) {
            RestaurantBooking_Logger::info('Migration complète terminée', array(
                'updated_records' => $updated_count
            ));
        }
        
        return $updated_count;
    }
    
    /**
     * Nettoyer les options unifiées
     */
    private static function clean_unified_options()
    {
        $options = get_option('restaurant_booking_unified_options', array());
        
        if (empty($options)) {
            return 0;
        }
        
        $updated = false;
        
        foreach ($options as $key => $value) {
            if (is_string($value)) {
                $cleaned_value = self::clean_escaped_quotes($value);
                if ($cleaned_value !== $value) {
                    $options[$key] = $cleaned_value;
                    $updated = true;
                }
            }
        }
        
        if ($updated) {
            update_option('restaurant_booking_unified_options', $options);
            return 1;
        }
        
        return 0;
    }
    
    /**
     * Nettoyer la table des produits
     */
    private static function clean_products_table()
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'restaurant_products';
        
        // Vérifier que la table existe
        if (!$wpdb->get_var("SHOW TABLES LIKE '$table_name'")) {
            return 0;
        }
        
        $updated_count = 0;
        
        // Colonnes à nettoyer dans la table produits
        $text_columns = array('name', 'description', 'short_description', 'supplement_name', 'unit_per_person', 'beer_category');
        
        foreach ($text_columns as $column) {
            // Vérifier que la colonne existe
            $column_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM information_schema.columns 
                 WHERE table_schema = %s AND table_name = %s AND column_name = %s",
                DB_NAME, $table_name, $column
            ));
            
            if (!$column_exists) {
                continue;
            }
            
            // Récupérer les enregistrements avec des échappements multiples
            $records = $wpdb->get_results($wpdb->prepare(
                "SELECT id, $column FROM $table_name WHERE $column LIKE %s",
                '%\\\\%'
            ));
            
            foreach ($records as $record) {
                $cleaned_value = self::clean_escaped_quotes($record->$column);
                if ($cleaned_value !== $record->$column) {
                    $wpdb->update(
                        $table_name,
                        array($column => $cleaned_value),
                        array('id' => $record->id),
                        array('%s'),
                        array('%d')
                    );
                    $updated_count++;
                }
            }
        }
        
        return $updated_count;
    }
    
    /**
     * Nettoyer la table des catégories
     */
    private static function clean_categories_table()
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'restaurant_categories';
        
        // Vérifier que la table existe
        if (!$wpdb->get_var("SHOW TABLES LIKE '$table_name'")) {
            return 0;
        }
        
        $updated_count = 0;
        
        // Colonnes à nettoyer dans la table catégories
        $text_columns = array('name', 'description');
        
        foreach ($text_columns as $column) {
            // Vérifier que la colonne existe
            $column_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM information_schema.columns 
                 WHERE table_schema = %s AND table_name = %s AND column_name = %s",
                DB_NAME, $table_name, $column
            ));
            
            if (!$column_exists) {
                continue;
            }
            
            // Récupérer les enregistrements avec des échappements multiples
            $records = $wpdb->get_results($wpdb->prepare(
                "SELECT id, $column FROM $table_name WHERE $column LIKE %s",
                '%\\\\%'
            ));
            
            foreach ($records as $record) {
                $cleaned_value = self::clean_escaped_quotes($record->$column);
                if ($cleaned_value !== $record->$column) {
                    $wpdb->update(
                        $table_name,
                        array($column => $cleaned_value),
                        array('id' => $record->id),
                        array('%s'),
                        array('%d')
                    );
                    $updated_count++;
                }
            }
        }
        
        return $updated_count;
    }
    
    /**
     * Nettoyer les autres tables avec du texte
     */
    private static function clean_other_tables()
    {
        global $wpdb;
        
        $updated_count = 0;
        
        // Tables supplémentaires à nettoyer
        $tables_to_clean = array(
            'restaurant_settings' => array('setting_value', 'description'),
            'restaurant_quotes' => array('admin_notes'),
            'restaurant_availability' => array('blocked_reason', 'notes'),
            'restaurant_delivery_zones' => array('zone_name'),
            'restaurant_accompaniment_options' => array('option_name'),
            'restaurant_accompaniment_suboptions' => array('suboption_name'),
            'restaurant_beverage_sizes' => array(),
            'restaurant_keg_sizes' => array(),
            'restaurant_beer_types' => array('name'),
            'restaurant_wine_types' => array('name'),
            'restaurant_games' => array('name', 'description')
        );
        
        // Nettoyer aussi les options WordPress qui contiennent des textes
        $updated_count += self::clean_wordpress_options();
        
        foreach ($tables_to_clean as $table_name => $columns) {
            $full_table_name = $wpdb->prefix . $table_name;
            
            // Vérifier que la table existe
            if (!$wpdb->get_var("SHOW TABLES LIKE '$full_table_name'")) {
                continue;
            }
            
            if (empty($columns)) {
                continue;
            }
            
            foreach ($columns as $column) {
                // Vérifier que la colonne existe
                $column_exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM information_schema.columns 
                     WHERE table_schema = %s AND table_name = %s AND column_name = %s",
                    DB_NAME, $full_table_name, $column
                ));
                
                if (!$column_exists) {
                    continue;
                }
                
                // Récupérer les enregistrements avec des échappements multiples
                $records = $wpdb->get_results($wpdb->prepare(
                    "SELECT id, $column FROM $full_table_name WHERE $column LIKE %s",
                    '%\\\\%'
                ));
                
                foreach ($records as $record) {
                    $cleaned_value = self::clean_escaped_quotes($record->$column);
                    if ($cleaned_value !== $record->$column) {
                        $wpdb->update(
                            $full_table_name,
                            array($column => $cleaned_value),
                            array('id' => $record->id),
                            array('%s'),
                            array('%d')
                        );
                        $updated_count++;
                    }
                }
            }
        }
        
        return $updated_count;
    }
    
    /**
     * Nettoyer les options WordPress qui contiennent des textes
     */
    private static function clean_wordpress_options()
    {
        $updated_count = 0;
        
        // Options WordPress à nettoyer
        $options_to_clean = array(
            'restaurant_booking_pdf_settings',
            'restaurant_booking_general_settings',
            'restaurant_booking_email_settings',
            'restaurant_booking_unified_options'
        );
        
        foreach ($options_to_clean as $option_name) {
            $option_value = get_option($option_name, array());
            
            if (is_array($option_value)) {
                $cleaned_value = self::clean_array($option_value);
                if ($cleaned_value !== $option_value) {
                    update_option($option_name, $cleaned_value);
                    $updated_count++;
                }
            } elseif (is_string($option_value)) {
                $cleaned_value = self::clean_escaped_quotes($option_value);
                if ($cleaned_value !== $option_value) {
                    update_option($option_name, $cleaned_value);
                    $updated_count++;
                }
            }
        }
        
        return $updated_count;
    }
    
    /**
     * Nettoyer un tableau de données
     */
    private static function clean_array($data)
    {
        if (!is_array($data)) {
            return $data;
        }
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = self::clean_escaped_quotes($value);
            } elseif (is_array($value)) {
                $data[$key] = self::clean_array($value);
            }
        }
        
        return $data;
    }
    
    /**
     * Nettoyer les échappements multiples d'apostrophes
     */
    private static function clean_escaped_quotes($text)
    {
        if (!is_string($text)) {
            return $text;
        }
        
        // Remplacer les multiples échappements par une seule apostrophe
        $text = preg_replace('/\\\\+\'/', "'", $text);
        $text = preg_replace('/\\\\+\"/', '"', $text);
        
        return $text;
    }
    
    /**
     * Forcer la ré-exécution de la migration (pour debug)
     */
    public static function reset()
    {
        delete_option('restaurant_booking_fix_escaped_quotes_complete_v1');
        if (class_exists('RestaurantBooking_Logger')) {
            RestaurantBooking_Logger::info('Reset de la migration complète des échappements');
        }
    }
    
    /**
     * Vérifier l'état de la migration
     */
    public static function get_status()
    {
        $migration_key = 'restaurant_booking_fix_escaped_quotes_complete_v1';
        $is_completed = get_option($migration_key, false);
        
        return array(
            'completed' => $is_completed,
            'migration_key' => $migration_key
        );
    }
}
