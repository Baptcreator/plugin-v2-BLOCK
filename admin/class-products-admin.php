<?php
/**
 * Classe d'administration des produits
 *
 * @package RestaurantBooking
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Products_Admin
{
    /**
     * Afficher la liste des produits
     */
    public function display_list()
    {
        // Récupérer les données
        $products = $this->get_sample_products();
        $categories = $this->get_sample_categories();

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Gestion des produits', 'restaurant-booking'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-products&action=add'); ?>" class="page-title-action">
                <?php _e('Ajouter un produit', 'restaurant-booking'); ?>
            </a>
            <hr class="wp-header-end">

            <!-- Statistiques rapides -->
            <div class="restaurant-booking-stats">
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><?php echo count($products); ?></h3>
                        <p><?php _e('Produits totaux', 'restaurant-booking'); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo count(array_filter($products, function($p) { return $p['is_active']; })); ?></h3>
                        <p><?php _e('Produits actifs', 'restaurant-booking'); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo count(array_unique(array_column($products, 'category_name'))); ?></h3>
                        <p><?php _e('Catégories', 'restaurant-booking'); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo number_format(array_sum(array_column($products, 'price')), 2, ',', ' '); ?> €</h3>
                        <p><?php _e('Valeur totale', 'restaurant-booking'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Tableau des produits -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th class="manage-column"><?php _e('Nom', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Catégorie', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Prix', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Service', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Stock', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Statut', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Actions', 'restaurant-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($product['name']); ?></strong>
                                <?php if ($product['description']): ?>
                                    <br><small style="color: #666;"><?php echo esc_html(wp_trim_words($product['description'], 10)); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="category-badge"><?php echo esc_html($product['category_name']); ?></span>
                            </td>
                            <td>
                                <strong><?php echo number_format($product['price'], 2, ',', ' '); ?> €</strong>
                                <?php if ($product['unit']): ?>
                                    <br><small>/ <?php echo esc_html($product['unit']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                switch($product['service_type']) {
                                    case 'restaurant': _e('Restaurant', 'restaurant-booking'); break;
                                    case 'remorque': _e('Remorque', 'restaurant-booking'); break;
                                    default: echo esc_html($product['service_type']);
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($product['manage_stock']): ?>
                                    <span class="stock-<?php echo $product['stock_quantity'] > 0 ? 'available' : 'out'; ?>">
                                        <?php echo $product['stock_quantity']; ?> <?php _e('en stock', 'restaurant-booking'); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="stock-unlimited"><?php _e('Illimité', 'restaurant-booking'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="product-status status-<?php echo $product['is_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $product['is_active'] ? __('Actif', 'restaurant-booking') : __('Inactif', 'restaurant-booking'); ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=restaurant-booking-products&action=edit&product_id=' . $product['id']); ?>" class="button button-small">
                                    <?php _e('Modifier', 'restaurant-booking'); ?>
                                </a>
                                <a href="#" class="button button-small button-link-delete" onclick="return confirm('<?php _e('Êtes-vous sûr de vouloir supprimer ce produit ?', 'restaurant-booking'); ?>')">
                                    <?php _e('Supprimer', 'restaurant-booking'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <style>
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
        .category-badge {
            background: #f0f6fc;
            color: #0073aa;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 500;
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
        .stock-available { color: #155724; }
        .stock-out { color: #721c24; }
        .stock-unlimited { color: #0073aa; }
        </style>
        <?php
    }

    /**
     * Données d'exemple pour les produits
     */
    private function get_sample_products()
    {
        return array(
            array(
                'id' => 1,
                'name' => 'Salade César',
                'description' => 'Salade fraîche avec croûtons, parmesan et sauce César maison',
                'category_name' => 'Entrées',
                'price' => 12.50,
                'unit' => 'portion',
                'service_type' => 'restaurant',
                'manage_stock' => false,
                'stock_quantity' => 0,
                'is_active' => true
            ),
            array(
                'id' => 2,
                'name' => 'Plateau de fromages',
                'description' => 'Sélection de fromages français avec pain et confiture',
                'category_name' => 'Plateaux',
                'price' => 25.00,
                'unit' => 'plateau',
                'service_type' => 'remorque',
                'manage_stock' => true,
                'stock_quantity' => 15,
                'is_active' => true
            ),
            array(
                'id' => 3,
                'name' => 'Cocktail de bienvenue',
                'description' => 'Cocktail maison pour accueillir vos invités',
                'category_name' => 'Boissons',
                'price' => 8.00,
                'unit' => 'verre',
                'service_type' => 'remorque',
                'manage_stock' => false,
                'stock_quantity' => 0,
                'is_active' => true
            ),
            array(
                'id' => 4,
                'name' => 'Magret de canard',
                'description' => 'Magret de canard aux figues avec légumes de saison',
                'category_name' => 'Plats principaux',
                'price' => 28.00,
                'unit' => 'portion',
                'service_type' => 'restaurant',
                'manage_stock' => true,
                'stock_quantity' => 0,
                'is_active' => false
            ),
            array(
                'id' => 5,
                'name' => 'Buffet desserts',
                'description' => 'Assortiment de desserts maison pour 10 personnes',
                'category_name' => 'Desserts',
                'price' => 85.00,
                'unit' => 'buffet',
                'service_type' => 'remorque',
                'manage_stock' => true,
                'stock_quantity' => 8,
                'is_active' => true
            )
        );
    }

    /**
     * Données d'exemple pour les catégories
     */
    private function get_sample_categories()
    {
        return array(
            array('id' => 1, 'name' => 'Entrées'),
            array('id' => 2, 'name' => 'Plats principaux'),
            array('id' => 3, 'name' => 'Desserts'),
            array('id' => 4, 'name' => 'Boissons'),
            array('id' => 5, 'name' => 'Plateaux')
        );
    }
}
