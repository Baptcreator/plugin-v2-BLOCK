/**
 * JavaScript public - Plugin Restaurant Block & Co
 * Gestion des formulaires selon le cahier des charges
 */

(function($) {
    'use strict';

    // Variables globales
    let RestaurantBookingPublic = {
        currentStep: 1,
        totalPrice: 0,
        priceBreakdown: {},
        selectedOptions: [],
        formData: {},
        
        // Configuration
        config: {
            ajaxUrl: restaurant_booking_ajax?.ajax_url || '/wp-admin/admin-ajax.php',
            nonce: restaurant_booking_ajax?.nonce || '',
            messages: restaurant_booking_ajax?.messages || {}
        },

        // Initialisation
        init: function() {
            this.bindEvents();
            this.initializeForms();
            console.log('🍽️ Restaurant Booking Plugin - Chargé selon le cahier des charges');
        },

        // Liaison des événements
        bindEvents: function() {
            // Navigation entre étapes
            $(document).on('click', '.restaurant-plugin-step-next', this.nextStep.bind(this));
            $(document).on('click', '.restaurant-plugin-step-prev', this.prevStep.bind(this));
            
            // Soumission des formulaires
            $(document).on('submit', '#restaurant-quote-form', this.handleRestaurantSubmission.bind(this));
            $(document).on('submit', '#remorque-quote-form', this.handleRemorqueSubmission.bind(this));
            
            // Calculs en temps réel
            $(document).on('change input', '.restaurant-plugin-form-input, .restaurant-plugin-form-select', this.handleFormChange.bind(this));
            
            // Code postal pour remorque
            $(document).on('input', '#postal_code', this.handlePostalCodeChange.bind(this));
            
            // Options remorque
            $(document).on('click', '.restaurant-plugin-option-card', this.handleOptionToggle.bind(this));
            
            // Calculateur de prix
            $(document).on('click', '.restaurant-plugin-price-detail', this.togglePriceDetail.bind(this));
            
            // Sauvegarde automatique en session
            $(document).on('change', '.restaurant-plugin-form input, .restaurant-plugin-form select, .restaurant-plugin-form textarea', this.saveFormData.bind(this));
        },

        // Initialiser les formulaires
        initializeForms: function() {
            // Restaurer les données de session
            this.restoreFormData();
            
            // Calculer le prix initial
            this.calculatePrice();
            
            // Valider les dates (pas de dates passées)
            this.initializeDateValidation();
            
            // Initialiser les tooltips et aides
            this.initializeHelpers();
        },

        // Navigation - Étape suivante
        nextStep: function(e) {
            e.preventDefault();
            
            if (this.validateCurrentStep()) {
                const maxSteps = this.getMaxSteps();
                if (this.currentStep < maxSteps) {
                    this.currentStep++;
                    this.showStep(this.currentStep);
                    this.updateStepIndicator();
                    this.calculatePrice();
                    this.scrollToTop();
                }
            }
        },

        // Navigation - Étape précédente
        prevStep: function(e) {
            e.preventDefault();
            
            if (this.currentStep > 1) {
                this.currentStep--;
                this.showStep(this.currentStep);
                this.updateStepIndicator();
                this.scrollToTop();
            }
        },

        // Afficher une étape
        showStep: function(step) {
            $('.restaurant-plugin-form-step').removeClass('active');
            $(`.restaurant-plugin-form-step[data-step="${step}"]`).addClass('active');
            $('#current_step').val(step);
            
            // Mettre à jour l'URL sans recharger
            if (history.pushState) {
                const url = new URL(window.location);
                url.searchParams.set('step', step);
                history.pushState(null, '', url);
            }
        },

        // Mettre à jour l'indicateur d'étapes
        updateStepIndicator: function() {
            $('.restaurant-plugin-step-indicator').each((index, el) => {
                const stepNumber = index + 1;
                $(el).removeClass('active completed');
                
                if (stepNumber === this.currentStep) {
                    $(el).addClass('active');
                } else if (stepNumber < this.currentStep) {
                    $(el).addClass('completed');
                }
            });
        },

        // Obtenir le nombre maximum d'étapes
        getMaxSteps: function() {
            const serviceType = $('input[name="service_type"]').val();
            return serviceType === 'remorque' ? 5 : 4;
        },

        // Validation de l'étape courante
        validateCurrentStep: function() {
            const currentStepEl = $(`.restaurant-plugin-form-step[data-step="${this.currentStep}"]`);
            const requiredFields = currentStepEl.find('[required]');
            let isValid = true;
            let firstInvalidField = null;

            // Vérifier les champs requis
            requiredFields.each(function() {
                const $field = $(this);
                const value = $field.val()?.trim();
                
                if (!value) {
                    $field.addClass('error');
                    isValid = false;
                    if (!firstInvalidField) {
                        firstInvalidField = $field;
                    }
                } else {
                    $field.removeClass('error');
                }
            });

            // Validations spécifiques par étape
            if (isValid && this.currentStep === 1) {
                isValid = this.validateStep1();
            }

            if (!isValid) {
                if (firstInvalidField) {
                    firstInvalidField.focus();
                }
                this.showMessage('Veuillez remplir tous les champs obligatoires.', 'error');
            }

            return isValid;
        },

        // Validation étape 1 (forfait de base)
        validateStep1: function() {
            const serviceType = $('input[name="service_type"]').val();
            const guestCount = parseInt($('#guest_count').val()) || 0;
            const eventDate = $('#event_date').val();
            const postalCode = $('#postal_code').val();

            // Validation nombre de convives
            const minGuests = serviceType === 'remorque' ? 20 : 10;
            const maxGuests = serviceType === 'remorque' ? 100 : 30;
            
            if (guestCount < minGuests || guestCount > maxGuests) {
                this.showMessage(`Le nombre de convives doit être entre ${minGuests} et ${maxGuests} personnes pour ce service.`, 'error');
                return false;
            }

            // Validation date (pas dans le passé)
            if (eventDate) {
                const selectedDate = new Date(eventDate);
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                
                if (selectedDate < tomorrow) {
                    this.showMessage('La date sélectionnée doit être au minimum demain.', 'error');
                    return false;
                }
            }

            // Validation code postal pour remorque
            if (serviceType === 'remorque' && postalCode) {
                if (!/^[0-9]{5}$/.test(postalCode)) {
                    this.showMessage('Le code postal doit contenir exactement 5 chiffres.', 'error');
                    return false;
                }
            }

            return true;
        },

        // Gestion changement de formulaire
        handleFormChange: function(e) {
            const $field = $(e.target);
            $field.removeClass('error');
            
            // Recalculer le prix
            clearTimeout(this.calculatePriceTimeout);
            this.calculatePriceTimeout = setTimeout(() => {
                this.calculatePrice();
            }, 300);
        },

        // Gestion changement code postal
        handlePostalCodeChange: function(e) {
            const postalCode = $(e.target).val();
            
            if (postalCode.length === 5 && /^[0-9]{5}$/.test(postalCode)) {
                this.calculateDeliveryDistance(postalCode);
            } else {
                $('#distance-info').hide();
            }
        },

        // Calculer la distance de livraison
        calculateDeliveryDistance: function(postalCode) {
            const $distanceInfo = $('#distance-info');
            
            $distanceInfo.removeClass('success error').addClass('loading')
                .html('📍 Calcul de la distance en cours...')
                .show();

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'calculate_delivery_distance',
                    postal_code: postalCode,
                    nonce: this.config.nonce
                },
                success: (response) => {
                    if (response.success) {
                        const data = response.data;
                        $distanceInfo.removeClass('loading').addClass('success')
                            .html(`✅ Distance : ${data.distance} km<br>Zone : ${data.zone.zone_name}<br>Frais de livraison : ${data.delivery_price.toFixed(2)} €`);
                        
                        // Mettre à jour les champs cachés
                        $('#calculated_distance').val(data.distance);
                        $('#delivery_zone_id').val(data.zone.id);
                        
                        // Recalculer le prix
                        this.calculatePrice();
                    } else {
                        $distanceInfo.removeClass('loading').addClass('error')
                            .html(`❌ ${response.data || 'Erreur de calcul de distance'}`);
                    }
                },
                error: () => {
                    $distanceInfo.removeClass('loading').addClass('error')
                        .html('❌ Erreur de connexion');
                }
            });
        },

        // Gestion options remorque
        handleOptionToggle: function(e) {
            e.preventDefault();
            const $card = $(e.currentTarget);
            const option = $card.data('option');
            const $status = $card.find('.restaurant-plugin-option-status');

            if ($card.hasClass('selected')) {
                // Désélectionner
                $card.removeClass('selected');
                $status.text('CHOISIR').css('color', 'var(--restaurant-primary)');
                this.selectedOptions = this.selectedOptions.filter(opt => opt !== option);
                
                // Masquer la sélection de fûts si tireuse désélectionnée
                if (option === 'tireuse') {
                    $('#barrels-selection').hide();
                }
            } else {
                // Sélectionner
                $card.addClass('selected');
                $status.text('SÉLECTIONNÉ').css('color', 'var(--restaurant-secondary)');
                this.selectedOptions.push(option);
                
                // Afficher la sélection de fûts si tireuse sélectionnée
                if (option === 'tireuse') {
                    $('#barrels-selection').show();
                }
            }

            this.calculatePrice();
        },

        // Calculer le prix en temps réel
        calculatePrice: function() {
            const serviceType = $('input[name="service_type"]').val();
            
            if (serviceType === 'remorque') {
                this.calculateRemorquePrice();
            } else {
                this.calculateRestaurantPrice();
            }
            
            this.updatePriceDisplay();
        },

        // Calcul prix restaurant
        calculateRestaurantPrice: function() {
            const duration = parseInt($('#event_duration').val()) || 2;
            const guestCount = parseInt($('#guest_count').val()) || 10;

            this.priceBreakdown = {
                base: 300.00,
                duration: (duration - 2) * 50.00,
                products: 0.00, // TODO: Calculer selon les produits sélectionnés
                beverages: 0.00 // TODO: Calculer selon les boissons sélectionnées
            };

            this.totalPrice = Object.values(this.priceBreakdown).reduce((sum, value) => sum + value, 0);
        },

        // Calcul prix remorque
        calculateRemorquePrice: function() {
            const duration = parseInt($('#event_duration').val()) || 2;
            const guestCount = parseInt($('#guest_count').val()) || 20;
            const distance = parseInt($('#calculated_distance').val()) || 0;

            // Prix de base
            let basePrice = 350.00;
            let durationSupplement = (duration - 2) * 50.00;
            let guestsSupplement = guestCount > 50 ? 150.00 : 0.00;
            let deliveryPrice = this.getDeliveryPriceByDistance(distance);
            let optionsPrice = this.calculateOptionsPrice();

            this.priceBreakdown = {
                base: basePrice,
                duration: durationSupplement,
                guests_supplement: guestsSupplement,
                delivery: deliveryPrice,
                products: 0.00, // TODO: Calculer selon les produits sélectionnés
                beverages: 0.00, // TODO: Calculer selon les boissons sélectionnées
                options: optionsPrice
            };

            this.totalPrice = Object.values(this.priceBreakdown).reduce((sum, value) => sum + value, 0);
        },

        // Obtenir le prix de livraison par distance
        getDeliveryPriceByDistance: function(distance) {
            if (distance <= 30) return 0.00;
            if (distance <= 60) return 50.00;
            if (distance <= 100) return 100.00;
            if (distance <= 150) return 150.00;
            return 0.00; // Hors zone
        },

        // Calculer le prix des options
        calculateOptionsPrice: function() {
            let total = 0;
            this.selectedOptions.forEach(option => {
                if (option === 'tireuse') total += 50.00;
                if (option === 'jeux') total += 70.00;
            });
            return total;
        },

        // Mettre à jour l'affichage du prix
        updatePriceDisplay: function() {
            $('#total-price').text(this.totalPrice.toFixed(2) + ' € TTC');
            this.updatePriceBreakdown();
            this.updateAdditionalCosts();
        },

        // Mettre à jour le détail des coûts
        updatePriceBreakdown: function() {
            const serviceType = $('input[name="service_type"]').val();
            const $breakdown = $('#price-breakdown');
            let html = '';

            if (serviceType === 'remorque') {
                html += `<div>Forfait de base remorque : ${this.priceBreakdown.base.toFixed(2)} €</div>`;
                if (this.priceBreakdown.duration > 0) {
                    html += `<div>Supplément durée : ${this.priceBreakdown.duration.toFixed(2)} €</div>`;
                }
                if (this.priceBreakdown.guests_supplement > 0) {
                    html += `<div>Supplément convives (+50) : ${this.priceBreakdown.guests_supplement.toFixed(2)} €</div>`;
                }
                if (this.priceBreakdown.delivery > 0) {
                    html += `<div>Frais livraison : ${this.priceBreakdown.delivery.toFixed(2)} €</div>`;
                }
                html += `<hr style="margin: 10px 0; border-color: rgba(255,255,255,0.3);">`;
                html += `<div style="font-weight: bold;">Sous-total forfait : ${(this.priceBreakdown.base + this.priceBreakdown.duration + this.priceBreakdown.guests_supplement + this.priceBreakdown.delivery).toFixed(2)} €</div>`;
                
                if (this.priceBreakdown.products > 0) {
                    html += `<div>Formules repas : ${this.priceBreakdown.products.toFixed(2)} €</div>`;
                }
                if (this.priceBreakdown.beverages > 0) {
                    html += `<div>Boissons : ${this.priceBreakdown.beverages.toFixed(2)} €</div>`;
                }
                if (this.priceBreakdown.options > 0) {
                    html += `<div>Options : ${this.priceBreakdown.options.toFixed(2)} €</div>`;
                }
            } else {
                html += `<div>Forfait de base restaurant : ${this.priceBreakdown.base.toFixed(2)} €</div>`;
                if (this.priceBreakdown.duration > 0) {
                    html += `<div>Supplément durée : ${this.priceBreakdown.duration.toFixed(2)} €</div>`;
                }
                if (this.priceBreakdown.products > 0) {
                    html += `<div>Formules repas : ${this.priceBreakdown.products.toFixed(2)} €</div>`;
                }
                if (this.priceBreakdown.beverages > 0) {
                    html += `<div>Boissons : ${this.priceBreakdown.beverages.toFixed(2)} €</div>`;
                }
            }

            html += `<hr style="margin: 10px 0; border-color: rgba(255,255,255,0.3);">`;
            html += `<div style="font-weight: bold; font-size: 14px;">TOTAL TTC : ${this.totalPrice.toFixed(2)} €</div>`;

            $breakdown.html(html);
        },

        // Mettre à jour les coûts additionnels
        updateAdditionalCosts: function() {
            const $additionalCosts = $('#additional-costs');
            let html = '';

            if (this.priceBreakdown.duration > 0) {
                html += `<p style="color: var(--restaurant-secondary); font-weight: bold;">+ ${this.priceBreakdown.duration.toFixed(2)} € (supplément durée)</p>`;
            }
            if (this.priceBreakdown.guests_supplement > 0) {
                html += `<p style="color: var(--restaurant-secondary); font-weight: bold;">+ ${this.priceBreakdown.guests_supplement.toFixed(2)} € (+ de 50 convives)</p>`;
            }
            if (this.priceBreakdown.delivery > 0) {
                html += `<p style="color: var(--restaurant-secondary); font-weight: bold;">+ ${this.priceBreakdown.delivery.toFixed(2)} € (frais livraison)</p>`;
            }

            $additionalCosts.html(html);
        },

        // Basculer l'affichage du détail des prix
        togglePriceDetail: function(e) {
            e.preventDefault();
            const $detail = $('#price-breakdown');
            const $toggle = $(e.target);
            
            if ($detail.is(':visible')) {
                $detail.slideUp();
                $toggle.text('Voir le détail ▼');
            } else {
                $detail.slideDown();
                $toggle.text('Masquer le détail ▲');
            }
        },

        // Soumission formulaire restaurant
        handleRestaurantSubmission: function(e) {
            e.preventDefault();
            
            if (!this.validateCurrentStep()) {
                return;
            }

            this.submitForm('submit_restaurant_quote', '#restaurant-quote-form');
        },

        // Soumission formulaire remorque
        handleRemorqueSubmission: function(e) {
            e.preventDefault();
            
            if (!this.validateCurrentStep()) {
                return;
            }

            this.submitForm('submit_remorque_quote', '#remorque-quote-form');
        },

        // Soumettre le formulaire
        submitForm: function(action, formSelector) {
            const $form = $(formSelector);
            const $submitBtn = $form.find('[type="submit"]');
            const originalText = $submitBtn.text();

            // État de chargement
            $submitBtn.prop('disabled', true).text('⏳ Envoi en cours...');
            $form.addClass('restaurant-plugin-loading');

            // Préparer les données
            const formData = $form.serialize() + `&action=${action}`;

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: formData,
                success: (response) => {
                    if (response.success) {
                        this.showMessage('✅ ' + (response.data.message || 'Devis envoyé avec succès !'), 'success');
                        this.clearFormData(); // Effacer les données de session
                        
                        // Optionnel : redirection ou affichage du numéro de devis
                        if (response.data.quote_id) {
                            this.showMessage(`📋 Numéro de devis : ${response.data.quote_id}`, 'info');
                        }
                    } else {
                        this.showMessage('❌ ' + (response.data || 'Une erreur est survenue.'), 'error');
                    }
                },
                error: () => {
                    this.showMessage('❌ Erreur de connexion. Veuillez réessayer.', 'error');
                },
                complete: () => {
                    $submitBtn.prop('disabled', false).text(originalText);
                    $form.removeClass('restaurant-plugin-loading');
                }
            });
        },

        // Afficher un message
        showMessage: function(message, type = 'info') {
            const $container = $('#form-messages');
            const messageHtml = `<div class="restaurant-plugin-message ${type}">${message}</div>`;
            
            $container.html(messageHtml).show();
            
            // Faire défiler vers le message
            $('html, body').animate({
                scrollTop: $container.offset().top - 100
            }, 300);
            
            // Masquer automatiquement après 5 secondes (sauf succès)
            if (type !== 'success') {
                setTimeout(() => {
                    $container.fadeOut();
                }, 5000);
            }
        },

        // Sauvegarder les données du formulaire en session
        saveFormData: function() {
            const formData = {};
            $('.restaurant-plugin-form input, .restaurant-plugin-form select, .restaurant-plugin-form textarea').each(function() {
                const $field = $(this);
                const name = $field.attr('name');
                if (name && name !== '_wpnonce') {
                    formData[name] = $field.val();
                }
            });
            
            sessionStorage.setItem('restaurant_booking_form_data', JSON.stringify(formData));
        },

        // Restaurer les données du formulaire depuis la session
        restoreFormData: function() {
            const savedData = sessionStorage.getItem('restaurant_booking_form_data');
            if (savedData) {
                try {
                    const formData = JSON.parse(savedData);
                    Object.keys(formData).forEach(name => {
                        const $field = $(`[name="${name}"]`);
                        if ($field.length && formData[name]) {
                            $field.val(formData[name]);
                        }
                    });
                } catch (e) {
                    console.log('Erreur lors de la restauration des données du formulaire:', e);
                }
            }
        },

        // Effacer les données du formulaire de la session
        clearFormData: function() {
            sessionStorage.removeItem('restaurant_booking_form_data');
        },

        // Initialiser la validation des dates
        initializeDateValidation: function() {
            const $dateField = $('#event_date');
            if ($dateField.length) {
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                const minDate = tomorrow.toISOString().split('T')[0];
                $dateField.attr('min', minDate);
            }
        },

        // Initialiser les aides et tooltips
        initializeHelpers: function() {
            // TODO: Ajouter des tooltips explicatifs
        },

        // Faire défiler vers le haut
        scrollToTop: function() {
            $('html, body').animate({
                scrollTop: $('.restaurant-plugin-form-container').offset().top - 50
            }, 300);
        }
    };

    // Fonctions globales pour compatibilité avec les boutons inline
    window.nextStep = function() {
        RestaurantBookingPublic.nextStep({ preventDefault: function() {} });
    };

    window.prevStep = function() {
        RestaurantBookingPublic.prevStep({ preventDefault: function() {} });
    };

    window.togglePriceDetail = function() {
        RestaurantBookingPublic.togglePriceDetail({ preventDefault: function() {}, target: $('.restaurant-plugin-price-detail')[0] });
    };

    window.toggleOption = function(option) {
        const $card = $(`.restaurant-plugin-option-card[data-option="${option}"]`);
        RestaurantBookingPublic.handleOptionToggle({ preventDefault: function() {}, currentTarget: $card[0] });
    };

    // Initialisation au chargement du DOM
    $(document).ready(function() {
        RestaurantBookingPublic.init();
    });

})(jQuery);
