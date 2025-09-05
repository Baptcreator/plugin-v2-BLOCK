<?php
/**
 * Classe de gestion de la liste des devis
 *
 * @package RestaurantBooking
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Quotes_List
{
    /**
     * Afficher la liste des devis
     */
    public function display()
    {
        echo '<div class="wrap">';
        echo '<h1>' . __('Gestion des devis', 'restaurant-booking') . '</h1>';
        echo '<p>' . __('Interface de gestion des devis en cours de développement...', 'restaurant-booking') . '</p>';
        echo '</div>';
    }
}
