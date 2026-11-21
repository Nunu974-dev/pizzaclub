<?php
/**
 * Jorders — résumé hebdomadaire par e-mail (passerelle Brevo).
 * Appelée chaque dimanche soir par la base (pg_cron + pg_net) : POST JSON {"slug", "from", "to"}.
 * Les chiffres viennent de la base (weekly_report_claim, secret partagé, une seule édition par semaine) :
 * l'appelant ne choisit ni les données ni le destinataire.
 */
header('Content-Type: application/json; charset=utf-8');

function out($code, $data)
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    out(405, ['ok' => false]);
}
$in = json_decode(file_get_contents('php://input') ?: '{}', true);
$slug = is_array($in) ? ($in['slug'] ?? '') : '';
$from = is_array($in) ? ($in['from'] ?? '') : '';
$to = is_array($in) ? ($in['to'] ?? '') : '';
if (!preg_match('/^[a-z0-9-]{2,40}$/', $slug) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    out(400, ['ok' => false, 'error' => 'Paramètres invalides']);
}

$pco = @include __DIR__ . '/config/pco-config.php';
$brevo = @include __DIR__ . '/config/brevo-config.php';
if (!is_array($pco) || empty($pco['supabase_url']) || empty($pco['publishable_key']) || empty($pco['sms_secret'])) {
    out(500, ['ok' => false, 'error' => 'Configuration caisse manquante']);
}
if (!is_array($brevo) || empty($brevo['api_key'])) {
    out(500, ['ok' => false, 'error' => 'Configuration Brevo manquante']);
}

$ch = curl_init(rtrim($pco['supabase_url'], '/') . '/rest/v1/rpc/weekly_report_claim');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'apikey: ' . $pco['publishable_key']],
    CURLOPT_POSTFIELDS => json_encode(['p_secret' => $pco['sms_secret'], 'p_slug' => $slug, 'p_from' => $from, 'p_to' => $to]),
]);
$res = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($status !== 200) {
    out(403, ['ok' => false, 'error' => 'Refusé par la base']);
}
$d = json_decode($res ?: 'null', true);
if (!is_array($d)) {
    out(200, ['ok' => true, 'skipped' => true]);   // déjà envoyé cette semaine
}
$to_email = $d['restaurant']['email'] ?? '';
if (!filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
    out(422, ['ok' => false, 'error' => 'Adresse e-mail du restaurant manquante']);
}

function e($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
function eur($c)
{
    return number_format(((int) $c) / 100, 2, ',', ' ') . ' €';
}
function dfr($iso)
{
    $t = strtotime((string) $iso);
    return $t ? date('d/m/Y', $t) : '';
}
function jour($iso)
{
    $j = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
    $t = strtotime((string) $iso);
    return $t ? $j[(int) date('w', $t)] . ' ' . date('d/m', $t) : '';
}
/** Évolution par rapport à la semaine précédente */
function evol($now, $prev)
{
    if ((int) $prev <= 0) {
        return '';
    }
    $p = round((($now - $prev) / $prev) * 100);
    return ' <span style="color:' . ($p >= 0 ? '#2E7D32' : '#C62828') . ';font-size:12px">' . ($p >= 0 ? '+' : '') . $p . ' % vs semaine précédente</span>';
}
$methods = ['card' => 'Carte bancaire', 'cash' => 'Espèces', 'check' => 'Chèque', 'meal_voucher' => 'Titres-restaurant', 'online' => 'Paiement en ligne',
    'platform' => 'Plateformes', 'other' => 'Autre', 'account' => 'En compte (crédit)'];
$modes = ['dine_in' => 'Sur place', 'takeaway' => 'À emporter', 'delivery' => 'Livraison'];

$td = 'style="padding:5px 4px;border-bottom:1px solid #eee"';
$tdr = 'style="padding:5px 4px;border-bottom:1px solid #eee;text-align:right;white-space:nowrap"';
$h = 'style="margin:22px 0 6px;font-size:16px;color:#222"';
$w = $d['week'];
$p = $d['previous_week'];
$t = $d['tickets'];

$body = '<div style="text-align:center"><div style="font-size:22px;font-weight:800">' . e($d['restaurant']['name']) . '</div>'
    . '<div style="color:#555">Résumé de la semaine du ' . e(dfr($d['from'])) . ' au ' . e(dfr($d['to'])) . '</div></div>';
if (!empty($d['restaurant']['is_training'])) {
    $body .= '<p style="background:#ef8a17;color:#fff;padding:6px;text-align:center;font-weight:700">MODE TEST</p>';
}
$body .= '<div style="background:#f7f6f3;border-radius:10px;padding:14px;margin-top:14px;text-align:center">'
    . '<div style="font-size:13px;color:#555">Chiffre d\'affaires TTC</div>'
    . '<div style="font-size:32px;font-weight:800">' . eur($w['revenue_cents']) . '</div>'
    . '<div>' . evol($w['revenue_cents'], $p['revenue_cents']) . '</div>'
    . '<div style="margin-top:6px;font-size:14px">' . (int) $w['orders'] . ' commandes · ticket moyen ' . eur($w['avg_cents']) . '</div></div>';

// jour par jour
$rows = '';
foreach ($d['daily'] as $x) {
    $rows .= "<tr><td $td>" . e(jour($x['date'])) . "</td><td $tdr>" . (int) $x['orders'] . " cmd</td><td $tdr>" . eur($x['revenue_cents']) . '</td></tr>';
}
$body .= "<h3 $h>Jour par jour</h3><table style=\"width:100%;border-collapse:collapse;font-size:14px\">" . ($rows ?: "<tr><td $td>Aucune vente</td></tr>") . '</table>';

// type de commande
$rows = '';
foreach ($d['by_mode'] as $k => $x) {
    $rows .= "<tr><td $td>" . e($modes[$k] ?? $k) . "</td><td $tdr>" . (int) $x['orders'] . " cmd</td><td $tdr>" . eur($x['revenue_cents']) . '</td></tr>';
}
$body .= "<h3 $h>Par type de commande</h3><table style=\"width:100%;border-collapse:collapse;font-size:14px\">" . $rows . '</table>'
    . '<div style="font-size:13px;color:#555;margin-top:4px">dont commandes du site internet : ' . (int) $w['web'] . '</div>';

// TVA
$rows = '';
foreach ($d['vat'] as $v) {
    $rows .= "<tr><td $td>TVA " . number_format($v['rate_bp'] / 100, 1, ',', '') . " %</td><td $tdr>HT " . eur($v['ht_cents']) . "</td><td $tdr>TVA " . eur($v['tax_cents']) . '</td></tr>';
}
$body .= "<h3 $h>TVA</h3><table style=\"width:100%;border-collapse:collapse;font-size:14px\">" . $rows
    . "<tr><td $td><b>Total</b></td><td $tdr><b>HT " . eur($t['ht_cents']) . "</b></td><td $tdr><b>TVA " . eur($t['tax_cents']) . '</b></td></tr></table>'
    . '<div style="font-size:12px;color:#777;margin-top:4px">Total TTC des tickets : ' . eur($t['ttc_cents']) . ' (' . (int) $t['count'] . ' tickets)</div>';

// paiements
$rows = '';
foreach ($d['payments'] as $k => $a) {
    $rows .= "<tr><td $td>" . e($methods[$k] ?? $k) . "</td><td $tdr>" . eur($a) . '</td></tr>';
}
$body .= "<h3 $h>Encaissements par moyen de paiement</h3><table style=\"width:100%;border-collapse:collapse;font-size:14px\">" . $rows . '</table>';

// produits
$rows = '';
foreach ($d['top_products'] as $x) {
    $rows .= "<tr><td $td>" . e($x['name']) . "</td><td $tdr>" . (int) $x['quantity'] . " ×</td><td $tdr>" . eur($x['revenue_cents']) . '</td></tr>';
}
$body .= "<h3 $h>Produits les plus vendus</h3><table style=\"width:100%;border-collapse:collapse;font-size:14px\">" . $rows . '</table>';

// à surveiller
$body .= "<h3 $h>À surveiller</h3><div style=\"font-size:14px;line-height:1.6\">"
    . 'Commandes annulées : ' . (int) $d['cancelled']['count'] . ' (' . eur($d['cancelled']['amount_cents']) . ')<br>'
    . 'Commandes non réglées : ' . (int) $d['unpaid']['count'] . ' (' . eur($d['unpaid']['amount_cents']) . ')<br>'
    . 'Remises accordées : ' . eur($d['discounts_cents']) . '</div>';

// crédits clients
$total = 0;
$rows = '';
foreach ($d['credits'] as $c) {
    $total += (int) $c['balance_cents'];
    $old = !empty($c['oldest_open']) ? ' · depuis le ' . dfr($c['oldest_open']) : '';
    $rows .= "<tr><td $td>" . e($c['name']) . '<div style="color:#777;font-size:12px">' . e($c['phone'] ?? '') . ' · ' . (int) $c['open_count'] . ' commande(s)' . e($old) . "</div></td><td $tdr><b>" . eur($c['balance_cents']) . '</b></td></tr>';
}
$body .= "<h3 $h>Crédits clients encore dus : " . eur($total) . '</h3>'
    . ($rows ? "<table style=\"width:100%;border-collapse:collapse;font-size:14px\">" . $rows . '</table>' : '<div style="font-size:14px">Aucun crédit en cours.</div>');

$html = '<!doctype html><html><body style="font-family:Arial,sans-serif;background:#f5f4f1;padding:16px">'
    . '<div style="max-width:600px;margin:auto;background:#fff;border-radius:12px;padding:20px">' . $body
    . '<p style="color:#999;font-size:11px;margin-top:24px">Résumé automatique Jorders, envoyé chaque dimanche soir.</p></div></body></html>';

$mail = [
    'sender' => ['name' => $d['restaurant']['name'], 'email' => 'commande@pizzaclub.re'],
    'to' => [['email' => $to_email]],
    'subject' => $d['restaurant']['name'] . ' — résumé de la semaine du ' . dfr($d['from']) . ' au ' . dfr($d['to']) . ' · CA ' . eur($w['revenue_cents']),
    'htmlContent' => $html,
];
$ch = curl_init('https://api.brevo.com/v3/smtp/email');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'accept: application/json', 'api-key: ' . $brevo['api_key']],
    CURLOPT_POSTFIELDS => json_encode($mail),
]);
$res = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($status < 200 || $status >= 300) {
    error_log('pco-weekly Brevo ' . $status . ' ' . $res);
    out(502, ['ok' => false, 'error' => "L'envoi de l'e-mail a échoué"]);
}
out(200, ['ok' => true, 'to' => $to_email]);
