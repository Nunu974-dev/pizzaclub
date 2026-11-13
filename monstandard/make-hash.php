<?php
// À lancer sur votre Mac : php make-hash.php  — choisit le mot de passe d'accès à l'application.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
fwrite(STDOUT, "Mot de passe de l'application (6 caractères min., masqué) : "); system('stty -echo'); $a = trim(fgets(STDIN)); system('stty echo');
fwrite(STDOUT, "\nConfirmez : "); system('stty -echo'); $b = trim(fgets(STDIN)); system('stty echo'); echo "\n";
if ($a !== $b || strlen($a) < 6) { fwrite(STDERR, "Différents ou trop courts (6 caractères minimum).\n"); exit(1); }
$h = password_hash($a, PASSWORD_DEFAULT); $f = __DIR__ . '/.env'; $e = file_get_contents($f);
$e = preg_replace_callback('/^APP_PASSWORD_HASH=.*$/m', fn() => 'APP_PASSWORD_HASH="' . $h . '"', $e); file_put_contents($f, $e);
echo "✔ Mot de passe de l'application enregistré (haché) dans .env\n";
