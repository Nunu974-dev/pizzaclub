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
        'email' => 'commande@aphrodrink.re',
        'products' => [
            ['name' => 'Coca-Cola 33 cl', 'price' => 0.65],
            ['name' => 'Coca-Cola 50 cl', 'price' => 0.95],
            ['name' => 'Coca-Cola 1,5 L', 'price' => 1.6],
            ['name' => 'Fanta Orange 33 cl', 'price' => 0.65],
            ['name' => 'Fanta Orange 50 cl', 'price' => 0.95],
            ['name' => 'Polka Thé Melon', 'price' => 1.8],
            ['name' => 'Polka Thé Pêche', 'price' => 1.8],
            ['name' => 'Orangina 33 cl', 'price' => 0.7],
            ['name' => 'Orangina 50 cl', 'price' => 1],
            ['name' => 'Oasis Tropical 50 cl', 'price' => 1],
            ['name' => 'Monster 50 cl', 'price' => 1.8],
            ['name' => 'Deep 33 cl', 'price' => 0.8],
            ['name' => 'Deep 50 cl', 'price' => 1],
            ['name' => 'HK 33 cl', 'price' => 0.8],
            ['name' => 'HK 50 cl', 'price' => 1],
            ['name' => 'Fischer 33 cl', 'price' => 1.2],
            ['name' => 'Dodo 33 cl', 'price' => 1],
            ['name' => 'Edena plate 50 cl', 'price' => 0.5],
            ['name' => 'Edena plate 1,5 L', 'price' => 0.9],
            ['name' => 'Cilaos gazeuse 1 L', 'price' => 1.1],
            ['name' => 'Sambo 33 cl', 'price' => 0.8],
            ['name' => 'Capri-Sun 20 cl', 'price' => 0.6],
        ]
    ],
    'EDG' => [
        'email' => 'commande@edg.re',
        'products' => [
            ['name' => 'Escalope jaune VRC (kg)', 'price' => 10.56],
            ['name' => 'Merguez poulet vrac (kg)', 'price' => 9.36],
            ['name' => 'Saucisse fumée poulet (kg)', 'price' => 9.6],
        ]
    ],
    'Zembal' => [
        'email' => 'commande@zembal.re',
        'products' => [
            ['name' => 'Boîte pizza 33 (x100)', 'price' => 22],
            ['name' => 'Boîte pizza 40 (x100)', 'price' => 35],
            ['name' => 'Boîte pizza DWK 26H4 (x100)', 'price' => 19],
            ['name' => 'Assiettes XS + couvercles (x100)', 'price' => 22],
            ['name' => 'Assiettes M 1000ml + couvercles (x50)', 'price' => 14],
            ['name' => 'Pots sauce 25ml (x200)', 'price' => 9],
            ['name' => 'Sacs bretelles PM (x1000)', 'price' => 48.8],
            ['name' => 'Essuie-mains DC450 (6 rouleaux)', 'price' => 15.9],
        ]
    ],
    'Topaze' => [
        'email' => 'commande@topaze.re',
        'products' => [
            ['name' => 'Farine Tipo 0 (10x1kg)', 'price' => 10.9],
            ['name' => 'Farine T55 Moulin Vert (10x1kg)', 'price' => 9.8],
            ['name' => 'Boîtes pizza 40 Delicious (x50)', 'price' => 35.95],
            ['name' => 'Huile de grignon 5L', 'price' => 18.71],
        ]
    ],
    'Frais Import' => [
        'email' => 'commande@fraisimport.re',
        'products' => [
            ['name' => 'Emmental râpé 45% 1kg', 'price' => 7.7],
            ['name' => 'Mozzarella râpée 2kg', 'price' => 7.4],
            ['name' => 'Lardons fumés 1kg', 'price' => 7.9],
            ['name' => 'Crème cuisson 18% 1L', 'price' => 4.1],
            ['name' => 'Olives noires dénoyautées 5/1', 'price' => 0],
            ['name' => 'Sel fin 1kg', 'price' => 0],
            ['name' => 'Miel mille fleurs 1kg', 'price' => 0],
            ['name' => 'Sucre 1kg', 'price' => 0],
            ['name' => 'Thon entier naturel 4/4', 'price' => 0],
            ['name' => 'Mozzarella tranches 500g', 'price' => 0],
            ['name' => 'Emmental râpé 1kg', 'price' => 0],
            ['name' => 'Raclette tranches 500g', 'price' => 0],
            ['name' => 'Fromage chèvre IQF', 'price' => 0],
            ['name' => 'Chorizo 500g', 'price' => 0],
            ['name' => 'Épaule cuite 5kg', 'price' => 0],
            ['name' => 'Jambon DD 5kg', 'price' => 0],
            ['name' => 'Gnocchi Surgital 10kg', 'price' => 0],
            ['name' => 'Lunettes Surgital 3kg', 'price' => 0],
        ]
    ],
    'SIS' => [
        'email' => 'commande@sis.re',
        'products' => [
            ['name' => 'Huile tournesol COOSOL 5L', 'price' => 0],
            ['name' => 'Bleu cube 14mm 1.3kg', 'price' => 0],
            ['name' => 'Raclette tranches 500g surg', 'price' => 0],
            ['name' => 'Boeuf haché égrené 1kg', 'price' => 0],
            ['name' => 'Oignons frits Brover 1kg', 'price' => 0],
            ['name' => 'Sarcive de volaille 2kg', 'price' => 0],
            ['name' => 'Crème cuisson 18% Président 1L', 'price' => 0],
            ['name' => 'Crème 18% Larsa 1L', 'price' => 0],
            ['name' => 'Mozzarella râpée 2kg', 'price' => 0],
            ['name' => 'Mozzarella tranches 500g IQF', 'price' => 0],
            ['name' => 'Fromage cheddar burger 88tr', 'price' => 0],
            ['name' => 'Chute saumon fumé 1kg', 'price' => 0],
            ['name' => 'Saumon Salar 1kg', 'price' => 0],
            ['name' => 'Lait 1/2 écrémé 1L', 'price' => 0],
            ['name' => 'Gants nitrile noirs', 'price' => 0],
            ['name' => 'Lavettes jaunes 36x42', 'price' => 0],
        ]
    ],
];

// Traitement de l'envoi de commande
if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_order'])) {
    $supplierName = $_POST['supplier'];
    $orders = $_POST['quantities'] ?? [];
    
    if (!empty($orders)) {
        // Filtrer les quantités non nulles
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
        
        if (!empty($orderedItems)) {
            // Envoyer l'email
            $success = sendOrderEmail($supplierName, $suppliers[$supplierName]['email'], $orderedItems, $total);
            if ($success) {
                $successMessage = "✅ Commande envoyée à $supplierName !";
            } else {
                $errorMessage = "❌ Erreur lors de l'envoi de la commande.";
            }
        }
    }
}

function sendOrderEmail($supplierName, $email, $items, $total) {
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
            </div>
            
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

        @media (max-width: 768px) {
            .suppliers-grid {
                grid-template-columns: 1fr;
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

            <div class="suppliers-grid">
                <?php foreach ($suppliers as $name => $supplier): ?>
                    <div class="supplier-card">
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

                            <div class="total-section" id="total-<?= $name ?>">
                                <div class="total-label">Total</div>
                                <div class="total-amount">0,00 €</div>
                            </div>

                            <button type="submit" class="btn-send-order">
                                <i class="fas fa-paper-plane"></i> Envoyer la commande
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <script>
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
                totalElement.textContent = total.toFixed(2).replace('.', ',') + ' €';
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
