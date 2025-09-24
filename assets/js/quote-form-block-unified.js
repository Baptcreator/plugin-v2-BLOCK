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
            
            // Nettoyer TOUTES les étapes précédentes pour éviter les doublons
            this.container.find('.restaurant-plugin-form-step[data-step]').not('[data-step="0"]').remove();
            this.dynamicSteps.empty();
            
            // Réinitialiser l'état
            this.currentStep = 0;
            
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

            // Vérifier si l'étape existe déjà pour éviter les doublons
            const existingStep = this.container.find(`[data-step="${stepNumber}"]`);
            if (existingStep.length > 0) {
                this.goToStep(stepNumber);
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
            // Étape d'information seulement
            // La navigation se fait avec les boutons standard "Suivant"
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
         * Initialiser le sélecteur Mini Boss
         */
        initializeMiniBossSelector(stepElement) {
            const miniBossContainer = stepElement.find('.mini-boss-container');
            if (miniBossContainer.length === 0) {
                return; // Pas de section Mini Boss dans cette étape
            }
            
            const miniBossCheckbox = stepElement.find('input[name="include_mini_boss"]');
            const miniBossProducts = stepElement.find('.mini-boss-products');
            
            // Gérer l'affichage/masquage des produits Mini Boss
            miniBossCheckbox.on('change', (e) => {
                if (e.target.checked) {
                    miniBossProducts.show();
                    this.loadMiniBossProducts();
                } else {
                    miniBossProducts.hide();
                    // Réinitialiser les quantités
                    miniBossProducts.find('input[type="number"]').val(0).trigger('change');
                }
            });
            
            // Initialiser les sélecteurs de quantité pour Mini Boss
            this.initializeQuantitySelectors(miniBossContainer);
        }

        /**
         * Charger les produits Mini Boss
         */
        loadMiniBossProducts() {
            const container = this.container.find('.mini-boss-products-list');
            if (container.length === 0) {
                this.log('Conteneur Mini Boss non trouvé');
                return;
            }
            
            container.html('<div class="restaurant-plugin-loading">Chargement des produits Mini Boss...</div>');
            
            const data = {
                action: 'restaurant_plugin_get_mini_boss_products',
                nonce: restaurantPluginAjax.nonce,
                service_type: this.selectedService
            };

            $.ajax({
                url: restaurantPluginAjax.ajax_url,
                type: 'POST',
                data: data,
                timeout: 10000,
                success: (response) => {
                    if (response.success && response.data.products && response.data.products.length > 0) {
                        this.renderMiniBossProducts(response.data.products);
                        this.log('Produits Mini Boss chargés avec succès', response.data.products);
                    } else {
                        container.html('<div class="restaurant-plugin-message restaurant-plugin-message-info"><p>👶 Aucun menu enfant disponible actuellement.</p><p><small>Les menus Mini Boss sont optionnels et peuvent être configurés dans l\'administration.</small></p></div>');
                        this.log('Aucun produit Mini Boss trouvé', response);
                    }
                },
                error: (xhr, status, error) => {
                    let errorMessage = '<div class="restaurant-plugin-message restaurant-plugin-message-error">';
                    errorMessage += '<p>❌ Erreur lors du chargement des menus Mini Boss.</p>';
                    
                    if (status === 'timeout') {
                        errorMessage += '<p><small>Délai d\'attente dépassé.</small></p>';
                    } else {
                        errorMessage += '<p><small>Erreur de connexion.</small></p>';
                    }
                    
                    errorMessage += '<button type="button" class="restaurant-plugin-btn-secondary" onclick="this.closest(\'.mini-boss-container\').style.display=\'none\'">Ignorer cette section</button>';
                    errorMessage += '</div>';
                    
                    container.html(errorMessage);
                    this.log('Erreur AJAX Mini Boss:', {xhr, status, error});
                }
            });
        }

        /**
         * Rendre les produits Mini Boss
         */
        renderMiniBossProducts(products) {
            const container = this.container.find('.mini-boss-products-list');
            const guestCount = parseInt(this.formData.guest_count) || 10;
            
            let html = '<div class="restaurant-plugin-products-grid mini-boss-grid">';
            
            products.forEach(product => {
                html += `
                    <div class="restaurant-plugin-product-card mini-boss-card" data-product-id="${product.id}">
                        <div class="product-image">
                            ${product.image ? `<img src="${product.image}" alt="${product.name}">` : '<div class="product-placeholder">🍔</div>'}
                        </div>
                        <div class="product-content">
                            <h4 class="product-title">${product.name}</h4>
                            <p class="product-description">${product.description || ''}</p>
                            <div class="product-price">${this.formatPrice(product.price)}</div>
                        </div>
                        <div class="product-quantity-selector">
                            <button type="button" class="qty-btn qty-minus" data-target="mini_boss_${product.id}">-</button>
                            <input type="number" 
                                   class="qty-input" 
                                   id="mini_boss_${product.id}" 
                                   name="products[mini_boss][${product.id}]" 
                                   value="0" 
                                   min="0" 
                                   max="${guestCount}" 
                                   data-product-id="${product.id}"
                                   data-category="mini_boss">
                            <button type="button" class="qty-btn qty-plus" data-target="mini_boss_${product.id}">+</button>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            
            container.html(html);
            
            // Initialiser les sélecteurs de quantité
            this.initializeQuantitySelectors(container);
        }

        /**
         * Initialiser le sélecteur d'accompagnements
         */
        initializeAccompanimentSelector(stepElement) {
            const accompanimentContainer = stepElement.find('.accompaniment-products-container');
            if (accompanimentContainer.length === 0) {
                return; // Pas de section accompagnements
            }
            
            // Les accompagnements sont déjà chargés côté serveur
            // Il suffit d'initialiser les sélecteurs de quantité et la validation
            this.initializeQuantitySelectors(accompanimentContainer);
            this.initializeAccompanimentValidation();
            this.initializeAccompanimentSuboptions(stepElement);
        }

        /**
         * Initialiser la validation des accompagnements
         */
        initializeAccompanimentValidation() {
            const guestCount = parseInt(this.formData.guest_count) || 10;
            const minRequired = this.options.accompaniment_min_per_person || 1;
            const totalMinRequired = guestCount * minRequired;
            
            // Mettre à jour le minimum requis dans l'affichage
            this.container.find('.min-required').text(totalMinRequired);
            
            this.container.on('change input', 'input[data-category="accompaniments"]', () => {
                let total = 0;
                this.container.find('input[data-category="accompaniments"]').each(function() {
                    total += parseInt($(this).val()) || 0;
                });
                
                const counter = this.container.find('.accompaniments-count');
                const validationInfo = this.container.find('.accompaniments-validation-info');
                
                if (counter.length) {
                    counter.text(total);
                }
                
                if (validationInfo.length) {
                    if (total >= totalMinRequired) {
                        validationInfo.removeClass('error').addClass('success');
                    } else {
                        validationInfo.removeClass('success').addClass('error');
                    }
                }
                
                this.formData.accompaniments_valid = (total >= totalMinRequired);
                this.log('Validation accompagnements:', {total, required: totalMinRequired, valid: this.formData.accompaniments_valid});
            });
        }
        
        /**
         * Initialiser les sous-options des accompagnements (frites, etc.)
         */
        initializeAccompanimentSuboptions(stepElement) {
            // Gérer l'affichage des sous-options quand on sélectionne des frites
            stepElement.on('change input', 'input[data-category="accompaniments"]', (e) => {
                const input = $(e.target);
                const productId = input.data('product-id');
                const quantity = parseInt(input.val()) || 0;
                const suboptionsContainer = stepElement.find(`#frites_options_${productId}`);
                
                if (suboptionsContainer.length > 0) {
                    if (quantity > 0) {
                        // Afficher les sous-options pour les frites
                        suboptionsContainer.show();
                        this.log(`Affichage des sous-options pour le produit ${productId}`);
                    } else {
                        // Masquer et réinitialiser les sous-options
                        suboptionsContainer.hide();
                        suboptionsContainer.find('input[type="number"]').val(0).trigger('change');
                        suboptionsContainer.find('input[type="checkbox"]').prop('checked', false).trigger('change');
                        this.log(`Masquage des sous-options pour le produit ${productId}`);
                    }
                }
            });
            
            // Validation des quantités de sous-options
            stepElement.on('change input', 'input[name*="frites_options"]', (e) => {
                const input = $(e.target);
                const productId = input.attr('name').match(/\[(\d+)\]/)[1];
                const mainQuantity = parseInt(stepElement.find(`#accompaniment_${productId}`).val()) || 0;
                const subQuantity = parseInt(input.val()) || 0;
                
                // Vérifier que la quantité de sous-option ne dépasse pas la quantité principale
                if (subQuantity > mainQuantity) {
                    input.val(mainQuantity);
                    this.showWarning(`La quantité d'option ne peut pas dépasser la quantité de frites sélectionnées (${mainQuantity})`);
                }
                
                this.updatePrice();
            });
            
            // Initialiser les sélecteurs de quantité pour les sous-options
            this.initializeQuantitySelectors(stepElement.find('.accompaniment-suboptions'));
        }

        /**
         * Étape 4: Buffets (selon cahier des charges)
         */
        initializeStep4(stepElement) {
            // 3 choix: salé, sucré, ou les deux
            this.initializeBuffetSelector(stepElement);
        }

        /**
         * Initialiser le sélecteur de buffets
         */
        initializeBuffetSelector(stepElement) {
            const buffetRadios = stepElement.find('input[name="buffet_type"]');
            const buffetSaleContainer = stepElement.find('.buffet-sale-container');
            const buffetSucreContainer = stepElement.find('.buffet-sucre-container');
            
            buffetRadios.on('change', (e) => {
                const selectedType = e.target.value;
                
                // Masquer tous les conteneurs
                buffetSaleContainer.hide();
                buffetSucreContainer.hide();
                
                // Afficher selon la sélection
                switch (selectedType) {
                    case 'sale':
                        buffetSaleContainer.show();
                        this.loadBuffetProducts('sale');
                        break;
                    case 'sucre':
                        buffetSucreContainer.show();
                        this.loadBuffetProducts('sucre');
                        break;
                    case 'both':
                        buffetSaleContainer.show();
                        buffetSucreContainer.show();
                        this.loadBuffetProducts('sale');
                        this.loadBuffetProducts('sucre');
                        break;
                    case 'none':
                    default:
                        // Réinitialiser les quantités
                        stepElement.find('input[data-category="buffet-sale"], input[data-category="buffet-sucre"]').val(0).trigger('change');
                        break;
                }
                
                this.updatePrice();
            });
        }

        /**
         * Charger les produits de buffet
         */
        loadBuffetProducts(buffetType) {
            const container = this.container.find(`.buffet-${buffetType}-products`);
            if (container.length === 0) {
                return;
            }
            
            container.html('<div class="restaurant-plugin-loading">Chargement des produits...</div>');
            
            const data = {
                action: 'restaurant_plugin_get_buffet_products',
                nonce: restaurantPluginAjax.nonce,
                buffet_type: buffetType,
                service_type: this.selectedService
            };

            $.ajax({
                url: restaurantPluginAjax.ajax_url,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success && response.data.products && response.data.products.length > 0) {
                        this.renderBuffetProducts(response.data.products, buffetType);
                    } else {
                        container.html(`<div class="restaurant-plugin-message restaurant-plugin-message-info"><p>Aucun produit disponible pour le buffet ${buffetType}.</p></div>`);
                    }
                },
                error: () => {
                    container.html(`<div class="restaurant-plugin-message restaurant-plugin-message-error"><p>Erreur lors du chargement des produits buffet ${buffetType}.</p></div>`);
                }
            });
        }

        /**
         * Rendre les produits de buffet
         */
        renderBuffetProducts(products, buffetType) {
            const container = this.container.find(`.buffet-${buffetType}-products`);
            const guestCount = parseInt(this.formData.guest_count) || 10;
            
            let html = '<div class="restaurant-plugin-products-grid buffet-grid">';
            
            products.forEach(product => {
                html += `
                    <div class="restaurant-plugin-product-card buffet-card" data-product-id="${product.id}">
                        <div class="product-content">
                            <h4 class="product-title">${product.name}</h4>
                            <p class="product-description">${product.description || ''}</p>
                            <div class="product-price">${this.formatPrice(product.price)}</div>
                        </div>
                        <div class="product-quantity-selector">
                            <button type="button" class="qty-btn qty-minus" data-target="buffet_${buffetType}_${product.id}">-</button>
                            <input type="number" 
                                   class="qty-input" 
                                   id="buffet_${buffetType}_${product.id}" 
                                   name="products[buffet-${buffetType}][${product.id}]" 
                                   value="0" 
                                   min="0" 
                                   max="${guestCount * 3}" 
                                   data-product-id="${product.id}"
                                   data-category="buffet-${buffetType}">
                            <button type="button" class="qty-btn qty-plus" data-target="buffet_${buffetType}_${product.id}">+</button>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            
            container.html(html);
            
            // Initialiser les sélecteurs de quantité
            this.initializeQuantitySelectors(container);
        }

        /**
         * Étape 5: Boissons (selon cahier des charges) - RÉEL
         */
        initializeStep5(stepElement) {
            // Vérifier que la méthode existe avant de l'appeler
            if (typeof this.initializeBeverageCategories === 'function') {
                this.initializeBeverageCategories(stepElement);
            } else {
                // Créer la méthode si elle n'existe pas
                this.createBeverageCategoriesMethod();
                this.initializeBeverageCategories(stepElement);
            }
            
            // Fûts uniquement pour restaurant
            if (this.selectedService === 'restaurant') {
                this.initializeKegsSelection(stepElement);
            }
            
            // Initialiser les sélecteurs de quantité pour les boissons
            this.initializeQuantitySelectors(stepElement);
        }

        /**
         * Créer la méthode initializeBeverageCategories si elle n'existe pas
         */
        createBeverageCategoriesMethod() {
            this.initializeBeverageCategories = function(stepElement) {
                const tabsContainer = stepElement.find('.beverage-tabs');
                const contentContainer = stepElement.find('.beverage-content');
                
                // Si pas d'onglets, créer la structure
                if (!tabsContainer.length) {
                    const beverageHTML = `
                        <div class="beverage-tabs">
                            <button class="beverage-tab active" data-category="softs">🥤 SOFTS</button>
                            <button class="beverage-tab" data-category="wines">🍷 VINS</button>
                            <button class="beverage-tab" data-category="beers">🍺 BIÈRES</button>
                            <button class="beverage-tab" data-category="kegs">🍻 FÛTS</button>
                        </div>
                        <div class="beverage-content">
                            <div class="beverage-category active" data-category="softs">
                                <p>Chargement des boissons soft...</p>
                            </div>
                            <div class="beverage-category" data-category="wines">
                                <p>Chargement des vins...</p>
                            </div>
                            <div class="beverage-category" data-category="beers">
                                <p>Chargement des bières...</p>
                            </div>
                            <div class="beverage-category" data-category="kegs">
                                <p>Chargement des fûts...</p>
                            </div>
                        </div>
                    `;
                    stepElement.append(beverageHTML);
                }
                
                // Gérer les clics sur les onglets
                stepElement.find('.beverage-tab').off('click').on('click', (e) => {
                    e.preventDefault();
                    const tab = $(e.currentTarget);
                    const category = tab.data('category');
                    
                    // Activer l'onglet
                    stepElement.find('.beverage-tab').removeClass('active');
                    tab.addClass('active');
                    
                    // Afficher le contenu correspondant
                    stepElement.find('.beverage-category').removeClass('active');
                    stepElement.find(`[data-category="${category}"]`).addClass('active');
                    
                    // Charger les boissons si pas encore fait
                    const categoryContainer = stepElement.find(`[data-category="${category}"]`);
                    if (!categoryContainer.hasClass('loaded')) {
                        this.loadBeveragesByCategory(category, categoryContainer);
                    }
                });
                
                // Charger la première catégorie
                this.loadBeveragesByCategory('softs', stepElement.find('[data-category="softs"]'));
                
                this.log('Catégories boissons initialisées');
            };
        }

        /**
         * Initialiser les catégories de boissons avec onglets
         */
        initializeBeverageCategories(stepElement) {
            const tabsContainer = stepElement.find('.beverage-tabs');
            const contentContainer = stepElement.find('.beverage-content');
            
            // Gérer les onglets
            tabsContainer.find('.beverage-tab').on('click', (e) => {
                e.preventDefault();
                const tab = $(e.currentTarget);
                const category = tab.data('category');
                
                // Activer l'onglet
                tabsContainer.find('.beverage-tab').removeClass('active');
                tab.addClass('active');
                
                // Afficher le contenu correspondant
                contentContainer.find('.beverage-category').hide();
                contentContainer.find(`[data-category="${category}"]`).show();
                
                // Charger les boissons si pas encore fait
                if (!contentContainer.find(`[data-category="${category}"]`).hasClass('loaded')) {
                    this.loadBeveragesByCategory(category, contentContainer.find(`[data-category="${category}"]`));
                }
            });
            
            // Activer le premier onglet par défaut
            const firstTab = tabsContainer.find('.beverage-tab').first();
            if (firstTab.length) {
                firstTab.trigger('click');
            }
            
            // Initialiser les sélecteurs de quantité
            this.initializeQuantitySelectors(stepElement);
        }

        /**
         * Charger les boissons par catégorie
         */
        loadBeveragesByCategory(category, container) {
            const data = {
                action: 'restaurant_plugin_get_beverages',
                nonce: restaurantPluginAjax.nonce,
                category: category,
                service_type: this.selectedService
            };

            $.ajax({
                url: restaurantPluginAjax.ajax_url,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        container.html(response.data.html);
                        container.addClass('loaded');
                        this.initializeQuantitySelectors(container);
                        this.log(`Boissons ${category} chargées`);
                    } else {
                        container.html('<p>Aucune boisson disponible dans cette catégorie.</p>');
                        this.log('Erreur chargement boissons:', response.data);
                    }
                },
                error: (xhr, status, error) => {
                    container.html('<p>Erreur de chargement des boissons.</p>');
                    this.log('Erreur AJAX boissons:', {xhr, status, error});
                }
            });
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
                
                // Mettre à jour le texte dynamique de durée dans le forfait
                this.updateDynamicDuration(selectedHours);
                
                this.formData.duration_supplement = supplement;
                this.formData.event_duration = selectedHours;
                this.updatePrice();
            });
            
            // Initialiser avec la valeur par défaut
            if (durationSelect.val()) {
                this.updateDynamicDuration(parseInt(durationSelect.val()));
            }
        }

        /**
         * Mettre à jour l'affichage dynamique de la durée - RÉELLE ET VISIBLE
         */
        updateDynamicDuration(hours) {
            // Mettre à jour TOUS les éléments qui affichent la durée
            const selectors = [
                '.dynamic-duration',
                '[data-dynamic="duration"]',
                '.duration-display',
                '.forfait-duration'
            ];
            
            selectors.forEach(selector => {
                $(selector).text(hours);
            });
            
            // Mettre à jour aussi dans les étapes déjà chargées ET dans le DOM global
            $('body').find(selectors.join(', ')).text(hours);
            
            // CORRECTION RÉELLE - Remplacer dans TOUS les textes
            setTimeout(() => {
                $(selectors.join(', ')).text(hours);
                
                // Mise à jour spécifique dans les textes de forfait - PLUS AGRESSIVE
                $('.restaurant-plugin-card, .restaurant-plugin-form-step, .restaurant-plugin-container').each(function() {
                    const element = $(this);
                    let text = element.html();
                    if (text && text.includes('H de privatisation')) {
                        // Remplacer TOUTES les occurrences de durée
                        const patterns = [
                            /\b\d+H de privatisation/g,
                            /\b\d+h de privatisation/g,
                            /\b\d+ H de privatisation/g,
                            /\b\d+ h de privatisation/g
                        ];
                        
                        patterns.forEach(pattern => {
                            text = text.replace(pattern, `${hours}H de privatisation`);
                        });
                        
                        element.html(text);
                    }
                });
                
                // Mise à jour FORCÉE pour tous les éléments contenant "privatisation"
                $('*:contains("privatisation")').each(function() {
                    const element = $(this);
                    if (element.children().length === 0) { // Seulement les éléments avec du texte
                        let text = element.text();
                        if (text.includes('H de privatisation')) {
                            text = text.replace(/\d+H de privatisation/g, `${hours}H de privatisation`);
                            element.text(text);
                        }
                    }
                });
                
            }, 100);
            
            // Deuxième passage après 500ms pour être sûr
            setTimeout(() => {
                this.updateDynamicDuration(hours);
            }, 500);
            
            this.log(`Durée mise à jour RÉELLEMENT: ${hours}H`);
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
            container.html('<div class="restaurant-plugin-loading">Chargement des produits...</div>').show();
            
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
                timeout: 10000, // 10 secondes
                success: (response) => {
                    if (response.success && response.data.products && response.data.products.length > 0) {
                        this.renderSignatureProducts(response.data.products, signatureType);
                        this.log(`Produits ${signatureType} chargés avec succès`, response.data.products);
                    } else {
                        container.html('<div class="restaurant-plugin-message restaurant-plugin-message-warning"><p>🔍 Aucun produit disponible pour cette sélection.</p><p><small>Vérifiez que les produits sont bien configurés dans l\'administration.</small></p></div>');
                        this.log(`Aucun produit trouvé pour ${signatureType}`, response);
                    }
                },
                error: (xhr, status, error) => {
                    let errorMessage = '<div class="restaurant-plugin-message restaurant-plugin-message-error">';
                    errorMessage += '<p>❌ Erreur lors du chargement des produits.</p>';
                    
                    if (status === 'timeout') {
                        errorMessage += '<p><small>Délai d\'attente dépassé. Veuillez réessayer.</small></p>';
                    } else if (status === 'error') {
                        errorMessage += '<p><small>Erreur de connexion. Vérifiez votre connexion internet.</small></p>';
                    } else {
                        errorMessage += '<p><small>Une erreur inattendue s\'est produite.</small></p>';
                    }
                    
                    errorMessage += '<button type="button" class="restaurant-plugin-btn-secondary" onclick="location.reload()">Recharger la page</button>';
                    errorMessage += '</div>';
                    
                    container.html(errorMessage);
                    this.log(`Erreur AJAX signature ${signatureType}:`, {xhr, status, error});
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
         * Initialiser les sélecteurs de quantité - VERSION CORRIGÉE UNIVERSELLE
         */
        initializeQuantitySelectors(container) {
            // Retirer les anciens event listeners pour éviter les doublons
            container.find('.qty-minus, .qty-plus').off('click');
            container.find('input[type="number"]').off('input change');
            
            // Boutons MOINS
            container.find('.qty-minus').on('click', (e) => {
                e.preventDefault();
                const button = $(e.currentTarget);
                let input;
                
                // Méthodes de ciblage multiples
                const target = button.data('target');
                if (target) {
                    input = $(`#${target}`);
                } else {
                    // Recherche par proximité
                    input = button.siblings('input[type="number"]').first();
                    if (!input.length) {
                        input = button.parent().find('input[type="number"]').first();
                    }
                    if (!input.length) {
                        input = button.closest('.product-quantity-selector, .beverage-quantity-selector, .sauce-quantity-selector, .qty-selector').find('input[type="number"]').first();
                    }
                }
                
                if (input.length) {
                    const currentVal = parseInt(input.val()) || 0;
                    const minVal = parseInt(input.attr('min')) || 0;
                    if (currentVal > minVal) {
                        input.val(currentVal - 1).trigger('change');
                        this.log(`Quantité diminuée: ${input.attr('name')} = ${currentVal - 1}`);
                    }
                }
            });
            
            // Boutons PLUS
            container.find('.qty-plus').on('click', (e) => {
                e.preventDefault();
                const button = $(e.currentTarget);
                let input;
                
                // Méthodes de ciblage multiples
                const target = button.data('target');
                if (target) {
                    input = $(`#${target}`);
                } else {
                    // Recherche par proximité
                    input = button.siblings('input[type="number"]').first();
                    if (!input.length) {
                        input = button.parent().find('input[type="number"]').first();
                    }
                    if (!input.length) {
                        input = button.closest('.product-quantity-selector, .beverage-quantity-selector, .sauce-quantity-selector, .qty-selector').find('input[type="number"]').first();
                    }
                }
                
                if (input.length) {
                    const currentVal = parseInt(input.val()) || 0;
                    const maxVal = parseInt(input.attr('max')) || 999;
                    if (currentVal < maxVal) {
                        input.val(currentVal + 1).trigger('change');
                        this.log(`Quantité augmentée: ${input.attr('name')} = ${currentVal + 1}`);
                    }
                }
            });
            
            // Gérer les changements directs dans les inputs
            container.find('input[type="number"]').on('input change', (e) => {
                const input = $(e.target);
                const currentVal = parseInt(input.val()) || 0;
                const minVal = parseInt(input.attr('min')) || 0;
                const maxVal = parseInt(input.attr('max')) || 999;
                
                // Validation des limites
                if (currentVal < minVal) {
                    input.val(minVal);
                } else if (currentVal > maxVal) {
                    input.val(maxVal);
                }
                
                // Mettre à jour les compteurs et prix
                this.updateQuantityCounters();
                this.updateFormData();
                this.updatePrice();
                
                this.log(`Input modifié: ${input.attr('name')} = ${input.val()}`);
            });
            
            this.log('Sélecteurs de quantité initialisés pour', container.length, 'éléments');
        }

        /**
         * Mettre à jour les compteurs de quantité - VERSION SYNCHRONISÉE
         */
        updateQuantityCounters() {
            // Compteur plats signature - RECHERCHE ÉLARGIE
            let signatureTotal = 0;
            this.container.find('input[data-category="signature"], input[name*="signature"], input[name*="products[signature]"]').each(function() {
                const val = parseInt($(this).val()) || 0;
                signatureTotal += val;
            });
            
            // Mettre à jour les affichages
            this.container.find('.signature-count').text(signatureTotal);
            const signatureCounter = this.container.find('.signature-counter');
            if (signatureCounter.length) {
                const minRequired = parseInt(signatureCounter.data('min-required')) || parseInt(this.container.find('#guest_count').val()) || 0;
                signatureCounter.text(`Sélectionnés: ${signatureTotal} / ${minRequired}`);
                
                if (signatureTotal >= minRequired) {
                    signatureCounter.removeClass('invalid').addClass('valid');
                } else {
                    signatureCounter.removeClass('valid').addClass('invalid');
                }
            }
            
            // Compteur accompagnements - RECHERCHE ÉLARGIE
            let accompanimentTotal = 0;
            this.container.find('input[data-category="accompaniments"], input[data-category="accompaniment"], input[name*="accompaniment"]').each(function() {
                const val = parseInt($(this).val()) || 0;
                accompanimentTotal += val;
            });
            this.container.find('.accompaniments-count, .accompaniment-count').text(accompanimentTotal);
            
            // Compteur Mini Boss - RECHERCHE ÉLARGIE
            let miniBossTotal = 0;
            this.container.find('input[data-category="mini_boss"], input[data-category="mini-boss"], input[name*="mini_boss"]').each(function() {
                const val = parseInt($(this).val()) || 0;
                miniBossTotal += val;
            });
            this.container.find('.mini-boss-count').text(miniBossTotal);
            
            // Compteurs buffets - RECHERCHE ÉLARGIE
            let buffetSaleTotal = 0;
            this.container.find('input[data-category="buffet-sale"], input[name*="buffet-sale"], input[name*="buffet_sale"]').each(function() {
                const val = parseInt($(this).val()) || 0;
                buffetSaleTotal += val;
            });
            this.container.find('.buffet-sale-count').text(buffetSaleTotal);
            
            let buffetSucreTotal = 0;
            this.container.find('input[data-category="buffet-sucre"], input[name*="buffet-sucre"], input[name*="buffet_sucre"]').each(function() {
                const val = parseInt($(this).val()) || 0;
                buffetSucreTotal += val;
            });
            this.container.find('.buffet-sucre-count').text(buffetSucreTotal);
            
            // SYNCHRONISER LES INPUTS AVEC LEURS AFFICHAGES
            this.container.find('.product-quantity-selector').each(function() {
                const container = $(this);
                const input = container.find('input[type="number"]');
                const displayElement = container.find('.qty-display, .quantity-display');
                
                if (input.length && displayElement.length) {
                    const actualValue = input.val();
                    displayElement.text(actualValue);
                }
            });
            
            this.log('Compteurs synchronisés:', {
                signature: signatureTotal,
                accompaniment: accompanimentTotal,
                miniBoss: miniBossTotal,
                buffetSale: buffetSaleTotal,
                buffetSucre: buffetSucreTotal
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
            if (currentStepElement.length === 0) {
                this.log(`Étape ${this.currentStep} non trouvée pour validation`);
                return false;
            }
            
            let isValid = true;
            let errorMessages = [];
            
            // Validation des champs requis
            currentStepElement.find('input[required], select[required], textarea[required]').each((index, field) => {
                const $field = $(field);
                if (!this.validateField($field)) {
                    isValid = false;
                    const fieldName = $field.attr('name') || $field.attr('id') || 'Champ inconnu';
                    
                    // Messages de validation en FRANÇAIS CORRECT
                    let frenchMessage = '';
                    switch(fieldName) {
                        case 'event_date':
                            frenchMessage = '📅 Veuillez compléter la date de l\'événement';
                            break;
                        case 'guest_count':
                            frenchMessage = '👥 Veuillez indiquer le nombre de convives';
                            break;
                        case 'event_duration':
                            frenchMessage = '⏰ Veuillez choisir la durée de l\'événement';
                            break;
                        case 'postal_code':
                            frenchMessage = '📍 Veuillez saisir votre code postal';
                            break;
                        case 'client_name':
                            frenchMessage = '👤 Veuillez saisir votre nom';
                            break;
                        case 'client_firstname':
                            frenchMessage = '👤 Veuillez saisir votre prénom';
                            break;
                        case 'client_email':
                            frenchMessage = '📧 Veuillez saisir une adresse email valide';
                            break;
                        case 'client_phone':
                            frenchMessage = '📞 Veuillez saisir un numéro de téléphone valide';
                            break;
                        default:
                            frenchMessage = `⚠️ Veuillez compléter le champ "${fieldName}"`;
                    }
                    
                    errorMessages.push(frenchMessage);
                }
            });
            
            // Validations spécifiques par étape
            switch (this.currentStep) {
                case 3: // Formules repas
                    const step3Validation = this.validateStep3();
                    if (!step3Validation.valid) {
                        isValid = false;
                        errorMessages.push(...step3Validation.errors);
                    }
                    break;
                case 4: // Buffets
                    const step4Validation = this.validateStep4();
                    if (!step4Validation.valid) {
                        isValid = false;
                        errorMessages.push(...step4Validation.errors);
                    }
                    break;
            }
            
            if (!isValid) {
                const errorMessage = errorMessages.length > 0 
                    ? errorMessages.join('<br>') 
                    : restaurantPluginAjax.texts.step_validation_error;
                this.showError(errorMessage);
                this.log('Validation échouée pour l\'étape', {step: this.currentStep, errors: errorMessages});
            }
            
            return isValid;
        }

        /**
         * Valider l'étape 3 (formules repas)
         */
        validateStep3() {
            let errors = [];
            
            // Vérifier plats signature
            const guestCount = parseInt(this.formData.guest_count) || 10;
            const signatureMinRequired = guestCount * (this.options.signature_dish_min_per_person || 1);
            
            let signatureTotal = 0;
            this.container.find('input[data-category="signature"]').each(function() {
                signatureTotal += parseInt($(this).val()) || 0;
            });
            
            if (signatureTotal < signatureMinRequired) {
                errors.push(`Sélectionnez au moins ${signatureMinRequired} plats signature (${this.options.signature_dish_min_per_person || 1} par personne)`);
            }
            
            // Vérifier accompagnements (min 1/personne)
            const accompanimentMinRequired = guestCount * (this.options.accompaniment_min_per_person || 1);
            let accompanimentTotal = 0;
            
            this.container.find('input[data-category="accompaniments"]').each(function() {
                accompanimentTotal += parseInt($(this).val()) || 0;
            });
            
            if (accompanimentTotal < accompanimentMinRequired) {
                errors.push(`Sélectionnez au moins ${accompanimentMinRequired} accompagnements (${this.options.accompaniment_min_per_person || 1} par personne)`);
            }
            
            this.log('Validation étape 3:', {
                signatureTotal, 
                signatureMinRequired,
                accompanimentTotal,
                accompanimentMinRequired,
                errors
            });
            
            return {
                valid: errors.length === 0,
                errors: errors
            };
        }

        /**
         * Valider l'étape 4 (buffets)
         */
        validateStep4() {
            let errors = [];
            const selectedBuffetType = this.container.find('input[name="buffet_type"]:checked').val();
            
            if (!selectedBuffetType || selectedBuffetType === 'none') {
                return { valid: true, errors: [] }; // Les buffets sont optionnels
            }
            
            // Validation buffet salé
            if (selectedBuffetType === 'sale' || selectedBuffetType === 'both') {
                const saleValidation = this.validateBuffetSale();
                if (!saleValidation.valid) {
                    errors.push(...saleValidation.errors);
                }
            }
            
            // Validation buffet sucré
            if (selectedBuffetType === 'sucre' || selectedBuffetType === 'both') {
                const sucreValidation = this.validateBuffetSucre();
                if (!sucreValidation.valid) {
                    errors.push(...sucreValidation.errors);
                }
            }
            
            return {
                valid: errors.length === 0,
                errors: errors
            };
        }

        /**
         * Valider le buffet salé (min 1/pers + min 2 recettes)
         */
        validateBuffetSale() {
            let errors = [];
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
                errors.push(`Buffet salé: minimum ${minTotalQuantity} portions requises (${minPerPerson} par personne)`);
            }
            
            if (selectedRecipes < minRecipes) {
                errors.push(`Buffet salé: minimum ${minRecipes} recettes différentes requises`);
            }
            
            return {
                valid: errors.length === 0,
                errors: errors
            };
        }

        /**
         * Valider le buffet sucré (min 1/pers + min 1 plat)
         */
        validateBuffetSucre() {
            let errors = [];
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
                errors.push(`Buffet sucré: minimum ${minTotalQuantity} portions requises (${minPerPerson} par personne)`);
            }
            
            if (selectedDishes < minDishes) {
                errors.push(`Buffet sucré: minimum ${minDishes} plat requis`);
            }
            
            return {
                valid: errors.length === 0,
                errors: errors
            };
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
                        if (value !== null && value !== undefined) {
                            formData[name] = value;
                        }
                    }
                }
            });
            
            // Collecter les produits sélectionnés
            this.collectProductsData(formData);
            
            // Ajouter des données calculées
            formData.service_type = this.selectedService;
            formData.current_step = this.currentStep;
            
            // Mettre à jour les données
            Object.assign(this.formData, formData);
            
            this.log('Données formulaire mises à jour:', this.formData);
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
            
            // Mettre à jour les données du formulaire avant le calcul
            this.updateFormData();
            
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
                        this.log('Prix mis à jour:', response.data);
                    } else {
                        this.log('Erreur calcul prix:', response.data);
                    }
                },
                error: (xhr, status, error) => {
                    this.log('Erreur AJAX prix:', {xhr, status, error});
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
            
            // Afficher le détail des suppléments si disponible
            if (priceData.breakdown && priceData.breakdown.length > 0) {
                this.updatePriceBreakdown(priceData.breakdown);
            }
            
            // Animation du total
            this.priceTotal.addClass('updated');
            setTimeout(() => {
                this.priceTotal.removeClass('updated');
            }, 500);
        }

        /**
         * Mettre à jour le détail des prix
         */
        updatePriceBreakdown(breakdown) {
            const breakdownContainer = this.calculator.find('.price-breakdown');
            if (breakdownContainer.length === 0) {
                // Créer le conteneur s'il n'existe pas
                this.calculator.find('.restaurant-plugin-price-total').before(`
                    <div class="price-breakdown" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(36, 49, 39, 0.2);">
                        <div class="breakdown-items"></div>
                    </div>
                `);
            }
            
            const itemsContainer = this.calculator.find('.breakdown-items');
            itemsContainer.empty();
            
            breakdown.forEach(item => {
                if (item.amount > 0) {
                    itemsContainer.append(`
                        <div class="restaurant-plugin-price-row breakdown-item">
                            <span>${item.label}</span>
                            <span>+${this.formatPrice(item.amount)}</span>
                        </div>
                    `);
                }
            });
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
            
            // Mettre à jour les états avec les bonnes couleurs
            this.progressBar.find('.restaurant-plugin-progress-step').each((index, step) => {
                const stepNumber = index + 1;
                const stepElement = $(step);
                const stepNumberElement = stepElement.find('.restaurant-plugin-step-number');
                
                stepElement.removeClass('active completed');
                
                if (stepNumber < this.currentStep) {
                    // Étapes complétées : fond vert foncé, chiffre beige
                    stepElement.addClass('completed');
                    stepNumberElement.css({
                        'background-color': '#243127',
                        'color': '#F6F2E7',
                        'border-color': '#243127'
                    });
                } else if (stepNumber === this.currentStep) {
                    // Étape actuelle : fond ORANGE, chiffre vert foncé
                    stepElement.addClass('active');
                    stepNumberElement.css({
                        'background-color': '#FFB404',
                        'color': '#243127',
                        'border-color': '#FFB404',
                        'transform': 'scale(1.1)',
                        'box-shadow': '0 4px 15px rgba(255, 180, 4, 0.4)'
                    });
                    
                    // Label aussi en orange
                    stepElement.find('.restaurant-plugin-step-label').css({
                        'color': '#FFB404',
                        'font-weight': '700'
                    });
                } else {
                    // Étapes futures : fond gris
                    stepNumberElement.css({
                        'background-color': '#ddd',
                        'color': '#666',
                        'border-color': '#ddd'
                    });
                }
            });
            
            // Mettre à jour la ligne de progression
            const progressPercent = this.totalSteps > 1 ? ((this.currentStep - 1) / (this.totalSteps - 1)) * 100 : 0;
            this.progressBar.find('.restaurant-plugin-progress-line-fill').css('width', `${Math.max(0, progressPercent)}%`);
        }

        /**
         * Générer les étapes de la barre de progression
         */
        generateProgressSteps() {
            const stepsContainer = this.progressBar.find('.restaurant-plugin-progress-steps');
            
            // Nettoyer le conteneur
            stepsContainer.empty();
            
            // Ajouter la ligne de progression
            stepsContainer.append('<div class="restaurant-plugin-progress-line"><div class="restaurant-plugin-progress-line-fill"></div></div>');
            
            // Labels des étapes
            const stepLabels = [
                'Service',
                'Détails', 
                'Options',
                'Produits',
                'Contact',
                'Récapitulatif'
            ];
            
            // Générer les étapes avec la structure complète
            for (let i = 1; i <= this.totalSteps; i++) {
                const stepLabel = stepLabels[i - 1] || `Étape ${i}`;
                const stepHtml = `
                    <div class="restaurant-plugin-progress-step">
                        <div class="restaurant-plugin-step-number">${i}</div>
                        <div class="restaurant-plugin-step-label">${stepLabel}</div>
                    </div>
                `;
                stepsContainer.append(stepHtml);
            }
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
     * Initialisation automatique avec protection contre les doublons
     */
    $(document).ready(function() {
        $('.restaurant-plugin-container').each(function() {
            // Éviter la double initialisation
            if (!$(this).hasClass('restaurant-plugin-initialized')) {
                $(this).addClass('restaurant-plugin-initialized');
                new RestaurantPluginFormBlock(this);
            }
        });
    });

})(jQuery);



