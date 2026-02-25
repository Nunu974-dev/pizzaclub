<?php
/**
 * API gestion des codes promo - Pizza Club
 * GET  → liste tous les codes
 * POST action=create  → crée un code
 * POST action=delete  → supprime un code
 * POST action=toggle  → active/désactive un code
 */
date_default_timezone_set('Indian/Reunion');
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

$FILE = __DIR__ . '/promo-codes.json';

function loadCodes($file) {
    if (!file_exists($file)) return ['codes' => []];
    $data = json_decode(file_get_contents($file), true);
    return $data ?: ['codes' => []];
}

function saveCodes($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$method = $_SERVER['REQUEST_METHOD'];
$data = loadCodes($FILE);

// ── GET : liste ─────────────────────────────────────────────
if ($method === 'GET') {
    echo json_encode($data);
    exit;
}

// ── POST : actions ───────────────────────────────────────────
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    // CRÉER
    if ($action === 'create') {
        $code = strtoupper(trim($input['code'] ?? ''));
        if (empty($code)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Code vide']);
            exit;
        }
        foreach ($data['codes'] as $c) {
            if ($c['code'] === $code) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Ce code existe déjà']);
                exit;
            }
        }
        $type = in_array($input['type'] ?? '', ['percent', 'euros']) ? $input['type'] : 'euros';
        $newCode = [
            'id'           => uniqid('p', true),
            'code'         => $code,
            'description'  => trim($input['description'] ?? ''),
            'type'         => $type,
            'value'        => (float)($input['value'] ?? 0),
            'minOrder'     => (float)($input['minOrder'] ?? 0),
            'limitedUses'  => (bool)($input['limitedUses'] ?? false),
            'maxUses'      => (int)($input['maxUses'] ?? 0),
            'usedCount'    => 0,
            'startDate'    => !empty($input['startDate']) ? $input['startDate'] : null,
            'endDate'      => !empty($input['endDate'])   ? $input['endDate']   : null,
            'active'       => true,
            'createdAt'    => date('c'),
        ];
        $data['codes'][] = $newCode;
        saveCodes($FILE, $data);
        echo json_encode(['success' => true, 'code' => $newCode]);
        exit;
    }

    // SUPPRIMER
    if ($action === 'delete') {
        $id = $input['id'] ?? '';
        $data['codes'] = array_values(array_filter($data['codes'], fn($c) => $c['id'] !== $id));
        saveCodes($FILE, $data);
        echo json_encode(['success' => true]);
        exit;
    }

    // ACTIVER / DÉSACTIVER
    if ($action === 'toggle') {
        $id = $input['id'] ?? '';
        foreach ($data['codes'] as &$c) {
            if ($c['id'] === $id) { $c['active'] = !$c['active']; break; }
        }
        saveCodes($FILE, $data);
        echo json_encode(['success' => true]);
        exit;
    }

    // INCRÉMENTER usedCount (appelé depuis send-order.php)
    if ($action === 'use') {
        $code = strtoupper(trim($input['code'] ?? ''));
        foreach ($data['codes'] as &$c) {
            if ($c['code'] === $code) { $c['usedCount']++; break; }
        }
        saveCodes($FILE, $data);
        echo json_encode(['success' => true]);
        exit;
    }
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Action inconnue']);
