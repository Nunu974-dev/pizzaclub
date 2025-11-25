// ========================================
// ÉTAT DE L'APPLICATION
// ========================================
let cart = [];
let currentPizza = null;
let currentStep = 1;
let customerData = {};
let orderNumber = null;

// Variables globales pour l'heure de livraison
let deliveryTimeMode = 'maintenant'; // 'maintenant' ou 'programmee'
let scheduledDeliveryHour = null; // Heure programmée (ex: 19)
let scheduledDeliveryDate = null; // Date programmée (ex: '2024-01-15')
let deliveryTimeSet = false; // True si le client a déjà choisi
let pendingCartAction = null; // Action en attente après sélection de l'heure

// Variables pour le code promo
let promoCodeApplied = null; // Code promo actuellement appliqué
let promoDiscount = 0; // Montant de la réduction

// ========================================
// GESTION DU LOGO ADAPTATIF (MODE SOMBRE/CLAIR)
// ========================================
function updateLogo() {
    const logo = document.querySelector('.logo');
    if (!logo) return;
    
    // Détecter le mode sombre
    const isDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    // Changer le logo en fonction du mode
    logo.src = isDarkMode ? 'img/New logo blanc 2022.png' : 'img/New logo noir 2022.png';
}

// ========================================
// INITIALISATION
// ========================================
document.addEventListener('DOMContentLoaded', () => {
    loadCartFromStorage(); // Charger le panier EN PREMIER
    initApp(); // Puis initialiser avec les préférences
    updateLogo(); // Mettre à jour le logo selon le mode
    
    // Charger tous les produits
    renderPizzas();
    renderPates();
    renderSalades();
    renderBuns();
    renderRolls();
    renderDesserts();
    
    setupEventListeners();
    
    // Écouter les changements de mode sombre/clair
    if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', updateLogo);
    }
    
    // Vérifier la disponibilité de la formule midi
    updateFormuleMidiAvailability();
});

function initApp() {
    console.log('Pizza Club - Application initialisée');
    updateCartUI();
    
    // Charger les préférences de livraison depuis le localStorage
    const savedMode = localStorage.getItem('deliveryTimeMode');
    const savedHour = localStorage.getItem('scheduledDeliveryHour');
    const savedDate = localStorage.getItem('scheduledDeliveryDate');
    const savedDeliveryTimeSet = localStorage.getItem('deliveryTimeSet');
    const savedPromoApplied = localStorage.getItem('promoApplied');
    
    console.log('initApp - savedMode:', savedMode, 'cart.length:', cart.length, 'savedDeliveryTimeSet:', savedDeliveryTimeSet, 'savedPromoApplied:', savedPromoApplied);
    
    // Si le panier est vide, réinitialiser deliveryTimeSet
    if (cart.length === 0) {
        console.log('Panier vide - réinitialisation de deliveryTimeSet');
        deliveryTimeSet = false;
        deliveryTimeMode = 'maintenant';
        scheduledDeliveryHour = null;
        scheduledDeliveryDate = null;
        promoApplied = false;
        localStorage.removeItem('deliveryTimeSet');
        localStorage.removeItem('promoApplied');
    } else if (savedMode && savedDeliveryTimeSet === 'true') {
        // Si le panier a des items, charger les préférences sauvegardées
        deliveryTimeMode = savedMode;
        deliveryTimeSet = true;
        
        if (savedHour) {
            scheduledDeliveryHour = parseInt(savedHour);
        }
        
        if (savedDate) {
            scheduledDeliveryDate = savedDate;
        }
        
        // Charger promoApplied
        promoApplied = savedPromoApplied === 'true';
        
        console.log('Préférences chargées - mode:', deliveryTimeMode, 'hour:', scheduledDeliveryHour, 'promoApplied:', promoApplied);
    } else if (cart.length > 0) {
        // Si le panier a des items mais pas de savedDeliveryTimeSet (ancien localStorage)
        // Considérer que l'heure est définie par défaut
        deliveryTimeSet = true;
        deliveryTimeMode = savedMode || 'maintenant';
        if (savedHour) {
            scheduledDeliveryHour = parseInt(savedHour);
        }
        if (savedDate) {
            scheduledDeliveryDate = savedDate;
        }
        // Charger promoApplied
        promoApplied = savedPromoApplied === 'true';
        // Sauvegarder pour la prochaine fois
        localStorage.setItem('deliveryTimeSet', 'true');
        console.log('Panier non vide - deliveryTimeSet activé par défaut, promoApplied:', promoApplied);
    }
}

// ========================================
// EVENT LISTENERS
// ========================================
function setupEventListeners() {
    // Navigation mobile
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');
    
    if (navToggle) {
        navToggle.addEventListener('click', () => {
            navMenu.classList.toggle('active');
        });
    }

    // Fermer le menu mobile lors du clic sur un lien
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', () => {
            navMenu.classList.remove('active');
        });
    });

    // Panier
    document.getElementById('btnCart').addEventListener('click', openCart);
    document.getElementById('cartClose').addEventListener('click', closeCart);
    document.getElementById('btnCheckout').addEventListener('click', openCheckoutModal);

    // Catégories principales - scroll vers les sections
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const category = e.currentTarget.dataset.category;
            document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
            e.currentTarget.classList.add('active');
            
            // Scroll vers la section correspondante
            let targetSection;
            if (category === 'pizzas') {
                targetSection = document.getElementById('menu');
            } else {
                targetSection = document.getElementById(category);
            }
            
            if (targetSection) {
                targetSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                
                // Afficher/masquer les filtres pizzas
                const pizzaFilters = document.getElementById('pizzaFilters');
                if (category === 'pizzas') {
                    pizzaFilters.style.display = 'flex';
                } else {
                    pizzaFilters.style.display = 'none';
                }
            }
        });
    });

    // Filtres menu pizzas
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            e.target.classList.add('active');
            filterPizzas(e.target.dataset.filter);
        });
    });

    // Mode de livraison change
    document.querySelectorAll('input[name="deliveryMode"]').forEach(input => {
        input.addEventListener('change', updateDeliveryMode);
    });

    // Smooth scroll
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // Empêcher la fermeture du panier au clic à l'intérieur
    const cartSidebar = document.getElementById('cartSidebar');
    if (cartSidebar) {
        cartSidebar.addEventListener('click', (e) => {
            e.stopPropagation();
        });
    }

    // Fermer le panier au clic à l'extérieur
    document.addEventListener('click', (e) => {
        const cartSidebar = document.getElementById('cartSidebar');
        const btnCart = document.getElementById('btnCart');
        
        if (cartSidebar && btnCart && 
            cartSidebar.classList.contains('active') && 
            !cartSidebar.contains(e.target) && 
            !btnCart.contains(e.target)) {
            closeCart();
        }
    });

    // Fermeture des modals au clic sur l'overlay
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal-overlay')) {
            closeCustomizeModal();
            closeCheckoutModal();
            closeConfirmationModal();
            closePatesCustomizeModal();
            closeSaladeCustomizeModal();
            closeBunsCustomizeModal();
            closeRollsCustomizeModal();
            closeAmericaineCustomizeModal();
            closePromoModal();
            closeFormuleMidiModal();
            closeMenuPatesSaladeModal();
            // Ne pas fermer deliveryTimeModal sur overlay click (obligatoire)
        }
    });
    
    // Event listeners pour le modal d'heure de livraison
    const deliveryTimeRadios = document.querySelectorAll('input[name="globalDeliveryTime"]');
    deliveryTimeRadios.forEach(radio => {
        radio.addEventListener('change', toggleGlobalScheduledTime);
    });
}

// ========================================
// RENDU DES PIZZAS
// ========================================
function renderPizzas(filter = 'all') {
    const menuGrid = document.getElementById('menuGrid');
    menuGrid.innerHTML = '';

    if (filter === 'all') {
        // Afficher par catégorie avec des titres
        const categories = ['creme', 'poulet', 'boeuf', 'porc', 'enfant', 'vegetarienne', 'mer'];
        
        categories.forEach(category => {
            const pizzasInCategory = PIZZAS_DATA.filter(pizza => pizza.category === category);
            
            if (pizzasInCategory.length > 0) {
                // Créer un titre de catégorie
                const categoryInfo = CATEGORY_ICONS[category];
                const categoryHeader = document.createElement('div');
                categoryHeader.className = 'category-header';
                categoryHeader.innerHTML = `
                    <i class="fas ${categoryInfo.icon}" style="color: ${categoryInfo.color}"></i>
                    <h3>Pizzas ${categoryInfo.label}</h3>
                `;
                menuGrid.appendChild(categoryHeader);

                // Ajouter les pizzas de cette catégorie
                pizzasInCategory.forEach(pizza => {
                    const card = createPizzaCard(pizza);
                    menuGrid.appendChild(card);
                });
            }
        });
    } else {
        // Afficher seulement la catégorie filtrée avec son titre
        const filteredPizzas = PIZZAS_DATA.filter(pizza => pizza.category === filter);
        
        if (filteredPizzas.length > 0) {
            // Ajouter le titre de la catégorie
            const categoryInfo = CATEGORY_ICONS[filter];
            const categoryHeader = document.createElement('div');
            categoryHeader.className = 'category-header';
            categoryHeader.innerHTML = `
                <i class="fas ${categoryInfo.icon}" style="color: ${categoryInfo.color}"></i>
                <h3>Pizzas ${categoryInfo.label}</h3>
            `;
            menuGrid.appendChild(categoryHeader);
            
            // Ajouter les pizzas
            filteredPizzas.forEach(pizza => {
                const card = createPizzaCard(pizza);
                menuGrid.appendChild(card);
            });
        }
    }
}

function createPizzaCard(pizza) {
    const card = document.createElement('div');
    card.className = 'pizza-card';
    card.dataset.category = pizza.category;

    // Récupérer l'icône de catégorie
    const categoryInfo = CATEGORY_ICONS[pizza.category];
    const categoryIcon = categoryInfo ? `<i class="fas ${categoryInfo.icon} category-icon" style="color: ${categoryInfo.color}"></i>` : '';
    
    // Créer les badges spéciaux (épicé, premium)
    let specialBadges = '';
    if (pizza.isSpicy) {
        specialBadges += '<span class="special-badge badge-spicy"><i class="fas fa-pepper-hot"></i> Forte</span>';
    }
    if (pizza.isPremium) {
        specialBadges += '<span class="special-badge badge-premium"><i class="fas fa-star"></i> Premium</span>';
    }

    card.innerHTML = `
        <div class="pizza-image">
            <img src="${pizza.image}" alt="${pizza.name}">
            ${pizza.badge ? `<div class="pizza-badge">${pizza.badge}</div>` : ''}
        </div>
        <div class="pizza-content">
            <div class="pizza-header">
                <h3 class="pizza-title">
                    ${categoryIcon}
                    ${pizza.name}
                </h3>
                ${specialBadges ? `<div class="special-badges">${specialBadges}</div>` : ''}
                <p class="pizza-ingredients">${pizza.ingredients.join(', ')}</p>
            </div>
            <div class="pizza-footer">
                <div class="pizza-price">${pizza.price33.toFixed(2)}€</div>
                <div class="pizza-actions">
                    <button class="btn btn-primary btn-block" onclick="openCustomizeModal(${pizza.id})">
                        <i class="fas fa-pizza-slice"></i> Personnaliser & Commander
                    </button>
                </div>
            </div>
        </div>
    `;

    return card;
}

function filterPizzas(category) {
    renderPizzas(category);
}

// ========================================
// RENDU DES PÂTES, SALADES, BUNS, DESSERTS
// ========================================
function renderPates() {
    const patesGrid = document.getElementById('patesGrid');
    if (!patesGrid || !PATES_DATA) return;
    
    patesGrid.innerHTML = '';
    PATES_DATA.forEach(item => {
        const card = createSimpleCard(item, 'pate');
        patesGrid.appendChild(card);
    });
}

function renderSalades() {
    const saladesGrid = document.getElementById('saladesGrid');
    if (!saladesGrid || !SALADES_DATA) return;
    
    saladesGrid.innerHTML = '';
    SALADES_DATA.forEach(item => {
        const card = createSimpleCard(item, 'salade');
        saladesGrid.appendChild(card);
    });
}

function renderBuns() {
    const bunsGrid = document.getElementById('bunsGrid');
    if (!bunsGrid || !BUNS_DATA) return;
    
    bunsGrid.innerHTML = '';
    BUNS_DATA.forEach(item => {
        const card = createSimpleCard(item, 'bun');
        bunsGrid.appendChild(card);
    });
}

function renderRolls() {
    const rollsGrid = document.getElementById('rollsGrid');
    if (!rollsGrid || !ROLLS_DATA) return;
    
    rollsGrid.innerHTML = '';
    ROLLS_DATA.forEach(item => {
        const card = createSimpleCard(item, 'roll');
        rollsGrid.appendChild(card);
    });
}

function renderDesserts() {
    const dessertsGrid = document.getElementById('dessertsGrid');
    if (!dessertsGrid || !DESSERTS_DATA) return;
    
    dessertsGrid.innerHTML = '';
    DESSERTS_DATA.forEach(item => {
        const card = createSimpleCard(item, 'dessert');
        dessertsGrid.appendChild(card);
    });
}

function createSimpleCard(item, type) {
    const card = document.createElement('div');
    card.className = 'pizza-card';
    
    // Pour les pâtes et salades, afficher seulement le prix de base
    const isPate = type === 'pate';
    const isSalade = type === 'salade';
    const isBun = type === 'bun';
    const isRoll = type === 'roll';
    
    let priceDisplay;
    if (isPate) {
        priceDisplay = `<div class="pizza-price">${item.priceL.toFixed(2)}€</div>`;
    } else if (isBun) {
        priceDisplay = `<div class="pizza-price">À partir de ${item.price.toFixed(2)}€</div>`;
    } else if (isRoll && !item.isBox) {
        priceDisplay = `<div class="pizza-price">${item.price.toFixed(2)}€</div>`;
    } else {
        priceDisplay = `<div class="pizza-price">${item.price.toFixed(2)}€</div>`;
    }
    
    // Boutons selon le type
    let buttonHTML;
    if (isPate) {
        buttonHTML = `<button class="btn btn-primary btn-block" onclick="openPatesCustomizeModal(${item.id})">
                <i class="fas fa-utensils"></i> Commander
           </button>`;
    } else if (isSalade) {
        buttonHTML = `<button class="btn btn-primary btn-block" onclick="openSaladeCustomizeModal(${item.id})">
                <i class="fas fa-leaf"></i> Commander
           </button>`;
    } else if (isBun) {
        buttonHTML = `<button class="btn btn-primary btn-block" onclick="openBunsCustomizeModal(${item.id})">
                <i class="fas fa-hamburger"></i> Commander
           </button>`;
    } else if (isRoll) {
        buttonHTML = `<button class="btn btn-primary btn-block" onclick="openRollsCustomizeModal(${item.id})">
                <i class="fas fa-utensils"></i> Personnaliser
           </button>`;
    } else {
        buttonHTML = `<button class="btn btn-primary btn-block" onclick="addSimpleItemToCart(${item.id}, '${type}')">
                <i class="fas fa-shopping-cart"></i> Ajouter au panier
           </button>`;
    }
    
    card.innerHTML = `
        <div class="pizza-image">
            <img src="${item.image}" alt="${item.name}">
        </div>
        <div class="pizza-content">
            <div class="pizza-header">
                <h3 class="pizza-title">${item.name}</h3>
                <p class="pizza-ingredients">${item.description}</p>
            </div>
            <div class="pizza-footer">
                ${priceDisplay}
                <div class="pizza-actions">
                    ${buttonHTML}
                </div>
            </div>
        </div>
    `;

    return card;
}

function addSimpleItemToCart(itemId, type) {
    // Si c'est le premier ajout, s'assurer que l'heure est définie
    if (cart.length === 0 && !deliveryTimeSet) {
        console.log('Premier ajout - ouverture modal heure avant ajout au panier');
        pendingCartAction = () => addSimpleItemToCart(itemId, type);
        openDeliveryTimeModal();
        return;
    }
    
    let item;
    let itemData;
    
    switch(type) {
        case 'pate':
            itemData = PATES_DATA;
            break;
        case 'salade':
            itemData = SALADES_DATA;
            break;
        case 'bun':
            itemData = BUNS_DATA;
            break;
        case 'roll':
            itemData = ROLLS_DATA;
            break;
        case 'dessert':
            itemData = DESSERTS_DATA;
            break;
        default:
            return;
    }
    
    item = itemData.find(i => i.id === itemId);
    if (!item) return;
    
    const cartItem = {
        id: Date.now(),
        itemId: item.id,
        name: item.name,
        type: type,
        quantity: 1,
        basePrice: item.price,
        totalPrice: item.price
    };

    cart.push(cartItem);
    saveCartToStorage();
    updateCartUI();
    showNotification(`${item.name} ajouté${type === 'salade' ? 'e' : ''} au panier`);
    setTimeout(() => openCart(), 100);
}

// ========================================
// GESTION DES QUANTITÉS
// ========================================
function increaseQty(pizzaId) {
    const input = document.getElementById(`qty-${pizzaId}`);
    if (input.value < 10) {
        input.value = parseInt(input.value) + 1;
    }
}

function decreaseQty(pizzaId) {
    const input = document.getElementById(`qty-${pizzaId}`);
    if (input.value > 1) {
        input.value = parseInt(input.value) - 1;
    }
}

function increaseCustomizeQty() {
    const input = document.getElementById('customizeQty');
    if (input.value < 10) {
        input.value = parseInt(input.value) + 1;
        updateCustomizePrice();
    }
}

function decreaseCustomizeQty() {
    const input = document.getElementById('customizeQty');
    if (input.value > 1) {
        input.value = parseInt(input.value) - 1;
        updateCustomizePrice();
    }
}

// ========================================
// PANIER
// ========================================
function addToCart(pizzaId, customization = null) {
    console.log('addToCart called, cart length:', cart.length, 'deliveryTimeSet:', deliveryTimeSet);
    
    // Si c'est le premier ajout, s'assurer que l'heure est définie AVANT d'ajouter
    if (cart.length === 0 && !deliveryTimeSet) {
        console.log('Premier ajout détecté - ouverture modal heure avant ajout au panier');
        pendingCartAction = () => addToCart(pizzaId, customization);
        openDeliveryTimeModal();
        return;
    }
    
    console.log('Ajout au panier sans modal (deliveryTimeSet:', deliveryTimeSet, ')');
    
    const pizza = PIZZAS_DATA.find(p => p.id === pizzaId);
    const qtyInput = document.getElementById(`qty-${pizzaId}`);
    const quantity = parseInt(qtyInput ? qtyInput.value : 1);

    const cartItem = {
        id: Date.now(),
        type: 'pizza',
        pizzaId: pizza.id,
        name: pizza.name,
        basePrice: pizza.price33,
        pizza: pizza, // Stocker l'objet pizza complet pour avoir accès à price40
        quantity: quantity,
        customization: customization || {
            size: 'moyenne', // 33cm par défaut
            base: 'tomate',
            removedIngredients: [],
            addedIngredients: []
        },
        totalPrice: calculateItemPrice(pizza.price33, customization, quantity, pizza)
    };

    cart.push(cartItem);
    saveCartToStorage();
    updateCartUI();
    
    // Reset quantity
    if (qtyInput) qtyInput.value = 1;

    // Afficher notification
    showNotification(`${pizza.name} ajoutée au panier`);
    
    // Ouvrir automatiquement le panier
    setTimeout(() => openCart(), 100);
}

function addFormuleToCart(formuleType) {
    // Si c'est le premier ajout, s'assurer que l'heure est définie
    if (cart.length === 0 && !deliveryTimeSet) {
        console.log('Premier ajout - ouverture modal heure avant ajout au panier');
        pendingCartAction = () => addFormuleToCart(formuleType);
        openDeliveryTimeModal();
        return;
    }
    
    // Vérifier l'heure pour la formule midi
    if (formuleType === 'midi') {
        const deliveryHour = getDeliveryHour();
        
        // Formule midi disponible de 11h à 14h
        if (deliveryHour < 11 || deliveryHour >= 14) {
            // Si le panier est vide, permettre de changer l'heure
            if (cart.length === 0) {
                showNotification('La formule midi est disponible uniquement de 11h à 14h. Modifiez votre horaire de livraison.', 'error');
                // Réinitialiser pour permettre de choisir une autre heure
                deliveryTimeSet = false;
                updateFormuleMidiAvailability();
            } else {
                showNotification('La formule midi est disponible uniquement de 11h à 14h', 'error');
            }
            return;
        }
        openFormuleMidiModal();
    } else if (formuleType === 'patesSalade') {
        openMenuPatesSaladeModal();
    } else {
        // Pour la promo 2 pizzas, comportement automatique (pas de modal)
        const formule = FORMULES_DATA[formuleType];
        if (!formule) {
            showNotification('Formule non disponible');
            return;
        }
        
        const cartItem = {
            id: Date.now(),
            type: 'formule',
            formuleType: formuleType,
            name: formule.name || 'Formule',
            description: formule.description || '',
            quantity: 1,
            totalPrice: formule.price || 0
        };
        
        cart.push(cartItem);
        saveCartToStorage();
        updateCartUI();
        showNotification(`${cartItem.name} ajoutée au panier`);
        setTimeout(() => openCart(), 100);
    }
}

function calculateItemPrice(basePrice, customization, quantity = 1, pizza = null) {
    if (!customization) return basePrice * quantity;

    let price = basePrice;

    // Gérer le prix selon la taille (33cm par défaut, 40cm si sélectionné)
    if (customization.size === 'grande' && pizza && pizza.price40) {
        // Utiliser le prix spécifique 40cm de la pizza
        price = pizza.price40;
    } else if (customization.size === 'petite' && pizza && pizza.price26) {
        // Pour les pizzas qui ont un prix 26cm (Marmaille)
        price = pizza.price26;
    }

    // Ajouter le supplément pour base crème
    if (customization.base === 'creme') {
        if (customization.size === 'grande') {
            price += 1.50; // +1.50€ pour crème en 40cm
        } else if (customization.size === 'moyenne') {
            price += 1.00; // +1€ pour crème en 33cm
        }
        // Pas de supplément pour Marmaille (26cm)
    }

    // Ajouter prix des ingrédients supplémentaires
    if (customization.addedIngredients) {
        customization.addedIngredients.forEach(ingredient => {
            if (EXTRAS.toppings[ingredient]) {
                price += EXTRAS.toppings[ingredient].price;
            }
        });
    }

    return price * quantity;
}

function updateCartItemQty(itemId, change) {
    const item = cart.find(i => i.id === itemId);
    if (item) {
        const wasPizza = item.type === 'pizza';
        item.quantity += change;
        if (item.quantity < 1) {
            removeFromCart(itemId);
        } else {
            item.totalPrice = item.customization 
                ? calculateItemPrice(item.basePrice, item.customization, item.quantity, item.pizza)
                : item.totalPrice / (item.quantity - change) * item.quantity;
            saveCartToStorage();
            updateCartUI();
            
            // Vérifier la promo si c'est une pizza
            if (wasPizza) {
                setTimeout(() => checkPromo2Pizzas(), 300);
            }
        }
    }
}

function removeFromCart(itemId) {
    // Vérifier si c'est une promo qui est retirée
    const itemToRemove = cart.find(item => item.id === itemId);
    if (itemToRemove && itemToRemove.type === 'promo2pizzas') {
        promoApplied = false; // Permettre de réafficher la promo si on a toujours 2 pizzas
    }
    
    cart = cart.filter(item => item.id !== itemId);
    saveCartToStorage();
    updateCartUI();
    showNotification('Article retiré du panier');
    
    // Revérifier la promo après suppression
    if (itemToRemove && (itemToRemove.type === 'pizza' || itemToRemove.type === 'promo2pizzas')) {
        setTimeout(() => checkPromo2Pizzas(), 300);
    }
}

function clearCart() {
    cart = [];
    promoApplied = false;
    deliveryTimeSet = false;
    deliveryTimeMode = 'maintenant';
    scheduledDeliveryHour = null;
    scheduledDeliveryDate = null;
    promoCodeApplied = null;
    promoDiscount = 0;
    localStorage.removeItem('promoApplied');
    localStorage.removeItem('deliveryTimeSet');
    localStorage.removeItem('deliveryTimeMode');
    localStorage.removeItem('scheduledDeliveryHour');
    localStorage.removeItem('scheduledDeliveryDate');
    localStorage.removeItem('promoCodeApplied');
    
    // Réactiver le champ de code promo
    const input = document.getElementById('promoCodeInput');
    const btn = document.getElementById('btnApplyPromo');
    if (input && btn) {
        input.value = '';
        input.disabled = false;
        btn.disabled = false;
    }
    document.getElementById('promoMessage').style.display = 'none';
    
    saveCartToStorage();
    updateCartUI();
}

function updateCartUI() {
    const cartCount = document.getElementById('cartCount');
    const cartEmpty = document.getElementById('cartEmpty');
    const cartItems = document.getElementById('cartItems');
    const cartFooter = document.getElementById('cartFooter');

    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    cartCount.textContent = totalItems;

    if (cart.length === 0) {
        cartEmpty.style.display = 'block';
        cartItems.innerHTML = '';
        cartFooter.style.display = 'none';
    } else {
        cartEmpty.style.display = 'none';
        cartFooter.style.display = 'block';
        renderCartItems();
        updateCartTotals();
        
        // Vérifier si le client a 2 pizzas pour la promo soir
        checkPromo2Pizzas();
    }
}

function renderCartItems() {
    const cartItems = document.getElementById('cartItems');
    cartItems.innerHTML = '';

    cart.forEach(item => {
        const itemElement = createCartItemElement(item);
        cartItems.appendChild(itemElement);
    });
}

function createCartItemElement(item) {
    const div = document.createElement('div');
    div.className = 'cart-item';

    let detailsHTML = '';
    
    // Gestion spéciale pour L'Américaine
    if (item.type === 'americaine' && item.customization) {
        const details = [];
        
        // Taille
        if (item.customization.size) {
            const sizeLabel = item.customization.size === 'moyenne' ? '33cm' : '40cm';
            details.push(`Taille: ${sizeLabel}`);
        }
        
        // Base de viande
        if (item.customization.base) {
            const baseLabels = {
                jambon: 'Jambon',
                sarcive: 'Sarcive',
                merguez: 'Merguez',
                thon: 'Thon',
                saumon: 'Saumon',
                boeuf: 'Bœuf',
                poulet: 'Poulet',
                fromages3: '3 Fromages'
            };
            const baseLabel = baseLabels[item.customization.base] || item.customization.base;
            details.push(`Base: ${baseLabel}`);
        }
        
        // Sauces
        if (item.customization.sauces && item.customization.sauces.length > 0) {
            const sauceLabels = {
                tunisienne: 'Tunisienne',
                barbecue: 'Barbecue',
                algerienne: 'Algérienne',
                brazil: 'Brazil',
                ketchup: 'Ketchup',
                mayonnaise: 'Mayonnaise'
            };
            const sauceNames = item.customization.sauces.map(s => sauceLabels[s] || s);
            details.push(`Sauces: ${sauceNames.join(' + ')}`);
        }
        
        // Ingrédients retirés
        if (item.customization.removedIngredients?.length > 0) {
            details.push(`Sans: ${item.customization.removedIngredients.join(', ')}`);
        }
        
        // Ingrédients ajoutés
        if (item.customization.addedIngredients?.length > 0) {
            details.push(`Avec: ${item.customization.addedIngredients.join(', ')}`);
        }
        
        detailsHTML = `<div class="cart-item-details">${details.join(' • ')}</div>`;
    }
    // Gestion pour les pâtes personnalisées
    else if (item.type === 'pate' && item.customization) {
        const details = [];
        if (item.customization.size) {
            details.push(`Taille: ${item.customization.size}`);
        }
        if (item.customization.base) {
            const baseLabels = {
                classique: 'Classique',
                ricottaEpinard: 'Ricotta Épinard',
                gnocchi: 'Gnocchi'
            };
            details.push(`Base: ${baseLabels[item.customization.base] || item.customization.base}`);
        }
        if (item.customization.hasSupplement) {
            details.push('Avec supplément');
        }
        detailsHTML = `<div class="cart-item-details">${details.join(' • ')}</div>`;
    }
    // Gestion pour les salades personnalisées
    else if (item.type === 'salade' && item.customization) {
        const details = [];
        if (item.customization.base) {
            details.push(`Base: ${item.customization.base}`);
        }
        if (item.customization.extras && item.customization.extras.length > 0) {
            const extraNames = item.customization.extras.map(e => {
                if (e.name === 'supplement') return 'Supplément';
                if (e.name === 'vinaigrette') return 'Vinaigrette';
                if (e.name === 'pain') return 'Pain';
                return e.name;
            });
            details.push(`Extras: ${extraNames.join(', ')}`);
        }
        detailsHTML = `<div class="cart-item-details">${details.join(' • ')}</div>`;
    }
    // Gestion pour les buns personnalisés
    else if (item.type === 'bun' && item.customization) {
        const details = [];
        details.push(`Quantité: ${item.customization.quantity}`);
        if (item.customization.base !== 'none') {
            const baseLabel = item.customization.base === 'creme' ? 'Crème' : 'Tomate';
            details.push(`Base: ${baseLabel}`);
        }
        detailsHTML = `<div class="cart-item-details">${details.join(' • ')}</div>`;
    }
    // Gestion pour les rolls personnalisés
    else if (item.type === 'roll' && item.customization) {
        const details = [];
        if (item.customization.base) {
            const baseLabel = item.customization.base === 'creme' ? 'Crème' : 'Tomate';
            details.push(`Base: ${baseLabel}`);
        }
        if (item.customization.ingredients && item.customization.ingredients.length > 0) {
            details.push(`Ingrédients: ${item.customization.ingredients.join(', ')}`);
        }
        detailsHTML = `<div class="cart-item-details">${details.join(' • ')}</div>`;
    }
    // Gestion pour les formules personnalisées
    else if (item.type === 'formule' && item.customization) {
        const details = [];
        if (item.customization.pizza) {
            details.push(`Pizza: ${item.customization.pizza}`);
        }
        if (item.customization.mainItem) {
            details.push(item.customization.mainItem);
        }
        if (item.customization.dessert) {
            details.push(`Dessert: ${item.customization.dessert}`);
        }
        if (item.customization.boisson) {
            details.push(`Boisson: ${item.customization.boisson}`);
        }
        detailsHTML = `<div class="cart-item-details">${details.join(' • ')}</div>`;
    }
    // Gestion normale pour les autres pizzas personnalisées
    else if (item.customization) {
        const details = [];
        if (item.customization.size) {
            details.push(`Taille: ${EXTRAS.sizes[item.customization.size]?.name || item.customization.size}`);
        }
        if (item.customization.base) {
            details.push(`Base: ${EXTRAS.bases[item.customization.base]?.name || item.customization.base}`);
        }
        if (item.customization.removedIngredients?.length > 0) {
            details.push(`Sans: ${item.customization.removedIngredients.join(', ')}`);
        }
        if (item.customization.addedIngredients?.length > 0) {
            details.push(`Avec: ${item.customization.addedIngredients.join(', ')}`);
        }
        detailsHTML = `<div class="cart-item-details">${details.join(' • ')}</div>`;
    } else if (item.description) {
        detailsHTML = `<div class="cart-item-details">${item.description}</div>`;
    }

    div.innerHTML = `
        <div class="cart-item-header">
            <div class="cart-item-title">${item.name}</div>
            <button class="cart-item-remove" onclick="removeFromCart(${item.id})">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        ${detailsHTML}
        <div class="cart-item-footer">
            <div class="cart-item-qty">
                <button class="cart-qty-btn" onclick="updateCartItemQty(${item.id}, -1)">
                    <i class="fas fa-minus"></i>
                </button>
                <span>${item.quantity}</span>
                <button class="cart-qty-btn" onclick="updateCartItemQty(${item.id}, 1)">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
            <div class="cart-item-price">${item.totalPrice.toFixed(2)}€</div>
        </div>
    `;

    return div;
}

function updateCartTotals() {
    const subtotal = cart.reduce((sum, item) => sum + item.totalPrice, 0);
    const deliveryFee = getDeliveryFee(subtotal);
    
    // Appliquer la réduction du code promo si valide
    let discount = 0;
    if (promoCodeApplied === 'LIV10' && subtotal >= 20) {
        discount = 2;
        promoDiscount = 2;
        document.getElementById('promoDiscountRow').style.display = 'flex';
        document.getElementById('promoDiscountAmount').textContent = `-${discount.toFixed(2)}€`;
    } else {
        promoDiscount = 0;
        document.getElementById('promoDiscountRow').style.display = 'none';
    }
    
    const total = subtotal + deliveryFee - discount;

    document.getElementById('cartSubtotal').textContent = `${subtotal.toFixed(2)}€`;
    document.getElementById('cartDeliveryFee').textContent = deliveryFee === 0 ? 'Offert' : `${deliveryFee.toFixed(2)}€`;
    document.getElementById('cartTotal').textContent = `${total.toFixed(2)}€`;
}

function getDeliveryFee(subtotal) {
    const mode = document.querySelector('input[name="deliveryMode"]:checked')?.value || 'livraison';
    
    if (mode === 'emporter') {
        return 0;
    }
    
    if (subtotal >= CONFIG.delivery.freeDeliveryThreshold) {
        return 0;
    }
    
    return CONFIG.delivery.fee;
}

// ========================================
// CODE PROMO
// ========================================
function applyPromoCode() {
    const input = document.getElementById('promoCodeInput');
    const code = input.value.trim().toUpperCase();
    
    if (!code) {
        showPromoMessage('Veuillez entrer un code promo.', 'error');
        return;
    }
    
    const subtotal = cart.reduce((sum, item) => sum + item.totalPrice, 0);
    
    // Vérifier si le code est valide
    if (code === 'LIV10') {
        if (subtotal < 20) {
            showPromoMessage('Ce code nécessite un minimum de 20€ de commande.', 'error');
            return;
        }
        
        promoCodeApplied = 'LIV10';
        promoDiscount = 2;
        localStorage.setItem('promoCodeApplied', promoCodeApplied);
        localStorage.setItem('promoDiscount', promoDiscount);
        
        input.value = '';
        input.disabled = true;
        
        const btnApply = document.getElementById('btnApplyPromo');
        if (btnApply) {
            btnApply.disabled = true;
        }
        
        showPromoMessage('Code promo appliqué ! -2€ sur votre commande', 'success');
        updateCartTotals();
        
        console.log('Code promo appliqué:', promoCodeApplied, 'Réduction:', promoDiscount);
    } else {
        showPromoMessage('Code promo invalide.', 'error');
    }
}

function showPromoMessage(message, type) {
    const messageDiv = document.getElementById('promoMessage');
    messageDiv.textContent = message;
    messageDiv.className = `promo-message ${type}`;
    messageDiv.style.display = 'block';
    
    if (type === 'error') {
        setTimeout(() => {
            messageDiv.style.display = 'none';
        }, 3000);
    }
}

function removePromoCode() {
    promoCodeApplied = null;
    promoDiscount = 0;
    localStorage.removeItem('promoCodeApplied');
    localStorage.removeItem('promoDiscount');
    
    const input = document.getElementById('promoCodeInput');
    const btnApply = document.getElementById('btnApplyPromo');
    
    if (input) input.disabled = false;
    if (btnApply) btnApply.disabled = false;
    
    const messageDiv = document.getElementById('promoMessage');
    if (messageDiv) messageDiv.style.display = 'none';
    
    updateCartTotals();
}

// ========================================
// MODAL PERSONNALISATION
// ========================================
function openCustomizeModal(pizzaId) {
    const pizza = PIZZAS_DATA.find(p => p.id === pizzaId);
    currentPizza = pizza;

    // Si c'est la Marmaille (ID 38), ouvrir le modal spécial de choix viande
    if (pizzaId === 38) {
        openMarmailleModal();
        return;
    }

    // Si c'est L'Américaine, ouvrir le modal spécial
    if (pizza.needsAmericaineCustomization) {
        openAmericaineCustomizeModal(pizzaId);
        return;
    }

    const modal = document.getElementById('customizeModal');
    const title = document.getElementById('customizeModalTitle');
    const ingredientsRemove = document.getElementById('ingredientsRemove');

    title.textContent = `Personnaliser ${pizza.name}`;

    // Gérer l'affichage de l'option 26cm (uniquement pour Marmaille)
    const sizePetiteOption = document.getElementById('size-petite-option');
    if (pizza.name.toLowerCase().includes('marmaille') || pizza.badge === 'MARMAILLE') {
        sizePetiteOption.style.display = 'flex';
        // Sélectionner 26cm par défaut pour Marmaille
        const petiteInput = sizePetiteOption.querySelector('input');
        if (petiteInput) petiteInput.checked = true;
    } else {
        sizePetiteOption.style.display = 'none';
        // Sélectionner 33cm par défaut pour les autres pizzas
        const moyenneInput = document.querySelector('input[name="size"][value="moyenne"]');
        if (moyenneInput) moyenneInput.checked = true;
    }

    // Générer les cases à cocher pour retirer des ingrédients
    ingredientsRemove.innerHTML = '';
    pizza.ingredients.forEach(ingredient => {
        const label = document.createElement('label');
        label.className = 'ingredient-checkbox';
        label.innerHTML = `
            <input type="checkbox" value="${ingredient}">
            <span>Sans ${ingredient}</span>
        `;
        ingredientsRemove.appendChild(label);
    });

    // Réinitialiser les sélections
    document.querySelectorAll('#customizeModal input[type="radio"]').forEach(input => {
        if (input.name === 'base' && input.value === 'tomate') {
            input.checked = true;
        }
    });
    document.querySelectorAll('#customizeModal input[type="checkbox"]').forEach(input => {
        input.checked = false;
    });
    document.getElementById('customizeQty').value = 1;

    updateCustomizePrice();
    modal.classList.add('active');

    // Add event listeners for price updates
    document.querySelectorAll('#customizeModal input[type="radio"], #customizeModal input[type="checkbox"]').forEach(input => {
        input.addEventListener('change', updateCustomizePrice);
    });
}

function closeCustomizeModal() {
    document.getElementById('customizeModal').classList.remove('active');
    currentPizza = null;
}

function updateCustomizePrice() {
    if (!currentPizza) return;

    let price = currentPizza.price33; // Prix de base pour 33cm
    let selectedSize = 'moyenne';

    // Calculer le prix selon la taille sélectionnée
    const sizeInput = document.querySelector('input[name="size"]:checked');
    if (sizeInput) {
        selectedSize = sizeInput.value;
        
        if (selectedSize === 'grande' && currentPizza.price40) {
            // Utiliser le prix spécifique 40cm
            price = currentPizza.price40;
        } else if (selectedSize === 'petite' && currentPizza.price26) {
            // Utiliser le prix spécifique 26cm si disponible (Marmaille)
            price = currentPizza.price26;
        }
    }

    // Ajouter le supplément pour base crème si sélectionnée
    const baseInput = document.querySelector('input[name="base"]:checked');
    if (baseInput && baseInput.value === 'creme') {
        if (selectedSize === 'grande') {
            price += 1.50; // +1.50€ pour crème en 40cm
        } else if (selectedSize === 'moyenne') {
            price += 1.00; // +1€ pour crème en 33cm
        }
        // Pas de supplément pour Marmaille (26cm)
    }

    // Ajouter le prix des ingrédients supplémentaires
    document.querySelectorAll('.ingredients-add input[type="checkbox"]:checked').forEach(checkbox => {
        price += parseFloat(checkbox.dataset.price);
    });

    // Multiplier par la quantité
    const quantity = parseInt(document.getElementById('customizeQty').value);
    price *= quantity;

    document.getElementById('customizePrice').textContent = `${price.toFixed(2)}€`;
}

function addCustomizedToCart() {
    if (!currentPizza) return;

    console.log('addCustomizedToCart - cart.length:', cart.length, 'deliveryTimeSet:', deliveryTimeSet);

    // Si c'est le premier ajout, s'assurer que l'heure est définie
    if (cart.length === 0 && !deliveryTimeSet) {
        console.log('Premier ajout détecté - ouverture modal heure avant ajout au panier');
        pendingCartAction = () => addCustomizedToCart();
        openDeliveryTimeModal();
        return;
    }

    const size = document.querySelector('input[name="size"]:checked')?.value;
    const base = document.querySelector('input[name="base"]:checked')?.value;
    const quantity = parseInt(document.getElementById('customizeQty').value);

    const removedIngredients = [];
    document.querySelectorAll('.ingredients-remove input[type="checkbox"]:checked').forEach(checkbox => {
        removedIngredients.push(checkbox.value);
    });

    const addedIngredients = [];
    document.querySelectorAll('.ingredients-add input[type="checkbox"]:checked').forEach(checkbox => {
        addedIngredients.push(checkbox.value);
    });

    const customization = {
        size,
        base,
        removedIngredients,
        addedIngredients
    };

    // Vérifier si c'est dans le cadre d'une formule midi
    if (window.pendingFormuleMidi) {
        const formuleInfo = window.pendingFormuleMidi;
        
        const cartItem = {
            id: Date.now(),
            type: 'formule',
            formuleType: 'midi',
            name: 'Formule Midi',
            basePrice: formuleInfo.basePrice,
            quantity: 1,
            totalPrice: formuleInfo.basePrice,
            customization: {
                pizza: currentPizza.name,
                pizzaCustomization: customization,
                boisson: formuleInfo.boisson
            }
        };
        
        // Nettoyer la variable temporaire
        window.pendingFormuleMidi = null;
        
        cart.push(cartItem);
        saveCartToStorage();
        updateCartUI();
        closeCustomizeModal();
        showNotification('Formule Midi ajoutée au panier');
        setTimeout(() => openCart(), 100);
        return;
    }

    const cartItem = {
        id: Date.now(),
        pizzaId: currentPizza.id,
        name: currentPizza.name,
        basePrice: currentPizza.price33,
        pizza: currentPizza, // Stocker l'objet pizza complet
        quantity: quantity,
        customization: customization,
        totalPrice: calculateItemPrice(currentPizza.price33, customization, quantity, currentPizza)
    };

    // Sauvegarder le nom avant de fermer le modal
    const pizzaName = currentPizza.name;

    cart.push(cartItem);
    saveCartToStorage();
    updateCartUI();
    closeCustomizeModal();
    showNotification(`${pizzaName} personnalisée ajoutée au panier`);
    setTimeout(() => openCart(), 100);
}

// ========================================
// MODAL PERSONNALISATION L'AMÉRICAINE
// ========================================
let currentAmericaine = null;

function openAmericaineCustomizeModal(pizzaId) {
    const pizza = PIZZAS_DATA.find(p => p.id === pizzaId);
    currentAmericaine = pizza;

    const modal = document.getElementById('americaineCustomizeModal');
    const ingredientsRemove = document.getElementById('americaineIngredientsRemove');
    
    // Réinitialiser les sélections
    document.querySelector('input[name="americaineSize"][value="moyenne"]').checked = true;
    document.querySelector('input[name="americaineBase"][value="jambon"]').checked = true;
    document.querySelectorAll('.sauce-checkbox').forEach(cb => cb.checked = false);
    document.querySelectorAll('#americaineIngredientsAdd input[type="checkbox"]').forEach(cb => cb.checked = false);
    document.getElementById('americaineQty').value = 1;
    document.getElementById('sauceWarning').style.display = 'none';

    // Générer les cases à cocher pour retirer des ingrédients
    ingredientsRemove.innerHTML = '';
    pizza.ingredients.forEach(ingredient => {
        const label = document.createElement('label');
        label.className = 'ingredient-checkbox';
        label.innerHTML = `
            <input type="checkbox" value="${ingredient}">
            <span>Sans ${ingredient}</span>
        `;
        ingredientsRemove.appendChild(label);
    });

    // Ajouter les event listeners pour les sauces
    setupAmericaineSauceListeners();
    
    // Ajouter les event listeners pour les ingrédients supplémentaires
    document.querySelectorAll('#americaineIngredientsAdd input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', updateAmericainePrice);
    });
    
    // Ajouter les event listeners pour les tailles et bases
    document.querySelectorAll('input[name="americaineSize"]').forEach(radio => {
        radio.addEventListener('change', updateAmericainePrice);
    });
    document.querySelectorAll('input[name="americaineBase"]').forEach(radio => {
        radio.addEventListener('change', updateAmericainePrice);
    });

    updateAmericainePrice();
    modal.classList.add('active');
}

function closeAmericaineCustomizeModal() {
    document.getElementById('americaineCustomizeModal').classList.remove('active');
    currentAmericaine = null;
}

function setupAmericaineSauceListeners() {
    const sauceCheckboxes = document.querySelectorAll('.sauce-checkbox');
    sauceCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const checkedCount = document.querySelectorAll('.sauce-checkbox:checked').length;
            
            // Si 2 sauces sont sélectionnées, désactiver les autres
            if (checkedCount >= 2) {
                sauceCheckboxes.forEach(cb => {
                    if (!cb.checked) {
                        cb.disabled = true;
                    }
                });
                document.getElementById('sauceWarning').style.display = 'none';
            } else {
                // Réactiver toutes les checkboxes
                sauceCheckboxes.forEach(cb => cb.disabled = false);
            }
            
            updateAmericainePrice();
        });
    });
}

function updateAmericainePrice() {
    if (!currentAmericaine) return;

    let price = currentAmericaine.price33; // Prix de base pour 33cm
    
    // Calculer le prix selon la taille sélectionnée
    const sizeInput = document.querySelector('input[name="americaineSize"]:checked');
    if (sizeInput && sizeInput.value === 'grande' && currentAmericaine.price40) {
        price = currentAmericaine.price40;
    }

    // Ajouter le supplément pour base premium (+1€)
    const baseInput = document.querySelector('input[name="americaineBase"]:checked');
    if (baseInput) {
        const basePrice = parseFloat(baseInput.dataset.price);
        price += basePrice;
    }

    // Ajouter le prix des ingrédients supplémentaires
    document.querySelectorAll('#americaineIngredientsAdd input[type="checkbox"]:checked').forEach(checkbox => {
        price += parseFloat(checkbox.dataset.price);
    });

    // Multiplier par la quantité
    const quantity = parseInt(document.getElementById('americaineQty').value);
    price *= quantity;

    document.getElementById('americainePrice').textContent = `${price.toFixed(2)}€`;
}

function increaseAmericaineQty() {
    const input = document.getElementById('americaineQty');
    let value = parseInt(input.value);
    if (value < 10) {
        input.value = value + 1;
        updateAmericainePrice();
    }
}

function decreaseAmericaineQty() {
    const input = document.getElementById('americaineQty');
    let value = parseInt(input.value);
    if (value > 1) {
        input.value = value - 1;
        updateAmericainePrice();
    }
}

function addCustomizedAmericaineToCart() {
    if (!currentAmericaine) return;

    // Si c'est le premier ajout, s'assurer que l'heure est définie
    if (cart.length === 0 && !deliveryTimeSet) {
        console.log('Premier ajout - ouverture modal heure avant ajout au panier');
        pendingCartAction = () => addCustomizedAmericaineToCart();
        openDeliveryTimeModal();
        return;
    }

    // Vérifier que 2 sauces sont sélectionnées
    const selectedSauces = Array.from(document.querySelectorAll('.sauce-checkbox:checked')).map(cb => cb.value);
    if (selectedSauces.length !== 2) {
        document.getElementById('sauceWarning').style.display = 'block';
        return;
    }

    const size = document.querySelector('input[name="americaineSize"]:checked')?.value;
    const base = document.querySelector('input[name="americaineBase"]:checked')?.value;
    const quantity = parseInt(document.getElementById('americaineQty').value);

    // Récupérer les ingrédients retirés
    const removedIngredients = [];
    document.querySelectorAll('#americaineIngredientsRemove input[type="checkbox"]:checked').forEach(checkbox => {
        removedIngredients.push(checkbox.value);
    });

    // Récupérer les ingrédients ajoutés
    const addedIngredients = [];
    document.querySelectorAll('#americaineIngredientsAdd input[type="checkbox"]:checked').forEach(checkbox => {
        addedIngredients.push(checkbox.value);
    });

    // Calculer le prix
    let basePrice = currentAmericaine.price33;
    if (size === 'grande' && currentAmericaine.price40) {
        basePrice = currentAmericaine.price40;
    }

    // Ajouter le supplément de la base premium
    const baseInput = document.querySelector('input[name="americaineBase"]:checked');
    const baseSupplement = parseFloat(baseInput.dataset.price);
    basePrice += baseSupplement;

    // Ajouter le prix des ingrédients supplémentaires
    document.querySelectorAll('#americaineIngredientsAdd input[type="checkbox"]:checked').forEach(checkbox => {
        basePrice += parseFloat(checkbox.dataset.price);
    });

    const customization = {
        size,
        base,
        sauces: selectedSauces,
        baseSupplement,
        removedIngredients,
        addedIngredients
    };

    const cartItem = {
        id: Date.now(),
        pizzaId: currentAmericaine.id,
        name: currentAmericaine.name,
        basePrice: basePrice,
        quantity: quantity,
        customization: customization,
        totalPrice: basePrice * quantity,
        type: 'americaine'
    };

    cart.push(cartItem);
    saveCartToStorage();
    updateCartUI();
    closeAmericaineCustomizeModal();
    showNotification(`L'Américaine personnalisée ajoutée au panier`);
    
    // Attendre que le modal soit complètement fermé avant d'ouvrir le panier
    setTimeout(() => {
        console.log('Ouverture du panier dans 400ms...');
        openCart();
    }, 400);
}

// ========================================
// MODAL PERSONNALISATION PÂTES
// ========================================
let currentPate = null;

function openPatesCustomizeModal(pateId) {
    const pate = PATES_DATA.find(p => p.id === pateId);
    currentPate = pate;

    const modal = document.getElementById('patesCustomizeModal');
    const title = document.getElementById('patesModalTitle');

    title.textContent = `Personnaliser ${pate.name}`;

    // Réinitialiser les sélections
    document.querySelectorAll('#patesCustomizeModal input[type="radio"]').forEach(input => {
        if (input.value === 'L' || input.value === 'classique') {
            input.checked = true;
        }
    });
    document.querySelectorAll('#patesCustomizeModal input[type="checkbox"]').forEach(input => {
        input.checked = false;
    });
    document.getElementById('patesQty').value = 1;

    updatePatesPrice();
    modal.classList.add('active');

    // Add event listeners for price updates
    document.querySelectorAll('#patesCustomizeModal input[type="radio"], #patesCustomizeModal input[type="checkbox"]').forEach(input => {
        input.addEventListener('change', updatePatesPrice);
    });
}

function closePatesCustomizeModal() {
    document.getElementById('patesCustomizeModal').classList.remove('active');
    currentPate = null;
}

function updatePatesPrice() {
    if (!currentPate) return;

    // Prix de base selon la taille
    const sizeInput = document.querySelector('input[name="patesSize"]:checked');
    const isL = sizeInput.value === 'L';
    let price = isL ? currentPate.priceL : currentPate.priceXL;

    // Ajouter le prix de la base (Gnocchi/Poulet Farci)
    const baseInput = document.querySelector('input[name="patesBase"]:checked');
    if (baseInput.value === 'gnocchi') {
        price += isL ? 1.50 : 2.00;
    }

    // Ajouter le supplément selon la taille
    const supplementCheckbox = document.querySelector('input[value="supplementL"]');
    if (supplementCheckbox && supplementCheckbox.checked) {
        price += isL ? 1.00 : 1.50;
    }

    // Multiplier par la quantité
    const quantity = parseInt(document.getElementById('patesQty').value);
    price *= quantity;

    document.getElementById('patesPrice').textContent = `${price.toFixed(2)}€`;
}

function increasePatesQty() {
    const input = document.getElementById('patesQty');
    if (parseInt(input.value) < 10) {
        input.value = parseInt(input.value) + 1;
        updatePatesPrice();
    }
}

function decreasePatesQty() {
    const input = document.getElementById('patesQty');
    if (parseInt(input.value) > 1) {
        input.value = parseInt(input.value) - 1;
        updatePatesPrice();
    }
}

function addCustomizedPatesToCart() {
    if (!currentPate) return;

    console.log('addCustomizedPatesToCart - cart.length:', cart.length, 'deliveryTimeSet:', deliveryTimeSet);

    // Si c'est le premier ajout, s'assurer que l'heure est définie
    if (cart.length === 0 && !deliveryTimeSet) {
        console.log('Premier ajout détecté - ouverture modal heure avant ajout au panier');
        pendingCartAction = () => addCustomizedPatesToCart();
        openDeliveryTimeModal();
        return;
    }

    const size = document.querySelector('input[name="patesSize"]:checked')?.value;
    const base = document.querySelector('input[name="patesBase"]:checked')?.value;
    const quantity = parseInt(document.getElementById('patesQty').value);
    const supplementCheckbox = document.querySelector('input[value="supplementL"]');
    const hasSupplement = supplementCheckbox ? supplementCheckbox.checked : false;

    // Calculer le prix
    const isL = size === 'L';
    let basePrice = isL ? currentPate.priceL : currentPate.priceXL;
    
    // Ajouter le prix de la base Gnocchi/Poulet Farci
    if (base === 'gnocchi') {
        basePrice += isL ? 1.50 : 2.00;
    }
    
    // Ajouter le supplément
    if (hasSupplement) {
        basePrice += isL ? 1.00 : 1.50;
    }

    const customization = {
        size,
        base,
        hasSupplement
    };

    const cartItem = {
        id: Date.now(),
        pateId: currentPate.id,
        name: currentPate.name,
        type: 'pate',
        basePrice: basePrice,
        quantity: quantity,
        customization: customization,
        totalPrice: basePrice * quantity
    };

    cart.push(cartItem);
    saveCartToStorage();
    updateCartUI();
    closePatesCustomizeModal();
    showNotification(`${currentPate.name} personnalisée ajoutée au panier`);
    setTimeout(() => openCart(), 100);
}

// ========================================
// MODAL PERSONNALISATION SALADES
// ========================================
let currentSalade = null;

function openSaladeCustomizeModal(saladeId) {
    const salade = SALADES_DATA.find(s => s.id === saladeId);
    currentSalade = salade;

    const modal = document.getElementById('saladeCustomizeModal');
    const title = document.getElementById('saladeModalTitle');

    title.textContent = `Personnaliser ${salade.name}`;

    // Réinitialiser les sélections
    document.querySelectorAll('#saladeCustomizeModal input[type="radio"]').forEach(input => {
        if (input.value === 'Salade verte') {
            input.checked = true;
        }
    });
    document.querySelectorAll('#saladeCustomizeModal input[type="checkbox"]').forEach(input => {
        input.checked = false;
    });
    document.getElementById('saladeQty').value = 1;

    updateSaladePrice();
    modal.classList.add('active');

    // Add event listeners for price updates
    document.querySelectorAll('#saladeCustomizeModal input[type="radio"], #saladeCustomizeModal input[type="checkbox"]').forEach(input => {
        input.addEventListener('change', updateSaladePrice);
    });
}

function closeSaladeCustomizeModal() {
    document.getElementById('saladeCustomizeModal').classList.remove('active');
    currentSalade = null;
}

function updateSaladePrice() {
    if (!currentSalade) return;

    // Prix de base
    let price = currentSalade.price;

    // Ajouter les suppléments
    const supplements = document.querySelectorAll('#saladeCustomizeModal input[type="checkbox"]:checked');
    supplements.forEach(checkbox => {
        price += parseFloat(checkbox.dataset.price);
    });

    // Multiplier par la quantité
    const quantity = parseInt(document.getElementById('saladeQty').value);
    price *= quantity;

    document.getElementById('saladePrice').textContent = `${price.toFixed(2)}€`;
}

function increaseSaladeQty() {
    const input = document.getElementById('saladeQty');
    if (parseInt(input.value) < 10) {
        input.value = parseInt(input.value) + 1;
        updateSaladePrice();
    }
}

function decreaseSaladeQty() {
    const input = document.getElementById('saladeQty');
    if (parseInt(input.value) > 1) {
        input.value = parseInt(input.value) - 1;
        updateSaladePrice();
    }
}

function addCustomizedSaladeToCart() {
    if (!currentSalade) return;

    // Si c'est le premier ajout, s'assurer que l'heure est définie
    if (cart.length === 0 && !deliveryTimeSet) {
        console.log('Premier ajout - ouverture modal heure avant ajout au panier');
        pendingCartAction = () => addCustomizedSaladeToCart();
        openDeliveryTimeModal();
        return;
    }

    const base = document.querySelector('input[name="saladeBase"]:checked')?.value;
    const quantity = parseInt(document.getElementById('saladeQty').value);
    
    // Récupérer les extras sélectionnés
    const extras = [];
    let extrasPrice = 0;
    document.querySelectorAll('#saladeCustomizeModal input[type="checkbox"]:checked').forEach(checkbox => {
        const extraName = checkbox.value;
        const extraPrice = parseFloat(checkbox.dataset.price);
        extras.push({ name: extraName, price: extraPrice });
        extrasPrice += extraPrice;
    });

    // Calculer le prix
    const basePrice = currentSalade.price + extrasPrice;

    const customization = {
        base,
        extras
    };

    const cartItem = {
        id: Date.now(),
        saladeId: currentSalade.id,
        name: currentSalade.name,
        type: 'salade',
        basePrice: basePrice,
        quantity: quantity,
        customization: customization,
        totalPrice: basePrice * quantity
    };

    cart.push(cartItem);
    saveCartToStorage();
    updateCartUI();
    closeSaladeCustomizeModal();
    showNotification(`${currentSalade.name} personnalisée ajoutée au panier`);
    setTimeout(() => openCart(), 100);
}

// ========================================
// MODAL PERSONNALISATION BUNS
// ========================================
let currentBun = null;

function openBunsCustomizeModal(bunId) {
    const bun = BUNS_DATA.find(b => b.id === bunId);
    currentBun = bun;

    const modal = document.getElementById('bunsCustomizeModal');
    const title = document.getElementById('bunsModalTitle');

    title.textContent = `Commander ${bun.name}`;

    // Réinitialiser les sélections
    document.querySelectorAll('#bunsCustomizeModal input[type="radio"]').forEach(input => {
        if (input.value === '1' || input.value === 'none') {
            input.checked = true;
        }
    });

    updateBunsPrice();
    modal.classList.add('active');

    // Add event listeners for price updates
    document.querySelectorAll('#bunsCustomizeModal input[type="radio"]').forEach(input => {
        input.addEventListener('change', updateBunsPrice);
    });
}

function closeBunsCustomizeModal() {
    document.getElementById('bunsCustomizeModal').classList.remove('active');
    currentBun = null;
}

function updateBunsPrice() {
    if (!currentBun) return;

    // Prix selon la quantité
    const qtyInput = document.querySelector('input[name="bunsQty"]:checked');
    const qty = parseInt(qtyInput.value);
    let price;
    
    if (qty === 1) price = currentBun.price;
    else if (qty === 2) price = currentBun.price2;
    else price = currentBun.price3;

    // Ajouter le prix de la base si crème est sélectionnée
    const baseInput = document.querySelector('input[name="bunsBase"]:checked');
    if (baseInput.value === 'creme') {
        price += 0.50 * qty; // +0.50€ par bun pour la crème
    }

    document.getElementById('bunsPrice').textContent = `${price.toFixed(2)}€`;
}

function addCustomizedBunsToCart() {
    if (!currentBun) return;

    // Si c'est le premier ajout, s'assurer que l'heure est définie
    if (cart.length === 0 && !deliveryTimeSet) {
        console.log('Premier ajout - ouverture modal heure avant ajout au panier');
        pendingCartAction = () => addCustomizedBunsToCart();
        openDeliveryTimeModal();
        return;
    }

    const qtyInput = document.querySelector('input[name="bunsQty"]:checked');
    const qty = parseInt(qtyInput.value);
    const baseInput = document.querySelector('input[name="bunsBase"]:checked');
    const base = baseInput.value;

    // Calculer le prix
    let basePrice;
    if (qty === 1) basePrice = currentBun.price;
    else if (qty === 2) basePrice = currentBun.price2;
    else basePrice = currentBun.price3;

    if (base === 'creme') {
        basePrice += 0.50 * qty;
    }

    const customization = {
        quantity: qty,
        base: base
    };

    const cartItem = {
        id: Date.now(),
        bunId: currentBun.id,
        name: `${currentBun.name} x${qty}`,
        type: 'bun',
        basePrice: basePrice,
        quantity: 1, // On gère la quantité dans le customization
        customization: customization,
        totalPrice: basePrice
    };

    cart.push(cartItem);
    saveCartToStorage();
    updateCartUI();
    closeBunsCustomizeModal();
    showNotification(`${currentBun.name} ajouté au panier`);
    setTimeout(() => openCart(), 100);
}

// ========================================
// MODAL PERSONNALISATION ROLLS
// ========================================
let currentRoll = null;

function openRollsCustomizeModal(rollId) {
    const roll = ROLLS_DATA.find(r => r.id === rollId);
    currentRoll = roll;

    const modal = document.getElementById('rollsCustomizeModal');
    const title = document.getElementById('rollsModalTitle');

    title.textContent = `Personnaliser ${roll.name}`;

    // Générer les ingrédients inclus (2 maximum)
    const ingredientsContainer = document.getElementById('rollsIngredients');
    ingredientsContainer.innerHTML = '';
    roll.ingredients.forEach(ing => {
        const label = document.createElement('label');
        label.className = 'ingredient-checkbox';
        label.innerHTML = `
            <input type="checkbox" value="${ing}">
            <span>${ing}</span>
        `;
        ingredientsContainer.appendChild(label);
    });

    // Réinitialiser
    document.querySelectorAll('#rollsCustomizeModal input[type="radio"]').forEach(input => {
        if (input.value === 'creme') {
            input.checked = true;
        }
    });
    document.querySelectorAll('#rollsCustomizeModal input[type="checkbox"]').forEach(input => {
        input.checked = false;
    });
    document.getElementById('rollsQty').value = 1;

    updateRollsPrice();
    modal.classList.add('active');

    // Add event listeners
    document.querySelectorAll('#rollsCustomizeModal input').forEach(input => {
        input.addEventListener('change', updateRollsPrice);
    });
}

function closeRollsCustomizeModal() {
    document.getElementById('rollsCustomizeModal').classList.remove('active');
    currentRoll = null;
}

function updateRollsPrice() {
    if (!currentRoll) return;

    // Prix de base fixe
    let price = currentRoll.price;

    // Compter les ingrédients sélectionnés
    const checkedIngredients = document.querySelectorAll('#rollsIngredients input[type="checkbox"]:checked');
    const count = checkedIngredients.length;
    
    // Mettre à jour le compteur
    const counterSpan = document.getElementById('rollsIngredientsCount');
    counterSpan.textContent = `${count}/2`;
    counterSpan.style.color = count > 2 ? 'red' : (count === 2 ? 'green' : '#666');

    // Désactiver les autres checkboxes si 2 sont sélectionnés
    const allCheckboxes = document.querySelectorAll('#rollsIngredients input[type="checkbox"]');
    allCheckboxes.forEach(cb => {
        if (!cb.checked && count >= 2) {
            cb.disabled = true;
            cb.parentElement.style.opacity = '0.5';
        } else {
            cb.disabled = false;
            cb.parentElement.style.opacity = '1';
        }
    });

    // Multiplier par la quantité
    const quantity = parseInt(document.getElementById('rollsQty').value);
    price *= quantity;

    document.getElementById('rollsPrice').textContent = `${price.toFixed(2)}€`;
}

function increaseRollsQty() {
    const input = document.getElementById('rollsQty');
    if (parseInt(input.value) < 10) {
        input.value = parseInt(input.value) + 1;
        updateRollsPrice();
    }
}

function decreaseRollsQty() {
    const input = document.getElementById('rollsQty');
    if (parseInt(input.value) > 1) {
        input.value = parseInt(input.value) - 1;
        updateRollsPrice();
    }
}

function addCustomizedRollsToCart() {
    if (!currentRoll) return;

    // Si c'est le premier ajout, s'assurer que l'heure est définie
    if (cart.length === 0 && !deliveryTimeSet) {
        console.log('Premier ajout - ouverture modal heure avant ajout au panier');
        pendingCartAction = () => addCustomizedRollsToCart();
        openDeliveryTimeModal();
        return;
    }

    // Vérifier qu'il y a bien 2 ingrédients
    const checkedIngredients = document.querySelectorAll('#rollsIngredients input[type="checkbox"]:checked');
    if (checkedIngredients.length !== 2) {
        showNotification('Veuillez sélectionner exactement 2 ingrédients', 'error');
        return;
    }

    const base = document.querySelector('input[name="rollsBase"]:checked')?.value;
    const quantity = parseInt(document.getElementById('rollsQty').value);
    
    // Récupérer les ingrédients
    const ingredients = Array.from(checkedIngredients).map(cb => cb.value);

    // Prix fixe
    let basePrice = currentRoll.price;

    const customization = {
        base,
        ingredients
    };

    const cartItem = {
        id: Date.now(),
        rollId: currentRoll.id,
        name: currentRoll.name,
        type: 'roll',
        basePrice: basePrice,
        quantity: quantity,
        customization: customization,
        totalPrice: basePrice * quantity
    };

    cart.push(cartItem);
    saveCartToStorage();
    updateCartUI();
    closeRollsCustomizeModal();
    showNotification(`${currentRoll.name} personnalisé ajouté au panier`);
    setTimeout(() => openCart(), 100);
}

// ========================================
// MODAL FORMULE MIDI
// ========================================
let selectedFormuleMidiPizza = null;

function openFormuleMidiModal() {
    const modal = document.getElementById('formuleMidiModal');
    
    // Générer la liste des pizzas (toutes sauf Burger et Américaine)
    const pizzasList = document.getElementById('formuleMidiPizzasList');
    pizzasList.innerHTML = '';
    
    const excludedIds = FORMULES_DATA.midi.excludedPizzas || [];
    
    PIZZAS_DATA.forEach(pizza => {
        const isPremium = excludedIds.includes(pizza.id);
        const priceNote = isPremium ? ' (+1€)' : '';
        
        const div = document.createElement('label');
        div.className = 'formule-item-option';
        div.innerHTML = `
            <input type="radio" name="formuleMidiPizza" value="${pizza.id}" onchange="updateFormuleMidiPrice()">
            <div class="formule-item-content">
                <div class="formule-item-name">${pizza.name}</div>
                ${priceNote ? `<div class="formule-item-note">${priceNote}</div>` : ''}
            </div>
        `;
        pizzasList.appendChild(div);
    });
    
    // Sélectionner la première pizza par défaut
    const firstRadio = pizzasList.querySelector('input[type="radio"]');
    if (firstRadio) firstRadio.checked = true;
    
    updateFormuleMidiPrice();
    modal.classList.add('active');
}

function closeFormuleMidiModal() {
    document.getElementById('formuleMidiModal').classList.remove('active');
    selectedFormuleMidiPizza = null;
}

function updateFormuleMidiPrice() {
    const selectedPizzaInput = document.querySelector('input[name="formuleMidiPizza"]:checked');
    if (!selectedPizzaInput) return;
    
    const pizzaId = parseInt(selectedPizzaInput.value);
    const excludedIds = FORMULES_DATA.midi.excludedPizzas || [];
    const isPremium = excludedIds.includes(pizzaId);
    
    let price = FORMULES_DATA.midi.price;
    if (isPremium) {
        price += FORMULES_DATA.midi.priceExtra;
    }
    
    document.getElementById('formuleMidiPrice').textContent = `${price.toFixed(2)}€`;
}

function addFormuleMidiToCart() {
    const selectedPizzaInput = document.querySelector('input[name="formuleMidiPizza"]:checked');
    const selectedBoissonInput = document.querySelector('input[name="formuleMidiBoisson"]:checked');
    
    if (!selectedPizzaInput) {
        showNotification('Veuillez sélectionner une pizza', 'error');
        return;
    }
    
    const pizzaId = parseInt(selectedPizzaInput.value);
    const pizza = PIZZAS_DATA.find(p => p.id === pizzaId);
    
    // Stocker les informations de la formule pour les utiliser après personnalisation
    const boisson = selectedBoissonInput ? selectedBoissonInput.value : 'Coca-Cola';
    
    const excludedIds = FORMULES_DATA.midi.excludedPizzas || [];
    const isPremium = excludedIds.includes(pizzaId);
    
    let price = FORMULES_DATA.midi.price;
    if (isPremium) {
        price += FORMULES_DATA.midi.priceExtra;
    }
    
    // Stocker les infos de la formule dans une variable temporaire
    window.pendingFormuleMidi = {
        boisson: boisson,
        basePrice: price,
        isPremium: isPremium
    };
    
    // Fermer le modal de formule et ouvrir la personnalisation pizza
    closeFormuleMidiModal();
    
    // Ouvrir le modal de personnalisation pour cette pizza
    openCustomizeModal(pizzaId);
}

// ========================================
// MODAL MENU PÂTES/SALADE
// ========================================
let selectedMenuPatesSaladeItem = null;

function openMenuPatesSaladeModal() {
    const modal = document.getElementById('menuPatesSaladeModal');
    
    // Générer les listes
    generateMenuPatesList();
    generateMenuSaladesList();
    generateMenuDessertsList();
    
    // Afficher la sélection pâtes par défaut
    updateMenuPatesSaladeSelection();
    updateMenuPatesSaladePrice();
    
    modal.classList.add('active');
}

function closeMenuPatesSaladeModal() {
    document.getElementById('menuPatesSaladeModal').classList.remove('active');
    selectedMenuPatesSaladeItem = null;
}

function generateMenuPatesList() {
    const patesList = document.getElementById('menuPatesList');
    patesList.innerHTML = '';
    
    PATES_DATA.forEach(pate => {
        const div = document.createElement('label');
        div.className = 'formule-item-option';
        div.innerHTML = `
            <input type="radio" name="menuPate" value="${pate.id}">
            <div class="formule-item-content">
                <div class="formule-item-name">${pate.name}</div>
            </div>
        `;
        patesList.appendChild(div);
    });
    
    // Sélectionner le premier par défaut
    const firstRadio = patesList.querySelector('input[type="radio"]');
    if (firstRadio) firstRadio.checked = true;
}

function generateMenuSaladesList() {
    const saladesList = document.getElementById('menuSaladesList');
    saladesList.innerHTML = '';
    
    SALADES_DATA.forEach(salade => {
        const div = document.createElement('label');
        div.className = 'formule-item-option';
        div.innerHTML = `
            <input type="radio" name="menuSalade" value="${salade.id}">
            <div class="formule-item-content">
                <div class="formule-item-name">${salade.name}</div>
            </div>
        `;
        saladesList.appendChild(div);
    });
    
    // Sélectionner le premier par défaut
    const firstRadio = saladesList.querySelector('input[type="radio"]');
    if (firstRadio) firstRadio.checked = true;
}

function generateMenuDessertsList() {
    const dessertsList = document.getElementById('menuDessertsList');
    dessertsList.innerHTML = '';
    
    DESSERTS_DATA.forEach(dessert => {
        const div = document.createElement('label');
        div.className = 'formule-item-option';
        div.innerHTML = `
            <input type="radio" name="menuDessert" value="${dessert.id}">
            <div class="formule-item-content">
                <div class="formule-item-name">${dessert.name}</div>
            </div>
        `;
        dessertsList.appendChild(div);
    });
    
    // Sélectionner le premier par défaut
    const firstRadio = dessertsList.querySelector('input[type="radio"]');
    if (firstRadio) firstRadio.checked = true;
}

function updateMenuPatesSaladeSelection() {
    const menuType = document.querySelector('input[name="menuType"]:checked')?.value;
    
    const patesSelection = document.getElementById('menuPatesSelection');
    const saladesSelection = document.getElementById('menuSaladesSelection');
    
    if (menuType === 'pate') {
        patesSelection.style.display = 'block';
        saladesSelection.style.display = 'none';
    } else {
        patesSelection.style.display = 'none';
        saladesSelection.style.display = 'block';
    }
    
    updateMenuPatesSaladePrice();
}

function updateMenuPatesSaladePrice() {
    const menuType = document.querySelector('input[name="menuType"]:checked')?.value;
    let price = FORMULES_DATA.patesSalade.priceL; // Par défaut L ou Salade
    
    if (menuType === 'pate') {
        const pateSize = document.querySelector('input[name="menuPateSize"]:checked')?.value;
        if (pateSize === 'XL') {
            price = FORMULES_DATA.patesSalade.priceXL;
        }
    }
    
    document.getElementById('menuPatesSaladePrice').textContent = `${price.toFixed(2)}€`;
}

function addMenuPatesSaladeToCart() {
    const menuType = document.querySelector('input[name="menuType"]:checked')?.value;
    const selectedDessertInput = document.querySelector('input[name="menuDessert"]:checked');
    const selectedBoissonInput = document.querySelector('input[name="menuBoisson"]:checked');
    
    if (!selectedDessertInput) {
        showNotification('Veuillez sélectionner un dessert', 'error');
        return;
    }
    
    const dessert = DESSERTS_DATA.find(d => d.id === parseInt(selectedDessertInput.value));
    const boisson = selectedBoissonInput ? selectedBoissonInput.value : 'Coca-Cola';
    
    let mainItem, price;
    
    if (menuType === 'pate') {
        const selectedPateInput = document.querySelector('input[name="menuPate"]:checked');
        if (!selectedPateInput) {
            showNotification('Veuillez sélectionner une pâte', 'error');
            return;
        }
        
        const pate = PATES_DATA.find(p => p.id === parseInt(selectedPateInput.value));
        const pateSize = document.querySelector('input[name="menuPateSize"]:checked')?.value || 'L';
        
        mainItem = `${pate.name} (${pateSize})`;
        price = pateSize === 'XL' ? FORMULES_DATA.patesSalade.priceXL : FORMULES_DATA.patesSalade.priceL;
    } else {
        const selectedSaladeInput = document.querySelector('input[name="menuSalade"]:checked');
        if (!selectedSaladeInput) {
            showNotification('Veuillez sélectionner une salade', 'error');
            return;
        }
        
        const salade = SALADES_DATA.find(s => s.id === parseInt(selectedSaladeInput.value));
        mainItem = salade.name;
        price = FORMULES_DATA.patesSalade.priceL;
    }
    
    const cartItem = {
        id: Date.now(),
        type: 'formule',
        formuleType: 'patesSalade',
        name: 'Menu Pâtes/Salade',
        basePrice: price,
        quantity: 1,
        totalPrice: price,
        customization: {
            mainItem: mainItem,
            dessert: dessert.name,
            boisson: boisson
        }
    };
    
    cart.push(cartItem);
    saveCartToStorage();
    updateCartUI();
    closeMenuPatesSaladeModal();
    showNotification('Menu Pâtes/Salade ajouté au panier');
    setTimeout(() => openCart(), 100);
}

// ========================================
// MODAL CHECKOUT
// ========================================
function openCheckoutModal() {
    if (cart.length === 0) {
        showNotification('Votre panier est vide', 'error');
        return;
    }

    // Afficher les informations de livraison déjà définies
    displayDeliveryTimeInfo();

    const modal = document.getElementById('checkoutModal');
    modal.classList.add('active');
    goToStep(1);
    closeCart();
}

function displayDeliveryTimeInfo() {
    const displayDiv = document.getElementById('deliveryTimeDisplay');
    
    console.log('displayDeliveryTimeInfo - deliveryTimeMode:', deliveryTimeMode, 'scheduledDeliveryDate:', scheduledDeliveryDate, 'scheduledDeliveryHour:', scheduledDeliveryHour);
    
    if (deliveryTimeMode === 'programmee' && scheduledDeliveryDate && scheduledDeliveryHour !== null) {
        // Formater la date
        const dateParts = scheduledDeliveryDate.split('-');
        const formattedDate = `${dateParts[2]}/${dateParts[1]}/${dateParts[0]}`;
        const formattedHour = `${scheduledDeliveryHour}h00`;
        
        displayDiv.innerHTML = `
            <div style="display: flex; align-items: center; gap: 15px;">
                <i class="fas fa-calendar-check" style="font-size: 2rem; color: #4CAF50;"></i>
                <div>
                    <p style="margin: 0; font-weight: 600;">Commande programmée</p>
                    <p style="margin: 5px 0 0 0; color: #666;">
                        Le <strong>${formattedDate}</strong> à <strong>${formattedHour}</strong>
                    </p>
                </div>
            </div>
        `;
    } else {
        // Mode maintenant
        const now = new Date();
        const currentHour = now.getHours();
        const currentMinutes = now.getMinutes();
        
        displayDiv.innerHTML = `
            <div style="display: flex; align-items: center; gap: 15px;">
                <i class="fas fa-bolt" style="font-size: 2rem; color: #FF9800;"></i>
                <div>
                    <p style="margin: 0; font-weight: 600;">Livraison dès que possible</p>
                    <p style="margin: 5px 0 0 0; color: #666;">
                        Votre commande sera préparée immédiatement (estimée vers ${currentHour}h${currentMinutes < 10 ? '0' + currentMinutes : currentMinutes})
                    </p>
                </div>
            </div>
        `;
    }
}

function closeCheckoutModal() {
    document.getElementById('checkoutModal').classList.remove('active');
    currentStep = 1;
}

function goToStep(step) {
    // Validation avant de passer à l'étape suivante
    if (step > currentStep) {
        if (currentStep === 2 && !validateCustomerForm()) {
            return;
        }
    }

    currentStep = step;

    // Cacher toutes les étapes
    document.querySelectorAll('.checkout-step').forEach(s => s.classList.remove('active'));
    
    // Afficher l'étape courante
    document.getElementById(`step${step}`).classList.add('active');

    // Si étape 3, afficher le récapitulatif
    if (step === 3) {
        displayOrderSummary();
    }
}

function updateDeliveryMode() {
    const mode = document.querySelector('input[name="deliveryMode"]:checked')?.value;
    const deliveryFields = document.querySelectorAll('.delivery-only');
    
    deliveryFields.forEach(field => {
        if (mode === 'livraison') {
            field.style.display = 'block';
            field.querySelectorAll('input').forEach(input => input.required = true);
        } else {
            field.style.display = 'none';
            field.querySelectorAll('input').forEach(input => input.required = false);
        }
    });

    updateCartTotals();
}

function validateCustomerForm() {
    const form = document.getElementById('customerForm');
    const mode = document.querySelector('input[name="deliveryMode"]:checked')?.value;

    // Validation basique
    if (!form.checkValidity()) {
        form.reportValidity();
        return false;
    }

    // Stocker les données client
    customerData = {
        lastName: document.getElementById('lastName').value,
        firstName: document.getElementById('firstName').value,
        phone: document.getElementById('phone').value,
        email: document.getElementById('email').value,
        deliveryMode: mode,
        birthdate: document.getElementById('birthdate').value,
        comments: document.getElementById('comments').value
    };

    if (mode === 'livraison') {
        customerData.address = document.getElementById('address').value;
        customerData.postalCode = document.getElementById('postalCode').value;
        customerData.city = document.getElementById('city').value;
    }

    // Sauvegarder dans localStorage (base client simulée)
    saveCustomerToDatabase(customerData);

    return true;
}

function displayOrderSummary() {
    // Informations client
    const summaryCustomer = document.getElementById('summaryCustomer');
    summaryCustomer.innerHTML = `
        <p><strong>${customerData.firstName} ${customerData.lastName}</strong></p>
        <p>${customerData.phone}</p>
        <p>${customerData.email}</p>
        ${customerData.deliveryMode === 'livraison' ? `
            <p>${customerData.address}</p>
            <p>${customerData.postalCode} ${customerData.city}</p>
        ` : '<p><strong>À emporter</strong></p>'}
        ${customerData.comments ? `<p><em>${customerData.comments}</em></p>` : ''}
    `;

    // Articles de la commande
    const summaryItems = document.getElementById('summaryItems');
    summaryItems.innerHTML = cart.map(item => `
        <div class="summary-row">
            <span>${item.name} x${item.quantity}</span>
            <span>${item.totalPrice.toFixed(2)}€</span>
        </div>
    `).join('');

    // Totaux
    const subtotal = cart.reduce((sum, item) => sum + item.totalPrice, 0);
    const deliveryFee = getDeliveryFee(subtotal);
    
    // Appliquer le code promo
    let discount = 0;
    if (promoCodeApplied === 'LIV10' && subtotal >= 20) {
        discount = 2;
        document.getElementById('summaryPromoDiscountRow').style.display = 'flex';
        document.getElementById('summaryPromoDiscountAmount').textContent = `-${discount.toFixed(2)}€`;
    } else {
        document.getElementById('summaryPromoDiscountRow').style.display = 'none';
    }
    
    const total = subtotal + deliveryFee - discount;

    document.getElementById('summarySubtotal').textContent = `${subtotal.toFixed(2)}€`;
    document.getElementById('summaryDelivery').textContent = deliveryFee === 0 ? 'Offert' : `${deliveryFee.toFixed(2)}€`;
    document.getElementById('summaryTotal').textContent = `${total.toFixed(2)}€`;
}

// ========================================
// SOUMISSION DE LA COMMANDE
// ========================================
async function submitOrder() {
    try {
        // Générer un numéro de commande
        orderNumber = generateOrderNumber();

        // Préparer les données de la commande
        const subtotalAmount = cart.reduce((sum, item) => sum + item.totalPrice, 0);
        const deliveryFeeAmount = getDeliveryFee(subtotalAmount);
        const discountAmount = (promoCodeApplied === 'LIV10' && subtotalAmount >= 20) ? 2 : 0;
        const totalAmount = subtotalAmount + deliveryFeeAmount - discountAmount;
        
        const orderData = {
            orderNumber,
            customer: customerData,
            items: cart,
            subtotal: subtotalAmount,
            deliveryFee: deliveryFeeAmount,
            promoCode: promoCodeApplied,
            discount: discountAmount,
            total: totalAmount,
            timestamp: new Date().toISOString(),
            estimatedTime: CONFIG.delivery.estimatedTime[customerData.deliveryMode]
        };

        // Sauvegarder la commande
        saveOrderToDatabase(orderData);

        // Envoyer par email
        await sendOrderByEmail(orderData);

        // Envoyer par SMS (si activé)
        if (CONFIG.sms.enabled) {
            await sendOrderBySMS(orderData);
        }

        // Afficher la confirmation
        showOrderConfirmation(orderData);

        // Vider le panier
        clearCart();
        closeCheckoutModal();

    } catch (error) {
        console.error('Erreur lors de la soumission:', error);
        showNotification('Erreur lors de l\'envoi de la commande. Veuillez réessayer.', 'error');
    }
}

function generateOrderNumber() {
    const date = new Date();
    const random = Math.floor(Math.random() * 10000);
    return `PC${date.getFullYear()}${String(date.getMonth() + 1).padStart(2, '0')}${String(date.getDate()).padStart(2, '0')}-${random}`;
}

async function sendOrderByEmail(orderData) {
    try {
        // Envoyer la commande au serveur PHP
        const response = await fetch('send-order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(orderData)
        });

        const result = await response.json();

        if (result.success) {
            console.log('✅ Commande envoyée avec succès!');
            console.log('📧 Email:', result.emailSent ? 'Envoyé' : 'Échec');
            console.log('📱 WhatsApp:', result.whatsappSent ? 'Envoyé' : 'Non configuré');
            showNotification('Commande envoyée avec succès !', 'success');
        } else {
            throw new Error('Erreur lors de l\'envoi de la commande');
        }

    } catch (error) {
        console.error('❌ Erreur:', error);
        showNotification('Erreur lors de l\'envoi. Appelez le 0262 66 82 30', 'error');
        throw error;
    }
}

async function sendOrderBySMS(orderData) {
    const smsContent = formatOrderForSMS(orderData);
    
    // Simulation d'envoi SMS (intégration Twilio à faire côté serveur)
    console.log('=== SMS SIMULÉ ===');
    console.log('À:', CONFIG.sms.toNumber);
    console.log(smsContent);
    console.log('==================');
}

function formatOrderForEmail(orderData) {
    const items = orderData.items.map(item => 
        `${item.name} x${item.quantity} - ${item.totalPrice.toFixed(2)}€`
    ).join('\n');

    const text = `
NOUVELLE COMMANDE - ${orderData.orderNumber}

CLIENT:
${orderData.customer.firstName} ${orderData.customer.lastName}
Tel: ${orderData.customer.phone}
Email: ${orderData.customer.email}

MODE: ${orderData.customer.deliveryMode === 'livraison' ? 'LIVRAISON' : 'À EMPORTER'}
${orderData.customer.deliveryMode === 'livraison' ? `
Adresse:
${orderData.customer.address}
${orderData.customer.postalCode} ${orderData.customer.city}
` : ''}

COMMANDE:
${items}

Sous-total: ${orderData.subtotal.toFixed(2)}€
Frais de livraison: ${orderData.deliveryFee.toFixed(2)}€
TOTAL: ${orderData.total.toFixed(2)}€

Temps estimé: ${orderData.estimatedTime}

${orderData.customer.comments ? `Commentaire: ${orderData.customer.comments}` : ''}
    `.trim();

    return { items, text };
}

function formatOrderForSMS(orderData) {
    return `Pizza Club - Nouvelle commande ${orderData.orderNumber}
${orderData.customer.firstName} ${orderData.customer.lastName}
${orderData.customer.phone}
${orderData.customer.deliveryMode === 'livraison' ? 'LIVRAISON' : 'À EMPORTER'}
Total: ${orderData.total.toFixed(2)}€
${orderData.items.length} article(s)`;
}

function showOrderConfirmation(orderData) {
    const modal = document.getElementById('confirmationModal');
    const messageEl = document.getElementById('confirmationMessage');
    const orderNumberEl = document.getElementById('orderNumber');
    const estimatedTimeEl = document.getElementById('estimatedTime');

    const mode = orderData.customer.deliveryMode === 'livraison' ? 'livrée' : 'prête';
    messageEl.textContent = `Votre commande sera ${mode} dans environ ${orderData.estimatedTime}.`;
    orderNumberEl.textContent = orderData.orderNumber;
    estimatedTimeEl.textContent = orderData.estimatedTime;

    modal.classList.add('active');
}

function closeConfirmationModal() {
    document.getElementById('confirmationModal').classList.remove('active');
}

// ========================================
// GESTION DU PANIER
// ========================================
function openCart() {
    console.log('openCart() appelée'); // Debug
    
    // Ne pas ouvrir automatiquement sur mobile
    const isMobile = window.innerWidth <= 768;
    if (isMobile) {
        console.log('Mobile détecté - panier non ouvert automatiquement');
        return;
    }
    
    const cartSidebar = document.getElementById('cartSidebar');
    console.log('cartSidebar element:', cartSidebar); // Debug
    if (cartSidebar) {
        console.log('Classes avant:', cartSidebar.className); // Debug
        cartSidebar.classList.add('active');
        console.log('Classes après:', cartSidebar.className); // Debug
        
        // Vérifier après 100ms si le panier est toujours ouvert
        setTimeout(() => {
            console.log('Vérification après 100ms, classes:', cartSidebar.className);
            if (!cartSidebar.classList.contains('active')) {
                console.error('Le panier a été fermé automatiquement!');
                cartSidebar.classList.add('active');
            }
        }, 100);
        
        console.log('Panier ouvert'); // Debug
    } else {
        console.error('Element cartSidebar non trouvé'); // Debug
    }
}

function closeCart() {
    const cartSidebar = document.getElementById('cartSidebar');
    if (cartSidebar) {
        cartSidebar.classList.remove('active');
    }
}

// ========================================
// STOCKAGE LOCAL
// ========================================
function saveCartToStorage() {
    localStorage.setItem('pizzaclub_cart', JSON.stringify(cart));
}

function loadCartFromStorage() {
    const saved = localStorage.getItem('pizzaclub_cart');
    if (saved) {
        cart = JSON.parse(saved);
        updateCartUI();
    }
    
    // Charger le code promo
    const savedPromoCode = localStorage.getItem('promoCodeApplied');
    const savedPromoDiscount = localStorage.getItem('promoDiscount');
    
    if (savedPromoCode) {
        promoCodeApplied = savedPromoCode;
        promoDiscount = savedPromoDiscount ? parseFloat(savedPromoDiscount) : 2;
        
        const input = document.getElementById('promoCodeInput');
        const btn = document.getElementById('btnApplyPromo');
        
        if (input && btn) {
            input.value = '';
            input.disabled = true;
            btn.disabled = true;
            showPromoMessage('Code promo appliqué ! -2€ sur votre commande', 'success');
        }
        
        // Mettre à jour les totaux pour afficher la réduction
        updateCartTotals();
    }
}

function saveCustomerToDatabase(customer) {
    // Récupérer la base client existante
    let customers = JSON.parse(localStorage.getItem('pizzaclub_customers') || '[]');
    
    // Ajouter le nouveau client
    customers.push({
        ...customer,
        id: Date.now(),
        timestamp: new Date().toISOString()
    });

    // Sauvegarder
    localStorage.setItem('pizzaclub_customers', JSON.stringify(customers));
}

function saveOrderToDatabase(order) {
    // Récupérer les commandes existantes
    let orders = JSON.parse(localStorage.getItem('pizzaclub_orders') || '[]');
    
    // Ajouter la nouvelle commande
    orders.push(order);

    // Sauvegarder
    localStorage.setItem('pizzaclub_orders', JSON.stringify(orders));
}

// ========================================
// NOTIFICATIONS
// ========================================
function showNotification(message, type = 'success') {
    // Créer l'élément de notification
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        background: ${type === 'success' ? '#FF0000' : '#dc3545'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.2);
        z-index: 10000;
        animation: slideInRight 0.3s ease;
        font-weight: 500;
    `;
    notification.textContent = message;

    // Ajouter au body
    document.body.appendChild(notification);

    // Retirer après 3 secondes
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Ajouter les animations CSS dynamiquement
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// ========================================
// PROMO 2 PIZZAS
// ========================================
let promoApplied = false;

function checkPromo2Pizzas() {
    const deliveryHour = getDeliveryHour();
    
    console.log('checkPromo2Pizzas - deliveryHour:', deliveryHour, 'deliveryTimeMode:', deliveryTimeMode, 'scheduledDeliveryDate:', scheduledDeliveryDate);
    
    // Vérifier si une promo est déjà dans le panier
    const hasPromo = cart.some(item => item.type === 'promo2pizzas');
    
    // Compter le nombre de pizzas dans le panier (hors Marmaille/pizza enfant et formules)
    const pizzaCount = cart.filter(item => {
        // Pizzas avec type 'pizza' (ajout simple)
        if (item.type === 'pizza') {
            // Exclure la Marmaille (pizza enfant - ID 38)
            if (item.pizzaId === 38) return false;
            return true;
        }
        // Pizzas personnalisées (ont pizzaId mais pas de type)
        if (item.pizzaId && !item.type) {
            // Exclure la Marmaille (pizza enfant - ID 38)
            if (item.pizzaId === 38) return false;
            return true;
        }
        return false;
    }).reduce((sum, item) => sum + item.quantity, 0);
    
    console.log('Nombre de pizzas éligibles:', pizzaCount, 'hasPromo:', hasPromo);
    
    // Si moins de 2 pizzas et qu'une promo existe, la retirer
    if (pizzaCount < 2 && hasPromo) {
        cart = cart.filter(item => item.type !== 'promo2pizzas');
        promoApplied = false;
        localStorage.setItem('promoApplied', 'false');
        saveCartToStorage();
        updateCartUI();
        showNotification('Promo retirée : moins de 2 pizzas dans le panier', 'info');
        return;
    }
    
    // Si on a déjà une promo dans le panier, ne rien faire (garder la promo)
    if (hasPromo) {
        console.log('Promo déjà présente dans le panier, aucune action');
        return;
    }
    
    // Promo disponible uniquement le soir (après 18h)
    if (deliveryHour < 18) {
        console.log('Heure < 18h, pas de promo disponible');
        return;
    }
    
    console.log('Heure >= 18h, promo du soir disponible');
    
    // Si 2 pizzas ou plus et pas encore de promo, ouvrir le modal automatiquement
    if (pizzaCount >= 2 && !promoApplied) {
        console.log('2 pizzas ou plus, ouverture modal promo');
        // Petite temporisation pour que le panier s'affiche d'abord
        setTimeout(() => {
            openPromoModal();
        }, 500);
    } else {
        console.log('Conditions promo non remplies - pizzaCount:', pizzaCount, 'promoApplied:', promoApplied);
    }
}

function openPromoModal() {
    const modal = document.getElementById('promoModal');
    if (modal) {
        modal.classList.add('active');
        promoApplied = true; // Marquer comme affiché pour cette session panier
    }
}

function closePromoModal() {
    const modal = document.getElementById('promoModal');
    if (modal) {
        modal.classList.remove('active');
    }
}

function selectPromo(type) {
    if (type === 'margherita') {
        const promoItem = {
            id: Date.now(),
            type: 'promo2pizzas',
            name: 'Pizza Margherita',
            description: '🎁 OFFERTE - Promo 2 Pizzas',
            quantity: 1,
            totalPrice: 0,
            isPromo: true
        };
        
        cart.push(promoItem);
        promoApplied = true;
        localStorage.setItem('promoApplied', 'true');
        saveCartToStorage();
        updateCartUI();
        showNotification('🎉 Cadeau ajouté au panier !');
        closePromoModal();
    } else if (type === 'boissons') {
        // Fermer le modal promo et ouvrir le modal de sélection des boissons
        closePromoModal();
        openPromoBoissonsModal();
    }
}

// ========================================
// MODAL SÉLECTION BOISSONS PROMO
// ========================================

let selectedPromoBoissons = [];

function openPromoBoissonsModal() {
    // Réinitialiser les sélections
    selectedPromoBoissons = [];
    document.querySelectorAll('.boisson-btn').forEach(btn => {
        btn.classList.remove('selected');
        btn.disabled = false;
    });
    updateSelectedBoissonsDisplay();
    document.getElementById('promoBoissonsModal').classList.add('active');
}

function closePromoBoissonsModal() {
    document.getElementById('promoBoissonsModal').classList.remove('active');
    selectedPromoBoissons = [];
}

function selectPromoBoisson(button, boissonName) {
    // Si on a déjà 2 boissons, ne pas permettre plus
    if (selectedPromoBoissons.length >= 2) {
        showNotification('Vous avez déjà sélectionné 2 boissons', 'warning');
        return;
    }
    
    // Ajouter la boisson (même si elle existe déjà)
    selectedPromoBoissons.push(boissonName);
    
    updateSelectedBoissonsDisplay();
}

function removePromoBoisson(index) {
    selectedPromoBoissons.splice(index, 1);
    updateSelectedBoissonsDisplay();
}

function updateSelectedBoissonsDisplay() {
    const displayList = document.getElementById('selectedBoissonsList');
    
    if (selectedPromoBoissons.length === 0) {
        displayList.innerHTML = '<em>Cliquez sur 2 boissons pour les sélectionner</em>';
    } else if (selectedPromoBoissons.length === 1) {
        displayList.innerHTML = `
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <span><strong>1.</strong> ${selectedPromoBoissons[0]}</span>
                <button onclick="removePromoBoisson(0)" style="background: #f44336; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 0.85rem;">
                    <i class="fas fa-times"></i>
                </button>
                <span style="color: #FF9800;">→ Sélectionnez encore 1 boisson</span>
            </div>
        `;
    } else {
        displayList.innerHTML = `
            <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                <div style="display: flex; gap: 8px; align-items: center;">
                    <span><strong>1.</strong> ${selectedPromoBoissons[0]}</span>
                    <button onclick="removePromoBoisson(0)" style="background: #f44336; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 0.85rem;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <span style="color: #4CAF50;">✓</span>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <span><strong>2.</strong> ${selectedPromoBoissons[1]}</span>
                    <button onclick="removePromoBoisson(1)" style="background: #f44336; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 0.85rem;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <span style="color: #4CAF50;">✓</span>
            </div>
        `;
    }
}

function confirmPromoBoissons() {
    // Vérifier que les 2 boissons sont sélectionnées
    if (selectedPromoBoissons.length !== 2) {
        showNotification('Veuillez sélectionner exactement 2 boissons', 'error');
        return;
    }
    
    // Créer l'item promo avec les boissons choisies
    const promoItem = {
        id: Date.now(),
        type: 'promo2pizzas',
        name: `2 Boissons: ${selectedPromoBoissons[0]} + ${selectedPromoBoissons[1]}`,
        description: '🎁 OFFERTES - Promo 2 Pizzas',
        quantity: 1,
        totalPrice: 0,
        isPromo: true,
        boissons: [...selectedPromoBoissons]
    };
    
    cart.push(promoItem);
    promoApplied = true;
    localStorage.setItem('promoApplied', 'true');
    saveCartToStorage();
    updateCartUI();
    showNotification('🎉 Boissons ajoutées au panier !');
    closePromoBoissonsModal();
}

// ========================================
// HEADER SCROLL EFFECT
// ========================================
let lastScroll = 0;
window.addEventListener('scroll', () => {
    const header = document.getElementById('header');
    const currentScroll = window.pageYOffset;

    if (currentScroll > 100) {
        header.style.boxShadow = '0 4px 16px rgba(0,0,0,0.15)';
    } else {
        header.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
    }

    lastScroll = currentScroll;
});

// ========================================
// GESTION HORAIRES ET COMMANDE PROGRAMMÉE
// ========================================
function ensureDeliveryTimeSet(callback) {
    console.log('ensureDeliveryTimeSet called - deliveryTimeSet:', deliveryTimeSet, 'cart.length:', cart.length);
    
    // Si l'heure est déjà définie, exécuter directement
    if (deliveryTimeSet) {
        console.log('Delivery time already set, executing callback');
        callback();
        return;
    }
    
    // Si le panier n'est pas vide (chargé depuis localStorage), considérer que l'heure est définie
    if (cart.length > 0) {
        console.log('Cart not empty, using default mode');
        // Utiliser l'heure actuelle par défaut si rien n'est sauvegardé
        if (!deliveryTimeMode) {
            deliveryTimeMode = 'maintenant';
        }
        deliveryTimeSet = true;
        callback();
        return;
    }
    
    // Sinon, ouvrir le modal et mettre l'action en attente
    console.log('Opening delivery time modal, setting pending action');
    pendingCartAction = callback;
    openDeliveryTimeModal();
}

function getDeliveryHour() {
    // Si mode programmé et heure définie
    if (deliveryTimeMode === 'programmee' && scheduledDeliveryHour !== null) {
        return scheduledDeliveryHour;
    }
    
    // Sinon retourner l'heure actuelle
    return new Date().getHours();
}

function isScheduledForFuture() {
    if (deliveryTimeMode !== 'programmee' || !scheduledDeliveryDate || scheduledDeliveryHour === null) {
        return false;
    }
    
    const today = new Date();
    const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
    
    // Si la date programmée est après aujourd'hui, c'est forcément dans le futur
    if (scheduledDeliveryDate > todayStr) {
        return true;
    }
    
    // Si c'est aujourd'hui, vérifier l'heure
    if (scheduledDeliveryDate === todayStr) {
        return scheduledDeliveryHour > today.getHours();
    }
    
    return false;
}

function isWithinOpeningHours() {
    const now = new Date();
    const currentHour = now.getHours();
    
    // Service midi : 11h-14h (précommande dès 10h)
    if (currentHour >= (CONFIG.openingHours.midi.start - CONFIG.openingHours.preorderBuffer) && 
        currentHour < CONFIG.openingHours.midi.end) {
        return true;
    }
    
    // Service soir : 18h-21h (précommande dès 17h)
    if (currentHour >= (CONFIG.openingHours.soir.start - CONFIG.openingHours.preorderBuffer) && 
        currentHour < CONFIG.openingHours.soir.end) {
        return true;
    }
    
    // Fermé entre 14h et 17h, et entre 21h et 10h
    return false;
}

function canOrderNow() {
    const now = new Date();
    const currentDay = now.getDay(); // 0=dimanche, 1=lundi, etc.
    const currentHour = now.getHours();
    
    // Vérifier si le restaurant est fermé toute la journée (lundi)
    if (CONFIG.openingHours.closedDays && CONFIG.openingHours.closedDays.includes(currentDay)) {
        return false; // Fermé toute la journée
    }
    
    // Service midi : commande "maintenant" possible de 10h à 14h
    if (currentHour >= (CONFIG.openingHours.midi.start - CONFIG.openingHours.preorderBuffer) && 
        currentHour < CONFIG.openingHours.midi.end) {
        // Vérifier si le midi est fermé ce jour (dimanche)
        if (CONFIG.openingHours.closedMidi && CONFIG.openingHours.closedMidi.includes(currentDay)) {
            return false; // Fermé le midi aujourd'hui
        }
        return true;
    }
    
    // Service soir : commande "maintenant" possible de 17h à 21h
    if (currentHour >= (CONFIG.openingHours.soir.start - CONFIG.openingHours.preorderBuffer) && 
        currentHour < CONFIG.openingHours.soir.end) {
        return true;
    }
    
    // Fermé : uniquement commande programmée
    return false;
}

function openDeliveryTimeModal() {
    const modal = document.getElementById('deliveryTimeModal');
    if (!modal) {
        console.error('Modal deliveryTimeModal not found');
        return;
    }
    
    console.log('Opening delivery time modal');
    
    const canNow = canOrderNow();
    const now = new Date();
    const currentDay = now.getDay();
    const currentHour = now.getHours();
    const isClosedAllDay = CONFIG.openingHours.closedDays && CONFIG.openingHours.closedDays.includes(currentDay);
    const isClosedMidi = CONFIG.openingHours.closedMidi && CONFIG.openingHours.closedMidi.includes(currentDay) && 
                         currentHour >= (CONFIG.openingHours.midi.start - CONFIG.openingHours.preorderBuffer) && 
                         currentHour < CONFIG.openingHours.midi.end;
    
    console.log('canOrderNow:', canNow, 'currentDay:', currentDay, 'isClosedAllDay:', isClosedAllDay, 'isClosedMidi:', isClosedMidi);
    
    // Afficher/cacher le message de fermeture
    const closedWarning = document.getElementById('closedWarning');
    if (closedWarning) {
        if (!canNow) {
            closedWarning.style.display = 'block';
            // Personnaliser le message selon la situation
            const warningText = closedWarning.querySelector('p:last-child');
            if (warningText) {
                if (isClosedAllDay) {
                    warningText.innerHTML = 'Nous sommes fermés le lundi.<br>Vous pouvez programmer votre commande pour un autre jour.';
                } else if (isClosedMidi) {
                    warningText.innerHTML = 'Nous sommes fermés le dimanche midi.<br>Vous pouvez commander pour ce soir (à partir de 17h) ou programmer pour un autre jour.';
                } else {
                    warningText.innerHTML = 'Heures d\'ouverture des commandes : 10h-14h et 17h-21h<br>Vous pouvez programmer votre commande pour plus tard.';
                }
            }
        } else {
            closedWarning.style.display = 'none';
        }
    }
    
    // Références aux radios
    const maintenantRadio = document.querySelector('input[name="globalDeliveryTime"][value="maintenant"]');
    const programmeeRadio = document.querySelector('input[name="globalDeliveryTime"][value="programmee"]');
    const maintenantLabel = maintenantRadio ? maintenantRadio.closest('.time-option, label') : null;
    const isMobile = window.innerWidth <= 768;
    
    if (canNow) {
        // Heures d'ouverture : les deux options disponibles
        if (maintenantRadio) {
            maintenantRadio.disabled = false;
            maintenantRadio.checked = true;
            if (maintenantLabel) maintenantLabel.style.display = '';
            console.log('Radio maintenant enabled and checked');
        }
        if (programmeeRadio) {
            programmeeRadio.disabled = false;
        }
    } else {
        // Fermé : uniquement mode programmé
        if (maintenantRadio) {
            maintenantRadio.disabled = true;
            maintenantRadio.checked = false;
            // Sur mobile, cacher complètement le bouton Maintenant
            if (isMobile && maintenantLabel) {
                maintenantLabel.style.display = 'none';
            }
            console.log('Radio maintenant disabled (fermé)');
        }
        if (programmeeRadio) {
            programmeeRadio.disabled = false;
            programmeeRadio.checked = true;
            console.log('Radio programmee checked (fermé)');
        }
        // Afficher directement la section programmée
        const scheduledSection = document.getElementById('globalScheduledSection');
        if (scheduledSection) {
            scheduledSection.style.display = 'block';
        }
    }
    
    const scheduledSection = document.getElementById('globalScheduledSection');
    if (scheduledSection && canNow) {
        scheduledSection.style.display = 'none';
        console.log('Scheduled section hidden');
    }
    
    // Initialiser la date à aujourd'hui (ou demain si fermé aujourd'hui)
    const dateInput = document.getElementById('globalScheduledDate');
    if (dateInput) {
        const dateToSet = isClosedAllDay ? new Date(now.getTime() + 24*60*60*1000) : now; // Demain si fermé toute la journée
        const year = dateToSet.getFullYear();
        const month = String(dateToSet.getMonth() + 1).padStart(2, '0');
        const day = String(dateToSet.getDate()).padStart(2, '0');
        const dateStr = `${year}-${month}-${day}`;
        
        const today = new Date();
        const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
        
        dateInput.setAttribute('min', todayStr);
        dateInput.value = dateStr;
        console.log('Date initialized to:', dateStr);
    }
    
    const hourInput = document.getElementById('globalScheduledHour');
    if (hourInput) {
        hourInput.value = '19:00';
        console.log('Hour initialized to 19:00');
    }
    
    modal.classList.add('active');
    console.log('Modal class active added');
}

function closeDeliveryTimeModal() {
    const modal = document.getElementById('deliveryTimeModal');
    if (modal) {
        modal.classList.remove('active');
    }
    
    // Si l'utilisateur ferme sans valider et que le panier est vide, réinitialiser
    if (!deliveryTimeSet && cart.length === 0) {
        pendingCartAction = null;
    }
}

function toggleGlobalScheduledTime() {
    const selectedMode = document.querySelector('input[name="globalDeliveryTime"]:checked')?.value;
    const scheduledSection = document.getElementById('globalScheduledSection');
    
    console.log('toggleGlobalScheduledTime called, mode:', selectedMode);
    
    if (selectedMode === 'programmee') {
        scheduledSection.style.display = 'block';
        
        // Initialiser la date à aujourd'hui si vide
        const dateInput = document.getElementById('globalScheduledDate');
        if (dateInput && !dateInput.value) {
            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const day = String(today.getDate()).padStart(2, '0');
            const todayStr = `${year}-${month}-${day}`;
            dateInput.value = todayStr;
        }
        
        // Définir une heure par défaut (19h)
        const hourInput = document.getElementById('globalScheduledHour');
        if (hourInput && !hourInput.value) {
            hourInput.value = '19:00';
        }
    } else {
        scheduledSection.style.display = 'none';
    }
}

function confirmDeliveryTime() {
    const selectedMode = document.querySelector('input[name="globalDeliveryTime"]:checked')?.value;
    
    console.log('confirmDeliveryTime called, mode:', selectedMode);
    
    if (selectedMode === 'programmee') {
        const dateInput = document.getElementById('globalScheduledDate')?.value;
        const hourInput = document.getElementById('globalScheduledHour')?.value;
        
        console.log('Scheduled - date:', dateInput, 'hour:', hourInput);
        
        if (!dateInput || !hourInput) {
            showNotification('Veuillez sélectionner une date et une heure', 'error');
            return;
        }
        
        // Valider que la date/heure est dans le futur
        const [hours, minutes] = hourInput.split(':');
        const selectedDateTime = new Date(dateInput);
        selectedDateTime.setHours(parseInt(hours), parseInt(minutes), 0, 0);
        
        const now = new Date();
        
        if (selectedDateTime <= now) {
            showNotification('La date et l\'heure doivent être dans le futur', 'error');
            return;
        }
        
        // Vérifier que l'heure est dans les heures d'ouverture
        const selectedHour = parseInt(hours);
        const isValidHour = (selectedHour >= CONFIG.openingHours.midi.start && selectedHour < CONFIG.openingHours.midi.end) ||
                           (selectedHour >= CONFIG.openingHours.soir.start && selectedHour < CONFIG.openingHours.soir.end);
        
        if (!isValidHour) {
            showNotification('Veuillez choisir une heure pendant nos horaires d\'ouverture (11h-14h ou 18h-21h)', 'error');
            return;
        }
        
        scheduledDeliveryDate = dateInput;
        scheduledDeliveryHour = parseInt(hours);
        
        console.log('Saved - scheduledDeliveryDate:', scheduledDeliveryDate, 'scheduledDeliveryHour:', scheduledDeliveryHour);
        
        // Afficher le modal de confirmation
        showDeliveryConfirmation(selectedMode, dateInput, hourInput);
        return;
    } else {
        console.log('Mode: maintenant');
        // Mode maintenant : confirmer directement
        showDeliveryConfirmation(selectedMode);
        return;
    }
}

function showDeliveryConfirmation(mode, date = null, time = null) {
    // Fermer le modal de sélection d'heure
    document.getElementById('deliveryTimeModal').classList.remove('active');
    
    // Créer le message de confirmation
    let message = '';
    if (mode === 'programmee') {
        const dateObj = new Date(date);
        const dateStr = dateObj.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        message = `Vous avez choisi une livraison programmée pour le <strong>${dateStr}</strong> à <strong>${time}</strong>`;
    } else {
        message = 'Vous avez choisi une livraison <strong>dès que possible</strong>';
    }
    
    // Afficher le modal de confirmation
    const confirmModal = document.getElementById('deliveryConfirmModal');
    const confirmMessage = document.getElementById('deliveryConfirmMessage');
    
    if (confirmModal && confirmMessage) {
        confirmMessage.innerHTML = message;
        confirmModal.classList.add('active');
    }
}

function confirmDeliveryChoice() {
    // Valider le choix
    deliveryTimeMode = document.querySelector('input[name="globalDeliveryTime"]:checked')?.value || 'maintenant';
    deliveryTimeSet = true;
    
    console.log('Delivery choice confirmed - mode:', deliveryTimeMode);
    
    // Fermer le modal de confirmation
    document.getElementById('deliveryConfirmModal').classList.remove('active');
    
    // Sauvegarder dans le localStorage
    localStorage.setItem('deliveryTimeMode', deliveryTimeMode);
    localStorage.setItem('deliveryTimeSet', 'true');
    if (scheduledDeliveryDate) {
        localStorage.setItem('scheduledDeliveryDate', scheduledDeliveryDate);
    }
    if (scheduledDeliveryHour !== null) {
        localStorage.setItem('scheduledDeliveryHour', scheduledDeliveryHour.toString());
    }
    
    // Mettre à jour la disponibilité de la formule midi
    updateFormuleMidiAvailability();
    
    // Exécuter l'action en attente
    if (pendingCartAction) {
        console.log('Executing pendingCartAction');
        pendingCartAction();
        pendingCartAction = null;
    } else {
        console.log('No pending action');
    }
}

function cancelDeliveryChoice() {
    console.log('Delivery choice cancelled - returning to time selection');
    
    // Fermer le modal de confirmation
    document.getElementById('deliveryConfirmModal').classList.remove('active');
    
    // Réouvrir le modal de sélection d'heure
    document.getElementById('deliveryTimeModal').classList.add('active');
}

function cancelDeliveryTime() {
    console.log('cancelDeliveryTime called - closing modal without action');
    
    // Fermer le modal
    document.getElementById('deliveryTimeModal').classList.remove('active');
    
    // Annuler l'action en attente
    pendingCartAction = null;
    
    showNotification('Action annulée', 'info');
}

function toggleScheduledTime() {
    const deliveryTimeMode = document.querySelector('input[name="deliveryTime"]:checked')?.value;
    const scheduledSection = document.getElementById('scheduledTimeSection');
    
    if (deliveryTimeMode === 'programmee') {
        scheduledSection.style.display = 'block';
        // Définir la date min à aujourd'hui
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('scheduledDate').setAttribute('min', today);
        document.getElementById('scheduledDate').value = today;
    } else {
        scheduledSection.style.display = 'none';
        // Vérifier immédiatement les formules pour "maintenant"
        validateScheduledOrder();
    }
}

function validateScheduledOrder() {
    const deliveryTimeMode = document.querySelector('input[name="deliveryTime"]:checked')?.value;
    const warning = document.getElementById('scheduledWarning');
    const warningText = document.getElementById('scheduledWarningText');
    
    if (!warning || !warningText) return;
    
    if (deliveryTimeMode === 'maintenant') {
        warning.style.display = 'none';
        // Vérifier les formules dans le panier pour maintenant
        checkFormulasValidity();
        return;
    }
    
    const scheduledHour = document.getElementById('scheduledHour')?.value;
    if (!scheduledHour) {
        warning.style.display = 'none';
        return;
    }
    
    const [hours] = scheduledHour.split(':');
    const deliveryHour = parseInt(hours);
    
    // Vérifier les formules dans le panier
    const hasFormuleMidi = cart.some(item => item.formuleType === 'midi');
    const hasPromo2Pizzas = cart.some(item => item.type === 'promo2pizzas');
    
    let warnings = [];
    
    // Formule midi : 11h-14h
    if (hasFormuleMidi && (deliveryHour < 11 || deliveryHour >= 14)) {
        warnings.push('La formule midi n\'est disponible que de 11h à 14h');
    }
    
    // Promo 2 pizzas : après 18h
    if (hasPromo2Pizzas && deliveryHour < 18) {
        warnings.push('L\'offre 2 pizzas est disponible uniquement le soir (après 18h)');
    }
    
    if (warnings.length > 0) {
        warning.style.display = 'block';
        warningText.textContent = warnings.join('. ');
    } else {
        warning.style.display = 'none';
    }
}

function checkFormulasValidity() {
    const deliveryHour = getDeliveryHour();
    
    // Vérifier et retirer les formules/promos non valides
    const hasFormuleMidi = cart.some(item => item.formuleType === 'midi');
    const hasPromo2Pizzas = cart.some(item => item.type === 'promo2pizzas');
    
    let removed = false;
    
    // Retirer formule midi si hors horaires
    if (hasFormuleMidi && (deliveryHour < 11 || deliveryHour >= 14)) {
        cart = cart.filter(item => item.formuleType !== 'midi');
        removed = true;
        showNotification('Formule midi retirée : hors horaires de disponibilité', 'warning');
    }
    
    // Retirer promo si pas le soir
    if (hasPromo2Pizzas && deliveryHour < 18) {
        cart = cart.filter(item => item.type !== 'promo2pizzas');
        promoApplied = false;
        removed = true;
        showNotification('Promo 2 pizzas retirée : disponible uniquement le soir', 'warning');
    }
    
    if (removed) {
        saveCartToStorage();
        updateCartUI();
    }
}

function updateFormuleMidiAvailability() {
    const btn = document.getElementById('formuleMidiBtn');
    if (!btn) return;
    
    // Si l'heure de livraison n'est pas encore définie, toujours permettre le clic
    // (le modal s'ouvrira pour choisir maintenant/programmé)
    if (!deliveryTimeSet) {
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.style.cursor = 'pointer';
        btn.innerHTML = '<i class="fas fa-shopping-cart"></i> Ajouter au panier';
        return;
    }
    
    // Si on est en mode programmé, vérifier l'heure programmée
    if (deliveryTimeMode === 'programmee') {
        const scheduledHour = parseInt(scheduledDeliveryHour);
        
        // Formule midi disponible uniquement de 11h à 14h
        if (scheduledHour >= 11 && scheduledHour < 14) {
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.style.cursor = 'pointer';
            btn.innerHTML = '<i class="fas fa-shopping-cart"></i> Ajouter au panier';
        } else {
            btn.disabled = true;
            btn.style.opacity = '0.5';
            btn.style.cursor = 'not-allowed';
            btn.innerHTML = '<i class="fas fa-clock"></i> Disponible uniquement le midi (11h-14h)';
        }
        return;
    }
    
    // En mode "maintenant", vérifier l'heure actuelle
    const now = new Date();
    const hours = now.getHours();
    
    // Formule midi disponible de 11h à 14h en mode "maintenant"
    if (hours < 11 || hours >= 14) {
        btn.disabled = true;
        btn.style.opacity = '0.5';
        btn.style.cursor = 'not-allowed';
        btn.innerHTML = '<i class="fas fa-clock"></i> Disponible uniquement le midi (11h-14h)';
    } else {
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.style.cursor = 'pointer';
        btn.innerHTML = '<i class="fas fa-shopping-cart"></i> Ajouter au panier';
    }
}

// ========================================
// MODAL MARMAILLE - CHOIX VIANDE
// ========================================

function openMarmailleModal() {
    const pizza = PIZZAS_DATA.find(p => p.id === 38);
    
    // Remplir les ingrédients à retirer
    const ingredientsRemove = document.getElementById('marmailleIngredientsRemove');
    ingredientsRemove.innerHTML = '';
    pizza.ingredients.forEach(ingredient => {
        const label = document.createElement('label');
        label.className = 'ingredient-checkbox';
        label.innerHTML = `
            <input type="checkbox" value="${ingredient}">
            <span>Sans ${ingredient}</span>
        `;
        ingredientsRemove.appendChild(label);
    });

    // Réinitialiser les sélections
    document.querySelector('input[name="marmailleViande"][value="jambon"]').checked = true;
    document.querySelector('input[name="marmailleBase"][value="tomate"]').checked = true;
    document.querySelectorAll('#marmailleIngredientsRemove input[type="checkbox"]').forEach(input => {
        input.checked = false;
    });
    document.getElementById('marmailleQty').value = 1;

    // Mettre à jour le prix
    updateMarmaillePrice();

    // Ajouter les event listeners
    document.querySelectorAll('#marmailleModal input[type="radio"], #marmailleModal input[type="checkbox"], #marmailleQty').forEach(input => {
        input.removeEventListener('change', updateMarmaillePrice);
        input.addEventListener('change', updateMarmaillePrice);
    });

    document.getElementById('marmailleModal').classList.add('active');
}

function closeMarmailleModal() {
    document.getElementById('marmailleModal').classList.remove('active');
}

function updateMarmaillePrice() {
    const pizza = PIZZAS_DATA.find(p => p.id === 38);
    let price = pizza.price33; // 8.40€

    // Ajouter supplément crème si sélectionnée
    const baseInput = document.querySelector('input[name="marmailleBase"]:checked');
    if (baseInput && baseInput.value === 'creme') {
        price += 1.00; // +1€ pour crème (26cm)
    }

    // Multiplier par la quantité
    const quantity = parseInt(document.getElementById('marmailleQty').value) || 1;
    price *= quantity;

    document.getElementById('marmaillePrice').textContent = `${price.toFixed(2)}€`;
}

function addMarmailleToCart() {
    // Si c'est le premier ajout, vérifier l'heure d'abord
    if (cart.length === 0 && !deliveryTimeSet) {
        console.log('Premier ajout Marmaille - ouverture modal heure');
        pendingCartAction = () => addMarmailleToCart();
        closeMarmailleModal();
        openDeliveryTimeModal();
        return;
    }

    const pizza = PIZZAS_DATA.find(p => p.id === 38);
    const qtyInput = document.getElementById('marmailleQty');
    const quantity = parseInt(qtyInput ? qtyInput.value : 1);

    // Récupérer les sélections
    const viande = document.querySelector('input[name="marmailleViande"]:checked').value;
    const base = document.querySelector('input[name="marmailleBase"]:checked').value;
    
    // Récupérer les ingrédients retirés
    const removedIngredients = [];
    document.querySelectorAll('#marmailleIngredientsRemove input[type="checkbox"]:checked').forEach(checkbox => {
        removedIngredients.push(checkbox.value);
    });

    // Créer le nom personnalisé avec la viande choisie
    const customName = `Marmaille ${viande.charAt(0).toUpperCase() + viande.slice(1)}`;

    // Calculer le prix
    let basePrice = pizza.price33;
    if (base === 'creme') {
        basePrice += 1.00;
    }

    const cartItem = {
        id: Date.now(),
        type: 'pizza',
        pizzaId: pizza.id,
        name: customName,
        basePrice: basePrice,
        pizza: pizza,
        quantity: quantity,
        customization: {
            size: 'petite', // 26cm uniquement pour Marmaille
            base: base,
            removedIngredients: removedIngredients,
            addedIngredients: [],
            viande: viande
        },
        totalPrice: basePrice * quantity
    };

    cart.push(cartItem);
    saveCartToStorage();
    updateCartUI();
    
    // Reset quantity
    if (qtyInput) qtyInput.value = 1;

    // Fermer le modal
    closeMarmailleModal();

    // Afficher notification
    showNotification(`${customName} ajoutée au panier`);
    
    // Vérifier promo 2 pizzas
    checkPromo2Pizzas();
    
    // Ouvrir automatiquement le panier
    setTimeout(() => openCart(), 100);
}

// Vérifier toutes les 60 secondes
setInterval(updateFormuleMidiAvailability, 60000);

