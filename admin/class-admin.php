<?php
/**
 * Classe principale de l'interface d'administration
 *
 * @package RestaurantBooking
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Admin
{
    /**
     * Instance unique
     */
    private static $instance = null;

    /**
     * Obtenir l'instance unique
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructeur
     */
    private function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menus'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('wp_ajax_restaurant_booking_admin_action', array($this, 'handle_ajax_action'));
        
        // Actions AJAX pour l'onglet Avancé
        add_action('wp_ajax_restaurant_booking_get_logs', array($this, 'ajax_get_logs'));
        add_action('wp_ajax_restaurant_booking_get_debug_log', array($this, 'ajax_get_debug_log'));
        add_action('wp_ajax_restaurant_booking_clean_logs', array($this, 'ajax_clean_logs'));
        add_action('wp_ajax_restaurant_booking_clear_cache', array($this, 'ajax_clear_cache'));
        add_action('wp_ajax_restaurant_booking_test_database', array($this, 'ajax_test_database'));
        add_action('wp_ajax_restaurant_booking_sync_google_calendar', array($this, 'ajax_sync_google_calendar'));
        
        // Actions AJAX pour le débogage Google Calendar
        add_action('wp_ajax_debug_google_events_retrieval', array($this, 'ajax_debug_google_events_retrieval'));
        add_action('wp_ajax_debug_database_content', array($this, 'ajax_debug_database_content'));
        add_action('wp_ajax_debug_force_sync_from_google', array($this, 'ajax_debug_force_sync_from_google'));
        add_action('wp_ajax_debug_clear_google_events', array($this, 'ajax_debug_clear_google_events'));
        add_action('wp_ajax_debug_raw_google_response', array($this, 'ajax_debug_raw_google_response'));
        add_action('wp_ajax_debug_fix_availability_status', array($this, 'ajax_debug_fix_availability_status'));
        
        // Actions AJAX pour les autres onglets
        add_action('wp_ajax_restaurant_booking_test_email', array($this, 'ajax_test_email'));
        add_action('wp_ajax_restaurant_booking_test_admin_notification', array($this, 'ajax_test_admin_notification'));
        
        // Action AJAX pour créer une nouvelle contenance
        add_action('wp_ajax_create_new_container', array($this, 'ajax_create_new_container'));
        
        // Charger le script de diagnostic
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/debug-ajax.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/debug-ajax.php';
        }
    }

    /**
     * Initialisation admin
     */
    public function admin_init()
    {
        // Vérifier les permissions
        if (!current_user_can('manage_restaurant_quotes')) {
            return;
        }
        
        // Gérer l'ancienne URL de création de produits de test (compatibilité)
        if (isset($_GET['create_test_products']) && $_GET['create_test_products'] === '1' && wp_verify_nonce($_GET['_wpnonce'], 'create_test_products')) {
            $this->create_test_products();
        }
        

        // Charger les styles globaux pour toutes les pages Block & Co
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Traiter les actions POST
        if (isset($_POST['restaurant_booking_action'])) {
            $this->handle_post_action();
        }
    }

    /**
     * Charger les assets admin
     */
    public function enqueue_admin_assets($hook)
    {
        // Charger seulement sur les pages Block & Co
        if (strpos($hook, 'restaurant-booking') !== false) {
            // Charger la médiathèque WordPress pour le sélecteur de logo
            wp_enqueue_media();
            wp_enqueue_style(
                'restaurant-booking-admin-global',
                RESTAURANT_BOOKING_PLUGIN_URL . 'assets/css/admin-global.css',
                array(),
                RESTAURANT_BOOKING_VERSION . '-' . time()
            );
            
            // Charger les corrections de mise en page
            wp_enqueue_style(
                'restaurant-booking-admin-layout-fix',
                RESTAURANT_BOOKING_PLUGIN_URL . 'assets/css/admin-layout-fix.css',
                array('restaurant-booking-admin-global'),
                RESTAURANT_BOOKING_VERSION . '-' . time()
            );
        }
    }

    /**
     * Ajouter les menus d'administration
     */
    public function add_admin_menus()
    {
        // Menu principal Block & Co
        add_menu_page(
            __('Block & Co', 'restaurant-booking'),
            __('Block & Co', 'restaurant-booking'),
            'manage_restaurant_quotes',
            'restaurant-booking',
            array($this, 'dashboard_page'),
            'dashicons-food',
            30
        );

        // Sous-menu Tableau de bord
        add_submenu_page(
            'restaurant-booking',
            __('Tableau de bord', 'restaurant-booking'),
            __('📊 Tableau de bord', 'restaurant-booking'),
            'manage_restaurant_quotes',
            'restaurant-booking',
            array($this, 'dashboard_page')
        );

        // Sous-menu Gestion des devis
        add_submenu_page(
            'restaurant-booking',
            __('Gestion des devis', 'restaurant-booking'),
            __('📋 Gestion des devis', 'restaurant-booking'),
            'manage_restaurant_quotes',
            'restaurant-booking-quotes',
            array($this, 'quotes_page')
        );

        // NOUVEAU : Gestionnaire unifié des catégories et produits
        add_submenu_page(
            'restaurant-booking',
            __('Catégories & Produits', 'restaurant-booking'),
            __('📂 Catégories & Produits', 'restaurant-booking'),
            'manage_restaurant_quotes',
            'restaurant-booking-categories-manager',
            array($this, 'categories_manager_page')
        );

        // ANCIEN : Page de catégories (redirige vers la nouvelle)
        add_submenu_page(
            null, // Pas dans le menu visible
            __('Catégories (ancien)', 'restaurant-booking'),
            __('Catégories (ancien)', 'restaurant-booking'),
            'manage_restaurant_quotes',
            'restaurant-booking-categories',
            array($this, 'categories_page')
        );

        // === PRODUITS ALIMENTAIRES ===
        add_submenu_page(
            'restaurant-booking',
            __('Plats Signature DOG', 'restaurant-booking'),
            __('🍽️ Plats Signature DOG', 'restaurant-booking'),
            'manage_restaurant_products',
            'restaurant-booking-products-dog',
            array($this, 'products_dog_page')
        );

        add_submenu_page(
            'restaurant-booking',
            __('Plats Signature CROQ', 'restaurant-booking'),
            __('🍽️ Plats Signature CROQ', 'restaurant-booking'),
            'manage_restaurant_products',
            'restaurant-booking-products-croq',
            array($this, 'products_croq_page')
        );

        add_submenu_page(
            'restaurant-booking',
            __('Menu Enfant (Mini Boss)', 'restaurant-booking'),
            __('🍽️ Menu Enfant (Mini Boss)', 'restaurant-booking'),
            'manage_restaurant_products',
            'restaurant-booking-products-mini-boss',
            array($this, 'products_mini_boss_page')
        );

        add_submenu_page(
            'restaurant-booking',
            __('Accompagnements', 'restaurant-booking'),
            __('🍽️ Accompagnements', 'restaurant-booking'),
            'manage_restaurant_products',
            'restaurant-booking-products-accompaniments',
            array($this, 'products_accompaniments_page')
        );

        add_submenu_page(
            'restaurant-booking',
            __('Buffet Salé', 'restaurant-booking'),
            __('🍽️ Buffet Salé', 'restaurant-booking'),
            'manage_restaurant_products',
            'restaurant-booking-products-buffet-sale',
            array($this, 'products_buffet_sale_page')
        );

        add_submenu_page(
            'restaurant-booking',
            __('Buffet Sucré', 'restaurant-booking'),
            __('🍽️ Buffet Sucré', 'restaurant-booking'),
            'manage_restaurant_products',
            'restaurant-booking-products-buffet-sucre',
            array($this, 'products_buffet_sucre_page')
        );

        // === GESTION DES BOISSONS ===
        add_submenu_page(
            'restaurant-booking',
            __('Boissons Soft', 'restaurant-booking'),
            __('🍷 Boissons Soft', 'restaurant-booking'),
            'manage_restaurant_products',
            'restaurant-booking-beverages-soft',
            array($this, 'beverages_soft_page')
        );

        add_submenu_page(
            'restaurant-booking',
            __('Vins', 'restaurant-booking'),
            __('🍷 Vins', 'restaurant-booking'),
            'manage_restaurant_products',
            'restaurant-booking-beverages-wines',
            array($this, 'beverages_wines_page')
        );

        add_submenu_page(
            'restaurant-booking',
            __('Bières Bouteilles', 'restaurant-booking'),
            __('🍷 Bières Bouteilles', 'restaurant-booking'),
            'manage_restaurant_products',
            'restaurant-booking-beverages-beers',
            array($this, 'beverages_beers_page')
        );

        add_submenu_page(
            'restaurant-booking',
            __('Fûts de Bière', 'restaurant-booking'),
            __('🍷 Fûts de Bière', 'restaurant-booking'),
            'manage_restaurant_products',
            'restaurant-booking-beverages-kegs',
            array($this, 'beverages_kegs_page')
        );


        // === OPTIONS ===

        add_submenu_page(
            'restaurant-booking',
            __('Options de Configuration', 'restaurant-booking'),
            __('⚙️ Options de Configuration', 'restaurant-booking'),
            'manage_restaurant_products',
            'restaurant-booking-options-unified',
            array($this, 'options_unified_page')
        );

        // Sous-menu pour créer les produits de test (temporaire)

        // Calendrier unifié (calendrier + Google Calendar)
        add_submenu_page(
            'restaurant-booking',
            __('Calendrier & Google Calendar', 'restaurant-booking'),
            __('📅 Calendrier & Google Calendar', 'restaurant-booking'),
            'manage_restaurant_quotes',
            'restaurant-booking-calendar',
            array($this, 'unified_calendar_page')
        );

        // Sous-menu Jeux (repositionné avant Paramètres)
        add_submenu_page(
            'restaurant-booking',
            __('Gestion des Jeux', 'restaurant-booking'),
            __('🎮 Jeux', 'restaurant-booking'),
            'manage_restaurant_quotes',
            'restaurant-booking-games',
            array($this, 'games_page')
        );


        // Sous-menu Paramètres (avec sous-sections)
        add_submenu_page(
            'restaurant-booking',
            __('Paramètres', 'restaurant-booking'),
            __('⚙️ Paramètres', 'restaurant-booking'),
            'manage_restaurant_settings',
            'restaurant-booking-settings',
            array($this, 'settings_page')
        );
        
        // Page de debug email
        add_submenu_page(
            'restaurant-booking',
            __('Debug Email', 'restaurant-booking'),
            __('🐛 Debug Email', 'restaurant-booking'),
            'manage_options',
            'restaurant-booking-debug-email',
            array($this, 'debug_email_page')
        );
        
        
        // Page de correction des échappements
        add_submenu_page(
            'restaurant-booking',
            __('Correction Échappements', 'restaurant-booking'),
            __('🔧 Correction Échappements', 'restaurant-booking'),
            'manage_options',
            'restaurant-booking-fix-escaped-quotes',
            array('RestaurantBooking_Fix_Escaped_Quotes_Admin', 'admin_page')
        );


    }

    /**
     * Page du tableau de bord
     */
    public function dashboard_page()
    {
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-dashboard.php';
        $dashboard = new RestaurantBooking_Dashboard();
        $dashboard->display();
    }


    /**
     * Page de gestion des devis
     */
    public function quotes_page()
    {
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-quotes-list.php';
        
        // Créer les devis de test UNIQUEMENT si demandé explicitement
        if (isset($_GET['create_test_quotes']) && $_GET['create_test_quotes'] === '1') {
            $this->create_test_quotes();
            wp_redirect(admin_url('admin.php?page=restaurant-booking-quotes&message=test_quotes_created'));
            exit;
        }
        
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $quote_id = isset($_GET['quote_id']) ? (int) $_GET['quote_id'] : 0;

        switch ($action) {
            case 'view':
                $this->quote_detail_page($quote_id);
                break;
            case 'edit':
                $this->quote_edit_page($quote_id);
                break;
            default:
                $quotes_list = new RestaurantBooking_Quotes_List();
                $quotes_list->display();
                break;
        }
    }

    /**
     * Page de détail d'un devis
     */
    private function quote_detail_page($quote_id)
    {
        $quote = RestaurantBooking_Quote::get($quote_id);
        if (!$quote) {
            wp_die(__('Devis introuvable', 'restaurant-booking'));
        }

        include RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/views/quote-detail.php';
    }

    /**
     * Page d'édition d'un devis
     */
    private function quote_edit_page($quote_id)
    {
        $quote = RestaurantBooking_Quote::get($quote_id);
        if (!$quote) {
            wp_die(__('Devis introuvable', 'restaurant-booking'));
        }

        include RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/views/quote-edit.php';
    }

    /**
     * Page des catégories
     */
    public function categories_page()
    {
        // REDIRECTION vers le nouveau gestionnaire unifié
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $category_id = isset($_GET['category_id']) ? (int) $_GET['category_id'] : 0;
        
        // Construire l'URL de redirection vers le nouveau gestionnaire
        $redirect_url = admin_url('admin.php?page=restaurant-booking-categories-manager');
        if ($action !== 'list') {
            $redirect_url .= '&action=' . $action;
            if ($category_id > 0) {
                $redirect_url .= '&category_id=' . $category_id;
            }
        }
        
        wp_redirect($redirect_url);
        exit;
        
        // Ancien code (conservé en commentaire)
        /*
        switch ($action) {
            case 'add':
                $this->category_form_page();
                break;
            case 'edit':
                $this->category_form_page($category_id);
                break;
            default:
                $this->categories_list_page();
                break;
        }
        */
    }

    /**
     * Page de liste des catégories
     */
    private function categories_list_page()
    {
        // Traitement des actions groupées
        if (isset($_POST['action']) && $_POST['action'] === 'bulk_delete' && isset($_POST['categories'])) {
            $this->bulk_delete_categories($_POST['categories']);
        }

        $page = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
        $per_page = 20;

        $args = array(
            'limit' => $per_page,
            'offset' => ($page - 1) * $per_page,
            'search' => isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '',
            'service_type' => isset($_GET['service_type']) ? sanitize_text_field($_GET['service_type']) : '',
            'is_active' => isset($_GET['is_active']) && $_GET['is_active'] !== '' ? (int) $_GET['is_active'] : ''
        );

        $result = RestaurantBooking_Category::get_list($args);
        $categories = $result['categories'];
        $total_pages = $result['pages'];

        include RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/views/categories-list.php';
    }

    /**
     * Page de formulaire de catégorie
     */
    private function category_form_page($category_id = 0)
    {
        $category = null;
        $is_edit = false;

        if ($category_id > 0) {
            $category = RestaurantBooking_Category::get($category_id);
            if (!$category) {
                wp_die(__('Catégorie introuvable', 'restaurant-booking'));
            }
            $is_edit = true;
        }

        // Traitement du formulaire
        if (isset($_POST['save_category'])) {
            $result = $this->save_category($category_id, $_POST);
            if (!is_wp_error($result)) {
                $redirect_url = add_query_arg(
                    array('page' => 'restaurant-booking-categories', 'message' => 'saved'),
                    admin_url('admin.php')
                );
                wp_redirect($redirect_url);
                exit;
            } else {
                $error_message = $result->get_error_message();
            }
        }

        $category_types = RestaurantBooking_Category::get_available_types();
        $service_types = RestaurantBooking_Category::get_service_types();

        include RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/views/category-form.php';
    }

    /**
     * Page des produits
     */
    public function products_page()
    {
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-products-admin.php';
        
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $product_id = isset($_GET['product_id']) ? (int) $_GET['product_id'] : 0;

        switch ($action) {
            case 'add':
                $this->product_form_page();
                break;
            case 'edit':
                $this->product_form_page($product_id);
                break;
            case 'import':
                $this->products_import_page();
                break;
            case 'export':
                $this->products_export();
                break;
            default:
                $products_admin = new RestaurantBooking_Products_Admin();
                $products_admin->display_list();
                break;
        }
    }

    /**
     * Page de formulaire de produit
     */
    private function product_form_page($product_id = 0)
    {
        $product = null;
        $is_edit = false;

        if ($product_id > 0) {
            $product = RestaurantBooking_Product::get($product_id);
            if (!$product) {
                wp_die(__('Produit introuvable', 'restaurant-booking'));
            }
            $is_edit = true;
        }

        // Traitement du formulaire
        if (isset($_POST['save_product'])) {
            $result = $this->save_product($product_id, $_POST);
            if (!is_wp_error($result)) {
                $redirect_url = add_query_arg(
                    array('page' => 'restaurant-booking-products', 'message' => 'saved'),
                    admin_url('admin.php')
                );
                wp_redirect($redirect_url);
                exit;
            } else {
                $error_message = $result->get_error_message();
            }
        }

        $categories = RestaurantBooking_Category::get_list(array('is_active' => 1, 'limit' => 999));

        include RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/views/product-form.php';
    }

    /**
     * Page de tarification (selon le cahier des charges)
     */
    public function pricing_page()
    {
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-settings-admin.php';
        
        $settings_admin = new RestaurantBooking_Settings_Admin();
        $settings_admin->display_pricing();
    }

    /**
     * Page des textes interface (selon le cahier des charges)
     */
    public function texts_page()
    {
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-settings-admin.php';
        
        $settings_admin = new RestaurantBooking_Settings_Admin();
        $settings_admin->display_texts();
    }

    /**
     * Page des emails (selon le cahier des charges)
     */
    public function emails_page()
    {
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-settings-admin.php';
        
        $settings_admin = new RestaurantBooking_Settings_Admin();
        $settings_admin->display_emails();
    }

    /**
     * Page du gestionnaire unifié des catégories (NOUVEAU)
     */
    public function categories_manager_page()
    {
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-categories-manager.php';
        
        $categories_manager = new RestaurantBooking_Categories_Manager();
        $categories_manager->display_main_page();
    }

    /**
     * Page des paramètres généraux
     */
    public function settings_page()
    {
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-settings-admin.php';
        
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        
        $settings_admin = new RestaurantBooking_Settings_Admin();
        $settings_admin->display($tab);
    }

    /**
     * Page du calendrier unifié (calendrier + Google Calendar)
     */
    public function unified_calendar_page()
    {
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'calendar';
        
        // Vérifier le statut Google Calendar pour tous les onglets
        $google_calendar_connected = false;
        if (class_exists('RestaurantBooking_Google_Calendar')) {
            $google_calendar = RestaurantBooking_Google_Calendar::get_instance();
            $access_token = get_option('restaurant_booking_google_access_token');
            $google_calendar_connected = !empty($access_token);
        }
        
        // Traitement des actions Google Calendar
        if (isset($_POST['save_google_settings'])) {
            $this->save_google_calendar_settings($_POST);
        }
        
        if (isset($_POST['test_connection']) && class_exists('RestaurantBooking_Google_Calendar')) {
            $google_calendar = RestaurantBooking_Google_Calendar::get_instance();
            $test_result = $google_calendar->test_connection();
        }
        
        if (isset($_POST['sync_calendar']) && class_exists('RestaurantBooking_Google_Calendar')) {
            $google_calendar = RestaurantBooking_Google_Calendar::get_instance();
            $sync_result = $google_calendar->sync_calendar();
            
            if ($sync_result) {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success is-dismissible"><p>';
                    echo __('✅ Synchronisation Google Calendar terminée avec succès !', 'restaurant-booking');
                    echo '</p></div>';
                });
            } else {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error is-dismissible"><p>';
                    echo __('❌ Erreur lors de la synchronisation Google Calendar.', 'restaurant-booking');
                    echo '</p></div>';
                });
            }
        }
        
        // Données pour l'onglet Google Calendar
        $auth_url = '';
        if (class_exists('RestaurantBooking_Google_Calendar')) {
            $google_calendar = RestaurantBooking_Google_Calendar::get_instance();
            $auth_url = $google_calendar->get_auth_url();
        }
        
        // Variables pour l'affichage
        $month = isset($_GET['month']) ? (int) $_GET['month'] : date('n');
        $year = isset($_GET['year']) ? (int) $_GET['year'] : date('Y');
        $service_type = isset($_GET['service_type']) ? sanitize_text_field($_GET['service_type']) : 'restaurant';
        
        // Traitement des actions sur les disponibilités
        if (isset($_POST['toggle_availability'])) {
            $this->toggle_availability($_POST);
        }
        
        // Inclure la vue unifiée avec toutes les données
        include RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/views/unified-calendar.php';
    }


    /**
     * Page des jeux
     */
    public function games_page()
    {
        if (class_exists('RestaurantBooking_Games_Admin')) {
            $games_admin = RestaurantBooking_Games_Admin::get_instance();
            $games_admin->render_games_page();
        } else {
            echo '<div class="wrap">';
            echo '<h1>🎮 ' . __('Jeux', 'restaurant-booking') . '</h1>';
            echo '<p>' . __('Module jeux non disponible.', 'restaurant-booking') . '</p>';
            echo '</div>';
        }
    }

    /**
     * Page de configuration Google Calendar
     */
    public function display_google_calendar_settings_page()
    {
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-google-calendar.php';
        
        $google_calendar = RestaurantBooking_Google_Calendar::get_instance();
        
        // Traitement des actions
        if (isset($_POST['save_google_settings'])) {
            $this->save_google_calendar_settings($_POST);
        }
        
        if (isset($_POST['test_connection'])) {
            $test_result = $google_calendar->test_connection();
        }
        
        if (isset($_POST['sync_calendar'])) {
            $sync_result = $google_calendar->sync_calendar();
            
            if ($sync_result) {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success is-dismissible"><p>';
                    echo __('✅ Synchronisation Google Calendar terminée avec succès !', 'restaurant-booking');
                    echo '</p></div>';
                });
            } else {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error is-dismissible"><p>';
                    echo __('❌ Erreur lors de la synchronisation Google Calendar.', 'restaurant-booking');
                    echo '</p></div>';
                });
            }
        }
        
        $auth_url = $google_calendar->get_auth_url();
        $access_token = get_option('restaurant_booking_google_access_token');
        $is_connected = !empty($access_token) && $access_token !== '' && $access_token !== false;
        
        include RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/views/google-calendar-settings.php';
    }


    /**
     * Sauvegarder une catégorie
     */
    private function save_category($category_id, $data)
    {
        // Vérifier le nonce
        if (!wp_verify_nonce($data['_wpnonce'], 'save_category')) {
            return new WP_Error('invalid_nonce', __('Token de sécurité invalide', 'restaurant-booking'));
        }

        $category_data = array(
            'name' => sanitize_text_field($data['name']),
            'slug' => sanitize_title($data['slug']),
            'type' => sanitize_text_field($data['type']),
            'service_type' => sanitize_text_field($data['service_type']),
            'description' => wp_kses_post($data['description']),
            'is_required' => isset($data['is_required']),
            'min_selection' => (int) $data['min_selection'],
            'max_selection' => !empty($data['max_selection']) ? (int) $data['max_selection'] : null,
            'min_per_person' => isset($data['min_per_person']),
            'display_order' => (int) $data['display_order'],
            'is_active' => isset($data['is_active'])
        );

        if ($category_id > 0) {
            return RestaurantBooking_Category::update($category_id, $category_data);
        } else {
            return RestaurantBooking_Category::create($category_data);
        }
    }

    /**
     * Sauvegarder un produit
     */
    private function save_product($product_id, $data)
    {
        // Vérifier le nonce
        if (!wp_verify_nonce($data['_wpnonce'], 'save_product')) {
            return new WP_Error('invalid_nonce', __('Token de sécurité invalide', 'restaurant-booking'));
        }

        $product_data = array(
            'category_id' => (int) $data['category_id'],
            'name' => sanitize_text_field($data['name']),
            'description' => wp_kses_post($data['description']),
            'short_description' => sanitize_text_field($data['short_description']),
            'price' => (float) $data['price'],
            'unit_type' => sanitize_text_field($data['unit_type']),
            'unit_label' => sanitize_text_field($data['unit_label']),
            'min_quantity' => (int) $data['min_quantity'],
            'max_quantity' => !empty($data['max_quantity']) ? (int) $data['max_quantity'] : null,
            'has_supplement' => isset($data['has_supplement']),
            'supplement_name' => sanitize_text_field($data['supplement_name']),
            'supplement_price' => (float) $data['supplement_price'],
            'image_url' => esc_url_raw($data['image_url']),
            'alcohol_degree' => !empty($data['alcohol_degree']) ? (float) $data['alcohol_degree'] : null,
            'volume_cl' => !empty($data['volume_cl']) ? (int) $data['volume_cl'] : null,
            'display_order' => (int) $data['display_order'],
            'is_active' => isset($data['is_active'])
        );

        if ($product_id > 0) {
            return RestaurantBooking_Product::update($product_id, $product_data);
        } else {
            return RestaurantBooking_Product::create($product_data);
        }
    }

    /**
     * Suppression groupée de catégories
     */
    private function bulk_delete_categories($category_ids)
    {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'bulk_categories')) {
            wp_die(__('Token de sécurité invalide', 'restaurant-booking'));
        }

        $deleted = 0;
        $errors = array();

        foreach ($category_ids as $category_id) {
            $result = RestaurantBooking_Category::delete((int) $category_id);
            if (is_wp_error($result)) {
                $errors[] = $result->get_error_message();
            } else {
                $deleted++;
            }
        }

        if ($deleted > 0) {
            add_action('admin_notices', function() use ($deleted) {
                echo '<div class="notice notice-success"><p>';
                printf(_n('%d catégorie supprimée', '%d catégories supprimées', $deleted, 'restaurant-booking'), $deleted);
                echo '</p></div>';
            });
        }

        if (!empty($errors)) {
            add_action('admin_notices', function() use ($errors) {
                echo '<div class="notice notice-error"><p>';
                echo implode('<br>', $errors);
                echo '</p></div>';
            });
        }
    }

    /**
     * Gérer les actions AJAX
     */
    public function handle_ajax_action()
    {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['nonce'], 'restaurant_booking_admin_nonce')) {
            wp_die(__('Token de sécurité invalide', 'restaurant-booking'));
        }

        $action = sanitize_text_field($_POST['admin_action']);

        switch ($action) {
            case 'delete_quote':
                $this->ajax_delete_quote();
                break;
            case 'update_quote_status':
                $this->ajax_update_quote_status();
                break;
            case 'send_quote_email':
                $this->ajax_send_quote_email();
                break;
            case 'test_email':
                $this->ajax_test_email();
                break;
            case 'clear_logs':
                $this->ajax_clear_logs();
                break;
            // Note: Les suppressions de produits utilisent maintenant des liens GET directs
            case 'toggle_game_status':
                $this->ajax_toggle_game_status();
                break;
            default:
                wp_send_json_error(__('Action inconnue', 'restaurant-booking'));
                break;
        }
    }

    /**
     * Action AJAX: Supprimer un devis
     */
    private function ajax_delete_quote()
    {
        $quote_id = (int) $_POST['quote_id'];
        $result = RestaurantBooking_Quote::delete($quote_id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(__('Devis supprimé', 'restaurant-booking'));
        }
    }

    /**
     * Action AJAX: Mettre à jour le statut d'un devis
     */
    private function ajax_update_quote_status()
    {
        $quote_id = (int) $_POST['quote_id'];
        $status = sanitize_text_field($_POST['status']);

        $result = RestaurantBooking_Quote::update($quote_id, array('status' => $status));

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(__('Statut mis à jour', 'restaurant-booking'));
        }
    }

    /**
     * Action AJAX: Supprimer un produit générique
     */
    private function ajax_delete_product()
    {
        if (!current_user_can('manage_restaurant_quotes')) {
            wp_send_json_error(__('Permissions insuffisantes', 'restaurant-booking'));
            return;
        }

        $product_id = (int) $_POST['product_id'];
        if ($product_id <= 0) {
            wp_send_json_error(__('ID produit invalide', 'restaurant-booking'));
            return;
        }

        $result = RestaurantBooking_Product::delete($product_id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(__('Produit supprimé avec succès', 'restaurant-booking'));
        }
    }

    /**
     * Action AJAX: Supprimer une bière
     */
    private function ajax_delete_beer()
    {
        if (!current_user_can('manage_restaurant_quotes')) {
            wp_send_json_error(__('Permissions insuffisantes', 'restaurant-booking'));
            return;
        }

        $product_id = (int) $_POST['beer_id'];
        if ($product_id <= 0) {
            wp_send_json_error(__('ID produit invalide', 'restaurant-booking'));
            return;
        }

        $result = RestaurantBooking_Product::delete($product_id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(__('Bière supprimée avec succès', 'restaurant-booking'));
        }
    }

    /**
     * Action AJAX: Supprimer un vin
     */
    private function ajax_delete_wine()
    {
        if (!current_user_can('manage_restaurant_quotes')) {
            wp_send_json_error(__('Permissions insuffisantes', 'restaurant-booking'));
            return;
        }

        $product_id = (int) $_POST['product_id'];
        if ($product_id <= 0) {
            wp_send_json_error(__('ID produit invalide', 'restaurant-booking'));
            return;
        }

        $result = RestaurantBooking_Product::delete($product_id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(__('Vin supprimé avec succès', 'restaurant-booking'));
        }
    }

    /**
     * Action AJAX: Supprimer une boisson sans alcool
     */
    private function ajax_delete_soft_beverage()
    {
        if (!current_user_can('manage_restaurant_quotes')) {
            wp_send_json_error(__('Permissions insuffisantes', 'restaurant-booking'));
            return;
        }

        $product_id = (int) $_POST['product_id'];
        if ($product_id <= 0) {
            wp_send_json_error(__('ID produit invalide', 'restaurant-booking'));
            return;
        }

        $result = RestaurantBooking_Product::delete($product_id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(__('Boisson supprimée avec succès', 'restaurant-booking'));
        }
    }

    /**
     * Action AJAX: Supprimer un fût
     */
    private function ajax_delete_keg()
    {
        if (!current_user_can('manage_restaurant_quotes')) {
            wp_send_json_error(__('Permissions insuffisantes', 'restaurant-booking'));
            return;
        }

        $product_id = (int) $_POST['product_id'];
        if ($product_id <= 0) {
            wp_send_json_error(__('ID produit invalide', 'restaurant-booking'));
            return;
        }

        $result = RestaurantBooking_Product::delete($product_id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(__('Fût supprimé avec succès', 'restaurant-booking'));
        }
    }

    /**
     * Action AJAX: Supprimer un accompagnement
     */
    private function ajax_delete_accompaniment()
    {
        if (!current_user_can('manage_restaurant_quotes')) {
            wp_send_json_error(__('Permissions insuffisantes', 'restaurant-booking'));
            return;
        }

        $product_id = (int) $_POST['accompaniment_id'];
        if ($product_id <= 0) {
            wp_send_json_error(__('ID produit invalide', 'restaurant-booking'));
            return;
        }

        $result = RestaurantBooking_Product::delete($product_id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(__('Accompagnement supprimé avec succès', 'restaurant-booking'));
        }
    }

    /**
     * Action AJAX: Supprimer un jeu
     */
    private function ajax_delete_game()
    {
        if (!current_user_can('manage_restaurant_quotes')) {
            wp_send_json_error(__('Permissions insuffisantes', 'restaurant-booking'));
            return;
        }

        $game_id = (int) $_POST['game_id'];
        if ($game_id <= 0) {
            wp_send_json_error(__('ID jeu invalide', 'restaurant-booking'));
            return;
        }

        $result = RestaurantBooking_Game::delete($game_id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(__('Jeu supprimé avec succès', 'restaurant-booking'));
        }
    }

    /**
     * Action AJAX: Basculer le statut d'un jeu
     */
    private function ajax_toggle_game_status()
    {
        if (!current_user_can('manage_restaurant_quotes')) {
            wp_send_json_error(__('Permissions insuffisantes', 'restaurant-booking'));
            return;
        }

        $game_id = (int) $_POST['game_id'];
        if ($game_id <= 0) {
            wp_send_json_error(__('ID jeu invalide', 'restaurant-booking'));
            return;
        }

        $result = RestaurantBooking_Game::toggle_status($game_id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(array(
                'new_status' => $result,
                'message' => __('Statut mis à jour', 'restaurant-booking')
            ));
        }
    }

    /**
     * Action AJAX: Supprimer un produit DOG
     */
    private function ajax_delete_dog_product()
    {
        if (!current_user_can('manage_restaurant_quotes')) {
            wp_send_json_error(__('Permissions insuffisantes', 'restaurant-booking'));
            return;
        }

        $product_id = (int) $_POST['product_id'];
        if ($product_id <= 0) {
            wp_send_json_error(__('ID produit invalide', 'restaurant-booking'));
            return;
        }

        $result = RestaurantBooking_Product::delete($product_id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(__('Produit DOG supprimé avec succès', 'restaurant-booking'));
        }
    }

    /**
     * Action AJAX: Supprimer un produit croque-monsieur
     */
    private function ajax_delete_croq_product()
    {
        if (!current_user_can('manage_restaurant_quotes')) {
            wp_send_json_error(__('Permissions insuffisantes', 'restaurant-booking'));
            return;
        }

        $product_id = (int) $_POST['product_id'];
        if ($product_id <= 0) {
            wp_send_json_error(__('ID produit invalide', 'restaurant-booking'));
            return;
        }

        $result = RestaurantBooking_Product::delete($product_id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(__('Produit croque-monsieur supprimé avec succès', 'restaurant-booking'));
        }
    }

    /**
     * Action AJAX: Supprimer un buffet salé
     */
    private function ajax_delete_buffet_sale()
    {
        if (!current_user_can('manage_restaurant_quotes')) {
            wp_send_json_error(__('Permissions insuffisantes', 'restaurant-booking'));
            return;
        }

        $product_id = (int) $_POST['product_id'];
        if ($product_id <= 0) {
            wp_send_json_error(__('ID produit invalide', 'restaurant-booking'));
            return;
        }

        $result = RestaurantBooking_Product::delete($product_id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(__('Buffet salé supprimé avec succès', 'restaurant-booking'));
        }
    }

    /**
     * Action AJAX: Supprimer un buffet sucré
     */
    private function ajax_delete_buffet_sucre()
    {
        if (!current_user_can('manage_restaurant_quotes')) {
            wp_send_json_error(__('Permissions insuffisantes', 'restaurant-booking'));
            return;
        }

        $product_id = (int) $_POST['product_id'];
        if ($product_id <= 0) {
            wp_send_json_error(__('ID produit invalide', 'restaurant-booking'));
            return;
        }

        $result = RestaurantBooking_Product::delete($product_id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(__('Buffet sucré supprimé avec succès', 'restaurant-booking'));
        }
    }

    /**
     * Action AJAX: Supprimer un mini boss
     */
    private function ajax_delete_mini_boss()
    {
        if (!current_user_can('manage_restaurant_quotes')) {
            wp_send_json_error(__('Permissions insuffisantes', 'restaurant-booking'));
            return;
        }

        $product_id = (int) $_POST['product_id'];
        if ($product_id <= 0) {
            wp_send_json_error(__('ID produit invalide', 'restaurant-booking'));
            return;
        }

        $result = RestaurantBooking_Product::delete($product_id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(__('Mini boss supprimé avec succès', 'restaurant-booking'));
        }
    }

    /**
     * Créer des devis de test
     */
    private function create_test_quotes()
    {
        global $wpdb;
        
        // Vérifier si des devis de test existent déjà (DEV ou TEST)
        $existing = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}restaurant_quotes WHERE quote_number LIKE 'DEV-2024-%' OR quote_number LIKE 'TEST-2024-%'");
        if ($existing > 0) {
            return; // Des devis de test existent déjà
        }
        
        $test_quotes = [
            [
                'quote_number' => 'DEV-2024-001',
                'service_type' => 'restaurant',
                'event_date' => '2024-02-15',
                'event_duration' => 3,
                'guest_count' => 25,
                'postal_code' => '75001',
                'distance_km' => 0,
                'customer_data' => json_encode([
                    'name' => 'Marie Dupont',
                    'email' => 'marie.dupont@email.com',
                    'phone' => '01 23 45 67 89',
                    'company' => 'SARL Dupont'
                ]),
                'selected_products' => json_encode([
                    ['name' => 'Menu Signature DOG', 'quantity' => 25, 'price' => 18.00],
                    ['name' => 'Dessert Maison', 'quantity' => 25, 'price' => 6.50]
                ]),
                'price_breakdown' => json_encode([
                    'base_price' => (float) RestaurantBooking_Settings::get('restaurant_base_price', 300),
                    'duration_supplement' => 50.00,
                    'products_total' => 612.50
                ]),
                'base_price' => (float) RestaurantBooking_Settings::get('restaurant_base_price', 300),
                'supplements_total' => 50.00,
                'products_total' => 612.50,
                'total_price' => 962.50,
                'status' => 'sent',
                'created_at' => '2024-01-15 10:30:00'
            ],
            [
                'quote_number' => 'DEV-2024-002',
                'service_type' => 'remorque',
                'event_date' => '2024-03-20',
                'event_duration' => 4,
                'guest_count' => 80,
                'postal_code' => '92100',
                'distance_km' => 15,
                'customer_data' => json_encode([
                    'name' => 'Pierre Martin',
                    'email' => 'pierre.martin@email.com',
                    'phone' => '06 78 90 12 34',
                    'company' => 'Entreprise Martin & Fils'
                ]),
                'selected_products' => json_encode([
                    ['name' => 'Menu Remorque Premium', 'quantity' => 80, 'price' => 15.00],
                    ['name' => 'Boissons Variées', 'quantity' => 80, 'price' => 3.50]
                ]),
                'price_breakdown' => json_encode([
                    'base_price' => (float) RestaurantBooking_Settings::get('remorque_base_price', 350),
                    'duration_supplement' => 100.00,
                    'guests_supplement' => 200.00,
                    'delivery_fee' => 75.00,
                    'products_total' => 1480.00
                ]),
                'base_price' => (float) RestaurantBooking_Settings::get('remorque_base_price', 350),
                'supplements_total' => 375.00,
                'products_total' => 1480.00,
                'total_price' => 2655.00,
                'status' => 'confirmed',
                'created_at' => '2024-01-10 14:20:00'
            ],
            [
                'quote_number' => 'DEV-2024-003',
                'service_type' => 'remorque',
                'event_date' => '2024-04-05',
                'event_duration' => 2,
                'guest_count' => 35,
                'postal_code' => '78000',
                'distance_km' => 25,
                'customer_data' => json_encode([
                    'name' => 'Sophie Bernard',
                    'email' => 'sophie.bernard@email.com',
                    'phone' => '01 45 67 89 12'
                ]),
                'selected_products' => json_encode([
                    ['name' => 'Menu Découverte', 'quantity' => 35, 'price' => 12.00],
                    ['name' => 'Apéritif Maison', 'quantity' => 35, 'price' => 4.00]
                ]),
                'price_breakdown' => json_encode([
                    'base_price' => (float) RestaurantBooking_Settings::get('remorque_base_price', 350),
                    'delivery_fee' => 100.00,
                    'products_total' => 560.00
                ]),
                'base_price' => (float) RestaurantBooking_Settings::get('remorque_base_price', 350),
                'supplements_total' => 100.00,
                'products_total' => 560.00,
                'total_price' => 1160.00,
                'status' => 'sent',
                'created_at' => '2024-01-20 09:15:00'
            ]
        ];

        foreach ($test_quotes as $quote) {
            $wpdb->insert(
                $wpdb->prefix . 'restaurant_quotes',
                $quote,
                ['%s', '%s', '%s', '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%s', '%s']
            );
        }
    }


    /**
     * Action AJAX: Envoyer un devis par email
     */
    private function ajax_send_quote_email()
    {
        $quote_id = (int) $_POST['quote_id'];
        
        // Ici on appellerait la classe Email pour envoyer le devis
        // require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-email.php';
        // $result = RestaurantBooking_Email::send_quote($quote_id);
        
        // Pour l'instant, simulation
        wp_send_json_success(__('Email envoyé', 'restaurant-booking'));
    }


    /**
     * Action AJAX: Vider les logs
     */
    private function ajax_clear_logs()
    {
        $count = RestaurantBooking_Logger::clear_all_logs();
        
        if ($count !== false) {
            wp_send_json_success(sprintf(__('%d logs supprimés', 'restaurant-booking'), $count));
        } else {
            wp_send_json_error(__('Erreur lors de la suppression', 'restaurant-booking'));
        }
    }

    /**
     * Traiter les actions POST
     */
    private function handle_post_action()
    {
        $action = sanitize_text_field($_POST['restaurant_booking_action']);
        
        switch ($action) {
            case 'save_settings':
                $this->save_settings($_POST);
                break;
            case 'import_settings':
                $this->import_settings();
                break;
            case 'export_settings':
                $this->export_settings();
                break;
            case 'save_product_mini_boss':
                $this->handle_save_product_mini_boss();
                break;
            case 'save_product_accompaniment':
                $this->handle_save_product_accompaniment();
                break;
            case 'save_product_dog':
                $this->handle_save_product_dog();
                break;
            case 'save_product_croq':
                $this->handle_save_product_croq();
                break;
            case 'save_product_buffet_sale':
                $this->handle_save_product_buffet_sale();
                break;
            case 'save_product_buffet_sucre':
                $this->handle_save_product_buffet_sucre();
                break;
            case 'save_beverage_beer':
                $this->handle_save_beverage_beer();
                break;
            case 'save_beverage_wine':
                $this->handle_save_beverage_wine();
                break;
            case 'save_beverage_soft':
                $this->handle_save_beverage_soft();
                break;
            case 'save_beer':
                $this->handle_save_beer();
                break;
            case 'save_keg':
                $this->handle_save_keg();
                break;
            case 'save_game':
                $this->handle_save_game();
                break;
        }
    }

    /**
     * Sauvegarder les paramètres
     */
    private function save_settings($data)
    {
        // Traitement dans la classe Settings Admin
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-settings-admin.php';
        $settings_admin = new RestaurantBooking_Settings_Admin();
        $settings_admin->save_settings($data);
    }

    /**
     * Gestionnaires de sauvegarde pour tous les modules
     */
    private function handle_save_product_mini_boss()
    {
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-products-mini-boss-admin.php';
        $admin = new RestaurantBooking_Products_MiniBoss_Admin();
        $admin->handle_save_mini_boss();
    }
    
    private function handle_save_product_accompaniment()
    {
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-products-accompaniments-admin.php';
        $admin = new RestaurantBooking_Products_Accompaniments_Admin();
        $admin->handle_save_accompaniment();
    }
    
    private function handle_save_product_buffet_sale()
    {
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-products-buffet-sale-admin.php';
        $admin = new RestaurantBooking_Products_BuffetSale_Admin();
        $admin->handle_save_buffet_sale();
    }
    
    private function handle_save_product_buffet_sucre()
    {
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-products-buffet-sucre-admin.php';
        $admin = new RestaurantBooking_Products_BuffetSucre_Admin();
        $admin->handle_save_buffet_sucre();
    }
    
    private function handle_save_beverage_beer()
    {
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-beverages-beers-admin.php';
        $admin = new RestaurantBooking_Beverages_Beers_Admin();
        $admin->handle_save_beer();
    }
    
    private function handle_save_beverage_wine()
    {
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-beverages-wines-admin.php';
        $admin = new RestaurantBooking_Beverages_Wines_Admin();
        $admin->handle_save_wine();
    }
    
    private function handle_save_beverage_soft()
    {
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-beverages-soft-admin.php';
        $admin = new RestaurantBooking_Beverages_Soft_Admin();
        $admin->handle_save_soft();
    }
    
    private function handle_save_game()
    {
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-games-admin.php';
        $admin = RestaurantBooking_Games_Admin::get_instance();
        
        // Déterminer si c'est une création ou une mise à jour
        $game_id = isset($_POST['game_id']) ? intval($_POST['game_id']) : 0;
        
        if ($game_id) {
            // Mise à jour
            $result = $admin->handle_edit_game($game_id);
            if (!is_wp_error($result)) {
                wp_redirect(admin_url('admin.php?page=restaurant-booking-games&message=2'));
                exit;
            }
        } else {
            // Création
            $result = $admin->handle_add_game();
            if (!is_wp_error($result)) {
                wp_redirect(admin_url('admin.php?page=restaurant-booking-games&message=1'));
                exit;
            }
        }
    }
    
    private function handle_save_beer()
    {
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-beverages-beers-admin.php';
        $admin = new RestaurantBooking_Beverages_Beers_Admin();
        $admin->handle_save_beer();
    }
    
    private function handle_save_keg()
    {
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-beverages-kegs-admin.php';
        $admin = new RestaurantBooking_Beverages_Kegs_Admin();
        $admin->handle_save_keg();
    }
    
    private function handle_save_product_dog()
    {
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-products-dog-admin.php';
        $admin = new RestaurantBooking_Products_Dog_Admin();
        $admin->handle_save_dog();
    }
    
    private function handle_save_product_croq()
    {
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-products-croq-admin.php';
        $admin = new RestaurantBooking_Products_Croq_Admin();
        $admin->handle_save_croq();
    }



    /**
     * AJAX pour synchroniser Google Calendar
     */
    public function ajax_sync_google_calendar()
    {
        // Vérification de sécurité
        if (!wp_verify_nonce($_POST['nonce'], 'sync_google_calendar')) {
            wp_send_json_error('Erreur de sécurité');
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permissions insuffisantes');
            return;
        }

        try {
            // Déclencher la synchronisation Google Calendar
            $google_calendar = RestaurantBooking_Google_Calendar::get_instance();
            if ($google_calendar) {
                $result = $google_calendar->sync_calendar();
                if ($result) {
                    wp_send_json_success('Synchronisation réussie');
                } else {
                    wp_send_json_error('Échec de la synchronisation');
                }
            } else {
                wp_send_json_error('Service Google Calendar non disponible');
            }
        } catch (Exception $e) {
            wp_send_json_error('Erreur : ' . $e->getMessage());
        }
    }

    /**
     * Importer les paramètres
     */
    private function import_settings()
    {
        if (!isset($_FILES['settings_file'])) {
            return;
        }

        $file = $_FILES['settings_file'];
        $content = file_get_contents($file['tmp_name']);
        $data = json_decode($content, true);

        if (!$data) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . __('Fichier invalide', 'restaurant-booking') . '</p></div>';
            });
            return;
        }

        $result = RestaurantBooking_Settings::import_settings($data);
        
        if ($result) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>' . __('Paramètres importés avec succès', 'restaurant-booking') . '</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . __('Erreur lors de l\'import', 'restaurant-booking') . '</p></div>';
            });
        }
    }

    /**
     * Exporter les paramètres
     */
    private function export_settings()
    {
        $settings = RestaurantBooking_Settings::export_settings();
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="restaurant-booking-settings-' . date('Y-m-d') . '.json"');
        echo json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Sauvegarder les paramètres Google Calendar
     */
    private function save_google_calendar_settings($data)
    {
        // Vérifier le nonce
        if (!wp_verify_nonce($data['_wpnonce'], 'google_calendar_settings')) {
            return new WP_Error('invalid_nonce', __('Token de sécurité invalide', 'restaurant-booking'));
        }

        // Sauvegarder les paramètres
        update_option('restaurant_booking_google_client_id', sanitize_text_field($data['client_id']));
        update_option('restaurant_booking_google_client_secret', sanitize_text_field($data['client_secret']));
        update_option('restaurant_booking_google_calendar_id', sanitize_text_field($data['calendar_id']));
        update_option('restaurant_booking_google_sync_frequency', sanitize_text_field($data['sync_frequency']));

        // Programmer la synchronisation automatique
        $this->schedule_google_sync($data['sync_frequency']);

        add_action('admin_notices', function() {
            echo '<div class="notice notice-success"><p>' . __('Paramètres Google Calendar sauvegardés', 'restaurant-booking') . '</p></div>';
        });

        return true;
    }

    /**
     * Programmer la synchronisation Google Calendar
     */
    private function schedule_google_sync($frequency)
    {
        // Supprimer l'ancien cron
        wp_clear_scheduled_hook('restaurant_booking_google_sync');

        // Programmer le nouveau si ce n'est pas manuel
        if ($frequency !== 'manual') {
            wp_schedule_event(time(), $frequency, 'restaurant_booking_google_sync');
        }
    }

    // ========================================
    // NOUVELLES MÉTHODES POUR LES PRODUITS SPÉCIALISÉS
    // ========================================

    /**
     * Page Plats Signature DOG
     */
    public function products_dog_page()
    {
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-products-dog-admin.php';
        $products_admin = new RestaurantBooking_Products_Dog_Admin();
        
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        
        switch ($action) {
            case 'add':
            case 'edit':
                $products_admin->display_form();
                break;
            default:
                $products_admin->display_list();
        }
    }

    /**
     * Page Plats Signature CROQ
     */
    public function products_croq_page()
    {
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-products-croq-admin.php';
        $products_admin = new RestaurantBooking_Products_Croq_Admin();
        
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        
        switch ($action) {
            case 'add':
            case 'edit':
                $products_admin->display_form();
                break;
            default:
                $products_admin->display_list();
        }
    }

    /**
     * Page Menu Enfant (Mini Boss)
     */
    public function products_mini_boss_page()
    {
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-products-mini-boss-admin.php';
        $products_admin = new RestaurantBooking_Products_MiniBoss_Admin();
        
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        
        switch ($action) {
            case 'add':
            case 'edit':
                $products_admin->display_form();
                break;
            default:
                $products_admin->display_list();
        }
    }
    
    
    

    /**
     * Page Accompagnements
     */
    public function products_accompaniments_page()
    {
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-products-accompaniments-admin.php';
        $products_admin = new RestaurantBooking_Products_Accompaniments_Admin();
        
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        
        switch ($action) {
            case 'add':
            case 'edit':
                $products_admin->display_form();
                break;
            default:
                $products_admin->display_list();
        }
    }

    /**
     * Page Buffet Salé
     */
    public function products_buffet_sale_page()
    {
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-products-buffet-sale-admin.php';
        $products_admin = new RestaurantBooking_Products_BuffetSale_Admin();
        
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        
        switch ($action) {
            case 'add':
            case 'edit':
                $products_admin->display_form();
                break;
            default:
                $products_admin->display_list();
        }
    }

    /**
     * Page Buffet Sucré
     */
    public function products_buffet_sucre_page()
    {
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-products-buffet-sucre-admin.php';
        $products_admin = new RestaurantBooking_Products_BuffetSucre_Admin();
        
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        
        switch ($action) {
            case 'add':
            case 'edit':
                $products_admin->display_form();
                break;
            default:
                $products_admin->display_list();
        }
    }

    /**
     * Page Boissons Soft
     */
    public function beverages_soft_page()
    {
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-beverages-soft-admin.php';
        $beverages_admin = new RestaurantBooking_Beverages_Soft_Admin();
        
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        
        switch ($action) {
            case 'add':
            case 'edit':
                $beverages_admin->display_form();
                break;
            default:
                $beverages_admin->display_list();
        }
    }

    /**
     * Page Vins
     */
    public function beverages_wines_page()
    {
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-beverages-wines-unified-admin.php';
        $wines_admin = new RestaurantBooking_Beverages_Wines_Unified_Admin();
        $wines_admin->display_main_page();
        return;
    }

    /**
     * Page Bières Bouteilles
     */
    public function beverages_beers_page()
    {
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-beverages-beers-admin.php';
        $beverages_admin = new RestaurantBooking_Beverages_Beers_Admin();
        
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        
        switch ($action) {
            case 'add':
            case 'edit':
                $beverages_admin->display_form();
                break;
            default:
                $beverages_admin->display_list();
        }
    }

    /**
     * Page Fûts de Bière
     */
    public function beverages_kegs_page()
    {
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-beverages-kegs-admin.php';
        $beverages_admin = new RestaurantBooking_Beverages_Kegs_Admin();
        
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        
        switch ($action) {
            case 'add':
            case 'edit':
                $beverages_admin->display_form();
                break;
            default:
                $beverages_admin->display_list();
        }
    }


    /**
     * Page Options Unifiées
     */
    public function options_unified_page()
    {
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-options-unified-admin.php';
        $options_admin = new RestaurantBooking_Options_Unified_Admin();
        $options_admin->display_page();
    }


    /**
     * Fonction de compatibilité - rediriger vers la nouvelle classe
     * @deprecated Utiliser RestaurantBooking_Test_Data_Creator::create_test_products()
     */
    public function create_test_products()
    {
        // Rediriger vers la nouvelle classe
        if (class_exists('RestaurantBooking_Test_Data_Creator')) {
            RestaurantBooking_Test_Data_Creator::ensure_categories_exist();
            $result = RestaurantBooking_Test_Data_Creator::create_test_products();
            
            if ($result['success']) {
                wp_redirect(admin_url('admin.php?page=restaurant-booking&message=test_products_created'));
            } else {
                wp_redirect(admin_url('admin.php?page=restaurant-booking&error=test_products_failed'));
            }
            exit;
                } else {
            wp_die(__('Classe de création de produits de test non trouvée', 'restaurant-booking'));
        }
    }

    /**
     * AJAX : Récupérer les logs
     */
    public function ajax_get_logs()
    {
        // Log de debug pour diagnostiquer
        error_log('[Restaurant Booking] AJAX get_logs appelé');
        error_log('[Restaurant Booking] POST data: ' . print_r($_POST, true));
        
        // Vérifier les permissions
        if (!current_user_can('manage_restaurant_settings')) {
            error_log('[Restaurant Booking] Permissions insuffisantes');
            wp_send_json_error(__('Permissions insuffisantes', 'restaurant-booking'));
            return;
        }

        // Vérifier le nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'get_logs')) {
            error_log('[Restaurant Booking] Nonce invalide');
            wp_send_json_error(__('Token de sécurité invalide', 'restaurant-booking'));
            return;
        }

        $type = sanitize_text_field($_POST['type'] ?? 'recent');
        error_log('[Restaurant Booking] Type de logs: ' . $type);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'restaurant_logs';
        
        // Vérifier si la table existe
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        error_log('[Restaurant Booking] Table existe: ' . ($table_exists ? 'OUI' : 'NON'));
        
        if (!$table_exists) {
            // Essayer de créer les tables
            if (class_exists('RestaurantBooking_Database')) {
                RestaurantBooking_Database::create_tables();
                error_log('[Restaurant Booking] Tentative de création des tables');
                
                // Créer quelques logs de test
                if (class_exists('RestaurantBooking_Logger')) {
                    RestaurantBooking_Logger::info('Table des logs créée avec succès');
                    RestaurantBooking_Logger::warning('Ceci est un exemple d\'avertissement');
                    RestaurantBooking_Logger::error('Ceci est un exemple d\'erreur pour les tests');
                }
            }
            
            // Revérifier si la table existe maintenant
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            
            if (!$table_exists) {
                wp_send_json_error(__('Impossible de créer la table des logs', 'restaurant-booking'));
                return;
            }
        }

        try {
            if ($type === 'error') {
                $logs = $wpdb->get_results(
                    "SELECT * FROM $table_name 
                     WHERE level = 'error' 
                     ORDER BY created_at DESC 
                     LIMIT 50"
                );
            } else {
                $logs = $wpdb->get_results(
                    "SELECT * FROM $table_name 
                     ORDER BY created_at DESC 
                     LIMIT 50"
                );
            }
            
            error_log('[Restaurant Booking] Logs trouvés: ' . count($logs));

            wp_send_json_success(array(
                'logs' => $logs,
                'count' => count($logs)
            ));

        } catch (Exception $e) {
            error_log('[Restaurant Booking] Erreur SQL: ' . $e->getMessage());
            wp_send_json_error(__('Erreur lors de la récupération des logs: ', 'restaurant-booking') . $e->getMessage());
        }
    }

    /**
     * AJAX : Récupérer le contenu du debug.log
     */
    public function ajax_get_debug_log()
    {
        // Vérifier les permissions
        if (!current_user_can('manage_restaurant_settings')) {
            wp_send_json_error(__('Permissions insuffisantes', 'restaurant-booking'));
            return;
        }

        // Vérifier le nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'get_debug_log')) {
            wp_send_json_error(__('Token de sécurité invalide', 'restaurant-booking'));
            return;
        }

        $debug_log_path = WP_CONTENT_DIR . '/debug.log';
        
        if (!file_exists($debug_log_path)) {
            wp_send_json_error(__('Fichier debug.log non trouvé. Vérifiez que WP_DEBUG_LOG est activé dans wp-config.php.', 'restaurant-booking'));
            return;
        }

        try {
            // Lire les 100 dernières lignes du fichier
            $lines = array();
            $file = new SplFileObject($debug_log_path);
            $file->seek(PHP_INT_MAX);
            $total_lines = $file->key() + 1;
            
            $start_line = max(0, $total_lines - 100);
            $file->seek($start_line);
            
            while (!$file->eof()) {
                $line = $file->current();
                if (!empty(trim($line))) {
                    $lines[] = $line;
                }
                $file->next();
            }

            wp_send_json_success(array(
                'content' => implode('', $lines),
                'file_size' => size_format(filesize($debug_log_path)),
                'total_lines' => $total_lines,
                'showing_lines' => count($lines)
            ));

        } catch (Exception $e) {
            wp_send_json_error(__('Erreur lors de la lecture du fichier: ', 'restaurant-booking') . $e->getMessage());
        }
    }

    /**
     * AJAX : Nettoyer les anciens logs
     */
    public function ajax_clean_logs()
    {
        if (!current_user_can('manage_restaurant_settings')) {
            wp_send_json_error(__('Permissions insuffisantes', 'restaurant-booking'));
            return;
        }

        if (!wp_verify_nonce($_POST['nonce'], 'clean_logs')) {
            wp_send_json_error(__('Token de sécurité invalide', 'restaurant-booking'));
            return;
        }

        try {
            global $wpdb;
            $table_name = $wpdb->prefix . 'restaurant_logs';
            $deleted = $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)", 90));
            
            wp_send_json_success(array(
                'message' => sprintf(__('%d logs anciens supprimés', 'restaurant-booking'), $deleted),
                'deleted' => $deleted
            ));
        } catch (Exception $e) {
            wp_send_json_error(__('Erreur: ', 'restaurant-booking') . $e->getMessage());
        }
    }

    /**
     * AJAX : Vider le cache
     */
    public function ajax_clear_cache()
    {
        if (!current_user_can('manage_restaurant_settings')) {
            wp_send_json_error(__('Permissions insuffisantes', 'restaurant-booking'));
            return;
        }

        if (!wp_verify_nonce($_POST['nonce'], 'clear_cache')) {
            wp_send_json_error(__('Token de sécurité invalide', 'restaurant-booking'));
            return;
        }

        try {
            // Vider les transients du plugin
            global $wpdb;
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_restaurant_booking_%'");
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_restaurant_booking_%'");
            
            wp_send_json_success(array(
                'message' => __('Cache du plugin vidé avec succès', 'restaurant-booking')
            ));
        } catch (Exception $e) {
            wp_send_json_error(__('Erreur: ', 'restaurant-booking') . $e->getMessage());
        }
    }

    /**
     * AJAX : Tester la base de données
     */
    public function ajax_test_database()
    {
        if (!current_user_can('manage_restaurant_settings')) {
            wp_send_json_error(__('Permissions insuffisantes', 'restaurant-booking'));
            return;
        }

        if (!wp_verify_nonce($_POST['nonce'], 'test_database')) {
            wp_send_json_error(__('Token de sécurité invalide', 'restaurant-booking'));
            return;
        }

        try {
            global $wpdb;
            $tables = array('restaurant_categories', 'restaurant_products', 'restaurant_settings', 'restaurant_quotes', 'restaurant_logs');
            $missing = array();
            
            foreach ($tables as $table) {
                $full_table_name = $wpdb->prefix . $table;
                $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'") == $full_table_name;
                if (!$exists) {
                    $missing[] = $table;
                }
            }
            
            if (empty($missing)) {
                wp_send_json_success(array(
                    'message' => __('Base de données OK - Toutes les tables sont présentes', 'restaurant-booking')
                ));
            } else {
                wp_send_json_error(__('Tables manquantes: ', 'restaurant-booking') . implode(', ', $missing));
            }
        } catch (Exception $e) {
            wp_send_json_error(__('Erreur: ', 'restaurant-booking') . $e->getMessage());
        }
    }


    /**
     * AJAX : Tester l'envoi d'email
     */
    public function ajax_test_email()
    {
        if (!current_user_can('manage_restaurant_settings')) {
            wp_send_json_error(__('Permissions insuffisantes', 'restaurant-booking'));
            return;
        }

        if (!wp_verify_nonce($_POST['nonce'], 'test_email')) {
            wp_send_json_error(__('Token de sécurité invalide', 'restaurant-booking'));
            return;
        }

        $test_email = sanitize_email($_POST['email']);
        if (!is_email($test_email)) {
            wp_send_json_error(__('Adresse email invalide', 'restaurant-booking'));
            return;
        }

        try {
            // Paramètres email
            $general_settings = get_option('restaurant_booking_general_settings', array());
            $company_name = $general_settings['company_name'] ?? 'Block & Co';

            $subject = __('Test email - ', 'restaurant-booking') . $company_name;
            $message = '<h2>' . __('Test de configuration email', 'restaurant-booking') . '</h2>';
            $message .= '<p>' . __('Ceci est un email de test envoyé depuis votre plugin Block & Co.', 'restaurant-booking') . '</p>';
            $message .= '<p>' . sprintf(__('Si vous recevez cet email, la configuration fonctionne correctement.', 'restaurant-booking')) . '</p>';
            $message .= '<p><strong>' . __('Informations techniques :', 'restaurant-booking') . '</strong></p>';
            $message .= '<ul>';
            $message .= '<li>' . __('Date : ', 'restaurant-booking') . date_i18n('d/m/Y H:i:s') . '</li>';
            $message .= '<li>' . __('Site : ', 'restaurant-booking') . get_bloginfo('name') . '</li>';
            $message .= '<li>' . __('URL : ', 'restaurant-booking') . home_url() . '</li>';
            $message .= '</ul>';

            $headers = array(
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . $company_name . ' <' . get_option('admin_email') . '>'
            );

            $sent = wp_mail($test_email, $subject, $message, $headers);

            if ($sent) {
                wp_send_json_success(__('Email de test envoyé avec succès !', 'restaurant-booking'));
            } else {
                wp_send_json_error(__('Échec de l\'envoi de l\'email. Vérifiez votre configuration SMTP.', 'restaurant-booking'));
            }

        } catch (Exception $e) {
            wp_send_json_error(__('Erreur: ', 'restaurant-booking') . $e->getMessage());
        }
    }

    /**
     * AJAX : Tester les notifications admin
     */
    public function ajax_test_admin_notification()
    {
        if (!current_user_can('manage_restaurant_settings')) {
            wp_send_json_error(array('message' => __('Permissions insuffisantes', 'restaurant-booking')));
            return;
        }

        if (!wp_verify_nonce($_POST['_wpnonce'], 'restaurant_booking_test_admin_notification')) {
            wp_send_json_error(array('message' => __('Token de sécurité invalide', 'restaurant-booking')));
            return;
        }

        try {
            // Vérifier si les notifications admin sont activées
            $email_settings = get_option('restaurant_booking_email_settings', array());
            $notifications_enabled = isset($email_settings['admin_notification_enabled']) ? $email_settings['admin_notification_enabled'] : '1';
            
            if ($notifications_enabled !== '1') {
                wp_send_json_error(array('message' => __('Les notifications admin sont désactivées. Activez-les d\'abord.', 'restaurant-booking')));
                return;
            }

            // Récupérer les emails admin
            $admin_emails = isset($email_settings['admin_notification_emails']) ? $email_settings['admin_notification_emails'] : array();
            if (empty($admin_emails)) {
                $admin_emails = array(get_option('admin_email'));
            }
            
            $admin_emails = array_filter($admin_emails);
            if (empty($admin_emails)) {
                wp_send_json_error(array('message' => __('Aucun email admin configuré.', 'restaurant-booking')));
                return;
            }

            // Créer un devis de test fictif
            $test_quote = array(
                'quote_number' => 'TEST-' . date('Y-m-d-His'),
                'service_type' => 'restaurant',
                'customer_data' => array(
                    'name' => 'Client Test',
                    'email' => 'client.test@example.com',
                    'phone' => '01 23 45 67 89'
                ),
                'client_firstname' => 'Client',
                'client_name' => 'Test',
                'client_email' => 'client.test@example.com',
                'client_phone' => '01 23 45 67 89',
                'event_date' => date('Y-m-d', strtotime('+1 week')),
                'guest_count' => 25,
                'total_price' => 1250.00
            );

            // Sujet personnalisable
            $subject = isset($email_settings['admin_notification_subject']) ? 
                       $email_settings['admin_notification_subject'] : 
                       'Nouveau devis reçu - Block & Co';
            
            $subject = '[TEST] ' . str_replace('{quote_number}', $test_quote['quote_number'], $subject);

            // Message de test
            $message = sprintf(
                __("🧪 CECI EST UN TEST DE NOTIFICATION ADMIN\n\n" .
                   "🎉 Nouvelle demande de devis reçue !\n\n" .
                   "📋 Détails du devis :\n" .
                   "• Numéro : %s\n" .
                   "• Service : %s\n" .
                   "• Client : %s\n" .
                   "• Email : %s\n" .
                   "• Téléphone : %s\n" .
                   "• Date événement : %s\n" .
                   "• Nombre de convives : %d\n" .
                   "• Montant total : %.2f€\n\n" .
                   "🔗 Voir le devis complet :\n%s\n\n" .
                   "📧 Cet email de test a été envoyé depuis votre configuration Block & Co.", 
                   'restaurant-booking'),
                $test_quote['quote_number'],
                '🍽️ Restaurant (TEST)',
                $test_quote['customer_data']['name'],
                $test_quote['customer_data']['email'],
                $test_quote['customer_data']['phone'],
                date_i18n('d/m/Y', strtotime($test_quote['event_date'])),
                $test_quote['guest_count'],
                $test_quote['total_price'],
                admin_url('admin.php?page=restaurant-booking-quotes')
            );

            // Headers
            $headers = array(
                'Content-Type: text/plain; charset=UTF-8',
                'From: ' . ($email_settings['sender_name'] ?? 'Block & Co') . ' <' . ($email_settings['sender_email'] ?? get_option('admin_email')) . '>'
            );

            $success_count = 0;
            $total_emails = count($admin_emails);

            foreach ($admin_emails as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $sent = wp_mail($email, $subject, $message, $headers);
                    if ($sent) {
                        $success_count++;
                    }
                }
            }

            if ($success_count > 0) {
                wp_send_json_success(array(
                    'message' => sprintf(
                        __('Test réussi ! %d/%d notifications envoyées aux emails admin configurés.', 'restaurant-booking'),
                        $success_count,
                        $total_emails
                    )
                ));
            } else {
                wp_send_json_error(array('message' => __('Échec de l\'envoi. Vérifiez votre configuration SMTP.', 'restaurant-booking')));
            }

        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Erreur: ', 'restaurant-booking') . $e->getMessage()));
        }
    }

    /**
     * AJAX : Déboguer la récupération d'événements Google Calendar
     */
    public function ajax_debug_google_events_retrieval()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissions insuffisantes', 'restaurant-booking'));
            return;
        }

        if (!wp_verify_nonce($_POST['nonce'], 'debug_google_sync')) {
            wp_send_json_error(__('Token de sécurité invalide', 'restaurant-booking'));
            return;
        }

        try {
            // Récupérer le client Google Calendar
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'google-calendar-simple.php';
            $google_client = RestaurantBooking_Google_Calendar_Simple::get_instance();
            
            if (!$google_client) {
                wp_send_json_error('Service Google Calendar non disponible');
                return;
            }

            // Récupérer les événements depuis le début du mois courant
            $time_min = date('c', strtotime('first day of this month'));
            $time_max = date('c', strtotime('+3 months'));
            
            $events_response = $google_client->list_events($time_min, $time_max);
            
            if (!$events_response) {
                wp_send_json_error('Impossible de récupérer les événements depuis Google Calendar');
                return;
            }

            // Filtrer les événements contenant "block"
            $block_events = array();
            if (isset($events_response['items'])) {
                foreach ($events_response['items'] as $event) {
                    if (isset($event['summary'])) {
                        $summary = strtolower($event['summary']);
                        if (strpos($summary, 'block') !== false) {
                            $block_events[] = array(
                                'id' => $event['id'] ?? '',
                                'summary' => $event['summary'] ?? '',
                                'start' => $event['start'] ?? array(),
                                'end' => $event['end'] ?? array()
                            );
                        }
                    }
                }
            }

            $result = array(
                'total_events' => count($events_response['items'] ?? array()),
                'block_events_count' => count($block_events),
                'block_events' => $block_events,
                'time_range' => array(
                    'from' => $time_min,
                    'to' => $time_max
                )
            );

            wp_send_json_success($result);

        } catch (Exception $e) {
            wp_send_json_error('Erreur: ' . $e->getMessage());
        }
    }

    /**
     * AJAX : Afficher le contenu de la base de données
     */
    public function ajax_debug_database_content()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissions insuffisantes', 'restaurant-booking'));
            return;
        }

        if (!wp_verify_nonce($_POST['nonce'], 'debug_google_sync')) {
            wp_send_json_error(__('Token de sécurité invalide', 'restaurant-booking'));
            return;
        }

        try {
            global $wpdb;
            
            // Récupérer tous les événements de septembre 2025
            $results = $wpdb->get_results($wpdb->prepare("
                SELECT * FROM {$wpdb->prefix}restaurant_availability 
                WHERE date BETWEEN %s AND %s
                ORDER BY date ASC, start_time ASC
            ", '2025-09-01', '2025-09-30'), ARRAY_A);

            wp_send_json_success($results);

        } catch (Exception $e) {
            wp_send_json_error('Erreur: ' . $e->getMessage());
        }
    }

    /**
     * AJAX : Forcer la synchronisation depuis Google Calendar
     */
    public function ajax_debug_force_sync_from_google()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissions insuffisantes', 'restaurant-booking'));
            return;
        }

        if (!wp_verify_nonce($_POST['nonce'], 'debug_google_sync')) {
            wp_send_json_error(__('Token de sécurité invalide', 'restaurant-booking'));
            return;
        }

        try {
            // Utiliser la classe Google Calendar pour synchroniser
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'google-calendar-simple.php';
            $google_client = RestaurantBooking_Google_Calendar_Simple::get_instance();
            
            if (!$google_client) {
                wp_send_json_error('Service Google Calendar non disponible');
                return;
            }

            // Appeler la méthode de synchronisation
            $result = $google_client->sync_calendar();
            
            if ($result) {
                wp_send_json_success(array('message' => 'Synchronisation forcée terminée avec succès'));
            } else {
                wp_send_json_error('Échec de la synchronisation');
            }

        } catch (Exception $e) {
            wp_send_json_error('Erreur: ' . $e->getMessage());
        }
    }

    /**
     * AJAX : Supprimer tous les événements Google synchronisés
     */
    public function ajax_debug_clear_google_events()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissions insuffisantes', 'restaurant-booking'));
            return;
        }

        if (!wp_verify_nonce($_POST['nonce'], 'debug_google_sync')) {
            wp_send_json_error(__('Token de sécurité invalide', 'restaurant-booking'));
            return;
        }

        try {
            global $wpdb;
            
            // Supprimer tous les événements avec google_event_id
            $deleted = $wpdb->delete(
                $wpdb->prefix . 'restaurant_availability',
                array('google_event_id' => array('!=', ''), '!=' => null),
                array('%s')
            );
            
            // Aussi supprimer ceux avec blocked_reason = 'Synchronisé depuis Google Calendar'
            $deleted += $wpdb->delete(
                $wpdb->prefix . 'restaurant_availability',
                array('blocked_reason' => 'Synchronisé depuis Google Calendar'),
                array('%s')
            );

            wp_send_json_success(array('message' => "Supprimé $deleted événements Google synchronisés"));

        } catch (Exception $e) {
            wp_send_json_error('Erreur: ' . $e->getMessage());
        }
    }

    /**
     * AJAX : Afficher la réponse brute de Google Calendar API
     */
    public function ajax_debug_raw_google_response()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissions insuffisantes', 'restaurant-booking'));
            return;
        }

        if (!wp_verify_nonce($_POST['nonce'], 'debug_google_sync')) {
            wp_send_json_error(__('Token de sécurité invalide', 'restaurant-booking'));
            return;
        }

        try {
            // Récupérer le client Google Calendar
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'google-calendar-simple.php';
            $google_client = RestaurantBooking_Google_Calendar_Simple::get_instance();
            
            if (!$google_client) {
                wp_send_json_error('Service Google Calendar non disponible');
                return;
            }

            // Récupérer les événements depuis le début du mois courant
            $time_min = date('c', strtotime('first day of this month'));
            $time_max = date('c', strtotime('+3 months'));
            
            $events_response = $google_client->list_events($time_min, $time_max);
            
            wp_send_json_success($events_response);

        } catch (Exception $e) {
            wp_send_json_error('Erreur: ' . $e->getMessage());
        }
    }

    /**
     * AJAX : Corriger le statut de disponibilité des événements Google
     */
    public function ajax_debug_fix_availability_status()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissions insuffisantes', 'restaurant-booking'));
            return;
        }

        if (!wp_verify_nonce($_POST['nonce'], 'debug_google_sync')) {
            wp_send_json_error(__('Token de sécurité invalide', 'restaurant-booking'));
            return;
        }

        try {
            global $wpdb;
            
            // D'abord, diagnostiquer ce qui est dans la base de données
            $debug_info = array();
            
            // Compter tous les événements de septembre 2025
            $total_events = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}restaurant_availability WHERE date BETWEEN '2025-09-01' AND '2025-09-30'");
            $debug_info[] = "Total événements septembre 2025: $total_events";
            
            // Compter les événements avec google_event_id
            $google_events = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}restaurant_availability WHERE google_event_id IS NOT NULL AND google_event_id != ''");
            $debug_info[] = "Événements avec google_event_id: $google_events";
            
            // Compter les événements avec blocked_reason = 'Synchronisé depuis Google Calendar'
            $sync_events = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}restaurant_availability WHERE blocked_reason = %s", 'Synchronisé depuis Google Calendar'));
            $debug_info[] = "Événements avec blocked_reason 'Synchronisé depuis Google Calendar': $sync_events";
            
            // Lister tous les événements de septembre avec leurs détails
            $all_events = $wpdb->get_results("SELECT id, date, notes, blocked_reason, google_event_id, is_available FROM {$wpdb->prefix}restaurant_availability WHERE date BETWEEN '2025-09-01' AND '2025-09-30'", ARRAY_A);
            
            $updated = 0;
            
            // Corriger TOUS les événements de septembre qui ont un google_event_id OU qui contiennent "Block" dans les notes
            foreach ($all_events as $event) {
                $should_update = false;
                $reason = '';
                
                // Si l'événement a un google_event_id
                if (!empty($event['google_event_id'])) {
                    $should_update = true;
                    $reason = 'a un google_event_id';
                }
                
                // Si l'événement contient "Block" dans les notes
                if (stripos($event['notes'], 'block') !== false) {
                    $should_update = true;
                    $reason .= ($reason ? ' et ' : '') . 'contient "Block" dans les notes';
                }
                
                if ($should_update && $event['is_available'] != 0) {
                    $result = $wpdb->update(
                        $wpdb->prefix . 'restaurant_availability',
                        array('is_available' => 0),
                        array('id' => $event['id']),
                        array('%d'),
                        array('%d')
                    );
                    
                    if ($result) {
                        $updated++;
                        $debug_info[] = "Corrigé événement ID {$event['id']} ({$event['date']}, {$event['notes']}) - $reason";
                    }
                }
            }
            
            wp_send_json_success(array(
                'message' => "Corrigé le statut de $updated événement(s) Google Calendar",
                'debug' => $debug_info
            ));

        } catch (Exception $e) {
            wp_send_json_error('Erreur: ' . $e->getMessage());
        }
    }

    /**
     * AJAX pour créer une nouvelle contenance
     */
    public function ajax_create_new_container()
    {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['nonce'], 'create_new_container')) {
            wp_send_json_error(__('Erreur de sécurité', 'restaurant-booking'));
        }

        // Vérifier les permissions
        if (!current_user_can('manage_restaurant_products')) {
            wp_send_json_error(__('Permissions insuffisantes', 'restaurant-booking'));
        }

        // Récupérer les données
        $liters = intval($_POST['liters']);
        $label = sanitize_text_field($_POST['label']);

        // Validation
        if (empty($liters) || empty($label)) {
            wp_send_json_error(__('Tous les champs sont requis', 'restaurant-booking'));
        }

        // Charger le gestionnaire des contenances
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-container-manager.php';

        // Créer la nouvelle contenance
        $container_data = array(
            'liters' => $liters,
            'label' => $label,
            'is_active' => 1,
            'display_order' => 0
        );

        $result = RestaurantBooking_Container_Manager::create_container($container_data);

        if ($result) {
            wp_send_json_success(array(
                'message' => __('Contenance créée avec succès', 'restaurant-booking'),
                'container_id' => $result,
                'liters' => $liters,
                'label' => $label
            ));
        } else {
            wp_send_json_error(__('Cette contenance existe déjà ou erreur lors de la création', 'restaurant-booking'));
        }
    }
    
    /**
     * Page de debug email
     */
    public function debug_email_page()
    {
        // Traitement des actions de test
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'test_email' && isset($_POST['quote_id'])) {
                $quote_id = intval($_POST['quote_id']);
                
                if (class_exists('RestaurantBooking_Email')) {
                    echo '<div class="notice notice-info"><p>Test d\'envoi d\'email en cours...</p></div>';
                    
                    // Diagnostiquer les problèmes
                    $diagnosis = RestaurantBooking_Email::diagnose_email_issues($quote_id);
                    
                    if (!empty($diagnosis['issues'])) {
                        echo '<div class="notice notice-error"><p><strong>Problèmes détectés :</strong><br>';
                        foreach ($diagnosis['issues'] as $issue) {
                            echo '• ' . esc_html($issue) . '<br>';
                        }
                        echo '</p></div>';
                    }
                    
                    // Essayer d'envoyer l'email
                    $result = RestaurantBooking_Email::send_quote_email($quote_id);
                    
                    if ($result) {
                        echo '<div class="notice notice-success"><p>✅ Email envoyé avec succès !</p></div>';
                    } else {
                        echo '<div class="notice notice-error"><p>❌ Échec de l\'envoi de l\'email</p></div>';
                    }
                    
                    // Afficher les informations de diagnostic
                    echo '<div class="notice notice-info"><p><strong>Informations système :</strong><br>';
                    echo 'wp_mail disponible : ' . ($diagnosis['wp_mail_available'] ? 'Oui' : 'Non') . '<br>';
                    echo 'Plugins SMTP : ' . (!empty($diagnosis['smtp_plugins']) ? implode(', ', $diagnosis['smtp_plugins']) : 'Aucun') . '<br>';
                    echo 'Devis existe : ' . ($diagnosis['quote_exists'] ? 'Oui' : 'Non') . '<br>';
                    echo '</p></div>';
                }
            }
        }
        
        // Récupérer la liste des devis pour les tests
        global $wpdb;
        $quotes = $wpdb->get_results("
            SELECT id, quote_number, JSON_UNQUOTE(JSON_EXTRACT(customer_data, '$.email')) as customer_email, 
                   JSON_UNQUOTE(JSON_EXTRACT(customer_data, '$.firstname')) as firstname,
                   JSON_UNQUOTE(JSON_EXTRACT(customer_data, '$.name')) as lastname,
                   created_at
            FROM {$wpdb->prefix}restaurant_quotes 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        
        ?>
        <div class="wrap">
            <h1>🐛 Debug Email - Test d'envoi de devis</h1>
            
            <div class="card">
                <h2>Tester l'envoi d'email pour un devis</h2>
                <p>Sélectionnez un devis existant pour tester l'envoi d'email :</p>
                
                <?php if (empty($quotes)): ?>
                    <p><em>Aucun devis trouvé. Créez d'abord un devis via le formulaire.</em></p>
                <?php else: ?>
                    <form method="post">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Sélection</th>
                                    <th>N° Devis</th>
                                    <th>Client</th>
                                    <th>Email</th>
                                    <th>Date création</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($quotes as $quote): ?>
                                <tr>
                                    <td>
                                        <input type="radio" name="quote_id" value="<?php echo $quote->id; ?>" required>
                                    </td>
                                    <td><?php echo esc_html($quote->quote_number); ?></td>
                                    <td><?php echo esc_html($quote->firstname . ' ' . $quote->lastname); ?></td>
                                    <td><?php echo esc_html($quote->customer_email); ?></td>
                                    <td><?php echo esc_html($quote->created_at); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <p class="submit">
                            <input type="hidden" name="action" value="test_email">
                            <input type="submit" class="button button-primary" value="🧪 Tester l'envoi d'email">
                        </p>
                    </form>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h2>Configuration système</h2>
                <table class="form-table">
                    <tr>
                        <th>Fonction wp_mail</th>
                        <td><?php echo function_exists('wp_mail') ? '✅ Disponible' : '❌ Non disponible'; ?></td>
                    </tr>
                    <tr>
                        <th>Plugins SMTP détectés</th>
                        <td>
                            <?php
                            $smtp_plugins = [];
                            if (class_exists('WPMailSMTP\\Core')) {
                                $smtp_plugins[] = 'WP Mail SMTP';
                            }
                            if (class_exists('EasyWPSMTP')) {
                                $smtp_plugins[] = 'Easy WP SMTP';
                            }
                            if (function_exists('wp_mail_smtp')) {
                                $smtp_plugins[] = 'WP Mail SMTP (fonction)';
                            }
                            
                            echo !empty($smtp_plugins) ? implode(', ', $smtp_plugins) : 'Aucun plugin SMTP détecté';
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Classe PDF</th>
                        <td><?php echo class_exists('RestaurantBooking_PDF') ? '✅ Disponible' : '❌ Non disponible'; ?></td>
                    </tr>
                    <tr>
                        <th>Répertoire upload</th>
                        <td>
                            <?php
                            $upload_dir = wp_upload_dir();
                            $pdf_dir = $upload_dir['basedir'] . '/restaurant-booking/pdf/';
                            echo is_writable(dirname($pdf_dir)) ? '✅ Accessible en écriture' : '❌ Problème d\'accès';
                            echo '<br><code>' . esc_html($pdf_dir) . '</code>';
                            ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <?php
    }


}
