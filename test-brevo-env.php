<?php
/**
 * TEST SMS BREVO avec .env
 * Upload sur ton serveur et accède à : https://www.pizzaclub.re/test-brevo-env.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🧪 Test SMS Brevo (.env)</h1>";
echo "<p style='color: #666;'>Version test: 20251211c</p>";
echo "<hr>";

// 1. Vérifier fichier .env
echo "<h2>1️⃣ Vérification fichier .env</h2>";
$envPath = __DIR__ . '/.env';
echo "Chemin recherché : <code>" . $envPath . "</code><br>";

if (!file_exists($envPath)) {
    echo "❌ <strong>FICHIER .env INTROUVABLE</strong><br>";
    echo "➡️ Tu dois uploader le fichier .env à la racine du serveur<br>";
    die();
}

echo "✅ Fichier .env trouvé<br>";

// 2. Lire le fichier .env
echo "<h2>2️⃣ Lecture du fichier .env</h2>";
$envLines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
echo "Nombre de lignes : " . count($envLines) . "<br><br>";

$brevoApiKey = null;
$brevoSender = 'PizzaClub';
$brevoRecipient = '+262692630364';

foreach ($envLines as $line) {
    if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        
        echo "Clé trouvée : <strong>" . htmlspecialchars($key) . "</strong><br>";
        
        if ($key === 'BREVO_API_KEY') $brevoApiKey = $value;
        if ($key === 'BREVO_SENDER') $brevoSender = $value;
        if ($key === 'BREVO_RECIPIENT') $brevoRecipient = $value;
    }
}

echo "<br>";

// 3. Vérifier les valeurs chargées
echo "<h2>3️⃣ Valeurs chargées</h2>";

if (!$brevoApiKey) {
    echo "❌ <strong>BREVO_API_KEY non trouvée</strong><br>";
    echo "➡️ Vérifie que le fichier .env contient bien : BREVO_API_KEY=ta_clé<br>";
    die();
}

echo "✅ API Key : " . substr($brevoApiKey, 0, 20) . "...  (longueur: " . strlen($brevoApiKey) . ")<br>";
echo "✅ Sender : " . htmlspecialchars($brevoSender) . "<br>";
echo "✅ Recipient : " . htmlspecialchars($brevoRecipient) . "<br>";

// 4. Test envoi SMS
echo "<h2>4️⃣ Test envoi SMS</h2>";

$smsMessage = "TEST Pizza Club\nDate: " . date('d/m/Y H:i') . "\nVersion: 20251211c";

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

echo "<h2>5️⃣ Résultat</h2>";
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
        echo "<strong>➡️ API Key invalide ou expirée</strong>";
    } elseif ($httpCode === 403) {
        echo "<strong>➡️ Accès refusé - vérifie ton compte Brevo</strong>";
    }
}
?>
