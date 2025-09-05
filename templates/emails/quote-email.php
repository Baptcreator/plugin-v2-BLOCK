<?php
/**
 * Template email de devis
 *
 * @package RestaurantBooking
 * @since 1.0.0
 * 
 * Variables disponibles :
 * @var array $quote Données du devis
 * @var array $customer Données client
 * @var array $settings Paramètres du plugin
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($settings['email_quote_subject'] ?? 'Votre devis Restaurant Block'); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .email-header {
            background: #0073aa;
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 28px;
        }
        .email-body {
            padding: 30px 20px;
        }
        .quote-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .quote-details {
            margin: 20px 0;
        }
        .quote-details table {
            width: 100%;
            border-collapse: collapse;
        }
        .quote-details th,
        .quote-details td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .quote-details th {
            background: #f8f9fa;
            font-weight: bold;
        }
        .total-price {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
        }
        .total-price .amount {
            font-size: 32px;
            font-weight: bold;
            color: #0073aa;
        }
        .email-footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 14px;
            color: #666;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #0073aa;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin: 10px 5px;
        }
        .service-badge {
            display: inline-block;
            padding: 4px 12px;
            background: #e3f2fd;
            color: #0073aa;
            border-radius: 16px;
            font-size: 12px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <?php echo wp_kses_post($settings['email_quote_header_html'] ?? '<h1>Restaurant Block</h1>'); ?>
        </div>

        <!-- Corps de l'email -->
        <div class="email-body">
            <h2>Votre devis de privatisation</h2>
            
            <?php echo wp_kses_post($settings['email_quote_body_html'] ?? '<p>Madame, Monsieur,</p><p>Nous vous remercions pour votre demande de devis.</p>'); ?>

            <!-- Résumé du devis -->
            <div class="quote-summary">
                <h3>Résumé de votre demande</h3>
                <p><strong>Numéro de devis :</strong> <?php echo esc_html($quote['quote_number']); ?></p>
                <p><strong>Service :</strong> 
                    <span class="service-badge">
                        <?php echo $quote['service_type'] === 'restaurant' ? 'Privatisation Restaurant' : 'Privatisation Remorque'; ?>
                    </span>
                </p>
                <p><strong>Date événement :</strong> <?php echo date_i18n('d/m/Y', strtotime($quote['event_date'])); ?></p>
                <p><strong>Durée :</strong> <?php echo $quote['event_duration']; ?> heures</p>
                <p><strong>Nombre de convives :</strong> <?php echo $quote['guest_count']; ?> personnes</p>
                <?php if ($quote['service_type'] === 'remorque' && !empty($quote['postal_code'])): ?>
                    <p><strong>Lieu :</strong> <?php echo esc_html($quote['postal_code']); ?></p>
                <?php endif; ?>
            </div>

            <!-- Détail des coûts -->
            <div class="quote-details">
                <h3>Détail de la tarification</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th style="text-align: right;">Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($quote['price_breakdown'] as $item_key => $item): ?>
                            <?php if ($item['amount'] > 0 || $item_key === 'base_price'): ?>
                                <tr>
                                    <td>
                                        <?php echo esc_html($item['label']); ?>
                                        <?php if (!empty($item['details'])): ?>
                                            <br><small style="color: #666;"><?php echo esc_html($item['details']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <?php echo number_format($item['amount'], 2, ',', ' '); ?> €
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Prix total -->
            <div class="total-price">
                <div>Total TTC</div>
                <div class="amount"><?php echo number_format($quote['total_price'], 2, ',', ' '); ?> €</div>
            </div>

            <!-- Informations client -->
            <?php if (!empty($customer)): ?>
                <div class="quote-details">
                    <h3>Vos coordonnées</h3>
                    <table>
                        <tbody>
                            <?php if (!empty($customer['name'])): ?>
                                <tr><td><strong>Nom :</strong></td><td><?php echo esc_html($customer['name']); ?></td></tr>
                            <?php endif; ?>
                            <?php if (!empty($customer['email'])): ?>
                                <tr><td><strong>Email :</strong></td><td><?php echo esc_html($customer['email']); ?></td></tr>
                            <?php endif; ?>
                            <?php if (!empty($customer['phone'])): ?>
                                <tr><td><strong>Téléphone :</strong></td><td><?php echo esc_html($customer['phone']); ?></td></tr>
                            <?php endif; ?>
                            <?php if (!empty($customer['company'])): ?>
                                <tr><td><strong>Entreprise :</strong></td><td><?php echo esc_html($customer['company']); ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Actions -->
            <div style="text-align: center; margin: 30px 0;">
                <p>Pour confirmer votre réservation ou pour toute question :</p>
                <a href="tel:+33123456789" class="button">Nous appeler</a>
                <a href="mailto:contact@restaurant-block.fr" class="button">Nous écrire</a>
            </div>

            <p><strong>Validité du devis :</strong> 30 jours à compter de la date d'émission.</p>
            <p><em>Ce devis est fourni à titre indicatif et peut être ajusté selon vos besoins spécifiques.</em></p>
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <?php echo wp_kses_post($settings['email_quote_footer_html'] ?? '<p>Restaurant Block - SIRET: 12345678901234</p>'); ?>
        </div>
    </div>
</body>
</html>
