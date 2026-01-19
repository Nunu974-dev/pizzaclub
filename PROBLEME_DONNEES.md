# ⚠️ PROBLÈME: Fichiers de données manquants sur le serveur

## Le Problème

Les fichiers `inventory.json` et `temperatures.json` sont dans le `.gitignore`, donc ils ne sont **jamais uploadés automatiquement** sur le serveur quand vous faites `git push`.

## Solution Rapide

### Option 1: Upload Manuel (RECOMMANDÉ)

1. Connectez-vous à votre serveur (FTP, SFTP, ou panneau de contrôle)
2. Allez dans le dossier où se trouve `admin-dashboard.php`
3. Uploadez ces fichiers depuis votre ordinateur local:
   - `inventory.json` (9.6 KB)
   - `temperatures.json` (6 KB)

### Option 2: Utiliser SCP (si vous avez accès SSH)

```bash
# Remplacez USER et SERVER par vos informations
scp inventory.json USER@SERVER:/chemin/vers/site/
scp temperatures.json USER@SERVER:/chemin/vers/site/
```

Exemple:
```bash
scp inventory.json root@pizzaclub.re:/var/www/html/
scp temperatures.json root@pizzaclub.re:/var/www/html/
```

### Option 3: Script Automatique

Utilisez le script `upload-data.sh`:

```bash
chmod +x upload-data.sh
./upload-data.sh
```

## Vérification

Après l'upload, vérifiez que ça fonctionne:

1. Allez sur: `https://votre-site.com/debug-data.php`
2. Vous devriez voir:
   - ✅ Fichier inventory.json trouvé
   - ✅ Fichier temperatures.json trouvé

## Pourquoi ces fichiers sont dans .gitignore?

C'est **normal** et **recommandé** pour les fichiers de données:
- Évite de surcharger Git avec des données qui changent constamment
- Protège vos données sensibles (inventaire, températures)
- Chaque environnement (local, serveur) garde ses propres données

## Fichiers à uploader manuellement

- ✅ `inventory.json` - Inventaire des articles
- ✅ `temperatures.json` - Relevés de température
- ✅ `unavailability.json` - Indisponibilités (si utilisé)
- ❌ `orders.json` - Commandes (géré automatiquement)
- ❌ `config/*.php` - Configuration (à créer sur le serveur)

## Sauvegarde

💡 **Pensez à sauvegarder régulièrement** ces fichiers depuis votre serveur!

Vous pouvez utiliser le bouton "Export JSON" dans l'admin dashboard pour faire des backups.
