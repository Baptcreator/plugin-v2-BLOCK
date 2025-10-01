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
            
            // Générer les étapes par défaut (restaurant) au chargement
            this.generateProgressSteps('restaurant');
            
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
            
            // Sélecteurs de quantité - avec délégation d'événements renforcée
            this.container.on('click', '.rbf-v3-qty-plus', (e) => {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                const $button = $(e.currentTarget);
                const target = $button.data('target');
                this.log('🔵 Clic sur bouton +:', target, 'Classes:', $button.attr('class'));
                
                // Vérifier si le bouton est désactivé
                if ($button.prop('disabled')) {
                    this.log('❌ Bouton + désactivé pour:', target);
                    return false;
                }
                
                // Sauvegarder la position de scroll
                const scrollPosition = $(window).scrollTop();
                
                this.handleQuantityChange($button, 1);
                
                // Restaurer la position de scroll après un court délai
                setTimeout(() => {
                    if ($(window).scrollTop() !== scrollPosition) {
                        $(window).scrollTop(scrollPosition);
                    }
                }, 50);
                
                return false;
            });

            this.container.on('click', '.rbf-v3-qty-minus', (e) => {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                const $button = $(e.currentTarget);
                const target = $button.data('target');
                this.log('🔴 Clic sur bouton -:', target, 'Classes:', $button.attr('class'));
                
                // Vérifier si le bouton est désactivé
                if ($button.prop('disabled')) {
                    this.log('❌ Bouton - désactivé pour:', target);
                    return false;
                }
                
                // Sauvegarder la position de scroll
                const scrollPosition = $(window).scrollTop();
                
                this.handleQuantityChange($button, -1);
                
                // Restaurer la position de scroll après un court délai
                setTimeout(() => {
                    if ($(window).scrollTop() !== scrollPosition) {
                        $(window).scrollTop(scrollPosition);
                    }
                }, 50);
                
                return false;
            });
            
            // Onglets boissons
            this.container.on('click', '.rbf-v3-tab-btn', (e) => {
                this.switchBeverageTab($(e.currentTarget));
            });

            // Gestion des filtres de sous-catégories pour boissons
            this.container.on('click', '.rbf-v3-subcategory-btn', (e) => {
                this.handleSubcategoryFilter($(e.currentTarget));
            });
            
            // ✅ CORRECTION : Filtres boissons (vins et bières)
            this.container.on('click', '.rbf-v3-filter-btn', (e) => {
                this.filterBeverages($(e.currentTarget));
            });
            
            // Sélecteurs de quantité pour boissons
            this.container.on('click', '.rbf-v3-qty-btn.plus', (e) => {
                this.handleBeverageQuantityChange($(e.currentTarget), 1);
            });
            
            this.container.on('click', '.rbf-v3-qty-btn.minus', (e) => {
                this.handleBeverageQuantityChange($(e.currentTarget), -1);
            });
            
            // Input quantité - gestion universelle pour tous les produits
            this.container.on('change input', '.rbf-v3-qty-input', (e) => {
                this.handleQuantityInput($(e.currentTarget));
            });
            
            // Événement délégué pour tous les boutons "Passer cette étape"
            this.container.on('click', '.rbf-v3-skip-step', () => {
                this.skipCurrentStep();
            });
            
            // DEBUG : Événement générique pour tous les boutons pour détecter les clics
            this.container.on('click', 'button', (e) => {
                const $button = $(e.currentTarget);
                const classes = $button.attr('class') || '';
                const target = $button.data('target') || '';
                
                if (classes.includes('rbf-v3-qty-btn')) {
                    this.log('🔍 DEBUG - Bouton cliqué:', {
                        classes: classes,
                        target: target,
                        text: $button.text(),
                        disabled: $button.prop('disabled'),
                        hasPlus: classes.includes('rbf-v3-qty-plus'),
                        hasMinus: classes.includes('rbf-v3-qty-minus')
                    });
                }
            });
            
            // Chargement des produits signature
            this.container.on('change', '[data-action="load-signature-products"]', (e) => {
                this.loadSignatureProducts($(e.currentTarget).val());
            });
            
            // Toggle Mini Boss
            this.container.on('change', '[data-action="toggle-mini-boss"]', (e) => {
                this.toggleMiniBoss($(e.currentTarget).is(':checked'));
            });
            
            // ✅ CORRECTION : Gestion des options remorque (tireuse + jeux)
            this.container.on('change', '[data-action="toggle-kegs"]', (e) => {
                this.toggleKegsSelection($(e.currentTarget).is(':checked'));
            });
            
            this.container.on('change', '[data-action="toggle-games"]', (e) => {
                this.toggleGamesSelection($(e.currentTarget).is(':checked'));
            });
            
            // Gestion des jeux individuels
            this.container.on('change', '.rbf-v3-game-checkbox', (e) => {
                this.updatePriceDisplay();
            });
            
            // Gestion des accompagnements
            this.container.on('change', '.rbf-v3-accompaniment-checkbox', (e) => {
                this.handleAccompanimentToggle($(e.currentTarget));
            });
            
            // Event listeners pour les options frites
            this.container.on('change', '.rbf-v3-option-checkbox, .rbf-v3-sauce-checkbox', (e) => {
                this.handleFritesOptionToggle($(e.currentTarget));
                
                // Forcer la réinitialisation des boutons après le toggle
                setTimeout(() => {
                    this.reinitializeFritesButtons();
                }, 100);
            });
            
            // Event listeners pour la saisie directe dans les champs de quantité des sauces et chimichurri
            this.container.on('input change', 'input[name^="sauce_"][name$="_qty"], input[name="frites_chimichurri_qty"], input[name*="enrobee"][name$="_qty"]', (e) => {
                const $input = $(e.currentTarget);
                const value = parseInt($input.val()) || 0;
                const min = parseInt($input.attr('min')) || 0;
                
                // Validation spéciale pour les options de frites
                const maxAllowed = this.validateFritesOptionsQuantity($input, value);
                const validValue = Math.max(min, Math.min(maxAllowed, value));
                
                if (value !== validValue) {
                    $input.val(validValue);
                    if (value > maxAllowed) {
                        // ✅ CORRECTION : Pas de scroll pour les validations de limites
                        this.showMessage(`Vous ne pouvez pas avoir plus de sauces que de frites (maximum ${maxAllowed})`, 'warning', true);
                    }
                }
                
                this.updateQuantityButtons($input);
                this.calculatePrice();
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

            // Générer les étapes dynamiquement selon le service
            this.generateProgressSteps(service);

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
         * Générer les étapes de progression selon le service
         */
        generateProgressSteps(service) {
            const progressStepsContainer = this.container.find('#rbf-v3-progress-steps');
            
            // Définir les étapes selon le service
            const steps = (service === 'restaurant') ? [
                { number: 1, label: 'Service' },
                { number: 2, label: 'Forfait' },
                { number: 3, label: 'Repas' },
                { number: 4, label: 'Buffets' },
                { number: 5, label: 'Boissons' },
                { number: 6, label: 'Contact' }
            ] : [
                { number: 1, label: 'Service' },
                { number: 2, label: 'Forfait' },
                { number: 3, label: 'Repas' },
                { number: 4, label: 'Buffets' },
                { number: 5, label: 'Boissons' },
                { number: 6, label: 'Options' },
                { number: 7, label: 'Contact' }
            ];

            // Vider le conteneur
            progressStepsContainer.empty();

            // Générer le HTML des étapes
            steps.forEach((step, index) => {
                const stepHtml = `
                    <div class="rbf-v3-step${index === 0 ? ' active' : ''}" data-step="${step.number}">
                        <span class="rbf-v3-step-number">${step.number}</span>
                        <span class="rbf-v3-step-label">${step.label}</span>
                    </div>
                `;
                progressStepsContainer.append(stepHtml);
            });

            // Mettre à jour la référence aux éléments de progression
            this.progressSteps = this.container.find('.rbf-v3-step');
            
            this.log(`Étapes générées pour ${service}:`, steps.length, 'étapes');
        }

        /**
         * Aller à l'étape suivante
         */
        goToNextStep() {
            this.log('Tentative de passage à l\'étape suivante. Étape actuelle:', this.currentStep);
            
            // Mettre à jour les données du formulaire avant la validation
            this.updateFormData();
            
            if (!this.validateCurrentStep()) {
                this.log('Validation échouée, arrêt du passage à l\'étape suivante');
                return;
            }

            this.log('Validation réussie, passage à l\'étape suivante');

            // Si on est à la dernière étape, soumettre le formulaire
            if (this.currentStep >= this.totalSteps) {
                this.log('Dernière étape atteinte, soumission du formulaire');
                this.submitForm();
                return;
            }

            // Sinon, passer à l'étape suivante
            this.currentStep++;
            this.loadStep(this.currentStep);
            this.updateProgress();
            this.updateNavigation();
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
                        
                        // Restaurer les valeurs des quantités depuis formData
                        this.restoreQuantityValues();
                        
                        // Si c'est l'étape 3, s'assurer que les boutons de frites sont bien initialisés
                        if (stepNumber === 3) {
                            setTimeout(() => {
                                this.reinitializeFritesButtons();
                                this.fixFritesOptionsAfterLoad();
                                this.forceUpdateFritesOptionsButtons();
                                this.debugButtonsInStep3();
                                this.log('🔧 Réinitialisation spéciale des boutons de frites pour l\'étape 3');
                            }, 200);
                        }
                        
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
                        this.formData[name] = $field.is(':checked') ? '1' : '0';
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
            
            // Calculer la distance si le code postal a changé (remorque uniquement)
            if (this.selectedService === 'remorque' && this.formData.postal_code && this.formData.postal_code.length === 5) {
                this.calculateDeliveryDistance(this.formData.postal_code);
            }
        }

        /**
         * Calculer la distance de livraison
         */
        calculateDeliveryDistance(postalCode) {
            // Éviter les appels répétés pour le même code postal
            if (this.lastCalculatedPostalCode === postalCode) {
                return;
            }
            
            this.lastCalculatedPostalCode = postalCode;
            
            const data = {
                action: 'rbf_v3_calculate_distance',
                nonce: rbfV3Config.nonce,
                postal_code: postalCode
            };

            $.ajax({
                url: rbfV3Config.ajaxUrl,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        const distance = response.data.distance;
                        const supplement = response.data.supplement;
                        const zone = response.data.zone;
                        const duration = response.data.duration;
                        const method = response.data.method;
                        const overLimitMessage = response.data.over_limit_message;
                        
                        // Stocker les données de distance
                        this.formData.delivery_distance = distance;
                        this.formData.delivery_supplement = supplement;
                        this.formData.delivery_zone = zone;
                        
                        // Si la zone dépasse la limite, afficher seulement le message d'erreur
                        if (overLimitMessage) {
                            // Réinitialiser le supplément à 0 pour les zones non couvertes
                            this.formData.delivery_supplement = 0;
                            this.displayDeliveryError(overLimitMessage);
                        } else {
                            // Afficher le supplément seulement si la zone est couverte
                            this.displayDeliveryInfo(distance, supplement, zone, duration, method);
                        }
                        
                        // Recalculer le prix avec le supplément
                        this.calculatePrice();
                        
                        this.log('Distance calculée:', {
                            distance: distance,
                            supplement: supplement,
                            zone: zone,
                            duration: duration,
                            method: method,
                            overLimit: !!overLimitMessage
                        });
                    } else {
                        this.displayDeliveryError(response.data || 'Erreur lors du calcul de distance');
                        // Réinitialiser les données de distance
                        delete this.formData.delivery_distance;
                        delete this.formData.delivery_supplement;
                        delete this.formData.delivery_zone;
                        this.hideDeliveryInfo();
                    }
                },
                error: () => {
                    this.displayDeliveryError('Erreur de connexion lors du calcul de distance');
                    // Réinitialiser les données de distance
                    delete this.formData.delivery_distance;
                    delete this.formData.delivery_supplement;
                    delete this.formData.delivery_zone;
                    this.hideDeliveryInfo();
                }
            });
        }

        /**
         * Afficher les informations de livraison
         */
        displayDeliveryInfo(distance, supplement, zone, duration, method) {
            let $deliveryInfo = this.container.find('.rbf-v3-delivery-info');
            
            // Créer l'élément s'il n'existe pas
            if ($deliveryInfo.length === 0) {
                $deliveryInfo = $('<div class="rbf-v3-delivery-info"></div>');
                this.container.find('[name="postal_code"]').closest('.rbf-v3-form-group').after($deliveryInfo);
            }
            
            let displayText = '';
            let cssClass = 'rbf-v3-delivery-free';
            
            if (supplement > 0) {
                displayText = `Supplément livraison: +${supplement}€ (${zone})`;
                cssClass = 'rbf-v3-delivery-paid';
                if (duration) {
                    displayText += ` - ${distance}km, ${duration}`;
                }
                if (method === 'google_maps') {
                    displayText += ' ✅';
                } else if (method === 'fallback') {
                    displayText += ' ⚠️ (estimation)';
                }
            } else {
                displayText = 'Livraison gratuite (zone locale)';
                if (duration) {
                    displayText += ` - ${distance}km, ${duration}`;
                }
            }
            
            $deliveryInfo.html(`<div class="${cssClass}">${displayText}</div>`).show();
        }

        /**
         * Masquer les informations de livraison
         */
        hideDeliveryInfo() {
            this.container.find('.rbf-v3-delivery-info').hide();
        }

        /**
         * Afficher une erreur de livraison
         */
        displayDeliveryError(errorMessage) {
            let $deliveryInfo = this.container.find('.rbf-v3-delivery-info');
            
            // Créer l'élément s'il n'existe pas
            if ($deliveryInfo.length === 0) {
                $deliveryInfo = $('<div class="rbf-v3-delivery-info"></div>');
                this.container.find('[name="postal_code"]').closest('.rbf-v3-form-group').after($deliveryInfo);
            }
            
            // Toujours remplacer complètement le contenu par le message d'erreur
            // pour éviter l'affichage du supplément avec le message d'erreur
            $deliveryInfo.html(`<div class="rbf-v3-delivery-error">${errorMessage}</div>`).show();
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
                        this.priceData.beverages_detailed = this.beveragesDetails || [];
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
            
            // Produits détaillés par catégorie
            if (this.priceData.products && this.priceData.products.length > 0) {
                // Grouper les produits par catégorie
                const productsByCategory = {};
                this.priceData.products.forEach(product => {
                    if (product.quantity > 0) {
                        const category = product.category || 'Produits';
                        if (!productsByCategory[category]) {
                            productsByCategory[category] = [];
                        }
                        productsByCategory[category].push(product);
                    }
                });
                
                // Afficher chaque catégorie avec structure hiérarchique
                Object.keys(productsByCategory).forEach(category => {
                    productsByCategory[category].forEach(product => {
                        html += `<div class="rbf-v3-price-line rbf-v3-price-main">
                            <span>${product.quantity}× ${product.name}</span>
                            <span class="rbf-v3-price">${this.formatPrice(product.total)}</span>
                        </div>`;
                        
                        // ✅ DYNAMIQUE : Afficher les options en sous-lignes (toutes catégories)
                        if (product.options && product.options.length > 0) {
                            product.options.forEach(option => {
                                const optionQuantity = option.quantity ? option.quantity + '× ' : '';
                                const optionPrice = option.total > 0 ? this.formatPrice(option.total) : '';
                                html += `<div class="rbf-v3-price-line rbf-v3-price-option">
                                    <span class="rbf-v3-option-indent">└── ${optionQuantity}${option.name}</span>
                                    <span class="rbf-v3-price">${optionPrice}</span>
                                </div>`;
                                
                                // ✅ NOUVEAU : Afficher les sous-options avec double indentation
                                if (option.suboptions && option.suboptions.length > 0) {
                                    option.suboptions.forEach(suboption => {
                                        const suboptionQuantity = suboption.quantity ? suboption.quantity + '× ' : '';
                                        const suboptionPrice = suboption.total > 0 ? this.formatPrice(suboption.total) : '';
                                        html += `<div class="rbf-v3-price-line rbf-v3-price-suboption">
                                            <span class="rbf-v3-option-indent">    └── ${suboptionQuantity}${suboption.name}</span>
                                            <span class="rbf-v3-price">${suboptionPrice}</span>
                                        </div>`;
                                    });
                                }
                            });
                        }
                    });
                });
            }
            
            // Boissons détaillées par type
            if (this.priceData.beverages_detailed && this.priceData.beverages_detailed.length > 0) {
                // Grouper par type de boisson
                const beveragesByType = {};
                this.priceData.beverages_detailed.forEach(beverage => {
                    const type = beverage.type || 'Boissons';
                    if (!beveragesByType[type]) {
                        beveragesByType[type] = [];
                    }
                    beveragesByType[type].push(beverage);
                });
                
                Object.keys(beveragesByType).forEach(type => {
                    beveragesByType[type].forEach(beverage => {
                        const sizeText = beverage.size ? ` (${beverage.size})` : '';
                        html += `<div class="rbf-v3-price-line">
                            <span>${beverage.quantity}× ${beverage.name}${sizeText}</span>
                            <span class="rbf-v3-price">${this.formatPrice(beverage.total)}</span>
                        </div>`;
                    });
                });
            } else if (this.priceData.beverages && this.priceData.beverages > 0) {
                // Fallback pour l'ancien format
                html += `<div class="rbf-v3-price-line">
                    <span>Boissons</span>
                    <span class="rbf-v3-price">${this.formatPrice(this.priceData.beverages)}</span>
                </div>`;
            }
            
            // ✅ CORRECTION : Options détaillées (remorque)
            if (this.priceData.options && this.priceData.options.length > 0) {
                this.priceData.options.forEach(option => {
                    html += `<div class="rbf-v3-price-line">
                        <span>${option.quantity ? option.quantity + '× ' : ''}${option.name}</span>
                        <span class="rbf-v3-price">${this.formatPrice(option.total || option.price)}</span>
                    </div>`;
                });
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
            // Vérifier que les étapes ont été générées
            if (this.progressSteps.length === 0) {
                this.log('Aucune étape générée, mise à jour de la progression ignorée');
                return;
            }

            // Centrer la barre de progression au-dessus de l'étape actuelle
            const progressPercent = ((this.currentStep - 0.5) / this.totalSteps) * 100;
            this.progressBar.css('width', Math.max(0, Math.min(100, progressPercent)) + '%');

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

            this.log(`Progression mise à jour: étape ${this.currentStep}/${this.totalSteps} (${progressPercent.toFixed(1)}%)`);
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
            
            // ✅ CORRECTION : Gestion des onglets de boissons ET des onglets de fûts
            if ($tabBtn.closest('.rbf-v3-kegs-tabs').length > 0) {
                // C'est un onglet de catégorie de fûts - filtrer les cartes
                this.filterKegsByCategory(tabName);
            } else {
                // C'est un onglet de boissons classique - changer le contenu
                this.container.find('.rbf-v3-tab-content').removeClass('active');
                this.container.find(`.rbf-v3-tab-content[data-tab="${tabName}"]`).addClass('active');
            }
        }

        /**
         * Gérer les filtres de sous-catégories pour vins et bières
         */
        handleSubcategoryFilter($filterBtn) {
            const filter = $filterBtn.data('filter');
            
            // Activer le bouton de filtre
            $filterBtn.addClass('active').siblings().removeClass('active');
            
            // Trouver le conteneur parent des boissons
            const $beveragesSection = $filterBtn.closest('.rbf-v3-tab-content').find('.rbf-v3-beverages-section');
            
            if (filter === 'all') {
                // Afficher toutes les boissons
                $beveragesSection.find('.rbf-v3-beverage-card').show();
            } else {
                // Masquer toutes les boissons
                $beveragesSection.find('.rbf-v3-beverage-card').hide();
                
                // Afficher seulement les boissons correspondant au filtre (vins ET bières)
                $beveragesSection.find(`.rbf-v3-beverage-card[data-wine-category="${filter}"], .rbf-v3-beverage-card[data-beer-category="${filter}"]`).show();
            }
        }
        
        /**
         * ✅ CORRECTION : Filtrer les fûts par catégorie de bière
         */
        filterKegsByCategory(category) {
            const $kegsContent = this.container.find('.rbf-v3-kegs-content');
            const $kegCards = $kegsContent.find('.rbf-v3-keg-card');
            
            // Masquer toutes les cartes
            $kegCards.hide();
            
            // Afficher seulement les cartes de la catégorie sélectionnée
            $kegCards.filter(`[data-category="${category}"]`).show();
            
            // Si aucune carte trouvée pour cette catégorie, afficher un message
            const visibleCards = $kegCards.filter(`[data-category="${category}"]:visible`);
            if (visibleCards.length === 0) {
                // Créer un message temporaire s'il n'existe pas
                let $noProductsMsg = $kegsContent.find('.rbf-v3-no-kegs-category');
                if ($noProductsMsg.length === 0) {
                    $noProductsMsg = $('<p class="rbf-v3-no-kegs-category" style="text-align: center; color: #666; padding: 20px;">Aucun fût disponible dans cette catégorie.</p>');
                    $kegsContent.append($noProductsMsg);
                }
                $noProductsMsg.show();
            } else {
                // Masquer le message s'il y a des produits
                $kegsContent.find('.rbf-v3-no-kegs-category').hide();
            }
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
         * Gérer la saisie directe de quantité pour tous les produits
         */
        handleQuantityInput($input) {
            const value = parseInt($input.val()) || 0;
            const minValue = parseInt($input.attr('min')) || 0;
            const maxValue = parseInt($input.attr('max')) || 999;
            const validValue = Math.max(minValue, Math.min(maxValue, value));
            
            // Corriger la valeur si nécessaire et déclencher 'change' seulement si corrigée
            if (validValue !== value) {
                $input.val(validValue).trigger('change');
            } else {
                $input.val(validValue);
            }
            
            // Vérifier si c'est un champ de boisson (avec data-product-id ou data-size-id)
            if ($input.data('product-id') || $input.data('size-id')) {
                // C'est une boisson, utiliser la logique spécifique aux boissons
                this.updateBeverageQuantity($input);
            } else {
                // C'est un autre type de produit, utiliser la logique générale
                this.updateQuantityButtons($input);
                
                // ✅ CORRECTION : Gérer les options pour TOUS les accompagnements (pas seulement les frites)
                const targetName = $input.attr('name');
                if (targetName.includes('accompaniment_') && targetName.includes('_qty')) {
                    // Utiliser le même système que les boutons +/- pour la cohérence
                    const $card = $input.closest('.rbf-v3-accompaniment-card');
                    const $optionsContainer = $card.find('.rbf-v3-acc-options');
                    
                    if ($optionsContainer.length > 0) {
                        this.updateAccompanimentOptionsLimits($card, validValue);
                    }
                }
                
                // ✅ CORRECTION : Validation générique pour toutes les options d'accompagnements
                if (targetName.includes('sauce_') || targetName.includes('chimichurri') || $input.hasClass('rbf-v3-option-input')) {
                    this.validateAccompanimentOptionsTotal($input);
                }
                
                // Validation spéciale pour les frites (gardée pour compatibilité)
                if (targetName.includes('frites_sauce') || targetName.includes('frites_chimichurri')) {
                    this.validateFritesOptions();
                }
                
                // ✅ CORRECTION : Vérification fûts/tireuse
                if (targetName.includes('keg_') && targetName.includes('_qty') && validValue > 0) {
                    const tireuseSelected = this.container.find('input[name="option_tireuse"]').is(':checked');
                    if (!tireuseSelected) {
                        // Cocher automatiquement la tireuse
                        this.container.find('input[name="option_tireuse"]').prop('checked', true).trigger('change');
                        this.showMessage('✅ Tireuse automatiquement ajoutée pour vos fûts sélectionnés.', 'info');
                        setTimeout(() => this.hideMessage(), 3000);
                    }
                }
                
                // ✅ CORRECTION : Vérification jeux/installation jeux
                if (targetName.startsWith('game_') && validValue > 0) {
                    const gamesSelected = this.container.find('input[name="option_games"]').is(':checked');
                    if (!gamesSelected) {
                        // Cocher automatiquement l'installation jeux
                        this.container.find('input[name="option_games"]').prop('checked', true).trigger('change');
                        this.showMessage('✅ Installation jeux automatiquement ajoutée pour vos jeux sélectionnés.', 'info');
                        setTimeout(() => this.hideMessage(), 3000);
                    }
                }
                
                // Recalculer le prix total
                this.calculatePrice();
            }
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
            
            // Récupérer le nom du produit et le type depuis le DOM
            const $beverageCard = $input.closest('.rbf-v3-beverage-card');
            let productName = 'Produit inconnu';
            let beverageType = 'soft';
            
            if ($beverageCard.length) {
                const $nameElement = $beverageCard.find('h4');
                if ($nameElement.length) {
                    productName = $nameElement.text().trim();
                }
                
                // Déterminer le type de boisson depuis les attributs data
                const category = $beverageCard.data('category') || '';
                const wineCategory = $beverageCard.data('wine-category') || '';
                const beerCategory = $beverageCard.data('beer-category') || '';
                
                if (wineCategory || category.includes('wine') || productName.toLowerCase().includes('vin')) {
                    beverageType = 'wines';
                } else if (beerCategory || category.includes('beer') || productName.toLowerCase().includes('bière') || productName.toLowerCase().includes('biere')) {
                    beverageType = 'beers';
                } else if (category.includes('soft') || productName.toLowerCase().includes('soft') || productName.toLowerCase().includes('jus') || productName.toLowerCase().includes('coca')) {
                    beverageType = 'soft';
                } else if (productName.toLowerCase().includes('fût') || productName.toLowerCase().includes('fut')) {
                    beverageType = 'kegs';
                }
            }
            
            // Récupérer la taille si c'est une boisson avec taille
            let sizeText = '';
            if (sizeId) {
                const $sizeLabel = $input.closest('.rbf-v3-size-option').find('.rbf-v3-size-label');
                if ($sizeLabel.length) {
                    sizeText = $sizeLabel.text().trim();
                }
            }
            
            // Mettre à jour l'état des boutons
            const $minusBtn = $input.siblings('.rbf-v3-qty-btn.minus');
            $minusBtn.prop('disabled', quantity <= 0);
            
            // Stocker la sélection dans formData avec les noms corrects
            if (!this.formData.beverages) {
                this.formData.beverages = {};
            }
            
            const key = sizeId ? `size_${sizeId}` : (size ? `${productId}_${size}` : productId);
            
            if (quantity > 0) {
                this.formData.beverages[key] = {
                    product_id: productId,
                    size_id: sizeId,
                    size: sizeText || size,
                    quantity: quantity,
                    price: price,
                    name: sizeText ? `${productName} ${sizeText}` : productName,
                    // ✅ NOUVEAU : Ajouter des métadonnées pour la restauration
                    category: 'boissons',
                    type: beverageType || 'soft',
                    timestamp: Date.now() // Pour détecter les données obsolètes
                };
            } else {
                delete this.formData.beverages[key];
            }
            
            // ✅ NOUVEAU : Nettoyer les données obsolètes
            this.cleanupObsoleteFormData();
            
            // Recalculer le prix
            this.calculatePrice();
        }
        
        // La méthode skipBeveragesStep a été remplacée par skipCurrentStep qui est plus générique
        
        /**
         * Calculer le prix des boissons avec détails
         */
        calculateBeveragesPrice() {
            let beveragesTotal = 0;
            const beveragesDetails = [];
            
            if (this.formData.beverages) {
                Object.values(this.formData.beverages).forEach(beverage => {
                    if (beverage.quantity > 0) {
                        const total = beverage.quantity * beverage.price;
                        beveragesTotal += total;
                        
                        // Déterminer le type de boisson
                        let type = 'Boissons';
                        const productName = beverage.name || '';
                        if (productName.toLowerCase().includes('vin')) {
                            type = 'Vins';
                        } else if (productName.toLowerCase().includes('bière') || productName.toLowerCase().includes('biere')) {
                            type = 'Bières';
                        } else if (productName.toLowerCase().includes('soft') || productName.toLowerCase().includes('jus') || productName.toLowerCase().includes('coca')) {
                            type = 'Softs';
                        } else if (productName.toLowerCase().includes('fût') || productName.toLowerCase().includes('fut')) {
                            type = 'Fûts';
                        }
                        
                        beveragesDetails.push({
                            name: beverage.name || `Produit ${beverage.product_id}`,
                            quantity: beverage.quantity,
                            price: beverage.price,
                            total: total,
                            size: beverage.size || null,
                            type: type
                        });
                    }
                });
            }
            
            // Stocker les détails pour l'affichage
            this.beveragesDetails = beveragesDetails;
            
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
                        // ✅ CORRECTION : Message de succès amélioré avec détails
                        const serviceName = this.selectedService === 'restaurant' ? 'privatisation du restaurant' : 'privatisation de la remorque Block';
                        const successMessage = `
                            <div class="rbf-v3-success-message">
                                <h2>🎉 Parfait ! Votre demande a été envoyée !</h2>
                                <div class="rbf-v3-success-details">
                                    <p><strong>Service :</strong> ${serviceName}</p>
                                    <p><strong>📧 Email de confirmation :</strong> envoyé à <code>${this.formData.client_email}</code></p>
                                    <p><strong>📞 Contact :</strong> ${this.formData.client_firstname} ${this.formData.client_name}</p>
                                    <p><strong>⏰ Prochaine étape :</strong> Nous vous recontacterons dans les plus brefs délais pour finaliser votre réservation.</p>
                                </div>
                                <div class="rbf-v3-success-actions">
                                    <p class="rbf-v3-success-note">💡 <em>Pensez à vérifier vos spams si vous ne recevez pas notre email de confirmation.</em></p>
                                </div>
                            </div>
                        `;
                        
                        this.container.find('.rbf-v3-content').html(successMessage);
                        this.showMessage('Devis envoyé avec succès ! Email de confirmation envoyé.', 'success');
                        this.navigation.hide();
                        this.calculator.hide();
                    } else {
                        this.showMessage('❌ ' + (response.data.message || 'Erreur lors de l\'envoi du devis'), 'error');
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
        /**
         * Afficher un message à l'utilisateur
         * @param {string} message - Le message à afficher
         * @param {string} type - Le type de message ('info', 'error', 'warning', 'success')
         * @param {boolean} noScroll - Si true, n'effectue pas de scroll automatique vers le haut
         */
        showMessage(message, type = 'info', noScroll = false) {
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
            
            // ✅ CORRECTION : Scroll conditionnel pour éviter les interruptions UX
            if (!noScroll) {
                this.scrollToTop();
            }
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
            let $input = this.container.find(`[name="${targetName}"]`);
            
            // Si pas trouvé dans le container, chercher globalement
            if ($input.length === 0) {
                $input = $(`[name="${targetName}"]`);
            }
            
            this.log('🔧 handleQuantityChange NOUVEAU:', {
                targetName,
                inputFound: $input.length,
                inputValue: $input.length ? $input.val() : 'N/A',
                buttonClasses: $button.attr('class')
            });
            
            if ($input.length) {
                let currentValue;
                try {
                    currentValue = parseInt($input.val()) || 0;
                } catch (error) {
                    this.log('❌ Erreur lors de la lecture de la valeur:', error);
                    return;
                }
                const minValue = parseInt($input.attr('min')) || 0;
                let maxValue = parseInt($input.attr('max')) || 999;
                
                // Validation spéciale pour les options de frites - SIMPLIFIÉE
                if (targetName && (targetName.includes('sauce_') || targetName.includes('frites_chimichurri') || targetName.includes('chimichurri'))) {
                    // Utiliser simplement l'attribut max de l'input qui est déjà défini correctement
                    const inputMaxValue = parseInt($input.attr('max'));
                    if (!isNaN(inputMaxValue) && inputMaxValue >= 0) {
                        maxValue = inputMaxValue;
                    }
                    this.log('Validation bouton frites SIMPLIFIÉE:', { targetName, currentValue, delta, maxValue });
                }
                
                const newValue = Math.max(minValue, Math.min(maxValue, currentValue + delta));
                
                if (newValue !== currentValue + delta && delta > 0) {
                    // ✅ CORRECTION : Pas de scroll pour les validations de limites
                    this.showMessage(`Vous ne pouvez pas avoir plus de sauces que de frites (maximum ${maxValue})`, 'warning', true);
                }
                
                $input.val(newValue).trigger('change');
                
                // Mettre à jour les boutons
                this.updateQuantityButtons($input);
                
                // Gestion spéciale pour les accompagnements
                if (targetName.includes('accompaniment_') && targetName.includes('_qty')) {
                    // ✅ CORRECTION : Mettre à jour les limites des options pour TOUS les accompagnements
                    const $card = $input.closest('.rbf-v3-accompaniment-card');
                    const $optionsContainer = $card.find('.rbf-v3-acc-options');
                    
                    if ($optionsContainer.length > 0) {
                        this.updateAccompanimentOptionsLimits($card, newValue);
                    }
                }
                
                // ✅ CORRECTION : Validation générique pour toutes les options d'accompagnements
                if (targetName.includes('sauce_') || targetName.includes('chimichurri') || $input.hasClass('rbf-v3-option-input')) {
                    this.validateAccompanimentOptionsTotal($input);
                }
                
                // Recalculer le prix si on est à partir de l'étape 2
                if (this.currentStep >= 2) {
                    this.calculatePrice();
                }
            } else {
                this.log('❌ Input non trouvé pour:', targetName);
                // Essayer de trouver l'input avec un sélecteur plus large
                const $alternativeInput = $(`[name="${targetName}"]`);
                if ($alternativeInput.length) {
                    this.log('✅ Input trouvé avec sélecteur global:', $alternativeInput.length);
                    // Relancer la fonction avec l'input trouvé
                    this.handleQuantityChangeForInput($alternativeInput, delta);
                } else {
                    this.log('❌ Input définitivement introuvable pour:', targetName);
                }
            }
        }

        /**
         * ✅ NOUVEAU : Gérer le changement de quantité directement avec un input trouvé
         */
        handleQuantityChangeForInput($input, delta) {
            try {
                const currentValue = parseInt($input.val()) || 0;
                const minValue = parseInt($input.attr('min')) || 0;
                const maxValue = parseInt($input.attr('max')) || 999;
                
                const newValue = Math.max(minValue, Math.min(maxValue, currentValue + delta));
                
                this.log('🔄 handleQuantityChangeForInput:', {
                    inputName: $input.attr('name'),
                    currentValue,
                    delta,
                    newValue,
                    minValue,
                    maxValue
                });
                
                $input.val(newValue).trigger('change');
                this.updateQuantityButtons($input);
                
                // Recalculer le prix si on est à partir de l'étape 2
                if (this.currentStep >= 2) {
                    this.calculatePrice();
                }
            } catch (error) {
                this.log('❌ Erreur dans handleQuantityChangeForInput:', error);
            }
        }

        /**
         * Mettre à jour l'état des boutons de quantité
         */
        updateQuantityButtons($input) {
            const value = parseInt($input.val()) || 0;
            const min = parseInt($input.attr('min')) || 0;
            let max = parseInt($input.attr('max')) || 999;
            const name = $input.attr('name');
            
            this.log('🔧 Mise à jour boutons pour:', name, 'valeur:', value);
            
            // Validation spéciale pour les options de frites
            if (name && (name.includes('sauce_') || name.includes('frites_chimichurri') || name.includes('chimichurri'))) {
                const validatedMax = this.validateFritesOptionsQuantity($input, value);
                
                // TEMPORAIRE : Si la validation retourne 999, utiliser une limite raisonnable
                if (validatedMax === 999) {
                    max = 50; // Limite raisonnable par défaut
                    this.log('🔧 Limite par défaut appliquée:', max);
                } else {
                    max = Math.min(max, Math.max(validatedMax, 1)); // Au minimum 1
                    this.log('🔧 Limite validée:', max, 'depuis validation:', validatedMax);
                }
                
                // Mettre à jour l'attribut max de l'input
                $input.attr('max', max);
            }
            
            // Chercher les boutons avec sélecteurs améliorés
            const $minusBtn = this.container.find(`button[data-target="${name}"]`).filter('.rbf-v3-qty-minus, .rbf-v3-qty-btn.rbf-v3-qty-minus');
            const $plusBtn = this.container.find(`button[data-target="${name}"]`).filter('.rbf-v3-qty-plus, .rbf-v3-qty-btn.rbf-v3-qty-plus');
            
            // S'assurer que les boutons existent
            if ($minusBtn.length === 0 || $plusBtn.length === 0) {
                this.log('⚠️ Boutons non trouvés pour:', name);
                return;
            }
            
            const shouldDisableMinus = value <= min;
            const shouldDisablePlus = value >= max;
            
            $minusBtn.prop('disabled', shouldDisableMinus);
            $plusBtn.prop('disabled', shouldDisablePlus);
            
            this.log('✅ Boutons mis à jour:', { 
                name, 
                value, 
                min, 
                max, 
                minusDisabled: shouldDisableMinus, 
                plusDisabled: shouldDisablePlus 
            });
        }

        /**
         * Charger les produits signature selon le choix DOG/CROQ
         */
        loadSignatureProducts(type) {
            const data = {
                action: 'rbf_v3_load_signature_products',
                nonce: rbfV3Config.nonce,
                signature_type: type,
                guest_count: this.formData.guest_count || 10,
                form_data: this.formData
            };

            $.ajax({
                url: rbfV3Config.ajaxUrl,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        this.container.find('.rbf-v3-signature-products').html(response.data.html).show();
                        this.initializeQuantitySelectors();
                        // ✅ CORRECTION : Restaurer les quantités après le chargement des produits signature
                        this.restoreQuantityValues();
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
            // Gérer les deux structures possibles (ancienne et nouvelle)
            const $item = $checkbox.closest('.rbf-v3-accompaniment-item, .rbf-v3-product-card-full');
            const $qtyInput = $item.find('.rbf-v3-qty-input');
            const $options = $item.find('.rbf-v3-frites-options');
            
            if ($checkbox.is(':checked')) {
                // Activer avec quantité minimum
                const guestCount = parseInt(this.formData.guest_count) || 10;
                $qtyInput.val(guestCount).trigger('change');
                
                // Afficher les options pour les frites
                const productName = $item.find('.rbf-v3-accompaniment-name, .rbf-v3-product-title').text().toLowerCase();
                if ($checkbox.attr('name').includes('frites') || productName.includes('frites')) {
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
            const $card = $input.closest('.rbf-v3-product-card-full');
            // Maintenant les options sont à l'intérieur de la carte
            const $options = $card.find('.rbf-v3-frites-options');
            
            // Vérifier si c'est des frites (chercher dans le titre de la card)
            const cardTitle = $card.find('.rbf-v3-product-title').text().toLowerCase();
            if (cardTitle.includes('frites')) {
                if (quantity > 0) {
                    $options.slideDown(() => {
                        // Après l'animation, s'assurer que les boutons sont bien initialisés
                        $options.find('.rbf-v3-qty-input').each((index, input) => {
                            const $optionInput = $(input);
                            // Limiter la valeur actuelle si elle dépasse la nouvelle quantité de frites
                            const currentValue = parseInt($optionInput.val()) || 0;
                            if (currentValue > quantity) {
                                $optionInput.val(quantity);
                            }
                            // Mettre à jour l'attribut max
                            $optionInput.attr('max', quantity);
                            this.updateQuantityButtons($optionInput);
                            
                            // Réattacher les event listeners pour les boutons d'options
                            const inputName = $optionInput.attr('name');
                            const $optionMinusBtn = this.container.find(`button[data-target="${inputName}"]`).filter('.rbf-v3-qty-minus');
                            const $optionPlusBtn = this.container.find(`button[data-target="${inputName}"]`).filter('.rbf-v3-qty-plus');
                            
                            // Vérifier que les boutons sont bien trouvés
                            if ($optionMinusBtn.length === 0 || $optionPlusBtn.length === 0) {
                                this.log('⚠️ Boutons d\'options non trouvés pour:', inputName);
                            }
                        });
                        this.log('Options de frites affichées et boutons initialisés pour', quantity, 'frites');
                    });
                } else {
                    $options.slideUp();
                    // Remettre toutes les options à 0
                    $options.find('.rbf-v3-qty-input').val(0).trigger('change');
                    $options.find('input[type="checkbox"]').prop('checked', false);
                }
                
                // Dans tous les cas, mettre à jour les boutons des options existantes
                // pour refléter la nouvelle quantité de frites disponible
                $options.find('.rbf-v3-qty-input').each((index, input) => {
                    const $optionInput = $(input);
                    $optionInput.attr('max', quantity);
                    this.updateQuantityButtons($optionInput);
                });
            }
        }

        /**
         * Valider les quantités des options de frites
         */
        validateFritesOptionsQuantity($input, proposedValue) {
            this.log('🔍 VALIDATION FRITES - Input concerné:', $input.attr('name'));
            
            // SOLUTION SIMPLE ET DIRECTE : Chercher les frites par leur nom dans le DOM
            let totalFrites = 0;
            let $fritesQtyInput = null;
            
            // Chercher TOUS les inputs d'accompagnements dans le formulaire
            this.container.find('input[name*="accompaniment_"][name$="_qty"]').each((index, input) => {
                const $accompInput = $(input);
                const inputName = $accompInput.attr('name');
                const inputValue = parseInt($accompInput.val()) || 0;
                
                // Trouver le titre du produit associé
                const $card = $accompInput.closest('.rbf-v3-product-card-full');
                let productTitle = '';
                if ($card.length) {
                    productTitle = $card.find('.rbf-v3-product-title').text().toLowerCase();
                }
                
                this.log(`Test accompagnement ${index}:`, {
                    name: inputName,
                    value: inputValue,
                    title: productTitle
                });
                
                // Si c'est des frites, on garde cette valeur
                if (productTitle.includes('frites') || productTitle.includes('frite')) {
                    totalFrites = inputValue;
                    $fritesQtyInput = $accompInput;
                    this.log('✅ FRITES TROUVÉES:', {
                        input: inputName,
                        quantité: totalFrites,
                        titre: productTitle
                    });
                    return false; // Arrêter la recherche
                }
            });
            
            // Si on n'a toujours rien trouvé, chercher dans formData
            if (totalFrites === 0 && this.formData) {
                this.log('🔍 Recherche dans formData...');
                Object.entries(this.formData).forEach(([key, value]) => {
                    if (key.includes('accompaniment_') && key.includes('_qty')) {
                        this.log(`FormData: ${key} = ${value}`);
                        if (key.toLowerCase().includes('frites') || key.toLowerCase().includes('frite')) {
                            totalFrites = parseInt(value) || 0;
                            this.log('✅ FRITES TROUVÉES dans formData:', { key, totalFrites });
                        }
                    }
                });
            }
            
            // STRATÉGIE DYNAMIQUE : Chercher tout produit d'accompagnement de type "frites"
            if (totalFrites === 0) {
                this.log('🔍 Recherche dynamique de produits type frites...');
                
                // Chercher tous les inputs d'accompagnements dans le DOM
                const $allAccompanimentInputs = this.container.find('input[name^="accompaniment_"][name$="_qty"]');
                $allAccompanimentInputs.each((index, input) => {
                    const $input = $(input);
                    const inputName = $input.attr('name');
                    const inputValue = parseInt($input.val()) || 0;
                    
                    // Chercher dans le label ou data attribute si c'est des frites
                    const $productCard = $input.closest('.rbf-v3-product-card, .rbf-v3-accompaniment-card');
                    const productName = $productCard.find('.rbf-v3-product-name, .rbf-v3-card-title').text().toLowerCase();
                    
                    if (productName.includes('frites') || productName.includes('frite')) {
                        totalFrites += inputValue;
                        if (inputValue > 0) {
                            $fritesQtyInput = $input;
                        }
                        this.log('✅ FRITES TROUVÉES dynamiquement:', { inputName, inputValue, productName });
                    }
                });
                
                // Fallback : chercher dans formData
                if (totalFrites === 0 && this.formData) {
                    Object.keys(this.formData).forEach(key => {
                        if (key.includes('accompaniment_') && key.includes('_qty')) {
                            const value = parseInt(this.formData[key]) || 0;
                            if (value > 0) {
                                // On ne peut pas déterminer le type depuis formData seule, donc on prend la première quantité trouvée
                                totalFrites += value;
                                this.log('✅ FRITES possibles trouvées dans formData:', { key, value });
                            }
                        }
                    });
                }
            }
            
            this.log('🎯 RÉSULTAT FINAL:', {
                totalFrites,
                inputTrouvé: $fritesQtyInput ? $fritesQtyInput.attr('name') : 'aucun',
                proposedValue
            });
            
            // Si on ne trouve aucune frite, retourner 0 pour bloquer les options
            if (totalFrites === 0) {
                this.log('❌ Aucune frite trouvée - Options bloquées');
                return 0; // Bloquer les options si pas de frites
            }
            
            // Calculer le total de TOUTES les autres options (sauf celle en cours de modification)
            let totalOtherOptions = 0;
            const currentInputName = $input.attr('name');
            
            // Chercher dans toute la section des options de frites
            const $fritesOptions = $input.closest('.rbf-v3-frites-options');
            if ($fritesOptions.length) {
                $fritesOptions.find('input[name^="sauce_"][name$="_qty"], input[name*="chimichurri"][name$="_qty"], input[name*="enrobee"][name$="_qty"]').each(function() {
                    if ($(this).attr('name') !== currentInputName) {
                        totalOtherOptions += parseInt($(this).val()) || 0;
                    }
                });
            }
            
            // Le maximum pour cette option = total frites - autres options utilisées
            const maxForThisOption = Math.max(0, totalFrites - totalOtherOptions);
            
            this.log('Validation frites DÉTAILLÉE:', {
                totalFrites,
                totalOtherOptions,
                maxForThisOption,
                proposedValue,
                currentInput: currentInputName,
                calculation: `${totalFrites} frites - ${totalOtherOptions} autres = ${maxForThisOption} max`,
                fritesInputName: $fritesQtyInput ? $fritesQtyInput.attr('name') : 'non trouvé'
            });
            
            // Si pas de frites, pas d'options possibles
            if (totalFrites === 0) {
                return 0;
            }
            
            return maxForThisOption;
        }

        /**
         * Forcer la mise à jour de tous les boutons d'options de frites
         */
        forceUpdateFritesOptionsButtons() {
            this.log('🔄 FORCE UPDATE - Mise à jour de tous les boutons d\'options de frites');
            
            // Trouver tous les inputs d'options de frites
            const $optionInputs = this.container.find('input[name^="sauce_"][name$="_qty"], input[name*="chimichurri"][name$="_qty"], input[name*="enrobee"][name$="_qty"]');
            
            $optionInputs.each((index, input) => {
                const $input = $(input);
                const inputName = $input.attr('name');
                
                this.log(`🔄 Mise à jour boutons pour: ${inputName}`);
                
                // Recalculer le max pour cette option
                const maxAllowed = this.validateFritesOptionsQuantity($input, 0);
                
                // Mettre à jour l'attribut max
                $input.attr('max', maxAllowed);
                
                // Ajuster la valeur si elle dépasse le max
                const currentValue = parseInt($input.val()) || 0;
                if (currentValue > maxAllowed) {
                    $input.val(maxAllowed);
                    this.log(`⚠️ Valeur ajustée de ${currentValue} à ${maxAllowed} pour ${inputName}`);
                }
                
                // Mettre à jour les boutons
                this.updateQuantityButtons(inputName, maxAllowed);
            });
        }

        /**
         * Ajuster les quantités des options de frites quand le nombre de frites change
         */
        adjustFritesOptionsToLimit($fritesInput, totalFrites) {
            const $fritesCard = $fritesInput.closest('.rbf-v3-product-card-full');
            const $optionInputs = $fritesCard.find('input[name^="sauce_"][name$="_qty"], input[name="frites_chimichurri_qty"]');
            
            if (totalFrites === 0) {
                // Si plus de frites, remettre toutes les options à 0
                $optionInputs.val(0);
                return;
            }
            
            // Calculer le total actuel des options
            let totalOptions = 0;
            $optionInputs.each(function() {
                totalOptions += parseInt($(this).val()) || 0;
            });
            
            // Si le total des options dépasse le nombre de frites, les ajuster proportionnellement
            if (totalOptions > totalFrites) {
                this.log('Ajustement proportionnel des options de frites:', {
                    totalFrites,
                    totalOptions,
                    ratio: totalFrites / totalOptions
                });
                
                let remainingFrites = totalFrites;
                $optionInputs.each(function(index, input) {
                    const $input = $(input);
                    const currentValue = parseInt($input.val()) || 0;
                    
                    if (currentValue > 0 && remainingFrites > 0) {
                        // Calculer la nouvelle valeur proportionnelle
                        const proportionalValue = Math.floor(currentValue * totalFrites / totalOptions);
                        const newValue = Math.min(proportionalValue, remainingFrites, currentValue);
                        
                        $input.val(newValue);
                        remainingFrites -= newValue;
                    } else if (remainingFrites === 0) {
                        $input.val(0);
                    }
                });
                
                this.showMessage(`Les quantités de sauces ont été ajustées car vous n'avez que ${totalFrites} frites`, 'info');
            }
        }

        /**
         * Gérer les options des frites (checkboxes)
         */
        handleFritesOptionToggle($checkbox) {
            const $row = $checkbox.closest('.rbf-v3-option-row');
            const $quantitySelector = $row.find('.rbf-v3-quantity-selector');
            const $input = $quantitySelector.find('.rbf-v3-qty-input');
            
            this.log('🔄 Toggle option frites:', $checkbox.attr('name'), 'checked:', $checkbox.is(':checked'));
            
            if ($checkbox.is(':checked')) {
                $quantitySelector.show();
                // Mettre au minimum 1 si c'est coché
                if (parseInt($input.val()) === 0) {
                    $input.val(1);
                }
                
                // Forcer la mise à jour des attributs max et des boutons
                setTimeout(() => {
                    this.updateQuantityButtons($input);
                    this.log('✅ Boutons mis à jour après toggle pour:', $input.attr('name'));
                }, 50);
                
            } else {
                $quantitySelector.hide();
                $input.val(0).trigger('change');
            }
            
            // Toujours mettre à jour les boutons et valider
            this.updateQuantityButtons($input);
            this.validateFritesOptions();
            this.calculatePrice();
        }

        /**
         * Réinitialiser spécifiquement les boutons des options de frites
         */
        reinitializeFritesButtons() {
            const $fritesOptions = this.container.find('.rbf-v3-frites-options:visible');
            this.log('Réinitialisation des boutons de frites:', $fritesOptions.length, 'sections trouvées');
            
            $fritesOptions.each((index, option) => {
                const $option = $(option);
                const $inputs = $option.find('.rbf-v3-qty-input');
                
                $inputs.each((inputIndex, input) => {
                    const $input = $(input);
                    const name = $input.attr('name');
                    this.log(`Réinitialisation input frites: ${name}`);
                    this.updateQuantityButtons($input);
                });
            });
        }

        /**
         * Corriger les options de frites après chargement de l'étape 3
         */
        fixFritesOptionsAfterLoad() {
            this.log('🔧 Correction des options de frites après chargement...');
            
            // Trouver toutes les sections d'options de frites
            const $fritesOptions = this.container.find('.rbf-v3-frites-options');
            
            $fritesOptions.each((index, optionsSection) => {
                const $optionsSection = $(optionsSection);
                
                // Trouver la carte produit parente pour récupérer la quantité de frites
                const $productCard = $optionsSection.closest('.rbf-v3-product-card-full');
                const $fritesQtyInput = $productCard.find('input[name$="_qty"]:not([name*="sauce"]):not([name*="chimichurri"]):not([name*="enrob"])').first();
                const fritesQty = parseInt($fritesQtyInput.val()) || 0;
                
                this.log(`🍟 Frites trouvées: ${fritesQty} (input: ${$fritesQtyInput.attr('name')})`);
                
                // Afficher/masquer selon la quantité de frites
                if (fritesQty > 0) {
                    $optionsSection.show();
                } else {
                    $optionsSection.hide();
                    // Remettre toutes les options à 0 si pas de frites
                    $optionsSection.find('.rbf-v3-qty-input').val(0);
                    $optionsSection.find('input[type="checkbox"]').prop('checked', false);
                    return; // Pas besoin de continuer si pas de frites
                }
                
                // Mettre à jour tous les inputs d'options avec la vraie limite
                $optionsSection.find('.rbf-v3-qty-input').each((inputIndex, input) => {
                    const $input = $(input);
                    const name = $input.attr('name');
                    
                    // Utiliser la quantité de frites comme limite max
                    $input.attr('max', fritesQty);
                    
                    // Valider et ajuster la valeur actuelle
                    const currentValue = parseInt($input.val()) || 0;
                    if (currentValue > fritesQty) {
                        $input.val(fritesQty);
                    }
                    
                    // Mettre à jour les boutons avec la vraie validation
                    this.updateQuantityButtons($input);
                    
                    this.log(`✅ Option corrigée: ${name} (max: ${fritesQty}, value: ${$input.val()})`);
                });
                
                // Afficher les sélecteurs pour les options cochées
                $optionsSection.find('input[type="checkbox"]:checked').each((checkboxIndex, checkbox) => {
                    const $checkbox = $(checkbox);
                    const $row = $checkbox.closest('.rbf-v3-option-row');
                    const $quantitySelector = $row.find('.rbf-v3-quantity-selector');
                    $quantitySelector.show();
                });
            });
            
            this.log('✅ Correction des options de frites terminée');
        }

        /**
         * Debug des boutons dans l'étape 3
         */
        debugButtonsInStep3() {
            this.log('🔍 DEBUG - Analyse des boutons dans l\'étape 3');
            
            const $allButtons = this.container.find('button');
            this.log(`Total boutons trouvés: ${$allButtons.length}`);
            
            const $qtyButtons = this.container.find('.rbf-v3-qty-btn');
            this.log(`Boutons quantité trouvés: ${$qtyButtons.length}`);
            
            $qtyButtons.each((index, btn) => {
                const $btn = $(btn);
                this.log(`Bouton ${index}:`, {
                    classes: $btn.attr('class'),
                    target: $btn.data('target'),
                    text: $btn.text(),
                    disabled: $btn.prop('disabled'),
                    visible: $btn.is(':visible')
                });
            });
            
            // Tester un clic programmatique
            const $firstPlusBtn = this.container.find('.rbf-v3-qty-plus').first();
            if ($firstPlusBtn.length) {
                this.log('🧪 Test clic programmatique sur premier bouton +');
                $firstPlusBtn.trigger('click');
            }
        }

        /**
         * Initialiser tous les sélecteurs de quantité
         */
        initializeQuantitySelectors() {
            this.log('Initialisation des sélecteurs de quantité...');
            
            this.container.find('.rbf-v3-qty-input').each((index, input) => {
                const $input = $(input);
                const name = $input.attr('name');
                this.log(`Initialisation input: ${name}`);
                this.updateQuantityButtons($input);
            });

            // Log des boutons trouvés
            const $buttons = this.container.find('.rbf-v3-qty-btn');
            this.log(`Boutons quantité trouvés: ${$buttons.length}`);
            
            $buttons.each((index, btn) => {
                const $btn = $(btn);
                const target = $btn.data('target');
                const classes = $btn.attr('class');
                this.log(`Bouton ${index}: target=${target}, classes=${classes}`);
            });

            // Gestion des radio buttons pour les buffets
            this.container.find('input[name="buffet_type"]').off('change').on('change', (e) => {
                const selectedType = $(e.currentTarget).val();
                this.showBuffetSections(selectedType);
            });
        }

        /**
         * Restaurer les valeurs des quantités depuis formData
         */
        restoreQuantityValues() {
            if (!this.formData) return;

            // Restaurer les quantités d'accompagnements
            this.container.find('input[name^="accompaniment_"][name$="_qty"]').each((index, input) => {
                const $input = $(input);
                const fieldName = $input.attr('name');
                if (this.formData[fieldName]) {
                    $input.val(this.formData[fieldName]);
                    this.updateQuantityButtons($input);
                }
            });

            // Restaurer les quantités de produits signature
            this.container.find('input[name^="signature_"][name$="_qty"]').each((index, input) => {
                const $input = $(input);
                const fieldName = $input.attr('name');
                if (this.formData[fieldName]) {
                    $input.val(this.formData[fieldName]);
                    this.updateQuantityButtons($input);
                }
            });

            // Restaurer les quantités de boissons
            if (this.formData.beverages) {
                // CORRECTION : Utiliser le nouveau format de stockage
                Object.entries(this.formData.beverages).forEach(([key, beverage]) => {
                    if (beverage.quantity > 0) {
                        // Pour les boissons avec tailles (size_id)
                        if (beverage.size_id) {
                            const $input = this.container.find(`input[data-size-id="${beverage.size_id}"]`);
                            if ($input.length > 0) {
                                $input.val(beverage.quantity);
                                this.updateQuantityButtons($input);
                                this.log(`Restauré boisson taille: ${beverage.name} = ${beverage.quantity}`);
                            }
                        } else {
                            // Pour les boissons sans tailles multiples
                            const $input = this.container.find(`input[data-product-id="${beverage.product_id}"]`);
                            if ($input.length > 0) {
                                $input.val(beverage.quantity);
                                this.updateQuantityButtons($input);
                                this.log(`Restauré boisson: ${beverage.name} = ${beverage.quantity}`);
                            }
                        }
                    }
                });
            }
            
            // FALLBACK : Ancien format pour compatibilité
            this.container.find('input[data-size-id]').each((index, input) => {
                const $input = $(input);
                const sizeId = $input.data('size-id');
                const fieldName = `beverage_size_${sizeId}_qty`;
                if (this.formData[fieldName]) {
                    $input.val(this.formData[fieldName]);
                    this.updateQuantityButtons($input);
                }
            });

            // Restaurer les autres types de quantités
            this.container.find('.rbf-v3-qty-input').each((index, input) => {
                const $input = $(input);
                const fieldName = $input.attr('name');
                if (fieldName && this.formData[fieldName] && !$input.val()) {
                    $input.val(this.formData[fieldName]);
                    this.updateQuantityButtons($input);
                }
            });

            // ✅ CORRECTION : Restaurer les cases à cocher
            this.container.find('input[type="checkbox"]').each((index, input) => {
                const $input = $(input);
                const fieldName = $input.attr('name');
                if (fieldName && this.formData[fieldName]) {
                    $input.prop('checked', Boolean(this.formData[fieldName]));
                }
            });

            // ✅ CORRECTION : Restaurer les boutons radio
            this.container.find('input[type="radio"]').each((index, input) => {
                const $input = $(input);
                const fieldName = $input.attr('name');
                if (fieldName && this.formData[fieldName] && $input.val() === this.formData[fieldName]) {
                    $input.prop('checked', true);
                }
            });

            this.log('Valeurs de quantité et sélections restaurées depuis formData');
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
         * ✅ CORRECTION : Toggle de la sélection des fûts (option tireuse)
         */
        toggleKegsSelection(enabled) {
            const $container = this.container.find('.rbf-v3-kegs-selection');
            
            if (enabled) {
                $container.slideDown();
                this.initializeQuantitySelectors();
                
                // ✅ CORRECTION : Initialiser l'affichage de la première catégorie de fûts
                const $firstTab = $container.find('.rbf-v3-tab-btn.active');
                if ($firstTab.length > 0) {
                    const firstCategory = $firstTab.data('tab');
                    this.filterKegsByCategory(firstCategory);
                }
                
                // Afficher un message d'information
                this.showMessage('✅ Tireuse sélectionnée ! Vous pouvez maintenant choisir vos fûts.', 'info');
                setTimeout(() => this.hideMessage(), 3000);
            } else {
                $container.slideUp();
                // Remettre toutes les quantités à 0
                $container.find('.rbf-v3-qty-input').val(0).trigger('change');
                // Vérifier s'il y avait des fûts sélectionnés
                let hadKegsSelected = false;
                $container.find('.rbf-v3-qty-input').each((index, input) => {
                    if (parseInt($(input).val()) > 0) {
                        hadKegsSelected = true;
                        return false;
                    }
                });
                if (hadKegsSelected) {
                    this.showMessage('⚠️ Tireuse désélectionnée - Les fûts ont été automatiquement retirés.', 'info');
                    setTimeout(() => this.hideMessage(), 3000);
                }
            }
            
            // Mettre à jour l'estimation immédiatement
            this.updatePriceDisplay();
        }

        /**
         * ✅ CORRECTION : Toggle de la sélection des jeux (option jeux)
         */
        toggleGamesSelection(enabled) {
            const $container = this.container.find('.rbf-v3-games-selection');
            
            if (enabled) {
                $container.slideDown();
                this.showMessage('✅ Installation jeux sélectionnée ! Vous pouvez maintenant choisir vos jeux.', 'info');
                setTimeout(() => this.hideMessage(), 3000);
            } else {
                $container.slideUp();
                // Désélectionner tous les jeux
                $container.find('input[type="checkbox"]').prop('checked', false).trigger('change');
                this.showMessage('⚠️ Installation jeux désélectionnée - Les jeux ont été automatiquement retirés.', 'info');
                setTimeout(() => this.hideMessage(), 3000);
            }
            
            // Mettre à jour l'estimation immédiatement
            this.updatePriceDisplay();
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
                    this.log('Étape 6: Validation selon le service');
                    return this.validateStep6();
                case 7:
                    this.log('Étape 7: Validation des coordonnées (remorque)');
                    return this.validateStep7();
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
            // Récupérer le nombre de convives depuis les données sauvegardées du formulaire
            let guestCount = parseInt(this.formData.guest_count) || 0;
            
            // Si pas trouvé dans les données sauvegardées, chercher dans le DOM
            if (guestCount === 0) {
                guestCount = parseInt(this.container.find('[name="guest_count"]').val()) || 0;
            }
            
            // Si toujours pas trouvé, chercher dans tout le document
            if (guestCount === 0) {
                guestCount = parseInt($('[name="guest_count"]').val()) || 0;
            }
            
            let isValid = true;
            let errors = [];

            this.log('Validation étape 3 - Nombre de convives:', guestCount);
            this.log('FormData guest_count:', this.formData.guest_count);

            // Vérifier plats signature
            const signatureType = this.container.find('input[name="signature_type"]:checked').val();
            if (!signatureType) {
                isValid = false;
                errors.push('🍽️ Veuillez sélectionner un type de plat signature (DOG ou CROQ).');
            } else {
                // Vérifier si les produits signature ont été chargés
                const signatureInputs = this.container.find('input[name^="signature_"][name$="_qty"]');
                this.log('Champs signature trouvés:', signatureInputs.length);
                
                if (signatureInputs.length === 0) {
                    isValid = false;
                    errors.push('🍽️ Les produits signature ne sont pas encore chargés. Veuillez attendre un moment et réessayer.');
                } else {
                    let totalSignatureQty = 0;
                    signatureInputs.each((index, input) => {
                        const qty = parseInt($(input).val()) || 0;
                        totalSignatureQty += qty;
                        this.log(`Plat signature ${index} (${$(input).attr('name')}):`, qty);
                    });

                    this.log('Total plats signature:', totalSignatureQty);
                    this.log('Validation plats signature:', totalSignatureQty >= guestCount ? 'RÉUSSIE' : 'ÉCHOUÉE');

                    if (totalSignatureQty < guestCount) {
                        isValid = false;
                        errors.push(`❌🍽️ Quantité insuffisante ! Il faut au minimum ${guestCount} plats signature pour ${guestCount} convives. Actuellement sélectionnés : ${totalSignatureQty} plats.`);
                        
                        // Mettre en évidence les champs concernés
                        signatureInputs.each((index, input) => {
                            $(input).addClass('rbf-v3-field-error');
                        });
                    } else {
                        // Retirer la classe d'erreur si validation réussie
                        signatureInputs.each((index, input) => {
                            $(input).removeClass('rbf-v3-field-error');
                        });
                    }
                }
            }

            // Vérifier accompagnements
            let totalAccompanimentQty = 0;
            const accompanimentInputs = this.container.find('input[name^="accompaniment_"][name$="_qty"]');
            this.log('Champs accompagnement trouvés:', accompanimentInputs.length);
            this.log('Sélecteur utilisé:', 'input[name^="accompaniment_"][name$="_qty"]');
            
            if (accompanimentInputs.length === 0) {
                isValid = false;
                errors.push('🥗 Les accompagnements ne sont pas encore chargés. Veuillez recharger la page.');
            } else {
                accompanimentInputs.each((index, input) => {
                    const $input = $(input);
                    // Chercher dans les deux types de conteneurs possibles
                    const $container = $input.closest('.rbf-v3-accompaniment-item, .rbf-v3-accompaniment-card');
                    const $checkbox = $container.find('input[name^="accompaniment_"][name$="_enabled"]');
                    
                    // Vérifier si l'accompagnement est activé (checkbox cochée) ou s'il n'y a pas de checkbox (mode simple)
                    if ($checkbox.length === 0 || $checkbox.is(':checked')) {
                        const qty = parseInt($input.val()) || 0;
                        totalAccompanimentQty += qty;
                        this.log(`Accompagnement ${index} (${$input.attr('name')}):`, qty, 'checkbox found:', $checkbox.length > 0, 'checked:', $checkbox.is(':checked'));
                    }
                });

                this.log('Total accompagnements:', totalAccompanimentQty);
                this.log('Nombre de convives requis (minimum):', guestCount);
                this.log('Validation accompagnements:', totalAccompanimentQty >= guestCount ? 'RÉUSSIE' : 'ÉCHOUÉE');

                if (totalAccompanimentQty < guestCount) {
                    isValid = false;
                    errors.push(`🥗 Quantité insuffisante ! Il faut au minimum ${guestCount} accompagnements pour ${guestCount} convives. Actuellement sélectionnés : ${totalAccompanimentQty} accompagnements.`);
                    
                    // Mettre en évidence les champs concernés
                    accompanimentInputs.each((index, input) => {
                        $(input).addClass('rbf-v3-field-error');
                    });
                } else {
                    // Retirer la classe d'erreur si validation réussie
                    accompanimentInputs.each((index, input) => {
                        $(input).removeClass('rbf-v3-field-error');
                    });
                }
            }

            // ✅ NOUVEAU : Valider les options de frites si des frites sont sélectionnées
            const fritesQuantity = this.getFritesQuantity();
            if (fritesQuantity > 0) {
                const saucesQuantity = this.getSaucesTotalQuantity();
                const chimichurriQuantity = this.getChimichurriQuantity();
                
                if (saucesQuantity > fritesQuantity) {
                    isValid = false;
                    errors.push(`🍟 Trop de sauces ! Vous avez ${fritesQuantity} frites mais ${saucesQuantity} sauces. Maximum ${fritesQuantity} sauces.`);
                }
                
                if (chimichurriQuantity > fritesQuantity) {
                    isValid = false;
                    errors.push(`🍟 Trop de chimichurri ! Vous avez ${fritesQuantity} frites mais ${chimichurriQuantity} chimichurri. Maximum ${fritesQuantity}.`);
                }
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
         * ✅ NOUVEAU : Obtenir la quantité totale de frites
         */
        getFritesQuantity() {
            let total = 0;
            const fritesInputs = this.container.find('input[name^="accompaniment_"][name$="_qty"]');
            fritesInputs.each((index, input) => {
                const $input = $(input);
                const $card = $input.closest('.rbf-v3-product-card, .rbf-v3-accompaniment-card');
                const title = $card.find('.rbf-v3-product-title').text().toLowerCase();
                if (title.includes('frites')) {
                    total += parseInt($input.val()) || 0;
                }
            });
            return total;
        }

        /**
         * ✅ NOUVEAU : Obtenir la quantité totale de sauces
         */
        getSaucesTotalQuantity() {
            let total = 0;
            const sauces = ['ketchup', 'mayonnaise', 'moutarde', 'sauce_bbq'];
            sauces.forEach(sauce => {
                const qty = parseInt(this.formData[`sauce_${sauce}_qty`] || 0);
                total += qty;
            });
            return total;
        }

        /**
         * ✅ NOUVEAU : Obtenir la quantité de chimichurri
         */
        getChimichurriQuantity() {
            return parseInt(this.formData['frites_chimichurri_qty'] || 0);
        }

        /**
         * ✅ CORRECTION : Valider que le total des options ne dépasse pas le nombre d'accompagnements
         */
        validateAccompanimentOptionsTotal($changedInput) {
            // Trouver la card d'accompagnement qui contient cette option
            const $accCard = $changedInput.closest('.rbf-v3-accompaniment-card');
            
            if ($accCard.length === 0) {
                this.log('❌ Card d\'accompagnement non trouvée');
                return;
            }
            
            const accName = $accCard.find('.rbf-v3-acc-title').text();
            
            // Obtenir le nombre d'accompagnements
            const $accInput = $accCard.find('input[name^="accompaniment_"][name$="_qty"]');
            const accQuantity = parseInt($accInput.val()) || 0;
            
            if (accQuantity === 0) {
                // Pas d'accompagnement, remettre toutes les options à 0
                $accCard.find('.rbf-v3-option-input').val(0);
                $accCard.find('.rbf-v3-option-input').each((index, input) => {
                    this.updateQuantityButtons($(input));
                });
                this.log(`🥗 Pas de ${accName}, options remises à 0`);
                return;
            }
            
            // Calculer le total de TOUTES les options
            let totalOptions = 0;
            const $allOptionInputs = $accCard.find('.rbf-v3-option-input');
            
            $allOptionInputs.each(function() {
                totalOptions += parseInt($(this).val()) || 0;
            });
            
            this.log(`🔍 Validation options ${accName}:`, {
                accQuantity,
                totalOptions,
                maxAllowed: accQuantity
            });
            
            // Si le total dépasse, ajuster
            if (totalOptions > accQuantity) {
                const excess = totalOptions - accQuantity;
                this.log('⚠️ Dépassement détecté:', excess);
                
                // Réduire la valeur de l'input qui vient d'être modifié
                const currentValue = parseInt($changedInput.val()) || 0;
                const newValue = Math.max(0, currentValue - excess);
                
                $changedInput.val(newValue);
                this.updateQuantityButtons($changedInput);
                
                // ✅ CORRECTION : Afficher le message sans scroll pour ne pas perturber l'utilisateur
                this.showMessage(`Maximum ${accQuantity} options au total pour ${accQuantity} ${accName}. Valeur ajustée.`, 'warning', true);
                
                this.log('✅ Valeur ajustée:', {
                    oldValue: currentValue,
                    newValue: newValue,
                    reduction: excess
                });
            }
        }

        /**
         * ✅ ANCIEN : Valider que le total des options ne dépasse pas le nombre de frites (gardé pour compatibilité)
         * @deprecated Utiliser validateAccompanimentOptionsTotal à la place
         */
        validateFritesOptionsTotal($changedInput) {
            return this.validateAccompanimentOptionsTotal($changedInput);
        }

        /**
         * ✅ CORRECTION : Mettre à jour les limites des options pour tous les accompagnements
         */
        updateAccompanimentOptionsLimits($accCard, accQuantity) {
            const accName = $accCard.find('.rbf-v3-acc-title').text();
            this.log('🔄 Mise à jour limites options pour', accName + ':', accQuantity);
            
            // Mettre à jour l'attribut data-max-total
            $accCard.find('.rbf-v3-acc-options').attr('data-max-total', accQuantity);
            
            // Mettre à jour l'attribut max de tous les inputs d'options
            const $optionInputs = $accCard.find('.rbf-v3-option-input');
            $optionInputs.attr('max', accQuantity);
            
            // Ajuster les valeurs qui dépassent la nouvelle limite
            $optionInputs.each(function() {
                const $input = $(this);
                const currentValue = parseInt($input.val()) || 0;
                
                if (currentValue > accQuantity) {
                    $input.val(accQuantity);
                    this.updateQuantityButtons($input);
                }
            }.bind(this));
        }

        /**
         * ✅ ANCIEN : Mettre à jour les limites des options de frites (gardé pour compatibilité)
         * @deprecated Utiliser updateAccompanimentOptionsLimits à la place
         */
        updateFritesOptionsLimits($fritesCard, fritesQuantity) {
            return this.updateAccompanimentOptionsLimits($fritesCard, fritesQuantity);
        }

        /**
         * Valider l'étape 4 (buffets)
         */
        validateStep4() {
            // ✅ CORRECTION : Utiliser this.formData au lieu de chercher dans le DOM
            const guestCount = parseInt(this.formData.guest_count) || 0;
            let isValid = true;
            let errors = [];

            this.log('Validation étape 4 - Nombre de convives:', guestCount);

            // Vérifier si au moins un buffet est sélectionné
            const buffetType = this.container.find('input[name="buffet_type"]:checked').val();
            this.log('Type de buffet sélectionné:', buffetType);
            
            // ✅ CORRECTION : Les buffets sont OBLIGATOIRES selon le cahier des charges
            if (!buffetType) {
                isValid = false;
                errors.push('🍽️ Veuillez sélectionner un type de buffet (obligatoire).');
                this.log('Aucun buffet sélectionné - validation échouée');
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
         * Valider l'étape 6 (coordonnées pour restaurant OU options pour remorque)
         */
        validateStep6() {
            let isValid = true;
            let errors = [];

            // Pour la remorque, l'étape 6 est les options (optionnelles)
            if (this.selectedService === 'remorque') {
                this.log('Validation étape 6 - Options remorque (optionnelles)');
                // Les options sont optionnelles, donc toujours valide
                // Mais on vérifie la cohérence fûts/tireuses
                return this.validateKegsAndTireuse();
            }

            // Pour le restaurant, l'étape 6 est les coordonnées
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
         * Valider l'étape 7 (coordonnées pour remorque)
         */
        validateStep7() {
            let isValid = true;
            let errors = [];

            this.log('Validation étape 7 - Coordonnées (remorque)');

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

            this.log('Validation étape 7 - Résultat:', { isValid, errors });

            if (!isValid) {
                this.showMessage(errors.join('<br>'), 'error');
            } else {
                this.hideMessage();
            }

            return isValid;
        }

        /**
         * Valider la cohérence fûts/tireuses
         */
        validateKegsAndTireuse() {
            // Vérifier si des fûts sont sélectionnés
            let kegsSelected = false;
            this.container.find('input[name^="keg_"][name$="_qty"]').each((index, input) => {
                if (parseInt($(input).val()) > 0) {
                    kegsSelected = true;
                    return false; // Sortir de la boucle
                }
            });

            // Vérifier si la tireuse est sélectionnée
            const tireuseSelected = this.container.find('input[name="option_tireuse"]').is(':checked');

            if (kegsSelected && !tireuseSelected) {
                this.showMessage('⚠️ Attention : Vous avez sélectionné des fûts mais pas de tireuse. Les fûts nécessitent une tireuse pour être servis.', 'error');
                return false;
            }

            return true;
        }

        /**
         * ✅ CORRECTION : Filtrer les boissons par type
         */
        filterBeverages($filterBtn) {
            const filter = $filterBtn.data('filter');
            const $container = $filterBtn.closest('.rbf-v3-tab-content');
            
            // Mettre à jour les boutons de filtre
            $filterBtn.siblings().removeClass('active');
            $filterBtn.addClass('active');
            
            // Filtrer les cartes
            if (filter === 'all') {
                $container.find('.rbf-v3-beverage-card').show();
            } else {
                // Masquer toutes les cartes
                $container.find('.rbf-v3-beverage-card').hide();
                
                // Afficher les cartes correspondantes
                if ($container.find('[data-wine-type]').length > 0) {
                    // C'est l'onglet vins
                    $container.find(`[data-wine-type="${filter}"]`).show();
                } else if ($container.find('[data-beer-type]').length > 0) {
                    // C'est l'onglet bières
                    $container.find(`[data-beer-type="${filter}"]`).show();
                }
            }
            
            this.log('Filtre boissons appliqué:', filter);
        }
        
        /**
         * ✅ CORRECTION : Réinitialiser les filtres boissons
         */
        resetBeverageFilters() {
            this.container.find('.rbf-v3-filter-btn').removeClass('active');
            this.container.find('.rbf-v3-filter-btn[data-filter="all"]').addClass('active');
            this.container.find('.rbf-v3-beverage-card').show();
        }

        /**
         * ✅ NOUVEAU : Nettoyer les données obsolètes
         */
        cleanupObsoleteFormData() {
            const now = Date.now();
            const maxAge = 30 * 60 * 1000; // 30 minutes
            
            if (this.formData.beverages) {
                Object.keys(this.formData.beverages).forEach(key => {
                    const beverage = this.formData.beverages[key];
                    if (beverage.timestamp && (now - beverage.timestamp) > maxAge) {
                        delete this.formData.beverages[key];
                        this.log('Données boisson obsolètes supprimées:', key);
                    }
                });
            }
        }

        /**
         * Logger pour debug
         */
        log(message, data = null) {
            // Activer les logs seulement en mode debug OU temporairement pour les boutons
            const isButtonDebug = message.includes('🔵') || message.includes('🔴') || message.includes('🔧') || message.includes('❌') || message.includes('✅') || message.includes('🔄');
            if (window.rbfV3Debug || (window.console && window.location.search.includes('debug=1')) || isButtonDebug) {
                if (window.console && console.log) {
                    console.log(`[RBF V3] ${message}`, data);
                }
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
