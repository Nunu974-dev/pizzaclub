# Configuration persistante

Le dossier `config/` contient les fichiers de configuration avec clés API.

**Ce dossier est ignoré par Git** pour éviter qu'il soit écrasé lors des déploiements.

## Fichiers à placer ici :

- `brevo-config.php` - Configuration SMS Brevo
- `pco-config.php` - Passerelle vers la caisse PizzaClubOrders (voir ci-dessous)
- Autres fichiers de config sensibles

## Upload sur Hostinger :

1. Créer le dossier `config/` à la racine du site
2. Uploader `brevo-config.php` dedans via FTP/cPanel
3. Le fichier restera intact même après les git push

## Passerelle caisse : `pco-config.php`

Transmet chaque commande du site à la caisse (alerte sonore + impression).
Sans ce fichier, le site fonctionne exactement comme avant.

```php
<?php
return [
    'supabase_url'    => 'https://xxxx.supabase.co',
    'publishable_key' => 'sb_publishable_...',
    'ingest_key'      => 'pco_...', // Admin caisse > Caisse & réglages > Passerelles
    'restaurant'      => 'pizza-club',
];
```

En cas de coupure, les commandes sont gardées dans `orders/pco-retry.jsonl`
et renvoyées automatiquement à la commande suivante (sans doublon).
