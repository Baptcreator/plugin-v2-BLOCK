<?php
/**
 * Gestionnaire unifié des catégories et produits
 * 
 * Cette classe centralise la gestion de toutes les catégories :
 * - Plats signature, Accompagnements, Buffets
 * - Boissons (Soft, Vins, Bières, Fûts)
 * - Jeux (pour étape 6 remorque)
 * - Sous-catégories (types de bières, vins)
 * 
 * @package RestaurantBooking
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Categories_Manager {
    
    /**
     * Rediriger les anciens IDs de catégories vers les bonnes pages
     */
    private function redirect_legacy_category_id($category_id, $action) {
        global $wpdb;
        
        // CORRECTION : Redirection directe selon les IDs de l'analyse de la base de données
        switch (intval($category_id)) {
            case 100: // Plats Signature DOG
                wp_redirect(admin_url('admin.php?page=restaurant-booking-products-dog'));
                break;
            case 101: // Plats Signature CROQ
                wp_redirect(admin_url('admin.php?page=restaurant-booking-products-croq'));
                break;
            case 102: // Menu Enfant (Mini Boss)
                wp_redirect(admin_url('admin.php?page=restaurant-booking-products-mini-boss'));
                break;
            case 103: // Accompagnements
                wp_redirect(admin_url('admin.php?page=restaurant-booking-products-accompaniments'));
                break;
            case 104: // Buffet Salé
                wp_redirect(admin_url('admin.php?page=restaurant-booking-products-buffet-sale'));
                break;
            case 105: // Buffet Sucré
                wp_redirect(admin_url('admin.php?page=restaurant-booking-products-buffet-sucre'));
                break;
            case 106: // Boissons Soft
                wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-soft'));
                break;
            case 109: // Bières Bouteilles
                wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-beers'));
                break;
            case 110: // Fûts de Bière
                wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-kegs'));
                break;
            case 111: // Jeux et Animations
                wp_redirect(admin_url('admin.php?page=restaurant-booking-games'));
                break;
            case 112: // Vins
                wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-wines'));
                break;
            default:
                // Fallback vers l'ancienne méthode pour les IDs non reconnus
                $category = $wpdb->get_row($wpdb->prepare(
                    "SELECT type, name FROM {$wpdb->prefix}restaurant_categories WHERE id = %d",
                    $category_id
                ));
                
                if (!$category) {
                    wp_die(__('Catégorie introuvable.', 'restaurant-booking'));
                }
                
                // Rediriger vers la vue principale si type inconnu
                wp_redirect(admin_url('admin.php?page=restaurant-booking-categories-manager'));
                break;
        }
        exit;
    }
    
    /**
     * Afficher la page principale de gestion des catégories
     */
    public function display_main_page() {
        // Vérifier les permissions
        if (!current_user_can('manage_restaurant_quotes')) {
            wp_die(__('Vous n\'avez pas les permissions suffisantes pour accéder à cette page.', 'restaurant-booking'));
        }

        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $category_id = isset($_GET['category_id']) ? sanitize_text_field($_GET['category_id']) : '';
        
        // Si c'est un ID numérique, rediriger vers la page produit appropriée
        if (is_numeric($category_id) && $category_id > 0) {
            $this->redirect_legacy_category_id(intval($category_id), $action);
            return;
        }
        
        switch ($action) {
            case 'add':
                $this->display_category_form();
                break;
            case 'edit':
                $this->display_category_form($category_id);
                break;
            case 'products':
                $this->display_products_page($category_id);
                break;
            case 'subcategories':
                $this->display_subcategories_page($category_id);
                break;
            case 'options':
                $this->display_options_page($category_id);
                break;
            default:
                $this->display_categories_overview();
                break;
        }
    }
    
    /**
     * Vue d'ensemble de toutes les catégories
     */
    private function display_categories_overview() {
        $categories = $this->get_all_categories_with_stats();
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">📂 <?php _e('Gestion des Catégories & Produits', 'restaurant-booking'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-categories-manager&action=add'); ?>" class="page-title-action">
                <?php _e('Nouvelle Catégorie', 'restaurant-booking'); ?>
            </a>
            <hr class="wp-header-end">

            <!-- Statistiques globales -->
            <div class="restaurant-booking-dashboard-stats">
                <div class="stats-grid">
                    <?php 
                    $total_categories = count($categories);
                    $total_products = array_sum(array_column($categories, 'product_count'));
                    $total_options = array_sum(array_column($categories, 'options_count'));
                    ?>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $total_categories; ?></div>
                        <div class="stat-label"><?php _e('Catégories actives', 'restaurant-booking'); ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $total_products; ?></div>
                        <div class="stat-label"><?php _e('Produits total', 'restaurant-booking'); ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $total_options; ?></div>
                        <div class="stat-label"><?php _e('Options & Suppléments', 'restaurant-booking'); ?></div>
                    </div>
                </div>
            </div>

            <!-- Catégories par service -->
            <div class="restaurant-booking-categories-sections">
                
                <!-- Catégories Restaurant & Remorque -->
                <div class="categories-section">
                    <h2>🍽️ <?php _e('Catégories Principales (Restaurant & Remorque)', 'restaurant-booking'); ?></h2>
                    <div class="categories-grid">
                        <?php foreach ($categories as $category) : 
                            if ($category['service_type'] === 'both') :
                                $this->render_category_card($category);
                            endif;
                        endforeach; ?>
                    </div>
                </div>

                <!-- Catégories spécifiques Remorque -->
                <?php 
                $remorque_categories = array_filter($categories, function($cat) {
                    return $cat['service_type'] === 'remorque';
                });
                if (!empty($remorque_categories)) : ?>
                <div class="categories-section">
                    <h2>🚛 <?php _e('Catégories Remorque uniquement', 'restaurant-booking'); ?></h2>
                    <div class="categories-grid">
                        <?php foreach ($remorque_categories as $category) : 
                            $this->render_category_card($category);
                        endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Boissons avec sous-types -->
                <div class="categories-section">
                    <h2>🍷 <?php _e('Gestion des Boissons & Sous-types', 'restaurant-booking'); ?></h2>
                    <div class="beverages-management">
                        <?php $this->render_beverages_section($categories); ?>
                    </div>
                </div>
            </div>
        </div>

        <style>
        .restaurant-booking-dashboard-stats {
            margin: 20px 0;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 1px 1px rgba(0,0,0,0.04);
        }
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #0073aa;
            line-height: 1;
        }
        .stat-label {
            color: #646970;
            margin-top: 8px;
            font-size: 14px;
        }
        .categories-section {
            margin-bottom: 40px;
        }
        .categories-section h2 {
            border-bottom: 2px solid #0073aa;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }
        .category-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .category-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .category-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        .category-title {
            font-size: 1.2em;
            font-weight: 600;
            margin: 0;
        }
        .category-badge {
            background: #0073aa;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            text-transform: uppercase;
        }
        .category-stats {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 14px;
            color: #646970;
        }
        .category-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .category-actions .button {
            font-size: 12px;
            padding: 4px 8px;
            height: auto;
        }
        .beverages-management {
            background: #f8f9fa;
            border: 1px solid #e1e5e9;
            border-radius: 8px;
            padding: 20px;
        }
        </style>
        <?php
    }
    
    /**
     * Rendre une carte de catégorie
     */
    private function render_category_card($category) {
        $icon = $this->get_category_icon($category['type']);
        $has_subcategories = isset($category['is_grouped']) || in_array($category['type'], ['biere_bouteille', 'fut', 'vin_blanc', 'vin_rouge']);
        $has_options = $category['options_count'] > 0;
        $is_grouped = isset($category['is_grouped']) && $category['is_grouped'];
        
        ?>
        <div class="category-card">
            <div class="category-header">
                <h3 class="category-title">
                    <?php echo $icon; ?> <?php echo esc_html($category['name']); ?>
                </h3>
                <span class="category-badge"><?php echo esc_html($category['service_type']); ?></span>
            </div>
            
            <div class="category-stats">
                <span><strong><?php echo $category['product_count']; ?></strong> produits</span>
                <?php if ($has_options) : ?>
                    <span><strong><?php echo $category['options_count']; ?></strong> options</span>
                <?php endif; ?>
                <?php if ($has_subcategories) : ?>
                    <span><strong><?php echo $this->get_subcategories_count($category['id']); ?></strong> sous-types</span>
                <?php endif; ?>
            </div>
            
            <div class="category-actions">
                <?php if ($is_grouped) : ?>
                    <!-- Actions pour les groupes (Vins, Bières) -->
                    <?php if ($category['type'] === 'wines_group') : ?>
                        <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-wines'); ?>" 
                           class="button button-primary">
                            📦 <?php _e('Gérer les Vins', 'restaurant-booking'); ?> (<?php echo $category['product_count']; ?>)
                        </a>
                    <?php elseif ($category['type'] === 'beers_group') : ?>
                        <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-beers'); ?>" 
                           class="button button-primary">
                            🍺 <?php _e('Bières Bouteilles', 'restaurant-booking'); ?>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-kegs'); ?>" 
                           class="button button-primary">
                            🍺 <?php _e('Fûts de Bière', 'restaurant-booking'); ?>
                        </a>
                    <?php endif; ?>
                    
                    <a href="<?php echo admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=' . $category['id']); ?>" 
                       class="button button-secondary">
                        🏷️ <?php _e('Sous-types', 'restaurant-booking'); ?>
                    </a>
                <?php else : ?>
                    <!-- Actions pour les catégories individuelles -->
                    <a href="<?php echo admin_url('admin.php?page=restaurant-booking-categories-manager&action=edit&category_id=' . $category['id']); ?>" 
                       class="button button-secondary">
                        ✏️ <?php _e('Modifier', 'restaurant-booking'); ?>
                    </a>
                    
                    <a href="<?php echo admin_url('admin.php?page=restaurant-booking-categories-manager&action=products&category_id=' . $category['id']); ?>" 
                       class="button button-primary">
                        📦 <?php _e('Produits', 'restaurant-booking'); ?> (<?php echo $category['product_count']; ?>)
                    </a>
                    
                    <?php if ($has_subcategories) : ?>
                        <a href="<?php echo admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=' . $category['id']); ?>" 
                           class="button button-secondary">
                            🏷️ <?php _e('Sous-types', 'restaurant-booking'); ?>
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($has_options || $category['type'] === 'accompagnement') : ?>
                        <a href="<?php echo admin_url('admin.php?page=restaurant-booking-categories-manager&action=options&category_id=' . $category['id']); ?>" 
                           class="button button-secondary">
                            ⚙️ <?php _e('Options', 'restaurant-booking'); ?>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Rendre la section des boissons
     */
    private function render_beverages_section($categories) {
        global $wpdb;
        
        // CORRECTION : Récupérer les boissons depuis la base de données réelle selon l'analyse
        $display_categories = [];
        
        // 1. Boissons Soft (ID 106)
        $soft_count = $wpdb->get_var("
            SELECT COUNT(p.id) 
            FROM {$wpdb->prefix}restaurant_products p
            INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
            WHERE c.type = 'soft' AND p.is_active = 1 AND c.is_active = 1
        ");
        
        if ($soft_count > 0) {
            $display_categories[] = [
                'name' => 'Boissons Soft',
                'icon' => '🥤',
                'product_count' => $soft_count,
                'admin_page' => 'restaurant-booking-beverages-soft',
                'has_subtypes' => false
            ];
        }
        
        // 2. Vins (ID 112 selon l'analyse)
        $wine_count = $wpdb->get_var("
            SELECT COUNT(p.id) 
            FROM {$wpdb->prefix}restaurant_products p
            INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
            WHERE c.id = 112 AND p.is_active = 1 AND c.is_active = 1
        ");
        
        if ($wine_count > 0) {
            // Récupérer les types de vins depuis wp_restaurant_wine_types
            $wine_types_table = $wpdb->prefix . 'restaurant_wine_types';
            $wine_types = $wpdb->get_results("
                SELECT wt.name, COUNT(p.id) as count
                FROM $wine_types_table wt
                LEFT JOIN {$wpdb->prefix}restaurant_products p ON wt.slug = p.wine_category AND p.is_active = 1
                WHERE wt.is_active = 1
                GROUP BY wt.id
                ORDER BY wt.display_order ASC
            ");
            
            $wine_types_list = [];
            foreach ($wine_types as $type) {
                if ($type->count > 0) {
                    $wine_types_list[] = $type->name . ' (' . $type->count . ')';
                }
            }
            
            $display_categories[] = [
                'name' => 'Vins',
                'icon' => '🍷',
                'product_count' => $wine_count,
                'admin_page' => 'restaurant-booking-beverages-wines',
                'has_subtypes' => true,
                'subtypes_page' => 'restaurant-booking-categories-manager&action=subcategories&category_id=wines_group',
                'subtypes_list' => implode(', ', $wine_types_list)
            ];
        }
        
        // 3. Bières Bouteilles (ID 109)
        $beer_bottles_count = $wpdb->get_var("
            SELECT COUNT(p.id) 
            FROM {$wpdb->prefix}restaurant_products p
            INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
            WHERE c.id = 109 AND p.is_active = 1 AND c.is_active = 1
        ");
        
        if ($beer_bottles_count > 0) {
            // Récupérer les types de bières depuis wp_restaurant_beer_types
            $beer_types_table = $wpdb->prefix . 'restaurant_beer_types';
            $beer_types = $wpdb->get_results("
                SELECT bt.name, 
                       COUNT(CASE WHEN c.id = 109 THEN p.id END) as bottle_count
                FROM $beer_types_table bt
                LEFT JOIN {$wpdb->prefix}restaurant_products p ON bt.slug = p.beer_category AND p.is_active = 1
                LEFT JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
                WHERE bt.is_active = 1
                GROUP BY bt.id
                ORDER BY bt.display_order ASC
            ");
            
            $beer_types_list = [];
            foreach ($beer_types as $type) {
                if ($type->bottle_count > 0) {
                    $beer_types_list[] = $type->name . ' (' . $type->bottle_count . ')';
                }
            }
            
            $display_categories[] = [
                'name' => 'Bières Bouteilles',
                'icon' => '🍺',
                'product_count' => $beer_bottles_count,
                'admin_page' => 'restaurant-booking-beverages-beers',
                'has_subtypes' => true,
                'subtypes_page' => 'restaurant-booking-categories-manager&action=subcategories&category_id=beers_group',
                'subtypes_list' => implode(', ', $beer_types_list)
            ];
        }
        
        // 4. Fûts de Bière (ID 110)
        $kegs_count = $wpdb->get_var("
            SELECT COUNT(p.id) 
            FROM {$wpdb->prefix}restaurant_products p
            INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
            WHERE c.id = 110 AND p.is_active = 1 AND c.is_active = 1
        ");
        
        if ($kegs_count > 0) {
            // Types de bières pour fûts (même table que bouteilles)
            $keg_types = $wpdb->get_results("
                SELECT bt.name, 
                       COUNT(CASE WHEN c.id = 110 THEN p.id END) as keg_count
                FROM {$wpdb->prefix}restaurant_beer_types bt
                LEFT JOIN {$wpdb->prefix}restaurant_products p ON bt.slug = p.beer_category AND p.is_active = 1
                LEFT JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
                WHERE bt.is_active = 1
                GROUP BY bt.id
                ORDER BY bt.display_order ASC
            ");
            
            $keg_types_list = [];
            foreach ($keg_types as $type) {
                if ($type->keg_count > 0) {
                    $keg_types_list[] = $type->name . ' (' . $type->keg_count . ')';
                }
            }
            
            $display_categories[] = [
                'name' => 'Fûts de Bière',
                'icon' => '🍺',
                'product_count' => $kegs_count,
                'admin_page' => 'restaurant-booking-beverages-kegs',
                'has_subtypes' => true,
                'subtypes_page' => 'restaurant-booking-categories-manager&action=subcategories&category_id=beers_group',
                'subtypes_list' => implode(', ', $keg_types_list)
            ];
        }
        
        ?>
        <div class="beverages-grid">
            <?php if (empty($display_categories)) : ?>
                <div class="no-beverages">
                    <p><?php _e('Aucune catégorie de boissons trouvée.', 'restaurant-booking'); ?></p>
                </div>
            <?php else : ?>
                <?php foreach ($display_categories as $category) : ?>
                    <div class="beverage-category">
                        <h4><?php echo $category['icon']; ?> <?php echo esc_html($category['name']); ?></h4>
                        <div class="beverage-stats">
                            <span><strong><?php echo $category['product_count']; ?></strong> produits</span>
                            <?php if ($category['has_subtypes'] && !empty($category['subtypes_list'])) : ?>
                                <span class="subtypes-info"><?php echo esc_html($category['subtypes_list']); ?></span>
                            <?php elseif ($category['has_subtypes']) : ?>
                                <span class="subtypes-info">Types disponibles</span>
                            <?php endif; ?>
                        </div>
                        <div class="beverage-actions">
                            <a href="<?php echo admin_url('admin.php?page=' . $category['admin_page']); ?>" 
                               class="button button-primary">Gérer</a>
                            <?php if ($category['has_subtypes']) : ?>
                                <a href="<?php echo admin_url('admin.php?page=' . $category['subtypes_page']); ?>" 
                                   class="button button-secondary">Sous-types</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <style>
        .beverages-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 15px;
        }
        .beverage-category {
            background: white;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 15px;
        }
        .beverage-category h4 {
            margin: 0 0 10px 0;
            font-size: 16px;
        }
        .beverage-stats {
            font-size: 13px;
            color: #646970;
            margin-bottom: 10px;
        }
        .beverage-stats span {
            display: block;
            margin-bottom: 3px;
        }
        .subtypes-info {
            display: block;
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            font-style: italic;
        }
        .no-beverages {
            grid-column: 1 / -1;
            text-align: center;
            padding: 20px;
            color: #666;
        }
        </style>
        <?php
    }
    
    /**
     * Récupérer toutes les catégories avec statistiques et regroupement logique
     */
    private function get_all_categories_with_stats() {
        global $wpdb;
        
        $raw_categories = $wpdb->get_results("
            SELECT 
                c.*,
                COUNT(p.id) as product_count,
                (
                    SELECT COUNT(ao.id) 
                    FROM {$wpdb->prefix}restaurant_accompaniment_options ao
                    INNER JOIN {$wpdb->prefix}restaurant_products p2 ON ao.product_id = p2.id
                    WHERE p2.category_id = c.id AND ao.is_active = 1
                ) +
                (
                    SELECT COUNT(s.id)
                    FROM {$wpdb->prefix}restaurant_product_supplements_v2 s
                    INNER JOIN {$wpdb->prefix}restaurant_products p3 ON s.product_id = p3.id
                    WHERE p3.category_id = c.id AND s.is_active = 1
                ) as options_count
            FROM {$wpdb->prefix}restaurant_categories c
            LEFT JOIN {$wpdb->prefix}restaurant_products p ON c.id = p.category_id AND p.is_active = 1
            WHERE c.is_active = 1
            GROUP BY c.id
            ORDER BY c.display_order ASC, c.name ASC
        ", ARRAY_A);
        
        // CORRECTION : Filtrer les catégories de boissons qui ne doivent pas apparaître dans les catégories principales
        $categories = array();
        
        foreach ($raw_categories as $category) {
            // EXCLURE les catégories de boissons des catégories principales
            // Selon l'analyse de la DB : ID 106 (Soft), 109 (Bières), 110 (Fûts), 112 (Vins)
            if (in_array($category['id'], [106, 109, 110, 112]) || 
                in_array($category['type'], ['soft', 'vin', 'vin_blanc', 'vin_rouge', 'vin_rose', 'cremant', 'biere_bouteille', 'fut'])) {
                continue; // Ne pas ajouter aux catégories principales
            }
            
            // Ajouter les autres catégories (plats, buffets, accompagnements, jeux)
            $categories[] = $category;
        }
        
        return $categories;
    }
    
    /**
     * Obtenir le nombre de sous-catégories
     */
    private function get_subcategories_count($category_id) {
        global $wpdb;
        
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}restaurant_subcategories'");
        if (!$table_exists) {
            return 0;
        }
        
        return $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}restaurant_subcategories 
            WHERE parent_category_id = %d AND is_active = 1
        ", $category_id));
    }
    
    /**
     * Obtenir l'icône d'une catégorie
     */
    private function get_category_icon($type) {
        $icons = array(
            'plat_signature_dog' => '🌭',
            'plat_signature_croq' => '🥪',
            'mini_boss' => '👶',
            'accompagnement' => '🥗',
            'buffet_sale' => '🧀',
            'buffet_sucre' => '🧁',
            'soft' => '🥤',
            'vin_blanc' => '🥂',
            'vin_rouge' => '🍷',
            'biere_bouteille' => '🍺',
            'fut' => '🍺',
            'jeux' => '🎮',
            'jeu' => '🎮',
            // Groupes
            'wines_group' => '🍷',
            'beers_group' => '🍺'
        );
        
        return $icons[$type] ?? '📦';
    }
    
    /**
     * Afficher la page des produits d'une catégorie
     */
    private function display_products_page($category_id) {
        // CORRECTION : Gérer les IDs numériques selon l'analyse de la base de données
        if (is_numeric($category_id)) {
            $category_id = intval($category_id);
            
            // Redirection selon les IDs réels de la base de données
            switch ($category_id) {
                case 106: // Boissons Soft
                    wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-soft'));
                    break;
                case 109: // Bières Bouteilles
                    wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-beers'));
                    break;
                case 110: // Fûts de Bière
                    wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-kegs'));
                    break;
                case 111: // Jeux et Animations
                    wp_redirect(admin_url('admin.php?page=restaurant-booking-games'));
                    break;
                case 112: // Vins
                    wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-wines'));
                    break;
                case 100: // Plats Signature DOG
                    wp_redirect(admin_url('admin.php?page=restaurant-booking-products-dog'));
                    break;
                case 101: // Plats Signature CROQ
                    wp_redirect(admin_url('admin.php?page=restaurant-booking-products-croq'));
                    break;
                case 102: // Menu Enfant (Mini Boss)
                    wp_redirect(admin_url('admin.php?page=restaurant-booking-products-mini-boss'));
                    break;
                case 103: // Accompagnements
                    wp_redirect(admin_url('admin.php?page=restaurant-booking-products-accompaniments'));
                    break;
                case 104: // Buffet Salé
                    wp_redirect(admin_url('admin.php?page=restaurant-booking-products-buffet-sale'));
                    break;
                case 105: // Buffet Sucré
                    wp_redirect(admin_url('admin.php?page=restaurant-booking-products-buffet-sucre'));
                    break;
                default:
                    wp_redirect(admin_url('admin.php?page=restaurant-booking-products'));
                    break;
            }
            exit;
        }
        
        // Fallback pour les anciennes méthodes
        $category = $this->get_category($category_id);
        if (!$category) {
            wp_die(__('Catégorie introuvable', 'restaurant-booking'));
        }
        
        // Rediriger vers l'admin spécialisé selon le type
        switch ($category['type']) {
            case 'biere_bouteille':
                wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-beers'));
                break;
            case 'fut':
                wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-kegs'));
                break;
            case 'soft':
                wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-soft'));
                break;
            case 'vin_blanc':
            case 'vin_rouge':
            case 'vin':
                wp_redirect(admin_url('admin.php?page=restaurant-booking-beverages-wines'));
                break;
            case 'accompagnement':
                wp_redirect(admin_url('admin.php?page=restaurant-booking-products-accompaniments'));
                break;
            case 'jeux':
                wp_redirect(admin_url('admin.php?page=restaurant-booking-games'));
                break;
            default:
                // Pour les autres types, utiliser l'admin générique
                $admin_page = $this->get_admin_page_for_type($category['type']);
                if ($admin_page) {
                    wp_redirect(admin_url('admin.php?page=' . $admin_page));
                } else {
                    wp_redirect(admin_url('admin.php?page=restaurant-booking-products'));
                }
                break;
        }
        exit;
    }
    
    /**
     * Obtenir une catégorie par ID
     */
    private function get_category($category_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}restaurant_categories 
            WHERE id = %d
        ", $category_id), ARRAY_A);
    }
    
    /**
     * Obtenir la page admin pour un type de catégorie
     */
    private function get_admin_page_for_type($type) {
        $pages = array(
            'plat_signature_dog' => 'restaurant-booking-products-dog',
            'plat_signature_croq' => 'restaurant-booking-products-croq',
            'mini_boss' => 'restaurant-booking-products-mini-boss',
            'buffet_sale' => 'restaurant-booking-products-buffet-sale',
            'buffet_sucre' => 'restaurant-booking-products-buffet-sucre'
        );
        
        return $pages[$type] ?? null;
    }
    
    /**
     * Afficher le formulaire de catégorie
     */
    private function display_category_form($category_id = 0) {
        // Cette méthode sera implémentée pour créer/modifier les catégories
        echo '<div class="wrap"><h1>Formulaire de catégorie (à implémenter)</h1></div>';
    }
    
    /**
     * Afficher la page des sous-catégories
     */
    private function display_subcategories_page($category_id) {
        // Gérer les groupes spéciaux
        if ($category_id === 'wines_group') {
            $this->display_wine_subcategories();
            return;
        } elseif ($category_id === 'beers_group') {
            $this->display_beer_subcategories();
            return;
        }
        
        // Catégorie individuelle
        echo '<div class="wrap"><h1>Gestion des sous-catégories (à implémenter pour catégorie ' . $category_id . ')</h1></div>';
    }
    
    /**
     * Afficher les sous-catégories de vins
     */
    private function display_wine_subcategories() {
        // Gérer les actions de gestion des types
        $action = isset($_GET['type_action']) ? sanitize_text_field($_GET['type_action']) : 'list';
        
        if ($action === 'add' || $action === 'edit') {
            $this->display_wine_type_form($action);
            return;
        }
        
        // Traitement des actions POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handle_wine_type_action();
            return;
        }
        
        // Traitement de la suppression (GET avec nonce)
        if ($action === 'delete') {
            $this->handle_wine_type_deletion();
            return;
        }
        
        global $wpdb;
        
        // CORRECTION : Récupérer tous les types de vins depuis la nouvelle table wp_restaurant_wine_types
        $wine_types_table = $wpdb->prefix . 'restaurant_wine_types';
        
        // Vérifier si la nouvelle table existe
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$wine_types_table'");
        
        if ($table_exists) {
            // DEBUG : Vérifier le contenu de la table
            $debug_count = $wpdb->get_var("SELECT COUNT(*) FROM $wine_types_table WHERE is_active = 1");
            
            // CORRECTION : Simplifier la requête pour d'abord récupérer tous les types
            $existing_types = $wpdb->get_results("
                SELECT 
                    wt.slug as type_key,
                    wt.name as type_name,
                    0 as product_count
                FROM $wine_types_table wt
                WHERE wt.is_active = 1
                ORDER BY wt.display_order ASC, wt.name ASC
            ");
            
            // Ensuite calculer les produits pour chaque type
            foreach ($existing_types as &$type) {
                $product_count = $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*) 
                    FROM {$wpdb->prefix}restaurant_products p
                    INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
                    WHERE c.id = 112 AND p.is_active = 1 AND p.wine_category = %s
                ", $type->type_key));
                $type->product_count = intval($product_count);
            }
            
            // DEBUG : Afficher les erreurs SQL s'il y en a
            if ($wpdb->last_error) {
                echo '<div class="notice notice-error"><p>Erreur SQL : ' . $wpdb->last_error . '</p></div>';
            }
            
            // DEBUG : Afficher le nombre de types trouvés
            if (current_user_can('manage_options')) {
                echo '<div class="notice notice-info"><p>DEBUG : ' . $debug_count . ' types dans la table, ' . count($existing_types) . ' types récupérés par la requête</p></div>';
            }
        } else {
            // Fallback vers l'ancienne méthode si la nouvelle table n'existe pas encore
            $subcategories_table = $wpdb->prefix . 'restaurant_subcategories';
            $wine_category_id = 112; // ID fixe selon l'analyse de la base de données
            
            $existing_types = $wpdb->get_results($wpdb->prepare("
                SELECT 
                    s.subcategory_key as type_key,
                    s.subcategory_name as type_name,
                    COALESCE(wine_count.count, 0) as product_count
                FROM $subcategories_table s
                LEFT JOIN (
                    SELECT wine_category, COUNT(*) as count
                    FROM {$wpdb->prefix}restaurant_products p
                    INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
                    WHERE c.id = 112 AND p.is_active = 1 AND p.wine_category IS NOT NULL
                    GROUP BY wine_category
                ) wine_count ON s.subcategory_key = wine_count.wine_category
                WHERE s.parent_category_id = %d AND s.is_active = 1
                ORDER BY s.display_order ASC, s.subcategory_name ASC
            ", $wine_category_id));
        }
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">🍷 <?php _e('Gestion des Types de Vins', 'restaurant-booking'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=wines_group&type_action=add'); ?>" 
               class="page-title-action">
                <?php _e('Ajouter un Type', 'restaurant-booking'); ?>
            </a>
            <hr class="wp-header-end">
            
            <div class="restaurant-booking-info-card">
                <h3><?php _e('Informations', 'restaurant-booking'); ?></h3>
                <p><?php _e('Gérez les différents types de vins. Ces types seront disponibles dans les formulaires de création de produits et dans le formulaire de devis.', 'restaurant-booking'); ?></p>
                <ul>
                    <li><?php _e('✓ Types par couleur : Blanc, Rouge, Rosé, Champagne', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Types par cépage : Chardonnay, Muscat, Pinot Noir, Merlot', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Types par région : Bordeaux, Bourgogne, Loire', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Types par style : Sec, Demi-sec, Moelleux', 'restaurant-booking'); ?></li>
                </ul>
            </div>
            
            <?php if (empty($existing_types)) : ?>
                <div class="notice notice-info">
                    <p><?php _e('Aucun type de vin trouvé. Commencez par ajouter des types pour organiser vos vins.', 'restaurant-booking'); ?></p>
                </div>
            <?php else : ?>
                
                <div class="wine-types-table-container">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th scope="col" class="manage-column"><?php _e('Type de Vin', 'restaurant-booking'); ?></th>
                                <th scope="col" class="manage-column"><?php _e('Produits', 'restaurant-booking'); ?></th>
                                <th scope="col" class="manage-column"><?php _e('Actions', 'restaurant-booking'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($existing_types as $type) : ?>
                                <tr>
                                    <td><strong><?php echo esc_html(ucfirst($type->type_name)); ?></strong></td>
                                    <td>
                                        <span class="count"><?php echo $type->product_count; ?></span>
                                        <?php if ($type->product_count > 0) : ?>
                                            <span class="description"><?php _e('produits', 'restaurant-booking'); ?></span>
                                            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-wines&filter_type=' . urlencode($type->type_key)); ?>" 
                                               class="button button-small">
                                                <?php _e('Voir', 'restaurant-booking'); ?>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=wines_group&type_action=edit&type_key=' . urlencode($type->type_key)); ?>" 
                                           class="button button-secondary">
                                            ✏️ <?php _e('Modifier', 'restaurant-booking'); ?>
                                        </a>
                                        
                                        <?php if ($type->product_count == 0) : ?>
                                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=wines_group&type_action=delete&type_key=' . urlencode($type->type_key)), 'delete_wine_type_' . $type->type_key); ?>" 
                                               class="button button-link-delete"
                                               onclick="return confirm('<?php _e('Êtes-vous sûr de vouloir supprimer ce type ?', 'restaurant-booking'); ?>')">
                                                🗑️ <?php _e('Supprimer', 'restaurant-booking'); ?>
                                            </a>
                                        <?php else : ?>
                                            <span class="description"><?php _e('Utilisé par des produits', 'restaurant-booking'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
            <?php endif; ?>
            
            <style>
            .wine-types-table-container {
                margin-top: 20px;
            }
            .wine-types-table-container .count {
                font-weight: bold;
                color: #0073aa;
            }
            .wine-types-table-container .description {
                font-size: 12px;
                color: #666;
                margin-left: 5px;
            }
            .wine-types-table-container .button-small {
                font-size: 11px;
                height: auto;
                padding: 2px 8px;
                margin-left: 8px;
            }
            </style>
        </div>
        <?php
    }
    
    /**
     * Afficher les sous-catégories de bières
     */
    private function display_beer_subcategories() {
        // Gérer les actions de gestion des types
        $action = isset($_GET['type_action']) ? sanitize_text_field($_GET['type_action']) : 'list';
        
        if ($action === 'add' || $action === 'edit') {
            $this->display_beer_type_form($action);
            return;
        }
        
        // Traitement des actions POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handle_beer_type_action();
            return;
        }
        
        // Traitement de la suppression (GET avec nonce)
        if ($action === 'delete') {
            $this->handle_beer_type_deletion();
            return;
        }
        
        global $wpdb;
        
        // CORRECTION : Récupérer tous les types de bières depuis la nouvelle table wp_restaurant_beer_types
        $beer_types_table = $wpdb->prefix . 'restaurant_beer_types';
        
        // Vérifier si la nouvelle table existe
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$beer_types_table'");
        
        if ($table_exists) {
            // DEBUG : Vérifier le contenu de la table
            $debug_count = $wpdb->get_var("SELECT COUNT(*) FROM $beer_types_table WHERE is_active = 1");
            
            // CORRECTION : Simplifier la requête pour d'abord récupérer tous les types
            $existing_types = $wpdb->get_results("
                SELECT 
                    bt.slug as type_key,
                    bt.name as type_name,
                    0 as product_count,
                    0 as bottle_count,
                    0 as keg_count
                FROM $beer_types_table bt
                WHERE bt.is_active = 1
                ORDER BY bt.display_order ASC, bt.name ASC
            ");
            
            // Ensuite calculer les produits pour chaque type
            foreach ($existing_types as &$type) {
                // Compter les bières bouteilles
                $bottle_count = $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*) 
                    FROM {$wpdb->prefix}restaurant_products p
                    INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
                    WHERE c.id = 109 AND p.is_active = 1 AND p.beer_category = %s
                ", $type->type_key));
                
                // Compter les fûts
                $keg_count = $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*) 
                    FROM {$wpdb->prefix}restaurant_products p
                    INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
                    WHERE c.id = 110 AND p.is_active = 1 AND p.beer_category = %s
                ", $type->type_key));
                
                $type->bottle_count = intval($bottle_count);
                $type->keg_count = intval($keg_count);
                $type->product_count = $type->bottle_count + $type->keg_count;
            }
            
            // DEBUG : Afficher les erreurs SQL s'il y en a
            if ($wpdb->last_error) {
                echo '<div class="notice notice-error"><p>Erreur SQL : ' . $wpdb->last_error . '</p></div>';
            }
            
            // DEBUG : Afficher le nombre de types trouvés
            if (current_user_can('manage_options')) {
                echo '<div class="notice notice-info"><p>DEBUG : ' . $debug_count . ' types de bières dans la table, ' . count($existing_types) . ' types récupérés par la requête</p></div>';
            }
        } else {
            // Fallback vers l'ancienne méthode si la nouvelle table n'existe pas encore
            $subcategories_table = $wpdb->prefix . 'restaurant_subcategories';
            $beer_category_id = 109; // ID fixe selon l'analyse de la base de données
            
            $existing_types = $wpdb->get_results($wpdb->prepare("
                SELECT 
                    s.subcategory_key as type_key,
                    s.subcategory_name as type_name,
                    COALESCE(bottle_count.count, 0) + COALESCE(keg_count.count, 0) as product_count,
                    COALESCE(bottle_count.count, 0) as bottle_count,
                    COALESCE(keg_count.count, 0) as keg_count
                FROM $subcategories_table s
                LEFT JOIN (
                    SELECT beer_category, COUNT(*) as count
                    FROM {$wpdb->prefix}restaurant_products p
                    INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
                    WHERE c.id = 109 AND p.is_active = 1 AND p.beer_category IS NOT NULL
                    GROUP BY beer_category
                ) bottle_count ON s.subcategory_key = bottle_count.beer_category
                LEFT JOIN (
                    SELECT beer_category, COUNT(*) as count
                    FROM {$wpdb->prefix}restaurant_products p
                    INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
                    WHERE c.id = 110 AND p.is_active = 1 AND p.beer_category IS NOT NULL
                    GROUP BY beer_category
                ) keg_count ON s.subcategory_key = keg_count.beer_category
                WHERE s.parent_category_id = %d AND s.is_active = 1
                ORDER BY s.display_order ASC, s.subcategory_name ASC
            ", $beer_category_id));
        }
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">🍺 <?php _e('Gestion des Types de Bières', 'restaurant-booking'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=beers_group&type_action=add'); ?>" 
               class="page-title-action">
                <?php _e('Ajouter un Type', 'restaurant-booking'); ?>
            </a>
            <hr class="wp-header-end">
            
            <div class="restaurant-booking-info-card">
                <h3><?php _e('Informations', 'restaurant-booking'); ?></h3>
                <p><?php _e('Gérez les différents types de bières partagés entre bouteilles et fûts. Ces types seront disponibles dans les formulaires de création de produits.', 'restaurant-booking'); ?></p>
                <ul>
                    <li><?php _e('✓ Ajoutez de nouveaux types (IPA, Stout, Porter, etc.)', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Modifiez les types existants', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Supprimez les types non utilisés', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Réorganisez l\'ordre d\'affichage', 'restaurant-booking'); ?></li>
                </ul>
            </div>
            
            <?php if (empty($existing_types)) : ?>
                <div class="notice notice-info">
                    <p><?php _e('Aucun type de bière trouvé. Commencez par ajouter des types pour organiser vos bières.', 'restaurant-booking'); ?></p>
                </div>
            <?php else : ?>
                
                <div class="beer-types-table-container">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th scope="col" class="manage-column"><?php _e('Type de Bière', 'restaurant-booking'); ?></th>
                                <th scope="col" class="manage-column"><?php _e('Produits Total', 'restaurant-booking'); ?></th>
                                <th scope="col" class="manage-column"><?php _e('Bières Bouteilles', 'restaurant-booking'); ?></th>
                                <th scope="col" class="manage-column"><?php _e('Fûts', 'restaurant-booking'); ?></th>
                                <th scope="col" class="manage-column"><?php _e('Actions', 'restaurant-booking'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($existing_types as $type) : ?>
                                <tr>
                                    <td><strong><?php echo esc_html(ucfirst($type->type_name)); ?></strong></td>
                                    <td>
                                        <span class="count"><?php echo $type->product_count; ?></span>
                                        <?php if ($type->product_count > 0) : ?>
                                            <span class="description"><?php _e('produits', 'restaurant-booking'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="count"><?php echo $type->bottle_count; ?></span>
                                        <?php if ($type->bottle_count > 0) : ?>
                                            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-beers&filter_type=' . urlencode($type->type_key)); ?>" 
                                               class="button button-small">
                                                <?php _e('Voir', 'restaurant-booking'); ?>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="count"><?php echo $type->keg_count; ?></span>
                                        <?php if ($type->keg_count > 0) : ?>
                                            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-beverages-kegs&filter_type=' . urlencode($type->type_key)); ?>" 
                                               class="button button-small">
                                                <?php _e('Voir', 'restaurant-booking'); ?>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=beers_group&type_action=edit&type_key=' . urlencode($type->type_key)); ?>" 
                                           class="button button-secondary">
                                            ✏️ <?php _e('Modifier', 'restaurant-booking'); ?>
                                        </a>
                                        
                                        <?php if ($type->product_count == 0) : ?>
                                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=beers_group&type_action=delete&type_key=' . urlencode($type->type_key)), 'delete_beer_type_' . $type->type_key); ?>" 
                                               class="button button-link-delete"
                                               onclick="return confirm('<?php _e('Êtes-vous sûr de vouloir supprimer ce type ?', 'restaurant-booking'); ?>')">
                                                🗑️ <?php _e('Supprimer', 'restaurant-booking'); ?>
                                            </a>
                                        <?php else : ?>
                                            <span class="description"><?php _e('Utilisé par des produits', 'restaurant-booking'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
            <?php endif; ?>
            
            <style>
            .beer-types-table-container {
                margin-top: 20px;
            }
            .beer-types-table-container .count {
                font-weight: bold;
                color: #0073aa;
            }
            .beer-types-table-container .description {
                font-size: 12px;
                color: #666;
                margin-left: 5px;
            }
            .beer-types-table-container .button-small {
                font-size: 11px;
                height: auto;
                padding: 2px 8px;
                margin-left: 8px;
            }
            </style>
        </div>
        <?php
    }
    
    /**
     * Afficher le formulaire d'ajout/modification d'un type de bière
     */
    private function display_beer_type_form($action) {
        $type_key = isset($_GET['type_key']) ? sanitize_text_field($_GET['type_key']) : '';
        $type_name = '';
        
        // Si mode édition, récupérer les données existantes
        if ($action === 'edit' && $type_key) {
            $type_name = ucfirst($type_key);
        }
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                🍺 <?php echo $action === 'edit' ? __('Modifier le Type de Bière', 'restaurant-booking') : __('Ajouter un Type de Bière', 'restaurant-booking'); ?>
            </h1>
            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=beers_group'); ?>" 
               class="page-title-action">
                ← <?php _e('Retour à la liste', 'restaurant-booking'); ?>
            </a>
            <hr class="wp-header-end">
            
            <div class="restaurant-booking-info-card">
                <h3><?php _e('Informations sur les types de bières', 'restaurant-booking'); ?></h3>
                <ul>
                    <li><?php _e('✓ Le nom sera utilisé pour organiser vos bières', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Utilisable pour les bouteilles et les fûts', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Apparaîtra dans les formulaires de création de produits', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Exemples : Blonde, IPA, Stout, Porter, Weizen, etc.', 'restaurant-booking'); ?></li>
                </ul>
            </div>
            
            <form method="post" action="" id="beer-type-form">
                <?php wp_nonce_field('save_beer_type', 'beer_type_nonce'); ?>
                <input type="hidden" name="action" value="<?php echo esc_attr($action); ?>">
                <input type="hidden" name="original_type_key" value="<?php echo esc_attr($type_key); ?>">
                
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="type_name"><?php _e('Nom du type', 'restaurant-booking'); ?> *</label>
                            </th>
                            <td>
                                <input type="text" id="type_name" name="type_name" class="regular-text" 
                                       value="<?php echo esc_attr($type_name); ?>" required>
                                <p class="description"><?php _e('Ex: IPA, Stout, Porter, Weizen, Triple, Saison...', 'restaurant-booking'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" class="button-primary" 
                           value="<?php echo $action === 'edit' ? __('Mettre à jour le type', 'restaurant-booking') : __('Ajouter le type', 'restaurant-booking'); ?>">
                    <a href="<?php echo admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=beers_group'); ?>" 
                       class="button button-secondary">
                        <?php _e('Annuler', 'restaurant-booking'); ?>
                    </a>
                </p>
            </form>
        </div>
        <?php
    }
    
    /**
     * Gérer les actions sur les types de bières (ajout/modification/suppression)
     */
    private function handle_beer_type_action() {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['beer_type_nonce'], 'save_beer_type')) {
            wp_die(__('Erreur de sécurité', 'restaurant-booking'));
        }
        
        $action = sanitize_text_field($_POST['action']);
        $type_name = sanitize_text_field($_POST['type_name']);
        $original_type_key = sanitize_text_field($_POST['original_type_key']);
        
        // Validation
        if (empty($type_name)) {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=beers_group&type_action=' . $action . '&error=empty_name'));
            exit;
        }
        
        // Créer la clé du type (minuscule, sans espaces)
        $type_key = strtolower(trim($type_name));
        $type_key = preg_replace('/[^a-z0-9-]/', '', str_replace(' ', '-', $type_key));
        
        global $wpdb;
        
        if ($action === 'add') {
            // CORRECTION : Vérifier si le type existe déjà dans la nouvelle table wp_restaurant_beer_types
            $beer_types_table = $wpdb->prefix . 'restaurant_beer_types';
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$beer_types_table'");
            
            if ($table_exists) {
                $existing = $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*) FROM $beer_types_table
                    WHERE slug = %s
                ", $type_key));
            } else {
                // Fallback vers l'ancienne table
                $subcategories_table = $wpdb->prefix . 'restaurant_subcategories';
                $existing = $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*) FROM $subcategories_table
                    WHERE subcategory_key = %s
                ", $type_key));
            }
            
            if ($existing > 0) {
                wp_redirect(admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=beers_group&type_action=add&error=already_exists'));
                exit;
            }
            
            // CORRECTION : Sauvegarder dans la nouvelle table wp_restaurant_beer_types
            $beer_types_table = $wpdb->prefix . 'restaurant_beer_types';
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$beer_types_table'");
            
            if ($table_exists) {
                // Utiliser la nouvelle table wp_restaurant_beer_types
                $max_order = $wpdb->get_var("SELECT MAX(display_order) FROM $beer_types_table");
                $display_order = ($max_order ?: 0) + 10;
                
                $inserted = $wpdb->insert($beer_types_table, array(
                    'name' => $type_name,
                    'slug' => $type_key,
                    'description' => '',
                    'display_order' => $display_order,
                    'is_active' => 1
                ));
                
                if ($inserted) {
                    wp_redirect(admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=beers_group&message=type_created&new_type=' . urlencode($type_key)));
                } else {
                    wp_redirect(admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=beers_group&type_action=add&error=save_failed'));
                }
            } else {
                // Fallback vers l'ancienne méthode si la nouvelle table n'existe pas encore
                $subcategories_table = $wpdb->prefix . 'restaurant_subcategories';
                $beer_category_id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}restaurant_categories WHERE type = 'biere_bouteille'");
                
                if ($beer_category_id) {
                    $max_order = $wpdb->get_var($wpdb->prepare("
                        SELECT MAX(display_order) FROM $subcategories_table 
                        WHERE parent_category_id = %d
                    ", $beer_category_id));
                    $display_order = ($max_order ?: 0) + 10;
                    
                    $inserted = $wpdb->insert($subcategories_table, array(
                        'parent_category_id' => $beer_category_id,
                        'subcategory_key' => $type_key,
                        'subcategory_name' => $type_name,
                        'subcategory_slug' => $type_key,
                        'display_order' => $display_order,
                        'is_active' => 1
                    ));
                    
                    if ($inserted) {
                        wp_redirect(admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=beers_group&message=type_created&new_type=' . urlencode($type_key)));
                    } else {
                        wp_redirect(admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=beers_group&type_action=add&error=save_failed'));
                    }
                } else {
                    wp_redirect(admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=beers_group&type_action=add&error=no_category'));
                }
            }
            exit;
            
        } elseif ($action === 'edit' && $original_type_key) {
            // CORRECTION : Mettre à jour dans la nouvelle table wp_restaurant_beer_types
            $beer_types_table = $wpdb->prefix . 'restaurant_beer_types';
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$beer_types_table'");
            
            if ($table_exists) {
                // Utiliser la nouvelle table wp_restaurant_beer_types
                $updated_types = $wpdb->update(
                    $beer_types_table,
                    array(
                        'name' => $type_name,
                        'slug' => $type_key
                    ),
                    array('slug' => $original_type_key),
                    array('%s', '%s'),
                    array('%s')
                );
                
                // Mettre à jour tous les produits qui utilisent ce type
                $updated_products = $wpdb->update(
                    $wpdb->prefix . 'restaurant_products',
                    array('beer_category' => $type_key),
                    array('beer_category' => $original_type_key),
                    array('%s'),
                    array('%s')
                );
                
                if ($updated_types !== false) {
                    wp_redirect(admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=beers_group&message=type_updated&updated_count=' . $updated_products));
                } else {
                    wp_redirect(admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=beers_group&error=update_failed'));
                }
            } else {
                // Fallback vers l'ancienne méthode
                $subcategories_table = $wpdb->prefix . 'restaurant_subcategories';
                $updated_subcategories = $wpdb->update(
                    $subcategories_table,
                    array(
                        'subcategory_key' => $type_key,
                        'subcategory_name' => $type_name,
                        'subcategory_slug' => $type_key
                    ),
                    array('subcategory_key' => $original_type_key),
                    array('%s', '%s', '%s'),
                    array('%s')
                );
                
                // Mettre à jour tous les produits qui utilisent ce type
                $updated_products = $wpdb->update(
                    $wpdb->prefix . 'restaurant_products',
                    array('beer_category' => $type_key),
                    array('beer_category' => $original_type_key),
                    array('%s'),
                    array('%s')
                );
                
                if ($updated_subcategories !== false) {
                    wp_redirect(admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=beers_group&message=type_updated&updated_count=' . $updated_products));
                } else {
                    wp_redirect(admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=beers_group&error=update_failed'));
                }
            }
            exit;
        }
    }
    
    /**
     * Afficher le formulaire d'ajout/modification d'un type de vin
     */
    private function display_wine_type_form($action) {
        $type_key = isset($_GET['type_key']) ? sanitize_text_field($_GET['type_key']) : '';
        $type_name = '';
        
        // Si mode édition, récupérer les données existantes
        if ($action === 'edit' && $type_key) {
            $type_name = ucfirst($type_key);
        }
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                🍷 <?php echo $action === 'edit' ? __('Modifier le Type de Vin', 'restaurant-booking') : __('Ajouter un Type de Vin', 'restaurant-booking'); ?>
            </h1>
            <a href="<?php echo admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=wines_group'); ?>" 
               class="page-title-action">
                ← <?php _e('Retour à la liste', 'restaurant-booking'); ?>
            </a>
            <hr class="wp-header-end">
            
            <div class="restaurant-booking-info-card">
                <h3><?php _e('Informations sur les types de vins', 'restaurant-booking'); ?></h3>
                <ul>
                    <li><?php _e('✓ Le nom sera utilisé pour organiser vos vins', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Apparaîtra dans les formulaires de création de produits', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Sera utilisé pour filtrer dans le formulaire de devis', 'restaurant-booking'); ?></li>
                    <li><?php _e('✓ Exemples : Blanc, Rouge, Chardonnay, Muscat, Bordeaux, etc.', 'restaurant-booking'); ?></li>
                </ul>
            </div>
            
            <form method="post" action="" id="wine-type-form">
                <?php wp_nonce_field('save_wine_type', 'wine_type_nonce'); ?>
                <input type="hidden" name="action" value="<?php echo esc_attr($action); ?>">
                <input type="hidden" name="original_type_key" value="<?php echo esc_attr($type_key); ?>">
                
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="type_name"><?php _e('Nom du type', 'restaurant-booking'); ?> *</label>
                            </th>
                            <td>
                                <input type="text" id="type_name" name="type_name" class="regular-text" 
                                       value="<?php echo esc_attr($type_name); ?>" required>
                                <p class="description"><?php _e('Ex: Blanc, Rouge, Chardonnay, Muscat, Bordeaux, Sec, Demi-sec...', 'restaurant-booking'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" class="button-primary" 
                           value="<?php echo $action === 'edit' ? __('Mettre à jour le type', 'restaurant-booking') : __('Ajouter le type', 'restaurant-booking'); ?>">
                    <a href="<?php echo admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=wines_group'); ?>" 
                       class="button button-secondary">
                        <?php _e('Annuler', 'restaurant-booking'); ?>
                    </a>
                </p>
            </form>
        </div>
        <?php
    }
    
    /**
     * Gérer les actions sur les types de vins (ajout/modification/suppression)
     */
    private function handle_wine_type_action() {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['wine_type_nonce'], 'save_wine_type')) {
            wp_die(__('Erreur de sécurité', 'restaurant-booking'));
        }
        
        $action = sanitize_text_field($_POST['action']);
        $type_name = sanitize_text_field($_POST['type_name']);
        $original_type_key = sanitize_text_field($_POST['original_type_key']);
        
        // Validation
        if (empty($type_name)) {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=wines_group&type_action=' . $action . '&error=empty_name'));
            exit;
        }
        
        // Créer la clé du type (minuscule, sans espaces)
        $type_key = strtolower(trim($type_name));
        $type_key = preg_replace('/[^a-z0-9-]/', '', str_replace(' ', '-', $type_key));
        
        global $wpdb;
        
        if ($action === 'add') {
            // CORRECTION : Vérifier si le type existe déjà dans la nouvelle table wp_restaurant_wine_types
            $wine_types_table = $wpdb->prefix . 'restaurant_wine_types';
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$wine_types_table'");
            
            if ($table_exists) {
                $existing = $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*) FROM $wine_types_table
                    WHERE slug = %s
                ", $type_key));
            } else {
                // Fallback vers l'ancienne table
                $subcategories_table = $wpdb->prefix . 'restaurant_subcategories';
                $existing = $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*) FROM $subcategories_table
                    WHERE subcategory_key = %s
                ", $type_key));
            }
            
            if ($existing > 0) {
                wp_redirect(admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=wines_group&type_action=add&error=already_exists'));
                exit;
            }
            
            // CORRECTION : Sauvegarder dans la nouvelle table wp_restaurant_wine_types
            $wine_types_table = $wpdb->prefix . 'restaurant_wine_types';
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$wine_types_table'");
            
            if ($table_exists) {
                // Utiliser la nouvelle table wp_restaurant_wine_types
                $max_order = $wpdb->get_var("SELECT MAX(display_order) FROM $wine_types_table");
                $display_order = ($max_order ?: 0) + 10;
                
                $inserted = $wpdb->insert($wine_types_table, array(
                    'name' => $type_name,
                    'slug' => $type_key,
                    'description' => '',
                    'display_order' => $display_order,
                    'is_active' => 1
                ));
                
                if ($inserted) {
                    wp_redirect(admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=wines_group&message=type_created&new_type=' . urlencode($type_key)));
                } else {
                    wp_redirect(admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=wines_group&type_action=add&error=save_failed'));
                }
            } else {
                // Fallback vers l'ancienne méthode si la nouvelle table n'existe pas encore
                $subcategories_table = $wpdb->prefix . 'restaurant_subcategories';
                $wine_category_id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}restaurant_categories WHERE type = 'vin'");
                
                if ($wine_category_id) {
                    $max_order = $wpdb->get_var($wpdb->prepare("
                        SELECT MAX(display_order) FROM $subcategories_table 
                        WHERE parent_category_id = %d
                    ", $wine_category_id));
                    $display_order = ($max_order ?: 0) + 10;
                    
                    $inserted = $wpdb->insert($subcategories_table, array(
                        'parent_category_id' => $wine_category_id,
                        'subcategory_key' => $type_key,
                        'subcategory_name' => $type_name,
                        'subcategory_slug' => $type_key,
                        'display_order' => $display_order,
                        'is_active' => 1
                    ));
                    
                    if ($inserted) {
                        wp_redirect(admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=wines_group&message=type_created&new_type=' . urlencode($type_key)));
                    } else {
                        wp_redirect(admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=wines_group&type_action=add&error=save_failed'));
                    }
                } else {
                    wp_redirect(admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=wines_group&type_action=add&error=no_category'));
                }
            }
            exit;
            
        } elseif ($action === 'edit' && $original_type_key) {
            // CORRECTION : Mettre à jour dans la nouvelle table wp_restaurant_wine_types
            $wine_types_table = $wpdb->prefix . 'restaurant_wine_types';
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$wine_types_table'");
            
            if ($table_exists) {
                // Utiliser la nouvelle table wp_restaurant_wine_types
                $updated_types = $wpdb->update(
                    $wine_types_table,
                    array(
                        'name' => $type_name,
                        'slug' => $type_key
                    ),
                    array('slug' => $original_type_key),
                    array('%s', '%s'),
                    array('%s')
                );
                
                // Mettre à jour tous les produits qui utilisent ce type
                $updated_products = $wpdb->update(
                    $wpdb->prefix . 'restaurant_products',
                    array('wine_category' => $type_key),
                    array('wine_category' => $original_type_key),
                    array('%s'),
                    array('%s')
                );
                
                if ($updated_types !== false) {
                    wp_redirect(admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=wines_group&message=type_updated&updated_count=' . $updated_products));
                } else {
                    wp_redirect(admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=wines_group&error=update_failed'));
                }
            } else {
                // Fallback vers l'ancienne méthode
                $subcategories_table = $wpdb->prefix . 'restaurant_subcategories';
                $updated_subcategories = $wpdb->update(
                    $subcategories_table,
                    array(
                        'subcategory_key' => $type_key,
                        'subcategory_name' => $type_name,
                        'subcategory_slug' => $type_key
                    ),
                    array('subcategory_key' => $original_type_key),
                    array('%s', '%s', '%s'),
                    array('%s')
                );
                
                // Mettre à jour tous les produits qui utilisent ce type
                $updated_products = $wpdb->update(
                    $wpdb->prefix . 'restaurant_products',
                    array('wine_category' => $type_key),
                    array('wine_category' => $original_type_key),
                    array('%s'),
                    array('%s')
                );
                
                if ($updated_subcategories !== false) {
                    wp_redirect(admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=wines_group&message=type_updated&updated_count=' . $updated_products));
                } else {
                    wp_redirect(admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=wines_group&error=update_failed'));
                }
            }
            exit;
        }
    }
    
    /**
     * Gérer la suppression d'un type de vin
     */
    private function handle_wine_type_deletion() {
        $type_key = isset($_GET['type_key']) ? sanitize_text_field($_GET['type_key']) : '';
        
        if (empty($type_key)) {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=wines_group&error=invalid_type'));
            exit;
        }
        
        // Vérifier le nonce
        if (!wp_verify_nonce($_GET['_wpnonce'], 'delete_wine_type_' . $type_key)) {
            wp_die(__('Erreur de sécurité', 'restaurant-booking'));
        }
        
        global $wpdb;
        
        // Vérifier que le type n'est pas utilisé par des produits
        $usage_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}restaurant_products p
            INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
            WHERE c.type = 'vin' 
            AND p.wine_category = %s
            AND p.is_active = 1
        ", $type_key));
        
        if ($usage_count > 0) {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=wines_group&error=type_in_use&usage_count=' . $usage_count));
            exit;
        }
        
        // CORRECTION : Le type n'est pas utilisé, on peut le supprimer de la nouvelle table
        $wine_types_table = $wpdb->prefix . 'restaurant_wine_types';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$wine_types_table'");
        
        if ($table_exists) {
            // Utiliser la nouvelle table wp_restaurant_wine_types
            $deleted = $wpdb->delete($wine_types_table, array(
                'slug' => $type_key
            ));
        } else {
            // Fallback vers l'ancienne méthode
            $subcategories_table = $wpdb->prefix . 'restaurant_subcategories';
            $deleted = $wpdb->delete($subcategories_table, array(
                'subcategory_key' => $type_key
            ));
        }
        
        if ($deleted !== false) {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=wines_group&message=type_deleted'));
        } else {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=wines_group&error=delete_failed'));
        }
        exit;
    }
    
    /**
     * Gérer la suppression d'un type de bière
     */
    private function handle_beer_type_deletion() {
        $type_key = isset($_GET['type_key']) ? sanitize_text_field($_GET['type_key']) : '';
        
        if (empty($type_key)) {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=beers_group&error=invalid_type'));
            exit;
        }
        
        // Vérifier le nonce
        if (!wp_verify_nonce($_GET['_wpnonce'], 'delete_beer_type_' . $type_key)) {
            wp_die(__('Erreur de sécurité', 'restaurant-booking'));
        }
        
        global $wpdb;
        
        // Vérifier que le type n'est pas utilisé par des produits
        $usage_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}restaurant_products p
            INNER JOIN {$wpdb->prefix}restaurant_categories c ON p.category_id = c.id
            WHERE c.type IN ('biere_bouteille', 'fut') 
            AND p.beer_category = %s
            AND p.is_active = 1
        ", $type_key));
        
        if ($usage_count > 0) {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=beers_group&error=type_in_use&usage_count=' . $usage_count));
            exit;
        }
        
        // CORRECTION : Le type n'est pas utilisé, on peut le supprimer de la nouvelle table
        $beer_types_table = $wpdb->prefix . 'restaurant_beer_types';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$beer_types_table'");
        
        if ($table_exists) {
            // Utiliser la nouvelle table wp_restaurant_beer_types
            $deleted = $wpdb->delete($beer_types_table, array(
                'slug' => $type_key
            ));
        } else {
            // Fallback vers l'ancienne méthode
            $subcategories_table = $wpdb->prefix . 'restaurant_subcategories';
            $deleted = $wpdb->delete($subcategories_table, array(
                'subcategory_key' => $type_key
            ));
        }
        
        if ($deleted !== false) {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=beers_group&message=type_deleted'));
        } else {
            wp_redirect(admin_url('admin.php?page=restaurant-booking-categories-manager&action=subcategories&category_id=beers_group&error=delete_failed'));
        }
        exit;
    }
    
    /**
     * Afficher la page des options
     */
    private function display_options_page($category_id) {
        // Rediriger vers la page des accompagnements pour l'instant
        wp_redirect(admin_url('admin.php?page=restaurant-booking-products-accompaniments'));
        exit;
    }
}
