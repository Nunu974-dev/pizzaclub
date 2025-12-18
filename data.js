
// ========================================
// GESTION DES INDISPONIBILITÉS
// ========================================
const UNAVAILABLE_ITEMS = {
    // Produits actuellement indisponibles
    'pizza-35': true,
    'pizza-5': true,
    'dessert-401': true,
    'dessert-403': true,
    'dessert-404': true,
};

const UNAVAILABLE_INGREDIENTS = {
    // Ingrédients actuellement indisponibles
    'gorgonzola': true,  // Gorgonzola
    'parmesan': true,  // Parmesan
    'crevettes': true,  // Crevettes
};


// ========================================
// CONFIGURATION DES ICÔNES PAR CATÉGORIE
// ========================================
const CATEGORY_ICONS = {
    creme: { icon: 'fa-box', color: '#F5DEB3', label: 'Crème' },
    poulet: { icon: 'fa-drumstick-bite', color: '#FF8C00', label: 'Poulet' },
    boeuf: { icon: 'fa-cow', color: '#8B4513', label: 'Bœuf' },
    porc: { icon: 'fa-bacon', color: '#FF69B4', label: 'Porc' },
    mer: { icon: 'fa-fish', color: '#4169E1', label: 'Mer' },
    vegetarienne: { icon: 'fa-leaf', color: '#32CD32', label: 'Végé' },
    enfant: { icon: 'fa-child', color: '#FF69B4', label: 'Enfant' }
};

// ========================================
// DONNÉES DES PIZZAS - MENU LA RÉUNION 2025
// Toutes les pizzas avec icônes et badges
// ========================================
const PIZZAS_DATA = [
    // ========== 🧀 PIZZAS CRÈME ==========
    {
        id: 1,
        name: 'Mixte',
        category: 'creme',
        ingredients: ['Base crème', 'Fromage', 'Lardon', 'Mozzarella', 'Chèvre', 'Champignon frais', 'Olive'],
        price33: 14.90,
        price40: 17.90,
        image: 'img/SHOOT JULIEN 2021/Mixte.png',
        badge: null,
        isSpicy: false,
        isPremium: false
    },
    {
        id: 2,
        name: 'Campagnarde',
        category: 'creme',
        ingredients: ['Base crème', 'Fromage', 'Pomme de terre', 'Lardon', 'Oignon', 'Olive'],
        price33: 14.90,
        price40: 17.90,
        image: 'img/SHOOT JULIEN 2021/Campagnarde.png',
        badge: null,
        isSpicy: false,
        isPremium: false
    },
    {
        id: 3,
        name: 'Carbonara',
        category: 'creme',
        ingredients: ['Base crème carbonara maison', 'Fromage', 'Œuf', 'Olive'],
        price33: 14.90,
        price40: 17.90,
        image: 'img/SHOOT JULIEN 2021/Carbo.png',
        badge: 'Maison',
        isSpicy: false,
        isPremium: false
    },
    {
        id: 4,
        name: 'Buffle',
        category: 'creme',
        ingredients: ['Base crème', 'Fromage', 'Bœuf', 'Sarcive poulet', 'Oignon', 'Œuf', 'Olive'],
        price33: 15.90,
        price40: 18.90,
        image: 'img/SHOOT JULIEN 2021/Buffle.png',
        isSpicy: false,
        isPremium: true
    },
    {
        id: 5,
        name: 'Tartiflette',
        category: 'creme',
        ingredients: ['Base crème', 'Fromage', 'Lardon', 'Oignon', 'Reblochon', 'Pomme de terre', 'Olive'],
        price33: 15.90,
        price40: 18.90,
        image: 'img/SHOOT JULIEN 2021/Tartiflette.png',
        badge: 'Best-seller',
        isSpicy: false,
        isPremium: true
    },
    {
        id: 6,
        name: 'Raclette',
        category: 'creme',
        ingredients: ['Base crème', 'Fromage', 'Épaule', 'Oignon', 'Raclette', 'Pomme de terre', 'Olive'],
        price33: 15.90,
        price40: 18.90,
        image: 'img/SHOOT JULIEN 2021/Raclette.png',
        badge: 'Populaire',
        isSpicy: false,
        isPremium: true
    },

    // ========== 🍗 PIZZAS POULET ==========
    {
        id: 7,
        name: 'Orientale',
        category: 'poulet',
        ingredients: ['Tomate', 'Fromage', 'Merguez poulet', 'Poivron', 'Olive'],
        price33: 13.90,
        price40: 15.90,
        image: 'img/SHOOT JULIEN 2021/Orientale.png',
        badge: 'Halal',
        isSpicy: false,
        isPremium: false
    },
    {
        id: 8,
        name: 'Poulet Péi',
        category: 'poulet',
        ingredients: ['Tomate', 'Fromage', 'Poulet péi', 'Oignon', 'Crème', 'Olive'],
        price33: 13.90,
        price40: 15.90,
        image: 'img/SHOOT JULIEN 2021/Poulet.png',
        badge: 'Halal',
        isSpicy: false,
        isPremium: false
    },
    {
        id: 9,
        name: 'Hawaïenne',
        category: 'poulet',
        ingredients: ['Tomate', 'Fromage', 'Poulet péi', 'Ananas Victoria', 'Crème', 'Olive'],
        price33: 14.90,
        price40: 16.90,
        image: 'img/SHOOT JULIEN 2021/Hawaii.png',
        badge: 'Halal',
        isSpicy: false,
        isPremium: false
    },
    {
        id: 10,
        name: 'Asiatique',
        category: 'poulet',
        ingredients: ['Tomate', 'Fromage', 'Sarcive', 'Ananas Victoria', 'Olive'],
        price33: 14.90,
        price40: 17.90,
        image: 'img/SHOOT JULIEN 2021/Asiatique.png',
        isSpicy: false,
        isPremium: false
    },
    {
        id: 11,
        name: 'Créole',
        category: 'poulet',
        ingredients: ['Tomate', 'Fromage', 'Saucisse fumée poulet', 'Gros piment', 'Oignon', 'Olive'],
        price33: 14.90,
        price40: 17.90,
        image: 'img/SHOOT JULIEN 2021/Creole.png',
        badge: 'Halal',
        isSpicy: false,
        isPremium: false
    },
    {
        id: 12,
        name: 'Sarcive',
        category: 'poulet',
        ingredients: ['Tomate', 'Fromage', 'Sarcive', 'Gros piment', 'Oignon', 'Olive'],
        price33: 14.90,
        price40: 17.90,
        image: 'img/SHOOT JULIEN 2021/Sarcive.png',
        isSpicy: true,
        isPremium: false
    },
    {
        id: 13,
        name: 'Forestière',
        category: 'poulet',
        ingredients: ['Tomate', 'Fromage', 'Champignon', 'Poulet péi', 'Mozzarella', 'Olive'],
        price33: 15.90,
        price40: 17.90,
        image: 'img/SHOOT JULIEN 2021/Forestiere.png',
        badge: 'Halal',
        isSpicy: false,
        isPremium: false
    },

    // ========== 🐄 PIZZAS BŒUF ==========
    {
        id: 14,
        name: 'Bœuf',
        category: 'boeuf',
        ingredients: ['Tomate', 'Fromage', 'Bœuf', 'Olive'],
        price33: 13.90,
        price40: 15.90,
        image: 'img/SHOOT JULIEN 2021/Boeuf.png',
        isSpicy: false,
        isPremium: false
    },
    {
        id: 15,
        name: 'Bolo',
        category: 'boeuf',
        ingredients: ['Sauce bolognaise maison', 'Mozzarella', 'Olive'],
        price33: 13.90,
        price40: 15.90,
        image: 'img/SHOOT JULIEN 2021/Bolo.png',
        isSpicy: false,
        isPremium: false
    },
    {
        id: 16,
        name: 'Mexicaine',
        category: 'boeuf',
        ingredients: ['Tomate', 'Fromage', 'Bœuf', 'Chorizo', 'Maïs', 'Gros piment', 'Olive'],
        price33: 15.90,
        price40: 18.90,
        image: 'img/SHOOT JULIEN 2021/Mexicaine.png',
        badge: '🌶️ Forte',
        isSpicy: true,
        isPremium: false
    },
    {
        id: 17,
        name: 'Burger',
        category: 'boeuf',
        ingredients: ['Tomate', 'Fromage', 'Bœuf', 'Oignon', 'Cheddar', 'Olive'],
        price33: 18.90,
        price40: 20.90,
        image: 'img/SHOOT JULIEN 2021/Burger.png',
        isSpicy: false,
        isPremium: true
    },

    // ========== 🐖 PIZZAS PORC ==========
    {
        id: 18,
        name: 'Reine',
        category: 'porc',
        ingredients: ['Tomate', 'Fromage', 'Épaule', 'Champignon frais', 'Olive'],
        price33: 13.90,
        price40: 15.90,
        image: 'img/SHOOT JULIEN 2021/Reine.png',
        badge: 'Classique',
        isSpicy: false,
        isPremium: false
    },
    {
        id: 19,
        name: 'Spécial',
        category: 'porc',
        ingredients: ['Tomate', 'Fromage', 'Épaule', 'Œuf', 'Crème', 'Olive'],
        price33: 13.90,
        price40: 15.90,
        image: 'img/SHOOT JULIEN 2021/Speciale.png',
        badge: null,
        isSpicy: false,
        isPremium: false
    },
    {
        id: 20,
        name: 'Chorizo',
        category: 'porc',
        ingredients: ['Tomate', 'Fromage', 'Chorizo', 'Poivron', 'Olive'],
        price33: 13.90,
        price40: 15.90,
        image: 'img/SHOOT JULIEN 2021/Chorizo.png',
        badge: null,
        isSpicy: true,
        isPremium: false
    },
    {
        id: 21,
        name: 'Chocho',
        category: 'porc',
        ingredients: ['Tomate', 'Fromage', 'Chorizo', 'Champignon frais', 'Olive'],
        price33: 14.90,
        price40: 16.90,
        image: 'img/SHOOT JULIEN 2021/Chocho.png',
        badge: null,
        isSpicy: false,
        isPremium: false
    },
    {
        id: 22,
        name: 'Paysanne',
        category: 'porc',
        ingredients: ['Tomate', 'Fromage', 'Lardon', 'Pomme de terre', 'Champignon frais', 'Olive'],
        price33: 14.90,
        price40: 17.90,
        image: 'img/SHOOT JULIEN 2021/Paysanne.png',
        badge: null,
        isSpicy: false,
        isPremium: false
    },
    {
        id: 23,
        name: 'Fermière',
        category: 'porc',
        ingredients: ['Tomate', 'Fromage', 'Lardon', 'Oignon', 'Champignon frais', 'Crème', 'Olive'],
        price33: 14.90,
        price40: 17.90,
        image: 'img/SHOOT JULIEN 2021/Fermiere.png',
        badge: null,
        isSpicy: false,
        isPremium: false
    },
    {
        id: 24,
        name: 'Complète',
        category: 'porc',
        ingredients: ['Tomate', 'Fromage', 'Chorizo', 'Épaule', 'Merguez poulet', 'Champignon frais', 'Olive'],
        price33: 15.90,
        price40: 18.90,
        image: 'img/SHOOT JULIEN 2021/Complete.png',
        badge: null,
        isSpicy: false,
        isPremium: true
    },
    {
        id: 25,
        name: 'Total',
        category: 'porc',
        ingredients: ['Tomate', 'Fromage', 'Merguez poulet', 'Saucisse fumée poulet', 'Lardon', 'Champignon frais', 'Crème', 'Olive'],
        price33: 15.90,
        price40: 18.90,
        image: 'img/SHOOT JULIEN 2021/Totale.png',
        badge: null,
        isSpicy: false,
        isPremium: true
    },
    {
        id: 26,
        name: "L'Américaine",
        category: 'porc',
        ingredients: ['Base au choix', 'Frites', 'Emmental', 'Crème', '2 Sauces au choix', 'Olive'],
        price33: 16.90,
        price40: 18.90,
        image: 'img/AMERICAIN.png',
        badge: 'Premium',
        isSpicy: false,
        isPremium: true,
        needsAmericaineCustomization: true
    },
    {
        id: 27,
        name: 'Pizza Club',
        category: 'porc',
        ingredients: ['Tomate', 'Fromage', 'Lardon', 'Chèvre', 'Miel','Olive'],
        price33: 15.90,
        price40: 17.90,
        image: 'img/SHOOT JULIEN 2021/Pizza Club.png',
        badge: 'Signature',
        isSpicy: true,
        isPremium: true
    },

    // ========== 👶 PIZZA ENFANT ==========
    {
        id: 38,
        name: 'Marmaille',
        category: 'enfant',
        ingredients: ['Tomate', 'Emmental', 'Jambon ou Poulet au choix', 'Olive'],
        price33: 8.40,  // Prix 26cm uniquement
        price40: null,  // Pas de grande taille
        image: 'img/SHOOT JULIEN 2021/Reine.png',
        badge: '👶 Enfant',
        isSpicy: false,
        isPremium: false,
        isKids: true
    },

    // ========== 🌿 PIZZAS VÉGÉTARIENNES ==========
    {
        id: 28,
        name: 'Margherita',
        category: 'vegetarienne',
        ingredients: ['Tomate', 'Fromage', 'Olive'],
        price33: 10.90,
        price40: 12.90,
        image: 'img/SHOOT JULIEN 2021/Margherita.png',
        badge: 'Classique',
        isSpicy: false,
        isPremium: false
    },
    {
        id: 29,
        name: 'Chavignol',
        category: 'vegetarienne',
        ingredients: ['Tomate', 'Fromage', 'Chèvre', 'Miel', 'Olive'],
        price33: 13.90,
        price40: 15.90,
        image: 'img/SHOOT JULIEN 2021/Chavignol.png',
        badge: null,
        isSpicy: false,
        isPremium: false
    },
    {
        id: 30,
        name: 'Champi',
        category: 'vegetarienne',
        ingredients: ['Tomate', 'Fromage', 'Mozzarella', 'Champignon frais', 'Olive'],
        price33: 13.90,
        price40: 15.90,
        image: 'img/SHOOT JULIEN 2021/Champi.png',
        badge: null,
        isSpicy: false,
        isPremium: false
    },
    {
        id: 31,
        name: 'Végétarienne',
        category: 'vegetarienne',
        ingredients: ['Tomate', 'Fromage', 'Poivron', 'Oignon', 'Champignon frais', 'Pomme de terre', 'Olive'],
        price33: 14.90,
        price40: 17.90,
        image: 'img/SHOOT JULIEN 2021/Vegetarienne.png',
        badge: 'Veggie',
        isSpicy: false,
        isPremium: false
    },
    {
        id: 32,
        name: '4 Fromages',
        category: 'vegetarienne',
        ingredients: ['Tomate', 'Emmental', 'Mozzarella', 'BLeue', 'Chèvre', 'Olive'],
        price33: 15.90,
        price40: 18.90,
        image: 'img/SHOOT JULIEN 2021/4 fromage.png',
        badge: '4 Fromages',
        isSpicy: false,
        isPremium: true
    },

    // ========== 🐟 PIZZAS MER ==========
    {
        id: 33,
        name: 'Thon',
        category: 'mer',
        ingredients: ['Tomate', 'Fromage', 'Thon', 'Oignon', 'Tomate fraîche', 'Olive'],
        price33: 13.90,
        price40: 15.90,
        image: 'img/SHOOT JULIEN 2021/Thon.png',
        badge: null,
        isSpicy: false,
        isPremium: false
    },
    {
        id: 34,
        name: 'Mer',
        category: 'mer',
        ingredients: ['Tomate', 'Fromage', 'Anchois', 'Câpres', 'Olive'],
        price33: 13.90,
        price40: 15.90,
        image: 'img/SHOOT JULIEN 2021/Mer.png',
        badge: null,
        isSpicy: false,
        isPremium: false
    },
    {
        id: 35,
        name: 'Crevette',
        category: 'mer',
        ingredients: ['Tomate', 'Fromage', 'Crevette', 'Citron', 'Olive'],
        price33: 13.90,
        price40: 15.90,
        image: 'img/SHOOT JULIEN 2021/Crevette.png',
        badge: null,
        isSpicy: false,
        isPremium: false
    },
    {
        id: 36,
        name: 'Saumon',
        category: 'mer',
        ingredients: ['Base crème', 'Fromage', 'Saumon', 'Citron', 'Olive'],
        price33: 14.90,
        price40: 17.90,
        image: 'img/SHOOT JULIEN 2021/Saumon.png',
        badge: 'Premium',
        isSpicy: false,
        isPremium: true
    },
    {
        id: 37,
        name: 'Océane',
        category: 'mer',
        ingredients: ['Tomate', 'Fromage', 'Crevette', 'Saumon', 'Crème', 'Olive'],
        price33: 15.90,
        price40: 18.90,
        image: 'img/SHOOT JULIEN 2021/Oceane.png',
        badge: 'Premium',
        isSpicy: false,
        isPremium: true
    },
    {
        id: 39,
        name: 'Atlantide',
        category: 'mer',
        ingredients: ['Tomate', 'Fromage', 'Thon', 'Crevette', 'Saumon', 'Anchois', 'Olive'],
        price33: 16.90,
        price40: 19.90,
        image: 'img/SHOOT JULIEN 2021/Atlantide.png',
        badge: '⭐ Premium',
        isSpicy: false,
        isPremium: true
    }
];

// ========================================
// DONNÉES DES PÂTES
// ========================================
const PATES_DATA = [
    {
        id: 101,
        name: 'Carbonara',
        description: 'Sauce maison : Crème, lardons frais, oignon, aromate',
        priceL: 8.90,
        priceXL: 11.90,
        image: 'img/Pates/carbo_decoupe.png',
        category: 'pates',
        bases: ['Classique', 'Ricotta Épinard', 'Gnocchi']
    },
    {
        id: 102,
        name: 'Bolognaise',
        description: 'Sauce maison bolognaise : Carotte, oignon, bœuf selon arrivage, aromate',
        priceL: 8.90,
        priceXL: 11.90,
        image: 'img/Pates/PATE_BOLOGNAISE.jpg',
        category: 'pates',
        bases: ['Classique', 'Ricotta Épinard', 'Gnocchi']
    },
    {
        id: 103,
        name: 'Poulet Crème',
        description: 'Sauce maison : Poulet, crème, oignon, aromate',
        priceL: 8.90,
        priceXL: 11.90,
        image: 'img/Pates/PATE_POULET2.jpg',
        category: 'pates',
        bases: ['Classique', 'Ricotta Épinard', 'Gnocchi']
    },
    {
        id: 104,
        name: 'Saumon',
        description: 'Sauce maison : Crème, saumon fumé, aromate',
        priceL: 8.90,
        priceXL: 11.90,
        image: 'img/Pates/PATE_SAUMON.jpg',
        category: 'pates',
        bases: ['Classique', 'Ricotta Épinard', 'Gnocchi']
    },
    {
        id: 105,
        name: 'Pesto',
        description: 'Sauce pesto : Basilic, pignon de pin, huile d\'olive, aromate',
        priceL: 8.90,
        priceXL: 11.90,
        image: 'img/Pates/Pate_Pesto/AR5A9018.jpg',
        category: 'pates',
        bases: ['Classique', 'Ricotta Épinard', 'Gnocchi']
    },
    {
        id: 106,
        name: 'Curry',
        description: 'Sauce maison curry : Poulet, poivron, oignon, crème, curry',
        priceL: 8.90,
        priceXL: 11.90,
        image: 'img/Pates/PATE_POULET2.jpg',
        category: 'pates',
        bases: ['Classique', 'Ricotta Épinard', 'Gnocchi']
    },
    {
        id: 107,
        name: 'Créole',
        description: 'Sauce maison : Saucisse fumée, oignon, tomate, gros piment',
        priceL: 8.90,
        priceXL: 11.90,
        image: 'img/Pates/PATE_BOLOGNAISE.jpg',
        category: 'pates',
        bases: ['Classique', 'Ricotta Épinard', 'Gnocchi']
    }
];

// ========================================
// DONNÉES DES SALADES
// ========================================
const SALADES_DATA = [
    {
        id: 201,
        name: 'Salade du Jardin',
        description: 'Base + Champignon frais, œuf, carotte, pomme de terre',
        price: 8.90,
        image: 'img/Salade copie/SALADE POULET.jpg',
        category: 'salades',
        bases: ['Salade verte', 'Pâtes']
    },
    {
        id: 202,
        name: 'Salade Poulet',
        description: 'Base + Tomate, champignon frais, maïs, carotte, poulet',
        price: 8.90,
        image: 'img/Salade copie/SALADE POULET.jpg',
        category: 'salades',
        bases: ['Salade verte', 'Pâtes']
    },
    {
        id: 203,
        name: 'Salade Crudité',
        description: 'Base + Tomate, concombre, carotte, emmental',
        price: 8.90,
        image: 'img/Salade copie/SALADE CRUDITE.jpg',
        category: 'salades',
        bases: ['Salade verte', 'Pâtes']
    },
    {
        id: 204,
        name: 'Salade Saumon',
        description: 'Base + Tomate, concombre, carotte, saumon, citron',
        price: 8.90,
        image: 'img/Salade copie/SALADE SAUMON.jpg',
        category: 'salades',
        bases: ['Salade verte', 'Pâtes']
    },
    {
        id: 205,
        name: 'Salade Asiatique',
        description: 'Base + Carotte, poivron, ananas, sarcive',
        price: 8.90,
        image: 'img/Salade copie/SALADE PATE .jpg',
        category: 'salades',
        bases: ['Salade verte', 'Pâtes']
    },
    {
        id: 206,
        name: 'Salade Niçoise',
        description: 'Base + Œuf, thon ou anchois, oignon, mayonnaise, poivron',
        price: 8.90,
        image: 'img/Salade copie/SALADE CRUDITE.jpg',
        category: 'salades',
        bases: ['Salade verte', 'Pâtes']
    }
];

// ========================================
// DONNÉES DES BUNS CLUB
// ========================================
const BUNS_DATA = [
    {
        id: 301,
        name: 'Bun Poivron Champignon',
        description: 'Poivron + Champignon (Base tomate gratuite, crème +0,50€)',
        price: 4.50,
        price2: 8.40,
        price3: 12.50,
        image: 'img/buns/AR5A9035.jpg',
        category: 'buns',
        baseOption: true
    },
    {
        id: 302,
        name: 'Bun Mozza Pomme de terre',
        description: 'Mozza + Pomme de terre (Base tomate gratuite, crème +0,50€)',
        price: 4.50,
        price2: 8.40,
        price3: 12.50,
        image: 'img/buns/AR5A9038.jpg',
        category: 'buns',
        baseOption: true
    },
    {
        id: 303,
        name: 'Bun Bœuf Oignon',
        description: 'Bœuf + Oignon (Base tomate gratuite, crème +0,50€)',
        price: 4.50,
        price2: 8.40,
        price3: 12.50,
        image: 'img/buns/AR5A9040.jpg',
        category: 'buns',
        baseOption: true
    },
    {
        id: 304,
        name: 'Bun Poulet',
        description: 'Poulet (Base tomate gratuite, crème +0,50€)',
        price: 4.50,
        price2: 8.40,
        price3: 12.50,
        image: 'img/buns/AR5A9042.jpg',
        category: 'buns',
        baseOption: true
    }
];

// ========================================
// DONNÉES DES ROLLS
// ========================================
const ROLLS_DATA = [
    {
        id: 352,
        name: 'Boîte de 8 Rolls',
        description: 'Base crème ou tomate + Fromage + 2 ingrédients au choix',
        price: 7.90,
        image: 'img/Rolls/AR5A9052.jpg',
        category: 'rolls',
        customizable: true,
        ingredients: ['Lardon', 'Mozzarella', 'Chèvre', 'Champignon', 'Olive', 'Pomme de terre', 'Oignon', 'Œuf', 'Bœuf', 'Sarcive poulet', 'Reblochon', 'Épaule', 'Raclette', 'Merguez', 'Poivron', 'Poulet', 'Ananas Victoria', 'Saucisse fumée', 'Saucisse', 'Gros piment', 'Chorizo', 'Maïs', 'Cheddar', 'Salade', 'Tomate fraîche', 'Miel', 'Frites', 'Emmental', 'Roquefort', 'Thon', 'Anchois', 'Câpres', 'Crevette', 'Citron', 'Saumon']
    }
];

// ========================================
// DONNÉES DES DESSERTS
// ========================================
const DESSERTS_DATA = [
    {
        id: 401,
        name: 'Crème Brûlée',
        description: 'Crème brûlée maison',
        price: 3.90,
        image: 'img/CREME_BRULEE.jpeg',
        category: 'desserts'
    },
    {
        id: 402,
        name: 'Fondant Chocolat',
        description: 'Fondant au chocolat coulant',
        price: 3.90,
        image: 'img/Fondant.jpg',
        category: 'desserts'
    },
    {
        id: 403,
        name: 'Tiramisu',
        description: 'Tiramisu maison',
        price: 4.90,
        image: 'img/TIRAMISU_CHOCO_SPECULOS.jpg',
        category: 'desserts'
    },
    {
        id: 404,
        name: 'Mousse au Chocolat',
        description: 'Mousse au chocolat maison',
        price: 3.90,
        image: 'img/MOUSSE_CHOCOLAT.jpg',
        category: 'desserts'
    }
];

// ========================================
// DONNÉES DES FORMULES
// ========================================
const FORMULES_DATA = {
    midi: {
        name: 'Formule Midi',
        description: 'Pizza 26cm + Boisson 33cl offerte',
        price: 10.90,
        priceExtra: 1.00, // +1€ pour Burger, Américaine ou Pizza du mois
        items: ['Pizza 26cm au choix*', 'Boisson 33cl offerte'],
        note: '*Sauf Burger, Américaine et Pizza du mois (+1€)',
        available: 'midi',
        excludedPizzas: [17, 26] // IDs des pizzas Burger et Américaine
    },
    patesSalade: {
        name: 'Menu Pâtes/Salade',
        description: '1 Pâte ou Salade + 1 Dessert + 1 Boisson',
        priceL: 12.80,
        priceXL: 15.80,
        items: ['1 Pâte (L) ou Salade', '1 Dessert', '1 Boisson 33cl offerte'],
        note: '+3€ pour pâtes XL'
    },
    promo2pizzas: {
        name: 'Promo Soir - 2 Pizzas',
        description: '2 Pizzas achetées = 1 Margherita offerte OU 2 Boissons 33cl offertes',
        type: 'promotion',
        items: ['2 Pizzas au choix', 'Au choix: 1 Margherita offerte OU 2 Boissons 33cl'],
        available: 'soir',
        triggerQuantity: 2 // Se déclenche automatiquement à partir de 2 pizzas
    }
};

// ========================================
// EXTRAS & OPTIONS
// ========================================
const EXTRAS = {
    sizes: {
        petite: { name: 'Marmaille (26cm)', price: 0 }, // Pour Marmaille uniquement
        moyenne: { name: '33cm', price: 0 }, // Prix par défaut (price33)
        grande: { name: '40cm', price: 0 } // Utilise price40 directement
    },
    patesSizes: {
        L: { name: 'L', price: 0 }, // Prix de base
        XL: { name: 'XL', price: 3.00 } // +3€ pour XL
    },
    patesBases: {
        classique: { name: 'Classique', price: 2 },
        ricottaEpinard: { name: 'Ricotta Épinard', price: 2 },
        gnocchi: { name: 'Gnocchi', price: 2 }
    },
    patesSupplements: {
        L: { name: 'Supplément L', price: 1.00 },
        XL: { name: 'Supplément XL', price: 1.50 }
    },
    bases: {
        tomate: { name: 'Sauce Tomate', price: 0 },
        creme: { 
            name: 'Crème fraîche', 
            price33: 1.00, // +1€ en 33cm
            price40: 1.50  // +1.50€ en 40cm
        }
    },
    toppings: {
        // Option spéciale
        maxiGarniture: { name: 'Maxi Garniture', price: 3.00 },
        
        // Légumes
        champignons: { name: 'Champignons frais', price: 1.50 },
        olives: { name: 'Olives', price: 1.00 },
        poivrons: { name: 'Poivrons', price: 1.50 },
        oignons: { name: 'Oignons', price: 1.00 },
        tomates: { name: 'Tomates fraîches', price: 1.00 },
        pommesDeTerre: { name: 'Pommes de terre', price: 1.50 },
        mais: { name: 'Maïs', price: 1.00 },
        capres: { name: 'Câpres', price: 1.00 },
        grosPiment: { name: 'Gros piment', price: 0.50 },
        salade: { name: 'Salade', price: 0.50 },
        
        // Fromages
        fromage: { name: 'Fromage supplémentaire', price: 1.50 },
        mozzarella: { name: 'Mozzarella', price: 2.00 },
        chevre: { name: 'Chèvre', price: 2.00 },
        emmental: { name: 'Emmental', price: 1.50 },
        roquefort: { name: 'Roquefort', price: 2.00 },
        raclette: { name: 'Raclette', price: 2.00 },
        reblochon: { name: 'Reblochon', price: 2.00 },
        cheddar: { name: 'Cheddar', price: 2.00 },
        gorgonzola: { name: 'Gorgonzola', price: 2.00 },
        parmesan: { name: 'Parmesan', price: 2.00 },
        
        // Viandes
        chorizo: { name: 'Chorizo', price: 2.00 },
        jambon: { name: 'Jambon/Épaule', price: 2.00 },
        merguez: { name: 'Merguez poulet', price: 2.00 },
        poulet: { name: 'Poulet', price: 2.00 },
        pouletFume: { name: 'Poulet fumé', price: 2.00 },
        sarcivePoulet: { name: 'Sarcive poulet', price: 2.00 },
        saucisseFumee: { name: 'Saucisse fumée poulet', price: 2.00 },
        boeuf: { name: 'Bœuf haché', price: 2.00 },
        lardons: { name: 'Lardons', price: 2.00 },
        
        // Produits de la mer
        thon: { name: 'Thon', price: 2.50 },
        anchois: { name: 'Anchois', price: 2.00 },
        crevettes: { name: 'Crevettes', price: 3.00 },
        saumon: { name: 'Saumon fumé', price: 3.00 },
        
        // Autres
        oeuf: { name: 'Œuf', price: 1.00 },
        miel: { name: 'Miel', price: 0.50 },
        creme: { name: 'Crème fraîche', price: 1.00 },
        citron: { name: 'Citron', price: 0.50 }
    },
    americaine: {
        bases: {
            jambon: { name: 'Jambon', price: 0 },
            sarcive: { name: 'Sarcive', price: 0 },
            merguez: { name: 'Merguez', price: 0 },
            thon: { name: 'Thon', price: 0 },
            saumon: { name: 'Saumon', price: 1.00 },
            boeuf: { name: 'Bœuf', price: 1.00 },
            poulet: { name: 'Poulet', price: 1.00 },
            fromages3: { name: '3 Fromages', price: 1.00 }
        },
        sauces: {
            tunisienne: { name: 'Tunisienne' },
            barbecue: { name: 'Barbecue' },
            algerienne: { name: 'Algérienne' },
            brazil: { name: 'Brazil' },
            ketchup: { name: 'Ketchup' },
            mayonnaise: { name: 'Mayonnaise' }
        }
    }
};

// ========================================
// DONNÉES DES BOISSONS
// ========================================
const BOISSONS_DATA = [
    { id: 1, name: 'Coca-Cola', size: '33cl', price: 2.00 },
    { id: 2, name: 'Thé Pêche', size: '33cl', price: 2.00 },
    { id: 3, name: 'Thé Melon', size: '33cl', price: 2.00 },
    { id: 4, name: 'Sambo', size: '33cl', price: 2.00 },
    { id: 5, name: 'Edena', size: '33cl', price: 2.00 },
    { id: 6, name: 'Cilaos', size: '33cl', price: 2.00 }
];
