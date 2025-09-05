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

        // Traiter les actions POST
        if (isset($_POST['restaurant_booking_action'])) {
            $this->handle_post_action();
        }
    }

    /**
     * Ajouter les menus d'administration
     */
    public function add_admin_menus()
    {
        // Menu principal selon le cahier des charges
        add_menu_page(
            __('Restaurant Devis', 'restaurant-booking'),
            __('Restaurant Devis', 'restaurant-booking'),
            'manage_restaurant_quotes',
            'restaurant-booking',
            array($this, 'dashboard_page'),
            'dashicons-food',
            30
        );

        // Sous-menu Gestion des devis
        add_submenu_page(
            'restaurant-booking',
            __('Gestion des devis', 'restaurant-booking'),
            __('Gestion des devis', 'restaurant-booking'),
            'manage_restaurant_quotes',
            'restaurant-booking-quotes',
            array($this, 'quotes_page')
        );

        // Sous-menu Catégories
        add_submenu_page(
            'restaurant-booking',
            __('Catégories', 'restaurant-booking'),
            __('Catégories', 'restaurant-booking'),
            'manage_restaurant_products',
            'restaurant-booking-categories',
            array($this, 'categories_page')
        );

        // Sous-menu Produits
        add_submenu_page(
            'restaurant-booking',
            __('Produits', 'restaurant-booking'),
            __('Produits', 'restaurant-booking'),
            'manage_restaurant_products',
            'restaurant-booking-products',
            array($this, 'products_page')
        );

        // Sous-menu Tarification (selon le cahier des charges)
        add_submenu_page(
            'restaurant-booking',
            __('Tarification', 'restaurant-booking'),
            __('Tarification', 'restaurant-booking'),
            'manage_restaurant_settings',
            'restaurant-booking-pricing',
            array($this, 'pricing_page')
        );

        // Sous-menu Textes interface (selon le cahier des charges)
        add_submenu_page(
            'restaurant-booking',
            __('Textes interface', 'restaurant-booking'),
            __('Textes interface', 'restaurant-booking'),
            'manage_restaurant_settings',
            'restaurant-booking-texts',
            array($this, 'texts_page')
        );

        // Sous-menu Emails (selon le cahier des charges)
        add_submenu_page(
            'restaurant-booking',
            __('Emails', 'restaurant-booking'),
            __('Emails', 'restaurant-booking'),
            'manage_restaurant_settings',
            'restaurant-booking-emails',
            array($this, 'emails_page')
        );

        // Sous-menu Paramètres généraux (renommé)
        add_submenu_page(
            'restaurant-booking',
            __('Paramètres', 'restaurant-booking'),
            __('Paramètres', 'restaurant-booking'),
            'manage_restaurant_settings',
            'restaurant-booking-settings',
            array($this, 'settings_page')
        );

        // Sous-menu Calendrier
        add_submenu_page(
            'restaurant-booking',
            __('Calendrier', 'restaurant-booking'),
            __('Calendrier', 'restaurant-booking'),
            'manage_restaurant_quotes',
            'restaurant-booking-calendar',
            array($this, 'calendar_page')
        );

        // Sous-menu Diagnostics (visible seulement en mode debug)
        if (RESTAURANT_BOOKING_DEBUG) {
            add_submenu_page(
                'restaurant-booking',
                __('Diagnostics', 'restaurant-booking'),
                __('Diagnostics', 'restaurant-booking'),
                'manage_options',
                'restaurant-booking-diagnostics',
                array($this, 'diagnostics_page')
            );
        }
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
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $category_id = isset($_GET['category_id']) ? (int) $_GET['category_id'] : 0;

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
     * Page du calendrier
     */
    public function calendar_page()
    {
        $month = isset($_GET['month']) ? (int) $_GET['month'] : date('n');
        $year = isset($_GET['year']) ? (int) $_GET['year'] : date('Y');
        $service_type = isset($_GET['service_type']) ? sanitize_text_field($_GET['service_type']) : 'restaurant';

        // Traitement des actions sur les disponibilités
        if (isset($_POST['toggle_availability'])) {
            $this->toggle_availability($_POST);
        }

        include RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/views/calendar.php';
    }

    /**
     * Page de diagnostics
     */
    public function diagnostics_page()
    {
        $health_check = RestaurantBooking_Database::check_database_health();
        $logs_stats = RestaurantBooking_Logger::get_log_stats();
        $logs_size = RestaurantBooking_Logger::get_logs_size();

        // Actions de maintenance
        if (isset($_POST['maintenance_action'])) {
            $this->handle_maintenance_action($_POST['maintenance_action']);
        }

        include RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/views/diagnostics.php';
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
     * Action AJAX: Test d'envoi d'email
     */
    private function ajax_test_email()
    {
        $email = sanitize_email($_POST['test_email']);
        
        $result = wp_mail(
            $email,
            __('Test email Restaurant Booking', 'restaurant-booking'),
            __('Ceci est un email de test depuis le plugin Restaurant Booking.', 'restaurant-booking')
        );

        if ($result) {
            wp_send_json_success(__('Email de test envoyé', 'restaurant-booking'));
        } else {
            wp_send_json_error(__('Erreur lors de l\'envoi', 'restaurant-booking'));
        }
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
}
