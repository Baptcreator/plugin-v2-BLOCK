<?php
/**
 * Classe d'administration des Options Unifiées
 * Gère toutes les options configurables du plugin (restaurant et remorque)
 *
 * @package RestaurantBooking
 * @since 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RestaurantBooking_Options_Unified_Admin
{
    /**
     * Options par défaut
     */
    private $default_options = array(
        // Règles de validation produits
        'buffet_sale_min_per_person' => 1,
        'buffet_sale_min_recipes' => 2,
        'buffet_sale_text' => 'min 1/personne et min 2 recettes différents',
        
        'buffet_sucre_min_per_person' => 1,
        'buffet_sucre_min_dishes' => 1,
        'buffet_sucre_text' => 'min 1/personne et min 1 plat',
        
        'accompaniment_min_per_person' => 1,
        'accompaniment_text' => 'mini 1/personne',
        
        'signature_dish_min_per_person' => 1,
        'signature_dish_text' => 'minimum 1 plat par personne',
        
        // Limites privatisation restaurant
        'restaurant_min_guests' => 10,
        'restaurant_max_guests' => 30,
        'restaurant_guests_text' => 'De 10 à 30 personnes',
        
        'restaurant_min_duration' => 2,
        'restaurant_max_duration_included' => 2,
        'restaurant_extra_hour_price' => 50,
        'restaurant_duration_text' => 'min durée = 2H (compris) max durée = 4H (supplément de +50 €/TTC/H)',
        
        // Limites privatisation remorque
        'remorque_min_guests' => 20,
        'remorque_max_guests' => 100,
        'remorque_staff_threshold' => 50,
        'remorque_staff_supplement' => 150,
        'remorque_guests_text' => 'À partir de 20 personnes',
        'remorque_staff_text' => 'au delà de 50p 1 forfait de +150€ s\'applique',
        
        'remorque_min_duration' => 2,
        'remorque_max_duration' => 5,
        'remorque_extra_hour_price' => 50,
        
        // Distance/Déplacement
        'free_radius_km' => 30,
        'price_30_50km' => 20,
        'price_50_100km' => 70,
        'price_100_150km' => 120,
        'max_distance_km' => 150,
        
        // Prix options remorque
        'tireuse_price' => 50,
        'games_price' => 70,
        
        // Prix accompagnements
        'accompaniment_base_price' => 4,
        'chimichurri_price' => 1,
        
        // Textes d'interface
        'final_message' => 'Votre devis est d\'ores et déjà disponible dans votre boîte mail, la suite ? Block va prendre contact avec vous afin d\'affiner celui-ci et de créer avec vous toute l\'expérience dont vous rêvez',
        'comment_section_text' => '1 question, 1 souhait, n\'hésitez pas de nous en fait part, on en parle, on....',
        
        // Textes du widget/shortcode
        'widget_title' => 'Demande de Devis Privatisation',
        'widget_subtitle' => 'Choisissez votre service et obtenez votre devis personnalisé',
        'service_selection_title' => 'Choisissez votre service',
        
        // Textes carte Restaurant
        'restaurant_card_title' => 'PRIVATISATION DU RESTAURANT',
        'restaurant_card_subtitle' => 'De 10 à 30 personnes',
        'restaurant_card_description' => 'Privatisez notre restaurant pour vos événements intimes et profitez d\'un service personnalisé dans un cadre chaleureux.',
        
        // Textes carte Remorque
        'remorque_card_title' => 'Privatisation de la remorque Block',
        'remorque_card_subtitle' => 'À partir de 20 personnes',
        'remorque_card_description' => 'Notre remorque mobile se déplace pour vos événements extérieurs et grandes réceptions.',
        
        // Messages système
        'success_message' => 'Votre devis est d\'ores et déjà disponible dans votre boîte mail',
        'loading_message' => 'Génération de votre devis en cours...',
        
        // Descriptions forfaits
        'restaurant_forfait_description' => 'Mise à disposition des murs de Block|Notre équipe salle + cuisine assurant la prestation|Présentation + mise en place buffets, selon vos choix|Mise à disposition vaisselle + verrerie|Entretien + nettoyage',
        'remorque_forfait_description' => 'Notre équipe salle + cuisine assurant la prestation|Déplacement et installation de la remorque BLOCK (aller et retour)|Présentation + mise en place buffets, selon vos choix|La fourniture de vaisselle jetable recyclable|La fourniture de verrerie (en cas d\'ajout de boisson)'
    );

    /**
     * Afficher la page des options
     */
    public function display_page()
    {
        // Traitement du formulaire
        if (isset($_POST['save_options']) && wp_verify_nonce($_POST['_wpnonce'], 'save_unified_options')) {
            $this->save_options();
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Options sauvegardées avec succès !', 'restaurant-booking') . '</p></div>';
        }
        
        // Traitement du nettoyage des échappements
        if (isset($_POST['clean_escaped_quotes']) && wp_verify_nonce($_POST['_wpnonce'], 'save_unified_options')) {
            $this->clean_all_escaped_quotes();
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Nettoyage des échappements terminé !', 'restaurant-booking') . '</p></div>';
        }

        $options = $this->get_options();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">⚙️ <?php _e('Options de Configuration', 'restaurant-booking'); ?></h1>
            <hr class="wp-header-end">

            <div class="restaurant-booking-info-card">
                <h3><?php _e('Configuration globale du plugin', 'restaurant-booking'); ?></h3>
                <p><?php _e('Cette page permet de configurer toutes les options, règles et textes utilisés dans les formulaires de devis.', 'restaurant-booking'); ?></p>
                <p><strong><?php _e('⚠️ Important :', 'restaurant-booking'); ?></strong> <?php _e('Les modifications apportées ici seront immédiatement visibles sur les widgets publics.', 'restaurant-booking'); ?></p>
            </div>

            <form method="post" action="">
                <?php wp_nonce_field('save_unified_options'); ?>
                
                <div class="restaurant-booking-options-container">
                    
                    <!-- Section 1: Règles de validation produits -->
                    <div class="options-section">
                        <h2>🍽️ <?php _e('Règles de Validation Produits', 'restaurant-booking'); ?></h2>
                        
                        <div class="options-group">
                            <h3><?php _e('Buffet Salé', 'restaurant-booking'); ?></h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Minimum par personne', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="number" name="buffet_sale_min_per_person" value="<?php echo esc_attr($options['buffet_sale_min_per_person']); ?>" min="1" class="small-text" />
                                        <p class="description"><?php _e('Nombre minimum de plats de buffet salé par personne', 'restaurant-booking'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Minimum de recettes différentes', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="number" name="buffet_sale_min_recipes" value="<?php echo esc_attr($options['buffet_sale_min_recipes']); ?>" min="1" class="small-text" />
                                        <p class="description"><?php _e('Nombre minimum de plats différents à sélectionner', 'restaurant-booking'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Texte d\'explication', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="text" name="buffet_sale_text" value="<?php echo esc_attr($options['buffet_sale_text']); ?>" class="regular-text" />
                                        <p class="description"><?php _e('Texte affiché dans le widget pour expliquer la règle', 'restaurant-booking'); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="options-group">
                            <h3><?php _e('Buffet Sucré', 'restaurant-booking'); ?></h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Minimum par personne', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="number" name="buffet_sucre_min_per_person" value="<?php echo esc_attr($options['buffet_sucre_min_per_person']); ?>" min="1" class="small-text" />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Minimum de plats', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="number" name="buffet_sucre_min_dishes" value="<?php echo esc_attr($options['buffet_sucre_min_dishes']); ?>" min="1" class="small-text" />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Texte d\'explication', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="text" name="buffet_sucre_text" value="<?php echo esc_attr($options['buffet_sucre_text']); ?>" class="regular-text" />
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="options-group">
                            <h3><?php _e('Accompagnements', 'restaurant-booking'); ?></h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Minimum par personne', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="number" name="accompaniment_min_per_person" value="<?php echo esc_attr($options['accompaniment_min_per_person']); ?>" min="1" class="small-text" />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Prix de base', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="number" name="accompaniment_base_price" value="<?php echo esc_attr($options['accompaniment_base_price']); ?>" min="0" step="0.01" class="small-text" />
                                        <span>€</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Prix option chimichurri', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="number" name="chimichurri_price" value="<?php echo esc_attr($options['chimichurri_price']); ?>" min="0" step="0.01" class="small-text" />
                                        <span>€</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Texte d\'explication', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="text" name="accompaniment_text" value="<?php echo esc_attr($options['accompaniment_text']); ?>" class="regular-text" />
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="options-group">
                            <h3><?php _e('Plats Signature', 'restaurant-booking'); ?></h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Minimum par personne', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="number" name="signature_dish_min_per_person" value="<?php echo esc_attr($options['signature_dish_min_per_person']); ?>" min="1" class="small-text" />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Texte d\'explication', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="text" name="signature_dish_text" value="<?php echo esc_attr($options['signature_dish_text']); ?>" class="regular-text" />
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Section 2: Privatisation Restaurant -->
                    <div class="options-section">
                        <h2>🏪 <?php _e('Privatisation Restaurant', 'restaurant-booking'); ?></h2>
                        
                        <div class="options-group">
                            <h3><?php _e('Nombre de convives', 'restaurant-booking'); ?></h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Minimum', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="number" name="restaurant_min_guests" value="<?php echo esc_attr($options['restaurant_min_guests']); ?>" min="1" class="small-text" />
                                        <span><?php _e('personnes', 'restaurant-booking'); ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Maximum', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="number" name="restaurant_max_guests" value="<?php echo esc_attr($options['restaurant_max_guests']); ?>" min="1" class="small-text" />
                                        <span><?php _e('personnes', 'restaurant-booking'); ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Texte d\'affichage', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="text" name="restaurant_guests_text" value="<?php echo esc_attr($options['restaurant_guests_text']); ?>" class="regular-text" />
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="options-group">
                            <h3><?php _e('Durée événement', 'restaurant-booking'); ?></h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Durée minimum incluse', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="number" name="restaurant_min_duration" value="<?php echo esc_attr($options['restaurant_min_duration']); ?>" min="1" class="small-text" />
                                        <span><?php _e('heures', 'restaurant-booking'); ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Durée max sans supplément', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="number" name="restaurant_max_duration_included" value="<?php echo esc_attr($options['restaurant_max_duration_included']); ?>" min="1" class="small-text" />
                                        <span><?php _e('heures', 'restaurant-booking'); ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Prix par heure supplémentaire', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="number" name="restaurant_extra_hour_price" value="<?php echo esc_attr($options['restaurant_extra_hour_price']); ?>" min="0" step="0.01" class="small-text" />
                                        <span>€ TTC</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Texte d\'explication', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="text" name="restaurant_duration_text" value="<?php echo esc_attr($options['restaurant_duration_text']); ?>" class="large-text" />
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="options-group">
                            <h3><?php _e('Description forfait', 'restaurant-booking'); ?></h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Éléments inclus', 'restaurant-booking'); ?></th>
                                    <td>
                                        <textarea name="restaurant_forfait_description" rows="5" class="large-text"><?php echo esc_textarea($options['restaurant_forfait_description']); ?></textarea>
                                        <p class="description"><?php _e('Séparez chaque élément par un pipe (|)', 'restaurant-booking'); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Section 3: Privatisation Remorque -->
                    <div class="options-section">
                        <h2>🚛 <?php _e('Privatisation Remorque', 'restaurant-booking'); ?></h2>
                        
                        <div class="options-group">
                            <h3><?php _e('Nombre de convives', 'restaurant-booking'); ?></h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Minimum', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="number" name="remorque_min_guests" value="<?php echo esc_attr($options['remorque_min_guests']); ?>" min="1" class="small-text" />
                                        <span><?php _e('personnes', 'restaurant-booking'); ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Maximum', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="number" name="remorque_max_guests" value="<?php echo esc_attr($options['remorque_max_guests']); ?>" min="1" class="small-text" />
                                        <span><?php _e('personnes', 'restaurant-booking'); ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Seuil supplément personnel', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="number" name="remorque_staff_threshold" value="<?php echo esc_attr($options['remorque_staff_threshold']); ?>" min="1" class="small-text" />
                                        <span><?php _e('personnes', 'restaurant-booking'); ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Montant supplément personnel', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="number" name="remorque_staff_supplement" value="<?php echo esc_attr($options['remorque_staff_supplement']); ?>" min="0" step="0.01" class="small-text" />
                                        <span>€</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Texte d\'affichage', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="text" name="remorque_guests_text" value="<?php echo esc_attr($options['remorque_guests_text']); ?>" class="regular-text" />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Texte explication supplément', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="text" name="remorque_staff_text" value="<?php echo esc_attr($options['remorque_staff_text']); ?>" class="regular-text" />
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="options-group">
                            <h3><?php _e('Durée événement', 'restaurant-booking'); ?></h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Durée minimum', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="number" name="remorque_min_duration" value="<?php echo esc_attr($options['remorque_min_duration']); ?>" min="1" class="small-text" />
                                        <span><?php _e('heures', 'restaurant-booking'); ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Durée maximum', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="number" name="remorque_max_duration" value="<?php echo esc_attr($options['remorque_max_duration']); ?>" min="1" class="small-text" />
                                        <span><?php _e('heures', 'restaurant-booking'); ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Prix par heure supplémentaire', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="number" name="remorque_extra_hour_price" value="<?php echo esc_attr($options['remorque_extra_hour_price']); ?>" min="0" step="0.01" class="small-text" />
                                        <span>€ TTC</span>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="options-group">
                            <h3><?php _e('Distance et Déplacement', 'restaurant-booking'); ?></h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Rayon gratuit', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="number" name="free_radius_km" value="<?php echo esc_attr($options['free_radius_km']); ?>" min="0" class="small-text" />
                                        <span>km</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Prix 30-50km', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="number" name="price_30_50km" value="<?php echo esc_attr($options['price_30_50km']); ?>" min="0" step="0.01" class="small-text" />
                                        <span>€</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Prix 50-100km', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="number" name="price_50_100km" value="<?php echo esc_attr($options['price_50_100km']); ?>" min="0" step="0.01" class="small-text" />
                                        <span>€</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Prix 100-150km', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="number" name="price_100_150km" value="<?php echo esc_attr($options['price_100_150km']); ?>" min="0" step="0.01" class="small-text" />
                                        <span>€</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Distance maximum', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="number" name="max_distance_km" value="<?php echo esc_attr($options['max_distance_km']); ?>" min="1" class="small-text" />
                                        <span>km</span>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="options-group">
                            <h3><?php _e('Prix Options Spécifiques', 'restaurant-booking'); ?></h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Mise à disposition tireuse', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="number" name="tireuse_price" value="<?php echo esc_attr($options['tireuse_price']); ?>" min="0" step="0.01" class="small-text" />
                                        <span>€</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Installation jeux', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="number" name="games_price" value="<?php echo esc_attr($options['games_price']); ?>" min="0" step="0.01" class="small-text" />
                                        <span>€</span>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="options-group">
                            <h3><?php _e('Description forfait', 'restaurant-booking'); ?></h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Éléments inclus', 'restaurant-booking'); ?></th>
                                    <td>
                                        <textarea name="remorque_forfait_description" rows="5" class="large-text"><?php echo esc_textarea($options['remorque_forfait_description']); ?></textarea>
                                        <p class="description"><?php _e('Séparez chaque élément par un pipe (|)', 'restaurant-booking'); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Section 4: Textes d'interface -->
                    <div class="options-section">
                        <h2>💬 <?php _e('Textes d\'Interface', 'restaurant-booking'); ?></h2>
                        
                        <div class="options-group">
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Message final après devis', 'restaurant-booking'); ?></th>
                                    <td>
                                        <textarea name="final_message" rows="3" class="large-text"><?php echo esc_textarea($options['final_message']); ?></textarea>
                                        <p class="description"><?php _e('Message affiché après soumission du devis', 'restaurant-booking'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Texte section commentaire', 'restaurant-booking'); ?></th>
                                    <td>
                                        <textarea name="comment_section_text" rows="2" class="large-text"><?php echo esc_textarea($options['comment_section_text']); ?></textarea>
                                        <p class="description"><?php _e('Texte affiché dans la section commentaires', 'restaurant-booking'); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Section 5: Textes du Formulaire (Shortcode) -->
                    <div class="options-section">
                        <h2>📝 <?php _e('Textes du Formulaire de Devis', 'restaurant-booking'); ?></h2>
                        <p class="description"><?php _e('Ces textes sont utilisés dans le shortcode [restaurant_booking_form]', 'restaurant-booking'); ?></p>
                        
                        <div class="options-group">
                            <h3><?php _e('En-tête du formulaire', 'restaurant-booking'); ?></h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Titre principal', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="text" name="widget_title" value="<?php echo esc_attr($options['widget_title']); ?>" class="large-text" />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Sous-titre', 'restaurant-booking'); ?></th>
                                    <td>
                                        <textarea name="widget_subtitle" rows="2" class="large-text"><?php echo esc_textarea($options['widget_subtitle']); ?></textarea>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Titre sélection service', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="text" name="service_selection_title" value="<?php echo esc_attr($options['service_selection_title']); ?>" class="large-text" />
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="options-group">
                            <h3><?php _e('Carte Restaurant', 'restaurant-booking'); ?></h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Titre', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="text" name="restaurant_card_title" value="<?php echo esc_attr($options['restaurant_card_title']); ?>" class="large-text" />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Sous-titre', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="text" name="restaurant_card_subtitle" value="<?php echo esc_attr($options['restaurant_card_subtitle']); ?>" class="large-text" />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Description', 'restaurant-booking'); ?></th>
                                    <td>
                                        <textarea name="restaurant_card_description" rows="3" class="large-text"><?php echo esc_textarea($options['restaurant_card_description']); ?></textarea>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="options-group">
                            <h3><?php _e('Carte Remorque', 'restaurant-booking'); ?></h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Titre', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="text" name="remorque_card_title" value="<?php echo esc_attr($options['remorque_card_title']); ?>" class="large-text" />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Sous-titre', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="text" name="remorque_card_subtitle" value="<?php echo esc_attr($options['remorque_card_subtitle']); ?>" class="large-text" />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Description', 'restaurant-booking'); ?></th>
                                    <td>
                                        <textarea name="remorque_card_description" rows="3" class="large-text"><?php echo esc_textarea($options['remorque_card_description']); ?></textarea>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="options-group">
                            <h3><?php _e('Messages système', 'restaurant-booking'); ?></h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Message de succès', 'restaurant-booking'); ?></th>
                                    <td>
                                        <textarea name="success_message" rows="2" class="large-text"><?php echo esc_textarea($options['success_message']); ?></textarea>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Message de chargement', 'restaurant-booking'); ?></th>
                                    <td>
                                        <input type="text" name="loading_message" value="<?php echo esc_attr($options['loading_message']); ?>" class="large-text" />
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                </div>

                <p class="submit">
                    <input type="submit" name="save_options" class="button-primary" value="<?php _e('Sauvegarder toutes les options', 'restaurant-booking'); ?>" />
                    <input type="submit" name="clean_escaped_quotes" class="button-secondary" value="<?php _e('Nettoyer les échappements multiples', 'restaurant-booking'); ?>" style="margin-left: 10px;" 
                           onclick="return confirm('<?php _e('Êtes-vous sûr de vouloir nettoyer les échappements multiples ? Cette action corrigera les apostrophes mal échappées.', 'restaurant-booking'); ?>');" />
                </p>
            </form>
        </div>

        <style>
        .restaurant-booking-info-card {
            background: #f0f8ff;
            border: 1px solid #0073aa;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }
        .restaurant-booking-info-card h3 {
            margin-top: 0;
            color: #0073aa;
        }
        .restaurant-booking-options-container {
            max-width: 1200px;
        }
        .options-section {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            margin: 20px 0;
            padding: 20px;
        }
        .options-section h2 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        .options-group {
            margin: 20px 0;
            padding: 15px;
            background: #fafafa;
            border-radius: 4px;
        }
        .options-group h3 {
            margin-top: 0;
            color: #333;
        }
        .form-table th {
            width: 250px;
        }
        .small-text {
            width: 80px;
        }
        </style>
        <?php
    }

    /**
     * Obtenir les options (avec valeurs par défaut)
     */
    public function get_options()
    {
        $saved_options = get_option('restaurant_booking_unified_options', array());
        
        // Nettoyer les échappements multiples dans les options sauvegardées
        foreach ($saved_options as $key => $value) {
            if (is_string($value)) {
                $saved_options[$key] = $this->clean_escaped_quotes($value);
            }
        }
        
        return array_merge($this->default_options, $saved_options);
    }

    /**
     * Sauvegarder les options
     */
    private function save_options()
    {
        $options = array();
        
        // Récupérer toutes les options du formulaire
        foreach ($this->default_options as $key => $default_value) {
            if (isset($_POST[$key])) {
                $value = sanitize_text_field($_POST[$key]);
                
                // Conversion des types pour les valeurs numériques
                if (is_numeric($default_value)) {
                    $options[$key] = floatval($value);
                } else {
                    // Nettoyer les échappements multiples pour les textes
                    $value = $this->clean_escaped_quotes($value);
                    $options[$key] = $value;
                }
            }
        }
        
        update_option('restaurant_booking_unified_options', $options);
    }
    
    /**
     * Nettoyer les échappements multiples d'apostrophes
     */
    private function clean_escaped_quotes($text)
    {
        // Remplacer les multiples échappements par une seule apostrophe
        $text = preg_replace('/\\\\+\'/', "'", $text);
        $text = preg_replace('/\\\\+\"/', '"', $text);
        
        return $text;
    }
    
    /**
     * Nettoyer tous les échappements dans les options sauvegardées
     */
    private function clean_all_escaped_quotes()
    {
        $options = get_option('restaurant_booking_unified_options', array());
        $updated = false;
        
        foreach ($options as $key => $value) {
            if (is_string($value)) {
                $cleaned_value = $this->clean_escaped_quotes($value);
                if ($cleaned_value !== $value) {
                    $options[$key] = $cleaned_value;
                    $updated = true;
                }
            }
        }
        
        if ($updated) {
            update_option('restaurant_booking_unified_options', $options);
        }
        
        return $updated;
    }

    /**
     * Obtenir une option spécifique
     */
    public static function get_option($key, $default = null)
    {
        $instance = new self();
        $options = $instance->get_options();
        
        return isset($options[$key]) ? $options[$key] : $default;
    }
}

