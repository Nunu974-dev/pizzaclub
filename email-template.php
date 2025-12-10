<?php
// Template email HTML pour confirmation client
function getClientEmailTemplate($orderData) {
    $deliveryMode = $orderData['customer']['deliveryMode'] === 'livraison' ? '🛵 LIVRAISON' : '🏃 À EMPORTER';
    
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Confirmation de commande</title>
        <style>
            body { margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4; }
            .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
            .header { background-color: #FF0000; padding: 30px; text-align: center; }
            .header img { max-width: 200px; height: auto; }
            .content { padding: 30px; color: #333333; line-height: 1.6; }
            .order-number { background-color: #fff3cd; border-left: 4px solid: #ffc107; padding: 15px; margin: 20px 0; font-size: 16px; font-weight: bold; }
            .order-details { background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .order-item { padding: 10px 0; border-bottom: 1px solid #e0e0e0; }
            .order-item:last-child { border-bottom: none; }
            .total { background-color: #FF0000; color: white; padding: 15px; text-align: center; font-size: 20px; font-weight: bold; margin: 20px 0; border-radius: 8px; }
            .info-box { background-color: #e7f3ff; border-left: 4px solid #2196F3; padding: 15px; margin: 20px 0; }
            .footer { background-color: #333333; color: white; padding: 30px; text-align: center; }
            .social-links { margin: 20px 0; }
            .social-links a { display: inline-block; margin: 0 10px; text-decoration: none; }
            .social-icon { width: 32px; height: 32px; }
            .contact-info { margin: 15px 0; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <img src="https://nunu974-dev.github.io/pizzaclub/img/New%20logo%20blanc%202022.png" alt="Pizza Club Logo">
            </div>
            
            <div class="content">
                <h2 style="color: #FF0000;">🍕 Merci <?= htmlspecialchars($orderData['customer']['firstName']) ?> !</h2>
                <p>Votre commande a bien été enregistrée chez <strong>Pizza Club</strong>.</p>
                
                <div class="order-number">
                    📋 Numéro de commande : <?= htmlspecialchars($orderData['orderNumber']) ?><br>
                    📅 Date : <?= date('d/m/Y à H:i') ?>
                </div>
                
                <h3 style="color: #FF0000;">Mode de livraison</h3>
                <div class="info-box">
                    <strong><?= $deliveryMode ?></strong><br>
                    <?php if ($orderData['customer']['deliveryMode'] === 'livraison'): ?>
                        📍 <?= htmlspecialchars($orderData['customer']['address']) ?><br>
                        <?= htmlspecialchars($orderData['customer']['postalCode']) ?> <?= htmlspecialchars($orderData['customer']['city']) ?>
                    <?php else: ?>
                        📍 À retirer au : 43 Rue Four à Chaux, 97410 Saint-Pierre
                    <?php endif; ?>
                    <br>⏱️ <strong>Temps estimé : <?= htmlspecialchars($orderData['estimatedTime']) ?></strong>
                </div>
                
                <h3 style="color: #FF0000;">Votre commande</h3>
                <div class="order-details">
                    <?php foreach ($orderData['items'] as $item): ?>
                        <div class="order-item">
                            <strong><?= htmlspecialchars($item['name']) ?></strong>
                            
                            <?php if (!empty($item['customization'])): ?>
                                <?php $custom = $item['customization']; ?>
                                
                                <?php // PIZZAS ?>
                                <?php if ($item['type'] === 'pizza'): ?>
                                    <?php 
                                    // Conversion des tailles en dimensions
                                    $sizeLabel = $custom['size'];
                                    if ($custom['size'] === 'moyenne') $sizeLabel = '33cm';
                                    if ($custom['size'] === 'grande') $sizeLabel = '40cm';
                                    ?>
                                    (<?= htmlspecialchars($sizeLabel) ?>)
                                    <?php if (isset($custom['base']) && $custom['base'] !== 'tomate'): ?>
                                        - Base <?= htmlspecialchars($custom['base']) ?>
                                    <?php endif; ?>
                                    <?php if (!empty($custom['ingredients']['added'])): ?>
                                        <br><small style="color: #28a745;">✓ Ajouts: <?= htmlspecialchars(implode(', ', $custom['ingredients']['added'])) ?></small>
                                    <?php endif; ?>
                                    <?php if (!empty($custom['ingredients']['removed'])): ?>
                                        <br><small style="color: #dc3545;">✗ Retraits: <?= htmlspecialchars(implode(', ', $custom['ingredients']['removed'])) ?></small>
                                    <?php endif; ?>
                                
                                <?php // PÂTES ?>
                                <?php elseif ($item['type'] === 'pate'): ?>
                                    <?php 
                                    // Conversion des tailles en dimensions
                                    $sizeLabel = $custom['size'];
                                    if ($custom['size'] === 'L') $sizeLabel = 'Large';
                                    if ($custom['size'] === 'XL') $sizeLabel = 'XL';
                                    ?>
                                    (<?= htmlspecialchars($sizeLabel) ?>)
                                    <?php if (isset($custom['base']) && $custom['base'] !== 'classique'): ?>
                                        - Base <?= htmlspecialchars($custom['base']) ?>
                                    <?php endif; ?>
                                    <?php if (!empty($custom['supplements'])): ?>
                                        <br><small style="color: #28a745;">+ Suppléments: <?php
                                        $names = [
                                            'champignons' => 'Champignons', 'olives' => 'Olives', 'poivrons' => 'Poivrons',
                                            'oignons' => 'Oignons', 'tomates' => 'Tomates', 'pommesDeTerre' => 'Pommes de terre',
                                            'mais' => 'Maïs', 'grosPiment' => 'Gros piment', 'fromage' => 'Fromage',
                                            'chevre' => 'Chèvre', 'gorgonzola' => 'Gorgonzola', 'parmesan' => 'Parmesan',
                                            'jambon' => 'Jambon', 'poulet' => 'Poulet', 'merguez' => 'Merguez',
                                            'chorizo' => 'Chorizo', 'boeuf' => 'Bœuf', 'lardons' => 'Lardons',
                                            'thon' => 'Thon', 'anchois' => 'Anchois', 'crevettes' => 'Crevettes',
                                            'saumon' => 'Saumon', 'oeuf' => 'Œuf', 'miel' => 'Miel'
                                        ];
                                        $suppNames = array_map(function($key) use ($names) {
                                            return $names[$key] ?? $key;
                                        }, $custom['supplements']);
                                        echo htmlspecialchars(implode(', ', $suppNames));
                                        ?></small>
                                    <?php endif; ?>
                                
                                <?php // SALADES ?>
                                <?php elseif ($item['type'] === 'salade'): ?>
                                    <?php if (isset($custom['base']) && $custom['base'] !== 'saladeverte'): ?>
                                        - Base <?= htmlspecialchars($custom['base']) ?>
                                    <?php endif; ?>
                                    <?php if (!empty($custom['options'])): ?>
                                        <br><small style="color: #0066cc;">[
                                        <?php 
                                        $optionLabels = array_map(function($opt) {
                                            if ($opt === 'pain') return 'Pain';
                                            if ($opt === 'vinaigrette-sup') return 'Vinaigrette sup.';
                                            return $opt;
                                        }, $custom['options']);
                                        echo htmlspecialchars(implode(', ', $optionLabels));
                                        ?>
                                        ]</small>
                                    <?php endif; ?>
                                    <?php if (!empty($custom['supplements'])): ?>
                                        <br><small style="color: #28a745;">+ Suppléments: <?php
                                        $names = [
                                            'champignons' => 'Champignons', 'olives' => 'Olives', 'poivrons' => 'Poivrons',
                                            'oignons' => 'Oignons', 'tomates' => 'Tomates', 'pommesDeTerre' => 'Pommes de terre',
                                            'mais' => 'Maïs', 'grosPiment' => 'Gros piment', 'fromage' => 'Fromage',
                                            'chevre' => 'Chèvre', 'gorgonzola' => 'Gorgonzola', 'parmesan' => 'Parmesan',
                                            'jambon' => 'Jambon', 'poulet' => 'Poulet', 'merguez' => 'Merguez',
                                            'chorizo' => 'Chorizo', 'boeuf' => 'Bœuf', 'lardons' => 'Lardons',
                                            'thon' => 'Thon', 'anchois' => 'Anchois', 'crevettes' => 'Crevettes',
                                            'saumon' => 'Saumon', 'oeuf' => 'Œuf', 'miel' => 'Miel'
                                        ];
                                        $suppNames = array_map(function($key) use ($names) {
                                            return $names[$key] ?? $key;
                                        }, $custom['supplements']);
                                        echo htmlspecialchars(implode(', ', $suppNames));
                                        ?></small>
                                    <?php endif; ?>
                                
                                <?php // BUNS ?>
                                <?php elseif ($item['type'] === 'bun'): ?>
                                    <?php if (isset($custom['size'])): ?>
                                        (<?= htmlspecialchars($custom['size']) ?>)
                                    <?php endif; ?>
                                    <?php if (!empty($custom['ingredients']['added'])): ?>
                                        <br><small style="color: #28a745;">✓ Ajouts: <?= htmlspecialchars(implode(', ', $custom['ingredients']['added'])) ?></small>
                                    <?php endif; ?>
                                    <?php if (!empty($custom['ingredients']['removed'])): ?>
                                        <br><small style="color: #dc3545;">✗ Retraits: <?= htmlspecialchars(implode(', ', $custom['ingredients']['removed'])) ?></small>
                                    <?php endif; ?>
                                
                                <?php // FORMULE MIDI ?>
                                <?php elseif ($item['type'] === 'formule' && isset($custom['pizza'])): ?>
                                    <br><small>🍕 <?= htmlspecialchars($custom['pizza']) ?>
                                    <?php if (!empty($custom['pizzaCustomization'])): ?>
                                        <?php 
                                        // Conversion des tailles en dimensions
                                        $sizeLabel = $custom['pizzaCustomization']['size'];
                                        if ($custom['pizzaCustomization']['size'] === 'moyenne') $sizeLabel = '33cm';
                                        if ($custom['pizzaCustomization']['size'] === 'grande') $sizeLabel = '40cm';
                                        ?>
                                        (<?= htmlspecialchars($sizeLabel) ?>)
                                        <?php if (isset($custom['pizzaCustomization']['base']) && $custom['pizzaCustomization']['base'] !== 'tomate'): ?>
                                            - Base <?= htmlspecialchars($custom['pizzaCustomization']['base']) ?>
                                        <?php endif; ?>
                                        <?php if (!empty($custom['pizzaCustomization']['ingredients']['added'])): ?>
                                            <br>&nbsp;&nbsp;✓ Ajouts: <?= htmlspecialchars(implode(', ', $custom['pizzaCustomization']['ingredients']['added'])) ?>
                                        <?php endif; ?>
                                        <?php if (!empty($custom['pizzaCustomization']['ingredients']['removed'])): ?>
                                            <br>&nbsp;&nbsp;✗ Retraits: <?= htmlspecialchars(implode(', ', $custom['pizzaCustomization']['ingredients']['removed'])) ?>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <br>🥤 <?= htmlspecialchars($custom['boisson']) ?> 33cl</small>
                                
                                <?php // FORMULE PÂTES/SALADE ?>
                                <?php elseif ($item['type'] === 'formule' && isset($custom['mainItem'])): ?>
                                    <br><small>
                                    <?php if ($custom['mainItem']['type'] === 'pate'): ?>
                                        🍝 <?= htmlspecialchars($custom['mainItem']['name']) ?>
                                        <?php if (!empty($custom['mainItem']['customization']['size'])): ?>
                                            <?php 
                                            // Conversion des tailles en dimensions
                                            $sizeLabel = $custom['mainItem']['customization']['size'];
                                            if ($custom['mainItem']['customization']['size'] === 'L') $sizeLabel = 'Large';
                                            if ($custom['mainItem']['customization']['size'] === 'XL') $sizeLabel = 'XL';
                                            ?>
                                            (<?= htmlspecialchars($sizeLabel) ?>)
                                        <?php endif; ?>
                                    <?php else: ?>
                                        🥗 <?= htmlspecialchars($custom['mainItem']['name']) ?>
                                    <?php endif; ?>
                                    <?php if (!empty($custom['mainItem']['customization']['supplements'])): ?>
                                        <br>&nbsp;&nbsp;+ <?= count($custom['mainItem']['customization']['supplements']) ?> supplément(s)
                                    <?php endif; ?>
                                    <br>🥤 <?= htmlspecialchars($custom['boisson']) ?>
                                    <br>🍰 <?= htmlspecialchars($custom['dessert']) ?></small>
                                <?php endif; ?>
                            
                            <?php // Items sans personnalisation ?>
                            <?php elseif (!empty($item['size'])): ?>
                                (<?= htmlspecialchars($item['size']) ?>)
                            <?php endif; ?>
                            
                            <br>Quantité : x<?= $item['quantity'] ?> - <?= number_format($item['totalPrice'], 2) ?>€
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="padding: 15px; background-color: #f8f9fa; border-radius: 8px;">
                    <div style="display: flex; justify-content: space-between; padding: 5px 0;">
                        <span>Sous-total :</span>
                        <span><?= number_format($orderData['subtotal'], 2) ?>€</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 5px 0;">
                        <span>Livraison :</span>
                        <span><?= number_format($orderData['deliveryFee'], 2) ?>€</span>
                    </div>
                </div>
                
                <div class="total">
                    💵 TOTAL : <?= number_format($orderData['total'], 2) ?>€
                </div>
                
                <?php if (!empty($orderData['customer']['comments'])): ?>
                    <div class="info-box">
                        💬 <strong>Votre commentaire :</strong><br><?= nl2br(htmlspecialchars($orderData['customer']['comments'])) ?>
                    </div>
                <?php endif; ?>
                
                <p style="text-align: center; margin-top: 30px;">
                    <strong>Nous préparons votre commande avec soin ! 🍕 \n aprécie a li !</strong>
                </p>
            </div>
            
            <div class="footer">
                <h3 style="margin-top: 0;">Pizza Club</h3>
                
                <div class="social-links">
                    <strong>Suivez-nous :</strong><br><br>
                    <a href="https://www.facebook.com/pizzaclub974" target="_blank" title="Facebook">
                        <img src="https://cdn-icons-png.flaticon.com/512/733/733547.png" alt="Facebook" class="social-icon">
                    </a>
                    <a href="https://www.instagram.com/pizzaclub974" target="_blank" title="Instagram">
                        <img src="https://cdn-icons-png.flaticon.com/512/2111/2111463.png" alt="Instagram" class="social-icon">
                    </a>
                    <a href="https://www.tiktok.com/@pizzaclub.re" target="_blank" title="TikTok">
                        <img src="https://cdn-icons-png.flaticon.com/512/3046/3046121.png" alt="TikTok" class="social-icon">
                    </a>
                </div>
                
                <div class="contact-info">
                    📞 <strong><a href="tel:0262668230" style="color: white; text-decoration: none;">0262 66 82 30</a></strong><br>
                    📧 <a href="mailto:commande@pizzaclub.re" style="color: white; text-decoration: none;">commande@pizzaclub.re</a><br>
                    📍 43 Rue Four à Chaux, 97410 Saint-Pierre, La Réunion
                </div>
                
                <p style="font-size: 12px; color: #999; margin-top: 20px;">
                    © 2025 Pizza Club - Tous droits réservés
                </p>
            </div>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}
?>
