<?php
/**
 * Vue du formulaire de produit
 *
 * @package RestaurantBooking
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$is_edit = !empty($product);
$title = $is_edit ? __('Modifier le produit', 'restaurant-booking') : __('Ajouter un produit', 'restaurant-booking');
$nonce_action = $is_edit ? 'restaurant_booking_edit_product' : 'restaurant_booking_add_product';

// Obtenir les catégories
$categories = RestaurantBooking_Category::get_list(array('is_active' => 1, 'limit' => 999));
?>

<div class="wrap">
    <h1><?php echo esc_html($title); ?></h1>
    
    <form method="post" enctype="multipart/form-data">
        <?php wp_nonce_field($nonce_action); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="product_name"><?php _e('Nom du produit', 'restaurant-booking'); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <input type="text" id="product_name" name="name" class="regular-text" 
                           value="<?php echo esc_attr($product['name'] ?? ''); ?>" required>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="product_category"><?php _e('Catégorie', 'restaurant-booking'); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <select id="product_category" name="category_id" required>
                        <option value=""><?php _e('Sélectionner une catégorie', 'restaurant-booking'); ?></option>
                        <?php foreach ($categories['categories'] as $category): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                    <?php selected($product['category_id'] ?? '', $category['id']); ?>>
                                <?php echo esc_html($category['name']); ?> (<?php echo esc_html($category['type']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="product_description"><?php _e('Description', 'restaurant-booking'); ?></label>
                </th>
                <td>
                    <textarea id="product_description" name="description" class="large-text" rows="4"><?php echo esc_textarea($product['description'] ?? ''); ?></textarea>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="product_price"><?php _e('Prix', 'restaurant-booking'); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <input type="number" id="product_price" name="price" class="small-text" 
                           value="<?php echo esc_attr($product['price'] ?? ''); ?>" 
                           step="0.01" min="0" required> €
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="product_unit_type"><?php _e('Type d\'unité', 'restaurant-booking'); ?></label>
                </th>
                <td>
                    <select id="product_unit_type" name="unit_type">
                        <option value=""><?php _e('Aucun', 'restaurant-booking'); ?></option>
                        <option value="piece" <?php selected($product['unit_type'] ?? '', 'piece'); ?>><?php _e('Pièce', 'restaurant-booking'); ?></option>
                        <option value="gramme" <?php selected($product['unit_type'] ?? '', 'gramme'); ?>><?php _e('Gramme', 'restaurant-booking'); ?></option>
                        <option value="litre" <?php selected($product['unit_type'] ?? '', 'litre'); ?>><?php _e('Litre', 'restaurant-booking'); ?></option>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="product_unit_label"><?php _e('Libellé d\'unité', 'restaurant-booking'); ?></label>
                </th>
                <td>
                    <input type="text" id="product_unit_label" name="unit_label" class="regular-text" 
                           value="<?php echo esc_attr($product['unit_label'] ?? ''); ?>" 
                           placeholder="ex: par personne, par portion">
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="product_min_quantity"><?php _e('Quantité minimum', 'restaurant-booking'); ?></label>
                </th>
                <td>
                    <input type="number" id="product_min_quantity" name="min_quantity" class="small-text" 
                           value="<?php echo esc_attr($product['min_quantity'] ?? 1); ?>" min="0">
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="product_max_quantity"><?php _e('Quantité maximum', 'restaurant-booking'); ?></label>
                </th>
                <td>
                    <input type="number" id="product_max_quantity" name="max_quantity" class="small-text" 
                           value="<?php echo esc_attr($product['max_quantity'] ?? ''); ?>" min="0">
                    <p class="description"><?php _e('Laisser vide pour aucune limite', 'restaurant-booking'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="product_has_supplement"><?php _e('Suppléments', 'restaurant-booking'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" id="product_has_supplement" name="has_supplement" value="1" 
                               <?php checked($product['has_supplement'] ?? false); ?>>
                        <?php _e('Ce produit a des suppléments', 'restaurant-booking'); ?>
                    </label>
                </td>
            </tr>
            
            <div id="supplement-fields" style="<?php echo empty($product['has_supplement']) ? 'display: none;' : ''; ?>">
                <tr>
                    <th scope="row">
                        <label for="supplement_name"><?php _e('Nom du supplément', 'restaurant-booking'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="supplement_name" name="supplement_name" class="regular-text" 
                               value="<?php echo esc_attr($product['supplement_name'] ?? ''); ?>">
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="supplement_price"><?php _e('Prix du supplément', 'restaurant-booking'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="supplement_price" name="supplement_price" class="small-text" 
                               value="<?php echo esc_attr($product['supplement_price'] ?? 0); ?>" 
                               step="0.01" min="0"> €
                    </td>
                </tr>
            </div>
            
            <tr>
                <th scope="row">
                    <label for="product_display_order"><?php _e('Ordre d\'affichage', 'restaurant-booking'); ?></label>
                </th>
                <td>
                    <input type="number" id="product_display_order" name="display_order" class="small-text" 
                           value="<?php echo esc_attr($product['display_order'] ?? 0); ?>" min="0" max="999">
                    <p class="description"><?php _e('Plus le nombre est petit, plus le produit apparaîtra en premier.', 'restaurant-booking'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="product_is_active"><?php _e('Statut', 'restaurant-booking'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" id="product_is_active" name="is_active" value="1" 
                               <?php checked($product['is_active'] ?? true); ?>>
                        <?php _e('Produit actif', 'restaurant-booking'); ?>
                    </label>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <?php submit_button($is_edit ? __('Mettre à jour', 'restaurant-booking') : __('Ajouter le produit', 'restaurant-booking'), 'primary', 'submit', false); ?>
            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-products'); ?>" class="button button-secondary">
                <?php _e('Annuler', 'restaurant-booking'); ?>
            </a>
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    $('#product_has_supplement').change(function() {
        if ($(this).is(':checked')) {
            $('#supplement-fields').show();
        } else {
            $('#supplement-fields').hide();
        }
    });
});
</script>
