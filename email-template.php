<?php
// Template email HTML pour confirmation client
function getClientEmailTemplate($orderData) {
    $deliveryMode = $orderData['customer']['deliveryMode'] === 'livraison' ? '🛵 LIVRAISON' : '🏃 À EMPORTER';
    
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <!-- EMAIL VERSION: 20251211b -->
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Confirmation de commande</title>
        <style>
            body { margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4; }
            .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
            .header { background-color: #FF0000; padding: 30px; text-align: center; }
            .header img { max-width: 200px; height: auto; }
            .content { padding: 30px; color: #333333; line-height: 1.6; }
            .order-number { background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; font-size: 16px; font-weight: bold; }
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
                <img src="https://www.pizzaclub.re/img/Logoblanc.png" alt="Pizza Club Logo" style="max-width: 200px;">
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

                    <?php
                    $isScheduled = !empty($orderData['scheduledDate']) && $orderData['scheduledTime'] !== null;
                    if ($isScheduled):
                        $scheduledHour = (int)$orderData['scheduledTime'];
                        $deliveryStart = $scheduledHour . ':00';
                        $deliveryEnd = ($scheduledHour + 1) . ':00';
                    ?>
                        <br>
                        <strong style="color: #FF6600;">📅 Livraison programmée :</strong><br>
                        <span style="color: #666;">Date : <?= htmlspecialchars($orderData['scheduledDate']) ?></span><br>
                        <span style="color: #666;">Créneau : <?= $deliveryStart ?> - <?= $deliveryEnd ?></span>
                    <?php else: ?>
                        <br>
                        <strong style="color: #28a745;">⚡ Commande IMMÉDIATE</strong>
                    <?php endif; ?>

                    <?php if ($orderData['customer']['deliveryMode'] === 'livraison'): ?>
                        <br><br>
                        <div style="background-color: #fff3cd; border: 2px solid #ffc107; border-radius: 6px; padding: 10px; color: #856404; font-size: 13px;">
                            📞 <strong>Nous vous appellerons pour confirmer votre commande.</strong><br>
                            Comptez un minimum d'1h d'attente, variable selon les commandes en cours.
                        </div>
                    <?php endif; ?>
                </div>
                
                <h3 style="color: #FF0000;">Votre commande</h3>
                <div class="order-details">
                <?php foreach ($orderData['items'] as $item): ?>
                    <div class="order-item">
                        <strong><?= htmlspecialchars($item['name']) ?></strong> x<?= $item['quantity'] ?>
                        
                        <?php if (!empty($item['customization'])): ?>
                            <?php $custom = $item['customization']; ?>
                            
                            <?php // PIZZAS ?>
                            <?php if ($item['type'] === 'pizza'): ?>
                                <br>
                                <?php 
                                $sizeLabel = $custom['size'];
                                if ($custom['size'] === 'moyenne') $sizeLabel = '33cm';
                                if ($custom['size'] === 'grande') $sizeLabel = '40cm';
                                ?>
                                <small><strong>📏 TAILLE:</strong> <?= htmlspecialchars($sizeLabel) ?></small>
                                
                                <?php 
                                // BASE - toujours afficher
                                $baseLabel = isset($custom['base']) ? ($custom['base'] === 'creme' ? 'Crème' : ($custom['base'] === 'tomate' ? 'Tomate' : $custom['base'])) : '(non spécifiée)';
                                ?>
                                <br><small><strong>🍕 BASE:</strong> <?= htmlspecialchars($baseLabel) ?></small>
                                
                                <?php 
                                // RETIRER - chercher dans toutes les variantes possibles + toujours afficher
                                $removed = $custom['removedIngredients'] ?? $custom['ingredients']['removed'] ?? $custom['removed'] ?? [];
                                ?>
                                <br><small style="color: #dc3545;"><strong>❌ RETIRER:</strong> 
                                <?php if (!empty($removed)): ?>
                                    <?= htmlspecialchars(implode(', ', $removed)) ?>
                                <?php else: ?>
                                    (aucun)
                                <?php endif; ?>
                                </small>
                                
                                <?php 
                                // AJOUTER - chercher dans toutes les variantes possibles + toujours afficher
                                $added = $custom['addedIngredients'] ?? $custom['ingredients']['added'] ?? $custom['added'] ?? [];
                                ?>
                                <br><small style="color: #28a745;"><strong>➕ AJOUTER:</strong> 
                                <?php if (!empty($added)): ?>
                                    <?= htmlspecialchars(implode(', ', $added)) ?>
                                <?php else: ?>
                                    (aucun)
                                <?php endif; ?>
                                </small>
                            
                            <?php // PÂTES ?>
                            <?php elseif ($item['type'] === 'pate'): ?>
                                <br>
                                <?php 
                                $sizeLabel = $custom['size'];
                                if ($custom['size'] === 'L') $sizeLabel = 'Large';
                                if ($custom['size'] === 'XL') $sizeLabel = 'XL';
                                ?>
                                <small><strong>📏 TAILLE:</strong> <?= htmlspecialchars($sizeLabel) ?></small>
                                
                                <?php if (isset($custom['base'])): ?>
                                    <br><small><strong>🍝 BASE:</strong> <?= htmlspecialchars($custom['base']) ?></small>
                                <?php endif; ?>
                                
                                <?php if (!empty($custom['supplements'])): ?>
                                    <br><small style="color: #28a745;"><strong>➕ SUPPLÉMENTS:</strong> <?php
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
                                <?php if (isset($custom['size'])): ?>
                                    <br><small><strong>📏 TAILLE:</strong> <?= htmlspecialchars($custom['size']) ?></small>
                                <?php endif; ?>
                                
                                <?php if (isset($custom['base'])): ?>
                                    <br><small><strong>🥗 BASE:</strong> <?= htmlspecialchars($custom['base']) ?></small>
                                <?php endif; ?>
                                
                                <?php if (!empty($custom['options'])): ?>
                                    <br><small style="color: #0066cc;"><strong>OPTIONS:</strong> 
                                    <?php 
                                    $optionLabels = array_map(function($opt) {
                                        if ($opt === 'pain') return 'Pain';
                                        if ($opt === 'vinaigrette-sup') return 'Vinaigrette sup.';
                                        return $opt;
                                    }, $custom['options']);
                                    echo htmlspecialchars(implode(', ', $optionLabels));
                                    ?></small>
                                <?php endif; ?>
                                
                                <?php if (!empty($custom['supplements'])): ?>
                                    <br><small style="color: #28a745;"><strong>➕ SUPPLÉMENTS:</strong> <?php
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
                                
                                <?php // ROLLS ?>
                                <?php elseif ($item['type'] === 'roll'): ?>
                                    <?php if (isset($custom['size'])): ?>
                                        (<?= htmlspecialchars($custom['size']) ?>)
                                    <?php endif; ?>
                                    
                                    <?php // INGRÉDIENTS CHOISIS ?>
                                    <?php if (!empty($custom['ingredients'])): ?>
                                        <br><small><strong>🥗 INGRÉDIENTS:</strong> 
                                        <?php
                                        if (is_array($custom['ingredients'])) {
                                            echo htmlspecialchars(implode(', ', $custom['ingredients']));
                                        } else {
                                            echo htmlspecialchars($custom['ingredients']);
                                        }
                                        ?>
                                        </small>
                                    <?php endif; ?>
                                    
                                    <?php // BASE (crème ou tomate) ?>
                                    <?php if (!empty($custom['base'])): ?>
                                        <br><small><strong>🌯 BASE:</strong> 
                                        <?= $custom['base'] === 'creme' ? 'Crème' : 'Tomate' ?>
                                        </small>
                                    <?php endif; ?>
                                    
                                    <?php // RETIRER ?>
                                    <?php
                                    $removed = $custom['removedIngredients'] ?? $custom['ingredients']['removed'] ?? $custom['removed'] ?? [];
                                    if (!empty($removed)):
                                    ?>
                                        <br><small style="color: #dc3545;"><strong>❌ RETIRER:</strong> <?= htmlspecialchars(implode(', ', $removed)) ?></small>
                                    <?php endif; ?>
                                
                                <?php // BUNS ?>
                                <?php elseif ($item['type'] === 'bun'): ?>
                                    <?php if (isset($custom['size'])): ?>
                                        (<?= htmlspecialchars($custom['size']) ?>)
                                    <?php endif; ?>
                                    
                                    <?php // INGRÉDIENTS CHOISIS ?>
                                    <?php if (!empty($custom['ingredients'])): ?>
                                        <br><small><strong>🥗 INGRÉDIENTS:</strong> 
                                        <?php
                                        if (is_array($custom['ingredients'])) {
                                            echo htmlspecialchars(implode(', ', $custom['ingredients']));
                                        } else {
                                            echo htmlspecialchars($custom['ingredients']);
                                        }
                                        ?>
                                        </small>
                                    <?php endif; ?>
                                    
                                    <?php // FORMAT (x1 ou x3) ?>
                                    <?php if (!empty($custom['format'])): ?>
                                        <br><small><strong>📦 FORMAT:</strong> <?= htmlspecialchars($custom['format']) ?></small>
                                    <?php endif; ?>
                                    
                                    <?php // TYPE (pizza ou pâte) ?>
                                    <?php if (!empty($custom['type'])): ?>
                                        <br><small><strong>🍕 TYPE:</strong> 
                                        <?php
                                        if ($custom['type'] === 'pizza') echo 'BASE PIZZA';
                                        elseif ($custom['type'] === 'pate') echo 'BASE PÂTE';
                                        else echo htmlspecialchars($custom['type']);
                                        ?>
                                        </small>
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
                                        <?php if (!empty($custom['pizzaCustomization']['addedIngredients'])): ?>
                                            <br>&nbsp;&nbsp;✓ Ajouts: <?= htmlspecialchars(implode(', ', $custom['pizzaCustomization']['addedIngredients'])) ?>
                                        <?php endif; ?>
                                        <?php if (!empty($custom['pizzaCustomization']['removedIngredients'])): ?>
                                            <br>&nbsp;&nbsp;✗ Retraits: <?= htmlspecialchars(implode(', ', $custom['pizzaCustomization']['removedIngredients'])) ?>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <br>🥤 <?= htmlspecialchars($custom['boisson']) ?> 33cl</small>
                                
                                <?php // FORMULE PÂTES/SALADE ?>
                                <?php elseif ($item['type'] === 'formule' && isset($custom['mainItem']) && is_array($custom['mainItem'])): ?>
                                    <br><small>
                                    <?php if ($custom['mainItem']['type'] === 'pate'): ?>
                                        🍝 <?= htmlspecialchars($custom['mainItem']['name']) ?>
                                        <?php if (!empty($custom['mainItem']['customization']['size'])): ?>
                                            <?php 
                                            $sizeLabel = $custom['mainItem']['customization']['size'];
                                            if ($sizeLabel === 'L') $sizeLabel = 'Large';
                                            if ($sizeLabel === 'XL') $sizeLabel = 'XL';
                                            ?>
                                            (<?= htmlspecialchars($sizeLabel) ?>)
                                        <?php endif; ?>
                                        <?php if (!empty($custom['mainItem']['customization']['base'])): ?>
                                            - Base <?= htmlspecialchars($custom['mainItem']['customization']['base']) ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        🥗 <?= htmlspecialchars($custom['mainItem']['name']) ?>
                                        <?php if (!empty($custom['mainItem']['customization']['base'])): ?>
                                            - Base <?= htmlspecialchars($custom['mainItem']['customization']['base']) ?>
                                        <?php endif; ?>
                                        <?php if (!empty($custom['mainItem']['customization']['options'])): ?>
                                            <br>&nbsp;&nbsp;Options: <?= htmlspecialchars(implode(', ', $custom['mainItem']['customization']['options'])) ?>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if (!empty($custom['mainItem']['customization']['supplements'])): ?>
                                        <br>&nbsp;&nbsp;+ <?= htmlspecialchars(implode(', ', $custom['mainItem']['customization']['supplements'])) ?>
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
                    <?php if (!empty($orderData['promoCode']) && !empty($orderData['discount']) && $orderData['discount'] > 0): ?>
                    <div style="display: flex; justify-content: space-between; padding: 5px 0; color: #2e7d32; font-weight: bold;">
                        <span>🏷 Code promo (<?= htmlspecialchars($orderData['promoCode']) ?>) :</span>
                        <span>-<?= number_format($orderData['discount'], 2) ?>€</span>
                    </div>
                    <?php endif; ?>
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
                    © 2025 Pizza Club - Tous droits réservés<br>
                    <span style="font-size: 10px;">📧 Email v20251211c</span>
                </p>
            </div>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}
?>
