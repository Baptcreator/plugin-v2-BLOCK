<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Migration pour restructurer les catégories de vins
 * 
 * OBJECTIF :
 * - Fusionner "Vins Blancs" (ID 107) + "Vins Rouges" (ID 108) en une seule catégorie "Vins"
 * - Créer des sous-catégories de vins (Blanc, Rouge, Chardonnay, Muscat, etc.)
 * - Migrer tous les produits vins vers la nouvelle catégorie unique
 * - Ajouter wine_category aux produits (comme beer_category)
 * 
 * @package RestaurantBooking
 * @since 1.0.0
 */
class RestaurantBooking_Migration_Restructure_Wine_Categories
{
    public static function is_migration_needed()
    {
        return get_option('restaurant_booking_migration_restructure_wine_categories_completed') !== 'true';
    }

    public static function migrate()
    {
        global $wpdb;
        
        RestaurantBooking_Logger::info('Début migration restructuration catégories vins');
        
        try {
            // 1. Ajouter la colonne wine_category aux produits si elle n'existe pas
            $column_exists = $wpdb->get_var("SHOW COLUMNS FROM {$wpdb->prefix}restaurant_products LIKE 'wine_category'");
            if (!$column_exists) {
                $wpdb->query("ALTER TABLE {$wpdb->prefix}restaurant_products ADD COLUMN wine_category VARCHAR(50) DEFAULT NULL");
                RestaurantBooking_Logger::info('Colonne wine_category ajoutée');
            }
            
            // 2. Créer la nouvelle catégorie "Vins" unifiée
            $categories_table = $wpdb->prefix . 'restaurant_categories';
            
            // Vérifier si elle existe déjà
            $wine_category_id = $wpdb->get_var("SELECT id FROM $categories_table WHERE type = 'vin' AND name = 'Vins'");
            
            if (!$wine_category_id) {
                $wpdb->insert($categories_table, [
                    'name' => 'Vins',
                    'slug' => 'vins',
                    'type' => 'vin',
                    'service_type' => 'both',
                    'description' => 'Sélection de vins avec sous-catégories (Blanc, Rouge, Chardonnay, etc.)',
                    'is_required' => 0,
                    'min_selection' => 0,
                    'max_selection' => null,
                    'display_order' => 107, // Entre soft (106) et bières (109)
                    'is_active' => 1,
                ]);
                $wine_category_id = $wpdb->insert_id;
                RestaurantBooking_Logger::info('Nouvelle catégorie Vins créée', ['id' => $wine_category_id]);
            }
            
            // 3. Migrer les produits de "Vins Blancs" (ID 107) vers la nouvelle catégorie
            $white_wine_products = $wpdb->get_results("
                SELECT * FROM {$wpdb->prefix}restaurant_products 
                WHERE category_id = 107 AND is_active = 1
            ");
            
            foreach ($white_wine_products as $product) {
                // Mettre à jour la catégorie et ajouter le type "blanc"
                $wpdb->update(
                    $wpdb->prefix . 'restaurant_products',
                    [
                        'category_id' => $wine_category_id,
                        'wine_category' => 'blanc'
                    ],
                    ['id' => $product->id]
                );
                RestaurantBooking_Logger::info('Produit vin blanc migré', ['product_id' => $product->id, 'name' => $product->name]);
            }
            
            // 4. Migrer les produits de "Vins Rouges" (ID 108) vers la nouvelle catégorie
            $red_wine_products = $wpdb->get_results("
                SELECT * FROM {$wpdb->prefix}restaurant_products 
                WHERE category_id = 108 AND is_active = 1
            ");
            
            foreach ($red_wine_products as $product) {
                // Mettre à jour la catégorie et ajouter le type "rouge"
                $wpdb->update(
                    $wpdb->prefix . 'restaurant_products',
                    [
                        'category_id' => $wine_category_id,
                        'wine_category' => 'rouge'
                    ],
                    ['id' => $product->id]
                );
                RestaurantBooking_Logger::info('Produit vin rouge migré', ['product_id' => $product->id, 'name' => $product->name]);
            }
            
            // 5. Désactiver les anciennes catégories (ne pas supprimer pour éviter les erreurs)
            $wpdb->update(
                $categories_table,
                ['is_active' => 0, 'name' => 'Vins Blancs (Ancien)', 'display_order' => 9999],
                ['id' => 107]
            );
            
            $wpdb->update(
                $categories_table,
                ['is_active' => 0, 'name' => 'Vins Rouges (Ancien)', 'display_order' => 9998],
                ['id' => 108]
            );
            
            RestaurantBooking_Logger::info('Anciennes catégories vins désactivées');
            
            // 6. Créer les sous-catégories de vins dans wp_restaurant_subcategories
            $subcategories_table = $wpdb->prefix . 'restaurant_subcategories';
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$subcategories_table'") == $subcategories_table;
            
            if ($table_exists) {
                $wine_subcategories = [
                    // Types par couleur
                    ['subcategory_key' => 'blanc', 'subcategory_name' => 'Vin Blanc', 'display_order' => 10],
                    ['subcategory_key' => 'rouge', 'subcategory_name' => 'Vin Rouge', 'display_order' => 20],
                    ['subcategory_key' => 'rose', 'subcategory_name' => 'Vin Rosé', 'display_order' => 30],
                    ['subcategory_key' => 'champagne', 'subcategory_name' => 'Champagne', 'display_order' => 40],
                    // Types par cépage (exemples)
                    ['subcategory_key' => 'chardonnay', 'subcategory_name' => 'Chardonnay', 'display_order' => 50],
                    ['subcategory_key' => 'muscat', 'subcategory_name' => 'Muscat', 'display_order' => 60],
                    ['subcategory_key' => 'pinot-noir', 'subcategory_name' => 'Pinot Noir', 'display_order' => 70],
                    ['subcategory_key' => 'merlot', 'subcategory_name' => 'Merlot', 'display_order' => 80],
                ];
                
                foreach ($wine_subcategories as $subcat) {
                    $existing = $wpdb->get_var($wpdb->prepare(
                        "SELECT id FROM $subcategories_table WHERE parent_category_id = %d AND subcategory_key = %s",
                        $wine_category_id,
                        $subcat['subcategory_key']
                    ));
                    
                    if (!$existing) {
                        $wpdb->insert($subcategories_table, array_merge($subcat, [
                            'parent_category_id' => $wine_category_id,
                            'subcategory_slug' => $subcat['subcategory_key'],
                            'is_active' => 1
                        ]));
                    }
                }
                
                RestaurantBooking_Logger::info('Sous-catégories de vins créées');
            }
            
            // 7. Marquer la migration comme terminée
            update_option('restaurant_booking_migration_restructure_wine_categories_completed', 'true');
            
            RestaurantBooking_Logger::info('Migration restructuration catégories vins terminée avec succès', [
                'white_products_migrated' => count($white_wine_products),
                'red_products_migrated' => count($red_wine_products),
                'new_wine_category_id' => $wine_category_id
            ]);
            
        } catch (Exception $e) {
            RestaurantBooking_Logger::error('Erreur lors de la migration restructuration vins', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
