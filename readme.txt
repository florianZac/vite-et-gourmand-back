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

6.4.2

