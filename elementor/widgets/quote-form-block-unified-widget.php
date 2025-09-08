<?php
/**
 * Widget Elementor - Formulaire de Devis Block Unifié V2
 * Structure exacte selon cahier des charges
 * Connexion complète aux Options Unifiées
 * 
 * @package RestaurantBooking
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Core\Schemes\Typography;
use Elementor\Core\Schemes\Color;

class RestaurantBooking_Quote_Form_Block_Unified_Widget extends Widget_Base
{
    /**
     * Nom du widget
     */
    public function get_name()
    {
        return 'restaurant_booking_quote_form_block_unified';
    }

    /**
     * Titre du widget
     */
    public function get_title()
    {
        return __('Formulaire de Devis Block Unifié V2', 'restaurant-booking');
    }

    /**
     * Icône du widget
     */
    public function get_icon()
    {
        return 'eicon-form-horizontal';
    }

    /**
     * Catégories du widget
     */
    public function get_categories()
    {
        return ['restaurant-booking'];
    }

    /**
     * Mots-clés pour la recherche
     */
    public function get_keywords()
    {
        return ['restaurant', 'booking', 'devis', 'formulaire', 'block', 'unifié'];
    }

    /**
     * Scripts nécessaires
     */
    public function get_script_depends()
    {
        return ['restaurant-booking-quote-form-block-unified'];
    }

    /**
     * Styles nécessaires
     */
    public function get_style_depends()
    {
        return ['restaurant-booking-quote-form-block'];
    }

    /**
     * Contrôles du widget
     */
    protected function register_controls()
    {
        // Section Contenu Principal
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Contenu Principal', 'restaurant-booking'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'widget_title',
            [
                'label' => __('Titre du Widget', 'restaurant-booking'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Demande de Devis Privatisation', 'restaurant-booking'),
                'placeholder' => __('Entrez le titre...', 'restaurant-booking'),
                'label_block' => true,
            ]
        );

        $this->add_control(
            'widget_subtitle',
            [
                'label' => __('Sous-titre du Widget', 'restaurant-booking'),
                'type' => Controls_Manager::TEXTAREA,
                'default' => __('Choisissez votre service et obtenez votre devis personnalisé', 'restaurant-booking'),
                'placeholder' => __('Entrez le sous-titre...', 'restaurant-booking'),
                'rows' => 3,
            ]
        );

        $this->add_control(
            'show_progress_bar',
            [
                'label' => __('Afficher la barre de progression', 'restaurant-booking'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Oui', 'restaurant-booking'),
                'label_off' => __('Non', 'restaurant-booking'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'calculator_position',
            [
                'label' => __('Position du calculateur', 'restaurant-booking'),
                'type' => Controls_Manager::SELECT,
                'default' => 'sticky',
                'options' => [
                    'sticky' => __('Collant (sticky)', 'restaurant-booking'),
                    'bottom' => __('En bas', 'restaurant-booking'),
                    'hidden' => __('Masqué', 'restaurant-booking'),
                ],
            ]
        );

        $this->end_controls_section();

        // Section Textes des Services
        $this->start_controls_section(
            'services_section',
            [
                'label' => __('Textes des Services', 'restaurant-booking'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'service_selection_title',
            [
                'label' => __('Titre de sélection des services', 'restaurant-booking'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Choisissez votre service', 'restaurant-booking'),
                'label_block' => true,
            ]
        );

        // Restaurant
        $this->add_control(
            'restaurant_card_title',
            [
                'label' => __('Titre carte Restaurant', 'restaurant-booking'),
                'type' => Controls_Manager::TEXT,
                'default' => __('PRIVATISATION DU RESTAURANT', 'restaurant-booking'),
                'label_block' => true,
            ]
        );

        $this->add_control(
            'restaurant_card_subtitle',
            [
                'label' => __('Sous-titre carte Restaurant', 'restaurant-booking'),
                'type' => Controls_Manager::TEXT,
                'default' => __('De 10 à 30 personnes', 'restaurant-booking'),
                'label_block' => true,
            ]
        );

        $this->add_control(
            'restaurant_card_description',
            [
                'label' => __('Description carte Restaurant', 'restaurant-booking'),
                'type' => Controls_Manager::TEXTAREA,
                'default' => __('Privatisez notre restaurant pour vos événements intimes et profitez d\'un service personnalisé dans un cadre chaleureux.', 'restaurant-booking'),
                'rows' => 3,
            ]
        );

        // Remorque
        $this->add_control(
            'remorque_card_title',
            [
                'label' => __('Titre carte Remorque', 'restaurant-booking'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Privatisation de la remorque Block', 'restaurant-booking'),
                'label_block' => true,
            ]
        );

        $this->add_control(
            'remorque_card_subtitle',
            [
                'label' => __('Sous-titre carte Remorque', 'restaurant-booking'),
                'type' => Controls_Manager::TEXT,
                'default' => __('À partir de 20 personnes', 'restaurant-booking'),
                'label_block' => true,
            ]
        );

        $this->add_control(
            'remorque_card_description',
            [
                'label' => __('Description carte Remorque', 'restaurant-booking'),
                'type' => Controls_Manager::TEXTAREA,
                'default' => __('Notre remorque mobile se déplace pour vos événements extérieurs et grandes réceptions.', 'restaurant-booking'),
                'rows' => 3,
            ]
        );

        $this->end_controls_section();

        // Section Messages
        $this->start_controls_section(
            'messages_section',
            [
                'label' => __('Messages et Textes', 'restaurant-booking'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'success_message',
            [
                'label' => __('Message de succès', 'restaurant-booking'),
                'type' => Controls_Manager::TEXTAREA,
                'default' => __('Votre devis est d\'ores et déjà disponible dans votre boîte mail', 'restaurant-booking'),
                'rows' => 3,
            ]
        );

        $this->add_control(
            'loading_message',
            [
                'label' => __('Message de chargement', 'restaurant-booking'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Génération de votre devis en cours...', 'restaurant-booking'),
                'label_block' => true,
            ]
        );

        $this->end_controls_section();

        // Section Style - Couleurs
        $this->start_controls_section(
            'style_colors_section',
            [
                'label' => __('Couleurs Block', 'restaurant-booking'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'primary_color',
            [
                'label' => __('Couleur Primaire (Vert)', 'restaurant-booking'),
                'type' => Controls_Manager::COLOR,
                'default' => '#243127',
                'description' => __('Couleur principale Block (vert foncé)', 'restaurant-booking'),
            ]
        );

        $this->add_control(
            'secondary_color',
            [
                'label' => __('Couleur Secondaire (Orange)', 'restaurant-booking'),
                'type' => Controls_Manager::COLOR,
                'default' => '#FFB404',
                'description' => __('Couleur secondaire Block (orange/jaune)', 'restaurant-booking'),
            ]
        );

        $this->add_control(
            'accent_color',
            [
                'label' => __('Couleur Accent (Rouge)', 'restaurant-booking'),
                'type' => Controls_Manager::COLOR,
                'default' => '#EF3D1D',
                'description' => __('Couleur accent Block (rouge)', 'restaurant-booking'),
            ]
        );

        $this->add_control(
            'light_color',
            [
                'label' => __('Couleur Claire (Beige)', 'restaurant-booking'),
                'type' => Controls_Manager::COLOR,
                'default' => '#F6F2E7',
                'description' => __('Couleur claire Block (beige)', 'restaurant-booking'),
            ]
        );

        $this->end_controls_section();

        // Section Style - Typographie
        $this->start_controls_section(
            'style_typography_section',
            [
                'label' => __('Typographie', 'restaurant-booking'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'label' => __('Typographie Titre', 'restaurant-booking'),
                'selector' => '{{WRAPPER}} .restaurant-plugin-title',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'subtitle_typography',
                'label' => __('Typographie Sous-titre', 'restaurant-booking'),
                'selector' => '{{WRAPPER}} .restaurant-plugin-subtitle',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'content_typography',
                'label' => __('Typographie Contenu', 'restaurant-booking'),
                'selector' => '{{WRAPPER}} .restaurant-plugin-text',
            ]
        );

        $this->end_controls_section();

        // Section Style - Boutons
        $this->start_controls_section(
            'style_buttons_section',
            [
                'label' => __('Styles des Boutons', 'restaurant-booking'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'button_border_radius',
            [
                'label' => __('Rayon des bordures', 'restaurant-booking'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 20,
                ],
                'selectors' => [
                    '{{WRAPPER}} .restaurant-plugin-btn-primary' => 'border-radius: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .restaurant-plugin-btn-secondary' => 'border-radius: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .restaurant-plugin-card' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Section Avancée
        $this->start_controls_section(
            'advanced_section',
            [
                'label' => __('Paramètres Avancés', 'restaurant-booking'),
                'tab' => Controls_Manager::TAB_ADVANCED,
            ]
        );

        $this->add_control(
            'custom_css_class',
            [
                'label' => __('Classe CSS personnalisée', 'restaurant-booking'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => __('ma-classe-personnalisee', 'restaurant-booking'),
                'label_block' => true,
            ]
        );

        $this->add_control(
            'enable_debug',
            [
                'label' => __('Mode Debug', 'restaurant-booking'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Activé', 'restaurant-booking'),
                'label_off' => __('Désactivé', 'restaurant-booking'),
                'return_value' => 'yes',
                'default' => 'no',
                'description' => __('Active les logs de debug dans la console', 'restaurant-booking'),
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Rendu du widget
     */
    protected function render()
    {
        $settings = $this->get_settings_for_display();
        
        // Générer un ID unique pour cette instance
        $widget_id = 'restaurant-plugin-form-' . $this->get_id();
        
        // Récupérer les options unifiées
        $options = $this->get_unified_options();
        
        // Préparer la configuration
        $config = [
            'widget_id' => $widget_id,
            'widget_title' => $settings['widget_title'],
            'widget_subtitle' => $settings['widget_subtitle'],
            'show_progress_bar' => ($settings['show_progress_bar'] === 'yes'),
            'calculator_position' => $settings['calculator_position'],
            
            // Services
            'service_selection_title' => $settings['service_selection_title'],
            'restaurant_card' => [
                'title' => $settings['restaurant_card_title'],
                'subtitle' => $settings['restaurant_card_subtitle'],
                'description' => $settings['restaurant_card_description'],
            ],
            'remorque_card' => [
                'title' => $settings['remorque_card_title'],
                'subtitle' => $settings['remorque_card_subtitle'],
                'description' => $settings['remorque_card_description'],
            ],
            
            // Messages
            'success_message' => $settings['success_message'],
            'loading_message' => $settings['loading_message'],
            
            // Couleurs
            'colors' => [
                'primary' => $settings['primary_color'],
                'secondary' => $settings['secondary_color'],
                'accent' => $settings['accent_color'],
                'light' => $settings['light_color'],
            ],
            
            // Options unifiées
            'options' => $options,
            
            // Debug
            'debug' => ($settings['enable_debug'] === 'yes'),
        ];

        // Enregistrer et charger les assets
        $this->enqueue_form_assets();
        
        // Classes CSS
        $css_classes = ['restaurant-plugin-container'];
        if (!empty($settings['custom_css_class'])) {
            $css_classes[] = esc_attr($settings['custom_css_class']);
        }
        
        // Rendu HTML
        ?>
        <div class="<?php echo implode(' ', $css_classes); ?>" 
             id="<?php echo esc_attr($widget_id); ?>" 
             data-config="<?php echo esc_attr(json_encode($config)); ?>">
             
            <?php $this->render_form_html($config); ?>
            
        </div>
        
        <?php if ($config['debug']): ?>
        <script>
        console.log('Restaurant Plugin Debug:', <?php echo json_encode($config); ?>);
        </script>
        <?php endif; ?>
        
        <style>
        #<?php echo esc_attr($widget_id); ?> {
            --restaurant-primary: <?php echo esc_attr($settings['primary_color']); ?> !important;
            --restaurant-secondary: <?php echo esc_attr($settings['secondary_color']); ?> !important;
            --restaurant-accent: <?php echo esc_attr($settings['accent_color']); ?> !important;
            --restaurant-light: <?php echo esc_attr($settings['light_color']); ?> !important;
        }
        </style>
        <?php
    }

    /**
     * Récupérer les options unifiées
     */
    private function get_unified_options()
    {
        if (class_exists('RestaurantBooking_Options_Unified_Admin')) {
            $instance = new RestaurantBooking_Options_Unified_Admin();
            return $instance->get_options();
        }
        
        // Fallback avec valeurs par défaut
        return [
            // Règles restaurant
            'restaurant_min_guests' => 10,
            'restaurant_max_guests' => 30,
            'restaurant_min_duration' => 2,
            'restaurant_extra_hour_price' => 50,
            
            // Règles remorque
            'remorque_min_guests' => 20,
            'remorque_max_guests' => 100,
            'remorque_staff_threshold' => 50,
            'remorque_staff_supplement' => 150,
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
     * Enregistrer et charger les assets
     */
    private function enqueue_form_assets()
    {
        // CSS Block
        wp_enqueue_style(
            'restaurant-booking-quote-form-block',
            RESTAURANT_BOOKING_PLUGIN_URL . 'assets/css/quote-form-block.css',
            [],
            RESTAURANT_BOOKING_VERSION
        );
        
        // JavaScript
        wp_enqueue_script(
            'restaurant-booking-quote-form-block-unified',
            RESTAURANT_BOOKING_PLUGIN_URL . 'assets/js/quote-form-block-unified.js',
            ['jquery'],
            RESTAURANT_BOOKING_VERSION,
            true
        );
        
        // Localisation
        wp_localize_script('restaurant-booking-quote-form-block-unified', 'restaurantPluginAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('restaurant_plugin_form'),
            'texts' => [
                'loading' => __('Chargement...', 'restaurant-booking'),
                'error_network' => __('Erreur de connexion. Veuillez réessayer.', 'restaurant-booking'),
                'error_required' => __('Ce champ est obligatoire', 'restaurant-booking'),
                'error_invalid_date' => __('Date invalide', 'restaurant-booking'),
                'error_min_guests' => __('Nombre minimum de convives non respecté', 'restaurant-booking'),
                'error_max_guests' => __('Nombre maximum de convives dépassé', 'restaurant-booking'),
                'calculating' => __('Calcul en cours...', 'restaurant-booking'),
                'success_quote' => __('Devis envoyé avec succès !', 'restaurant-booking'),
                'step_validation_error' => __('Veuillez corriger les erreurs avant de continuer', 'restaurant-booking'),
                'network_error' => __('Erreur réseau. Vérifiez votre connexion.', 'restaurant-booking'),
            ]
        ]);
    }

    /**
     * Rendu HTML du formulaire selon le cahier des charges
     */
    private function render_form_html($config)
    {
        ?>
        <!-- En-tête du formulaire -->
        <div class="restaurant-plugin-form-header restaurant-plugin-text-center">
            <?php if (!empty($config['widget_title'])): ?>
                <h1 class="restaurant-plugin-title"><?php echo esc_html($config['widget_title']); ?></h1>
            <?php endif; ?>
            
            <?php if (!empty($config['widget_subtitle'])): ?>
                <p class="restaurant-plugin-subtitle restaurant-plugin-text"><?php echo esc_html($config['widget_subtitle']); ?></p>
            <?php endif; ?>
        </div>

        <!-- Barre de progression (masquée initialement) -->
        <?php if ($config['show_progress_bar']): ?>
        <div class="restaurant-plugin-progress-bar restaurant-plugin-hidden" id="progress-bar">
            <div class="restaurant-plugin-progress-steps">
                <div class="restaurant-plugin-progress-line">
                    <div class="restaurant-plugin-progress-line-fill" id="progress-fill"></div>
                </div>
                <!-- Les étapes seront générées dynamiquement -->
            </div>
        </div>
        <?php endif; ?>

        <!-- Étape 0: Sélection du service -->
        <div class="restaurant-plugin-form-step active" data-step="0">
            <div class="restaurant-plugin-step-header">
                <h2 class="restaurant-plugin-step-title"><?php echo esc_html($config['service_selection_title']); ?></h2>
            </div>
            
            <div class="restaurant-plugin-service-cards">
                <!-- Card Restaurant -->
                <div class="restaurant-plugin-service-card" data-service="restaurant">
                    <div class="service-icon">🏠</div>
                    <div class="service-title"><?php echo esc_html($config['restaurant_card']['title']); ?></div>
                    <div class="service-subtitle"><?php echo esc_html($config['restaurant_card']['subtitle']); ?></div>
                    <div class="service-description"><?php echo esc_html($config['restaurant_card']['description']); ?></div>
                    <button type="button" class="restaurant-plugin-btn-primary" onclick="restaurantPluginSelectService('restaurant')">
                        <?php _e('CHOISIR', 'restaurant-booking'); ?>
                    </button>
                </div>
                
                <!-- Card Remorque -->
                <div class="restaurant-plugin-service-card" data-service="remorque">
                    <div class="service-icon">🚚</div>
                    <div class="service-title"><?php echo esc_html($config['remorque_card']['title']); ?></div>
                    <div class="service-subtitle"><?php echo esc_html($config['remorque_card']['subtitle']); ?></div>
                    <div class="service-description"><?php echo esc_html($config['remorque_card']['description']); ?></div>
                    <button type="button" class="restaurant-plugin-btn-primary" onclick="restaurantPluginSelectService('remorque')">
                        <?php _e('CHOISIR', 'restaurant-booking'); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Conteneur pour les étapes dynamiques -->
        <div class="restaurant-plugin-dynamic-steps"></div>

        <!-- Messages système -->
        <div class="restaurant-plugin-messages restaurant-plugin-hidden" id="form-messages">
            <div class="restaurant-plugin-message restaurant-plugin-message-success restaurant-plugin-hidden" id="success-message">
                <span class="message-text"></span>
            </div>
            <div class="restaurant-plugin-message restaurant-plugin-message-error restaurant-plugin-hidden" id="error-message">
                <span class="message-text"></span>
            </div>
            <div class="restaurant-plugin-message restaurant-plugin-message-warning restaurant-plugin-hidden" id="warning-message">
                <span class="message-text"></span>
            </div>
            <div class="restaurant-plugin-message restaurant-plugin-message-info restaurant-plugin-hidden" id="info-message">
                <span class="message-text"></span>
            </div>
        </div>

        <!-- Navigation du formulaire -->
        <div class="restaurant-plugin-form-navigation restaurant-plugin-hidden" id="form-navigation">
            <button type="button" class="restaurant-plugin-btn-secondary restaurant-plugin-nav-button hidden" id="prev-button">
                <?php _e('← Précédent', 'restaurant-booking'); ?>
            </button>
            
            <div class="restaurant-plugin-step-indicator">
                <span id="current-step-text"></span>
            </div>
            
            <button type="button" class="restaurant-plugin-btn-primary restaurant-plugin-nav-button" id="next-button">
                <?php _e('Suivant →', 'restaurant-booking'); ?>
            </button>
        </div>

        <!-- Calculateur de prix -->
        <?php if ($config['calculator_position'] !== 'hidden'): ?>
        <div class="restaurant-plugin-price-calculator restaurant-plugin-hidden" 
             id="price-calculator" 
             data-position="<?php echo esc_attr($config['calculator_position']); ?>">
            <h4><?php _e('💰 Montant Estimatif', 'restaurant-booking'); ?></h4>
            
            <div class="restaurant-plugin-price-row">
                <span><?php _e('Forfait de base', 'restaurant-booking'); ?></span>
                <span id="price-base">0,00 €</span>
            </div>
            
            <div class="restaurant-plugin-price-row">
                <span><?php _e('Suppléments', 'restaurant-booking'); ?></span>
                <span id="price-supplements">0,00 €</span>
            </div>
            
            <div class="restaurant-plugin-price-row">
                <span><?php _e('Produits', 'restaurant-booking'); ?></span>
                <span id="price-products">0,00 €</span>
            </div>
            
            <div class="restaurant-plugin-price-total">
                <span><?php _e('TOTAL ESTIMATIF', 'restaurant-booking'); ?></span>
                <span id="price-total">0,00 €</span>
            </div>
            
            <div class="restaurant-plugin-small-text restaurant-plugin-text-center restaurant-plugin-mt-2">
                <?php _e('Montant indiqué estimatif', 'restaurant-booking'); ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Overlay de chargement -->
        <div class="restaurant-plugin-loading-overlay restaurant-plugin-hidden" id="loading-overlay">
            <div class="restaurant-plugin-loading-spinner"></div>
            <div class="restaurant-plugin-loading-text">
                <?php echo esc_html($config['loading_message']); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu dans l'éditeur Elementor
     */
    protected function content_template()
    {
        ?>
        <div class="restaurant-plugin-container">
            <div class="restaurant-plugin-form-header restaurant-plugin-text-center">
                <h1 class="restaurant-plugin-title">{{{ settings.widget_title }}}</h1>
                <p class="restaurant-plugin-subtitle restaurant-plugin-text">{{{ settings.widget_subtitle }}}</p>
            </div>
            
            <div class="restaurant-plugin-service-cards">
                <div class="restaurant-plugin-service-card">
                    <div class="service-icon">🏠</div>
                    <div class="service-title">{{{ settings.restaurant_card_title }}}</div>
                    <div class="service-subtitle">{{{ settings.restaurant_card_subtitle }}}</div>
                    <div class="service-description">{{{ settings.restaurant_card_description }}}</div>
                    <button type="button" class="restaurant-plugin-btn-primary">CHOISIR</button>
                </div>
                
                <div class="restaurant-plugin-service-card">
                    <div class="service-icon">🚚</div>
                    <div class="service-title">{{{ settings.remorque_card_title }}}</div>
                    <div class="service-subtitle">{{{ settings.remorque_card_subtitle }}}</div>
                    <div class="service-description">{{{ settings.remorque_card_description }}}</div>
                    <button type="button" class="restaurant-plugin-btn-primary">CHOISIR</button>
                </div>
            </div>
            
            <# if (settings.calculator_position !== 'hidden') { #>
            <div class="restaurant-plugin-price-calculator">
                <h4>💰 Montant Estimatif</h4>
                <div class="restaurant-plugin-price-total">
                    <span>TOTAL ESTIMATIF</span>
                    <span>0,00 €</span>
                </div>
                <div class="restaurant-plugin-small-text restaurant-plugin-text-center">
                    Montant indiqué estimatif
                </div>
            </div>
            <# } #>
        </div>
        <?php
    }
}

