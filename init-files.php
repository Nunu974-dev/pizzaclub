<?php
/**
 * INITIALISATION DES FICHIERS DE LOG
 * Upload sur Hostinger et accède à : https://www.pizzaclub.re/init-files.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Indian/Reunion');

echo "<h1>🔧 Initialisation des fichiers de log</h1>";
echo "<p>Date: " . date('d/m/Y H:i:s') . "</p>";
echo "<hr>";

// Chemins des fichiers
$ordersFile = __DIR__ . '/orders.json';
$debugFile = __DIR__ . '/debug-order.txt';
$inventoryFile = __DIR__ . '/inventory.json';
$temperaturesFile = __DIR__ . '/temperatures.json';
$unavailabilityFile = __DIR__ . '/unavailability.json';

echo "<h2>1️⃣ Vérification du répertoire</h2>";
echo "Répertoire actuel: <code>" . __DIR__ . "</code><br>";
echo "Permissions répertoire: <code>" . substr(sprintf('%o', fileperms(__DIR__)), -4) . "</code><br><br>";

// Test écriture répertoire
if (is_writable(__DIR__)) {
    echo "✅ <strong>Le répertoire est accessible en écriture</strong><br>";
} else {
    echo "❌ <strong>Le répertoire n'est PAS accessible en écriture</strong><br>";
    echo "➡️ Contacte le support Hostinger pour corriger les permissions<br>";
}

echo "<hr>";

// Créer orders.json
echo "<h2>2️⃣ Création du fichier orders.json</h2>";
echo "Chemin: <code>$ordersFile</code><br>";

if (file_exists($ordersFile)) {
    echo "⚠️ Le fichier existe déjà<br>";
    $content = file_get_contents($ordersFile);
    $orders = json_decode($content, true);
    echo "Nombre de commandes: <strong>" . (is_array($orders) ? count($orders) : 0) . "</strong><br>";
} else {
    // Créer le fichier avec un tableau vide
    $result = file_put_contents($ordersFile, '[]');
    if ($result !== false) {
        echo "✅ <strong>Fichier créé avec succès</strong><br>";
        echo "Taille: " . filesize($ordersFile) . " octets<br>";
        echo "Permissions: <code>" . substr(sprintf('%o', fileperms($ordersFile)), -4) . "</code><br>";
    } else {
        echo "❌ <strong>Échec de la création du fichier</strong><br>";
        echo "Erreur: " . error_get_last()['message'] . "<br>";
    }
}

echo "<hr>";

// Créer debug-order.txt
echo "<h2>3️⃣ Création du fichier debug-order.txt</h2>";
echo "Chemin: <code>$debugFile</code><br>";

if (file_exists($debugFile)) {
    echo "⚠️ Le fichier existe déjà<br>";
    echo "Taille: " . number_format(filesize($debugFile)) . " octets<br>";
    echo "Dernière modification: " . date('d/m/Y H:i:s', filemtime($debugFile)) . "<br>";
} else {
    // Créer le fichier avec un message initial
    $initialContent = "=== FICHIER DEBUG INITIALISÉ ===\n";
    $initialContent .= "Date: " . date('Y-m-d H:i:s') . "\n";
    $initialContent .= "Les commandes seront enregistrées ici.\n\n";
    
    $result = file_put_contents($debugFile, $initialContent);
    if ($result !== false) {
        echo "✅ <strong>Fichier créé avec succès</strong><br>";
        echo "Taille: " . filesize($debugFile) . " octets<br>";
        echo "Permissions: <code>" . substr(sprintf('%o', fileperms($debugFile)), -4) . "</code><br>";
    } else {
        echo "❌ <strong>Échec de la création du fichier</strong><br>";
        echo "Erreur: " . error_get_last()['message'] . "<br>";
    }
}

echo "<hr>";

// Créer inventory.json
echo "<h2>4️⃣ Création du fichier inventory.json</h2>";
echo "Chemin: <code>$inventoryFile</code><br>";

if (file_exists($inventoryFile)) {
    echo "⚠️ Le fichier existe déjà<br>";
} else {
    $defaultInventory = json_encode(['inventory' => [], 'lastUpdate' => null], JSON_PRETTY_PRINT);
    $result = file_put_contents($inventoryFile, $defaultInventory);
    if ($result !== false) {
        echo "✅ <strong>Fichier créé avec succès</strong><br>";
        chmod($inventoryFile, 0666);
    } else {
        echo "❌ <strong>Échec de la création du fichier</strong><br>";
    }
}

echo "<hr>";

// Créer temperatures.json
echo "<h2>5️⃣ Création du fichier temperatures.json</h2>";
echo "Chemin: <code>$temperaturesFile</code><br>";

if (file_exists($temperaturesFile)) {
    echo "⚠️ Le fichier existe déjà<br>";
} else {
    $defaultTemperatures = json_encode(['temperatures' => []], JSON_PRETTY_PRINT);
    $result = file_put_contents($temperaturesFile, $defaultTemperatures);
    if ($result !== false) {
        echo "✅ <strong>Fichier créé avec succès</strong><br>";
        chmod($temperaturesFile, 0666);
    } else {
        echo "❌ <strong>Échec de la création du fichier</strong><br>";
    }
}

echo "<hr>";

// Créer unavailability.json
echo "<h2>6️⃣ Création du fichier unavailability.json</h2>";
echo "Chemin: <code>$unavailabilityFile</code><br>";

if (file_exists($unavailabilityFile)) {
    echo "⚠️ Le fichier existe déjà<br>";
} else {
    $defaultUnavailability = json_encode([
        'items' => new stdClass(),
        'ingredients' => new stdClass(),
        'closures' => ['emergency' => null, 'scheduled' => []]
    ], JSON_PRETTY_PRINT);
    $result = file_put_contents($unavailabilityFile, $defaultUnavailability);
    if ($result !== false) {
        echo "✅ <strong>Fichier créé avec succès</strong><br>";
        chmod($unavailabilityFile, 0666);
    } else {
        echo "❌ <strong>Échec de la création du fichier</strong><br>";
    }
}

echo "<hr>";

// Test d'écriture (SEULEMENT si fichiers vides ou inexistants)
echo "<h2>7️⃣ Test d'écriture dans les fichiers</h2>";

// Vérifier si orders.json contient déjà des commandes
$hasExistingOrders = false;
if (file_exists($ordersFile)) {
    $existingContent = file_get_contents($ordersFile);
    $existingOrders = json_decode($existingContent, true);
    if (is_array($existingOrders) && count($existingOrders) > 0) {
        $hasExistingOrders = true;
    }
}

// Test orders.json
echo "<strong>Test orders.json:</strong><br>";

if ($hasExistingOrders) {
    echo "⚠️ <strong>Le fichier contient déjà " . count($existingOrders) . " commande(s)</strong><br>";
    echo "✅ Test ignoré pour ne pas effacer l'historique<br>";
    echo "📊 Fichier protégé contre l'écrasement<br>";
} else {
    // Fichier vide ou inexistant, on peut tester
    $testData = [
        [
            'orderNumber' => 'TEST-' . date('YmdHis'),
            'timestamp' => date('Y-m-d H:i:s'),
            'customer' => [
                'firstName' => 'Test',
                'lastName' => 'Initialisation'
            ],
            'items' => [],
            'total' => 0
        ]
    ];
    $writeResult = file_put_contents($ordersFile, json_encode($testData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    if ($writeResult !== false) {
        echo "✅ Écriture réussie ($writeResult octets)<br>";
        
        // Relire pour vérifier
        $readContent = file_get_contents($ordersFile);
        $decoded = json_decode($readContent, true);
        if ($decoded && isset($decoded[0]['orderNumber'])) {
            echo "✅ Lecture et décodage JSON réussis<br>";
            
            // Nettoyer le fichier test
            file_put_contents($ordersFile, '[]');
            echo "✅ Fichier remis à zéro (prêt pour les vraies commandes)<br>";
        } else {
            echo "❌ Erreur de décodage JSON<br>";
        }
    } else {
        echo "❌ Échec de l'écriture<br>";
    }
}

echo "<br><strong>Test debug-order.txt:</strong><br>";
echo "✅ Test ignoré (fichier d'historique, on ne touche pas)<br>";

echo "<hr>";

// Lister tous les fichiers .txt et .json du répertoire
echo "<h2>8️⃣ Fichiers existants dans le répertoire</h2>";
$files = glob(__DIR__ . '/*.{json,txt}', GLOB_BRACE);
if (count($files) > 0) {
    echo "<ul>";
    foreach ($files as $file) {
        $basename = basename($file);
        $size = filesize($file);
        $modified = date('d/m/Y H:i:s', filemtime($file));
        $perms = substr(sprintf('%o', fileperms($file)), -4);
        echo "<li><strong>$basename</strong> - " . number_format($size) . " octets - Modifié: $modified - Permissions: $perms</li>";
    }
    echo "</ul>";
} else {
    echo "Aucun fichier .json ou .txt trouvé dans le répertoire.<br>";
}

echo "<hr>";
echo "<h2>📊 Conclusion</h2>";

$allFiles = [$ordersFile, $debugFile, $inventoryFile, $temperaturesFile, $unavailabilityFile];
$allExist = true;
foreach ($allFiles as $file) {
    if (!file_exists($file)) {
        $allExist = false;
        break;
    }
}

if ($allExist) {
    echo "<p style='color: green; font-size: 18px;'><strong>✅ Tous les fichiers sont prêts !</strong></p>";
    echo "<p>Tu peux maintenant accéder au <a href='admin-dashboard.php' style='font-weight: bold;'>Dashboard Admin</a></p>";
} else {
    echo "<p style='color: red; font-size: 18px;'><strong>❌ Il y a des problèmes</strong></p>";
    echo "<p>Contacte le support Hostinger pour corriger les permissions d'écriture.</p>";
}
?>
