<?php
/**
 * Classe de gestion des paramètres
 *
 * @package RestaurantBooking
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Settings
{
    /**
     * Instance unique
     */
    private static $instance = null;

    /**
     * Cache des paramètres
     */
    private static $settings_cache = array();

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
        // Charger les paramètres en cache
        $this->load_settings_cache();
    }

    /**
     * Charger tous les paramètres en cache
     */
    private function load_settings_cache()
    {
        global $wpdb;

        $settings = $wpdb->get_results(
            "SELECT setting_key, setting_value, setting_type 
             FROM {$wpdb->prefix}restaurant_settings 
             WHERE is_active = 1",
            ARRAY_A
        );

        foreach ($settings as $setting) {
            self::$settings_cache[$setting['setting_key']] = $this->parse_setting_value(
                $setting['setting_value'], 
                $setting['setting_type']
            );
        }
    }

    /**
     * Parser la valeur selon son type
     */
    private function parse_setting_value($value, $type)
    {
        switch ($type) {
            case 'number':
                return is_numeric($value) ? (float) $value : 0;
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'json':
                return json_decode($value, true) ?: array();
            case 'html':
            case 'text':
            default:
                return $value;
        }
    }

    /**
     * Obtenir un paramètre
     */
    public static function get($key, $default = null)
    {
        if (isset(self::$settings_cache[$key])) {
            return self::$settings_cache[$key];
        }

        // Si pas en cache, chercher en base
        global $wpdb;
        $setting = $wpdb->get_row($wpdb->prepare(
            "SELECT setting_value, setting_type 
             FROM {$wpdb->prefix}restaurant_settings 
             WHERE setting_key = %s AND is_active = 1",
            $key
        ));

        if ($setting) {
            $value = self::get_instance()->parse_setting_value($setting->setting_value, $setting->setting_type);
            self::$settings_cache[$key] = $value;
            return $value;
        }

        return $default;
    }

    /**
     * Définir un paramètre
     */
    public static function set($key, $value, $type = 'text')
    {
        global $wpdb;

        // Préparer la valeur selon le type
        $setting_value = $value;
        if ($type === 'json' && is_array($value)) {
            $setting_value = json_encode($value, JSON_UNESCAPED_UNICODE);
        } elseif ($type === 'boolean') {
            $setting_value = $value ? '1' : '0';
        }

        // Vérifier si le paramètre existe
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}restaurant_settings WHERE setting_key = %s",
            $key
        ));

        if ($exists) {
            // Mettre à jour
            $result = $wpdb->update(
                $wpdb->prefix . 'restaurant_settings',
                array(
                    'setting_value' => $setting_value,
                    'setting_type' => $type,
                    'updated_at' => current_time('mysql')
                ),
                array('setting_key' => $key),
                array('%s', '%s', '%s'),
                array('%s')
            );
        } else {
            // Insérer
            $result = $wpdb->insert(
                $wpdb->prefix . 'restaurant_settings',
                array(
                    'setting_key' => $key,
                    'setting_value' => $setting_value,
                    'setting_type' => $type,
                    'setting_group' => 'custom',
                    'is_active' => 1
                ),
                array('%s', '%s', '%s', '%s', '%d')
            );
        }

        if ($result !== false) {
            // Mettre à jour le cache
            self::$settings_cache[$key] = self::get_instance()->parse_setting_value($setting_value, $type);
            return true;
        }

        return false;
    }

    /**
     * Supprimer un paramètre
     */
    public static function delete($key)
    {
        global $wpdb;

        $result = $wpdb->delete(
            $wpdb->prefix . 'restaurant_settings',
            array('setting_key' => $key),
            array('%s')
        );

        if ($result !== false) {
            unset(self::$settings_cache[$key]);
            return true;
        }

        return false;
    }

    /**
     * Obtenir tous les paramètres d'un groupe
     */
    public static function get_group($group)
    {
        global $wpdb;

        $settings = $wpdb->get_results($wpdb->prepare(
            "SELECT setting_key, setting_value, setting_type 
             FROM {$wpdb->prefix}restaurant_settings 
             WHERE setting_group = %s AND is_active = 1
             ORDER BY setting_key",
            $group
        ), ARRAY_A);

        $result = array();
        foreach ($settings as $setting) {
            $result[$setting['setting_key']] = self::get_instance()->parse_setting_value(
                $setting['setting_value'], 
                $setting['setting_type']
            );
        }

        return $result;
    }

    /**
     * Mettre à jour plusieurs paramètres d'un coup
     */
    public static function update_group($group, $settings)
    {
        global $wpdb;

        $wpdb->query('START TRANSACTION');

        try {
            foreach ($settings as $key => $data) {
                $value = isset($data['value']) ? $data['value'] : $data;
                $type = isset($data['type']) ? $data['type'] : 'text';

                if (!self::set($key, $value, $type)) {
                    throw new Exception("Erreur lors de la mise à jour du paramètre: $key");
                }
            }

            $wpdb->query('COMMIT');
            
            // Log de la mise à jour
            RestaurantBooking_Logger::log("Paramètres du groupe '$group' mis à jour", 'info', array(
                'group' => $group,
                'count' => count($settings)
            ));

            return true;
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            
            RestaurantBooking_Logger::log("Erreur lors de la mise à jour du groupe '$group': " . $e->getMessage(), 'error');
            
            return false;
        }
    }

    /**
     * Obtenir les paramètres de tarification
     */
    public static function get_pricing_settings()
    {
        return array(
            'restaurant_base_price' => self::get('restaurant_base_price', 300.00),
            'remorque_base_price' => self::get('remorque_base_price', 350.00),
            'restaurant_included_hours' => self::get('restaurant_included_hours', 2),
            'remorque_included_hours' => self::get('remorque_included_hours', 2),
            'hourly_supplement' => self::get('hourly_supplement', 50.00),
            'remorque_50_guests_supplement' => self::get('remorque_50_guests_supplement', 150.00),
        );
    }

    /**
     * Obtenir les contraintes
     */
    public static function get_constraints()
    {
        return array(
            'restaurant' => array(
                'min_guests' => self::get('restaurant_min_guests', 10),
                'max_guests' => self::get('restaurant_max_guests', 30),
                'max_hours' => self::get('restaurant_max_hours', 4),
            ),
            'remorque' => array(
                'min_guests' => self::get('remorque_min_guests', 20),
                'max_guests' => self::get('remorque_max_guests', 100),
                'max_hours' => self::get('remorque_max_hours', 5),
                'max_delivery_distance' => self::get('remorque_max_delivery_distance', 150),
            ),
        );
    }

    /**
     * Obtenir les textes d'interface
     */
    public static function get_interface_texts($section = null)
    {
        $all_texts = array(
            'homepage' => array(
                'restaurant_title' => self::get('homepage_restaurant_title', 'LE RESTAURANT'),
                'restaurant_description' => self::get('homepage_restaurant_description', ''),
                'button_menu' => self::get('homepage_button_menu', 'Voir le menu'),
                'button_booking' => self::get('homepage_button_booking', 'Réserver à table'),
                'traiteur_title' => self::get('homepage_traiteur_title', 'LE TRAITEUR ÉVÉNEMENTIEL'),
                'button_privatiser' => self::get('homepage_button_privatiser', 'Privatiser Block'),
                'button_infos' => self::get('homepage_button_infos', 'Infos'),
            ),
            'traiteur' => array(
                'restaurant_title' => self::get('traiteur_restaurant_title', 'Privatisation du restaurant'),
                'restaurant_subtitle' => self::get('traiteur_restaurant_subtitle', 'De 10 à 30 personnes'),
                'restaurant_description' => self::get('traiteur_restaurant_description', ''),
                'remorque_title' => self::get('traiteur_remorque_title', 'Privatisation de la remorque Block'),
                'remorque_subtitle' => self::get('traiteur_remorque_subtitle', 'À partir de 20 personnes'),
                'remorque_description' => self::get('traiteur_remorque_description', ''),
            ),
            'forms' => array(
                'step1_title' => self::get('form_step1_title', 'Forfait de base'),
                'step2_title' => self::get('form_step2_title', 'Choix des formules repas'),
                'step3_title' => self::get('form_step3_title', 'Choix des boissons'),
                'step4_title' => self::get('form_step4_title', 'Coordonnées / Contact'),
                'date_label' => self::get('form_date_label', 'Date souhaitée événement'),
                'guests_label' => self::get('form_guests_label', 'Nombre de convives'),
                'duration_label' => self::get('form_duration_label', 'Durée souhaitée événement'),
                'postal_label' => self::get('form_postal_label', 'Commune événement'),
            ),
            'messages' => array(
                'date_unavailable' => self::get('error_date_unavailable', 'Cette date n\'est pas disponible'),
                'guests_min' => self::get('error_guests_min', 'Nombre minimum de convives : {min}'),
                'guests_max' => self::get('error_guests_max', 'Nombre maximum de convives : {max}'),
                'duration_max' => self::get('error_duration_max', 'Durée maximum : {max} heures'),
                'selection_required' => self::get('error_selection_required', 'Sélection obligatoire'),
            ),
        );

        return $section ? (isset($all_texts[$section]) ? $all_texts[$section] : array()) : $all_texts;
    }

    /**
     * Remplacer les variables dans un texte
     */
    public static function replace_variables($text, $variables = array())
    {
        foreach ($variables as $key => $value) {
            $text = str_replace('{' . $key . '}', $value, $text);
        }
        return $text;
    }

    /**
     * Vider le cache des paramètres
     */
    public static function clear_cache()
    {
        self::$settings_cache = array();
        self::get_instance()->load_settings_cache();
    }

    /**
     * Exporter la configuration
     */
    public static function export_settings()
    {
        global $wpdb;

        $settings = $wpdb->get_results(
            "SELECT setting_key, setting_value, setting_type, setting_group, description 
             FROM {$wpdb->prefix}restaurant_settings 
             WHERE is_active = 1
             ORDER BY setting_group, setting_key",
            ARRAY_A
        );

        return array(
            'version' => RESTAURANT_BOOKING_VERSION,
            'export_date' => current_time('mysql'),
            'settings' => $settings
        );
    }

    /**
     * Importer la configuration
     */
    public static function import_settings($data)
    {
        if (!isset($data['settings']) || !is_array($data['settings'])) {
            return false;
        }

        global $wpdb;
        $wpdb->query('START TRANSACTION');

        try {
            foreach ($data['settings'] as $setting) {
                if (!isset($setting['setting_key']) || !isset($setting['setting_value'])) {
                    continue;
                }

                $exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}restaurant_settings WHERE setting_key = %s",
                    $setting['setting_key']
                ));

                if ($exists) {
                    $wpdb->update(
                        $wpdb->prefix . 'restaurant_settings',
                        array(
                            'setting_value' => $setting['setting_value'],
                            'setting_type' => $setting['setting_type'],
                            'setting_group' => $setting['setting_group'],
                            'description' => $setting['description'],
                            'updated_at' => current_time('mysql')
                        ),
                        array('setting_key' => $setting['setting_key'])
                    );
                } else {
                    $wpdb->insert(
                        $wpdb->prefix . 'restaurant_settings',
                        array(
                            'setting_key' => $setting['setting_key'],
                            'setting_value' => $setting['setting_value'],
                            'setting_type' => $setting['setting_type'],
                            'setting_group' => $setting['setting_group'],
                            'description' => $setting['description'],
                            'is_active' => 1
                        )
                    );
                }
            }

            $wpdb->query('COMMIT');
            
            // Vider le cache
            self::clear_cache();
            
            RestaurantBooking_Logger::log('Configuration importée avec succès', 'info', array(
                'settings_count' => count($data['settings'])
            ));

            return true;
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            
            RestaurantBooking_Logger::log('Erreur lors de l\'import: ' . $e->getMessage(), 'error');
            
            return false;
        }
    }
}
