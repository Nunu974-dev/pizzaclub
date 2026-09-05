<?php
/**
 * Interface de Commande Fournisseurs - Pizza Club
 * Génération automatique de commandes avec envoi email
 */

session_start();

// Configuration
define('ADMIN_PASSWORD', 'pizzaclub2025'); // 🔒 Même mot de passe que l'autre admin

// Gestion de la connexion
if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_POST['password'])) {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['commande_logged_in'] = true;
    } else {
        $error = "Mot de passe incorrect";
    }
}

$isLoggedIn = isset($_SESSION['commande_logged_in']) && $_SESSION['commande_logged_in'] === true;

// Données des fournisseurs et produits
$suppliers = [
    'Aphrodrink' => [
        'email' => 'contact@pizzaclub.re',
        'products' => [
            ['name' => 'Capri-Sun 20 cl', 'price' => 14.89],
            ['name' => 'Cilaos 50 cl', 'price' => 4.32],
            ['name' => 'Coca-Cola 1,5 L', 'price' => 17.20],
            ['name' => 'Coca-Cola 33 cl X24', 'price' => 24.70],
            ['name' => 'Coca-Cola 50 cl X24', 'price' => 26.40],
            ['name' => 'COT Barquette 33 cl X24', 'price' => 14.72],
            ['name' => 'COT PET 33 cl CITRON X10', 'price' => 5.50],
            ['name' => 'COT PET 33 cl AMERICAIN X10', 'price' => 5.50],
            ['name' => 'COT PET 33 cl FRUITALO X10', 'price' => 5.50],
            ['name' => 'COT PET 33 cl ANANAS X10', 'price' => 5.50],
            ['name' => 'COT PET 33 cl GRENADINE X10', 'price' => 5.50],
            ['name' => 'COT PET 1,5 L AMERICAIN X6', 'price' => 11.60],
            ['name' => 'Desperados bouteilles 33cl X24', 'price' => 44.16],
            ['name' => 'Desperados boites 50cl X24', 'price' => 52.08],
            ['name' => 'Dodo 33 cl X24', 'price' => 19.91],
            ['name' => 'Edena plate 1,5 L X8', 'price' => 5.95],
            ['name' => 'Edena plate 50 cl X12', 'price' => 5.52],
            ['name' => 'Fanta Orange 50 cl X24', 'price' => 24.96],
            ['name' => 'Fischer 33 cl X24', 'price' => 19.92],
            ['name' => 'HK 33 cl X24', 'price' => 33.18],
            ['name' => 'HK 50 cl X24', 'price' => 44.59],
            ['name' => 'Monster 50 cl', 'price' => 44.66],
            ['name' => 'Oasis Tropical 50 cl', 'price' => 35.52],
            ['name' => 'Orangina 50 cl', 'price' => 32.13],
            ['name' => 'Pokka Thé Melon 33cl', 'price' => 24.68],
            ['name' => 'Pokka Thé Pêche 33cl', 'price' => 24.68],
            ['name' => 'Pokka Thé Melon 50cl', 'price' => 30.09],
            ['name' => 'Pokka Thé Pêche 50cl', 'price' => 30.09],
            ['name' => 'Sambo 33 cl X24', 'price' => 19.91],
            ['name' => 'Volcanik 50 cl', 'price' => 5.78],
        ]
    ],
    'EDG' => [
        'email' => 'contact@pizzaclub.re',
        'products' => [
            ['name' => 'Escalope jaune VRC (kg)', 'price' => 10.56],
            ['name' => 'Merguez poulet vrac (kg)', 'price' => 9.36],
            ['name' => 'Saucisse fumée poulet (kg)', 'price' => 9.6],
        ]
    ],
    'Zembal' => [
        'email' => 'contact@pizzaclub.re',
        'products' => [
            ['name' => 'Assiettes S + couvercles (x50)', 'price' => 24],
            ['name' => 'Assiettes M 1000ml + couvercles (x50)', 'price' => 14],
            ['name' => 'Bobine blanche 450 (6 rouleaux)', 'price' => 15.9],
            ['name' => 'Boîte pizza 26 (x100)', 'price' => 19],
            ['name' => 'Boîte pizza 33 (x100)', 'price' => 22],
            ['name' => 'Boîte pizza 40 (x100)', 'price' => 35],
            ['name' => 'Farine T55 1kg', 'price' => 0.97],
            ['name' => 'Farine Tipo 00 1kg', 'price' => 1.00],
            ['name' => 'Pots sauce 25ml (x100)', 'price' => 4.50],
            ['name' => 'Sacs bretelles PM (x1000)', 'price' => 48.8],
            ['name' => 'Sauce pizza aroma GOLD 3', 'price' => 10.50],
        ]
    ],
    'Topaze' => [
        'email' => 'contact@pizzaclub.re',
        'products' => [
            ['name' => 'Boîtes pizza 40 Delicious', 'price' => 35.95],
            ['name' => 'Boîtes pizza 33 Delicious', 'price' => 23.95],
            ['name' => 'Boîtes pizza 26 Delicious', 'price' => 18.50],
            ['name' => 'Boîtes pizza 33 dakri', 'price' => 23.95],
            ['name' => 'Boîtes pizza 40 semba', 'price' => 43.95],
            ['name' => 'Farine Tipo 0 (10x1kg)', 'price' => 10.9],
            ['name' => 'Farine T55 Moulin Vert (10x1kg)', 'price' => 9.8],
            ['name' => 'Gnocchi Surgital 10kg', 'price' => 59.50],
            ['name' => 'Huile de grignon 5L', 'price' => 25.00],
            ['name' => 'Lunettes Surgital 3kg', 'price' => 43.59],
            ['name' => 'Sauce pizza AROPIZ', 'price' => 27.60],
        ]
    ],
    'Frais Import' => [
        'email' => 'contact@pizzaclub.re',
        'products' => [
            ['name' => 'Champignon Paris 3kg pays-préco', 'price' => 7.50],
            ['name' => 'Cheddar burger 88 tranches', 'price' => 11.95],
            ['name' => 'Chèvre IQF tranches 7g Ø42mm 500g surg', 'price' => 0],
            ['name' => 'Chorizo tranché 500g', 'price' => 5.55],
            ['name' => 'Chute saumon fumé 95/05 1kg', 'price' => 18.47],
            ['name' => 'Coca Cola BTE 33clx24', 'price' => 27.22],
            ['name' => 'Coulant chocolat Ø65', 'price' => 1.98],
            ['name' => 'Crème 18% Larsa 1L', 'price' => 4.10],
            ['name' => 'Crème cuisson 18% Président 1L', 'price' => 4.20],
            ['name' => 'Emmental râpé Kaasbrik 45% 1kg', 'price' => 7.55],
            ['name' => 'Essuie-mains gaufrés 450F', 'price' => 3.758],
            ['name' => 'Épaule cuite DD 5kg', 'price' => 6.95],
            ['name' => 'Farine T55 1kg', 'price' => 1.053],
            ['name' => 'Farine Tipo 00 1kg', 'price' => 1.363],
            ['name' => 'Frites 9/9 GR A4 2.5kg', 'price' => 2.39],
            ['name' => 'Fromage chèvre IQF 500g', 'price' => 7.94],
            ['name' => 'Fusilli / Spirali Pasta Z 5kg', 'price' => 12.988],
            ['name' => 'Huile Tournesol 1L', 'price' => 2.958],
            ['name' => 'Huile Tournesol PET 5L', 'price' => 13.861],
            ['name' => 'Ketchup jerrican 5L', 'price' => 11.577],
            ['name' => 'Lardons fumés 1kg', 'price' => 7.8],
            ['name' => 'Lait 1/2 écrémé 1L', 'price' => 1.16],
            ['name' => 'Levure SAF Rouge 500g', 'price' => 3.833],
            ['name' => 'Liquide vaisselle 5L', 'price' => 8.50],
            ['name' => 'Miel Mille Fleurs 1kg', 'price' => 7.817],
            ['name' => 'Mayonnaise corbeille dor 4,7kg', 'price' => 19.233],
            ['name' => 'Mix 70-30 Mozzarella emmental 2kg', 'price' => 6.90],
            ['name' => 'Mozzarella cossettes 2.5kg', 'price' => 2.5],
            ['name' => 'Mozzarella râpée 2kg', 'price' => 7.40],
            ['name' => 'Mozzarella tranches IQF 500g', 'price' => 7.26],
            ['name' => 'Oignons frits  1kg', 'price' => 4.805],
            ['name' => 'Olive noire dénoyautée 5/1', 'price' => 10.86],
            ['name' => 'Pulpe d\'ail pot 1kg', 'price' => 4.25],
            ['name' => 'Raclette tranches 15g x500g surg', 'price' => 0],
            ['name' => 'Reblochon tranché 500g', 'price' => 13.98],
            ['name' => 'Sacs poubelles 200L (rouleau)', 'price' => 4.563],
            ['name' => 'Sambo Tropical BTE 33clx24', 'price' => 22.58],
            ['name' => 'Sarcive de volaille 2kg', 'price' => 23.25],
            ['name' => 'Sauce BBQ Gyma 1L', 'price' => 4.177],
            ['name' => 'Sauce Pizza Cabanon 12/14', 'price' => 8.495],
            ['name' => 'Sauce Salade Lesieur 5L', 'price' => 12.342],
            ['name' => 'Saumon Salar 1kg', 'price' => 18.47],
            ['name' => 'Savon main 5L', 'price' => 9.50],
            ['name' => 'Sel fin sachet 1kg', 'price' => 1.116],
            ['name' => 'Sucre roux semoule 1kg', 'price' => 1.45],
            ['name' => 'Thon entier naturel 4/4', 'price' => 6.479],
            ['name' => 'Viande hachée pur boeuf 20%MG', 'price' => 14.59],
        ]
    ],
    'SIS' => [
        'email' => 'contact@pizzaclub.re',
        'products' => [
            ['name' => 'Bleu cube 14mm 1.3kg', 'price' => 26.49],
            ['name' => 'Boeuf haché égrené 1kg', 'price' => 11.99],
            ['name' => 'Boite sushi a fenetre noir 17,5x12x4,5 x50', 'price' => 2.75],
            ['name' => 'Chute saumon fumé 1kg', 'price' => 13.75],
            ['name' => 'Crevette deco 300/500 400gr', 'price' => 9.99],
            ['name' => 'Cuillere a glace en bois 9,5cm x100', 'price' => 2.15],
            ['name' => 'Demi baguette four ventile 125gx36', 'price' => 11.88],
            ['name' => 'Fourme d\'ambert cube 1300kg', 'price' => 2.75],
            ['name' => 'Gants nitrile noirs non poudré XL x100', 'price' => 6.99],
            ['name' => 'Gobelet carton boisson 180ml x50', 'price' => 2.75],
            ['name' => 'Huile tournesol COOSOL 5L', 'price' => 12.99],
            ['name' => 'Kit couvert bois fourchette/cuill x 100', 'price' => 12.59],
            ['name' => 'Lavettes jaunes 36x42', 'price' => 5.29],
            ['name' => 'Mayosnack bidon colona 5l', 'price' => 14.79],
            ['name' => 'Mozzarella tranche 1200kg', 'price' => 17.99],
            ['name' => 'Raclette tranches 1.100kg surg', 'price' => 17.99],
            ['name' => 'Sac réutilisable x100', 'price' => 6.15],
            ['name' => 'Sarcive de volaille 2kg SALAISONS', 'price' => 19.95],
            ['name' => 'Sauce Colona ALGERIENNE FLACON 850ml', 'price' => 4.99],
            ['name' => 'Sauce Colona BBQ FLACON 900ml', 'price' => 4.99],
            ['name' => 'Sauce Colona PITA FLACON 840ml', 'price' => 4.99],
            ['name' => 'Sauce Colona BRAZIL FLACON 900ml', 'price' => 5.20],
            ['name' => 'Sauce Colona SAMOURAUI 840ml', 'price' => 4.99],
            ['name' => 'Sauce Colona TUNISIENNE FLACON 900ml', 'price' => 4.99],
            ['name' => 'Sauce d\'huitre panda 907gr', 'price' => 4.89],
            ['name' => 'Sel fin 1kg salina', 'price' => 0.79],
            ['name' => 'Serviete alba 30x30 x100', 'price' => 1.25],
           ]
    ],
];

// ========================================
// ARTICLES PERSONNALISÉS (persistance)
// ========================================
// Les articles ajoutés via "Ajouter un article hors liste" sont enregistrés ici
// pour réapparaître dans le catalogue du fournisseur aux prochaines commandes.
define('CUSTOM_PRODUCTS_FILE', __DIR__ . '/fournisseurs-articles-perso.json');

function loadCustomProducts() {
    if (!file_exists(CUSTOM_PRODUCTS_FILE)) {
        return [];
    }
    $data = json_decode(file_get_contents(CUSTOM_PRODUCTS_FILE), true);
    return is_array($data) ? $data : [];
}

// Enregistre un article dans le catalogue persistant. Retourne true s'il vient
// d'être ajouté, false s'il existait déjà (pas de doublon créé).
function saveCustomProduct($supplierName, $name, $price) {
    $customProducts = loadCustomProducts();
    if (!isset($customProducts[$supplierName])) {
        $customProducts[$supplierName] = [];
    }
    foreach ($customProducts[$supplierName] as $existing) {
        if (mb_strtolower(trim($existing['name'])) === mb_strtolower(trim($name))) {
            return false; // déjà enregistré
        }
    }
    $customProducts[$supplierName][] = ['name' => $name, 'price' => $price];
    file_put_contents(CUSTOM_PRODUCTS_FILE, json_encode($customProducts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return true;
}

// Fusionner les articles personnalisés enregistrés précédemment dans le catalogue
foreach (loadCustomProducts() as $supplierName => $products) {
    if (isset($suppliers[$supplierName])) {
        foreach ($products as $p) {
            $suppliers[$supplierName]['products'][] = $p;
        }
    }
}

// Traitement de l'envoi de commande
if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_order'])) {
    $supplierName = $_POST['supplier'];
    $orders = $_POST['quantities'] ?? [];
    $customNames = $_POST['custom_name'] ?? [];
    $customPrices = $_POST['custom_price'] ?? [];
    $customQtys = $_POST['custom_qty'] ?? [];

    // Filtrer les quantités non nulles (produits de la liste fournisseur)
    $orderedItems = [];
    $total = 0;

    foreach ($orders as $productIndex => $quantity) {
        if ($quantity > 0) {
            $product = $suppliers[$supplierName]['products'][$productIndex];
            $subtotal = $product['price'] * $quantity;
            $orderedItems[] = [
                'name' => $product['name'],
                'quantity' => $quantity,
                'price' => $product['price'],
                'subtotal' => $subtotal
            ];
            $total += $subtotal;
        }
    }

    // Articles ajoutés manuellement (hors liste du fournisseur)
    // Ils sont aussi enregistrés dans le catalogue pour réapparaître la prochaine fois.
    foreach ($customNames as $i => $customName) {
        $customName = trim($customName);
        $qty = (int)($customQtys[$i] ?? 0);
        if ($customName !== '' && $qty > 0) {
            $price = (float)str_replace(',', '.', $customPrices[$i] ?? 0);
            $subtotal = $price * $qty;
            $isNewProduct = saveCustomProduct($supplierName, $customName, $price);
            $orderedItems[] = [
                'name' => $customName . ($isNewProduct ? ' (nouvel article - ajouté au catalogue)' : ''),
                'quantity' => $qty,
                'price' => $price,
                'subtotal' => $subtotal
            ];
            $total += $subtotal;
        }
    }

    if (!empty($orderedItems)) {
        // Récupérer les commentaires
        $comments = $_POST['comments'] ?? '';

        // Envoyer l'email à contact@pizzaclub.re
        $success = sendOrderEmail($supplierName, $suppliers[$supplierName]['email'], $orderedItems, $total, $comments);
        if ($success) {
            $successMessage = "✅ Commande envoyée sur contact@pizzaclub.re !";
        } else {
            $errorMessage = "❌ Erreur lors de l'envoi de la commande.";
        }
    } else {
        $errorMessage = "❌ Aucun article sélectionné ou ajouté.";
    }
}

function sendOrderEmail($supplierName, $email, $items, $total, $comments = '') {
    $date = date('d/m/Y à H:i');
    
    $subject = "Commande Pizza Club - " . date('d/m/Y');
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
            th { background-color: #667eea; color: white; }
            .total { font-size: 1.2em; font-weight: bold; text-align: right; padding: 20px; background: #f5f5f5; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 0.9em; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>🍕 Commande Pizza Club</h1>
            <p>$date</p>
        </div>
        <div class='content'>
            <h2>Bonjour $supplierName,</h2>
            <p>Veuillez trouver ci-dessous notre commande :</p>
            
            <table>
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Quantité</th>
                        <th>Prix Unitaire</th>
                        <th>Sous-total</th>
                    </tr>
                </thead>
                <tbody>";
    
    foreach ($items as $item) {
        $message .= "
                    <tr>
                        <td>{$item['name']}</td>
                        <td>{$item['quantity']}</td>
                        <td>" . number_format($item['price'], 2, ',', ' ') . " €</td>
                        <td>" . number_format($item['subtotal'], 2, ',', ' ') . " €</td>
                    </tr>";
    }
    
    $message .= "
                </tbody>
            </table>
            
            <div class='total'>
                TOTAL : " . number_format($total, 2, ',', ' ') . " €
            </div>";
    
    // Ajouter les commentaires s'ils existent
    if (!empty($comments)) {
        $message .= "
            <div style='margin: 20px 0; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107;'>
                <h3 style='margin-bottom: 10px;'>💬 Commentaires / Instructions :</h3>
                <p style='white-space: pre-wrap;'>" . htmlspecialchars($comments) . "</p>
            </div>";
    }
    
    $message .= "
            <p>Merci de confirmer la réception de cette commande.</p>
            <p>Cordialement,<br>L'équipe Pizza Club</p>
        </div>
        <div class='footer'>
            <p>Pizza Club - La Réunion<br>📞 0262 XX XX XX | 📧 contact@pizzaclub.re</p>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Pizza Club <commande@pizzaclub.re>\r\n";
    $headers .= "Reply-To: commande@pizzaclub.re\r\n";

    return mail($email, $subject, $message, $headers);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commande Fournisseurs | Pizza Club</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        <?php if (!$isLoggedIn): ?>
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .login-container h1 {
            text-align: center;
            color: #667eea;
            margin-bottom: 10px;
        }

        .login-container p {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
        }

        .error {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        <?php endif; ?>

        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .header h1 {
            color: #667eea;
            font-size: 28px;
        }

        .btn-logout {
            padding: 10px 20px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
        }

        .suppliers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
        }

        .supplier-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .supplier-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .supplier-header h2 {
            color: #667eea;
            font-size: 22px;
        }

        .supplier-header .email {
            color: #666;
            font-size: 13px;
        }

        .product-list {
            max-height: 500px;
            overflow-y: auto;
        }

        .product-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            margin-bottom: 8px;
            background: #f8f9fa;
            border-radius: 8px;
            transition: background 0.2s;
        }

        .product-item:hover {
            background: #e9ecef;
        }

        .product-info {
            flex: 1;
        }

        .product-name {
            font-weight: 500;
            color: #333;
            margin-bottom: 4px;
        }

        .product-price {
            color: #667eea;
            font-size: 14px;
            font-weight: 600;
        }

        .product-quantity {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .product-quantity input {
            width: 70px;
            padding: 8px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            text-align: center;
            font-size: 16px;
        }

        .custom-products {
            margin-top: 10px;
        }

        .custom-product-row {
            display: flex;
            gap: 8px;
            align-items: center;
            padding: 10px;
            margin-bottom: 8px;
            background: #fff8e6;
            border: 1px dashed #ffc107;
            border-radius: 8px;
        }

        .custom-product-row .custom-name-input {
            flex: 2;
            min-width: 0;
        }

        .custom-product-row .custom-price-input {
            flex: 1;
            width: 90px;
        }

        .custom-product-row .custom-qty-input {
            width: 65px;
        }

        .custom-product-row input {
            padding: 8px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
        }

        .btn-remove-custom {
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 6px;
            width: 32px;
            height: 32px;
            cursor: pointer;
            flex-shrink: 0;
        }

        .btn-remove-custom:hover {
            background: #c82333;
        }

        .btn-add-custom {
            width: 100%;
            padding: 10px;
            background: white;
            color: #667eea;
            border: 2px dashed #667eea;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
        }

        .btn-add-custom:hover {
            background: #f0f2ff;
        }

        .total-section {
            margin-top: 20px;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .total-label {
            font-size: 18px;
            font-weight: 600;
        }

        .total-amount {
            font-size: 24px;
            font-weight: 700;
        }

        .btn-send-order {
            width: 100%;
            padding: 15px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 15px;
            transition: transform 0.2s;
        }

        .btn-send-order:hover {
            transform: translateY(-2px);
            background: #218838;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 20px 30px;
            border-radius: 10px;
            color: white;
            font-weight: 500;
            z-index: 1000;
            animation: slideIn 0.3s ease;
        }

        .notification.success {
            background: #28a745;
        }

        .notification.error {
            background: #dc3545;
        }

        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .mobile-supplier-selector {
            display: none;
        }

        .mobile-supplier-selector select {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            color: #667eea;
            background: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            -webkit-appearance: none;
            appearance: none;
        }

        @media (max-width: 768px) {
            .suppliers-grid {
                grid-template-columns: 1fr;
            }

            .mobile-supplier-selector {
                display: block;
                margin-bottom: 20px;
            }

            /* Sur mobile : un seul fournisseur affiché à la fois, choisi via le menu déroulant */
            .suppliers-grid .supplier-card {
                display: none;
            }

            .suppliers-grid .supplier-card.active-mobile {
                display: block;
            }
        }
    </style>
</head>
<body>
    <?php if (!$isLoggedIn): ?>
        <div class="login-container">
            <h1>🍕 Pizza Club</h1>
            <p>Commande Fournisseurs</p>
            
            <?php if (isset($error)): ?>
                <div class="error"><?= $error ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Mot de passe</label>
                    <input type="password" name="password" required autofocus>
                </div>
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Se connecter
                </button>
            </form>
        </div>
    <?php else: ?>
        <?php if (isset($successMessage)): ?>
            <div class="notification success"><?= $successMessage ?></div>
        <?php endif; ?>
        <?php if (isset($errorMessage)): ?>
            <div class="notification error"><?= $errorMessage ?></div>
        <?php endif; ?>

        <div class="admin-container">
            <div class="header">
                <div>
                    <h1>📦 Commande Fournisseurs</h1>
                    <p style="color: #666; margin-top: 5px;">Gestion des commandes Pizza Club</p>
                </div>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="logout" class="btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Déconnexion
                    </button>
                </form>
            </div>

            <!-- Sélecteur de fournisseur (visible uniquement sur mobile) -->
            <div class="mobile-supplier-selector">
                <select id="mobileSupplierSelect" onchange="selectSupplierMobile(this.value)">
                    <?php foreach ($suppliers as $name => $supplier): ?>
                        <option value="<?= htmlspecialchars($name) ?>"><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="suppliers-grid">
                <?php foreach ($suppliers as $name => $supplier):
                    // Créer un ID valide sans espaces
                    $safeId = str_replace(' ', '-', strtolower($name));
                ?>
                    <div class="supplier-card<?= $name === array_key_first($suppliers) ? ' active-mobile' : '' ?>" data-supplier="<?= htmlspecialchars($name) ?>">
                        <form method="POST">
                            <input type="hidden" name="supplier" value="<?= $name ?>">
                            <input type="hidden" name="send_order" value="1">
                            
                            <div class="supplier-header">
                                <div>
                                    <h2><?= $name ?></h2>
                                    <div class="email"><?= $supplier['email'] ?></div>
                                </div>
                            </div>

                            <div class="product-list">
                                <?php foreach ($supplier['products'] as $index => $product): ?>
                                    <div class="product-item">
                                        <div class="product-info">
                                            <div class="product-name"><?= $product['name'] ?></div>
                                            <?php if ($product['price'] > 0): ?>
                                                <div class="product-price"><?= number_format($product['price'], 2, ',', ' ') ?> €</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="product-quantity">
                                            <input type="number" 
                                                   name="quantities[<?= $index ?>]" 
                                                   min="0" 
                                                   value="0" 
                                                   class="quantity-input"
                                                   data-price="<?= $product['price'] ?>"
                                                   onchange="updateTotal('<?= $name ?>')">
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Articles ajoutés manuellement (hors liste fournisseur) -->
                            <div class="custom-products" id="custom-products-<?= $safeId ?>"></div>

                            <button type="button" class="btn-add-custom" onclick="addCustomProductRow('<?= $safeId ?>', '<?= $name ?>')">
                                <i class="fas fa-plus"></i> Ajouter un article hors liste
                            </button>
                            <p style="font-size: 12px; color: #999; text-align: center; margin-top: 6px;">
                                L'article sera automatiquement ajouté au catalogue de ce fournisseur pour les prochaines commandes.
                            </p>

                            <div class="total-section" id="total-<?= $safeId ?>">
                                <div class="total-label">Total</div>
                                <div class="total-amount">0,00 €</div>
                            </div>

                            <!-- CHAMP COMMENTAIRES -->
                            <div style="margin: 20px 0;">
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">
                                    💬 Commentaires / Instructions
                                </label>
                                <textarea 
                                    name="comments" 
                                    rows="4" 
                                    style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-family: Arial; font-size: 14px; resize: vertical;"
                                    placeholder="Ex: Livraison avant 14h, appeler avant livraison, produit urgent, etc."></textarea>
                            </div>

                            <button type="submit" class="btn-send-order">
                                <i class="fas fa-paper-plane"></i> Envoyer sur contact@pizzaclub.re
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <script>
            // Sur mobile : n'afficher que le fournisseur choisi dans le menu déroulant
            function selectSupplierMobile(supplierName) {
                document.querySelectorAll('.supplier-card').forEach(card => {
                    card.classList.toggle('active-mobile', card.dataset.supplier === supplierName);
                });
            }

            // Ajoute une ligne d'article libre (non présent dans la liste du fournisseur)
            function addCustomProductRow(safeId, supplierName) {
                const container = document.getElementById(`custom-products-${safeId}`);
                if (!container) return;

                const row = document.createElement('div');
                row.className = 'custom-product-row';
                row.innerHTML = `
                    <input type="text" name="custom_name[]" placeholder="Nom de l'article" class="custom-name-input" required>
                    <input type="number" name="custom_price[]" placeholder="Prix €" step="0.01" min="0" class="custom-price-input" oninput="updateTotal('${supplierName}')">
                    <input type="number" name="custom_qty[]" placeholder="Qté" min="1" value="1" class="custom-qty-input" oninput="updateTotal('${supplierName}')">
                    <button type="button" class="btn-remove-custom" title="Supprimer cet article" onclick="this.closest('.custom-product-row').remove(); updateTotal('${supplierName}')">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                container.appendChild(row);
            }

            function updateTotal(supplier) {
                // Trouver la carte du fournisseur
                const card = document.querySelector(`.supplier-card[data-supplier="${supplier}"]`);
                if (!card) return;

                const inputs = card.querySelectorAll('.quantity-input');
                let total = 0;

                inputs.forEach(input => {
                    const quantity = parseInt(input.value) || 0;
                    const price = parseFloat(input.dataset.price) || 0;
                    total += quantity * price;
                });

                // Ajouter les articles hors liste
                card.querySelectorAll('.custom-product-row').forEach(row => {
                    const qty = parseInt(row.querySelector('.custom-qty-input')?.value) || 0;
                    const price = parseFloat(row.querySelector('.custom-price-input')?.value) || 0;
                    total += qty * price;
                });

                // Créer un ID sûr (même logique que PHP)
                const safeId = supplier.toLowerCase().replace(/ /g, '-');
                const totalElement = document.getElementById(`total-${safeId}`);
                if (totalElement) {
                    const amountElement = totalElement.querySelector('.total-amount');
                    if (amountElement) {
                        amountElement.textContent = total.toFixed(2).replace('.', ',') + ' €';
                    }
                }
            }

            // Auto-hide notifications
            setTimeout(() => {
                const notifications = document.querySelectorAll('.notification');
                notifications.forEach(n => n.style.display = 'none');
            }, 5000);
        </script>
    <?php endif; ?>
</body>
</html>
