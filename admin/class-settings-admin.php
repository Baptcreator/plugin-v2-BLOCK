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
            'integration' => __('Shortcodes & Intégration', 'restaurant-booking'),
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
     * Paramètres d'intégration et shortcodes
     */
    private function display_integration_settings()
    {
        // Charger le CSS spécifique pour cette page
        wp_enqueue_style(
            'restaurant-booking-integration',
            RESTAURANT_BOOKING_PLUGIN_URL . 'assets/css/admin-integration.css',
            array(),
            RESTAURANT_BOOKING_VERSION
        );
        
        // CSS spécifique pour corriger le débordement des widgets
        wp_enqueue_style(
            'restaurant-booking-widget-fix',
            RESTAURANT_BOOKING_PLUGIN_URL . 'assets/css/admin-widget-fix.css',
            array('restaurant-booking-integration'),
            RESTAURANT_BOOKING_VERSION
        );
        
        // JavaScript pour forcer la correction des widgets
        wp_enqueue_script(
            'restaurant-booking-widget-fix-js',
            RESTAURANT_BOOKING_PLUGIN_URL . 'assets/js/admin-widget-fix.js',
            array('jquery'),
            RESTAURANT_BOOKING_VERSION,
            true
        );
        
        ?>
        <div class="restaurant-booking-integration">
            <h1><?php _e('Guide d\'intégration - Formulaires Block & Co', 'restaurant-booking'); ?></h1>
            <h2><?php _e('🚀 Intégration Elementor', 'restaurant-booking'); ?></h2>
            
            <div class="integration-method recommended">
                <h4>✅ <?php _e('Méthode recommandée : Widgets Elementor', 'restaurant-booking'); ?></h4>
                <div class="method-content">
                    <div class="method-steps">
                        <div class="step">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h5><?php _e('Ouvrir l\'éditeur Elementor', 'restaurant-booking'); ?></h5>
                                <p><?php _e('Éditez votre page avec Elementor', 'restaurant-booking'); ?></p>
                            </div>
                        </div>
                        
                        <div class="step">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h5><?php _e('Chercher "Block"', 'restaurant-booking'); ?></h5>
                                <p><?php _e('Dans le panneau des widgets, recherchez "Block" ou "Restaurant"', 'restaurant-booking'); ?></p>
                            </div>
                        </div>
                        
                        <div class="step">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <h5><?php _e('Choisir votre formulaire', 'restaurant-booking'); ?></h5>
                                <div class="widget-options">
                                    <div class="widget-option">
                                        <div class="widget-icon">🍽️</div>
                                        <div class="widget-info">
                                            <strong><?php _e('Devis Restaurant', 'restaurant-booking'); ?></strong>
                                            <p><?php _e('Formulaire 4 étapes pour service restaurant', 'restaurant-booking'); ?></p>
                                        </div>
                                    </div>
                                    <div class="widget-option">
                                        <div class="widget-icon">🚚</div>
                                        <div class="widget-info">
                                            <strong><?php _e('Devis Remorque', 'restaurant-booking'); ?></strong>
                                            <p><?php _e('Formulaire 5 étapes pour service remorque mobile', 'restaurant-booking'); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="step">
                            <div class="step-number">4</div>
                            <div class="step-content">
                                <h5><?php _e('Glisser-déposer', 'restaurant-booking'); ?></h5>
                                <p><?php _e('Faites glisser le widget sur votre page et configurez les options', 'restaurant-booking'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="integration-method alternative">
                <h4>⚙️ <?php _e('Méthode alternative : Shortcodes', 'restaurant-booking'); ?></h4>
                <div class="method-content">
                    <p><?php _e('Si vous préférez utiliser des shortcodes, voici les codes disponibles :', 'restaurant-booking'); ?></p>
                    
                    <div class="shortcode-list">
                        <div class="shortcode-item">
                            <div class="shortcode-code">
                                <code>[restaurant_booking_form type="restaurant"]</code>
                                <button type="button" class="copy-btn" onclick="copyShortcode('[restaurant_booking_form type=&quot;restaurant&quot;]')">
                                    <?php _e('Copier', 'restaurant-booking'); ?>
                                </button>
                            </div>
                            <div class="shortcode-description">
                                <strong>🍽️ <?php _e('Formulaire Restaurant', 'restaurant-booking'); ?></strong>
                                <p><?php _e('Affiche le formulaire de devis restaurant (4 étapes)', 'restaurant-booking'); ?></p>
                            </div>
                        </div>
                        
                        <div class="shortcode-item">
                            <div class="shortcode-code">
                                <code>[restaurant_booking_form type="remorque"]</code>
                                <button type="button" class="copy-btn" onclick="copyShortcode('[restaurant_booking_form type=&quot;remorque&quot;]')">
                                    <?php _e('Copier', 'restaurant-booking'); ?>
                                </button>
                            </div>
                            <div class="shortcode-description">
                                <strong>🚚 <?php _e('Formulaire Remorque', 'restaurant-booking'); ?></strong>
                                <p><?php _e('Affiche le formulaire de devis remorque mobile (5 étapes)', 'restaurant-booking'); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="shortcode-usage">
                        <h5><?php _e('Comment utiliser les shortcodes', 'restaurant-booking'); ?></h5>
                        <div class="usage-methods">
                            <div class="usage-method">
                                <h6><?php _e('Dans Elementor', 'restaurant-booking'); ?></h6>
                                <ol>
                                    <li><?php _e('Ajoutez un widget "Shortcode"', 'restaurant-booking'); ?></li>
                                    <li><?php _e('Collez le code dans le champ shortcode', 'restaurant-booking'); ?></li>
                                    <li><?php _e('Sauvegardez et prévisualisez', 'restaurant-booking'); ?></li>
                                </ol>
                            </div>
                            <div class="usage-method">
                                <h6><?php _e('Dans l\'éditeur WordPress', 'restaurant-booking'); ?></h6>
                                <ol>
                                    <li><?php _e('Ajoutez un bloc "Shortcode"', 'restaurant-booking'); ?></li>
                                    <li><?php _e('Collez le code dans le bloc', 'restaurant-booking'); ?></li>
                                    <li><?php _e('Publiez votre page', 'restaurant-booking'); ?></li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="integration-tips">
                <h4>💡 <?php _e('Conseils d\'intégration', 'restaurant-booking'); ?></h4>
                <div class="tips-grid">
                    <div class="tip-item">
                        <div class="tip-icon">🎨</div>
                        <div class="tip-content">
                            <h5><?php _e('Design cohérent', 'restaurant-booking'); ?></h5>
                            <p><?php _e('Les formulaires utilisent automatiquement la charte graphique Block & Co', 'restaurant-booking'); ?></p>
                        </div>
                    </div>
                    <div class="tip-item">
                        <div class="tip-icon">📱</div>
                        <div class="tip-content">
                            <h5><?php _e('Responsive', 'restaurant-booking'); ?></h5>
                            <p><?php _e('Les formulaires s\'adaptent automatiquement à tous les écrans', 'restaurant-booking'); ?></p>
                        </div>
                    </div>
                    <div class="tip-item">
                        <div class="tip-icon">⚡</div>
                        <div class="tip-content">
                            <h5><?php _e('AJAX natif', 'restaurant-booking'); ?></h5>
                            <p><?php _e('Soumission sans rechargement et calculs en temps réel', 'restaurant-booking'); ?></p>
                        </div>
                    </div>
                    <div class="tip-item">
                        <div class="tip-icon">🔒</div>
                        <div class="tip-content">
                            <h5><?php _e('Sécurisé', 'restaurant-booking'); ?></h5>
                            <p><?php _e('Protection CSRF, validation serveur et sanitisation des données', 'restaurant-booking'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="integration-demo">
                <h4>🎯 <?php _e('Pages d\'exemple', 'restaurant-booking'); ?></h4>
                <p><?php _e('Voici des exemples de pages que vous pouvez créer :', 'restaurant-booking'); ?></p>
                
                <div class="demo-pages">
                    <div class="demo-page">
                        <h5>📄 <?php _e('Page "Devis Restaurant"', 'restaurant-booking'); ?></h5>
                        <div class="demo-structure">
                            <div class="demo-section">
                                <div class="demo-element">Header + Hero</div>
                                <div class="demo-element">Texte d'introduction</div>
                                <div class="demo-element highlighted">Widget "Devis Restaurant"</div>
                                <div class="demo-element">Témoignages</div>
                                <div class="demo-element">Footer</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="demo-page">
                        <h5>📄 <?php _e('Page "Devis Remorque"', 'restaurant-booking'); ?></h5>
                        <div class="demo-structure">
                            <div class="demo-section">
                                <div class="demo-element">Header + Hero</div>
                                <div class="demo-element">Galerie photos</div>
                                <div class="demo-element highlighted">Widget "Devis Remorque"</div>
                                <div class="demo-element">FAQ</div>
                                <div class="demo-element">Footer</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- Fermeture du container restaurant-booking-integration -->
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
        
        // Traitement des actions
        if (isset($_POST['save_emails'])) {
            $this->save_email_settings($_POST);
        }
        
        if (isset($_POST['test_email'])) {
            $this->test_email_configuration($_POST);
        }

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
                <?php wp_nonce_field('restaurant_booking_emails', '_wpnonce'); ?>
                <input type="hidden" name="save_emails" value="1">

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
        function copyShortcode(shortcode) {
            // Créer un élément temporaire pour la copie
            const tempInput = document.createElement('input');
            tempInput.value = shortcode;
            document.body.appendChild(tempInput);
            tempInput.select();
            
            try {
                document.execCommand('copy');
                // Feedback visuel
                event.target.textContent = '✓ Copié !';
                event.target.style.background = '#00a32a';
                
                setTimeout(() => {
                    event.target.textContent = '<?php _e('Copier', 'restaurant-booking'); ?>';
                    event.target.style.background = '#FFB404';
                }, 2000);
            } catch (err) {
                console.error('Erreur lors de la copie:', err);
                alert('Erreur lors de la copie. Veuillez copier manuellement : ' + shortcode);
            }
            
            document.body.removeChild(tempInput);
        }
        </script>
        <?php
    }

    /**
     * Sauvegarder les paramètres
     */
    public function save_settings($data)
    {
        return true;
    }

    /**
     * Sauvegarder les paramètres d'email
     */
    private function save_email_settings($data)
    {
        // Vérifier le nonce
        if (!wp_verify_nonce($data['_wpnonce'], 'restaurant_booking_emails')) {
            wp_die(__('Token de sécurité invalide', 'restaurant-booking'));
        }

        // Sauvegarder les paramètres
        $settings = array(
            'email_quote_subject' => sanitize_text_field($data['email_quote_subject'] ?? ''),
            'email_quote_header_logo' => (int) ($data['email_quote_header_logo'] ?? 0),
            'email_quote_footer_logo' => (int) ($data['email_quote_footer_logo'] ?? 0),
            'email_quote_body_html' => wp_kses_post($data['email_quote_body_html'] ?? ''),
            'admin_notification_emails' => sanitize_textarea_field($data['admin_notification_emails'] ?? ''),
        );

        foreach ($settings as $key => $value) {
            update_option('restaurant_booking_' . $key, $value);
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
}
