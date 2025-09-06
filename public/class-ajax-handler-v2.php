<?php
/**
 * Gestionnaire AJAX pour le système v2
 *
 * @package RestaurantBooking
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Ajax_Handler_V2
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
     * Constructeur
     */
    private function __construct()
    {
        $this->init_hooks();
    }

    /**
     * Initialiser les hooks AJAX
     */
    private function init_hooks()
    {
        // Hooks pour les utilisateurs connectés et non connectés
        add_action('wp_ajax_load_quote_form_step', array($this, 'load_quote_form_step'));
        add_action('wp_ajax_nopriv_load_quote_form_step', array($this, 'load_quote_form_step'));

        add_action('wp_ajax_calculate_quote_price_realtime', array($this, 'calculate_quote_price_realtime'));
        add_action('wp_ajax_nopriv_calculate_quote_price_realtime', array($this, 'calculate_quote_price_realtime'));

        add_action('wp_ajax_submit_unified_quote_form', array($this, 'submit_unified_quote_form'));
        add_action('wp_ajax_nopriv_submit_unified_quote_form', array($this, 'submit_unified_quote_form'));

        add_action('wp_ajax_get_products_by_category_v2', array($this, 'get_products_by_category_v2'));
        add_action('wp_ajax_nopriv_get_products_by_category_v2', array($this, 'get_products_by_category_v2'));

        add_action('wp_ajax_validate_step_data', array($this, 'validate_step_data'));
        add_action('wp_ajax_nopriv_validate_step_data', array($this, 'validate_step_data'));

        add_action('wp_ajax_check_date_availability_v2', array($this, 'check_date_availability_v2'));
        add_action('wp_ajax_nopriv_check_date_availability_v2', array($this, 'check_date_availability_v2'));
    }

    /**
     * Charger une étape du formulaire
     */
    public function load_quote_form_step()
    {
        try {
            // Vérification de sécurité
            if (!wp_verify_nonce($_POST['nonce'], 'restaurant_booking_quote_form')) {
                throw new Exception(__('Erreur de sécurité', 'restaurant-booking'));
            }

            $service_type = sanitize_text_field($_POST['service_type']);
            $step = (int) $_POST['step'];
            $form_data = isset($_POST['form_data']) ? $_POST['form_data'] : array();

            // Valider le type de service
            if (!in_array($service_type, array('restaurant', 'remorque'))) {
                throw new Exception(__('Type de service invalide', 'restaurant-booking'));
            }

            // Générer le contenu de l'étape
            $step_data = $this->generate_step_content($service_type, $step, $form_data);

            wp_send_json_success($step_data);

        } catch (Exception $e) {
            RestaurantBooking_Logger::error('Erreur lors du chargement de l\'étape', array(
                'error' => $e->getMessage(),
                'service_type' => $_POST['service_type'] ?? '',
                'step' => $_POST['step'] ?? ''
            ));

            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Générer le contenu d'une étape
     */
    private function generate_step_content($service_type, $step, $form_data)
    {
        $step_data = array(
            'step_number' => $step,
            'service_type' => $service_type,
            'total_steps' => $service_type === 'restaurant' ? 6 : 7,
            'title' => '',
            'subtitle' => '',
            'content' => ''
        );

        switch ($step) {
            case 1:
                $step_data = $this->generate_step_1_content($service_type, $form_data);
                break;
            case 2:
                $step_data = $this->generate_step_2_content($service_type, $form_data);
                break;
            case 3:
                $step_data = $this->generate_step_3_content($service_type, $form_data);
                break;
            case 4:
                $step_data = $this->generate_step_4_content($service_type, $form_data);
                break;
            case 5:
                $step_data = $this->generate_step_5_content($service_type, $form_data);
                break;
            case 6:
                if ($service_type === 'remorque') {
                    $step_data = $this->generate_step_6_remorque_content($form_data);
                } else {
                    $step_data = $this->generate_step_6_restaurant_content($form_data);
                }
                break;
            case 7:
                if ($service_type === 'remorque') {
                    $step_data = $this->generate_step_7_remorque_content($form_data);
                }
                break;
        }

        return $step_data;
    }

    /**
     * Étape 1: Introduction au service
     */
    private function generate_step_1_content($service_type, $form_data)
    {
        $title_key = $service_type . '_step1_title';
        $process_list_key = $service_type . '_step1_process_list';
        
        $title = RestaurantBooking_Settings::get($title_key, 'Pourquoi privatiser notre ' . $service_type . ' ?');
        $process_list = json_decode(RestaurantBooking_Settings::get($process_list_key, '[]'), true);

        $content = '<div class="rb-info-card">';
        $content .= '<h4>' . RestaurantBooking_Settings::get($service_type . '_step1_card_title', 'Comment ça fonctionne ?') . '</h4>';
        
        if (!empty($process_list)) {
            $content .= '<ul class="rb-info-list">';
            foreach ($process_list as $item) {
                $content .= '<li>' . esc_html($item) . '</li>';
            }
            $content .= '</ul>';
        }
        
        $content .= '</div>';
        
        $content .= '<div style="text-align: center; margin-top: 30px;">';
        $content .= '<button type="button" class="rb-btn rb-btn-primary rb-btn-next">';
        $content .= __('COMMENCER MON DEVIS', 'restaurant-booking');
        $content .= '</button>';
        $content .= '</div>';

        return array(
            'step_number' => 1,
            'service_type' => $service_type,
            'total_steps' => $service_type === 'restaurant' ? 6 : 7,
            'title' => $title,
            'content' => $content
        );
    }

    /**
     * Étape 2: Forfait de base
     */
    private function generate_step_2_content($service_type, $form_data)
    {
        $title_key = $service_type . '_step2_title';
        $card_title_key = $service_type . '_step2_card_title';
        $included_items_key = $service_type . '_step2_included_items';
        
        $title = RestaurantBooking_Settings::get($title_key, 'FORFAIT DE BASE');
        $card_title = RestaurantBooking_Settings::get($card_title_key, 'FORFAIT DE BASE');
        $included_items = json_decode(RestaurantBooking_Settings::get($included_items_key, '[]'), true);

        $min_guests = $service_type === 'restaurant' ? 10 : 20;
        $max_guests = $service_type === 'restaurant' ? 30 : 100;
        $max_hours = $service_type === 'restaurant' ? 4 : 5;

        $content = '<div class="rb-form-row">';
        
        // Date
        $content .= '<div class="rb-form-group">';
        $content .= '<label class="rb-form-label">' . __('Date souhaitée événement', 'restaurant-booking') . '</label>';
        $content .= '<input type="date" name="event_date" class="rb-form-field rb-date-picker" required>';
        $content .= '</div>';
        
        // Nombre de convives
        $content .= '<div class="rb-form-group">';
        $content .= '<label class="rb-form-label">' . __('Nombre de convives', 'restaurant-booking') . '</label>';
        $content .= '<input type="number" name="guest_count" class="rb-form-field" min="' . $min_guests . '" max="' . $max_guests . '" required>';
        $content .= '<small>Min: ' . $min_guests . ' - Max: ' . $max_guests . '</small>';
        $content .= '</div>';
        
        // Durée
        $content .= '<div class="rb-form-group">';
        $content .= '<label class="rb-form-label">' . __('Durée souhaitée événement', 'restaurant-booking') . '</label>';
        $content .= '<select name="event_duration" class="rb-form-field" required>';
        for ($i = 2; $i <= $max_hours; $i++) {
            $content .= '<option value="' . $i . '">' . $i . 'H</option>';
        }
        $content .= '</select>';
        $content .= '</div>';
        
        // Code postal pour remorque
        if ($service_type === 'remorque') {
            $content .= '<div class="rb-form-group">';
            $content .= '<label class="rb-form-label">' . __('Commune événement', 'restaurant-booking') . '</label>';
            $content .= '<input type="text" name="postal_code" class="rb-form-field" placeholder="67000" maxlength="5" required>';
            $content .= '<small>Max 150 km de Strasbourg</small>';
            $content .= '</div>';
        }
        
        $content .= '</div>';

        // Card forfait de base
        $content .= '<div class="rb-info-card" style="margin-top: 30px;">';
        $content .= '<h4>' . esc_html($card_title) . '</h4>';
        $content .= '<p><strong>Ce forfait comprend :</strong></p>';
        
        if (!empty($included_items)) {
            $content .= '<ul class="rb-info-list">';
            foreach ($included_items as $item) {
                // Remplacer les variables dynamiques
                $item = str_replace('[___]H', '<span class="rb-dynamic-hours">2H</span>', $item);
                $content .= '<li>' . wp_kses_post($item) . '</li>';
            }
            $content .= '</ul>';
        }
        
        $content .= '</div>';

        return array(
            'step_number' => 2,
            'service_type' => $service_type,
            'total_steps' => $service_type === 'restaurant' ? 6 : 7,
            'title' => $title,
            'content' => $content
        );
    }

    /**
     * Étape 3: Choix des formules repas
     */
    private function generate_step_3_content($service_type, $form_data)
    {
        $title = __('CHOIX DES FORMULES REPAS', 'restaurant-booking');
        
        $content = '<div class="rb-products-section">';
        
        // Sélecteur plat signature
        $content .= '<div class="rb-product-category">';
        $content .= '<h4>' . __('Choix du plat signature', 'restaurant-booking') . '</h4>';
        $content .= '<p><em>Minimum 1 plat par personne</em></p>';
        
        $content .= '<div class="rb-signature-selector">';
        $content .= '<label><input type="radio" name="signature_type" value="DOG" required> DOG</label>';
        $content .= '<label><input type="radio" name="signature_type" value="CROQ" required> CROQ</label>';
        $content .= '</div>';
        
        $content .= '<div class="rb-signature-products" id="signature-products"></div>';
        $content .= '</div>';
        
        // Menu Mini Boss
        $content .= '<div class="rb-product-category">';
        $content .= '<h4>' . __('LE MENU DES MINI BOSS (menu enfant)', 'restaurant-booking') . '</h4>';
        $content .= '<div class="rb-mini-boss-products" id="mini-boss-products"></div>';
        $content .= '</div>';
        
        // Accompagnements
        $content .= '<div class="rb-product-category">';
        $content .= '<h4>' . __('Choix de l\'accompagnement 4 €', 'restaurant-booking') . '</h4>';
        $content .= '<p><em>Minimum 1 par personne</em></p>';
        $content .= '<div class="rb-accompaniment-products" id="accompaniment-products"></div>';
        $content .= '</div>';
        
        $content .= '</div>';

        return array(
            'step_number' => 3,
            'service_type' => $service_type,
            'total_steps' => $service_type === 'restaurant' ? 6 : 7,
            'title' => $title,
            'content' => $content
        );
    }

    /**
     * Étape 4: Choix des buffets
     */
    private function generate_step_4_content($service_type, $form_data)
    {
        $title = __('CHOIX DU/DES BUFFET(S)', 'restaurant-booking');
        
        $content = '<div class="rb-buffet-section">';
        
        $content .= '<div class="rb-buffet-type-selector">';
        $content .= '<h4>' . __('Choisissez votre type de buffet', 'restaurant-booking') . '</h4>';
        
        $content .= '<div class="rb-buffet-options">';
        $content .= '<label><input type="radio" name="buffet_type" value="sale" required> Buffet salé</label>';
        $content .= '<label><input type="radio" name="buffet_type" value="sucre" required> Buffet sucré</label>';
        $content .= '<label><input type="radio" name="buffet_type" value="both" required> Buffets salés et sucrés</label>';
        $content .= '</div>';
        $content .= '</div>';
        
        $content .= '<div class="rb-buffet-products">';
        $content .= '<div class="rb-buffet-sale" id="buffet-sale-products" style="display: none;"></div>';
        $content .= '<div class="rb-buffet-sucre" id="buffet-sucre-products" style="display: none;"></div>';
        $content .= '</div>';
        
        $content .= '</div>';

        return array(
            'step_number' => 4,
            'service_type' => $service_type,
            'total_steps' => $service_type === 'restaurant' ? 6 : 7,
            'title' => $title,
            'content' => $content
        );
    }

    /**
     * Étape 5: Choix des boissons
     */
    private function generate_step_5_content($service_type, $form_data)
    {
        $title = __('CHOIX DES BOISSONS', 'restaurant-booking') . ' <small>(Optionnel)</small>';
        
        $content = '<div class="rb-beverages-section">';
        
        // Onglets des catégories
        $categories = array('soft', 'vin_blanc', 'vin_rouge', 'biere');
        if ($service_type === 'restaurant') {
            $categories[] = 'fut';
        }
        
        $content .= '<div class="rb-beverage-tabs">';
        foreach ($categories as $category) {
            $label = $this->get_category_label($category);
            $content .= '<button type="button" class="rb-tab-btn" data-category="' . $category . '">' . $label . '</button>';
        }
        $content .= '</div>';
        
        // Suggestions
        $content .= '<div class="rb-beverage-suggestions">';
        $content .= '<h4>' . __('Nos suggestions', 'restaurant-booking') . '</h4>';
        $content .= '<div id="featured-beverages"></div>';
        $content .= '</div>';
        
        // Contenu des onglets
        $content .= '<div class="rb-beverage-content">';
        foreach ($categories as $category) {
            $content .= '<div class="rb-beverage-tab-content" data-category="' . $category . '" style="display: none;"></div>';
        }
        $content .= '</div>';
        
        $content .= '</div>';

        return array(
            'step_number' => 5,
            'service_type' => $service_type,
            'total_steps' => $service_type === 'restaurant' ? 6 : 7,
            'title' => $title,
            'content' => $content
        );
    }

    /**
     * Étape 6 Restaurant: Coordonnées/Contact
     */
    private function generate_step_6_restaurant_content($form_data)
    {
        return $this->generate_contact_step_content('restaurant', 6);
    }

    /**
     * Étape 6 Remorque: Choix des options
     */
    private function generate_step_6_remorque_content($form_data)
    {
        $title = __('CHOIX DES OPTIONS', 'restaurant-booking') . ' <small>(Optionnel)</small>';
        
        $content = '<div class="rb-options-section">';
        
        // Option tireuse
        $tireuse_price = RestaurantBooking_Settings::get('remorque_tireuse_price', 50);
        $content .= '<div class="rb-option-card">';
        $content .= '<h4>MISE À DISPO TIREUSE ' . $tireuse_price . ' €</h4>';
        $content .= '<p>Descriptif + mention (futs non inclus à choisir)</p>';
        $content .= '<label><input type="checkbox" name="option_tireuse" value="1"> Ajouter cette option</label>';
        $content .= '<div class="rb-tireuse-kegs" id="tireuse-kegs" style="display: none;"></div>';
        $content .= '</div>';
        
        // Option jeux
        $games_price = RestaurantBooking_Settings::get('remorque_games_base_price', 70);
        $content .= '<div class="rb-option-card">';
        $content .= '<h4>INSTALLATION JEUX ' . $games_price . ' €</h4>';
        $content .= '<p>Descriptif avec listing des jeux (type jeu gonflable)</p>';
        $content .= '<label><input type="checkbox" name="option_games" value="1"> Ajouter cette option</label>';
        $content .= '<div class="rb-games-selection" id="games-selection" style="display: none;"></div>';
        $content .= '</div>';
        
        $content .= '</div>';

        return array(
            'step_number' => 6,
            'service_type' => 'remorque',
            'total_steps' => 7,
            'title' => $title,
            'content' => $content
        );
    }

    /**
     * Étape 7 Remorque: Coordonnées/Contact
     */
    private function generate_step_7_remorque_content($form_data)
    {
        return $this->generate_contact_step_content('remorque', 7);
    }

    /**
     * Générer l'étape de contact
     */
    private function generate_contact_step_content($service_type, $step_number)
    {
        $title = __('COORDONNÉES/CONTACT', 'restaurant-booking');
        
        $content = '<div class="rb-contact-form">';
        
        $content .= '<div class="rb-form-row">';
        $content .= '<div class="rb-form-group">';
        $content .= '<label class="rb-form-label">' . __('Nom', 'restaurant-booking') . '</label>';
        $content .= '<input type="text" name="customer_lastname" class="rb-form-field" required>';
        $content .= '</div>';
        
        $content .= '<div class="rb-form-group">';
        $content .= '<label class="rb-form-label">' . __('Prénom', 'restaurant-booking') . '</label>';
        $content .= '<input type="text" name="customer_firstname" class="rb-form-field" required>';
        $content .= '</div>';
        $content .= '</div>';
        
        $content .= '<div class="rb-form-row">';
        $content .= '<div class="rb-form-group">';
        $content .= '<label class="rb-form-label">' . __('Téléphone', 'restaurant-booking') . '</label>';
        $content .= '<input type="tel" name="customer_phone" class="rb-form-field" required>';
        $content .= '</div>';
        
        $content .= '<div class="rb-form-group">';
        $content .= '<label class="rb-form-label">' . __('Email', 'restaurant-booking') . '</label>';
        $content .= '<input type="email" name="customer_email" class="rb-form-field" required>';
        $content .= '</div>';
        $content .= '</div>';
        
        $content .= '<div class="rb-form-group">';
        $content .= '<label class="rb-form-label">' . __('Questions/Commentaires', 'restaurant-booking') . '</label>';
        $content .= '<textarea name="customer_message" class="rb-form-field" rows="4" placeholder="1 question, 1 souhait, n\'hésitez pas de nous en faire part, on en parle, on..."></textarea>';
        $content .= '</div>';
        
        $content .= '</div>';

        return array(
            'step_number' => $step_number,
            'service_type' => $service_type,
            'total_steps' => $service_type === 'restaurant' ? 6 : 7,
            'title' => $title,
            'content' => $content
        );
    }

    /**
     * Calculer le prix en temps réel
     */
    public function calculate_quote_price_realtime()
    {
        try {
            // Vérification de sécurité
            if (!wp_verify_nonce($_POST['nonce'], 'restaurant_booking_quote_form')) {
                throw new Exception(__('Erreur de sécurité', 'restaurant-booking'));
            }

            $service_type = sanitize_text_field($_POST['service_type']);
            $form_data = $_POST['form_data'];

            // Créer une instance du calculateur de prix
            $calculator = new RestaurantBooking_Quote_Calculator_V2();
            $price_data = $calculator->calculate_total($service_type, $form_data);

            wp_send_json_success($price_data);

        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Soumettre le formulaire unifié
     */
    public function submit_unified_quote_form()
    {
        try {
            // Vérification de sécurité
            if (!wp_verify_nonce($_POST['nonce'], 'restaurant_booking_quote_form')) {
                throw new Exception(__('Erreur de sécurité', 'restaurant-booking'));
            }

            $service_type = sanitize_text_field($_POST['service_type']);
            $form_data = $_POST['form_data'];

            // Valider les données
            $this->validate_form_data($service_type, $form_data);

            // Créer le devis
            $quote_id = $this->create_quote($service_type, $form_data);

            // Envoyer l'email
            $this->send_quote_email($quote_id);

            wp_send_json_success(array(
                'quote_id' => $quote_id,
                'message' => __('Devis envoyé avec succès', 'restaurant-booking')
            ));

        } catch (Exception $e) {
            RestaurantBooking_Logger::error('Erreur lors de la soumission du devis', array(
                'error' => $e->getMessage(),
                'form_data' => $_POST['form_data'] ?? null
            ));

            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Obtenir les produits par catégorie v2
     */
    public function get_products_by_category_v2()
    {
        try {
            $category_type = sanitize_text_field($_POST['category_type']);
            $service_type = sanitize_text_field($_POST['service_type']);

            $products = RestaurantBooking_Product::get_by_service_type($service_type);
            
            if (isset($products[$category_type])) {
                wp_send_json_success($products[$category_type]);
            } else {
                wp_send_json_success(array('products' => array()));
            }

        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Valider les données d'une étape
     */
    public function validate_step_data()
    {
        try {
            $service_type = sanitize_text_field($_POST['service_type']);
            $step = (int) $_POST['step'];
            $step_data = $_POST['step_data'];

            $validation_result = $this->validate_step($service_type, $step, $step_data);

            wp_send_json_success($validation_result);

        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Vérifier la disponibilité d'une date v2
     */
    public function check_date_availability_v2()
    {
        try {
            $date = sanitize_text_field($_POST['date']);
            $service_type = sanitize_text_field($_POST['service_type']);

            // Vérifier la disponibilité dans la base de données
            $is_available = RestaurantBooking_Calendar::is_date_available($date, $service_type);

            wp_send_json_success(array(
                'available' => $is_available,
                'date' => $date
            ));

        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Utilitaires privées
     */
    private function get_category_label($category)
    {
        $labels = array(
            'soft' => 'SOFTS',
            'vin_blanc' => 'VINS BLANCS',
            'vin_rouge' => 'VINS ROUGES',
            'biere' => 'BIÈRES BOUTEILLE',
            'fut' => 'LES FÛTS'
        );

        return $labels[$category] ?? strtoupper($category);
    }

    private function validate_form_data($service_type, $form_data)
    {
        // Validation des champs obligatoires
        $required_fields = array('event_date', 'guest_count', 'event_duration', 'customer_firstname', 'customer_lastname', 'customer_email', 'customer_phone');
        
        if ($service_type === 'remorque') {
            $required_fields[] = 'postal_code';
        }

        foreach ($required_fields as $field) {
            if (empty($form_data[$field])) {
                throw new Exception(sprintf(__('Le champ %s est obligatoire', 'restaurant-booking'), $field));
            }
        }

        // Validations spécifiques
        $guest_count = (int) $form_data['guest_count'];
        $min_guests = $service_type === 'restaurant' ? 10 : 20;
        $max_guests = $service_type === 'restaurant' ? 30 : 100;

        if ($guest_count < $min_guests || $guest_count > $max_guests) {
            throw new Exception(sprintf(__('Nombre de convives invalide (%d-%d)', 'restaurant-booking'), $min_guests, $max_guests));
        }

        return true;
    }

    private function create_quote($service_type, $form_data)
    {
        // Calculer le prix total
        $calculator = new RestaurantBooking_Quote_Calculator_V2();
        $price_data = $calculator->calculate_total($service_type, $form_data);

        // Préparer les données du devis
        $quote_data = array(
            'service_type' => $service_type,
            'event_date' => $form_data['event_date'],
            'event_duration' => (int) $form_data['event_duration'],
            'guest_count' => (int) $form_data['guest_count'],
            'postal_code' => $form_data['postal_code'] ?? null,
            'customer_data' => array(
                'firstname' => $form_data['customer_firstname'],
                'lastname' => $form_data['customer_lastname'],
                'email' => $form_data['customer_email'],
                'phone' => $form_data['customer_phone'],
                'message' => $form_data['customer_message'] ?? ''
            ),
            'selected_products' => $form_data['selected_products'] ?? array(),
            'price_breakdown' => $price_data,
            'base_price' => $price_data['base_price'],
            'supplements_total' => $price_data['supplements_total'],
            'products_total' => $price_data['products_total'],
            'total_price' => $price_data['total_price'],
            'status' => 'draft'
        );

        return RestaurantBooking_Quote::create($quote_data);
    }

    private function send_quote_email($quote_id)
    {
        // Obtenir le devis
        $quote = RestaurantBooking_Quote::get($quote_id);
        
        if (!$quote) {
            throw new Exception(__('Devis introuvable', 'restaurant-booking'));
        }

        // Envoyer l'email avec PDF
        $email_handler = RestaurantBooking_Email::get_instance();
        return $email_handler->send_quote_email($quote);
    }

    private function validate_step($service_type, $step, $step_data)
    {
        // Implémentation de la validation par étape
        return array('valid' => true);
    }
}
