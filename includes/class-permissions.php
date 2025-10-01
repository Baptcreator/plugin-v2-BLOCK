<?php
/**
 * Gestionnaire des permissions du plugin
 *
 * @package RestaurantBooking
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Permissions
{
    /**
     * Initialiser les permissions
     */
    public static function init()
    {
        add_action('init', array(__CLASS__, 'add_capabilities'));
        add_action('wp_loaded', array(__CLASS__, 'ensure_user_capabilities'));
    }

    /**
     * Ajouter les capacités personnalisées
     */
    public static function add_capabilities()
    {
        // Obtenir le rôle administrateur
        $admin_role = get_role('administrator');
        
        if ($admin_role) {
            // Ajouter les capacités personnalisées
            $admin_role->add_cap('manage_restaurant_quotes');
            $admin_role->add_cap('manage_restaurant_products');
            $admin_role->add_cap('manage_restaurant_settings');
            // manage_restaurant_games supprimé (les jeux sont maintenant dans restaurant_products)
            $admin_role->add_cap('manage_restaurant_categories');
        }

        // Ajouter aussi aux éditeurs si nécessaire
        $editor_role = get_role('editor');
        if ($editor_role) {
            $editor_role->add_cap('manage_restaurant_quotes');
            $editor_role->add_cap('manage_restaurant_products');
        }
    }

    /**
     * S'assurer que l'utilisateur actuel a les bonnes capacités
     */
    public static function ensure_user_capabilities()
    {
        $current_user = wp_get_current_user();
        
        // Si l'utilisateur peut gérer les options, lui donner toutes les capacités
        if ($current_user && $current_user->has_cap('manage_options')) {
            $current_user->add_cap('manage_restaurant_quotes');
            $current_user->add_cap('manage_restaurant_products');
            $current_user->add_cap('manage_restaurant_settings');
            // manage_restaurant_games supprimé (les jeux sont maintenant dans restaurant_products)
            $current_user->add_cap('manage_restaurant_categories');
        }
    }

    /**
     * Vérifier si l'utilisateur peut accéder à une page
     */
    public static function can_access_page($page)
    {
        $current_user = wp_get_current_user();
        
        // Super admin peut tout faire
        if (is_super_admin()) {
            return true;
        }

        // Administrateur peut tout faire
        if ($current_user->has_cap('manage_options')) {
            return true;
        }

        // Vérifications spécifiques par page
        switch ($page) {
            case 'games':
                return $current_user->has_cap('manage_restaurant_products');
            
            case 'products':
                return $current_user->has_cap('manage_restaurant_products');
            
            case 'categories':
                return $current_user->has_cap('manage_restaurant_categories') || 
                       $current_user->has_cap('manage_restaurant_products');
            
            case 'quotes':
                return $current_user->has_cap('manage_restaurant_quotes');
            
            case 'settings':
                return $current_user->has_cap('manage_restaurant_settings') || 
                       $current_user->has_cap('manage_options');
            
            default:
                return $current_user->has_cap('manage_restaurant_products');
        }
    }

    /**
     * Obtenir la capacité requise pour une page
     */
    public static function get_required_capability($page)
    {
        switch ($page) {
            case 'games':
                return 'manage_restaurant_products'; // Fallback compatible
            
            case 'products':
                return 'manage_restaurant_products';
            
            case 'categories':
                return 'manage_restaurant_products';
            
            case 'quotes':
                return 'manage_restaurant_quotes';
            
            case 'settings':
                return 'manage_restaurant_settings';
            
            default:
                return 'manage_options'; // Sécurité par défaut
        }
    }

    /**
     * Forcer l'ajout des capacités (pour dépannage)
     */
    public static function force_add_capabilities()
    {
        global $wp_roles;
        
        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }

        // Ajouter aux administrateurs
        $admin_role = $wp_roles->get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('manage_restaurant_quotes');
            $admin_role->add_cap('manage_restaurant_products');
            $admin_role->add_cap('manage_restaurant_settings');
            // manage_restaurant_games supprimé (les jeux sont maintenant dans restaurant_products)
            $admin_role->add_cap('manage_restaurant_categories');
        }

        // Forcer pour l'utilisateur actuel
        $current_user = wp_get_current_user();
        if ($current_user && $current_user->ID) {
            $current_user->add_cap('manage_restaurant_quotes');
            $current_user->add_cap('manage_restaurant_products');
            $current_user->add_cap('manage_restaurant_settings');
            // manage_restaurant_games supprimé (les jeux sont maintenant dans restaurant_products)
            $current_user->add_cap('manage_restaurant_categories');
        }

        return true;
    }
}

// Initialiser les permissions
RestaurantBooking_Permissions::init();
?>
