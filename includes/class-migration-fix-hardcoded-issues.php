<?php
/**
 * Migration pour corriger les problèmes identifiés dans l'analyse DB
 * - Prix du Fût Blonde Premium à 0€
 * - Disponibilités toutes indisponibles
 * - Options d'accompagnement manquantes
 *
 * @package RestaurantBooking
 * @since 3.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Migration_Fix_Hardcoded_Issues
{
    /**
     * Version de cette migration
     */
    const MIGRATION_VERSION = '3.1.0';

    /**
     * Exécuter la migration
     */
    public static function migrate()
    {
        global $wpdb;
        
        $current_version = get_option('restaurant_booking_migration_fix_hardcoded_version', '0');
        
        if (version_compare($current_version, self::MIGRATION_VERSION, '>=')) {
            return; // Migration déjà effectuée
        }

        RestaurantBooking_Logger::info('Début de la migration - Correction des problèmes hardcodés', array(
            'from_version' => $current_version,
            'to_version' => self::MIGRATION_VERSION
        ));

        try {
            // 1. Corriger le prix du Fût Blonde Premium
            self::fix_keg_price();
            
            // 2. Activer quelques créneaux de disponibilité
            self::activate_availability_slots();
            
            // 3. Ajouter des options d'accompagnement manquantes
            self::add_missing_accompaniment_options();
            
            // Marquer la migration comme terminée
            update_option('restaurant_booking_migration_fix_hardcoded_version', self::MIGRATION_VERSION);
            
            RestaurantBooking_Logger::info('Migration terminée avec succès', array(
                'version' => self::MIGRATION_VERSION
            ));
            
        } catch (Exception $e) {
            RestaurantBooking_Logger::error('Erreur lors de la migration', array(
                'error' => $e->getMessage(),
                'version' => self::MIGRATION_VERSION
            ));
            throw $e;
        }
    }

    /**
     * Corriger le prix du Fût Blonde Premium (ID: 35)
     */
    private static function fix_keg_price()
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'restaurant_products';
        
        // Vérifier si le produit existe et a un prix à 0
        $product = $wpdb->get_row($wpdb->prepare(
            "SELECT id, name, price FROM {$table_name} WHERE name LIKE %s AND price = 0",
            '%Fût%Blonde%Premium%'
        ));
        
        if ($product) {
            // Mettre à jour le prix à 15€ (prix de base du fût 10L)
            $updated = $wpdb->update(
                $table_name,
                array('price' => 15.00),
                array('id' => $product->id),
                array('%f'),
                array('%d')
            );
            
            if ($updated !== false) {
                RestaurantBooking_Logger::info('Prix du fût corrigé', array(
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'old_price' => 0.00,
                    'new_price' => 15.00
                ));
            } else {
                RestaurantBooking_Logger::warning('Impossible de corriger le prix du fût', array(
                    'product_id' => $product->id,
                    'wpdb_error' => $wpdb->last_error
                ));
            }
        } else {
            RestaurantBooking_Logger::info('Aucun fût avec prix à 0€ trouvé - correction non nécessaire');
        }
    }

    /**
     * Activer quelques créneaux de disponibilité
     */
    private static function activate_availability_slots()
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'restaurant_availability';
        
        // Vérifier combien de créneaux sont disponibles
        $available_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table_name} WHERE is_available = 1"
        );
        
        if ($available_count == 0) {
            // Activer les 2 premiers créneaux
            $updated = $wpdb->query(
                "UPDATE {$table_name} SET is_available = 1 WHERE id IN (
                    SELECT id FROM (
                        SELECT id FROM {$table_name} ORDER BY id LIMIT 2
                    ) AS temp
                )"
            );
            
            if ($updated > 0) {
                RestaurantBooking_Logger::info('Créneaux de disponibilité activés', array(
                    'activated_count' => $updated
                ));
            } else {
                RestaurantBooking_Logger::warning('Impossible d\'activer les créneaux', array(
                    'wpdb_error' => $wpdb->last_error
                ));
            }
        } else {
            RestaurantBooking_Logger::info('Créneaux déjà disponibles', array(
                'available_count' => $available_count
            ));
        }
    }

    /**
     * Ajouter des options d'accompagnement manquantes
     */
    private static function add_missing_accompaniment_options()
    {
        global $wpdb;
        
        $products_table = $wpdb->prefix . 'restaurant_products';
        $options_table = $wpdb->prefix . 'restaurant_accompaniment_options';
        $suboptions_table = $wpdb->prefix . 'restaurant_accompaniment_suboptions';
        
        // Récupérer les produits d'accompagnement sans options
        $products_without_options = $wpdb->get_results("
            SELECT p.id, p.name 
            FROM {$products_table} p
            LEFT JOIN {$options_table} ao ON p.id = ao.product_id
            INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
            WHERE c.type = 'accompagnement' 
            AND p.is_active = 1 
            AND ao.id IS NULL
        ");
        
        foreach ($products_without_options as $product) {
            if (stripos($product->name, 'légumes') !== false || stripos($product->name, 'grillés') !== false) {
                // Options pour Légumes Grillés
                self::add_accompaniment_option($product->id, 'Sauce à l\'ail', 1.00, 1);
                self::add_accompaniment_option($product->id, 'Herbes de Provence', 0.50, 2);
                
                RestaurantBooking_Logger::info('Options ajoutées pour Légumes Grillés', array(
                    'product_id' => $product->id,
                    'product_name' => $product->name
                ));
                
            } elseif (stripos($product->name, 'salade') !== false) {
                // Options pour Salade Verte
                $vinaigrette_id = self::add_accompaniment_option($product->id, 'Vinaigrette maison', 0.50, 1);
                $croutons_id = self::add_accompaniment_option($product->id, 'Croûtons', 1.00, 2);
                
                // Ajouter des sous-options pour la vinaigrette
                if ($vinaigrette_id) {
                    self::add_accompaniment_suboption($vinaigrette_id, 'Vinaigrette classique', 0.00, 1);
                    self::add_accompaniment_suboption($vinaigrette_id, 'Vinaigrette balsamique', 0.50, 2);
                }
                
                RestaurantBooking_Logger::info('Options ajoutées pour Salade Verte', array(
                    'product_id' => $product->id,
                    'product_name' => $product->name
                ));
            }
        }
    }

    /**
     * Ajouter une option d'accompagnement
     */
    private static function add_accompaniment_option($product_id, $option_name, $option_price, $display_order)
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'restaurant_accompaniment_options';
        
        $inserted = $wpdb->insert(
            $table_name,
            array(
                'product_id' => $product_id,
                'option_name' => $option_name,
                'option_price' => $option_price,
                'display_order' => $display_order,
                'is_active' => 1
            ),
            array('%d', '%s', '%f', '%d', '%d')
        );
        
        return $inserted ? $wpdb->insert_id : false;
    }

    /**
     * Ajouter une sous-option d'accompagnement
     */
    private static function add_accompaniment_suboption($option_id, $suboption_name, $option_price, $display_order)
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'restaurant_accompaniment_suboptions';
        
        $inserted = $wpdb->insert(
            $table_name,
            array(
                'option_id' => $option_id,
                'suboption_name' => $suboption_name,
                'option_price' => $option_price,
                'display_order' => $display_order,
                'is_active' => 1
            ),
            array('%d', '%s', '%f', '%d', '%d')
        );
        
        return $inserted ? $wpdb->insert_id : false;
    }

    /**
     * Vérifier si la migration est nécessaire
     */
    public static function is_migration_needed()
    {
        $current_version = get_option('restaurant_booking_migration_fix_hardcoded_version', '0');
        return version_compare($current_version, self::MIGRATION_VERSION, '<');
    }
}
