<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UtilisateurRepository;
use App\Repository\CommandeRepository;
use App\Repository\AvisRepository;
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
 * 
 *  1. getAllUsers              : Retourne la liste de tous les utilisateurs
 *  2. getUserById              : Retourne un utilisateurs par son id
 *  3. deleteUserByID           : Supprime un utilisateurs par son id
 *  4. deleteUserByEmail        : Supprime un utilisateurs par son e-mail
 *  5. updateUserAdminById      : Modifie un utilisateurs en le ciblant par son id
 *  6. updateUserAdminByEmail   : Modifie un utilisateurs en le ciblant par son e-mail
 *  7. desactiverCompte         : Désactivation d'un compte utilisateur
 *  8. reactiverCompte          : Résactivation d'un compte utilisateur
 *  9. deleteCommande           : Supprimer une commande
 *  10. rechercherCommande      : Rechercher une commande par son numéro de commande
 *  11. supprimerAvis           : Supprimer un avis client
 *  12. refuserAvis             : Refuser un avis client
 *  13. approuverAvis           : Approuver un avis client
 *  14. getAvisEnAttente        : Afficher tous les avis en attente de validation
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
 
    /**
     * @description Rechercher une commande par son numéro de commande
     * @param string $nom Le numéro de commande à rechercher
     * @param CommandeRepository $commandeRepository Le repository des commandes
     * @return JsonResponse
     */
    #[Route('/commandes/recherche/{nom}', name: 'api_admin_commandes_recherche', methods: ['GET'])]
    public function rechercherCommande(string $nom, CommandeRepository $commandeRepository): JsonResponse
    {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Rechercher la commande par son numéro
        $commandes = $commandeRepository->findByNumeroCommande($nom);

        // Étape 3 - Si aucune commande trouvée
        if (empty($commandes)) {
            return $this->json(['status' => 'Erreur', 'message' => 'Aucune commande trouvée'], 404);
        }

        // Étape 4 - Retourner les commandes en JSON
        return $this->json(['status' => 'Succès', 'commandes' => $commandes]);
    }

    // =========================================================================
    // AVIS
    // =========================================================================

    /**
     * @description Affiche tous les avis en attente de validation
     * @param AvisRepository $avisRepository Le repository des avis
     * @return JsonResponse
     */
    #[Route('/avis', name: 'api_admin_avis', methods: ['GET'])]
    public function getAvisEnAttente(AvisRepository $avisRepository): JsonResponse
    {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer tous les avis en attente
        $avis = $avisRepository->findBy(['statut' => 'en_attente']);

        // Étape 3 - Retourner les avis en JSON
        return $this->json(['status' => 'Succès', 'total' => count($avis), 'avis' => $avis]);
    }

    /**
     * @description Approuve un avis client
     * @param int $id L'id de l'avis
     * @param AvisRepository $avisRepository Le repository des avis
     * @param EntityManagerInterface $em L'EntityManager
     * @return JsonResponse
     */
    #[Route('/avis/{id}/approuver', name: 'api_admin_avis_approuver', methods: ['PUT'])]
    public function approuverAvis(int $id, AvisRepository $avisRepository, EntityManagerInterface $em): JsonResponse
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

        // Étape 3 - Vérifier que l'avis est en attente
        if ($avis->getStatut() !== 'en_attente') {
            return $this->json(['status' => 'Erreur', 'message' => 'Cet avis n\'est pas en attente'], 400);
        }

        // Étape 4 - Approuver l'avis
        $avis->setStatut('validé');
        $em->flush();

        // Étape 5 - Retourner un message de confirmation
        return $this->json(['status' => 'Succès', 'message' => 'Avis approuvé avec succès']);
    }

    /**
     * @description Refuse un avis client
     * @param int $id L'id de l'avis
     * @param AvisRepository $avisRepository Le repository des avis
     * @param EntityManagerInterface $em L'EntityManager
     * @return JsonResponse
     */
    #[Route('/avis/{id}/refuser', name: 'api_admin_avis_refuser', methods: ['PUT'])]
    public function refuserAvis(int $id, AvisRepository $avisRepository, EntityManagerInterface $em): JsonResponse
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

        // Étape 3 - Vérifier que l'avis est en attente
        if ($avis->getStatut() !== 'en_attente') {
            return $this->json(['status' => 'Erreur', 'message' => 'Cet avis n\'est pas en attente'], 400);
        }

        // Étape 4 - Refuser l'avis
        $avis->setStatut('refusé');
        $em->flush();

        // Étape 5 - Retourner un message de confirmation
        return $this->json(['status' => 'Succès', 'message' => 'Avis refusé avec succès']);
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
    // STATISTIQUE
    // =========================================================================

    /**
     * @description Retourne les statistiques complètes pour le tableau de bord admin de l'entreprise vite et gourmand
     * Inclut : commandes, CA, remboursements, utilisateurs, avis, et données graphique par menu
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

}

