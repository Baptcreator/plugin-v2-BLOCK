<?php
/**
 * Nettoyage et maintenance du plugin
 *
 * @package RestaurantBooking
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Cleanup_Admin
{
    /**
     * Nettoyer les données obsolètes
     */
    public static function cleanup_obsolete_data()
    {
        global $wpdb;

        try {
            // Supprimer les produits sans catégorie valide
            $wpdb->query("DELETE p FROM {$wpdb->prefix}restaurant_products p 
                         LEFT JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id 
                         WHERE c.id IS NULL");

            // Nettoyer les paramètres obsolètes
            $obsolete_settings = array(
                'old_widget_setting_1',
                'old_widget_setting_2'
            );

            foreach ($obsolete_settings as $setting) {
                delete_option($setting);
            }

            return true;

        } catch (Exception $e) {
            RestaurantBooking_Logger::error('Erreur lors du nettoyage', array(
                'error' => $e->getMessage()
            ));
            return false;
        }
    }

    /**
     * Créer les catégories manquantes par défaut
     */
    public static function create_missing_categories()
    {
        global $wpdb;

        $default_categories = array(
            array(
                'name' => 'Plats Signature',
                'slug' => 'plats-signature',
                'type' => 'plat_signature',
                'service_type' => 'both',
                'description' => 'Nos plats signature DOG et CROQ',
                'is_required' => 1,
                'min_selection' => 1,
                'max_selection' => null,
                'min_per_person' => 1,
                'display_order' => 10
            ),
            array(
                'name' => 'Menu Mini Boss',
                'slug' => 'mini-boss',
                'type' => 'mini_boss',
                'service_type' => 'both',
                'description' => 'Menu enfant',
                'is_required' => 0,
                'min_selection' => 0,
                'max_selection' => null,
                'min_per_person' => 0,
                'display_order' => 20
            ),
            array(
                'name' => 'Accompagnements',
                'slug' => 'accompagnements',
                'type' => 'accompagnement',
                'service_type' => 'both',
                'description' => 'Accompagnements 4€',
                'is_required' => 1,
                'min_selection' => 1,
                'max_selection' => null,
                'min_per_person' => 1,
                'display_order' => 30
            ),
            array(
                'name' => 'Buffet Salé',
                'slug' => 'buffet-sale',
                'type' => 'buffet_sale',
                'service_type' => 'both',
                'description' => 'Buffet salé (min 2 recettes différentes)',
                'is_required' => 0,
                'min_selection' => 2,
                'max_selection' => null,
                'min_per_person' => 1,
                'display_order' => 40
            ),
            array(
                'name' => 'Buffet Sucré',
                'slug' => 'buffet-sucre',
                'type' => 'buffet_sucre',
                'service_type' => 'both',
                'description' => 'Buffet sucré (min 1 recette)',
                'is_required' => 0,
                'min_selection' => 1,
                'max_selection' => null,
                'min_per_person' => 1,
                'display_order' => 50
            ),
            array(
                'name' => 'Softs',
                'slug' => 'softs',
                'type' => 'soft',
                'service_type' => 'both',
                'description' => 'Boissons sans alcool',
                'is_required' => 0,
                'min_selection' => 0,
                'max_selection' => null,
                'min_per_person' => 0,
                'display_order' => 60
            ),
            array(
                'name' => 'Vins Blancs',
                'slug' => 'vins-blancs',
                'type' => 'vin_blanc',
                'service_type' => 'both',
                'description' => 'Sélection de vins blancs',
                'is_required' => 0,
                'min_selection' => 0,
                'max_selection' => null,
                'min_per_person' => 0,
                'display_order' => 70
            ),
            array(
                'name' => 'Vins Rouges',
                'slug' => 'vins-rouges',
                'type' => 'vin_rouge',
                'service_type' => 'both',
                'description' => 'Sélection de vins rouges',
                'is_required' => 0,
                'min_selection' => 0,
                'max_selection' => null,
                'min_per_person' => 0,
                'display_order' => 80
            ),
            array(
                'name' => 'Bières',
                'slug' => 'bieres',
                'type' => 'biere',
                'service_type' => 'both',
                'description' => 'Bières bouteille',
                'is_required' => 0,
                'min_selection' => 0,
                'max_selection' => null,
                'min_per_person' => 0,
                'display_order' => 90
            ),
            array(
                'name' => 'Fûts',
                'slug' => 'futs',
                'type' => 'fut',
                'service_type' => 'restaurant',
                'description' => 'Fûts de bière (restaurant uniquement)',
                'is_required' => 0,
                'min_selection' => 0,
                'max_selection' => null,
                'min_per_person' => 0,
                'display_order' => 100
            )
        );

        foreach ($default_categories as $category) {
            // Vérifier si la catégorie existe déjà
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}restaurant_categories WHERE slug = %s",
                $category['slug']
            ));

            if (!$existing) {
                $wpdb->insert(
                    $wpdb->prefix . 'restaurant_categories',
                    array_merge($category, array(
                        'is_active' => 1,
                        'created_at' => current_time('mysql')
                    )),
                    array('%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%s')
                );
            }
        }
    }

    /**
     * Supprimer les produits de test/exemple
     */
    public static function remove_example_products()
    {
        global $wpdb;

        $example_products = array(
            'Plateau de fromages',
            'Produit test',
            'Exemple'
        );

        foreach ($example_products as $product_name) {
            $wpdb->delete(
                $wpdb->prefix . 'restaurant_products',
                array('name' => $product_name),
                array('%s')
            );
        }
    }
}
