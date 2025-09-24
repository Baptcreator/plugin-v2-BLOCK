<?php
/**
 * Shortcode Formulaire Block V3 - Design moderne inspiré du site
 * Formulaire de réservation entièrement repensé avec design cohérent
 *
 * @package RestaurantBooking
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Shortcode_Form_V3
{
    /**
     * Initialiser le shortcode
     */
    public function __construct()
    {
        add_shortcode('restaurant_booking_form_v3', [$this, 'render_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Enregistrer les assets V3
     */
    public function enqueue_assets()
    {
        // CSS V3 - Design moderne inspiré du site
        wp_register_style(
            'restaurant-booking-form-v3',
            RESTAURANT_BOOKING_PLUGIN_URL . 'assets/css/restaurant-booking-form-v3.css',
            [],
            '3.0.0-' . time() // Force cache refresh pendant développement
        );
        
        // CSS V3 Force - Override des styles de boutons du thème
        wp_register_style(
            'restaurant-booking-form-v3-force',
            RESTAURANT_BOOKING_PLUGIN_URL . 'assets/css/restaurant-booking-form-v3-force.css',
            ['restaurant-booking-form-v3'],
            '3.0.0-' . time() // Force cache refresh pendant développement
        );
        
        // CSS V3 Ultimate - Override ultime des styles de boutons
        wp_register_style(
            'restaurant-booking-form-v3-ultimate',
            RESTAURANT_BOOKING_PLUGIN_URL . 'assets/css/restaurant-booking-form-v3-ultimate.css',
            ['restaurant-booking-form-v3-force'],
            '3.0.0-' . time() // Force cache refresh pendant développement
        );
        
        // JavaScript V3 - Code moderne et robuste
        wp_register_script(
            'restaurant-booking-form-v3',
            RESTAURANT_BOOKING_PLUGIN_URL . 'assets/js/restaurant-booking-form-v3.js',
            ['jquery'],
            '3.0.0-' . time(),
            true
        );
        
        // Configuration JavaScript
        wp_localize_script('restaurant-booking-form-v3', 'rbfV3Config', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('restaurant_booking_form_v3'),
            'texts' => [
                'loading' => __('Chargement...', 'restaurant-booking'),
                'calculating' => __('Calcul en cours...', 'restaurant-booking'),
                'error_network' => __('Erreur de connexion. Veuillez réessayer.', 'restaurant-booking'),
                'error_required' => __('Veuillez compléter ce champ', 'restaurant-booking'),
                'error_invalid_date' => __('Date invalide', 'restaurant-booking'),
                'error_min_guests' => __('Nombre minimum de convives non respecté', 'restaurant-booking'),
                'error_max_guests' => __('Nombre maximum de convives dépassé', 'restaurant-booking'),
                'success_quote' => __('Votre devis a été envoyé avec succès !', 'restaurant-booking'),
                'step_validation_error' => __('Veuillez corriger les erreurs avant de continuer', 'restaurant-booking'),
                'next_step' => __('Étape suivante', 'restaurant-booking'),
                'prev_step' => __('Étape précédente', 'restaurant-booking'),
                'submit_quote' => __('Obtenir mon devis', 'restaurant-booking')
            ]
        ]);
    }

    /**
     * Rendu du shortcode V3
     */
    public function render_shortcode($atts)
    {
        // Attributs par défaut
        $atts = shortcode_atts([
            'show_progress' => 'yes',
            'calculator_position' => 'sticky',
            'theme' => 'block', // Thème Block par défaut
            'custom_class' => '',
        ], $atts, 'restaurant_booking_form_v3');

        // Charger les assets
        wp_enqueue_style('restaurant-booking-form-v3');
        wp_enqueue_style('restaurant-booking-form-v3-force');
        wp_enqueue_style('restaurant-booking-form-v3-ultimate');
        wp_enqueue_script('restaurant-booking-form-v3');

        // Récupérer les options depuis l'admin
        $options = $this->get_options();

        // ID unique pour ce formulaire
        $form_id = 'rbf-v3-' . uniqid();

        ob_start();
        ?>
        <div id="<?php echo esc_attr($form_id); ?>" class="rbf-v3-container <?php echo esc_attr($atts['custom_class']); ?>" data-config='<?php echo json_encode($options); ?>'>
            
            <!-- En-tête du formulaire -->
            <div class="rbf-v3-header">
                <h1 class="rbf-v3-title"><?php echo esc_html($options['widget_title'] ?? 'Demande de Devis Privatisation'); ?></h1>
                <p class="rbf-v3-subtitle"><?php echo esc_html($options['widget_subtitle'] ?? 'Choisissez votre service et obtenez votre devis personnalisé'); ?></p>
            </div>

            <!-- Barre de progression (si activée) -->
            <?php if ($atts['show_progress'] === 'yes') : ?>
            <div class="rbf-v3-progress-container">
                <div class="rbf-v3-progress-bar">
                    <div class="rbf-v3-progress-fill"></div>
                </div>
                <div class="rbf-v3-progress-steps">
                    <div class="rbf-v3-step active" data-step="1">
                        <span class="rbf-v3-step-number">1</span>
                        <span class="rbf-v3-step-label">Service</span>
                    </div>
                    <div class="rbf-v3-step" data-step="2">
                        <span class="rbf-v3-step-number">2</span>
                        <span class="rbf-v3-step-label">Forfait</span>
                    </div>
                    <div class="rbf-v3-step" data-step="3">
                        <span class="rbf-v3-step-number">3</span>
                        <span class="rbf-v3-step-label">Repas</span>
                    </div>
                    <div class="rbf-v3-step" data-step="4">
                        <span class="rbf-v3-step-number">4</span>
                        <span class="rbf-v3-step-label">Buffets</span>
                    </div>
                    <div class="rbf-v3-step" data-step="5">
                        <span class="rbf-v3-step-number">5</span>
                        <span class="rbf-v3-step-label">Boissons</span>
                    </div>
                    <div class="rbf-v3-step" data-step="6">
                        <span class="rbf-v3-step-number">6</span>
                        <span class="rbf-v3-step-label">Contact</span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Messages d'erreur/succès -->
            <div class="rbf-v3-messages" style="display: none;"></div>

            <!-- Contenu dynamique des étapes -->
            <div class="rbf-v3-content">
                
                <!-- Étape 0: Sélection du service -->
                <div class="rbf-v3-step-content active" data-step="0">
                    <h2 class="rbf-v3-step-title"><?php echo esc_html($options['service_selection_title'] ?? 'Choisissez votre service'); ?></h2>
                    
                    <div class="rbf-v3-service-cards">
                        <!-- Card Restaurant -->
                        <div class="rbf-v3-service-card" data-service="restaurant">
                            <div class="rbf-v3-card-header">
                                <h3><?php echo esc_html($options['restaurant_card_title'] ?? 'PRIVATISATION DU RESTAURANT'); ?></h3>
                                <p class="rbf-v3-card-subtitle"><?php echo esc_html($options['restaurant_card_subtitle'] ?? 'De 10 à 30 personnes'); ?></p>
                            </div>
                            <div class="rbf-v3-card-body">
                                <p><?php echo esc_html($options['restaurant_card_description'] ?? 'Privatisez notre restaurant pour vos événements intimes et profitez d\'un service personnalisé dans un cadre chaleureux.'); ?></p>
                            </div>
                            <div class="rbf-v3-card-footer">
                                <button type="button" class="rbf-v3-btn rbf-v3-btn-primary" data-action="select-service" data-service="restaurant">
                                    Choisir
                                </button>
                            </div>
                        </div>

                        <!-- Card Remorque -->
                        <div class="rbf-v3-service-card" data-service="remorque">
                    <div class="rbf-v3-card-header">
                        <h3><?php echo esc_html($options['remorque_card_title'] ?? 'PRIVATISATION DE LA REMORQUE BLOCK'); ?></h3>
                        <p class="rbf-v3-card-subtitle"><?php echo esc_html($options['remorque_card_subtitle'] ?? $options['remorque_display_text'] ?? 'À partir de 20 personnes'); ?></p>
                    </div>
                            <div class="rbf-v3-card-body">
                                <p><?php echo esc_html($options['remorque_card_description'] ?? 'Notre remorque mobile se déplace pour vos événements extérieurs et grandes réceptions.'); ?></p>
                            </div>
                            <div class="rbf-v3-card-footer">
                                <button type="button" class="rbf-v3-btn rbf-v3-btn-primary" data-action="select-service" data-service="remorque">
                                    Choisir
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Les autres étapes seront chargées dynamiquement -->
                <div class="rbf-v3-dynamic-content"></div>

            </div>

            <!-- Navigation -->
            <div class="rbf-v3-navigation">
                <button type="button" class="rbf-v3-btn rbf-v3-btn-outline" id="rbf-v3-prev" style="display: none;">
                    ← Étape précédente
                </button>
                <button type="button" class="rbf-v3-btn rbf-v3-btn-primary" id="rbf-v3-next" style="display: none;">
                    Étape suivante →
                </button>
            </div>

            <!-- Calculateur de prix (position configurable) -->
            <?php if ($atts['calculator_position'] !== 'hidden') : ?>
            <div class="rbf-v3-price-calculator <?php echo esc_attr($atts['calculator_position']); ?>" style="display: none;">
                <div class="rbf-v3-calculator-header">
                    <h4>💰 Estimation de votre devis</h4>
                </div>
                <div class="rbf-v3-calculator-body">
                    <div class="rbf-v3-price-line">
                        <span>Forfait de base</span>
                        <span class="rbf-v3-price" id="rbf-v3-price-base">0 €</span>
                    </div>
                    <div class="rbf-v3-price-line">
                        <span>Suppléments</span>
                        <span class="rbf-v3-price" id="rbf-v3-price-supplements">0 €</span>
                    </div>
                    <div class="rbf-v3-price-line">
                        <span>Produits</span>
                        <span class="rbf-v3-price" id="rbf-v3-price-products">0 €</span>
                    </div>
                    <div class="rbf-v3-price-line rbf-v3-price-total">
                        <span><strong>Total estimé</strong></span>
                        <span class="rbf-v3-price" id="rbf-v3-price-total"><strong>0 €</strong></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Récupérer les options depuis l'admin
     */
    private function get_options()
    {
        // Utiliser la classe existante des options unifiées
        if (class_exists('RestaurantBooking_Options_Unified_Admin')) {
            $options_admin = new RestaurantBooking_Options_Unified_Admin();
            return $options_admin->get_options();
        }

        // Fallback avec options par défaut
        return [
            'widget_title' => 'Demande de Devis Privatisation',
            'widget_subtitle' => 'Choisissez votre service et obtenez votre devis personnalisé',
            'service_selection_title' => 'Choisissez votre service',
            'restaurant_card_title' => 'PRIVATISATION DU RESTAURANT',
            'restaurant_card_subtitle' => 'De 10 à 30 personnes',
            'restaurant_card_description' => 'Privatisez notre restaurant pour vos événements intimes et profitez d\'un service personnalisé dans un cadre chaleureux.',
            'remorque_card_title' => 'PRIVATISATION DE LA REMORQUE BLOCK',
            'remorque_card_subtitle' => 'À partir de 20 personnes',
            'remorque_card_description' => 'Notre remorque mobile se déplace pour vos événements extérieurs et grandes réceptions.',
            'restaurant_min_guests' => 10,
            'restaurant_max_guests' => 30,
            'remorque_min_guests' => 20,
            'remorque_max_guests' => 100,
        ];
    }
}

// Initialiser le shortcode
new RestaurantBooking_Shortcode_Form_V3();
