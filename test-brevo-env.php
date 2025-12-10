<?php
/**
 * TEST SMS BREVO avec brevo-config.php
 * Upload sur ton serveur et accède à : https://www.pizzaclub.re/test-brevo-env.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🧪 Test SMS Brevo (brevo-config.php)</h1>";
echo "<p style='color: #666;'>Version test: 20251211d</p>";
echo "<hr>";

// 1. Vérifier fichier brevo-config.php
echo "<h2>1️⃣ Vérification fichier brevo-config.php</h2>";

$configPaths = [
    __DIR__ . '/config/brevo-config.php',
    __DIR__ . '/brevo-config.php'
];

$configFound = false;
$configPath = '';

foreach ($configPaths as $path) {
    echo "Recherche : <code>" . $path . "</code><br>";
    if (file_exists($path)) {
        echo "✅ <strong>TROUVÉ !</strong><br>";
        $brevoConfig = require $path;
        $configFound = true;
        $configPath = $path;
        break;
    } else {
        echo "❌ Introuvable<br>";
    }
}

if (!$configFound) {
    echo "<br>❌ <strong>FICHIER brevo-config.php INTROUVABLE</strong><br>";
    echo "➡️ Tu dois uploader brevo-config.php dans le dossier <code>config/</code><br>";
    die();
}

echo "<br>";

// 2. Lire les valeurs
echo "<h2>2️⃣ Valeurs du fichier config</h2>";

$brevoApiKey = $brevoConfig['api_key'];
$brevoSender = $brevoConfig['sender_name'];
$brevoRecipient = $brevoConfig['recipient_number'];

if (!$brevoApiKey) {
    echo "❌ <strong>API Key non trouvée dans le fichier</strong><br>";
    die();
}

echo "✅ API Key : " . substr($brevoApiKey, 0, 20) . "... (longueur: " . strlen($brevoApiKey) . ")<br>";
echo "✅ Sender : " . htmlspecialchars($brevoSender) . "<br>";
echo "✅ Recipient : " . htmlspecialchars($brevoRecipient) . "<br>";

// 3. Test envoi SMS
echo "<h2>3️⃣ Test envoi SMS</h2>";

$smsMessage = "TEST Pizza Club\nDate: " . date('d/m/Y H:i') . "\nVersion: 20251211d\nConfig: brevo-config.php";

echo "Message à envoyer :<br>";
echo "<pre>" . htmlspecialchars($smsMessage) . "</pre>";

$brevoUrl = "https://api.brevo.com/v3/transactionalSMS/sms";

$brevoData = [
    'sender' => $brevoSender,
    'recipient' => $brevoRecipient,
    'content' => $smsMessage,
    'type' => 'transactional'
];

echo "Données JSON :<br>";
echo "<pre>" . json_encode($brevoData, JSON_PRETTY_PRINT) . "</pre>";

// Envoi CURL
$ch = curl_init($brevoUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($brevoData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'api-key: ' . $brevoApiKey,
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$brevoResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "<h2>4️⃣ Résultat</h2>";
echo "Code HTTP : <strong>" . $httpCode . "</strong><br>";

if ($curlError) {
    echo "❌ Erreur CURL : " . htmlspecialchars($curlError) . "<br>";
}

echo "Réponse Brevo :<br>";
echo "<pre>" . htmlspecialchars($brevoResponse) . "</pre>";

if ($httpCode === 201 || $httpCode === 200) {
    echo "<h3 style='color: green;'>✅ SMS ENVOYÉ AVEC SUCCÈS !</h3>";
    echo "Vérifie ton téléphone : " . htmlspecialchars($brevoRecipient);
} else {
    echo "<h3 style='color: red;'>❌ SMS NON ENVOYÉ</h3>";
    
    $responseData = json_decode($brevoResponse, true);
    if ($responseData) {
        echo "Détails erreur :<br>";
        echo "<pre>" . json_encode($responseData, JSON_PRETTY_PRINT) . "</pre>";
    }
    
    if ($httpCode === 401) {
        echo "<strong>➡️ API Key invalide ou expirée</strong><br>";
        echo "Vérifie ta clé API sur : <a href='https://app.brevo.com/settings/keys/api' target='_blank'>Brevo API Keys</a>";
    } elseif ($httpCode === 403) {
        echo "<strong>➡️ Accès refusé - vérifie ton compte Brevo</strong>";
    }
}
?>
