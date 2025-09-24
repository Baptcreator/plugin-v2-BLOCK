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

            <!-- Tableau des produits -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th class="manage-column"><?php _e('Boisson', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Contenance', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Prix', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Suggestion', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Statut', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Actions', 'restaurant-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px;">
                                <p><?php _e('Aucune boisson soft configurée.', 'restaurant-booking'); ?></p>
                                <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-soft&action=add'); ?>" class="button button-primary">
                                    <?php _e('Créer la première boisson soft', 'restaurant-booking'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <div class="product-info">
                                        <?php if ($product['image_url']): ?>
                                            <img src="<?php echo esc_url($product['image_url']); ?>" 
                                                 alt="<?php echo esc_attr($product['name']); ?>" 
                                                 class="product-thumb">
                                        <?php endif; ?>
                                        <div>
                                            <strong><?php echo esc_html($product['name']); ?></strong>
                                            <?php if ($product['description']): ?>
                                                <br><small class="description"><?php echo esc_html(wp_trim_words($product['description'], 15)); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <strong><?php echo esc_html($product['size_label']); ?></strong>
                                    <br><small><?php echo $product['size_cl']; ?> cl</small>
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
                                        <?php echo $product['is_active'] ? __('Active', 'restaurant-booking') : __('Inactive', 'restaurant-booking'); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-soft&action=edit&product_id=' . $product['id']); ?>" 
                                       class="button button-small">
                                        <?php _e('Modifier', 'restaurant-booking'); ?>
                                    </a>
                                    <a href="#" class="button button-small button-link-delete" 
                                       onclick="return confirm('<?php _e('Êtes-vous sûr de vouloir supprimer cette boisson ?', 'restaurant-booking'); ?>')">
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
                            <label><?php _e('Contenances disponibles', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <div id="beverage_sizes_container">
                                <p class="description"><?php _e('Ajoutez les différentes tailles disponibles pour cette boisson (ex: 33cl, 1L)', 'restaurant-booking'); ?></p>
                                
                                <div id="sizes_list">
                                    <?php if ($product): ?>
                                        <?php $sizes = RestaurantBooking_Beverage_Size_Manager::get_product_sizes($product['id']); ?>
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
                                                    <button type="button" class="button button-small edit-size" data-size-id="<?php echo $size->id; ?>">
                                                        <?php _e('Modifier', 'restaurant-booking'); ?>
                                                    </button>
                                                    <button type="button" class="button button-small delete-size" data-size-id="<?php echo $size->id; ?>">
                                                        <?php _e('Supprimer', 'restaurant-booking'); ?>
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
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
            var mediaUploader;
            
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
            
            // Gestion des tailles de boissons
            $('#add_size_button').click(function() {
                $('#size_modal_title').text('<?php _e('Ajouter une contenance', 'restaurant-booking'); ?>');
                $('#size_form')[0].reset();
                $('#size_image_preview').empty();
                $('#size_modal').show();
            });
            
            $('#cancel_size').click(function() {
                $('#size_modal').hide();
            });
            
            // Soumettre le formulaire de taille
            $('#size_form').on('submit', function(e) {
                e.preventDefault();
                
                var formData = {
                    action: 'restaurant_add_beverage_size',
                    nonce: '<?php echo wp_create_nonce('restaurant_booking_admin'); ?>',
                    product_id: <?php echo $product ? $product['id'] : 'null'; ?>,
                    size_cl: $('#size_cl').val(),
                    size_label: $('#size_label').val(),
                    price: $('#size_price').val(),
                    image_id: $('#size_image_id').val(),
                    is_featured: $('#is_featured').is(':checked') ? 1 : 0
                };
                
                $.post(ajaxurl, formData, function(response) {
                    if (response.success) {
                        location.reload(); // Recharger pour voir les nouvelles tailles
                    } else {
                        alert('Erreur: ' + response.data);
                    }
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
                            $item.remove();
                        } else {
                            alert('Erreur: ' + response.data);
                        }
                    });
                }
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
     * Obtenir les boissons soft depuis la base de données (nouveau système multi-tailles)
     */
    private function get_soft_beverages()
    {
        global $wpdb;

        // Récupérer les boissons avec leurs tailles
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
                // Récupérer toutes les tailles pour cette boisson
                $sizes = $wpdb->get_results($wpdb->prepare("
                    SELECT * FROM {$wpdb->prefix}restaurant_beverage_sizes 
                    WHERE product_id = %d
                    ORDER BY display_order ASC, size_cl ASC
                ", $product['id']), ARRAY_A);
                
                foreach ($sizes as $size) {
                    $beverages[] = array(
                        'id' => $product['id'],
                        'size_id' => $size['id'],
                        'name' => $product['name'],
                        'description' => $product['description'],
                        'size_label' => $size['size_label'],
                        'size_cl' => (int) $size['size_cl'],
                        'price' => (float) $size['price'],
                        'image_id' => $size['image_id'],
                        'image_url' => $size['image_id'] ? wp_get_attachment_image_url($size['image_id'], 'thumbnail') : '',
                        'is_featured' => (bool) $size['is_featured'],
                        'suggested_beverage' => (bool) $size['is_featured'], // Utiliser is_featured comme suggestion
                        'is_active' => (bool) $product['is_active'],
                        'service_type' => $product['service_type'],
                        'has_multiple_sizes' => true
                    );
                }
            } else {
                // Ancien système (compatibilité)
                $beverages[] = array(
                    'id' => $product['id'],
                    'size_id' => null,
                    'name' => $product['name'],
                    'description' => $product['description'],
                    'size_label' => $product['volume_cl'] . 'cl',
                    'size_cl' => (int) $product['volume_cl'],
                    'price' => (float) $product['price'],
                    'image_id' => $product['image_id'],
                    'image_url' => $product['image_id'] ? wp_get_attachment_image_url($product['image_id'], 'thumbnail') : '',
                    'is_featured' => false,
                    'suggested_beverage' => (bool) $product['suggested_beverage'],
                    'is_active' => (bool) $product['is_active'],
                    'service_type' => $product['service_type'],
                    'has_multiple_sizes' => false
                );
            }
        }

        return $beverages;
    }
}
