<?php
// Debug - Affichage des données
header('Content-Type: text/html; charset=utf-8');

define('INVENTORY_FILE', __DIR__ . '/inventory.json');
define('TEMPERATURE_FILE', __DIR__ . '/temperatures.json');

echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <title>Debug Données</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; }
        h2 { color: #333; border-bottom: 2px solid #ffc107; padding-bottom: 10px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 Diagnostic des données</h1>
";

// INVENTAIRE
echo "<div class='section'>";
echo "<h2>📦 Inventaire</h2>";
if (file_exists(INVENTORY_FILE)) {
    $inventoryData = json_decode(file_get_contents(INVENTORY_FILE), true);
    echo "<p class='success'>✅ Fichier inventory.json trouvé</p>";
    echo "<p><strong>Nombre d'articles:</strong> " . count($inventoryData['inventory']) . "</p>";
    echo "<p><strong>Dernière mise à jour:</strong> " . ($inventoryData['lastUpdate'] ?? 'null') . "</p>";
    
    // Les 5 premiers articles
    echo "<p><strong>5 premiers articles:</strong></p><ul>";
    foreach (array_slice($inventoryData['inventory'], 0, 5) as $item) {
        echo "<li>{$item['name']}: {$item['quantity']} {$item['unit']}</li>";
    }
    echo "</ul>";
    
    // Les 5 derniers articles
    echo "<p><strong>5 derniers articles (dont les nouveaux):</strong></p><ul>";
    foreach (array_slice($inventoryData['inventory'], -5) as $item) {
        echo "<li>{$item['name']}: {$item['quantity']} {$item['unit']}</li>";
    }
    echo "</ul>";
    
    // Taille du fichier
    echo "<p><strong>Taille du fichier:</strong> " . filesize(INVENTORY_FILE) . " octets</p>";
} else {
    echo "<p class='error'>❌ Fichier inventory.json NON TROUVÉ</p>";
}
echo "</div>";

// TEMPÉRATURES
echo "<div class='section'>";
echo "<h2>🌡️ Températures</h2>";
if (file_exists(TEMPERATURE_FILE)) {
    $temperatureData = json_decode(file_get_contents(TEMPERATURE_FILE), true);
    echo "<p class='success'>✅ Fichier temperatures.json trouvé</p>";
    echo "<p><strong>Nombre de jours:</strong> " . count($temperatureData['temperatures']) . "</p>";
    
    $dates = array_keys($temperatureData['temperatures']);
    sort($dates);
    
    if (count($dates) > 0) {
        echo "<p><strong>Premier jour:</strong> " . $dates[0] . "</p>";
        echo "<p><strong>Dernier jour:</strong> " . $dates[count($dates) - 1] . "</p>";
        
        echo "<p><strong>5 derniers jours:</strong></p>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>Date</th><th>Midi Boissons</th><th>Midi Blanc</th><th>Soir Boissons</th><th>Soir Blanc</th></tr>";
        foreach (array_slice($dates, -5) as $date) {
            $d = $temperatureData['temperatures'][$date];
            echo "<tr>";
            echo "<td>{$date}</td>";
            echo "<td>{$d['midi']['frigo_boissons']}°C</td>";
            echo "<td>{$d['midi']['frigo_blanc']}°C</td>";
            echo "<td>{$d['soir']['frigo_boissons']}°C</td>";
            echo "<td>{$d['soir']['frigo_blanc']}°C</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<p><strong>Taille du fichier:</strong> " . filesize(TEMPERATURE_FILE) . " octets</p>";
} else {
    echo "<p class='error'>❌ Fichier temperatures.json NON TROUVÉ</p>";
}
echo "</div>";

// JSON BRUT
echo "<div class='section'>";
echo "<h2>📄 JSON Brut (premiers caractères)</h2>";
echo "<p><strong>inventory.json (500 premiers caractères):</strong></p>";
echo "<pre>" . htmlspecialchars(substr(file_get_contents(INVENTORY_FILE), 0, 500)) . "...</pre>";
echo "<p><strong>temperatures.json (500 premiers caractères):</strong></p>";
echo "<pre>" . htmlspecialchars(substr(file_get_contents(TEMPERATURE_FILE), 0, 500)) . "...</pre>";
echo "</div>";

echo "</body></html>";
?>
