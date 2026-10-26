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

// Liste commune caisse / panneau admin : celle de la caisse fait foi ; unavailability.json sert de secours
require_once __DIR__ . '/pco-availability.php';
$pos = pco_availability();
if (is_array($pos)) {
    $data['items'] = array_filter((array) ($pos['items'] ?? []));
    $data['ingredients'] = [];
    foreach ((array) ($pos['ingredient_keys'] ?? []) as $key) {
        $data['ingredients'][$key] = true;
    }
    $data['ingredientNames'] = array_values((array) ($pos['ingredient_names'] ?? []));
    $data['posClosure'] = $pos['closure'] ?? null;
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);
