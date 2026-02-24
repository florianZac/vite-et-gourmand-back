<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\RoleRepository;
use App\Service\MailerService;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author      Florian Aizac
 * @created     24/02/2026
 * @description Contrôleur gérant les utilisateurs 
 *  mise en place d'un CRUD de base pour les utilisateurs
 *  Create (POST) : un utilisateur (register) est géré dans AuthController.php, mais les autres opérations seront gérées ici
 *  Read  (GET): récuperation d'un utilisateur par son id fonction-> getUserById(), récuperation de la liste des utilisateurs fonction-> getAllUsers()
 *  Update (PUT): mise à jour des informations d'un utilisateur.
 *  Delete (DELETE): suppression d'un utilisateur par id est par email
 *  
 *  Recherche (GET) pour le fun recherche d'un utilisateur par son email.
 */

#[Route('/api/admin')]
final class AdminController extends AbstractController
{
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
        // indice : $utilisateurRepository->findOneBy(...)
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

        // Modification du mot de passe d'un utilisateur par un administrateur
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
        // Mise à jour du prénom
        if (isset($data['prenom'])) {
            $utilisateur->setPrenom($data['prenom']);
        }

        // Vérification doublon téléphone
        if (isset($data['telephone'])) {
            $telephoneExistant = $utilisateurRepository->findOneBy(['telephone' => $data['telephone']]);
            // Même logique que pour l'email
            if ($telephoneExistant && $telephoneExistant->getId() !== $utilisateur->getId()) {
                return $this->json(['status' => 'Erreur', 'message' => 'Ce téléphone est déjà utilisé'], 409);
            }
            $utilisateur->setTelephone($data['telephone']);
        }
        // Mise à jour de la ville
        if (isset($data['ville'])) {
            $utilisateur->setVille($data['ville']);
        }
        // Mise à jour de l'adresse postale
        if (isset($data['adresse_postale'])) {
            $utilisateur->setAdressePostale($data['adresse_postale']);
        }

        // Modification du rôle
        if (isset($data['role'])) {
            $role = $roleRepository->findOneBy(['libelle' => $data['role']]);
            if (!$role) {
                return $this->json(['status' => 'Erreur', 'message' => 'Rôle invalide'], 400);
            }
            $utilisateur->setRole($role);
        }

        // Étape 6 - flush() uniquement, pas besoin de persist() pour une mise à jour
        $em->flush();

        // Étape 7 - Retourner un message de confirmation
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

        // Modification du mot de passe d'un utilisateur par un administrateur
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
        // Mise à jour du prénom
        if (isset($data['prenom'])) {
            $utilisateur->setPrenom($data['prenom']);
        }

        // Vérification doublon téléphone
        if (isset($data['telephone'])) {
            $telephoneExistant = $utilisateurRepository->findOneBy(['telephone' => $data['telephone']]);
            // Même logique que pour l'email
            if ($telephoneExistant && $telephoneExistant->getId() !== $utilisateur->getId()) {
                return $this->json(['status' => 'Erreur', 'message' => 'Ce téléphone est déjà utilisé'], 409);
            }
            $utilisateur->setTelephone($data['telephone']);
        }
        // Mise à jour de la ville
        if (isset($data['ville'])) {
            $utilisateur->setVille($data['ville']);
        }
        // Mise à jour de l'adresse postale
        if (isset($data['adresse_postale'])) {
            $utilisateur->setAdressePostale($data['adresse_postale']);
        }

        // Modification du rôle
        if (isset($data['role'])) {
            $role = $roleRepository->findOneBy(['libelle' => $data['role']]);
            if (!$role) {
                return $this->json(['status' => 'Erreur', 'message' => 'Rôle invalide'], 400);
            }
            $utilisateur->setRole($role);
        }

        // Étape 6 - flush() uniquement, pas besoin de persist() pour une mise à jour
        $em->flush();

        // Étape 7 - Retourner un message de confirmation
        return $this->json(['status' => 'Succès', 'message' => 'Utilisateur mis à jour avec succès']);
    }
}

