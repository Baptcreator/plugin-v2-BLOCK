<?php
/**
 * Vue d'édition d'un devis
 *
 * @package RestaurantBooking
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Variables disponibles: $quote
if (!$quote) {
    wp_die(__('Devis introuvable', 'restaurant-booking'));
}

// Traitement du formulaire
if ($_POST && wp_verify_nonce($_POST['_wpnonce'], 'edit_quote_' . $quote['id'])) {
    $update_data = array();
    
    if (isset($_POST['status'])) {
        $update_data['status'] = sanitize_text_field($_POST['status']);
    }
    
    if (isset($_POST['event_date'])) {
        $update_data['event_date'] = sanitize_text_field($_POST['event_date']);
    }
    
    if (isset($_POST['guest_count'])) {
        $update_data['guest_count'] = (int) $_POST['guest_count'];
    }
    
    if (isset($_POST['event_duration'])) {
        $update_data['event_duration'] = (int) $_POST['event_duration'];
    }
    
    if (isset($_POST['postal_code'])) {
        $update_data['postal_code'] = sanitize_text_field($_POST['postal_code']);
    }
    
    // Mise à jour des données client si fournies
    if (isset($_POST['customer_name']) || isset($_POST['customer_email']) || isset($_POST['customer_phone'])) {
        $customer_data = json_decode($quote['customer_data'] ?? '{}', true);
        if (isset($_POST['customer_name'])) $customer_data['name'] = sanitize_text_field($_POST['customer_name']);
        if (isset($_POST['customer_email'])) $customer_data['email'] = sanitize_email($_POST['customer_email']);
        if (isset($_POST['customer_phone'])) $customer_data['phone'] = sanitize_text_field($_POST['customer_phone']);
        if (isset($_POST['customer_company'])) $customer_data['company'] = sanitize_text_field($_POST['customer_company']);
        $update_data['customer_data'] = json_encode($customer_data, JSON_UNESCAPED_UNICODE);
    }
    
    if (!empty($update_data)) {
        $result = RestaurantBooking_Quote::update($quote['id'], $update_data);
        if (!is_wp_error($result)) {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-quotes&action=view&quote_id=' . $quote['id'] . '&message=updated'));
            exit;
        } else {
            $error_message = $result->get_error_message();
        }
    }
}

// Décoder les données JSON (gérer le cas où elles peuvent déjà être décodées)
$customer_data_raw = $quote['customer_data'] ?? '{}';
$customer_data = is_array($customer_data_raw) ? $customer_data_raw : (json_decode($customer_data_raw, true) ?: []);
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php printf(__('Modifier le devis #%s', 'restaurant-booking'), $quote['quote_number']); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=restaurant-booking-quotes&action=view&quote_id=' . $quote['id']); ?>" class="page-title-action">
        <?php _e('← Voir le devis', 'restaurant-booking'); ?>
    </a>
    <a href="<?php echo admin_url('admin.php?page=restaurant-booking-quotes'); ?>" class="page-title-action">
        <?php _e('← Retour à la liste', 'restaurant-booking'); ?>
    </a>
    <hr class="wp-header-end">

    <?php if (isset($error_message)): ?>
        <div class="notice notice-error">
            <p><?php echo esc_html($error_message); ?></p>
        </div>
    <?php endif; ?>

    <form method="post">
        <?php wp_nonce_field('edit_quote_' . $quote['id']); ?>
        
        <div class="quote-edit-container" style="display: flex; gap: 20px;">
            <!-- Colonne principale -->
            <div class="quote-main-edit" style="flex: 2;">
                <!-- Informations générales -->
                <div class="postbox">
                    <h2 class="hndle"><span><?php _e('Informations générales', 'restaurant-booking'); ?></span></h2>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th><?php _e('Numéro de devis', 'restaurant-booking'); ?></th>
                                <td><strong><?php echo esc_html($quote['quote_number']); ?></strong> <em>(non modifiable)</em></td>
                            </tr>
                            <tr>
                                <th><?php _e('Service', 'restaurant-booking'); ?></th>
                                <td>
                                    <?php 
                                    switch($quote['service_type']) {
                                        case 'restaurant': echo __('Restaurant', 'restaurant-booking'); break;
                                        case 'remorque': echo __('Remorque', 'restaurant-booking'); break;
                                        default: echo esc_html($quote['service_type']);
                                    }
                                    ?>
                                    <em>(non modifiable)</em>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="event_date"><?php _e('Date événement', 'restaurant-booking'); ?></label></th>
                                <td>
                                    <input type="date" id="event_date" name="event_date" 
                                           value="<?php echo esc_attr($quote['event_date']); ?>" class="regular-text">
                                </td>
                            </tr>
                            <tr>
                                <th><label for="event_duration"><?php _e('Durée (heures)', 'restaurant-booking'); ?></label></th>
                                <td>
                                    <input type="number" id="event_duration" name="event_duration" 
                                           value="<?php echo esc_attr($quote['event_duration']); ?>" 
                                           min="1" max="24" class="small-text">
                                </td>
                            </tr>
                            <tr>
                                <th><label for="guest_count"><?php _e('Nombre de convives', 'restaurant-booking'); ?></label></th>
                                <td>
                                    <input type="number" id="guest_count" name="guest_count" 
                                           value="<?php echo esc_attr($quote['guest_count']); ?>" 
                                           min="1" class="small-text">
                                </td>
                            </tr>
                            <tr>
                                <th><label for="postal_code"><?php _e('Code postal', 'restaurant-booking'); ?></label></th>
                                <td>
                                    <input type="text" id="postal_code" name="postal_code" 
                                           value="<?php echo esc_attr($quote['postal_code'] ?: ''); ?>" 
                                           class="regular-text">
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Informations client -->
                <div class="postbox">
                    <h2 class="hndle"><span><?php _e('Informations client', 'restaurant-booking'); ?></span></h2>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th><label for="customer_name"><?php _e('Nom', 'restaurant-booking'); ?></label></th>
                                <td>
                                    <input type="text" id="customer_name" name="customer_name" 
                                           value="<?php echo esc_attr($customer_data['name'] ?? ''); ?>" 
                                           class="regular-text">
                                </td>
                            </tr>
                            <tr>
                                <th><label for="customer_email"><?php _e('Email', 'restaurant-booking'); ?></label></th>
                                <td>
                                    <input type="email" id="customer_email" name="customer_email" 
                                           value="<?php echo esc_attr($customer_data['email'] ?? ''); ?>" 
                                           class="regular-text">
                                </td>
                            </tr>
                            <tr>
                                <th><label for="customer_phone"><?php _e('Téléphone', 'restaurant-booking'); ?></label></th>
                                <td>
                                    <input type="tel" id="customer_phone" name="customer_phone" 
                                           value="<?php echo esc_attr($customer_data['phone'] ?? ''); ?>" 
                                           class="regular-text">
                                </td>
                            </tr>
                            <tr>
                                <th><label for="customer_company"><?php _e('Entreprise', 'restaurant-booking'); ?></label></th>
                                <td>
                                    <input type="text" id="customer_company" name="customer_company" 
                                           value="<?php echo esc_attr($customer_data['company'] ?? ''); ?>" 
                                           class="regular-text">
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Colonne latérale -->
            <div class="quote-sidebar-edit" style="flex: 1;">
                <!-- Statut -->
                <div class="postbox">
                    <h2 class="hndle"><span><?php _e('Statut', 'restaurant-booking'); ?></span></h2>
                    <div class="inside">
                        <p><strong><?php _e('Statut actuel:', 'restaurant-booking'); ?></strong></p>
                        <select name="status" class="regular-text">
                            <option value="sent" <?php selected($quote['status'], 'sent'); ?>><?php _e('Envoyé', 'restaurant-booking'); ?></option>
                            <option value="confirmed" <?php selected($quote['status'], 'confirmed'); ?>><?php _e('Confirmé', 'restaurant-booking'); ?></option>
                            <option value="cancelled" <?php selected($quote['status'], 'cancelled'); ?>><?php _e('Annulé', 'restaurant-booking'); ?></option>
                        </select>
                        <p class="description"><?php _e('Changez le statut du devis selon son état actuel.', 'restaurant-booking'); ?></p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="postbox">
                    <h2 class="hndle"><span><?php _e('Actions', 'restaurant-booking'); ?></span></h2>
                    <div class="inside">
                        <p class="submit">
                            <input type="submit" class="button button-primary" value="<?php _e('Sauvegarder les modifications', 'restaurant-booking'); ?>">
                        </p>
                        <p>
                            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-quotes&action=view&quote_id=' . $quote['id']); ?>" class="button">
                                <?php _e('Annuler', 'restaurant-booking'); ?>
                            </a>
                        </p>
                    </div>
                </div>

                <!-- Récapitulatif des prix (lecture seule) -->
                <div class="postbox">
                    <h2 class="hndle"><span><?php _e('Récapitulatif des prix', 'restaurant-booking'); ?></span></h2>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th><?php _e('Prix de base', 'restaurant-booking'); ?></th>
                                <td><?php echo number_format($quote['base_price'], 2, ',', ' '); ?> €</td>
                            </tr>
                            <tr>
                                <th><?php _e('Suppléments', 'restaurant-booking'); ?></th>
                                <td><?php echo number_format($quote['supplements_total'], 2, ',', ' '); ?> €</td>
                            </tr>
                            <tr>
                                <th><?php _e('Produits', 'restaurant-booking'); ?></th>
                                <td><?php echo number_format($quote['products_total'], 2, ',', ' '); ?> €</td>
                            </tr>
                            <tr style="border-top: 2px solid #ddd; font-weight: bold;">
                                <th><?php _e('Total', 'restaurant-booking'); ?></th>
                                <td><?php echo number_format($quote['total_price'], 2, ',', ' '); ?> €</td>
                            </tr>
                        </table>
                        <p class="description"><?php _e('Les prix sont calculés automatiquement et ne peuvent pas être modifiés manuellement.', 'restaurant-booking'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.quote-status {
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
}
.status-draft { background: #f0f0f1; color: #646970; }
.status-sent { background: #d1ecf1; color: #0c5460; }
.status-confirmed { background: #d4edda; color: #155724; }
.status-cancelled { background: #f8d7da; color: #721c24; }

/* Marges internes pour toutes les postbox de la page */
.postbox .inside {
    padding: 15px !important;
}

/* Marges pour les titres des postbox */
.postbox .hndle {
    padding: 12px 15px !important;
}
</style>
