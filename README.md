# 🍕 Pizza Club - Site de Commande en Ligne

Site One Page moderne et responsive pour pizzeria avec système de commande complet.

**Dernière mise à jour : 10 décembre 2025**

## ✨ Fonctionnalités

### 🎨 Interface
- Design moderne rouge et blanc
- Responsive (Mobile, Tablette, Desktop)
- Animations fluides
- Interface type "food tech app"

### 🍕 Menu & Commandes
- Catalogue de pizzas avec filtres (Classiques, Signatures, Végétariennes)
- Personnalisation complète des pizzas :
  - Choix de la taille (Petite, Moyenne, Grande)
  - Choix de la base (Tomate, Crème, Blanche)
  - Retrait d'ingrédients
  - Ajout d'ingrédients supplémentaires
- 4 formules menu (Solo, Gourmand, Duo, Famille)
- Gestion des quantités

### 🛒 Panier
- Panier dynamique sidebar
- Modification des quantités
- Suppression d'articles
- Calcul automatique des totaux
- Frais de livraison configurables
- Sauvegarde dans localStorage

### 📦 Système de Commande
- Choix Livraison / À emporter
- Formulaire client complet
- Validation des données
- Récapitulatif de commande
- Numéro de commande unique

### 📧 Envoi des Commandes
- Envoi par Email (EmailJS)
- Envoi par SMS (Twilio - prêt à configurer)
- Sauvegarde des commandes en local
- Base client simulée (localStorage)

## 📁 Structure du Projet

```
SITE INTERNET/
├── index.html          # Structure HTML principale
├── style.css           # Styles CSS
├── script.js           # Logique JavaScript
├── config.js           # Configuration (à personnaliser)
├── data.js             # Données des pizzas et formules
├── README.md           # Documentation
└── img/
    └── logo.png        # Votre logo (à ajouter)
```

## 🚀 Installation

### 1. Fichiers
Tous les fichiers sont déjà créés dans votre dossier.

### 2. Ajouter votre logo
Placez votre logo dans le dossier `img/` avec le nom `logo.png`

### 3. Configuration Email (EmailJS)

**EmailJS est un service gratuit pour envoyer des emails depuis le frontend.**

#### Étapes :
1. Créez un compte sur [EmailJS](https://www.emailjs.com/)
2. Créez un service email (Gmail, Outlook, etc.)
3. Créez un template d'email avec ces variables :
   ```
   {{order_number}}
   {{customer_name}}
   {{customer_email}}
   {{customer_phone}}
   {{delivery_mode}}
   {{order_items}}
   {{total}}
   {{estimated_time}}
   ```
4. Notez votre :
   - Service ID
   - Template ID
   - Public Key

5. Ajoutez le SDK EmailJS dans `index.html` avant la balise `</body>` :
   ```html
   <script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@3/dist/email.min.js"></script>
   <script>
       emailjs.init('VOTRE_PUBLIC_KEY');
   </script>
   ```

6. Modifiez `config.js` avec vos identifiants :
   ```javascript
   email: {
       serviceId: 'VOTRE_SERVICE_ID',
       templateId: 'VOTRE_TEMPLATE_ID',
       publicKey: 'VOTRE_PUBLIC_KEY',
       recipientEmail: 'votre@email.com'
   }
   ```

### 4. Configuration SMS (Twilio) - Optionnel

⚠️ **Nécessite un backend** (Twilio ne peut pas être utilisé directement depuis le frontend)

Pour activer les SMS :
1. Créez un compte [Twilio](https://www.twilio.com/)
2. Créez un backend simple (Node.js, PHP, etc.) avec l'API Twilio
3. Modifiez `config.js` :
   ```javascript
   sms: {
       enabled: true,
       // Configurez votre endpoint backend
   }
   ```

### 5. Personnalisation

#### Modifier les prix
Éditez `data.js` pour ajuster :
- Prix des pizzas
- Prix des formules
- Prix des extras

#### Modifier les paramètres
Éditez `config.js` pour ajuster :
- Frais de livraison
- Seuil livraison gratuite
- Informations du restaurant

#### Ajouter/Modifier des pizzas
Dans `data.js`, ajoutez des pizzas dans `PIZZAS_DATA` :
```javascript
{
    id: 13,
    name: 'Votre Pizza',
    category: 'signature', // classique, signature, vegetarienne
    ingredients: ['Ingrédient 1', 'Ingrédient 2'],
    price: 13.90,
    image: 'URL_IMAGE',
    badge: 'Nouveau' // optionnel
}
```

## 🌐 Mise en Ligne

### Option 1 : GitHub Pages (Gratuit)
1. Créez un dépôt GitHub
2. Uploadez tous les fichiers
3. Activez GitHub Pages dans les paramètres
4. Votre site sera accessible à : `https://votreusername.github.io/nom-du-repo`

### Option 2 : Netlify (Gratuit)
1. Créez un compte sur [Netlify](https://www.netlify.com/)
2. Glissez-déposez votre dossier
3. Site en ligne en quelques secondes !

### Option 3 : Hébergement classique
Uploadez tous les fichiers via FTP sur votre hébergement web.

## 📱 Test Local

Pour tester en local :

1. **Option simple** : Double-cliquez sur `index.html`

2. **Option avec serveur local** (recommandé) :
   ```bash
   # Avec Python
   python -m http.server 8000
   
   # Avec Node.js
   npx serve
   ```
   Puis ouvrez : `http://localhost:8000`

## 🎯 Utilisation

### Pour les clients :
1. Parcourir le menu
2. Ajouter des pizzas au panier (simple ou personnalisées)
3. Choisir une formule
4. Valider le panier
5. Choisir Livraison ou À emporter
6. Remplir les informations
7. Confirmer la commande

### Pour vous (gérant) :
- Les commandes sont envoyées par email
- Les commandes sont sauvegardées dans le navigateur (localStorage)
- Accès aux données clients (localStorage)

## 📊 Données Stockées

Toutes les données sont stockées en local dans le navigateur :

### Voir les commandes
Ouvrez la console du navigateur (F12) et tapez :
```javascript
JSON.parse(localStorage.getItem('pizzaclub_orders'))
```

### Voir les clients
```javascript
JSON.parse(localStorage.getItem('pizzaclub_customers'))
```

### Exporter les données
Copiez-collez le résultat dans un fichier JSON.

## 🔧 Personnalisation Avancée

### Changer les couleurs
Dans `style.css`, modifiez les variables CSS :
```css
:root {
    --primary-color: #FF0000;  /* Votre couleur principale */
    --primary-dark: #CC0000;   /* Version foncée */
    --primary-light: #FF3333;  /* Version claire */
}
```

### Ajouter une section
Ajoutez votre HTML dans `index.html` et stylisez dans `style.css`

### Modifier les formules
Éditez `FORMULES_DATA` dans `data.js`

## 🐛 Dépannage

### Les images ne s'affichent pas
- Vérifiez les URLs des images dans `data.js`
- Utilisez des images Unsplash ou hébergez les vôtres

### Les emails ne partent pas
- Vérifiez votre configuration EmailJS
- Vérifiez la console du navigateur (F12) pour les erreurs
- Assurez-vous d'avoir ajouté le script EmailJS

### Le panier ne se sauvegarde pas
- Vérifiez que localStorage est activé dans votre navigateur
- Testez en navigation normale (pas en mode privé)

## 📝 To-Do pour Production

- [ ] Remplacer le logo
- [ ] Configurer EmailJS
- [ ] Modifier les prix des pizzas
- [ ] Ajouter vos vraies coordonnées dans `config.js`
- [ ] Personnaliser les textes
- [ ] Ajouter vos photos de pizzas
- [ ] Tester toutes les fonctionnalités
- [ ] Mettre en ligne

## 🔒 Sécurité

⚠️ **Important** :
- Ce site est conçu pour des petites structures
- Les données sont stockées localement
- Pour une vraie production à grande échelle, utilisez un backend sécurisé
- Ne stockez jamais de données de paiement côté client

## 📄 Licence

Libre d'utilisation pour votre pizzeria Pizza Club.

## 🆘 Support

Pour toute question ou problème :
- Consultez la console du navigateur (F12)
- Vérifiez la configuration dans `config.js`
- Testez étape par étape

## 🎉 Fonctionnalités Futures (Optionnelles)

Pour aller plus loin, vous pourriez ajouter :
- Système de paiement en ligne (Stripe, PayPal)
- Backend avec base de données réelle
- Espace d'administration
- Gestion des horaires d'ouverture
- Système de fidélité
- Codes promo
- Tracking de livraison

---

**Fait avec ❤️ pour Pizza Club**

Bon succès avec votre site ! 🍕
# Test deploy
