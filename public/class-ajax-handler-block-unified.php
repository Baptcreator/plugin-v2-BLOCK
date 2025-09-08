<?php
/**
 * Gestionnaire AJAX - Formulaire Block Unifié V2
 * Connexion complète aux Options Unifiées
 * Structure exacte selon cahier des charges
 * 
 * @package RestaurantBooking
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Ajax_Handler_Block_Unified
{
    /**
     * Instance unique
     */
    private static $instance = null;

    /**
     * Options unifiées
     */
    private $options = null;

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
        $this->load_options();
        $this->init_hooks();
    }

    /**
     * Charger les options unifiées
     */
    private function load_options()
    {
        if (class_exists('RestaurantBooking_Options_Unified_Admin')) {
            $instance = new RestaurantBooking_Options_Unified_Admin();
            $this->options = $instance->get_options();
        } else {
            $this->options = $this->get_default_options();
        }
    }

    /**
     * Options par défaut en cas de fallback
     */
    private function get_default_options()
    {
        return [
            // Restaurant
            'restaurant_min_guests' => 10,
            'restaurant_max_guests' => 30,
            'restaurant_min_duration' => 2,
            'restaurant_max_duration_included' => 2,
            'restaurant_extra_hour_price' => 50,
            
            // Remorque
            'remorque_min_guests' => 20,
            'remorque_max_guests' => 100,
            'remorque_staff_threshold' => 50,
            'remorque_staff_supplement' => 150,
            'remorque_min_duration' => 2,
            'remorque_max_duration' => 5,
            'remorque_extra_hour_price' => 50,
            
            // Distance
            'free_radius_km' => 30,
            'price_30_50km' => 20,
            'price_50_100km' => 70,
            'price_100_150km' => 120,
            'max_distance_km' => 150,
            
            // Options
            'tireuse_price' => 50,
            'games_price' => 70,
            
            // Produits
            'accompaniment_base_price' => 4,
            'signature_dish_min_per_person' => 1,
            'accompaniment_min_per_person' => 1,
            'buffet_sale_min_per_person' => 1,
            'buffet_sale_min_recipes' => 2,
            'buffet_sucre_min_per_person' => 1,
            'buffet_sucre_min_dishes' => 1,
        ];
    }

    /**
     * Initialiser les hooks AJAX
     */
    private function init_hooks()
    {
        // Chargement des étapes
        add_action('wp_ajax_restaurant_plugin_load_step', [$this, 'load_step']);
        add_action('wp_ajax_nopriv_restaurant_plugin_load_step', [$this, 'load_step']);

        // Calcul de prix temps réel
        add_action('wp_ajax_restaurant_plugin_calculate_price', [$this, 'calculate_price']);
        add_action('wp_ajax_nopriv_restaurant_plugin_calculate_price', [$this, 'calculate_price']);

        // Vérification de date
        add_action('wp_ajax_restaurant_plugin_check_date', [$this, 'check_date_availability']);
        add_action('wp_ajax_nopriv_restaurant_plugin_check_date', [$this, 'check_date_availability']);

        // Calcul de distance
        add_action('wp_ajax_restaurant_plugin_calculate_distance', [$this, 'calculate_distance']);
        add_action('wp_ajax_nopriv_restaurant_plugin_calculate_distance', [$this, 'calculate_distance']);

        // Produits signature
        add_action('wp_ajax_restaurant_plugin_get_signature_products', [$this, 'get_signature_products']);
        add_action('wp_ajax_nopriv_restaurant_plugin_get_signature_products', [$this, 'get_signature_products']);

        // Soumission finale
        add_action('wp_ajax_restaurant_plugin_submit_quote', [$this, 'submit_quote']);
        add_action('wp_ajax_nopriv_restaurant_plugin_submit_quote', [$this, 'submit_quote']);
    }

    /**
     * Charger une étape selon le cahier des charges
     */
    public function load_step()
    {
        try {
            // Vérification de sécurité
            if (!wp_verify_nonce($_POST['nonce'], 'restaurant_plugin_form')) {
                throw new Exception(__('Erreur de sécurité', 'restaurant-booking'));
            }

            $service_type = sanitize_text_field($_POST['service_type']);
            $step = (int) $_POST['step'];
            $form_data = $_POST['form_data'] ?? [];

            // Valider les paramètres
            if (!in_array($service_type, ['restaurant', 'remorque'])) {
                throw new Exception(__('Service invalide', 'restaurant-booking'));
            }

            if ($step < 1 || $step > 7) {
                throw new Exception(__('Numéro d\'étape invalide', 'restaurant-booking'));
            }

            // Générer le contenu de l'étape
            $step_data = $this->generate_step_content($service_type, $step, $form_data);

            wp_send_json_success($step_data);

        } catch (Exception $e) {
            RestaurantBooking_Logger::error('Erreur chargement étape', [
                'error' => $e->getMessage(),
                'service_type' => $_POST['service_type'] ?? '',
                'step' => $_POST['step'] ?? ''
            ]);

            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Générer le contenu d'une étape selon le cahier des charges
     */
    private function generate_step_content($service_type, $step, $form_data)
    {
        switch ($step) {
            case 1:
                return $this->generate_step_1_content($service_type);
            case 2:
                return $this->generate_step_2_content($service_type, $form_data);
            case 3:
                return $this->generate_step_3_content($service_type, $form_data);
            case 4:
                return $this->generate_step_4_content($service_type, $form_data);
            case 5:
                return $this->generate_step_5_content($service_type, $form_data);
            case 6:
                return $this->generate_step_6_content($service_type, $form_data);
            case 7:
                return $this->generate_step_7_content($service_type, $form_data);
            default:
                throw new Exception(__('Étape non supportée', 'restaurant-booking'));
        }
    }

    /**
     * Étape 1: Pourquoi privatiser (selon cahier des charges)
     */
    private function generate_step_1_content($service_type)
    {
        $title = ($service_type === 'restaurant') 
            ? __('Pourquoi privatiser notre restaurant ?', 'restaurant-booking')
            : __('Pourquoi privatiser notre remorque Block ?', 'restaurant-booking');

        $process_steps = ($service_type === 'restaurant') ? [
            '1. Forfait de base',
            '2. Choix du formule repas (personnalisable)',
            '3. Choix des boissons (optionnel)',
            '4. Coordonnées / Contact'
        ] : [
            '1. Forfait de base',
            '2. Choix du formule repas (personnalisable)',
            '3. Choix des boissons (optionnel)',
            '4. Choix des options (optionnel)',
            '5. Coordonnées/Contact'
        ];

        $content = '<div class="restaurant-plugin-intro-section">';
        $content .= '<div class="restaurant-plugin-card">';
        $content .= '<h3>' . __('Comment ça fonctionne ?', 'restaurant-booking') . '</h3>';
        $content .= '<ul class="restaurant-plugin-process-list">';
        
        foreach ($process_steps as $process_step) {
            $content .= '<li>' . esc_html($process_step) . '</li>';
        }
        
        $content .= '</ul>';
        $content .= '<div class="restaurant-plugin-text-center restaurant-plugin-mt-4">';
        $content .= '<button type="button" class="restaurant-plugin-btn-accent start-quote-button">';
        $content .= __('COMMENCER MON DEVIS', 'restaurant-booking');
        $content .= '</button>';
        $content .= '</div>';
        $content .= '</div>';
        $content .= '</div>';

        return [
            'step_number' => 1,
            'service_type' => $service_type,
            'title' => $title,
            'content' => $content
        ];
    }

    /**
     * Étape 2: Forfait de base (selon cahier des charges)
     */
    private function generate_step_2_content($service_type, $form_data)
    {
        $title = __('FORFAIT DE BASE', 'restaurant-booking');

        $min_guests = $this->options[$service_type . '_min_guests'];
        $max_guests = $this->options[$service_type . '_max_guests'];
        $min_duration = $this->options[$service_type . '_min_duration'];
        $max_duration = ($service_type === 'restaurant') ? 4 : 5;
        $extra_hour_price = $this->options[$service_type . '_extra_hour_price'];

        $content = '<div class="restaurant-plugin-forfait-section">';

        // Formulaire de base
        $content .= '<div class="restaurant-plugin-form-row">';
        $content .= '<div class="restaurant-plugin-form-group">';
        $content .= '<label for="event_date" class="required">' . __('Date souhaitée événement', 'restaurant-booking') . '</label>';
        $content .= '<input type="date" id="event_date" name="event_date" required min="' . date('Y-m-d', strtotime('+1 day')) . '">';
        $content .= '</div>';
        $content .= '</div>';

        $content .= '<div class="restaurant-plugin-form-row">';
        $content .= '<div class="restaurant-plugin-form-group">';
        $content .= '<label for="guest_count" class="required">' . __('Nombre de convives', 'restaurant-booking') . '</label>';
        $content .= '<input type="number" id="guest_count" name="guest_count" required min="' . $min_guests . '" max="' . $max_guests . '" placeholder="' . $min_guests . '">';
        $content .= '<small class="restaurant-plugin-small-text">' . sprintf(__('Entre %d et %d personnes', 'restaurant-booking'), $min_guests, $max_guests) . '</small>';
        $content .= '</div>';
        $content .= '</div>';

        $content .= '<div class="restaurant-plugin-form-row">';
        $content .= '<div class="restaurant-plugin-form-group">';
        $content .= '<label for="event_duration" class="required">' . __('Durée souhaitée événement', 'restaurant-booking') . '</label>';
        $content .= '<select id="event_duration" name="event_duration" required>';
        for ($h = $min_duration; $h <= $max_duration; $h++) {
            $content .= '<option value="' . $h . '">' . $h . 'H</option>';
        }
        $content .= '</select>';
        $content .= '<div class="duration-supplement-text restaurant-plugin-small-text restaurant-plugin-mt-1" style="display: none;"></div>';
        $content .= '</div>';
        $content .= '</div>';

        // Code postal pour remorque
        if ($service_type === 'remorque') {
            $content .= '<div class="restaurant-plugin-form-row">';
            $content .= '<div class="restaurant-plugin-form-group">';
            $content .= '<label for="postal_code" class="required">' . __('Code postal événement', 'restaurant-booking') . '</label>';
            $content .= '<input type="text" id="postal_code" name="postal_code" required pattern="\\d{5}" placeholder="67000" maxlength="5">';
            $content .= '<small class="restaurant-plugin-small-text">' . sprintf(__('Rayon maximum %d km', 'restaurant-booking'), $this->options['max_distance_km']) . '</small>';
            $content .= '<div class="delivery-supplement-text restaurant-plugin-small-text restaurant-plugin-mt-1" style="display: none;"></div>';
            $content .= '</div>';
            $content .= '</div>';
        }

        // Card forfait
        $forfait_title = ($service_type === 'restaurant') 
            ? __('FORFAIT DE BASE PRIVATISATION RESTO', 'restaurant-booking')
            : __('FORFAIT DE BASE PRIVATISATION REMORQUE BLOCK', 'restaurant-booking');

        $forfait_items = ($service_type === 'restaurant') ? [
            'Mise à disposition des murs de Block',
            '[DURATION]H de privatisation (service inclus, hors installation et nettoyage)',
            'Notre équipe salle + cuisine assurant la prestation',
            'Présentation + mise en place buffets, selon vos choix',
            'Mise à disposition vaisselle + verrerie',
            'Entretien + nettoyage'
        ] : [
            '[DURATION]H de privatisation (service inclus, hors installation et nettoyage)',
            'Notre équipe salle + cuisine assurant la prestation',
            'Déplacement et installation de la remorque BLOCK (aller et retour)',
            'Présentation + mise en place buffets, selon vos choix',
            'La fourniture de vaisselle jetable recyclable',
            'La fourniture de verrerie (en cas d\'ajout de boisson)'
        ];

        $content .= '<div class="restaurant-plugin-card restaurant-plugin-mt-4">';
        $content .= '<h3>' . $forfait_title . '</h3>';
        $content .= '<p><strong>' . __('Ce forfait comprend :', 'restaurant-booking') . '</strong></p>';
        $content .= '<ul>';
        foreach ($forfait_items as $item) {
            $content .= '<li>' . str_replace('[DURATION]', '<span class="dynamic-duration">2</span>', esc_html($item)) . '</li>';
        }
        $content .= '</ul>';
        
        if ($service_type === 'remorque') {
            $staff_threshold = $this->options['remorque_staff_threshold'];
            $staff_supplement = $this->options['remorque_staff_supplement'];
            $content .= '<div class="restaurant-plugin-warning restaurant-plugin-mt-3">';
            $content .= '<small><strong>' . __('Attention :', 'restaurant-booking') . '</strong> ';
            $content .= sprintf(__('Au delà de %d personnes, un forfait de +%d€ s\'applique (frais de personnel/matériel/vaisselle jetable)', 'restaurant-booking'), $staff_threshold, $staff_supplement);
            $content .= '</small>';
            $content .= '</div>';
        }
        
        $content .= '</div>';
        $content .= '</div>';

        return [
            'step_number' => 2,
            'service_type' => $service_type,
            'title' => $title,
            'content' => $content
        ];
    }

    /**
     * Étape 3: Choix des formules repas (selon cahier des charges)
     */
    private function generate_step_3_content($service_type, $form_data)
    {
        $title = __('CHOIX DES FORMULES REPAS', 'restaurant-booking');
        $guest_count = isset($form_data['guest_count']) ? (int) $form_data['guest_count'] : $this->options[$service_type . '_min_guests'];

        $content = '<div class="restaurant-plugin-products-section">';

        // Information
        $content .= '<div class="restaurant-plugin-message restaurant-plugin-message-info">';
        $content .= '<p><strong>ℹ️ ' . __('Information importante :', 'restaurant-booking') . '</strong></p>';
        $content .= '<p>' . sprintf(__('Sélection obligatoire pour %d convives. Les quantités minimales sont calculées automatiquement.', 'restaurant-booking'), $guest_count) . '</p>';
        $content .= '</div>';

        // 1. Plat signature DOG/CROQ
        $content .= '<div class="restaurant-plugin-card" data-category="signature">';
        $content .= '<h3>🍽️ ' . __('Choix du plat signature', 'restaurant-booking') . '</h3>';
        $content .= '<p class="restaurant-plugin-small-text"><em>' . __('Minimum 1 plat par personne - Choix obligatoire', 'restaurant-booking') . '</em></p>';
        
        $content .= '<div class="restaurant-plugin-signature-selector restaurant-plugin-mt-3">';
        $content .= '<label class="restaurant-plugin-radio-option">';
        $content .= '<input type="radio" name="signature_type" value="DOG" required>';
        $content .= '<span>🌭 ' . __('DOG - Nos hot-dogs signature', 'restaurant-booking') . '</span>';
        $content .= '</label>';
        $content .= '<label class="restaurant-plugin-radio-option">';
        $content .= '<input type="radio" name="signature_type" value="CROQ" required>';
        $content .= '<span>🥪 ' . __('CROQ - Nos croque-monsieurs', 'restaurant-booking') . '</span>';
        $content .= '</label>';
        $content .= '</div>';
        
        $content .= '<div class="signature-products-container restaurant-plugin-mt-3" style="display: none;"></div>';
        $content .= '</div>';

        // 2. Menu Mini Boss
        $content .= '<div class="restaurant-plugin-card" data-category="mini-boss">';
        $content .= '<h3>👶 ' . __('LE MENU DES MINI BOSS', 'restaurant-booking') . '</h3>';
        $content .= '<p class="restaurant-plugin-small-text"><em>' . __('Menu enfant - Optionnel', 'restaurant-booking') . '</em></p>';
        $content .= '<div class="mini-boss-products-container restaurant-plugin-mt-3">';
        $content .= $this->generate_mini_boss_products();
        $content .= '</div>';
        $content .= '</div>';

        // 3. Accompagnements
        $content .= '<div class="restaurant-plugin-card" data-category="accompaniments">';
        $content .= '<h3>🥗 ' . __('Choix des accompagnements', 'restaurant-booking') . '</h3>';
        $content .= '<p class="restaurant-plugin-small-text"><em>' . sprintf(__('Minimum 1 par personne - %s€ par accompagnement', 'restaurant-booking'), number_format($this->options['accompaniment_base_price'], 2)) . '</em></p>';
        $content .= '<div class="accompaniment-products-container restaurant-plugin-mt-3">';
        $content .= $this->generate_accompaniment_products();
        $content .= '</div>';
        $content .= '</div>';

        $content .= '</div>';

        return [
            'step_number' => 3,
            'service_type' => $service_type,
            'title' => $title,
            'content' => $content
        ];
    }

    /**
     * Étape 4: Choix des buffets (selon cahier des charges)
     */
    private function generate_step_4_content($service_type, $form_data)
    {
        $title = __('CHOIX DU/DES BUFFET(S)', 'restaurant-booking') . ' <small>(' . __('Optionnel', 'restaurant-booking') . ')</small>';

        $content = '<div class="restaurant-plugin-buffets-section">';

        // Information
        $content .= '<div class="restaurant-plugin-message restaurant-plugin-message-warning">';
        $content .= '<p><strong>🍽️ ' . __('Information :', 'restaurant-booking') . '</strong></p>';
        $content .= '<p>' . __('Les buffets sont optionnels et viennent compléter vos plats signature. Vous ne pouvez choisir qu\'un seul type de buffet.', 'restaurant-booking') . '</p>';
        $content .= '</div>';

        // Sélecteur de type de buffet
        $content .= '<div class="restaurant-plugin-card">';
        $content .= '<h3>' . __('Choisissez votre type de buffet', 'restaurant-booking') . '</h3>';
        
        $content .= '<div class="restaurant-plugin-buffet-selector restaurant-plugin-mt-3">';
        $content .= '<label class="restaurant-plugin-radio-option">';
        $content .= '<input type="radio" name="buffet_type" value="none" checked>';
        $content .= '<span>🚫 ' . __('Aucun buffet', 'restaurant-booking') . '</span>';
        $content .= '</label>';
        $content .= '<label class="restaurant-plugin-radio-option">';
        $content .= '<input type="radio" name="buffet_type" value="sale">';
        $content .= '<span>🥗 ' . __('Buffet salé uniquement', 'restaurant-booking') . '</span>';
        $content .= '</label>';
        $content .= '<label class="restaurant-plugin-radio-option">';
        $content .= '<input type="radio" name="buffet_type" value="sucre">';
        $content .= '<span>🍰 ' . __('Buffet sucré uniquement', 'restaurant-booking') . '</span>';
        $content .= '</label>';
        $content .= '<label class="restaurant-plugin-radio-option">';
        $content .= '<input type="radio" name="buffet_type" value="both">';
        $content .= '<span>🍽️ ' . __('Buffets salés ET sucrés', 'restaurant-booking') . '</span>';
        $content .= '</label>';
        $content .= '</div>';
        $content .= '</div>';

        // Conteneurs pour les buffets (masqués initialement)
        $content .= '<div class="buffet-sale-container restaurant-plugin-card" style="display: none;">';
        $content .= '<h3>🥗 ' . __('Buffet Salé', 'restaurant-booking') . '</h3>';
        $content .= '<p class="restaurant-plugin-small-text"><em>' . $this->options['buffet_sale_text'] . '</em></p>';
        $content .= '<div class="buffet-sale-products"></div>';
        $content .= '</div>';

        $content .= '<div class="buffet-sucre-container restaurant-plugin-card" style="display: none;">';
        $content .= '<h3>🍰 ' . __('Buffet Sucré', 'restaurant-booking') . '</h3>';
        $content .= '<p class="restaurant-plugin-small-text"><em>' . $this->options['buffet_sucre_text'] . '</em></p>';
        $content .= '<div class="buffet-sucre-products"></div>';
        $content .= '</div>';

        $content .= '</div>';

        return [
            'step_number' => 4,
            'service_type' => $service_type,
            'title' => $title,
            'content' => $content
        ];
    }

    /**
     * Étape 5: Choix des boissons (selon cahier des charges)
     */
    private function generate_step_5_content($service_type, $form_data)
    {
        $title = __('CHOIX DES BOISSONS', 'restaurant-booking') . ' <small>(' . __('Optionnel', 'restaurant-booking') . ')</small>';

        $content = '<div class="restaurant-plugin-beverages-section">';

        // Information
        $content .= '<div class="restaurant-plugin-message restaurant-plugin-message-info">';
        $content .= '<p><strong>🍷 ' . __('Information :', 'restaurant-booking') . '</strong></p>';
        $content .= '<p>' . __('Les boissons sont optionnelles. Sections dépliables pour explorer nos différentes gammes.', 'restaurant-booking') . '</p>';
        $content .= '</div>';

        // Catégories de boissons
        $categories = [
            'soft' => ['label' => '🥤 SOFTS', 'description' => 'Boissons sans alcool, jus, sodas'],
            'vin_blanc' => ['label' => '🍾 VINS BLANCS', 'description' => 'Notre sélection de vins blancs'],
            'vin_rouge' => ['label' => '🍷 VINS ROUGES', 'description' => 'Notre sélection de vins rouges'],
            'biere' => ['label' => '🍺 BIÈRES BOUTEILLE', 'description' => 'Bières en bouteille et canettes']
        ];

        // Ajouter les fûts pour le restaurant uniquement
        if ($service_type === 'restaurant') {
            $categories['fut'] = ['label' => '🍺 LES FÛTS', 'description' => 'Bières pression et fûts'];
        }

        foreach ($categories as $category_key => $category_info) {
            $content .= '<div class="restaurant-plugin-beverage-category" data-category="' . $category_key . '">';
            $content .= '<div class="restaurant-plugin-category-header" onclick="toggleBeverageCategory(\'' . $category_key . '\')">';
            $content .= '<div>';
            $content .= '<h4>' . $category_info['label'] . '</h4>';
            $content .= '<p class="restaurant-plugin-small-text">' . $category_info['description'] . '</p>';
            $content .= '</div>';
            $content .= '<div class="restaurant-plugin-category-toggle">▼</div>';
            $content .= '</div>';
            
            $content .= '<div class="restaurant-plugin-category-content" id="beverages-' . $category_key . '" style="display: none;">';
            $content .= '<div class="restaurant-plugin-loading">Chargement des produits...</div>';
            $content .= '</div>';
            $content .= '</div>';
        }

        $content .= '</div>';

        return [
            'step_number' => 5,
            'service_type' => $service_type,
            'title' => $title,
            'content' => $content
        ];
    }

    /**
     * Étape 6: Options remorque OU Coordonnées restaurant
     */
    private function generate_step_6_content($service_type, $form_data)
    {
        if ($service_type === 'remorque') {
            // Options remorque
            $title = __('CHOIX DES OPTIONS', 'restaurant-booking') . ' <small>(' . __('Optionnel', 'restaurant-booking') . ')</small>';
            
            $tireuse_price = $this->options['tireuse_price'];
            $games_price = $this->options['games_price'];

            $content = '<div class="restaurant-plugin-options-section">';

            // Information
            $content .= '<div class="restaurant-plugin-message restaurant-plugin-message-info">';
            $content .= '<p><strong>⚡ ' . __('Information :', 'restaurant-booking') . '</strong></p>';
            $content .= '<p>' . __('Ces options sont spécifiques à la remorque Block et sont entièrement optionnelles.', 'restaurant-booking') . '</p>';
            $content .= '</div>';

            // Option TIREUSE
            $content .= '<div class="restaurant-plugin-card">';
            $content .= '<div class="restaurant-plugin-option-header">';
            $content .= '<label class="restaurant-plugin-checkbox-option">';
            $content .= '<input type="checkbox" name="option_tireuse" value="1">';
            $content .= '<span><strong>MISE À DISPO TIREUSE ' . $tireuse_price . ' €</strong></span>';
            $content .= '</label>';
            $content .= '</div>';
            $content .= '<p>' . __('Descriptif + mention (fûts non inclus à choisir)', 'restaurant-booking') . '</p>';
            $content .= '<div class="tireuse-kegs-selection" style="display: none; margin-top: 15px;">';
            $content .= '<p><strong>' . __('Sélection des fûts (obligatoire avec tireuse) :', 'restaurant-booking') . '</strong></p>';
            $content .= '<div class="kegs-products-container"></div>';
            $content .= '</div>';
            $content .= '</div>';

            // Option JEUX
            $content .= '<div class="restaurant-plugin-card">';
            $content .= '<div class="restaurant-plugin-option-header">';
            $content .= '<label class="restaurant-plugin-checkbox-option">';
            $content .= '<input type="checkbox" name="option_games" value="1">';
            $content .= '<span><strong>INSTALLATION JEUX ' . $games_price . ' €</strong></span>';
            $content .= '</label>';
            $content .= '</div>';
            $content .= '<p>' . __('Descriptif avec listing des jeux (type jeu gonflable)', 'restaurant-booking') . '</p>';
            $content .= '<div class="games-selection" style="display: none; margin-top: 15px;">';
            $content .= '<div class="games-products-container"></div>';
            $content .= '</div>';
            $content .= '</div>';

            $content .= '</div>';

        } else {
            // Coordonnées restaurant (étape finale)
            return $this->generate_contact_form_content();
        }

        return [
            'step_number' => 6,
            'service_type' => $service_type,
            'title' => $title,
            'content' => $content
        ];
    }

    /**
     * Étape 7: Coordonnées remorque (seulement pour remorque)
     */
    private function generate_step_7_content($service_type, $form_data)
    {
        if ($service_type === 'remorque') {
            return $this->generate_contact_form_content();
        }

        throw new Exception(__('Étape 7 non applicable pour ce service', 'restaurant-booking'));
    }

    /**
     * Générer le formulaire de contact
     */
    private function generate_contact_form_content()
    {
        $title = __('COORDONNÉES/CONTACT', 'restaurant-booking');

        $content = '<div class="restaurant-plugin-contact-section">';

        $content .= '<div class="restaurant-plugin-form-row">';
        $content .= '<div class="restaurant-plugin-form-group">';
        $content .= '<label for="customer_lastname" class="required">' . __('Nom', 'restaurant-booking') . '</label>';
        $content .= '<input type="text" id="customer_lastname" name="customer_lastname" required>';
        $content .= '</div>';
        $content .= '<div class="restaurant-plugin-form-group">';
        $content .= '<label for="customer_firstname" class="required">' . __('Prénom', 'restaurant-booking') . '</label>';
        $content .= '<input type="text" id="customer_firstname" name="customer_firstname" required>';
        $content .= '</div>';
        $content .= '</div>';

        $content .= '<div class="restaurant-plugin-form-row">';
        $content .= '<div class="restaurant-plugin-form-group">';
        $content .= '<label for="customer_phone" class="required">' . __('Téléphone', 'restaurant-booking') . '</label>';
        $content .= '<input type="tel" id="customer_phone" name="customer_phone" required>';
        $content .= '</div>';
        $content .= '<div class="restaurant-plugin-form-group">';
        $content .= '<label for="customer_email" class="required">' . __('Email', 'restaurant-booking') . '</label>';
        $content .= '<input type="email" id="customer_email" name="customer_email" required>';
        $content .= '</div>';
        $content .= '</div>';

        $content .= '<div class="restaurant-plugin-form-group">';
        $content .= '<label for="customer_message">' . __('Section question/commentaire', 'restaurant-booking') . '</label>';
        $content .= '<textarea id="customer_message" name="customer_message" rows="4" placeholder="' . esc_attr($this->options['comment_section_text']) . '"></textarea>';
        $content .= '</div>';

        $content .= '<div class="restaurant-plugin-text-center restaurant-plugin-mt-4">';
        $content .= '<button type="button" class="restaurant-plugin-btn-accent submit-quote-button">';
        $content .= __('OBTENIR MON DEVIS ESTIMATIF', 'restaurant-booking');
        $content .= '</button>';
        $content .= '</div>';

        $content .= '</div>';

        return [
            'step_number' => 7, // Sera ajusté selon le service
            'service_type' => 'both',
            'title' => $title,
            'content' => $content
        ];
    }

    /**
     * Calculer le prix en temps réel
     */
    public function calculate_price()
    {
        try {
            // Vérification de sécurité
            if (!wp_verify_nonce($_POST['nonce'], 'restaurant_plugin_form')) {
                throw new Exception(__('Erreur de sécurité', 'restaurant-booking'));
            }

            $service_type = sanitize_text_field($_POST['service_type']);
            $form_data = $_POST['form_data'] ?? [];

            // Utiliser le calculateur V2 si disponible
            if (class_exists('RestaurantBooking_Quote_Calculator_V2')) {
                $calculator = new RestaurantBooking_Quote_Calculator_V2();
                $price_data = $calculator->calculate_total($service_type, $form_data);
            } else {
                $price_data = $this->calculate_simple_price($service_type, $form_data);
            }

            wp_send_json_success($price_data);

        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Calcul de prix simple (fallback)
     */
    private function calculate_simple_price($service_type, $form_data)
    {
        $base_price = ($service_type === 'restaurant') ? 300 : 350;
        $supplements_total = 0;
        $products_total = 0;
        
        // Supplément durée
        if (isset($form_data['event_duration'])) {
            $duration = (int) $form_data['event_duration'];
            $included_duration = $this->options[$service_type . '_max_duration_included'];
            $extra_hours = max(0, $duration - $included_duration);
            $supplements_total += $extra_hours * $this->options[$service_type . '_extra_hour_price'];
        }
        
        // Supplément convives (remorque)
        if ($service_type === 'remorque' && isset($form_data['guest_count'])) {
            $guest_count = (int) $form_data['guest_count'];
            if ($guest_count > $this->options['remorque_staff_threshold']) {
                $supplements_total += $this->options['remorque_staff_supplement'];
            }
        }
        
        // Supplément distance (remorque)
        if (isset($form_data['delivery_supplement'])) {
            $supplements_total += (float) $form_data['delivery_supplement'];
        }
        
        // Options remorque
        if ($service_type === 'remorque') {
            if (!empty($form_data['option_tireuse'])) {
                $supplements_total += $this->options['tireuse_price'];
            }
            if (!empty($form_data['option_games'])) {
                $supplements_total += $this->options['games_price'];
            }
        }
        
        $total_price = $base_price + $supplements_total + $products_total;
        
        return [
            'service_type' => $service_type,
            'base_price' => $base_price,
            'supplements_total' => $supplements_total,
            'products_total' => $products_total,
            'total_price' => $total_price,
            'breakdown' => []
        ];
    }

    /**
     * Vérifier la disponibilité d'une date
     */
    public function check_date_availability()
    {
        try {
            if (!wp_verify_nonce($_POST['nonce'], 'restaurant_plugin_form')) {
                throw new Exception(__('Erreur de sécurité', 'restaurant-booking'));
            }

            $date = sanitize_text_field($_POST['date']);
            $service_type = sanitize_text_field($_POST['service_type']);

            // Vérifier avec le calendrier si disponible
            $is_available = true;
            if (class_exists('RestaurantBooking_Calendar')) {
                $is_available = RestaurantBooking_Calendar::is_date_available($date, $service_type);
            }

            wp_send_json_success([
                'available' => $is_available,
                'date' => $date,
                'message' => $is_available ? __('Date disponible', 'restaurant-booking') : __('Date non disponible', 'restaurant-booking')
            ]);

        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Calculer la distance de livraison
     */
    public function calculate_distance()
    {
        try {
            if (!wp_verify_nonce($_POST['nonce'], 'restaurant_plugin_form')) {
                throw new Exception(__('Erreur de sécurité', 'restaurant-booking'));
            }

            $postal_code = sanitize_text_field($_POST['postal_code']);
            
            // Validation code postal
            if (!preg_match('/^\d{5}$/', $postal_code)) {
                throw new Exception(__('Code postal invalide', 'restaurant-booking'));
            }

            // Calculer la distance (simulation basée sur les premiers chiffres)
            $distance = $this->calculate_postal_distance($postal_code);
            
            if ($distance > $this->options['max_distance_km']) {
                throw new Exception(__('Zone de livraison dépassée', 'restaurant-booking'));
            }

            // Déterminer le supplément selon les zones
            $supplement = 0;
            $zone_name = '';
            
            if ($distance <= $this->options['free_radius_km']) {
                $supplement = 0;
                $zone_name = 'Zone locale';
            } elseif ($distance <= 50) {
                $supplement = $this->options['price_30_50km'];
                $zone_name = 'Zone 30-50km';
            } elseif ($distance <= 100) {
                $supplement = $this->options['price_50_100km'];
                $zone_name = 'Zone 50-100km';
            } else {
                $supplement = $this->options['price_100_150km'];
                $zone_name = 'Zone 100-150km';
            }

            wp_send_json_success([
                'distance' => $distance,
                'supplement' => $supplement,
                'zone' => $zone_name
            ]);

        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Calculer la distance approximative basée sur le code postal
     */
    private function calculate_postal_distance($postal_code)
    {
        // Base : 67000 Strasbourg
        $base_dept = 67;
        $input_dept = (int) substr($postal_code, 0, 2);
        
        // Calcul approximatif basé sur les départements
        $dept_distances = [
            67 => 0,   // Bas-Rhin
            68 => 25,  // Haut-Rhin
            54 => 40,  // Meurthe-et-Moselle
            57 => 35,  // Moselle
            88 => 45,  // Vosges
            25 => 60,  // Doubs
            70 => 55,  // Haute-Saône
        ];
        
        if (isset($dept_distances[$input_dept])) {
            return $dept_distances[$input_dept];
        }
        
        // Distance approximative pour les autres départements
        $distance_factor = abs($input_dept - $base_dept) * 15;
        return min($distance_factor, 200); // Cap à 200km
    }

    /**
     * Récupérer les produits signature
     */
    public function get_signature_products()
    {
        try {
            if (!wp_verify_nonce($_POST['nonce'], 'restaurant_plugin_form')) {
                throw new Exception(__('Erreur de sécurité', 'restaurant-booking'));
            }

            $signature_type = sanitize_text_field($_POST['signature_type']);
            $service_type = sanitize_text_field($_POST['service_type']);

            // Récupérer les produits depuis la base de données
            global $wpdb;
            
            $category_table = $wpdb->prefix . 'restaurant_categories';
            $products_table = $wpdb->prefix . 'restaurant_products';
            
            $category_slug = strtolower($signature_type);
            
            $category = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $category_table WHERE slug = %s AND is_active = 1",
                $category_slug
            ));
            
            if (!$category) {
                wp_send_json_success(['products' => []]);
                return;
            }
            
            $products = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $products_table WHERE category_id = %d AND is_active = 1 ORDER BY display_order ASC",
                $category->id
            ));

            wp_send_json_success(['products' => $products]);

        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Soumettre le devis final
     */
    public function submit_quote()
    {
        try {
            if (!wp_verify_nonce($_POST['nonce'], 'restaurant_plugin_form')) {
                throw new Exception(__('Erreur de sécurité', 'restaurant-booking'));
            }

            $service_type = sanitize_text_field($_POST['service_type']);
            $form_data = $_POST['form_data'] ?? [];

            // Créer le devis
            $quote_id = $this->create_quote($service_type, $form_data);
            
            // Envoyer l'email
            $this->send_quote_email($quote_id);

            wp_send_json_success([
                'quote_id' => $quote_id,
                'message' => $this->options['final_message']
            ]);

        } catch (Exception $e) {
            RestaurantBooking_Logger::error('Erreur soumission devis', [
                'error' => $e->getMessage(),
                'service_type' => $_POST['service_type'] ?? '',
                'form_data' => $_POST['form_data'] ?? []
            ]);

            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Créer un devis
     */
    private function create_quote($service_type, $form_data)
    {
        // Calculer le prix
        if (class_exists('RestaurantBooking_Quote_Calculator_V2')) {
            $calculator = new RestaurantBooking_Quote_Calculator_V2();
            $price_data = $calculator->calculate_total($service_type, $form_data);
        } else {
            $price_data = $this->calculate_simple_price($service_type, $form_data);
        }

        // Créer avec la classe Quote si disponible
        if (class_exists('RestaurantBooking_Quote')) {
            return RestaurantBooking_Quote::create([
                'service_type' => $service_type,
                'form_data' => $form_data,
                'price_data' => $price_data,
                'status' => 'draft'
            ]);
        }

        // Fallback : insertion directe
        global $wpdb;
        $table_name = $wpdb->prefix . 'restaurant_quotes';
        
        $result = $wpdb->insert($table_name, [
            'service_type' => $service_type,
            'customer_firstname' => $form_data['customer_firstname'] ?? '',
            'customer_lastname' => $form_data['customer_lastname'] ?? '',
            'customer_email' => $form_data['customer_email'] ?? '',
            'customer_phone' => $form_data['customer_phone'] ?? '',
            'event_date' => $form_data['event_date'] ?? null,
            'guest_count' => $form_data['guest_count'] ?? 0,
            'total_price' => $price_data['total_price'],
            'form_data' => json_encode($form_data),
            'price_data' => json_encode($price_data),
            'status' => 'draft',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ]);
        
        if ($result === false) {
            throw new Exception(__('Erreur lors de la création du devis', 'restaurant-booking'));
        }
        
        return $wpdb->insert_id;
    }

    /**
     * Envoyer l'email de devis
     */
    private function send_quote_email($quote_id)
    {
        try {
            if (class_exists('RestaurantBooking_Email')) {
                $email_handler = RestaurantBooking_Email::get_instance();
                return $email_handler->send_quote_email($quote_id);
            }

            // Fallback : email simple
            global $wpdb;
            $table_name = $wpdb->prefix . 'restaurant_quotes';
            
            $quote = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $quote_id
            ));

            if ($quote) {
                $subject = __('Votre devis Restaurant Block', 'restaurant-booking');
                $message = sprintf(
                    __('Bonjour %s,\n\nVotre devis n°%s a été généré avec succès.\n\n%s\n\nCordialement,\nL\'équipe Restaurant Block', 'restaurant-booking'),
                    $quote->customer_firstname,
                    $quote_id,
                    $this->options['final_message']
                );
                
                wp_mail($quote->customer_email, $subject, $message);
            }

        } catch (Exception $e) {
            // Ne pas faire échouer la création du devis pour un problème d'email
            RestaurantBooking_Logger::error('Erreur envoi email devis', [
                'error' => $e->getMessage(),
                'quote_id' => $quote_id
            ]);
        }
    }

    /**
     * Générer les produits Mini Boss
     */
    private function generate_mini_boss_products()
    {
        global $wpdb;
        
        $category_table = $wpdb->prefix . 'restaurant_categories';
        $products_table = $wpdb->prefix . 'restaurant_products';
        
        $category = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $category_table WHERE slug = 'mini-boss' AND is_active = 1"
        ));
        
        if (!$category) {
            return '<p>' . __('Aucun menu enfant disponible.', 'restaurant-booking') . '</p>';
        }
        
        $products = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $products_table WHERE category_id = %d AND is_active = 1 ORDER BY display_order ASC",
            $category->id
        ));
        
        if (empty($products)) {
            return '<p>' . __('Aucun menu enfant disponible.', 'restaurant-booking') . '</p>';
        }
        
        $html = '<div class="restaurant-plugin-products-grid">';
        
        foreach ($products as $product) {
            $html .= '<div class="restaurant-plugin-product-card" data-product-id="' . $product->id . '">';
            $html .= '<div class="product-content">';
            $html .= '<h4 class="product-title">' . esc_html($product->name) . '</h4>';
            if ($product->description) {
                $html .= '<p class="product-description">' . esc_html($product->description) . '</p>';
            }
            $html .= '<div class="product-price">' . number_format($product->price, 2, ',', ' ') . ' €</div>';
            $html .= '</div>';
            $html .= '<div class="product-quantity-selector">';
            $html .= '<button type="button" class="qty-btn qty-minus" data-target="mini_boss_' . $product->id . '">-</button>';
            $html .= '<input type="number" class="qty-input" id="mini_boss_' . $product->id . '" name="products[mini_boss][' . $product->id . ']" value="0" min="0" max="20" data-product-id="' . $product->id . '" data-category="mini-boss">';
            $html .= '<button type="button" class="qty-btn qty-plus" data-target="mini_boss_' . $product->id . '">+</button>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Générer les accompagnements
     */
    private function generate_accompaniment_products()
    {
        global $wpdb;
        
        $category_table = $wpdb->prefix . 'restaurant_categories';
        $products_table = $wpdb->prefix . 'restaurant_products';
        
        $category = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $category_table WHERE slug = 'accompaniments' AND is_active = 1"
        ));
        
        if (!$category) {
            return '<p>' . __('Aucun accompagnement disponible.', 'restaurant-booking') . '</p>';
        }
        
        $products = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $products_table WHERE category_id = %d AND is_active = 1 ORDER BY display_order ASC",
            $category->id
        ));
        
        if (empty($products)) {
            return '<p>' . __('Aucun accompagnement disponible.', 'restaurant-booking') . '</p>';
        }
        
        $html = '<div class="restaurant-plugin-accompaniments-list">';
        
        foreach ($products as $product) {
            $html .= '<div class="restaurant-plugin-accompaniment-item" data-product-id="' . $product->id . '">';
            $html .= '<div class="accompaniment-info">';
            $html .= '<span class="accompaniment-name">' . esc_html($product->name) . '</span>';
            $html .= '<span class="accompaniment-price">' . number_format($product->price, 2, ',', ' ') . ' €</span>';
            $html .= '</div>';
            $html .= '<div class="accompaniment-quantity-selector">';
            $html .= '<button type="button" class="qty-btn qty-minus" data-target="accompaniment_' . $product->id . '">-</button>';
            $html .= '<input type="number" class="qty-input" id="accompaniment_' . $product->id . '" name="products[accompaniments][' . $product->id . ']" value="0" min="0" max="50" data-product-id="' . $product->id . '" data-category="accompaniments">';
            $html .= '<button type="button" class="qty-btn qty-plus" data-target="accompaniment_' . $product->id . '">+</button>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
}

// Initialiser le gestionnaire AJAX
RestaurantBooking_Ajax_Handler_Block_Unified::get_instance();

