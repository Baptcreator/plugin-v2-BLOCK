<?php
/**
 * Shortcodes pour la version 2
 *
 * @package RestaurantBooking
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Shortcodes_V2
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
        add_action('init', array($this, 'register_shortcodes'));
    }

    /**
     * Enregistrer les shortcodes
     */
    public function register_shortcodes()
    {
        // Shortcode pour le formulaire unifié
        add_shortcode('restaurant_booking_form_unified', array($this, 'render_unified_form'));
        
        // Shortcodes de compatibilité (redirection vers le nouveau)
        add_shortcode('restaurant_booking_form', array($this, 'render_legacy_form'));
    }

    /**
     * Rendu du formulaire unifié
     */
    public function render_unified_form($atts)
    {
        // Vérifier que la migration v2 est terminée
        if (!class_exists('RestaurantBooking_Migration_V2')) {
            return '<div class="rb-error">Système v2 non disponible. Veuillez contacter l\'administrateur.</div>';
        }

        $migration_status = RestaurantBooking_Migration_V2::get_migration_status();
        if ($migration_status['migration_needed']) {
            return '<div class="rb-error">Migration v2 requise. <a href="' . admin_url('admin.php?page=restaurant-booking-migration') . '">Cliquez ici pour migrer</a>.</div>';
        }

        // Attributs par défaut
        $atts = shortcode_atts(array(
            'title' => 'Demande de devis privatisation',
            'subtitle' => 'Choisissez votre service et obtenez votre devis personnalisé',
            'show_progress' => 'yes',
            'calculator_position' => 'bottom',
            'primary_color' => '#243127',
            'secondary_color' => '#FFB404',
            'accent_color' => '#EF3D1D'
        ), $atts, 'restaurant_booking_form_unified');

        // Générer un ID unique
        $widget_id = 'rb-shortcode-' . uniqid();

        // Configuration du formulaire
        $config = array(
            'widget_id' => $widget_id,
            'widget_title' => $atts['title'],
            'widget_subtitle' => $atts['subtitle'],
            'show_progress_bar' => ($atts['show_progress'] === 'yes'),
            'calculator_position' => $atts['calculator_position'],
            
            // Textes service selection
            'service_selection_title' => RestaurantBooking_Settings::get('widget_service_selection_title', 'Choisissez votre service'),
            'restaurant_card' => array(
                'title' => RestaurantBooking_Settings::get('widget_restaurant_card_title', 'PRIVATISATION DU RESTAURANT'),
                'subtitle' => RestaurantBooking_Settings::get('widget_restaurant_card_subtitle', 'De 10 à 30 personnes'),
                'description' => RestaurantBooking_Settings::get('widget_restaurant_card_description', 'Privatisez notre restaurant pour vos événements intimes et profitez d\'un service personnalisé dans un cadre chaleureux.')
            ),
            'remorque_card' => array(
                'title' => RestaurantBooking_Settings::get('widget_remorque_card_title', 'Privatisation de la remorque Block'),
                'subtitle' => RestaurantBooking_Settings::get('widget_remorque_card_subtitle', 'À partir de 20 personnes'),
                'description' => RestaurantBooking_Settings::get('widget_remorque_card_description', 'Notre remorque mobile se déplace pour vos événements extérieurs et grandes réceptions.')
            ),
            
            // Messages
            'success_message' => RestaurantBooking_Settings::get('quote_success_message', 'Votre devis est d\'ores et déjà disponible dans votre boîte mail'),
            'loading_message' => 'Génération de votre devis en cours...',
            
            // Couleurs
            'colors' => array(
                'primary' => $atts['primary_color'],
                'secondary' => $atts['secondary_color'],
                'accent' => $atts['accent_color']
            )
        );

        // Enqueue des styles et scripts
        wp_enqueue_style('restaurant-booking-quote-form-unified');
        wp_enqueue_script('restaurant-booking-quote-form-unified');

        // Rendu du formulaire
        ob_start();
        $this->render_unified_form_html($config);
        return ob_get_clean();
    }

    /**
     * Rendu HTML du formulaire unifié
     */
    private function render_unified_form_html($config)
    {
        $widget_id = $config['widget_id'];
        ?>
        <div class="rb-quote-form-container" id="<?php echo esc_attr($widget_id); ?>" data-config="<?php echo esc_attr(json_encode($config)); ?>">
            
            <!-- En-tête du widget -->
            <?php if (!empty($config['widget_title']) || !empty($config['widget_subtitle'])): ?>
            <div class="rb-widget-header">
                <?php if (!empty($config['widget_title'])): ?>
                    <h2 class="rb-widget-title"><?php echo esc_html($config['widget_title']); ?></h2>
                <?php endif; ?>
                
                <?php if (!empty($config['widget_subtitle'])): ?>
                    <p class="rb-widget-subtitle"><?php echo esc_html($config['widget_subtitle']); ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Barre de progression -->
            <?php if ($config['show_progress_bar']): ?>
            <div class="rb-progress-bar" style="display: none;">
                <div class="rb-progress-steps">
                    <div class="rb-progress-step active" data-step="0">
                        <span class="rb-step-number">1</span>
                        <span class="rb-step-label"><?php _e('Service', 'restaurant-booking'); ?></span>
                    </div>
                    <div class="rb-progress-step" data-step="1">
                        <span class="rb-step-number">2</span>
                        <span class="rb-step-label"><?php _e('Forfait', 'restaurant-booking'); ?></span>
                    </div>
                    <div class="rb-progress-step" data-step="2">
                        <span class="rb-step-number">3</span>
                        <span class="rb-step-label"><?php _e('Repas', 'restaurant-booking'); ?></span>
                    </div>
                    <div class="rb-progress-step" data-step="3">
                        <span class="rb-step-number">4</span>
                        <span class="rb-step-label"><?php _e('Boissons', 'restaurant-booking'); ?></span>
                    </div>
                    <div class="rb-progress-step" data-step="4">
                        <span class="rb-step-number">5</span>
                        <span class="rb-step-label"><?php _e('Contact', 'restaurant-booking'); ?></span>
                    </div>
                </div>
                <div class="rb-progress-line">
                    <div class="rb-progress-fill"></div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Conteneur principal du formulaire -->
            <div class="rb-form-wrapper">
                
                <!-- Étape 0: Sélection du service -->
                <div class="rb-form-step active" data-step="0">
                    <div class="rb-step-header">
                        <h3 class="rb-step-title"><?php echo esc_html($config['service_selection_title']); ?></h3>
                    </div>
                    
                    <div class="rb-service-cards">
                        <!-- Card Restaurant -->
                        <div class="rb-service-card" data-service="restaurant">
                            <div class="rb-card-content">
                                <h4 class="rb-card-title"><?php echo esc_html($config['restaurant_card']['title']); ?></h4>
                                <p class="rb-card-subtitle"><?php echo esc_html($config['restaurant_card']['subtitle']); ?></p>
                                <div class="rb-card-description">
                                    <?php echo wp_kses_post($config['restaurant_card']['description']); ?>
                                </div>
                            </div>
                            <div class="rb-card-footer">
                                <button type="button" class="rb-btn rb-btn-primary rb-select-service" data-service="restaurant">
                                    <?php _e('CHOISIR', 'restaurant-booking'); ?>
                                </button>
                            </div>
                        </div>

                        <!-- Card Remorque -->
                        <div class="rb-service-card" data-service="remorque">
                            <div class="rb-card-content">
                                <h4 class="rb-card-title"><?php echo esc_html($config['remorque_card']['title']); ?></h4>
                                <p class="rb-card-subtitle"><?php echo esc_html($config['remorque_card']['subtitle']); ?></p>
                                <div class="rb-card-description">
                                    <?php echo wp_kses_post($config['remorque_card']['description']); ?>
                                </div>
                            </div>
                            <div class="rb-card-footer">
                                <button type="button" class="rb-btn rb-btn-primary rb-select-service" data-service="remorque">
                                    <?php _e('CHOISIR', 'restaurant-booking'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Les autres étapes seront chargées dynamiquement via AJAX -->
                <div class="rb-dynamic-steps"></div>

                <!-- Messages d'erreur et de succès -->
                <div class="rb-messages">
                    <div class="rb-message rb-message-error" style="display: none;"></div>
                    <div class="rb-message rb-message-success" style="display: none;"></div>
                    <div class="rb-message rb-message-loading" style="display: none;">
                        <span class="rb-loading-spinner"></span>
                        <?php echo esc_html($config['loading_message']); ?>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="rb-form-navigation" style="display: none;">
                    <button type="button" class="rb-btn rb-btn-secondary rb-btn-prev">
                        <?php _e('Précédent', 'restaurant-booking'); ?>
                    </button>
                    <button type="button" class="rb-btn rb-btn-primary rb-btn-next">
                        <?php _e('Suivant', 'restaurant-booking'); ?>
                    </button>
                    <button type="button" class="rb-btn rb-btn-accent rb-btn-submit" style="display: none;">
                        <?php _e('OBTENIR MON DEVIS ESTIMATIF', 'restaurant-booking'); ?>
                    </button>
                </div>
            </div>

            <!-- Calculateur de prix -->
            <?php if ($config['calculator_position'] !== 'hidden'): ?>
            <div class="rb-price-calculator rb-calculator-<?php echo esc_attr($config['calculator_position']); ?>" style="display: none;">
                <div class="rb-calculator-header">
                    <h4><?php _e('Montant estimatif', 'restaurant-booking'); ?></h4>
                </div>
                <div class="rb-calculator-content">
                    <div class="rb-price-breakdown">
                        <div class="rb-price-line">
                            <span class="rb-price-label"><?php _e('Forfait de base', 'restaurant-booking'); ?></span>
                            <span class="rb-price-value" data-price="base">0,00 €</span>
                        </div>
                        <div class="rb-price-line">
                            <span class="rb-price-label"><?php _e('Suppléments', 'restaurant-booking'); ?></span>
                            <span class="rb-price-value" data-price="supplements">0,00 €</span>
                        </div>
                        <div class="rb-price-line">
                            <span class="rb-price-label"><?php _e('Produits', 'restaurant-booking'); ?></span>
                            <span class="rb-price-value" data-price="products">0,00 €</span>
                        </div>
                    </div>
                    <div class="rb-price-total-line">
                        <span class="rb-price-label"><?php _e('Total estimé', 'restaurant-booking'); ?></span>
                        <span class="rb-price-total">0,00 €</span>
                    </div>
                    <div class="rb-price-disclaimer">
                        <small><?php _e('Montant indiqué estimatif', 'restaurant-booking'); ?></small>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <!-- Données cachées pour JavaScript -->
        <script type="application/json" id="<?php echo esc_attr($widget_id); ?>-data">
        <?php echo json_encode(array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('restaurant_booking_quote_form'),
            'config' => $config,
            'texts' => array(
                'error_required' => __('Ce champ est obligatoire', 'restaurant-booking'),
                'error_invalid_date' => __('Date invalide', 'restaurant-booking'),
                'error_min_guests' => __('Nombre minimum de convives non respecté', 'restaurant-booking'),
                'error_max_guests' => __('Nombre maximum de convives dépassé', 'restaurant-booking'),
                'error_network' => __('Erreur de connexion. Veuillez réessayer.', 'restaurant-booking'),
                'loading' => __('Chargement...', 'restaurant-booking'),
                'calculating' => __('Calcul en cours...', 'restaurant-booking'),
            )
        )); ?>
        </script>

        <!-- Variables CSS pour les couleurs -->
        <style>
        #<?php echo esc_attr($widget_id); ?> {
            --rb-primary-color: <?php echo esc_attr($config['colors']['primary']); ?>;
            --rb-secondary-color: <?php echo esc_attr($config['colors']['secondary']); ?>;
            --rb-accent-color: <?php echo esc_attr($config['colors']['accent']); ?>;
        }
        </style>
        <?php
    }

    /**
     * Shortcode de compatibilité (ancien système)
     */
    public function render_legacy_form($atts)
    {
        // Rediriger vers le nouveau formulaire unifié avec un message
        $atts = shortcode_atts(array(
            'type' => 'restaurant'
        ), $atts, 'restaurant_booking_form');

        // Message de migration
        $migration_notice = '<div class="rb-migration-notice" style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 20px 0; border-radius: 4px;">';
        $migration_notice .= '<h4 style="margin: 0 0 10px 0; color: #856404;">🚀 Nouveau formulaire disponible !</h4>';
        $migration_notice .= '<p style="margin: 0; color: #856404;">Ce shortcode utilise maintenant le nouveau formulaire unifié v2 avec plus de fonctionnalités.</p>';
        $migration_notice .= '</div>';

        // Utiliser le nouveau formulaire unifié
        return $migration_notice . $this->render_unified_form($atts);
    }
}
