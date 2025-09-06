<?php
/**
 * Classe d'administration des Buffets Sucrés
 *
 * @package RestaurantBooking
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Products_BuffetSucre_Admin
{
    /**
     * Afficher la liste des buffets sucrés
     */
    public function display_list()
    {
        $products = $this->get_buffet_sucre_products();
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">🍽️ <?php _e('Buffet Sucré', 'restaurant-booking'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-products-buffet-sucre&action=add'); ?>" class="page-title-action">
                <?php _e('Ajouter un dessert de buffet', 'restaurant-booking'); ?>
            </a>
            <hr class="wp-header-end">

            <div class="restaurant-booking-info-card">
                <h3><?php _e('Règles du buffet sucré', 'restaurant-booking'); ?></h3>
                <ul>
                    <li><?php _e('✓ Minimum 1 recette requise', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Minimum 1 portion par personne', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Desserts et douceurs pour clôturer le repas', 'restaurant-booking'); ?></li>
                </ul>
            </div>

            <!-- Statistiques rapides -->
            <div class="restaurant-booking-stats">
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><?php echo count($products); ?></h3>
                        <p><?php _e('Desserts', 'restaurant-booking'); ?></p>
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
                        <th class="manage-column"><?php _e('Dessert', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Quantité/personne', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Prix', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Statut', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Actions', 'restaurant-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px;">
                                <p><?php _e('Aucun dessert de buffet configuré.', 'restaurant-booking'); ?></p>
                                <a href="<?php echo admin_url('admin.php?page=restaurant-booking-products-buffet-sucre&action=add'); ?>" class="button button-primary">
                                    <?php _e('Créer le premier dessert', 'restaurant-booking'); ?>
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
                                    <strong><?php echo esc_html($product['unit_per_person'] ?: '1 portion/pers'); ?></strong>
                                    <br><small><?php echo esc_html($product['unit_label']); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo number_format($product['price'], 2, ',', ' '); ?> €</strong>
                                    <br><small><?php echo esc_html($product['unit_label']); ?></small>
                                </td>
                                <td>
                                    <span class="product-status status-<?php echo $product['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $product['is_active'] ? __('Actif', 'restaurant-booking') : __('Inactif', 'restaurant-booking'); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=restaurant-booking-products-buffet-sucre&action=edit&product_id=' . $product['id']); ?>" 
                                       class="button button-small">
                                        <?php _e('Modifier', 'restaurant-booking'); ?>
                                    </a>
                                    <a href="#" class="button button-small button-link-delete" 
                                       onclick="return confirm('<?php _e('Êtes-vous sûr de vouloir supprimer ce dessert ?', 'restaurant-booking'); ?>')">
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
            background: #fce4ec;
            border: 1px solid #f8bbd9;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }
        .restaurant-booking-info-card h3 {
            margin-top: 0;
            color: #c2185b;
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
            if (!$product || $product['category_type'] !== 'buffet_sucre') {
                wp_die(__('Produit introuvable ou type incorrect.', 'restaurant-booking'));
            }
        }

        ?>
        <div class="wrap">
            <h1>
                🍽️ <?php echo $product ? __('Modifier le dessert', 'restaurant-booking') : __('Nouveau dessert de buffet', 'restaurant-booking'); ?>
            </h1>
            
            <form method="post" action="" enctype="multipart/form-data">
                <?php wp_nonce_field('restaurant_booking_product_buffet_sucre', 'product_buffet_sucre_nonce'); ?>
                <input type="hidden" name="restaurant_booking_action" value="save_product_buffet_sucre">
                <?php if ($product): ?>
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <?php endif; ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="product_name"><?php _e('Nom du dessert', 'restaurant-booking'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" id="product_name" name="product_name" 
                                   value="<?php echo $product ? esc_attr($product['name']) : ''; ?>" 
                                   class="regular-text" required>
                            <p class="description"><?php _e('Ex: Tiramisu, Tarte aux fruits, Assortiment de mignardises...', 'restaurant-booking'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="product_description"><?php _e('Description', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <textarea id="product_description" name="product_description" 
                                      rows="4" class="large-text"><?php echo $product ? esc_textarea($product['description']) : ''; ?></textarea>
                            <p class="description"><?php _e('Description du dessert et de sa composition.', 'restaurant-booking'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="unit_per_person"><?php _e('Quantité par personne', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="unit_per_person" name="unit_per_person" 
                                   value="<?php echo $product ? esc_attr($product['unit_per_person']) : '1 portion/pers'; ?>" 
                                   class="regular-text" placeholder="Ex: 1 portion/pers ou 2 pièces/pers">
                            <p class="description"><?php _e('Quantité recommandée par personne.', 'restaurant-booking'); ?></p>
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
                            <p class="description"><?php _e('Prix pour la quantité indiquée.', 'restaurant-booking'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="unit_label"><?php _e('Unité de vente', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <select id="unit_label" name="unit_label">
                                <option value="/6 personnes" <?php selected($product['unit_label'] ?? '', '/6 personnes'); ?>>
                                    <?php _e('Par 6 personnes', 'restaurant-booking'); ?>
                                </option>
                                <option value="/10 personnes" <?php selected($product['unit_label'] ?? '', '/10 personnes'); ?>>
                                    <?php _e('Par 10 personnes', 'restaurant-booking'); ?>
                                </option>
                                <option value="/portion" <?php selected($product['unit_label'] ?? '', '/portion'); ?>>
                                    <?php _e('Par portion individuelle', 'restaurant-booking'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="product_image"><?php _e('Image du dessert', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="product_image" name="product_image" 
                                   value="<?php echo $product ? esc_url($product['image_url']) : ''; ?>" 
                                   class="regular-text">
                            <p class="description"><?php _e('URL de l\'image du dessert (optionnel).', 'restaurant-booking'); ?></p>
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
                                <?php _e('Dessert actif (visible dans les formulaires)', 'restaurant-booking'); ?>
                            </label>
                        </td>
                    </tr>
                </table>

                <?php submit_button($product ? __('Mettre à jour le dessert', 'restaurant-booking') : __('Créer le dessert', 'restaurant-booking')); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Obtenir les produits de buffet sucré depuis la base de données
     */
    private function get_buffet_sucre_products()
    {
        global $wpdb;

        $products = $wpdb->get_results($wpdb->prepare("
            SELECT p.*, c.service_type
            FROM {$wpdb->prefix}restaurant_products p
            INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
            WHERE c.type = %s
            ORDER BY p.display_order ASC, p.name ASC
        ", 'buffet_sucre'), ARRAY_A);

        // Convertir les types
        foreach ($products as &$product) {
            $product['price'] = (float) $product['price'];
            $product['is_active'] = (bool) $product['is_active'];
        }

        return $products ?: array();
    }
}
