<?php
/**
 * Valide un code promo côté serveur
 * POST { code, subtotal } → { valid, discount, type, value, label, message, id }
 */
date_default_timezone_set('Indian/Reunion');
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['valid' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$FILE = __DIR__ . '/promo-codes.json';
$input    = json_decode(file_get_contents('php://input'), true);
$code     = strtoupper(trim($input['code'] ?? ''));
$subtotal = (float)($input['subtotal'] ?? 0);
$today    = date('Y-m-d');

if (!$code) {
    echo json_encode(['valid' => false, 'message' => 'Veuillez entrer un code promo.']);
    exit;
}

if (!file_exists($FILE)) {
    echo json_encode(['valid' => false, 'message' => 'Code promo invalide.']);
    exit;
}

$data  = json_decode(file_get_contents($FILE), true);
$codes = $data['codes'] ?? [];

foreach ($codes as $c) {
    if ($c['code'] !== $code) continue;

    if (!$c['active']) {
        echo json_encode(['valid' => false, 'message' => 'Ce code promo est désactivé.']);
        exit;
    }
    if ($c['startDate'] && $today < $c['startDate']) {
        echo json_encode(['valid' => false, 'message' => 'Ce code promo n\'est pas encore actif.']);
        exit;
    }
    if ($c['endDate'] && $today > $c['endDate']) {
        echo json_encode(['valid' => false, 'message' => 'Ce code promo a expiré.']);
        exit;
    }
    if ($c['limitedUses'] && $c['usedCount'] >= $c['maxUses']) {
        echo json_encode(['valid' => false, 'message' => 'Ce code a atteint son nombre maximum d\'utilisations.']);
        exit;
    }
    if ($subtotal < $c['minOrder']) {
        $min = number_format($c['minOrder'], 2, ',', '');
        echo json_encode(['valid' => false, 'message' => "Minimum de commande {$min}€ requis pour ce code."]);
        exit;
    }

    // Calcul remise
    if ($c['type'] === 'percent') {
        $discount = round($subtotal * $c['value'] / 100, 2);
        $label    = '-' . $c['value'] . '%';
    } else {
        $discount = $c['value'];
        $label    = '-' . number_format($c['value'], 2, ',', '') . '€';
    }
    $discount = min($discount, $subtotal);

    echo json_encode([
        'valid'    => true,
        'id'       => $c['id'],
        'code'     => $code,
        'discount' => $discount,
        'type'     => $c['type'],
        'value'    => $c['value'],
        'label'    => $label,
        'message'  => "Code promo appliqué ! {$label} sur votre commande",
    ]);
    exit;
}

echo json_encode(['valid' => false, 'message' => 'Code promo invalide.']);
