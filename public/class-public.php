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
        
        // Enregistrer les shortcodes
        add_shortcode('restaurant_booking_form', array($this, 'render_shortcode_form'));
    }

    /**
     * Rendu du shortcode de formulaire
     */
    public function render_shortcode_form($atts)
    {
        // Paramètres par défaut
        $atts = shortcode_atts(array(
            'type' => 'restaurant', // restaurant ou remorque
            'class' => '',
            'id' => ''
        ), $atts, 'restaurant_booking_form');

        // Sanitiser les paramètres
        $type = sanitize_text_field($atts['type']);
        $class = sanitize_html_class($atts['class']);
        $id = sanitize_html_class($atts['id']);

        // Valider le type
        if (!in_array($type, array('restaurant', 'remorque'))) {
            $type = 'restaurant';
        }

        // Générer un ID unique si non fourni
        if (empty($id)) {
            $id = 'restaurant-booking-form-' . uniqid();
        }

        // Buffer de sortie
        ob_start();

        // Wrapper avec classes et ID
        echo '<div class="restaurant-booking-shortcode-wrapper ' . esc_attr($class) . '" id="' . esc_attr($id) . '">';

        // Rendu selon le type
        if ($type === 'restaurant') {
            if (class_exists('RestaurantBooking_Quote_Form_Restaurant')) {
                $form_instance = RestaurantBooking_Quote_Form_Restaurant::get_instance();
                $form_instance->render_form();
            } else {
                echo '<div class="restaurant-booking-error">';
                echo '<p>' . __('Erreur : Le formulaire restaurant n\'est pas disponible.', 'restaurant-booking') . '</p>';
                echo '</div>';
            }
        } elseif ($type === 'remorque') {
            if (class_exists('RestaurantBooking_Quote_Form_Remorque')) {
                $form_instance = RestaurantBooking_Quote_Form_Remorque::get_instance();
                $form_instance->render_form();
            } else {
                echo '<div class="restaurant-booking-error">';
                echo '<p>' . __('Erreur : Le formulaire remorque n\'est pas disponible.', 'restaurant-booking') . '</p>';
                echo '</div>';
            }
        }

        echo '</div>';

        return ob_get_clean();
    }
}
