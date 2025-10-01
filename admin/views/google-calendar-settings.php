<?php
/**
 * Vue de configuration Google Calendar
 *
 * @package RestaurantBooking
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$client_id = get_option('restaurant_booking_google_client_id', '');
$client_secret = get_option('restaurant_booking_google_client_secret', '');
$calendar_id = get_option('restaurant_booking_google_calendar_id', 'primary');
$sync_frequency = get_option('restaurant_booking_google_sync_frequency', 'hourly');

?>
<div class="wrap">
    <h1><?php _e('Configuration Google Calendar', 'restaurant-booking'); ?></h1>

    <?php if (isset($_GET['auth']) && $_GET['auth'] === 'success'): ?>
        <div class="notice notice-success">
            <p><?php _e('✅ Autorisation Google Calendar réussie !', 'restaurant-booking'); ?></p>
        </div>
    <?php elseif (isset($_GET['auth']) && $_GET['auth'] === 'error'): ?>
        <div class="notice notice-error">
            <p><?php _e('❌ Erreur lors de l\'autorisation Google Calendar.', 'restaurant-booking'); ?></p>
        </div>
    <?php endif; ?>

    <div class="google-calendar-container">
        <!-- Statut de connexion -->
        <div class="connection-status">
            <h2><?php _e('Statut de la connexion', 'restaurant-booking'); ?></h2>
            <div class="status-card <?php echo $is_connected ? 'connected' : 'disconnected'; ?>">
                <div class="status-icon">
                    <?php echo $is_connected ? '🟢' : '🔴'; ?>
                </div>
                <div class="status-info">
                    <h3><?php echo $is_connected ? __('Connecté', 'restaurant-booking') : __('Non connecté', 'restaurant-booking'); ?></h3>
                    <p>
                        <?php if ($is_connected): ?>
                            <?php _e('Synchronisation bidirectionnelle active avec Google Calendar', 'restaurant-booking'); ?>
                        <?php else: ?>
                            <?php _e('Configurez les paramètres ci-dessous pour activer la synchronisation', 'restaurant-booking'); ?>
                        <?php endif; ?>
                    </p>
                </div>
                <?php if ($is_connected): ?>
                    <div class="status-actions">
                        <form method="post" style="display: inline;">
                            <button type="submit" name="test_connection" class="button">
                                <?php _e('Tester la connexion', 'restaurant-booking'); ?>
                            </button>
                        </form>
                        <form method="post" style="display: inline;">
                            <button type="submit" name="sync_calendar" class="button button-primary">
                                <?php _e('Synchroniser maintenant', 'restaurant-booking'); ?>
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Configuration OAuth2 -->
        <div class="settings-section">
            <h2><?php _e('Configuration OAuth2', 'restaurant-booking'); ?></h2>
            
            <div class="setup-steps">
                <div class="step">
                    <h4><?php _e('Étape 1 : Créer un projet Google Cloud', 'restaurant-booking'); ?></h4>
                    <ol>
                        <li><?php _e('Allez sur', 'restaurant-booking'); ?> <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a></li>
                        <li><?php _e('Créez un nouveau projet ou sélectionnez un projet existant', 'restaurant-booking'); ?></li>
                        <li><?php _e('Activez l\'API Google Calendar', 'restaurant-booking'); ?></li>
                    </ol>
                </div>

                <div class="step">
                    <h4><?php _e('Étape 2 : Configurer OAuth2', 'restaurant-booking'); ?></h4>
                    <ol>
                        <li><?php _e('Dans "Identifiants", créez un "ID client OAuth 2.0"', 'restaurant-booking'); ?></li>
                        <li><?php _e('Type d\'application : Application Web', 'restaurant-booking'); ?></li>
                        <li><?php _e('URI de redirection autorisée :', 'restaurant-booking'); ?>
                            <code class="redirect-uri"><?php echo admin_url('admin-ajax.php?action=google_calendar_auth'); ?></code>
                            <button type="button" onclick="copyToClipboard('<?php echo admin_url('admin-ajax.php?action=google_calendar_auth'); ?>')" class="button button-small">
                                <?php _e('Copier', 'restaurant-booking'); ?>
                            </button>
                        </li>
                    </ol>
                </div>
            </div>

            <form method="post">
                <?php wp_nonce_field('google_calendar_settings', '_wpnonce'); ?>
                <input type="hidden" name="save_google_settings" value="1">

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="client_id"><?php _e('Client ID', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="client_id" name="client_id" value="<?php echo esc_attr($client_id); ?>" class="large-text" />
                            <p class="description"><?php _e('ID client OAuth2 depuis Google Cloud Console', 'restaurant-booking'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="client_secret"><?php _e('Client Secret', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <input type="password" id="client_secret" name="client_secret" value="<?php echo esc_attr($client_secret); ?>" class="large-text" />
                            <p class="description"><?php _e('Secret client OAuth2 depuis Google Cloud Console', 'restaurant-booking'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="calendar_id"><?php _e('ID du calendrier', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="calendar_id" name="calendar_id" value="<?php echo esc_attr($calendar_id); ?>" class="large-text" placeholder="primary" />
                            <p class="description"><?php _e('ID du calendrier Google à synchroniser (primary pour le calendrier principal)', 'restaurant-booking'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="sync_frequency"><?php _e('Fréquence de synchronisation', 'restaurant-booking'); ?></label>
                        </th>
                        <td>
                            <select id="sync_frequency" name="sync_frequency">
                                <option value="hourly" <?php selected($sync_frequency, 'hourly'); ?>><?php _e('Toutes les heures', 'restaurant-booking'); ?></option>
                                <option value="twicedaily" <?php selected($sync_frequency, 'twicedaily'); ?>><?php _e('Deux fois par jour', 'restaurant-booking'); ?></option>
                                <option value="daily" <?php selected($sync_frequency, 'daily'); ?>><?php _e('Une fois par jour', 'restaurant-booking'); ?></option>
                                <option value="manual" <?php selected($sync_frequency, 'manual'); ?>><?php _e('Manuelle uniquement', 'restaurant-booking'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>

                <?php submit_button(__('Sauvegarder la configuration', 'restaurant-booking')); ?>
            </form>
        </div>

        <!-- Autorisation -->
        <?php if (!empty($client_id) && !empty($client_secret)): ?>
            <div class="settings-section">
                <h2><?php _e('Autorisation', 'restaurant-booking'); ?></h2>
                
                <?php if (!$is_connected): ?>
                    <div class="auth-section">
                        <p><?php _e('Cliquez sur le bouton ci-dessous pour autoriser l\'accès à votre Google Calendar :', 'restaurant-booking'); ?></p>
                        <a href="<?php echo esc_url($auth_url); ?>" class="button button-primary button-large">
                            🔗 <?php _e('Autoriser l\'accès à Google Calendar', 'restaurant-booking'); ?>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="auth-section connected">
                        <p>✅ <?php _e('Accès autorisé à Google Calendar', 'restaurant-booking'); ?></p>
                        <button type="button" onclick="revokeAccess()" class="button button-secondary">
                            <?php _e('Révoquer l\'accès', 'restaurant-booking'); ?>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Synchronisation -->
        <?php if ($is_connected): ?>
            <div class="settings-section">
                <h2><?php _e('Synchronisation bidirectionnelle', 'restaurant-booking'); ?></h2>
                
                <div class="sync-explanation">
                    <h4><?php _e('Comment ça marche :', 'restaurant-booking'); ?></h4>
                    <div class="sync-flow">
                        <div class="sync-direction">
                            <h5>📅 Google Calendar → WordPress</h5>
                            <ul>
                                <li><?php _e('Les événements contenant "Block", "Restaurant" ou "Remorque" sont synchronisés', 'restaurant-booking'); ?></li>
                                <li><?php _e('Les dates sont automatiquement marquées comme non disponibles', 'restaurant-booking'); ?></li>
                                <li><?php _e('Synchronisation automatique selon la fréquence choisie', 'restaurant-booking'); ?></li>
                            </ul>
                        </div>
                        
                        <div class="sync-direction">
                            <h5>🏠 WordPress → Google Calendar</h5>
                            <ul>
                                <li><?php _e('Les blocages de dates créent des événements dans Google Calendar', 'restaurant-booking'); ?></li>
                                <li><?php _e('Les devis confirmés peuvent créer des événements', 'restaurant-booking'); ?></li>
                                <li><?php _e('Titre automatique : "Block & Co - [Service] indisponible"', 'restaurant-booking'); ?></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="sync-stats">
                    <h4><?php _e('Statistiques de synchronisation', 'restaurant-booking'); ?></h4>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-number">--</div>
                            <div class="stat-label"><?php _e('Dernière sync', 'restaurant-booking'); ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">--</div>
                            <div class="stat-label"><?php _e('Événements synchronisés', 'restaurant-booking'); ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">--</div>
                            <div class="stat-label"><?php _e('Erreurs', 'restaurant-booking'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Prérequis techniques -->
        <div class="settings-section">
            <h2><?php _e('Prérequis techniques', 'restaurant-booking'); ?></h2>
            
            <div class="requirements-check">
                <?php
                $requirements = array(
                    'google_simple' => file_exists(RESTAURANT_BOOKING_PLUGIN_DIR . 'google-calendar-simple.php'),
                    'curl' => function_exists('curl_init'),
                    'json' => function_exists('json_encode'),
                    'openssl' => extension_loaded('openssl')
                );
                ?>
                
                <div class="requirement-item <?php echo $requirements['google_simple'] ? 'ok' : 'error'; ?>">
                    <?php echo $requirements['google_simple'] ? '✅' : '❌'; ?>
                    <strong><?php _e('Google Calendar Integration (Version simplifiée)', 'restaurant-booking'); ?></strong>
                    <?php if (!$requirements['google_simple']): ?>
                        <p><?php _e('Fichier google-calendar-simple.php manquant', 'restaurant-booking'); ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="requirement-item <?php echo $requirements['curl'] ? 'ok' : 'error'; ?>">
                    <?php echo $requirements['curl'] ? '✅' : '❌'; ?>
                    <strong><?php _e('Extension cURL', 'restaurant-booking'); ?></strong>
                </div>
                
                <div class="requirement-item <?php echo $requirements['json'] ? 'ok' : 'error'; ?>">
                    <?php echo $requirements['json'] ? '✅' : '❌'; ?>
                    <strong><?php _e('Support JSON', 'restaurant-booking'); ?></strong>
                </div>
                
                <div class="requirement-item <?php echo $requirements['openssl'] ? 'ok' : 'error'; ?>">
                    <?php echo $requirements['openssl'] ? '✅' : '❌'; ?>
                    <strong><?php _e('Extension OpenSSL', 'restaurant-booking'); ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.google-calendar-container {
    max-width: 1000px;
}

.connection-status {
    margin-bottom: 30px;
}

.status-card {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 20px;
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 8px;
    margin-bottom: 20px;
}

.status-card.connected {
    border-color: #00a32a;
    background: #f6fff6;
}

.status-card.disconnected {
    border-color: #d63638;
    background: #fff6f6;
}

.status-icon {
    font-size: 24px;
}

.status-info {
    flex: 1;
}

.status-info h3 {
    margin: 0 0 5px 0;
    font-size: 18px;
}

.status-info p {
    margin: 0;
    color: #666;
}

.status-actions {
    display: flex;
    gap: 10px;
}

.settings-section {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.settings-section h2 {
    margin-top: 0;
    color: #243127;
    border-bottom: 1px solid #ddd;
    padding-bottom: 10px;
}

.setup-steps {
    margin-bottom: 30px;
}

.step {
    margin-bottom: 20px;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 4px;
}

.step h4 {
    margin-top: 0;
    color: #243127;
}

.step ol {
    margin-bottom: 0;
}

.redirect-uri {
    background: #f1f1f1;
    padding: 5px 8px;
    border-radius: 3px;
    font-family: monospace;
    font-size: 13px;
}

.auth-section {
    text-align: center;
    padding: 30px;
    background: #f9f9f9;
    border-radius: 4px;
}

.auth-section.connected {
    background: #f6fff6;
}

.sync-explanation {
    margin-bottom: 30px;
}

.sync-flow {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-top: 15px;
}

.sync-direction {
    padding: 15px;
    background: #f9f9f9;
    border-radius: 4px;
}

.sync-direction h5 {
    margin-top: 0;
    color: #243127;
}

.sync-direction ul {
    margin-bottom: 0;
    font-size: 14px;
}

.sync-stats {
    margin-top: 20px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-top: 15px;
}

.stat-item {
    text-align: center;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 4px;
}

.stat-number {
    font-size: 24px;
    font-weight: bold;
    color: #243127;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 14px;
    color: #666;
}

.requirements-check {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.requirement-item {
    padding: 15px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.requirement-item.ok {
    background: #f6fff6;
    border: 1px solid #00a32a;
}

.requirement-item.error {
    background: #fff6f6;
    border: 1px solid #d63638;
}

.requirement-item strong {
    flex: 1;
}

.requirement-item p {
    margin: 5px 0 0 0;
    font-size: 12px;
    color: #666;
}

@media (max-width: 768px) {
    .sync-flow {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .requirements-check {
        grid-template-columns: 1fr;
    }
    
    .status-card {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('<?php _e('URI copiée dans le presse-papiers', 'restaurant-booking'); ?>');
    });
}

function revokeAccess() {
    if (confirm('<?php _e('Êtes-vous sûr de vouloir révoquer l\'accès à Google Calendar ?', 'restaurant-booking'); ?>')) {
        // TODO: Implémenter la révocation
        alert('<?php _e('Fonctionnalité en cours de développement', 'restaurant-booking'); ?>');
    }
}
</script>
