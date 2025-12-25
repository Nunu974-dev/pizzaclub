<?php
/**
 * TABLEAU DE BORD ADMIN - Pizza Club
 * Interface unifiée pour gérer:
 * - Commandes fournisseurs
 * - Commandes clients
 * - Indisponibilités produits & fermetures
 */

session_start();
date_default_timezone_set('Indian/Reunion');

// ========================================
// CONFIGURATION
// ========================================
define('ADMIN_PASSWORD', 'pizzaclub2025');
define('JSON_FILE', __DIR__ . '/unavailability.json');
define('LOGIN', 'pizzaclub');

// ========================================
// GESTION DE LA CONNEXION
// ========================================
if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_POST['password'])) {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        $error = "Mot de passe incorrect";
    }
}

$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// ========================================
// GESTION DES ACTIONS AJAX
// ========================================
if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    // Sauvegarde des indisponibilités
    if (strpos($contentType, 'application/json') !== false) {
        header('Content-Type: application/json');
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if ($data && isset($data['items']) && isset($data['ingredients'])) {
            $data['lastUpdate'] = date('c');
            
            if (!isset($data['closures'])) {
                $data['closures'] = [
                    'emergency' => null,
                    'scheduled' => []
                ];
            }
            
            if (file_put_contents(JSON_FILE, json_encode($data, JSON_PRETTY_PRINT))) {
                echo json_encode(['success' => true, 'message' => 'Sauvegarde réussie']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Erreur d\'écriture du fichier']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Données invalides']);
        }
        exit;
    }
}

// ========================================
// CHARGEMENT DES DONNÉES
// ========================================
$unavailabilityData = ['items' => [], 'ingredients' => []];
if (file_exists(JSON_FILE)) {
    $unavailabilityData = json_decode(file_get_contents(JSON_FILE), true);
}

// Données des fournisseurs
$suppliers = [
    'Aphrodrink' => [
        'email' => 'aphrodrink@gmail.com',
        'products' => [
            ['name' => 'Capri-Sun 20 cl', 'price' => 14.89],
            ['name' => 'Cilaos 50 cl', 'price' => 4.32],
            ['name' => 'Coca-Cola 1,5 L', 'price' => 17.20],
            ['name' => 'Coca-Cola 33 cl X24', 'price' => 24.70],
            ['name' => 'Coca-Cola 50 cl X24', 'price' => 26.40],
            ['name' => 'Desperados boites 33cl X24', 'price' => 44.16],
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
        'email' => 'Zembal974@gmail.com',
        'products' => [
            ['name' => 'Assiettes S + couvercles (x50)', 'price' => 24],
            ['name' => 'Assiettes M 1000ml + couvercles (x50)', 'price' => 14],
            ['name' => 'Bobine blanche 450 (6 rouleaux)', 'price' => 15.9],
            ['name' => 'Boîte pizza 26 (x100)', 'price' => 19],
            ['name' => 'Boîte pizza 33 (x100)', 'price' => 22],
            ['name' => 'Boîte pizza 40 (x100)', 'price' => 35],
            ['name' => 'Boîte pizza DWK 26H4 (x100)', 'price' => 19],
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
        'email' => 'commandes@frais-import.com',
        'products' => [
            ['name' => 'Pâte à pizza 260g', 'price' => 1.20],
            ['name' => 'Mozzarella râpée 1kg', 'price' => 8.50],
            ['name' => 'Champignons tranchés 800g', 'price' => 3.90],
            ['name' => 'Jambon blanc dés 1kg', 'price' => 12.00],
            ['name' => 'Ananas morceaux 850g', 'price' => 2.80],
        ]
    ],
];

// Fonction d'envoi d'email pour les commandes fournisseurs
function sendOrderEmail($supplierName, $email, $items, $total) {
    $date = date('d/m/Y à H:i');
    
    $subject = "🛒 Commande Pizza Club - $date";
    
    $message = "<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; }
        .header { background: #FF0000; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background: #f0f0f0; padding: 10px; text-align: left; border-bottom: 2px solid #ddd; }
        td { padding: 10px; border-bottom: 1px solid #ddd; }
        .total { font-size: 20px; font-weight: bold; color: #FF0000; text-align: right; margin-top: 20px; }
        .footer { background: #f9f9f9; padding: 15px; text-align: center; margin-top: 30px; color: #666; }
    </style>
</head>
<body>
    <div class='header'>
        <h1>🍕 PIZZA CLUB</h1>
        <p>Nouvelle commande du $date</p>
    </div>
    <div class='content'>
        <h2>Bonjour $supplierName,</h2>
        <p>Voici notre commande :</p>
        <table>
            <thead>
                <tr>
                    <th>Produit</th>
                    <th>Quantité</th>
                    <th>Prix unitaire</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>";
    
    foreach ($items as $item) {
        $itemTotal = $item['quantity'] * $item['price'];
        $message .= "<tr>
            <td>{$item['name']}</td>
            <td>{$item['quantity']}</td>
            <td>" . number_format($item['price'], 2) . " €</td>
            <td><strong>" . number_format($itemTotal, 2) . " €</strong></td>
        </tr>";
    }
    
    $message .= "</tbody>
        </table>
        <div class='total'>TOTAL: " . number_format($total, 2) . " €</div>
    </div>
    <div class='footer'>
        <p><strong>Pizza Club</strong><br>
        📧 contact@pizzaclub.re<br>
        📱 0692 XX XX XX</p>
    </div>
</body>
</html>";
    
    $headers = "From: Pizza Club <contact@pizzaclub.re>\r\n";
    $headers .= "Reply-To: contact@pizzaclub.re\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($email, $subject, $message, $headers);
}

// Traitement de l'envoi de commande fournisseur
if ($isLoggedIn && isset($_POST['send_order'])) {
    $supplierName = $_POST['supplier'] ?? '';
    
    if (isset($suppliers[$supplierName])) {
        $items = [];
        $total = 0;
        
        foreach ($suppliers[$supplierName]['products'] as $index => $product) {
            $quantity = intval($_POST["quantity_$index"] ?? 0);
            if ($quantity > 0) {
                $items[] = [
                    'name' => $product['name'],
                    'quantity' => $quantity,
                    'price' => $product['price']
                ];
                $total += $quantity * $product['price'];
            }
        }
        
        if (!empty($items)) {
            if (sendOrderEmail($supplierName, $suppliers[$supplierName]['email'], $items, $total)) {
                $successMessage = "✅ Commande envoyée à $supplierName (" . count($items) . " produits, " . number_format($total, 2) . " €)";
            } else {
                $errorMessage = "❌ Erreur lors de l'envoi de la commande";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎛️ Admin Dashboard - Pizza Club</title>
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
        }

        <?php if (!$isLoggedIn): ?>
        .login-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        .login-box {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: #667eea;
            font-size: 32px;
            margin-bottom: 10px;
        }

        .login-header p {
            color: #666;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .login-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .login-btn:hover {
            transform: translateY(-2px);
        }

        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        <?php else: ?>
        
        /* Dashboard Styles */
        .dashboard-header {
            background: white;
            padding: 20px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .dashboard-header h1 {
            color: #667eea;
            font-size: 28px;
        }

        .logout-btn {
            padding: 10px 25px;
            background: #FF0000;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }

        .logout-btn:hover {
            background: #CC0000;
        }

        .tabs-container {
            margin: 30px;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .tab {
            padding: 15px 30px;
            background: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            color: #666;
            transition: all 0.3s;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .tab-content {
            display: none;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 30px rgba(0,0,0,0.1);
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.5s;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Styles pour les commandes fournisseurs */
        .supplier-card {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            border-left: 5px solid #667eea;
        }

        .supplier-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .supplier-name {
            font-size: 24px;
            font-weight: 700;
            color: #333;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .product-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .product-info {
            flex: 1;
        }

        .product-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .product-price {
            color: #667eea;
            font-weight: 600;
        }

        .quantity-input {
            width: 80px;
            padding: 8px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            text-align: center;
            font-size: 16px;
        }

        .total-section {
            background: #667eea;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: right;
        }

        .total-amount {
            font-size: 28px;
            font-weight: 700;
        }

        .send-order-btn {
            width: 100%;
            padding: 15px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }

        .send-order-btn:hover {
            background: #218838;
        }

        /* Styles pour les commandes clients */
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .orders-table th,
        .orders-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .orders-table th {
            background: #667eea;
            color: white;
            font-weight: 600;
        }

        .orders-table tr:hover {
            background: #f8f9fa;
        }

        .notification {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            animation: slideIn 0.5s;
        }

        .notification.success {
            background: #d4edda;
            color: #155724;
            border-left: 5px solid #28a745;
        }

        .notification.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 5px solid #dc3545;
        }

        @keyframes slideIn {
            from { transform: translateX(-100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* Styles pour indisponibilités */
        .items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .item-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .item-card.unavailable {
            background: #ffebee;
            border-color: #FF0000;
        }

        .item-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .item-name {
            font-weight: 600;
            margin-top: 10px;
            color: #333;
        }

        .save-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            padding: 15px 30px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 5px 20px rgba(40, 167, 69, 0.4);
            transition: all 0.3s;
            z-index: 1000;
        }

        .save-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(40, 167, 69, 0.6);
        }

        @media (max-width: 768px) {
            .tabs {
                flex-direction: column;
            }
            
            .tab {
                width: 100%;
            }

            .products-grid {
                grid-template-columns: 1fr;
            }
        }
        <?php endif; ?>
    </style>
</head>
<body>
    <?php if (!$isLoggedIn): ?>
        <!-- PAGE DE CONNEXION -->
        <div class="login-container">
            <div class="login-box">
                <div class="login-header">
                    <h1>🎛️ Admin Dashboard</h1>
                    <p>Pizza Club - Interface d'administration</p>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Mot de passe</label>
                        <input type="password" name="password" required autofocus>
                    </div>
                    <button type="submit" class="login-btn">
                        <i class="fas fa-sign-in-alt"></i> Se connecter
                    </button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <!-- DASHBOARD ADMIN -->
        <div class="dashboard-header">
            <h1><i class="fas fa-chart-line"></i> Dashboard Admin</h1>
            <form method="POST" style="margin: 0;">
                <button type="submit" name="logout" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </button>
            </form>
        </div>

        <div class="tabs-container">
            <!-- ONGLETS -->
            <div class="tabs">
                <button class="tab active" onclick="switchTab('suppliers')">
                    <i class="fas fa-truck"></i> Commandes Fournisseurs
                </button>
                <button class="tab" onclick="switchTab('orders')">
                    <i class="fas fa-shopping-cart"></i> Commandes Clients
                </button>
                <button class="tab" onclick="switchTab('unavailability')">
                    <i class="fas fa-ban"></i> Indisponibilités & Fermetures
                </button>
            </div>

            <!-- NOTIFICATIONS -->
            <?php if (isset($successMessage)): ?>
                <div class="notification success">
                    <?= htmlspecialchars($successMessage) ?>
                </div>
            <?php endif; ?>
            <?php if (isset($errorMessage)): ?>
                <div class="notification error">
                    <?= htmlspecialchars($errorMessage) ?>
                </div>
            <?php endif; ?>

            <!-- CONTENU ONGLET 1: COMMANDES FOURNISSEURS -->
            <div id="suppliers-content" class="tab-content active">
                <h2><i class="fas fa-truck"></i> Gestion des Commandes Fournisseurs</h2>
                <p style="margin: 10px 0 30px; color: #666;">Gérez vos commandes auprès de vos fournisseurs</p>

                <?php foreach ($suppliers as $supplierName => $supplierData): ?>
                    <form method="POST" class="supplier-card">
                        <div class="supplier-header">
                            <div class="supplier-name">
                                <i class="fas fa-store"></i> <?= htmlspecialchars($supplierName) ?>
                            </div>
                            <div style="color: #666;">
                                <i class="fas fa-envelope"></i> <?= htmlspecialchars($supplierData['email']) ?>
                            </div>
                        </div>

                        <div class="products-grid">
                            <?php foreach ($supplierData['products'] as $index => $product): ?>
                                <div class="product-item">
                                    <div class="product-info">
                                        <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
                                        <div class="product-price"><?= number_format($product['price'], 2) ?> €</div>
                                    </div>
                                    <input 
                                        type="number" 
                                        name="quantity_<?= $index ?>" 
                                        class="quantity-input" 
                                        min="0" 
                                        value="0"
                                        data-price="<?= $product['price'] ?>"
                                        onchange="updateTotal('<?= $supplierName ?>')">
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div id="total-<?= $supplierName ?>" class="total-section">
                            <div>Total commande:</div>
                            <div class="total-amount">0.00 €</div>
                        </div>

                        <input type="hidden" name="supplier" value="<?= htmlspecialchars($supplierName) ?>">
                        <button type="submit" name="send_order" class="send-order-btn">
                            <i class="fas fa-paper-plane"></i> Envoyer la commande par email
                        </button>
                    </form>
                <?php endforeach; ?>
            </div>

            <!-- CONTENU ONGLET 2: COMMANDES CLIENTS -->
            <div id="orders-content" class="tab-content">
                <h2><i class="fas fa-shopping-cart"></i> Historique des Commandes Clients</h2>
                <p style="margin: 10px 0 30px; color: #666;">Consultez toutes les commandes passées sur le site</p>

                <?php
                // Lecture des commandes
                $ordersFile = __DIR__ . '/orders.json';
                $orders = [];
                
                if (file_exists($ordersFile)) {
                    $ordersContent = file_get_contents($ordersFile);
                    $orders = json_decode($ordersContent, true) ?? [];
                    
                    // Trier par date décroissante
                    usort($orders, function($a, $b) {
                        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
                    });
                }

                if (empty($orders)): ?>
                    <div style="text-align: center; padding: 50px; color: #999;">
                        <i class="fas fa-inbox" style="font-size: 64px; margin-bottom: 20px;"></i>
                        <p>Aucune commande pour le moment</p>
                    </div>
                <?php else: ?>
                    <div style="margin-bottom: 20px; color: #666;">
                        <strong><?= count($orders) ?></strong> commande(s) au total
                    </div>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Date/Heure</th>
                                <th>Client</th>
                                <th>Téléphone</th>
                                <th>Mode</th>
                                <th>Total</th>
                                <th>Détails</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($orders, 0, 50) as $order): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($order['timestamp'])) ?></td>
                                    <td><strong><?= htmlspecialchars($order['customer']['name']) ?></strong></td>
                                    <td><?= htmlspecialchars($order['customer']['phone']) ?></td>
                                    <td>
                                        <?php if ($order['deliveryMode'] === 'delivery'): ?>
                                            <span style="color: #667eea;"><i class="fas fa-motorcycle"></i> Livraison</span>
                                        <?php else: ?>
                                            <span style="color: #28a745;"><i class="fas fa-walking"></i> À emporter</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= number_format($order['totalPrice'], 2) ?> €</strong></td>
                                    <td>
                                        <button onclick="showOrderDetails(<?= htmlspecialchars(json_encode($order)) ?>)" 
                                                style="padding: 5px 15px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer;">
                                            <i class="fas fa-eye"></i> Voir
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- CONTENU ONGLET 3: INDISPONIBILITÉS -->
            <div id="unavailability-content" class="tab-content">
                <h2><i class="fas fa-ban"></i> Gestion des Indisponibilités</h2>
                <p style="margin: 10px 0 30px; color: #666;">Marquez les produits indisponibles et gérez les fermetures</p>

                <div style="margin-bottom: 30px;">
                    <h3 style="margin-bottom: 15px;"><i class="fas fa-pizza-slice"></i> Pizzas</h3>
                    <div id="pizzas-grid" class="items-grid"></div>
                </div>

                <div style="margin-bottom: 30px;">
                    <h3 style="margin-bottom: 15px;"><i class="fas fa-bread-slice"></i> Pâtes</h3>
                    <div id="pates-grid" class="items-grid"></div>
                </div>

                <div style="margin-bottom: 30px;">
                    <h3 style="margin-bottom: 15px;"><i class="fas fa-leaf"></i> Salades</h3>
                    <div id="salades-grid" class="items-grid"></div>
                </div>

                <div style="margin-bottom: 30px;">
                    <h3 style="margin-bottom: 15px;"><i class="fas fa-ice-cream"></i> Desserts</h3>
                    <div id="desserts-grid" class="items-grid"></div>
                </div>

                <button class="save-btn" onclick="saveUnavailability()">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
            </div>
        </div>

        <script src="data.js"></script>
        <script>
            // Gestion des onglets
            function switchTab(tabName) {
                // Masquer tous les contenus
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                
                // Désactiver tous les onglets
                document.querySelectorAll('.tab').forEach(tab => {
                    tab.classList.remove('active');
                });
                
                // Activer l'onglet et le contenu sélectionnés
                document.getElementById(tabName + '-content').classList.add('active');
                event.target.classList.add('active');
            }

            // Calcul des totaux pour commandes fournisseurs
            function updateTotal(supplier) {
                const card = document.querySelector(`input[value="${supplier}"]`).closest('.supplier-card');
                const inputs = card.querySelectorAll('.quantity-input');
                let total = 0;
                
                inputs.forEach(input => {
                    const quantity = parseInt(input.value) || 0;
                    const price = parseFloat(input.dataset.price) || 0;
                    total += quantity * price;
                });
                
                const totalElement = card.querySelector(`#total-${supplier} .total-amount`);
                if (totalElement) {
                    totalElement.textContent = total.toFixed(2) + ' €';
                }
            }

            // Afficher détails commande
            function showOrderDetails(order) {
                let details = `═══════════════════════════════════\n`;
                details += `📋 COMMANDE DU ${new Date(order.timestamp).toLocaleString('fr-FR')}\n`;
                details += `═══════════════════════════════════\n\n`;
                details += `👤 CLIENT:\n`;
                details += `   Nom: ${order.customer.name}\n`;
                details += `   Téléphone: ${order.customer.phone}\n`;
                details += `   Email: ${order.customer.email || 'Non renseigné'}\n\n`;
                
                if (order.deliveryMode === 'delivery') {
                    details += `🏠 LIVRAISON:\n`;
                    details += `   Adresse: ${order.customer.address}\n`;
                    details += `   Frais de livraison: ${order.deliveryFee.toFixed(2)} €\n\n`;
                } else {
                    details += `🚶 À EMPORTER\n\n`;
                }
                
                details += `🍕 ARTICLES:\n`;
                order.items.forEach((item, index) => {
                    details += `   ${index + 1}. ${item.name}\n`;
                    details += `      Quantité: ${item.quantity}\n`;
                    details += `      Prix: ${item.price.toFixed(2)} €\n`;
                    if (item.options && item.options.length > 0) {
                        details += `      Options: ${item.options.join(', ')}\n`;
                    }
                    details += `\n`;
                });
                
                details += `═══════════════════════════════════\n`;
                details += `💰 TOTAL: ${order.totalPrice.toFixed(2)} €\n`;
                details += `═══════════════════════════════════`;
                
                alert(details);
            }

            // Gestion des indisponibilités
            let unavailability = <?= json_encode($unavailabilityData) ?>;

            function loadPizzas() {
                const grid = document.getElementById('pizzas-grid');
                grid.innerHTML = '';
                
                pizzas.forEach(pizza => {
                    const id = `pizza-${pizza.id}`;
                    const isUnavailable = unavailability.items[id] || false;
                    
                    const card = document.createElement('div');
                    card.className = `item-card ${isUnavailable ? 'unavailable' : ''}`;
                    card.onclick = () => toggleItem(id);
                    card.innerHTML = `
                        <div style="font-size: 48px;">${isUnavailable ? '🚫' : '✅'}</div>
                        <div class="item-name">${pizza.name}</div>
                    `;
                    grid.appendChild(card);
                });
            }

            function loadPates() {
                const grid = document.getElementById('pates-grid');
                grid.innerHTML = '';
                
                pates.forEach(pate => {
                    const id = `pate-${pate.id}`;
                    const isUnavailable = unavailability.items[id] || false;
                    
                    const card = document.createElement('div');
                    card.className = `item-card ${isUnavailable ? 'unavailable' : ''}`;
                    card.onclick = () => toggleItem(id);
                    card.innerHTML = `
                        <div style="font-size: 48px;">${isUnavailable ? '🚫' : '✅'}</div>
                        <div class="item-name">${pate.name}</div>
                    `;
                    grid.appendChild(card);
                });
            }

            function loadSalades() {
                const grid = document.getElementById('salades-grid');
                grid.innerHTML = '';
                
                salades.forEach(salade => {
                    const id = `salade-${salade.id}`;
                    const isUnavailable = unavailability.items[id] || false;
                    
                    const card = document.createElement('div');
                    card.className = `item-card ${isUnavailable ? 'unavailable' : ''}`;
                    card.onclick = () => toggleItem(id);
                    card.innerHTML = `
                        <div style="font-size: 48px;">${isUnavailable ? '🚫' : '✅'}</div>
                        <div class="item-name">${salade.name}</div>
                    `;
                    grid.appendChild(card);
                });
            }

            function loadDesserts() {
                const grid = document.getElementById('desserts-grid');
                grid.innerHTML = '';
                
                desserts.forEach(dessert => {
                    const id = `dessert-${dessert.id}`;
                    const isUnavailable = unavailability.items[id] || false;
                    
                    const card = document.createElement('div');
                    card.className = `item-card ${isUnavailable ? 'unavailable' : ''}`;
                    card.onclick = () => toggleItem(id);
                    card.innerHTML = `
                        <div style="font-size: 48px;">${isUnavailable ? '🚫' : '✅'}</div>
                        <div class="item-name">${dessert.name}</div>
                    `;
                    grid.appendChild(card);
                });
            }

            function toggleItem(itemId) {
                unavailability.items[itemId] = !unavailability.items[itemId];
                loadPizzas();
                loadPates();
                loadSalades();
                loadDesserts();
            }

            function saveUnavailability() {
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(unavailability)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ Modifications enregistrées avec succès !');
                    } else {
                        alert('❌ Erreur: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('❌ Erreur de sauvegarde');
                    console.error(error);
                });
            }

            // Charger les produits au démarrage
            document.addEventListener('DOMContentLoaded', function() {
                loadPizzas();
                loadPates();
                loadSalades();
                loadDesserts();
            });
        </script>
    <?php endif; ?>
</body>
</html>
