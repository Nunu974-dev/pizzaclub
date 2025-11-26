<?php
/**
 * Test WhatsApp API - Diagnostic
 * Accéder via: http://votre-site.com/test-whatsapp.php
 */

// Activer l'affichage des erreurs pour debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html lang='fr'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Test WhatsApp API - Pizza Club</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }";
echo "h1 { color: #333; border-bottom: 3px solid #25D366; padding-bottom: 10px; }";
echo "h2 { color: #555; margin-top: 30px; }";
echo "pre { background: #fff; padding: 15px; border-radius: 5px; border: 1px solid #ddd; overflow-x: auto; }";
echo ".success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 5px solid #28a745; }";
echo ".error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 5px solid #dc3545; }";
echo ".info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 5px solid #17a2b8; }";
echo ".checklist { background: #fff; padding: 20px; border-radius: 5px; }";
echo ".checklist li { margin: 10px 0; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<h1>🔍 Test WhatsApp Business API - Pizza Club</h1>";

// Vérifier que le fichier de config existe
if (!file_exists(__DIR__ . '/whatsapp-config.php')) {
    echo "<div class='error'>";
    echo "<strong>❌ ERREUR CRITIQUE</strong><br>";
    echo "Le fichier <code>whatsapp-config.php</code> est introuvable !<br>";
    echo "Chemin recherché : " . __DIR__ . "/whatsapp-config.php";
    echo "</div>";
    echo "</body></html>";
    exit;
}

// Charger la configuration
try {
    $config = require __DIR__ . '/whatsapp-config.php';
    echo "<div class='success'>✅ Fichier de configuration chargé avec succès</div>";
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<strong>❌ ERREUR</strong><br>";
    echo "Impossible de charger whatsapp-config.php : " . $e->getMessage();
    echo "</div>";
    echo "</body></html>";
    exit;
}

// Vérifier la configuration
echo "<h2>1. Configuration</h2>";
echo "<pre>";
echo "Phone Number ID: " . $config['phone_number_id'] . "\n";
echo "API Version: " . $config['api_version'] . "\n";
echo "Recipient: " . $config['recipient_number'] . "\n";
echo "Token: " . substr($config['access_token'], 0, 20) . "..." . substr($config['access_token'], -10) . "\n";
echo "Token Length: " . strlen($config['access_token']) . " caractères\n";
echo "</pre>";

// Vérifier que cURL est disponible
if (!function_exists('curl_init')) {
    echo "<div class='error'>";
    echo "<strong>❌ ERREUR</strong><br>";
    echo "L'extension PHP cURL n'est pas installée sur ce serveur !<br>";
    echo "Contacte ton hébergeur pour activer cURL.";
    echo "</div>";
    echo "</body></html>";
    exit;
}
echo "<div class='success'>✅ Extension cURL disponible</div>";

// Test 1: Vérifier que le token est valide
echo "<h2>2. Test du token (GET request)</h2>";
echo "<div class='info'>🔍 Vérification du token d'accès...</div>";
$testUrl = "https://graph.facebook.com/{$config['api_version']}/{$config['phone_number_id']}";
$ch = curl_init($testUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $config['access_token']
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "<pre>";
echo "URL: " . $testUrl . "\n";
echo "HTTP Code: " . $httpCode . "\n";
if ($curlError) {
    echo "CURL Error: " . $curlError . "\n";
}
echo "Response: " . $response . "\n";
echo "</pre>";

if ($httpCode === 200) {
    echo "<div class='success'><strong>✅ Token valide !</strong></div>";
} else {
    echo "<div class='error'>";
    echo "<strong>❌ Token invalide ou expiré</strong><br>";
    $errorData = json_decode($response, true);
    if (isset($errorData['error'])) {
        echo "Erreur: " . $errorData['error']['message'] . "<br>";
        echo "<strong>💡 Solution:</strong> Génère un nouveau token sur Facebook Developers";
    }
    echo "</div>";
}

// Test 2: Envoyer un message de test
if ($httpCode === 200) {
    echo "<h2>3. Envoi d'un message de test</h2>";
    echo "<div class='info'>📤 Tentative d'envoi d'un message de test...</div>";
    
    $messageUrl = "https://graph.facebook.com/{$config['api_version']}/{$config['phone_number_id']}/messages";
    $messageData = [
        'messaging_product' => 'whatsapp',
        'to' => $config['recipient_number'],
        'type' => 'text',
        'text' => [
            'body' => '🧪 Message de test - ' . date('d/m/Y H:i:s')
        ]
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
    echo "URL: " . $messageUrl . "\n";
    echo "HTTP Code: " . $msgHttpCode . "\n";
    if ($msgCurlError) {
        echo "CURL Error: " . $msgCurlError . "\n";
    }
    echo "Response: " . $msgResponse . "\n";
    echo "</pre>";
    
    if ($msgHttpCode === 200) {
        echo "<div class='success'>";
        echo "<strong>✅ Message envoyé avec succès !</strong><br>";
        echo "Vérifie WhatsApp sur le numéro <strong>{$config['recipient_number']}</strong>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<strong>❌ Échec de l'envoi</strong><br>";
        $errorData = json_decode($msgResponse, true);
        if (isset($errorData['error'])) {
            echo "Code erreur: <strong>" . $errorData['error']['code'] . "</strong><br>";
            echo "Message: " . $errorData['error']['message'] . "<br><br>";
            
            // Solutions courantes
            if ($errorData['error']['code'] == 131026) {
                echo "<strong>💡 Solution:</strong> Le numéro destinataire doit être vérifié dans Meta Business Suite<br>";
                echo "→ Va dans WhatsApp → API Setup → Add recipient phone number";
            } elseif ($errorData['error']['code'] == 133) {
                echo "<strong>💡 Solution:</strong> Le numéro n'est pas au bon format<br>";
                echo "→ Format attendu: 262692620062 (sans espaces, sans +)";
            } elseif ($errorData['error']['code'] == 190) {
                echo "<strong>💡 Solution:</strong> Token expiré ou invalide<br>";
                echo "→ Génère un nouveau token sur Facebook Developers";
            } else {
                echo "<strong>💡 Solution:</strong> Vérifie la documentation de l'erreur code " . $errorData['error']['code'];
            }
        }
        echo "</div>";
    }
} else {
    echo "<div class='error'>";
    echo "⚠️ Test d'envoi de message ignoré car le token est invalide.<br>";
    echo "Corrige d'abord le problème de token ci-dessus.";
    echo "</div>";
}

echo "<hr>";
echo "<div class='checklist'>";
echo "<h2>📋 Checklist</h2>";
echo "<ol>";
echo "<li>✓ Compte WhatsApp Business créé sur <a href='https://business.facebook.com/' target='_blank'>Meta Business Suite</a></li>";
echo "<li>✓ Numéro de téléphone vérifié et connecté</li>";
echo "<li>✓ Token d'accès généré (valide 24h pour token temporaire)</li>";
echo "<li>✓ Numéro destinataire ajouté aux \"Recipient Phone Numbers\" dans l'API Setup</li>";
echo "<li>✓ App en mode production (pas development)</li>";
echo "</ol>";
echo "</div>";

echo "<hr>";
echo "<div class='info'>";
echo "<strong>💡 Comment obtenir un nouveau token :</strong><br>";
echo "1. Va sur <a href='https://developers.facebook.com/apps' target='_blank'>Facebook Developers</a><br>";
echo "2. Sélectionne ton app WhatsApp Business<br>";
echo "3. Menu : WhatsApp → API Setup<br>";
echo "4. Copie le token temporaire (ou génère un token permanent)<br>";
echo "5. Colle-le dans <code>whatsapp-config.php</code>";
echo "</div>";

echo "<p style='text-align: center; color: #999; margin-top: 30px;'><small>Test effectué le " . date('d/m/Y à H:i:s') . "</small></p>";
echo "</body></html>";
?>
