# Plateforme de Don pour Vlavonou

Une plateforme web sécurisée, rapide et fluide pour collecter des dons en ligne avec FedaPay et KkiaPay.

## 🚀 Fonctionnalités

- ✅ Page unique simple et épurée
- ✅ Paiements sécurisés via FedaPay et KkiaPay
- ✅ Reçus automatiques par email
- ✅ Protection CSRF et validation des données
- ✅ Rate limiting anti-DDoS
- ✅ Design responsive (mobile-friendly)
- ✅ Logs de sécurité
- ✅ SSL/HTTPS obligatoire

## 📋 Prérequis

- Serveur web Apache/Nginx
- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Certificat SSL (Let's Encrypt gratuit)
- Compte FedaPay (https://fedapay.com)
- Compte KkiaPay (https://kkiapay.me)

## 📦 Installation

### 1. Copier les fichiers sur votre serveur

```bash
# Cloner le dépôt sur votre serveur web
git clone https://github.com/<votre-username>/don-vlavonou.git
cd don-vlavonou
```

### 2. Configuration de la base de données

```bash
# Se connecter à MySQL
mysql -u root -p

# Importer le script SQL
mysql -u root -p < config/database.sql

# Ou via phpMyAdmin :
# - Créer la base de données "don_vlavonou"
# - Importer le fichier config/database.sql
```

### 3. Configuration du projet

Éditer le fichier `config/config.php` :

```php
// Base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'don_vlavonou');
define('DB_USER', 'votre_user');      // À MODIFIER
define('DB_PASS', 'votre_password');  // À MODIFIER

// FedaPay
define('FEDAPAY_PUBLIC_KEY', 'pk_live_VOTRE_CLE');   // À MODIFIER
define('FEDAPAY_SECRET_KEY', 'sk_live_VOTRE_CLE');   // À MODIFIER
define('FEDAPAY_MODE', 'live'); // 'sandbox' pour test, 'live' pour production

// KkiaPay
define('KKIAPAY_PUBLIC_KEY', 'VOTRE_CLE_PUBLIQUE');  // À MODIFIER
define('KKIAPAY_PRIVATE_KEY', 'VOTRE_CLE_PRIVEE');   // À MODIFIER
define('KKIAPAY_SECRET', 'VOTRE_SECRET');            // À MODIFIER
define('KKIAPAY_MODE', 'live'); // 'sandbox' pour test, 'live' pour production

// Email SMTP (Gmail)
define('SMTP_USER', 'votre-email@gmail.com');  // À MODIFIER
define('SMTP_PASS', 'votre-app-password');      // À MODIFIER

// URL du site
define('SITE_URL', 'https://votre-domaine.com'); // À MODIFIER
```

### 4. Obtenir les clés API

**FedaPay :**
1. Créer un compte sur https://fedapay.com
2. Aller dans Paramètres > API
3. Copier vos clés publique et secrète

**KkiaPay :**
1. Créer un compte sur https://kkiapay.me
2. Aller dans Dashboard > API
3. Copier vos clés API

### 5. Configuration Gmail pour les emails

1. Activer la validation en 2 étapes sur votre compte Gmail
2. Générer un mot de passe d'application :
   - https://myaccount.google.com/apppasswords
   - Sélectionner "Mail" et "Autre"
   - Copier le mot de passe généré dans `SMTP_PASS`

### 6. Installer le certificat SSL (HTTPS)

**Avec Let's Encrypt (gratuit) :**

```bash
# Installation de Certbot
sudo apt-get update
sudo apt-get install certbot python3-certbot-apache

# Obtenir le certificat
sudo certbot --apache -d votre-domaine.com

# Renouvellement automatique
sudo certbot renew --dry-run
```

### 7. Permissions des fichiers

```bash
# Sur votre serveur Linux
chmod 755 public/
chmod 755 backend/
chmod 644 config/config.php
chmod 600 config/database.sql  # Protéger le fichier SQL
```

### 8. Ajouter les images

Placez vos images dans le dossier `assets/images/` :
- `logo.png` - Logo de l'Union Progressiste
- `vlavonou.jpg` - Photo de Vlavonou
- `favicon.png` - Favicon du site
- `fedapay-logo.png` - Logo FedaPay
- `kkiapay-logo.png` - Logo KkiaPay

## 🧪 Test

### Mode Test (Sandbox)

1. Dans `config/config.php`, mettre :
```php
define('FEDAPAY_MODE', 'sandbox');
define('KKIAPAY_MODE', 'sandbox');
```

2. Utiliser les clés sandbox de FedaPay et KkiaPay

3. Tester avec les numéros de test fournis par les plateformes

### Mode Production

1. Changer les modes en 'live'
2. Utiliser les vraies clés API
3. Tester avec un petit montant réel

## 📊 Administration

### Voir les donations

```sql
-- Se connecter à MySQL
mysql -u root -p don_vlavonou

-- Voir toutes les donations
SELECT * FROM donations ORDER BY created_at DESC;

-- Voir les donations complétées
SELECT * FROM donations WHERE status = 'completed';

-- Statistiques
SELECT * FROM donation_statistics;
```

### Exporter les données

```sql
-- Exporter en CSV
SELECT 
    transaction_id, 
    CONCAT(first_name, ' ', last_name) as donateur,
    email,
    phone,
    amount,
    payment_method,
    status,
    created_at
FROM donations
WHERE status = 'completed'
INTO OUTFILE '/tmp/donations.csv'
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n';
```

## 🔒 Sécurité

✅ Protection CSRF activée
✅ Validation et sanitisation des données
✅ Rate limiting (max 5 tentatives / 5 minutes)
✅ Logs de sécurité
✅ Headers de sécurité HTTP
✅ HTTPS obligatoire
✅ Protection des fichiers sensibles
✅ Requêtes préparées (SQL injection)

## 📱 Responsive Design

La plateforme est optimisée pour :
- 📱 Mobile (smartphones)
- 📱 Tablettes
- 💻 Desktop

## 🆘 Dépannage

### Erreur de connexion à la base de données
- Vérifier les identifiants dans `config/config.php`
- Vérifier que MySQL est démarré
- Vérifier que la base `don_vlavonou` existe

### Les emails ne partent pas
- Vérifier les paramètres SMTP
- Vérifier que le mot de passe d'application Gmail est correct
- Vérifier les logs d'erreurs PHP

### Le paiement ne fonctionne pas
- Vérifier les clés API FedaPay/KkiaPay
- Vérifier le mode (sandbox/live)
- Consulter les logs dans la console du navigateur

### Erreur 500
- Vérifier les permissions des fichiers
- Consulter les logs Apache/PHP
- Vérifier la configuration PHP

## 📞 Support

Pour toute question ou problème :
- Email : contact@unionprogressiste.org
- Documentation FedaPay : https://docs.fedapay.com
- Documentation KkiaPay : https://docs.kkiapay.me

## 📄 Licence

© 2024 Union Progressiste le Renouveau - Tous droits réservés

---

**Développé avec ❤️ pour soutenir Vlavonou**
