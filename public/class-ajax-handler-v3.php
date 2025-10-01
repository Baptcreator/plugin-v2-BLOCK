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
        
        // Nouveau: Calendrier avec créneaux horaires
        add_action('wp_ajax_rbf_v3_get_month_availability', [$this, 'get_month_availability']);
        add_action('wp_ajax_nopriv_rbf_v3_get_month_availability', [$this, 'get_month_availability']);
        
        // Widget calendrier avec disponibilités
        add_action('wp_ajax_rbf_v3_get_availability', [$this, 'get_availability']);
        add_action('wp_ajax_nopriv_rbf_v3_get_availability', [$this, 'get_availability']);

        // Calcul de distance Google Maps
        add_action('wp_ajax_rbf_v3_calculate_distance', [$this, 'calculate_distance']);
        add_action('wp_ajax_nopriv_rbf_v3_calculate_distance', [$this, 'calculate_distance']);

        // Charger les options
        $this->load_options();
    }

    /**
     * Charger les options
     */
    private function load_options()
    {
        // Vérifier si la classe existe ET si elle est correctement initialisée
        if (class_exists('RestaurantBooking_Options_Unified_Admin')) {
            try {
                $options_admin = new RestaurantBooking_Options_Unified_Admin();
                $this->options = $options_admin->get_options();
                
                // Vérifier que les options sont bien chargées
                if (empty($this->options) || !is_array($this->options)) {
                    throw new Exception('Options vides ou invalides');
                }
            } catch (Exception $e) {
                error_log('Restaurant Booking V3: Erreur chargement options - ' . $e->getMessage());
                $this->options = $this->get_default_options();
            }
        } else {
            error_log('Restaurant Booking V3: Classe RestaurantBooking_Options_Unified_Admin non trouvée');
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
                // Log pour debug - vérifier l'ID avant envoi
                if (class_exists('RestaurantBooking_Logger')) {
                    RestaurantBooking_Logger::info("Devis créé avec succès, ID: {$quote_id}");
                }
                
                // Envoyer l'email au client avec l'ID correct - utiliser la classe Email unifiée
                if (class_exists('RestaurantBooking_Email')) {
                    $email_result = RestaurantBooking_Email::send_quote_email($quote_id);
                } else {
                    // Fallback vers la méthode locale si la classe n'existe pas
                    $email_result = $this->send_quote_email($quote_id);
                }
                
                if (class_exists('RestaurantBooking_Logger')) {
                    RestaurantBooking_Logger::info("Résultat envoi email client", [
                        'quote_id' => $quote_id,
                        'success' => $email_result
                    ]);
                }
                
                // Envoyer la notification admin avec gestion d'erreur
                if (class_exists('RestaurantBooking_Email')) {
                    $admin_result = RestaurantBooking_Email::send_admin_notification($quote_id);
                    
                    if (class_exists('RestaurantBooking_Logger')) {
                        RestaurantBooking_Logger::info("Résultat notification admin", [
                            'quote_id' => $quote_id,
                            'success' => $admin_result
                        ]);
                    }
                } else {
                    if (class_exists('RestaurantBooking_Logger')) {
                        RestaurantBooking_Logger::error("Classe RestaurantBooking_Email non disponible pour notification admin", [
                            'quote_id' => $quote_id
                        ]);
                    }
                }
                
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
            // Récupérer les données du formulaire pour la restauration des quantités
            $form_data = $this->sanitize_form_data($_POST['form_data'] ?? []);
            $html = $this->get_signature_products_html($signature_type, $guest_count, $form_data);
            wp_send_json_success(['html' => $html]);
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Erreur lors du chargement des produits']);
        }
    }

    /**
     * Générer le HTML des produits signature pour pré-chargement
     */
    private function load_signature_products_html($signature_type, $form_data)
    {
        $guest_count = intval($form_data['guest_count'] ?? 10);
        return $this->get_signature_products_html($signature_type, $guest_count, $form_data);
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
                    
                    <?php 
                    // Nouveau calendrier toujours activé
                    $use_new_calendar = true;
                    
                    if ($use_new_calendar) : ?>
                        <!-- Champ de sélection de date avec calendrier -->
                        <div class="rbf-v3-date-selector">
                            <input 
                                type="text" 
                                id="rbf-v3-event-date" 
                                name="event_date" 
                                class="rbf-v3-input rbf-v3-date-input" 
                                placeholder="Cliquez pour choisir une date"
                                readonly
                                required 
                                value="<?php echo esc_attr($form_data['event_date'] ?? ''); ?>"
                            >
                            <button type="button" class="rbf-v3-calendar-btn" onclick="openCalendarModal()">
                                📅
                            </button>
                        </div>
                        
                        <!-- Modal du calendrier -->
                        <div id="rbf-v3-calendar-modal" class="rbf-v3-modal" style="display: none;">
                            <div class="rbf-v3-modal-content">
                                <div class="rbf-v3-modal-header">
                                    <h3>Choisir une date</h3>
                                    <button type="button" class="rbf-v3-modal-close" onclick="closeCalendarModal()">×</button>
                                </div>
                                <div class="rbf-v3-calendar-container" 
                                     data-rbf-calendar 
                                     data-rbf-calendar-options='{"serviceType": "<?php echo esc_attr($service_type); ?>", "selectedDate": "<?php echo esc_attr($form_data['event_date'] ?? ''); ?>"}'>
                                </div>
                                <div class="rbf-v3-modal-footer">
                                    <button type="button" class="rbf-v3-btn rbf-v3-btn-secondary" onclick="closeCalendarModal()">Annuler</button>
                                    <button type="button" class="rbf-v3-btn rbf-v3-btn-primary" onclick="confirmDateSelection()">Confirmer</button>
                                </div>
                            </div>
                        </div>
                        
                        <small class="rbf-v3-help-text">Sélectionnez une date disponible. Les créneaux bloqués sont affichés en détail.</small>
                    <?php else : ?>
                        <!-- Calendrier simple existant (par défaut) -->
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
                    <?php endif; ?>
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
                        <?php echo esc_html($this->options[$service_type . '_duration_text'] ?? ''); ?>
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
                        Rayon maximum <?php echo $this->options['max_distance_km']; ?> km<br>
                        Des suppléments peuvent s'appliquer en fonction de la distance de l'événement
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
                    <?php 
                    $selected_signature_type = $form_data['signature_type'] ?? '';
                    ?>
                    <label class="rbf-v3-radio-card">
                        <input type="radio" name="signature_type" value="DOG" required data-action="load-signature-products" <?php echo ($selected_signature_type === 'DOG') ? 'checked' : ''; ?>>
                        <div class="rbf-v3-radio-content">
                            <span class="rbf-v3-radio-title">🌭 DOG</span>
                            <span class="rbf-v3-radio-subtitle">Nos hot-dogs signature</span>
                        </div>
                    </label>
                    
                    <label class="rbf-v3-radio-card">
                        <input type="radio" name="signature_type" value="CROQ" required data-action="load-signature-products" <?php echo ($selected_signature_type === 'CROQ') ? 'checked' : ''; ?>>
                        <div class="rbf-v3-radio-content">
                            <span class="rbf-v3-radio-title">🥪 CROQ</span>
                            <span class="rbf-v3-radio-subtitle">Nos croque-monsieurs</span>
                        </div>
                    </label>
                </div>
                
                <div class="rbf-v3-signature-products" <?php echo !empty($selected_signature_type) ? '' : 'style="display: none;"'; ?>>
                    <?php 
                    // Pré-charger les produits si un type est déjà sélectionné
                    if (!empty($selected_signature_type)) {
                        echo $this->load_signature_products_html($selected_signature_type, $form_data);
                    } else {
                        echo '<!-- Les produits seront chargés dynamiquement selon le choix DOG/CROQ -->';
                    }
                    ?>
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
                
                <div class="rbf-v3-accompaniments-vertical">
                    <?php echo $this->get_accompaniments_with_options_html($guest_count, $form_data); ?>
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
                                                <span class="rbf-v3-product-servings"><?php echo esc_html(isset($product->servings_per_person) ? $product->servings_per_person : $product->unit_per_person ?? '1 pers'); ?></span>
                                                <span class="rbf-v3-product-price"><?php echo number_format($product->price, 0); ?> €</span>
                                            </div>
                                            <div class="rbf-v3-product-footer">
                                                <div class="rbf-v3-quantity-selector">
                                                    <button type="button" class="rbf-v3-qty-btn rbf-v3-qty-minus" data-target="buffet_sale_<?php echo $product->id; ?>_qty">-</button>
                                                    <?php $saved_qty = intval($form_data['buffet_sale_' . $product->id . '_qty'] ?? 0); ?>
                                                    <input type="number" name="buffet_sale_<?php echo $product->id; ?>_qty" value="<?php echo $saved_qty; ?>" min="0" max="999" class="rbf-v3-qty-input">
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
                                                <span class="rbf-v3-product-servings"><?php echo esc_html(isset($product->servings_per_person) ? $product->servings_per_person : $product->unit_per_person ?? '1 pers'); ?></span>
                                                <span class="rbf-v3-product-price"><?php echo number_format($product->price, 0); ?> €</span>
                                            </div>
                                            <div class="rbf-v3-product-footer">
                                                <div class="rbf-v3-quantity-selector">
                                                    <button type="button" class="rbf-v3-qty-btn rbf-v3-qty-minus" data-target="buffet_sucre_<?php echo $product->id; ?>_qty">-</button>
                                                    <?php $saved_qty = intval($form_data['buffet_sucre_' . $product->id . '_qty'] ?? 0); ?>
                                                    <input type="number" name="buffet_sucre_<?php echo $product->id; ?>_qty" value="<?php echo $saved_qty; ?>" min="0" max="999" class="rbf-v3-qty-input">
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
        $beer_beverages = $this->get_beverages_by_type('beers', $service_type);
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
                <?php 
                $available_tabs = $this->get_available_beverage_tabs($service_type);
                $first_tab = true;
                foreach ($available_tabs as $tab_key => $tab_data): 
                ?>
                    <button type="button" class="rbf-v3-tab-btn <?php echo $first_tab ? 'active' : ''; ?>" data-tab="<?php echo esc_attr($tab_key); ?>">
                        <?php echo esc_html($tab_data['label']); ?>
                    </button>
                    <?php $first_tab = false; ?>
                <?php endforeach; ?>
            </div>
            
            <!-- Contenu des onglets -->
            <div class="rbf-v3-drinks-content">
                <?php 
                $first_content = true;
                foreach ($available_tabs as $tab_key => $tab_data): 
                    $beverages = $this->get_beverages_by_type($tab_key, $service_type);
                ?>
                    <div class="rbf-v3-tab-content <?php echo $first_content ? 'active' : ''; ?>" data-tab="<?php echo esc_attr($tab_key); ?>">
                        <?php if (!empty($beverages)) : ?>
                            <?php echo $this->generate_beverage_tab_content($beverages, $tab_key, $tab_data); ?>
                        <?php else : ?>
                            <p class="rbf-v3-no-products">Aucune boisson disponible pour le moment dans cette catégorie.</p>
                        <?php endif; ?>
                    </div>
                    <?php $first_content = false; ?>
                <?php endforeach; ?>
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
        
        
        // CORRECTION : Mapping selon les IDs réels de la base de données
        $category_ids = array();
        switch ($beverage_type) {
            case 'soft':
                $category_ids = array(106); // ID 106 selon l'analyse
                break;
            case 'wines':
                $category_ids = array(112); // ID 112 confirmé pour les vins
                break;
            case 'beers':
                $category_ids = array(109); // ID 109 selon l'analyse
                break;
            case 'fut':
                $category_ids = array(110); // ID 110 selon l'analyse
                break;
        }
        
        if (empty($category_ids)) {
            return array();
        }
        
        $placeholders = implode(',', array_fill(0, count($category_ids), '%d'));
        $params = $category_ids;
        
        // Ajouter le filtre de service si nécessaire
        if ($service_type !== 'both') {
            $params[] = $service_type;
        }
        
        $sql = "SELECT p.*, c.name as category_name, c.type as category_type,
                       p.suggested_beverage as is_featured
                FROM {$wpdb->prefix}restaurant_products p
                INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
                WHERE c.id IN ($placeholders)
                AND p.is_active = 1 AND c.is_active = 1";
        
        if ($service_type !== 'both') {
            $sql .= " AND (c.service_type = %s OR c.service_type = 'both')";
        }
        
        $sql .= " ORDER BY p.suggested_beverage DESC, p.display_order ASC, p.name ASC";
        
        $beverages = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
        
        // Nettoyer les échappements dans les résultats
        $beverages = RestaurantBooking_Text_Cleaner::clean_array($beverages);
        
        // Traiter les résultats
        foreach ($beverages as &$beverage) {
            $beverage['price'] = (float) $beverage['price'];
            // CORRECTION : Pour les vins et bières, is_featured vient maintenant de suggested_beverage
            $beverage['is_featured'] = (bool) $beverage['is_featured'];
            $beverage['alcohol_degree'] = $beverage['alcohol_degree'] ? (float) $beverage['alcohol_degree'] : null;
            $beverage['volume_cl'] = $beverage['volume_cl'] ? (int) $beverage['volume_cl'] : null;
            
            // Ajouter l'URL de l'image
            $beverage['image_url'] = $beverage['image_id'] ? wp_get_attachment_image_url($beverage['image_id'], 'medium') : '';
            
            // Pour les fûts, récupérer les tailles depuis la table dédiée
            if ($beverage_type === 'fut') {
                $keg_sizes = $wpdb->get_results($wpdb->prepare(
                    "SELECT liters, price FROM {$wpdb->prefix}restaurant_keg_sizes 
                     WHERE product_id = %d AND is_active = 1 
                     ORDER BY liters ASC",
                    $beverage['id']
                ), ARRAY_A);
                
                $beverage['keg_sizes'] = $keg_sizes;
                
                // Pour compatibilité avec l'ancien code, ajouter les prix spécifiques
                foreach ($keg_sizes as $size) {
                    if ($size['liters'] == 10) {
                        $beverage['keg_size_10l_price'] = (float) $size['price'];
                    }
                    if ($size['liters'] == 20) {
                        $beverage['keg_size_20l_price'] = (float) $size['price'];
                    }
                }
                
                // Valeurs par défaut si les tailles n'existent pas
                if (!isset($beverage['keg_size_10l_price'])) {
                    $beverage['keg_size_10l_price'] = null;
                }
                if (!isset($beverage['keg_size_20l_price'])) {
                    $beverage['keg_size_20l_price'] = null;
                }
            }
            
            // CORRECTION : Pour toutes les boissons avec tailles multiples, récupérer les tailles
            if ($beverage['has_multiple_sizes']) {
                $beverage['sizes'] = $this->get_beverage_sizes($beverage['id']);
                
                // CORRECTION : Pour les boissons multi-tailles, vérifier si une des tailles est mise en avant
                if (!$beverage['is_featured'] && !empty($beverage['sizes'])) {
                    foreach ($beverage['sizes'] as $size) {
                        if (!empty($size['is_featured'])) {
                            $beverage['is_featured'] = true;
                            break;
                        }
                    }
                }
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
            $size['is_featured'] = (bool) $size['is_featured'];
        }
        
        return $sizes;
    }

    /**
     * Récupérer les informations d'une taille de boisson
     */
    private function get_beverage_size_info($size_id)
    {
        global $wpdb;
        
        $size_info = $wpdb->get_row($wpdb->prepare("
            SELECT bs.*, p.name as product_name
            FROM {$wpdb->prefix}restaurant_beverage_sizes bs
            LEFT JOIN {$wpdb->prefix}restaurant_products p ON bs.product_id = p.id
            WHERE bs.id = %d AND bs.is_active = 1
        ", $size_id), ARRAY_A);
        
        if ($size_info) {
            $size_info['price'] = (float) $size_info['price'];
            $size_info['size_cl'] = (int) $size_info['size_cl'];
        }
        
        return $size_info;
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
        // Récupérer les fûts et jeux depuis la BDD
        $kegs = $this->get_products_by_category('fut');
        
        // Récupérer les jeux par ID de catégorie 111 (Jeux et Animations)
        global $wpdb;
        $games = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, c.name as category_name, c.type as category_type 
             FROM {$wpdb->prefix}restaurant_products p
             LEFT JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
             WHERE p.category_id = %d AND p.is_active = 1 AND c.is_active = 1
             ORDER BY p.display_order ASC, p.name ASC",
            111
        ));
        
        // Ajouter l'URL de l'image pour chaque jeu
        foreach ($games as &$game) {
            $game->image_url = $game->image_id ? wp_get_attachment_image_url($game->image_id, 'medium') : '';
            $game->price = (float) $game->price;
        }
        
        ob_start();
        ?>
        <div class="rbf-v3-step-content active" data-step="6">
            <h2 class="rbf-v3-step-title">Choix des options (optionnel)</h2>
            
            <!-- Message d'information -->
            <div class="rbf-v3-product-section">
                <div class="rbf-v3-message info">
                    <div class="rbf-v3-message-content">
                        <strong>⚡ Information :</strong>
                        <span>Ces options sont spécifiques à la remorque Block et sont entièrement optionnelles.</span>
                    </div>
                </div>
            </div>
            
            <!-- Options disponibles -->
            <div class="rbf-v3-options-grid">
                
                <!-- Option Tireuse -->
                <div class="rbf-v3-option-card">
                    <h3>🍺 MISE À DISPO TIREUSE <?php echo esc_html($this->options['tireuse_price'] ?? '50'); ?> €</h3>
                    <p>Descriptif + mention (fûts non inclus à choisir)</p>
                    <label class="rbf-v3-checkbox-label">
                        <input type="checkbox" name="option_tireuse" value="1" data-action="toggle-kegs">
                        <span class="rbf-v3-checkmark"></span>
                        Ajouter la tireuse à bière
                    </label>
                    
                    <!-- Sélection des fûts (masquée par défaut) -->
                    <div class="rbf-v3-kegs-selection" style="display: none; margin-top: 20px;">
                        <h4>SÉLECTION DES FÛTS (si tireuse sélectionnée)</h4>
                        
                        <!-- ✅ CORRECTION : Onglets fûts par catégorie dynamiques -->
                        <div class="rbf-v3-kegs-tabs">
                            <?php 
                            $beer_categories = $this->get_beer_categories_for_kegs();
                            $first_category = true;
                            foreach ($beer_categories as $category_key => $category_name) : ?>
                                <button type="button" class="rbf-v3-tab-btn <?php echo $first_category ? 'active' : ''; ?>" data-tab="<?php echo esc_attr($category_key); ?>">
                                    <?php echo esc_html(strtoupper($category_name)); ?>
                                </button>
                                <?php $first_category = false; ?>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="rbf-v3-kegs-content">
                            <?php if (!empty($kegs)) : ?>
                                <?php foreach ($kegs as $keg) : ?>
                                    <div class="rbf-v3-keg-card" data-category="<?php echo esc_attr(strtolower($keg->beer_category ?? 'blonde')); ?>">
                                        <div class="rbf-v3-keg-image">
                                            <?php if (!empty($keg->image_url)) : ?>
                                                <img src="<?php echo esc_url($keg->image_url); ?>" alt="<?php echo esc_attr($keg->name); ?>">
                                            <?php else : ?>
                                                <div class="rbf-v3-placeholder-image">🍺</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="rbf-v3-keg-content">
                                            <div class="rbf-v3-keg-info">
                                                <h5><?php echo esc_html($keg->name); ?></h5>
                                                <?php if (!empty($keg->description)) : ?>
                                                    <p class="rbf-v3-keg-description"><?php echo esc_html($keg->description); ?></p>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="rbf-v3-keg-sizes">
                                                <?php 
                                                // Récupérer les tailles disponibles pour ce fût depuis wp_restaurant_keg_sizes
                                                global $wpdb;
                                                $keg_sizes = $wpdb->get_results($wpdb->prepare("
                                                    SELECT liters, price, is_featured, display_order
                                                    FROM {$wpdb->prefix}restaurant_keg_sizes 
                                                    WHERE product_id = %d AND is_active = 1
                                                    ORDER BY display_order ASC, liters ASC
                                                ", $keg->id));
                                                
                                                if (!empty($keg_sizes)) :
                                                    foreach ($keg_sizes as $size) : ?>
                                                        <div class="rbf-v3-size-option">
                                                            <div class="rbf-v3-size-info">
                                                                <span class="rbf-v3-size-label"><?php echo $size->liters; ?>L: <?php echo number_format($size->price, 0); ?>€</span>
                                                                <?php if ($size->is_featured) : ?>
                                                                    <span class="rbf-v3-featured">⭐</span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="rbf-v3-quantity-selector">
                                                                <button type="button" class="rbf-v3-qty-btn rbf-v3-qty-minus" data-target="keg_<?php echo $keg->id; ?>_<?php echo $size->liters; ?>l_qty">-</button>
                                                                <input type="number" name="keg_<?php echo $keg->id; ?>_<?php echo $size->liters; ?>l_qty" value="0" min="0" max="999" class="rbf-v3-qty-input">
                                                                <button type="button" class="rbf-v3-qty-btn rbf-v3-qty-plus" data-target="keg_<?php echo $keg->id; ?>_<?php echo $size->liters; ?>l_qty">+</button>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; 
                                                else : ?>
                                                    <div class="rbf-v3-size-option">
                                                        <div class="rbf-v3-size-info">
                                                            <span class="rbf-v3-size-label">Prix de base: <?php echo number_format($keg->price, 0); ?>€</span>
                                                        </div>
                                                        <div class="rbf-v3-quantity-selector">
                                                            <button type="button" class="rbf-v3-qty-btn rbf-v3-qty-minus" data-target="keg_<?php echo $keg->id; ?>_qty">-</button>
                                                            <input type="number" name="keg_<?php echo $keg->id; ?>_qty" value="0" min="0" max="999" class="rbf-v3-qty-input">
                                                            <button type="button" class="rbf-v3-qty-btn rbf-v3-qty-plus" data-target="keg_<?php echo $keg->id; ?>_qty">+</button>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <p class="rbf-v3-no-products">Aucun fût disponible pour le moment.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Option Jeux -->
                <div class="rbf-v3-option-card">
                    <h3>🎮 INSTALLATION JEUX <?php echo esc_html($this->options['games_price'] ?? '70'); ?> €</h3>
                    <p>Descriptif avec listing des jeux disponibles</p>
                    <label class="rbf-v3-checkbox-label">
                        <input type="checkbox" name="option_games" value="1" data-action="toggle-games">
                        <span class="rbf-v3-checkmark"></span>
                        Ajouter l'installation jeux
                    </label>
                    
                    <!-- Sélection des jeux (masquée par défaut) -->
                    <div class="rbf-v3-games-selection" style="display: none; margin-top: 20px;">
                        <h4>SÉLECTION DES JEUX (si option sélectionnée)</h4>
                        
                        <div class="rbf-v3-games-grid">
                            <?php if (!empty($games)) : ?>
                                <?php foreach ($games as $game) : ?>
                                    <div class="rbf-v3-game-card">
                                        <div class="rbf-v3-game-image">
                                            <?php if (!empty($game->image_url)) : ?>
                                                <img src="<?php echo esc_url($game->image_url); ?>" alt="<?php echo esc_attr($game->name); ?>">
                                            <?php else : ?>
                                                <div class="rbf-v3-placeholder-image">🎮</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="rbf-v3-game-info">
                                            <h5><?php echo esc_html($game->name); ?></h5>
                                            <?php if (!empty($game->description)) : ?>
                                                <p class="rbf-v3-game-description"><?php echo esc_html($game->description); ?></p>
                                            <?php endif; ?>
                                            <div class="rbf-v3-game-price">
                                                <span><?php echo number_format($game->price, 0); ?>€</span>
                                                <label class="rbf-v3-checkbox-label">
                                                    <input type="checkbox" name="game_<?php echo $game->id; ?>" value="1">
                                                    <span class="rbf-v3-checkmark"></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <p class="rbf-v3-no-products">Aucun jeu disponible pour le moment.</p>
                            <?php endif; ?>
                        </div>
                    </div>
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
                        <strong><?php echo (($form_data['service_type'] ?? '') === 'restaurant') ? 'Privatisation du restaurant' : 'Privatisation de la remorque Block'; ?></strong>
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

        // Prix de base selon le service (récupéré depuis la base de données)
        $base_price = $this->get_base_price($service_type);

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

        // Supplément distance (remorque uniquement)
        if ($service_type === 'remorque' && isset($form_data['delivery_supplement']) && $form_data['delivery_supplement'] > 0) {
            $delivery_supplement = (float) $form_data['delivery_supplement'];
            $delivery_zone = $form_data['delivery_zone'] ?? 'Zone de livraison';
            $supplements_array[] = [
                'name' => 'Supplément livraison (' . $delivery_zone . ')',
                'price' => $delivery_supplement
            ];
        }

        // Prix des produits (calculer selon les sélections)
        $products_array = $this->calculate_products_detailed($form_data);
        
        // ✅ CORRECTION : Prix des options remorque (tireuse + jeux)
        $options_array = [];
        $options_total = 0;
        if ($service_type === 'remorque') {
            $options_data = $this->calculate_options_detailed($form_data);
            $options_array = $options_data['options'];
            $options_total = $options_data['total'];
        }

        // Calculer les totaux
        $supplements_total = array_sum(array_column($supplements_array, 'price'));
        $products_total = array_sum(array_column($products_array, 'total'));
        $total = $base_price + $supplements_total + $products_total + $options_total;

        return [
            'base_price' => $base_price,
            'supplements' => $supplements_array,
            'products' => $products_array,
            'options' => $options_array, // ✅ CORRECTION : Inclure les options dans le retour
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
                    
                    // Inclure tous les produits sélectionnés, même avec prix 0 pour diagnostic
                    $products[] = [
                        'name' => $product_name,
                        'quantity' => $quantity,
                        'price' => $product_price,
                        'total' => $quantity * $product_price,
                        'category' => 'Plats signature'
                    ];
                    
                    // Logger si le prix est 0 pour diagnostic
                    if ($product_price <= 0) {
                        RestaurantBooking_Logger::warning('Produit signature avec prix 0', array(
                            'product_id' => $product_id,
                            'product_name' => $product_name,
                            'quantity' => $quantity
                        ));
                    }
                }
            }
        }
        
        // Calculer les accompagnements avec leurs options
        foreach ($form_data as $key => $value) {
            if (strpos($key, 'accompaniment_') === 0 && strpos($key, '_qty') !== false) {
                $quantity = intval($value);
                if ($quantity > 0) {
                    $product_id = str_replace(['accompaniment_', '_qty'], '', $key);
                    $product_name = $this->get_product_name($product_id, 'accompaniment');
                    $product_price = $this->get_product_price($product_id, 'accompaniment');
                    
                    if ($product_price > 0) {
                        $product_item = [
                            'name' => $product_name,
                            'quantity' => $quantity,
                            'price' => $product_price,
                            'total' => $quantity * $product_price,
                            'category' => 'Accompagnements',
                            'options' => [] // ✅ NOUVEAU : Structure pour les options
                        ];
                        
                        // Ajouter les options d'accompagnement depuis la base de données
                        $product_item['options'] = $this->get_accompaniment_options_for_display($product_id, $form_data, $quantity);
                        
                        // ✅ CORRECTION : Calculer le prix total incluant les options
                        $options_total = 0;
                        foreach ($product_item['options'] as $option) {
                            $options_total += $option['total'];
                            // Ajouter aussi les sous-options si elles ont un prix
                            if (isset($option['suboptions'])) {
                                foreach ($option['suboptions'] as $suboption) {
                                    $options_total += $suboption['total'];
                                }
                            }
                        }
                        $product_item['total'] += $options_total;
                        
                        $products[] = $product_item;
                    }
                }
            }
        }
        
        // ✅ CORRECTION : Calculer les buffets salés
        foreach ($form_data as $key => $value) {
            if (strpos($key, 'buffet_sale_') === 0 && strpos($key, '_qty') !== false) {
                $quantity = intval($value);
                if ($quantity > 0) {
                    $product_id = str_replace(['buffet_sale_', '_qty'], '', $key);
                    $product_name = $this->get_product_name($product_id, 'buffet_sale');
                    $product_price = $this->get_product_price($product_id, 'buffet_sale');
                    
                    // Inclure tous les produits sélectionnés, même avec prix 0 pour diagnostic
                    $products[] = [
                        'name' => $product_name,
                        'quantity' => $quantity,
                        'price' => $product_price,
                        'total' => $quantity * $product_price,
                        'category' => 'Buffet salé'
                    ];
                    
                    // Logger si le prix est 0 pour diagnostic
                    if ($product_price <= 0) {
                        RestaurantBooking_Logger::warning('Produit buffet salé avec prix 0', array(
                            'product_id' => $product_id,
                            'product_name' => $product_name,
                            'quantity' => $quantity
                        ));
                    }
                }
            }
        }
        
        // ✅ CORRECTION : Calculer les buffets sucrés
        foreach ($form_data as $key => $value) {
            if (strpos($key, 'buffet_sucre_') === 0 && strpos($key, '_qty') !== false) {
                $quantity = intval($value);
                if ($quantity > 0) {
                    $product_id = str_replace(['buffet_sucre_', '_qty'], '', $key);
                    $product_name = $this->get_product_name($product_id, 'buffet_sucre');
                    $product_price = $this->get_product_price($product_id, 'buffet_sucre');
                    
                    // Inclure tous les produits sélectionnés, même avec prix 0 pour diagnostic
                    $products[] = [
                        'name' => $product_name,
                        'quantity' => $quantity,
                        'price' => $product_price,
                        'total' => $quantity * $product_price,
                        'category' => 'Buffet sucré'
                    ];
                    
                    // Logger si le prix est 0 pour diagnostic
                    if ($product_price <= 0) {
                        RestaurantBooking_Logger::warning('Produit buffet sucré avec prix 0', array(
                            'product_id' => $product_id,
                            'product_name' => $product_name,
                            'quantity' => $quantity
                        ));
                    }
                }
            }
        }
        
        // ✅ CORRECTION : Calculer les boissons depuis les champs du formulaire
        // Parcourir tous les champs pour trouver les boissons (pattern: beverage_[id]_qty ou beverage_size_[id]_qty)
        foreach ($form_data as $field_name => $field_value) {
            if (preg_match('/^beverage_(\d+)_qty$/', $field_name, $matches) && intval($field_value) > 0) {
                $product_id = intval($matches[1]);
                $quantity = intval($field_value);
                
                // Récupérer les infos du produit depuis la base de données
                $product_name = $this->get_product_name($product_id, 'boissons');
                $product_price = $this->get_product_price($product_id, 'boissons');
                
                if ($product_price > 0) {
                    $products[] = [
                        'name' => $product_name,
                        'quantity' => $quantity,
                        'price' => $product_price,
                        'total' => $quantity * $product_price,
                        'category' => 'Boissons'
                    ];
                }
            }
            // Gérer les tailles de boissons (beverage_size_[id]_qty)
            elseif (preg_match('/^beverage_size_(\d+)_qty$/', $field_name, $matches) && intval($field_value) > 0) {
                $size_id = intval($matches[1]);
                $quantity = intval($field_value);
                
                // Récupérer les infos de la taille depuis la base de données
                $size_info = $this->get_beverage_size_info($size_id);
                if ($size_info && $size_info['price'] > 0) {
                    $products[] = [
                        'name' => $size_info['product_name'] . ' (' . $size_info['size_label'] . ')',
                        'quantity' => $quantity,
                        'price' => $size_info['price'],
                        'total' => $quantity * $size_info['price'],
                        'category' => 'Boissons'
                    ];
                }
            }
        }
        
        // ✅ CORRECTION : Calculer les Mini Boss
        foreach ($form_data as $key => $value) {
            if (strpos($key, 'mini_boss_') === 0 && strpos($key, '_qty') !== false) {
                $quantity = intval($value);
                if ($quantity > 0) {
                    $product_id = str_replace(['mini_boss_', '_qty'], '', $key);
                    $product_name = $this->get_product_name($product_id, 'mini_boss');
                    $product_price = $this->get_product_price($product_id, 'mini_boss');
                    
                    // Inclure tous les produits sélectionnés, même avec prix 0 pour diagnostic
                    $products[] = [
                        'name' => $product_name,
                        'quantity' => $quantity,
                        'price' => $product_price,
                        'total' => $quantity * $product_price,
                        'category' => 'Mini Boss'
                    ];
                    
                    // Logger si le prix est 0 pour diagnostic
                    if ($product_price <= 0) {
                        RestaurantBooking_Logger::warning('Produit Mini Boss avec prix 0', array(
                            'product_id' => $product_id,
                            'product_name' => $product_name,
                            'quantity' => $quantity
                        ));
                    }
                }
            }
        }
        
        // NE PLUS traiter les options séparément - elles sont maintenant dans les produits parents
        
        return $products;
    }

    /**
     * Obtenir les options d'accompagnement pour l'affichage hiérarchique (DYNAMIQUE)
     * Récupère toutes les options depuis la base de données
     */
    private function get_accompaniment_options_for_display($product_id, $form_data, $product_quantity)
    {
        global $wpdb;
        
        $options = [];
        
        // Récupérer toutes les options d'accompagnement pour ce produit depuis la DB
        $accompaniment_options = $wpdb->get_results($wpdb->prepare("
            SELECT ao.id, ao.option_name, ao.option_price
            FROM {$wpdb->prefix}restaurant_accompaniment_options ao
            WHERE ao.product_id = %d AND ao.is_active = 1
            ORDER BY ao.display_order, ao.option_name
        ", $product_id));
        
        foreach ($accompaniment_options as $option) {
            // Chercher les quantités sélectionnées pour cette option
            $option_quantity = $this->find_option_quantity_in_form_data($option, $form_data);
            
            if ($option_quantity > 0) {
                $option_item = [
                    'name' => $option->option_name . ($option->option_price > 0 ? ' (+' . number_format($option->option_price, 2) . '€)' : ''),
                    'quantity' => $option_quantity,
                    'price' => (float) $option->option_price,
                    'total' => $option_quantity * (float) $option->option_price
                ];
                
                // Récupérer les sous-options pour cette option
                $suboptions = $this->get_suboptions_for_display($option->id, $form_data);
                if (!empty($suboptions)) {
                    $option_item['suboptions'] = $suboptions;
                }
                
                $options[] = $option_item;
            }
        }
        
        return $options;
    }
    
    /**
     * Trouver la quantité d'une option dans les données du formulaire
     */
    private function find_option_quantity_in_form_data($option, $form_data)
    {
        // Utiliser la même logique que la génération HTML
        $field_name = $this->get_option_field_name($option->option_name);
        
        if (isset($form_data[$field_name]) && intval($form_data[$field_name]) > 0) {
            return intval($form_data[$field_name]);
        }
        
        // Stratégies alternatives pour compatibilité
        $possible_field_names = [
            // Format avec préfixe option_
            'option_' . $this->sanitize_field_name($option->option_name) . '_qty',
            // Format avec ID
            'option_' . $option->id . '_qty',
            // Format legacy pour frites
            'enrobee_sauce_chimichurri_qty' // Pour compatibilité avec l'ancien système
        ];
        
        foreach ($possible_field_names as $fallback_name) {
            if (isset($form_data[$fallback_name]) && intval($form_data[$fallback_name]) > 0) {
                return intval($form_data[$fallback_name]);
            }
        }
        
        return 0;
    }
    
    /**
     * Récupérer les sous-options pour l'affichage
     */
    private function get_suboptions_for_display($option_id, $form_data)
    {
        global $wpdb;
        
        $suboptions = [];
        
        // Récupérer les sous-options depuis la DB
        // CORRECTION: La colonne option_price n'existe pas dans cette table
        $db_suboptions = $wpdb->get_results($wpdb->prepare("
            SELECT id, suboption_name, 0 as option_price
            FROM {$wpdb->prefix}restaurant_accompaniment_suboptions
            WHERE option_id = %d AND is_active = 1
            ORDER BY display_order, suboption_name
        ", $option_id));
        
        foreach ($db_suboptions as $suboption) {
            $suboption_quantity = $this->find_suboption_quantity_in_form_data($suboption, $form_data);
            
            if ($suboption_quantity > 0) {
                $suboptions[] = [
                    'name' => $suboption->suboption_name . ($suboption->option_price > 0 ? ' (+' . number_format($suboption->option_price, 2) . '€)' : ''),
                    'quantity' => $suboption_quantity,
                    'price' => (float) $suboption->option_price,
                    'total' => $suboption_quantity * (float) $suboption->option_price
                ];
            }
        }
        
        return $suboptions;
    }
    
    /**
     * Trouver la quantité d'une sous-option dans les données du formulaire
     */
    private function find_suboption_quantity_in_form_data($suboption, $form_data)
    {
        // Utiliser la même logique que la génération HTML
        $field_name = $this->get_suboption_field_name($suboption->suboption_name);
        
        if (isset($form_data[$field_name]) && intval($form_data[$field_name]) > 0) {
            return intval($form_data[$field_name]);
        }
        
        // Stratégies alternatives pour compatibilité
        $possible_field_names = [
            // Mapping explicite des sous-options connues
            'sauce_ketchup_qty',
            'sauce_mayonnaise_qty', 
            'sauce_moutarde_qty',
            'sauce_sauce_bbq_qty',
            // Format sans préfixe sauce_
            $this->sanitize_field_name($suboption->suboption_name) . '_qty',
            // Format avec préfixe suboption_
            'suboption_' . $this->sanitize_field_name($suboption->suboption_name) . '_qty',
            // Format avec ID
            'suboption_' . $suboption->id . '_qty'
        ];
        
        foreach ($possible_field_names as $fallback_name) {
            if (isset($form_data[$fallback_name]) && intval($form_data[$fallback_name]) > 0) {
                return intval($form_data[$fallback_name]);
            }
        }
        
        return 0;
    }
    
    /**
     * ✅ CORRECTION : Calculer le prix des options remorque avec détails
     */
    private function calculate_options_detailed($form_data)
    {
        $options = [];
        $total = 0;
        
        // CORRECTION : Vérifier d'abord si des fûts sont sélectionnés
        $has_kegs_selected = false;
        $kegs_total = 0;
        $kegs_options = [];
        
        // Parcourir les fûts sélectionnés
        foreach ($form_data as $key => $value) {
            if (strpos($key, 'keg_') === 0 && strpos($key, '_qty') !== false) {
                $quantity = intval($value);
                if ($quantity > 0) {
                    $has_kegs_selected = true;
                    
                    // Extraire l'ID du fût et la taille
                    // Format: keg_35_10l_qty ou keg_35_qty (sans taille spécifique)
                    $key_clean = str_replace(['keg_', '_qty'], ['', ''], $key);
                    $parts = explode('_', $key_clean);
                    $keg_id = $parts[0];
                    
                    // Vérifier s'il y a une taille spécifiée (ex: 10l, 20l)
                    if (count($parts) > 1 && preg_match('/^\d+l$/', $parts[1])) {
                        $size = $parts[1];
                        $keg_name = $this->get_product_name($keg_id, 'fut');
                        $keg_price = $this->get_keg_price($keg_id, $size);
                        
                        $kegs_options[] = [
                            'name' => $keg_name . ' (' . strtoupper($size) . ')',
                            'quantity' => $quantity,
                            'price' => $keg_price,
                            'total' => $quantity * $keg_price
                        ];
                        $kegs_total += $quantity * $keg_price;
                    } else {
                        // Pas de taille spécifiée, utiliser le prix de base du produit
                        $keg_name = $this->get_product_name($keg_id, 'fut');
                        $keg_price = $this->get_product_price($keg_id, 'fut');
                        
                        $kegs_options[] = [
                            'name' => $keg_name,
                            'quantity' => $quantity,
                            'price' => $keg_price,
                            'total' => $quantity * $keg_price
                        ];
                        $kegs_total += $quantity * $keg_price;
                    }
                }
            }
        }
        
        // Option tireuse - Automatique si des fûts sont sélectionnés OU explicitement cochée
        if ((isset($form_data['option_tireuse']) && $form_data['option_tireuse'] == '1') || $has_kegs_selected) {
            $tireuse_price = floatval($this->options['tireuse_price'] ?? 50);
            $options[] = [
                'name' => 'Mise à disposition tireuse',
                'price' => $tireuse_price
            ];
            $total += $tireuse_price;
            
            // Ajouter les fûts sélectionnés
            foreach ($kegs_options as $keg_option) {
                $options[] = $keg_option;
            }
            $total += $kegs_total;
        }
        
        // Option jeux - Automatique si des jeux sont sélectionnés OU explicitement cochée
        $has_games_selected = false;
        $games_options = [];
        $games_total = 0;
        
        // Vérifier si des jeux sont sélectionnés
        foreach ($form_data as $key => $value) {
            if (strpos($key, 'game_') === 0 && $value == '1') {
                $has_games_selected = true;
                $game_id = str_replace('game_', '', $key);
                $game_name = $this->get_product_name($game_id, 'jeux');
                $game_price = $this->get_product_price($game_id, 'jeux');
                
                // Vérifier que le jeu est valide
                if (!empty($game_name) && $game_name !== 'Produit invalide' && $game_name !== 'Produit inconnu') {
                    $games_options[] = [
                        'name' => $game_name,
                        'price' => $game_price
                    ];
                    $games_total += $game_price;
                }
            }
        }
        
        if ((isset($form_data['option_games']) && $form_data['option_games'] == '1') || $has_games_selected) {
            $games_price = floatval($this->options['games_price'] ?? 70);
            $options[] = [
                'name' => 'Installation jeux',
                'price' => $games_price
            ];
            $total += $games_price;
            
            // Ajouter les jeux sélectionnés
            foreach ($games_options as $game_option) {
                $options[] = $game_option;
            }
            $total += $games_total;
        }
        
        return [
            'options' => $options,
            'total' => $total
        ];
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
        
        // Validation de l'ID
        if (!is_numeric($product_id) || $product_id <= 0) {
            RestaurantBooking_Logger::warning('ID de produit invalide', array(
                'product_id' => $product_id,
                'category' => $category
            ));
            return 'Produit invalide';
        }
        
        $table_name = $wpdb->prefix . 'restaurant_products';
        
        // Construire la requête selon la catégorie
        if ($category === 'jeux') {
            // Pour les jeux, filtrer par catégorie ID 111 (Jeux et Animations)
        $product = $wpdb->get_row($wpdb->prepare(
            "SELECT p.name, c.name as category_name, c.type as category_type 
             FROM {$table_name} p
             LEFT JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
             WHERE p.id = %d AND p.is_active = 1 AND c.id = 111",
            intval($product_id)
        ));
        
        // Nettoyer les échappements dans le résultat
        $product = RestaurantBooking_Text_Cleaner::clean_object($product);
        } else {
            // Pour les autres catégories, utiliser la requête originale
        $product = $wpdb->get_row($wpdb->prepare(
            "SELECT p.name, c.name as category_name, c.type as category_type 
             FROM {$table_name} p
             LEFT JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
             WHERE p.id = %d AND p.is_active = 1",
            intval($product_id)
        ));
        
        // Nettoyer les échappements dans le résultat
        $product = RestaurantBooking_Text_Cleaner::clean_object($product);
        }
        
        if ($product) {
            RestaurantBooking_Logger::debug('Produit trouvé', array(
                'product_id' => $product_id,
                'name' => $product->name,
                'category' => $category
            ));
            return $product->name;
        }
        
        // Logger l'erreur avec plus de détails pour diagnostiquer
        RestaurantBooking_Logger::error('Produit non trouvé dans la base de données', array(
            'product_id' => $product_id,
            'category' => $category,
            'sql_query' => $wpdb->last_query,
            'sql_error' => $wpdb->last_error,
            'action' => 'get_product_name'
        ));
        
        // Retourner un nom plus informatif au lieu de données hardcodées
        return sprintf('Produit #%d (%s)', $product_id, $category);
    }

    /**
     * Obtenir le prix d'un produit
     */
    private function get_product_price($product_id, $category)
    {
        global $wpdb;
        
        // Validation de l'ID
        if (!is_numeric($product_id) || $product_id <= 0) {
            RestaurantBooking_Logger::warning('ID de produit invalide pour prix', array(
                'product_id' => $product_id,
                'category' => $category
            ));
            return 0.0;
        }
        
        $table_name = $wpdb->prefix . 'restaurant_products';
        
        // Construire la requête selon la catégorie
        if ($category === 'jeux') {
            // Pour les jeux, filtrer par catégorie ID 111 (Jeux et Animations)
            $product = $wpdb->get_row($wpdb->prepare(
                "SELECT p.price, c.name as category_name, c.type as category_type 
                 FROM {$table_name} p
                 LEFT JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
                 WHERE p.id = %d AND p.is_active = 1 AND c.id = 111",
                intval($product_id)
            ));
        } else {
            // Pour les autres catégories, utiliser la requête originale
            $product = $wpdb->get_row($wpdb->prepare(
                "SELECT p.price, c.name as category_name, c.type as category_type 
                 FROM {$table_name} p
                 LEFT JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
                 WHERE p.id = %d AND p.is_active = 1",
                intval($product_id)
            ));
        }
        
        if ($product) {
            $price = (float) $product->price;
            RestaurantBooking_Logger::debug('Prix de produit trouvé', array(
                'product_id' => $product_id,
                'price' => $price,
                'category' => $category
            ));
            return $price;
        }
        
        // Logger l'erreur avec plus de détails pour diagnostiquer
        RestaurantBooking_Logger::error('Prix de produit non trouvé dans la base de données', array(
            'product_id' => $product_id,
            'category' => $category,
            'sql_query' => $wpdb->last_query,
            'sql_error' => $wpdb->last_error,
            'action' => 'get_product_price'
        ));
        
        // Retourner 0 au lieu de prix hardcodés
        return 0.0;
    }
    
    /**
     * ✅ CORRECTION : Obtenir le prix d'un fût selon la taille
     */
    private function get_keg_price($keg_id, $size)
    {
        global $wpdb;
        
        // Convertir la taille en litres (ex: '10l' -> 10, '20l' -> 20)
        $liters = intval(str_replace('l', '', $size));
        
        $price = $wpdb->get_var($wpdb->prepare("
            SELECT price 
            FROM {$wpdb->prefix}restaurant_keg_sizes 
            WHERE product_id = %d AND liters = %d AND is_active = 1
        ", $keg_id, $liters));
        
        if ($price) {
            return floatval($price);
        }
        
        // Fallback pour les fûts de démonstration
        return $size === '20l' ? 80 : 45;
    }
    
    /**
     * Obtenir les données d'une taille de boisson
     */
    private function get_beverage_size_data($size_id)
    {
        global $wpdb;
        
        $size = $wpdb->get_row($wpdb->prepare("
            SELECT bs.*, p.name as product_name 
            FROM {$wpdb->prefix}restaurant_beverage_sizes bs
            INNER JOIN {$wpdb->prefix}restaurant_products p ON bs.product_id = p.id
            WHERE bs.id = %d AND bs.is_active = 1
        ", $size_id), ARRAY_A);
        
        if ($size) {
            return [
                'product_name' => $size['product_name'],
                'size_cl' => intval($size['size_cl']),
                'price' => floatval($size['price'])
            ];
        }
        
        return null;
    }
    
    /**
     * Obtenir le volume d'un produit boisson
     */
    private function get_product_volume($product_id)
    {
        global $wpdb;
        
        $volume = $wpdb->get_var($wpdb->prepare(
            "SELECT volume_cl FROM {$wpdb->prefix}restaurant_products WHERE id = %d",
            $product_id
        ));
        
        return $volume ? intval($volume) : null;
    }
    
    /**
     * Obtenir les onglets de boissons disponibles selon le service
     */
    private function get_available_beverage_tabs($service_type)
    {
        global $wpdb;
        
        $tabs = array();
        
        // CORRECTION : Utiliser les IDs réels selon l'analyse de la base de données
        // ID 106 = Soft, ID 109 = Bières Bouteilles, ID 110 = Fûts, ID 112 = Vins
        $beverage_category_ids = array(106, 109, 110, 112);
        
        // Vérifier quelles catégories de boissons ont des produits actifs
        $placeholders = implode(',', array_fill(0, count($beverage_category_ids), '%d'));
        $params = $beverage_category_ids;
        
        $sql = "SELECT c.id, c.type, c.name, c.display_order, COUNT(p.id) as product_count
                FROM {$wpdb->prefix}restaurant_categories c
                INNER JOIN {$wpdb->prefix}restaurant_products p ON c.id = p.category_id
                WHERE c.id IN ($placeholders) AND c.is_active = 1 AND p.is_active = 1";
        
        // Filtrer par service si nécessaire
        if ($service_type !== 'both') {
            $sql .= " AND (c.service_type = %s OR c.service_type = 'both')";
            $params[] = $service_type;
        }
        
        $sql .= " GROUP BY c.id ORDER BY c.display_order ASC, c.name ASC";
        
        $categories = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
        
        // Mapper les catégories vers les onglets selon les IDs réels
        foreach ($categories as $category) {
            $category_id = intval($category['id']);
            
            switch ($category_id) {
                case 106: // Boissons Soft
                    $tabs['soft'] = array(
                        'label' => 'Soft',
                        'category_id' => 106
                    );
                    break;
                
                case 112: // Vins
                    $tabs['wines'] = array(
                        'label' => 'Vins',
                        'category_id' => 112
                    );
                    break;
                
                case 109: // Bières Bouteilles
                    $tabs['beers'] = array(
                        'label' => 'Bières',
                        'category_id' => 109
                    );
                    break;
                
                case 110: // Fûts de Bière (seulement pour remorque, dans l'étape 6)
                    if ($service_type !== 'restaurant') {
                        // Les fûts sont dans l'étape 6 pour la remorque, on les ignore ici
                        continue 2;
                    }
                    break;
            }
        }
        
        // S'assurer qu'il y a au moins un onglet par défaut
        if (empty($tabs)) {
            $tabs['soft'] = array(
                'label' => 'Soft',
                'category_id' => 106
            );
        }
        
        return $tabs;
    }
    
    /**
     * Générer le contenu d'un onglet de boisson
     */
    private function generate_beverage_tab_content($beverages, $tab_key, $tab_data)
    {
        $placeholder_icon = '🥤'; // Par défaut
        $category_label = $tab_data['label'];
        
        // Définir l'icône selon le type
        switch ($tab_key) {
            case 'wines':
                $placeholder_icon = '🍷';
                break;
            case 'beers':
                $placeholder_icon = '🍺';
                break;
            case 'soft':
            default:
                $placeholder_icon = '🥤';
                break;
        }
        
        ob_start();
        ?>
        <div class="rbf-v3-beverages-section">
            <?php if ($tab_key === 'wines' || $tab_key === 'beers') : ?>
                <div class="rbf-v3-subcategory-filters">
                    <?php echo $this->generate_beverage_subcategory_buttons($beverages, $tab_key); ?>
                </div>
            <?php endif; ?>
            
            <h3>🌟 NOS SUGGESTIONS</h3>
            <div class="rbf-v3-beverages-grid">
                <?php 
                $has_featured = false;
                foreach ($beverages as $beverage) : ?>
                    <?php if ($beverage['is_featured']) : 
                        $has_featured = true; ?>
                        <div class="rbf-v3-beverage-card featured" 
                             data-product-id="<?php echo esc_attr($beverage['id']); ?>" 
                             data-category="<?php echo esc_attr($beverage['category_type']); ?>"
                             data-wine-category="<?php echo esc_attr($beverage['wine_category'] ?? ''); ?>"
                             data-beer-category="<?php echo esc_attr($beverage['beer_category'] ?? ''); ?>">
                            <div class="rbf-v3-beverage-image">
                                <?php if (!empty($beverage['image_url'])) : ?>
                                    <img src="<?php echo esc_url($beverage['image_url']); ?>" alt="<?php echo esc_attr($beverage['name']); ?>">
                                <?php else : ?>
                                    <div class="rbf-v3-placeholder-image"><?php echo $placeholder_icon; ?></div>
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
                                
                                <?php if (!empty($beverage['sizes'])) : ?>
                                    <div class="rbf-v3-beverage-sizes">
                                        <?php foreach ($beverage['sizes'] as $size) : ?>
                                        <div class="rbf-v3-size-option">
                                            <div class="rbf-v3-size-info">
                                                <span class="rbf-v3-size-label"><?php echo esc_html($size['size_cl']); ?>cl</span>
                                                <span class="rbf-v3-size-price"><?php echo number_format($size['price'], 2); ?>€</span>
                                            </div>
                                            <div class="rbf-v3-quantity-selector">
                                                <button type="button" class="rbf-v3-qty-btn minus" data-target="beverage_size_<?php echo esc_attr($size['id']); ?>_qty">-</button>
                                                <input type="number" name="beverage_size_<?php echo esc_attr($size['id']); ?>_qty" class="rbf-v3-qty-input" value="0" min="0" data-size-id="<?php echo esc_attr($size['id']); ?>" data-price="<?php echo esc_attr($size['price']); ?>">
                                                <button type="button" class="rbf-v3-qty-btn plus" data-target="beverage_size_<?php echo esc_attr($size['id']); ?>_qty">+</button>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else : ?>
                                    <div class="rbf-v3-beverage-sizes">
                                        <div class="rbf-v3-size-option">
                                            <div class="rbf-v3-size-info">
                                                <?php if (!empty($beverage['volume_cl'])) : ?>
                                                    <span class="rbf-v3-size-label"><?php echo esc_html($beverage['volume_cl']); ?>cl</span>
                                                <?php endif; ?>
                                                <span class="rbf-v3-size-price"><?php echo number_format($beverage['price'], 2); ?>€</span>
                                            </div>
                                            <div class="rbf-v3-quantity-selector">
                                                <button type="button" class="rbf-v3-qty-btn minus" data-target="beverage_<?php echo esc_attr($beverage['id']); ?>_qty">-</button>
                                                <input type="number" name="beverage_<?php echo esc_attr($beverage['id']); ?>_qty" class="rbf-v3-qty-input" value="0" min="0" data-product-id="<?php echo esc_attr($beverage['id']); ?>" data-price="<?php echo esc_attr($beverage['price']); ?>">
                                                <button type="button" class="rbf-v3-qty-btn plus" data-target="beverage_<?php echo esc_attr($beverage['id']); ?>_qty">+</button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
                
                <?php if (!$has_featured) : ?>
                    <div class="rbf-v3-no-suggestions">
                        <p>Aucune suggestion disponible pour le moment dans cette catégorie.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <h3>📋 TOUS LES <?php echo strtoupper($category_label); ?></h3>
            <div class="rbf-v3-beverages-grid">
                <?php foreach ($beverages as $beverage) : ?>
                    <div class="rbf-v3-beverage-card" 
                         data-product-id="<?php echo esc_attr($beverage['id']); ?>" 
                         data-category="<?php echo esc_attr($beverage['category_type']); ?>"
                         data-wine-category="<?php echo esc_attr($beverage['wine_category'] ?? ''); ?>"
                         data-beer-category="<?php echo esc_attr($beverage['beer_category'] ?? ''); ?>">
                        <div class="rbf-v3-beverage-image">
                            <?php if (!empty($beverage['image_url'])) : ?>
                                <img src="<?php echo esc_url($beverage['image_url']); ?>" alt="<?php echo esc_attr($beverage['name']); ?>">
                            <?php else : ?>
                                <div class="rbf-v3-placeholder-image"><?php echo $placeholder_icon; ?></div>
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
                            
                            <?php if (!empty($beverage['sizes'])) : ?>
                                <div class="rbf-v3-beverage-sizes">
                                    <?php foreach ($beverage['sizes'] as $size) : ?>
                                        <div class="rbf-v3-size-option">
                                            <div class="rbf-v3-size-info">
                                                <span class="rbf-v3-size-label"><?php echo esc_html($size['size_cl']); ?>cl</span>
                                                <span class="rbf-v3-size-price"><?php echo number_format($size['price'], 2); ?>€</span>
                                            </div>
                                            <div class="rbf-v3-quantity-selector">
                                                <button type="button" class="rbf-v3-qty-btn minus" data-target="beverage_size_<?php echo esc_attr($size['id']); ?>_qty">-</button>
                                                <input type="number" name="beverage_size_<?php echo esc_attr($size['id']); ?>_qty" class="rbf-v3-qty-input" value="0" min="0" data-size-id="<?php echo esc_attr($size['id']); ?>" data-price="<?php echo esc_attr($size['price']); ?>">
                                                <button type="button" class="rbf-v3-qty-btn plus" data-target="beverage_size_<?php echo esc_attr($size['id']); ?>_qty">+</button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else : ?>
                                <div class="rbf-v3-beverage-sizes">
                                    <div class="rbf-v3-size-option">
                                        <div class="rbf-v3-size-info">
                                            <?php if (!empty($beverage['volume_cl'])) : ?>
                                                <span class="rbf-v3-size-label"><?php echo esc_html($beverage['volume_cl']); ?>cl</span>
                                            <?php endif; ?>
                                            <span class="rbf-v3-size-price"><?php echo number_format($beverage['price'], 2); ?>€</span>
                                        </div>
                                        <div class="rbf-v3-quantity-selector">
                                            <button type="button" class="rbf-v3-qty-btn minus" data-target="beverage_<?php echo esc_attr($beverage['id']); ?>_qty">-</button>
                                            <input type="number" name="beverage_<?php echo esc_attr($beverage['id']); ?>_qty" class="rbf-v3-qty-input" value="0" min="0" data-product-id="<?php echo esc_attr($beverage['id']); ?>" data-price="<?php echo esc_attr($beverage['price']); ?>">
                                            <button type="button" class="rbf-v3-qty-btn plus" data-target="beverage_<?php echo esc_attr($beverage['id']); ?>_qty">+</button>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * ✅ CORRECTION : Envoyer l'email de devis au client
     */
    private function send_quote_email($quote_id)
    {
        global $wpdb;
        
        // Log pour tracer l'ID reçu
        if (class_exists('RestaurantBooking_Logger')) {
            RestaurantBooking_Logger::debug("send_quote_email appelée avec ID: {$quote_id}");
        }
        
        // Récupérer les données du devis
        $table_name = $wpdb->prefix . 'restaurant_quotes';
        $quote = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d",
            $quote_id
        ));
        
        if (!$quote) {
            error_log("Restaurant Booking: Devis non trouvé pour l'ID: {$quote_id}");
            if (class_exists('RestaurantBooking_Logger')) {
                RestaurantBooking_Logger::error("Devis non trouvé dans send_quote_email", ['quote_id' => $quote_id]);
            }
            return false;
        }
        
        // Utiliser la classe Email existante si disponible
        if (class_exists('RestaurantBooking_Email')) {
            return RestaurantBooking_Email::send_quote_email($quote_id);
        }
        
        // Fallback : envoi d'email simple
        $customer_data = json_decode($quote->customer_data, true);
        $price_data = json_decode($quote->price_breakdown, true);
        
        if (!$customer_data || !$customer_data['email']) {
            if (class_exists('RestaurantBooking_Logger')) {
                RestaurantBooking_Logger::error("Données client manquantes pour le devis ID: {$quote_id}");
            }
            return false;
        }
        
        $to = $customer_data['email'];
        $subject = 'Votre devis Block - ' . ($quote->service_type === 'restaurant' ? 'Privatisation du restaurant' : 'Privatisation de la remorque');
        
        $message = "Bonjour " . $customer_data['firstname'] . " " . $customer_data['name'] . ",\n\n";
        $message .= "Nous avons bien reçu votre demande de devis.\n\n";
        $message .= "📋 Numéro de devis : " . $quote->quote_number . "\n";
        $message .= "🍽️ Service : " . ($quote->service_type === 'restaurant' ? 'Privatisation du restaurant' : 'Privatisation de la remorque Block') . "\n";
        $message .= "📅 Date : " . date('d/m/Y', strtotime($quote->event_date)) . "\n";
        $message .= "👥 Convives : " . $quote->guest_count . " personnes\n";
        $message .= "⏰ Durée : " . $quote->event_duration . "H\n\n";
        
        if ($quote->postal_code) {
            $message .= "📍 Code postal : " . $quote->postal_code . "\n";
        }
        
        if ($quote->distance_km > 0) {
            $message .= "🚛 Distance : " . $quote->distance_km . " km\n";
        }
        
        $message .= "\n💰 Prix total estimé : " . number_format($quote->total_price, 2, ',', ' ') . " €\n\n";
        
        if (!empty($customer_data['message'])) {
            $message .= "💬 Votre message :\n" . $customer_data['message'] . "\n\n";
        }
        
        $message .= "Nous vous recontacterons dans les plus brefs délais pour finaliser votre réservation.\n\n";
        $message .= "Cordialement,\nL'équipe Block\n\n";
        $message .= "---\n";
        $message .= "Ceci est un email automatique, merci de ne pas y répondre.";
        
        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
            'From: Block <noreply@block-restaurant.fr>'
        ];
        
        $sent = wp_mail($to, $subject, $message, $headers);
        
        if ($sent) {
            // Mettre à jour la date d'envoi
            $wpdb->update(
                $table_name,
                ['sent_at' => current_time('mysql'), 'status' => 'sent'],
                ['id' => $quote_id]
            );
            
            if (class_exists('RestaurantBooking_Logger')) {
                RestaurantBooking_Logger::info("Email client envoyé pour le devis: {$quote->quote_number}", [
                    'quote_id' => $quote_id,
                    'email' => $to
                ]);
            }
        } else {
            if (class_exists('RestaurantBooking_Logger')) {
                RestaurantBooking_Logger::error("Échec envoi email client pour devis: {$quote->quote_number}", [
                    'quote_id' => $quote_id,
                    'email' => $to
                ]);
            }
        }
        
        return $sent;
    }

    /**
     * Créer un devis
     */
    private function create_quote($service_type, $form_data, $price_data)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'restaurant_quotes';

        // Générer un numéro de devis unique
        $quote_number = 'BLOCK-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // S'assurer que le numéro est unique
        while ($wpdb->get_var($wpdb->prepare("SELECT id FROM {$table_name} WHERE quote_number = %s", $quote_number))) {
            $quote_number = 'BLOCK-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }

        // Préparer les données client
        $customer_data = [
            'firstname' => sanitize_text_field($form_data['client_firstname']),
            'name' => sanitize_text_field($form_data['client_name']),
            'email' => sanitize_email($form_data['client_email']),
            'phone' => sanitize_text_field($form_data['client_phone']),
            'message' => sanitize_textarea_field($form_data['client_message'] ?? ''),
            'postal_code' => sanitize_text_field($form_data['postal_code'] ?? ''),
            'address' => sanitize_text_field($form_data['client_address'] ?? '')
        ];

        // Préparer les produits sélectionnés - extraction DYNAMIQUE depuis les clés du formulaire
        $selected_products = [
            'signature' => $this->extract_products_from_form_data($form_data, 'signature'),
            'accompaniments' => $this->extract_accompaniments_with_sauces($form_data),
            'buffets' => $this->extract_products_from_form_data($form_data, 'buffet'),
            'beverages' => $this->extract_products_from_form_data($form_data, 'beverage'),
            'options' => $this->extract_options_from_form_data($form_data),
            'games' => $this->extract_products_from_form_data($form_data, 'game'),
            'mini_boss' => $this->extract_products_from_form_data($form_data, 'mini_boss'),
            'other_products' => $this->extract_any_remaining_products($form_data)
        ];
        
        // Log pour diagnostiquer les données produits
        if (class_exists('RestaurantBooking_Logger')) {
            RestaurantBooking_Logger::debug("Données produits sauvegardées", [
                'selected_products' => $selected_products,
                'form_data_keys' => array_keys($form_data)
            ]);
        }

        $quote_data = [
            'quote_number' => $quote_number,
            'service_type' => $service_type,
            'event_date' => sanitize_text_field($form_data['event_date']),
            'event_duration' => intval($form_data['event_duration']),
            'guest_count' => intval($form_data['guest_count']),
            'postal_code' => sanitize_text_field($form_data['postal_code'] ?? ''),
            'distance_km' => intval($form_data['delivery_distance'] ?? 0),
            'customer_data' => json_encode($customer_data),
            'selected_products' => json_encode($selected_products),
            'price_breakdown' => json_encode($price_data),
            'base_price' => floatval($price_data['base_price'] ?? 0),
            'supplements_total' => floatval($price_data['supplements_total'] ?? 0),
            'products_total' => floatval($price_data['products_total'] ?? 0),
            'total_price' => floatval($price_data['total']),
            'status' => 'draft'
        ];

        $result = $wpdb->insert($table_name, $quote_data);

        if ($result && $wpdb->insert_id) {
            $new_quote_id = $wpdb->insert_id;
            
            // Vérification immédiate que le devis existe bien en base
            $verification = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM {$table_name} WHERE id = %d",
                $new_quote_id
            ));
            
            if (!$verification) {
                // Attendre un peu pour la synchronisation MySQL
                usleep(500000); // 0.5 seconde
                $verification = $wpdb->get_row($wpdb->prepare(
                    "SELECT id FROM {$table_name} WHERE id = %d",
                    $new_quote_id
                ));
                
                if (class_exists('RestaurantBooking_Logger')) {
                    RestaurantBooking_Logger::warning("Devis créé mais nécessite attente pour synchronisation", [
                        'quote_id' => $new_quote_id,
                        'verification_success' => !empty($verification)
                    ]);
                }
            }
            
            if ($verification) {
                // Log de la création du devis
                if (class_exists('RestaurantBooking_Logger')) {
                    RestaurantBooking_Logger::info("Devis créé: {$quote_number}", [
                        'quote_id' => $new_quote_id,
                        'service_type' => $service_type,
                        'client_email' => $customer_data['email']
                    ]);
                }
                
                return $new_quote_id;
            } else {
                if (class_exists('RestaurantBooking_Logger')) {
                    RestaurantBooking_Logger::error("Devis créé mais non accessible immédiatement", [
                        'quote_id' => $new_quote_id,
                        'quote_number' => $quote_number
                    ]);
                }
                return false;
            }
        }

        return false;
    }

    /**
     * Extraire les produits sélectionnés depuis les données du formulaire
     */
    private function extract_products_from_form_data($form_data, $prefix)
    {
        $products = [];
        
        foreach ($form_data as $key => $value) {
            $quantity = intval($value);
            if ($quantity <= 0) continue;
            
            // Pattern principal : prefix_ID_qty (ex: signature_11_qty)
            if (strpos($key, $prefix . '_') === 0 && strpos($key, '_qty') !== false) {
                $product_id = str_replace([$prefix . '_', '_qty'], '', $key);
                if (is_numeric($product_id)) {
                    $products[$product_id] = [
                        'id' => intval($product_id),
                        'quantity' => $quantity,
                        'key' => $key,
                        'type' => 'product'
                    ];
                }
            }
            
            // Pattern spécial pour les boissons avec tailles : beverage_size_ID_qty
            if ($prefix === 'beverage' && strpos($key, 'beverage_size_') === 0 && strpos($key, '_qty') !== false) {
                $size_id = str_replace(['beverage_size_', '_qty'], '', $key);
                if (is_numeric($size_id)) {
                    $products['size_' . $size_id] = [
                        'id' => intval($size_id),
                        'quantity' => $quantity,
                        'key' => $key,
                        'type' => 'beverage_size'
                    ];
                }
            }
            
            // Pattern pour les boissons simples : beverage_ID_qty
            if ($prefix === 'beverage' && strpos($key, 'beverage_') === 0 && strpos($key, '_qty') !== false && strpos($key, 'beverage_size_') === false) {
                $product_id = str_replace(['beverage_', '_qty'], '', $key);
                if (is_numeric($product_id)) {
                    $products[$product_id] = [
                        'id' => intval($product_id),
                        'quantity' => $quantity,
                        'key' => $key,
                        'type' => 'beverage'
                    ];
                }
            }
        }
        
        return $products;
    }

    /**
     * Extraire TOUTES les options de manière dynamique
     */
    private function extract_options_from_form_data($form_data)
    {
        $options = [];
        
        foreach ($form_data as $key => $value) {
            // Pattern 1: option_ANYTHING (services et options booléennes)
            if (strpos($key, 'option_') === 0 && !empty($value)) {
                $option_name = str_replace('option_', '', $key);
                $option_display_name = ucwords(str_replace('_', ' ', $option_name));
                
                $options[$option_name] = [
                    'enabled' => true,
                    'name' => $option_display_name,
                    'key' => $key,
                    'type' => 'service'
                ];
            }
            
            // Pattern 2: keg_ANYTHING_qty (tous les fûts dynamiquement)
            elseif (strpos($key, 'keg_') === 0 && strpos($key, '_qty') !== false) {
                $quantity = intval($value);
                if ($quantity > 0) {
                    $keg_info = str_replace(['keg_', '_qty'], '', $key);
                    $parts = explode('_', $keg_info);
                    
                    if (count($parts) >= 1) {
                        $product_id = $parts[0];
                        $size = isset($parts[1]) ? $parts[1] : 'standard';
                        
                        $options['kegs'][$key] = [
                            'product_id' => is_numeric($product_id) ? intval($product_id) : $product_id,
                            'size' => $size,
                            'quantity' => $quantity,
                            'key' => $key,
                            'name' => "Fût " . strtoupper($size) . " (Produit " . $product_id . ")",
                            'type' => 'keg'
                        ];
                    }
                }
            }
            
            // Pattern 3: game_ANYTHING (tous les jeux sélectionnés)
            elseif (strpos($key, 'game_') === 0 && $value == '1') {
                $game_id = str_replace('game_', '', $key);
                if (is_numeric($game_id)) {
                    $options['games'][$key] = [
                        'product_id' => intval($game_id),
                        'enabled' => true,
                        'key' => $key,
                        'name' => "Jeu " . $game_id,
                        'type' => 'game'
                    ];
                }
            }
            
            // Pattern 4: supplement_ANYTHING_qty (tous les suppléments)
            elseif (strpos($key, 'supplement_') === 0 && strpos($key, '_qty') !== false) {
                $quantity = intval($value);
                if ($quantity > 0) {
                    $supplement_name = str_replace(['supplement_', '_qty'], '', $key);
                    $supplement_display_name = ucwords(str_replace('_', ' ', $supplement_name));
                    
                    $options['supplements'][$key] = [
                        'name' => $supplement_display_name,
                        'quantity' => $quantity,
                        'key' => $key,
                        'type' => 'supplement'
                    ];
                }
            }
        }
        
        return $options;
    }

    /**
     * Extraire TOUS les accompagnements de manière dynamique
     */
    private function extract_accompaniments_with_sauces($form_data)
    {
        $accompaniments = [];
        
        // Extraire TOUS les éléments liés aux accompagnements de manière dynamique
        foreach ($form_data as $key => $value) {
            $quantity = intval($value);
            if ($quantity <= 0) continue;
            
            // Pattern 1: accompaniment_ID_qty (produits de base)
            if (strpos($key, 'accompaniment_') === 0 && strpos($key, '_qty') !== false) {
                $product_id = str_replace(['accompaniment_', '_qty'], '', $key);
                if (is_numeric($product_id)) {
                    $accompaniments[$product_id] = [
                        'id' => intval($product_id),
                        'quantity' => $quantity,
                        'key' => $key,
                        'type' => 'product'
                    ];
                }
            }
            
            // Pattern 2: sauce_ANYTHING_qty (toutes les sauces dynamiquement)
            elseif (strpos($key, 'sauce_') === 0 && strpos($key, '_qty') !== false) {
                $sauce_name = str_replace(['sauce_', '_qty'], '', $key);
                $sauce_display_name = ucwords(str_replace('_', ' ', $sauce_name));
                $accompaniments['sauce_' . $sauce_name] = [
                    'name' => $sauce_display_name,
                    'quantity' => $quantity,
                    'key' => $key,
                    'type' => 'sauce'
                ];
            }
            
            // Pattern 3: Toute autre option d'accompagnement (dynamique)
            elseif (strpos($key, '_qty') !== false && 
                    (strpos($key, 'enrobee_') === 0 || 
                     strpos($key, 'vinaigrette_') === 0 || 
                     strpos($key, 'croutons') !== false || 
                     strpos($key, 'herbes_') === 0 ||
                     strpos($key, 'supplement_') === 0)) {
                
                $option_name = str_replace('_qty', '', $key);
                $option_display_name = ucwords(str_replace('_', ' ', $option_name));
                $accompaniments['option_' . $option_name] = [
                    'name' => $option_display_name,
                    'quantity' => $quantity,
                    'key' => $key,
                    'type' => 'option'
                ];
            }
        }
        
        return $accompaniments;
    }

    /**
     * Extraire TOUT ce qui n'a pas encore été capturé (méthode de sécurité)
     */
    private function extract_any_remaining_products($form_data)
    {
        $remaining = [];
        
        // Listes des préfixes déjà traités
        $processed_prefixes = [
            'signature_', 'accompaniment_', 'buffet_', 'beverage_', 'game_', 'mini_boss_',
            'sauce_', 'option_', 'keg_', 'supplement_', 'enrobee_', 'vinaigrette_', 
            'croutons', 'herbes_', 'beverage_size_'
        ];
        
        // Clés système à ignorer
        $system_keys = [
            'service_type', 'event_date', 'guest_count', 'event_duration', 'postal_code',
            'delivery_distance', 'delivery_supplement', 'delivery_zone', 'signature_type',
            'buffet_type', 'client_firstname', 'client_name', 'client_email', 'client_phone',
            'client_message', 'client_address'
        ];
        
        foreach ($form_data as $key => $value) {
            $quantity = intval($value);
            if ($quantity <= 0) continue;
            
            // Ignorer les clés système
            if (in_array($key, $system_keys)) continue;
            
            // Vérifier si cette clé a déjà été traitée
            $already_processed = false;
            foreach ($processed_prefixes as $prefix) {
                if (strpos($key, $prefix) === 0) {
                    $already_processed = true;
                    break;
                }
            }
            
            // Si pas encore traitée et se termine par _qty, l'extraire
            if (!$already_processed && strpos($key, '_qty') !== false) {
                $product_name = str_replace('_qty', '', $key);
                $display_name = ucwords(str_replace('_', ' ', $product_name));
                
                $remaining[$key] = [
                    'name' => $display_name,
                    'quantity' => $quantity,
                    'key' => $key,
                    'type' => 'unknown_product'
                ];
            }
        }
        
        return $remaining;
    }


    // ❌ MÉTHODE SUPPRIMÉE : get_accompaniments_html_DEPRECATED - Remplacée par get_accompaniments_with_options_html()

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

        // Convertir les image_id en image_url pour les produits récupérés de la base
        foreach ($products as $product) {
            if (!empty($product->image_id)) {
                $product->image_url = wp_get_attachment_url($product->image_id);
            } else {
                $product->image_url = '';
            }
        }

        // Si pas de produits en base, ne rien afficher
        if (empty($products)) {
            return '<p class="rbf-v3-message info">Aucun menu Mini Boss disponible pour le moment.</p>';
        }

        $html = '<div class="rbf-v3-mini-boss-grid">';
        foreach ($products as $product) {
            $image_url = $product->image_url ? esc_url($product->image_url) : '';
            
            $html .= '<div class="rbf-v3-product-card-full">';
            
            // Afficher le bloc image dans tous les cas (comme pour les autres sections)
            $html .= '<div class="rbf-v3-product-image">';
            if ($image_url) {
                $html .= '<img src="' . $image_url . '" alt="' . esc_attr($product->name) . '" loading="lazy">';
            }
            $html .= '</div>';
            $html .= '<div class="rbf-v3-product-info">';
            $html .= '<h4 class="rbf-v3-product-title">' . esc_html($product->name) . '</h4>';
            if ($product->description) {
                $html .= '<p class="rbf-v3-product-description">' . esc_html($product->description) . '</p>';
            }
            $html .= '<div class="rbf-v3-product-price-qty">';
            $html .= '<span class="rbf-v3-product-price">' . number_format($product->price, 0) . ' €</span>';
            $html .= '<div class="rbf-v3-quantity-selector">';
            $html .= '<button type="button" class="rbf-v3-qty-btn rbf-v3-qty-minus" data-target="mini_boss_' . $product->id . '_qty">-</button>';
            $html .= '<input type="number" name="mini_boss_' . $product->id . '_qty" value="0" min="0" max="999" class="rbf-v3-qty-input">';
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
     * ✅ OPTIMISÉ : Obtenir le HTML des accompagnements avec options (évite les duplications)
     */
    private function get_accompaniments_with_options_html($guest_count, $form_data = [])
    {
        global $wpdb;
        
        // ✅ CORRECTION : Utiliser le préfixe dynamique au lieu de wp_ hard-codé
        $products_table = $wpdb->prefix . 'restaurant_products';
        $categories_table = $wpdb->prefix . 'restaurant_categories';
        $options_table = $wpdb->prefix . 'restaurant_accompaniment_options';
        $suboptions_table = $wpdb->prefix . 'restaurant_accompaniment_suboptions';
        
        // ✅ OPTIMISATION : Une seule requête avec JOIN pour récupérer tout
        $accompaniments = $wpdb->get_results($wpdb->prepare("
            SELECT DISTINCT 
                p.id, p.name, p.description, p.price, p.image_id, p.display_order, p.is_active,
                c.name as category_name,
                o.id as option_id, o.option_name, o.option_price, o.display_order as option_order,
                s.id as suboption_id, s.suboption_name, s.display_order as suboption_order
            FROM {$products_table} p 
            INNER JOIN {$categories_table} c ON p.category_id = c.id 
            LEFT JOIN {$options_table} o ON p.id = o.product_id AND o.is_active = 1
            LEFT JOIN {$suboptions_table} s ON o.id = s.option_id AND s.is_active = 1
            WHERE c.type = %s AND p.is_active = 1
            ORDER BY p.display_order ASC, p.name ASC, o.display_order ASC, o.option_name ASC, s.display_order ASC, s.suboption_name ASC
        ", 'accompagnement'));
        
        if (empty($accompaniments)) {
            return '<div class="rbf-v3-error">❌ Aucun accompagnement configuré dans la base de données.</div>';
        }
        
        // ✅ OPTIMISATION : Structurer les données pour éviter les duplications
        $structured_accompaniments = [];
        
        foreach ($accompaniments as $row) {
            $product_id = $row->id;
            
            // Créer le produit s'il n'existe pas encore
            if (!isset($structured_accompaniments[$product_id])) {
                $structured_accompaniments[$product_id] = (object) [
                    'id' => $row->id,
                    'name' => $row->name,
                    'description' => $row->description,
                    'price' => $row->price,
                    'image_id' => $row->image_id,
                    'display_order' => $row->display_order,
                    'is_active' => $row->is_active,
                    'category_name' => $row->category_name,
                    'options' => []
                ];
            }
            
            // Ajouter l'option s'il y en a une et qu'elle n'existe pas déjà
            if ($row->option_id && !isset($structured_accompaniments[$product_id]->options[$row->option_id])) {
                $structured_accompaniments[$product_id]->options[$row->option_id] = (object) [
                    'id' => $row->option_id,
                    'option_name' => $row->option_name,
                    'option_price' => $row->option_price,
                    'display_order' => $row->option_order,
                    'suboptions' => []
                ];
            }
            
            // Ajouter la sous-option s'il y en a une
            if ($row->suboption_id && $row->option_id) {
                $structured_accompaniments[$product_id]->options[$row->option_id]->suboptions[] = (object) [
                    'id' => $row->suboption_id,
                    'suboption_name' => $row->suboption_name,
                    'display_order' => $row->suboption_order
                ];
            }
        }
        
        // Générer le HTML
        $html = '<div class="rbf-v3-accompaniments-vertical-list">';
        
        foreach ($structured_accompaniments as $acc) {
            $html .= $this->generate_accompaniment_card_html($acc, $form_data);
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * ✅ NOUVEAU : Générer le HTML d'une card d'accompagnement
     */
    private function generate_accompaniment_card_html($accompaniment, $form_data = [])
    {
        $acc_id = $accompaniment->id;
        $acc_name = esc_html($accompaniment->name);
        $acc_description = esc_html($accompaniment->description ?? '');
        $acc_price = number_format($accompaniment->price, 2);
        
        // Récupérer la quantité sauvegardée
        $saved_quantity = intval($form_data["accompaniment_{$acc_id}_qty"] ?? 0);
        
        // Générer l'image si elle existe
        $image_html = '';
        if (!empty($accompaniment->image_id)) {
            $image_url = wp_get_attachment_url($accompaniment->image_id);
            if ($image_url) {
                $image_html = '<div class="rbf-v3-acc-image">
                    <img src="' . esc_url($image_url) . '" alt="' . esc_attr($acc_name) . '" loading="lazy">
                </div>';
            }
        }
        
        $html = '<div class="rbf-v3-accompaniment-card" data-id="' . $acc_id . '">';
        
        // En-tête de la card
        $html .= '<div class="rbf-v3-acc-header">';
        $html .= $image_html;
        $html .= '<div class="rbf-v3-acc-info">';
        $html .= '<h4 class="rbf-v3-acc-title">' . $acc_name . '</h4>';
        if ($acc_description) {
            $html .= '<p class="rbf-v3-acc-description">' . $acc_description . '</p>';
        }
        $html .= '<div class="rbf-v3-acc-price-qty">';
        $html .= '<span class="rbf-v3-acc-price">' . $acc_price . ' €</span>';
        
        // Sélecteur de quantité principal
        $html .= '<div class="rbf-v3-quantity-selector rbf-v3-qty-main">';
        $html .= '<button type="button" class="rbf-v3-qty-btn rbf-v3-qty-minus" data-target="accompaniment_' . $acc_id . '_qty">-</button>';
        $html .= '<input type="number" name="accompaniment_' . $acc_id . '_qty" value="' . $saved_quantity . '" min="0" max="999" class="rbf-v3-qty-input">';
        $html .= '<button type="button" class="rbf-v3-qty-btn rbf-v3-qty-plus" data-target="accompaniment_' . $acc_id . '_qty">+</button>';
        $html .= '</div>';
        
        $html .= '</div>'; // rbf-v3-acc-price-qty
        $html .= '</div>'; // rbf-v3-acc-info
        $html .= '</div>'; // rbf-v3-acc-header
        
        // Options (toujours visibles si elles existent)
        if (!empty($accompaniment->options)) {
            // ✅ CORRECTION : La quantité maximale des options doit être égale au nombre d'accompagnements
            $max_options = max($saved_quantity, 1); // Au minimum 1 pour permettre la sélection même si pas encore d'accompagnement
            
            $html .= '<div class="rbf-v3-acc-options" data-max-total="' . $max_options . '" data-acc-id="' . $acc_id . '">';
            $html .= '<h5 class="rbf-v3-options-title">Options :</h5>';
            
            foreach ($accompaniment->options as $option) {
                $html .= $this->generate_option_html($option, $acc_id, $form_data, $max_options);
            }
            
            $html .= '</div>';
        }
        
        $html .= '</div>'; // rbf-v3-accompaniment-card
        
        return $html;
    }
    
    /**
     * ✅ NOUVEAU : Générer le HTML d'une option
     */
    private function generate_option_html($option, $acc_id, $form_data = [], $max_total = 999)
    {
        $option_id = $option->id;
        $option_name = esc_html($option->option_name);
        $option_price = $option->option_price;
        $price_text = $option_price > 0 ? ' (+' . number_format($option_price, 2) . '€)' : '';
        
        $html = '<div class="rbf-v3-option-item" data-option-id="' . $option_id . '">';
        
        // Si l'option a des sous-options, les afficher
        if (!empty($option->suboptions)) {
            $html .= '<div class="rbf-v3-option-header">';
            $html .= '<span class="rbf-v3-option-name">' . $option_name . $price_text . '</span>';
            $html .= '</div>';
            
            $html .= '<div class="rbf-v3-suboptions">';
            foreach ($option->suboptions as $suboption) {
                $html .= $this->generate_suboption_html($suboption, $option_id, $form_data, $max_total);
            }
            $html .= '</div>';
        } else {
            // Option simple sans sous-options
            $field_name = $this->get_option_field_name($option_name);
            $saved_quantity = intval($form_data[$field_name] ?? 0);
            
            $html .= '<div class="rbf-v3-option-simple">';
            $html .= '<span class="rbf-v3-option-name">' . $option_name . $price_text . '</span>';
            $html .= '<div class="rbf-v3-quantity-selector rbf-v3-qty-small">';
            $html .= '<button type="button" class="rbf-v3-qty-btn rbf-v3-qty-minus" data-target="' . $field_name . '">-</button>';
            $html .= '<input type="number" name="' . $field_name . '" value="' . $saved_quantity . '" min="0" max="' . $max_total . '" class="rbf-v3-qty-input rbf-v3-option-input">';
            $html .= '<button type="button" class="rbf-v3-qty-btn rbf-v3-qty-plus" data-target="' . $field_name . '">+</button>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * ✅ NOUVEAU : Générer le HTML d'une sous-option
     */
    private function generate_suboption_html($suboption, $option_id, $form_data = [], $max_total = 999)
    {
        $suboption_name = esc_html($suboption->suboption_name);
        $field_name = $this->get_suboption_field_name($suboption_name);
        $saved_quantity = intval($form_data[$field_name] ?? 0);
        
        $html = '<div class="rbf-v3-suboption-item">';
        $html .= '<span class="rbf-v3-suboption-name">' . $suboption_name . '</span>';
        $html .= '<div class="rbf-v3-quantity-selector rbf-v3-qty-small">';
        $html .= '<button type="button" class="rbf-v3-qty-btn rbf-v3-qty-minus" data-target="' . $field_name . '">-</button>';
        $html .= '<input type="number" name="' . $field_name . '" value="' . $saved_quantity . '" min="0" max="' . $max_total . '" class="rbf-v3-qty-input rbf-v3-option-input">';
        $html .= '<button type="button" class="rbf-v3-qty-btn rbf-v3-qty-plus" data-target="' . $field_name . '">+</button>';
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * ✅ CORRECTION : Obtenir le nom de champ pour une option avec mapping explicite
     */
    private function get_option_field_name($option_name)
    {
        // Mapping explicite pour correspondre aux noms JavaScript
        $option_mapping = [
            'Enrobée sauce chimichurri' => 'enrobee_sauce_chimichurri_qty',
            'Choix de la sauce' => 'choix_de_la_sauce_qty'
        ];
        
        // Utiliser le mapping si disponible
        if (isset($option_mapping[$option_name])) {
            return $option_mapping[$option_name];
        }
        
        // Fallback vers la logique dynamique
        return $this->sanitize_field_name($option_name) . '_qty';
    }
    
    /**
     * ✅ CORRECTION : Obtenir le nom de champ pour une sous-option avec mapping explicite
     */
    private function get_suboption_field_name($suboption_name)
    {
        // Mapping explicite pour correspondre exactement aux noms JavaScript
        $suboption_mapping = [
            'Ketchup' => 'sauce_ketchup_qty',
            'Mayonnaise' => 'sauce_mayonnaise_qty',
            'Moutarde' => 'sauce_moutarde_qty',
            'Sauce BBQ' => 'sauce_sauce_bbq_qty'
        ];
        
        // Utiliser le mapping si disponible
        if (isset($suboption_mapping[$suboption_name])) {
            return $suboption_mapping[$suboption_name];
        }
        
        // Fallback vers la logique dynamique
        return 'sauce_' . $this->sanitize_field_name($suboption_name) . '_qty';
    }
    
    /**
     * ✅ NOUVEAU : Sanitizer un nom pour créer un nom de champ valide
     */
    private function sanitize_field_name($name)
    {
        // Convertir en minuscules et remplacer les caractères spéciaux
        $sanitized = strtolower($name);
        $sanitized = str_replace(['é', 'è', 'ê', 'ë'], 'e', $sanitized);
        $sanitized = str_replace(['à', 'á', 'â', 'ä'], 'a', $sanitized);
        $sanitized = str_replace(['ù', 'ú', 'û', 'ü'], 'u', $sanitized);
        $sanitized = str_replace(['ì', 'í', 'î', 'ï'], 'i', $sanitized);
        $sanitized = str_replace(['ò', 'ó', 'ô', 'ö'], 'o', $sanitized);
        $sanitized = str_replace(['ç'], 'c', $sanitized);
        
        // Remplacer les espaces et caractères spéciaux par des underscores
        $sanitized = preg_replace('/[^a-z0-9]/', '_', $sanitized);
        
        // Supprimer les underscores multiples et en début/fin
        $sanitized = preg_replace('/_+/', '_', $sanitized);
        $sanitized = trim($sanitized, '_');
        
        return $sanitized;
    }

    // ❌ MÉTHODE SUPPRIMÉE : get_accompaniments_simple_html_DEPRECATED - Remplacée par get_accompaniments_with_options_html()

    /**
     * Obtenir le HTML des produits signature
     */
    private function get_signature_products_html($signature_type, $guest_count, $form_data = [])
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'restaurant_products';
        $category = ($signature_type === 'DOG') ? 'plat_signature_dog' : 'plat_signature_croq';
        
        // Récupérer les produits via la table des catégories
        $products = $wpdb->get_results($wpdb->prepare(
            "SELECT p.* FROM {$table_name} p 
             INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id 
             WHERE c.type = %s AND p.is_active = 1 ORDER BY p.name ASC",
            $category
        ));

        // Convertir les image_id en image_url pour les produits récupérés de la base
        foreach ($products as $product) {
            if (!empty($product->image_id)) {
                $product->image_url = wp_get_attachment_url($product->image_id);
            } else {
                $product->image_url = '';
            }
        }

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
            // Afficher le bloc image dans tous les cas (comme pour Mini-Boss)
            $html .= '<div class="rbf-v3-product-image">';
            if ($image_url) {
                $html .= '<img src="' . $image_url . '" alt="' . esc_attr($product->name) . '" loading="lazy">';
            }
            $html .= '</div>';
            
            $html .= '<div class="rbf-v3-product-info">';
            $html .= '<h4 class="rbf-v3-product-title">' . esc_html($product->name) . '</h4>';
            if ($product->description) {
                $html .= '<p class="rbf-v3-product-description">' . esc_html($product->description) . '</p>';
            }
            $html .= '<div class="rbf-v3-product-price-qty">';
            $html .= '<span class="rbf-v3-product-price">' . number_format($product->price, 0) . ' €</span>';
            $html .= '<div class="rbf-v3-quantity-selector">';
            // Récupérer la quantité sauvegardée ou 0 par défaut
            $saved_quantity = intval($form_data['signature_' . $product->id . '_qty'] ?? 0);
            
            $html .= '<button type="button" class="rbf-v3-qty-btn rbf-v3-qty-minus" data-target="signature_' . $product->id . '_qty">-</button>';
            $html .= '<input type="number" name="signature_' . $product->id . '_qty" value="' . $saved_quantity . '" min="0" max="999" class="rbf-v3-qty-input">';
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
     * Obtenir le prix de base selon le type de service
     */
    private function get_base_price($service_type)
    {
        $setting_key = $service_type . '_base_price';
        $default_price = $service_type === 'restaurant' ? 300 : 350;
        
        return (float) RestaurantBooking_Settings::get($setting_key, $default_price);
    }

    /**
     * Options par défaut
     */
    private function get_default_options()
    {
        // Récupérer les options depuis les paramètres de la base de données
        return [
            'restaurant_min_guests' => (int) RestaurantBooking_Settings::get('restaurant_min_guests', 10),
            'restaurant_max_guests' => (int) RestaurantBooking_Settings::get('restaurant_max_guests', 30),
            'restaurant_min_duration' => (int) RestaurantBooking_Settings::get('restaurant_included_hours', 2),
            'restaurant_extra_hour_price' => (float) RestaurantBooking_Settings::get('hourly_supplement', 50),
            'restaurant_guests_text' => $this->build_restaurant_guests_text(),
            'restaurant_duration_text' => $this->build_restaurant_duration_text(),
            'restaurant_forfait_description' => RestaurantBooking_Settings::get('restaurant_forfait_description', 'Mise à disposition des murs de Block|Notre équipe salle + cuisine assurant la prestation|Présentation + mise en place buffets, selon vos choix|Mise à disposition vaisselle + verrerie|Entretien + nettoyage'),
            'remorque_min_guests' => (int) RestaurantBooking_Settings::get('remorque_min_guests', 20),
            'remorque_max_guests' => (int) RestaurantBooking_Settings::get('remorque_max_guests', 100),
            'remorque_min_duration' => (int) RestaurantBooking_Settings::get('remorque_included_hours', 2),
            'remorque_extra_hour_price' => (float) RestaurantBooking_Settings::get('hourly_supplement', 50),
            'remorque_staff_threshold' => 50, // Ce seuil est utilisé dans le calculateur, pas besoin de le changer
            'remorque_staff_supplement' => (float) RestaurantBooking_Settings::get('remorque_50_guests_supplement', 150),
            'remorque_duration_text' => $this->build_remorque_duration_text(),
            'max_distance_km' => (int) RestaurantBooking_Settings::get('remorque_max_delivery_distance', 150),
            'signature_dish_text' => RestaurantBooking_Settings::get('signature_dish_text', 'exactement 1 plat par personne'),
            'accompaniment_text' => RestaurantBooking_Settings::get('accompaniment_text', 'exactement 1/personne'),
            'comment_section_text' => RestaurantBooking_Settings::get('comment_section_text', '1 question, 1 souhait, n\'hésitez pas de nous en faire part...')
        ];
    }

    /**
     * Construire le texte des convives restaurant depuis les paramètres DB
     */
    private function build_restaurant_guests_text()
    {
        $min_guests = (int) RestaurantBooking_Settings::get('restaurant_min_guests', 10);
        $max_guests = (int) RestaurantBooking_Settings::get('restaurant_max_guests', 30);
        
        return sprintf('De %d à %d personnes', $min_guests, $max_guests);
    }

    /**
     * Construire le texte de durée restaurant depuis les paramètres DB
     */
    private function build_restaurant_duration_text()
    {
        $included_hours = (int) RestaurantBooking_Settings::get('restaurant_included_hours', 2);
        $max_hours = (int) RestaurantBooking_Settings::get('restaurant_max_hours', 4);
        $hourly_supplement = (float) RestaurantBooking_Settings::get('hourly_supplement', 50);
        
        return sprintf('min durée = %dH (compris) max durée = %dH (supplément de +%.0f €/TTC/H)', 
            $included_hours, $max_hours, $hourly_supplement);
    }

    /**
     * Construire le texte de durée remorque depuis les paramètres DB
     */
    private function build_remorque_duration_text()
    {
        $included_hours = (int) RestaurantBooking_Settings::get('remorque_included_hours', 2);
        $max_hours = (int) RestaurantBooking_Settings::get('remorque_max_hours', 5);
        $hourly_supplement = (float) RestaurantBooking_Settings::get('hourly_supplement', 50);
        
        return sprintf('min durée = %dH (compris) max durée = %dH (supplément de +%.0f €/TTC/H)', 
            $included_hours, $max_hours, $hourly_supplement);
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
                        <strong>Privatisation de la remorque Block</strong>
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
            "SELECT id FROM {$categories_table} WHERE type = %s AND is_active = 1",
            $category
        ));

        if (!$category_id) {
            // Logger l'erreur mais ne pas retourner de données fictives
            RestaurantBooking_Logger::warning('Catégorie non trouvée', array(
                'category_type' => $category,
                'action' => 'get_products_by_category'
            ));
            return array();
        }

        $table_name = $wpdb->prefix . 'restaurant_products';
        
        $products = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, c.name as category_name, c.type as category_type 
             FROM {$table_name} p
             LEFT JOIN {$categories_table} c ON p.category_id = c.id
             WHERE p.category_id = %d AND p.is_active = 1 AND c.is_active = 1
             ORDER BY p.display_order ASC, p.name ASC",
            $category_id
        ));
        
        // Nettoyer les échappements dans les résultats
        $products = RestaurantBooking_Text_Cleaner::clean_wpdb_results($products);

        // Si pas de produits en base, logger mais ne pas créer de données fictives
        if (empty($products)) {
            RestaurantBooking_Logger::info('Aucun produit trouvé pour la catégorie', array(
                'category_type' => $category,
                'category_id' => $category_id
            ));
            return array();
        }

        // Ajouter l'URL de l'image et enrichir les données pour chaque produit
        foreach ($products as &$product) {
            $product->image_url = $product->image_id ? wp_get_attachment_image_url($product->image_id, 'medium') : '';
            
            // Convertir les types pour assurer la cohérence
            $product->price = (float) $product->price;
            $product->has_supplement = (bool) $product->has_supplement;
            $product->has_accompaniment_options = (bool) ($product->has_accompaniment_options ?? 0);
            $product->has_multiple_sizes = (bool) ($product->has_multiple_sizes ?? 0);
        }

        return $products;
    }


    /**
     * Obtenir les disponibilités d'un mois avec créneaux horaires
     */
    public function get_month_availability()
    {
        try {
            // Vérification de sécurité
            if (!wp_verify_nonce($_POST['nonce'], 'rbf_v3_nonce')) {
                throw new Exception('Erreur de sécurité');
            }

            $year = intval($_POST['year']);
            $month = intval($_POST['month']);
            $service_type = sanitize_text_field($_POST['service_type']);

            // Validation
            if ($year < date('Y') || $year > date('Y') + 2) {
                throw new Exception('Année invalide');
            }
            if ($month < 1 || $month > 12) {
                throw new Exception('Mois invalide');
            }
            if (!in_array($service_type, ['restaurant', 'remorque', 'both'])) {
                $service_type = 'both';
            }

            // Récupérer les disponibilités via Google Calendar
            $google_calendar = RestaurantBooking_Google_Calendar::get_instance();
            $availability_data = $google_calendar->get_month_availability($year, $month, $service_type);

            // Générer le calendrier avec les jours du mois
            $first_day = sprintf('%04d-%02d-01', $year, $month);
            $last_day = date('Y-m-t', strtotime($first_day));
            $calendar_data = array();

            $current_date = $first_day;
            while ($current_date <= $last_day) {
                $day_data = array(
                    'date' => $current_date,
                    'day' => intval(date('d', strtotime($current_date))),
                    'is_past' => $current_date < date('Y-m-d'),
                    'is_available' => true,
                    'blocked_slots' => array(),
                    'display_text' => ''
                );

                if (isset($availability_data[$current_date])) {
                    $day_availability = $availability_data[$current_date];
                    
                    if ($day_availability['is_fully_blocked']) {
                        $day_data['is_available'] = false;
                        $day_data['display_text'] = 'Indisponible';
                    } else {
                        $day_data['blocked_slots'] = $day_availability['blocked_slots'];
                        // Créer un texte résumé des créneaux bloqués
                        $blocked_times = array();
                        foreach ($day_availability['blocked_slots'] as $slot) {
                            if ($slot['type'] === 'time_slot') {
                                $start = date('H\hi', strtotime($slot['start_time']));
                                $end = date('H\hi', strtotime($slot['end_time']));
                                $blocked_times[] = $start . '-' . $end;
                            }
                        }
                        if (!empty($blocked_times)) {
                            $day_data['display_text'] = 'Bloqué: ' . implode(', ', $blocked_times);
                        }
                    }
                }

                $calendar_data[] = $day_data;
                $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
            }

            wp_send_json_success(array(
                'calendar_data' => $calendar_data,
                'year' => $year,
                'month' => $month,
                'month_name' => date_i18n('F Y', strtotime($first_day)),
                'service_type' => $service_type
            ));

        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Récupérer les disponibilités pour le widget calendrier
     */
    public function get_availability()
    {
        try {
            // Vérifier le nonce
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'rbf_v3_form')) {
                throw new Exception('Erreur de sécurité');
            }

            $start_date = sanitize_text_field($_POST['start_date'] ?? '');
            $end_date = sanitize_text_field($_POST['end_date'] ?? '');
            $service_type = sanitize_text_field($_POST['service_type'] ?? 'restaurant');

            if (empty($start_date) || empty($end_date)) {
                throw new Exception('Dates manquantes');
            }

            // Utiliser la classe Google Calendar pour récupérer les disponibilités
            if (class_exists('RestaurantBooking_Google_Calendar')) {
                $google_calendar = RestaurantBooking_Google_Calendar::get_instance();
                $availability_data = $google_calendar->get_month_availability(
                    date('Y', strtotime($start_date)),
                    date('n', strtotime($start_date)),
                    $service_type
                );
            } else {
                // Fallback : récupération directe depuis la base de données
                global $wpdb;
                
                // Récupérer TOUS les événements bloqués selon le type de service demandé
                // Si service_type = 'all', on récupère tout
                // Sinon on récupère pour le service spécifique + 'both' + 'all'
                if ($service_type === 'all') {
                    $results = $wpdb->get_results($wpdb->prepare("
                        SELECT date, service_type, is_available, blocked_reason, notes, google_event_id,
                               start_time, end_time
                        FROM {$wpdb->prefix}restaurant_availability 
                        WHERE date BETWEEN %s AND %s
                        AND is_available = 0
                        ORDER BY date ASC, start_time ASC
                    ", $start_date, $end_date), ARRAY_A);
                } else {
                    $results = $wpdb->get_results($wpdb->prepare("
                        SELECT date, service_type, is_available, blocked_reason, notes, google_event_id,
                               start_time, end_time
                        FROM {$wpdb->prefix}restaurant_availability 
                        WHERE date BETWEEN %s AND %s
                        AND is_available = 0
                        AND (service_type = %s OR service_type = 'both' OR service_type = 'all')
                        ORDER BY date ASC, start_time ASC
                    ", $start_date, $end_date, $service_type), ARRAY_A);
                }
                
                $availability_data = array();
                
                foreach ($results as $row) {
                    $date = $row['date'];
                    
                    if (!isset($availability_data[$date])) {
                        $availability_data[$date] = array(
                            'is_fully_blocked' => false,
                            'blocked_slots' => array(),
                            'events' => array(),
                            'has_google_events' => false
                        );
                    }
                    
                    // Ajouter l'événement
                    $event_info = array(
                        'is_available' => $row['is_available'],
                        'blocked_reason' => $row['blocked_reason'],
                        'notes' => $row['notes'],
                        'google_event_id' => $row['google_event_id'] ?? '',
                        'start_time' => $row['start_time'],
                        'end_time' => $row['end_time'],
                        'service_type' => $row['service_type']
                    );
                    
                    $availability_data[$date]['events'][] = $event_info;
                    
                    // Marquer si c'est un événement Google Calendar
                    if (!empty($row['google_event_id'])) {
                        $availability_data[$date]['has_google_events'] = true;
                    }
                    
                    // Vérifier si la journée entière est bloquée
                    if ($row['is_available'] == 0 && empty($row['start_time']) && empty($row['end_time'])) {
                        $availability_data[$date]['is_fully_blocked'] = true;
                    } else if ($row['is_available'] == 0) {
                        // Créneau spécifique bloqué
                        $availability_data[$date]['blocked_slots'][] = array(
                            'type' => 'time_slot',
                            'start_time' => $row['start_time'],
                            'end_time' => $row['end_time'],
                            'reason' => $row['blocked_reason'],
                            'is_google_sync' => !empty($row['google_event_id'])
                        );
                    }
                }
            }

            wp_send_json_success($availability_data);

        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Calculer la distance de livraison avec Google Maps
     */
    public function calculate_distance()
    {
        try {
            if (!wp_verify_nonce($_POST['nonce'], 'restaurant_booking_form_v3')) {
                throw new Exception(__('Erreur de sécurité', 'restaurant-booking'));
            }

            $postal_code = sanitize_text_field($_POST['postal_code']);
            
            // Validation code postal
            if (!preg_match('/^\d{5}$/', $postal_code)) {
                throw new Exception(__('Code postal invalide', 'restaurant-booking'));
            }

            // Calculer la distance avec Google Maps ou méthode de secours
            $distance_result = $this->calculate_real_distance($postal_code);
            
            if (is_wp_error($distance_result)) {
                throw new Exception($distance_result->get_error_message());
            }
            
            $distance = $distance_result['distance_km'];
            
            // Log pour debug
            RestaurantBooking_Logger::info('Vérification distance limite', array(
                'distance_calculee' => $distance,
                'distance_max' => $this->options['max_distance_km'],
                'methode_calcul' => $distance_result['method'] ?? 'unknown'
            ));
            
            // Déterminer le supplément selon les zones
            $supplement = 0;
            $zone_name = '';
            $is_over_limit = false;
            
            if ($distance <= $this->options['free_radius_km']) {
                $supplement = 0;
                $zone_name = 'Zone locale';
            } elseif ($distance <= 50) {
                $supplement = $this->options['price_30_50km'];
                $zone_name = 'Zone 30-50km';
            } elseif ($distance <= 100) {
                $supplement = $this->options['price_50_100km'];
                $zone_name = 'Zone 50-100km';
            } elseif ($distance <= $this->options['max_distance_km']) {
                $supplement = $this->options['price_100_150km'];
                $zone_name = 'Zone 100-150km';
            } else {
                // Zone au-delà de la limite - pas de supplément car zone non couverte
                $supplement = 0;
                $zone_name = 'Zone non couverte';
                $is_over_limit = true;
            }

            $response_data = [
                'distance' => $distance,
                'supplement' => $supplement,
                'zone' => $zone_name,
                'duration' => $distance_result['duration_text'] ?? '',
                'method' => $distance_result['method'] ?? 'unknown'
            ];

            // Si la zone dépasse la limite, ajouter le message d'erreur
            if ($is_over_limit) {
                $response_data['over_limit_message'] = sprintf(__('Zone non couverte : l\'adresse de l\'événement dépasse %d km. Merci de nous contacter directement.', 'restaurant-booking'), $this->options['max_distance_km']);
                RestaurantBooking_Logger::warning('Distance limite dépassée', array(
                    'distance' => $distance,
                    'max_distance' => $this->options['max_distance_km']
                ));
            }

            wp_send_json_success($response_data);

        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Calculer la distance avec Google Maps API uniquement
     */
    private function calculate_real_distance($postal_code)
    {
        // Vérifier que Google Maps est disponible
        if (!class_exists('RestaurantBooking_Google_Maps_Service')) {
            return new WP_Error('google_maps_unavailable', 
                __('🚫 Service Google Maps non disponible. Veuillez contacter l\'administrateur.', 'restaurant-booking')
            );
        }
        
        $google_maps = RestaurantBooking_Google_Maps_Service::get_instance();
        $result = $google_maps->calculate_distance_from_restaurant($postal_code);
        
        if (!is_wp_error($result)) {
            $result['method'] = 'google_maps';
            return $result;
        }
        
        // Si Google Maps échoue, retourner une erreur claire
        RestaurantBooking_Logger::error('Google Maps API failed', array(
            'error' => $result->get_error_message(),
            'postal_code' => $postal_code
        ));
        
        // Message d'erreur adapté selon le type d'erreur
        $error_code = $result->get_error_code();
        $error_message = $result->get_error_message();
        
        if (in_array($error_code, ['no_api_key', 'api_request_failed', 'api_http_error'])) {
            return new WP_Error('google_maps_config_error', 
                __('🚫 Erreur de configuration Google Maps. Veuillez contacter le service client.', 'restaurant-booking')
            );
        } elseif (in_array($error_code, ['api_status_error', 'element_error'])) {
            return new WP_Error('google_maps_calculation_error', 
                __('🚫 Impossible de calculer la distance pour ce code postal. Veuillez vérifier le code postal ou contacter le service client.', 'restaurant-booking')
            );
        } else {
            return new WP_Error('google_maps_unknown_error', 
                __('🚫 Erreur temporaire du service de calcul de distance. Veuillez réessayer dans quelques minutes.', 'restaurant-booking')
            );
        }
    }

    /**
     * Générer les boutons de sous-catégories pour vins et bières
     */
    private function generate_beverage_subcategory_buttons($beverages, $tab_key)
    {
        global $wpdb;
        $subcategories = array();
        
        if ($tab_key === 'wines') {
            // CORRECTION : Récupérer les types de vins depuis wp_restaurant_wine_types
            $wine_types_table = $wpdb->prefix . 'restaurant_wine_types';
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$wine_types_table'");
            
            if ($table_exists) {
                // Récupérer tous les types de vins actifs avec le nombre de produits
                $wine_types = $wpdb->get_results("
                    SELECT wt.slug, wt.name, COUNT(p.id) as product_count
                    FROM $wine_types_table wt
                    LEFT JOIN {$wpdb->prefix}restaurant_products p ON p.wine_category = wt.slug AND p.is_active = 1
                    INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id AND c.id = 112 AND c.is_active = 1
                    WHERE wt.is_active = 1
                    GROUP BY wt.id
                    HAVING product_count > 0
                    ORDER BY wt.display_order ASC, wt.name ASC
                ", ARRAY_A);
                
                foreach ($wine_types as $wine_type) {
                    $subcategories[$wine_type['slug']] = array(
                        'label' => $wine_type['name'],
                        'count' => (int) $wine_type['product_count']
                    );
                }
            } else {
                // Fallback : Regrouper les vins par wine_category existant
                foreach ($beverages as $beverage) {
                    $wine_category = $beverage['wine_category'] ?: 'autre';
                    if (!isset($subcategories[$wine_category])) {
                        $subcategories[$wine_category] = array(
                            'label' => $this->get_wine_category_label($wine_category),
                            'count' => 0
                        );
                    }
                    $subcategories[$wine_category]['count']++;
                }
            }
        } elseif ($tab_key === 'beers') {
            // CORRECTION : Récupérer les types de bières depuis wp_restaurant_beer_types
            $beer_types_table = $wpdb->prefix . 'restaurant_beer_types';
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$beer_types_table'");
            
            if ($table_exists) {
                // Récupérer tous les types de bières actifs avec le nombre de produits
                $beer_types = $wpdb->get_results("
                    SELECT bt.slug, bt.name, COUNT(p.id) as product_count
                    FROM $beer_types_table bt
                    LEFT JOIN {$wpdb->prefix}restaurant_products p ON p.beer_category = bt.slug AND p.is_active = 1
                    INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id AND c.id = 109 AND c.is_active = 1
                    WHERE bt.is_active = 1
                    GROUP BY bt.id
                    HAVING product_count > 0
                    ORDER BY bt.display_order ASC, bt.name ASC
                ", ARRAY_A);
                
                foreach ($beer_types as $beer_type) {
                    $subcategories[$beer_type['slug']] = array(
                        'label' => $beer_type['name'],
                        'count' => (int) $beer_type['product_count']
                    );
                }
            } else {
                // Fallback : Regrouper les bières par beer_category existant
                foreach ($beverages as $beverage) {
                    $beer_category = $beverage['beer_category'] ?: 'autre';
                    if (!isset($subcategories[$beer_category])) {
                        $subcategories[$beer_category] = array(
                            'label' => $this->get_beer_category_label($beer_category),
                            'count' => 0
                        );
                    }
                    $subcategories[$beer_category]['count']++;
                }
            }
        }
        
        if (empty($subcategories)) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="rbf-v3-beverage-subcategory-tabs">
            <button type="button" class="rbf-v3-subcategory-btn active" data-filter="all">
                Tous les <?php echo $tab_key === 'wines' ? 'vins' : 'bières'; ?>
            </button>
            <?php foreach ($subcategories as $key => $data) : ?>
                <button type="button" class="rbf-v3-subcategory-btn" data-filter="<?php echo esc_attr($key); ?>">
                    <?php echo esc_html($data['label']); ?> (<?php echo $data['count']; ?>)
                </button>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Obtenir le libellé d'une catégorie de vin depuis wp_restaurant_wine_types
     */
    private function get_wine_category_label($wine_category)
    {
        global $wpdb;
        
        // CORRECTION : Récupérer le nom depuis la table wp_restaurant_wine_types
        $wine_types_table = $wpdb->prefix . 'restaurant_wine_types';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$wine_types_table'");
        
        if ($table_exists) {
            $wine_type = $wpdb->get_row($wpdb->prepare("
                SELECT name FROM $wine_types_table 
                WHERE slug = %s AND is_active = 1
            ", $wine_category));
            
            if ($wine_type) {
                return $wine_type->name;
            }
        }
        
        // Fallback vers les labels hardcodés si pas trouvé dans la nouvelle table
        switch (strtolower($wine_category)) {
            case 'blanc':
                return 'Vins Blancs';
            case 'rouge':
                return 'Vins Rouges';
            case 'rose':
                return 'Vins Rosés';
            case 'champagne':
                return 'Champagnes';
            case 'cremant':
                return 'Crémants';
            default:
                return ucfirst(str_replace(['-', '_'], ' ', $wine_category));
        }
    }
    
    /**
     * Obtenir le libellé d'une catégorie de bière depuis wp_restaurant_beer_types
     */
    private function get_beer_category_label($beer_category)
    {
        global $wpdb;
        
        // CORRECTION : Récupérer le nom depuis la table wp_restaurant_beer_types
        $beer_types_table = $wpdb->prefix . 'restaurant_beer_types';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$beer_types_table'");
        
        if ($table_exists) {
            $beer_type = $wpdb->get_row($wpdb->prepare("
                SELECT name FROM $beer_types_table 
                WHERE slug = %s AND is_active = 1
            ", $beer_category));
            
            if ($beer_type) {
                return $beer_type->name;
            }
        }
        
        // Fallback vers les labels hardcodés si pas trouvé dans la nouvelle table
        switch (strtolower($beer_category)) {
            case 'blonde':
                return 'Bières Blondes';
            case 'blanche':
                return 'Bières Blanches';
            case 'ipa':
                return 'IPA';
            case 'ambree':
                return 'Bières Ambrées';
            case 'brune':
                return 'Bières Brunes';
            case 'pils':
                return 'Pils';
            default:
                return 'Autres Bières';
        }
    }

    /**
     * ✅ CORRECTION : Obtenir les catégories de bières pour les fûts depuis wp_restaurant_beer_types
     */
    private function get_beer_categories_for_kegs()
    {
        global $wpdb;
        
        // CORRECTION : Récupérer depuis la nouvelle table wp_restaurant_beer_types en priorité
        $beer_types_table = $wpdb->prefix . 'restaurant_beer_types';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$beer_types_table'");
        
        $beer_categories = array();
        
        if ($table_exists) {
            // Utiliser la nouvelle table wp_restaurant_beer_types
            $types = $wpdb->get_results("
                SELECT bt.slug, bt.name, COUNT(p.id) as keg_count
                FROM $beer_types_table bt
                LEFT JOIN {$wpdb->prefix}restaurant_products p ON bt.slug = p.beer_category 
                    AND p.is_active = 1
                LEFT JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id 
                    AND c.id = 110
                WHERE bt.is_active = 1
                GROUP BY bt.id
                HAVING keg_count > 0
                ORDER BY bt.display_order ASC, bt.name ASC
            ");
            
            foreach ($types as $type) {
                $beer_categories[$type->slug] = $type->name;
            }
        }
        
        // Fallback : Récupérer depuis les produits fûts si pas de nouvelle table
        if (empty($beer_categories)) {
            $categories = $wpdb->get_results("
                SELECT DISTINCT p.beer_category
                FROM {$wpdb->prefix}restaurant_products p
                INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
                WHERE c.id = 110
                AND p.beer_category IS NOT NULL 
                AND p.beer_category != ''
                AND p.is_active = 1
                ORDER BY p.beer_category ASC
            ");
            
            if (!empty($categories)) {
                foreach ($categories as $category) {
                    $key = strtolower($category->beer_category);
                    $name = ucfirst($category->beer_category);
                    
                    // Mapper les noms courts vers les noms complets
                    switch ($key) {
                        case 'blonde':
                            $name = 'Blondes';
                            break;
                        case 'blanche':
                            $name = 'Blanches';
                            break;
                        case 'ipa':
                            $name = 'IPA';
                            break;
                        case 'ambree':
                            $name = 'Ambrées';
                            break;
                        case 'brune':
                            $name = 'Brunes';
                            break;
                        case 'pils':
                            $name = 'Pils';
                            break;
                    }
                    
                    $beer_categories[$key] = $name;
                }
            }
        }
        
        // Fallback vers les catégories par défaut si aucune catégorie trouvée
        if (empty($beer_categories)) {
            $beer_categories = array(
                'blonde' => 'Blondes',
                'blanche' => 'Blanches', 
                'ipa' => 'IPA',
                'ambree' => 'Ambrées'
            );
        }
        
        return $beer_categories;
    }

}

// Initialiser le gestionnaire AJAX
new RestaurantBooking_Ajax_Handler_V3();

