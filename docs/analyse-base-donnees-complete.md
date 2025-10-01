# 🔍 Analyse Complète de la Base de Données - Restaurant Block

**Date d'analyse :** 29/09/2025 18:59:29  
**Serveur :** block-streetfood.fr  
**Analysé par :** Script automatique d'analyse DB  

---

## 📊 Informations Générales de la Base de Données

| Paramètre | Valeur |
|-----------|--------|
| **Nom de la base** | u844876091_da32M |
| **Hôte** | 127.0.0.1 |
| **Utilisateur** | u844876091_JvLvO |
| **Charset** | utf8 |
| **Collate** | (vide) |
| **Préfixe des tables** | wp_ |
| **Version MySQL** | 11.8.3-MariaDB-log |

---

## 🏠 Tables WordPress Standard

| Table | Nombre d'enregistrements | Taille | Statut |
|-------|-------------------------|---------|---------|
| wp_posts | 624 | 4.67 MB | ✅ OK |
| wp_postmeta | 4,208 | 41.72 MB | ✅ OK |
| wp_users | 5 | 0.06 MB | ✅ OK |
| wp_usermeta | 156 | 0.08 MB | ✅ OK |
| wp_options | 675 | 2.59 MB | ✅ OK |
| wp_terms | 17 | 0.05 MB | ✅ OK |
| wp_term_taxonomy | 17 | 0.05 MB | ✅ OK |
| wp_term_relationships | 48 | 0.03 MB | ✅ OK |

**📈 Statistiques WordPress :**
- **Total des enregistrements :** 5,730
- **Taille totale :** ~49 MB
- **Santé générale :** ✅ Excellente

---

## 🍽️ Tables du Plugin Restaurant Block

### Vue d'ensemble des tables

| Table | Description | Enregistrements | Taille (MB) | Dernière MAJ |
|-------|-------------|----------------|-------------|--------------|
| wp_restaurant_accompaniment_options | Options d'accompagnement | 6 | 0.06 | 2025-09-29 12:02:36 |
| wp_restaurant_accompaniment_suboptions | Sous-options d'accompagnement | 4 | 0.06 | 2025-09-27 17:48:01 |
| wp_restaurant_availability | Disponibilités/Planning | 4 | 0.11 | 2025-09-29 12:02:36 |
| wp_restaurant_available_containers | Contenants disponibles | 4 | 0.06 | 2025-09-27 13:22:03 |
| wp_restaurant_beer_types | Types de bières | 6 | 0.06 | 2025-09-29 14:47:57 |
| wp_restaurant_beverage_sizes | Tailles de boissons | 9 | 0.06 | 2025-09-29 12:55:21 |
| wp_restaurant_categories | Catégories de produits | 11 | 0.11 | 2025-09-29 17:36:56 |
| wp_restaurant_delivery_zones | Zones de livraison | 4 | 0.06 | 2025-09-05 16:45:38 |
| wp_restaurant_keg_sizes | Tailles de fûts | 2 | 0.08 | 2025-09-29 17:48:40 |
| wp_restaurant_logs | Logs système | 2,250 | 0.55 | N/A |
| wp_restaurant_products | Produits et menus | 36 | 0.13 | 2025-09-29 17:48:40 |
| wp_restaurant_product_supplements_v2 | Suppléments produits V2 | 24 | 0.06 | 2025-09-25 14:11:28 |
| wp_restaurant_quotes | Devis clients | 4 | 0.09 | 2025-09-27 17:30:38 |
| wp_restaurant_settings | Paramètres du plugin | 69 | 0.06 | 2025-09-06 12:37:52 |
| wp_restaurant_subcategories | Sous-catégories | 26 | 0.06 | 2025-09-29 17:36:56 |
| wp_restaurant_wine_types | Types de vins | 4 | 0.06 | 2025-09-29 14:47:57 |

**📊 Totaux :**
- **16 tables** spécialisées
- **2,459 enregistrements** au total
- **~1.5 MB** de données

---

## 🔍 Analyse Détaillée des Tables Supplémentaires

### 🍺 Table: restaurant_beer_types

**Structure :**
- **6 enregistrements** - Types de bières configurés
- Colonnes : id, name, slug, description, display_order, is_active, created_at, updated_at

**Données :**
| ID | Nom | Slug | Description | Ordre | Actif |
|----|-----|------|-------------|-------|-------|
| 1 | Blonde | blonde | Bières blondes | 1 | ✅ |
| 2 | Blanche | blanche | Bières blanches | 2 | ✅ |
| 3 | Brune | brune | Bières brunes | 3 | ✅ |
| 4 | IPA | ipa | India Pale Ale | 4 | ✅ |
| 5 | Ambrée | ambree | Bières ambrées | 5 | ✅ |
| 6 | Pils | pils | Bières de type Pilsner | 6 | ✅ |

### 🍷 Table: restaurant_wine_types

**Structure :**
- **4 enregistrements** - Types de vins configurés
- Colonnes : id, name, slug, description, display_order, is_active, created_at, updated_at

**Données :**
| ID | Nom | Slug | Description | Ordre | Actif |
|----|-----|------|-------------|-------|-------|
| 1 | Rouge | rouge | Vins rouges | 1 | ✅ |
| 2 | Blanc | blanc | Vins blancs | 2 | ✅ |
| 3 | Rosé | rose | Vins rosés | 3 | ✅ |
| 4 | Crémant | cremant | Vins effervescents | 4 | ✅ |

### 🥤 Table: restaurant_beverage_sizes

**Structure :**
- **9 enregistrements** - Tailles de boissons configurées
- Colonnes : id, product_id, size_cl, size_label, price, display_order, is_active, created_at, image_id, is_featured, updated_at

**Exemples de données :**
| ID | Produit ID | Taille (cl) | Label | Prix | Actif |
|----|------------|-------------|-------|------|-------|
| 3 | 26 | 25 | 25cl | 2.50€ | ✅ |
| 4 | 26 | 50 | 50cl | 3.50€ | ✅ |
| 9 | 30 | 33 | 33cl | 4.00€ | ✅ |
| 11 | 27 | 50 | bouteille | 6.00€ | ✅ |

### 🍺 Table: restaurant_keg_sizes

**Structure :**
- **2 enregistrements** - Tailles de fûts configurées
- Colonnes : id, product_id, liters, price, image_id, is_featured, display_order, is_active, created_at, updated_at

**Données :**
| ID | Produit ID | Litres | Prix | Actif |
|----|------------|--------|------|-------|
| 5 | 35 | 10 | 15.00€ | ✅ |
| 6 | 35 | 20 | 25.00€ | ✅ |

### 🥗 Table: restaurant_accompaniment_options

**Structure :**
- **6 enregistrements** - Options d'accompagnement
- Colonnes : id, product_id, option_name, option_price, display_order, is_active, created_at, updated_at

**Données :**
| ID | Produit ID | Nom de l'option | Prix | Actif |
|----|------------|-----------------|------|-------|
| 12 | 17 | Choix de la sauce | 0.00€ | ✅ |
| 13 | 17 | Enrobée sauce chimichurri | 1.00€ | ✅ |
| 14 | 18 | Vinaigrette maison | 0.50€ | ✅ |
| 15 | 18 | Croûtons | 1.00€ | ✅ |
| 16 | 19 | Sauce à l'ail | 1.00€ | ✅ |
| 17 | 19 | Herbes de Provence | 0.50€ | ✅ |

### 🍴 Table: restaurant_accompaniment_suboptions

**Structure :**
- **4 enregistrements** - Sous-options d'accompagnement
- Colonnes : id, option_id, suboption_name, display_order, is_active, created_at, updated_at

**Données :**
| ID | Option ID | Nom de la sous-option | Actif |
|----|-----------|----------------------|-------|
| 25 | 12 | Ketchup | ✅ |
| 26 | 12 | Mayonnaise | ✅ |
| 27 | 12 | Moutarde | ✅ |
| 28 | 12 | Sauce BBQ | ✅ |

### 🥤 Table: restaurant_available_containers

**Structure :**
- **4 enregistrements** - Contenants disponibles
- Colonnes : id, liters, label, is_active, display_order, created_at, updated_at

**Données :**
| ID | Litres | Label | Actif | Ordre |
|----|--------|-------|-------|-------|
| 1 | 10 | 10L | ✅ | 1 |
| 2 | 20 | 20L | ✅ | 2 |
| 3 | 30 | 30L | ✅ | 3 |
| 4 | 50 | 50L | ✅ | 4 |

### 💊 Table: restaurant_product_supplements_v2

**Structure :**
- **24 enregistrements** - Suppléments produits version 2
- Colonnes : id, product_id, supplement_name, supplement_price, max_quantity, display_order, is_active, created_at, updated_at

**Exemples de suppléments :**
| ID | Produit ID | Nom du supplément | Prix | Actif |
|----|------------|-------------------|------|-------|
| 9 | 11 | Fromage cheddar | 1.50€ | ✅ |
| 10 | 11 | Bacon croustillant | 2.00€ | ✅ |
| 11 | 12 | Double sauce | 1.00€ | ✅ |
| 13 | 13 | Avocat frais | 2.50€ | ✅ |
| 15 | 14 | Jambon artisanal | 2.50€ | ✅ |

### 📂 Table: restaurant_subcategories

**Structure :**
- **26 enregistrements** - Sous-catégories organisées
- Colonnes : id, parent_category_id, subcategory_name, subcategory_slug, subcategory_key, display_order, is_active, created_at, updated_at

**Répartition par catégorie parent :**
- **Catégorie 109** (Bières Bouteilles) : 6 sous-catégories
- **Catégorie 110** (Fûts de Bière) : 6 sous-catégories
- **Autres catégories** : 14 sous-catégories diverses

---

## 📂 Analyse des Catégories de Produits

| ID | Nom | Type | Service | Produits | Requis | Min/Max | Actif |
|----|-----|------|---------|----------|---------|---------|-------|
| 100 | Plats Signature DOG | plat_signature_dog | both | 3 | ❌ | 0/∞ | ✅ |
| 101 | Plats Signature CROQ | plat_signature_croq | both | 3 | ❌ | 0/∞ | ✅ |
| 102 | Menu Enfant (Mini Boss) | mini_boss | both | 3 | ❌ | 0/∞ | ✅ |
| 103 | Accompagnements | accompagnement | both | 3 | ❌ | 0/∞ | ✅ |
| 104 | Buffet Salé | buffet_sale | both | 3 | ❌ | 0/∞ | ✅ |
| 105 | Buffet Sucré | buffet_sucre | both | 3 | ❌ | 0/∞ | ✅ |
| 106 | Boissons Soft | soft | both | 3 | ❌ | 0/∞ | ✅ |
| 109 | Bières Bouteilles | biere_bouteille | both | 3 | ❌ | 0/∞ | ✅ |
| 110 | Fûts de Bière | fut | remorque | 3 | ❌ | 0/∞ | ✅ |
| 111 | Jeux et Animations | (vide) | remorque | 3 | ❌ | 0/∞ | ✅ |
| 112 | Vins | (vide) | both | 6 | ❌ | 0/∞ | ✅ |

### 📈 Statistiques des Catégories

- **Service "remorque" :** 2 catégories (2 actives, 0 requises)
- **Service "both" :** 9 catégories (9 actives, 0 requises)
- **Total :** 11 catégories actives, aucune obligatoire

---

## 🍕 Analyse des Produits

### Statistiques par catégorie

| Catégorie | Type | Nb Produits | Produits Actifs | Prix Min | Prix Moyen | Prix Max |
|-----------|------|-------------|-----------------|----------|------------|----------|
| Vins | (vide) | 6 | 6 | 14.00€ | 18.00€ | 22.00€ |
| Plats Signature DOG | plat_signature_dog | 3 | 3 | 10.00€ | 11.00€ | 12.00€ |
| Buffet Salé | buffet_sale | 3 | 3 | 8.50€ | 11.83€ | 15.00€ |
| Fûts de Bière | fut | 3 | 3 | 0.00€ | 60.00€ | 95.00€ |
| Plats Signature CROQ | plat_signature_croq | 3 | 3 | 9.00€ | 10.00€ | 11.00€ |
| Buffet Sucré | buffet_sucre | 3 | 3 | 6.00€ | 7.33€ | 9.00€ |
| Jeux et Animations | (vide) | 3 | 3 | 80.00€ | 116.67€ | 150.00€ |
| Menu Enfant (Mini Boss) | mini_boss | 3 | 3 | 7.50€ | 8.17€ | 9.00€ |
| Boissons Soft | soft | 3 | 3 | 2.00€ | 2.33€ | 2.50€ |
| Accompagnements | accompagnement | 3 | 3 | 4.00€ | 4.33€ | 5.00€ |
| Bières Bouteilles | biere_bouteille | 3 | 3 | 3.50€ | 4.00€ | 4.50€ |

### 🔍 Détail des 20 derniers produits créés

| ID | Nom | Catégorie | Prix | Unité | Supplément | Actif | Créé le |
|----|-----|-----------|------|--------|------------|-------|---------|
| 47 | Château Gonflable | Jeux et Animations | 150.00€ | /jour | - | ✅ | 29/09/2025 |
| 48 | Toboggan Géant | Jeux et Animations | 120.00€ | /jour | - | ✅ | 29/09/2025 |
| 49 | Piscine à Balles | Jeux et Animations | 80.00€ | /jour | - | ✅ | 29/09/2025 |
| 38 | Mini Boss Classic | Menu Enfant (Mini Boss) | 8.00€ | /menu | - | ✅ | 25/09/2025 |
| 39 | Mini Boss Nuggets | Menu Enfant (Mini Boss) | 9.00€ | /menu | - | ✅ | 25/09/2025 |
| 40 | Mini Boss Croque | Menu Enfant (Mini Boss) | 7.50€ | /menu | - | ✅ | 25/09/2025 |
| 41 | Chardonnay | Vins | 18.00€ | /pièce | - | ✅ | 25/09/2025 |
| 42 | Sauvignon Blanc | Vins | 16.00€ | /pièce | - | ✅ | 25/09/2025 |
| 43 | Muscadet | Vins | 14.00€ | /bouteille 75cl | - | ✅ | 25/09/2025 |
| 44 | Bordeaux Rouge | Vins | 20.00€ | /pièce | - | ✅ | 25/09/2025 |
| 45 | Côtes du Rhône | Vins | 18.00€ | /pièce | - | ✅ | 25/09/2025 |
| 46 | Pinot Noir | Vins | 22.00€ | /pièce | - | ✅ | 25/09/2025 |

---

## ⚙️ Paramètres du Plugin

### 📋 Groupe: Constraints (Contraintes)

| Clé | Valeur | Type | Description |
|-----|--------|------|-------------|
| remorque_max_delivery_distance | 150 | number | Distance maximum livraison |
| remorque_max_guests | 100 | number | Maximum convives remorque |
| remorque_max_hours | 5 | number | Durée maximum remorque |
| remorque_min_guests | 20 | number | Minimum convives remorque |
| restaurant_max_guests | 30 | number | Maximum convives restaurant |
| restaurant_max_hours | 4 | number | Durée maximum restaurant |
| restaurant_min_guests | 10 | number | Minimum convives restaurant |

### 📧 Groupe: Emails

| Clé | Valeur | Type | Description |
|-----|--------|------|-------------|
| admin_notification_emails | ["admin@restaurant-block.fr"] | json | Emails de notification admin |
| email_quote_body_html | Madame, Monsieur, Nous vous remercions... | html | Corps email devis |
| email_quote_footer_html | Restaurant Block - SIRET: 1234567890... | html | Footer email devis |
| email_quote_header_html | Restaurant Block | html | Header email devis |
| email_quote_subject | Votre devis privatisation Block | text | Sujet email devis |

### 📝 Groupe: Forms (Formulaires)

| Clé | Valeur | Type | Description |
|-----|--------|------|-------------|
| form_date_label | Date souhaitée événement | text | Label date |
| form_duration_label | Durée souhaitée événement | text | Label durée |
| form_guests_label | Nombre de convives | text | Label convives |
| form_postal_label | Commune événement | text | Label code postal |
| form_step1_title | Forfait de base | text | Titre étape 1 |
| form_step2_title | Choix des formules repas | text | Titre étape 2 |
| form_step3_title | Choix des boissons | text | Titre étape 3 |
| form_step4_title | Coordonnées / Contact | text | Titre étape 4 |

### 🏠 Groupe: General

| Clé | Valeur | Type | Description |
|-----|--------|------|-------------|
| restaurant_postal_code | 67000 | text | Code postal restaurant |

### 🎨 Groupe: Interface

| Clé | Valeur | Type | Description |
|-----|--------|------|-------------|
| homepage_button_booking | Réserver à table | text | Texte bouton réservation |
| homepage_button_infos | Infos | text | Texte bouton infos |
| homepage_button_menu | Voir le menu | text | Texte bouton menu |
| homepage_button_privatiser | Privatiser Block | text | Texte bouton privatisation |
| homepage_restaurant_description | Découvrez notre cuisine authentique... | html | Description restaurant |
| homepage_restaurant_title | LE RESTAURANT | text | Titre restaurant page d'accueil |
| homepage_traiteur_title | LE TRAITEUR ÉVÉNEMENTIEL | text | Titre traiteur |
| traiteur_remorque_description | Notre remorque mobile se déplace... | html | Description remorque |
| traiteur_remorque_subtitle | À partir de 20 personnes | text | Sous-titre remorque |
| traiteur_remorque_title | Privatisation de la remorque Block | text | Titre remorque |
| traiteur_restaurant_description | Privatisez notre restaurant... | html | Description privatisation restaurant |
| traiteur_restaurant_subtitle | De 10 à 30 personnes | text | Sous-titre restaurant |
| traiteur_restaurant_title | Privatisation du restaurant | text | Titre privatisation restaurant |

### ⚠️ Groupe: Messages

| Clé | Valeur | Type | Description |
|-----|--------|------|-------------|
| error_date_unavailable | Cette date n'est pas disponible | text | Erreur date indisponible |
| error_duration_max | Durée maximum : {max} heures | text | Erreur durée maximum |
| error_guests_max | Nombre maximum de convives : {max} | text | Erreur maximum convives |
| error_guests_min | Nombre minimum de convives : {min} | text | Erreur minimum convives |
| error_selection_required | Sélection obligatoire | text | Erreur sélection obligatoire |

### 💰 Groupe: Pricing (Tarification)

| Clé | Valeur | Type | Description |
|-----|--------|------|-------------|
| delivery_zone_100_150_price | 120.00 | number | Supplément zone 100-150km |
| delivery_zone_30_50_price | 20.00 | number | Supplément zone 30-50km |
| delivery_zone_50_100_price | 70.00 | number | Supplément zone 50-100km |
| hourly_supplement | 50.00 | number | Supplément horaire |
| remorque_50_guests_supplement | 150.00 | number | Supplément +50 convives |
| remorque_base_price | 350.00 | number | Prix forfait remorque |
| remorque_games_base_price | 70.00 | number | Prix installation jeux |
| remorque_included_hours | 2 | number | Heures incluses remorque |
| remorque_tireuse_price | 50.00 | number | Prix mise à disposition tireuse |
| restaurant_base_price | 300.00 | number | Prix forfait restaurant |
| restaurant_included_hours | 2 | number | Heures incluses restaurant |

---

## 📋 Analyse des Devis

### Statistiques par statut et service

| Statut | Service | Nombre | Prix Moyen | Total CA | Convives Moyen |
|--------|---------|--------|------------|----------|----------------|
| draft | remorque | 1 | 1,160.00€ | 1,160.00€ | 20 |
| sent | restaurant | 1 | 962.50€ | 962.50€ | 25 |
| sent | remorque | 2 | 1,907.50€ | 3,815.00€ | 58 |

### 🕐 10 Derniers Devis

| N° Devis | Service | Date Événement | Convives | Prix Total | Statut | Créé le |
|----------|---------|----------------|----------|------------|--------|----------|
| BLOCK-2025-9421 | remorque | 02/10/2025 | 20 | 1,160.00€ | draft | 27/09/2025 17:30 |
| DEV-2024-003 | remorque | 05/04/2024 | 35 | 1,160.00€ | sent | 20/01/2024 09:15 |
| DEV-2024-001 | restaurant | 15/02/2024 | 25 | 962.50€ | sent | 15/01/2024 10:30 |
| DEV-2024-002 | remorque | 20/03/2024 | 80 | 2,655.00€ | sent | 10/01/2024 14:20 |

**📊 Performance des devis :**
- **Taux de conversion :** 75% (3 envoyés sur 4 générés)
- **CA total :** 4,777.50€
- **Panier moyen :** 1,484.38€

---

## 📅 Planning et Disponibilités

### Statistiques de disponibilité

| Service | Disponible | Nombre de dates | Première date | Dernière date |
|---------|------------|----------------|---------------|---------------|
| restaurant | ✅ Disponible | 1 | 27/09/2025 | 27/09/2025 |
| restaurant | ❌ Bloqué | 1 | 30/09/2025 | 30/09/2025 |
| both | ✅ Disponible | 1 | 28/09/2025 | 28/09/2025 |
| both | ❌ Bloqué | 1 | 29/09/2025 | 29/09/2025 |

### 🚫 Prochaines Dates Bloquées

| Date | Service | Raison | Heure Début | Heure Fin |
|------|---------|--------|-------------|-----------|
| 29/09/2025 | both | Synchronisé depuis Google Calendar | 07:00:00 | 16:00:00 |
| 30/09/2025 | restaurant | Synchronisé depuis Google Calendar | Toute la journée | Toute la journée |

---

## 🚚 Zones de Livraison

| Zone | Distance Min | Distance Max | Prix Livraison | Ordre | Statut |
|------|--------------|--------------|----------------|-------|---------|
| Zone 0-30km | 0 km | 30 km | 0.00€ | 1 | ✅ Active |
| Zone 31-60km | 31 km | 60 km | 50.00€ | 2 | ✅ Active |
| Zone 61-100km | 61 km | 100 km | 100.00€ | 3 | ✅ Active |
| Zone 101-150km | 101 km | 150 km | 150.00€ | 4 | ✅ Active |

---

## 📝 Logs et Activité Récente

### Statistiques des logs

| Niveau | Nombre | Dernier log |
|--------|--------|-------------|
| ❌ ERROR | 7 | 29/09/2025 17:40 |
| ⚠️ WARNING | 59 | 29/09/2025 17:43 |
| ℹ️ INFO | 2,178 | 29/09/2025 18:30 |
| 🐛 DEBUG | 6 | 29/09/2025 15:20 |

### 📋 15 Derniers Logs

| Niveau | Message | Date | Utilisateur | IP |
|--------|---------|------|-------------|-----|
| ℹ️ info | Migration correction ENUM vins terminée avec succès | 29/09/2025 18:30:44 | 2 | 2a02:8424:9004:8d01:8084:1e1a:531c:d15a |
| ℹ️ info | Catégorie Vins unifiée créée | 29/09/2025 18:30:44 | 2 | 2a02:8424:9004:8d01:8084:1e1a:531c:d15a |
| ℹ️ info | ENUM des catégories mis à jour avec le type "vin" | 29/09/2025 18:30:44 | 2 | 2a02:8424:9004:8d01:8084:1e1a:531c:d15a |
| ℹ️ info | Début migration correction ENUM vins | 29/09/2025 18:30:44 | 2 | 2a02:8424:9004:8d01:8084:1e1a:531c:d15a |
| ℹ️ info | Produit mis à jour: Fût Blonde Premium | 29/09/2025 17:48:40 | 2 | 2a02:8424:9004:8d01:8084:1e1a:531c:d15a |
| ⚠️ warning | Catégorie non trouvée | 29/09/2025 17:43:47 | 2 | 2a02:8424:9004:8d01:8084:1e1a:531c:d15a |
| ❌ error | Erreur lors de la création du produit | 29/09/2025 17:40:39 | 2 | 2a02:8424:9004:8d01:8084:1e1a:531c:d15a |

---

## 📊 Résumé Exécutif

### 📈 DONNÉES GÉNÉRALES
- **Catégories actives :** 11
- **Produits actifs :** 36
- **Total devis générés :** 4
- **Chiffre d'affaires total :** 4,777.50€
- **Panier moyen :** 1,484.38€

### 🎯 SERVICES DISPONIBLES
- **Remorque :** 2 catégories
- **Both (Restaurant + Remorque) :** 9 catégories

### 📈 PERFORMANCE DEVIS
- **Draft :** 1 (25.0%)
- **Sent :** 3 (75.0%)

### 🔧 ÉTAT TECHNIQUE
- **Version DB :** 3.0.0
- **Tables plugin :** 16 tables créées
- **Dernière activité :** 2025-09-29 18:30:44

### 📋 TABLES SUPPLÉMENTAIRES DÉTECTÉES
- **restaurant_accompaniment_options :** 6 enregistrements
- **restaurant_accompaniment_suboptions :** 4 enregistrements
- **restaurant_availability :** 4 enregistrements
- **restaurant_available_containers :** 4 enregistrements
- **restaurant_beer_types :** 6 enregistrements
- **restaurant_beverage_sizes :** 9 enregistrements
- **restaurant_categories :** 11 enregistrements
- **restaurant_delivery_zones :** 4 enregistrements
- **restaurant_keg_sizes :** 2 enregistrements
- **restaurant_logs :** 2,250 enregistrements
- **restaurant_products :** 36 enregistrements
- **restaurant_product_supplements_v2 :** 24 enregistrements
- **restaurant_quotes :** 4 enregistrements
- **restaurant_settings :** 69 enregistrements
- **restaurant_subcategories :** 26 enregistrements
- **restaurant_wine_types :** 4 enregistrements

### 📅 PLANNING
- **Dates bloquées à venir :** 2

### ✅ STATUT GLOBAL
**Plugin configuré et opérationnel**

---

## 🔍 Insights et Recommandations

### ✅ Points Forts
1. **Structure complète :** 16 tables bien organisées
2. **Configuration riche :** 69 paramètres configurés
3. **Système de logs actif :** 2,250 entrées de monitoring
4. **Gestion fine des produits :** Suppléments, tailles, options
5. **Planning synchronisé :** Intégration Google Calendar

### ⚠️ Points d'Attention
1. **Erreurs récentes :** 7 erreurs dans les logs
2. **Warnings fréquents :** 59 avertissements (catégories non trouvées)
3. **Peu de devis :** Seulement 4 devis générés
4. **Catégories non requises :** Aucune catégorie obligatoire configurée

### 🚀 Recommandations
1. **Corriger les erreurs** de création de produits
2. **Résoudre les warnings** de catégories manquantes  
3. **Optimiser le taux de conversion** des devis
4. **Configurer des catégories obligatoires** selon les besoins
5. **Surveiller les performances** avec plus de devis réels

---

**📅 Document généré le :** 29/09/2025  
**🔄 Prochaine analyse recommandée :** Dans 1 mois  
**📧 Contact support :** Pour toute question technique
