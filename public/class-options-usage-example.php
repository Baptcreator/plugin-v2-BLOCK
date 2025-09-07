<?php
/**
 * EXEMPLE D'UTILISATION DES OPTIONS UNIFIÉES
 * 
 * Ce fichier montre comment remplacer les valeurs codées en dur
 * par les options configurables dans l'admin
 *
 * @package RestaurantBooking
 * @since 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Options_Usage_Example
{
    /**
     * Instance du helper d'options
     */
    private $options_helper;

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->options_helper = RestaurantBooking_Options_Helper::get_instance();
    }

    /**
     * AVANT: Valeurs codées en dur dans class-ajax-handler-v2.php ligne 199-201
     * 
     * $min_guests = $service_type === 'restaurant' ? 10 : 20;
     * $max_guests = $service_type === 'restaurant' ? 30 : 100;
     * $max_hours = $service_type === 'restaurant' ? 4 : 5;
     */
    public function get_service_limits_new_way($service_type)
    {
        if ($service_type === 'restaurant') {
            $limits = $this->options_helper->get_restaurant_limits();
            return array(
                'min_guests' => $limits['min_guests'],
                'max_guests' => $limits['max_guests'],
                'max_hours' => 4, // Durée max pour restaurant
                'guests_text' => $limits['guests_text'],
                'duration_text' => $limits['duration_text']
            );
        } else {
            $limits = $this->options_helper->get_remorque_limits();
            return array(
                'min_guests' => $limits['min_guests'],
                'max_guests' => $limits['max_guests'],
                'max_hours' => $limits['max_duration'],
                'guests_text' => $limits['guests_text'],
                'staff_text' => $limits['staff_text']
            );
        }
    }

    /**
     * Exemple : Générer le contenu de l'étape forfait de base
     */
    public function generate_forfait_content($service_type, $duration, $guests_count)
    {
        if ($service_type === 'restaurant') {
            $limits = $this->options_helper->get_restaurant_limits();
            $descriptions = $this->options_helper->get_forfait_descriptions();
            
            $content = '<div class="forfait-card">';
            $content .= '<h3>FORFAIT DE BASE PRIVATISATION RESTO</h3>';
            $content .= '<p>Ce forfait comprend :</p>';
            $content .= '<ul>';
            
            foreach ($descriptions['restaurant'] as $item) {
                // Remplacer le placeholder de durée
                $item = str_replace('[___]H', $duration . 'H', $item);
                $content .= '<li>' . esc_html($item) . '</li>';
            }
            
            $content .= '</ul>';
            
            // Calculer et afficher le supplément horaire
            $hour_supplement = $this->options_helper->calculate_hour_supplement($duration, 'restaurant');
            if ($hour_supplement > 0) {
                $content .= '<p class="supplement-info">';
                $content .= sprintf(__('Supplément durée : +%s €', 'restaurant-booking'), number_format($hour_supplement, 2));
                $content .= '</p>';
            }
            
            $content .= '</div>';
            
        } else {
            // Remorque
            $limits = $this->options_helper->get_remorque_limits();
            $descriptions = $this->options_helper->get_forfait_descriptions();
            
            $content = '<div class="forfait-card">';
            $content .= '<h3>FORFAIT DE BASE PRIVATISATION REMORQUE BLOCK</h3>';
            $content .= '<p>Ce forfait comprend :</p>';
            $content .= '<ul>';
            
            foreach ($descriptions['remorque'] as $item) {
                // Remplacer le placeholder de durée
                $item = str_replace('[___]H', $duration . 'H', $item);
                $content .= '<li>' . esc_html($item) . '</li>';
            }
            
            $content .= '</ul>';
            
            // Calculer les suppléments
            $hour_supplement = $this->options_helper->calculate_hour_supplement($duration, 'remorque');
            $staff_supplement = $this->options_helper->calculate_staff_supplement($guests_count);
            
            if ($hour_supplement > 0) {
                $content .= '<p class="supplement-info">';
                $content .= sprintf(__('Supplément durée : +%s €', 'restaurant-booking'), number_format($hour_supplement, 2));
                $content .= '</p>';
            }
            
            if ($staff_supplement > 0) {
                $content .= '<p class="supplement-info">';
                $content .= sprintf(__('Supplément personnel : +%s €', 'restaurant-booking'), number_format($staff_supplement, 2));
                $content .= '</p>';
            }
            
            $content .= '</div>';
        }
        
        return $content;
    }

    /**
     * Exemple : Validation des sélections de buffet
     */
    public function validate_buffet_selections($buffet_type, $selected_dishes, $guests_count)
    {
        $errors = array();
        
        if ($buffet_type === 'sale') {
            $errors = $this->options_helper->validate_buffet_sale($selected_dishes, $guests_count);
        } elseif ($buffet_type === 'sucre') {
            $errors = $this->options_helper->validate_buffet_sucre($selected_dishes, $guests_count);
        }
        
        return $errors;
    }

    /**
     * Exemple : Afficher les règles d'accompagnements
     */
    public function render_accompaniment_rules()
    {
        $rules = $this->options_helper->get_accompaniment_rules();
        
        ob_start();
        ?>
        <div class="accompaniment-section">
            <h3>Choix de l'accompagnement <?php echo number_format($rules['base_price'], 2); ?> €</h3>
            <p class="rules-text"><?php echo esc_html($rules['text']); ?></p>
            
            <div class="accompaniment-options">
                <label>
                    <input type="checkbox" name="accompaniment[]" value="salade" data-price="<?php echo $rules['base_price']; ?>">
                    Salade
                </label>
                
                <label>
                    <input type="checkbox" name="accompaniment[]" value="frites" data-price="<?php echo $rules['base_price']; ?>">
                    Frites
                </label>
                
                <!-- Options pour les frites -->
                <div class="frites-options" style="display: none; margin-left: 20px;">
                    <label>
                        <input type="checkbox" name="frites_option[]" value="chimichurri" data-price="<?php echo $rules['chimichurri_price']; ?>">
                        Enrobée sauce chimichurri +<?php echo number_format($rules['chimichurri_price'], 2); ?>€
                    </label>
                    
                    <div class="sauce-selection">
                        <p>Choix de la sauce :</p>
                        <label><input type="checkbox" name="sauce[]" value="ketchup"> Ketchup</label>
                        <label><input type="checkbox" name="sauce[]" value="mayo"> Mayo</label>
                        <label><input type="checkbox" name="sauce[]" value="bbq"> BBQ</label>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Exemple : Afficher les textes d'interface configurables
     */
    public function render_interface_texts()
    {
        $texts = $this->options_helper->get_interface_texts();
        
        ob_start();
        ?>
        <div class="interface-texts-example">
            <!-- Message final après soumission -->
            <div class="final-message" style="display: none;">
                <p><?php echo esc_html($texts['final_message']); ?></p>
            </div>
            
            <!-- Section commentaire -->
            <div class="comment-section">
                <label for="comments">Questions/Commentaires</label>
                <p class="description"><?php echo esc_html($texts['comment_section_text']); ?></p>
                <textarea name="comments" id="comments" rows="4"></textarea>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Exemple : Calculer le prix en fonction de la distance (pour remorque)
     */
    public function calculate_delivery_price($postal_code)
    {
        // Ici vous utiliseriez votre calculateur de distance existant
        // pour obtenir la distance en km, puis :
        
        $distance_km = 45; // Exemple : 45 km calculés
        
        $price = $this->options_helper->calculate_distance_price($distance_km);
        
        if ($price === false) {
            return array(
                'error' => true,
                'message' => sprintf(
                    __('Désolé, nous ne livrons pas au-delà de %d km.', 'restaurant-booking'),
                    $this->options_helper->get_option('max_distance_km')
                )
            );
        }
        
        return array(
            'error' => false,
            'distance' => $distance_km,
            'price' => $price,
            'message' => $price > 0 ? sprintf(__('Supplément livraison : %s €', 'restaurant-booking'), number_format($price, 2)) : __('Livraison gratuite', 'restaurant-booking')
        );
    }

    /**
     * Exemple : Afficher les options spécifiques remorque avec prix configurables
     */
    public function render_remorque_options()
    {
        $pricing = $this->options_helper->get_remorque_options_pricing();
        
        ob_start();
        ?>
        <div class="remorque-options">
            <h3>Choix des Options</h3>
            
            <div class="option-item">
                <label>
                    <input type="checkbox" name="options[]" value="tireuse" data-price="<?php echo $pricing['tireuse_price']; ?>">
                    <strong>MISE À DISPO TIREUSE <?php echo number_format($pricing['tireuse_price'], 0); ?> €</strong>
                </label>
                <p class="description">Descriptif + mention (fûts non inclus à choisir)</p>
            </div>
            
            <div class="option-item">
                <label>
                    <input type="checkbox" name="options[]" value="jeux" data-price="<?php echo $pricing['games_price']; ?>">
                    <strong>INSTALLATION JEUX <?php echo number_format($pricing['games_price'], 0); ?> €</strong>
                </label>
                <p class="description">Descriptif avec listing des jeux (type jeu gonflable)</p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * INSTRUCTIONS POUR REMPLACER LES VALEURS CODÉES EN DUR
     * 
     * 1. Dans class-ajax-handler-v2.php ligne 199-201, remplacer :
     *    $min_guests = $service_type === 'restaurant' ? 10 : 20;
     *    Par :
     *    $limits = RestaurantBooking_Options_Helper::get_instance()->get_restaurant_limits();
     *    $min_guests = $limits['min_guests'];
     * 
     * 2. Dans tous les fichiers de widgets, remplacer les textes codés en dur par :
     *    $helper = RestaurantBooking_Options_Helper::get_instance();
     *    $text = $helper->get_option('nom_de_loption');
     * 
     * 3. Pour les validations, utiliser les méthodes de validation :
     *    $errors = $helper->validate_buffet_sale($selected, $guests);
     * 
     * 4. Pour les calculs de prix :
     *    $supplement = $helper->calculate_hour_supplement($duration, $type);
     */
}

// Fonction helper globale pour un accès facile
if (!function_exists('rb_get_option')) {
    /**
     * Raccourci pour obtenir une option
     */
    function rb_get_option($key, $default = null) {
        return RestaurantBooking_Options_Helper::get_instance()->get_option($key, $default);
    }
}

if (!function_exists('rb_get_limits')) {
    /**
     * Raccourci pour obtenir les limites d'un service
     */
    function rb_get_limits($service_type) {
        $helper = RestaurantBooking_Options_Helper::get_instance();
        return $service_type === 'restaurant' ? $helper->get_restaurant_limits() : $helper->get_remorque_limits();
    }
}

