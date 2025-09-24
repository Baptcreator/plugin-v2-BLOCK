# 🧪 TEST FORMULAIRE V3 - GUIDE DE VÉRIFICATION

## 🎯 **OBJECTIF**

Vérifier que le nouveau formulaire V3 fonctionne correctement selon le cahier des charges.

---

## ✅ **CHECKLIST DE TEST**

### **1. ÉTAPE 0 : SÉLECTION SERVICE**
- [ ] **Affichage** : 2 cards (Restaurant + Remorque) visibles
- [ ] **Design** : Couleurs Block, polices FatKat/Roboto
- [ ] **Interaction** : Hover effects sur les cards
- [ ] **Sélection** : Clic sur "Choisir" fonctionne
- [ ] **Transition** : Passage automatique à l'étape 1

### **2. ÉTAPE 1 : POURQUOI PRIVATISER**
- [ ] **Affichage** : Card explicative avec liste des étapes
- [ ] **Contenu** : Texte adapté selon service (restaurant/remorque)
- [ ] **Bouton** : "COMMENCER MON DEVIS" fonctionne
- [ ] **Navigation** : Passage à l'étape 2

### **3. ÉTAPE 2 : FORFAIT DE BASE**
- [ ] **Champs** : Date, convives, durée (+ code postal pour remorque)
- [ ] **Validation** : Limites min/max respectées
- [ ] **Calculateur** : Apparition du prix estimatif
- [ ] **Card forfait** : Description détaillée affichée
- [ ] **Prix** : Calcul automatique des suppléments

### **4. ÉTAPE 3 : FORMULES REPAS**
- [ ] **Plats signature** : Choix DOG/CROQ fonctionnel
- [ ] **Produits** : Chargement dynamique depuis DB
- [ ] **Sélecteurs** : Boutons +/- fonctionnels
- [ ] **Mini Boss** : Toggle et affichage conditionnel
- [ ] **Accompagnements** : Quantités minimales respectées
- [ ] **Options frites** : Chimichurri et sauces

### **5. RESPONSIVE DESIGN**
- [ ] **Desktop** : Affichage parfait 1200px+
- [ ] **Tablette** : Adaptation 768px-1023px
- [ ] **Mobile** : Touch-friendly 320px-767px
- [ ] **Boutons** : Taille minimum 44px sur mobile

### **6. FONCTIONNALITÉS AVANCÉES**
- [ ] **Validation temps réel** : Messages d'erreur français
- [ ] **Calcul prix** : Mise à jour automatique
- [ ] **Sauvegarde** : Données conservées entre étapes
- [ ] **Messages** : Feedback utilisateur clair

---

## 🔧 **COMMENT TESTER**

### **Installation**
1. Vérifier que tous les fichiers V3 sont présents
2. S'assurer que le plugin est activé
3. Placer le shortcode `[restaurant_booking_form_v3]` sur une page

### **Test Étape par Étape**
1. **Ouvrir la page** avec le formulaire
2. **Vérifier l'affichage** initial (étape 0)
3. **Sélectionner "Restaurant"** et vérifier la transition
4. **Remplir chaque étape** en testant toutes les fonctionnalités
5. **Tester sur mobile** avec les outils développeur
6. **Vérifier les calculs** de prix à chaque étape

### **Test des Erreurs**
1. **Laisser des champs vides** et vérifier les messages
2. **Dépasser les limites** (convives, quantités)
3. **Tester la navigation** arrière/avant
4. **Vérifier la validation** des règles métier

---

## 🐛 **PROBLÈMES POTENTIELS**

### **JavaScript**
- Erreurs dans la console navigateur
- Sélecteurs de quantité non fonctionnels
- Chargement AJAX qui échoue
- Validation qui ne fonctionne pas

### **CSS**
- Styles non appliqués (cache)
- Responsive cassé sur certains écrans
- Couleurs incorrectes
- Animations qui ne fonctionnent pas

### **PHP/Base de Données**
- Produits non chargés depuis la DB
- Options admin non récupérées
- Erreurs AJAX (vérifier les logs)
- Nonces de sécurité invalides

---

## 🔍 **OUTILS DE DEBUG**

### **Console Navigateur (F12)**
```javascript
// Vérifier que la classe est chargée
console.log(window.RestaurantBookingFormV3);

// Vérifier la configuration
console.log(rbfV3Config);

// Voir les erreurs JavaScript
// (onglet Console)
```

### **Réseau (Network)**
- Vérifier les requêtes AJAX
- S'assurer que les CSS/JS se chargent
- Contrôler les réponses du serveur

### **Logs WordPress**
- Activer WP_DEBUG dans wp-config.php
- Vérifier les logs d'erreur PHP
- Contrôler les requêtes de base de données

---

## ✅ **CRITÈRES DE RÉUSSITE**

Le formulaire V3 est considéré comme fonctionnel si :

1. **Toutes les étapes** s'affichent correctement
2. **La navigation** fonctionne sans erreur
3. **Les produits** se chargent depuis la base de données
4. **Les calculs** de prix sont corrects
5. **Le responsive** fonctionne sur tous les écrans
6. **La validation** respecte toutes les règles métier
7. **Aucune erreur** JavaScript dans la console
8. **Le design** respecte la charte graphique Block

---

## 📞 **EN CAS DE PROBLÈME**

### **Vérifications Rapides**
1. **Vider le cache** navigateur et WordPress
2. **Vérifier les permissions** de fichiers
3. **S'assurer** que jQuery est chargé
4. **Contrôler** que les tables de base de données existent

### **Debug Avancé**
1. **Activer le mode debug** WordPress
2. **Vérifier les logs** serveur
3. **Tester avec un thème** par défaut
4. **Désactiver les autres plugins** temporairement

---

**🎯 Une fois tous les tests validés, le formulaire V3 sera prêt pour la production !**

