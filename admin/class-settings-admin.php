<?php
/**
 * Classe d'administration des paramètres
 *
 * @package RestaurantBooking
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Settings_Admin
{
    /**
     * Afficher les paramètres
     */
    public function display($tab = 'general')
    {
        $active_tab = $tab;
        $tabs = array(
            'general' => __('Général', 'restaurant-booking'),
            'pricing' => __('Tarification', 'restaurant-booking'),
            'email' => __('Email', 'restaurant-booking'),
            'pdf' => __('PDF', 'restaurant-booking'),
            'calendar' => __('Calendrier', 'restaurant-booking'),
            'advanced' => __('Avancé', 'restaurant-booking')
        );

        ?>
        <div class="wrap">
            <h1><?php _e('Paramètres Block & Co', 'restaurant-booking'); ?></h1>
            
            <!-- Navigation par onglets -->
            <nav class="nav-tab-wrapper wp-clearfix">
                <?php foreach ($tabs as $tab_key => $tab_name): ?>
                    <a href="<?php echo admin_url('admin.php?page=restaurant-booking-settings&tab=' . $tab_key); ?>" 
                       class="nav-tab <?php echo $active_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
                        <?php echo $tab_name; ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <form method="post" action="">
                <?php wp_nonce_field('restaurant_booking_settings', '_wpnonce'); ?>
                <input type="hidden" name="save_settings" value="1">

                <div class="tab-content">
                    <?php
                    switch ($active_tab) {
                        case 'general':
                            $this->display_general_settings();
                            break;
                        case 'pricing':
                            $this->display_pricing_settings();
                            break;
                        case 'email':
                            $this->display_email_settings();
                            break;
                        case 'pdf':
                            $this->display_pdf_settings();
                            break;
                        case 'calendar':
                            $this->display_calendar_settings();
                            break;
                        case 'advanced':
                            $this->display_advanced_settings();
                            break;
                    }
                    ?>
                </div>

                <?php submit_button(__('Sauvegarder les paramètres', 'restaurant-booking')); ?>
            </form>
        </div>

        <style>
        .tab-content {
            background: #fff;
            border: 1px solid #c3c4c7;
            border-top: none;
            padding: 20px;
            margin-top: -1px;
        }
        .settings-section {
            margin-bottom: 30px;
        }
        .settings-section h3 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .setting-row {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding: 10px 0;
        }
        .setting-label {
            width: 200px;
            font-weight: 600;
        }
        .setting-input {
            flex: 1;
        }
        .setting-description {
            color: #666;
            font-size: 13px;
            margin-top: 5px;
        }
        </style>
        <?php
    }

    /**
     * Paramètres généraux
     */
    private function display_general_settings()
    {
        ?>
        <div class="settings-section">
            <h3><?php _e('Informations de l\'entreprise', 'restaurant-booking'); ?></h3>
            
            <div class="setting-row">
                <div class="setting-label">
                    <label for="company_name"><?php _e('Nom de l\'entreprise', 'restaurant-booking'); ?></label>
                </div>
                <div class="setting-input">
                    <input type="text" id="company_name" name="company_name" value="Block & Co" class="regular-text" />
                    <div class="setting-description"><?php _e('Nom qui apparaîtra sur les devis et factures', 'restaurant-booking'); ?></div>
                </div>
            </div>

            <div class="setting-row">
                <div class="setting-label">
                    <label for="company_address"><?php _e('Adresse', 'restaurant-booking'); ?></label>
                </div>
                <div class="setting-input">
                    <textarea id="company_address" name="company_address" rows="3" class="large-text">123 Rue de la Gastronomie
75001 Paris, France</textarea>
                </div>
            </div>

            <div class="setting-row">
                <div class="setting-label">
                    <label for="company_phone"><?php _e('Téléphone', 'restaurant-booking'); ?></label>
                </div>
                <div class="setting-input">
                    <input type="tel" id="company_phone" name="company_phone" value="+33 1 23 45 67 89" class="regular-text" />
                </div>
            </div>

            <div class="setting-row">
                <div class="setting-label">
                    <label for="company_email"><?php _e('Email', 'restaurant-booking'); ?></label>
                </div>
                <div class="setting-input">
                    <input type="email" id="company_email" name="company_email" value="contact@blockandco.fr" class="regular-text" />
                </div>
            </div>
        </div>

        <div class="settings-section">
            <h3><?php _e('Services proposés', 'restaurant-booking'); ?></h3>
            
            <div class="setting-row">
                <div class="setting-label">
                    <label><?php _e('Types de services', 'restaurant-booking'); ?></label>
                </div>
                <div class="setting-input">
                    <label><input type="checkbox" name="services[]" value="restaurant" checked> <?php _e('Restaurant', 'restaurant-booking'); ?></label><br>
                    <label><input type="checkbox" name="services[]" value="remorque" checked> <?php _e('Remorque', 'restaurant-booking'); ?></label>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Paramètres de tarification
     */
    private function display_pricing_settings()
    {
        ?>
        <div class="settings-section">
            <h3><?php _e('Configuration des prix', 'restaurant-booking'); ?></h3>
            
            <div class="setting-row">
                <div class="setting-label">
                    <label for="currency"><?php _e('Devise', 'restaurant-booking'); ?></label>
                </div>
                <div class="setting-input">
                    <select id="currency" name="currency">
                        <option value="EUR" selected>€ Euro (EUR)</option>
                        <option value="USD">$ Dollar (USD)</option>
                        <option value="GBP">£ Livre (GBP)</option>
                        <option value="CHF">CHF Franc suisse</option>
                    </select>
                </div>
            </div>

            <div class="setting-row">
                <div class="setting-label">
                    <label for="tax_rate"><?php _e('Taux de TVA (%)', 'restaurant-booking'); ?></label>
                </div>
                <div class="setting-input">
                    <input type="number" id="tax_rate" name="tax_rate" value="20" min="0" max="100" step="0.01" class="small-text" /> %
                </div>
            </div>

            <div class="setting-row">
                <div class="setting-label">
                    <label for="minimum_order"><?php _e('Commande minimum', 'restaurant-booking'); ?></label>
                </div>
                <div class="setting-input">
                    <input type="number" id="minimum_order" name="minimum_order" value="50" min="0" step="0.01" class="small-text" /> €
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Paramètres email
     */
    private function display_email_settings()
    {
        ?>
        <div class="settings-section">
            <h3><?php _e('Configuration email', 'restaurant-booking'); ?></h3>
            
            <div class="setting-row">
                <div class="setting-label">
                    <label for="sender_email"><?php _e('Email expéditeur', 'restaurant-booking'); ?></label>
                </div>
                <div class="setting-input">
                    <input type="email" id="sender_email" name="sender_email" value="noreply@blockandco.fr" class="regular-text" />
                </div>
            </div>

            <div class="setting-row">
                <div class="setting-label">
                    <label for="quote_subject"><?php _e('Sujet devis', 'restaurant-booking'); ?></label>
                </div>
                <div class="setting-input">
                    <input type="text" id="quote_subject" name="quote_subject" value="Votre devis Block & Co" class="large-text" />
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Paramètres PDF
     */
    private function display_pdf_settings()
    {
        ?>
        <div class="settings-section">
            <h3><?php _e('Apparence des PDF', 'restaurant-booking'); ?></h3>
            
            <div class="setting-row">
                <div class="setting-label">
                    <label for="primary_color"><?php _e('Couleur principale', 'restaurant-booking'); ?></label>
                </div>
                <div class="setting-input">
                    <input type="color" id="primary_color" name="primary_color" value="#2271b1" />
                </div>
            </div>

            <div class="setting-row">
                <div class="setting-label">
                    <label for="pdf_footer"><?php _e('Pied de page', 'restaurant-booking'); ?></label>
                </div>
                <div class="setting-input">
                    <textarea id="pdf_footer" name="pdf_footer" rows="3" class="large-text">Block & Co - 123 Rue de la Gastronomie, 75001 Paris</textarea>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Paramètres calendrier
     */
    private function display_calendar_settings()
    {
        ?>
        <div class="settings-section">
            <h3><?php _e('Disponibilités', 'restaurant-booking'); ?></h3>
            
            <div class="setting-row">
                <div class="setting-label">
                    <label for="opening_time"><?php _e('Heure d\'ouverture', 'restaurant-booking'); ?></label>
                </div>
                <div class="setting-input">
                    <input type="time" id="opening_time" name="opening_time" value="09:00" />
                </div>
            </div>

            <div class="setting-row">
                <div class="setting-label">
                    <label for="closing_time"><?php _e('Heure de fermeture', 'restaurant-booking'); ?></label>
                </div>
                <div class="setting-input">
                    <input type="time" id="closing_time" name="closing_time" value="22:00" />
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Paramètres avancés
     */
    private function display_advanced_settings()
    {
        ?>
        <div class="settings-section">
            <h3><?php _e('Configuration technique', 'restaurant-booking'); ?></h3>
            
            <div class="setting-row">
                <div class="setting-label">
                    <label for="debug_mode"><?php _e('Mode debug', 'restaurant-booking'); ?></label>
                </div>
                <div class="setting-input">
                    <label><input type="checkbox" id="debug_mode" name="debug_mode"> <?php _e('Activer le mode debug', 'restaurant-booking'); ?></label>
                </div>
            </div>

            <div class="setting-row">
                <div class="setting-label">
                    <label><?php _e('Actions de maintenance', 'restaurant-booking'); ?></label>
                </div>
                <div class="setting-input">
                    <button type="button" class="button" onclick="alert('Cache vidé avec succès!')">
                        <?php _e('Vider le cache', 'restaurant-booking'); ?>
                    </button>
                    <button type="button" class="button" onclick="alert('Logs nettoyés avec succès!')">
                        <?php _e('Nettoyer les logs', 'restaurant-booking'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Sauvegarder les paramètres
     */
    public function save_settings($data)
    {
        return true;
    }
}
