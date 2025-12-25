<?php
/**
 * DASHBOARD ADMIN UNIFIÉ - Pizza Club
 * Point d'entrée unique pour toutes les fonctionnalités admin
 */

session_start();
date_default_timezone_set('Indian/Reunion');

// Configuration
define('ADMIN_PASSWORD', 'pizzaclub2025');
define('INVENTORY_FILE', __DIR__ . '/inventory.json');
define('TEMPERATURE_FILE', __DIR__ . '/temperatures.json');

// Gestion de la connexion
if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_POST['password'])) {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['admin_dashboard_logged'] = true;
        $_SESSION['commande_logged_in'] = true; // Pour commande-fournisseurs
        $_SESSION['logged_orders'] = true; // Pour orders-log
        $_SESSION['admin_logged_in'] = true; // Pour admin-indispos
    } else {
        $error = "Mot de passe incorrect";
    }
}

$isLoggedIn = isset($_SESSION['admin_dashboard_logged']) && $_SESSION['admin_dashboard_logged'] === true;

// Gestion AJAX pour inventaire et températures
if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'application/json') !== false) {
        header('Content-Type: application/json');
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Sauvegarde inventaire
        if ($data && isset($data['inventory'])) {
            if (file_put_contents(INVENTORY_FILE, json_encode($data, JSON_PRETTY_PRINT))) {
                echo json_encode(['success' => true, 'message' => 'Inventaire sauvegardé']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Erreur sauvegarde']);
            }
        }
        // Sauvegarde températures
        elseif ($data && isset($data['temperatures'])) {
            if (file_put_contents(TEMPERATURE_FILE, json_encode($data, JSON_PRETTY_PRINT))) {
                echo json_encode(['success' => true, 'message' => 'Températures sauvegardées']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Erreur sauvegarde']);
            }
        }
        exit;
    }
}

// Chargement des données
$inventoryData = ['inventory' => [], 'lastUpdate' => null];
if (file_exists(INVENTORY_FILE)) {
    $inventoryData = json_decode(file_get_contents(INVENTORY_FILE), true);
}

$temperatureData = ['temperatures' => []];
if (file_exists(TEMPERATURE_FILE)) {
    $temperatureData = json_decode(file_get_contents(TEMPERATURE_FILE), true);
}

// Pas d'auto-remplissage - l'utilisateur entre les températures manuellement
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
            overflow: hidden;
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
            padding: 50px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 500px;
            text-align: center;
        }

        .login-box h1 {
            color: #667eea;
            font-size: 36px;
            margin-bottom: 10px;
        }

        .login-box p {
            color: #666;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 25px;
            text-align: left;
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
        }
        <?php else: ?>
        
        /* Dashboard Layout */
        .dashboard-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        .dashboard-header {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 100;
        }

        .dashboard-header h1 {
            color: #667eea;
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 10px;
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

        .tabs-nav {
            background: white;
            padding: 0 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            gap: 5px;
            overflow-x: auto;
        }

        .tab-btn {
            padding: 15px 25px;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            color: #666;
            white-space: nowrap;
            transition: all 0.3s;
        }

        .tab-btn:hover {
            color: #667eea;
            background: #f8f9fa;
        }

        .tab-btn.active {
            color: #667eea;
            border-bottom-color: #667eea;
            background: #f8f9fa;
        }

        .content-area {
            flex: 1;
            overflow: hidden;
            position: relative;
        }

        .tab-content {
            display: none;
            width: 100%;
            height: 100%;
        }

        .tab-content.active {
            display: block;
        }

        .tab-content iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .local-content {
            height: 100%;
            overflow-y: auto;
            padding: 30px;
            background: white;
        }

        /* Styles pour Gestion Restaurant */
        .management-grid {
            display: grid;
            gap: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .section-card {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .section-card h2 {
            color: #667eea;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-card h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .add-item-form {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 10px;
            margin-bottom: 20px;
            align-items: end;
        }

        .form-field label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .form-field input,
        .form-field select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }

        .btn-add {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            white-space: nowrap;
        }

        .btn-add:hover {
            background: #5568d3;
        }

        .inventory-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }

        .inventory-table th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }

        .inventory-table td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }

        .inventory-table tr:hover {
            background: #f8f9fa;
        }

        .btn-delete {
            padding: 5px 10px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .temp-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 20px;
        }

        .temp-section h4 {
            color: #667eea;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .temp-field {
            margin-bottom: 15px;
        }

        .temp-field label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }

        .temp-field input {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
        }

        .temp-field small {
            color: #999;
            font-size: 12px;
        }

        .btn-save {
            width: 100%;
            padding: 15px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
        }

        .btn-save:hover {
            background: #218838;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            margin-top: 20px;
        }

        .history-table th {
            background: #667eea;
            color: white;
            padding: 10px;
            text-align: left;
        }

        .history-table td {
            padding: 8px;
            border-bottom: 1px solid #e0e0e0;
        }

        .btn-delete-temp {
            padding: 3px 8px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }

        .btn-delete-temp:hover {
            background: #c82333;
        }

        @media (max-width: 768px) {
            .add-item-form {
                grid-template-columns: 1fr;
            }
            
            .temp-grid {
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
                <h1>🎛️ Admin Dashboard</h1>
                <p>Pizza Club - Interface d'administration unifiée</p>
                
                <?php if (isset($error)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Mot de passe</label>
                        <input type="password" name="password" required autofocus placeholder="Entrez le mot de passe">
                    </div>
                    <button type="submit" class="login-btn">
                        <i class="fas fa-sign-in-alt"></i> Se connecter
                    </button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <!-- DASHBOARD -->
        <div class="dashboard-container">
            <div class="dashboard-header">
                <h1>
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard Admin
                </h1>
                <form method="POST" style="margin: 0;">
                    <button type="submit" name="logout" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Déconnexion
                    </button>
                </form>
            </div>

            <div class="tabs-nav">
                <button class="tab-btn active" onclick="switchTab('suppliers', this)">
                    <i class="fas fa-truck"></i> Commandes Fournisseurs
                </button>
                <button class="tab-btn" onclick="switchTab('orders', this)">
                    <i class="fas fa-shopping-cart"></i> Commandes Clients
                </button>
                <button class="tab-btn" onclick="switchTab('unavailability', this)">
                    <i class="fas fa-ban"></i> Indisponibilités & Fermetures
                </button>
                <button class="tab-btn" onclick="switchTab('management', this)">
                    <i class="fas fa-cogs"></i> Gestion Restaurant
                </button>
            </div>

            <div class="content-area">
                <!-- Onglet 1: Commandes Fournisseurs -->
                <div id="tab-suppliers" class="tab-content active">
                    <iframe src="commande-fournisseurs.php"></iframe>
                </div>

                <!-- Onglet 2: Commandes Clients -->
                <div id="tab-orders" class="tab-content">
                    <iframe src="orders-log.php"></iframe>
                </div>

                <!-- Onglet 3: Indisponibilités -->
                <div id="tab-unavailability" class="tab-content">
                    <iframe src="admin-indispos-manager.php"></iframe>
                </div>

                <!-- Onglet 4: Gestion Restaurant -->
                <div id="tab-management" class="tab-content">
                    <div class="local-content">
                        <div class="management-grid">
                            <!-- INVENTAIRE ANNUEL -->
                            <div class="section-card">
                                <h2><i class="fas fa-boxes"></i> Inventaire Annuel</h2>
                                <p style="color: #666; margin-bottom: 20px;">Gérez votre inventaire des articles en stock</p>
                                
                                <h3>Ajouter un article</h3>
                                <div class="add-item-form">
                                    <div class="form-field">
                                        <label>Nom de l'article</label>
                                        <input type="text" id="item-name" placeholder="Ex: Farine T55">
                                    </div>
                                    <div class="form-field">
                                        <label>Quantité</label>
                                        <input type="number" id="item-quantity" value="0" min="0">
                                    </div>
                                    <div class="form-field">
                                        <label>Unité</label>
                                        <select id="item-unit">
                                            <option value="pièce(s)">pièce(s)</option>
                                            <option value="kg">kg</option>
                                            <option value="L">L</option>
                                            <option value="carton(s)">carton(s)</option>
                                            <option value="paquet(s)">paquet(s)</option>
                                            <option value="sachet(s)">sachet(s)</option>
                                        </select>
                                    </div>
                                    <button class="btn-add" onclick="addItem()">
                                        <i class="fas fa-plus"></i> Ajouter
                                    </button>
                                </div>

                                <div id="inventory-container">
                                    <p style="text-align: center; color: #999; padding: 40px;">Aucun article dans l'inventaire</p>
                                </div>

                                <button class="btn-save" onclick="saveInventory()">
                                    <i class="fas fa-save"></i> Sauvegarder l'inventaire
                                </button>
                            </div>

                            <!-- TEMPÉRATURES -->
                            <div class="section-card">
                                <h2><i class="fas fa-thermometer-half"></i> Relevé de Température</h2>
                                <p style="color: #666; margin-bottom: 20px;">Enregistrez les températures matin et soir - Aujourd'hui: <strong><?= date('d/m/Y') ?></strong></p>
                                
                                <?php 
                                $todayTemps = $temperatureData['temperatures'][$today] ?? [
                                    'midi' => ['frigo_boissons' => '', 'frigo_blanc' => '', 'congelateur' => '', 'frigo_armoire' => ''],
                                    'soir' => ['frigo_boissons' => '', 'frigo_blanc' => '', 'congelateur' => '', 'frigo_armoire' => '']
                                ];
                                ?>

                                <div class="temp-grid">
                                    <div class="temp-section">
                                        <h4><i class="fas fa-sun"></i> MIDI</h4>
                                        <div class="temp-field">
                                            <label>🥤 Frigo Boissons</label>
                                            <input type="number" step="0.1" id="midi-boissons" value="<?= $todayTemps['midi']['frigo_boissons'] ?>">
                                            <small>Max: 4°C</small>
                                        </div>
                                        <div class="temp-field">
                                            <label>🧊 Frigo Blanc Principal</label>
                                            <input type="number" step="0.1" id="midi-blanc" value="<?= $todayTemps['midi']['frigo_blanc'] ?>">
                                            <small>Max: 4°C</small>
                                        </div>
                                        <div class="temp-field">
                                            <label>❄️ Congélateur</label>
                                            <input type="number" step="0.1" id="midi-congelateur" value="<?= $todayTemps['midi']['congelateur'] ?>">
                                            <small>Min: -18°C</small>
                                        </div>
                                        <div class="temp-field">
                                            <label>🚪 Frigo Armoire 4 Portes</label>
                                            <input type="number" step="0.1" id="midi-armoire" value="<?= $todayTemps['midi']['frigo_armoire'] ?>">
                                            <small>Max: 4°C</small>
                                        </div>
                                    </div>

                                    <div class="temp-section">
                                        <h4><i class="fas fa-moon"></i> SOIR</h4>
                                        <div class="temp-field">
                                            <label>🥤 Frigo Boissons</label>
                                            <input type="number" step="0.1" id="soir-boissons" value="<?= $todayTemps['soir']['frigo_boissons'] ?>">
                                            <small>Max: 4°C</small>
                                        </div>
                                        <div class="temp-field">
                                            <label>🧊 Frigo Blanc Principal</label>
                                            <input type="number" step="0.1" id="soir-blanc" value="<?= $todayTemps['soir']['frigo_blanc'] ?>">
                                            <small>Entre -18°C et -16</small>
                                        </div>
                                        <div class="temp-field">
                                            <label>❄️ Congélateur</label>
                                            <input type="number" step="0.1" id="soir-congelateur" value="<?= $todayTemps['soir']['congelateur'] ?>">
                                            <small>Entre -18°C et -16°C</small>
                                        </div>
                                        <div class="temp-field">
                                            <label>🚪 Frigo Armoire 4 Portes</label>
                                            <input type="number" step="0.1" id="soir-armoire" value="<?= $todayTemps['soir']['frigo_armoire'] ?>">
                                            <small>Max: 4°C</small>
                                        </div>
                                    </div>
                                </div>

                                <button class="btn-save" onclick="saveTemperatures()">
                                    <i class="fas fa-save"></i> Enregistrer les températures
                                </button>

                                <h3 style="margin-top: 40px;">📊 Historique des 7 derniers jours</h3>
                                <div id="temp-history"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            let inventory = <?= json_encode($inventoryData) ?>;
            let temperatures = <?= json_encode($temperatureData) ?>;

            // Gestion des onglets
            function switchTab(tabName, button) {
                document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
                
                button.classList.add('active');
                document.getElementById('tab-' + tabName).classList.add('active');
            }

            // === INVENTAIRE ===
            function loadInventory() {
                const container = document.getElementById('inventory-container');
                if (!inventory.inventory || inventory.inventory.length === 0) {
                    container.innerHTML = '<p style="text-align: center; color: #999; padding: 40px;">Aucun article dans l\'inventaire</p>';
                    return;
                }

                let html = '<table class="inventory-table"><thead><tr>';
                html += '<th>Article</th><th style="text-align: center;">Quantité</th><th style="text-align: center;">Unité</th><th style="text-align: center;">Actions</th>';
                html += '</tr></thead><tbody>';

                inventory.inventory.forEach((item, index) => {
                    html += `<tr>
                        <td><strong>${item.name}</strong></td>
                        <td style="text-align: center;">
                            <input type="number" value="${item.quantity}" onchange="updateQuantity(${index}, this.value)"
                                   style="width: 80px; padding: 5px; text-align: center; border: 1px solid #ddd; border-radius: 4px;">
                        </td>
                        <td style="text-align: center;">${item.unit}</td>
                        <td style="text-align: center;">
                            <button class="btn-delete" onclick="deleteItem(${index})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>`;
                });

                html += '</tbody></table>';
                container.innerHTML = html;
            }

            function addItem() {
                const name = document.getElementById('item-name').value.trim();
                const quantity = parseFloat(document.getElementById('item-quantity').value) || 0;
                const unit = document.getElementById('item-unit').value;

                if (!name) {
                    alert('❌ Veuillez entrer un nom d\'article');
                    return;
                }

                if (!inventory.inventory) inventory.inventory = [];
                
                inventory.inventory.push({
                    name: name,
                    quantity: quantity,
                    unit: unit,
                    addedDate: new Date().toISOString()
                });

                document.getElementById('item-name').value = '';
                document.getElementById('item-quantity').value = '0';
                
                loadInventory();
                alert('✅ Article ajouté !');
            }

            function updateQuantity(index, newQuantity) {
                inventory.inventory[index].quantity = parseFloat(newQuantity) || 0;
            }

            function deleteItem(index) {
                if (confirm('Supprimer cet article ?')) {
                    inventory.inventory.splice(index, 1);
                    loadInventory();
                }
            }

            function saveInventory() {
                inventory.lastUpdate = new Date().toISOString();
                
                fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(inventory)
                })
                .then(r => r.json())
                .then(data => {
                    alert(data.success ? '✅ Inventaire sauvegardé !' : '❌ Erreur: ' + data.message);
                })
                .catch(() => alert('❌ Erreur de sauvegarde'));
            }

            // === TEMPÉRATURES ===
            function saveTemperatures() {
                const today = '<?= $today ?>';
                
                const midi = {
                    frigo_boissons: parseFloat(document.getElementById('midi-boissons').value) || 0,
                    frigo_blanc: parseFloat(document.getElementById('midi-blanc').value) || 0,
                    congelateur: parseFloat(document.getElementById('midi-congelateur').value) || 0,
                    frigo_armoire: parseFloat(document.getElementById('midi-armoire').value) || 0
                };

                const soir = {
                    frigo_boissons: parseFloat(document.getElementById('soir-boissons').value) || 0,
                    frigo_blanc: parseFloat(document.getElementById('soir-blanc').value) || 0,
                    congelateur: parseFloat(document.getElementById('soir-congelateur').value) || 0,
                    frigo_armoire: parseFloat(document.getElementById('soir-armoire').value) || 0
                };

                let errors = [];
                ['frigo_boissons', 'frigo_blanc', 'frigo_armoire'].forEach(k => {
                    if (midi[k] > 4) errors.push(`MIDI ${k} > 4°C`);
                    if (soir[k] > 4) errors.push(`SOIR ${k} > 4°C`);
                });
                if (midi.congelateur < -18 || midi.congelateur > -16) errors.push('MIDI congélateur hors norme (-18°C à -16°C)');
                if (soir.congelateur < -18 || soir.congelateur > -16) errors.push('SOIR congélateur hors norme (-18°C à -16°C)');

                if (errors.length && !confirm('⚠️ Températures non conformes:\n' + errors.join('\n') + '\n\nEnregistrer quand même ?')) {
                    return;
                }

                if (!temperatures.temperatures) temperatures.temperatures = {};
                temperatures.temperatures[today] = {
                    midi: midi,
                    soir: soir,
                    savedAt: new Date().toISOString()
                };

                fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(temperatures)
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ Températures enregistrées !');
                        loadTempHistory();
                    } else {
                        alert('❌ Erreur: ' + data.message);
                    }
                })
                .catch(() => alert('❌ Erreur de sauvegarde'));
            }Actions</th>';
                html += '</tr></thead><tbody>';

                dates.forEach(date => {
                    const d = temperatures.temperatures[date];
                    const dateStr = new Date(date).toLocaleDateString('fr-FR');

                    html += `<tr>
                        <td rowspan="2"><strong>${dateStr}</strong></td>
                        <td><i class="fas fa-sun"></i> Midi</td>
                        <td>${d.midi.frigo_boissons.toFixed(1)}°C</td>
                        <td>${d.midi.frigo_blanc.toFixed(1)}°C</td>
                        <td>${d.midi.congelateur.toFixed(1)}°C</td>
                        <td>${d.midi.frigo_armoire.toFixed(1)}°C</td>
                        <td rowspan="2" style="text-align: center;">
                            <button class="btn-delete-temp" onclick="deleteTemperature('${date}')">
                                <i class="fas fa-trash"></i> Supprimer
                            </button>
                        
                dates.forEach(date => {
                    const d = temperatures.temperatures[date];
                    const auto = d.auto_filled ? '<span style="color: #856404;">🤖 Auto</span>' : '<span style="color: #28a745;">✅ Manuel</span>';
                    const dateStr = new Date(date).toLocaleDateString('fr-FR');

                    html += `<tr>
                        <td rowspan="2"><strong>${dateStr}</strong></td>
                        <td><i class="fas fa-sun"></i> Midi</td>
                        <td>${d.midi.frigo_boissons.toFixed(1)}°C</td>
                        <td>${d.midi.frigo_blanc.toFixed(1)}°C</td>
            function deleteTemperature(date) {
                if (!confirm(`Supprimer les températures du ${new Date(date).toLocaleDateString('fr-FR')} ?`)) {
                    return;
                }

                delete temperatures.temperatures[date];

                fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(temperatures)
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ Ligne supprimée !');
                        loadTempHistory();
                    } else {
                        alert('❌ Erreur: ' + data.message);
                    }
                })
                .catch(() => alert('❌ Erreur de suppression'));
            }

                        <td>${d.midi.congelateur.toFixed(1)}°C</td>
                        <td>${d.midi.frigo_armoire.toFixed(1)}°C</td>
                        <td rowspan="2">${auto}</td>
                    </tr><tr>
                        <td><i class="fas fa-moon"></i> Soir</td>
                        <td>${d.soir.frigo_boissons.toFixed(1)}°C</td>
                        <td>${d.soir.frigo_blanc.toFixed(1)}°C</td>
                        <td>${d.soir.congelateur.toFixed(1)}°C</td>
                        <td>${d.soir.frigo_armoire.toFixed(1)}°C</td>
                    </tr>`;
                });

                html += '</tbody></table>';
                container.innerHTML = html;
            }

            // Chargement initial
            document.addEventListener('DOMContentLoaded', function() {
                loadInventory();
                loadTempHistory();
            });
        </script>
    <?php endif; ?>
</body>
</html>
