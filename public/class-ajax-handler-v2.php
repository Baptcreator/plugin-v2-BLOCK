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
        
        $settings_manager = RestaurantBooking_Settings::get_instance();
        $title = $settings_manager->get($title_key, 'Pourquoi privatiser notre ' . $service_type . ' ?');
        $process_list_json = $settings_manager->get($process_list_key, '[]');
        $process_list = is_string($process_list_json) ? json_decode($process_list_json, true) : [];

        $content = '<div class="rb-info-card">';
        $content .= '<h4>' . $settings_manager->get($service_type . '_step1_card_title', 'Comment ça fonctionne ?') . '</h4>';
        
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
        
        // Récupérer les paramètres depuis la base de données
        $settings_manager = RestaurantBooking_Settings::get_instance();
        $title = $settings_manager->get($title_key, 'FORFAIT DE BASE');
        $card_title = $settings_manager->get($card_title_key, 'FORFAIT DE BASE');
        $included_items_json = $settings_manager->get($included_items_key, '[]');
        $included_items = is_string($included_items_json) ? json_decode($included_items_json, true) : [];

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
        $guest_count = isset($form_data['guest_count']) ? (int) $form_data['guest_count'] : 10;
        
        $content = '<div class="rb-products-section">';
        
        // Message d'information
        $content .= '<div class="rb-info-banner" style="background: #e8f4fd; border: 1px solid #bee5eb; padding: 15px; border-radius: 8px; margin-bottom: 25px;">';
        $content .= '<p><strong>ℹ️ Information importante :</strong></p>';
        $content .= '<p>Sélection obligatoire pour <strong>' . $guest_count . ' convives</strong>. Les quantités minimales sont calculées automatiquement.</p>';
        $content .= '</div>';
        
        // 1. Sélecteur plat signature
        $content .= '<div class="rb-product-category" data-category="signature">';
        $content .= '<h4 class="rb-category-title">🍽️ ' . __('Choix du plat signature', 'restaurant-booking') . '</h4>';
        $content .= '<p class="rb-category-description"><em>Minimum 1 plat par personne - Choix obligatoire</em></p>';
        
        $content .= '<div class="rb-signature-type-selector">';
        $content .= '<div class="rb-radio-group">';
        $content .= '<label class="rb-radio-option"><input type="radio" name="signature_type" value="DOG" required> <span>🌭 DOG - Nos hot-dogs signature</span></label>';
        $content .= '<label class="rb-radio-option"><input type="radio" name="signature_type" value="CROQ" required> <span>🥪 CROQ - Nos croque-monsieurs</span></label>';
        $content .= '</div>';
        $content .= '</div>';
        
        $content .= '<div class="rb-signature-products" id="signature-products" style="display: none; margin-top: 20px;">';
        $content .= '<div class="rb-loading-placeholder">Chargement des produits...</div>';
        $content .= '</div>';
        $content .= '</div>';
        
        // 2. Menu Mini Boss
        $content .= '<div class="rb-product-category" data-category="mini-boss">';
        $content .= '<h4 class="rb-category-title">👶 ' . __('LE MENU DES MINI BOSS', 'restaurant-booking') . '</h4>';
        $content .= '<p class="rb-category-description"><em>Menu enfant - Optionnel</em></p>';
        $content .= '<div class="rb-mini-boss-products" id="mini-boss-products">';
        $content .= $this->generate_mini_boss_products();
        $content .= '</div>';
        $content .= '</div>';
        
        // 3. Accompagnements
        $content .= '<div class="rb-product-category" data-category="accompaniments">';
        $content .= '<h4 class="rb-category-title">🥗 ' . __('Choix des accompagnements', 'restaurant-booking') . '</h4>';
        $content .= '<p class="rb-category-description"><em>Minimum 1 par personne - 4,00 € par accompagnement</em></p>';
        $content .= '<div class="rb-accompaniment-products" id="accompaniment-products">';
        $content .= $this->generate_accompaniment_products();
        $content .= '</div>';
        $content .= '</div>';
        
        $content .= '</div>';

        // JavaScript pour la gestion des produits
        $content .= '<script>';
        $content .= 'document.addEventListener("DOMContentLoaded", function() {';
        $content .= '    initProductsStep3(' . $guest_count . ');';
        $content .= '});';
        $content .= '</script>';

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
        $title = __('CHOIX DU/DES BUFFET(S)', 'restaurant-booking') . ' <small>(Optionnel)</small>';
        $guest_count = isset($form_data['guest_count']) ? (int) $form_data['guest_count'] : 10;
        
        $content = '<div class="rb-buffet-section">';
        
        // Message d'information
        $content .= '<div class="rb-info-banner" style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 8px; margin-bottom: 25px;">';
        $content .= '<p><strong>🍽️ Information :</strong></p>';
        $content .= '<p>Les buffets sont optionnels et viennent compléter vos plats signature. Système à 3 niveaux selon vos besoins.</p>';
        $content .= '</div>';
        
        // Sélecteur de type de buffet
        $content .= '<div class="rb-buffet-type-selector">';
        $content .= '<h4 class="rb-category-title">Choisissez votre type de buffet</h4>';
        
        $content .= '<div class="rb-radio-group">';
        $content .= '<label class="rb-radio-option">';
        $content .= '<input type="radio" name="buffet_type" value="none" checked> ';
        $content .= '<span>🚫 Aucun buffet</span>';
        $content .= '</label>';
        $content .= '<label class="rb-radio-option">';
        $content .= '<input type="radio" name="buffet_type" value="sale"> ';
        $content .= '<span>🥗 Buffet salé uniquement</span>';
        $content .= '</label>';
        $content .= '<label class="rb-radio-option">';
        $content .= '<input type="radio" name="buffet_type" value="sucre"> ';
        $content .= '<span>🍰 Buffet sucré uniquement</span>';
        $content .= '</label>';
        $content .= '<label class="rb-radio-option">';
        $content .= '<input type="radio" name="buffet_type" value="both"> ';
        $content .= '<span>🍽️ Buffets salés ET sucrés</span>';
        $content .= '</label>';
        $content .= '</div>';
        $content .= '</div>';
        
        // Conteneurs pour les buffets
        $content .= '<div class="rb-buffet-products">';
        
        // Buffet salé
        $content .= '<div class="rb-buffet-category" id="buffet-sale-section" style="display: none;">';
        $content .= '<h4 class="rb-category-title">🥗 Buffet Salé - Système à 3 niveaux</h4>';
        $content .= '<div class="rb-buffet-levels">';
        $content .= $this->generate_buffet_levels('sale', $guest_count);
        $content .= '</div>';
        $content .= '</div>';
        
        // Buffet sucré
        $content .= '<div class="rb-buffet-category" id="buffet-sucre-section" style="display: none;">';
        $content .= '<h4 class="rb-category-title">🍰 Buffet Sucré - Système à 3 niveaux</h4>';
        $content .= '<div class="rb-buffet-levels">';
        $content .= $this->generate_buffet_levels('sucre', $guest_count);
        $content .= '</div>';
        $content .= '</div>';
        
        $content .= '</div>';
        $content .= '</div>';

        // JavaScript pour la gestion des buffets
        $content .= '<script>';
        $content .= 'document.addEventListener("DOMContentLoaded", function() {';
        $content .= '    initBuffetsStep4(' . $guest_count . ');';
        $content .= '});';
        $content .= '</script>';

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
        
        // Message d'information
        $content .= '<div class="rb-info-banner" style="background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 8px; margin-bottom: 25px;">';
        $content .= '<p><strong>🍷 Information :</strong></p>';
        $content .= '<p>Les boissons sont optionnelles. Sections dépliables pour explorer nos différentes gammes.</p>';
        $content .= '</div>';
        
        // Sections dépliables des catégories
        $categories = array(
            'soft' => array('label' => '🥤 SOFTS', 'description' => 'Boissons sans alcool, jus, sodas'),
            'vin_blanc' => array('label' => '🍾 VINS BLANCS', 'description' => 'Notre sélection de vins blancs'),
            'vin_rouge' => array('label' => '🍷 VINS ROUGES', 'description' => 'Notre sélection de vins rouges'),
            'biere' => array('label' => '🍺 BIÈRES BOUTEILLE', 'description' => 'Bières en bouteille et canettes')
        );
        
        // Ajouter les fûts pour le restaurant
        if ($service_type === 'restaurant') {
            $categories['fut'] = array('label' => '🍺 LES FÛTS', 'description' => 'Bières pression et fûts');
        }
        
        foreach ($categories as $category_key => $category_info) {
            $content .= '<div class="rb-beverage-category" data-category="' . $category_key . '">';
            $content .= '<div class="rb-category-header" onclick="toggleBeverageCategory(\'' . $category_key . '\')">';
            $content .= '<h4 class="rb-category-title">' . $category_info['label'] . '</h4>';
            $content .= '<p class="rb-category-description">' . $category_info['description'] . '</p>';
            $content .= '<div class="rb-category-toggle">▼</div>';
            $content .= '</div>';
            
            $content .= '<div class="rb-category-content" id="beverages-' . $category_key . '" style="display: none;">';
            $content .= '<div class="rb-loading-placeholder">Chargement des produits...</div>';
            $content .= '</div>';
            $content .= '</div>';
        }
        
        $content .= '</div>';

        // JavaScript pour la gestion des boissons
        $content .= '<script>';
        $content .= 'let loadedBeverageCategories = [];';
        $content .= 'function toggleBeverageCategory(category) {';
        $content .= '    const content = document.getElementById("beverages-" + category);';
        $content .= '    const toggle = content.parentElement.querySelector(".rb-category-toggle");';
        $content .= '    if (content.style.display === "none") {';
        $content .= '        content.style.display = "block";';
        $content .= '        toggle.textContent = "▲";';
        $content .= '        if (!loadedBeverageCategories.includes(category)) {';
        $content .= '            loadBeverageCategory(category);';
        $content .= '            loadedBeverageCategories.push(category);';
        $content .= '        }';
        $content .= '    } else {';
        $content .= '        content.style.display = "none";';
        $content .= '        toggle.textContent = "▼";';
        $content .= '    }';
        $content .= '}';
        $content .= '</script>';

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
        $settings_manager = RestaurantBooking_Settings::get_instance();
        $tireuse_price = $settings_manager->get('remorque_tireuse_price', 50);
        $content .= '<div class="rb-option-card">';
        $content .= '<h4>MISE À DISPO TIREUSE ' . $tireuse_price . ' €</h4>';
        $content .= '<p>Descriptif + mention (futs non inclus à choisir)</p>';
        $content .= '<label><input type="checkbox" name="option_tireuse" value="1"> Ajouter cette option</label>';
        $content .= '<div class="rb-tireuse-kegs" id="tireuse-kegs" style="display: none;"></div>';
        $content .= '</div>';
        
        // Option jeux
        $games_price = $settings_manager->get('remorque_games_base_price', 70);
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
            $form_data = $_POST['form_data'] ?? array();

            // Nettoyer et valider les données du formulaire
            $cleaned_form_data = $this->clean_form_data($form_data);

            // Créer une instance du calculateur de prix
            if (class_exists('RestaurantBooking_Quote_Calculator_V2')) {
                $calculator = new RestaurantBooking_Quote_Calculator_V2();
                $price_data = $calculator->calculate_total($service_type, $cleaned_form_data);
            } else {
                // Fallback : calcul simple
                $price_data = $this->calculate_simple_price($service_type, $cleaned_form_data);
            }

            // Formater les données de prix pour l'affichage
            $formatted_price_data = array(
                'base_price' => $price_data['base_price'],
                'supplements_total' => $price_data['duration_supplement'] + $price_data['guest_supplement'] + $price_data['distance_supplement'],
                'products_total' => $price_data['products_total'] + $price_data['supplements_total'],
                'options_total' => $price_data['options_total'] ?? 0,
                'total_price' => $price_data['total_price'],
                'breakdown' => $price_data['breakdown'] ?? array(),
                'service_type' => $service_type
            );

            wp_send_json_success($formatted_price_data);

        } catch (Exception $e) {
            RestaurantBooking_Logger::error('Erreur lors du calcul de prix en temps réel', array(
                'error' => $e->getMessage(),
                'service_type' => $_POST['service_type'] ?? '',
                'form_data' => $_POST['form_data'] ?? array()
            ));

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
            // Vérification de sécurité
            if (!wp_verify_nonce($_POST['nonce'], 'restaurant_booking_quote_form')) {
                throw new Exception(__('Erreur de sécurité', 'restaurant-booking'));
            }

            $category_type = sanitize_text_field($_POST['category_type']);
            $service_type = sanitize_text_field($_POST['service_type']);
            $signature_type = isset($_POST['signature_type']) ? sanitize_text_field($_POST['signature_type']) : '';

            // Gestion spéciale pour les plats signature
            if ($category_type === 'signature' && !empty($signature_type)) {
                $products = $this->get_signature_products($signature_type, $service_type);
                wp_send_json_success(array('products' => $products, 'signature_type' => $signature_type));
                return;
            }

            // Utiliser la classe Product existante pour récupérer les produits
            if (class_exists('RestaurantBooking_Product')) {
                $products = RestaurantBooking_Product::get_by_service_type($service_type);
                
                if (isset($products[$category_type])) {
                    wp_send_json_success($products[$category_type]);
                } else {
                    wp_send_json_success(array('products' => array()));
                }
            } else {
                // Fallback : récupérer directement depuis la base de données
                $products = $this->get_products_by_category_fallback($category_type, $service_type);
                wp_send_json_success(array('products' => $products));
            }

        } catch (Exception $e) {
            RestaurantBooking_Logger::error('Erreur lors de la récupération des produits', array(
                'error' => $e->getMessage(),
                'category_type' => $_POST['category_type'] ?? '',
                'service_type' => $_POST['service_type'] ?? ''
            ));

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
            // Vérification de sécurité
            if (!wp_verify_nonce($_POST['nonce'], 'restaurant_booking_quote_form')) {
                throw new Exception(__('Erreur de sécurité', 'restaurant-booking'));
            }

            $date = sanitize_text_field($_POST['date']);
            $service_type = sanitize_text_field($_POST['service_type']);

            // Vérifier que la date est valide
            if (!$date || !strtotime($date)) {
                throw new Exception(__('Date invalide', 'restaurant-booking'));
            }

            // Vérifier la disponibilité dans la base de données
            $is_available = true;
            
            if (class_exists('RestaurantBooking_Calendar')) {
                $is_available = RestaurantBooking_Calendar::is_date_available($date, $service_type);
            } else {
                // Fallback : vérifier directement dans la base de données
                global $wpdb;
                $table_name = $wpdb->prefix . 'restaurant_availability';
                
                $existing_booking = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_name WHERE date = %s AND service_type = %s AND is_available = 0",
                    $date,
                    $service_type
                ));
                
                $is_available = ($existing_booking == 0);
            }

            wp_send_json_success(array(
                'available' => $is_available,
                'date' => $date,
                'message' => $is_available ? 
                    __('Date disponible', 'restaurant-booking') : 
                    __('Date non disponible', 'restaurant-booking')
            ));

        } catch (Exception $e) {
            RestaurantBooking_Logger::error('Erreur lors de la vérification de disponibilité', array(
                'error' => $e->getMessage(),
                'date' => $_POST['date'] ?? '',
                'service_type' => $_POST['service_type'] ?? ''
            ));

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
        if (class_exists('RestaurantBooking_Quote_Calculator_V2')) {
            $calculator = new RestaurantBooking_Quote_Calculator_V2();
            $price_data = $calculator->calculate_total($service_type, $form_data);
        } else {
            // Fallback : calcul simple
            $base_price = $service_type === 'restaurant' ? 300 : 350;
            $price_data = array(
                'base_price' => $base_price,
                'supplements_total' => 0,
                'products_total' => 0,
                'total_price' => $base_price
            );
        }

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

        // Créer le devis
        if (class_exists('RestaurantBooking_Quote')) {
            return RestaurantBooking_Quote::create($quote_data);
        } else {
            // Fallback : insertion directe en base de données
            global $wpdb;
            $table_name = $wpdb->prefix . 'restaurant_quotes';
            
            $insert_data = array(
                'service_type' => $service_type,
                'event_date' => $form_data['event_date'],
                'event_duration' => (int) $form_data['event_duration'],
                'guest_count' => (int) $form_data['guest_count'],
                'postal_code' => $form_data['postal_code'] ?? null,
                'customer_firstname' => $form_data['customer_firstname'],
                'customer_lastname' => $form_data['customer_lastname'],
                'customer_email' => $form_data['customer_email'],
                'customer_phone' => $form_data['customer_phone'],
                'customer_message' => $form_data['customer_message'] ?? '',
                'selected_products' => json_encode($form_data['selected_products'] ?? array()),
                'price_breakdown' => json_encode($price_data),
                'base_price' => $price_data['base_price'],
                'total_price' => $price_data['total_price'],
                'status' => 'draft',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            );
            
            $result = $wpdb->insert($table_name, $insert_data);
            
            if ($result === false) {
                throw new Exception(__('Erreur lors de la création du devis', 'restaurant-booking'));
            }
            
            return $wpdb->insert_id;
        }
    }

    private function send_quote_email($quote_id)
    {
        try {
            // Obtenir le devis
            $quote = null;
            
            if (class_exists('RestaurantBooking_Quote')) {
                $quote = RestaurantBooking_Quote::get($quote_id);
            } else {
                // Fallback : récupérer directement depuis la base de données
                global $wpdb;
                $table_name = $wpdb->prefix . 'restaurant_quotes';
                $quote = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $table_name WHERE id = %d",
                    $quote_id
                ));
            }
            
            if (!$quote) {
                throw new Exception(__('Devis introuvable', 'restaurant-booking'));
            }

            // Envoyer l'email avec PDF
            if (class_exists('RestaurantBooking_Email')) {
                $email_handler = RestaurantBooking_Email::get_instance();
                return $email_handler->send_quote_email($quote);
            } else {
                // Fallback : envoi d'email simple
                $to = is_object($quote) ? $quote->customer_email : $quote['customer_email'];
                $subject = __('Votre devis Restaurant Block', 'restaurant-booking');
                $message = sprintf(
                    __('Bonjour,\n\nVotre devis n°%s a été généré avec succès.\n\nCordialement,\nL\'équipe Restaurant Block', 'restaurant-booking'),
                    $quote_id
                );
                
                $headers = array('Content-Type: text/html; charset=UTF-8');
                
                return wp_mail($to, $subject, nl2br($message), $headers);
            }
            
        } catch (Exception $e) {
            RestaurantBooking_Logger::error('Erreur lors de l\'envoi de l\'email de devis', array(
                'error' => $e->getMessage(),
                'quote_id' => $quote_id
            ));
            
            // Ne pas faire échouer la création du devis si l'email échoue
            return false;
        }
    }

    private function validate_step($service_type, $step, $step_data)
    {
        // Implémentation de la validation par étape
        return array('valid' => true);
    }

    /**
     * Générer les produits Mini Boss
     */
    private function generate_mini_boss_products()
    {
        // Récupérer les produits Mini Boss depuis la base de données
        global $wpdb;
        
        $category_table = $wpdb->prefix . 'restaurant_categories';
        $products_table = $wpdb->prefix . 'restaurant_products';
        
        // Récupérer la catégorie Mini Boss
        $mini_boss_category = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $category_table WHERE slug = 'mini-boss' AND is_active = 1"
        ));
        
        if (!$mini_boss_category) {
            return '<p>Aucun menu enfant disponible pour le moment.</p>';
        }
        
        // Récupérer les produits
        $products = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $products_table WHERE category_id = %d AND is_active = 1 ORDER BY display_order ASC",
            $mini_boss_category->id
        ));
        
        if (empty($products)) {
            return '<p>Aucun menu enfant disponible pour le moment.</p>';
        }
        
        $html = '<div class="rb-products-grid">';
        
        foreach ($products as $product) {
            $html .= '<div class="rb-product-card" data-product-id="' . $product->id . '">';
            $html .= '<div class="rb-product-content">';
            $html .= '<h5 class="rb-product-title">' . esc_html($product->name) . '</h5>';
            
            if (!empty($product->description)) {
                $html .= '<p class="rb-product-description">' . esc_html($product->description) . '</p>';
            }
            
            $html .= '<div class="rb-product-price">' . number_format($product->price, 2, ',', ' ') . ' €</div>';
            $html .= '</div>';
            
            $html .= '<div class="rb-product-footer">';
            $html .= '<div class="rb-quantity-selector">';
            $html .= '<button type="button" class="rb-qty-btn rb-qty-minus" data-target="mini_boss_' . $product->id . '">-</button>';
            $html .= '<input type="number" class="rb-qty-input rb-product-quantity" id="mini_boss_' . $product->id . '" name="products[mini_boss][' . $product->id . ']" value="0" min="0" max="20" data-product-id="' . $product->id . '" data-category="mini-boss">';
            $html .= '<button type="button" class="rb-qty-btn rb-qty-plus" data-target="mini_boss_' . $product->id . '">+</button>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Générer les produits Accompagnements
     */
    private function generate_accompaniment_products()
    {
        // Récupérer les accompagnements depuis la base de données
        global $wpdb;
        
        $category_table = $wpdb->prefix . 'restaurant_categories';
        $products_table = $wpdb->prefix . 'restaurant_products';
        
        // Récupérer la catégorie Accompagnements
        $accompaniment_category = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $category_table WHERE slug = 'accompaniments' AND is_active = 1"
        ));
        
        if (!$accompaniment_category) {
            return '<p>Aucun accompagnement disponible pour le moment.</p>';
        }
        
        // Récupérer les produits
        $products = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $products_table WHERE category_id = %d AND is_active = 1 ORDER BY display_order ASC",
            $accompaniment_category->id
        ));
        
        if (empty($products)) {
            return '<p>Aucun accompagnement disponible pour le moment.</p>';
        }
        
        $html = '<div class="rb-products-grid">';
        
        foreach ($products as $product) {
            $html .= '<div class="rb-product-card" data-product-id="' . $product->id . '">';
            $html .= '<div class="rb-product-content">';
            $html .= '<h5 class="rb-product-title">' . esc_html($product->name) . '</h5>';
            
            if (!empty($product->description)) {
                $html .= '<p class="rb-product-description">' . esc_html($product->description) . '</p>';
            }
            
            $html .= '<div class="rb-product-price">' . number_format($product->price, 2, ',', ' ') . ' €</div>';
            $html .= '</div>';
            
            $html .= '<div class="rb-product-footer">';
            $html .= '<div class="rb-quantity-selector">';
            $html .= '<button type="button" class="rb-qty-btn rb-qty-minus" data-target="accompaniment_' . $product->id . '">-</button>';
            $html .= '<input type="number" class="rb-qty-input rb-product-quantity" id="accompaniment_' . $product->id . '" name="products[accompaniments][' . $product->id . ']" value="0" min="0" max="50" data-product-id="' . $product->id . '" data-category="accompaniments">';
            $html .= '<button type="button" class="rb-qty-btn rb-qty-plus" data-target="accompaniment_' . $product->id . '">+</button>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Récupérer les produits signature (DOG/CROQ)
     */
    private function get_signature_products($signature_type, $service_type)
    {
        global $wpdb;
        
        $category_table = $wpdb->prefix . 'restaurant_categories';
        $products_table = $wpdb->prefix . 'restaurant_products';
        
        // Mapper le type de signature vers le slug de catégorie
        $category_slug = strtolower($signature_type);
        
        // Récupérer la catégorie
        $category = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $category_table WHERE slug = %s AND (service_type = %s OR service_type = 'both') AND is_active = 1",
            $category_slug,
            $service_type
        ));
        
        if (!$category) {
            return array();
        }
        
        // Récupérer les produits avec leurs suppléments
        $products = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, ps.supplement_options 
             FROM $products_table p
             LEFT JOIN {$wpdb->prefix}restaurant_product_supplements ps ON p.id = ps.product_id
             WHERE p.category_id = %d AND p.is_active = 1 
             ORDER BY p.display_order ASC",
            $category->id
        ));
        
        // Formater les produits avec leurs suppléments
        $formatted_products = array();
        foreach ($products as $product) {
            if (!isset($formatted_products[$product->id])) {
                $formatted_products[$product->id] = array(
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'price' => $product->price,
                    'category_id' => $product->category_id,
                    'supplements' => array()
                );
            }
            
            // Ajouter les suppléments si disponibles
            if (!empty($product->supplement_options)) {
                $supplements = json_decode($product->supplement_options, true);
                if (is_array($supplements)) {
                    $formatted_products[$product->id]['supplements'] = $supplements;
                }
            }
        }
        
        return array_values($formatted_products);
    }

    /**
     * Fallback pour récupérer les produits par catégorie
     */
    private function get_products_by_category_fallback($category_type, $service_type)
    {
        global $wpdb;
        
        $category_table = $wpdb->prefix . 'restaurant_categories';
        $products_table = $wpdb->prefix . 'restaurant_products';
        
        $category = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $category_table WHERE slug = %s AND (service_type = %s OR service_type = 'both') AND is_active = 1",
            $category_type,
            $service_type
        ));
        
        if (!$category) {
            return array();
        }
        
        $products = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $products_table WHERE category_id = %d AND is_active = 1 ORDER BY display_order ASC",
            $category->id
        ));
        
        return $products ?: array();
    }

    /**
     * Générer les niveaux de buffets (système à 3 niveaux)
     */
    private function generate_buffet_levels($type, $guest_count)
    {
        // Récupérer les produits de buffet depuis la base de données
        global $wpdb;
        
        $category_table = $wpdb->prefix . 'restaurant_categories';
        $products_table = $wpdb->prefix . 'restaurant_products';
        
        // Récupérer la catégorie buffet
        $buffet_category = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $category_table WHERE slug = 'buffet-%s' AND is_active = 1",
            $type
        ));
        
        if (!$buffet_category) {
            return '<p>Aucun produit de buffet ' . $type . ' disponible pour le moment.</p>';
        }
        
        // Récupérer les produits
        $products = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $products_table WHERE category_id = %d AND is_active = 1 ORDER BY display_order ASC",
            $buffet_category->id
        ));
        
        if (empty($products)) {
            return '<p>Aucun produit de buffet ' . $type . ' disponible pour le moment.</p>';
        }
        
        $html = '<div class="rb-buffet-level-selector">';
        $html .= '<p class="rb-level-description">Choisissez votre niveau de buffet (1 seul niveau par type de buffet) :</p>';
        
        // Niveau 1
        $html .= '<div class="rb-buffet-level" data-level="1">';
        $html .= '<div class="rb-level-header">';
        $html .= '<label class="rb-level-option">';
        $html .= '<input type="radio" name="buffet_' . $type . '_level" value="1"> ';
        $html .= '<span class="rb-level-title">Niveau 1 - Basique (8,00 € / pers.)</span>';
        $html .= '</label>';
        $html .= '</div>';
        $html .= '<div class="rb-level-content" style="display: none;">';
        $html .= '<div class="rb-level-products">';
        
        // Filtrer les produits du niveau 1 (les moins chers)
        $level1_products = array_slice($products, 0, 3);
        foreach ($level1_products as $product) {
            $html .= '<div class="rb-buffet-product">';
            $html .= '<span class="rb-product-name">' . esc_html($product->name) . '</span>';
            $html .= '<span class="rb-product-included">✓ Inclus</span>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        $html .= '<div class="rb-level-total">Total : ' . number_format(8 * $guest_count, 2, ',', ' ') . ' € (' . $guest_count . ' pers.)</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        // Niveau 2
        $html .= '<div class="rb-buffet-level" data-level="2">';
        $html .= '<div class="rb-level-header">';
        $html .= '<label class="rb-level-option">';
        $html .= '<input type="radio" name="buffet_' . $type . '_level" value="2"> ';
        $html .= '<span class="rb-level-title">Niveau 2 - Intermédiaire (12,00 € / pers.)</span>';
        $html .= '</label>';
        $html .= '</div>';
        $html .= '<div class="rb-level-content" style="display: none;">';
        $html .= '<div class="rb-level-products">';
        
        // Inclure les produits du niveau 1 + quelques autres
        $level2_products = array_slice($products, 0, 5);
        foreach ($level2_products as $product) {
            $html .= '<div class="rb-buffet-product">';
            $html .= '<span class="rb-product-name">' . esc_html($product->name) . '</span>';
            $html .= '<span class="rb-product-included">✓ Inclus</span>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        $html .= '<div class="rb-level-total">Total : ' . number_format(12 * $guest_count, 2, ',', ' ') . ' € (' . $guest_count . ' pers.)</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        // Niveau 3
        $html .= '<div class="rb-buffet-level" data-level="3">';
        $html .= '<div class="rb-level-header">';
        $html .= '<label class="rb-level-option">';
        $html .= '<input type="radio" name="buffet_' . $type . '_level" value="3"> ';
        $html .= '<span class="rb-level-title">Niveau 3 - Premium (18,00 € / pers.)</span>';
        $html .= '</label>';
        $html .= '</div>';
        $html .= '<div class="rb-level-content" style="display: none;">';
        $html .= '<div class="rb-level-products">';
        
        // Tous les produits disponibles
        foreach ($products as $product) {
            $html .= '<div class="rb-buffet-product">';
            $html .= '<span class="rb-product-name">' . esc_html($product->name) . '</span>';
            $html .= '<span class="rb-product-included">✓ Inclus</span>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        $html .= '<div class="rb-level-total">Total : ' . number_format(18 * $guest_count, 2, ',', ' ') . ' € (' . $guest_count . ' pers.)</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Nettoyer et valider les données du formulaire
     */
    private function clean_form_data($form_data)
    {
        $cleaned = array();
        
        // Champs de base
        if (isset($form_data['event_date'])) {
            $cleaned['event_date'] = sanitize_text_field($form_data['event_date']);
        }
        if (isset($form_data['event_duration'])) {
            $cleaned['event_duration'] = (int) $form_data['event_duration'];
        }
        if (isset($form_data['guest_count'])) {
            $cleaned['guest_count'] = (int) $form_data['guest_count'];
        }
        if (isset($form_data['postal_code'])) {
            $cleaned['postal_code'] = sanitize_text_field($form_data['postal_code']);
        }
        
        // Produits sélectionnés
        if (isset($form_data['selected_products']) && is_array($form_data['selected_products'])) {
            $cleaned['selected_products'] = array();
            foreach ($form_data['selected_products'] as $product_id => $product_data) {
                if (is_array($product_data) && isset($product_data['quantity']) && $product_data['quantity'] > 0) {
                    $cleaned['selected_products'][(int) $product_id] = array(
                        'quantity' => (int) $product_data['quantity'],
                        'product_id' => (int) $product_id
                    );
                }
            }
        }
        
        // Suppléments sélectionnés
        if (isset($form_data['selected_supplements']) && is_array($form_data['selected_supplements'])) {
            $cleaned['selected_supplements'] = array();
            foreach ($form_data['selected_supplements'] as $product_id => $supplements) {
                if (is_array($supplements)) {
                    $cleaned['selected_supplements'][(int) $product_id] = array();
                    foreach ($supplements as $supplement_id => $quantity) {
                        if ($quantity > 0) {
                            $cleaned['selected_supplements'][(int) $product_id][(int) $supplement_id] = (int) $quantity;
                        }
                    }
                }
            }
        }
        
        // Options (remorque)
        if (isset($form_data['option_tireuse'])) {
            $cleaned['option_tireuse'] = (bool) $form_data['option_tireuse'];
        }
        if (isset($form_data['option_games'])) {
            $cleaned['option_games'] = (bool) $form_data['option_games'];
        }
        
        return $cleaned;
    }

    /**
     * Calcul de prix simple (fallback)
     */
    private function calculate_simple_price($service_type, $form_data)
    {
        $calculation = array(
            'service_type' => $service_type,
            'base_price' => $service_type === 'restaurant' ? 300 : 350,
            'duration_supplement' => 0,
            'guest_supplement' => 0,
            'distance_supplement' => 0,
            'products_total' => 0,
            'supplements_total' => 0,
            'options_total' => 0,
            'total_price' => 0,
            'breakdown' => array()
        );
        
        // Supplément durée
        if (isset($form_data['event_duration']) && $form_data['event_duration'] > 2) {
            $calculation['duration_supplement'] = ($form_data['event_duration'] - 2) * 50;
        }
        
        // Supplément convives (remorque uniquement)
        if ($service_type === 'remorque' && isset($form_data['guest_count']) && $form_data['guest_count'] > 50) {
            $calculation['guest_supplement'] = 150;
        }
        
        // Options remorque
        if ($service_type === 'remorque') {
            if (!empty($form_data['option_tireuse'])) {
                $calculation['options_total'] += 50;
            }
            if (!empty($form_data['option_games'])) {
                $calculation['options_total'] += 70;
            }
        }
        
        // Calcul du total
        $calculation['total_price'] = $calculation['base_price'] 
            + $calculation['duration_supplement'] 
            + $calculation['guest_supplement']
            + $calculation['distance_supplement']
            + $calculation['products_total']
            + $calculation['supplements_total']
            + $calculation['options_total'];
            
        return $calculation;
    }
}
