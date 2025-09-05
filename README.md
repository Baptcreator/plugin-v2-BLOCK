# Restaurant Booking & Quote System - Plugin WordPress/Elementor

Plugin complet de gestion de devis de privatisation pour restaurant avec interface Elementor intégrée.

## 🎯 Vue d'ensemble

Ce plugin WordPress permet la gestion complète de devis de privatisation avec :
- **Interface publique** : Formulaires multi-étapes avec widgets Elementor
- **Administration backend** : Gestion complète des produits, catégories, devis et paramètres
- **Calculs automatiques** : Tarification dynamique selon les règles métier
- **Intégrations** : Google Calendar, emails automatiques, génération PDF

### Services proposés
1. **Privatisation du restaurant** (10-30 personnes)
2. **Privatisation de remorque mobile** (20-100+ personnes)

## 🚀 Fonctionnalités principales

### Interface d'administration
- ✅ **Tableau de bord** avec statistiques et aperçu
- ✅ **Gestion des devis** : CRUD complet, changement de statut, envoi emails
- ✅ **Gestion des produits** : Création, modification, import/export CSV
- ✅ **Gestion des catégories** : Types, contraintes, règles de sélection
- ✅ **Paramètres configurables** : Tous les textes et tarifs modifiables
- ✅ **Calendrier** : Gestion des disponibilités par service
- ✅ **Diagnostics** : Vérification santé système, logs, maintenance

### Interface publique (en cours)
- 🔄 **Widgets Elementor** : Restaurant Hero, Service Selection, Quote Form
- 🔄 **Formulaires multi-étapes** : Navigation fluide avec validation
- 🔄 **Calculateur temps réel** : Prix mis à jour automatiquement
- 🔄 **Design responsive** : Adaptation mobile/tablet/desktop

### Intégrations (à venir)
- ⏳ **Google Calendar** : Synchronisation disponibilités
- ⏳ **Emails automatiques** : Templates personnalisables
- ⏳ **Génération PDF** : Devis au format professionnel

## 📋 Prérequis

- **WordPress** : 5.0 ou supérieur
- **PHP** : 8.0 ou supérieur
- **MySQL** : 5.7 ou supérieur
- **Elementor** : 3.0 ou supérieur (recommandé)

## 🛠️ Installation

1. **Télécharger** le plugin dans le dossier `/wp-content/plugins/`
2. **Activer** le plugin depuis l'administration WordPress
3. **Configurer** les paramètres de base via le menu "Restaurant Devis"

### Activation automatique
Le plugin créé automatiquement :
- ✅ Toutes les tables de base de données
- ✅ Les données par défaut (catégories, zones de livraison, paramètres)
- ✅ Les rôles et permissions utilisateur
- ✅ Les tâches de maintenance automatique

## 🗄️ Architecture de la base de données

### Tables principales
- `wp_restaurant_categories` : Catégories de produits avec contraintes
- `wp_restaurant_products` : Produits avec prix et suppléments
- `wp_restaurant_quotes` : Devis clients avec détail complet
- `wp_restaurant_settings` : Paramètres configurables
- `wp_restaurant_availability` : Calendrier de disponibilités
- `wp_restaurant_delivery_zones` : Zones et tarifs de livraison
- `wp_restaurant_logs` : Journalisation système

## ⚙️ Configuration

### 1. Paramètres de base
```
Menu : Restaurant Devis > Paramètres > Tarification
- Prix forfaits restaurant/remorque
- Heures incluses et suppléments
- Contraintes min/max convives et durée
```

### 2. Catégories et produits
```
Menu : Restaurant Devis > Catégories
- Créer les catégories (Plats, Boissons, Options...)
- Définir les contraintes de sélection

Menu : Restaurant Devis > Produits  
- Ajouter les produits avec prix
- Configurer les suppléments optionnels
```

### 3. Zones de livraison (remorque)
```
Menu : Restaurant Devis > Paramètres > Zones de livraison
- Définir les tranches de distance
- Configurer les tarifs de livraison
```

## 🎨 Utilisation des widgets Elementor

### Widget "Restaurant Hero"
Affiche la présentation du restaurant avec boutons d'action.
```
Paramètres :
- Titre et description personnalisables
- Boutons menu et réservation
- Images de fond
- Animations d'entrée
```

### Widget "Service Selection"  
Présente les deux services de privatisation.
```
Paramètres :
- Layout colonnes/rangées
- Couleurs et espacement
- Textes automatiques ou personnalisés
```

### Widget "Quote Form"
Formulaire de demande de devis multi-étapes.
```
Paramètres :
- Type de service (restaurant/remorque)
- Thème de couleurs
- Position calculateur prix
- Messages de validation
```

## 🔧 Personnalisation

### Textes d'interface
Tous les textes sont modifiables via :
```
Restaurant Devis > Paramètres > Textes interface
- Page d'accueil
- Formulaires de devis  
- Messages de validation
- Templates d'emails
```

### Règles de tarification
Configuration complète via :
```
Restaurant Devis > Paramètres > Tarification
- Forfaits de base par service
- Suppléments durée et convives
- Zones et frais de livraison
```

### Contraintes métier
Définition par catégorie :
```
Restaurant Devis > Catégories > [Modifier]
- Sélection obligatoire
- Minimum/maximum de produits
- Quantité minimum par convive
```

## 📊 Gestion des devis

### Workflow standard
1. **Création** : Via formulaire public ou administration
2. **Calcul automatique** : Prix total selon les règles configurées
3. **Envoi** : Email automatique avec PDF joint
4. **Suivi** : Changement de statut (Brouillon → Envoyé → Confirmé)

### Statuts disponibles
- **Brouillon** : Devis en cours de création
- **Envoyé** : Devis transmis au client
- **Confirmé** : Devis accepté par le client  
- **Annulé** : Devis annulé

### Actions disponibles
- Voir le détail complet
- Modifier les informations
- Renvoyer par email
- Générer le PDF
- Changer le statut
- Ajouter des notes internes

## 📅 Gestion du calendrier

### Interface calendrier
```
Restaurant Devis > Calendrier
- Vue mensuelle par service
- Clic pour basculer disponible/occupé
- Blocage de périodes (vacances, maintenance)
- Notes par date
```

### Synchronisation Google Calendar
```
Restaurant Devis > Paramètres > Intégrations
- Connexion compte Google
- Sélection calendrier source
- Synchronisation bidirectionnelle
```

## 🔍 Diagnostics et maintenance

### Page de diagnostics (mode debug)
```
Restaurant Devis > Diagnostics
Tests automatiques :
✓ Connexion base de données
✓ Intégrité des tables  
✓ Configuration SMTP
✓ Permissions fichiers
✓ Espace disque disponible
```

### Maintenance automatique
Tâches programmées :
- **Quotidien** : Nettoyage devis brouillons > 30 jours
- **Quotidien** : Sauvegarde configuration
- **Hebdomadaire** : Optimisation base de données
- **Mensuel** : Archivage logs anciens

### Logs système
```
Restaurant Devis > Diagnostics > Logs
Niveaux : ERROR, WARNING, INFO, DEBUG
Filtres : Date, niveau, utilisateur
Export : CSV, JSON
```

## 🛡️ Sécurité

### Permissions WordPress
- `manage_restaurant_quotes` : Gestion des devis
- `manage_restaurant_products` : Gestion produits/catégories  
- `manage_restaurant_settings` : Configuration système

### Rôle personnalisé
- **Restaurant Manager** : Accès limité aux devis et produits

### Protection des données
- Nonces WordPress sur tous les formulaires
- Sanitization complète des entrées utilisateur
- Validation côté serveur systématique
- Limitation du taux de soumission par IP

## 🚀 Développement

### Structure du plugin
```
restaurant-booking-plugin.php     # Fichier principal
includes/                         # Classes principales
├── class-database.php           # Gestion BDD
├── class-settings.php           # Paramètres
├── class-quote.php             # Devis
├── class-product.php           # Produits  
├── class-category.php          # Catégories
├── class-logger.php            # Logs
├── class-email.php             # Emails
└── class-pdf.php               # PDF

admin/                           # Interface d'administration
├── class-admin.php             # Contrôleur principal
├── class-dashboard.php         # Tableau de bord
└── views/                      # Vues d'administration

public/                         # Interface publique
├── class-public.php           # Contrôleur public
├── class-quote-form.php       # Formulaires
└── class-ajax-handler.php     # Gestion AJAX

elementor/                      # Widgets Elementor
└── class-elementor-widgets.php

assets/                         # Ressources
├── css/                       # Styles
├── js/                        # Scripts
└── images/                    # Images
```

### Hooks disponibles
```php
// Actions
do_action('restaurant_booking_quote_created', $quote_id);
do_action('restaurant_booking_quote_sent', $quote_id);
do_action('restaurant_booking_settings_updated', $group);

// Filtres
apply_filters('restaurant_booking_quote_price', $price, $quote_data);
apply_filters('restaurant_booking_email_template', $template, $type);
apply_filters('restaurant_booking_pdf_content', $content, $quote_id);
```

## 📈 Roadmap

### Version 1.1 (à venir)
- [ ] Interface publique complète
- [ ] Widgets Elementor fonctionnels
- [ ] Système de paiement en ligne
- [ ] Notifications SMS

### Version 1.2 (planifié)
- [ ] Multi-langues (WPML)
- [ ] API REST publique
- [ ] Intégration WooCommerce
- [ ] Application mobile

### Version 1.3 (futur)
- [ ] Intelligence artificielle (suggestions)
- [ ] Analyses avancées
- [ ] Intégrations comptables
- [ ] Mode multi-sites

## 🆘 Support

### Documentation
- [Guide utilisateur complet](docs/user-guide.md)
- [Guide développeur](docs/developer-guide.md)
- [FAQ](docs/faq.md)

### Assistance technique
- **Email** : support@restaurant-block.fr
- **Forum** : [Support WordPress](https://wordpress.org/support/)
- **GitHub** : [Issues et demandes](https://github.com/restaurant-block/plugin)

## 📄 Licence

Ce plugin est distribué sous licence GPL v2 ou ultérieure.

---

**Développé avec ❤️ pour Restaurant Block**

*Version actuelle : 1.0.0*  
*Dernière mise à jour : Janvier 2025*