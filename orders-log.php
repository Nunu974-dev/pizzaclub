<?php
/**
 * VISUALISEUR DE COMMANDES
 * Affiche toutes les commandes enregistrées
 * URL: https://www.pizzaclub.re/orders-log.php
 */

session_start();
date_default_timezone_set('Indian/Reunion');

// Configuration
$LOGIN = 'pizzaclub';
$PASSWORD = 'pizza2024'; // CHANGE CE MOT DE PASSE !

// Gestion connexion
if (isset($_POST['login']) && isset($_POST['password'])) {
    if ($_POST['login'] === $LOGIN && $_POST['password'] === $PASSWORD) {
        $_SESSION['logged_orders'] = true;
    } else {
        $error = 'Identifiants incorrects';
    }
}

// Déconnexion
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: orders-log.php');
    exit;
}

// Vérifier si connecté
if (!isset($_SESSION['logged_orders']) || $_SESSION['logged_orders'] !== true) {
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>🔐 Connexion - Commandes Pizza Club</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: Arial, sans-serif; 
                background: linear-gradient(135deg, #FF0000 0%, #8B0000 100%);
                height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .login-box {
                background: white;
                padding: 40px;
                border-radius: 10px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                width: 100%;
                max-width: 400px;
            }
            h1 { 
                color: #FF0000; 
                text-align: center;
                margin-bottom: 30px;
            }
            .form-group {
                margin-bottom: 20px;
            }
            label {
                display: block;
                margin-bottom: 5px;
                font-weight: bold;
                color: #333;
            }
            input {
                width: 100%;
                padding: 12px;
                border: 2px solid #ddd;
                border-radius: 5px;
                font-size: 16px;
            }
            input:focus {
                outline: none;
                border-color: #FF0000;
            }
            button {
                width: 100%;
                padding: 15px;
                background: #FF0000;
                color: white;
                border: none;
                border-radius: 5px;
                font-size: 18px;
                font-weight: bold;
                cursor: pointer;
            }
            button:hover {
                background: #CC0000;
            }
            .error {
                background: #ffebee;
                color: #c62828;
                padding: 10px;
                border-radius: 5px;
                margin-bottom: 20px;
                text-align: center;
            }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h1>🔐 Connexion</h1>
            <?php if (isset($error)): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label for="login">Identifiant</label>
                    <input type="text" id="login" name="login" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit">Se connecter</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ====== API POLLING - vérifier nouvelles commandes ======
if (isset($_GET['action']) && $_GET['action'] === 'check') {
    header('Content-Type: application/json');
    $file = __DIR__ . '/orders.json';
    $orders = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
    $count = is_array($orders) ? count($orders) : 0;
    $lastId = ($count > 0) ? ($orders[0]['id'] ?? '') : '';
    echo json_encode(['count' => $count, 'lastId' => (string)$lastId]);
    exit;
}

// Lire le fichier de commandes
$ordersFile = __DIR__ . '/orders.json';
$debugFile = __DIR__ . '/debug-order.txt';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📋 Commandes Pizza Club</title>
    <!-- PWA -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#FF0000">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="🍕 Commandes">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            background: #f5f5f5; 
            padding: 20px;
        }
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            background: white; 
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { 
            color: #FF0000; 
            margin-bottom: 20px;
            text-align: center;
        }
        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .logout-btn {
            background: #666;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
        }
        .logout-btn:hover {
            background: #444;
        }
        .info-box {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #2196F3;
        }
        .order { 
            border: 2px solid #e0e0e0; 
            padding: 20px; 
            margin: 20px 0;
            border-radius: 8px;
            background: #fafafa;
        }
        .order-header { 
            background: #FF0000; 
            color: white; 
            padding: 15px;
            margin: -20px -20px 15px -20px;
            border-radius: 6px 6px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .order-number { font-size: 20px; font-weight: bold; }
        .order-date { font-size: 14px; opacity: 0.9; }
        .customer-info { 
            background: #fff3cd; 
            padding: 15px; 
            border-left: 4px solid #ffc107;
            margin: 15px 0;
        }
        .customer-info strong { color: #000; }
        .items-list { 
            background: white; 
            padding: 15px;
            border: 1px solid #e0e0e0;
            margin: 15px 0;
        }
        .item { 
            padding: 10px; 
            border-bottom: 1px solid #f0f0f0;
            margin: 5px 0;
        }
        .item:last-child { border-bottom: none; }
        .item-name { 
            font-weight: bold; 
            color: #FF0000;
            font-size: 16px;
        }
        .item-details { 
            color: #666; 
            font-size: 14px;
            margin: 5px 0 5px 20px;
        }
        .total { 
            background: #28a745; 
            color: white; 
            padding: 15px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            border-radius: 5px;
            margin: 15px 0;
        }
        .mode-badge { 
            display: inline-block;
            padding: 5px 15px;
            background: #FFC107;
            color: #000;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
        }
        .no-orders { 
            text-align: center; 
            padding: 50px; 
            color: #999;
            font-size: 18px;
        }
        .debug-section { 
            margin-top: 50px; 
            padding-top: 30px; 
            border-top: 3px solid #e0e0e0;
        }
        .debug-content { 
            background: #2d2d2d; 
            color: #0f0; 
            padding: 20px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            overflow-x: auto;
            white-space: pre-wrap;
            max-height: 500px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-bar">
            <h1>📋 Historique des commandes Pizza Club</h1>
            <a href="?logout" class="logout-btn">🚪 Déconnexion</a>
        </div>
        
        <div class="info-box">
            <strong>📍 Fichiers:</strong><br>
            JSON: <?= file_exists($ordersFile) ? '✅ Trouvé' : '❌ Introuvable' ?> (<?= $ordersFile ?>)<br>
            Debug: <?= file_exists($debugFile) ? '✅ Trouvé' : '❌ Introuvable' ?> (<?= $debugFile ?>)
        </div>
        
        <?php
        // Afficher les commandes du fichier JSON
        if (file_exists($ordersFile)) {
            $ordersJson = file_get_contents($ordersFile);
            $orders = json_decode($ordersJson, true);
            
            if ($orders && count($orders) > 0) {
                echo "<div class='info-box'><strong>📊 " . count($orders) . " commande(s) enregistrée(s)</strong></div>";
                
                // Trier par date décroissante (plus récentes en premier)
                usort($orders, function($a, $b) {
                    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
                });
                
                foreach ($orders as $order) {
                    $customer = $order['customer'];
                    $deliveryMode = $customer['deliveryMode'] === 'livraison' ? '🛵 LIVRAISON' : '🏃 À EMPORTER';
                    ?>
                    <div class="order">
                        <div class="order-header">
                            <span class="order-number"><?= htmlspecialchars($order['orderNumber']) ?></span>
                            <span class="order-date"><?= date('d/m/Y à H:i', strtotime($order['timestamp'])) ?></span>
                        </div>
                        
                        <span class="mode-badge"><?= $deliveryMode ?></span>
                        
                        <?php 
                        // Vérifier si c'est une commande programmée
                        $isScheduled = !empty($order['scheduledDate']) && isset($order['scheduledTime']);
                        ?>
                        
                        <?php if ($isScheduled): ?>
                            <div style="background: #fff3cd; border: 2px solid #ffc107; padding: 15px; margin: 15px 0; border-radius: 5px;">
                                <strong style="color: #856404;">⏰ COMMANDE PROGRAMMÉE</strong><br>
                                <span style="color: #856404;">
                                    📅 Date: <?= htmlspecialchars($order['scheduledDate']) ?><br>
                                    🕐 Créneau: <?= (int)$order['scheduledTime'] ?>:00 - <?= ((int)$order['scheduledTime'] + 1) ?>:00
                                </span>
                            </div>
                        <?php else: ?>
                            <div style="background: #d4edda; border: 2px solid #28a745; padding: 15px; margin: 15px 0; border-radius: 5px;">
                                <strong style="color: #155724;">⚡ COMMANDE IMMÉDIATE</strong>
                            </div>
                        <?php endif; ?>
                        
                        <div class="customer-info">
                            <strong>Client:</strong> <?= htmlspecialchars($customer['firstName']) ?> <?= htmlspecialchars($customer['lastName']) ?><br>
                            <strong>Téléphone:</strong> <?= htmlspecialchars($customer['phone']) ?><br>
                            <?php if (!empty($customer['email'])): ?>
                                <strong>Email:</strong> <?= htmlspecialchars($customer['email']) ?><br>
                            <?php endif; ?>
                            <?php if ($customer['deliveryMode'] === 'livraison'): ?>
                                <strong>Adresse:</strong> <?= htmlspecialchars($customer['address']) ?>, <?= htmlspecialchars($customer['postalCode']) ?> <?= htmlspecialchars($customer['city']) ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="items-list">
                            <h3 style="margin-bottom: 15px; color: #FF0000;">📦 Articles commandés</h3>
                            <?php foreach ($order['items'] as $item): ?>
                                <div class="item">
                                    <div class="item-name">
                                        <?= htmlspecialchars($item['name']) ?> x<?= $item['quantity'] ?>
                                    </div>
                                    <?php if (isset($item['customization']) && !empty($item['customization'])): ?>
                                        <div class="item-details">
                                            <?php
                                            $custom = $item['customization'];
                                            
                                            // TAILLE
                                            if (!empty($custom['size'])) {
                                                $sizeLabel = $custom['size'];
                                                if ($custom['size'] === 'moyenne') $sizeLabel = '33cm';
                                                if ($custom['size'] === 'grande') $sizeLabel = '40cm';
                                                if ($custom['size'] === 'L') $sizeLabel = 'Large';
                                                if ($custom['size'] === 'XL') $sizeLabel = 'XL';
                                                echo "📏 Taille: " . htmlspecialchars($sizeLabel) . "<br>";
                                            }
                                            
                                            // BASE (pizzas, pâtes, salades)
                                            if (!empty($custom['base'])) {
                                                $baseLabel = $custom['base'];
                                                if ($custom['base'] === 'creme') $baseLabel = 'Crème';
                                                if ($custom['base'] === 'tomate') $baseLabel = 'Tomate';
                                                echo "🍕 Base: " . htmlspecialchars($baseLabel) . "<br>";
                                            }
                                            
                                            // RETIRER (chercher dans toutes les variantes possibles)
                                            $removed = $custom['removedIngredients'] ?? $custom['ingredients']['removed'] ?? $custom['removed'] ?? [];
                                            if (!empty($removed)) {
                                                echo "❌ Retirer: " . htmlspecialchars(implode(', ', $removed)) . "<br>";
                                            }
                                            
                                            // AJOUTER (chercher dans toutes les variantes possibles)
                                            $added = $custom['addedIngredients'] ?? $custom['ingredients']['added'] ?? $custom['added'] ?? [];
                                            if (!empty($added)) {
                                                echo "➕ Ajouter: " . htmlspecialchars(implode(', ', $added)) . "<br>";
                                            }
                                            
                                            // INGRÉDIENTS (buns et rolls)
                                            if (($item['type'] === 'bun' || $item['type'] === 'roll') && !empty($custom['ingredients'])) {
                                                if (is_array($custom['ingredients'])) {
                                                    echo "🥗 INGRÉDIENTS: " . htmlspecialchars(implode(', ', $custom['ingredients'])) . "<br>";
                                                } else {
                                                    echo "🥗 INGRÉDIENTS: " . htmlspecialchars($custom['ingredients']) . "<br>";
                                                }
                                            }
                                            
                                            // FORMAT (buns x1/x3)
                                            if ($item['type'] === 'bun' && !empty($custom['format'])) {
                                                echo "📦 Format: " . htmlspecialchars($custom['format']) . "<br>";
                                            }
                                            
                                            // TYPE (buns: pizza ou pâte)
                                            if ($item['type'] === 'bun' && !empty($custom['type'])) {
                                                $bunType = $custom['type'];
                                                if ($bunType === 'pizza') echo "🍕 Type: BASE PIZZA<br>";
                                                elseif ($bunType === 'pate') echo "🍝 Type: BASE PÂTE<br>";
                                                else echo "Type: " . htmlspecialchars($bunType) . "<br>";
                                            }
                                            
                                            // SUPPLÉMENTS (pâtes, salades, rolls, buns)
                                            if (!empty($custom['supplements'])) {
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
                                                $suppNames = array_map(function($key) use ($names) {
                                                    return $names[$key] ?? ucfirst($key);
                                                }, $custom['supplements']);
                                                echo "➕ SUPPLÉMENTS: " . htmlspecialchars(implode(', ', $suppNames)) . "<br>";
                                            }
                                            
                                            // OPTIONS (salades: pain, vinaigrette sup)
                                            if (!empty($custom['options'])) {
                                                $optionLabels = array_map(function($opt) {
                                                    if ($opt === 'pain') return 'Pain';
                                                    if ($opt === 'vinaigrette-sup') return 'Vinaigrette sup.';
                                                    return ucfirst($opt);
                                                }, $custom['options']);
                                                echo "🔸 OPTIONS: " . htmlspecialchars(implode(', ', $optionLabels)) . "<br>";
                                            }
                                            
                                            // FORMULES avec pizza
                                            if (!empty($custom['pizza'])) {
                                                echo "🍕 Pizza: " . htmlspecialchars($custom['pizza']) . "<br>";
                                                if (!empty($custom['pizzaCustomization'])) {
                                                    $pizzaCust = $custom['pizzaCustomization'];
                                                    if (!empty($pizzaCust['size'])) {
                                                        $sizeLabel = $pizzaCust['size'];
                                                        if ($pizzaCust['size'] === 'moyenne') $sizeLabel = '33cm';
                                                        if ($pizzaCust['size'] === 'grande') $sizeLabel = '40cm';
                                                        echo "&nbsp;&nbsp;↳ Taille: " . htmlspecialchars($sizeLabel) . "<br>";
                                                    }
                                                    if (!empty($pizzaCust['base']) && $pizzaCust['base'] !== 'tomate') {
                                                        echo "&nbsp;&nbsp;↳ Base: " . htmlspecialchars($pizzaCust['base']) . "<br>";
                                                    }
                                                    if (!empty($pizzaCust['ingredients']['added'])) {
                                                        echo "&nbsp;&nbsp;↳ ➕ Ajouts: " . htmlspecialchars(implode(', ', $pizzaCust['ingredients']['added'])) . "<br>";
                                                    }
                                                    if (!empty($pizzaCust['ingredients']['removed'])) {
                                                        echo "&nbsp;&nbsp;↳ ❌ Retraits: " . htmlspecialchars(implode(', ', $pizzaCust['ingredients']['removed'])) . "<br>";
                                                    }
                                                }
                                            }
                                            
                                            // FORMULES avec pâtes/salade
                                            if (!empty($custom['mainItem']) && is_array($custom['mainItem'])) {
                                                $mainType = $custom['mainItem']['type'] === 'pate' ? '🍝' : '🥗';
                                                echo $mainType . " " . htmlspecialchars($custom['mainItem']['name']) . "<br>";
                                                if (!empty($custom['mainItem']['customization']['size'])) {
                                                    $sizeLabel = $custom['mainItem']['customization']['size'];
                                                    if ($sizeLabel === 'L') $sizeLabel = 'Large';
                                                    if ($sizeLabel === 'XL') $sizeLabel = 'XL';
                                                    echo "&nbsp;&nbsp;↳ Taille: " . htmlspecialchars($sizeLabel) . "<br>";
                                                }
                                                if (!empty($custom['mainItem']['customization']['base'])) {
                                                    echo "&nbsp;&nbsp;↳ Base: " . htmlspecialchars($custom['mainItem']['customization']['base']) . "<br>";
                                                }
                                                if (!empty($custom['mainItem']['customization']['options'])) {
                                                    echo "&nbsp;&nbsp;↳ Options: " . htmlspecialchars(implode(', ', $custom['mainItem']['customization']['options'])) . "<br>";
                                                }
                                                if (!empty($custom['mainItem']['customization']['supplements'])) {
                                                    echo "&nbsp;&nbsp;↳ + " . htmlspecialchars(implode(', ', $custom['mainItem']['customization']['supplements'])) . "<br>";
                                                }
                                            }
                                            
                                            // BOISSON (formules)
                                            if (!empty($custom['boisson'])) {
                                                echo "🥤 " . htmlspecialchars($custom['boisson']) . "<br>";
                                            }
                                            
                                            // DESSERT (formules)
                                            if (!empty($custom['dessert'])) {
                                                echo "🍰 " . htmlspecialchars($custom['dessert']) . "<br>";
                                            }
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="item-details">
                                        💰 <?= number_format($item['totalPrice'], 2, ',', ' ') ?>€
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (!empty($order['promoCode']) && !empty($order['discount']) && $order['discount'] > 0): ?>
                        <div style="display:flex;justify-content:space-between;padding:6px 12px;background:#fff3cd;border-radius:6px;margin-bottom:6px;font-size:14px;color:#856404;">
                            <span>🏷 Code promo <strong><?= htmlspecialchars($order['promoCode']) ?></strong></span>
                            <span>-<?= number_format($order['discount'], 2, ',', ' ') ?>€</span>
                        </div>
                        <?php endif; ?>
                        <div class="total">
                            TOTAL: <?= number_format($order['total'], 2, ',', ' ') ?>€
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '<div class="no-orders">Aucune commande enregistrée (le fichier JSON est vide)</div>';
            }
        } else {
            echo '<div class="no-orders">';
            echo '<strong>❌ Fichier orders.json introuvable</strong><br><br>';
            echo 'Chemin recherché: <code>' . $ordersFile . '</code><br><br>';
            echo 'Le fichier sera créé automatiquement à la prochaine commande.';
            echo '</div>';
        }
        ?>
        
        <!-- Section DEBUG -->
        <div class="debug-section">
            <h2 style="color: #666; margin-bottom: 15px;">🔧 Debug - Dernières commandes brutes</h2>
            <div class="debug-content">
                <?php
                if (file_exists($debugFile)) {
                    // Lire toutes les lignes
                    $content = file_get_contents($debugFile);
                    if (empty($content)) {
                        echo "Le fichier debug-order.txt existe mais est vide.";
                    } else {
                        // Afficher les 5000 derniers caractères (environ 3-5 dernières commandes)
                        $lines = file($debugFile);
                        $lastLines = array_slice($lines, -300); // 300 dernières lignes
                        echo htmlspecialchars(implode('', $lastLines));
                    }
                } else {
                    echo "❌ Fichier debug-order.txt introuvable\n\n";
                    echo "Chemin recherché: " . $debugFile . "\n\n";
                    echo "Le fichier sera créé automatiquement à la prochaine commande.";
                }
                ?>
            </div>
        </div>
    </div>

<!-- ====== ALARME COMMANDE + PWA ====== -->

<!-- Overlay alarme plein écran -->
<div id="alarmOverlay" style="
    display:none; position:fixed; inset:0; z-index:99999;
    background:#CC0000;
    flex-direction:column; align-items:center; justify-content:center;
    text-align:center;
">
    <div id="alarmIcon" style="font-size:100px; animation:pulse 0.6s infinite alternate;">🍕</div>
    <div style="color:white; font-size:36px; font-weight:900; margin:20px 0; text-shadow:0 2px 8px rgba(0,0,0,0.4);" id="alarmTitle">
        NOUVELLE COMMANDE !
    </div>
    <div id="alarmDetails" style="color:rgba(255,255,255,0.9); font-size:18px; margin-bottom:40px; max-width:320px; line-height:1.5;"></div>
    <button onclick="confirmOrder()" style="
        background:white; color:#CC0000;
        border:none; padding:22px 60px;
        font-size:24px; font-weight:900;
        border-radius:16px;
        box-shadow:0 8px 30px rgba(0,0,0,0.3);
        cursor:pointer; letter-spacing:1px;
        animation:scalePulse 0.8s infinite alternate;
    ">✅ CONFIRMER</button>
    <div style="color:rgba(255,255,255,0.6); font-size:13px; margin-top:20px;">
        Appuyer pour arrêter la sonnerie
    </div>
</div>

<style>
    @keyframes pulse { from { transform:scale(1); } to { transform:scale(1.2); } }
    @keyframes scalePulse { from { transform:scale(1); } to { transform:scale(1.06); box-shadow:0 12px 40px rgba(0,0,0,0.4); } }
    @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0} }
</style>

<script>
(function() {
    let lastKnownId   = null;
    let alarmCtx      = null;
    let alarmInterval = null;
    let blinkInterval = null;
    let alarmNodes    = [];

    // ── Enregistrement Service Worker (PWA) ─────────────────
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    }

    // ── Générer son d'alarme (sonnerie urgente en boucle) ─────
    function createAlarmSound() {
        try {
            alarmCtx = new (window.AudioContext || window.webkitAudioContext)();
        } catch(e) { return; }

        function note(freq, start, dur, vol) {
            const o = alarmCtx.createOscillator();
            const g = alarmCtx.createGain();
            o.connect(g); g.connect(alarmCtx.destination);
            o.type = 'square';
            o.frequency.value = freq;
            g.gain.setValueAtTime(vol || 0.4, alarmCtx.currentTime + start);
            g.gain.exponentialRampToValueAtTime(0.001, alarmCtx.currentTime + start + dur);
            o.start(alarmCtx.currentTime + start);
            o.stop(alarmCtx.currentTime + start + dur);
            return o;
        }

        function playRing() {
            // Sonnerie style téléphone urgente
            note(1200, 0.00, 0.12);
            note(900,  0.14, 0.12);
            note(1200, 0.28, 0.12);
            note(900,  0.42, 0.12);
            note(1200, 0.56, 0.12);
        }

        playRing();
        // Se répète toutes les 1.2s
        alarmInterval = setInterval(playRing, 1200);
    }

    function stopAlarmSound() {
        clearInterval(alarmInterval);
        alarmInterval = null;
        if (alarmCtx) { try { alarmCtx.close(); } catch(e){} alarmCtx = null; }
    }

    // ── Vibration mobile en boucle ────────────────────────────
    let vibrateLoop = null;
    function startVibration() {
        if (!navigator.vibrate) return;
        navigator.vibrate([500, 200, 500, 200, 500]);
        vibrateLoop = setInterval(() => navigator.vibrate([500, 200, 500, 200, 500]), 2500);
    }
    function stopVibration() {
        clearInterval(vibrateLoop);
        if (navigator.vibrate) navigator.vibrate(0);
    }

    // ── Afficher l'alarme ─────────────────────────────────────
    function showAlarm(orderData) {
        const overlay  = document.getElementById('alarmOverlay');
        const details  = document.getElementById('alarmDetails');

        // Remplir les détails si disponibles
        if (orderData) {
            details.textContent = orderData;
        }

        overlay.style.display = 'flex';

        // Clignotement titre onglet
        blinkInterval = setInterval(() => {
            document.title = document.title.startsWith('🔔')
                ? '📋 Commandes Pizza Club'
                : '🔔 NOUVELLE COMMANDE !';
        }, 700);

        createAlarmSound();
        startVibration();

        // Notification navigateur persistante
        if (Notification.permission === 'granted') {
            new Notification('🍕 NOUVELLE COMMANDE !', {
                body: orderData || 'Une commande vient d\'arriver !',
                requireInteraction: true,
                icon: 'img/favicon.ico'
            });
        }
    }

    // ── Confirmer réception ───────────────────────────────────
    window.confirmOrder = function() {
        document.getElementById('alarmOverlay').style.display = 'none';
        stopAlarmSound();
        stopVibration();
        clearInterval(blinkInterval);
        document.title = '📋 Commandes Pizza Club';
        location.reload();
    };

    // ── Init : lire le dernier ID connu ───────────────────────
    fetch('orders-log.php?action=check')
        .then(r => r.json())
        .then(d => { lastKnownId = d.lastId; })
        .catch(() => {});

    // ── Polling toutes les 5 minutes ──────────────────────────
    function poll() {
        fetch('orders-log.php?action=check')
            .then(r => r.json())
            .then(data => {
                if (lastKnownId !== null && data.lastId !== lastKnownId) {
                    lastKnownId = data.lastId;
                    showAlarm('Nouvelle commande reçue');
                }
            })
            .catch(() => {});
    }

    poll(); // vérification immédiate au chargement
    setInterval(poll, 30 * 1000); // toutes les 30 secondes

    // ── Bouton installer comme app (PWA) ─────────────────────
    let deferredPrompt = null;
    const installBtn = document.createElement('button');
    installBtn.innerHTML = '📲 Installer l\'app';
    installBtn.style.cssText = 'position:fixed;bottom:80px;right:20px;background:#FF6600;color:white;border:none;padding:12px 18px;border-radius:8px;font-size:14px;font-weight:bold;cursor:pointer;box-shadow:0 4px 10px rgba(0,0,0,0.3);z-index:1000;';
    installBtn.onclick = () => {
        if(deferredPrompt){ deferredPrompt.prompt(); deferredPrompt.userChoice.then(() => installBtn.remove()); }
        else { alert('Pour installer :\n\n🟠 Chrome/Edge : Menu ⋮ → "Installer l\'application"\n🦊 Firefox : Menu ☰ → "Installer le site"\n📱 Android : Menu ⋮ → "Ajouter à l\'écran d\'accueil"'); }
    };
    document.body.appendChild(installBtn);
    window.addEventListener('beforeinstallprompt', e => { e.preventDefault(); deferredPrompt = e; });
    window.addEventListener('appinstalled', () => installBtn.remove());

    // ── Bouton activer alertes ────────────────────────────────
    const notifBtn = document.createElement('button');
    notifBtn.innerHTML = '🔔 Activer les alertes';
    notifBtn.style.cssText = 'position:fixed;bottom:20px;right:20px;background:#ff0000;color:white;border:none;padding:12px 18px;border-radius:8px;font-size:14px;font-weight:bold;cursor:pointer;box-shadow:0 4px 10px rgba(0,0,0,0.3);z-index:1000;';
    notifBtn.onclick = () => {
        Notification.requestPermission().then(p => {
            if (p === 'granted') {
                notifBtn.innerHTML = '✅ Alertes activées';
                notifBtn.style.background = '#4CAF50';
                // Test son
                showAlarm('🔔 Test sonnerie - Appuie CONFIRMER pour valider');
                setTimeout(() => { if (Notification.permission === 'granted') notifBtn.remove(); }, 4000);
            }
        });
    };
    if (Notification.permission !== 'granted') document.body.appendChild(notifBtn);
})();
</script>

</body>
</html>
