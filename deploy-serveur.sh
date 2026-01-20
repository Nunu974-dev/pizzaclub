#!/bin/bash

# 🚀 SCRIPT DE DÉPLOIEMENT SÉCURISÉ
# Ce script doit être exécuté SUR LE SERVEUR avant chaque git pull

echo "🚀 =========================================="
echo "🚀 DÉPLOIEMENT SÉCURISÉ - Pizza Club"
echo "🚀 =========================================="

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 1. SAUVEGARDE DES DONNÉES
echo ""
echo "📦 Étape 1: Sauvegarde des données..."

# Créer le dossier de backup si nécessaire
BACKUP_DIR="backups/backup_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR/config"

# Liste des fichiers critiques
FILES=(
    "orders.json"
    "inventory.json"
    "temperatures.json"
    "unavailability.json"
    "config/brevo-config.php"
    ".env"
)

SAVED=0
for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        cp "$file" "$BACKUP_DIR/$file"
        echo -e "${GREEN}✅ $file sauvegardé${NC}"
        ((SAVED++))
    else
        echo -e "${YELLOW}⚠️  $file n'existe pas (normal si nouveau)${NC}"
    fi
done

echo ""
echo -e "${GREEN}📊 $SAVED fichier(s) sauvegardé(s)${NC}"
echo -e "${GREEN}📁 Backup créé: $BACKUP_DIR${NC}"

# 2. GIT PULL
echo ""
echo "⬇️  Étape 2: Récupération des modifications depuis GitHub..."
git pull

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Git pull réussi${NC}"
else
    echo -e "${RED}❌ Erreur lors du git pull${NC}"
    echo "📦 Restauration des fichiers..."
    
    # Restaurer en cas d'erreur
    for file in "${FILES[@]}"; do
        if [ -f "$BACKUP_DIR/$file" ]; then
            cp "$BACKUP_DIR/$file" "$file"
        fi
    done
    
    exit 1
fi

# 3. RESTAURATION DES DONNÉES
echo ""
echo "📥 Étape 3: Restauration des données..."

RESTORED=0
for file in "${FILES[@]}"; do
    if [ -f "$BACKUP_DIR/$file" ]; then
        cp "$BACKUP_DIR/$file" "$file"
        SIZE=$(ls -lh "$file" | awk '{print $5}')
        echo -e "${GREEN}✅ $file restauré ($SIZE)${NC}"
        ((RESTORED++))
    fi
done

echo ""
echo -e "${GREEN}📊 $RESTORED fichier(s) restauré(s)${NC}"

# 4. VÉRIFICATION
echo ""
echo "🔍 Étape 4: Vérification..."

ALL_OK=true
for file in "inventory.json" "temperatures.json" "unavailability.json"; do
    if [ -f "$file" ]; then
        SIZE=$(stat -f%z "$file" 2>/dev/null || stat -c%s "$file" 2>/dev/null)
        if [ "$SIZE" -gt 10 ]; then
            echo -e "${GREEN}✅ $file OK (${SIZE} octets)${NC}"
        else
            echo -e "${RED}⚠️  $file trop petit (${SIZE} octets)${NC}"
            ALL_OK=false
        fi
    else
        echo -e "${RED}❌ $file MANQUANT${NC}"
        ALL_OK=false
    fi
done

echo ""
if [ "$ALL_OK" = true ]; then
    echo -e "${GREEN}🎉 DÉPLOIEMENT RÉUSSI !${NC}"
    echo -e "${GREEN}✅ Toutes vos données sont préservées${NC}"
else
    echo -e "${YELLOW}⚠️  ATTENTION: Vérifiez vos données${NC}"
fi

echo ""
echo "🚀 =========================================="
