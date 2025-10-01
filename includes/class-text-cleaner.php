<?php
/**
 * Classe utilitaire pour nettoyer automatiquement les échappements lors de l'affichage
 * 
 * @package RestaurantBooking
 * @since 3.0.2
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Text_Cleaner
{
    /**
     * Nettoyer les échappements multiples d'apostrophes
     */
    public static function clean_escaped_quotes($text)
    {
        if (!is_string($text)) {
            return $text;
        }
        
        // Remplacer les multiples échappements par une seule apostrophe
        $text = preg_replace('/\\\\+\'/', "'", $text);
        $text = preg_replace('/\\\\+\"/', '"', $text);
        
        return $text;
    }
    
    /**
     * Nettoyer un tableau de données
     */
    public static function clean_array($data)
    {
        if (!is_array($data)) {
            return $data;
        }
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = self::clean_escaped_quotes($value);
            } elseif (is_array($value)) {
                $data[$key] = self::clean_array($value);
            }
        }
        
        return $data;
    }
    
    /**
     * Nettoyer un objet
     */
    public static function clean_object($object)
    {
        if (!is_object($object)) {
            return $object;
        }
        
        foreach ($object as $property => $value) {
            if (is_string($value)) {
                $object->$property = self::clean_escaped_quotes($value);
            } elseif (is_array($value)) {
                $object->$property = self::clean_array($value);
            } elseif (is_object($value)) {
                $object->$property = self::clean_object($value);
            }
        }
        
        return $object;
    }
    
    /**
     * Nettoyer les résultats d'une requête WordPress
     */
    public static function clean_wpdb_results($results)
    {
        if (empty($results)) {
            return $results;
        }
        
        if (is_array($results)) {
            foreach ($results as $key => $result) {
                if (is_object($result)) {
                    $results[$key] = self::clean_object($result);
                } elseif (is_array($result)) {
                    $results[$key] = self::clean_array($result);
                }
            }
        } elseif (is_object($results)) {
            $results = self::clean_object($results);
        }
        
        return $results;
    }
}
