# 📍 Gestion des Zones de Livraison - Pizza Club

## Configuration actuelle

Les zones de livraison sont configurées dans le fichier `config.js`.

### Codes postaux actuellement desservis :

```javascript
deliveryZones: [
    '97410', // Saint-Pierre
    '97432', // Ravine des Cabris
    '97420', // Le Port
    '97438', // Sainte-Marie
    '97400', // Saint-Denis
    '97419', // La Possession
    '97417', // La Montagne
    '97460', // Saint-Paul
    '97411', // Bois d'Olives
]
```

---

## 🔧 Comment ajouter/modifier les zones

### 1. Ouvrir le fichier `config.js`

Chercher la section `delivery` :

```javascript
delivery: {
    fee: 0,
    freeDeliveryThreshold: 0,
    estimatedTime: { ... },
    deliveryZones: [
        // Les codes postaux ici
    ]
}
```

### 2. Ajouter un nouveau code postal

Simplement ajouter le code postal dans la liste :

```javascript
deliveryZones: [
    '97410', // Saint-Pierre
    '97432', // Ravine des Cabris
    '97430', // LE TAMPON - NOUVEAU ✨
    // Ajoutez ici
]
```

### 3. Retirer un code postal

Supprimer ou commenter la ligne :

```javascript
deliveryZones: [
    '97410', // Saint-Pierre
    // '97432', // Ravine des Cabris - DÉSACTIVÉ
]
```

---

## 📋 Liste complète des codes postaux de La Réunion

Pour référence, voici tous les codes postaux :

| Code | Commune |
|------|---------|
| **97400** | Saint-Denis |
| **97410** | Saint-Pierre |
| **97411** | Bois d'Olives (Saint-Pierre) |
| **97412** | Bras-Panon |
| **97413** | Cilaos |
| **97414** | Entre-Deux |
| **97415** | La Plaine-des-Palmistes |
| **97416** | La Rivière |
| **97417** | La Montagne |
| **97418** | Le Tampon |
| **97419** | La Possession |
| **97420** | Le Port |
| **97421** | La Rivière-Saint-Louis |
| **97422** | La Saline |
| **97423** | Le Guillaume (Petite-Île) |
| **97424** | Piton Saint-Leu |
| **97425** | Les Avirons |
| **97426** | Les Trois-Bassins |
| **97427** | L'Étang-Salé |
| **97429** | Petite-Île |
| **97430** | Le Tampon |
| **97431** | La Plaine-des-Cafres |
| **97432** | Ravine des Cabris |
| **97433** | Salazie |
| **97434** | La Saline-les-Bains |
| **97435** | Saint-Gilles-les-Hauts |
| **97436** | Saint-Leu |
| **97437** | Sainte-Anne |
| **97438** | Sainte-Marie |
| **97439** | Sainte-Rose |
| **97440** | Saint-André |
| **97441** | Sainte-Suzanne |
| **97442** | Saint-Philippe |
| **97450** | Saint-Louis |
| **97460** | Saint-Paul |
| **97470** | Saint-Benoît |
| **97480** | Saint-Joseph |

---

## 🚫 Ce qui se passe quand un client est hors zone

1. Le client remplit le formulaire avec son code postal
2. Le système vérifie automatiquement si le code postal est dans la liste
3. Si **hors zone** :
   - ❌ Le formulaire ne valide pas
   - 💬 Message affiché : *"😔 Désolé, nous ne livrons pas encore dans votre secteur. Vous pouvez commander en mode "À emporter"."*
   - 📋 Liste des zones desservies affichée
   - 🔄 Le client peut changer pour "À emporter"

---

## 💡 Exemples d'utilisation

### Exemple 1 : Livraison uniquement à Saint-Pierre

```javascript
deliveryZones: [
    '97410', // Saint-Pierre
    '97411', // Bois d'Olives
]
```

### Exemple 2 : Livraison Sud de l'île

```javascript
deliveryZones: [
    '97410', // Saint-Pierre
    '97411', // Bois d'Olives
    '97418', // Le Tampon
    '97430', // Le Tampon
    '97450', // Saint-Louis
    '97421', // La Rivière-Saint-Louis
    '97414', // Entre-Deux
    '97427', // L'Étang-Salé
]
```

### Exemple 3 : Livraison Nord + Ouest

```javascript
deliveryZones: [
    '97400', // Saint-Denis
    '97417', // La Montagne
    '97419', // La Possession
    '97420', // Le Port
    '97460', // Saint-Paul
    '97434', // La Saline-les-Bains
    '97435', // Saint-Gilles-les-Hauts
]
```

### Exemple 4 : Livraison TOUTE l'île

```javascript
// Désactiver la vérification en laissant le tableau vide
deliveryZones: []

// OU lister tous les codes postaux
```

---

## 🎨 Personnaliser le message d'erreur

Dans `config.js`, modifier :

```javascript
outOfZoneMessage: '😔 Désolé, nous ne livrons pas encore dans votre secteur. Vous pouvez commander en mode "À emporter".'
```

Par exemple :

```javascript
outOfZoneMessage: '🚫 Zone non desservie actuellement. Appelez-nous au 0262 66 82 30 pour plus d\'informations.'
```

---

## 🔄 Mettre à jour les zones

1. Modifier `config.js`
2. Sauvegarder
3. Commit et push :
   ```bash
   git add config.js
   git commit -m "📍 Mise à jour des zones de livraison"
   git push origin main
   ```

---

## ⚠️ Important

- Les codes postaux doivent être **exacts** (5 chiffres)
- Mettre des **guillemets** : `'97410'` pas `97410`
- Ne pas oublier les **virgules** entre chaque code
- Tester après chaque modification

---

## 🧪 Tester la fonctionnalité

1. Aller sur le site
2. Ajouter un article au panier
3. Choisir "Livraison"
4. Remplir avec un code postal **dans la zone** → ✅ Doit passer
5. Remplir avec un code postal **hors zone** → ❌ Doit bloquer avec message

---

## 📞 Support

En cas de problème, vérifier :
- Que le code postal est bien dans `deliveryZones`
- Qu'il n'y a pas d'erreur de syntaxe (virgules, guillemets)
- Que le fichier `config.js` est bien sauvegardé et en ligne
