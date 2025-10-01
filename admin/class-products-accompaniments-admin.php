<?php
/**
 * Classe d'administration des Accompagnements
 *
 * @package RestaurantBooking
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Products_Accompaniments_Admin
{
    /**
     * Afficher la liste des accompagnements
     */
    public function display_list()
    {
        // Gérer les actions (suppression, etc.)
        $this->handle_actions();
        
        $products = $this->get_accompaniment_products();
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">🍽️ <?php _e('Accompagnements', 'restaurant-booking'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-products-accompaniments&action=add'); ?>" class="page-title-action">
                <?php _e('Ajouter un accompagnement', 'restaurant-booking'); ?>
            </a>
            <hr class="wp-header-end">

            <div class="restaurant-booking-info-card">
                <h3><?php _e('Nouveau système d\'accompagnements', 'restaurant-booking'); ?></h3>
                <ul>
                    <li><?php _e('✓ Prix personnalisable par accompagnement', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Système d\'options avec prix (ex: "Enrobée sauce chimichurri" +1€)', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Sous-options gratuites (ex: Sauces → Ketchup, Mayo, BBQ)', 'restaurant-booking'); ?></li>
                    <li><?php _e('⚠️ Quantité des options limitée par la quantité de l\'accompagnement', 'restaurant-booking'); ?></li>
                </ul>
            </div>

            <!-- Statistiques rapides -->
            <div class="restaurant-booking-stats">
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><?php echo count($products); ?></h3>
                        <p><?php _e('Accompagnements', 'restaurant-booking'); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo count(array_filter($products, function($p) { return $p['is_active']; })); ?></h3>
                        <p><?php _e('Actifs', 'restaurant-booking'); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo count(array_filter($products, function($p) { return $p['has_sauce_options']; })); ?></h3>
                        <p><?php _e('Avec options', 'restaurant-booking'); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>4,00 €</h3>
                        <p><?php _e('Prix fixe', 'restaurant-booking'); ?></p>
                    </div>
                </div>
            </div>

            <form method="post" id="accompaniments-filter">
                <?php wp_nonce_field('restaurant_booking_accompaniments_action'); ?>
                
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

                <!-- Tableau des produits -->
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <input id="cb-select-all-1" type="checkbox">
                            </td>
                            <th scope="col" class="manage-column column-image"><?php _e('Image', 'restaurant-booking'); ?></th>
                            <th scope="col" class="manage-column column-name column-primary"><?php _e('Nom', 'restaurant-booking'); ?></th>
                            <th scope="col" class="manage-column column-description"><?php _e('Description', 'restaurant-booking'); ?></th>
                            <th scope="col" class="manage-column column-price"><?php _e('Prix', 'restaurant-booking'); ?></th>
                            <th scope="col" class="manage-column column-order"><?php _e('Ordre', 'restaurant-booking'); ?></th>
                            <th scope="col" class="manage-column column-status"><?php _e('Statut', 'restaurant-booking'); ?></th>
                            <th scope="col" class="manage-column column-date"><?php _e('Date de création', 'restaurant-booking'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr class="no-items">
                                <td class="colspanchange" colspan="8">
                                    <?php _e('Aucun accompagnement trouvé.', 'restaurant-booking'); ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <th scope="row" class="check-column">
                                        <input id="cb-select-<?php echo $product['id']; ?>" type="checkbox" name="accompaniment_ids[]" value="<?php echo $product['id']; ?>">
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
                                            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-products-accompaniments&action=edit&product_id=' . $product['id']); ?>">
                                                <?php echo esc_html($product['name']); ?>
                                            </a>
                                        </strong>
                                        <div class="row-actions">
                                            <span class="edit">
                                                <a href="<?php echo admin_url('admin.php?page=restaurant-booking-products-accompaniments&action=edit&product_id=' . $product['id']); ?>">
                                                    <?php _e('Modifier', 'restaurant-booking'); ?>
                                                </a> |
                                            </span>
                                            <span class="toggle-status">
                                                <a href="#" class="toggle-accompaniment-status" data-accompaniment-id="<?php echo $product['id']; ?>" data-current-status="<?php echo $product['is_active'] ? 1 : 0; ?>">
                                                    <?php echo $product['is_active'] ? __('Désactiver', 'restaurant-booking') : __('Activer', 'restaurant-booking'); ?>
                                                </a> |
                                            </span>
                                            <span class="delete">
                                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=restaurant-booking-products-accompaniments&action=delete&accompaniment_id=' . $product['id']), 'delete_accompaniment_' . $product['id']); ?>" 
                                                   class="button button-small button-link-delete" 
                                                   onclick="return confirm('<?php _e('Êtes-vous sûr de vouloir supprimer cet accompagnement ?', 'restaurant-booking'); ?>')">
                                                    <?php _e('Supprimer', 'restaurant-booking'); ?>
                                                </a>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="column-description">
                                        <?php echo esc_html(wp_trim_words($product['description'] ?? '', 10)); ?>
                                    </td>
                                    <td class="column-price">
                                        <strong>4,00 €</strong>
                                    </td>
                                    <td class="column-order">
                                        <input type="number" class="small-text accompaniment-order-input" 
                                               value="<?php echo $product['display_order'] ?? 0; ?>" 
                                               data-accompaniment-id="<?php echo $product['id']; ?>"
                                               min="0" max="999">
                                    </td>
                                    <td class="column-status">
                                        <?php if ($product['is_active']): ?>
                                            <span class="status-active"><?php _e('Actif', 'restaurant-booking'); ?></span>
                                        <?php else: ?>
                                            <span class="status-inactive"><?php _e('Inactif', 'restaurant-booking'); ?></span>
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
            background: #f0f8ff;
            border: 1px solid #b3d9ff;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }
        .restaurant-booking-info-card h3 {
            margin-top: 0;
            color: #0066cc;
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
        .accompaniment-order-input { width: 60px; }
        .column-image { width: 70px; }
        .column-price { width: 100px; text-align: center; }
        .column-order { width: 80px; }
        .column-status { width: 80px; }
        .column-date { width: 120px; }
        </style>
        <?php
    }

    /**
     * Afficher le formulaire d'ajout/modification
     */
    public function display_form()
    {
        // Charger les scripts de la médiathèque WordPress
        wp_enqueue_media();
        
        $product_id = isset($_GET['product_id']) ? (int) $_GET['product_id'] : 0;
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'add';
        $product = null;

        if ($product_id && $action === 'edit') {
            $product = RestaurantBooking_Product::get($product_id);
            if (!$product || $product['category_type'] !== 'accompagnement') {
                wp_die(__('Produit introuvable ou type incorrect.', 'restaurant-booking'));
            }
        }

        // Charger les options existantes
        $existing_options = array();
        if ($product) {
            $options = RestaurantBooking_Accompaniment_Option_Manager::get_product_options($product['id']);
            foreach ($options as $option) {
                $suboptions = RestaurantBooking_Accompaniment_Option_Manager::get_option_suboptions($option->id);
                $existing_options[] = array(
                    'id' => $option->id,
                    'name' => $option->option_name,
                    'price' => $option->option_price,
                    'suboptions' => array_column($suboptions, 'suboption_name')
                );
            }
        }
        
        ?>
        <div class="wrap">
            <h1>
                🍽️ <?php echo $product ? __('Modifier l\'accompagnement', 'restaurant-booking') : __('Nouvel accompagnement', 'restaurant-booking'); ?>
            </h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('restaurant_booking_product_accompaniment', 'product_accompaniment_nonce'); ?>
                <input type="hidden" name="restaurant_booking_action" value="save_product_accompaniment">
                <?php if ($product): ?>
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <?php endif; ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="product_name"><?php _e('Nom de l\'accompagnement', 'restaurant-booking'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" id="product_name" name="product_name" 
                                   value="<?php echo $product ? esc_attr($product['name']) : ''; ?>" 
                                   class="regular-text" required>
                            <p class="description"><?php _e('Ex: Salade verte, Frites...', 'restaurant-booking'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="product_price"><?php _e('Prix', 'restaurant-booking'); ?> *</label>
                        </th>
                        <td>
                            <input type="number" id="product_price" name="product_price" 
                                   value="<?php echo $product ? esc_attr($product['price']) : '4.00'; ?>" 
                                   step="0.01" min="0" class="small-text" required> €
                            <p class="description"><?php _e('Prix de l\'accompagnement par portion.', 'restaurant-booking'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="product_image"><?php _e('Image', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <button type="button" class="button" id="upload_image_button">
                                <?php _e('Choisir une image', 'restaurant-booking'); ?>
                            </button>
                            <input type="hidden" id="product_image_id" name="product_image_id" 
                                   value="<?php echo $product ? esc_attr($product['image_id']) : ''; ?>">
                            <div id="image_preview" style="margin-top: 10px;">
                                <?php if ($product && $product['image_id']): ?>
                                    <?php echo wp_get_attachment_image($product['image_id'], 'thumbnail'); ?>
                                <?php endif; ?>
                            </div>
                            <p class="description"><?php _e('Image de l\'accompagnement pour la médiathèque WordPress.', 'restaurant-booking'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="product_description"><?php _e('Description', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <textarea id="product_description" name="product_description" 
                                      rows="3" class="large-text"><?php echo $product ? esc_textarea($product['description']) : ''; ?></textarea>
                            <p class="description"><?php _e('Description de l\'accompagnement (optionnel).', 'restaurant-booking'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label><?php _e('Options', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <div id="accompaniment_options_container">
                                <p class="description"><?php _e('Ajoutez des options payantes pour cet accompagnement (ex: "Enrobée sauce chimichurri" +1€)', 'restaurant-booking'); ?></p>
                                
                                <div id="options_list">
                                    <!-- Les options seront ajoutées dynamiquement ici -->
                                </div>
                                
                                <button type="button" class="button button-secondary" id="add_option_button">
                                    <?php _e('+ Ajouter une option', 'restaurant-booking'); ?>
                                </button>
                            </div>
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
                                <?php _e('Accompagnement actif (visible dans les formulaires)', 'restaurant-booking'); ?>
                            </label>
                        </td>
                    </tr>
                </table>

                <?php submit_button($product ? __('Mettre à jour l\'accompagnement', 'restaurant-booking') : __('Créer l\'accompagnement', 'restaurant-booking')); ?>
            </form>
        </div>

        <!-- Modal pour ajouter/modifier une option -->
        <div id="option_modal" style="display: none;">
            <div class="option-modal-content">
                <h3 id="option_modal_title"><?php _e('Ajouter une option', 'restaurant-booking'); ?></h3>
                <form id="option_form">
                    <table class="form-table">
                        <tr>
                            <th><label for="option_name"><?php _e('Nom de l\'option', 'restaurant-booking'); ?> *</label></th>
                            <td>
                                <input type="text" id="option_name" name="option_name" class="regular-text" required
                                       placeholder="<?php _e('Ex: Enrobée sauce chimichurri', 'restaurant-booking'); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="option_price"><?php _e('Prix', 'restaurant-booking'); ?> *</label></th>
                            <td>
                                <input type="number" id="option_price" name="option_price" step="0.01" min="0" class="small-text" required> €
                            </td>
                        </tr>
                        <tr>
                            <th><label for="suboptions"><?php _e('Sous-options', 'restaurant-booking'); ?></label></th>
                            <td>
                                <div id="suboptions_container">
                                    <p class="description"><?php _e('Ajoutez des sous-options gratuites (ex: Ketchup, Mayo)', 'restaurant-booking'); ?></p>
                                    <div id="suboptions_list"></div>
                                    <button type="button" class="button button-small" id="add_suboption_button">
                                        <?php _e('+ Ajouter une sous-option', 'restaurant-booking'); ?>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" class="button-primary" value="<?php _e('Enregistrer', 'restaurant-booking'); ?>">
                        <button type="button" class="button" id="cancel_option"><?php _e('Annuler', 'restaurant-booking'); ?></button>
                    </p>
                </form>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            var mediaUploader;
            var optionCounter = 0;
            var suboptions = [];
            
            // Sélecteur d'images WordPress
            $('#upload_image_button').click(function(e) {
                e.preventDefault();
                
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                
                mediaUploader = wp.media({
                    title: '<?php _e('Choisir une image', 'restaurant-booking'); ?>',
                    button: {
                        text: '<?php _e('Utiliser cette image', 'restaurant-booking'); ?>'
                    },
                    multiple: false
                });
                
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#product_image_id').val(attachment.id);
                    $('#image_preview').html('<img src="' + attachment.sizes.thumbnail.url + '" alt="">');
                });
                
                mediaUploader.open();
            });
            
            // Gestion des options
            $('#add_option_button').click(function() {
                $('#option_modal_title').text('<?php _e('Ajouter une option', 'restaurant-booking'); ?>');
                $('#option_form')[0].reset();
                $('#suboptions_list').empty();
                suboptions = [];
                $('#option_modal').show();
            });
            
            $('#cancel_option').click(function() {
                $('#option_modal').hide();
            });
            
            // Ajouter une sous-option
            $('#add_suboption_button').click(function() {
                var suboptionName = prompt('<?php _e('Nom de la sous-option:', 'restaurant-booking'); ?>');
                if (suboptionName && suboptionName.trim()) {
                    suboptions.push(suboptionName.trim());
                    updateSuboptionsList();
                }
            });
            
            function updateSuboptionsList() {
                var html = '';
                suboptions.forEach(function(suboption, index) {
                    html += '<div class="suboption-item">';
                    html += '<span class="suboption-name">' + suboption + '</span>';
                    html += '<button type="button" class="button button-small remove-suboption" data-index="' + index + '">Supprimer</button>';
                    html += '</div>';
                });
                $('#suboptions_list').html(html);
            }
            
            // Supprimer une sous-option
            $(document).on('click', '.remove-suboption', function() {
                var index = $(this).data('index');
                suboptions.splice(index, 1);
                updateSuboptionsList();
            });
            
            // Soumettre le formulaire d'option
            $('#option_form').on('submit', function(e) {
                e.preventDefault();
                
                var optionName = $('#option_name').val();
                var optionPrice = $('#option_price').val();
                
                if (!optionName || !optionPrice) {
                    alert('<?php _e('Veuillez remplir tous les champs obligatoires.', 'restaurant-booking'); ?>');
                    return;
                }
                
                // Ajouter l'option à la liste
                var optionHtml = '<div class="option-item" data-option-id="' + optionCounter + '">';
                optionHtml += '<h4>' + optionName + ' - ' + parseFloat(optionPrice).toFixed(2) + '€</h4>';
                optionHtml += '<input type="hidden" name="options[' + optionCounter + '][name]" value="' + optionName + '">';
                optionHtml += '<input type="hidden" name="options[' + optionCounter + '][price]" value="' + optionPrice + '">';
                
                if (suboptions.length > 0) {
                    optionHtml += '<div class="suboptions">';
                    suboptions.forEach(function(suboption, index) {
                        optionHtml += '<span class="suboption-tag">' + suboption + '</span>';
                        optionHtml += '<input type="hidden" name="options[' + optionCounter + '][suboptions][' + index + ']" value="' + suboption + '">';
                    });
                    optionHtml += '</div>';
                }
                
                optionHtml += '<button type="button" class="button button-small delete-option" data-option-id="' + optionCounter + '">';
                optionHtml += '<?php _e('Supprimer', 'restaurant-booking'); ?>';
                optionHtml += '</button>';
                optionHtml += '</div>';
                
                $('#options_list').append(optionHtml);
                optionCounter++;
                
                $('#option_modal').hide();
            });
            
            // Supprimer une option
            $(document).on('click', '.delete-option', function() {
                if (confirm('<?php _e('Êtes-vous sûr de vouloir supprimer cette option ?', 'restaurant-booking'); ?>')) {
                    $(this).closest('.option-item').remove();
                }
            });
            
            // Charger les options existantes au chargement de la page
            <?php if (!empty($existing_options)): ?>
                <?php foreach ($existing_options as $index => $option): ?>
                    var optionHtml = '<div class="option-item" data-option-id="<?php echo $index; ?>">';
                    optionHtml += '<h4><?php echo esc_js($option['name']); ?> - <?php echo number_format($option['price'], 2); ?>€</h4>';
                    optionHtml += '<input type="hidden" name="options[<?php echo $index; ?>][name]" value="<?php echo esc_attr($option['name']); ?>">';
                    optionHtml += '<input type="hidden" name="options[<?php echo $index; ?>][price]" value="<?php echo esc_attr($option['price']); ?>">';
                    
                    <?php if (!empty($option['suboptions'])): ?>
                        optionHtml += '<div class="suboptions">';
                        <?php foreach ($option['suboptions'] as $subindex => $suboption): ?>
                            optionHtml += '<span class="suboption-tag"><?php echo esc_js($suboption); ?></span>';
                            optionHtml += '<input type="hidden" name="options[<?php echo $index; ?>][suboptions][<?php echo $subindex; ?>]" value="<?php echo esc_attr($suboption); ?>">';
                        <?php endforeach; ?>
                        optionHtml += '</div>';
                    <?php endif; ?>
                    
                    optionHtml += '<button type="button" class="button button-small delete-option" data-option-id="<?php echo $index; ?>">';
                    optionHtml += '<?php _e('Supprimer', 'restaurant-booking'); ?>';
                    optionHtml += '</button>';
                    optionHtml += '</div>';
                    
                    $('#options_list').append(optionHtml);
                    optionCounter = <?php echo $index + 1; ?>;
                <?php endforeach; ?>
            <?php endif; ?>
        });
        </script>

        <style>
        .option-item {
            border: 1px solid #ddd;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .suboptions {
            margin: 10px 0;
        }
        .suboption-tag {
            background: #f0f0f0;
            padding: 3px 8px;
            border-radius: 3px;
            margin-right: 5px;
            font-size: 12px;
        }
        .suboption-item {
            margin: 5px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px 10px;
            background: #f9f9f9;
            border-radius: 3px;
        }
        #image_preview img {
            max-width: 150px;
            height: auto;
        }
        #option_modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .option-modal-content {
            background: white;
            padding: 20px;
            border-radius: 5px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        </style>
        <?php
    }

    /**
     * Traiter la sauvegarde d'un accompagnement
     */
    public function handle_save_accompaniment()
    {
        // Vérification des permissions et du nonce
        if (!current_user_can('manage_restaurant_quotes')) {
            wp_die(__('Permissions insuffisantes', 'restaurant-booking'));
        }
        
        if (!wp_verify_nonce($_POST['product_accompaniment_nonce'], 'restaurant_booking_product_accompaniment')) {
            wp_die(__('Nonce invalide', 'restaurant-booking'));
        }
        
        $product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
        $is_edit = $product_id > 0;
        
        // Obtenir la catégorie accompagnement
        $category = RestaurantBooking_Category::get_by_type('accompagnement');
        if (!$category) {
            wp_die(__('Catégorie accompagnement non trouvée', 'restaurant-booking'));
        }
        
        // Préparer les données du produit
        $product_data = array(
            'category_id' => $category['id'],
            'name' => sanitize_text_field($_POST['product_name']),
            'description' => sanitize_textarea_field($_POST['product_description']),
            'price' => floatval($_POST['product_price']),
            'unit_type' => 'piece',
            'unit_label' => '/portion',
            'image_id' => !empty($_POST['product_image_id']) ? (int) $_POST['product_image_id'] : null,
            'has_accompaniment_options' => 1, // Marquer comme ayant des options
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'display_order' => isset($_POST['display_order']) ? (int) $_POST['display_order'] : 0
        );
        
        if ($is_edit) {
            $result = RestaurantBooking_Product::update($product_id, $product_data);
            $message = $result ? __('Accompagnement mis à jour avec succès.', 'restaurant-booking') : __('Erreur lors de la mise à jour.', 'restaurant-booking');
        } else {
            $result = RestaurantBooking_Product::create($product_data);
            if (is_wp_error($result)) {
                $product_id = null;
                $message = $result->get_error_message();
            } else {
                $product_id = $result;
                $message = __('Accompagnement créé avec succès.', 'restaurant-booking');
            }
        }
        
        if ($result && !is_wp_error($result) && $product_id) {
            // Traiter les options d'accompagnement
            $this->save_accompaniment_options($product_id);
            
            wp_redirect(admin_url('admin.php?page=restaurant-booking-products-accompaniments&message=' . urlencode($message)));
        } else {
            $error_message = is_wp_error($result) ? $result->get_error_message() : $message;
            wp_redirect(admin_url('admin.php?page=restaurant-booking-products-accompaniments&error=' . urlencode($error_message)));
        }
        exit;
    }
    
    /**
     * Sauvegarder les options d'accompagnement
     */
    private function save_accompaniment_options($product_id)
    {
        if (!isset($_POST['options']) || !is_array($_POST['options'])) {
            return;
        }
        
        // Supprimer les anciennes options
        $this->delete_product_options($product_id);
        
        // Ajouter les nouvelles options
        foreach ($_POST['options'] as $option_data) {
            if (empty($option_data['name']) || !isset($option_data['price'])) {
                continue;
            }
            
            $option_id = RestaurantBooking_Accompaniment_Option_Manager::create_option(array(
                'product_id' => $product_id,
                'option_name' => sanitize_text_field($option_data['name']),
                'option_price' => floatval($option_data['price'])
            ));
            
            // Vérifier si la création de l'option a réussi
            if (is_wp_error($option_id)) {
                // Log l'erreur pour debug
                if (class_exists('RestaurantBooking_Logger')) {
                    RestaurantBooking_Logger::error('Erreur création option accompagnement: ' . $option_id->get_error_message());
                }
                continue;
            }
            
            // Ajouter les sous-options si présentes
            if (isset($option_data['suboptions']) && is_array($option_data['suboptions'])) {
                foreach ($option_data['suboptions'] as $suboption_name) {
                    if (!empty($suboption_name)) {
                        $suboption_result = RestaurantBooking_Accompaniment_Option_Manager::create_suboption(array(
                            'option_id' => $option_id,
                            'suboption_name' => sanitize_text_field($suboption_name)
                        ));
                        
                        // Log les erreurs de sous-options
                        if (is_wp_error($suboption_result) && class_exists('RestaurantBooking_Logger')) {
                            RestaurantBooking_Logger::error('Erreur création sous-option accompagnement: ' . $suboption_result->get_error_message());
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Supprimer toutes les options d'un produit
     */
    private function delete_product_options($product_id)
    {
        $options = RestaurantBooking_Accompaniment_Option_Manager::get_product_options($product_id);
        
        foreach ($options as $option) {
            RestaurantBooking_Accompaniment_Option_Manager::delete_option($option->id);
        }
    }
    
    /**
     * Obtenir les accompagnements depuis la base de données
     */
    private function get_accompaniment_products()
    {
        global $wpdb;

        $products = $wpdb->get_results("
            SELECT p.*, c.name as category_name, c.type as category_type
            FROM {$wpdb->prefix}restaurant_products p
            INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
            WHERE c.type = 'accompagnement'
            ORDER BY p.display_order ASC, p.name ASC
        ", ARRAY_A);

        // Ajouter l'URL de l'image et convertir les types
        foreach ($products as &$product) {
            $product['image_url'] = $product['image_id'] ? wp_get_attachment_image_url($product['image_id'], 'thumbnail') : '';
            $product['is_active'] = (bool) $product['is_active'];
            
            // Vérifier si le produit a des options d'accompagnement
            $options_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}restaurant_accompaniment_options WHERE product_id = %d AND is_active = 1",
                $product['id']
            ));
            $product['has_sauce_options'] = $options_count > 0;
        }

        return $products ?: array();
    }

    /**
     * Gérer les actions (suppression, etc.)
     */
    public function handle_actions()
    {
        if (!isset($_GET['action']) || !isset($_GET['accompaniment_id'])) {
            return;
        }

        $action = sanitize_text_field($_GET['action']);
        $accompaniment_id = (int) $_GET['accompaniment_id'];

        switch ($action) {
            case 'delete':
                $this->delete_accompaniment($accompaniment_id);
                break;
        }
    }

    /**
     * Supprimer un accompagnement
     */
    private function delete_accompaniment($accompaniment_id)
    {
        if (!wp_verify_nonce($_GET['_wpnonce'], 'delete_accompaniment_' . $accompaniment_id)) {
            wp_die(__('Action non autorisée.', 'restaurant-booking'));
        }

        if (!current_user_can('manage_restaurant_quotes')) {
            wp_die(__('Permissions insuffisantes.', 'restaurant-booking'));
        }

        $result = RestaurantBooking_Product::delete($accompaniment_id);
        
        if (is_wp_error($result)) {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-products-accompaniments&message=error&error=' . urlencode($result->get_error_message())));
        } else {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-products-accompaniments&message=deleted'));
        }
        exit;
    }
}
