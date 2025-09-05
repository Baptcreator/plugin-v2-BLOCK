<?php
/**
 * Classe de gestion des emails
 *
 * @package RestaurantBooking
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Email
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
        // Configuration des emails
        add_filter('wp_mail_content_type', array($this, 'set_html_content_type'));
        
        // Hooks AJAX pour les tests d'email
        add_action('wp_ajax_restaurant_booking_test_email', array($this, 'ajax_test_email'));
        add_action('wp_ajax_restaurant_booking_send_quote_email', array($this, 'ajax_send_quote_email'));
        
        // Compatibilité WP Mail SMTP
        add_action('phpmailer_init', array($this, 'configure_phpmailer'));
    }

    /**
     * Définir le type de contenu HTML pour les emails
     */
    public function set_html_content_type()
    {
        return 'text/html';
    }

    /**
     * Envoyer un devis par email
     */
    public static function send_quote($quote_id)
    {
        $quote = RestaurantBooking_Quote::get($quote_id);
        if (!$quote) {
            return new WP_Error('quote_not_found', __('Devis introuvable', 'restaurant-booking'));
        }

        $customer = $quote['customer_data'];
        if (empty($customer['email'])) {
            return new WP_Error('no_email', __('Aucune adresse email client', 'restaurant-booking'));
        }

        // Obtenir les paramètres d'email
        $settings = RestaurantBooking_Settings::get_group('emails');
        
        $subject = $settings['email_quote_subject'] ?? __('Votre devis Restaurant Block', 'restaurant-booking');
        
        // Charger le template email
        ob_start();
        include RESTAURANT_BOOKING_PLUGIN_DIR . 'templates/emails/quote-email.php';
        $message = ob_get_clean();

        // Headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );

        // Envoyer l'email
        $sent = wp_mail($customer['email'], $subject, $message, $headers);

        if ($sent) {
            // Marquer le devis comme envoyé
            RestaurantBooking_Quote::mark_as_sent($quote_id);
            
            RestaurantBooking_Logger::info("Email de devis envoyé", array(
                'quote_id' => $quote_id,
                'quote_number' => $quote['quote_number'],
                'customer_email' => $customer['email']
            ));
            
            return true;
        } else {
            RestaurantBooking_Logger::error("Erreur envoi email devis", array(
                'quote_id' => $quote_id,
                'customer_email' => $customer['email']
            ));
            
            return new WP_Error('email_failed', __('Erreur lors de l\'envoi de l\'email', 'restaurant-booking'));
        }
    }

    /**
     * Envoyer une notification admin
     */
    public static function send_admin_notification($quote_id)
    {
        $quote = RestaurantBooking_Quote::get($quote_id);
        if (!$quote) {
            return false;
        }

        $admin_emails = RestaurantBooking_Settings::get('admin_notification_emails', array());
        if (empty($admin_emails)) {
            $admin_emails = array(get_option('admin_email'));
        }

        $subject = sprintf(__('Nouvelle demande de devis #%s', 'restaurant-booking'), $quote['quote_number']);
        
        $message = sprintf(
            __('Une nouvelle demande de devis a été soumise.\n\nNuméro: %s\nService: %s\nDate: %s\nConvives: %d\nMontant: %.2f€\n\nVoir le devis: %s', 'restaurant-booking'),
            $quote['quote_number'],
            $quote['service_type'] === 'restaurant' ? 'Restaurant' : 'Remorque',
            date_i18n('d/m/Y', strtotime($quote['event_date'])),
            $quote['guest_count'],
            $quote['total_price'],
            admin_url('admin.php?page=restaurant-booking-quotes&action=view&quote_id=' . $quote_id)
        );

        foreach ($admin_emails as $email) {
            wp_mail($email, $subject, $message);
        }

        return true;
    }

    /**
     * Tester la configuration email
     */
    public static function test_email_config($test_email = null)
    {
        if (!$test_email) {
            $test_email = get_option('admin_email');
        }

        $subject = __('Test email Restaurant Booking', 'restaurant-booking');
        $message = __('Ceci est un email de test depuis le plugin Restaurant Booking. Si vous recevez cet email, la configuration fonctionne correctement.', 'restaurant-booking');

        $sent = wp_mail($test_email, $subject, $message);

        if ($sent) {
            RestaurantBooking_Logger::info("Email de test envoyé avec succès", array('email' => $test_email));
            return true;
        } else {
            RestaurantBooking_Logger::error("Erreur envoi email de test", array('email' => $test_email));
            return false;
        }
    }

    /**
     * Configuration PHPMailer pour compatibilité WP Mail SMTP
     */
    public function configure_phpmailer($phpmailer)
    {
        // Vérifier si WP Mail SMTP est actif
        if (class_exists('WPMailSMTP\Core')) {
            // WP Mail SMTP gère déjà la configuration
            return;
        }

        // Configuration SMTP personnalisée si pas de WP Mail SMTP
        $smtp_settings = RestaurantBooking_Settings::get_group('smtp');
        
        if (!empty($smtp_settings['smtp_host']) && !empty($smtp_settings['smtp_username'])) {
            $phpmailer->isSMTP();
            $phpmailer->Host = $smtp_settings['smtp_host'];
            $phpmailer->SMTPAuth = true;
            $phpmailer->Username = $smtp_settings['smtp_username'];
            $phpmailer->Password = $smtp_settings['smtp_password'];
            $phpmailer->SMTPSecure = $smtp_settings['smtp_encryption'] ?? 'tls';
            $phpmailer->Port = $smtp_settings['smtp_port'] ?? 587;
            
            // Debug si activé
            if (defined('RESTAURANT_BOOKING_DEBUG') && RESTAURANT_BOOKING_DEBUG) {
                $phpmailer->SMTPDebug = 2;
                $phpmailer->Debugoutput = function($str, $level) {
                    RestaurantBooking_Logger::debug("SMTP Debug: " . $str);
                };
            }
        }
    }

    /**
     * AJAX : Test d'envoi d'email
     */
    public function ajax_test_email()
    {
        // Vérifier les permissions
        if (!current_user_can('manage_restaurant_settings')) {
            wp_send_json_error(__('Permissions insuffisantes', 'restaurant-booking'));
        }

        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['nonce'], 'restaurant_booking_test_email')) {
            wp_send_json_error(__('Token de sécurité invalide', 'restaurant-booking'));
        }

        $test_email = sanitize_email($_POST['test_email'] ?? get_option('admin_email'));
        
        if (!is_email($test_email)) {
            wp_send_json_error(__('Adresse email invalide', 'restaurant-booking'));
        }

        $result = self::test_email_config($test_email);
        
        if ($result) {
            wp_send_json_success(__('Email de test envoyé avec succès ! Vérifiez votre boîte de réception.', 'restaurant-booking'));
        } else {
            wp_send_json_error(__('Erreur lors de l\'envoi de l\'email de test. Vérifiez vos paramètres SMTP.', 'restaurant-booking'));
        }
    }

    /**
     * AJAX : Envoi d'un devis par email
     */
    public function ajax_send_quote_email()
    {
        // Vérifier les permissions
        if (!current_user_can('manage_restaurant_quotes')) {
            wp_send_json_error(__('Permissions insuffisantes', 'restaurant-booking'));
        }

        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['nonce'], 'restaurant_booking_send_quote')) {
            wp_send_json_error(__('Token de sécurité invalide', 'restaurant-booking'));
        }

        $quote_id = (int) $_POST['quote_id'];
        
        if (!$quote_id) {
            wp_send_json_error(__('ID de devis manquant', 'restaurant-booking'));
        }

        $result = self::send_quote($quote_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(__('Devis envoyé par email avec succès !', 'restaurant-booking'));
        }
    }

    /**
     * Détecter le plugin SMTP actif
     */
    public static function get_smtp_plugin_status()
    {
        $status = array(
            'plugin' => 'none',
            'active' => false,
            'configured' => false
        );

        // Vérifier WP Mail SMTP
        if (class_exists('WPMailSMTP\Core')) {
            $status['plugin'] = 'WP Mail SMTP';
            $status['active'] = true;
            
            // Vérifier si configuré
            $wp_mail_smtp_options = get_option('wp_mail_smtp');
            $status['configured'] = !empty($wp_mail_smtp_options['mail']['mailer']) && $wp_mail_smtp_options['mail']['mailer'] !== 'mail';
        }
        // Vérifier Easy WP SMTP
        elseif (class_exists('EasyWPSMTP')) {
            $status['plugin'] = 'Easy WP SMTP';
            $status['active'] = true;
            $easy_smtp_options = get_option('swpsmtp_options');
            $status['configured'] = !empty($easy_smtp_options['smtp_settings']['host']);
        }
        // Vérifier Post SMTP
        elseif (class_exists('PostmanOptions')) {
            $status['plugin'] = 'Post SMTP';
            $status['active'] = true;
            $postman_options = PostmanOptions::getInstance();
            $status['configured'] = $postman_options->isConfigured();
        }
        // Configuration SMTP personnalisée
        else {
            $smtp_settings = RestaurantBooking_Settings::get_group('smtp');
            if (!empty($smtp_settings['smtp_host'])) {
                $status['plugin'] = 'Configuration personnalisée';
                $status['active'] = true;
                $status['configured'] = !empty($smtp_settings['smtp_username']);
            }
        }

        return $status;
    }

    /**
     * Obtenir les statistiques d'envoi d'emails
     */
    public static function get_email_stats()
    {
        global $wpdb;
        
        $stats = array();
        
        // Emails envoyés ce mois
        $stats['month'] = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}restaurant_logs 
            WHERE action = 'email_sent' 
            AND created_at >= %s
        ", date('Y-m-01 00:00:00')));
        
        // Emails envoyés aujourd'hui
        $stats['today'] = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}restaurant_logs 
            WHERE action = 'email_sent' 
            AND DATE(created_at) = %s
        ", date('Y-m-d')));
        
        // Erreurs d'email ce mois
        $stats['errors'] = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}restaurant_logs 
            WHERE action = 'email_error' 
            AND created_at >= %s
        ", date('Y-m-01 00:00:00')));
        
        return $stats;
    }
}
