/**
 * Script pour forcer la correction des widgets qui débordent
 * Block & Co - Restaurant Booking Plugin
 */

jQuery(document).ready(function($) {
    
    // Force l'application des styles après le chargement
    function fixWidgetOverflow() {
        $('.widget-options').each(function() {
            $(this).css({
                'display': 'grid',
                'grid-template-columns': '1fr',
                'gap': '15px',
                'width': '100%',
                'max-width': '100%',
                'box-sizing': 'border-box',
                'padding': '0'
            });
        });
        
        $('.widget-option').each(function() {
            $(this).css({
                'width': '100%',
                'max-width': '100%',
                'box-sizing': 'border-box',
                'overflow': 'hidden',
                'padding': '15px',
                'gap': '12px',
                'margin': '0'
            });
        });
        
        $('.widget-info strong').each(function() {
            $(this).css({
                'white-space': 'nowrap',
                'overflow': 'hidden',
                'text-overflow': 'ellipsis',
                'font-size': '14px',
                'line-height': '1.2'
            });
        });
        
        $('.widget-info p').each(function() {
            $(this).css({
                'font-size': '12px',
                'line-height': '1.3',
                'word-wrap': 'break-word',
                'overflow': 'hidden',
                'display': '-webkit-box',
                '-webkit-line-clamp': '2',
                '-webkit-box-orient': 'vertical'
            });
        });
        
        $('.step, .step-content').each(function() {
            $(this).css({
                'overflow': 'hidden',
                'box-sizing': 'border-box',
                'width': '100%'
            });
        });
    }
    
    // Applique les corrections immédiatement
    fixWidgetOverflow();
    
    // Réapplique après 500ms pour s'assurer que tous les CSS sont chargés
    setTimeout(fixWidgetOverflow, 500);
    
    // Réapplique après 1s pour être sûr
    setTimeout(fixWidgetOverflow, 1000);
    
    // Applique aussi lors du redimensionnement de la fenêtre
    $(window).on('resize', function() {
        setTimeout(fixWidgetOverflow, 100);
    });
    
    // Debug : affiche les dimensions dans la console
    if (window.console && console.log) {
        setTimeout(function() {
            $('.widget-option').each(function(index) {
                var width = $(this).outerWidth();
                var parentWidth = $(this).parent().width();
                console.log('Widget ' + (index + 1) + ' - Largeur: ' + width + 'px, Parent: ' + parentWidth + 'px');
                
                if (width > parentWidth) {
                    console.warn('⚠️ Widget ' + (index + 1) + ' dépasse encore ! Largeur: ' + width + 'px > Parent: ' + parentWidth + 'px');
                    // Force la correction
                    $(this).css('width', '100%');
                }
            });
        }, 1500);
    }
});
