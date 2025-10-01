<?php
/**
 * Vue du calendrier des disponibilités
 *
 * @package RestaurantBooking
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Paramètres par défaut
$current_month = $month ?? date('n');
$current_year = $year ?? date('Y');
$service_type = 'both'; // Toujours afficher les deux services

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

?>
<div class="wrap">
    <h1><?php _e('Calendrier des disponibilités', 'restaurant-booking'); ?></h1>


    <div class="calendar-header">
        <!-- Navigation mensuelle -->
        <div class="calendar-nav">
            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-calendar&month=' . $prev_month . '&year=' . $prev_year); ?>" class="button">
                ← <?php echo $month_names[$prev_month]; ?>
            </a>
            
            <h2 class="current-month">
                <?php echo $month_names[$current_month] . ' ' . $current_year; ?>
            </h2>
            
            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-calendar&month=' . $next_month . '&year=' . $next_year); ?>" class="button">
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

    <!-- Légende -->
    <div class="calendar-legend">
        <h3><?php _e('Légende', 'restaurant-booking'); ?></h3>
        <div class="legend-items">
            <div class="legend-item">
                <span class="legend-color available"></span>
                <span><?php _e('Disponible', 'restaurant-booking'); ?></span>
            </div>
            <div class="legend-item">
                <span class="legend-color unavailable"></span>
                <span><?php _e('Indisponible', 'restaurant-booking'); ?></span>
            </div>
        <div class="legend-item">
            <span class="legend-color google-sync"></span>
            <span><?php _e('Synchronisé depuis Google Calendar', 'restaurant-booking'); ?></span>
        </div>
        <div class="legend-item">
            <span class="legend-color partial-blocked"></span>
            <span><?php _e('Partiellement bloqué (créneaux spécifiques)', 'restaurant-booking'); ?></span>
        </div>
            <div class="legend-item">
                <span class="legend-color past"></span>
                <span><?php _e('Date passée', 'restaurant-booking'); ?></span>
            </div>
        </div>
    </div>

</div>

<style>
.calendar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 15px;
    background: #fff;
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
.legend-color.past { background: #e2e3e5; }
.legend-color.google-sync { background: #e3f2fd; border-color: #4285f4; }
.legend-color.partial-blocked { background: #fff8dc; border-left: 4px solid #ffc107; }

.calendar-container {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    overflow: hidden;
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

.calendar-day.past {
    background-color: #e2e3e5;
    color: #6c757d;
    cursor: not-allowed;
}

.calendar-day.today {
    border: 2px solid #FFB404;
    font-weight: bold;
}

.calendar-day.google-sync {
    border: 2px solid #4285f4;
}

.calendar-day.partial-blocked {
    background-color: #fff8dc;
    border-left: 4px solid #ffc107;
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
    } else {
        dayElement.classList.remove('unavailable');
        dayElement.classList.add('available');
        button.textContent = '✓';
    }
}
</script>