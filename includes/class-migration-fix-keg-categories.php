<?php
/**
 * Migration pour corriger les catégories de bières des fûts
 * 
 * Cette migration s'assure que les fûts ont les bonnes catégories de bières
 * qui correspondent à celles utilisées dans l'étape 5 (boissons)
 * 
 * @package RestaurantBooking
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Migration_Fix_Keg_Categories {
    
    /**
     * Vérifier si la migration est nécessaire
     */
    public static function is_migration_needed() {
        return !get_option('restaurant_booking_migration_fix_keg_categories_completed', false);
    }
    
    /**
     * Exécuter la migration
     */
    public static function migrate() {
        global $wpdb;
        
        try {
            RestaurantBooking_Logger::info('Début migration catégories fûts', array(
                'migration' => 'fix_keg_categories'
            ));
            
            // S'assurer que la colonne beer_category existe
            $column_exists = $wpdb->get_var("SHOW COLUMNS FROM {$wpdb->prefix}restaurant_products LIKE 'beer_category'");
            if (!$column_exists) {
                $wpdb->query("ALTER TABLE {$wpdb->prefix}restaurant_products ADD COLUMN beer_category VARCHAR(50) DEFAULT NULL");
                RestaurantBooking_Logger::info('Colonne beer_category ajoutée à wp_restaurant_products');
            }
            
            // Récupérer les fûts existants de la catégorie 'fut'
            $kegs = $wpdb->get_results("
                SELECT p.id, p.name, c.type
                FROM {$wpdb->prefix}restaurant_products p
                LEFT JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
                WHERE c.type = 'fut' AND p.is_active = 1
            ");
            
            if (!empty($kegs)) {
                foreach ($kegs as $keg) {
                    $beer_category = null;
                    
                    // Assigner les catégories selon le nom du produit
                    $keg_name = strtolower($keg->name);
                    
                    if (strpos($keg_name, 'blonde') !== false) {
                        $beer_category = 'blonde';
                    } elseif (strpos($keg_name, 'blanche') !== false) {
                        $beer_category = 'blanche';
                    } elseif (strpos($keg_name, 'ipa') !== false || strpos($keg_name, 'houblon') !== false) {
                        $beer_category = 'brune'; // IPA classée comme brune pour l'instant
                    } elseif (strpos($keg_name, 'brune') !== false) {
                        $beer_category = 'brune';
                    } elseif (strpos($keg_name, 'ambr') !== false) {
                        $beer_category = 'ambree';
                    } elseif (strpos($keg_name, 'pils') !== false) {
                        $beer_category = 'pils';
                    } else {
                        // Par défaut, assigner 'blonde'
                        $beer_category = 'blonde';
                    }
                    
                    // Mettre à jour le produit
                    $updated = $wpdb->update(
                        $wpdb->prefix . 'restaurant_products',
                        array('beer_category' => $beer_category),
                        array('id' => $keg->id),
                        array('%s'),
                        array('%d')
                    );
                    
                    if ($updated) {
                        RestaurantBooking_Logger::info('Catégorie bière mise à jour', array(
                            'keg_id' => $keg->id,
                            'keg_name' => $keg->name,
                            'beer_category' => $beer_category
                        ));
                    }
                }
            }
            
            // Marquer la migration comme terminée
            update_option('restaurant_booking_migration_fix_keg_categories_completed', true);
            
            RestaurantBooking_Logger::info('Migration catégories fûts terminée avec succès', array(
                'kegs_updated' => count($kegs)
            ));
            
        } catch (Exception $e) {
            RestaurantBooking_Logger::error('Erreur lors de la migration catégories fûts', array(
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ));
            throw $e;
        }
    }
}
