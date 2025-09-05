<?php
/**
 * Fichier de protection du répertoire
 * Empêche la navigation directe dans les dossiers du plugin
 *
 * @package RestaurantBooking
 * @since 1.0.0
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Rediriger vers la page d'accueil
wp_redirect(home_url());
exit;
