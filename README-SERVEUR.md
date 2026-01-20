# 🚨 INSTRUCTIONS SERVEUR - NE PAS SUPPRIMER

## ⚠️ RÈGLE ABSOLUE

**NE JAMAIS FAIRE `git pull` DIRECTEMENT !**

Sinon vous perdez TOUTES les données :
- ❌ Commandes clients
- ❌ Inventaire
- ❌ Températures HACCP
- ❌ Indisponibilités
- ❌ Configuration Brevo

---

## ✅ COMMENT METTRE À JOUR LE SITE

### Méthode 1 : Script automatique (RECOMMANDÉ)

```bash
./deploy-serveur.sh
```

Ce script fait TOUT automatiquement :
1. Sauvegarde vos données
2. Récupère les modifications depuis GitHub
3. Restaure vos données
4. Vérifie que tout est OK

### Méthode 2 : Manuelle (si le script ne marche pas)

```bash
# 1. SAUVEGARDER D'ABORD
./backup-avant-git.sh

# 2. Faire le pull
git pull

# 3. RESTAURER IMMÉDIATEMENT
./restaurer-backup.sh
```

---

## 📦 FICHIERS À NE JAMAIS MODIFIER DIRECTEMENT

Ces fichiers sont gérés par le site :
- `orders.json` - Commandes clients
- `inventory.json` - Inventaire produits
- `temperatures.json` - Relevés HACCP
- `unavailability.json` - Indisponibilités + fermetures
- `config/brevo-config.php` - Clés API Brevo
- `.env` - Variables d'environnement

**SI CES FICHIERS DISPARAISSENT = PERTE DE DONNÉES !**

---

## 🆘 EN CAS DE PROBLÈME

### J'ai fait git pull et j'ai tout perdu !

```bash
./restaurer-backup.sh
```

### Je vois "0 articles" dans l'inventaire

Vos données ont été écrasées. Restaurez :

```bash
./restaurer-backup.sh
```

### Le script deploy-serveur.sh ne marche pas

Utilisez la méthode manuelle :
1. `./backup-avant-git.sh`
2. `git pull`
3. `./restaurer-backup.sh`

---

## 📞 CONTACT

En cas de problème : contact@pizzaclub.re

---

## ⚙️ INSTALLATION DU SYSTÈME DE PROTECTION

Si les scripts n'existent pas encore sur le serveur :

1. Copier ces fichiers depuis GitHub :
   - `backup-avant-git.sh`
   - `restaurer-backup.sh`
   - `deploy-serveur.sh`

2. Les rendre exécutables :
   ```bash
   chmod +x backup-avant-git.sh restaurer-backup.sh deploy-serveur.sh
   ```

3. Créer le dossier backups :
   ```bash
   mkdir -p backups
   ```

4. Tester :
   ```bash
   ./backup-avant-git.sh
   ```

---

**Date de création : 20 janvier 2026**
**Dernière mise à jour : 20 janvier 2026**
