<?php
/**
 * Vue du calendrier d'administration
 *
 * @package RestaurantBooking
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Variables disponibles : $month, $year, $service_type
$current_date = new DateTime();
$calendar_date = new DateTime("$year-$month-01");

// Navigation mois précédent/suivant
$prev_month = clone $calendar_date;
$prev_month->modify('-1 month');
$next_month = clone $calendar_date;
$next_month->modify('+1 month');

// Générer les données du calendrier
$first_day_of_month = clone $calendar_date;
$last_day_of_month = clone $calendar_date;
$last_day_of_month->modify('last day of this month');

// Commencer par le lundi de la semaine contenant le premier jour du mois
$calendar_start = clone $first_day_of_month;
$calendar_start->modify('monday this week');

// Finir par le dimanche de la semaine contenant le dernier jour du mois
$calendar_end = clone $last_day_of_month;
$calendar_end->modify('sunday this week');

// Données d'exemple pour les réservations
$sample_bookings = array(
    '2024-01-15' => array(
        array('time' => '12:00', 'client' => 'Marie Dupont', 'service' => 'restaurant', 'guests' => 4),
        array('time' => '19:30', 'client' => 'Pierre Martin', 'service' => 'restaurant', 'guests' => 2)
    ),
    '2024-01-20' => array(
        array('time' => '18:00', 'client' => 'Sophie Bernard', 'service' => 'remorque', 'guests' => 25)
    ),
    '2024-01-25' => array(
        array('time' => '14:00', 'client' => 'Jean Durand', 'service' => 'remorque', 'guests' => 50)
    )
);

$months_fr = array(
    1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
    5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
    9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
);

?>
<div class="wrap">
    <h1><?php _e('Calendrier des réservations', 'restaurant-booking'); ?></h1>

    <!-- Filtres et navigation -->
    <div class="calendar-header">
        <div class="calendar-navigation">
            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-calendar&month=' . $prev_month->format('n') . '&year=' . $prev_month->format('Y') . '&service_type=' . $service_type); ?>" class="button">
                &laquo; <?php echo $months_fr[(int)$prev_month->format('n')]; ?>
            </a>
            
            <h2 class="calendar-title">
                <?php echo $months_fr[$month] . ' ' . $year; ?>
            </h2>
            
            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-calendar&month=' . $next_month->format('n') . '&year=' . $next_month->format('Y') . '&service_type=' . $service_type); ?>" class="button">
                <?php echo $months_fr[(int)$next_month->format('n')]; ?> &raquo;
            </a>
        </div>

        <div class="calendar-filters">
            <form method="get" class="calendar-filter-form">
                <input type="hidden" name="page" value="restaurant-booking-calendar">
                <input type="hidden" name="month" value="<?php echo $month; ?>">
                <input type="hidden" name="year" value="<?php echo $year; ?>">
                
                <select name="service_type" onchange="this.form.submit()">
                    <option value=""><?php _e('Tous les services', 'restaurant-booking'); ?></option>
                    <option value="restaurant" <?php selected($service_type, 'restaurant'); ?>><?php _e('Restaurant', 'restaurant-booking'); ?></option>
                    <option value="remorque" <?php selected($service_type, 'remorque'); ?>><?php _e('Remorque', 'restaurant-booking'); ?></option>
                </select>

                <a href="<?php echo admin_url('admin.php?page=restaurant-booking-calendar&month=' . date('n') . '&year=' . date('Y')); ?>" class="button">
                    <?php _e('Aujourd\'hui', 'restaurant-booking'); ?>
                </a>
            </form>
        </div>
    </div>

    <!-- Légende -->
    <div class="calendar-legend">
        <div class="legend-item">
            <span class="legend-color restaurant"></span>
            <span><?php _e('Restaurant', 'restaurant-booking'); ?></span>
        </div>
        <div class="legend-item">
            <span class="legend-color remorque"></span>
            <span><?php _e('Remorque', 'restaurant-booking'); ?></span>
        </div>
        <div class="legend-item">
            <span class="legend-color unavailable"></span>
            <span><?php _e('Indisponible', 'restaurant-booking'); ?></span>
        </div>
    </div>

    <!-- Calendrier -->
    <div class="calendar-container">
        <table class="calendar-table">
            <thead>
                <tr>
                    <th><?php _e('Lun', 'restaurant-booking'); ?></th>
                    <th><?php _e('Mar', 'restaurant-booking'); ?></th>
                    <th><?php _e('Mer', 'restaurant-booking'); ?></th>
                    <th><?php _e('Jeu', 'restaurant-booking'); ?></th>
                    <th><?php _e('Ven', 'restaurant-booking'); ?></th>
                    <th><?php _e('Sam', 'restaurant-booking'); ?></th>
                    <th><?php _e('Dim', 'restaurant-booking'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $current_week_start = clone $calendar_start;
                
                while ($current_week_start <= $calendar_end) {
                    echo '<tr>';
                    
                    for ($day = 0; $day < 7; $day++) {
                        $current_day = clone $current_week_start;
                        $current_day->modify("+$day days");
                        
                        $day_key = $current_day->format('Y-m-d');
                        $is_current_month = $current_day->format('n') == $month;
                        $is_today = $current_day->format('Y-m-d') == date('Y-m-d');
                        $is_past = $current_day < $current_date;
                        
                        $classes = array('calendar-day');
                        if (!$is_current_month) $classes[] = 'other-month';
                        if ($is_today) $classes[] = 'today';
                        if ($is_past) $classes[] = 'past';
                        
                        // Vérifier s'il y a des réservations ce jour
                        $day_bookings = isset($sample_bookings[$day_key]) ? $sample_bookings[$day_key] : array();
                        if (!empty($day_bookings)) {
                            $classes[] = 'has-bookings';
                        }
                        
                        echo '<td class="' . implode(' ', $classes) . '" data-date="' . $day_key . '">';
                        echo '<div class="day-header">';
                        echo '<span class="day-number">' . $current_day->format('j') . '</span>';
                        
                        if (!empty($day_bookings)) {
                            echo '<span class="booking-count">' . count($day_bookings) . '</span>';
                        }
                        
                        echo '</div>';
                        
                        // Afficher les réservations du jour
                        if (!empty($day_bookings)) {
                            echo '<div class="day-bookings">';
                            foreach ($day_bookings as $booking) {
                                if ($service_type && $booking['service'] !== $service_type) {
                                    continue;
                                }
                                
                                echo '<div class="booking-item ' . $booking['service'] . '">';
                                echo '<div class="booking-time">' . $booking['time'] . '</div>';
                                echo '<div class="booking-client">' . esc_html($booking['client']) . '</div>';
                                echo '<div class="booking-guests">' . $booking['guests'] . ' pers.</div>';
                                echo '</div>';
                            }
                            echo '</div>';
                        }
                        
                        echo '</td>';
                    }
                    
                    echo '</tr>';
                    $current_week_start->modify('+1 week');
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Statistiques du mois -->
    <div class="calendar-stats">
        <h3><?php _e('Statistiques du mois', 'restaurant-booking'); ?></h3>
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-number">24</div>
                <div class="stat-label"><?php _e('Réservations totales', 'restaurant-booking'); ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-number">156</div>
                <div class="stat-label"><?php _e('Couverts', 'restaurant-booking'); ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-number">3 450 €</div>
                <div class="stat-label"><?php _e('Chiffre d\'affaires', 'restaurant-booking'); ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-number">85%</div>
                <div class="stat-label"><?php _e('Taux d\'occupation', 'restaurant-booking'); ?></div>
            </div>
        </div>
    </div>
</div>

<style>
.calendar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 20px 0;
    padding: 20px;
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
}

.calendar-navigation {
    display: flex;
    align-items: center;
    gap: 20px;
}

.calendar-title {
    margin: 0;
    font-size: 24px;
    font-weight: 600;
}

.calendar-filter-form {
    display: flex;
    align-items: center;
    gap: 10px;
}

.calendar-legend {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 4px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 13px;
}

.legend-color {
    width: 12px;
    height: 12px;
    border-radius: 2px;
}

.legend-color.restaurant { background: #d4edda; }
.legend-color.remorque { background: #d1ecf1; }
.legend-color.unavailable { background: #f8d7da; }

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
    background: #f9f9f9;
    padding: 15px 5px;
    text-align: center;
    font-weight: 600;
    border-bottom: 1px solid #c3c4c7;
}

.calendar-day {
    width: 14.28%;
    height: 120px;
    vertical-align: top;
    border: 1px solid #e1e1e1;
    position: relative;
    background: #fff;
}

.calendar-day.other-month {
    background: #f9f9f9;
    color: #999;
}

.calendar-day.today {
    background: #e7f3ff;
}

.calendar-day.past {
    background: #f5f5f5;
}

.calendar-day.has-bookings {
    background: #f0f8f0;
}

.day-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 5px 8px;
    border-bottom: 1px solid #e1e1e1;
    background: rgba(0,0,0,0.02);
}

.day-number {
    font-weight: 600;
    font-size: 14px;
}

.booking-count {
    background: #0073aa;
    color: white;
    border-radius: 10px;
    padding: 2px 6px;
    font-size: 11px;
    font-weight: bold;
}

.day-bookings {
    padding: 5px;
    max-height: 90px;
    overflow-y: auto;
}

.booking-item {
    margin-bottom: 3px;
    padding: 3px 5px;
    border-radius: 3px;
    font-size: 11px;
    line-height: 1.2;
}

.booking-item.restaurant {
    background: #d4edda;
    border-left: 3px solid #28a745;
}

.booking-item.remorque {
    background: #d1ecf1;
    border-left: 3px solid #17a2b8;
}

.booking-time {
    font-weight: bold;
}

.booking-client {
    color: #333;
}

.booking-guests {
    color: #666;
    font-size: 10px;
}

.calendar-stats {
    margin-top: 30px;
    padding: 20px;
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
}

.calendar-stats h3 {
    margin-top: 0;
    margin-bottom: 20px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.stat-item {
    text-align: center;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 4px;
}

.stat-number {
    font-size: 24px;
    font-weight: bold;
    color: #0073aa;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 13px;
    color: #666;
}

@media (max-width: 768px) {
    .calendar-header {
        flex-direction: column;
        gap: 15px;
    }
    
    .calendar-day {
        height: 80px;
    }
    
    .booking-item {
        font-size: 10px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Clic sur une journée pour voir les détails
    $('.calendar-day').on('click', function() {
        var date = $(this).data('date');
        if (date && !$(this).hasClass('past') && !$(this).hasClass('other-month')) {
            // Ici vous pourriez ouvrir une modal ou rediriger vers une page de détail
            console.log('Clic sur la date:', date);
        }
    });
    
    // Survol pour afficher plus d'informations
    $('.booking-item').on('mouseenter', function() {
        // Ici vous pourriez afficher une tooltip avec plus de détails
    });
});
</script>
