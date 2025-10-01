<?php
/**
 * Classe de gestion des logs
 *
 * @package RestaurantBooking
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Logger
{
    /**
     * Instance unique
     */
    private static $instance = null;

    /**
     * Niveaux de log
     */
    const LEVEL_ERROR = 'error';
    const LEVEL_WARNING = 'warning';
    const LEVEL_INFO = 'info';
    const LEVEL_DEBUG = 'debug';

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
        // Hook pour nettoyer les anciens logs
        add_action('restaurant_booking_cleanup', array($this, 'cleanup_old_logs'));
    }

    /**
     * Enregistrer un log
     */
    public static function log($message, $level = self::LEVEL_INFO, $context = null)
    {
        global $wpdb;

        // Ne pas logger en mode debug si pas activé
        if ($level === self::LEVEL_DEBUG && !(defined('RESTAURANT_BOOKING_DEBUG') && RESTAURANT_BOOKING_DEBUG)) {
            return;
        }

        // Préparer le contexte
        $context_json = null;
        if ($context !== null) {
            $context_json = json_encode($context, JSON_UNESCAPED_UNICODE);
        }

        // Obtenir les informations utilisateur
        $user_id = get_current_user_id();
        $ip_address = self::get_client_ip();

        // Insérer en base de données
        $result = $wpdb->insert(
            $wpdb->prefix . 'restaurant_logs',
            array(
                'level' => $level,
                'message' => $message,
                'context' => $context_json,
                'user_id' => $user_id ?: null,
                'ip_address' => $ip_address,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%d', '%s', '%s')
        );

        // En mode debug, également écrire dans le log WordPress
        if (defined('RESTAURANT_BOOKING_DEBUG') && RESTAURANT_BOOKING_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            $log_message = sprintf(
                '[Restaurant Booking] [%s] %s',
                strtoupper($level),
                $message
            );
            
            if ($context) {
                $log_message .= ' | Context: ' . json_encode($context);
            }
            
            error_log($log_message);
        }

        return $result !== false;
    }

    /**
     * Log d'erreur
     */
    public static function error($message, $context = null)
    {
        return self::log($message, self::LEVEL_ERROR, $context);
    }

    /**
     * Log d'avertissement
     */
    public static function warning($message, $context = null)
    {
        return self::log($message, self::LEVEL_WARNING, $context);
    }

    /**
     * Log d'information
     */
    public static function info($message, $context = null)
    {
        return self::log($message, self::LEVEL_INFO, $context);
    }

    /**
     * Log de debug
     */
    public static function debug($message, $context = null)
    {
        return self::log($message, self::LEVEL_DEBUG, $context);
    }

    /**
     * Obtenir les logs récents
     */
    public static function get_recent_logs($limit = 50, $level = null)
    {
        global $wpdb;

        $where_clause = '';
        $params = array();

        if ($level) {
            $where_clause = 'WHERE level = %s';
            $params[] = $level;
        }

        $sql = "SELECT * FROM {$wpdb->prefix}restaurant_logs 
                $where_clause 
                ORDER BY created_at DESC 
                LIMIT %d";
        
        $params[] = $limit;

        return $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
    }

    /**
     * Obtenir les statistiques des logs
     */
    public static function get_log_stats($days = 7)
    {
        global $wpdb;

        $stats = $wpdb->get_results($wpdb->prepare("
            SELECT 
                level,
                COUNT(*) as count,
                DATE(created_at) as date
            FROM {$wpdb->prefix}restaurant_logs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY level, DATE(created_at)
            ORDER BY date DESC, level
        ", $days), ARRAY_A);

        // Organiser par niveau
        $result = array(
            'error' => array(),
            'warning' => array(),
            'info' => array(),
            'debug' => array(),
            'total' => 0
        );

        foreach ($stats as $stat) {
            $result[$stat['level']][] = array(
                'date' => $stat['date'],
                'count' => (int) $stat['count']
            );
            $result['total'] += (int) $stat['count'];
        }

        return $result;
    }

    /**
     * Nettoyer les anciens logs
     */
    public function cleanup_old_logs($days = 90)
    {
        global $wpdb;

        $deleted = $wpdb->query($wpdb->prepare("
            DELETE FROM {$wpdb->prefix}restaurant_logs 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)
        ", $days));

        if ($deleted > 0) {
            self::info("Nettoyage automatique: $deleted logs supprimés");
        }

        return $deleted;
    }

    /**
     * Vider tous les logs
     */
    public static function clear_all_logs()
    {
        global $wpdb;

        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}restaurant_logs");
        $result = $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}restaurant_logs");

        if ($result !== false) {
            // Ne pas utiliser self::log ici pour éviter la récursion
            return $count;
        }

        return false;
    }

    /**
     * Exporter les logs
     */
    public static function export_logs($start_date = null, $end_date = null, $level = null)
    {
        global $wpdb;

        $where_conditions = array();
        $params = array();

        if ($start_date) {
            $where_conditions[] = 'created_at >= %s';
            $params[] = $start_date;
        }

        if ($end_date) {
            $where_conditions[] = 'created_at <= %s';
            $params[] = $end_date;
        }

        if ($level) {
            $where_conditions[] = 'level = %s';
            $params[] = $level;
        }

        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }

        $sql = "SELECT 
                    level,
                    message,
                    context,
                    user_id,
                    ip_address,
                    created_at
                FROM {$wpdb->prefix}restaurant_logs 
                $where_clause 
                ORDER BY created_at DESC";

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        $logs = $wpdb->get_results($sql, ARRAY_A);

        // Ajouter les informations utilisateur
        foreach ($logs as &$log) {
            if ($log['user_id']) {
                $user = get_userdata($log['user_id']);
                $log['user_login'] = $user ? $user->user_login : 'Utilisateur supprimé';
            }
            
            if ($log['context']) {
                $log['context'] = json_decode($log['context'], true);
            }
        }

        return array(
            'export_date' => current_time('mysql'),
            'total_logs' => count($logs),
            'filters' => array(
                'start_date' => $start_date,
                'end_date' => $end_date,
                'level' => $level
            ),
            'logs' => $logs
        );
    }

    /**
     * Obtenir l'adresse IP du client
     */
    private static function get_client_ip()
    {
        $ip_keys = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );

        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
    }

    /**
     * Formater un log pour l'affichage
     */
    public static function format_log_for_display($log)
    {
        $level_colors = array(
            'error' => '#dc3545',
            'warning' => '#ffc107', 
            'info' => '#17a2b8',
            'debug' => '#6c757d'
        );

        $level_icons = array(
            'error' => '❌',
            'warning' => '⚠️',
            'info' => 'ℹ️',
            'debug' => '🔧'
        );

        return array(
            'id' => $log['id'],
            'level' => $log['level'],
            'level_color' => isset($level_colors[$log['level']]) ? $level_colors[$log['level']] : '#000',
            'level_icon' => isset($level_icons[$log['level']]) ? $level_icons[$log['level']] : '📝',
            'message' => $log['message'],
            'context' => $log['context'] ? json_decode($log['context'], true) : null,
            'user_id' => $log['user_id'],
            'ip_address' => $log['ip_address'],
            'created_at' => $log['created_at'],
            'created_at_formatted' => date_i18n('d/m/Y H:i:s', strtotime($log['created_at']))
        );
    }

    /**
     * Obtenir la taille totale des logs
     */
    public static function get_logs_size()
    {
        global $wpdb;

        $size_info = $wpdb->get_row("
            SELECT 
                COUNT(*) as total_logs,
                ROUND(
                    (DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2
                ) as size_mb
            FROM information_schema.tables 
            WHERE table_schema = DATABASE() 
            AND table_name = '{$wpdb->prefix}restaurant_logs'
        ");

        if (!$size_info) {
            return array(
                'total_logs' => 0,
                'size_mb' => 0
            );
        }

        return array(
            'total_logs' => (int) $size_info->total_logs,
            'size_mb' => (float) $size_info->size_mb
        );
    }
}
