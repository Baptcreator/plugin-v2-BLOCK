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

        // Vérifier l'état de la migration v2
        if (class_exists('RestaurantBooking_Migration_V2')) {
            $migration_status = RestaurantBooking_Migration_V2::get_migration_status();
            
            // Si la migration v2 est terminée, utiliser le nouveau widget unifié
            if ($migration_status['migration_completed']) {
                require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'elementor/widgets/quote-form-unified-widget.php';
                $widgets_manager->register(new \RestaurantBooking_Quote_Form_Unified_Widget());
            }
        }

        // Inclure les anciens widgets (rétrocompatibilité)
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'elementor/widgets/quote-form-widget.php';
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'elementor/widgets/quote-form-restaurant-widget.php';
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'elementor/widgets/quote-form-remorque-widget.php';

        // Enregistrer les anciens widgets
        $widgets_manager->register(new \RestaurantBooking_Quote_Form_Widget());
        $widgets_manager->register(new \RestaurantBooking_Quote_Form_Restaurant_Widget());
        $widgets_manager->register(new \RestaurantBooking_Quote_Form_Remorque_Widget());
    }
}