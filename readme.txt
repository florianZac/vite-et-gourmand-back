Aide mémoire est d'Installation Back-end

1. Installation et mise en place de Symfony



1.1 vérification des versions php,composeur etc ..
php -v  -> vérification de la version de php
composer -v  -> vérification de la version de composer
symfony -v  -> vérification de la version du Command Line Interface (CLI)

1.1.1 Vérification des requirement syfony avant installation 
symfony check:requirements


1.1 deplacement et création du dossier projet 

j'utilise actuellement le logiciel Wampserveur : cd D:\wamp64\www
déplacement dans le dossier concerné :  cd D:\wamp64\www  ensuite pwd on vérifie ou ont est ou ls 

1.1.1 Cas Utilisation site monolite (mono bloc pas de front et back )
    création du dossier : symfony new vite-et-gourmand-back --version="lts" --webapp
    car il installe Twig, les formulaires Symfony, le moteur de templates..

1.1.2 Cas Utilisation séparation back et front.
    Dans mon cas je veut séparer le front et le back du coup la commande est la suivante:
car mon serveur ne vas que générer du json 
symfony new vite-et-gourmand-back --version="lts" --api

cas personnel : 

1.2 Installation de symfony
symfony new vite-et-gourmand-back --version="lts" --api

1.3 déplacement dans le dossier
cd vite-et-gourmand-back

1.4 lancement du serveur  |   cloture du serveur
symfony server:start      |   symfony server:stop
si on souhaite le lancer à travers la console du dossier bin (attention à la version php)
php bin/console server:run

Au cas ou Installation de un certificat de sécurité pas nécessaire pour l'instant: symfony server:ca:install 

2. Configuration de la base de donnée

Ou : dans le fichier .env du projet

2.1 
de base DATABASE_URL="postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=16&charset=utf8"

mon cas d'utilisation :
 
sur Wampserver il utilise MySQL
l'utilisateur par défaut actuel à modifier plus tard : florian
mot de passe vide sinon je vais pas m'en rappeler est sa vas me souler : 123456
port par défaut de Wampserveur :3306 
Nom de la base de donnée crée : vite_et_gourmand
version de SQL dans Wampserver : 8.0
mode d'encodage européen utf4 :  charset=utf8mb4
donc :
DATABASE_URL="mysql://florian:123456@localhost:3306/vite_et_gourmand?serverVersion=8.4.7&charset=utf8mb4"

3. création de la base de donnée vite & gourmand
3.1 création de la base de donnée avec DOCTRINE
voir database.sql, create_delete_database et constraints.sql pour voir la création manuel

3.1.1 Installation de MakerBundle 
composer require symfony/maker-bundle --dev

3.2 création des entity (les tables) 
il crée deux fichiers src/Entity/name_entity.php la classe entité et src/repository/name_entityRepositoy.php les requetes BDD

3.3 création des entity symphony (des tables sql)

php bin/console make:entity Role
php bin/console make:entity Avis
php bin/console make:entity Utilisateur
php bin/console make:entity Regime
php bin/console make:entity Theme
php bin/console make:entity Allergene
php bin/console make:entity Horaire
php bin/console make:entity Plat
php bin/console make:entity Menu
php bin/console make:entity Commande

3.3 création (des clés étrangères sql)

php bin/console make:entity Utilisateur
exemple de configuration :
New property name
 > role
 Field type:
 > ManyToOne
 related to:
 > Role
 Is the Utilisateur.role property allowed to be null (nullable)?:
 > no
 Do you want to add a new property to Role so that you can access/updat:
 > no

php bin/console make:entity Avis
php bin/console make:entity Menu
php bin/console make:entity Plat
php bin/console make:entity Commande
etc..
penser à bien vérifier la concordance des entity générer en fonction du MCD avant de passer à la suite

3.4 Création de la base de donnée en automatique
php bin/console doctrine:database:create

3.5 génération de la migration (création de la base de donnée)
php bin/console make:migration

3.6 pousser pour génèrer la bas de donnée
php bin/console doctrine:migrations:migrate

4 Installation du composant sécurity pour gérer les rôles les connexions et la protection des routes.

4.1 Installation du bundle JWT pour la gestion des tokens
composer.bat require lexik/jwt-authentication-bundle

4.2 génèration des clefs de chiffrement
php bin/console lexik:jwt:generate-keypair
-> génération de deux fichiers dans le dossier config/jwt
    private.pem qui est la clé privée pour signer les tokens
    public.pem qui est la clé publique afin de vérifier les tokens

4.3 modification du fichier .env
avec 
// variable Symfony qui pointe vers le fichier private.pem ou est présente la clefs privée
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
// variable Symfony qui pointe vers le fichier private.pem ou est présente la clefs publique
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
//  le mot de passe qui protège la clé privée
JWT_PASSPHRASE=vite_et_gourmand_secret

4.4 génération des clefs JWT
 php bin/console lexik:jwt:generate-keypair --overwrite

4.5 creation des fonctions handler dans src/Security
LoginFailureHandler
LoginSuccessHandler


Test des routes et tokens

4.6 Installation de doctrine bundle pour inserer les données dans la base
composer.bat require doctrine/doctrine-bundle

4.7 creation de script sql pour l'instantiation des données dans un users et la table role. 
création_utilisateur.sql


4.8 création de la route login dans un controleur
php bin/console make:controller AuthController

4.9 Lancement du handler pour test 
php bin/console server:start
avec cli symfony server:start

5 Vidé le cache symfony
php bin/console cache:clear

5.1 création du document de testpostman 
Test_API_postman


