<?php
/**
 * Plugin Name: Plugin Block & co
 * Plugin URI: https://www.thecomm.agency/
 * Description: Plugin complet de gestion de devis de privatisation pour restaurant avec interface Elementor
 * Version: 1.0.1
 * Author: Thecomm
 * Author URI: https://www.thecomm.agency/
 * Text Domain: restaurant-booking
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 8.0
 * Network: false
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Définir les constantes du plugin
define('RESTAURANT_BOOKING_VERSION', '1.0.1');
define('RESTAURANT_BOOKING_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RESTAURANT_BOOKING_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RESTAURANT_BOOKING_PLUGIN_FILE', __FILE__);
define('RESTAURANT_BOOKING_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Mode debug
if (!defined('RESTAURANT_BOOKING_DEBUG')) {
    define('RESTAURANT_BOOKING_DEBUG', false);
}

/**
 * Classe principale du plugin
 */
class RestaurantBookingPlugin
{
    /**
     * Instance unique du plugin
     */
    private static $instance = null;

    /**
     * Obtenir l'instance unique du plugin
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructeur privé pour le singleton
     */
    private function __construct()
    {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        register_uninstall_hook(__FILE__, array('RestaurantBookingPlugin', 'uninstall'));
    }

    /**
     * Initialisation du plugin
     */
    public function init()
    {
        // Vérifier les prérequis
        if (!$this->check_requirements()) {
            return;
        }

        // Charger les fichiers de traduction
        load_plugin_textdomain('restaurant-booking', false, dirname(plugin_basename(__FILE__)) . '/languages');

        // Inclure les fichiers nécessaires
        $this->include_files();

        // Initialiser les composants
        $this->init_components();

        // Hooks d'initialisation
        add_action('init', array($this, 'load_components'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // Hook pour Elementor
        add_action('elementor/widgets/widgets_registered', array($this, 'register_elementor_widgets'));
        add_action('elementor/elements/categories_registered', array($this, 'add_elementor_widget_categories'));
    }

    /**
     * Vérifier les prérequis du plugin
     */
    private function check_requirements()
    {
        // Vérifier la version PHP
        if (version_compare(PHP_VERSION, '8.0', '<')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>';
                echo sprintf(
                    __('Le plugin Restaurant Booking nécessite PHP 8.0 ou supérieur. Version actuelle: %s', 'restaurant-booking'),
                    PHP_VERSION
                );
                echo '</p></div>';
            });
            return false;
        }

        // Vérifier WordPress
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>';
                echo __('Le plugin Restaurant Booking nécessite WordPress 5.0 ou supérieur.', 'restaurant-booking');
                echo '</p></div>';
            });
            return false;
        }

        return true;
    }

    /**
     * Inclure les fichiers nécessaires
     */
    private function include_files()
    {
        // Charger le logger EN PREMIER
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-logger.php';
        
        // Charger les permissions
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-permissions.php';
        
        // Classes principales
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-database.php';
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-settings.php';
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-quote.php';
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-product.php';
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-category.php';
        
        // Helper pour les options unifiées
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-options-helper.php';
        
        // Classes v2 (nouvelles fonctionnalités)
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-migration-v2.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-migration-v2.php';
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-migration-v3.php';
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-migration-v4-cleanup.php';
        }
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-game.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-game.php';
        }
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-supplement-manager.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-supplement-manager.php';
        }
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-accompaniment-option-manager.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-accompaniment-option-manager.php';
        }
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-product-supplement-manager.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-product-supplement-manager.php';
        }
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-beverage-size-manager.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-beverage-size-manager.php';
        }
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-database-cleaner.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-database-cleaner.php';
        }
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-test-data-creator.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-test-data-creator.php';
        }
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-beverage-manager.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-beverage-manager.php';
        }
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-distance-calculator.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-distance-calculator.php';
        }
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-quote-calculator-v2.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-quote-calculator-v2.php';
        }
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'public/class-ajax-handler-v2.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'public/class-ajax-handler-v2.php';
        }
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'public/class-shortcodes-v2.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'public/class-shortcodes-v2.php';
        }

        // Classes optionnelles avec vérification
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-email.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-email.php';
        }
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-pdf.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-pdf.php';
        }
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-calendar.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-calendar.php';
        }
        // Intégration Google Calendar
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-google-calendar.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-google-calendar.php';
        }

        // Interface d'administration
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-admin.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-admin.php';
        }
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-dashboard.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-dashboard.php';
        }
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-quotes-list.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-quotes-list.php';
        }
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-products-admin.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-products-admin.php';
        }
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-settings-admin.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-settings-admin.php';
        }
        
        // Classes d'administration v2
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-games-admin.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-games-admin.php';
        }
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-migration-admin.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-migration-admin.php';
        }
        
        // Classes d'administration des boissons
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-beverages-soft-admin.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-beverages-soft-admin.php';
        }
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-beverages-beers-admin.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-beverages-beers-admin.php';
        }
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-beverages-kegs-admin.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-beverages-kegs-admin.php';
        }
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-beverages-wines-admin.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-beverages-wines-admin.php';
        }

        // Interface publique
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'public/class-public.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'public/class-public.php';
        }
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'public/class-quote-form.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'public/class-quote-form.php';
        }
        // Nouveaux formulaires spécialisés selon le cahier des charges
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'public/class-quote-form-restaurant.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'public/class-quote-form-restaurant.php';
        }
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'public/class-quote-form-remorque.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'public/class-quote-form-remorque.php';
        }
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'public/class-ajax-handler.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'public/class-ajax-handler.php';
        }

        // Widgets Elementor
        if (did_action('elementor/loaded') && file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'elementor/class-elementor-widgets.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'elementor/class-elementor-widgets.php';
        }
    }

    /**
     * Initialiser les composants
     */
    private function init_components()
    {
        // Initialiser la base de données
        RestaurantBooking_Database::get_instance();
        
        // Initialiser les paramètres
        RestaurantBooking_Settings::get_instance();
        
        // Initialiser le logger
        RestaurantBooking_Logger::get_instance();
        
        // Initialiser les composants v2
        if (class_exists('RestaurantBooking_Migration_V2')) {
            RestaurantBooking_Migration_V2::get_instance();
        }
        
        // Exécuter la migration v3 si nécessaire
        if (class_exists('RestaurantBooking_Migration_V3') && RestaurantBooking_Migration_V3::needs_migration()) {
            RestaurantBooking_Migration_V3::migrate();
        }
        
        // Exécuter la migration v4 de nettoyage si nécessaire
        if (class_exists('RestaurantBooking_Migration_V4_Cleanup') && RestaurantBooking_Migration_V4_Cleanup::needs_migration()) {
            RestaurantBooking_Migration_V4_Cleanup::run();
        }
        if (class_exists('RestaurantBooking_Game')) {
            RestaurantBooking_Game::get_instance();
        }
        if (class_exists('RestaurantBooking_Supplement_Manager')) {
            RestaurantBooking_Supplement_Manager::get_instance();
        }
        if (class_exists('RestaurantBooking_Accompaniment_Option_Manager')) {
            RestaurantBooking_Accompaniment_Option_Manager::get_instance();
        }
        if (class_exists('RestaurantBooking_Beverage_Size_Manager')) {
            RestaurantBooking_Beverage_Size_Manager::get_instance();
        }
        if (class_exists('RestaurantBooking_Product_Supplement_Manager')) {
            RestaurantBooking_Product_Supplement_Manager::get_instance();
        }
        if (class_exists('RestaurantBooking_Beverage_Manager')) {
            RestaurantBooking_Beverage_Manager::get_instance();
        }
        if (class_exists('RestaurantBooking_Distance_Calculator')) {
            RestaurantBooking_Distance_Calculator::get_instance();
        }
        if (class_exists('RestaurantBooking_Ajax_Handler_V2')) {
            RestaurantBooking_Ajax_Handler_V2::get_instance();
        }
        if (class_exists('RestaurantBooking_Shortcodes_V2')) {
            RestaurantBooking_Shortcodes_V2::get_instance();
        }
    }

    /**
     * Charger les composants après l'initialisation
     */
    public function load_components()
    {
        // Interface d'administration
        if (is_admin()) {
            RestaurantBooking_Admin::get_instance();
            
            // Charger les classes d'administration v2
            if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-games-admin.php')) {
                require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-games-admin.php';
                RestaurantBooking_Games_Admin::get_instance();
            }
            if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-migration-admin.php')) {
                require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-migration-admin.php';
                // RestaurantBooking_Migration_Admin::get_instance(); // Désactivé - migration terminée
            }
        }

        // Interface publique
        if (!is_admin()) {
            RestaurantBooking_Public::get_instance();
        }
    }


    /**
     * Enregistrer les widgets Elementor
     */
    public function register_elementor_widgets($widgets_manager)
    {
        if (class_exists('RestaurantBooking_Elementor_Widgets')) {
            $elementor_widgets = new RestaurantBooking_Elementor_Widgets();
            $elementor_widgets->register_widgets($widgets_manager);
        }
    }

    /**
     * Ajouter les catégories de widgets Elementor
     */
    public function add_elementor_widget_categories($elements_manager)
    {
        $elements_manager->add_category(
            'restaurant-booking',
            array(
                'title' => __('Block & Co', 'restaurant-booking'),
                'icon' => 'fa fa-utensils',
            )
        );
    }

    /**
     * Charger les scripts et styles publics
     */
    public function enqueue_public_scripts()
    {
        wp_enqueue_style(
            'restaurant-booking-public',
            RESTAURANT_BOOKING_PLUGIN_URL . 'assets/css/public.css',
            array(),
            RESTAURANT_BOOKING_VERSION
        );

        wp_enqueue_script(
            'restaurant-booking-public',
            RESTAURANT_BOOKING_PLUGIN_URL . 'assets/js/public.js',
            array('jquery'),
            RESTAURANT_BOOKING_VERSION,
            true
        );

        // Scripts et styles v2 (formulaire unifié)
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'assets/css/quote-form-unified.css')) {
            wp_enqueue_style(
                'restaurant-booking-quote-form-unified',
                RESTAURANT_BOOKING_PLUGIN_URL . 'assets/css/quote-form-unified.css',
                array(),
                RESTAURANT_BOOKING_VERSION
            );
        }

        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'assets/js/quote-form-unified.js')) {
            wp_enqueue_script(
                'restaurant-booking-quote-form-unified',
                RESTAURANT_BOOKING_PLUGIN_URL . 'assets/js/quote-form-unified.js',
                array('jquery'),
                RESTAURANT_BOOKING_VERSION,
                true
            );
        }

        // Localisation des scripts
        wp_localize_script('restaurant-booking-public', 'restaurant_booking_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('restaurant_booking_nonce'),
            'messages' => array(
                'loading' => __('Chargement...', 'restaurant-booking'),
                'error' => __('Une erreur est survenue.', 'restaurant-booking'),
                'success' => __('Succès !', 'restaurant-booking'),
            )
        ));
    }

    /**
     * Charger les scripts et styles d'administration
     */
    public function enqueue_admin_scripts($hook)
    {
        // Charger seulement sur les pages du plugin
        if (strpos($hook, 'restaurant-booking') === false) {
            return;
        }

        wp_enqueue_style(
            'restaurant-booking-admin',
            RESTAURANT_BOOKING_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            RESTAURANT_BOOKING_VERSION
        );

        wp_enqueue_script(
            'restaurant-booking-admin',
            RESTAURANT_BOOKING_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-util'),
            RESTAURANT_BOOKING_VERSION,
            true
        );

        // Localisation
        wp_localize_script('restaurant-booking-admin', 'restaurant_booking_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('restaurant_booking_admin_nonce'),
            'messages' => array(
                'confirm_delete' => __('Êtes-vous sûr de vouloir supprimer cet élément ?', 'restaurant-booking'),
                'saved' => __('Paramètres sauvegardés', 'restaurant-booking'),
            )
        ));
    }

    /**
     * Activation du plugin
     */
    public function activate()
    {
        // Créer les tables de base de données
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-database.php';
        RestaurantBooking_Database::create_tables();

        // Insérer les données par défaut
        RestaurantBooking_Database::insert_default_data();

        // Créer les rôles et capabilities
        $this->create_roles_and_capabilities();

        // Programmer les tâches cron
        $this->schedule_cron_jobs();

        // Nettoyer les anciens menus WordPress
        $this->cleanup_old_menus();

        // Flush rewrite rules
        flush_rewrite_rules();

        // Log de l'activation
        if (class_exists('RestaurantBooking_Logger')) {
            RestaurantBooking_Logger::log('Plugin activé avec succès', 'info');
        }
    }

    /**
     * Désactivation du plugin
     */
    public function deactivate()
    {
        // Supprimer les tâches cron
        wp_clear_scheduled_hook('restaurant_booking_cleanup');
        wp_clear_scheduled_hook('restaurant_booking_backup');

        // Flush rewrite rules
        flush_rewrite_rules();

        // Log de la désactivation
        if (class_exists('RestaurantBooking_Logger')) {
            RestaurantBooking_Logger::log('Plugin désactivé', 'info');
        }
    }

    /**
     * Désinstallation du plugin
     */
    public static function uninstall()
    {
        // Supprimer les tables si l'option est activée
        $delete_data = get_option('restaurant_booking_delete_data_on_uninstall', false);
        if ($delete_data) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-database.php';
            RestaurantBooking_Database::drop_tables();
        }

        // Supprimer toutes les options
        delete_option('restaurant_booking_version');
        delete_option('restaurant_booking_settings');
        delete_option('restaurant_booking_delete_data_on_uninstall');

        // Supprimer les rôles personnalisés
        remove_role('restaurant_manager');

        // Supprimer les capabilities
        $role = get_role('administrator');
        if ($role) {
            $role->remove_cap('manage_restaurant_quotes');
            $role->remove_cap('manage_restaurant_products');
            $role->remove_cap('manage_restaurant_settings');
        }
    }

    /**
     * Créer les rôles et capabilities personnalisés
     */
    private function create_roles_and_capabilities()
    {
        // Ajouter les capabilities à l'administrateur
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('manage_restaurant_quotes');
            $admin_role->add_cap('manage_restaurant_products');
            $admin_role->add_cap('manage_restaurant_settings');
        }

        // Créer le rôle Restaurant Manager
        add_role(
            'restaurant_manager',
            __('Gestionnaire Restaurant', 'restaurant-booking'),
            array(
                'read' => true,
                'manage_restaurant_quotes' => true,
                'manage_restaurant_products' => true,
            )
        );
    }

    /**
     * Programmer les tâches cron
     */
    private function schedule_cron_jobs()
    {
        // Nettoyage quotidien
        if (!wp_next_scheduled('restaurant_booking_cleanup')) {
            wp_schedule_event(time(), 'daily', 'restaurant_booking_cleanup');
        }

        // Sauvegarde quotidienne
        if (!wp_next_scheduled('restaurant_booking_backup')) {
            wp_schedule_event(time(), 'daily', 'restaurant_booking_backup');
        }
    }

    /**
     * Nettoyer les anciens menus WordPress
     */
    private function cleanup_old_menus()
    {
        // Supprimer les transients de menu
        delete_transient('menu_items');
        delete_transient('admin_menu');
        
        // Vider le cache WordPress
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        // Supprimer les options de menu obsolètes
        delete_option('restaurant_booking_menu_cache');
    }
}

// Initialiser le plugin
function restaurant_booking_init()
{
    return RestaurantBookingPlugin::get_instance();
}

// Démarrer le plugin
restaurant_booking_init();

// Tâches cron
add_action('restaurant_booking_cleanup', 'restaurant_booking_cleanup_task');
add_action('restaurant_booking_backup', 'restaurant_booking_backup_task');

/**
 * Tâche de nettoyage quotidien
 */
function restaurant_booking_cleanup_task()
{
    global $wpdb;
    
    // Supprimer les devis brouillons de plus de 30 jours
    $wpdb->query("
        DELETE FROM {$wpdb->prefix}restaurant_quotes 
        WHERE status = 'draft' 
        AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    
    // Nettoyer les logs de plus de 90 jours
    $wpdb->query("
        DELETE FROM {$wpdb->prefix}restaurant_logs 
        WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)
    ");
    
    RestaurantBooking_Logger::log('Nettoyage automatique effectué', 'info');
}

/**
 * Tâche de sauvegarde quotidienne
 */
function restaurant_booking_backup_task()
{
    // Sauvegarder la configuration
    $settings = get_option('restaurant_booking_settings', array());
    update_option('restaurant_booking_settings_backup', $settings);
    
    RestaurantBooking_Logger::log('Sauvegarde de configuration effectuée', 'info');
}
