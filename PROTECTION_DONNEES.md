# 🔒 Protection des Données - Ne Pas Écraser lors des Commits

## ⚠️ IMPORTANT

Les fichiers suivants **NE DOIVENT JAMAIS** être versionnés dans Git car ils contiennent les données en temps réel du site :

### ✅ Fichiers Protégés (dans .gitignore)

1. **`orders.json`** - Commandes clients
2. **`debug-order.txt`** - Logs de debug des commandes
3. **`inventory.json`** - Stock de l'inventaire
4. **`temperatures.json`** - Historique des températures
5. **`unavailability.json`** - **Indisponibilités des produits** ⭐
6. **`commandes-fournisseurs.json`** - Commandes fournisseurs

### 🛡️ Protection Active

Ces fichiers sont listés dans `.gitignore` et ont été retirés du suivi Git avec :
```bash
git rm --cached inventory.json temperatures.json unavailability.json
```

### 📋 Ce qui se passe maintenant

- ✅ Les modifications locales de ces fichiers ne seront JAMAIS commitées
- ✅ Les indisponibilités configurées sur le serveur ne seront JAMAIS écrasées
- ✅ Le stock et les températures restent intacts lors des déploiements
- ✅ Les commandes ne sont jamais perdues

### 🔄 Workflow de Déploiement

Quand vous faites `git push` :
1. Le code source est mis à jour (PHP, JS, CSS)
2. Les fichiers de données restent **intacts** sur le serveur
3. Vos indisponibilités configurées sont **préservées**

### 🆕 Premier Déploiement sur un Nouveau Serveur

Si vous déployez sur un nouveau serveur, ces fichiers n'existeront pas. Utilisez :
```
https://www.pizzaclub.re/init-files.php
```

Ce script créera automatiquement tous les fichiers nécessaires avec les bonnes permissions.

### 🚨 En Cas de Problème

Si les indisponibilités disparaissent quand même :
1. Vérifiez que le fichier existe : `ls -la unavailability.json`
2. Vérifiez les permissions : `chmod 666 unavailability.json`
3. Vérifiez qu'il n'est pas suivi : `git status unavailability.json`
4. Recréez-le : `https://www.pizzaclub.re/init-files.php`

---
**Date de mise en place** : 20 janvier 2026
