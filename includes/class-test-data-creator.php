<?php
/**
 * Classe pour créer des données de test avec les nouvelles fonctionnalités
 *
 * @package RestaurantBooking
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Test_Data_Creator
{
    /**
     * Créer des produits de test avec les nouvelles fonctionnalités
     */
    public static function create_test_products()
    {
        global $wpdb;
        
        try {
            $wpdb->query('START TRANSACTION');
            
            $results = array(
                'products_created' => 0,
                'options_created' => 0,
                'suboptions_created' => 0,
                'sizes_created' => 0,
                'supplements_created' => 0,
                'errors' => array()
            );
            
            // 1. Créer un plat signature DOG avec suppléments multiples
            $dog_result = self::create_test_plat_signature_dog();
            if (is_wp_error($dog_result)) {
                $results['errors'][] = 'Plat DOG: ' . $dog_result->get_error_message();
            } else {
                $results['products_created']++;
                $results['supplements_created'] = isset($dog_result['supplements_created']) ? $dog_result['supplements_created'] : 0;
            }
            
            // 2. Créer un plat signature CROQ avec suppléments multiples
            $croq_result = self::create_test_plat_signature_croq();
            if (is_wp_error($croq_result)) {
                $results['errors'][] = 'Plat CROQ: ' . $croq_result->get_error_message();
            } else {
                $results['products_created']++;
                $results['supplements_created'] += isset($croq_result['supplements_created']) ? $croq_result['supplements_created'] : 0;
            }
            
            // 3. Créer un menu enfant
            $menu_enfant_result = self::create_test_menu_enfant();
            if (is_wp_error($menu_enfant_result)) {
                $results['errors'][] = 'Menu Enfant: ' . $menu_enfant_result->get_error_message();
            } else {
                $results['products_created']++;
            }
            
            // 4. Créer un accompagnement avec options complexes
            $accompaniment_result = self::create_test_accompaniment();
            if (is_wp_error($accompaniment_result)) {
                $results['errors'][] = 'Accompagnement: ' . $accompaniment_result->get_error_message();
            } else {
                $results['products_created']++;
                $results['options_created'] += $accompaniment_result['options_created'];
                $results['suboptions_created'] += $accompaniment_result['suboptions_created'];
            }
            
            // 5. Créer un plat de buffet salé avec suppléments
            $buffet_sale_result = self::create_test_buffet_sale();
            if (is_wp_error($buffet_sale_result)) {
                $results['errors'][] = 'Buffet Salé: ' . $buffet_sale_result->get_error_message();
            } else {
                $results['products_created']++;
                $results['supplements_created'] += isset($buffet_sale_result['supplements_created']) ? $buffet_sale_result['supplements_created'] : 0;
            }
            
            // 6. Créer un dessert de buffet avec suppléments
            $buffet_sucre_result = self::create_test_buffet_sucre();
            if (is_wp_error($buffet_sucre_result)) {
                $results['errors'][] = 'Buffet Sucré: ' . $buffet_sucre_result->get_error_message();
            } else {
                $results['products_created']++;
                $results['supplements_created'] += isset($buffet_sucre_result['supplements_created']) ? $buffet_sucre_result['supplements_created'] : 0;
            }
            
            // 7. Créer une boisson avec tailles multiples
            $beverage_result = self::create_test_beverage();
            if (is_wp_error($beverage_result)) {
                $results['errors'][] = 'Boisson: ' . $beverage_result->get_error_message();
            } else {
                $results['products_created']++;
                $results['sizes_created'] += $beverage_result['sizes_created'];
            }
            
            // 8. Créer un vin
            $vin_result = self::create_test_vin();
            if (is_wp_error($vin_result)) {
                $results['errors'][] = 'Vin: ' . $vin_result->get_error_message();
            } else {
                $results['products_created']++;
            }
            
            $wpdb->query('COMMIT');
            
            RestaurantBooking_Logger::info('Produits de test créés avec succès', $results);
            
            $results['success'] = true;
            return $results;
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            
            RestaurantBooking_Logger::error('Erreur lors de la création des produits de test: ' . $e->getMessage());
            
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }
    
    /**
     * Créer un plat signature DOG de test
     */
    private static function create_test_plat_signature_dog()
    {
        // Obtenir la catégorie plat signature DOG
        $category = RestaurantBooking_Category::get_by_type('plat_signature_dog');
        if (!$category) {
            return new WP_Error('no_category', 'Catégorie plat signature DOG non trouvée');
        }
        
        // Créer le produit
        $product_data = array(
            'category_id' => $category['id'],
            'name' => 'Hot-Dog Signature Classic (Test)',
            'description' => 'Saucisse de Francfort, moutarde à l\'ancienne, cornichons, pain brioché maison',
            'price' => 8.50,
            'unit_type' => 'piece',
            'unit_label' => '/pièce',
            'has_multiple_supplements' => 1,
            'is_active' => 1,
            'display_order' => 1
        );
        
        $product_id = RestaurantBooking_Product::create($product_data);
        if (!$product_id) {
            return new WP_Error('product_creation_failed', 'Échec de la création du plat signature DOG');
        }
        
        // Ajouter des suppléments multiples
        $supplement_manager = RestaurantBooking_Product_Supplement_Manager::get_instance();
        $supplements_created = 0;
        
        $supplements = array(
            array('name' => 'Frites maison', 'price' => 2.50, 'max_quantity' => null),
            array('name' => 'Gratiné fromage', 'price' => 1.50, 'max_quantity' => null),
            array('name' => 'Bacon croustillant', 'price' => 2.00, 'max_quantity' => null)
        );
        
        foreach ($supplements as $index => $supplement) {
            $supplement_id = $supplement_manager->create_supplement(
                $product_id,
                $supplement['name'],
                $supplement['price'],
                $supplement['max_quantity'],
                $index
            );
            
            if (!is_wp_error($supplement_id)) {
                $supplements_created++;
            }
        }
        
        return array(
            'product_id' => $product_id,
            'supplements_created' => $supplements_created
        );
    }
    
    /**
     * Créer un plat signature CROQ de test
     */
    private static function create_test_plat_signature_croq()
    {
        // Obtenir la catégorie plat signature CROQ
        $category = RestaurantBooking_Category::get_by_type('plat_signature_croq');
        if (!$category) {
            return new WP_Error('no_category', 'Catégorie plat signature CROQ non trouvée');
        }
        
        // Créer le produit
        $product_data = array(
            'category_id' => $category['id'],
            'name' => 'Croque-Monsieur Signature (Test)',
            'description' => 'Pain de mie, jambon de Bayonne, béchamel maison, gruyère AOP',
            'price' => 9.50,
            'unit_type' => 'piece',
            'unit_label' => '/pièce',
            'has_multiple_supplements' => 1,
            'is_active' => 1,
            'display_order' => 1
        );
        
        $product_id = RestaurantBooking_Product::create($product_data);
        if (!$product_id) {
            return new WP_Error('product_creation_failed', 'Échec de la création du plat signature CROQ');
        }
        
        // Ajouter des suppléments multiples
        $supplement_manager = RestaurantBooking_Product_Supplement_Manager::get_instance();
        $supplements_created = 0;
        
        $supplements = array(
            array('name' => 'Salade verte', 'price' => 2.00, 'max_quantity' => null),
            array('name' => 'Œuf à cheval', 'price' => 1.00, 'max_quantity' => null),
            array('name' => 'Tomates cerises', 'price' => 1.50, 'max_quantity' => null)
        );
        
        foreach ($supplements as $index => $supplement) {
            $supplement_id = $supplement_manager->create_supplement(
                $product_id,
                $supplement['name'],
                $supplement['price'],
                $supplement['max_quantity'],
                $index
            );
            
            if (!is_wp_error($supplement_id)) {
                $supplements_created++;
            }
        }
        
        return array(
            'product_id' => $product_id,
            'supplements_created' => $supplements_created
        );
    }
    
    /**
     * Créer un menu enfant de test
     */
    private static function create_test_menu_enfant()
    {
        // Obtenir la catégorie menu enfant
        $category = RestaurantBooking_Category::get_by_type('mini_boss');
        if (!$category) {
            return new WP_Error('no_category', 'Catégorie menu enfant non trouvée');
        }
        
        // Créer le produit
        $product_data = array(
            'category_id' => $category['id'],
            'name' => 'Menu Mini Boss Classic (Test)',
            'description' => 'Mini burger, frites, boisson, dessert surprise',
            'price' => 7.50,
            'unit_type' => 'piece',
            'unit_label' => '/menu',
            'is_active' => 1,
            'display_order' => 1
        );
        
        $product_id = RestaurantBooking_Product::create($product_data);
        if (!$product_id) {
            return new WP_Error('product_creation_failed', 'Échec de la création du menu enfant');
        }
        
        return array('product_id' => $product_id);
    }
    
    /**
     * Créer un plat de buffet salé de test
     */
    private static function create_test_buffet_sale()
    {
        // Obtenir la catégorie buffet salé
        $category = RestaurantBooking_Category::get_by_type('buffet_sale');
        if (!$category) {
            return new WP_Error('no_category', 'Catégorie buffet salé non trouvée');
        }
        
        // Créer le produit
        $product_data = array(
            'category_id' => $category['id'],
            'name' => 'Quiche Lorraine Maison (Test)',
            'description' => 'Pâte brisée, lardons fumés, œufs fermiers, crème fraîche',
            'price' => 6.50,
            'unit_type' => 'piece',
            'unit_label' => '/part',
            'has_multiple_supplements' => 1,
            'is_active' => 1,
            'display_order' => 1
        );
        
        $product_id = RestaurantBooking_Product::create($product_data);
        if (!$product_id) {
            return new WP_Error('product_creation_failed', 'Échec de la création du buffet salé');
        }
        
        // Ajouter des suppléments multiples
        $supplement_manager = RestaurantBooking_Product_Supplement_Manager::get_instance();
        $supplements_created = 0;
        
        $supplements = array(
            array('name' => 'Salade composée', 'price' => 2.50, 'max_quantity' => null),
            array('name' => 'Pain artisanal', 'price' => 1.00, 'max_quantity' => null)
        );
        
        foreach ($supplements as $index => $supplement) {
            $supplement_id = $supplement_manager->create_supplement(
                $product_id,
                $supplement['name'],
                $supplement['price'],
                $supplement['max_quantity'],
                $index
            );
            
            if (!is_wp_error($supplement_id)) {
                $supplements_created++;
            }
        }
        
        return array(
            'product_id' => $product_id,
            'supplements_created' => $supplements_created
        );
    }
    
    /**
     * Créer un dessert de buffet de test
     */
    private static function create_test_buffet_sucre()
    {
        // Obtenir la catégorie buffet sucré
        $category = RestaurantBooking_Category::get_by_type('buffet_sucre');
        if (!$category) {
            return new WP_Error('no_category', 'Catégorie buffet sucré non trouvée');
        }
        
        // Créer le produit
        $product_data = array(
            'category_id' => $category['id'],
            'name' => 'Tarte Tatin aux Pommes (Test)',
            'description' => 'Pommes caramélisées, pâte feuilletée, crème fraîche',
            'price' => 4.50,
            'unit_type' => 'piece',
            'unit_label' => '/part',
            'has_multiple_supplements' => 1,
            'is_active' => 1,
            'display_order' => 1
        );
        
        $product_id = RestaurantBooking_Product::create($product_data);
        if (!$product_id) {
            return new WP_Error('product_creation_failed', 'Échec de la création du buffet sucré');
        }
        
        // Ajouter des suppléments multiples
        $supplement_manager = RestaurantBooking_Product_Supplement_Manager::get_instance();
        $supplements_created = 0;
        
        $supplements = array(
            array('name' => 'Boule de glace vanille', 'price' => 2.00, 'max_quantity' => null),
            array('name' => 'Chantilly maison', 'price' => 1.00, 'max_quantity' => null)
        );
        
        foreach ($supplements as $index => $supplement) {
            $supplement_id = $supplement_manager->create_supplement(
                $product_id,
                $supplement['name'],
                $supplement['price'],
                $supplement['max_quantity'],
                $index
            );
            
            if (!is_wp_error($supplement_id)) {
                $supplements_created++;
            }
        }
        
        return array(
            'product_id' => $product_id,
            'supplements_created' => $supplements_created
        );
    }
    
    /**
     * Créer un vin de test
     */
    private static function create_test_vin()
    {
        // Obtenir la catégorie vin rouge
        $category = RestaurantBooking_Category::get_by_type('vin_rouge');
        if (!$category) {
            return new WP_Error('no_category', 'Catégorie vin rouge non trouvée');
        }
        
        // Créer le produit
        $product_data = array(
            'category_id' => $category['id'],
            'name' => 'Côtes du Rhône Rouge (Test)',
            'description' => 'Vin rouge fruité, 75cl, 13.5°',
            'price' => 18.50,
            'unit_type' => 'bouteille',
            'unit_label' => '/bouteille',
            'is_active' => 1,
            'display_order' => 1
        );
        
        $product_id = RestaurantBooking_Product::create($product_data);
        if (!$product_id) {
            return new WP_Error('product_creation_failed', 'Échec de la création du vin');
        }
        
        return array('product_id' => $product_id);
    }
    
    /**
     * Créer un accompagnement de test avec options
     */
    private static function create_test_accompaniment()
    {
        // Obtenir la catégorie accompagnement
        $category = RestaurantBooking_Category::get_by_type('accompagnement');
        if (!$category) {
            return new WP_Error('no_category', 'Catégorie accompagnement non trouvée');
        }
        
        // Créer le produit accompagnement
        $product_data = array(
            'category_id' => $category['id'],
            'name' => 'Frites Maison (Test)',
            'description' => 'Frites fraîches coupées et préparées sur place avec diverses options de sauces',
            'price' => 4.50,
            'unit_type' => 'piece',
            'unit_label' => '/portion',
            'has_accompaniment_options' => 1,
            'is_active' => 1,
            'display_order' => 1
        );
        
        $product_id = RestaurantBooking_Product::create($product_data);
        if (!$product_id) {
            return new WP_Error('product_creation_failed', 'Échec de la création du produit accompagnement');
        }
        
        $options_created = 0;
        $suboptions_created = 0;
        
        // Créer l'option "Sauces" avec sous-options gratuites
        $sauce_option_data = array(
            'product_id' => $product_id,
            'option_name' => 'Sauces',
            'option_price' => 0.00,
            'display_order' => 1
        );
        
        $sauce_option_id = RestaurantBooking_Accompaniment_Option_Manager::create_option($sauce_option_data);
        if (!is_wp_error($sauce_option_id)) {
            $options_created++;
            
            // Ajouter les sous-options de sauces
            $sauces = array('Ketchup', 'Mayonnaise', 'Moutarde', 'Sauce BBQ', 'Sauce Curry');
            foreach ($sauces as $index => $sauce) {
                $suboption_data = array(
                    'option_id' => $sauce_option_id,
                    'suboption_name' => $sauce,
                    'display_order' => $index + 1
                );
                
                $suboption_id = RestaurantBooking_Accompaniment_Option_Manager::create_suboption($suboption_data);
                if (!is_wp_error($suboption_id)) {
                    $suboptions_created++;
                }
            }
        }
        
        // Créer l'option "Enrobée sauce chimichurri" payante
        $chimichurri_option_data = array(
            'product_id' => $product_id,
            'option_name' => 'Enrobée sauce chimichurri',
            'option_price' => 1.00,
            'display_order' => 2
        );
        
        $chimichurri_option_id = RestaurantBooking_Accompaniment_Option_Manager::create_option($chimichurri_option_data);
        if (!is_wp_error($chimichurri_option_id)) {
            $options_created++;
        }
        
        // Créer l'option "Gratiné fromage" avec sous-options
        $gratine_option_data = array(
            'product_id' => $product_id,
            'option_name' => 'Gratiné fromage',
            'option_price' => 3.00,
            'display_order' => 3
        );
        
        $gratine_option_id = RestaurantBooking_Accompaniment_Option_Manager::create_option($gratine_option_data);
        if (!is_wp_error($gratine_option_id)) {
            $options_created++;
            
            // Ajouter les sous-options de fromages
            $fromages = array('Chèvre', 'Gruyère', 'Roquefort');
            foreach ($fromages as $index => $fromage) {
                $suboption_data = array(
                    'option_id' => $gratine_option_id,
                    'suboption_name' => $fromage,
                    'display_order' => $index + 1
                );
                
                $suboption_id = RestaurantBooking_Accompaniment_Option_Manager::create_suboption($suboption_data);
                if (!is_wp_error($suboption_id)) {
                    $suboptions_created++;
                }
            }
        }
        
        return array(
            'product_id' => $product_id,
            'options_created' => $options_created,
            'suboptions_created' => $suboptions_created
        );
    }
    
    /**
     * Créer une boisson de test avec tailles multiples
     */
    private static function create_test_beverage()
    {
        // Obtenir la catégorie boisson soft
        $category = RestaurantBooking_Category::get_by_type('boisson_soft');
        if (!$category) {
            return new WP_Error('no_category', 'Catégorie boisson soft non trouvée');
        }
        
        // Créer le produit boisson
        $product_data = array(
            'category_id' => $category['id'],
            'name' => 'Coca-Cola (Test)',
            'description' => 'La célèbre boisson gazeuse au cola disponible en plusieurs contenances',
            'price' => 0.00, // Prix défini par les tailles
            'unit_type' => 'centilitre',
            'unit_label' => '/cl',
            'has_multiple_sizes' => 1,
            'is_active' => 1,
            'display_order' => 1
        );
        
        $product_id = RestaurantBooking_Product::create($product_data);
        if (!$product_id) {
            return new WP_Error('product_creation_failed', 'Échec de la création du produit boisson');
        }
        
        $sizes_created = 0;
        
        // Créer les différentes tailles
        $sizes = array(
            array(
                'size_cl' => 33,
                'size_label' => 'Canette',
                'price' => 2.50,
                'is_featured' => 0,
                'display_order' => 1
            ),
            array(
                'size_cl' => 50,
                'size_label' => 'Bouteille',
                'price' => 3.50,
                'is_featured' => 1, // Mise en avant
                'display_order' => 2
            ),
            array(
                'size_cl' => 100,
                'size_label' => 'Grande bouteille',
                'price' => 5.00,
                'is_featured' => 0,
                'display_order' => 3
            )
        );
        
        foreach ($sizes as $size_data) {
            $size_data['product_id'] = $product_id;
            
            $size_id = RestaurantBooking_Beverage_Size_Manager::create_size($size_data);
            if (!is_wp_error($size_id)) {
                $sizes_created++;
            }
        }
        
        return array(
            'product_id' => $product_id,
            'sizes_created' => $sizes_created
        );
    }
    
    /**
     * Créer toutes les catégories nécessaires si elles n'existent pas
     */
    public static function ensure_categories_exist()
    {
        global $wpdb;
        
        $categories_to_create = array(
            // Plats principaux
            array(
                'name' => '🍽️ Plats Signature DOG',
                'type' => 'plat_signature_dog',
                'service_type' => 'restaurant',
                'description' => 'Plats signature à base de hot-dogs',
                'is_required' => 0,
                'min_per_person' => 0,
                'max_selection' => null
            ),
            array(
                'name' => '🍽️ Plats Signature CROQ',
                'type' => 'plat_signature_croq',
                'service_type' => 'restaurant',
                'description' => 'Plats signature à base de croques',
                'is_required' => 0,
                'min_per_person' => 0,
                'max_selection' => null
            ),
            array(
                'name' => '🍽️ Menu Enfant (Mini Boss)',
                'type' => 'mini_boss',
                'service_type' => 'restaurant',
                'description' => 'Menus spécialement conçus pour les enfants',
                'is_required' => 0,
                'min_per_person' => 0,
                'max_selection' => null
            ),
            
            // Accompagnements
            array(
                'name' => 'Accompagnements',
                'type' => 'accompagnement',
                'service_type' => 'restaurant',
                'description' => 'Accompagnements avec système d\'options',
                'is_required' => 0,
                'min_per_person' => 0,
                'max_selection' => null
            ),
            
            // Buffets
            array(
                'name' => '🍽️ Buffet Salé',
                'type' => 'buffet_sale',
                'service_type' => 'remorque',
                'description' => 'Plats salés pour buffet',
                'is_required' => 0,
                'min_per_person' => 1,
                'max_selection' => null
            ),
            array(
                'name' => '🍽️ Buffet Sucré',
                'type' => 'buffet_sucre',
                'service_type' => 'remorque',
                'description' => 'Desserts pour buffet',
                'is_required' => 0,
                'min_per_person' => 1,
                'max_selection' => null
            ),
            
            // Boissons
            array(
                'name' => '🍷 Boissons Soft',
                'type' => 'boisson_soft',
                'service_type' => 'both',
                'description' => 'Boissons sans alcool avec tailles multiples',
                'is_required' => 0,
                'min_per_person' => 0,
                'max_selection' => null
            ),
            array(
                'name' => '🍷 Vins Blancs',
                'type' => 'vin_blanc',
                'service_type' => 'both',
                'description' => 'Sélection de vins blancs',
                'is_required' => 0,
                'min_per_person' => 0,
                'max_selection' => null
            ),
            array(
                'name' => '🍷 Vins Rouges',
                'type' => 'vin_rouge',
                'service_type' => 'both',
                'description' => 'Sélection de vins rouges',
                'is_required' => 0,
                'min_per_person' => 0,
                'max_selection' => null
            ),
            array(
                'name' => '🍷 Bières Bouteilles',
                'type' => 'biere_bouteille',
                'service_type' => 'both',
                'description' => 'Bières en bouteilles',
                'is_required' => 0,
                'min_per_person' => 0,
                'max_selection' => null
            ),
            array(
                'name' => '🍷 Fûts de Bière',
                'type' => 'fut',
                'service_type' => 'both',
                'description' => 'Bières à la pression',
                'is_required' => 0,
                'min_per_person' => 0,
                'max_selection' => null
            )
        );
        
        $created_count = 0;
        
        foreach ($categories_to_create as $cat_data) {
            // Vérifier si la catégorie existe déjà
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}restaurant_categories WHERE type = %s",
                $cat_data['type']
            ));
            
            if (!$existing) {
                // Ajouter les champs obligatoires
                $cat_data['created_at'] = current_time('mysql');
                $cat_data['updated_at'] = current_time('mysql');
                $cat_data['is_active'] = 1;
                $cat_data['display_order'] = $created_count;
                $cat_data['slug'] = sanitize_title($cat_data['name']);
                
                $result = $wpdb->insert(
                    $wpdb->prefix . 'restaurant_categories',
                    $cat_data,
                    array('%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%d', '%d', '%s', '%s')
                );
                
                if ($result !== false) {
                    $created_count++;
                    RestaurantBooking_Logger::info("Catégorie créée: {$cat_data['name']} ({$cat_data['type']})");
                } else {
                    RestaurantBooking_Logger::error("Erreur création catégorie {$cat_data['name']}: " . $wpdb->last_error);
                }
            }
        }
        
        return $created_count;
    }
    
    /**
     * Obtenir un résumé des données de test
     */
    public static function get_test_data_summary()
    {
        global $wpdb;
        
        $summary = array();
        
        // Compter les accompagnements avec options
        $accompaniments = $wpdb->get_results("
            SELECT p.*, COUNT(o.id) as options_count
            FROM {$wpdb->prefix}restaurant_products p
            LEFT JOIN {$wpdb->prefix}restaurant_accompaniment_options o ON p.id = o.product_id
            INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
            WHERE c.type = 'accompagnement' AND p.name LIKE '%Test%'
            GROUP BY p.id
        ");
        
        $summary['accompaniments'] = count($accompaniments);
        $summary['accompaniment_options'] = array_sum(array_column($accompaniments, 'options_count'));
        
        // Compter les boissons avec tailles multiples
        $beverages = $wpdb->get_results("
            SELECT p.*, COUNT(s.id) as sizes_count
            FROM {$wpdb->prefix}restaurant_products p
            LEFT JOIN {$wpdb->prefix}restaurant_beverage_sizes s ON p.id = s.product_id
            INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
            WHERE c.type = 'boisson_soft' AND p.name LIKE '%Test%'
            GROUP BY p.id
        ");
        
        $summary['beverages'] = count($beverages);
        $summary['beverage_sizes'] = array_sum(array_column($beverages, 'sizes_count'));
        
        return $summary;
    }
}
