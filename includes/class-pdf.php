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
     * Générer un PDF de devis avec validations
     */
    public static function generate_quote_pdf($quote_id)
    {
        global $wpdb;
        
        // Validation de l'ID du devis
        if (!is_numeric($quote_id) || $quote_id <= 0) {
            return new WP_Error('invalid_quote_id', __('ID de devis invalide', 'restaurant-booking'));
        }
        
        // Récupérer les données du devis depuis la base
        $table_name = $wpdb->prefix . 'restaurant_quotes';
        $quote = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d",
            $quote_id
        ));
        
        if (!$quote) {
            return new WP_Error('quote_not_found', __('Devis introuvable', 'restaurant-booking'));
        }

        try {
            // Décoder les données JSON avec validation
            $customer_data = is_array($quote->customer_data) ? $quote->customer_data : json_decode($quote->customer_data, true);
            $price_data = is_array($quote->price_breakdown) ? $quote->price_breakdown : json_decode($quote->price_breakdown, true);
            $selected_products = is_array($quote->selected_products) ? $quote->selected_products : json_decode($quote->selected_products, true);
            
            // Validation des données décodées
            if (!is_array($customer_data)) {
                $customer_data = [];
            }
            if (!is_array($price_data)) {
                $price_data = [];
            }
            if (!is_array($selected_products)) {
                $selected_products = [];
            }
            
            // Générer le HTML directement avec le nouveau système
            $html_content = self::generate_direct_html($quote, $customer_data, $price_data, $selected_products);

            // Créer le répertoire de sortie si nécessaire avec permissions correctes
            $upload_dir = wp_upload_dir();
            $pdf_dir = $upload_dir['basedir'] . '/restaurant-booking/pdf/';
            
            if (!file_exists($pdf_dir)) {
                $created = wp_mkdir_p($pdf_dir);
                if ($created) {
                    // S'assurer que les permissions sont correctes
                    chmod($pdf_dir, 0755);
                    // Créer un fichier .htaccess pour permettre l'accès aux fichiers PDF et HTML
                    $htaccess_content = "# Restaurant Booking PDF/HTML Access\n";
                    $htaccess_content .= "Options -Indexes\n";
                    $htaccess_content .= "<Files ~ \"\.(pdf|html)$\">\n";
                    $htaccess_content .= "    Require all granted\n";
                    $htaccess_content .= "    Allow from all\n";
                    $htaccess_content .= "</Files>\n";
                    $htaccess_content .= "<Files ~ \"\.html$\">\n";
                    $htaccess_content .= "    Header set Content-Type \"text/html; charset=UTF-8\"\n";
                    $htaccess_content .= "</Files>";
                    file_put_contents($pdf_dir . '.htaccess', $htaccess_content);
                } else {
                    error_log("Restaurant Booking: Impossible de créer le répertoire PDF: {$pdf_dir}");
                    return new WP_Error('pdf_dir_error', __('Impossible de créer le répertoire PDF', 'restaurant-booking'));
                }
            }
            
            // Vérifier et mettre à jour le .htaccess si nécessaire
            $htaccess_path = $pdf_dir . '.htaccess';
            if (!file_exists($htaccess_path) || !strpos(file_get_contents($htaccess_path), 'Require all granted')) {
                $htaccess_content = "# Restaurant Booking PDF/HTML Access\n";
                $htaccess_content .= "Options -Indexes\n";
                $htaccess_content .= "<Files ~ \"\.(pdf|html)$\">\n";
                $htaccess_content .= "    Require all granted\n";
                $htaccess_content .= "    Allow from all\n";
                $htaccess_content .= "</Files>\n";
                $htaccess_content .= "<Files ~ \"\.html$\">\n";
                $htaccess_content .= "    Header set Content-Type \"text/html; charset=UTF-8\"\n";
                $htaccess_content .= "</Files>";
                file_put_contents($htaccess_path, $htaccess_content);
                chmod($htaccess_path, 0644);
            }
            
            // Vérifier que le répertoire est accessible en écriture
            if (!is_writable($pdf_dir)) {
                error_log("Restaurant Booking: Répertoire PDF non accessible en écriture: {$pdf_dir}");
                return new WP_Error('pdf_dir_not_writable', __('Répertoire PDF non accessible en écriture', 'restaurant-booking'));
            }

            // Nom du fichier PDF
            $pdf_filename = 'devis-' . $quote->quote_number . '.pdf';
            $pdf_path = $pdf_dir . $pdf_filename;

            // Essayer d'utiliser une librairie PDF si disponible
            if (self::generate_with_tcpdf($html_content, $pdf_path)) {
                // TCPDF réussi
            } elseif (self::generate_with_dompdf($html_content, $pdf_path)) {
                // DomPDF réussi
            } else {
                // Fallback : créer un fichier HTML directement sans passer par le template PHP
                $html_path = str_replace('.pdf', '.html', $pdf_path);
                
                // Générer le HTML directement avec les données
                $direct_html = self::generate_direct_html($quote, $customer_data, $price_data, $selected_products);
                
                if (file_put_contents($html_path, $direct_html)) {
                    // S'assurer que le fichier HTML est accessible publiquement
                    chmod($html_path, 0644);
                    
                    if (class_exists('RestaurantBooking_Logger')) {
                        RestaurantBooking_Logger::info("PDF HTML généré (fallback direct)", array(
                            'quote_id' => $quote_id,
                            'quote_number' => $quote->quote_number,
                            'file_path' => $html_path,
                            'file_permissions' => substr(sprintf('%o', fileperms($html_path)), -4)
                        ));
                    }
                    return $html_path; // Retourner le fichier HTML au lieu du PDF
                } else {
                    if (class_exists('RestaurantBooking_Logger')) {
                        RestaurantBooking_Logger::error("Impossible de créer le fichier HTML", array(
                            'quote_id' => $quote_id,
                            'quote_number' => $quote->quote_number
                        ));
                    }
                    return new WP_Error('html_creation_failed', __('Impossible de créer le fichier de devis', 'restaurant-booking'));
                }
            }

            if (class_exists('RestaurantBooking_Logger')) {
                RestaurantBooking_Logger::info("PDF généré", array(
                    'quote_id' => $quote_id,
                    'quote_number' => $quote->quote_number,
                    'file_path' => $pdf_path
                ));
            }

            return $pdf_path;

        } catch (Exception $e) {
            if (class_exists('RestaurantBooking_Logger')) {
                RestaurantBooking_Logger::error("Erreur génération PDF", array(
                    'quote_id' => $quote_id,
                    'error' => $e->getMessage()
                ));
            }
            
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
     * Générer PDF avec solution native WordPress
     */
    private static function generate_with_native_wp($html_content, $pdf_path)
    {
        // Utiliser WP_Filesystem pour créer un PDF simple
        if (!function_exists('WP_Filesystem')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        WP_Filesystem();
        global $wp_filesystem;
        
        try {
            // Convertir HTML en texte simple pour un PDF basique
            $text_content = self::html_to_text($html_content);
            
            // Créer un contenu PDF simple
            $pdf_content = self::create_simple_pdf($text_content);
            
            // Écrire le fichier
            $result = $wp_filesystem->put_contents($pdf_path, $pdf_content);
            
            return $result !== false;
        } catch (Exception $e) {
            RestaurantBooking_Logger::debug("Native WP PDF error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Convertir HTML en texte simple bien formaté
     */
    private static function html_to_text($html)
    {
        // Remplacer les balises HTML par des équivalents texte lisibles
        $html = str_replace(['<br>', '<br/>', '<br />'], "\n", $html);
        $html = str_replace(['</p>', '</div>', '</h1>', '</h2>', '</h3>', '</h4>', '</h5>', '</h6>'], "\n\n", $html);
        $html = str_replace(['</tr>'], "\n", $html);
        $html = str_replace(['</td>', '</th>'], " | ", $html);
        $html = str_replace(['<hr>', '<hr/>', '<hr />'], "\n" . str_repeat('-', 50) . "\n", $html);
        
        // Gérer les listes
        $html = preg_replace('/<li[^>]*>/i', "\n• ", $html);
        $html = str_replace('</li>', '', $html);
        
        // Gérer les tableaux
        $html = preg_replace('/<table[^>]*>/i', "\n" . str_repeat('=', 60) . "\n", $html);
        $html = str_replace('</table>', "\n" . str_repeat('=', 60) . "\n", $html);
        $html = preg_replace('/<thead[^>]*>/i', "\n", $html);
        $html = str_replace('</thead>', "\n" . str_repeat('-', 60) . "\n", $html);
        
        // Supprimer les balises HTML et convertir en texte lisible
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }
    
    /**
     * Créer un PDF simple avec meilleur formatage
     */
    private static function create_simple_pdf($text_content)
    {
        // Formater le contenu pour une meilleure lisibilité
        $formatted_content = self::format_text_for_pdf($text_content);
        
        // En-tête PDF minimal
        $pdf = "%PDF-1.4\n";
        $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $pdf .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $pdf .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> >>\nendobj\n";
        
        // Contenu de la page avec formatage amélioré
        $stream = self::create_formatted_pdf_stream($formatted_content);
        
        $pdf .= "4 0 obj\n<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream\nendobj\n";
        $pdf .= "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
        $pdf .= "6 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>\nendobj\n";
        
        // Table de références croisées
        $xref_pos = strlen($pdf);
        $pdf .= "xref\n0 7\n0000000000 65535 f \n";
        $pdf .= sprintf("%010d 00000 n \n", strpos($pdf, "1 0 obj"));
        $pdf .= sprintf("%010d 00000 n \n", strpos($pdf, "2 0 obj"));
        $pdf .= sprintf("%010d 00000 n \n", strpos($pdf, "3 0 obj"));
        $pdf .= sprintf("%010d 00000 n \n", strpos($pdf, "4 0 obj"));
        $pdf .= sprintf("%010d 00000 n \n", strpos($pdf, "5 0 obj"));
        $pdf .= sprintf("%010d 00000 n \n", strpos($pdf, "6 0 obj"));
        
        // Trailer
        $pdf .= "trailer\n<< /Size 7 /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xref_pos . "\n%%EOF\n";
        
        return $pdf;
    }
    
    /**
     * Formater le texte pour un meilleur rendu PDF
     */
    private static function format_text_for_pdf($text)
    {
        // Identifier et marquer les sections importantes
        $text = str_replace('DEVIS DE PRIVATISATION', "TITRE:DEVIS DE PRIVATISATION", $text);
        $text = preg_replace('/^(Client|Service|Date|Durée|Nombre de convives|Lieu de livraison):/m', 'LABEL:$1:', $text);
        $text = str_replace('TOTAL TTC', "TOTAL:TOTAL TTC", $text);
        
        // Améliorer la lisibilité des tableaux
        $text = preg_replace('/\|\s*\|/', ' | ', $text);
        $text = str_replace(' | ', '  |  ', $text);
        
        return $text;
    }
    
    /**
     * Créer le flux PDF formaté
     */
    private static function create_formatted_pdf_stream($text)
    {
        $stream = "BT\n";
        $lines = explode("\n", $text);
        $y_position = 750;
        $line_height = 14;
        $margin_left = 50;
        
        foreach ($lines as $line) {
            if ($y_position < 50) break; // Éviter de sortir de la page
            
            $line = trim($line);
            if (empty($line)) {
                $y_position -= $line_height / 2;
                continue;
            }
            
            // Positionner le curseur
            $stream .= "{$margin_left} {$y_position} Td\n";
            
            // Détecter les types de contenu et appliquer le formatage
            if (strpos($line, 'TITRE:') === 0) {
                $stream .= "/F2 16 Tf\n"; // Titre principal
                $line = str_replace('TITRE:', '', $line);
                $stream .= "(" . addcslashes($line, "()\\") . ") Tj\n";
                $y_position -= $line_height * 1.5;
            } elseif (strpos($line, 'TOTAL:') === 0) {
                $stream .= "/F2 14 Tf\n"; // Total en gras
                $line = str_replace('TOTAL:', '', $line);
                $stream .= "(" . addcslashes($line, "()\\") . ") Tj\n";
                $y_position -= $line_height;
            } elseif (strpos($line, 'LABEL:') === 0) {
                $stream .= "/F2 12 Tf\n"; // Labels en gras
                $line = str_replace('LABEL:', '', $line);
                $stream .= "(" . addcslashes($line, "()\\") . ") Tj\n";
                $y_position -= $line_height;
            } else {
                $stream .= "/F1 10 Tf\n"; // Texte normal
                
                // Gérer les lignes longues
                if (strlen($line) > 75) {
                    $wrapped_lines = explode("\n", wordwrap($line, 75, "\n", true));
                    foreach ($wrapped_lines as $wrapped_line) {
                        if ($y_position < 50) break;
                        $stream .= "(" . addcslashes($wrapped_line, "()\\") . ") Tj\n";
                        $stream .= "0 -{$line_height} Td\n";
                        $y_position -= $line_height;
                    }
                } else {
                    $stream .= "(" . addcslashes($line, "()\\") . ") Tj\n";
                    $y_position -= $line_height;
                }
            }
            
            $stream .= "0 -{$line_height} Td\n";
        }
        
        $stream .= "ET\n";
        return $stream;
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
     * Rendre le HTML imprimable et bien formaté
     */
    private static function make_html_printable($html_content)
    {
        // Ajouter les styles d'impression et optimiser pour l'affichage
        $printable_html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Devis Block & Co</title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none !important; }
        }
        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 20px;
            background: white;
        }
        .header {
            border-bottom: 3px solid #FFB404;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .quote-title {
            font-size: 24px;
            font-weight: bold;
            color: #FFB404;
            text-align: center;
            margin: 30px 0;
        }
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .products-table th,
        .products-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .products-table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        .products-table .text-right {
            text-align: right;
        }
        .products-table .text-center {
            text-align: center;
        }
        .total-section {
            margin-top: 30px;
            border-top: 2px solid #FFB404;
            padding-top: 15px;
        }
        .total-table {
            width: 60%;
            margin-left: auto;
            border-collapse: collapse;
        }
        .total-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #eee;
        }
        .total-table .total-row {
            background: #243127;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }
        .service-badge {
            background: #243127;
            color: white;
            padding: 4px 12px;
            border-radius: 16px;
            font-size: 10px;
            font-weight: bold;
            display: inline-block;
        }
        .quote-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .quote-info table {
            width: 100%;
            border-collapse: collapse;
        }
        .quote-info td {
            padding: 5px 0;
            vertical-align: top;
        }
        .quote-info .label {
            font-weight: bold;
            width: 40%;
        }
    </style>
</head>
<body>';

        // Extraire le contenu du body du HTML original
        if (preg_match('/<body[^>]*>(.*?)<\/body>/s', $html_content, $matches)) {
            $printable_html .= $matches[1];
        } else {
            $printable_html .= $html_content;
        }

        $printable_html .= '</body></html>';

        return $printable_html;
    }
    
    /**
     * Générer le HTML directement sans template PHP
     */
    public static function generate_direct_html($quote, $customer_data, $price_data, $selected_products)
    {
        // Récupérer les paramètres de personnalisation PDF depuis les options
        $settings = get_option('restaurant_booking_pdf_settings', array());
        $primary_color = isset($settings['primary_color']) ? $settings['primary_color'] : '#FFB404';
        $secondary_color = isset($settings['secondary_color']) ? $settings['secondary_color'] : '#243127';
        $footer_text = isset($settings['footer_text']) ? $settings['footer_text'] : 'Block & Co - 123 Rue de la Gastronomie, 67000 Strasbourg';
        $logo_id = isset($settings['logo_id']) ? $settings['logo_id'] : '';
        $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'medium') : '';
        
        // Récupérer les paramètres généraux de l'entreprise
        $general_settings = get_option('restaurant_booking_general_settings', array());
        $company_name = isset($general_settings['company_name']) ? $general_settings['company_name'] : 'Restaurant Block';
        $company_address = isset($general_settings['company_address']) ? $general_settings['company_address'] : '123 Rue de la Gastronomie';
        $company_postal_code = isset($general_settings['company_postal_code']) ? $general_settings['company_postal_code'] : '67000';
        $company_city = isset($general_settings['company_city']) ? $general_settings['company_city'] : 'Strasbourg';
        $company_phone = isset($general_settings['company_phone']) ? $general_settings['company_phone'] : '03 88 XX XX XX';
        $company_email = isset($general_settings['company_email']) ? $general_settings['company_email'] : 'contact@restaurant-block.fr';
        $company_siret = isset($general_settings['company_siret']) ? $general_settings['company_siret'] : '123 456 789 01234';
        
        // Récupérer les textes des conditions générales
        $quote_validity = isset($settings['quote_validity']) ? $settings['quote_validity'] : 'Ce devis est valable 30 jours à compter de sa date d\'émission.';
        $payment_terms = isset($settings['payment_terms']) ? $settings['payment_terms'] : '- Acompte de 30% à la confirmation de commande\n- Solde le jour de la prestation';
        $cancellation_terms = isset($settings['cancellation_terms']) ? $settings['cancellation_terms'] : '- Annulation gratuite jusqu\'à 48h avant l\'événement\n- Annulation entre 48h et 24h : 50% du montant total\n- Annulation moins de 24h : 100% du montant total';
        $general_remarks = isset($settings['general_remarks']) ? $settings['general_remarks'] : 'Ce devis est établi selon vos indications. Toute modification pourra donner lieu à un avenant.\nLes prix sont exprimés en euros TTC.';
        
        // Sécuriser toutes les données
        $quote_number = is_object($quote) ? ($quote->quote_number ?? 'N/A') : ($quote['quote_number'] ?? 'N/A');
        $service_type = is_object($quote) ? ($quote->service_type ?? 'restaurant') : ($quote['service_type'] ?? 'restaurant');
        $event_date = is_object($quote) ? ($quote->event_date ?? '') : ($quote['event_date'] ?? '');
        $event_duration = is_object($quote) ? ($quote->event_duration ?? 0) : ($quote['event_duration'] ?? 0);
        $guest_count = is_object($quote) ? ($quote->guest_count ?? 0) : ($quote['guest_count'] ?? 0);
        $postal_code = is_object($quote) ? ($quote->postal_code ?? '') : ($quote['postal_code'] ?? '');
        $total_price = is_object($quote) ? ($quote->total_price ?? 0) : ($quote['total_price'] ?? 0);
        
        $customer_firstname = $customer_data['firstname'] ?? '';
        $customer_name = $customer_data['name'] ?? '';
        $customer_email = $customer_data['email'] ?? '';
        $customer_phone = $customer_data['phone'] ?? '';
        $customer_message = $customer_data['message'] ?? '';
        
        $service_name = $service_type === 'restaurant' ? 'Privatisation Restaurant' : 'Privatisation Remorque';
        $formatted_date = $event_date ? date('d/m/Y', strtotime($event_date)) : 'Non définie';
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Devis ' . htmlspecialchars($quote_number) . '</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 20px;
            background: white;
        }
        .header {
            border-bottom: 3px solid ' . $primary_color . ';
            padding-bottom: 20px;
            margin-bottom: 30px;
            text-align: center;
        }
        .company-info {
            font-weight: bold;
            font-size: 16px;
            color: ' . $primary_color . ';
            margin-bottom: 10px;
        }
        .quote-title {
            font-size: 24px;
            font-weight: bold;
            color: ' . $primary_color . ';
            text-align: center;
            margin: 30px 0;
        }
        .quote-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .quote-info table {
            width: 100%;
            border-collapse: collapse;
        }
        .quote-info td {
            padding: 5px 0;
            vertical-align: top;
        }
        .quote-info .label {
            font-weight: bold;
            width: 40%;
        }
        .service-badge {
            background: #243127;
            color: white;
            padding: 4px 12px;
            border-radius: 16px;
            font-size: 10px;
            font-weight: bold;
            display: inline-block;
        }
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .products-table th,
        .products-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .products-table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        .products-table .text-right {
            text-align: right;
        }
        .products-table .text-center {
            text-align: center;
        }
        .total-section {
            margin-top: 30px;
            border-top: 2px solid #FFB404;
            padding-top: 15px;
        }
        .total-table {
            width: 60%;
            margin-left: auto;
            border-collapse: collapse;
        }
        .total-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #eee;
        }
        .total-table .total-row {
            background: #243127;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }
        .customer-section {
            margin: 20px 0;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }
        @media print {
            .no-print { display: none !important; }
            .print-button { display: none !important; }
        }
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #FFB404;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            z-index: 1000;
            transition: all 0.3s ease;
        }
        .print-button:hover {
            background: #e6a200;
            transform: translateY(-2px);
        }
        .download-section {
            background: #e3f2fd;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid ' . $primary_color . ';
        }
        .download-button {
            background: ' . $primary_color . ';
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin: 10px;
            transition: all 0.3s ease;
        }
        .download-button:hover {
            background: #e6a200;
            transform: translateY(-2px);
        }
    </style>
    <script>
        function downloadPDF() {
            window.print();
        }
    </script>
</head>
<body>
    <!-- Bouton flottant de téléchargement -->
    <button class="print-button no-print" onclick="downloadPDF()" title="Télécharger en PDF">
        📥 Télécharger PDF
    </button>

    <!-- Header -->
    <div class="header">';
        
        if ($logo_url) {
            $html .= '<div style="margin-bottom: 15px;"><img src="' . esc_url($logo_url) . '" alt="Logo" style="max-height: 80px; max-width: 200px;" /></div>';
        }
        
        $html .= '<div class="company-info">' . htmlspecialchars($company_name) . '</div>
        <div>' . htmlspecialchars($company_address . ', ' . $company_postal_code . ' ' . $company_city) . '</div>
        <div>Tél: ' . htmlspecialchars($company_phone) . ' | Email: ' . htmlspecialchars($company_email) . '</div>
        <div style="margin-top: 10px;"><strong>Date:</strong> ' . date('d/m/Y') . ' | <strong>Devis N°:</strong> ' . htmlspecialchars($quote_number) . '</div>
    </div>

    <!-- Titre -->
    <div class="quote-title">DEVIS DE PRIVATISATION</div>

    <!-- Informations client -->
    <div class="customer-section">
        <h3>Client</h3>
        <p><strong>' . htmlspecialchars($customer_firstname . ' ' . $customer_name) . '</strong></p>
        <p><strong>Email:</strong> ' . htmlspecialchars($customer_email) . '</p>
        <p><strong>Téléphone:</strong> ' . htmlspecialchars($customer_phone) . '</p>';
        
        if ($customer_message) {
            $html .= '<p><strong>Message:</strong> ' . htmlspecialchars($customer_message) . '</p>';
        }
        
        $html .= '
    </div>

    <!-- Informations événement -->
    <div class="quote-info">
        <h3>Détails de l\'événement</h3>
        <table>
            <tr>
                <td class="label">Service :</td>
                <td><span class="service-badge">' . htmlspecialchars($service_name) . '</span></td>
            </tr>
            <tr>
                <td class="label">Date événement :</td>
                <td>' . htmlspecialchars($formatted_date) . '</td>
            </tr>
            <tr>
                <td class="label">Durée :</td>
                <td>' . intval($event_duration) . ' heures</td>
            </tr>
            <tr>
                <td class="label">Nombre de convives :</td>
                <td>' . intval($guest_count) . ' personnes</td>
            </tr>';
            
        if ($service_type === 'remorque' && $postal_code) {
            $html .= '
            <tr>
                <td class="label">Lieu de livraison :</td>
                <td>' . htmlspecialchars($postal_code) . '</td>
            </tr>';
        }
        
        $html .= '
        </table>
    </div>

    <!-- Détail de la prestation -->
    <h3>Détail de la prestation</h3>
    <table class="products-table">
        <thead>
            <tr>
                <th>Description</th>
                <th class="text-center">Quantité</th>
                <th class="text-right">Prix unitaire</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>';
        
        // Forfait de base
        $base_price = $price_data['base_price']['amount'] ?? 0;
        $html .= '
            <tr>
                <td><strong>Forfait de base</strong><br><small>' . htmlspecialchars($price_data['base_price']['details'] ?? '') . '</small></td>
                <td class="text-center">1</td>
                <td class="text-right">' . number_format($base_price, 2, ',', ' ') . ' €</td>
                <td class="text-right">' . number_format($base_price, 2, ',', ' ') . ' €</td>
            </tr>';
            
        // Produits sélectionnés - Utiliser price_data['products'] en priorité
        $products_displayed = false;
        
        // Essayer d'abord avec price_data['products'] (données calculées avec prix)
        if (!empty($price_data['products']) && is_array($price_data['products'])) {
            foreach ($price_data['products'] as $product) {
                if (isset($product['quantity']) && $product['quantity'] > 0) {
                    $products_displayed = true;
                    
                    // Récupérer le nom réel du produit depuis la base de données si nécessaire
                    $name = $product['name'] ?? 'Produit';
                    if ($name === 'Produit' && isset($product['id'])) {
                        global $wpdb;
                        $product_info = $wpdb->get_row($wpdb->prepare(
                            "SELECT name FROM {$wpdb->prefix}restaurant_products WHERE id = %d",
                            $product['id']
                        ));
                        if ($product_info) {
                            $name = $product_info->name;
                        }
                    }
                    
                    $price = floatval($product['price'] ?? 0);
                    $quantity = intval($product['quantity']);
                    $total = floatval($product['total'] ?? ($price * $quantity));
                    
                    $html .= '
            <tr>
                <td><strong>' . htmlspecialchars($name) . '</strong>';
                    
                    if (!empty($product['category'])) {
                        $html .= '<br><small style="color: #666;">' . htmlspecialchars($product['category']) . '</small>';
                    }
                    
                    $html .= '</td>
                <td class="text-center">' . $quantity . '</td>
                <td class="text-right">' . number_format($price, 2, ',', ' ') . ' €</td>
                <td class="text-right">' . number_format($total, 2, ',', ' ') . ' €</td>
            </tr>';
                    
                    // Afficher les options du produit si elles existent
                    if (!empty($product['options']) && is_array($product['options'])) {
                        foreach ($product['options'] as $option) {
                            if (isset($option['quantity']) && $option['quantity'] > 0) {
                                $html .= '
            <tr style="background-color: #f9f9f9;">
                <td style="padding-left: 30px;">└── ' . htmlspecialchars($option['name'] ?? 'Option') . '</td>
                <td class="text-center">' . intval($option['quantity']) . '</td>
                <td class="text-right">' . number_format(floatval($option['price'] ?? 0), 2, ',', ' ') . ' €</td>
                <td class="text-right">' . number_format(floatval($option['total'] ?? 0), 2, ',', ' ') . ' €</td>
            </tr>';
                                
                                // Afficher les sous-options si elles existent
                                if (!empty($option['suboptions']) && is_array($option['suboptions'])) {
                                    foreach ($option['suboptions'] as $suboption) {
                                        if (isset($suboption['quantity']) && $suboption['quantity'] > 0) {
                                            $html .= '
            <tr style="background-color: #f5f5f5;">
                <td style="padding-left: 50px;">└── ' . htmlspecialchars($suboption['name'] ?? 'Sous-option') . '</td>
                <td class="text-center">' . intval($suboption['quantity']) . '</td>
                <td class="text-right">' . number_format(floatval($suboption['price'] ?? 0), 2, ',', ' ') . ' €</td>
                <td class="text-right">' . number_format(floatval($suboption['total'] ?? 0), 2, ',', ' ') . ' €</td>
            </tr>';
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // Si pas de produits dans price_data, essayer avec selected_products (fallback)
        if (!$products_displayed && !empty($selected_products) && is_array($selected_products)) {
            foreach ($selected_products as $category => $products) {
                if (!empty($products) && is_array($products)) {
                    foreach ($products as $product) {
                        if (isset($product['quantity']) && $product['quantity'] > 0) {
                            $products_displayed = true;
                            
                            // Récupérer le nom et prix depuis la base de données
                            $name = 'Produit';
                            $price = 0;
                            
                            if (isset($product['name'])) {
                                $name = $product['name'];
                            } elseif (isset($product['id'])) {
                                global $wpdb;
                                $product_info = $wpdb->get_row($wpdb->prepare(
                                    "SELECT name, price FROM {$wpdb->prefix}restaurant_products WHERE id = %d",
                                    $product['id']
                                ));
                                if ($product_info) {
                                    $name = $product_info->name;
                                    $price = floatval($product_info->price);
                                }
                            }
                            
                            $quantity = intval($product['quantity']);
                            $total = $price * $quantity;
                            
                            $html .= '
            <tr>
                <td><strong>' . htmlspecialchars($name) . '</strong>';
                            
                            if (!empty($category)) {
                                $html .= '<br><small style="color: #666;">' . htmlspecialchars(ucfirst(str_replace('_', ' ', $category))) . '</small>';
                            }
                            
                            $html .= '</td>
                <td class="text-center">' . $quantity . '</td>
                <td class="text-right">' . number_format($price, 2, ',', ' ') . ' €</td>
                <td class="text-right">' . number_format($total, 2, ',', ' ') . ' €</td>
            </tr>';
                        }
                    }
                }
            }
        }
        
        // Afficher les boissons calculées
        if (!empty($price_data['beverages_detailed']) && is_array($price_data['beverages_detailed'])) {
            foreach ($price_data['beverages_detailed'] as $beverage) {
                $html .= '
            <tr>
                <td><strong>' . htmlspecialchars($beverage['name'] ?? 'Boisson') . '</strong>';
                
                if (!empty($beverage['size'])) {
                    $html .= ' <small>(' . htmlspecialchars($beverage['size']) . ')</small>';
                }
                
                if (!empty($beverage['type'])) {
                    $html .= '<br><small style="color: #666;">' . htmlspecialchars($beverage['type']) . '</small>';
                }
                
                $html .= '</td>
                <td class="text-center">' . intval($beverage['quantity'] ?? 0) . '</td>
                <td class="text-right">' . number_format(floatval($beverage['price'] ?? 0), 2, ',', ' ') . ' €</td>
                <td class="text-right">' . number_format(floatval($beverage['total'] ?? 0), 2, ',', ' ') . ' €</td>
            </tr>';
            }
        }
        
        // Afficher les options remorque
        if (!empty($price_data['options']) && is_array($price_data['options'])) {
            foreach ($price_data['options'] as $option) {
                $html .= '
            <tr>
                <td><strong>' . htmlspecialchars($option['name'] ?? 'Option') . '</strong>';
                
                if (!empty($option['description'])) {
                    $html .= '<br><small style="color: #666;">' . htmlspecialchars($option['description']) . '</small>';
                }
                
                $html .= '</td>
                <td class="text-center">' . intval($option['quantity'] ?? 1) . '</td>
                <td class="text-right">' . number_format(floatval($option['price'] ?? 0), 2, ',', ' ') . ' €</td>
                <td class="text-right">' . number_format(floatval($option['total'] ?? $option['price'] ?? 0), 2, ',', ' ') . ' €</td>
            </tr>';
            }
        }
        
        $html .= '
        </tbody>
    </table>

    <!-- Totaux -->
    <div class="total-section">
        <table class="total-table">
            <tr class="total-row">
                <td><strong>TOTAL TTC :</strong></td>
                <td class="text-right"><strong>' . number_format($total_price, 2, ',', ' ') . ' €</strong></td>
            </tr>
        </table>
    </div>

    <!-- Section de téléchargement -->
    <div class="download-section no-print">
        <h3 style="color: #243127; margin-top: 0;">💾 Sauvegarder votre devis</h3>
        <p style="font-size: 14px; margin-bottom: 20px;">
            Cliquez sur le bouton ci-dessous pour télécharger et sauvegarder votre devis en PDF sur votre ordinateur :
        </p>
        <button class="download-button" onclick="downloadPDF()">
            📥 Télécharger en PDF
        </button>
        <p style="font-size: 12px; color: #666; margin-top: 15px;">
            <strong>Instructions :</strong> Une fenêtre va s\'ouvrir. Choisissez "Enregistrer au format PDF" comme destination, puis cliquez sur "Enregistrer".
        </p>
    </div>

    <!-- Conditions générales -->
    <div style="margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid ' . $primary_color . ';">
        <h3 style="color: ' . $primary_color . '; margin-top: 0;">Conditions générales</h3>
        
        <p><strong>Validité du devis :</strong> ' . esc_html($quote_validity) . '</p>
        
        <p><strong>Modalités de paiement :</strong><br>
        ' . nl2br(esc_html($payment_terms)) . '</p>
        
        <p><strong>Conditions d\'annulation :</strong><br>
        ' . nl2br(esc_html($cancellation_terms)) . '</p>
        
        <p><strong>Remarques :</strong><br>
        ' . nl2br(esc_html($general_remarks)) . '</p>';
        
        if ($customer_message) {
            $html .= '<p><strong>Votre demande :</strong><br>
            <em>' . esc_html($customer_message) . '</em></p>';
        }
        
        $html .= '</div>

    <!-- Footer -->
    <div style="margin-top: 20px; padding: 20px; background: ' . $secondary_color . '; border-radius: 8px; text-align: center; color: white; font-weight: bold;">
        ' . esc_html($footer_text) . '
    </div>
</body>
</html>';

        return $html;
    }

}
