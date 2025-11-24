<?php
// Configuration
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Empêcher l'accès direct
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

// Récupérer les données JSON
$jsonData = file_get_contents('php://input');
$orderData = json_decode($jsonData, true);

if (!$orderData) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Données invalides']);
    exit;
}

// Configuration email
$to = 'commande@pizzaclub.re';
$subject = 'Nouvelle commande ' . $orderData['orderNumber'];

// Construction du contenu email
$deliveryMode = $orderData['customer']['deliveryMode'] === 'livraison' ? 'LIVRAISON' : 'À EMPORTER';
$deliveryAddress = '';
if ($orderData['customer']['deliveryMode'] === 'livraison') {
    $deliveryAddress = "\nAdresse:\n" . $orderData['customer']['address'] . "\n" . 
                       $orderData['customer']['postalCode'] . " " . $orderData['customer']['city'];
}

// Formater les articles avec détails complets
$itemsList = '';
foreach ($orderData['items'] as $item) {
    $itemsList .= $item['name'];
    
    // Ajouter la taille si présente
    if (!empty($item['size'])) {
        $itemsList .= " (" . $item['size'] . ")";
    }
    
    // Ajouter les suppléments si présents
    if (!empty($item['supplements']) && is_array($item['supplements']) && count($item['supplements']) > 0) {
        $itemsList .= "\n  Suppléments: " . implode(', ', $item['supplements']);
    }
    
    // Ajouter les options si présentes
    if (!empty($item['options'])) {
        $itemsList .= "\n  Options: " . $item['options'];
    }
    
    // Ajouter la quantité et le prix
    $itemsList .= "\n  Quantité: x" . $item['quantity'];
    $itemsList .= " - Prix unitaire: " . number_format($item['price'], 2) . "€";
    $itemsList .= " - Total: " . number_format($item['totalPrice'], 2) . "€\n\n";
}

// Corps de l'email
$message = "NOUVELLE COMMANDE - " . $orderData['orderNumber'] . "\n\n";
$message .= "CLIENT:\n";
$message .= $orderData['customer']['firstName'] . " " . $orderData['customer']['lastName'] . "\n";
$message .= "Tel: " . $orderData['customer']['phone'] . "\n";
$message .= "Email: " . ($orderData['customer']['email'] ?: 'Non renseigné') . "\n\n";
$message .= "MODE: " . $deliveryMode . $deliveryAddress . "\n\n";
$message .= "COMMANDE:\n" . $itemsList . "\n";
$message .= "Sous-total: " . number_format($orderData['subtotal'], 2) . "€\n";
$message .= "Frais de livraison: " . number_format($orderData['deliveryFee'], 2) . "€\n";
$message .= "TOTAL: " . number_format($orderData['total'], 2) . "€\n\n";
$message .= "Temps estimé: " . $orderData['estimatedTime'] . "\n";
if (!empty($orderData['customer']['comments'])) {
    $message .= "\nCommentaire: " . $orderData['customer']['comments'];
}

// Headers pour l'email
$headers = "From: Pizza Club <noreply@pizzaclub.re>\r\n";
$headers .= "Reply-To: " . ($orderData['customer']['email'] ?: 'noreply@pizzaclub.re') . "\r\n";
$headers .= "Return-Path: noreply@pizzaclub.re\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
$headers .= "X-Priority: 1\r\n";
$headers .= "Importance: High\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// Envoi de l'email au restaurant
$emailSent = mail($to, $subject, $message, $headers);

// Envoi de l'email de confirmation au client
$clientEmailSent = false;
if (!empty($orderData['customer']['email'])) {
    $clientSubject = 'Confirmation de commande ' . $orderData['orderNumber'] . ' - Pizza Club';
    
    // Utiliser le template HTML
    require_once __DIR__ . '/email-template.php';
    $clientMessage = getClientEmailTemplate($orderData);
    
    $clientHeaders = "From: Pizza Club <commande@pizzaclub.re>\r\n";
    $clientHeaders .= "Reply-To: commande@pizzaclub.re\r\n";
    $clientHeaders .= "Return-Path: commande@pizzaclub.re\r\n";
    $clientHeaders .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    $clientHeaders .= "MIME-Version: 1.0\r\n";
    $clientHeaders .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    $clientEmailSent = mail($orderData['customer']['email'], $clientSubject, $clientMessage, $clientHeaders);
}

// Envoi WhatsApp via API (nécessite un compte WhatsApp Business API)
$whatsappSent = false;
$whatsappNumber = '262692620062';

// Message WhatsApp
$whatsappMessage = "🍕 *NOUVELLE COMMANDE {$orderData['orderNumber']}*\n\n";
$whatsappMessage .= "👤 *CLIENT*\n";
$whatsappMessage .= "{$orderData['customer']['firstName']} {$orderData['customer']['lastName']}\n";
$whatsappMessage .= "📞 {$orderData['customer']['phone']}\n";
$whatsappMessage .= "📧 " . ($orderData['customer']['email'] ?: 'Non renseigné') . "\n\n";
$whatsappMessage .= "🚚 *MODE:* " . ($orderData['customer']['deliveryMode'] === 'livraison' ? '🛵 LIVRAISON' : '🏃 À EMPORTER') . "\n";
if ($orderData['customer']['deliveryMode'] === 'livraison') {
    $whatsappMessage .= "📍 {$orderData['customer']['address']}, {$orderData['customer']['postalCode']} {$orderData['customer']['city']}\n\n";
} else {
    $whatsappMessage .= "\n";
}
$whatsappMessage .= "📦 *COMMANDE:*\n";
foreach ($orderData['items'] as $item) {
    $whatsappMessage .= "• {$item['name']} x{$item['quantity']} - " . number_format($item['totalPrice'], 2) . "€\n";
}
$whatsappMessage .= "\n💰 Sous-total: " . number_format($orderData['subtotal'], 2) . "€\n";
$whatsappMessage .= "🚚 Livraison: " . number_format($orderData['deliveryFee'], 2) . "€\n";
$whatsappMessage .= "*💵 TOTAL: " . number_format($orderData['total'], 2) . "€*\n\n";
$whatsappMessage .= "⏱️ Temps estimé: {$orderData['estimatedTime']}";
if (!empty($orderData['customer']['comments'])) {
    $whatsappMessage .= "\n\n💬 {$orderData['customer']['comments']}";
}

// Option 1: Utiliser l'API WhatsApp Business (nécessite configuration)
// Décommentez et configurez si vous avez un compte WhatsApp Business API
/*
$whatsappApiUrl = 'https://graph.facebook.com/v17.0/YOUR_PHONE_NUMBER_ID/messages';
$whatsappToken = 'YOUR_WHATSAPP_TOKEN';

$whatsappData = [
    'messaging_product' => 'whatsapp',
    'to' => $whatsappNumber,
    'type' => 'text',
    'text' => ['body' => $whatsappMessage]
];

$ch = curl_init($whatsappApiUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($whatsappData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $whatsappToken,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$whatsappResponse = curl_exec($ch);
curl_close($ch);

$whatsappSent = !empty($whatsappResponse);
*/

// Option 2: Utiliser CallMeBot (gratuit, simple, pas besoin d'API)
// Inscription sur https://www.callmebot.com/blog/free-api-whatsapp-messages/
$callmebotApiKey = 'YOUR_CALLMEBOT_API_KEY'; // À obtenir via CallMeBot

if ($callmebotApiKey !== 'YOUR_CALLMEBOT_API_KEY') {
    $callmebotUrl = 'https://api.callmebot.com/whatsapp.php?' . http_build_query([
        'phone' => $whatsappNumber,
        'text' => $whatsappMessage,
        'apikey' => $callmebotApiKey
    ]);
    
    $ch = curl_init($callmebotUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $whatsappSent = strpos($response, 'Message queued') !== false;
}

// Sauvegarder la commande dans un fichier log
$logDir = __DIR__ . '/orders';
if (!file_exists($logDir)) {
    mkdir($logDir, 0755, true);
}

$logFile = $logDir . '/' . date('Y-m-d') . '.log';
$logEntry = date('Y-m-d H:i:s') . " - " . $orderData['orderNumber'] . " - " . 
            $orderData['customer']['firstName'] . " " . $orderData['customer']['lastName'] . " - " .
            number_format($orderData['total'], 2) . "€\n";
file_put_contents($logFile, $logEntry, FILE_APPEND);

// Sauvegarder les détails complets en JSON
$jsonFile = $logDir . '/' . $orderData['orderNumber'] . '.json';
file_put_contents($jsonFile, $jsonData);

// Réponse
$response = [
    'success' => $emailSent,
    'emailSent' => $emailSent,
    'clientEmailSent' => $clientEmailSent,
    'whatsappSent' => $whatsappSent,
    'orderNumber' => $orderData['orderNumber'],
    'message' => $emailSent ? 'Commande envoyée avec succès' : 'Erreur lors de l\'envoi'
];

echo json_encode($response);
?>
