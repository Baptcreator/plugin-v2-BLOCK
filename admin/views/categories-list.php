<?php
/**
 * Vue de liste des catégories
 *
 * @package RestaurantBooking
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Variables disponibles: $categories, $total_pages, $args
?>

<div class="wrap restaurant-booking-admin-page">
    <div class="restaurant-booking-admin-header">
        <h1><?php _e('Catégories', 'restaurant-booking'); ?></h1>
        <div class="restaurant-booking-admin-actions">
            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-categories&action=add'); ?>" 
               class="button button-primary">
                <?php _e('Ajouter une catégorie', 'restaurant-booking'); ?>
            </a>
        </div>
    </div>

    <?php if (isset($_GET['message']) && $_GET['message'] === 'saved'): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Catégorie sauvegardée avec succès', 'restaurant-booking'); ?></p>
        </div>
    <?php endif; ?>

    <!-- Filtres -->
    <div class="restaurant-booking-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="restaurant-booking-categories">
            
            <div class="restaurant-booking-filters-row">
                <div class="restaurant-booking-form-group">
                    <label for="search"><?php _e('Recherche', 'restaurant-booking'); ?></label>
                    <input type="text" id="search" name="search" 
                           value="<?php echo esc_attr($args['search']); ?>"
                           placeholder="<?php _e('Nom ou description...', 'restaurant-booking'); ?>">
                </div>

                <div class="restaurant-booking-form-group">
                    <label for="service_type"><?php _e('Type de service', 'restaurant-booking'); ?></label>
                    <select id="service_type" name="service_type">
                        <option value=""><?php _e('Tous les services', 'restaurant-booking'); ?></option>
                        <option value="restaurant" <?php selected($args['service_type'], 'restaurant'); ?>>
                            <?php _e('Restaurant uniquement', 'restaurant-booking'); ?>
                        </option>
                        <option value="remorque" <?php selected($args['service_type'], 'remorque'); ?>>
                            <?php _e('Remorque uniquement', 'restaurant-booking'); ?>
                        </option>
                        <option value="both" <?php selected($args['service_type'], 'both'); ?>>
                            <?php _e('Les deux services', 'restaurant-booking'); ?>
                        </option>
                    </select>
                </div>

                <div class="restaurant-booking-form-group">
                    <label for="is_active"><?php _e('Statut', 'restaurant-booking'); ?></label>
                    <select id="is_active" name="is_active">
                        <option value=""><?php _e('Tous les statuts', 'restaurant-booking'); ?></option>
                        <option value="1" <?php selected($args['is_active'], '1'); ?>>
                            <?php _e('Actif', 'restaurant-booking'); ?>
                        </option>
                        <option value="0" <?php selected($args['is_active'], '0'); ?>>
                            <?php _e('Inactif', 'restaurant-booking'); ?>
                        </option>
                    </select>
                </div>

                <div class="restaurant-booking-filters-actions">
                    <button type="submit" class="button"><?php _e('Filtrer', 'restaurant-booking'); ?></button>
                    <a href="<?php echo admin_url('admin.php?page=restaurant-booking-categories'); ?>" 
                       class="button"><?php _e('Réinitialiser', 'restaurant-booking'); ?></a>
                </div>
            </div>
        </form>
    </div>

    <!-- Liste des catégories -->
    <?php if (!empty($categories)): ?>
        <form method="post" action="">
            <?php wp_nonce_field('bulk_categories'); ?>
            
            <div class="restaurant-booking-table">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td id="cb" class="manage-column column-cb check-column">
                                <input id="cb-select-all" type="checkbox">
                            </td>
                            <th class="manage-column column-name">
                                <?php _e('Nom', 'restaurant-booking'); ?>
                            </th>
                            <th class="manage-column column-type">
                                <?php _e('Type', 'restaurant-booking'); ?>
                            </th>
                            <th class="manage-column column-service">
                                <?php _e('Service', 'restaurant-booking'); ?>
                            </th>
                            <th class="manage-column column-products">
                                <?php _e('Produits', 'restaurant-booking'); ?>
                            </th>
                            <th class="manage-column column-constraints">
                                <?php _e('Contraintes', 'restaurant-booking'); ?>
                            </th>
                            <th class="manage-column column-status">
                                <?php _e('Statut', 'restaurant-booking'); ?>
                            </th>
                            <th class="manage-column column-actions">
                                <?php _e('Actions', 'restaurant-booking'); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="categories[]" value="<?php echo $category['id']; ?>">
                                </th>
                                <td class="column-name">
                                    <strong><?php echo esc_html($category['name']); ?></strong>
                                    <div class="row-actions">
                                        <span class="edit">
                                            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-categories&action=edit&category_id=' . $category['id']); ?>">
                                                <?php _e('Modifier', 'restaurant-booking'); ?>
                                            </a> |
                                        </span>
                                        <span class="duplicate">
                                            <a href="#" data-rb-action="duplicate_category" 
                                               data-rb-data='{"category_id": <?php echo $category['id']; ?>}'>
                                                <?php _e('Dupliquer', 'restaurant-booking'); ?>
                                            </a> |
                                        </span>
                                        <span class="delete">
                                            <a href="#" data-rb-action="delete_category" 
                                               data-rb-data='{"category_id": <?php echo $category['id']; ?>}'
                                               data-rb-confirm="<?php _e('Êtes-vous sûr de vouloir supprimer cette catégorie ?', 'restaurant-booking'); ?>"
                                               class="submitdelete">
                                                <?php _e('Supprimer', 'restaurant-booking'); ?>
                                            </a>
                                        </span>
                                    </div>
                                </td>
                                <td class="column-type">
                                    <span class="category-type-badge type-<?php echo $category['type']; ?>">
                                        <?php 
                                        $types = RestaurantBooking_Category::get_available_types();
                                        echo esc_html($types[$category['type']] ?? $category['type']);
                                        ?>
                                    </span>
                                </td>
                                <td class="column-service">
                                    <span class="service-badge service-<?php echo $category['service_type']; ?>">
                                        <?php
                                        $service_labels = array(
                                            'restaurant' => __('Restaurant', 'restaurant-booking'),
                                            'remorque' => __('Remorque', 'restaurant-booking'),
                                            'both' => __('Les deux', 'restaurant-booking')
                                        );
                                        echo $service_labels[$category['service_type']];
                                        ?>
                                    </span>
                                </td>
                                <td class="column-products">
                                    <strong><?php echo $category['product_count']; ?></strong>
                                    <?php if ($category['product_count'] > 0): ?>
                                        <br><a href="<?php echo admin_url('admin.php?page=restaurant-booking-products&category_id=' . $category['id']); ?>">
                                            <?php _e('Voir les produits', 'restaurant-booking'); ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td class="column-constraints">
                                    <?php if ($category['is_required']): ?>
                                        <span class="constraint-badge required">
                                            <?php _e('Obligatoire', 'restaurant-booking'); ?>
                                        </span><br>
                                    <?php endif; ?>
                                    
                                    <?php if ($category['min_selection'] > 0): ?>
                                        <small><?php printf(__('Min: %d', 'restaurant-booking'), $category['min_selection']); ?></small><br>
                                    <?php endif; ?>
                                    
                                    <?php if ($category['max_selection']): ?>
                                        <small><?php printf(__('Max: %d', 'restaurant-booking'), $category['max_selection']); ?></small><br>
                                    <?php endif; ?>
                                    
                                    <?php if ($category['min_per_person']): ?>
                                        <small><?php _e('1 min/pers.', 'restaurant-booking'); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="column-status">
                                    <?php if ($category['is_active']): ?>
                                        <span class="status-badge status-active"><?php _e('Actif', 'restaurant-booking'); ?></span>
                                    <?php else: ?>
                                        <span class="status-badge status-inactive"><?php _e('Inactif', 'restaurant-booking'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="column-actions">
                                    <a href="<?php echo admin_url('admin.php?page=restaurant-booking-categories&action=edit&category_id=' . $category['id']); ?>" 
                                       class="button button-small">
                                        <?php _e('Modifier', 'restaurant-booking'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Actions groupées -->
            <div class="tablenav bottom">
                <div class="alignleft actions bulkactions">
                    <select name="action">
                        <option value="-1"><?php _e('Actions groupées', 'restaurant-booking'); ?></option>
                        <option value="bulk_delete"><?php _e('Supprimer', 'restaurant-booking'); ?></option>
                        <option value="bulk_activate"><?php _e('Activer', 'restaurant-booking'); ?></option>
                        <option value="bulk_deactivate"><?php _e('Désactiver', 'restaurant-booking'); ?></option>
                    </select>
                    <input type="submit" class="button action" value="<?php _e('Appliquer', 'restaurant-booking'); ?>">
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="tablenav-pages">
                        <?php
                        $current_page = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
                        
                        echo paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'current' => $current_page,
                            'total' => $total_pages,
                            'type' => 'plain'
                        ));
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </form>

    <?php else: ?>
        <div class="restaurant-booking-widget">
            <div style="text-align: center; padding: 40px;">
                <h3><?php _e('Aucune catégorie trouvée', 'restaurant-booking'); ?></h3>
                <p><?php _e('Commencez par créer votre première catégorie de produits.', 'restaurant-booking'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=restaurant-booking-categories&action=add'); ?>" 
                   class="button button-primary button-large">
                    <?php _e('Créer une catégorie', 'restaurant-booking'); ?>
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.category-type-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.type-plat_signature { background: #e8f5e8; color: #2e7d32; }
.type-mini_boss { background: #fff3e0; color: #f57c00; }
.type-accompagnement { background: #f3e5f5; color: #7b1fa2; }
.type-buffet_sale { background: #e3f2fd; color: #1976d2; }
.type-buffet_sucre { background: #fce4ec; color: #c2185b; }
.type-soft { background: #e0f2f1; color: #00695c; }
.type-vin_blanc { background: #f9fbe7; color: #827717; }
.type-vin_rouge { background: #ffebee; color: #c62828; }
.type-vin_rose { background: #fce4ec; color: #ad1457; }
.type-cremant { background: #fff8e1; color: #ff8f00; }
.type-biere { background: #fff3e0; color: #ef6c00; }
.type-fut { background: #efebe9; color: #5d4037; }

.constraint-badge {
    display: inline-block;
    padding: 2px 6px;
    border-radius: 8px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
}

.constraint-badge.required {
    background: #ffebee;
    color: #c62828;
}

.status-badge.status-active {
    background: #e8f5e8;
    color: #2e7d32;
}

.status-badge.status-inactive {
    background: #f5f5f5;
    color: #666;
}
</style>
