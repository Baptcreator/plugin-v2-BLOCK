<?php
/**
 * Classe du formulaire de devis Restaurant (4 étapes)
 *
 * @package RestaurantBooking
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Quote_Form_Restaurant
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
        add_action('wp_ajax_submit_restaurant_quote', array($this, 'handle_form_submission'));
        add_action('wp_ajax_nopriv_submit_restaurant_quote', array($this, 'handle_form_submission'));
        add_action('wp_ajax_calculate_restaurant_price', array($this, 'calculate_price_ajax'));
        add_action('wp_ajax_nopriv_calculate_restaurant_price', array($this, 'calculate_price_ajax'));
        add_action('wp_ajax_check_date_availability', array($this, 'check_date_availability'));
        add_action('wp_ajax_nopriv_check_date_availability', array($this, 'check_date_availability'));
    }

    /**
     * Afficher le formulaire restaurant 4 étapes
     */
    public function render_form($config = array())
    {
        // Configuration par défaut
        $default_config = array(
            'service_type' => 'restaurant',
            'steps' => 4,
            'min_guests' => 10,
            'max_guests' => 30,
            'max_hours' => 4,
            'require_postal_code' => false,
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
        
        // Obtenir les catégories et produits
        $categories = $this->get_categories_for_restaurant();
        $products = $this->get_products_by_categories($categories);

        ob_start();
        ?>
        <div class="restaurant-plugin-form-container restaurant-plugin-service-restaurant" data-service="restaurant">
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
            
            /* Indicateur de progression */
            .restaurant-plugin-steps-indicator {
                display: flex;
                justify-content: center;
                margin-bottom: 40px;
                gap: 15px;
            }
            
            .restaurant-plugin-step-indicator {
                width: 50px;
                height: 50px;
                border-radius: 50%;
                background: #ddd;
                color: #666;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: bold;
                font-family: 'Fatkat', sans-serif;
                font-size: 18px;
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
            
            /* Calculateur de prix */
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
                    gap: 10px;
                }
                
                .restaurant-plugin-step-indicator {
                    width: 40px;
                    height: 40px;
                    font-size: 16px;
                }
                
                .restaurant-plugin-step-navigation {
                    flex-direction: column;
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

            <!-- Indicateur de progression 4 étapes -->
            <div class="restaurant-plugin-steps-indicator">
                <div class="restaurant-plugin-step-indicator active" data-step="1">1</div>
                <div class="restaurant-plugin-step-indicator" data-step="2">2</div>
                <div class="restaurant-plugin-step-indicator" data-step="3">3</div>
                <div class="restaurant-plugin-step-indicator" data-step="4">4</div>
            </div>

            <form id="restaurant-quote-form" class="restaurant-plugin-form">
                <?php wp_nonce_field('restaurant_quote_form', '_restaurant_nonce'); ?>
                <input type="hidden" name="service_type" value="restaurant">
                <input type="hidden" name="current_step" id="current_step" value="1">

                <!-- ÉTAPE 1/4 : Forfait de base -->
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
                               placeholder="Entre <?php echo esc_attr($config['min_guests']); ?> et <?php echo esc_attr($config['max_guests']); ?> personnes">
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
                        </select>
                    </div>

                    <!-- Zone forfait de base dynamique -->
                    <div class="restaurant-plugin-package-info" style="background: var(--restaurant-light); padding: 20px; border-radius: 20px; margin: 20px 0;">
                        <h4 style="color: var(--restaurant-primary); margin-bottom: 15px;">Forfait Restaurant - 300,00 €</h4>
                        <div id="package-details">
                            <p>• Privatisation du restaurant</p>
                            <p>• 2 heures incluses</p>
                            <p>• Service personnalisé</p>
                            <p>• Matériel fourni</p>
                        </div>
                    </div>

                    <div class="restaurant-plugin-step-navigation">
                        <div></div>
                        <button type="button" class="restaurant-plugin-btn-primary" onclick="nextStep()">
                            Suivant : Choix des repas
                        </button>
                    </div>
                </div>

                <!-- ÉTAPE 2/4 : Formules repas -->
                <div class="restaurant-plugin-form-step" data-step="2">
                    <h3 class="restaurant-plugin-step-title">
                        <?php echo esc_html($settings['form_step2_title'] ?? 'Choix des formules repas'); ?>
                    </h3>
                    
                    <p style="text-align: center; color: #666; margin-bottom: 30px;">
                        Sélection obligatoire - Minimum 1 par convive pour chaque catégorie
                    </p>

                    <!-- Ici sera intégré le système complexe de sélection des produits -->
                    <div id="products-selection">
                        <?php echo $this->render_products_selection($categories, $products); ?>
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

                <!-- ÉTAPE 3/4 : Boissons -->
                <div class="restaurant-plugin-form-step" data-step="3">
                    <h3 class="restaurant-plugin-step-title">
                        <?php echo esc_html($settings['form_step3_title'] ?? 'Choix des boissons'); ?>
                    </h3>
                    
                    <p style="text-align: center; color: #666; margin-bottom: 30px;">
                        Étape optionnelle - Vous pouvez passer cette étape
                    </p>

                    <!-- Interface boissons à sections dépliables -->
                    <div id="beverages-selection">
                        <?php echo $this->render_beverages_selection(); ?>
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

                <!-- ÉTAPE 4/4 : Contact -->
                <div class="restaurant-plugin-form-step" data-step="4">
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

            <!-- Calculateur de prix temps réel -->
            <?php if ($config['calculator_position'] === 'bottom'): ?>
            <div class="restaurant-plugin-price-calculator" id="price-calculator">
                <div class="restaurant-plugin-price-total" id="total-price">
                    300,00 € TTC
                </div>
                <div class="restaurant-plugin-price-subtitle">
                    (Montant indicatif estimatif)
                </div>
                <div class="restaurant-plugin-price-detail" onclick="togglePriceDetail()">
                    Voir le détail ▼
                </div>
                <div id="price-breakdown" style="display: none; margin-top: 15px; font-size: 12px; text-align: left;">
                    <!-- Détail des coûts sera généré ici -->
                </div>
            </div>
            <?php endif; ?>

            <!-- Messages -->
            <div id="form-messages" style="display: none;"></div>
        </div>

        <!-- JavaScript du formulaire -->
        <script>
        let currentStep = 1;
        let totalPrice = 300.00;
        let priceBreakdown = {
            base: 300.00,
            duration: 0.00,
            products: 0.00,
            beverages: 0.00
        };

        function nextStep() {
            if (validateCurrentStep()) {
                if (currentStep < 4) {
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
            // Validation spécifique par étape
            const currentStepEl = document.querySelector(`[data-step="${currentStep}"]`);
            const requiredFields = currentStepEl.querySelectorAll('[required]');
            
            for (let field of requiredFields) {
                if (!field.value.trim()) {
                    field.focus();
                    showMessage('Veuillez remplir tous les champs obligatoires.', 'error');
                    return false;
                }
            }
            
            // Validations spécifiques
            if (currentStep === 1) {
                const guestCount = parseInt(document.getElementById('guest_count').value);
                if (guestCount < <?php echo esc_js($config['min_guests']); ?> || guestCount > <?php echo esc_js($config['max_guests']); ?>) {
                    showMessage('Le nombre de convives doit être entre <?php echo esc_js($config['min_guests']); ?> et <?php echo esc_js($config['max_guests']); ?> personnes.', 'error');
                    return false;
                }
            }
            
            return true;
        }

        function calculatePrice() {
            // Calcul du prix en temps réel
            const duration = parseInt(document.getElementById('event_duration')?.value || 2);
            const guestCount = parseInt(document.getElementById('guest_count')?.value || <?php echo esc_js($config['min_guests']); ?>);
            
            // Supplément durée
            priceBreakdown.duration = (duration - 2) * 50.00;
            
            // Calcul des produits sélectionnés
            // TODO: Implémenter le calcul des produits
            
            // Total
            totalPrice = priceBreakdown.base + priceBreakdown.duration + priceBreakdown.products + priceBreakdown.beverages;
            
            // Mise à jour affichage
            document.getElementById('total-price').textContent = totalPrice.toFixed(2) + ' € TTC';
            updatePriceBreakdown();
        }

        function updatePriceBreakdown() {
            const breakdown = document.getElementById('price-breakdown');
            breakdown.innerHTML = `
                <div>Forfait de base restaurant : ${priceBreakdown.base.toFixed(2)} €</div>
                ${priceBreakdown.duration > 0 ? `<div>Supplément durée : ${priceBreakdown.duration.toFixed(2)} €</div>` : ''}
                ${priceBreakdown.products > 0 ? `<div>Formules repas : ${priceBreakdown.products.toFixed(2)} €</div>` : ''}
                ${priceBreakdown.beverages > 0 ? `<div>Boissons : ${priceBreakdown.beverages.toFixed(2)} €</div>` : ''}
                <hr style="margin: 10px 0; border-color: rgba(255,255,255,0.3);">
                <div style="font-weight: bold;">TOTAL TTC : ${totalPrice.toFixed(2)} €</div>
            `;
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
            // Calculer le prix initial
            calculatePrice();
            
            // Événements de changement pour recalcul automatique
            document.getElementById('event_duration')?.addEventListener('change', calculatePrice);
            document.getElementById('guest_count')?.addEventListener('input', calculatePrice);
            
            // Soumission du formulaire
            document.getElementById('restaurant-quote-form').addEventListener('submit', function(e) {
                e.preventDefault();
                if (validateCurrentStep()) {
                    submitForm();
                }
            });
        });

        function submitForm() {
            // TODO: Implémenter la soumission AJAX
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
        );
    }

    /**
     * Obtenir les catégories pour le restaurant
     */
    private function get_categories_for_restaurant()
    {
        global $wpdb;
        
        $categories = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}restaurant_categories 
            WHERE (service_type = 'restaurant' OR service_type = 'both') 
            AND is_active = 1 
            ORDER BY display_order ASC
        "));
        
        return $categories ?: array();
    }

    /**
     * Obtenir les produits par catégories
     */
    private function get_products_by_categories($categories)
    {
        if (empty($categories)) {
            return array();
        }
        
        global $wpdb;
        $category_ids = wp_list_pluck($categories, 'id');
        $placeholders = implode(',', array_fill(0, count($category_ids), '%d'));
        
        $products = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}restaurant_products 
            WHERE category_id IN ($placeholders) 
            AND is_active = 1 
            ORDER BY category_id, display_order ASC
        ", $category_ids));
        
        // Organiser par catégorie
        $organized = array();
        foreach ($products as $product) {
            $organized[$product->category_id][] = $product;
        }
        
        return $organized;
    }

    /**
     * Rendre la sélection de produits (système complexe)
     */
    private function render_products_selection($categories, $products)
    {
        ob_start();
        ?>
        <div class="restaurant-plugin-products-selection">
            <p style="background: #fff3cd; padding: 15px; border-radius: 20px; color: #856404; margin-bottom: 30px;">
                ⚠️ <strong>Système de sélection de produits en cours de développement</strong><br>
                Cette section implémentera le système complexe décrit dans le cahier des charges avec :
                <br>• Plats signature (obligatoire)
                <br>• Recettes principales (min 1 par convive)
                <br>• Menu Mini Boss (min 1 par convive)
                <br>• Accompagnements avec options
                <br>• Système de buffets à 3 niveaux
            </p>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Rendre la sélection de boissons
     */
    private function render_beverages_selection()
    {
        ob_start();
        ?>
        <div class="restaurant-plugin-beverages-selection">
            <p style="background: #d1ecf1; padding: 15px; border-radius: 20px; color: #0c5460; margin-bottom: 30px;">
                🍷 <strong>Interface boissons en cours de développement</strong><br>
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
        if (!wp_verify_nonce($_POST['_restaurant_nonce'], 'restaurant_quote_form')) {
            wp_send_json_error(__('Token de sécurité invalide.', 'restaurant-booking'));
        }

        // TODO: Implémenter la logique de sauvegarde du devis
        wp_send_json_success(array(
            'message' => __('Devis restaurant reçu avec succès !', 'restaurant-booking'),
            'quote_id' => 'REST-' . date('Y') . '-' . rand(1000, 9999)
        ));
    }

    /**
     * Calcul de prix AJAX
     */
    public function calculate_price_ajax()
    {
        // TODO: Implémenter le calcul de prix en temps réel
        wp_send_json_success(array(
            'total' => 350.00,
            'breakdown' => array(
                'base' => 300.00,
                'duration' => 50.00
            )
        ));
    }

    /**
     * Vérifier la disponibilité d'une date
     */
    public function check_date_availability()
    {
        $date = sanitize_text_field($_POST['date'] ?? '');
        
        if (empty($date)) {
            wp_send_json_error(__('Date manquante', 'restaurant-booking'));
        }

        // TODO: Vérifier dans wp_restaurant_availability
        wp_send_json_success(array(
            'available' => true,
            'message' => __('Date disponible', 'restaurant-booking')
        ));
    }
}
