<?php
/**
 * Plugin Name: Plugin Block & co
 * Plugin URI: https://www.thecomm.agency/
 * Description: Plugin complet de gestion de devis de privatisation pour restaurant avec shortcode intégré
 * Version: 1.0.6
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

// Mode debug temporaire pour diagnostiquer les problèmes d'affichage
define('RESTAURANT_BOOKING_DEBUG', true);

// Définir les constantes du plugin
define('RESTAURANT_BOOKING_VERSION', '1.0.6');
define('RESTAURANT_BOOKING_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RESTAURANT_BOOKING_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RESTAURANT_BOOKING_PLUGIN_FILE', __FILE__);
define('RESTAURANT_BOOKING_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Mode debug
if (!defined('RESTAURANT_BOOKING_DEBUG')) {
    define('RESTAURANT_BOOKING_DEBUG', true);
}

// mPDF supprimé - utilisation de TCPDF natif WordPress

// Charger l'autoloader Google API si disponible (temporairement désactivé)
// if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'vendor/autoload.php')) {
//     require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'vendor/autoload.php';
// }

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
        
        // Note: Les actions AJAX sont enregistrées par la classe Admin

        // Ancien shortcode supprimé - Utiliser uniquement [restaurant_booking_form_v3]
        
        // Charger le nouveau shortcode V3
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'public/class-shortcode-form-v3.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'public/class-shortcode-form-v3.php';
        }
        
        // Charger le gestionnaire AJAX V3
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'public/class-ajax-handler-v3.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'public/class-ajax-handler-v3.php';
        }
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
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-logger.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-logger.php';
        } else {
            // Fallback si le logger n'existe pas
            error_log('Restaurant Booking: class-logger.php not found at ' . RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-logger.php');
            // Créer une classe logger de fallback
            if (!class_exists('RestaurantBooking_Logger')) {
                $this->create_fallback_logger();
            }
        }
        
        // Charger les permissions
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-permissions.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-permissions.php';
        }
        
        // Classes principales
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-database.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-database.php';
        }
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-settings.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-settings.php';
        }
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-quote.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-quote.php';
        }
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-product.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-product.php';
        }
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-category.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-category.php';
        }
        
        // Helper pour les options unifiées
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-options-helper.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-options-helper.php';
        }
        
        // Classe de nettoyage des textes
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-text-cleaner.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-text-cleaner.php';
        }
        
        // Classes v2 (nouvelles fonctionnalités)
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-migration-v2.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-migration-v2.php';
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-migration-v3.php';
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-migration-v4-cleanup.php';
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-migration-fix-hardcoded-issues.php';
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-migration-beer-types.php';
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-migration-add-games.php';
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-migration-fix-keg-categories.php';
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-migration-create-subcategories.php';
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-migration-restructure-wine-categories.php';
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-migration-fix-collations.php';
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
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-google-maps-service.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-google-maps-service.php';
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
            // Initialiser immédiatement pour enregistrer les actions AJAX
            RestaurantBooking_Google_Calendar::get_instance();
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
        
        // Options unifiées admin
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-options-unified-admin.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-options-unified-admin.php';
        }
        
        // Migration pour corriger les échappements multiples
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-fix-escaped-quotes-migration.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-fix-escaped-quotes-migration.php';
            // Exécuter la migration automatiquement
            RestaurantBooking_Fix_Escaped_Quotes_Migration::run();
        }
        
        // Migration complète pour corriger les échappements multiples
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-fix-escaped-quotes-complete-migration.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-fix-escaped-quotes-complete-migration.php';
        }
        
        // Interface admin pour la correction des échappements
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-fix-escaped-quotes-admin.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-fix-escaped-quotes-admin.php';
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
        
        // Système unifié V2
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'public/class-shortcodes-v2.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'public/class-shortcodes-v2.php';
        }
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'public/class-ajax-handler-v2.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'public/class-ajax-handler-v2.php';
        }
        
        // Ancien gestionnaire AJAX supprimé - Utiliser uniquement V3
        
        // Initialiser les gestionnaires après inclusion de toutes les classes
        if (class_exists('RestaurantBooking_Ajax_Handler_V2')) {
            RestaurantBooking_Ajax_Handler_V2::get_instance();
        }
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'public/class-ajax-handler.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'public/class-ajax-handler.php';
        }

        // Ancien shortcode Block Form supprimé - Utiliser [restaurant_booking_form_v3]
    }

    /**
     * Initialiser les composants
     */
    private function init_components()
    {
        // Initialiser la base de données
        if (class_exists('RestaurantBooking_Database')) {
            RestaurantBooking_Database::get_instance();
        } else {
            error_log('Restaurant Booking: RestaurantBooking_Database class not found');
        }
        
        // Initialiser les paramètres
        if (class_exists('RestaurantBooking_Settings')) {
            RestaurantBooking_Settings::get_instance();
        }
        
        // Initialiser le logger
        if (class_exists('RestaurantBooking_Logger')) {
            RestaurantBooking_Logger::get_instance();
        }
        
        // Initialiser le service Google Maps
        if (class_exists('RestaurantBooking_Google_Maps_Service')) {
            RestaurantBooking_Google_Maps_Service::get_instance();
        }
        
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
        
        // Exécuter la migration de correction des problèmes hardcodés si nécessaire
        if (class_exists('RestaurantBooking_Migration_Fix_Hardcoded_Issues') && RestaurantBooking_Migration_Fix_Hardcoded_Issues::is_migration_needed()) {
            RestaurantBooking_Migration_Fix_Hardcoded_Issues::migrate();
        }
        
        // Exécuter la migration pour diversifier les types de bières
        if (class_exists('RestaurantBooking_Migration_Beer_Types') && RestaurantBooking_Migration_Beer_Types::is_migration_needed()) {
            RestaurantBooking_Migration_Beer_Types::migrate();
        }
        
        // Exécuter la migration pour ajouter les jeux
        if (class_exists('RestaurantBooking_Migration_Add_Games') && RestaurantBooking_Migration_Add_Games::is_migration_needed()) {
            RestaurantBooking_Migration_Add_Games::migrate();
        }
        
        // Migration pour corriger les catégories de bières des fûts
        if (class_exists('RestaurantBooking_Migration_Fix_Keg_Categories') && RestaurantBooking_Migration_Fix_Keg_Categories::is_migration_needed()) {
            RestaurantBooking_Migration_Fix_Keg_Categories::migrate();
        }
        
        // Migration pour créer la table des sous-catégories
        if (class_exists('RestaurantBooking_Migration_Create_Subcategories') && RestaurantBooking_Migration_Create_Subcategories::is_migration_needed()) {
            RestaurantBooking_Migration_Create_Subcategories::migrate();
        }

        // Migration pour restructurer les catégories de vins
        if (class_exists('RestaurantBooking_Migration_Restructure_Wine_Categories') && RestaurantBooking_Migration_Restructure_Wine_Categories::is_migration_needed()) {
            RestaurantBooking_Migration_Restructure_Wine_Categories::migrate();
        }
        
        // Migration pour corriger les collations de base de données
        if (class_exists('RestaurantBooking_Migration_Fix_Collations') && RestaurantBooking_Migration_Fix_Collations::is_needed()) {
            RestaurantBooking_Migration_Fix_Collations::run();
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
            if (class_exists('RestaurantBooking_Admin')) {
                RestaurantBooking_Admin::get_instance();
            } else {
                error_log('Restaurant Booking: RestaurantBooking_Admin class not found');
            }
            
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
            if (class_exists('RestaurantBooking_Public')) {
                RestaurantBooking_Public::get_instance();
            } else {
                error_log('Restaurant Booking: RestaurantBooking_Public class not found');
            }
        }
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

        // Ancien CSS Block supprimé - Utiliser uniquement V3

        // Ancien CSS Block Force supprimé - Utiliser uniquement V3

        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'assets/js/quote-form-unified.js')) {
            wp_enqueue_script(
                'restaurant-booking-quote-form-unified',
                RESTAURANT_BOOKING_PLUGIN_URL . 'assets/js/quote-form-unified.js',
                array('jquery'),
                RESTAURANT_BOOKING_VERSION,
                true
            );
        }

        // Ancien JavaScript Block supprimé - Utiliser uniquement V3

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
            RESTAURANT_BOOKING_VERSION . '-' . time() // Cache busting pour forcer le rechargement
        );

        // S'assurer que jQuery est chargé
        wp_enqueue_script('jquery');
        wp_enqueue_script('wp-util');
        
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

    /**
     * Créer une classe logger de fallback
     */
    private function create_fallback_logger()
    {
        if (!class_exists('RestaurantBooking_Logger')) {
            eval('
                class RestaurantBooking_Logger {
                    public static function log($message, $level = "info", $context = null) {
                        error_log("[Restaurant Booking] [" . strtoupper($level) . "] " . $message);
                    }
                    public static function error($message, $context = null) { self::log($message, "error", $context); }
                    public static function warning($message, $context = null) { self::log($message, "warning", $context); }
                    public static function info($message, $context = null) { self::log($message, "info", $context); }
                    public static function debug($message, $context = null) { self::log($message, "debug", $context); }
                }
            ');
        }
    }

    /**
     * Gestionnaire AJAX de fallback
     */
    public function handle_fallback_ajax()
    {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['nonce'], 'restaurant_booking_admin_nonce')) {
            wp_die(__('Token de sécurité invalide', 'restaurant-booking'));
        }

        $action = sanitize_text_field($_POST['admin_action']);

        // Si la classe Admin est disponible, déléguer à elle
        if (class_exists('RestaurantBooking_Admin')) {
            $admin = RestaurantBooking_Admin::get_instance();
            if (method_exists($admin, 'handle_ajax_action')) {
                $admin->handle_ajax_action();
                return;
            }
        }

        // Fallback pour les actions de suppression
        switch ($action) {
            case 'delete_product':
            case 'delete_beer':
            case 'delete_wine':
            case 'delete_soft_beverage':
            case 'delete_keg':
            case 'delete_accompaniment':
            case 'delete_game':
            case 'delete_dog_product':
            case 'delete_croq_product':
            case 'delete_buffet_sale':
            case 'delete_buffet_sucre':
            case 'delete_mini_boss':
                $this->handle_fallback_delete($action);
                break;
            default:
                wp_send_json_error(__('Action non reconnue', 'restaurant-booking'));
        }
    }

    /**
     * Gestionnaire de suppression de fallback
     */
    private function handle_fallback_delete($action)
    {
        if (!current_user_can('manage_restaurant_quotes')) {
            wp_send_json_error(__('Permissions insuffisantes', 'restaurant-booking'));
            return;
        }

        // Déterminer le paramètre d'ID selon l'action
        $id_param = 'product_id';
        if ($action === 'delete_beer') {
            $id_param = 'beer_id';
        } elseif ($action === 'delete_accompaniment') {
            $id_param = 'accompaniment_id';
        } elseif ($action === 'delete_game') {
            $id_param = 'game_id';
        }

        $id = (int) $_POST[$id_param];
        if ($id <= 0) {
            wp_send_json_error(__('ID invalide', 'restaurant-booking'));
            return;
        }

        // Utiliser la classe Product pour la suppression
        if (class_exists('RestaurantBooking_Product')) {
            if ($action === 'delete_game' && class_exists('RestaurantBooking_Game')) {
                $result = RestaurantBooking_Game::delete($id);
            } else {
                $result = RestaurantBooking_Product::delete($id);
            }

            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            } else {
                wp_send_json_success(__('Élément supprimé avec succès', 'restaurant-booking'));
            }
        } else {
            wp_send_json_error(__('Classes de gestion des produits non disponibles', 'restaurant-booking'));
        }
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
