<?php
/**
 * Widget Elementor - Formulaire de devis Restaurant (4 étapes)
 *
 * @package RestaurantBooking
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Quote_Form_Restaurant_Widget extends \Elementor\Widget_Base
{
    /**
     * Obtenir le nom du widget
     */
    public function get_name()
    {
        return 'restaurant_booking_quote_form_restaurant';
    }

    /**
     * Obtenir le titre du widget
     */
    public function get_title()
    {
        return __('Formulaire Devis Restaurant (4 étapes)', 'restaurant-booking');
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
        return ['restaurant', 'booking', 'devis', 'privatisation', 'block', 'co'];
    }

    /**
     * Enregistrer les contrôles du widget
     */
    protected function register_controls()
    {
        // Section Configuration
        $this->start_controls_section(
            'config_section',
            [
                'label' => __('Configuration Restaurant', 'restaurant-booking'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'service_info',
            [
                'label' => __('Service configuré', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::RAW_HTML,
                'raw' => '<div style="padding: 15px; background: #e8f5e8; border-left: 4px solid #4caf50; margin: 10px 0;">
                    <strong>🍽️ Service Restaurant</strong><br>
                    • 4 étapes : Forfait, Repas, Boissons, Contact<br>
                    • 10-30 personnes<br>
                    • Durée max : 4 heures<br>
                    • Pas de code postal requis
                </div>',
            ]
        );

        $this->end_controls_section();

        // Section Style - Couleurs du thème
        $this->start_controls_section(
            'theme_colors_section',
            [
                'label' => __('Couleurs du thème Block', 'restaurant-booking'),
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
                    '{{WRAPPER}} .restaurant-plugin-btn-primary' => 'background-color: {{VALUE}}',
                    '{{WRAPPER}} .restaurant-plugin-step-active' => 'background-color: {{VALUE}}',
                    '{{WRAPPER}} .restaurant-plugin-progress-bar' => 'background-color: {{VALUE}}',
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
                    '{{WRAPPER}} .restaurant-plugin-accent' => 'color: {{VALUE}}',
                    '{{WRAPPER}} .restaurant-plugin-highlight' => 'background-color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'accent_color',
            [
                'label' => __('Couleur accent', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#EF3D1D',
                'selectors' => [
                    '{{WRAPPER}} .restaurant-plugin-error' => 'color: {{VALUE}}',
                    '{{WRAPPER}} .restaurant-plugin-required' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->end_controls_section();

        // Section Style - Layout
        $this->start_controls_section(
            'layout_section',
            [
                'label' => __('Mise en page', 'restaurant-booking'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'form_width',
            [
                'label' => __('Largeur du formulaire', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 300,
                        'max' => 1200,
                    ],
                    '%' => [
                        'min' => 50,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'unit' => '%',
                    'size' => 100,
                ],
                'selectors' => [
                    '{{WRAPPER}} .restaurant-plugin-form-container' => 'max-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'calculator_position',
            [
                'label' => __('Position du calculateur de prix', 'restaurant-booking'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'bottom',
                'options' => [
                    'bottom' => __('En bas (sticky)', 'restaurant-booking'),
                    'right' => __('À droite', 'restaurant-booking'),
                    'top' => __('En haut', 'restaurant-booking'),
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
        if (!class_exists('RestaurantBooking_Quote_Form_Restaurant')) {
            echo '<div style="padding: 20px; background: #f8d7da; color: #721c24; border-radius: 20px;">';
            echo __('Erreur : La classe RestaurantBooking_Quote_Form_Restaurant n\'est pas disponible.', 'restaurant-booking');
            echo '</div>';
            return;
        }

        // Configuration du formulaire restaurant
        $form_config = array(
            'service_type' => 'restaurant',
            'steps' => 4,
            'min_guests' => 10,
            'max_guests' => 30,
            'max_hours' => 4,
            'require_postal_code' => false,
            'calculator_position' => $settings['calculator_position'],
            'theme_colors' => array(
                'primary' => $settings['primary_color'],
                'secondary' => $settings['secondary_color'],
                'accent' => $settings['accent_color'],
            )
        );

        // Obtenir l'instance du formulaire et l'afficher
        $quote_form = RestaurantBooking_Quote_Form_Restaurant::get_instance();
        echo $quote_form->render_form($form_config);
    }

    /**
     * Rendu du contenu dans l'éditeur
     */
    protected function content_template()
    {
        ?>
        <div class="restaurant-plugin-form-container" style="padding: 30px; background: #F6F2E7; border-radius: 20px; border: 2px solid #243127;">
            <div style="text-align: center; margin-bottom: 30px;">
                <div style="font-size: 48px; margin-bottom: 15px;">🍽️</div>
                <h3 style="margin: 0 0 10px 0; color: #243127; font-family: 'Fatkat', sans-serif; font-size: 24px;">
                    Formulaire Privatisation Restaurant
                </h3>
                <p style="margin: 0; color: #666; font-size: 14px;">
                    4 étapes : Forfait • Repas • Boissons • Contact
                </p>
            </div>
            
            <!-- Indicateur d'étapes -->
            <div style="display: flex; justify-content: center; margin-bottom: 30px;">
                <div style="display: flex; gap: 10px;">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: #243127; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold;">1</div>
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: #ddd; color: #666; display: flex; align-items: center; justify-content: center;">2</div>
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: #ddd; color: #666; display: flex; align-items: center; justify-content: center;">3</div>
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: #ddd; color: #666; display: flex; align-items: center; justify-content: center;">4</div>
                </div>
            </div>

            <div style="background: white; padding: 20px; border-radius: 20px; margin-bottom: 20px;">
                <h4 style="color: #243127; margin-bottom: 15px;">Étape 1/4 : Forfait de base</h4>
                <p style="color: #666; margin: 0;">Date • Convives (10-30) • Durée (2-4h)</p>
            </div>

            <!-- Calculateur de prix -->
            <div style="background: #243127; color: white; padding: 15px 20px; border-radius: 20px; text-align: center; position: sticky; bottom: 0;">
                <div style="font-size: 24px; font-weight: bold; font-family: 'Fatkat', sans-serif;">
                    1 250,00 € TTC
                </div>
                <div style="font-size: 12px; opacity: 0.8;">
                    (Montant indicatif estimatif)
                </div>
            </div>

            <div style="text-align: center; margin-top: 20px; font-size: 12px; color: #666;">
                Le formulaire sera affiché ici sur le site en direct
            </div>
        </div>
        <?php
    }
}
