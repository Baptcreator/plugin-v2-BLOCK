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
        if (isset($_POST['action']) && $_POST['action'] === 'bulk_delete' && isset($_POST['quotes'])) {
            $this->bulk_delete_quotes($_POST['quotes']);
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

        // Simuler des données de devis pour l'affichage
        $quotes = $this->get_sample_quotes();
        $total = count($quotes);
        $total_pages = ceil($total / $per_page);

        // Affichage
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Gestion des devis', 'restaurant-booking'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-quotes&action=add'); ?>" class="page-title-action">
                <?php _e('Ajouter un devis', 'restaurant-booking'); ?>
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
                                _e('Devis sélectionnés supprimés avec succès.', 'restaurant-booking');
                                break;
                            case 'created':
                                _e('Devis créé avec succès.', 'restaurant-booking');
                                break;
                            case 'updated':
                                _e('Devis mis à jour avec succès.', 'restaurant-booking');
                                break;
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
                            <option value="draft" <?php selected($status, 'draft'); ?>><?php _e('Brouillon', 'restaurant-booking'); ?></option>
                            <option value="sent" <?php selected($status, 'sent'); ?>><?php _e('Envoyé', 'restaurant-booking'); ?></option>
                            <option value="accepted" <?php selected($status, 'accepted'); ?>><?php _e('Accepté', 'restaurant-booking'); ?></option>
                            <option value="rejected" <?php selected($status, 'rejected'); ?>><?php _e('Refusé', 'restaurant-booking'); ?></option>
                            <option value="expired" <?php selected($status, 'expired'); ?>><?php _e('Expiré', 'restaurant-booking'); ?></option>
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
                <input type="hidden" name="action" value="bulk_delete">
                
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
                                        <strong><?php echo esc_html($quote['client_name']); ?></strong><br>
                                        <small><?php echo esc_html($quote['client_email']); ?></small>
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
                                        <strong><?php echo number_format($quote['total_amount'], 2, ',', ' '); ?> €</strong>
                                    </td>
                                    <td>
                                        <?php
                                        $status_class = 'status-' . $quote['status'];
                                        switch($quote['status']) {
                                            case 'draft': $status_label = __('Brouillon', 'restaurant-booking'); break;
                                            case 'sent': $status_label = __('Envoyé', 'restaurant-booking'); break;
                                            case 'accepted': $status_label = __('Accepté', 'restaurant-booking'); break;
                                            case 'rejected': $status_label = __('Refusé', 'restaurant-booking'); break;
                                            case 'expired': $status_label = __('Expiré', 'restaurant-booking'); break;
                                            default: $status_label = ucfirst($quote['status']);
                                        }
                                        ?>
                                        <span class="quote-status <?php echo $status_class; ?>"><?php echo $status_label; ?></span>
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
        .status-accepted { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .status-expired { background: #fff3cd; color: #856404; }
        </style>

        <script>
        jQuery(document).ready(function($) {
            $('#cb-select-all-1').on('change', function() {
                $('input[name="quotes[]"]').prop('checked', this.checked);
            });
        });
        </script>
        <?php
    }

    /**
     * Données d'exemple pour les devis
     */
    private function get_sample_quotes()
    {
        return array(
            array(
                'id' => 1,
                'client_name' => 'Marie Dupont',
                'client_email' => 'marie.dupont@email.com',
                'service_type' => 'restaurant',
                'event_date' => '2024-02-15',
                'total_amount' => 450.00,
                'status' => 'sent',
                'created_at' => '2024-01-15 10:30:00'
            ),
            array(
                'id' => 2,
                'client_name' => 'Pierre Martin',
                'client_email' => 'pierre.martin@email.com',
                'service_type' => 'remorque',
                'event_date' => '2024-03-20',
                'total_amount' => 1200.00,
                'status' => 'accepted',
                'created_at' => '2024-01-10 14:20:00'
            ),
            array(
                'id' => 3,
                'client_name' => 'Sophie Bernard',
                'client_email' => 'sophie.bernard@email.com',
                'service_type' => 'remorque',
                'event_date' => '2024-04-05',
                'total_amount' => 850.00,
                'status' => 'draft',
                'created_at' => '2024-01-20 09:15:00'
            )
        );
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

        wp_redirect(admin_url('admin.php?page=restaurant-booking-quotes&message=deleted'));
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

        wp_redirect(admin_url('admin.php?page=restaurant-booking-quotes&message=bulk_deleted'));
        exit;
    }
}
