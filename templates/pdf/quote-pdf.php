<?php
/**
 * Template PDF de devis
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
<html>
<head>
    <meta charset="UTF-8">
    <title>Devis <?php echo esc_html($quote['quote_number']); ?></title>
    <style>
        @page {
            margin: 2cm;
            @bottom-center {
                content: "Page " counter(page) " / " counter(pages);
                font-size: 10px;
                color: #666;
            }
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            border-bottom: 3px solid #0073aa;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header-content {
            display: table;
            width: 100%;
        }
        .header-left,
        .header-right {
            display: table-cell;
            vertical-align: top;
            width: 50%;
        }
        .header-right {
            text-align: right;
        }
        .company-info {
            font-weight: bold;
            font-size: 16px;
            color: #0073aa;
        }
        .quote-title {
            font-size: 24px;
            font-weight: bold;
            color: #0073aa;
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
        .customer-info {
            margin: 20px 0;
        }
        .service-badge {
            background: #e3f2fd;
            color: #0073aa;
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
            border-top: 2px solid #0073aa;
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
            background: #e3f2fd;
            font-weight: bold;
            font-size: 14px;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: #f8f9fa;
            border-top: 1px solid #ddd;
            padding: 15px 20px;
            font-size: 10px;
            color: #666;
        }
        .terms {
            margin-top: 40px;
            font-size: 10px;
            color: #666;
            line-height: 1.3;
        }
        .terms h4 {
            font-size: 12px;
            color: #333;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div class="header-left">
                <div class="company-info">Restaurant Block</div>
                <div>123 Rue de la Gastronomie</div>
                <div>67000 Strasbourg</div>
                <div>Tél: 03 88 XX XX XX</div>
                <div>Email: contact@restaurant-block.fr</div>
                <div>SIRET: 123 456 789 01234</div>
            </div>
            <div class="header-right">
                <div><strong>Date:</strong> <?php echo date_i18n('d/m/Y'); ?></div>
                <div><strong>Devis N°:</strong> <?php echo esc_html($quote['quote_number']); ?></div>
                <div><strong>Validité:</strong> 30 jours</div>
            </div>
        </div>
    </div>

    <!-- Titre -->
    <div class="quote-title">DEVIS DE PRIVATISATION</div>

    <!-- Informations client -->
    <?php if (!empty($customer)): ?>
    <div class="customer-info">
        <h3>Client</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    <?php if (!empty($customer['name'])): ?>
                        <strong><?php echo esc_html($customer['name']); ?></strong><br>
                    <?php endif; ?>
                    <?php if (!empty($customer['company'])): ?>
                        <?php echo esc_html($customer['company']); ?><br>
                    <?php endif; ?>
                    <?php if (!empty($customer['address'])): ?>
                        <?php echo esc_html($customer['address']); ?><br>
                    <?php endif; ?>
                    <?php if (!empty($customer['city'])): ?>
                        <?php echo esc_html($customer['postal_code'] . ' ' . $customer['city']); ?><br>
                    <?php endif; ?>
                </td>
                <td style="width: 50%; vertical-align: top;">
                    <?php if (!empty($customer['email'])): ?>
                        <strong>Email:</strong> <?php echo esc_html($customer['email']); ?><br>
                    <?php endif; ?>
                    <?php if (!empty($customer['phone'])): ?>
                        <strong>Téléphone:</strong> <?php echo esc_html($customer['phone']); ?><br>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>
    <?php endif; ?>

    <!-- Informations événement -->
    <div class="quote-info">
        <h3 style="margin-top: 0;">Détails de l'événement</h3>
        <table>
            <tr>
                <td class="label">Service :</td>
                <td>
                    <span class="service-badge">
                        <?php echo $quote['service_type'] === 'restaurant' ? 'Privatisation Restaurant' : 'Privatisation Remorque'; ?>
                    </span>
                </td>
            </tr>
            <tr>
                <td class="label">Date événement :</td>
                <td><?php echo date_i18n('l d F Y', strtotime($quote['event_date'])); ?></td>
            </tr>
            <tr>
                <td class="label">Durée :</td>
                <td><?php echo $quote['event_duration']; ?> heures</td>
            </tr>
            <tr>
                <td class="label">Nombre de convives :</td>
                <td><?php echo $quote['guest_count']; ?> personnes</td>
            </tr>
            <?php if ($quote['service_type'] === 'remorque' && !empty($quote['postal_code'])): ?>
            <tr>
                <td class="label">Lieu de livraison :</td>
                <td><?php echo esc_html($quote['postal_code']); ?> 
                    <?php if ($quote['distance_km']): ?>
                        (<?php echo $quote['distance_km']; ?> km)
                    <?php endif; ?>
                </td>
            </tr>
            <?php endif; ?>
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
        <tbody>
            <!-- Forfait de base -->
            <tr>
                <td>
                    <strong><?php echo esc_html($quote['price_breakdown']['base_price']['label']); ?></strong>
                    <?php if (!empty($quote['price_breakdown']['base_price']['details'])): ?>
                        <br><small><?php echo esc_html($quote['price_breakdown']['base_price']['details']); ?></small>
                    <?php endif; ?>
                </td>
                <td class="text-center">1</td>
                <td class="text-right"><?php echo number_format($quote['base_price'], 2, ',', ' '); ?> €</td>
                <td class="text-right"><?php echo number_format($quote['base_price'], 2, ',', ' '); ?> €</td>
            </tr>

            <!-- Suppléments -->
            <?php foreach (['duration_supplement', 'guests_supplement', 'delivery_cost'] as $supplement_key): ?>
                <?php if ($quote['price_breakdown'][$supplement_key]['amount'] > 0): ?>
                <tr>
                    <td>
                        <?php echo esc_html($quote['price_breakdown'][$supplement_key]['label']); ?>
                        <?php if (!empty($quote['price_breakdown'][$supplement_key]['details'])): ?>
                            <br><small><?php echo esc_html($quote['price_breakdown'][$supplement_key]['details']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">1</td>
                    <td class="text-right"><?php echo number_format($quote['price_breakdown'][$supplement_key]['amount'], 2, ',', ' '); ?> €</td>
                    <td class="text-right"><?php echo number_format($quote['price_breakdown'][$supplement_key]['amount'], 2, ',', ' '); ?> €</td>
                </tr>
                <?php endif; ?>
            <?php endforeach; ?>

            <!-- Produits sélectionnés -->
            <?php if (!empty($quote['price_breakdown']['products']['details'])): ?>
                <?php foreach ($quote['price_breakdown']['products']['details'] as $product): ?>
                <tr>
                    <td><?php echo esc_html($product['name']); ?></td>
                    <td class="text-center"><?php echo $product['quantity']; ?></td>
                    <td class="text-right"><?php echo number_format($product['price'], 2, ',', ' '); ?> €</td>
                    <td class="text-right"><?php echo number_format($product['total'], 2, ',', ' '); ?> €</td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Totaux -->
    <div class="total-section">
        <table class="total-table">
            <tr>
                <td>Sous-total HT :</td>
                <td class="text-right"><?php echo number_format($quote['total_price'] / 1.2, 2, ',', ' '); ?> €</td>
            </tr>
            <tr>
                <td>TVA (20%) :</td>
                <td class="text-right"><?php echo number_format($quote['total_price'] - ($quote['total_price'] / 1.2), 2, ',', ' '); ?> €</td>
            </tr>
            <tr class="total-row">
                <td><strong>TOTAL TTC :</strong></td>
                <td class="text-right"><strong><?php echo number_format($quote['total_price'], 2, ',', ' '); ?> €</strong></td>
            </tr>
        </table>
    </div>

    <!-- Conditions -->
    <div class="terms">
        <h4>Conditions générales</h4>
        <p><strong>Validité du devis :</strong> Ce devis est valable 30 jours à compter de sa date d'émission.</p>
        
        <p><strong>Modalités de paiement :</strong> 
        - Acompte de 30% à la confirmation de commande<br>
        - Solde le jour de la prestation</p>
        
        <p><strong>Conditions d'annulation :</strong>
        - Annulation gratuite jusqu'à 48h avant l'événement<br>
        - Annulation entre 48h et 24h : 50% du montant total<br>
        - Annulation moins de 24h : 100% du montant total</p>
        
        <p><strong>Remarques :</strong> 
        Ce devis est établi selon vos indications. Toute modification pourra donner lieu à un avenant.
        Les prix sont exprimés en euros TTC.</p>
        
        <?php if (!empty($customer['message'])): ?>
        <p><strong>Votre demande :</strong><br>
        <em><?php echo esc_html($customer['message']); ?></em></p>
        <?php endif; ?>
    </div>

    <!-- Footer fixe -->
    <div class="footer">
        <div style="text-align: center;">
            Restaurant Block - 123 Rue de la Gastronomie, 67000 Strasbourg - Tél: 03 88 XX XX XX - SIRET: 123 456 789 01234
        </div>
    </div>
</body>
</html>
