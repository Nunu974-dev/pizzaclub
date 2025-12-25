<?php
/**
 * Test de sauvegarde unavailability.json
 * Vérifie les permissions et teste l'écriture
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Test Sauvegarde Indisponibilités</h1>";
echo "<hr>";

$file = __DIR__ . '/unavailability.json';

echo "<h2>1️⃣ Vérification du fichier</h2>";
echo "Chemin: <code>$file</code><br>";

if (file_exists($file)) {
    echo "✅ Le fichier existe<br>";
    echo "Taille: " . filesize($file) . " octets<br>";
    echo "Permissions: <code>" . substr(sprintf('%o', fileperms($file)), -4) . "</code><br>";
    echo "Modifié le: " . date('d/m/Y H:i:s', filemtime($file)) . "<br><br>";
    
    $content = file_get_contents($file);
    $data = json_decode($content, true);
    
    if ($data) {
        echo "✅ JSON valide<br>";
        echo "Date dernière mise à jour: <strong>" . ($data['lastUpdate'] ?? 'Non définie') . "</strong><br>";
        echo "Nombre d'items indisponibles: <strong>" . count($data['items'] ?? []) . "</strong><br>";
        echo "Nombre d'ingrédients indisponibles: <strong>" . count($data['ingredients'] ?? []) . "</strong><br><br>";
        
        echo "<details><summary>📋 Voir le contenu actuel</summary><pre>";
        print_r($data);
        echo "</pre></details>";
    } else {
        echo "❌ Erreur de décodage JSON<br>";
    }
} else {
    echo "❌ Le fichier n'existe pas<br>";
}

echo "<hr>";

echo "<h2>2️⃣ Test d'écriture</h2>";

if (is_writable($file)) {
    echo "✅ Le fichier est accessible en écriture<br><br>";
    
    // Test de sauvegarde
    $testData = [
        'items' => [],
        'ingredients' => [],
        'closures' => [
            'emergency' => null,
            'scheduled' => []
        ],
        'lastUpdate' => date('c'),
        'test' => 'Test effectué le ' . date('d/m/Y H:i:s')
    ];
    
    $result = file_put_contents($file, json_encode($testData, JSON_PRETTY_PRINT));
    
    if ($result !== false) {
        echo "✅ <strong>Test d'écriture réussi !</strong><br>";
        echo "Octets écrits: $result<br><br>";
        
        // Vérifier la lecture
        $verify = json_decode(file_get_contents($file), true);
        if ($verify && isset($verify['test'])) {
            echo "✅ <strong>Lecture et vérification OK</strong><br>";
            echo "Message test: " . $verify['test'] . "<br><br>";
            
            echo "<strong>⚠️ Le fichier a été réinitialisé pour le test</strong><br>";
            echo "Si tu avais des données, elles ont été effacées.<br>";
            echo "Tu peux maintenant utiliser l'interface admin-indispos-manager.php pour reconfigurer.<br>";
        } else {
            echo "❌ Erreur lors de la vérification<br>";
        }
    } else {
        echo "❌ <strong>Échec de l'écriture</strong><br>";
        echo "Erreur: Le serveur n'a pas pu écrire dans le fichier<br>";
    }
} else {
    echo "❌ <strong>Le fichier n'est PAS accessible en écriture</strong><br>";
    echo "Action requise: Modifie les permissions du fichier (chmod 666 ou 777)<br>";
}

echo "<hr>";

echo "<h2>3️⃣ Recommandations</h2>";
echo "<ul>";
echo "<li>Vérifie que le fichier a les bonnes permissions (666 ou 777)</li>";
echo "<li>Utilise <a href='admin-indispos-manager.php'>admin-indispos-manager.php</a> directement (pas via iframe) pour tester</li>";
echo "<li>Ouvre la console JavaScript (F12) pour voir les erreurs éventuelles</li>";
echo "<li>Après avoir sauvegardé, recharge cette page pour voir si la date lastUpdate change</li>";
echo "</ul>";
?>
