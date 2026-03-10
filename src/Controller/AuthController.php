<?php
namespace App\Controller;

use App\Entity\Utilisateur;
use App\Entity\PasswordResetToken;
use App\Repository\RoleRepository;
use App\Repository\UtilisateurRepository;
use App\Repository\PasswordResetTokenRepository;
use App\Service\MailerService;
use App\Service\LogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

/**
 * @author      Florian Aizac
 * @created     24/02/2026
 * @description Contrôleur gérant l'authentification 
 * utilisation : tous les rôle sont concerné.
 *  1. login                  : Log tous les utilisateurs
 *  2. register               : Inscription de tous les utilisateurs + envoie mail de bienvenue
 *  3. forgotPassword         : Demande de réinitialisation de mot de passe envoie lien par email
 *  4. resetPassword          : Réinitialise le mot de passe avec le token reçu par email
 */
#[Route('/api')]
final class AuthController extends AbstractController
{
  // Fonction qui log tous les utilisateurs
  #[Route('/login', name: 'api_login', methods: ['POST'])]
  public function login(): JsonResponse
  {
      // Symfony gère le login automatiquement via json_login
      // Cette méthode ne sera jamais appelée directement
      // Le log de connexion est géré par SecuritySubscriber lié à événement LoginSuccessEvent
      throw new \Exception('Ne devrait pas être appelé directement');
  }

  #[Route('/register', name: 'api_register', methods: ['POST'])]
  #[OA\Post(
      summary: 'Inscription d\'un nouveau client',
      description: 'Crée un nouveau compte client avec validation des données, protection honeypot, et envoi d\'un email de bienvenue. Le rôle ROLE_CLIENT est attribué par défaut.'
  )]
  #[OA\Tag(name: 'Authentification')]
  #[OA\RequestBody(
      required: true,
      content: new OA\JsonContent(
          properties: [
              new OA\Property(property: 'nom', type: 'string', example: 'Dupont'),
              new OA\Property(property: 'prenom', type: 'string', example: 'Marie'),
              new OA\Property(property: 'telephone', type: 'string', example: '0612345678'),
              new OA\Property(property: 'email', type: 'string', example: 'marie.dupont@email.com'),
              new OA\Property(property: 'password', type: 'string', example: 'MonMotDePasse1!'),
              new OA\Property(property: 'pays', type: 'string', example: 'France'),
              new OA\Property(property: 'ville', type: 'string', example: 'Bordeaux'),
              new OA\Property(property: 'code_postal', type: 'string', example: '33000'),
              new OA\Property(property: 'adresse_postale', type: 'string', example: '12 rue des Roses'),
              new OA\Property(property: 'site_web', type: 'string', example: '', description: 'Champ honeypot anti-bot, doit rester vide'),
          ]
      )
  )]
  #[OA\Response(response: 201, description: 'Compte créé avec succès')]
  #[OA\Response(response: 400, description: 'Données manquantes, email invalide, téléphone invalide ou mot de passe trop faible')]
  #[OA\Response(response: 409, description: 'Email déjà utilisé')]
  public function register(
      Request $request,                            // la requête HTTP entrante (contenant les données JSON du client)
      UserPasswordHasherInterface $passwordHasher, // service qui hashe les mots de passe
      EntityManagerInterface $em,                  // service qui communique avec la BDD
      UtilisateurRepository $utilisateurRepository,// service qui fait les SELECT sur utilisateur
      RoleRepository $roleRepository,              // service qui fait les SELECT sur role
      MailerService $mailerService,                // service qui envoie les emails pour le mail de bienvenue
      LogService $logService                       // service de logs MongoDB
  ): JsonResponse
  {
      // Étape 1 - Récupère les données JSON envoyées par le client
      $data = json_decode($request->getContent(), true);
      //dump($data); // version du printf affiche le tableau $data

      // Étape 2 - Vérifie que les champs obligatoires sont présents
      if (empty($data['nom']) || empty($data['prenom']) || empty($data['telephone']) ||
          empty($data['email']) || empty($data['password']) || empty($data['pays']) ||
          empty($data['ville']) || empty($data['code_postal']) || empty($data['adresse_postale'])) {
          return $this->json(['status' => 'Erreur', 'message' => 'Toutes les données doivent etre remplis'], 400);
      }

      // Étape 3 - Honeypot : si le champ site_web est rempli c'est un bot
      // On retourne un faux succès pour tromper le bot sans traiter la demande
      if (!empty($data['site_web'])) {
          return $this->json(['status' => 'Succès', 'message' => 'Compte créé avec succès'], 201);
      }

      // Étape 4 - Validation de l'email (.com et .fr uniquement)
      if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL) || 
          !preg_match('/\.(com|fr)$/i', $data['email'])) {
          return $this->json(['status' => 'Erreur', 'message' => 'Email invalide (extensions .com et .fr uniquement)'], 400);
      }

      // Étape 5 - Validation du téléphone (format français 06 ou 07)
      if (!preg_match('/^(\+33|0)(6|7)[0-9]{8}$/', $data['telephone'])) {
          return $this->json(['status' => 'Erreur', 'message' => 'Téléphone invalide (format 06 ou 07 requis)'], 400);
      }

      // Étape 6 - Validation du mot de passe
      // Règles : 10 caractères minimum, 1 majuscule, 1 minuscule, 1 chiffre, 1 caractère spécial
      if (strlen($data['password']) < 10 ||
          !preg_match('/[A-Z]/', $data['password']) ||
          !preg_match('/[a-z]/', $data['password']) ||
          !preg_match('/[0-9]/', $data['password']) ||
          !preg_match('/[\W_]/', $data['password'])) {
          return $this->json([
              'status'  => 'Erreur',
              'message' => 'Mot de passe invalide : 10 caractères minimum, 1 majuscule, 1 minuscule, 1 chiffre, 1 caractère spécial'
          ], 400);
      }
      

      // Étape 7 - Vérifie que l'email n'existe pas déjà en base de données
      // équivalent de SELECT * FROM utilisateur WHERE email = :email
      if ($utilisateurRepository->findOneBy(['email' => $data['email']])) {
          return $this->json(['status' => 'Erreur', 'message' => 'Cet email est déjà utilisé'], 409);
      }

      // Étape 8 - Récupère le rôle ROLE_CLIENT par défaut
      // équivalent de SELECT * FROM role WHERE libelle = 'ROLE_CLIENT'
      $role = $roleRepository->findOneBy(['libelle' => 'ROLE_CLIENT']);

      // Étape 9 - Création et remplissage d'un objet nouvel utilisateur
      $utilisateur = new Utilisateur();

      // Rappel de la notion : value ?? "" signifie "si value existe et n'est pas null, 
      // alors prends sa valeur, sinon prends une chaîne vide"

      $utilisateur->setNom($data['nom']);
      $utilisateur->setPrenom($data['prenom']);
      $utilisateur->setTelephone($data['telephone'] ?? ''); // si le champ telephone n'est pas présent dans les données, on met une chaîne vide par défaut
      $utilisateur->setEmail($data['email']);      
      $utilisateur->setPays($data['pays'] ?? '');
      $utilisateur->setVille($data['ville'] ?? '');
      $utilisateur->setAdressePostale($data['adresse_postale'] ?? '');
      $utilisateur->setCodePostal($data['code_postal'] ?? '');
      $utilisateur->setStatutCompte('actif');     
      $utilisateur->setRole($role);

      // Étape 10 - Hash le mot de passe avant de le stocker en base
      // On ne stocke JAMAIS un mot de passe en clair
      $motDePasseHashe = $passwordHasher->hashPassword($utilisateur, $data['password']);
      $utilisateur->setPassword($motDePasseHashe);

      // Étape 11 - Sauvegarde en base de données
      // persist() prépare l'insertion
      $em->persist($utilisateur);
      // flush() exécute réellement la requête SQL INSERT
      $em->flush();

      // Étape 12 - Envois un mail de bienvenu de manière automatique
      // Le mail contient un message de bienvenue personnalisé avec le prénom du nouvel utilisateur
      $mailerService->sendWelcomeEmail($utilisateur);

      // Étape 13 - Enregistrer le log d'inscription dans MongoDB
      // Pourquoi MongoDB et pas MySQL ? -> Les logs sont des données volumineuses sans schéma fixe,
      // Pas de relations nécessaires, optimisé pour l'écriture rapide -> cas d'usage NoSQL idéal
      $logService->log(
          'inscription',                          // type de l'action
          $utilisateur->getEmail(),              // email de l'utilisateur concerné
          'ROLE_CLIENT',                          // rôle attribué par défaut à l'inscription
          [                                   // contexte libre : données spécifiques à ce type de log
              'nom'    => $utilisateur->getNom(),
              'prenom' => $utilisateur->getPrenom(),
              'ville'  => $utilisateur->getVille(),
          ]
      );

      // Étape 14 - Retourne une réponse de succès avec le code 201
      return $this->json([
          'status'  => 'Succès',
          'message' => 'Compte créé avec succès',
      ], 201);
  }

  #[Route('/forgot-password', name: 'api_forgot_passwordmail', methods: ['POST'])]
  #[OA\Post(
      summary: 'Demande de réinitialisation de mot de passe',
      description: 'Envoie un lien de réinitialisation par email. Retourne toujours un succès par sécurité (même si l\'email n\'existe pas).'
  )]
  #[OA\Tag(name: 'Authentification')]
  #[OA\RequestBody(
      required: true,
      content: new OA\JsonContent(
          properties: [
              new OA\Property(property: 'email', type: 'string', example: 'marie.dupont@email.com'),
          ]
      )
  )]
  #[OA\Response(response: 200, description: 'Lien de réinitialisation envoyé (message générique par sécurité)')]
  #[OA\Response(response: 400, description: 'Email requis')]
  public function forgotPasswordmail(
      Request $request,
      UtilisateurRepository $utilisateurRepository,
      PasswordResetTokenRepository $tokenRepository,
      EntityManagerInterface $em,
      MailerService $mailerService
  ): JsonResponse {
      // Étape 1 - Récupérer et valider l'email
      $data = json_decode($request->getContent(), true);
      if (empty($data['email'])) {
          return $this->json(['status' => 'Erreur', 'message' => 'Email requis'], 400);
      }

      // Étape 2 - Vérifier que l'utilisateur existe
      $utilisateur = $utilisateurRepository->findOneBy(['email' => $data['email']]);

      // Étape 3 - Message générique si l'utilisateur n'existe pas (sécurité)
      if (!$utilisateur) {
          return $this->json([
              'status'  => 'Succès',
              'message' => 'Si cet email existe dans notre système, un lien de réinitialisation a été envoyé'
          ], 200);
      }

      // Étape 4 - Nettoyer les anciens tokens expirés ou utilisés de cet utilisateur
      // Evite l'accumulation de tokens morts en base de données
      $tokenRepository->deleteOldTokens($utilisateur);

      // Étape 5 - Générer un token sécurisé
      $token = bin2hex(random_bytes(32));

      // Étape 6 - Créer l'entité PasswordResetToken
      $resetToken = new PasswordResetToken();
      $resetToken->setToken($token);
      $resetToken->setUtilisateur($utilisateur);
      $resetToken->setCreatedAt(new \DateTime());
      // Le token expire dans 4 heures
      $resetToken->setExpiresAt((new \DateTime())->modify('+4 hours'));
      $resetToken->setIsUsed(false);

      // Étape 7 - Sauvegarder le token
      $em->persist($resetToken);
      $em->flush();

      // Étape 7 - Construire le lien de réinitialisation
      // Ce lien sera cliqué par l'utilisateur pour se rendre sur la page de réinitialisation
      // #RAPPEL PROD
      // IMPORTANT NE PAS OUBLIER DE MODIFIER : En production, utiliser le domaine réel (vite-et-gourmand.fr)
      $resetLink = "http://localhost:3000/reset-password?token=" . $token;

      // Étape 8 - Envoyer l'email contenant le lien
      $mailerService->sendPasswordResetEmail($utilisateur, $resetLink);

      // Étape 9 - Retourner un succès au client
      // Message générique pour des raisons de sécurité
      return $this->json([
          'status'  => 'Succès',
          'message' => 'Si cet email existe dans notre système, un lien de réinitialisation a été envoyé'
      ], 200);
  }

  /**
   * @description Valide le token et réinitialise le mot de passe
   * 
   * Algo:
   * 1. L'utilisateur clique sur le lien envoyé par l'email contenant le token.
   * 2. La page front affiche un formulaire de réinitialisation
   * 3. L'utilisateur entre son nouveau mot de passe
   * 4. On valide le token non expiré, non utilisé
   * 5. On hashe et sauvegarde le nouveau mot de passe
   * 6. On marque le token comme utilisé afin de gérer le cas impossible de le réutiliser
   * 7. L'utilisateur peut se connecter avec son nouveau mot de passe
   * 
   * @param Request $request La requête contenant le token et le nouveau mot de passe
   * @param PasswordResetTokenRepository $tokenRepository Pour récupérer et valider le token
   * @param UserPasswordHasherInterface $passwordHasher Pour hasher le nouveau mot de passe
   * @param EntityManagerInterface $em Pour sauvegarder en base
   * @return JsonResponse
   */
  #[Route('/reset-password', name: 'api_reset_password', methods: ['POST'])]
  #[OA\Post(
      summary: 'Réinitialiser le mot de passe',
      description: 'Valide le token reçu par email et enregistre le nouveau mot de passe. Le token est marqué comme utilisé après usage.'
  )]
  #[OA\Tag(name: 'Authentification')]
  #[OA\RequestBody(
      required: true,
      content: new OA\JsonContent(
          properties: [
              new OA\Property(property: 'token', type: 'string', example: 'a1b2c3d4e5f6...'),
              new OA\Property(property: 'password', type: 'string', example: 'NouveauMotDePasse1!'),
          ]
      )
  )]
  #[OA\Response(response: 200, description: 'Mot de passe réinitialisé avec succès')]
  #[OA\Response(response: 400, description: 'Token et mot de passe requis, mot de passe invalide, ou token expiré/invalide')]
  public function resetPassword(
      Request $request,
      PasswordResetTokenRepository $tokenRepository,
      UserPasswordHasherInterface $passwordHasher,
      EntityManagerInterface $em
  ): JsonResponse
  {
      // Étape 1 - Récupérer les données du formulaire
      $data = json_decode($request->getContent(), true);

      if (empty($data['token']) || empty($data['password'])) {
          return $this->json(['status' => 'Erreur', 'message' => 'Token et mot de passe requis'], 400);
      }

      // Étape 2 - Valider le nouveau mot de passe 
      // Même validation que lors de l'inscription
      // Penser à faire une fonction reggex plus tard
      if (strlen($data['password']) < 10  ||                     // 10 caractères min
          !preg_match('/[A-Z]/', $data['password']) || // 1 majuscule
          !preg_match('/[a-z]/', $data['password']) || // 1 minuscule
          !preg_match('/[0-9]/', $data['password']) || // 1 chiffre
          !preg_match('/[\W_]/', $data['password'])) { // 1 caractère spécial
          return $this->json([
              'status'  => 'Erreur',
              'message' => 'Mot de passe invalide : 10 caractères minimum, 1 majuscule, 1 minuscule, 1 chiffre, 1 caractère spécial'
          ], 400);
      }

      // Étape 3 - Récupérer et valider le token
      // findValidToken() vérifie que le token est non expiré et non utilisé
      $resetToken = $tokenRepository->findValidToken($data['token']);

      if (!$resetToken) {
          return $this->json([
              'status'  => 'Erreur',
              'message' => 'Token invalide ou expiré. Veuillez demander une nouvelle réinitialisation'
          ], 400);
      }

      // Étape 4 - Récupérer l'utilisateur associé au token
      $utilisateur = $resetToken->getUtilisateur();

      // Étape 5 - Hasher et sauvegarder le nouveau mot de passe
      $nouveauMotDePasseHashe = $passwordHasher->hashPassword($utilisateur, $data['password']);
      $utilisateur->setPassword($nouveauMotDePasseHashe);

      // Étape 6 - Marquer le token comme utilisé
      // Cela empêche sa réutilisation dans le cas ou quelqu'un d'autre récupère l'email
      $resetToken->setIsUsed(true);

      // Étape 7 - Sauvegarder les modifications en base de données
      $em->persist($utilisateur);
      $em->persist($resetToken);
      $em->flush();

      // Étape 8 - Retourner un succès
      return $this->json([
          'status'  => 'Succès',
          'message' => 'Mot de passe réinitialisé avec succès. Vous pouvez maintenant vous connecter'
      ], 200);
  }


  // Déclare la route HTTP GET pour l’URL /api/me Pour SWAGGER
  #[Route('/me', name: 'api_me', methods: ['GET'])]
  #[OA\Get(
      summary: 'Récupérer les infos de l’utilisateur connecté',
      description: 'Retourne l’email et le rôle de l’utilisateur à partir du token JWT envoyé dans l’en-tête Authorization.'
  )]
  #[OA\Tag(name: 'Authentification')]
  public function me(UtilisateurRepository $utilisateurRepository): JsonResponse
  {

    // Étape 1 - Récupère l'utilisateur courant à partir du token JWT
    $utilisateur = $this->getUser();

    // Étape 2 - Si aucun utilisateur n’est trouvé (token absent ou invalide)
    if (!$utilisateur instanceof \App\Entity\Utilisateur) {
      // On renvoie un JSON avec une erreur 401 Unauthorized
      return $this->json([
          'status' => 'Erreur',
          'message' => 'Utilisateur non connecté ou JWT mal configuré'
      ], 401);
    }

    // Étape 3 - Retourne les infos de l'utilisateur authentifié
    return $this->json([
        'email' => $utilisateur->getEmail(), // email de l’utilisateur
        'role'  => $utilisateur->getRole()?->getLibelle() ?? 'ROLE_VISITEUR'  // rôle de l’utilisateur
    ]);
  }
}
