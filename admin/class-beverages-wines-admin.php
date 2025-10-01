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
     * Afficher la liste des vins avec filtrage par types
     */
    public function display_list()
    {
        // Gérer les actions (suppression, etc.)
        $this->handle_actions();
        
        $type_filter = isset($_GET['wine_type']) ? sanitize_text_field($_GET['wine_type']) : '';
        $products = $this->get_wines($type_filter);
        $wine_types = $this->get_wine_types();
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">🍷 <?php _e('Vins', 'restaurant-booking'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-wines&action=add'); ?>" class="page-title-action">
                <?php _e('Ajouter un vin', 'restaurant-booking'); ?>
            </a>
            <hr class="wp-header-end">

            <!-- Filtres par types -->
            <div class="wine-types-filter">
                <h3><?php _e('Filtrer par type de vin', 'restaurant-booking'); ?></h3>
                <div class="type-buttons">
                    <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-wines'); ?>" 
                       class="button <?php echo empty($type_filter) ? 'button-primary' : ''; ?>">
                        <?php _e('Tous les vins', 'restaurant-booking'); ?>
                    </a>
                    <?php foreach ($wine_types as $type): ?>
                        <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-wines&wine_type=' . urlencode($type['type'])); ?>" 
                           class="button <?php echo $type_filter === $type['type'] ? 'button-primary' : ''; ?>">
                            <?php echo esc_html($type['name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="restaurant-booking-info-card">
                <h3><?php _e('Système de gestion des vins', 'restaurant-booking'); ?></h3>
                <ul>
                    <li><?php _e('✓ Filtrage par types de vins (Blanc, Rouge, Rosé, Crémant...)', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Système "Nos suggestions" pour mise en avant', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Volumes personnalisables en centilitres', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Ajout de nouveaux types de vins possible', 'restaurant-booking'); ?></li>
                </ul>
            </div>

            <!-- Statistiques rapides -->
            <div class="restaurant-booking-stats">
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><?php echo count($products); ?></h3>
                        <p><?php _e('Vins', 'restaurant-booking'); ?> <?php echo $type_filter ? '(' . esc_html($this->get_wine_type_name($type_filter)) . ')' : ''; ?></p>
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
                        <h3><?php echo count($wine_types); ?></h3>
                        <p><?php _e('Types de vins', 'restaurant-booking'); ?></p>
                    </div>
                </div>
            </div>

            <form method="post" id="wines-filter">
                <?php wp_nonce_field('restaurant_booking_wines_action'); ?>
                
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

                <!-- Tableau des vins -->
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
                            <th scope="col" class="manage-column column-degree"><?php _e('Degré/Volume', 'restaurant-booking'); ?></th>
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
                                    <?php _e('Aucun vin trouvé.', 'restaurant-booking'); ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <th scope="row" class="check-column">
                                        <input id="cb-select-<?php echo $product['id']; ?>" type="checkbox" name="wine_ids[]" value="<?php echo $product['id']; ?>">
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
                                            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-wines&action=edit&product_id=' . $product['id']); ?>">
                                                <?php echo esc_html($product['name']); ?>
                                            </a>
                                        </strong>
                                        <div class="row-actions">
                                            <span class="edit">
                                                <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-wines&action=edit&product_id=' . $product['id']); ?>">
                                                    <?php _e('Modifier', 'restaurant-booking'); ?>
                                                </a> |
                                            </span>
                                            <span class="toggle-status">
                                                <a href="#" class="toggle-wine-status" data-product-id="<?php echo $product['id']; ?>" data-current-status="<?php echo $product['is_active'] ? 1 : 0; ?>">
                                                    <?php echo $product['is_active'] ? __('Désactiver', 'restaurant-booking') : __('Activer', 'restaurant-booking'); ?>
                                                </a> |
                                            </span>
                                            <span class="delete">
                                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=restaurant-booking-beverages-wines&action=delete&product_id=' . $product['id']), 'delete_wine_' . $product['id']); ?>" 
                                                   class="button button-small button-link-delete" 
                                                   onclick="return confirm('<?php _e('Êtes-vous sûr de vouloir supprimer ce vin ?', 'restaurant-booking'); ?>')">
                                                    <?php _e('Supprimer', 'restaurant-booking'); ?>
                                                </a>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="column-description">
                                        <?php echo esc_html(wp_trim_words($product['description'] ?? '', 10)); ?>
                                    </td>
                                    <td class="column-type">
                                        <span class="wine-type-badge">
                                            <?php echo esc_html($this->get_wine_type_name($product['category_type'])); ?>
                                        </span>
                                    </td>
                                    <td class="column-degree">
                                        <strong><?php echo $product['alcohol_degree']; ?>°</strong>
                                        <br><small><?php echo $product['volume_cl']; ?> cl</small>
                                    </td>
                                    <td class="column-price">
                                        <strong><?php echo number_format($product['price'], 2, ',', ' '); ?> €</strong>
                                    </td>
                                    <td class="column-suggestion">
                                        <?php if ($product['suggested_beverage']): ?>
                                            <span class="suggestion-yes">⭐ <?php _e('Oui', 'restaurant-booking'); ?></span>
                                        <?php else: ?>
                                            <span class="suggestion-no">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="column-order">
                                        <input type="number" class="small-text wine-order-input" 
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
            </form>
        </div>

        <style>
        .wine-types-filter {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        .wine-types-filter h3 {
            margin-top: 0;
            margin-bottom: 10px;
        }
        .type-buttons {
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
        .wine-type-badge {
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
        .status-active { color: #46b450; font-weight: 600; }
        .status-inactive { color: #dc3232; font-weight: 600; }
        .wine-order-input { width: 60px; }
        .column-image { width: 70px; }
        .column-price { width: 100px; text-align: center; }
        .column-type { width: 100px; }
        .column-degree { width: 100px; }
        .column-suggestion { width: 100px; }
        .column-order { width: 80px; }
        .column-status { width: 80px; }
        .column-date { width: 120px; }
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
                                <?php 
                                $wine_types = $this->get_wine_types();
                                foreach ($wine_types as $type): ?>
                                    <option value="<?php echo esc_attr($type['type']); ?>" <?php selected($product['category_type'] ?? '', $type['type']); ?>>
                                        <?php echo esc_html($type['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('Sélectionnez le type de vin. Vous pouvez ajouter de nouveaux types ci-dessous.', 'restaurant-booking'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="new_wine_type"><?php _e('Nouveau type de vin', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="new_wine_type" name="new_wine_type" class="regular-text" placeholder="<?php _e('Ex: Champagne, Moscato...', 'restaurant-booking'); ?>">
                            <p class="description"><?php _e('Si vous saisissez un nouveau type, il sera créé automatiquement et sélectionné.', 'restaurant-booking'); ?></p>
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
                            <input type="number" id="volume_cl" name="volume_cl" 
                                   value="<?php echo $product ? esc_attr($product['volume_cl']) : '75'; ?>" 
                                   min="1" max="5000" step="0.1" class="small-text" required> cl
                            <p class="description"><?php _e('Volume en centilitres (ex: 75 pour une bouteille standard, 150 pour un magnum).', 'restaurant-booking'); ?></p>
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
     * Gérer la sauvegarde d'un vin
     */
    public function handle_save_wine()
    {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['beverage_wine_nonce'], 'restaurant_booking_beverage_wine')) {
            wp_die(__('Erreur de sécurité', 'restaurant-booking'));
        }

        // Récupérer les données
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $product_name = sanitize_text_field($_POST['product_name']);
        $product_description = sanitize_textarea_field($_POST['product_description']);
        $wine_type = sanitize_text_field($_POST['wine_type']);
        $new_wine_type = sanitize_text_field($_POST['new_wine_type']);
        $alcohol_degree = floatval($_POST['alcohol_degree']);
        $volume_cl = floatval($_POST['volume_cl']);
        $product_price = floatval($_POST['product_price']);
        $product_image_id = intval($_POST['product_image_id']);
        $suggested_beverage = isset($_POST['suggested_beverage']) ? 1 : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Gérer le nouveau type de vin si fourni
        if (!empty($new_wine_type)) {
            $wine_type = $this->create_new_wine_type($new_wine_type);
        }

        // Validation
        if (empty($product_name) || empty($wine_type) || $product_price <= 0 || $alcohol_degree <= 0 || $volume_cl <= 0) {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-wines&action=add&error=validation'));
            exit;
        }

        // Obtenir la catégorie
        $category = RestaurantBooking_Category::get_by_type($wine_type);
        if (!$category) {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-wines&action=add&error=no_category'));
            exit;
        }

        // Préparer les données du produit
        $product_data = array(
            'category_id' => $category['id'],
            'name' => $product_name,
            'description' => $product_description,
            'price' => $product_price,
            'unit_type' => 'bouteille',
            'unit_label' => '/bouteille ' . $volume_cl . 'cl',
            'volume_cl' => $volume_cl,
            'alcohol_degree' => $alcohol_degree,
            'image_id' => $product_image_id ?: null,
            'suggested_beverage' => $suggested_beverage,
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
        $redirect_url = admin_url('admin.php?page=restaurant-booking-beverages-wines&message=' . $success_param);
        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Obtenir les vins avec filtrage optionnel par type
     */
    private function get_wines($type_filter = '')
    {
        global $wpdb;

        $wine_types = $this->get_wine_types();
        $wine_type_codes = array_column($wine_types, 'type');
        
        $where_clause = "WHERE c.type IN ('" . implode("', '", array_map('esc_sql', $wine_type_codes)) . "')";
        $params = array();

        if (!empty($type_filter)) {
            $where_clause .= " AND c.type = %s";
            $params[] = $type_filter;
        }

        $sql = "SELECT p.*, c.service_type, c.type as category_type
                FROM {$wpdb->prefix}restaurant_products p
                INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
                $where_clause
                ORDER BY p.suggested_beverage DESC, c.type ASC, p.name ASC";

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        $products = $wpdb->get_results($sql, ARRAY_A);

        // Convertir les types et ajouter l'URL de l'image
        foreach ($products as &$product) {
            $product['price'] = (float) $product['price'];
            $product['alcohol_degree'] = (float) $product['alcohol_degree'];
            $product['volume_cl'] = (int) $product['volume_cl'];
            $product['suggested_beverage'] = (bool) $product['suggested_beverage'];
            $product['is_active'] = (bool) $product['is_active'];
            $product['image_url'] = $product['image_id'] ? wp_get_attachment_image_url($product['image_id'], 'thumbnail') : '';
        }

        return $products ?: array();
    }

    /**
     * Obtenir tous les types de vins existants
     */
    private function get_wine_types()
    {
        global $wpdb;

        $types = $wpdb->get_results("
            SELECT c.type, c.name 
            FROM {$wpdb->prefix}restaurant_categories c
            WHERE c.type LIKE 'vin_%' OR c.type = 'cremant'
            ORDER BY c.name ASC
        ", ARRAY_A);

        return $types ?: array();
    }
    
    /**
     * Obtenir le nom d'un type de vin
     */
    private function get_wine_type_name($type)
    {
        global $wpdb;
        
        $name = $wpdb->get_var($wpdb->prepare("
            SELECT name FROM {$wpdb->prefix}restaurant_categories 
            WHERE type = %s
        ", $type));
        
        return $name ?: $type;
    }
    
    /**
     * Créer un nouveau type de vin
     */
    private function create_new_wine_type($type_name)
    {
        global $wpdb;
        
        // Générer un code unique pour le type
        $type_code = 'vin_' . sanitize_title($type_name);
        
        // Vérifier si le type existe déjà
        $existing = $wpdb->get_var($wpdb->prepare("
            SELECT id FROM {$wpdb->prefix}restaurant_categories 
            WHERE type = %s
        ", $type_code));
        
        if ($existing) {
            return $type_code;
        }
        
        // Créer la nouvelle catégorie
        $result = $wpdb->insert(
            $wpdb->prefix . 'restaurant_categories',
            array(
                'name' => $type_name,
                'slug' => sanitize_title($type_name),
                'type' => $type_code,
                'service_type' => 'both',
                'description' => 'Type de vin créé automatiquement',
                'is_required' => 0,
                'max_selections' => 0,
                'image_id' => null,
                'display_order' => 0
            ),
            array('%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d')
        );
        
        if ($result) {
            // Mettre à jour l'ENUM de la base de données
            $this->update_category_enum($type_code);
            return $type_code;
        }
        
        return false;
    }
    
    /**
     * Mettre à jour l'ENUM des types de catégories
     */
    private function update_category_enum($new_type)
    {
        global $wpdb;
        
        // Récupérer les types existants
        $existing_types = array(
            'plat_signature_dog', 'plat_signature_croq', 'mini_boss', 'accompagnement', 
            'buffet_sale', 'buffet_sucre', 'soft', 'vin_blanc', 'vin_rouge', 'vin_rose', 
            'cremant', 'biere_bouteille', 'fut', 'jeu', 'option_restaurant', 'option_remorque'
        );
        
        // Ajouter le nouveau type s'il n'existe pas
        if (!in_array($new_type, $existing_types)) {
            $existing_types[] = $new_type;
        }
        
        // Créer le nouvel ENUM
        $new_enum = "enum('" . implode("', '", array_map('esc_sql', $existing_types)) . "') NOT NULL";
        
        // Mettre à jour la table
        $wpdb->query("ALTER TABLE {$wpdb->prefix}restaurant_categories MODIFY COLUMN type $new_enum");
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
                $this->delete_wine($product_id);
                break;
        }
    }

    /**
     * Supprimer un vin
     */
    private function delete_wine($product_id)
    {
        if (!wp_verify_nonce($_GET['_wpnonce'], 'delete_wine_' . $product_id)) {
            wp_die(__('Action non autorisée.', 'restaurant-booking'));
        }

        if (!current_user_can('manage_restaurant_quotes')) {
            wp_die(__('Permissions insuffisantes.', 'restaurant-booking'));
        }

        $result = RestaurantBooking_Product::delete($product_id);
        
        if (is_wp_error($result)) {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-wines&message=error&error=' . urlencode($result->get_error_message())));
        } else {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-wines&message=deleted'));
        }
        exit;
    }
}
