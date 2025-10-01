<?php
/**
 * Gestionnaire des contenances disponibles
 * Permet de gérer dynamiquement les contenances disponibles pour les fûts
 *
 * @package RestaurantBooking
 * @since 3.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Container_Manager
{
    /**
     * Contenances par défaut
     */
    private static $default_containers = array(
        array('liters' => 10, 'label' => '10L', 'is_active' => 1, 'display_order' => 1),
        array('liters' => 20, 'label' => '20L', 'is_active' => 1, 'display_order' => 2),
        array('liters' => 30, 'label' => '30L', 'is_active' => 1, 'display_order' => 3),
        array('liters' => 50, 'label' => '50L', 'is_active' => 1, 'display_order' => 4)
    );

    /**
     * Créer une nouvelle contenance disponible
     */
    public static function create_container($data)
    {
        global $wpdb;
        
        $table = $wpdb->prefix . 'restaurant_available_containers';
        
        $defaults = array(
            'liters' => 0,
            'label' => '',
            'is_active' => 1,
            'display_order' => 0
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Validation
        if (empty($data['liters']) || empty($data['label'])) {
            return false;
        }
        
        // Vérifier si cette contenance existe déjà
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE liters = %d",
            $data['liters']
        ));
        
        if ($exists > 0) {
            return false; // Contenance déjà existante
        }
        
        $result = $wpdb->insert($table, array(
            'liters' => (int) $data['liters'],
            'label' => sanitize_text_field($data['label']),
            'is_active' => (int) $data['is_active'],
            'display_order' => (int) $data['display_order']
        ), array(
            '%d', '%s', '%d', '%d'
        ));
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Mettre à jour une contenance
     */
    public static function update_container($container_id, $data)
    {
        global $wpdb;
        
        $table = $wpdb->prefix . 'restaurant_available_containers';
        
        $allowed_fields = array(
            'liters' => '%d',
            'label' => '%s',
            'is_active' => '%d',
            'display_order' => '%d'
        );
        
        $update_data = array();
        $formats = array();
        
        foreach ($data as $key => $value) {
            if (array_key_exists($key, $allowed_fields)) {
                if ($key === 'label') {
                    $update_data[$key] = sanitize_text_field($value);
                } else {
                    $update_data[$key] = (int) $value;
                }
                $formats[] = $allowed_fields[$key];
            }
        }
        
        if (empty($update_data)) {
            return false;
        }
        
        $result = $wpdb->update(
            $table,
            $update_data,
            array('id' => (int) $container_id),
            $formats,
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Obtenir une contenance par ID
     */
    public static function get_container($container_id)
    {
        global $wpdb;
        
        $table = $wpdb->prefix . 'restaurant_available_containers';
        
        $container = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $container_id
        ), ARRAY_A);
        
        return $container;
    }
    
    /**
     * Obtenir toutes les contenances disponibles
     */
    public static function get_all_containers($active_only = true)
    {
        global $wpdb;
        
        $table = $wpdb->prefix . 'restaurant_available_containers';
        
        $where_clause = $active_only ? 'WHERE is_active = 1' : '';
        
        $containers = $wpdb->get_results("
            SELECT * FROM {$table} 
            {$where_clause}
            ORDER BY display_order ASC, liters ASC
        ", ARRAY_A);
        
        // Si aucune contenance en base, retourner les contenances par défaut
        if (empty($containers) && $active_only) {
            return self::$default_containers;
        }
        
        return $containers ?: array();
    }
    
    /**
     * Supprimer une contenance
     */
    public static function delete_container($container_id)
    {
        global $wpdb;
        
        $table = $wpdb->prefix . 'restaurant_available_containers';
        
        // Vérifier que la contenance existe
        $container = self::get_container($container_id);
        if (!$container) {
            return false;
        }
        
        // Vérifier qu'aucun fût n'utilise cette contenance
        $keg_sizes_table = $wpdb->prefix . 'restaurant_keg_sizes';
        $usage_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$keg_sizes_table} WHERE liters = %d",
            $container['liters']
        ));
        
        if ($usage_count > 0) {
            return new WP_Error('container_in_use', __('Cette contenance est utilisée par des fûts existants', 'restaurant-booking'));
        }
        
        $result = $wpdb->delete(
            $table,
            array('id' => (int) $container_id),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Initialiser les contenances par défaut
     */
    public static function init_default_containers()
    {
        global $wpdb;
        
        $table = $wpdb->prefix . 'restaurant_available_containers';
        
        // Vérifier si des contenances existent déjà
        $existing_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        
        if ($existing_count > 0) {
            return; // Des contenances existent déjà
        }
        
        // Créer les contenances par défaut
        foreach (self::$default_containers as $container) {
            self::create_container($container);
        }
        
        if (class_exists('RestaurantBooking_Logger')) {
            RestaurantBooking_Logger::info('Contenances par défaut initialisées');
        }
    }
    
    /**
     * Obtenir les contenances sous forme de options HTML
     */
    public static function get_containers_as_options($selected_value = '')
    {
        $containers = self::get_all_containers(true);
        $options = '<option value="">' . __('Choisir la contenance', 'restaurant-booking') . '</option>';
        
        foreach ($containers as $container) {
            $selected = selected($selected_value, $container['liters'], false);
            $options .= '<option value="' . esc_attr($container['liters']) . '" ' . $selected . '>';
            $options .= esc_html($container['label']);
            $options .= '</option>';
        }
        
        return $options;
    }
    
    /**
     * Vérifier si une contenance est disponible
     */
    public static function is_container_available($liters)
    {
        $containers = self::get_all_containers(true);
        
        foreach ($containers as $container) {
            if ($container['liters'] == $liters) {
                return true;
            }
        }
        
        return false;
    }
}
