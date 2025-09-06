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
        $migration_completed = false;
        if (class_exists('RestaurantBooking_Migration_V2')) {
            $migration_status = RestaurantBooking_Migration_V2::get_migration_status();
            $migration_completed = $migration_status['migration_completed'];
        }
        
        if ($migration_completed) {
            // Migration v2 terminée : utiliser UNIQUEMENT le nouveau widget unifié
            if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'elementor/widgets/quote-form-unified-widget.php')) {
                require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'elementor/widgets/quote-form-unified-widget.php';
                if (class_exists('RestaurantBooking_Quote_Form_Unified_Widget')) {
                    $widgets_manager->register(new \RestaurantBooking_Quote_Form_Unified_Widget());
                }
            }
        } else {
            // Migration v2 non terminée : utiliser les anciens widgets (rétrocompatibilité)
            if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'elementor/widgets/quote-form-widget.php')) {
                require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'elementor/widgets/quote-form-widget.php';
                if (class_exists('RestaurantBooking_Quote_Form_Widget')) {
                    $widgets_manager->register(new \RestaurantBooking_Quote_Form_Widget());
                }
            }
            
            if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'elementor/widgets/quote-form-restaurant-widget.php')) {
                require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'elementor/widgets/quote-form-restaurant-widget.php';
                if (class_exists('RestaurantBooking_Quote_Form_Restaurant_Widget')) {
                    $widgets_manager->register(new \RestaurantBooking_Quote_Form_Restaurant_Widget());
                }
            }
            
            if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'elementor/widgets/quote-form-remorque-widget.php')) {
                require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'elementor/widgets/quote-form-remorque-widget.php';
                if (class_exists('RestaurantBooking_Quote_Form_Remorque_Widget')) {
                    $widgets_manager->register(new \RestaurantBooking_Quote_Form_Remorque_Widget());
                }
            }
        }
    }
}