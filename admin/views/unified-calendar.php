<?php
/**
 * Vue unifiée du calendrier avec Google Calendar
 *
 * @package RestaurantBooking
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'calendar';

// Paramètres par défaut
$current_month = $month ?? date('n');
$current_year = $year ?? date('Y');
$service_type = 'both'; // Toujours afficher les deux services

// Calculer les dates
$first_day = mktime(0, 0, 0, $current_month, 1, $current_year);
$last_day = mktime(0, 0, 0, $current_month + 1, 0, $current_year);
$days_in_month = date('t', $first_day);
$first_day_of_week = date('w', $first_day);

// Navigation
$prev_month = $current_month == 1 ? 12 : $current_month - 1;
$prev_year = $current_month == 1 ? $current_year - 1 : $current_year;
$next_month = $current_month == 12 ? 1 : $current_month + 1;
$next_year = $current_month == 12 ? $current_year + 1 : $current_year;

$month_names = array(
    1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
    5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
    9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
);

// Récupérer les données de disponibilité depuis la base de données
global $wpdb;
$availability_data = array();

$start_date = sprintf('%04d-%02d-01', $current_year, $current_month);
$end_date = date('Y-m-t', mktime(0, 0, 0, $current_month, 1, $current_year));

// Vérifier quelles colonnes existent dans la table
$columns = $wpdb->get_col("DESCRIBE {$wpdb->prefix}restaurant_availability", 0);
$has_google_event_id = in_array('google_event_id', $columns);
$has_start_time = in_array('start_time', $columns);
$has_end_time = in_array('end_time', $columns);

// Construire la requête SELECT en fonction des colonnes disponibles
$select_columns = "date, service_type, is_available, blocked_reason, notes";
if ($has_google_event_id) {
    $select_columns .= ", google_event_id";
}
if ($has_start_time) {
    $select_columns .= ", start_time";
}
if ($has_end_time) {
    $select_columns .= ", end_time";
}

$order_by = "date ASC";
if ($has_start_time) {
    $order_by .= ", start_time ASC";
}

$availability_results = $wpdb->get_results($wpdb->prepare("
    SELECT {$select_columns}
    FROM {$wpdb->prefix}restaurant_availability 
    WHERE date BETWEEN %s AND %s
    ORDER BY {$order_by}
", $start_date, $end_date), ARRAY_A);

foreach ($availability_results as $row) {
    $date = $row['date'];
    
    if (!isset($availability_data[$date])) {
        $availability_data[$date] = array(
            'events' => array(),
            'is_fully_blocked' => false,
            'has_google_events' => false
        );
    }
    
    // Ajouter l'événement à la liste
    $event_info = array(
        'is_available' => $row['is_available'],
        'blocked_reason' => $row['blocked_reason'],
        'notes' => $row['notes'],
        'google_event_id' => isset($row['google_event_id']) ? $row['google_event_id'] : '',
        'start_time' => isset($row['start_time']) ? $row['start_time'] : null,
        'end_time' => isset($row['end_time']) ? $row['end_time'] : null,
        'service_type' => $row['service_type']
    );
    
    $availability_data[$date]['events'][] = $event_info;
    
    // Marquer si c'est un événement Google Calendar
    if (isset($row['google_event_id']) && !empty($row['google_event_id'])) {
        $availability_data[$date]['has_google_events'] = true;
    }
    
    // Vérifier si la journée entière est bloquée
    $start_time_empty = !isset($row['start_time']) || empty($row['start_time']);
    $end_time_empty = !isset($row['end_time']) || empty($row['end_time']);
    if ($row['is_available'] == 0 && $start_time_empty && $end_time_empty) {
        $availability_data[$date]['is_fully_blocked'] = true;
    }
}

?>
<div class="wrap">
    <h1><?php _e('📅 Calendrier & Google Calendar', 'restaurant-booking'); ?></h1>

    <!-- Navigation par onglets -->
    <nav class="nav-tab-wrapper wp-clearfix">
        <a href="<?php echo admin_url('admin.php?page=restaurant-booking-calendar&tab=calendar'); ?>" 
           class="nav-tab <?php echo $current_tab === 'calendar' ? 'nav-tab-active' : ''; ?>">
            📅 <?php _e('Calendrier des disponibilités', 'restaurant-booking'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=restaurant-booking-calendar&tab=google'); ?>" 
           class="nav-tab <?php echo $current_tab === 'google' ? 'nav-tab-active' : ''; ?>">
            🔗 <?php _e('Configuration Google Calendar', 'restaurant-booking'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=restaurant-booking-calendar&tab=settings'); ?>" 
           class="nav-tab <?php echo $current_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
            ⚙️ <?php _e('Paramètres', 'restaurant-booking'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=restaurant-booking-calendar&tab=guide'); ?>" 
           class="nav-tab <?php echo $current_tab === 'guide' ? 'nav-tab-active' : ''; ?>">
            📖 <?php _e('Guide d\'utilisation', 'restaurant-booking'); ?>
        </a>
    </nav>

    <div class="tab-content unified-calendar-content">
        <?php if ($current_tab === 'calendar'): ?>

            <!-- Statut Google Calendar -->
            <?php if (isset($google_calendar_connected)): ?>
                <div class="google-sync-status <?php echo $google_calendar_connected ? 'connected' : 'not-connected'; ?>">
                    <div class="sync-indicator">
                        <?php if ($google_calendar_connected): ?>
                            <span class="dashicons dashicons-yes-alt"></span>
                            <strong><?php _e('Google Calendar connecté', 'restaurant-booking'); ?></strong>
                            <span class="sync-details"><?php _e('- Synchronisation automatique active', 'restaurant-booking'); ?></span>
                        <?php else: ?>
                            <span class="dashicons dashicons-warning"></span>
                            <strong><?php _e('Google Calendar non connecté', 'restaurant-booking'); ?></strong>
                            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-calendar&tab=google'); ?>" class="button button-small">
                                <?php _e('Configurer', 'restaurant-booking'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="calendar-header">
                <!-- Navigation mensuelle -->
                <div class="calendar-nav">
                    <a href="<?php echo admin_url('admin.php?page=restaurant-booking-calendar&tab=calendar&month=' . $prev_month . '&year=' . $prev_year); ?>" class="button">
                        ← <?php echo $month_names[$prev_month]; ?>
                    </a>
                    
                    <h2 class="current-month">
                        <?php echo $month_names[$current_month] . ' ' . $current_year; ?>
                    </h2>
                    
                    <a href="<?php echo admin_url('admin.php?page=restaurant-booking-calendar&tab=calendar&month=' . $next_month . '&year=' . $next_year); ?>" class="button">
                        <?php echo $month_names[$next_month]; ?> →
                    </a>
                </div>
            </div>

            <!-- Légende -->
            <div class="calendar-legend">
                <div class="legend-item">
                    <span class="legend-color available"></span>
                    <?php _e('Disponible', 'restaurant-booking'); ?>
                </div>
                <div class="legend-item">
                    <span class="legend-color unavailable"></span>
                    <?php _e('Non disponible', 'restaurant-booking'); ?>
                </div>
                <div class="legend-item">
                    <span class="legend-color booked"></span>
                    <?php _e('Réservé', 'restaurant-booking'); ?>
                </div>
                <div class="legend-item">
                    <span class="legend-color google-sync"></span>
                    <?php _e('Synchronisé Google Calendar', 'restaurant-booking'); ?>
                </div>
                <div class="legend-item">
                    <span class="legend-color past"></span>
                    <?php _e('Passé', 'restaurant-booking'); ?>
                </div>
            </div>

            <!-- Calendrier -->
            <div class="calendar-container">
                <table class="calendar-table">
                    <thead>
                        <tr>
                            <th><?php _e('Dim', 'restaurant-booking'); ?></th>
                            <th><?php _e('Lun', 'restaurant-booking'); ?></th>
                            <th><?php _e('Mar', 'restaurant-booking'); ?></th>
                            <th><?php _e('Mer', 'restaurant-booking'); ?></th>
                            <th><?php _e('Jeu', 'restaurant-booking'); ?></th>
                            <th><?php _e('Ven', 'restaurant-booking'); ?></th>
                            <th><?php _e('Sam', 'restaurant-booking'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $current_date = 1;
                        $today = date('Y-m-d');
                        
                        // Première semaine (peut commencer par des cases vides)
                        echo '<tr>';
                        for ($i = 0; $i < $first_day_of_week; $i++) {
                            echo '<td class="empty-day"></td>';
                        }
                        
                        for ($i = $first_day_of_week; $i < 7 && $current_date <= $days_in_month; $i++) {
                            $date_string = sprintf('%04d-%02d-%02d', $current_year, $current_month, $current_date);
                            $is_past = $date_string < $today;
                            $is_today = $date_string == $today;
                            
                            // Récupérer le statut réel depuis les données synchronisées
                            $status = $is_past ? 'past' : 'available';
                            $tooltip = '';
                            $is_google_sync = false;
                            $event_details = array();
                            
                            if (isset($availability_data[$date_string])) {
                                $day_data = $availability_data[$date_string];
                                
                                // Journée entièrement bloquée
                                if ($day_data['is_fully_blocked']) {
                                    $status = $day_data['has_google_events'] ? 'google-sync' : 'unavailable';
                                    $is_google_sync = $day_data['has_google_events'];
                                }
                                // Événements spécifiques
                                else if (!empty($day_data['events'])) {
                                    $blocked_events = array_filter($day_data['events'], function($event) {
                                        return $event['is_available'] == 0;
                                    });
                                    
                                    if (!empty($blocked_events)) {
                                        $status = 'partial-blocked';
                                        $has_google_events = false;
                                        
                                        foreach ($blocked_events as $event) {
                                            if (!empty($event['google_event_id'])) {
                                                $has_google_events = true;
                                                $is_google_sync = true;
                                            }
                                            
                                            // Créer les détails de l'événement
                                            $event_detail = '';
                                            if (!empty($event['notes'])) {
                                                $event_detail .= $event['notes'];
                                            } else {
                                                $event_detail .= $event['blocked_reason'];
                                            }
                                            
                                            if (!empty($event['start_time']) && !empty($event['end_time'])) {
                                                $start_formatted = date('H:i', strtotime($event['start_time']));
                                                $end_formatted = date('H:i', strtotime($event['end_time']));
                                                $event_detail .= " ({$start_formatted}-{$end_formatted})";
                                            }
                                            
                                            $event_details[] = $event_detail;
                                        }
                                        
                                        if ($has_google_events) {
                                            $status = 'google-sync';
                                        }
                                    }
                                }
                                
                                // Construire le tooltip
                                if (!empty($event_details)) {
                                    $tooltip = implode("\n", $event_details);
                                }
                            }
                            
                            $classes = array('calendar-day', $status);
                            if ($is_today) $classes[] = 'today';
                            
                            echo '<td class="' . implode(' ', $classes) . '" data-date="' . $date_string . '" title="' . esc_attr($tooltip) . '">';
                            echo '<span class="day-number">' . $current_date . '</span>';
                            
                            // Afficher les événements directement dans la cellule avec des libellés clairs
                            if (!empty($event_details)) {
                                echo '<div class="event-details">';
                                
                                // Si c'est une journée entièrement bloquée
                                if (isset($day_data) && $day_data['is_fully_blocked']) {
                                    echo '<div class="event-item blocked-day' . ($is_google_sync ? ' google-event' : '') . '">';
                                    echo '<span class="event-label">🚫 BLOQUÉ</span>';
                                    if (!empty($event_details[0])) {
                                        echo '<span class="event-title">' . esc_html($event_details[0]) . '</span>';
                                    }
                                    echo '</div>';
                                } else {
                                    // Événements partiels avec créneaux
                                    foreach ($event_details as $detail) {
                                        echo '<div class="event-item' . ($is_google_sync ? ' google-event' : '') . '">';
                                        echo '<span class="event-text">' . esc_html($detail) . '</span>';
                                        echo '</div>';
                                    }
                                }
                                echo '</div>';
                            }
                            
                            // Indicateur de statut (plus petit maintenant)
                            if (!$is_past) {
                                echo '<button type="button" class="toggle-availability" onclick="toggleAvailability(\'' . $date_string . '\', \'' . $service_type . '\')">';
                                if ($is_google_sync) {
                                    echo '🔗'; // Icône de synchronisation Google Calendar
                                } else {
                                    echo $status == 'available' ? '✓' : '✗';
                                }
                                echo '</button>';
                            }
                            
                            echo '</td>';
                            $current_date++;
                        }
                        echo '</tr>';
                        
                        // Semaines suivantes
                        while ($current_date <= $days_in_month) {
                            echo '<tr>';
                            for ($i = 0; $i < 7 && $current_date <= $days_in_month; $i++) {
                                $date_string = sprintf('%04d-%02d-%02d', $current_year, $current_month, $current_date);
                                $is_past = $date_string < $today;
                                $is_today = $date_string == $today;
                                
                                // Récupérer le statut réel depuis les données synchronisées
                                $status = $is_past ? 'past' : 'available';
                                $tooltip = '';
                                $is_google_sync = false;
                                $event_details = array();
                                
                                if (isset($availability_data[$date_string])) {
                                    $day_data = $availability_data[$date_string];
                                    
                                    // Journée entièrement bloquée
                                    if ($day_data['is_fully_blocked']) {
                                        $status = $day_data['has_google_events'] ? 'google-sync' : 'unavailable';
                                        $is_google_sync = $day_data['has_google_events'];
                                    }
                                    // Événements spécifiques
                                    else if (!empty($day_data['events'])) {
                                        $blocked_events = array_filter($day_data['events'], function($event) {
                                            return $event['is_available'] == 0;
                                        });
                                        
                                        if (!empty($blocked_events)) {
                                            $status = 'partial-blocked';
                                            $has_google_events = false;
                                            
                                            foreach ($blocked_events as $event) {
                                                if (!empty($event['google_event_id'])) {
                                                    $has_google_events = true;
                                                    $is_google_sync = true;
                                                }
                                                
                                                // Créer les détails de l'événement
                                                $event_detail = '';
                                                if (!empty($event['notes'])) {
                                                    $event_detail .= $event['notes'];
                                                } else {
                                                    $event_detail .= $event['blocked_reason'];
                                                }
                                                
                                                if (!empty($event['start_time']) && !empty($event['end_time'])) {
                                                    $start_formatted = date('H:i', strtotime($event['start_time']));
                                                    $end_formatted = date('H:i', strtotime($event['end_time']));
                                                    $event_detail .= " ({$start_formatted}-{$end_formatted})";
                                                }
                                                
                                                $event_details[] = $event_detail;
                                            }
                                            
                                            if ($has_google_events) {
                                                $status = 'google-sync';
                                            }
                                        }
                                    }
                                    
                                    // Construire le tooltip
                                    if (!empty($event_details)) {
                                        $tooltip = implode("\n", $event_details);
                                    }
                                }
                                
                                $classes = array('calendar-day', $status);
                                if ($is_today) $classes[] = 'today';
                                
                                echo '<td class="' . implode(' ', $classes) . '" data-date="' . $date_string . '" title="' . esc_attr($tooltip) . '">';
                                echo '<span class="day-number">' . $current_date . '</span>';
                                
                                // Afficher les événements directement dans la cellule
                                if (!empty($event_details)) {
                                    echo '<div class="event-details">';
                                    
                                    // Si c'est une journée entièrement bloquée, afficher le titre clairement
                                    if (isset($availability_data[$date_string]) && $availability_data[$date_string]['is_fully_blocked']) {
                                        echo '<div class="event-item blocked-day' . ($is_google_sync ? ' google-event' : '') . '">';
                                        echo '<span class="event-label">🚫 BLOQUÉ</span>';
                                        if (!empty($event_details[0])) {
                                            echo '<span class="event-title">' . esc_html($event_details[0]) . '</span>';
                                        }
                                        echo '</div>';
                                    } else {
                                        // Événements partiels avec créneaux
                                        foreach ($event_details as $detail) {
                                            echo '<div class="event-item' . ($is_google_sync ? ' google-event' : '') . '">';
                                            echo '<span class="event-text">' . esc_html($detail) . '</span>';
                                            echo '</div>';
                                        }
                                    }
                                    echo '</div>';
                                }
                                
                                // Indicateur de statut (plus petit maintenant)
                                if (!$is_past) {
                                    echo '<button type="button" class="toggle-availability" onclick="toggleAvailability(\'' . $date_string . '\', \'' . $service_type . '\')">';
                                    if ($is_google_sync) {
                                        echo '🔗'; // Icône de synchronisation Google Calendar
                                    } else {
                                        echo $status == 'available' ? '✓' : '✗';
                                    }
                                    echo '</button>';
                                }
                                
                                echo '</td>';
                                $current_date++;
                            }
                            
                            // Compléter la semaine avec des cases vides si nécessaire
                            for ($j = $i; $j < 7; $j++) {
                                echo '<td class="empty-day"></td>';
                            }
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Synchronisation Google Calendar -->
            <?php if (isset($google_calendar_connected) && $google_calendar_connected): ?>
            <div class="calendar-actions">
                <div class="actions-group">
                    <h3><?php _e('Synchronisation', 'restaurant-booking'); ?></h3>
                    <button type="button" class="button button-primary" onclick="syncGoogleCalendar()">
                        🔗 <?php _e('Synchroniser Google Calendar', 'restaurant-booking'); ?>
                    </button>
                </div>
            </div>
            <?php endif; ?>

        <?php elseif ($current_tab === 'google'): ?>
            <!-- Configuration Google Calendar -->
            <div class="google-calendar-configuration">
                <?php 
                // Récupérer les paramètres Google Calendar
                $client_id = get_option('restaurant_booking_google_client_id', '');
                $client_secret = get_option('restaurant_booking_google_client_secret', '');
                $calendar_id = get_option('restaurant_booking_google_calendar_id', 'primary');
                $sync_frequency = get_option('restaurant_booking_google_sync_frequency', 'hourly');
                $is_connected = !empty(get_option('restaurant_booking_google_access_token'));
                ?>

                <?php if (isset($_GET['auth']) && $_GET['auth'] === 'success'): ?>
                    <div class="notice notice-success">
                        <p><?php _e('✅ Autorisation Google Calendar réussie !', 'restaurant-booking'); ?></p>
                    </div>
                <?php elseif (isset($_GET['auth']) && $_GET['auth'] === 'error'): ?>
                    <div class="notice notice-error">
                        <p><?php _e('❌ Erreur lors de l\'autorisation Google Calendar.', 'restaurant-booking'); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Statut de connexion -->
                <div class="connection-status">
                    <h2><?php _e('Statut de la connexion', 'restaurant-booking'); ?></h2>
                    <div class="status-card <?php echo $is_connected ? 'connected' : 'disconnected'; ?>">
                        <div class="status-icon">
                            <?php echo $is_connected ? '🟢' : '🔴'; ?>
                        </div>
                        <div class="status-info">
                            <h3><?php echo $is_connected ? __('Connecté', 'restaurant-booking') : __('Non connecté', 'restaurant-booking'); ?></h3>
                            <p>
                                <?php if ($is_connected): ?>
                                    <?php _e('Synchronisation bidirectionnelle active avec Google Calendar', 'restaurant-booking'); ?>
                                <?php else: ?>
                                    <?php _e('Configurez les paramètres ci-dessous pour activer la synchronisation', 'restaurant-booking'); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <?php if ($is_connected): ?>
                            <div class="status-actions">
                                <form method="post" style="display: inline;">
                                    <button type="submit" name="test_connection" class="button">
                                        <?php _e('Tester la connexion', 'restaurant-booking'); ?>
                                    </button>
                                </form>
                                <form method="post" style="display: inline;">
                                    <button type="submit" name="sync_calendar" class="button button-primary">
                                        <?php _e('Synchroniser maintenant', 'restaurant-booking'); ?>
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Configuration OAuth2 -->
                <div class="settings-section">
                    <h2><?php _e('Configuration OAuth2', 'restaurant-booking'); ?></h2>
                    
                    <div class="setup-steps">
                        <div class="step">
                            <h4><?php _e('Étape 1 : Créer un projet Google Cloud', 'restaurant-booking'); ?></h4>
                            <ol>
                                <li><?php _e('Allez sur', 'restaurant-booking'); ?> <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a></li>
                                <li><?php _e('Créez un nouveau projet ou sélectionnez un projet existant', 'restaurant-booking'); ?></li>
                                <li><?php _e('Activez l\'API Google Calendar', 'restaurant-booking'); ?></li>
                            </ol>
                        </div>

                        <div class="step">
                            <h4><?php _e('Étape 2 : Configurer OAuth2', 'restaurant-booking'); ?></h4>
                            <ol>
                                <li><?php _e('Dans "Identifiants", créez un "ID client OAuth 2.0"', 'restaurant-booking'); ?></li>
                                <li><?php _e('Type d\'application : Application Web', 'restaurant-booking'); ?></li>
                                <li><?php _e('URI de redirection autorisée :', 'restaurant-booking'); ?>
                                    <code class="redirect-uri"><?php echo admin_url('admin-ajax.php?action=google_calendar_auth'); ?></code>
                                    <button type="button" onclick="copyToClipboard('<?php echo admin_url('admin-ajax.php?action=google_calendar_auth'); ?>')" class="button button-small">
                                        <?php _e('Copier', 'restaurant-booking'); ?>
                                    </button>
                                </li>
                            </ol>
                        </div>
                    </div>

                    <form method="post">
                        <?php wp_nonce_field('google_calendar_settings', '_wpnonce'); ?>
                        <input type="hidden" name="save_google_settings" value="1">

                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="client_id"><?php _e('Client ID', 'restaurant-booking'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="client_id" name="client_id" value="<?php echo esc_attr($client_id); ?>" class="large-text" />
                                    <p class="description"><?php _e('ID client OAuth2 depuis Google Cloud Console', 'restaurant-booking'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="client_secret"><?php _e('Client Secret', 'restaurant-booking'); ?></label>
                                </th>
                                <td>
                                    <input type="password" id="client_secret" name="client_secret" value="<?php echo esc_attr($client_secret); ?>" class="large-text" />
                                    <p class="description"><?php _e('Secret client OAuth2 depuis Google Cloud Console', 'restaurant-booking'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="calendar_id"><?php _e('ID du calendrier', 'restaurant-booking'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="calendar_id" name="calendar_id" value="<?php echo esc_attr($calendar_id); ?>" class="large-text" placeholder="primary" />
                                    <p class="description"><?php _e('ID du calendrier Google à synchroniser (primary pour le calendrier principal)', 'restaurant-booking'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="sync_frequency"><?php _e('Fréquence de synchronisation', 'restaurant-booking'); ?></label>
                                </th>
                                <td>
                                    <select id="sync_frequency" name="sync_frequency">
                                        <option value="hourly" <?php selected($sync_frequency, 'hourly'); ?>><?php _e('Toutes les heures', 'restaurant-booking'); ?></option>
                                        <option value="twicedaily" <?php selected($sync_frequency, 'twicedaily'); ?>><?php _e('Deux fois par jour', 'restaurant-booking'); ?></option>
                                        <option value="daily" <?php selected($sync_frequency, 'daily'); ?>><?php _e('Une fois par jour', 'restaurant-booking'); ?></option>
                                        <option value="manual" <?php selected($sync_frequency, 'manual'); ?>><?php _e('Manuelle uniquement', 'restaurant-booking'); ?></option>
                                    </select>
                                </td>
                            </tr>
                        </table>

                        <?php submit_button(__('Sauvegarder la configuration', 'restaurant-booking')); ?>
                    </form>
                </div>

                <!-- Autorisation -->
                <?php if (!empty($client_id) && !empty($client_secret)): ?>
                    <div class="settings-section">
                        <h2><?php _e('Autorisation', 'restaurant-booking'); ?></h2>
                        
                        <?php if (!$is_connected): ?>
                            <div class="auth-section">
                                <p><?php _e('Cliquez sur le bouton ci-dessous pour autoriser l\'accès à votre Google Calendar :', 'restaurant-booking'); ?></p>
                                <a href="<?php echo esc_url($auth_url); ?>" class="button button-primary button-large">
                                    🔗 <?php _e('Autoriser l\'accès à Google Calendar', 'restaurant-booking'); ?>
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="auth-section connected">
                                <p>✅ <?php _e('Accès autorisé à Google Calendar', 'restaurant-booking'); ?></p>
                                <button type="button" onclick="revokeAccess()" class="button button-secondary">
                                    <?php _e('Révoquer l\'accès', 'restaurant-booking'); ?>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($current_tab === 'settings'): ?>
            <!-- Paramètres du calendrier -->
            <div class="calendar-settings">
                <?php
                // Inclure le contenu des paramètres calendrier
                require_once RESTAURANT_BOOKING_PLUGIN_DIR . 'admin/class-settings-admin.php';
                $settings_admin = new RestaurantBooking_Settings_Admin();
                $settings_admin->display_calendar_settings_content_only();
                ?>
            </div>

        <?php elseif ($current_tab === 'guide'): ?>
            <!-- Guide d'utilisation Google Calendar -->
            <div class="google-calendar-guide">
                <div class="guide-header">
                    <h2>📖 <?php _e('Guide d\'utilisation - Google Calendar et Blocage des Disponibilités', 'restaurant-booking'); ?></h2>
                    <p class="guide-description">
                        <?php _e('Ce guide vous explique comment utiliser Google Calendar pour gérer les disponibilités de vos services et comprendre le fonctionnement des demandes de devis.', 'restaurant-booking'); ?>
                    </p>
                </div>

                <!-- Section 1: Comprendre le système -->
                <div class="guide-section">
                    <h3>🎯 <?php _e('Comment fonctionne le système', 'restaurant-booking'); ?></h3>
                    <div class="workflow-diagram">
                        <div class="workflow-step">
                            <div class="step-icon">1️⃣</div>
                            <div class="step-content">
                                <h4><?php _e('Vous bloquez des créneaux dans Google Calendar', 'restaurant-booking'); ?></h4>
                                <p><?php _e('Utilisez des mots-clés spécifiques pour indiquer les indisponibilités', 'restaurant-booking'); ?></p>
                            </div>
                        </div>
                        <div class="workflow-arrow">→</div>
                        <div class="workflow-step">
                            <div class="step-icon">2️⃣</div>
                            <div class="step-content">
                                <h4><?php _e('Synchronisation automatique', 'restaurant-booking'); ?></h4>
                                <p><?php _e('Le plugin synchronise et met à jour les disponibilités', 'restaurant-booking'); ?></p>
                            </div>
                        </div>
                        <div class="workflow-arrow">→</div>
                        <div class="workflow-step">
                            <div class="step-icon">3️⃣</div>
                            <div class="step-content">
                                <h4><?php _e('Les clients voient les vraies disponibilités', 'restaurant-booking'); ?></h4>
                                <p><?php _e('Dans le formulaire, les dates bloquées apparaissent clairement', 'restaurant-booking'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Affichage des disponibilités -->
                <div class="guide-section">
                    <h3>👁️ <?php _e('Comment les clients voient les disponibilités', 'restaurant-booking'); ?></h3>
                    
                    <div class="availability-display-info">
                        <div class="important-notice">
                            <h4>📋 <?php _e('Principe de fonctionnement', 'restaurant-booking'); ?></h4>
                            <p><strong><?php _e('Les clients voient un calendrier avec des journées :', 'restaurant-booking'); ?></strong></p>
                            <ul>
                                <li><span style="color: #28a745; font-weight: bold;">🟢 VERTES</span> <?php _e('= Journées disponibles (aucune réservation)', 'restaurant-booking'); ?></li>
                                <li><span style="color: #dc3545; font-weight: bold;">🔴 ROUGES</span> <?php _e('= Journées non disponibles (réservation existante ou blocage)', 'restaurant-booking'); ?></li>
                            </ul>
                            <div class="blocking-rule">
                                <h5>⚠️ <?php _e('Règle importante :', 'restaurant-booking'); ?></h5>
                                <p><strong><?php _e('Dès qu\'il y a UNE réservation dans une journée, TOUTE la journée devient rouge.', 'restaurant-booking'); ?></strong></p>
                                <p><?php _e('Cela s\'applique pour :', 'restaurant-booking'); ?></p>
                                <ul>
                                    <li><?php _e('Les réservations confirmées (restaurant ou remorque)', 'restaurant-booking'); ?></li>
                                    <li><?php _e('Les blocages manuels ("Block", "Vacances", "Fermeture exceptionnelle")', 'restaurant-booking'); ?></li>
                                    <li><?php _e('Les blocages spécifiques ("Block Restaurant", "Block Remorque")', 'restaurant-booking'); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Comment bloquer des créneaux -->
                <div class="guide-section">
                    <h3>🚫 <?php _e('Comment bloquer des créneaux depuis Google Calendar', 'restaurant-booking'); ?></h3>
                    
                    <div class="blocking-instructions">
                        <div class="instruction-card">
                            <h4>📝 <?php _e('1. Créer un événement Google Calendar', 'restaurant-booking'); ?></h4>
                            <ul>
                                <li><?php _e('Ouvrez votre Google Calendar', 'restaurant-booking'); ?></li>
                                <li><?php _e('Cliquez sur la date/heure à bloquer', 'restaurant-booking'); ?></li>
                                <li><?php _e('Créez un nouvel événement', 'restaurant-booking'); ?></li>
                            </ul>
                        </div>

                        <div class="instruction-card">
                            <h4>🏷️ <?php _e('2. Utiliser les bons mots-clés dans le titre', 'restaurant-booking'); ?></h4>
                            <div class="keyword-examples">
                                <div class="keyword-group">
                                    <h5><?php _e('Pour bloquer le Restaurant :', 'restaurant-booking'); ?></h5>
                                    <code>Block Restaurant</code><br>
                                    <code>Restaurant indisponible</code><br>
                                    <code>Restaurant fermé</code>
                                </div>
                                <div class="keyword-group">
                                    <h5><?php _e('Pour bloquer la Remorque :', 'restaurant-booking'); ?></h5>
                                    <code>Block Remorque</code><br>
                                    <code>Remorque indisponible</code><br>
                                    <code>Remorque maintenance</code>
                                </div>
                                <div class="keyword-group">
                                    <h5><?php _e('Pour bloquer les deux services :', 'restaurant-booking'); ?></h5>
                                    <code>Block</code><br>
                                    <code>Vacances</code><br>
                                    <code>Fermeture exceptionnelle</code>
                                </div>
                            </div>
                        </div>

                        <div class="instruction-card">
                            <h4>⏰ <?php _e('3. Choisir le type de blocage', 'restaurant-booking'); ?></h4>
                            <div class="blocking-types">
                                <div class="blocking-type">
                                    <h5>🌅 <?php _e('Événement "Toute la journée"', 'restaurant-booking'); ?></h5>
                                    <p><?php _e('→ Bloque complètement la date', 'restaurant-booking'); ?></p>
                                    <p><strong><?php _e('Utilisation :', 'restaurant-booking'); ?></strong> <?php _e('Fermeture, vacances, maintenance', 'restaurant-booking'); ?></p>
                                </div>
                                <div class="blocking-type">
                                    <h5>🕐 <?php _e('Événement avec heures précises', 'restaurant-booking'); ?></h5>
                                    <p><?php _e('→ Bloque TOUTE la journée (même comportement)', 'restaurant-booking'); ?></p>
                                    <p><strong><?php _e('Utilisation :', 'restaurant-booking'); ?></strong> <?php _e('Rendez-vous, livraison, autre prestation', 'restaurant-booking'); ?></p>
                                    <p><em><?php _e('Note importante : Le plugin ne gère pas les réservations par créneaux horaires. Toute réservation dans une journée bloque la journée entière.', 'restaurant-booking'); ?></em></p>
                                </div>
                            </div>
                        </div>

                        <div class="instruction-card">
                            <h4>👁️ <?php _e('4. Configurer la visibilité', 'restaurant-booking'); ?></h4>
                            <div class="visibility-options">
                                <div class="visibility-option recommended">
                                    <h5>✅ <?php _e('Occupé (Recommandé)', 'restaurant-booking'); ?></h5>
                                    <p><?php _e('L\'événement bloquera le créneau dans le système', 'restaurant-booking'); ?></p>
                                </div>
                                <div class="visibility-option">
                                    <h5>❌ <?php _e('Disponible', 'restaurant-booking'); ?></h5>
                                    <p><?php _e('L\'événement n\'affectera pas les disponibilités', 'restaurant-booking'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Gestion des demandes de devis -->
                <div class="guide-section">
                    <h3>📋 <?php _e('Gestion des demandes de devis', 'restaurant-booking'); ?></h3>
                    
                    <div class="quote-management">
                        <div class="important-notice">
                            <h4>⚠️ <?php _e('Important à retenir', 'restaurant-booking'); ?></h4>
                            <p><strong><?php _e('Les demandes de devis NE bloquent PAS automatiquement les dates !', 'restaurant-booking'); ?></strong></p>
                            <p><?php _e('C\'est volontaire pour vous laisser le contrôle total de vos disponibilités.', 'restaurant-booking'); ?></p>
                        </div>

                        <div class="quote-workflow">
                            <h4>🔄 <?php _e('Processus complet d\'une demande', 'restaurant-booking'); ?></h4>
                            <div class="process-steps">
                                <div class="process-step">
                                    <span class="step-number">1</span>
                                    <div class="step-details">
                                        <h5><?php _e('Client fait une demande de devis', 'restaurant-booking'); ?></h5>
                                        <p><?php _e('Il choisit une date disponible dans le calendrier', 'restaurant-booking'); ?></p>
                                        <span class="step-status neutral"><?php _e('Statut : Demande en attente', 'restaurant-booking'); ?></span>
                                    </div>
                                </div>
                                <div class="process-step">
                                    <span class="step-number">2</span>
                                    <div class="step-details">
                                        <h5><?php _e('Vous recevez la demande par email', 'restaurant-booking'); ?></h5>
                                        <p><?php _e('La date reste DISPONIBLE pour d\'autres clients', 'restaurant-booking'); ?></p>
                                        <span class="step-status neutral"><?php _e('Google Calendar : Aucun changement', 'restaurant-booking'); ?></span>
                                    </div>
                                </div>
                                <div class="process-step">
                                    <span class="step-number">3</span>
                                    <div class="step-details">
                                        <h5><?php _e('Vous validez ou refusez la demande', 'restaurant-booking'); ?></h5>
                                        <p><strong><?php _e('Si validée :', 'restaurant-booking'); ?></strong> <?php _e('Créez manuellement l\'événement dans VOTRE Google Calendar (pas sur le plugin)', 'restaurant-booking'); ?></p>
                                        <p><strong><?php _e('Si refusée :', 'restaurant-booking'); ?></strong> <?php _e('Rien à faire, la date reste disponible', 'restaurant-booking'); ?></p>
                                        <p><em><?php _e('Important : La validation ne se fait pas dans le plugin mais directement dans votre Google Agenda personnel.', 'restaurant-booking'); ?></em></p>
                                    </div>
                                </div>
                                <div class="process-step">
                                    <span class="step-number">4</span>
                                    <div class="step-details">
                                        <h5><?php _e('Synchronisation finale', 'restaurant-booking'); ?></h5>
                                        <p><?php _e('L\'événement Google Calendar bloque la date pour les futurs clients', 'restaurant-booking'); ?></p>
                                        <span class="step-status success"><?php _e('Date définitivement réservée', 'restaurant-booking'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 5: Exemples pratiques -->
                <div class="guide-section">
                    <h3>💡 <?php _e('Exemples pratiques', 'restaurant-booking'); ?></h3>
                    
                    <div class="practical-examples">
                        <div class="example-card">
                            <h4>📅 <?php _e('Exemple 1 : Vacances d\'été', 'restaurant-booking'); ?></h4>
                            <div class="example-details">
                                <p><strong><?php _e('Situation :', 'restaurant-booking'); ?></strong> <?php _e('Vous partez en vacances du 15 au 30 août', 'restaurant-booking'); ?></p>
                                <p><strong><?php _e('Action :', 'restaurant-booking'); ?></strong></p>
                                <ul>
                                    <li><?php _e('Créez un événement "Vacances" du 15/08 au 30/08', 'restaurant-booking'); ?></li>
                                    <li><?php _e('Cochez "Toute la journée"', 'restaurant-booking'); ?></li>
                                    <li><?php _e('Visibilité : "Occupé"', 'restaurant-booking'); ?></li>
                                </ul>
                                <p><strong><?php _e('Résultat :', 'restaurant-booking'); ?></strong> <?php _e('Toutes les dates sont bloquées dans le formulaire client', 'restaurant-booking'); ?></p>
                            </div>
                        </div>

                        <div class="example-card">
                            <h4>🍽️ <?php _e('Exemple 2 : Maintenance du restaurant', 'restaurant-booking'); ?></h4>
                            <div class="example-details">
                                <p><strong><?php _e('Situation :', 'restaurant-booking'); ?></strong> <?php _e('Maintenance du restaurant le 10 juin de 9h à 15h', 'restaurant-booking'); ?></p>
                                <p><strong><?php _e('Action :', 'restaurant-booking'); ?></strong></p>
                                <ul>
                                    <li><?php _e('Créez un événement "Block Restaurant - Maintenance"', 'restaurant-booking'); ?></li>
                                    <li><?php _e('Horaire : 10/06 de 09:00 à 15:00', 'restaurant-booking'); ?></li>
                                    <li><?php _e('Visibilité : "Occupé"', 'restaurant-booking'); ?></li>
                                </ul>
                                <p><strong><?php _e('Résultat :', 'restaurant-booking'); ?></strong> <?php _e('Le restaurant est bloqué TOUTE LA JOURNÉE (le plugin ne gère pas les créneaux horaires)', 'restaurant-booking'); ?></p>
                            </div>
                        </div>

                        <div class="example-card">
                            <h4>🚚 <?php _e('Exemple 3 : Remorque en réparation', 'restaurant-booking'); ?></h4>
                            <div class="example-details">
                                <p><strong><?php _e('Situation :', 'restaurant-booking'); ?></strong> <?php _e('La remorque est au garage toute la journée du 5 mars', 'restaurant-booking'); ?></p>
                                <p><strong><?php _e('Action :', 'restaurant-booking'); ?></strong></p>
                                <ul>
                                    <li><?php _e('Créez un événement "Block Remorque - Réparation"', 'restaurant-booking'); ?></li>
                                    <li><?php _e('Date : 05/03 - Toute la journée', 'restaurant-booking'); ?></li>
                                    <li><?php _e('Visibilité : "Occupé"', 'restaurant-booking'); ?></li>
                                </ul>
                                <p><strong><?php _e('Résultat :', 'restaurant-booking'); ?></strong> <?php _e('Seul le service remorque est bloqué, le restaurant reste disponible', 'restaurant-booking'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 6: Conseils et bonnes pratiques -->
                <div class="guide-section">
                    <h3>🎯 <?php _e('Conseils et bonnes pratiques', 'restaurant-booking'); ?></h3>
                    
                    <div class="tips-grid">
                        <div class="tip-card">
                            <h4>⚡ <?php _e('Synchronisation régulière', 'restaurant-booking'); ?></h4>
                            <p><?php _e('Cliquez sur "Synchroniser Google Calendar" après avoir créé/modifié des événements pour une mise à jour immédiate.', 'restaurant-booking'); ?></p>
                        </div>
                        <div class="tip-card">
                            <h4>📝 <?php _e('Nommage cohérent', 'restaurant-booking'); ?></h4>
                            <p><?php _e('Utilisez toujours les mêmes mots-clés (Block, Restaurant, Remorque) pour éviter les erreurs de synchronisation.', 'restaurant-booking'); ?></p>
                        </div>
                        <div class="tip-card">
                            <h4>📧 <?php _e('Gestion des demandes', 'restaurant-booking'); ?></h4>
                            <p><?php _e('Traitez rapidement les demandes de devis pour éviter les conflits de réservation sur les mêmes dates.', 'restaurant-booking'); ?></p>
                        </div>
                        <div class="tip-card">
                            <h4>🔍 <?php _e('Vérification visuelle', 'restaurant-booking'); ?></h4>
                            <p><?php _e('Consultez régulièrement l\'onglet "Calendrier des disponibilités" pour vérifier que les blocages sont bien appliqués.', 'restaurant-booking'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Section 7: Dépannage -->
                <div class="guide-section">
                    <h3>🔧 <?php _e('Dépannage', 'restaurant-booking'); ?></h3>
                    
                    <div class="troubleshooting">
                        <div class="trouble-item">
                            <h4>❓ <?php _e('Mon événement Google Calendar n\'apparaît pas comme bloqué', 'restaurant-booking'); ?></h4>
                            <ul>
                                <li><?php _e('Vérifiez que le titre contient un des mots-clés requis', 'restaurant-booking'); ?></li>
                                <li><?php _e('Assurez-vous que la visibilité est "Occupé"', 'restaurant-booking'); ?></li>
                                <li><?php _e('Cliquez sur "Synchroniser Google Calendar"', 'restaurant-booking'); ?></li>
                                <li><?php _e('Attendez quelques minutes et actualisez la page', 'restaurant-booking'); ?></li>
                            </ul>
                        </div>
                        <div class="trouble-item">
                            <h4>❓ <?php _e('Les clients peuvent réserver sur une date que j\'ai bloquée', 'restaurant-booking'); ?></h4>
                            <ul>
                                <li><?php _e('Vérifiez la synchronisation dans l\'onglet "Configuration Google Calendar"', 'restaurant-booking'); ?></li>
                                <li><?php _e('Testez la connexion Google Calendar', 'restaurant-booking'); ?></li>
                                <li><?php _e('Vérifiez que l\'événement est sur le bon calendrier Google', 'restaurant-booking'); ?></li>
                            </ul>
                        </div>
                        <div class="trouble-item">
                            <h4>❓ <?php _e('Je ne vois pas les événements Google Calendar dans l\'admin', 'restaurant-booking'); ?></h4>
                            <ul>
                                <li><?php _e('Vérifiez que Google Calendar est bien connecté (onglet Configuration)', 'restaurant-booking'); ?></li>
                                <li><?php _e('Lancez une synchronisation manuelle', 'restaurant-booking'); ?></li>
                                <li><?php _e('Vérifiez les permissions de votre compte Google', 'restaurant-booking'); ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </div>
</div>


<style>
.unified-calendar-content {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-top: none;
    padding: 20px;
    margin-top: -1px;
}

.google-sync-status {
    margin-bottom: 20px;
    padding: 15px;
    border-radius: 4px;
    border: 1px solid #ddd;
}

.google-sync-status.connected {
    background: #f6fff6;
    border-color: #00a32a;
}

.google-sync-status.not-connected {
    background: #fff6f6;
    border-color: #d63638;
}

.sync-indicator {
    display: flex;
    align-items: center;
    gap: 10px;
}

.sync-indicator .dashicons {
    font-size: 20px;
    width: 20px;
    height: 20px;
}

.sync-indicator .dashicons-yes-alt {
    color: #00a32a;
}

.sync-indicator .dashicons-warning {
    color: #d63638;
}

.sync-details {
    color: #666;
    font-weight: normal;
}

.calendar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 15px;
    background: #f9f9f9;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
}

.calendar-nav {
    display: flex;
    align-items: center;
    gap: 20px;
}

.current-month {
    margin: 0;
    font-size: 24px;
    font-weight: 600;
    color: #243127;
}


.calendar-legend {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
    padding: 10px;
    background: #f9f9f9;
    border-radius: 4px;
    flex-wrap: wrap;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
}

.legend-color {
    width: 16px;
    height: 16px;
    border-radius: 2px;
    border: 1px solid #ddd;
}

.legend-color.available { background: #d4edda; }
.legend-color.unavailable { background: #f8d7da; }
.legend-color.booked { background: #fff3cd; }
.legend-color.google-sync { background: #cce5ff; border-color: #0073aa; }
.legend-color.past { background: #e2e3e5; }

.calendar-container {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 20px;
}

.calendar-table {
    width: 100%;
    border-collapse: collapse;
}

.calendar-table th {
    background: #243127;
    color: #fff;
    padding: 12px;
    text-align: center;
    font-weight: 600;
    border-right: 1px solid #ddd;
}

.calendar-table th:last-child {
    border-right: none;
}

.calendar-day {
    width: 14.28%;
    height: 100px;
    border: 1px solid #e2e3e5;
    position: relative;
    vertical-align: top;
    padding: 5px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.calendar-day:hover:not(.past) {
    background-color: rgba(36, 49, 39, 0.1);
}

.calendar-day.available {
    background-color: #d4edda;
}

.calendar-day.unavailable {
    background-color: #f8d7da;
}

.calendar-day.booked {
    background-color: #fff3cd;
}

.calendar-day.google-sync {
    background-color: #cce5ff;
    border-color: #0073aa;
}

.calendar-day.partial-blocked {
    background-color: #fff8dc;
    border-left: 4px solid #ffc107;
}

.calendar-day.past {
    background-color: #e2e3e5;
    color: #6c757d;
    cursor: not-allowed;
}

.calendar-day.today {
    border: 2px solid #FFB404;
    font-weight: bold;
}

.day-number {
    display: block;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 3px;
}

.event-details {
    font-size: 10px;
    line-height: 1.2;
    max-height: 60px;
    overflow: hidden;
    margin-bottom: 3px;
}

.event-item {
    background: rgba(255, 255, 255, 0.8);
    padding: 1px 3px;
    margin-bottom: 1px;
    border-radius: 2px;
    border-left: 2px solid #dc3545;
}

.event-item.google-event {
    border-left-color: #4285f4;
    background: rgba(66, 133, 244, 0.1);
}

.event-text {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-weight: 500;
}

.event-item.blocked-day {
    background: rgba(220, 53, 69, 0.1);
    border-left-color: #dc3545;
    padding: 2px 4px;
}

.event-item.blocked-day.google-event {
    background: rgba(66, 133, 244, 0.1);
    border-left-color: #4285f4;
}

.event-label {
    display: block;
    font-size: 8px;
    font-weight: bold;
    color: #dc3545;
    margin-bottom: 1px;
}

.event-item.google-event .event-label {
    color: #4285f4;
}

.event-title {
    display: block;
    font-size: 9px;
    font-weight: 500;
    line-height: 1.1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: #333;
}

.toggle-availability {
    position: absolute;
    bottom: 5px;
    right: 5px;
    width: 20px;
    height: 20px;
    border: none;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.8);
    cursor: pointer;
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.empty-day {
    height: 80px;
    background: #f8f9fa;
}

.calendar-actions {
    padding: 15px;
    background: #f9f9f9;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
}

.actions-group h3 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #243127;
}

.actions-group .button {
    margin-right: 10px;
    margin-bottom: 5px;
}


/* Styles pour l'onglet Google Calendar */
.connection-status {
    margin-bottom: 30px;
}

.status-card {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 20px;
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 8px;
    margin-bottom: 20px;
}

.status-card.connected {
    border-color: #00a32a;
    background: #f6fff6;
}

.status-card.disconnected {
    border-color: #d63638;
    background: #fff6f6;
}

.status-icon {
    font-size: 24px;
}

.status-info {
    flex: 1;
}

.status-info h3 {
    margin: 0 0 5px 0;
    font-size: 18px;
}

.status-info p {
    margin: 0;
    color: #666;
}

.status-actions {
    display: flex;
    gap: 10px;
}

.settings-section {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.settings-section h2 {
    margin-top: 0;
    color: #243127;
    border-bottom: 1px solid #ddd;
    padding-bottom: 10px;
}

.setup-steps {
    margin-bottom: 30px;
}

.step {
    margin-bottom: 20px;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 4px;
}

.step h4 {
    margin-top: 0;
    color: #243127;
}

.step ol {
    margin-bottom: 0;
}

.redirect-uri {
    background: #f1f1f1;
    padding: 5px 8px;
    border-radius: 3px;
    font-family: monospace;
    font-size: 13px;
}

.auth-section {
    text-align: center;
    padding: 30px;
    background: #f9f9f9;
    border-radius: 4px;
}

.auth-section.connected {
    background: #f6fff6;
}

/* Styles pour l'onglet Paramètres */
.calendar-settings .settings-section {
    margin-bottom: 20px;
}
</style>

<script>
function toggleAvailability(date, serviceType) {
    // TODO: Implémenter la logique AJAX pour basculer la disponibilité
    console.log('Toggle availability for', date, serviceType);
    
    // Simulation visuelle
    var dayElement = document.querySelector('[data-date="' + date + '"]');
    var button = dayElement.querySelector('.toggle-availability');
    
    if (dayElement.classList.contains('available')) {
        dayElement.classList.remove('available');
        dayElement.classList.add('unavailable');
        button.textContent = '✗';
    } else if (dayElement.classList.contains('unavailable')) {
        dayElement.classList.remove('unavailable');
        dayElement.classList.add('available');
        button.textContent = '✓';
    }
}

function syncGoogleCalendar() {
    if (confirm('Synchroniser maintenant avec Google Calendar ?')) {
        // Afficher le message de chargement
        var button = event.target;
        var originalText = button.innerHTML;
        button.innerHTML = '⏳ Synchronisation en cours...';
        button.disabled = true;
        
        // Appel AJAX pour déclencher la synchronisation
        jQuery.post(ajaxurl, {
            action: 'restaurant_booking_sync_google_calendar',
            nonce: '<?php echo wp_create_nonce('sync_google_calendar'); ?>'
        }, function(response) {
            if (response.success) {
                alert('✅ Synchronisation réussie !');
                // Recharger la page pour afficher les nouvelles données
                location.reload();
            } else {
                alert('❌ Erreur lors de la synchronisation : ' + (response.data || 'Erreur inconnue'));
            }
        }).fail(function() {
            alert('❌ Erreur de communication avec le serveur');
        }).always(function() {
            button.innerHTML = originalText;
            button.disabled = false;
        });
    }
}

// Fonctions pour l'onglet Google Calendar
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(function() {
            alert('URI copiée dans le presse-papiers');
        });
    } else {
        // Fallback pour les navigateurs plus anciens
        var textArea = document.createElement("textarea");
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        alert('URI copiée dans le presse-papiers');
    }
}

function revokeAccess() {
    if (confirm('Êtes-vous sûr de vouloir révoquer l\'accès à Google Calendar ?')) {
        // TODO: Implémenter la révocation
        alert('Fonctionnalité en cours de développement');
    }
}
</script>
