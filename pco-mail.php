<?php
/**
 * Envoi par e-mail depuis la caisse PizzaClubOrders : ticket de caisse, relevé de compte client.
 *
 * La caisse envoie { type, id, month?, to?, access_token } où access_token est la session Supabase
 * de l'appareil. Les données sont lues dans la base avec CETTE session (mail_payload) : seul un
 * appareil connecté au restaurant peut envoyer, et uniquement des contenus générés par la base.
 * Envoi via l'API Brevo (même compte que les commandes du site).
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function fail($code, $message)
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail(405, 'Méthode non autorisée');
}

$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) {
    fail(400, 'Requête invalide');
}
$type = $in['type'] ?? '';
$id = $in['id'] ?? '';
$token = $in['access_token'] ?? '';
// day_report : récapitulatif de la journée pour le gérant (pas d'identifiant, la date est dans « month »)
if (!in_array($type, ['receipt', 'statement', 'day_report', 'invoice'], true) || $token === ''
    || ($type !== 'day_report' && !preg_match('/^[0-9a-f-]{36}$/i', $id))) {
    fail(400, 'Paramètres manquants');
}
if ($type === 'day_report') {
    $id = null;
}

$pco = @include __DIR__ . '/config/pco-config.php';
$brevo = @include __DIR__ . '/config/brevo-config.php';
if (!is_array($pco) || empty($pco['supabase_url']) || empty($pco['publishable_key'])) {
    fail(500, 'Configuration caisse manquante sur le serveur');
}
if (!is_array($brevo) || empty($brevo['api_key'])) {
    fail(500, 'Configuration e-mail (Brevo) manquante sur le serveur');
}

// --- Données préparées par la base, avec la session de l'appareil
$ch = curl_init(rtrim($pco['supabase_url'], '/') . '/rest/v1/rpc/mail_payload');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'apikey: ' . $pco['publishable_key'],
        'Authorization: Bearer ' . $token,
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'p_type' => $type,
        'p_id' => $id,
        'p_month' => $in['month'] ?? null,
        'p_to' => $in['to'] ?? null,
    ]),
]);
$res = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$payload = json_decode($res ?: 'null', true);
if ($status !== 200 || !is_array($payload)) {
    fail($status === 401 ? 401 : 400, $payload['message'] ?? 'Données introuvables');
}

// --- Mise en forme
function e($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
function eur($cents)
{
    return number_format(((int) $cents) / 100, 2, ',', ' ') . ' €';
}
function dfr($iso)
{
    $t = strtotime((string) $iso);
    return $t ? date('d/m/Y', $t) : '';
}
$methods = ['card' => 'CB', 'cash' => 'Espèces', 'check' => 'Chèque', 'meal_voucher' => 'Titre-restaurant', 'online' => 'Paiement en ligne',
    'platform' => 'Plateforme', 'other' => 'Autre', 'account' => 'En compte'];

$r = $payload['restaurant'];
$header = '<div style="text-align:center;margin-bottom:16px">'
    . '<div style="font-size:22px;font-weight:800">' . e($r['name']) . '</div>'
    . '<div style="color:#555;font-size:13px">' . e(trim(($r['legal_name'] ?? '') . ' · ' . ($r['address'] ?? '') . ' ' . ($r['postal_code'] ?? '') . ' ' . ($r['city'] ?? ''), ' ·')) . '</div>'
    . (!empty($r['siret']) ? '<div style="color:#555;font-size:12px">SIRET ' . e($r['siret']) . (!empty($r['vat_number']) ? ' · TVA ' . e($r['vat_number']) : '') . '</div>' : '')
    . '</div>';
$test = !empty($r['is_training']) ? '<p style="background:#ef8a17;color:#fff;padding:6px;text-align:center;font-weight:700">MODE TEST — document sans valeur</p>' : '';
$td = 'style="padding:6px 4px;border-bottom:1px solid #eee"';
$tdr = 'style="padding:6px 4px;border-bottom:1px solid #eee;text-align:right;white-space:nowrap"';

if ($type === 'receipt') {
    $o = $payload['data']['order'];
    $t = $payload['data']['tickets'][0] ?? null;
    $rows = '';
    foreach ($o['items'] ?? [] as $i) {
        $name = $i['quantity'] . ' × ' . $i['name'] . (!empty($i['variant_name']) ? ' ' . $i['variant_name'] : '');
        $details = !empty($i['details']) ? '<div style="color:#777;font-size:12px">' . e(implode(' · ', $i['details'])) . '</div>' : '';
        $rows .= "<tr><td $td>" . e($name) . $details . "</td><td $tdr>" . eur($i['line_total_cents']) . '</td></tr>';
    }
    if (($o['delivery_fee_cents'] ?? 0) > 0) {
        $rows .= "<tr><td $td>Frais de livraison</td><td $tdr>" . eur($o['delivery_fee_cents']) . '</td></tr>';
    }
    if (($o['discount_cents'] ?? 0) > 0) {
        $rows .= "<tr><td $td>Remise</td><td $tdr>-" . eur($o['discount_cents']) . '</td></tr>';
    }
    $pays = [];
    foreach ($o['payments'] ?? [] as $p) {
        $pays[] = ($p['label'] ?? $methods[$p['method']] ?? $p['method']) . ' ' . eur($p['amount_cents']);
    }
    $vat = '';
    foreach (($t['tax_breakdown'] ?? []) as $v) {
        $vat .= '<div>TVA ' . number_format($v['rate_bp'] / 100, 1, ',', '') . ' % : ' . eur($v['tva']) . ' (HT ' . eur($v['ht']) . ')</div>';
    }
    $subject = $r['name'] . ' — votre ticket n° ' . ($t ? str_pad($t['seq'], 6, '0', STR_PAD_LEFT) : '#' . $o['number']);
    $body = $test . $header
        . '<p>Commande <b>#' . e($o['number']) . '</b> du ' . e(dfr($o['created_at'])) . ($t ? ' · ticket n° ' . e(str_pad($t['seq'], 6, '0', STR_PAD_LEFT)) : '') . '</p>'
        . '<table style="width:100%;border-collapse:collapse;font-size:14px">' . $rows
        . '<tr><td style="padding:10px 4px;font-weight:800;font-size:16px">TOTAL TTC</td><td style="padding:10px 4px;font-weight:800;font-size:16px;text-align:right">' . eur($o['total_cents']) . '</td></tr></table>'
        . '<div style="font-size:13px;color:#555;margin-top:8px">' . $vat . ($pays ? '<div>Réglé : ' . e(implode(' + ', $pays)) . '</div>' : '<div>Non réglé</div>') . '</div>'
        . ($t ? '<p style="font-size:11px;color:#999">Signature ' . e(strtoupper(substr($t['hash'] ?? '', 0, 16))) . '</p>' : '')
        . '<p>Merci et à bientôt !</p>';
} elseif ($type === 'invoice') {
    // facture (numérotée, inaltérable) envoyée au client
    $inv = $payload['data']['invoice'];
    $cu = $inv['customer'] ?? [];
    $rows = '';
    foreach ($inv['lines'] ?? [] as $l) {
        $name = $l['qty'] . ' × ' . $l['name'] . (!empty($l['variant']) ? ' ' . $l['variant'] : '');
        $rows .= "<tr><td $td>" . e($name) . ' <span style="color:#777;font-size:12px">TVA ' . number_format(($l['tax_bp'] ?? 0) / 100, 1, ',', '') . " %</span></td><td $tdr>" . eur($l['total']) . '</td></tr>';
    }
    if (($inv['delivery_fee_cents'] ?? 0) > 0) {
        $rows .= "<tr><td $td>Frais de livraison</td><td $tdr>" . eur($inv['delivery_fee_cents']) . '</td></tr>';
    }
    if (($inv['discount_cents'] ?? 0) > 0) {
        $rows .= "<tr><td $td>Remise</td><td $tdr>-" . eur($inv['discount_cents']) . '</td></tr>';
    }
    $vat = '';
    foreach (($inv['tax_breakdown'] ?? []) as $v) {
        $vat .= '<div>TVA ' . number_format($v['rate_bp'] / 100, 1, ',', '') . ' % : ' . eur($v['tva']) . ' (HT ' . eur($v['ht']) . ')</div>';
    }
    $pays = [];
    foreach ($inv['payments'] ?? [] as $p) {
        $pays[] = ($p['label'] ?? $methods[$p['method']] ?? $p['method']) . ' ' . eur($p['amount']);
    }
    $credit = ($inv['kind'] ?? '') === 'credit';
    $subject = $r['name'] . ($credit ? ' — avoir ' : ' — facture ') . $inv['number'];
    $body = $test . $header
        . '<p style="font-size:18px;font-weight:800;margin:0">' . ($credit ? "Facture d'avoir " : 'Facture ') . e($inv['number']) . '</p>'
        . '<p style="color:#555;margin:2px 0 12px">Du ' . e(dfr($inv['issued_at'])) . ' · commande #' . e($inv['order_number'] ?? '') . '</p>'
        . '<div style="border:1px solid #ddd;border-radius:8px;padding:10px;margin-bottom:12px"><div style="color:#777;font-size:11px">CLIENT</div><b>' . e($cu['name'] ?? '') . '</b>'
        . (!empty($cu['address']) ? '<div>' . e($cu['address']) . '</div>' : '')
        . (!empty($cu['siret']) ? '<div style="color:#555;font-size:12px">SIRET ' . e($cu['siret']) . '</div>' : '')
        . (!empty($cu['vat_number']) ? '<div style="color:#555;font-size:12px">TVA ' . e($cu['vat_number']) . '</div>' : '') . '</div>'
        . '<table style="width:100%;border-collapse:collapse;font-size:14px">' . $rows
        . "<tr><td $td>Total HT</td><td $tdr>" . eur($inv['total_ht_cents']) . '</td></tr>'
        . "<tr><td $td>Total TVA</td><td $tdr>" . eur($inv['total_tax_cents']) . '</td></tr>'
        . '<tr><td style="padding:10px 4px;font-weight:800;font-size:16px">TOTAL TTC</td><td style="padding:10px 4px;font-weight:800;font-size:16px;text-align:right">' . eur($inv['total_ttc_cents']) . '</td></tr></table>'
        . '<div style="font-size:13px;color:#555;margin-top:8px">' . $vat . '<div><b>Facture acquittée</b>' . ($pays ? ' : ' . e(implode(' + ', $pays)) : '') . '</div></div>'
        . '<p style="font-size:11px;color:#999">Paiement comptant, pas d\'escompte. Clients professionnels : pénalités de retard au taux de 3 fois le taux d\'intérêt légal, indemnité forfaitaire de 40 € pour frais de recouvrement.</p>'
        . '<p>Merci de votre confiance.</p>';
} elseif ($type === 'statement') {
    $a = $payload['data']['account'];
    $c = $a['customer'];
    $name = $c['kind'] === 'company' && !empty($c['company_name']) ? $c['company_name'] : trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''));
    $month = DateTime::createFromFormat('Y-m-d', $a['month'] . '-01');
    $months = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    $label = $month ? $months[(int) $month->format('n')] . ' ' . $month->format('Y') : $a['month'];
    $rows = "<tr><td $td colspan=\"2\">Solde au début du mois</td><td $tdr>" . eur($a['opening_cents']) . '</td></tr>';
    $sumOrders = 0;
    foreach ($a['orders'] as $o) {
        $sumOrders += $o['amount_cents'];
        $rows .= "<tr><td $td>" . e(dfr($o['date'])) . "</td><td $td>Commande #" . e($o['number'])
            . '<div style="color:#777;font-size:12px">' . e($o['items'] ?? '') . "</div></td><td $tdr>" . eur($o['amount_cents']) . '</td></tr>';
    }
    $sumPaid = 0;
    foreach ($a['settlements'] as $s) {
        $sumPaid += $s['amount_cents'];
        $rows .= "<tr><td $td>" . e(dfr($s['date'])) . "</td><td $td>Règlement " . e($methods[$s['method']] ?? $s['method'])
            . (!empty($s['reference']) ? ' (' . e($s['reference']) . ')' : '') . "</td><td $tdr>-" . eur($s['amount_cents']) . '</td></tr>';
    }
    $closing = $a['opening_cents'] + $sumOrders - $sumPaid;
    $subject = $r['name'] . ' — relevé de compte ' . $label;
    $body = $test . $header
        . '<p><b>' . e($name) . '</b>' . (!empty($c['siret']) ? '<br>SIRET ' . e($c['siret']) : '') . (!empty($c['billing_address']) ? '<br>' . nl2br(e($c['billing_address'])) : '') . '</p>'
        . '<p>Relevé de compte — <b>' . e($label) . '</b></p>'
        . '<table style="width:100%;border-collapse:collapse;font-size:14px">' . $rows
        . '<tr><td colspan="2" style="padding:10px 4px;font-weight:800;font-size:16px">Solde à la fin du mois</td><td style="padding:10px 4px;font-weight:800;font-size:16px;text-align:right">' . eur($closing) . '</td></tr></table>'
        . '<p style="font-size:13px;color:#555">Solde actuel du compte : <b>' . eur($a['balance_cents']) . '</b></p>'
        . '<p>Merci de votre confiance.</p>';
} else {
    // --- Récapitulatif de la journée (fermeture de caisse) pour le gérant
    $d = $payload['data']['report'];
    $day = $d['day'];
    $pct = function ($cur, $ref) {
        if ((int) $ref <= 0) {
            return '<span style="color:#999">—</span>';
        }
        $v = round(($cur - $ref) * 100 / $ref);
        return '<span style="color:' . ($v >= 0 ? '#2e7d32' : '#c62828') . ';font-weight:700">' . ($v >= 0 ? '+' : '') . $v . ' %</span>';
    };
    $kpi = function ($label, $value) {
        return '<td style="padding:8px;background:#f5f4f1;border-radius:8px;text-align:center"><div style="font-size:12px;color:#777">'
            . e($label) . '</div><div style="font-size:18px;font-weight:800">' . $value . '</div></td>';
    };
    $rows = '';
    foreach (($d['payments'] ?? []) as $m => $c) {
        $rows .= "<tr><td $td>" . e($methods[$m] ?? $m) . "</td><td $tdr>" . eur($c) . '</td></tr>';
    }
    $svc = '';
    foreach ($d['services'] as $s) {
        $cnt = $s['count'] ?? null;
        $svc .= "<tr><td $td><b>" . ($s['service'] === 'midi' ? 'Midi' : 'Soir') . '</b><div style="color:#777;font-size:12px">'
            . (int) $s['orders_count'] . ' commande(s)</div></td>'
            . "<td $tdr>" . eur($s['revenue_cents']) . '</td>'
            . "<td $tdr>" . ($cnt ? 'compté ' . eur($cnt['counted_cents']) . '<div style="font-size:12px;color:' . ((int) $cnt['difference_cents'] === 0 ? '#2e7d32' : '#c62828') . '">écart ' . eur($cnt['difference_cents']) . '</div>' : '<span style="color:#ef8a17">non compté</span>') . '</td></tr>';
    }
    $soir = $d['services'][1]['count'] ?? null;
    // contrôle de caisse : attendu par la caisse / compté ou relevé (TPE…) / écart
    $rec = '';
    foreach (($d['reconciliation'] ?? []) as $x) {
        $diff = $x['difference_cents'];
        $rec .= "<tr><td $td>" . e($x['method'] === 'cash' ? 'Espèces (avec le fond)' : ($x['label'] ?? $methods[$x['method']] ?? $x['method'])) . '</td>'
            . "<td $tdr>" . eur($x['expected_cents']) . '</td>'
            . "<td $tdr>" . ($x['declared_cents'] === null ? '—' : eur($x['declared_cents'])) . '</td>'
            . "<td $tdr><b style=\"color:" . ((int) $diff === 0 ? '#2e7d32' : '#c62828') . '">'
            . ($diff === null ? '—' : ((int) $diff === 0 ? 'juste' : ($diff > 0 ? '+' : '') . eur($diff))) . '</b></td></tr>';
    }
    $top = '';
    foreach (array_slice($d['top_products'] ?? [], 0, 8) as $p) {
        $top .= "<tr><td $td>" . e($p['name']) . "</td><td $tdr>" . (int) $p['quantity'] . "</td><td $tdr>" . eur($p['revenue_cents']) . '</td></tr>';
    }
    $drivers = implode(', ', array_map(function ($x) {
        return e($x['name']) . ' (' . (int) $x['deliveries'] . ')';
    }, $d['drivers'] ?? []));
    $subject = $r['name'] . ' — clôture du ' . dfr($d['date']) . ' : ' . eur($day['revenue_cents']);
    $h2 = 'style="font-size:15px;margin:18px 0 6px"';
    $body = $test . $header
        . '<p style="font-size:16px">Clôture de caisse du <b>' . e(dfr($d['date'])) . '</b></p>'
        . '<table style="width:100%;border-spacing:6px"><tr>'
        . $kpi("Chiffre d'affaires", eur($day['revenue_cents']))
        . $kpi('Commandes', (int) $day['orders'])
        . $kpi('Panier moyen', eur($day['avg_cents']))
        . '</tr></table>'
        . '<p style="font-size:13px">vs même jour N-1 : ' . $pct($day['revenue_cents'], $d['last_year']['revenue_cents'] ?? 0)
        . ' · vs semaine dernière : ' . $pct($day['revenue_cents'], $d['last_week']['revenue_cents'] ?? 0)
        . '<br>Cumul du mois : <b>' . eur($d['month']['revenue_cents']) . '</b> (' . (int) $d['month']['orders'] . ' commandes)</p>'
        . '<p style="font-size:13px;color:#555">' . (int) $day['dine_in'] . ' sur place · ' . (int) $day['takeaway'] . ' à emporter · '
        . (int) $day['delivery'] . ' livraisons · ' . (int) $day['web'] . ' via le site' . ($drivers ? '<br>Livreurs : ' . $drivers : '') . '</p>'
        . "<h3 $h2>Services</h3><table style=\"width:100%;border-collapse:collapse;font-size:14px\">$svc</table>"
        . "<h3 $h2>Encaissements</h3><table style=\"width:100%;border-collapse:collapse;font-size:14px\">$rows</table>"
        . ($rec ? "<h3 $h2>Contrôle de caisse</h3><table style=\"width:100%;border-collapse:collapse;font-size:14px\">"
            . "<tr><td $td style=\"color:#777\"></td><td $tdr><span style=\"color:#777\">Caisse</span></td><td $tdr><span style=\"color:#777\">Compté</span></td><td $tdr><span style=\"color:#777\">Écart</span></td></tr>"
            . "$rec</table>" : '')
        . "<h3 $h2>Fond de caisse</h3><p style=\"font-size:14px\">"
        . ($soir ? 'Compté le soir : <b>' . eur($soir['counted_cents']) . '</b> · retiré ' . eur($soir['withdrawn_cents'])
            . '<br>Fond laissé pour demain : <b>' . eur($soir['float_cents']) . '</b>' : 'Fond du soir non compté')
        . (!empty($d['opening']) ? '<br><span style="color:#777">Caisse ouverte à ' . e(substr((new DateTime($d['opening']['opened_at']))->setTimezone(new DateTimeZone('Indian/Reunion'))->format('H:i'), 0, 5))
            . (!empty($d['opening']['staff']) ? ' par ' . e($d['opening']['staff']) : '') . ' avec un fond de ' . eur($d['opening']['float_cents']) . '</span>' : '') . '</p>'
        . (($d['unpaid']['count'] ?? 0) > 0 ? '<p style="color:#c62828;font-weight:700">⚠ ' . (int) $d['unpaid']['count'] . ' commande(s) non payée(s) : ' . eur($d['unpaid']['amount_cents']) . '</p>' : '')
        . '<p style="font-size:13px;color:#555">Annulées : ' . (int) $d['cancelled']['count'] . ' (' . eur($d['cancelled']['amount_cents']) . ')'
        . ' · articles offerts : ' . (int) $d['offered']['count'] . ' · remises : ' . eur($d['offered']['discount_cents']) . '</p>'
        . ($top ? "<h3 $h2>Meilleures ventes</h3><table style=\"width:100%;border-collapse:collapse;font-size:14px\">$top</table>" : '')
        . (!empty($d['z']) ? '<p style="font-size:12px;color:#777;margin-top:16px">Clôture Z : ' . (int) $d['z']['tickets_count'] . ' ticket(s) · TTC '
            . eur($d['z']['total_ttc_cents']) . ' · HT ' . eur($d['z']['total_ht_cents']) . ' · TVA ' . eur($d['z']['total_tax_cents']) . '</p>' : '');
}

$html = '<!doctype html><html><body style="font-family:Arial,sans-serif;background:#f5f4f1;padding:16px">'
    . '<div style="max-width:560px;margin:auto;background:#fff;border-radius:12px;padding:20px">' . $body . '</div></body></html>';

// --- Envoi Brevo
$mail = [
    'sender' => ['name' => $r['name'], 'email' => 'commande@pizzaclub.re'],
    'to' => [['email' => $payload['to']]],
    'subject' => $subject,
    'htmlContent' => $html,
];
if (!empty($r['email'])) {
    $mail['replyTo'] = ['email' => $r['email'], 'name' => $r['name']];
}
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
    error_log('pco-mail Brevo ' . $status . ' ' . $res);
    fail(502, "L'envoi de l'e-mail a échoué");
}
echo json_encode(['ok' => true, 'to' => $payload['to']], JSON_UNESCAPED_UNICODE);
