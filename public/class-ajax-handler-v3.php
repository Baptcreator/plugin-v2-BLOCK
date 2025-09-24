<?php
/**
 * Gestionnaire AJAX pour le Formulaire Block V3
 * Traite toutes les requêtes AJAX du nouveau formulaire
 *
 * @package RestaurantBooking
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Ajax_Handler_V3
{
    /**
     * Options du plugin
     */
    private $options;

    /**
     * Constructeur
     */
    public function __construct()
    {
        // Actions AJAX pour utilisateurs connectés et non connectés
        add_action('wp_ajax_rbf_v3_load_step', [$this, 'load_step']);
        add_action('wp_ajax_nopriv_rbf_v3_load_step', [$this, 'load_step']);
        
        add_action('wp_ajax_rbf_v3_calculate_price', [$this, 'calculate_price']);
        add_action('wp_ajax_nopriv_rbf_v3_calculate_price', [$this, 'calculate_price']);
        
        add_action('wp_ajax_rbf_v3_submit_quote', [$this, 'submit_quote']);
        add_action('wp_ajax_nopriv_rbf_v3_submit_quote', [$this, 'submit_quote']);
        
        add_action('wp_ajax_rbf_v3_load_signature_products', [$this, 'load_signature_products']);
        add_action('wp_ajax_nopriv_rbf_v3_load_signature_products', [$this, 'load_signature_products']);

        // Charger les options
        $this->load_options();
    }

    /**
     * Charger les options
     */
    private function load_options()
    {
        if (class_exists('RestaurantBooking_Options_Unified_Admin')) {
            $options_admin = new RestaurantBooking_Options_Unified_Admin();
            $this->options = $options_admin->get_options();
        } else {
            $this->options = $this->get_default_options();
        }
    }

    /**
     * Charger une étape du formulaire
     */
    public function load_step()
    {
        // Vérification de sécurité
        if (!wp_verify_nonce($_POST['nonce'], 'restaurant_booking_form_v3')) {
            wp_send_json_error(['message' => 'Erreur de sécurité']);
        }

        $step = intval($_POST['step']);
        $service_type = sanitize_text_field($_POST['service_type']);
        $form_data = $this->sanitize_form_data($_POST['form_data']);

        try {
            $html = $this->generate_step_html($step, $service_type, $form_data);
            wp_send_json_success(['html' => $html]);
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Erreur lors du chargement de l\'étape']);
        }
    }

    /**
     * Calculer le prix
     */
    public function calculate_price()
    {
        // Vérification de sécurité
        if (!wp_verify_nonce($_POST['nonce'], 'restaurant_booking_form_v3')) {
            wp_send_json_error(['message' => 'Erreur de sécurité']);
        }

        $service_type = sanitize_text_field($_POST['service_type']);
        $form_data = $this->sanitize_form_data($_POST['form_data']);

        try {
            $price_data = $this->calculate_quote_price($service_type, $form_data);
            wp_send_json_success($price_data);
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Erreur lors du calcul du prix']);
        }
    }

    /**
     * Soumettre le devis
     */
    public function submit_quote()
    {
        // Vérification de sécurité
        if (!wp_verify_nonce($_POST['nonce'], 'restaurant_booking_form_v3')) {
            wp_send_json_error(['message' => 'Erreur de sécurité']);
        }

        $service_type = sanitize_text_field($_POST['service_type']);
        $form_data = $this->sanitize_form_data($_POST['form_data']);
        $price_data = $this->sanitize_form_data($_POST['price_data']);

        try {
            $quote_id = $this->create_quote($service_type, $form_data, $price_data);
            
            if ($quote_id) {
                // Envoyer l'email
                $this->send_quote_email($quote_id);
                wp_send_json_success(['quote_id' => $quote_id]);
            } else {
                wp_send_json_error(['message' => 'Erreur lors de la création du devis']);
            }
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Erreur lors de l\'envoi du devis']);
        }
    }

    /**
     * Charger les produits signature selon le type sélectionné
     */
    public function load_signature_products()
    {
        // Vérification de sécurité
        if (!wp_verify_nonce($_POST['nonce'], 'restaurant_booking_form_v3')) {
            wp_send_json_error(['message' => 'Erreur de sécurité']);
        }

        $signature_type = sanitize_text_field($_POST['signature_type']);
        $guest_count = intval($_POST['guest_count']);

        try {
            $html = $this->get_signature_products_html($signature_type, $guest_count);
            wp_send_json_success(['html' => $html]);
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Erreur lors du chargement des produits']);
        }
    }

    /**
     * Générer le HTML d'une étape
     */
    private function generate_step_html($step, $service_type, $form_data)
    {
        switch ($step) {
            case 1:
                return $this->generate_step_1_html($service_type, $form_data);
            case 2:
                return $this->generate_step_2_html($service_type, $form_data);
            case 3:
                return $this->generate_step_3_html($service_type, $form_data);
            case 4:
                return $this->generate_step_4_html($service_type, $form_data);
            case 5:
                return $this->generate_step_5_html($service_type, $form_data);
            case 6:
                return $this->generate_step_6_html($service_type, $form_data);
            case 7:
                return $this->generate_step_7_html($service_type, $form_data);
            default:
                throw new Exception('Étape invalide');
        }
    }

    /**
     * Étape 1: Pourquoi privatiser notre restaurant/remorque
     */
    private function generate_step_1_html($service_type, $form_data)
    {
        $service_name = ($service_type === 'restaurant') ? 'restaurant' : 'remorque Block';
        $steps_list = ($service_type === 'restaurant') 
            ? ['Forfait de base', 'Choix du formule repas (personnalisable)', 'Choix des boissons (optionnel)', 'Coordonnées / Contact']
            : ['Forfait de base', 'Choix du formule repas (personnalisable)', 'Choix des boissons (optionnel)', 'Choix des options (optionnel)', 'Coordonnées/Contact'];

        ob_start();
        ?>
        <div class="rbf-v3-step-content active" data-step="1">
            <h2 class="rbf-v3-step-title">Pourquoi privatiser notre <?php echo esc_html($service_name); ?> ?</h2>
            
            <div class="rbf-v3-explanation-card">
                <div class="rbf-v3-card-header">
                    <h3>Comment ça fonctionne ?</h3>
                </div>
                <div class="rbf-v3-card-body">
                    <div class="rbf-v3-steps-list">
                        <?php foreach ($steps_list as $index => $step) : ?>
                            <div class="rbf-v3-step-item">
                                <span class="rbf-v3-step-number"><?php echo ($index + 1); ?>.</span>
                                <span class="rbf-v3-step-text"><?php echo esc_html($step); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="rbf-v3-card-footer">
                    <button type="button" class="rbf-v3-btn rbf-v3-btn-primary rbf-v3-btn-full" id="rbf-v3-start-quote">
                        🎯 COMMENCER MON DEVIS
                    </button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Étape 2: Forfait de base
     */
    private function generate_step_2_html($service_type, $form_data)
    {
        $min_guests = $this->options[$service_type . '_min_guests'];
        $max_guests = $this->options[$service_type . '_max_guests'];
        $min_duration = $this->options[$service_type . '_min_duration'];
        $max_duration = ($service_type === 'restaurant') ? 4 : 5;

        ob_start();
        ?>
        <div class="rbf-v3-step-content active" data-step="2">
            <h2 class="rbf-v3-step-title">Forfait de base</h2>
            
            <div class="rbf-v3-form-grid">
                <div class="rbf-v3-form-group">
                    <label for="rbf-v3-event-date" class="rbf-v3-label required">
                        📅 Date souhaitée de l'événement
                    </label>
                    <input 
                        type="date" 
                        id="rbf-v3-event-date" 
                        name="event_date" 
                        class="rbf-v3-input" 
                        required 
                        min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                        value="<?php echo esc_attr($form_data['event_date'] ?? ''); ?>"
                    >
                    <small class="rbf-v3-help-text">Sélectionnez une date future</small>
                </div>

                <div class="rbf-v3-form-group">
                    <label for="rbf-v3-guest-count" class="rbf-v3-label required">
                        👥 Nombre de convives
                    </label>
                    <input 
                        type="number" 
                        id="rbf-v3-guest-count" 
                        name="guest_count" 
                        class="rbf-v3-input" 
                        required 
                        min="<?php echo $min_guests; ?>" 
                        max="<?php echo $max_guests; ?>"
                        value="<?php echo esc_attr($form_data['guest_count'] ?? $min_guests); ?>"
                    >
                    <small class="rbf-v3-help-text">
                        <?php echo esc_html($this->options[$service_type . '_guests_text']); ?>
                    </small>
                </div>

                <div class="rbf-v3-form-group">
                    <label for="rbf-v3-event-duration" class="rbf-v3-label required">
                        ⏰ Durée souhaitée de l'événement
                    </label>
                    <select id="rbf-v3-event-duration" name="event_duration" class="rbf-v3-select" required>
                        <?php for ($h = $min_duration; $h <= $max_duration; $h++) : ?>
                            <option value="<?php echo $h; ?>" <?php selected($form_data['event_duration'] ?? $min_duration, $h); ?>>
                                <?php echo $h; ?>H
                            </option>
                        <?php endfor; ?>
                    </select>
                    <small class="rbf-v3-help-text">
                        <?php echo esc_html($this->options[$service_type . '_duration_text']); ?>
                    </small>
                </div>

                <?php if ($service_type === 'remorque') : ?>
                <div class="rbf-v3-form-group">
                    <label for="rbf-v3-postal-code" class="rbf-v3-label required">
                        📍 Code postal de l'événement
                    </label>
                    <input 
                        type="text" 
                        id="rbf-v3-postal-code" 
                        name="postal_code" 
                        class="rbf-v3-input" 
                        required 
                        pattern="\d{5}" 
                        maxlength="5"
                        placeholder="67000"
                        value="<?php echo esc_attr($form_data['postal_code'] ?? ''); ?>"
                    >
                    <small class="rbf-v3-help-text">
                        Rayon maximum <?php echo $this->options['max_distance_km']; ?> km
                    </small>
                </div>
                <?php endif; ?>
            </div>

            <!-- Carte forfait -->
            <div class="rbf-v3-forfait-card">
                <div class="rbf-v3-card-header">
                    <h3>
                        <?php echo ($service_type === 'restaurant') 
                            ? 'FORFAIT DE BASE PRIVATISATION RESTO' 
                            : 'FORFAIT DE BASE PRIVATISATION REMORQUE BLOCK'; ?>
                    </h3>
                </div>
                <div class="rbf-v3-card-body">
                    <div class="rbf-v3-forfait-description">
                        <?php 
                        $description = $this->options[$service_type . '_forfait_description'] ?? '';
                        $items = explode('|', $description);
                        foreach ($items as $item) : ?>
                            <div class="rbf-v3-forfait-item">✓ <?php echo esc_html(trim($item)); ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Étape 3: Formules repas
     */
    private function generate_step_3_html($service_type, $form_data)
    {
        $guest_count = intval($form_data['guest_count'] ?? 10);
        
        ob_start();
        ?>
        <div class="rbf-v3-step-content active" data-step="3">
            <h2 class="rbf-v3-step-title">Choix des formules repas</h2>
            
            <div class="rbf-v3-message info">
                <div class="rbf-v3-message-content">
                    <strong>ℹ️ Information importante :</strong>
                    <span>Sélection obligatoire pour <?php echo $guest_count; ?> convives. Les quantités minimales sont calculées automatiquement.</span>
                </div>
            </div>

            <!-- Plat signature -->
            <div class="rbf-v3-product-section">
                <h3>🍽️ Choix du plat signature</h3>
                <p class="rbf-v3-help-text">
                    <em><?php echo esc_html($this->options['signature_dish_text'] ?? 'minimum 1 plat par personne'); ?></em>
                </p>
                
                <div class="rbf-v3-signature-selector">
                    <label class="rbf-v3-radio-card">
                        <input type="radio" name="signature_type" value="DOG" required data-action="load-signature-products">
                        <div class="rbf-v3-radio-content">
                            <span class="rbf-v3-radio-title">🌭 DOG</span>
                            <span class="rbf-v3-radio-subtitle">Nos hot-dogs signature</span>
                        </div>
                    </label>
                    
                    <label class="rbf-v3-radio-card">
                        <input type="radio" name="signature_type" value="CROQ" required data-action="load-signature-products">
                        <div class="rbf-v3-radio-content">
                            <span class="rbf-v3-radio-title">🥪 CROQ</span>
                            <span class="rbf-v3-radio-subtitle">Nos croque-monsieurs</span>
                        </div>
                    </label>
                </div>
                
                <div class="rbf-v3-signature-products" style="display: none;">
                    <!-- Les produits seront chargés dynamiquement selon le choix DOG/CROQ -->
                </div>
            </div>

            <!-- Menu Mini Boss -->
            <div class="rbf-v3-product-section">
                <h3>👑 Menu Mini Boss</h3>
                <p class="rbf-v3-help-text"><em>Optionnel - Pour les plus petits</em></p>
                
                <label class="rbf-v3-checkbox-card">
                    <input type="checkbox" name="mini_boss_enabled" value="1" data-action="toggle-mini-boss">
                    <div class="rbf-v3-checkbox-content">
                        <span class="rbf-v3-checkbox-title">Ajouter le menu Mini Boss</span>
                        <span class="rbf-v3-checkbox-subtitle">Menu spécialement conçu pour les enfants</span>
                    </div>
                </label>
                
                <div class="rbf-v3-mini-boss-products" style="display: none;">
                    <?php echo $this->get_mini_boss_products_html(); ?>
                </div>
            </div>

            <!-- Accompagnements -->
            <div class="rbf-v3-product-section">
                <h3>🥗 Accompagnements</h3>
                <p class="rbf-v3-help-text">
                    <em><?php echo esc_html($this->options['accompaniment_text'] ?? 'mini 1/personne'); ?></em>
                </p>
                
                <div class="rbf-v3-accompaniments-list">
                    <?php echo $this->get_accompaniments_simple_html($guest_count); ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Étape 4: Buffets
     */
    private function generate_step_4_html($service_type, $form_data)
    {
        // Récupérer les buffets depuis la base de données
        $buffet_sale_products = $this->get_products_by_category('buffet_sale');
        $buffet_sucre_products = $this->get_products_by_category('buffet_sucre');
        $guest_count = intval($form_data['guest_count'] ?? 10);
        
        ob_start();
        ?>
        <div class="rbf-v3-step-content active" data-step="4">
            <h2 class="rbf-v3-step-title">Choix du/des buffet(s)</h2>
            
            <div class="rbf-v3-message info">
                <div class="rbf-v3-message-content">
                    <strong>ℹ️ Information importante :</strong>
                    <span>Sélection obligatoire pour <?php echo $guest_count; ?> convives. Les quantités minimales sont calculées automatiquement.</span>
                </div>
            </div>

            <!-- Sélection type buffet -->
            <div class="rbf-v3-product-section">
                <h3>🍽️ Choisissez votre formule buffet :</h3>
                <p class="rbf-v3-help-text">
                    <em>Sélectionnez le type de buffet qui correspond à votre événement</em>
                </p>
                
                <div class="rbf-v3-signature-selector">
                    <label class="rbf-v3-radio-card">
                        <input type="radio" name="buffet_type" value="sale" data-action="show-buffet-section">
                        <div class="rbf-v3-radio-content">
                            <span class="rbf-v3-radio-title">🥗 Buffet salé</span>
                            <span class="rbf-v3-radio-subtitle">Plats salés uniquement</span>
                        </div>
                    </label>
                    
                    <label class="rbf-v3-radio-card">
                        <input type="radio" name="buffet_type" value="sucre" data-action="show-buffet-section">
                        <div class="rbf-v3-radio-content">
                            <span class="rbf-v3-radio-title">🍰 Buffet sucré</span>
                            <span class="rbf-v3-radio-subtitle">Desserts uniquement</span>
                        </div>
                    </label>
                    
                    <label class="rbf-v3-radio-card">
                        <input type="radio" name="buffet_type" value="both" data-action="show-buffet-section">
                        <div class="rbf-v3-radio-content">
                            <span class="rbf-v3-radio-title">🍽️ Buffets salés et sucrés</span>
                            <span class="rbf-v3-radio-subtitle">Le meilleur des deux</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Sections buffets -->
            <div class="rbf-v3-buffet-sections">
                <!-- Buffet Salé -->
                <div class="rbf-v3-buffet-section" data-buffet-type="sale" style="display: none;">
                    <div class="rbf-v3-product-section">
                        <h3>🥗 BUFFET SALÉ</h3>
                        <p class="rbf-v3-help-text">
                            <em>min 1/personne et min 2 recettes différentes</em>
                        </p>
                        
                        <div class="rbf-v3-products-grid">
                            <?php if (!empty($buffet_sale_products)) : ?>
                                <?php foreach ($buffet_sale_products as $product) : ?>
                                    <div class="rbf-v3-product-card">
                                        <div class="rbf-v3-product-image">
                                            <?php if (!empty($product->image_url)) : ?>
                                                <img src="<?php echo esc_url($product->image_url); ?>" alt="<?php echo esc_attr($product->name); ?>">
                                            <?php else : ?>
                                                <div class="rbf-v3-placeholder-image">🍽️</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="rbf-v3-product-info">
                                            <h4 class="rbf-v3-product-name"><?php echo esc_html($product->name); ?></h4>
                                            <?php if (!empty($product->description)) : ?>
                                                <p class="rbf-v3-product-description"><?php echo esc_html($product->description); ?></p>
                                            <?php endif; ?>
                                            <div class="rbf-v3-product-details">
                                                <span class="rbf-v3-product-servings"><?php echo esc_html($product->servings_per_person); ?> pers</span>
                                                <span class="rbf-v3-product-price"><?php echo number_format($product->price, 0); ?> €</span>
                                            </div>
                                            <div class="rbf-v3-product-footer">
                                                <div class="rbf-v3-quantity-selector">
                                                    <button type="button" class="rbf-v3-qty-btn rbf-v3-qty-minus" data-target="buffet_sale_<?php echo $product->id; ?>_qty">-</button>
                                                    <input type="number" name="buffet_sale_<?php echo $product->id; ?>_qty" value="0" min="0" max="999" class="rbf-v3-qty-input" readonly>
                                                    <button type="button" class="rbf-v3-qty-btn rbf-v3-qty-plus" data-target="buffet_sale_<?php echo $product->id; ?>_qty">+</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <p class="rbf-v3-no-products">Aucun plat de buffet salé disponible pour le moment.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Buffet Sucré -->
                <div class="rbf-v3-buffet-section" data-buffet-type="sucre" style="display: none;">
                    <div class="rbf-v3-product-section">
                        <h3>🍰 BUFFET SUCRÉ</h3>
                        <p class="rbf-v3-help-text">
                            <em>min 1/personne et min 1 plat</em>
                        </p>
                        
                        <div class="rbf-v3-products-grid">
                            <?php if (!empty($buffet_sucre_products)) : ?>
                                <?php foreach ($buffet_sucre_products as $product) : ?>
                                    <div class="rbf-v3-product-card">
                                        <div class="rbf-v3-product-image">
                                            <?php if (!empty($product->image_url)) : ?>
                                                <img src="<?php echo esc_url($product->image_url); ?>" alt="<?php echo esc_attr($product->name); ?>">
                                            <?php else : ?>
                                                <div class="rbf-v3-placeholder-image">🍰</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="rbf-v3-product-info">
                                            <h4 class="rbf-v3-product-name"><?php echo esc_html($product->name); ?></h4>
                                            <?php if (!empty($product->description)) : ?>
                                                <p class="rbf-v3-product-description"><?php echo esc_html($product->description); ?></p>
                                            <?php endif; ?>
                                            <div class="rbf-v3-product-details">
                                                <span class="rbf-v3-product-servings"><?php echo esc_html($product->servings_per_person); ?> pers</span>
                                                <span class="rbf-v3-product-price"><?php echo number_format($product->price, 0); ?> €</span>
                                            </div>
                                            <div class="rbf-v3-product-footer">
                                                <div class="rbf-v3-quantity-selector">
                                                    <button type="button" class="rbf-v3-qty-btn rbf-v3-qty-minus" data-target="buffet_sucre_<?php echo $product->id; ?>_qty">-</button>
                                                    <input type="number" name="buffet_sucre_<?php echo $product->id; ?>_qty" value="0" min="0" max="999" class="rbf-v3-qty-input" readonly>
                                                    <button type="button" class="rbf-v3-qty-btn rbf-v3-qty-plus" data-target="buffet_sucre_<?php echo $product->id; ?>_qty">+</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <p class="rbf-v3-no-products">Aucun dessert de buffet sucré disponible pour le moment.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Étape 5: Boissons
     */
    private function generate_step_5_html($service_type, $form_data)
    {
        // Récupérer les boissons depuis la base de données
        $soft_beverages = $this->get_beverages_by_type('soft', $service_type);
        $wine_beverages = $this->get_beverages_by_type('wines', $service_type);
        $beer_beverages = $this->get_beverages_by_type('biere_bouteille', $service_type);
        $keg_beverages = $this->get_beverages_by_type('fut', $service_type);
        
        ob_start();
        ?>
        <div class="rbf-v3-step-content active" data-step="5">
            <h2 class="rbf-v3-step-title">Choix des boissons</h2>
            
            <!-- Message d'information -->
            <div class="rbf-v3-product-section">
                <div class="rbf-v3-message info">
                    <div class="rbf-v3-message-content">
                        <strong>ℹ️ Étape optionnelle :</strong>
                        <span>Sélectionnez vos boissons pour accompagner votre événement.</span>
                    </div>
                </div>
            </div>
            
            <!-- Onglets boissons -->
            <div class="rbf-v3-drinks-tabs">
                <button type="button" class="rbf-v3-tab-btn active" data-tab="soft">🥤 Soft</button>
                <button type="button" class="rbf-v3-tab-btn" data-tab="wines">🍷 Vins</button>
                <button type="button" class="rbf-v3-tab-btn" data-tab="beers">🍺 Bières</button>
                <?php if ($service_type === 'remorque') : ?>
                <button type="button" class="rbf-v3-tab-btn" data-tab="kegs">🍻 Fûts</button>
                <?php endif; ?>
            </div>
            
            <!-- Contenu des onglets -->
            <div class="rbf-v3-drinks-content">
                <!-- Soft Drinks -->
                <div class="rbf-v3-tab-content active" data-tab="soft">
                    <?php if (!empty($soft_beverages)) : ?>
                        <div class="rbf-v3-beverages-section">
                            <h3>🌟 NOS SUGGESTIONS</h3>
                            <div class="rbf-v3-beverages-grid">
                                <?php foreach ($soft_beverages as $beverage) : ?>
                                    <?php if ($beverage['is_featured']) : ?>
                                        <div class="rbf-v3-beverage-card featured" data-product-id="<?php echo esc_attr($beverage['id']); ?>">
                                            <div class="rbf-v3-beverage-image">
                                                <?php if (!empty($beverage['image_url'])) : ?>
                                                    <img src="<?php echo esc_url($beverage['image_url']); ?>" alt="<?php echo esc_attr($beverage['name']); ?>">
                                                <?php else : ?>
                                                    <div class="rbf-v3-placeholder-image">🥤</div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="rbf-v3-beverage-info">
                                                <h4><?php echo esc_html($beverage['name']); ?></h4>
                                                <?php if (!empty($beverage['description'])) : ?>
                                                    <p class="rbf-v3-beverage-description"><?php echo esc_html($beverage['description']); ?></p>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($beverage['sizes'])) : ?>
                                                    <div class="rbf-v3-beverage-sizes">
                                                        <?php foreach ($beverage['sizes'] as $size) : ?>
                                                            <div class="rbf-v3-size-option">
                                                                <span class="rbf-v3-size-label"><?php echo esc_html($size['size_cl']); ?>cl</span>
                                                                <span class="rbf-v3-size-price"><?php echo number_format($size['price'], 2); ?>€</span>
                                                                <div class="rbf-v3-quantity-selector">
                                                                    <button type="button" class="rbf-v3-qty-btn minus" data-size-id="<?php echo esc_attr($size['id']); ?>">-</button>
                                                                    <input type="number" class="rbf-v3-qty-input" value="0" min="0" data-size-id="<?php echo esc_attr($size['id']); ?>" data-price="<?php echo esc_attr($size['price']); ?>">
                                                                    <button type="button" class="rbf-v3-qty-btn plus" data-size-id="<?php echo esc_attr($size['id']); ?>">+</button>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else : ?>
                                                    <div class="rbf-v3-beverage-sizes">
                                                        <div class="rbf-v3-size-option">
                                                            <span class="rbf-v3-size-price"><?php echo number_format($beverage['price'], 2); ?>€</span>
                                                            <div class="rbf-v3-quantity-selector">
                                                                <button type="button" class="rbf-v3-qty-btn minus" data-product-id="<?php echo esc_attr($beverage['id']); ?>">-</button>
                                                                <input type="number" class="rbf-v3-qty-input" value="0" min="0" data-product-id="<?php echo esc_attr($beverage['id']); ?>" data-price="<?php echo esc_attr($beverage['price']); ?>">
                                                                <button type="button" class="rbf-v3-qty-btn plus" data-product-id="<?php echo esc_attr($beverage['id']); ?>">+</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            
                            <h3>📋 TOUS LES SOFTS</h3>
                            <div class="rbf-v3-beverages-grid">
                                <?php foreach ($soft_beverages as $beverage) : ?>
                                    <div class="rbf-v3-beverage-card" data-product-id="<?php echo esc_attr($beverage['id']); ?>">
                                        <div class="rbf-v3-beverage-image">
                                            <?php if (!empty($beverage['image_url'])) : ?>
                                                <img src="<?php echo esc_url($beverage['image_url']); ?>" alt="<?php echo esc_attr($beverage['name']); ?>">
                                            <?php else : ?>
                                                <div class="rbf-v3-placeholder-image">🥤</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="rbf-v3-beverage-info">
                                            <h4><?php echo esc_html($beverage['name']); ?></h4>
                                            <?php if (!empty($beverage['description'])) : ?>
                                                <p class="rbf-v3-beverage-description"><?php echo esc_html($beverage['description']); ?></p>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($beverage['sizes'])) : ?>
                                                <div class="rbf-v3-beverage-sizes">
                                                    <?php foreach ($beverage['sizes'] as $size) : ?>
                                                        <div class="rbf-v3-size-option">
                                                            <span class="rbf-v3-size-label"><?php echo esc_html($size['size_cl']); ?>cl</span>
                                                            <span class="rbf-v3-size-price"><?php echo number_format($size['price'], 2); ?>€</span>
                                                            <div class="rbf-v3-quantity-selector">
                                                                <button type="button" class="rbf-v3-qty-btn minus" data-size-id="<?php echo esc_attr($size['id']); ?>">-</button>
                                                                <input type="number" class="rbf-v3-qty-input" value="0" min="0" data-size-id="<?php echo esc_attr($size['id']); ?>" data-price="<?php echo esc_attr($size['price']); ?>">
                                                                <button type="button" class="rbf-v3-qty-btn plus" data-size-id="<?php echo esc_attr($size['id']); ?>">+</button>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else : ?>
                                                <div class="rbf-v3-beverage-sizes">
                                                    <div class="rbf-v3-size-option">
                                                        <span class="rbf-v3-size-price"><?php echo number_format($beverage['price'], 2); ?>€</span>
                                                        <div class="rbf-v3-quantity-selector">
                                                            <button type="button" class="rbf-v3-qty-btn minus" data-product-id="<?php echo esc_attr($beverage['id']); ?>">-</button>
                                                            <input type="number" class="rbf-v3-qty-input" value="0" min="0" data-product-id="<?php echo esc_attr($beverage['id']); ?>" data-price="<?php echo esc_attr($beverage['price']); ?>">
                                                            <button type="button" class="rbf-v3-qty-btn plus" data-product-id="<?php echo esc_attr($beverage['id']); ?>">+</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else : ?>
                        <p class="rbf-v3-no-products">Aucune boisson sans alcool disponible pour le moment.</p>
                    <?php endif; ?>
                </div>
                
                <!-- Wines -->
                <div class="rbf-v3-tab-content" data-tab="wines">
                    <?php if (!empty($wine_beverages)) : ?>
                        <div class="rbf-v3-beverages-section">
                            <h3>🌟 NOS SUGGESTIONS</h3>
                            <div class="rbf-v3-beverages-grid">
                                <?php foreach ($wine_beverages as $beverage) : ?>
                                    <?php if ($beverage['is_featured']) : ?>
                                        <div class="rbf-v3-beverage-card featured" data-product-id="<?php echo esc_attr($beverage['id']); ?>">
                                            <div class="rbf-v3-beverage-image">
                                                <?php if (!empty($beverage['image_url'])) : ?>
                                                    <img src="<?php echo esc_url($beverage['image_url']); ?>" alt="<?php echo esc_attr($beverage['name']); ?>">
                                                <?php else : ?>
                                                    <div class="rbf-v3-placeholder-image">🍷</div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="rbf-v3-beverage-info">
                                                <h4><?php echo esc_html($beverage['name']); ?></h4>
                                                <?php if (!empty($beverage['description'])) : ?>
                                                    <p class="rbf-v3-beverage-description"><?php echo esc_html($beverage['description']); ?></p>
                                                <?php endif; ?>
                                                <?php if (!empty($beverage['alcohol_degree'])) : ?>
                                                    <p class="rbf-v3-alcohol-degree"><?php echo esc_html($beverage['alcohol_degree']); ?>°</p>
                                                <?php endif; ?>
                                                
                                                <div class="rbf-v3-beverage-sizes">
                                                    <div class="rbf-v3-size-option">
                                                        <span class="rbf-v3-size-label"><?php echo esc_html($beverage['volume_cl']); ?>cl</span>
                                                        <span class="rbf-v3-size-price"><?php echo number_format($beverage['price'], 2); ?>€</span>
                                                        <div class="rbf-v3-quantity-selector">
                                                            <button type="button" class="rbf-v3-qty-btn minus" data-product-id="<?php echo esc_attr($beverage['id']); ?>">-</button>
                                                            <input type="number" class="rbf-v3-qty-input" value="0" min="0" data-product-id="<?php echo esc_attr($beverage['id']); ?>" data-price="<?php echo esc_attr($beverage['price']); ?>">
                                                            <button type="button" class="rbf-v3-qty-btn plus" data-product-id="<?php echo esc_attr($beverage['id']); ?>">+</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            
                            <h3>📋 TOUS LES VINS</h3>
                            <div class="rbf-v3-beverages-grid">
                                <?php foreach ($wine_beverages as $beverage) : ?>
                                    <div class="rbf-v3-beverage-card" data-product-id="<?php echo esc_attr($beverage['id']); ?>">
                                        <div class="rbf-v3-beverage-image">
                                            <?php if (!empty($beverage['image_url'])) : ?>
                                                <img src="<?php echo esc_url($beverage['image_url']); ?>" alt="<?php echo esc_attr($beverage['name']); ?>">
                                            <?php else : ?>
                                                <div class="rbf-v3-placeholder-image">🍷</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="rbf-v3-beverage-info">
                                            <h4><?php echo esc_html($beverage['name']); ?></h4>
                                            <?php if (!empty($beverage['description'])) : ?>
                                                <p class="rbf-v3-beverage-description"><?php echo esc_html($beverage['description']); ?></p>
                                            <?php endif; ?>
                                            <?php if (!empty($beverage['alcohol_degree'])) : ?>
                                                <p class="rbf-v3-alcohol-degree"><?php echo esc_html($beverage['alcohol_degree']); ?>°</p>
                                            <?php endif; ?>
                                            
                                            <div class="rbf-v3-beverage-sizes">
                                                <div class="rbf-v3-size-option">
                                                    <span class="rbf-v3-size-label"><?php echo esc_html($beverage['volume_cl']); ?>cl</span>
                                                    <span class="rbf-v3-size-price"><?php echo number_format($beverage['price'], 2); ?>€</span>
                                                    <div class="rbf-v3-quantity-selector">
                                                        <button type="button" class="rbf-v3-qty-btn minus" data-product-id="<?php echo esc_attr($beverage['id']); ?>">-</button>
                                                        <input type="number" class="rbf-v3-qty-input" value="0" min="0" data-product-id="<?php echo esc_attr($beverage['id']); ?>" data-price="<?php echo esc_attr($beverage['price']); ?>">
                                                        <button type="button" class="rbf-v3-qty-btn plus" data-product-id="<?php echo esc_attr($beverage['id']); ?>">+</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else : ?>
                        <p class="rbf-v3-no-products">Aucun vin disponible pour le moment.</p>
                    <?php endif; ?>
                </div>
                
                <!-- Beers -->
                <div class="rbf-v3-tab-content" data-tab="beers">
                    <?php if (!empty($beer_beverages)) : ?>
                        <div class="rbf-v3-beverages-section">
                            <h3>🌟 NOS SUGGESTIONS</h3>
                            <div class="rbf-v3-beverages-grid">
                                <?php foreach ($beer_beverages as $beverage) : ?>
                                    <?php if ($beverage['is_featured']) : ?>
                                        <div class="rbf-v3-beverage-card featured" data-product-id="<?php echo esc_attr($beverage['id']); ?>">
                                            <div class="rbf-v3-beverage-image">
                                                <?php if (!empty($beverage['image_url'])) : ?>
                                                    <img src="<?php echo esc_url($beverage['image_url']); ?>" alt="<?php echo esc_attr($beverage['name']); ?>">
                                                <?php else : ?>
                                                    <div class="rbf-v3-placeholder-image">🍺</div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="rbf-v3-beverage-info">
                                                <h4><?php echo esc_html($beverage['name']); ?></h4>
                                                <?php if (!empty($beverage['description'])) : ?>
                                                    <p class="rbf-v3-beverage-description"><?php echo esc_html($beverage['description']); ?></p>
                                                <?php endif; ?>
                                                <?php if (!empty($beverage['alcohol_degree'])) : ?>
                                                    <p class="rbf-v3-alcohol-degree"><?php echo esc_html($beverage['alcohol_degree']); ?>°</p>
                                                <?php endif; ?>
                                                
                                                <div class="rbf-v3-beverage-sizes">
                                                    <div class="rbf-v3-size-option">
                                                        <span class="rbf-v3-size-label"><?php echo esc_html($beverage['volume_cl']); ?>cl</span>
                                                        <span class="rbf-v3-size-price"><?php echo number_format($beverage['price'], 2); ?>€</span>
                                                        <div class="rbf-v3-quantity-selector">
                                                            <button type="button" class="rbf-v3-qty-btn minus" data-product-id="<?php echo esc_attr($beverage['id']); ?>">-</button>
                                                            <input type="number" class="rbf-v3-qty-input" value="0" min="0" data-product-id="<?php echo esc_attr($beverage['id']); ?>" data-price="<?php echo esc_attr($beverage['price']); ?>">
                                                            <button type="button" class="rbf-v3-qty-btn plus" data-product-id="<?php echo esc_attr($beverage['id']); ?>">+</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            
                            <h3>📋 TOUTES LES BIÈRES</h3>
                            <div class="rbf-v3-beverages-grid">
                                <?php foreach ($beer_beverages as $beverage) : ?>
                                    <div class="rbf-v3-beverage-card" data-product-id="<?php echo esc_attr($beverage['id']); ?>">
                                        <div class="rbf-v3-beverage-image">
                                            <?php if (!empty($beverage['image_url'])) : ?>
                                                <img src="<?php echo esc_url($beverage['image_url']); ?>" alt="<?php echo esc_attr($beverage['name']); ?>">
                                            <?php else : ?>
                                                <div class="rbf-v3-placeholder-image">🍺</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="rbf-v3-beverage-info">
                                            <h4><?php echo esc_html($beverage['name']); ?></h4>
                                            <?php if (!empty($beverage['description'])) : ?>
                                                <p class="rbf-v3-beverage-description"><?php echo esc_html($beverage['description']); ?></p>
                                            <?php endif; ?>
                                            <?php if (!empty($beverage['alcohol_degree'])) : ?>
                                                <p class="rbf-v3-alcohol-degree"><?php echo esc_html($beverage['alcohol_degree']); ?>°</p>
                                            <?php endif; ?>
                                            
                                            <div class="rbf-v3-beverage-sizes">
                                                <div class="rbf-v3-size-option">
                                                    <span class="rbf-v3-size-label"><?php echo esc_html($beverage['volume_cl']); ?>cl</span>
                                                    <span class="rbf-v3-size-price"><?php echo number_format($beverage['price'], 2); ?>€</span>
                                                    <div class="rbf-v3-quantity-selector">
                                                        <button type="button" class="rbf-v3-qty-btn minus" data-product-id="<?php echo esc_attr($beverage['id']); ?>">-</button>
                                                        <input type="number" class="rbf-v3-qty-input" value="0" min="0" data-product-id="<?php echo esc_attr($beverage['id']); ?>" data-price="<?php echo esc_attr($beverage['price']); ?>">
                                                        <button type="button" class="rbf-v3-qty-btn plus" data-product-id="<?php echo esc_attr($beverage['id']); ?>">+</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else : ?>
                        <p class="rbf-v3-no-products">Aucune bière disponible pour le moment.</p>
                    <?php endif; ?>
                </div>
                
                <?php if ($service_type === 'remorque') : ?>
                <!-- Kegs -->
                <div class="rbf-v3-tab-content" data-tab="kegs">
                    <?php if (!empty($keg_beverages)) : ?>
                        <div class="rbf-v3-beverages-section">
                            <h3>🌟 NOS SUGGESTIONS</h3>
                            <div class="rbf-v3-beverages-grid">
                                <?php foreach ($keg_beverages as $beverage) : ?>
                                    <?php if ($beverage['is_featured']) : ?>
                                        <div class="rbf-v3-beverage-card featured" data-product-id="<?php echo esc_attr($beverage['id']); ?>">
                                            <div class="rbf-v3-beverage-image">
                                                <?php if (!empty($beverage['image_url'])) : ?>
                                                    <img src="<?php echo esc_url($beverage['image_url']); ?>" alt="<?php echo esc_attr($beverage['name']); ?>">
                                                <?php else : ?>
                                                    <div class="rbf-v3-placeholder-image">🍻</div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="rbf-v3-beverage-info">
                                                <h4><?php echo esc_html($beverage['name']); ?></h4>
                                                <?php if (!empty($beverage['description'])) : ?>
                                                    <p class="rbf-v3-beverage-description"><?php echo esc_html($beverage['description']); ?></p>
                                                <?php endif; ?>
                                                <?php if (!empty($beverage['alcohol_degree'])) : ?>
                                                    <p class="rbf-v3-alcohol-degree"><?php echo esc_html($beverage['alcohol_degree']); ?>°</p>
                                                <?php endif; ?>
                                                
                                                <div class="rbf-v3-beverage-sizes">
                                                    <?php if (!empty($beverage['keg_size_10l_price'])) : ?>
                                                        <div class="rbf-v3-size-option">
                                                            <span class="rbf-v3-size-label">10L</span>
                                                            <span class="rbf-v3-size-price"><?php echo number_format($beverage['keg_size_10l_price'], 2); ?>€</span>
                                                            <div class="rbf-v3-quantity-selector">
                                                                <button type="button" class="rbf-v3-qty-btn minus" data-product-id="<?php echo esc_attr($beverage['id']); ?>" data-size="10l">-</button>
                                                                <input type="number" class="rbf-v3-qty-input" value="0" min="0" data-product-id="<?php echo esc_attr($beverage['id']); ?>" data-size="10l" data-price="<?php echo esc_attr($beverage['keg_size_10l_price']); ?>">
                                                                <button type="button" class="rbf-v3-qty-btn plus" data-product-id="<?php echo esc_attr($beverage['id']); ?>" data-size="10l">+</button>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($beverage['keg_size_20l_price'])) : ?>
                                                        <div class="rbf-v3-size-option">
                                                            <span class="rbf-v3-size-label">20L</span>
                                                            <span class="rbf-v3-size-price"><?php echo number_format($beverage['keg_size_20l_price'], 2); ?>€</span>
                                                            <div class="rbf-v3-quantity-selector">
                                                                <button type="button" class="rbf-v3-qty-btn minus" data-product-id="<?php echo esc_attr($beverage['id']); ?>" data-size="20l">-</button>
                                                                <input type="number" class="rbf-v3-qty-input" value="0" min="0" data-product-id="<?php echo esc_attr($beverage['id']); ?>" data-size="20l" data-price="<?php echo esc_attr($beverage['keg_size_20l_price']); ?>">
                                                                <button type="button" class="rbf-v3-qty-btn plus" data-product-id="<?php echo esc_attr($beverage['id']); ?>" data-size="20l">+</button>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            
                            <h3>📋 TOUS LES FÛTS</h3>
                            <div class="rbf-v3-beverages-grid">
                                <?php foreach ($keg_beverages as $beverage) : ?>
                                    <div class="rbf-v3-beverage-card" data-product-id="<?php echo esc_attr($beverage['id']); ?>">
                                        <div class="rbf-v3-beverage-image">
                                            <?php if (!empty($beverage['image_url'])) : ?>
                                                <img src="<?php echo esc_url($beverage['image_url']); ?>" alt="<?php echo esc_attr($beverage['name']); ?>">
                                            <?php else : ?>
                                                <div class="rbf-v3-placeholder-image">🍻</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="rbf-v3-beverage-info">
                                            <h4><?php echo esc_html($beverage['name']); ?></h4>
                                            <?php if (!empty($beverage['description'])) : ?>
                                                <p class="rbf-v3-beverage-description"><?php echo esc_html($beverage['description']); ?></p>
                                            <?php endif; ?>
                                            <?php if (!empty($beverage['alcohol_degree'])) : ?>
                                                <p class="rbf-v3-alcohol-degree"><?php echo esc_html($beverage['alcohol_degree']); ?>°</p>
                                            <?php endif; ?>
                                            
                                            <div class="rbf-v3-beverage-sizes">
                                                <?php if (!empty($beverage['keg_size_10l_price'])) : ?>
                                                    <div class="rbf-v3-size-option">
                                                        <span class="rbf-v3-size-label">10L</span>
                                                        <span class="rbf-v3-size-price"><?php echo number_format($beverage['keg_size_10l_price'], 2); ?>€</span>
                                                        <div class="rbf-v3-quantity-selector">
                                                            <button type="button" class="rbf-v3-qty-btn minus" data-product-id="<?php echo esc_attr($beverage['id']); ?>" data-size="10l">-</button>
                                                            <input type="number" class="rbf-v3-qty-input" value="0" min="0" data-product-id="<?php echo esc_attr($beverage['id']); ?>" data-size="10l" data-price="<?php echo esc_attr($beverage['keg_size_10l_price']); ?>">
                                                            <button type="button" class="rbf-v3-qty-btn plus" data-product-id="<?php echo esc_attr($beverage['id']); ?>" data-size="10l">+</button>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (!empty($beverage['keg_size_20l_price'])) : ?>
                                                    <div class="rbf-v3-size-option">
                                                        <span class="rbf-v3-size-label">20L</span>
                                                        <span class="rbf-v3-size-price"><?php echo number_format($beverage['keg_size_20l_price'], 2); ?>€</span>
                                                        <div class="rbf-v3-quantity-selector">
                                                            <button type="button" class="rbf-v3-qty-btn minus" data-product-id="<?php echo esc_attr($beverage['id']); ?>" data-size="20l">-</button>
                                                            <input type="number" class="rbf-v3-qty-input" value="0" min="0" data-product-id="<?php echo esc_attr($beverage['id']); ?>" data-size="20l" data-price="<?php echo esc_attr($beverage['keg_size_20l_price']); ?>">
                                                            <button type="button" class="rbf-v3-qty-btn plus" data-product-id="<?php echo esc_attr($beverage['id']); ?>" data-size="20l">+</button>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else : ?>
                        <p class="rbf-v3-no-products">Aucun fût de bière disponible pour le moment.</p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Bouton "Passer cette étape" spécifique aux boissons -->
            <div class="rbf-v3-step-skip-section">
                <div class="rbf-v3-skip-info">
                    <p class="rbf-v3-skip-text">
                        <strong>ℹ️ Cette étape est optionnelle.</strong><br>
                        Vous pouvez passer directement à l'étape suivante si vous ne souhaitez pas de boissons.
                    </p>
                </div>
                <div class="rbf-v3-skip-actions">
                    <button type="button" class="rbf-v3-btn rbf-v3-btn-secondary rbf-v3-skip-step">
                        Passer cette étape →
                    </button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Récupérer les boissons par type depuis la base de données
     */
    private function get_beverages_by_type($beverage_type, $service_type)
    {
        global $wpdb;
        
        // Mapping des types d'onglets vers les types de catégories
        $category_types = array();
        switch ($beverage_type) {
            case 'soft':
                $category_types = array('soft');
                break;
            case 'wines':
                $category_types = array('vin_blanc', 'vin_rouge', 'vin_rose', 'cremant');
                break;
            case 'biere_bouteille':
                $category_types = array('biere_bouteille');
                break;
            case 'fut':
                $category_types = array('fut');
                break;
        }
        
        if (empty($category_types)) {
            return array();
        }
        
        $placeholders = implode(',', array_fill(0, count($category_types), '%s'));
        $params = $category_types;
        
        // Ajouter le filtre de service si nécessaire
        if ($service_type !== 'both') {
            $params[] = $service_type;
        }
        
        $sql = "SELECT p.*, c.name as category_name, c.type as category_type
                FROM {$wpdb->prefix}restaurant_products p
                INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
                WHERE c.type IN ($placeholders)
                AND p.is_active = 1 AND c.is_active = 1";
        
        if ($service_type !== 'both') {
            $sql .= " AND (c.service_type = %s OR c.service_type = 'both')";
        }
        
        $sql .= " ORDER BY p.is_featured DESC, p.display_order ASC, p.name ASC";
        
        $beverages = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
        
        // Traiter les résultats
        foreach ($beverages as &$beverage) {
            $beverage['price'] = (float) $beverage['price'];
            $beverage['is_featured'] = (bool) $beverage['is_featured'];
            $beverage['alcohol_degree'] = $beverage['alcohol_degree'] ? (float) $beverage['alcohol_degree'] : null;
            $beverage['volume_cl'] = $beverage['volume_cl'] ? (int) $beverage['volume_cl'] : null;
            
            // Pour les fûts, ajouter les prix spéciaux
            if ($beverage_type === 'fut') {
                $beverage['keg_size_10l_price'] = $beverage['keg_size_10l_price'] ? (float) $beverage['keg_size_10l_price'] : null;
                $beverage['keg_size_20l_price'] = $beverage['keg_size_20l_price'] ? (float) $beverage['keg_size_20l_price'] : null;
            }
            
            // Pour les boissons soft, récupérer les tailles
            if ($beverage_type === 'soft' && $beverage['has_multiple_sizes']) {
                $beverage['sizes'] = $this->get_beverage_sizes($beverage['id']);
            } else {
                $beverage['sizes'] = array();
            }
        }
        
        return $beverages;
    }
    
    /**
     * Récupérer les tailles d'une boisson
     */
    private function get_beverage_sizes($product_id)
    {
        global $wpdb;
        
        $sizes = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}restaurant_beverage_sizes 
            WHERE product_id = %d AND is_active = 1
            ORDER BY display_order ASC, size_cl ASC
        ", $product_id), ARRAY_A);
        
        foreach ($sizes as &$size) {
            $size['price'] = (float) $size['price'];
            $size['size_cl'] = (int) $size['size_cl'];
        }
        
        return $sizes;
    }


    /**
     * Étape 6: Coordonnées (dernière étape restaurant)
     */
    private function generate_step_6_html($service_type, $form_data)
    {
        // Pour la remorque, l'étape 6 est "Options" (optionnelle)
        // Pour le restaurant, l'étape 6 est "Contact" (obligatoire)
        if ($service_type === 'remorque') {
            return $this->generate_step_6_options_html($form_data);
        } else {
            return $this->generate_step_6_contact_html($form_data);
        }
    }
    
    /**
     * Étape 6: Options pour remorque (optionnelle)
     */
    private function generate_step_6_options_html($form_data)
    {
        ob_start();
        ?>
        <div class="rbf-v3-step-content active" data-step="6">
            <h2 class="rbf-v3-step-title">Options supplémentaires</h2>
            
            <!-- Message d'information -->
            <div class="rbf-v3-product-section">
                <div class="rbf-v3-message info">
                    <div class="rbf-v3-message-content">
                        <strong>ℹ️ Étape optionnelle :</strong>
                        <span>Personnalisez votre événement avec nos options supplémentaires.</span>
                    </div>
                </div>
            </div>
            
            <!-- Options disponibles -->
            <div class="rbf-v3-options-grid">
                <div class="rbf-v3-option-card">
                    <h3>🎵 Animation musicale</h3>
                    <p>DJ professionnel pour animer votre événement</p>
                    <div class="rbf-v3-option-price">+150€</div>
                    <label class="rbf-v3-checkbox-label">
                        <input type="checkbox" name="option_dj" value="1">
                        <span class="rbf-v3-checkmark"></span>
                        Ajouter cette option
                    </label>
                </div>
                
                <div class="rbf-v3-option-card">
                    <h3>🎪 Tente de réception</h3>
                    <p>Protection contre les intempéries (6x4m)</p>
                    <div class="rbf-v3-option-price">+200€</div>
                    <label class="rbf-v3-checkbox-label">
                        <input type="checkbox" name="option_tent" value="1">
                        <span class="rbf-v3-checkmark"></span>
                        Ajouter cette option
                    </label>
                </div>
                
                <div class="rbf-v3-option-card">
                    <h3>🍽️ Service vaisselle premium</h3>
                    <p>Vaisselle en porcelaine et couverts argentés</p>
                    <div class="rbf-v3-option-price">+80€</div>
                    <label class="rbf-v3-checkbox-label">
                        <input type="checkbox" name="option_premium_tableware" value="1">
                        <span class="rbf-v3-checkmark"></span>
                        Ajouter cette option
                    </label>
                </div>
                
                <div class="rbf-v3-option-card">
                    <h3>🧹 Service nettoyage renforcé</h3>
                    <p>Nettoyage complet après l'événement</p>
                    <div class="rbf-v3-option-price">+100€</div>
                    <label class="rbf-v3-checkbox-label">
                        <input type="checkbox" name="option_cleaning" value="1">
                        <span class="rbf-v3-checkmark"></span>
                        Ajouter cette option
                    </label>
                </div>
            </div>
            
            <!-- Bouton "Passer cette étape" spécifique aux options -->
            <div class="rbf-v3-step-skip-section">
                <div class="rbf-v3-skip-info">
                    <p class="rbf-v3-skip-text">
                        <strong>ℹ️ Cette étape est optionnelle.</strong><br>
                        Vous pouvez passer directement à l'étape suivante si vous ne souhaitez pas d'options supplémentaires.
                    </p>
                </div>
                <div class="rbf-v3-skip-actions">
                    <button type="button" class="rbf-v3-btn rbf-v3-btn-secondary rbf-v3-skip-step">
                        Passer cette étape →
                    </button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Étape 6: Contact pour restaurant (obligatoire)
     */
    private function generate_step_6_contact_html($form_data)
    {
        ob_start();
        ?>
        <div class="rbf-v3-step-content active" data-step="6">
            <h2 class="rbf-v3-step-title">Vos coordonnées</h2>
            
            <div class="rbf-v3-form-grid">
                <div class="rbf-v3-form-group">
                    <label for="rbf-v3-client-firstname" class="rbf-v3-label required">
                        👤 Prénom
                    </label>
                    <input 
                        type="text" 
                        id="rbf-v3-client-firstname" 
                        name="client_firstname" 
                        class="rbf-v3-input" 
                        required
                        value="<?php echo esc_attr($form_data['client_firstname'] ?? ''); ?>"
                    >
                </div>

                <div class="rbf-v3-form-group">
                    <label for="rbf-v3-client-name" class="rbf-v3-label required">
                        👤 Nom
                    </label>
                    <input 
                        type="text" 
                        id="rbf-v3-client-name" 
                        name="client_name" 
                        class="rbf-v3-input" 
                        required
                        value="<?php echo esc_attr($form_data['client_name'] ?? ''); ?>"
                    >
                </div>

                <div class="rbf-v3-form-group">
                    <label for="rbf-v3-client-email" class="rbf-v3-label required">
                        📧 Email
                    </label>
                    <input 
                        type="email" 
                        id="rbf-v3-client-email" 
                        name="client_email" 
                        class="rbf-v3-input" 
                        required
                        value="<?php echo esc_attr($form_data['client_email'] ?? ''); ?>"
                    >
                </div>

                <div class="rbf-v3-form-group">
                    <label for="rbf-v3-client-phone" class="rbf-v3-label required">
                        📞 Téléphone
                    </label>
                    <input 
                        type="tel" 
                        id="rbf-v3-client-phone" 
                        name="client_phone" 
                        class="rbf-v3-input" 
                        required
                        value="<?php echo esc_attr($form_data['client_phone'] ?? ''); ?>"
                    >
                </div>
            </div>

            <div class="rbf-v3-form-group rbf-v3-form-full">
                <label for="rbf-v3-client-message" class="rbf-v3-label">
                    💬 Questions / Commentaires
                </label>
                <textarea 
                    id="rbf-v3-client-message" 
                    name="client_message" 
                    class="rbf-v3-textarea" 
                    rows="4"
                    placeholder="<?php echo esc_attr($this->options['comment_section_text'] ?? '1 question, 1 souhait, n\'hésitez pas de nous en faire part...'); ?>"
                ><?php echo esc_textarea($form_data['client_message'] ?? ''); ?></textarea>
            </div>

            <!-- Récapitulatif -->
            <div class="rbf-v3-recap-card">
                <h3>📋 Récapitulatif de votre demande</h3>
                <div class="rbf-v3-recap-content">
                    <div class="rbf-v3-recap-line">
                        <span>Service :</span>
                        <strong><?php echo ucfirst($service_type); ?></strong>
                    </div>
                    <div class="rbf-v3-recap-line">
                        <span>Date :</span>
                        <strong><?php echo esc_html($form_data['event_date'] ?? 'Non définie'); ?></strong>
                    </div>
                    <div class="rbf-v3-recap-line">
                        <span>Convives :</span>
                        <strong><?php echo intval($form_data['guest_count'] ?? 0); ?> personnes</strong>
                    </div>
                    <div class="rbf-v3-recap-line">
                        <span>Durée :</span>
                        <strong><?php echo intval($form_data['event_duration'] ?? 2); ?>H</strong>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Calculer le prix du devis
     */
    private function calculate_quote_price($service_type, $form_data)
    {
        $base_price = 0;
        $supplements_array = [];
        $products_array = [];

        // Prix de base selon le service
        if ($service_type === 'restaurant') {
            $base_price = 200; // Prix de base restaurant
        } else {
            $base_price = 300; // Prix de base remorque
        }

        // Suppléments durée
        $duration = intval($form_data['event_duration'] ?? 2);
        $min_duration = $this->options[$service_type . '_min_duration'];
        if ($duration > $min_duration) {
            $extra_hours = $duration - $min_duration;
            $hour_price = $this->options[$service_type . '_extra_hour_price'];
            $duration_supplement = $extra_hours * $hour_price;
            
            $supplements_array[] = [
                'name' => "Supplément {$hour_price}€×{$extra_hours} durée",
                'price' => $duration_supplement
            ];
        }

        // Suppléments convives (remorque)
        if ($service_type === 'remorque') {
            $guests = intval($form_data['guest_count'] ?? 20);
            if ($guests > $this->options['remorque_staff_threshold']) {
                $staff_supplement = $this->options['remorque_staff_supplement'];
                $supplements_array[] = [
                    'name' => 'Supplément équipe (+50 personnes)',
                    'price' => $staff_supplement
                ];
            }
        }

        // Prix des produits (calculer selon les sélections)
        $products_array = $this->calculate_products_detailed($form_data);

        // Calculer les totaux
        $supplements_total = array_sum(array_column($supplements_array, 'price'));
        $products_total = array_sum(array_column($products_array, 'total'));
        $total = $base_price + $supplements_total + $products_total;

        return [
            'base_price' => $base_price,
            'supplements' => $supplements_array,
            'products' => $products_array,
            'duration_supplement' => $supplements_total,
            'extra_hours' => $duration > $min_duration ? ($duration - $min_duration) : 0,
            'duration_rate' => $this->options[$service_type . '_extra_hour_price'],
            'total' => $total
        ];
    }

    /**
     * Calculer le prix des produits avec détails
     */
    private function calculate_products_detailed($form_data)
    {
        $products = [];
        
        // Calculer les plats signature
        foreach ($form_data as $key => $value) {
            if (strpos($key, 'signature_') === 0 && strpos($key, '_qty') !== false) {
                $quantity = intval($value);
                if ($quantity > 0) {
                    $product_id = str_replace(['signature_', '_qty'], '', $key);
                    $product_name = $this->get_product_name($product_id, 'signature');
                    $product_price = $this->get_product_price($product_id, 'signature');
                    
                    $products[] = [
                        'name' => $product_name,
                        'quantity' => $quantity,
                        'price' => $product_price,
                        'total' => $quantity * $product_price
                    ];
                }
            }
        }
        
        // Calculer les accompagnements
        foreach ($form_data as $key => $value) {
            if (strpos($key, 'accompaniment_') === 0 && strpos($key, '_qty') !== false) {
                $quantity = intval($value);
                if ($quantity > 0) {
                    $product_id = str_replace(['accompaniment_', '_qty'], '', $key);
                    $product_name = $this->get_product_name($product_id, 'accompaniment');
                    $product_price = $this->get_product_price($product_id, 'accompaniment');
                    
                    $products[] = [
                        'name' => $product_name,
                        'quantity' => $quantity,
                        'price' => $product_price,
                        'total' => $quantity * $product_price
                    ];
                }
            }
        }
        
        // Calculer les options frites (chimichurri et sauces)
        if (isset($form_data['frites_chimichurri_qty']) && intval($form_data['frites_chimichurri_qty']) > 0) {
            $quantity = intval($form_data['frites_chimichurri_qty']);
            $products[] = [
                'name' => 'Chimichurri',
                'quantity' => $quantity,
                'price' => 1,
                'total' => $quantity * 1
            ];
        }
        
        return $products;
    }

    /**
     * Calculer le prix des produits (version simple)
     */
    private function calculate_products_price($form_data)
    {
        $products = $this->calculate_products_detailed($form_data);
        return array_sum(array_column($products, 'total'));
    }

    /**
     * Obtenir le nom d'un produit
     */
    private function get_product_name($product_id, $category)
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'restaurant_products';
        $product = $wpdb->get_row($wpdb->prepare(
            "SELECT name FROM {$table_name} WHERE id = %d AND category = %s",
            $product_id, $category
        ));
        
        if ($product) {
            return $product->name;
        }
        
        // Fallback pour les produits de démonstration
        if ($category === 'signature') {
            return $product_id == 1 ? 'Hot-Dog Classic' : 'Hot-Dog Spicy';
        } elseif ($category === 'accompaniment') {
            return $product_id == 1 ? 'Salade' : 'Frites';
        }
        
        return 'Produit #' . $product_id;
    }

    /**
     * Obtenir le prix d'un produit
     */
    private function get_product_price($product_id, $category)
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'restaurant_products';
        $product = $wpdb->get_row($wpdb->prepare(
            "SELECT price FROM {$table_name} WHERE id = %d AND category = %s",
            $product_id, $category
        ));
        
        if ($product) {
            return floatval($product->price);
        }
        
        // Fallback pour les produits de démonstration
        if ($category === 'signature') {
            return $product_id == 1 ? 12 : 14;
        } elseif ($category === 'accompaniment') {
            return 4;
        }
        
        return 0;
    }

    /**
     * Créer un devis
     */
    private function create_quote($service_type, $form_data, $price_data)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'restaurant_quotes';

        $quote_data = [
            'service_type' => $service_type,
            'client_firstname' => sanitize_text_field($form_data['client_firstname']),
            'client_name' => sanitize_text_field($form_data['client_name']),
            'client_email' => sanitize_email($form_data['client_email']),
            'client_phone' => sanitize_text_field($form_data['client_phone']),
            'client_message' => sanitize_textarea_field($form_data['client_message']),
            'event_date' => sanitize_text_field($form_data['event_date']),
            'guest_count' => intval($form_data['guest_count']),
            'event_duration' => intval($form_data['event_duration']),
            'total_price' => floatval($price_data['total']),
            'form_data' => json_encode($form_data),
            'price_breakdown' => json_encode($price_data),
            'status' => 'draft',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        $result = $wpdb->insert($table_name, $quote_data);

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Envoyer l'email du devis
     */
    private function send_quote_email($quote_id)
    {
        // Utiliser la classe Email existante si disponible
        if (class_exists('RestaurantBooking_Email')) {
            $email_handler = new RestaurantBooking_Email();
            return $email_handler->send_quote_email($quote_id);
        }

        return true; // Fallback
    }

    /**
     * Obtenir le HTML des accompagnements avec sélecteurs de quantité
     */
    private function get_accompaniments_html($guest_count)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'restaurant_products';
        $accompaniments = $wpdb->get_results(
            "SELECT * FROM {$table_name} WHERE category = 'accompaniments' AND active = 1 ORDER BY name ASC"
        );

        $html = '';
        foreach ($accompaniments as $product) {
            $min_quantity = ($product->name === 'Frites' || $product->name === 'Salade') ? $guest_count : 0;
            
            $html .= '<div class="rbf-v3-accompaniment-item">';
            $html .= '<div class="rbf-v3-accompaniment-header">';
            $html .= '<label class="rbf-v3-accompaniment-label">';
            $html .= '<input type="checkbox" name="accompaniment_' . $product->id . '_enabled" value="1" class="rbf-v3-accompaniment-checkbox">';
            $html .= '<span class="rbf-v3-accompaniment-name">' . esc_html($product->name) . '</span>';
            $html .= '<span class="rbf-v3-accompaniment-price">' . number_format($product->price, 0) . ' €</span>';
            $html .= '</label>';
            $html .= '</div>';
            
            $html .= '<div class="rbf-v3-quantity-selector">';
            $html .= '<button type="button" class="rbf-v3-qty-btn rbf-v3-qty-minus" data-target="accompaniment_' . $product->id . '_qty">-</button>';
            $html .= '<input type="number" name="accompaniment_' . $product->id . '_qty" value="' . $min_quantity . '" min="0" max="999" class="rbf-v3-qty-input" readonly>';
            $html .= '<button type="button" class="rbf-v3-qty-btn rbf-v3-qty-plus" data-target="accompaniment_' . $product->id . '_qty">+</button>';
            $html .= '</div>';
            
            // Options spéciales pour les frites
            if (strtolower($product->name) === 'frites') {
                $html .= '<div class="rbf-v3-frites-options" style="display: none;">';
                
                // Option chimichurri
                $html .= '<div class="rbf-v3-option-item">';
                $html .= '<label class="rbf-v3-option-label">';
                $html .= '<input type="checkbox" name="frites_chimichurri_enabled" value="1">';
                $html .= '<span>Enrobée sauce chimichurri +1€</span>';
                $html .= '</label>';
                $html .= '<div class="rbf-v3-quantity-selector">';
                $html .= '<button type="button" class="rbf-v3-qty-btn rbf-v3-qty-minus" data-target="frites_chimichurri_qty">-</button>';
                $html .= '<input type="number" name="frites_chimichurri_qty" value="0" min="0" class="rbf-v3-qty-input" readonly>';
                $html .= '<button type="button" class="rbf-v3-qty-btn rbf-v3-qty-plus" data-target="frites_chimichurri_qty">+</button>';
                $html .= '</div>';
                $html .= '</div>';
                
                // Choix des sauces
                $sauces = ['Ketchup', 'Mayo', 'Barbecue', 'Curry'];
                foreach ($sauces as $sauce) {
                    $sauce_key = strtolower(str_replace(' ', '_', $sauce));
                    $html .= '<div class="rbf-v3-option-item">';
                    $html .= '<label class="rbf-v3-option-label">';
                    $html .= '<input type="checkbox" name="frites_sauce_' . $sauce_key . '_enabled" value="1">';
                    $html .= '<span>Sauce ' . $sauce . '</span>';
                    $html .= '</label>';
                    $html .= '<div class="rbf-v3-quantity-selector">';
                    $html .= '<button type="button" class="rbf-v3-qty-btn rbf-v3-qty-minus" data-target="frites_sauce_' . $sauce_key . '_qty">-</button>';
                    $html .= '<input type="number" name="frites_sauce_' . $sauce_key . '_qty" value="0" min="0" class="rbf-v3-qty-input" readonly>';
                    $html .= '<button type="button" class="rbf-v3-qty-btn rbf-v3-qty-plus" data-target="frites_sauce_' . $sauce_key . '_qty">+</button>';
                    $html .= '</div>';
                    $html .= '</div>';
                }
                
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Obtenir le HTML des produits Mini Boss
     */
    private function get_mini_boss_products_html()
    {
        global $wpdb;

        $category_table = $wpdb->prefix . 'restaurant_categories';
        $products_table = $wpdb->prefix . 'restaurant_products';
        
        // Rechercher la catégorie Mini Boss
        $category = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$category_table} WHERE type = %s AND is_active = 1",
            'mini_boss'
        ));
        
        if (!$category) {
            return '<p class="rbf-v3-message info">Aucun menu Mini Boss disponible pour le moment.</p>';
        }
        
        // Récupérer les produits de cette catégorie
        $products = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$products_table} WHERE category_id = %d AND is_active = 1 ORDER BY display_order ASC, name ASC",
            $category->id
        ));

        // Si pas de produits en base, ne rien afficher
        if (empty($products)) {
            return '<p class="rbf-v3-message info">Aucun menu Mini Boss disponible pour le moment.</p>';
        }

        $html = '<div class="rbf-v3-mini-boss-grid">';
        foreach ($products as $product) {
            $image_url = $product->image_url ? esc_url($product->image_url) : '';
            
            $html .= '<div class="rbf-v3-product-card-full">';
            if ($image_url) {
                $html .= '<div class="rbf-v3-product-image">';
                $html .= '<img src="' . $image_url . '" alt="' . esc_attr($product->name) . '" loading="lazy">';
                $html .= '</div>';
            }
            $html .= '<div class="rbf-v3-product-info">';
            $html .= '<h4 class="rbf-v3-product-title">' . esc_html($product->name) . '</h4>';
            if ($product->description) {
                $html .= '<p class="rbf-v3-product-description">' . esc_html($product->description) . '</p>';
            }
            $html .= '<div class="rbf-v3-product-price-qty">';
            $html .= '<span class="rbf-v3-product-price">' . number_format($product->price, 0) . ' €</span>';
            $html .= '<div class="rbf-v3-quantity-selector">';
            $html .= '<button type="button" class="rbf-v3-qty-btn rbf-v3-qty-minus" data-target="mini_boss_' . $product->id . '_qty">-</button>';
            $html .= '<input type="number" name="mini_boss_' . $product->id . '_qty" value="0" min="0" max="999" class="rbf-v3-qty-input" readonly>';
            $html .= '<button type="button" class="rbf-v3-qty-btn rbf-v3-qty-plus" data-target="mini_boss_' . $product->id . '_qty">+</button>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Obtenir le HTML des accompagnements simplifié
     */
    private function get_accompaniments_simple_html($guest_count)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'restaurant_products';
        $accompaniments = $wpdb->get_results(
            "SELECT * FROM {$table_name} WHERE category = 'accompaniments' AND active = 1 ORDER BY name ASC"
        );

        if (empty($accompaniments)) {
            // Fallback si pas de produits en base
            $accompaniments = [
                (object) ['id' => 1, 'name' => 'Salade', 'price' => 4, 'description' => ''],
                (object) ['id' => 2, 'name' => 'Frites', 'price' => 4, 'description' => '']
            ];
        }

        $html = '<div class="rbf-v3-accompaniments-grid">';
        foreach ($accompaniments as $product) {
            $html .= '<div class="rbf-v3-accompaniment-card">';
            $html .= '<div class="rbf-v3-accompaniment-header">';
            $html .= '<h4>' . esc_html($product->name) . '</h4>';
            $html .= '<span class="rbf-v3-price">' . number_format($product->price, 0) . ' €</span>';
            $html .= '</div>';
            
            $html .= '<div class="rbf-v3-quantity-selector">';
            $html .= '<button type="button" class="rbf-v3-qty-btn rbf-v3-qty-minus" data-target="accompaniment_' . $product->id . '_qty">-</button>';
            $html .= '<input type="number" name="accompaniment_' . $product->id . '_qty" value="0" min="0" max="999" class="rbf-v3-qty-input" readonly>';
            $html .= '<button type="button" class="rbf-v3-qty-btn rbf-v3-qty-plus" data-target="accompaniment_' . $product->id . '_qty">+</button>';
            $html .= '</div>';
            
            // Options spéciales pour les frites
            if (strtolower($product->name) === 'frites') {
                $html .= '<div class="rbf-v3-frites-options" style="margin-top: 15px; display: none;">';
                
                // Checkbox pour enrobée chimichurri
                $html .= '<div class="rbf-v3-option-row">';
                $html .= '<label class="rbf-v3-checkbox-option">';
                $html .= '<input type="checkbox" name="frites_chimichurri_enabled" value="1" class="rbf-v3-option-checkbox">';
                $html .= '<span class="rbf-v3-checkbox-text">Enrobée sauce chimichurri +1€</span>';
                $html .= '</label>';
                $html .= '<div class="rbf-v3-quantity-selector rbf-v3-qty-small" style="display: none;">';
                $html .= '<button type="button" class="rbf-v3-qty-btn rbf-v3-qty-minus" data-target="frites_chimichurri_qty">-</button>';
                $html .= '<input type="number" name="frites_chimichurri_qty" value="0" min="0" class="rbf-v3-qty-input" readonly>';
                $html .= '<button type="button" class="rbf-v3-qty-btn rbf-v3-qty-plus" data-target="frites_chimichurri_qty">+</button>';
                $html .= '</div>';
                $html .= '</div>';
                
                // Section choix de sauce
                $html .= '<div class="rbf-v3-sauce-section">';
                $html .= '<h5>Choix de la sauce :</h5>';
                
                $sauces = ['Ketchup', 'Mayo', 'Barbecue', 'Curry'];
                foreach ($sauces as $sauce) {
                    $sauce_key = strtolower($sauce);
                    $html .= '<div class="rbf-v3-option-row">';
                    $html .= '<label class="rbf-v3-checkbox-option">';
                    $html .= '<input type="checkbox" name="sauce_' . $sauce_key . '_enabled" value="1" class="rbf-v3-sauce-checkbox">';
                    $html .= '<span class="rbf-v3-checkbox-text">Sauce ' . $sauce . '</span>';
                    $html .= '</label>';
                    $html .= '<div class="rbf-v3-quantity-selector rbf-v3-qty-small" style="display: none;">';
                    $html .= '<button type="button" class="rbf-v3-qty-btn rbf-v3-qty-minus" data-target="sauce_' . $sauce_key . '_qty">-</button>';
                    $html .= '<input type="number" name="sauce_' . $sauce_key . '_qty" value="0" min="0" class="rbf-v3-qty-input" readonly>';
                    $html .= '<button type="button" class="rbf-v3-qty-btn rbf-v3-qty-plus" data-target="sauce_' . $sauce_key . '_qty">+</button>';
                    $html .= '</div>';
                    $html .= '</div>';
                }
                
                $html .= '</div>'; // fin sauce-section
                $html .= '</div>'; // fin frites-options
            }
            
            $html .= '</div>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Obtenir le HTML des produits signature
     */
    private function get_signature_products_html($signature_type, $guest_count)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'restaurant_products';
        $category = strtolower($signature_type); // 'dog' ou 'croq'
        
        $products = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE category = %s AND active = 1 ORDER BY name ASC",
            $category
        ));

        // Fallback si pas de produits en base
        if (empty($products)) {
            $products = [
                (object) [
                    'id' => ($category === 'dog') ? 1 : 3,
                    'name' => ($category === 'dog') ? 'Hot-Dog Classic' : 'Croque-Monsieur Classic',
                    'price' => 12,
                    'description' => 'Notre ' . ($category === 'dog' ? 'hot-dog' : 'croque-monsieur') . ' signature',
                    'image_url' => ''
                ],
                (object) [
                    'id' => ($category === 'dog') ? 2 : 4,
                    'name' => ($category === 'dog') ? 'Hot-Dog Spicy' : 'Croque-Monsieur Deluxe',
                    'price' => 14,
                    'description' => 'Version épicée de notre ' . ($category === 'dog' ? 'hot-dog' : 'croque-monsieur'),
                    'image_url' => ''
                ]
            ];
        }

        $html = '<div class="rbf-v3-signature-products-grid">';
        foreach ($products as $product) {
            $image_url = $product->image_url ? esc_url($product->image_url) : '';
            
            $html .= '<div class="rbf-v3-product-card-full">';
            if ($image_url) {
                $html .= '<div class="rbf-v3-product-image">';
                $html .= '<img src="' . $image_url . '" alt="' . esc_attr($product->name) . '" loading="lazy">';
                $html .= '</div>';
            }
            $html .= '<div class="rbf-v3-product-info">';
            $html .= '<h4 class="rbf-v3-product-title">' . esc_html($product->name) . '</h4>';
            if ($product->description) {
                $html .= '<p class="rbf-v3-product-description">' . esc_html($product->description) . '</p>';
            }
            $html .= '<div class="rbf-v3-product-price-qty">';
            $html .= '<span class="rbf-v3-product-price">' . number_format($product->price, 0) . ' €</span>';
            $html .= '<div class="rbf-v3-quantity-selector">';
            $html .= '<button type="button" class="rbf-v3-qty-btn rbf-v3-qty-minus" data-target="signature_' . $product->id . '_qty">-</button>';
            $html .= '<input type="number" name="signature_' . $product->id . '_qty" value="0" min="0" max="999" class="rbf-v3-qty-input" readonly>';
            $html .= '<button type="button" class="rbf-v3-qty-btn rbf-v3-qty-plus" data-target="signature_' . $product->id . '_qty">+</button>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Nettoyer les données du formulaire
     */
    private function sanitize_form_data($data)
    {
        if (!is_array($data)) {
            return [];
        }

        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitize_form_data($value);
            } else {
                $sanitized[$key] = sanitize_text_field($value);
            }
        }

        return $sanitized;
    }

    /**
     * Options par défaut
     */
    private function get_default_options()
    {
        return [
            'restaurant_min_guests' => 10,
            'restaurant_max_guests' => 30,
            'restaurant_min_duration' => 2,
            'restaurant_extra_hour_price' => 50,
            'restaurant_guests_text' => 'De 10 à 30 personnes',
            'restaurant_duration_text' => 'min durée = 2H (compris) max durée = 4H (supplément de +50 €/TTC/H)',
            'restaurant_forfait_description' => 'Mise à disposition des murs de Block|Notre équipe salle + cuisine assurant la prestation|Présentation + mise en place buffets, selon vos choix|Mise à disposition vaisselle + verrerie|Entretien + nettoyage',
            'remorque_min_guests' => 20,
            'remorque_max_guests' => 100,
            'remorque_min_duration' => 2,
            'remorque_extra_hour_price' => 50,
            'remorque_staff_threshold' => 50,
            'remorque_staff_supplement' => 150,
            'max_distance_km' => 150,
            'signature_dish_text' => 'minimum 1 plat par personne',
            'accompaniment_text' => 'mini 1/personne',
            'comment_section_text' => '1 question, 1 souhait, n\'hésitez pas de nous en faire part...'
        ];
    }

    /**
     * Étape 7: Récapitulatif final (remorque uniquement)
     */
    private function generate_step_7_html($service_type, $form_data)
    {
        // L'étape 7 n'existe que pour la remorque (Contact)
        if ($service_type === 'remorque') {
            return $this->generate_step_7_contact_remorque_html($form_data);
        } else {
            // Pour le restaurant, il n'y a pas d'étape 7
            throw new Exception('Étape 7 non supportée pour le service restaurant');
        }
    }
    
    /**
     * Étape 7: Contact pour remorque
     */
    private function generate_step_7_contact_remorque_html($form_data)
    {
        ob_start();
        ?>
        <div class="rbf-v3-step-content active" data-step="7">
            <h2 class="rbf-v3-step-title">Vos coordonnées</h2>
            
            <div class="rbf-v3-form-grid">
                <div class="rbf-v3-form-group">
                    <label for="rbf-v3-client-firstname" class="rbf-v3-label required">
                        👤 Prénom
                    </label>
                    <input 
                        type="text" 
                        id="rbf-v3-client-firstname" 
                        name="client_firstname" 
                        class="rbf-v3-input" 
                        required
                        value="<?php echo esc_attr($form_data['client_firstname'] ?? ''); ?>"
                    >
                </div>

                <div class="rbf-v3-form-group">
                    <label for="rbf-v3-client-name" class="rbf-v3-label required">
                        👤 Nom
                    </label>
                    <input 
                        type="text" 
                        id="rbf-v3-client-name" 
                        name="client_name" 
                        class="rbf-v3-input" 
                        required
                        value="<?php echo esc_attr($form_data['client_name'] ?? ''); ?>"
                    >
                </div>

                <div class="rbf-v3-form-group">
                    <label for="rbf-v3-client-email" class="rbf-v3-label required">
                        📧 Email
                    </label>
                    <input 
                        type="email" 
                        id="rbf-v3-client-email" 
                        name="client_email" 
                        class="rbf-v3-input" 
                        required
                        value="<?php echo esc_attr($form_data['client_email'] ?? ''); ?>"
                    >
                </div>

                <div class="rbf-v3-form-group">
                    <label for="rbf-v3-client-phone" class="rbf-v3-label required">
                        📞 Téléphone
                    </label>
                    <input 
                        type="tel" 
                        id="rbf-v3-client-phone" 
                        name="client_phone" 
                        class="rbf-v3-input" 
                        required
                        value="<?php echo esc_attr($form_data['client_phone'] ?? ''); ?>"
                    >
                </div>
            </div>

            <div class="rbf-v3-form-group rbf-v3-form-full">
                <label for="rbf-v3-client-message" class="rbf-v3-label">
                    💬 Questions / Commentaires
                </label>
                <textarea 
                    id="rbf-v3-client-message" 
                    name="client_message" 
                    class="rbf-v3-textarea" 
                    rows="4"
                    placeholder="<?php echo esc_attr($this->options['comment_section_text'] ?? '1 question, 1 souhait, n\'hésitez pas de nous en faire part...'); ?>"
                ><?php echo esc_textarea($form_data['client_message'] ?? ''); ?></textarea>
            </div>

            <!-- Récapitulatif -->
            <div class="rbf-v3-recap-card">
                <h3>📋 Récapitulatif de votre demande</h3>
                <div class="rbf-v3-recap-content">
                    <div class="rbf-v3-recap-line">
                        <span>Service :</span>
                        <strong>Remorque</strong>
                    </div>
                    <div class="rbf-v3-recap-line">
                        <span>Date :</span>
                        <strong><?php echo esc_html($form_data['event_date'] ?? 'Non définie'); ?></strong>
                    </div>
                    <div class="rbf-v3-recap-line">
                        <span>Convives :</span>
                        <strong><?php echo intval($form_data['guest_count'] ?? 0); ?> personnes</strong>
                    </div>
                    <div class="rbf-v3-recap-line">
                        <span>Durée :</span>
                        <strong><?php echo intval($form_data['event_duration'] ?? 2); ?>H</strong>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtenir les produits par catégorie
     */
    private function get_products_by_category($category)
    {
        global $wpdb;

        // D'abord, récupérer l'ID de la catégorie depuis la table des catégories
        $categories_table = $wpdb->prefix . 'restaurant_categories';
        $category_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$categories_table} WHERE type = %s",
            $category
        ));

        if (!$category_id) {
            // Si la catégorie n'existe pas, retourner les produits de fallback
            return $this->get_fallback_buffet_products($category);
        }

        $table_name = $wpdb->prefix . 'restaurant_products';
        
        $products = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE category_id = %d AND is_active = 1 ORDER BY name ASC",
            $category_id
        ));

        // Fallback si pas de produits en base
        if (empty($products)) {
            return $this->get_fallback_buffet_products($category);
        }

        return $products;
    }

    /**
     * Obtenir les produits de fallback pour les buffets
     */
    private function get_fallback_buffet_products($category)
    {
        if ($category === 'buffet_sale') {
            return [
                (object) [
                    'id' => 10,
                    'name' => 'Grilled Cheese',
                    'price' => 10,
                    'description' => 'Sandwich au fromage grillé',
                    'servings_per_person' => '20 pers',
                    'image_url' => ''
                ],
                (object) [
                    'id' => 11,
                    'name' => 'Salade César',
                    'price' => 8,
                    'description' => 'Salade fraîche avec croûtons et parmesan',
                    'servings_per_person' => '15 pers',
                    'image_url' => ''
                ],
                (object) [
                    'id' => 12,
                    'name' => 'Wrap Poulet',
                    'price' => 12,
                    'description' => 'Wrap au poulet et légumes frais',
                    'servings_per_person' => '12 pers',
                    'image_url' => ''
                ]
            ];
        } elseif ($category === 'buffet_sucre') {
            return [
                (object) [
                    'id' => 20,
                    'name' => 'Tiramisu',
                    'price' => 6,
                    'description' => 'Dessert italien au café et mascarpone',
                    'servings_per_person' => '8 pers',
                    'image_url' => ''
                ],
                (object) [
                    'id' => 21,
                    'name' => 'Tarte aux Fruits',
                    'price' => 5,
                    'description' => 'Tarte aux fruits de saison',
                    'servings_per_person' => '10 pers',
                    'image_url' => ''
                ]
            ];
        }

        return [];
    }
}

// Initialiser le gestionnaire AJAX
new RestaurantBooking_Ajax_Handler_V3();
