<?php
/**
 * TEST ENVOI EMAIL VIA BREVO API
 * Upload sur serveur et accède à : https://www.pizzaclub.re/test-brevo-email.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Indian/Reunion');

echo "<h1>🧪 Test envoi email via Brevo API</h1>";
echo "<p>Date: " . date('d/m/Y H:i:s') . "</p>";
echo "<hr>";

// Charger la config Brevo
$configPaths = [
    __DIR__ . '/config/brevo-config.php',
    __DIR__ . '/brevo-config.php'
];

$brevoApiKey = null;
$configFound = false;

foreach ($configPaths as $configPath) {
    if (file_exists($configPath)) {
        echo "✓ Config trouvé: $configPath<br>";
        $brevoConfig = require $configPath;
        $brevoApiKey = $brevoConfig['api_key'];
        $configFound = true;
        break;
    }
}

// Fallback sur variable d'environnement
if (!$configFound && getenv('BREVO_API_KEY')) {
    echo "✓ Config depuis variables d'environnement<br>";
    $brevoApiKey = getenv('BREVO_API_KEY');
    $configFound = true;
}

if (!$brevoApiKey) {
    echo "<p style='color: red;'><strong>❌ ERREUR: Aucune clé API Brevo trouvée!</strong></p>";
    echo "<p>Vérifie que config/brevo-config.php existe et contient 'api_key'</p>";
    exit;
}

echo "<p style='color: green;'><strong>✓ Clé API Brevo trouvée</strong></p>";
echo "<p>API Key: " . substr($brevoApiKey, 0, 20) . "...</p>";
echo "<hr>";

// Test 1: Email simple
echo "<h2>1️⃣ Test email simple (texte)</h2>";

$brevoUrl = "https://api.brevo.com/v3/smtp/email";

$emailData = [
    'sender' => [
        'name' => 'Pizza Club TEST',
        'email' => 'commande@pizzaclub.re'
    ],
    'to' => [
        [
            'email' => 'commande@pizzaclub.re',
            'name' => 'Test Cuisine'
        ]
    ],
    'subject' => 'TEST Email Brevo - ' . date('H:i:s'),
    'textContent' => "Ceci est un test d'envoi email via Brevo API\nDate: " . date('d/m/Y H:i:s') . "\nServeur: " . $_SERVER['SERVER_NAME']
];

echo "<p><strong>Envoi vers:</strong> commande@pizzaclub.re</p>";
echo "<p><strong>Sujet:</strong> " . htmlspecialchars($emailData['subject']) . "</p>";

$ch = curl_init($brevoUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'api-key: ' . $brevoApiKey,
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> $httpCode</p>";

if ($httpCode === 201 || $httpCode === 200) {
    echo "<p style='color: green; font-size: 20px;'><strong>✅ EMAIL ENVOYÉ AVEC SUCCÈS!</strong></p>";
    $responseData = json_decode($response, true);
    echo "<p>Message ID: " . ($responseData['messageId'] ?? 'N/A') . "</p>";
} else {
    echo "<p style='color: red; font-size: 20px;'><strong>❌ ÉCHEC DE L'ENVOI</strong></p>";
    echo "<p><strong>Réponse API:</strong></p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    if ($curlError) {
        echo "<p><strong>Erreur CURL:</strong> " . htmlspecialchars($curlError) . "</p>";
    }
}

echo "<hr>";

// Test 2: Email HTML (comme pour une vraie commande)
echo "<h2>2️⃣ Test email HTML (format commande)</h2>";

$htmlContent = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; }
        .header { background-color: #FF0000; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; }
        .footer { background-color: #333; color: white; padding: 10px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚨 TEST COMMANDE BREVO</h1>
    </div>
    <div class="content">
        <h2>Test d'email HTML via Brevo API</h2>
        <p>Si vous recevez cet email, l'intégration Brevo fonctionne parfaitement!</p>
        <p><strong>Date:</strong> {DATE}</p>
        <p><strong>Serveur:</strong> {SERVER}</p>
    </div>
    <div class="footer">
        <p>Pizza Club - Test Brevo API</p>
    </div>
</body>
</html>
HTML;

$htmlContent = str_replace('{DATE}', date('d/m/Y H:i:s'), $htmlContent);
$htmlContent = str_replace('{SERVER}', $_SERVER['SERVER_NAME'], $htmlContent);

$emailDataHtml = [
    'sender' => [
        'name' => 'Pizza Club TEST',
        'email' => 'commande@pizzaclub.re'
    ],
    'to' => [
        [
            'email' => 'commande@pizzaclub.re',
            'name' => 'Test Cuisine HTML'
        ]
    ],
    'subject' => 'TEST Email HTML Brevo - ' . date('H:i:s'),
    'htmlContent' => $htmlContent,
    'headers' => [
        'X-Priority' => '1',
        'Importance' => 'High'
    ]
];

echo "<p><strong>Envoi vers:</strong> commande@pizzaclub.re</p>";
echo "<p><strong>Sujet:</strong> " . htmlspecialchars($emailDataHtml['subject']) . "</p>";

$ch = curl_init($brevoUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailDataHtml));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'api-key: ' . $brevoApiKey,
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> $httpCode</p>";

if ($httpCode === 201 || $httpCode === 200) {
    echo "<p style='color: green; font-size: 20px;'><strong>✅ EMAIL HTML ENVOYÉ AVEC SUCCÈS!</strong></p>";
    $responseData = json_decode($response, true);
    echo "<p>Message ID: " . ($responseData['messageId'] ?? 'N/A') . "</p>";
} else {
    echo "<p style='color: red; font-size: 20px;'><strong>❌ ÉCHEC DE L'ENVOI</strong></p>";
    echo "<p><strong>Réponse API:</strong></p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    if ($curlError) {
        echo "<p><strong>Erreur CURL:</strong> " . htmlspecialchars($curlError) . "</p>";
    }
}

echo "<hr>";

echo "<h2>📊 Résumé</h2>";
echo "<p>Si les deux tests sont OK (code 200 ou 201), l'envoi d'emails via Brevo fonctionne!</p>";
echo "<p><strong>Prochaines étapes:</strong></p>";
echo "<ul>";
echo "<li>Teste une vraie commande sur le site</li>";
echo "<li>Vérifie que tu reçois l'email cuisine</li>";
echo "<li>Vérifie les logs dans debug-order.txt</li>";
echo "</ul>";
?>
