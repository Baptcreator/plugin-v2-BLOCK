/**
 * Script JavaScript pour le formulaire de devis unifié v2
 *
 * @package RestaurantBooking
 * @since 2.0.0
 */

(function($) {
    'use strict';

    /**
     * Classe principale du formulaire de devis unifié
     */
    class RestaurantBookingQuoteFormUnified {
        constructor(container) {
            this.container = $(container);
            this.widgetId = this.container.attr('id');
            this.config = this.container.data('config') || {};
            this.currentStep = 0;
            this.selectedService = null;
            this.formData = {};
            this.priceCalculator = null;
            
            // Récupérer les données JSON
            this.data = this.getWidgetData();
            
            this.init();
        }

        /**
         * Initialisation
         */
        init() {
            this.bindEvents();
            this.initializePriceCalculator();
            
            // Masquer la barre de progression initialement
            this.container.find('.rb-progress-bar').hide();
            
            console.log('RestaurantBooking Quote Form Unified initialized', this.widgetId);
        }

        /**
         * Récupérer les données du widget
         */
        getWidgetData() {
            const dataScript = document.getElementById(this.widgetId + '-data');
            if (dataScript) {
                try {
                    return JSON.parse(dataScript.textContent);
                } catch (e) {
                    console.error('Error parsing widget data:', e);
                }
            }
            return {};
        }

        /**
         * Lier les événements
         */
        bindEvents() {
            // Sélection du service
            this.container.on('click', '.rb-select-service', (e) => {
                e.preventDefault();
                const service = $(e.currentTarget).data('service');
                this.selectService(service);
            });

            // Navigation
            this.container.on('click', '.rb-btn-prev', (e) => {
                e.preventDefault();
                this.previousStep();
            });

            this.container.on('click', '.rb-btn-next', (e) => {
                e.preventDefault();
                this.nextStep();
            });

            this.container.on('click', '.rb-btn-submit', (e) => {
                e.preventDefault();
                this.submitForm();
            });

            // Validation en temps réel
            this.container.on('change input', '.rb-form-field', (e) => {
                this.validateField($(e.target));
                this.updatePrice();
            });

            // Sélection de produits
            this.container.on('change', '.rb-product-quantity', (e) => {
                this.updateProductSelection($(e.target));
            });

            // Gestion des suppléments
            this.container.on('change', '.rb-supplement-quantity', (e) => {
                this.updateSupplementSelection($(e.target));
            });
        }

        /**
         * Sélectionner un service
         */
        selectService(service) {
            this.selectedService = service;
            this.formData.service_type = service;
            
            // Marquer la card comme sélectionnée
            this.container.find('.rb-service-card').removeClass('selected');
            this.container.find(`.rb-service-card[data-service="${service}"]`).addClass('selected');
            
            // Charger la première étape du service sélectionné
            this.loadServiceStep(service, 1);
        }

        /**
         * Charger une étape spécifique du service
         */
        loadServiceStep(service, stepNumber) {
            this.showLoading();
            
            const data = {
                action: 'load_quote_form_step',
                nonce: this.data.nonce,
                service_type: service,
                step: stepNumber,
                form_data: this.formData
            };

            $.ajax({
                url: this.data.ajax_url,
                type: 'POST',
                data: data,
                success: (response) => {
                    this.hideLoading();
                    
                    if (response.success) {
                        this.renderStep(response.data);
                        this.goToStep(stepNumber);
                    } else {
                        this.showError(response.data || 'Erreur lors du chargement de l\'étape');
                    }
                },
                error: () => {
                    this.hideLoading();
                    this.showError(this.data.texts.error_network);
                }
            });
        }

        /**
         * Rendu d'une étape
         */
        renderStep(stepData) {
            const stepHtml = this.buildStepHtml(stepData);
            
            // Ajouter l'étape au conteneur dynamique
            const dynamicContainer = this.container.find('.rb-dynamic-steps');
            dynamicContainer.append(stepHtml);
            
            // Mettre à jour la barre de progression
            this.updateProgressBar(stepData.step_number, stepData.total_steps);
            
            // Initialiser les éléments spéciaux de l'étape
            this.initializeStepElements(stepData);
        }

        /**
         * Construire le HTML d'une étape
         */
        buildStepHtml(stepData) {
            let html = `<div class="rb-form-step" data-step="${stepData.step_number}">`;
            
            // En-tête de l'étape
            html += `<div class="rb-step-header">`;
            html += `<h3 class="rb-step-title">${stepData.title}</h3>`;
            if (stepData.subtitle) {
                html += `<p class="rb-step-subtitle">${stepData.subtitle}</p>`;
            }
            html += `</div>`;
            
            // Contenu de l'étape
            html += `<div class="rb-step-content">`;
            html += stepData.content;
            html += `</div>`;
            
            html += `</div>`;
            
            return html;
        }

        /**
         * Aller à une étape spécifique
         */
        goToStep(stepNumber) {
            // Masquer toutes les étapes
            this.container.find('.rb-form-step').removeClass('active');
            
            // Afficher l'étape demandée
            this.container.find(`.rb-form-step[data-step="${stepNumber}"]`).addClass('active');
            
            this.currentStep = stepNumber;
            
            // Afficher la barre de progression et la navigation
            if (stepNumber > 0) {
                this.container.find('.rb-progress-bar').show();
                this.container.find('.rb-form-navigation').show();
                
                // Afficher/masquer le calculateur de prix
                if (stepNumber >= 2) {
                    this.container.find('.rb-price-calculator').show();
                }
            }
            
            // Mettre à jour les boutons de navigation
            this.updateNavigationButtons();
            
            // Scroll vers le haut du formulaire
            this.scrollToTop();
        }

        /**
         * Étape suivante
         */
        nextStep() {
            if (this.validateCurrentStep()) {
                const nextStepNumber = this.currentStep + 1;
                
                // Vérifier si l'étape suivante existe déjà
                const nextStepExists = this.container.find(`.rb-form-step[data-step="${nextStepNumber}"]`).length > 0;
                
                if (nextStepExists) {
                    this.goToStep(nextStepNumber);
                } else {
                    // Charger l'étape suivante
                    this.loadServiceStep(this.selectedService, nextStepNumber);
                }
            }
        }

        /**
         * Étape précédente
         */
        previousStep() {
            if (this.currentStep > 0) {
                this.goToStep(this.currentStep - 1);
            }
        }

        /**
         * Valider l'étape actuelle
         */
        validateCurrentStep() {
            const currentStepElement = this.container.find(`.rb-form-step[data-step="${this.currentStep}"]`);
            let isValid = true;
            
            // Valider tous les champs obligatoires
            currentStepElement.find('.rb-form-field[required]').each((index, field) => {
                if (!this.validateField($(field))) {
                    isValid = false;
                }
            });
            
            // Validations spécifiques par étape
            if (this.currentStep === 2) {
                // Validation des sélections de produits
                isValid = this.validateProductSelections() && isValid;
            }
            
            return isValid;
        }

        /**
         * Valider un champ
         */
        validateField($field) {
            const value = $field.val();
            const fieldName = $field.attr('name');
            let isValid = true;
            let errorMessage = '';
            
            // Supprimer les erreurs précédentes
            $field.removeClass('error');
            $field.siblings('.rb-field-error').remove();
            
            // Validation des champs obligatoires
            if ($field.attr('required') && !value) {
                isValid = false;
                errorMessage = this.data.texts.error_required;
            }
            
            // Validations spécifiques
            switch (fieldName) {
                case 'event_date':
                    if (value && !this.isValidDate(value)) {
                        isValid = false;
                        errorMessage = this.data.texts.error_invalid_date;
                    }
                    break;
                    
                case 'guest_count':
                    const guestCount = parseInt(value);
                    const minGuests = this.getMinGuests();
                    const maxGuests = this.getMaxGuests();
                    
                    if (guestCount < minGuests) {
                        isValid = false;
                        errorMessage = this.data.texts.error_min_guests + ` (${minGuests})`;
                    } else if (guestCount > maxGuests) {
                        isValid = false;
                        errorMessage = this.data.texts.error_max_guests + ` (${maxGuests})`;
                    }
                    break;
                    
                case 'postal_code':
                    if (value && !this.isValidPostalCode(value)) {
                        isValid = false;
                        errorMessage = 'Code postal invalide';
                    }
                    break;
            }
            
            // Afficher l'erreur si nécessaire
            if (!isValid) {
                $field.addClass('error');
                $field.after(`<div class="rb-field-error">${errorMessage}</div>`);
            } else {
                // Sauvegarder la valeur valide
                this.formData[fieldName] = value;
            }
            
            return isValid;
        }

        /**
         * Valider les sélections de produits
         */
        validateProductSelections() {
            // Cette méthode sera implémentée selon les règles métier spécifiques
            return true;
        }

        /**
         * Mettre à jour la sélection de produits
         */
        updateProductSelection($quantityField) {
            const productId = $quantityField.data('product-id');
            const quantity = parseInt($quantityField.val()) || 0;
            
            if (!this.formData.selected_products) {
                this.formData.selected_products = {};
            }
            
            if (quantity > 0) {
                this.formData.selected_products[productId] = {
                    quantity: quantity,
                    product_id: productId
                };
            } else {
                delete this.formData.selected_products[productId];
            }
            
            this.updatePrice();
        }

        /**
         * Mettre à jour la sélection de suppléments
         */
        updateSupplementSelection($quantityField) {
            const supplementId = $quantityField.data('supplement-id');
            const productId = $quantityField.data('product-id');
            const quantity = parseInt($quantityField.val()) || 0;
            
            if (!this.formData.selected_supplements) {
                this.formData.selected_supplements = {};
            }
            
            if (!this.formData.selected_supplements[productId]) {
                this.formData.selected_supplements[productId] = {};
            }
            
            if (quantity > 0) {
                this.formData.selected_supplements[productId][supplementId] = quantity;
            } else {
                delete this.formData.selected_supplements[productId][supplementId];
            }
            
            this.updatePrice();
        }

        /**
         * Initialiser le calculateur de prix
         */
        initializePriceCalculator() {
            this.priceCalculator = {
                basePrice: 0,
                supplements: 0,
                products: 0,
                total: 0
            };
        }

        /**
         * Mettre à jour le prix
         */
        updatePrice() {
            if (!this.selectedService || this.currentStep < 2) {
                return;
            }
            
            const data = {
                action: 'calculate_quote_price_realtime',
                nonce: this.data.nonce,
                service_type: this.selectedService,
                form_data: this.formData
            };

            $.ajax({
                url: this.data.ajax_url,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        this.updatePriceDisplay(response.data);
                    }
                }
            });
        }

        /**
         * Mettre à jour l'affichage des prix
         */
        updatePriceDisplay(priceData) {
            const calculator = this.container.find('.rb-price-calculator');
            
            calculator.find('[data-price="base"]').text(this.formatPrice(priceData.base_price));
            calculator.find('[data-price="supplements"]').text(this.formatPrice(priceData.supplements_total));
            calculator.find('[data-price="products"]').text(this.formatPrice(priceData.products_total));
            calculator.find('.rb-price-total').text(this.formatPrice(priceData.total_price));
            
            // Animation du prix total
            calculator.find('.rb-price-total').addClass('updated');
            setTimeout(() => {
                calculator.find('.rb-price-total').removeClass('updated');
            }, 300);
        }

        /**
         * Formater un prix
         */
        formatPrice(price) {
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'EUR'
            }).format(price);
        }

        /**
         * Mettre à jour la barre de progression
         */
        updateProgressBar(currentStep, totalSteps) {
            const progressBar = this.container.find('.rb-progress-bar');
            const steps = progressBar.find('.rb-progress-step');
            const progressFill = progressBar.find('.rb-progress-fill');
            
            // Mettre à jour les étapes
            steps.removeClass('active completed');
            steps.each((index, step) => {
                if (index < currentStep) {
                    $(step).addClass('completed');
                } else if (index === currentStep) {
                    $(step).addClass('active');
                }
            });
            
            // Mettre à jour la barre de progression
            const progressPercent = (currentStep / (totalSteps - 1)) * 100;
            progressFill.css('width', progressPercent + '%');
        }

        /**
         * Mettre à jour les boutons de navigation
         */
        updateNavigationButtons() {
            const navigation = this.container.find('.rb-form-navigation');
            const prevBtn = navigation.find('.rb-btn-prev');
            const nextBtn = navigation.find('.rb-btn-next');
            const submitBtn = navigation.find('.rb-btn-submit');
            
            // Bouton précédent
            if (this.currentStep <= 1) {
                prevBtn.hide();
            } else {
                prevBtn.show();
            }
            
            // Déterminer si c'est la dernière étape
            const isLastStep = this.isLastStep();
            
            if (isLastStep) {
                nextBtn.hide();
                submitBtn.show();
            } else {
                nextBtn.show();
                submitBtn.hide();
            }
        }

        /**
         * Vérifier si c'est la dernière étape
         */
        isLastStep() {
            // Restaurant: 6 étapes (0-5), Remorque: 7 étapes (0-6)
            const maxSteps = this.selectedService === 'restaurant' ? 5 : 6;
            return this.currentStep >= maxSteps;
        }

        /**
         * Soumettre le formulaire
         */
        submitForm() {
            if (!this.validateCurrentStep()) {
                return;
            }
            
            this.showLoading();
            
            const data = {
                action: 'submit_unified_quote_form',
                nonce: this.data.nonce,
                service_type: this.selectedService,
                form_data: this.formData
            };

            $.ajax({
                url: this.data.ajax_url,
                type: 'POST',
                data: data,
                success: (response) => {
                    this.hideLoading();
                    
                    if (response.success) {
                        this.showSuccess(this.config.success_message);
                        this.resetForm();
                    } else {
                        this.showError(response.data || 'Erreur lors de l\'envoi du devis');
                    }
                },
                error: () => {
                    this.hideLoading();
                    this.showError(this.data.texts.error_network);
                }
            });
        }

        /**
         * Initialiser les éléments spéciaux d'une étape
         */
        initializeStepElements(stepData) {
            const stepElement = this.container.find(`.rb-form-step[data-step="${stepData.step_number}"]`);
            
            // Initialiser les sélecteurs de date
            stepElement.find('.rb-date-picker').each((index, element) => {
                this.initializeDatePicker($(element));
            });
            
            // Initialiser les sélecteurs de quantité
            stepElement.find('.rb-quantity-selector').each((index, element) => {
                this.initializeQuantitySelector($(element));
            });
        }

        /**
         * Initialiser un sélecteur de date
         */
        initializeDatePicker($element) {
            // Implémentation du sélecteur de date avec disponibilités
            // Sera connecté à l'API Google Calendar
        }

        /**
         * Initialiser un sélecteur de quantité
         */
        initializeQuantitySelector($element) {
            const minusBtn = $element.find('.rb-qty-minus');
            const plusBtn = $element.find('.rb-qty-plus');
            const input = $element.find('.rb-qty-input');
            
            minusBtn.on('click', () => {
                const currentVal = parseInt(input.val()) || 0;
                const minVal = parseInt(input.attr('min')) || 0;
                if (currentVal > minVal) {
                    input.val(currentVal - 1).trigger('change');
                }
            });
            
            plusBtn.on('click', () => {
                const currentVal = parseInt(input.val()) || 0;
                const maxVal = parseInt(input.attr('max')) || 999;
                if (currentVal < maxVal) {
                    input.val(currentVal + 1).trigger('change');
                }
            });
        }

        /**
         * Utilitaires
         */
        getMinGuests() {
            return this.selectedService === 'restaurant' ? 10 : 20;
        }

        getMaxGuests() {
            return this.selectedService === 'restaurant' ? 30 : 100;
        }

        isValidDate(dateString) {
            const date = new Date(dateString);
            return date instanceof Date && !isNaN(date);
        }

        isValidPostalCode(postalCode) {
            return /^[0-9]{5}$/.test(postalCode.replace(/\s/g, ''));
        }

        scrollToTop() {
            this.container[0].scrollIntoView({ behavior: 'smooth' });
        }

        showLoading() {
            this.container.find('.rb-message-loading').show();
        }

        hideLoading() {
            this.container.find('.rb-message-loading').hide();
        }

        showError(message) {
            const errorElement = this.container.find('.rb-message-error');
            errorElement.text(message).show();
            setTimeout(() => errorElement.fadeOut(), 5000);
        }

        showSuccess(message) {
            const successElement = this.container.find('.rb-message-success');
            successElement.html(message).show();
        }

        resetForm() {
            this.currentStep = 0;
            this.selectedService = null;
            this.formData = {};
            this.goToStep(0);
            this.container.find('.rb-progress-bar').hide();
            this.container.find('.rb-form-navigation').hide();
            this.container.find('.rb-price-calculator').hide();
            this.container.find('.rb-dynamic-steps').empty();
        }
    }

    /**
     * Initialisation automatique
     */
    $(document).ready(function() {
        $('.rb-quote-form-container').each(function() {
            new RestaurantBookingQuoteFormUnified(this);
        });
    });

    // Initialisation pour Elementor
    $(window).on('elementor/frontend/init', function() {
        elementorFrontend.hooks.addAction('frontend/element_ready/restaurant_booking_quote_form_unified.default', function($scope) {
            const container = $scope.find('.rb-quote-form-container');
            if (container.length) {
                new RestaurantBookingQuoteFormUnified(container[0]);
            }
        });
    });

})(jQuery);
