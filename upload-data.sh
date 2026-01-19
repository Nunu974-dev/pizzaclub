#!/bin/bash
# Script pour uploader les fichiers de données vers le serveur

echo "📦 Upload des fichiers de données vers le serveur..."
echo ""
echo "⚠️  IMPORTANT: Configurez d'abord vos informations FTP/SFTP ci-dessous"
echo ""

# Configuration à personnaliser
SERVER_HOST="votre-serveur.com"
SERVER_USER="votre-utilisateur"
SERVER_PATH="/chemin/vers/site"
SERVER_PORT="21"  # 21 pour FTP, 22 pour SFTP

# Fichiers à uploader
FILES=(
    "inventory.json"
    "temperatures.json"
    "unavailability.json"
)

echo "Fichiers à uploader:"
for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        size=$(wc -c < "$file" | tr -d ' ')
        echo "  ✅ $file ($size octets)"
    else
        echo "  ❌ $file (manquant)"
    fi
done

echo ""
echo "Options d'upload:"
echo "1. FTP (utiliser ftp ou lftp)"
echo "2. SFTP (utiliser sftp ou rsync)"
echo "3. SCP (utiliser scp)"
echo "4. Manuel (instructions)"
echo ""
echo "Choisissez votre méthode (1-4):"

# Pour l'instant, afficher juste les instructions
echo ""
echo "=== INSTRUCTIONS UPLOAD MANUEL ==="
echo ""
echo "1. Connectez-vous à votre serveur via FTP/SFTP"
echo "2. Allez dans le dossier où se trouve admin-dashboard.php"
echo "3. Uploadez ces fichiers:"
for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "   - $file"
    fi
done
echo ""
echo "=== OU UTILISEZ LA COMMANDE SCP ==="
echo ""
echo "Remplacez les valeurs et exécutez:"
echo ""
for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "scp $file USER@SERVER:/path/to/site/"
    fi
done
echo ""
echo "Exemple:"
echo "scp inventory.json user@pizzaclub.re:/var/www/html/"
echo ""
