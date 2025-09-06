<?php
/**
 * SCRIPT DE RÉPARATION PERMISSIONS - URGENCE
 * 
 * Uploadez ce fichier dans /wp-content/plugins/plugin v2 BLOCK/
 * Accédez à : https://block-streetfood.fr/wp-content/plugins/plugin%20v2%20BLOCK/fix-permissions-urgent.php?key=fix2024
 */

// Sécurité
if (!isset($_GET['key']) || $_GET['key'] !== 'fix2024') {
    die('Accès refusé. Utilisez: ?key=fix2024');
}

// Charger WordPress
$wp_load_paths = array(
    '../../../wp-load.php',
    '../../../../wp-load.php',
    '../../../../../wp-load.php'
);

foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once($path);
        break;
    }
}

if (!function_exists('wp_get_current_user')) {
    die('WordPress non trouvé');
}

if (!current_user_can('manage_options')) {
    die('Permissions insuffisantes');
}

echo '<h1>🔧 RÉPARATION PERMISSIONS URGENTE</h1>';
echo '<style>body{font-family:Arial,sans-serif;margin:20px;} .ok{color:green;} .error{color:red;} .info{color:blue;}</style>';

// FORCER LES PERMISSIONS
echo '<h2>🚀 Forçage des permissions...</h2>';

global $wp_roles;
if (!isset($wp_roles)) {
    $wp_roles = new WP_Roles();
}

// 1. Ajouter aux rôles
$admin_role = $wp_roles->get_role('administrator');
if ($admin_role) {
    $admin_role->add_cap('manage_restaurant_quotes');
    $admin_role->add_cap('manage_restaurant_products');
    $admin_role->add_cap('manage_restaurant_settings');
    $admin_role->add_cap('manage_restaurant_games');
    $admin_role->add_cap('manage_restaurant_categories');
    echo '<p class="ok">✅ Capacités ajoutées au rôle administrateur</p>';
} else {
    echo '<p class="error">❌ Rôle administrateur non trouvé</p>';
}

// 2. Forcer pour l'utilisateur actuel
$current_user = wp_get_current_user();
if ($current_user && $current_user->ID) {
    $current_user->add_cap('manage_restaurant_quotes');
    $current_user->add_cap('manage_restaurant_products');
    $current_user->add_cap('manage_restaurant_settings');
    $current_user->add_cap('manage_restaurant_games');
    $current_user->add_cap('manage_restaurant_categories');
    echo '<p class="ok">✅ Capacités ajoutées à votre utilisateur (' . esc_html($current_user->user_login) . ')</p>';
} else {
    echo '<p class="error">❌ Utilisateur actuel non trouvé</p>';
}

// 3. Vider le cache des permissions
wp_cache_delete($current_user->ID, 'user_meta');
wp_cache_delete($current_user->ID, 'users');

echo '<p class="ok">✅ Cache des permissions vidé</p>';

// 4. Vérification finale
echo '<h2>🔍 Vérification finale...</h2>';

$current_user = wp_get_current_user(); // Recharger l'utilisateur
$capabilities = array(
    'manage_options' => $current_user->has_cap('manage_options'),
    'manage_restaurant_products' => $current_user->has_cap('manage_restaurant_products'),
    'manage_restaurant_games' => $current_user->has_cap('manage_restaurant_games'),
);

foreach ($capabilities as $cap => $has_cap) {
    if ($has_cap) {
        echo '<p class="ok">✅ ' . $cap . '</p>';
    } else {
        echo '<p class="error">❌ ' . $cap . '</p>';
    }
}

echo '<h2>🎯 ÉTAPES SUIVANTES</h2>';
echo '<ol>';
echo '<li><strong>Actualisez votre page admin</strong> (F5)</li>';
echo '<li><strong>Déconnectez-vous et reconnectez-vous</strong> à WordPress</li>';
echo '<li><strong>Essayez d\'accéder</strong> à <a href="/wp-admin/admin.php?page=restaurant-booking-games" target="_blank">Restaurant Devis > Jeux</a></li>';
echo '<li><strong>Supprimez ce fichier</strong> après utilisation</li>';
echo '</ol>';

echo '<hr>';
echo '<p><strong>⚠️ IMPORTANT :</strong> Supprimez ce fichier après utilisation !</p>';
echo '<p><strong>📅 Réparation effectuée le :</strong> ' . date('d/m/Y H:i:s') . '</p>';
?>
