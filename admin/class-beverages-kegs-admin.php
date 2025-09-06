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
        $products = $this->get_kegs();
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">🍺 <?php _e('Fûts de Bière', 'restaurant-booking'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-kegs&action=add'); ?>" class="page-title-action">
                <?php _e('Ajouter un fût', 'restaurant-booking'); ?>
            </a>
            <hr class="wp-header-end">

            <div class="restaurant-booking-info-card">
                <h3><?php _e('Fûts de bière à la pression', 'restaurant-booking'); ?></h3>
                <ul>
                    <li><?php _e('✓ Bières à la pression pour événements', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Différentes contenances de fûts (10L, 20L, 30L, 50L)', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Prix par fût complet', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Système de mise à disposition (matériel inclus)', 'restaurant-booking'); ?></li>
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
                        <h3><?php echo count(array_filter($products, function($p) { return $p['suggested_beverage']; })); ?></h3>
                        <p><?php _e('En suggestion', 'restaurant-booking'); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $products ? number_format(array_sum(array_column($products, 'price')) / count($products), 0, ',', ' ') : '0'; ?> €</h3>
                        <p><?php _e('Prix moyen', 'restaurant-booking'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Tableau des fûts -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th class="manage-column"><?php _e('Fût de bière', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Type', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Contenance', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Prix', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Suggestion', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Actions', 'restaurant-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px;">
                                <p><?php _e('Aucun fût de bière configuré.', 'restaurant-booking'); ?></p>
                                <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-kegs&action=add'); ?>" class="button button-primary">
                                    <?php _e('Créer le premier fût', 'restaurant-booking'); ?>
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
                                                <br><small class="description"><?php echo esc_html(wp_trim_words($product['description'], 10)); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="beer-category-badge">
                                        <?php echo esc_html($product['beer_category'] ?: 'Non classée'); ?>
                                    </span>
                                    <?php if ($product['alcohol_degree']): ?>
                                        <br><small><?php echo $product['alcohol_degree']; ?>°</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo $product['volume_cl'] / 100; ?> L</strong>
                                    <br><small>Fût <?php echo $product['volume_cl'] / 100; ?> litres</small>
                                </td>
                                <td>
                                    <strong><?php echo number_format($product['price'], 0, ',', ' '); ?> €</strong>
                                    <br><small>/fût</small>
                                </td>
                                <td>
                                    <?php if ($product['suggested_beverage']): ?>
                                        <span class="dashicons dashicons-star-filled" style="color: #ffb900;" title="<?php _e('En suggestion', 'restaurant-booking'); ?>"></span>
                                        <small><?php _e('Oui', 'restaurant-booking'); ?></small>
                                    <?php else: ?>
                                        <span class="dashicons dashicons-star-empty" style="color: #ddd;"></span>
                                        <small><?php _e('Non', 'restaurant-booking'); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-kegs&action=edit&id=' . $product['id']); ?>" 
                                       class="button button-small"><?php _e('Modifier', 'restaurant-booking'); ?></a>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=restaurant-booking-beverages-kegs&action=delete&id=' . $product['id']), 'delete_keg_' . $product['id']); ?>" 
                                       class="button button-small button-link-delete" 
                                       onclick="return confirm('<?php _e('Êtes-vous sûr de vouloir supprimer ce fût ?', 'restaurant-booking'); ?>')"><?php _e('Supprimer', 'restaurant-booking'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
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
        </style>
        
        <?php
    }

    /**
     * Afficher le formulaire d'ajout/modification de fût
     */
    public function display_form()
    {
        $product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
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
                            <p class="description"><?php _e('Ex: Fût Heineken 30L, Fût Stella Artois 50L...', 'restaurant-booking'); ?></p>
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
                            <label for="beer_category"><?php _e('Type de bière', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <select id="beer_category" name="beer_category" class="regular-text">
                                <option value=""><?php _e('Sélectionner un type', 'restaurant-booking'); ?></option>
                                <option value="blonde" <?php selected($product['beer_category'] ?? '', 'blonde'); ?>><?php _e('Blonde', 'restaurant-booking'); ?></option>
                                <option value="blanche" <?php selected($product['beer_category'] ?? '', 'blanche'); ?>><?php _e('Blanche', 'restaurant-booking'); ?></option>
                                <option value="brune" <?php selected($product['beer_category'] ?? '', 'brune'); ?>><?php _e('Brune', 'restaurant-booking'); ?></option>
                                <option value="ipa" <?php selected($product['beer_category'] ?? '', 'ipa'); ?>><?php _e('IPA', 'restaurant-booking'); ?></option>
                                <option value="ambree" <?php selected($product['beer_category'] ?? '', 'ambree'); ?>><?php _e('Ambrée', 'restaurant-booking'); ?></option>
                                <option value="pils" <?php selected($product['beer_category'] ?? '', 'pils'); ?>><?php _e('Pils', 'restaurant-booking'); ?></option>
                            </select>
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
                            <label for="volume_liters"><?php _e('Contenance du fût', 'restaurant-booking'); ?> *</label>
                        </th>
                        <td>
                            <select id="volume_liters" name="volume_liters" class="regular-text" required>
                                <option value=""><?php _e('Choisir la contenance', 'restaurant-booking'); ?></option>
                                <option value="10" <?php selected(($product['volume_cl'] ?? 0) / 100, 10); ?>>10 L</option>
                                <option value="20" <?php selected(($product['volume_cl'] ?? 0) / 100, 20); ?>>20 L</option>
                                <option value="30" <?php selected(($product['volume_cl'] ?? 0) / 100, 30); ?>>30 L</option>
                                <option value="50" <?php selected(($product['volume_cl'] ?? 0) / 100, 50); ?>>50 L</option>
                            </select>
                            <p class="description"><?php _e('Contenance standard du fût', 'restaurant-booking'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="keg_price"><?php _e('Prix du fût', 'restaurant-booking'); ?> *</label>
                        </th>
                        <td>
                            <input type="number" id="keg_price" name="keg_price" step="0.01" min="0" 
                                   value="<?php echo $product ? esc_attr($product['price']) : ''; ?>" required>
                            <span>€</span>
                            <p class="description"><?php _e('Prix de location/vente du fût complet', 'restaurant-booking'); ?></p>
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

        <script>
        jQuery(document).ready(function($) {
            // Gestionnaire pour l'upload d'image
            var mediaUploader;
            
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
        });
        </script>
        <?php
    }

    /**
     * Gérer la sauvegarde d'un fût
     */
    private function handle_save_keg()
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
        $volume_liters = intval($_POST['volume_liters']);
        $keg_price = floatval($_POST['keg_price']);
        $keg_image_id = intval($_POST['keg_image_id']);
        $suggested_beverage = isset($_POST['suggested_beverage']) ? 1 : 0;

        // Validation
        if (empty($keg_name) || $keg_price <= 0 || $volume_liters <= 0) {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-kegs&action=add&error=validation'));
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
            'price' => $keg_price,
            'unit_type' => 'fut',
            'unit_label' => '/fût',
            'volume_cl' => $volume_liters * 100, // Convertir en centilitres
            'alcohol_degree' => $alcohol_degree,
            'beer_category' => $beer_category,
            'image_id' => $keg_image_id ?: null,
            'suggested_beverage' => $suggested_beverage,
            'is_active' => 1
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
        $redirect_url = admin_url('admin.php?page=restaurant-booking-beverages-kegs&message=' . $success_param);
        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Obtenir les fûts de bière
     */
    private function get_kegs()
    {
        global $wpdb;

        $products = $wpdb->get_results($wpdb->prepare("
            SELECT p.*, c.service_type
            FROM {$wpdb->prefix}restaurant_products p
            INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
            WHERE c.type = %s AND p.is_active = 1
            ORDER BY p.suggested_beverage DESC, p.display_order ASC, p.name ASC
        ", 'fut'), ARRAY_A);

        // Convertir les types et ajouter l'URL de l'image
        foreach ($products as &$product) {
            $product['price'] = (float) $product['price'];
            $product['volume_cl'] = (int) $product['volume_cl'];
            $product['alcohol_degree'] = (float) $product['alcohol_degree'];
            $product['suggested_beverage'] = (bool) $product['suggested_beverage'];
            $product['is_active'] = (bool) $product['is_active'];
            $product['image_url'] = $product['image_id'] ? wp_get_attachment_image_url($product['image_id'], 'thumbnail') : '';
        }

        return $products ?: array();
    }
}
