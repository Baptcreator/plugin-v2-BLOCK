<?php
/**
 * Classe de gestion des jeux
 *
 * @package RestaurantBooking
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Game
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
        add_action('init', array($this, 'init'));
    }

    /**
     * Initialisation
     */
    public function init()
    {
        // Hooks pour l'admin
        if (is_admin()) {
            add_action('wp_ajax_create_game', array($this, 'ajax_create_game'));
            add_action('wp_ajax_update_game', array($this, 'ajax_update_game'));
            add_action('wp_ajax_delete_game', array($this, 'ajax_delete_game'));
            add_action('wp_ajax_toggle_game_status', array($this, 'ajax_toggle_game_status'));
        }
    }

    /**
     * Créer un nouveau jeu
     */
    public static function create($data)
    {
        global $wpdb;

        // Validation des données obligatoires
        $required_fields = array('name', 'price');
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return new WP_Error('missing_field', sprintf(__('Le champ %s est obligatoire', 'restaurant-booking'), $field));
            }
        }

        // Préparer les données pour l'insertion
        $game_data = array(
            'name' => sanitize_text_field($data['name']),
            'description' => isset($data['description']) ? wp_kses_post($data['description']) : '',
            'price' => (float) $data['price'],
            'image_id' => isset($data['image_id']) && !empty($data['image_id']) ? (int) $data['image_id'] : null,
            'display_order' => isset($data['display_order']) ? (int) $data['display_order'] : 0,
            'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
            'created_at' => current_time('mysql')
        );

        // Insérer en base de données
        $result = $wpdb->insert(
            $wpdb->prefix . 'restaurant_games',
            $game_data,
            array('%s', '%s', '%f', '%d', '%d', '%d', '%s')
        );

        if ($result === false) {
            RestaurantBooking_Logger::error('Erreur lors de la création du jeu', array(
                'data' => $data,
                'error' => $wpdb->last_error
            ));
            return new WP_Error('db_error', __('Erreur lors de la création du jeu', 'restaurant-booking'));
        }

        $game_id = $wpdb->insert_id;

        // Log de la création
        RestaurantBooking_Logger::info("Nouveau jeu créé: {$data['name']}", array(
            'game_id' => $game_id
        ));

        return $game_id;
    }

    /**
     * Obtenir un jeu par ID
     */
    public static function get($game_id)
    {
        global $wpdb;

        $game = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}restaurant_games WHERE id = %d",
            $game_id
        ), ARRAY_A);

        if (!$game) {
            return null;
        }

        // Convertir les types
        $game['price'] = (float) $game['price'];
        $game['image_id'] = $game['image_id'] ? (int) $game['image_id'] : null;
        $game['display_order'] = (int) $game['display_order'];
        $game['is_active'] = (bool) $game['is_active'];

        // Ajouter l'URL de l'image si disponible
        if ($game['image_id']) {
            $game['image_url'] = wp_get_attachment_url($game['image_id']);
            $game['image_alt'] = get_post_meta($game['image_id'], '_wp_attachment_image_alt', true);
        } else {
            $game['image_url'] = null;
            $game['image_alt'] = '';
        }

        return $game;
    }

    /**
     * Mettre à jour un jeu
     */
    public static function update($game_id, $data)
    {
        global $wpdb;

        // Vérifier que le jeu existe
        $existing_game = self::get($game_id);
        if (!$existing_game) {
            return new WP_Error('game_not_found', __('Jeu introuvable', 'restaurant-booking'));
        }

        // Préparer les données à mettre à jour
        $update_data = array();
        $format = array();

        $updatable_fields = array(
            'name' => '%s',
            'description' => '%s',
            'price' => '%f',
            'image_id' => '%d',
            'display_order' => '%d',
            'is_active' => '%d'
        );

        foreach ($updatable_fields as $field => $field_format) {
            if (isset($data[$field])) {
                switch ($field) {
                    case 'name':
                        $update_data[$field] = sanitize_text_field($data[$field]);
                        break;
                    case 'description':
                        $update_data[$field] = wp_kses_post($data[$field]);
                        break;
                    case 'price':
                        $update_data[$field] = (float) $data[$field];
                        break;
                    case 'image_id':
                        $update_data[$field] = $data[$field] ? (int) $data[$field] : null;
                        break;
                    case 'display_order':
                        $update_data[$field] = (int) $data[$field];
                        break;
                    case 'is_active':
                        $update_data[$field] = (bool) $data[$field];
                        break;
                }
                $format[] = $field_format;
            }
        }

        if (empty($update_data)) {
            return new WP_Error('no_data', __('Aucune donnée à mettre à jour', 'restaurant-booking'));
        }

        $update_data['updated_at'] = current_time('mysql');
        $format[] = '%s';

        // Effectuer la mise à jour
        $result = $wpdb->update(
            $wpdb->prefix . 'restaurant_games',
            $update_data,
            array('id' => $game_id),
            $format,
            array('%d')
        );

        if ($result === false) {
            RestaurantBooking_Logger::error('Erreur lors de la mise à jour du jeu', array(
                'game_id' => $game_id,
                'data' => $data,
                'error' => $wpdb->last_error
            ));
            return new WP_Error('db_error', __('Erreur lors de la mise à jour', 'restaurant-booking'));
        }

        // Log de la mise à jour
        RestaurantBooking_Logger::info("Jeu mis à jour: {$existing_game['name']}", array(
            'game_id' => $game_id,
            'updated_fields' => array_keys($update_data)
        ));

        return true;
    }

    /**
     * Supprimer un jeu
     */
    public static function delete($game_id)
    {
        global $wpdb;

        $game = self::get($game_id);
        if (!$game) {
            return new WP_Error('game_not_found', __('Jeu introuvable', 'restaurant-booking'));
        }

        $result = $wpdb->delete(
            $wpdb->prefix . 'restaurant_games',
            array('id' => $game_id),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Erreur lors de la suppression', 'restaurant-booking'));
        }

        RestaurantBooking_Logger::info("Jeu supprimé: {$game['name']}", array(
            'game_id' => $game_id
        ));

        return true;
    }

    /**
     * Lister les jeux avec filtres
     */
    public static function get_list($args = array())
    {
        global $wpdb;

        $defaults = array(
            'is_active' => '',
            'search' => '',
            'orderby' => 'display_order',
            'order' => 'ASC',
            'limit' => 50,
            'offset' => 0
        );

        $args = wp_parse_args($args, $defaults);

        // Construire la requête
        $where_conditions = array();
        $params = array();

        if ($args['is_active'] !== '') {
            $where_conditions[] = 'is_active = %d';
            $params[] = (int) $args['is_active'];
        }

        if (!empty($args['search'])) {
            $where_conditions[] = '(name LIKE %s OR description LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }

        // Requête de comptage
        $count_sql = "SELECT COUNT(*) FROM {$wpdb->prefix}restaurant_games $where_clause";
        if (!empty($params)) {
            $count_sql = $wpdb->prepare($count_sql, $params);
        }
        $total = $wpdb->get_var($count_sql);

        // Requête principale
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        $sql = "SELECT * FROM {$wpdb->prefix}restaurant_games 
                $where_clause 
                ORDER BY $orderby 
                LIMIT %d OFFSET %d";

        $params[] = $args['limit'];
        $params[] = $args['offset'];

        $games = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);

        // Convertir les types et ajouter les URLs d'images
        foreach ($games as &$game) {
            $game['price'] = (float) $game['price'];
            $game['image_id'] = $game['image_id'] ? (int) $game['image_id'] : null;
            $game['display_order'] = (int) $game['display_order'];
            $game['is_active'] = (bool) $game['is_active'];

            if ($game['image_id']) {
                $game['image_url'] = wp_get_attachment_url($game['image_id']);
                $game['image_alt'] = get_post_meta($game['image_id'], '_wp_attachment_image_alt', true);
            } else {
                $game['image_url'] = null;
                $game['image_alt'] = '';
            }
        }

        return array(
            'games' => $games,
            'total' => (int) $total,
            'pages' => ceil($total / $args['limit'])
        );
    }

    /**
     * Obtenir les jeux actifs pour le frontend
     */
    public static function get_active_games()
    {
        return self::get_list(array(
            'is_active' => 1,
            'orderby' => 'display_order',
            'order' => 'ASC',
            'limit' => 999
        ));
    }

    /**
     * Changer l'ordre d'affichage
     */
    public static function update_display_order($game_id, $new_order)
    {
        global $wpdb;

        $result = $wpdb->update(
            $wpdb->prefix . 'restaurant_games',
            array('display_order' => (int) $new_order),
            array('id' => $game_id),
            array('%d'),
            array('%d')
        );

        return $result !== false;
    }

    /**
     * Basculer le statut actif/inactif
     */
    public static function toggle_status($game_id)
    {
        global $wpdb;

        $current_status = $wpdb->get_var($wpdb->prepare(
            "SELECT is_active FROM {$wpdb->prefix}restaurant_games WHERE id = %d",
            $game_id
        ));

        if ($current_status === null) {
            return new WP_Error('game_not_found', __('Jeu introuvable', 'restaurant-booking'));
        }

        $new_status = $current_status ? 0 : 1;

        $result = $wpdb->update(
            $wpdb->prefix . 'restaurant_games',
            array('is_active' => $new_status),
            array('id' => $game_id),
            array('%d'),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Erreur lors du changement de statut', 'restaurant-booking'));
        }

        return $new_status;
    }

    /**
     * AJAX: Créer un jeu
     */
    public function ajax_create_game()
    {
        check_ajax_referer('restaurant_booking_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Permissions insuffisantes', 'restaurant-booking'));
        }

        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'description' => wp_kses_post($_POST['description']),
            'price' => (float) $_POST['price'],
            'image_id' => !empty($_POST['image_id']) ? (int) $_POST['image_id'] : null,
            'display_order' => (int) $_POST['display_order'],
            'is_active' => isset($_POST['is_active']) ? (bool) $_POST['is_active'] : true
        );

        $result = self::create($data);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(array(
            'game_id' => $result,
            'message' => __('Jeu créé avec succès', 'restaurant-booking')
        ));
    }

    /**
     * AJAX: Mettre à jour un jeu
     */
    public function ajax_update_game()
    {
        check_ajax_referer('restaurant_booking_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Permissions insuffisantes', 'restaurant-booking'));
        }

        $game_id = (int) $_POST['game_id'];
        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'description' => wp_kses_post($_POST['description']),
            'price' => (float) $_POST['price'],
            'image_id' => !empty($_POST['image_id']) ? (int) $_POST['image_id'] : null,
            'display_order' => (int) $_POST['display_order'],
            'is_active' => isset($_POST['is_active']) ? (bool) $_POST['is_active'] : true
        );

        $result = self::update($game_id, $data);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(array(
            'message' => __('Jeu mis à jour avec succès', 'restaurant-booking')
        ));
    }

    /**
     * AJAX: Supprimer un jeu
     */
    public function ajax_delete_game()
    {
        check_ajax_referer('restaurant_booking_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Permissions insuffisantes', 'restaurant-booking'));
        }

        $game_id = (int) $_POST['game_id'];
        $result = self::delete($game_id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(array(
            'message' => __('Jeu supprimé avec succès', 'restaurant-booking')
        ));
    }

    /**
     * AJAX: Basculer le statut
     */
    public function ajax_toggle_game_status()
    {
        check_ajax_referer('restaurant_booking_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Permissions insuffisantes', 'restaurant-booking'));
        }

        $game_id = (int) $_POST['game_id'];
        $result = self::toggle_status($game_id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(array(
            'new_status' => $result,
            'message' => __('Statut mis à jour', 'restaurant-booking')
        ));
    }
}
