<?php
/**
 * Classe d'administration des Boissons Soft
 *
 * @package RestaurantBooking
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Beverages_Soft_Admin
{
    /**
     * Afficher la liste des boissons soft
     */
    public function display_list()
    {
        // Gérer les actions (suppression, etc.)
        $this->handle_actions();
        
        $products = $this->get_soft_beverages();
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">🍷 <?php _e('Boissons Soft', 'restaurant-booking'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-soft&action=add'); ?>" class="page-title-action">
                <?php _e('Ajouter une boisson soft', 'restaurant-booking'); ?>
            </a>
            <hr class="wp-header-end">

            <div class="restaurant-booking-info-card">
                <h3><?php _e('Nouveau système de boissons', 'restaurant-booking'); ?></h3>
                <ul>
                    <li><?php _e('✓ Contenances multiples par boisson (ex: Coca 33cl ET 1L)', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Prix et images spécifiques par contenance', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Mise en avant par taille de contenant', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Intégration avec la médiathèque WordPress', 'restaurant-booking'); ?></li>
                </ul>
            </div>

            <!-- Statistiques rapides -->
            <div class="restaurant-booking-stats">
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><?php echo count($products); ?></h3>
                        <p><?php _e('Boissons soft', 'restaurant-booking'); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo count(array_filter($products, function($p) { return $p['is_active']; })); ?></h3>
                        <p><?php _e('Actives', 'restaurant-booking'); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo count(array_filter($products, function($p) { return $p['suggested_beverage']; })); ?></h3>
                        <p><?php _e('En suggestion', 'restaurant-booking'); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $products ? number_format(array_sum(array_column($products, 'price')) / count($products), 2, ',', ' ') : '0'; ?> €</h3>
                        <p><?php _e('Prix moyen', 'restaurant-booking'); ?></p>
                    </div>
                </div>
            </div>

            <form method="post" id="soft-beverages-filter">
                <?php wp_nonce_field('restaurant_booking_soft_beverages_action'); ?>
                
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
                </div>

                <!-- Tableau des produits -->
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <input id="cb-select-all-1" type="checkbox">
                            </td>
                            <th scope="col" class="manage-column column-image"><?php _e('Image', 'restaurant-booking'); ?></th>
                            <th scope="col" class="manage-column column-name column-primary"><?php _e('Nom', 'restaurant-booking'); ?></th>
                            <th scope="col" class="manage-column column-description"><?php _e('Description', 'restaurant-booking'); ?></th>
                            <th scope="col" class="manage-column column-size"><?php _e('Contenance', 'restaurant-booking'); ?></th>
                            <th scope="col" class="manage-column column-price"><?php _e('Prix', 'restaurant-booking'); ?></th>
                            <th scope="col" class="manage-column column-suggestion"><?php _e('Suggestion', 'restaurant-booking'); ?></th>
                            <th scope="col" class="manage-column column-order"><?php _e('Ordre', 'restaurant-booking'); ?></th>
                            <th scope="col" class="manage-column column-status"><?php _e('Statut', 'restaurant-booking'); ?></th>
                            <th scope="col" class="manage-column column-date"><?php _e('Date de création', 'restaurant-booking'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr class="no-items">
                                <td class="colspanchange" colspan="10">
                                    <?php _e('Aucune boisson soft trouvée.', 'restaurant-booking'); ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $beverage): ?>
                                <!-- Ligne principale de la boisson -->
                                <tr class="soft-main-row" data-beverage-id="<?php echo $beverage['id']; ?>">
                                    <th scope="row" class="check-column">
                                        <input id="cb-select-<?php echo $beverage['id']; ?>" type="checkbox" name="soft_beverage_ids[]" value="<?php echo $beverage['id']; ?>">
                                    </th>
                                    <td class="column-image">
                                        <?php if (!empty($beverage['image_url'])): ?>
                                            <img src="<?php echo esc_url($beverage['image_url']); ?>" alt="<?php echo esc_attr($beverage['name']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                        <?php else: ?>
                                            <div style="width: 50px; height: 50px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #666;">
                                                <span class="dashicons dashicons-format-image"></span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="column-name column-primary">
                                        <strong>
                                            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-soft&action=edit&product_id=' . $beverage['id']); ?>">
                                                🥤 <?php echo esc_html($beverage['name']); ?>
                                            </a>
                                        </strong>
                                        <?php if ($beverage['has_multiple_sizes'] && !empty($beverage['sizes'])): ?>
                                            <small class="soft-sizes-info">(<?php echo count($beverage['sizes']); ?> contenances disponibles)</small>
                                        <?php elseif (isset($beverage['needs_configuration'])): ?>
                                            <small class="soft-needs-config" style="color: #d63638;">(Configuration requise)</small>
                                        <?php endif; ?>
                                        <div class="row-actions">
                                            <span class="edit">
                                                <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-soft&action=edit&product_id=' . $beverage['id']); ?>">
                                                    <?php _e('Modifier', 'restaurant-booking'); ?>
                                                </a> |
                                            </span>
                                            <?php if ($beverage['has_multiple_sizes'] && !empty($beverage['sizes'])): ?>
                                                <span class="toggle-sizes">
                                                    <a href="#" class="toggle-soft-sizes" data-beverage-id="<?php echo $beverage['id']; ?>">
                                                        <?php _e('Voir contenances', 'restaurant-booking'); ?>
                                                    </a> |
                                                </span>
                                            <?php endif; ?>
                                            <span class="toggle-status">
                                                <a href="#" class="toggle-soft-beverage-status" data-product-id="<?php echo $beverage['id']; ?>" data-current-status="<?php echo $beverage['is_active'] ? 1 : 0; ?>">
                                                    <?php echo $beverage['is_active'] ? __('Désactiver', 'restaurant-booking') : __('Activer', 'restaurant-booking'); ?>
                                                </a> |
                                            </span>
                                            <span class="delete">
                                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=restaurant-booking-beverages-soft&action=delete&product_id=' . $beverage['id']), 'delete_soft_beverage_' . $beverage['id']); ?>" 
                                                   class="button button-small button-link-delete" 
                                                   onclick="return confirm('<?php _e('Êtes-vous sûr de vouloir supprimer cette boisson ?', 'restaurant-booking'); ?>')">
                                                    <?php _e('Supprimer', 'restaurant-booking'); ?>
                                                </a>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="column-description">
                                        <?php echo esc_html(wp_trim_words($beverage['description'] ?? '', 10)); ?>
                                    </td>
                                    <td class="column-size">
                                        <?php if ($beverage['has_multiple_sizes'] && !empty($beverage['sizes'])): ?>
                                            <strong><?php echo count($beverage['sizes']); ?> tailles</strong>
                                            <br><small>
                                                <?php 
                                                $size_labels = array_map(function($size) { return $size['size_label']; }, $beverage['sizes']);
                                                echo esc_html(implode(', ', $size_labels)); 
                                                ?>
                                            </small>
                                        <?php elseif (isset($beverage['legacy_data'])): ?>
                                            <strong><?php echo $beverage['legacy_data']['size_label']; ?></strong>
                                            <br><small><?php echo $beverage['legacy_data']['size_cl']; ?> cl</small>
                                        <?php else: ?>
                                            <span style="color: #d63638;">Non configuré</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="column-price">
                                        <?php if ($beverage['has_multiple_sizes'] && !empty($beverage['sizes'])): ?>
                                            <?php 
                                            $prices = array_map(function($size) { return $size['price']; }, $beverage['sizes']);
                                            $min_price = min($prices);
                                            $max_price = max($prices);
                                            ?>
                                            <?php if ($min_price === $max_price): ?>
                                                <strong><?php echo number_format($min_price, 2, ',', ' '); ?> €</strong>
                                            <?php else: ?>
                                                <strong><?php echo number_format($min_price, 2, ',', ' '); ?> - <?php echo number_format($max_price, 2, ',', ' '); ?> €</strong>
                                            <?php endif; ?>
                                        <?php elseif (isset($beverage['legacy_data'])): ?>
                                            <strong><?php echo number_format($beverage['legacy_data']['price'], 2, ',', ' '); ?> €</strong>
                                        <?php else: ?>
                                            <span style="color: #d63638;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="column-suggestion">
                                        <?php if ($beverage['suggested_beverage']): ?>
                                            <span class="dashicons dashicons-star-filled" style="color: #ffb900;" title="<?php _e('En suggestion', 'restaurant-booking'); ?>"></span>
                                            <small><?php _e('Oui', 'restaurant-booking'); ?></small>
                                        <?php else: ?>
                                            <span class="dashicons dashicons-star-empty" style="color: #ddd;"></span>
                                            <small><?php _e('Non', 'restaurant-booking'); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="column-order">
                                        <input type="number" class="small-text soft-beverage-order-input" 
                                               value="<?php echo $beverage['display_order'] ?? 0; ?>" 
                                               data-product-id="<?php echo $beverage['id']; ?>"
                                               min="0" max="999">
                                    </td>
                                    <td class="column-status">
                                        <?php if ($beverage['is_active']): ?>
                                            <span class="status-active"><?php _e('Actif', 'restaurant-booking'); ?></span>
                                        <?php else: ?>
                                            <span class="status-inactive"><?php _e('Inactif', 'restaurant-booking'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="column-date">
                                        <?php echo date_i18n(get_option('date_format'), strtotime($beverage['created_at'] ?? 'now')); ?>
                                    </td>
                                </tr>

                                <!-- Lignes des contenances (cachées par défaut) -->
                                <?php if ($beverage['has_multiple_sizes'] && !empty($beverage['sizes'])): ?>
                                    <?php foreach ($beverage['sizes'] as $size): ?>
                                        <tr class="soft-size-row" data-beverage-id="<?php echo $beverage['id']; ?>" style="display: none;">
                                            <td class="check-column"></td>
                                            <td class="column-image">
                                                <?php if (!empty($size['image_url'])): ?>
                                                    <img src="<?php echo esc_url($size['image_url']); ?>" alt="<?php echo esc_attr($beverage['name'] . ' ' . $size['size_label']); ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; opacity: 0.8;">
                                                <?php else: ?>
                                                    <div style="width: 40px; height: 40px; background: #f9f9f9; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #999;">
                                                        <span class="dashicons dashicons-admin-generic" style="font-size: 16px;"></span>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="column-name column-primary">
                                                <span style="padding-left: 20px; color: #666;">
                                                    ├─ <strong><?php echo esc_html($size['size_label']); ?></strong>
                                                </span>
                                                <div class="row-actions" style="padding-left: 20px;">
                                                    <span class="edit">
                                                        <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-soft&action=edit&product_id=' . $beverage['id'] . '#size-' . $size['size_id']); ?>">
                                                            <?php _e('Modifier cette contenance', 'restaurant-booking'); ?>
                                                        </a>
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="column-description">
                                                <small style="color: #666;">Contenance: <?php echo $size['size_cl']; ?> cl</small>
                                            </td>
                                            <td class="column-size">
                                                <strong><?php echo $size['size_label']; ?></strong>
                                            </td>
                                            <td class="column-price">
                                                <strong><?php echo number_format($size['price'], 2, ',', ' '); ?> €</strong>
                                            </td>
                                            <td class="column-suggestion">
                                                <?php if ($size['is_featured']): ?>
                                                    <span class="dashicons dashicons-star-filled" style="color: #ffb900;" title="<?php _e('Contenance mise en avant', 'restaurant-booking'); ?>"></span>
                                                <?php else: ?>
                                                    <span class="dashicons dashicons-star-empty" style="color: #ddd;"></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="column-order">
                                                <small><?php echo $size['display_order']; ?></small>
                                            </td>
                                            <td class="column-status">
                                                <?php if ($size['is_active']): ?>
                                                    <small class="status-active"><?php _e('Actif', 'restaurant-booking'); ?></small>
                                                <?php else: ?>
                                                    <small class="status-inactive"><?php _e('Inactif', 'restaurant-booking'); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="column-date">
                                                <small style="color: #666;">—</small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
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
        .product-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .product-thumb {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }
        .description {
            color: #666;
            font-style: italic;
        }
        .suggestion-yes { 
            color: #ff9800; 
            font-weight: bold;
        }
        .suggestion-no { color: #666; }
        .status-active { color: #46b450; font-weight: 600; }
        .status-inactive { color: #dc3232; font-weight: 600; }
        .soft-beverage-order-input { width: 60px; }
        .column-image { width: 70px; }
        .column-price { width: 100px; text-align: center; }
        .column-size { width: 100px; }
        .column-suggestion { width: 100px; }
        .column-order { width: 80px; }
        .column-status { width: 80px; }
        .column-date { width: 120px; }
        
        /* Styles pour l'affichage hiérarchique des boissons soft */
        .soft-main-row {
            background-color: #fff;
        }
        
        .soft-main-row:hover {
            background-color: #f6f7f7;
        }
        
        .soft-size-row {
            background-color: #f9f9f9;
            border-left: 3px solid #4caf50;
        }
        
        .soft-size-row:hover {
            background-color: #f0f0f1;
        }
        
        .soft-sizes-info {
            color: #4caf50;
            font-weight: 500;
        }
        
        .soft-needs-config {
            background: #fff2f2;
            padding: 2px 6px;
            border-radius: 3px;
            border: 1px solid #ffb2b2;
        }
        
        .toggle-soft-sizes {
            color: #4caf50;
            text-decoration: none;
        }
        
        .toggle-soft-sizes:hover {
            color: #2e7d32;
        }
        
        .toggle-soft-sizes.expanded:after {
            content: ' ▲';
        }
        
        .toggle-soft-sizes:not(.expanded):after {
            content: ' ▼';
        }
        </style>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Gérer l'affichage/masquage des contenances
            $('.toggle-soft-sizes').on('click', function(e) {
                e.preventDefault();
                
                var beverageId = $(this).data('beverage-id');
                var sizeRows = $('.soft-size-row[data-beverage-id="' + beverageId + '"]');
                var isExpanded = $(this).hasClass('expanded');
                
                if (isExpanded) {
                    // Masquer les contenances
                    sizeRows.fadeOut(200);
                    $(this).removeClass('expanded').text('<?php _e('Voir contenances', 'restaurant-booking'); ?>');
                } else {
                    // Afficher les contenances
                    sizeRows.fadeIn(200);
                    $(this).addClass('expanded').text('<?php _e('Masquer contenances', 'restaurant-booking'); ?>');
                }
            });
            
            // Améliorer l'UX : clic sur la ligne principale pour développer
            $('.soft-main-row').on('click', function(e) {
                // Ne pas déclencher si on clique sur un lien ou un input
                if ($(e.target).is('a, input, button') || $(e.target).closest('a, input, button').length > 0) {
                    return;
                }
                
                var beverageId = $(this).data('beverage-id');
                var toggleLink = $('.toggle-soft-sizes[data-beverage-id="' + beverageId + '"]');
                
                if (toggleLink.length > 0) {
                    toggleLink.trigger('click');
                }
            });
            
            // Ajouter un curseur pointer sur les lignes cliquables
            $('.soft-main-row').each(function() {
                var beverageId = $(this).data('beverage-id');
                var hasToggle = $('.toggle-soft-sizes[data-beverage-id="' + beverageId + '"]').length > 0;
                
                if (hasToggle) {
                    $(this).css('cursor', 'pointer').attr('title', 'Cliquer pour voir les contenances');
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Afficher le formulaire d'ajout/modification
     */
    public function display_form()
    {
        // Charger les scripts de la médiathèque WordPress
        wp_enqueue_media();
        
        $product_id = isset($_GET['product_id']) ? (int) $_GET['product_id'] : 0;
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'add';
        $product = null;

        if ($product_id && $action === 'edit') {
            $product = RestaurantBooking_Product::get($product_id);
            if (!$product || $product['category_type'] !== 'soft') {
                wp_die(__('Produit introuvable ou type incorrect.', 'restaurant-booking'));
            }
        }

        ?>
        <div class="wrap">
            <h1>
                🍷 <?php echo $product ? __('Modifier la boisson soft', 'restaurant-booking') : __('Nouvelle boisson soft', 'restaurant-booking'); ?>
            </h1>
            
            <form method="post" action="" enctype="multipart/form-data">
                <?php wp_nonce_field('restaurant_booking_beverage_soft', 'beverage_soft_nonce'); ?>
                <input type="hidden" name="restaurant_booking_action" value="save_beverage_soft">
                <?php if ($product): ?>
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <?php endif; ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="product_name"><?php _e('Nom de la boisson', 'restaurant-booking'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" id="product_name" name="product_name" 
                                   value="<?php echo $product ? esc_attr($product['name']) : ''; ?>" 
                                   class="regular-text" required>
                            <p class="description"><?php _e('Ex: Coca-Cola, Jus d\'orange, Eau minérale...', 'restaurant-booking'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="product_description"><?php _e('Description', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <textarea id="product_description" name="product_description" 
                                      rows="3" class="large-text"><?php echo $product ? esc_textarea($product['description']) : ''; ?></textarea>
                            <p class="description"><?php _e('Description de la boisson (optionnel).', 'restaurant-booking'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="soft_image"><?php _e('Image de la boisson', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <input type="hidden" id="soft_image_id" name="soft_image_id" value="<?php echo $product ? esc_attr($product['image_id']) : ''; ?>">
                            <div id="soft_image_preview">
                                <?php if ($product && $product['image_id']): ?>
                                    <?php $image_url = wp_get_attachment_image_url($product['image_id'], 'medium'); ?>
                                    <?php if ($image_url): ?>
                                        <img src="<?php echo esc_url($image_url); ?>" alt="" style="max-width: 200px;">
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <p>
                                <button type="button" id="upload_soft_image" class="button"><?php _e('Choisir une image', 'restaurant-booking'); ?></button>
                                <button type="button" id="remove_soft_image" class="button" <?php echo (!$product || !$product['image_id']) ? 'style="display:none;"' : ''; ?>><?php _e('Supprimer l\'image', 'restaurant-booking'); ?></button>
                            </p>
                            <p class="description"><?php _e('Image principale de la boisson (recommandé 300x300px).', 'restaurant-booking'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label><?php _e('Contenances disponibles', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <div id="beverage_sizes_container">
                                <p class="description"><?php _e('Ajoutez les différentes tailles disponibles pour cette boisson (ex: 33cl, 1L)', 'restaurant-booking'); ?></p>
                                
                                <div id="sizes_list">
                                    <?php if ($product): ?>
                                        <?php $sizes = RestaurantBooking_Beverage_Size_Manager::get_product_sizes($product['id']); ?>
                                        <?php if (!empty($sizes)): ?>
                                            <?php foreach ($sizes as $size): ?>
                                                <div class="size-item" data-size-id="<?php echo $size->id; ?>">
                                                    <div class="size-info">
                                                        <h4><?php echo $size->size_cl; ?>cl (<?php echo $size->size_label; ?>) - <?php echo number_format($size->price, 2); ?>€</h4>
                                                        <?php if ($size->image_id): ?>
                                                            <div class="size-image">
                                                                <?php echo wp_get_attachment_image($size->image_id, 'thumbnail'); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if ($size->is_featured): ?>
                                                            <span class="featured-badge"><?php _e('Mise en avant', 'restaurant-booking'); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="size-actions">
                                                        <button type="button" class="button button-small edit-size" data-size-id="<?php echo $size->id; ?>" 
                                                                data-size-cl="<?php echo $size->size_cl; ?>"
                                                                data-size-label="<?php echo esc_attr($size->size_label); ?>"
                                                                data-price="<?php echo $size->price; ?>"
                                                                data-image-id="<?php echo $size->image_id; ?>"
                                                                data-is-featured="<?php echo $size->is_featured ? '1' : '0'; ?>">
                                                            <?php _e('Modifier', 'restaurant-booking'); ?>
                                                        </button>
                                                        <button type="button" class="button button-small delete-size" data-size-id="<?php echo $size->id; ?>">
                                                            <?php _e('Supprimer', 'restaurant-booking'); ?>
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="no-sizes-message">
                                                <p><em><?php _e('Aucune contenance configurée. Ajoutez au moins une contenance pour cette boisson.', 'restaurant-booking'); ?></em></p>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                
                                <button type="button" class="button button-secondary" id="add_size_button">
                                    <?php _e('+ Ajouter une contenance', 'restaurant-booking'); ?>
                                </button>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="is_active"><?php _e('Statut', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="is_active" name="is_active" value="1" 
                                       <?php checked($product['is_active'] ?? true); ?>>
                                <?php _e('Boisson active (visible dans les formulaires)', 'restaurant-booking'); ?>
                            </label>
                        </td>
                    </tr>
                </table>

                <?php submit_button($product ? __('Mettre à jour la boisson', 'restaurant-booking') : __('Créer la boisson', 'restaurant-booking')); ?>
            </form>
        </div>

        <!-- Modal pour ajouter/modifier une taille -->
        <div id="size_modal" style="display: none;">
            <div class="size-modal-content">
                <h3 id="size_modal_title"><?php _e('Ajouter une contenance', 'restaurant-booking'); ?></h3>
                <form id="size_form">
                    <table class="form-table">
                        <tr>
                            <th><label for="size_cl"><?php _e('Contenance', 'restaurant-booking'); ?> *</label></th>
                            <td>
                                <input type="number" id="size_cl" name="size_cl" min="1" class="small-text" required> cl
                                <p class="description"><?php _e('Contenance en centilitres', 'restaurant-booking'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="size_label"><?php _e('Libellé', 'restaurant-booking'); ?> *</label></th>
                            <td>
                                <input type="text" id="size_label" name="size_label" class="regular-text" required
                                       placeholder="<?php _e('Ex: Canette, Bouteille, Magnum...', 'restaurant-booking'); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="size_price"><?php _e('Prix', 'restaurant-booking'); ?> *</label></th>
                            <td>
                                <input type="number" id="size_price" name="size_price" step="0.01" min="0" class="small-text" required> €
                            </td>
                        </tr>
                        <tr>
                            <th><label for="size_image"><?php _e('Image', 'restaurant-booking'); ?></label></th>
                            <td>
                                <button type="button" class="button" id="upload_size_image_button">
                                    <?php _e('Choisir une image', 'restaurant-booking'); ?>
                                </button>
                                <input type="hidden" id="size_image_id" name="size_image_id">
                                <div id="size_image_preview" style="margin-top: 10px;"></div>
                                <p class="description"><?php _e('Image spécifique pour cette contenance', 'restaurant-booking'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="is_featured"><?php _e('Mise en avant', 'restaurant-booking'); ?></label></th>
                            <td>
                                <label>
                                    <input type="checkbox" id="is_featured" name="is_featured" value="1">
                                    <?php _e('Mettre en avant cette contenance', 'restaurant-booking'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" class="button-primary" value="<?php _e('Enregistrer', 'restaurant-booking'); ?>">
                        <button type="button" class="button" id="cancel_size"><?php _e('Annuler', 'restaurant-booking'); ?></button>
                    </p>
                </form>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            var mediaUploader, softImageUploader;
            
            // Sélecteur d'images WordPress pour l'image principale
            $('#upload_soft_image').on('click', function(e) {
                e.preventDefault();
                
                if (softImageUploader) {
                    softImageUploader.open();
                    return;
                }
                
                softImageUploader = wp.media({
                    title: '<?php _e('Choisir une image pour la boisson', 'restaurant-booking'); ?>',
                    button: {
                        text: '<?php _e('Utiliser cette image', 'restaurant-booking'); ?>'
                    },
                    multiple: false
                });
                
                softImageUploader.on('select', function() {
                    var attachment = softImageUploader.state().get('selection').first().toJSON();
                    $('#soft_image_id').val(attachment.id);
                    $('#soft_image_preview').html('<img src="' + attachment.sizes.medium.url + '" alt="" style="max-width: 200px;">');
                    $('#remove_soft_image').show();
                });
                
                softImageUploader.open();
            });
            
            $('#remove_soft_image').on('click', function(e) {
                e.preventDefault();
                $('#soft_image_id').val('');
                $('#soft_image_preview').empty();
                $(this).hide();
            });
            
            // Sélecteur d'images WordPress pour les tailles
            $('#upload_size_image_button').click(function(e) {
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
                    $('#size_image_id').val(attachment.id);
                    $('#size_image_preview').html('<img src="' + attachment.sizes.thumbnail.url + '" alt="" style="max-width: 100px;">');
                });
                
                mediaUploader.open();
            });
            
            
            var currentEditingSizeId = null; // Variable pour tracker l'édition
            
            // Ouvrir le modal en mode édition
            $(document).on('click', '.edit-size', function() {
                var sizeId = $(this).data('size-id');
                var sizeCl = $(this).data('size-cl');
                var sizeLabel = $(this).data('size-label');
                var price = $(this).data('price');
                var imageId = $(this).data('image-id');
                var isFeatured = $(this).data('is-featured');
                
                // Remplir le formulaire avec les données existantes
                $('#size_cl').val(sizeCl);
                $('#size_label').val(sizeLabel);
                $('#size_price').val(price);
                $('#size_image_id').val(imageId);
                $('#is_featured').prop('checked', isFeatured == '1');
                
                // Afficher l'image si elle existe
                if (imageId) {
                    // Récupérer l'image depuis l'élément existant
                    var existingImage = $(this).closest('.size-item').find('.size-image img');
                    if (existingImage.length > 0) {
                        $('#size_image_preview').html('<img src="' + existingImage.attr('src') + '" alt="" style="max-width: 100px;">');
                    }
                } else {
                    $('#size_image_preview').empty();
                }
                
                // Changer le titre du modal et tracker l'ID
                $('#size_modal_title').text('<?php _e('Modifier la contenance', 'restaurant-booking'); ?>');
                currentEditingSizeId = sizeId;
                
                $('#size_modal').show();
            });
            
            // Soumettre le formulaire de taille (ajout ou modification)
            $('#size_form').on('submit', function(e) {
                e.preventDefault();
                
                var isEdit = currentEditingSizeId !== null;
                var action = isEdit ? 'restaurant_update_beverage_size' : 'restaurant_add_beverage_size';
                
                var formData = {
                    action: action,
                    nonce: '<?php echo wp_create_nonce('restaurant_booking_admin'); ?>',
                    product_id: <?php echo $product ? $product['id'] : 'null'; ?>,
                    size_cl: $('#size_cl').val(),
                    size_label: $('#size_label').val(),
                    price: $('#size_price').val(),
                    image_id: $('#size_image_id').val(),
                    is_featured: $('#is_featured').is(':checked') ? 1 : 0
                };
                
                // Ajouter l'ID de la taille si on modifie
                if (isEdit) {
                    formData.size_id = currentEditingSizeId;
                }
                
                $.post(ajaxurl, formData, function(response) {
                    if (response.success) {
                        location.reload(); // Recharger pour voir les modifications
                    } else {
                        alert('Erreur: ' + response.data);
                    }
                }).fail(function() {
                    alert('Erreur de communication avec le serveur');
                });
            });
            
            // Supprimer une taille
            $(document).on('click', '.delete-size', function() {
                if (confirm('<?php _e('Êtes-vous sûr de vouloir supprimer cette contenance ?', 'restaurant-booking'); ?>')) {
                    var sizeId = $(this).data('size-id');
                    var $item = $(this).closest('.size-item');
                    
                    $.post(ajaxurl, {
                        action: 'restaurant_delete_beverage_size',
                        nonce: '<?php echo wp_create_nonce('restaurant_booking_admin'); ?>',
                        size_id: sizeId
                    }, function(response) {
                        if (response.success) {
                            $item.fadeOut(300, function() {
                                $(this).remove();
                                // Vérifier s'il reste des contenances
                                if ($('#sizes_list .size-item').length === 0) {
                                    $('#sizes_list').append('<div class="no-sizes-message"><p><em><?php _e('Aucune contenance configurée. Ajoutez au moins une contenance pour cette boisson.', 'restaurant-booking'); ?></em></p></div>');
                                }
                            });
                        } else {
                            alert('Erreur: ' + response.data);
                        }
                    }).fail(function() {
                        alert('Erreur de communication avec le serveur');
                    });
                }
            });
            
            // Réinitialiser le modal quand on l'ouvre pour ajouter
            $('#add_size_button').click(function() {
                $('#size_modal_title').text('<?php _e('Ajouter une contenance', 'restaurant-booking'); ?>');
                $('#size_form')[0].reset();
                $('#size_image_preview').empty();
                currentEditingSizeId = null;
                $('#size_modal').show();
            });
            
            // Fermer le modal
            $('#cancel_size').click(function() {
                $('#size_modal').hide();
                currentEditingSizeId = null;
            });
        });
        </script>
        
        <style>
        #size_modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .size-modal-content {
            background: white;
            padding: 20px;
            border-radius: 5px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        .size-item {
            border: 1px solid #ddd;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .size-info h4 {
            margin: 0 0 5px 0;
        }
        .size-image img {
            max-width: 50px;
            height: auto;
        }
        .featured-badge {
            background: #0073aa;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
        }
        .size-actions {
            display: flex;
            gap: 5px;
        }
        </style>
        
        <?php
    }
    
    /**
     * Gérer la sauvegarde d'une boisson soft
     */
    public function handle_save_soft()
    {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['beverage_soft_nonce'], 'restaurant_booking_beverage_soft')) {
            wp_die(__('Erreur de sécurité', 'restaurant-booking'));
        }

        // Récupérer les données
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $product_name = sanitize_text_field($_POST['product_name']);
        $product_description = sanitize_textarea_field($_POST['product_description']);
        $soft_image_id = isset($_POST['soft_image_id']) ? intval($_POST['soft_image_id']) : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        // Validation
        if (empty($product_name)) {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-soft&action=add&error=validation'));
            exit;
        }

        // Obtenir la catégorie
        $category = RestaurantBooking_Category::get_by_type('soft');
        if (!$category) {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-soft&action=add&error=no_category'));
            exit;
        }

        // Préparer les données du produit
        $product_data = array(
            'category_id' => $category['id'],
            'name' => $product_name,
            'description' => $product_description,
            'price' => 2.50, // Prix par défaut
            'unit_type' => 'piece',
            'unit_label' => '/bouteille',
            'volume_cl' => 33, // Volume par défaut
            'has_multiple_sizes' => 1, // Activer le système multi-tailles
            'image_id' => $soft_image_id ?: null,
            'is_active' => $is_active
        );

        if ($product_id) {
            // Mise à jour
            $result = RestaurantBooking_Product::update($product_id, $product_data);
            $success_param = $result ? 'updated' : 'error';
        } else {
            // Création
            $result = RestaurantBooking_Product::create($product_data);
            $success_param = $result ? 'created' : 'error';
        }

        // Redirection
        $redirect_url = admin_url('admin.php?page=restaurant-booking-beverages-soft&message=' . $success_param);
        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Obtenir les boissons soft depuis la base de données (nouveau système multi-tailles)
     */
    private function get_soft_beverages()
    {
        global $wpdb;

        // Récupérer les boissons avec leurs tailles (similaire aux fûts)
        $beverages = array();
        
        $products = $wpdb->get_results($wpdb->prepare("
            SELECT p.*, c.service_type
            FROM {$wpdb->prefix}restaurant_products p
            INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
            WHERE c.type = %s AND p.is_active = 1
            ORDER BY p.display_order ASC, p.name ASC
        ", 'soft'), ARRAY_A);

        foreach ($products as $product) {
            if ($product['has_multiple_sizes']) {
                // Nouveau système multi-contenances avec table dédiée
                $sizes = RestaurantBooking_Beverage_Size_Manager::get_product_sizes($product['id']);
                
                // Créer l'entrée principale de la boisson avec toutes ses contenances
                $beverage_data = array(
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'description' => $product['description'],
                    'image_id' => $product['image_id'],
                    'image_url' => $product['image_id'] ? wp_get_attachment_image_url($product['image_id'], 'thumbnail') : '',
                    'suggested_beverage' => (bool) $product['suggested_beverage'],
                    'is_active' => (bool) $product['is_active'],
                    'service_type' => $product['service_type'],
                    'has_multiple_sizes' => true,
                    'display_order' => (int) $product['display_order'],
                    'created_at' => $product['created_at'],
                    'sizes' => array()
                );
                
                if (!empty($sizes)) {
                    // Ajouter les contenances comme sous-éléments
                    foreach ($sizes as $size) {
                        $beverage_data['sizes'][] = array(
                            'size_id' => $size->id,
                            'size_cl' => (int) $size->size_cl,
                            'size_label' => $size->size_label,
                            'price' => (float) $size->price,
                            'image_id' => $size->image_id,
                            'image_url' => $size->image_id ? wp_get_attachment_image_url($size->image_id, 'thumbnail') : '',
                            'is_featured' => (bool) $size->is_featured,
                            'display_order' => (int) $size->display_order,
                            'is_active' => (bool) $size->is_active
                        );
                    }
                    
                    // Trier les contenances par ordre d'affichage puis par taille
                    usort($beverage_data['sizes'], function($a, $b) {
                        if ($a['display_order'] === $b['display_order']) {
                            return $a['size_cl'] - $b['size_cl'];
                        }
                        return $a['display_order'] - $b['display_order'];
                    });
                } else {
                    // Marquer comme nécessitant une configuration
                    $beverage_data['needs_configuration'] = true;
                }
                
                $beverages[] = $beverage_data;
            } else {
                // Ancien système (compatibilité) - traiter comme une boisson simple
                $beverages[] = array(
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'description' => $product['description'],
                    'image_id' => $product['image_id'],
                    'image_url' => $product['image_id'] ? wp_get_attachment_image_url($product['image_id'], 'thumbnail') : '',
                    'suggested_beverage' => (bool) $product['suggested_beverage'],
                    'is_active' => (bool) $product['is_active'],
                    'service_type' => $product['service_type'],
                    'has_multiple_sizes' => false,
                    'display_order' => (int) $product['display_order'],
                    'created_at' => $product['created_at'],
                    'sizes' => array(),
                    // Pour compatibilité avec l'ancien affichage
                    'legacy_data' => array(
                        'size_label' => $product['volume_cl'] . 'cl',
                        'size_cl' => (int) $product['volume_cl'],
                        'price' => (float) $product['price']
                    )
                );
            }
        }

        return $beverages;
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
                $this->delete_soft_beverage($product_id);
                break;
        }
    }

    /**
     * Supprimer une boisson soft
     */
    private function delete_soft_beverage($product_id)
    {
        if (!wp_verify_nonce($_GET['_wpnonce'], 'delete_soft_beverage_' . $product_id)) {
            wp_die(__('Action non autorisée.', 'restaurant-booking'));
        }

        if (!current_user_can('manage_restaurant_quotes')) {
            wp_die(__('Permissions insuffisantes.', 'restaurant-booking'));
        }

        $result = RestaurantBooking_Product::delete($product_id);
        
        if (is_wp_error($result)) {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-soft&message=error&error=' . urlencode($result->get_error_message())));
        } else {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-soft&message=deleted'));
        }
        exit;
    }
}
