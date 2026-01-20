# 🚨 GUIDE ANTI-PERTE DE DONNÉES

## ⚠️ PROBLÈME IDENTIFIÉ

À chaque `git pull` ou `git push`, vous perdez :
- ❌ Les commandes clients (`orders.json`, `debug-order.txt`)
- ❌ L'inventaire (`inventory.json`)
- ❌ Les températures HACCP (`temperatures.json`)
- ❌ Les indisponibilités (`unavailability.json`)
- ❌ La config Brevo SMS/Email (`config/brevo-config.php`)

**POURQUOI ?** Ces fichiers sont dans `.gitignore` donc Git ne les suit PAS.

---

## ✅ SOLUTION : WORKFLOW DE SAUVEGARDE

### 🔴 AVANT CHAQUE GIT (pull/push/commit) :

```bash
cd "/Users/julienchanewai/Desktop/PIZZA CLUB/SITE INTERNET"
./backup-avant-git.sh
```

Cela crée une copie de tous vos fichiers critiques dans `backups/backup_YYYYMMDD_HHMMSS/`

### 🔴 APRÈS UN GIT PULL (si vous avez perdu des fichiers) :

```bash
./restaurer-backup.sh
```

Cela restaure automatiquement le dernier backup.

---

## 📋 WORKFLOW COMPLET RECOMMANDÉ

### Pour modifier du code :

```bash
# 1. SAUVEGARDER D'ABORD
./backup-avant-git.sh

# 2. Voir ce qui a changé
git status

# 3. Ajouter UNIQUEMENT les fichiers PHP/HTML/CSS/JS modifiés
git add commande-fournisseurs.php
git add admin-dashboard.php
# etc.

# 4. Commit
git commit -m "Description claire"

# 5. Push
git push
```

### Pour récupérer les dernières modifications depuis GitHub :

```bash
# 1. SAUVEGARDER D'ABORD
./backup-avant-git.sh

# 2. Pull
git pull

# 3. SI vous avez perdu des données, restaurer
./restaurer-backup.sh
```

---

## 📊 VÉRIFIER VOS BACKUPS

```bash
ls -lh backups/
```

Vous verrez tous vos backups avec leur date.

---

## 🗑️ NETTOYER LES VIEUX BACKUPS

Si vous avez trop de backups (> 30 jours) :

```bash
# Supprimer les backups de plus de 30 jours
find backups/ -name "backup_*" -mtime +30 -exec rm -rf {} \;
```

---

## 🆘 RÉCUPÉRATION D'URGENCE

Si vous avez déjà tout perdu AVANT de lire ce guide :

1. **NE FAITES RIEN** (pas de git pull/push)
2. Vérifiez si vous avez des backups dans `backups/`
3. Si oui : `./restaurer-backup.sh`
4. Si non : vérifiez si vous avez des sauvegardes ailleurs (Time Machine, etc.)

---

## 💡 MÉMO RAPIDE

**TOUJOURS faire AVANT un git pull/push :**
```bash
./backup-avant-git.sh
```

**Pour restaurer après une perte :**
```bash
./restaurer-backup.sh
```

**Vérifier que les fichiers critiques sont là :**
```bash
ls -lh orders.json inventory.json temperatures.json unavailability.json
```

---

## 📞 FICHIERS CRITIQUES À NE JAMAIS PERDRE

1. **orders.json** - TOUTES vos commandes clients
2. **inventory.json** - Votre inventaire complet
3. **temperatures.json** - Relevés HACCP (obligation légale)
4. **unavailability.json** - Produits indispos + fermetures
5. **config/brevo-config.php** - Clés API Brevo (emails/SMS)
6. **.env** - Variables d'environnement

**CES FICHIERS NE SONT PAS SUR GITHUB PAR SÉCURITÉ !**

---

## 🎯 RÈGLE D'OR

**SAUVEGARDE = OBLIGATOIRE AVANT GIT**

Créez un alias pour ne jamais oublier :

```bash
# Ajoutez dans votre ~/.zshrc :
alias gitpull='./backup-avant-git.sh && git pull && echo "✅ Pull terminé - données sauvegardées"'
alias gitpush='./backup-avant-git.sh && git push && echo "✅ Push terminé - données sauvegardées"'
```

Ensuite utilisez `gitpull` et `gitpush` au lieu de `git pull` et `git push`.
