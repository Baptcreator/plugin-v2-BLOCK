/**
 * Script JavaScript pour l'administration des jeux
 *
 * @package RestaurantBooking
 * @since 2.0.0
 */

(function($) {
    'use strict';

    /**
     * Classe d'administration des jeux
     */
    class GamesAdmin {
        constructor() {
            this.init();
        }

        /**
         * Initialisation
         */
        init() {
            this.bindEvents();
            this.initSortable();
        }

        /**
         * Lier les événements
         */
        bindEvents() {
            // Suppression de jeu
            $(document).on('click', '.delete-game', (e) => {
                e.preventDefault();
                this.deleteGame($(e.currentTarget));
            });

            // Basculer le statut
            $(document).on('click', '.toggle-game-status', (e) => {
                e.preventDefault();
                this.toggleGameStatus($(e.currentTarget));
            });

            // Mise à jour de l'ordre
            $(document).on('change', '.game-order-input', (e) => {
                this.updateGameOrder($(e.currentTarget));
            });

            // Sélection/désélection de tous les éléments
            $('#cb-select-all-1').on('change', function() {
                $('input[name="game_ids[]"]').prop('checked', this.checked);
            });

            // Actions groupées
            $('#doaction').on('click', (e) => {
                const action = $('#bulk-action-selector-top').val();
                if (action === 'delete') {
                    if (!confirm(rb_games_admin.messages.confirm_delete)) {
                        e.preventDefault();
                        return false;
                    }
                }
            });
        }

        /**
         * Initialiser le tri par glisser-déposer
         */
        initSortable() {
            if (typeof $.fn.sortable !== 'undefined') {
                $('.wp-list-table tbody').sortable({
                    items: 'tr',
                    cursor: 'move',
                    axis: 'y',
                    handle: '.column-order',
                    update: (event, ui) => {
                        this.updateSortOrder();
                    }
                });
            }
        }

        /**
         * Supprimer un jeu
         */
        deleteGame($button) {
            if (!confirm(rb_games_admin.messages.confirm_delete)) {
                return;
            }

            const gameId = $button.data('game-id');
            const $row = $button.closest('tr');

            this.showLoading($row);

            $.ajax({
                url: rb_games_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'restaurant_booking_admin_action',
                    admin_action: 'delete_game',
                    nonce: rb_games_admin.nonce,
                    game_id: gameId
                },
                success: (response) => {
                    if (response.success) {
                        $row.fadeOut(300, function() {
                            $(this).remove();
                        });
                        this.showNotice(response.data.message || rb_games_admin.messages.success, 'success');
                    } else {
                        this.hideLoading($row);
                        this.showNotice(response.data || rb_games_admin.messages.error, 'error');
                    }
                },
                error: () => {
                    this.hideLoading($row);
                    this.showNotice(rb_games_admin.messages.error, 'error');
                }
            });
        }

        /**
         * Basculer le statut d'un jeu
         */
        toggleGameStatus($button) {
            const gameId = $button.data('game-id');
            const currentStatus = $button.data('current-status');
            const $row = $button.closest('tr');

            this.showLoading($row);

            $.ajax({
                url: rb_games_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'restaurant_booking_admin_action',
                    admin_action: 'toggle_game_status',
                    nonce: rb_games_admin.nonce,
                    game_id: gameId
                },
                success: (response) => {
                    this.hideLoading($row);
                    
                    if (response.success) {
                        const newStatus = response.data.new_status;
                        
                        // Mettre à jour le bouton
                        $button.data('current-status', newStatus);
                        $button.text(newStatus ? 'Désactiver' : 'Activer');
                        
                        // Mettre à jour la colonne statut
                        const $statusColumn = $row.find('.column-status');
                        if (newStatus) {
                            $statusColumn.html('<span class="status-active">Actif</span>');
                        } else {
                            $statusColumn.html('<span class="status-inactive">Inactif</span>');
                        }
                        
                        this.showNotice(response.data.message || rb_games_admin.messages.success, 'success');
                    } else {
                        this.showNotice(response.data || rb_games_admin.messages.error, 'error');
                    }
                },
                error: () => {
                    this.hideLoading($row);
                    this.showNotice(rb_games_admin.messages.error, 'error');
                }
            });
        }

        /**
         * Mettre à jour l'ordre d'un jeu
         */
        updateGameOrder($input) {
            const gameId = $input.data('game-id');
            const newOrder = parseInt($input.val());
            const $row = $input.closest('tr');

            // Validation
            if (isNaN(newOrder) || newOrder < 0) {
                $input.val($input.data('original-value') || 0);
                return;
            }

            // Sauvegarder la valeur originale
            if (!$input.data('original-value')) {
                $input.data('original-value', newOrder);
            }

            this.showLoading($row);

            $.ajax({
                url: rb_games_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'update_game_order',
                    nonce: rb_games_admin.nonce,
                    game_id: gameId,
                    display_order: newOrder
                },
                success: (response) => {
                    this.hideLoading($row);
                    
                    if (response.success) {
                        $input.data('original-value', newOrder);
                        this.showNotice('Ordre mis à jour', 'success', 2000);
                    } else {
                        $input.val($input.data('original-value'));
                        this.showNotice(response.data || rb_games_admin.messages.error, 'error');
                    }
                },
                error: () => {
                    this.hideLoading($row);
                    $input.val($input.data('original-value'));
                    this.showNotice(rb_games_admin.messages.error, 'error');
                }
            });
        }

        /**
         * Mettre à jour l'ordre de tri après glisser-déposer
         */
        updateSortOrder() {
            const orders = [];
            
            $('.wp-list-table tbody tr').each(function(index) {
                const gameId = $(this).find('input[name="game_ids[]"]').val();
                if (gameId) {
                    orders.push({
                        id: gameId,
                        order: index + 1
                    });
                }
            });

            $.ajax({
                url: rb_games_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'update_games_sort_order',
                    nonce: rb_games_admin.nonce,
                    orders: orders
                },
                success: (response) => {
                    if (response.success) {
                        // Mettre à jour les champs d'ordre
                        orders.forEach(item => {
                            $(`.game-order-input[data-game-id="${item.id}"]`).val(item.order);
                        });
                        
                        this.showNotice('Ordre mis à jour', 'success', 2000);
                    } else {
                        this.showNotice(response.data || rb_games_admin.messages.error, 'error');
                        // Recharger la page en cas d'erreur
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    }
                },
                error: () => {
                    this.showNotice(rb_games_admin.messages.error, 'error');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }
            });
        }

        /**
         * Afficher un indicateur de chargement
         */
        showLoading($element) {
            $element.addClass('loading').css('opacity', '0.6');
            
            if (!$element.find('.spinner').length) {
                $element.append('<span class="spinner is-active" style="float: none; margin: 0 5px;"></span>');
            }
        }

        /**
         * Masquer l'indicateur de chargement
         */
        hideLoading($element) {
            $element.removeClass('loading').css('opacity', '1');
            $element.find('.spinner').remove();
        }

        /**
         * Afficher une notification
         */
        showNotice(message, type = 'info', duration = 5000) {
            // Supprimer les anciennes notifications
            $('.rb-admin-notice').remove();
            
            const noticeClass = type === 'error' ? 'notice-error' : 'notice-success';
            const $notice = $(`
                <div class="notice ${noticeClass} is-dismissible rb-admin-notice" style="margin: 10px 0;">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Ignorer cette notice.</span>
                    </button>
                </div>
            `);
            
            $('.wrap h1').after($notice);
            
            // Auto-masquer après la durée spécifiée
            if (duration > 0) {
                setTimeout(() => {
                    $notice.fadeOut(300, function() {
                        $(this).remove();
                    });
                }, duration);
            }
            
            // Gérer le bouton de fermeture
            $notice.find('.notice-dismiss').on('click', function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            });
        }

        /**
         * Confirmer une action
         */
        confirmAction(message, callback) {
            if (confirm(message)) {
                callback();
            }
        }

        /**
         * Valider un formulaire
         */
        validateForm($form) {
            let isValid = true;
            const errors = [];

            // Vérifier les champs obligatoires
            $form.find('[required]').each(function() {
                const $field = $(this);
                const value = $field.val().trim();
                
                if (!value) {
                    isValid = false;
                    $field.addClass('error');
                    errors.push(`Veuillez compléter le champ "${$field.prev('label').text()}".`);
                } else {
                    $field.removeClass('error');
                }
            });

            // Validation du prix
            const $priceField = $form.find('[name="price"]');
            const price = parseFloat($priceField.val());
            
            if (isNaN(price) || price < 0) {
                isValid = false;
                $priceField.addClass('error');
                errors.push('Le prix doit être un nombre positif.');
            }

            // Afficher les erreurs
            if (!isValid) {
                this.showNotice(errors.join('<br>'), 'error');
            }

            return isValid;
        }
    }

    /**
     * Gestionnaire d'upload d'images
     */
    class ImageUploader {
        constructor() {
            this.initImageUpload();
        }

        initImageUpload() {
            let mediaUploader;
            
            $(document).on('click', '.upload-image-button', function(e) {
                e.preventDefault();
                
                const $button = $(this);
                const $container = $button.closest('.image-upload-container');
                
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                
                mediaUploader = wp.media({
                    title: 'Choisir une image pour le jeu',
                    button: {
                        text: 'Utiliser cette image'
                    },
                    multiple: false,
                    library: {
                        type: 'image'
                    }
                });
                
                mediaUploader.on('select', function() {
                    const attachment = mediaUploader.state().get('selection').first().toJSON();
                    
                    // Mettre à jour les champs
                    $container.find('#game_image_id').val(attachment.id);
                    
                    // Afficher l'aperçu
                    const $preview = $container.find('.image-preview');
                    $preview.html(`
                        <img src="${attachment.url}" alt="${attachment.alt}" 
                             style="max-width: 200px; height: auto; border: 1px solid #ddd; border-radius: 4px;">
                    `);
                    
                    // Afficher le bouton de suppression
                    $container.find('.remove-image-button').show();
                });
                
                mediaUploader.open();
            });
            
            // Supprimer l'image
            $(document).on('click', '.remove-image-button', function(e) {
                e.preventDefault();
                
                const $container = $(this).closest('.image-upload-container');
                
                $container.find('#game_image_id').val('');
                $container.find('.image-preview').empty();
                $container.find('.current-image').hide();
                $(this).hide();
            });
        }
    }

    /**
     * Initialisation au chargement de la page
     */
    $(document).ready(function() {
        // Initialiser seulement sur les pages d'administration des jeux
        if ($('body').hasClass('restaurant-booking_page_restaurant-booking-games')) {
            new GamesAdmin();
            new ImageUploader();
        }
    });

    /**
     * Styles CSS additionnels
     */
    const additionalStyles = `
        <style>
        .loading { position: relative; }
        .error { border-color: #dc3232 !important; }
        .status-active { color: #46b450; font-weight: 600; }
        .status-inactive { color: #dc3232; font-weight: 600; }
        .game-order-input { width: 60px; text-align: center; }
        .image-upload-container .current-image img { 
            max-width: 200px; 
            height: auto; 
            border: 1px solid #ddd; 
            border-radius: 4px; 
            margin-bottom: 10px; 
        }
        .wp-list-table .column-image { width: 70px; text-align: center; }
        .wp-list-table .column-price { width: 100px; text-align: right; }
        .wp-list-table .column-order { width: 80px; text-align: center; }
        .wp-list-table .column-status { width: 80px; text-align: center; }
        .wp-list-table .column-date { width: 120px; }
        .rb-admin-notice { margin: 10px 0 !important; }
        .sortable-placeholder { 
            background: #f0f0f0; 
            border: 2px dashed #ddd; 
            height: 40px; 
        }
        .ui-sortable-helper { 
            background: #fff; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.1); 
        }
        </style>
    `;
    
    $('head').append(additionalStyles);

})(jQuery);
