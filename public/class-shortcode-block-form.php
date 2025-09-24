<?php
/**
 * Shortcode pour le formulaire de devis Block
 * Remplace le widget Elementor par une solution plus simple et maintenable
 *
 * @package RestaurantBooking
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Shortcode_Block_Form
{
    /**
     * Initialiser le shortcode
     */
    public function __construct()
    {
        add_shortcode('restaurant_booking_form', [$this, 'render_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Enregistrer les assets
     */
    public function enqueue_assets()
    {
        // CSS Block - FORCE CACHE REFRESH COMPLET
        wp_register_style(
            'restaurant-booking-quote-form-block',
            RESTAURANT_BOOKING_PLUGIN_URL . 'assets/css/quote-form-block.css',
            [],
            '2.0.2-REAL-' . time() . '-' . rand(1000, 9999) // Force cache refresh TOTAL
        );
        
        // JavaScript - FORCE CACHE REFRESH COMPLET
        wp_register_script(
            'restaurant-booking-quote-form-block-unified',
            RESTAURANT_BOOKING_PLUGIN_URL . 'assets/js/quote-form-block-unified.js',
            ['jquery'],
            '2.0.2-REAL-' . time() . '-' . rand(1000, 9999), // Force cache refresh TOTAL
            true
        );
        
        // Localisation JavaScript
        wp_localize_script('restaurant-booking-quote-form-block-unified', 'restaurantPluginAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('restaurant_plugin_form'),
            'texts' => [
                'loading' => __('Chargement...', 'restaurant-booking'),
                'error_network' => __('Erreur de connexion. Veuillez réessayer.', 'restaurant-booking'),
                'error_required' => __('Veuillez compléter ce champ', 'restaurant-booking'),
                'error_invalid_date' => __('Date invalide', 'restaurant-booking'),
                'error_min_guests' => __('Nombre minimum de convives non respecté', 'restaurant-booking'),
                'error_max_guests' => __('Nombre maximum de convives dépassé', 'restaurant-booking'),
                'calculating' => __('Calcul en cours...', 'restaurant-booking'),
                'success_quote' => __('Devis envoyé avec succès !', 'restaurant-booking'),
                'step_validation_error' => __('Veuillez corriger les erreurs avant de continuer', 'restaurant-booking'),
                'network_error' => __('Erreur réseau. Vérifiez votre connexion.', 'restaurant-booking'),
            ]
        ]);
    }

    /**
     * Rendu du shortcode
     */
    public function render_shortcode($atts)
    {
        // Attributs par défaut
        $atts = shortcode_atts([
            'show_progress_bar' => 'yes',
            'calculator_position' => 'sticky',
            'custom_css_class' => '',
        ], $atts, 'restaurant_booking_form');

        // Charger les assets
        wp_enqueue_style('restaurant-booking-quote-form-block');
        wp_enqueue_script('restaurant-booking-quote-form-block-unified');

        // Récupérer les options depuis l'admin
        $options = $this->get_unified_options();
        
        // Générer un ID unique
        $widget_id = 'restaurant-plugin-form-' . uniqid();
        
        // Préparer la configuration
        $config = [
            'widget_id' => $widget_id,
            'show_progress_bar' => ($atts['show_progress_bar'] === 'yes'),
            'calculator_position' => $atts['calculator_position'],
            'options' => $options,
        ];

        // Classes CSS
        $css_classes = ['restaurant-plugin-container'];
        if (!empty($atts['custom_css_class'])) {
            $css_classes[] = esc_attr($atts['custom_css_class']);
        }

        // Générer le HTML
        ob_start();
        ?>
        <div class="<?php echo implode(' ', $css_classes); ?>" 
             id="<?php echo esc_attr($widget_id); ?>" 
             data-config="<?php echo esc_attr(json_encode($config)); ?>">
             
            <?php $this->render_form_html($config, $options); ?>
            
        </div>
        <?php
        
        return ob_get_clean();
    }

    /**
     * Récupérer les options unifiées
     */
    private function get_unified_options()
    {
        if (class_exists('RestaurantBooking_Options_Unified_Admin')) {
            $instance = new RestaurantBooking_Options_Unified_Admin();
            return $instance->get_options();
        }
        
        // Fallback avec valeurs par défaut
        return [
            // Règles restaurant
            'restaurant_min_guests' => 10,
            'restaurant_max_guests' => 30,
            'restaurant_min_duration' => 2,
            'restaurant_extra_hour_price' => 50,
            
            // Règles remorque
            'remorque_min_guests' => 20,
            'remorque_max_guests' => 100,
            'remorque_staff_threshold' => 50,
            'remorque_staff_supplement' => 150,
            'remorque_extra_hour_price' => 50,
            
            // Distance
            'free_radius_km' => 30,
            'price_30_50km' => 20,
            'price_50_100km' => 70,
            'price_100_150km' => 120,
            'max_distance_km' => 150,
            
            // Options
            'tireuse_price' => 50,
            'games_price' => 70,
            
            // Produits
            'accompaniment_base_price' => 4,
            'signature_dish_min_per_person' => 1,
            'accompaniment_min_per_person' => 1,
            'buffet_sale_min_per_person' => 1,
            'buffet_sale_min_recipes' => 2,
            'buffet_sucre_min_per_person' => 1,
            'buffet_sucre_min_dishes' => 1,
            
            // Textes du formulaire
            'widget_title' => 'Demande de Devis Privatisation',
            'widget_subtitle' => 'Choisissez votre service et obtenez votre devis personnalisé',
            'service_selection_title' => 'Choisissez votre service',
            'restaurant_card_title' => 'PRIVATISATION DU RESTAURANT',
            'restaurant_card_subtitle' => 'De 10 à 30 personnes',
            'restaurant_card_description' => 'Privatisez notre restaurant pour vos événements intimes et profitez d\'un service personnalisé dans un cadre chaleureux.',
            'remorque_card_title' => 'Privatisation de la remorque Block',
            'remorque_card_subtitle' => 'À partir de 20 personnes',
            'remorque_card_description' => 'Notre remorque mobile se déplace pour vos événements extérieurs et grandes réceptions.',
            'success_message' => 'Votre devis est d\'ores et déjà disponible dans votre boîte mail',
            'loading_message' => 'Génération de votre devis en cours...',
        ];
    }

    /**
     * Rendu HTML du formulaire - identique au widget mais simplifié
     */
    private function render_form_html($config, $options)
    {
        ?>
        <!-- En-tête du formulaire -->
        <div class="restaurant-plugin-form-header restaurant-plugin-text-center">
            <h1 class="restaurant-plugin-title"><?php echo esc_html($options['widget_title']); ?></h1>
            <p class="restaurant-plugin-subtitle restaurant-plugin-text"><?php echo esc_html($options['widget_subtitle']); ?></p>
        </div>

        <!-- Barre de progression -->
        <?php if ($config['show_progress_bar']): ?>
        <div class="restaurant-plugin-progress-bar restaurant-plugin-hidden" id="progress-bar">
            <div class="restaurant-plugin-progress-steps">
                <div class="restaurant-plugin-progress-line">
                    <div class="restaurant-plugin-progress-line-fill" id="progress-fill"></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Étape 0: Sélection du service -->
        <div class="restaurant-plugin-form-step active" data-step="0">
            <div class="restaurant-plugin-step-header">
                <h2 class="restaurant-plugin-step-title"><?php echo esc_html($options['service_selection_title']); ?></h2>
            </div>
            
            <div class="restaurant-plugin-service-cards">
                <!-- Card Restaurant -->
                <div class="restaurant-plugin-service-card" data-service="restaurant">
                    <div class="service-icon">🏠</div>
                    <div class="service-title"><?php echo esc_html($options['restaurant_card_title']); ?></div>
                    <div class="service-subtitle"><?php echo esc_html($options['restaurant_card_subtitle']); ?></div>
                    <div class="service-description"><?php echo esc_html($options['restaurant_card_description']); ?></div>
                    <button type="button" class="restaurant-plugin-btn-primary" onclick="restaurantPluginSelectService('restaurant')">
                        <?php _e('CHOISIR', 'restaurant-booking'); ?>
                    </button>
                </div>
                
                <!-- Card Remorque -->
                <div class="restaurant-plugin-service-card" data-service="remorque">
                    <div class="service-icon">🚚</div>
                    <div class="service-title"><?php echo esc_html($options['remorque_card_title']); ?></div>
                    <div class="service-subtitle"><?php echo esc_html($options['remorque_guests_text'] ?? $options['remorque_card_subtitle']); ?></div>
                    <div class="service-description"><?php echo esc_html($options['remorque_card_description']); ?></div>
                    <button type="button" class="restaurant-plugin-btn-primary" onclick="restaurantPluginSelectService('remorque')">
                        <?php _e('CHOISIR', 'restaurant-booking'); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Conteneur pour les étapes dynamiques -->
        <div class="restaurant-plugin-dynamic-steps"></div>

        <!-- Messages système -->
        <div class="restaurant-plugin-messages restaurant-plugin-hidden" id="form-messages">
            <div class="restaurant-plugin-message restaurant-plugin-message-success restaurant-plugin-hidden" id="success-message">
                <span class="message-text"></span>
            </div>
            <div class="restaurant-plugin-message restaurant-plugin-message-error restaurant-plugin-hidden" id="error-message">
                <span class="message-text"></span>
            </div>
            <div class="restaurant-plugin-message restaurant-plugin-message-warning restaurant-plugin-hidden" id="warning-message">
                <span class="message-text"></span>
            </div>
            <div class="restaurant-plugin-message restaurant-plugin-message-info restaurant-plugin-hidden" id="info-message">
                <span class="message-text"></span>
            </div>
        </div>

        <!-- Navigation du formulaire -->
        <div class="restaurant-plugin-form-navigation restaurant-plugin-hidden" id="form-navigation">
            <button type="button" class="restaurant-plugin-btn-secondary restaurant-plugin-nav-button hidden" id="prev-button">
                <?php _e('← Précédent', 'restaurant-booking'); ?>
            </button>
            
            <div class="restaurant-plugin-step-indicator">
                <span id="current-step-text"></span>
            </div>
            
            <button type="button" class="restaurant-plugin-btn-primary restaurant-plugin-nav-button" id="next-button">
                <?php _e('Suivant →', 'restaurant-booking'); ?>
            </button>
        </div>

        <!-- Calculateur de prix -->
        <?php if ($config['calculator_position'] !== 'hidden'): ?>
        <div class="restaurant-plugin-price-calculator restaurant-plugin-hidden" 
             id="price-calculator" 
             data-position="<?php echo esc_attr($config['calculator_position']); ?>">
            <h4><?php _e('💰 Montant Estimatif', 'restaurant-booking'); ?></h4>
            
            <div class="restaurant-plugin-price-row">
                <span><?php _e('Forfait de base', 'restaurant-booking'); ?></span>
                <span id="price-base">0,00 €</span>
            </div>
            
            <div class="restaurant-plugin-price-row">
                <span><?php _e('Suppléments', 'restaurant-booking'); ?></span>
                <span id="price-supplements">0,00 €</span>
            </div>
            
            <div class="restaurant-plugin-price-row">
                <span><?php _e('Produits', 'restaurant-booking'); ?></span>
                <span id="price-products">0,00 €</span>
            </div>
            
            <div class="restaurant-plugin-price-total">
                <span><?php _e('TOTAL ESTIMATIF', 'restaurant-booking'); ?></span>
                <span id="price-total">0,00 €</span>
            </div>
            
            <div class="restaurant-plugin-small-text restaurant-plugin-text-center restaurant-plugin-mt-2">
                <?php _e('Montant indiqué estimatif', 'restaurant-booking'); ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Overlay de chargement -->
        <div class="restaurant-plugin-loading-overlay restaurant-plugin-hidden" id="loading-overlay">
            <div class="restaurant-plugin-loading-spinner"></div>
            <div class="restaurant-plugin-loading-text">
                <?php echo esc_html($options['loading_message']); ?>
            </div>
        </div>
        <?php
    }
}

// Initialiser le shortcode
new RestaurantBooking_Shortcode_Block_Form();
