<?php
/**
 * API - Récupération des indisponibilités
 * Endpoint appelé par le site pour charger les produits indisponibles
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$jsonFile = __DIR__ . '/unavailability.json';
$data = file_exists($jsonFile) ? json_decode(file_get_contents($jsonFile), true) : null;
if (!is_array($data)) {
    $data = ['items' => [], 'ingredients' => [], 'lastUpdate' => null];
}
$data['items'] = (array) ($data['items'] ?? []);
$data['ingredients'] = (array) ($data['ingredients'] ?? []);

// + ce qui est épuisé à la caisse Jorders (produits par référence du site, ingrédients par nom)
require_once __DIR__ . '/pco-availability.php';
$pos = pco_availability();
if (is_array($pos)) {
    foreach ((array) ($pos['items'] ?? []) as $key => $v) {
        if ($v) {
            $data['items'][$key] = true;
        }
    }
    $data['ingredientNames'] = array_values((array) ($pos['ingredient_names'] ?? []));
    $data['posClosure'] = $pos['closure'] ?? null;
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);
