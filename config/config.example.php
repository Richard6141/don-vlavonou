<?php
/**
 * Configuration Example File
 * 
 * Copiez ce fichier vers config.php et remplissez vos vraies valeurs
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'don_vlavonou');
define('DB_USER', 'root');  // À MODIFIER
define('DB_PASS', '');      // À MODIFIER
define('DB_CHARSET', 'utf8mb4');

// Payment Gateway Configuration - FedaPay
define('FEDAPAY_PUBLIC_KEY', 'pk_sandbox_XXXXXXXXXXXXX');  // À MODIFIER
define('FEDAPAY_SECRET_KEY', 'sk_sandbox_XXXXXXXXXXXXX');  // À MODIFIER
define('FEDAPAY_MODE', 'sandbox'); // 'sandbox' pour test, 'live' pour production

// Payment Gateway Configuration - KkiaPay
define('KKIAPAY_PUBLIC_KEY', 'XXXXXXXXXXXXX');   // À MODIFIER
define('KKIAPAY_PRIVATE_KEY', 'XXXXXXXXXXXXX'); // À MODIFIER
define('KKIAPAY_SECRET', 'XXXXXXXXXXXXX');      // À MODIFIER
define('KKIAPAY_MODE', 'sandbox'); // 'sandbox' pour test, 'live' pour production

// Email Configuration (Gmail)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'votre-email@gmail.com'); // À MODIFIER
define('SMTP_PASS', 'votre-mot-de-passe-app'); // À MODIFIER (Mot de passe d'application Gmail)
define('SMTP_FROM', 'noreply@vlavonou.com');
define('SMTP_FROM_NAME', 'Don Vlavonou');

// Security Settings
define('CSRF_TOKEN_EXPIRY', 3600); // 1 heure
define('SESSION_LIFETIME', 7200);  // 2 heures

// Application Settings
define('SITE_URL', 'https://votre-domaine.com'); // À MODIFIER
define('MIN_DONATION_AMOUNT', 500);       // FCFA
define('MAX_DONATION_AMOUNT', 10000000);  // FCFA

// Timezone
date_default_timezone_set('Africa/Porto-Novo');

/*
 * INSTRUCTIONS IMPORTANTES :
 * 
 * 1. Renommez ce fichier en config.php
 * 2. Remplacez toutes les valeurs marquées "À MODIFIER"
 * 3. Ne partagez JAMAIS ce fichier avec vos vraies clés
 * 4. Ajoutez config.php au .gitignore
 * 
 * COMMENT OBTENIR LES CLÉS :
 * 
 * FedaPay :
 * - Créer un compte sur https://fedapay.com
 * - Aller dans Paramètres > API
 * - Copier les clés publique et secrète
 * 
 * KkiaPay :
 * - Créer un compte sur https://kkiapay.me
 * - Aller dans Dashboard > API
 * - Copier les clés API
 * 
 * Gmail :
 * - Activer la validation en 2 étapes
 * - Générer un mot de passe d'application :
 *   https://myaccount.google.com/apppasswords
 */

?>
