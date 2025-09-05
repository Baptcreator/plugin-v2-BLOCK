<?php
/**
 * Classe des widgets Elementor
 *
 * @package RestaurantBooking
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Elementor_Widgets
{
    /**
     * Enregistrer les widgets
     */
    public function register_widgets($widgets_manager)
    {
        // Vérifier qu'Elementor est chargé
        if (!did_action('elementor/loaded')) {
            return;
        }

        // Inclure les widgets selon le cahier des charges
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'elementor/widgets/quote-form-widget.php';
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'elementor/widgets/quote-form-restaurant-widget.php';
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'elementor/widgets/quote-form-remorque-widget.php';

        // Enregistrer les widgets
        $widgets_manager->register(new \RestaurantBooking_Quote_Form_Widget());
        $widgets_manager->register(new \RestaurantBooking_Quote_Form_Restaurant_Widget());
        $widgets_manager->register(new \RestaurantBooking_Quote_Form_Remorque_Widget());
    }
}