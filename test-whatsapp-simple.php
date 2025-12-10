<?php
// Version simple du test WhatsApp
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test WhatsApp - Pizza Club</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        h1 { color: #333; }
        pre { background: #fff; padding: 15px; border-radius: 5px; border: 1px solid #ddd; overflow-x: auto; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>🔍 Test WhatsApp - Pizza Club</h1>

<?php
// Vérifier que le fichier existe
$configFile = __DIR__ . '/whatsapp-config.php';
echo "<h2>1. Vérification du fichier de configuration</h2>";

if (!file_exists($configFile)) {
    echo "<div class='error'><strong>❌ ERREUR</strong><br>";
    echo "Fichier whatsapp-config.php introuvable !<br>";
    echo "Chemin : " . $configFile . "</div>";
    exit;
}

echo "<div class='success'>✅ Fichier trouvé : whatsapp-config.php</div>";

// Charger la config
try {
    $config = require $configFile;
    echo "<div class='success'>✅ Configuration chargée avec succès</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</div>";
    exit;
}

// Afficher la config
echo "<h2>2. Configuration actuelle</h2>";
echo "<pre>";
echo "Phone Number ID: " . htmlspecialchars($config['phone_number_id']) . "\n";
echo "API Version: " . htmlspecialchars($config['api_version']) . "\n";
echo "Recipient: " . htmlspecialchars($config['recipient_number']) . "\n";
echo "Token: " . substr($config['access_token'], 0, 20) . "..." . substr($config['access_token'], -10) . "\n";
echo "Token length: " . strlen($config['access_token']) . " caractères\n";
echo "</pre>";

// Vérifier cURL
if (!function_exists('curl_init')) {
    echo "<div class='error'>❌ Extension cURL non disponible !</div>";
    exit;
}
echo "<div class='success'>✅ Extension cURL disponible</div>";

// Test du token
echo "<h2>3. Test du token d'accès</h2>";
$testUrl = "https://graph.facebook.com/{$config['api_version']}/{$config['phone_number_id']}";

$ch = curl_init($testUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $config['access_token']]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "<pre>";
echo "URL testée: " . htmlspecialchars($testUrl) . "\n";
echo "HTTP Code: " . $httpCode . "\n";
if ($curlError) {
    echo "CURL Error: " . htmlspecialchars($curlError) . "\n";
}
echo "Réponse: " . htmlspecialchars($response) . "\n";
echo "</pre>";

if ($httpCode == 200) {
    echo "<div class='success'><strong>✅ Token VALIDE !</strong></div>";
    
    // Test d'envoi de message
    echo "<h2>4. Test d'envoi de message</h2>";
    
    $messageUrl = "https://graph.facebook.com/{$config['api_version']}/{$config['phone_number_id']}/messages";
    $messageData = [
        'messaging_product' => 'whatsapp',
        'to' => $config['recipient_number'],
        'type' => 'text',
        'text' => ['body' => '🧪 Test - ' . date('d/m/Y H:i:s')]
    ];
    
    $ch = curl_init($messageUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($messageData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $config['access_token'],
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $msgResponse = curl_exec($ch);
    $msgHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $msgCurlError = curl_error($ch);
    curl_close($ch);
    
    echo "<pre>";
    echo "HTTP Code: " . $msgHttpCode . "\n";
    if ($msgCurlError) {
        echo "CURL Error: " . htmlspecialchars($msgCurlError) . "\n";
    }
    echo "Réponse: " . htmlspecialchars($msgResponse) . "\n";
    echo "</pre>";
    
    if ($msgHttpCode == 200) {
        echo "<div class='success'><strong>✅ MESSAGE ENVOYÉ !</strong><br>";
        echo "Vérifie WhatsApp sur le " . htmlspecialchars($config['recipient_number']) . "</div>";
    } else {
        echo "<div class='error'><strong>❌ Échec de l'envoi</strong><br>";
        $errorData = @json_decode($msgResponse, true);
        if ($errorData && isset($errorData['error'])) {
            echo "Code: " . htmlspecialchars($errorData['error']['code']) . "<br>";
            echo "Message: " . htmlspecialchars($errorData['error']['message']) . "<br><br>";
            
            // Solutions
            $code = $errorData['error']['code'];
            if ($code == 131026) {
                echo "💡 <strong>Solution :</strong> Ajoute le numéro " . htmlspecialchars($config['recipient_number']) . " dans 'Recipient Phone Numbers' sur Facebook Developers";
            } elseif ($code == 133) {
                echo "💡 <strong>Solution :</strong> Vérifie le format du numéro (doit être 262xxxxxxxxx sans espaces)";
            } elseif ($code == 190) {
                echo "💡 <strong>Solution :</strong> Token expiré, génère un nouveau token";
            }
        }
        echo "</div>";
    }
    
} else {
    echo "<div class='error'><strong>❌ Token INVALIDE ou EXPIRÉ</strong><br>";
    $errorData = @json_decode($response, true);
    if ($errorData && isset($errorData['error'])) {
        echo "Message : " . htmlspecialchars($errorData['error']['message']) . "<br>";
    }
    echo "<br>💡 <strong>Solution :</strong> Va sur <a href='https://developers.facebook.com' target='_blank'>Facebook Developers</a> et génère un nouveau token</div>";
}
?>

<hr>
<div class="info">
    <h3>📋 Comment obtenir un nouveau token :</h3>
    <ol>
        <li>Va sur <a href="https://developers.facebook.com/apps" target="_blank">Facebook Developers</a></li>
        <li>Sélectionne ton app WhatsApp Business</li>
        <li>Menu : WhatsApp → API Setup</li>
        <li>Copie le token temporaire (ou génère un token permanent)</li>
        <li>Colle-le dans whatsapp-config.php</li>
    </ol>
</div>

<p style="text-align: center; color: #999; margin-top: 30px;">
    <small>Test effectué le <?php echo date('d/m/Y à H:i:s'); ?></small>
</p>

</body>
</html>
