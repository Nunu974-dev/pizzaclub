# Mon standard — Pizza Club (version PHP, pour pizzaclub.re/monstandard/)

## 1. Préparer le fichier .env (sur votre Mac)
1. Ouvrez `.env` (fichier caché : Cmd+Maj+. dans le Finder) avec TextEdit.
2. Tapez votre mot de passe StandardFacile entre les guillemets de `SF_PASSWORD=""`. Le numéro est déjà rempli.
3. Dans le Terminal : `cd` vers ce dossier puis `php make-hash.php` → choisissez le mot de passe d'accès à l'application (12 caractères min.). Il est enregistré haché dans `.env`.

## 2. Mettre en ligne
- Copiez le dossier `monstandard/` dans le projet du site (même niveau que `index.html`).
- `.env` n'est PAS versionné ni déployé automatiquement (règles `.gitignore` et workflow existantes) : envoyez-le À LA MAIN dans `public_html/monstandard/` via le Gestionnaire de fichiers Hostinger, puis fixez ses droits à 600 (ou 640).
- Vérifiez que `https://pizzaclub.re/monstandard/.env` affiche une erreur « interdit » (jamais le contenu !).
- Le dossier `data/` est créé/rempli par l'application ; il est protégé par son `.htaccess`.

## 3. Sécurité
- Ne réutilisez pas d'autre mot de passe existant. HTTPS obligatoire (activé chez Hostinger).
- Après 5 essais ratés, la connexion est bloquée 15 minutes.
- Le navigateur ne reçoit jamais vos identifiants StandardFacile.

## 4. Tester prudemment (dans cet ordre)
Numéro (changer puis remettre) → horaires (idem) → annonces. Un changement n'est « Appliqué et vérifié » que si la relecture chez StandardFacile correspond ; sinon c'est un échec avec le motif.
