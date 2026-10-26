<?php
/**
 * Disponibilités pilotées depuis la caisse Jorders
 *
 * La caisse marque un produit ou un ingrédient épuisé, ou ferme les commandes en ligne ;
 * le site lit cet état (public_availability) et l'ajoute à ses propres indisponibilités
 * (unavailability.json, inchangé). Réponse gardée 20 s en cache ; si la caisse ne répond pas,
 * le site continue avec ses seules règles habituelles (jamais bloquant).
 *
 * Configuration : config/pco-config.php (supabase_url, publishable_key) ;
 *   'availability_restaurant' => 'pizza-club' : restaurant dont la caisse pilote le site.
 *   Sans cette clé, rien n'est lu (une caisse de test ne doit pas fermer le vrai site).
 */

function pco_availability()
{
    $cfgFile = __DIR__ . '/config/pco-config.php';
    if (!file_exists($cfgFile)) {
        return null;
    }
    $cfg = require $cfgFile;
    if (!is_array($cfg) || empty($cfg['supabase_url']) || empty($cfg['publishable_key'])) {
        return null;
    }
    $slug = $cfg['availability_restaurant'] ?? null;
    if (!$slug) {
        return null;
    }

    $cache = sys_get_temp_dir() . '/pco-availability-' . md5($slug) . '.json';
    if (file_exists($cache) && time() - filemtime($cache) < 20) {
        $data = json_decode((string) file_get_contents($cache), true);
        if (is_array($data)) {
            return $data;
        }
    }

    $ch = curl_init(rtrim($cfg['supabase_url'], '/') . '/rest/v1/rpc/public_availability');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['p_slug' => $slug]),
        CURLOPT_HTTPHEADER => [
            'apikey: ' . $cfg['publishable_key'],
            'Authorization: Bearer ' . $cfg['publishable_key'],
            'Content-Type: application/json',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_TIMEOUT => 3,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = ($body !== false && $code === 200) ? json_decode($body, true) : null;
    if (!is_array($data)) {
        // caisse injoignable : dernière réponse connue (même ancienne), sinon rien
        $old = file_exists($cache) ? json_decode((string) file_get_contents($cache), true) : null;
        return is_array($old) ? $old : null;
    }
    @file_put_contents($cache, json_encode($data));
    return $data;
}

/** Fermeture des commandes en ligne décidée à la caisse, pour l'instant $ts (null = ouvert) */
function pco_online_closure($ts = null)
{
    $a = pco_availability();
    $c = $a['closure'] ?? null;
    if (!$c || empty($c['closed']) || empty($c['until'])) {
        return null;
    }
    $until = strtotime($c['until']);
    if ($until === false || ($ts ?? time()) >= $until) {
        return null;
    }
    return ['reason' => $c['reason'] ?? 'Commandes en ligne fermées', 'until' => $until];
}

/**
 * Enregistre la liste du panneau admin dans la caisse (une seule liste pour la caisse et le site).
 * $items : références épuisées (pizza-35…) ; $ingredients : [['key' => 'champignons', 'name' => 'Champignons frais'], …]
 * Renvoie l'état à jour, ou null si la caisse est injoignable (la liste reste alors dans unavailability.json).
 */
function pco_availability_save(array $items, array $ingredients)
{
    $cfgFile = __DIR__ . '/config/pco-config.php';
    if (!file_exists($cfgFile)) {
        return null;
    }
    $cfg = require $cfgFile;
    $slug = $cfg['availability_restaurant'] ?? null;
    if (!$slug || empty($cfg['supabase_url']) || empty($cfg['publishable_key']) || empty($cfg['ingest_key'])) {
        return null;
    }
    $ch = curl_init(rtrim($cfg['supabase_url'], '/') . '/rest/v1/rpc/site_availability_save');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'p_slug' => $slug,
            'p_key' => $cfg['ingest_key'],
            'p_items' => array_values($items),
            'p_ingredients' => array_values($ingredients),
        ]),
        CURLOPT_HTTPHEADER => [
            'apikey: ' . $cfg['publishable_key'],
            'Authorization: Bearer ' . $cfg['publishable_key'],
            'Content-Type: application/json',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 6,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = ($body !== false && $code === 200) ? json_decode($body, true) : null;
    if (!is_array($data)) {
        error_log('INDISPOS - caisse : enregistrement impossible (' . $code . ') ' . substr((string) $body, 0, 200));
        return null;
    }
    // le site relit tout de suite la nouvelle liste
    @file_put_contents(sys_get_temp_dir() . '/pco-availability-' . md5($slug) . '.json', json_encode($data));
    return $data;
}
