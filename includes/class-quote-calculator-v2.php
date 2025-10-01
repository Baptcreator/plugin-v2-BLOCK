<?php
/**
 * Calculateur de prix pour devis v2
 *
 * @package RestaurantBooking
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Quote_Calculator_V2
{
    /**
     * Instance unique
     */
    private static $instance = null;

    /**
     * Obtenir l'instance unique
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Calculer le prix total d'un devis
     */
    public function calculate_total($service_type, $form_data)
    {
        $calculation = array(
            'service_type' => $service_type,
            'base_price' => 0,
            'duration_supplement' => 0,
            'guest_supplement' => 0,
            'distance_supplement' => 0,
            'products_total' => 0,
            'supplements_total' => 0,
            'options_total' => 0,
            'total_price' => 0,
            'breakdown' => array()
        );

        try {
            // 1. Prix de base du forfait
            $calculation['base_price'] = $this->calculate_base_price($service_type);
            $calculation['breakdown'][] = array(
                'label' => 'Forfait de base',
                'amount' => $calculation['base_price']
            );

            // 2. Supplément durée
            if (isset($form_data['event_duration'])) {
                $duration_supplement = $this->calculate_duration_supplement($service_type, (int) $form_data['event_duration']);
                $calculation['duration_supplement'] = $duration_supplement;
                
                if ($duration_supplement > 0) {
                    $calculation['breakdown'][] = array(
                        'label' => 'Supplément durée',
                        'amount' => $duration_supplement
                    );
                }
            }

            // 3. Supplément convives (remorque uniquement)
            if ($service_type === 'remorque' && isset($form_data['guest_count'])) {
                $guest_supplement = $this->calculate_guest_supplement((int) $form_data['guest_count']);
                $calculation['guest_supplement'] = $guest_supplement;
                
                if ($guest_supplement > 0) {
                    $calculation['breakdown'][] = array(
                        'label' => 'Supplément +50 convives',
                        'amount' => $guest_supplement
                    );
                }
            }

            // 4. Supplément distance (remorque uniquement)
            if ($service_type === 'remorque') {
                $distance_supplement_amount = 0;
                $zone_name = 'Zone locale';
                
                // Debug: Logger les données reçues
                RestaurantBooking_Logger::info('Calcul distance - données reçues', array(
                    'form_data_keys' => array_keys($form_data),
                    'delivery_supplement' => $form_data['delivery_supplement'] ?? 'non défini',
                    'delivery_zone' => $form_data['delivery_zone'] ?? 'non défini',
                    'postal_code' => $form_data['postal_code'] ?? 'non défini'
                ));
                
                // Utiliser les données déjà calculées côté client si disponibles
                if (isset($form_data['delivery_supplement']) && isset($form_data['delivery_zone'])) {
                    $distance_supplement_amount = (float) $form_data['delivery_supplement'];
                    $zone_name = $form_data['delivery_zone'];
                    RestaurantBooking_Logger::info('Utilisation données client', array(
                        'supplement' => $distance_supplement_amount,
                        'zone' => $zone_name
                    ));
                } elseif (isset($form_data['postal_code'])) {
                    // Fallback : recalculer côté serveur
                    $distance_supplement = $this->calculate_distance_supplement($form_data['postal_code']);
                    $distance_supplement_amount = $distance_supplement['amount'];
                    $zone_name = $distance_supplement['zone_name'];
                    RestaurantBooking_Logger::info('Recalcul côté serveur', array(
                        'supplement' => $distance_supplement_amount,
                        'zone' => $zone_name
                    ));
                }
                
                $calculation['distance_supplement'] = $distance_supplement_amount;
                
                if ($distance_supplement_amount > 0) {
                    $calculation['breakdown'][] = array(
                        'label' => 'Supplément livraison (' . $zone_name . ')',
                        'amount' => $distance_supplement_amount
                    );
                    RestaurantBooking_Logger::info('Supplément ajouté au breakdown', array(
                        'label' => 'Supplément livraison (' . $zone_name . ')',
                        'amount' => $distance_supplement_amount
                    ));
                }
            }

            // 5. Prix des produits sélectionnés
            if (isset($form_data['selected_products'])) {
                $products_total = $this->calculate_products_total($form_data['selected_products']);
                $calculation['products_total'] = $products_total;
                
                if ($products_total > 0) {
                    $calculation['breakdown'][] = array(
                        'label' => 'Produits sélectionnés',
                        'amount' => $products_total
                    );
                }
            }

            // 6. Prix des suppléments
            if (isset($form_data['selected_supplements'])) {
                $supplements_total = $this->calculate_supplements_total($form_data['selected_supplements']);
                $calculation['supplements_total'] = $supplements_total;
                
                if ($supplements_total > 0) {
                    $calculation['breakdown'][] = array(
                        'label' => 'Suppléments',
                        'amount' => $supplements_total
                    );
                }
            }

            // 7. Prix des options (remorque uniquement)
            if ($service_type === 'remorque') {
                $options_total = $this->calculate_options_total($form_data);
                $calculation['options_total'] = $options_total['total'];
                
                foreach ($options_total['breakdown'] as $option) {
                    $calculation['breakdown'][] = $option;
                }
            }

            // 8. Calcul du total
            $calculation['total_price'] = 
                $calculation['base_price'] +
                $calculation['duration_supplement'] +
                $calculation['guest_supplement'] +
                $calculation['distance_supplement'] +
                $calculation['products_total'] +
                $calculation['supplements_total'] +
                $calculation['options_total'];

            // Arrondir à 2 décimales
            foreach ($calculation as $key => $value) {
                if (is_numeric($value)) {
                    $calculation[$key] = round($value, 2);
                }
            }

            RestaurantBooking_Logger::info('Calcul de prix effectué', array(
                'service_type' => $service_type,
                'total' => $calculation['total_price']
            ));

            return $calculation;

        } catch (Exception $e) {
            RestaurantBooking_Logger::error('Erreur lors du calcul de prix', array(
                'error' => $e->getMessage(),
                'service_type' => $service_type,
                'form_data' => $form_data
            ));

            throw $e;
        }
    }

    /**
     * Calculer le prix de base du forfait
     */
    private function calculate_base_price($service_type)
    {
        $setting_key = $service_type . '_base_price';
        $default_price = $service_type === 'restaurant' ? 300 : 350;
        
        return (float) RestaurantBooking_Settings::get($setting_key, $default_price);
    }

    /**
     * Calculer le supplément de durée
     */
    private function calculate_duration_supplement($service_type, $duration)
    {
        $included_hours = (int) RestaurantBooking_Settings::get($service_type . '_included_hours', 2);
        $hourly_supplement = (float) RestaurantBooking_Settings::get('hourly_supplement', 50);
        
        if ($duration > $included_hours) {
            return ($duration - $included_hours) * $hourly_supplement;
        }
        
        return 0;
    }

    /**
     * Calculer le supplément convives (remorque +50 personnes)
     */
    private function calculate_guest_supplement($guest_count)
    {
        if ($guest_count > 50) {
            return (float) RestaurantBooking_Settings::get('remorque_50_guests_supplement', 150);
        }
        
        return 0;
    }

    /**
     * Calculer le supplément de distance
     */
    private function calculate_distance_supplement($postal_code)
    {
        try {
            // Utiliser le nouveau service Google Maps
            $google_maps = RestaurantBooking_Google_Maps_Service::get_instance();
            $distance_result = $google_maps->calculate_distance_from_restaurant($postal_code);
            
            if (is_wp_error($distance_result)) {
                throw new Exception($distance_result->get_error_message());
            }
            
            $distance_km = $distance_result['distance_km'];
            
            // Calculer le supplément selon les zones configurées en base de données
            $zone_info = $this->get_delivery_zone_for_distance($distance_km);
            if ($zone_info) {
                return [
                    'amount' => (float) $zone_info['delivery_price'],
                    'zone_name' => $zone_info['zone_name'] . ' (' . $zone_info['distance_min'] . '-' . $zone_info['distance_max'] . 'km)'
                ];
            } else {
                // Aucune zone trouvée = distance trop importante
                $max_distance = $this->get_max_delivery_distance();
                throw new Exception(sprintf('Distance trop importante (max %dkm)', $max_distance));
            }
            
        } catch (Exception $e) {
            // En cas d'erreur, retourner 0 mais logger l'erreur
            RestaurantBooking_Logger::warning('Erreur calcul distance', array(
                'postal_code' => $postal_code,
                'error' => $e->getMessage()
            ));
            
            return ['amount' => 0, 'zone_name' => 'Erreur calcul'];
        }
    }

    /**
     * Calculer le total des produits sélectionnés
     */
    private function calculate_products_total($selected_products)
    {
        $total = 0;
        
        foreach ($selected_products as $product_id => $selection) {
            if (!isset($selection['quantity']) || $selection['quantity'] <= 0) {
                continue;
            }
            
            $product = RestaurantBooking_Product::get($product_id);
            if (!$product || !$product['is_active']) {
                continue;
            }
            
            $quantity = (int) $selection['quantity'];
            $product_total = $product['price'] * $quantity;
            
            // Ajouter les tailles de boissons si spécifiées
            if (isset($selection['size_id']) && !empty($selection['size_id'])) {
                $beverage_manager = RestaurantBooking_Beverage_Manager::get_instance();
                $size = RestaurantBooking_Beverage_Manager::get_beverage_size($selection['size_id']);
                
                if ($size && $size['is_active']) {
                    $product_total = $size['price'] * $quantity;
                }
            }
            
            // Ajouter les prix de fûts si spécifiés
            elseif (isset($selection['keg_size']) && !empty($selection['keg_size'])) {
                $keg_price_field = 'keg_size_' . $selection['keg_size'] . 'l_price';
                if (isset($product[$keg_price_field]) && $product[$keg_price_field] > 0) {
                    $product_total = $product[$keg_price_field] * $quantity;
                }
            }
            
            $total += $product_total;
        }
        
        return $total;
    }

    /**
     * Calculer le total des suppléments
     */
    private function calculate_supplements_total($selected_supplements)
    {
        $total = 0;
        
        foreach ($selected_supplements as $product_id => $supplements) {
            foreach ($supplements as $supplement_id => $quantity) {
                if ($quantity <= 0) {
                    continue;
                }
                
                $supplement = RestaurantBooking_Supplement_Manager::get($supplement_id);
                if (!$supplement || !$supplement['is_active']) {
                    continue;
                }
                
                $total += $supplement['price'] * $quantity;
            }
        }
        
        return $total;
    }

    /**
     * Calculer le total des options (remorque)
     */
    private function calculate_options_total($form_data)
    {
        $total = 0;
        $breakdown = array();
        
        // Option tireuse
        if (isset($form_data['option_tireuse']) && $form_data['option_tireuse']) {
            $tireuse_price = (float) RestaurantBooking_Settings::get('remorque_tireuse_price', 50);
            $total += $tireuse_price;
            
            $breakdown[] = array(
                'label' => 'Mise à disposition tireuse',
                'amount' => $tireuse_price
            );
            
            // Ajouter les fûts sélectionnés
            if (isset($form_data['selected_kegs'])) {
                $kegs_total = $this->calculate_kegs_total($form_data['selected_kegs']);
                $total += $kegs_total;
                
                if ($kegs_total > 0) {
                    $breakdown[] = array(
                        'label' => 'Fûts sélectionnés',
                        'amount' => $kegs_total
                    );
                }
            }
        }
        
        // Option jeux
        if (isset($form_data['option_games']) && $form_data['option_games']) {
            $games_price = (float) RestaurantBooking_Settings::get('remorque_games_base_price', 70);
            
            // Ajouter les jeux sélectionnés
            if (isset($form_data['selected_games'])) {
                $games_total = $this->calculate_games_total($form_data['selected_games']);
                $total += $games_total;
                
                if ($games_total > 0) {
                    $breakdown[] = array(
                        'label' => 'Installation jeux',
                        'amount' => $games_total
                    );
                }
            } else {
                // Prix de base si aucun jeu spécifique sélectionné
                $total += $games_price;
                $breakdown[] = array(
                    'label' => 'Installation jeux',
                    'amount' => $games_price
                );
            }
        }
        
        return array(
            'total' => $total,
            'breakdown' => $breakdown
        );
    }

    /**
     * Calculer le total des fûts
     */
    private function calculate_kegs_total($selected_kegs)
    {
        $total = 0;
        
        foreach ($selected_kegs as $keg_id => $selection) {
            if (!isset($selection['quantity']) || $selection['quantity'] <= 0) {
                continue;
            }
            
            $keg = RestaurantBooking_Product::get($keg_id);
            if (!$keg || !$keg['is_active']) {
                continue;
            }
            
            $quantity = (int) $selection['quantity'];
            $keg_size = $selection['size'] ?? '10'; // 10L par défaut
            
            $price_field = 'keg_size_' . $keg_size . 'l_price';
            if (isset($keg[$price_field]) && $keg[$price_field] > 0) {
                $total += $keg[$price_field] * $quantity;
            }
        }
        
        return $total;
    }

    /**
     * Calculer le total des jeux
     */
    private function calculate_games_total($selected_games)
    {
        $total = 0;
        
        foreach ($selected_games as $game_id => $quantity) {
            if ($quantity <= 0) {
                continue;
            }
            
            $game = RestaurantBooking_Game::get($game_id);
            if (!$game || !$game['is_active']) {
                continue;
            }
            
            $total += $game['price'] * $quantity;
        }
        
        return $total;
    }

    /**
     * Valider les règles métier pour les sélections
     */
    public function validate_selections($service_type, $form_data)
    {
        $errors = array();
        
        try {
            // Valider les sélections de produits selon les règles métier
            if (isset($form_data['selected_products'])) {
                $product_errors = $this->validate_product_selections($service_type, $form_data);
                $errors = array_merge($errors, $product_errors);
            }
            
            // Valider les suppléments
            if (isset($form_data['selected_supplements'])) {
                $supplement_errors = $this->validate_supplement_selections($form_data);
                $errors = array_merge($errors, $supplement_errors);
            }
            
            return $errors;
            
        } catch (Exception $e) {
            RestaurantBooking_Logger::error('Erreur validation sélections', array(
                'error' => $e->getMessage(),
                'service_type' => $service_type
            ));
            
            return array($e->getMessage());
        }
    }

    /**
     * Valider les sélections de produits
     */
    private function validate_product_selections($service_type, $form_data)
    {
        $errors = array();
        $guest_count = (int) ($form_data['guest_count'] ?? 0);
        
        if ($guest_count <= 0) {
            return $errors; // Pas de validation si pas de convives
        }
        
        // Obtenir les catégories et leurs règles
        $categories = RestaurantBooking_Product::get_by_service_type($service_type);
        
        foreach ($categories as $category_type => $category_data) {
            $category_info = $category_data['category_info'];
            $selected_products = $this->get_selected_products_by_category($form_data['selected_products'], $category_data['products']);
            
            // Vérifier si la sélection est obligatoire
            if ($category_info['is_required'] && empty($selected_products)) {
                $errors[] = sprintf(__('Sélection obligatoire pour %s', 'restaurant-booking'), $category_info['name']);
                continue;
            }
            
            if (!empty($selected_products)) {
                // Vérifier le minimum de sélections
                if ($category_info['min_selection'] > 0 && count($selected_products) < $category_info['min_selection']) {
                    $errors[] = sprintf(__('Minimum %d sélections requises pour %s', 'restaurant-booking'), 
                        $category_info['min_selection'], $category_info['name']);
                }
                
                // Vérifier le maximum de sélections
                if ($category_info['max_selection'] && count($selected_products) > $category_info['max_selection']) {
                    $errors[] = sprintf(__('Maximum %d sélections autorisées pour %s', 'restaurant-booking'), 
                        $category_info['max_selection'], $category_info['name']);
                }
                
                // Vérifier le minimum par personne
                if ($category_info['min_per_person']) {
                    $total_quantity = array_sum(array_column($selected_products, 'quantity'));
                    if ($total_quantity < $guest_count) {
                        $errors[] = sprintf(__('Minimum 1 par convive requis pour %s (%d manquants)', 'restaurant-booking'), 
                            $category_info['name'], $guest_count - $total_quantity);
                    }
                }
            }
        }
        
        return $errors;
    }

    /**
     * Valider les sélections de suppléments
     */
    private function validate_supplement_selections($form_data)
    {
        $errors = array();
        
        if (!isset($form_data['selected_supplements']) || !isset($form_data['selected_products'])) {
            return $errors;
        }
        
        foreach ($form_data['selected_supplements'] as $product_id => $supplements) {
            // Vérifier que le produit principal est sélectionné
            if (!isset($form_data['selected_products'][$product_id])) {
                continue;
            }
            
            $product_quantity = (int) $form_data['selected_products'][$product_id]['quantity'];
            
            foreach ($supplements as $supplement_id => $supplement_quantity) {
                if ($supplement_quantity <= 0) {
                    continue;
                }
                
                // Valider la quantité du supplément
                $validation = RestaurantBooking_Supplement_Manager::validate_supplement_quantity(
                    $supplement_id, 
                    $supplement_quantity, 
                    $product_quantity
                );
                
                if (is_wp_error($validation)) {
                    $errors[] = $validation->get_error_message();
                }
            }
        }
        
        return $errors;
    }

    /**
     * Obtenir les produits sélectionnés par catégorie
     */
    private function get_selected_products_by_category($selected_products, $category_products)
    {
        $category_product_ids = array_column($category_products, 'id');
        $selected_in_category = array();
        
        foreach ($selected_products as $product_id => $selection) {
            if (in_array($product_id, $category_product_ids) && $selection['quantity'] > 0) {
                $selected_in_category[] = $selection;
            }
        }
        
        return $selected_in_category;
    }

    /**
     * Obtenir un résumé du calcul pour l'affichage
     */
    public function get_calculation_summary($calculation)
    {
        $summary = array(
            'total_formatted' => $this->format_price($calculation['total_price']),
            'base_formatted' => $this->format_price($calculation['base_price']),
            'supplements_formatted' => $this->format_price(
                $calculation['duration_supplement'] + 
                $calculation['guest_supplement'] + 
                $calculation['distance_supplement']
            ),
            'products_formatted' => $this->format_price($calculation['products_total']),
            'breakdown' => array()
        );
        
        foreach ($calculation['breakdown'] as $item) {
            $summary['breakdown'][] = array(
                'label' => $item['label'],
                'amount_formatted' => $this->format_price($item['amount'])
            );
        }
        
        return $summary;
    }

    /**
     * Formater un prix
     */
    private function format_price($price)
    {
        return number_format($price, 2, ',', ' ') . ' €';
    }

    /**
     * Obtenir la zone de livraison pour une distance donnée
     */
    private function get_delivery_zone_for_distance($distance_km)
    {
        global $wpdb;
        
        $zone = $wpdb->get_row($wpdb->prepare("
            SELECT zone_name, distance_min, distance_max, delivery_price
            FROM {$wpdb->prefix}restaurant_delivery_zones
            WHERE is_active = 1 
            AND %f >= distance_min 
            AND %f <= distance_max
            ORDER BY distance_min ASC
            LIMIT 1
        ", $distance_km, $distance_km), ARRAY_A);
        
        return $zone;
    }

    /**
     * Obtenir la distance maximale de livraison
     */
    private function get_max_delivery_distance()
    {
        global $wpdb;
        
        $max_distance = $wpdb->get_var("
            SELECT MAX(distance_max) 
            FROM {$wpdb->prefix}restaurant_delivery_zones
            WHERE is_active = 1
        ");
        
        return $max_distance ? (int) $max_distance : 150; // Fallback à 150km si pas de données
    }
}
