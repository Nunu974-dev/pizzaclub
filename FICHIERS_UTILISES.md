# 📋 FICHIERS UTILISÉS EN PRODUCTION - Pizza Club

**Date de création:** 20 janvier 2026  
**Objectif:** Éviter la confusion entre les fichiers et ne plus perdre les modifications

---

## ⚠️ FICHIERS ACTIFS (NE PAS TOUCHER AUX AUTRES !)

### 🛒 **Commandes Clients**
- **Fichier principal:** `send-order.php`
  - ✅ C'est LUI qui reçoit les commandes du site
  - ✅ Il utilise 2 templates d'email :
    - `email-template.php` → Email envoyé AU CLIENT
    - `email-template-kitchen.php` → Email envoyé À LA CUISINE
  - ✅ Il sauvegarde dans `orders.json`

- **Visualisation:** `orders-log.php`  
  - ✅ Pour voir toutes les commandes
  - ✅ Affiche le contenu de `orders.json`

### 📦 **Commandes Fournisseurs**  
- **Fichier unique:** `commande-fournisseurs.php`
  - ✅ Tous les emails DOIVENT aller sur `contact@pizzaclub.re`
  - ⚠️ NE PAS remettre les emails des fournisseurs !

### 🎛️ **Administration**
- **Dashboard principal:** `admin-dashboard.php`
  - ✅ Contient tout : fournisseurs, clients, inventaire, températures
  - ✅ Utilise des iframes pour charger les autres pages

### 🚫 **Indisponibilités & Fermetures**
- **Interface admin:** `admin-indispos-manager.php`
- **Fichier de données:** `unavailability.json`
- **Script de vérification:** `check-closure.php`

---

## 🗂️ FICHIERS DE DONNÉES (JSON)

| Fichier | Usage | Sauvegarde |
|---------|-------|------------|
| `orders.json` | Historique commandes clients | ✅ Auto (100 dernières) |
| `inventory.json` | Stock & inventaire | ✅ Dans `/archives/` |
| `temperatures.json` | Relevés HACCP | ✅ Manuel |
| `unavailability.json` | Articles indispos + fermetures | ⚠️ Manuel |

---

## ❌ FICHIERS À NE PAS MODIFIER (versions anciennes/tests)

- `admin-dashboard-v2.php` → version test (utiliser `admin-dashboard.php`)
- `admin-indispos.html` → ancienne version statique
- `server-version.html` → test
- `email-version.php` → test
- Tous les fichiers `test-*.php` → tests uniquement

---

## 🔄 WORKFLOW GIT RECOMMANDÉ

### Avant de commit :
```bash
cd "/Users/julienchanewai/Desktop/PIZZA CLUB/SITE INTERNET"
git status
```

### Voir les modifications :
```bash
git diff commande-fournisseurs.php
git diff orders-log.php
git diff email-template.php
```

### Commit SEULEMENT les fichiers modifiés :
```bash
git add commande-fournisseurs.php orders-log.php
git commit -m "Fix: tous les emails fournisseurs vont sur contact@pizzaclub.re + affichage complet suppléments"
git push
```

### ⚠️ NE JAMAIS faire :
```bash
git add .          # ❌ DANGER : ajoute TOUS les fichiers
git add *          # ❌ DANGER : ajoute TOUS les fichiers
```

---

## 🔍 COMMENT VÉRIFIER LE BON FICHIER ?

### Pour les emails clients :
```bash
grep "require" send-order.php
```
Résultat attendu :
- `require_once __DIR__ . '/email-template.php';` → Email CLIENT
- `require_once __DIR__ . '/email-template-kitchen.php';` → Email CUISINE

### Pour les commandes fournisseurs :
```bash
grep "'email'" commande-fournisseurs.php | head -20
```
Résultat attendu : **TOUS** doivent avoir `'email' => 'contact@pizzaclub.re'`

---

## 📝 DERNIÈRES MODIFICATIONS (20 jan 2026)

✅ **commande-fournisseurs.php**
- Tous les emails → `contact@pizzaclub.re`
- Suppression du CC qui envoyait en copie

✅ **orders-log.php**  
- Affichage complet des suppléments (pâtes, salades, rolls, buns)
- Affichage des options (pain, vinaigrette)
- Conversion tailles (moyenne → 33cm, L → Large)
- Support formules avec détails

✅ **email-template.php**
- (Pas modifié aujourd'hui, mais contient déjà l'affichage complet)

---

## 🆘 EN CAS DE PROBLÈME

1. **Les modifications disparaissent ?**
   - Vérifier que vous éditez le bon fichier (voir liste ci-dessus)
   - Vérifier `git status` avant de commit
   - Ne pas faire `git pull` sans avoir commit vos changements

2. **Les emails ne partent pas ?**  
   - Vérifier `send-order.php` (c'est lui qui envoie)
   - Vérifier la config Brevo dans `/config/brevo-config.php`

3. **Les commandes ne s'affichent pas ?**
   - Vérifier que `orders.json` existe
   - Vérifier `orders-log.php` pour la connexion
   - Regarder `debug-order.txt` pour les logs

---

## 📞 CONTACT TECHNIQUE

En cas de doute, TOUJOURS vérifier ce fichier avant de modifier quoi que ce soit !
