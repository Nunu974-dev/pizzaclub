<?php
// Template email HTML pour cuisine (restaurant)
function getKitchenEmailTemplate($orderData) {
    $deliveryMode = $orderData['customer']['deliveryMode'] === 'livraison' ? '🛵 LIVRAISON' : '🏃 À EMPORTER';
    
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <!-- EMAIL VERSION: 20251211b -->
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Nouvelle commande</title>
        <style>
            body { margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4; }
            .container { max-width: 900px; margin: 0 auto; background-color: #ffffff; }
            .header { background-color: #FF0000; padding: 25px; text-align: center; color: white; }
            .header h1 { margin: 0; font-size: 28px; color: white; }
            .mode-badge { background-color: #FFC107; color: #000; padding: 20px; text-align: center; font-size: 24px; font-weight: bold; margin: 0; }
            .content { padding: 30px; color: #333333; background-color: #ffffff; }
            .section { margin: 25px 0; padding: 20px; background-color: #f8f9fa; border: 2px solid #e0e0e0; }
            .section h3 { margin-top: 0; color: #FF0000; font-size: 20px; }
            .client-info { font-size: 16px; line-height: 2; color: #333; }
            .client-info strong { color: #000; }
            .order-item { background-color: #ffffff; border: 2px solid #e0e0e0; padding: 20px; margin: 20px 0; }
            .order-item-header { font-size: 20px; font-weight: bold; color: #FF0000; background-color: #fff3cd; margin: -20px -20px 15px -20px; padding: 15px 20px; border-bottom: 3px solid #ffc107; }
            .item-detail { margin: 10px 0; padding: 10px; background-color: #f8f9fa; border-left: 3px solid #FF0000; }
            .item-detail-label { display: inline-block; min-width: 180px; font-weight: bold; color: #000; font-size: 15px; }
            .item-detail-value { color: #333; font-size: 15px; }
            .empty-value { color: #999; font-style: italic; }
            .price { background-color: #28a745; color: white; padding: 12px; text-align: center; font-size: 20px; font-weight: bold; margin-top: 15px; }
            .total-section { background-color: #FF0000; color: white; padding: 25px; text-align: center; margin: 30px 0 0 0; }
            .total-section h2 { margin: 0; font-size: 32px; color: white; }
            .footer { background-color: #333; color: white; padding: 15px; text-align: center; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class="container">
            <!-- Header -->
            <div class="header">
                <h1>🚨 NOUVELLE COMMANDE</h1>
                <p style="margin: 5px 0 0 0; font-size: 18px;"><?= htmlspecialchars($orderData['orderNumber']) ?></p>
            </div>
            
            <!-- Mode de livraison -->
            <div class="mode-badge">
                <?= $deliveryMode ?>
            </div>
            
            <!-- Horaire de livraison -->
            <div class="content">
                <div class="section" style="background-color: #fff3cd; border: 2px solid #ffc107;">
                    <h3 style="color: #856404;">⏰ HORAIRE DE LIVRAISON</h3>
                    <?php 
                    $isScheduled = !empty($orderData['scheduledDate']) && $orderData['scheduledTime'] !== null;
                    if ($isScheduled): 
                        // Déterminer le créneau de livraison (45 min après ouverture)
                        $scheduledHour = (int)$orderData['scheduledTime'];
                        $deliveryStart = $scheduledHour . ':00';
                        $deliveryEnd = ($scheduledHour + 1) . ':00';
                        
                        // Déterminer si c'est le midi ou le soir
                        $period = ($scheduledHour < 16) ? 'MIDI' : 'SOIR';
                        $firstDeliveryTime = ($scheduledHour < 16) ? '11:45' : '18:45';
                    ?>
                        <p style="margin: 0; color: #856404;"><strong>📅 Date :</strong> <?= htmlspecialchars($orderData['scheduledDate']) ?></p>
                        <p style="margin: 10px 0 0 0; color: #856404;"><strong>🕐 Créneau demandé :</strong> <?= $deliveryStart ?> - <?= $deliveryEnd ?></p>
                        <p style="margin: 10px 0 0 0; color: #856404; font-size: 13px;"><em>ℹ️ Première livraison <?= $period ?> : <?= $firstDeliveryTime ?></em></p>
                    <?php else: ?>
                        <p style="margin: 0; color: #28a745;"><strong>⚡ Commande IMMÉDIATE</strong></p>
                        <p style="margin: 10px 0 0 0; color: #666;"><em>À préparer et livrer dès que possible</em></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Informations client -->
            <div class="content">
                <div class="section">
                    <h3>👤 INFORMATIONS CLIENT</h3>
                    <div class="client-info">
                        <strong>Nom :</strong> <?= htmlspecialchars($orderData['customer']['firstName']) ?> <?= htmlspecialchars($orderData['customer']['lastName']) ?><br>
                        <strong>Téléphone :</strong> <?= htmlspecialchars($orderData['customer']['phone']) ?><br>
                        <?php if (!empty($orderData['customer']['email'])): ?>
                            <strong>Email :</strong> <?= htmlspecialchars($orderData['customer']['email']) ?><br>
                        <?php endif; ?>
                        
                        <?php if ($orderData['customer']['deliveryMode'] === 'livraison'): ?>
                            <strong>Adresse :</strong> <?= htmlspecialchars($orderData['customer']['address']) ?><br>
                            <strong>Code postal :</strong> <?= htmlspecialchars($orderData['customer']['postalCode']) ?> <?= htmlspecialchars($orderData['customer']['city']) ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Détail de la commande -->
                <div class="section">
                    <h3>🍕 DÉTAIL DE LA COMMANDE</h3>
                    
                    <?php foreach ($orderData['items'] as $item): ?>
                        <?php $custom = $item['customization'] ?? []; ?>
                        
                        <div class="order-item">
                            <div class="order-item-header">
                                <?php 
                                $productType = '';
                                if (isset($item['type'])) {
                                    switch($item['type']) {
                                        case 'pizza': $productType = '[PIZZA] '; break;
                                        case 'pate': $productType = '[PÂTE] '; break;
                                        case 'salade': $productType = '[SALADE] '; break;
                                        case 'bun': $productType = '[BUN] '; break;
                                        case 'roll': $productType = '[ROLL] '; break;
                                        case 'dessert': $productType = '[DESSERT] '; break;
                                        case 'formule': $productType = '[FORMULE] '; break;
                                    }
                                }
                                ?>
                                <?= $productType . htmlspecialchars($item['name']) ?> x<?= $item['quantity'] ?>
                            </div>
                            
                            <?php if ($item['type'] === 'formule'): ?>
                                <!-- FORMULES -->
                                <?php if (isset($custom['pizza'])): ?>
                                    <div class="item-detail">
                                        <span class="item-detail-label">🍕 Pizza :</span>
                                        <span class="item-detail-value"><?= htmlspecialchars($custom['pizza']) ?></span>
                                    </div>
                                    <?php if (!empty($custom['pizzaSize'])): ?>
                                        <div class="item-detail">
                                            <span class="item-detail-label">📏 Taille :</span>
                                            <span class="item-detail-value"><?= $custom['pizzaSize'] === 'moyenne' ? '33cm' : '40cm' ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($custom['pizzaBase'])): ?>
                                        <div class="item-detail">
                                            <span class="item-detail-label">🍕 Base :</span>
                                            <span class="item-detail-value"><?= $custom['pizzaBase'] === 'creme' ? 'Crème' : 'Tomate' ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($custom['pizzaAdded'])): ?>
                                        <div class="item-detail">
                                            <span class="item-detail-label">➕ Ajouts :</span>
                                            <span class="item-detail-value"><?= htmlspecialchars(implode(', ', $custom['pizzaAdded'])) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($custom['pizzaRemoved'])): ?>
                                        <div class="item-detail">
                                            <span class="item-detail-label">❌ Retraits :</span>
                                            <span class="item-detail-value"><?= htmlspecialchars(implode(', ', $custom['pizzaRemoved'])) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($custom['boisson'])): ?>
                                        <div class="item-detail">
                                            <span class="item-detail-label">🥤 Boisson :</span>
                                            <span class="item-detail-value"><?= htmlspecialchars($custom['boisson']) ?> (33cl)</span>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                            <?php else: ?>
                                <!-- PRODUITS INDIVIDUELS (PIZZAS, PÂTES, SALADES, etc.) -->
                                
                                <!-- TAILLE - TOUJOURS AFFICHER -->
                                <div class="item-detail">
                                    <span class="item-detail-label">📏 Taille :</span>
                                    <span class="item-detail-value">
                                        <?php
                                        $sizeLabel = '';
                                        if (!empty($custom['size'])) {
                                            switch($custom['size']) {
                                                case 'moyenne': $sizeLabel = '33cm'; break;
                                                case 'grande': $sizeLabel = '40cm'; break;
                                                case 'L': $sizeLabel = 'Large'; break;
                                                case 'XL': $sizeLabel = 'XL'; break;
                                                default: $sizeLabel = $custom['size'];
                                            }
                                        }
                                        if (!empty($sizeLabel)) {
                                            echo htmlspecialchars($sizeLabel);
                                        } else {
                                            echo '<span class="empty-value">(non spécifiée)</span>';
                                        }
                                        ?>
                                    </span>
                                </div>
                                
                                <!-- BASE - TOUJOURS AFFICHER -->
                                <div class="item-detail">
                                    <span class="item-detail-label">
                                        <?php
                                        if ($item['type'] === 'pizza') echo '🍕 Base pizza :';
                                        elseif ($item['type'] === 'pate') echo '🍝 Base pâte :';
                                        elseif ($item['type'] === 'roll' || $item['type'] === 'bun') echo '🌯 Base :';
                                        else echo '🍴 Base :';
                                        ?>
                                    </span>
                                    <span class="item-detail-value">
                                        <?php
                                        if (!empty($custom['base'])) {
                                            if (in_array($item['type'], ['pizza', 'roll', 'bun'])) {
                                                echo $custom['base'] === 'creme' ? 'Crème' : 'Tomate';
                                            } else {
                                                echo htmlspecialchars($custom['base']);
                                            }
                                        } else {
                                            echo '<span class="empty-value">(non spécifiée)</span>';
                                        }
                                        ?>
                                    </span>
                                </div>
                                
                                <!-- RETIRER - TOUJOURS AFFICHER -->
                                <div class="item-detail">
                                    <span class="item-detail-label">❌ Retirer :</span>
                                    <span class="item-detail-value">
                                        <?php
                                        $removedList = $custom['removed'] ?? $custom['removedIngredients'] ?? [];
                                        if (!empty($removedList) && is_array($removedList) && count($removedList) > 0) {
                                            echo htmlspecialchars(implode(', ', $removedList));
                                        } else {
                                            echo '<span class="empty-value">(aucun)</span>';
                                        }
                                        ?>
                                    </span>
                                </div>
                                
                                <!-- AJOUTER - TOUJOURS AFFICHER -->
                                <div class="item-detail">
                                    <span class="item-detail-label">➕ Ajouter :</span>
                                    <span class="item-detail-value">
                                        <?php
                                        $addedList = $custom['added'] ?? $custom['addedIngredients'] ?? [];
                                        if (!empty($addedList) && is_array($addedList) && count($addedList) > 0) {
                                            echo htmlspecialchars(implode(', ', $addedList));
                                        } else {
                                            echo '<span class="empty-value">(aucun)</span>';
                                        }
                                        ?>
                                    </span>
                                </div>
                                
                                <!-- INGRÉDIENTS (buns et rolls) -->
                                <?php if (($item['type'] === 'bun' || $item['type'] === 'roll') && !empty($custom['ingredients'])): ?>
                                    <div class="item-detail">
                                        <span class="item-detail-label">🥗 Ingrédients choisis :</span>
                                        <span class="item-detail-value">
                                            <?php
                                            if (is_array($custom['ingredients'])) {
                                                echo htmlspecialchars(implode(', ', $custom['ingredients']));
                                            } else {
                                                echo htmlspecialchars($custom['ingredients']);
                                            }
                                            ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- FORMAT (buns x1/x3) et TYPE (pâte/pizza) -->
                                <?php if ($item['type'] === 'bun'): ?>
                                    <?php if (!empty($custom['format'])): ?>
                                        <div class="item-detail">
                                            <span class="item-detail-label">📦 Format :</span>
                                            <span class="item-detail-value"><?= htmlspecialchars($custom['format']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($custom['type'])): ?>
                                        <div class="item-detail">
                                            <span class="item-detail-label">🍕 Type :</span>
                                            <span class="item-detail-value">
                                                <?php
                                                $bunType = $custom['type'];
                                                if ($bunType === 'pizza') echo '🍕 BASE PIZZA';
                                                elseif ($bunType === 'pate') echo '🍝 BASE PÂTE';
                                                else echo htmlspecialchars($bunType);
                                                ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <!-- SUPPLÉMENTS (pâtes/salades) -->
                                <?php if (!empty($custom['supplements']) && is_array($custom['supplements'])): ?>
                                    <div class="item-detail">
                                        <span class="item-detail-label">➕ Suppléments :</span>
                                        <span class="item-detail-value"><?= htmlspecialchars(implode(', ', $custom['supplements'])) ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- OPTIONS (salades) -->
                                <?php if (!empty($custom['options']) && is_array($custom['options'])): ?>
                                    <div class="item-detail">
                                        <span class="item-detail-label">🔧 Options :</span>
                                        <span class="item-detail-value">
                                            <?php
                                            $optionLabels = array_map(function($opt) {
                                                if ($opt === 'pain') return 'Avec pain';
                                                if ($opt === 'vinaigrette-sup') return 'Vinaigrette sup.';
                                                return $opt;
                                            }, $custom['options']);
                                            echo htmlspecialchars(implode(', ', $optionLabels));
                                            ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                
                            <?php endif; ?>
                            
                            <!-- QUANTITÉ -->
                            <div class="item-detail">
                                <span class="item-detail-label">📦 Quantité :</span>
                                <span class="item-detail-value"><?= $item['quantity'] ?></span>
                            </div>
                            
                            <!-- PRIX -->
                            <div class="price">
                                💰 Prix : <?= number_format($item['totalPrice'], 2) ?> €
                            </div>
                        </div>
                        
                    <?php endforeach; ?>
                </div>
                
                <!-- Commentaires -->
                <?php if (!empty($orderData['customer']['comments'])): ?>
                    <div class="section">
                        <h3>💬 COMMENTAIRE CLIENT</h3>
                        <p style="font-size: 16px; background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;">
                            <?= nl2br(htmlspecialchars($orderData['customer']['comments'])) ?>
                        </p>
                    </div>
                <?php endif; ?>
                
                <!-- Temps estimé -->
                <div class="section">
                    <h3>⏱️ TEMPS ESTIMÉ</h3>
                    <p style="font-size: 18px; font-weight: bold; color: #FF0000;"><?= htmlspecialchars($orderData['estimatedTime']) ?></p>
                </div>
            </div>
            
            <!-- Total -->
            <div class="total-section">
                <p style="margin: 0; font-size: 16px;">Sous-total : <?= number_format($orderData['subtotal'], 2) ?> €</p>
                <?php if ($orderData['customer']['deliveryMode'] === 'livraison'): ?>
                    <p style="margin: 5px 0; font-size: 16px;">Frais de livraison : <?= number_format($orderData['deliveryFee'], 2) ?> €</p>
                <?php endif; ?>
                <?php if (!empty($orderData['promoCode']) && !empty($orderData['discount']) && $orderData['discount'] > 0): ?>
                    <p style="margin: 5px 0; font-size: 16px; color: #ffe082;">🏷 Code promo (<?= htmlspecialchars($orderData['promoCode']) ?>) : -<?= number_format($orderData['discount'], 2) ?> €</p>
                <?php endif; ?>
                <h2 style="margin: 10px 0 0 0;">TOTAL : <?= number_format($orderData['total'], 2) ?> €</h2>
            </div>
            
            <!-- Footer -->
            <div class="footer">
                <p style="margin: 0;">Pizza Club - <?= date('d/m/Y à H:i') ?></p>
                <p style="margin: 5px 0 0 0; font-size: 10px; color: #999;">📧 Email v20251211f - Couleurs mail client</p>
            </div>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}
?>
