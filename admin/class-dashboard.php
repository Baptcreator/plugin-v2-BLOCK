<?php
/**
 * Classe du tableau de bord d'administration
 *
 * @package RestaurantBooking
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Dashboard
{
    /**
     * Afficher le tableau de bord
     */
    public function display()
    {
        // Obtenir les statistiques
        $stats = $this->get_dashboard_stats();
        $recent_quotes = $this->get_recent_quotes();
        $upcoming_events = $this->get_upcoming_events();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Tableau de bord - Block & Co', 'restaurant-booking'); ?></h1>
            
            <!-- Statistiques rapides -->
            <div class="restaurant-booking-dashboard-stats">
                <div class="restaurant-booking-stat-box">
                    <div class="stat-number"><?php echo $stats['total_quotes_month']; ?></div>
                    <div class="stat-label"><?php _e('Devis ce mois', 'restaurant-booking'); ?></div>
                </div>
                
                <div class="restaurant-booking-stat-box">
                    <div class="stat-number"><?php echo number_format($stats['revenue_month'], 2); ?>€</div>
                    <div class="stat-label"><?php _e('CA prévisionnel', 'restaurant-booking'); ?></div>
                </div>
                
                <div class="restaurant-booking-stat-box">
                    <div class="stat-number"><?php echo $stats['conversion_rate']; ?>%</div>
                    <div class="stat-label"><?php _e('Taux conversion', 'restaurant-booking'); ?></div>
                </div>
                
                <div class="restaurant-booking-stat-box">
                    <div class="stat-number"><?php echo $stats['upcoming_events']; ?></div>
                    <div class="stat-label"><?php _e('Événements à venir', 'restaurant-booking'); ?></div>
                </div>
            </div>

            <div class="restaurant-booking-dashboard-content">
                <!-- Colonne principale -->
                <div class="restaurant-booking-main-column">
                    
                    <!-- Graphique des devis -->
                    <div class="restaurant-booking-widget">
                        <h2><?php _e('Évolution des devis', 'restaurant-booking'); ?></h2>
                        <div id="quotes-chart" style="height: 300px;">
                            <?php $this->render_quotes_chart($stats['chart_data']); ?>
                        </div>
                    </div>

                    <!-- Devis récents -->
                    <div class="restaurant-booking-widget">
                        <h2><?php _e('Devis récents', 'restaurant-booking'); ?></h2>
                        <div class="restaurant-booking-recent-quotes">
                            <?php if (empty($recent_quotes)): ?>
                                <p><?php _e('Aucun devis récent', 'restaurant-booking'); ?></p>
                            <?php else: ?>
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th><?php _e('Numéro', 'restaurant-booking'); ?></th>
                                            <th><?php _e('Client', 'restaurant-booking'); ?></th>
                                            <th><?php _e('Service', 'restaurant-booking'); ?></th>
                                            <th><?php _e('Date événement', 'restaurant-booking'); ?></th>
                                            <th><?php _e('Montant', 'restaurant-booking'); ?></th>
                                            <th><?php _e('Statut', 'restaurant-booking'); ?></th>
                                            <th><?php _e('Actions', 'restaurant-booking'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_quotes as $quote): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo esc_html($quote['quote_number']); ?></strong>
                                                </td>
                                                <td>
                                                    <?php echo esc_html($quote['customer_data']['name'] ?? __('Non renseigné', 'restaurant-booking')); ?>
                                                    <?php if (!empty($quote['customer_data']['email'])): ?>
                                                        <br><small><?php echo esc_html($quote['customer_data']['email']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="service-badge service-<?php echo $quote['service_type']; ?>">
                                                        <?php echo $quote['service_type'] === 'restaurant' ? __('Restaurant', 'restaurant-booking') : __('Remorque', 'restaurant-booking'); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php echo date_i18n('d/m/Y', strtotime($quote['event_date'])); ?>
                                                    <br><small><?php echo sprintf(__('%d convives', 'restaurant-booking'), $quote['guest_count']); ?></small>
                                                </td>
                                                <td>
                                                    <strong><?php echo number_format($quote['total_price'], 2); ?>€</strong>
                                                </td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $quote['status']; ?>">
                                                        <?php echo $this->get_status_label($quote['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="<?php echo admin_url('admin.php?page=restaurant-booking-quotes&action=view&quote_id=' . $quote['id']); ?>" 
                                                       class="button button-small">
                                                        <?php _e('Voir', 'restaurant-booking'); ?>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                
                                <p class="textright">
                                    <a href="<?php echo admin_url('admin.php?page=restaurant-booking-quotes'); ?>" class="button">
                                        <?php _e('Voir tous les devis', 'restaurant-booking'); ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Colonne latérale -->
                <div class="restaurant-booking-sidebar-column">
                    
                    <!-- Actions rapides -->
                    <div class="restaurant-booking-widget">
                        <h2><?php _e('Actions rapides', 'restaurant-booking'); ?></h2>
                        <div class="restaurant-booking-quick-actions">
                            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-products&action=add'); ?>" 
                               class="button button-primary button-large">
                                <?php _e('Ajouter un produit', 'restaurant-booking'); ?>
                            </a>
                            
                            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-categories&action=add'); ?>" 
                               class="button button-secondary button-large">
                                <?php _e('Ajouter une catégorie', 'restaurant-booking'); ?>
                            </a>
                            
                            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-calendar'); ?>" 
                               class="button button-secondary button-large">
                                <?php _e('Gérer le calendrier', 'restaurant-booking'); ?>
                            </a>
                            
                            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-settings'); ?>" 
                               class="button button-secondary button-large">
                                <?php _e('Paramètres', 'restaurant-booking'); ?>
                            </a>
                        </div>
                    </div>

                    <!-- Événements à venir -->
                    <div class="restaurant-booking-widget">
                        <h2><?php _e('Événements à venir', 'restaurant-booking'); ?></h2>
                        <div class="restaurant-booking-upcoming-events">
                            <?php if (empty($upcoming_events)): ?>
                                <p><?php _e('Aucun événement prévu', 'restaurant-booking'); ?></p>
                            <?php else: ?>
                                <ul class="upcoming-events-list">
                                    <?php foreach ($upcoming_events as $event): ?>
                                        <li class="upcoming-event">
                                            <div class="event-date">
                                                <?php echo date_i18n('d/m', strtotime($event['event_date'])); ?>
                                            </div>
                                            <div class="event-details">
                                                <strong><?php echo esc_html($event['customer_data']['name'] ?? __('Client', 'restaurant-booking')); ?></strong>
                                                <br>
                                                <span class="service-badge service-<?php echo $event['service_type']; ?>">
                                                    <?php echo $event['service_type'] === 'restaurant' ? __('Restaurant', 'restaurant-booking') : __('Remorque', 'restaurant-booking'); ?>
                                                </span>
                                                - <?php echo sprintf(__('%d pers.', 'restaurant-booking'), $event['guest_count']); ?>
                                                <br>
                                                <small><?php echo number_format($event['total_price'], 0); ?>€</small>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                
                                <p class="textright">
                                    <a href="<?php echo admin_url('admin.php?page=restaurant-booking-calendar'); ?>">
                                        <?php _e('Voir le calendrier', 'restaurant-booking'); ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- État du système -->
                    <div class="restaurant-booking-widget">
                        <h2><?php _e('État du système', 'restaurant-booking'); ?></h2>
                        <div class="restaurant-booking-system-status">
                            <?php $this->render_system_status(); ?>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <style>
        .restaurant-booking-dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .restaurant-booking-stat-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #0073aa;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        .restaurant-booking-dashboard-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        
        .restaurant-booking-widget {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .restaurant-booking-widget h2 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .restaurant-booking-quick-actions .button {
            display: block;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .service-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .service-restaurant {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .service-remorque {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-draft {
            background: #fff3e0;
            color: #f57c00;
        }
        
        .status-sent {
            background: #e8f5e8;
            color: #2e7d32;
        }
        
        .status-confirmed {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .status-cancelled {
            background: #ffebee;
            color: #c62828;
        }
        
        .upcoming-events-list {
            list-style: none;
            padding: 0;
        }
        
        .upcoming-event {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .event-date {
            background: #0073aa;
            color: white;
            padding: 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 12px;
            text-align: center;
            min-width: 40px;
            margin-right: 15px;
        }
        
        .event-details {
            flex: 1;
        }
        
        .system-status-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px 0;
        }
        
        .status-ok {
            color: #2e7d32;
        }
        
        .status-warning {
            color: #f57c00;
        }
        
        .status-error {
            color: #c62828;
        }
        </style>
        <?php
    }

    /**
     * Obtenir les statistiques du tableau de bord
     */
    private function get_dashboard_stats()
    {
        global $wpdb;

        $stats = array();

        // Devis ce mois
        $stats['total_quotes_month'] = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}restaurant_quotes 
            WHERE MONTH(created_at) = MONTH(NOW()) 
            AND YEAR(created_at) = YEAR(NOW())
        ");

        // Chiffre d'affaires prévisionnel ce mois
        $stats['revenue_month'] = $wpdb->get_var("
            SELECT COALESCE(SUM(total_price), 0) 
            FROM {$wpdb->prefix}restaurant_quotes 
            WHERE MONTH(created_at) = MONTH(NOW()) 
            AND YEAR(created_at) = YEAR(NOW())
            AND status IN ('sent', 'confirmed')
        ");

        // Taux de conversion
        $total_sent = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}restaurant_quotes 
            WHERE status = 'sent'
            AND created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
        ");
        
        $total_confirmed = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}restaurant_quotes 
            WHERE status = 'confirmed'
            AND created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
        ");
        
        $stats['conversion_rate'] = $total_sent > 0 ? round(($total_confirmed / $total_sent) * 100) : 0;

        // Événements à venir
        $stats['upcoming_events'] = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}restaurant_quotes 
            WHERE event_date >= CURDATE()
            AND status IN ('sent', 'confirmed')
        ");

        // Données pour le graphique (6 derniers mois)
        $chart_data = $wpdb->get_results("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as count,
                SUM(total_price) as revenue
            FROM {$wpdb->prefix}restaurant_quotes 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ", ARRAY_A);

        $stats['chart_data'] = $chart_data;

        return $stats;
    }

    /**
     * Obtenir les devis récents
     */
    private function get_recent_quotes($limit = 10)
    {
        $result = RestaurantBooking_Quote::get_list(array(
            'limit' => $limit,
            'orderby' => 'created_at',
            'order' => 'DESC'
        ));

        return $result['quotes'];
    }

    /**
     * Obtenir les événements à venir
     */
    private function get_upcoming_events($limit = 5)
    {
        global $wpdb;

        $events = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}restaurant_quotes 
            WHERE event_date >= CURDATE()
            AND status IN ('sent', 'confirmed')
            ORDER BY event_date ASC
            LIMIT %d
        ", $limit), ARRAY_A);

        // Décoder les données JSON
        foreach ($events as &$event) {
            $event['customer_data'] = json_decode($event['customer_data'], true) ?: array();
        }

        return $events;
    }

    /**
     * Rendre le graphique des devis
     */
    private function render_quotes_chart($chart_data)
    {
        if (empty($chart_data)) {
            echo '<p>' . __('Pas assez de données pour afficher le graphique', 'restaurant-booking') . '</p>';
            return;
        }

        echo '<div style="display: flex; align-items: end; height: 250px; gap: 10px; padding: 20px 0;">';
        
        $max_count = max(array_column($chart_data, 'count'));
        
        foreach ($chart_data as $data) {
            $height = $max_count > 0 ? ($data['count'] / $max_count) * 200 : 0;
            $month_name = date_i18n('M Y', strtotime($data['month'] . '-01'));
            
            echo '<div style="display: flex; flex-direction: column; align-items: center; flex: 1;">';
            echo '<div style="background: #0073aa; width: 100%; height: ' . $height . 'px; margin-bottom: 5px; border-radius: 4px 4px 0 0;" title="' . $data['count'] . ' devis"></div>';
            echo '<small>' . $month_name . '</small>';
            echo '<strong>' . $data['count'] . '</strong>';
            echo '</div>';
        }
        
        echo '</div>';
    }

    /**
     * Obtenir le label d'un statut
     */
    private function get_status_label($status)
    {
        $labels = array(
            'draft' => __('Brouillon', 'restaurant-booking'),
            'sent' => __('Envoyé', 'restaurant-booking'),
            'confirmed' => __('Confirmé', 'restaurant-booking'),
            'cancelled' => __('Annulé', 'restaurant-booking')
        );

        return isset($labels[$status]) ? $labels[$status] : $status;
    }

    /**
     * Rendre l'état du système
     */
    private function render_system_status()
    {
        // Vérifier la base de données
        $db_health = RestaurantBooking_Database::check_database_health();
        
        // Vérifier les logs
        $logs_size = RestaurantBooking_Logger::get_logs_size();
        
        // Vérifier l'espace disque (approximation)
        $upload_dir = wp_upload_dir();
        $disk_free = disk_free_space($upload_dir['basedir']);
        
        ?>
        <div class="system-status-item">
            <span><?php _e('Base de données', 'restaurant-booking'); ?></span>
            <span class="<?php echo $db_health['status'] === 'ok' ? 'status-ok' : 'status-error'; ?>">
                <?php echo $db_health['status'] === 'ok' ? '✓ OK' : '✗ Erreur'; ?>
            </span>
        </div>
        
        <div class="system-status-item">
            <span><?php _e('Logs système', 'restaurant-booking'); ?></span>
            <span class="<?php echo $logs_size['size_mb'] < 10 ? 'status-ok' : 'status-warning'; ?>">
                <?php echo $logs_size['size_mb']; ?> MB
            </span>
        </div>
        
        <div class="system-status-item">
            <span><?php _e('Espace disque', 'restaurant-booking'); ?></span>
            <span class="<?php echo $disk_free > 100000000 ? 'status-ok' : 'status-warning'; ?>">
                <?php echo size_format($disk_free); ?>
            </span>
        </div>
        
        <div class="system-status-item">
            <span><?php _e('Version PHP', 'restaurant-booking'); ?></span>
            <span class="<?php echo version_compare(PHP_VERSION, '8.0', '>=') ? 'status-ok' : 'status-warning'; ?>">
                <?php echo PHP_VERSION; ?>
            </span>
        </div>
        
        <?php if (RESTAURANT_BOOKING_DEBUG): ?>
        <p style="margin-top: 15px;">
            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-diagnostics'); ?>" class="button button-small">
                <?php _e('Diagnostics complets', 'restaurant-booking'); ?>
            </a>
        </p>
        <?php endif; ?>
        <?php
    }
}
