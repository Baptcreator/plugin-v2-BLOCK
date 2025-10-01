<?php
/**
 * Interface admin pour gérer la correction des échappements multiples
 * 
 * @package RestaurantBooking
 * @since 3.0.2
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Fix_Escaped_Quotes_Admin
{
    /**
     * Ajouter la page admin
     */
    public static function add_admin_page()
    {
        add_submenu_page(
            'restaurant-booking',
            'Correction Échappements',
            'Correction Échappements',
            'manage_options',
            'restaurant-booking-fix-escaped-quotes',
            array(__CLASS__, 'admin_page')
        );
    }
    
    /**
     * Afficher la page admin
     */
    public static function admin_page()
    {
        // Traiter les actions
        if (isset($_POST['action'])) {
            self::handle_action($_POST['action']);
        }
        
        // Récupérer le statut
        $status = RestaurantBooking_Fix_Escaped_Quotes_Complete_Migration::get_status();
        
        ?>
        <div class="wrap">
            <h1>🔧 Correction des Échappements Multiples</h1>
            
            <div class="notice notice-info">
                <p><strong>Problème identifié :</strong> Certains textes dans le plugin affichent des apostrophes mal échappées (ex: "Jus d\\'Orange" au lieu de "Jus d'Orange").</p>
            </div>
            
            <div class="card">
                <h2>📊 État de la Migration</h2>
                <p><strong>Statut :</strong> 
                    <?php if ($status['completed']): ?>
                        <span style="color: green;">✅ Migration terminée</span>
                    <?php else: ?>
                        <span style="color: orange;">⏳ Migration en attente</span>
                    <?php endif; ?>
                </p>
            </div>
            
            <div class="card">
                <h2>🛠️ Actions Disponibles</h2>
                
                <form method="post" style="display: inline-block; margin-right: 10px;">
                    <?php wp_nonce_field('fix_escaped_quotes_action', 'fix_escaped_quotes_nonce'); ?>
                    <input type="hidden" name="action" value="run_migration">
                    <input type="submit" class="button button-primary" value="🚀 Exécuter la Migration" 
                           onclick="return confirm('Êtes-vous sûr de vouloir exécuter la migration ? Cette action va nettoyer tous les échappements multiples dans la base de données.');">
                </form>
                
                <form method="post" style="display: inline-block; margin-right: 10px;">
                    <?php wp_nonce_field('fix_escaped_quotes_action', 'fix_escaped_quotes_nonce'); ?>
                    <input type="hidden" name="action" value="reset_migration">
                    <input type="submit" class="button button-secondary" value="🔄 Réinitialiser" 
                           onclick="return confirm('Êtes-vous sûr de vouloir réinitialiser la migration ? Cela permettra de la ré-exécuter.');">
                </form>
                
                <form method="post" style="display: inline-block;">
                    <?php wp_nonce_field('fix_escaped_quotes_action', 'fix_escaped_quotes_nonce'); ?>
                    <input type="hidden" name="action" value="test_cleaning">
                    <input type="submit" class="button button-secondary" value="🧪 Tester le Nettoyage">
                </form>
            </div>
            
            <div class="card">
                <h2>📋 Tables et Options Concernées</h2>
                <h3>Tables de base de données :</h3>
                <ul>
                    <li><code>wp_restaurant_products</code> - Noms et descriptions des produits</li>
                    <li><code>wp_restaurant_categories</code> - Noms et descriptions des catégories</li>
                    <li><code>wp_restaurant_settings</code> - Paramètres du plugin</li>
                    <li><code>wp_restaurant_quotes</code> - Notes des devis</li>
                    <li><code>wp_restaurant_availability</code> - Raisons de blocage</li>
                    <li><code>wp_restaurant_delivery_zones</code> - Noms des zones</li>
                    <li><code>wp_restaurant_accompaniment_options</code> - Options d'accompagnement</li>
                    <li><code>wp_restaurant_accompaniment_suboptions</code> - Sous-options</li>
                    <li><code>wp_restaurant_beer_types</code> - Types de bières</li>
                    <li><code>wp_restaurant_wine_types</code> - Types de vins</li>
                    <li><code>wp_restaurant_games</code> - Jeux et animations</li>
                </ul>
                <h3>Options WordPress :</h3>
                <ul>
                    <li><code>restaurant_booking_pdf_settings</code> - <strong>Conditions générales PDF</strong></li>
                    <li><code>restaurant_booking_general_settings</code> - Paramètres généraux</li>
                    <li><code>restaurant_booking_email_settings</code> - Paramètres email</li>
                    <li><code>restaurant_booking_unified_options</code> - Options unifiées</li>
                </ul>
            </div>
            
            <div class="card">
                <h2>ℹ️ Informations Techniques</h2>
                <p><strong>Problème :</strong> Les apostrophes sont échappées plusieurs fois, créant des séquences comme <code>\\'</code> au lieu de <code>'</code>.</p>
                <p><strong>Solution :</strong> La migration nettoie automatiquement tous les échappements multiples dans les tables concernées.</p>
                <p><strong>Sécurité :</strong> Cette migration est sécurisée et ne modifie que les échappements problématiques.</p>
            </div>
        </div>
        
        <style>
        .card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        .card h2 {
            margin-top: 0;
            color: #23282d;
        }
        </style>
        <?php
    }
    
    /**
     * Traiter les actions
     */
    private static function handle_action($action)
    {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['fix_escaped_quotes_nonce'], 'fix_escaped_quotes_action')) {
            wp_die('Erreur de sécurité');
        }
        
        switch ($action) {
            case 'run_migration':
                $updated_count = RestaurantBooking_Fix_Escaped_Quotes_Complete_Migration::run();
                add_action('admin_notices', function() use ($updated_count) {
                    echo '<div class="notice notice-success is-dismissible"><p>✅ Migration terminée avec succès ! ' . $updated_count . ' enregistrements nettoyés.</p></div>';
                });
                break;
                
            case 'reset_migration':
                RestaurantBooking_Fix_Escaped_Quotes_Complete_Migration::reset();
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success is-dismissible"><p>🔄 Migration réinitialisée avec succès !</p></div>';
                });
                break;
                
            case 'test_cleaning':
                self::test_cleaning();
                break;
        }
    }
    
    /**
     * Tester le nettoyage
     */
    private static function test_cleaning()
    {
        $test_strings = array(
            "Jus d\\'Orange" => "Jus d'Orange",
            "Café d\\'après-midi" => "Café d'après-midi",
            "L\\'équipe" => "L'équipe",
            "D\\'accord" => "D'accord",
            "Text normal" => "Text normal"
        );
        
        $results = array();
        foreach ($test_strings as $input => $expected) {
            $cleaned = RestaurantBooking_Text_Cleaner::clean_escaped_quotes($input);
            $results[] = array(
                'input' => $input,
                'expected' => $expected,
                'cleaned' => $cleaned,
                'success' => $cleaned === $expected
            );
        }
        
        add_action('admin_notices', function() use ($results) {
            echo '<div class="notice notice-info is-dismissible">';
            echo '<h3>🧪 Résultats du Test de Nettoyage</h3>';
            echo '<table class="widefat">';
            echo '<thead><tr><th>Entrée</th><th>Attendu</th><th>Résultat</th><th>Statut</th></tr></thead>';
            echo '<tbody>';
            foreach ($results as $result) {
                $status = $result['success'] ? '✅ Succès' : '❌ Échec';
                $color = $result['success'] ? 'green' : 'red';
                echo '<tr>';
                echo '<td><code>' . esc_html($result['input']) . '</code></td>';
                echo '<td><code>' . esc_html($result['expected']) . '</code></td>';
                echo '<td><code>' . esc_html($result['cleaned']) . '</code></td>';
                echo '<td style="color: ' . $color . ';">' . $status . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
            echo '</div>';
        });
    }
}
