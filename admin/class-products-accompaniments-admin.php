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

            <!-- Tableau des produits -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th class="manage-column"><?php _e('Accompagnement', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Type', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Prix', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Options', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Statut', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Actions', 'restaurant-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px;">
                                <p><?php _e('Aucun accompagnement configuré.', 'restaurant-booking'); ?></p>
                                <a href="<?php echo admin_url('admin.php?page=restaurant-booking-products-accompaniments&action=add'); ?>" class="button button-primary">
                                    <?php _e('Créer le premier accompagnement', 'restaurant-booking'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <div class="product-info">
                                        <div>
                                            <strong><?php echo esc_html($product['name']); ?></strong>
                                            <?php if ($product['description']): ?>
                                                <br><small class="description"><?php echo esc_html(wp_trim_words($product['description'], 10)); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="type-badge type-<?php echo esc_attr($product['accompaniment_type']); ?>">
                                        <?php 
                                        switch($product['accompaniment_type']) {
                                            case 'frites': _e('Frites', 'restaurant-booking'); break;
                                            case 'salade': _e('Salade', 'restaurant-booking'); break;
                                            default: echo esc_html($product['accompaniment_type']);
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <strong>4,00 €</strong>
                                    <br><small>/portion</small>
                                </td>
                                <td>
                                    <?php if ($product['has_sauce_options']): ?>
                                        <div class="sauce-options">
                                            <?php 
                                            $sauce_options = json_decode($product['sauce_options'], true);
                                            if ($sauce_options): ?>
                                                <strong><?php _e('Sauces disponibles:', 'restaurant-booking'); ?></strong>
                                                <ul class="sauce-list">
                                                    <?php foreach ($sauce_options as $sauce): ?>
                                                        <li><?php echo esc_html($sauce['name']); ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                                <?php if (isset($sauce_options['chimichurri'])): ?>
                                                    <small class="chimichurri-option">
                                                        <?php _e('+ Option Chimichurri: +1€', 'restaurant-booking'); ?>
                                                    </small>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="no-options">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="product-status status-<?php echo $product['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $product['is_active'] ? __('Actif', 'restaurant-booking') : __('Inactif', 'restaurant-booking'); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=restaurant-booking-products-accompaniments&action=edit&product_id=' . $product['id']); ?>" 
                                       class="button button-small">
                                        <?php _e('Modifier', 'restaurant-booking'); ?>
                                    </a>
                                    <a href="#" class="button button-small button-link-delete" 
                                       onclick="return confirm('<?php _e('Êtes-vous sûr de vouloir supprimer cet accompagnement ?', 'restaurant-booking'); ?>')">
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
        .product-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .description {
            color: #666;
            font-style: italic;
        }
        .type-badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 500;
            text-transform: uppercase;
        }
        .type-frites { background: #fff3cd; color: #856404; }
        .type-salade { background: #d4edda; color: #155724; }
        .sauce-options {
            font-size: 12px;
        }
        .sauce-list {
            margin: 5px 0;
            padding-left: 15px;
        }
        .sauce-list li {
            margin: 2px 0;
        }
        .chimichurri-option {
            color: #28a745;
            font-weight: bold;
        }
        .no-options {
            color: #666;
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
            $product_id = $result;
            $message = $result ? __('Accompagnement créé avec succès.', 'restaurant-booking') : __('Erreur lors de la création.', 'restaurant-booking');
        }
        
        if ($result) {
            // Traiter les options d'accompagnement
            $this->save_accompaniment_options($product_id);
            
            wp_redirect(admin_url('admin.php?page=restaurant-booking-products-accompaniments&message=' . urlencode($message)));
        } else {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-products-accompaniments&error=' . urlencode($message)));
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
            
            // Ajouter les sous-options si présentes
            if (is_wp_error($option_id) || !isset($option_data['suboptions']) || !is_array($option_data['suboptions'])) {
                continue;
            }
            
            foreach ($option_data['suboptions'] as $suboption_name) {
                if (!empty($suboption_name)) {
                    RestaurantBooking_Accompaniment_Option_Manager::create_suboption(array(
                        'option_id' => $option_id,
                        'suboption_name' => sanitize_text_field($suboption_name)
                    ));
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

        return $wpdb->get_results("
            SELECT p.*, c.name as category_name, c.type as category_type
            FROM {$wpdb->prefix}restaurant_products p
            INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
            WHERE c.type = 'accompagnement'
            ORDER BY p.display_order ASC, p.name ASC
        ", ARRAY_A);
    }
}
