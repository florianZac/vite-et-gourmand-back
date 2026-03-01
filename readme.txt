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

en dev seulement :
# Met à jour directement le schéma (sans fichier de migration)
php bin/console doctrine:schema:update --force

3.6 Execution de la migration pour génèrer la bas de donnée
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
symfony server:start

Sans Symfony CLI :
php -S localhost:8000 -t public/

5 Vidé le cache symfony
php bin/console cache:clear

5.1 création du document de testpostman 
Test_API_postman

5.2 Choix de la solution mail
Choix mailtrap 
pourquoi : 
    facile à installer sur symphony et configurer
    Interface web pour voir les mails envoyées
    fonctionne sans serveur mail
    gratuit
    peu de temps disponible 

5.2.1 Installation de Mailtrap le composant officiel de Symfony pour envoyer des emails
composer require symfony/mailer

5.3 Installation de twig pour la création de template HTML pour les mails uniquement
cela permet d'avoir un vrai fichier HTML dédié à chaque email
composer require twig

5.3 Création d'un template d'email
dans le dossier templates/emails/contact.html.twig

5.4 création d'un compte mailtrap
https://mailtrap.io/inboxes/4404595/messages/5352687463/html
et récupération du MAILER_DSN
mailtrap -> sandbox -> intégration -> symphony -> MAILER_DSN="smtp://6836c3cc28f364:****c337@sandbox.smtp.mailtrap.io:2525"
ne pas oublié de régénerer les crédentiale il faut pas que le mdp soit masquer sa ma souler j'ai perdu 1 heure de débug.

5.5 creation des fonctions sanitazier, validation reggex et rate limitation dans le controleur mail ContactController.php

5.1 installation rate limiting 
composer require symfony/rate-limiter
composer require symfony/lock
php bin/console cache:clear

mise en place des différentes sécutité 
Protection n°1 : Rate limiting
permet de proteger un attaquant qui envoie des millies de requêtes par seconde pour surcharger le serveur ou spammer la boîte mail
Solution proposer identifier l'adresse IP de l'utilisateur et lui accorde que 5 requêtes par heure.

Protection n°2 : Content-Type
Un bot peut envoyer des données dans le formulaire de type XML, formulaire HTML, fichier binaire pour provoquer des erreurs.
Solution : vérifier le type des données de toutes les inputs ou textarea et ne traiter que les requetes ayant le bon format.

Protection n°3 : Taille du body
Un attaquant peut envoyer une requetes de plusssieurs mégaoctets pour saturer la mémoire du serveur.
Solution : on limite la 10ko avant la gestion json

Protection n°4 : Honeypot à vérifier je suis pas sur de mon coup .
Les bots automatiques remplissent tous les champs d'un formulaire sans réfléchir pour surcharger le serveur
Solution :Si ce champ est rempli c'est forcément un bot car un humain ne verra pas ce champs.

Protection n°5 : sanitazier 
un attaquant peut cree du code malvaillant avec des espaces du html ou php ou js ou des characteres speciaux
Solution :
trim -> supprime les espaces inutile
strip_tags -> supprime les balise html, js et ,php
htmlspecialchars -> évite les attaques XSS de script malvaillant

Protection n°6 : Injection SQL
Un attaquant peut tenter d'injecter des commandes SQL dans les champs texte pour manipuler la base de données
Solution :
preg_replace('/(\bunion\b|\bselect\b|\binsert\b|\bdelete\b|\bdrop\b|\bupdate\b)/i')
cette reggex permet de supprimer tout les mots clefs dangereux SQL.

5.6 Apprendre à utiliser l'injection de dépendance
taper dans le terminal  php bin/console debug:autowiring --all
https://symfony.com/doc/current/reference/forms/types/entity.html
https://symfony.com/doc/current/doctrine.html#fetching-objects-from-the-database


5.7 vérification des routes après modification de UtilisateurController.php -> AdminController.php
pour respecter la regle un controleur un niveau responsabilité unique que je respecter pas
Commande pérmettant de vérifier que Symfony voit bien les nouvelles routes :
php bin/console debug:router | Select-String "admin"
Commande pérmettant de vérifier toutes les routes du projet :
php bin/console debug:router | Select-String "api"

5.8 Installation des commandes  syfony pour utilisation de cron 

Objectif : chaque jour, vérifier les commandes (pret_materiel=1, restitution_materiel=0) dont le statut est Livré

5.9 Création du fichier de commande
php bin/console make:command        
Choose a command name ->CheckRetourMaterielCommand
Crée automatiquement la commande -> src/Command/CheckRetourMaterielCommand.php

5.9 Création de la command Symfony
php bin/console app:check-retour-materiel

6 Vérification de la commande 
php bin/console list

6.1 Utilisation de la command crée dans le terminal 
php bin/console app:check-retour-materiel

6.2 verification des propriétées et des methodes via symfony apres modification d'une entity
6.2.1 : php bin/console doctrine:schema:update --force

6.2.2 : Valide le mapping Doctrine 
php bin/console doctrine:schema:validate

6.2.3 : Génère le SQL théorique et inspecte les FK
php bin/console doctrine:schema:create --dump-sql

6.2.4 : Vérifie que ta BDD est synchronisée
php bin/console doctrine:migrations:diff
# → Si "No changes detected" c'est parfait

6.3.5 : Apres création du cron et de la doctrine tester la commande dans le terminal :
 Tester MANUELLEMENT check-retour-materiel
-> php bin/console app:check-retour-materiel

6.3.6 Voir la version de doctrine-bundle 
composer show doctrine/doctrine-bundle | grep versions


6.4 Installation est mise en place de MongoDB sur un projet symfony

Quelle interet ? : Je vais utiliser MongoDB pour gérer les logs d'activité de la sociétée
Pourquoi ? :Car se sont des données volumineuse qui n'on pas de schéma fixe il non pas de relation entre eux ce qui est le cas d'usage optimale de NoSQL.

6.4.1 Vérification de MongoDB avant d'installation 
composer show | findstr mongodb sur windows
composer show | grep mongodb sur linux

6.4.2 Installation de MongoDB
composer require doctrine/mongodb-odm-bundle

6.4.2 Vérification de la version mongodb
mongod --version n'exite pas okay 

6.4.2.1 
php -m dans visual code la reponse contient MongoDB oui ou non si non continue 

6.4.2.2 ouvrir le bon dossier PHP pour savoir lequel est installer php-v
réponse -> PHP 8.4.15
du coup le bon dossier -> D:\wamp64\bin\php\php8.4.15\ext\
Lance cette commande :
if (Test-Path "D:\wamp64\bin\php\php8.4.15\ext\php_mongodb.dll") { echo "php_mongodb.dll EST PRESENT" } else { echo "php_mongodb.dll N'EST PAS PRESENT" }

Bon pas de dll disponible passage en docker 
6.4.5 docker --version
pas de version de docker T_T 

6.4.6 Installation de docker
https://www.docker.com/products/docker-desktop/
https://docs.docker.com/desktop/setup/install/windows-install/

6.5 Fermer tous les processus Docker existants
# Liste tous les processus Docker
Get-Process *docker* | Select-Object Id, ProcessName

# Termine tous les processus Docker bloqués
Get-Process *docker* | Stop-Process -Force

6.6 Redémarrer les services Docker
# Arrêter le service Docker Desktop
Stop-Service com.docker.service -Force

# Démarrer le service Docker Desktop
Start-Service com.docker.service

6.7 Supprimer les anciens containers ou images corrompus
docker container prune -f -> supprime tous les containers arrêtés
docker image prune -a -f -> supprime toutes les images inutilisées

6.8 ENFIN on part sur quelque chose de property  (POWERSHELL avec droit admin )
Lancer MongoDB via docker 
mkdir D:\DockerData\MongoDB
docker run -d --name mongodb -p 27017:27017 -v D:\DockerData\MongoDB:/data/db mongo:6
Explications :
-d -> mode détaché (arrière-plan)
--name mongodb -> nom du container
-p 27017:27017 -> accessible depuis ton PC sur localhost
-v D:\DockerData\MongoDB:/data/db -> MongoDB stocke les données sur D:, pas dans Docker interne
mongo:6 -> version officielle MongoDB 6

Vérification que MongoDB tourne 
PS C:\WINDOWS\system32> docker ps

résultat : sa tourne bien  Ports -> 0.0.0.0:27017->27017/tcp 
CONTAINER ID   IMAGE     COMMAND                  CREATED         STATUS              PORTS                                             NAMES
dab15d72eb7e   mongo:6   "docker-entrypoint.s…"   2 minutes ago   Up About a minute   0.0.0.0:27017->27017/tcp, [::]:27017->27017/tcp   mongodb
PS C:\WINDOWS\system32>

Faire la connection entre MongoDB <-> Docker
$client = new MongoDB\Client("mongodb://127.0.0.1:27017");

$client -> tu crées un objet client MongoDB.
new MongoDB\Client(...) -> tu dis à PHP : « Je veux me connecter à MongoDB »
"mongodb://127.0.0.1:27017"-> c’est l’adresse du serveur MongoDB :
127.0.0.1-> ton PC local (localhost)
27017->le port où MongoDB écoute


Créer un projet test pour MongoDB
    cd D:\wamp64\www\vite-et-gourmand-back
    mkdir test_mongo
    cd test_mongo

Créer un composer.json minimal
    composer install --ignore-platform-req=ext-mongodb

Supprimer l’ancien container MongoDB
    docker rm -f mongodb

création :

docker run -d --name vite_et_gourmand_logs -p 27017:27017 -v D:\docker-data\vite_et_gourmand_logs:/data/db mongo:6

Installer la librairie PHP (option test) 
composer require mongodb/mongodb --ignore-platform-req=ext-mongodb
    Le --ignore-platform-req=ext-mongodb permet d’installer les fichiers PHP même si ext-mongodb est absent

php --ini -> affiche ou se lance ton php 
résultat : D:\wamp64\bin\php\php8.4.15\php.ini

php -i | findstr "Thread"
résulat :
    Thread Safety => enabled
    Thread API => Windows Threads

Du coup il me faut -> PHP 8.4 / Thread Safe / x64
https://pecl.php.net/package/mongodb
Clique la dernière version de la DLL :  à côté de la version 2.2.1 est choisie 8.4 Thread Safe (TS) x64 
On extrait le dossier php_mongodb-2.2.1-8.4-ts-vs17-x64.zip
Ensuite on copie le fichier php_mongodb.dll dans D:\wamp64\bin\php\php8.4.15\ext
Trouve php.init avec la commande php --ini dans un cmd
D:\wamp64\bin\php\php8.4.15\php.ini
ouvre le CRTL F " extension=" il faut que ton fichier dll soit dans \ext
écris dans le fichier -> extension=php_mongodb.dll
Sauvegarde et ferme le fichier

Ferme et relance wamp  -> pour recharger le php.ini

Test pour vérifier si sa fonctionne :
    php -m | findstr mongodb 
    si le résultat de la commande ci-dessus est mongodb le fichier est bien présent dans ext et le fichier php.ini est bien configuré .

installer MongoDB pour symfony
composer require doctrine/mongodb-odm-bundle


met a jour le .env 

MONGODB_URI=mongodb://localhost:27017
MONGODB_DB=mongodb_symfony

test si sa fonctionne :
C:\Users\USUARIO>docker ps
CONTAINER ID   IMAGE     COMMAND                  CREATED         STATUS         PORTS                                             NAMES
7d471ae11e68   mongo:6   "docker-entrypoint.s…"   4 minutes ago   Up 4 minutes   0.0.0.0:27017->27017/tcp, [::]:27017->27017/tcp   mongodb_symphony

Test de la connection 
docker exec -it mongodb_symphony mongosh --eval "db.runCommand({ping:1})"

if (php -m | findstr mongodb) { Write-Host "MongoDB PRESENT" } else { Write-Host "MongoDB N'EST PAS PRESENT" }

6.8 création du log activité
structure à implémenté
{
  "_id": "ObjectId(...)",
  "type": "commande_creee",
  "message": "Commande CMD-XXXX créée par florian@email.fr",
  "email": "florian@email.fr",
  "role": "ROLE_ADMIN",
  "contexte": {
    "numero_commande": "CMD-XXXX",
    "montant": 450.00
  },
  "createdAt": "2026-02-28T10:30:00"
}

6.8.1 création du fichier LogActivite.php définissant les donnée représenté dans le log d'activité

id -> string -> Identifiant MongoDB
message -> string -> Message descriptif du log
email -> string -> Email de l'utilisateur concerné
role -> string -> Rôle de l'utilisateur concerné
"numero_commande": "CMD-XXXX", "montant": 450.00 -> Données contextuelles supplémentaires 
date -> DateTime -> Date et heure du log

6.8.2 Création du service de gestion de l'enregistrement des logs dans mongodb
création du fichier LogService.php dans src/Service

6.8.3 Ajout des logs à chaque controleur 

6.9 test

php bin/console cache:clear
php bin/console debug:container mongodb

On test 
7.1 Liste des conatiner
docker ps -a

7.2 Redémarrage du container
docker start vite_et_gourmand_logs

7.3 On revérifie son état 
docker ps

7.4 On test son accès en ligne de cmd à travers mongosh
docker exec -it vite_et_gourmand_logs mongosh

7.5 Affiche les logs
docker logs vite_et_gourmand_logs

test dans mongosh :
docker exec -it vite_et_gourmand_logs mongosh
use vite_et_gourmand_logs
show collections

Ensuite :
db.test.insertOne({
  message: "hello mongo test Insertion données",
  createdAt: new Date()
})

show collections

résultat :
test

Affichage : 
db.test.find().pretty()
résultat :
[
  {
    _id: ObjectId('69a32a02a35cdf85528de666'),
    message: 'hello mongo',
    createdAt: ISODate('2026-02-28T17:46:42.808Z')
  }
]

exit

résultat : Coté mongodb Tout fonctionne 

Maintenant test du coté Symfony :

création des collections
php bin/console doctrine:mongodb:schema:create

VERIFICATION DES ROUTES :

PS D:\wamp64\www\vite-et-gourmand-back> php bin/console debug:router
 ------------------------------------- ---------- -------------------------------------------- 
  Name                                  Method     Path
 ------------------------------------- ---------- -------------------------------------------- 
  api_doc                               GET|HEAD   /api/docs.{_format}
  api_genid                             GET|HEAD   /api/.well-known/genid/{id}
  api_validation_errors                 GET|HEAD   /api/validation_errors/{id}
  api_entrypoint                        GET|HEAD   /api/{index}.{_format}
  api_jsonld_context                    GET|HEAD   /api/contexts/{shortName}.{_format}
  _api_errors                           GET        /api/errors/{status}.{_format}
  _api_validation_errors_problem        GET        /api/validation_errors/{id}
  _api_validation_errors_hydra          GET        /api/validation_errors/{id}
  _api_validation_errors_jsonapi        GET        /api/validation_errors/{id}
  _api_validation_errors_xml            GET        /api/validation_errors/{id}
  _preview_error                        ANY        /_error/{code}.{_format}
  api_utilisateurs                      GET        /api/admin/utilisateurs
  api_utilisateur_show                  GET        /api/admin/utilisateurs/{id}
  api_utilisateur_delete                DELETE     /api/admin/utilisateurs/{id}
  api_utilisateur_delete_email          DELETE     /api/admin/utilisateurs/email/{email}       
  api_utilisateur_update                PUT        /api/admin/utilisateurs/{id}
  api_utilisateur_update_by_email       PUT        /api/admin/utilisateurs/email/{email}       
  api_admin_utilisateur_desactivation   PUT        /api/admin/utilisateurs/{id}/desactivation  
  api_admin_utilisateur_reactivation    PUT        /api/admin/utilisateurs/{id}/reactivation   
  api_admin_employes_create             POST       /api/admin/employes
  api_client_commande_delete            DELETE     /api/admin/commandes/{id}
  api_admin_avis_list                   GET        /api/admin/avis
  api_admin_avis_delete                 DELETE     /api/admin/avis/{id}
  api_admin_statistiques                GET        /api/admin/statistiques
  api_admin_logs                        GET        /api/admin/logs
  api_admin_statistiques_graphiques     GET        /api/admin/statistiques/graphiques
  api_admin_horaires_list               GET        /api/admin/horaires
  api_admin_horaires_create             POST       /api/admin/horaires
  api_admin_horaires_update             PUT        /api/admin/horaires/{id}
  api_admin_horaires_delete             DELETE     /api/admin/horaires/{id}
  api_admin_allergenes_list             GET        /api/admin/allergenes
  api_admin_allergenes_show             GET        /api/admin/allergenes/{id}
  api_admin_allergenes_create           POST       /api/admin/allergenes
  api_admin_allergenes_update           PUT        /api/admin/allergenes/{id}
  api_admin_allergenes_delete           DELETE     /api/admin/allergenes/{id}
  api_login                             POST       /api/login
  api_register                          POST       /api/register
  api_forgot_password                   POST       /api/forgot-password
  api_reset_password                    POST       /api/reset-password
  api_client_profil                     GET        /api/client/profil
  api_client_update_profil              PUT        /api/client/profil
  api_client_compte_desactivation       POST       /api/client/compte/desactivation
  api_client_commandes                  GET        /api/client/commandes
  api_client_commande_modifier          PUT        /api/client/commandes/{id}
  api_client_commande_annuler           POST       /api/client/commandes/{id}/annuler
  api_client_commande_suivi             GET        /api/client/commandes/{id}/suivi
  api_client_avis_list                  GET        /api/client/avis
  api_client_avis                       POST       /api/client/commandes/{id}/avis
  api_admin_commandes_create            POST       /api/admin/commandes
  api_admin_commandes_list              GET        /api/admin/commandes
  api_admin_commandes_show              GET        /api/admin/commandes/{id}
  api_admin_commandes_annuler           PUT        /api/admin/commandes/{id}/annuler
  api_contact                           POST       /api/contact
  api_employe_commandes                 GET        /api/employe/commandes
  api_employe_commandes_recherche       GET        /api/employe/commandes/recherche/{nom}
  api_employe_commande_statut           POST       /api/employe/commandes/{id}/statut
  api_employe_materiels_en_cours        GET        /api/employe/commandes/materiels-en-cours
  api_employe_materiel_show             GET        /api/employe/commandes/{id}/materiel
  api_employe_materiel_restitution      PUT        /api/employe/commandes/{id}/restitution
  api_employe_commandes_filtres         GET        /api/employe/commandes/filtres
  api_employe_avis                      GET        /api/employe/avis
  api_employe_avis_approuver            PUT        /api/employe/avis/{id}/approuver
  api_employe_avis_refuser              PUT        /api/employe/avis/{id}/refuser
  api_employe_menus_list                GET        /api/employe/menus
  api_employe_menus_show                GET        /api/employe/menus/{id}
  api_employe_menus_create              POST       /api/employe/menus
  api_employe_menus_update              PUT        /api/employe/menus/{id}
  api_employe_menus_delete              DELETE     /api/employe/menus/{id}
  api_employe_menus_images_add          POST       /api/employe/menus/{id}/images
  api_employe_menus_images_delete       DELETE     /api/employe/menus/{id}/images/{imageId}
  api_employe_menus_images_update       PUT        /api/employe/menus/{id}/images/{imageId}
  api_employe_themes_create             POST       /api/employe/themes
  api_employe_themes_update             PUT        /api/employe/themes/{id}
  api_employe_regimes_create            POST       /api/employe/regimes
  api_employe_regimes_update            PUT        /api/employe/regimes/{id}
  api_employe_allergenes_create         POST       /api/employe/allergenes
  api_employe_allergenes_update         PUT        /api/employe/allergenes/{id}
  api_employe_plats_create              POST       /api/employe/plats
  api_employe_plats_update              PUT        /api/employe/plats/{id}
  api_employe_plats_delete              DELETE     /api/employe/plats/{id}
  api_horaires                          GET        /api/horaires
  api_menus                             GET        /api/menus
  api_menu_show                         GET        /api/menus/{id}
  api_avis_public                       GET        /api/avis
  api_admin_menus_list                  GET        /api/admin/menus
  api_admin_menus_show                  GET        /api/admin/menus/{id}
  api_admin_menus_create                POST       /api/admin/menus
  api_admin_menus_update                PUT        /api/admin/menus/{id}
  api_admin_menus_delete                DELETE     /api/admin/menus/{id}
  api_admin_plats_list                  GET        /api/admin/plats
  api_admin_plats_show                  GET        /api/admin/plats/{id}
  api_admin_plats_create                POST       /api/admin/plats
  api_admin_plats_update                PUT        /api/admin/plats/{id}
  api_admin_plats_delete                DELETE     /api/admin/plats/{id}
  api_admin_regimes_list                GET        /api/admin/regimes
  api_admin_regimes_show                GET        /api/admin/regimes/{id}
  api_admin_regimes_create              POST       /api/admin/regimes
  api_admin_regimes_update              PUT        /api/admin/regimes/{id}
  api_admin_regimes_delete              DELETE     /api/admin/regimes/{id}
  api_admin_roles_list                  GET        /api/admin/roles
  api_admin_roles_show                  GET        /api/admin/roles/{id}
  api_admin_roles_create                POST       /api/admin/roles
  api_admin_roles_update                PUT        /api/admin/roles/{id}
  api_admin_roles_delete                DELETE     /api/admin/roles/{id}
  api_admin_themes_list                 GET        /api/admin/themes
  api_admin_themes_show                 GET        /api/admin/themes/{id}
  api_admin_themes_create               POST       /api/admin/themes
  api_admin_themes_update               PUT        /api/admin/themes/{id}
  api_admin_themes_delete               DELETE     /api/admin/themes/{id}
 ------------------------------------- ---------- --------------------------------------------

PS D:\wamp64\www\vite-et-gourmand-back> 


