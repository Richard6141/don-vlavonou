# 🚀 GUIDE DE DÉMARRAGE RAPIDE

## ⚡ Installation en 5 Minutes

### Option 1 : Installation Automatique (Linux/Mac)

```bash
# 1. Copier le projet sur votre serveur
cd /var/www/html

# 2. Lancer le script d'installation
sudo ./install.sh

# 3. Suivre les instructions à l'écran
```

### Option 2 : Installation Manuelle (Windows/Linux)

#### Étape 1 : Base de données (2 min)
```bash
# Ouvrir phpMyAdmin ou MySQL
# Créer une base de données "don_vlavonou"
# Importer le fichier : config/database.sql
```

#### Étape 2 : Configuration (2 min)
```bash
# 1. Copier config/config.example.php vers config/config.php
# 2. Éditer config/config.php
# 3. Remplir :
#    - Identifiants MySQL
#    - Clés FedaPay (obtenir sur fedapay.com)
#    - Clés KkiaPay (obtenir sur kkiapay.me)
#    - Email Gmail + mot de passe d'application
#    - URL de votre site
```

#### Étape 3 : Images (1 min)
```
Ajouter dans assets/images/ :
✅ logo.png (logo Union Progressiste)
✅ vlavonou.jpg (photo de Vlavonou)
✅ favicon.png (icône du site)
✅ fedapay-logo.png
✅ kkiapay-logo.png
```

#### Étape 4 : Test
```
Ouvrir dans le navigateur : https://votre-domaine.com
Tester avec un petit montant
```

## 📝 Checklist Avant Lancement

- [ ] Base de données créée et importée
- [ ] Fichier config.php configuré avec les bonnes clés
- [ ] Images ajoutées dans assets/images/
- [ ] SSL/HTTPS activé
- [ ] Test de paiement réussi (mode sandbox)
- [ ] Email de reçu reçu avec succès
- [ ] Vérification des donations dans la base de données
- [ ] Passage en mode "live" pour la production

## 🔑 Obtenir les Clés API

### FedaPay
1. Aller sur https://fedapay.com
2. Créer un compte
3. Dashboard > API > Copier les clés
4. Pour tester : utiliser les clés "sandbox"
5. Pour production : utiliser les clés "live"

### KkiaPay
1. Aller sur https://kkiapay.me
2. Créer un compte
3. Dashboard > API > Copier les clés
4. Pour tester : activer le mode sandbox
5. Pour production : désactiver le mode sandbox

### Gmail (pour les emails)
1. Aller sur https://myaccount.google.com/apppasswords
2. Sélectionner "Mail" et "Autre"
3. Générer et copier le mot de passe

## 🧪 Test en Mode Sandbox

1. Dans `config/config.php` :
```php
define('FEDAPAY_MODE', 'sandbox');
define('KKIAPAY_MODE', 'sandbox');
```

2. Utiliser les clés de test
3. Tester le paiement
4. Vérifier l'email de reçu

## 🚀 Mise en Production

1. Obtenir les clés "live" de FedaPay et KkiaPay
2. Dans `config/config.php` :
```php
define('FEDAPAY_MODE', 'live');
define('KKIAPAY_MODE', 'live');
```
3. Mettre les vraies clés API
4. Activer le SSL/HTTPS
5. Tester avec un petit montant réel

## 📊 Voir les Donations

### Via MySQL
```sql
mysql -u root -p don_vlavonou

SELECT * FROM donations ORDER BY created_at DESC LIMIT 10;
```

### Via phpMyAdmin
1. Ouvrir phpMyAdmin
2. Sélectionner la base "don_vlavonou"
3. Table "donations"

## ⚠️ Problèmes Courants

### "Erreur de connexion à la base de données"
➡️ Vérifier les identifiants dans config/config.php

### "Les emails ne partent pas"
➡️ Vérifier le mot de passe d'application Gmail
➡️ Vérifier que SMTP est activé

### "Le paiement ne fonctionne pas"
➡️ Vérifier les clés API
➡️ Vérifier le mode (sandbox/live)
➡️ Consulter la console du navigateur (F12)

### "Page blanche"
➡️ Vérifier les logs PHP
➡️ Activer l'affichage des erreurs temporairement

## 📞 Support

- Email : contact@unionprogressiste.org
- Documentation complète : README.md

## 🎉 C'est Tout !

Votre plateforme de don est prête à collecter des fonds pour Vlavonou !

**Bonne chance ! 🌳**
