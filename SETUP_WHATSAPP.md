# Configuration WhatsApp pour les commandes automatiques

Le fichier `send-order.php` envoie automatiquement les commandes par **email** et **WhatsApp**.

## ✅ Email (Déjà fonctionnel)

L'envoi par email vers `commande@pizzaclub.re` fonctionne automatiquement via le serveur mail Hostinger.

## 📱 WhatsApp - Options disponibles

### Option 1: CallMeBot (Recommandé - Gratuit et Simple)

1. **Inscription (5 minutes)**
   - Ajoutez le numéro **+34 644 44 32 09** dans vos contacts WhatsApp
   - Envoyez le message: `I allow callmebot to send me messages`
   - Vous recevrez votre **API Key**

2. **Configuration**
   - Ouvrez `send-order.php` ligne 113
   - Remplacez `YOUR_CALLMEBOT_API_KEY` par votre clé API reçue
   ```php
   $callmebotApiKey = 'VOTRE_CLE_API_ICI';
   ```

3. **Testez** - Les commandes seront envoyées automatiquement sur WhatsApp

**Documentation**: https://www.callmebot.com/blog/free-api-whatsapp-messages/

### Option 2: WhatsApp Business API (Professionnel)

Si vous avez un compte WhatsApp Business API:

1. Ouvrez `send-order.php` ligne 87
2. Décommentez le code (supprimez `/*` et `*/`)
3. Remplacez:
   - `YOUR_PHONE_NUMBER_ID` par votre Phone Number ID
   - `YOUR_WHATSAPP_TOKEN` par votre token d'accès

**Documentation**: https://developers.facebook.com/docs/whatsapp/business-management-api

## 🚀 Déploiement sur Hostinger

1. Uploadez `send-order.php` à la racine du site
2. Vérifiez que PHP est activé (déjà le cas sur Hostinger)
3. Les permissions seront automatiquement gérées
4. Le dossier `orders/` sera créé automatiquement pour sauvegarder les commandes

## 📊 Logs des commandes

Toutes les commandes sont sauvegardées dans le dossier `orders/`:
- `orders/2025-11-24.log` - Log quotidien
- `orders/PC20251124-XXXX.json` - Détails complets en JSON

## ⚠️ Important

- Sans configuration WhatsApp, **seul l'email sera envoyé** (ce qui est déjà fonctionnel)
- L'email fonctionne immédiatement sans configuration supplémentaire
- CallMeBot est **gratuit** et prend 5 minutes à configurer
