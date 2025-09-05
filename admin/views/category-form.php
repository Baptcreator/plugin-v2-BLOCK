<?php
/**
 * Vue du formulaire de catégorie
 *
 * @package RestaurantBooking
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Variables disponibles: $category, $is_edit, $category_types, $service_types, $error_message
?>

<div class="wrap restaurant-booking-admin-page">
    <div class="restaurant-booking-admin-header">
        <h1>
            <?php if ($is_edit): ?>
                <?php printf(__('Modifier la catégorie : %s', 'restaurant-booking'), esc_html($category['name'])); ?>
            <?php else: ?>
                <?php _e('Ajouter une catégorie', 'restaurant-booking'); ?>
            <?php endif; ?>
        </h1>
        <div class="restaurant-booking-admin-actions">
            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-categories'); ?>" 
               class="button">
                <?php _e('← Retour à la liste', 'restaurant-booking'); ?>
            </a>
        </div>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="notice notice-error">
            <p><?php echo esc_html($error_message); ?></p>
        </div>
    <?php endif; ?>

    <div class="restaurant-booking-form">
        <form method="post" action="" class="restaurant-booking-category-form">
            <?php wp_nonce_field('save_category'); ?>
            
            <div class="restaurant-booking-form-row">
                <!-- Informations générales -->
                <div class="restaurant-booking-widget">
                    <h2><?php _e('Informations générales', 'restaurant-booking'); ?></h2>
                    
                    <div class="restaurant-booking-form-group">
                        <label for="name"><?php _e('Nom de la catégorie', 'restaurant-booking'); ?> *</label>
                        <input type="text" id="name" name="name" 
                               value="<?php echo esc_attr($category['name'] ?? ''); ?>" 
                               required>
                        <p class="description">
                            <?php _e('Le nom affiché de la catégorie (ex: "Plats Signature")', 'restaurant-booking'); ?>
                        </p>
                    </div>

                    <div class="restaurant-booking-form-group">
                        <label for="slug"><?php _e('Slug', 'restaurant-booking'); ?></label>
                        <input type="text" id="slug" name="slug" 
                               value="<?php echo esc_attr($category['slug'] ?? ''); ?>">
                        <p class="description">
                            <?php _e('Identifiant technique (généré automatiquement si vide)', 'restaurant-booking'); ?>
                        </p>
                    </div>

                    <div class="restaurant-booking-form-group">
                        <label for="type"><?php _e('Type de catégorie', 'restaurant-booking'); ?> *</label>
                        <select id="type" name="type" required>
                            <option value=""><?php _e('Sélectionner un type...', 'restaurant-booking'); ?></option>
                            <?php foreach ($category_types as $type_key => $type_label): ?>
                                <option value="<?php echo esc_attr($type_key); ?>" 
                                        <?php selected($category['type'] ?? '', $type_key); ?>>
                                    <?php echo esc_html($type_label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php _e('Le type détermine la nature des produits de cette catégorie', 'restaurant-booking'); ?>
                        </p>
                    </div>

                    <div class="restaurant-booking-form-group">
                        <label for="service_type"><?php _e('Type de service', 'restaurant-booking'); ?></label>
                        <select id="service_type" name="service_type">
                            <?php foreach ($service_types as $service_key => $service_label): ?>
                                <option value="<?php echo esc_attr($service_key); ?>" 
                                        <?php selected($category['service_type'] ?? 'both', $service_key); ?>>
                                    <?php echo esc_html($service_label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php _e('Pour quel(s) service(s) cette catégorie est-elle disponible ?', 'restaurant-booking'); ?>
                        </p>
                    </div>

                    <div class="restaurant-booking-form-group full-width">
                        <label for="description"><?php _e('Description', 'restaurant-booking'); ?></label>
                        <textarea id="description" name="description" rows="4"><?php echo esc_textarea($category['description'] ?? ''); ?></textarea>
                        <p class="description">
                            <?php _e('Description optionnelle affichée avec la catégorie', 'restaurant-booking'); ?>
                        </p>
                    </div>
                </div>

                <!-- Contraintes et règles -->
                <div class="restaurant-booking-widget">
                    <h2><?php _e('Contraintes de sélection', 'restaurant-booking'); ?></h2>
                    
                    <div class="restaurant-booking-form-group">
                        <label>
                            <input type="checkbox" name="is_required" value="1" 
                                   <?php checked($category['is_required'] ?? false); ?>>
                            <?php _e('Sélection obligatoire', 'restaurant-booking'); ?>
                        </label>
                        <p class="description">
                            <?php _e('Les clients doivent obligatoirement sélectionner au moins un produit de cette catégorie', 'restaurant-booking'); ?>
                        </p>
                    </div>

                    <div class="restaurant-booking-form-row">
                        <div class="restaurant-booking-form-group">
                            <label for="min_selection"><?php _e('Sélections minimum', 'restaurant-booking'); ?></label>
                            <input type="number" id="min_selection" name="min_selection" 
                                   value="<?php echo esc_attr($category['min_selection'] ?? 0); ?>" 
                                   min="0">
                            <p class="description">
                                <?php _e('Nombre minimum de produits différents à sélectionner', 'restaurant-booking'); ?>
                            </p>
                        </div>

                        <div class="restaurant-booking-form-group">
                            <label for="max_selection"><?php _e('Sélections maximum', 'restaurant-booking'); ?></label>
                            <input type="number" id="max_selection" name="max_selection" 
                                   value="<?php echo esc_attr($category['max_selection'] ?? ''); ?>" 
                                   min="1">
                            <p class="description">
                                <?php _e('Nombre maximum de produits différents (vide = illimité)', 'restaurant-booking'); ?>
                            </p>
                        </div>
                    </div>

                    <div class="restaurant-booking-form-group">
                        <label>
                            <input type="checkbox" name="min_per_person" value="1" 
                                   <?php checked($category['min_per_person'] ?? false); ?>>
                            <?php _e('Minimum 1 par convive', 'restaurant-booking'); ?>
                        </label>
                        <p class="description">
                            <?php _e('La quantité totale sélectionnée doit être au minimum égale au nombre de convives', 'restaurant-booking'); ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="restaurant-booking-form-row">
                <!-- Affichage et ordre -->
                <div class="restaurant-booking-widget">
                    <h2><?php _e('Affichage', 'restaurant-booking'); ?></h2>
                    
                    <div class="restaurant-booking-form-group">
                        <label for="display_order"><?php _e('Ordre d\'affichage', 'restaurant-booking'); ?></label>
                        <input type="number" id="display_order" name="display_order" 
                               value="<?php echo esc_attr($category['display_order'] ?? 0); ?>" 
                               min="0">
                        <p class="description">
                            <?php _e('Plus le nombre est petit, plus la catégorie apparaît en premier', 'restaurant-booking'); ?>
                        </p>
                    </div>

                    <div class="restaurant-booking-form-group">
                        <label>
                            <input type="checkbox" name="is_active" value="1" 
                                   <?php checked($category['is_active'] ?? true); ?>>
                            <?php _e('Catégorie active', 'restaurant-booking'); ?>
                        </label>
                        <p class="description">
                            <?php _e('Seules les catégories actives sont affichées dans les formulaires de devis', 'restaurant-booking'); ?>
                        </p>
                    </div>
                </div>

                <!-- Aperçu -->
                <?php if ($is_edit): ?>
                <div class="restaurant-booking-widget">
                    <h2><?php _e('Informations', 'restaurant-booking'); ?></h2>
                    
                    <div class="category-info">
                        <p>
                            <strong><?php _e('Créée le :', 'restaurant-booking'); ?></strong><br>
                            <?php echo date_i18n('d/m/Y H:i', strtotime($category['created_at'])); ?>
                        </p>
                        
                        <p>
                            <strong><?php _e('Dernière modification :', 'restaurant-booking'); ?></strong><br>
                            <?php echo date_i18n('d/m/Y H:i', strtotime($category['updated_at'])); ?>
                        </p>
                        
                        <p>
                            <strong><?php _e('Nombre de produits :', 'restaurant-booking'); ?></strong><br>
                            <?php echo $category['product_count'] ?? 0; ?>
                            <?php if (($category['product_count'] ?? 0) > 0): ?>
                                <br><a href="<?php echo admin_url('admin.php?page=restaurant-booking-products&category_id=' . $category['id']); ?>">
                                    <?php _e('Voir les produits', 'restaurant-booking'); ?>
                                </a>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Actions -->
            <div class="restaurant-booking-form-actions">
                <button type="submit" name="save_category" class="button button-primary button-large">
                    <?php if ($is_edit): ?>
                        <?php _e('Mettre à jour la catégorie', 'restaurant-booking'); ?>
                    <?php else: ?>
                        <?php _e('Créer la catégorie', 'restaurant-booking'); ?>
                    <?php endif; ?>
                </button>
                
                <a href="<?php echo admin_url('admin.php?page=restaurant-booking-categories'); ?>" 
                   class="button button-large">
                    <?php _e('Annuler', 'restaurant-booking'); ?>
                </a>
                
                <?php if ($is_edit): ?>
                    <button type="button" 
                            data-rb-action="duplicate_category" 
                            data-rb-data='{"category_id": <?php echo $category['id']; ?>}'
                            class="button button-secondary button-large">
                        <?php _e('Dupliquer', 'restaurant-booking'); ?>
                    </button>
                    
                    <button type="button" 
                            data-rb-action="delete_category" 
                            data-rb-data='{"category_id": <?php echo $category['id']; ?>}'
                            data-rb-confirm="<?php _e('Êtes-vous sûr de vouloir supprimer cette catégorie ? Cette action est irréversible.', 'restaurant-booking'); ?>"
                            class="button button-link-delete button-large">
                        <?php _e('Supprimer', 'restaurant-booking'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<style>
.restaurant-booking-category-form .restaurant-booking-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

@media (max-width: 1024px) {
    .restaurant-booking-category-form .restaurant-booking-form-row {
        grid-template-columns: 1fr;
    }
}

.restaurant-booking-form-actions {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-top: 20px;
    text-align: left;
}

.restaurant-booking-form-actions .button {
    margin-right: 10px;
    margin-bottom: 10px;
}

.category-info p {
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.category-info p:last-child {
    border-bottom: none;
}

/* Validation visuelle */
.restaurant-booking-form-group input.error,
.restaurant-booking-form-group select.error,
.restaurant-booking-form-group textarea.error {
    border-color: #c62828;
    box-shadow: 0 0 0 2px rgba(198, 40, 40, 0.1);
}

/* Aide contextuelle */
.restaurant-booking-form-group .description {
    font-size: 13px;
    color: #666;
    margin-top: 5px;
    font-style: italic;
    line-height: 1.4;
}

/* Types de catégories avec couleurs */
#type option[value="plat_signature"] { background: #e8f5e8; }
#type option[value="mini_boss"] { background: #fff3e0; }
#type option[value="accompagnement"] { background: #f3e5f5; }
#type option[value="buffet_sale"] { background: #e3f2fd; }
#type option[value="buffet_sucre"] { background: #fce4ec; }
#type option[value="soft"] { background: #e0f2f1; }
</style>

<script>
jQuery(document).ready(function($) {
    // Génération automatique du slug
    $('#name').on('input', function() {
        var name = $(this).val();
        var slug = name.toLowerCase()
            .replace(/[àáâãäå]/g, 'a')
            .replace(/[èéêë]/g, 'e')
            .replace(/[ìíîï]/g, 'i')
            .replace(/[òóôõö]/g, 'o')
            .replace(/[ùúûü]/g, 'u')
            .replace(/[ç]/g, 'c')
            .replace(/[^a-z0-9]/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '');
        
        if ($('#slug').val() === '' || $('#slug').data('auto-generated')) {
            $('#slug').val(slug).data('auto-generated', true);
        }
    });
    
    // Marquer le slug comme modifié manuellement
    $('#slug').on('input', function() {
        $(this).data('auto-generated', false);
    });
    
    // Validation des contraintes
    $('#min_selection, #max_selection').on('input', function() {
        var min = parseInt($('#min_selection').val()) || 0;
        var max = parseInt($('#max_selection').val()) || 0;
        
        if (max > 0 && min > max) {
            $('#max_selection').addClass('error');
            $('.constraint-error').remove();
            $('#max_selection').after('<p class="constraint-error" style="color: #c62828; font-size: 13px; margin-top: 5px;">Le maximum doit être supérieur ou égal au minimum</p>');
        } else {
            $('#max_selection').removeClass('error');
            $('.constraint-error').remove();
        }
    });
    
    // Prévisualisation du type de catégorie
    $('#type').on('change', function() {
        var type = $(this).val();
        var descriptions = {
            'plat_signature': 'Plats emblématiques du restaurant',
            'mini_boss': 'Petites portions gourmandes',
            'accompagnement': 'Accompagnements et garnitures',
            'buffet_sale': 'Sélection salée pour buffet',
            'buffet_sucre': 'Desserts et douceurs',
            'soft': 'Boissons sans alcool',
            'vin_blanc': 'Sélection de vins blancs',
            'vin_rouge': 'Sélection de vins rouges',
            'biere': 'Bières artisanales et classiques'
        };
        
        $('.type-description').remove();
        if (descriptions[type]) {
            $(this).after('<p class="type-description description">' + descriptions[type] + '</p>');
        }
    });
});
</script>
