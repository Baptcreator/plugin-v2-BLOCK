<?php
/**
 * Classe d'administration des Buffets Salés
 *
 * @package RestaurantBooking
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Products_BuffetSale_Admin
{
    /**
     * Afficher la liste des buffets salés
     */
    public function display_list()
    {
        $products = $this->get_buffet_sale_products();
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">🍽️ <?php _e('Buffet Salé', 'restaurant-booking'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-products-buffet-sale&action=add'); ?>" class="page-title-action">
                <?php _e('Ajouter un plat de buffet salé', 'restaurant-booking'); ?>
            </a>
            <hr class="wp-header-end">

            <div class="restaurant-booking-info-card">
                <h3><?php _e('Règles du buffet salé', 'restaurant-booking'); ?></h3>
                <ul>
                    <li><?php _e('✓ Minimum 2 recettes différentes requises', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Minimum 1 portion par personne sur l\'ensemble', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Possibilité d\'ajouter des suppléments aux plats', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Quantité de suppléments limitée par la quantité du plat principal', 'restaurant-booking'); ?></li>
                </ul>
            </div>

            <!-- Statistiques rapides -->
            <div class="restaurant-booking-stats">
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><?php echo count($products); ?></h3>
                        <p><?php _e('Plats de buffet', 'restaurant-booking'); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo count(array_filter($products, function($p) { return $p['is_active']; })); ?></h3>
                        <p><?php _e('Actifs', 'restaurant-booking'); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo count(array_filter($products, function($p) { return $p['has_supplement']; })); ?></h3>
                        <p><?php _e('Avec suppléments', 'restaurant-booking'); ?></p>
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
                        <th class="manage-column"><?php _e('Plat', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Quantité/personne', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Prix', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Suppléments', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Statut', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Actions', 'restaurant-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px;">
                                <p><?php _e('Aucun plat de buffet salé configuré.', 'restaurant-booking'); ?></p>
                                <a href="<?php echo admin_url('admin.php?page=restaurant-booking-products-buffet-sale&action=add'); ?>" class="button button-primary">
                                    <?php _e('Créer le premier plat de buffet', 'restaurant-booking'); ?>
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
                                    <strong><?php echo esc_html($product['unit_per_person']); ?></strong>
                                    <br><small><?php echo esc_html($product['unit_label']); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo number_format($product['price'], 2, ',', ' '); ?> €</strong>
                                    <br><small><?php echo esc_html($product['unit_label']); ?></small>
                                </td>
                                <td>
                                    <?php if ($product['has_supplement']): ?>
                                        <span class="supplement-yes">
                                            ✓ <?php echo esc_html($product['supplement_name']); ?>
                                            <br><small>+<?php echo number_format($product['supplement_price'], 2, ',', ' '); ?> €</small>
                                        </span>
                                    <?php else: ?>
                                        <span class="supplement-no">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="product-status status-<?php echo $product['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $product['is_active'] ? __('Actif', 'restaurant-booking') : __('Inactif', 'restaurant-booking'); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=restaurant-booking-products-buffet-sale&action=edit&product_id=' . $product['id']); ?>" 
                                       class="button button-small">
                                        <?php _e('Modifier', 'restaurant-booking'); ?>
                                    </a>
                                    <a href="#" class="button button-small button-link-delete" 
                                       onclick="return confirm('<?php _e('Êtes-vous sûr de vouloir supprimer ce plat ?', 'restaurant-booking'); ?>')">
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
            background: #f0f4f8;
            border: 1px solid #b3d4fc;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }
        .restaurant-booking-info-card h3 {
            margin-top: 0;
            color: #1565c0;
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
        .supplement-yes { color: #155724; }
        .supplement-no { color: #666; }
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
            if (!$product || $product['category_type'] !== 'buffet_sale') {
                wp_die(__('Produit introuvable ou type incorrect.', 'restaurant-booking'));
            }
        }

        ?>
        <div class="wrap">
            <h1>
                🍽️ <?php echo $product ? __('Modifier le plat de buffet salé', 'restaurant-booking') : __('Nouveau plat de buffet salé', 'restaurant-booking'); ?>
            </h1>
            
            <form method="post" action="" enctype="multipart/form-data">
                <?php wp_nonce_field('restaurant_booking_product_buffet_sale', 'product_buffet_sale_nonce'); ?>
                <input type="hidden" name="restaurant_booking_action" value="save_product_buffet_sale">
                <?php if ($product): ?>
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <?php endif; ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="product_name"><?php _e('Nom du plat', 'restaurant-booking'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" id="product_name" name="product_name" 
                                   value="<?php echo $product ? esc_attr($product['name']) : ''; ?>" 
                                   class="regular-text" required>
                            <p class="description"><?php _e('Ex: Plateau de Charcuterie, Assortiment de Fromages...', 'restaurant-booking'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="product_description"><?php _e('Description (recette)', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <textarea id="product_description" name="product_description" 
                                      rows="4" class="large-text"><?php echo $product ? esc_textarea($product['description']) : ''; ?></textarea>
                            <p class="description"><?php _e('Description détaillée du plat et de sa composition.', 'restaurant-booking'); ?></p>
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
                            <p class="description"><?php _e('Prix du plat.', 'restaurant-booking'); ?></p>
                        </td>
                    </tr>


                    <tr>
                        <th scope="row">
                            <label><?php _e('Suppléments disponibles', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <div id="supplements_container">
                                <p class="description"><?php _e('Ajoutez des suppléments optionnels pour ce plat (ex: "Gratiné" +2€)', 'restaurant-booking'); ?></p>
                                
                                <div id="supplements_list">
                                    <!-- Les suppléments seront ajoutés dynamiquement ici -->
                                </div>
                                
                                <button type="button" class="button button-secondary" id="add_supplement_button">
                                    <?php _e('+ Ajouter un supplément', 'restaurant-booking'); ?>
                                </button>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="product_image"><?php _e('Image du plat', 'restaurant-booking'); ?></label>
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
                            <p class="description"><?php _e('Image du plat depuis la médiathèque WordPress.', 'restaurant-booking'); ?></p>
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
                                <?php _e('Plat actif (visible dans les formulaires)', 'restaurant-booking'); ?>
                            </label>
                        </td>
                    </tr>
                </table>

                <?php submit_button($product ? __('Mettre à jour le plat', 'restaurant-booking') : __('Créer le plat', 'restaurant-booking')); ?>
            </form>
        </div>

        <!-- Modal pour ajouter un supplément -->
        <div id="supplement_modal" style="display: none;">
            <div class="supplement-modal-content">
                <h3 id="supplement_modal_title"><?php _e('Ajouter un supplément', 'restaurant-booking'); ?></h3>
                <form id="supplement_form">
                    <table class="form-table">
                        <tr>
                            <th><label for="supplement_name"><?php _e('Nom du supplément', 'restaurant-booking'); ?> *</label></th>
                            <td>
                                <input type="text" id="supplement_name" name="supplement_name" class="regular-text" required
                                       placeholder="<?php _e('Ex: Gratiné, Bacon, Chèvre', 'restaurant-booking'); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="supplement_price"><?php _e('Prix', 'restaurant-booking'); ?> *</label></th>
                            <td>
                                <input type="number" id="supplement_price" name="supplement_price" step="0.01" min="0" class="small-text" required> €
                            </td>
                        </tr>
                        <tr>
                            <th><label for="supplement_max_qty"><?php _e('Quantité maximum', 'restaurant-booking'); ?></label></th>
                            <td>
                                <input type="number" id="supplement_max_qty" name="supplement_max_qty" min="1" max="10" value="10" class="small-text">
                                <p class="description"><?php _e('Quantité maximum par commande (1-10)', 'restaurant-booking'); ?></p>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" class="button-primary" value="<?php _e('Enregistrer', 'restaurant-booking'); ?>">
                        <button type="button" class="button" id="cancel_supplement"><?php _e('Annuler', 'restaurant-booking'); ?></button>
                    </p>
                </form>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            var mediaUploader;
            var supplementCounter = 0;
            
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
            
            // Gestion des suppléments
            $('#add_supplement_button').click(function() {
                $('#supplement_modal_title').text('<?php _e('Ajouter un supplément', 'restaurant-booking'); ?>');
                $('#supplement_form')[0].reset();
                $('#supplement_max_qty').val(10);
                $('#supplement_modal').show();
            });
            
            $('#cancel_supplement').click(function() {
                $('#supplement_modal').hide();
            });
            
            // Soumettre le formulaire de supplément
            $('#supplement_form').on('submit', function(e) {
                e.preventDefault();
                
                var supplementName = $('#supplement_name').val();
                var supplementPrice = $('#supplement_price').val();
                var supplementMaxQty = $('#supplement_max_qty').val();
                
                if (!supplementName || !supplementPrice) {
                    alert('<?php _e('Veuillez remplir tous les champs obligatoires.', 'restaurant-booking'); ?>');
                    return;
                }
                
                // Ajouter le supplément à la liste
                var supplementHtml = '<div class="supplement-item" data-supplement-id="' + supplementCounter + '">';
                supplementHtml += '<h4>' + supplementName + ' (+' + parseFloat(supplementPrice).toFixed(2) + '€) [Quantité: 1-' + supplementMaxQty + ']</h4>';
                supplementHtml += '<input type="hidden" name="supplements[' + supplementCounter + '][name]" value="' + supplementName + '">';
                supplementHtml += '<input type="hidden" name="supplements[' + supplementCounter + '][price]" value="' + supplementPrice + '">';
                supplementHtml += '<input type="hidden" name="supplements[' + supplementCounter + '][max_qty]" value="' + supplementMaxQty + '">';
                supplementHtml += '<button type="button" class="button button-small delete-supplement" data-supplement-id="' + supplementCounter + '">';
                supplementHtml += '<?php _e('Supprimer', 'restaurant-booking'); ?>';
                supplementHtml += '</button>';
                supplementHtml += '</div>';
                
                $('#supplements_list').append(supplementHtml);
                supplementCounter++;
                
                $('#supplement_modal').hide();
            });
            
            // Supprimer un supplément
            $(document).on('click', '.delete-supplement', function() {
                if (confirm('<?php _e('Êtes-vous sûr de vouloir supprimer ce supplément ?', 'restaurant-booking'); ?>')) {
                    $(this).closest('.supplement-item').remove();
                }
            });
        });
        </script>

        <style>
        #supplement_modal {
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
        .supplement-modal-content {
            background: white;
            padding: 20px;
            border-radius: 5px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        .supplement-item {
            border: 1px solid #ddd;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .supplement-item h4 {
            margin: 0;
            flex: 1;
        }
        </style>
        <?php
    }

    /**
     * Obtenir les produits de buffet salé depuis la base de données
     */
    private function get_buffet_sale_products()
    {
        global $wpdb;

        $products = $wpdb->get_results($wpdb->prepare("
            SELECT p.*, c.service_type
            FROM {$wpdb->prefix}restaurant_products p
            INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
            WHERE c.type = %s
            ORDER BY p.display_order ASC, p.name ASC
        ", 'buffet_sale'), ARRAY_A);

        // Convertir les types
        foreach ($products as &$product) {
            $product['price'] = (float) $product['price'];
            $product['supplement_price'] = (float) $product['supplement_price'];
            $product['has_supplement'] = (bool) $product['has_supplement'];
            $product['is_active'] = (bool) $product['is_active'];
        }

        return $products ?: array();
    }
}
