# 📧 Migration emails vers Brevo API

## ✅ Problème résolu

**Avant:** Les emails utilisaient `mail()` de PHP, qui est peu fiable sur Hostinger et peut être bloqué.

**Maintenant:** Tous les emails (cuisine + client) passent par l'API Brevo, comme les SMS.

## 🔧 Modifications apportées

### 1. `send-order.php`
- ✅ Email cuisine maintenant envoyé via Brevo API (au lieu de `mail()`)
- ✅ Email client maintenant envoyé via Brevo API (au lieu de `mail()`)
- ✅ Logs détaillés dans `debug-order.txt` pour chaque envoi
- ✅ Utilise la même clé API que les SMS (dans `config/brevo-config.php`)

### 2. `test-brevo-email.php` (nouveau)
- 🆕 Script de test pour vérifier que les emails Brevo fonctionnent
- 🆕 Teste email texte simple
- 🆕 Teste email HTML (format commande)

## 📝 Configuration requise

La configuration Brevo existante dans `config/brevo-config.php` suffit :
```php
return [
    'api_key' => 'xkeysib-...', // Ta clé API Brevo
    'sender_name' => 'PizzaClub',
    'recipient_number' => '+262692630364',
];
```

**Aucune configuration supplémentaire nécessaire!** La clé API fonctionne pour SMS ET emails.

## 🧪 Comment tester

### Sur le serveur :

1. **Upload les fichiers modifiés sur le serveur:**
   ```bash
   # Depuis ton ordinateur
   ./deploy-serveur.sh
   ```

2. **Teste l'envoi d'email Brevo:**
   - Va sur : `https://www.pizzaclub.re/test-brevo-email.php`
   - Tu devrais recevoir 2 emails de test sur `commande@pizzaclub.re`
   - Vérifie que les codes HTTP sont 200 ou 201

3. **Teste une vraie commande:**
   - Fais une commande test sur le site
   - Tu devrais recevoir:
     - ✅ Un SMS (comme avant)
     - ✅ Un email cuisine (nouveau via Brevo!)
     - ✅ Un email client si l'adresse est renseignée

4. **Vérifie les logs:**
   ```bash
   ssh ton-serveur
   cd /chemin/vers/site
   tail -50 debug-order.txt
   ```
   
   Tu verras :
   ```
   📧 Email restaurant (BREVO) - To: commande@pizzaclub.re, Sent: YES ✅
   📧 Email client (BREVO) - To: client@example.com, Sent: YES ✅
   ```

## 🎯 Avantages

1. **Fiabilité:** Brevo est bien plus fiable que `mail()` PHP
2. **Tracking:** Tu peux voir l'historique des emails sur Brevo
3. **Délivrabilité:** Meilleur taux de délivrance (pas de spam)
4. **Logs:** Logs détaillés dans `debug-order.txt`
5. **Unified:** SMS + Emails sur la même plateforme

## 📊 Dashboard Brevo

Pour voir les emails envoyés :
1. Va sur https://app.brevo.com/
2. Clique sur "Email" > "Transactional"
3. Tu verras tous les emails envoyés avec leur statut

## ⚠️ Limites Brevo (plan gratuit)

- **300 emails/jour** (largement suffisant pour un restaurant)
- Si tu dépasses, tu peux upgrader ou ajouter des crédits

## 🐛 Debugging

Si tu ne reçois pas d'email :

1. **Vérifie les logs:**
   ```bash
   tail -100 debug-order.txt
   ```

2. **Cherche:**
   - `✓ EMAIL CUISINE ENVOYÉ VIA BREVO!` = OK
   - `✗ EMAIL CUISINE ÉCHOUÉ` = Problème (voir code HTTP)

3. **Codes HTTP:**
   - `200` ou `201` = ✅ Envoyé avec succès
   - `401` = Clé API invalide
   - `400` = Erreur dans les données
   - `402` = Quota dépassé

4. **Vérifie Brevo Dashboard:**
   - https://app.brevo.com/
   - Vérifie si l'email apparaît dans l'historique

## 📞 Support

Si problème :
1. Envoie-moi le contenu de `debug-order.txt`
2. Envoie-moi une capture du dashboard Brevo
3. Dis-moi le code HTTP reçu
