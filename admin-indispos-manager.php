<?php
/**
 * Interface Admin - Gestion des Indisponibilités
 * Accès: https://www.pizzaclub.re/admin-indispos-manager.php
 */

// ========================================
// CONFIGURATION
// ========================================
define('ADMIN_PASSWORD', 'pizzaclub2025'); // 🔒 CHANGE CE MOT DE PASSE !
define('JSON_FILE', __DIR__ . '/unavailability.json');

session_start();

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
// GESTION DE LA SAUVEGARDE
// ========================================
if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier si c'est une sauvegarde (pas le login)
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        header('Content-Type: application/json');
        
        $rawInput = file_get_contents('php://input');
        error_log("📝 INDISPOS - Données reçues: " . $rawInput);
        
        $data = json_decode($rawInput, true);
        
        if ($data && isset($data['items']) && isset($data['ingredients'])) {
            $data['lastUpdate'] = date('c');
            
            // Initialiser la structure des fermetures si elle n'existe pas
            if (!isset($data['closures'])) {
                $data['closures'] = [
                    'emergency' => null,
                    'scheduled' => []
                ];
            }
            
            $jsonToSave = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $bytesWritten = file_put_contents(JSON_FILE, $jsonToSave, LOCK_EX);
            
            error_log("📝 INDISPOS - Octets écrits: " . $bytesWritten);
            error_log("📝 INDISPOS - Fichier: " . JSON_FILE);
            
            if ($bytesWritten !== false) {
                // Vérifier que le fichier a bien été écrit
                if (file_exists(JSON_FILE)) {
                    $verif = json_decode(file_get_contents(JSON_FILE), true);
                    error_log("📝 INDISPOS - Vérification: " . count($verif['items']) . " items, " . count($verif['ingredients']) . " ingredients");
                }
                echo json_encode(['success' => true, 'message' => 'Sauvegarde réussie (' . $bytesWritten . ' octets)']);
            } else {
                error_log("❌ INDISPOS - ERREUR écriture fichier");
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Erreur d\'écriture du fichier']);
            }
        } else {
            error_log("❌ INDISPOS - Données invalides reçues");
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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Gestion Indisponibilités | Pizza Club</title>
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
            font-size: 28px;
        }

        .login-container p {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .login-form input[type="password"] {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            margin-bottom: 10px;
            transition: border-color 0.3s;
        }

        .login-form input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }

        .login-form button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .login-form button:hover {
            transform: translateY(-2px);
        }

        .error-message {
            background: #fee;
            color: #c33;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
            font-size: 14px;
        }
        <?php else: ?>
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 25px 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #667eea;
            font-size: 32px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-actions {
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-save {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102,126,234,0.4);
        }

        .btn-logout {
            background: #f44336;
            color: white;
        }

        .btn-logout:hover {
            background: #d32f2f;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        .stat-icon.items { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-icon.ingredients { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-icon.last-update { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }

        .stat-info h3 {
            color: #333;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 5px;
        }

        .stat-info p {
            color: #667eea;
            font-size: 28px;
            font-weight: 700;
        }

        .tabs {
            background: white;
            padding: 20px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .tab-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 12px 24px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            color: #666;
        }

        .tab-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .product-card {
            background: #f9f9f9;
            border-radius: 15px;
            padding: 20px;
            transition: all 0.3s;
            border: 3px solid transparent;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .product-card.unavailable {
            border-color: #f44336;
            background: #ffebee;
        }

        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .product-info h3 {
            color: #333;
            font-size: 18px;
            margin-bottom: 5px;
        }

        .product-info p {
            color: #666;
            font-size: 13px;
        }

        .toggle-switch {
            position: relative;
            width: 60px;
            height: 30px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #4caf50;
            transition: .4s;
            border-radius: 30px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: #f44336;
        }

        input:checked + .slider:before {
            transform: translateX(30px);
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 10px;
        }

        .status-available {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-unavailable {
            background: #ffebee;
            color: #c62828;
        }

        .save-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 20px 25px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            display: none;
            align-items: center;
            gap: 15px;
            z-index: 1000;
        }

        .closure-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .closure-card h2 {
            color: #667eea;
            font-size: 24px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .closure-card p {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .closure-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group input[type="date"],
        .form-group input[type="time"],
        .form-group input[type="text"] {
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: border-color 0.3s;
            font-family: 'Poppins', sans-serif;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn-close-now {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            font-size: 16px;
        }

        .btn-close-now:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(240,147,251,0.4);
        }

        .btn-add-closure {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-size: 16px;
        }

        .btn-add-closure:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102,126,234,0.4);
        }

        .closures-list {
            margin-top: 25px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .closure-item {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 4px solid #667eea;
        }

        .closure-item.active {
            background: #fff3e0;
            border-left-color: #ff9800;
        }

        .closure-item.emergency {
            background: #ffebee;
            border-left-color: #f44336;
        }

        .closure-info {
            flex: 1;
        }

        .closure-info h4 {
            color: #333;
            font-size: 16px;
            margin-bottom: 5px;
        }

        .closure-info p {
            color: #666;
            font-size: 13px;
            margin: 0;
        }

        .closure-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-right: 10px;
        }

        .badge-active {
            background: #fff3e0;
            color: #f57c00;
        }

        .badge-future {
            background: #e3f2fd;
            color: #1976d2;
        }

        .badge-emergency {
            background: #ffebee;
            color: #d32f2f;
        }

        .btn-remove {
            background: #f44336;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-remove:hover {
            background: #d32f2f;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
            animation: slideIn 0.3s ease;
        }

        .save-notification.show {
            display: flex;
        }

        .save-notification.success {
            border-left: 5px solid #4caf50;
        }

        .save-notification.error {
            border-left: 5px solid #f44336;
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
            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }

            .header h1 {
                font-size: 24px;
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
        <h1><i class="fas fa-lock"></i> Accès Admin</h1>
        <p>Gestion des Indisponibilités - Pizza Club</p>
        
        <?php if (isset($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="login-form">
            <input type="password" name="password" placeholder="Mot de passe" required autofocus>
            <button type="submit">
                <i class="fas fa-sign-in-alt"></i> Se connecter
            </button>
        </form>
    </div>

<?php else: ?>
    <!-- INTERFACE ADMIN -->
    <div class="admin-container">
        <!-- Header -->
        <div class="header">
            <h1>
                <i class="fas fa-tools"></i>
                Gestion des Indisponibilités
            </h1>
            <div class="header-actions">
                <button class="btn btn-save" onclick="saveChanges()">
                    <i class="fas fa-save"></i> Sauvegarder
                </button>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="logout" class="btn btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Déconnexion
                    </button>
                </form>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-icon items">
                    <i class="fas fa-pizza-slice"></i>
                </div>
                <div class="stat-info">
                    <h3>Produits indisponibles</h3>
                    <p id="stat-items"><?= count($unavailabilityData['items']) ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon ingredients">
                    <i class="fas fa-carrot"></i>
                </div>
                <div class="stat-info">
                    <h3>Ingrédients indisponibles</h3>
                    <p id="stat-ingredients"><?= count($unavailabilityData['ingredients']) ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon last-update">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3>Dernière mise à jour</h3>
                    <p style="font-size: 14px;" id="stat-last-update">
                        <?= isset($unavailabilityData['lastUpdate']) ? date('d/m/Y H:i', strtotime($unavailabilityData['lastUpdate'])) : 'Jamais' ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Onglets -->
        <div class="tabs">
            <div class="tab-buttons">
                <button class="tab-btn active" onclick="switchTab('pizzas')">
                    <i class="fas fa-pizza-slice"></i> Pizzas
                </button>
                <button class="tab-btn" onclick="switchTab('pates')">
                    <i class="fas fa-utensils"></i> Pâtes
                </button>
                <button class="tab-btn" onclick="switchTab('salades')">
                    <i class="fas fa-leaf"></i> Salades
                </button>
                <button class="tab-btn" onclick="switchTab('desserts')">
                    <i class="fas fa-ice-cream"></i> Desserts
                </button>
                <button class="tab-btn" onclick="switchTab('ingredients')">
                    <i class="fas fa-carrot"></i> Ingrédients
                </button>
                <button class="tab-btn" onclick="switchTab('closures')">
                    <i class="fas fa-door-closed"></i> Fermetures
                </button>
            </div>

            <!-- Contenu des onglets -->
            <div id="tab-pizzas" class="tab-content active">
                <div class="products-grid" id="pizzas-grid"></div>
            </div>
            <div id="tab-pates" class="tab-content">
                <div class="products-grid" id="pates-grid"></div>
            </div>
            <div id="tab-salades" class="tab-content">
                <div class="products-grid" id="salades-grid"></div>
            </div>
            <div id="tab-desserts" class="tab-content">
                <div class="products-grid" id="desserts-grid"></div>
            </div>
            <div id="tab-ingredients" class="tab-content">
                <div class="products-grid" id="ingredients-grid"></div>
            </div>
            
            <!-- Onglet Fermetures -->
            <div id="tab-closures" class="tab-content">
                <!-- Fermeture anticipée -->
                <div class="closure-card emergency">
                    <h2><i class="fas fa-clock"></i> Fermeture Anticipée</h2>
                    <p>Fermer les commandes <strong>maintenant</strong> pour le reste de la journée (ex: départ anticipé, problème technique).</p>
                    <div class="closure-form">
                        <div class="form-group">
                            <label for="emergency-reason">Raison de la fermeture (optionnel)</label>
                            <input type="text" id="emergency-reason" placeholder="Ex: Départ anticipé, problème technique...">
                        </div>
                        <button class="btn btn-close-now" onclick="closeNow()">
                            <i class="fas fa-power-off"></i> Fermer les commandes maintenant
                        </button>
                    </div>
                </div>

                <!-- Fermetures programmées -->
                <div class="closure-card">
                    <h2><i class="fas fa-calendar-times"></i> Fermetures Programmées</h2>
                    <p>Programmer des fermetures à l'avance (ex: jours fériés, congés, événements spéciaux).</p>
                    <div class="closure-form">
                        <div class="form-group">
                            <label for="closure-date">Date de fermeture *</label>
                            <input type="date" id="closure-date" required>
                        </div>
                        <div class="form-group">
                            <label for="closure-reason">Raison de la fermeture *</label>
                            <input type="text" id="closure-reason" placeholder="Ex: Noël, Congés annuels, Événement spécial..." required>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label for="closure-start-time">Heure de début (optionnel)</label>
                                <input type="time" id="closure-start-time" placeholder="Laisser vide pour toute la journée">
                            </div>
                            <div class="form-group">
                                <label for="closure-end-time">Heure de fin (optionnel)</label>
                                <input type="time" id="closure-end-time" placeholder="Laisser vide pour toute la journée">
                            </div>
                        </div>
                        <button class="btn btn-add-closure" onclick="addScheduledClosure()">
                            <i class="fas fa-plus-circle"></i> Programmer cette fermeture
                        </button>
                    </div>

                    <!-- Liste des fermetures programmées -->
                    <div class="closures-list" id="closures-list">
                        <!-- Les fermetures seront ajoutées ici dynamiquement -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification -->
    <div id="save-notification" class="save-notification">
        <i class="fas fa-check-circle" style="font-size: 24px; color: #4caf50;"></i>
        <div>
            <strong>Sauvegarde réussie!</strong>
            <p style="font-size: 13px; color: #666; margin-top: 3px;">Les changements sont en ligne</p>
        </div>
    </div>

    <script src="data.js"></script>
    <script>
        // Données d'indisponibilité chargées depuis PHP
        let unavailability = <?= json_encode($unavailabilityData) ?>;
        
        // Initialiser les fermetures si elles n'existent pas
        if (!unavailability.closures) {
            unavailability.closures = {
                emergency: null, // Fermeture d'urgence en cours
                scheduled: []    // Fermetures programmées
            };
        }

        // Initialisation au chargement
        document.addEventListener('DOMContentLoaded', function() {
            loadPizzas();
            loadPates();
            loadSalades();
            loadDesserts();
            loadIngredients();
            loadClosures();
            
            // Définir la date minimale à aujourd'hui
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('closure-date').setAttribute('min', today);
        });

        // Chargement des pizzas
        function loadPizzas() {
            const grid = document.getElementById('pizzas-grid');
            grid.innerHTML = '';
            
            PIZZAS_DATA.forEach(pizza => {
                const id = `pizza-${pizza.id}`;
                const isUnavailable = unavailability.items[id] || false;
                
                grid.innerHTML += `
                    <div class="product-card ${isUnavailable ? 'unavailable' : ''}">
                        <div class="product-header">
                            <div class="product-info">
                                <h3>${pizza.name}</h3>
                                <p>ID: ${pizza.id} | ${pizza.price33}€</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" ${isUnavailable ? 'checked' : ''} 
                                       onchange="toggleItem('${id}', this.checked)">
                                <span class="slider"></span>
                            </label>
                        </div>
                        <span class="status-badge status-${isUnavailable ? 'unavailable' : 'available'}">
                            ${isUnavailable ? '❌ Indisponible' : '✅ Disponible'}
                        </span>
                    </div>
                `;
            });
        }

        // Chargement des pâtes
        function loadPates() {
            const grid = document.getElementById('pates-grid');
            grid.innerHTML = '';
            
            PATES_DATA.forEach(pate => {
                const id = `pate-${pate.id}`;
                const isUnavailable = unavailability.items[id] || false;
                
                grid.innerHTML += `
                    <div class="product-card ${isUnavailable ? 'unavailable' : ''}">
                        <div class="product-header">
                            <div class="product-info">
                                <h3>${pate.name}</h3>
                                <p>${pate.description}</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" ${isUnavailable ? 'checked' : ''} 
                                       onchange="toggleItem('${id}', this.checked)">
                                <span class="slider"></span>
                            </label>
                        </div>
                        <span class="status-badge status-${isUnavailable ? 'unavailable' : 'available'}">
                            ${isUnavailable ? '❌ Indisponible' : '✅ Disponible'}
                        </span>
                    </div>
                `;
            });
        }

        // Chargement des salades
        function loadSalades() {
            const grid = document.getElementById('salades-grid');
            grid.innerHTML = '';
            
            SALADES_DATA.forEach(salade => {
                const id = `salade-${salade.id}`;
                const isUnavailable = unavailability.items[id] || false;
                
                grid.innerHTML += `
                    <div class="product-card ${isUnavailable ? 'unavailable' : ''}">
                        <div class="product-header">
                            <div class="product-info">
                                <h3>${salade.name}</h3>
                                <p>${salade.description}</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" ${isUnavailable ? 'checked' : ''} 
                                       onchange="toggleItem('${id}', this.checked)">
                                <span class="slider"></span>
                            </label>
                        </div>
                        <span class="status-badge status-${isUnavailable ? 'unavailable' : 'available'}">
                            ${isUnavailable ? '❌ Indisponible' : '✅ Disponible'}
                        </span>
                    </div>
                `;
            });
        }

        // Chargement des desserts
        function loadDesserts() {
            const grid = document.getElementById('desserts-grid');
            grid.innerHTML = '';
            
            DESSERTS_DATA.forEach(dessert => {
                const id = `dessert-${dessert.id}`;
                const isUnavailable = unavailability.items[id] || false;
                
                grid.innerHTML += `
                    <div class="product-card ${isUnavailable ? 'unavailable' : ''}">
                        <div class="product-header">
                            <div class="product-info">
                                <h3>${dessert.name}</h3>
                                <p>${dessert.description} - ${dessert.price}€</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" ${isUnavailable ? 'checked' : ''} 
                                       onchange="toggleItem('${id}', this.checked)">
                                <span class="slider"></span>
                            </label>
                        </div>
                        <span class="status-badge status-${isUnavailable ? 'unavailable' : 'available'}">
                            ${isUnavailable ? '❌ Indisponible' : '✅ Disponible'}
                        </span>
                    </div>
                `;
            });
        }

        // Chargement des ingrédients
        function loadIngredients() {
            const grid = document.getElementById('ingredients-grid');
            grid.innerHTML = '';
            
            const ingredients = [
                // Fromages
                'mozzarella', 'chevre', 'emmental', 'roquefort', 'raclette', 'reblochon', 
                'cheddar', 'gorgonzola', 'parmesan',
                
                // Viandes
                'chorizo', 'jambon', 'merguez', 'poulet', 'poulet-fume', 'sarcive-poulet', 
                'saucisse-fumee', 'boeuf', 'lardons',
                
                // Produits de la mer
                'thon', 'anchois', 'crevettes', 'saumon',
                
                // Légumes
                'champignons', 'olives', 'poivrons', 'oignons', 'tomates', 
                'pommes-de-terre', 'mais', 'capres', 'gros-piment', 'salade',
                
                // Autres
                'oeuf', 'miel', 'creme', 'citron', 'ananas'
            ];
            
            // Noms d'affichage pour les ingrédients
            const ingredientNames = {
                // Fromages
                'mozzarella': 'Mozzarella', 'chevre': 'Chèvre', 'emmental': 'Emmental', 
                'roquefort': 'Roquefort', 'raclette': 'Raclette', 'reblochon': 'Reblochon',
                'cheddar': 'Cheddar', 'gorgonzola': 'Gorgonzola', 'parmesan': 'Parmesan',
                
                // Viandes
                'chorizo': 'Chorizo', 'jambon': 'Jambon/Épaule', 'merguez': 'Merguez poulet',
                'poulet': 'Poulet', 'poulet-fume': 'Poulet fumé', 'sarcive-poulet': 'Sarcive poulet',
                'saucisse-fumee': 'Saucisse fumée poulet', 'boeuf': 'Bœuf haché', 'lardons': 'Lardons',
                
                // Produits de la mer
                'thon': 'Thon', 'anchois': 'Anchois', 'crevettes': 'Crevettes', 'saumon': 'Saumon fumé',
                
                // Légumes
                'champignons': 'Champignons frais', 'olives': 'Olives', 'poivrons': 'Poivrons',
                'oignons': 'Oignons', 'tomates': 'Tomates fraîches', 'pommes-de-terre': 'Pommes de terre',
                'mais': 'Maïs', 'capres': 'Câpres', 'gros-piment': 'Gros piment', 'salade': 'Salade',
                
                // Autres
                'oeuf': 'Œuf', 'miel': 'Miel', 'creme': 'Crème fraîche', 'citron': 'Citron', 'ananas': 'Ananas Victoria'
            };
            
            ingredients.forEach(ingredient => {
                const isUnavailable = unavailability.ingredients[ingredient] || false;
                const displayName = ingredientNames[ingredient] || ingredient.charAt(0).toUpperCase() + ingredient.slice(1);
                
                grid.innerHTML += `
                    <div class="product-card ${isUnavailable ? 'unavailable' : ''}">
                        <div class="product-header">
                            <div class="product-info">
                                <h3>${displayName}</h3>
                                <p>Ingrédient</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" ${isUnavailable ? 'checked' : ''} 
                                       onchange="toggleIngredient('${ingredient}', this.checked)">
                                <span class="slider"></span>
                            </label>
                        </div>
                        <span class="status-badge status-${isUnavailable ? 'unavailable' : 'available'}">
                            ${isUnavailable ? '❌ Indisponible' : '✅ Disponible'}
                        </span>
                    </div>
                `;
            });
        }

        // Toggle produit
        function toggleItem(id, isUnavailable) {
            if (isUnavailable) {
                unavailability.items[id] = true;
            } else {
                delete unavailability.items[id];
            }
            updateStats();
        }

        // Toggle ingrédient
        function toggleIngredient(ingredient, isUnavailable) {
            if (isUnavailable) {
                unavailability.ingredients[ingredient] = true;
            } else {
                delete unavailability.ingredients[ingredient];
            }
            updateStats();
        }

        // Mise à jour des statistiques
        function updateStats() {
            document.getElementById('stat-items').textContent = Object.keys(unavailability.items).length;
            document.getElementById('stat-ingredients').textContent = Object.keys(unavailability.ingredients).length;
        }

        // Changement d'onglet
        function switchTab(tab) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            event.target.classList.add('active');
            document.getElementById(`tab-${tab}`).classList.add('active');
        }

        // Sauvegarde
        async function saveChanges() {
            try {
                const response = await fetch('<?= $_SERVER['PHP_SELF'] ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(unavailability)
                });

                if (response.ok) {
                    showNotification('success');
                    const now = new Date();
                    document.getElementById('stat-last-update').textContent = 
                        now.toLocaleDateString('fr-FR') + ' ' + now.toLocaleTimeString('fr-FR');
                } else {
                    showNotification('error');
                }
            } catch (error) {
                showNotification('error');
            }
        }

        // Affichage notification
        function showNotification(type) {
            const notif = document.getElementById('save-notification');
            notif.className = `save-notification ${type} show`;
            
            setTimeout(() => {
                notif.classList.remove('show');
            }, 3000);
        }

        // Fermeture anticipée (maintenant)
        function closeNow() {
            const reason = document.getElementById('emergency-reason').value || 'Fermeture anticipée';
            
            if (confirm('⚠️ Confirmer la fermeture des commandes MAINTENANT ?\n\nLes clients ne pourront plus commander pour aujourd\'hui.')) {
                const now = new Date();
                unavailability.closures.emergency = {
                    date: now.toISOString().split('T')[0],
                    time: now.toTimeString().split(' ')[0],
                    reason: reason,
                    timestamp: now.toISOString()
                };
                
                saveChanges();
                loadClosures();
                document.getElementById('emergency-reason').value = '';
                
                alert('✅ Commandes fermées avec succès !\n\nLes clients verront un message indiquant que le restaurant est fermé.');
            }
        }

        // Ajouter une fermeture programmée
        function addScheduledClosure() {
            const date = document.getElementById('closure-date').value;
            const reason = document.getElementById('closure-reason').value;
            const startTime = document.getElementById('closure-start-time').value;
            const endTime = document.getElementById('closure-end-time').value;
            
            if (!date || !reason) {
                alert('❌ Veuillez remplir au moins la date et la raison de la fermeture.');
                return;
            }
            
            // Vérifier si une fermeture existe déjà pour cette date
            const existingClosure = unavailability.closures.scheduled.find(c => c.date === date);
            if (existingClosure) {
                if (!confirm('⚠️ Une fermeture existe déjà pour cette date.\n\nVoulez-vous la remplacer ?')) {
                    return;
                }
                // Supprimer l'ancienne fermeture
                unavailability.closures.scheduled = unavailability.closures.scheduled.filter(c => c.date !== date);
            }
            
            const closure = {
                id: Date.now(),
                date: date,
                reason: reason,
                startTime: startTime || null,
                endTime: endTime || null,
                fullDay: !startTime && !endTime,
                createdAt: new Date().toISOString()
            };
            
            unavailability.closures.scheduled.push(closure);
            unavailability.closures.scheduled.sort((a, b) => new Date(a.date) - new Date(b.date));
            
            saveChanges();
            loadClosures();
            
            // Réinitialiser le formulaire
            document.getElementById('closure-date').value = '';
            document.getElementById('closure-reason').value = '';
            document.getElementById('closure-start-time').value = '';
            document.getElementById('closure-end-time').value = '';
            
            alert('✅ Fermeture programmée avec succès !');
        }

        // Supprimer une fermeture programmée
        function removeScheduledClosure(id) {
            if (confirm('❌ Supprimer cette fermeture programmée ?')) {
                unavailability.closures.scheduled = unavailability.closures.scheduled.filter(c => c.id !== id);
                saveChanges();
                loadClosures();
            }
        }

        // Réactiver les commandes (annuler fermeture d'urgence)
        function reopenNow() {
            if (confirm('✅ Réactiver les commandes maintenant ?')) {
                unavailability.closures.emergency = null;
                saveChanges();
                loadClosures();
                alert('✅ Commandes réactivées avec succès !');
            }
        }

        // Charger et afficher les fermetures
        function loadClosures() {
            const list = document.getElementById('closures-list');
            const today = new Date().toISOString().split('T')[0];
            
            list.innerHTML = '';
            
            // Afficher la fermeture d'urgence si active
            if (unavailability.closures.emergency) {
                const emergency = unavailability.closures.emergency;
                const emergencyDate = new Date(emergency.timestamp);
                
                list.innerHTML += `
                    <div class="closure-item emergency">
                        <div class="closure-info">
                            <h4>🚨 FERMETURE D'URGENCE EN COURS</h4>
                            <p><strong>Depuis:</strong> ${emergencyDate.toLocaleDateString('fr-FR')} à ${emergency.time}</p>
                            <p><strong>Raison:</strong> ${emergency.reason}</p>
                        </div>
                        <span class="closure-badge badge-emergency">ACTIF</span>
                        <button class="btn btn-remove" onclick="reopenNow()">
                            <i class="fas fa-check"></i> Réactiver
                        </button>
                    </div>
                `;
            }
            
            // Afficher les fermetures programmées
            if (unavailability.closures.scheduled && unavailability.closures.scheduled.length > 0) {
                unavailability.closures.scheduled.forEach(closure => {
                    const closureDate = new Date(closure.date);
                    const isToday = closure.date === today;
                    const isPast = closure.date < today;
                    
                    if (isPast) return; // Ne pas afficher les fermetures passées
                    
                    const timeInfo = closure.fullDay 
                        ? 'Toute la journée' 
                        : `De ${closure.startTime || '00:00'} à ${closure.endTime || '23:59'}`;
                    
                    list.innerHTML += `
                        <div class="closure-item ${isToday ? 'active' : ''}">
                            <div class="closure-info">
                                <h4>${closure.reason}</h4>
                                <p><strong>Date:</strong> ${closureDate.toLocaleDateString('fr-FR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</p>
                                <p><strong>Horaires:</strong> ${timeInfo}</p>
                            </div>
                            <span class="closure-badge ${isToday ? 'badge-active' : 'badge-future'}">
                                ${isToday ? 'AUJOURD\'HUI' : 'À VENIR'}
                            </span>
                            <button class="btn btn-remove" onclick="removeScheduledClosure(${closure.id})">
                                <i class="fas fa-trash"></i> Supprimer
                            </button>
                        </div>
                    `;
                });
            }
            
            // Message si aucune fermeture
            if (list.innerHTML === '') {
                list.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-calendar-check"></i>
                        <p>Aucune fermeture programmée</p>
                    </div>
                `;
            }
        }
    </script>
<?php endif; ?>

</body>
</html>
