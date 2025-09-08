# 📋 DOCUMENTATION - FORMULAIRE BLOCK UNIFIÉ V2

## 🎯 **SYSTÈME ENTIÈREMENT RECONSTRUIT**

Le **Formulaire Block Unifié V2** est une reconstruction complète selon le cahier des charges, avec isolation CSS totale et connexion aux Options Unifiées.

---

## 🚀 **UTILISATION**

### **Widget Elementor**
Dans Elementor, utilisez le widget :
**"Formulaire de devis Block Unifié V2"**

### **Shortcode** (si nécessaire)
```php
[restaurant_booking_form_block_unified]
```

---

## 📁 **NOUVEAUX FICHIERS CRÉÉS**

### **CSS Isolé Block**
- `assets/css/quote-form-block.css` (1000+ lignes)
- Isolation complète avec namespace `.restaurant-plugin-`
- Charte graphique Block officielle
- Responsive mobile-first intégré

### **JavaScript Avancé**
- `assets/js/quote-form-block-unified.js` (1500+ lignes)  
- Classe `RestaurantPluginFormBlock`
- Gestion complète multi-étapes
- Validation temps réel selon règles

### **Widget Elementor V2**
- `elementor/widgets/quote-form-block-unified-widget.php`
- Interface de configuration complète
- Connexion aux Options Unifiées
- Prévisualisation Elementor

### **Gestionnaire AJAX**
- `public/class-ajax-handler-block-unified.php`
- Actions AJAX spécialisées
- Connexion base de données
- Calculs de prix temps réel

---

## 📋 **STRUCTURE EXACTE CAHIER DES CHARGES**

### **RESTAURANT (6 étapes)**
1. **Sélection service** → Cards Restaurant vs Remorque
2. **Étape 1** → "Pourquoi privatiser notre restaurant ?" + [COMMENCER MON DEVIS]
3. **Étape 2** → FORFAIT BASE (date, convives, durée) + montant estimatif
4. **Étape 3** → FORMULES REPAS (DOG/CROQ + Mini Boss + accompagnements)
5. **Étape 4** → BUFFETS (3 choix: salé, sucré, les deux)
6. **Étape 5** → BOISSONS (optionnel, avec fûts)
7. **Étape 6** → COORDONNÉES + [OBTENIR MON DEVIS ESTIMATIF]

### **REMORQUE (7 étapes)**
1. **Sélection service** → Cards Restaurant vs Remorque
2. **Étape 1** → "Pourquoi privatiser notre remorque ?" + [COMMENCER MON DEVIS]
3. **Étape 2** → FORFAIT BASE (date, convives, durée, **code postal**) + montant estimatif
4. **Étape 3** → FORMULES REPAS (identique restaurant)
5. **Étape 4** → BUFFETS (identique restaurant)
6. **Étape 5** → BOISSONS (optionnel, **SANS fûts**)
7. **Étape 6** → OPTIONS (TIREUSE 50€ + JEUX 70€)
8. **Étape 7** → COORDONNÉES + [OBTENIR MON DEVIS ESTIMATIF]

---

## ⚙️ **CONNEXION AUX OPTIONS UNIFIÉES**

Toutes les règles sont récupérées dynamiquement depuis :
`wp-admin/admin.php?page=restaurant-booking-options-unified`

### **Règles Automatiques**
- ✅ **Convives** : Restaurant 10-30, Remorque 20-100
- ✅ **Durée** : 2H inclus, +50€/h supplémentaire  
- ✅ **Distance remorque** : 0-30km gratuit, puis suppléments par zone
- ✅ **Plats signature** : min 1/personne
- ✅ **Accompagnements** : min 1/personne
- ✅ **Buffet salé** : min 1/pers + min 2 recettes différentes
- ✅ **Buffet sucré** : min 1/pers + min 1 plat
- ✅ **Supplément +50 convives** : +150€ (remorque)
- ✅ **Options** : Tireuse 50€, Jeux 70€

---

## 🎨 **DESIGN BLOCK ISOLÉ**

### **Charte Graphique Officielle**
```css
:root {
  --restaurant-primary: #243127;    /* Vert foncé */
  --restaurant-secondary: #FFB404;  /* Orange/jaune */
  --restaurant-accent: #EF3D1D;     /* Rouge */
  --restaurant-light: #F6F2E7;      /* Beige clair */
}
```

### **Typographie**
- **Titres** : Fatkat 32px
- **Sous-titres** : Fatkat 24px
- **Texte** : Roboto 16px
- **Boutons** : Fatkat 18px

### **Border-radius Universel**
- **20px** sur TOUS les éléments (boutons, cards, inputs, etc.)

### **Isolation CSS Complète**
- ✅ Namespace `.restaurant-plugin-` sur TOUT
- ✅ Reset CSS intégré
- ✅ Aucun impact des styles WordPress/Elementor
- ✅ Variables CSS configurables

---

## 📱 **RESPONSIVE MOBILE-FIRST**

### **Breakpoints**
- **Mobile** : 320px - 767px (touch-friendly 44px)
- **Tablette** : 768px - 1023px
- **Desktop** : 1024px+

### **Adaptations**
- ✅ Service cards en colonne sur mobile
- ✅ Formulaires adaptés tactiles
- ✅ Navigation simplifiée
- ✅ Boutons touch-friendly
- ✅ Prix calculator responsive

---

## 🔧 **ACTIONS AJAX DISPONIBLES**

### **Chargement Étapes**
- `restaurant_plugin_load_step` → Génère le HTML d'une étape

### **Calculs Temps Réel**
- `restaurant_plugin_calculate_price` → Prix en temps réel
- `restaurant_plugin_calculate_distance` → Distance remorque

### **Données Produits**
- `restaurant_plugin_get_signature_products` → Produits DOG/CROQ

### **Validations**
- `restaurant_plugin_check_date` → Disponibilité dates

### **Soumission**
- `restaurant_plugin_submit_quote` → Création devis + email

---

## 🔒 **SÉCURITÉ ET VALIDATION**

### **Sécurité**
- ✅ Nonces WordPress sur toutes requêtes AJAX
- ✅ Sanitisation complète des données
- ✅ Validation côté client ET serveur
- ✅ Protection contre injections

### **Validation Selon Règles**
- ✅ Quantités minimales respectées
- ✅ Codes postaux valides (remorque)
- ✅ Dates futures uniquement
- ✅ Emails et téléphones valides

---

## 💾 **COMPATIBILITÉ ET FALLBACKS**

### **Classes Existantes**
- ✅ `RestaurantBooking_Options_Unified_Admin` → Options
- ✅ `RestaurantBooking_Quote_Calculator_V2` → Prix
- ✅ `RestaurantBooking_Quote` → Création devis
- ✅ `RestaurantBooking_Email` → Envoi emails
- ✅ `RestaurantBooking_Calendar` → Disponibilités

### **Fallbacks Robustes**
- ✅ Valeurs par défaut si classes manquantes
- ✅ Calculs simples si calculateur V2 absent
- ✅ Insertion directe BDD si classe Quote absente
- ✅ Email simple si classe Email absente

---

## 🎯 **FONCTIONNALITÉS AVANCÉES**

### **Multi-étapes Intelligent**
- ✅ Navigation fluide avec validation
- ✅ Sauvegarde automatique des données
- ✅ Barre de progression dynamique
- ✅ Retour en arrière possible

### **Sélection Produits Complexe**
- ✅ Plats signature DOG/CROQ dynamiques
- ✅ Menu Mini Boss optionnel
- ✅ Accompagnements avec validation min
- ✅ Buffets 3 choix selon règles
- ✅ Boissons sections dépliables

### **Calculateur Prix Temps Réel**
- ✅ Mise à jour automatique
- ✅ Détail des suppléments
- ✅ Animation des changements
- ✅ Position sticky/bottom configurable

### **Options Remorque Spéciales**
- ✅ TIREUSE 50€ + sélection fûts obligatoire
- ✅ JEUX 70€ + sélection jeux
- ✅ Calcul distance automatique
- ✅ Suppléments par zone

---

## 🎉 **AVANTAGES DU SYSTÈME V2**

### **Pour l'Admin**
- ✅ **Configuration centralisée** → Options Unifiées
- ✅ **Règles modifiables** → Sans développeur
- ✅ **Textes personnalisables** → Interface complète
- ✅ **Prix ajustables** → En temps réel

### **Pour l'Utilisateur**
- ✅ **Interface moderne** → Charte Block
- ✅ **Navigation intuitive** → Étapes claires
- ✅ **Validation temps réel** → Erreurs immédiates
- ✅ **Prix transparent** → Calculs visibles
- ✅ **Mobile parfait** → Touch-friendly

### **Pour le Développeur**
- ✅ **Code propre** → Architecture moderne
- ✅ **Maintenabilité** → Classes séparées
- ✅ **Extensibilité** → Hooks et filtres
- ✅ **Documentation** → Commentaires complets

---

## 📞 **SUPPORT ET MAINTENANCE**

### **Logs et Debug**
- ✅ `RestaurantBooking_Logger` → Erreurs tracées
- ✅ Mode debug activable → Console détaillée
- ✅ Validation étapes → Messages explicites

### **Compatibilité**
- ✅ **WordPress** : 5.0+
- ✅ **PHP** : 7.4+
- ✅ **Elementor** : 3.0+
- ✅ **jQuery** : 3.0+

---

## 🔄 **MIGRATION ET COEXISTENCE**

### **Indépendance Totale**
- ✅ **Nouveau widget** → Indépendant de l'ancien
- ✅ **Nouveaux fichiers** → Pas de conflit
- ✅ **Namespace CSS** → Isolation complète
- ✅ **Actions AJAX** → Préfixes spécifiques

### **Coexistence Possible**
- ✅ Ancien et nouveau systèmes peuvent coexister
- ✅ Migration progressive possible
- ✅ Tests A/B réalisables
- ✅ Rollback sécurisé

---

## ✅ **SYSTÈME 100% FONCTIONNEL**

Le **Formulaire Block Unifié V2** est maintenant :

1. ✅ **Entièrement reconstruit** selon le cahier des charges
2. ✅ **CSS isolé** avec charte graphique Block
3. ✅ **Responsive parfait** mobile-first
4. ✅ **Connecté aux Options Unifiées** dynamiquement
5. ✅ **Widget Elementor** configurable
6. ✅ **AJAX complet** avec fallbacks
7. ✅ **Sécurisé et validé** selon les règles
8. ✅ **Prêt pour production** immédiatement

**🎯 Utilisez le widget "Formulaire de devis Block Unifié V2" dans Elementor !**

