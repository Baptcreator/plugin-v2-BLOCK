/**
 * Scripts d'administration pour Restaurant Booking
 *
 * @package RestaurantBooking
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Variables globales
    var RestaurantBookingAdmin = {
        init: function() {
            this.bindEvents();
            this.initComponents();
        },

        bindEvents: function() {
            // Actions AJAX génériques
            $(document).on('click', '[data-rb-action]', this.handleAjaxAction);
            
            // Confirmation des suppressions
            $(document).on('click', '[data-rb-confirm]', this.handleConfirmAction);
            
            // Onglets
            $(document).on('click', '.restaurant-booking-tabs-nav a', this.handleTabClick);
            
            // Filtres
            $(document).on('change', '.restaurant-booking-filters select, .restaurant-booking-filters input', this.handleFilterChange);
            
            // Actions groupées
            $(document).on('change', '#cb-select-all', this.handleSelectAll);
            
            // Calendrier
            $(document).on('click', '.restaurant-booking-calendar-day', this.handleCalendarDayClick);
            
            // Formulaires
            $(document).on('submit', '.restaurant-booking-form', this.handleFormSubmit);
        },

        initComponents: function() {
            // Initialiser les composants au chargement
            this.initDatePickers();
            this.initTooltips();
            this.initSortables();
            this.initColorPickers();
        },

        // === GESTION AJAX ===
        handleAjaxAction: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var action = $button.data('rb-action');
            var data = $button.data('rb-data') || {};
            
            // Confirmation si nécessaire
            if ($button.data('rb-confirm')) {
                if (!confirm($button.data('rb-confirm'))) {
                    return;
                }
            }
            
            // Désactiver le bouton pendant la requête
            $button.prop('disabled', true);
            var originalText = $button.text();
            $button.text(restaurant_booking_admin.messages.loading || 'Chargement...');
            
            // Préparer les données
            var ajaxData = {
                action: 'restaurant_booking_admin_action',
                admin_action: action,
                nonce: restaurant_booking_admin.nonce
            };
            
            // Ajouter les données supplémentaires
            $.extend(ajaxData, data);
            
            // Requête AJAX
            $.ajax({
                url: restaurant_booking_admin.ajax_url,
                type: 'POST',
                data: ajaxData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        RestaurantBookingAdmin.showMessage(response.data, 'success');
                        
                        // Actions spécifiques selon le type
                        RestaurantBookingAdmin.handleAjaxSuccess(action, response.data, $button);
                    } else {
                        RestaurantBookingAdmin.showMessage(response.data, 'error');
                    }
                },
                error: function() {
                    RestaurantBookingAdmin.showMessage('Erreur de communication avec le serveur', 'error');
                },
                complete: function() {
                    // Réactiver le bouton
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },

        handleAjaxSuccess: function(action, data, $button) {
            switch (action) {
                case 'delete_quote':
                    // Supprimer la ligne du tableau
                    $button.closest('tr').fadeOut(300, function() {
                        $(this).remove();
                    });
                    break;
                    
                case 'update_quote_status':
                    // Mettre à jour le badge de statut
                    var $statusBadge = $button.closest('tr').find('.status-badge');
                    $statusBadge.removeClass().addClass('status-badge status-' + data.new_status);
                    break;
                    
                case 'clear_logs':
                    // Recharger la page des logs
                    if (window.location.href.includes('diagnostics')) {
                        window.location.reload();
                    }
                    break;
            }
        },

        // === CONFIRMATIONS ===
        handleConfirmAction: function(e) {
            var message = $(this).data('rb-confirm');
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        },

        // === ONGLETS ===
        handleTabClick: function(e) {
            e.preventDefault();
            
            var $tab = $(this);
            var targetTab = $tab.attr('href').substring(1);
            
            // Désactiver tous les onglets
            $tab.closest('.restaurant-booking-tabs-nav').find('a').removeClass('active');
            $('.restaurant-booking-tab-content').removeClass('active');
            
            // Activer l'onglet sélectionné
            $tab.addClass('active');
            $('#' + targetTab).addClass('active');
            
            // Sauvegarder l'onglet actif
            if (window.history && window.history.replaceState) {
                var url = new URL(window.location);
                url.searchParams.set('tab', targetTab);
                window.history.replaceState({}, '', url);
            }
        },

        // === FILTRES ===
        handleFilterChange: function() {
            var $form = $(this).closest('form');
            if ($form.length) {
                $form.submit();
            }
        },

        // === SÉLECTION GROUPÉE ===
        handleSelectAll: function() {
            var isChecked = $(this).prop('checked');
            $('input[name="items[]"]').prop('checked', isChecked);
        },

        // === CALENDRIER ===
        handleCalendarDayClick: function(e) {
            var $day = $(this);
            var date = $day.data('date');
            var serviceType = $('.restaurant-booking-calendar').data('service-type');
            
            // Toggle de disponibilité
            if ($day.hasClass('available')) {
                $day.removeClass('available').addClass('unavailable');
                RestaurantBookingAdmin.updateAvailability(date, serviceType, false);
            } else {
                $day.removeClass('unavailable').addClass('available');
                RestaurantBookingAdmin.updateAvailability(date, serviceType, true);
            }
        },

        updateAvailability: function(date, serviceType, isAvailable) {
            $.ajax({
                url: restaurant_booking_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'restaurant_booking_admin_action',
                    admin_action: 'update_availability',
                    date: date,
                    service_type: serviceType,
                    is_available: isAvailable ? 1 : 0,
                    nonce: restaurant_booking_admin.nonce
                },
                success: function(response) {
                    if (!response.success) {
                        RestaurantBookingAdmin.showMessage('Erreur lors de la mise à jour', 'error');
                    }
                }
            });
        },

        // === FORMULAIRES ===
        handleFormSubmit: function(e) {
            var $form = $(this);
            
            // Validation côté client si nécessaire
            if (!RestaurantBookingAdmin.validateForm($form)) {
                e.preventDefault();
                return false;
            }
            
            // Désactiver le bouton de soumission
            $form.find('[type="submit"]').prop('disabled', true);
        },

        validateForm: function($form) {
            var isValid = true;
            var errors = [];
            
            // Validation des champs obligatoires
            $form.find('[required]').each(function() {
                var $field = $(this);
                if (!$field.val().trim()) {
                    errors.push('Le champ "' + $field.attr('name') + '" est obligatoire');
                    $field.addClass('error');
                    isValid = false;
                } else {
                    $field.removeClass('error');
                }
            });
            
            // Validation des emails
            $form.find('[type="email"]').each(function() {
                var $field = $(this);
                var email = $field.val().trim();
                if (email && !RestaurantBookingAdmin.isValidEmail(email)) {
                    errors.push('L\'email "' + email + '" n\'est pas valide');
                    $field.addClass('error');
                    isValid = false;
                }
            });
            
            // Afficher les erreurs
            if (!isValid) {
                RestaurantBookingAdmin.showMessage(errors.join('<br>'), 'error');
            }
            
            return isValid;
        },

        // === UTILITAIRES ===
        showMessage: function(message, type) {
            type = type || 'info';
            
            // Supprimer les anciens messages
            $('.restaurant-booking-message').remove();
            
            // Créer le nouveau message
            var $message = $('<div class="restaurant-booking-message ' + type + '">' + message + '</div>');
            
            // Insérer au début du contenu
            $('.wrap').prepend($message);
            
            // Animation d'apparition
            $message.addClass('restaurant-booking-fade-in');
            
            // Auto-suppression après 5 secondes pour les succès
            if (type === 'success') {
                setTimeout(function() {
                    $message.fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 5000);
            }
        },

        isValidEmail: function(email) {
            var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        },

        // === COMPOSANTS ===
        initDatePickers: function() {
            // Initialiser les sélecteurs de date
            if ($.fn.datepicker) {
                $('.restaurant-booking-datepicker').datepicker({
                    dateFormat: 'dd/mm/yy',
                    showButtonPanel: true,
                    changeMonth: true,
                    changeYear: true
                });
            }
        },

        initTooltips: function() {
            // Initialiser les tooltips
            if ($.fn.tooltip) {
                $('[data-tooltip]').tooltip({
                    content: function() {
                        return $(this).data('tooltip');
                    }
                });
            }
        },

        initSortables: function() {
            // Initialiser les listes triables
            if ($.fn.sortable) {
                $('.restaurant-booking-sortable').sortable({
                    handle: '.sort-handle',
                    placeholder: 'sort-placeholder',
                    update: function(event, ui) {
                        RestaurantBookingAdmin.handleSortUpdate(this);
                    }
                });
            }
        },

        initColorPickers: function() {
            // Initialiser les sélecteurs de couleur
            if ($.fn.wpColorPicker) {
                $('.restaurant-booking-color-picker').wpColorPicker();
            }
        },

        handleSortUpdate: function(sortable) {
            var $sortable = $(sortable);
            var items = [];
            
            $sortable.find('[data-id]').each(function(index) {
                items.push({
                    id: $(this).data('id'),
                    order: index + 1
                });
            });
            
            // Envoyer la nouvelle ordre au serveur
            $.ajax({
                url: restaurant_booking_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'restaurant_booking_admin_action',
                    admin_action: 'update_order',
                    items: items,
                    nonce: restaurant_booking_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        RestaurantBookingAdmin.showMessage('Ordre mis à jour', 'success');
                    }
                }
            });
        },

        // === EXPORT/IMPORT ===
        exportData: function(type, filters) {
            var url = restaurant_booking_admin.ajax_url + '?action=restaurant_booking_export';
            url += '&type=' + type;
            url += '&nonce=' + restaurant_booking_admin.nonce;
            
            if (filters) {
                url += '&' + $.param(filters);
            }
            
            // Ouvrir dans une nouvelle fenêtre
            window.open(url, '_blank');
        },

        // === DIAGNOSTICS ===
        runDiagnostic: function(test) {
            var $button = $('[data-rb-action="run_diagnostic"][data-rb-data-test="' + test + '"]');
            
            if ($button.length) {
                $button.trigger('click');
            }
        }
    };

    // === COMPOSANTS SPÉCIALISÉS ===
    
    // Gestionnaire de produits
    var ProductsManager = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            $(document).on('change', '[name="has_supplement"]', this.toggleSupplementFields);
            $(document).on('change', '[name="category_id"]', this.updateUnitOptions);
        },

        toggleSupplementFields: function() {
            var $checkbox = $(this);
            var $supplementFields = $('.supplement-fields');
            
            if ($checkbox.is(':checked')) {
                $supplementFields.show();
            } else {
                $supplementFields.hide();
            }
        },

        updateUnitOptions: function() {
            var categoryId = $(this).val();
            // Ici on pourrait charger les options d'unité selon la catégorie
        }
    };

    // Gestionnaire de devis
    var QuotesManager = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            $(document).on('click', '.send-quote', this.sendQuote);
            $(document).on('change', '.quote-status-select', this.updateStatus);
        },

        sendQuote: function(e) {
            e.preventDefault();
            
            var quoteId = $(this).data('quote-id');
            var $button = $(this);
            
            $button.prop('disabled', true).text('Envoi...');
            
            $.ajax({
                url: restaurant_booking_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'restaurant_booking_admin_action',
                    admin_action: 'send_quote_email',
                    quote_id: quoteId,
                    nonce: restaurant_booking_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        RestaurantBookingAdmin.showMessage('Devis envoyé par email', 'success');
                        $button.text('Envoyé').addClass('button-disabled');
                    } else {
                        RestaurantBookingAdmin.showMessage(response.data, 'error');
                        $button.prop('disabled', false).text('Envoyer');
                    }
                }
            });
        },

        updateStatus: function() {
            var $select = $(this);
            var quoteId = $select.data('quote-id');
            var newStatus = $select.val();
            
            $.ajax({
                url: restaurant_booking_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'restaurant_booking_admin_action',
                    admin_action: 'update_quote_status',
                    quote_id: quoteId,
                    status: newStatus,
                    nonce: restaurant_booking_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        RestaurantBookingAdmin.showMessage('Statut mis à jour', 'success');
                    }
                }
            });
        }
    };

    // === INITIALISATION ===
    $(document).ready(function() {
        RestaurantBookingAdmin.init();
        ProductsManager.init();
        QuotesManager.init();
        
        // Restaurer l'onglet actif depuis l'URL
        var urlParams = new URLSearchParams(window.location.search);
        var activeTab = urlParams.get('tab');
        if (activeTab) {
            $('a[href="#' + activeTab + '"]').trigger('click');
        }
    });

    // Exposer l'objet global
    window.RestaurantBookingAdmin = RestaurantBookingAdmin;

})(jQuery);
