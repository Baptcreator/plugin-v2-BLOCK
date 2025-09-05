<?php
/**
 * Classe d'administration des produits
 *
 * @package RestaurantBooking
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Products_Admin
{
    /**
     * Afficher la liste des produits
     */
    public function display_list()
    {
        echo '<div class="wrap">';
        echo '<h1>' . __('Gestion des produits', 'restaurant-booking') . '</h1>';
        echo '<p>' . __('Interface de gestion des produits en cours de développement...', 'restaurant-booking') . '</p>';
        echo '</div>';
    }
}
