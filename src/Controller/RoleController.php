<?php

namespace App\Controller;

use App\Entity\Role;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @author      Florian Aizac
 * @created     26/02/2026
 * @description Contrôleur gérant les opérations sur les rôles
 *  1. getAllRoles        : Retourne la liste de tous les rôles
 *  2. getRoleById        : Retourne un rôle par son id
 *  3. createRole         : Crée un nouveau rôle
 *  4. updateRole         : Met à jour un rôle
 *  5. deleteRole         : Supprime un rôle
 */
#[Route('/api/admin/roles')]
final class RoleController extends BaseController
{
    /**
     * @description Récupère tous les rôles
     * @return JsonResponse
     */
    #[Route('', name: 'api_admin_roles_list', methods: ['GET'])]
    public function getAllRoles(RoleRepository $roleRepository): JsonResponse
    {
        // Étape 1 - Retourne tous les roles
        $roles = $roleRepository->findAll();

        // Étape 2 - Retourne le résultat
        return $this->json([
            'success' => true,
            'data' => $roles,
            'count' => \count($roles),
        ]);
    }

    /**
     * @description Récupère un rôle par son identifiant
     * @param int $id Identifiant du rôle
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'api_admin_roles_show', methods: ['GET'])]
    public function getRoleById(int $id, RoleRepository $roleRepository): JsonResponse
    {
        // Étape 1 - Retourne le role d'un utilisateur par son id
        $role = $roleRepository->find($id);

        // Étape 2 - Si il n'existe pas retourne une érreur
        if (!$role) {
            return $this->json(
                ['success' => false, 'error' => 'Role not found'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }
        // Étape 3 - Retourne le résultat
        return $this->json([
            'success' => true,
            'data' => $role,
        ]);
    }

    /**
     * @description Crée un nouveau rôle
     * Corps JSON attendu :
     * {
     *   "libelle": "ROLE_ADMIN",
     *   "description": "Administrateur du système"
     * }
     * @return JsonResponse
     */
    #[Route('', name: 'api_admin_roles_create', methods: ['POST'])]
    public function createRole(
        Request $request,
        RoleRepository $roleRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = $this->getDataFromRequest($request);

        // Étape 1 - Vérifie que l'utilisateur a le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['success' => false, 'error' => 'Accès refusé'], 403);
        }

        // Étape 2 - test les données requises
        $requiredFields = ['libelle'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                return $this->json(
                    ['success' => false, 'error' => "Missing required field: $field"],
                    JsonResponse::HTTP_BAD_REQUEST
                );
            }
        }

        // Étape 3 - Vérifie que le rôle n'existe pas déjà
        $existingRole = $roleRepository->findOneBy(['libelle' => $data['libelle']]);
        if ($existingRole) {
            return $this->json(
                ['success' => false, 'error' => 'Role already exists'],
                JsonResponse::HTTP_CONFLICT
            );
        }

        // Étape 4 - Créer le nouveau rôle
        $role = new Role();
        $role->setLibelle($data['libelle']);
        if (isset($data['description'])) {
            $role->setDescription($data['description']);
        }

        // Étape 5 - Persiste et sauvegarde
        $entityManager->persist($role);
        $entityManager->flush();

        // Étape 6 - Retourne le résultat
        return $this->json(
            [
                'success' => true,
                'message' => 'Role created successfully',
                'data' => $role,
            ],
            JsonResponse::HTTP_CREATED
        );
    }

    /**
     * @description Met à jour un rôle existant
     * @param int $id Identifiant du rôle
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'api_admin_roles_update', methods: ['PUT'])]
    public function updateRole(
        int $id,
        Request $request,
        RoleRepository $roleRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        
        // Étape 1 - Vérifie que l'utilisateur a le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['success' => false, 'error' => 'Accès refusé'], 403);
        }

        // Étape 2 - Trouve un role d'un utilisateur pointé par son id
        $role = $roleRepository->find($id);

        // Étape 3 - Vérifie si le role d'un utilisateur existe ou non 
        if (!$role) {
            return $this->json(
                ['success' => false, 'error' => 'Role not found'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        // Étape 4 - Récupere ces données
        $data = $this->getDataFromRequest($request);

        // Étape 5 - Mise à jour des champs optionnels
        if (isset($data['libelle'])) {
            // Étape 5.1 - Vérifier que le nouveau libellé n'existe pas ailleurs
            $existingRole = $roleRepository->findOneBy(['libelle' => $data['libelle']]);
            if ($existingRole && $existingRole->getId() !== $id) {
                return $this->json(
                    ['success' => false, 'error' => 'Role libelle already exists'],
                    JsonResponse::HTTP_CONFLICT
                );
            }
            $role->setLibelle($data['libelle']);
        }

        // Étape 6 - Met à jour la description 
        if (isset($data['description'])) {
            $role->setDescription($data['description']);
        }

        // Étape 7 - Sauvegarde les données
        $entityManager->flush();

        // Étape 8 - Retourne le résultat
        return $this->json([
            'success' => true,
            'message' => 'Role updated successfully',
            'data' => $role,
        ]);
    }

    /**
     * @description Supprime un rôle
     * @param int $id Identifiant du rôle
     * @return JsonResponse
     * 
     * FONCTION à vérifier dans mon algo pas sur quelle soit utile 
     * le role ne devrait pas pourvoir etre supprimer !!
     */
    #[Route('/{id}', name: 'api_admin_roles_delete', methods: ['DELETE'])]
    public function deleteRole(
        int $id,
        RoleRepository $roleRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {

        // Étape 1 - Vérifie que l'utilisateur a le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['success' => false, 'error' => 'Accès refusé'], 403);
        }

        // Étape 2 - Trouve le role d'un utilisateur pointé par son id
        $role = $roleRepository->find($id);

        // Étape 3 - existe t'il oui ou non ? 
        if (!$role) {
            return $this->json(
                ['success' => false, 'error' => 'Role not found'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        // Étape 4 - Vérifier que le rôle n'est pas utilisé par des utilisateurs
        $utilisateurs = $role->getUtilisateurs();
        if ($utilisateurs && \count($utilisateurs) > 0) {
            return $this->json(
                ['success' => false, 'error' => 'Cannot delete role with associated users'],
                JsonResponse::HTTP_CONFLICT
            );
        }
        // Étape 5 - Supprime le role d'un utilisateur
        $entityManager->remove($role);

        // Étape 6 - Sauvegarde le role d'un utilisateur
        $entityManager->flush();
        
        // Étape 7 - Retourne le résultat
        return $this->json([
            'success' => true,
            'message' => 'Role deleted successfully',
        ]);
    }
}
