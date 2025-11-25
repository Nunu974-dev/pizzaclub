// Configuration EmailJS pour Pizza Club
// Documentation: https://www.emailjs.com/docs/

// ⚠️ IMPORTANT: Vous devez créer un compte sur https://www.emailjs.com/
// et remplacer les valeurs ci-dessous par vos propres identifiants

const EMAILJS_CONFIG = {
    // Votre USER ID (Public Key) depuis https://dashboard.emailjs.com/admin/account
    USER_ID: 'VOTRE_USER_ID',
    
    // Votre SERVICE ID depuis https://dashboard.emailjs.com/admin
    SERVICE_ID: 'VOTRE_SERVICE_ID',
    
    // Votre TEMPLATE ID depuis https://dashboard.emailjs.com/admin/templates
    TEMPLATE_ID: 'VOTRE_TEMPLATE_ID'
};

// Initialisation d'EmailJS
function initEmailJS() {
    if (typeof emailjs !== 'undefined') {
        emailjs.init(EMAILJS_CONFIG.USER_ID);
        console.log('✅ EmailJS initialisé');
    } else {
        console.error('❌ EmailJS non chargé');
    }
}

// Fonction pour envoyer la commande par email
async function sendOrderByEmail(orderData) {
    try {
        // Vérifier que EmailJS est configuré
        if (EMAILJS_CONFIG.USER_ID === 'VOTRE_USER_ID') {
            console.warn('⚠️ EmailJS n\'est pas encore configuré. Consultez GUIDE_EMAILJS.md');
            alert('Configuration EmailJS requise. Consultez le guide de configuration.');
            return false;
        }

        // Préparer les données du template
        const templateParams = {
            to_email: 'contact@pizzaclub.re',
            from_name: orderData.customerName,
            customer_phone: orderData.customerPhone,
            customer_email: orderData.customerEmail || 'Non renseigné',
            order_type: orderData.orderType === 'delivery' ? 'Livraison' : 'À emporter',
            delivery_address: orderData.deliveryAddress || 'À emporter',
            order_items: orderData.items,
            subtotal: orderData.subtotal,
            delivery_fee: orderData.deliveryFee,
            total: orderData.total,
            comments: orderData.comments || 'Aucun',
            order_number: orderData.orderNumber,
            order_date: new Date().toLocaleString('fr-FR')
        };

        // Envoyer l'email via EmailJS
        const response = await emailjs.send(
            EMAILJS_CONFIG.SERVICE_ID,
            EMAILJS_CONFIG.TEMPLATE_ID,
            templateParams
        );

        console.log('✅ Email envoyé avec succès!', response);
        return true;

    } catch (error) {
        console.error('❌ Erreur lors de l\'envoi de l\'email:', error);
        alert('Erreur lors de l\'envoi de la commande. Veuillez réessayer ou appelez-nous au 0262 66 82 30');
        return false;
    }
}

// Initialiser EmailJS au chargement de la page
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initEmailJS);
} else {
    initEmailJS();
}
