<?php
/**
 * Classe d'administration des Bières Bouteilles
 *
 * @package RestaurantBooking
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Beverages_Beers_Admin
{
    /**
     * Afficher la liste des bières
     */
    public function display_list()
    {
        $products = $this->get_beers();
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">🍷 <?php _e('Bières Bouteilles', 'restaurant-booking'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-beers&action=add'); ?>" class="page-title-action">
                <?php _e('Ajouter une bière', 'restaurant-booking'); ?>
            </a>
            <hr class="wp-header-end">

            <div class="restaurant-booking-info-card">
                <h3><?php _e('Bières en bouteilles', 'restaurant-booking'); ?></h3>
                <ul>
                    <li><?php _e('✓ Bières artisanales et classiques', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Catégories : Blonde, Blanche, IPA, Ambrée...', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Différentes contenances', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Système "Nos suggestions"', 'restaurant-booking'); ?></li>
                </ul>
            </div>

            <!-- Statistiques rapides -->
            <div class="restaurant-booking-stats">
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><?php echo count($products); ?></h3>
                        <p><?php _e('Bières', 'restaurant-booking'); ?></p>
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
                        <h3><?php echo count(array_unique(array_column($products, 'beer_category'))); ?></h3>
                        <p><?php _e('Catégories', 'restaurant-booking'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Tableau des bières -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th class="manage-column"><?php _e('Bière', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Catégorie', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Degré/Volume', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Prix', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Suggestion', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Actions', 'restaurant-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px;">
                                <p><?php _e('Aucune bière configurée.', 'restaurant-booking'); ?></p>
                                <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-beers&action=add'); ?>" class="button button-primary">
                                    <?php _e('Créer la première bière', 'restaurant-booking'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($product['name']); ?></strong>
                                    <?php if ($product['description']): ?>
                                        <br><small class="description"><?php echo esc_html(wp_trim_words($product['description'], 10)); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="beer-category-badge">
                                        <?php echo esc_html($product['beer_category'] ?: 'Non classée'); ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo $product['alcohol_degree']; ?>°</strong>
                                    <br><small><?php echo $product['volume_cl']; ?> cl</small>
                                </td>
                                <td>
                                    <strong><?php echo number_format($product['price'], 2, ',', ' '); ?> €</strong>
                                </td>
                                <td>
                                    <?php if ($product['suggested_beverage']): ?>
                                        <span class="suggestion-yes">⭐</span>
                                    <?php else: ?>
                                        <span class="suggestion-no">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-beers&action=edit&product_id=' . $product['id']); ?>" 
                                       class="button button-small"><?php _e('Modifier', 'restaurant-booking'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <style>
        .restaurant-booking-info-card {
            background: #fff3e0;
            border: 1px solid #ffcc02;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }
        .restaurant-booking-info-card h3 {
            margin-top: 0;
            color: #ef6c00;
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
        .beer-category-badge {
            background: #fff3e0;
            color: #ef6c00;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 500;
        }
        .suggestion-yes { color: #ff9800; font-weight: bold; }
        .suggestion-no { color: #666; }
        .description { color: #666; font-style: italic; }
        </style>
        <?php
    }

    /**
     * Afficher le formulaire (version simplifiée)
     */
    public function display_form()
    {
        // Charger les scripts de la médiathèque WordPress
        wp_enqueue_media();
        
        $product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'add';
        $product = null;

        if ($product_id && $action === 'edit') {
            $product = RestaurantBooking_Product::get($product_id);
            if (!$product || $product['category_type'] !== 'biere_bouteille') {
                wp_die(__('Produit introuvable ou type incorrect.', 'restaurant-booking'));
            }
        }

        // Gérer la soumission du formulaire
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handle_save_beer();
            return;
        }

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                🍺 <?php echo $action === 'edit' ? __('Modifier la bière', 'restaurant-booking') : __('Ajouter une bière', 'restaurant-booking'); ?>
            </h1>
            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-beers'); ?>" class="page-title-action">
                ← <?php _e('Retour à la liste', 'restaurant-booking'); ?>
            </a>
            <hr class="wp-header-end">

            <div class="restaurant-booking-info-card">
                <h3><?php _e('Informations sur les bières', 'restaurant-booking'); ?></h3>
                <ul>
                    <li><?php _e('✓ Spécifiez le type de bière (Blonde, IPA, etc.)', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Indiquez le degré d\'alcool et le volume', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Ajoutez une image depuis la médiathèque WordPress', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Marquez comme "suggestion" pour la mettre en avant', 'restaurant-booking'); ?></li>
                </ul>
            </div>

            <form method="post" action="" id="beer-form">
                <?php wp_nonce_field('save_beer', 'beer_nonce'); ?>
                <input type="hidden" name="action" value="save_beer">
                <?php if ($product): ?>
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <?php endif; ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="beer_name"><?php _e('Nom de la bière', 'restaurant-booking'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" id="beer_name" name="beer_name" class="regular-text" 
                                   value="<?php echo $product ? esc_attr($product['name']) : ''; ?>" required>
                            <p class="description"><?php _e('Ex: Kronenbourg 1664, Leffe Blonde...', 'restaurant-booking'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="beer_description"><?php _e('Description', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <textarea id="beer_description" name="beer_description" class="large-text" rows="3"><?php echo $product ? esc_textarea($product['description']) : ''; ?></textarea>
                            <p class="description"><?php _e('Description courte de la bière', 'restaurant-booking'); ?></p>
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
                            <label for="alcohol_degree"><?php _e('Degré d\'alcool', 'restaurant-booking'); ?> *</label>
                        </th>
                        <td>
                            <input type="number" id="alcohol_degree" name="alcohol_degree" step="0.1" min="0" max="15" 
                                   value="<?php echo $product ? esc_attr($product['alcohol_degree']) : '5.0'; ?>" required>
                            <span>°</span>
                            <p class="description"><?php _e('Degré d\'alcool en pourcentage (ex: 5.0)', 'restaurant-booking'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="volume_cl"><?php _e('Volume', 'restaurant-booking'); ?> *</label>
                        </th>
                        <td>
                            <input type="number" id="volume_cl" name="volume_cl" min="1" max="1000" 
                                   value="<?php echo $product ? esc_attr($product['volume_cl']) : '25'; ?>" required>
                            <span>cl</span>
                            <p class="description"><?php _e('Volume en centilitres (ex: 25, 33, 50)', 'restaurant-booking'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="beer_price"><?php _e('Prix', 'restaurant-booking'); ?> *</label>
                        </th>
                        <td>
                            <input type="number" id="beer_price" name="beer_price" step="0.01" min="0" 
                                   value="<?php echo $product ? esc_attr($product['price']) : ''; ?>" required>
                            <span>€</span>
                            <p class="description"><?php _e('Prix de vente en euros', 'restaurant-booking'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="beer_image"><?php _e('Image de la bière', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <div class="image-upload-container">
                                <input type="hidden" id="beer_image_id" name="beer_image_id" 
                                       value="<?php echo $product ? esc_attr($product['image_id']) : ''; ?>">
                                <div id="beer_image_preview">
                                    <?php if ($product && $product['image_id']): ?>
                                        <?php echo wp_get_attachment_image($product['image_id'], 'medium'); ?>
                                    <?php endif; ?>
                                </div>
                                <p>
                                    <button type="button" class="button" id="upload_beer_image">
                                        <?php _e('Choisir une image', 'restaurant-booking'); ?>
                                    </button>
                                    <button type="button" class="button" id="remove_beer_image" style="<?php echo (!$product || !$product['image_id']) ? 'display:none;' : ''; ?>">
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

                <?php submit_button($action === 'edit' ? __('Mettre à jour la bière', 'restaurant-booking') : __('Ajouter la bière', 'restaurant-booking')); ?>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Gestionnaire pour l'upload d'image
            var mediaUploader;
            
            $('#upload_beer_image').on('click', function(e) {
                e.preventDefault();
                
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                
                mediaUploader = wp.media({
                    title: '<?php _e('Choisir une image pour la bière', 'restaurant-booking'); ?>',
                    button: {
                        text: '<?php _e('Utiliser cette image', 'restaurant-booking'); ?>'
                    },
                    multiple: false
                });
                
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#beer_image_id').val(attachment.id);
                    $('#beer_image_preview').html('<img src="' + attachment.sizes.medium.url + '" alt="" style="max-width: 200px;">');
                    $('#remove_beer_image').show();
                });
                
                mediaUploader.open();
            });
            
            $('#remove_beer_image').on('click', function(e) {
                e.preventDefault();
                $('#beer_image_id').val('');
                $('#beer_image_preview').empty();
                $(this).hide();
            });
        });
        </script>
        <?php
    }

    /**
     * Gérer la sauvegarde d'une bière
     */
    private function handle_save_beer()
    {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['beer_nonce'], 'save_beer')) {
            wp_die(__('Erreur de sécurité', 'restaurant-booking'));
        }

        // Récupérer les données
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $beer_name = sanitize_text_field($_POST['beer_name']);
        $beer_description = sanitize_textarea_field($_POST['beer_description']);
        $beer_category = sanitize_text_field($_POST['beer_category']);
        $alcohol_degree = floatval($_POST['alcohol_degree']);
        $volume_cl = intval($_POST['volume_cl']);
        $beer_price = floatval($_POST['beer_price']);
        $beer_image_id = intval($_POST['beer_image_id']);
        $suggested_beverage = isset($_POST['suggested_beverage']) ? 1 : 0;

        // Validation
        if (empty($beer_name) || $beer_price <= 0 || $volume_cl <= 0) {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-beers&action=add&error=validation'));
            exit;
        }

        // Obtenir la catégorie bière
        $category = RestaurantBooking_Category::get_by_type('biere_bouteille');
        if (!$category) {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-beers&action=add&error=no_category'));
            exit;
        }

        // Préparer les données du produit
        $product_data = array(
            'category_id' => $category['id'],
            'name' => $beer_name,
            'description' => $beer_description,
            'price' => $beer_price,
            'unit_type' => 'bouteille',
            'unit_label' => '/bouteille',
            'volume_cl' => $volume_cl,
            'alcohol_degree' => $alcohol_degree,
            'beer_category' => $beer_category,
            'image_id' => $beer_image_id ?: null,
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
        $redirect_url = admin_url('admin.php?page=restaurant-booking-beverages-beers&message=' . $success_param);
        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Obtenir les bières
     */
    private function get_beers()
    {
        global $wpdb;

        $products = $wpdb->get_results($wpdb->prepare("
            SELECT p.*, c.service_type
            FROM {$wpdb->prefix}restaurant_products p
            INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
            WHERE c.type = %s
            ORDER BY p.suggested_beverage DESC, p.beer_category ASC, p.name ASC
        ", 'biere_bouteille'), ARRAY_A);

        foreach ($products as &$product) {
            $product['price'] = (float) $product['price'];
            $product['alcohol_degree'] = (float) $product['alcohol_degree'];
            $product['volume_cl'] = (int) $product['volume_cl'];
            $product['suggested_beverage'] = (bool) $product['suggested_beverage'];
            $product['is_active'] = (bool) $product['is_active'];
        }

        return $products ?: array();
    }
}
