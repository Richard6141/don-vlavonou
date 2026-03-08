# 📁 STRUCTURE DU PROJET - DON VLAVONOU

```
don-vlavonou/
│
├── 📄 README.md                 # Documentation complète
├── 📄 QUICKSTART.md             # Guide de démarrage rapide
├── 📄 .gitignore                # Fichiers à ignorer par Git
├── 📄 .htaccess                 # Configuration Apache (sécurité)
├── 🔧 install.sh                # Script d'installation automatique
│
├── 📂 public/                   # Dossier public (accessible via web)
│   └── 📄 index.html            # Page principale de don
│
├── 📂 backend/                  # Scripts PHP backend
│   ├── 📄 process_donation.php  # Traite les soumissions de don
│   ├── 📄 confirm_payment.php   # Confirme les paiements réussis
│   ├── 📄 payment_callback.php  # Reçoit les callbacks des plateformes
│   └── 📄 get_csrf_token.php    # Génère les tokens de sécurité
│
├── 📂 config/                   # Configuration
│   ├── 📄 config.php            # Configuration principale (À CRÉER)
│   ├── 📄 config.example.php    # Exemple de configuration
│   └── 📄 database.sql          # Script SQL de création de DB
│
└── 📂 assets/                   # Ressources statiques
    ├── 📂 css/
    │   └── 📄 style.css         # Styles personnalisés
    │
    ├── 📂 js/
    │   └── 📄 main.js           # JavaScript principal
    │
    └── 📂 images/               # Images (À AJOUTER)
        ├── 🖼️ logo.png           # Logo Union Progressiste
        ├── 🖼️ vlavonou.jpg       # Photo de Vlavonou
        ├── 🖼️ favicon.png        # Icône du site
        ├── 🖼️ fedapay-logo.png  # Logo FedaPay
        └── 🖼️ kkiapay-logo.png  # Logo KkiaPay
```

## 🗃️ BASE DE DONNÉES

### Tables créées automatiquement :

1. **donations** - Enregistre toutes les donations
   - id, transaction_id, first_name, last_name
   - email, phone, amount, payment_method
   - message, status, payment_reference
   - created_at, updated_at, ip_address, user_agent

2. **csrf_tokens** - Tokens de sécurité CSRF
   - id, token, ip_address
   - created_at, expires_at, used

3. **security_logs** - Logs de sécurité
   - id, event_type, ip_address
   - user_agent, details, created_at

4. **receipts** - Reçus envoyés
   - id, donation_id, email_sent
   - sent_at, email_status, created_at

### Vues :
- **donation_statistics** - Statistiques des donations

## 🔐 FICHIERS DE SÉCURITÉ

### Obligatoires :
✅ .htaccess - Protection Apache
✅ config.php - Configuration (ne PAS partager)
✅ Certificat SSL

### Protégés automatiquement :
🔒 Tous les fichiers .php en dehors de public/
🔒 config.php
🔒 database.sql
🔒 .env (si utilisé)

## 📦 DÉPENDANCES EXTERNES

### CDN utilisés (aucune installation requise) :
- TailwindCSS (via CDN)
- FedaPay SDK
- KkiaPay SDK

### Bibliothèques serveur :
- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx

## 🚀 DÉPLOIEMENT

### Développement Local :
1. XAMPP/WAMP/MAMP
2. Importer database.sql
3. Configurer config.php
4. Ouvrir http://localhost/don-vlavonou/public/

### Production :
1. Hébergeur (OVH, Hostinger, etc.)
2. Certificat SSL activé
3. Mode "live" activé
4. Clés API de production

## 📊 FLUX DE DONNÉES

```
Utilisateur
    ↓
[Formulaire HTML]
    ↓
[JavaScript validation]
    ↓
[Backend PHP] → Validation
    ↓
[Base de données MySQL] → Enregistrement
    ↓
[API FedaPay/KkiaPay] → Paiement
    ↓
[Callback PHP] → Confirmation
    ↓
[Email SMTP] → Reçu
    ↓
Utilisateur reçoit confirmation
```

## 🎨 PERSONNALISATION

### Modifier les couleurs :
- Éditer `assets/css/style.css`
- Variables CSS dans `:root`

### Modifier le texte :
- Éditer `public/index.html`
- Section "Message de Vlavonou"

### Modifier les montants prédéfinis :
- Éditer `public/index.html`
- Boutons `.amount-btn`

### Modifier l'email de reçu :
- Éditer `backend/confirm_payment.php`
- Fonction `generateReceiptHTML()`

## 📈 MONITORING

### Logs à surveiller :
- `/var/log/apache2/error.log` (Apache)
- `/var/log/php_errors.log` (PHP)
- Table `security_logs` (Application)

### Métriques importantes :
- Nombre de donations
- Montant total collecté
- Taux de succès des paiements
- Emails envoyés/échoués

## 🔄 MAINTENANCE

### Quotidienne :
- Vérifier les donations en attente
- Consulter les logs d'erreurs

### Hebdomadaire :
- Exporter les données
- Nettoyer les tokens expirés (auto)
- Vérifier le SSL

### Mensuelle :
- Mettre à jour PHP/MySQL
- Réviser les logs de sécurité
- Backup de la base de données

## 💾 BACKUP

```bash
# Backup base de données
mysqldump -u root -p don_vlavonou > backup_$(date +%Y%m%d).sql

# Backup fichiers
tar -czf backup_files_$(date +%Y%m%d).tar.gz don-vlavonou/
```

## 🆘 SUPPORT TECHNIQUE

### En cas de problème :
1. Consulter README.md
2. Consulter QUICKSTART.md
3. Vérifier les logs
4. Contacter le support

---

**Version :** 1.0.0  
**Dernière mise à jour :** Novembre 2024  
**Développé pour :** Vlavonou - Union Progressiste le Renouveau
