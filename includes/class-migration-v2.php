<?php
/**
 * Classe de migration vers la version 2
 *
 * @package RestaurantBooking
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Migration_V2
{
    /**
     * Version de la base de données v2
     */
    const DB_VERSION_V2 = '2.0.0';

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
        add_action('init', array($this, 'check_migration_needed'));
    }

    /**
     * Vérifier si une migration est nécessaire
     */
    public function check_migration_needed()
    {
        $current_version = get_option('restaurant_booking_db_version', '0.0.0');
        
        if (version_compare($current_version, self::DB_VERSION_V2, '<')) {
            $this->run_migration();
        }
    }

    /**
     * Exécuter la migration complète
     */
    public function run_migration()
    {
        global $wpdb;

        try {
            // Démarrer une transaction
            $wpdb->query('START TRANSACTION');

            // Étape 1: Créer les nouvelles tables
            $this->create_new_tables();

            // Étape 2: Modifier les tables existantes
            $this->modify_existing_tables();

            // Étape 3: Insérer les nouvelles données par défaut
            $this->insert_new_default_data();

            // Étape 4: Migrer les données existantes
            $this->migrate_existing_data();

            // Étape 5: Nettoyer les données obsolètes
            $this->cleanup_obsolete_data();

            // Valider la transaction
            $wpdb->query('COMMIT');

            // Mettre à jour la version
            update_option('restaurant_booking_db_version', self::DB_VERSION_V2);

            // Log de succès
            RestaurantBooking_Logger::info('Migration v2 terminée avec succès');

        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            $wpdb->query('ROLLBACK');
            
            RestaurantBooking_Logger::error('Erreur lors de la migration v2', array(
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ));
            
            throw $e;
        }
    }

    /**
     * Créer les nouvelles tables
     */
    private function create_new_tables()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Table des jeux - SUPPRIMÉE (les jeux sont maintenant dans restaurant_products)

        // Table des suppléments de produits
        $table_supplements = $wpdb->prefix . 'restaurant_product_supplements';
        $sql_supplements = "CREATE TABLE $table_supplements (
            id int(11) NOT NULL AUTO_INCREMENT,
            product_id int(11) NOT NULL,
            name varchar(255) NOT NULL,
            price decimal(10,2) NOT NULL DEFAULT 0.00,
            max_quantity int(11) DEFAULT NULL,
            display_order int(11) NOT NULL DEFAULT 0,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY is_active (is_active),
        ) $charset_collate;";

        // Table des tailles de boissons
        $table_beverage_sizes = $wpdb->prefix . 'restaurant_beverage_sizes';
        $sql_beverage_sizes = "CREATE TABLE $table_beverage_sizes (
            id int(11) NOT NULL AUTO_INCREMENT,
            product_id int(11) NOT NULL,
            size_cl int(11) NOT NULL,
            size_label varchar(50) NOT NULL,
            price decimal(10,2) NOT NULL DEFAULT 0.00,
            display_order int(11) NOT NULL DEFAULT 0,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY is_active (is_active),
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Exécuter les créations
        dbDelta($sql_supplements);
        dbDelta($sql_beverage_sizes);

        RestaurantBooking_Logger::info('Nouvelles tables créées pour v2');
    }

    /**
     * Modifier les tables existantes
     */
    private function modify_existing_tables()
    {
        global $wpdb;

        // Modifications table categories
        $wpdb->query("ALTER TABLE {$wpdb->prefix}restaurant_categories 
                     MODIFY COLUMN type enum('plat_signature', 'mini_boss', 'accompagnement', 'buffet_sale', 'buffet_sucre', 'soft', 'vin_blanc', 'vin_rouge', 'vin_rose', 'cremant', 'biere', 'fut', 'sauce', 'jeu', 'tireuse', 'option_restaurant', 'option_remorque') NOT NULL");
        
        // Vérifier si la colonne is_featured existe déjà dans categories
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}restaurant_categories LIKE 'is_featured'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}restaurant_categories 
                         ADD COLUMN is_featured tinyint(1) NOT NULL DEFAULT 0 AFTER min_per_person");
        }

        // Modifications table products - vérifier chaque colonne
        $unit_per_person_exists = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}restaurant_products LIKE 'unit_per_person'");
        if (empty($unit_per_person_exists)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}restaurant_products 
                         ADD COLUMN unit_per_person enum('gramme', 'piece') DEFAULT NULL AFTER unit_label");
        }
        
        $is_featured_exists = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}restaurant_products LIKE 'is_featured'");
        if (empty($is_featured_exists)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}restaurant_products 
                         ADD COLUMN is_featured tinyint(1) NOT NULL DEFAULT 0 AFTER has_supplement");
        }
        
        $keg_10l_exists = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}restaurant_products LIKE 'keg_size_10l_price'");
        if (empty($keg_10l_exists)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}restaurant_products 
                         ADD COLUMN keg_size_10l_price decimal(10,2) DEFAULT NULL AFTER volume_cl");
        }
        
        $keg_20l_exists = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}restaurant_products LIKE 'keg_size_20l_price'");
        if (empty($keg_20l_exists)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}restaurant_products 
                         ADD COLUMN keg_size_20l_price decimal(10,2) DEFAULT NULL AFTER keg_size_10l_price");
        }

        // Ajouter les index seulement s'ils n'existent pas
        $index_cat_exists = $wpdb->get_results("SHOW INDEX FROM {$wpdb->prefix}restaurant_categories WHERE Key_name = 'is_featured'");
        if (empty($index_cat_exists)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}restaurant_categories ADD KEY is_featured (is_featured)");
        }
        
        $index_prod_exists = $wpdb->get_results("SHOW INDEX FROM {$wpdb->prefix}restaurant_products WHERE Key_name = 'is_featured'");
        if (empty($index_prod_exists)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}restaurant_products ADD KEY is_featured (is_featured)");
        }

        RestaurantBooking_Logger::info('Tables existantes modifiées pour v2');
    }

    /**
     * Insérer les nouvelles données par défaut
     */
    private function insert_new_default_data()
    {
        global $wpdb;

        // Nouveaux paramètres v2
        $new_settings = array(
            // Options remorque
            array('remorque_tireuse_price', '50.00', 'number', 'pricing', 'Prix mise à disposition tireuse'),
            array('remorque_games_base_price', '70.00', 'number', 'pricing', 'Prix installation jeux'),
            
            // Suppléments par zone
            array('delivery_zone_30_50_price', '20.00', 'number', 'pricing', 'Supplément zone 30-50km'),
            array('delivery_zone_50_100_price', '70.00', 'number', 'pricing', 'Supplément zone 50-100km'),
            array('delivery_zone_100_150_price', '120.00', 'number', 'pricing', 'Supplément zone 100-150km'),
            
            // Textes widget étape 0
            array('widget_service_selection_title', 'Choisissez votre service', 'text', 'widget_texts', 'Titre sélection service'),
            array('widget_restaurant_card_title', 'PRIVATISATION DU RESTAURANT', 'text', 'widget_texts', 'Titre card restaurant'),
            array('widget_restaurant_card_subtitle', 'De 10 à 30 personnes', 'text', 'widget_texts', 'Sous-titre card restaurant'),
            array('widget_restaurant_card_description', 'Privatisez notre restaurant pour vos événements intimes et profitez d\'un service personnalisé dans un cadre chaleureux.', 'html', 'widget_texts', 'Description card restaurant'),
            array('widget_remorque_card_title', 'Privatisation de la remorque Block', 'text', 'widget_texts', 'Titre card remorque'),
            array('widget_remorque_card_subtitle', 'À partir de 20 personnes', 'text', 'widget_texts', 'Sous-titre card remorque'),
            array('widget_remorque_card_description', 'Notre remorque mobile se déplace pour vos événements extérieurs et grandes réceptions.', 'html', 'widget_texts', 'Description card remorque'),
            
            // Textes étapes restaurant
            array('restaurant_step1_title', 'Pourquoi privatiser notre restaurant ?', 'text', 'widget_texts', 'Titre étape 1 restaurant'),
            array('restaurant_step1_card_title', 'Comment ça fonctionne ?', 'text', 'widget_texts', 'Titre card étape 1 restaurant'),
            array('restaurant_step1_process_list', '["Forfait de base", "Choix du formule repas (personnalisable)", "Choix des boissons (optionnel)", "Coordonnées / Contact"]', 'json', 'widget_texts', 'Liste processus restaurant'),
            
            array('restaurant_step2_title', 'FORFAIT DE BASE', 'text', 'widget_texts', 'Titre étape 2 restaurant'),
            array('restaurant_step2_card_title', 'FORFAIT DE BASE PRIVATISATION RESTO', 'text', 'widget_texts', 'Titre card forfait restaurant'),
            array('restaurant_step2_included_items', '["Mise à disposition des murs de Block", "Notre équipe salle + cuisine assurant la prestation", "Présentation + mise en place buffets, selon vos choix", "Mise à disposition vaisselle + verrerie", "Entretien + nettoyage"]', 'json', 'widget_texts', 'Éléments inclus forfait restaurant'),
            
            // Textes étapes remorque
            array('remorque_step1_title', 'Pourquoi privatiser notre remorque Block ?', 'text', 'widget_texts', 'Titre étape 1 remorque'),
            array('remorque_step1_process_list', '["Forfait de base", "Choix du formule repas (personnalisable)", "Choix des boissons (optionnel)", "Choix des options (optionnel)", "Coordonnées/Contact"]', 'json', 'widget_texts', 'Liste processus remorque'),
            
            array('remorque_step2_title', 'FORFAIT DE BASE', 'text', 'widget_texts', 'Titre étape 2 remorque'),
            array('remorque_step2_card_title', 'FORFAIT DE BASE PRIVATISATION REMORQUE BLOCK', 'text', 'widget_texts', 'Titre card forfait remorque'),
            array('remorque_step2_included_items', '["Notre équipe salle + cuisine assurant la prestation", "Déplacement et installation de la remorque BLOCK (aller et retour)", "Présentation + mise en place buffets, selon vos choix", "La fourniture de vaisselle jetable recyclable", "La fourniture de verrerie (en cas d\'ajout de boisson)"]', 'json', 'widget_texts', 'Éléments inclus forfait remorque'),
            
            // Messages de fin
            array('quote_success_message', 'Votre devis est d\'ores et déjà disponible dans votre boîte mail, la suite ? Block va prendre contact avec vous afin d\'affiner celui-ci et de créer avec vous toute l\'expérience dont vous rêvez', 'html', 'widget_texts', 'Message succès devis'),
        );

        // Insérer les nouveaux paramètres
        foreach ($new_settings as $setting) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}restaurant_settings WHERE setting_key = %s",
                $setting[0]
            ));

            if (!$existing) {
                $wpdb->insert(
                    $wpdb->prefix . 'restaurant_settings',
                    array(
                        'setting_key' => $setting[0],
                        'setting_value' => $setting[1],
                        'setting_type' => $setting[2],
                        'setting_group' => $setting[3],
                        'description' => $setting[4],
                        'is_active' => 1
                    ),
                    array('%s', '%s', '%s', '%s', '%s', '%d')
                );
            }
        }

        // Nouvelles catégories v2
        $new_categories = array(
            array('Sauces', 'sauces', 'sauce', 'both', 'Sauces d\'accompagnement', 0, 0, null, 0, 100),
            array('Jeux', 'jeux', 'jeu', 'remorque', 'Jeux et animations', 0, 0, null, 0, 200),
            array('Tireuse', 'tireuse', 'tireuse', 'remorque', 'Mise à disposition tireuse', 0, 0, 1, 0, 300),
        );

        foreach ($new_categories as $index => $category) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}restaurant_categories WHERE slug = %s",
                $category[1]
            ));

            if (!$existing) {
                $wpdb->insert(
                    $wpdb->prefix . 'restaurant_categories',
                    array(
                        'name' => $category[0],
                        'slug' => $category[1],
                        'type' => $category[2],
                        'service_type' => $category[3],
                        'description' => $category[4],
                        'is_required' => $category[5],
                        'min_selection' => $category[6],
                        'max_selection' => $category[7],
                        'min_per_person' => $category[8],
                        'display_order' => $category[9],
                        'is_active' => 1
                    ),
                    array('%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d')
                );
            }
        }

        // Jeux par défaut - SUPPRIMÉ (les jeux sont maintenant dans restaurant_products via la catégorie 'jeu')

        RestaurantBooking_Logger::info('Nouvelles données par défaut v2 insérées');
    }

    /**
     * Migrer les données existantes
     */
    private function migrate_existing_data()
    {
        global $wpdb;

        // Migrer les anciens devis vers le nouveau format si nécessaire
        // Pour l'instant, on garde la compatibilité

        RestaurantBooking_Logger::info('Migration des données existantes terminée');
    }

    /**
     * Nettoyer les données obsolètes
     */
    private function cleanup_obsolete_data()
    {
        global $wpdb;

        // Charger la classe de nettoyage
        if (file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-cleanup-admin.php')) {
            require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-cleanup-admin.php';
            
            // Supprimer les produits d'exemple
            RestaurantBooking_Cleanup_Admin::remove_example_products();
            
            // Créer les catégories manquantes
            RestaurantBooking_Cleanup_Admin::create_missing_categories();
            
            // Nettoyer les données obsolètes
            RestaurantBooking_Cleanup_Admin::cleanup_obsolete_data();
        }

        RestaurantBooking_Logger::info('Nettoyage des données obsolètes terminé');
    }

    /**
     * Vérifier l'état de la migration
     */
    public static function get_migration_status()
    {
        $current_version = get_option('restaurant_booking_db_version', '0.0.0');
        
        return array(
            'current_version' => $current_version,
            'target_version' => self::DB_VERSION_V2,
            'migration_needed' => version_compare($current_version, self::DB_VERSION_V2, '<'),
            'migration_completed' => version_compare($current_version, self::DB_VERSION_V2, '>=')
        );
    }

    /**
     * Forcer la migration (pour les tests)
     */
    public static function force_migration()
    {
        $instance = self::get_instance();
        $instance->run_migration();
    }
}
