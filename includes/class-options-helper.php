<?php
/**
 * Classe Helper pour les Options
 * Facilite l'accès aux options configurées depuis les widgets publics
 *
 * @package RestaurantBooking
 * @since 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Options_Helper
{
    /**
     * Instance unique de la classe (Singleton)
     */
    private static $instance = null;

    /**
     * Cache des options
     */
    private $options = null;

    /**
     * Options par défaut
     */
    private $default_options = array(
        // Règles de validation produits
        'buffet_sale_min_per_person' => 1,
        'buffet_sale_min_recipes' => 2,
        'buffet_sale_text' => 'min 1/personne et min 2 recettes différents',
        
        'buffet_sucre_min_per_person' => 1,
        'buffet_sucre_min_dishes' => 1,
        'buffet_sucre_text' => 'min 1/personne et min 1 plat',
        
        'accompaniment_min_per_person' => 1,
        'accompaniment_text' => 'exactement 1/personne',
        
        'signature_dish_min_per_person' => 1,
        'signature_dish_text' => 'exactement 1 plat par personne',
        
        // Limites privatisation restaurant
        'restaurant_min_guests' => 10,
        'restaurant_max_guests' => 30,
        'restaurant_guests_text' => 'De 10 à 30 personnes',
        
        'restaurant_min_duration' => 2,
        'restaurant_max_duration_included' => 2,
        'restaurant_extra_hour_price' => 50,
        'restaurant_duration_text' => 'min durée = 2H (compris) max durée = 4H (supplément de +50 €/TTC/H)',
        
        // Limites privatisation remorque
        'remorque_min_guests' => 20,
        'remorque_max_guests' => 100,
        'remorque_staff_threshold' => 50,
        'remorque_staff_supplement' => 150,
        'remorque_guests_text' => 'À partir de 20 personnes',
        'remorque_staff_text' => 'au delà de 50p 1 forfait de +150€ s\'applique',
        
        'remorque_min_duration' => 2,
        'remorque_max_duration' => 5,
        'remorque_extra_hour_price' => 50,
        
        // Distance/Déplacement
        'free_radius_km' => 30,
        'price_30_50km' => 20,
        'price_50_100km' => 70,
        'price_100_150km' => 120,
        'max_distance_km' => 150,
        
        // Prix options remorque
        'tireuse_price' => 50,
        'games_price' => 70,
        
        // Prix accompagnements
        'accompaniment_base_price' => 4,
        'chimichurri_price' => 1,
        
        // Textes d'interface
        'final_message' => 'Votre devis est d\'ores et déjà disponible dans votre boîte mail, la suite ? Block va prendre contact avec vous afin d\'affiner celui-ci et de créer avec vous toute l\'expérience dont vous rêvez',
        'comment_section_text' => '1 question, 1 souhait, n\'hésitez pas de nous en fait part, on en parle, on....',
        
        // Descriptions forfaits
        'restaurant_forfait_description' => 'Mise à disposition des murs de Block|Notre équipe salle + cuisine assurant la prestation|Présentation + mise en place buffets, selon vos choix|Mise à disposition vaisselle + verrerie|Entretien + nettoyage',
        'remorque_forfait_description' => 'Notre équipe salle + cuisine assurant la prestation|Déplacement et installation de la remorque BLOCK (aller et retour)|Présentation + mise en place buffets, selon vos choix|La fourniture de vaisselle jetable recyclable|La fourniture de verrerie (en cas d\'ajout de boisson)'
    );

    /**
     * Constructeur privé (Singleton)
     */
    private function __construct() {}

    /**
     * Obtenir l'instance unique
     */
    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Obtenir toutes les options
     */
    public function get_options()
    {
        if ($this->options === null) {
            $saved_options = get_option('restaurant_booking_unified_options', array());
            
            // Nettoyer les échappements multiples dans les options sauvegardées
            foreach ($saved_options as $key => $value) {
                if (is_string($value)) {
                    $saved_options[$key] = $this->clean_escaped_quotes($value);
                }
            }
            
            $this->options = array_merge($this->default_options, $saved_options);
        }
        return $this->options;
    }
    
    /**
     * Nettoyer les échappements multiples d'apostrophes
     */
    private function clean_escaped_quotes($text)
    {
        // Remplacer les multiples échappements par une seule apostrophe
        $text = preg_replace('/\\\\+\'/', "'", $text);
        $text = preg_replace('/\\\\+\"/', '"', $text);
        
        return $text;
    }

    /**
     * Obtenir une option spécifique
     */
    public function get_option($key, $default = null)
    {
        $options = $this->get_options();
        return isset($options[$key]) ? $options[$key] : ($default !== null ? $default : (isset($this->default_options[$key]) ? $this->default_options[$key] : null));
    }

    /**
     * Vider le cache des options (utile après sauvegarde)
     */
    public function clear_cache()
    {
        $this->options = null;
    }

    // ========== MÉTHODES DE COMMODITÉ ==========

    /**
     * Règles Buffet Salé
     */
    public function get_buffet_sale_rules()
    {
        return array(
            'min_per_person' => $this->get_option('buffet_sale_min_per_person'),
            'min_recipes' => $this->get_option('buffet_sale_min_recipes'),
            'text' => $this->get_option('buffet_sale_text')
        );
    }

    /**
     * Règles Buffet Sucré
     */
    public function get_buffet_sucre_rules()
    {
        return array(
            'min_per_person' => $this->get_option('buffet_sucre_min_per_person'),
            'min_dishes' => $this->get_option('buffet_sucre_min_dishes'),
            'text' => $this->get_option('buffet_sucre_text')
        );
    }

    /**
     * Règles Accompagnements
     */
    public function get_accompaniment_rules()
    {
        return array(
            'min_per_person' => $this->get_option('accompaniment_min_per_person'),
            'base_price' => $this->get_option('accompaniment_base_price'),
            'chimichurri_price' => $this->get_option('chimichurri_price'),
            'text' => $this->get_option('accompaniment_text')
        );
    }

    /**
     * Règles Plats Signature
     */
    public function get_signature_dish_rules()
    {
        return array(
            'min_per_person' => $this->get_option('signature_dish_min_per_person'),
            'text' => $this->get_option('signature_dish_text')
        );
    }

    /**
     * Limites Restaurant
     */
    public function get_restaurant_limits()
    {
        return array(
            'min_guests' => $this->get_option('restaurant_min_guests'),
            'max_guests' => $this->get_option('restaurant_max_guests'),
            'guests_text' => $this->get_option('restaurant_guests_text'),
            'min_duration' => $this->get_option('restaurant_min_duration'),
            'max_duration_included' => $this->get_option('restaurant_max_duration_included'),
            'extra_hour_price' => $this->get_option('restaurant_extra_hour_price'),
            'duration_text' => $this->get_option('restaurant_duration_text')
        );
    }

    /**
     * Limites Remorque
     */
    public function get_remorque_limits()
    {
        return array(
            'min_guests' => $this->get_option('remorque_min_guests'),
            'max_guests' => $this->get_option('remorque_max_guests'),
            'staff_threshold' => $this->get_option('remorque_staff_threshold'),
            'staff_supplement' => $this->get_option('remorque_staff_supplement'),
            'guests_text' => $this->get_option('remorque_guests_text'),
            'staff_text' => $this->get_option('remorque_staff_text'),
            'min_duration' => $this->get_option('remorque_min_duration'),
            'max_duration' => $this->get_option('remorque_max_duration'),
            'extra_hour_price' => $this->get_option('remorque_extra_hour_price')
        );
    }

    /**
     * Tarifs Distance
     */
    public function get_distance_pricing()
    {
        return array(
            'free_radius_km' => $this->get_option('free_radius_km'),
            'price_30_50km' => $this->get_option('price_30_50km'),
            'price_50_100km' => $this->get_option('price_50_100km'),
            'price_100_150km' => $this->get_option('price_100_150km'),
            'max_distance_km' => $this->get_option('max_distance_km')
        );
    }

    /**
     * Prix Options Remorque
     */
    public function get_remorque_options_pricing()
    {
        return array(
            'tireuse_price' => $this->get_option('tireuse_price'),
            'games_price' => $this->get_option('games_price')
        );
    }

    /**
     * Textes d'Interface
     */
    public function get_interface_texts()
    {
        return array(
            'final_message' => $this->get_option('final_message'),
            'comment_section_text' => $this->get_option('comment_section_text')
        );
    }

    /**
     * Descriptions Forfaits
     */
    public function get_forfait_descriptions()
    {
        return array(
            'restaurant' => explode('|', $this->get_option('restaurant_forfait_description')),
            'remorque' => explode('|', $this->get_option('remorque_forfait_description'))
        );
    }

    /**
     * Calculer le prix en fonction de la distance
     */
    public function calculate_distance_price($distance_km)
    {
        $pricing = $this->get_distance_pricing();
        
        if ($distance_km <= $pricing['free_radius_km']) {
            return 0;
        } elseif ($distance_km <= 50) {
            return $pricing['price_30_50km'];
        } elseif ($distance_km <= 100) {
            return $pricing['price_50_100km'];
        } elseif ($distance_km <= $pricing['max_distance_km']) {
            return $pricing['price_100_150km'];
        } else {
            return false; // Distance trop élevée
        }
    }

    /**
     * Calculer le supplément personnel pour remorque
     */
    public function calculate_staff_supplement($guests_count)
    {
        $limits = $this->get_remorque_limits();
        
        if ($guests_count > $limits['staff_threshold']) {
            return $limits['staff_supplement'];
        }
        
        return 0;
    }

    /**
     * Calculer le supplément horaire
     */
    public function calculate_hour_supplement($duration, $service_type = 'restaurant')
    {
        if ($service_type === 'restaurant') {
            $limits = $this->get_restaurant_limits();
            $included_hours = $limits['max_duration_included'];
            $extra_price = $limits['extra_hour_price'];
        } else {
            $limits = $this->get_remorque_limits();
            $included_hours = 2; // Les 2 premières heures sont incluses pour la remorque
            $extra_price = $limits['extra_hour_price'];
        }
        
        if ($duration > $included_hours) {
            return ($duration - $included_hours) * $extra_price;
        }
        
        return 0;
    }

    /**
     * Valider les règles de buffet salé
     */
    public function validate_buffet_sale($selected_dishes, $guests_count)
    {
        $rules = $this->get_buffet_sale_rules();
        
        $errors = array();
        
        // Vérifier le nombre minimum de recettes différentes
        if (count($selected_dishes) < $rules['min_recipes']) {
            $errors[] = sprintf(
                __('Vous devez sélectionner au minimum %d recettes différentes pour le buffet salé.', 'restaurant-booking'),
                $rules['min_recipes']
            );
        }
        
        // Vérifier le nombre minimum par personne
        $total_quantity = array_sum($selected_dishes);
        $min_total = $guests_count * $rules['min_per_person'];
        
        if ($total_quantity < $min_total) {
            $errors[] = sprintf(
                __('Vous devez sélectionner au minimum %d plats pour %d personnes (%s).', 'restaurant-booking'),
                $min_total,
                $guests_count,
                $rules['text']
            );
        }
        
        return $errors;
    }

    /**
     * Valider les règles de buffet sucré
     */
    public function validate_buffet_sucre($selected_dishes, $guests_count)
    {
        $rules = $this->get_buffet_sucre_rules();
        
        $errors = array();
        
        // Vérifier le nombre minimum de plats
        if (count($selected_dishes) < $rules['min_dishes']) {
            $errors[] = sprintf(
                __('Vous devez sélectionner au minimum %d plat pour le buffet sucré.', 'restaurant-booking'),
                $rules['min_dishes']
            );
        }
        
        // Vérifier le nombre minimum par personne
        $total_quantity = array_sum($selected_dishes);
        $min_total = $guests_count * $rules['min_per_person'];
        
        if ($total_quantity < $min_total) {
            $errors[] = sprintf(
                __('Vous devez sélectionner au minimum %d desserts pour %d personnes (%s).', 'restaurant-booking'),
                $min_total,
                $guests_count,
                $rules['text']
            );
        }
        
        return $errors;
    }

    /**
     * Valider les règles d'accompagnements
     */
    public function validate_accompaniments($selected_accompaniments, $guests_count)
    {
        $rules = $this->get_accompaniment_rules();
        
        $errors = array();
        
        // Vérifier le nombre exact par personne
        $total_quantity = array_sum($selected_accompaniments);
        $required_total = $guests_count * $rules['min_per_person'];
        
        if ($total_quantity !== $required_total) {
            $errors[] = sprintf(
                __('Vous devez sélectionner exactement %d accompagnements pour %d personnes (%s). Actuellement sélectionnés : %d accompagnements.', 'restaurant-booking'),
                $required_total,
                $guests_count,
                $rules['text'],
                $total_quantity
            );
        }
        
        return $errors;
    }

    /**
     * Valider les règles de plats signature
     */
    public function validate_signature_dishes($selected_dishes, $guests_count)
    {
        $rules = $this->get_signature_dish_rules();
        
        $errors = array();
        
        // Vérifier le nombre minimum par personne
        $total_quantity = array_sum($selected_dishes);
        $required_minimum = $guests_count * $rules['min_per_person'];
        
        if ($total_quantity < $required_minimum) {
            $errors[] = sprintf(
                __('Vous devez sélectionner au minimum %d plats signature pour %d personnes (%s). Actuellement sélectionnés : %d plats.', 'restaurant-booking'),
                $required_minimum,
                $guests_count,
                $rules['text'],
                $total_quantity
            );
        }
        
        return $errors;
    }
}

