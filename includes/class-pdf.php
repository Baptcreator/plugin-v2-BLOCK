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
            $pdf_filename = 'devis-' . $quote['quote_number'] . '.pdf';
            $pdf_path = $pdf_dir . $pdf_filename;

            // Essayer d'utiliser une librairie PDF si disponible
            if (self::generate_with_mpdf($html_content, $pdf_path)) {
                // mPDF réussi
            } elseif (self::generate_with_dompdf($html_content, $pdf_path)) {
                // DomPDF réussi
            } elseif (self::generate_with_tcpdf($html_content, $pdf_path)) {
                // TCPDF réussi
            } else {
                // Fallback : sauvegarder en HTML
                $pdf_filename = 'devis-' . $quote['quote_number'] . '.html';
                $pdf_path = $pdf_dir . $pdf_filename;
                file_put_contents($pdf_path, $html_content);
                
                RestaurantBooking_Logger::warning("PDF généré en HTML (aucune librairie PDF disponible)", array(
                    'quote_id' => $quote_id,
                    'quote_number' => $quote['quote_number']
                ));
            }

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

    /**
     * Générer PDF avec mPDF
     */
    private static function generate_with_mpdf($html_content, $pdf_path)
    {
        if (!class_exists('\Mpdf\Mpdf')) {
            return false;
        }

        try {
            $mpdf = new \Mpdf\Mpdf([
                'format' => 'A4',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 16,
                'margin_bottom' => 16,
                'margin_header' => 9,
                'margin_footer' => 9,
                'default_font' => 'DejaVuSans'
            ]);

            $mpdf->WriteHTML($html_content);
            $mpdf->Output($pdf_path, 'F');
            
            return true;
        } catch (Exception $e) {
            RestaurantBooking_Logger::debug("mPDF error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Générer PDF avec DomPDF
     */
    private static function generate_with_dompdf($html_content, $pdf_path)
    {
        if (!class_exists('\Dompdf\Dompdf')) {
            return false;
        }

        try {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html_content);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            
            file_put_contents($pdf_path, $dompdf->output());
            
            return true;
        } catch (Exception $e) {
            RestaurantBooking_Logger::debug("DomPDF error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Générer PDF avec TCPDF
     */
    private static function generate_with_tcpdf($html_content, $pdf_path)
    {
        if (!class_exists('TCPDF')) {
            return false;
        }

        try {
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetCreator('Restaurant Booking Plugin');
            $pdf->SetTitle('Devis Block & Co');
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->AddPage();
            $pdf->writeHTML($html_content, true, false, true, false, '');
            $pdf->Output($pdf_path, 'F');
            
            return true;
        } catch (Exception $e) {
            RestaurantBooking_Logger::debug("TCPDF error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtenir le statut des librairies PDF
     */
    public static function get_pdf_libraries_status()
    {
        return array(
            'mpdf' => array(
                'available' => class_exists('\Mpdf\Mpdf'),
                'name' => 'mPDF',
                'description' => __('Librairie PDF moderne avec bon support HTML/CSS', 'restaurant-booking'),
                'install_command' => 'composer require mpdf/mpdf'
            ),
            'dompdf' => array(
                'available' => class_exists('\Dompdf\Dompdf'),
                'name' => 'DomPDF', 
                'description' => __('Librairie PDF légère et rapide', 'restaurant-booking'),
                'install_command' => 'composer require dompdf/dompdf'
            ),
            'tcpdf' => array(
                'available' => class_exists('TCPDF'),
                'name' => 'TCPDF',
                'description' => __('Librairie PDF complète avec nombreuses fonctionnalités', 'restaurant-booking'),
                'install_command' => 'composer require tecnickcom/tcpdf'
            )
        );
    }

    /**
     * Test de génération PDF
     */
    public static function test_pdf_generation()
    {
        $test_html = '
        <html>
        <head>
            <style>
                body { font-family: DejaVu Sans, sans-serif; }
                .header { color: #243127; font-size: 24px; text-align: center; margin-bottom: 30px; }
                .content { font-size: 14px; line-height: 1.5; }
            </style>
        </head>
        <body>
            <div class="header">Test PDF - Block & Co</div>
            <div class="content">
                <p>Ceci est un test de génération PDF.</p>
                <p>Date : ' . date('d/m/Y H:i:s') . '</p>
                <p>Si vous voyez ce contenu, la génération PDF fonctionne correctement.</p>
            </div>
        </body>
        </html>';

        $upload_dir = wp_upload_dir();
        $pdf_dir = $upload_dir['basedir'] . '/restaurant-booking/pdf/';
        
        if (!file_exists($pdf_dir)) {
            wp_mkdir_p($pdf_dir);
        }

        $test_path = $pdf_dir . 'test-pdf-' . time() . '.pdf';

        // Essayer les différentes librairies
        if (self::generate_with_mpdf($test_html, $test_path)) {
            return array('status' => 'success', 'library' => 'mPDF', 'file' => $test_path);
        } elseif (self::generate_with_dompdf($test_html, $test_path)) {
            return array('status' => 'success', 'library' => 'DomPDF', 'file' => $test_path);
        } elseif (self::generate_with_tcpdf($test_html, $test_path)) {
            return array('status' => 'success', 'library' => 'TCPDF', 'file' => $test_path);
        } else {
            return array('status' => 'error', 'message' => __('Aucune librairie PDF disponible', 'restaurant-booking'));
        }
    }
}
