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
            livraison: '45-60 min',
            emporter: '15-20 min'
        }
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
