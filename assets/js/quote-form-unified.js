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
            
            // Récupérer les données JSON et les données WordPress localisées
            this.data = this.getWidgetData();
            this.wpData = window.rbUnifiedForm || {};
            
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
                nonce: this.wpData.nonce || this.data.nonce,
                service_type: service,
                step: stepNumber,
                form_data: this.formData
            };

            $.ajax({
                url: this.wpData.ajax_url || this.data.ajax_url,
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
                    this.showError(this.wpData.texts?.error_network || this.data.texts?.error_network || 'Erreur réseau');
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
            } else if (this.currentStep === 3) {
                // Validation des quantités minimales pour l'étape 3
                if (this.step3Validator) {
                    const step3Valid = this.step3Validator();
                    if (!step3Valid) {
                        this.showError('Veuillez respecter les quantités minimales requises pour chaque catégorie.');
                        isValid = false;
                    }
                }
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
                errorMessage = this.wpData.texts?.error_required || this.data.texts?.error_required || 'Ce champ est obligatoire';
            }
            
            // Validations spécifiques
            switch (fieldName) {
                case 'event_date':
                    if (value && !this.isValidDate(value)) {
                        isValid = false;
                        errorMessage = this.wpData.texts?.error_invalid_date || this.data.texts?.error_invalid_date || 'Date invalide';
                    }
                    break;
                    
                case 'guest_count':
                    const guestCount = parseInt(value);
                    const minGuests = this.getMinGuests();
                    const maxGuests = this.getMaxGuests();
                    
                    if (guestCount < minGuests) {
                        isValid = false;
                        errorMessage = (this.wpData.texts?.error_min_guests || this.data.texts?.error_min_guests || 'Nombre minimum de convives non respecté') + ` (${minGuests})`;
                    } else if (guestCount > maxGuests) {
                        isValid = false;
                        errorMessage = (this.wpData.texts?.error_max_guests || this.data.texts?.error_max_guests || 'Nombre maximum de convives dépassé') + ` (${maxGuests})`;
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
                nonce: this.wpData.nonce || this.data.nonce,
                service_type: this.selectedService,
                form_data: this.formData
            };

            $.ajax({
                url: this.wpData.ajax_url || this.data.ajax_url,
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
            
            if (calculator.length === 0) {
                return;
            }
            
            // Mettre à jour les valeurs individuelles
            calculator.find('[data-price="base"]').text(this.formatPrice(priceData.base_price || 0));
            calculator.find('[data-price="supplements"]').text(this.formatPrice(priceData.supplements_total || 0));
            calculator.find('[data-price="products"]').text(this.formatPrice(priceData.products_total || 0));
            
            // Mettre à jour le prix total
            const totalPrice = priceData.total_price || 0;
            calculator.find('.rb-price-total').text(this.formatPrice(totalPrice));
            
            // Animation du prix total
            calculator.find('.rb-price-total').addClass('updated');
            setTimeout(() => {
                calculator.find('.rb-price-total').removeClass('updated');
            }, 300);
            
            // Mettre à jour le détail si disponible
            if (priceData.breakdown && priceData.breakdown.length > 0) {
                this.updatePriceBreakdown(priceData.breakdown);
            }
            
            // Sauvegarder les données de prix pour référence
            this.priceCalculator = {
                basePrice: priceData.base_price || 0,
                supplements: priceData.supplements_total || 0,
                products: priceData.products_total || 0,
                total: totalPrice
            };
        }
        
        /**
         * Mettre à jour le détail des prix
         */
        updatePriceBreakdown(breakdown) {
            const calculator = this.container.find('.rb-price-calculator');
            let breakdownContainer = calculator.find('.rb-price-breakdown-detail');
            
            if (breakdownContainer.length === 0) {
                calculator.append('<div class="rb-price-breakdown-detail" style="margin-top: 15px; font-size: 12px; display: none;"></div>');
                breakdownContainer = calculator.find('.rb-price-breakdown-detail');
            }
            
            let html = '<h5 style="margin: 0 0 10px 0; color: var(--rb-primary-color);">Détail du calcul :</h5>';
            
            breakdown.forEach(item => {
                html += `<div style="display: flex; justify-content: space-between; margin-bottom: 5px;">`;
                html += `<span>${item.label}</span>`;
                html += `<span>${this.formatPrice(item.amount)}</span>`;
                html += `</div>`;
            });
            
            breakdownContainer.html(html);
            
            // Ajouter un bouton pour afficher/masquer le détail
            if (calculator.find('.rb-toggle-breakdown').length === 0) {
                calculator.append('<button type="button" class="rb-toggle-breakdown" style="background: none; border: none; color: var(--rb-primary-color); font-size: 12px; cursor: pointer; margin-top: 10px;">Voir le détail ▼</button>');
                
                calculator.find('.rb-toggle-breakdown').on('click', function() {
                    const detail = calculator.find('.rb-price-breakdown-detail');
                    const button = $(this);
                    
                    if (detail.is(':visible')) {
                        detail.slideUp();
                        button.text('Voir le détail ▼');
                    } else {
                        detail.slideDown();
                        button.text('Masquer le détail ▲');
                    }
                });
            }
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
                nonce: this.wpData.nonce || this.data.nonce,
                service_type: this.selectedService,
                form_data: this.formData
            };

            $.ajax({
                url: this.wpData.ajax_url || this.data.ajax_url,
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
                    this.showError(this.wpData.texts?.error_network || this.data.texts?.error_network || 'Erreur réseau');
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
            
            // Initialiser la gestion des produits pour l'étape 3
            if (stepData.step_number === 3) {
                this.initializeProductsStep3(stepElement);
            }
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
         * Initialiser la gestion des produits pour l'étape 3
         */
        initializeProductsStep3($stepElement) {
            const guestCount = parseInt(this.formData.guest_count) || 10;
            
            // Gérer la sélection du type de signature
            $stepElement.find('input[name="signature_type"]').on('change', (e) => {
                const signatureType = e.target.value;
                this.loadSignatureProducts(signatureType);
            });
            
            // Initialiser les sélecteurs de quantité existants
            this.initializeQuantitySelectors($stepElement);
            
            // Valider les quantités minimales
            this.setupMinimumQuantityValidation($stepElement, guestCount);
        }

        /**
         * Charger les produits signature selon le type sélectionné
         */
        loadSignatureProducts(signatureType) {
            const signatureContainer = this.container.find('#signature-products');
            signatureContainer.show().html('<div class="rb-loading-placeholder">Chargement des produits ' + signatureType + '...</div>');
            
            const data = {
                action: 'get_products_by_category_v2',
                nonce: this.wpData.nonce || this.data.nonce,
                category_type: 'signature',
                signature_type: signatureType,
                service_type: this.selectedService
            };

            $.ajax({
                url: this.wpData.ajax_url || this.data.ajax_url,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success && response.data.products) {
                        this.renderSignatureProducts(response.data.products, signatureType);
                    } else {
                        signatureContainer.html('<p>Aucun produit disponible pour cette sélection.</p>');
                    }
                },
                error: () => {
                    signatureContainer.html('<p>Erreur lors du chargement des produits.</p>');
                }
            });
        }

        /**
         * Rendre les produits signature
         */
        renderSignatureProducts(products, signatureType) {
            const signatureContainer = this.container.find('#signature-products');
            const guestCount = parseInt(this.formData.guest_count) || 10;
            
            let html = '<div class="rb-products-grid">';
            
            products.forEach(product => {
                html += '<div class="rb-product-card" data-product-id="' + product.id + '">';
                html += '<div class="rb-product-content">';
                html += '<h5 class="rb-product-title">' + product.name + '</h5>';
                
                if (product.description) {
                    html += '<p class="rb-product-description">' + product.description + '</p>';
                }
                
                html += '<div class="rb-product-price">' + this.formatPrice(product.price) + '</div>';
                html += '</div>';
                
                html += '<div class="rb-product-footer">';
                html += '<div class="rb-quantity-selector">';
                html += '<button type="button" class="rb-qty-btn rb-qty-minus" data-target="signature_' + product.id + '">-</button>';
                html += '<input type="number" class="rb-qty-input rb-product-quantity" id="signature_' + product.id + '" ';
                html += 'name="products[signature][' + product.id + ']" value="0" min="0" max="' + (guestCount * 2) + '" ';
                html += 'data-product-id="' + product.id + '" data-category="signature" data-min-required="' + guestCount + '">';
                html += '<button type="button" class="rb-qty-btn rb-qty-plus" data-target="signature_' + product.id + '">+</button>';
                html += '</div>';
                
                // Ajouter les suppléments si disponibles
                if (product.supplements && product.supplements.length > 0) {
                    html += '<div class="rb-product-supplements" style="margin-top: 10px;">';
                    html += '<h6>Suppléments :</h6>';
                    product.supplements.forEach(supplement => {
                        html += '<div class="rb-supplement-option">';
                        html += '<label>' + supplement.name + ' (+' + this.formatPrice(supplement.price) + ')</label>';
                        html += '<input type="number" class="rb-supplement-quantity" ';
                        html += 'name="supplements[' + product.id + '][' + supplement.id + ']" ';
                        html += 'value="0" min="0" max="' + guestCount + '" ';
                        html += 'data-product-id="' + product.id + '" data-supplement-id="' + supplement.id + '">';
                        html += '</div>';
                    });
                    html += '</div>';
                }
                
                html += '</div>';
                html += '</div>';
            });
            
            html += '</div>';
            
            // Ajouter un message d'aide
            html += '<div class="rb-help-text" style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 5px;">';
            html += '<small><strong>💡 Aide :</strong> Minimum ' + guestCount + ' plats requis (1 par convive). ';
            html += 'Vous pouvez mélanger les différents produits.</small>';
            html += '</div>';
            
            signatureContainer.html(html);
            
            // Initialiser les sélecteurs de quantité pour les nouveaux éléments
            this.initializeQuantitySelectors(signatureContainer);
            
            // Sauvegarder le type de signature sélectionné
            this.formData.signature_type = signatureType;
        }

        /**
         * Initialiser tous les sélecteurs de quantité dans un conteneur
         */
        initializeQuantitySelectors($container) {
            $container.find('.rb-quantity-selector').each((index, element) => {
                this.initializeQuantitySelector($(element));
            });
        }

        /**
         * Configurer la validation des quantités minimales
         */
        setupMinimumQuantityValidation($stepElement, guestCount) {
            const validateMinimums = () => {
                // Validation des plats signature
                const signatureTotal = this.getTotalQuantityByCategory('signature');
                const signatureValid = signatureTotal >= guestCount;
                
                // Validation des accompagnements
                const accompanimentsTotal = this.getTotalQuantityByCategory('accompaniments');
                const accompanimentsValid = accompanimentsTotal >= guestCount;
                
                // Mettre à jour les indicateurs visuels
                this.updateValidationIndicators($stepElement, {
                    signature: signatureValid,
                    accompaniments: accompanimentsValid
                });
                
                return signatureValid && accompanimentsValid;
            };
            
            // Valider lors des changements de quantité
            $stepElement.find('.rb-product-quantity').on('change input', validateMinimums);
            
            // Sauvegarder la fonction de validation pour l'étape
            this.step3Validator = validateMinimums;
        }

        /**
         * Obtenir le total des quantités pour une catégorie
         */
        getTotalQuantityByCategory(category) {
            let total = 0;
            this.container.find(`[data-category="${category}"]`).each((index, element) => {
                total += parseInt($(element).val()) || 0;
            });
            return total;
        }

        /**
         * Mettre à jour les indicateurs de validation
         */
        updateValidationIndicators($stepElement, validations) {
            Object.keys(validations).forEach(category => {
                const categoryElement = $stepElement.find(`[data-category="${category}"]`).closest('.rb-product-category');
                const isValid = validations[category];
                
                if (isValid) {
                    categoryElement.removeClass('rb-validation-error').addClass('rb-validation-success');
                } else {
                    categoryElement.removeClass('rb-validation-success').addClass('rb-validation-error');
                }
            });
        }

        /**
         * Utilitaires
         */
        getMinGuests() {
            if (this.selectedService === 'restaurant') {
                return this.wpData.config?.min_guests_restaurant || 10;
            }
            return this.wpData.config?.min_guests_remorque || 20;
        }

        getMaxGuests() {
            if (this.selectedService === 'restaurant') {
                return this.wpData.config?.max_guests_restaurant || 30;
            }
            return this.wpData.config?.max_guests_remorque || 100;
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
