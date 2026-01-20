#!/bin/bash
#
# 📥 RESTAURATION DES FICHIERS CRITIQUES
# À lancer APRÈS un git pull si vous avez perdu des fichiers
#

echo "📥 =========================================="
echo "📥 RESTAURATION FICHIERS - Pizza Club"
echo "📥 =========================================="
echo ""

# Trouver le backup le plus récent
LATEST_BACKUP=$(ls -td backups/backup_* 2>/dev/null | head -1)

if [ -z "$LATEST_BACKUP" ]; then
    echo "❌ Aucun backup trouvé !"
    echo ""
    echo "Les backups doivent être dans le dossier 'backups/'"
    exit 1
fi

echo "📁 Backup trouvé: $LATEST_BACKUP"
echo "📅 Date: $(basename $LATEST_BACKUP | sed 's/backup_//' | sed 's/_/ à /' | sed 's/\([0-9]\{8\}\)/\1 /')"
echo ""
echo "⚠️  ATTENTION : Cette opération va écraser vos fichiers actuels !"
read -p "Continuer ? (o/N) " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Oo]$ ]]; then
    echo "❌ Restauration annulée"
    exit 1
fi

echo ""
echo "🔄 Restauration en cours..."
echo ""

RESTORED=0
MISSING=0

# Liste des fichiers à restaurer
FILES=(
    "orders.json"
    "debug-order.txt"
    "inventory.json"
    "temperatures.json"
    "unavailability.json"
    "config/brevo-config.php"
    ".env"
)

for file in "${FILES[@]}"; do
    BACKUP_FILE="$LATEST_BACKUP/$file"
    
    if [ -f "$BACKUP_FILE" ]; then
        # Créer le dossier parent si nécessaire
        mkdir -p "$(dirname "$file")"
        
        # Restaurer le fichier
        cp "$BACKUP_FILE" "$file"
        
        SIZE=$(stat -f%z "$file" 2>/dev/null || stat -c%s "$file" 2>/dev/null)
        echo "✅ $file restauré ($SIZE octets)"
        ((RESTORED++))
    else
        echo "⚠️  $file (pas dans le backup)"
        ((MISSING++))
    fi
done

echo ""
echo "📊 RÉSULTAT:"
echo "   ✅ $RESTORED fichier(s) restauré(s)"
echo "   ⚠️  $MISSING fichier(s) non trouvé(s) dans le backup"
echo ""
echo "✅ Restauration terminée !"
echo ""
echo "📥 =========================================="
