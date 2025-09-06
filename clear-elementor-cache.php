<?php
/**
 * SCRIPT DE VIDAGE CACHE ELEMENTOR - URGENCE
 * 
 * Uploadez ce fichier dans /wp-content/plugins/plugin v2 BLOCK/
 * Accédez à : https://block-streetfood.fr/wp-content/plugins/plugin%20v2%20BLOCK/clear-elementor-cache.php?key=clear2024
 */

// Sécurité
if (!isset($_GET['key']) || $_GET['key'] !== 'clear2024') {
    die('Accès refusé. Utilisez: ?key=clear2024');
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

echo '<h1>🧹 VIDAGE CACHE ELEMENTOR - URGENCE</h1>';
echo '<style>body{font-family:Arial,sans-serif;margin:20px;} .ok{color:green;} .error{color:red;} .info{color:blue;}</style>';

echo '<h2>🚀 Nettoyage en cours...</h2>';

// 1. Vider le cache WordPress
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
    echo '<p class="ok">✅ Cache WordPress vidé</p>';
}

// 2. Vider le cache Elementor
if (class_exists('\Elementor\Plugin')) {
    \Elementor\Plugin::$instance->files_manager->clear_cache();
    echo '<p class="ok">✅ Cache Elementor vidé</p>';
} else {
    echo '<p class="error">❌ Elementor non trouvé</p>';
}

// 3. Forcer la régénération des widgets Elementor
if (class_exists('\Elementor\Plugin')) {
    \Elementor\Plugin::$instance->widgets_manager->init_widgets();
    echo '<p class="ok">✅ Widgets Elementor réinitialisés</p>';
}

// 4. Supprimer les transients liés à Elementor
global $wpdb;
$deleted_transients = $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_elementor_%' OR option_name LIKE '_transient_timeout_elementor_%'");
echo '<p class="ok">✅ ' . $deleted_transients . ' transients Elementor supprimés</p>';

// 5. Vider le cache des plugins
if (function_exists('wp_cache_delete')) {
    wp_cache_delete('plugins', 'plugins');
    echo '<p class="ok">✅ Cache des plugins vidé</p>';
}

// 6. Forcer la recompilation des assets
if (class_exists('\Elementor\Plugin')) {
    delete_option('elementor_css_print_method');
    delete_option('elementor_default_generic_fonts');
    echo '<p class="ok">✅ Assets Elementor marqués pour recompilation</p>';
}

// 7. Vérifier l'état de la migration
echo '<h2>🔍 État de la migration v2...</h2>';

if (class_exists('RestaurantBooking_Migration_V2')) {
    $migration_status = RestaurantBooking_Migration_V2::get_migration_status();
    
    echo '<p class="info">Version actuelle: ' . $migration_status['current_version'] . '</p>';
    echo '<p class="info">Version cible: ' . $migration_status['target_version'] . '</p>';
    
    if ($migration_status['migration_completed']) {
        echo '<p class="ok">✅ Migration v2 terminée</p>';
        echo '<p class="info">🎯 Les nouveaux widgets devraient être disponibles</p>';
    } else {
        echo '<p class="error">❌ Migration v2 non terminée</p>';
        echo '<p class="info">🔄 Les anciens widgets restent actifs</p>';
    }
} else {
    echo '<p class="error">❌ Classe de migration non trouvée</p>';
}

echo '<h2>🎯 ÉTAPES SUIVANTES</h2>';
echo '<ol>';
echo '<li><strong>Actualisez votre éditeur Elementor</strong> (F5)</li>';
echo '<li><strong>Vérifiez la liste des widgets</strong> dans Elementor</li>';
echo '<li><strong>Si migration terminée</strong> : Seul "Formulaire de Devis Unifié v2" devrait être visible</li>';
echo '<li><strong>Si problème persiste</strong> : Contactez-moi avec une capture d\'écran</li>';
echo '<li><strong>Supprimez ce fichier</strong> après utilisation</li>';
echo '</ol>';

echo '<hr>';
echo '<p><strong>⚠️ IMPORTANT :</strong> Supprimez ce fichier après utilisation !</p>';
echo '<p><strong>📅 Nettoyage effectué le :</strong> ' . date('d/m/Y H:i:s') . '</p>';
?>
