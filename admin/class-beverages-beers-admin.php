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
        echo '<div class="wrap">';
        echo '<h1>🍷 ' . __('Formulaire bière en cours de développement', 'restaurant-booking') . '</h1>';
        echo '<p><a href="' . admin_url('admin.php?page=restaurant-booking-beverages-beers') . '" class="button">← ' . __('Retour à la liste', 'restaurant-booking') . '</a></p>';
        echo '</div>';
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
