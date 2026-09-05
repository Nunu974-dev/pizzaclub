<?php
/**
 * API pour vérifier si le restaurant est fermé
 * Utilisé par le formulaire de commande pour bloquer les commandes si nécessaire
 * Peut être inclus comme module (require_once) ou appelé directement comme API
 */

// Fuseau horaire La Réunion (UTC+4) - indispensable ici car ce fichier est aussi
// appelé directement comme API (fetch côté client), sans passer par un autre
// script qui définirait déjà le fuseau. Sans ça, "aujourd'hui" est calculé selon
// le fuseau par défaut du serveur, ce qui peut décaler la date d'un jour et faire
// apparaître le lendemain comme fermé (ou l'inverse) selon l'heure.
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set('Indian/Reunion');
}

// Ne définir JSON_FILE que s'il n'est pas déjà défini
if (!defined('JSON_FILE')) {
    define('JSON_FILE', __DIR__ . '/unavailability.json');
}

function isRestaurantClosed() {
    // Accepter une date/heure spécifique à vérifier (pour les commandes programmées)
    $checkDateParam = $_GET['checkDate'] ?? null;
    $checkTimeParam = $_GET['checkTime'] ?? null;

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
    
    // Si on vérifie une date/heure future (commande programmée), utiliser ces valeurs
    if ($checkDateParam && $checkTimeParam) {
        $checkDT = DateTime::createFromFormat('Y-m-d H:i', $checkDateParam . ' ' . $checkTimeParam);
        if (!$checkDT) {
            return ['isClosed' => false, 'reason' => null];
        }
        $today       = $checkDateParam;
        $currentTime = $checkDT->format('H:i:s');
        $currentHour = (int)$checkDT->format('G');
        $dayOfWeek   = (int)$checkDT->format('N');
    } else {
        $now         = new DateTime();
        $today       = $now->format('Y-m-d');
        $currentTime = $now->format('H:i:s');
        $currentHour = (int)date('G');
        $dayOfWeek   = (int)$now->format('N');
    }
    
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
    
    // Dimanche midi = fermeture (N = 7) - uniquement avant 17h
    if ($dayOfWeek === 7) {
        if ($currentHour < 17) {
            return [
                'isClosed' => true,
                'reason' => 'Fermeture dimanche midi',
                'type' => 'weekly',
                'message' => '🔒 Restaurant fermé le dimanche midi. Réouverture à 18h !'
            ];
        }
    }
    
    // Vérifier la fermeture d'urgence
    if (isset($data['closures']['emergency']) && $data['closures']['emergency'] !== null) {
        $emergency = $data['closures']['emergency'];
        $emergencyDate = $emergency['date'];
        $emergencyService = $emergency['service'] ?? 'all';
        
        if ($emergencyDate === $today) {
            $isClosed = false;
            $serviceMsg = '';
            if ($emergencyService === 'all') {
                $isClosed = true;
                $serviceMsg = 'pour aujourd\'hui';
            } elseif ($emergencyService === 'midi' && $currentHour >= 11 && $currentHour < 14) {
                $isClosed = true;
                $serviceMsg = 'pour le service du midi';
            } elseif ($emergencyService === 'soir' && $currentHour >= 18 && $currentHour < 21) {
                $isClosed = true;
                $serviceMsg = 'pour le service du soir';
            }
            if ($isClosed) {
                return [
                    'isClosed'       => true,
                    'reason'         => $emergency['reason'],
                    'type'           => 'emergency',
                    'service'        => $emergencyService,
                    'message'        => '🚨 Restaurant fermé ' . $serviceMsg . ' : ' . $emergency['reason']
                ];
            }
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
    // HORAIRES D'OUVERTURE DU RESTAURANT
    // Midi: 11h-14h | Soir: 18h-21h
    // ========================================
    
    // $currentHour est déjà défini plus haut
    $currentMinute = $checkDateParam ? (int)(new DateTime($checkDateParam . ' ' . $checkTimeParam))->format('i') : (int)date('i');
    $currentTotalMinutes = ($currentHour * 60) + $currentMinute;
    
    // Vérifier si on est pendant les heures de fermeture (entre 14h et 18h)
    if ($currentHour >= 14 && $currentHour < 18) {
        return [
            'isClosed' => true,
            'reason' => 'Fermeture entre midi et soir',
            'type' => 'closed_hours',
            'message' => '🔒 Restaurant fermé. Réouverture à 18h pour le service du soir !'
        ];
    }
    
    // Vérifier si on est avant l'ouverture du matin (avant 11h)
    if ($currentHour < 11) {
        return [
            'isClosed' => true,
            'reason' => 'Fermeture avant ouverture',
            'type' => 'closed_hours',
            'message' => '🔒 Restaurant fermé. Ouverture à 11h pour le service du midi !'
        ];
    }
    
    // Vérifier si on est après la fermeture du soir (après 21h)
    if ($currentHour >= 21) {
        return [
            'isClosed' => true,
            'reason' => 'Fermeture après service',
            'type' => 'closed_hours',
            'message' => '🔒 Restaurant fermé pour aujourd\'hui. Réouverture demain à 11h !'
        ];
    }
    
    // ========================================
    // DÉLAI AVANT FERMETURE
    // Bloquer commandes: 45min avant (livraison), 30min avant (emporter)
    // ========================================
    
    // Récupérer le mode de livraison depuis la requête (si disponible)
    $deliveryMode = $_GET['deliveryMode'] ?? $_POST['deliveryMode'] ?? $GLOBALS['_deliveryMode'] ?? 'livraison';
    $isDelivery = ($deliveryMode === 'livraison');
    
    // Le délai de coupure avant fermeture ne s'applique qu'aux commandes immédiates
    if (!$checkDateParam) {
        $cutoffMinutes = $isDelivery ? 45 : 30;
        
        $closingTimes = [
            ['hour' => 14, 'minute' => 0],
            ['hour' => 21, 'minute' => 0],
        ];
        
        foreach ($closingTimes as $closing) {
            $closingTotalMinutes = ($closing['hour'] * 60) + $closing['minute'];
            $cutoffTime = $closingTotalMinutes - $cutoffMinutes;
            
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
