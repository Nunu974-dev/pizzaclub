#!/bin/bash
#
# 💾 SAUVEGARDE AUTOMATIQUE DES FICHIERS CRITIQUES
# À lancer AVANT chaque git pull/push
#

echo "💾 =========================================="
echo "💾 SAUVEGARDE FICHIERS CRITIQUES - Pizza Club"
echo "💾 =========================================="
echo ""

# Créer le dossier de sauvegarde avec la date
BACKUP_DIR="backups/backup_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

# Liste des fichiers critiques à sauvegarder
FILES=(
    "orders.json"
    "debug-order.txt"
    "inventory.json"
    "temperatures.json"
    "unavailability.json"
    "config/brevo-config.php"
    ".env"
)

echo "📦 Sauvegarde des fichiers critiques..."
echo ""

SAVED=0
MISSING=0

for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        # Créer le dossier parent dans le backup si nécessaire
        mkdir -p "$BACKUP_DIR/$(dirname "$file")"
        
        # Copier le fichier
        cp "$file" "$BACKUP_DIR/$file"
        
        # Afficher la taille
        SIZE=$(stat -f%z "$file" 2>/dev/null || stat -c%s "$file" 2>/dev/null)
        echo "✅ $file ($SIZE octets)"
        ((SAVED++))
    else
        echo "⚠️  $file (n'existe pas)"
        ((MISSING++))
    fi
done

echo ""
echo "📊 RÉSULTAT:"
echo "   ✅ $SAVED fichier(s) sauvegardé(s)"
echo "   ⚠️  $MISSING fichier(s) manquant(s)"
echo ""
echo "📁 Backup créé dans: $BACKUP_DIR"
echo ""
echo "💾 =========================================="
