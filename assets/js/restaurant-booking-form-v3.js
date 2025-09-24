/**
 * JavaScript Formulaire Block V3 - Code moderne et robuste
 * Gestion complète du formulaire multi-étapes avec validation
 * 
 * @package RestaurantBooking
 * @version 3.0.0
 */

(function($) {
    'use strict';

    /**
     * Classe principale du formulaire V3
     */
    class RestaurantBookingFormV3 {
        constructor(container) {
            this.container = $(container);
            this.formId = this.container.attr('id');
            this.config = this.container.data('config') || {};
            
            // État du formulaire
            this.currentStep = 0; // Commence à l'étape 0 (sélection service)
            this.totalSteps = 6; // Par défaut restaurant
            this.selectedService = null;
            this.formData = {};
            this.priceData = {
                base: 0,
                supplements: 0,
                products: 0,
                total: 0
            };
            
            // Éléments DOM
            this.progressBar = this.container.find('.rbf-v3-progress-fill');
            this.progressSteps = this.container.find('.rbf-v3-step');
            this.messages = this.container.find('.rbf-v3-messages');
            this.dynamicContent = this.container.find('.rbf-v3-dynamic-content');
            this.navigation = this.container.find('.rbf-v3-navigation');
            this.prevButton = this.container.find('#rbf-v3-prev');
            this.nextButton = this.container.find('#rbf-v3-next');
            // Le bouton "Passer cette étape" sera ajouté dynamiquement dans les étapes concernées
            this.calculator = this.container.find('.rbf-v3-price-calculator');
            
            // Éléments de prix
            this.priceBase = this.container.find('#rbf-v3-price-base');
            this.priceSupplements = this.container.find('#rbf-v3-price-supplements');
            this.priceProducts = this.container.find('#rbf-v3-price-products');
            this.priceTotal = this.container.find('#rbf-v3-price-total');
            
            this.init();
        }

        /**
         * Initialisation
         */
        init() {
            this.bindEvents();
            this.updateProgress();
            this.updateNavigation(); // S'assurer que l'affichage initial est correct
            this.log('Formulaire V3 initialisé', this.config);
        }

        /**
         * Liaison des événements
         */
        bindEvents() {
            // Sélection de service
            this.container.on('click', '[data-action="select-service"]', (e) => {
                const service = $(e.currentTarget).data('service');
                this.selectService(service);
            });

            // Navigation
            this.prevButton.on('click', () => this.goToPreviousStep());
            this.nextButton.on('click', () => this.goToNextStep());
            
            // Les événements pour "Passer cette étape" seront ajoutés dynamiquement

            // Mise à jour des données en temps réel
            this.container.on('change input', 'input, select, textarea', () => {
                this.updateFormData();
                this.calculatePrice();
                // Effacer les messages d'erreur lors de la saisie
                this.hideMessage();
            });

            // Validation en temps réel
            this.container.on('blur', 'input[required], select[required], textarea[required]', (e) => {
                this.validateField($(e.target));
            });

            // Soumission du formulaire
            this.container.on('submit', 'form', (e) => {
                e.preventDefault();
                this.submitForm();
            });
            
            // Bouton "Commencer mon devis" de l'étape 1
            this.container.on('click', '#rbf-v3-start-quote', () => {
                this.goToNextStep();
            });
            
            // Sélecteurs de quantité
            this.container.on('click', '.rbf-v3-qty-plus', (e) => {
                this.handleQuantityChange($(e.currentTarget), 1);
            });
            
            this.container.on('click', '.rbf-v3-qty-minus', (e) => {
                this.handleQuantityChange($(e.currentTarget), -1);
            });
            
            // Onglets boissons
            this.container.on('click', '.rbf-v3-tab-btn', (e) => {
                this.switchBeverageTab($(e.currentTarget));
            });
            
            // Sélecteurs de quantité pour boissons
            this.container.on('click', '.rbf-v3-qty-btn.plus', (e) => {
                this.handleBeverageQuantityChange($(e.currentTarget), 1);
            });
            
            this.container.on('click', '.rbf-v3-qty-btn.minus', (e) => {
                this.handleBeverageQuantityChange($(e.currentTarget), -1);
            });
            
            // Input quantité boissons
            this.container.on('change', '.rbf-v3-qty-input', (e) => {
                this.handleBeverageQuantityInput($(e.currentTarget));
            });
            
            // Événement délégué pour tous les boutons "Passer cette étape"
            this.container.on('click', '.rbf-v3-skip-step', () => {
                this.skipCurrentStep();
            });
            
            // Chargement des produits signature
            this.container.on('change', '[data-action="load-signature-products"]', (e) => {
                this.loadSignatureProducts($(e.currentTarget).val());
            });
            
            // Toggle Mini Boss
            this.container.on('change', '[data-action="toggle-mini-boss"]', (e) => {
                this.toggleMiniBoss($(e.currentTarget).is(':checked'));
            });
            
            // Gestion des accompagnements
            this.container.on('change', '.rbf-v3-accompaniment-checkbox', (e) => {
                this.handleAccompanimentToggle($(e.currentTarget));
            });
            
            // Event listeners pour les options frites
            this.container.on('change', '.rbf-v3-option-checkbox, .rbf-v3-sauce-checkbox', (e) => {
                this.handleFritesOptionToggle($(e.currentTarget));
            });
            
            // Event listeners pour les champs de l'étape 2 (recalcul prix)
            this.container.on('change', '[name="guest_count"], [name="event_duration"]', () => {
                if (this.currentStep >= 2) {
                    this.calculatePrice();
                }
            });
        }

        /**
         * Sélectionner un service
         */
        selectService(service) {
            this.selectedService = service;
            this.formData.service_type = service;
            this.totalSteps = (service === 'restaurant') ? 6 : 7;

            // Marquer la card comme sélectionnée
            this.container.find('.rbf-v3-service-card').removeClass('selected');
            this.container.find(`[data-service="${service}"]`).addClass('selected');

            // Mettre à jour la navigation maintenant que le service est défini
            this.updateNavigation();

            // Le calculateur sera affiché automatiquement à partir de l'étape 2
            // par la logique dans updateStepDisplay()

            // Passer à l'étape suivante automatiquement
            setTimeout(() => {
                this.goToNextStep();
            }, 500);

            this.log('Service sélectionné:', service);
        }

        /**
         * Aller à l'étape suivante
         */
        goToNextStep() {
            this.log('Tentative de passage à l\'étape suivante. Étape actuelle:', this.currentStep);
            
            if (!this.validateCurrentStep()) {
                this.log('Validation échouée, arrêt du passage à l\'étape suivante');
                return;
            }

            this.log('Validation réussie, passage à l\'étape suivante');

            if (this.currentStep < this.totalSteps) {
                this.currentStep++;
                this.loadStep(this.currentStep);
                this.updateProgress();
                this.updateNavigation();
            }
        }

        /**
         * Aller à l'étape précédente
         */
        goToPreviousStep() {
            if (this.currentStep > 1) {
                this.currentStep--;
                this.loadStep(this.currentStep);
                this.updateProgress();
                this.updateNavigation();
            }
        }

        /**
         * Passer l'étape actuelle (pour les étapes optionnelles)
         */
        skipCurrentStep() {
            this.log('Passage de l\'étape', this.currentStep);
            
            if (this.currentStep < this.totalSteps) {
                this.currentStep++;
                this.loadStep(this.currentStep);
                this.updateProgress();
                this.updateNavigation();
            }
        }

        // La méthode isOptionalStep n'est plus nécessaire car les boutons sont ajoutés directement dans les étapes

        /**
         * Charger une étape
         */
        loadStep(stepNumber) {
            // Masquer l'étape actuelle
            this.container.find('.rbf-v3-step-content.active').removeClass('active');
            
            // Effacer les messages d'erreur lors du changement d'étape
            this.hideMessage();

            if (stepNumber === 0) {
                // Étape 0 (sélection service) est déjà dans le HTML
                this.container.find('[data-step="0"]').addClass('active');
                this.updateNavigation();
                return;
            }

            // Charger les autres étapes via AJAX
            this.showLoading();

            const data = {
                action: 'rbf_v3_load_step',
                nonce: rbfV3Config.nonce,
                step: stepNumber,
                service_type: this.selectedService,
                form_data: this.formData
            };

            $.ajax({
                url: rbfV3Config.ajaxUrl,
                type: 'POST',
                data: data,
                success: (response) => {
                    this.hideLoading();
                    
                    if (response.success) {
                        this.dynamicContent.html(response.data.html);
                        this.container.find(`[data-step="${stepNumber}"]`).addClass('active');
                        
                        // Initialiser les sélecteurs de quantité
                        this.initializeQuantitySelectors();
                        
                        this.scrollToTop();
                    } else {
                        this.showMessage(response.data.message || 'Erreur lors du chargement de l\'étape', 'error');
                    }
                },
                error: () => {
                    this.hideLoading();
                    this.showMessage(rbfV3Config.texts.error_network, 'error');
                }
            });
        }

        /**
         * Valider l'étape actuelle
         */
        validateCurrentStep() {
            this.log('Validation de l\'étape actuelle:', this.currentStep);
            const result = this.validateStep(this.currentStep);
            this.log('Résultat de la validation:', result);
            return result;
        }

        /**
         * Valider un champ
         */
        validateField($field) {
            const fieldValue = $field.val();
            const value = fieldValue ? fieldValue.trim() : '';
            const fieldType = $field.attr('type');
            const fieldName = $field.attr('name');

            // Champ requis
            if ($field.prop('required') && !value) {
                this.markFieldError($field);
                return false;
            }

            // Validations spécifiques
            switch (fieldType) {
                case 'email':
                    if (value && !this.isValidEmail(value)) {
                        this.markFieldError($field);
                        return false;
                    }
                    break;
                    
                case 'tel':
                    if (value && !this.isValidPhone(value)) {
                        this.markFieldError($field);
                        return false;
                    }
                    break;
                    
                case 'date':
                    if (value && !this.isValidDate(value)) {
                        this.markFieldError($field);
                        return false;
                    }
                    break;
                    
                case 'number':
                    const min = parseInt($field.attr('min'));
                    const max = parseInt($field.attr('max'));
                    const numValue = parseInt(value);
                    
                    if (value && (isNaN(numValue) || (min && numValue < min) || (max && numValue > max))) {
                        this.markFieldError($field);
                        return false;
                    }
                    break;
            }

            // Validation code postal
            if (fieldName === 'postal_code' && value && !/^\d{5}$/.test(value)) {
                this.markFieldError($field);
                return false;
            }

            this.markFieldValid($field);
            return true;
        }

        /**
         * Marquer un champ comme invalide
         */
        markFieldError($field) {
            $field.addClass('rbf-v3-field-error');
            $field.removeClass('rbf-v3-field-valid');
        }

        /**
         * Marquer un champ comme valide
         */
        markFieldValid($field) {
            $field.removeClass('rbf-v3-field-error');
            $field.addClass('rbf-v3-field-valid');
        }

        /**
         * Obtenir le message d'erreur pour un champ
         */
        getFieldErrorMessage($field) {
            const fieldName = $field.attr('name');
            const fieldLabel = $field.closest('.rbf-v3-form-group').find('label').text().replace('*', '').trim();
            
            const messages = {
                'event_date': '📅 Veuillez compléter la date de l\'événement',
                'guest_count': '👥 Veuillez indiquer le nombre de convives',
                'event_duration': '⏰ Veuillez choisir la durée de l\'événement',
                'postal_code': '📍 Veuillez saisir votre code postal (5 chiffres)',
                'client_name': '👤 Veuillez saisir votre nom',
                'client_firstname': '👤 Veuillez saisir votre prénom',
                'client_email': '📧 Veuillez saisir une adresse email valide',
                'client_phone': '📞 Veuillez saisir un numéro de téléphone valide'
            };

            return messages[fieldName] || `⚠️ Veuillez compléter le champ "${fieldLabel}"`;
        }

        /**
         * Mettre à jour les données du formulaire
         */
        updateFormData() {
            const currentStepElement = this.container.find('.rbf-v3-step-content.active');
            
            currentStepElement.find('input, select, textarea').each((index, field) => {
                const $field = $(field);
                const name = $field.attr('name');
                const type = $field.attr('type');
                
                if (name) {
                    if (type === 'checkbox') {
                        this.formData[name] = $field.is(':checked');
                    } else if (type === 'radio') {
                        if ($field.is(':checked')) {
                            this.formData[name] = $field.val();
                        }
                    } else {
                        this.formData[name] = $field.val();
                    }
                }
            });

            this.log('Données du formulaire mises à jour:', this.formData);
        }

        /**
         * Calculer le prix
         */
        calculatePrice() {
            if (!this.selectedService) return;

            // Calculer le prix des boissons côté client
            const beveragesPrice = this.calculateBeveragesPrice();
            
            const data = {
                action: 'rbf_v3_calculate_price',
                nonce: rbfV3Config.nonce,
                service_type: this.selectedService,
                form_data: this.formData,
                beverages_price: beveragesPrice
            };

            $.ajax({
                url: rbfV3Config.ajaxUrl,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        this.priceData = response.data;
                        // Ajouter le prix des boissons au total
                        this.priceData.beverages = beveragesPrice;
                        this.priceData.total += beveragesPrice;
                        this.updatePriceDisplay();
                    }
                },
                error: () => {
                    this.log('Erreur lors du calcul du prix');
                }
            });
        }

        /**
         * Mettre à jour l'affichage du prix
         */
        updatePriceDisplay() {
            if (!this.priceData) return;
            
            const $calculator = this.container.find('.rbf-v3-price-calculator');
            const $body = $calculator.find('.rbf-v3-calculator-body');
            
            let html = '';
            
            // Forfait de base
            if (this.priceData.base_price > 0) {
                html += `<div class="rbf-v3-price-line">
                    <span>Forfait de base</span>
                    <span class="rbf-v3-price">${this.formatPrice(this.priceData.base_price)}</span>
                </div>`;
            }
            
            // Produits détaillés
            if (this.priceData.products && this.priceData.products.length > 0) {
                this.priceData.products.forEach(product => {
                    if (product.quantity > 0) {
                        html += `<div class="rbf-v3-price-line">
                            <span>${product.quantity}× ${product.name}</span>
                            <span class="rbf-v3-price">${this.formatPrice(product.total)}</span>
                        </div>`;
                    }
                });
            }
            
            // Boissons détaillées
            if (this.priceData.beverages && this.priceData.beverages > 0) {
                html += `<div class="rbf-v3-price-line">
                    <span>Boissons</span>
                    <span class="rbf-v3-price">${this.formatPrice(this.priceData.beverages)}</span>
                </div>`;
            }
            
            // Suppléments (incluant la durée supplémentaire)
            if (this.priceData.supplements && this.priceData.supplements.length > 0) {
                this.priceData.supplements.forEach(supplement => {
                    html += `<div class="rbf-v3-price-line">
                        <span>${supplement.name}</span>
                        <span class="rbf-v3-price">${this.formatPrice(supplement.price)}</span>
                    </div>`;
                });
            }
            
            // Total
            html += `<div class="rbf-v3-price-line rbf-v3-price-total">
                <span><strong>Total estimé</strong></span>
                <span class="rbf-v3-price"><strong>${this.formatPrice(this.priceData.total)}</strong></span>
            </div>`;
            
            $body.html(html);
            
            // Animation du prix total
            $calculator.addClass('rbf-v3-price-updated');
            setTimeout(() => {
                $calculator.removeClass('rbf-v3-price-updated');
            }, 300);
        }

        /**
         * Mettre à jour la barre de progression
         */
        updateProgress() {
            // Centrer la barre de progression au-dessus de l'étape actuelle
            const progressPercent = ((this.currentStep - 0.5) / this.totalSteps) * 100;
            this.progressBar.css('width', progressPercent + '%');

            // Mettre à jour les étapes
            this.progressSteps.each((index, step) => {
                const $step = $(step);
                const stepNumber = parseInt($step.data('step'));
                
                $step.removeClass('active completed');
                
                if (stepNumber < this.currentStep) {
                    $step.addClass('completed');
                } else if (stepNumber === this.currentStep) {
                    $step.addClass('active');
                }
            });
        }

        /**
         * Mettre à jour la navigation
         */
        updateNavigation() {
            // Bouton précédent
            if (this.currentStep > 0) {
                this.prevButton.show();
            } else {
                this.prevButton.hide();
            }

            // Le bouton "Passer cette étape" est maintenant géré directement dans les étapes concernées

            // Bouton suivant
            if (this.currentStep < this.totalSteps) {
                this.nextButton.show().text(rbfV3Config.texts.next_step || 'Étape suivante →');
            } else {
                this.nextButton.show().text(rbfV3Config.texts.submit_quote || 'Obtenir mon devis');
            }

            // Masquer la navigation sur l'étape 0 et 1 (sélection service et explication)
            if (this.currentStep === 0 || this.currentStep === 1) {
                this.navigation.hide();
            } else {
                this.navigation.show();
            }
            
            // Afficher le calculateur de prix à partir de l'étape 2
            const $calculator = this.container.find('.rbf-v3-price-calculator');
            if (this.currentStep >= 2) {
                $calculator.show();
            } else {
                $calculator.hide();
            }
        }

        /**
         * Changer d'onglet boissons
         */
        switchBeverageTab($tabBtn) {
            const tabName = $tabBtn.data('tab');
            
            // Mettre à jour les boutons d'onglets
            this.container.find('.rbf-v3-tab-btn').removeClass('active');
            $tabBtn.addClass('active');
            
            // Mettre à jour le contenu des onglets
            this.container.find('.rbf-v3-tab-content').removeClass('active');
            this.container.find(`.rbf-v3-tab-content[data-tab="${tabName}"]`).addClass('active');
        }
        
        /**
         * Gérer le changement de quantité pour les boissons
         */
        handleBeverageQuantityChange($btn, delta) {
            const $input = $btn.siblings('.rbf-v3-qty-input');
            const currentValue = parseInt($input.val()) || 0;
            const newValue = Math.max(0, currentValue + delta);
            
            $input.val(newValue);
            this.updateBeverageQuantity($input);
        }
        
        /**
         * Gérer la saisie directe de quantité pour les boissons
         */
        handleBeverageQuantityInput($input) {
            const value = parseInt($input.val()) || 0;
            $input.val(Math.max(0, value));
            this.updateBeverageQuantity($input);
        }
        
        /**
         * Mettre à jour la quantité d'une boisson
         */
        updateBeverageQuantity($input) {
            const quantity = parseInt($input.val()) || 0;
            const price = parseFloat($input.data('price')) || 0;
            const productId = $input.data('product-id');
            const sizeId = $input.data('size-id');
            const size = $input.data('size');
            
            // Mettre à jour l'état des boutons
            const $minusBtn = $input.siblings('.rbf-v3-qty-btn.minus');
            $minusBtn.prop('disabled', quantity <= 0);
            
            // Stocker la sélection dans formData
            if (!this.formData.beverages) {
                this.formData.beverages = {};
            }
            
            const key = sizeId ? `size_${sizeId}` : (size ? `${productId}_${size}` : productId);
            
            if (quantity > 0) {
                this.formData.beverages[key] = {
                    product_id: productId,
                    size_id: sizeId,
                    size: size,
                    quantity: quantity,
                    price: price
                };
            } else {
                delete this.formData.beverages[key];
            }
            
            // Recalculer le prix
            this.calculatePrice();
        }
        
        // La méthode skipBeveragesStep a été remplacée par skipCurrentStep qui est plus générique
        
        /**
         * Calculer le prix des boissons
         */
        calculateBeveragesPrice() {
            let beveragesTotal = 0;
            
            if (this.formData.beverages) {
                Object.values(this.formData.beverages).forEach(beverage => {
                    beveragesTotal += beverage.quantity * beverage.price;
                });
            }
            
            return beveragesTotal;
        }

        /**
         * Soumettre le formulaire
         */
        submitForm() {
            if (!this.validateCurrentStep()) {
                return;
            }

            this.showLoading();
            this.nextButton.prop('disabled', true);

            const data = {
                action: 'rbf_v3_submit_quote',
                nonce: rbfV3Config.nonce,
                service_type: this.selectedService,
                form_data: this.formData,
                price_data: this.priceData
            };

            $.ajax({
                url: rbfV3Config.ajaxUrl,
                type: 'POST',
                data: data,
                success: (response) => {
                    this.hideLoading();
                    
                    if (response.success) {
                        this.showMessage(rbfV3Config.texts.success_quote, 'success');
                        this.container.find('.rbf-v3-content').html(
                            '<div class="rbf-v3-success-message">' +
                            '<h2>🎉 Devis envoyé avec succès !</h2>' +
                            '<p>' + (this.config.success_message || rbfV3Config.texts.success_quote) + '</p>' +
                            '</div>'
                        );
                        this.navigation.hide();
                        this.calculator.hide();
                    } else {
                        this.showMessage(response.data.message || 'Erreur lors de l\'envoi du devis', 'error');
                        this.nextButton.prop('disabled', false);
                    }
                },
                error: () => {
                    this.hideLoading();
                    this.showMessage(rbfV3Config.texts.error_network, 'error');
                    this.nextButton.prop('disabled', false);
                }
            });
        }

        /**
         * Afficher un message
         */
        showMessage(message, type = 'info') {
            const icons = {
                success: '✅',
                error: '❌',
                info: 'ℹ️'
            };

            const html = `
                <div class="rbf-v3-message ${type}">
                    ${icons[type]} ${message}
                </div>
            `;

            this.log('Affichage message:', { message, type, html });
            
            this.messages.html(html).show();
            this.scrollToTop();
        }

        /**
         * Masquer les messages
         */
        hideMessage() {
            this.messages.hide().empty();
        }

        /**
         * Afficher le loading
         */
        showLoading() {
            this.container.addClass('rbf-v3-loading');
        }

        /**
         * Masquer le loading
         */
        hideLoading() {
            this.container.removeClass('rbf-v3-loading');
        }

        /**
         * Faire défiler vers le haut
         */
        scrollToTop() {
            $('html, body').animate({
                scrollTop: this.container.offset().top - 50
            }, 300);
        }

        /**
         * Utilitaires de validation
         */
        isValidEmail(email) {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        }

        isValidPhone(phone) {
            const regex = /^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/;
            return regex.test(phone.replace(/\s/g, ''));
        }

        isValidDate(date) {
            const selectedDate = new Date(date);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            return selectedDate >= today;
        }

        /**
         * Formater le prix
         */
        formatPrice(price) {
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'EUR',
                minimumFractionDigits: 0
            }).format(price || 0);
        }

        /**
         * Gérer les changements de quantité
         */
        handleQuantityChange($button, delta) {
            const targetName = $button.data('target');
            const $input = this.container.find(`[name="${targetName}"]`);
            
            if ($input.length) {
                const currentValue = parseInt($input.val()) || 0;
                const minValue = parseInt($input.attr('min')) || 0;
                const maxValue = parseInt($input.attr('max')) || 999;
                const newValue = Math.max(minValue, Math.min(maxValue, currentValue + delta));
                
                $input.val(newValue).trigger('change');
                
                // Mettre à jour les boutons
                this.updateQuantityButtons($input);
                
                // Afficher/masquer les options des frites
                if (targetName.includes('accompaniment_') && targetName.includes('_qty')) {
                    this.handleFritesOptionsDisplay($input, newValue);
                }
                
                // Validation spéciale pour les frites
                if (targetName.includes('frites_sauce') || targetName.includes('frites_chimichurri')) {
                    this.validateFritesOptions();
                }
                
                // Recalculer le prix si on est à partir de l'étape 2
                if (this.currentStep >= 2) {
                    this.calculatePrice();
                }
            }
        }

        /**
         * Mettre à jour l'état des boutons de quantité
         */
        updateQuantityButtons($input) {
            const value = parseInt($input.val()) || 0;
            const min = parseInt($input.attr('min')) || 0;
            const max = parseInt($input.attr('max')) || 999;
            const name = $input.attr('name');
            
            const $minusBtn = this.container.find(`[data-target="${name}"]`).filter('.rbf-v3-qty-minus');
            const $plusBtn = this.container.find(`[data-target="${name}"]`).filter('.rbf-v3-qty-plus');
            
            $minusBtn.prop('disabled', value <= min);
            $plusBtn.prop('disabled', value >= max);
        }

        /**
         * Charger les produits signature selon le choix DOG/CROQ
         */
        loadSignatureProducts(type) {
            const data = {
                action: 'rbf_v3_load_signature_products',
                nonce: rbfV3Config.nonce,
                signature_type: type,
                guest_count: this.formData.guest_count || 10
            };

            $.ajax({
                url: rbfV3Config.ajaxUrl,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        this.container.find('.rbf-v3-signature-products').html(response.data.html).show();
                        this.initializeQuantitySelectors();
                    }
                },
                error: () => {
                    this.log('Erreur lors du chargement des produits signature');
                }
            });
        }

        /**
         * Toggle du menu Mini Boss
         */
        toggleMiniBoss(enabled) {
            const $container = this.container.find('.rbf-v3-mini-boss-products');
            
            if (enabled) {
                $container.slideDown();
                this.initializeQuantitySelectors();
            } else {
                $container.slideUp();
                // Remettre toutes les quantités à 0
                $container.find('.rbf-v3-qty-input').val(0).trigger('change');
            }
        }

        /**
         * Gérer le toggle des accompagnements
         */
        handleAccompanimentToggle($checkbox) {
            const $item = $checkbox.closest('.rbf-v3-accompaniment-item');
            const $qtyInput = $item.find('.rbf-v3-qty-input');
            const $options = $item.find('.rbf-v3-frites-options');
            
            if ($checkbox.is(':checked')) {
                // Activer avec quantité minimum
                const guestCount = parseInt(this.formData.guest_count) || 10;
                $qtyInput.val(guestCount).trigger('change');
                
                // Afficher les options pour les frites
                if ($checkbox.attr('name').includes('frites') || 
                    $item.find('.rbf-v3-accompaniment-name').text().toLowerCase().includes('frites')) {
                    $options.slideDown();
                }
            } else {
                // Désactiver
                $qtyInput.val(0).trigger('change');
                $options.slideUp();
                
                // Désactiver toutes les options
                $options.find('input[type="checkbox"]').prop('checked', false);
                $options.find('.rbf-v3-qty-input').val(0);
            }
            
            this.updateQuantityButtons($qtyInput);
        }

        /**
         * Valider les options des frites
         */
        validateFritesOptions() {
            // Trouver la quantité de frites (accompagnement)
            const $fritesInput = this.container.find('[name*="accompaniment"][name*="_qty"]').filter(function() {
                return $(this).closest('.rbf-v3-accompaniment-card').find('h4').text().toLowerCase().includes('frites');
            });
            
            const fritesQuantity = parseInt($fritesInput.val()) || 0;
            
            // Valider que les sauces et chimichurri ne dépassent pas la quantité de frites
            this.container.find('[name*="sauce"][name*="_qty"], [name="frites_chimichurri_qty"]').each((index, input) => {
                const $input = $(input);
                const currentValue = parseInt($input.val()) || 0;
                
                // Mettre à jour le max
                $input.attr('max', fritesQuantity);
                
                // Si la valeur actuelle dépasse, la réduire
                if (currentValue > fritesQuantity) {
                    $input.val(fritesQuantity).trigger('change');
                    this.updateQuantityButtons($input);
                }
                
                // Désactiver les boutons + si on atteint le max
                const $plusBtn = $input.siblings('.rbf-v3-qty-plus');
                $plusBtn.prop('disabled', currentValue >= fritesQuantity);
            });
        }

        /**
         * Gérer l'affichage des options des frites
         */
        handleFritesOptionsDisplay($input, quantity) {
            const $card = $input.closest('.rbf-v3-accompaniment-card');
            const $options = $card.find('.rbf-v3-frites-options');
            
            // Vérifier si c'est des frites
            const cardTitle = $card.find('h4').text().toLowerCase();
            if (cardTitle.includes('frites')) {
                if (quantity > 0) {
                    $options.slideDown();
                } else {
                    $options.slideUp();
                    // Remettre toutes les options à 0
                    $options.find('.rbf-v3-qty-input').val(0).trigger('change');
                }
            }
        }

        /**
         * Gérer les options des frites (checkboxes)
         */
        handleFritesOptionToggle($checkbox) {
            const $row = $checkbox.closest('.rbf-v3-option-row');
            const $quantitySelector = $row.find('.rbf-v3-quantity-selector');
            const $input = $quantitySelector.find('.rbf-v3-qty-input');
            
            if ($checkbox.is(':checked')) {
                $quantitySelector.show();
                // Mettre au minimum 1 si c'est coché
                if (parseInt($input.val()) === 0) {
                    $input.val(1).trigger('change');
                }
            } else {
                $quantitySelector.hide();
                $input.val(0).trigger('change');
            }
            
            this.updateQuantityButtons($input);
            this.validateFritesOptions();
        }

        /**
         * Initialiser tous les sélecteurs de quantité
         */
        initializeQuantitySelectors() {
            this.container.find('.rbf-v3-qty-input').each((index, input) => {
                this.updateQuantityButtons($(input));
            });

            // Gestion des radio buttons pour les buffets
            this.container.find('input[name="buffet_type"]').off('change').on('change', (e) => {
                const selectedType = $(e.currentTarget).val();
                this.showBuffetSections(selectedType);
            });
        }

        /**
         * Afficher les sections de buffet selon le type sélectionné
         */
        showBuffetSections(selectedType) {
            // Masquer toutes les sections
            this.container.find('.rbf-v3-buffet-section').hide();

            // Afficher les sections selon le choix
            if (selectedType === 'sale') {
                this.container.find('[data-buffet-type="sale"]').show();
            } else if (selectedType === 'sucre') {
                this.container.find('[data-buffet-type="sucre"]').show();
            } else if (selectedType === 'both') {
                this.container.find('[data-buffet-type="sale"]').show();
                this.container.find('[data-buffet-type="sucre"]').show();
            }
        }

        /**
         * Valider une étape avant de passer à la suivante
         */
        validateStep(stepNumber) {
            this.log('Validation de l\'étape:', stepNumber);
            
            switch (stepNumber) {
                case 0:
                    this.log('Étape 0: Validation du service sélectionné');
                    return this.selectedService !== null;
                case 1:
                    this.log('Étape 1: Validation automatique (étape informative)');
                    return true; // Étape informative
                case 2:
                    this.log('Étape 2: Validation des champs de base');
                    return this.validateStep2();
                case 3:
                    this.log('Étape 3: Validation des formules repas');
                    return this.validateStep3();
                case 4:
                    this.log('Étape 4: Validation des buffets');
                    return this.validateStep4();
                case 5:
                    this.log('Étape 5: Validation des boissons (optionnel)');
                    return this.validateStep5();
                case 6:
                    this.log('Étape 6: Validation des coordonnées');
                    return this.validateStep6();
                default:
                    this.log('Étape inconnue, validation automatique');
                    return true;
            }
        }

        /**
         * Valider l'étape 2 (forfait de base)
         */
        validateStep2() {
            const requiredFields = ['guest_count', 'event_date', 'event_duration'];
            let isValid = true;
            let errors = [];

            requiredFields.forEach(fieldName => {
                const $field = this.container.find(`[name="${fieldName}"]`);
                const value = $field.val();
                
                if (!value || value.trim() === '') {
                    isValid = false;
                    const errorMessage = this.getFieldErrorMessage($field);
                    errors.push(errorMessage);
                    $field.addClass('rbf-v3-error');
                } else {
                    $field.removeClass('rbf-v3-error');
                }
            });

            // Validation du nombre de convives
            const guestCount = parseInt(this.container.find('[name="guest_count"]').val()) || 0;
            const minGuests = this.selectedService === 'restaurant' ? 10 : 20;
            const maxGuests = this.selectedService === 'restaurant' ? 30 : 999;

            if (guestCount < minGuests) {
                isValid = false;
                errors.push(`Minimum ${minGuests} convives requis pour ${this.selectedService}`);
            } else if (guestCount > maxGuests) {
                isValid = false;
                errors.push(`Maximum ${maxGuests} convives pour ${this.selectedService}`);
            }

            if (!isValid) {
                this.showMessage(errors.join('<br>'), 'error');
            } else {
                // Effacer les messages d'erreur si la validation réussit
                this.hideMessage();
            }

            return isValid;
        }

        /**
         * Valider l'étape 3 (formules repas)
         */
        validateStep3() {
            const guestCount = parseInt(this.container.find('[name="guest_count"]').val()) || 0;
            let isValid = true;
            let errors = [];

            this.log('Validation étape 3 - Nombre de convives:', guestCount);

            // Vérifier plats signature
            const signatureType = this.container.find('input[name="signature_type"]:checked').val();
            if (!signatureType) {
                isValid = false;
                errors.push('🍽️ Veuillez sélectionner un type de plat signature (DOG ou CROQ).');
            } else {
                let totalSignatureQty = 0;
                const signatureInputs = this.container.find('input[name^="signature_"][name$="_qty"]');
                this.log('Champs signature trouvés:', signatureInputs.length);
                signatureInputs.each((index, input) => {
                    const qty = parseInt($(input).val()) || 0;
                    totalSignatureQty += qty;
                    this.log(`Plat signature ${index} (${$(input).attr('name')}):`, qty);
                });

                this.log('Total plats signature:', totalSignatureQty);

                if (totalSignatureQty < guestCount) {
                    isValid = false;
                    errors.push(`🍽️ Sélection obligatoire pour ${guestCount} convives. Actuellement ${totalSignatureQty} plats sélectionnés.`);
                }
            }

            // Vérifier accompagnements
            let totalAccompanimentQty = 0;
            const accompanimentInputs = this.container.find('input[name^="accompaniment_"][name$="_qty"]');
            this.log('Champs accompagnement trouvés:', accompanimentInputs.length);
            accompanimentInputs.each((index, input) => {
                const $input = $(input);
                const $checkbox = $input.closest('.rbf-v3-accompaniment-item').find('input[name^="accompaniment_"][name$="_enabled"]');
                
                // Vérifier si l'accompagnement est activé (checkbox cochée)
                if ($checkbox.length === 0 || $checkbox.is(':checked')) {
                    const qty = parseInt($input.val()) || 0;
                    totalAccompanimentQty += qty;
                    this.log(`Accompagnement ${index} (${$input.attr('name')}):`, qty, 'checkbox:', $checkbox.is(':checked'));
                }
            });

            this.log('Total accompagnements:', totalAccompanimentQty);

            if (totalAccompanimentQty < guestCount) {
                isValid = false;
                errors.push(`🥗 Minimum 1 accompagnement par personne requis. Actuellement ${totalAccompanimentQty} sélectionnés pour ${guestCount} convives.`);
            }

            this.log('Validation étape 3 - Résultat:', { isValid, errors });

            if (!isValid) {
                this.showMessage(errors.join('<br>'), 'error');
            } else {
                // Effacer les messages d'erreur si la validation réussit
                this.hideMessage();
            }

            return isValid;
        }

        /**
         * Valider l'étape 4 (buffets)
         */
        validateStep4() {
            const guestCount = parseInt(this.container.find('[name="guest_count"]').val()) || 0;
            let isValid = true;
            let errors = [];

            this.log('Validation étape 4 - Nombre de convives:', guestCount);

            // Vérifier si au moins un buffet est sélectionné
            const buffetType = this.container.find('input[name="buffet_type"]:checked').val();
            this.log('Type de buffet sélectionné:', buffetType);
            
            // Les buffets sont optionnels - si aucun buffet n'est sélectionné ou si "none" est choisi, on peut passer à l'étape suivante
            if (!buffetType || buffetType === 'none') {
                this.log('Aucun buffet sélectionné - étape valide (optionnel)');
                this.hideMessage();
                return true;
            }

            // Vérifier les quantités selon le type de buffet sélectionné
            if (buffetType === 'sale' || buffetType === 'both') {
                // Buffet salé : min 1/personne ET min 2 recettes différentes
                let totalSaleQty = 0;
                let saleRecipes = 0;
                const saleInputs = this.container.find('input[name^="buffet_sale_"][name$="_qty"]');
                
                saleInputs.each((index, input) => {
                    const qty = parseInt($(input).val()) || 0;
                    if (qty > 0) {
                        saleRecipes++;
                        totalSaleQty += qty;
                    }
                });

                this.log('Validation buffet salé:', { totalSaleQty, saleRecipes, guestCount });

                if (totalSaleQty < guestCount) {
                    isValid = false;
                    errors.push(`🥗 Buffet salé : minimum 1 par personne requis. Actuellement ${totalSaleQty} pour ${guestCount} convives.`);
                }

                if (saleRecipes < 2) {
                    isValid = false;
                    errors.push('🥗 Buffet salé : minimum 2 recettes différentes requises.');
                }
            }

            if (buffetType === 'sucre' || buffetType === 'both') {
                // Buffet sucré : min 1/personne ET min 1 plat
                let totalSucreQty = 0;
                let sucreRecipes = 0;
                const sucreInputs = this.container.find('input[name^="buffet_sucre_"][name$="_qty"]');
                
                sucreInputs.each((index, input) => {
                    const qty = parseInt($(input).val()) || 0;
                    if (qty > 0) {
                        sucreRecipes++;
                        totalSucreQty += qty;
                    }
                });

                this.log('Validation buffet sucré:', { totalSucreQty, sucreRecipes, guestCount });

                if (totalSucreQty < guestCount) {
                    isValid = false;
                    errors.push(`🍰 Buffet sucré : minimum 1 par personne requis. Actuellement ${totalSucreQty} pour ${guestCount} convives.`);
                }

                if (sucreRecipes < 1) {
                    isValid = false;
                    errors.push('🍰 Buffet sucré : minimum 1 plat requis.');
                }
            }

            this.log('Validation étape 4 - Résultat:', { isValid, errors });

            if (!isValid) {
                this.showMessage(errors.join('<br>'), 'error');
            } else {
                this.hideMessage();
            }

            return isValid;
        }

        /**
         * Valider l'étape 5 (boissons - optionnel)
         */
        validateStep5() {
            // L'étape 5 est optionnelle, donc toujours valide
            this.log('Validation étape 5 - Étape optionnelle, validation automatique');
            this.hideMessage();
            return true;
        }

        /**
         * Valider l'étape 6 (coordonnées)
         */
        validateStep6() {
            let isValid = true;
            let errors = [];

            this.log('Validation étape 6 - Coordonnées');

            // Champs obligatoires
            const requiredFields = [
                { name: 'client_firstname', label: 'Prénom' },
                { name: 'client_name', label: 'Nom' },
                { name: 'client_email', label: 'Email' },
                { name: 'client_phone', label: 'Téléphone' }
            ];

            requiredFields.forEach(field => {
                const $field = this.container.find(`[name="${field.name}"]`);
                const value = $field.val();
                if (!value || value.trim() === '') {
                    isValid = false;
                    errors.push(`👤 ${field.label} est obligatoire.`);
                }
            });

            // Validation email
            const $emailField = this.container.find('[name="client_email"]');
            const email = $emailField.val();
            if (email && email.trim() && !this.isValidEmail(email.trim())) {
                isValid = false;
                errors.push('📧 Format d\'email invalide.');
            }

            // Validation téléphone
            const $phoneField = this.container.find('[name="client_phone"]');
            const phone = $phoneField.val();
            if (phone && phone.trim() && !this.isValidPhone(phone.trim())) {
                isValid = false;
                errors.push('📞 Format de téléphone invalide.');
            }

            this.log('Validation étape 6 - Résultat:', { isValid, errors });

            if (!isValid) {
                this.showMessage(errors.join('<br>'), 'error');
            } else {
                this.hideMessage();
            }

            return isValid;
        }

        /**
         * Logger pour debug
         */
        log(message, data = null) {
            if (window.console && console.log) {
                console.log(`[RBF V3] ${message}`, data);
            }
        }
    }

    /**
     * Initialisation automatique
     */
    $(document).ready(function() {
        $('.rbf-v3-container').each(function() {
            new RestaurantBookingFormV3(this);
        });
    });

    // Exposer la classe globalement pour debug
    window.RestaurantBookingFormV3 = RestaurantBookingFormV3;

})(jQuery);
