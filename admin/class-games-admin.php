<?php
/**
 * Administration des jeux
 *
 * @package RestaurantBooking
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Games_Admin
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Ajouter le menu d'administration
     */
    public function add_admin_menu()
    {
        add_submenu_page(
            'restaurant-booking',
            __('Gestion des Jeux', 'restaurant-booking'),
            __('Jeux', 'restaurant-booking'),
            class_exists('RestaurantBooking_Permissions') ? RestaurantBooking_Permissions::get_required_capability('games') : 'manage_options',
            'restaurant-booking-games',
            array($this, 'render_games_page')
        );
    }

    /**
     * Enregistrer les scripts d'administration
     */
    public function enqueue_admin_scripts($hook)
    {
        if (strpos($hook, 'restaurant-booking-games') === false) {
            return;
        }

        wp_enqueue_media();
        
        wp_enqueue_script(
            'restaurant-booking-games-admin',
            RESTAURANT_BOOKING_PLUGIN_URL . 'assets/js/games-admin.js',
            array('jquery', 'wp-util'),
            RESTAURANT_BOOKING_VERSION,
            true
        );

        wp_localize_script('restaurant-booking-games-admin', 'rb_games_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('restaurant_booking_admin'),
            'messages' => array(
                'confirm_delete' => __('Êtes-vous sûr de vouloir supprimer ce jeu ?', 'restaurant-booking'),
                'loading' => __('Chargement...', 'restaurant-booking'),
                'error' => __('Une erreur est survenue', 'restaurant-booking'),
                'success' => __('Opération réussie', 'restaurant-booking'),
            )
        ));
    }

    /**
     * Rendu de la page de gestion des jeux
     */
    public function render_games_page()
    {
        // Vérification des permissions
        if (class_exists('RestaurantBooking_Permissions')) {
            if (!RestaurantBooking_Permissions::can_access_page('games')) {
                wp_die(__('Désolé, vous n\'avez pas l\'autorisation d\'accéder à cette page.', 'restaurant-booking'));
            }
        } else {
            // Fallback si la classe n'est pas disponible
            if (!current_user_can('manage_options')) {
                wp_die(__('Désolé, vous n\'avez pas l\'autorisation d\'accéder à cette page.', 'restaurant-booking'));
            }
        }

        $action = $_GET['action'] ?? 'list';
        $game_id = $_GET['game_id'] ?? null;

        switch ($action) {
            case 'edit':
                $this->render_edit_game_page($game_id);
                break;
            case 'add':
                $this->render_add_game_page();
                break;
            default:
                $this->render_games_list_page();
                break;
        }
    }

    /**
     * Rendu de la liste des jeux
     */
    private function render_games_list_page()
    {
        // Traitement des actions
        if (isset($_POST['action']) && wp_verify_nonce($_POST['_wpnonce'], 'restaurant_booking_games_action')) {
            $this->handle_bulk_actions();
        }

        // Pagination
        $page = max(1, $_GET['paged'] ?? 1);
        $per_page = 20;
        $offset = ($page - 1) * $per_page;

        // Filtres
        $search = $_GET['s'] ?? '';
        $status_filter = $_GET['status'] ?? '';

        $args = array(
            'search' => $search,
            'is_active' => $status_filter !== '' ? (int) $status_filter : '',
            'limit' => $per_page,
            'offset' => $offset,
            'orderby' => $_GET['orderby'] ?? 'display_order',
            'order' => $_GET['order'] ?? 'ASC'
        );

        $games_data = RestaurantBooking_Game::get_list($args);
        $games = $games_data['games'];
        $total_games = $games_data['total'];
        $total_pages = $games_data['pages'];

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Gestion des Jeux', 'restaurant-booking'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-games&action=add'); ?>" class="page-title-action">
                <?php _e('Ajouter un jeu', 'restaurant-booking'); ?>
            </a>
            
            <?php if (isset($_GET['message'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo esc_html($this->get_message($_GET['message'])); ?></p>
                </div>
            <?php endif; ?>

            <form method="get" class="search-form">
                <input type="hidden" name="page" value="restaurant-booking-games">
                <p class="search-box">
                    <label class="screen-reader-text" for="game-search-input"><?php _e('Rechercher des jeux', 'restaurant-booking'); ?></label>
                    <input type="search" id="game-search-input" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('Rechercher...', 'restaurant-booking'); ?>">
                    
                    <select name="status">
                        <option value=""><?php _e('Tous les statuts', 'restaurant-booking'); ?></option>
                        <option value="1" <?php selected($status_filter, '1'); ?>><?php _e('Actif', 'restaurant-booking'); ?></option>
                        <option value="0" <?php selected($status_filter, '0'); ?>><?php _e('Inactif', 'restaurant-booking'); ?></option>
                    </select>
                    
                    <?php submit_button(__('Rechercher', 'restaurant-booking'), 'secondary', '', false, array('id' => 'search-submit')); ?>
                </p>
            </form>

            <form method="post" id="games-filter">
                <?php wp_nonce_field('restaurant_booking_games_action'); ?>
                
                <div class="tablenav top">
                    <div class="alignleft actions bulkactions">
                        <select name="action" id="bulk-action-selector-top">
                            <option value="-1"><?php _e('Actions groupées', 'restaurant-booking'); ?></option>
                            <option value="activate"><?php _e('Activer', 'restaurant-booking'); ?></option>
                            <option value="deactivate"><?php _e('Désactiver', 'restaurant-booking'); ?></option>
                            <option value="delete"><?php _e('Supprimer', 'restaurant-booking'); ?></option>
                        </select>
                        <?php submit_button(__('Appliquer', 'restaurant-booking'), 'action', '', false, array('id' => 'doaction')); ?>
                    </div>
                    
                    <?php if ($total_pages > 1): ?>
                    <div class="tablenav-pages">
                        <span class="displaying-num">
                            <?php printf(_n('%s élément', '%s éléments', $total_games, 'restaurant-booking'), number_format_i18n($total_games)); ?>
                        </span>
                        <?php
                        echo paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => __('&laquo;'),
                            'next_text' => __('&raquo;'),
                            'total' => $total_pages,
                            'current' => $page
                        ));
                        ?>
                    </div>
                    <?php endif; ?>
                </div>

                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <input id="cb-select-all-1" type="checkbox">
                            </td>
                            <th scope="col" class="manage-column column-image"><?php _e('Image', 'restaurant-booking'); ?></th>
                            <th scope="col" class="manage-column column-name column-primary sortable">
                                <a href="<?php echo esc_url(add_query_arg(array('orderby' => 'name', 'order' => $args['order'] === 'ASC' ? 'DESC' : 'ASC'))); ?>">
                                    <span><?php _e('Nom', 'restaurant-booking'); ?></span>
                                    <span class="sorting-indicator"></span>
                                </a>
                            </th>
                            <th scope="col" class="manage-column column-description"><?php _e('Description', 'restaurant-booking'); ?></th>
                            <th scope="col" class="manage-column column-price sortable">
                                <a href="<?php echo esc_url(add_query_arg(array('orderby' => 'price', 'order' => $args['order'] === 'ASC' ? 'DESC' : 'ASC'))); ?>">
                                    <span><?php _e('Prix', 'restaurant-booking'); ?></span>
                                    <span class="sorting-indicator"></span>
                                </a>
                            </th>
                            <th scope="col" class="manage-column column-order sortable">
                                <a href="<?php echo esc_url(add_query_arg(array('orderby' => 'display_order', 'order' => $args['order'] === 'ASC' ? 'DESC' : 'ASC'))); ?>">
                                    <span><?php _e('Ordre', 'restaurant-booking'); ?></span>
                                    <span class="sorting-indicator"></span>
                                </a>
                            </th>
                            <th scope="col" class="manage-column column-status"><?php _e('Statut', 'restaurant-booking'); ?></th>
                            <th scope="col" class="manage-column column-date"><?php _e('Date de création', 'restaurant-booking'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($games)): ?>
                            <tr class="no-items">
                                <td class="colspanchange" colspan="8">
                                    <?php _e('Aucun jeu trouvé.', 'restaurant-booking'); ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($games as $game): ?>
                                <tr>
                                    <th scope="row" class="check-column">
                                        <input id="cb-select-<?php echo $game['id']; ?>" type="checkbox" name="game_ids[]" value="<?php echo $game['id']; ?>">
                                    </th>
                                    <td class="column-image">
                                        <?php if ($game['image_url']): ?>
                                            <img src="<?php echo esc_url($game['image_url']); ?>" alt="<?php echo esc_attr($game['image_alt']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                        <?php else: ?>
                                            <div style="width: 50px; height: 50px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #666;">
                                                <span class="dashicons dashicons-format-image"></span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="column-name column-primary">
                                        <strong>
                                            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-games&action=edit&game_id=' . $game['id']); ?>">
                                                <?php echo esc_html($game['name']); ?>
                                            </a>
                                        </strong>
                                        <div class="row-actions">
                                            <span class="edit">
                                                <a href="<?php echo admin_url('admin.php?page=restaurant-booking-games&action=edit&game_id=' . $game['id']); ?>">
                                                    <?php _e('Modifier', 'restaurant-booking'); ?>
                                                </a> |
                                            </span>
                                            <span class="toggle-status">
                                                <a href="#" class="toggle-game-status" data-game-id="<?php echo $game['id']; ?>" data-current-status="<?php echo $game['is_active'] ? 1 : 0; ?>">
                                                    <?php echo $game['is_active'] ? __('Désactiver', 'restaurant-booking') : __('Activer', 'restaurant-booking'); ?>
                                                </a> |
                                            </span>
                                            <span class="delete">
                                                <a href="#" class="delete-game" data-game-id="<?php echo $game['id']; ?>" style="color: #a00;">
                                                    <?php _e('Supprimer', 'restaurant-booking'); ?>
                                                </a>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="column-description">
                                        <?php echo esc_html(wp_trim_words($game['description'], 10)); ?>
                                    </td>
                                    <td class="column-price">
                                        <strong><?php echo number_format($game['price'], 2, ',', ' '); ?> €</strong>
                                    </td>
                                    <td class="column-order">
                                        <input type="number" class="small-text game-order-input" 
                                               value="<?php echo $game['display_order']; ?>" 
                                               data-game-id="<?php echo $game['id']; ?>"
                                               min="0" max="999">
                                    </td>
                                    <td class="column-status">
                                        <?php if ($game['is_active']): ?>
                                            <span class="status-active"><?php _e('Actif', 'restaurant-booking'); ?></span>
                                        <?php else: ?>
                                            <span class="status-inactive"><?php _e('Inactif', 'restaurant-booking'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="column-date">
                                        <?php echo date_i18n(get_option('date_format'), strtotime($game['created_at'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="tablenav bottom">
                    <?php if ($total_pages > 1): ?>
                    <div class="tablenav-pages">
                        <?php
                        echo paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => __('&laquo;'),
                            'next_text' => __('&raquo;'),
                            'total' => $total_pages,
                            'current' => $page
                        ));
                        ?>
                    </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <style>
        .status-active { color: #46b450; font-weight: 600; }
        .status-inactive { color: #dc3232; font-weight: 600; }
        .game-order-input { width: 60px; }
        .column-image { width: 70px; }
        .column-price { width: 100px; }
        .column-order { width: 80px; }
        .column-status { width: 80px; }
        .column-date { width: 120px; }
        </style>
        <?php
    }

    /**
     * Rendu de la page d'ajout de jeu
     */
    private function render_add_game_page()
    {
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'restaurant_booking_add_game')) {
            $result = $this->handle_add_game();
            if (!is_wp_error($result)) {
                wp_redirect(admin_url('admin.php?page=restaurant-booking-games&message=1'));
                exit;
            }
        }

        $this->render_game_form();
    }

    /**
     * Rendu de la page d'édition de jeu
     */
    private function render_edit_game_page($game_id)
    {
        $game = RestaurantBooking_Game::get($game_id);
        if (!$game) {
            wp_die(__('Jeu introuvable.', 'restaurant-booking'));
        }

        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'restaurant_booking_edit_game')) {
            $result = $this->handle_edit_game($game_id);
            if (!is_wp_error($result)) {
                wp_redirect(admin_url('admin.php?page=restaurant-booking-games&message=2'));
                exit;
            }
        }

        $this->render_game_form($game);
    }

    /**
     * Rendu du formulaire de jeu
     */
    private function render_game_form($game = null)
    {
        $is_edit = !empty($game);
        $title = $is_edit ? __('Modifier le jeu', 'restaurant-booking') : __('Ajouter un jeu', 'restaurant-booking');
        $nonce_action = $is_edit ? 'restaurant_booking_edit_game' : 'restaurant_booking_add_game';

        ?>
        <div class="wrap">
            <h1><?php echo esc_html($title); ?></h1>
            
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field($nonce_action); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="game_name"><?php _e('Nom du jeu', 'restaurant-booking'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="text" id="game_name" name="name" class="regular-text" 
                                   value="<?php echo esc_attr($game['name'] ?? ''); ?>" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="game_description"><?php _e('Description', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <textarea id="game_description" name="description" class="large-text" rows="4"><?php echo esc_textarea($game['description'] ?? ''); ?></textarea>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="game_price"><?php _e('Prix', 'restaurant-booking'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="number" id="game_price" name="price" class="small-text" 
                                   value="<?php echo esc_attr($game['price'] ?? 70); ?>" 
                                   step="0.01" min="0" required> €
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="game_image"><?php _e('Image', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <div class="image-upload-container">
                                <?php if ($is_edit && $game['image_url']): ?>
                                    <div class="current-image">
                                        <img src="<?php echo esc_url($game['image_url']); ?>" alt="<?php echo esc_attr($game['image_alt']); ?>" style="max-width: 200px; height: auto;">
                                        <input type="hidden" name="current_image_id" value="<?php echo esc_attr($game['image_id']); ?>">
                                    </div>
                                <?php endif; ?>
                                
                                <input type="hidden" id="game_image_id" name="image_id" value="<?php echo esc_attr($game['image_id'] ?? ''); ?>">
                                <button type="button" class="button upload-image-button">
                                    <?php _e('Choisir une image', 'restaurant-booking'); ?>
                                </button>
                                <button type="button" class="button remove-image-button" style="<?php echo empty($game['image_id']) ? 'display: none;' : ''; ?>">
                                    <?php _e('Supprimer l\'image', 'restaurant-booking'); ?>
                                </button>
                                <div class="image-preview" style="margin-top: 10px;"></div>
                            </div>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="game_display_order"><?php _e('Ordre d\'affichage', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="game_display_order" name="display_order" class="small-text" 
                                   value="<?php echo esc_attr($game['display_order'] ?? 0); ?>" min="0" max="999">
                            <p class="description"><?php _e('Plus le nombre est petit, plus le jeu apparaîtra en premier.', 'restaurant-booking'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="game_is_active"><?php _e('Statut', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="game_is_active" name="is_active" value="1" 
                                       <?php checked($game['is_active'] ?? true); ?>>
                                <?php _e('Jeu actif', 'restaurant-booking'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <?php submit_button($is_edit ? __('Mettre à jour', 'restaurant-booking') : __('Ajouter le jeu', 'restaurant-booking'), 'primary', 'submit', false); ?>
                    <a href="<?php echo admin_url('admin.php?page=restaurant-booking-games'); ?>" class="button button-secondary">
                        <?php _e('Annuler', 'restaurant-booking'); ?>
                    </a>
                </p>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Upload d'image
            var mediaUploader;
            
            $('.upload-image-button').click(function(e) {
                e.preventDefault();
                
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                
                mediaUploader = wp.media({
                    title: '<?php _e('Choisir une image', 'restaurant-booking'); ?>',
                    button: {
                        text: '<?php _e('Utiliser cette image', 'restaurant-booking'); ?>'
                    },
                    multiple: false
                });
                
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#game_image_id').val(attachment.id);
                    $('.image-preview').html('<img src="' + attachment.url + '" style="max-width: 200px; height: auto;">');
                    $('.remove-image-button').show();
                });
                
                mediaUploader.open();
            });
            
            $('.remove-image-button').click(function(e) {
                e.preventDefault();
                $('#game_image_id').val('');
                $('.image-preview').empty();
                $('.current-image').hide();
                $(this).hide();
            });
        });
        </script>
        <?php
    }

    /**
     * Traiter l'ajout d'un jeu
     */
    private function handle_add_game()
    {
        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'description' => wp_kses_post($_POST['description']),
            'price' => (float) $_POST['price'],
            'image_id' => !empty($_POST['image_id']) ? (int) $_POST['image_id'] : null,
            'display_order' => (int) $_POST['display_order'],
            'is_active' => isset($_POST['is_active'])
        );

        return RestaurantBooking_Game::create($data);
    }

    /**
     * Traiter l'édition d'un jeu
     */
    private function handle_edit_game($game_id)
    {
        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'description' => wp_kses_post($_POST['description']),
            'price' => (float) $_POST['price'],
            'image_id' => !empty($_POST['image_id']) ? (int) $_POST['image_id'] : null,
            'display_order' => (int) $_POST['display_order'],
            'is_active' => isset($_POST['is_active'])
        );

        return RestaurantBooking_Game::update($game_id, $data);
    }

    /**
     * Traiter les actions groupées
     */
    private function handle_bulk_actions()
    {
        $action = $_POST['action'];
        $game_ids = $_POST['game_ids'] ?? array();

        if (empty($game_ids)) {
            return;
        }

        $success_count = 0;
        $error_count = 0;

        foreach ($game_ids as $game_id) {
            switch ($action) {
                case 'activate':
                    $result = RestaurantBooking_Game::update($game_id, array('is_active' => true));
                    break;
                case 'deactivate':
                    $result = RestaurantBooking_Game::update($game_id, array('is_active' => false));
                    break;
                case 'delete':
                    $result = RestaurantBooking_Game::delete($game_id);
                    break;
                default:
                    continue 2;
            }

            if (is_wp_error($result)) {
                $error_count++;
            } else {
                $success_count++;
            }
        }

        $message = 3; // Message d'action groupée
        wp_redirect(admin_url('admin.php?page=restaurant-booking-games&message=' . $message . '&success=' . $success_count . '&errors=' . $error_count));
        exit;
    }

    /**
     * Obtenir le message selon le code
     */
    private function get_message($code)
    {
        $messages = array(
            1 => __('Jeu ajouté avec succès.', 'restaurant-booking'),
            2 => __('Jeu mis à jour avec succès.', 'restaurant-booking'),
            3 => sprintf(__('%d jeux traités avec succès.', 'restaurant-booking'), $_GET['success'] ?? 0),
        );

        return $messages[$code] ?? __('Opération terminée.', 'restaurant-booking');
    }
}
