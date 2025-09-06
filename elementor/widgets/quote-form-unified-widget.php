<?php
/**
 * Widget Elementor - Formulaire de Devis Unifié v2
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
     * Nom du widget
     */
    public function get_name()
    {
        return 'restaurant-booking-unified-form';
    }

    /**
     * Titre du widget
     */
    public function get_title()
    {
        return __('Formulaire de Devis Unifié v2', 'restaurant-booking');
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
        return ['restaurant', 'booking', 'devis', 'formulaire', 'privatisation', 'remorque'];
    }

    /**
     * Scripts nécessaires
     */
    public function get_script_depends()
    {
        return ['restaurant-booking-unified-form'];
    }

    /**
     * Styles nécessaires
     */
    public function get_style_depends()
    {
        return ['restaurant-booking-unified-form'];
    }

    /**
     * Contrôles du widget
     */
    protected function register_controls()
    {
        // Section Contenu
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Contenu', 'restaurant-booking'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'form_title',
            [
                'label' => __('Titre du formulaire', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Demande de Devis', 'restaurant-booking'),
                'placeholder' => __('Entrez le titre...', 'restaurant-booking'),
            ]
        );

        $this->add_control(
            'form_subtitle',
            [
                'label' => __('Sous-titre', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'default' => __('Choisissez votre service de privatisation', 'restaurant-booking'),
                'placeholder' => __('Entrez le sous-titre...', 'restaurant-booking'),
            ]
        );

        $this->add_control(
            'show_calculator',
            [
                'label' => __('Afficher le calculateur de prix', 'restaurant-booking'),
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
                'label' => __('Position du calculateur', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'right',
                'options' => [
                    'left' => __('Gauche', 'restaurant-booking'),
                    'right' => __('Droite', 'restaurant-booking'),
                    'bottom' => __('Bas', 'restaurant-booking'),
                ],
                'condition' => [
                    'show_calculator' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // Section Style
        $this->start_controls_section(
            'style_section',
            [
                'label' => __('Style', 'restaurant-booking'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'primary_color',
            [
                'label' => __('Couleur primaire', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#243127',
            ]
        );

        $this->add_control(
            'secondary_color',
            [
                'label' => __('Couleur secondaire', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#FFB404',
            ]
        );

        $this->add_control(
            'accent_color',
            [
                'label' => __('Couleur d\'accent', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#EF3D1D',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'label' => __('Typographie du titre', 'restaurant-booking'),
                'selector' => '{{WRAPPER}} .rb-form-title',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'subtitle_typography',
                'label' => __('Typographie du sous-titre', 'restaurant-booking'),
                'selector' => '{{WRAPPER}} .rb-form-subtitle',
            ]
        );

        $this->end_controls_section();

        // Section Avancé
        $this->start_controls_section(
            'advanced_section',
            [
                'label' => __('Avancé', 'restaurant-booking'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'enable_analytics',
            [
                'label' => __('Activer le suivi analytics', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Oui', 'restaurant-booking'),
                'label_off' => __('Non', 'restaurant-booking'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'custom_css_class',
            [
                'label' => __('Classe CSS personnalisée', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'placeholder' => __('ma-classe-personnalisee', 'restaurant-booking'),
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
        
        // Configuration du formulaire
        $form_config = array(
            'title' => $settings['form_title'] ?? __('Demande de Devis', 'restaurant-booking'),
            'subtitle' => $settings['form_subtitle'] ?? __('Choisissez votre service de privatisation', 'restaurant-booking'),
            'show_calculator' => $settings['show_calculator'] === 'yes',
            'calculator_position' => $settings['calculator_position'] ?? 'right',
            'enable_analytics' => $settings['enable_analytics'] === 'yes',
            'custom_css_class' => $settings['custom_css_class'] ?? '',
            'theme_colors' => array(
                'primary' => $settings['primary_color'] ?? '#243127',
                'secondary' => $settings['secondary_color'] ?? '#FFB404',
                'accent' => $settings['accent_color'] ?? '#EF3D1D',
            ),
            'widget_id' => $this->get_id(),
        );

        // Ajouter les variables CSS personnalisées
        echo '<div style="--rb-primary-color: ' . esc_attr($form_config['theme_colors']['primary']) . '; --rb-secondary-color: ' . esc_attr($form_config['theme_colors']['secondary']) . '; --rb-accent-color: ' . esc_attr($form_config['theme_colors']['accent']) . ';">';
        
        // Rendu du formulaire unifié
        $this->render_unified_form($form_config);
        
        echo '</div>';
    }

    /**
     * Rendu du formulaire unifié
     */
    private function render_unified_form($config)
    {
        $css_classes = array('rb-unified-form');
        if (!empty($config['custom_css_class'])) {
            $css_classes[] = sanitize_html_class($config['custom_css_class']);
        }
        ?>
        <div class="<?php echo implode(' ', $css_classes); ?>" id="rb-form-<?php echo esc_attr($config['widget_id']); ?>">
            
            <!-- En-tête du formulaire -->
            <div class="rb-form-header">
                <?php if (!empty($config['title'])): ?>
                    <h2 class="rb-form-title"><?php echo esc_html($config['title']); ?></h2>
                <?php endif; ?>
                
                <?php if (!empty($config['subtitle'])): ?>
                    <p class="rb-form-subtitle"><?php echo esc_html($config['subtitle']); ?></p>
                <?php endif; ?>
            </div>

            <!-- Container principal -->
            <div class="rb-form-container <?php echo $config['calculator_position']; ?>">
                
                <!-- Formulaire principal -->
                <div class="rb-form-main">
                    
                    <!-- Étape 0: Sélection du service -->
                    <div class="rb-form-step rb-step-0 active" data-step="0">
                        <div class="rb-service-selection">
                            
                            <!-- Card Restaurant -->
                            <div class="rb-service-card" data-service="restaurant">
                                <div class="rb-card-icon">🏠</div>
                                <h3><?php echo esc_html(get_option('widget_restaurant_card_title', 'PRIVATISATION DU RESTAURANT')); ?></h3>
                                <p class="rb-card-subtitle"><?php echo esc_html(get_option('widget_restaurant_card_subtitle', 'De 10 à 30 personnes')); ?></p>
                                <p class="rb-card-description"><?php echo esc_html(get_option('widget_restaurant_card_description', 'Privatisez notre restaurant pour vos événements intimes et profitez d\'un service personnalisé dans un cadre chaleureux.')); ?></p>
                                <button type="button" class="rb-select-service-btn">
                                    <?php _e('Choisir ce service', 'restaurant-booking'); ?>
                                </button>
                            </div>

                            <!-- Card Remorque -->
                            <div class="rb-service-card" data-service="remorque">
                                <div class="rb-card-icon">🚚</div>
                                <h3><?php echo esc_html(get_option('widget_remorque_card_title', 'PRIVATISATION DE LA REMORQUE BLOCK')); ?></h3>
                                <p class="rb-card-subtitle"><?php echo esc_html(get_option('widget_remorque_card_subtitle', 'À partir de 20 personnes')); ?></p>
                                <p class="rb-card-description"><?php echo esc_html(get_option('widget_remorque_card_description', 'Notre remorque mobile se déplace pour vos événements extérieurs et grandes réceptions.')); ?></p>
                                <button type="button" class="rb-select-service-btn">
                                    <?php _e('Choisir ce service', 'restaurant-booking'); ?>
                                </button>
                            </div>

                        </div>
                    </div>

                    <!-- Étapes dynamiques (seront générées par JavaScript) -->
                    <div class="rb-dynamic-steps"></div>

                    <!-- Navigation -->
                    <div class="rb-form-navigation" style="display: none;">
                        <button type="button" class="rb-btn rb-btn-secondary rb-prev-btn">
                            <?php _e('Précédent', 'restaurant-booking'); ?>
                        </button>
                        <button type="button" class="rb-btn rb-btn-primary rb-next-btn">
                            <?php _e('Suivant', 'restaurant-booking'); ?>
                        </button>
                        <button type="submit" class="rb-btn rb-btn-primary rb-submit-btn" style="display: none;">
                            <?php _e('Envoyer la demande', 'restaurant-booking'); ?>
                        </button>
                    </div>

                </div>

                <!-- Calculateur de prix (si activé) -->
                <?php if ($config['show_calculator']): ?>
                <div class="rb-price-calculator">
                    <div class="rb-calculator-header">
                        <h3><?php _e('Estimation du prix', 'restaurant-booking'); ?></h3>
                    </div>
                    <div class="rb-calculator-content">
                        <div class="rb-price-line">
                            <span><?php _e('Forfait de base', 'restaurant-booking'); ?></span>
                            <span class="rb-price-value" data-type="base">-</span>
                        </div>
                        <div class="rb-price-line">
                            <span><?php _e('Suppléments', 'restaurant-booking'); ?></span>
                            <span class="rb-price-value" data-type="supplements">-</span>
                        </div>
                        <div class="rb-price-line rb-total-line">
                            <span><?php _e('Total estimé', 'restaurant-booking'); ?></span>
                            <span class="rb-price-value rb-total-price">-</span>
                        </div>
                        <p class="rb-price-note">
                            <?php _e('* Prix indicatif, devis personnalisé sur demande', 'restaurant-booking'); ?>
                        </p>
                    </div>
                </div>
                <?php endif; ?>

            </div>

            <!-- Indicateur de progression -->
            <div class="rb-progress-indicator">
                <div class="rb-progress-bar">
                    <div class="rb-progress-fill"></div>
                </div>
                <div class="rb-progress-steps">
                    <span class="rb-progress-step active"><?php _e('Service', 'restaurant-booking'); ?></span>
                    <span class="rb-progress-step"><?php _e('Informations', 'restaurant-booking'); ?></span>
                    <span class="rb-progress-step"><?php _e('Options', 'restaurant-booking'); ?></span>
                    <span class="rb-progress-step"><?php _e('Contact', 'restaurant-booking'); ?></span>
                </div>
            </div>

        </div>

        <!-- JavaScript de configuration -->
        <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof RestaurantBookingUnifiedForm !== 'undefined') {
                new RestaurantBookingUnifiedForm('rb-form-<?php echo esc_js($config['widget_id']); ?>', <?php echo json_encode($config); ?>);
            }
        });
        </script>
        <?php
    }

    /**
     * Rendu pour l'éditeur Elementor
     */
    protected function content_template()
    {
        ?>
        <div class="rb-unified-form rb-editor-mode">
            <div class="rb-form-header">
                <# if (settings.form_title) { #>
                    <h2 class="rb-form-title">{{{ settings.form_title }}}</h2>
                <# } #>
                <# if (settings.form_subtitle) { #>
                    <p class="rb-form-subtitle">{{{ settings.form_subtitle }}}</p>
                <# } #>
            </div>
            
            <div class="rb-editor-placeholder">
                <div class="rb-placeholder-icon">📝</div>
                <h3><?php _e('Formulaire de Devis Unifié v2', 'restaurant-booking'); ?></h3>
                <p><?php _e('Le formulaire interactif sera affiché sur le front-end', 'restaurant-booking'); ?></p>
                <p><strong><?php _e('Services disponibles:', 'restaurant-booking'); ?></strong></p>
                <ul>
                    <li>🏠 <?php _e('Privatisation du Restaurant', 'restaurant-booking'); ?></li>
                    <li>🚚 <?php _e('Privatisation de la Remorque Block', 'restaurant-booking'); ?></li>
                </ul>
            </div>
        </div>
        
        <style>
        .rb-editor-placeholder {
            text-align: center;
            padding: 40px 20px;
            border: 2px dashed #ddd;
            border-radius: 8px;
            background: #f9f9f9;
        }
        .rb-placeholder-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }
        .rb-editor-placeholder h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .rb-editor-placeholder p {
            margin: 5px 0;
            color: #666;
        }
        .rb-editor-placeholder ul {
            list-style: none;
            padding: 0;
            margin: 15px 0 0 0;
        }
        .rb-editor-placeholder li {
            margin: 5px 0;
            color: #555;
        }
        </style>
        <?php
    }
}
?>