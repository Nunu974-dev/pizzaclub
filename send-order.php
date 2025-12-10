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

// DEBUG - Logger TOUTES les données reçues dans un fichier
error_log("=== DÉBUT COMMANDE ===");
error_log("Données JSON complètes reçues:");
error_log(print_r($orderData, true));
error_log("=== FIN DEBUG ===");

// DEBUG FICHIER - Sauvegarder aussi dans un fichier temporaire
file_put_contents(
    __DIR__ . '/debug-order.txt',
    "=== NOUVELLE COMMANDE " . date('Y-m-d H:i:s') . " ===\n" . 
    print_r($orderData, true) . 
    "\n\n",
    FILE_APPEND
);

if (!$orderData) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Données invalides']);
    exit;
}

// ========================================
// VÉRIFICATION DES FERMETURES (côté serveur)
// ========================================
require_once __DIR__ . '/check-closure.php';

// Vérifier si c'est une commande "maintenant" (pas programmée)
$isOrderNow = true;
if (isset($orderData['scheduledDate']) && !empty($orderData['scheduledDate'])) {
    $isOrderNow = false; // C'est une commande programmée
}

// Si c'est une commande "maintenant", vérifier si le restaurant est fermé
if ($isOrderNow) {
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
    
    $itemsList .= $productType . $item['name'];
    
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
    
    // Ajouter la taille si présente (pour pizzas et pâtes normales, pas formules)
    elseif (!empty($custom['size'])) {
        $sizeLabel = '';
        switch($custom['size']) {
            case 'moyenne': $sizeLabel = '33cm'; break;
            case 'grande': $sizeLabel = '40cm'; break;
            case 'L': $sizeLabel = 'Large'; break;
            case 'XL': $sizeLabel = 'XL'; break;
            default: $sizeLabel = $custom['size'];
        }
        $itemsList .= " - Taille: " . $sizeLabel;
    } elseif (!empty($item['size'])) {
        $itemsList .= " - Taille: " . $item['size'];
    }
    
    // Ajouter la base pour PIZZAS individuelles (non-formules)
    if ($item['type'] === 'pizza' && !empty($custom['base']) && $item['type'] !== 'formule') {
        $baseLabel = $custom['base'] === 'creme' ? 'Crème' : 'Tomate';
        $itemsList .= "\n  Base: " . $baseLabel;
    }
    
    // Ajouter la base pour PÂTES individuelles (non-formules)
    if ($item['type'] === 'pate' && !empty($custom['base']) && $item['type'] !== 'formule') {
        $itemsList .= "\n  Base: " . $custom['base'];
    }
    
    // Ajouter les ingrédients des rolls (obligatoire : 2 ingrédients)
    if (!empty($custom['ingredients']) && is_array($custom['ingredients'])) {
        $itemsList .= "\n  Ingrédients: " . implode(', ', $custom['ingredients']);
    }
    
    // Ajouter la base pour rolls et buns (crème/tomate)
    if (($item['type'] === 'roll' || $item['type'] === 'bun') && !empty($custom['base'])) {
        $baseLabel = $custom['base'] === 'creme' ? 'Crème' : 'Tomate';
        $itemsList .= "\n  Base: " . $baseLabel;
    }
    
    // Ajouter les ingrédients ajoutés (pizzas, buns, rolls)
    // Support des deux formats: 'added' et 'addedIngredients'
    $addedList = $custom['added'] ?? $custom['addedIngredients'] ?? [];
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
            'maxiGarniture' => 'MAXI GARNITURE (+50%)'
        ];
        $addedNames = array_map(function($key) use ($names) {
            // Si c'est déjà un texte formaté (ex: "Pomme de terre"), le garder tel quel
            if (strpos($key, ' ') !== false || strpos($key, 'é') !== false || strpos($key, 'è') !== false) {
                return $key;
            }
            // Sinon chercher dans le tableau de correspondance
            return $names[$key] ?? ucfirst($key);
        }, $addedList);
        $itemsList .= "\n  ➕ AJOUTS: " . implode(', ', $addedNames);
    }
    
    // Ajouter les ingrédients retirés (pizzas, buns, rolls)
    // Support des deux formats: 'removed' et 'removedIngredients'
    $removedList = $custom['removed'] ?? $custom['removedIngredients'] ?? [];
    if (!empty($removedList) && is_array($removedList) && count($removedList) > 0) {
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
            // Si c'est déjà un texte formaté (ex: "Pomme de terre"), le garder tel quel
            if (strpos($key, ' ') !== false || strpos($key, 'é') !== false || strpos($key, 'è') !== false) {
                return $key;
            }
            // Sinon chercher dans le tableau de correspondance
            return $names[$key] ?? ucfirst($key);
        }, $removedList);
        $itemsList .= "\n  ➖ RETRAITS: " . implode(', ', $removedNames);
    }
    
    // Ajouter les suppléments si présents (pâtes, salades normales)
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
        $itemsList .= "\n  ➕ Suppléments: " . implode(', ', $supplementNames);
    }
    // Ancienne structure (compatibilité)
    elseif (!empty($item['supplements']) && is_array($item['supplements']) && count($item['supplements']) > 0) {
        $itemsList .= "\n  ➕ Suppléments: " . implode(', ', $item['supplements']);
    }
    
    // Ajouter les options si présentes (salades individuelles)
    if (!empty($custom['options']) && is_array($custom['options']) && count($custom['options']) > 0) {
        $optionLabels = [];
        foreach ($custom['options'] as $opt) {
            if ($opt === 'pain') $optionLabels[] = 'Avec pain';
            elseif ($opt === 'vinaigrette-sup') $optionLabels[] = 'Vinaigrette supplémentaire';
        }
        if (count($optionLabels) > 0) {
            $itemsList .= "\n  Options: " . implode(', ', $optionLabels);
        }
    }
    // Ancienne structure (compatibilité)
    elseif (!empty($item['options'])) {
        $itemsList .= "\n  Options: " . $item['options'];
    }
    
    // Ajouter la quantité et le prix
    $itemsList .= "\n  Quantité: x" . $item['quantity'];
    $itemsList .= " - Prix unitaire: " . number_format($item['price'], 2) . "€";
    $itemsList .= " - Total: " . number_format($item['totalPrice'], 2) . "€\n\n";
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

$message .= "\n═══════════════════════════════════════════\n";

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

// ========================================
// ENVOI SMS VIA BREVO (SENDINBLUE)
// ========================================
$smsSent = false;

try {
    if (file_exists(__DIR__ . '/brevo-config.php')) {
        $brevoConfig = require __DIR__ . '/brevo-config.php';
        $brevoApiKey = $brevoConfig['api_key'];
        $brevoSender = $brevoConfig['sender_name'];
        $brevoRecipient = $brevoConfig['recipient_number'];
        
        // Message SMS court (160 caractères max)
        $smsMessage = "COMMANDE {$orderData['orderNumber']}\n";
        $smsMessage .= "{$orderData['customer']['firstName']} {$orderData['customer']['lastName']}\n";
        $smsMessage .= "Tel: {$orderData['customer']['phone']}\n";
        $smsMessage .= ($orderData['customer']['deliveryMode'] === 'livraison' ? 'LIVRAISON' : 'A EMPORTER') . "\n";
        $smsMessage .= "TOTAL: " . number_format($orderData['total'], 2) . " EUR";
        
        // API Brevo
        $brevoUrl = "https://api.brevo.com/v3/transactionalSMS/sms";
        
        $brevoData = [
            'sender' => $brevoSender,
            'recipient' => $brevoRecipient,
            'content' => $smsMessage,
            'type' => 'transactional'
        ];
        
        $ch = curl_init($brevoUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($brevoData));
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
        }
        error_log("======= FIN BREVO SMS =======")
    } else {
        error_log("Brevo non configuré - fichier brevo-config.php introuvable");
    }
} catch (Exception $e) {
    error_log("ERREUR Brevo SMS: " . $e->getMessage());
}

// ========================================
// ENVOI SMS TWILIO (DÉSACTIVÉ - BLOQUÉ POUR LA RÉUNION)
// ========================================
$twilioSmsSent = false;

try {
    // Twilio désactivé - bloqué pour La Réunion en compte d'essai
    error_log("Twilio SMS désactivé - utilisation SMS via email à la place");
} catch (Exception $e) {
    error_log("ERREUR Twilio SMS: " . $e->getMessage());
}

// ========================================
// ENVOI WHATSAPP (DÉSACTIVÉ - TOKEN EXPIRÉ)
// ========================================
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
            $custom = $item['customization'] ?? [];
            $whatsappMessage .= "• {$item['name']}";
            
            // Détails formule midi
            if ($item['type'] === 'formule' && isset($custom['pizza'])) {
                $whatsappMessage .= "\n  🍕 " . $custom['pizza'];
                if (!empty($custom['pizzaSize'])) {
                    $size = ($custom['pizzaSize'] === 'moyenne') ? '33cm' : '40cm';
                    $whatsappMessage .= " ({$size})";
                }
                if (!empty($custom['pizzaBase'])) {
                    $base = ($custom['pizzaBase'] === 'creme') ? 'Crème' : 'Tomate';
                    $whatsappMessage .= " - Base {$base}";
                }
                if (!empty($custom['pizzaAdded']) && count($custom['pizzaAdded']) > 0) {
                    $whatsappMessage .= "\n    ➕ " . implode(', ', $custom['pizzaAdded']);
                }
                if (!empty($custom['pizzaRemoved']) && count($custom['pizzaRemoved']) > 0) {
                    $whatsappMessage .= "\n    ➖ " . implode(', ', $custom['pizzaRemoved']);
                }
                if (!empty($custom['boisson'])) {
                    $whatsappMessage .= "\n  🥤 " . $custom['boisson'] . " 33cl";
                }
            }
            // Détails formule pâtes
            elseif ($item['type'] === 'formule' && isset($custom['pate'])) {
                $whatsappMessage .= "\n  🍝 " . $custom['pate'];
                if (!empty($custom['pateSize'])) {
                    $size = ($custom['pateSize'] === 'L') ? 'Large' : 'XL';
                    $whatsappMessage .= " ({$size})";
                }
                if (!empty($custom['pateSupplements']) && count($custom['pateSupplements']) > 0) {
                    $whatsappMessage .= "\n    ➕ " . implode(', ', $custom['pateSupplements']);
                }
                if (!empty($custom['boisson'])) {
                    $whatsappMessage .= "\n  🥤 " . $custom['boisson'];
                }
                if (!empty($custom['dessert'])) {
                    $whatsappMessage .= "\n  🍰 " . $custom['dessert'];
                }
            }
            // Détails formule salade
            elseif ($item['type'] === 'formule' && isset($custom['salade'])) {
                $whatsappMessage .= "\n  🥗 " . $custom['salade'];
                if (!empty($custom['saladeSupplements']) && count($custom['saladeSupplements']) > 0) {
                    $whatsappMessage .= "\n    ➕ " . implode(', ', $custom['saladeSupplements']);
                }
                if (!empty($custom['boisson'])) {
                    $whatsappMessage .= "\n  🥤 " . $custom['boisson'];
                }
                if (!empty($custom['dessert'])) {
                    $whatsappMessage .= "\n  🍰 " . $custom['dessert'];
                }
            }
            // Taille pour produits normaux
            elseif (!empty($custom['size'])) {
                $sizeLabels = ['moyenne' => '33cm', 'grande' => '40cm', 'L' => 'Large', 'XL' => 'XL'];
                $size = $sizeLabels[$custom['size']] ?? $custom['size'];
                $whatsappMessage .= " ({$size})";
            }
            
            $whatsappMessage .= " x{$item['quantity']} - " . number_format($item['totalPrice'], 2) . "€\n";
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
            $curlError = curl_error($ch);
            curl_close($ch);
            
            $whatsappSent = ($httpCode === 200);
            
            // Log détaillé pour debug
            error_log("WhatsApp API Call:");
            error_log("  URL: $whatsappApiUrl");
            error_log("  To: $whatsappNumber");
            error_log("  HTTP Code: $httpCode");
            error_log("  Sent: " . ($whatsappSent ? 'YES' : 'NO'));
            
            if ($curlError) {
                error_log("  CURL Error: $curlError");
            }
            
            if (!$whatsappSent) {
                error_log("  API Response: " . $whatsappResponse);
                // Décoder la réponse pour voir l'erreur
                $responseData = json_decode($whatsappResponse, true);
                if (isset($responseData['error'])) {
                    error_log("  Error Type: " . ($responseData['error']['type'] ?? 'unknown'));
                    error_log("  Error Message: " . ($responseData['error']['message'] ?? 'unknown'));
                    error_log("  Error Code: " . ($responseData['error']['code'] ?? 'unknown'));
                }
            } else {
                error_log("  ✓ WhatsApp message sent successfully!");
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

// Réponse - Succès si au moins l'email restaurant OU le SMS est envoyé
$response = [
    'success' => true, // Toujours true car commande enregistrée
    'emailSent' => $emailSent,
    'clientEmailSent' => $clientEmailSent,
    'smsSent' => $smsSent,
    'whatsappSent' => $whatsappSent,
    'orderNumber' => $orderData['orderNumber'],
    'message' => ($emailSent || $smsSent) ? 'Commande envoyée avec succès' : 'Commande enregistrée (notifications en attente)'
];

echo json_encode($response);
?>
