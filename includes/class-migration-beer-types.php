<?php

/**
 * Migration pour ajouter des types de bières diversifiés
 */
class RestaurantBooking_Migration_Beer_Types {
    
    /**
     * Vérifier si la migration est nécessaire
     */
    public static function is_migration_needed() {
        return !get_option('restaurant_booking_migration_beer_types_completed', false);
    }
    
    /**
     * Exécuter la migration
     */
    public static function migrate() {
        global $wpdb;
        
        try {
            // Récupérer les bières actuelles
            $beers = $wpdb->get_results("
                SELECT p.id, p.name, p.beer_category 
                FROM {$wpdb->prefix}restaurant_products p
                INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
                WHERE c.type = 'biere_bouteille' AND p.is_active = 1
                ORDER BY p.id
            ");
            
            if (empty($beers)) {
                error_log('Migration Beer Types: Aucune bière trouvée');
                return false;
            }
            
            // Définir les nouveaux types de bières pour diversifier
            $beer_types = ['blonde', 'blanche', 'brune'];
            $type_index = 0;
            
            foreach ($beers as $beer) {
                // Assigner un type de bière différent à chaque produit
                $new_type = $beer_types[$type_index % count($beer_types)];
                
                // Mettre à jour le type de bière
                $result = $wpdb->update(
                    $wpdb->prefix . 'restaurant_products',
                    ['beer_category' => $new_type],
                    ['id' => $beer->id],
                    ['%s'],
                    ['%d']
                );
                
                if ($result !== false) {
                    error_log("Migration Beer Types: Bière '{$beer->name}' (ID: {$beer->id}) mise à jour avec le type '{$new_type}'");
                } else {
                    error_log("Migration Beer Types: Erreur lors de la mise à jour de la bière ID {$beer->id}");
                }
                
                $type_index++;
            }
            
            // Faire la même chose pour les fûts
            $kegs = $wpdb->get_results("
                SELECT p.id, p.name, p.beer_category 
                FROM {$wpdb->prefix}restaurant_products p
                INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
                WHERE c.type = 'fut' AND p.is_active = 1
                ORDER BY p.id
            ");
            
            $keg_type_index = 0;
            foreach ($kegs as $keg) {
                $new_type = $beer_types[$keg_type_index % count($beer_types)];
                
                $result = $wpdb->update(
                    $wpdb->prefix . 'restaurant_products',
                    ['beer_category' => $new_type],
                    ['id' => $keg->id],
                    ['%s'],
                    ['%d']
                );
                
                if ($result !== false) {
                    error_log("Migration Beer Types: Fût '{$keg->name}' (ID: {$keg->id}) mis à jour avec le type '{$new_type}'");
                }
                
                $keg_type_index++;
            }
            
            // Marquer la migration comme terminée
            update_option('restaurant_booking_migration_beer_types_completed', true);
            
            error_log('Migration Beer Types: Migration terminée avec succès');
            return true;
            
        } catch (Exception $e) {
            error_log('Migration Beer Types: Erreur - ' . $e->getMessage());
            return false;
        }
    }
}
