<?php
/**
 * Script de réparation d'urgence pour Restaurant Booking Plugin
 * 
 * À exécuter depuis l'administration WordPress en cas de problème
 * URL: /wp-content/plugins/plugin-v2-BLOCK/repair-plugin.php
 * 
 * @package RestaurantBooking
 * @since 1.0.0
 */

// Sécurité - vérifier que nous sommes dans WordPress
if (!defined('ABSPATH')) {
    // Essayer de charger WordPress
    $wp_load_paths = array(
        '../../../wp-load.php',
        '../../../../wp-load.php',
        '../../../../../wp-load.php'
    );
    
    $wp_loaded = false;
    foreach ($wp_load_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $wp_loaded = true;
            break;
        }
    }
    
    if (!$wp_loaded) {
        die('Impossible de charger WordPress');
    }
}

// Vérifier les permissions admin
if (!current_user_can('manage_options')) {
    wp_die('Accès refusé - Permissions administrateur requises');
}

// Définir les constantes si nécessaire
if (!defined('RESTAURANT_BOOKING_PLUGIN_DIR')) {
    define('RESTAURANT_BOOKING_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Réparation Restaurant Booking Plugin</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .success { color: green; background: #f0f8f0; padding: 10px; border-radius: 4px; }
        .error { color: red; background: #f8f0f0; padding: 10px; border-radius: 4px; }
        .info { color: blue; background: #f0f0f8; padding: 10px; border-radius: 4px; }
        .button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; margin: 5px; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔧 Réparation Restaurant Booking Plugin</h1>
    
    <?php
    $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
    
    if ($action) {
        echo '<div class="info">Exécution de l\'action: ' . esc_html($action) . '</div>';
        
        switch ($action) {
            case 'cleanup_database':
                repair_database();
                break;
            case 'recreate_tables':
                recreate_tables();
                break;
            case 'reset_data':
                reset_default_data();
                break;
            case 'check_files':
                check_required_files();
                break;
            case 'deactivate_plugin':
                deactivate_plugin_safely();
                break;
            default:
                echo '<div class="error">Action inconnue</div>';
        }
    }
    ?>
    
    <h2>Actions de réparation disponibles</h2>
    
    <div style="margin: 20px 0;">
        <a href="?action=check_files" class="button">🔍 Vérifier les fichiers</a>
        <a href="?action=cleanup_database" class="button">🧹 Nettoyer la base de données</a>
        <a href="?action=recreate_tables" class="button">🔄 Recréer les tables</a>
        <a href="?action=reset_data" class="button">📊 Réinitialiser les données</a>
        <a href="?action=deactivate_plugin" class="button">❌ Désactiver le plugin</a>
    </div>
    
    <h2>État actuel</h2>
    <?php display_current_status(); ?>
    
    <h2>Logs récents</h2>
    <?php display_recent_logs(); ?>
    
</body>
</html>

<?php
/**
 * Fonctions de réparation
 */

function repair_database()
{
    global $wpdb;
    
    echo '<h3>🔧 Réparation de la base de données</h3>';
    
    try {
        // Supprimer les entrées corrompues
        $deleted_categories = $wpdb->query("DELETE FROM {$wpdb->prefix}restaurant_categories WHERE slug = '' OR slug IS NULL");
        echo '<div class="success">✅ ' . $deleted_categories . ' catégories corrompues supprimées</div>';
        
        // Supprimer les doublons de slugs
        $deleted_duplicates = $wpdb->query("
            DELETE c1 FROM {$wpdb->prefix}restaurant_categories c1
            INNER JOIN {$wpdb->prefix}restaurant_categories c2 
            WHERE c1.id > c2.id AND c1.slug = c2.slug
        ");
        echo '<div class="success">✅ ' . $deleted_duplicates . ' doublons supprimés</div>';
        
        // Réparer les index
        $wpdb->query("ALTER TABLE {$wpdb->prefix}restaurant_categories DROP INDEX slug");
        $wpdb->query("ALTER TABLE {$wpdb->prefix}restaurant_categories ADD INDEX slug (slug)");
        echo '<div class="success">✅ Index réparés</div>';
        
        echo '<div class="success">🎉 Base de données réparée avec succès</div>';
        
    } catch (Exception $e) {
        echo '<div class="error">❌ Erreur: ' . esc_html($e->getMessage()) . '</div>';
    }
}

function recreate_tables()
{
    echo '<h3>🔄 Recréation des tables</h3>';
    
    if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-database.php')) {
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-database.php';
        
        try {
            RestaurantBooking_Database::create_tables();
            echo '<div class="success">✅ Tables recréées avec succès</div>';
        } catch (Exception $e) {
            echo '<div class="error">❌ Erreur: ' . esc_html($e->getMessage()) . '</div>';
        }
    } else {
        echo '<div class="error">❌ Fichier class-database.php manquant</div>';
    }
}

function reset_default_data()
{
    echo '<h3>📊 Réinitialisation des données par défaut</h3>';
    
    if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-database.php')) {
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-database.php';
        
        try {
            RestaurantBooking_Database::insert_default_data();
            echo '<div class="success">✅ Données par défaut réinitialisées</div>';
        } catch (Exception $e) {
            echo '<div class="error">❌ Erreur: ' . esc_html($e->getMessage()) . '</div>';
        }
    } else {
        echo '<div class="error">❌ Fichier class-database.php manquant</div>';
    }
}

function check_required_files()
{
    echo '<h3>🔍 Vérification des fichiers requis</h3>';
    
    $required_files = array(
        'restaurant-booking-plugin.php',
        'includes/class-database.php',
        'includes/class-settings.php',
        'includes/class-logger.php',
        'includes/class-quote.php',
        'includes/class-product.php',
        'includes/class-category.php',
        'includes/class-email.php',
        'includes/class-pdf.php',
        'includes/class-calendar.php',
        'admin/class-admin.php',
        'admin/class-dashboard.php',
        'uninstall.php'
    );
    
    $missing_files = array();
    
    foreach ($required_files as $file) {
        $file_path = RESTAURANT_BOOKING_PLUGIN_DIR . $file;
        if (file_exists($file_path)) {
            echo '<div class="success">✅ ' . esc_html($file) . '</div>';
        } else {
            echo '<div class="error">❌ ' . esc_html($file) . ' - MANQUANT</div>';
            $missing_files[] = $file;
        }
    }
    
    if (empty($missing_files)) {
        echo '<div class="success">🎉 Tous les fichiers requis sont présents</div>';
    } else {
        echo '<div class="error">⚠️ ' . count($missing_files) . ' fichiers manquants détectés</div>';
    }
}

function deactivate_plugin_safely()
{
    echo '<h3>❌ Désactivation sécurisée du plugin</h3>';
    
    $plugin_file = 'plugin-v2-BLOCK/restaurant-booking-plugin.php';
    
    if (is_plugin_active($plugin_file)) {
        deactivate_plugins($plugin_file);
        echo '<div class="success">✅ Plugin désactivé avec succès</div>';
        echo '<div class="info">ℹ️ Vous pouvez maintenant corriger les problèmes et réactiver le plugin</div>';
    } else {
        echo '<div class="info">ℹ️ Le plugin est déjà désactivé</div>';
    }
}

function display_current_status()
{
    global $wpdb;
    
    echo '<div style="background: #f9f9f9; padding: 15px; border-radius: 4px;">';
    
    // Vérifier les tables
    $tables = array(
        'restaurant_categories',
        'restaurant_products', 
        'restaurant_settings',
        'restaurant_quotes',
        'restaurant_availability',
        'restaurant_delivery_zones',
        'restaurant_logs'
    );
    
    echo '<h4>📊 État des tables:</h4>';
    foreach ($tables as $table) {
        $full_table = $wpdb->prefix . $table;
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table'") == $full_table;
        
        if ($exists) {
            $count = $wpdb->get_var("SELECT COUNT(*) FROM $full_table");
            echo "<div class='success'>✅ $table ($count entrées)</div>";
        } else {
            echo "<div class='error'>❌ $table - Table manquante</div>";
        }
    }
    
    // État du plugin
    echo '<h4>🔌 État du plugin:</h4>';
    $plugin_file = 'plugin-v2-BLOCK/restaurant-booking-plugin.php';
    if (is_plugin_active($plugin_file)) {
        echo '<div class="success">✅ Plugin activé</div>';
    } else {
        echo '<div class="error">❌ Plugin désactivé</div>';
    }
    
    echo '</div>';
}

function display_recent_logs()
{
    global $wpdb;
    
    $table_logs = $wpdb->prefix . 'restaurant_logs';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_logs'") == $table_logs;
    
    if (!$table_exists) {
        echo '<div class="info">ℹ️ Table des logs non disponible</div>';
        return;
    }
    
    $logs = $wpdb->get_results("
        SELECT level, message, created_at 
        FROM $table_logs 
        ORDER BY created_at DESC 
        LIMIT 10
    ", ARRAY_A);
    
    if (empty($logs)) {
        echo '<div class="info">ℹ️ Aucun log récent</div>';
        return;
    }
    
    echo '<pre>';
    foreach ($logs as $log) {
        $level_emoji = array(
            'error' => '❌',
            'warning' => '⚠️',
            'info' => 'ℹ️',
            'debug' => '🔧'
        );
        
        $emoji = isset($level_emoji[$log['level']]) ? $level_emoji[$log['level']] : '📝';
        
        echo sprintf(
            "[%s] %s %s: %s\n",
            $log['created_at'],
            $emoji,
            strtoupper($log['level']),
            $log['message']
        );
    }
    echo '</pre>';
}
?>
