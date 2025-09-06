<?php
/**
 * Classe d'administration des Options Remorque
 *
 * @package RestaurantBooking
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Options_Remorque_Admin
{
    /**
     * Afficher la liste des options remorque
     */
    public function display_list()
    {
        $products = $this->get_remorque_options();
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">🔢 <?php _e('Options Remorque', 'restaurant-booking'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-options-remorque&action=add'); ?>" class="page-title-action">
                <?php _e('Ajouter une option', 'restaurant-booking'); ?>
            </a>
            <hr class="wp-header-end">

            <div class="restaurant-booking-info-card">
                <h3><?php _e('Options spécifiques à la remorque', 'restaurant-booking'); ?></h3>
                <ul>
                    <li><?php _e('✓ Mise à disposition tireuse (50€) - Fûts non inclus', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Installation jeux (70€) - Jeux gonflables', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Autres services spécifiques remorque', 'restaurant-booking'); ?></li>
                </ul>
            </div>

            <!-- Tableau des options -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th class="manage-column"><?php _e('Option', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Prix', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Statut', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Actions', 'restaurant-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 40px;">
                                <p><?php _e('Aucune option remorque configurée.', 'restaurant-booking'); ?></p>
                                <div style="margin-top: 15px;">
                                    <p><strong><?php _e('Options suggérées à créer :', 'restaurant-booking'); ?></strong></p>
                                    <ul style="text-align: left; display: inline-block;">
                                        <li><?php _e('Mise à disposition tireuse (50€)', 'restaurant-booking'); ?></li>
                                        <li><?php _e('Installation jeux (70€)', 'restaurant-booking'); ?></li>
                                    </ul>
                                </div>
                                <a href="<?php echo admin_url('admin.php?page=restaurant-booking-options-remorque&action=add'); ?>" class="button button-primary">
                                    <?php _e('Créer la première option', 'restaurant-booking'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($product['name']); ?></strong>
                                    <?php if ($product['description']): ?>
                                        <br><small class="description"><?php echo esc_html($product['description']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo number_format($product['price'], 2, ',', ' '); ?> €</strong>
                                </td>
                                <td>
                                    <span class="product-status status-<?php echo $product['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $product['is_active'] ? __('Active', 'restaurant-booking') : __('Inactive', 'restaurant-booking'); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=restaurant-booking-options-remorque&action=edit&product_id=' . $product['id']); ?>" 
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
            background: #fff8e1;
            border: 1px solid #ffcc02;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }
        .restaurant-booking-info-card h3 {
            margin-top: 0;
            color: #f57f17;
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
        echo '<h1>🔢 ' . __('Formulaire option remorque en cours de développement', 'restaurant-booking') . '</h1>';
        echo '<p><a href="' . admin_url('admin.php?page=restaurant-booking-options-remorque') . '" class="button">← ' . __('Retour à la liste', 'restaurant-booking') . '</a></p>';
        echo '</div>';
    }

    /**
     * Obtenir les options remorque
     */
    private function get_remorque_options()
    {
        global $wpdb;

        $products = $wpdb->get_results($wpdb->prepare("
            SELECT p.*, c.service_type
            FROM {$wpdb->prefix}restaurant_products p
            INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
            WHERE c.type = %s
            ORDER BY p.display_order ASC, p.name ASC
        ", 'option_remorque'), ARRAY_A);

        foreach ($products as &$product) {
            $product['price'] = (float) $product['price'];
            $product['is_active'] = (bool) $product['is_active'];
        }

        return $products ?: array();
    }
}
