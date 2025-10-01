<?php

/**
 * Migration pour ajouter la catégorie jeux et quelques jeux de base
 */
class RestaurantBooking_Migration_Add_Games {
    
    /**
     * Vérifier si la migration est nécessaire
     */
    public static function is_migration_needed() {
        return !get_option('restaurant_booking_migration_add_games_completed', false);
    }
    
    /**
     * Exécuter la migration
     */
    public static function migrate() {
        global $wpdb;
        
        try {
            // 1. Créer la catégorie jeux si elle n'existe pas
            $category_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}restaurant_categories WHERE type = %s",
                'jeu'
            ));
            
            if (!$category_id) {
                $wpdb->insert(
                    $wpdb->prefix . 'restaurant_categories',
                    [
                        'name' => 'Jeux et Animations',
                        'slug' => 'jeux-animations',
                        'type' => 'jeu',
                        'service_type' => 'remorque',
                        'is_required' => 0,
                        'min_selection' => 0,
                        'max_selection' => null,
                        'display_order' => 11,
                        'is_active' => 1
                    ],
                    ['%s', '%s', '%s', '%s', '%d', '%d', '%s', '%d', '%d']
                );
                
                $category_id = $wpdb->insert_id;
                error_log("Migration Add Games: Catégorie 'jeu' créée avec ID: {$category_id}");
            }
            
            // 2. Ajouter quelques jeux de base
            $games = [
                [
                    'name' => 'Château Gonflable',
                    'description' => 'Grand château gonflable pour enfants',
                    'price' => 150.00,
                    'unit_type' => 'piece',
                    'unit_label' => '/jour'
                ],
                [
                    'name' => 'Toboggan Géant',
                    'description' => 'Toboggan gonflable géant',
                    'price' => 120.00,
                    'unit_type' => 'piece',
                    'unit_label' => '/jour'
                ],
                [
                    'name' => 'Piscine à Balles',
                    'description' => 'Grande piscine à balles colorées',
                    'price' => 80.00,
                    'unit_type' => 'piece',
                    'unit_label' => '/jour'
                ]
            ];
            
            foreach ($games as $index => $game) {
                // Vérifier si le jeu existe déjà
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}restaurant_products WHERE category_id = %d AND name = %s",
                    $category_id,
                    $game['name']
                ));
                
                if (!$existing) {
                    $result = $wpdb->insert(
                        $wpdb->prefix . 'restaurant_products',
                        [
                            'category_id' => $category_id,
                            'name' => $game['name'],
                            'description' => $game['description'],
                            'price' => $game['price'],
                            'unit_type' => $game['unit_type'],
                            'unit_label' => $game['unit_label'],
                            'min_quantity' => 1,
                            'max_quantity' => 5,
                            'has_supplement' => 0,
                            'display_order' => $index + 1,
                            'is_active' => 1
                        ],
                        ['%d', '%s', '%s', '%f', '%s', '%s', '%d', '%d', '%d', '%d', '%d']
                    );
                    
                    if ($result) {
                        error_log("Migration Add Games: Jeu '{$game['name']}' ajouté avec succès");
                    }
                }
            }
            
            // Marquer la migration comme terminée
            update_option('restaurant_booking_migration_add_games_completed', true);
            
            error_log('Migration Add Games: Migration terminée avec succès');
            return true;
            
        } catch (Exception $e) {
            error_log('Migration Add Games: Erreur - ' . $e->getMessage());
            return false;
        }
    }
}
