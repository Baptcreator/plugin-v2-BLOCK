# 🔧 DEBUG PRODUITS FORMULAIRE V3

## 🎯 **CORRECTIONS APPLIQUÉES**

### **Problème Identifié**
- Les produits ne s'affichaient pas dans l'étape 3
- Pas de sélecteurs de quantité visibles
- Sections vides (DOG/CROQ, Mini Boss, Accompagnements)

### **Solutions Implémentées**

#### **1. Affichage Direct des Produits**
- ✅ **Produits signature** : Affichage par défaut des produits DOG
- ✅ **Fallback** : Produits de démonstration si base de données vide
- ✅ **Sélecteurs quantité** : Boutons +/- fonctionnels

#### **2. Accompagnements Simplifiés**
- ✅ **Cards modernes** : Design cohérent avec charte Block
- ✅ **Sélecteurs visuels** : Quantité avec boutons +/-
- ✅ **Options frites** : Chimichurri +1€ et sauces
- ✅ **Animation** : Affichage conditionnel des options

#### **3. JavaScript Amélioré**
- ✅ **Gestion quantités** : Logique complète +/-
- ✅ **Validation** : Limites min/max respectées
- ✅ **Options frites** : Affichage automatique si quantité > 0
- ✅ **Initialisation** : Sélecteurs activés au chargement

---

## 🧪 **TESTS À EFFECTUER**

### **Étape 3 : Formules Repas**

#### **Plats Signature**
1. **Vérifier** que les produits DOG s'affichent par défaut
2. **Tester** le changement DOG ↔ CROQ
3. **Utiliser** les boutons +/- pour ajuster les quantités
4. **Vérifier** que les quantités se sauvegardent

#### **Accompagnements**
1. **Voir** les cards Salade et Frites
2. **Tester** les sélecteurs de quantité
3. **Ajouter des frites** et vérifier que les options apparaissent
4. **Tester** Chimichurri +1€ et les sauces

#### **Mini Boss**
1. **Cocher** "Ajouter le menu Mini Boss"
2. **Vérifier** que les produits s'affichent
3. **Tester** les sélecteurs de quantité

---

## 🎨 **DESIGN ATTENDU**

### **Structure Visuelle**
```
🍽️ CHOIX DU PLAT SIGNATURE
minimum 1 plat par personne

● 🌭 DOG    ○ 🥪 CROQ

┌─────────────────────────────────────┐
│ [Photo] Hot-Dog Classic       12€   │
│ Notre hot-dog signature             │
│                    [-] 10 [+]       │
│                                     │
│ [Photo] Hot-Dog Spicy         14€   │
│ Version épicée...  [-] 0  [+]       │
└─────────────────────────────────────┘

👑 MENU MINI BOSS
Optionnel - Pour les plus petits

☐ Ajouter le menu Mini Boss

🥗 ACCOMPAGNEMENTS
mini 1/personne

┌─────────────────┐  ┌─────────────────┐
│ SALADE     4€   │  │ FRITES     4€   │
│    [-] 0 [+]    │  │    [-] 0 [+]    │
└─────────────────┘  │                 │
                     │ ▼ Options frites │
                     │ Chimichurri +1€  │
                     │    [-] 0 [+]     │
                     │ Sauce Ketchup    │
                     │    [-] 0 [+]     │
                     └─────────────────┘
```

---

## 🔍 **VÉRIFICATIONS TECHNIQUES**

### **Console JavaScript (F12)**
```javascript
// Vérifier que la classe est chargée
console.log(window.RestaurantBookingFormV3);

// Tester un sélecteur de quantité
$('.rbf-v3-qty-plus').first().click();

// Vérifier les données du formulaire
// (dans la console, après avoir ajouté des produits)
```

### **Éléments DOM**
- ✅ `.rbf-v3-signature-products-grid` contient des produits
- ✅ `.rbf-v3-accompaniments-grid` contient des cards
- ✅ `.rbf-v3-qty-btn` boutons fonctionnels
- ✅ `.rbf-v3-frites-options` s'affiche conditionnellement

### **Requêtes AJAX**
- ✅ `rbf_v3_load_signature_products` fonctionne
- ✅ Pas d'erreurs 500 dans l'onglet Network
- ✅ Réponses JSON valides

---

## 🚨 **PROBLÈMES POTENTIELS**

### **Si les produits ne s'affichent toujours pas :**
1. **Vider le cache** navigateur (Ctrl+Shift+R)
2. **Vérifier** que les fichiers V3 sont bien chargés
3. **Contrôler** les erreurs JavaScript dans la console
4. **Tester** avec un thème par défaut

### **Si les sélecteurs ne fonctionnent pas :**
1. **Vérifier** que jQuery est chargé
2. **Contrôler** les conflits avec d'autres plugins
3. **Tester** en mode navigation privée

### **Si les styles sont cassés :**
1. **Forcer** le rechargement CSS
2. **Vérifier** que le CSS V3 se charge après les autres
3. **Contrôler** les conflits de namespace

---

## ✅ **RÉSULTAT ATTENDU**

Après ces corrections, l'étape 3 devrait afficher :

1. **Produits signature** avec photos et sélecteurs
2. **Accompagnements** dans des cards modernes
3. **Sélecteurs de quantité** fonctionnels avec +/-
4. **Options frites** qui apparaissent automatiquement
5. **Design cohérent** avec la charte Block

**🎯 Le formulaire V3 devrait maintenant être pleinement fonctionnel pour l'étape 3 !**

