<?php
/**
 * Classe de l'interface publique
 *
 * @package RestaurantBooking
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Public
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
        add_action('init', array($this, 'init'));
    }

    /**
     * Initialisation
     */
    public function init()
    {
        // Initialiser les formulaires spécialisés selon le cahier des charges
        if (class_exists('RestaurantBooking_Quote_Form_Restaurant')) {
            RestaurantBooking_Quote_Form_Restaurant::get_instance();
        }
        
        if (class_exists('RestaurantBooking_Quote_Form_Remorque')) {
            RestaurantBooking_Quote_Form_Remorque::get_instance();
        }
        
        // Conserver l'ancien formulaire pour compatibilité
        if (class_exists('RestaurantBooking_Quote_Form')) {
            RestaurantBooking_Quote_Form::get_instance();
        }
    }
}
