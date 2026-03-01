<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ODM\MongoDB\DocumentManager; // DocumentManager pour lire les logs MongoDB

use App\Repository\UtilisateurRepository;
use App\Repository\CommandeRepository;
use App\Repository\AvisRepository;
use App\Repository\RoleRepository;
use App\Repository\AllergeneRepository;
use App\Repository\MenuRepository;
use App\Repository\PlatRepository;
use App\Repository\RegimeRepository;
use App\Repository\SuiviCommandeRepository;
use App\Repository\ThemeRepository;
use App\Repository\HoraireRepository;

use App\Document\LogActivite;             // import du Document MongoDB
use App\Service\MailerService;

use App\Entity\Utilisateur;
use App\Entity\Horaire;
use App\Entity\Menu;
use App\Entity\Regime;
use App\Entity\SuiviCommande;
use App\Entity\Theme;
use App\Entity\Allergene;
use App\Entity\Plat;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author      Florian Aizac
 * @created     24/02/2026
 * @description Contrôleur gérant les utilisateurs 
 *  mise en place d'un CRUD de base pour les utilisateurs
 * 
 *  1. getAllUsers()                : Retourne la liste de tous les utilisateurs
 *  2. getUserById()                : Retourne un utilisateurs par son id
 *  3. deleteUserByID()             : Supprime un utilisateurs par son id
 *  4. deleteUserByEmail()          : Supprime un utilisateurs par son e-mail
 *  5. updateUserAdminById()        : Modifie un utilisateurs en le ciblant par son id
 *  6. updateUserAdminByEmail()     : Modifie un utilisateurs en le ciblant par son e-mail
 *  7. desactiverCompte()           : Désactivation d'un compte utilisateur
 *  8. reactiverCompte()            : Résactivation d'un compte utilisateur
 *  9. createEmploye()              : Création d'un compte employé par l'administrateur
 *  10. deleteCommande()            : Supprimer une commande
 *  11. getAllAvis                  : Récupère tous les avis
 *  12. supprimerAvis()             : Supprimer un avis client
 *  13. getStatistiques()           : Retourne les statistiques complètes vennant de MySQl 
 *  14. getStatistiquesGraphiques() : Retourne les données graphiques depuis MongoDB
 *  15. getLogs()                   : Retourne les logs d'activité vennant de MongoDB -> NoSQL
 *  16. createHoraire()             : Créer un nouvel horaire
 *  17. updateHoraire()             : Met à jour un horaire par son id
 *  18. deleteHoraire()             : Supprime un horaire par son id
*/

#[Route('/api/admin')]
final class AdminController extends AbstractController
{
    
    // =========================================================================
    // UTILISATEUR
    // =========================================================================

    #[Route('/utilisateurs', name: 'api_utilisateurs', methods: ['GET'])]

    // Fonction qui récupère tous les utilisateurs
    // équivalent de SELECT * FROM utilisateur
    public function getAllUsers(UtilisateurRepository $utilisateurRepository): JsonResponse
    {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer tous les utilisateurs
        $utilisateurs = $utilisateurRepository->findAll();
        
        // Étape 3 - Retourner la liste des utilisateurs en JSON
        return $this->json($utilisateurs);
    }

    #[Route('/utilisateurs/{id}', name: 'api_utilisateur_show', methods: ['GET'])]
    // Fonction qui sélectionne un utilisateur par son id meme chose que SELECT * FROM utilisateur WHERE utilisateur_id = :id
    public function getUserById(int $id, UtilisateurRepository $utilisateurRepository): JsonResponse
    {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer l'utilisateur par son id
        $utilisateur = $utilisateurRepository->find($id);
        // Si l'utilisateur n'existe pas, on retourne une réponse JSON avec un message d'erreur et un code HTTP 404 correspondant à Not Found
        if (!$utilisateur) {
            return $this->json(['status' => 'Erreur', 'message' => 'Utilisateur non trouvé'], 404);
        }
        
        // Étape 3 - Si l'utilisateur est trouvé, on le retourne en JSON
        return $this->json($utilisateur);
    }

    #[Route('/utilisateurs/{id}', name: 'api_utilisateur_delete', methods: ['DELETE'])]
    // Fonction qui supprime un utilisateur par son id
    // équivalent de DELETE FROM utilisateur WHERE utilisateur_id = :id
    public function deleteUserByID(int $id, UtilisateurRepository $utilisateurRepository, EntityManagerInterface $em): JsonResponse
    {
        // Étape 1 - Vérifier le rôle ADMIN
        // lit le token JWT de la requête pour vérifier si l'utilisateur a le rôle ADMIN
        // Si l'utilisateur n'a pas le rôle ADMIN, on retourne une réponse JSON avec un message d'erreur et un code HTTP 403
        // isGranted() retourne true ou false
        // denyAccessUnlessGranted() ne retourne rien et lève une exception
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer l'utilisateur à supprimer par son id
        $utilisateur_rechercher = $utilisateurRepository->find($id);
        // Si l'utilisateur n'existe pas, on retourne une réponse JSON avec un message d'erreur et un code HTTP 404 correspondant à Not Found
        if (!$utilisateur_rechercher) {
            return $this->json(['status' => 'Erreur','message' => 'Utilisateur non trouvé'], 404);
        }

        // Étape 3 - Empêcher l'admin de se supprimer lui-même
        if ($this->getUser() === $utilisateur_rechercher) {
            return $this->json(['status' => 'Erreur', 'message' => 'Vous ne pouvez pas supprimer votre propre compte'], 403);
        }

        // Étape 4 - Supprimer l'utilisateur
        $em->remove($utilisateur_rechercher);
        $em->flush();

        // Étape 4 - Retourner un message de confirmation
        return $this->json(['status' => 'Succès', 'message' => 'Utilisateur supprimé avec succès']);
    }

    #[Route('/utilisateurs/email/{email}', name: 'api_utilisateur_delete_email', methods: ['DELETE'])]
    // Fonction qui supprime un utilisateur par son email
    // équivalent de DELETE FROM utilisateur WHERE email = :email
    public function deleteUserByEmail(string $email, UtilisateurRepository $utilisateurRepository, EntityManagerInterface $em): JsonResponse
    {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Chercher l'utilisateur par son email
        $email_utilisateur = $utilisateurRepository->findOneBy(['email' => $email]);
        // Si l'utilisateur n'existe pas, on retourne une réponse JSON avec un message d'erreur 
        // et un code HTTP 404 correspondant à Not Found
         if (!$email_utilisateur) {
            return $this->json(['status' => 'Erreur', 'message' => 'Utilisateur non trouvé'], 404); 
        }
    
        // Étape 3 - Empêcher l'admin de se supprimer lui-même
        if ($this->getUser() === $email_utilisateur) {
            return $this->json(['status' => 'Erreur', 'message' => 'Vous ne pouvez pas supprimer votre propre compte'], 403);
        }

        // Étape 4 - Supprimer l'utilisateur
        $em->remove($email_utilisateur);
        $em->flush();

        // Étape 5 - Retourner un message de confirmation
        return $this->json(['status' => 'Succès', 'message' => 'Utilisateur supprimé avec succès']);
    }

    #[Route('/utilisateurs/{id}', name: 'api_utilisateur_update', methods: ['PUT'])]
    /**
     * 
     * @description Cette fonction permet à un administrateur de mettre à jour les informations d'un utilisateur en utilisant son id.
     * L'administrateur peut mettre à jour les champs suivants : email, prenom, telephone, ville, adresse_postale.
     * lors de la modification du mot de passe d'un utilisateur il lui envoie un email de notification pour l'informer du changement dde mot de passe
     * @param int $id l'id de l'utilisateur à mettre à jour
     * @param Request $request la requête HTTP contenant les données à mettre à jour au format JSON
     * @param UtilisateurRepository $utilisateurRepository le repository pour accéder aux données des utilisateurs
     * @param EntityManagerInterface $em l'EntityManager pour gérer les opérations de base de données
     * @param UserPasswordHasherInterface $passwordHasher le service pour hasher les mots de passe des utilisateurs
     * @param RoleRepository $roleRepository le repository pour accéder aux données des rôles
     * @param MailerService $mailerService le service pour envoyer des emails de notification aux utilisateurs
     * @return JsonResponse une réponse JSON indiquant le succès ou l'échec de l'opération de mise à jour
     */
    public function updateUserAdminById(
        int $id, 
        Request $request, 
        UtilisateurRepository $utilisateurRepository, 
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,  
        RoleRepository $roleRepository,               
        MailerService $mailerService                  
    ): JsonResponse
    {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer les données JSON
        $data = json_decode($request->getContent(), true);

        // Étape 3 - Chercher l'utilisateur par son id
        $utilisateur = $utilisateurRepository->find($id);

        // Étape 4 - Si non trouvé retourner 404
        if (!$utilisateur) {
            return $this->json(['status' => 'Erreur', 'message' => 'Utilisateur non trouvé'], 404);
        }
        
        // Étape 5 - Mise à jour des champs
        // On vérifie que le nouvel email n'est pas déjà utilisé par un AUTRE utilisateur
        if (isset($data['email'])) {

            $emailExistant = $utilisateurRepository->findOneBy(['email' => $data['email']]);
            // getId() !== $utilisateur->getId() permet d'exclure l'utilisateur lui-même
            if ($emailExistant && $emailExistant->getId() !== $utilisateur->getId()) {
                return $this->json(['status' => 'Erreur', 'message' => 'Cet email est déjà utilisé'], 409);
            }
            // Si l'email est valide et pas déjà utilisé, on le met à jour
            $utilisateur->setEmail($data['email']);
        }

        // Étape 6 - Modification du mot de passe d'un utilisateur par un administrateur
        // et envois d'un email de notification à l'utilisateur pour l'informer du changement de mot de passe
        if (isset($data['password'])) {
            // Génère un mot de passe temporaire aléatoire
            $motDePasseTemporaire = bin2hex(random_bytes(6)); // génère ex: "a3f2b1c4d5e6"
            // Hash le mot de passe temporaire
            $motDePasseHashe = $passwordHasher->hashPassword($utilisateur, $motDePasseTemporaire);
            $utilisateur->setPassword($motDePasseHashe);
            // Envoie un email au client avec le mot de passe temporaire
            $mailerService->sendPasswordResetEmail($utilisateur, $motDePasseTemporaire);
        }

        // Étape 7 - Mise à jour du prénom
        if (isset($data['prenom'])) {
            $utilisateur->setPrenom($data['prenom']);
        }

        // Étape 8 - Vérification doublon téléphone
        if (isset($data['telephone'])) {
            $telephoneExistant = $utilisateurRepository->findOneBy(['telephone' => $data['telephone']]);
            // Même logique que pour l'email
            if ($telephoneExistant && $telephoneExistant->getId() !== $utilisateur->getId()) {
                return $this->json(['status' => 'Erreur', 'message' => 'Ce téléphone est déjà utilisé'], 409);
            }
            $utilisateur->setTelephone($data['telephone']);
        }

        // Étape 9 - Mise à jour de la ville
        if (isset($data['ville'])) {
            $utilisateur->setVille($data['ville']);
        }

        // Étape 10 - Mise à jour de l'adresse postale
        if (isset($data['adresse_postale'])) {
            $utilisateur->setAdressePostale($data['adresse_postale']);
        }

        // Étape 11 - Modification du rôle
        if (isset($data['role'])) {
            $role = $roleRepository->findOneBy(['libelle' => $data['role']]);
            if (!$role) {
                return $this->json(['status' => 'Erreur', 'message' => 'Rôle invalide'], 400);
            }
            $utilisateur->setRole($role);
        }

        // Étape 12 - flush() uniquement, pas besoin de persist() pour une mise à jour
        $em->flush();

        // Étape 13 - Retourner un message de confirmation
        return $this->json(['status' => 'Succès', 'message' => 'Utilisateur mis à jour avec succès']);
    }

    #[Route('/utilisateurs/email/{email}', name: 'api_utilisateur_update_by_email', methods: ['PUT'])]
    /**
     * @description Cette fonction permet à un administrateur de mettre à jour les informations d'un utilisateur en utilisant son email.
     * L'administrateur peut mettre à jour les champs suivants : email, prenom, telephone, ville, adresse_postale, mdp et role.
     * lors de la modification du mot de passe d'un utilisateur il lui envoie un email de notification pour l'informer du changement de mot de passe
     * @param string $email l'email de l'utilisateur à mettre à jour
     * @param Request $request la requête HTTP contenant les données à mettre à jour au format JSON
     * @param UtilisateurRepository $utilisateurRepository le repository pour accéder aux données des utilisateurs
     * @param EntityManagerInterface $em l'EntityManager pour gérer les opérations de base de données
     * @param UserPasswordHasherInterface $passwordHasher le service pour hasher les mots de passe des utilisateurs
     * @param RoleRepository $roleRepository le repository pour accéder aux données des rôles
     * @param MailerService $mailerService le service pour envoyer des emails de notification aux utilisateurs
     * @return JsonResponse une réponse JSON indiquant le succès ou l'échec de l'opération de mise à jour
     */
    public function updateUserAdminByEmail(
        string $email, 
        Request $request, 
        UtilisateurRepository $utilisateurRepository, 
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,  
        RoleRepository $roleRepository,               
        MailerService $mailerService                  
    ): JsonResponse
    {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer les données JSON
        $data = json_decode($request->getContent(), true);

        // Étape 3 - Chercher l'utilisateur par son email
        $utilisateur = $utilisateurRepository->findOneBy(['email' => $email]);

        // Étape 4 - Si non trouvé retourner 404
        if (!$utilisateur) {
            return $this->json(['status' => 'Erreur', 'message' => 'Utilisateur non trouvé'], 404);
        }
        
        // Étape 5 - Mise à jour des champs        
        // On vérifie que le nouvel email n'est pas déjà utilisé par un AUTRE utilisateur
        if (isset($data['email'])) {

            $emailExistant = $utilisateurRepository->findOneBy(['email' => $data['email']]);
            // getId() !== $utilisateur->getId() permet d'exclure l'utilisateur lui-même
            if ($emailExistant && $emailExistant->getId() !== $utilisateur->getId()) {
                return $this->json(['status' => 'Erreur', 'message' => 'Cet email est déjà utilisé'], 409);
            }
            // Si l'email est valide et pas déjà utilisé, on le met à jour
            $utilisateur->setEmail($data['email']);
        }

        // Étape 6 - Modification du mot de passe d'un utilisateur par un administrateur
        // et envois d'un email de notification à l'utilisateur pour l'informer du changement de mot de passe
        if (isset($data['password'])) {
            // Génère un mot de passe temporaire aléatoire
            $motDePasseTemporaire = bin2hex(random_bytes(6)); // génère ex: "a3f2b1c4d5e6"
            // Hash le mot de passe temporaire
            $motDePasseHashe = $passwordHasher->hashPassword($utilisateur, $motDePasseTemporaire);
            $utilisateur->setPassword($motDePasseHashe);
            // Envoie un email au client avec le mot de passe temporaire
            $mailerService->sendPasswordResetEmail($utilisateur, $motDePasseTemporaire);
        }

        // Étape 7 - Mise à jour du prénom
        if (isset($data['prenom'])) {
            $utilisateur->setPrenom($data['prenom']);
        }

        // Étape 8 - Vérification doublon téléphone
        if (isset($data['telephone'])) {
            $telephoneExistant = $utilisateurRepository->findOneBy(['telephone' => $data['telephone']]);
            // Même logique que pour l'email
            if ($telephoneExistant && $telephoneExistant->getId() !== $utilisateur->getId()) {
                return $this->json(['status' => 'Erreur', 'message' => 'Ce téléphone est déjà utilisé'], 409);
            }
            $utilisateur->setTelephone($data['telephone']);
        }

        // Étape 9 - Mise à jour de la ville
        if (isset($data['ville'])) {
            $utilisateur->setVille($data['ville']);
        }

        // Étape 10 - Mise à jour de l'adresse postale
        if (isset($data['adresse_postale'])) {
            $utilisateur->setAdressePostale($data['adresse_postale']);
        }

        // Étape 11 - Modification du rôle
        if (isset($data['role'])) {
            $role = $roleRepository->findOneBy(['libelle' => $data['role']]);
            if (!$role) {
                return $this->json(['status' => 'Erreur', 'message' => 'Rôle invalide'], 400);
            }
            $utilisateur->setRole($role);
        }

        // Étape 12 - flush() uniquement, pas besoin de persist() pour une mise à jour
        $em->flush();

        // Étape 13 - Retourner un message de confirmation
        return $this->json(['status' => 'Succès', 'message' => 'Utilisateur mis à jour avec succès']);
    }

    /**
     * @description Désactivation d'un compte utilisateur
     * @param int $id L'id de l'utilisateur à désactiver
     * @param UtilisateurRepository $utilisateurRepository Le repository des utilisateurs
     * @param EntityManagerInterface $em L'EntityManager
     * @return JsonResponse
     */
    #[Route('/utilisateurs/{id}/desactivation', name: 'api_admin_utilisateur_desactivation', methods: ['PUT'])]
    public function desactiverCompte(int $id, UtilisateurRepository $utilisateurRepository, EntityManagerInterface $em): JsonResponse
    {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer l'utilisateur
        $utilisateur = $utilisateurRepository->find($id);
        if (!$utilisateur) {
            return $this->json(['status' => 'Erreur', 'message' => 'Utilisateur non trouvé'], 404);
        }

        // Étape 3 - Vérifier que le compte n'est pas déjà inactif
        if ($utilisateur->getStatutCompte() === 'inactif') {
            return $this->json(['status' => 'Erreur', 'message' => 'Compte déjà désactivé'], 400);
        }

        // Étape 4 - Désactiver le compte
        $utilisateur->setStatutCompte('inactif');

        // Étape 5 - Sauvegarder en base
        $em->flush();

        // Étape 6 - Retourner un message de confirmation
        return $this->json(['status' => 'Succès', 'message' => 'Compte désactivé avec succès']);
    }

    /**
     * @description Réactivation d'un compte utilisateur
     * @param int $id L'id de l'utilisateur à réactiver
     * @param UtilisateurRepository $utilisateurRepository Le repository des utilisateurs
     * @param EntityManagerInterface $em L'EntityManager
     * @return JsonResponse
     */
    #[Route('/utilisateurs/{id}/reactivation', name: 'api_admin_utilisateur_reactivation', methods: ['PUT'])]
    public function reactiverCompte(int $id, UtilisateurRepository $utilisateurRepository, EntityManagerInterface $em): JsonResponse
    {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer l'utilisateur
        $utilisateur = $utilisateurRepository->find($id);
        if (!$utilisateur) {
            return $this->json(['status' => 'Erreur', 'message' => 'Utilisateur non trouvé'], 404);
        }

        // Étape 3 - Vérifier que le compte est bien inactif
        if ($utilisateur->getStatutCompte() === 'actif') {
            return $this->json(['status' => 'Erreur', 'message' => 'Compte déjà actif'], 400);
        }

        // Étape 4 - Réactiver le compte
        $utilisateur->setStatutCompte('actif');

        // Étape 5 - Sauvegarder en base
        $em->flush();

        // Étape 6 - Retourner un message de confirmation
        return $this->json(['status' => 'Succès', 'message' => 'Compte réactivé avec succès']);
    }

    /**
     * @description Création d'un compte employé par l'administrateur
     * Génère un mot de passe temporaire aléatoire et l'envoie par email à l'employé
     * L'employé devra changer son mot de passe à sa première connexion
     * Corps JSON attendu :
     * {
     *   "nom": "Dupont",
     *   "prenom": "Jean",
     *   "email": "jean.dupont@vite-et-gourmand.fr",
     *   "telephone": "0612345678",
     *   "ville": "Bordeaux"
     * }
     * @param Request $request La requête HTTP contenant les données de l'employé
     * @param UtilisateurRepository $utilisateurRepository Pour vérifier les doublons
     * @param RoleRepository $roleRepository Pour récupérer le rôle ROLE_EMPLOYE
     * @param UserPasswordHasherInterface $passwordHasher Pour hasher le mot de passe temporaire
     * @param EntityManagerInterface $em Pour sauvegarder en base
     * @param MailerService $mailerService Pour envoyer les identifiants à l'employé
     * @return JsonResponse
     */
    #[Route('/employes', name: 'api_admin_employes_create', methods: ['POST'])]
    public function createEmploye(
        Request $request,
        UtilisateurRepository $utilisateurRepository,
        RoleRepository $roleRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        MailerService $mailerService
    ): JsonResponse {

        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer les données JSON
        $data = json_decode($request->getContent(), true);

        // Étape 3 - Vérifier les champs obligatoires
        if (empty($data['nom']) || empty($data['prenom']) || empty($data['email']) || empty($data['telephone'])) {
            return $this->json(['status' => 'Erreur', 'message' => 'Les champs nom, prenom, email et telephone sont obligatoires'], 400);
        }

        // Étape 4 - Vérifier que l'email n'existe pas déjà
        if ($utilisateurRepository->findOneBy(['email' => $data['email']])) {
            return $this->json(['status' => 'Erreur', 'message' => 'Cet email est déjà utilisé'], 409);
        }

        // Étape 5 - Récupérer le rôle ROLE_EMPLOYE
        $role = $roleRepository->findOneBy(['libelle' => 'ROLE_EMPLOYE']);
        if (!$role) {
            return $this->json(['status' => 'Erreur', 'message' => 'Rôle ROLE_EMPLOYE introuvable en base'], 500);
        }

        // Étape 6 - Générer un mot de passe temporaire aléatoire
        // L'employé devra le changer à sa première connexion
        $motDePasseTemporaire = bin2hex(random_bytes(8));

        // Étape 7 - Créer le compte employé
        $employe = new Utilisateur();
        $employe->setNom($data['nom']);
        $employe->setPrenom($data['prenom']);
        $employe->setEmail($data['email']);
        $employe->setTelephone($data['telephone']);
        $employe->setVille($data['ville'] ?? '');
        $employe->setAdressePostale($data['adresse_postale'] ?? '');
        $employe->setCodePostal($data['code_postal'] ?? '');
        $employe->setPays($data['pays'] ?? 'France');
        $employe->setStatutCompte('actif');
        $employe->setRole($role);

        // Étape 8 - Hasher le mot de passe temporaire
        $motDePasseHashe = $passwordHasher->hashPassword($employe, $motDePasseTemporaire);
        $employe->setPassword($motDePasseHashe);

        // Étape 9 - Sauvegarder en base
        $em->persist($employe);
        $em->flush();

        // Étape 10 - Envoyer les identifiants par email à l'employé
        // L'email contient le mot de passe temporaire en clair lors du premier envoi, jamais stocké en clair
        $mailerService->sendBienvenueEmployeEmail($employe, $motDePasseTemporaire);

        // Étape 11 - Retourner une confirmation (sans le mot de passe)
        return $this->json([
            'status'  => 'Succès',
            'message' => 'Compte employé créé avec succès. Les identifiants ont été envoyés par email.',
            'employe' => [
                'id'     => $employe->getId(),
                'nom'    => $employe->getNom(),
                'prenom' => $employe->getPrenom(),
                'email'  => $employe->getEmail(),
            ]
        ], 201);
    }

    // =========================================================================
    // COMMANDE
    // =========================================================================

    #[Route('/commandes/{id}', name: 'api_client_commande_delete', methods: ['DELETE'])]
    /**
     * @description Cette fonction permet à un client connecté de supprimer une commande.
     * L'utilisateur doit être authentifié et avoir le rôle CLIENT pour accéder à cette route. 
     * @param CommandeRepository $commandeRepository Le repository des commandes
     * @param EntityManagerInterface $em l'EntityManager pour gérer les opérations de base de données
     * @return JsonResponse reponse JSON
     */
    public function deleteCommande(int $id, CommandeRepository $commandeRepository, EntityManagerInterface $em): JsonResponse
    {
        // Étape 1 — Vérifier le rôle CLIENT
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 — Chercher la commande par son id
        $commande = $commandeRepository->find($id);

        // Étape 3 — Si non trouvée retourner 404
        if (!$commande) {
            return $this->json(['status' => 'Erreur', 'message' => 'Commande non trouvée'], 404);
        }

        // Étape 4 — Supprimer la commande
        $em->remove($commande);
        $em->flush();

        // Étape 5 — Retourner un message de confirmation
        return $this->json(['status' => 'Succès', 'message' => 'Commande supprimée avec succès']);
    }

    // =========================================================================
    // AVIS
    // =========================================================================

    /**
     * @description Récupère tous les avis
     * @return JsonResponse
     */
    #[Route('/avis', name: 'api_admin_avis_list', methods: ['GET'])]
    public function getAllAvis(AvisRepository $avisRepository): JsonResponse
    {
        $avis = $avisRepository->findAll();

        return $this->json([
            'success' => true,
            'data' => $avis,
            'count' => \count($avis),
        ]);
    }

    /**
     * @description Supprime un avis client
     * @param int $id L'id de l'avis
     * @param AvisRepository $avisRepository Le repository des avis
     * @param EntityManagerInterface $em L'EntityManager
     * @return JsonResponse
     */
    #[Route('/avis/{id}', name: 'api_admin_avis_delete', methods: ['DELETE'])]
    public function supprimerAvis(int $id, AvisRepository $avisRepository, EntityManagerInterface $em): JsonResponse
    {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer l'avis
        $avis = $avisRepository->find($id);
        if (!$avis) {
            return $this->json(['status' => 'Erreur', 'message' => 'Avis non trouvé'], 404);
        }

        // Étape 3 - Supprimer l'avis
        $em->remove($avis);
        $em->flush();

        // Étape 4 - Retourner un message de confirmation
        return $this->json(['status' => 'Succès', 'message' => 'Avis supprimé avec succès']);
    }

    // =========================================================================
    // STATISTIQUE - SOURCE MySQL
    // =========================================================================

    /**
     * @description Retourne les statistiques complètes pour le tableau de bord admin de l'entreprise vite et gourmand
     * Inclut : commandes, CA, remboursements, utilisateurs, avis, et données graphique par menu
     * SOURCE : MySQL -> données structurées avec relations (commandes, utilisateurs, avis)
     * Voir getLogs() pour les données de traçabilité depuis MongoDB
     * @param CommandeRepository $commandeRepository Le repository des commandes
     * @param UtilisateurRepository $utilisateurRepository Le repository des utilisateurs
     * @param AvisRepository $avisRepository Le repository des avis
     * @return JsonResponse
     */
    #[Route('/statistiques', name: 'api_admin_statistiques', methods: ['GET'])]
    public function getStatistiques(
        CommandeRepository $commandeRepository,
        UtilisateurRepository $utilisateurRepository,
        AvisRepository $avisRepository
    ): JsonResponse {

        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Stats commandes
        $statsCommandes = $commandeRepository->getStatistiques();

        // Étape 3 - Stats utilisateurs
        $totalUtilisateurs  = count($utilisateurRepository->findAll());
        $comptesActifs      = count($utilisateurRepository->findBy(['statut_compte' => 'actif']));
        $comptesInactifs    = count($utilisateurRepository->findBy(['statut_compte' => 'inactif']));
        $comptesEnAttente   = count($utilisateurRepository->findBy(['statut_compte' => 'en_attente_desactivation']));

        // Étape 4 - Stats avis
        $totalAvis          = count($avisRepository->findAll());
        $avisEnAttente      = count($avisRepository->findBy(['statut' => 'en_attente']));
        $avisValides        = count($avisRepository->findBy(['statut' => 'validé']));
        $avisRefuses        = count($avisRepository->findBy(['statut' => 'refusé']));

        // Étape 5 - Retourner toutes les statistiques
        return $this->json([
            'status' => 'Succès',
            'statistiques' => array_merge($statsCommandes, [

                // Stats utilisateurs
                'utilisateurs' => [
                    'total'        => $totalUtilisateurs,
                    'actifs'       => $comptesActifs,
                    'inactifs'     => $comptesInactifs,
                    'en_attente'   => $comptesEnAttente,
                ],

                // Stats avis
                'avis' => [
                    'total'      => $totalAvis,
                    'en_attente' => $avisEnAttente,
                    'valides'    => $avisValides,
                    'refuses'    => $avisRefuses,
                ],
            ])
        ]);
    }

    // =========================================================================
    // LOGS - SOURCE MongoDB (NoSQL)
    // =========================================================================
    
    /**
     * @description Retourne les données graphiques depuis MongoDB
     * 
     * Exploite les logs de type "commande_creee" déjà stockés dans MongoDB
     * 
     * Pourquoi MongoDB pour les graphiques ?
     *  L'énoncé impose une source NoSQL pour les graphiques
     *  Les logs commande_creee contiennent déjà : menu, montant, ville_livraison, date
     *  MongoDB est optimisé pour ce type d'agrégation sans jointure
     *
     * Données retournées :
     *   - CA total et par menu
     *   - Nombre de commandes par menu
     *   - Filtres optionnels : ?menu=NomMenu, ?debut=2026-01-01, ?fin=2026-12-31
     *
     * SOURCE : MongoDB logs d'activité non relationnels
     * Voir getStatistiques() pour les données métier depuis MySQL
     *
     * @param Request $request La requête HTTP avec les éventuels filtres
     * @param DocumentManager $dm Le DocumentManager MongoDB
     * @return JsonResponse
     */
    #[Route('/statistiques/graphiques', name: 'api_admin_statistiques_graphiques', methods: ['GET'])]
    public function getStatistiquesGraphiques(Request $request, DocumentManager $dm): JsonResponse
    {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer les filtres depuis la query string
        $menuFiltre  = $request->query->get('menu');    // ex: ?menu=Menu Prestige
        $dateDebut   = $request->query->get('debut');   // ex: ?debut=2026-01-01
        $dateFin     = $request->query->get('fin');     // ex: ?fin=2026-12-31

        // Étape 3 - Construire la requête MongoDB
        // On cible uniquement les logs de type "commande_creee"
        $qb = $dm->createQueryBuilder(LogActivite::class)
            ->field('type')->equals('commande_creee')
            ->sort('createdAt', 'ASC');

        // Étape 4 - Filtre par date si fourni
        // MongoDB stocke createdAt en DateTime ->comparaison directe possible
        if ($dateDebut) {
            $qb->field('createdAt')->gte(new \DateTime($dateDebut));
        }
        if ($dateFin) {
            $qb->field('createdAt')->lte(new \DateTime($dateFin . ' 23:59:59'));
        }

        // Étape 5 - Exécuter la requête
        $logs = $qb->getQuery()->execute();

        // Étape 6 - Agréger les données pour les graphiques
        // On parcourt les logs et on regroupe par menu
        $caParMenu         = []; // CA total par menu
        $commandesParMenu  = []; // Nombre de commandes par menu
        $caParMois         = []; // CA par mois pour courbe d'évolution

        foreach ($logs as $log) {
            $contexte = $log->getContexte();
            $menu     = $contexte['menu']    ?? 'Inconnu';
            $montant  = $contexte['montant'] ?? 0;
            $mois     = $log->getCreatedAt()->format('Y-m'); // ex: "2026-03"

            // Si le filtre menu est actif, on ignore les autres menus
            if ($menuFiltre && $menu !== $menuFiltre) {
                continue;
            }

            // Agrégation CA par menu
            if (!isset($caParMenu[$menu])) {
                $caParMenu[$menu] = 0;
            }
            $caParMenu[$menu] += $montant;

            // Agrégation nombre de commandes par menu
            if (!isset($commandesParMenu[$menu])) {
                $commandesParMenu[$menu] = 0;
            }
            $commandesParMenu[$menu]++;

            // Agrégation CA par mois
            if (!isset($caParMois[$mois])) {
                $caParMois[$mois] = 0;
            }
            $caParMois[$mois] += $montant;
        }

        // Étape 7 - Formater pour l'affichage graphique côté front
        // Format tableau de tableaux pour être directement exploitable par Chart.js ou autre
        $graphiqueMenus = [];
        foreach ($caParMenu as $menu => $ca) {
            $graphiqueMenus[] = [
                'menu'               => $menu,
                'ca_total'           => round($ca, 2),
                'nombre_commandes'   => $commandesParMenu[$menu],
                'ca_moyen'           => round($ca / $commandesParMenu[$menu], 2),
            ];
        }

        // Trier par CA décroissant
        usort($graphiqueMenus, fn($a, $b) => $b['ca_total'] <=> $a['ca_total']);

        // Formater CA par mois (trié chronologiquement)
        ksort($caParMois);
        $graphiqueMois = [];
        foreach ($caParMois as $mois => $ca) {
            $graphiqueMois[] = [
                'mois'     => $mois,
                'ca_total' => round($ca, 2),
            ];
        }

        // Étape 8 - Retourner les données graphiques
        return $this->json([
            'status'  => 'Succès',
            'source'  => 'MongoDB',
            'filtres' => [
                'menu'  => $menuFiltre ?? 'tous',
                'debut' => $dateDebut  ?? 'aucun',
                'fin'   => $dateFin    ?? 'aucun',
            ],
            'graphiques' => [
                'ca_par_menu'  => $graphiqueMenus,  // pour graphique barres/camembert
                'ca_par_mois'  => $graphiqueMois,   // pour courbe d'évolution temporelle
                'ca_total'     => round(array_sum($caParMenu), 2),
                'total_commandes' => array_sum($commandesParMenu),
            ],
        ]);
    }

    /**
     * @description Retourne les logs d'activité depuis MongoDB
     * 
     * Pourquoi deux routes distinctes (/statistiques et /logs) ?
     * 
     * /statistiques -> MySQL (Doctrine ORM)
     *   -> Données métier structurées : commandes, montants, utilisateurs, avis
     *   -> Relations entre entités (jointures), agrégations comptables
     *   -> Schéma fixe, intégrité référentielle garantie
     * 
     * /logs -> MongoDB (Doctrine ODM)
     *   -> Données de traçabilité volumineuses : connexions, actions, changements de statut
     *   -> Pas de relations, chaque log est autonome et indépendant
     *   -> Schéma flexible (le champ "contexte" varie selon le type de log)
     *   -> Écriture rapide, lecture par filtres simples sans jointure
     * 
     * @param Request $request La requête HTTP avec les éventuels filtres
     * @param DocumentManager $dm Le DocumentManager MongoDB
     * @return JsonResponse
     */
    #[Route('/logs', name: 'api_admin_logs', methods: ['GET'])]
    public function getLogs(Request $request, DocumentManager $dm): JsonResponse
    {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer les filtres depuis la query string
        $type   = $request->query->get('type');             // ex: ?type=connexion
        $email  = $request->query->get('email');            // ex: ?email=florian@email.fr
        $limit  = (int) ($request->query->get('limit', 100)); // défaut 100 résultats

        // Étape 3 - Construire la requête MongoDB via le QueryBuilder ODM
        // Différence clé avec MySQL :
        //       MySQL : $em->createQueryBuilder() -> génère du SQL avec jointures
        //       MongoDB : $dm->createQueryBuilder() -> requête NoSQL, pas de SQL, pas de jointure
        $qb = $dm->createQueryBuilder(LogActivite::class)
            ->sort('createdAt', 'DESC') // tri du plus récent au plus ancien
            ->limit($limit);

        // Étape 4 - Appliquer les filtres si fournis
        if ($type) {
            // Filtre sur le champ "type" du document MongoDB
            $qb->field('type')->equals($type);
        }

        
        if ($email) {
            // Filtre sur le champ "email" du document MongoDB
            $qb->field('email')->equals($email);
        }

        // Étape 5 - Exécuter la requête et récupérer les résultats
        $logs = $qb->getQuery()->execute();

        // Étape 6 - Formater les résultats pour la réponse JSON
        // MongoDB retourne des objets LogActivite -> on les sérialise manuellement
        $logsFormates = [];
        foreach ($logs as $log) {
            $logsFormates[] = [
                'id'         => $log->getId(),
                'type'       => $log->getType(),
                'message'    => $log->getMessage(),
                'email'      => $log->getEmail(),
                'role'       => $log->getRole(),
                'contexte'   => $log->getContexte(),
                'created_at' => $log->getCreatedAt()->format('d/m/Y H:i:s'),
            ];
        }

        // Étape 7 - Retourner les logs en JSON
        return $this->json([
            'status'  => 'Succès',
            'source'  => 'MongoDB',     // indique explicitement la source NoSQL
            'total'   => count($logsFormates),
            'filtres' => [              // rappel des filtres appliqués pour la lisibilité
                'type'  => $type  ?? 'tous',
                'email' => $email ?? 'tous',
                'limit' => $limit,
            ],
            'logs' => $logsFormates,
        ]);
    }

    // =========================================================================
    // HORAIRES
    // =========================================================================

    /**
     * @description Créer un nouvel horaire
     * Corps JSON attendu : { "jour": "Lundi", "heure_ouverture": "09:00", "heure_fermeture": "18:00" }
     * @param Request $request La requête HTTP
     * @param HoraireRepository $horaireRepository Le repository des horaires
     * @param EntityManagerInterface $em L'EntityManager
     * @return JsonResponse
     */
    #[Route('/horaires', name: 'api_admin_horaires_create', methods: ['POST'])]
    public function createHoraire(
        Request $request,
        HoraireRepository $horaireRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }
        // Étape 2 - Récupère les donnée de la table horraire
        $data = json_decode($request->getContent(), true);

        // Étape 3 - La valeur est elle vide  ?
        if (empty($data['jour'])) {
            return $this->json(['status' => 'Erreur', 'message' => 'Le champ jour est obligatoire'], 400);
        }

        // Étape 4 - Vérifier qu'un horaire pour ce jour n'existe pas déjà
        $existant = $horaireRepository->findOneBy(['jour' => $data['jour']]);
        if ($existant) {
            return $this->json(['status' => 'Erreur', 'message' => 'Un horaire pour ce jour existe déjà'], 409);
        }

        // Étape 5 - Créer l'objet horaire et remplis sa donnée
        $horaire = new Horaire();
        $horaire->setJour($data['jour']);

        // Étape 6 - heure_ouverture et heure_fermeture sont optionnelles car jour fermé possible
        // voir comment le traiter autrement 
        if (!empty($data['heure_ouverture'])) {
            $horaire->setHeureOuverture(new \DateTime($data['heure_ouverture']));
        }
        if (!empty($data['heure_fermeture'])) {
            $horaire->setHeureFermeture(new \DateTime($data['heure_fermeture']));
        }

        // Étape 7 - Persister et Sauvegarder la donnée
        $em->persist($horaire);
        $em->flush();

        // Étape 8 - Retourne le résultat 
        return $this->json(['status' => 'Succès', 'message' => 'Horaire créé avec succès', 'id' => $horaire->getId()], 201);
    }

    /**
     * @description Met à jour un horaire par son id
     * Corps JSON attendu (tous optionnels) : { "jour": "Mardi", "heure_ouverture": "10:00", "heure_fermeture": "19:00" }
     * @param int $id L'id de l'horaire
     * @param Request $request La requête HTTP
     * @param HoraireRepository $horaireRepository Le repository des horaires
     * @param EntityManagerInterface $em L'EntityManager
     * @return JsonResponse
     */
    #[Route('/horaires/{id}', name: 'api_admin_horaires_update', methods: ['PUT'])]
    public function updateHoraire(
        int $id,
        Request $request,
        HoraireRepository $horaireRepository,
        EntityManagerInterface $em
    ): JsonResponse {

        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer l'horaire
        $horaire = $horaireRepository->find($id);
        if (!$horaire) {
            return $this->json(['status' => 'Erreur', 'message' => 'Horaire non trouvé'], 404);
        }
        // Étape 3 - Récupére le contenue
        $data = json_decode($request->getContent(), true);

        // Étape 4 - Mise à jour du jour et vérification doublon
        if (isset($data['jour'])) {
            $existant = $horaireRepository->findOneBy(['jour' => $data['jour']]);
            if ($existant && $existant->getId() !== $horaire->getId()) {
                return $this->json(['status' => 'Erreur', 'message' => 'Un horaire pour ce jour existe déjà'], 409);
            }
            $horaire->setJour($data['jour']);
        }

        // Étape 5 - Mise à jour heure_ouverture
        if (isset($data['heure_ouverture'])) {
            $horaire->setHeureOuverture(new \DateTime($data['heure_ouverture']));
        }

        // Étape 6 - Mise à jour heure_fermeture
        if (isset($data['heure_fermeture'])) {
            $horaire->setHeureFermeture(new \DateTime($data['heure_fermeture']));
        }

        // Étape 7 - Sauvegarder
        $em->flush();

        // Étape 8 - Retourne le résultat
        return $this->json(['status' => 'Succès', 'message' => 'Horaire mis à jour avec succès']);
    }

    /**
     * @description Supprime un horaire par son id
     * @param int $id L'id de l'horaire
     * @param HoraireRepository $horaireRepository Le repository des horaires
     * @param EntityManagerInterface $em L'EntityManager
     * @return JsonResponse
     */
    #[Route('/horaires/{id}', name: 'api_admin_horaires_delete', methods: ['DELETE'])]
    public function deleteHoraire(
        int $id,
        HoraireRepository $horaireRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer l'horaire
        $horaire = $horaireRepository->find($id);
        if (!$horaire) {
            return $this->json(['status' => 'Erreur', 'message' => 'Horaire non trouvé'], 404);
        }

        // Étape 3 - Supprimer
        $em->remove($horaire);
        $em->flush();

        // Étape 4 - Retourne le résultat       
        return $this->json(['status' => 'Succès', 'message' => 'Horaire supprimé avec succès']);
    }
}