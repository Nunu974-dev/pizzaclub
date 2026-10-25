<?php
/**
 * Jorders — passerelle SMS (Brevo).
 * Appelée par la base (pg_net) avec l'identifiant d'un SMS mis en file par la caisse
 * (commande prête, livreur parti…). La passerelle ne choisit ni le numéro ni le texte :
 * elle prend en charge le SMS auprès de la base avec un secret partagé, l'envoie, puis donne le résultat.
 * POST JSON : {"id": "<uuid du SMS>"}
 */
header('Content-Type: application/json; charset=utf-8');

function out($code, $data)
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    out(405, ['ok' => false]);
}
$in = json_decode(file_get_contents('php://input') ?: '{}', true);
$id = is_array($in) ? ($in['id'] ?? '') : '';
if (!preg_match('/^[0-9a-f-]{36}$/i', $id)) {
    out(400, ['ok' => false, 'error' => 'Identifiant invalide']);
}

$pco = @include __DIR__ . '/config/pco-config.php';
$brevo = @include __DIR__ . '/config/brevo-config.php';
if (!is_array($pco) || empty($pco['supabase_url']) || empty($pco['publishable_key']) || empty($pco['sms_secret'])) {
    out(500, ['ok' => false, 'error' => 'Configuration caisse manquante']);
}
if (!is_array($brevo) || empty($brevo['api_key'])) {
    out(500, ['ok' => false, 'error' => 'Configuration Brevo manquante']);
}

/** Appel d'une fonction de la base (rôle anonyme : seules sms_claim / sms_result sont permises) */
function rpc($pco, $fn, $params)
{
    $ch = curl_init(rtrim($pco['supabase_url'], '/') . '/rest/v1/rpc/' . $fn);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'apikey: ' . $pco['publishable_key']],
        CURLOPT_POSTFIELDS => json_encode($params),
    ]);
    $res = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$status, json_decode($res ?: 'null', true)];
}

[$status, $sms] = rpc($pco, 'sms_claim', ['p_id' => $id, 'p_secret' => $pco['sms_secret']]);
if ($status !== 200) {
    out(403, ['ok' => false, 'error' => 'Refusé par la base']);
}
if (!is_array($sms)) {
    out(200, ['ok' => true, 'skipped' => true]);   // déjà envoyé ou introuvable
}

$ch = curl_init('https://api.brevo.com/v3/transactionalSMS/sms');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_HTTPHEADER => ['api-key: ' . $brevo['api_key'], 'Content-Type: application/json', 'Accept: application/json'],
    CURLOPT_POSTFIELDS => json_encode([
        'sender' => substr(preg_replace('/[^A-Za-z0-9]/', '', $sms['sender']), 0, 11) ?: 'Jorders',
        'recipient' => $sms['phone'],
        'content' => $sms['content'],
        'type' => 'transactional',
        'unicodeEnabled' => true,
    ]),
]);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);
$body = json_decode($res ?: 'null', true);
$ok = $code === 201 || $code === 200;

rpc($pco, 'sms_result', [
    'p_id' => $id,
    'p_secret' => $pco['sms_secret'],
    'p_ok' => $ok,
    'p_error' => $ok ? null : ($body['message'] ?? ($err ?: 'Erreur Brevo ' . $code)),
    'p_reference' => $ok ? (string) ($body['messageId'] ?? $body['reference'] ?? '') : null,
]);
out(200, ['ok' => $ok]);
