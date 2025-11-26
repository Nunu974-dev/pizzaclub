<?php
/**
 * API - Récupération des indisponibilités
 * Endpoint appelé par le site pour charger les produits indisponibles
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$jsonFile = __DIR__ . '/unavailability.json';

if (file_exists($jsonFile)) {
    echo file_get_contents($jsonFile);
} else {
    // Retourne une structure vide si le fichier n'existe pas
    echo json_encode([
        'items' => [],
        'ingredients' => [],
        'lastUpdate' => null
    ]);
}
