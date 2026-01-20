#!/bin/bash

# 🎯 POST-MERGE GIT HOOK POUR LE SERVEUR
# À copier dans : .git/hooks/post-merge sur le serveur
# Puis : chmod +x .git/hooks/post-merge

echo "🔄 Post-merge: Restauration des données..."

# Chemin du backup le plus récent
BACKUP_DIR=$(ls -td backups/backup_* 2>/dev/null | head -1)

if [ -z "$BACKUP_DIR" ]; then
    echo "⚠️  Aucun backup trouvé - première installation"
    exit 0
fi

echo "📦 Utilisation du backup: $BACKUP_DIR"

# Fichiers à restaurer (ignorés par Git mais nécessaires pour le site)
FILES=(
    "orders.json"
    "inventory.json"
    "temperatures.json"
    "unavailability.json"
    "config/brevo-config.php"
    ".env"
)

RESTORED=0
for file in "${FILES[@]}"; do
    if [ -f "$BACKUP_DIR/$file" ]; then
        # Créer le dossier si nécessaire
        DIR=$(dirname "$file")
        mkdir -p "$DIR"
        
        # Restaurer le fichier
        cp "$BACKUP_DIR/$file" "$file"
        echo "✅ $file restauré"
        ((RESTORED++))
    fi
done

echo "✅ $RESTORED fichier(s) restauré(s) automatiquement"
echo "🎉 Déploiement terminé - vos données sont préservées !"
