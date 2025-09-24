# 📋 Guide des Options Unifiées - Plugin Restaurant Booking

## 🎯 Vue d'ensemble

Le système d'options unifiées permet à l'administrateur de configurer toutes les règles, limites et textes du plugin depuis une seule page d'administration, remplaçant les valeurs précédemment codées en dur dans le code.

## 🔧 Fonctionnalités

### ✅ Ce qui a été implémenté

1. **Page d'administration unifiée** (`admin/class-options-unified-admin.php`)
   - Interface complète pour configurer toutes les options
   - Sauvegarde automatique des modifications
   - Interface utilisateur intuitive avec sections organisées

2. **Classe Helper** (`includes/class-options-helper.php`)
   - Accès facile aux options depuis le code
   - Méthodes de validation intégrées
   - Calculs automatiques (suppléments, prix)
   - Cache des options pour les performances

3. **Remplacement du menu admin**
   - Les anciennes pages "Options Restaurant" et "Options Remorque" ont été remplacées
   - Nouvelle page "⚙️ Options de Configuration" dans le menu

## 📊 Options configurables

### 🍽️ Règles de Validation Produits

#### Buffet Salé
- Minimum par personne (défaut: 1)
- Minimum de recettes différentes (défaut: 2)
- Texte d'explication personnalisable

#### Buffet Sucré
- Minimum par personne (défaut: 1)
- Minimum de plats (défaut: 1)
- Texte d'explication personnalisable

#### Accompagnements
- Minimum par personne (défaut: 1)
- Prix de base (défaut: 4€)
- Prix option chimichurri (défaut: 1€)
- Texte d'explication personnalisable

#### Plats Signature
- Minimum par personne (défaut: 1)
- Texte d'explication personnalisable

### 🏪 Privatisation Restaurant

#### Nombre de convives
- Minimum (défaut: 10 personnes)
- Maximum (défaut: 30 personnes)
- Texte d'affichage personnalisable

#### Durée événement
- Durée minimum incluse (défaut: 2h)
- Durée max sans supplément (défaut: 2h)
- Prix par heure supplémentaire (défaut: 50€)
- Texte d'explication personnalisable

#### Description forfait
- Éléments inclus dans le forfait (modifiable)

### 🚛 Privatisation Remorque

#### Nombre de convives
- Minimum (défaut: 20 personnes)
- Maximum (défaut: 100 personnes)
- Seuil supplément personnel (défaut: 50 personnes)
- Montant supplément personnel (défaut: 150€)
- Textes d'affichage personnalisables

#### Durée événement
- Durée minimum (défaut: 2h)
- Durée maximum (défaut: 5h)
- Prix par heure supplémentaire (défaut: 50€)

#### Distance et Déplacement
- Rayon gratuit (défaut: 30km)
- Prix 30-50km (défaut: 20€)
- Prix 50-100km (défaut: 70€)
- Prix 100-150km (défaut: 120€)
- Distance maximum (défaut: 150km)

#### Prix Options Spécifiques
- Mise à disposition tireuse (défaut: 50€)
- Installation jeux (défaut: 70€)

### 💬 Textes d'Interface
- Message final après devis
- Texte section commentaire

## 🔨 Utilisation pour les Développeurs

### Accès aux options

```php
// Méthode 1: Via l'instance du helper
$helper = RestaurantBooking_Options_Helper::get_instance();
$min_guests = $helper->get_option('restaurant_min_guests');

// Méthode 2: Via les fonctions raccourcis
$min_guests = rb_get_option('restaurant_min_guests');
$limits = rb_get_limits('restaurant');
```

### Méthodes de commodité

```php
$helper = RestaurantBooking_Options_Helper::get_instance();

// Obtenir les règles par catégorie
$buffet_rules = $helper->get_buffet_sale_rules();
$restaurant_limits = $helper->get_restaurant_limits();
$remorque_limits = $helper->get_remorque_limits();

// Calculs automatiques
$distance_price = $helper->calculate_distance_price(45); // 45km
$staff_supplement = $helper->calculate_staff_supplement(60); // 60 personnes
$hour_supplement = $helper->calculate_hour_supplement(5, 'restaurant'); // 5h restaurant

// Validations
$errors = $helper->validate_buffet_sale($selected_dishes, $guests_count);
$errors = $helper->validate_accompaniments($selected_accompaniments, $guests_count);
```

### Remplacement des valeurs codées en dur

**AVANT :**
```php
$min_guests = $service_type === 'restaurant' ? 10 : 20;
$max_guests = $service_type === 'restaurant' ? 30 : 100;
```

**APRÈS :**
```php
$limits = rb_get_limits($service_type);
$min_guests = $limits['min_guests'];
$max_guests = $limits['max_guests'];
```

## 🎨 Interface d'Administration

### Accès
- Menu WordPress Admin → Block & Co → ⚙️ Options de Configuration
- URL: `wp-admin/admin.php?page=restaurant-booking-options-unified`

### Sections de l'interface
1. **Règles de Validation Produits** - Configuration des minimums et textes
2. **Privatisation Restaurant** - Limites et descriptions
3. **Privatisation Remorque** - Limites, distances, et options
4. **Textes d'Interface** - Messages personnalisables

### Sauvegarde
- Bouton "Sauvegarder toutes les options" en bas de page
- Confirmation visuelle après sauvegarde
- Mise à jour immédiate dans les widgets publics

## 📁 Fichiers créés/modifiés

### Nouveaux fichiers
- `admin/class-options-unified-admin.php` - Interface d'administration
- `includes/class-options-helper.php` - Helper pour accéder aux options
- `public/class-options-usage-example.php` - Exemples d'utilisation
- `GUIDE-OPTIONS-UNIFIEES.md` - Cette documentation

### Fichiers modifiés
- `admin/class-admin.php` - Menu admin mis à jour
- `restaurant-booking-plugin.php` - Inclusion du helper

### Fichiers supprimés
- `admin/class-options-restaurant-admin.php` - Remplacé par le système unifié
- `admin/class-options-remorque-admin.php` - Remplacé par le système unifié

## 🔄 Migration

### Données existantes
- Le système utilise les options WordPress natives (`wp_options`)
- Clé d'option: `restaurant_booking_unified_options`
- Les valeurs par défaut sont automatiquement appliquées

### Compatibilité
- Le système est rétrocompatible
- Les anciennes valeurs codées en dur restent fonctionnelles jusqu'à migration
- Pas de perte de données lors de l'activation

## ⚠️ Notes importantes

1. **Performance** : Les options sont mises en cache pour éviter les requêtes répétées
2. **Sécurité** : Toutes les données sont sanitisées avant sauvegarde
3. **Validation** : Les valeurs numériques sont automatiquement validées
4. **Traduction** : Tous les textes sont prêts pour la traduction

## 🚀 Prochaines étapes

Pour finaliser l'implémentation :

1. **Mise à jour des widgets publics** - Remplacer les valeurs codées en dur
2. **Tests complets** - Vérifier que toutes les options fonctionnent
3. **Documentation utilisateur** - Guide pour les administrateurs
4. **Formation** - Expliquer le nouveau système à l'équipe

## 💡 Exemples d'utilisation

Voir le fichier `public/class-options-usage-example.php` pour des exemples concrets d'utilisation dans les widgets publics.

---

**Développé par :** Assistant IA Claude  
**Date :** $(date)  
**Version :** 2.1.0

