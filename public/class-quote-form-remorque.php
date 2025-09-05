<?php
/**
 * Classe du formulaire de devis Remorque (5 étapes)
 *
 * @package RestaurantBooking
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Quote_Form_Remorque
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
        add_action('wp_ajax_submit_remorque_quote', array($this, 'handle_form_submission'));
        add_action('wp_ajax_nopriv_submit_remorque_quote', array($this, 'handle_form_submission'));
        add_action('wp_ajax_calculate_remorque_price', array($this, 'calculate_price_ajax'));
        add_action('wp_ajax_nopriv_calculate_remorque_price', array($this, 'calculate_price_ajax'));
        add_action('wp_ajax_calculate_delivery_distance', array($this, 'calculate_delivery_distance'));
        add_action('wp_ajax_nopriv_calculate_delivery_distance', array($this, 'calculate_delivery_distance'));
    }

    /**
     * Afficher le formulaire remorque 5 étapes
     */
    public function render_form($config = array())
    {
        // Configuration par défaut
        $default_config = array(
            'service_type' => 'remorque',
            'steps' => 5,
            'min_guests' => 20,
            'max_guests' => 100,
            'max_hours' => 5,
            'require_postal_code' => true,
            'calculator_position' => 'bottom',
            'theme_colors' => array(
                'primary' => '#243127',
                'secondary' => '#FFB404',
                'accent' => '#EF3D1D',
            )
        );

        $config = wp_parse_args($config, $default_config);

        // Obtenir les paramètres depuis la base de données
        $settings = $this->get_form_settings();
        
        // Obtenir les zones de livraison
        $delivery_zones = $this->get_delivery_zones();

        ob_start();
        ?>
        <div class="restaurant-plugin-form-container restaurant-plugin-service-remorque" data-service="remorque">
            <!-- CSS intégré avec la charte graphique Block -->
            <style>
            :root {
                --restaurant-primary: <?php echo esc_attr($config['theme_colors']['primary']); ?>;
                --restaurant-secondary: <?php echo esc_attr($config['theme_colors']['secondary']); ?>;
                --restaurant-accent: <?php echo esc_attr($config['theme_colors']['accent']); ?>;
                --restaurant-white: #FFFFFF;
                --restaurant-light: #F6F2E7;
                --restaurant-black: #000000;
            }
            
            .restaurant-plugin-form-container {
                font-family: 'Roboto', sans-serif;
                max-width: 900px;
                margin: 0 auto;
                background: var(--restaurant-light);
                border-radius: 20px;
                padding: 30px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                position: relative;
            }
            
            .restaurant-plugin-form-container * {
                box-sizing: border-box;
            }
            
            /* Indicateur de progression 5 étapes */
            .restaurant-plugin-steps-indicator {
                display: flex;
                justify-content: center;
                margin-bottom: 40px;
                gap: 12px;
            }
            
            .restaurant-plugin-step-indicator {
                width: 45px;
                height: 45px;
                border-radius: 50%;
                background: #ddd;
                color: #666;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: bold;
                font-family: 'Fatkat', sans-serif;
                font-size: 16px;
                position: relative;
                transition: all 0.3s ease;
            }
            
            .restaurant-plugin-step-indicator.active {
                background: var(--restaurant-primary);
                color: var(--restaurant-white);
                transform: scale(1.1);
            }
            
            .restaurant-plugin-step-indicator.completed {
                background: var(--restaurant-secondary);
                color: var(--restaurant-black);
            }
            
            /* Étapes du formulaire */
            .restaurant-plugin-form-step {
                display: none;
                background: var(--restaurant-white);
                border-radius: 20px;
                padding: 30px;
                margin-bottom: 20px;
            }
            
            .restaurant-plugin-form-step.active {
                display: block;
                animation: fadeInUp 0.5s ease;
            }
            
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .restaurant-plugin-step-title {
                font-family: 'Fatkat', sans-serif;
                font-size: 24px;
                color: var(--restaurant-primary);
                margin-bottom: 20px;
                text-align: center;
            }
            
            /* Formulaires */
            .restaurant-plugin-form-group {
                margin-bottom: 20px;
            }
            
            .restaurant-plugin-form-label {
                display: block;
                font-weight: 600;
                color: var(--restaurant-primary);
                margin-bottom: 8px;
            }
            
            .restaurant-plugin-required {
                color: var(--restaurant-accent);
            }
            
            .restaurant-plugin-form-input,
            .restaurant-plugin-form-select,
            .restaurant-plugin-form-textarea {
                width: 100%;
                padding: 15px;
                border: 2px solid #ddd;
                border-radius: 20px;
                font-size: 16px;
                transition: all 0.3s ease;
                background: var(--restaurant-white);
            }
            
            .restaurant-plugin-form-input:focus,
            .restaurant-plugin-form-select:focus,
            .restaurant-plugin-form-textarea:focus {
                outline: none;
                border-color: var(--restaurant-primary);
                box-shadow: 0 0 0 3px rgba(36, 49, 39, 0.1);
            }
            
            /* Zone de calcul distance */
            .restaurant-plugin-distance-info {
                background: var(--restaurant-light);
                padding: 15px;
                border-radius: 20px;
                margin-top: 10px;
                border: 2px solid transparent;
                transition: all 0.3s ease;
            }
            
            .restaurant-plugin-distance-info.loading {
                border-color: var(--restaurant-secondary);
                background: #fff9e6;
            }
            
            .restaurant-plugin-distance-info.success {
                border-color: #28a745;
                background: #d4edda;
                color: #155724;
            }
            
            .restaurant-plugin-distance-info.error {
                border-color: var(--restaurant-accent);
                background: #f8d7da;
                color: #721c24;
            }
            
            /* Options remorque */
            .restaurant-plugin-options-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 20px;
                margin: 20px 0;
            }
            
            .restaurant-plugin-option-card {
                border: 2px solid #ddd;
                border-radius: 20px;
                padding: 20px;
                text-align: center;
                cursor: pointer;
                transition: all 0.3s ease;
                background: var(--restaurant-white);
            }
            
            .restaurant-plugin-option-card:hover {
                border-color: var(--restaurant-primary);
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(36, 49, 39, 0.1);
            }
            
            .restaurant-plugin-option-card.selected {
                border-color: var(--restaurant-primary);
                background: var(--restaurant-light);
            }
            
            .restaurant-plugin-option-icon {
                font-size: 40px;
                margin-bottom: 15px;
            }
            
            .restaurant-plugin-option-title {
                font-family: 'Fatkat', sans-serif;
                font-size: 18px;
                color: var(--restaurant-primary);
                margin-bottom: 10px;
            }
            
            .restaurant-plugin-option-price {
                font-size: 20px;
                font-weight: bold;
                color: var(--restaurant-secondary);
                margin-bottom: 10px;
            }
            
            /* Boutons */
            .restaurant-plugin-btn-primary {
                background: var(--restaurant-primary);
                color: var(--restaurant-white);
                border: none;
                border-radius: 20px;
                font-family: 'Fatkat', sans-serif;
                font-size: 18px;
                padding: 15px 30px;
                cursor: pointer;
                transition: all 0.3s ease;
                text-decoration: none;
                display: inline-block;
            }
            
            .restaurant-plugin-btn-primary:hover {
                background: transparent;
                color: var(--restaurant-primary);
                border: 2px solid var(--restaurant-primary);
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(36, 49, 39, 0.2);
            }
            
            .restaurant-plugin-btn-secondary {
                background: transparent;
                color: var(--restaurant-primary);
                border: 2px solid var(--restaurant-primary);
                border-radius: 20px;
                font-family: 'Fatkat', sans-serif;
                font-size: 18px;
                padding: 15px 30px;
                cursor: pointer;
                transition: all 0.3s ease;
                text-decoration: none;
                display: inline-block;
            }
            
            .restaurant-plugin-btn-secondary:hover {
                background: var(--restaurant-primary);
                color: var(--restaurant-white);
            }
            
            /* Navigation entre étapes */
            .restaurant-plugin-step-navigation {
                display: flex;
                justify-content: space-between;
                margin-top: 30px;
                gap: 20px;
            }
            
            /* Calculateur de prix remorque */
            .restaurant-plugin-price-calculator {
                background: var(--restaurant-primary);
                color: var(--restaurant-white);
                padding: 20px;
                border-radius: 20px;
                text-align: center;
                position: sticky;
                bottom: 20px;
                margin-top: 30px;
                box-shadow: 0 5px 20px rgba(36, 49, 39, 0.3);
            }
            
            .restaurant-plugin-price-total {
                font-family: 'Fatkat', sans-serif;
                font-size: 28px;
                font-weight: bold;
                margin-bottom: 5px;
            }
            
            .restaurant-plugin-price-subtitle {
                font-size: 14px;
                opacity: 0.8;
            }
            
            .restaurant-plugin-price-detail {
                font-size: 12px;
                opacity: 0.7;
                margin-top: 10px;
                cursor: pointer;
            }
            
            /* Responsive */
            @media (max-width: 768px) {
                .restaurant-plugin-form-container {
                    padding: 20px;
                    margin: 10px;
                }
                
                .restaurant-plugin-steps-indicator {
                    gap: 8px;
                }
                
                .restaurant-plugin-step-indicator {
                    width: 35px;
                    height: 35px;
                    font-size: 14px;
                }
                
                .restaurant-plugin-step-navigation {
                    flex-direction: column;
                }
                
                .restaurant-plugin-options-grid {
                    grid-template-columns: 1fr;
                }
            }
            
            /* Messages d'erreur et succès */
            .restaurant-plugin-message {
                padding: 15px 20px;
                border-radius: 20px;
                margin-bottom: 20px;
            }
            
            .restaurant-plugin-message.success {
                background: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }
            
            .restaurant-plugin-message.error {
                background: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }
            </style>

            <!-- Indicateur de progression 5 étapes -->
            <div class="restaurant-plugin-steps-indicator">
                <div class="restaurant-plugin-step-indicator active" data-step="1">1</div>
                <div class="restaurant-plugin-step-indicator" data-step="2">2</div>
                <div class="restaurant-plugin-step-indicator" data-step="3">3</div>
                <div class="restaurant-plugin-step-indicator" data-step="4">4</div>
                <div class="restaurant-plugin-step-indicator" data-step="5">5</div>
            </div>

            <form id="remorque-quote-form" class="restaurant-plugin-form">
                <?php wp_nonce_field('remorque_quote_form', '_remorque_nonce'); ?>
                <input type="hidden" name="service_type" value="remorque">
                <input type="hidden" name="current_step" id="current_step" value="1">
                <input type="hidden" name="calculated_distance" id="calculated_distance" value="">
                <input type="hidden" name="delivery_zone_id" id="delivery_zone_id" value="">

                <!-- ÉTAPE 1/5 : Forfait de base -->
                <div class="restaurant-plugin-form-step active" data-step="1">
                    <h3 class="restaurant-plugin-step-title">
                        <?php echo esc_html($settings['form_step1_title'] ?? 'Forfait de base'); ?>
                    </h3>
                    
                    <div class="restaurant-plugin-form-group">
                        <label class="restaurant-plugin-form-label">
                            <?php echo esc_html($settings['form_date_label'] ?? 'Date souhaitée événement'); ?> 
                            <span class="restaurant-plugin-required">*</span>
                        </label>
                        <input type="date" 
                               name="event_date" 
                               id="event_date"
                               class="restaurant-plugin-form-input" 
                               required
                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                    </div>

                    <div class="restaurant-plugin-form-group">
                        <label class="restaurant-plugin-form-label">
                            <?php echo esc_html($settings['form_guests_label'] ?? 'Nombre de convives'); ?> 
                            <span class="restaurant-plugin-required">*</span>
                        </label>
                        <input type="number" 
                               name="guest_count" 
                               id="guest_count"
                               class="restaurant-plugin-form-input" 
                               required
                               min="<?php echo esc_attr($config['min_guests']); ?>"
                               max="<?php echo esc_attr($config['max_guests']); ?>"
                               placeholder="Entre <?php echo esc_attr($config['min_guests']); ?> et <?php echo esc_attr($config['max_guests']); ?>+ personnes">
                    </div>

                    <div class="restaurant-plugin-form-group">
                        <label class="restaurant-plugin-form-label">
                            <?php echo esc_html($settings['form_duration_label'] ?? 'Durée souhaitée événement'); ?>
                            <span class="restaurant-plugin-required">*</span>
                        </label>
                        <select name="event_duration" id="event_duration" class="restaurant-plugin-form-select" required>
                            <option value="2">2 heures (inclus)</option>
                            <option value="3">3 heures (+50€)</option>
                            <option value="4">4 heures (+100€)</option>
                            <option value="5">5 heures (+150€)</option>
                        </select>
                    </div>

                    <div class="restaurant-plugin-form-group">
                        <label class="restaurant-plugin-form-label">
                            <?php echo esc_html($settings['form_postal_label'] ?? 'Code postal événement'); ?>
                            <span class="restaurant-plugin-required">*</span>
                        </label>
                        <input type="text" 
                               name="postal_code" 
                               id="postal_code"
                               class="restaurant-plugin-form-input" 
                               required
                               pattern="[0-9]{5}"
                               placeholder="Ex: 67000"
                               maxlength="5">
                        
                        <div id="distance-info" class="restaurant-plugin-distance-info" style="display: none;">
                            <!-- Informations distance et frais de livraison -->
                        </div>
                    </div>

                    <!-- Zone forfait de base dynamique -->
                    <div class="restaurant-plugin-package-info" style="background: var(--restaurant-light); padding: 20px; border-radius: 20px; margin: 20px 0;">
                        <h4 style="color: var(--restaurant-primary); margin-bottom: 15px;">Forfait Remorque - 350,00 €</h4>
                        <div id="package-details">
                            <p>• Remorque mobile Block</p>
                            <p>• 2 heures incluses</p>
                            <p>• Déplacement et installation</p>
                            <p>• Matériel et équipement</p>
                            <div id="additional-costs" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
                                <!-- Coûts additionnels calculés automatiquement -->
                            </div>
                        </div>
                    </div>

                    <div class="restaurant-plugin-step-navigation">
                        <div></div>
                        <button type="button" class="restaurant-plugin-btn-primary" onclick="nextStep()">
                            Suivant : Choix des repas
                        </button>
                    </div>
                </div>

                <!-- ÉTAPE 2/5 : Formules repas (identique restaurant) -->
                <div class="restaurant-plugin-form-step" data-step="2">
                    <h3 class="restaurant-plugin-step-title">
                        <?php echo esc_html($settings['form_step2_title'] ?? 'Choix des formules repas'); ?>
                    </h3>
                    
                    <p style="text-align: center; color: #666; margin-bottom: 30px;">
                        Sélection obligatoire - Minimum 1 par convive pour chaque catégorie
                    </p>

                    <!-- Système identique au restaurant -->
                    <div id="products-selection">
                        <?php echo $this->render_products_selection(); ?>
                    </div>

                    <div class="restaurant-plugin-step-navigation">
                        <button type="button" class="restaurant-plugin-btn-secondary" onclick="prevStep()">
                            Précédent
                        </button>
                        <button type="button" class="restaurant-plugin-btn-primary" onclick="nextStep()">
                            Suivant : Boissons
                        </button>
                    </div>
                </div>

                <!-- ÉTAPE 3/5 : Boissons (identique restaurant) -->
                <div class="restaurant-plugin-form-step" data-step="3">
                    <h3 class="restaurant-plugin-step-title">
                        <?php echo esc_html($settings['form_step3_title'] ?? 'Choix des boissons'); ?>
                    </h3>
                    
                    <p style="text-align: center; color: #666; margin-bottom: 30px;">
                        Étape optionnelle - Vous pouvez passer cette étape
                    </p>

                    <!-- Interface identique au restaurant -->
                    <div id="beverages-selection">
                        <?php echo $this->render_beverages_selection(); ?>
                    </div>

                    <div class="restaurant-plugin-step-navigation">
                        <button type="button" class="restaurant-plugin-btn-secondary" onclick="prevStep()">
                            Précédent
                        </button>
                        <button type="button" class="restaurant-plugin-btn-primary" onclick="nextStep()">
                            Suivant : Options
                        </button>
                    </div>
                </div>

                <!-- ÉTAPE 4/5 : Options (spécifique remorque) -->
                <div class="restaurant-plugin-form-step" data-step="4">
                    <h3 class="restaurant-plugin-step-title">
                        Options spéciales remorque
                    </h3>
                    
                    <p style="text-align: center; color: #666; margin-bottom: 30px;">
                        Étape optionnelle - Améliorez votre événement
                    </p>

                    <div class="restaurant-plugin-options-grid">
                        <!-- Option Tireuse -->
                        <div class="restaurant-plugin-option-card" data-option="tireuse" onclick="toggleOption('tireuse')">
                            <div class="restaurant-plugin-option-icon">🍺</div>
                            <div class="restaurant-plugin-option-title">MISE À DISPOSITION TIREUSE</div>
                            <div class="restaurant-plugin-option-price">50,00 €</div>
                            <p style="color: #666; font-size: 14px;">
                                Tireuse professionnelle avec système de refroidissement
                            </p>
                            <div class="restaurant-plugin-option-status" style="margin-top: 15px; font-weight: bold; color: var(--restaurant-primary);">
                                CHOISIR
                            </div>
                        </div>

                        <!-- Option Jeux -->
                        <div class="restaurant-plugin-option-card" data-option="jeux" onclick="toggleOption('jeux')">
                            <div class="restaurant-plugin-option-icon">🎯</div>
                            <div class="restaurant-plugin-option-title">INSTALLATION JEUX</div>
                            <div class="restaurant-plugin-option-price">70,00 €</div>
                            <p style="color: #666; font-size: 14px;">
                                Jeux en bois, fléchettes, pétanque, etc.
                            </p>
                            <div class="restaurant-plugin-option-status" style="margin-top: 15px; font-weight: bold; color: var(--restaurant-primary);">
                                CHOISIR
                            </div>
                        </div>
                    </div>

                    <!-- Sélection fûts pour option tireuse -->
                    <div id="barrels-selection" style="display: none; margin-top: 30px; padding: 20px; background: var(--restaurant-light); border-radius: 20px;">
                        <h4 style="color: var(--restaurant-primary); margin-bottom: 15px;">
                            Sélection des fûts (obligatoire avec tireuse)
                        </h4>
                        <p style="color: #666; margin-bottom: 20px;">
                            Interface identique à la section Boissons/Fûts - Minimum 1 fût requis
                        </p>
                        <!-- TODO: Implémenter la sélection des fûts -->
                    </div>

                    <div class="restaurant-plugin-step-navigation">
                        <button type="button" class="restaurant-plugin-btn-secondary" onclick="prevStep()">
                            Précédent
                        </button>
                        <button type="button" class="restaurant-plugin-btn-primary" onclick="nextStep()">
                            Suivant : Contact
                        </button>
                    </div>
                </div>

                <!-- ÉTAPE 5/5 : Contact (identique restaurant) -->
                <div class="restaurant-plugin-form-step" data-step="5">
                    <h3 class="restaurant-plugin-step-title">
                        <?php echo esc_html($settings['form_step4_title'] ?? 'Coordonnées / Contact'); ?>
                    </h3>

                    <div class="restaurant-plugin-form-group">
                        <label class="restaurant-plugin-form-label">
                            Nom <span class="restaurant-plugin-required">*</span>
                        </label>
                        <input type="text" name="customer_name" class="restaurant-plugin-form-input" required>
                    </div>

                    <div class="restaurant-plugin-form-group">
                        <label class="restaurant-plugin-form-label">
                            Prénom <span class="restaurant-plugin-required">*</span>
                        </label>
                        <input type="text" name="customer_firstname" class="restaurant-plugin-form-input" required>
                    </div>

                    <div class="restaurant-plugin-form-group">
                        <label class="restaurant-plugin-form-label">
                            Téléphone <span class="restaurant-plugin-required">*</span>
                        </label>
                        <input type="tel" name="customer_phone" class="restaurant-plugin-form-input" required>
                    </div>

                    <div class="restaurant-plugin-form-group">
                        <label class="restaurant-plugin-form-label">
                            Email <span class="restaurant-plugin-required">*</span>
                        </label>
                        <input type="email" name="customer_email" class="restaurant-plugin-form-input" required>
                    </div>

                    <div class="restaurant-plugin-form-group">
                        <label class="restaurant-plugin-form-label">
                            Commentaires (optionnel)
                        </label>
                        <textarea name="customer_comments" class="restaurant-plugin-form-textarea" rows="4" placeholder="Allergies, demandes spéciales, informations complémentaires..."></textarea>
                    </div>

                    <div class="restaurant-plugin-step-navigation">
                        <button type="button" class="restaurant-plugin-btn-secondary" onclick="prevStep()">
                            Précédent
                        </button>
                        <button type="submit" class="restaurant-plugin-btn-primary" style="background: var(--restaurant-secondary); color: var(--restaurant-black);">
                            OBTENIR MON DEVIS ESTIMATIF
                        </button>
                    </div>
                </div>
            </form>

            <!-- Calculateur de prix temps réel remorque -->
            <?php if ($config['calculator_position'] === 'bottom'): ?>
            <div class="restaurant-plugin-price-calculator" id="price-calculator">
                <div class="restaurant-plugin-price-total" id="total-price">
                    350,00 € TTC
                </div>
                <div class="restaurant-plugin-price-subtitle">
                    (Montant indicatif estimatif)
                </div>
                <div class="restaurant-plugin-price-detail" onclick="togglePriceDetail()">
                    Voir le détail ▼
                </div>
                <div id="price-breakdown" style="display: none; margin-top: 15px; font-size: 12px; text-align: left;">
                    <!-- Détail des coûts remorque sera généré ici -->
                </div>
            </div>
            <?php endif; ?>

            <!-- Messages -->
            <div id="form-messages" style="display: none;"></div>
        </div>

        <!-- JavaScript du formulaire remorque -->
        <script>
        let currentStep = 1;
        let totalPrice = 350.00;
        let priceBreakdown = {
            base: 350.00,
            duration: 0.00,
            guests_supplement: 0.00,
            delivery: 0.00,
            products: 0.00,
            beverages: 0.00,
            options: 0.00
        };
        let selectedOptions = [];

        function nextStep() {
            if (validateCurrentStep()) {
                if (currentStep < 5) {
                    currentStep++;
                    showStep(currentStep);
                    updateStepIndicator();
                    calculatePrice();
                }
            }
        }

        function prevStep() {
            if (currentStep > 1) {
                currentStep--;
                showStep(currentStep);
                updateStepIndicator();
            }
        }

        function showStep(step) {
            document.querySelectorAll('.restaurant-plugin-form-step').forEach(el => {
                el.classList.remove('active');
            });
            document.querySelector(`[data-step="${step}"]`).classList.add('active');
            document.getElementById('current_step').value = step;
        }

        function updateStepIndicator() {
            document.querySelectorAll('.restaurant-plugin-step-indicator').forEach((el, index) => {
                el.classList.remove('active', 'completed');
                if (index + 1 === currentStep) {
                    el.classList.add('active');
                } else if (index + 1 < currentStep) {
                    el.classList.add('completed');
                }
            });
        }

        function validateCurrentStep() {
            const currentStepEl = document.querySelector(`[data-step="${currentStep}"]`);
            const requiredFields = currentStepEl.querySelectorAll('[required]');
            
            for (let field of requiredFields) {
                if (!field.value.trim()) {
                    field.focus();
                    showMessage('Veuillez remplir tous les champs obligatoires.', 'error');
                    return false;
                }
            }
            
            // Validations spécifiques remorque
            if (currentStep === 1) {
                const guestCount = parseInt(document.getElementById('guest_count').value);
                if (guestCount < <?php echo esc_js($config['min_guests']); ?>) {
                    showMessage('Le nombre de convives doit être d\'au moins <?php echo esc_js($config['min_guests']); ?> personnes pour la remorque.', 'error');
                    return false;
                }
                
                const postalCode = document.getElementById('postal_code').value;
                if (!/^[0-9]{5}$/.test(postalCode)) {
                    showMessage('Le code postal doit contenir 5 chiffres.', 'error');
                    return false;
                }
            }
            
            return true;
        }

        function calculatePrice() {
            const duration = parseInt(document.getElementById('event_duration')?.value || 2);
            const guestCount = parseInt(document.getElementById('guest_count')?.value || <?php echo esc_js($config['min_guests']); ?>);
            
            // Supplément durée
            priceBreakdown.duration = (duration - 2) * 50.00;
            
            // Supplément convives (+50 personnes)
            priceBreakdown.guests_supplement = guestCount > 50 ? 150.00 : 0.00;
            
            // Frais de livraison (sera calculé via AJAX)
            // priceBreakdown.delivery sera mis à jour par calculateDeliveryDistance()
            
            // Options
            priceBreakdown.options = selectedOptions.reduce((total, option) => {
                if (option === 'tireuse') return total + 50.00;
                if (option === 'jeux') return total + 70.00;
                return total;
            }, 0.00);
            
            // Total
            totalPrice = Object.values(priceBreakdown).reduce((sum, value) => sum + value, 0);
            
            // Mise à jour affichage
            document.getElementById('total-price').textContent = totalPrice.toFixed(2) + ' € TTC';
            updatePriceBreakdown();
            updateAdditionalCosts();
        }

        function updatePriceBreakdown() {
            const breakdown = document.getElementById('price-breakdown');
            breakdown.innerHTML = `
                <div>Forfait de base remorque : ${priceBreakdown.base.toFixed(2)} €</div>
                ${priceBreakdown.duration > 0 ? `<div>Supplément durée (+${(priceBreakdown.duration/50).toFixed(0)}H) : ${priceBreakdown.duration.toFixed(2)} €</div>` : ''}
                ${priceBreakdown.guests_supplement > 0 ? `<div>Supplément convives (+50 > 50p) : ${priceBreakdown.guests_supplement.toFixed(2)} €</div>` : ''}
                ${priceBreakdown.delivery > 0 ? `<div>Frais livraison : ${priceBreakdown.delivery.toFixed(2)} €</div>` : ''}
                <hr style="margin: 10px 0; border-color: rgba(255,255,255,0.3);">
                <div style="font-weight: bold;">Sous-total forfait : ${(priceBreakdown.base + priceBreakdown.duration + priceBreakdown.guests_supplement + priceBreakdown.delivery).toFixed(2)} €</div>
                ${priceBreakdown.products > 0 ? `<div>Formules repas : ${priceBreakdown.products.toFixed(2)} €</div>` : ''}
                ${priceBreakdown.beverages > 0 ? `<div>Boissons : ${priceBreakdown.beverages.toFixed(2)} €</div>` : ''}
                ${priceBreakdown.options > 0 ? `<div>Options : ${priceBreakdown.options.toFixed(2)} €</div>` : ''}
                <hr style="margin: 10px 0; border-color: rgba(255,255,255,0.3);">
                <div style="font-weight: bold; font-size: 14px;">TOTAL TTC : ${totalPrice.toFixed(2)} €</div>
            `;
        }

        function updateAdditionalCosts() {
            const additionalCosts = document.getElementById('additional-costs');
            let html = '';
            
            if (priceBreakdown.duration > 0) {
                html += `<p style="color: var(--restaurant-secondary); font-weight: bold;">+ ${priceBreakdown.duration.toFixed(2)} € (supplément durée)</p>`;
            }
            if (priceBreakdown.guests_supplement > 0) {
                html += `<p style="color: var(--restaurant-secondary); font-weight: bold;">+ ${priceBreakdown.guests_supplement.toFixed(2)} € (+ de 50 convives)</p>`;
            }
            if (priceBreakdown.delivery > 0) {
                html += `<p style="color: var(--restaurant-secondary); font-weight: bold;">+ ${priceBreakdown.delivery.toFixed(2)} € (frais livraison)</p>`;
            }
            
            additionalCosts.innerHTML = html;
        }

        function calculateDeliveryDistance() {
            const postalCode = document.getElementById('postal_code').value;
            if (!/^[0-9]{5}$/.test(postalCode)) return;
            
            const distanceInfo = document.getElementById('distance-info');
            distanceInfo.className = 'restaurant-plugin-distance-info loading';
            distanceInfo.style.display = 'block';
            distanceInfo.innerHTML = '📍 Calcul de la distance en cours...';
            
            // TODO: Appel AJAX pour calculer la distance
            setTimeout(() => {
                // Simulation
                const simulatedDistance = Math.floor(Math.random() * 120) + 10;
                let deliveryPrice = 0;
                let zone = '';
                
                if (simulatedDistance <= 30) {
                    deliveryPrice = 0;
                    zone = 'Zone 0-30km';
                } else if (simulatedDistance <= 60) {
                    deliveryPrice = 50;
                    zone = 'Zone 31-60km';
                } else if (simulatedDistance <= 100) {
                    deliveryPrice = 100;
                    zone = 'Zone 61-100km';
                } else if (simulatedDistance <= 150) {
                    deliveryPrice = 150;
                    zone = 'Zone 101-150km';
                } else {
                    distanceInfo.className = 'restaurant-plugin-distance-info error';
                    distanceInfo.innerHTML = '❌ Distance trop importante (max 150km)';
                    return;
                }
                
                priceBreakdown.delivery = deliveryPrice;
                document.getElementById('calculated_distance').value = simulatedDistance;
                
                distanceInfo.className = 'restaurant-plugin-distance-info success';
                distanceInfo.innerHTML = `✅ Distance : ${simulatedDistance} km<br>Zone : ${zone}<br>Frais de livraison : ${deliveryPrice.toFixed(2)} €`;
                
                calculatePrice();
            }, 1500);
        }

        function toggleOption(option) {
            const card = document.querySelector(`[data-option="${option}"]`);
            const status = card.querySelector('.restaurant-plugin-option-status');
            
            if (selectedOptions.includes(option)) {
                selectedOptions = selectedOptions.filter(opt => opt !== option);
                card.classList.remove('selected');
                status.textContent = 'CHOISIR';
                status.style.color = 'var(--restaurant-primary)';
                
                if (option === 'tireuse') {
                    document.getElementById('barrels-selection').style.display = 'none';
                }
            } else {
                selectedOptions.push(option);
                card.classList.add('selected');
                status.textContent = 'SÉLECTIONNÉ';
                status.style.color = 'var(--restaurant-secondary)';
                
                if (option === 'tireuse') {
                    document.getElementById('barrels-selection').style.display = 'block';
                }
            }
            
            calculatePrice();
        }

        function togglePriceDetail() {
            const detail = document.getElementById('price-breakdown');
            detail.style.display = detail.style.display === 'none' ? 'block' : 'none';
        }

        function showMessage(message, type) {
            const messagesContainer = document.getElementById('form-messages');
            messagesContainer.innerHTML = `<div class="restaurant-plugin-message ${type}">${message}</div>`;
            messagesContainer.style.display = 'block';
            setTimeout(() => {
                messagesContainer.style.display = 'none';
            }, 5000);
        }

        // Événements
        document.addEventListener('DOMContentLoaded', function() {
            calculatePrice();
            
            document.getElementById('event_duration')?.addEventListener('change', calculatePrice);
            document.getElementById('guest_count')?.addEventListener('input', calculatePrice);
            document.getElementById('postal_code')?.addEventListener('input', function() {
                if (this.value.length === 5) {
                    calculateDeliveryDistance();
                }
            });
            
            document.getElementById('remorque-quote-form').addEventListener('submit', function(e) {
                e.preventDefault();
                if (validateCurrentStep()) {
                    submitForm();
                }
            });
        });

        function submitForm() {
            showMessage('Fonctionnalité de soumission en cours de développement', 'error');
        }
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtenir les paramètres du formulaire
     */
    private function get_form_settings()
    {
        // TODO: Récupérer les paramètres depuis wp_restaurant_settings
        return array(
            'form_step1_title' => 'Forfait de base',
            'form_step2_title' => 'Choix des formules repas',
            'form_step3_title' => 'Choix des boissons',
            'form_step4_title' => 'Coordonnées / Contact',
            'form_date_label' => 'Date souhaitée événement',
            'form_guests_label' => 'Nombre de convives',
            'form_duration_label' => 'Durée souhaitée événement',
            'form_postal_label' => 'Code postal événement',
        );
    }

    /**
     * Obtenir les zones de livraison
     */
    private function get_delivery_zones()
    {
        global $wpdb;
        
        $zones = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}restaurant_delivery_zones 
            WHERE is_active = 1 
            ORDER BY display_order ASC
        "));
        
        return $zones ?: array();
    }

    /**
     * Rendre la sélection de produits (identique restaurant)
     */
    private function render_products_selection()
    {
        ob_start();
        ?>
        <div class="restaurant-plugin-products-selection">
            <p style="background: #fff3cd; padding: 15px; border-radius: 20px; color: #856404; margin-bottom: 30px;">
                ⚠️ <strong>Système identique au restaurant</strong><br>
                Même système complexe de sélection avec buffets salés/sucrés
            </p>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Rendre la sélection de boissons (identique restaurant)
     */
    private function render_beverages_selection()
    {
        ob_start();
        ?>
        <div class="restaurant-plugin-beverages-selection">
            <p style="background: #d1ecf1; padding: 15px; border-radius: 20px; color: #0c5460; margin-bottom: 30px;">
                🍷 <strong>Interface identique au restaurant</strong><br>
                Sections dépliables : Softs • Vins • Bières • Fûts
            </p>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Gérer la soumission du formulaire
     */
    public function handle_form_submission()
    {
        if (!wp_verify_nonce($_POST['_remorque_nonce'], 'remorque_quote_form')) {
            wp_send_json_error(__('Token de sécurité invalide.', 'restaurant-booking'));
        }

        // TODO: Implémenter la logique de sauvegarde du devis remorque
        wp_send_json_success(array(
            'message' => __('Devis remorque reçu avec succès !', 'restaurant-booking'),
            'quote_id' => 'REM-' . date('Y') . '-' . rand(1000, 9999)
        ));
    }

    /**
     * Calcul de prix AJAX
     */
    public function calculate_price_ajax()
    {
        // TODO: Implémenter le calcul de prix remorque
        wp_send_json_success(array(
            'total' => 500.00,
            'breakdown' => array(
                'base' => 350.00,
                'duration' => 50.00,
                'delivery' => 100.00
            )
        ));
    }

    /**
     * Calculer la distance de livraison
     */
    public function calculate_delivery_distance()
    {
        $postal_code = sanitize_text_field($_POST['postal_code'] ?? '');
        
        if (empty($postal_code) || !preg_match('/^[0-9]{5}$/', $postal_code)) {
            wp_send_json_error(__('Code postal invalide', 'restaurant-booking'));
        }

        // TODO: Implémenter le calcul de distance réel
        // Pour l'instant simulation
        $distance = rand(10, 140);
        $delivery_zone = $this->get_delivery_zone_by_distance($distance);
        
        if (!$delivery_zone) {
            wp_send_json_error(__('Distance trop importante (maximum 150km)', 'restaurant-booking'));
        }

        wp_send_json_success(array(
            'distance' => $distance,
            'zone' => $delivery_zone,
            'delivery_price' => $delivery_zone->delivery_price
        ));
    }

    /**
     * Obtenir la zone de livraison par distance
     */
    private function get_delivery_zone_by_distance($distance)
    {
        global $wpdb;
        
        $zone = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}restaurant_delivery_zones 
            WHERE %d >= distance_min AND %d <= distance_max 
            AND is_active = 1 
            LIMIT 1
        ", $distance, $distance));
        
        return $zone;
    }
}
