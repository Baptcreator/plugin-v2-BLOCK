<?php
/**
 * Classe d'administration des produits
 *
 * @package RestaurantBooking
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Products_Admin
{
    /**
     * Afficher la liste des produits
     */
    public function display_list()
    {
        // Gérer les actions (suppression, etc.)
        $this->handle_actions();
        
        // Traitement des actions
        if (isset($_POST['action']) && wp_verify_nonce($_POST['_wpnonce'], 'restaurant_booking_products_action')) {
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

        // Récupérer les données (simulation pour l'exemple)
        $products = $this->get_sample_products();
        $total_products = count($products);
        $total_pages = ceil($total_products / $per_page);

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Gestion des produits', 'restaurant-booking'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-products&action=add'); ?>" class="page-title-action">
                <?php _e('Ajouter un produit', 'restaurant-booking'); ?>
            </a>
            <hr class="wp-header-end">
            
            <?php if (isset($_GET['message'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo esc_html($this->get_message($_GET['message'])); ?></p>
                </div>
            <?php endif; ?>

            <div class="restaurant-booking-info-card">
                <h3><?php _e('Gestion des produits', 'restaurant-booking'); ?></h3>
                <ul>
                    <li><?php _e('✓ Plats signature et spécialités de la maison', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Gestion des prix et disponibilités', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Organisation par catégories', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Images et descriptions détaillées', 'restaurant-booking'); ?></li>
                </ul>
            </div>

            <!-- Statistiques rapides -->
            <div class="restaurant-booking-stats">
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><?php echo count($products); ?></h3>
                        <p><?php _e('Produits totaux', 'restaurant-booking'); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo count(array_filter($products, function($p) { return $p['is_active']; })); ?></h3>
                        <p><?php _e('Produits actifs', 'restaurant-booking'); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo count(array_unique(array_column($products, 'category_name'))); ?></h3>
                        <p><?php _e('Catégories', 'restaurant-booking'); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo number_format(array_sum(array_column($products, 'price')), 2, ',', ' '); ?> €</h3>
                        <p><?php _e('Valeur totale', 'restaurant-booking'); ?></p>
                    </div>
                </div>
            </div>

            <form method="get" class="search-form">
                <input type="hidden" name="page" value="restaurant-booking-products">
                <p class="search-box">
                    <label class="screen-reader-text" for="product-search-input"><?php _e('Rechercher des produits', 'restaurant-booking'); ?></label>
                    <input type="search" id="product-search-input" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('Rechercher...', 'restaurant-booking'); ?>">
                    
                    <select name="status">
                        <option value=""><?php _e('Tous les statuts', 'restaurant-booking'); ?></option>
                        <option value="1" <?php selected($status_filter, '1'); ?>><?php _e('Actif', 'restaurant-booking'); ?></option>
                        <option value="0" <?php selected($status_filter, '0'); ?>><?php _e('Inactif', 'restaurant-booking'); ?></option>
                    </select>
                    
                    <?php submit_button(__('Rechercher', 'restaurant-booking'), 'secondary', '', false, array('id' => 'search-submit')); ?>
                </p>
            </form>

            <form method="post" id="products-filter">
                <?php wp_nonce_field('restaurant_booking_products_action'); ?>
                
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
                            <?php printf(_n('%s élément', '%s éléments', $total_products, 'restaurant-booking'), number_format_i18n($total_products)); ?>
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
                        <?php if (empty($products)): ?>
                            <tr class="no-items">
                                <td class="colspanchange" colspan="8">
                                    <?php _e('Aucun produit trouvé.', 'restaurant-booking'); ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <th scope="row" class="check-column">
                                        <input id="cb-select-<?php echo $product['id']; ?>" type="checkbox" name="product_ids[]" value="<?php echo $product['id']; ?>">
                                    </th>
                                    <td class="column-image">
                                        <?php if (!empty($product['image_url'])): ?>
                                            <img src="<?php echo esc_url($product['image_url']); ?>" alt="<?php echo esc_attr($product['name']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                        <?php else: ?>
                                            <div style="width: 50px; height: 50px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #666;">
                                                <span class="dashicons dashicons-format-image"></span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="column-name column-primary">
                                        <strong>
                                            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-products&action=edit&product_id=' . $product['id']); ?>">
                                                <?php echo esc_html($product['name']); ?>
                                            </a>
                                        </strong>
                                        <div class="row-actions">
                                            <span class="edit">
                                                <a href="<?php echo admin_url('admin.php?page=restaurant-booking-products&action=edit&product_id=' . $product['id']); ?>">
                                                    <?php _e('Modifier', 'restaurant-booking'); ?>
                                                </a> |
                                            </span>
                                            <span class="toggle-status">
                                                <a href="#" class="toggle-product-status" data-product-id="<?php echo $product['id']; ?>" data-current-status="<?php echo $product['is_active'] ? 1 : 0; ?>">
                                                    <?php echo $product['is_active'] ? __('Désactiver', 'restaurant-booking') : __('Activer', 'restaurant-booking'); ?>
                                                </a> |
                                            </span>
                                            <span class="delete">
                                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=restaurant-booking-products&action=delete&product_id=' . $product['id']), 'delete_product_' . $product['id']); ?>" 
                                                   class="button button-small button-link-delete" 
                                                   onclick="return confirm('<?php _e('Êtes-vous sûr de vouloir supprimer ce produit ?', 'restaurant-booking'); ?>')">
                                                    <?php _e('Supprimer', 'restaurant-booking'); ?>
                                                </a>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="column-description">
                                        <?php echo esc_html(wp_trim_words($product['description'], 10)); ?>
                                    </td>
                                    <td class="column-price">
                                        <strong><?php echo number_format($product['price'], 2, ',', ' '); ?> €</strong>
                                    </td>
                                    <td class="column-order">
                                        <input type="number" class="small-text product-order-input" 
                                               value="<?php echo $product['display_order'] ?? 0; ?>" 
                                               data-product-id="<?php echo $product['id']; ?>"
                                               min="0" max="999">
                                    </td>
                                    <td class="column-status">
                                        <?php if ($product['is_active']): ?>
                                            <span class="status-active"><?php _e('Actif', 'restaurant-booking'); ?></span>
                                        <?php else: ?>
                                            <span class="status-inactive"><?php _e('Inactif', 'restaurant-booking'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="column-date">
                                        <?php echo date_i18n(get_option('date_format'), strtotime($product['created_at'] ?? 'now')); ?>
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
        .restaurant-booking-info-card {
            background: #e8f5e8;
            border: 1px solid #4caf50;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }
        .restaurant-booking-info-card h3 {
            margin-top: 0;
            color: #2e7d32;
        }
        .restaurant-booking-info-card ul {
            margin-bottom: 0;
        }
        .restaurant-booking-stats {
            margin: 20px 0;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: #fff;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
        }
        .stat-card h3 {
            margin: 0 0 5px 0;
            font-size: 24px;
            color: #1d2327;
        }
        .stat-card p {
            margin: 0;
            color: #646970;
            font-size: 13px;
        }
        .status-active { color: #46b450; font-weight: 600; }
        .status-inactive { color: #dc3232; font-weight: 600; }
        .product-order-input { width: 60px; }
        .column-image { width: 70px; }
        .column-price { width: 100px; text-align: center; }
        .column-order { width: 80px; }
        .column-status { width: 80px; }
        .column-date { width: 120px; }
        </style>
        <?php
    }

    /**
     * Données d'exemple pour les produits
     */
    private function get_sample_products()
    {
        return array(
            array(
                'id' => 1,
                'name' => 'Salade César',
                'description' => 'Salade fraîche avec croûtons, parmesan et sauce César maison',
                'category_name' => 'Entrées',
                'price' => 12.50,
                'unit' => 'portion',
                'service_type' => 'restaurant',
                'manage_stock' => false,
                'stock_quantity' => 0,
                'is_active' => true,
                'display_order' => 1,
                'image_url' => '',
                'created_at' => '2025-09-20 10:30:00'
            ),
            array(
                'id' => 2,
                'name' => 'Plateau de fromages',
                'description' => 'Sélection de fromages français avec pain et confiture',
                'category_name' => 'Plateaux',
                'price' => 25.00,
                'unit' => 'plateau',
                'service_type' => 'remorque',
                'manage_stock' => true,
                'stock_quantity' => 15,
                'is_active' => true,
                'display_order' => 2,
                'image_url' => '',
                'created_at' => '2025-09-21 14:15:00'
            ),
            array(
                'id' => 3,
                'name' => 'Cocktail de bienvenue',
                'description' => 'Cocktail maison pour accueillir vos invités',
                'category_name' => 'Boissons',
                'price' => 8.00,
                'unit' => 'verre',
                'service_type' => 'remorque',
                'manage_stock' => false,
                'stock_quantity' => 0,
                'is_active' => true,
                'display_order' => 3,
                'image_url' => '',
                'created_at' => '2025-09-22 09:45:00'
            ),
            array(
                'id' => 4,
                'name' => 'Magret de canard',
                'description' => 'Magret de canard aux figues avec légumes de saison',
                'category_name' => 'Plats principaux',
                'price' => 28.00,
                'unit' => 'portion',
                'service_type' => 'restaurant',
                'manage_stock' => true,
                'stock_quantity' => 0,
                'is_active' => false,
                'display_order' => 4,
                'image_url' => '',
                'created_at' => '2025-09-23 16:20:00'
            ),
            array(
                'id' => 5,
                'name' => 'Buffet desserts',
                'description' => 'Assortiment de desserts maison pour 10 personnes',
                'category_name' => 'Desserts',
                'price' => 85.00,
                'unit' => 'buffet',
                'service_type' => 'remorque',
                'manage_stock' => true,
                'stock_quantity' => 8,
                'is_active' => true,
                'display_order' => 5,
                'image_url' => '',
                'created_at' => '2025-09-24 11:10:00'
            )
        );
    }

    /**
     * Données d'exemple pour les catégories
     */
    private function get_sample_categories()
    {
        return array(
            array('id' => 1, 'name' => 'Entrées'),
            array('id' => 2, 'name' => 'Plats principaux'),
            array('id' => 3, 'name' => 'Desserts'),
            array('id' => 4, 'name' => 'Boissons'),
            array('id' => 5, 'name' => 'Plateaux')
        );
    }

    /**
     * Traiter les actions groupées
     */
    private function handle_bulk_actions()
    {
        $action = $_POST['action'];
        $product_ids = $_POST['product_ids'] ?? array();

        if (empty($product_ids)) {
            return;
        }

        $success_count = 0;
        $error_count = 0;

        foreach ($product_ids as $product_id) {
            switch ($action) {
                case 'activate':
                    // Ici on appellerait RestaurantBooking_Product::update($product_id, array('is_active' => true));
                    $success_count++;
                    break;
                case 'deactivate':
                    // Ici on appellerait RestaurantBooking_Product::update($product_id, array('is_active' => false));
                    $success_count++;
                    break;
                case 'delete':
                    // Ici on appellerait RestaurantBooking_Product::delete($product_id);
                    $success_count++;
                    break;
                default:
                    continue 2;
            }
        }

        $message = 3; // Message d'action groupée
        wp_redirect(admin_url('admin.php?page=restaurant-booking-products&message=' . $message . '&success=' . $success_count . '&errors=' . $error_count));
        exit;
    }

    /**
     * Obtenir le message selon le code
     */
    private function get_message($code)
    {
        $messages = array(
            1 => __('Produit ajouté avec succès.', 'restaurant-booking'),
            2 => __('Produit mis à jour avec succès.', 'restaurant-booking'),
            3 => sprintf(__('%d produits traités avec succès.', 'restaurant-booking'), $_GET['success'] ?? 0),
        );

        return $messages[$code] ?? __('Opération terminée.', 'restaurant-booking');
    }

    /**
     * Gérer les actions (suppression, etc.)
     */
    public function handle_actions()
    {
        if (!isset($_GET['action']) || !isset($_GET['product_id'])) {
            return;
        }

        $action = sanitize_text_field($_GET['action']);
        $product_id = (int) $_GET['product_id'];

        switch ($action) {
            case 'delete':
                $this->delete_product($product_id);
                break;
        }
    }

    /**
     * Supprimer un produit
     */
    private function delete_product($product_id)
    {
        if (!wp_verify_nonce($_GET['_wpnonce'], 'delete_product_' . $product_id)) {
            wp_die(__('Action non autorisée.', 'restaurant-booking'));
        }

        if (!current_user_can('manage_restaurant_quotes')) {
            wp_die(__('Permissions insuffisantes.', 'restaurant-booking'));
        }

        $result = RestaurantBooking_Product::delete($product_id);
        
        if (is_wp_error($result)) {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-products&message=error&error=' . urlencode($result->get_error_message())));
        } else {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-products&message=deleted'));
        }
        exit;
    }
}
