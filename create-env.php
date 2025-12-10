<?php
// Script à exécuter UNE SEULE FOIS sur le serveur pour créer le .env

$envContent = <<<ENV
BREVO_API_KEY=xkeysib-798b0d84ce90fb64d5b8e76c01abc472fbd3e37f5c4b29a18f6d7e90a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4
BREVO_SENDER=PizzaClub
BREVO_RECIPIENT=+262692630364
ENV;

$envPath = __DIR__ . '/.env';

if (file_exists($envPath)) {
    echo "✓ Le fichier .env existe déjà<br>";
    echo "Contenu actuel :<br><pre>";
    echo htmlspecialchars(file_get_contents($envPath));
    echo "</pre>";
} else {
    if (file_put_contents($envPath, $envContent)) {
        echo "✓ Fichier .env créé avec succès !<br>";
        echo "Permissions : " . substr(sprintf('%o', fileperms($envPath)), -4) . "<br>";
        echo "Chemin : $envPath<br>";
        
        // Vérifier lecture
        if (is_readable($envPath)) {
            echo "✓ Fichier lisible<br>";
        } else {
            echo "❌ Fichier non lisible<br>";
        }
    } else {
        echo "❌ Erreur lors de la création du .env<br>";
        echo "Permissions du dossier : " . substr(sprintf('%o', fileperms(__DIR__)), -4) . "<br>";
    }
}

echo "<br><strong>IMPORTANT : Supprime ce fichier create-env.php après utilisation !</strong>";
?>
