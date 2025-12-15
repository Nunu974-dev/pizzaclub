<?php
/**
 * RÉCUPÉRATION DES COMMANDES depuis debug-order.txt
 * Ce script reconstruit orders.json depuis l'historique debug
 * URL: https://www.pizzaclub.re/recover-orders.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Indian/Reunion');

echo "<h1>🔄 Récupération des commandes</h1>";
echo "<p>Date: " . date('d/m/Y H:i:s') . "</p>";
echo "<hr>";

$debugFile = __DIR__ . '/debug-order.txt';
$ordersFile = __DIR__ . '/orders.json';

// Vérifier que debug-order.txt existe
if (!file_exists($debugFile)) {
    die("❌ Fichier debug-order.txt introuvable");
}

echo "<h2>1️⃣ Lecture de debug-order.txt</h2>";
$content = file_get_contents($debugFile);
echo "Taille: " . number_format(strlen($content)) . " octets<br>";

// Parser les commandes
$recoveredOrders = [];
$blocks = explode('=== NOUVELLE COMMANDE', $content);
array_shift($blocks); // Enlever le premier élément vide

echo "<br><h2>2️⃣ Parsing des commandes</h2>";
echo "Nombre de blocs trouvés: <strong>" . count($blocks) . "</strong><br><br>";

foreach ($blocks as $index => $block) {
    // Extraire la date
    preg_match('/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $block, $dateMatch);
    if (!$dateMatch) continue;
    
    $timestamp = $dateMatch[1];
    
    // Extraire le Array
    $arrayStart = strpos($block, 'Array');
    if ($arrayStart === false) continue;
    
    $arrayContent = substr($block, $arrayStart);
    
    // Parser avec eval (ATTENTION: seulement sur fichiers de confiance!)
    ob_start();
    eval('$parsedData = ' . preg_replace('/^Array/m', 'array', $arrayContent) . ';');
    ob_end_clean();
    
    if (isset($parsedData) && is_array($parsedData) && isset($parsedData['orderNumber'])) {
        $parsedData['timestamp'] = $timestamp;
        $recoveredOrders[] = $parsedData;
        echo "✅ Commande " . ($index + 1) . ": " . $parsedData['orderNumber'] . " - " . $timestamp . "<br>";
    } else {
        echo "⚠️ Bloc " . ($index + 1) . " ignoré (format invalide)<br>";
    }
}

echo "<br><h2>3️⃣ Sauvegarde dans orders.json</h2>";
echo "Commandes récupérées: <strong>" . count($recoveredOrders) . "</strong><br>";

if (count($recoveredOrders) > 0) {
    // Sauvegarder
    $jsonContent = json_encode($recoveredOrders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $bytesWritten = file_put_contents($ordersFile, $jsonContent);
    
    if ($bytesWritten !== false) {
        echo "✅ <strong>Fichier orders.json créé avec succès</strong><br>";
        echo "Taille: " . number_format($bytesWritten) . " octets<br>";
        echo "<br><a href='orders-log.php' style='background: #FF0000; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>📋 Voir les commandes</a>";
    } else {
        echo "❌ <strong>Échec de l'écriture</strong><br>";
    }
} else {
    echo "❌ Aucune commande récupérée<br>";
}

echo "<hr>";
echo "<h2>📊 Détails des commandes récupérées</h2>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>N° Commande</th><th>Date</th><th>Client</th><th>Mode</th><th>Total</th></tr>";
foreach ($recoveredOrders as $order) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($order['orderNumber']) . "</td>";
    echo "<td>" . htmlspecialchars($order['timestamp']) . "</td>";
    echo "<td>" . htmlspecialchars($order['customer']['firstName'] . ' ' . $order['customer']['lastName']) . "</td>";
    echo "<td>" . ($order['customer']['deliveryMode'] === 'livraison' ? '🛵 Livraison' : '🏃 À emporter') . "</td>";
    echo "<td>" . number_format($order['total'], 2, ',', ' ') . "€</td>";
    echo "</tr>";
}
echo "</table>";
?>
