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
        $roles = $roleRepository->findAll();

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
        $role = $roleRepository->find($id);

        if (!$role) {
            return $this->json(
                ['success' => false, 'error' => 'Role not found'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

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

        // Validation des données requises
        $requiredFields = ['libelle'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                return $this->json(
                    ['success' => false, 'error' => "Missing required field: $field"],
                    JsonResponse::HTTP_BAD_REQUEST
                );
            }
        }

        // Vérifier que le rôle n'existe pas déjà
        $existingRole = $roleRepository->findOneBy(['libelle' => $data['libelle']]);
        if ($existingRole) {
            return $this->json(
                ['success' => false, 'error' => 'Role already exists'],
                JsonResponse::HTTP_CONFLICT
            );
        }

        // Créer le nouveau rôle
        $role = new Role();
        $role->setLibelle($data['libelle']);
        if (isset($data['description'])) {
            $role->setDescription($data['description']);
        }

        $entityManager->persist($role);
        $entityManager->flush();

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
        $role = $roleRepository->find($id);

        if (!$role) {
            return $this->json(
                ['success' => false, 'error' => 'Role not found'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        $data = $this->getDataFromRequest($request);

        // Mise à jour des champs optionnels
        if (isset($data['libelle'])) {
            // Vérifier que le nouveau libellé n'existe pas ailleurs
            $existingRole = $roleRepository->findOneBy(['libelle' => $data['libelle']]);
            if ($existingRole && $existingRole->getId() !== $id) {
                return $this->json(
                    ['success' => false, 'error' => 'Role libelle already exists'],
                    JsonResponse::HTTP_CONFLICT
                );
            }
            $role->setLibelle($data['libelle']);
        }
        if (isset($data['description'])) {
            $role->setDescription($data['description']);
        }

        $entityManager->flush();

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
     */
    #[Route('/{id}', name: 'api_admin_roles_delete', methods: ['DELETE'])]
    public function deleteRole(
        int $id,
        RoleRepository $roleRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $role = $roleRepository->find($id);

        if (!$role) {
            return $this->json(
                ['success' => false, 'error' => 'Role not found'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        // Vérifier que le rôle n'est pas utilisé par des utilisateurs
        $utilisateurs = $role->getUtilisateurs();
        if ($utilisateurs && \count($utilisateurs) > 0) {
            return $this->json(
                ['success' => false, 'error' => 'Cannot delete role with associated users'],
                JsonResponse::HTTP_CONFLICT
            );
        }

        $entityManager->remove($role);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Role deleted successfully',
        ]);
    }
}
