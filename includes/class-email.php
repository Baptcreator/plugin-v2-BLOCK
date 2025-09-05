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
}
