<?php
/**
 * Interface d'administration pour la migration v2
 *
 * @package RestaurantBooking
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Migration_Admin
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_force_migration_v2', array($this, 'ajax_force_migration'));
        add_action('admin_notices', array($this, 'show_migration_notice'));
    }

    /**
     * Ajouter le menu d'administration
     */
    public function add_admin_menu()
    {
        add_submenu_page(
            'restaurant-booking',
            __('Migration v2', 'restaurant-booking'),
            __('Migration v2', 'restaurant-booking'),
            'manage_options',
            'restaurant-booking-migration',
            array($this, 'render_migration_page')
        );
    }

    /**
     * Afficher une notice de migration si nécessaire
     */
    public function show_migration_notice()
    {
        if (!class_exists('RestaurantBooking_Migration_V2')) {
            return;
        }

        $status = RestaurantBooking_Migration_V2::get_migration_status();
        
        if ($status['migration_needed']) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong><?php _e('Restaurant Booking v2', 'restaurant-booking'); ?></strong> - 
                    <?php _e('Une migration vers la version 2 est nécessaire.', 'restaurant-booking'); ?>
                    <a href="<?php echo admin_url('admin.php?page=restaurant-booking-migration'); ?>" class="button button-primary">
                        <?php _e('Lancer la migration', 'restaurant-booking'); ?>
                    </a>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Rendu de la page de migration
     */
    public function render_migration_page()
    {
        if (!class_exists('RestaurantBooking_Migration_V2')) {
            echo '<div class="wrap"><h1>Erreur</h1><p>Classe de migration introuvable.</p></div>';
            return;
        }

        $status = RestaurantBooking_Migration_V2::get_migration_status();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Migration Restaurant Booking v2', 'restaurant-booking'); ?></h1>
            
            <div class="card">
                <h2><?php _e('État de la migration', 'restaurant-booking'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Version actuelle', 'restaurant-booking'); ?></th>
                        <td><code><?php echo esc_html($status['current_version']); ?></code></td>
                    </tr>
                    <tr>
                        <th><?php _e('Version cible', 'restaurant-booking'); ?></th>
                        <td><code><?php echo esc_html($status['target_version']); ?></code></td>
                    </tr>
                    <tr>
                        <th><?php _e('Statut', 'restaurant-booking'); ?></th>
                        <td>
                            <?php if ($status['migration_completed']): ?>
                                <span style="color: green; font-weight: bold;">✅ Migration terminée</span>
                            <?php else: ?>
                                <span style="color: orange; font-weight: bold;">⚠️ Migration nécessaire</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>

            <?php if ($status['migration_needed']): ?>
            <div class="card">
                <h2><?php _e('Lancer la migration', 'restaurant-booking'); ?></h2>
                <p><?php _e('Cette migration va :', 'restaurant-booking'); ?></p>
                <ul>
                    <li>✅ Créer les nouvelles tables (jeux, suppléments, contenances)</li>
                    <li>✅ Ajouter les nouveaux champs aux tables existantes</li>
                    <li>✅ Insérer les données par défaut</li>
                    <li>✅ Activer le nouveau widget Elementor unifié</li>
                    <li>✅ Ajouter les nouvelles pages d'administration</li>
                </ul>
                
                <p><strong><?php _e('⚠️ Important :', 'restaurant-booking'); ?></strong> 
                <?php _e('Sauvegardez votre base de données avant de continuer.', 'restaurant-booking'); ?></p>
                
                <button id="force-migration-btn" class="button button-primary button-large">
                    <?php _e('🚀 LANCER LA MIGRATION V2', 'restaurant-booking'); ?>
                </button>
                
                <div id="migration-progress" style="display: none; margin-top: 20px;">
                    <div class="progress-bar" style="background: #f0f0f0; border-radius: 4px; overflow: hidden;">
                        <div class="progress-fill" style="background: #0073aa; height: 20px; width: 0%; transition: width 0.3s;"></div>
                    </div>
                    <p id="migration-status"><?php _e('Préparation...', 'restaurant-booking'); ?></p>
                </div>
                
                <div id="migration-result" style="display: none; margin-top: 20px;"></div>
            </div>
            <?php else: ?>
            <div class="card">
                <h2><?php _e('Migration terminée', 'restaurant-booking'); ?></h2>
                <p style="color: green; font-weight: bold;">✅ <?php _e('La migration v2 est terminée avec succès !', 'restaurant-booking'); ?></p>
                
                <h3><?php _e('Nouvelles fonctionnalités disponibles :', 'restaurant-booking'); ?></h3>
                <ul>
                    <li>🎯 <strong>Widget Elementor unifié</strong> - Formulaire avec 2 parcours (Restaurant/Remorque)</li>
                    <li>🎮 <strong>Gestion des jeux</strong> - <a href="<?php echo admin_url('admin.php?page=restaurant-booking-games'); ?>">Accéder à la gestion des jeux</a></li>
                    <li>🍹 <strong>Système de boissons avancé</strong> - Contenances multiples et suggestions</li>
                    <li>📍 <strong>Calcul de distance</strong> - Suppléments automatiques par zone</li>
                    <li>💰 <strong>Calculateur de prix v2</strong> - Calculs temps réel complexes</li>
                </ul>
                
                <h3><?php _e('Prochaines étapes :', 'restaurant-booking'); ?></h3>
                <ol>
                    <li>Configurez vos jeux dans <a href="<?php echo admin_url('admin.php?page=restaurant-booking-games'); ?>">Restaurant Devis > Jeux</a></li>
                    <li>Personnalisez les textes dans <a href="<?php echo admin_url('admin.php?page=restaurant-booking-texts'); ?>">Restaurant Devis > Textes interface</a></li>
                    <li>Utilisez le nouveau widget <strong>"Formulaire de Devis Unifié v2"</strong> dans Elementor</li>
                </ol>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <h2><?php _e('Diagnostic système', 'restaurant-booking'); ?></h2>
                <?php $this->render_system_diagnostic(); ?>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#force-migration-btn').on('click', function() {
                if (!confirm('<?php _e('Êtes-vous sûr de vouloir lancer la migration ? Assurez-vous d\'avoir sauvegardé votre base de données.', 'restaurant-booking'); ?>')) {
                    return;
                }
                
                $(this).prop('disabled', true).text('<?php _e('Migration en cours...', 'restaurant-booking'); ?>');
                $('#migration-progress').show();
                
                // Simuler le progrès
                let progress = 0;
                const progressInterval = setInterval(() => {
                    progress += 10;
                    $('.progress-fill').css('width', progress + '%');
                    
                    if (progress >= 90) {
                        clearInterval(progressInterval);
                    }
                }, 200);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'force_migration_v2',
                        nonce: '<?php echo wp_create_nonce('force_migration_v2'); ?>'
                    },
                    success: function(response) {
                        clearInterval(progressInterval);
                        $('.progress-fill').css('width', '100%');
                        
                        if (response.success) {
                            $('#migration-result').html(
                                '<div class="notice notice-success"><p><strong>✅ Migration terminée avec succès !</strong></p></div>' +
                                '<p>La page va se recharger dans 3 secondes...</p>'
                            ).show();
                            
                            setTimeout(() => {
                                window.location.reload();
                            }, 3000);
                        } else {
                            $('#migration-result').html(
                                '<div class="notice notice-error"><p><strong>❌ Erreur :</strong> ' + (response.data || 'Erreur inconnue') + '</p></div>'
                            ).show();
                            
                            $('#force-migration-btn').prop('disabled', false).text('<?php _e('Réessayer la migration', 'restaurant-booking'); ?>');
                        }
                    },
                    error: function() {
                        clearInterval(progressInterval);
                        $('#migration-result').html(
                            '<div class="notice notice-error"><p><strong>❌ Erreur de connexion</strong></p></div>'
                        ).show();
                        
                        $('#force-migration-btn').prop('disabled', false).text('<?php _e('Réessayer la migration', 'restaurant-booking'); ?>');
                    }
                });
            });
        });
        </script>

        <style>
        .card { background: white; padding: 20px; margin: 20px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .progress-bar { height: 20px; background: #f0f0f0; border-radius: 4px; overflow: hidden; }
        .progress-fill { height: 100%; background: #0073aa; transition: width 0.3s; }
        </style>
        <?php
    }

    /**
     * Diagnostic système
     */
    private function render_system_diagnostic()
    {
        global $wpdb;
        
        echo '<table class="form-table">';
        
        // Vérifier les tables
        $tables_to_check = array(
            'restaurant_games' => 'Table des jeux',
            'restaurant_product_supplements' => 'Table des suppléments',
            'restaurant_beverage_sizes' => 'Table des contenances',
            'restaurant_categories' => 'Table des catégories',
            'restaurant_products' => 'Table des produits'
        );
        
        foreach ($tables_to_check as $table => $description) {
            $full_table_name = $wpdb->prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'") === $full_table_name;
            
            echo '<tr>';
            echo '<th>' . esc_html($description) . '</th>';
            echo '<td>' . ($exists ? '<span style="color: green;">✅ Existe</span>' : '<span style="color: red;">❌ Manquante</span>') . '</td>';
            echo '</tr>';
        }
        
        // Vérifier les nouvelles colonnes
        $columns_to_check = array(
            'restaurant_categories.is_featured' => 'Colonne is_featured (catégories)',
            'restaurant_products.is_featured' => 'Colonne is_featured (produits)',
            'restaurant_products.keg_size_10l_price' => 'Colonne prix fût 10L',
            'restaurant_products.keg_size_20l_price' => 'Colonne prix fût 20L'
        );
        
        foreach ($columns_to_check as $column => $description) {
            list($table, $column_name) = explode('.', $column);
            $full_table_name = $wpdb->prefix . $table;
            
            $exists = $wpdb->get_results("SHOW COLUMNS FROM $full_table_name LIKE '$column_name'");
            
            echo '<tr>';
            echo '<th>' . esc_html($description) . '</th>';
            echo '<td>' . (!empty($exists) ? '<span style="color: green;">✅ Existe</span>' : '<span style="color: red;">❌ Manquante</span>') . '</td>';
            echo '</tr>';
        }
        
        // Vérifier les classes
        $classes_to_check = array(
            'RestaurantBooking_Migration_V2' => 'Classe de migration v2',
            'RestaurantBooking_Game' => 'Classe de gestion des jeux',
            'RestaurantBooking_Supplement_Manager' => 'Gestionnaire de suppléments',
            'RestaurantBooking_Beverage_Manager' => 'Gestionnaire de boissons',
            'RestaurantBooking_Distance_Calculator' => 'Calculateur de distance',
            'RestaurantBooking_Quote_Calculator_V2' => 'Calculateur de prix v2'
        );
        
        foreach ($classes_to_check as $class => $description) {
            $exists = class_exists($class);
            
            echo '<tr>';
            echo '<th>' . esc_html($description) . '</th>';
            echo '<td>' . ($exists ? '<span style="color: green;">✅ Chargée</span>' : '<span style="color: red;">❌ Non chargée</span>') . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
    }

    /**
     * AJAX: Forcer la migration
     */
    public function ajax_force_migration()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'force_migration_v2')) {
            wp_send_json_error('Erreur de sécurité');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permissions insuffisantes');
        }

        try {
            if (class_exists('RestaurantBooking_Migration_V2')) {
                RestaurantBooking_Migration_V2::force_migration();
                wp_send_json_success('Migration terminée avec succès');
            } else {
                wp_send_json_error('Classe de migration introuvable');
            }
        } catch (Exception $e) {
            wp_send_json_error('Erreur lors de la migration: ' . $e->getMessage());
        }
    }
}
