// ========================================
// CONFIGURATION
// ========================================
const CONFIG = {
    // Email configuration
    email: {
        recipientEmail: 'commande@pizzaclub.re', // Email où recevoir les commandes
        ccEmail: '' // Email en copie (optionnel)
    },
    
    // SMS configuration (Twilio)
    sms: {
        enabled: false, // Mettre à true pour activer
        accountSid: 'YOUR_TWILIO_ACCOUNT_SID',
        authToken: 'YOUR_TWILIO_AUTH_TOKEN',
        fromNumber: '+33123456789',
        toNumber: '+33123456789' // Numéro du pizzeria
    },
    
    // Delivery settings
    delivery: {
        fee: 0, // Livraison GRATUITE à La Réunion
        freeDeliveryThreshold: 0, // Toujours gratuit
        estimatedTime: {
            livraison: 'minimum 1h (selon les commandes en cours)',
            emporter: '15-20 min'
        },
        // Zones de livraison (codes postaux acceptés)
        deliveryZones: [
            '97410', // Saint-Pierre (avec restrictions de quartiers)
        ],
        
        // ========================================
        // QUARTIERS EXCLUS PAR CODE POSTAL
        // ========================================
        // Pour Saint-Pierre (97410), certains quartiers ne sont PAS livrés
        excludedAreas: {
            '97410': {
                // Quartiers NON desservis
                excludedDistricts: [
                    'Mont-Vert-les-Bas',
                    'Mont Vert les Bas',
                    'Mont-Vert-les-Hauts', 
                    'Mont Vert les Hauts',
         
                    'Montvert',
                    'Mont Vert'
                ],
                // Mots-clés dans l'adresse qui indiquent une zone non desservie
                excludedKeywords: [
                    'mont vert',
                    'montvert',
                    'mont-vert',
    
                ],
                // Message personnalisé
                message: '🚫 Nous ne livrons pas à Mont-Vert. Secteurs desservis : Terre-Sainte, Ravine Blanche, Casabona, Centre-Ville, Ligne Paradis (bas), Cité Jasmin, Chemin Badamier, etc.'
            }
        },
        
        // ZONES DESSERVIES À SAINT-PIERRE (97410)
        // Pour information/affichage client
        deliveredAreas: {
            '97410': [
                'Centre-Ville Saint-Pierre',
                'Terre-Sainte',
                'Ravine Blanche',
                'Casabona (partie 97410)',
                'Centre Ouest',
                'Ligne Paradis (bas, côté Saint-Pierre)',
                'Cité Jasmin',
                'Chemin Badamier',
                'Bois d\'Olives (limite)',
                'Pierrefonds (proche centre)',
                'Saint Pierre',
                'Grand Bois',
            ]
        },
        
        // Message affiché si hors zone (code postal pas dans deliveryZones)
        outOfZoneMessage: '😔 Désolé, nous ne livrons pas encore dans votre secteur. Vous pouvez commander en mode "À emporter".'
    },
    
    // Restaurant info
    restaurant: {
        name: 'Pizza Club',
        address: '43 Rue Four à Chaux, 97410 Saint-Pierre, La Réunion',
        phone: '0262 66 82 30',
        whatsapp: '262692620062',
        email: 'contact@pizzaclub.fr'
    },
    
    // Heures d'ouverture
    openingHours: {
        closedDays: [1], // Jours fermés toute la journée (1=lundi)
        closedMidi: [0], // Jours fermés uniquement le midi (0=dimanche)
        midi: {
            start: 11,  // 11h
            end: 14     // 14h (fermeture cuisine)
        },
        soir: {
            start: 18,  // 18h
            end: 21     // 21h (fermeture cuisine - 20h15/21h15 selon le jour)
        },
        preorderBuffer: 1  // 1 heure de battement pour précommander (depuis 10h pour midi, depuis 17h pour soir)
    }
};
