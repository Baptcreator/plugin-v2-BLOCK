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
        
        // Hook AJAX pour le téléchargement de devis (accessible aux non-connectés)
        add_action('wp_ajax_restaurant_booking_download_quote', array($this, 'ajax_download_quote'));
        add_action('wp_ajax_nopriv_restaurant_booking_download_quote', array($this, 'ajax_download_quote'));
        
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
     * Envoyer un devis par email avec PDF en pièce jointe (méthode pour AJAX V3)
     */
    public static function send_quote_email($quote_id)
    {
        global $wpdb;
        
        // Récupérer les données du devis
        $table_name = $wpdb->prefix . 'restaurant_quotes';
        $quote = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d",
            $quote_id
        ));
        
        if (!$quote) {
            if (class_exists('RestaurantBooking_Logger')) {
                RestaurantBooking_Logger::error("Devis non trouvé pour l'ID: {$quote_id}");
            }
            return false;
        }
        
        // Décoder les données JSON
        $customer_data = json_decode($quote->customer_data, true);
        $price_data = json_decode($quote->price_breakdown, true);
        
        if (!$customer_data || !$customer_data['email']) {
            if (class_exists('RestaurantBooking_Logger')) {
                RestaurantBooking_Logger::error("Données client manquantes pour le devis ID: {$quote_id}");
            }
            return false;
        }
        
        // Générer le PDF du devis avec gestion d'erreur robuste
        $pdf_path = null;
        if (class_exists('RestaurantBooking_PDF')) {
            try {
                $pdf_result = RestaurantBooking_PDF::generate_quote_pdf($quote_id);
                if (!is_wp_error($pdf_result) && file_exists($pdf_result)) {
                    $pdf_path = $pdf_result;
                } else {
                    $error_msg = is_wp_error($pdf_result) ? $pdf_result->get_error_message() : 'Fichier non créé';
                    error_log('Restaurant Booking: Erreur génération PDF - ' . $error_msg);
                    
                    if (class_exists('RestaurantBooking_Logger')) {
                        RestaurantBooking_Logger::warning("PDF non généré, envoi email sans pièce jointe", [
                            'quote_id' => $quote_id,
                            'error' => $error_msg
                        ]);
                    }
                }
            } catch (Exception $e) {
                error_log('Restaurant Booking: Exception PDF - ' . $e->getMessage());
                
                if (class_exists('RestaurantBooking_Logger')) {
                    RestaurantBooking_Logger::warning("Exception PDF, envoi email sans pièce jointe", [
                        'quote_id' => $quote_id,
                        'exception' => $e->getMessage()
                    ]);
                }
            }
        } else {
            error_log('Restaurant Booking: Classe RestaurantBooking_PDF non trouvée');
        }
        
        // Préparer l'email
        $to = $customer_data['email'];
        $subject = 'Votre devis Block - ' . ($quote->service_type === 'restaurant' ? 'Privatisation du restaurant' : 'Privatisation de la remorque');
        
        // Préparer le lien de téléchargement AVANT de générer le template email
        $attachments = [];
        $download_link = '';
        
        // Si c'est un fichier HTML, créer un lien direct au lieu de l'attacher
        if ($pdf_path && file_exists($pdf_path)) {
            if (pathinfo($pdf_path, PATHINFO_EXTENSION) === 'html') {
                // Créer un lien direct vers le fichier HTML (accessible publiquement)
                $download_link = self::create_public_download_link($pdf_path);
            } else {
                // Pour les vrais PDF, on peut les attacher normalement
                $attachments[] = $pdf_path;
            }
        }
        
        // Préparer les données pour le template email
        $selected_products = json_decode($quote->selected_products, true);
        $quote_array = [
            'quote_number' => $quote->quote_number,
            'service_type' => $quote->service_type,
            'event_date' => $quote->event_date,
            'event_duration' => $quote->event_duration,
            'guest_count' => $quote->guest_count,
            'postal_code' => $quote->postal_code,
            'total_price' => $quote->total_price,
            'price_breakdown' => $price_data,
            'selected_products' => $selected_products
        ];
        
        // Message HTML avec le lien de téléchargement (maintenant correctement défini)
        $message = self::get_quote_email_template($quote_array, $customer_data, $price_data, $download_link);
        
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: Block & Co <noreply@block-streetfood.fr>'
        ];
        
        // Envoyer l'email avec gestion d'erreur améliorée
        $sent = wp_mail($to, $subject, $message, $headers, $attachments);
        
        // Log détaillé du résultat
        if (!$sent) {
            global $phpmailer;
            $error_info = '';
            if (isset($phpmailer) && !empty($phpmailer->ErrorInfo)) {
                $error_info = $phpmailer->ErrorInfo;
            }
            
            error_log("Restaurant Booking: Échec wp_mail - Email: {$to}, Erreur: {$error_info}");
            
            if (class_exists('RestaurantBooking_Logger')) {
                RestaurantBooking_Logger::error("Échec envoi wp_mail", [
                    'email' => $to,
                    'subject' => $subject,
                    'error' => $error_info,
                    'headers' => $headers,
                    'attachments_count' => count($attachments),
                    'quote_id' => $quote_id
                ]);
            }
        }
        
        if ($sent) {
            // Mettre à jour la date d'envoi
            $wpdb->update(
                $table_name,
                ['sent_at' => current_time('mysql'), 'status' => 'sent'],
                ['id' => $quote_id]
            );
            
            if (class_exists('RestaurantBooking_Logger')) {
                RestaurantBooking_Logger::info("Email client envoyé pour le devis: {$quote->quote_number}", [
                    'quote_id' => $quote_id,
                    'email' => $to,
                    'pdf_attached' => !empty($attachments),
                    'download_link_included' => !empty($download_link),
                    'download_link' => $download_link
                ]);
            }
        } else {
            if (class_exists('RestaurantBooking_Logger')) {
                RestaurantBooking_Logger::error("Échec envoi email client pour devis: {$quote->quote_number}", [
                    'quote_id' => $quote_id,
                    'email' => $to,
                    'pdf_path' => $pdf_path,
                    'download_link' => $download_link
                ]);
            }
        }
        
        return $sent;
    }
    
    /**
     * Diagnostiquer les problèmes d'email
     */
    public static function diagnose_email_issues($quote_id)
    {
        $issues = [];
        
        // Vérifier si wp_mail fonctionne
        $test_result = wp_mail('test@example.com', 'Test', 'Test message');
        if (!$test_result) {
            $issues[] = 'wp_mail() ne fonctionne pas correctement';
        }
        
        // Vérifier la configuration SMTP
        if (!function_exists('wp_mail')) {
            $issues[] = 'Fonction wp_mail non disponible';
        }
        
        // Vérifier les plugins SMTP
        $smtp_plugins = [];
        if (class_exists('WPMailSMTP\\Core')) {
            $smtp_plugins[] = 'WP Mail SMTP';
        }
        if (class_exists('EasyWPSMTP')) {
            $smtp_plugins[] = 'Easy WP SMTP';
        }
        if (function_exists('wp_mail_smtp')) {
            $smtp_plugins[] = 'WP Mail SMTP (fonction)';
        }
        
        // Vérifier le devis
        global $wpdb;
        $quote = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}restaurant_quotes WHERE id = %d",
            $quote_id
        ));
        
        if (!$quote) {
            $issues[] = 'Devis introuvable';
        } else {
            $customer_data = json_decode($quote->customer_data, true);
            if (!$customer_data || !isset($customer_data['email'])) {
                $issues[] = 'Email client manquant ou invalide';
            } elseif (!filter_var($customer_data['email'], FILTER_VALIDATE_EMAIL)) {
                $issues[] = 'Format email client invalide: ' . $customer_data['email'];
            }
        }
        
        // Vérifier la génération PDF
        if (class_exists('RestaurantBooking_PDF')) {
            $pdf_result = RestaurantBooking_PDF::generate_quote_pdf($quote_id);
            if (is_wp_error($pdf_result)) {
                $issues[] = 'Erreur génération PDF: ' . $pdf_result->get_error_message();
            } elseif (!file_exists($pdf_result)) {
                $issues[] = 'Fichier PDF non créé: ' . $pdf_result;
            }
        } else {
            $issues[] = 'Classe RestaurantBooking_PDF non disponible';
        }
        
        return [
            'issues' => $issues,
            'smtp_plugins' => $smtp_plugins,
            'wp_mail_available' => function_exists('wp_mail'),
            'quote_exists' => !empty($quote)
        ];
    }
    
    /**
     * Template HTML pour l'email de devis
     */
    private static function get_quote_email_template($quote, $customer_data, $price_data, $download_link = '')
    {
        $service_type = is_array($quote) ? $quote['service_type'] : $quote->service_type;
        $service_name = $service_type === 'restaurant' ? 'Privatisation du restaurant' : 'Privatisation de la remorque Block';
        
        $message = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #243127; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .quote-details { background: white; padding: 15px; margin: 15px 0; border-radius: 5px; }
                .footer { background: #243127; color: white; padding: 15px; text-align: center; font-size: 12px; }
                .price { font-size: 18px; font-weight: bold; color: #243127; }
                .highlight { background: #fff3cd; padding: 10px; border-left: 4px solid #ffc107; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🍽️ Block & Co</h1>
                    <p>Votre devis de privatisation</p>
                </div>
                
                <div class="content">
                    <h2>Bonjour ' . esc_html($customer_data['firstname']) . ' ' . esc_html($customer_data['name']) . ',</h2>
                    
                    <p>Nous vous remercions pour votre demande de devis. Vous trouverez ci-dessous le récapitulatif de votre demande.</p>
                    
                    <div class="quote-details">
                        <h3>📋 Détails de votre réservation</h3>
                        <p><strong>Numéro de devis :</strong> ' . esc_html(is_array($quote) ? $quote['quote_number'] : $quote->quote_number) . '</p>
                        <p><strong>Service :</strong> ' . esc_html($service_name) . '</p>
                        <p><strong>Date :</strong> ' . date('d/m/Y', strtotime(is_array($quote) ? $quote['event_date'] : $quote->event_date)) . '</p>
                        <p><strong>Nombre de convives :</strong> ' . esc_html(is_array($quote) ? $quote['guest_count'] : $quote->guest_count) . ' personnes</p>
                        <p><strong>Durée :</strong> ' . esc_html(is_array($quote) ? $quote['event_duration'] : $quote->event_duration) . 'H</p>';
                        
        $postal_code = is_array($quote) ? $quote['postal_code'] : $quote->postal_code;
        $distance_km = is_array($quote) ? ($quote['distance_km'] ?? 0) : ($quote->distance_km ?? 0);
        $total_price = is_array($quote) ? $quote['total_price'] : $quote->total_price;
        
        if ($postal_code) {
            $message .= '<p><strong>Code postal :</strong> ' . esc_html($postal_code) . '</p>';
        }
        
        if ($distance_km > 0) {
            $message .= '<p><strong>Distance :</strong> ' . esc_html($distance_km) . ' km</p>';
        }
        
        $message .= '
                        <p class="price"><strong>💰 Prix total estimé : ' . number_format($total_price, 2, ',', ' ') . ' €</strong></p>
                    </div>';
                    
        if (!empty($customer_data['message'])) {
            $message .= '
                    <div class="quote-details">
                        <h3>💬 Votre message</h3>
                        <p>' . nl2br(esc_html($customer_data['message'])) . '</p>
                    </div>';
        }
        
        // Ajouter le lien de téléchargement si disponible
        if (!empty($download_link)) {
            $message .= '
                    <div class="quote-details" style="text-align: center; background: #e3f2fd; padding: 20px; margin: 20px 0;">
                        <h3>📄 Votre devis détaillé</h3>
                        <p>Cliquez sur le bouton ci-dessous pour télécharger et imprimer votre devis complet :</p>
                        <a href="' . esc_url($download_link) . '" 
                           style="display: inline-block; background: #243127; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 10px 0;"
                           target="_blank">
                           📥 Télécharger mon devis
                        </a>
                        <p><small>Le lien s\'ouvrira dans votre navigateur. Vous pourrez ensuite l\'imprimer ou l\'enregistrer en PDF.</small></p>
                    </div>';
        }
        
        $message .= '
                    <div class="highlight">
                        <p><strong>⏰ Prochaines étapes :</strong></p>
                        <p>Notre équipe va étudier votre demande et vous recontacter dans les plus brefs délais pour finaliser votre réservation et confirmer tous les détails.</p>
                    </div>
                    
                    <p>Si vous avez des questions, n\'hésitez pas à nous contacter.</p>
                    
                    <p>Cordialement,<br>
                    L\'équipe Block & Co</p>
                </div>
                
                <div class="footer">
                    <p>Block & Co - Restaurant & Remorque</p>
                    <p>Ceci est un email automatique, merci de ne pas y répondre directement.</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $message;
    }
    
    /**
     * Créer un lien direct vers le fichier HTML (accessible publiquement)
     */
    private static function create_public_download_link($file_path)
    {
        // Convertir le chemin du fichier en URL publique
        $upload_dir = wp_upload_dir();
        $file_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $file_path);
        
        return $file_url;
    }
    
    /**
     * Créer un lien de téléchargement sécurisé pour le devis HTML (méthode de backup)
     */
    private static function create_secure_download_link($quote_id, $file_path)
    {
        // Créer un token sécurisé pour le téléchargement
        $token = wp_create_nonce('download_quote_' . $quote_id);
        
        // Stocker temporairement le chemin du fichier avec le token
        set_transient('quote_download_' . $token, $file_path, 24 * HOUR_IN_SECONDS); // Expire après 24h
        
        // Créer l'URL de téléchargement
        $download_url = add_query_arg([
            'action' => 'restaurant_booking_download_quote',
            'quote_id' => $quote_id,
            'token' => $token
        ], admin_url('admin-ajax.php'));
        
        return $download_url;
    }

    /**
     * Envoyer une notification admin
     */
    public static function send_admin_notification($quote_id)
    {
        // Vérifier si les notifications admin sont activées
        $email_settings = get_option('restaurant_booking_email_settings', array());
        $notifications_enabled = isset($email_settings['admin_notification_enabled']) ? $email_settings['admin_notification_enabled'] : '1';
        
        if ($notifications_enabled !== '1') {
            RestaurantBooking_Logger::info("Notifications admin désactivées", array('quote_id' => $quote_id));
            return false;
        }

        $quote = RestaurantBooking_Quote::get($quote_id);
        if (!$quote) {
            RestaurantBooking_Logger::error("Devis introuvable pour notification admin", array('quote_id' => $quote_id));
            return false;
        }

        // Récupérer les emails admin depuis les nouveaux paramètres
        $admin_emails = isset($email_settings['admin_notification_emails']) ? $email_settings['admin_notification_emails'] : array();
        
        // Fallback vers l'ancien système
        if (empty($admin_emails)) {
            $admin_emails = RestaurantBooking_Settings::get('admin_notification_emails', array());
        }
        
        // Fallback final vers l'email admin WordPress
        if (empty($admin_emails)) {
            $admin_emails = array(get_option('admin_email'));
        }

        // Filtrer les emails vides
        $admin_emails = array_filter($admin_emails);
        
        if (empty($admin_emails)) {
            RestaurantBooking_Logger::error("Aucun email admin configuré pour les notifications", array('quote_id' => $quote_id));
            return false;
        }

        // Sujet personnalisable
        $subject = isset($email_settings['admin_notification_subject']) ? 
                   $email_settings['admin_notification_subject'] : 
                   'Nouvelle demande de devis - Block & Co';
        
        // Remplacer les variables dans le sujet
        $subject = str_replace('{quote_number}', $quote['quote_number'] ?? $quote_id, $subject);
        
        // Récupérer les données client
        $customer_data = $quote['customer_data'] ?? array();
        $customer_name = '';
        if (!empty($customer_data['name'])) {
            $customer_name = $customer_data['name'];
        } elseif (!empty($quote['client_firstname']) && !empty($quote['client_name'])) {
            $customer_name = $quote['client_firstname'] . ' ' . $quote['client_name'];
        }

        // Récupérer le message du client
        $client_message = '';
        if (!empty($customer_data['message'])) {
            $client_message = $customer_data['message'];
        } elseif (!empty($quote['client_message'])) {
            $client_message = $quote['client_message'];
        }

        // Message détaillé
        $message_parts = [
            __("🎉 Nouvelle demande de devis reçue !\n\n" .
               "📋 Détails du devis :\n" .
               "• Numéro : %s\n" .
               "• Service : %s\n" .
               "• Client : %s\n" .
               "• Email : %s\n" .
               "• Téléphone : %s\n" .
               "• Date événement : %s\n" .
               "• Nombre de convives : %d\n" .
               "• Montant total : %.2f€", 
               'restaurant-booking'),
            $quote['quote_number'] ?? "#{$quote_id}",
            $quote['service_type'] === 'restaurant' ? '🍽️ Restaurant' : '🚚 Remorque Block',
            $customer_name ?: 'Non renseigné',
            $customer_data['email'] ?? $quote['client_email'] ?? 'Non renseigné',
            $customer_data['phone'] ?? $quote['client_phone'] ?? 'Non renseigné',
            $quote['event_date'] ? date_i18n('d/m/Y', strtotime($quote['event_date'])) : 'Non renseignée',
            $quote['guest_count'] ?? 0,
            $quote['total_price'] ?? 0
        ];

        $message = sprintf($message_parts[0], ...array_slice($message_parts, 1));

        // Ajouter le message du client s'il existe
        if (!empty($client_message)) {
            $message .= sprintf(
                __("\n\n💬 Message du client :\n\"%s\"", 'restaurant-booking'),
                $client_message
            );
        }

        // Ajouter le lien et la signature
        $message .= sprintf(
            __("\n\n🔗 Voir le devis complet :\n%s\n\n" .
               "📧 Cet email a été envoyé automatiquement depuis votre site Block & Co.", 
               'restaurant-booking'),
            admin_url('admin.php?page=restaurant-booking-quotes&action=view&quote_id=' . $quote_id)
        );

        // Headers pour un meilleur affichage
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . ($email_settings['sender_name'] ?? 'Block & Co') . ' <' . ($email_settings['sender_email'] ?? get_option('admin_email')) . '>'
        );

        $success_count = 0;
        foreach ($admin_emails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $sent = wp_mail($email, $subject, $message, $headers);
                if ($sent) {
                    $success_count++;
                    RestaurantBooking_Logger::info("Notification admin envoyée", array(
                        'quote_id' => $quote_id,
                        'quote_number' => $quote['quote_number'] ?? $quote_id,
                        'admin_email' => $email
                    ));
                } else {
                    RestaurantBooking_Logger::error("Erreur envoi notification admin", array(
                        'quote_id' => $quote_id,
                        'admin_email' => $email
                    ));
                }
            } else {
                RestaurantBooking_Logger::error("Email admin invalide", array(
                    'quote_id' => $quote_id,
                    'invalid_email' => $email
                ));
            }
        }

        return $success_count > 0;
    }
    
    /**
     * AJAX : Télécharger un devis HTML
     */
    public function ajax_download_quote()
    {
        // Désactiver les erreurs pour éviter qu'elles polluent l'affichage HTML
        error_reporting(E_ERROR | E_PARSE);
        
        $quote_id = (int) $_GET['quote_id'];
        $token = sanitize_text_field($_GET['token']);
        
        // Vérifier le token de sécurité
        if (!wp_verify_nonce($token, 'download_quote_' . $quote_id)) {
            wp_die(__('Lien de téléchargement invalide ou expiré', 'restaurant-booking'));
        }
        
        // Récupérer le chemin du fichier depuis le transient
        $file_path = get_transient('quote_download_' . $token);
        
        if (!$file_path || !file_exists($file_path)) {
            wp_die(__('Fichier de devis introuvable ou expiré', 'restaurant-booking'));
        }
        
        // Lire le contenu HTML
        $html_content = file_get_contents($file_path);
        
        if ($html_content === false) {
            wp_die(__('Erreur lors de la lecture du fichier de devis', 'restaurant-booking'));
        }
        
        // Nettoyer le contenu HTML des erreurs potentielles
        $html_content = $this->clean_html_output($html_content);
        
        // Envoyer les headers appropriés
        header('Content-Type: text/html; charset=UTF-8');
        header('Content-Disposition: inline; filename="devis-block-' . $quote_id . '.html"');
        
        // Afficher le contenu HTML
        echo $html_content;
        exit;
    }
    
    /**
     * Nettoyer le contenu HTML des erreurs et warnings
     */
    private function clean_html_output($html_content)
    {
        // Supprimer les erreurs PHP qui pourraient être dans le HTML
        $html_content = preg_replace('/\[.*?\] PHP (Notice|Warning|Deprecated):.*?\n/', '', $html_content);
        
        // Supprimer les caractères de contrôle indésirables
        $html_content = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $html_content);
        
        // S'assurer que le HTML commence bien par <!DOCTYPE
        if (!preg_match('/^\s*<!DOCTYPE/i', $html_content)) {
            // Si le HTML ne commence pas correctement, on le reconstruit
            if (preg_match('/<body[^>]*>(.*?)<\/body>/s', $html_content, $matches)) {
                $body_content = $matches[1];
                $html_content = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Devis Block & Co</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { border-bottom: 3px solid #FFB404; padding-bottom: 20px; margin-bottom: 30px; }
        .products-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .products-table th, .products-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .products-table th { background: #f8f9fa; font-weight: bold; }
        .total-row { background: #243127; color: white; font-weight: bold; }
    </style>
</head>
<body>' . $body_content . '</body>
</html>';
            }
        }
        
        return $html_content;
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
