<?php
/**
 * Classe de gestion de la base de données
 *
 * @package RestaurantBooking
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Database
{
    /**
     * Instance unique
     */
    private static $instance = null;

    /**
     * Version de la base de données
     */
    const DB_VERSION = '1.0.0';

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
        add_action('init', array($this, 'check_database_version'));
    }

    /**
     * Vérifier la version de la base de données
     */
    public function check_database_version()
    {
        $current_version = get_option('restaurant_booking_db_version', '0.0.0');
        
        if (version_compare($current_version, self::DB_VERSION, '<')) {
            self::create_tables();
            update_option('restaurant_booking_db_version', self::DB_VERSION);
        }
    }

    /**
     * Créer toutes les tables
     */
    public static function create_tables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Table des catégories
        $table_categories = $wpdb->prefix . 'restaurant_categories';
        $sql_categories = "CREATE TABLE $table_categories (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(100) NOT NULL DEFAULT '',
            type enum('plat_signature_dog', 'plat_signature_croq', 'mini_boss', 'accompagnement', 'buffet_sale', 'buffet_sucre', 'soft', 'vin_blanc', 'vin_rouge', 'vin_rose', 'cremant', 'biere_bouteille', 'fut', 'jeu', 'option_restaurant', 'option_remorque') NOT NULL,
            service_type enum('restaurant', 'remorque', 'both') NOT NULL DEFAULT 'both',
            description text,
            image_id bigint(20) DEFAULT NULL,
            is_required tinyint(1) NOT NULL DEFAULT 0,
            min_selection int(11) DEFAULT 0,
            max_selection int(11) DEFAULT NULL,
            min_per_person tinyint(1) NOT NULL DEFAULT 0,
            display_order int(11) NOT NULL DEFAULT 0,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY slug (slug),
            KEY type (type),
            KEY service_type (service_type),
            KEY is_active (is_active),
            KEY display_order (display_order)
        ) $charset_collate;";

        // Table des produits
        $table_products = $wpdb->prefix . 'restaurant_products';
        $sql_products = "CREATE TABLE $table_products (
            id int(11) NOT NULL AUTO_INCREMENT,
            category_id int(11) NOT NULL,
            name varchar(255) NOT NULL,
            description text,
            short_description varchar(500),
            price decimal(10,2) NOT NULL DEFAULT 0.00,
            unit_type enum('piece', 'gramme', 'portion_6p', 'litre', 'centilitre', 'bouteille') NOT NULL DEFAULT 'piece',
            unit_label varchar(50) DEFAULT '/pièce',
            min_quantity int(11) NOT NULL DEFAULT 1,
            max_quantity int(11) DEFAULT NULL,
            has_supplement tinyint(1) NOT NULL DEFAULT 0,
            supplement_name varchar(255) DEFAULT NULL,
            supplement_price decimal(10,2) DEFAULT 0.00,
            image_id bigint(20) DEFAULT NULL,
            alcohol_degree decimal(3,1) DEFAULT NULL,
            volume_cl int(11) DEFAULT NULL,
            suggested_beverage tinyint(1) DEFAULT 0,
            sauce_options json DEFAULT NULL,
            accompaniment_type varchar(50) DEFAULT NULL,
            has_chimichurri tinyint(1) DEFAULT 0,
            unit_per_person varchar(50) DEFAULT NULL,
            beer_category varchar(50) DEFAULT NULL,
            keg_sizes json DEFAULT NULL,
            wine_category varchar(100) DEFAULT NULL,
            display_order int(11) NOT NULL DEFAULT 0,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY category_id (category_id),
            KEY is_active (is_active),
            KEY display_order (display_order)
        ) $charset_collate;";

        // Table des paramètres
        $table_settings = $wpdb->prefix . 'restaurant_settings';
        $sql_settings = "CREATE TABLE $table_settings (
            id int(11) NOT NULL AUTO_INCREMENT,
            setting_key varchar(100) NOT NULL,
            setting_value longtext,
            setting_type enum('text', 'number', 'boolean', 'json', 'html') NOT NULL DEFAULT 'text',
            setting_group varchar(100) DEFAULT 'general',
            description text,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY setting_key (setting_key),
            KEY setting_group (setting_group),
            KEY is_active (is_active)
        ) $charset_collate;";

        // Table des devis
        $table_quotes = $wpdb->prefix . 'restaurant_quotes';
        $sql_quotes = "CREATE TABLE $table_quotes (
            id int(11) NOT NULL AUTO_INCREMENT,
            quote_number varchar(50) NOT NULL,
            service_type enum('restaurant', 'remorque') NOT NULL,
            event_date date NOT NULL,
            event_duration int(11) NOT NULL DEFAULT 2,
            guest_count int(11) NOT NULL,
            postal_code varchar(10) DEFAULT NULL,
            distance_km int(11) DEFAULT NULL,
            customer_data json,
            selected_products json,
            price_breakdown json,
            base_price decimal(10,2) NOT NULL DEFAULT 0.00,
            supplements_total decimal(10,2) NOT NULL DEFAULT 0.00,
            products_total decimal(10,2) NOT NULL DEFAULT 0.00,
            total_price decimal(10,2) NOT NULL DEFAULT 0.00,
            status enum('draft', 'sent', 'confirmed', 'cancelled') NOT NULL DEFAULT 'draft',
            admin_notes text,
            sent_at datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY quote_number (quote_number),
            KEY service_type (service_type),
            KEY event_date (event_date),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Table des disponibilités
        $table_availability = $wpdb->prefix . 'restaurant_availability';
        $sql_availability = "CREATE TABLE $table_availability (
            id int(11) NOT NULL AUTO_INCREMENT,
            date date NOT NULL,
            service_type enum('restaurant', 'remorque', 'both') NOT NULL DEFAULT 'both',
            is_available tinyint(1) NOT NULL DEFAULT 1,
            blocked_reason varchar(255) DEFAULT NULL,
            notes text,
            created_by int(11) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY date_service (date, service_type),
            KEY date (date),
            KEY service_type (service_type),
            KEY is_available (is_available)
        ) $charset_collate;";

        // Table des zones de livraison
        $table_delivery_zones = $wpdb->prefix . 'restaurant_delivery_zones';
        $sql_delivery_zones = "CREATE TABLE $table_delivery_zones (
            id int(11) NOT NULL AUTO_INCREMENT,
            zone_name varchar(100) NOT NULL,
            distance_min int(11) NOT NULL DEFAULT 0,
            distance_max int(11) NOT NULL,
            delivery_price decimal(10,2) NOT NULL DEFAULT 0.00,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            display_order int(11) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY distance_range (distance_min, distance_max),
            KEY is_active (is_active),
            KEY display_order (display_order)
        ) $charset_collate;";

        // Table des logs
        $table_logs = $wpdb->prefix . 'restaurant_logs';
        $sql_logs = "CREATE TABLE $table_logs (
            id int(11) NOT NULL AUTO_INCREMENT,
            level enum('error', 'warning', 'info', 'debug') NOT NULL DEFAULT 'info',
            message text NOT NULL,
            context json DEFAULT NULL,
            user_id int(11) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY level (level),
            KEY created_at (created_at),
            KEY user_id (user_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Exécuter les requêtes de création
        dbDelta($sql_categories);
        dbDelta($sql_products);
        dbDelta($sql_settings);
        dbDelta($sql_quotes);
        dbDelta($sql_availability);
        dbDelta($sql_delivery_zones);
        dbDelta($sql_logs);

        // Créer des données de test si c'est une nouvelle installation
        if (get_option('restaurant_booking_sample_data_created') !== 'yes') {
            $this->create_sample_data();
            update_option('restaurant_booking_sample_data_created', 'yes');
        }
        
        // Mettre à jour le schéma de la base de données si nécessaire
        $this->update_database_schema();

        // Forcer la création des données d'exemple si elles n'existent pas
        $this->ensure_sample_data_exists();

        // Log de la création des tables
        if (class_exists('RestaurantBooking_Logger')) {
            RestaurantBooking_Logger::log('Tables de base de données créées', 'info');
        }
    }

    /**
     * Nettoyer les données corrompues avant insertion
     */
    public static function cleanup_corrupted_data()
    {
        global $wpdb;
        
        // Supprimer les catégories avec des slugs vides ou dupliqués
        $wpdb->query("DELETE FROM {$wpdb->prefix}restaurant_categories WHERE slug = '' OR slug IS NULL");
        
        // Supprimer les doublons de slugs
        $wpdb->query("
            DELETE c1 FROM {$wpdb->prefix}restaurant_categories c1
            INNER JOIN {$wpdb->prefix}restaurant_categories c2 
            WHERE c1.id > c2.id AND c1.slug = c2.slug
        ");
        
        if (class_exists('RestaurantBooking_Logger')) {
            RestaurantBooking_Logger::info('Données corrompues nettoyées');
        }
    }

    /**
     * Mettre à jour le schéma de la base de données
     */
    private function update_database_schema()
    {
        global $wpdb;
        
        $table_products = $wpdb->prefix . 'restaurant_products';
        $table_categories = $wpdb->prefix . 'restaurant_categories';
        
        // Vérifier et ajouter les nouvelles colonnes dans restaurant_products
        $columns_to_add = array(
            'suggested_beverage' => 'tinyint(1) DEFAULT 0',
            'sauce_options' => 'json DEFAULT NULL',
            'accompaniment_type' => 'varchar(50) DEFAULT NULL',
            'has_chimichurri' => 'tinyint(1) DEFAULT 0',
            'unit_per_person' => 'varchar(50) DEFAULT NULL',
            'beer_category' => 'varchar(50) DEFAULT NULL',
            'keg_sizes' => 'json DEFAULT NULL',
            'wine_category' => 'varchar(100) DEFAULT NULL',
            'volume_cl' => 'int(11) DEFAULT NULL',
            'alcohol_degree' => 'decimal(3,1) DEFAULT NULL'
        );
        
        foreach ($columns_to_add as $column => $definition) {
            // Vérifier si la colonne existe
            $column_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                DB_NAME, $table_products, $column
            ));
            
            if (!$column_exists) {
                $sql = "ALTER TABLE `$table_products` ADD COLUMN `$column` $definition";
                $result = $wpdb->query($sql);
                
                if ($result === false) {
                    // En cas d'erreur, essayer une approche alternative
                    $sql_alt = "ALTER TABLE `$table_products` ADD `$column` $definition";
                    $result = $wpdb->query($sql_alt);
                }
                
                if (class_exists('RestaurantBooking_Logger')) {
                    if ($result !== false) {
                        RestaurantBooking_Logger::log("Colonne ajoutée avec succès: $column dans $table_products", 'info');
                    } else {
                        RestaurantBooking_Logger::log("Erreur lors de l'ajout de la colonne: $column - " . $wpdb->last_error, 'error');
                    }
                }
            }
        }
        
        // Mettre à jour l'enum des types de catégories
        $current_enum = $wpdb->get_row($wpdb->prepare(
            "SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'type'",
            DB_NAME, $table_categories
        ));
        
        if ($current_enum && !strpos($current_enum->COLUMN_TYPE, 'plat_signature_dog')) {
            $new_enum = "enum('plat_signature_dog', 'plat_signature_croq', 'mini_boss', 'accompagnement', 'buffet_sale', 'buffet_sucre', 'soft', 'vin_blanc', 'vin_rouge', 'vin_rose', 'cremant', 'biere_bouteille', 'fut', 'jeu', 'option_restaurant', 'option_remorque') NOT NULL";
            $sql = "ALTER TABLE `$table_categories` MODIFY COLUMN `type` $new_enum";
            $wpdb->query($sql);
            
            // Insérer les nouvelles catégories si elles n'existent pas
            self::insert_default_data();
            
            if (class_exists('RestaurantBooking_Logger')) {
                RestaurantBooking_Logger::log("Types de catégories mis à jour dans $table_categories", 'info');
            }
        }
    }

    /**
     * S'assurer que les données d'exemple existent
     */
    private function ensure_sample_data_exists()
    {
        global $wpdb;
        
        // Vérifier si des produits existent
        $products_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}restaurant_products");
        
        if ($products_count == 0) {
            // Aucun produit n'existe, créer les données d'exemple
            $this->create_sample_data();
            
            if (class_exists('RestaurantBooking_Logger')) {
                RestaurantBooking_Logger::log('Données d\'exemple créées car aucun produit n\'existait', 'info');
            }
        }
    }

    /**
     * Insérer les données par défaut
     */
    public static function insert_default_data()
    {
        global $wpdb;

        // Nettoyer les données corrompues d'abord
        self::cleanup_corrupted_data();

        // Vérifier si les données par défaut existent déjà
        $settings_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}restaurant_settings");
        if ($settings_count > 0) {
            return; // Les données existent déjà
        }

        // Paramètres par défaut
        $default_settings = array(
            // Forfaits de base
            array('restaurant_base_price', '300.00', 'number', 'pricing', 'Prix forfait restaurant'),
            array('remorque_base_price', '350.00', 'number', 'pricing', 'Prix forfait remorque'),
            array('restaurant_included_hours', '2', 'number', 'pricing', 'Heures incluses restaurant'),
            array('remorque_included_hours', '2', 'number', 'pricing', 'Heures incluses remorque'),
            array('hourly_supplement', '50.00', 'number', 'pricing', 'Supplément horaire'),

            // Contraintes participants
            array('restaurant_min_guests', '10', 'number', 'constraints', 'Minimum convives restaurant'),
            array('restaurant_max_guests', '30', 'number', 'constraints', 'Maximum convives restaurant'),
            array('remorque_min_guests', '20', 'number', 'constraints', 'Minimum convives remorque'),
            array('remorque_max_guests', '100', 'number', 'constraints', 'Maximum convives remorque'),

            // Contraintes durée
            array('restaurant_max_hours', '4', 'number', 'constraints', 'Durée maximum restaurant'),
            array('remorque_max_hours', '5', 'number', 'constraints', 'Durée maximum remorque'),

            // Suppléments remorque
            array('remorque_50_guests_supplement', '150.00', 'number', 'pricing', 'Supplément +50 convives'),
            array('remorque_max_delivery_distance', '150', 'number', 'constraints', 'Distance maximum livraison'),
            array('restaurant_postal_code', '67000', 'text', 'general', 'Code postal restaurant'),

            // Textes interface - Page d'accueil
            array('homepage_restaurant_title', 'LE RESTAURANT', 'text', 'interface', 'Titre restaurant page d\'accueil'),
            array('homepage_restaurant_description', 'Découvrez notre cuisine authentique dans un cadre chaleureux et convivial.', 'html', 'interface', 'Description restaurant'),
            array('homepage_button_menu', 'Voir le menu', 'text', 'interface', 'Texte bouton menu'),
            array('homepage_button_booking', 'Réserver à table', 'text', 'interface', 'Texte bouton réservation'),
            array('homepage_traiteur_title', 'LE TRAITEUR ÉVÉNEMENTIEL', 'text', 'interface', 'Titre traiteur'),
            array('homepage_button_privatiser', 'Privatiser Block', 'text', 'interface', 'Texte bouton privatisation'),
            array('homepage_button_infos', 'Infos', 'text', 'interface', 'Texte bouton infos'),

            // Textes interface - Page traiteur
            array('traiteur_restaurant_title', 'Privatisation du restaurant', 'text', 'interface', 'Titre privatisation restaurant'),
            array('traiteur_restaurant_subtitle', 'De 10 à 30 personnes', 'text', 'interface', 'Sous-titre restaurant'),
            array('traiteur_restaurant_description', 'Privatisez notre restaurant pour vos événements intimes et profitez d\'un service personnalisé.', 'html', 'interface', 'Description privatisation restaurant'),
            array('traiteur_remorque_title', 'Privatisation de la remorque Block', 'text', 'interface', 'Titre remorque'),
            array('traiteur_remorque_subtitle', 'À partir de 20 personnes', 'text', 'interface', 'Sous-titre remorque'),
            array('traiteur_remorque_description', 'Notre remorque mobile se déplace pour vos événements extérieurs et grandes réceptions.', 'html', 'interface', 'Description remorque'),

            // Textes formulaires
            array('form_step1_title', 'Forfait de base', 'text', 'forms', 'Titre étape 1'),
            array('form_step2_title', 'Choix des formules repas', 'text', 'forms', 'Titre étape 2'),
            array('form_step3_title', 'Choix des boissons', 'text', 'forms', 'Titre étape 3'),
            array('form_step4_title', 'Coordonnées / Contact', 'text', 'forms', 'Titre étape 4'),
            array('form_date_label', 'Date souhaitée événement', 'text', 'forms', 'Label date'),
            array('form_guests_label', 'Nombre de convives', 'text', 'forms', 'Label convives'),
            array('form_duration_label', 'Durée souhaitée événement', 'text', 'forms', 'Label durée'),
            array('form_postal_label', 'Commune événement', 'text', 'forms', 'Label code postal'),

            // Messages de validation
            array('error_date_unavailable', 'Cette date n\'est pas disponible', 'text', 'messages', 'Erreur date indisponible'),
            array('error_guests_min', 'Nombre minimum de convives : {min}', 'text', 'messages', 'Erreur minimum convives'),
            array('error_guests_max', 'Nombre maximum de convives : {max}', 'text', 'messages', 'Erreur maximum convives'),
            array('error_duration_max', 'Durée maximum : {max} heures', 'text', 'messages', 'Erreur durée maximum'),
            array('error_selection_required', 'Sélection obligatoire', 'text', 'messages', 'Erreur sélection obligatoire'),

            // Templates d'emails
            array('email_quote_subject', 'Votre devis privatisation Block', 'text', 'emails', 'Sujet email devis'),
            array('email_quote_header_html', '<div style="text-align: center; padding: 20px;"><h1>Restaurant Block</h1></div>', 'html', 'emails', 'Header email devis'),
            array('email_quote_body_html', '<p>Madame, Monsieur,</p><p>Nous vous remercions pour votre demande de devis.</p><p>Vous trouverez en pièce jointe votre devis personnalisé.</p><p>Cordialement,<br>L\'équipe Block</p>', 'html', 'emails', 'Corps email devis'),
            array('email_quote_footer_html', '<div style="font-size: 12px; color: #666; margin-top: 30px;"><p>Restaurant Block - SIRET: 12345678901234</p></div>', 'html', 'emails', 'Footer email devis'),

            // Configuration email
            array('admin_notification_emails', '["admin@restaurant-block.fr"]', 'json', 'emails', 'Emails de notification admin'),
        );

        // Insérer les paramètres
        foreach ($default_settings as $setting) {
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

        // Catégories par défaut
        $default_categories = array(
            // Plats Signature
            array('Plats Signature DOG', 'plats-signature-dog', 'plat_signature_dog', 'both', 'Nos hot-dogs emblématiques', 1, 1, null, 1),
            array('Plats Signature CROQ', 'plats-signature-croq', 'plat_signature_croq', 'both', 'Nos croque-monsieur gourmands', 1, 1, null, 1),
            
            // Menu Enfant et Accompagnements
            array('Mini Boss', 'mini-boss', 'mini_boss', 'both', 'Menus enfants spécialement conçus', 0, 0, null, 0),
            array('Accompagnements', 'accompagnements', 'accompagnement', 'both', 'Accompagnements 4€ - min 1/personne', 1, 1, null, 1),
            
            // Buffets
            array('Buffet Salé', 'buffet-sale', 'buffet_sale', 'both', 'Min 2 recettes + min 1/personne', 1, 2, null, 1),
            array('Buffet Sucré', 'buffet-sucre', 'buffet_sucre', 'both', 'Min 1 recette + min 1/personne', 1, 1, null, 1),
            
            // Boissons
            array('Boissons Soft', 'boissons-soft', 'soft', 'both', 'Boissons sans alcool', 0, 0, null, 0),
            array('Vins Blancs', 'vins-blancs', 'vin_blanc', 'both', 'Sélection de vins blancs', 0, 0, null, 0),
            array('Vins Rouges', 'vins-rouges', 'vin_rouge', 'both', 'Sélection de vins rouges', 0, 0, null, 0),
            array('Vins Rosés', 'vins-roses', 'vin_rose', 'both', 'Sélection de vins rosés', 0, 0, null, 0),
            array('Crémants', 'cremants', 'cremant', 'both', 'Bulles et crémants', 0, 0, null, 0),
            array('Bières Bouteilles', 'bieres-bouteilles', 'biere_bouteille', 'both', 'Bières en bouteilles', 0, 0, null, 0),
            array('Fûts de Bière', 'futs-biere', 'fut', 'remorque', 'Fûts 10L et 20L pour remorque', 0, 0, null, 0),
            
            // Options
            array('Jeux', 'jeux', 'jeu', 'remorque', 'Jeux gonflables et animations', 0, 0, null, 0),
            array('Options Restaurant', 'options-restaurant', 'option_restaurant', 'restaurant', 'Options spécifiques restaurant', 0, 0, null, 0),
            array('Options Remorque', 'options-remorque', 'option_remorque', 'remorque', 'Options spécifiques remorque', 0, 0, null, 0),
        );

        foreach ($default_categories as $index => $category) {
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
                    'display_order' => $index + 1,
                    'is_active' => 1
                ),
                array('%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d')
            );
        }

        // Zones de livraison par défaut
        $default_zones = array(
            array('Zone 0-30km', 0, 30, 0.00, 1, 1),
            array('Zone 31-60km', 31, 60, 50.00, 1, 2),
            array('Zone 61-100km', 61, 100, 100.00, 1, 3),
            array('Zone 101-150km', 101, 150, 150.00, 1, 4),
        );

        foreach ($default_zones as $zone) {
            $wpdb->insert(
                $wpdb->prefix . 'restaurant_delivery_zones',
                array(
                    'zone_name' => $zone[0],
                    'distance_min' => $zone[1],
                    'distance_max' => $zone[2],
                    'delivery_price' => $zone[3],
                    'is_active' => $zone[4],
                    'display_order' => $zone[5]
                ),
                array('%s', '%d', '%d', '%f', '%d', '%d')
            );
        }

        // Log de l'insertion des données par défaut
        if (class_exists('RestaurantBooking_Logger')) {
            RestaurantBooking_Logger::log('Données par défaut insérées', 'info');
        }
    }

    /**
     * Forcer la mise à jour du schéma de base de données (méthode publique)
     */
    public static function force_update_schema()
    {
        $instance = new self();
        $instance->update_database_schema();
        
        if (class_exists('RestaurantBooking_Logger')) {
            RestaurantBooking_Logger::log('Schéma de base de données mis à jour manuellement', 'info');
        }
    }

    /**
     * Forcer la recréation des données d'exemple (méthode publique)
     */
    public static function force_recreate_sample_data()
    {
        $instance = new self();
        $instance->update_database_schema(); // Mettre à jour le schéma d'abord
        $instance->create_sample_data();
        
        if (class_exists('RestaurantBooking_Logger')) {
            RestaurantBooking_Logger::log('Données d\'exemple forcées à être recréées', 'info');
        }
    }

    /**
     * Créer des données de test
     */
    private function create_sample_data()
    {
        global $wpdb;

        // Obtenir les IDs des catégories créées
        $categories = $wpdb->get_results("SELECT id, type FROM {$wpdb->prefix}restaurant_categories", ARRAY_A);
        $category_ids = array();
        foreach ($categories as $category) {
            $category_ids[$category['type']] = $category['id'];
        }

        // Supprimer les produits d'exemple existants pour éviter les doublons
        $wpdb->query("DELETE FROM {$wpdb->prefix}restaurant_products WHERE name LIKE 'Hot-Dog%' OR name LIKE 'Croque-%' OR name LIKE '%Frites%' OR name LIKE '%Bordeaux%' OR name LIKE '%Plateau%'");

        // Données de test pour les plats signature DOG
        if (isset($category_ids['plat_signature_dog'])) {
            $dog_products = array(
                array('Hot-Dog Classique', 'Saucisse de porc, pain brioché, oignons confits, moutarde à l\'ancienne', 8.50, 1, 'Fromage supplémentaire', 1.50),
                array('Hot-Dog Végétarien', 'Saucisse végétale, pain complet, légumes grillés, sauce barbecue', 9.00, 0, null, 0),
                array('Hot-Dog Gourmet', 'Saucisse artisanale, pain aux graines, confit de tomates, roquette', 11.50, 1, 'Avocat', 2.00),
            );

            foreach ($dog_products as $product) {
                $wpdb->insert(
                    $wpdb->prefix . 'restaurant_products',
                    array(
                        'category_id' => $category_ids['plat_signature_dog'],
                        'name' => $product[0],
                        'description' => $product[1],
                        'price' => $product[2],
                        'unit_type' => 'piece',
                        'unit_label' => '/pièce',
                        'has_supplement' => $product[3],
                        'supplement_name' => $product[4],
                        'supplement_price' => $product[5],
                        'is_active' => 1
                    ),
                    array('%d', '%s', '%s', '%f', '%s', '%s', '%d', '%s', '%f', '%d')
                );
            }
        }

        // Données de test pour les plats signature CROQ
        if (isset($category_ids['plat_signature_croq'])) {
            $croq_products = array(
                array('Croque-Monsieur Traditionnel', 'Jambon blanc, fromage Gruyère, pain de mie, béchamel', 9.50, 0, null, 0),
                array('Croque-Madame', 'Jambon blanc, fromage Gruyère, œuf au plat, pain de mie', 11.00, 1, 'Œuf au plat supplémentaire', 2.00),
                array('Croque-Végétarien', 'Légumes grillés, fromage de chèvre, pain complet, pesto', 10.50, 1, 'Avocat', 2.00),
            );

            foreach ($croq_products as $product) {
                $wpdb->insert(
                    $wpdb->prefix . 'restaurant_products',
                    array(
                        'category_id' => $category_ids['plat_signature_croq'],
                        'name' => $product[0],
                        'description' => $product[1],
                        'price' => $product[2],
                        'unit_type' => 'piece',
                        'unit_label' => '/pièce',
                        'has_supplement' => $product[3],
                        'supplement_name' => $product[4],
                        'supplement_price' => $product[5],
                        'is_active' => 1
                    ),
                    array('%d', '%s', '%s', '%f', '%s', '%s', '%d', '%s', '%f', '%d')
                );
            }
        }

        // Données de test pour les accompagnements
        if (isset($category_ids['accompagnement'])) {
            $accompaniment_products = array(
                array(
                    'name' => 'Salade Verte',
                    'description' => 'Salade verte fraîche, vinaigrette maison',
                    'accompaniment_type' => 'salade',
                    'sauce_options' => null,
                    'has_chimichurri' => 0
                ),
                array(
                    'name' => 'Frites Maison',
                    'description' => 'Frites fraîches coupées maison',
                    'accompaniment_type' => 'frites',
                    'sauce_options' => json_encode(array(
                        array('name' => 'Ketchup'),
                        array('name' => 'Mayonnaise'),
                        array('name' => 'Moutarde'),
                        array('name' => 'Sauce barbecue')
                    )),
                    'has_chimichurri' => 1
                ),
            );

            foreach ($accompaniment_products as $product) {
                $wpdb->insert(
                    $wpdb->prefix . 'restaurant_products',
                    array(
                        'category_id' => $category_ids['accompagnement'],
                        'name' => $product['name'],
                        'description' => $product['description'],
                        'price' => 4.00,
                        'unit_type' => 'piece',
                        'unit_label' => '/portion',
                        'accompaniment_type' => $product['accompaniment_type'],
                        'sauce_options' => $product['sauce_options'],
                        'has_chimichurri' => $product['has_chimichurri'],
                        'is_active' => 1
                    ),
                    array('%d', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%d', '%d')
                );
            }
        }

        // Données de test pour les vins avec catégories
        if (isset($category_ids['vin_blanc'])) {
            $wine_products = array(
                array('Sancerre AOC', 'Vin blanc sec de Loire, notes minérales', 'Loire', 28.00, 1),
                array('Chablis Premier Cru', 'Vin blanc de Bourgogne, élégant et frais', 'Bourgogne', 32.00, 0),
                array('Muscadet Sèvre-et-Maine', 'Vin blanc sec parfait avec les fruits de mer', 'Loire', 18.00, 1),
            );

            foreach ($wine_products as $product) {
                $wpdb->insert(
                    $wpdb->prefix . 'restaurant_products',
                    array(
                        'category_id' => $category_ids['vin_blanc'],
                        'name' => $product[0],
                        'description' => $product[1],
                        'wine_category' => $product[2],
                        'price' => $product[3],
                        'unit_type' => 'bouteille',
                        'unit_label' => '/bouteille 75cl',
                        'volume_cl' => 75,
                        'alcohol_degree' => 12.5,
                        'suggested_beverage' => $product[4],
                        'is_active' => 1
                    ),
                    array('%d', '%s', '%s', '%s', '%f', '%s', '%s', '%d', '%f', '%d', '%d')
                );
            }
        }

        // Données de test pour les buffets salés
        if (isset($category_ids['buffet_sale'])) {
            $buffet_sale_products = array(
                array('Plateau de Charcuterie', 'Sélection de charcuteries artisanales', '150g/pers', 12.00, 1, 'Cornichons supplémentaires', 2.00),
                array('Assortiment de Fromages', 'Plateau de fromages français affinés', '120g/pers', 15.00, 0, null, 0),
                array('Verrines Salées', 'Verrines variées aux légumes et terrines', '3 pièces/pers', 8.50, 1, 'Verrine végétarienne', 1.50),
            );

            foreach ($buffet_sale_products as $product) {
                $wpdb->insert(
                    $wpdb->prefix . 'restaurant_products',
                    array(
                        'category_id' => $category_ids['buffet_sale'],
                        'name' => $product[0],
                        'description' => $product[1],
                        'unit_per_person' => $product[2],
                        'price' => $product[3],
                        'unit_type' => 'portion_6p',
                        'unit_label' => '/6 personnes',
                        'has_supplement' => $product[4],
                        'supplement_name' => $product[5],
                        'supplement_price' => $product[6],
                        'is_active' => 1
                    ),
                    array('%d', '%s', '%s', '%s', '%f', '%s', '%s', '%d', '%s', '%f', '%d')
                );
            }
        }

        // Log de la création des données de test
        if (class_exists('RestaurantBooking_Logger')) {
            RestaurantBooking_Logger::log('Données de test créées', 'info');
        }
    }

    /**
     * Supprimer toutes les tables
     */
    public static function drop_tables()
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
    }

    /**
     * Obtenir la version de la base de données
     */
    public static function get_db_version()
    {
        return get_option('restaurant_booking_db_version', '0.0.0');
    }

    /**
     * Vérifier l'état de la base de données
     */
    public static function check_database_health()
    {
        global $wpdb;

        $health_status = array(
            'status' => 'ok',
            'tables' => array(),
            'errors' => array()
        );

        $expected_tables = array(
            'restaurant_categories',
            'restaurant_products', 
            'restaurant_settings',
            'restaurant_quotes',
            'restaurant_availability',
            'restaurant_delivery_zones',
            'restaurant_logs'
        );

        foreach ($expected_tables as $table) {
            $full_table_name = $wpdb->prefix . $table;
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'") == $full_table_name;
            
            $health_status['tables'][$table] = array(
                'exists' => $table_exists,
                'status' => $table_exists ? 'ok' : 'missing'
            );

            if (!$table_exists) {
                $health_status['status'] = 'error';
                $health_status['errors'][] = "Table manquante: $table";
            }
        }

        return $health_status;
    }
}
