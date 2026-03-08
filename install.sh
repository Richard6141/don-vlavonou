#!/bin/bash

##############################################
# Script d'installation - Plateforme Don Vlavonou
##############################################

echo "=========================================="
echo "  Installation - Plateforme Don Vlavonou"
echo "=========================================="
echo ""

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Vérifier si le script est exécuté en tant que root
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}Ce script doit être exécuté en tant que root${NC}"
    echo "Utilisez: sudo ./install.sh"
    exit 1
fi

echo -e "${GREEN}[1/8] Vérification des prérequis...${NC}"

# Vérifier Apache
if ! command -v apache2 &> /dev/null; then
    echo -e "${YELLOW}Apache non trouvé. Installation...${NC}"
    apt-get update
    apt-get install -y apache2
fi

# Vérifier PHP
if ! command -v php &> /dev/null; then
    echo -e "${YELLOW}PHP non trouvé. Installation...${NC}"
    apt-get install -y php php-mysql php-curl php-json php-mbstring
fi

# Vérifier MySQL
if ! command -v mysql &> /dev/null; then
    echo -e "${YELLOW}MySQL non trouvé. Installation...${NC}"
    apt-get install -y mysql-server
fi

echo -e "${GREEN}[2/8] Configuration de la base de données...${NC}"

# Demander les informations MySQL
read -p "Nom d'utilisateur MySQL (défaut: root): " DB_USER
DB_USER=${DB_USER:-root}

read -sp "Mot de passe MySQL: " DB_PASS
echo ""

read -p "Nom de la base de données (défaut: don_vlavonou): " DB_NAME
DB_NAME=${DB_NAME:-don_vlavonou}

# Créer la base de données
mysql -u "$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}Base de données créée avec succès${NC}"
else
    echo -e "${RED}Erreur lors de la création de la base de données${NC}"
    exit 1
fi

# Importer le schéma
mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < config/database.sql

if [ $? -eq 0 ]; then
    echo -e "${GREEN}Schéma de base de données importé avec succès${NC}"
else
    echo -e "${RED}Erreur lors de l'importation du schéma${NC}"
    exit 1
fi

echo -e "${GREEN}[3/8] Configuration des clés API...${NC}"

# FedaPay
read -p "Clé publique FedaPay: " FEDAPAY_PUBLIC
read -p "Clé secrète FedaPay: " FEDAPAY_SECRET
read -p "Mode FedaPay (sandbox/live, défaut: sandbox): " FEDAPAY_MODE
FEDAPAY_MODE=${FEDAPAY_MODE:-sandbox}

# KkiaPay
read -p "Clé publique KkiaPay: " KKIAPAY_PUBLIC
read -p "Clé privée KkiaPay: " KKIAPAY_PRIVATE
read -p "Secret KkiaPay: " KKIAPAY_SECRET
read -p "Mode KkiaPay (sandbox/live, défaut: sandbox): " KKIAPAY_MODE
KKIAPAY_MODE=${KKIAPAY_MODE:-sandbox}

echo -e "${GREEN}[4/8] Configuration de l'email...${NC}"

read -p "Email SMTP (Gmail): " SMTP_USER
read -sp "Mot de passe d'application Gmail: " SMTP_PASS
echo ""

echo -e "${GREEN}[5/8] Configuration du site...${NC}"

read -p "URL du site (ex: https://don-vlavonou.com): " SITE_URL

echo -e "${GREEN}[6/8] Création du fichier de configuration...${NC}"

# Créer le fichier config.php depuis l'exemple
cp config/config.example.php config/config.php

# Remplacer les valeurs
sed -i "s/'root'/'$DB_USER'/g" config/config.php
sed -i "s/define('DB_PASS', '');/define('DB_PASS', '$DB_PASS');/g" config/config.php
sed -i "s/'don_vlavonou'/'$DB_NAME'/g" config/config.php
sed -i "s/pk_sandbox_XXXXXXXXXXXXX/$FEDAPAY_PUBLIC/g" config/config.php
sed -i "s/sk_sandbox_XXXXXXXXXXXXX/$FEDAPAY_SECRET/g" config/config.php
sed -i "s/define('FEDAPAY_MODE', 'sandbox');/define('FEDAPAY_MODE', '$FEDAPAY_MODE');/g" config/config.php
sed -i "s/XXXXXXXXXXXXX/$KKIAPAY_PUBLIC/g" config/config.php
sed -i "s/XXXXXXXXXXXXX/$KKIAPAY_PRIVATE/g" config/config.php
sed -i "s/XXXXXXXXXXXXX/$KKIAPAY_SECRET/g" config/config.php
sed -i "s/define('KKIAPAY_MODE', 'sandbox');/define('KKIAPAY_MODE', '$KKIAPAY_MODE');/g" config/config.php
sed -i "s/votre-email@gmail.com/$SMTP_USER/g" config/config.php
sed -i "s/votre-mot-de-passe-app/$SMTP_PASS/g" config/config.php
sed -i "s|https://votre-domaine.com|$SITE_URL|g" config/config.php

echo -e "${GREEN}[7/8] Configuration des permissions...${NC}"

# Définir les permissions
chmod 755 public/
chmod 755 backend/
chmod 644 config/config.php
chmod 600 config/database.sql
chown -R www-data:www-data .

echo -e "${GREEN}[8/8] Installation de Certbot pour SSL (optionnel)...${NC}"

read -p "Voulez-vous installer Certbot pour SSL? (y/n): " INSTALL_SSL

if [ "$INSTALL_SSL" = "y" ] || [ "$INSTALL_SSL" = "Y" ]; then
    apt-get install -y certbot python3-certbot-apache
    
    read -p "Nom de domaine pour le certificat SSL: " DOMAIN
    certbot --apache -d "$DOMAIN"
fi

echo ""
echo -e "${GREEN}=========================================="
echo "  Installation terminée avec succès!"
echo "==========================================${NC}"
echo ""
echo -e "${YELLOW}Prochaines étapes :${NC}"
echo "1. Ajoutez vos images dans assets/images/"
echo "2. Testez le site dans votre navigateur"
echo "3. Vérifiez les logs en cas d'erreur"
echo ""
echo -e "${GREEN}URL du site : $SITE_URL${NC}"
echo ""
echo -e "${YELLOW}Pour voir les donations :${NC}"
echo "mysql -u $DB_USER -p$DB_PASS $DB_NAME -e 'SELECT * FROM donations;'"
echo ""
echo "Merci d'utiliser la Plateforme Don Vlavonou !"
echo ""
