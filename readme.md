# Vite & Gourmand — Back-end (API REST Symfony)

API REST du traiteur **Vite & Gourmand** . Elle gère les utilisateurs, les menus, les plats, les commandes, les avis, les horaires et les statistiques, et sert le front-end du dépôt `vite-et-gourmand-front`.

- API en production : https://vite-et-gourmand-api-2b0eeb54e8d5.herokuapp.com
- Documentation Swagger : https://vite-et-gourmand-api-2b0eeb54e8d5.herokuapp.com/api/doc
- Front en production : https://vite-et-gourmand-c36478b4c1b0.herokuapp.com

> **Attention :** ce fichier est mon aide-mémoire complet. Il contient des identifiants réels (bases de données, Mailtrap, SendPit, Cloudinary, MongoDB Atlas). Si le dépôt est public, changer ces mots de passe ou masquer ces valeurs.
---

## Sommaire

- [Présentation du projet](#présentation-du-projet)
- [Partie 1 — Installation et mise en place en local](#partie-1--installation-et-mise-en-place-en-local)
  - [1. Installation et mise en place de Symfony](#1-installation-et-mise-en-place-de-symfony)
  - [2. Configuration de la base de données](#2-configuration-de-la-base-de-données)
  - [3. Création de la base de données Vite & Gourmand](#3-création-de-la-base-de-données-vite--gourmand)
  - [4. Sécurité : rôles, connexions et JWT](#4-sécurité--rôles-connexions-et-jwt)
  - [5. Cache, mails et sécurité du formulaire de contact](#5-cache-mails-et-sécurité-du-formulaire-de-contact)
  - [6. Commande Symfony (cron) et vérifications Doctrine](#6-commande-symfony-cron-et-vérifications-doctrine)
  - [7. MongoDB pour les logs d'activité](#7-mongodb-pour-les-logs-dactivité)
  - [8. Vérification des routes](#8-vérification-des-routes)
  - [9. Planification du cron et documentation Swagger](#9-planification-du-cron-et-documentation-swagger)
- [Partie 2 — Déploiement sur Heroku](#partie-2--déploiement-sur-heroku)
- [Partie 3 — Base de données distante JawsDB](#partie-3--base-de-données-distante-jawsdb)
- [Partie 4 — Données, JWT, logs et mails en production](#partie-4--données-jwt-logs-et-mails-en-production)
- [Partie 5 — Cloudinary et Docker](#partie-5--cloudinary-et-docker)
- [Partie 6 — Optimisation des requêtes SQL](#partie-6--optimisation-des-requêtes-sql)

---


## Présentation du projet

### Technologies

| Rôle | Outil |
|---|---|
| Langage / framework | PHP 8.4, Symfony 7.4 (mode API) |
| Base relationnelle | MySQL 8.4 (WAMP en local, JawsDB sur Heroku) avec Doctrine ORM |
| Base NoSQL | MongoDB (Docker en local, MongoDB Atlas en production) avec Doctrine ODM |
| Authentification | LexikJWTAuthenticationBundle (jeton JWT valable 1 heure) |
| Documentation | NelmioApiDocBundle (Swagger) |
| Mails | Symfony Mailer + Twig (Mailtrap / SendPit) |
| Images | Cloudinary |
| Distance de livraison | Nominatim (adresse → GPS) + OSRM (distance par la route) |
| Sécurité | Rate Limiter, CORS (NelmioCorsBundle), validation et nettoyage des données |
| Déploiement | Heroku |
| Environnement local | WAMP ou Docker Compose |

### Organisation du code

| Dossier | Contenu |
|---|---|
| `src/Controller/` | Auth, Menu, Plat, Commande, Client, Employe, Admin, Contact, Horaire, Geocode |
| `src/Entity/` | Utilisateur, Role, Menu, Plat, Allergene, Theme, Regime, MenuTags, Commande, SuiviCommande, Avis, Horaire, PasswordResetToken |
| `src/Document/` | `LogActivite` (document MongoDB) |
| `src/Repository/` | Requêtes Doctrine |
| `src/Service/` | LogService, MailerService, CloudinaryService, NominatimService, OsrmService, DistanceService, DateService, SanitizerService |
| `src/Command/` | `CheckRetourMaterielCommand` (vérification quotidienne du matériel prêté) |
| `src/Security/` | LoginSuccessHandler, LoginFailureHandler |
| `docs/` | Scripts SQL, règles métier, collection Postman, diagrammes Mermaid |

### Rôles

| Rôle | Accès |
|---|---|
| Visiteur | Menus, horaires, avis publiés, contact, inscription, connexion |
| `ROLE_CLIENT` | + commander, suivre et annuler ses commandes, laisser un avis, modifier son profil |
| `ROLE_EMPLOYE` | + gestion des commandes, des avis, des menus, plats, thèmes, régimes, allergènes et tags (sans suppression) |
| `ROLE_ADMIN` | + statistiques, gestion des comptes, horaires, suppressions |

### Règles métier principales

- Commande au moins **3 jours ouvrables** avant la prestation (**14 jours** au-delà de 20 personnes). Les week-ends et jours fériés sont exclus, y compris ceux qui dépendent de Pâques.
- Réduction de **10 %** si le nombre de personnes dépasse le minimum du menu de plus de 5.
- Livraison gratuite dans un rayon de **10 km** du restaurant, sinon **5 € + 0,59 €/km** au-delà (200 km maximum).
- Cycle de vie strict : En attente → Acceptée → En préparation → En livraison → Terminée (ou Annulée), avec un email aux étapes clés et un historique dans `suivi_commande`.
- Matériel prêté : la commande `app:check-retour-materiel` envoie un email de pénalité (600 €) si le matériel n'est pas rendu sous 10 jours ouvrés.

Le détail complet est dans `docs/Règles Métier.docx`.

---

# Partie 1 — Installation et mise en place en local

## 1. Installation et mise en place de Symfony

### 1.1 Vérification des versions de PHP, Composer, etc.

```bash
php -v        # vérification de la version de PHP
composer -v   # vérification de la version de Composer
symfony -v    # vérification de la version du Command Line Interface (CLI)
```

### 1.2 Vérification des prérequis Symfony avant installation

```bash
symfony check:requirements
```

### 1.3 Déplacement et création du dossier projet

J'utilise actuellement le logiciel WampServer : `cd D:\wamp64\www`

Déplacement dans le dossier concerné : `cd D:\wamp64\www`, ensuite `pwd` ou `ls` pour vérifier où on est.

### 1.4 Cas d'utilisation : site monolithique (un seul bloc, pas de séparation front / back)

```bash
symfony new vite-et-gourmand-back --version="lts" --webapp
```

Car il installe Twig, les formulaires Symfony, le moteur de templates…

### 1.5 Cas d'utilisation : séparation du back et du front

Dans mon cas, je veux séparer le front et le back, car mon serveur ne va générer que du JSON. La commande est donc la suivante :

```bash
symfony new vite-et-gourmand-back --version="lts" --api
```

### 1.6 Installation de Symfony (cas personnel)

```bash
symfony new vite-et-gourmand-back --version="lts" --api
```

### 1.7 Déplacement dans le dossier

```bash
cd vite-et-gourmand-back
```

### 1.8 Lancement et arrêt du serveur

```bash
symfony server:start   # lancement du serveur
symfony server:stop    # arrêt du serveur
```

Si on souhaite le lancer à travers la console du dossier `bin` (attention à la version de PHP) :

```bash
php bin/console server:run
```

Au cas où, installation d'un certificat de sécurité (pas nécessaire pour l'instant) :

```bash
symfony server:ca:install
```
---

## 2. Configuration de la base de données

La configuration se fait dans le fichier `.env` du projet.

### 2.1 Valeur par défaut

```dotenv
DATABASE_URL="postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=16&charset=utf8"
```

### 2.2 Mon cas d'utilisation

- Sur WampServer, on utilise MySQL.
- Utilisateur par défaut actuel (à modifier plus tard) : `florian`
- Mot de passe (simple pour ne pas l'oublier en local) : `123456`
- Port par défaut de WampServer : `3306`
- Nom de la base de données créée : `vite_et_gourmand`
- Version de MySQL dans WampServer : 8.0
- Encodage européen : `charset=utf8mb4`

Donc :

```dotenv
DATABASE_URL="mysql://florian:123456@localhost:3306/vite_et_gourmand?serverVersion=8.4.7&charset=utf8mb4"
```

---

## 3. Création de la base de données Vite & Gourmand

### 3.1 Création de la base de données avec Doctrine

Voir `database.sql`, `create_delete_database` et `constraints.sql` pour la création manuelle.

### 3.2 Installation de MakerBundle

```bash
composer require symfony/maker-bundle --dev
```

### 3.3 Création des entités (les tables)

La commande crée deux fichiers : `src/Entity/NomEntite.php` (la classe entité) et `src/Repository/NomEntiteRepository.php` (les requêtes en base).

```bash
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
```

### 3.4 Création des clés étrangères

```bash
php bin/console make:entity Utilisateur
```

Exemple de configuration :

```text
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
```

```bash
php bin/console make:entity Avis
php bin/console make:entity Menu
php bin/console make:entity Plat
php bin/console make:entity Commande
```

Etc. Penser à bien vérifier la concordance des entités générées avec le MCD avant de passer à la suite.

### 3.5 Création automatique de la base de données

```bash
php bin/console doctrine:database:create
```

### 3.6 Génération de la migration (création de la base de données)

```bash
php bin/console make:migration
```

En dev seulement, mise à jour directe du schéma (sans fichier de migration) :

```bash
php bin/console doctrine:schema:update --force
```

### 3.7 Exécution de la migration pour générer la base de données

```bash
php bin/console doctrine:migrations:migrate
```

---

## 4 Installation du composant sécurity pour gérer les rôles les connexions et la protection des routes (rôles, connexions et JWT)

```bash
composer.bat require lexik/jwt-authentication-bundle
```

### 4.2 Génération des clés de chiffrement

```bash
php bin/console lexik:jwt:generate-keypair
```

Génération de deux fichiers dans le dossier `config/jwt` :

- `private.pem` : la clé privée pour signer les jetons ;
- `public.pem` : la clé publique pour vérifier les jetons.

### 4.3 Modification du fichier .env

```dotenv
# variable Symfony qui pointe vers le fichier private.pem où est présente la clé privée
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
# variable Symfony qui pointe vers le fichier public.pem où est présente la clé publique
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
# le mot de passe qui protège la clé privée
JWT_PASSPHRASE=vite_et_gourmand_secret
```

### 4.4 Régénération des clés JWT

```bash
php bin/console lexik:jwt:generate-keypair --overwrite
```

### 4.5 Création des handlers dans src/Security

- `LoginFailureHandler`
- `LoginSuccessHandler`

Test des routes et des jetons.

### 4.6 Installation de DoctrineBundle pour insérer les données dans la base

```bash
composer.bat require doctrine/doctrine-bundle
```

### 4.7 Création d'un script SQL pour insérer les utilisateurs et la table role

`création_utilisateur.sql`

### 4.8 Création de la route login dans un contrôleur

```bash
php bin/console make:controller AuthController
```

### 4.9 Lancement du serveur pour tester le handler

```bash
symfony server:start
```

Sans Symfony CLI :

```bash
php -S localhost:8000 -t public/
```

---

## 5. Cache, mails et sécurité du formulaire de contact

### 5.1 Vider le cache Symfony

```bash
php bin/console cache:clear
```

### 5.2 Création du document de test Postman

`Test_API_postman`

### 5.3 Choix de la solution mail

Choix : **Mailtrap**. Pourquoi :

- facile à installer et à configurer sur Symfony ;
- interface web pour voir les mails envoyés ;
- fonctionne sans serveur mail ;
- gratuit ;
- peu de temps disponible.

### 5.4 Installation du composant officiel de Symfony pour envoyer des emails

```bash
composer require symfony/mailer
```

### 5.5 Installation de Twig pour créer les templates HTML des mails uniquement

Cela permet d'avoir un vrai fichier HTML dédié à chaque email.

```bash
composer require twig
```

### 5.6 Création d'un template d'email

Dans le dossier `templates/emails/contact.html.twig`.

### 5.7 Création d'un compte Mailtrap

https://mailtrap.io/inboxes/4404595/messages/5352687463/html

Et récupération du `MAILER_DSN` : Mailtrap → Sandbox → Intégration → Symfony :

```dotenv
MAILER_DSN="smtp://6836c3cc28f364:****c337@sandbox.smtp.mailtrap.io:2525"
```

> Ne pas oublier de régénérer les identifiants : le mot de passe ne doit pas être masqué. J'ai perdu 1 heure de débogage à cause de ça.

### 5.8 Création des fonctions de nettoyage, de validation par regex et de limitation dans ContactController.php

### 5.9 Installation du rate limiting

```bash
composer require symfony/rate-limiter
composer require symfony/lock
php bin/console cache:clear
```

### 5.10 Mise en place des différentes sécurités

**Protection n°1 : rate limiting**
Protège contre un attaquant qui envoie des milliers de requêtes par seconde pour surcharger le serveur ou spammer la boîte mail.
Solution : identifier l'adresse IP de l'utilisateur et ne lui accorder que 5 requêtes par heure.

**Protection n°2 : Content-Type**
Un bot peut envoyer dans le formulaire des données de type XML, formulaire HTML ou fichier binaire pour provoquer des erreurs.
Solution : vérifier le type des données de tous les inputs et textarea, et ne traiter que les requêtes au bon format.

**Protection n°3 : taille du body**
Un attaquant peut envoyer une requête de plusieurs mégaoctets pour saturer la mémoire du serveur.
Solution : on limite la taille à 10 Ko avant le traitement du JSON.

**Protection n°4 : honeypot** (à vérifier, je ne suis pas sûr de mon coup)
Les bots automatiques remplissent tous les champs d'un formulaire sans réfléchir.
Solution : si ce champ caché est rempli, c'est forcément un bot, car un humain ne le voit pas.

**Protection n°5 : nettoyage des données (sanitizer)**
Un attaquant peut créer du code malveillant avec des espaces, du HTML, du PHP, du JS ou des caractères spéciaux.
Solution :

- `trim` : supprime les espaces inutiles ;
- `strip_tags` : supprime les balises HTML, JS et PHP ;
- `htmlspecialchars` : évite les attaques XSS par script malveillant.

**Protection n°6 : injection SQL**
Un attaquant peut tenter d'injecter des commandes SQL dans les champs texte pour manipuler la base de données.
Solution :

```php
preg_replace('/(\bunion\b|\bselect\b|\binsert\b|\bdelete\b|\bdrop\b|\bupdate\b)/i')
```

Cette regex supprime tous les mots-clés SQL dangereux.

### 5.11 Apprendre à utiliser l'injection de dépendances

```bash
php bin/console debug:autowiring --all
```

- https://symfony.com/doc/current/reference/forms/types/entity.html
- https://symfony.com/doc/current/doctrine.html#fetching-objects-from-the-database

### 5.12 Vérification des routes après renommage de UtilisateurController.php en AdminController.php

Pour respecter la règle « un contrôleur, une seule responsabilité », que je ne respectais pas.

Vérifier que Symfony voit bien les nouvelles routes :

```powershell
php bin/console debug:router | Select-String "admin"
```

Vérifier toutes les routes du projet :

```powershell
php bin/console debug:router | Select-String "api"
```

---


## 6. Commande Symfony (cron) et vérifications Doctrine

### 6.1 Installation des commandes Symfony pour l'utilisation d'un cron

Objectif : chaque jour, vérifier les commandes (`pret_materiel=1`, `restitution_materiel=0`) dont le statut est « Livré ».

### 6.2 Création du fichier de commande

```bash
php bin/console make:command
```

Choose a command name : `CheckRetourMaterielCommand`
Crée automatiquement la commande : `src/Command/CheckRetourMaterielCommand.php`

### 6.3 Lancement de la commande Symfony

```bash
php bin/console app:check-retour-materiel
```

### 6.4 Vérification de la commande

```bash
php bin/console list
```

### 6.5 Utilisation de la commande créée dans le terminal

```bash
php bin/console app:check-retour-materiel
```

### 6.6 Vérification des propriétés et des méthodes après modification d'une entité

Mise à jour du schéma :

```bash
php bin/console doctrine:schema:update --force
```

Valide le mapping Doctrine :

```bash
php bin/console doctrine:schema:validate
```

Génère le SQL théorique et inspecte les clés étrangères :

```bash
php bin/console doctrine:schema:create --dump-sql
```

Vérifie que la base est synchronisée (si « No changes detected », c'est parfait) :

```bash
php bin/console doctrine:migrations:diff
```

### 6.7 Après création du cron et de la Doctrine, tester manuellement check-retour-materiel

```bash
php bin/console app:check-retour-materiel
```

### 6.8 Voir la version de doctrine-bundle

```bash
composer show doctrine/doctrine-bundle | grep versions
```

---

## 7. MongoDB pour les logs d'activité

**Quel intérêt ?** Utiliser MongoDB pour gérer les logs d'activité de la société.
**Pourquoi ?** Ce sont des données volumineuses, sans schéma fixe et sans relation entre elles : c'est le cas d'usage idéal du NoSQL.

### 7.1 Vérification de MongoDB avant l'installation

```bash
composer show | findstr mongodb   # sur Windows
composer show | grep mongodb      # sur Linux
```

### 7.2 Installation de MongoDB

```bash
composer require doctrine/mongodb-odm-bundle
```

### 7.3 Vérification de la version de MongoDB

`mongod --version` : la commande n'existe pas.

```bash
php -m
```

Dans VS Code : la réponse contient-elle `mongodb` ? Si non, continuer.

### 7.4 Ouvrir le bon dossier PHP (pour savoir lequel est installé : php -v)

Réponse : `PHP 8.4.15`
Donc le bon dossier : `D:\wamp64\bin\php\php8.4.15\ext\`

```powershell
if (Test-Path "D:\wamp64\bin\php\php8.4.15\ext\php_mongodb.dll") { echo "php_mongodb.dll EST PRESENT" } else { echo "php_mongodb.dll N'EST PAS PRESENT" }
```

Pas de DLL disponible : passage par Docker.

### 7.5 Vérification de Docker

```bash
docker --version
```

Pas de version de Docker installée.

### 7.6 Installation de Docker

- https://www.docker.com/products/docker-desktop/
- https://docs.docker.com/desktop/setup/install/windows-install/

### 7.7 Fermer tous les processus Docker existants

Liste tous les processus Docker :

```powershell
Get-Process *docker* | Select-Object Id, ProcessName
```

Termine tous les processus Docker bloqués :

```powershell
Get-Process *docker* | Stop-Process -Force
```

### 7.8 Redémarrer les services Docker

Arrêter le service Docker Desktop :

```powershell
Stop-Service com.docker.service -Force
```

Démarrer le service Docker Desktop :

```powershell
Start-Service com.docker.service
```

### 7.9 Supprimer les anciens conteneurs ou images corrompus

```bash
docker container prune -f   # supprime tous les conteneurs arrêtés
docker image prune -a -f    # supprime toutes les images inutilisées
```

### 7.10 Lancer MongoDB via Docker (PowerShell avec droits administrateur)

```powershell
mkdir D:\DockerData\MongoDB
docker run -d --name mongodb -p 27017:27017 -v D:\DockerData\MongoDB:/data/db mongo:6
```

Explications :

- `-d` : mode détaché (arrière-plan) ;
- `--name mongodb` : nom du conteneur ;
- `-p 27017:27017` : accessible depuis le PC sur localhost ;
- `-v D:\DockerData\MongoDB:/data/db` : MongoDB stocke les données sur D:, pas dans Docker ;
- `mongo:6` : version officielle MongoDB 6.

Vérification que MongoDB tourne :

```text
PS C:\WINDOWS\system32> docker ps

résultat : ça tourne bien  Ports  :  0.0.0.0:27017 : 27017/tcp
CONTAINER ID   IMAGE     COMMAND                  CREATED         STATUS              PORTS                                             NAMES
dab15d72eb7e   mongo:6   "docker-entrypoint.s…"   2 minutes ago   Up About a minute   0.0.0.0:27017 : 27017/tcp, [::]:27017 : 27017/tcp   mongodb
PS C:\WINDOWS\system32>
```

### 7.11 Connexion entre PHP et MongoDB (Docker)

```php
$client = new MongoDB\Client("mongodb://127.0.0.1:27017");
```

- `$client` : on crée un objet client MongoDB ;
- `new MongoDB\Client(...)` : on dit à PHP « je veux me connecter à MongoDB » ;
- `"mongodb://127.0.0.1:27017"` : l'adresse du serveur MongoDB ;
- `127.0.0.1` : le PC local (localhost) ;
- `27017` : le port où MongoDB écoute.

### 7.12 Créer un projet test pour MongoDB

```powershell
cd D:\wamp64\www\vite-et-gourmand-back
mkdir test_mongo
cd test_mongo
```

Créer un `composer.json` minimal :

```bash
composer install --ignore-platform-req=ext-mongodb
```

Supprimer l'ancien conteneur MongoDB :

```bash
docker rm -f mongodb
```

Création :

```powershell
docker run -d --name vite_et_gourmand_logs -p 27017:27017 -v D:\docker-data\vite_et_gourmand_logs:/data/db mongo:6
```

Installer la librairie PHP (option test) :

```bash
composer require mongodb/mongodb --ignore-platform-req=ext-mongodb
```

`--ignore-platform-req=ext-mongodb` permet d'installer les fichiers PHP même si `ext-mongodb` est absent.

### 7.13 Installer l'extension PHP mongodb sous WAMP

Affiche le php.ini utilisé :

```bash
php --ini
```

Résultat : `D:\wamp64\bin\php\php8.4.15\php.ini`

```powershell
php -i | findstr "Thread"
```

Résultat :

```text
Thread Safety => enabled
Thread API => Windows Threads
```

Il me faut donc : **PHP 8.4 / Thread Safe / x64**.

1. Aller sur https://pecl.php.net/package/mongodb
2. Cliquer sur la DLL de la dernière version : à côté de la version 2.2.1, choisir 8.4 Thread Safe (TS) x64.
3. Extraire le dossier `php_mongodb-2.2.1-8.4-ts-vs17-x64.zip`.
4. Copier le fichier `php_mongodb.dll` dans `D:\wamp64\bin\php\php8.4.15\ext`.
5. Trouver le php.ini avec la commande `php --ini` dans un terminal : `D:\wamp64\bin\php\php8.4.15\php.ini`
6. L'ouvrir, faire Ctrl + F sur `extension=` (le fichier DLL doit être dans `\ext`).
7. Ajouter la ligne `extension=php_mongodb.dll`.
8. Sauvegarder et fermer le fichier.
9. Fermer et relancer WAMP pour recharger le php.ini.

Test pour vérifier que ça fonctionne :

```bash
php -m | findstr mongodb
```

Si le résultat est `mongodb`, le fichier est bien présent dans `ext` et le php.ini est bien configuré.

### 7.14 Installer MongoDB pour Symfony

```bash
composer require doctrine/mongodb-odm-bundle
```

Mettre à jour le `.env` :

```dotenv
MONGODB_URI=mongodb://localhost:27017
MONGODB_DB=mongodb_symfony
```

Test de fonctionnement :

```text
C:\Users\USUARIO>docker ps
CONTAINER ID   IMAGE     COMMAND                  CREATED         STATUS         PORTS                                             NAMES
7d471ae11e68   mongo:6   "docker-entrypoint.s…"   4 minutes ago   Up 4 minutes   0.0.0.0:27017 : 27017/tcp, [::]:27017 : 27017/tcp   mongodb_symphony
```

Test de la connexion :

```bash
docker exec -it mongodb_symphony mongosh --eval "db.runCommand({ping:1})"
```

```powershell
if (php -m | findstr mongodb) { Write-Host "MongoDB PRESENT" } else { Write-Host "MongoDB N'EST PAS PRESENT" }
```

### 7.15 Création du log d'activité

Structure à implémenter :

```json
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
```

### 7.16 Création du fichier LogActivite.php (données représentées dans le log d'activité)

| Champ | Type | Description |
|---|---|---|
| `id` | string | Identifiant MongoDB |
| `message` | string | Message descriptif du log |
| `email` | string | Email de l'utilisateur concerné |
| `role` | string | Rôle de l'utilisateur concerné |
| `contexte` | objet | Données supplémentaires, ex. `"numero_commande": "CMD-XXXX", "montant": 450.00` |
| `date` | DateTime | Date et heure du log |

### 7.17 Création du service d'enregistrement des logs dans MongoDB

Création du fichier `LogService.php` dans `src/Service`.

### 7.18 Ajout des logs dans chaque contrôleur

### 7.19 Test

```bash
php bin/console cache:clear
php bin/console debug:container mongodb
```

Liste des conteneurs :

```bash
docker ps -a
```

Redémarrage du conteneur :

```bash
docker start vite_et_gourmand_logs
```

On revérifie son état :

```bash
docker ps
```

On teste son accès en ligne de commande avec mongosh :

```bash
docker exec -it vite_et_gourmand_logs mongosh
```

Affiche les logs :

```bash
docker logs vite_et_gourmand_logs
```

Test dans mongosh :

```bash
docker exec -it vite_et_gourmand_logs mongosh
```

```javascript
use vite_et_gourmand_logs
show collections

db.test.insertOne({
  message: "hello mongo test Insertion données",
  createdAt: new Date()
})

show collections
```

Résultat :

```text
test
```

Affichage :

```javascript
db.test.find().pretty()
```

Résultat :

```text
[
  {
    _id: ObjectId('69a32a02a35cdf85528de666'),
    message: 'hello mongo',
    createdAt: ISODate('2026-02-28T17:46:42.808Z')
  }
]
```

```javascript
exit
```

Résultat : côté MongoDB, tout fonctionne.

### 7.20 Test côté Symfony

Création des collections :

```bash
php bin/console doctrine:mongodb:schema:create
```

---

## 8. Vérification des routes

```bash
php bin/console debug:router
```

<details>
<summary>Résultat complet de <code>php bin/console debug:router</code> (cliquer pour afficher)</summary>

```text
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
  api_admin_commande_delete             DELETE     /api/admin/commandes/{id}
  api_admin_avis_list                   GET        /api/admin/avis
  api_admin_avis_delete                 DELETE     /api/admin/avis/{id}
  api_admin_statistiques                GET        /api/admin/statistiques
  api_admin_statistiques_graphiques     GET        /api/admin/statistiques/graphiques
  api_admin_logs                        GET        /api/admin/logs
  api_admin_horaires_create             POST       /api/admin/horaires
  api_admin_horaires_update             PUT        /api/admin/horaires/{id}
  api_admin_horaires_delete             DELETE     /api/admin/horaires/{id}
  api_login                             POST       /api/login
  api_register                          POST       /api/registerapi_doc                               GET|HEAD   /api/docs.{_format}
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
  api_admin_commande_delete             DELETE     /api/admin/commandes/{id}
  api_admin_avis_list                   GET        /api/admin/avis
  api_admin_avis_delete                 DELETE     /api/admin/avis/{id}
  api_admin_statistiques                GET        /api/admin/statistiques
  api_admin_statistiques_graphiques     GET        /api/admin/statistiques/graphiques
  api_admin_logs                        GET        /api/admin/logs
  api_admin_horaires_create             POST       /api/admin/horaires
  api_admin_horaires_update             PUT        /api/admin/horaires/{id}
  api_admin_horaires_delete             DELETE     /api/admin/horaires/{id}
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
  api_employe_commande_suivi            GET        /api/employe/commandes/{id}/suivi
  api_employe_avis                      GET        /api/employe/avis
  api_employe_avis_approuver            PUT        /api/employe/avis/{id}/approuver
  api_employe_avis_refuser              PUT        /api/employe/avis/{id}/refuser
  api_employe_menus_create              POST       /api/employe/menus
  api_employe_menus_update              PUT        /api/employe/menus/{id}
  api_employe_menus_delete              DELETE     /api/employe/menus/{id}
  api_employe_menus_images_add          POST       /api/employe/menus/{id}/images
  api_employe_menus_images_delete       DELETE     /api/employe/menus/{id}/images/{imageId}
  api_employe_menus_images_update       PUT        /api/employe/menus/{id}/images/{imageId}
  api_employe_themes_create             POST       /api/employe/themes
  api_employe_themes_update             PUT        /api/employe/themes/{id}
  api_employe_themes_delete             DELETE     /api/employe/themes/{id}
  api_employe_regimes_create            POST       /api/employe/regimes
  api_employe_regimes_update            PUT        /api/employe/regimes/{id}
  api_employe_regimes_delete            DELETE     /api/employe/regimes/{id}
  api_employe_allergenes_create         POST       /api/employe/allergenes
  api_employe_allergenes_update         PUT        /api/employe/allergenes/{id}
  api_employe_allergenes_delete         DELETE     /api/employe/allergenes/{id}
  api_employe_plats_create              POST       /api/employe/plats
  api_employe_plats_update              PUT        /api/employe/plats/{id}
  api_employe_plats_delete              DELETE     /api/employe/plats/{id}
  geocode_address                       ANY        /geocode
  distance_between                      ANY        /distance
  delivery_cost                         GET        /delivery-cost
  api_horaires                          GET        /api/horaires
  api_menus                             GET        /api/menus
  api_menu_show                         GET        /api/menus/{id}
  api_themes_list                       GET        /api/themes
  api_regimes_list                      GET        /api/regimes
  api_allergenes_list                   GET        /api/allergenes
  api_plats_list                        GET        /api/plats
  api_avis_public                       GET        /api/avis
  api_admin_plats_list                  GET        /api/admin/plats
  api_admin_plats_show                  GET        /api/admin/plats/{id}
  api_admin_plats_create                POST       /api/admin/plats
  api_admin_plats_update                PUT        /api/admin/plats/{id}
  api_admin_plats_delete                DELETE     /api/admin/plats/{id}api_doc                               GET|HEAD   /api/docs.{_format}
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
  api_admin_commande_delete             DELETE     /api/admin/commandes/{id}
  api_admin_avis_list                   GET        /api/admin/avis
  api_admin_avis_delete                 DELETE     /api/admin/avis/{id}
  api_admin_statistiques                GET        /api/admin/statistiques
  api_admin_statistiques_graphiques     GET        /api/admin/statistiques/graphiques
  api_admin_logs                        GET        /api/admin/logs
  api_admin_horaires_create             POST       /api/admin/horaires
  api_admin_horaires_update             PUT        /api/admin/horaires/{id}
  api_admin_horaires_delete             DELETE     /api/admin/horaires/{id}
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
  api_employe_commande_suivi            GET        /api/employe/commandes/{id}/suivi
  api_employe_avis                      GET        /api/employe/avis
  api_employe_avis_approuver            PUT        /api/employe/avis/{id}/approuver
  api_employe_avis_refuser              PUT        /api/employe/avis/{id}/refuser
  api_employe_menus_create              POST       /api/employe/menus
  api_employe_menus_update              PUT        /api/employe/menus/{id}
  api_employe_menus_delete              DELETE     /api/employe/menus/{id}
  api_employe_menus_images_add          POST       /api/employe/menus/{id}/images
  api_employe_menus_images_delete       DELETE     /api/employe/menus/{id}/images/{imageId}
  api_employe_menus_images_update       PUT        /api/employe/menus/{id}/images/{imageId}
  api_employe_themes_create             POST       /api/employe/themes
  api_employe_themes_update             PUT        /api/employe/themes/{id}
  api_employe_themes_delete             DELETE     /api/employe/themes/{id}
  api_employe_regimes_create            POST       /api/employe/regimes
  api_employe_regimes_update            PUT        /api/employe/regimes/{id}
  api_employe_regimes_delete            DELETE     /api/employe/regimes/{id}
  api_employe_allergenes_create         POST       /api/employe/allergenes
  api_employe_allergenes_update         PUT        /api/employe/allergenes/{id}
  api_employe_allergenes_delete         DELETE     /api/employe/allergenes/{id}
  api_employe_plats_create              POST       /api/employe/plats
  api_employe_plats_update              PUT        /api/employe/plats/{id}
  api_employe_plats_delete              DELETE     /api/employe/plats/{id}
  geocode_address                       ANY        /geocode
  distance_between                      ANY        /distance
  delivery_cost                         GET        /delivery-cost
  api_horaires                          GET        /api/horaires
  api_menus                             GET        /api/menus
  api_menu_show                         GET        /api/menus/{id}
  api_themes_list                       GET        /api/themes
  api_regimes_list                      GET        /api/regimes
  api_allergenes_list                   GET        /api/allergenes
  api_plats_list                        GET        /api/plats
  api_avis_public                       GET        /api/avis
  api_admin_plats_list                  GET        /api/admin/plats
  api_admin_plats_show                  GET        /api/admin/plats/{id}
  api_admin_plats_create                POST       /api/admin/plats
  api_admin_plats_update                PUT        /api/admin/plats/{id}
  api_admin_plats_delete                DELETE     /api/admin/plats/{id}
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
  api_employe_commande_suivi            GET        /api/employe/commandes/{id}/suivi
  api_employe_avis                      GET        /api/employe/avis
  api_employe_avis_approuver            PUT        /api/employe/avis/{id}/approuver
  api_employe_avis_refuser              PUT        /api/employe/avis/{id}/refuser
  api_employe_menus_create              POST       /api/employe/menus
  api_employe_menus_update              PUT        /api/employe/menus/{id}
  api_employe_menus_delete              DELETE     /api/employe/menus/{id}
  api_employe_menus_images_add          POST       /api/employe/menus/{id}/images
  api_employe_menus_images_delete       DELETE     /api/employe/menus/{id}/images/{imageId}
  api_employe_menus_images_update       PUT        /api/employe/menus/{id}/images/{imageId}
  api_employe_themes_create             POST       /api/employe/themes
  api_employe_themes_update             PUT        /api/employe/themes/{id}
  api_employe_themes_delete             DELETE     /api/employe/themes/{id}
  api_employe_regimes_create            POST       /api/employe/regimes
  api_employe_regimes_update            PUT        /api/employe/regimes/{id}
  api_employe_regimes_delete            DELETE     /api/employe/regimes/{id}
  api_employe_allergenes_create         POST       /api/employe/allergenes
  api_employe_allergenes_update         PUT        /api/employe/allergenes/{id}
  api_employe_allergenes_delete         DELETE     /api/employe/allergenes/{id}
  api_employe_plats_create              POST       /api/employe/plats
  api_employe_plats_update              PUT        /api/employe/plats/{id}
  api_employe_plats_delete              DELETE     /api/employe/plats/{id}
  geocode_address                       ANY        /geocode
  distance_between                      ANY        /distance
  delivery_cost                         GET        /delivery-cost
  api_horaires                          GET        /api/horaires
  api_menus                             GET        /api/menus
  api_menu_show                         GET        /api/menus/{id}
  api_themes_list                       GET        /api/themes
  api_regimes_list                      GET        /api/regimes
  api_allergenes_list                   GET        /api/allergenes
  api_plats_list                        GET        /api/plats
  api_avis_public                       GET        /api/avis
  api_admin_plats_list                  GET        /api/admin/plats
  api_admin_plats_show                  GET        /api/admin/plats/{id}
  api_admin_plats_create                POST       /api/admin/plats
  api_admin_plats_update                PUT        /api/admin/plats/{id}
  api_admin_plats_delete                DELETE     /api/admin/plats/{id}
 ------------------------------------- ---------- --------------------------------------------

PS D:\wamp64\www\vite-et-gourmand-back>
```

</details>

---

## 9. Planification du cron et documentation Swagger

### 9.1 Test de la commande

```bash
php bin/console app:check-retour-materiel
```

### 9.2 Configuration du cron sur Linux

Éditer la crontab du serveur Linux :

```bash
crontab -e
```

Ensuite, ajouter la ligne :

```bash
# Tous les jours à 9h00 (minute heure jour mois jour_semaine commande)
0 8 * * * /usr/bin/php /var/www/html/mon-projet/bin/console app:check-retour-materiel
```

> Remarque : `0 8` correspond à 8h00. Pour 9h00, écrire `0 9`.

### 9.3 Version Windows

- Panneau de configuration → Planificateur de tâches
- Nouvelle tâche : déclencher tous les jours à 8h
- Action : `php C:\wamp64\www\mon-projet\bin/console app:check-retour-materiel`

### 9.4 Génération de l'APP_SECRET

```bash
php bin/console secrets:generate-keys
```

### 9.5 Création de la documentation de l'API (/api/doc)

Installer :

```bash
composer require nelmio/api-doc-bundle
```

Dans `config/packages/nelmio_api_doc.yaml`, ajouter :

```yaml
nelmio_api_doc:
    documentation:
        info:
            title: "Vite & Gourmand - API"
            description: "API REST du service traiteur Vite & Gourmand"
            version: "1.0.0"
        components:
            securitySchemes:
                Bearer:
                    type: http
                    scheme: bearer
                    bearerFormat: JWT
        security:
            - Bearer: []

    # Une area par rôle
    areas:
        # Doc publique : routes sans authentification
        default:
            path_patterns:
                - ^/api/menus
                - ^/api/themes
                - ^/api/regimes
                - ^/api/allergenes
                - ^/api/plats
                - ^/api/avis
                - ^/api/horaires
                - ^/api/contact
                - ^/api/register
                - ^/api/login
                - ^/api/forgot-password
                - ^/api/reset-password
                - ^/geocode
                - ^/distance
                - ^/delivery-cost

        # Doc client : routes /api/client/*
        client:
            path_patterns:
                - ^/api/client

        # Doc employé : routes /api/employe/*
        employe:
            path_patterns:
                - ^/api/employe

        # Doc admin : routes /api/admin/*
        admin:
            path_patterns:
                - ^/api/admin
```

Dans `config/routes/nelmio_api_doc.yaml`, ajouter :

```yaml
# Page Swagger pour chaque area
app.swagger_ui_public:
    path: /api/doc/public
    methods: GET
    defaults:
        _controller: nelmio_api_doc.controller.swagger_ui
        area: public

app.swagger_ui_client:
    path: /api/doc/client
    methods: GET
    defaults:
        _controller: nelmio_api_doc.controller.swagger_ui
        area: client

app.swagger_ui_employe:
    path: /api/doc/employe
    methods: GET
    defaults:
        _controller: nelmio_api_doc.controller.swagger_ui
        area: employe

app.swagger_ui_admin:
    path: /api/doc/admin
    methods: GET
    defaults:
        _controller: nelmio_api_doc.controller.swagger_ui
        area: admin

# JSON pour chaque area (utile pour exporter)
app.swagger_public:
    path: /api/doc/public.json
    methods: GET
    defaults:
        _controller: nelmio_api_doc.controller.swagger
        area: public

app.swagger_client:
    path: /api/doc/client.json
    methods: GET
    defaults:
        _controller: nelmio_api_doc.controller.swagger
        area: client

app.swagger_employe:
    path: /api/doc/employe.json
    methods: GET
    defaults:
        _controller: nelmio_api_doc.controller.swagger
        area: employe

app.swagger_admin:
    path: /api/doc/admin.json
    methods: GET
    defaults:
        _controller: nelmio_api_doc.controller.swagger
        area: admin
```

Vider le cache :

```bash
php bin/console cache:clear
```

Ensuite, accéder à la documentation via https://127.0.0.1:8000/api/doc/public

---

# Partie 2 — Déploiement sur Heroku

### 1.1 Ajouter un fichier Procfile

```bash
echo "web: heroku-php-apache2 public/" > Procfile
```

### 1.2 Se placer sur la branche master

```bash
git checkout master
```

### 1.3 Renommer master en main

```bash
git branch -m master main   # on renomme master en main
```

### 1.4 Pousser la nouvelle branche main sur le remote

```bash
git push -u origin main
```

### 1.5 Supprimer l'ancienne branche master du remote

```bash
git push origin --delete master
```

### 1.6 Mettre à jour main avec les données présentes sur dev

```bash
git checkout main
git pull origin main
git status
git push origin main
```

### 1.7 Connexion à Heroku

```bash
heroku login
```

### 1.8 Initialiser le remote Heroku

```bash
heroku create vite-et-gourmand-api
git remote -v
heroku git:remote -a vite-et-gourmand-api
```

### 1.9 Vérifier que tous les fichiers sont commités

```bash
git add .
git commit -m "Préparer le backend pour Heroku"
```

### 1.10 Configurer le .env avec les données suivantes

```dotenv
###> symfony/framework-bundle ###
APP_ENV=prod
APP_SECRET=METTRE_UN_SECRET_DIFFERENT
APP_SHARE_DIR=var/share
###< symfony/framework-bundle ###

###> symfony/routing ###
DEFAULT_URI=https://vite-et-gourmand-api.herokuapp.com
###< symfony/routing ###

###> doctrine/doctrine-bundle ###
# MySQL ou autre DB relationnelle (si tu l'utilises)
DATABASE_URL="mysql://username:password@host:3306/dbname?serverVersion=8.0&charset=utf8mb4"
###< doctrine/doctrine-bundle ###

###> nelmio/cors-bundle ###
# Autoriser ton front Heroku à faire des requêtes
CORS_ALLOW_ORIGIN='^https://vite-et-gourmand-c36478b4c1b0.herokuapp.com$'
###< nelmio/cors-bundle ###

###> lexik/jwt-authentication-bundle ###
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=vite_et_gourmand_secret
###< lexik/jwt-authentication-bundle ###

###> symfony/mailer ###
MAILER_DSN="smtp://USERNAME:PASSWORD@smtp.your-email.com:PORT"
###< symfony/mailer ###

###> symfony/lock ###
LOCK_DSN=flock
###< symfony/lock ###

###> doctrine/mongodb-odm-bundle ###
# Utiliser ton cluster MongoDB Atlas

MONGODB_URI="mongodb+srv://vite_user:vite_pass@vite-et-gourmand.v51wxj4.mongodb.net/?appName=vite-et-gourmand"
MONGODB_DB=vite_et_gourmand
APP_URL=https://vite-et-gourmand-api.herokuapp.com
###< doctrine/mongodb-odm-bundle ###

# URL du front pour redirections CORS ou notifications
FRONT_URL=https://vite-et-gourmand-c36478b4c1b0.herokuapp.com
```

### 1.11 Configurer les variables de configuration Heroku

```bash
heroku config:set APP_ENV=prod -a vite-et-gourmand-api
heroku config:set APP_SECRET=3f4a8b2c1d9e7f6a5b4c3d2e1f0a9b8c -a vite-et-gourmand-api
heroku config:set APP_URL=https://vite-et-gourmand-api.herokuapp.com -a vite-et-gourmand-api
heroku config:set FRONT_URL=https://vite-et-gourmand-c36478b4c1b0.herokuapp.com -a vite-et-gourmand-api
```

MongoDB Atlas (remplacer USERNAME, PASSWORD et CLUSTER par ses informations) :

```bash
heroku config:set MONGODB_URI="mongodb+srv://vite_user:vite_pass@vite-et-gourmand.v51wxj4.mongodb.net/?appName=vite-et-gourmand" -a vite-et-gourmand-api
heroku config:set MONGODB_DB=vite_et_gourmand -a vite-et-gourmand-api
```

> **Mise à jour (octobre 2026) :** sur Heroku, l'adresse `mongodb+srv://` faisait échouer le build (résolution DNS impossible pendant le `cache:clear`). On utilise maintenant la **chaîne de connexion standard** qui liste directement les 3 serveurs du cluster. Elle se règle dans Heroku → Settings → Config Vars (ou avec `heroku --% config:set ...` dans PowerShell, pour que les `&` passent) :
>
> ```text
> MONGODB_URI=mongodb://vite_user:vite_pass@ac-rm3q8wf-shard-00-00.v51wxj4.mongodb.net:27017,ac-rm3q8wf-shard-00-01.v51wxj4.mongodb.net:27017,ac-rm3q8wf-shard-00-02.v51wxj4.mongodb.net:27017/vite-et-gourmand?ssl=true&replicaSet=atlas-pjagb4-shard-0&authSource=admin&retryWrites=true&w=majority&appName=vite-et-gourmand-log
> MONGODB_DB=vite-et-gourmand
> ```

Mailer (Mailtrap) :

```bash
heroku config:set MAILER_DSN="smtp://6836c3cc28f364:ed676f5a2fc337@sandbox.smtp.mailtrap.io:2525" -a vite-et-gourmand-api
```

### 1.12 Vérifier après avoir tout configuré

```bash
heroku config -a vite-et-gourmand-api
```

### 1.13 Créer une base de données MySQL avec Heroku

Pour voir la liste des bases disponibles : https://elements.heroku.com/addons#data-stores

Cliquer sur « Data stores ». J'ai choisi **JawsDB MySQL**.
Pourquoi : gratuit, et j'utilise déjà MySQL, je ne voulais pas passer à PostgreSQL.

Lancer depuis VS Code, avec le terminal dans le dossier `vite-et-gourmand-api` :

```bash
heroku addons:create jawsdb:kitefin
```

> Le forfait gratuit Kitefin est limité à 3 600 requêtes SQL par heure (voir la Partie 6).

### 1.14 Une fois installée, récupérer l'URL de connexion

```bash
heroku config:get JAWSDB_URL -a vite-et-gourmand-api
```

Résultat :

```text
mysql://z6kfic0nl9ubmba9:lgcy2tt6lhnbg7a8@l6slz5o3eduzatkw.cbetxkdyhwsb.us-east-1.rds.amazonaws.com:3306/utp2g4edmtrisl82
username: z6kfic0nl9ubmba9
password: lgcy2tt6lhnbg7a8
```

Ajouter la version à la fin : `?serverVersion=8.4.7&charset=utf8mb4`. L'URL devient :

```text
"mysql://z6kfic0nl9ubmba9:lgcy2tt6lhnbg7a8@l6slz5o3eduzatkw.cbetxkdyhwsb.us-east-1.rds.amazonaws.com:3306/utp2g4edmtrisl82?serverVersion=8.4.7&charset=utf8mb4"
```

### 1.15 Installation de Mailtrap avec Heroku

https://elements.heroku.com/addons/mailtrap

L'installer en appuyant sur « Install add-on » et en sélectionnant le nom de l'API, ou en ligne de commande :

```bash
heroku addons:create mailtrap:unpaid
```

### 1.16 Afficher la configuration de toutes les variables Heroku

```bash
heroku config -a vite-et-gourmand-api
```

### 1.17 Récupérer la valeur de Mailtrap

```bash
heroku config:get "MAILER_DSN" -a "vite-et-gourmand-api"
```

```text
smtp://6836c3cc28f364:ed676f5a2fc337@sandbox.smtp.mailtrap.io:2525
```

### 1.18 Installation de MongoDB Atlas

https://www.mongodb.com/products/platform/atlas-database

### 1.19 Créer son cluster avec le plan gratuit une fois le compte créé

Nom du cluster : `vite-et-gourmand`

Aller ensuite dans **Database & Network Access** et créer un nouvel utilisateur :

- Utilisateur : `vite_user`
- Description : `user`
- Mot de passe : `vite_pass`
- Rôle : **Read and write to any database**

Aller ensuite dans **IP Access List** → Add IP Address.

Pour les tests, entrer la valeur ci-dessous (à supprimer après, faille de sécurité). Elle autorise toutes les adresses IP :

```text
0.0.0.0/0
```

> Sur Heroku, les adresses IP changent à chaque redémarrage : `0.0.0.0/0` reste nécessaire tant qu'on ne passe pas par une IP fixe.

### 1.20 Ajout du .env.prod final sur Git avec les données plus haut

```bash
git add .env.prod
git commit -m "modification pour le déploiment de l'application sur Heroku "
```

### 1.21 Configurer le projet Symfony en prod

```bash
composer dump-env prod
```

### 1.22 Nettoyer et vérifier le cache pour l'environnement de prod

```bash
php bin/console cache:clear --env=prod
```

### 1.23 Tester le cache et la connexion

```bash
php bin/console cache:clear --env=prod
php bin/console doctrine:query:sql "SHOW TABLES;" --env=prod
```

### 1.24 Résoudre l'erreur de connexion

Normal à la première utilisation. On migre les données Symfony.

Obligatoire : ma version de MongoDB était trop récente, je dois revenir à une version antérieure :

```bash
composer require mongodb/mongodb:^2.1.1 -W
git add composer.json composer.lock
git commit -m "régression MongoDB version due à la version Heroku"
```

### 1.25 Mise à jour de l'autoloader Symfony

```bash
composer update mongodb/mongodb doctrine/mongodb-odm doctrine/mongodb-odm-bundle --with-all-dependencies
composer install --no-dev --optimize-autoloader
heroku config:set COMPOSER_MEMORY_LIMIT=-1
composer require symfony/apache-pack
php bin/console cache:clear
php bin/console cache:warmup
composer validate
composer install --no-dev
composer update
```

### 1.26 Remplir les Config Vars dans Heroku

| Variable | Valeur |
|---|---|
| `APP_ENV` | `prod` |
| `APP_SECRET` | votre secret |
| `JAWSDB_URL` | `mysql://z6kfic0nl9ubmba9:lgcy2tt6lhnbg7a8@l6slz5o3eduzatkw.cbetx` |
| `MAILER_DSN` | `smtp://6836c3cc28f364:ed676f5a2fc337@sandbox.smtp.mailtrap.io:2525` |
| `MAILTRAP_API_TOKEN` | `4ea729412eab9427db4805423616bb83` |
| `MONGODB_DB` | `vite-et-gourmand_log` |
| `MONGODB_URI` | `mongodb+srv://vite_user:vite_pass@vite-et-gourmand.v51wxj4.mongodb.net/?appName=vite-et-gourmand-log` |
| `COMPOSER_MEMORY_LIMIT` | `-1` |
| `DATABASE_URL` | `mysql://z6kfic0nl9ubmba9:lgcy2tt6lhnbg7a8@l6slz5o3eduzatkw.cbetxkdyhwsb.us-east-1.rds.amazonaws.com:3306/utp2g4edmtrisl82` |
| `CORS_ALLOW_ORIGIN` | `https://vite-et-gourmand-c36478b4c1b0.herokuapp.com` |
| `JWT_PASSPHRASE` | votre secret |
| `FRONT_URL` | `https://vite-et-gourmand-c36478b4c1b0.herokuapp.com` |
| `APP_URL` | `https://vite-et-gourmand-api-2b0eeb54e8d5.herokuapp.com/` |
| `JWT_SECRET_KEY` | valeur à récupérer dans le dépôt avec la commande `cat config/jwt/private.pem` |
| `JWT_PUBLIC_KEY` | valeur à récupérer dans le dépôt avec la commande `cat config/jwt/public.pem` |

> `MONGODB_URI` utilise maintenant la chaîne de connexion standard (voir l'encadré de la section 1.11).

### 1.27 Création du fichier apache_app.conf pour la redirection Apache

À la racine du projet, créer un fichier nommé `apache_app.conf`. Il force Apache à passer par `index.php` pour toutes les routes. Mettre dedans :

```apache
<Directory "/app/public">
    AllowOverride All
    Options -Indexes +FollowSymLinks
    Require all granted

    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.php [QSA,L]
</Directory>
```

### 1.28 Modification de config/routes/api_platform.yaml

J'ai volontairement ajouté les lignes ci-dessous pour tester. À enlever dans un vrai environnement de production (elles montrent toutes les routes, ce n'est pas sécurisé).

```yaml
api_platform:
    resource: .
    type: api_platform
    prefix: /api
    enable_swagger_ui: true
    enable_re_doc: true
```

### 1.29 Vérification des fichiers avant de redéployer sur Heroku

```bash
composer dump-autoload --optimize
```

1. Vidange du cache :

```powershell
php bin/console cache:clear --env=prod
$env:APP_ENV="prod"; php bin/console cache:clear --env=prod
```

2. Vérification de la relecture des variables d'environnement :

```bash
php bin/console debug:dotenv
```

3. Vérification des variables spécifiques :

```bash
php bin/console debug:container --env-vars
```

4. Vérification de la connexion Doctrine pour MySQL :

```bash
php bin/console doctrine:schema:validate
```

5. Vérification que les routes sont fonctionnelles et correctement chargées :

```bash
php bin/console debug:router
```

6. Vérification de la configuration CORS :

```bash
php bin/console debug:config nelmio_cors
```

7. Vérification de la configuration JWT :

```bash
php bin/console debug:config lexik_jwt_authentication
```

8. Dernière vérification, compilation sans erreur :

```bash
composer install --no-dev --optimize-autoloader
```

### 1.30 Pousser vers Heroku si la 1.29 fonctionne

```bash
git push heroku main
```

### 1.31 Lancement et test de l'API

```bash
heroku open
```

### 1.32 Test de l'API et de Swagger

- Si https://vite-et-gourmand-api-2b0eeb54e8d5.herokuapp.com/ s'ouvre et affiche « Welcome to Symfony 7 », l'API fonctionne.
- Si https://vite-et-gourmand-api-2b0eeb54e8d5.herokuapp.com/api/doc s'ouvre et affiche les routes, Swagger fonctionne.

---

# Partie 3 — Base de données distante JawsDB

## 1. Mise en place des données dans la nouvelle base JawsDB

### 1.1 Supprimer l'ancienne migration et la recréer pour repartir propre

```powershell
Remove-Item migrations\*.php
ls migrations/   # est-il vide ? Si oui, on continue
$env:APP_ENV="dev"; php bin/console make:migration
```

### 1.2 Créer la base de données JawsDB et récupérer les informations de connexion

```bash
heroku config:get JAWSDB_URL --app vite-et-gourmand-api
```

Résultat si tout fonctionne :

```text
mysql://z6kfic0nl9ubmba9:lgcy2tt6lhnbg7a8@l6slz5o3eduzatkw.cbetxkdyhwsb.us-east-1.rds.amazonaws.com:3306/utp2g4edmtrisl82
```

Supprimer celle existante si ça ne marche pas :

```bash
heroku run --app vite-et-gourmand-api -- php bin/console doctrine:schema:drop --force
```

Vérifier que les migrations sont exécutées :

```bash
heroku run --app vite-et-gourmand-api -- php bin/console doctrine:migrations:version --add --all
```

Vérifier les tables :

```bash
heroku run --app vite-et-gourmand-api -- php bin/console dbal:run-sql "SHOW TABLES;"
```

Remettre à jour la base après une suppression :

```bash
heroku run --app vite-et-gourmand-api -- php bin/console doctrine:schema:update --force
heroku run --app vite-et-gourmand-api -- php bin/console doctrine:migrations:version --add --all
```

Recréer les tables :

```bash
heroku run --app vite-et-gourmand-api -- php bin/console doctrine:schema:create
```

## 2. Si ça bug toujours : recréer les tables avec un dump SQL

### 2.1 Générer le SQL de toutes les tables depuis Symfony en local

```bash
php bin/console doctrine:schema:update --dump-sql > dump.sql
```

Mettre à jour la base :

```bash
heroku run php bin/console doctrine:migrations:migrate  --app vite-et-gourmand-api
heroku run php bin/console doctrine:schema:create --app vite-et-gourmand-api
heroku run php bin/console doctrine:migrations:status --app vite-et-gourmand-api
```

```powershell
cmd /c ""D:\wamp64\bin\mysql\mysql8.4.7\bin\mysql.exe" -h rtzsaka6vivj2zp1.cbetxkdyhwsb.us-east-1.rds.amazonaws.com -P 3306 -u t5f5ela0eyr3yxrf -pjuemmdoy7baw5xkg mh5niaxni11vqgfn < D:\wamp64\www\vite-et-gourmand-back\docs\creation_database.sql"
```

```bash
heroku run php bin/console doctrine:schema:validate -a vite-et-gourmand-api
```

### 2.2 Recréer une base JawsDB

```bash
heroku addons:create jawsdb:kitefin --app vite-et-gourmand-api
```

### 2.3 Nouvelle base

```text
JAWSDB_YELLOW_URL:     mysql://ue3gbfbhdm68zc42:zljkqdfbr7l21x5j@muowdopceqgxjn2b3.cbetxkdyhwsb.us-east-1.rds.amazonaws.com:3306/y1gtp5y6q1qq2atq
```

### 2.4 Remettre à jour la base

```bash
heroku config:set DATABASE_URL="mysql://t5f5ela0eyr3yxrf:juemmdoy7baw5xkg@rtzsaka6vivj2zp1.cbetxkdyhwsb.us-east-1.rds.amazonaws.com:3306/mh5niaxni11vqgfn" --app vite-et-gourmand-api
```

### 2.5 Définir comme base principale

```bash
heroku config:set DATABASE_URL="$(heroku config:get JAWSDB_YELLOW_URL -a vite-et-gourmand-api)" --app vite-et-gourmand-api
```

### 2.6 Nettoyer le reste (important)

```bash
heroku config:unset JAWSDB_URL --app vite-et-gourmand-api
heroku config:unset JAWSDB_COBALT_URL --app vite-et-gourmand-api
```

### 2.7 Vérifier qu'il n'y a qu'une seule base

```bash
heroku config --app vite-et-gourmand-api
```

Marquer les migrations comme exécutées :

```bash
php bin/console doctrine:migrations:version --add --all
```

### 2.8 Se connecter à JawsDB depuis Heroku et vider la base

```bash
mysql -h rtzsaka6vivj2zp1.cbetxkdyhwsb.us-east-1.rds.amazonaws.com -P 3306 -u t5f5ela0eyr3yxrf -p mh5niaxni11vqgfn
```

Mot de passe : `juemmdoy7baw5xkg`

### 2.9 Supprimer toutes les tables (ATTENTION, À NE PAS FAIRE)

```sql
DROP TABLE IF EXISTS avis, menu, plat, commande, utilisateur, contient, propose, allergene, regime, role, theme, menu_tags, suivi_commande, password_reset_token, horaire;
```

### 2.10 Charger les tables depuis le dump SQL

```powershell
Get-Content .\dump.sql | mysql -h rtzsaka6vivj2zp1.cbetxkdyhwsb.us-east-1.rds.amazonaws.com -P 3306 -u t5f5ela0eyr3yxrf -p mh5niaxni11vqgfn
```

Mot de passe : `juemmdoy7baw5xkg`

### 2.11 Synchroniser Doctrine Migrations (pour que Symfony ne pense pas que certaines migrations n'ont pas été exécutées)

```bash
heroku run --app vite-et-gourmand-api -- php bin/console doctrine:migrations:version --add --all
```

## 3. Vérifier que les tables existent

```bash
mysql -h rtzsaka6vivj2zp1.cbetxkdyhwsb.us-east-1.rds.amazonaws.com -P 3306 -u t5f5ela0eyr3yxrf -p mh5niaxni11vqgfn
```

Mot de passe : `juemmdoy7baw5xkg`

Pour voir les tables, taper :

```sql
SHOW TABLES;
```

Pour sortir : `exit` ou Ctrl + C.

| Paramètre | Valeur |
|---|---|
| USER | `ue3gbfbhdm68zc42` |
| PASSWORD | `zljkqdfbr7l21x5j` |
| HOST | `muowdopceqgxjn2b3.cbetxkdyhwsb.us-east-1.rds.amazonaws.com` |
| DB | `y1gtp5y6q1qq2atq` |

### 3.1 Afficher la base créée par JawsDB

```bash
heroku run --app vite-et-gourmand-api -- php bin/console dbal:run-sql "SHOW TABLES;"
```

Ou en SQL, c'est mieux !

### 3.2 Voir comment est composée une table (clés, index, types, colonnes)

```sql
SHOW CREATE TABLE user;
```

```text
mysql> SHOW TABLES;
+----------------------------+
| Tables_in_y1gtp5y6q1qq2atq |
+----------------------------+
| allergene                  |
| avis                       |
| commande                   |
| contient                   |
| horaire                    |
| menu                       |
| menu_tags                  |
| password_reset_token       |
| plat                       |
| propose                    |
| regime                     |
| role                       |
| suivi_commande             |
| theme                      |
| utilisateur                |
+----------------------------+
```

### 3.3 Vérifier le contenu de chaque table

```sql
DESCRIBE allergene;
```

```text
mysql> DESCRIBE allergene;
+--------------+-------------+------+-----+---------+----------------+
| Field        | Type        | Null | Key | Default | Extra          |
+--------------+-------------+------+-----+---------+----------------+
| allergene_id | int         | NO   | PRI | NULL    | auto_increment |
| libelle      | varchar(50) | NO   |     | NULL    |                |
+--------------+-------------+------+-----+---------+----------------+
2 rows in set (0.10 sec)
```

### 3.4 Mettre à jour les données de la base d'après le fichier creation_database.sql

> **IMPORTANT :** vérifier toutes les tables avant d'utiliser cette commande.

Sur Windows :

```powershell
cmd /c ""D:\wamp64\bin\mysql\mysql8.4.7\bin\mysql.exe" -h rtzsaka6vivj2zp1.cbetxkdyhwsb.us-east-1.rds.amazonaws.com -P 3306 -u t5f5ela0eyr3yxrf -pjuemmdoy7baw5xkg mh5niaxni11vqgfn < D:\wamp64\www\vite-et-gourmand-back\docs\creation_database.sql"
```

Ou, si on est déjà connecté à MySQL :

```sql
SOURCE docs/creation_database.sql;
```

## 4. Voir les données d'une base de données distante

### 4.1 Se connecter à la base

```bash
mysql -h rtzsaka6vivj2zp1.cbetxkdyhwsb.us-east-1.rds.amazonaws.com -P 3306 -u t5f5ela0eyr3yxrf -p mh5niaxni11vqgfn
```

### 4.2 Voir les éléments d'une table

```sql
SELECT * FROM utilisateur;
```

### 4.3 Supprimer plusieurs éléments d'un coup

```sql
DELETE FROM utilisateur
WHERE utilisateur_id IN (4 , 5, 6, 7,8,9,10,11,12,13,14,15,16,17,18,19);
```

### 4.4 Afficher les contraintes liées à une table donnée

```sql
SELECT
TABLE_NAME,
COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE REFERENCED_TABLE_NAME = 'utilisateur';
```

### 4.5 Trouver les utilisateurs qui n'ont pas de lien avec commande, avis et password_reset_token

```sql
SELECT *
FROM utilisateur
WHERE utilisateur_id NOT IN (
    SELECT utilisateur_id FROM password_reset_token
)
AND utilisateur_id NOT IN (
    SELECT utilisateur_id FROM avis
)
AND utilisateur_id NOT IN (
    SELECT utilisateur_id FROM commande
);
```

### 4.6 Supprimer les utilisateurs qui n'ont pas de lien, sauf certains id

```sql
DELETE FROM utilisateur
WHERE utilisateur_id NOT IN (
    SELECT utilisateur_id FROM password_reset_token
)
AND utilisateur_id NOT IN (
    SELECT utilisateur_id FROM avis
)
AND utilisateur_id NOT IN (
    SELECT utilisateur_id FROM commande
)
AND utilisateur_id NOT IN (1, 2, 12, 23);
```

### 4.7 Modifier le mot de passe d'un utilisateur

```sql
UPDATE utilisateur
SET password = '$2y$13$wjlZHiTr40IOymvkXskeCeZ.3hJGVM2acU3lsL9fbniwGo4GAZNX.';
```

> Attention : sans `WHERE`, cette requête modifie le mot de passe de **tous** les utilisateurs.

### 4.8 Cibler les utilisateurs qui ont des commandes

```sql
SELECT u.*
FROM utilisateur u
INNER JOIN commande c ON u.utilisateur_id = c.utilisateur_id
GROUP BY u.utilisateur_id;
```

### 4.9 Réinitialiser l'auto-increment (seulement si la table est vide)

```sql
ALTER TABLE utilisateur AUTO_INCREMENT = 1;
```

### 4.10 Vérifier si les plats ont une description

```sql
SELECT plat_id, titre_plat, description_plat
FROM plat
```

### 4.11 Ajouter une colonne à une table

```sql
ALTER TABLE plat
ADD COLUMN description_plat VARCHAR(250);
```

### 4.12 Vérifier la mise à jour d'une table

```sql
SELECT plat_id, titre_plat, description_plat FROM plat LIMIT 10;
```

### 4.13 Mettre à jour une colonne d'une ligne ciblée par son id

```sql
UPDATE plat SET description_plat = 'Magret de canard rôti accompagné de cèpes.' WHERE plat_id = 2;
```

---

# Partie 4 — Données, JWT, logs et mails en production

## 1. Importation des données dans la base

J'ai créé un fichier dans `docs` pour remplir la base de données : `docs/creation_database.sql`

### 1.1 Vérifier que le fichier est bien présent

```powershell
dir D:\wamp64\www\vite-et-gourmand-back\docs\*.sql
```

### 1.2 Lancer l'insertion des données dans la base

```powershell
cd D:\wamp64\www\vite-et-gourmand-back\docs
cmd /c "D:\wamp64\bin\mysql\mysql8.4.7\bin\mysql.exe -h l6slz5o3eduzatkw.cbetxkdyhwsb.us-east-1.rds.amazonaws.com -u z6kfic0nl9ubmba9 -plgcy2tt6lhnbg7a8 utp2g4edmtrisl82 < creation_database.sql"
```

### 1.3 Vérification des données via l'appel des routes

- https://vite-et-gourmand-api-2b0eeb54e8d5.herokuapp.com/api/menus
- https://vite-et-gourmand-api-2b0eeb54e8d5.herokuapp.com/api/horaires
- https://vite-et-gourmand-api-2b0eeb54e8d5.herokuapp.com/api/plats
- https://vite-et-gourmand-api-2b0eeb54e8d5.herokuapp.com/api/themes
- https://vite-et-gourmand-api-2b0eeb54e8d5.herokuapp.com/api/regimes
- https://vite-et-gourmand-api-2b0eeb54e8d5.herokuapp.com/api/allergenes
- https://vite-et-gourmand-api-2b0eeb54e8d5.herokuapp.com/api/avis

## 2. Résolution du problème JWT (JWTEncodeFailureException)

Exception : `Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTEncodeFailureException`

```bash
heroku config:set JWT_PASSPHRASE=vite_et_gourmand_secret --app vite-et-gourmand-api
heroku run --app vite-et-gourmand-api -- ls config/jwt
```

## 3. Afficher les logs

```bash
heroku logs --num 100 --app vite-et-gourmand-api
heroku logs --tail --app vite-et-gourmand-api
```

## 4. Changement de boîte mail : SendPit

| Paramètre | Valeur |
|---|---|
| MAIL_HOST | `smtp.sendpit.com` |
| MAIL_PORT | `2525` |
| MAIL_USERNAME | `mb_ab6pbmevd9l9` |
| MAIL_PASSWORD | `BXkhhaUjSLwXtVnE` |
| TLS | actif (`encryption=tls`) |

Modification du `.env` :

```dotenv
MAILER_DSN=smtp://mb_ab6pbmevd9l9:BXkhhaUjSLwXtVnE@smtp.sendpit.com:2525?encryption=tls&auth_mode=login
```

Mise à jour de Heroku :

```bash
heroku config:set "MAILER_DSN=smtp://mb_ab6pbmevd9l9:BXkhhaUjSLwXtVnE@smtp.sendpit.com:2525?encryption=tls&auth_mode=login" --app vite-et-gourmand-api
heroku config:set APP_URL="https://vite-et-gourmand-api-2b0eeb54e8d5.herokuapp.com" --app vite-et-gourmand-api
```

Création du contrôleur de test pour l'envoi d'un mail : `TestEmailController`

Installation du mailer :

```bash
composer require symfony/mailer
```

Vérifier la configuration du mail.

---

# Partie 5 — Cloudinary et Docker

## 1. Mise en place de Cloudinary pour la gestion des images

https://console.cloudinary.com/app/c-d5bfca4e46946f1728ea3eff5264e4/settings/upload/presets

### 1.1 Création du service CloudinaryService.php

### 1.2 Installation des dépendances Cloudinary

```bash
composer require cloudinary/cloudinary_php
```

### 1.3 Mise à jour de config/services.yaml

```yaml
services:
    App\Service\CloudinaryService:
        arguments:
            $cloudinaryUrl: '%env(CLOUDINARY_URL)%'
```

### 1.4 Mise à jour du .env

```dotenv
CLOUDINARY_URL=cloudinary://API_KEY:API_SECRET@CLOUD_NAME
CLOUDINARY_URL=cloudinary://353472624727459:5qxlnkO3rPAvHg@drdup0seu
```

### 1.5 Mise à jour des Config Vars Heroku

```bash
heroku config:set CLOUDINARY_URL=cloudinary://353472624727459:5qxlnkO3rPAvHg4MLIm6M_thFfk@drdup0seu --app vite-et-gourmand-api
heroku restart --app vite-et-gourmand-api
```

## 2. Docker Compose (environnement local)

Le fichier `compose.dev.yml` lance le back, MySQL 8.4, le front et phpMyAdmin.

### 2.1 Supprimer l'image corrompue et la retélécharger proprement

```bash
docker compose -f compose.dev.yml down -v
docker rmi mysql:8.4
docker compose -f compose.dev.yml up --build
```

### 2.2 Repartir d'un volume propre

```bash
docker compose -f compose.dev.yml down -v
docker compose -f compose.dev.yml up --build
```

### 2.3 Génération de la base de données par la migration

```bash
docker compose -f compose.dev.yml exec backend php bin/console make:migration
docker compose -f compose.dev.yml exec backend php bin/console doctrine:migrations:migrate --no-interaction
```

### 2.4 Génération du schéma directement, sans migrations

```bash
docker compose -f compose.dev.yml exec backend php bin/console doctrine:schema:drop --force
docker compose -f compose.dev.yml exec backend php bin/console doctrine:schema:create
```

### 2.5 Redémarrer

```bash
docker compose -f compose.dev.yml restart backend
docker compose -f compose.dev.yml logs backend
```

### 2.6 Vider le cache

```bash
docker compose -f compose.dev.yml exec backend php bin/console cache:clear
docker compose -f compose.dev.yml restart backend
```

---

# Partie 6 — Optimisation des requêtes SQL

## Pourquoi

Le forfait gratuit JawsDB (Kitefin) est limité à **3 600 requêtes SQL par heure**. Au-delà, toutes les routes renvoient une erreur 500 :

```text
SQLSTATE[42000]: Syntax error or access violation: 1226 User '...' has exceeded the 'max_questions' resource (current value: 3600)
```

Le compteur se remet à zéro une heure après le dépassement. Pour ne pas payer un forfait supérieur, l'API a été optimisée pour faire beaucoup moins de requêtes.

## Le problème : les requêtes « N+1 »

Avec Doctrine, lire `$menu->getPlats()` ou `$commande->getUtilisateur()` déclenche une nouvelle requête SQL à chaque appel. Une liste de 10 menus de 3 plats générait ainsi près de 60 requêtes.

## La solution

- **Jointures (`leftJoin` + `addSelect`)** dans les repositories : le menu, son thème, son régime, ses plats, leurs allergènes et ses tags sont chargés en une seule requête.
  - `MenuRepository::findAllAvecDetails()` et `findAvecDetails($id)`
  - `PlatRepository::findAllAvecAllergenes()`
  - `CommandeRepository::findAllAvecRelations()` (client + menu)
  - `AvisRepository::findAvecRelations()` et `findParCommandePourUtilisateur()`
- **Suivi des commandes inclus dans les listes** : `SuiviCommandeRepository::findFormatesParCommandes()` charge le suivi de toutes les commandes en une requête. Les routes `GET /api/commandes` et `GET /api/client/commandes` renvoient un champ `suivis`, et le front n'appelle plus `/suivi` pour chaque commande.
- **Rôle chargé avec l'utilisateur** : `fetch: 'EAGER'` sur `Utilisateur::$role` évite une requête à chaque appel authentifié.
- **Statistiques** : les compteurs d'utilisateurs et d'avis passent par un `GROUP BY` au lieu de charger toutes les lignes.

## Résultat (mesuré sur une base de test : 10 menus, 30 plats, 30 commandes, 20 avis)

| Route | Avant | Après |
|---|---|---|
| `GET /api/menus/full` | 59 | 1 |
| `GET /api/menus/{id}` | 8 | 1 |
| `GET /api/plats` | 31 | 1 |
| `GET /api/commandes` | 29 | 5 |
| `GET /api/client/commandes` | 8 | 5 |
| `GET /api/employe/avis` | 39 | 4 |
| `GET /api/admin/statistiques` | 25 | 8 |
| `GET /api/me` (et toute route authentifiée) | 3 | 2 |

La page de gestion des commandes passe d'environ 180 requêtes à 7 par affichage.

Bug corrigé au passage : le total « Avis validés » des statistiques comptait le statut `validé`, qui n'existe pas. Un avis approuvé passe au statut `publié`.
