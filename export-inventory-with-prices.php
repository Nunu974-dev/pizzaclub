<?php
/**
 * Export Inventaire avec Prix - Pizza Club
 * Fusionne inventory.json avec les prix de commande-fournisseurs.php
 */

// Charger l'inventaire
$inventoryFile = __DIR__ . '/inventory.json';
$inventoryData = json_decode(file_get_contents($inventoryFile), true);

// Charger les prix depuis commande-fournisseurs.php
$suppliers = [
    'Aphrodrink' => [
        'products' => [
            ['name' => 'Capri-Sun 20 cl', 'price' => 14.89],
            ['name' => 'Cilaos 50 cl', 'price' => 4.32],
            ['name' => 'Coca-Cola 1,5 L', 'price' => 17.20],
            ['name' => 'Coca-Cola 33 cl X24', 'price' => 24.70],
            ['name' => 'Coca-Cola 50 cl X24', 'price' => 26.40],
            ['name' => 'Desperados boites 33cl X24', 'price' => 44.16],
            ['name' => 'Desperados boites 50cl X24', 'price' => 52.08],
            ['name' => 'Dodo 33 cl X24', 'price' => 19.91],
            ['name' => 'Edena plate 1,5 L X8', 'price' => 5.95],
            ['name' => 'Edena plate 50 cl X12', 'price' => 5.52],
            ['name' => 'Fanta Orange 50 cl X24', 'price' => 24.96],
            ['name' => 'Fischer 33 cl X24', 'price' => 19.92],
            ['name' => 'HK 33 cl X24', 'price' => 33.18],
            ['name' => 'HK 50 cl X24', 'price' => 44.59],
            ['name' => 'Monster 50 cl', 'price' => 44.66],
            ['name' => 'Oasis Tropical 50 cl', 'price' => 35.52],
            ['name' => 'Orangina 50 cl', 'price' => 32.13],
            ['name' => 'Pokka Thé Melon 33cl', 'price' => 24.68],
            ['name' => 'Pokka Thé Pêche 33cl', 'price' => 24.68],
            ['name' => 'Pokka Thé Melon 50cl', 'price' => 30.09],
            ['name' => 'Pokka Thé Pêche 50cl', 'price' => 30.09],
            ['name' => 'Sambo 33 cl X24', 'price' => 19.91],
        ]
    ],
    'EDG' => [
        'products' => [
            ['name' => 'Escalope jaune VRC (kg)', 'price' => 10.56],
            ['name' => 'Merguez poulet vrac (kg)', 'price' => 9.36],
            ['name' => 'Saucisse fumée poulet (kg)', 'price' => 9.6],
        ]
    ],
    'Zembal' => [
        'products' => [
            ['name' => 'Assiettes S + couvercles (x50)', 'price' => 24],
            ['name' => 'Assiettes M 1000ml + couvercles (x50)', 'price' => 14],
            ['name' => 'Bobine blanche 450 (6 rouleaux)', 'price' => 15.9],
            ['name' => 'Boîte pizza 26 (x100)', 'price' => 19],
            ['name' => 'Boîte pizza 33 (x100)', 'price' => 22],
            ['name' => 'Boîte pizza 40 (x100)', 'price' => 35],
            ['name' => 'Boîte pizza DWK 26H4 (x100)', 'price' => 19],
            ['name' => 'Farine T55 1kg', 'price' => 0.97],
            ['name' => 'Farine Tipo 00 1kg', 'price' => 1.00],
            ['name' => 'Pots sauce 25ml (x100)', 'price' => 4.50],
            ['name' => 'Sacs bretelles PM (x1000)', 'price' => 48.8],
            ['name' => 'Sauce pizza aroma GOLD 3', 'price' => 10.50],
        ]
    ],
    'Topaze' => [
        'products' => [
            ['name' => 'Boîtes pizza 40 Delicious', 'price' => 35.95],
            ['name' => 'Boîtes pizza 33 Delicious', 'price' => 23.95],
            ['name' => 'Boîtes pizza 26 Delicious', 'price' => 18.50],
            ['name' => 'Boîtes pizza 33 dakri', 'price' => 23.95],
            ['name' => 'Boîtes pizza 40 semba', 'price' => 43.95],
            ['name' => 'Farine Tipo 0 (10x1kg)', 'price' => 10.9],
            ['name' => 'Farine T55 Moulin Vert (10x1kg)', 'price' => 9.8],
            ['name' => 'Gnocchi Surgital 10kg', 'price' => 59.50],
            ['name' => 'Huile de grignon 5L', 'price' => 25.00],
            ['name' => 'Lunettes Surgital 3kg', 'price' => 43.59],
            ['name' => 'Sauce pizza AROPIZ', 'price' => 27.60],
        ]
    ],
    'Frais Import' => [
        'products' => [
            ['name' => 'Cheddar burger 88 tranches', 'price' => 11.95],
            ['name' => 'Chorizo tranché 500g', 'price' => 7.8],
            ['name' => 'Chute saumon fumé 95/05 1kg', 'price' => 18.47],
            ['name' => 'Coulant chocolat Ø65', 'price' => 2.5],
            ['name' => 'Crème 18% Larsa 1L', 'price' => 4.10],
            ['name' => 'Crème cuisson 18% Président', 'price' => 5],
            ['name' => 'Crème cuisson 18% Président 1L', 'price' => 4.20],
            ['name' => 'Emmental râpé Kaasbrik 45% 1kg', 'price' => 7.55],
            ['name' => 'Essuie-mains gaufrés 450F', 'price' => 3.758],
            ['name' => 'Épaule cuite DD 5kg', 'price' => 6.95],
            ['name' => 'Farine T55 1kg', 'price' => 1.053],
            ['name' => 'Farine Tipo 00 1kg', 'price' => 1.363],
            ['name' => 'Frites 9/9 GR A4 2.5kg', 'price' => 2.39],
            ['name' => 'Fromage cheddar burger 88tr', 'price' => 0],
            ['name' => 'Fromage chèvre IQF 500g', 'price' => 7.94],
            ['name' => 'Fusilli / Spirali Pasta Z 5kg', 'price' => 12.988],
            ['name' => 'Huile Tournesol 1L', 'price' => 2.958],
            ['name' => 'Lardons fumés 1kg', 'price' => 7.8],
            ['name' => 'Lait 1/2 écrémé 1L', 'price' => 1.18],
            ['name' => 'Levure SAF Rouge 500g', 'price' => 3.833],
            ['name' => 'Liquide vaisselle 5L', 'price' => 8.50],
            ['name' => 'Miel Mille Fleurs 1kg', 'price' => 7.817],
            ['name' => 'Mozzarella cossettes 2.5kg', 'price' => 2.5],
            ['name' => 'Mozzarella râpée 2kg', 'price' => 7.40],
            ['name' => 'Mozzarella tranches IQF 500g', 'price' => 7.26],
            ['name' => 'Oignons frits  1kg', 'price' => 4.805],
            ['name' => 'Olive noire dénoyautée 5/1', 'price' => 11.283],
            ['name' => 'Pulpe d\'ail pot 1kg', 'price' => 4.25],
            ['name' => 'Reblochon tranché 500g', 'price' => 13.98],
            ['name' => 'Sacs poubelles 200L (rouleau)', 'price' => 12.00],
            ['name' => 'Sarcive de volaille 2kg', 'price' => 21.95],
            ['name' => 'Sauce BBQ Gyma 1L', 'price' => 4.177],
            ['name' => 'Sauce Pizza Cabanon 12/14', 'price' => 8.495],
            ['name' => 'Saumon Salar 1kg', 'price' => 18.47],
            ['name' => 'Savon main 5L', 'price' => 9.50],
            ['name' => 'Sel fin sachet 1kg', 'price' => 1.116],
            ['name' => 'Sucre roux semoule 1kg', 'price' => 1.45],
            ['name' => 'Thon entier naturel 4/4', 'price' => 7.18],
        ]
    ],
    'Géant Casino' => [
        'products' => [
            ['name' => 'Bleu cube 14mm 1.3kg', 'price' => 26.49],
            ['name' => 'Boeuf haché égrené 1kg', 'price' => 11.99],
            ['name' => 'Boite sushi a fenetre noir 17,5x12x4,5 x50', 'price' => 2.75],
            ['name' => 'Chute saumon fumé 1kg', 'price' => 13.75],
            ['name' => 'Crevette deco 300/500 400gr', 'price' => 9.99],
            ['name' => 'Cuillere a glace en bois 9,5cm x100', 'price' => 2.15],
            ['name' => 'Fourme d\'ambert cube 1300kg', 'price' => 2.75],
            ['name' => 'Gants nitrile noirs non poudré XL x100', 'price' => 6.99],
            ['name' => 'Gobelet carton boisson 180ml x50', 'price' => 2.75],
            ['name' => 'Huile tournesol COOSOL 5L', 'price' => 12.99],
            ['name' => 'Kit couvert bois fourchette/cuill x 100', 'price' => 12.59],
            ['name' => 'Lavettes jaunes 36x42', 'price' => 5.29],
            ['name' => 'Mozzarella tranche 1200kg', 'price' => 17.99],
            ['name' => 'Raclette tranches 1.100kg surg', 'price' => 17.99],
            ['name' => 'Sac réutilisable x100', 'price' => 6.15],
            ['name' => 'Sarcive de volaille 2kg SALAISONS', 'price' => 19.95],
            ['name' => 'Sauce Colona ALGERIENNE FLACON 850ml', 'price' => 4.99],
            ['name' => 'Sauce Colona BBQ FLACON 900ml', 'price' => 4.99],
            ['name' => 'Sauce Colona BRAZIL FLACON 900ml', 'price' => 4.99],
            ['name' => 'Sauce Colona TUNISIENNE FLACON 900ml', 'price' => 4.99],
            ['name' => 'Sauce d\'huitre panda 907gr', 'price' => 4.89],
            ['name' => 'Sel fin 1kg salina', 'price' => 0.79],
            ['name' => 'Serviete alba 30x30 x100', 'price' => 1.25],
        ]
    ],
];

// Créer une table de correspondance nom => prix
$priceMap = [];
foreach ($suppliers as $supplierName => $supplier) {
    foreach ($supplier['products'] as $product) {
        $priceMap[$product['name']] = [
            'price' => $product['price'],
            'supplier' => $supplierName
        ];
    }
}

// Fusionner avec l'inventaire
$export = [];
foreach ($inventoryData['inventory'] as $item) {
    $name = $item['name'];
    $quantity = $item['quantity'];
    $unit = $item['unit'];
    
    $price = 0;
    $supplier = 'N/A';
    $total = 0;
    
    // Chercher le prix
    if (isset($priceMap[$name])) {
        $price = $priceMap[$name]['price'];
        $supplier = $priceMap[$name]['supplier'];
        $total = $price * $quantity;
    }
    
    $export[] = [
        'Article' => $name,
        'Quantité' => $quantity,
        'Unité' => $unit,
        'Prix Unitaire (€)' => number_format($price, 2, ',', ' '),
        'Total (€)' => number_format($total, 2, ',', ' '),
        'Fournisseur' => $supplier
    ];
}

// Générer le CSV
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="inventaire-avec-prix-' . date('Y-m-d') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// BOM UTF-8 pour Excel
echo "\xEF\xBB\xBF";

// Créer le CSV
$output = fopen('php://output', 'w');

// En-têtes
fputcsv($output, ['Article', 'Quantité', 'Unité', 'Prix Unitaire (€)', 'Total (€)', 'Fournisseur'], ';');

// Données
foreach ($export as $row) {
    fputcsv($output, $row, ';');
}

// Total général
$grandTotal = array_sum(array_column($inventoryData['inventory'], 'quantity'));
$grandValue = 0;
foreach ($inventoryData['inventory'] as $item) {
    if (isset($priceMap[$item['name']])) {
        $grandValue += $priceMap[$item['name']]['price'] * $item['quantity'];
    }
}

fputcsv($output, [''], ';');
fputcsv($output, ['TOTAL GÉNÉRAL', $grandTotal, '', '', number_format($grandValue, 2, ',', ' '), ''], ';');

fclose($output);
exit;
