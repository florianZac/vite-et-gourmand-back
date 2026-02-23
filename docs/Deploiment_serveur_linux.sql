-- 1.0 creation de la base de données
CREATE DATABASE IF NOT EXISTS vite_et_gourmand CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 1.1 création de l'utilisateur.
CREATE USER 'symfony_user'@'localhost' IDENTIFIED BY '123456';
-- 1.2 attribution des privilèges à l'utilisateur.
GRANT ALL PRIVILEGES ON vite_et_gourmand.* TO 'symfony_user'@'localhost';
-- 1.3 rechargement des privilèges pour que les modifications prennent effet immédiatement.
FLUSH PRIVILEGES;

-- 2.0 configuration du serveur web pour le projet Symfony

<VirtualHost *:80>
  ServerName vite-et-gourmand.local
  DocumentRoot "D:/wamp64/www/vite-et-gourmand-back/public"
  <Directory "D:/wamp64/www/vite-et-gourmand-back/public">
    Options +Indexes +FollowSymLinks
    AllowOverride All
    Require all granted
  </Directory>
  ErrorLog "D:/wamp64/logs/vite-et-gourmand-error.log"
  CustomLog "D:/wamp64/logs/vite-et-gourmand-access.log" combined
</VirtualHost>

# Le `.env` doit utiliser `symfony_user`
DATABASE_URL="mysql://symfony_user:123456@localhost:3306/vite_et_gourmand?serverVersion=8.4.7&charset=utf8mb4"

-- 3.0 activation du site et rechargement du serveur web

-- 3.1 activation du virtual host sur linux
sudo a2ensite symfony

-- 3.2 redémmarage du serveur Apache sur linux
sudo systemctl reload apache2

-- 4.0 configuration des permissions pour le projet Symfony

-- 4.1 Changement de propriétaire et de groupe de façon récursive 
--     pour les dossiers de cache et de log de Symfony (www-data:www-data)

sudo chown -R www-data:www-data /var/www/symfony

-- 4.2 Changement des permissions pour les dossiers de cache et de log de Symfony
-- pour que le propriétaire et le groupe aient l'ensemble des permissions de lecture, d'écriture et d'exécution (775)
-- rappel personel chmod propriétaire groupe autres
--  0   --- 000 --- aucune permission
--  1   --- 001 --- permission d'exécution
--  2   --- 010 --- permission d'écriture
--  3   --- 011 --- permission d'écriture et d'exécution
--  4   --- 100 --- permission de lecture
--  5   --- 101 --- permission de lecture et d'exécution
--  6   --- 110 --- permission de lecture et d'écriture
--  7   --- 111 --- permission de lecture, d'écriture et d'exécution

sudo chmod -R 775 /var/www/symfony/var/cache /var/www/symfony/var/log
