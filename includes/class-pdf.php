<?php
/**
 * Classe de gestion des PDF
 *
 * @package RestaurantBooking
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_PDF
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
        // Rien pour le moment
    }

    /**
     * Générer un PDF de devis
     */
    public static function generate_quote_pdf($quote_id)
    {
        $quote = RestaurantBooking_Quote::get($quote_id);
        if (!$quote) {
            return new WP_Error('quote_not_found', __('Devis introuvable', 'restaurant-booking'));
        }

        try {
            // Pour l'instant, on génère un HTML qui peut être converti en PDF
            $customer = $quote['customer_data'];
            $settings = RestaurantBooking_Settings::get_group('emails');
            
            // Charger le template PDF
            ob_start();
            include RESTAURANT_BOOKING_PLUGIN_DIR . 'templates/pdf/quote-pdf.php';
            $html_content = ob_get_clean();

            // Créer le répertoire de sortie si nécessaire
            $upload_dir = wp_upload_dir();
            $pdf_dir = $upload_dir['basedir'] . '/restaurant-booking/pdf/';
            
            if (!file_exists($pdf_dir)) {
                wp_mkdir_p($pdf_dir);
            }

            // Nom du fichier PDF
            $pdf_filename = 'devis-' . $quote['quote_number'] . '.html';
            $pdf_path = $pdf_dir . $pdf_filename;

            // Sauvegarder le HTML (en attendant une vraie librairie PDF)
            file_put_contents($pdf_path, $html_content);

            RestaurantBooking_Logger::info("PDF généré", array(
                'quote_id' => $quote_id,
                'quote_number' => $quote['quote_number'],
                'file_path' => $pdf_path
            ));

            return $pdf_path;

        } catch (Exception $e) {
            RestaurantBooking_Logger::error("Erreur génération PDF", array(
                'quote_id' => $quote_id,
                'error' => $e->getMessage()
            ));
            
            return new WP_Error('pdf_generation_failed', $e->getMessage());
        }
    }

    /**
     * Obtenir l'URL de téléchargement d'un PDF
     */
    public static function get_pdf_download_url($quote_id)
    {
        $quote = RestaurantBooking_Quote::get($quote_id);
        if (!$quote) {
            return false;
        }

        $upload_dir = wp_upload_dir();
        $pdf_filename = 'devis-' . $quote['quote_number'] . '.html';
        $pdf_url = $upload_dir['baseurl'] . '/restaurant-booking/pdf/' . $pdf_filename;

        return $pdf_url;
    }

    /**
     * Supprimer un PDF
     */
    public static function delete_pdf($quote_id)
    {
        $quote = RestaurantBooking_Quote::get($quote_id);
        if (!$quote) {
            return false;
        }

        $upload_dir = wp_upload_dir();
        $pdf_filename = 'devis-' . $quote['quote_number'] . '.html';
        $pdf_path = $upload_dir['basedir'] . '/restaurant-booking/pdf/' . $pdf_filename;

        if (file_exists($pdf_path)) {
            return unlink($pdf_path);
        }

        return true;
    }

    /**
     * Nettoyer les anciens PDF
     */
    public static function cleanup_old_pdfs($days = 90)
    {
        $upload_dir = wp_upload_dir();
        $pdf_dir = $upload_dir['basedir'] . '/restaurant-booking/pdf/';

        if (!is_dir($pdf_dir)) {
            return 0;
        }

        $deleted = 0;
        $cutoff_time = time() - ($days * 24 * 60 * 60);

        $files = glob($pdf_dir . '*.html');
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff_time) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }

        if ($deleted > 0) {
            RestaurantBooking_Logger::info("Nettoyage PDF: $deleted fichiers supprimés");
        }

        return $deleted;
    }
}
