<?php
/**
 * Widget Elementor - Formulaire de devis
 *
 * @package RestaurantBooking
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Quote_Form_Widget extends \Elementor\Widget_Base
{
    /**
     * Obtenir le nom du widget
     */
    public function get_name()
    {
        return 'restaurant_booking_quote_form';
    }

    /**
     * Obtenir le titre du widget
     */
    public function get_title()
    {
        return __('Formulaire de Devis', 'restaurant-booking');
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
        return ['restaurant', 'booking', 'devis', 'formulaire', 'block', 'co'];
    }

    /**
     * Enregistrer les contrôles du widget
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
                'default' => __('Demande de devis', 'restaurant-booking'),
                'placeholder' => __('Saisissez le titre', 'restaurant-booking'),
            ]
        );

        $this->add_control(
            'form_subtitle',
            [
                'label' => __('Sous-titre', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'default' => __('Obtenez votre devis personnalisé en quelques minutes', 'restaurant-booking'),
                'placeholder' => __('Saisissez le sous-titre', 'restaurant-booking'),
            ]
        );

        $this->add_control(
            'show_title',
            [
                'label' => __('Afficher le titre', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Oui', 'restaurant-booking'),
                'label_off' => __('Non', 'restaurant-booking'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_subtitle',
            [
                'label' => __('Afficher le sous-titre', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Oui', 'restaurant-booking'),
                'label_off' => __('Non', 'restaurant-booking'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'button_text',
            [
                'label' => __('Texte du bouton', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Demander un devis', 'restaurant-booking'),
                'placeholder' => __('Saisissez le texte du bouton', 'restaurant-booking'),
            ]
        );

        $this->add_control(
            'success_message',
            [
                'label' => __('Message de succès', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'default' => __('Votre demande a été envoyée avec succès ! Nous vous répondrons dans les plus brefs délais.', 'restaurant-booking'),
                'placeholder' => __('Message affiché après envoi', 'restaurant-booking'),
            ]
        );

        $this->end_controls_section();

        // Section Style - Titre
        $this->start_controls_section(
            'title_style_section',
            [
                'label' => __('Style du titre', 'restaurant-booking'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_title' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label' => __('Couleur du titre', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .form-title' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'selector' => '{{WRAPPER}} .form-title',
            ]
        );

        $this->add_responsive_control(
            'title_align',
            [
                'label' => __('Alignement', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => __('Gauche', 'restaurant-booking'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => __('Centre', 'restaurant-booking'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => __('Droite', 'restaurant-booking'),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'default' => 'center',
                'selectors' => [
                    '{{WRAPPER}} .form-title' => 'text-align: {{VALUE}}',
                ],
            ]
        );

        $this->end_controls_section();

        // Section Style - Sous-titre
        $this->start_controls_section(
            'subtitle_style_section',
            [
                'label' => __('Style du sous-titre', 'restaurant-booking'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_subtitle' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'subtitle_color',
            [
                'label' => __('Couleur du sous-titre', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .form-subtitle' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'subtitle_typography',
                'selector' => '{{WRAPPER}} .form-subtitle',
            ]
        );

        $this->add_responsive_control(
            'subtitle_align',
            [
                'label' => __('Alignement', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => __('Gauche', 'restaurant-booking'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => __('Centre', 'restaurant-booking'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => __('Droite', 'restaurant-booking'),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'default' => 'center',
                'selectors' => [
                    '{{WRAPPER}} .form-subtitle' => 'text-align: {{VALUE}}',
                ],
            ]
        );

        $this->end_controls_section();

        // Section Style - Formulaire
        $this->start_controls_section(
            'form_style_section',
            [
                'label' => __('Style du formulaire', 'restaurant-booking'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'form_background',
            [
                'label' => __('Couleur de fond', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .restaurant-booking-form-container' => 'background-color: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'form_border',
                'selector' => '{{WRAPPER}} .restaurant-booking-form-container',
            ]
        );

        $this->add_responsive_control(
            'form_border_radius',
            [
                'label' => __('Rayon de bordure', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .restaurant-booking-form-container' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'form_box_shadow',
                'selector' => '{{WRAPPER}} .restaurant-booking-form-container',
            ]
        );

        $this->add_responsive_control(
            'form_padding',
            [
                'label' => __('Espacement interne', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .restaurant-booking-form-container' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Section Style - Bouton
        $this->start_controls_section(
            'button_style_section',
            [
                'label' => __('Style du bouton', 'restaurant-booking'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'button_typography',
                'selector' => '{{WRAPPER}} .submit-button',
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
                    '{{WRAPPER}} .submit-button' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'button_background',
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .submit-button',
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
                    '{{WRAPPER}} .submit-button:hover' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'button_hover_background',
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .submit-button:hover',
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'button_border',
                'selector' => '{{WRAPPER}} .submit-button',
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
                    '{{WRAPPER}} .submit-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'button_padding',
            [
                'label' => __('Espacement interne', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .submit-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
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

        // Vérifier que la classe du formulaire existe
        if (!class_exists('RestaurantBooking_Quote_Form')) {
            echo '<div style="padding: 20px; background: #f8d7da; color: #721c24; border-radius: 5px;">';
            echo __('Erreur : La classe RestaurantBooking_Quote_Form n\'est pas disponible.', 'restaurant-booking');
            echo '</div>';
            return;
        }

        // Préparer les paramètres pour le formulaire
        $form_settings = array(
            'title' => $settings['form_title'],
            'subtitle' => $settings['form_subtitle'],
            'show_title' => ($settings['show_title'] === 'yes'),
            'show_subtitle' => ($settings['show_subtitle'] === 'yes'),
            'button_text' => $settings['button_text'],
            'success_message' => $settings['success_message'],
            'form_style' => 'elementor'
        );

        // Obtenir l'instance du formulaire et l'afficher
        $quote_form = RestaurantBooking_Quote_Form::get_instance();
        echo $quote_form->render_form($form_settings);
    }

    /**
     * Rendu du contenu dans l'éditeur
     */
    protected function content_template()
    {
        ?>
        <#
        var showTitle = settings.show_title === 'yes';
        var showSubtitle = settings.show_subtitle === 'yes';
        #>
        
        <div class="restaurant-booking-form-container">
            <# if (showTitle && settings.form_title) { #>
                <h2 class="form-title">{{{ settings.form_title }}}</h2>
            <# } #>
            
            <# if (showSubtitle && settings.form_subtitle) { #>
                <p class="form-subtitle">{{{ settings.form_subtitle }}}</p>
            <# } #>
            
            <div style="padding: 40px; background: #f8f9fa; border-radius: 8px; text-align: center; color: #666;">
                <div style="font-size: 48px; margin-bottom: 20px;">📝</div>
                <h3 style="margin: 0 0 10px 0; color: #333;">
                    <?php _e('Formulaire de devis', 'restaurant-booking'); ?>
                </h3>
                <p style="margin: 0; font-size: 14px;">
                    <?php _e('Le formulaire sera affiché ici sur le site en direct', 'restaurant-booking'); ?>
                </p>
                <div style="margin-top: 20px;">
                    <button type="button" class="submit-button" style="background: linear-gradient(135deg, #3498db, #2980b9); color: white; border: none; padding: 12px 30px; border-radius: 25px; font-weight: 600;">
                        {{{ settings.button_text || '<?php _e('Demander un devis', 'restaurant-booking'); ?>' }}}
                    </button>
                </div>
            </div>
        </div>
        <?php
    }
}

