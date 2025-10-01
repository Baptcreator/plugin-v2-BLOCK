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
     * Constructeur
     */
    public function __construct()
    {
        // Les actions AJAX sont maintenant gérées dans class-admin.php
    }
    /**
     * Afficher les paramètres
     */
    public function display($tab = 'general')
    {
        $active_tab = $tab;
        $tabs = array(
            'general' => __('Général', 'restaurant-booking'),
            'email' => __('Email', 'restaurant-booking'),
            'pdf' => __('PDF', 'restaurant-booking'),
            'maps' => __('Maps', 'restaurant-booking'),
            'integration' => __('Shortcode & Intégration', 'restaurant-booking'),
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
                <input type="hidden" name="restaurant_booking_action" value="save_settings">

                <div class="tab-content">
                    <?php
                    switch ($active_tab) {
                        case 'general':
                            $this->display_general_settings();
                            break;
                        case 'email':
                            $this->display_email_settings();
                            break;
                        case 'pdf':
                            $this->display_pdf_settings();
                            break;
                        case 'maps':
                            $this->display_maps_settings();
                            break;
                        case 'integration':
                            $this->display_integration_settings();
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
        
        /* Message d'information pour la réorganisation du calendrier */
        .info-banner {
            background: linear-gradient(135deg, #f0f6fc 0%, #e8f4fd 100%);
            border: 1px solid #0073aa;
            border-left: 4px solid #0073aa;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 115, 170, 0.1);
        }
        
        .info-banner h3 {
            margin-top: 0;
            margin-bottom: 10px;
            color: #0073aa;
            border-bottom: none;
            padding-bottom: 0;
        }
        
        .info-banner p {
            margin-bottom: 15px;
            color: #333;
        }
        
        .info-banner .button {
            font-weight: 600;
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
        
        /* Correction alignement icônes dans les boutons */
        .button .dashicons {
            vertical-align: middle;
            margin-right: 5px;
            margin-top: -2px;
            line-height: 1;
        }
        
        /* Amélioration des boutons avec icônes */
        .button {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .button .dashicons {
            margin-right: 0;
            margin-top: 0;
            flex-shrink: 0;
        }
        </style>
        <?php
    }

    /**
     * Paramètres généraux
     */
    private function display_general_settings()
    {
        // Récupérer les valeurs existantes
        $settings = get_option('restaurant_booking_general_settings', array());
        $company_name = isset($settings['company_name']) ? $settings['company_name'] : 'Block & Co';
        $company_address = isset($settings['company_address']) ? $settings['company_address'] : '123 Rue de la Gastronomie';
        $company_postal_code = isset($settings['company_postal_code']) ? $settings['company_postal_code'] : '75001';
        $company_city = isset($settings['company_city']) ? $settings['company_city'] : 'Paris';
        $company_phone = isset($settings['company_phone']) ? $settings['company_phone'] : '+33 1 23 45 67 89';
        $company_email = isset($settings['company_email']) ? $settings['company_email'] : 'contact@blockandco.fr';
        $company_siret = isset($settings['company_siret']) ? $settings['company_siret'] : '';
        
        ?>
        <!-- Message d'information sur la réorganisation -->
        <div class="settings-section">
            <div class="info-banner calendar-moved">
                <h3>📅 <?php _e('Calendrier & Google Calendar', 'restaurant-booking'); ?></h3>
                <p><?php _e('Les paramètres de calendrier ont été regroupés dans un onglet dédié pour une meilleure organisation.', 'restaurant-booking'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=restaurant-booking-calendar'); ?>" class="button button-primary">
                    📅 <?php _e('Accéder au Calendrier & Google Calendar', 'restaurant-booking'); ?>
                </a>
            </div>
        </div>

        <div class="settings-section">
            <h3>📍 <?php _e('Informations de l\'entreprise', 'restaurant-booking'); ?></h3>
            <p class="description"><?php _e('Ces informations apparaîtront sur vos devis et dans les emails envoyés aux clients.', 'restaurant-booking'); ?></p>
            
            <div class="setting-row">
                <div class="setting-label">
                    <label for="company_name"><?php _e('Nom de l\'entreprise', 'restaurant-booking'); ?></label>
                </div>
                <div class="setting-input">
                    <input type="text" id="company_name" name="company_name" value="<?php echo esc_attr($company_name); ?>" class="regular-text" required />
                    <div class="setting-description"><?php _e('Nom qui apparaîtra sur les devis et emails', 'restaurant-booking'); ?></div>
                </div>
            </div>

            <div class="setting-row">
                <div class="setting-label">
                    <label for="company_address"><?php _e('Adresse', 'restaurant-booking'); ?></label>
                </div>
                <div class="setting-input">
                    <input type="text" id="company_address" name="company_address" value="<?php echo esc_attr($company_address); ?>" class="large-text" />
                    <div class="setting-description"><?php _e('Numéro et nom de rue', 'restaurant-booking'); ?></div>
                </div>
            </div>

            <div class="setting-row">
                <div class="setting-label">
                    <label for="company_postal_code"><?php _e('Code postal', 'restaurant-booking'); ?></label>
                </div>
                <div class="setting-input">
                    <input type="text" id="company_postal_code" name="company_postal_code" value="<?php echo esc_attr($company_postal_code); ?>" class="small-text" pattern="[0-9]{5}" maxlength="5" />
                    <div class="setting-description"><?php _e('5 chiffres (ex: 67000)', 'restaurant-booking'); ?></div>
                </div>
            </div>

            <div class="setting-row">
                <div class="setting-label">
                    <label for="company_city"><?php _e('Ville', 'restaurant-booking'); ?></label>
                </div>
                <div class="setting-input">
                    <input type="text" id="company_city" name="company_city" value="<?php echo esc_attr($company_city); ?>" class="regular-text" />
                    <div class="setting-description"><?php _e('Nom de la ville', 'restaurant-booking'); ?></div>
                </div>
            </div>

            <div class="setting-row">
                <div class="setting-label">
                    <label for="company_phone"><?php _e('Téléphone', 'restaurant-booking'); ?></label>
                </div>
                <div class="setting-input">
                    <input type="tel" id="company_phone" name="company_phone" value="<?php echo esc_attr($company_phone); ?>" class="regular-text" />
                    <div class="setting-description"><?php _e('Format international recommandé (ex: +33 1 23 45 67 89)', 'restaurant-booking'); ?></div>
                </div>
            </div>

            <div class="setting-row">
                <div class="setting-label">
                    <label for="company_email"><?php _e('Email', 'restaurant-booking'); ?></label>
                </div>
                <div class="setting-input">
                    <input type="email" id="company_email" name="company_email" value="<?php echo esc_attr($company_email); ?>" class="regular-text" required />
                    <div class="setting-description"><?php _e('Email principal de contact', 'restaurant-booking'); ?></div>
                </div>
            </div>

            <div class="setting-row">
                <div class="setting-label">
                    <label for="company_siret"><?php _e('SIRET', 'restaurant-booking'); ?></label>
                </div>
                <div class="setting-input">
                    <input type="text" id="company_siret" name="company_siret" value="<?php echo esc_attr($company_siret); ?>" class="regular-text" />
                    <div class="setting-description"><?php _e('Numéro SIRET de l\'entreprise (apparaîtra sur les devis)', 'restaurant-booking'); ?></div>
                </div>
            </div>
        </div>

        <div class="settings-section">
            <h3>ℹ️ <?php _e('Informations système', 'restaurant-booking'); ?></h3>
            <p class="description"><?php _e('Block & Co propose automatiquement les services Restaurant et Remorque selon les besoins du client.', 'restaurant-booking'); ?></p>
            
            <div class="setting-row">
                <div class="setting-label">
                    <strong><?php _e('Services disponibles', 'restaurant-booking'); ?></strong>
                </div>
                <div class="setting-input">
                    <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> <?php _e('Privatisation Restaurant (10-30 personnes)', 'restaurant-booking'); ?><br>
                    <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> <?php _e('Privatisation Remorque Block (20-100+ personnes)', 'restaurant-booking'); ?>
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
        // Récupérer les valeurs existantes
        $settings = get_option('restaurant_booking_email_settings', array());
        $sender_email = isset($settings['sender_email']) ? $settings['sender_email'] : 'noreply@blockandco.fr';
        $sender_name = isset($settings['sender_name']) ? $settings['sender_name'] : 'Block & Co';
        $quote_subject = isset($settings['quote_subject']) ? $settings['quote_subject'] : 'Votre devis Block & Co';
        $admin_email = isset($settings['admin_email']) ? $settings['admin_email'] : get_option('admin_email');
        
        // Nouvelles options pour les notifications admin
        $admin_notification_enabled = isset($settings['admin_notification_enabled']) ? $settings['admin_notification_enabled'] : '1';
        $admin_notification_emails = isset($settings['admin_notification_emails']) ? $settings['admin_notification_emails'] : array(get_option('admin_email'));
        $admin_notification_subject = isset($settings['admin_notification_subject']) ? $settings['admin_notification_subject'] : 'Nouveau devis reçu - Block & Co';
        
        // Vérifier le statut SMTP
        $smtp_status = $this->check_smtp_status();
        
        ?>
        <div class="settings-section">
            <h3>🔍 <?php _e('Statut de la configuration email', 'restaurant-booking'); ?></h3>
            
            <div class="email-status-card <?php echo $smtp_status['configured'] ? 'configured' : 'not-configured'; ?>">
                <div class="status-indicator">
                    <?php echo $smtp_status['configured'] ? '✅' : '⚠️'; ?>
                    <strong><?php echo $smtp_status['configured'] ? __('SMTP Configuré', 'restaurant-booking') : __('SMTP Non configuré', 'restaurant-booking'); ?></strong>
                </div>
                <div class="status-details">
                    <?php if ($smtp_status['plugin'] !== 'none'): ?>
                        <p><?php printf(__('Plugin détecté : %s', 'restaurant-booking'), '<strong>' . $smtp_status['plugin'] . '</strong>'); ?></p>
                    <?php else: ?>
                        <p style="color: #d63638;"><?php _e('⚠️ Aucun plugin SMTP détecté. Les emails utilisent la fonction mail() de PHP (moins fiable).', 'restaurant-booking'); ?></p>
                        <p><strong><?php _e('Recommandation :', 'restaurant-booking'); ?></strong> <?php _e('Installez WP Mail SMTP pour améliorer la délivrabilité.', 'restaurant-booking'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="settings-section">
            <h3>✉️ <?php _e('Configuration des emails', 'restaurant-booking'); ?></h3>
            <p class="description"><?php _e('Paramètres des emails envoyés automatiquement aux clients et à l\'administration.', 'restaurant-booking'); ?></p>
            
            <div class="setting-row">
                <div class="setting-label">
                    <label for="sender_email"><?php _e('Email expéditeur', 'restaurant-booking'); ?></label>
                </div>
                <div class="setting-input">
                    <input type="email" id="sender_email" name="sender_email" value="<?php echo esc_attr($sender_email); ?>" class="regular-text" required />
                    <div class="setting-description"><?php _e('Adresse email utilisée pour envoyer les devis aux clients', 'restaurant-booking'); ?></div>
                </div>
            </div>

            <div class="setting-row">
                <div class="setting-label">
                    <label for="sender_name"><?php _e('Nom expéditeur', 'restaurant-booking'); ?></label>
                </div>
                <div class="setting-input">
                    <input type="text" id="sender_name" name="sender_name" value="<?php echo esc_attr($sender_name); ?>" class="regular-text" />
                    <div class="setting-description"><?php _e('Nom qui apparaîtra comme expéditeur des emails', 'restaurant-booking'); ?></div>
                </div>
            </div>

            <div class="setting-row">
                <div class="setting-label">
                    <label for="quote_subject"><?php _e('Sujet des devis', 'restaurant-booking'); ?></label>
                </div>
                <div class="setting-input">
                    <input type="text" id="quote_subject" name="quote_subject" value="<?php echo esc_attr($quote_subject); ?>" class="large-text" />
                    <div class="setting-description"><?php _e('Sujet des emails de devis envoyés aux clients', 'restaurant-booking'); ?></div>
                </div>
            </div>

        </div>

        <div class="settings-section">
            <h3>🔔 <?php _e('Notifications administrateur', 'restaurant-booking'); ?></h3>
            <p class="description"><?php _e('Configuration des notifications email envoyées à l\'administration lors de la réception de nouveaux devis.', 'restaurant-booking'); ?></p>
            
            <div class="setting-row">
                <div class="setting-label">
                    <label for="admin_notification_enabled"><?php _e('Activer les notifications', 'restaurant-booking'); ?></label>
                </div>
                <div class="setting-input">
                    <label class="switch">
                        <input type="checkbox" id="admin_notification_enabled" name="admin_notification_enabled" value="1" <?php checked($admin_notification_enabled, '1'); ?> />
                        <span class="slider"></span>
                    </label>
                    <div class="setting-description"><?php _e('Recevoir un email à chaque nouveau devis soumis sur le site', 'restaurant-booking'); ?></div>
                </div>
            </div>

            <div class="setting-row admin-notification-settings" <?php echo $admin_notification_enabled !== '1' ? 'style="opacity: 0.5;"' : ''; ?>>
                <div class="setting-label">
                    <label for="admin_notification_subject"><?php _e('Sujet de notification', 'restaurant-booking'); ?></label>
                </div>
                <div class="setting-input">
                    <input type="text" id="admin_notification_subject" name="admin_notification_subject" value="<?php echo esc_attr($admin_notification_subject); ?>" class="large-text" />
                    <div class="setting-description"><?php _e('Sujet des emails de notification envoyés aux administrateurs', 'restaurant-booking'); ?></div>
                </div>
            </div>

            <div class="setting-row admin-notification-settings" <?php echo $admin_notification_enabled !== '1' ? 'style="opacity: 0.5;"' : ''; ?>>
                <div class="setting-label">
                    <label for="admin_notification_emails"><?php _e('Emails administrateur', 'restaurant-booking'); ?></label>
                </div>
                <div class="setting-input">
                    <div id="admin-emails-container">
                        <?php 
                        if (empty($admin_notification_emails)) {
                            $admin_notification_emails = array(get_option('admin_email'));
                        }
                        foreach ($admin_notification_emails as $index => $email): 
                        ?>
                            <div class="admin-email-row" data-index="<?php echo $index; ?>">
                                <input type="email" name="admin_notification_emails[]" value="<?php echo esc_attr($email); ?>" class="regular-text" placeholder="admin@example.com" />
                                <?php if ($index > 0): ?>
                                    <button type="button" class="button button-secondary remove-email-btn">❌ <?php _e('Supprimer', 'restaurant-booking'); ?></button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" id="add-admin-email" class="button button-secondary">
                        ➕ <?php _e('Ajouter un email', 'restaurant-booking'); ?>
                    </button>
                    <div class="setting-description">
                        <?php _e('Adresses email qui recevront les notifications de nouveaux devis. Vous pouvez ajouter plusieurs emails.', 'restaurant-booking'); ?>
                    </div>
                </div>
            </div>

            <!-- Email de compatibilité (pour les anciens paramètres) -->
            <div class="setting-row" style="display: none;">
                <input type="email" name="admin_email" value="<?php echo esc_attr($admin_email); ?>" />
            </div>
        </div>

        <div class="settings-section">
            <h3>🧪 <?php _e('Test d\'envoi d\'email', 'restaurant-booking'); ?></h3>
            <p class="description"><?php _e('Testez votre configuration email en envoyant un email de test.', 'restaurant-booking'); ?></p>
            
            <div class="setting-row">
                <div class="setting-label">
                    <label for="test_email"><?php _e('Email de test', 'restaurant-booking'); ?></label>
                </div>
                <div class="setting-input">
                    <input type="email" id="test_email" name="test_email" value="<?php echo esc_attr(get_option('admin_email')); ?>" class="regular-text" />
                    <button type="button" id="send_test_email" class="button button-secondary">
                        <span class="dashicons dashicons-email-alt"></span><?php _e('Envoyer email de test', 'restaurant-booking'); ?>
                    </button>
                    <button type="button" id="send_test_admin_notification" class="button button-secondary" style="margin-left: 10px;">
                        <span class="dashicons dashicons-bell"></span><?php _e('Test notification admin', 'restaurant-booking'); ?>
                    </button>
                    <div id="test_email_result" style="margin-top: 10px;"></div>
                    <div id="test_admin_notification_result" style="margin-top: 10px;"></div>
                </div>
            </div>
        </div>

        <div class="settings-section">
            <h3>📊 <?php _e('Statistiques d\'envoi', 'restaurant-booking'); ?></h3>
            <?php
            $stats = $this->get_email_stats();
            ?>
            <div class="email-stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_sent']; ?></div>
                    <div class="stat-label"><?php _e('Emails envoyés ce mois', 'restaurant-booking'); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['success_rate']; ?>%</div>
                    <div class="stat-label"><?php _e('Taux de succès', 'restaurant-booking'); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['last_error_count']; ?></div>
                    <div class="stat-label"><?php _e('Erreurs cette semaine', 'restaurant-booking'); ?></div>
                </div>
            </div>
        </div>

        <style>
        .email-status-card {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }
        .email-status-card.configured {
            background: #f0f9ff;
            border-color: #46b450;
        }
        .email-status-card.not-configured {
            background: #fef7f0;
            border-color: #d63638;
        }
        .status-indicator {
            font-size: 16px;
            margin-bottom: 10px;
        }
        .status-details p {
            margin: 5px 0;
        }
        .email-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }
        .stat-card {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #ddd;
            text-align: center;
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #2271b1;
            margin-bottom: 5px;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        #test_email_result {
            padding: 10px;
            border-radius: 4px;
            display: none;
        }
        #test_email_result.success {
            background: #d1edff;
            color: #0073aa;
            border: 1px solid #0073aa;
            display: block;
        }
        #test_email_result.error {
            background: #ffeaea;
            color: #d63638;
            border: 1px solid #d63638;
            display: block;
        }
        #test_admin_notification_result {
            padding: 10px;
            border-radius: 4px;
            display: none;
        }
        #test_admin_notification_result.success {
            background: #d1edff;
            color: #0073aa;
            border: 1px solid #0073aa;
            display: block;
        }
        #test_admin_notification_result.error {
            background: #ffeaea;
            color: #d63638;
            border: 1px solid #d63638;
            display: block;
        }
        
        /* Styles pour les notifications admin */
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #2196F3;
        }
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        .admin-email-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        .admin-email-row input[type="email"] {
            flex: 1;
        }
        .remove-email-btn {
            color: #d63638 !important;
            border-color: #d63638 !important;
        }
        .remove-email-btn:hover {
            background-color: #d63638 !important;
            color: white !important;
        }
        #add-admin-email {
            margin-top: 10px;
            color: #2271b1 !important;
            border-color: #2271b1 !important;
        }
        #add-admin-email:hover {
            background-color: #2271b1 !important;
            color: white !important;
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Gestion de l'activation/désactivation des notifications admin
            $('#admin_notification_enabled').change(function() {
                var isEnabled = $(this).is(':checked');
                $('.admin-notification-settings').css('opacity', isEnabled ? '1' : '0.5');
                $('.admin-notification-settings input, .admin-notification-settings button').prop('disabled', !isEnabled);
            });

            // Ajouter un nouvel email admin
            $('#add-admin-email').click(function() {
                var container = $('#admin-emails-container');
                var newIndex = container.find('.admin-email-row').length;
                var newRow = $('<div class="admin-email-row" data-index="' + newIndex + '">' +
                    '<input type="email" name="admin_notification_emails[]" value="" class="regular-text" placeholder="admin@example.com" />' +
                    '<button type="button" class="button button-secondary remove-email-btn">❌ <?php _e('Supprimer', 'restaurant-booking'); ?></button>' +
                    '</div>');
                container.append(newRow);
            });

            // Supprimer un email admin
            $(document).on('click', '.remove-email-btn', function() {
                $(this).closest('.admin-email-row').remove();
            });

            // Test de notification admin
            $('#send_test_admin_notification').click(function() {
                var button = $(this);
                var result = $('#test_admin_notification_result');
                
                button.prop('disabled', true).text('Test en cours...');
                result.hide();
                
                $.post(ajaxurl, {
                    action: 'restaurant_booking_test_admin_notification',
                    _wpnonce: '<?php echo wp_create_nonce('restaurant_booking_test_admin_notification'); ?>'
                }, function(response) {
                    if (response.success) {
                        result.removeClass('error').addClass('success').text('✅ ' + response.data.message).show();
                    } else {
                        result.removeClass('success').addClass('error').text('❌ ' + (response.data ? response.data.message : 'Erreur inconnue')).show();
                    }
                }).fail(function() {
                    result.removeClass('success').addClass('error').text('❌ Erreur de connexion au serveur.').show();
                }).always(function() {
                    button.prop('disabled', false).html('<span class="dashicons dashicons-bell"></span><?php _e('Test notification admin', 'restaurant-booking'); ?>');
                });
            });

            // Test d'email
            $('#send_test_email').click(function() {
                var button = $(this);
                var result = $('#test_email_result');
                var testEmail = $('#test_email').val();
                
                if (!testEmail || !testEmail.includes('@')) {
                    result.removeClass('success').addClass('error').text('Veuillez saisir une adresse email valide.').show();
                    return;
                }
                
                button.prop('disabled', true).text('Envoi en cours...');
                result.hide();
                
                $.post(ajaxurl, {
                    action: 'restaurant_booking_test_email',
                    email: testEmail,
                    nonce: '<?php echo wp_create_nonce('test_email'); ?>'
                }, function(response) {
                    if (response.success) {
                        result.removeClass('error').addClass('success').text('✅ ' + response.data).show();
                    } else {
                        result.removeClass('success').addClass('error').text('❌ Erreur : ' + response.data).show();
                    }
                }).fail(function(xhr, status, error) {
                    console.error('AJAX Error:', status, error, xhr.responseText);
                    result.removeClass('success').addClass('error').text('❌ Erreur de connexion : ' + error + '. Vérifiez la console.').show();
                }).always(function() {
                    button.prop('disabled', false).html('<span class="dashicons dashicons-email-alt"></span>Envoyer email de test');
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Vérifier le statut SMTP
     */
    private function check_smtp_status()
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
            $status['configured'] = true; // Assume configured if active
        }

        return $status;
    }

    /**
     * Obtenir les statistiques d'email
     */
    private function get_email_stats()
    {
        global $wpdb;
        
        // Compter les emails envoyés ce mois depuis les logs
        $current_month = date('Y-m');
        $total_sent = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}restaurant_logs 
            WHERE message LIKE %s 
            AND DATE_FORMAT(created_at, '%%Y-%%m') = %s
        ", '%Email de devis envoyé%', $current_month));
        
        // Compter les erreurs cette semaine
        $last_error_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}restaurant_logs 
            WHERE level = 'error' 
            AND message LIKE %s 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ", '%email%'));
        
        // Calculer le taux de succès (approximatif)
        $total_attempts = $total_sent + $last_error_count;
        $success_rate = $total_attempts > 0 ? round(($total_sent / $total_attempts) * 100) : 100;
        
        return array(
            'total_sent' => (int) $total_sent,
            'success_rate' => $success_rate,
            'last_error_count' => (int) $last_error_count
        );
    }

    /**
     * Paramètres PDF
     */
    private function display_pdf_settings()
    {
        // Récupérer les valeurs existantes
        $settings = get_option('restaurant_booking_pdf_settings', array());
        $logo_id = isset($settings['logo_id']) ? $settings['logo_id'] : '';
        $primary_color = isset($settings['primary_color']) ? $settings['primary_color'] : '#FFB404';
        $secondary_color = isset($settings['secondary_color']) ? $settings['secondary_color'] : '#243127';
        $footer_text = isset($settings['footer_text']) ? $settings['footer_text'] : 'Block & Co - 123 Rue de la Gastronomie, 67000 Strasbourg';
        $quote_validity = isset($settings['quote_validity']) ? $settings['quote_validity'] : 'Ce devis est valable 30 jours à compter de sa date d\'émission.';
        $payment_terms = isset($settings['payment_terms']) ? $settings['payment_terms'] : '- Acompte de 30% à la confirmation de commande\n- Solde le jour de la prestation';
        $cancellation_terms = isset($settings['cancellation_terms']) ? $settings['cancellation_terms'] : '- Annulation gratuite jusqu\'à 48h avant l\'événement\n- Annulation entre 48h et 24h : 50% du montant total\n- Annulation moins de 24h : 100% du montant total';
        $general_remarks = isset($settings['general_remarks']) ? $settings['general_remarks'] : 'Ce devis est établi selon vos indications. Toute modification pourra donner lieu à un avenant.\nLes prix sont exprimés en euros TTC.';
        
        ?>
        <div class="settings-section">
            <h3>📄 <?php _e('Génération de devis', 'restaurant-booking'); ?></h3>
            <p class="description"><?php _e('Configuration du format de devis envoyé aux clients. Utilise du HTML optimisé pour l\'impression.', 'restaurant-booking'); ?></p>
            
            <div class="pdf-method-info">
                <div class="method-card">
                    <div class="method-icon">✅</div>
                    <div class="method-details">
                        <h4><?php _e('Format HTML Optimisé', 'restaurant-booking'); ?></h4>
                        <p><?php _e('Les devis sont générés en HTML parfaitement optimisé pour l\'impression. Compatible avec tous les navigateurs et hébergements.', 'restaurant-booking'); ?></p>
                        <ul>
                            <li>✓ <?php _e('100% fiable sur tous les serveurs', 'restaurant-booking'); ?></li>
                            <li>✓ <?php _e('Impression parfaite depuis le navigateur', 'restaurant-booking'); ?></li>
                            <li>✓ <?php _e('Personnalisation complète', 'restaurant-booking'); ?></li>
                            <li>✓ <?php _e('Aucune dépendance externe', 'restaurant-booking'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="settings-section">
            <h3>🎨 <?php _e('Personnalisation des devis', 'restaurant-booking'); ?></h3>
            
            <div class="setting-row">
                <div class="setting-label">
                    <label for="pdf_logo"><?php _e('Logo entreprise', 'restaurant-booking'); ?></label>
                </div>
                <div class="setting-input">
                    <div class="media-upload-container">
                        <input type="hidden" id="pdf_logo_id" name="pdf_logo_id" value="<?php echo esc_attr($logo_id); ?>" />
                        <div id="pdf_logo_preview">
                            <?php if ($logo_id): 
                                $logo_url = wp_get_attachment_image_url($logo_id, 'medium');
                                if ($logo_url): ?>
                                    <img src="<?php echo esc_url($logo_url); ?>" alt="Logo" style="max-width: 200px; max-height: 100px;" />
                                <?php endif;
                            endif; ?>
                        </div>
                        <button type="button" id="upload_pdf_logo" class="button">
                            <span class="dashicons dashicons-format-image"></span><?php _e('Choisir le logo', 'restaurant-booking'); ?>
                        </button>
                        <?php if ($logo_id): ?>
                            <button type="button" id="remove_pdf_logo" class="button button-secondary">
                                <span class="dashicons dashicons-no-alt"></span><?php _e('Supprimer', 'restaurant-booking'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="setting-description"><?php _e('Logo qui apparaîtra en en-tête des devis (format recommandé : PNG, 300x150px max)', 'restaurant-booking'); ?></div>
                </div>
            </div>

            <div class="setting-row">
                <div class="setting-label">
                    <label for="primary_color"><?php _e('Couleur principale', 'restaurant-booking'); ?></label>
                </div>
                <div class="setting-input">
                    <input type="color" id="primary_color" name="primary_color" value="<?php echo esc_attr($primary_color); ?>" />
                    <div class="setting-description"><?php _e('Couleur utilisée pour les titres et éléments importants (défaut : orange Block)', 'restaurant-booking'); ?></div>
                </div>
            </div>

            <div class="setting-row">
                <div class="setting-label">
                    <label for="secondary_color"><?php _e('Couleur secondaire', 'restaurant-booking'); ?></label>
                </div>
                <div class="setting-input">
                    <input type="color" id="secondary_color" name="secondary_color" value="<?php echo esc_attr($secondary_color); ?>" />
                    <div class="setting-description"><?php _e('Couleur utilisée pour les sous-titres et bordures (défaut : vert Block)', 'restaurant-booking'); ?></div>
                </div>
            </div>

            <div class="setting-row">
                <div class="setting-label">
                    <label for="footer_text"><?php _e('Pied de page', 'restaurant-booking'); ?></label>
                </div>
                <div class="setting-input">
                    <textarea id="footer_text" name="footer_text" rows="3" class="large-text" placeholder="Block & Co - Adresse complète"><?php echo esc_textarea($footer_text); ?></textarea>
                    <div class="setting-description"><?php _e('Texte qui apparaîtra en bas de chaque devis', 'restaurant-booking'); ?></div>
                </div>
            </div>
        </div>

        <div class="settings-section">
            <h3>📋 <?php _e('Conditions générales', 'restaurant-booking'); ?></h3>
            <p class="description"><?php _e('Personnalisez les textes des conditions générales qui apparaîtront sur vos devis.', 'restaurant-booking'); ?></p>
            
            <div class="setting-row">
                <div class="setting-label">
                    <label for="quote_validity"><?php _e('Validité du devis', 'restaurant-booking'); ?></label>
                </div>
                <div class="setting-input">
                    <textarea id="quote_validity" name="quote_validity" rows="2" class="large-text"><?php echo esc_textarea($quote_validity); ?></textarea>
                    <div class="setting-description"><?php _e('Texte sur la validité du devis', 'restaurant-booking'); ?></div>
                </div>
            </div>

            <div class="setting-row">
                <div class="setting-label">
                    <label for="payment_terms"><?php _e('Modalités de paiement', 'restaurant-booking'); ?></label>
                </div>
                <div class="setting-input">
                    <textarea id="payment_terms" name="payment_terms" rows="3" class="large-text"><?php echo esc_textarea($payment_terms); ?></textarea>
                    <div class="setting-description"><?php _e('Conditions de paiement (utilisez \\n pour les retours à la ligne)', 'restaurant-booking'); ?></div>
                </div>
            </div>

            <div class="setting-row">
                <div class="setting-label">
                    <label for="cancellation_terms"><?php _e('Conditions d\'annulation', 'restaurant-booking'); ?></label>
                </div>
                <div class="setting-input">
                    <textarea id="cancellation_terms" name="cancellation_terms" rows="4" class="large-text"><?php echo esc_textarea($cancellation_terms); ?></textarea>
                    <div class="setting-description"><?php _e('Politique d\'annulation (utilisez \\n pour les retours à la ligne)', 'restaurant-booking'); ?></div>
                </div>
            </div>

            <div class="setting-row">
                <div class="setting-label">
                    <label for="general_remarks"><?php _e('Remarques générales', 'restaurant-booking'); ?></label>
                </div>
                <div class="setting-input">
                    <textarea id="general_remarks" name="general_remarks" rows="3" class="large-text"><?php echo esc_textarea($general_remarks); ?></textarea>
                    <div class="setting-description"><?php _e('Remarques et mentions légales (utilisez \\n pour les retours à la ligne)', 'restaurant-booking'); ?></div>
                </div>
            </div>
        </div>

        <div class="settings-section">
            <h3>👁️ <?php _e('Prévisualisation', 'restaurant-booking'); ?></h3>
            <p class="description"><?php _e('Testez l\'apparence de vos devis avec vos paramètres actuels.', 'restaurant-booking'); ?></p>
            
            <div class="setting-row">
                <div class="setting-label">
                    <strong><?php _e('Aperçu du devis', 'restaurant-booking'); ?></strong>
                </div>
                <div class="setting-input">
                    <button type="button" id="preview_pdf" class="button button-secondary">
                        <span class="dashicons dashicons-visibility"></span><?php _e('Prévisualiser un exemple de devis', 'restaurant-booking'); ?>
                    </button>
                    <div class="setting-description"><?php _e('Ouvre un exemple de devis dans un nouvel onglet avec vos paramètres actuels', 'restaurant-booking'); ?></div>
                </div>
            </div>
        </div>

        <style>
        .pdf-method-info {
            margin-bottom: 30px;
        }
        .method-card {
            display: flex;
            align-items: flex-start;
            background: #f0f9ff;
            border: 1px solid #0073aa;
            border-radius: 8px;
            padding: 20px;
            gap: 15px;
        }
        .method-icon {
            font-size: 24px;
            flex-shrink: 0;
        }
        .method-details h4 {
            margin: 0 0 10px 0;
            color: #0073aa;
        }
        .method-details p {
            margin: 0 0 10px 0;
        }
        .method-details ul {
            margin: 0;
            padding-left: 20px;
        }
        .method-details li {
            margin: 5px 0;
            color: #46b450;
        }
        .media-upload-container {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        #pdf_logo_preview {
            margin-right: 10px;
        }
        #pdf_logo_preview img {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px;
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            var mediaUploader;
            
            // Sélecteur de logo
            $('#upload_pdf_logo').click(function(e) {
                e.preventDefault();
                
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                
                mediaUploader = wp.media({
                    title: 'Choisir le logo pour les devis',
                    button: {
                        text: 'Utiliser ce logo'
                    },
                    multiple: false,
                    library: {
                        type: 'image'
                    }
                });
                
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#pdf_logo_id').val(attachment.id);
                    $('#pdf_logo_preview').html('<img src="' + attachment.sizes.medium.url + '" alt="Logo" style="max-width: 200px; max-height: 100px;" />');
                    
                    // Ajouter le bouton supprimer s'il n'existe pas
                    if (!$('#remove_pdf_logo').length) {
                        $('#upload_pdf_logo').after('<button type="button" id="remove_pdf_logo" class="button button-secondary"><span class="dashicons dashicons-no-alt"></span>Supprimer</button>');
                    }
                });
                
                mediaUploader.open();
            });
            
            // Supprimer le logo
            $(document).on('click', '#remove_pdf_logo', function() {
                $('#pdf_logo_id').val('');
                $('#pdf_logo_preview').empty();
                $(this).remove();
            });
            
            // Prévisualisation
            $('#preview_pdf').click(function() {
                var button = $(this);
                button.prop('disabled', true).text('Génération...');
                
                // Ouvrir la prévisualisation dans un nouvel onglet
                var previewUrl = '<?php echo admin_url('admin-ajax.php'); ?>?action=restaurant_booking_preview_pdf&nonce=<?php echo wp_create_nonce('preview_pdf'); ?>';
                window.open(previewUrl, '_blank');
                
                setTimeout(function() {
                    button.prop('disabled', false).html('<span class="dashicons dashicons-visibility"></span>Prévisualiser un exemple de devis');
                }, 2000);
            });
        });
        </script>
        <?php
    }

    /**
     * Paramètres calendrier
     */
    private function display_calendar_settings()
    {
        // Récupérer les valeurs existantes
        $settings = get_option('restaurant_booking_calendar_settings', array());
        
        // Vérifier le statut Google Calendar
        $google_calendar_status = $this->check_google_calendar_status();
        
        ?>
        <div class="settings-section">
            <h3>🔗 <?php _e('Connexion Google Calendar', 'restaurant-booking'); ?></h3>
            <p class="description"><?php _e('Synchronisez automatiquement les disponibilités avec votre agenda Google.', 'restaurant-booking'); ?></p>
            
            <div class="calendar-status-card <?php echo $google_calendar_status['connected'] ? 'connected' : 'not-connected'; ?>">
                <div class="status-indicator">
                    <?php echo $google_calendar_status['connected'] ? '✅' : '⚠️'; ?>
                    <strong><?php echo $google_calendar_status['connected'] ? __('Google Calendar Connecté', 'restaurant-booking') : __('Google Calendar Non connecté', 'restaurant-booking'); ?></strong>
                </div>
                <div class="status-details">
                    <?php if ($google_calendar_status['connected']): ?>
                        <p><?php _e('✓ Synchronisation automatique des disponibilités active', 'restaurant-booking'); ?></p>
                        <p><?php printf(__('Calendrier : %s', 'restaurant-booking'), $google_calendar_status['calendar_name']); ?></p>
                        <button type="button" class="button button-secondary" id="disconnect_google_calendar">
                            <?php _e('Déconnecter', 'restaurant-booking'); ?>
                        </button>
                    <?php else: ?>
                        <p style="color: #d63638;"><?php _e('⚠️ Les dates bloquées doivent être gérées manuellement.', 'restaurant-booking'); ?></p>
                        <p><strong><?php _e('Avantages de la connexion :', 'restaurant-booking'); ?></strong></p>
                        <ul>
                            <li><?php _e('• Synchronisation automatique des événements', 'restaurant-booking'); ?></li>
                            <li><?php _e('• Blocage automatique des dates occupées', 'restaurant-booking'); ?></li>
                            <li><?php _e('• Mise à jour en temps réel', 'restaurant-booking'); ?></li>
                        </ul>
                        <button type="button" class="button button-primary" id="connect_google_calendar">
                            <span class="dashicons dashicons-calendar-alt"></span><?php _e('Connecter Google Calendar', 'restaurant-booking'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>


        <div class="settings-section">
            <h3>🚫 <?php _e('Dates bloquées manuellement', 'restaurant-booking'); ?></h3>
            <p class="description"><?php _e('Gérez les dates indisponibles directement depuis cette interface.', 'restaurant-booking'); ?></p>
            
            <div class="setting-row">
                <div class="setting-label">
                    <strong><?php _e('Gestion des blocages', 'restaurant-booking'); ?></strong>
                </div>
                <div class="setting-input">
                    <button type="button" class="button button-secondary" id="manage_blocked_dates">
                        <span class="dashicons dashicons-calendar"></span><?php _e('Gérer les dates bloquées', 'restaurant-booking'); ?>
                    </button>
                    <div class="setting-description"><?php _e('Ouvre l\'interface de gestion du calendrier dans un nouvel onglet', 'restaurant-booking'); ?></div>
                    
                    <div id="blocked_dates_summary" style="margin-top: 15px;">
                        <?php
                        $blocked_dates = $this->get_blocked_dates_summary();
                        if ($blocked_dates['count'] > 0): ?>
                            <div class="blocked-dates-info">
                                <strong><?php printf(__('%d dates actuellement bloquées', 'restaurant-booking'), $blocked_dates['count']); ?></strong>
                                <ul style="margin: 10px 0; padding-left: 20px;">
                                    <?php foreach ($blocked_dates['recent'] as $date): ?>
                                        <li><?php echo date_i18n('d/m/Y', strtotime($date['date'])); ?> - <?php echo esc_html($date['reason']); ?></li>
                                    <?php endforeach; ?>
                                    <?php if ($blocked_dates['count'] > 5): ?>
                                        <li><em><?php printf(__('... et %d autres', 'restaurant-booking'), $blocked_dates['count'] - 5); ?></em></li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        <?php else: ?>
                            <p><em><?php _e('Aucune date bloquée actuellement', 'restaurant-booking'); ?></em></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="settings-section">
            <h3>📅 <?php _e('Interface de Sélection de Date', 'restaurant-booking'); ?></h3>
            <p class="description"><?php _e('Choisissez comment les clients sélectionnent la date dans le formulaire de devis.', 'restaurant-booking'); ?></p>
            
            <?php 
            // Récupérer l'option du calendrier (option séparée)
            $use_calendar_widget = get_option('restaurant_booking_use_calendar_widget', false);
            ?>
            
            <div class="setting-row">
                <div class="setting-label">
                    <strong><?php _e('Type de calendrier', 'restaurant-booking'); ?></strong>
                </div>
                <div class="setting-input">
                    <label class="calendar-option">
                        <input type="radio" name="calendar_type" value="simple" <?php checked(!$use_calendar_widget); ?> />
                        <div class="option-content">
                            <strong><?php _e('Calendrier Simple (Actuel)', 'restaurant-booking'); ?></strong>
                            <p class="option-description"><?php _e('Champ de date standard du navigateur. Simple et compatible avec tous les navigateurs.', 'restaurant-booking'); ?></p>
                        </div>
                    </label>
                    
                    <label class="calendar-option">
                        <input type="radio" name="calendar_type" value="advanced" <?php checked($use_calendar_widget); ?> />
                        <div class="option-content">
                            <strong><?php _e('🆕 Calendrier Interactif avec Créneaux', 'restaurant-booking'); ?></strong>
                            <p class="option-description">
                                <?php _e('<strong>Nouveau !</strong> Calendrier visuel qui affiche les créneaux horaires bloqués depuis Google Calendar.', 'restaurant-booking'); ?><br>
                                <strong><?php _e('Fonctionnalités :', 'restaurant-booking'); ?></strong><br>
                                • <?php _e('Vue mensuelle avec navigation', 'restaurant-booking'); ?><br>
                                • <?php _e('Affichage des créneaux bloqués (ex: "19h-23h BLOQUÉ")', 'restaurant-booking'); ?><br>
                                • <?php _e('Synchronisation avec Google Calendar', 'restaurant-booking'); ?><br>
                                • <?php _e('Interface moderne et responsive', 'restaurant-booking'); ?>
                            </p>
                            <?php if (!$google_calendar_status['connected']): ?>
                                <div class="calendar-warning">
                                    <span class="dashicons dashicons-warning"></span>
                                    <?php _e('Recommandé avec Google Calendar connecté pour afficher les créneaux bloqués.', 'restaurant-booking'); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </label>
                    
                    <input type="hidden" name="use_calendar_widget" id="use_calendar_widget" value="<?php echo $use_calendar_widget ? '1' : '0'; ?>" />
                </div>
            </div>
        </div>

        <style>
        .calendar-option {
            display: block;
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fff;
        }
        .calendar-option:hover {
            border-color: #0073aa;
            box-shadow: 0 2px 8px rgba(0,115,170,0.1);
        }
        .calendar-option input[type="radio"] {
            margin-right: 10px;
        }
        .calendar-option input[type="radio"]:checked + .option-content {
            color: #0073aa;
        }
        .calendar-option:has(input[type="radio"]:checked) {
            border-color: #0073aa;
            background: #f0f8ff;
        }
        .option-content strong {
            font-size: 14px;
            display: block;
            margin-bottom: 5px;
        }
        .option-description {
            font-size: 13px;
            color: #666;
            line-height: 1.4;
            margin: 0;
        }
        .calendar-warning {
            margin-top: 10px;
            padding: 8px 12px;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            color: #856404;
            font-size: 12px;
        }
        .calendar-warning .dashicons {
            font-size: 14px;
            margin-right: 5px;
            vertical-align: text-top;
        }
        .calendar-status-card {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }
        .calendar-status-card.connected {
            background: #f0f9ff;
            border-color: #46b450;
        }
        .calendar-status-card.not-connected {
            background: #fef7f0;
            border-color: #d63638;
        }
        .status-indicator {
            font-size: 16px;
            margin-bottom: 10px;
        }
        .status-details p {
            margin: 5px 0;
        }
        .status-details ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .status-details li {
            margin: 3px 0;
        }
        .blocked-dates-info {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Gestion du changement de type de calendrier
            $('input[name="calendar_type"]').change(function() {
                var useAdvanced = $(this).val() === 'interactive';
                $('#use_calendar_widget').val(useAdvanced ? '1' : '0');
            });
            
            // Connecter Google Calendar
            $('#connect_google_calendar').click(function() {
                var button = $(this);
                button.prop('disabled', true).text('Connexion...');
                
                // Rediriger vers la page de configuration Google Calendar
                window.location.href = '<?php echo admin_url('admin.php?page=restaurant-booking-google-calendar'); ?>';
            });
            
            // Déconnecter Google Calendar
            $('#disconnect_google_calendar').click(function() {
                if (confirm('Êtes-vous sûr de vouloir déconnecter Google Calendar ?')) {
                    var button = $(this);
                    button.prop('disabled', true).text('Déconnexion...');
                    
                    $.post(ajaxurl, {
                        action: 'restaurant_booking_disconnect_google_calendar',
                        nonce: '<?php echo wp_create_nonce('disconnect_google_calendar'); ?>'
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Erreur lors de la déconnexion : ' + response.data);
                            button.prop('disabled', false).text('Déconnecter');
                        }
                    });
                }
            });
            
            // Gérer les dates bloquées
            $('#manage_blocked_dates').click(function() {
                window.open('<?php echo admin_url('admin.php?page=restaurant-booking-calendar'); ?>', '_blank');
            });
        });
        </script>
        <?php
    }

    /**
     * Vérifier le statut Google Calendar
     */
    private function check_google_calendar_status()
    {
        // Vérifier si Google Calendar est configuré
        $google_settings = get_option('restaurant_booking_google_calendar', array());
        
        $status = array(
            'connected' => false,
            'calendar_name' => '',
            'last_sync' => ''
        );
        
        if (!empty($google_settings['access_token']) && !empty($google_settings['calendar_id'])) {
            $status['connected'] = true;
            $status['calendar_name'] = $google_settings['calendar_name'] ?? 'Calendrier principal';
            $status['last_sync'] = $google_settings['last_sync'] ?? '';
        }
        
        return $status;
    }

    /**
     * Obtenir un résumé des dates bloquées
     */
    private function get_blocked_dates_summary()
    {
        global $wpdb;
        
        $count = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}restaurant_availability 
            WHERE is_available = 0 
            AND date >= CURDATE()
        ");
        
        $recent = $wpdb->get_results("
            SELECT date, blocked_reason as reason 
            FROM {$wpdb->prefix}restaurant_availability 
            WHERE is_available = 0 
            AND date >= CURDATE()
            ORDER BY date ASC 
            LIMIT 5
        ", ARRAY_A);
        
        return array(
            'count' => (int) $count,
            'recent' => $recent ?: array()
        );
    }

    /**
     * Paramètres d'intégration et shortcodes
     */
    private function display_integration_settings()
    {
        
        ?>
        <div class="wrap">
            <h1><?php _e('Shortcode & Intégration', 'restaurant-booking'); ?></h1>
            
            <!-- Carte d'information principale -->
            <div class="restaurant-booking-info-card">
                <h3>🚀 <?php _e('Shortcode Formulaire V3 - Version Active', 'restaurant-booking'); ?></h3>
                <p><?php _e('Le nouveau shortcode V3 avec design moderne et fonctionnalités améliorées.', 'restaurant-booking'); ?></p>
                <ul>
                    <li><?php _e('✅ Design moderne et responsive', 'restaurant-booking'); ?></li>
                    <li><?php _e('✅ Formulaire multi-étapes fluide', 'restaurant-booking'); ?></li>
                    <li><?php _e('✅ Calculateur de prix en temps réel', 'restaurant-booking'); ?></li>
                    <li><?php _e('✅ Configuration centralisée dans l\'admin', 'restaurant-booking'); ?></li>
                    <li><?php _e('✅ Utilisable partout (pages, articles, widgets)', 'restaurant-booking'); ?></li>
                </ul>
            </div>

            <!-- Avertissement sur l'ancien shortcode -->
            <div class="restaurant-booking-warning-card" style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 20px 0; border-radius: 5px;">
                <h4>⚠️ <?php _e('Important : Shortcode obsolète', 'restaurant-booking'); ?></h4>
                <p><?php _e('L\'ancien shortcode [restaurant_booking_form] a été supprimé. Utilisez uniquement [restaurant_booking_form_v3] pour toutes les intégrations.', 'restaurant-booking'); ?></p>
            </div>

            <!-- Instructions d'utilisation du shortcode -->
            <div class="restaurant-booking-shortcode-usage">
                <h3>📝 <?php _e('Comment utiliser le shortcode', 'restaurant-booking'); ?></h3>
                
                <div class="shortcode-examples">
                    <div class="shortcode-example">
                        <h4><?php _e('Utilisation de base (V3)', 'restaurant-booking'); ?></h4>
                        <div class="code-block">
                            <code>[restaurant_booking_form_v3]</code>
                            <button class="copy-btn" onclick="navigator.clipboard.writeText('[restaurant_booking_form_v3]')"><?php _e('Copier', 'restaurant-booking'); ?></button>
                        </div>
                        <p><?php _e('Copiez ce shortcode et collez-le dans n\'importe quelle page ou article.', 'restaurant-booking'); ?></p>
                    </div>
                    
                    <div class="shortcode-example">
                        <h4><?php _e('Avec options personnalisées (V3)', 'restaurant-booking'); ?></h4>
                        <div class="code-block">
                            <code>[restaurant_booking_form_v3 show_progress="yes" calculator_position="sticky"]</code>
                            <button class="copy-btn" onclick="navigator.clipboard.writeText('[restaurant_booking_form_v3 show_progress=\"yes\" calculator_position=\"sticky\"]')"><?php _e('Copier', 'restaurant-booking'); ?></button>
                        </div>
                        <p><?php _e('Personnalisez l\'affichage avec les paramètres disponibles.', 'restaurant-booking'); ?></p>
                    </div>
                </div>
            </div>
            <div class="restaurant-booking-steps">
                <h3><?php _e('Étapes d\'intégration', 'restaurant-booking'); ?></h3>
                <div class="steps-grid">
                    <div class="step-card">
                        <div class="step-number">1</div>
                        <h4><?php _e('Ouvrir l\'éditeur', 'restaurant-booking'); ?></h4>
                        <p><?php _e('Éditez votre page/article WordPress', 'restaurant-booking'); ?></p>
                    </div>
                    
                    <div class="step-card">
                        <div class="step-number">2</div>
                        <h4><?php _e('Coller le shortcode', 'restaurant-booking'); ?></h4>
                        <p><?php _e('Ajoutez [restaurant_booking_form_v3] où vous voulez', 'restaurant-booking'); ?></p>
                    </div>
                    
                    <div class="step-card">
                        <div class="step-number">3</div>
                        <h4><?php _e('Configurer les textes', 'restaurant-booking'); ?></h4>
                        <p><?php _e('Modifiez les textes dans', 'restaurant-booking'); ?> <a href="<?php echo admin_url('admin.php?page=restaurant-booking-options-unified'); ?>"><?php _e('Options de Configuration', 'restaurant-booking'); ?></a></p>
                    </div>
                    
                    <div class="step-card">
                        <div class="step-number">4</div>
                        <h4><?php _e('Publier', 'restaurant-booking'); ?></h4>
                        <p><?php _e('Sauvegardez et publiez votre page', 'restaurant-booking'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Paramètres disponibles -->
            <div class="restaurant-booking-parameters">
                <h3>⚙️ <?php _e('Paramètres du shortcode', 'restaurant-booking'); ?></h3>
                <div class="parameters-table">
                    <table class="wp-list-table widefat">
                        <thead>
                            <tr>
                                <th><?php _e('Paramètre', 'restaurant-booking'); ?></th>
                                <th><?php _e('Valeurs', 'restaurant-booking'); ?></th>
                                <th><?php _e('Défaut', 'restaurant-booking'); ?></th>
                                <th><?php _e('Description', 'restaurant-booking'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>show_progress</code></td>
                                <td>yes / no</td>
                                <td>yes</td>
                                <td><?php _e('Affiche la barre de progression', 'restaurant-booking'); ?></td>
                            </tr>
                            <tr>
                                <td><code>calculator_position</code></td>
                                <td>sticky / bottom / hidden</td>
                                <td>sticky</td>
                                <td><?php _e('Position du calculateur de prix', 'restaurant-booking'); ?></td>
                            </tr>
                            <tr>
                                <td><code>theme</code></td>
                                <td>block</td>
                                <td>block</td>
                                <td><?php _e('Thème du formulaire', 'restaurant-booking'); ?></td>
                            </tr>
                            <tr>
                                <td><code>custom_class</code></td>
                                <td><?php _e('Texte libre', 'restaurant-booking'); ?></td>
                                <td><?php _e('Aucune', 'restaurant-booking'); ?></td>
                                <td><?php _e('Classe CSS personnalisée', 'restaurant-booking'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Avantages du shortcode -->
            <div class="restaurant-booking-features">
                <h3><?php _e('Avantages du shortcode', 'restaurant-booking'); ?></h3>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">🚀</div>
                        <h4><?php _e('Plus simple', 'restaurant-booking'); ?></h4>
                        <p><?php _e('Un seul shortcode à placer, pas de widget complexe', 'restaurant-booking'); ?></p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">🛠️</div>
                        <h4><?php _e('Plus stable', 'restaurant-booking'); ?></h4>
                        <p><?php _e('Pas de problèmes avec l\'éditeur Elementor', 'restaurant-booking'); ?></p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">⚙️</div>
                        <h4><?php _e('Configuration centralisée', 'restaurant-booking'); ?></h4>
                        <p><?php _e('Tous les textes modifiables depuis l\'admin WordPress', 'restaurant-booking'); ?></p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">🎨</div>
                        <h4><?php _e('Même design', 'restaurant-booking'); ?></h4>
                        <p><?php _e('Style identique au widget original', 'restaurant-booking'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Lien vers la configuration -->
            <div class="restaurant-booking-config-link">
                <h3>🔗 <?php _e('Configuration des textes', 'restaurant-booking'); ?></h3>
                <p><?php _e('Tous les textes du formulaire sont modifiables depuis la page d\'options :', 'restaurant-booking'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=restaurant-booking-options-unified'); ?>" class="button button-primary button-large">
                    <?php _e('📝 Configurer les textes du formulaire', 'restaurant-booking'); ?>
                </a>
            </div>
        </div>

        <style>
        .restaurant-booking-info-card {
            background: #f0f6fc;
            border: 1px solid #0073aa;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
        }
        .restaurant-booking-info-card h3 {
            margin-top: 0;
            color: #0073aa;
        }
        .restaurant-booking-info-card ul {
            margin-bottom: 0;
        }
        
        .restaurant-booking-shortcode-usage {
            margin: 30px 0;
            background: #fff;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            padding: 20px;
        }
        .shortcode-examples {
            display: grid;
            gap: 20px;
            margin-top: 20px;
        }
        .shortcode-example {
            background: #f6f7f7;
            border-radius: 4px;
            padding: 15px;
        }
        .code-block {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            border-radius: 4px;
            font-family: monospace;
            margin: 10px 0;
            position: relative;
        }
        .code-block code {
            background: none;
            color: #d4d4d4;
            padding: 0;
        }
        .copy-btn {
            position: absolute;
            right: 10px;
            top: 10px;
            background: #0073aa;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
        }
        .copy-btn:hover {
            background: #005a87;
        }
        
        .restaurant-booking-parameters {
            margin: 30px 0;
            background: #fff;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            padding: 20px;
        }
        .parameters-table table {
            margin-top: 15px;
        }
        .parameters-table code {
            background: #f1f1f1;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        
        .restaurant-booking-steps {
            margin: 30px 0;
        }
        .steps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .step-card {
            background: #fff;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
        }
        .step-number {
            background: #0073aa;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
            margin: 0 auto 15px auto;
        }
        .step-card h4 {
            margin: 0 0 10px 0;
            color: #1d2327;
        }
        .step-card p {
            margin: 0;
            color: #646970;
        }
        .restaurant-booking-features {
            margin: 30px 0;
        }
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .feature-card {
            background: #fff;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
        }
        .feature-icon {
            font-size: 32px;
            margin-bottom: 15px;
        }
        .feature-card h4 {
            margin: 0 0 10px 0;
            color: #1d2327;
        }
        .feature-card p {
            margin: 0;
            color: #646970;
        }
        
        .restaurant-booking-config-link {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 4px;
            padding: 20px;
            margin: 30px 0;
            text-align: center;
        }
        .restaurant-booking-config-link h3 {
            color: #0c5460;
            margin-top: 0;
        }
        </style>
        <?php
    }

    /**
     * Paramètres avancés
     */
    private function display_advanced_settings()
    {
        // Récupérer les statistiques des logs
        $log_stats = $this->get_advanced_log_stats();
        $system_info = $this->get_system_info();
        
        ?>
        <div class="settings-section">
            <h3>📊 <?php _e('Logs et Diagnostics', 'restaurant-booking'); ?></h3>
            <p class="description"><?php _e('Le système enregistre automatiquement toutes les actions importantes pour vous aider à diagnostiquer les problèmes.', 'restaurant-booking'); ?></p>
            
            <div class="logs-stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📝</div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $log_stats['total_logs']; ?></div>
                        <div class="stat-label"><?php _e('Logs total', 'restaurant-booking'); ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">❌</div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $log_stats['errors_week']; ?></div>
                        <div class="stat-label"><?php _e('Erreurs cette semaine', 'restaurant-booking'); ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💾</div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $log_stats['size_mb']; ?> MB</div>
                        <div class="stat-label"><?php _e('Taille des logs', 'restaurant-booking'); ?></div>
                    </div>
                </div>
            </div>

            <div class="setting-row">
                <div class="setting-label">
                    <strong><?php _e('Consultation des logs', 'restaurant-booking'); ?></strong>
                </div>
                <div class="setting-input">
                    <button type="button" class="button button-secondary" id="view_recent_logs">
                        <span class="dashicons dashicons-list-view"></span><?php _e('Voir les logs récents', 'restaurant-booking'); ?>
                    </button>
                    <button type="button" class="button button-secondary" id="view_error_logs">
                        <span class="dashicons dashicons-warning"></span><?php _e('Voir les erreurs', 'restaurant-booking'); ?>
                    </button>
                    <div class="setting-description"><?php _e('Consultez l\'historique des actions et erreurs du plugin', 'restaurant-booking'); ?></div>
                </div>
            </div>

            <div id="logs_display" style="display: none; margin-top: 20px;">
                <div class="logs-container">
                    <div class="logs-header">
                        <h4 id="logs_title"><?php _e('Logs récents', 'restaurant-booking'); ?></h4>
                        <button type="button" class="button-link" id="close_logs">✕</button>
                    </div>
                    <div class="logs-content" id="logs_content">
                        <div class="loading"><?php _e('Chargement...', 'restaurant-booking'); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="settings-section">
            <h3>🧹 <?php _e('Outils de maintenance', 'restaurant-booking'); ?></h3>
            
            <div class="setting-row">
                <div class="setting-label">
                    <strong><?php _e('Nettoyage des logs', 'restaurant-booking'); ?></strong>
                </div>
                <div class="setting-input">
                    <button type="button" class="button button-secondary" id="clean_old_logs">
                        <span class="dashicons dashicons-trash"></span><?php _e('Nettoyer logs anciens (>90 jours)', 'restaurant-booking'); ?>
                    </button>
                    <button type="button" class="button button-secondary" id="export_logs" style="margin-left: 10px;">
                        <span class="dashicons dashicons-download"></span><?php _e('Exporter tous les logs', 'restaurant-booking'); ?>
                    </button>
                    <div class="setting-description"><?php _e('Nettoyez les anciens logs pour libérer de l\'espace ou exportez-les pour analyse', 'restaurant-booking'); ?></div>
                </div>
            </div>

            <div class="setting-row">
                <div class="setting-label">
                    <strong><?php _e('Cache du plugin', 'restaurant-booking'); ?></strong>
                </div>
                <div class="setting-input">
                    <button type="button" class="button button-secondary" id="clear_cache">
                        <span class="dashicons dashicons-update"></span><?php _e('Vider le cache', 'restaurant-booking'); ?>
                    </button>
                    <div class="setting-description"><?php _e('Supprime les données en cache (coordonnées géographiques, etc.)', 'restaurant-booking'); ?></div>
                </div>
            </div>

            <div class="setting-row">
                <div class="setting-label">
                    <strong><?php _e('Test base de données', 'restaurant-booking'); ?></strong>
                </div>
                <div class="setting-input">
                    <button type="button" class="button button-secondary" id="test_database">
                        <span class="dashicons dashicons-database"></span><?php _e('Tester la connexion', 'restaurant-booking'); ?>
                    </button>
                    <div class="setting-description"><?php _e('Vérifie que toutes les tables et données sont correctement configurées', 'restaurant-booking'); ?></div>
                </div>
            </div>
        </div>

        <div class="settings-section">
            <h3>⚙️ <?php _e('Mode debug', 'restaurant-booking'); ?></h3>
            <p class="description"><?php _e('Le mode debug enregistre des informations détaillées pour diagnostiquer les problèmes. Activez-le seulement en cas de problème.', 'restaurant-booking'); ?></p>
            
            <div class="setting-row">
                <div class="setting-label">
                    <label for="debug_mode"><?php _e('Activer le debug', 'restaurant-booking'); ?></label>
                </div>
                <div class="setting-input">
                    <label>
                        <input type="checkbox" id="debug_mode" name="debug_mode" <?php checked(get_option('restaurant_booking_debug_mode', 0), 1); ?>> 
                        <?php _e('Enregistrer les logs détaillés', 'restaurant-booking'); ?>
                    </label>
                    <div class="setting-description">
                        <?php if (get_option('restaurant_booking_debug_mode', 0)): ?>
                            <span style="color: #d63638;">⚠️ <?php _e('Mode debug actuellement ACTIVÉ', 'restaurant-booking'); ?></span>
                            <br><strong><?php _e('Où voir les logs détaillés :', 'restaurant-booking'); ?></strong>
                            <ul style="margin: 10px 0;">
                                <li>📁 <strong>Fichier WordPress :</strong> <code>wp-content/debug.log</code></li>
                                <li>📊 <strong>Base de données :</strong> Table <code><?php global $wpdb; echo $wpdb->prefix; ?>restaurant_logs</code></li>
                                <li>🔍 <strong>Interface admin :</strong> Boutons "Voir les logs" ci-dessous</li>
                            </ul>
                        <?php else: ?>
                            <span style="color: #46b450;">✓ <?php _e('Mode debug actuellement DÉSACTIVÉ', 'restaurant-booking'); ?></span>
                            <br><em><?php _e('Les logs détaillés ne sont pas enregistrés.', 'restaurant-booking'); ?></em>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if (get_option('restaurant_booking_debug_mode', 0)): ?>
        <div class="settings-section">
            <h3>🔍 <?php _e('Consultation debug.log WordPress', 'restaurant-booking'); ?></h3>
            <p class="description"><?php _e('Le mode debug enregistre également des informations dans le fichier debug.log de WordPress.', 'restaurant-booking'); ?></p>
            
            <div class="setting-row">
                <div class="setting-label">
                    <strong><?php _e('Fichier debug.log', 'restaurant-booking'); ?></strong>
                </div>
                <div class="setting-input">
                    <button type="button" class="button button-secondary" id="view_debug_log">
                        <span class="dashicons dashicons-media-text"></span><?php _e('Voir debug.log WordPress', 'restaurant-booking'); ?>
                    </button>
                    <div class="setting-description">
                        <?php 
                        $debug_log_path = WP_CONTENT_DIR . '/debug.log';
                        if (file_exists($debug_log_path)): 
                            $file_size = size_format(filesize($debug_log_path));
                            printf(__('Fichier trouvé (%s) : %s', 'restaurant-booking'), $file_size, $debug_log_path);
                        else: ?>
                            <em><?php _e('Fichier debug.log non trouvé. Activez WP_DEBUG_LOG dans wp-config.php.', 'restaurant-booking'); ?></em>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div id="debug_log_display" style="display: none; margin-top: 20px;">
                <div class="debug-log-container">
                    <h4><?php _e('Contenu du fichier debug.log (100 dernières lignes)', 'restaurant-booking'); ?></h4>
                    <pre id="debug_log_content" style="background: #f1f1f1; padding: 15px; max-height: 400px; overflow-y: auto; font-family: monospace; font-size: 12px; border: 1px solid #ddd;"></pre>
                    <button type="button" class="button button-secondary" id="close_debug_log"><?php _e('Fermer', 'restaurant-booking'); ?></button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="settings-section">
            <h3>ℹ️ <?php _e('Informations système', 'restaurant-booking'); ?></h3>
            
            <div class="system-info-grid">
                <div class="info-card">
                    <strong><?php _e('Version plugin', 'restaurant-booking'); ?></strong>
                    <span><?php echo defined('RESTAURANT_BOOKING_VERSION') ? RESTAURANT_BOOKING_VERSION : '3.0.0'; ?></span>
                </div>
                <div class="info-card">
                    <strong><?php _e('Version WordPress', 'restaurant-booking'); ?></strong>
                    <span><?php echo get_bloginfo('version'); ?></span>
                </div>
                <div class="info-card">
                    <strong><?php _e('Version PHP', 'restaurant-booking'); ?></strong>
                    <span><?php echo PHP_VERSION; ?></span>
                </div>
                <div class="info-card">
                    <strong><?php _e('Tables base de données', 'restaurant-booking'); ?></strong>
                    <span><?php echo $system_info['tables_count']; ?> / 7</span>
                </div>
            </div>
        </div>

        <style>
        .logs-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .stat-card {
            display: flex;
            align-items: center;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #ddd;
            gap: 15px;
        }
        .stat-icon {
            font-size: 32px;
        }
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #2271b1;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        .logs-container {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }
        .logs-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
        }
        .logs-header h4 {
            margin: 0;
        }
        .logs-content {
            max-height: 400px;
            overflow-y: auto;
            padding: 20px;
        }
        .log-entry {
            padding: 10px;
            border-bottom: 1px solid #eee;
            font-family: monospace;
            font-size: 13px;
        }
        .log-entry.error { border-left: 4px solid #d63638; background: #fef7f0; }
        .log-entry.warning { border-left: 4px solid #dba617; background: #fffbf0; }
        .log-entry.info { border-left: 4px solid #2271b1; background: #f0f6fc; }
        .log-entry.debug { border-left: 4px solid #666; background: #f6f7f7; }
        .system-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .info-card {
            display: flex;
            justify-content: space-between;
            padding: 15px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .loading {
            text-align: center;
            color: #666;
            font-style: italic;
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Voir les logs récents
            $('#view_recent_logs').click(function() {
                loadLogs('recent', 'Logs récents (50 derniers)');
            });
            
            // Voir les erreurs
            $('#view_error_logs').click(function() {
                loadLogs('error', 'Erreurs récentes');
            });
            
            // Fermer les logs
            $('#close_logs').click(function() {
                $('#logs_display').hide();
            });
            
            // Nettoyer les anciens logs
            $('#clean_old_logs').click(function() {
                if (confirm('Supprimer tous les logs de plus de 90 jours ?')) {
                    var button = $(this);
                    button.prop('disabled', true).text('Nettoyage...');
                    
                    $.post(ajaxurl, {
                        action: 'restaurant_booking_clean_logs',
                        nonce: '<?php echo wp_create_nonce('clean_logs'); ?>'
                    }, function(response) {
                        if (response.success) {
                            alert('✅ ' + response.data.deleted + ' logs supprimés avec succès !');
                            location.reload();
                        } else {
                            alert('❌ Erreur : ' + response.data);
                        }
                    }).always(function() {
                        button.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span>Nettoyer logs anciens (>90 jours)');
                    });
                }
            });
            
            // Exporter les logs
            $('#export_logs').click(function() {
                window.location.href = '<?php echo admin_url('admin-ajax.php'); ?>?action=restaurant_booking_export_logs&nonce=<?php echo wp_create_nonce('export_logs'); ?>';
            });
            
            // Vider le cache
            $('#clear_cache').click(function() {
                var button = $(this);
                button.prop('disabled', true).text('Nettoyage...');
                
                $.post(ajaxurl, {
                    action: 'restaurant_booking_clear_cache',
                    nonce: '<?php echo wp_create_nonce('clear_cache'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert('✅ Cache vidé avec succès !');
                    } else {
                        alert('❌ Erreur : ' + response.data);
                    }
                }).always(function() {
                    button.prop('disabled', false).html('<span class="dashicons dashicons-update"></span>Vider le cache');
                });
            });
            
            // Test base de données
            $('#test_database').click(function() {
                var button = $(this);
                button.prop('disabled', true).text('Test en cours...');
                
                $.post(ajaxurl, {
                    action: 'restaurant_booking_test_database',
                    nonce: '<?php echo wp_create_nonce('test_database'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert('✅ Base de données OK !\n' + response.data.message);
                    } else {
                        alert('❌ Problème détecté :\n' + response.data);
                    }
                }).always(function() {
                    button.prop('disabled', false).html('<span class="dashicons dashicons-database"></span>Tester la connexion');
                });
            });
            
            // Voir debug.log WordPress
            $('#view_debug_log').click(function() {
                var button = $(this);
                button.prop('disabled', true).text('Chargement...');
                
                $('#debug_log_content').html('<div class="loading">Chargement du fichier debug.log...</div>');
                $('#debug_log_display').show();
                
                $.post(ajaxurl, {
                    action: 'restaurant_booking_get_debug_log',
                    nonce: '<?php echo wp_create_nonce('get_debug_log'); ?>'
                }, function(response) {
                    if (response.success) {
                        var info = 'Fichier: wp-content/debug.log | Taille: ' + response.data.file_size + ' | Lignes totales: ' + response.data.total_lines + ' | Affichées: ' + response.data.showing_lines;
                        $('#debug_log_content').html('<div style="color: #666; font-size: 11px; margin-bottom: 10px;">' + info + '</div>' + response.data.content);
                    } else {
                        $('#debug_log_content').html('<div style="color: #d63638;">❌ Erreur: ' + response.data + '</div>');
                    }
                }).fail(function() {
                    $('#debug_log_content').html('<div style="color: #d63638;">❌ Erreur de connexion</div>');
                }).always(function() {
                    button.prop('disabled', false).html('<span class="dashicons dashicons-media-text"></span>Voir debug.log WordPress');
                });
            });
            
            // Fermer debug.log
            $('#close_debug_log').click(function() {
                $('#debug_log_display').hide();
            });
            
            // Fonction pour charger les logs
            function loadLogs(type, title) {
                $('#logs_title').text(title);
                $('#logs_content').html('<div class="loading">Chargement...</div>');
                $('#logs_display').show();
                
                $.post(ajaxurl, {
                    action: 'restaurant_booking_get_logs',
                    type: type,
                    nonce: '<?php echo wp_create_nonce('get_logs'); ?>'
                }, function(response) {
                    if (response.success) {
                        var html = '';
                        if (response.data.logs.length > 0) {
                            response.data.logs.forEach(function(log) {
                                html += '<div class="log-entry ' + log.level + '">';
                                html += '<strong>' + log.created_at + '</strong> [' + log.level.toUpperCase() + '] ';
                                html += log.message;
                                if (log.context) {
                                    html += '<br><small style="color: #666;">Context: ' + JSON.stringify(log.context) + '</small>';
                                }
                                html += '</div>';
                            });
                        } else {
                            html = '<div style="text-align: center; color: #666; font-style: italic;">Aucun log trouvé</div>';
                        }
                        $('#logs_content').html(html);
                    } else {
                        $('#logs_content').html('<div style="color: #d63638;">Erreur : ' + response.data + '</div>');
                    }
                });
            }
        });
        </script>
        <?php
    }

    /**
     * Obtenir les statistiques avancées des logs
     */
    private function get_advanced_log_stats()
    {
        global $wpdb;
        
        // Nombre total de logs
        $total_logs = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}restaurant_logs");
        
        // Erreurs cette semaine
        $errors_week = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}restaurant_logs 
            WHERE level = 'error' 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        
        // Taille des logs
        $size_info = $wpdb->get_row("
            SELECT ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 1) as size_mb
            FROM information_schema.tables 
            WHERE table_schema = DATABASE() 
            AND table_name = '{$wpdb->prefix}restaurant_logs'
        ");
        
        return array(
            'total_logs' => (int) $total_logs,
            'errors_week' => (int) $errors_week,
            'size_mb' => $size_info ? $size_info->size_mb : 0
        );
    }

    /**
     * Obtenir les informations système
     */
    private function get_system_info()
    {
        global $wpdb;
        
        // Compter les tables du plugin
        $tables = array(
            'restaurant_categories',
            'restaurant_products', 
            'restaurant_settings',
            'restaurant_quotes',
            'restaurant_availability',
            'restaurant_delivery_zones',
            'restaurant_logs'
        );
        
        $tables_count = 0;
        foreach ($tables as $table) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}{$table}'");
            if ($exists) {
                $tables_count++;
            }
        }
        
        return array(
            'tables_count' => $tables_count
        );
    }

    /**
     * Page de tarification (selon le cahier des charges)
     */
    public function display_pricing()
    {
        ?>
        <div class="wrap">
            <h1><?php _e('Tarification', 'restaurant-booking'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('restaurant_booking_pricing', '_wpnonce'); ?>
                <input type="hidden" name="save_pricing" value="1">

                <div class="settings-section">
                    <h3><?php _e('Forfaits de base', 'restaurant-booking'); ?></h3>
                    
                    <div class="setting-row">
                        <div class="setting-label">
                            <label for="restaurant_base_price"><?php _e('Prix forfait restaurant', 'restaurant-booking'); ?></label>
                        </div>
                        <div class="setting-input">
                            <input type="number" id="restaurant_base_price" name="restaurant_base_price" value="300" min="0" step="0.01" class="small-text" /> €
                            <div class="setting-description"><?php _e('Prix de base pour la privatisation du restaurant (défaut: 300€)', 'restaurant-booking'); ?></div>
                        </div>
                    </div>

                    <div class="setting-row">
                        <div class="setting-label">
                            <label for="remorque_base_price"><?php _e('Prix forfait remorque', 'restaurant-booking'); ?></label>
                        </div>
                        <div class="setting-input">
                            <input type="number" id="remorque_base_price" name="remorque_base_price" value="350" min="0" step="0.01" class="small-text" /> €
                            <div class="setting-description"><?php _e('Prix de base pour la remorque mobile (défaut: 350€)', 'restaurant-booking'); ?></div>
                        </div>
                    </div>

                    <div class="setting-row">
                        <div class="setting-label">
                            <label for="hourly_supplement"><?php _e('Supplément horaire', 'restaurant-booking'); ?></label>
                        </div>
                        <div class="setting-input">
                            <input type="number" id="hourly_supplement" name="hourly_supplement" value="50" min="0" step="0.01" class="small-text" /> €
                            <div class="setting-description"><?php _e('Prix par heure supplémentaire (défaut: 50€)', 'restaurant-booking'); ?></div>
                        </div>
                    </div>
                </div>

                <div class="settings-section">
                    <h3><?php _e('Contraintes participants', 'restaurant-booking'); ?></h3>
                    
                    <div class="setting-row">
                        <div class="setting-label">
                            <label for="restaurant_min_guests"><?php _e('Restaurant - Minimum convives', 'restaurant-booking'); ?></label>
                        </div>
                        <div class="setting-input">
                            <input type="number" id="restaurant_min_guests" name="restaurant_min_guests" value="10" min="1" class="small-text" />
                        </div>
                    </div>

                    <div class="setting-row">
                        <div class="setting-label">
                            <label for="restaurant_max_guests"><?php _e('Restaurant - Maximum convives', 'restaurant-booking'); ?></label>
                        </div>
                        <div class="setting-input">
                            <input type="number" id="restaurant_max_guests" name="restaurant_max_guests" value="30" min="1" class="small-text" />
                        </div>
                    </div>

                    <div class="setting-row">
                        <div class="setting-label">
                            <label for="remorque_min_guests"><?php _e('Remorque - Minimum convives', 'restaurant-booking'); ?></label>
                        </div>
                        <div class="setting-input">
                            <input type="number" id="remorque_min_guests" name="remorque_min_guests" value="20" min="1" class="small-text" />
                        </div>
                    </div>

                    <div class="setting-row">
                        <div class="setting-label">
                            <label for="remorque_max_guests"><?php _e('Remorque - Maximum convives', 'restaurant-booking'); ?></label>
                        </div>
                        <div class="setting-input">
                            <input type="number" id="remorque_max_guests" name="remorque_max_guests" value="100" min="1" class="small-text" />
                        </div>
                    </div>
                </div>

                <div class="settings-section">
                    <h3><?php _e('Suppléments remorque', 'restaurant-booking'); ?></h3>
                    
                    <div class="setting-row">
                        <div class="setting-label">
                            <label for="remorque_50_guests_supplement"><?php _e('Supplément +50 convives', 'restaurant-booking'); ?></label>
                        </div>
                        <div class="setting-input">
                            <input type="number" id="remorque_50_guests_supplement" name="remorque_50_guests_supplement" value="150" min="0" step="0.01" class="small-text" /> €
                            <div class="setting-description"><?php _e('Supplément appliqué si plus de 50 convives (défaut: 150€)', 'restaurant-booking'); ?></div>
                        </div>
                    </div>

                    <div class="setting-row">
                        <div class="setting-label">
                            <label for="restaurant_postal_code"><?php _e('Code postal restaurant', 'restaurant-booking'); ?></label>
                        </div>
                        <div class="setting-input">
                            <input type="text" id="restaurant_postal_code" name="restaurant_postal_code" value="67000" pattern="[0-9]{5}" class="small-text" />
                            <div class="setting-description"><?php _e('Code postal du restaurant pour calcul des distances (défaut: 67000)', 'restaurant-booking'); ?></div>
                        </div>
                    </div>
                </div>

                <?php submit_button(__('Sauvegarder la tarification', 'restaurant-booking')); ?>
            </form>
        </div>

        <style>
        .settings-section {
            background: #fff;
            border: 1px solid #c3c4c7;
            padding: 20px;
            margin-bottom: 20px;
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
            width: 300px;
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
     * Page des textes interface (selon le cahier des charges)
     */
    public function display_texts()
    {
        ?>
        <div class="wrap">
            <h1><?php _e('Textes interface', 'restaurant-booking'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('restaurant_booking_texts', '_wpnonce'); ?>
                <input type="hidden" name="save_texts" value="1">

                <div class="settings-section">
                    <h3><?php _e('Page d\'accueil', 'restaurant-booking'); ?></h3>
                    
                    <div class="setting-row">
                        <div class="setting-label">
                            <label for="homepage_restaurant_title"><?php _e('Titre restaurant', 'restaurant-booking'); ?></label>
                        </div>
                        <div class="setting-input">
                            <input type="text" id="homepage_restaurant_title" name="homepage_restaurant_title" value="LE RESTAURANT" class="large-text" />
                        </div>
                    </div>

                    <div class="setting-row">
                        <div class="setting-label">
                            <label for="homepage_restaurant_description"><?php _e('Description restaurant', 'restaurant-booking'); ?></label>
                        </div>
                        <div class="setting-input">
                            <textarea id="homepage_restaurant_description" name="homepage_restaurant_description" rows="3" class="large-text">Découvrez notre cuisine authentique dans un cadre chaleureux et convivial.</textarea>
                        </div>
                    </div>

                    <div class="setting-row">
                        <div class="setting-label">
                            <label for="homepage_traiteur_title"><?php _e('Titre traiteur', 'restaurant-booking'); ?></label>
                        </div>
                        <div class="setting-input">
                            <input type="text" id="homepage_traiteur_title" name="homepage_traiteur_title" value="LE TRAITEUR ÉVÉNEMENTIEL" class="large-text" />
                        </div>
                    </div>
                </div>

                <div class="settings-section">
                    <h3><?php _e('Formulaires de devis', 'restaurant-booking'); ?></h3>
                    
                    <div class="setting-row">
                        <div class="setting-label">
                            <label for="form_step1_title"><?php _e('Titre étape 1', 'restaurant-booking'); ?></label>
                        </div>
                        <div class="setting-input">
                            <input type="text" id="form_step1_title" name="form_step1_title" value="Forfait de base" class="large-text" />
                        </div>
                    </div>

                    <div class="setting-row">
                        <div class="setting-label">
                            <label for="form_step2_title"><?php _e('Titre étape 2', 'restaurant-booking'); ?></label>
                        </div>
                        <div class="setting-input">
                            <input type="text" id="form_step2_title" name="form_step2_title" value="Choix des formules repas" class="large-text" />
                        </div>
                    </div>

                    <div class="setting-row">
                        <div class="setting-label">
                            <label for="form_step3_title"><?php _e('Titre étape 3', 'restaurant-booking'); ?></label>
                        </div>
                        <div class="setting-input">
                            <input type="text" id="form_step3_title" name="form_step3_title" value="Choix des boissons" class="large-text" />
                        </div>
                    </div>

                    <div class="setting-row">
                        <div class="setting-label">
                            <label for="form_step4_title"><?php _e('Titre étape 4', 'restaurant-booking'); ?></label>
                        </div>
                        <div class="setting-input">
                            <input type="text" id="form_step4_title" name="form_step4_title" value="Coordonnées / Contact" class="large-text" />
                        </div>
                    </div>
                </div>

                <?php submit_button(__('Sauvegarder les textes', 'restaurant-booking')); ?>
            </form>
        </div>

        <style>
        .settings-section {
            background: #fff;
            border: 1px solid #c3c4c7;
            padding: 20px;
            margin-bottom: 20px;
        }
        .settings-section h3 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .setting-row {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
            padding: 10px 0;
        }
        .setting-label {
            width: 300px;
            font-weight: 600;
            padding-top: 5px;
        }
        .setting-input {
            flex: 1;
        }
        </style>
        <?php
    }

    /**
     * Page des emails (selon le cahier des charges)
     */
    public function display_emails()
    {
        // Charger le CSS et JS spécifiques pour cette page
        wp_enqueue_style(
            'restaurant-booking-emails',
            RESTAURANT_BOOKING_PLUGIN_URL . 'assets/css/admin-emails.css',
            array(),
            RESTAURANT_BOOKING_VERSION
        );
        
        wp_enqueue_script(
            'restaurant-booking-emails-js',
            RESTAURANT_BOOKING_PLUGIN_URL . 'assets/js/admin-emails.js',
            array('jquery'),
            RESTAURANT_BOOKING_VERSION,
            true
        );
        
        // Les actions sont maintenant gérées par le système unifié de sauvegarde

        // Obtenir les paramètres actuels
        $settings = RestaurantBooking_Settings::get_group('emails');
        $smtp_status = RestaurantBooking_Email::get_smtp_plugin_status();
        $email_stats = RestaurantBooking_Email::get_email_stats();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Configuration des emails', 'restaurant-booking'); ?></h1>

            <!-- Statut SMTP -->
            <div class="email-status-section">
                <h2><?php _e('Statut de la configuration email', 'restaurant-booking'); ?></h2>
                <div class="status-card <?php echo $smtp_status['configured'] ? 'configured' : 'not-configured'; ?>">
                    <div class="status-icon">
                        <?php echo $smtp_status['configured'] ? '✅' : '⚠️'; ?>
                    </div>
                    <div class="status-info">
                        <h3><?php echo $smtp_status['configured'] ? __('SMTP Configuré', 'restaurant-booking') : __('SMTP Non configuré', 'restaurant-booking'); ?></h3>
                        <p>
                            <?php if ($smtp_status['plugin'] !== 'none'): ?>
                                <?php printf(__('Plugin détecté : %s', 'restaurant-booking'), '<strong>' . $smtp_status['plugin'] . '</strong>'); ?>
                            <?php else: ?>
                                <?php _e('Aucun plugin SMTP détecté. Les emails utilisent la fonction mail() de PHP.', 'restaurant-booking'); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="status-actions">
                        <form method="post" style="display: inline;">
                            <?php wp_nonce_field('restaurant_booking_test_email', '_wpnonce'); ?>
                            <input type="email" id="test_email" name="test_email" placeholder="<?php echo esc_attr(get_option('admin_email')); ?>" value="<?php echo esc_attr(get_option('admin_email')); ?>" />
                            <button type="submit" id="test-email-btn" name="test_email" value="1" class="button button-primary">
                                <?php _e('Tester l\'envoi', 'restaurant-booking'); ?>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Statistiques -->
                <div class="email-stats">
                    <h4><?php _e('📊 Statistiques d\'envoi', 'restaurant-booking'); ?></h4>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo (int) ($email_stats['today'] ?? 0); ?></span>
                            <span class="stat-label"><?php _e('Aujourd\'hui', 'restaurant-booking'); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo (int) ($email_stats['month'] ?? 0); ?></span>
                            <span class="stat-label"><?php _e('Ce mois', 'restaurant-booking'); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number" style="color: <?php echo ($email_stats['errors'] ?? 0) > 0 ? '#d63638' : '#00a32a'; ?>">
                                <?php echo (int) ($email_stats['errors'] ?? 0); ?>
                            </span>
                            <span class="stat-label"><?php _e('Erreurs', 'restaurant-booking'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Recommandations SMTP -->
                <?php if (!$smtp_status['configured']): ?>
                    <div class="smtp-recommendations">
                        <h4><?php _e('💡 Recommandations pour un envoi fiable', 'restaurant-booking'); ?></h4>
                        <p><?php _e('Pour garantir la délivrabilité de vos emails, nous recommandons d\'utiliser un plugin SMTP professionnel :', 'restaurant-booking'); ?></p>
                        <div class="recommendation-grid">
                            <div class="recommendation-item">
                                <h5>🏆 WP Mail SMTP</h5>
                                <p><?php _e('Plugin le plus populaire avec support Gmail, Outlook, SendGrid, Mailgun, etc. Interface intuitive et configuration guidée.', 'restaurant-booking'); ?></p>
                                <a href="<?php echo admin_url('plugin-install.php?s=WP+Mail+SMTP&tab=search&type=term'); ?>" class="button button-secondary" target="_blank">
                                    <?php _e('Installer maintenant', 'restaurant-booking'); ?>
                                </a>
                            </div>
                            <div class="recommendation-item">
                                <h5>📧 Easy WP SMTP</h5>
                                <p><?php _e('Configuration simple et rapide pour les serveurs SMTP classiques. Parfait pour les hébergements avec SMTP intégré.', 'restaurant-booking'); ?></p>
                                <a href="<?php echo admin_url('plugin-install.php?s=Easy+WP+SMTP&tab=search&type=term'); ?>" class="button button-secondary" target="_blank">
                                    <?php _e('Installer maintenant', 'restaurant-booking'); ?>
                                </a>
                            </div>
                        </div>
                        
                        <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #FFB404;">
                            <strong><?php _e('💡 Conseil :', 'restaurant-booking'); ?></strong>
                            <?php _e('Une fois un plugin SMTP installé et configuré, cette page détectera automatiquement la configuration et adaptera l\'interface.', 'restaurant-booking'); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <form method="post" action="">
                <?php wp_nonce_field('restaurant_booking_settings', '_wpnonce'); ?>
                <input type="hidden" name="restaurant_booking_action" value="save_settings">

                <div class="settings-section">
                    <h3><?php _e('Email devis client', 'restaurant-booking'); ?></h3>
                    
                    <div class="setting-row">
                        <div class="setting-label">
                            <label for="email_quote_subject"><?php _e('Sujet email', 'restaurant-booking'); ?></label>
                        </div>
                        <div class="setting-input">
                            <input type="text" id="email_quote_subject" name="email_quote_subject" value="<?php echo esc_attr($settings['email_quote_subject'] ?? 'Votre devis privatisation Block'); ?>" class="large-text" />
                        </div>
                    </div>

                    <div class="setting-row">
                        <div class="setting-label">
                            <label for="email_quote_header_logo"><?php _e('Logo header', 'restaurant-booking'); ?></label>
                        </div>
                        <div class="setting-input">
                            <button type="button" class="button" onclick="openMediaLibrary('email_quote_header_logo')">
                                <?php _e('Choisir une image', 'restaurant-booking'); ?>
                            </button>
                            <input type="hidden" id="email_quote_header_logo" name="email_quote_header_logo" value="" />
                            <div id="email_quote_header_logo_preview" style="margin-top: 10px;"></div>
                            <div class="setting-description"><?php _e('Dimensions recommandées : 200x80px', 'restaurant-booking'); ?></div>
                        </div>
                    </div>

                    <div class="setting-row">
                        <div class="setting-label">
                            <label for="email_quote_body_html"><?php _e('Corps de l\'email', 'restaurant-booking'); ?></label>
                        </div>
                        <div class="setting-input">
                            <?php 
                            wp_editor(
                                '<p>Madame, Monsieur,</p><p>Nous vous remercions pour votre demande de devis.</p><p>Vous trouverez en pièce jointe votre devis personnalisé.</p><p>Cordialement,<br>L\'équipe Block</p>',
                                'email_quote_body_html',
                                array(
                                    'textarea_name' => 'email_quote_body_html',
                                    'media_buttons' => true,
                                    'textarea_rows' => 10,
                                    'teeny' => false
                                )
                            );
                            ?>
                        </div>
                    </div>
                </div>

                <div class="settings-section">
                    <h3><?php _e('Configuration SMTP', 'restaurant-booking'); ?></h3>
                    
                    <div class="setting-row">
                        <div class="setting-label">
                            <label for="smtp_host"><?php _e('Serveur SMTP', 'restaurant-booking'); ?></label>
                        </div>
                        <div class="setting-input">
                            <input type="text" id="smtp_host" name="smtp_host" value="" class="regular-text" placeholder="smtp.gmail.com" />
                        </div>
                    </div>

                    <div class="setting-row">
                        <div class="setting-label">
                            <label for="smtp_port"><?php _e('Port SMTP', 'restaurant-booking'); ?></label>
                        </div>
                        <div class="setting-input">
                            <input type="number" id="smtp_port" name="smtp_port" value="587" class="small-text" />
                        </div>
                    </div>

                    <div class="setting-row">
                        <div class="setting-label">
                            <label for="test_email"><?php _e('Test d\'envoi', 'restaurant-booking'); ?></label>
                        </div>
                        <div class="setting-input">
                            <input type="email" id="test_email" name="test_email" placeholder="test@exemple.com" class="regular-text" />
                            <button type="button" class="button" onclick="sendTestEmail()">
                                <?php _e('Envoyer email de test', 'restaurant-booking'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <?php submit_button(__('Sauvegarder la configuration email', 'restaurant-booking')); ?>
            </form>
        </div>

        <script>
        function openMediaLibrary(inputId) {
            var mediaUploader = wp.media({
                title: 'Choisir une image',
                button: {
                    text: 'Utiliser cette image'
                },
                multiple: false
            });

            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                document.getElementById(inputId).value = attachment.id;
                document.getElementById(inputId + '_preview').innerHTML = '<img src="' + attachment.url + '" style="max-width: 200px; height: auto;" />';
            });

            mediaUploader.open();
        }

        function sendTestEmail() {
            var email = document.getElementById('test_email').value;
            if (!email) {
                alert('Veuillez saisir une adresse email');
                return;
            }
            
            // AJAX call pour envoyer l'email de test
            alert('Fonctionnalité d\'envoi de test en cours de développement');
        }
        </script>

        <style>
        .settings-section {
            background: #fff;
            border: 1px solid #c3c4c7;
            padding: 20px;
            margin-bottom: 20px;
        }
        .settings-section h3 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .setting-row {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
            padding: 10px 0;
        }
        .setting-label {
            width: 300px;
            font-weight: 600;
            padding-top: 5px;
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

        <script>
        // JavaScript pour les widgets Elementor (si nécessaire dans le futur)
        console.log('Block & Co - Widgets Elementor disponibles');
        </script>
        <?php
    }


    /**
     * Sauvegarder les paramètres d'email
     */
    private function save_email_settings($data)
    {
        // Le nonce est déjà vérifié par save_settings()

        // Sauvegarder les paramètres dans le format unifié
        $email_settings = array(
            'sender_email' => sanitize_email($data['sender_email'] ?? 'noreply@blockandco.fr'),
            'sender_name' => sanitize_text_field($data['sender_name'] ?? 'Block & Co'),
            'quote_subject' => sanitize_text_field($data['quote_subject'] ?? 'Votre devis Block & Co'),
            'admin_email' => sanitize_email($data['admin_email'] ?? get_option('admin_email')),
            
            // Nouveaux paramètres de notification admin
            'admin_notification_enabled' => isset($data['admin_notification_enabled']) ? '1' : '0',
            'admin_notification_subject' => sanitize_text_field($data['admin_notification_subject'] ?? 'Nouveau devis reçu - Block & Co'),
            'admin_notification_emails' => array_filter(array_map('sanitize_email', $data['admin_notification_emails'] ?? array())),
            
            // Anciens paramètres pour compatibilité
            'email_quote_subject' => sanitize_text_field($data['email_quote_subject'] ?? ''),
            'email_quote_header_logo' => (int) ($data['email_quote_header_logo'] ?? 0),
            'email_quote_footer_logo' => (int) ($data['email_quote_footer_logo'] ?? 0),
            'email_quote_body_html' => wp_kses_post($data['email_quote_body_html'] ?? ''),
        );

        // Sauvegarder dans le nouveau format unifié
        update_option('restaurant_booking_email_settings', $email_settings);

        // Maintenir la compatibilité avec l'ancien format
        foreach ($email_settings as $key => $value) {
            if (strpos($key, 'email_') === 0 || $key === 'admin_notification_emails') {
                update_option('restaurant_booking_' . $key, $value);
            }
        }

        add_action('admin_notices', function() {
            echo '<div class="notice notice-success"><p>' . __('Paramètres email sauvegardés avec succès !', 'restaurant-booking') . '</p></div>';
        });
    }

    /**
     * Tester la configuration email
     */
    private function test_email_configuration($data)
    {
        // Vérifier le nonce
        if (!wp_verify_nonce($data['_wpnonce'], 'restaurant_booking_test_email')) {
            wp_die(__('Token de sécurité invalide', 'restaurant-booking'));
        }

        $test_email = sanitize_email($data['test_email']);
        
        if (!is_email($test_email)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . __('Adresse email invalide.', 'restaurant-booking') . '</p></div>';
            });
            return;
        }

        $result = RestaurantBooking_Email::test_email_config($test_email);
        
        if ($result) {
            add_action('admin_notices', function() use ($test_email) {
                echo '<div class="notice notice-success"><p>' . sprintf(__('Email de test envoyé avec succès à %s ! Vérifiez votre boîte de réception.', 'restaurant-booking'), $test_email) . '</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . __('Erreur lors de l\'envoi de l\'email de test. Vérifiez votre configuration SMTP.', 'restaurant-booking') . '</p></div>';
            });
        }
    }

    /**
     * Sauvegarder les paramètres
     */
    public function save_settings($data)
    {
        // Vérifier le nonce
        if (!wp_verify_nonce($data['_wpnonce'], 'restaurant_booking_settings')) {
            wp_die(__('Token de sécurité invalide', 'restaurant-booking'));
        }

        // Traiter selon l'onglet actuel
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        
        switch ($tab) {
            case 'general':
                $this->save_general_settings($data);
                break;
            case 'email':
                $this->save_email_settings($data);
                break;
            case 'pdf':
                $this->save_pdf_settings($data);
                break;
            case 'maps':
                $this->save_maps_settings($data);
                break;
            case 'calendar':
                $this->save_calendar_settings($data);
                break;
            case 'settings':
                // Onglet settings de la page calendrier - traiter comme calendrier
                $this->save_calendar_settings($data);
                break;
            case 'integration':
                $this->save_integration_settings($data);
                break;
            case 'advanced':
                $this->save_advanced_settings($data);
                break;
        }
    }

    /**
     * Sauvegarder les paramètres Maps
     */
    private function save_maps_settings($data)
    {
        // Sauvegarder la clé API Google Maps
        $google_maps_api_key = isset($data['google_maps_api_key']) ? sanitize_text_field($data['google_maps_api_key']) : '';
        update_option('restaurant_booking_google_maps_api_key', $google_maps_api_key);
        
        // Sauvegarder l'adresse complète du restaurant
        $restaurant_address = isset($data['restaurant_address']) ? sanitize_text_field($data['restaurant_address']) : '1 Place Kléber, 67000 Strasbourg, France';
        update_option('restaurant_booking_restaurant_address', $restaurant_address);
        
        // Sauvegarder le code postal du restaurant (fallback)
        $restaurant_postal_code = isset($data['restaurant_postal_code']) ? sanitize_text_field($data['restaurant_postal_code']) : '67000';
        if (preg_match('/^[0-9]{5}$/', $restaurant_postal_code)) {
            update_option('restaurant_booking_restaurant_postal_code', $restaurant_postal_code);
        }
        
        // Note: Plus besoin de sauvegarder la méthode de calcul - Google Maps uniquement
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success"><p>' . __('Paramètres Maps sauvegardés avec succès !', 'restaurant-booking') . '</p></div>';
        });
    }

    /**
     * Sauvegarder les paramètres intégration
     */
    private function save_integration_settings($data)
    {
        // L'onglet intégration ne contient que de la documentation
        // Aucun paramètre à sauvegarder pour l'instant
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success"><p>' . __('Paramètres intégration sauvegardés avec succès !', 'restaurant-booking') . '</p></div>';
        });
    }

    /**
     * Sauvegarder les paramètres généraux
     */
    private function save_general_settings($data)
    {
        $settings = array(
            'company_name' => sanitize_text_field($data['company_name'] ?? ''),
            'company_address' => sanitize_text_field($data['company_address'] ?? ''),
            'company_postal_code' => sanitize_text_field($data['company_postal_code'] ?? ''),
            'company_city' => sanitize_text_field($data['company_city'] ?? ''),
            'company_phone' => sanitize_text_field($data['company_phone'] ?? ''),
            'company_email' => sanitize_email($data['company_email'] ?? ''),
            'company_siret' => sanitize_text_field($data['company_siret'] ?? ''),
        );

        update_option('restaurant_booking_general_settings', $settings);
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success"><p>' . __('Paramètres généraux sauvegardés avec succès !', 'restaurant-booking') . '</p></div>';
        });
    }

    /**
     * Sauvegarder les paramètres PDF
     */
    private function save_pdf_settings($data)
    {
        // Debug pour diagnostiquer
        error_log('[Restaurant Booking] save_pdf_settings appelé');
        error_log('[Restaurant Booking] POST data: ' . print_r($data, true));
        
        $settings = array(
            'logo_id' => intval($data['pdf_logo_id'] ?? 0),
            'primary_color' => sanitize_hex_color($data['primary_color'] ?? '#FFB404'),
            'secondary_color' => sanitize_hex_color($data['secondary_color'] ?? '#243127'),
            'footer_text' => $this->clean_escaped_quotes(sanitize_text_field($data['footer_text'] ?? '')),
            'quote_validity' => $this->clean_escaped_quotes(sanitize_textarea_field($data['quote_validity'] ?? '')),
            'payment_terms' => $this->clean_escaped_quotes(sanitize_textarea_field($data['payment_terms'] ?? '')),
            'cancellation_terms' => $this->clean_escaped_quotes(sanitize_textarea_field($data['cancellation_terms'] ?? '')),
            'general_remarks' => $this->clean_escaped_quotes(sanitize_textarea_field($data['general_remarks'] ?? '')),
        );

        error_log('[Restaurant Booking] Settings to save: ' . print_r($settings, true));
        $result = update_option('restaurant_booking_pdf_settings', $settings);
        error_log('[Restaurant Booking] Save result: ' . ($result ? 'SUCCESS' : 'FAILED'));
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success"><p>' . __('Paramètres PDF sauvegardés avec succès !', 'restaurant-booking') . '</p></div>';
        });
    }

    /**
     * Affichage du contenu des paramètres calendrier uniquement (sans les doublons Google Calendar)
     */
    public function display_calendar_settings_content_only()
    {
        ?>
        <div class="settings-section">
            <h3>📅 <?php _e('Interface de Sélection de Date', 'restaurant-booking'); ?></h3>
            <p class="description"><?php _e('Choisissez comment les clients sélectionnent la date dans le formulaire de devis.', 'restaurant-booking'); ?></p>
            
            <?php 
            // Récupérer l'option du calendrier (option séparée)
            $use_calendar_widget = get_option('restaurant_booking_use_calendar_widget', false);
            ?>
            
            <form method="post">
                <?php wp_nonce_field('restaurant_booking_settings', '_wpnonce'); ?>
                <input type="hidden" name="restaurant_booking_action" value="save_settings">
                
                <div class="setting-row">
                    <div class="setting-label">
                        <strong><?php _e('Type de calendrier', 'restaurant-booking'); ?></strong>
                    </div>
                    <div class="setting-input">
                        <label class="calendar-option">
                            <input type="radio" name="calendar_type" value="simple" <?php checked(!$use_calendar_widget); ?> />
                            <div class="option-content">
                                <strong><?php _e('Calendrier Simple (Actuel)', 'restaurant-booking'); ?></strong>
                                <p class="option-description"><?php _e('Champ de date standard du navigateur. Simple et compatible avec tous les navigateurs.', 'restaurant-booking'); ?></p>
                            </div>
                        </label>
                        
                        <label class="calendar-option">
                            <input type="radio" name="calendar_type" value="interactive" <?php checked($use_calendar_widget); ?> />
                            <div class="option-content">
                                <strong><?php _e('Calendrier Interactif avec Créneaux', 'restaurant-booking'); ?></strong>
                                <p class="option-description"><?php _e('Calendrier visuel avec affichage des créneaux disponibles. Plus interactif mais nécessite JavaScript.', 'restaurant-booking'); ?></p>
                            </div>
                        </label>
                    </div>
                </div>

                <?php submit_button(__('Sauvegarder les paramètres', 'restaurant-booking')); ?>
            </form>
        </div>

        <div class="settings-section">
            <h3>ℹ️ <?php _e('Informations', 'restaurant-booking'); ?></h3>
            <div class="info-card">
                <p><strong><?php _e('Note :', 'restaurant-booking'); ?></strong> <?php _e('Les paramètres de connexion Google Calendar ont été déplacés dans l\'onglet "Configuration Google Calendar" pour une meilleure organisation.', 'restaurant-booking'); ?></p>
                <p><strong><?php _e('Gestion des disponibilités :', 'restaurant-booking'); ?></strong> <?php _e('Utilisez l\'onglet "Calendrier des disponibilités" pour gérer les dates bloquées manuellement ou synchronisées avec Google Calendar.', 'restaurant-booking'); ?></p>
            </div>
        </div>
        
        <style>
        .calendar-option {
            display: block;
            margin-bottom: 15px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: border-color 0.2s;
        }
        
        .calendar-option:hover {
            border-color: #0073aa;
        }
        
        .calendar-option input[type="radio"] {
            margin-right: 10px;
        }
        
        .option-content {
            display: inline-block;
            vertical-align: top;
        }
        
        .option-description {
            margin: 5px 0 0 0;
            color: #666;
            font-size: 13px;
        }
        
        .info-card {
            background: #f0f6fc;
            border: 1px solid #c3c4c7;
            border-left: 4px solid #0073aa;
            padding: 15px;
            border-radius: 4px;
        }
        
        .info-card p {
            margin: 0 0 10px 0;
        }
        
        .info-card p:last-child {
            margin-bottom: 0;
        }
        </style>
        <?php
    }

    /**
     * Sauvegarder les paramètres calendrier
     */
    private function save_calendar_settings($data)
    {
        $settings = array();
        // Aucun paramètre spécifique calendrier pour l'instant
        
        update_option('restaurant_booking_calendar_settings', $settings);
        
        // Gestion du type de calendrier - convertir calendar_type en use_calendar_widget
        $use_calendar_widget = false;
        
        if (isset($data['calendar_type'])) {
            $use_calendar_widget = ($data['calendar_type'] === 'interactive');
        } elseif (isset($data['use_calendar_widget'])) {
            $use_calendar_widget = ($data['use_calendar_widget'] === '1');
        }
        
        // Sauvegarder l'option
        update_option('restaurant_booking_use_calendar_widget', $use_calendar_widget);
        
        $calendar_type = $use_calendar_widget ? 'Calendrier Interactif avec Créneaux' : 'Calendrier Simple';
        add_action('admin_notices', function() use ($calendar_type) {
            echo '<div class="notice notice-success"><p>' . sprintf(__('✅ Paramètres calendrier sauvegardés ! Type de calendrier : %s', 'restaurant-booking'), $calendar_type) . '</p></div>';
        });
    }

    /**
     * Sauvegarder les paramètres avancés
     */
    private function save_advanced_settings($data)
    {
        // Debug pour diagnostiquer
        error_log('[Restaurant Booking] save_advanced_settings appelé');
        error_log('[Restaurant Booking] POST data: ' . print_r($data, true));
        
        // Gérer le mode debug
        $debug_mode = isset($data['debug_mode']) ? true : false;
        error_log('[Restaurant Booking] Debug mode: ' . ($debug_mode ? 'TRUE' : 'FALSE'));
        
        // Mettre à jour l'option
        $result = update_option('restaurant_booking_debug_mode', $debug_mode ? 1 : 0);
        error_log('[Restaurant Booking] Option update result: ' . ($result ? 'SUCCESS' : 'FAILED'));
        
        // Vérifier la valeur sauvegardée
        $saved_value = get_option('restaurant_booking_debug_mode', 0);
        error_log('[Restaurant Booking] Saved value: ' . $saved_value);

        add_action('admin_notices', function() use ($debug_mode) {
            $status = $debug_mode ? __('activé', 'restaurant-booking') : __('désactivé', 'restaurant-booking');
            echo '<div class="notice notice-success"><p>' . sprintf(__('Paramètres avancés sauvegardés avec succès ! Mode debug %s.', 'restaurant-booking'), $status) . '</p></div>';
        });
    }

    /**
     * Afficher les paramètres Maps
     */
    private function display_maps_settings()
    {
        $google_maps_api_key = get_option('restaurant_booking_google_maps_api_key', '');
        $restaurant_postal_code = get_option('restaurant_booking_restaurant_postal_code', '67000');
        
        ?>
        <div class="settings-section">
            <h3><?php _e('Configuration Google Maps API', 'restaurant-booking'); ?></h3>
            <p class="description">
                <?php _e('Configurez l\'API Google Maps pour calculer les distances routières réelles entre le restaurant et les lieux d\'événements.', 'restaurant-booking'); ?>
            </p>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="google_maps_api_key"><?php _e('Clé API Google Maps', 'restaurant-booking'); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               id="google_maps_api_key" 
                               name="google_maps_api_key" 
                               value="<?php echo esc_attr($google_maps_api_key); ?>" 
                               class="regular-text" 
                               placeholder="AIzaSyC4DgH..." />
                        <p class="description">
                            <?php _e('Votre clé API Google Maps avec l\'API Distance Matrix activée.', 'restaurant-booking'); ?>
                            <br>
                            <a href="#" onclick="document.getElementById('maps-setup-guide').style.display='block'; return false;">
                                <?php _e('📖 Voir le guide de configuration', 'restaurant-booking'); ?>
                            </a>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="restaurant_address"><?php _e('Adresse complète du restaurant', 'restaurant-booking'); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               id="restaurant_address" 
                               name="restaurant_address" 
                               value="<?php echo esc_attr(get_option('restaurant_booking_restaurant_address', '1 Place Kléber, 67000 Strasbourg, France')); ?>" 
                               class="regular-text" 
                               placeholder="1 Place Kléber, 67000 Strasbourg, France" />
                        <p class="description">
                            <?php _e('Adresse complète de votre restaurant (point de départ précis pour le calcul des distances).', 'restaurant-booking'); ?>
                            <br><strong><?php _e('Exemple :', 'restaurant-booking'); ?></strong> 1 Place Kléber, 67000 Strasbourg, France
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="restaurant_postal_code"><?php _e('Code postal (fallback)', 'restaurant-booking'); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               id="restaurant_postal_code" 
                               name="restaurant_postal_code" 
                               value="<?php echo esc_attr($restaurant_postal_code); ?>" 
                               class="small-text" 
                               pattern="[0-9]{5}" 
                               maxlength="5" />
                        <p class="description">
                            <?php _e('Code postal utilisé si l\'adresse complète ne fonctionne pas.', 'restaurant-booking'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <td colspan="2">
                        <div style="background: #e7f3ff; padding: 15px; border-radius: 8px; border-left: 4px solid #0073aa;">
                            <h4 style="margin-top: 0;">ℹ️ <?php _e('Calcul de distance simplifié', 'restaurant-booking'); ?></h4>
                            <p><?php _e('Le plugin utilise désormais <strong>uniquement Google Maps API</strong> pour calculer les distances routières réelles.', 'restaurant-booking'); ?></p>
                            <p><?php _e('En cas d\'erreur temporaire de l\'API, un message clair sera affiché à l\'utilisateur plutôt que d\'utiliser une estimation approximative qui pourrait créer des incohérences.', 'restaurant-booking'); ?></p>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Guide de configuration (masqué par défaut) -->
        <div id="maps-setup-guide" style="display: none;" class="settings-section">
            <h3><?php _e('🚀 Guide de Configuration Google Maps API', 'restaurant-booking'); ?></h3>
            
            <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; border-left: 4px solid #0073aa;">
                <h4><?php _e('Étape 1 : Créer un projet Google Cloud', 'restaurant-booking'); ?></h4>
                <ol>
                    <li><?php _e('Allez sur', 'restaurant-booking'); ?> <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a></li>
                    <li><?php _e('Créez un nouveau projet ou sélectionnez un projet existant', 'restaurant-booking'); ?></li>
                    <li><?php _e('Notez l\'ID de votre projet', 'restaurant-booking'); ?></li>
                </ol>

                <h4><?php _e('Étape 2 : Activer l\'API Distance Matrix', 'restaurant-booking'); ?></h4>
                <ol>
                    <li><?php _e('Dans le menu, allez à "APIs & Services" > "Library"', 'restaurant-booking'); ?></li>
                    <li><?php _e('Recherchez "Distance Matrix API"', 'restaurant-booking'); ?></li>
                    <li><?php _e('Cliquez sur "Distance Matrix API" puis "ENABLE"', 'restaurant-booking'); ?></li>
                </ol>

                <h4><?php _e('Étape 3 : Créer une clé API', 'restaurant-booking'); ?></h4>
                <ol>
                    <li><?php _e('Allez à "APIs & Services" > "Credentials"', 'restaurant-booking'); ?></li>
                    <li><?php _e('Cliquez sur "CREATE CREDENTIALS" > "API key"', 'restaurant-booking'); ?></li>
                    <li><?php _e('Copiez la clé générée et collez-la ci-dessus', 'restaurant-booking'); ?></li>
                    <li><?php _e('(Optionnel) Restreignez la clé à votre domaine pour plus de sécurité', 'restaurant-booking'); ?></li>
                </ol>

                <h4><?php _e('💰 Coûts estimés', 'restaurant-booking'); ?></h4>
                <ul>
                    <li><?php _e('200$ de crédit gratuit par mois', 'restaurant-booking'); ?></li>
                    <li><?php _e('~10 000 calculs de distance gratuits par mois', 'restaurant-booking'); ?></li>
                    <li><?php _e('Au-delà : ~0,005$ par calcul', 'restaurant-booking'); ?></li>
                </ul>

                <div style="background: #d4edda; padding: 15px; border-radius: 4px; margin-top: 15px;">
                    <strong><?php _e('✅ Pour la plupart des restaurants :', 'restaurant-booking'); ?></strong>
                    <?php _e('Avec moins de 500 devis par mois, vous restez dans le quota gratuit !', 'restaurant-booking'); ?>
                </div>
            </div>
        </div>

        <!-- Test de connexion -->
        <?php if (!empty($google_maps_api_key)): ?>
        <div class="settings-section">
            <h3><?php _e('🧪 Test de l\'API', 'restaurant-booking'); ?></h3>
            <div style="background: #f0f6fc; padding: 15px; border-radius: 4px; margin-bottom: 15px;">
                <h4><?php _e('🌐 Informations Serveur', 'restaurant-booking'); ?></h4>
                <p><strong><?php _e('IP du serveur :', 'restaurant-booking'); ?></strong> 
                   <code><?php echo $_SERVER['SERVER_ADDR'] ?? gethostbyname($_SERVER['HTTP_HOST'] ?? 'localhost'); ?></code>
                   <button type="button" onclick="navigator.clipboard.writeText('<?php echo $_SERVER['SERVER_ADDR'] ?? gethostbyname($_SERVER['HTTP_HOST'] ?? 'localhost'); ?>')" class="button button-small">
                       <?php _e('Copier', 'restaurant-booking'); ?>
                   </button>
                </p>
                <p class="description">
                    <?php _e('Utilisez cette IP dans les restrictions Google Cloud Console.', 'restaurant-booking'); ?>
                </p>
            </div>
            
            <p>
                <button type="button" id="test-google-maps-api" class="button button-secondary">
                    <?php _e('Tester la connexion API', 'restaurant-booking'); ?>
                </button>
                <span id="api-test-result" style="margin-left: 10px;"></span>
            </p>
            <div id="api-test-details" style="display: none; background: #f9f9f9; padding: 15px; border-radius: 4px; margin-top: 10px;"></div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#test-google-maps-api').click(function() {
                const button = $(this);
                const result = $('#api-test-result');
                const details = $('#api-test-details');
                
                button.prop('disabled', true).text('<?php _e('Test en cours...', 'restaurant-booking'); ?>');
                result.html('');
                details.hide();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'test_google_maps_api',
                        nonce: '<?php echo wp_create_nonce('test_google_maps_api'); ?>',
                        api_key: $('#google_maps_api_key').val(),
                        restaurant_address: $('#restaurant_address').val()
                    },
                    success: function(response) {
                        console.log('API Test Response:', response);
                        
                        if (response.success) {
                            result.html('<span style="color: green;">✅ ' + (response.data.message || response.data) + '</span>');
                            if (response.data.details) {
                                details.html('<strong><?php _e('Détails du test :', 'restaurant-booking'); ?></strong><br>' + response.data.details).show();
                            }
                        } else {
                            result.html('<span style="color: red;">❌ ' + (response.data || 'Erreur inconnue') + '</span>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error, xhr.responseText);
                        result.html('<span style="color: red;">❌ Erreur de connexion: ' + error + '</span>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('<?php _e('Tester la connexion API', 'restaurant-booking'); ?>');
                    }
                });
            });
        });
        </script>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Nettoyer les échappements multiples d'apostrophes
     */
    private function clean_escaped_quotes($text)
    {
        if (!is_string($text)) {
            return $text;
        }
        
        // Remplacer les multiples échappements par une seule apostrophe
        $text = preg_replace('/\\\\+\'/', "'", $text);
        $text = preg_replace('/\\\\+\"/', '"', $text);
        
        return $text;
    }

}
