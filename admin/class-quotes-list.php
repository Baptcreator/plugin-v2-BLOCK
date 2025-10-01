<?php
/**
 * Classe de gestion de la liste des devis
 *
 * @package RestaurantBooking
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Quotes_List
{
    /**
     * Afficher la liste des devis
     */
    public function display()
    {
        // Traitement des actions
        if ((isset($_POST['action']) && $_POST['action'] === 'bulk_delete') || 
            (isset($_POST['action2']) && $_POST['action2'] === 'bulk_delete')) {
            if (isset($_POST['quotes']) && !empty($_POST['quotes'])) {
                $this->bulk_delete_quotes($_POST['quotes']);
            }
        }

        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['quote_id'])) {
            $this->delete_quote((int)$_GET['quote_id']);
        }

        // Paramètres de pagination
        $page = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
        $per_page = 20;

        // Paramètres de recherche et filtrage
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $service_type = isset($_GET['service_type']) ? sanitize_text_field($_GET['service_type']) : '';

        // Récupérer les devis réels de la base de données
        $quotes_args = array(
            'search' => $search,
            'status' => $status,
            'service_type' => $service_type,
            'limit' => $per_page,
            'offset' => ($page - 1) * $per_page,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );
        
        $quotes_result = RestaurantBooking_Quote::get_list($quotes_args);
        $quotes = $quotes_result['quotes'];
        $total = $quotes_result['total'];
        $total_pages = $quotes_result['pages'];

        // Affichage
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Gestion des devis', 'restaurant-booking'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-quotes&action=add'); ?>" class="page-title-action">
                <?php _e('Ajouter un devis', 'restaurant-booking'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-quotes&create_test_quotes=1'); ?>" class="page-title-action" 
               onclick="return confirm('<?php _e('Créer des devis de test ?', 'restaurant-booking'); ?>')">
                <?php _e('Créer devis test', 'restaurant-booking'); ?>
            </a>
            <hr class="wp-header-end">

            <?php if (isset($_GET['message'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p>
                        <?php
                        switch ($_GET['message']) {
                            case 'deleted':
                                _e('Devis supprimé avec succès.', 'restaurant-booking');
                                break;
                            case 'bulk_deleted':
                                _e('Devis supprimé avec succès.', 'restaurant-booking');
                                break;
                            case 'bulk_deleted_multiple':
                                $count = isset($_GET['count']) ? (int) $_GET['count'] : 0;
                                printf(_n('%d devis supprimé avec succès.', '%d devis supprimés avec succès.', $count, 'restaurant-booking'), $count);
                                break;
                            case 'no_quotes_selected':
                                _e('Aucun devis sélectionné.', 'restaurant-booking');
                                break;
                            case 'bulk_delete_failed':
                                _e('Aucun devis n\'a pu être supprimé.', 'restaurant-booking');
                                break;
                            case 'test_quotes_created':
                                _e('Devis de test créés avec succès.', 'restaurant-booking');
                                break;
                            case 'test_quotes_deleted':
                                $count = isset($_GET['count']) ? (int) $_GET['count'] : 0;
                                printf(_n('%d devis de test supprimé avec succès.', '%d devis de test supprimés avec succès.', $count, 'restaurant-booking'), $count);
                                break;
                            case 'no_test_quotes_found':
                                _e('Aucun devis de test trouvé.', 'restaurant-booking');
                                break;
                            case 'test_quotes_delete_failed':
                                _e('Aucun devis de test n\'a pu être supprimé.', 'restaurant-booking');
                                break;
                            case 'created':
                                _e('Devis créé avec succès.', 'restaurant-booking');
                                break;
                            case 'updated':
                                _e('Devis mis à jour avec succès.', 'restaurant-booking');
                                break;
                        }
                        
                        // Afficher les erreurs s'il y en a
                        if (isset($_GET['errors']) && !empty($_GET['errors'])) {
                            echo '<br><strong>' . __('Erreurs:', 'restaurant-booking') . '</strong><br>';
                            echo esc_html($_GET['errors']);
                        }
                        ?>
                    </p>
                </div>
            <?php endif; ?>

            <!-- Formulaire de recherche et filtres -->
            <div class="tablenav top">
                <form method="get" id="quotes-filter">
                    <input type="hidden" name="page" value="restaurant-booking-quotes">
                    
                    <div class="alignleft actions">
                        <select name="status">
                            <option value=""><?php _e('Tous les statuts', 'restaurant-booking'); ?></option>
                            <option value="sent" <?php selected($status, 'sent'); ?>><?php _e('Envoyé', 'restaurant-booking'); ?></option>
                            <option value="confirmed" <?php selected($status, 'confirmed'); ?>><?php _e('Confirmé', 'restaurant-booking'); ?></option>
                        </select>

                        <select name="service_type">
                            <option value=""><?php _e('Tous les services', 'restaurant-booking'); ?></option>
                            <option value="restaurant" <?php selected($service_type, 'restaurant'); ?>><?php _e('Restaurant', 'restaurant-booking'); ?></option>
                            <option value="remorque" <?php selected($service_type, 'remorque'); ?>><?php _e('Remorque', 'restaurant-booking'); ?></option>
                        </select>

                        <input type="submit" class="button" value="<?php _e('Filtrer', 'restaurant-booking'); ?>">
                    </div>

                    <div class="alignright">
                        <input type="search" name="search" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('Rechercher...', 'restaurant-booking'); ?>">
                        <input type="submit" class="button" value="<?php _e('Rechercher', 'restaurant-booking'); ?>">
                    </div>
                </form>
            </div>

            <!-- Tableau des devis -->
            <form method="post" id="quotes-list-form">
                <?php wp_nonce_field('bulk_delete_quotes', '_wpnonce'); ?>
                
                <!-- Actions groupées -->
                <div class="tablenav top">
                    <div class="alignleft actions bulkactions">
                        <label for="bulk-action-selector-top" class="screen-reader-text"><?php _e('Sélectionner une action groupée', 'restaurant-booking'); ?></label>
                        <select name="action" id="bulk-action-selector-top">
                            <option value="-1"><?php _e('Actions groupées', 'restaurant-booking'); ?></option>
                            <option value="bulk_delete"><?php _e('Supprimer', 'restaurant-booking'); ?></option>
                        </select>
                        <input type="submit" id="doaction" class="button action" value="<?php _e('Appliquer', 'restaurant-booking'); ?>" onclick="return confirmBulkDelete()">
                    </div>
                </div>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <input type="checkbox" id="cb-select-all-1">
                            </td>
                            <th class="manage-column"><?php _e('ID', 'restaurant-booking'); ?></th>
                            <th class="manage-column"><?php _e('Client', 'restaurant-booking'); ?></th>
                            <th class="manage-column"><?php _e('Service', 'restaurant-booking'); ?></th>
                            <th class="manage-column"><?php _e('Date événement', 'restaurant-booking'); ?></th>
                            <th class="manage-column"><?php _e('Montant', 'restaurant-booking'); ?></th>
                            <th class="manage-column"><?php _e('Statut', 'restaurant-booking'); ?></th>
                            <th class="manage-column"><?php _e('Date création', 'restaurant-booking'); ?></th>
                            <th class="manage-column"><?php _e('Actions', 'restaurant-booking'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($quotes)): ?>
                            <tr>
                                <td colspan="9">
                                    <p style="text-align: center; padding: 20px;">
                                        <?php _e('Aucun devis trouvé.', 'restaurant-booking'); ?>
                                        <br><br>
                                        <a href="<?php echo admin_url('admin.php?page=restaurant-booking-quotes&action=add'); ?>" class="button button-primary">
                                            <?php _e('Créer votre premier devis', 'restaurant-booking'); ?>
                                        </a>
                                    </p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($quotes as $quote): ?>
                                <tr>
                                    <th class="check-column">
                                        <input type="checkbox" name="quotes[]" value="<?php echo $quote['id']; ?>">
                                    </th>
                                    <td><strong>#<?php echo $quote['id']; ?></strong></td>
                                    <td>
                                        <?php 
                                        // Gérer le cas où customer_data peut être déjà décodé ou être une chaîne JSON
                                        $customer_data_raw = $quote['customer_data'] ?? '{}';
                                        if (is_array($customer_data_raw)) {
                                            $customer_data = $customer_data_raw;
                                        } else {
                                            $customer_data = json_decode($customer_data_raw, true) ?: [];
                                        }
                                        $client_name = $customer_data['name'] ?? __('Client sans nom', 'restaurant-booking');
                                        $client_email = $customer_data['email'] ?? '';
                                        ?>
                                        <strong><?php echo esc_html($client_name); ?></strong><br>
                                        <?php if ($client_email): ?>
                                            <small><?php echo esc_html($client_email); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        switch($quote['service_type']) {
                                            case 'restaurant': _e('Restaurant', 'restaurant-booking'); break;
                                            case 'remorque': _e('Remorque', 'restaurant-booking'); break;
                                            default: echo esc_html($quote['service_type']);
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php echo $quote['event_date'] ? date_i18n(get_option('date_format'), strtotime($quote['event_date'])) : '-'; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo number_format($quote['total_price'], 2, ',', ' '); ?> €</strong>
                                    </td>
                                    <td>
                                        <select class="quote-status-dropdown" data-quote-id="<?php echo $quote['id']; ?>" 
                                                onchange="updateQuoteStatus(this)">
                                            <option value="sent" <?php selected($quote['status'], 'sent'); ?>>
                                                <?php _e('Envoyé', 'restaurant-booking'); ?>
                                            </option>
                                            <option value="confirmed" <?php selected($quote['status'], 'confirmed'); ?>>
                                                <?php _e('Confirmé', 'restaurant-booking'); ?>
                                            </option>
                                        </select>
                                    </td>
                                    <td>
                                        <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($quote['created_at'])); ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=restaurant-booking-quotes&action=view&quote_id=' . $quote['id']); ?>" class="button button-small">
                                            <?php _e('Voir', 'restaurant-booking'); ?>
                                        </a>
                                        <a href="<?php echo admin_url('admin.php?page=restaurant-booking-quotes&action=edit&quote_id=' . $quote['id']); ?>" class="button button-small">
                                            <?php _e('Modifier', 'restaurant-booking'); ?>
                                        </a>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=restaurant-booking-quotes&action=delete&quote_id=' . $quote['id']), 'delete_quote_' . $quote['id']); ?>" 
                                           class="button button-small button-link-delete" 
                                           onclick="return confirm('<?php _e('Êtes-vous sûr de vouloir supprimer ce devis ?', 'restaurant-booking'); ?>')">
                                            <?php _e('Supprimer', 'restaurant-booking'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <!-- Actions groupées en bas -->
                <div class="tablenav bottom">
                    <div class="alignleft actions bulkactions">
                        <label for="bulk-action-selector-bottom" class="screen-reader-text"><?php _e('Sélectionner une action groupée', 'restaurant-booking'); ?></label>
                        <select name="action2" id="bulk-action-selector-bottom">
                            <option value="-1"><?php _e('Actions groupées', 'restaurant-booking'); ?></option>
                            <option value="bulk_delete"><?php _e('Supprimer', 'restaurant-booking'); ?></option>
                        </select>
                        <input type="submit" id="doaction2" class="button action" value="<?php _e('Appliquer', 'restaurant-booking'); ?>" onclick="return confirmBulkDelete()">
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
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Gestion de la sélection multiple
            $('#cb-select-all-1').on('change', function() {
                $('input[name="quotes[]"]').prop('checked', this.checked);
            });
            
            // Gestion des actions groupées (haut et bas)
            $('#doaction, #doaction2').on('click', function(e) {
                var $button = $(this);
                var isTop = $button.attr('id') === 'doaction';
                var action = isTop ? $('#bulk-action-selector-top').val() : $('#bulk-action-selector-bottom').val();
                
                if (action === '-1') {
                    e.preventDefault();
                    alert('<?php _e('Veuillez sélectionner une action.', 'restaurant-booking'); ?>');
                    return false;
                }
                
                var checkedQuotes = $('input[name="quotes[]"]:checked');
                if (checkedQuotes.length === 0) {
                    e.preventDefault();
                    alert('<?php _e('Veuillez sélectionner au moins un devis.', 'restaurant-booking'); ?>');
                    return false;
                }
                
                if (action === 'bulk_delete') {
                    var count = checkedQuotes.length;
                    var message = count === 1 ? 
                        '<?php _e('Êtes-vous sûr de vouloir supprimer ce devis ?', 'restaurant-booking'); ?>' :
                        '<?php _e('Êtes-vous sûr de vouloir supprimer ces %d devis ?', 'restaurant-booking'); ?>'.replace('%d', count);
                    
                    if (!confirm(message)) {
                        e.preventDefault();
                        return false;
                    }
                }
            });
        });
        
        // Fonction de confirmation pour la suppression groupée
        function confirmBulkDelete() {
            var checkedQuotes = jQuery('input[name="quotes[]"]:checked');
            if (checkedQuotes.length === 0) {
                alert('<?php _e('Veuillez sélectionner au moins un devis.', 'restaurant-booking'); ?>');
                return false;
            }
            
            var count = checkedQuotes.length;
            var message = count === 1 ? 
                '<?php _e('Êtes-vous sûr de vouloir supprimer ce devis ?', 'restaurant-booking'); ?>' :
                '<?php _e('Êtes-vous sûr de vouloir supprimer ces %d devis ?', 'restaurant-booking'); ?>'.replace('%d', count);
            
            return confirm(message);
        }
        
        // Fonction pour mettre à jour le statut d'un devis
        function updateQuoteStatus(selectElement) {
            const quoteId = selectElement.getAttribute('data-quote-id');
            const newStatus = selectElement.value;
            const originalValue = selectElement.getAttribute('data-original-value') || selectElement.value;
            
            // Désactiver le select pendant la requête
            selectElement.disabled = true;
            
            // Requête AJAX
            jQuery.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'restaurant_booking_admin_action',
                    admin_action: 'update_quote_status',
                    quote_id: quoteId,
                    status: newStatus,
                    nonce: '<?php echo wp_create_nonce('restaurant_booking_admin_nonce'); ?>'
                },
                success: function(response) {
                    selectElement.disabled = false;
                    if (response.success) {
                        // Mettre à jour la valeur originale
                        selectElement.setAttribute('data-original-value', newStatus);
                        // Afficher un message de succès
                        if (typeof wp !== 'undefined' && wp.notices) {
                            wp.notices.create({
                                message: '<?php _e('Statut mis à jour avec succès', 'restaurant-booking'); ?>',
                                type: 'success',
                                isDismissible: true
                            });
                        }
                    } else {
                        // Restaurer la valeur précédente en cas d'erreur
                        selectElement.value = originalValue;
                        alert('<?php _e('Erreur lors de la mise à jour du statut', 'restaurant-booking'); ?>: ' + (response.data || '<?php _e('Erreur inconnue', 'restaurant-booking'); ?>'));
                    }
                },
                error: function() {
                    selectElement.disabled = false;
                    selectElement.value = originalValue;
                    alert('<?php _e('Erreur de communication avec le serveur', 'restaurant-booking'); ?>');
                }
            });
        }
        </script>
        <?php
    }


    /**
     * Supprimer un devis
     */
    private function delete_quote($quote_id)
    {
        if (!wp_verify_nonce($_GET['_wpnonce'], 'delete_quote_' . $quote_id)) {
            wp_die(__('Action non autorisée.', 'restaurant-booking'));
        }

        if (!current_user_can('manage_restaurant_quotes')) {
            wp_die(__('Permissions insuffisantes.', 'restaurant-booking'));
        }

        $result = RestaurantBooking_Quote::delete($quote_id);
        
        if (is_wp_error($result)) {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-quotes&message=error&error=' . urlencode($result->get_error_message())));
        } else {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-quotes&message=deleted'));
        }
        exit;
    }

    /**
     * Suppression groupée de devis
     */
    private function bulk_delete_quotes($quote_ids)
    {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'bulk_delete_quotes')) {
            wp_die(__('Action non autorisée.', 'restaurant-booking'));
        }

        if (!current_user_can('manage_restaurant_quotes')) {
            wp_die(__('Permissions insuffisantes.', 'restaurant-booking'));
        }

        if (empty($quote_ids) || !is_array($quote_ids)) {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-quotes&message=no_quotes_selected'));
            exit;
        }

        $deleted_count = 0;
        $errors = array();

        foreach ($quote_ids as $quote_id) {
            $quote_id = (int) $quote_id;
            if ($quote_id > 0) {
                $result = RestaurantBooking_Quote::delete($quote_id);
                if (is_wp_error($result)) {
                    $errors[] = sprintf(__('Erreur lors de la suppression du devis #%d: %s', 'restaurant-booking'), $quote_id, $result->get_error_message());
                } else {
                    $deleted_count++;
                }
            }
        }

        if ($deleted_count > 0) {
            $message = $deleted_count === 1 ? 'bulk_deleted' : 'bulk_deleted_multiple';
            $redirect_url = admin_url('admin.php?page=restaurant-booking-quotes&message=' . $message . '&count=' . $deleted_count);
        } else {
            $redirect_url = admin_url('admin.php?page=restaurant-booking-quotes&message=bulk_delete_failed');
        }

        if (!empty($errors)) {
            $redirect_url .= '&errors=' . urlencode(implode('; ', $errors));
        }

        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Supprimer tous les devis de test
     */
    private function delete_all_test_quotes()
    {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'bulk_delete_quotes')) {
            wp_die(__('Action non autorisée.', 'restaurant-booking'));
        }

        if (!current_user_can('manage_restaurant_quotes')) {
            wp_die(__('Permissions insuffisantes.', 'restaurant-booking'));
        }

        global $wpdb;
        
        // Récupérer tous les devis de test
        $test_quotes = $wpdb->get_results(
            "SELECT id FROM {$wpdb->prefix}restaurant_quotes WHERE quote_number LIKE 'DEV-2024-%' OR quote_number LIKE 'TEST-2024-%'"
        );

        if (empty($test_quotes)) {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-quotes&message=no_test_quotes_found'));
            exit;
        }

        $deleted_count = 0;
        $errors = array();

        foreach ($test_quotes as $quote) {
            $result = RestaurantBooking_Quote::delete($quote->id);
            if (is_wp_error($result)) {
                $errors[] = sprintf(__('Erreur lors de la suppression du devis #%d: %s', 'restaurant-booking'), $quote->id, $result->get_error_message());
            } else {
                $deleted_count++;
            }
        }

        if ($deleted_count > 0) {
            $message = 'test_quotes_deleted';
            $redirect_url = admin_url('admin.php?page=restaurant-booking-quotes&message=' . $message . '&count=' . $deleted_count);
        } else {
            $redirect_url = admin_url('admin.php?page=restaurant-booking-quotes&message=test_quotes_delete_failed');
        }

        if (!empty($errors)) {
            $redirect_url .= '&errors=' . urlencode(implode('; ', $errors));
        }

        wp_redirect($redirect_url);
        exit;
    }
}
