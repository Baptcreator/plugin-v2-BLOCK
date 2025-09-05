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
$service_type = $service_type ?? 'restaurant';

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
            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-calendar&month=' . $prev_month . '&year=' . $prev_year . '&service_type=' . $service_type); ?>" class="button">
                ← <?php echo $month_names[$prev_month]; ?>
            </a>
            
            <h2 class="current-month">
                <?php echo $month_names[$current_month] . ' ' . $current_year; ?>
            </h2>
            
            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-calendar&month=' . $next_month . '&year=' . $next_year . '&service_type=' . $service_type); ?>" class="button">
                <?php echo $month_names[$next_month]; ?> →
            </a>
        </div>

        <!-- Sélection du service -->
        <div class="service-selector">
            <label for="service_type_select"><?php _e('Service :', 'restaurant-booking'); ?></label>
            <select id="service_type_select" onchange="changeServiceType(this.value)">
                <option value="restaurant" <?php selected($service_type, 'restaurant'); ?>><?php _e('Restaurant', 'restaurant-booking'); ?></option>
                <option value="remorque" <?php selected($service_type, 'remorque'); ?>><?php _e('Remorque', 'restaurant-booking'); ?></option>
                <option value="both" <?php selected($service_type, 'both'); ?>><?php _e('Les deux', 'restaurant-booking'); ?></option>
            </select>
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
                    
                    // Simuler le statut (à remplacer par une vraie requête)
                    $status = $is_past ? 'past' : 'available';
                    if ($current_date % 7 == 0) $status = 'unavailable'; // Exemple: dimanche non disponible
                    if ($current_date % 15 == 0) $status = 'booked'; // Exemple: quelques jours réservés
                    
                    $classes = array('calendar-day', $status);
                    if ($is_today) $classes[] = 'today';
                    
                    echo '<td class="' . implode(' ', $classes) . '" data-date="' . $date_string . '">';
                    echo '<span class="day-number">' . $current_date . '</span>';
                    
                    // Indicateur de statut
                    if (!$is_past) {
                        echo '<button type="button" class="toggle-availability" onclick="toggleAvailability(\'' . $date_string . '\', \'' . $service_type . '\')">';
                        echo $status == 'available' ? '✓' : '✗';
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
                        
                        // Simuler le statut
                        $status = $is_past ? 'past' : 'available';
                        if ($current_date % 7 == 0) $status = 'unavailable';
                        if ($current_date % 15 == 0) $status = 'booked';
                        
                        $classes = array('calendar-day', $status);
                        if ($is_today) $classes[] = 'today';
                        
                        echo '<td class="' . implode(' ', $classes) . '" data-date="' . $date_string . '">';
                        echo '<span class="day-number">' . $current_date . '</span>';
                        
                        if (!$is_past) {
                            echo '<button type="button" class="toggle-availability" onclick="toggleAvailability(\'' . $date_string . '\', \'' . $service_type . '\')">';
                            echo $status == 'available' ? '✓' : '✗';
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

    <!-- Actions groupées -->
    <div class="calendar-actions">
        <div class="actions-group">
            <h3><?php _e('Actions groupées', 'restaurant-booking'); ?></h3>
            <button type="button" class="button" onclick="blockWeekends()">
                <?php _e('Bloquer tous les week-ends', 'restaurant-booking'); ?>
            </button>
            <button type="button" class="button" onclick="openBulkBlockModal()">
                <?php _e('Blocage période', 'restaurant-booking'); ?>
            </button>
            <button type="button" class="button button-secondary" onclick="resetMonth()">
                <?php _e('Réinitialiser le mois', 'restaurant-booking'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Modal de blocage groupé -->
<div id="bulk-block-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3><?php _e('Blocage de période', 'restaurant-booking'); ?></h3>
        <form id="bulk-block-form">
            <div class="form-row">
                <label for="start_date"><?php _e('Date de début', 'restaurant-booking'); ?></label>
                <input type="date" id="start_date" name="start_date" required>
            </div>
            <div class="form-row">
                <label for="end_date"><?php _e('Date de fin', 'restaurant-booking'); ?></label>
                <input type="date" id="end_date" name="end_date" required>
            </div>
            <div class="form-row">
                <label for="block_reason"><?php _e('Raison du blocage', 'restaurant-booking'); ?></label>
                <input type="text" id="block_reason" name="block_reason" placeholder="Vacances, maintenance...">
            </div>
            <div class="modal-actions">
                <button type="button" class="button button-primary" onclick="applyBulkBlock()">
                    <?php _e('Appliquer', 'restaurant-booking'); ?>
                </button>
                <button type="button" class="button" onclick="closeBulkBlockModal()">
                    <?php _e('Annuler', 'restaurant-booking'); ?>
                </button>
            </div>
        </form>
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

.service-selector select {
    padding: 5px 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
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
    height: 80px;
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

.day-number {
    display: block;
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 5px;
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
    margin-top: 20px;
    padding: 15px;
    background: #fff;
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
}

/* Modal */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    max-width: 400px;
    width: 90%;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.modal-content h3 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #243127;
}

.form-row {
    margin-bottom: 15px;
}

.form-row label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.form-row input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.modal-actions {
    margin-top: 20px;
    text-align: right;
}

.modal-actions .button {
    margin-left: 10px;
}
</style>

<script>
function changeServiceType(serviceType) {
    var url = new URL(window.location);
    url.searchParams.set('service_type', serviceType);
    window.location.href = url.toString();
}

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

function blockWeekends() {
    // TODO: Implémenter le blocage des week-ends
    alert('Fonctionnalité en cours de développement');
}

function openBulkBlockModal() {
    document.getElementById('bulk-block-modal').style.display = 'flex';
}

function closeBulkBlockModal() {
    document.getElementById('bulk-block-modal').style.display = 'none';
}

function applyBulkBlock() {
    var startDate = document.getElementById('start_date').value;
    var endDate = document.getElementById('end_date').value;
    var reason = document.getElementById('block_reason').value;
    
    if (!startDate || !endDate) {
        alert('Veuillez saisir les dates de début et de fin');
        return;
    }
    
    // TODO: Implémenter la logique AJAX pour le blocage groupé
    alert('Période bloquée du ' + startDate + ' au ' + endDate + (reason ? ' (' + reason + ')' : ''));
    closeBulkBlockModal();
}

function resetMonth() {
    if (confirm('Êtes-vous sûr de vouloir réinitialiser toutes les disponibilités du mois ?')) {
        // TODO: Implémenter la réinitialisation
        alert('Fonctionnalité en cours de développement');
    }
}
</script>