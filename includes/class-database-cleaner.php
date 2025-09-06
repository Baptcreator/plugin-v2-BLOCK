<?php
/**
 * Classe pour nettoyer la base de données
 *
 * @package RestaurantBooking
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Database_Cleaner
{
    /**
     * Supprimer tous les produits existants
     */
    public static function clean_all_products()
    {
        global $wpdb;
        
        try {
            // Commencer une transaction
            $wpdb->query('START TRANSACTION');
            
            // Supprimer les données liées aux produits ET les catégories dupliquées
            $tables_to_clean = array(
                $wpdb->prefix . 'restaurant_product_supplements_v2', // Nouveaux suppléments
                $wpdb->prefix . 'restaurant_product_supplements',    // Anciens suppléments
                $wpdb->prefix . 'restaurant_accompaniment_suboptions',
                $wpdb->prefix . 'restaurant_accompaniment_options',
                $wpdb->prefix . 'restaurant_beverage_sizes',
                $wpdb->prefix . 'restaurant_products',
                $wpdb->prefix . 'restaurant_categories' // Supprimer TOUTES les catégories pour éliminer doublons
            );
            
            $deleted_counts = array();
            
            foreach ($tables_to_clean as $table) {
                // Vérifier si la table existe
                $table_exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
                    DB_NAME,
                    $table
                ));
                
                if ($table_exists) {
                    $count_before = $wpdb->get_var("SELECT COUNT(*) FROM $table");
                    $result = $wpdb->query("DELETE FROM $table");
                    
                    if ($result !== false) {
                        $deleted_counts[str_replace($wpdb->prefix, '', $table)] = $count_before;
                        RestaurantBooking_Logger::info("Table $table nettoyée: $count_before enregistrements supprimés");
                    } else {
                        throw new Exception("Erreur lors du nettoyage de la table $table: " . $wpdb->last_error);
                    }
                } else {
                    RestaurantBooking_Logger::warning("Table $table n'existe pas, ignorée");
                }
            }
            
            // Valider la transaction
            $wpdb->query('COMMIT');
            
            RestaurantBooking_Logger::info('Nettoyage de la base de données terminé avec succès');
            
            return array(
                'success' => true,
                'deleted_counts' => $deleted_counts,
                'message' => 'Base de données nettoyée avec succès'
            );
            
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            $wpdb->query('ROLLBACK');
            
            RestaurantBooking_Logger::error('Erreur lors du nettoyage: ' . $e->getMessage());
            
            return array(
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Erreur lors du nettoyage de la base de données'
            );
        }
    }
    
    /**
     * Remettre à zéro les compteurs auto-increment
     */
    public static function reset_auto_increment()
    {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'restaurant_products',
            $wpdb->prefix . 'restaurant_product_supplements',
            $wpdb->prefix . 'restaurant_accompaniment_options',
            $wpdb->prefix . 'restaurant_accompaniment_suboptions',
            $wpdb->prefix . 'restaurant_beverage_sizes'
        );
        
        foreach ($tables as $table) {
            $table_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
                DB_NAME,
                $table
            ));
            
            if ($table_exists) {
                $wpdb->query("ALTER TABLE $table AUTO_INCREMENT = 1");
                RestaurantBooking_Logger::info("Auto-increment remis à zéro pour la table $table");
            }
        }
    }
    
    /**
     * Obtenir les statistiques de la base de données
     */
    public static function get_database_stats()
    {
        global $wpdb;
        
        $stats = array();
        
        $tables = array(
            'restaurant_products' => 'Produits',
            'restaurant_product_supplements' => 'Suppléments',
            'restaurant_accompaniment_options' => 'Options d\'accompagnements',
            'restaurant_accompaniment_suboptions' => 'Sous-options d\'accompagnements',
            'restaurant_beverage_sizes' => 'Tailles de boissons',
            'restaurant_categories' => 'Catégories'
        );
        
        foreach ($tables as $table_suffix => $label) {
            $full_table = $wpdb->prefix . $table_suffix;
            
            $table_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
                DB_NAME,
                $full_table
            ));
            
            if ($table_exists) {
                $count = $wpdb->get_var("SELECT COUNT(*) FROM $full_table");
                $stats[$table_suffix] = array(
                    'label' => $label,
                    'count' => (int) $count,
                    'exists' => true
                );
            } else {
                $stats[$table_suffix] = array(
                    'label' => $label,
                    'count' => 0,
                    'exists' => false
                );
            }
        }
        
        return $stats;
    }
    
    /**
     * Vérifier l'intégrité de la base de données
     */
    public static function check_database_integrity()
    {
        global $wpdb;
        
        $issues = array();
        
        // Vérifier les produits sans catégorie
        $orphan_products = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}restaurant_products p
            LEFT JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
            WHERE c.id IS NULL
        ");
        
        if ($orphan_products > 0) {
            $issues[] = "Produits sans catégorie: $orphan_products";
        }
        
        // Vérifier les suppléments sans produit
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}restaurant_product_supplements'")) {
            $orphan_supplements = $wpdb->get_var("
                SELECT COUNT(*) 
                FROM {$wpdb->prefix}restaurant_product_supplements s
                LEFT JOIN {$wpdb->prefix}restaurant_products p ON s.product_id = p.id
                WHERE p.id IS NULL
            ");
            
            if ($orphan_supplements > 0) {
                $issues[] = "Suppléments sans produit: $orphan_supplements";
            }
        }
        
        // Vérifier les options d'accompagnement sans produit
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}restaurant_accompaniment_options'")) {
            $orphan_options = $wpdb->get_var("
                SELECT COUNT(*) 
                FROM {$wpdb->prefix}restaurant_accompaniment_options o
                LEFT JOIN {$wpdb->prefix}restaurant_products p ON o.product_id = p.id
                WHERE p.id IS NULL
            ");
            
            if ($orphan_options > 0) {
                $issues[] = "Options d'accompagnement sans produit: $orphan_options";
            }
        }
        
        // Vérifier les tailles de boisson sans produit
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}restaurant_beverage_sizes'")) {
            $orphan_sizes = $wpdb->get_var("
                SELECT COUNT(*) 
                FROM {$wpdb->prefix}restaurant_beverage_sizes s
                LEFT JOIN {$wpdb->prefix}restaurant_products p ON s.product_id = p.id
                WHERE p.id IS NULL
            ");
            
            if ($orphan_sizes > 0) {
                $issues[] = "Tailles de boisson sans produit: $orphan_sizes";
            }
        }
        
        return $issues;
    }
    
    /**
     * Nettoyer les données orphelines
     */
    public static function clean_orphaned_data()
    {
        global $wpdb;
        
        $cleaned = array();
        
        try {
            $wpdb->query('START TRANSACTION');
            
            // Nettoyer les suppléments orphelins
            if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}restaurant_product_supplements'")) {
                $result = $wpdb->query("
                    DELETE s FROM {$wpdb->prefix}restaurant_product_supplements s
                    LEFT JOIN {$wpdb->prefix}restaurant_products p ON s.product_id = p.id
                    WHERE p.id IS NULL
                ");
                $cleaned['supplements'] = $wpdb->rows_affected;
            }
            
            // Nettoyer les options d'accompagnement orphelines
            if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}restaurant_accompaniment_options'")) {
                $result = $wpdb->query("
                    DELETE o FROM {$wpdb->prefix}restaurant_accompaniment_options o
                    LEFT JOIN {$wpdb->prefix}restaurant_products p ON o.product_id = p.id
                    WHERE p.id IS NULL
                ");
                $cleaned['accompaniment_options'] = $wpdb->rows_affected;
            }
            
            // Nettoyer les sous-options d'accompagnement orphelines
            if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}restaurant_accompaniment_suboptions'")) {
                $result = $wpdb->query("
                    DELETE so FROM {$wpdb->prefix}restaurant_accompaniment_suboptions so
                    LEFT JOIN {$wpdb->prefix}restaurant_accompaniment_options o ON so.option_id = o.id
                    WHERE o.id IS NULL
                ");
                $cleaned['accompaniment_suboptions'] = $wpdb->rows_affected;
            }
            
            // Nettoyer les tailles de boisson orphelines
            if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}restaurant_beverage_sizes'")) {
                $result = $wpdb->query("
                    DELETE s FROM {$wpdb->prefix}restaurant_beverage_sizes s
                    LEFT JOIN {$wpdb->prefix}restaurant_products p ON s.product_id = p.id
                    WHERE p.id IS NULL
                ");
                $cleaned['beverage_sizes'] = $wpdb->rows_affected;
            }
            
            $wpdb->query('COMMIT');
            
            RestaurantBooking_Logger::info('Nettoyage des données orphelines terminé', $cleaned);
            
            return array(
                'success' => true,
                'cleaned' => $cleaned
            );
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            
            RestaurantBooking_Logger::error('Erreur lors du nettoyage des données orphelines: ' . $e->getMessage());
            
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }
}
