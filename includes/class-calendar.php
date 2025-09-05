<?php
/**
 * Classe de gestion du calendrier
 *
 * @package RestaurantBooking
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Calendar
{
    /**
     * Instance unique
     */
    private static $instance = null;

    /**
     * Obtenir l'instance unique
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructeur
     */
    private function __construct()
    {
        add_action('init', array($this, 'init'));
    }

    /**
     * Initialisation
     */
    public function init()
    {
        // Rien pour le moment
    }

    /**
     * Vérifier la disponibilité d'une date
     */
    public static function is_date_available($date, $service_type = 'both')
    {
        global $wpdb;

        $availability = $wpdb->get_row($wpdb->prepare("
            SELECT is_available 
            FROM {$wpdb->prefix}restaurant_availability 
            WHERE date = %s 
            AND (service_type = %s OR service_type = 'both')
            ORDER BY service_type = %s DESC
            LIMIT 1
        ", $date, $service_type, $service_type));

        if ($availability) {
            return (bool) $availability->is_available;
        }

        // Par défaut, considérer comme disponible si pas d'entrée
        return true;
    }

    /**
     * Définir la disponibilité d'une date
     */
    public static function set_date_availability($date, $service_type, $is_available, $reason = '', $notes = '')
    {
        global $wpdb;

        $existing = $wpdb->get_row($wpdb->prepare("
            SELECT id FROM {$wpdb->prefix}restaurant_availability 
            WHERE date = %s AND service_type = %s
        ", $date, $service_type));

        $data = array(
            'date' => $date,
            'service_type' => $service_type,
            'is_available' => $is_available ? 1 : 0,
            'blocked_reason' => $reason,
            'notes' => $notes,
            'created_by' => get_current_user_id(),
            'updated_at' => current_time('mysql')
        );

        if ($existing) {
            // Mettre à jour
            $result = $wpdb->update(
                $wpdb->prefix . 'restaurant_availability',
                $data,
                array('id' => $existing->id),
                array('%s', '%s', '%d', '%s', '%s', '%d', '%s'),
                array('%d')
            );
        } else {
            // Insérer
            $data['created_at'] = current_time('mysql');
            $result = $wpdb->insert(
                $wpdb->prefix . 'restaurant_availability',
                $data,
                array('%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s')
            );
        }

        if ($result !== false) {
            RestaurantBooking_Logger::info("Disponibilité mise à jour", array(
                'date' => $date,
                'service_type' => $service_type,
                'is_available' => $is_available
            ));
            return true;
        }

        return false;
    }

    /**
     * Obtenir les disponibilités d'un mois
     */
    public static function get_month_availability($year, $month, $service_type = 'both')
    {
        global $wpdb;

        $start_date = sprintf('%04d-%02d-01', $year, $month);
        $end_date = date('Y-m-t', strtotime($start_date));

        $availabilities = $wpdb->get_results($wpdb->prepare("
            SELECT date, service_type, is_available, blocked_reason, notes
            FROM {$wpdb->prefix}restaurant_availability 
            WHERE date BETWEEN %s AND %s
            AND (service_type = %s OR service_type = 'both')
            ORDER BY date ASC
        ", $start_date, $end_date, $service_type), ARRAY_A);

        $result = array();
        foreach ($availabilities as $availability) {
            $result[$availability['date']] = $availability;
        }

        return $result;
    }

    /**
     * Bloquer une période
     */
    public static function block_period($start_date, $end_date, $service_type, $reason, $notes = '')
    {
        $current_date = $start_date;
        $blocked_count = 0;

        while ($current_date <= $end_date) {
            if (self::set_date_availability($current_date, $service_type, false, $reason, $notes)) {
                $blocked_count++;
            }
            $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
        }

        RestaurantBooking_Logger::info("Période bloquée", array(
            'start_date' => $start_date,
            'end_date' => $end_date,
            'service_type' => $service_type,
            'reason' => $reason,
            'blocked_days' => $blocked_count
        ));

        return $blocked_count;
    }

    /**
     * Débloquer une période
     */
    public static function unblock_period($start_date, $end_date, $service_type)
    {
        $current_date = $start_date;
        $unblocked_count = 0;

        while ($current_date <= $end_date) {
            if (self::set_date_availability($current_date, $service_type, true)) {
                $unblocked_count++;
            }
            $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
        }

        RestaurantBooking_Logger::info("Période débloquée", array(
            'start_date' => $start_date,
            'end_date' => $end_date,
            'service_type' => $service_type,
            'unblocked_days' => $unblocked_count
        ));

        return $unblocked_count;
    }

    /**
     * Obtenir les dates occupées par des événements confirmés
     */
    public static function get_booked_dates($service_type = 'both')
    {
        global $wpdb;

        $where_clause = '';
        $params = array();

        if ($service_type !== 'both') {
            $where_clause = 'AND service_type = %s';
            $params[] = $service_type;
        }

        $sql = "SELECT DISTINCT event_date 
                FROM {$wpdb->prefix}restaurant_quotes 
                WHERE status IN ('sent', 'confirmed')
                AND event_date >= CURDATE()
                $where_clause
                ORDER BY event_date ASC";

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        $dates = $wpdb->get_col($sql);

        return $dates;
    }

    /**
     * Synchroniser avec Google Calendar (placeholder)
     */
    public static function sync_google_calendar()
    {
        // TODO: Implémenter la synchronisation Google Calendar
        RestaurantBooking_Logger::info("Synchronisation Google Calendar (placeholder)");
        return true;
    }

    /**
     * Nettoyer les anciennes disponibilités
     */
    public static function cleanup_old_availability($days = 365)
    {
        global $wpdb;

        $cutoff_date = date('Y-m-d', strtotime("-$days days"));

        $deleted = $wpdb->query($wpdb->prepare("
            DELETE FROM {$wpdb->prefix}restaurant_availability 
            WHERE date < %s
        ", $cutoff_date));

        if ($deleted > 0) {
            RestaurantBooking_Logger::info("Nettoyage disponibilités: $deleted entrées supprimées");
        }

        return $deleted;
    }
}
