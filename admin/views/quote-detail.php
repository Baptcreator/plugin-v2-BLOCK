<?php
/**
 * Vue de détail d'un devis
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

// Décoder les données JSON (gérer le cas où elles peuvent déjà être décodées)
$customer_data_raw = $quote['customer_data'] ?? '{}';
$customer_data = is_array($customer_data_raw) ? $customer_data_raw : (json_decode($customer_data_raw, true) ?: []);

$selected_products_raw = $quote['selected_products'] ?? '[]';
$selected_products = is_array($selected_products_raw) ? $selected_products_raw : (json_decode($selected_products_raw, true) ?: []);

$price_breakdown_raw = $quote['price_breakdown'] ?? '{}';
$price_breakdown = is_array($price_breakdown_raw) ? $price_breakdown_raw : (json_decode($price_breakdown_raw, true) ?: []);

// Debug pour diagnostiquer le problème d'affichage
if (RESTAURANT_BOOKING_DEBUG) {
    error_log("DEBUG QUOTE DETAIL - Quote ID: " . $quote['id']);
    error_log("DEBUG QUOTE DETAIL - Selected Products: " . print_r($selected_products, true));
    error_log("DEBUG QUOTE DETAIL - Customer Data: " . print_r($customer_data, true));
    error_log("DEBUG QUOTE DETAIL - Price Breakdown: " . print_r($price_breakdown, true));
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php printf(__('Devis #%s', 'restaurant-booking'), $quote['quote_number']); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=restaurant-booking-quotes&action=edit&quote_id=' . $quote['id']); ?>" class="page-title-action">
        <?php _e('Modifier', 'restaurant-booking'); ?>
    </a>
    <a href="<?php echo admin_url('admin.php?page=restaurant-booking-quotes'); ?>" class="page-title-action">
        <?php _e('← Retour à la liste', 'restaurant-booking'); ?>
    </a>
    <hr class="wp-header-end">

    <div class="quote-detail-container" style="display: flex; gap: 20px;">
        <!-- Colonne principale -->
        <div class="quote-main-info" style="flex: 2;">
            <!-- Informations générales -->
            <div class="postbox">
                <h2 class="hndle"><span><?php _e('Informations générales', 'restaurant-booking'); ?></span></h2>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th><?php _e('Numéro de devis', 'restaurant-booking'); ?></th>
                            <td><strong><?php echo esc_html($quote['quote_number']); ?></strong></td>
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
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Date événement', 'restaurant-booking'); ?></th>
                            <td><?php echo $quote['event_date'] ? date_i18n(get_option('date_format'), strtotime($quote['event_date'])) : '-'; ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('Durée', 'restaurant-booking'); ?></th>
                            <td><?php echo $quote['event_duration']; ?> heures</td>
                        </tr>
                        <tr>
                            <th><?php _e('Nombre de convives', 'restaurant-booking'); ?></th>
                            <td><?php echo $quote['guest_count']; ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('Code postal', 'restaurant-booking'); ?></th>
                            <td><?php echo esc_html($quote['postal_code'] ?: '-'); ?></td>
                        </tr>
                        <?php if ($quote['distance_km']): ?>
                        <tr>
                            <th><?php _e('Distance', 'restaurant-booking'); ?></th>
                            <td><?php echo $quote['distance_km']; ?> km</td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <!-- Informations client -->
            <?php if (!empty($customer_data)): ?>
            <div class="postbox">
                <h2 class="hndle"><span><?php _e('Informations client', 'restaurant-booking'); ?></span></h2>
                <div class="inside">
                    <table class="form-table">
                        <?php if (!empty($customer_data['firstname']) || !empty($customer_data['name'])): ?>
                        <tr>
                            <th><?php _e('Nom complet', 'restaurant-booking'); ?></th>
                            <td>
                                <?php 
                                $full_name = trim(($customer_data['firstname'] ?? '') . ' ' . ($customer_data['name'] ?? ''));
                                echo esc_html($full_name ?: ($customer_data['name'] ?? 'Non renseigné'));
                                ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($customer_data['email'])): ?>
                        <tr>
                            <th><?php _e('Email', 'restaurant-booking'); ?></th>
                            <td><a href="mailto:<?php echo esc_attr($customer_data['email']); ?>"><?php echo esc_html($customer_data['email']); ?></a></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($customer_data['phone'])): ?>
                        <tr>
                            <th><?php _e('Téléphone', 'restaurant-booking'); ?></th>
                            <td><a href="tel:<?php echo esc_attr($customer_data['phone']); ?>"><?php echo esc_html($customer_data['phone']); ?></a></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($customer_data['company'])): ?>
                        <tr>
                            <th><?php _e('Entreprise', 'restaurant-booking'); ?></th>
                            <td><?php echo esc_html($customer_data['company']); ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- DEBUG: Afficher la structure des données -->
            <?php if (defined('RESTAURANT_BOOKING_DEBUG') && RESTAURANT_BOOKING_DEBUG): ?>
                <div class="postbox">
                    <h2 class="hndle"><span>🐛 DEBUG - Données disponibles</span></h2>
                    <div class="inside">
                        <h4>price_breakdown:</h4>
                        <pre style="font-size: 11px; max-height: 200px; overflow: auto; background: #f9f9f9; padding: 10px;"><?php echo esc_html(print_r($price_breakdown, true)); ?></pre>
                        <h4>selected_products:</h4>
                        <pre style="font-size: 11px; max-height: 200px; overflow: auto; background: #f9f9f9; padding: 10px;"><?php echo esc_html(print_r($selected_products, true)); ?></pre>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Produits sélectionnés -->
            <?php if (!empty($price_breakdown['products']) || !empty($price_breakdown['beverages_detailed']) || !empty($price_breakdown['options']) || !empty($selected_products)): ?>
            <div class="postbox">
                <h2 class="hndle"><span><?php _e('Produits sélectionnés', 'restaurant-booking'); ?></span></h2>
                <div class="inside">
                    <table class="wp-list-table widefat">
                        <thead>
                            <tr>
                                <th><?php _e('Produit', 'restaurant-booking'); ?></th>
                                <th><?php _e('Quantité', 'restaurant-booking'); ?></th>
                                <th><?php _e('Prix unitaire', 'restaurant-booking'); ?></th>
                                <th><?php _e('Total', 'restaurant-booking'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Utiliser les données calculées de price_breakdown au lieu de selected_products
                            $display_calculated_admin_products = function() use ($price_breakdown) {
                                $products_displayed = false;
                                
                                // Afficher les produits calculés
                                if (!empty($price_breakdown['products']) && is_array($price_breakdown['products'])) {
                                    foreach ($price_breakdown['products'] as $product) {
                                        $products_displayed = true;
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo esc_html($product['name'] ?? 'Produit'); ?></strong>
                                                <?php if (!empty($product['category'])): ?>
                                                    <br><small style="color: #666;"><?php echo esc_html($product['category']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo intval($product['quantity'] ?? 0); ?></td>
                                            <td><?php echo number_format(floatval($product['price'] ?? 0), 2, ',', ' '); ?> €</td>
                                            <td><?php echo number_format(floatval($product['total'] ?? 0), 2, ',', ' '); ?> €</td>
                                        </tr>
                                        
                                        <!-- Options et sous-options hiérarchiques -->
                                        <?php if (!empty($product['options']) && is_array($product['options'])): ?>
                                            <?php foreach ($product['options'] as $option): ?>
                                                <tr style="background-color: #f9f9f9;">
                                                    <td style="padding-left: 30px;">└── <?php echo esc_html($option['name'] ?? 'Option'); ?></td>
                                                    <td><?php echo intval($option['quantity'] ?? 0); ?></td>
                                                    <td><?php echo number_format(floatval($option['price'] ?? 0), 2, ',', ' '); ?> €</td>
                                                    <td><?php echo number_format(floatval($option['total'] ?? 0), 2, ',', ' '); ?> €</td>
                                                </tr>
                                                
                                                <?php if (!empty($option['suboptions']) && is_array($option['suboptions'])): ?>
                                                    <?php foreach ($option['suboptions'] as $suboption): ?>
                                                        <tr style="background-color: #f5f5f5;">
                                                            <td style="padding-left: 50px;">└── <?php echo esc_html($suboption['name'] ?? 'Sous-option'); ?></td>
                                                            <td><?php echo intval($suboption['quantity'] ?? 0); ?></td>
                                                            <td><?php echo number_format(floatval($suboption['price'] ?? 0), 2, ',', ' '); ?> €</td>
                                                            <td><?php echo number_format(floatval($suboption['total'] ?? 0), 2, ',', ' '); ?> €</td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        <?php
                                    }
                                }
                                
                                // Afficher les boissons calculées
                                if (!empty($price_breakdown['beverages_detailed']) && is_array($price_breakdown['beverages_detailed'])) {
                                    foreach ($price_breakdown['beverages_detailed'] as $beverage) {
                                        $products_displayed = true;
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo esc_html($beverage['name'] ?? 'Boisson'); ?></strong>
                                                <?php if (!empty($beverage['size'])): ?>
                                                    <small> (<?php echo esc_html($beverage['size']); ?>)</small>
                                                <?php endif; ?>
                                                <?php if (!empty($beverage['type'])): ?>
                                                    <br><small style="color: #666;"><?php echo esc_html($beverage['type']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo intval($beverage['quantity'] ?? 0); ?></td>
                                            <td><?php echo number_format(floatval($beverage['price'] ?? 0), 2, ',', ' '); ?> €</td>
                                            <td><?php echo number_format(floatval($beverage['total'] ?? 0), 2, ',', ' '); ?> €</td>
                                        </tr>
                                        <?php
                                    }
                                }
                                
                                // Afficher les options remorque
                                if (!empty($price_breakdown['options']) && is_array($price_breakdown['options'])) {
                                    foreach ($price_breakdown['options'] as $option) {
                                        $products_displayed = true;
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo esc_html($option['name'] ?? 'Option'); ?></strong>
                                                <?php if (!empty($option['description'])): ?>
                                                    <br><small style="color: #666;"><?php echo esc_html($option['description']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo intval($option['quantity'] ?? 1); ?></td>
                                            <td><?php echo number_format(floatval($option['price'] ?? 0), 2, ',', ' '); ?> €</td>
                                            <td><?php echo number_format(floatval($option['total'] ?? $option['price'] ?? 0), 2, ',', ' '); ?> €</td>
                                        </tr>
                                        <?php
                                    }
                                }
                                
                                return $products_displayed;
                            };
                            
                            // Exécuter l'affichage des produits calculés
                            $products_displayed = $display_calculated_admin_products();
                            
                            // Fallback si aucune donnée calculée n'est disponible
                            if (!$products_displayed) {
                                ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #666; font-style: italic;">
                                        Aucun produit calculé disponible. Données brutes disponibles dans la section debug ci-dessous.
                                    </td>
                                </tr>
                                <?php
                            }
                            
                            // Fallback pour l'ancien format ou format non reconnu
                            $has_categories = !empty($selected_products['signature']) || !empty($selected_products['mini_boss']) || 
                                            !empty($selected_products['accompaniments']) || !empty($selected_products['buffets']) || 
                                            !empty($selected_products['beverages']) || !empty($selected_products['options']) || 
                                            !empty($selected_products['games']) || !empty($selected_products['other_products']);
                            
                            if (!$has_categories) {
                                // Ancien format direct ou format non catégorisé
                                foreach ($selected_products as $product_key => $product) {
                                    if (is_array($product) && isset($product['name'])) {
                                        ?>
                                        <tr>
                                            <td><?php echo esc_html($product['name'] ?? ''); ?></td>
                                            <td><?php echo esc_html($product['quantity'] ?? 0); ?></td>
                                            <td><?php echo number_format($product['price'] ?? 0, 2, ',', ' '); ?> €</td>
                                            <td><?php echo number_format(($product['price'] ?? 0) * ($product['quantity'] ?? 0), 2, ',', ' '); ?> €</td>
                                        </tr>
                                        <?php
                                    } elseif (is_numeric($product_key) && is_numeric($product)) {
                                        // Format très simple : ID => quantité
                                        global $wpdb;
                                        $db_product = $wpdb->get_row($wpdb->prepare(
                                            "SELECT name, price FROM {$wpdb->prefix}restaurant_products WHERE id = %d",
                                            $product_key
                                        ));
                                        if ($db_product && $product > 0) {
                                            $total = floatval($db_product->price) * intval($product);
                                            ?>
                                            <tr>
                                                <td><?php echo esc_html($db_product->name); ?></td>
                                                <td><?php echo intval($product); ?></td>
                                                <td><?php echo number_format($db_product->price, 2, ',', ' '); ?> €</td>
                                                <td><?php echo number_format($total, 2, ',', ' '); ?> €</td>
                                            </tr>
                                            <?php
                                        }
                                    }
                                }
                            }
                            
                            // Si aucun produit n'a été affiché, afficher un message
                            if (empty($selected_products)) {
                                ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; font-style: italic; color: #666;">
                                        <?php _e('Aucun produit sélectionné', 'restaurant-booking'); ?>
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Colonne latérale -->
        <div class="quote-sidebar" style="flex: 1;">
            <!-- Statut et actions -->
            <div class="postbox">
                <h2 class="hndle"><span><?php _e('Statut et actions', 'restaurant-booking'); ?></span></h2>
                <div class="inside">
                    <p><strong><?php _e('Statut actuel:', 'restaurant-booking'); ?></strong></p>
                    <p>
                        <?php
                        $status_class = 'status-' . $quote['status'];
                        switch($quote['status']) {
                            case 'draft': $status_label = __('Brouillon', 'restaurant-booking'); break;
                            case 'sent': $status_label = __('Envoyé', 'restaurant-booking'); break;
                            case 'confirmed': $status_label = __('Confirmé', 'restaurant-booking'); break;
                            case 'cancelled': $status_label = __('Annulé', 'restaurant-booking'); break;
                            default: $status_label = ucfirst($quote['status']);
                        }
                        ?>
                        <span class="quote-status <?php echo $status_class; ?>"><?php echo $status_label; ?></span>
                    </p>

                    <div class="quote-actions" style="margin-top: 15px;">
                        <a href="<?php echo admin_url('admin.php?page=restaurant-booking-quotes&action=edit&quote_id=' . $quote['id']); ?>" class="button button-primary">
                            <?php _e('Modifier', 'restaurant-booking'); ?>
                        </a>
                        <br><br>
                        <a href="<?php echo wp_nonce_url(admin_url('admin-ajax.php?action=restaurant_booking_view_quote_pdf&quote_id=' . $quote['id']), 'view_quote_pdf_' . $quote['id']); ?>" 
                           class="button" target="_blank">
                            <?php _e('Voir le devis', 'restaurant-booking'); ?>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Récapitulatif des prix -->
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
                </div>
            </div>

            <!-- Informations de création -->
            <div class="postbox">
                <h2 class="hndle"><span><?php _e('Informations', 'restaurant-booking'); ?></span></h2>
                <div class="inside">
                    <p><strong><?php _e('Créé le:', 'restaurant-booking'); ?></strong><br>
                    <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($quote['created_at'])); ?></p>
                    
                    <?php if ($quote['updated_at']): ?>
                    <p><strong><?php _e('Modifié le:', 'restaurant-booking'); ?></strong><br>
                    <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($quote['updated_at'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
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
.quote-actions a { display: inline-block; margin-right: 10px; }

/* Marges internes pour toutes les postbox de la page */
.postbox .inside {
    padding: 15px !important;
}

/* Marges pour les titres des postbox */
.postbox .hndle {
    padding: 12px 15px !important;
}
</style>
