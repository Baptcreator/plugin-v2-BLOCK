<?php
/**
 * Gestionnaire des tailles de fûts
 *
 * @package RestaurantBooking
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Keg_Size_Manager
{
    /**
     * Créer une nouvelle taille de fût
     */
    public static function create_size($data)
    {
        global $wpdb;
        
        $table = $wpdb->prefix . 'restaurant_keg_sizes';
        
        $defaults = array(
            'product_id' => 0,
            'liters' => 0,
            'price' => 0.00,
            'image_id' => null,
            'is_featured' => 0,
            'display_order' => 0,
            'is_active' => 1
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Validation
        if (empty($data['product_id']) || empty($data['liters']) || $data['price'] <= 0) {
            return false;
        }
        
        // Vérifier si cette taille existe déjà pour ce produit
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} 
             WHERE product_id = %d AND liters = %d",
            $data['product_id'],
            $data['liters']
        ));
        
        if ($exists > 0) {
            return false; // Taille déjà existante
        }
        
        $result = $wpdb->insert($table, array(
            'product_id' => (int) $data['product_id'],
            'liters' => (int) $data['liters'],
            'price' => (float) $data['price'],
            'image_id' => $data['image_id'] ? (int) $data['image_id'] : null,
            'is_featured' => (int) $data['is_featured'],
            'display_order' => (int) $data['display_order'],
            'is_active' => (int) $data['is_active']
        ), array(
            '%d', '%d', '%f', '%d', '%d', '%d', '%d'
        ));
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Mettre à jour une taille de fût
     */
    public static function update_size($size_id, $data)
    {
        global $wpdb;
        
        $table = $wpdb->prefix . 'restaurant_keg_sizes';
        
        $allowed_fields = array(
            'liters' => '%d',
            'price' => '%f',
            'image_id' => '%d',
            'is_featured' => '%d',
            'display_order' => '%d',
            'is_active' => '%d'
        );
        
        $update_data = array();
        $formats = array();
        
        foreach ($data as $key => $value) {
            if (array_key_exists($key, $allowed_fields)) {
                $update_data[$key] = $value;
                $formats[] = $allowed_fields[$key];
            }
        }
        
        if (empty($update_data)) {
            return false;
        }
        
        $result = $wpdb->update(
            $table,
            $update_data,
            array('id' => (int) $size_id),
            $formats,
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Obtenir une taille de fût par ID
     */
    public static function get_size($size_id)
    {
        global $wpdb;
        
        $table = $wpdb->prefix . 'restaurant_keg_sizes';
        
        $size = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $size_id
        ), ARRAY_A);
        
        return $size;
    }
    
    /**
     * Obtenir toutes les tailles d'un fût
     */
    public static function get_sizes_by_product($product_id)
    {
        global $wpdb;
        
        $table = $wpdb->prefix . 'restaurant_keg_sizes';
        
        $sizes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} 
             WHERE product_id = %d AND is_active = 1
             ORDER BY display_order ASC, liters ASC",
            $product_id
        ), ARRAY_A);
        
        return $sizes ?: array();
    }
    
    /**
     * Supprimer une taille de fût
     */
    public static function delete_size($size_id)
    {
        global $wpdb;
        
        $table = $wpdb->prefix . 'restaurant_keg_sizes';
        
        // Vérifier que la taille existe
        $size = self::get_size($size_id);
        if (!$size) {
            return false;
        }
        
        $result = $wpdb->delete(
            $table,
            array('id' => (int) $size_id),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Supprimer toutes les tailles d'un produit
     */
    public static function delete_sizes_by_product($product_id)
    {
        global $wpdb;
        
        $table = $wpdb->prefix . 'restaurant_keg_sizes';
        
        $result = $wpdb->delete(
            $table,
            array('product_id' => (int) $product_id),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Compter les tailles d'un produit
     */
    public static function count_sizes_by_product($product_id)
    {
        global $wpdb;
        
        $table = $wpdb->prefix . 'restaurant_keg_sizes';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} 
             WHERE product_id = %d AND is_active = 1",
            $product_id
        ));
        
        return (int) $count;
    }
}
