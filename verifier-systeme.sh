#!/bin/bash
#
# 🔍 SCRIPT DE VÉRIFICATION - Pizza Club
# Vérifie que les bons fichiers sont utilisés et configurés correctement
#

echo "🔍 =========================================="
echo "🍕 VÉRIFICATION SYSTÈME PIZZA CLUB"
echo "🔍 =========================================="
echo ""

# Couleurs
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 1. Vérifier les emails fournisseurs
echo "📧 1. VÉRIFICATION EMAILS FOURNISSEURS"
echo "---------------------------------------"

count_correct=$(grep -o "'email' => 'contact@pizzaclub.re'" commande-fournisseurs.php | wc -l | tr -d ' ')
echo "✅ Emails configurés sur contact@pizzaclub.re : $count_correct"

# Vérifier s'il y a des emails incorrects
wrong_emails=$(grep "'email' =>" commande-fournisseurs.php | grep -v "contact@pizzaclub.re" || true)
if [ -z "$wrong_emails" ]; then
    echo -e "${GREEN}✅ TOUS les emails vont bien sur contact@pizzaclub.re${NC}"
else
    echo -e "${RED}❌ ATTENTION : certains emails ne vont PAS sur contact@pizzaclub.re :${NC}"
    echo "$wrong_emails"
fi
echo ""

# 2. Vérifier les fichiers utilisés par send-order.php
echo "📨 2. VÉRIFICATION TEMPLATES EMAIL"
echo "---------------------------------------"

if grep -q "email-template.php" send-order.php; then
    echo -e "${GREEN}✅ email-template.php utilisé (email CLIENT)${NC}"
else
    echo -e "${RED}❌ email-template.php NON utilisé !${NC}"
fi

if grep -q "email-template-kitchen.php" send-order.php; then
    echo -e "${GREEN}✅ email-template-kitchen.php utilisé (email CUISINE)${NC}"
else
    echo -e "${RED}❌ email-template-kitchen.php NON utilisé !${NC}"
fi
echo ""

# 3. Vérifier les fichiers JSON
echo "📁 3. VÉRIFICATION FICHIERS JSON"
echo "---------------------------------------"

check_file() {
    file=$1
    if [ -f "$file" ]; then
        size=$(stat -f%z "$file" 2>/dev/null || stat -c%s "$file" 2>/dev/null)
        echo -e "${GREEN}✅ $file existe (${size} octets)${NC}"
    else
        echo -e "${RED}❌ $file MANQUANT !${NC}"
    fi
}

check_file "orders.json"
check_file "inventory.json"
check_file "temperatures.json"
check_file "unavailability.json"
echo ""

# 4. Vérifier Git status
echo "🔄 4. STATUT GIT"
echo "---------------------------------------"

if [ -d ".git" ]; then
    modified=$(git status --short | wc -l | tr -d ' ')
    if [ "$modified" -eq "0" ]; then
        echo -e "${GREEN}✅ Aucun fichier modifié (tout est synchro)${NC}"
    else
        echo -e "${YELLOW}⚠️  $modified fichier(s) modifié(s) non commité(s) :${NC}"
        git status --short
    fi
else
    echo -e "${RED}❌ Pas de dépôt Git trouvé${NC}"
fi
echo ""

# 5. Vérifier les doublons
echo "🔍 5. RECHERCHE DE DOUBLONS"
echo "---------------------------------------"

echo "Fichiers commande-fournisseur* :"
find . -name "*commande-fournisseur*" -type f | grep -v ".git" | while read f; do
    echo "  - $f"
done

echo "Fichiers orders-log* :"
find . -name "*orders-log*" -type f | grep -v ".git" | while read f; do
    echo "  - $f"
done

echo "Fichiers email-template* :"
find . -name "*email-template*" -type f | grep -v ".git" | while read f; do
    echo "  - $f"
done
echo ""

# 6. Résumé
echo "📊 RÉSUMÉ"
echo "---------------------------------------"
echo "Si tout est ✅ vert ci-dessus, votre configuration est CORRECTE."
echo "Si vous voyez du ❌ rouge, consultez FICHIERS_UTILISES.md"
echo ""
echo "Pour commit vos changements :"
echo "  git add <fichier>"
echo "  git commit -m 'description'"
echo "  git push"
echo ""
echo "🔍 =========================================="
