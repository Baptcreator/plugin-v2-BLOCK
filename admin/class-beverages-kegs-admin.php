<?php
/**
 * Classe d'administration des Fûts de Bière
 *
 * @package RestaurantBooking
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Beverages_Kegs_Admin
{
    /**
     * Afficher la liste des fûts de bière
     */
    public function display_list()
    {
        // Gérer les actions (suppression, etc.)
        $this->handle_actions();
        
        $products = $this->get_kegs();
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">🍺 <?php _e('Fûts de Bière', 'restaurant-booking'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-kegs&action=add'); ?>" class="page-title-action">
                <?php _e('Ajouter un fût', 'restaurant-booking'); ?>
            </a>
            <hr class="wp-header-end">

            <div class="restaurant-booking-info-card">
                <h3><?php _e('Système de fûts multi-contenances', 'restaurant-booking'); ?></h3>
                <ul>
                    <li><?php _e('✓ Différentes contenances par type de bière (10L, 20L)', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Prix spécifiques par contenance', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Images différentes par taille de fût', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Système de mise en avant par contenance', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Exemple: IPA → 10L (30€) + 20L (50€)', 'restaurant-booking'); ?></li>
                </ul>
            </div>

            <!-- Statistiques rapides -->
            <div class="restaurant-booking-stats">
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><?php echo count($products); ?></h3>
                        <p><?php _e('Fûts disponibles', 'restaurant-booking'); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo count(array_filter($products, function($p) { return $p['is_active']; })); ?></h3>
                        <p><?php _e('Actifs', 'restaurant-booking'); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>
                            <?php 
                            $total_sizes = 0;
                            foreach ($products as $keg) {
                                if ($keg['has_multiple_sizes'] && !empty($keg['sizes'])) {
                                    $total_sizes += count($keg['sizes']);
                                } else {
                                    $total_sizes += 1; // Ancien système ou sans configuration
                                }
                            }
                            echo $total_sizes;
                            ?>
                        </h3>
                        <p><?php _e('Contenances totales', 'restaurant-booking'); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>
                            <?php 
                            $all_prices = array();
                            foreach ($products as $keg) {
                                if ($keg['has_multiple_sizes'] && !empty($keg['sizes'])) {
                                    foreach ($keg['sizes'] as $size) {
                                        $all_prices[] = $size['price'];
                                    }
                                } elseif (isset($keg['legacy_data'])) {
                                    $all_prices[] = $keg['legacy_data']['price'];
                                }
                            }
                            echo $all_prices ? number_format(array_sum($all_prices) / count($all_prices), 0, ',', ' ') : '0';
                            ?> €
                        </h3>
                        <p><?php _e('Prix moyen', 'restaurant-booking'); ?></p>
                    </div>
                </div>
            </div>

            <form method="post" id="kegs-filter">
                <?php wp_nonce_field('restaurant_booking_kegs_action'); ?>
                
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

                <!-- Tableau des fûts -->
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <input id="cb-select-all-1" type="checkbox">
                            </td>
                            <th scope="col" class="manage-column column-image"><?php _e('Image', 'restaurant-booking'); ?></th>
                            <th scope="col" class="manage-column column-name column-primary"><?php _e('Nom', 'restaurant-booking'); ?></th>
                            <th scope="col" class="manage-column column-description"><?php _e('Description', 'restaurant-booking'); ?></th>
                            <th scope="col" class="manage-column column-type"><?php _e('Type', 'restaurant-booking'); ?></th>
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
                                <td class="colspanchange" colspan="11">
                                    <?php _e('Aucun fût de bière trouvé.', 'restaurant-booking'); ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $keg): ?>
                                <!-- Ligne principale du fût -->
                                <tr class="keg-main-row" data-keg-id="<?php echo $keg['id']; ?>">
                                    <th scope="row" class="check-column">
                                        <input id="cb-select-<?php echo $keg['id']; ?>" type="checkbox" name="keg_ids[]" value="<?php echo $keg['id']; ?>">
                                    </th>
                                    <td class="column-image">
                                        <?php if (!empty($keg['image_url'])): ?>
                                            <img src="<?php echo esc_url($keg['image_url']); ?>" alt="<?php echo esc_attr($keg['name']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                        <?php else: ?>
                                            <div style="width: 50px; height: 50px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #666;">
                                                <span class="dashicons dashicons-format-image"></span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="column-name column-primary">
                                        <strong>
                                            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-kegs&action=edit&product_id=' . $keg['id']); ?>">
                                                🍺 <?php echo esc_html($keg['name']); ?>
                                            </a>
                                        </strong>
                                        <?php if ($keg['has_multiple_sizes'] && !empty($keg['sizes'])): ?>
                                            <small class="keg-sizes-info">(<?php echo count($keg['sizes']); ?> contenances disponibles)</small>
                                        <?php elseif (isset($keg['needs_configuration'])): ?>
                                            <small class="keg-needs-config" style="color: #d63638;">(Configuration requise)</small>
                                        <?php endif; ?>
                                        <div class="row-actions">
                                            <span class="edit">
                                                <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-kegs&action=edit&product_id=' . $keg['id']); ?>">
                                                    <?php _e('Modifier', 'restaurant-booking'); ?>
                                                </a> |
                                            </span>
                                            <?php if ($keg['has_multiple_sizes'] && !empty($keg['sizes'])): ?>
                                                <span class="toggle-sizes">
                                                    <a href="#" class="toggle-keg-sizes" data-keg-id="<?php echo $keg['id']; ?>">
                                                        <?php _e('Voir contenances', 'restaurant-booking'); ?>
                                                    </a> |
                                                </span>
                                            <?php endif; ?>
                                            <span class="toggle-status">
                                                <a href="#" class="toggle-keg-status" data-product-id="<?php echo $keg['id']; ?>" data-current-status="<?php echo $keg['is_active'] ? 1 : 0; ?>">
                                                    <?php echo $keg['is_active'] ? __('Désactiver', 'restaurant-booking') : __('Activer', 'restaurant-booking'); ?>
                                                </a> |
                                            </span>
                                            <span class="delete">
                                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=restaurant-booking-beverages-kegs&action=delete&product_id=' . $keg['id']), 'delete_keg_' . $keg['id']); ?>" 
                                                   class="button button-small button-link-delete" 
                                                   onclick="return confirm('<?php _e('Êtes-vous sûr de vouloir supprimer ce fût ?', 'restaurant-booking'); ?>')">
                                                    <?php _e('Supprimer', 'restaurant-booking'); ?>
                                                </a>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="column-description">
                                        <?php echo esc_html(wp_trim_words($keg['description'] ?? '', 10)); ?>
                                    </td>
                                    <td class="column-type">
                                        <span class="beer-category-badge">
                                            <?php echo esc_html($keg['beer_category'] ?: 'Non classée'); ?>
                                        </span>
                                        <?php if ($keg['alcohol_degree']): ?>
                                            <br><small><?php echo $keg['alcohol_degree']; ?>°</small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="column-size">
                                        <?php if ($keg['has_multiple_sizes'] && !empty($keg['sizes'])): ?>
                                            <strong><?php echo count($keg['sizes']); ?> tailles</strong>
                                            <br><small>
                                                <?php 
                                                $size_labels = array_map(function($size) { return $size['size_label']; }, $keg['sizes']);
                                                echo esc_html(implode(', ', $size_labels)); 
                                                ?>
                                            </small>
                                        <?php elseif (isset($keg['legacy_data'])): ?>
                                            <strong><?php echo $keg['legacy_data']['size_label']; ?></strong>
                                            <br><small>Fût <?php echo $keg['legacy_data']['size_label']; ?></small>
                                        <?php else: ?>
                                            <span style="color: #d63638;">Non configuré</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="column-price">
                                        <?php if ($keg['has_multiple_sizes'] && !empty($keg['sizes'])): ?>
                                            <?php 
                                            $prices = array_map(function($size) { return $size['price']; }, $keg['sizes']);
                                            $min_price = min($prices);
                                            $max_price = max($prices);
                                            ?>
                                            <?php if ($min_price === $max_price): ?>
                                                <strong><?php echo number_format($min_price, 0, ',', ' '); ?> €</strong>
                                            <?php else: ?>
                                                <strong><?php echo number_format($min_price, 0, ',', ' '); ?> - <?php echo number_format($max_price, 0, ',', ' '); ?> €</strong>
                                            <?php endif; ?>
                                        <?php elseif (isset($keg['legacy_data'])): ?>
                                            <strong><?php echo number_format($keg['legacy_data']['price'], 0, ',', ' '); ?> €</strong>
                                        <?php else: ?>
                                            <span style="color: #d63638;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="column-suggestion">
                                        <?php if ($keg['suggested_beverage']): ?>
                                            <span class="dashicons dashicons-star-filled" style="color: #ffb900;" title="<?php _e('En suggestion', 'restaurant-booking'); ?>"></span>
                                            <small><?php _e('Oui', 'restaurant-booking'); ?></small>
                                        <?php else: ?>
                                            <span class="dashicons dashicons-star-empty" style="color: #ddd;"></span>
                                            <small><?php _e('Non', 'restaurant-booking'); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="column-order">
                                        <input type="number" class="small-text keg-order-input" 
                                               value="<?php echo $keg['display_order'] ?? 0; ?>" 
                                               data-product-id="<?php echo $keg['id']; ?>"
                                               min="0" max="999">
                                    </td>
                                    <td class="column-status">
                                        <?php if ($keg['is_active']): ?>
                                            <span class="status-active"><?php _e('Actif', 'restaurant-booking'); ?></span>
                                        <?php else: ?>
                                            <span class="status-inactive"><?php _e('Inactif', 'restaurant-booking'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="column-date">
                                        <?php echo date_i18n(get_option('date_format'), strtotime($keg['created_at'] ?? 'now')); ?>
                                    </td>
                                </tr>

                                <!-- Lignes des contenances (cachées par défaut) -->
                                <?php if ($keg['has_multiple_sizes'] && !empty($keg['sizes'])): ?>
                                    <?php foreach ($keg['sizes'] as $size): ?>
                                        <tr class="keg-size-row" data-keg-id="<?php echo $keg['id']; ?>" style="display: none;">
                                            <td class="check-column"></td>
                                            <td class="column-image">
                                                <?php if (!empty($size['image_url'])): ?>
                                                    <img src="<?php echo esc_url($size['image_url']); ?>" alt="<?php echo esc_attr($keg['name'] . ' ' . $size['size_label']); ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; opacity: 0.8;">
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
                                                        <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-kegs&action=edit&product_id=' . $keg['id'] . '#size-' . $size['size_id']); ?>">
                                                            <?php _e('Modifier cette taille', 'restaurant-booking'); ?>
                                                        </a>
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="column-description">
                                                <small style="color: #666;">Contenance: <?php echo $size['liters']; ?> litres</small>
                                            </td>
                                            <td class="column-type">
                                                <small style="color: #666;">—</small>
                                            </td>
                                            <td class="column-size">
                                                <strong><?php echo $size['size_label']; ?></strong>
                                            </td>
                                            <td class="column-price">
                                                <strong><?php echo number_format($size['price'], 0, ',', ' '); ?> €</strong>
                                            </td>
                                            <td class="column-suggestion">
                                                <?php if ($size['is_featured']): ?>
                                                    <span class="dashicons dashicons-star-filled" style="color: #ffb900;" title="<?php _e('Taille mise en avant', 'restaurant-booking'); ?>"></span>
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
        .restaurant-booking-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .stats-grid {
            display: contents;
        }
        
        .stat-card {
            background: #fff;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        
        .stat-card h3 {
            font-size: 2em;
            margin: 0 0 10px 0;
            color: #1d2327;
        }
        
        .stat-card p {
            margin: 0;
            color: #646970;
            font-weight: 500;
        }
        
        .restaurant-booking-info-card {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }
        
        .restaurant-booking-info-card h3 {
            margin-top: 0;
            color: #0c5460;
        }
        
        .restaurant-booking-info-card ul {
            margin-bottom: 0;
        }
        
        .product-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .product-thumb {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .beer-category-badge {
            background: #f0f6fc;
            border: 1px solid #0969da;
            color: #0969da;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-active { color: #46b450; font-weight: 600; }
        .status-inactive { color: #dc3232; font-weight: 600; }
        .keg-order-input { width: 60px; }
        .column-image { width: 70px; }
        .column-price { width: 100px; text-align: center; }
        .column-type { width: 100px; }
        .column-size { width: 100px; }
        .column-suggestion { width: 100px; }
        .column-order { width: 80px; }
        .column-status { width: 80px; }
        .column-date { width: 120px; }
        
        /* Styles pour l'affichage hiérarchique des fûts */
        .keg-main-row {
            background-color: #fff;
        }
        
        .keg-main-row:hover {
            background-color: #f6f7f7;
        }
        
        .keg-size-row {
            background-color: #f9f9f9;
            border-left: 3px solid #0073aa;
        }
        
        .keg-size-row:hover {
            background-color: #f0f0f1;
        }
        
        .keg-sizes-info {
            color: #0073aa;
            font-weight: 500;
        }
        
        .keg-needs-config {
            background: #fff2f2;
            padding: 2px 6px;
            border-radius: 3px;
            border: 1px solid #ffb2b2;
        }
        
        .toggle-keg-sizes {
            color: #0073aa;
            text-decoration: none;
        }
        
        .toggle-keg-sizes:hover {
            color: #005a87;
        }
        
        .toggle-keg-sizes.expanded:after {
            content: ' ▲';
        }
        
        .toggle-keg-sizes:not(.expanded):after {
            content: ' ▼';
        }
        </style>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Gérer l'affichage/masquage des contenances
            $('.toggle-keg-sizes').on('click', function(e) {
                e.preventDefault();
                
                var kegId = $(this).data('keg-id');
                var sizeRows = $('.keg-size-row[data-keg-id="' + kegId + '"]');
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
            $('.keg-main-row').on('click', function(e) {
                // Ne pas déclencher si on clique sur un lien ou un input
                if ($(e.target).is('a, input, button') || $(e.target).closest('a, input, button').length > 0) {
                    return;
                }
                
                var kegId = $(this).data('keg-id');
                var toggleLink = $('.toggle-keg-sizes[data-keg-id="' + kegId + '"]');
                
                if (toggleLink.length > 0) {
                    toggleLink.trigger('click');
                }
            });
            
            // Ajouter un curseur pointer sur les lignes cliquables
            $('.keg-main-row').each(function() {
                var kegId = $(this).data('keg-id');
                var hasToggle = $('.toggle-keg-sizes[data-keg-id="' + kegId + '"]').length > 0;
                
                if (hasToggle) {
                    $(this).css('cursor', 'pointer').attr('title', 'Cliquer pour voir les contenances');
                }
            });
        });
        </script>
        
        <?php
    }

    /**
     * Afficher le formulaire d'ajout/modification de fût
     */
    public function display_form()
    {
        // Charger les scripts de la médiathèque WordPress
        wp_enqueue_media();
        
        $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'add';
        $product = null;

        if ($product_id && $action === 'edit') {
            $product = RestaurantBooking_Product::get($product_id);
            if (!$product || $product['category_type'] !== 'fut') {
                wp_die(__('Produit introuvable ou type incorrect.', 'restaurant-booking'));
            }
        }

        // Gérer la soumission du formulaire
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handle_save_keg();
            return;
        }

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                🍺 <?php echo $action === 'edit' ? __('Modifier le fût', 'restaurant-booking') : __('Ajouter un fût', 'restaurant-booking'); ?>
            </h1>
            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-kegs'); ?>" class="page-title-action">
                ← <?php _e('Retour à la liste', 'restaurant-booking'); ?>
            </a>
            <hr class="wp-header-end">

            <div class="restaurant-booking-info-card">
                <h3><?php _e('Informations sur les fûts de bière', 'restaurant-booking'); ?></h3>
                <ul>
                    <li><?php _e('✓ Les fûts sont vendus complets (prix par fût)', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Matériel de service généralement inclus', 'restaurant-booking'); ?></li>
                                    <li><?php _e('✓ Contenances standard : 10L, 20L, 30L, 50L', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Parfait pour les événements et grandes réceptions', 'restaurant-booking'); ?></li>
                </ul>
            </div>

            <form method="post" action="" id="keg-form">
                <?php wp_nonce_field('save_keg', 'keg_nonce'); ?>
                <input type="hidden" name="action" value="save_keg">
                <?php if ($product): ?>
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <?php endif; ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="keg_name"><?php _e('Nom du fût', 'restaurant-booking'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" id="keg_name" name="keg_name" class="regular-text" 
                                   value="<?php echo $product ? esc_attr($product['name']) : ''; ?>" required>
                            <p class="description"><?php _e('Ex: IPA, Blanche, Blonde... (les contenances seront ajoutées séparément)', 'restaurant-booking'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="keg_description"><?php _e('Description', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <textarea id="keg_description" name="keg_description" class="large-text" rows="3"><?php echo $product ? esc_textarea($product['description']) : ''; ?></textarea>
                            <p class="description"><?php _e('Description du fût et conditions de service', 'restaurant-booking'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="beer_category"><?php _e('Type de bière', 'restaurant-booking'); ?> *</label>
                        </th>
                        <td>
                            <select id="beer_category" name="beer_category" class="regular-text" required>
                                <option value=""><?php _e('Sélectionner un type', 'restaurant-booking'); ?></option>
                                <?php 
                                $beer_types = $this->get_beer_types();
                                foreach ($beer_types as $type): ?>
                                    <option value="<?php echo esc_attr($type['category']); ?>" <?php selected($product['beer_category'] ?? '', $type['category']); ?>>
                                        <?php echo esc_html($type['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('Sélectionnez le type de bière. Vous pouvez ajouter de nouveaux types ci-dessous.', 'restaurant-booking'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label><?php _e('Gérer les types', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=beers_group'); ?>" 
                               class="button button-secondary" target="_blank">
                                🍺 <?php _e('Gérer les types de bières', 'restaurant-booking'); ?>
                            </a>
                            <p class="description"><?php _e('Ajoutez, modifiez ou supprimez les types de bières disponibles dans la liste ci-dessus.', 'restaurant-booking'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="alcohol_degree"><?php _e('Degré d\'alcool', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="alcohol_degree" name="alcohol_degree" step="0.1" min="0" max="15" 
                                   value="<?php echo $product ? esc_attr($product['alcohol_degree']) : '5.0'; ?>">
                            <span>°</span>
                            <p class="description"><?php _e('Degré d\'alcool en pourcentage (optionnel)', 'restaurant-booking'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label><?php _e('Contenances disponibles', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <div id="keg_sizes_container">
                                <p class="description"><?php _e('Ajoutez les différentes contenances disponibles pour ce fût (ex: 10L, 20L)', 'restaurant-booking'); ?></p>
                                
                                <div id="keg_sizes_list">
                                    <?php if ($product): ?>
                                        <?php 
                                        // Charger les contenances existantes
                                        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-keg-size-manager.php';
                                        $existing_sizes = RestaurantBooking_Keg_Size_Manager::get_sizes_by_product($product['id']);
                                        
                                        if (!empty($existing_sizes)):
                                            foreach ($existing_sizes as $index => $size):
                                        ?>
                                            <div class="keg-size-item" data-size-id="<?php echo $index; ?>">
                                                <div class="keg-size-info">
                                                    <h4><?php echo $size['liters']; ?>L - <?php echo number_format($size['price'], 2); ?>€</h4>
                                                    <?php if ($size['image_id']): ?>
                                                        <div class="keg-size-image">
                                                            <?php echo wp_get_attachment_image($size['image_id'], 'thumbnail'); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if ($size['is_featured']): ?>
                                                        <span class="featured-badge"><?php _e('Mise en avant', 'restaurant-booking'); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="keg-size-actions">
                                                    <button type="button" class="button button-small delete-keg-size" data-size-id="<?php echo $index; ?>">
                                                        <?php _e('Supprimer', 'restaurant-booking'); ?>
                                                    </button>
                                                </div>
                                                <!-- Champs cachés -->
                                                <input type="hidden" name="keg_sizes[<?php echo $index; ?>][liters]" value="<?php echo $size['liters']; ?>">
                                                <input type="hidden" name="keg_sizes[<?php echo $index; ?>][price]" value="<?php echo $size['price']; ?>">
                                                <input type="hidden" name="keg_sizes[<?php echo $index; ?>][image_id]" value="<?php echo $size['image_id']; ?>">
                                                <input type="hidden" name="keg_sizes[<?php echo $index; ?>][is_featured]" value="<?php echo $size['is_featured'] ? '1' : '0'; ?>">
                                            </div>
                                        <?php 
                                            endforeach;
                                        endif;
                                        ?>
                                    <?php endif; ?>
                                </div>
                                
                                <button type="button" class="button button-secondary" id="add_keg_size_button">
                                    <?php _e('+ Ajouter une contenance', 'restaurant-booking'); ?>
                                </button>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="keg_image"><?php _e('Image du fût', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <div class="image-upload-container">
                                <input type="hidden" id="keg_image_id" name="keg_image_id" 
                                       value="<?php echo $product ? esc_attr($product['image_id']) : ''; ?>">
                                <div id="keg_image_preview">
                                    <?php if ($product && $product['image_id']): ?>
                                        <?php echo wp_get_attachment_image($product['image_id'], 'medium'); ?>
                                    <?php endif; ?>
                                </div>
                                <p>
                                    <button type="button" class="button" id="upload_keg_image">
                                        <?php _e('Choisir une image', 'restaurant-booking'); ?>
                                    </button>
                                    <button type="button" class="button" id="remove_keg_image" style="<?php echo (!$product || !$product['image_id']) ? 'display:none;' : ''; ?>">
                                        <?php _e('Supprimer', 'restaurant-booking'); ?>
                                    </button>
                                </p>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Options', 'restaurant-booking'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="suggested_beverage" value="1" 
                                           <?php checked($product['suggested_beverage'] ?? false); ?>>
                                    <?php _e('Marquer comme "suggestion"', 'restaurant-booking'); ?>
                                </label>
                                <p class="description"><?php _e('Les suggestions apparaissent en premier dans la liste', 'restaurant-booking'); ?></p>
                            </fieldset>
                        </td>
                    </tr>
                </table>

                <?php submit_button($action === 'edit' ? __('Mettre à jour le fût', 'restaurant-booking') : __('Ajouter le fût', 'restaurant-booking')); ?>
            </form>
        </div>

        <!-- Modal pour ajouter/modifier une contenance -->
        <div id="keg_size_modal" style="display: none;">
            <div class="keg-size-modal-content">
                <h3 id="keg_size_modal_title"><?php _e('Ajouter une contenance', 'restaurant-booking'); ?></h3>
                <form id="keg_size_form">
                    <table class="form-table">
                        <tr>
                            <th><label for="keg_size_liters"><?php _e('Contenance', 'restaurant-booking'); ?> *</label></th>
                            <td>
                                <select id="keg_size_liters" name="keg_size_liters" class="regular-text" required>
                                    <?php 
                                    // Charger les contenances disponibles
                                    require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-container-manager.php';
                                    echo RestaurantBooking_Container_Manager::get_containers_as_options();
                                    ?>
                                </select>
                                <p class="description">
                                    <?php _e('Contenances disponibles.', 'restaurant-booking'); ?>
                                    <br>
                                    <button type="button" class="button button-secondary" id="add_new_container_button" style="margin-top: 5px;">
                                        <?php _e('+ Ajouter une nouvelle contenance', 'restaurant-booking'); ?>
                                    </button>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="keg_size_price"><?php _e('Prix', 'restaurant-booking'); ?> *</label></th>
                            <td>
                                <input type="number" id="keg_size_price" name="keg_size_price" step="0.01" min="0" class="small-text" required> €
                                <p class="description"><?php _e('Prix pour cette contenance de fût', 'restaurant-booking'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="keg_size_image"><?php _e('Image', 'restaurant-booking'); ?></label></th>
                            <td>
                                <button type="button" class="button" id="upload_keg_size_image_button">
                                    <?php _e('Choisir une image', 'restaurant-booking'); ?>
                                </button>
                                <input type="hidden" id="keg_size_image_id" name="keg_size_image_id">
                                <div id="keg_size_image_preview" style="margin-top: 10px;"></div>
                                <p class="description"><?php _e('Image spécifique pour cette contenance', 'restaurant-booking'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="keg_is_featured"><?php _e('Mise en avant', 'restaurant-booking'); ?></label></th>
                            <td>
                                <label>
                                    <input type="checkbox" id="keg_is_featured" name="keg_is_featured" value="1">
                                    <?php _e('Mettre en avant cette contenance', 'restaurant-booking'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" class="button-primary" value="<?php _e('Enregistrer', 'restaurant-booking'); ?>">
                        <button type="button" class="button" id="cancel_keg_size"><?php _e('Annuler', 'restaurant-booking'); ?></button>
                    </p>
                </form>
            </div>
        </div>

        <!-- Modal pour ajouter une nouvelle contenance disponible -->
        <div id="new_container_modal" style="display: none;">
            <div class="keg-size-modal-content">
                <h3><?php _e('Ajouter une nouvelle contenance', 'restaurant-booking'); ?></h3>
                <form id="new_container_form">
                    <table class="form-table">
                        <tr>
                            <th><label for="new_container_liters"><?php _e('Contenance (litres)', 'restaurant-booking'); ?> *</label></th>
                            <td>
                                <input type="number" id="new_container_liters" name="new_container_liters" class="regular-text" min="1" max="1000" step="1" required>
                                <p class="description"><?php _e('Ex: 15, 25, 40...', 'restaurant-booking'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="new_container_label"><?php _e('Libellé', 'restaurant-booking'); ?> *</label></th>
                            <td>
                                <input type="text" id="new_container_label" name="new_container_label" class="regular-text" required>
                                <p class="description"><?php _e('Ex: "15L", "25 Litres"...', 'restaurant-booking'); ?></p>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" class="button-primary" value="<?php _e('Créer la contenance', 'restaurant-booking'); ?>">
                        <button type="button" class="button" id="cancel_new_container"><?php _e('Annuler', 'restaurant-booking'); ?></button>
                    </p>
                </form>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Gestionnaire pour l'upload d'image principale
            var mediaUploader;
            var kegSizeMediaUploader;
            var kegSizeCounter = $('#keg_sizes_list .keg-size-item').length;
            
            $('#upload_keg_image').on('click', function(e) {
                e.preventDefault();
                
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                
                mediaUploader = wp.media({
                    title: '<?php _e('Choisir une image pour le fût', 'restaurant-booking'); ?>',
                    button: {
                        text: '<?php _e('Utiliser cette image', 'restaurant-booking'); ?>'
                    },
                    multiple: false
                });
                
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#keg_image_id').val(attachment.id);
                    $('#keg_image_preview').html('<img src="' + attachment.sizes.medium.url + '" alt="" style="max-width: 200px;">');
                    $('#remove_keg_image').show();
                });
                
                mediaUploader.open();
            });
            
            $('#remove_keg_image').on('click', function(e) {
                e.preventDefault();
                $('#keg_image_id').val('');
                $('#keg_image_preview').empty();
                $(this).hide();
            });
            
            // Sélecteur d'images WordPress pour les contenances
            $('#upload_keg_size_image_button').click(function(e) {
                e.preventDefault();
                
                if (kegSizeMediaUploader) {
                    kegSizeMediaUploader.open();
                    return;
                }
                
                kegSizeMediaUploader = wp.media({
                    title: '<?php _e('Choisir une image', 'restaurant-booking'); ?>',
                    button: {
                        text: '<?php _e('Utiliser cette image', 'restaurant-booking'); ?>'
                    },
                    multiple: false
                });
                
                kegSizeMediaUploader.on('select', function() {
                    var attachment = kegSizeMediaUploader.state().get('selection').first().toJSON();
                    $('#keg_size_image_id').val(attachment.id);
                    $('#keg_size_image_preview').html('<img src="' + attachment.sizes.thumbnail.url + '" alt="" style="max-width: 100px;">');
                });
                
                kegSizeMediaUploader.open();
            });
            
            // Gestion des contenances de fûts
            $('#add_keg_size_button').click(function() {
                $('#keg_size_modal_title').text('<?php _e('Ajouter une contenance', 'restaurant-booking'); ?>');
                $('#keg_size_form')[0].reset();
                $('#keg_size_image_preview').empty();
                $('#keg_size_modal').show();
            });
            
            $('#cancel_keg_size').click(function() {
                $('#keg_size_modal').hide();
            });
            
            // Soumettre le formulaire de contenance
            $('#keg_size_form').on('submit', function(e) {
                e.preventDefault();
                
                var kegSizeLiters = $('#keg_size_liters').val();
                var kegSizePrice = $('#keg_size_price').val();
                var kegSizeImageId = $('#keg_size_image_id').val();
                var kegIsFeatured = $('#keg_is_featured').is(':checked');
                
                if (!kegSizeLiters || !kegSizePrice) {
                    alert('<?php _e('Veuillez remplir tous les champs obligatoires.', 'restaurant-booking'); ?>');
                    return;
                }
                
                // Vérifier si cette contenance existe déjà
                var exists = false;
                $('#keg_sizes_list .keg-size-item').each(function() {
                    if ($(this).find('input[name$="[liters]"]').val() == kegSizeLiters) {
                        exists = true;
                        return false;
                    }
                });
                
                if (exists) {
                    alert('<?php _e('Cette contenance existe déjà.', 'restaurant-booking'); ?>');
                    return;
                }
                
                // Ajouter la contenance à la liste
                var kegSizeHtml = '<div class="keg-size-item" data-size-id="' + kegSizeCounter + '">';
                kegSizeHtml += '<div class="keg-size-info">';
                kegSizeHtml += '<h4>' + kegSizeLiters + 'L - ' + parseFloat(kegSizePrice).toFixed(2) + '€</h4>';
                
                if (kegSizeImageId) {
                    kegSizeHtml += '<div class="keg-size-image">';
                    kegSizeHtml += $('#keg_size_image_preview').html();
                    kegSizeHtml += '</div>';
                }
                
                if (kegIsFeatured) {
                    kegSizeHtml += '<span class="featured-badge"><?php _e('Mise en avant', 'restaurant-booking'); ?></span>';
                }
                
                kegSizeHtml += '</div>';
                kegSizeHtml += '<div class="keg-size-actions">';
                kegSizeHtml += '<button type="button" class="button button-small delete-keg-size" data-size-id="' + kegSizeCounter + '">';
                kegSizeHtml += '<?php _e('Supprimer', 'restaurant-booking'); ?>';
                kegSizeHtml += '</button>';
                kegSizeHtml += '</div>';
                
                // Champs cachés
                kegSizeHtml += '<input type="hidden" name="keg_sizes[' + kegSizeCounter + '][liters]" value="' + kegSizeLiters + '">';
                kegSizeHtml += '<input type="hidden" name="keg_sizes[' + kegSizeCounter + '][price]" value="' + kegSizePrice + '">';
                kegSizeHtml += '<input type="hidden" name="keg_sizes[' + kegSizeCounter + '][image_id]" value="' + kegSizeImageId + '">';
                kegSizeHtml += '<input type="hidden" name="keg_sizes[' + kegSizeCounter + '][is_featured]" value="' + (kegIsFeatured ? '1' : '0') + '">';
                
                kegSizeHtml += '</div>';
                
                $('#keg_sizes_list').append(kegSizeHtml);
                kegSizeCounter++;
                
                $('#keg_size_modal').hide();
            });
            
            // Supprimer une contenance
            $(document).on('click', '.delete-keg-size', function() {
                if (confirm('<?php _e('Êtes-vous sûr de vouloir supprimer cette contenance ?', 'restaurant-booking'); ?>')) {
                    $(this).closest('.keg-size-item').remove();
                }
            });

            // Gestionnaire pour les types de bières
            $('#new_beer_type').on('input', function() {
                var newTypeValue = $(this).val().trim();
                if (newTypeValue.length > 0) {
                    $('#beer_category').prop('disabled', true).val('');
                    $('#beer_category').after('<p class="description" style="color: #d63638;"><em><?php _e('Le nouveau type sera utilisé à la place de la sélection.', 'restaurant-booking'); ?></em></p>');
                } else {
                    $('#beer_category').prop('disabled', false);
                    $('#beer_category').next('p').remove();
                }
            });

            $('#beer_category').on('change', function() {
                if ($(this).val()) {
                    $('#new_beer_type').val('');
                }
            });

            // === GESTION DES NOUVELLES CONTENANCES ===
            
            // Ouvrir la modal pour ajouter une nouvelle contenance
            $('#add_new_container_button').click(function() {
                $('#new_container_modal').show();
                $('#new_container_liters').focus();
            });
            
            // Fermer la modal
            $('#cancel_new_container').click(function() {
                $('#new_container_modal').hide();
                $('#new_container_form')[0].reset();
            });
            
            // Auto-générer le libellé
            $('#new_container_liters').on('input', function() {
                var liters = $(this).val();
                if (liters && $('#new_container_label').val() === '') {
                    $('#new_container_label').val(liters + 'L');
                }
            });
            
            // Soumettre le formulaire de nouvelle contenance
            $('#new_container_form').on('submit', function(e) {
                e.preventDefault();
                
                var liters = $('#new_container_liters').val();
                var label = $('#new_container_label').val();
                
                if (!liters || !label) {
                    alert('<?php _e('Veuillez remplir tous les champs.', 'restaurant-booking'); ?>');
                    return;
                }
                
                // Vérifier si cette contenance existe déjà
                var exists = false;
                $('#keg_size_liters option').each(function() {
                    if ($(this).val() == liters) {
                        exists = true;
                        return false;
                    }
                });
                
                if (exists) {
                    alert('<?php _e('Cette contenance existe déjà.', 'restaurant-booking'); ?>');
                    return;
                }
                
                // Créer la nouvelle contenance via AJAX
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'create_new_container',
                        liters: liters,
                        label: label,
                        nonce: '<?php echo wp_create_nonce('create_new_container'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Ajouter la nouvelle option au select
                            $('#keg_size_liters').append('<option value="' + liters + '">' + label + '</option>');
                            
                            // Sélectionner la nouvelle contenance
                            $('#keg_size_liters').val(liters);
                            
                            // Fermer la modal
                            $('#new_container_modal').hide();
                            $('#new_container_form')[0].reset();
                            
                            alert('<?php _e('Contenance créée avec succès !', 'restaurant-booking'); ?>');
                        } else {
                            alert('<?php _e('Erreur lors de la création:', 'restaurant-booking'); ?> ' + response.data);
                        }
                    },
                    error: function() {
                        alert('<?php _e('Erreur de communication avec le serveur.', 'restaurant-booking'); ?>');
                    }
                });
            });
        });
        </script>
        
        <style>
        /* Styles pour les fûts */
        #keg_size_modal, #new_container_modal {
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
        .keg-size-modal-content {
            background: white;
            padding: 20px;
            border-radius: 5px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        .keg-size-item {
            border: 1px solid #ddd;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .keg-size-info {
            flex: 1;
        }
        .keg-size-info h4 {
            margin: 0 0 5px 0;
        }
        .keg-size-image img {
            max-width: 50px;
            height: auto;
            margin: 5px 0;
        }
        .keg-size-actions {
            display: flex;
            gap: 5px;
        }
        .featured-badge {
            background: #0073aa;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
            margin-left: 10px;
        }
        </style>
        
        <?php
    }

    /**
     * Gérer la sauvegarde d'un fût
     */
    public function handle_save_keg()
    {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['keg_nonce'], 'save_keg')) {
            wp_die(__('Erreur de sécurité', 'restaurant-booking'));
        }

        // Récupérer les données
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $keg_name = sanitize_text_field($_POST['keg_name']);
        $keg_description = sanitize_textarea_field($_POST['keg_description']);
        $beer_category = sanitize_text_field($_POST['beer_category']);
        $alcohol_degree = floatval($_POST['alcohol_degree']);
        $keg_image_id = intval($_POST['keg_image_id']);
        $suggested_beverage = isset($_POST['suggested_beverage']) ? 1 : 0;
        $keg_sizes = isset($_POST['keg_sizes']) ? $_POST['keg_sizes'] : array();

        // Validation de base
        if (empty($keg_name) || empty($beer_category)) {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-kegs&action=add&error=validation'));
            exit;
        }

        // Validation des contenances - au moins une contenance requise
        if (empty($keg_sizes)) {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-kegs&action=add&error=no_sizes'));
            exit;
        }

        // Obtenir la catégorie fût
        $category = RestaurantBooking_Category::get_by_type('fut');
        if (!$category) {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-kegs&action=add&error=no_category'));
            exit;
        }

        // Préparer les données du produit
        $product_data = array(
            'category_id' => $category['id'],
            'name' => $keg_name,
            'description' => $keg_description,
            'price' => 0, // Prix sera géré par les tailles
            'unit_type' => 'piece',
            'unit_label' => '/fût',
            'volume_cl' => 2000, // Volume par défaut 20L
            'alcohol_degree' => $alcohol_degree,
            'beer_category' => $beer_category,
            'image_id' => $keg_image_id ?: null,
            'suggested_beverage' => $suggested_beverage,
            'has_multiple_sizes' => 1, // Activer le système multi-tailles
            'is_active' => 1
        );

        if ($product_id) {
            // Mise à jour
            $result = RestaurantBooking_Product::update($product_id, $product_data);
            if ($result) {
                $final_product_id = $product_id;
            } else {
                wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-kegs&action=edit&product_id=' . $product_id . '&error=update_failed'));
                exit;
            }
        } else {
            // Création
            $result = RestaurantBooking_Product::create($product_data);
            if ($result) {
                $final_product_id = $result;
            } else {
                wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-kegs&action=add&error=create_failed'));
                exit;
            }
        }

        // Sauvegarder les contenances
        $this->save_keg_sizes($final_product_id, $keg_sizes);

        // Redirection avec succès
        $success_param = $product_id ? 'updated' : 'created';
        $redirect_url = admin_url('admin.php?page=restaurant-booking-beverages-kegs&message=' . $success_param);
        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Sauvegarder les contenances d'un fût
     */
    private function save_keg_sizes($product_id, $sizes_data)
    {
        // Inclure le gestionnaire des tailles de fûts
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-keg-size-manager.php';
        
        // Supprimer les anciennes tailles
        RestaurantBooking_Keg_Size_Manager::delete_sizes_by_product($product_id);
        
        // Ajouter les nouvelles tailles
        foreach ($sizes_data as $size_data) {
            if (empty($size_data['liters']) || empty($size_data['price'])) {
                continue;
            }
            
            $keg_size_data = array(
                'product_id' => $product_id,
                'liters' => intval($size_data['liters']),
                'price' => floatval($size_data['price']),
                'image_id' => !empty($size_data['image_id']) ? intval($size_data['image_id']) : null,
                'is_featured' => !empty($size_data['is_featured']) ? 1 : 0,
                'display_order' => 0,
                'is_active' => 1
            );
            
            RestaurantBooking_Keg_Size_Manager::create_size($keg_size_data);
        }
    }

    /**
     * Obtenir les fûts de bière avec leurs contenances multiples
     */
    private function get_kegs()
    {
        global $wpdb;

        // Inclure le gestionnaire des tailles de fûts
        require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'includes/class-keg-size-manager.php';

        // Récupérer les fûts avec leurs contenances
        $kegs = array();
        
        $products = $wpdb->get_results($wpdb->prepare("
            SELECT p.*, c.service_type
            FROM {$wpdb->prefix}restaurant_products p
            INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
            WHERE c.type = %s AND p.is_active = 1
            ORDER BY p.suggested_beverage DESC, p.display_order ASC, p.name ASC
        ", 'fut'), ARRAY_A);

        foreach ($products as $product) {
            if ($product['has_multiple_sizes']) {
                // Nouveau système multi-contenances avec table dédiée
                $sizes = RestaurantBooking_Keg_Size_Manager::get_sizes_by_product($product['id']);
                
                // Créer l'entrée principale du fût avec toutes ses contenances
                $keg_data = array(
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'description' => $product['description'],
                    'beer_category' => $product['beer_category'],
                    'alcohol_degree' => (float) $product['alcohol_degree'],
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
                        $keg_data['sizes'][] = array(
                            'size_id' => $size['id'],
                            'liters' => (int) $size['liters'],
                            'size_label' => $size['liters'] . 'L',
                            'price' => (float) $size['price'],
                            'image_id' => $size['image_id'],
                            'image_url' => $size['image_id'] ? wp_get_attachment_image_url($size['image_id'], 'thumbnail') : '',
                            'is_featured' => (bool) $size['is_featured'],
                            'display_order' => (int) $size['display_order'],
                            'is_active' => (bool) $size['is_active']
                        );
                    }
                    
                    // Trier les contenances par ordre d'affichage puis par litres
                    usort($keg_data['sizes'], function($a, $b) {
                        if ($a['display_order'] === $b['display_order']) {
                            return $a['liters'] - $b['liters'];
                        }
                        return $a['display_order'] - $b['display_order'];
                    });
                } else {
                    // Marquer comme nécessitant une configuration
                    $keg_data['needs_configuration'] = true;
                }
                
                $kegs[] = $keg_data;
            } else {
                // Ancien système (compatibilité) - traiter comme un fût simple
                $kegs[] = array(
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'description' => $product['description'],
                    'beer_category' => $product['beer_category'],
                    'alcohol_degree' => (float) $product['alcohol_degree'],
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
                        'size_label' => ($product['volume_cl'] / 100) . 'L',
                        'volume_cl' => (int) $product['volume_cl'],
                        'price' => (float) $product['price']
                    )
                );
            }
        }

        return $kegs;
    }

    /**
     * Obtenir les types de bières disponibles (partagé avec les bières bouteilles)
     */
    private function get_beer_types()
    {
        global $wpdb;
        
        // CORRECTION : Récupérer depuis la nouvelle table wp_restaurant_beer_types en priorité
        $beer_types_table = $wpdb->prefix . 'restaurant_beer_types';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$beer_types_table'");
        
        if ($table_exists) {
            // Utiliser la nouvelle table wp_restaurant_beer_types
            $types = $wpdb->get_results("
                SELECT slug as category, name
                FROM $beer_types_table
                WHERE is_active = 1
                ORDER BY display_order ASC, name ASC
            ", ARRAY_A);
            
            if (!empty($types)) {
                return $types;
            }
        }
        
        // Fallback : Récupérer depuis la table des sous-catégories si elle existe encore
        $subcategories_table = $wpdb->prefix . 'restaurant_subcategories';
        $subcategories_exists = $wpdb->get_var("SHOW TABLES LIKE '$subcategories_table'") == $subcategories_table;
        
        if ($subcategories_exists) {
            $beer_category_id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}restaurant_categories WHERE type = 'biere_bouteille'");
            
            if ($beer_category_id) {
                $types = $wpdb->get_results($wpdb->prepare("
                    SELECT subcategory_key as category, subcategory_name as name
                    FROM $subcategories_table
                    WHERE parent_category_id = %d AND is_active = 1
                    ORDER BY display_order ASC, subcategory_name ASC
                ", $beer_category_id), ARRAY_A);
                
                if (!empty($types)) {
                    return $types;
                }
            }
        }
        
        // Fallback : Récupérer depuis les produits existants (fûts ET bouteilles)
        $existing_types = $wpdb->get_results("
            SELECT DISTINCT 
                p.beer_category as category, 
                p.beer_category as name
            FROM {$wpdb->prefix}restaurant_products p
            INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
            WHERE c.type IN ('biere_bouteille', 'fut') 
            AND p.is_active = 1 
            AND p.beer_category IS NOT NULL 
            AND p.beer_category != ''
            ORDER BY p.beer_category ASC
        ", ARRAY_A);
        
        if (!empty($existing_types)) {
            // Formatter les noms pour l'affichage
            foreach ($existing_types as &$type) {
                $type['name'] = ucfirst($type['name']);
            }
            return $existing_types;
        }
        
        // Dernier fallback : types par défaut (seulement si rien d'autre n'existe)
        return array(
            array('category' => 'blonde', 'name' => __('Blonde', 'restaurant-booking')),
            array('category' => 'blanche', 'name' => __('Blanche', 'restaurant-booking')),
            array('category' => 'brune', 'name' => __('Brune', 'restaurant-booking'))
        );
    }

    /**
     * Créer un nouveau type de bière (partagé avec les bières bouteilles)
     */
    private function create_new_beer_type($type_name)
    {
        // Nettoyer et formater le nom du type
        $beer_category = strtolower(sanitize_title($type_name));
        
        // Vérifier si le type existe déjà (dans les bières bouteilles OU les fûts)
        global $wpdb;
        $existing = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}restaurant_products p
            INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
            WHERE c.type IN (%s, %s) AND p.beer_category = %s
        ", 'biere_bouteille', 'fut', $beer_category));
        
        // Retourner le type (existant ou nouveau)
        return $beer_category;
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
                $this->delete_keg($product_id);
                break;
        }
    }

    /**
     * Supprimer un fût
     */
    private function delete_keg($product_id)
    {
        if (!wp_verify_nonce($_GET['_wpnonce'], 'delete_keg_' . $product_id)) {
            wp_die(__('Action non autorisée.', 'restaurant-booking'));
        }

        if (!current_user_can('manage_restaurant_quotes')) {
            wp_die(__('Permissions insuffisantes.', 'restaurant-booking'));
        }

        $result = RestaurantBooking_Product::delete($product_id);
        
        if (is_wp_error($result)) {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-kegs&message=error&error=' . urlencode($result->get_error_message())));
        } else {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-kegs&message=deleted'));
        }
        exit;
    }
}
