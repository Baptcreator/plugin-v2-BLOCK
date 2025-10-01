<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Administration des vins unifiée
 * 
 * Gère tous les vins dans une seule interface avec filtrage par sous-types
 * 
 * @package RestaurantBooking
 * @since 1.0.0
 */
class RestaurantBooking_Beverages_Wines_Unified_Admin
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'), 20);
    }

    public function add_admin_menu()
    {
        // Cette page remplace l'ancienne page wines
        // Elle sera appelée depuis le menu principal
    }

    /**
     * Afficher la page principale des vins
     */
    public function display_main_page()
    {
        // Gérer les actions (suppression, etc.)
        $this->handle_actions();
        
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $wine_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        switch ($action) {
            case 'add':
                $this->display_form();
                break;
            case 'edit':
                $this->display_form($wine_id);
                break;
            default:
                $this->display_list();
                break;
        }
    }

    /**
     * Afficher la liste des vins avec filtres
     */
    private function display_list()
    {
        global $wpdb;
        
        // Récupérer le filtre par type
        $filter_type = isset($_GET['filter_type']) ? sanitize_text_field($_GET['filter_type']) : '';
        
        // Récupérer tous les vins
        $where_clause = '';
        $params = [];
        
        if (!empty($filter_type)) {
            $where_clause = ' AND p.wine_category = %s';
            $params[] = $filter_type;
        }
        
        // CORRECTION : Utiliser la catégorie ID 112 selon l'analyse de la base de données
        $query = "
            SELECT p.*, c.name as category_name 
            FROM {$wpdb->prefix}restaurant_products p
            INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
            WHERE c.id = 112
            AND p.is_active = 1 AND c.is_active = 1 {$where_clause}
            ORDER BY p.wine_category ASC, p.display_order ASC, p.name ASC
        ";
        
        if (!empty($params)) {
            $wines = $wpdb->get_results($wpdb->prepare($query, ...$params));
        } else {
            $wines = $wpdb->get_results($query);
        }
        
        // CORRECTION : Récupérer tous les types de vins pour les filtres depuis la catégorie ID 112
        $wine_types = $wpdb->get_results("
            SELECT DISTINCT wine_category, COUNT(*) as count
            FROM {$wpdb->prefix}restaurant_products p
            INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
            WHERE c.id = 112
            AND p.is_active = 1 AND c.is_active = 1 AND p.wine_category IS NOT NULL
            GROUP BY wine_category
            ORDER BY wine_category ASC
        ");

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">🍷 <?php _e('Gestion des Vins', 'restaurant-booking'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-wines&action=add'); ?>" 
               class="page-title-action">
                <?php _e('Ajouter un Vin', 'restaurant-booking'); ?>
            </a>
            <hr class="wp-header-end">

            <!-- Filtres par type -->
            <div class="wine-filters">
                <div class="alignleft actions">
                    <select name="filter_type" id="filter_type">
                        <option value=""><?php _e('Tous les types', 'restaurant-booking'); ?></option>
                        <?php foreach ($wine_types as $type) : ?>
                            <option value="<?php echo esc_attr($type->wine_category); ?>" <?php selected($filter_type, $type->wine_category); ?>>
                                <?php echo esc_html(ucfirst($type->wine_category)); ?> (<?php echo $type->count; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="submit" id="doaction" class="button action" value="<?php _e('Filtrer', 'restaurant-booking'); ?>">
                    
                    <?php if (!empty($filter_type)) : ?>
                        <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-wines'); ?>" 
                           class="button"><?php _e('Voir tous', 'restaurant-booking'); ?></a>
                    <?php endif; ?>
                </div>
                
                <div class="alignright actions">
                    <a href="<?php echo admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=wines_group'); ?>" 
                       class="button button-secondary">
                        🏷️ <?php _e('Gérer les types de vins', 'restaurant-booking'); ?>
                    </a>
                </div>
                <br class="clear">
            </div>

            <?php if (empty($wines)) : ?>
                <div class="notice notice-info">
                    <p><?php _e('Aucun vin trouvé. Commencez par ajouter des vins à votre carte.', 'restaurant-booking'); ?></p>
                    <?php
                    // Vérifier si la migration est nécessaire
                    if (class_exists('RestaurantBooking_Migration_Restructure_Wine_Categories') && 
                        RestaurantBooking_Migration_Restructure_Wine_Categories::is_migration_needed()) : ?>
                        <p><strong><?php _e('Note :', 'restaurant-booking'); ?></strong> 
                           <?php _e('La restructuration des catégories de vins sera appliquée automatiquement lors du prochain chargement.', 'restaurant-booking'); ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php else : ?>
                
                <form method="post" id="wines-filter">
                    <?php wp_nonce_field('restaurant_booking_wines_action'); ?>
                    
                    <div class="tablenav top">
                        <div class="alignleft actions bulkactions">
                            <select name="action" id="bulk-action-selector-top">
                                <option value="-1"><?php _e('Actions groupées', 'restaurant-booking'); ?></option>
                                <option value="activate"><?php _e('Activer', 'restaurant-booking'); ?></option>
                                <option value="deactivate"><?php _e('Désactiver', 'restaurant-booking'); ?></option>
                                <option value="delete"><?php _e('Supprimer', 'restaurant-booking'); ?></option>
                            </select>
                            <?php submit_button(__('Appliquer', 'restaurant-booking'), 'action', '', false, array('id' => 'doaction')); ?>
                        </div>
                    </div>

                    <!-- Tableau des vins -->
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <td class="manage-column column-cb check-column">
                                    <input id="cb-select-all-1" type="checkbox">
                                </td>
                                <th scope="col" class="manage-column column-image"><?php _e('Image', 'restaurant-booking'); ?></th>
                                <th scope="col" class="manage-column column-name column-primary"><?php _e('Nom', 'restaurant-booking'); ?></th>
                                <th scope="col" class="manage-column column-type"><?php _e('Type', 'restaurant-booking'); ?></th>
                                <th scope="col" class="manage-column column-price"><?php _e('Prix', 'restaurant-booking'); ?></th>
                                <th scope="col" class="manage-column column-degree"><?php _e('Degré', 'restaurant-booking'); ?></th>
                                <th scope="col" class="manage-column column-order"><?php _e('Ordre', 'restaurant-booking'); ?></th>
                                <th scope="col" class="manage-column column-status"><?php _e('Statut', 'restaurant-booking'); ?></th>
                                <th scope="col" class="manage-column column-date"><?php _e('Date de création', 'restaurant-booking'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($wines as $wine) : ?>
                                <tr>
                                    <th scope="row" class="check-column">
                                        <input id="cb-select-<?php echo $wine->id; ?>" type="checkbox" name="wine_ids[]" value="<?php echo $wine->id; ?>">
                                    </th>
                                    <td class="column-image">
                                        <?php if (!empty($wine->image_url)) : ?>
                                            <img src="<?php echo esc_url($wine->image_url); ?>" alt="<?php echo esc_attr($wine->name); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                        <?php else : ?>
                                            <div style="width: 50px; height: 50px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #666;">
                                                <span class="dashicons dashicons-format-image"></span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="column-name column-primary">
                                        <strong>
                                            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-wines&action=edit&id=' . $wine->id); ?>">
                                                <?php echo esc_html($wine->name); ?>
                                            </a>
                                        </strong>
                                        <?php if ($wine->description) : ?>
                                            <div class="row-description"><?php echo esc_html(wp_trim_words($wine->description, 10)); ?></div>
                                        <?php endif; ?>
                                        <div class="row-actions">
                                            <span class="edit">
                                                <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-wines&action=edit&id=' . $wine->id); ?>">
                                                    <?php _e('Modifier', 'restaurant-booking'); ?>
                                                </a> |
                                            </span>
                                            <span class="toggle-status">
                                                <a href="#" class="toggle-wine-status" data-wine-id="<?php echo $wine->id; ?>" data-current-status="<?php echo $wine->is_active ? 1 : 0; ?>">
                                                    <?php echo $wine->is_active ? __('Désactiver', 'restaurant-booking') : __('Activer', 'restaurant-booking'); ?>
                                                </a> |
                                            </span>
                                            <span class="delete">
                                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=restaurant-booking-beverages-wines&action=delete&wine_id=' . $wine->id), 'delete_wine_' . $wine->id); ?>" 
                                                   class="button button-small button-link-delete" 
                                                   onclick="return confirm('<?php _e('Êtes-vous sûr de vouloir supprimer ce vin ?', 'restaurant-booking'); ?>')">
                                                    <?php _e('Supprimer', 'restaurant-booking'); ?>
                                                </a>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="column-type">
                                        <?php if ($wine->wine_category) : ?>
                                            <span class="wine-type-badge wine-type-<?php echo esc_attr($wine->wine_category); ?>">
                                                <?php echo esc_html(ucfirst($wine->wine_category)); ?>
                                            </span>
                                        <?php else : ?>
                                            <span class="description"><?php _e('Non défini', 'restaurant-booking'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="column-price">
                                        <strong><?php echo number_format($wine->price, 2, ',', ' '); ?> €</strong>
                                        <?php if ($wine->unit_label) : ?>
                                            <br><small><?php echo esc_html($wine->unit_label); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="column-degree">
                                        <?php if ($wine->alcohol_degree) : ?>
                                            <strong><?php echo $wine->alcohol_degree; ?>°</strong>
                                        <?php else : ?>
                                            <span class="description">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="column-order">
                                        <input type="number" class="small-text wine-order-input" 
                                               value="<?php echo esc_attr($wine->display_order ?? 0); ?>" 
                                               data-wine-id="<?php echo $wine->id; ?>" 
                                               min="0" step="1">
                                    </td>
                                    <td class="column-status">
                                        <?php if ($wine->is_active) : ?>
                                            <span class="status-active">✅ <?php _e('Actif', 'restaurant-booking'); ?></span>
                                        <?php else : ?>
                                            <span class="status-inactive">❌ <?php _e('Inactif', 'restaurant-booking'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="column-date">
                                        <?php echo $wine->created_at ? date_i18n(get_option('date_format'), strtotime($wine->created_at)) : '-'; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </form>
                
            <?php endif; ?>

            <style>
            .wine-filters {
                margin: 20px 0;
                padding: 10px;
                background: #f9f9f9;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            .wine-type-badge {
                padding: 3px 8px;
                border-radius: 3px;
                font-size: 12px;
                font-weight: bold;
                color: white;
            }
            .wine-type-blanc { background: #f1c40f; }
            .wine-type-rouge { background: #e74c3c; }
            .wine-type-rose { background: #ff69b4; }
            .wine-type-champagne { background: #ffd700; }
            .wine-type-badge:not([class*="wine-type-"]) { 
                background: #95a5a6; 
            }
            .description {
                font-size: 12px;
                color: #666;
                font-style: italic;
            }
            
            /* Styles pour correspondre aux boissons soft */
            .column-image {
                width: 60px;
            }
            
            .row-description {
                color: #666;
                font-size: 13px;
                margin-top: 4px;
            }
            
            .status-active {
                color: #46b450;
                font-weight: bold;
            }
            
            .status-inactive {
                color: #dc3232;
                font-weight: bold;
            }
            
            .wine-order-input {
                width: 60px;
            }
            
            .row-actions {
                visibility: hidden;
            }
            
            .column-primary:hover .row-actions {
                visibility: visible;
            }
            
            .column-cb {
                width: 2.2em;
            }
            </style>

            <script>
            jQuery(document).ready(function($) {
                $('#filter_type').on('change', function() {
                    var filterValue = $(this).val();
                    var baseUrl = '<?php echo admin_url('admin.php?page=restaurant-booking-beverages-wines'); ?>';
                    
                    if (filterValue) {
                        window.location.href = baseUrl + '&filter_type=' + encodeURIComponent(filterValue);
                    } else {
                        window.location.href = baseUrl;
                    }
                });
            });
            </script>
        </div>
        <?php
    }

    /**
     * Afficher le formulaire d'ajout/modification
     */
    private function display_form($wine_id = 0)
    {
        // Charger les scripts de la médiathèque WordPress
        wp_enqueue_media();
        
        $action = $wine_id ? 'edit' : 'add';
        $wine = null;

        if ($wine_id && $action === 'edit') {
            $wine = RestaurantBooking_Product::get($wine_id);
            if (!$wine) {
                wp_die(__('Vin introuvable.', 'restaurant-booking'));
            }
        }

        // Gérer la soumission du formulaire
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handle_save_wine();
            return;
        }

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                🍷 <?php echo $action === 'edit' ? __('Modifier le vin', 'restaurant-booking') : __('Ajouter un vin', 'restaurant-booking'); ?>
            </h1>
            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-wines'); ?>" class="page-title-action">
                ← <?php _e('Retour à la liste', 'restaurant-booking'); ?>
            </a>
            <hr class="wp-header-end">

            <div class="restaurant-booking-info-card">
                <h3><?php _e('Informations sur les vins', 'restaurant-booking'); ?></h3>
                <ul>
                    <li><?php _e('✓ Spécifiez le type de vin (Blanc, Rouge, Chardonnay, etc.)', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Indiquez le degré d\'alcool et le volume', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Ajoutez une image depuis la médiathèque WordPress', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Marquez comme "suggestion" pour le mettre en avant', 'restaurant-booking'); ?></li>
                </ul>
            </div>

            <form method="post" action="" id="wine-form">
                <?php wp_nonce_field('save_wine', 'wine_nonce'); ?>
                <input type="hidden" name="wine_id" value="<?php echo $wine_id; ?>">
                
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="wine_name"><?php _e('Nom du vin', 'restaurant-booking'); ?> *</label>
                            </th>
                            <td>
                                <input type="text" id="wine_name" name="wine_name" class="regular-text" 
                                       value="<?php echo $wine ? esc_attr($wine['name']) : ''; ?>" required>
                                <p class="description"><?php _e('Ex: Muscadet, Bordeaux Rouge, Chardonnay...', 'restaurant-booking'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="wine_description"><?php _e('Description', 'restaurant-booking'); ?></label>
                            </th>
                            <td>
                                <textarea id="wine_description" name="wine_description" class="large-text" rows="3"><?php echo $wine ? esc_textarea($wine['description']) : ''; ?></textarea>
                                <p class="description"><?php _e('Description du vin et conditions de service', 'restaurant-booking'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="wine_category"><?php _e('Type de vin', 'restaurant-booking'); ?> *</label>
                            </th>
                            <td>
                                <select id="wine_category" name="wine_category" class="regular-text" required>
                                    <option value=""><?php _e('Sélectionner un type', 'restaurant-booking'); ?></option>
                                    <?php 
                                    $wine_types = $this->get_wine_types();
                                    foreach ($wine_types as $type): ?>
                                        <option value="<?php echo esc_attr($type['category']); ?>" <?php selected($wine['wine_category'] ?? '', $type['category']); ?>>
                                            <?php echo esc_html($type['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php _e('Sélectionnez le type de vin. Vous pouvez gérer les types ci-dessous.', 'restaurant-booking'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label><?php _e('Gérer les types', 'restaurant-booking'); ?></label>
                            </th>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=wines_group'); ?>" 
                                   class="button button-secondary" target="_blank">
                                    🍷 <?php _e('Gérer les types de vins', 'restaurant-booking'); ?>
                                </a>
                                <p class="description"><?php _e('Ajoutez, modifiez ou supprimez les types de vins disponibles dans la liste ci-dessus.', 'restaurant-booking'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="alcohol_degree"><?php _e('Degré d\'alcool', 'restaurant-booking'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="alcohol_degree" name="alcohol_degree" class="small-text" 
                                       value="<?php echo $wine ? esc_attr($wine['alcohol_degree']) : ''; ?>" 
                                       min="0" max="50" step="0.1">
                                <span>°</span>
                                <p class="description"><?php _e('Degré d\'alcool en pourcentage (optionnel)', 'restaurant-booking'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="volume_cl"><?php _e('Volume', 'restaurant-booking'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="volume_cl" name="volume_cl" class="small-text" 
                                       value="<?php echo $wine ? esc_attr($wine['volume_cl']) : '75'; ?>" 
                                       min="1" max="1000">
                                <span>cl</span>
                                <p class="description"><?php _e('Volume de la bouteille en centilitres', 'restaurant-booking'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="wine_price"><?php _e('Prix', 'restaurant-booking'); ?> *</label>
                            </th>
                            <td>
                                <input type="number" id="wine_price" name="wine_price" class="regular-text" 
                                       value="<?php echo $wine ? esc_attr($wine['price']) : ''; ?>" 
                                       min="0" step="0.01" required>
                                <span>€</span>
                                <p class="description"><?php _e('Prix de vente du vin', 'restaurant-booking'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="wine_image"><?php _e('Image du vin', 'restaurant-booking'); ?></label>
                            </th>
                            <td>
                                <input type="hidden" id="wine_image_id" name="wine_image_id" value="<?php echo $wine ? esc_attr($wine['image_id']) : ''; ?>">
                                <div id="wine_image_preview">
                                    <?php if ($wine && $wine['image_id']): ?>
                                        <?php $image_url = wp_get_attachment_image_url($wine['image_id'], 'medium'); ?>
                                        <?php if ($image_url): ?>
                                            <img src="<?php echo esc_url($image_url); ?>" alt="" style="max-width: 200px;">
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                <p>
                                    <button type="button" id="upload_wine_image" class="button"><?php _e('Choisir une image', 'restaurant-booking'); ?></button>
                                    <button type="button" id="remove_wine_image" class="button" <?php echo (!$wine || !$wine['image_id']) ? 'style="display:none;"' : ''; ?>><?php _e('Supprimer l\'image', 'restaurant-booking'); ?></button>
                                </p>
                                <p class="description"><?php _e('Image du vin (recommandé 300x300px).', 'restaurant-booking'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="suggested_beverage"><?php _e('Suggestion', 'restaurant-booking'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="suggested_beverage" name="suggested_beverage" value="1" 
                                       <?php checked($wine['suggested_beverage'] ?? 0, 1); ?>>
                                <label for="suggested_beverage"><?php _e('Mettre en avant ce vin', 'restaurant-booking'); ?></label>
                                <p class="description"><?php _e('Les vins suggérés apparaîtront en premier dans les listes.', 'restaurant-booking'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" class="button-primary" 
                           value="<?php echo $action === 'edit' ? __('Mettre à jour le vin', 'restaurant-booking') : __('Ajouter le vin', 'restaurant-booking'); ?>">
                    <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-wines'); ?>" 
                       class="button button-secondary">
                        <?php _e('Annuler', 'restaurant-booking'); ?>
                    </a>
                </p>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($) {
            var wineImageUploader;
            
            // Sélecteur d'images WordPress
            $('#upload_wine_image').on('click', function(e) {
                e.preventDefault();
                
                if (wineImageUploader) {
                    wineImageUploader.open();
                    return;
                }
                
                wineImageUploader = wp.media({
                    title: '<?php _e('Choisir une image pour le vin', 'restaurant-booking'); ?>',
                    button: {
                        text: '<?php _e('Utiliser cette image', 'restaurant-booking'); ?>'
                    },
                    multiple: false
                });
                
                wineImageUploader.on('select', function() {
                    var attachment = wineImageUploader.state().get('selection').first().toJSON();
                    $('#wine_image_id').val(attachment.id);
                    $('#wine_image_preview').html('<img src="' + attachment.sizes.medium.url + '" alt="" style="max-width: 200px;">');
                    $('#remove_wine_image').show();
                });
                
                wineImageUploader.open();
            });
            
            $('#remove_wine_image').on('click', function(e) {
                e.preventDefault();
                $('#wine_image_id').val('');
                $('#wine_image_preview').empty();
                $(this).hide();
            });
        });
        </script>
        <?php
    }

    /**
     * Récupérer les types de vins disponibles
     */
    private function get_wine_types()
    {
        global $wpdb;
        
        // CORRECTION : Récupérer depuis la nouvelle table wp_restaurant_wine_types en priorité
        $wine_types_table = $wpdb->prefix . 'restaurant_wine_types';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$wine_types_table'");
        
        if ($table_exists) {
            // Utiliser la nouvelle table wp_restaurant_wine_types
            $existing_types = $wpdb->get_results("
                SELECT slug as category, name
                FROM $wine_types_table
                WHERE is_active = 1
                ORDER BY display_order ASC, name ASC
            ", ARRAY_A);
            
            if (!empty($existing_types)) {
                return $existing_types;
            }
        }
        
        // Fallback : Récupérer depuis les produits existants de la catégorie ID 112
        $existing_types = $wpdb->get_results("
            SELECT DISTINCT wine_category as category, wine_category as name
            FROM {$wpdb->prefix}restaurant_products p
            INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
            WHERE c.id = 112
            AND p.wine_category IS NOT NULL AND p.wine_category != '' AND c.is_active = 1
            ORDER BY wine_category ASC
        ", ARRAY_A);
        
        // Si pas de types existants, retourner les types par défaut
        if (empty($existing_types)) {
            return array(
                array('category' => 'blanc', 'name' => __('Blanc', 'restaurant-booking')),
                array('category' => 'rouge', 'name' => __('Rouge', 'restaurant-booking')),
                array('category' => 'rose', 'name' => __('Rosé', 'restaurant-booking')),
                array('category' => 'champagne', 'name' => __('Champagne', 'restaurant-booking'))
            );
        }
        
        // Formatter les noms
        foreach ($existing_types as &$type) {
            $type['name'] = ucfirst($type['name']);
        }
        
        return $existing_types;
    }

    /**
     * Gérer la sauvegarde du vin
     */
    public function handle_save_wine()
    {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['wine_nonce'], 'save_wine')) {
            wp_die(__('Erreur de sécurité', 'restaurant-booking'));
        }

        // Récupérer les données
        $wine_id = isset($_POST['wine_id']) ? intval($_POST['wine_id']) : 0;
        $wine_name = sanitize_text_field($_POST['wine_name']);
        $wine_description = sanitize_textarea_field($_POST['wine_description']);
        $wine_category = sanitize_text_field($_POST['wine_category']);
        $alcohol_degree = floatval($_POST['alcohol_degree']);
        $volume_cl = intval($_POST['volume_cl']);
        $wine_price = floatval($_POST['wine_price']);
        $wine_image_id = intval($_POST['wine_image_id']);
        $suggested_beverage = isset($_POST['suggested_beverage']) ? 1 : 0;

        // Validation
        if (empty($wine_name) || empty($wine_category) || $wine_price <= 0) {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-wines&action=' . ($wine_id ? 'edit' : 'add') . '&error=validation'));
            exit;
        }

        // CORRECTION : Utiliser directement la catégorie ID 112 selon l'analyse
        global $wpdb;
        $category = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}restaurant_categories WHERE id = 112 AND is_active = 1");
        
        if (!$category) {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-wines&action=' . ($wine_id ? 'edit' : 'add') . '&error=no_category'));
            exit;
        }

        // Préparer les données du produit
        $product_data = array(
            'category_id' => $category->id,
            'name' => $wine_name,
            'description' => $wine_description,
            'price' => $wine_price,
            'unit_type' => 'bouteille',
            'unit_label' => '/bouteille',
            'image_id' => $wine_image_id ?: null,
            'alcohol_degree' => $alcohol_degree ?: null,
            'volume_cl' => $volume_cl ?: null,
            'wine_category' => $wine_category,
            'suggested_beverage' => $suggested_beverage,
            'is_active' => 1,
        );

        if ($wine_id) {
            // Mise à jour
            $result = RestaurantBooking_Product::update($wine_id, $product_data);
            $redirect_action = 'updated';
        } else {
            // Création
            $result = RestaurantBooking_Product::create($product_data);
            $redirect_action = 'created';
        }

        if (is_wp_error($result)) {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-wines&action=' . ($wine_id ? 'edit' : 'add') . '&error=save_failed'));
            exit;
        }

        wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-wines&message=' . $redirect_action));
        exit;
    }

    /**
     * Gérer les actions (suppression, etc.)
     */
    public function handle_actions()
    {
        if (!isset($_GET['action']) || !isset($_GET['wine_id'])) {
            return;
        }

        $action = sanitize_text_field($_GET['action']);
        $wine_id = (int) $_GET['wine_id'];

        switch ($action) {
            case 'delete':
                $this->delete_wine($wine_id);
                break;
        }
    }

    /**
     * Supprimer un vin
     */
    private function delete_wine($wine_id)
    {
        if (!wp_verify_nonce($_GET['_wpnonce'], 'delete_wine_' . $wine_id)) {
            wp_die(__('Action non autorisée.', 'restaurant-booking'));
        }

        if (!current_user_can('manage_restaurant_quotes')) {
            wp_die(__('Permissions insuffisantes.', 'restaurant-booking'));
        }

        $result = RestaurantBooking_Product::delete($wine_id);
        
        if (is_wp_error($result)) {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-wines&message=error&error=' . urlencode($result->get_error_message())));
        } else {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-wines&message=deleted'));
        }
        exit;
    }
}
