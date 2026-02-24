<?php
/**
 * Configuration ntfy.sh - Notifications push Pizza Club
 * 
 * ============================================================
 * SETUP (5 minutes) :
 * 1. Installe l'app "ntfy" sur ton Android (Play Store - gratuit)
 * 2. Ouvre l'app → +  → entre le topic ci-dessous → Subscribe
 * 3. Dans Paramètres de l'app : active "Garder en vie" ou "Background"
 * 4. C'est tout ! Tu recevras chaque commande même écran verrouillé
 * ============================================================
 * 
 * Tu peux changer le topic par n'importe quel nom unique.
 * Plus c'est long/aléatoire, plus c'est privé.
 */

define('NTFY_TOPIC', 'pizzaclub-commandes-974'); // ← Change si tu veux quelque chose de plus privé
define('NTFY_SERVER', 'https://ntfy.sh');        // Serveur public gratuit

// Numéros à notifier par ntfy (liste des topics si tu veux plusieurs appareils)
// Chaque appareil s'abonne simplement au même NTFY_TOPIC dans l'app ntfy
