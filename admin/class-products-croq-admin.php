<?php
/**
 * Classe d'administration des Plats Signature CROQ
 *
 * @package RestaurantBooking
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Products_Croq_Admin
{
    /**
     * Afficher la liste des plats signature CROQ
     */
    public function display_list()
    {
        $products = $this->get_croq_products();
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">🍽️ <?php _e('Plats Signature CROQ', 'restaurant-booking'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-products-croq&action=add'); ?>" class="page-title-action">
                <?php _e('Ajouter un plat CROQ', 'restaurant-booking'); ?>
            </a>
            <hr class="wp-header-end">

            <div class="restaurant-booking-info-card">
                <h3><?php _e('Caractéristiques des plats signature CROQ', 'restaurant-booking'); ?></h3>
                <ul>
                    <li><?php _e('✓ Minimum 1 plat par personne requis', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Disponible pour Restaurant et/ou Remorque', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Possibilité d\'ajouter des suppléments', 'restaurant-booking'); ?></li>
                </ul>
            </div>

            <!-- Statistiques rapides -->
            <div class="restaurant-booking-stats">
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><?php echo count($products); ?></h3>
                        <p><?php _e('Plats CROQ', 'restaurant-booking'); ?></p>
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
                        <th class="manage-column"><?php _e('Service', 'restaurant-booking'); ?></th>
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
                                <p><?php _e('Aucun plat signature CROQ configuré.', 'restaurant-booking'); ?></p>
                                <a href="<?php echo admin_url('admin.php?page=restaurant-booking-products-croq&action=add'); ?>" class="button button-primary">
                                    <?php _e('Créer le premier plat CROQ', 'restaurant-booking'); ?>
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
                                    <span class="service-badge service-<?php echo esc_attr($product['service_type']); ?>">
                                        <?php 
                                        switch($product['service_type']) {
                                            case 'restaurant': _e('Restaurant', 'restaurant-booking'); break;
                                            case 'remorque': _e('Remorque', 'restaurant-booking'); break;
                                            case 'both': _e('Les deux', 'restaurant-booking'); break;
                                            default: echo esc_html($product['service_type']);
                                        }
                                        ?>
                                    </span>
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
                                    <a href="<?php echo admin_url('admin.php?page=restaurant-booking-products-croq&action=edit&product_id=' . $product['id']); ?>" 
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
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }
        .restaurant-booking-info-card h3 {
            margin-top: 0;
            color: #856404;
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
        .service-badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 500;
            text-transform: uppercase;
        }
        .service-restaurant { background: #d1ecf1; color: #0c5460; }
        .service-remorque { background: #f8d7da; color: #721c24; }
        .service-both { background: #d4edda; color: #155724; }
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
        $product_id = isset($_GET['product_id']) ? (int) $_GET['product_id'] : 0;
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'add';
        $product = null;

        if ($product_id && $action === 'edit') {
            $product = RestaurantBooking_Product::get($product_id);
            if (!$product || $product['category_type'] !== 'plat_signature_croq') {
                wp_die(__('Produit introuvable ou type incorrect.', 'restaurant-booking'));
            }
        }

        ?>
        <div class="wrap">
            <h1>
                🍽️ <?php echo $product ? __('Modifier le plat CROQ', 'restaurant-booking') : __('Nouveau plat CROQ', 'restaurant-booking'); ?>
            </h1>
            
            <form method="post" action="" enctype="multipart/form-data">
                <?php wp_nonce_field('restaurant_booking_product_croq', 'product_croq_nonce'); ?>
                <input type="hidden" name="restaurant_booking_action" value="save_product_croq">
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
                            <p class="description"><?php _e('Ex: Croque-Monsieur Traditionnel, Croque-Madame...', 'restaurant-booking'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="product_description"><?php _e('Description (recette)', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <textarea id="product_description" name="product_description" 
                                      rows="4" class="large-text"><?php echo $product ? esc_textarea($product['description']) : ''; ?></textarea>
                            <p class="description"><?php _e('Description détaillée de la recette et des ingrédients.', 'restaurant-booking'); ?></p>
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
                            <p class="description"><?php _e('Prix unitaire du plat.', 'restaurant-booking'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="service_type"><?php _e('Service disponible', 'restaurant-booking'); ?> *</label>
                        </th>
                        <td>
                            <select id="service_type" name="service_type" required>
                                <option value="restaurant" <?php selected($product['service_type'] ?? '', 'restaurant'); ?>>
                                    <?php _e('Restaurant uniquement', 'restaurant-booking'); ?>
                                </option>
                                <option value="remorque" <?php selected($product['service_type'] ?? '', 'remorque'); ?>>
                                    <?php _e('Remorque uniquement', 'restaurant-booking'); ?>
                                </option>
                                <option value="both" <?php selected($product['service_type'] ?? 'both', 'both'); ?>>
                                    <?php _e('Les deux services', 'restaurant-booking'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="has_supplement"><?php _e('Supplément disponible', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="has_supplement" name="has_supplement" value="1" 
                                       <?php checked($product['has_supplement'] ?? false); ?>>
                                <?php _e('Ce plat peut avoir un supplément', 'restaurant-booking'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr id="supplement_details" style="<?php echo (!$product || !$product['has_supplement']) ? 'display: none;' : ''; ?>">
                        <th scope="row">
                            <label for="supplement_name"><?php _e('Détails du supplément', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="supplement_name" name="supplement_name" 
                                   value="<?php echo $product ? esc_attr($product['supplement_name']) : ''; ?>" 
                                   class="regular-text" placeholder="<?php _e('Ex: Œuf au plat', 'restaurant-booking'); ?>">
                            <br><br>
                            <input type="number" id="supplement_price" name="supplement_price" 
                                   value="<?php echo $product ? $product['supplement_price'] : ''; ?>" 
                                   step="0.01" min="0" class="small-text"> €
                            <p class="description"><?php _e('Nom et prix du supplément optionnel.', 'restaurant-booking'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="product_image"><?php _e('Image du plat', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="product_image" name="product_image" 
                                   value="<?php echo $product ? esc_url($product['image_url']) : ''; ?>" 
                                   class="regular-text">
                            <p class="description"><?php _e('URL de l\'image du plat (optionnel).', 'restaurant-booking'); ?></p>
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

        <script>
        document.getElementById('has_supplement').addEventListener('change', function() {
            const supplementDetails = document.getElementById('supplement_details');
            supplementDetails.style.display = this.checked ? 'table-row' : 'none';
        });
        </script>
        <?php
    }

    /**
     * Obtenir les produits CROQ depuis la base de données
     */
    private function get_croq_products()
    {
        global $wpdb;

        $products = $wpdb->get_results($wpdb->prepare("
            SELECT p.*, c.service_type
            FROM {$wpdb->prefix}restaurant_products p
            INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
            WHERE c.type = %s
            ORDER BY p.display_order ASC, p.name ASC
        ", 'plat_signature_croq'), ARRAY_A);

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
