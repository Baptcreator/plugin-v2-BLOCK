<?php
/**
 * Classe des formulaires de devis
 *
 * @package RestaurantBooking
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Quote_Form
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
        add_action('wp_ajax_submit_quote_form', array($this, 'handle_form_submission'));
        add_action('wp_ajax_nopriv_submit_quote_form', array($this, 'handle_form_submission'));
        add_shortcode('restaurant_booking_form', array($this, 'render_shortcode'));
    }

    /**
     * Afficher le formulaire de devis
     */
    public function render_form($settings = array())
    {
        // Paramètres par défaut
        $default_settings = array(
            'title' => __('Demande de devis', 'restaurant-booking'),
            'subtitle' => __('Obtenez votre devis personnalisé en quelques minutes', 'restaurant-booking'),
            'show_title' => true,
            'show_subtitle' => true,
            'form_style' => 'default',
            'button_text' => __('Demander un devis', 'restaurant-booking'),
            'success_message' => __('Votre demande a été envoyée avec succès !', 'restaurant-booking')
        );

        $settings = wp_parse_args($settings, $default_settings);

        ob_start();
        ?>
        <div class="restaurant-booking-form-container">
            <?php if ($settings['show_title'] && !empty($settings['title'])): ?>
                <h2 class="form-title"><?php echo esc_html($settings['title']); ?></h2>
            <?php endif; ?>

            <?php if ($settings['show_subtitle'] && !empty($settings['subtitle'])): ?>
                <p class="form-subtitle"><?php echo esc_html($settings['subtitle']); ?></p>
            <?php endif; ?>

            <form id="restaurant-booking-quote-form" class="restaurant-booking-form" method="post">
                <?php wp_nonce_field('restaurant_booking_quote', '_wpnonce'); ?>
                
                <!-- Informations client -->
                <div class="form-section">
                    <h3 class="section-title">
                        <span class="section-number">1</span>
                        <?php _e('Vos informations', 'restaurant-booking'); ?>
                    </h3>
                    
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="client_name"><?php _e('Nom complet', 'restaurant-booking'); ?> <span class="required">*</span></label>
                            <input type="text" id="client_name" name="client_name" required>
                        </div>
                        <div class="form-group half">
                            <label for="client_email"><?php _e('Email', 'restaurant-booking'); ?> <span class="required">*</span></label>
                            <input type="email" id="client_email" name="client_email" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group half">
                            <label for="client_phone"><?php _e('Téléphone', 'restaurant-booking'); ?> <span class="required">*</span></label>
                            <input type="tel" id="client_phone" name="client_phone" required>
                        </div>
                        <div class="form-group half">
                            <label for="client_company"><?php _e('Entreprise (optionnel)', 'restaurant-booking'); ?></label>
                            <input type="text" id="client_company" name="client_company">
                        </div>
                    </div>
                </div>

                <!-- Type de service -->
                <div class="form-section">
                    <h3 class="section-title">
                        <span class="section-number">2</span>
                        <?php _e('Type de service', 'restaurant-booking'); ?>
                    </h3>
                    
                    <div class="service-type-selector">
                        <label class="service-option">
                            <input type="radio" name="service_type" value="restaurant" checked>
                            <div class="service-card">
                                <div class="service-icon">🍽️</div>
                                <h4><?php _e('Restaurant', 'restaurant-booking'); ?></h4>
                                <p><?php _e('Repas sur place dans notre établissement', 'restaurant-booking'); ?></p>
                            </div>
                        </label>

                        <label class="service-option">
                            <input type="radio" name="service_type" value="remorque">
                            <div class="service-card">
                                <div class="service-icon">🚚</div>
                                <h4><?php _e('Remorque', 'restaurant-booking'); ?></h4>
                                <p><?php _e('Service de restauration mobile avec notre remorque', 'restaurant-booking'); ?></p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Détails de l'événement -->
                <div class="form-section">
                    <h3 class="section-title">
                        <span class="section-number">3</span>
                        <?php _e('Détails de votre demande', 'restaurant-booking'); ?>
                    </h3>

                    <div class="form-row">
                        <div class="form-group half">
                            <label for="event_date"><?php _e('Date souhaitée', 'restaurant-booking'); ?> <span class="required">*</span></label>
                            <input type="date" id="event_date" name="event_date" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                        </div>
                        <div class="form-group half">
                            <label for="event_time"><?php _e('Heure', 'restaurant-booking'); ?></label>
                            <input type="time" id="event_time" name="event_time">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group half">
                            <label for="guest_count"><?php _e('Nombre de personnes', 'restaurant-booking'); ?> <span class="required">*</span></label>
                            <select id="guest_count" name="guest_count" required>
                                <option value=""><?php _e('Sélectionner...', 'restaurant-booking'); ?></option>
                                <option value="1-5">1-5 personnes</option>
                                <option value="6-10">6-10 personnes</option>
                                <option value="11-20">11-20 personnes</option>
                                <option value="21-50">21-50 personnes</option>
                                <option value="51-100">51-100 personnes</option>
                                <option value="100+">Plus de 100 personnes</option>
                            </select>
                        </div>
                        <div class="form-group half">
                            <label for="budget_range"><?php _e('Budget approximatif', 'restaurant-booking'); ?></label>
                            <select id="budget_range" name="budget_range">
                                <option value=""><?php _e('Non défini', 'restaurant-booking'); ?></option>
                                <option value="0-500">0 - 500 €</option>
                                <option value="500-1000">500 - 1 000 €</option>
                                <option value="1000-2000">1 000 - 2 000 €</option>
                                <option value="2000-5000">2 000 - 5 000 €</option>
                                <option value="5000+">Plus de 5 000 €</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="special_requirements"><?php _e('Demandes spéciales', 'restaurant-booking'); ?></label>
                        <textarea id="special_requirements" name="special_requirements" rows="4" placeholder="<?php _e('Allergies, régimes spéciaux, demandes particulières...', 'restaurant-booking'); ?>"></textarea>
                    </div>
                </div>

                <!-- Bouton de soumission -->
                <div class="form-actions">
                    <button type="submit" class="submit-button" id="submit-quote-btn">
                        <span class="button-text"><?php echo esc_html($settings['button_text']); ?></span>
                        <span class="button-loader" style="display: none;">⏳ <?php _e('Envoi en cours...', 'restaurant-booking'); ?></span>
                    </button>
                </div>

                <!-- Messages -->
                <div id="form-messages" class="form-messages" style="display: none;">
                    <div class="success-message" style="display: none;">
                        ✅ <span class="message-text"><?php echo esc_html($settings['success_message']); ?></span>
                    </div>
                    <div class="error-message" style="display: none;">
                        ❌ <span class="message-text"></span>
                    </div>
                </div>
            </form>
        </div>

        <!-- Styles CSS intégrés -->
        <style>
        .restaurant-booking-form-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .form-title {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 28px;
            font-weight: 700;
        }
        .form-subtitle {
            text-align: center;
            color: #7f8c8d;
            margin-bottom: 30px;
            font-size: 16px;
        }
        .form-section {
            margin-bottom: 40px;
            border-bottom: 1px solid #ecf0f1;
            padding-bottom: 30px;
        }
        .section-title {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            color: #2c3e50;
            font-size: 20px;
            font-weight: 600;
        }
        .section-number {
            background: #3498db;
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: bold;
        }
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            flex: 1;
        }
        .form-group.half {
            flex: 0 0 calc(50% - 10px);
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        .required {
            color: #e74c3c;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #fff;
            box-sizing: border-box;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        .service-type-selector {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .service-option {
            cursor: pointer;
        }
        .service-option input[type="radio"] {
            display: none;
        }
        .service-card {
            padding: 25px;
            border: 2px solid #ecf0f1;
            border-radius: 12px;
            text-align: center;
            transition: all 0.3s ease;
            background: #fff;
        }
        .service-option input[type="radio"]:checked + .service-card {
            border-color: #3498db;
            background: #f8f9fa;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.2);
        }
        .service-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }
        .service-card h4 {
            margin: 0 0 10px 0;
            color: #2c3e50;
            font-size: 18px;
            font-weight: 600;
        }
        .service-card p {
            margin: 0;
            color: #7f8c8d;
            font-size: 14px;
            line-height: 1.4;
        }
        .form-actions {
            text-align: center;
            margin-top: 40px;
        }
        .submit-button {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 50px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 200px;
        }
        .submit-button:hover {
            background: linear-gradient(135deg, #2980b9, #21618c);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.3);
        }
        .form-messages {
            margin-top: 20px;
        }
        .success-message,
        .error-message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .success-message {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        @media (max-width: 768px) {
            .restaurant-booking-form-container {
                padding: 20px;
                margin: 10px;
            }
            .form-row {
                flex-direction: column;
                gap: 10px;
            }
            .service-type-selector {
                grid-template-columns: 1fr;
            }
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            $('#restaurant-booking-quote-form').on('submit', function(e) {
                e.preventDefault();
                
                var $form = $(this);
                var $submitBtn = $('#submit-quote-btn');
                var $messages = $('#form-messages');
                
                $submitBtn.prop('disabled', true);
                $submitBtn.find('.button-text').hide();
                $submitBtn.find('.button-loader').show();
                
                $messages.hide();
                $('.success-message, .error-message').hide();
                
                var formData = $form.serialize() + '&action=submit_quote_form';
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            $('.success-message').show();
                            $messages.show();
                            $form[0].reset();
                        } else {
                            $('.error-message .message-text').text(response.data || 'Une erreur est survenue.');
                            $('.error-message').show();
                            $messages.show();
                        }
                    },
                    error: function() {
                        $('.error-message .message-text').text('Erreur de connexion.');
                        $('.error-message').show();
                        $messages.show();
                    },
                    complete: function() {
                        $submitBtn.prop('disabled', false);
                        $submitBtn.find('.button-text').show();
                        $submitBtn.find('.button-loader').hide();
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Gérer la soumission du formulaire
     */
    public function handle_form_submission()
    {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'restaurant_booking_quote')) {
            wp_send_json_error(__('Token de sécurité invalide.', 'restaurant-booking'));
        }

        $required_fields = array('client_name', 'client_email', 'client_phone', 'service_type', 'event_date', 'guest_count');
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(sprintf(__('Le champ %s est requis.', 'restaurant-booking'), $field));
            }
        }

        if (!is_email($_POST['client_email'])) {
            wp_send_json_error(__('Adresse email invalide.', 'restaurant-booking'));
        }

        // Simuler une sauvegarde réussie
        $quote_id = rand(1000, 9999);

        wp_send_json_success(array(
            'message' => __('Votre demande a été envoyée avec succès !', 'restaurant-booking'),
            'quote_id' => $quote_id
        ));
    }

    /**
     * Shortcode pour afficher le formulaire
     */
    public function render_shortcode($atts)
    {
        $settings = shortcode_atts(array(
            'title' => __('Demande de devis', 'restaurant-booking'),
            'subtitle' => __('Obtenez votre devis personnalisé', 'restaurant-booking'),
            'show_title' => 'true',
            'show_subtitle' => 'true',
            'button_text' => __('Demander un devis', 'restaurant-booking')
        ), $atts);

        $settings['show_title'] = ($settings['show_title'] === 'true');
        $settings['show_subtitle'] = ($settings['show_subtitle'] === 'true');

        return $this->render_form($settings);
    }
}
