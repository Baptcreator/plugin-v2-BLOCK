<?php
/**
 * Migration pour corriger les échappements multiples d'apostrophes
 * 
 * @package RestaurantBooking
 * @since 3.0.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Fix_Escaped_Quotes_Migration
{
    /**
     * Exécuter la migration
     */
    public static function run()
    {
        $migration_key = 'restaurant_booking_fix_escaped_quotes_v1';
        
        // Vérifier si la migration a déjà été exécutée
        if (get_option($migration_key, false)) {
            return;
        }
        
        // Récupérer les options actuelles
        $options = get_option('restaurant_booking_unified_options', array());
        
        if (empty($options)) {
            // Marquer la migration comme terminée
            update_option($migration_key, true);
            return;
        }
        
        $updated = false;
        
        // Nettoyer chaque option
        foreach ($options as $key => $value) {
            if (is_string($value)) {
                $cleaned_value = self::clean_escaped_quotes($value);
                if ($cleaned_value !== $value) {
                    $options[$key] = $cleaned_value;
                    $updated = true;
                    
                    // Log pour debug
                    error_log("MIGRATION: Nettoyage de l'option '$key': '$value' => '$cleaned_value'");
                }
            }
        }
        
        // Sauvegarder les options nettoyées
        if ($updated) {
            update_option('restaurant_booking_unified_options', $options);
            error_log("MIGRATION: Options nettoyées et sauvegardées");
        }
        
        // Marquer la migration comme terminée
        update_option($migration_key, true);
        
        error_log("MIGRATION: Correction des échappements multiples terminée");
    }
    
    /**
     * Nettoyer les échappements multiples d'apostrophes
     */
    private static function clean_escaped_quotes($text)
    {
        // Remplacer les multiples échappements par une seule apostrophe
        $text = preg_replace('/\\\\+\'/', "'", $text);
        $text = preg_replace('/\\\\+\"/', '"', $text);
        
        return $text;
    }
    
    /**
     * Forcer la ré-exécution de la migration (pour debug)
     */
    public static function reset()
    {
        delete_option('restaurant_booking_fix_escaped_quotes_v1');
        error_log("MIGRATION: Reset de la migration des échappements");
    }
}
