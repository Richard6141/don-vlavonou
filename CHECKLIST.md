# ✅ CHECKLIST D'INSTALLATION ET DE DÉPLOIEMENT

## 📦 PHASE 1 : PRÉPARATION (Avant installation)

### Comptes et Accès
- [ ] Compte d'hébergement web prêt
- [ ] Accès FTP/SSH configuré
- [ ] Accès phpMyAdmin ou MySQL
- [ ] Nom de domaine enregistré
- [ ] DNS configuré vers l'hébergeur

### Comptes API
- [ ] Compte FedaPay créé (https://fedapay.com)
- [ ] Clés API FedaPay obtenues (publique + secrète)
- [ ] Compte KkiaPay créé (https://kkiapay.me)
- [ ] Clés API KkiaPay obtenues (publique + privée + secret)
- [ ] Compte Gmail configuré
- [ ] Validation en 2 étapes Gmail activée
- [ ] Mot de passe d'application Gmail généré

### Images Préparées
- [ ] logo.png (Logo Union Progressiste)
- [ ] vlavonou.jpg (Photo de Vlavonou, format portrait)
- [ ] favicon.png (32x32 ou 64x64 pixels)
- [ ] fedapay-logo.png (optionnel, 200x80px recommandé)
- [ ] kkiapay-logo.png (optionnel, 200x80px recommandé)

---

## 🚀 PHASE 2 : INSTALLATION (15-30 minutes)

### Transfert des Fichiers
- [ ] Télécharger don-vlavonou.tar.gz
- [ ] Extraire l'archive
- [ ] Copier tous les fichiers vers le serveur web
- [ ] Vérifier que la structure est intacte

### Base de Données
- [ ] Se connecter à phpMyAdmin
- [ ] Créer la base de données "don_vlavonou"
- [ ] Charset: utf8mb4, Collation: utf8mb4_unicode_ci
- [ ] Importer config/database.sql
- [ ] Vérifier que les 4 tables sont créées
- [ ] Vérifier que la vue donation_statistics existe

### Configuration
- [ ] Copier config/config.example.php vers config/config.php
- [ ] Éditer config/config.php
- [ ] Remplir DB_HOST (généralement 'localhost')
- [ ] Remplir DB_NAME ('don_vlavonou')
- [ ] Remplir DB_USER (votre utilisateur MySQL)
- [ ] Remplir DB_PASS (votre mot de passe MySQL)
- [ ] Remplir FEDAPAY_PUBLIC_KEY
- [ ] Remplir FEDAPAY_SECRET_KEY
- [ ] Définir FEDAPAY_MODE ('sandbox' pour test, 'live' pour production)
- [ ] Remplir KKIAPAY_PUBLIC_KEY
- [ ] Remplir KKIAPAY_PRIVATE_KEY
- [ ] Remplir KKIAPAY_SECRET
- [ ] Définir KKIAPAY_MODE ('sandbox' pour test, 'live' pour production)
- [ ] Remplir SMTP_USER (votre email Gmail)
- [ ] Remplir SMTP_PASS (mot de passe d'application Gmail)
- [ ] Remplir SITE_URL (https://votre-domaine.com)

### Images
- [ ] Créer le dossier assets/images/ (s'il n'existe pas)
- [ ] Uploader logo.png
- [ ] Uploader vlavonou.jpg
- [ ] Uploader favicon.png
- [ ] Uploader fedapay-logo.png (optionnel)
- [ ] Uploader kkiapay-logo.png (optionnel)
- [ ] Vérifier les permissions (644 pour les images)

### Permissions
- [ ] chmod 755 public/
- [ ] chmod 755 backend/
- [ ] chmod 755 assets/
- [ ] chmod 644 config/config.php
- [ ] chmod 600 config/database.sql
- [ ] chmod 644 .htaccess

---

## 🔒 PHASE 3 : SÉCURITÉ (10-20 minutes)

### Certificat SSL
- [ ] Certificat SSL installé
- [ ] HTTPS forcé (.htaccess configure cela)
- [ ] Tester https://votre-domaine.com
- [ ] Vérifier le cadenas dans le navigateur
- [ ] Tester la redirection HTTP → HTTPS

### Fichiers Protégés
- [ ] Vérifier que config/config.php n'est pas accessible via web
- [ ] Vérifier que config/database.sql n'est pas accessible
- [ ] Tester l'accès à .htaccess (doit être bloqué)
- [ ] Vérifier que le dossier backend/ n'est pas listable

### Headers de Sécurité
- [ ] Vérifier X-Frame-Options (https://securityheaders.com)
- [ ] Vérifier X-XSS-Protection
- [ ] Vérifier X-Content-Type-Options
- [ ] Vérifier Content-Security-Policy

---

## 🧪 PHASE 4 : TESTS (20-30 minutes)

### Tests Mode Sandbox
- [ ] Ouvrir la page de don dans le navigateur
- [ ] Vérifier que la page s'affiche correctement
- [ ] Vérifier que les images s'affichent
- [ ] Vérifier le responsive (mobile, tablette)
- [ ] Sélectionner un montant prédéfini
- [ ] Remplir le formulaire avec des données de test
- [ ] Sélectionner FedaPay comme mode de paiement
- [ ] Soumettre le formulaire
- [ ] Compléter le paiement test FedaPay
- [ ] Vérifier la redirection de retour
- [ ] Vérifier la réception de l'email de reçu
- [ ] Répéter avec KkiaPay
- [ ] Vérifier les donations dans la base de données

### Tests Base de Données
- [ ] Se connecter à MySQL/phpMyAdmin
- [ ] Vérifier table donations (doit contenir vos tests)
- [ ] Vérifier table csrf_tokens (doit se remplir)
- [ ] Vérifier table receipts (confirmation envoi email)
- [ ] Vérifier table security_logs (logs d'événements)
- [ ] Tester la vue donation_statistics

### Tests Sécurité
- [ ] Tester soumission sans CSRF token (doit échouer)
- [ ] Tester montant < 500 FCFA (doit échouer)
- [ ] Tester email invalide (doit échouer)
- [ ] Tester plus de 5 soumissions en 5 minutes (rate limit)
- [ ] Vérifier les logs de sécurité

### Tests Email
- [ ] Vérifier format de l'email reçu
- [ ] Vérifier que toutes les informations sont présentes
- [ ] Vérifier le design HTML de l'email
- [ ] Tester avec différentes adresses email

---

## 🎯 PHASE 5 : MISE EN PRODUCTION (15-20 minutes)

### Passage en Mode Live
- [ ] Obtenir les clés LIVE de FedaPay
- [ ] Obtenir les clés LIVE de KkiaPay
- [ ] Modifier config/config.php
- [ ] Changer FEDAPAY_MODE de 'sandbox' à 'live'
- [ ] Changer KKIAPAY_MODE de 'sandbox' à 'live'
- [ ] Mettre les clés LIVE FedaPay
- [ ] Mettre les clés LIVE KkiaPay
- [ ] Sauvegarder config/config.php

### Test de Production
- [ ] Effectuer un don réel avec un PETIT montant (ex: 500 FCFA)
- [ ] Vérifier que le paiement est bien traité
- [ ] Vérifier la réception du reçu
- [ ] Vérifier l'enregistrement en base de données
- [ ] Confirmer la réception du paiement sur FedaPay/KkiaPay

### Nettoyage
- [ ] Supprimer les donations de test de la base
- [ ] Nettoyer les logs de test
- [ ] Vider table csrf_tokens si nécessaire
- [ ] Supprimer install.sh du serveur (sécurité)
- [ ] Supprimer config.example.php du serveur (optionnel)

---

## 📊 PHASE 6 : MONITORING (Configuration continue)

### Configuration Monitoring
- [ ] Configurer une alerte email pour les erreurs PHP
- [ ] Mettre en place un système de backup automatique
- [ ] Configurer la sauvegarde quotidienne de la BDD
- [ ] Documenter la procédure de backup/restore

### Maintenance Préventive
- [ ] Planifier vérification hebdomadaire des logs
- [ ] Planifier export mensuel des donations
- [ ] Planifier mise à jour semestrielle PHP/MySQL
- [ ] Documenter les identifiants importants (coffre-fort)

### Statistiques
- [ ] Définir les KPI à suivre (montant total, nb donations)
- [ ] Configurer un tableau de bord (optionnel)
- [ ] Planifier rapports mensuels

---

## 📱 PHASE 7 : COMMUNICATION (Avant lancement)

### Préparation
- [ ] Préparer l'annonce du lancement
- [ ] Préparer les visuels pour réseaux sociaux
- [ ] Préparer le message d'invitation à donner
- [ ] Tester le partage de la page sur réseaux sociaux

### Lancement
- [ ] Annoncer sur les réseaux sociaux
- [ ] Envoyer aux membres et sympathisants
- [ ] Partager le lien de don largement

---

## ✅ VALIDATION FINALE

### Checklist Globale
- [ ] ✅ Site accessible via HTTPS
- [ ] ✅ Formulaire de don fonctionnel
- [ ] ✅ Paiements FedaPay opérationnels
- [ ] ✅ Paiements KkiaPay opérationnels
- [ ] ✅ Emails de reçu envoyés automatiquement
- [ ] ✅ Base de données enregistre correctement
- [ ] ✅ Sécurité en place (CSRF, SSL, etc.)
- [ ] ✅ Mode LIVE activé
- [ ] ✅ Images bien affichées
- [ ] ✅ Responsive sur mobile
- [ ] ✅ Backups configurés
- [ ] ✅ Monitoring en place

---

## 🆘 EN CAS DE PROBLÈME

### Problèmes Courants
1. **Page blanche**
   - Vérifier les logs PHP
   - Activer display_errors temporairement
   - Vérifier les permissions

2. **Erreur base de données**
   - Vérifier identifiants dans config.php
   - Vérifier que MySQL est démarré
   - Vérifier que la base existe

3. **Paiement ne fonctionne pas**
   - Vérifier les clés API
   - Vérifier le mode (sandbox/live)
   - Consulter la console navigateur (F12)

4. **Email non reçu**
   - Vérifier dossier spam
   - Vérifier config SMTP
   - Consulter les logs PHP

### Support
- 📧 Email: contact@unionprogressiste.org
- 📚 Documentation: README.md
- 🔗 FedaPay Support: https://support.fedapay.com
- 🔗 KkiaPay Support: https://support.kkiapay.me

---

## 🎉 FÉLICITATIONS !

Si toutes les cases sont cochées, votre plateforme est prête !

**La collecte de fonds pour Vlavonou peut commencer ! 🌳**

---

Date d'installation : _______________
Installé par : _______________
Validé par : _______________
