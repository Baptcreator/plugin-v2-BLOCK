<?php
/**
 * Classe d'administration des Vins
 *
 * @package RestaurantBooking
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Beverages_Wines_Admin
{
    /**
     * Afficher la liste des vins avec filtrage par catégories
     */
    public function display_list()
    {
        $category_filter = isset($_GET['wine_category']) ? sanitize_text_field($_GET['wine_category']) : '';
        $products = $this->get_wines($category_filter);
        $categories = $this->get_wine_categories();
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">🍷 <?php _e('Vins', 'restaurant-booking'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-wines&action=add'); ?>" class="page-title-action">
                <?php _e('Ajouter un vin', 'restaurant-booking'); ?>
            </a>
            <hr class="wp-header-end">

            <!-- Filtres par catégories -->
            <div class="wine-categories-filter">
                <h3><?php _e('Filtrer par catégorie', 'restaurant-booking'); ?></h3>
                <div class="category-buttons">
                    <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-wines'); ?>" 
                       class="button <?php echo empty($category_filter) ? 'button-primary' : ''; ?>">
                        <?php _e('Tous les vins', 'restaurant-booking'); ?>
                    </a>
                    <?php foreach ($categories as $category): ?>
                        <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-wines&wine_category=' . urlencode($category)); ?>" 
                           class="button <?php echo $category_filter === $category ? 'button-primary' : ''; ?>">
                            <?php echo esc_html($category); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="restaurant-booking-info-card">
                <h3><?php _e('Système de catégories des vins', 'restaurant-booking'); ?></h3>
                <ul>
                    <li><?php _e('✓ Filtrage par régions et appellations', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Système "Nos suggestions" pour mise en avant', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Différentes contenances et degrés d\'alcool', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Sélection optionnelle', 'restaurant-booking'); ?></li>
                </ul>
            </div>

            <!-- Statistiques rapides -->
            <div class="restaurant-booking-stats">
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><?php echo count($products); ?></h3>
                        <p><?php _e('Vins', 'restaurant-booking'); ?> <?php echo $category_filter ? '(' . esc_html($category_filter) . ')' : ''; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo count(array_filter($products, function($p) { return $p['is_active']; })); ?></h3>
                        <p><?php _e('Actifs', 'restaurant-booking'); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo count(array_filter($products, function($p) { return $p['suggested_beverage']; })); ?></h3>
                        <p><?php _e('En suggestion', 'restaurant-booking'); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo count($categories); ?></h3>
                        <p><?php _e('Catégories', 'restaurant-booking'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Tableau des vins -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th class="manage-column"><?php _e('Vin', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Catégorie', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Degré/Volume', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Prix', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Suggestion', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Statut', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Actions', 'restaurant-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px;">
                                <p><?php _e('Aucun vin trouvé pour cette catégorie.', 'restaurant-booking'); ?></p>
                                <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-wines&action=add'); ?>" class="button button-primary">
                                    <?php _e('Ajouter un vin', 'restaurant-booking'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <div class="product-info">
                                        <div>
                                            <strong><?php echo esc_html($product['name']); ?></strong>
                                            <?php if ($product['description']): ?>
                                                <br><small class="description"><?php echo esc_html(wp_trim_words($product['description'], 15)); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="wine-category-badge">
                                        <?php echo esc_html($product['wine_category'] ?: __('Non classé', 'restaurant-booking')); ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo $product['alcohol_degree']; ?>°</strong>
                                    <br><small><?php echo $product['volume_cl']; ?> cl</small>
                                </td>
                                <td>
                                    <strong><?php echo number_format($product['price'], 2, ',', ' '); ?> €</strong>
                                    <br><small><?php echo esc_html($product['unit_label']); ?></small>
                                </td>
                                <td>
                                    <?php if ($product['suggested_beverage']): ?>
                                        <span class="suggestion-yes">⭐ <?php _e('Nos suggestions', 'restaurant-booking'); ?></span>
                                    <?php else: ?>
                                        <span class="suggestion-no">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="product-status status-<?php echo $product['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $product['is_active'] ? __('Actif', 'restaurant-booking') : __('Inactif', 'restaurant-booking'); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-wines&action=edit&product_id=' . $product['id']); ?>" 
                                       class="button button-small">
                                        <?php _e('Modifier', 'restaurant-booking'); ?>
                                    </a>
                                    <a href="#" class="button button-small button-link-delete" 
                                       onclick="return confirm('<?php _e('Êtes-vous sûr de vouloir supprimer ce vin ?', 'restaurant-booking'); ?>')">
                                        <?php _e('Supprimer', 'restaurant-booking'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <style>
        .wine-categories-filter {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        .wine-categories-filter h3 {
            margin-top: 0;
            margin-bottom: 10px;
        }
        .category-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .restaurant-booking-info-card {
            background: #f3e5f5;
            border: 1px solid #e1bee7;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }
        .restaurant-booking-info-card h3 {
            margin-top: 0;
            color: #7b1fa2;
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
        .description {
            color: #666;
            font-style: italic;
        }
        .wine-category-badge {
            background: #e1f5fe;
            color: #0277bd;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 500;
        }
        .suggestion-yes { 
            color: #ff9800; 
            font-weight: bold;
        }
        .suggestion-no { color: #666; }
        .product-status {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }
        </style>
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
            if (!$product || !in_array($product['category_type'], ['vin_blanc', 'vin_rouge', 'vin_rose', 'cremant'])) {
                wp_die(__('Produit introuvable ou type incorrect.', 'restaurant-booking'));
            }
        }

        ?>
        <div class="wrap">
            <h1>
                🍷 <?php echo $product ? __('Modifier le vin', 'restaurant-booking') : __('Nouveau vin', 'restaurant-booking'); ?>
            </h1>
            
            <form method="post" action="" enctype="multipart/form-data">
                <?php wp_nonce_field('restaurant_booking_beverage_wine', 'beverage_wine_nonce'); ?>
                <input type="hidden" name="restaurant_booking_action" value="save_beverage_wine">
                <?php if ($product): ?>
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <?php endif; ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="product_name"><?php _e('Nom du vin', 'restaurant-booking'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" id="product_name" name="product_name" 
                                   value="<?php echo $product ? esc_attr($product['name']) : ''; ?>" 
                                   class="regular-text" required>
                            <p class="description"><?php _e('Ex: Sancerre AOC, Chablis Premier Cru...', 'restaurant-booking'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="wine_type"><?php _e('Type de vin', 'restaurant-booking'); ?> *</label>
                        </th>
                        <td>
                            <select id="wine_type" name="wine_type" required>
                                <option value=""><?php _e('Sélectionner un type...', 'restaurant-booking'); ?></option>
                                <option value="vin_blanc" <?php selected($product['category_type'] ?? '', 'vin_blanc'); ?>>
                                    <?php _e('Vin Blanc', 'restaurant-booking'); ?>
                                </option>
                                <option value="vin_rouge" <?php selected($product['category_type'] ?? '', 'vin_rouge'); ?>>
                                    <?php _e('Vin Rouge', 'restaurant-booking'); ?>
                                </option>
                                <option value="vin_rose" <?php selected($product['category_type'] ?? '', 'vin_rose'); ?>>
                                    <?php _e('Vin Rosé', 'restaurant-booking'); ?>
                                </option>
                                <option value="cremant" <?php selected($product['category_type'] ?? '', 'cremant'); ?>>
                                    <?php _e('Crémant', 'restaurant-booking'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="wine_category"><?php _e('Catégorie/Région', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="wine_category" name="wine_category" 
                                   value="<?php echo $product ? esc_attr($product['wine_category']) : ''; ?>" 
                                   class="regular-text" list="wine_categories">
                            <datalist id="wine_categories">
                                <option value="Loire">
                                <option value="Bourgogne">
                                <option value="Bordeaux">
                                <option value="Alsace">
                                <option value="Rhône">
                                <option value="Provence">
                                <option value="Languedoc">
                            </datalist>
                            <p class="description"><?php _e('Ex: Loire, Bourgogne, Bordeaux... (utilisé pour le filtrage).', 'restaurant-booking'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="product_description"><?php _e('Description', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <textarea id="product_description" name="product_description" 
                                      rows="3" class="large-text"><?php echo $product ? esc_textarea($product['description']) : ''; ?></textarea>
                            <p class="description"><?php _e('Description du vin, notes de dégustation...', 'restaurant-booking'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="alcohol_degree"><?php _e('Degré d\'alcool', 'restaurant-booking'); ?> *</label>
                        </th>
                        <td>
                            <input type="number" id="alcohol_degree" name="alcohol_degree" 
                                   value="<?php echo $product ? $product['alcohol_degree'] : ''; ?>" 
                                   step="0.1" min="0" max="20" class="small-text" required> °
                            <p class="description"><?php _e('Degré d\'alcool (ex: 12.5).', 'restaurant-booking'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="volume_cl"><?php _e('Volume', 'restaurant-booking'); ?> *</label>
                        </th>
                        <td>
                            <select id="volume_cl" name="volume_cl" required>
                                <option value="37.5" <?php selected($product['volume_cl'] ?? '', '37.5'); ?>>37,5 cl (demi-bouteille)</option>
                                <option value="75" <?php selected($product['volume_cl'] ?? '75', '75'); ?>>75 cl (bouteille standard)</option>
                                <option value="150" <?php selected($product['volume_cl'] ?? '', '150'); ?>>1,5 L (magnum)</option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="product_price"><?php _e('Prix', 'restaurant-booking'); ?> *</label>
                        </th>
                        <td>
                            <input type="number" id="product_price" name="product_price" 
                                   value="<?php echo $product ? $product['price'] : ''; ?>" 
                                   step="0.01" min="0" class="small-text" required> €
                            <p class="description"><?php _e('Prix pour ce volume.', 'restaurant-booking'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="suggested_beverage"><?php _e('Mise en avant', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="suggested_beverage" name="suggested_beverage" value="1" 
                                       <?php checked($product['suggested_beverage'] ?? false); ?>>
                                <?php _e('Mettre en "Nos suggestions du moment"', 'restaurant-booking'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="product_image"><?php _e('Image du vin', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <button type="button" class="button" id="upload_image_button">
                                <?php _e('Choisir une image', 'restaurant-booking'); ?>
                            </button>
                            <input type="hidden" id="product_image_id" name="product_image_id" 
                                   value="<?php echo $product ? esc_attr($product['image_id']) : ''; ?>">
                            <div id="image_preview" style="margin-top: 10px;">
                                <?php if ($product && $product['image_id']): ?>
                                    <?php echo wp_get_attachment_image($product['image_id'], 'thumbnail'); ?>
                                <?php endif; ?>
                            </div>
                            <p class="description"><?php _e('Image du vin depuis la médiathèque WordPress.', 'restaurant-booking'); ?></p>
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
                                <?php _e('Vin actif (visible dans les formulaires)', 'restaurant-booking'); ?>
                            </label>
                        </td>
                    </tr>
                </table>

                <?php submit_button($product ? __('Mettre à jour le vin', 'restaurant-booking') : __('Créer le vin', 'restaurant-booking')); ?>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($) {
            var mediaUploader;
            
            // Sélecteur d'images WordPress
            $('#upload_image_button').click(function(e) {
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
                    $('#product_image_id').val(attachment.id);
                    $('#image_preview').html('<img src="' + attachment.sizes.thumbnail.url + '" alt="">');
                });
                
                mediaUploader.open();
            });
        });
        </script>
        <?php
    }

    /**
     * Obtenir les vins avec filtrage optionnel par catégorie
     */
    private function get_wines($category_filter = '')
    {
        global $wpdb;

        $where_clause = "WHERE c.type IN ('vin_blanc', 'vin_rouge', 'vin_rose', 'cremant')";
        $params = array();

        if (!empty($category_filter)) {
            $where_clause .= " AND p.wine_category = %s";
            $params[] = $category_filter;
        }

        $sql = "SELECT p.*, c.service_type, c.type as category_type
                FROM {$wpdb->prefix}restaurant_products p
                INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
                $where_clause
                ORDER BY p.suggested_beverage DESC, p.wine_category ASC, p.name ASC";

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        $products = $wpdb->get_results($sql, ARRAY_A);

        // Convertir les types
        foreach ($products as &$product) {
            $product['price'] = (float) $product['price'];
            $product['alcohol_degree'] = (float) $product['alcohol_degree'];
            $product['volume_cl'] = (int) $product['volume_cl'];
            $product['suggested_beverage'] = (bool) $product['suggested_beverage'];
            $product['is_active'] = (bool) $product['is_active'];
        }

        return $products ?: array();
    }

    /**
     * Obtenir toutes les catégories de vins existantes
     */
    private function get_wine_categories()
    {
        global $wpdb;

        $categories = $wpdb->get_col("
            SELECT DISTINCT p.wine_category 
            FROM {$wpdb->prefix}restaurant_products p
            INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
            WHERE c.type IN ('vin_blanc', 'vin_rouge', 'vin_rose', 'cremant')
            AND p.wine_category IS NOT NULL 
            AND p.wine_category != ''
            ORDER BY p.wine_category ASC
        ");

        return $categories ?: array();
    }
}
