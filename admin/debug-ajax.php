<?php
/**
 * Script de diagnostic AJAX pour l'administration
 * Accessible via: /wp-admin/admin.php?page=restaurant-booking-debug
 */

if (!defined('ABSPATH')) {
    exit;
}

// Vérifier les permissions
if (!current_user_can('manage_restaurant_quotes')) {
    wp_die(__('Permissions insuffisantes', 'restaurant-booking'));
}

// Ajouter la page de diagnostic au menu admin
add_action('admin_menu', function() {
    add_submenu_page(
        'restaurant-booking',
        __('Diagnostic AJAX', 'restaurant-booking'),
        __('🔧 Diagnostic', 'restaurant-booking'),
        'manage_restaurant_quotes',
        'restaurant-booking-debug',
        'restaurant_booking_debug_page'
    );
});

function restaurant_booking_debug_page() {
    ?>
    <div class="wrap">
        <h1>🔧 Diagnostic AJAX - Restaurant Booking</h1>
        
        <div style="background: #f1f1f1; padding: 20px; margin: 20px 0; border-radius: 5px;">
            <h2>1. Vérification des classes</h2>
            <?php
            $classes_to_check = [
                'RestaurantBooking_Admin',
                'RestaurantBooking_Product', 
                'RestaurantBooking_Game',
                'RestaurantBooking_Logger'
            ];

            foreach ($classes_to_check as $class) {
                if (class_exists($class)) {
                    echo "✅ <strong>$class</strong> existe<br>";
                } else {
                    echo "❌ <strong>$class</strong> n'existe pas<br>";
                }
            }
            ?>
        </div>

        <div style="background: #f1f1f1; padding: 20px; margin: 20px 0; border-radius: 5px;">
            <h2>2. Actions AJAX enregistrées</h2>
            <?php
            global $wp_filter;
            $ajax_actions = $wp_filter['wp_ajax_restaurant_booking_admin_action'] ?? null;
            if ($ajax_actions) {
                echo "✅ Action AJAX 'restaurant_booking_admin_action' enregistrée<br>";
                foreach ($ajax_actions->callbacks[10] as $callback) {
                    if (is_array($callback['function'])) {
                        $class = is_object($callback['function'][0]) ? get_class($callback['function'][0]) : $callback['function'][0];
                        $method = $callback['function'][1];
                        echo "   - <strong>$class::$method</strong><br>";
                    }
                }
            } else {
                echo "❌ Action AJAX 'restaurant_booking_admin_action' non enregistrée<br>";
            }
            ?>
        </div>

        <div style="background: #f1f1f1; padding: 20px; margin: 20px 0; border-radius: 5px;">
            <h2>3. Test des nonces</h2>
            <?php
            $nonce = wp_create_nonce('restaurant_booking_admin_nonce');
            echo "Nonce généré: <code>$nonce</code><br>";
            if (wp_verify_nonce($nonce, 'restaurant_booking_admin_nonce')) {
                echo "✅ Nonce valide<br>";
            } else {
                echo "❌ Nonce invalide<br>";
            }
            ?>
        </div>

        <div style="background: #f1f1f1; padding: 20px; margin: 20px 0; border-radius: 5px;">
            <h2>4. Test de la base de données</h2>
            <?php
            global $wpdb;
            $table_name = $wpdb->prefix . 'restaurant_products';
            $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
            if ($count !== null) {
                echo "✅ Table 'restaurant_products' accessible ($count produits)<br>";
                
                // Test d'un produit
                $test_product = $wpdb->get_row("SELECT * FROM $table_name LIMIT 1");
                if ($test_product) {
                    echo "✅ Produit de test trouvé: <strong>{$test_product->name}</strong> (ID: {$test_product->id})<br>";
                } else {
                    echo "⚠️ Aucun produit trouvé<br>";
                }
            } else {
                echo "❌ Table 'restaurant_products' inaccessible<br>";
                echo "Erreur: " . $wpdb->last_error . "<br>";
            }
            ?>
        </div>

        <div style="background: #f1f1f1; padding: 20px; margin: 20px 0; border-radius: 5px;">
            <h2>5. Scripts JavaScript</h2>
            <?php
            $admin_script_path = RESTAURANT_BOOKING_PLUGIN_DIR . 'assets/js/admin.js';
            if (file_exists($admin_script_path)) {
                echo "✅ Fichier admin.js existe<br>";
                echo "Chemin: <code>$admin_script_path</code><br>";
            } else {
                echo "❌ Fichier admin.js manquant<br>";
                echo "Chemin attendu: <code>$admin_script_path</code><br>";
            }
            ?>
        </div>

        <div style="background: #f1f1f1; padding: 20px; margin: 20px 0; border-radius: 5px;">
            <h2>6. Test AJAX manuel</h2>
            <button type="button" id="test-ajax-btn" class="button button-primary">Tester AJAX</button>
            <div id="ajax-result" style="margin-top: 10px; padding: 10px; background: white; border: 1px solid #ccc; min-height: 50px;"></div>
        </div>

        <div style="background: #f1f1f1; padding: 20px; margin: 20px 0; border-radius: 5px;">
            <h2>7. Instructions de test</h2>
            <ol>
                <li>Ouvrez la console du navigateur (F12)</li>
                <li>Allez sur une page d'administration du plugin (ex: Bières, Vins, etc.)</li>
                <li>Cliquez sur un bouton de suppression</li>
                <li>Vérifiez s'il y a des erreurs JavaScript dans la console</li>
                <li>Vérifiez la requête AJAX dans l'onglet Network</li>
            </ol>
        </div>
    </div>

    <script>
    document.getElementById('test-ajax-btn').addEventListener('click', function() {
        const resultDiv = document.getElementById('ajax-result');
        resultDiv.innerHTML = 'Test en cours...';
        
        const formData = new FormData();
        formData.append('action', 'restaurant_booking_admin_action');
        formData.append('admin_action', 'delete_product');
        formData.append('product_id', '999999'); // ID inexistant
        formData.append('nonce', '<?php echo $nonce; ?>');
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            resultDiv.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        })
        .catch(error => {
            resultDiv.innerHTML = 'Erreur: ' + error.message;
        });
    });
    </script>
    <?php
}
