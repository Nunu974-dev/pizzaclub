<?php
// Inventory Manager API
header('Content-Type: application/json; charset=utf-8');

// Configuration
$inventoryFile = __DIR__ . '/inventory.json';
$archiveDir = __DIR__ . '/archives';

// Create archives directory if it doesn't exist
if (!file_exists($archiveDir)) {
    mkdir($archiveDir, 0755, true);
}

// Get action from request
$action = $_GET['action'] ?? '';

function loadInventory($file) {
    if (!file_exists($file)) {
        return ['inventory' => []];
    }
    $content = file_get_contents($file);
    return json_decode($content, true) ?: ['inventory' => []];
}

function saveInventory($file, $data) {
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Handle different actions
switch ($action) {
    case 'export-json':
        $data = loadInventory($inventoryFile);
        $filename = 'inventaire_' . date('Y-m-d_H-i-s') . '.json';
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
        
    case 'export-csv':
        $data = loadInventory($inventoryFile);
        $filename = 'inventaire_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel UTF-8 support
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Header row
        fputcsv($output, ['Article', 'Quantité', 'Unité']);
        
        // Data rows
        foreach ($data['inventory'] as $item) {
            fputcsv($output, [
                $item['name'],
                $item['quantity'],
                $item['unit']
            ]);
        }
        
        fclose($output);
        exit;
        
    case 'archive':
        $data = loadInventory($inventoryFile);
        $timestamp = date('Y-m-d_H-i-s');
        $archiveFile = $archiveDir . '/inventory_' . $timestamp . '.json';
        
        // Save archive
        if (saveInventory($archiveFile, $data)) {
            // Reset quantities to zero
            foreach ($data['inventory'] as &$item) {
                $item['quantity'] = 0;
            }
            
            // Save reset inventory
            if (saveInventory($inventoryFile, $data)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Inventaire archivé et remis à zéro',
                    'archive' => basename($archiveFile)
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Erreur lors de la remise à zéro'
                ]);
            }
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de l\'archivage'
            ]);
        }
        exit;
        
    case 'list-archives':
        $archives = [];
        if (is_dir($archiveDir)) {
            $files = scandir($archiveDir);
            foreach ($files as $file) {
                if (preg_match('/^inventory_(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})\.json$/', $file, $matches)) {
                    $archives[] = [
                        'filename' => $file,
                        'date' => $matches[1],
                        'size' => filesize($archiveDir . '/' . $file)
                    ];
                }
            }
            // Sort by date descending
            usort($archives, function($a, $b) {
                return strcmp($b['date'], $a['date']);
            });
        }
        
        echo json_encode([
            'success' => true,
            'archives' => $archives
        ]);
        exit;
        
    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Action non reconnue'
        ]);
        exit;
}
?>
