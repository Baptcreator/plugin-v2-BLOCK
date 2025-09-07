<?php
/**
 * SCRIPT DE TEST - Options Unifiées
 * 
 * Ce fichier permet de tester rapidement le système d'options unifiées
 * À exécuter depuis l'admin WordPress ou via WP-CLI
 *
 * @package RestaurantBooking
 * @since 2.1.0
 */

// Vérifier que nous sommes dans WordPress
if (!defined('ABSPATH')) {
    echo "Ce script doit être exécuté depuis WordPress.\n";
    exit;
}

// Fonction de test des options
function test_restaurant_booking_options() {
    echo "<h2>🧪 Test des Options Unifiées - Restaurant Booking</h2>\n";
    
    // Vérifier que la classe helper existe
    if (!class_exists('RestaurantBooking_Options_Helper')) {
        echo "<div style='color: red;'>❌ Erreur : La classe RestaurantBooking_Options_Helper n'est pas chargée.</div>\n";
        return false;
    }
    
    echo "<div style='color: green;'>✅ Classe RestaurantBooking_Options_Helper chargée.</div>\n";
    
    // Obtenir l'instance
    $helper = RestaurantBooking_Options_Helper::get_instance();
    
    // Test 1: Options par défaut
    echo "<h3>📋 Test 1: Options par défaut</h3>\n";
    
    $restaurant_limits = $helper->get_restaurant_limits();
    echo "<strong>Limites Restaurant:</strong><br>\n";
    echo "- Min convives: " . $restaurant_limits['min_guests'] . "<br>\n";
    echo "- Max convives: " . $restaurant_limits['max_guests'] . "<br>\n";
    echo "- Texte: " . $restaurant_limits['guests_text'] . "<br><br>\n";
    
    $remorque_limits = $helper->get_remorque_limits();
    echo "<strong>Limites Remorque:</strong><br>\n";
    echo "- Min convives: " . $remorque_limits['min_guests'] . "<br>\n";
    echo "- Max convives: " . $remorque_limits['max_guests'] . "<br>\n";
    echo "- Seuil personnel: " . $remorque_limits['staff_threshold'] . "<br><br>\n";
    
    // Test 2: Règles de validation
    echo "<h3>🔍 Test 2: Règles de validation</h3>\n";
    
    $buffet_sale_rules = $helper->get_buffet_sale_rules();
    echo "<strong>Buffet Salé:</strong><br>\n";
    echo "- Min/personne: " . $buffet_sale_rules['min_per_person'] . "<br>\n";
    echo "- Min recettes: " . $buffet_sale_rules['min_recipes'] . "<br>\n";
    echo "- Texte: " . $buffet_sale_rules['text'] . "<br><br>\n";
    
    // Test 3: Calculs
    echo "<h3>🧮 Test 3: Calculs automatiques</h3>\n";
    
    // Test supplément distance
    $distance_price_30km = $helper->calculate_distance_price(30);
    $distance_price_45km = $helper->calculate_distance_price(45);
    $distance_price_80km = $helper->calculate_distance_price(80);
    
    echo "<strong>Prix selon distance:</strong><br>\n";
    echo "- 30km: " . ($distance_price_30km === 0 ? "Gratuit" : $distance_price_30km . "€") . "<br>\n";
    echo "- 45km: " . $distance_price_45km . "€<br>\n";
    echo "- 80km: " . $distance_price_80km . "€<br><br>\n";
    
    // Test supplément personnel
    $staff_30 = $helper->calculate_staff_supplement(30);
    $staff_60 = $helper->calculate_staff_supplement(60);
    
    echo "<strong>Supplément personnel:</strong><br>\n";
    echo "- 30 personnes: " . $staff_30 . "€<br>\n";
    echo "- 60 personnes: " . $staff_60 . "€<br><br>\n";
    
    // Test supplément horaire
    $hour_resto_2h = $helper->calculate_hour_supplement(2, 'restaurant');
    $hour_resto_4h = $helper->calculate_hour_supplement(4, 'restaurant');
    
    echo "<strong>Supplément horaire restaurant:</strong><br>\n";
    echo "- 2h: " . $hour_resto_2h . "€<br>\n";
    echo "- 4h: " . $hour_resto_4h . "€<br><br>\n";
    
    // Test 4: Validations
    echo "<h3>✅ Test 4: Validations</h3>\n";
    
    // Test validation buffet salé
    $selected_dishes = array('plat1' => 5, 'plat2' => 5); // 2 plats, 10 portions total
    $guests = 10;
    $errors = $helper->validate_buffet_sale($selected_dishes, $guests);
    
    echo "<strong>Validation Buffet Salé (2 plats, 10 portions, 10 personnes):</strong><br>\n";
    if (empty($errors)) {
        echo "<div style='color: green;'>✅ Validation réussie</div><br>\n";
    } else {
        echo "<div style='color: red;'>❌ Erreurs: " . implode(', ', $errors) . "</div><br>\n";
    }
    
    // Test avec erreur
    $selected_dishes_error = array('plat1' => 5); // 1 seul plat
    $errors = $helper->validate_buffet_sale($selected_dishes_error, $guests);
    
    echo "<strong>Validation Buffet Salé (1 plat seulement - doit échouer):</strong><br>\n";
    if (empty($errors)) {
        echo "<div style='color: red;'>❌ Erreur : validation devrait échouer</div><br>\n";
    } else {
        echo "<div style='color: green;'>✅ Validation échoue correctement: " . implode(', ', $errors) . "</div><br>\n";
    }
    
    // Test 5: Fonctions raccourcis
    echo "<h3>⚡ Test 5: Fonctions raccourcis</h3>\n";
    
    if (function_exists('rb_get_option')) {
        $min_guests = rb_get_option('restaurant_min_guests');
        echo "<strong>rb_get_option('restaurant_min_guests'):</strong> " . $min_guests . "<br>\n";
    } else {
        echo "<div style='color: red;'>❌ Fonction rb_get_option non disponible</div><br>\n";
    }
    
    if (function_exists('rb_get_limits')) {
        $limits = rb_get_limits('restaurant');
        echo "<strong>rb_get_limits('restaurant'):</strong> Min=" . $limits['min_guests'] . ", Max=" . $limits['max_guests'] . "<br><br>\n";
    } else {
        echo "<div style='color: red;'>❌ Fonction rb_get_limits non disponible</div><br>\n";
    }
    
    // Test 6: Textes d'interface
    echo "<h3>💬 Test 6: Textes d'interface</h3>\n";
    
    $texts = $helper->get_interface_texts();
    echo "<strong>Message final:</strong><br>\n";
    echo "<em>" . substr($texts['final_message'], 0, 100) . "...</em><br><br>\n";
    
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #2e7d32; margin-top: 0;'>🎉 Tests terminés avec succès !</h3>";
    echo "<p>Le système d'options unifiées fonctionne correctement.</p>";
    echo "<p><strong>Prochaines étapes :</strong></p>";
    echo "<ul>";
    echo "<li>Accéder à la page d'administration : <a href='" . admin_url('admin.php?page=restaurant-booking-options-unified') . "'>Options de Configuration</a></li>";
    echo "<li>Modifier les options selon vos besoins</li>";
    echo "<li>Mettre à jour les widgets publics pour utiliser les nouvelles options</li>";
    echo "</ul>";
    echo "</div>";
    
    return true;
}

// Exécuter les tests si nous sommes dans l'admin
if (is_admin() && current_user_can('manage_options')) {
    // Ajouter une action pour afficher les tests dans l'admin
    add_action('admin_notices', function() {
        if (isset($_GET['test_options']) && $_GET['test_options'] === '1') {
            echo '<div class="notice notice-info" style="background: white; border-left: 4px solid #0073aa; padding: 20px;">';
            test_restaurant_booking_options();
            echo '</div>';
        }
    });
}

// Fonction pour exécuter via WP-CLI
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('rb-test-options', function() {
        WP_CLI::line('🧪 Test des Options Unifiées - Restaurant Booking');
        WP_CLI::line('================================================');
        
        if (test_restaurant_booking_options()) {
            WP_CLI::success('Tous les tests sont passés avec succès !');
        } else {
            WP_CLI::error('Certains tests ont échoué.');
        }
    });
}
