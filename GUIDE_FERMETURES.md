# 🔒 Gestion des Fermetures - Pizza Club

## Vue d'ensemble

Le système de gestion des fermetures permet de :
1. **Fermer les commandes immédiatement** (fermeture anticipée)
2. **Programmer des fermetures à l'avance** (jours fériés, congés, etc.)

---

## 🚨 Fermeture Anticipée (Fermer Maintenant)

### Cas d'usage
- Départ plus tôt que prévu
- Problème technique
- Rupture de stock majeure
- Événement imprévu

### Comment faire
1. Accéder à l'interface admin : `https://www.pizzaclub.re/admin-indispos-manager.php`
2. Aller dans l'onglet **"Fermetures"**
3. Dans la section **"Fermeture Anticipée"** :
   - Indiquer une raison (optionnel)
   - Cliquer sur **"Fermer les commandes maintenant"**
4. Confirmer l'action

### Effet
- Les commandes sont **immédiatement fermées** pour le reste de la journée
- Les clients voient un message indiquant que le restaurant est fermé
- La fermeture est automatiquement enregistrée

### Réactivation
Pour réactiver les commandes :
1. Aller dans l'onglet **"Fermetures"**
2. Cliquer sur **"Réactiver"** à côté de la fermeture d'urgence

---

## 🏖️ Fermeture Vacances (période de date à date)

### Cas d'usage
- Congés annuels sur plusieurs jours (ex: 1 semaine, 2 semaines)
- Fermeture estivale
- Toute période de fermeture continue connue à l'avance

### Comment faire
1. Accéder à l'interface admin : `https://www.pizzaclub.re/admin-indispos-manager.php`
2. Aller dans l'onglet **"Fermetures"**
3. Dans la section **"Fermeture Vacances (période)"** :
   - **Date de début** et **Date de fin** (incluses toutes les deux)
   - **Raison** (optionnel, ex: "Congés annuels")
4. Cliquer sur **"Programmer ces vacances"** puis confirmer

### Effet
- Les commandes en ligne sont fermées **chaque jour de la période**, du matin au soir
- Cela couvre **tous les services, midi et soir**, quel que soit le jour de la semaine
- Les clients voient un message dédié (ex: "🔒 Commandes fermées : Congés annuels. Réouverture le 05/01/2026.")
- **Réouverture automatique** le lendemain de la date de fin — aucune action nécessaire

### Suppression / modification
- Une période peut être supprimée à tout moment via **"Supprimer"** dans la liste
- Pour modifier une période, la supprimer puis en recréer une nouvelle avec les bonnes dates

---

## 📅 Fermetures Programmées

### Cas d'usage
- Jours fériés (Noël, Jour de l'An, etc.)
- Congés annuels
- Événements spéciaux
- Fermetures exceptionnelles planifiées

### Comment programmer une fermeture

1. Accéder à l'interface admin
2. Aller dans l'onglet **"Fermetures"**
3. Dans la section **"Fermetures Programmées"** :
   - **Date** : Choisir la date de fermeture
   - **Raison** : Indiquer la raison (ex: "Noël", "Congés annuels")
   - **Horaires** (optionnel) :
     - Laisser vide pour fermer **toute la journée**
     - Ou indiquer une heure de début et fin pour une fermeture partielle
4. Cliquer sur **"Programmer cette fermeture"**

### Exemples

#### Fermeture toute la journée (Noël)
```
Date : 25/12/2025
Raison : Noël
Heure de début : (vide)
Heure de fin : (vide)
```

#### Fermeture partielle (départ anticipé)
```
Date : 15/01/2026
Raison : Événement spécial
Heure de début : 14:00
Heure de fin : 23:59
```

### Gestion des fermetures

- **Visualisation** : Toutes les fermetures programmées sont listées
- **Statuts** :
  - 🔴 **AUJOURD'HUI** : Fermeture active
  - 🔵 **À VENIR** : Fermeture future
- **Suppression** : Cliquer sur "Supprimer" pour annuler une fermeture

---

## 🔧 Intégration avec le site

### Vérification automatique

Le fichier `check-closure.php` permet de vérifier si le restaurant est fermé.

#### Utilisation dans votre formulaire de commande

```javascript
// Vérifier si le restaurant est fermé
fetch('check-closure.php')
    .then(response => response.json())
    .then(data => {
        if (data.isClosed) {
            // Afficher un message et désactiver les commandes
            alert(data.message);
            // Désactiver le bouton de commande
            document.getElementById('order-button').disabled = true;
        }
    });
```

#### Réponse de l'API

**Restaurant ouvert :**
```json
{
    "isClosed": false,
    "reason": null
}
```

**Restaurant fermé (urgence) :**
```json
{
    "isClosed": true,
    "reason": "Départ anticipé",
    "type": "emergency",
    "message": "🚨 Restaurant fermé : Départ anticipé"
}
```

**Restaurant fermé (programmé) :**
```json
{
    "isClosed": true,
    "reason": "Noël",
    "type": "scheduled",
    "fullDay": true,
    "message": "🔒 Restaurant fermé aujourd'hui : Noël"
}
```

**Restaurant fermé (vacances, période de date à date) :**
```json
{
    "isClosed": true,
    "reason": "Congés annuels",
    "type": "vacation",
    "startDate": "2025-12-24",
    "endDate": "2026-01-04",
    "message": "🔒 Commandes fermées : Congés annuels. Réouverture le 05/01/2026."
}
```

---

## 📁 Structure des données

Les fermetures sont enregistrées dans `unavailability.json` :

```json
{
    "items": { ... },
    "ingredients": { ... },
    "closures": {
        "emergency": {
            "date": "2025-12-24",
            "time": "14:30:00",
            "reason": "Départ anticipé",
            "timestamp": "2025-12-24T14:30:00Z"
        },
        "scheduled": [
            {
                "id": 1703500800000,
                "date": "2025-12-25",
                "reason": "Noël",
                "startTime": null,
                "endTime": null,
                "fullDay": true,
                "createdAt": "2025-12-01T10:00:00Z"
            }
        ],
        "vacations": [
            {
                "id": 1703500800001,
                "startDate": "2025-12-24",
                "endDate": "2026-01-04",
                "reason": "Congés annuels",
                "createdAt": "2025-12-01T10:00:00Z"
            }
        ]
    },
    "lastUpdate": "2025-12-24T14:30:00Z"
}
```

---

## 💡 Conseils d'utilisation

### Fermeture anticipée
- ✅ Utiliser pour les situations **immédiates et imprévues**
- ✅ Penser à réactiver les commandes le lendemain si nécessaire
- ℹ️ La fermeture est valable jusqu'à minuit du jour en cours

### Fermetures programmées
- ✅ Programmer **à l'avance** les jours fériés connus
- ✅ Vérifier régulièrement la liste des fermetures à venir
- ✅ Supprimer les fermetures qui ne sont plus d'actualité
- ℹ️ Les fermetures passées ne s'affichent plus automatiquement

### Bonnes pratiques
1. **Anticiper** : Programmer les jours fériés en début d'année
2. **Communiquer** : Indiquer des raisons claires pour informer les clients
3. **Vérifier** : Consulter la liste avant de partir en congés
4. **Nettoyer** : Supprimer les anciennes fermetures programmées si les plans changent

---

## 🆘 Support

En cas de problème :
1. Vérifier que le fichier `unavailability.json` est accessible et modifiable
2. Vérifier les permissions du serveur (chmod 644 pour le JSON)
3. Consulter les logs du serveur PHP en cas d'erreur

---

## 🔄 Mises à jour

**Version 2.0** (Décembre 2025)
- ✨ Ajout de la fermeture anticipée
- ✨ Ajout des fermetures programmées
- ✨ API de vérification des fermetures
- ✨ Interface intuitive avec badges de statut
