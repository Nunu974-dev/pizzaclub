# 📦 Archives d'Inventaire

Ce dossier contient les archives des inventaires passés.

## Format des fichiers

Les fichiers d'archive sont au format JSON avec la structure suivante :

```json
{
  "archiveDate": "2026-01-19 14:30:00",
  "archiveTimestamp": 1737291000,
  "itemCount": 150,
  "originalData": {
    "inventory": [
      {
        "name": "Article 1",
        "quantity": 10,
        "unit": "kg"
      }
    ],
    "lastUpdate": "2026-01-19T14:30:00Z"
  }
}
```

## Nomenclature

Format : `inventaire_archive_YYYY-MM-DD_HHMMSS.json`

Exemple : `inventaire_archive_2026-01-19_143000.json`

## Gestion

- ✅ Les archives sont créées automatiquement lors de l'archivage depuis le dashboard
- ✅ Elles sont conservées indéfiniment (pas de suppression automatique)
- ⚠️ Pensez à télécharger régulièrement ces fichiers sur un stockage externe
- 🗑️ Nettoyage manuel recommandé après sauvegarde externe

## Restauration

Pour restaurer une archive :

1. **Via le dashboard** : Téléchargez l'archive et importez manuellement
2. **Manuellement** : Copiez le contenu de `originalData` dans `inventory.json`

## Sécurité

Ce dossier est exclu du versioning Git (`.gitignore`) pour des raisons de confidentialité.
