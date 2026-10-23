<?php
/**
 * Passerelle site web → PizzaClubOrders (caisse)
 *
 * Chaque commande validée par send-order.php est transmise à la caisse,
 * qui sonne et l'affiche en temps réel. Ne bloque JAMAIS la commande web :
 * en cas d'échec, la commande est mise en file (orders/pco-retry.jsonl)
 * et renvoyée au prochain passage (sans doublon : la caisse déduplique
 * sur le numéro de commande du site).
 *
 * Configuration : config/pco-config.php (non versionné)
 *   <?php return [
 *       'supabase_url' => 'https://xxxx.supabase.co',
 *       'publishable_key' => 'sb_publishable_...',
 *       'ingest_key' => 'pco_...',          // Admin caisse > Caisse & réglages > Passerelles
 *       'restaurant' => 'pizza-club',
 *   ];
 */

function pco_config()
{
    $file = __DIR__ . '/config/pco-config.php';
    if (!file_exists($file)) {
        return null;
    }
    $c = require $file;
    if (!is_array($c) || empty($c['supabase_url']) || empty($c['publishable_key']) || empty($c['ingest_key'])) {
        return null;
    }
    $c['restaurant'] = $c['restaurant'] ?? 'pizza-club';
    return $c;
}

function pco_cents($v)
{
    return (int) round(((float) $v) * 100);
}

function pco_size_label($size)
{
    $map = ['moyenne' => '33cm', 'grande' => '40cm', 'petite' => '26cm', 'L' => 'L', 'XL' => 'XL'];
    return $map[$size] ?? (string) $size;
}

function pco_list($v)
{
    if (empty($v)) {
        return [];
    }
    if (!is_array($v)) {
        return [(string) $v];
    }
    return array_values(array_filter(array_map(function ($x) {
        return is_array($x) ? ($x['name'] ?? '') : (string) $x;
    }, $v), 'strlen'));
}

/** Détails lisibles en cuisine pour une ligne du panier du site */
function pco_item_details($item)
{
    $type = $item['type'] ?? '';
    $c = $item['customization'] ?? [];
    $d = [];

    if ($type === 'formule') {
        if (!empty($c['pizza'])) {
            $pc = $c['pizzaCustomization'] ?? [];
            $info = [];
            if (!empty($pc['size'])) $info[] = pco_size_label($pc['size']);
            if (!empty($pc['base'])) $info[] = $pc['base'] === 'creme' ? 'base crème' : 'base tomate';
            $d[] = 'Pizza : ' . $c['pizza'] . ($info ? ' (' . implode(', ', $info) . ')' : '');
            foreach (pco_list($pc['addedIngredients'] ?? []) as $a) $d[] = '+ ' . $a;
            foreach (pco_list($pc['removedIngredients'] ?? []) as $r) $d[] = 'SANS ' . $r;
        }
        if (!empty($c['mainItem']) && is_array($c['mainItem'])) {
            $m = $c['mainItem'];
            $mc = $m['customization'] ?? [];
            $info = [];
            if (!empty($mc['size'])) $info[] = $mc['size'] === 'XL' ? 'XL' : 'L';
            if (!empty($mc['base'])) $info[] = $mc['base'];
            $d[] = (($m['type'] ?? '') === 'pate' ? 'Pâte : ' : 'Salade : ') . ($m['name'] ?? '') . ($info ? ' (' . implode(', ', $info) . ')' : '');
            foreach (pco_list($mc['options'] ?? []) as $o) $d[] = 'Option : ' . $o;
            foreach (pco_list($mc['supplements'] ?? []) as $s) $d[] = '+ ' . $s;
        }
        if (!empty($c['boisson'])) $d[] = 'Boisson : ' . $c['boisson'];
        if (!empty($c['dessert'])) $d[] = 'Dessert : ' . $c['dessert'];
        return $d;
    }

    if (!empty($c['base'])) {
        if (in_array($type, ['pizza', 'roll', 'bun'], true)) {
            if ($c['base'] === 'creme') $d[] = 'Base crème';
            elseif ($c['base'] !== 'tomate') $d[] = 'Base ' . $c['base'];
        } elseif ($type === 'sandwich') {
            $d[] = 'Base : ' . $c['base'];
        } else {
            $d[] = 'Base : ' . $c['base'];
        }
    }
    if ($type === 'sandwich' && !empty($c['type'])) {
        $d[] = $c['type'] === 'gratine' ? 'Gratiné (chaud)' : 'Américain (froid)';
    }
    if ($type === 'bun') {
        if (!empty($c['format'])) $d[] = 'Format : ' . $c['format'];
        if (!empty($c['type'])) $d[] = $c['type'] === 'pizza' ? 'Base pizza' : ($c['type'] === 'pate' ? 'Base pâte' : (string) $c['type']);
    }
    foreach (pco_list($c['ingredients'] ?? []) as $i) {
        if (in_array($type, ['bun', 'roll'], true)) $d[] = '+ ' . $i;
    }
    foreach (pco_list($c['added'] ?? $c['addedIngredients'] ?? []) as $a) $d[] = '+ ' . $a;
    foreach (pco_list($c['supplements'] ?? []) as $s) $d[] = '+ ' . $s;
    $sauces = pco_list($c['sauces'] ?? []);
    if ($sauces) $d[] = 'Sauces : ' . implode(' + ', array_map('ucfirst', $sauces));
    foreach (pco_list($c['options'] ?? []) as $o) {
        if ($o === 'pain') $d[] = 'Avec pain';
        elseif ($o === 'vinaigrette-sup') $d[] = 'Vinaigrette sup.';
        else $d[] = 'Option : ' . $o;
    }
    foreach (pco_list($c['removed'] ?? $c['removedIngredients'] ?? []) as $r) $d[] = 'SANS ' . $r;
    if (!empty($item['notes'])) $d[] = '>> ' . $item['notes'];
    return $d;
}

/** Référence catalogue (identique aux indisponibilités du site : 'pizza-35'…) */
function pco_legacy_ref($item)
{
    $type = $item['type'] ?? '';
    $ids = [
        'pizza' => $item['pizzaId'] ?? null,
        'pate' => $item['pateId'] ?? $item['itemId'] ?? null,
        'salade' => $item['saladeId'] ?? $item['itemId'] ?? null,
        'bun' => $item['bunId'] ?? $item['itemId'] ?? null,
        'roll' => $item['rollId'] ?? $item['itemId'] ?? null,
        'dessert' => $item['itemId'] ?? null,
        'boisson' => $item['boissonId'] ?? $item['itemId'] ?? null,
        'sandwich' => $item['sandwichId'] ?? $item['itemId'] ?? null,
    ];
    return isset($ids[$type]) && $ids[$type] !== null ? $type . '-' . $ids[$type] : null;
}

function pco_category_label($type)
{
    $map = [
        'pizza' => 'Pizzas', 'pate' => 'Pâtes', 'salade' => 'Salades', 'bun' => 'Buns', 'roll' => 'Rolls',
        'dessert' => 'Desserts', 'boisson' => 'Boissons', 'sandwich' => 'Sandwichs', 'formule' => 'Formules',
    ];
    return $map[$type] ?? null;
}

/** Convertit une commande du site au format de la caisse (ingest_order) */
function pco_build_payload($orderData, $restaurant)
{
    $customer = $orderData['customer'] ?? [];
    $modeMap = ['livraison' => 'delivery', 'emporter' => 'takeaway', 'surplace' => 'dine_in'];
    $mode = $modeMap[$customer['deliveryMode'] ?? 'emporter'] ?? 'takeaway';

    $lines = [];
    foreach (($orderData['items'] ?? []) as $item) {
        $qty = max(1, (int) ($item['quantity'] ?? 1));
        $total = pco_cents($item['totalPrice'] ?? (($item['price'] ?? 0) * $qty));
        $c = $item['customization'] ?? [];
        $size = $c['size'] ?? null;
        $lines[] = [
            'name' => (string) ($item['name'] ?? 'Article'),
            'variant' => ($size && ($item['type'] ?? '') !== 'formule') ? pco_size_label($size) : null,
            'quantity' => $qty,
            'unit_price_cents' => (int) round($total / $qty),
            'line_total_cents' => $total,
            'details' => pco_item_details($item),
            'category' => pco_category_label($item['type'] ?? ''),
            'legacy_ref' => pco_legacy_ref($item),
        ];
    }

    $scheduled = null;
    if (!empty($orderData['scheduledDate']) && isset($orderData['scheduledTime']) && $orderData['scheduledTime'] !== '') {
        $scheduled = sprintf('%sT%02d:00:00+04:00', $orderData['scheduledDate'], (int) $orderData['scheduledTime']);
    }

    $payload = [
        'restaurant' => $restaurant,
        'source' => 'web',
        'external_ref' => (string) ($orderData['orderNumber'] ?? ''),
        'mode' => $mode,
        'scheduled_for' => $scheduled,
        'notes' => $customer['comments'] ?? null,
        'promo_code' => $orderData['promoCode'] ?? null,
        'discount_cents' => pco_cents($orderData['discount'] ?? 0),
        'delivery_fee_cents' => pco_cents($orderData['deliveryFee'] ?? 0),
        'total_cents' => pco_cents($orderData['total'] ?? 0),
        'customer' => [
            'phone' => $customer['phone'] ?? null,
            'first_name' => $customer['firstName'] ?? null,
            'last_name' => $customer['lastName'] ?? null,
            'email' => $customer['email'] ?? null,
            'birthdate' => $customer['birthdate'] ?? null,
        ],
        'lines' => $lines,
    ];
    if ($mode === 'delivery') {
        $payload['address'] = [
            'line1' => $customer['address'] ?? '',
            'postal_code' => $customer['postalCode'] ?? null,
            'city' => $customer['city'] ?? null,
        ];
    }
    return $payload;
}

function pco_send($config, $payload)
{
    $url = rtrim($config['supabase_url'], '/') . '/rest/v1/rpc/ingest_order';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['p_key' => $config['ingest_key'], 'p' => $payload], JSON_UNESCAPED_UNICODE),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 6,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'apikey: ' . $config['publishable_key'],
        ],
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    $ok = $code >= 200 && $code < 300;
    return ['ok' => $ok, 'code' => $code, 'body' => $ok ? json_decode($body, true) : ($err ?: $body)];
}

/** Renvoie les commandes restées en attente (échec réseau précédent) */
function pco_flush_retry_queue($config)
{
    $file = __DIR__ . '/orders/pco-retry.jsonl';
    if (!file_exists($file)) {
        return;
    }
    $fp = fopen($file, 'c+');
    if (!$fp || !flock($fp, LOCK_EX | LOCK_NB)) {
        return;
    }
    $pending = array_filter(array_map('trim', explode("\n", stream_get_contents($fp))));
    $remaining = [];
    foreach ($pending as $line) {
        $payload = json_decode($line, true);
        if (!$payload) continue;
        $res = pco_send($config, $payload);
        // 4xx = rejet définitif (clé invalide, données) : on garde pour analyse humaine dans le log
        if (!$res['ok'] && ($res['code'] === 0 || $res['code'] >= 500)) {
            $remaining[] = $line;
        } elseif (!$res['ok']) {
            error_log('PizzaClubOrders rejet définitif ' . ($payload['external_ref'] ?? '?') . ' : ' . json_encode($res['body']));
        }
    }
    ftruncate($fp, 0);
    rewind($fp);
    if ($remaining) fwrite($fp, implode("\n", $remaining) . "\n");
    flock($fp, LOCK_UN);
    fclose($fp);
}

/**
 * Point d'entrée appelé par send-order.php.
 * Retourne ['sent' => bool, 'number' => int|null, 'skipped' => bool]
 */
function pco_forward_order($orderData)
{
    $config = pco_config();
    if (!$config) {
        return ['sent' => false, 'number' => null, 'skipped' => true];
    }
    try {
        pco_flush_retry_queue($config);
        $payload = pco_build_payload($orderData, $config['restaurant']);
        $res = pco_send($config, $payload);
        if ($res['ok']) {
            return ['sent' => true, 'number' => $res['body']['number'] ?? null, 'skipped' => false];
        }
        error_log('PizzaClubOrders échec (' . $res['code'] . ') : ' . (is_string($res['body']) ? $res['body'] : json_encode($res['body'])));
        if ($res['code'] === 0 || $res['code'] >= 500) {
            $dir = __DIR__ . '/orders';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            file_put_contents($dir . '/pco-retry.jsonl', json_encode($payload, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
        }
    } catch (Throwable $e) {
        error_log('PizzaClubOrders exception : ' . $e->getMessage());
    }
    return ['sent' => false, 'number' => null, 'skipped' => false];
}
