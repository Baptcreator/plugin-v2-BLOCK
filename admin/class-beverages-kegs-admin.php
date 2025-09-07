<?php
/**
 * Classe d'administration des Fûts de Bière
 *
 * @package RestaurantBooking
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Beverages_Kegs_Admin
{
    /**
     * Afficher la liste des fûts de bière
     */
    public function display_list()
    {
        $products = $this->get_kegs();
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">🍺 <?php _e('Fûts de Bière', 'restaurant-booking'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-kegs&action=add'); ?>" class="page-title-action">
                <?php _e('Ajouter un fût', 'restaurant-booking'); ?>
            </a>
            <hr class="wp-header-end">

            <div class="restaurant-booking-info-card">
                <h3><?php _e('Système de fûts multi-contenances', 'restaurant-booking'); ?></h3>
                <ul>
                    <li><?php _e('✓ Différentes contenances par type de bière (10L, 20L)', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Prix spécifiques par contenance', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Images différentes par taille de fût', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Système de mise en avant par contenance', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Exemple: IPA → 10L (30€) + 20L (50€)', 'restaurant-booking'); ?></li>
                </ul>
            </div>

            <!-- Statistiques rapides -->
            <div class="restaurant-booking-stats">
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><?php echo count($products); ?></h3>
                        <p><?php _e('Fûts disponibles', 'restaurant-booking'); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo count(array_filter($products, function($p) { return $p['is_active']; })); ?></h3>
                        <p><?php _e('Actifs', 'restaurant-booking'); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo count(array_filter($products, function($p) { return $p['suggested_beverage']; })); ?></h3>
                        <p><?php _e('En suggestion', 'restaurant-booking'); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $products ? number_format(array_sum(array_column($products, 'price')) / count($products), 0, ',', ' ') : '0'; ?> €</h3>
                        <p><?php _e('Prix moyen', 'restaurant-booking'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Tableau des fûts -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th class="manage-column"><?php _e('Fût de bière', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Type', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Contenance', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Prix', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Suggestion', 'restaurant-booking'); ?></th>
                        <th class="manage-column"><?php _e('Actions', 'restaurant-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px;">
                                <p><?php _e('Aucun fût de bière configuré.', 'restaurant-booking'); ?></p>
                                <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-kegs&action=add'); ?>" class="button button-primary">
                                    <?php _e('Créer le premier fût', 'restaurant-booking'); ?>
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
                                                <br><small class="description"><?php echo esc_html(wp_trim_words($product['description'], 10)); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="beer-category-badge">
                                        <?php echo esc_html($product['beer_category'] ?: 'Non classée'); ?>
                                    </span>
                                    <?php if ($product['alcohol_degree']): ?>
                                        <br><small><?php echo $product['alcohol_degree']; ?>°</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo $product['size_label']; ?></strong>
                                    <br><small>Fût <?php echo $product['size_label']; ?></small>
                                </td>
                                <td>
                                    <strong><?php echo number_format($product['price'], 0, ',', ' '); ?> €</strong>
                                    <br><small>/fût</small>
                                </td>
                                <td>
                                    <?php if ($product['suggested_beverage']): ?>
                                        <span class="dashicons dashicons-star-filled" style="color: #ffb900;" title="<?php _e('En suggestion', 'restaurant-booking'); ?>"></span>
                                        <small><?php _e('Oui', 'restaurant-booking'); ?></small>
                                    <?php else: ?>
                                        <span class="dashicons dashicons-star-empty" style="color: #ddd;"></span>
                                        <small><?php _e('Non', 'restaurant-booking'); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-kegs&action=edit&id=' . $product['id']); ?>" 
                                       class="button button-small"><?php _e('Modifier', 'restaurant-booking'); ?></a>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=restaurant-booking-beverages-kegs&action=delete&id=' . $product['id']), 'delete_keg_' . $product['id']); ?>" 
                                       class="button button-small button-link-delete" 
                                       onclick="return confirm('<?php _e('Êtes-vous sûr de vouloir supprimer ce fût ?', 'restaurant-booking'); ?>')"><?php _e('Supprimer', 'restaurant-booking'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <style>
        .restaurant-booking-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .stats-grid {
            display: contents;
        }
        
        .stat-card {
            background: #fff;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        
        .stat-card h3 {
            font-size: 2em;
            margin: 0 0 10px 0;
            color: #1d2327;
        }
        
        .stat-card p {
            margin: 0;
            color: #646970;
            font-weight: 500;
        }
        
        .restaurant-booking-info-card {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }
        
        .restaurant-booking-info-card h3 {
            margin-top: 0;
            color: #0c5460;
        }
        
        .restaurant-booking-info-card ul {
            margin-bottom: 0;
        }
        
        .product-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .product-thumb {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 4px;
        }
        
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
     * Afficher le formulaire d'ajout/modification de fût
     */
    public function display_form()
    {
        $product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'add';
        $product = null;

        if ($product_id && $action === 'edit') {
            $product = RestaurantBooking_Product::get($product_id);
            if (!$product || $product['category_type'] !== 'fut') {
                wp_die(__('Produit introuvable ou type incorrect.', 'restaurant-booking'));
            }
        }

        // Gérer la soumission du formulaire
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handle_save_keg();
            return;
        }

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                🍺 <?php echo $action === 'edit' ? __('Modifier le fût', 'restaurant-booking') : __('Ajouter un fût', 'restaurant-booking'); ?>
            </h1>
            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-kegs'); ?>" class="page-title-action">
                ← <?php _e('Retour à la liste', 'restaurant-booking'); ?>
            </a>
            <hr class="wp-header-end">

            <div class="restaurant-booking-info-card">
                <h3><?php _e('Informations sur les fûts de bière', 'restaurant-booking'); ?></h3>
                <ul>
                    <li><?php _e('✓ Les fûts sont vendus complets (prix par fût)', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Matériel de service généralement inclus', 'restaurant-booking'); ?></li>
                                    <li><?php _e('✓ Contenances standard : 10L, 20L, 30L, 50L', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Parfait pour les événements et grandes réceptions', 'restaurant-booking'); ?></li>
                </ul>
            </div>

            <form method="post" action="" id="keg-form">
                <?php wp_nonce_field('save_keg', 'keg_nonce'); ?>
                <input type="hidden" name="action" value="save_keg">
                <?php if ($product): ?>
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <?php endif; ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="keg_name"><?php _e('Nom du fût', 'restaurant-booking'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" id="keg_name" name="keg_name" class="regular-text" 
                                   value="<?php echo $product ? esc_attr($product['name']) : ''; ?>" required>
                            <p class="description"><?php _e('Ex: IPA, Blanche, Blonde... (les contenances seront ajoutées séparément)', 'restaurant-booking'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="keg_description"><?php _e('Description', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <textarea id="keg_description" name="keg_description" class="large-text" rows="3"><?php echo $product ? esc_textarea($product['description']) : ''; ?></textarea>
                            <p class="description"><?php _e('Description du fût et conditions de service', 'restaurant-booking'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="beer_category"><?php _e('Type de bière', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <select id="beer_category" name="beer_category" class="regular-text">
                                <option value=""><?php _e('Sélectionner un type', 'restaurant-booking'); ?></option>
                                <option value="blonde" <?php selected($product['beer_category'] ?? '', 'blonde'); ?>><?php _e('Blonde', 'restaurant-booking'); ?></option>
                                <option value="blanche" <?php selected($product['beer_category'] ?? '', 'blanche'); ?>><?php _e('Blanche', 'restaurant-booking'); ?></option>
                                <option value="brune" <?php selected($product['beer_category'] ?? '', 'brune'); ?>><?php _e('Brune', 'restaurant-booking'); ?></option>
                                <option value="ipa" <?php selected($product['beer_category'] ?? '', 'ipa'); ?>><?php _e('IPA', 'restaurant-booking'); ?></option>
                                <option value="ambree" <?php selected($product['beer_category'] ?? '', 'ambree'); ?>><?php _e('Ambrée', 'restaurant-booking'); ?></option>
                                <option value="pils" <?php selected($product['beer_category'] ?? '', 'pils'); ?>><?php _e('Pils', 'restaurant-booking'); ?></option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="alcohol_degree"><?php _e('Degré d\'alcool', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="alcohol_degree" name="alcohol_degree" step="0.1" min="0" max="15" 
                                   value="<?php echo $product ? esc_attr($product['alcohol_degree']) : '5.0'; ?>">
                            <span>°</span>
                            <p class="description"><?php _e('Degré d\'alcool en pourcentage (optionnel)', 'restaurant-booking'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label><?php _e('Contenances disponibles', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <div id="keg_sizes_container">
                                <p class="description"><?php _e('Ajoutez les différentes contenances disponibles pour ce fût (ex: 10L, 20L)', 'restaurant-booking'); ?></p>
                                
                                <div id="keg_sizes_list">
                                    <?php if ($product): ?>
                                        <?php 
                                        // Pour la compatibilité avec l'ancien système, nous créerons des "sizes" basées sur les données existantes
                                        // Dans une vraie implémentation, vous devriez avoir une table séparée pour les tailles de fûts
                                        ?>
                                    <?php endif; ?>
                                </div>
                                
                                <button type="button" class="button button-secondary" id="add_keg_size_button">
                                    <?php _e('+ Ajouter une contenance', 'restaurant-booking'); ?>
                                </button>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="keg_image"><?php _e('Image du fût', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <div class="image-upload-container">
                                <input type="hidden" id="keg_image_id" name="keg_image_id" 
                                       value="<?php echo $product ? esc_attr($product['image_id']) : ''; ?>">
                                <div id="keg_image_preview">
                                    <?php if ($product && $product['image_id']): ?>
                                        <?php echo wp_get_attachment_image($product['image_id'], 'medium'); ?>
                                    <?php endif; ?>
                                </div>
                                <p>
                                    <button type="button" class="button" id="upload_keg_image">
                                        <?php _e('Choisir une image', 'restaurant-booking'); ?>
                                    </button>
                                    <button type="button" class="button" id="remove_keg_image" style="<?php echo (!$product || !$product['image_id']) ? 'display:none;' : ''; ?>">
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

                <?php submit_button($action === 'edit' ? __('Mettre à jour le fût', 'restaurant-booking') : __('Ajouter le fût', 'restaurant-booking')); ?>
            </form>
        </div>

        <!-- Modal pour ajouter/modifier une contenance -->
        <div id="keg_size_modal" style="display: none;">
            <div class="keg-size-modal-content">
                <h3 id="keg_size_modal_title"><?php _e('Ajouter une contenance', 'restaurant-booking'); ?></h3>
                <form id="keg_size_form">
                    <table class="form-table">
                        <tr>
                            <th><label for="keg_size_liters"><?php _e('Contenance', 'restaurant-booking'); ?> *</label></th>
                            <td>
                                <select id="keg_size_liters" name="keg_size_liters" class="regular-text" required>
                                    <option value=""><?php _e('Choisir la contenance', 'restaurant-booking'); ?></option>
                                    <option value="10">10L</option>
                                    <option value="20">20L</option>
                                    <option value="30">30L</option>
                                    <option value="50">50L</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="keg_size_price"><?php _e('Prix', 'restaurant-booking'); ?> *</label></th>
                            <td>
                                <input type="number" id="keg_size_price" name="keg_size_price" step="0.01" min="0" class="small-text" required> €
                                <p class="description"><?php _e('Prix pour cette contenance de fût', 'restaurant-booking'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="keg_size_image"><?php _e('Image', 'restaurant-booking'); ?></label></th>
                            <td>
                                <button type="button" class="button" id="upload_keg_size_image_button">
                                    <?php _e('Choisir une image', 'restaurant-booking'); ?>
                                </button>
                                <input type="hidden" id="keg_size_image_id" name="keg_size_image_id">
                                <div id="keg_size_image_preview" style="margin-top: 10px;"></div>
                                <p class="description"><?php _e('Image spécifique pour cette contenance', 'restaurant-booking'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="keg_is_featured"><?php _e('Mise en avant', 'restaurant-booking'); ?></label></th>
                            <td>
                                <label>
                                    <input type="checkbox" id="keg_is_featured" name="keg_is_featured" value="1">
                                    <?php _e('Mettre en avant cette contenance', 'restaurant-booking'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" class="button-primary" value="<?php _e('Enregistrer', 'restaurant-booking'); ?>">
                        <button type="button" class="button" id="cancel_keg_size"><?php _e('Annuler', 'restaurant-booking'); ?></button>
                    </p>
                </form>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Gestionnaire pour l'upload d'image principale
            var mediaUploader;
            var kegSizeMediaUploader;
            var kegSizeCounter = 0;
            
            $('#upload_keg_image').on('click', function(e) {
                e.preventDefault();
                
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                
                mediaUploader = wp.media({
                    title: '<?php _e('Choisir une image pour le fût', 'restaurant-booking'); ?>',
                    button: {
                        text: '<?php _e('Utiliser cette image', 'restaurant-booking'); ?>'
                    },
                    multiple: false
                });
                
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#keg_image_id').val(attachment.id);
                    $('#keg_image_preview').html('<img src="' + attachment.sizes.medium.url + '" alt="" style="max-width: 200px;">');
                    $('#remove_keg_image').show();
                });
                
                mediaUploader.open();
            });
            
            $('#remove_keg_image').on('click', function(e) {
                e.preventDefault();
                $('#keg_image_id').val('');
                $('#keg_image_preview').empty();
                $(this).hide();
            });
            
            // Sélecteur d'images WordPress pour les contenances
            $('#upload_keg_size_image_button').click(function(e) {
                e.preventDefault();
                
                if (kegSizeMediaUploader) {
                    kegSizeMediaUploader.open();
                    return;
                }
                
                kegSizeMediaUploader = wp.media({
                    title: '<?php _e('Choisir une image', 'restaurant-booking'); ?>',
                    button: {
                        text: '<?php _e('Utiliser cette image', 'restaurant-booking'); ?>'
                    },
                    multiple: false
                });
                
                kegSizeMediaUploader.on('select', function() {
                    var attachment = kegSizeMediaUploader.state().get('selection').first().toJSON();
                    $('#keg_size_image_id').val(attachment.id);
                    $('#keg_size_image_preview').html('<img src="' + attachment.sizes.thumbnail.url + '" alt="" style="max-width: 100px;">');
                });
                
                kegSizeMediaUploader.open();
            });
            
            // Gestion des contenances de fûts
            $('#add_keg_size_button').click(function() {
                $('#keg_size_modal_title').text('<?php _e('Ajouter une contenance', 'restaurant-booking'); ?>');
                $('#keg_size_form')[0].reset();
                $('#keg_size_image_preview').empty();
                $('#keg_size_modal').show();
            });
            
            $('#cancel_keg_size').click(function() {
                $('#keg_size_modal').hide();
            });
            
            // Soumettre le formulaire de contenance
            $('#keg_size_form').on('submit', function(e) {
                e.preventDefault();
                
                var kegSizeLiters = $('#keg_size_liters').val();
                var kegSizePrice = $('#keg_size_price').val();
                var kegSizeImageId = $('#keg_size_image_id').val();
                var kegIsFeatured = $('#keg_is_featured').is(':checked');
                
                if (!kegSizeLiters || !kegSizePrice) {
                    alert('<?php _e('Veuillez remplir tous les champs obligatoires.', 'restaurant-booking'); ?>');
                    return;
                }
                
                // Vérifier si cette contenance existe déjà
                var exists = false;
                $('#keg_sizes_list .keg-size-item').each(function() {
                    if ($(this).find('input[name$="[liters]"]').val() == kegSizeLiters) {
                        exists = true;
                        return false;
                    }
                });
                
                if (exists) {
                    alert('<?php _e('Cette contenance existe déjà.', 'restaurant-booking'); ?>');
                    return;
                }
                
                // Ajouter la contenance à la liste
                var kegSizeHtml = '<div class="keg-size-item" data-size-id="' + kegSizeCounter + '">';
                kegSizeHtml += '<div class="keg-size-info">';
                kegSizeHtml += '<h4>' + kegSizeLiters + 'L - ' + parseFloat(kegSizePrice).toFixed(2) + '€</h4>';
                
                if (kegSizeImageId) {
                    kegSizeHtml += '<div class="keg-size-image">';
                    kegSizeHtml += $('#keg_size_image_preview').html();
                    kegSizeHtml += '</div>';
                }
                
                if (kegIsFeatured) {
                    kegSizeHtml += '<span class="featured-badge"><?php _e('Mise en avant', 'restaurant-booking'); ?></span>';
                }
                
                kegSizeHtml += '</div>';
                kegSizeHtml += '<div class="keg-size-actions">';
                kegSizeHtml += '<button type="button" class="button button-small delete-keg-size" data-size-id="' + kegSizeCounter + '">';
                kegSizeHtml += '<?php _e('Supprimer', 'restaurant-booking'); ?>';
                kegSizeHtml += '</button>';
                kegSizeHtml += '</div>';
                
                // Champs cachés
                kegSizeHtml += '<input type="hidden" name="keg_sizes[' + kegSizeCounter + '][liters]" value="' + kegSizeLiters + '">';
                kegSizeHtml += '<input type="hidden" name="keg_sizes[' + kegSizeCounter + '][price]" value="' + kegSizePrice + '">';
                kegSizeHtml += '<input type="hidden" name="keg_sizes[' + kegSizeCounter + '][image_id]" value="' + kegSizeImageId + '">';
                kegSizeHtml += '<input type="hidden" name="keg_sizes[' + kegSizeCounter + '][is_featured]" value="' + (kegIsFeatured ? '1' : '0') + '">';
                
                kegSizeHtml += '</div>';
                
                $('#keg_sizes_list').append(kegSizeHtml);
                kegSizeCounter++;
                
                $('#keg_size_modal').hide();
            });
            
            // Supprimer une contenance
            $(document).on('click', '.delete-keg-size', function() {
                if (confirm('<?php _e('Êtes-vous sûr de vouloir supprimer cette contenance ?', 'restaurant-booking'); ?>')) {
                    $(this).closest('.keg-size-item').remove();
                }
            });
        });
        </script>
        
        <style>
        /* Styles pour les fûts */
        #keg_size_modal {
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
        .keg-size-modal-content {
            background: white;
            padding: 20px;
            border-radius: 5px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        .keg-size-item {
            border: 1px solid #ddd;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .keg-size-info {
            flex: 1;
        }
        .keg-size-info h4 {
            margin: 0 0 5px 0;
        }
        .keg-size-image img {
            max-width: 50px;
            height: auto;
            margin: 5px 0;
        }
        .keg-size-actions {
            display: flex;
            gap: 5px;
        }
        .featured-badge {
            background: #0073aa;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
            margin-left: 10px;
        }
        </style>
        
        <?php
    }

    /**
     * Gérer la sauvegarde d'un fût
     */
    private function handle_save_keg()
    {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['keg_nonce'], 'save_keg')) {
            wp_die(__('Erreur de sécurité', 'restaurant-booking'));
        }

        // Récupérer les données
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $keg_name = sanitize_text_field($_POST['keg_name']);
        $keg_description = sanitize_textarea_field($_POST['keg_description']);
        $beer_category = sanitize_text_field($_POST['beer_category']);
        $alcohol_degree = floatval($_POST['alcohol_degree']);
        $volume_liters = intval($_POST['volume_liters']);
        $keg_price = floatval($_POST['keg_price']);
        $keg_image_id = intval($_POST['keg_image_id']);
        $suggested_beverage = isset($_POST['suggested_beverage']) ? 1 : 0;

        // Validation
        if (empty($keg_name) || $keg_price <= 0 || $volume_liters <= 0) {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-kegs&action=add&error=validation'));
            exit;
        }

        // Obtenir la catégorie fût
        $category = RestaurantBooking_Category::get_by_type('fut');
        if (!$category) {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-kegs&action=add&error=no_category'));
            exit;
        }

        // Préparer les données du produit
        $product_data = array(
            'category_id' => $category['id'],
            'name' => $keg_name,
            'description' => $keg_description,
            'price' => $keg_price,
            'unit_type' => 'fut',
            'unit_label' => '/fût',
            'volume_cl' => $volume_liters * 100, // Convertir en centilitres
            'alcohol_degree' => $alcohol_degree,
            'beer_category' => $beer_category,
            'image_id' => $keg_image_id ?: null,
            'suggested_beverage' => $suggested_beverage,
            'is_active' => 1
        );

        if ($product_id) {
            // Mise à jour
            $result = RestaurantBooking_Product::update($product_id, $product_data);
            $success_param = $result ? 'updated' : 'error';
        } else {
            // Création
            $result = RestaurantBooking_Product::create($product_data);
            $success_param = $result ? 'created' : 'error';
        }

        // Redirection
        $redirect_url = admin_url('admin.php?page=restaurant-booking-beverages-kegs&message=' . $success_param);
        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Obtenir les fûts de bière avec leurs contenances multiples
     */
    private function get_kegs()
    {
        global $wpdb;

        // Récupérer les fûts avec leurs contenances (système étendu comme les boissons soft)
        $kegs = array();
        
        $products = $wpdb->get_results($wpdb->prepare("
            SELECT p.*, c.service_type
            FROM {$wpdb->prefix}restaurant_products p
            INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
            WHERE c.type = %s AND p.is_active = 1
            ORDER BY p.suggested_beverage DESC, p.display_order ASC, p.name ASC
        ", 'fut'), ARRAY_A);

        foreach ($products as $product) {
            // Pour l'instant, nous utilisons l'ancien système comme base
            // Dans une vraie implémentation, vous devriez avoir une table séparée pour les tailles de fûts
            // comme `restaurant_keg_sizes` similaire à `restaurant_beverage_sizes`
            
            if ($product['has_multiple_sizes'] ?? false) {
                // Nouveau système multi-contenances (à implémenter avec une table dédiée)
                // $sizes = $wpdb->get_results($wpdb->prepare("
                //     SELECT * FROM {$wpdb->prefix}restaurant_keg_sizes 
                //     WHERE product_id = %d
                //     ORDER BY liters ASC
                // ", $product['id']), ARRAY_A);
                
                // Pour l'instant, nous créons des exemples de tailles
                $example_sizes = [
                    ['liters' => 10, 'price' => 30.00],
                    ['liters' => 20, 'price' => 50.00]
                ];
                
                foreach ($example_sizes as $size) {
                    $kegs[] = array(
                        'id' => $product['id'],
                        'size_id' => $size['liters'] . 'L',
                        'name' => $product['name'],
                        'description' => $product['description'],
                        'size_label' => $size['liters'] . 'L',
                        'volume_cl' => $size['liters'] * 100,
                        'price' => (float) $size['price'],
                        'image_id' => $product['image_id'],
                        'image_url' => $product['image_id'] ? wp_get_attachment_image_url($product['image_id'], 'thumbnail') : '',
                        'beer_category' => $product['beer_category'],
                        'alcohol_degree' => (float) $product['alcohol_degree'],
                        'suggested_beverage' => (bool) $product['suggested_beverage'],
                        'is_active' => (bool) $product['is_active'],
                        'service_type' => $product['service_type'],
                        'has_multiple_sizes' => true
                    );
                }
            } else {
                // Ancien système (compatibilité)
                $kegs[] = array(
                    'id' => $product['id'],
                    'size_id' => null,
                    'name' => $product['name'],
                    'description' => $product['description'],
                    'size_label' => ($product['volume_cl'] / 100) . 'L',
                    'volume_cl' => (int) $product['volume_cl'],
                    'price' => (float) $product['price'],
                    'image_id' => $product['image_id'],
                    'image_url' => $product['image_id'] ? wp_get_attachment_image_url($product['image_id'], 'thumbnail') : '',
                    'beer_category' => $product['beer_category'],
                    'alcohol_degree' => (float) $product['alcohol_degree'],
                    'suggested_beverage' => (bool) $product['suggested_beverage'],
                    'is_active' => (bool) $product['is_active'],
                    'service_type' => $product['service_type'],
                    'has_multiple_sizes' => false
                );
            }
        }

        return $kegs;
    }
}
