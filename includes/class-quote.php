<?php
/**
 * Classe de gestion des devis
 *
 * @package RestaurantBooking
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Quote
{
    /**
     * Instance unique
     */
    private static $instance = null;

    /**
     * Obtenir l'instance unique
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructeur
     */
    private function __construct()
    {
        // Hooks pour la génération automatique des numéros de devis
        add_action('init', array($this, 'init'));
    }

    /**
     * Initialisation
     */
    public function init()
    {
        // Rien pour le moment
    }

    /**
     * Créer un nouveau devis
     */
    public static function create($data)
    {
        global $wpdb;

        // Validation des données obligatoires
        $required_fields = array('service_type', 'event_date', 'guest_count');
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $field_messages = array(
                    'service_type' => __('Veuillez sélectionner un service', 'restaurant-booking'),
                    'event_date' => __('Veuillez compléter la date de l\'événement', 'restaurant-booking'),
                    'guest_count' => __('Veuillez indiquer le nombre de convives', 'restaurant-booking')
                );
                $message = isset($field_messages[$field]) ? $field_messages[$field] : sprintf(__('Veuillez compléter le champ %s', 'restaurant-booking'), $field);
                return new WP_Error('missing_field', $message);
            }
        }

        // Générer un numéro de devis unique
        $quote_number = self::generate_quote_number();

        // Calculer le prix total
        $price_calculation = self::calculate_total_price($data);
        if (is_wp_error($price_calculation)) {
            return $price_calculation;
        }

        // Préparer les données pour l'insertion
        $quote_data = array(
            'quote_number' => $quote_number,
            'service_type' => sanitize_text_field($data['service_type']),
            'event_date' => sanitize_text_field($data['event_date']),
            'event_duration' => isset($data['event_duration']) ? (int) $data['event_duration'] : 2,
            'guest_count' => (int) $data['guest_count'],
            'postal_code' => isset($data['postal_code']) ? sanitize_text_field($data['postal_code']) : null,
            'distance_km' => isset($data['distance_km']) ? (int) $data['distance_km'] : null,
            'customer_data' => json_encode(self::sanitize_customer_data($data['customer_data'] ?? array()), JSON_UNESCAPED_UNICODE),
            'selected_products' => json_encode($data['selected_products'] ?? array(), JSON_UNESCAPED_UNICODE),
            'price_breakdown' => json_encode($price_calculation['breakdown'], JSON_UNESCAPED_UNICODE),
            'base_price' => $price_calculation['base_price'],
            'supplements_total' => $price_calculation['supplements_total'],
            'products_total' => $price_calculation['products_total'],
            'total_price' => $price_calculation['total_price'],
            'status' => 'sent',
            'created_at' => current_time('mysql')
        );

        // Insérer en base de données
        $result = $wpdb->insert(
            $wpdb->prefix . 'restaurant_quotes',
            $quote_data,
            array('%s', '%s', '%s', '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%s', '%s')
        );

        if ($result === false) {
            RestaurantBooking_Logger::error('Erreur lors de la création du devis', array(
                'data' => $data,
                'error' => $wpdb->last_error
            ));
            return new WP_Error('db_error', __('Erreur lors de la création du devis', 'restaurant-booking'));
        }

        $quote_id = $wpdb->insert_id;

        // Log de la création
        RestaurantBooking_Logger::info("Nouveau devis créé: $quote_number", array(
            'quote_id' => $quote_id,
            'service_type' => $data['service_type'],
            'total_price' => $price_calculation['total_price']
        ));

        // Envoyer la notification admin automatiquement
        if (class_exists('RestaurantBooking_Email')) {
            RestaurantBooking_Email::send_admin_notification($quote_id);
        }

        return $quote_id;
    }

    /**
     * Obtenir un devis par ID
     */
    public static function get($quote_id)
    {
        global $wpdb;

        $quote = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}restaurant_quotes WHERE id = %d",
            $quote_id
        ), ARRAY_A);

        if (!$quote) {
            return null;
        }

        // Décoder les données JSON
        $quote['customer_data'] = json_decode($quote['customer_data'], true) ?: array();
        $quote['selected_products'] = json_decode($quote['selected_products'], true) ?: array();
        $quote['price_breakdown'] = json_decode($quote['price_breakdown'], true) ?: array();

        return $quote;
    }

    /**
     * Obtenir un devis par numéro
     */
    public static function get_by_number($quote_number)
    {
        global $wpdb;

        $quote = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}restaurant_quotes WHERE quote_number = %s",
            $quote_number
        ), ARRAY_A);

        if (!$quote) {
            return null;
        }

        // Décoder les données JSON
        $quote['customer_data'] = json_decode($quote['customer_data'], true) ?: array();
        $quote['selected_products'] = json_decode($quote['selected_products'], true) ?: array();
        $quote['price_breakdown'] = json_decode($quote['price_breakdown'], true) ?: array();

        return $quote;
    }

    /**
     * Mettre à jour un devis
     */
    public static function update($quote_id, $data)
    {
        global $wpdb;

        // Vérifier que le devis existe
        $existing_quote = self::get($quote_id);
        if (!$existing_quote) {
            return new WP_Error('quote_not_found', __('Devis introuvable', 'restaurant-booking'));
        }

        // Préparer les données à mettre à jour
        $update_data = array();
        $format = array();

        if (isset($data['status'])) {
            $update_data['status'] = sanitize_text_field($data['status']);
            $format[] = '%s';
        }

        if (isset($data['admin_notes'])) {
            $update_data['admin_notes'] = sanitize_textarea_field($data['admin_notes']);
            $format[] = '%s';
        }

        if (isset($data['sent_at'])) {
            $update_data['sent_at'] = $data['sent_at'];
            $format[] = '%s';
        }

        // Recalculer les prix si les données ont changé
        if (isset($data['selected_products']) || isset($data['event_duration']) || isset($data['guest_count'])) {
            $calculation_data = array_merge($existing_quote, $data);
            $price_calculation = self::calculate_total_price($calculation_data);
            
            if (!is_wp_error($price_calculation)) {
                $update_data['selected_products'] = json_encode($data['selected_products'] ?? $existing_quote['selected_products'], JSON_UNESCAPED_UNICODE);
                $update_data['price_breakdown'] = json_encode($price_calculation['breakdown'], JSON_UNESCAPED_UNICODE);
                $update_data['base_price'] = $price_calculation['base_price'];
                $update_data['supplements_total'] = $price_calculation['supplements_total'];
                $update_data['products_total'] = $price_calculation['products_total'];
                $update_data['total_price'] = $price_calculation['total_price'];
                
                $format = array_merge($format, array('%s', '%s', '%f', '%f', '%f', '%f'));
            }
        }

        $update_data['updated_at'] = current_time('mysql');
        $format[] = '%s';

        // Effectuer la mise à jour
        $result = $wpdb->update(
            $wpdb->prefix . 'restaurant_quotes',
            $update_data,
            array('id' => $quote_id),
            $format,
            array('%d')
        );

        if ($result === false) {
            RestaurantBooking_Logger::error('Erreur lors de la mise à jour du devis', array(
                'quote_id' => $quote_id,
                'data' => $data,
                'error' => $wpdb->last_error
            ));
            return new WP_Error('db_error', __('Erreur lors de la mise à jour', 'restaurant-booking'));
        }

        // Log de la mise à jour
        RestaurantBooking_Logger::info("Devis mis à jour: {$existing_quote['quote_number']}", array(
            'quote_id' => $quote_id,
            'updated_fields' => array_keys($update_data)
        ));

        return true;
    }

    /**
     * Supprimer un devis
     */
    public static function delete($quote_id)
    {
        global $wpdb;

        $quote = self::get($quote_id);
        if (!$quote) {
            return new WP_Error('quote_not_found', __('Devis introuvable', 'restaurant-booking'));
        }

        $result = $wpdb->delete(
            $wpdb->prefix . 'restaurant_quotes',
            array('id' => $quote_id),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Erreur lors de la suppression', 'restaurant-booking'));
        }

        RestaurantBooking_Logger::info("Devis supprimé: {$quote['quote_number']}", array(
            'quote_id' => $quote_id
        ));

        return true;
    }

    /**
     * Lister les devis avec filtres
     */
    public static function get_list($args = array())
    {
        global $wpdb;

        $defaults = array(
            'status' => '',
            'service_type' => '',
            'date_from' => '',
            'date_to' => '',
            'search' => '',
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => 20,
            'offset' => 0
        );

        $args = wp_parse_args($args, $defaults);

        // Construire la requête
        $where_conditions = array();
        $params = array();

        if (!empty($args['status'])) {
            $where_conditions[] = 'status = %s';
            $params[] = $args['status'];
        }

        if (!empty($args['service_type'])) {
            $where_conditions[] = 'service_type = %s';
            $params[] = $args['service_type'];
        }

        if (!empty($args['date_from'])) {
            $where_conditions[] = 'event_date >= %s';
            $params[] = $args['date_from'];
        }

        if (!empty($args['date_to'])) {
            $where_conditions[] = 'event_date <= %s';
            $params[] = $args['date_to'];
        }

        if (!empty($args['search'])) {
            $where_conditions[] = '(quote_number LIKE %s OR JSON_EXTRACT(customer_data, "$.name") LIKE %s OR JSON_EXTRACT(customer_data, "$.email") LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }

        // Requête de comptage
        $count_sql = "SELECT COUNT(*) FROM {$wpdb->prefix}restaurant_quotes $where_clause";
        if (!empty($params)) {
            $count_sql = $wpdb->prepare($count_sql, $params);
        }
        $total = $wpdb->get_var($count_sql);

        // Requête principale
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        $sql = "SELECT * FROM {$wpdb->prefix}restaurant_quotes 
                $where_clause 
                ORDER BY $orderby 
                LIMIT %d OFFSET %d";

        $params[] = $args['limit'];
        $params[] = $args['offset'];

        $quotes = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);

        // Décoder les données JSON pour chaque devis
        foreach ($quotes as &$quote) {
            $quote['customer_data'] = json_decode($quote['customer_data'], true) ?: array();
            $quote['selected_products'] = json_decode($quote['selected_products'], true) ?: array();
            $quote['price_breakdown'] = json_decode($quote['price_breakdown'], true) ?: array();
        }

        return array(
            'quotes' => $quotes,
            'total' => (int) $total,
            'pages' => ceil($total / $args['limit'])
        );
    }

    /**
     * Calculer le prix total d'un devis
     */
    public static function calculate_total_price($data)
    {
        $service_type = $data['service_type'];
        $guest_count = (int) $data['guest_count'];
        $duration = isset($data['event_duration']) ? (int) $data['event_duration'] : 2;
        $selected_products = $data['selected_products'] ?? array();

        // Obtenir les paramètres de tarification
        $pricing = RestaurantBooking_Settings::get_pricing_settings();

        // Prix de base
        $base_price = $service_type === 'restaurant' 
            ? $pricing['restaurant_base_price'] 
            : $pricing['remorque_base_price'];

        // Heures incluses
        $included_hours = $service_type === 'restaurant' 
            ? $pricing['restaurant_included_hours'] 
            : $pricing['remorque_included_hours'];

        // Supplément durée
        $duration_supplement = 0;
        if ($duration > $included_hours) {
            $extra_hours = $duration - $included_hours;
            $duration_supplement = $extra_hours * $pricing['hourly_supplement'];
        }

        // Supplément convives (remorque uniquement)
        $guests_supplement = 0;
        if ($service_type === 'remorque' && $guest_count > 50) {
            $guests_supplement = $pricing['remorque_50_guests_supplement'];
        }

        // Frais de livraison (remorque uniquement)
        $delivery_cost = 0;
        if ($service_type === 'remorque' && isset($data['postal_code'])) {
            $distance = isset($data['distance_km']) ? $data['distance_km'] : self::calculate_distance($data['postal_code']);
            $delivery_cost = self::get_delivery_cost($distance);
        }

        // Total des produits
        $products_total = 0;
        $products_detail = array();

        if (!empty($selected_products)) {
            foreach ($selected_products as $product_id => $quantity) {
                $product = RestaurantBooking_Product::get($product_id);
                if ($product && $quantity > 0) {
                    $product_total = $product['price'] * $quantity;
                    $products_total += $product_total;
                    
                    $products_detail[] = array(
                        'id' => $product_id,
                        'name' => $product['name'],
                        'price' => $product['price'],
                        'quantity' => $quantity,
                        'total' => $product_total
                    );

                    // Ajouter les suppléments si sélectionnés
                    if ($product['has_supplement'] && isset($data['product_supplements'][$product_id]) && $data['product_supplements'][$product_id]) {
                        $supplement_total = $product['supplement_price'] * $quantity;
                        $products_total += $supplement_total;
                        
                        $products_detail[] = array(
                            'id' => $product_id . '_supplement',
                            'name' => $product['name'] . ' - ' . $product['supplement_name'],
                            'price' => $product['supplement_price'],
                            'quantity' => $quantity,
                            'total' => $supplement_total
                        );
                    }
                }
            }
        }

        // Total général
        $supplements_total = $duration_supplement + $guests_supplement + $delivery_cost;
        $total_price = $base_price + $supplements_total + $products_total;

        // Détail de la tarification
        $breakdown = array(
            'base_price' => array(
                'label' => $service_type === 'restaurant' ? 'Forfait restaurant' : 'Forfait remorque',
                'amount' => $base_price,
                'details' => sprintf('%d heures incluses', $included_hours)
            ),
            'duration_supplement' => array(
                'label' => 'Supplément durée',
                'amount' => $duration_supplement,
                'details' => $duration > $included_hours ? sprintf('%d heures supplémentaires', $duration - $included_hours) : ''
            ),
            'guests_supplement' => array(
                'label' => 'Supplément convives',
                'amount' => $guests_supplement,
                'details' => $guests_supplement > 0 ? sprintf('+%d convives', $guest_count - 50) : ''
            ),
            'delivery_cost' => array(
                'label' => 'Frais de livraison',
                'amount' => $delivery_cost,
                'details' => $delivery_cost > 0 ? sprintf('%d km', $distance ?? 0) : ''
            ),
            'products' => array(
                'label' => 'Produits sélectionnés',
                'amount' => $products_total,
                'details' => $products_detail
            ),
            'total' => array(
                'label' => 'Total TTC',
                'amount' => $total_price
            )
        );

        return array(
            'base_price' => $base_price,
            'supplements_total' => $supplements_total,
            'products_total' => $products_total,
            'total_price' => $total_price,
            'breakdown' => $breakdown
        );
    }

    /**
     * Générer un numéro de devis unique
     */
    private static function generate_quote_number()
    {
        $year = date('Y');
        $prefix = $year . '-';
        
        global $wpdb;
        
        // Obtenir le dernier numéro pour cette année
        $last_number = $wpdb->get_var($wpdb->prepare(
            "SELECT quote_number FROM {$wpdb->prefix}restaurant_quotes 
             WHERE quote_number LIKE %s 
             ORDER BY quote_number DESC 
             LIMIT 1",
            $prefix . '%'
        ));

        if ($last_number) {
            $last_increment = (int) str_replace($prefix, '', $last_number);
            $new_increment = $last_increment + 1;
        } else {
            $new_increment = 1;
        }

        return $prefix . str_pad($new_increment, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Calculer la distance entre deux codes postaux
     */
    private static function calculate_distance($destination_postal_code)
    {
        $origin_postal_code = RestaurantBooking_Settings::get('restaurant_postal_code', '67000');
        
        // Pour l'instant, simulation simple basée sur les codes postaux
        // Dans une version complète, utiliser l'API Google Distance Matrix
        $origin_dept = substr($origin_postal_code, 0, 2);
        $dest_dept = substr($destination_postal_code, 0, 2);
        
        if ($origin_dept === $dest_dept) {
            return rand(10, 30); // Même département
        } else {
            return rand(50, 150); // Départements différents
        }
    }

    /**
     * Obtenir le coût de livraison selon la distance
     */
    private static function get_delivery_cost($distance)
    {
        global $wpdb;

        $zone = $wpdb->get_row($wpdb->prepare(
            "SELECT delivery_price FROM {$wpdb->prefix}restaurant_delivery_zones 
             WHERE distance_min <= %d AND distance_max >= %d AND is_active = 1
             ORDER BY distance_min ASC
             LIMIT 1",
            $distance,
            $distance
        ));

        return $zone ? (float) $zone->delivery_price : 0;
    }

    /**
     * Nettoyer les données client
     */
    private static function sanitize_customer_data($data)
    {
        $sanitized = array();
        
        $fields = array('name', 'email', 'phone', 'company', 'address', 'city', 'postal_code', 'message');
        
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $sanitized[$field] = sanitize_text_field($data[$field]);
            }
        }

        return $sanitized;
    }

    /**
     * Marquer un devis comme envoyé
     */
    public static function mark_as_sent($quote_id)
    {
        return self::update($quote_id, array(
            'status' => 'sent',
            'sent_at' => current_time('mysql')
        ));
    }

    /**
     * Obtenir les statistiques des devis
     */
    public static function get_statistics($period = 30)
    {
        global $wpdb;

        $stats = array();

        // Nombre total de devis par statut
        $status_counts = $wpdb->get_results($wpdb->prepare("
            SELECT status, COUNT(*) as count
            FROM {$wpdb->prefix}restaurant_quotes 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY status
        ", $period), ARRAY_A);

        $stats['by_status'] = array();
        foreach ($status_counts as $status) {
            $stats['by_status'][$status['status']] = (int) $status['count'];
        }

        // Chiffre d'affaires prévisionnel
        $revenue = $wpdb->get_results($wpdb->prepare("
            SELECT 
                service_type,
                SUM(total_price) as total_revenue,
                COUNT(*) as count
            FROM {$wpdb->prefix}restaurant_quotes 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            AND status IN ('sent', 'confirmed')
            GROUP BY service_type
        ", $period), ARRAY_A);

        $stats['revenue'] = array();
        foreach ($revenue as $rev) {
            $stats['revenue'][$rev['service_type']] = array(
                'total' => (float) $rev['total_revenue'],
                'count' => (int) $rev['count']
            );
        }

        return $stats;
    }
}
