<?php
/**
 * API pour vérifier si le restaurant est fermé
 * Utilisé par le formulaire de commande pour bloquer les commandes si nécessaire
 * Peut être inclus comme module (require_once) ou appelé directement comme API
 */

// Ne définir JSON_FILE que s'il n'est pas déjà défini
if (!defined('JSON_FILE')) {
    define('JSON_FILE', __DIR__ . '/unavailability.json');
}

function isRestaurantClosed() {
    if (!file_exists(JSON_FILE)) {
        return [
            'isClosed' => false,
            'reason' => null
        ];
    }
    
    $data = json_decode(file_get_contents(JSON_FILE), true);
    
    if (!isset($data['closures'])) {
        return [
            'isClosed' => false,
            'reason' => null
        ];
    }
    
    $now = new DateTime();
    $today = $now->format('Y-m-d');
    $currentTime = $now->format('H:i:s');
    $dayOfWeek = (int)$now->format('N'); // 1 = Lundi, 7 = Dimanche
    
    // ========================================
    // JOURS DE FERMETURE RÉGULIERS
    // ========================================
    // Lundi = jour de fermeture (N = 1)
    if ($dayOfWeek === 1) {
        return [
            'isClosed' => true,
            'reason' => 'Jour de fermeture hebdomadaire',
            'type' => 'weekly',
            'message' => '🔒 Restaurant fermé le lundi. Réouverture mardi !'
        ];
    }
    
    // Vérifier la fermeture d'urgence
    if (isset($data['closures']['emergency']) && $data['closures']['emergency'] !== null) {
        $emergency = $data['closures']['emergency'];
        $emergencyDate = $emergency['date'];
        
        // Si la fermeture d'urgence est pour aujourd'hui
        if ($emergencyDate === $today) {
            return [
                'isClosed' => true,
                'reason' => $emergency['reason'],
                'type' => 'emergency',
                'message' => '🚨 Restaurant fermé : ' . $emergency['reason']
            ];
        }
    }
    
    // Vérifier les fermetures programmées
    if (isset($data['closures']['scheduled']) && is_array($data['closures']['scheduled'])) {
        foreach ($data['closures']['scheduled'] as $closure) {
            if ($closure['date'] === $today) {
                // Si c'est une fermeture toute la journée
                if ($closure['fullDay']) {
                    return [
                        'isClosed' => true,
                        'reason' => $closure['reason'],
                        'type' => 'scheduled',
                        'fullDay' => true,
                        'message' => '🔒 Restaurant fermé aujourd\'hui : ' . $closure['reason']
                    ];
                }
                
                // Si c'est une fermeture partielle, vérifier les horaires
                $startTime = $closure['startTime'] ?? '00:00:00';
                $endTime = $closure['endTime'] ?? '23:59:59';
                
                if ($currentTime >= $startTime && $currentTime <= $endTime) {
                    return [
                        'isClosed' => true,
                        'reason' => $closure['reason'],
                        'type' => 'scheduled',
                        'fullDay' => false,
                        'startTime' => $startTime,
                        'endTime' => $endTime,
                        'message' => '🔒 Restaurant fermé : ' . $closure['reason'] . ' (jusqu\'à ' . substr($endTime, 0, 5) . ')'
                    ];
                }
            }
        }
    }
    
    // ========================================
    // HORAIRES DE FERMETURE + DÉLAI AVANT FERMETURE
    // Restaurant ferme à 14h et 21h/22h
    // Bloquer commandes: 45min avant (livraison), 30min avant (emporter)
    // ========================================
    
    // Récupérer le mode de livraison depuis la requête (si disponible)
    $deliveryMode = $_GET['deliveryMode'] ?? $_POST['deliveryMode'] ?? 'livraison';
    $isDelivery = ($deliveryMode === 'livraison');
    
    // Délais avant fermeture
    $cutoffMinutes = $isDelivery ? 45 : 30;
    
    // Horaires de fermeture (14h midi, 21h ou 22h soir)
    $closingTimes = [
        ['hour' => 14, 'minute' => 0],  // Fermeture midi
        ['hour' => 21, 'minute' => 0],  // Fermeture soir (à ajuster)
    ];
    
    $currentHour = (int)date('G');
    $currentMinute = (int)date('i');
    $currentTotalMinutes = ($currentHour * 60) + $currentMinute;
    
    foreach ($closingTimes as $closing) {
        $closingTotalMinutes = ($closing['hour'] * 60) + $closing['minute'];
        $cutoffTime = $closingTotalMinutes - $cutoffMinutes;
        
        // Si on est dans la période de blocage avant fermeture
        if ($currentTotalMinutes >= $cutoffTime && $currentTotalMinutes < $closingTotalMinutes) {
            $closingTimeStr = sprintf("%02dh%02d", $closing['hour'], $closing['minute']);
            $cutoffTimeHour = floor($cutoffTime / 60);
            $cutoffTimeMin = $cutoffTime % 60;
            $cutoffTimeStr = sprintf("%02dh%02d", $cutoffTimeHour, $cutoffTimeMin);
            
            return [
                'isClosed' => true,
                'reason' => 'Délai avant fermeture',
                'type' => 'cutoff',
                'closingTime' => $closingTimeStr,
                'cutoffTime' => $cutoffTimeStr,
                'deliveryMode' => $deliveryMode,
                'message' => "⏰ Commandes " . ($isDelivery ? 'en livraison' : 'à emporter') . " fermées (fermeture à $closingTimeStr). Réouverture prochaine !"
            ];
        }
    }
    
    return [
        'isClosed' => false,
        'reason' => null
    ];
}

// Si appelé directement comme API (pas inclus comme module)
// Vérifier si on est dans un contexte d'appel direct
if (basename($_SERVER['SCRIPT_FILENAME']) === 'check-closure.php') {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    
    $status = isRestaurantClosed();
    echo json_encode($status);
}
