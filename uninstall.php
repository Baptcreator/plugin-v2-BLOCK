<?php
/**
 * Fichier de désinstallation du plugin Restaurant Booking
 *
 * Ce fichier est exécuté lorsque le plugin est désinstallé via l'interface WordPress
 *
 * @package RestaurantBooking
 * @since 1.0.0
 */

// Empêcher l'exécution directe
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Vérifier que c'est bien notre plugin qui est désinstallé
if (!defined('RESTAURANT_BOOKING_VERSION')) {
    define('RESTAURANT_BOOKING_VERSION', '1.0.0');
    define('RESTAURANT_BOOKING_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

/**
 * Classe de désinstallation
 */
class RestaurantBooking_Uninstaller
{
    /**
     * Exécuter la désinstallation
     */
    public static function uninstall()
    {
        global $wpdb;

        // Vérifier les permissions
        if (!current_user_can('activate_plugins')) {
            return;
        }

        // Obtenir les options de suppression
        $delete_data = get_option('restaurant_booking_delete_data_on_uninstall', false);
        $delete_settings = get_option('restaurant_booking_delete_settings_on_uninstall', true);

        // Log de début de désinstallation
        error_log('Restaurant Booking Plugin: Début de la désinstallation');

        // Supprimer les tâches cron
        self::clear_scheduled_tasks();

        // Supprimer les données si demandé
        if ($delete_data) {
            self::delete_database_tables();
            self::delete_uploaded_files();
        }

        // Supprimer les paramètres si demandé
        if ($delete_settings) {
            self::delete_plugin_options();
        }

        // Supprimer les rôles et capabilities personnalisés
        self::remove_custom_roles();

        // Nettoyer le cache
        self::clear_cache();

        // Log de fin de désinstallation
        error_log('Restaurant Booking Plugin: Désinstallation terminée');
    }

    /**
     * Supprimer les tâches cron programmées
     */
    private static function clear_scheduled_tasks()
    {
        // Supprimer les tâches cron
        wp_clear_scheduled_hook('restaurant_booking_cleanup');
        wp_clear_scheduled_hook('restaurant_booking_backup');
        wp_clear_scheduled_hook('restaurant_booking_email_reminders');
        
        error_log('Restaurant Booking Plugin: Tâches cron supprimées');
    }

    /**
     * Supprimer les tables de base de données
     */
    private static function delete_database_tables()
    {
        global $wpdb;

        $tables = array(
            $wpdb->prefix . 'restaurant_logs',
            $wpdb->prefix . 'restaurant_delivery_zones',
            $wpdb->prefix . 'restaurant_availability',
            $wpdb->prefix . 'restaurant_quotes',
            $wpdb->prefix . 'restaurant_products',
            $wpdb->prefix . 'restaurant_categories',
            $wpdb->prefix . 'restaurant_settings',
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }

        error_log('Restaurant Booking Plugin: Tables de base de données supprimées');
    }

    /**
     * Supprimer les fichiers uploadés
     */
    private static function delete_uploaded_files()
    {
        $upload_dir = wp_upload_dir();
        $plugin_upload_dir = $upload_dir['basedir'] . '/restaurant-booking/';

        if (is_dir($plugin_upload_dir)) {
            self::delete_directory($plugin_upload_dir);
        }

        error_log('Restaurant Booking Plugin: Fichiers uploadés supprimés');
    }

    /**
     * Supprimer récursivement un dossier
     */
    private static function delete_directory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), array('.', '..'));
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                self::delete_directory($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }

    /**
     * Supprimer toutes les options du plugin
     */
    private static function delete_plugin_options()
    {
        global $wpdb;

        // Supprimer les options par préfixe
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'restaurant_booking_%'");

        // Supprimer les options spécifiques
        $options_to_delete = array(
            'restaurant_booking_version',
            'restaurant_booking_db_version',
            'restaurant_booking_activation_date',
            'restaurant_booking_settings',
            'restaurant_booking_delete_data_on_uninstall',
            'restaurant_booking_delete_settings_on_uninstall',
        );

        foreach ($options_to_delete as $option) {
            delete_option($option);
        }

        // Supprimer les transients
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_restaurant_booking_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_restaurant_booking_%'");

        error_log('Restaurant Booking Plugin: Options supprimées');
    }

    /**
     * Supprimer les rôles et capabilities personnalisés
     */
    private static function remove_custom_roles()
    {
        // Supprimer le rôle personnalisé
        remove_role('restaurant_manager');

        // Supprimer les capabilities des rôles existants
        $roles_to_clean = array('administrator', 'editor');
        $caps_to_remove = array(
            'manage_restaurant_quotes',
            'manage_restaurant_products',
            'manage_restaurant_settings',
            'view_restaurant_reports'
        );

        foreach ($roles_to_clean as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                foreach ($caps_to_remove as $cap) {
                    $role->remove_cap($cap);
                }
            }
        }

        error_log('Restaurant Booking Plugin: Rôles et capabilities supprimés');
    }

    /**
     * Vider le cache
     */
    private static function clear_cache()
    {
        // Vider le cache WordPress
        wp_cache_flush();

        // Vider les caches populaires si présents
        if (function_exists('wp_cache_clear_cache')) {
            wp_cache_clear_cache();
        }

        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all();
        }

        if (function_exists('wp_rocket_clean_domain')) {
            wp_rocket_clean_domain();
        }

        error_log('Restaurant Booking Plugin: Cache vidé');
    }

    /**
     * Sauvegarder les données avant suppression (optionnel)
     */
    private static function backup_data_before_deletion()
    {
        global $wpdb;

        $backup_data = array(
            'version' => RESTAURANT_BOOKING_VERSION,
            'uninstall_date' => current_time('mysql'),
            'tables' => array()
        );

        // Sauvegarder les données importantes
        $tables_to_backup = array(
            'restaurant_quotes',
            'restaurant_settings'
        );

        foreach ($tables_to_backup as $table) {
            $full_table_name = $wpdb->prefix . $table;
            if ($wpdb->get_var("SHOW TABLES LIKE '$full_table_name'") === $full_table_name) {
                $backup_data['tables'][$table] = $wpdb->get_results("SELECT * FROM $full_table_name", ARRAY_A);
            }
        }

        // Sauvegarder dans un fichier temporaire
        $upload_dir = wp_upload_dir();
        $backup_file = $upload_dir['basedir'] . '/restaurant-booking-backup-' . date('Y-m-d-H-i-s') . '.json';
        
        file_put_contents($backup_file, json_encode($backup_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        error_log('Restaurant Booking Plugin: Sauvegarde créée dans ' . $backup_file);
    }
}

// Exécuter la désinstallation
RestaurantBooking_Uninstaller::uninstall();
