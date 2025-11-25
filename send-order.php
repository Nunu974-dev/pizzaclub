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
    // Déterminer le type de produit
    $productType = '';
    if (isset($item['type'])) {
        switch($item['type']) {
            case 'pizza': $productType = '[PIZZA] '; break;
            case 'pate': $productType = '[PÂTE] '; break;
            case 'salade': $productType = '[SALADE] '; break;
            case 'bun': $productType = '[BUN] '; break;
            case 'roll': $productType = '[ROLL] '; break;
            case 'dessert': $productType = '[DESSERT] '; break;
            case 'formule': $productType = '[FORMULE] '; break;
            case 'promo2pizzas': $productType = '[PROMO] '; break;
        }
    } elseif (isset($item['pizzaId'])) {
        $productType = '[PIZZA] ';
    }
    
    $itemsList .= $productType . $item['name'];
    
    // Ajouter la taille si présente (pour pizzas et pâtes)
    if (!empty($item['customization']['size'])) {
        $sizeLabel = '';
        switch($item['customization']['size']) {
            case 'moyenne': $sizeLabel = '33cm'; break;
            case 'grande': $sizeLabel = '40cm'; break;
            case 'L': $sizeLabel = 'Large'; break;
            case 'XL': $sizeLabel = 'XL'; break;
            default: $sizeLabel = $item['customization']['size'];
        }
        $itemsList .= " - Taille: " . $sizeLabel;
    } elseif (!empty($item['size'])) {
        $itemsList .= " - Taille: " . $item['size'];
    }
    
    // Ajouter les suppléments si présents
    if (!empty($item['customization']['supplements']) && is_array($item['customization']['supplements']) && count($item['customization']['supplements']) > 0) {
        // Charger les noms depuis EXTRAS.toppings si possible
        $supplementNames = array_map(function($key) {
            // Mapping simplifié des clés vers les noms
            $names = [
                'champignons' => 'Champignons', 'olives' => 'Olives', 'poivrons' => 'Poivrons',
                'oignons' => 'Oignons', 'tomates' => 'Tomates', 'pommesDeTerre' => 'Pommes de terre',
                'mais' => 'Maïs', 'grosPiment' => 'Gros piment', 'fromage' => 'Fromage',
                'chevre' => 'Chèvre', 'gorgonzola' => 'Gorgonzola', 'parmesan' => 'Parmesan',
                'jambon' => 'Jambon', 'poulet' => 'Poulet', 'merguez' => 'Merguez',
                'chorizo' => 'Chorizo', 'boeuf' => 'Bœuf', 'lardons' => 'Lardons',
                'thon' => 'Thon', 'anchois' => 'Anchois', 'crevettes' => 'Crevettes',
                'saumon' => 'Saumon', 'oeuf' => 'Œuf', 'miel' => 'Miel'
            ];
            return $names[$key] ?? $key;
        }, $item['customization']['supplements']);
        $itemsList .= "\n  Suppléments: " . implode(', ', $supplementNames);
    }
    // Ancienne structure (compatibilité)
    elseif (!empty($item['supplements']) && is_array($item['supplements']) && count($item['supplements']) > 0) {
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

// Headers pour l'email restaurant (utiliser le même expéditeur que le client)
$headers = "From: Pizza Club <commande@pizzaclub.re>\r\n";
$headers .= "Reply-To: " . ($orderData['customer']['email'] ?: 'commande@pizzaclub.re') . "\r\n";
$headers .= "Return-Path: commande@pizzaclub.re\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
$headers .= "X-Priority: 1\r\n";
$headers .= "Importance: High\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// Envoi de l'email au restaurant
$emailSent = mail($to, $subject, $message, $headers);

// Log pour debug
error_log("Email restaurant - To: $to, Subject: $subject, Sent: " . ($emailSent ? 'YES' : 'NO'));

// Si l'email principal échoue, essayer avec un email de secours
if (!$emailSent) {
    // Tenter avec un autre domaine email si disponible
    $backupEmail = 'contact@pizzaclub.re'; // ou tout autre email de secours
    $emailSent = mail($backupEmail, $subject, $message, $headers);
    error_log("Email secours - To: $backupEmail, Sent: " . ($emailSent ? 'YES' : 'NO'));
}

// Envoi de l'email de confirmation au client
$clientEmailSent = false;
if (!empty($orderData['customer']['email'])) {
    try {
        $clientSubject = 'Confirmation de commande ' . $orderData['orderNumber'] . ' - Pizza Club';
        
        // Utiliser le template HTML
        if (!file_exists(__DIR__ . '/email-template.php')) {
            error_log("ERREUR: email-template.php introuvable");
        } else {
            require_once __DIR__ . '/email-template.php';
            $clientMessage = getClientEmailTemplate($orderData);
        }
        
        $clientHeaders = "From: Pizza Club <commande@pizzaclub.re>\r\n";
        $clientHeaders .= "Reply-To: commande@pizzaclub.re\r\n";
        $clientHeaders .= "Return-Path: commande@pizzaclub.re\r\n";
        $clientHeaders .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        $clientHeaders .= "MIME-Version: 1.0\r\n";
        $clientHeaders .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        $clientEmailSent = mail($orderData['customer']['email'], $clientSubject, $clientMessage, $clientHeaders);
        error_log("Email client - To: {$orderData['customer']['email']}, Sent: " . ($clientEmailSent ? 'YES' : 'NO'));
    } catch (Exception $e) {
        error_log("ERREUR email client: " . $e->getMessage());
    }
}

// Envoi WhatsApp via API (nécessite un compte WhatsApp Business API)
$whatsappSent = false;

try {
    // Charger la configuration WhatsApp
    if (!file_exists(__DIR__ . '/whatsapp-config.php')) {
        error_log("ERREUR: whatsapp-config.php introuvable");
    } else {
        $whatsappConfig = require __DIR__ . '/whatsapp-config.php';
        $whatsappPhoneNumberId = $whatsappConfig['phone_number_id'];
        $whatsappToken = $whatsappConfig['access_token'];
        $whatsappNumber = $whatsappConfig['recipient_number'];
        $whatsappApiVersion = $whatsappConfig['api_version'];

        // Construire le message WhatsApp
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

        // Configuration API URL
        $whatsappApiUrl = "https://graph.facebook.com/{$whatsappApiVersion}/{$whatsappPhoneNumberId}/messages";

        // Tenter l'envoi uniquement si le token est configuré
        if (!empty($whatsappToken) && $whatsappToken !== 'VOTRE_ACCESS_TOKEN_ICI') {
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
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $whatsappSent = ($httpCode === 200);
            
            // Log pour debug
            error_log("WhatsApp - To: $whatsappNumber, HTTP Code: $httpCode, Sent: " . ($whatsappSent ? 'YES' : 'NO'));
            if (!$whatsappSent) {
                error_log("WhatsApp Error Response: " . $whatsappResponse);
            }
        } else {
            error_log("WhatsApp non configuré - Token manquant");
        }
    }
} catch (Exception $e) {
    error_log("ERREUR WhatsApp: " . $e->getMessage());
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

// Réponse - Succès si au moins l'email restaurant OU le WhatsApp est envoyé
$response = [
    'success' => true, // Toujours true car commande enregistrée
    'emailSent' => $emailSent,
    'clientEmailSent' => $clientEmailSent,
    'whatsappSent' => $whatsappSent,
    'orderNumber' => $orderData['orderNumber'],
    'message' => ($emailSent || $whatsappSent) ? 'Commande envoyée avec succès' : 'Commande enregistrée (email en attente)'
];

echo json_encode($response);
?>
