<?php
// Configuration
// VERSION: 20251215-APPEND (lecture existant + ajout)
date_default_timezone_set('Indian/Reunion'); // Fuseau horaire La Réunion (UTC+4)

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

// DEBUG - Logger TOUTES les données reçues
error_log("=== DÉBUT COMMANDE " . ($orderData['orderNumber'] ?? 'UNKNOWN') . " ===");
error_log("Données JSON complètes reçues:");
error_log(print_r($orderData, true));
error_log("=== FIN DEBUG ===");

// ========================================
// SAUVEGARDE JSON DES COMMANDES
// ========================================
$ordersFile = __DIR__ . '/orders.json';
$debugFile = __DIR__ . '/debug-order.txt';

// Écrire dans debug-order.txt pour tracer le problème
file_put_contents(
    $debugFile,
    "\n=== 📊 SAUVEGARDE JSON (VERSION 20251215-APPEND) ===\n",
    FILE_APPEND
);

// Vérifier si orders.json existe et logger
if (file_exists($ordersFile)) {
    file_put_contents($debugFile, "📄 orders.json existe\n", FILE_APPEND);
} else {
    file_put_contents($debugFile, "⚠️ orders.json n'existe pas - QUELQUE CHOSE L'A SUPPRIMÉ!\n", FILE_APPEND);
}

$ordersData = [];

// Lire les commandes existantes (ou créer fichier vide si inexistant)
if (file_exists($ordersFile)) {
    $existingJson = file_get_contents($ordersFile);
    if (!empty($existingJson)) {
        file_put_contents($debugFile, "📄 Taille orders.json: " . strlen($existingJson) . " octets\n", FILE_APPEND);
        $ordersData = json_decode($existingJson, true) ?: [];
        file_put_contents($debugFile, "✅ Nombre de commandes existantes: " . count($ordersData) . "\n", FILE_APPEND);
    } else {
        file_put_contents($debugFile, "⚠️ orders.json existe mais est VIDE\n", FILE_APPEND);
    }
} else {
    file_put_contents($debugFile, "🆕 Création nouveau orders.json\n", FILE_APPEND);
}

// Ajouter la nouvelle commande avec timestamp
$orderToSave = $orderData;
$orderToSave['timestamp'] = date('Y-m-d H:i:s');
$ordersData[] = $orderToSave;
file_put_contents($debugFile, "✅ AJOUT - Nombre de commandes APRÈS ajout: " . count($ordersData) . "\n", FILE_APPEND);
file_put_contents($debugFile, "✅ Nouvelle commande: " . ($orderToSave['orderNumber'] ?? 'UNKNOWN') . "\n", FILE_APPEND);

// Limiter à 100 dernières commandes
if (count($ordersData) > 100) {
    $ordersData = array_slice($ordersData, -100);
}

// Sauvegarder orders.json AVEC LOCK_EX directement
$jsonToSave = json_encode($ordersData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
$bytesWritten = file_put_contents($ordersFile, $jsonToSave, LOCK_EX);
file_put_contents($debugFile, "💾 Octets écrits dans orders.json: " . $bytesWritten . "\n", FILE_APPEND);
file_put_contents($debugFile, "💾 Commandes dans le fichier: " . count($ordersData) . "\n", FILE_APPEND);

// Sauvegarder debug-order.txt
file_put_contents(
    $debugFile,
    "=== NOUVELLE COMMANDE " . date('Y-m-d H:i:s') . " ===\n" . 
    print_r($orderData, true) . 
    "\n=== FIN SAUVEGARDE JSON ===\n\n",
    FILE_APPEND | LOCK_EX
);
// ========================================

// ========================================
// VÉRIFICATION DES FERMETURES (côté serveur)
// ========================================
require_once __DIR__ . '/check-closure.php';

// Fonction pour vérifier si une date programmée est valide
function isScheduledDateValid($scheduledDate, $scheduledTime) {
    if (empty($scheduledDate)) {
        return ['valid' => true];
    }
    
    $date = new DateTime($scheduledDate);
    $dayOfWeek = (int)$date->format('N'); // 1 = Lundi, 7 = Dimanche
    
    // Bloquer les lundis (fermé toute la journée)
    if ($dayOfWeek === 1) {
        return [
            'valid' => false,
            'message' => '🔒 Restaurant fermé le lundi. Choisissez un autre jour.'
        ];
    }
    
    // Bloquer les dimanches midi (avant 17h)
    if ($dayOfWeek === 7 && $scheduledTime !== null) {
        $scheduledHour = (int)$scheduledTime;
        if ($scheduledHour < 17) {
            return [
                'valid' => false,
                'message' => '🔒 Restaurant fermé le dimanche midi. Choisissez le soir (à partir de 18h) ou un autre jour.'
            ];
        }
    }
    
    return ['valid' => true];
}

// Vérifier si c'est une commande "maintenant" (pas programmée)
$isOrderNow = true;
if (isset($orderData['scheduledDate']) && !empty($orderData['scheduledDate'])) {
    $isOrderNow = false; // C'est une commande programmée
    
    // Vérifier que la date programmée est valide
    $scheduledValidation = isScheduledDateValid(
        $orderData['scheduledDate'], 
        $orderData['scheduledTime'] ?? null
    );
    
    if (!$scheduledValidation['valid']) {
        http_response_code(403);
        echo json_encode([
            'success' => false, 
            'error' => $scheduledValidation['message'],
            'closureType' => 'scheduled_closed_day'
        ]);
        exit;
    }
}

// Si c'est une commande "maintenant", vérifier si le restaurant est fermé
if ($isOrderNow) {
    // Passer le mode de livraison pour calculer le bon délai (45min livraison, 30min emporter)
    $GLOBALS['_deliveryMode'] = $orderData['customer']['deliveryMode'] ?? 'livraison';
    $closureStatus = isRestaurantClosed();
    if ($closureStatus['isClosed']) {
        http_response_code(403);
        echo json_encode([
            'success' => false, 
            'error' => $closureStatus['message'],
            'closureType' => $closureStatus['type'] ?? 'unknown'
        ]);
        exit;
    }
}

// Configuration email
$to = 'commande@pizzaclub.re';
$smsEmail = '0692630364@orange.fr'; // SMS instantané via Orange (SFR ne marche pas)
$subject = '🚨 COMMANDE ' . $orderData['orderNumber'] . ' - ' . number_format($orderData['total'], 2) . '€';

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
    $custom = $item['customization'] ?? [];
    
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
    
    $itemsList .= "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $itemsList .= $productType . $item['name'] . " x" . $item['quantity'];
    $itemsList .= "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    // ===== TRAITEMENT SPÉCIAL POUR LES FORMULES =====
    if ($item['type'] === 'formule') {
        // FORMULE MIDI - Détails de la pizza choisie
        if (isset($custom['pizza']) && !empty($custom['pizza'])) {
            $itemsList .= "\n  ▶ Pizza: " . $custom['pizza'];
            
            // Taille de la pizza
            if (!empty($custom['pizzaSize'])) {
                $pizzaSizeLabel = ($custom['pizzaSize'] === 'moyenne') ? '33cm' : '40cm';
                $itemsList .= " (" . $pizzaSizeLabel . ")";
            }
            
            // Base de la pizza
            if (!empty($custom['pizzaBase'])) {
                $baseLabel = ($custom['pizzaBase'] === 'creme') ? 'Crème' : 'Tomate';
                $itemsList .= "\n    Base: " . $baseLabel;
            }
            
            // Ingrédients ajoutés à la pizza
            if (!empty($custom['pizzaAdded']) && is_array($custom['pizzaAdded']) && count($custom['pizzaAdded']) > 0) {
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
                $addedNames = array_map(function($key) use ($names) {
                    return $names[$key] ?? $key;
                }, $custom['pizzaAdded']);
                $itemsList .= "\n    ➕ AJOUTS: " . implode(', ', $addedNames);
            }
            
            // Ingrédients retirés de la pizza
            if (!empty($custom['pizzaRemoved']) && is_array($custom['pizzaRemoved']) && count($custom['pizzaRemoved']) > 0) {
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
                $removedNames = array_map(function($key) use ($names) {
                    return $names[$key] ?? $key;
                }, $custom['pizzaRemoved']);
                $itemsList .= "\n    ➖ RETRAITS: " . implode(', ', $removedNames);
            }
            
            // Boisson de la formule midi
            if (!empty($custom['boisson'])) {
                $itemsList .= "\n  ▶ Boisson: " . $custom['boisson'] . " (33cl)";
            }
        }
        // FORMULE PÂTES/SALADE - Détails du plat principal
        elseif (isset($custom['pate']) && !empty($custom['pate'])) {
            $itemsList .= "\n  ▶ Pâte: " . $custom['pate'];
            
            // Taille de la pâte
            if (!empty($custom['pateSize'])) {
                $pateSizeLabel = ($custom['pateSize'] === 'L') ? 'Large' : 'XL';
                $itemsList .= " (" . $pateSizeLabel . ")";
            }
            
            // Base de la pâte
            if (!empty($custom['pateBase'])) {
                $itemsList .= "\n    Base: " . $custom['pateBase'];
            }
            
            // Suppléments de la pâte
            if (!empty($custom['pateSupplements']) && is_array($custom['pateSupplements']) && count($custom['pateSupplements']) > 0) {
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
                $supplementNames = array_map(function($key) use ($names) {
                    return $names[$key] ?? $key;
                }, $custom['pateSupplements']);
                $itemsList .= "\n    ➕ Suppléments: " . implode(', ', $supplementNames);
            }
            
            // Boisson
            if (!empty($custom['boisson'])) {
                $itemsList .= "\n  ▶ Boisson: " . $custom['boisson'];
            }
            
            // Dessert
            if (!empty($custom['dessert'])) {
                $itemsList .= "\n  ▶ Dessert: " . $custom['dessert'];
            }
        }
        elseif (isset($custom['salade']) && !empty($custom['salade'])) {
            $itemsList .= "\n  ▶ Salade: " . $custom['salade'];
            
            // Options de la salade
            if (!empty($custom['saladeOptions']) && is_array($custom['saladeOptions']) && count($custom['saladeOptions']) > 0) {
                $optionLabels = [];
                foreach ($custom['saladeOptions'] as $opt) {
                    if ($opt === 'pain') $optionLabels[] = 'Avec pain';
                    elseif ($opt === 'vinaigrette-sup') $optionLabels[] = 'Vinaigrette supplémentaire';
                }
                if (count($optionLabels) > 0) {
                    $itemsList .= "\n    Options: " . implode(', ', $optionLabels);
                }
            }
            
            // Suppléments de la salade
            if (!empty($custom['saladeSupplements']) && is_array($custom['saladeSupplements']) && count($custom['saladeSupplements']) > 0) {
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
                $supplementNames = array_map(function($key) use ($names) {
                    return $names[$key] ?? $key;
                }, $custom['saladeSupplements']);
                $itemsList .= "\n    ➕ Suppléments: " . implode(', ', $supplementNames);
            }
            
            // Boisson
            if (!empty($custom['boisson'])) {
                $itemsList .= "\n  ▶ Boisson: " . $custom['boisson'];
            }
            
            // Dessert
            if (!empty($custom['dessert'])) {
                $itemsList .= "\n  ▶ Dessert: " . $custom['dessert'];
            }
        }
    }
    // ===== FIN TRAITEMENT FORMULES =====
    
    // ===== PIZZAS, PÂTES, SALADES INDIVIDUELLES =====
    else {
        // ── TAILLE ──
        $sizeLabel = '';
        if (!empty($custom['size'])) {
            switch($custom['size']) {
                case 'moyenne': $sizeLabel = '33cm'; break;
                case 'grande': $sizeLabel = '40cm'; break;
                case 'L': $sizeLabel = 'Large'; break;
                case 'XL': $sizeLabel = 'XL'; break;
                default: $sizeLabel = $custom['size'];
            }
        } elseif (!empty($item['size'])) {
            $sizeLabel = $item['size'];
        }
        
        // TAILLE - Toujours afficher
        $itemsList .= "📏 TAILLE: " . (!empty($sizeLabel) ? $sizeLabel : "(non spécifiée)") . "\n";
        
        // ── BASE ──
        $baseLabel = '';
        
        // BASE - Toujours afficher selon le type de produit
        if ($item['type'] === 'pizza') {
            $baseLabel = !empty($custom['base']) ? ($custom['base'] === 'creme' ? 'Crème' : 'Tomate') : '(non spécifiée)';
            $itemsList .= "🍕 BASE: " . $baseLabel . "\n";
        }
        elseif ($item['type'] === 'pate') {
            $baseLabel = !empty($custom['base']) ? $custom['base'] : '(non spécifiée)';
            $itemsList .= "🍝 BASE: " . $baseLabel . "\n";
        }
        elseif ($item['type'] === 'roll' || $item['type'] === 'bun') {
            $baseLabel = !empty($custom['base']) ? ($custom['base'] === 'creme' ? 'Crème' : 'Tomate') : '(non spécifiée)';
            $itemsList .= "🌯 BASE: " . $baseLabel . "\n";
        }
        
        // ── INGRÉDIENTS RETIRÉS - Toujours afficher ──
        $removedList = $custom['removed'] ?? $custom['removedIngredients'] ?? [];
        $itemsList .= "❌ RETIRER: ";
        if (!empty($removedList) && is_array($removedList) && count($removedList) > 0) {
            $names = [
                'champignons' => 'Champignons', 'olives' => 'Olives', 'poivrons' => 'Poivrons',
                'oignons' => 'Oignons', 'tomates' => 'Tomates', 'pommesDeTerre' => 'Pommes de terre',
                'mais' => 'Maïs', 'grosPiment' => 'Gros piment', 'fromage' => 'Fromage',
                'chevre' => 'Chèvre', 'gorgonzola' => 'Gorgonzola', 'parmesan' => 'Parmesan',
                'jambon' => 'Jambon', 'poulet' => 'Poulet', 'merguez' => 'Merguez',
                'chorizo' => 'Chorizo', 'boeuf' => 'Bœuf', 'lardons' => 'Lardons',
                'thon' => 'Thon', 'anchois' => 'Anchois', 'crevettes' => 'Crevettes',
                'saumon' => 'Saumon', 'oeuf' => 'Œuf', 'miel' => 'Miel',
                'Base crème' => 'Base crème', 'Base tomate' => 'Base tomate',
                'Olive' => 'Olives', 'Oignon' => 'Oignons'
            ];
            $removedNames = array_map(function($key) use ($names) {
                // Si c'est déjà un texte formaté, le garder tel quel
                if (strpos($key, ' ') !== false || strpos($key, 'é') !== false || strpos($key, 'è') !== false) {
                    return $key;
                }
                return $names[$key] ?? ucfirst($key);
            }, $removedList);
            $itemsList .= implode(', ', $removedNames);
        } else {
            $itemsList .= "(aucun)";
        }
        $itemsList .= "\n";
        
        // ── INGRÉDIENTS AJOUTÉS - Toujours afficher ──
        $addedList = $custom['added'] ?? $custom['addedIngredients'] ?? [];
        $itemsList .= "➕ AJOUTER: ";
        if (!empty($addedList) && is_array($addedList) && count($addedList) > 0) {
            $names = [
                'champignons' => 'Champignons', 'olives' => 'Olives', 'poivrons' => 'Poivrons',
                'oignons' => 'Oignons', 'tomates' => 'Tomates', 'pommesDeTerre' => 'Pommes de terre',
                'mais' => 'Maïs', 'grosPiment' => 'Gros piment', 'fromage' => 'Fromage',
                'chevre' => 'Chèvre', 'gorgonzola' => 'Gorgonzola', 'parmesan' => 'Parmesan',
                'jambon' => 'Jambon', 'poulet' => 'Poulet', 'merguez' => 'Merguez',
                'chorizo' => 'Chorizo', 'boeuf' => 'Bœuf', 'lardons' => 'Lardons',
                'thon' => 'Thon', 'anchois' => 'Anchois', 'crevettes' => 'Crevettes',
                'saumon' => 'Saumon', 'oeuf' => 'Œuf', 'miel' => 'Miel',
                'maxiGarniture' => '🌟 MAXI GARNITURE (+50%)',
                'reblochon' => 'Reblochon'
            ];
            $addedNames = array_map(function($key) use ($names) {
                // Si c'est déjà un texte formaté, le garder tel quel
                if (strpos($key, ' ') !== false || strpos($key, 'é') !== false || strpos($key, 'è') !== false) {
                    return $key;
                }
                return $names[$key] ?? ucfirst($key);
            }, $addedList);
            $itemsList .= implode(', ', $addedNames);
        } else {
            $itemsList .= "(aucun)";
        }
        $itemsList .= "\n";
        
        // ── SUPPLÉMENTS (pour pâtes et salades) ──
        if (!empty($custom['supplements']) && is_array($custom['supplements']) && count($custom['supplements']) > 0) {
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
            $supplementNames = array_map(function($key) use ($names) {
                return $names[$key] ?? $key;
            }, $custom['supplements']);
            $itemsList .= "➕ SUPPLÉMENTS: " . implode(', ', $supplementNames) . "\n";
        }
        // Ancienne structure (compatibilité)
        elseif (!empty($item['supplements']) && is_array($item['supplements']) && count($item['supplements']) > 0) {
            $itemsList .= "➕ SUPPLÉMENTS: " . implode(', ', $item['supplements']) . "\n";
        }
        
        // ── OPTIONS (salades) ──
        if (!empty($custom['options']) && is_array($custom['options']) && count($custom['options']) > 0) {
            $optionLabels = [];
            foreach ($custom['options'] as $opt) {
                if ($opt === 'pain') $optionLabels[] = 'Avec pain';
                elseif ($opt === 'vinaigrette-sup') $optionLabels[] = 'Vinaigrette supplémentaire';
            }
            if (count($optionLabels) > 0) {
                $itemsList .= "🔧 OPTIONS: " . implode(', ', $optionLabels) . "\n";
            }
        }
        
        // ── INGRÉDIENTS ROLLS ──
        if (!empty($custom['ingredients']) && is_array($custom['ingredients'])) {
            $itemsList .= "🌯 INGRÉDIENTS: " . implode(', ', $custom['ingredients']) . "\n";
        }
    }
    
    // Prix
    $itemsList .= "💰 " . number_format($item['totalPrice'], 2) . " €\n\n";
}

// Corps de l'email - FORMAT CLAIR ET LISIBLE
$message = "═══════════════════════════════════════════\n";
$message .= "       🍕 NOUVELLE COMMANDE 🍕\n";
$message .= "           " . $orderData['orderNumber'] . "\n";
$message .= "═══════════════════════════════════════════\n\n";

// MODE DE RETRAIT - TRÈS VISIBLE
$message .= "┌───────────────────────────────────────────┐\n";
if ($orderData['customer']['deliveryMode'] === 'livraison') {
    $message .= "│  🚗 MODE: LIVRAISON                       │\n";
} else {
    $message .= "│  🏪 MODE: À EMPORTER                      │\n";
}
$message .= "└───────────────────────────────────────────┘\n\n";

// INFORMATIONS CLIENT
$message .= "📋 CLIENT:\n";
$message .= "───────────────────────────────────────────\n";
$message .= "👤 " . $orderData['customer']['firstName'] . " " . $orderData['customer']['lastName'] . "\n";
$message .= "📞 " . $orderData['customer']['phone'] . "\n";
if (!empty($orderData['customer']['email'])) {
    $message .= "📧 " . $orderData['customer']['email'] . "\n";
}

// ADRESSE SI LIVRAISON
if ($orderData['customer']['deliveryMode'] === 'livraison') {
    $message .= "\n📍 ADRESSE DE LIVRAISON:\n";
    $message .= "───────────────────────────────────────────\n";
    $message .= $orderData['customer']['address'] . "\n";
    $message .= $orderData['customer']['postalCode'] . " " . $orderData['customer']['city'] . "\n";
}

// DÉTAIL DE LA COMMANDE
$message .= "\n🍕 DÉTAIL DE LA COMMANDE:\n";
$message .= "═══════════════════════════════════════════\n";
$message .= $itemsList;
$message .= "═══════════════════════════════════════════\n\n";

// RÉCAPITULATIF PRIX
$message .= "💰 RÉCAPITULATIF:\n";
$message .= "───────────────────────────────────────────\n";
$message .= "   Sous-total:        " . number_format($orderData['subtotal'], 2) . " €\n";
if ($orderData['customer']['deliveryMode'] === 'livraison') {
    $message .= "   Frais livraison:   " . number_format($orderData['deliveryFee'], 2) . " €\n";
}
$message .= "───────────────────────────────────────────\n";
$message .= "   💵 TOTAL:          " . number_format($orderData['total'], 2) . " €\n";
$message .= "───────────────────────────────────────────\n\n";

// TEMPS ET COMMENTAIRES
$message .= "⏱️  Temps estimé: " . $orderData['estimatedTime'] . "\n";

if (!empty($orderData['customer']['comments'])) {
    $message .= "\n💬 COMMENTAIRE CLIENT:\n";
    $message .= "───────────────────────────────────────────\n";
    $message .= $orderData['customer']['comments'] . "\n";
}

// ========================================
// ENVOI EMAIL CUISINE VIA BREVO API
// ========================================
$emailSent = false;

try {
    error_log("======= TENTATIVE ENVOI EMAIL CUISINE BREVO =======");
    
    // Charger la config Brevo
    $configPaths = [
        __DIR__ . '/config/brevo-config.php',
        __DIR__ . '/brevo-config.php'
    ];
    
    $brevoApiKey = null;
    foreach ($configPaths as $configPath) {
        if (file_exists($configPath)) {
            error_log("✓ Config trouvé: $configPath");
            $brevoConfig = require $configPath;
            $brevoApiKey = $brevoConfig['api_key'];
            break;
        }
    }
    
    // Fallback sur variable d'environnement
    if (!$brevoApiKey && getenv('BREVO_API_KEY')) {
        $brevoApiKey = getenv('BREVO_API_KEY');
        error_log("✓ Config depuis variables d'environnement");
    }
    
    if ($brevoApiKey) {
        // Utiliser le template HTML pour l'email restaurant
        require_once __DIR__ . '/email-template-kitchen.php';
        $htmlMessage = getKitchenEmailTemplate($orderData);
        
        // API Brevo pour emails transactionnels
        $brevoUrl = "https://api.brevo.com/v3/smtp/email";
        
        $brevoEmailData = [
            'sender' => [
                'name' => 'Pizza Club',
                'email' => 'commande@pizzaclub.re'
            ],
            'to' => [
                [
                    'email' => $to,
                    'name' => 'Cuisine Pizza Club'
                ]
            ],
            'subject' => $subject,
            'htmlContent' => $htmlMessage,
            'headers' => [
                'X-Priority' => '1',
                'Importance' => 'High'
            ]
        ];
        
        error_log("Envoi email cuisine via Brevo API à: $to");
        
        $ch = curl_init($brevoUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($brevoEmailData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'api-key: ' . $brevoApiKey,
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $brevoResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        $emailSent = ($httpCode === 201 || $httpCode === 200);
        
        // Log détaillé
        error_log("======= BREVO EMAIL CUISINE DEBUG =======");
        error_log("To: $to");
        error_log("HTTP Code: $httpCode");
        error_log("API Response: " . $brevoResponse);
        
        if ($curlError) {
            error_log("CURL Error: $curlError");
        }
        
        if ($emailSent) {
            error_log("✓ EMAIL CUISINE ENVOYÉ VIA BREVO!");
        } else {
            error_log("✗ EMAIL CUISINE ÉCHOUÉ - Code: $httpCode");
            $responseData = json_decode($brevoResponse, true);
            if ($responseData) {
                error_log("Détails erreur: " . json_encode($responseData, JSON_PRETTY_PRINT));
            }
        }
        error_log("======= FIN BREVO EMAIL CUISINE =======");
        
        // Log dans fichier
        $emailLog = "📧 Email restaurant (BREVO) - To: $to, Subject: $subject, Sent: " . ($emailSent ? 'YES ✅' : 'NO ❌');
        file_put_contents(__DIR__ . '/debug-order.txt', $emailLog . "\n", FILE_APPEND | LOCK_EX);
        
    } else {
        error_log("ERREUR: Pas de clé API Brevo trouvée pour l'email cuisine");
        file_put_contents(__DIR__ . '/debug-order.txt', "❌ Email cuisine: Pas de clé API Brevo\n", FILE_APPEND | LOCK_EX);
    }
} catch (Exception $e) {
    error_log("EXCEPTION Email cuisine Brevo: " . $e->getMessage());
    file_put_contents(__DIR__ . '/debug-order.txt', "❌ Email cuisine exception: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
}

// ========================================
// ENVOI EMAIL CLIENT VIA BREVO API
// ========================================
$clientEmailSent = false;
if (!empty($orderData['customer']['email'])) {
    try {
        error_log("======= TENTATIVE ENVOI EMAIL CLIENT BREVO =======");
        
        // Charger la config Brevo
        $configPaths = [
            __DIR__ . '/config/brevo-config.php',
            __DIR__ . '/brevo-config.php'
        ];
        
        $brevoApiKey = null;
        foreach ($configPaths as $configPath) {
            if (file_exists($configPath)) {
                $brevoConfig = require $configPath;
                $brevoApiKey = $brevoConfig['api_key'];
                break;
            }
        }
        
        // Fallback sur variable d'environnement
        if (!$brevoApiKey && getenv('BREVO_API_KEY')) {
            $brevoApiKey = getenv('BREVO_API_KEY');
        }
        
        if ($brevoApiKey) {
            $clientSubject = 'Confirmation de commande ' . $orderData['orderNumber'] . ' - Pizza Club';
            
            // Utiliser le template HTML client
            if (!file_exists(__DIR__ . '/email-template.php')) {
                error_log("ERREUR: email-template.php introuvable");
            } else {
                require_once __DIR__ . '/email-template.php';
                $clientHtmlMessage = getClientEmailTemplate($orderData);
                
                // API Brevo pour emails transactionnels
                $brevoUrl = "https://api.brevo.com/v3/smtp/email";
                
                $brevoEmailData = [
                    'sender' => [
                        'name' => 'Pizza Club',
                        'email' => 'commande@pizzaclub.re'
                    ],
                    'to' => [
                        [
                            'email' => $orderData['customer']['email'],
                            'name' => $orderData['customer']['firstName'] . ' ' . $orderData['customer']['lastName']
                        ]
                    ],
                    'subject' => $clientSubject,
                    'htmlContent' => $clientHtmlMessage,
                    'replyTo' => [
                        'email' => 'commande@pizzaclub.re',
                        'name' => 'Pizza Club'
                    ]
                ];
                
                error_log("Envoi email client via Brevo API à: " . $orderData['customer']['email']);
                
                $ch = curl_init($brevoUrl);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($brevoEmailData));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'api-key: ' . $brevoApiKey,
                    'Content-Type: application/json',
                    'Accept: application/json'
                ]);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                
                $brevoResponse = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);
                
                $clientEmailSent = ($httpCode === 201 || $httpCode === 200);
                
                // Log détaillé
                error_log("======= BREVO EMAIL CLIENT DEBUG =======");
                error_log("To: " . $orderData['customer']['email']);
                error_log("HTTP Code: $httpCode");
                error_log("API Response: " . $brevoResponse);
                
                if ($curlError) {
                    error_log("CURL Error: $curlError");
                }
                
                if ($clientEmailSent) {
                    error_log("✓ EMAIL CLIENT ENVOYÉ VIA BREVO!");
                } else {
                    error_log("✗ EMAIL CLIENT ÉCHOUÉ - Code: $httpCode");
                    $responseData = json_decode($brevoResponse, true);
                    if ($responseData) {
                        error_log("Détails erreur: " . json_encode($responseData, JSON_PRETTY_PRINT));
                    }
                }
                error_log("======= FIN BREVO EMAIL CLIENT =======");
                
                $clientLog = "📧 Email client (BREVO) - To: {$orderData['customer']['email']}, Sent: " . ($clientEmailSent ? 'YES ✅' : 'NO ❌');
                file_put_contents(__DIR__ . '/debug-order.txt', $clientLog . "\n", FILE_APPEND | LOCK_EX);
            }
        } else {
            error_log("ERREUR: Pas de clé API Brevo trouvée pour l'email client");
            file_put_contents(__DIR__ . '/debug-order.txt', "❌ Email client: Pas de clé API Brevo\n", FILE_APPEND | LOCK_EX);
        }
    } catch (Exception $e) {
        $errorLog = "❌ ERREUR email client: " . $e->getMessage();
        error_log($errorLog);
        file_put_contents(__DIR__ . '/debug-order.txt', $errorLog . "\n", FILE_APPEND | LOCK_EX);
    }
}

// ========================================
// ENVOI SMS VIA BREVO (SENDINBLUE)
// ========================================
$smsSent = false;

try {
    error_log("======= TENTATIVE ENVOI SMS BREVO =======");
    error_log("Script directory: " . __DIR__);
    
    // PRIORITÉ 1: Fichier config/brevo-config.php
    $configPaths = [
        __DIR__ . '/config/brevo-config.php',
        __DIR__ . '/brevo-config.php'
    ];
    
    $brevoApiKey = null;
    $brevoSender = 'PizzaClub';
    $brevoRecipient = '+262692630364';
    $configFound = false;
    
    foreach ($configPaths as $configPath) {
        if (file_exists($configPath)) {
            error_log("✓ Config trouvé: $configPath");
            $brevoConfig = require $configPath;
            $brevoApiKey = $brevoConfig['api_key'];
            $brevoSender = $brevoConfig['sender_name'];
            $brevoRecipient = $brevoConfig['recipient_number'];
            $configFound = true;
            break;
        }
    }
    
    // PRIORITÉ 2: Variables d'environnement Hostinger (fallback)
    if (!$configFound && getenv('BREVO_API_KEY')) {
        error_log("✓ Config depuis variables d'environnement");
        $brevoApiKey = getenv('BREVO_API_KEY');
        $brevoSender = getenv('BREVO_SENDER') ?: 'PizzaClub';
        $brevoRecipient = getenv('BREVO_RECIPIENT') ?: '+262692630364';
        $configFound = true;
    }
    
    if (!$configFound) {
        error_log("ERREUR: Aucune config Brevo trouvée");
        $brevoApiKey = null;
    }
    
    if ($brevoApiKey) {
        
        error_log("Config chargée:");
        error_log("  Sender: $brevoSender");
        error_log("  Recipient: $brevoRecipient");
        error_log("  API Key: " . substr($brevoApiKey, 0, 20) . "...");
        
        // Message SMS court (160 caractères max)
        $smsMessage = "COMMANDE {$orderData['orderNumber']}\n";
        $smsMessage .= "{$orderData['customer']['firstName']} {$orderData['customer']['lastName']}\n";
        $smsMessage .= "Tel: {$orderData['customer']['phone']}\n";
        $smsMessage .= ($orderData['customer']['deliveryMode'] === 'livraison' ? 'LIVRAISON' : 'A EMPORTER') . "\n";
        $smsMessage .= "TOTAL: " . number_format($orderData['total'], 2) . " EUR";
        
        error_log("Message SMS:");
        error_log($smsMessage);
        
        // API Brevo
        $brevoUrl = "https://api.brevo.com/v3/transactionalSMS/sms";
        
        $brevoData = [
            'sender' => $brevoSender,
            'recipient' => $brevoRecipient,
            'content' => $smsMessage,
            'type' => 'transactional'
        ];
        
        error_log("Données envoyées à Brevo:");
        error_log(json_encode($brevoData, JSON_PRETTY_PRINT));
        
        $ch = curl_init($brevoUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($brevoData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'api-key: ' . $brevoApiKey,
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        error_log("Envoi requête CURL vers: $brevoUrl");
        
        $brevoResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        $smsSent = ($httpCode === 201 || $httpCode === 200);
        
        // Log détaillé SMS
        error_log("======= BREVO SMS DEBUG =======");
        error_log("To: $brevoRecipient");
        error_log("HTTP Code: $httpCode");
        error_log("Message: $smsMessage");
        error_log("API Response: " . $brevoResponse);
        
        if ($curlError) {
            error_log("CURL Error: $curlError");
        }
        
        if ($smsSent) {
            error_log("✓ SMS ENVOYÉ!");
        } else {
            error_log("✗ SMS ÉCHOUÉ - Code: $httpCode");
            
            // Décoder la réponse pour avoir plus de détails
            $responseData = json_decode($brevoResponse, true);
            if ($responseData) {
                error_log("Détails erreur: " . json_encode($responseData, JSON_PRETTY_PRINT));
            }
        }
        error_log("======= FIN BREVO SMS =======");
    }
} catch (Exception $e) {
    error_log("EXCEPTION Brevo SMS: " . $e->getMessage());
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

// ========================================
// INCRÉMENTER usedCount si code promo utilisé
// ========================================
if (!empty($orderData['promoCode'])) {
    $promoPayload = json_encode(['action' => 'use', 'code' => $orderData['promoCode']]);
    $chPromo = curl_init('http://localhost/promo-manager.php');
    if (!$chPromo) {
        // Fallback : écriture directe dans le JSON
        $promoFile = __DIR__ . '/promo-codes.json';
        if (file_exists($promoFile)) {
            $promoData = json_decode(file_get_contents($promoFile), true);
            $promoCode = strtoupper($orderData['promoCode']);
            foreach ($promoData['codes'] as &$c) {
                if ($c['code'] === $promoCode) { $c['usedCount']++; break; }
            }
            file_put_contents($promoFile, json_encode($promoData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    } else {
        curl_setopt_array($chPromo, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $promoPayload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 3,
        ]);
        curl_exec($chPromo);
        curl_close($chPromo);
    }
}

// ========================================
// NOTIFICATION NTFY.SH (push téléphone)
// ========================================
try {
    // Config ntfy (inlinée pour éviter les erreurs de require_once)
    if (!defined('NTFY_TOPIC')) define('NTFY_TOPIC', 'pizzaclub-commandes-974');
    if (!defined('NTFY_SERVER')) define('NTFY_SERVER', 'https://ntfy.sh');

    $customer   = $orderData['customer'];
    $mode       = ($customer['deliveryMode'] === 'livraison') ? '🛵 LIVRAISON' : '🏃 À EMPORTER';
    $total      = number_format($orderData['total'], 2, ',', ' ');
    $orderNum   = $orderData['orderNumber'] ?? '?';
    $firstName  = $customer['firstName'] ?? '';
    $lastName   = $customer['lastName'] ?? '';
    $phone      = $customer['phone'] ?? '';

    // Résumé articles
    $items = $orderData['items'] ?? [];
    $itemLines = array_map(fn($i) => '• ' . $i['name'] . ' x' . $i['quantity'], $items);
    $itemsText = implode("\n", array_slice($itemLines, 0, 5));
    if (count($items) > 5) $itemsText .= "\n• ...";

    $ntfyMessage = "📋 {$orderNum}\n👤 {$firstName} {$lastName} - {$phone}\n{$mode}\n\n{$itemsText}\n\n💰 TOTAL : {$total}€";

    $ch = curl_init(NTFY_SERVER . '/' . NTFY_TOPIC);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $ntfyMessage,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_HTTPHEADER     => [
            'Title: 🍕 NOUVELLE COMMANDE !',
            'Tags: bell,rotating_light',
            'Priority: urgent',
            'Content-Type: text/plain; charset=utf-8',
        ],
    ]);
    $ntfyResponse = curl_exec($ch);
    $ntfyCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    error_log("📱 ntfy.sh - Code: {$ntfyCode} - Response: {$ntfyResponse}");
} catch (Exception $e) {
    error_log("ntfy.sh EXCEPTION: " . $e->getMessage());
}

// Réponse - Succès si au moins l'email restaurant OU le SMS est envoyé
$response = [
    'success' => true, // Toujours true car commande enregistrée
    'emailSent' => $emailSent,
    'clientEmailSent' => $clientEmailSent,
    'smsSent' => $smsSent,
    'orderNumber' => $orderData['orderNumber'],
    'message' => ($emailSent || $smsSent) ? 'Commande envoyée avec succès' : 'Commande enregistrée (notifications en attente)'
];

echo json_encode($response);
?>
