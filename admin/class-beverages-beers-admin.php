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
        // Gérer les actions (suppression, etc.)
        $this->handle_actions();
        
        $products = $this->get_beers();
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">🍷 <?php _e('Bières Bouteilles', 'restaurant-booking'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-beers&action=add'); ?>" class="page-title-action">
                <?php _e('Ajouter une bière', 'restaurant-booking'); ?>
            </a>
            <hr class="wp-header-end">

            <?php
            // Afficher les messages de succès
            if (isset($_GET['message'])) {
                $success_message = '';
                switch ($_GET['message']) {
                    case 'updated':
                        $success_message = __('Bière mise à jour avec succès.', 'restaurant-booking');
                        break;
                    case 'created':
                        $success_message = __('Bière créée avec succès.', 'restaurant-booking');
                        break;
                    case 'deleted':
                        $success_message = __('Bière supprimée avec succès.', 'restaurant-booking');
                        break;
                }
                if ($success_message) {
                    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($success_message) . '</p></div>';
                }
            }
            ?>

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

            <form method="post" id="beers-filter">
                <?php wp_nonce_field('restaurant_booking_beers_action'); ?>
                
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

                <!-- Tableau des bières -->
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <input id="cb-select-all-1" type="checkbox">
                            </td>
                            <th scope="col" class="manage-column column-image"><?php _e('Image', 'restaurant-booking'); ?></th>
                            <th scope="col" class="manage-column column-name column-primary"><?php _e('Nom', 'restaurant-booking'); ?></th>
                            <th scope="col" class="manage-column column-description"><?php _e('Description', 'restaurant-booking'); ?></th>
                            <th scope="col" class="manage-column column-type"><?php _e('Type', 'restaurant-booking'); ?></th>
                            <th scope="col" class="manage-column column-degree"><?php _e('Degré', 'restaurant-booking'); ?></th>
                            <th scope="col" class="manage-column column-volume"><?php _e('Volume', 'restaurant-booking'); ?></th>
                            <th scope="col" class="manage-column column-price"><?php _e('Prix', 'restaurant-booking'); ?></th>
                            <th scope="col" class="manage-column column-order"><?php _e('Ordre', 'restaurant-booking'); ?></th>
                            <th scope="col" class="manage-column column-status"><?php _e('Statut', 'restaurant-booking'); ?></th>
                            <th scope="col" class="manage-column column-date"><?php _e('Date de création', 'restaurant-booking'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr class="no-items">
                                <td class="colspanchange" colspan="11">
                                    <?php _e('Aucune bière trouvée.', 'restaurant-booking'); ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <th scope="row" class="check-column">
                                        <input id="cb-select-<?php echo $product['id']; ?>" type="checkbox" name="beer_ids[]" value="<?php echo $product['id']; ?>">
                                    </th>
                                    <td class="column-image">
                                        <?php if (!empty($product['image_url'])): ?>
                                            <img src="<?php echo esc_url($product['image_url']); ?>" alt="<?php echo esc_attr($product['name']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                        <?php else: ?>
                                            <div style="width: 50px; height: 50px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #666;">
                                                <span class="dashicons dashicons-format-image"></span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="column-name column-primary">
                                        <strong>
                                            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-beers&action=edit&product_id=' . $product['id']); ?>">
                                                <?php echo esc_html($product['name']); ?>
                                            </a>
                                        </strong>
                                        <div class="row-actions">
                                            <span class="edit">
                                                <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-beers&action=edit&product_id=' . $product['id']); ?>">
                                                    <?php _e('Modifier', 'restaurant-booking'); ?>
                                                </a> |
                                            </span>
                                            <span class="toggle-status">
                                                <a href="#" class="toggle-beer-status" data-beer-id="<?php echo $product['id']; ?>" data-current-status="<?php echo $product['is_active'] ? 1 : 0; ?>">
                                                    <?php echo $product['is_active'] ? __('Désactiver', 'restaurant-booking') : __('Activer', 'restaurant-booking'); ?>
                                                </a> |
                                            </span>
                                            <span class="delete">
                                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=restaurant-booking-beverages-beers&action=delete&beer_id=' . $product['id']), 'delete_beer_' . $product['id']); ?>" 
                                                   class="button button-small button-link-delete" 
                                                   onclick="return confirm('<?php _e('Êtes-vous sûr de vouloir supprimer cette bière ?', 'restaurant-booking'); ?>')">
                                                    <?php _e('Supprimer', 'restaurant-booking'); ?>
                                                </a>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="column-description">
                                        <?php echo esc_html(wp_trim_words($product['description'] ?? '', 10)); ?>
                                    </td>
                                    <td class="column-type">
                                        <span class="beer-category-badge">
                                            <?php echo esc_html($product['beer_category'] ?: 'Non classée'); ?>
                                        </span>
                                    </td>
                                    <td class="column-degree">
                                        <strong><?php echo $product['alcohol_degree']; ?>°</strong>
                                    </td>
                                    <td class="column-volume">
                                        <strong><?php echo $product['volume_cl']; ?> cl</strong>
                                    </td>
                                    <td class="column-price">
                                        <strong><?php echo number_format($product['price'], 2, ',', ' '); ?> €</strong>
                                    </td>
                                    <td class="column-order">
                                        <input type="number" class="small-text beer-order-input" 
                                               value="<?php echo $product['display_order'] ?? 0; ?>" 
                                               data-beer-id="<?php echo $product['id']; ?>"
                                               min="0" max="999">
                                    </td>
                                    <td class="column-status">
                                        <?php if ($product['is_active']): ?>
                                            <span class="status-active"><?php _e('Actif', 'restaurant-booking'); ?></span>
                                        <?php else: ?>
                                            <span class="status-inactive"><?php _e('Inactif', 'restaurant-booking'); ?></span>
                                        <?php endif; ?>
                                        <?php if ($product['suggested_beverage']): ?>
                                            <br><small style="color: #ff9800;">⭐ <?php _e('Suggestion', 'restaurant-booking'); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="column-date">
                                        <?php echo date_i18n(get_option('date_format'), strtotime($product['created_at'] ?? 'now')); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </form>
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
        .status-active { color: #46b450; font-weight: 600; }
        .status-inactive { color: #dc3232; font-weight: 600; }
        .beer-order-input { width: 60px; }
        .column-image { width: 70px; }
        .column-type { width: 100px; }
        .column-degree { width: 80px; text-align: center; }
        .column-volume { width: 80px; text-align: center; }
        .column-price { width: 100px; text-align: center; }
        .column-order { width: 80px; }
        .column-status { width: 100px; }
        .column-date { width: 120px; }
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
     * Afficher le formulaire (version simplifiée)
     */
    public function display_form()
    {
        // Charger les scripts de la médiathèque WordPress
        wp_enqueue_media();
        
        $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
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

            <?php
            // Afficher les messages d'erreur ou de succès
            if (isset($_GET['error'])) {
                $error_message = '';
                switch ($_GET['error']) {
                    case 'validation':
                        $error_message = __('Erreur de validation : veuillez vérifier tous les champs obligatoires.', 'restaurant-booking');
                        break;
                    case 'no_category':
                        $error_message = __('Erreur : catégorie bière introuvable.', 'restaurant-booking');
                        break;
                    case 'save_failed':
                        $error_message = __('Erreur lors de la sauvegarde. Veuillez réessayer.', 'restaurant-booking');
                        break;
                    default:
                        $error_message = __('Une erreur est survenue.', 'restaurant-booking');
                }
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error_message) . '</p></div>';
            }

            if (isset($_GET['message'])) {
                $success_message = '';
                switch ($_GET['message']) {
                    case 'updated':
                        $success_message = __('Bière mise à jour avec succès.', 'restaurant-booking');
                        break;
                    case 'created':
                        $success_message = __('Bière créée avec succès.', 'restaurant-booking');
                        break;
                }
                if ($success_message) {
                    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($success_message) . '</p></div>';
                }
            }
            ?>

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
                <input type="hidden" name="restaurant_booking_action" value="save_beer">
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
                            <label for="beer_category"><?php _e('Type de bière', 'restaurant-booking'); ?> *</label>
                        </th>
                        <td>
                            <select id="beer_category" name="beer_category" class="regular-text" required>
                                <option value=""><?php _e('Sélectionner un type', 'restaurant-booking'); ?></option>
                                <?php 
                                $beer_types = $this->get_beer_types();
                                foreach ($beer_types as $type): ?>
                                    <option value="<?php echo esc_attr($type['category']); ?>" <?php selected($product['beer_category'] ?? '', $type['category']); ?>>
                                        <?php echo esc_html($type['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('Sélectionnez le type de bière. Vous pouvez ajouter de nouveaux types ci-dessous.', 'restaurant-booking'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label><?php _e('Gérer les types', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=beers_group'); ?>" 
                               class="button button-secondary" target="_blank">
                                🍺 <?php _e('Gérer les types de bières', 'restaurant-booking'); ?>
                            </a>
                            <p class="description"><?php _e('Ajoutez, modifiez ou supprimez les types de bières disponibles dans la liste ci-dessus.', 'restaurant-booking'); ?></p>
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

            // Gestionnaire pour les types de bières
            $('#new_beer_type').on('input', function() {
                var newTypeValue = $(this).val().trim();
                if (newTypeValue.length > 0) {
                    $('#beer_category').prop('disabled', true).val('');
                    $('#beer_category').after('<p class="description" style="color: #d63638;"><em><?php _e('Le nouveau type sera utilisé à la place de la sélection.', 'restaurant-booking'); ?></em></p>');
                } else {
                    $('#beer_category').prop('disabled', false);
                    $('#beer_category').next('p').remove();
                }
            });

            $('#beer_category').on('change', function() {
                if ($(this).val()) {
                    $('#new_beer_type').val('');
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Gérer la sauvegarde d'une bière
     */
    public function handle_save_beer()
    {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['beer_nonce'], 'save_beer')) {
            wp_die(__('Erreur de sécurité', 'restaurant-booking'));
        }

        // Log du début de la sauvegarde
        if (class_exists('RestaurantBooking_Logger')) {
            RestaurantBooking_Logger::info('Début sauvegarde bière', array(
                'product_id' => isset($_POST['product_id']) ? intval($_POST['product_id']) : 0,
                'beer_name' => isset($_POST['beer_name']) ? sanitize_text_field($_POST['beer_name']) : '',
                'action' => isset($_POST['product_id']) && $_POST['product_id'] ? 'update' : 'create'
            ));
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
        if (empty($beer_name) || empty($beer_category) || $beer_price <= 0 || $volume_cl <= 0) {
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
            if (is_wp_error($result)) {
                wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-beers&action=edit&product_id=' . $product_id . '&error=save_failed'));
                exit;
            }
            $success_param = 'updated';
        } else {
            // Création
            $result = RestaurantBooking_Product::create($product_data);
            if (is_wp_error($result)) {
                wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-beers&action=add&error=save_failed'));
                exit;
            }
            $success_param = 'created';
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
            $product['image_url'] = $product['image_id'] ? wp_get_attachment_image_url($product['image_id'], 'thumbnail') : '';
        }

        return $products ?: array();
    }

    /**
     * Obtenir les types de bières disponibles
     */
    private function get_beer_types()
    {
        global $wpdb;
        
        // CORRECTION : Récupérer depuis la nouvelle table wp_restaurant_beer_types en priorité
        $beer_types_table = $wpdb->prefix . 'restaurant_beer_types';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$beer_types_table'");
        
        if ($table_exists) {
            // Utiliser la nouvelle table wp_restaurant_beer_types
            $types = $wpdb->get_results("
                SELECT slug as category, name
                FROM $beer_types_table
                WHERE is_active = 1
                ORDER BY display_order ASC, name ASC
            ", ARRAY_A);
            
            if (!empty($types)) {
                return $types;
            }
        }
        
        // Fallback : Récupérer depuis la table des sous-catégories si elle existe encore
        $subcategories_table = $wpdb->prefix . 'restaurant_subcategories';
        $subcategories_exists = $wpdb->get_var("SHOW TABLES LIKE '$subcategories_table'") == $subcategories_table;
        
        if ($subcategories_exists) {
            $beer_category_id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}restaurant_categories WHERE type = 'biere_bouteille'");
            
            if ($beer_category_id) {
                $types = $wpdb->get_results($wpdb->prepare("
                    SELECT subcategory_key as category, subcategory_name as name
                    FROM $subcategories_table
                    WHERE parent_category_id = %d AND is_active = 1
                    ORDER BY display_order ASC, subcategory_name ASC
                ", $beer_category_id), ARRAY_A);
                
                if (!empty($types)) {
                    return $types;
                }
            }
        }
        
        // Fallback : Récupérer depuis les produits existants
        $existing_types = $wpdb->get_results("
            SELECT DISTINCT 
                p.beer_category as category, 
                p.beer_category as name
            FROM {$wpdb->prefix}restaurant_products p
            INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
            WHERE c.type = 'biere_bouteille' 
            AND p.is_active = 1 
            AND p.beer_category IS NOT NULL 
            AND p.beer_category != ''
            ORDER BY p.beer_category ASC
        ", ARRAY_A);
        
        if (!empty($existing_types)) {
            // Formatter les noms pour l'affichage
            foreach ($existing_types as &$type) {
                $type['name'] = ucfirst($type['name']);
            }
            return $existing_types;
        }
        
        // Dernier fallback : types par défaut (seulement si rien d'autre n'existe)
        return array(
            array('category' => 'blonde', 'name' => __('Blonde', 'restaurant-booking')),
            array('category' => 'blanche', 'name' => __('Blanche', 'restaurant-booking')),
            array('category' => 'brune', 'name' => __('Brune', 'restaurant-booking'))
        );
    }

    /**
     * Créer un nouveau type de bière
     */
    private function create_new_beer_type($type_name)
    {
        // Nettoyer et formater le nom du type
        $beer_category = strtolower(sanitize_title($type_name));
        
        // Vérifier si le type existe déjà (dans les bières bouteilles OU les fûts)
        global $wpdb;
        $existing = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}restaurant_products p
            INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
            WHERE c.type IN (%s, %s) AND p.beer_category = %s
        ", 'biere_bouteille', 'fut', $beer_category));
        
        // Retourner le type (existant ou nouveau)
        return $beer_category;
    }

    /**
     * Gérer les actions (suppression, etc.)
     */
    public function handle_actions()
    {
        if (!isset($_GET['action']) || !isset($_GET['beer_id'])) {
            return;
        }

        $action = sanitize_text_field($_GET['action']);
        $beer_id = (int) $_GET['beer_id'];

        switch ($action) {
            case 'delete':
                $this->delete_beer($beer_id);
                break;
        }
    }

    /**
     * Supprimer une bière
     */
    private function delete_beer($beer_id)
    {
        if (!wp_verify_nonce($_GET['_wpnonce'], 'delete_beer_' . $beer_id)) {
            wp_die(__('Action non autorisée.', 'restaurant-booking'));
        }

        if (!current_user_can('manage_restaurant_quotes')) {
            wp_die(__('Permissions insuffisantes.', 'restaurant-booking'));
        }

        $result = RestaurantBooking_Product::delete($beer_id);
        
        if (is_wp_error($result)) {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-beers&message=error&error=' . urlencode($result->get_error_message())));
        } else {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-beers&message=deleted'));
        }
        exit;
    }
}
