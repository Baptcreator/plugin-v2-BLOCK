# 📋 CAHIER DES CHARGES COMPLET - FORMULAIRE V3

## 🎯 **VUE D'ENSEMBLE**

Le **Formulaire Block V3** est un système de devis multi-étapes pour deux services de privatisation :
- **Restaurant** : 6 étapes (10-30 personnes)
- **Remorque Block** : 7 étapes (20-100+ personnes)

---

## 🎨 **DESIGN SYSTEM**

### **Charte Graphique Block**
- **Couleurs** : #F6F2E7 (beige), #243127 (vert foncé), #FFB404 (orange), #EF3D1D (rouge), #FFFFFF (blanc)
- **Polices** : FatKat pour titres/boutons, Roboto pour textes
- **Border-radius** : 12px partout
- **Ombres** : Subtiles avec rgba(0,0,0,0.1-0.2)
- **Animations** : Transitions fluides 0.3s ease

### **Composants UI**
- **Cards** : Fond blanc, bordure beige, hover orange
- **Boutons** : FatKat, uppercase, padding 16px 32px, min-height 48px
- **Inputs** : Bordure beige, focus orange, validation rouge/vert
- **Sélecteurs quantité** : Boutons +/- avec input central
- **Progress bar** : Gradient orange-rouge avec étapes numérotées

---

## 🚀 **ÉTAPE INITIALE - SÉLECTION SERVICE**

### **Design**
```
┌─────────────────────────────────────────────────────────────┐
│                    DEMANDE DE DEVIS PRIVATISATION          │
│           Choisissez votre service et obtenez votre        │
│                    devis personnalisé                      │
│                                                             │
│  ┌─────────────────────┐    ┌─────────────────────────┐    │
│  │ PRIVATISATION DU    │    │ PRIVATISATION DE LA     │    │
│  │ RESTAURANT          │    │ REMORQUE BLOCK          │    │
│  │ De 10 à 30 personnes│    │ À partir de 20 personnes│   │
│  │                     │    │                         │    │
│  │ Description...      │    │ Description...          │    │
│  │                     │    │                         │    │
│  │ [PRIVATISER LE      │    │ [COMING SOON...]        │    │
│  │  RESTAURANT]        │    │                         │    │
│  └─────────────────────┘    └─────────────────────────┘    │
└─────────────────────────────────────────────────────────────┘
```

### **Fonctionnalités**
- **Cards interactives** avec hover effects
- **Sélection unique** (remorque désactivée temporairement)
- **Transition automatique** vers étape suivante après sélection
- **Textes configurables** depuis l'admin

---

## 🏠 **PARCOURS RESTAURANT (6 ÉTAPES)**

### **ÉTAPE 1 : POURQUOI PRIVATISER NOTRE RESTAURANT ?**

#### **Design**
```
┌─────────────────────────────────────────────────────────────┐
│ ●○○○○○  Étape 1/6 : Pourquoi privatiser notre restaurant ? │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │              Comment ça fonctionne ?                │   │
│  │                                                     │   │
│  │  1. Forfait de base                                 │   │
│  │  2. Choix du formule repas (personnalisable)       │   │
│  │  3. Choix des boissons (optionnel)                 │   │
│  │  4. Coordonnées / Contact                           │   │
│  │                                                     │   │
│  │           [COMMENCER MON DEVIS]                     │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

#### **Fonctionnalités**
- **Card explicative** avec liste des étapes
- **Textes modifiables** depuis l'admin
- **Bouton d'action** pour démarrer le processus

---

### **ÉTAPE 2 : FORFAIT DE BASE**

#### **Design**
```
┌─────────────────────────────────────────────────────────────┐
│ ●●○○○○  Étape 2/6 : Forfait de base                        │
│                                                             │
│  📅 Date souhaitée événement    👥 Nombre de convives      │
│  [  --/--/----  ]              [    10    ] personnes     │
│  Sélectionnez une date future   De 10 à 30 personnes       │
│                                                             │
│  ⏰ Durée souhaitée événement                              │
│  [ 2H ▼ ] (2H/3H/4H)                                      │
│  min durée = 2H (compris) max durée = 4H (+50€/H)         │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │        FORFAIT DE BASE PRIVATISATION RESTO         │   │
│  │                                                     │   │
│  │  ✓ 2H de privatisation (service inclus...)         │   │
│  │  ✓ Notre équipe salle + cuisine assurant...        │   │
│  │  ✓ Présentation + mise en place buffets...         │   │
│  │  ✓ Mise à disposition vaisselle + verrerie         │   │
│  │  ✓ Entretien + nettoyage                           │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  💰 Montant estimatif : 200 € (montant indicatif)         │
└─────────────────────────────────────────────────────────────┘
```

#### **Règles Métier**
- **Date** : Uniquement dates futures, connexion Google Calendar
- **Convives** : Min 10, Max 30 personnes
- **Durée** : 2H inclus, 3H/4H avec supplément +50€/H
- **Prix base** : 200€ (configurable admin)

#### **Fonctionnalités**
- **Sélecteur de date** avec disponibilités
- **Input numérique** avec validation min/max
- **Menu déroulant** durée avec calcul automatique
- **Card forfait** avec description détaillée
- **Calculateur prix** sticky qui apparaît

---

### **ÉTAPE 3 : CHOIX DES FORMULES REPAS**

#### **Design**
```
┌─────────────────────────────────────────────────────────────┐
│ ●●●○○○  Étape 3/6 : Choix des formules repas               │
│                                                             │
│  ℹ️ Information importante :                                │
│  Sélection obligatoire pour 15 convives.                   │
│  Les quantités minimales sont calculées automatiquement.   │
│                                                             │
│  🍽️ CHOIX DU PLAT SIGNATURE                               │
│  minimum 1 plat par personne                               │
│                                                             │
│  ○ 🌭 DOG - Nos hot-dogs signature                         │
│  ● 🥪 CROQ - Nos croque-monsieurs                          │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │  [Photo] Hot-Dog Classic        12€  [-] 15 [+]    │   │
│  │  Pain brioche, saucisse...                          │   │
│  │                                                     │   │
│  │  [Photo] Hot-Dog Spicy          14€  [-] 0  [+]    │   │
│  │  Pain brioche, saucisse...                          │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  👑 MENU MINI BOSS (optionnel)                             │
│  Pour les plus petits                                      │
│                                                             │
│  ☐ Ajouter le menu Mini Boss                               │
│                                                             │
│  🥗 ACCOMPAGNEMENTS                                        │
│  mini 1/personne                                           │
│                                                             │
│  ☐ Salade 4€        [-] 0 [+]                             │
│  ☑ Frites 4€        [-] 15 [+]                            │
│    ☐ Enrobée sauce chimichurri +1€  [-] 0 [+]            │
│    ☐ Sauce ketchup                  [-] 0 [+]            │
│    ☐ Sauce mayo                     [-] 0 [+]            │
│                                                             │
│  💰 Montant estimatif : 440 € (montant indicatif)         │
└─────────────────────────────────────────────────────────────┘
```

#### **Règles Métier**
- **Plat signature** : Choix obligatoire DOG OU CROQ, min 1/personne
- **Produits** : Affichage depuis DB avec photo, titre, description, prix
- **Mini Boss** : Optionnel, produits catégorie "MINI BOSS"
- **Accompagnements** : Min 1/personne, options frites (chimichurri +1€)
- **Validation** : Impossible de dépasser quantité accompagnement pour les sauces

#### **Fonctionnalités**
- **Radio buttons** pour choix DOG/CROQ
- **Chargement dynamique** produits selon sélection
- **Sélecteurs quantité** visuels avec +/-
- **Checkbox** pour Mini Boss avec expansion
- **Options conditionnelles** pour frites
- **Validation temps réel** des quantités minimales

---

### **ÉTAPE 4 : CHOIX DU/DES BUFFET(S)**

#### **Design**
```
┌─────────────────────────────────────────────────────────────┐
│ ●●●●○○  Étape 4/6 : Choix du/des buffet(s)                 │
│                                                             │
│  Choisissez votre formule buffet :                         │
│                                                             │
│  ○ Buffet salé                                              │
│  ○ Buffet sucré                                             │
│  ● Buffets salés et sucrés                                 │
│                                                             │
│  🥗 BUFFET SALÉ                                            │
│  min 1/personne et min 2 recettes différentes              │
│                                                             │
│  ☑ [Photo] Grilled Cheese    10€  [-] 8 [+]               │
│      Description du plat...   20 pers                      │
│      ☐ +1€ supp · Jambon Blanc  [-] 0 [+]                 │
│                                                             │
│  ☑ [Photo] Salade César      8€   [-] 7 [+]               │
│      Description du plat...   15 pers                      │
│                                                             │
│  ☐ [Photo] Wrap Poulet       12€  [-] 0 [+]               │
│      Description du plat...                                │
│                                                             │
│  🍰 BUFFET SUCRÉ                                           │
│  min 1/personne et min 1 plat                              │
│                                                             │
│  ☑ [Photo] Tiramisu          6€   [-] 15 [+]              │
│      Description du dessert...                             │
│                                                             │
│  💰 Montant estimatif : 680 € (montant indicatif)         │
└─────────────────────────────────────────────────────────────┘
```

#### **Règles Métier**
- **Choix unique** : Salé OU Sucré OU Les deux
- **Buffet salé** : Min 1/personne ET min 2 recettes différentes
- **Buffet sucré** : Min 1/personne ET min 1 plat
- **Suppléments** : Possibles par plat, quantité ≤ plat principal
- **Affichage** : Photo, titre, description, grammes/pièces par personne, prix

#### **Fonctionnalités**
- **Radio buttons** pour choix type buffet
- **Affichage conditionnel** des sections selon choix
- **Cards produits** avec photos et descriptions
- **Sélecteurs quantité** avec validation
- **Suppléments dépliables** par produit
- **Validation complexe** multi-règles

---

### **ÉTAPE 5 : CHOIX DES BOISSONS (OPTIONNEL)**

#### **Design**
```
┌─────────────────────────────────────────────────────────────┐
│ ●●●●●○  Étape 5/6 : Choix des boissons (optionnel)         │
│                                                             │
│  [SOFTS] [LES VINS] [BIÈRES BOUTEILLE] [LES FÛTS]         │
│                                                             │
│  🌟 NOS SUGGESTIONS                                        │
│  ┌─────────────────────────────────────────────────────┐   │
│  │  [Photo] Coca-Cola                                  │   │
│  │  50cl: 3€  [-] 0 [+]    25cl: 2€  [-] 0 [+]       │   │
│  │                                                     │   │
│  │  [Photo] Jus d'orange                               │   │
│  │  25cl: 2.5€  [-] 0 [+]                             │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  📋 TOUS LES SOFTS                                         │
│  ┌─────────────────────────────────────────────────────┐   │
│  │  ☐ [Photo] Coca-Cola                               │   │
│  │     50cl: 3€  [-] 0 [+]    25cl: 2€  [-] 0 [+]    │   │
│  │                                                     │   │
│  │  ☐ [Photo] Sprite                                  │   │
│  │     33cl: 2.5€  [-] 0 [+]                          │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  💰 Montant estimatif : 680 € (montant indicatif)         │
│                                                             │
│  [← Étape précédente]  [Passer cette étape →]             │
└─────────────────────────────────────────────────────────────┘
```

#### **Règles Métier**
- **Étape optionnelle** : Bouton "Passer cette étape"
- **Onglets** : SOFTS, LES VINS, BIÈRES BOUTEILLE, LES FÛTS
- **Suggestions** : Produits marqués "suggestion du moment" en avant
- **Contenances multiples** : 25cl, 50cl, 75cl avec prix différents
- **Fûts** : 10L/20L avec catégories (BLONDES, BLANCHES, IPA, AMBRÉES)

#### **Fonctionnalités**
- **Navigation par onglets** avec contenu dynamique
- **Section suggestions** mise en avant
- **Sélecteurs multiples** pour contenances
- **Affichage conditionnel** des contenances disponibles
- **Degré d'alcool** affiché si renseigné
- **Possibilité de passer** l'étape

---

### **ÉTAPE 6 : COORDONNÉES/CONTACT**

#### **Design**
```
┌─────────────────────────────────────────────────────────────┐
│ ●●●●●●  Étape 6/6 : Vos coordonnées                        │
│                                                             │
│  👤 Prénom *              👤 Nom *                         │
│  [____________]           [____________]                    │
│                                                             │
│  📧 Email *               📞 Téléphone *                   │
│  [____________]           [____________]                    │
│                                                             │
│  💬 Questions / Commentaires                               │
│  [________________________________________________]        │
│  [1 question, 1 souhait, n'hésitez pas de nous en]       │
│  [faire part, on en parle, on....]                       │
│                                                             │
│  📋 RÉCAPITULATIF DE VOTRE DEMANDE                        │
│  ┌─────────────────────────────────────────────────────┐   │
│  │  Service : Restaurant                               │   │
│  │  Date : 15/03/2025                                 │   │
│  │  Convives : 15 personnes                           │   │
│  │  Durée : 3H                                        │   │
│  │  Plats : 15x Hot-Dog Classic, 15x Frites          │   │
│  │  Buffets : Grilled Cheese (8), Salade César (7)   │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  💰 TOTAL ESTIMÉ : 680 €                                  │
│                                                             │
│  [🎯 OBTENIR MON DEVIS ESTIMATIF]                         │
└─────────────────────────────────────────────────────────────┘
```

#### **Fonctionnalités**
- **Formulaire complet** avec validation
- **Récapitulatif détaillé** de tous les choix
- **Prix total** calculé et affiché
- **Soumission** avec génération PDF et email
- **Message de confirmation** personnalisable

---

## 🚛 **PARCOURS REMORQUE (7 ÉTAPES)**

### **ÉTAPE 1 : POURQUOI PRIVATISER NOTRE REMORQUE ?**

#### **Design**
```
┌─────────────────────────────────────────────────────────────┐
│ ●○○○○○○  Étape 1/7 : Pourquoi privatiser notre remorque ? │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │              Comment ça fonctionne ?                │   │
│  │                                                     │   │
│  │  1. Forfait de base                                 │   │
│  │  2. Choix du formule repas (personnalisable)       │   │
│  │  3. Choix des boissons (optionnel)                 │   │
│  │  4. Choix des options (optionnel)                  │   │
│  │  5. Coordonnées/Contact                             │   │
│  │                                                     │   │
│  │           [COMMENCER MON DEVIS]                     │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

---

### **ÉTAPE 2 : FORFAIT DE BASE REMORQUE**

#### **Design**
```
┌─────────────────────────────────────────────────────────────┐
│ ●●○○○○○  Étape 2/7 : Forfait de base                       │
│                                                             │
│  📅 Date souhaitée événement    👥 Nombre de convives      │
│  [  --/--/----  ]              [    25    ] personnes     │
│  Sélectionnez une date future   À partir de 20 personnes   │
│                                 au delà de 50p +150€       │
│                                                             │
│  ⏰ Durée souhaitée événement   📍 Code postal événement   │
│  [ 3H ▼ ] (2H/3H/4H/5H)        [67000]                   │
│  min 2H, max 5H (+50€/H)       Rayon maximum 150 km       │
│                                 30-50km: +20€, 50-100km: +70€│
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │     FORFAIT DE BASE PRIVATISATION REMORQUE BLOCK   │   │
│  │                                                     │   │
│  │  ✓ 3H de privatisation (service inclus...)         │   │
│  │  ✓ Notre équipe salle + cuisine assurant...        │   │
│  │  ✓ Déplacement et installation remorque...         │   │
│  │  ✓ Fourniture vaisselle jetable recyclable         │   │
│  │  ✓ Fourniture verrerie (si boissons)               │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  💰 Montant estimatif : 470 € (montant indicatif)         │
│  (300€ base + 50€ durée + 120€ distance)                   │
└─────────────────────────────────────────────────────────────┘
```

#### **Règles Métier Spécifiques**
- **Convives** : Min 20, Max 100, +150€ au-delà de 50 personnes
- **Durée** : 2H à 5H, supplément +50€/H dès 3H
- **Distance** : Calcul automatique selon code postal
  - 0-30km : Gratuit
  - 30-50km : +20€
  - 50-100km : +70€
  - 100-150km : +120€
  - Max 150km
- **Prix base** : 300€ (configurable admin)

---

### **ÉTAPES 3, 4, 5 : IDENTIQUES AU RESTAURANT**
- **Étape 3** : Formules repas (même logique)
- **Étape 4** : Buffets (même logique)
- **Étape 5** : Boissons (SANS les fûts, qui sont dans les options)

---

### **ÉTAPE 6 : CHOIX DES OPTIONS (OPTIONNEL)**

#### **Design**
```
┌─────────────────────────────────────────────────────────────┐
│ ●●●●●●○  Étape 6/7 : Choix des options (optionnel)         │
│                                                             │
│  ⚡ Information :                                           │
│  Ces options sont spécifiques à la remorque Block          │
│  et sont entièrement optionnelles.                         │
│                                                             │
│  🍺 MISE À DISPO TIREUSE 50 €                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │  ☐ Ajouter la tireuse à bière                       │   │
│  │     Descriptif + mention (fûts non inclus à choisir)│   │
│  │                                                     │   │
│  │     ▼ SÉLECTION DES FÛTS (si tireuse sélectionnée) │   │
│  │     [BLONDES] [BLANCHES] [IPA] [AMBRÉES]           │   │
│  │                                                     │   │
│  │     ☐ [Photo] Kronenbourg Blonde                   │   │
│  │        10L: 45€  [-] 0 [+]    20L: 80€  [-] 0 [+] │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  🎮 INSTALLATION JEUX 70 €                                │
│  ┌─────────────────────────────────────────────────────┐   │
│  │  ☐ Ajouter l'installation jeux                     │   │
│  │     Descriptif avec listing des jeux disponibles   │   │
│  │                                                     │   │
│  │     ▼ SÉLECTION DES JEUX (si option sélectionnée)  │   │
│  │     ☐ Château gonflable  15€                       │   │
│  │     ☐ Toboggan gonflable 12€                       │   │
│  │     ☐ Jeux en bois       8€                        │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  💰 Montant estimatif : 470 € (montant indicatif)         │
│                                                             │
│  [← Étape précédente]  [Passer cette étape →]             │
└─────────────────────────────────────────────────────────────┘
```

#### **Règles Métier**
- **Tireuse** : 50€ fixe + sélection fûts obligatoire si activée
- **Jeux** : 70€ fixe + sélection jeux individuels avec prix
- **Fûts** : Catégories avec 10L/20L, prix différents
- **Étape optionnelle** : Possibilité de passer

---

### **ÉTAPE 7 : COORDONNÉES/CONTACT REMORQUE**
Identique au restaurant avec récapitulatif adapté incluant les options.

---

## 🔧 **RÈGLES MÉTIER COMPLÈTES**

### **Validation Globale**
- **Dates** : Uniquement futures, vérification disponibilités
- **Quantités** : Respect des minimums par personne
- **Suppléments** : Quantité ≤ produit principal
- **Prix** : Calcul temps réel avec tous les suppléments

### **Calculs de Prix**
```
RESTAURANT:
Base: 200€
+ Durée: (heures - 2) × 50€
+ Produits: Σ(quantité × prix)
+ Suppléments: Σ(quantité × prix_supplément)

REMORQUE:
Base: 300€
+ Personnel: +150€ si > 50 convives
+ Durée: (heures - 2) × 50€
+ Distance: selon zones
+ Produits: Σ(quantité × prix)
+ Options: 50€ (tireuse) + 70€ (jeux) + prix individuels
```

### **Connexions Admin**
- **Produits** : Tables existantes par catégorie
- **Options** : Page admin jeux existante
- **Textes** : Options unifiées configurables
- **Prix** : Tarification admin modifiable

---

## 🎯 **FONCTIONNALITÉS TECHNIQUES**

### **Interface**
- **Responsive** : Mobile-first, breakpoints 320/768/1024px
- **Animations** : Transitions fluides, loading states
- **Validation** : Temps réel avec messages français
- **Navigation** : Barre progression + boutons prev/next

### **Backend**
- **AJAX** : Chargement dynamique des étapes
- **Sécurité** : Nonces WordPress, sanitisation
- **Base de données** : Connexion tables existantes
- **Email/PDF** : Génération automatique devis

### **UX/UI**
- **Sélecteurs visuels** : +/- pour quantités
- **Feedback visuel** : États hover/active/disabled
- **Messages clairs** : Erreurs avec icônes et explications
- **Calculateur sticky** : Prix toujours visible

---

## ✅ **CHECKLIST DE DÉVELOPPEMENT**

### **Structure**
- [ ] Shortcode `[restaurant_booking_form_v3]` fonctionnel
- [ ] CSS isolé avec namespace `.rbf-v3-`
- [ ] JavaScript moderne avec gestion d'erreurs
- [ ] Gestionnaire AJAX complet

### **Étapes Restaurant**
- [ ] Étape 1 : Card explicative
- [ ] Étape 2 : Forfait base avec validation
- [ ] Étape 3 : Formules repas avec règles
- [ ] Étape 4 : Buffets avec suppléments
- [ ] Étape 5 : Boissons avec onglets
- [ ] Étape 6 : Coordonnées + récapitulatif

### **Étapes Remorque**
- [ ] Étape 1 : Card explicative remorque
- [ ] Étape 2 : Forfait base + distance
- [ ] Étapes 3-5 : Identiques restaurant
- [ ] Étape 6 : Options (tireuse + jeux)
- [ ] Étape 7 : Coordonnées + récapitulatif

### **Fonctionnalités**
- [ ] Connexion toutes tables admin
- [ ] Validation toutes règles métier
- [ ] Calcul prix temps réel complet
- [ ] Génération PDF + email
- [ ] Responsive parfait
- [ ] Tests sur tous navigateurs

---

**Ce cahier des charges détaille exactement toutes les fonctionnalités à implémenter pour un formulaire V3 complet et conforme aux spécifications.**

**Dois-je procéder à l'implémentation complète selon ce cahier des charges ?**

