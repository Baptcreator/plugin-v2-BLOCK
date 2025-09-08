/**
 * JavaScript - Formulaire de Devis Block Unifié V2
 * Structure exacte selon cahier des charges
 * Connexion complète aux Options Unifiées
 * 
 * @package RestaurantBooking
 * @version 2.0.0
 */

(function($) {
    'use strict';

    /**
     * Classe principale du formulaire Block Unifié
     */
    class RestaurantPluginFormBlock {
        constructor(container) {
            this.container = $(container);
            this.widgetId = this.container.attr('id');
            this.config = this.container.data('config') || {};
            this.options = this.config.options || {};
            
            // État du formulaire
            this.currentStep = 0;
            this.selectedService = null;
            this.formData = {};
            this.totalSteps = 0;
            
            // Éléments DOM
            this.progressBar = this.container.find('#progress-bar');
            this.navigation = this.container.find('#form-navigation');
            this.calculator = this.container.find('#price-calculator');
            this.messages = this.container.find('#form-messages');
            this.dynamicSteps = this.container.find('.restaurant-plugin-dynamic-steps');
            
            // Boutons de navigation
            this.prevButton = this.container.find('#prev-button');
            this.nextButton = this.container.find('#next-button');
            this.currentStepText = this.container.find('#current-step-text');
            
            // Prix
            this.priceBase = this.container.find('#price-base');
            this.priceSupplements = this.container.find('#price-supplements');
            this.priceProducts = this.container.find('#price-products');
            this.priceTotal = this.container.find('#price-total');
            
            this.init();
        }

        /**
         * Initialisation
         */
        init() {
            this.bindEvents();
            this.log('Formulaire Block Unifié initialisé', this.config);
        }

        /**
         * Liaison des événements
         */
        bindEvents() {
            // Navigation
            this.prevButton.on('click', () => this.goToPreviousStep());
            this.nextButton.on('click', () => this.goToNextStep());
            
            // Sélection de service (global)
            window.restaurantPluginSelectService = (service) => this.selectService(service);
            
            // Mise à jour du prix en temps réel
            this.container.on('change input', 'input, select, textarea', () => {
                this.updateFormData();
                this.updatePrice();
            });
            
            // Validation en temps réel
            this.container.on('blur', 'input[required], select[required], textarea[required]', (e) => {
                this.validateField($(e.target));
            });
        }

        /**
         * Sélectionner un service selon le cahier des charges
         */
        selectService(service) {
            this.selectedService = service;
            this.formData.service_type = service;
            
            // Définir le nombre total d'étapes selon le service
            this.totalSteps = (service === 'restaurant') ? 6 : 7;
            
            // Marquer la card comme sélectionnée
            this.container.find('.restaurant-plugin-service-card').removeClass('selected');
            this.container.find(`.restaurant-plugin-service-card[data-service="${service}"]`).addClass('selected');
            
            // Charger la première étape du service
            this.loadStep(1);
            
            this.log(`Service sélectionné: ${service} (${this.totalSteps} étapes)`);
        }

        /**
         * Charger une étape dynamiquement
         */
        loadStep(stepNumber) {
            if (!this.selectedService) {
                this.showError('Veuillez d\'abord sélectionner un service');
                return;
            }

            this.showLoading();

            const data = {
                action: 'restaurant_plugin_load_step',
                nonce: restaurantPluginAjax.nonce,
                service_type: this.selectedService,
                step: stepNumber,
                form_data: this.formData
            };

            $.ajax({
                url: restaurantPluginAjax.ajax_url,
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
                    this.showError(restaurantPluginAjax.texts.error_network);
                }
            });
        }

        /**
         * Rendre une étape selon le cahier des charges
         */
        renderStep(stepData) {
            const stepHtml = this.buildStepHtml(stepData);
            
            // Ajouter l'étape au conteneur dynamique
            this.dynamicSteps.append(stepHtml);
            
            // Initialiser les composants de l'étape
            this.initializeStepComponents(stepData.step_number);
            
            this.log('Étape rendue:', stepData);
        }

        /**
         * Construire le HTML d'une étape
         */
        buildStepHtml(stepData) {
            const isActive = stepData.step_number === 1 ? 'active' : '';
            
            return `
                <div class="restaurant-plugin-form-step ${isActive}" data-step="${stepData.step_number}">
                    <div class="restaurant-plugin-step-header">
                        <h2 class="restaurant-plugin-step-title">${stepData.title}</h2>
                        ${stepData.subtitle ? `<p class="restaurant-plugin-text">${stepData.subtitle}</p>` : ''}
                    </div>
                    <div class="restaurant-plugin-step-content">
                        ${stepData.content}
                    </div>
                </div>
            `;
        }

        /**
         * Initialiser les composants d'une étape
         */
        initializeStepComponents(stepNumber) {
            const stepElement = this.container.find(`[data-step="${stepNumber}"]`);
            
            // Initialiser selon le type d'étape
            switch (stepNumber) {
                case 1:
                    this.initializeStep1(stepElement);
                    break;
                case 2:
                    this.initializeStep2(stepElement);
                    break;
                case 3:
                    this.initializeStep3(stepElement);
                    break;
                case 4:
                    this.initializeStep4(stepElement);
                    break;
                case 5:
                    this.initializeStep5(stepElement);
                    break;
                case 6:
                    this.initializeStep6(stepElement);
                    break;
                case 7:
                    this.initializeStep7(stepElement);
                    break;
            }
            
            // Initialiser les composants communs
            this.initializeCommonComponents(stepElement);
        }

        /**
         * Étape 1: Pourquoi privatiser (selon cahier des charges)
         */
        initializeStep1(stepElement) {
            // Bouton "COMMENCER MON DEVIS"
            stepElement.find('.start-quote-button').on('click', () => {
                this.goToNextStep();
            });
        }

        /**
         * Étape 2: Forfait de base (selon cahier des charges)
         */
        initializeStep2(stepElement) {
            // Date picker avec vérification disponibilité
            this.initializeDatePicker(stepElement);
            
            // Validation nombre de convives selon service
            this.initializeGuestValidation(stepElement);
            
            // Durée avec supplément automatique
            this.initializeDurationSelector(stepElement);
            
            // Code postal pour remorque avec calcul distance
            if (this.selectedService === 'remorque') {
                this.initializePostalCodeValidator(stepElement);
            }
            
            // Afficher le calculateur de prix à partir de cette étape
            this.showPriceCalculator();
        }

        /**
         * Étape 3: Formules repas (selon cahier des charges)
         */
        initializeStep3(stepElement) {
            // Sélecteur plat signature DOG/CROQ
            this.initializeSignatureSelector(stepElement);
            
            // Menu Mini Boss (optionnel)
            this.initializeMiniBossSelector(stepElement);
            
            // Accompagnements avec validation min 1/personne
            this.initializeAccompanimentSelector(stepElement);
        }

        /**
         * Étape 4: Buffets (selon cahier des charges)
         */
        initializeStep4(stepElement) {
            // 3 choix: salé, sucré, ou les deux
            this.initializeBuffetSelector(stepElement);
        }

        /**
         * Étape 5: Boissons (selon cahier des charges)
         */
        initializeStep5(stepElement) {
            // Sections dépliables par catégorie
            this.initializeBeverageCategories(stepElement);
            
            // Fûts uniquement pour restaurant
            if (this.selectedService === 'restaurant') {
                this.initializeKegsSelection(stepElement);
            }
        }

        /**
         * Étape 6: Options remorque OU Coordonnées restaurant
         */
        initializeStep6(stepElement) {
            if (this.selectedService === 'remorque') {
                // Options: TIREUSE 50€ + JEUX 70€
                this.initializeRemorqueOptions(stepElement);
            } else {
                // Coordonnées restaurant
                this.initializeContactForm(stepElement);
            }
        }

        /**
         * Étape 7: Coordonnées remorque (seulement pour remorque)
         */
        initializeStep7(stepElement) {
            this.initializeContactForm(stepElement);
        }

        /**
         * Initialiser le sélecteur de date avec API Google
         */
        initializeDatePicker(stepElement) {
            const dateInput = stepElement.find('input[name="event_date"]');
            
            dateInput.on('change', (e) => {
                const selectedDate = e.target.value;
                if (selectedDate) {
                    this.checkDateAvailability(selectedDate);
                }
            });
        }

        /**
         * Vérifier la disponibilité d'une date
         */
        checkDateAvailability(date) {
            const data = {
                action: 'restaurant_plugin_check_date',
                nonce: restaurantPluginAjax.nonce,
                date: date,
                service_type: this.selectedService
            };

            $.ajax({
                url: restaurantPluginAjax.ajax_url,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        if (!response.data.available) {
                            this.showWarning('Cette date n\'est pas disponible. Veuillez en choisir une autre.');
                            this.container.find('input[name="event_date"]').val('').addClass('error');
                        } else {
                            this.showSuccess('Date disponible !');
                            this.container.find('input[name="event_date"]').removeClass('error').addClass('success');
                        }
                    }
                }
            });
        }

        /**
         * Initialiser la validation des convives selon les règles
         */
        initializeGuestValidation(stepElement) {
            const guestInput = stepElement.find('input[name="guest_count"]');
            const minGuests = this.options[`${this.selectedService}_min_guests`] || 10;
            const maxGuests = this.options[`${this.selectedService}_max_guests`] || 30;
            
            guestInput.attr('min', minGuests);
            guestInput.attr('max', maxGuests);
            
            guestInput.on('input', (e) => {
                const value = parseInt(e.target.value);
                const input = $(e.target);
                
                input.removeClass('error success');
                
                if (value < minGuests) {
                    input.addClass('error');
                    this.showFieldError(input, `Minimum ${minGuests} convives requis`);
                } else if (value > maxGuests) {
                    input.addClass('error');
                    this.showFieldError(input, `Maximum ${maxGuests} convives autorisé`);
                } else {
                    input.addClass('success');
                    this.hideFieldError(input);
                }
            });
        }

        /**
         * Initialiser le sélecteur de durée avec supplément
         */
        initializeDurationSelector(stepElement) {
            const durationSelect = stepElement.find('select[name="event_duration"]');
            const extraHourPrice = this.options[`${this.selectedService}_extra_hour_price`] || 50;
            const includedHours = this.options[`${this.selectedService}_max_duration_included`] || 2;
            
            durationSelect.on('change', (e) => {
                const selectedHours = parseInt(e.target.value);
                const extraHours = Math.max(0, selectedHours - includedHours);
                const supplement = extraHours * extraHourPrice;
                
                // Mettre à jour l'affichage du supplément
                const supplementText = stepElement.find('.duration-supplement-text');
                if (extraHours > 0) {
                    supplementText.text(`Supplément: +${supplement}€ (${extraHours}h × ${extraHourPrice}€)`).show();
                } else {
                    supplementText.hide();
                }
                
                this.formData.duration_supplement = supplement;
                this.updatePrice();
            });
        }

        /**
         * Initialiser le validateur de code postal avec calcul distance
         */
        initializePostalCodeValidator(stepElement) {
            const postalInput = stepElement.find('input[name="postal_code"]');
            
            postalInput.on('blur', (e) => {
                const postalCode = e.target.value;
                if (postalCode && postalCode.length === 5) {
                    this.calculateDeliveryDistance(postalCode);
                }
            });
        }

        /**
         * Calculer la distance de livraison
         */
        calculateDeliveryDistance(postalCode) {
            const data = {
                action: 'restaurant_plugin_calculate_distance',
                nonce: restaurantPluginAjax.nonce,
                postal_code: postalCode,
                service_type: this.selectedService
            };

            $.ajax({
                url: restaurantPluginAjax.ajax_url,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        const distance = response.data.distance;
                        const supplement = response.data.supplement;
                        const zone = response.data.zone;
                        
                        this.formData.delivery_distance = distance;
                        this.formData.delivery_supplement = supplement;
                        this.formData.delivery_zone = zone;
                        
                        // Afficher le supplément
                        const supplementText = this.container.find('.delivery-supplement-text');
                        if (supplement > 0) {
                            supplementText.text(`Supplément livraison: +${supplement}€ (${zone})`).show();
                        } else {
                            supplementText.text('Livraison gratuite (zone locale)').show();
                        }
                        
                        this.updatePrice();
                    } else {
                        this.showError('Code postal non reconnu ou hors zone de livraison');
                    }
                }
            });
        }

        /**
         * Initialiser le sélecteur de plats signature DOG/CROQ
         */
        initializeSignatureSelector(stepElement) {
            const signatureRadios = stepElement.find('input[name="signature_type"]');
            
            signatureRadios.on('change', (e) => {
                const selectedType = e.target.value;
                this.loadSignatureProducts(selectedType);
            });
        }

        /**
         * Charger les produits signature selon le type
         */
        loadSignatureProducts(signatureType) {
            const container = this.container.find('.signature-products-container');
            container.html('<div class="restaurant-plugin-loading">Chargement des produits...</div>');
            
            const data = {
                action: 'restaurant_plugin_get_signature_products',
                nonce: restaurantPluginAjax.nonce,
                signature_type: signatureType,
                service_type: this.selectedService
            };

            $.ajax({
                url: restaurantPluginAjax.ajax_url,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        this.renderSignatureProducts(response.data.products, signatureType);
                    } else {
                        container.html('<p>Aucun produit disponible pour cette sélection.</p>');
                    }
                },
                error: () => {
                    container.html('<p>Erreur lors du chargement des produits.</p>');
                }
            });
        }

        /**
         * Rendre les produits signature avec validation min 1/personne
         */
        renderSignatureProducts(products, signatureType) {
            const container = this.container.find('.signature-products-container');
            const guestCount = parseInt(this.formData.guest_count) || 10;
            const minRequired = this.options.signature_dish_min_per_person || 1;
            const totalMinRequired = guestCount * minRequired;
            
            let html = '<div class="restaurant-plugin-products-grid">';
            
            products.forEach(product => {
                html += `
                    <div class="restaurant-plugin-product-card" data-product-id="${product.id}">
                        <div class="product-image">
                            ${product.image ? `<img src="${product.image}" alt="${product.name}">` : '<div class="product-placeholder">📷</div>'}
                        </div>
                        <div class="product-content">
                            <h4 class="product-title">${product.name}</h4>
                            <p class="product-description">${product.description || ''}</p>
                            <div class="product-price">${this.formatPrice(product.price)}</div>
                        </div>
                        <div class="product-quantity-selector">
                            <button type="button" class="qty-btn qty-minus" data-target="signature_${product.id}">-</button>
                            <input type="number" 
                                   class="qty-input" 
                                   id="signature_${product.id}" 
                                   name="products[signature][${product.id}]" 
                                   value="0" 
                                   min="0" 
                                   max="${guestCount * 2}" 
                                   data-product-id="${product.id}"
                                   data-category="signature">
                            <button type="button" class="qty-btn qty-plus" data-target="signature_${product.id}">+</button>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            html += `
                <div class="restaurant-plugin-validation-info">
                    <p><strong>Règle:</strong> Minimum ${totalMinRequired} plats signature requis (${minRequired} par personne × ${guestCount} convives)</p>
                    <div class="signature-counter">
                        <span>Sélectionnés: </span>
                        <span class="signature-count">0</span>
                        <span> / ${totalMinRequired}</span>
                    </div>
                </div>
            `;
            
            container.html(html);
            
            // Initialiser les sélecteurs de quantité
            this.initializeQuantitySelectors(container);
            
            // Validation en temps réel
            this.initializeSignatureValidation(totalMinRequired);
        }

        /**
         * Initialiser la validation des plats signature
         */
        initializeSignatureValidation(minRequired) {
            this.container.on('change input', 'input[data-category="signature"]', () => {
                let total = 0;
                this.container.find('input[data-category="signature"]').each(function() {
                    total += parseInt($(this).val()) || 0;
                });
                
                const counter = this.container.find('.signature-count');
                const validationInfo = this.container.find('.restaurant-plugin-validation-info');
                
                counter.text(total);
                
                if (total >= minRequired) {
                    validationInfo.removeClass('error').addClass('success');
                } else {
                    validationInfo.removeClass('success').addClass('error');
                }
                
                this.formData.signature_products_valid = (total >= minRequired);
            });
        }

        /**
         * Initialiser les sélecteurs de quantité
         */
        initializeQuantitySelectors(container) {
            container.find('.qty-minus').on('click', (e) => {
                const target = $(e.target).data('target');
                const input = $(`#${target}`);
                const currentVal = parseInt(input.val()) || 0;
                if (currentVal > 0) {
                    input.val(currentVal - 1).trigger('change');
                }
            });
            
            container.find('.qty-plus').on('click', (e) => {
                const target = $(e.target).data('target');
                const input = $(`#${target}`);
                const currentVal = parseInt(input.val()) || 0;
                const maxVal = parseInt(input.attr('max')) || 999;
                if (currentVal < maxVal) {
                    input.val(currentVal + 1).trigger('change');
                }
            });
        }

        /**
         * Initialiser les options spécifiques remorque
         */
        initializeRemorqueOptions(stepElement) {
            // Option TIREUSE 50€
            const tireuse = stepElement.find('input[name="option_tireuse"]');
            const tireusePrice = this.options.tireuse_price || 50;
            
            tireuse.on('change', (e) => {
                if (e.target.checked) {
                    this.formData.option_tireuse = true;
                    this.formData.tireuse_price = tireusePrice;
                    // Afficher la sélection de fûts
                    this.showKegsSelection();
                } else {
                    this.formData.option_tireuse = false;
                    this.formData.tireuse_price = 0;
                    this.hideKegsSelection();
                }
                this.updatePrice();
            });
            
            // Option JEUX 70€
            const games = stepElement.find('input[name="option_games"]');
            const gamesPrice = this.options.games_price || 70;
            
            games.on('change', (e) => {
                if (e.target.checked) {
                    this.formData.option_games = true;
                    this.formData.games_price = gamesPrice;
                    // Afficher la sélection de jeux
                    this.showGamesSelection();
                } else {
                    this.formData.option_games = false;
                    this.formData.games_price = 0;
                    this.hideGamesSelection();
                }
                this.updatePrice();
            });
        }

        /**
         * Initialiser le formulaire de contact
         */
        initializeContactForm(stepElement) {
            const submitButton = stepElement.find('.submit-quote-button');
            
            submitButton.on('click', (e) => {
                e.preventDefault();
                this.submitQuote();
            });
        }

        /**
         * Navigation entre les étapes
         */
        goToStep(stepNumber) {
            // Masquer toutes les étapes
            this.container.find('.restaurant-plugin-form-step').removeClass('active');
            
            // Afficher l'étape demandée
            this.container.find(`[data-step="${stepNumber}"]`).addClass('active');
            
            // Mettre à jour l'état
            this.currentStep = stepNumber;
            
            // Mettre à jour la navigation
            this.updateNavigation();
            
            // Mettre à jour la barre de progression
            this.updateProgressBar();
            
            this.log(`Navigation vers l'étape ${stepNumber}`);
        }

        goToNextStep() {
            if (this.validateCurrentStep()) {
                if (this.currentStep < this.totalSteps) {
                    this.loadStep(this.currentStep + 1);
                }
            }
        }

        goToPreviousStep() {
            if (this.currentStep > 1) {
                this.goToStep(this.currentStep - 1);
            } else if (this.currentStep === 1) {
                // Retour à la sélection de service
                this.goToStep(0);
                this.hidePriceCalculator();
            }
        }

        /**
         * Valider l'étape actuelle selon les règles du cahier des charges
         */
        validateCurrentStep() {
            const currentStepElement = this.container.find(`[data-step="${this.currentStep}"]`);
            let isValid = true;
            
            // Validation des champs requis
            currentStepElement.find('input[required], select[required], textarea[required]').each((index, field) => {
                if (!this.validateField($(field))) {
                    isValid = false;
                }
            });
            
            // Validations spécifiques par étape
            switch (this.currentStep) {
                case 3: // Formules repas
                    if (!this.validateStep3()) {
                        isValid = false;
                    }
                    break;
                case 4: // Buffets
                    if (!this.validateStep4()) {
                        isValid = false;
                    }
                    break;
            }
            
            if (!isValid) {
                this.showError(restaurantPluginAjax.texts.step_validation_error);
            }
            
            return isValid;
        }

        /**
         * Valider l'étape 3 (formules repas)
         */
        validateStep3() {
            // Vérifier plats signature
            if (!this.formData.signature_products_valid) {
                this.showError('Veuillez sélectionner le nombre minimum de plats signature requis');
                return false;
            }
            
            // Vérifier accompagnements (min 1/personne)
            const guestCount = parseInt(this.formData.guest_count) || 10;
            let accompanimentTotal = 0;
            
            this.container.find('input[data-category="accompaniments"]').each(function() {
                accompanimentTotal += parseInt($(this).val()) || 0;
            });
            
            if (accompanimentTotal < guestCount) {
                this.showError(`Veuillez sélectionner au moins ${guestCount} accompagnements (1 par personne)`);
                return false;
            }
            
            return true;
        }

        /**
         * Valider l'étape 4 (buffets)
         */
        validateStep4() {
            const selectedBuffetType = this.container.find('input[name="buffet_type"]:checked').val();
            
            if (!selectedBuffetType || selectedBuffetType === 'none') {
                return true; // Les buffets sont optionnels
            }
            
            // Validation buffet salé
            if (selectedBuffetType === 'sale' || selectedBuffetType === 'both') {
                if (!this.validateBuffetSale()) {
                    return false;
                }
            }
            
            // Validation buffet sucré
            if (selectedBuffetType === 'sucre' || selectedBuffetType === 'both') {
                if (!this.validateBuffetSucre()) {
                    return false;
                }
            }
            
            return true;
        }

        /**
         * Valider le buffet salé (min 1/pers + min 2 recettes)
         */
        validateBuffetSale() {
            const guestCount = parseInt(this.formData.guest_count) || 10;
            const minPerPerson = this.options.buffet_sale_min_per_person || 1;
            const minRecipes = this.options.buffet_sale_min_recipes || 2;
            
            let totalQuantity = 0;
            let selectedRecipes = 0;
            
            this.container.find('input[data-category="buffet-sale"]').each(function() {
                const quantity = parseInt($(this).val()) || 0;
                if (quantity > 0) {
                    totalQuantity += quantity;
                    selectedRecipes++;
                }
            });
            
            const minTotalQuantity = guestCount * minPerPerson;
            
            if (totalQuantity < minTotalQuantity) {
                this.showError(`Buffet salé: minimum ${minTotalQuantity} portions requises (${minPerPerson} par personne)`);
                return false;
            }
            
            if (selectedRecipes < minRecipes) {
                this.showError(`Buffet salé: minimum ${minRecipes} recettes différentes requises`);
                return false;
            }
            
            return true;
        }

        /**
         * Valider le buffet sucré (min 1/pers + min 1 plat)
         */
        validateBuffetSucre() {
            const guestCount = parseInt(this.formData.guest_count) || 10;
            const minPerPerson = this.options.buffet_sucre_min_per_person || 1;
            const minDishes = this.options.buffet_sucre_min_dishes || 1;
            
            let totalQuantity = 0;
            let selectedDishes = 0;
            
            this.container.find('input[data-category="buffet-sucre"]').each(function() {
                const quantity = parseInt($(this).val()) || 0;
                if (quantity > 0) {
                    totalQuantity += quantity;
                    selectedDishes++;
                }
            });
            
            const minTotalQuantity = guestCount * minPerPerson;
            
            if (totalQuantity < minTotalQuantity) {
                this.showError(`Buffet sucré: minimum ${minTotalQuantity} portions requises (${minPerPerson} par personne)`);
                return false;
            }
            
            if (selectedDishes < minDishes) {
                this.showError(`Buffet sucré: minimum ${minDishes} plat requis`);
                return false;
            }
            
            return true;
        }

        /**
         * Valider un champ individuel
         */
        validateField(field) {
            const value = field.val().trim();
            const fieldName = field.attr('name');
            let isValid = true;
            let errorMessage = '';
            
            // Supprimer les erreurs précédentes
            this.hideFieldError(field);
            field.removeClass('error success');
            
            // Validation des champs requis
            if (field.attr('required') && !value) {
                isValid = false;
                errorMessage = restaurantPluginAjax.texts.error_required;
            }
            
            // Validations spécifiques
            if (value) {
                switch (fieldName) {
                    case 'event_date':
                        if (!this.isValidDate(value)) {
                            isValid = false;
                            errorMessage = restaurantPluginAjax.texts.error_invalid_date;
                        }
                        break;
                        
                    case 'guest_count':
                        const guestCount = parseInt(value);
                        const minGuests = this.options[`${this.selectedService}_min_guests`] || 10;
                        const maxGuests = this.options[`${this.selectedService}_max_guests`] || 30;
                        
                        if (guestCount < minGuests) {
                            isValid = false;
                            errorMessage = `${restaurantPluginAjax.texts.error_min_guests} (${minGuests})`;
                        } else if (guestCount > maxGuests) {
                            isValid = false;
                            errorMessage = `${restaurantPluginAjax.texts.error_max_guests} (${maxGuests})`;
                        }
                        break;
                        
                    case 'postal_code':
                        if (this.selectedService === 'remorque' && !this.isValidPostalCode(value)) {
                            isValid = false;
                            errorMessage = 'Code postal invalide (5 chiffres requis)';
                        }
                        break;
                        
                    case 'customer_email':
                        if (!this.isValidEmail(value)) {
                            isValid = false;
                            errorMessage = 'Adresse email invalide';
                        }
                        break;
                        
                    case 'customer_phone':
                        if (!this.isValidPhone(value)) {
                            isValid = false;
                            errorMessage = 'Numéro de téléphone invalide';
                        }
                        break;
                }
            }
            
            // Afficher l'erreur ou le succès
            if (!isValid) {
                field.addClass('error');
                this.showFieldError(field, errorMessage);
            } else if (value) {
                field.addClass('success');
            }
            
            return isValid;
        }

        /**
         * Mettre à jour les données du formulaire
         */
        updateFormData() {
            const formData = {};
            
            // Collecter toutes les données du formulaire
            this.container.find('input, select, textarea').each(function() {
                const field = $(this);
                const name = field.attr('name');
                const type = field.attr('type');
                
                if (name) {
                    if (type === 'checkbox') {
                        formData[name] = field.is(':checked');
                    } else if (type === 'radio') {
                        if (field.is(':checked')) {
                            formData[name] = field.val();
                        }
                    } else {
                        const value = field.val();
                        if (value) {
                            formData[name] = value;
                        }
                    }
                }
            });
            
            // Collecter les produits sélectionnés
            this.collectProductsData(formData);
            
            // Mettre à jour les données
            Object.assign(this.formData, formData);
        }

        /**
         * Collecter les données des produits
         */
        collectProductsData(formData) {
            formData.selected_products = {};
            formData.selected_supplements = {};
            
            this.container.find('input[data-product-id]').each(function() {
                const field = $(this);
                const productId = field.data('product-id');
                const category = field.data('category');
                const quantity = parseInt(field.val()) || 0;
                
                if (quantity > 0) {
                    if (!formData.selected_products[category]) {
                        formData.selected_products[category] = {};
                    }
                    formData.selected_products[category][productId] = {
                        quantity: quantity,
                        product_id: productId
                    };
                }
            });
        }

        /**
         * Mettre à jour le prix en temps réel
         */
        updatePrice() {
            if (!this.selectedService || this.currentStep < 2) {
                return;
            }
            
            const data = {
                action: 'restaurant_plugin_calculate_price',
                nonce: restaurantPluginAjax.nonce,
                service_type: this.selectedService,
                form_data: this.formData
            };

            $.ajax({
                url: restaurantPluginAjax.ajax_url,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        this.updatePriceDisplay(response.data);
                    }
                },
                error: () => {
                    this.log('Erreur lors du calcul de prix');
                }
            });
        }

        /**
         * Mettre à jour l'affichage des prix
         */
        updatePriceDisplay(priceData) {
            this.priceBase.text(this.formatPrice(priceData.base_price || 0));
            this.priceSupplements.text(this.formatPrice(priceData.supplements_total || 0));
            this.priceProducts.text(this.formatPrice(priceData.products_total || 0));
            this.priceTotal.text(this.formatPrice(priceData.total_price || 0));
            
            // Animation du total
            this.priceTotal.addClass('updated');
            setTimeout(() => {
                this.priceTotal.removeClass('updated');
            }, 500);
        }

        /**
         * Soumettre le devis final
         */
        submitQuote() {
            if (!this.validateCurrentStep()) {
                return;
            }
            
            this.showLoading();
            
            const data = {
                action: 'restaurant_plugin_submit_quote',
                nonce: restaurantPluginAjax.nonce,
                service_type: this.selectedService,
                form_data: this.formData
            };

            $.ajax({
                url: restaurantPluginAjax.ajax_url,
                type: 'POST',
                data: data,
                success: (response) => {
                    this.hideLoading();
                    if (response.success) {
                        this.showSuccess(this.config.success_message);
                        // Masquer le formulaire et afficher le message de succès
                        this.showSuccessPage(response.data);
                    } else {
                        this.showError(response.data || 'Erreur lors de la soumission du devis');
                    }
                },
                error: () => {
                    this.hideLoading();
                    this.showError(restaurantPluginAjax.texts.network_error);
                }
            });
        }

        /**
         * Afficher la page de succès
         */
        showSuccessPage(quoteData) {
            const successHtml = `
                <div class="restaurant-plugin-success-page restaurant-plugin-text-center">
                    <div class="success-icon">✅</div>
                    <h2 class="restaurant-plugin-step-title">Devis Envoyé avec Succès !</h2>
                    <p class="restaurant-plugin-text">${this.config.success_message}</p>
                    ${quoteData.quote_id ? `<p><strong>Numéro de devis :</strong> ${quoteData.quote_id}</p>` : ''}
                    <div class="restaurant-plugin-mt-4">
                        <button type="button" class="restaurant-plugin-btn-primary" onclick="location.reload()">
                            Nouveau Devis
                        </button>
                    </div>
                </div>
            `;
            
            this.container.html(successHtml);
        }

        /**
         * Mettre à jour la navigation
         */
        updateNavigation() {
            if (this.currentStep === 0) {
                this.navigation.addClass('restaurant-plugin-hidden');
                return;
            }
            
            this.navigation.removeClass('restaurant-plugin-hidden');
            
            // Bouton précédent
            if (this.currentStep === 1) {
                this.prevButton.removeClass('hidden').text('← Choisir un service');
            } else if (this.currentStep > 1) {
                this.prevButton.removeClass('hidden').text('← Précédent');
            } else {
                this.prevButton.addClass('hidden');
            }
            
            // Bouton suivant
            if (this.currentStep === this.totalSteps) {
                this.nextButton.text('Obtenir mon devis estimatif').removeClass('restaurant-plugin-btn-primary').addClass('restaurant-plugin-btn-accent');
            } else {
                this.nextButton.text('Suivant →').removeClass('restaurant-plugin-btn-accent').addClass('restaurant-plugin-btn-primary');
            }
            
            // Indicateur d'étape
            this.currentStepText.text(`Étape ${this.currentStep} sur ${this.totalSteps}`);
        }

        /**
         * Mettre à jour la barre de progression
         */
        updateProgressBar() {
            if (!this.config.show_progress_bar) return;
            
            if (this.currentStep === 0) {
                this.progressBar.addClass('restaurant-plugin-hidden');
                return;
            }
            
            this.progressBar.removeClass('restaurant-plugin-hidden');
            
            // Générer les étapes si nécessaire
            if (this.progressBar.find('.restaurant-plugin-progress-step').length === 0) {
                this.generateProgressSteps();
            }
            
            // Mettre à jour les états
            this.progressBar.find('.restaurant-plugin-progress-step').each((index, step) => {
                const stepNumber = index + 1;
                const stepElement = $(step);
                
                stepElement.removeClass('active completed');
                
                if (stepNumber < this.currentStep) {
                    stepElement.addClass('completed');
                } else if (stepNumber === this.currentStep) {
                    stepElement.addClass('active');
                }
            });
            
            // Mettre à jour la ligne de progression
            const progressPercent = ((this.currentStep - 1) / (this.totalSteps - 1)) * 100;
            this.progressBar.find('.restaurant-plugin-progress-line-fill').css('width', `${progressPercent}%`);
        }

        /**
         * Générer les étapes de la barre de progression
         */
        generateProgressSteps() {
            const stepsContainer = this.progressBar.find('.restaurant-plugin-progress-steps');
            let stepsHtml = '<div class="restaurant-plugin-progress-line"><div class="restaurant-plugin-progress-line-fill"></div></div>';
            
            for (let i = 1; i <= this.totalSteps; i++) {
                stepsHtml += `<div class="restaurant-plugin-progress-step">${i}</div>`;
            }
            
            stepsContainer.html(stepsHtml);
        }

        /**
         * Afficher/masquer le calculateur de prix
         */
        showPriceCalculator() {
            this.calculator.removeClass('restaurant-plugin-hidden');
        }

        hidePriceCalculator() {
            this.calculator.addClass('restaurant-plugin-hidden');
        }

        /**
         * Gestion des messages
         */
        showMessage(message, type = 'info') {
            const messageElement = this.messages.find(`#${type}-message .message-text`);
            messageElement.text(message);
            
            this.messages.removeClass('restaurant-plugin-hidden');
            this.messages.find('.restaurant-plugin-message').addClass('restaurant-plugin-hidden');
            this.messages.find(`#${type}-message`).removeClass('restaurant-plugin-hidden');
            
            // Auto-masquer après 5 secondes
            setTimeout(() => {
                this.hideMessages();
            }, 5000);
        }

        showSuccess(message) { this.showMessage(message, 'success'); }
        showError(message) { this.showMessage(message, 'error'); }
        showWarning(message) { this.showMessage(message, 'warning'); }
        showInfo(message) { this.showMessage(message, 'info'); }

        hideMessages() {
            this.messages.addClass('restaurant-plugin-hidden');
        }

        showFieldError(field, message) {
            this.hideFieldError(field);
            field.after(`<span class="restaurant-plugin-field-error">${message}</span>`);
        }

        hideFieldError(field) {
            field.siblings('.restaurant-plugin-field-error').remove();
        }

        /**
         * Gestion du loading
         */
        showLoading() {
            this.container.find('#loading-overlay').removeClass('restaurant-plugin-hidden');
        }

        hideLoading() {
            this.container.find('#loading-overlay').addClass('restaurant-plugin-hidden');
        }

        /**
         * Utilitaires
         */
        formatPrice(price) {
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'EUR'
            }).format(price);
        }

        isValidDate(dateString) {
            const date = new Date(dateString);
            return date instanceof Date && !isNaN(date) && date > new Date();
        }

        isValidPostalCode(postalCode) {
            return /^\d{5}$/.test(postalCode);
        }

        isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }

        isValidPhone(phone) {
            return /^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/.test(phone);
        }

        log(message, data = null) {
            if (this.config.debug) {
                console.log(`[Restaurant Plugin] ${message}`, data);
            }
        }

        /**
         * Initialiser les composants communs
         */
        initializeCommonComponents(stepElement) {
            // Initialiser les tooltips
            stepElement.find('[data-tooltip]').each(function() {
                // Implémentation des tooltips si nécessaire
            });
            
            // Initialiser les validations en temps réel
            stepElement.find('input, select, textarea').on('blur change', (e) => {
                this.validateField($(e.target));
            });
        }
    }

    /**
     * Initialisation automatique
     */
    $(document).ready(function() {
        $('.restaurant-plugin-container').each(function() {
            new RestaurantPluginFormBlock(this);
        });
    });

})(jQuery);

