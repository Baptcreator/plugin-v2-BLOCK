<?php
/**
 * Widget Elementor - Formulaire de devis unifié v2
 *
 * @package RestaurantBooking
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Quote_Form_Unified_Widget extends \Elementor\Widget_Base
{
    /**
     * Obtenir le nom du widget
     */
    public function get_name()
    {
        return 'restaurant_booking_quote_form_unified';
    }

    /**
     * Obtenir le titre du widget
     */
    public function get_title()
    {
        return __('Formulaire de Devis Unifié v2', 'restaurant-booking');
    }

    /**
     * Obtenir l'icône du widget
     */
    public function get_icon()
    {
        return 'eicon-form-horizontal';
    }

    /**
     * Obtenir les catégories du widget
     */
    public function get_categories()
    {
        return ['restaurant-booking'];
    }

    /**
     * Obtenir les mots-clés du widget
     */
    public function get_keywords()
    {
        return ['restaurant', 'booking', 'devis', 'formulaire', 'block', 'unified', 'v2'];
    }

    /**
     * Obtenir les scripts nécessaires
     */
    public function get_script_depends()
    {
        return ['restaurant-booking-quote-form-unified'];
    }

    /**
     * Obtenir les styles nécessaires
     */
    public function get_style_depends()
    {
        return ['restaurant-booking-quote-form-unified'];
    }

    /**
     * Enregistrer les contrôles du widget
     */
    protected function register_controls()
    {
        // Section Contenu Principal
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Configuration Générale', 'restaurant-booking'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'widget_title',
            [
                'label' => __('Titre du widget', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Demande de devis privatisation', 'restaurant-booking'),
                'placeholder' => __('Saisissez le titre', 'restaurant-booking'),
            ]
        );

        $this->add_control(
            'widget_subtitle',
            [
                'label' => __('Sous-titre', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'default' => __('Choisissez votre service et obtenez votre devis personnalisé', 'restaurant-booking'),
                'placeholder' => __('Saisissez le sous-titre', 'restaurant-booking'),
            ]
        );

        $this->add_control(
            'show_progress_bar',
            [
                'label' => __('Afficher la barre de progression', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Oui', 'restaurant-booking'),
                'label_off' => __('Non', 'restaurant-booking'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'calculator_position',
            [
                'label' => __('Position du calculateur de prix', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'bottom',
                'options' => [
                    'bottom' => __('En bas', 'restaurant-booking'),
                    'right' => __('À droite', 'restaurant-booking'),
                    'floating' => __('Flottant', 'restaurant-booking'),
                ],
            ]
        );

        $this->end_controls_section();

        // Section Textes Service Selection
        $this->start_controls_section(
            'service_selection_section',
            [
                'label' => __('Sélection de Service', 'restaurant-booking'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'service_selection_title',
            [
                'label' => __('Titre sélection service', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => RestaurantBooking_Settings::get('widget_service_selection_title', 'Choisissez votre service'),
            ]
        );

        $this->add_control(
            'restaurant_card_title',
            [
                'label' => __('Titre card restaurant', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => RestaurantBooking_Settings::get('widget_restaurant_card_title', 'PRIVATISATION DU RESTAURANT'),
            ]
        );

        $this->add_control(
            'restaurant_card_subtitle',
            [
                'label' => __('Sous-titre card restaurant', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => RestaurantBooking_Settings::get('widget_restaurant_card_subtitle', 'De 10 à 30 personnes'),
            ]
        );

        $this->add_control(
            'restaurant_card_description',
            [
                'label' => __('Description card restaurant', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::WYSIWYG,
                'default' => RestaurantBooking_Settings::get('widget_restaurant_card_description', 'Privatisez notre restaurant pour vos événements intimes et profitez d\'un service personnalisé dans un cadre chaleureux.'),
            ]
        );

        $this->add_control(
            'remorque_card_title',
            [
                'label' => __('Titre card remorque', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => RestaurantBooking_Settings::get('widget_remorque_card_title', 'Privatisation de la remorque Block'),
            ]
        );

        $this->add_control(
            'remorque_card_subtitle',
            [
                'label' => __('Sous-titre card remorque', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => RestaurantBooking_Settings::get('widget_remorque_card_subtitle', 'À partir de 20 personnes'),
            ]
        );

        $this->add_control(
            'remorque_card_description',
            [
                'label' => __('Description card remorque', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::WYSIWYG,
                'default' => RestaurantBooking_Settings::get('widget_remorque_card_description', 'Notre remorque mobile se déplace pour vos événements extérieurs et grandes réceptions.'),
            ]
        );

        $this->end_controls_section();

        // Section Messages
        $this->start_controls_section(
            'messages_section',
            [
                'label' => __('Messages et Validation', 'restaurant-booking'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'success_message',
            [
                'label' => __('Message de succès', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::WYSIWYG,
                'default' => RestaurantBooking_Settings::get('quote_success_message', 'Votre devis est d\'ores et déjà disponible dans votre boîte mail, la suite ? Block va prendre contact avec vous afin d\'affiner celui-ci et de créer avec vous toute l\'expérience dont vous rêvez'),
            ]
        );

        $this->add_control(
            'loading_message',
            [
                'label' => __('Message de chargement', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Génération de votre devis en cours...', 'restaurant-booking'),
            ]
        );

        $this->end_controls_section();

        // Section Style - Général
        $this->start_controls_section(
            'general_style_section',
            [
                'label' => __('Style Général', 'restaurant-booking'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'primary_color',
            [
                'label' => __('Couleur primaire', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#243127',
                'selectors' => [
                    '{{WRAPPER}}' => '--rb-primary-color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'secondary_color',
            [
                'label' => __('Couleur secondaire', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#FFB404',
                'selectors' => [
                    '{{WRAPPER}}' => '--rb-secondary-color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'accent_color',
            [
                'label' => __('Couleur d\'accent', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#EF3D1D',
                'selectors' => [
                    '{{WRAPPER}}' => '--rb-accent-color: {{VALUE}}',
                ],
            ]
        );

        $this->add_responsive_control(
            'container_padding',
            [
                'label' => __('Espacement interne', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .rb-quote-form-container' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'container_border',
                'selector' => '{{WRAPPER}} .rb-quote-form-container',
            ]
        );

        $this->add_responsive_control(
            'container_border_radius',
            [
                'label' => __('Rayon de bordure', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .rb-quote-form-container' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Section Style - Cards de service
        $this->start_controls_section(
            'service_cards_style_section',
            [
                'label' => __('Style Cards de Service', 'restaurant-booking'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'cards_gap',
            [
                'label' => __('Espacement entre les cards', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                        'step' => 5,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 30,
                ],
                'selectors' => [
                    '{{WRAPPER}} .rb-service-cards' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'card_background',
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .rb-service-card',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'card_border',
                'selector' => '{{WRAPPER}} .rb-service-card',
            ]
        );

        $this->add_responsive_control(
            'card_border_radius',
            [
                'label' => __('Rayon de bordure des cards', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .rb-service-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'card_box_shadow',
                'selector' => '{{WRAPPER}} .rb-service-card',
            ]
        );

        $this->end_controls_section();

        // Section Style - Boutons
        $this->start_controls_section(
            'buttons_style_section',
            [
                'label' => __('Style des Boutons', 'restaurant-booking'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'button_typography',
                'selector' => '{{WRAPPER}} .rb-btn',
            ]
        );

        $this->start_controls_tabs('button_tabs');

        $this->start_controls_tab(
            'button_normal_tab',
            [
                'label' => __('Normal', 'restaurant-booking'),
            ]
        );

        $this->add_control(
            'button_text_color',
            [
                'label' => __('Couleur du texte', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .rb-btn' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'button_background',
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .rb-btn',
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'button_hover_tab',
            [
                'label' => __('Survol', 'restaurant-booking'),
            ]
        );

        $this->add_control(
            'button_hover_text_color',
            [
                'label' => __('Couleur du texte', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rb-btn:hover' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'button_hover_background',
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .rb-btn:hover',
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control(
            'button_padding',
            [
                'label' => __('Espacement interne', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .rb-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->add_responsive_control(
            'button_border_radius',
            [
                'label' => __('Rayon de bordure', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .rb-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Section Style - Calculateur de prix
        $this->start_controls_section(
            'calculator_style_section',
            [
                'label' => __('Style Calculateur de Prix', 'restaurant-booking'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'calculator_background',
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .rb-price-calculator',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'calculator_border',
                'selector' => '{{WRAPPER}} .rb-price-calculator',
            ]
        );

        $this->add_responsive_control(
            'calculator_padding',
            [
                'label' => __('Espacement interne', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .rb-price-calculator' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'calculator_price_color',
            [
                'label' => __('Couleur du prix', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#243127',
                'selectors' => [
                    '{{WRAPPER}} .rb-price-total' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'calculator_price_typography',
                'selector' => '{{WRAPPER}} .rb-price-total',
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

        // Vérifier que les classes nécessaires existent
        if (!class_exists('RestaurantBooking_Migration_V2')) {
            echo '<div class="rb-error">';
            echo __('Erreur : Le système v2 n\'est pas encore initialisé.', 'restaurant-booking');
            echo '</div>';
            return;
        }

        // Vérifier l'état de la migration
        $migration_status = RestaurantBooking_Migration_V2::get_migration_status();
        if ($migration_status['migration_needed']) {
            echo '<div class="rb-error">';
            echo __('Migration v2 requise. Veuillez contacter l\'administrateur.', 'restaurant-booking');
            echo '</div>';
            return;
        }

        // Préparer les paramètres pour le formulaire
        $form_config = array(
            'widget_id' => $this->get_id(),
            'widget_title' => $settings['widget_title'],
            'widget_subtitle' => $settings['widget_subtitle'],
            'show_progress_bar' => ($settings['show_progress_bar'] === 'yes'),
            'calculator_position' => $settings['calculator_position'],
            
            // Textes service selection
            'service_selection_title' => $settings['service_selection_title'],
            'restaurant_card' => array(
                'title' => $settings['restaurant_card_title'],
                'subtitle' => $settings['restaurant_card_subtitle'],
                'description' => $settings['restaurant_card_description']
            ),
            'remorque_card' => array(
                'title' => $settings['remorque_card_title'],
                'subtitle' => $settings['remorque_card_subtitle'],
                'description' => $settings['remorque_card_description']
            ),
            
            // Messages
            'success_message' => $settings['success_message'],
            'loading_message' => $settings['loading_message'],
            
            // Couleurs
            'colors' => array(
                'primary' => $settings['primary_color'],
                'secondary' => $settings['secondary_color'],
                'accent' => $settings['accent_color']
            )
        );

        // Inclure le template du formulaire unifié
        $this->render_unified_form($form_config);
    }

    /**
     * Rendu du formulaire unifié
     */
    private function render_unified_form($config)
    {
        // Générer un ID unique pour ce widget
        $widget_id = 'rb-quote-form-' . $config['widget_id'];
        
        ?>
        <div class="rb-quote-form-container" id="<?php echo esc_attr($widget_id); ?>" data-config="<?php echo esc_attr(json_encode($config)); ?>">
            
            <!-- En-tête du widget -->
            <?php if (!empty($config['widget_title']) || !empty($config['widget_subtitle'])): ?>
            <div class="rb-widget-header">
                <?php if (!empty($config['widget_title'])): ?>
                    <h2 class="rb-widget-title"><?php echo esc_html($config['widget_title']); ?></h2>
                <?php endif; ?>
                
                <?php if (!empty($config['widget_subtitle'])): ?>
                    <p class="rb-widget-subtitle"><?php echo esc_html($config['widget_subtitle']); ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Barre de progression -->
            <?php if ($config['show_progress_bar']): ?>
            <div class="rb-progress-bar" style="display: none;">
                <div class="rb-progress-steps">
                    <div class="rb-progress-step active" data-step="0">
                        <span class="rb-step-number">1</span>
                        <span class="rb-step-label"><?php _e('Service', 'restaurant-booking'); ?></span>
                    </div>
                    <div class="rb-progress-step" data-step="1">
                        <span class="rb-step-number">2</span>
                        <span class="rb-step-label"><?php _e('Forfait', 'restaurant-booking'); ?></span>
                    </div>
                    <div class="rb-progress-step" data-step="2">
                        <span class="rb-step-number">3</span>
                        <span class="rb-step-label"><?php _e('Repas', 'restaurant-booking'); ?></span>
                    </div>
                    <div class="rb-progress-step" data-step="3">
                        <span class="rb-step-number">4</span>
                        <span class="rb-step-label"><?php _e('Boissons', 'restaurant-booking'); ?></span>
                    </div>
                    <div class="rb-progress-step" data-step="4">
                        <span class="rb-step-number">5</span>
                        <span class="rb-step-label"><?php _e('Contact', 'restaurant-booking'); ?></span>
                    </div>
                </div>
                <div class="rb-progress-line">
                    <div class="rb-progress-fill"></div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Conteneur principal du formulaire -->
            <div class="rb-form-wrapper">
                
                <!-- Étape 0: Sélection du service -->
                <div class="rb-form-step active" data-step="0">
                    <div class="rb-step-header">
                        <h3 class="rb-step-title"><?php echo esc_html($config['service_selection_title']); ?></h3>
                    </div>
                    
                    <div class="rb-service-cards">
                        <!-- Card Restaurant -->
                        <div class="rb-service-card" data-service="restaurant">
                            <div class="rb-card-content">
                                <h4 class="rb-card-title"><?php echo esc_html($config['restaurant_card']['title']); ?></h4>
                                <p class="rb-card-subtitle"><?php echo esc_html($config['restaurant_card']['subtitle']); ?></p>
                                <div class="rb-card-description">
                                    <?php echo wp_kses_post($config['restaurant_card']['description']); ?>
                                </div>
                            </div>
                            <div class="rb-card-footer">
                                <button type="button" class="rb-btn rb-btn-primary rb-select-service" data-service="restaurant">
                                    <?php _e('CHOISIR', 'restaurant-booking'); ?>
                                </button>
                            </div>
                        </div>

                        <!-- Card Remorque -->
                        <div class="rb-service-card" data-service="remorque">
                            <div class="rb-card-content">
                                <h4 class="rb-card-title"><?php echo esc_html($config['remorque_card']['title']); ?></h4>
                                <p class="rb-card-subtitle"><?php echo esc_html($config['remorque_card']['subtitle']); ?></p>
                                <div class="rb-card-description">
                                    <?php echo wp_kses_post($config['remorque_card']['description']); ?>
                                </div>
                            </div>
                            <div class="rb-card-footer">
                                <button type="button" class="rb-btn rb-btn-primary rb-select-service" data-service="remorque">
                                    <?php _e('CHOISIR', 'restaurant-booking'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Les autres étapes seront chargées dynamiquement via AJAX -->
                <div class="rb-dynamic-steps"></div>

                <!-- Messages d'erreur et de succès -->
                <div class="rb-messages">
                    <div class="rb-message rb-message-error" style="display: none;"></div>
                    <div class="rb-message rb-message-success" style="display: none;"></div>
                    <div class="rb-message rb-message-loading" style="display: none;">
                        <span class="rb-loading-spinner"></span>
                        <?php echo esc_html($config['loading_message']); ?>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="rb-form-navigation" style="display: none;">
                    <button type="button" class="rb-btn rb-btn-secondary rb-btn-prev">
                        <?php _e('Précédent', 'restaurant-booking'); ?>
                    </button>
                    <button type="button" class="rb-btn rb-btn-primary rb-btn-next">
                        <?php _e('Suivant', 'restaurant-booking'); ?>
                    </button>
                    <button type="button" class="rb-btn rb-btn-accent rb-btn-submit" style="display: none;">
                        <?php _e('OBTENIR MON DEVIS ESTIMATIF', 'restaurant-booking'); ?>
                    </button>
                </div>
            </div>

            <!-- Calculateur de prix -->
            <?php if ($config['calculator_position'] !== 'hidden'): ?>
            <div class="rb-price-calculator rb-calculator-<?php echo esc_attr($config['calculator_position']); ?>" style="display: none;">
                <div class="rb-calculator-header">
                    <h4><?php _e('Montant estimatif', 'restaurant-booking'); ?></h4>
                </div>
                <div class="rb-calculator-content">
                    <div class="rb-price-breakdown">
                        <div class="rb-price-line">
                            <span class="rb-price-label"><?php _e('Forfait de base', 'restaurant-booking'); ?></span>
                            <span class="rb-price-value" data-price="base">0,00 €</span>
                        </div>
                        <div class="rb-price-line">
                            <span class="rb-price-label"><?php _e('Suppléments', 'restaurant-booking'); ?></span>
                            <span class="rb-price-value" data-price="supplements">0,00 €</span>
                        </div>
                        <div class="rb-price-line">
                            <span class="rb-price-label"><?php _e('Produits', 'restaurant-booking'); ?></span>
                            <span class="rb-price-value" data-price="products">0,00 €</span>
                        </div>
                    </div>
                    <div class="rb-price-total-line">
                        <span class="rb-price-label"><?php _e('Total estimé', 'restaurant-booking'); ?></span>
                        <span class="rb-price-total">0,00 €</span>
                    </div>
                    <div class="rb-price-disclaimer">
                        <small><?php _e('Montant indiqué estimatif', 'restaurant-booking'); ?></small>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <!-- Données cachées pour JavaScript -->
        <script type="application/json" id="<?php echo esc_attr($widget_id); ?>-data">
        <?php echo json_encode(array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('restaurant_booking_quote_form'),
            'config' => $config,
            'texts' => array(
                'error_required' => __('Ce champ est obligatoire', 'restaurant-booking'),
                'error_invalid_date' => __('Date invalide', 'restaurant-booking'),
                'error_min_guests' => __('Nombre minimum de convives non respecté', 'restaurant-booking'),
                'error_max_guests' => __('Nombre maximum de convives dépassé', 'restaurant-booking'),
                'error_network' => __('Erreur de connexion. Veuillez réessayer.', 'restaurant-booking'),
                'loading' => __('Chargement...', 'restaurant-booking'),
                'calculating' => __('Calcul en cours...', 'restaurant-booking'),
            )
        )); ?>
        </script>
        <?php
    }

    /**
     * Rendu du contenu dans l'éditeur
     */
    protected function content_template()
    {
        ?>
        <#
        var showProgressBar = settings.show_progress_bar === 'yes';
        #>
        
        <div class="rb-quote-form-container">
            <# if (settings.widget_title || settings.widget_subtitle) { #>
            <div class="rb-widget-header">
                <# if (settings.widget_title) { #>
                    <h2 class="rb-widget-title">{{{ settings.widget_title }}}</h2>
                <# } #>
                
                <# if (settings.widget_subtitle) { #>
                    <p class="rb-widget-subtitle">{{{ settings.widget_subtitle }}}</p>
                <# } #>
            </div>
            <# } #>

            <# if (showProgressBar) { #>
            <div class="rb-progress-bar">
                <div class="rb-progress-steps">
                    <div class="rb-progress-step active">
                        <span class="rb-step-number">1</span>
                        <span class="rb-step-label"><?php _e('Service', 'restaurant-booking'); ?></span>
                    </div>
                    <div class="rb-progress-step">
                        <span class="rb-step-number">2</span>
                        <span class="rb-step-label"><?php _e('Forfait', 'restaurant-booking'); ?></span>
                    </div>
                    <div class="rb-progress-step">
                        <span class="rb-step-number">3</span>
                        <span class="rb-step-label"><?php _e('Repas', 'restaurant-booking'); ?></span>
                    </div>
                    <div class="rb-progress-step">
                        <span class="rb-step-number">4</span>
                        <span class="rb-step-label"><?php _e('Boissons', 'restaurant-booking'); ?></span>
                    </div>
                    <div class="rb-progress-step">
                        <span class="rb-step-number">5</span>
                        <span class="rb-step-label"><?php _e('Contact', 'restaurant-booking'); ?></span>
                    </div>
                </div>
                <div class="rb-progress-line">
                    <div class="rb-progress-fill"></div>
                </div>
            </div>
            <# } #>

            <div class="rb-form-wrapper">
                <div class="rb-form-step active">
                    <div class="rb-step-header">
                        <h3 class="rb-step-title">{{{ settings.service_selection_title }}}</h3>
                    </div>
                    
                    <div class="rb-service-cards">
                        <div class="rb-service-card">
                            <div class="rb-card-content">
                                <h4 class="rb-card-title">{{{ settings.restaurant_card_title }}}</h4>
                                <p class="rb-card-subtitle">{{{ settings.restaurant_card_subtitle }}}</p>
                                <div class="rb-card-description">
                                    {{{ settings.restaurant_card_description }}}
                                </div>
                            </div>
                            <div class="rb-card-footer">
                                <button type="button" class="rb-btn rb-btn-primary">
                                    <?php _e('CHOISIR', 'restaurant-booking'); ?>
                                </button>
                            </div>
                        </div>

                        <div class="rb-service-card">
                            <div class="rb-card-content">
                                <h4 class="rb-card-title">{{{ settings.remorque_card_title }}}</h4>
                                <p class="rb-card-subtitle">{{{ settings.remorque_card_subtitle }}}</p>
                                <div class="rb-card-description">
                                    {{{ settings.remorque_card_description }}}
                                </div>
                            </div>
                            <div class="rb-card-footer">
                                <button type="button" class="rb-btn rb-btn-primary">
                                    <?php _e('CHOISIR', 'restaurant-booking'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div style="margin-top: 20px; padding: 20px; background: #f8f9fa; border-radius: 8px; text-align: center; color: #666;">
                <div style="font-size: 48px; margin-bottom: 20px;">📝</div>
                <h3 style="margin: 0 0 10px 0; color: #333;">
                    <?php _e('Formulaire de devis unifié v2', 'restaurant-booking'); ?>
                </h3>
                <p style="margin: 0; font-size: 14px;">
                    <?php _e('Le formulaire interactif sera affiché ici sur le site en direct', 'restaurant-booking'); ?>
                </p>
            </div>
        </div>
        <?php
    }
}
