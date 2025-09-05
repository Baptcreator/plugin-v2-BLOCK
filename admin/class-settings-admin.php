<?php
/**
 * Classe d'administration des paramètres
 *
 * @package RestaurantBooking
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Settings_Admin
{
    /**
     * Afficher les paramètres
     */
    public function display($tab = 'pricing')
    {
        echo '<div class="wrap">';
        echo '<h1>' . __('Paramètres', 'restaurant-booking') . '</h1>';
        echo '<p>' . __('Interface de paramètres en cours de développement...', 'restaurant-booking') . '</p>';
        echo '</div>';
    }

    /**
     * Sauvegarder les paramètres
     */
    public function save_settings($data)
    {
        // Placeholder pour la sauvegarde
        return true;
    }
}
