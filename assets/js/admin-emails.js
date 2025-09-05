/**
 * JavaScript pour la page "Emails"
 * Block & Co - Restaurant Booking Plugin
 */

jQuery(document).ready(function($) {
    
    // Animation des cartes au chargement
    $('.status-card, .stat-item, .recommendation-item').each(function(index) {
        $(this).css({
            'opacity': '0',
            'transform': 'translateY(20px)'
        }).delay(index * 100).animate({
            'opacity': '1',
            'transform': 'translateY(0)'
        }, 600);
    });

    // Test d'email amélioré
    $('#test-email-btn').on('click', function(e) {
        e.preventDefault();
        
        const $btn = $(this);
        const $input = $('#test_email');
        const email = $input.val();
        
        // Validation simple de l'email
        if (!email || !isValidEmail(email)) {
            showNotification('Veuillez saisir une adresse email valide', 'error');
            $input.focus();
            return;
        }
        
        // État de chargement
        const originalText = $btn.text();
        $btn.prop('disabled', true)
           .html('<span class="spinner"></span> Envoi en cours...')
           .addClass('loading');
        
        // Simulation d'envoi (à remplacer par un vrai AJAX)
        setTimeout(function() {
            // Succès simulé (à remplacer par la vraie réponse AJAX)
            showNotification('Email de test envoyé avec succès à ' + email + ' !', 'success');
            
            // Réinitialiser le bouton
            $btn.prop('disabled', false)
               .text(originalText)
               .removeClass('loading');
               
            // Mettre à jour les statistiques (simulation)
            updateStats();
            
        }, 2000);
    });
    
    // Fonction de validation d'email
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    // Afficher une notification
    function showNotification(message, type) {
        // Supprimer les anciennes notifications
        $('.email-notification').remove();
        
        const notificationClass = type === 'success' ? 'notice-success' : 'notice-error';
        const icon = type === 'success' ? '✅' : '❌';
        
        const $notification = $('<div class="notice email-notification ' + notificationClass + '">')
            .html('<p>' + icon + ' ' + message + '</p>')
            .hide()
            .prependTo('.email-status-section')
            .slideDown(300);
        
        // Auto-masquer après 5 secondes
        setTimeout(function() {
            $notification.slideUp(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Mettre à jour les statistiques (simulation)
    function updateStats() {
        const $todayCount = $('.stat-item:first-child .stat-number');
        const currentCount = parseInt($todayCount.text()) || 0;
        
        // Animation du compteur
        $todayCount.prop('counter', currentCount).animate({
            counter: currentCount + 1
        }, {
            duration: 1000,
            easing: 'swing',
            step: function(now) {
                $todayCount.text(Math.ceil(now));
            }
        });
    }
    
    // Hover effect sur les recommandations
    $('.recommendation-item').hover(
        function() {
            $(this).find('.button-secondary').addClass('pulse');
        },
        function() {
            $(this).find('.button-secondary').removeClass('pulse');
        }
    );
    
    // Animation des statistiques au scroll
    function animateCounters() {
        $('.stat-number').each(function() {
            const $this = $(this);
            const countTo = parseInt($this.text()) || 0;
            
            if (countTo === 0) return;
            
            $this.prop('counter', 0).animate({
                counter: countTo
            }, {
                duration: 1500,
                easing: 'swing',
                step: function(now) {
                    $this.text(Math.ceil(now));
                }
            });
        });
    }
    
    // Détecter si les stats sont visibles
    function isInViewport($element) {
        const elementTop = $element.offset().top;
        const elementBottom = elementTop + $element.outerHeight();
        const viewportTop = $(window).scrollTop();
        const viewportBottom = viewportTop + $(window).height();
        
        return elementBottom > viewportTop && elementTop < viewportBottom;
    }
    
    // Animation au scroll
    $(window).on('scroll', function() {
        const $statsSection = $('.email-stats');
        
        if ($statsSection.length && isInViewport($statsSection) && !$statsSection.hasClass('animated')) {
            $statsSection.addClass('animated');
            animateCounters();
        }
    });
    
    // Copie des informations de configuration
    $('.status-info').on('click', function() {
        const configText = $(this).text();
        
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(configText).then(function() {
                showNotification('Informations copiées dans le presse-papiers', 'success');
            });
        }
    });
    
    // Validation en temps réel du champ email
    $('#test_email').on('input', function() {
        const $input = $(this);
        const email = $input.val();
        
        if (email && !isValidEmail(email)) {
            $input.addClass('invalid');
        } else {
            $input.removeClass('invalid');
        }
    });
    
    // Effet de pulse pour les boutons
    $('.button-primary, .button-secondary').on('mousedown', function() {
        $(this).addClass('pressed');
    }).on('mouseup mouseleave', function() {
        $(this).removeClass('pressed');
    });
    
});

// Styles CSS additionnels injectés via JavaScript
const additionalCSS = `
    .spinner {
        display: inline-block;
        width: 12px;
        height: 12px;
        border: 2px solid rgba(255,255,255,0.3);
        border-radius: 50%;
        border-top-color: white;
        animation: spin 1s ease-in-out infinite;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    .loading {
        pointer-events: none;
        opacity: 0.7;
    }
    
    .invalid {
        border-color: #d63638 !important;
        box-shadow: 0 0 0 2px rgba(214, 54, 56, 0.2) !important;
    }
    
    .pulse {
        animation: pulse 1s infinite;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    
    .pressed {
        transform: scale(0.95);
        transition: transform 0.1s ease;
    }
    
    .email-notification {
        animation: slideInDown 0.3s ease-out;
        margin-bottom: 20px;
    }
    
    @keyframes slideInDown {
        from {
            transform: translateY(-20px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
`;

// Injecter le CSS
const style = document.createElement('style');
style.textContent = additionalCSS;
document.head.appendChild(style);
