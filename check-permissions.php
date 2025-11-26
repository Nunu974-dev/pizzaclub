<?php
/**
 * Script de diagnostic des permissions
 */

$jsonFile = __DIR__ . '/unavailability.json';

echo "<h2>Diagnostic Permissions - unavailability.json</h2>";

// Vérifier si le fichier existe
if (file_exists($jsonFile)) {
    echo "✅ Fichier existe<br>";
    
    // Permissions actuelles
    $perms = fileperms($jsonFile);
    echo "📋 Permissions: " . substr(sprintf('%o', $perms), -4) . "<br>";
    
    // Test de lecture
    if (is_readable($jsonFile)) {
        echo "✅ Lecture possible<br>";
        $content = file_get_contents($jsonFile);
        echo "📄 Contenu actuel: <pre>" . htmlspecialchars($content) . "</pre>";
    } else {
        echo "❌ Lecture impossible<br>";
    }
    
    // Test d'écriture
    if (is_writable($jsonFile)) {
        echo "✅ Écriture possible<br>";
        
        // Test d'écriture réel
        $testData = json_decode(file_get_contents($jsonFile), true);
        $testData['_test'] = time();
        
        if (file_put_contents($jsonFile, json_encode($testData, JSON_PRETTY_PRINT))) {
            echo "✅ Test écriture réussi<br>";
            
            // Supprimer le test
            unset($testData['_test']);
            file_put_contents($jsonFile, json_encode($testData, JSON_PRETTY_PRINT));
        } else {
            echo "❌ Test écriture échoué<br>";
        }
    } else {
        echo "❌ Écriture impossible<br>";
        echo "<br><strong>🔧 Solution : Exécute cette commande SSH :</strong><br>";
        echo "<code>chmod 666 " . $jsonFile . "</code><br>";
    }
    
    // Propriétaire du fichier
    $owner = posix_getpwuid(fileowner($jsonFile));
    echo "<br>👤 Propriétaire: " . $owner['name'] . " (UID: " . fileowner($jsonFile) . ")<br>";
    
    // Utilisateur PHP
    $currentUser = posix_getpwuid(posix_geteuid());
    echo "🖥️ Utilisateur PHP: " . $currentUser['name'] . " (UID: " . posix_geteuid() . ")<br>";
    
} else {
    echo "❌ Fichier n'existe pas<br>";
    echo "<br><strong>🔧 Solution : Le fichier sera créé automatiquement</strong><br>";
}
?>
