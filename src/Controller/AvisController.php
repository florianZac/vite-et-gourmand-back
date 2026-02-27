<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Repository\AvisRepository;
use App\Repository\CommandeRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @author      Florian Aizac
 * @created     26/02/2026
 * @description Contrôleur gérant les opérations sur les avis
 *  1. getAllAvis         : Retourne la liste de tous les avis
 *  2. getAvisById        : Retourne un avis par son id
 *  3. createAvis         : Crée un nouvel avis
 *  4. updateAvis         : Met à jour un avis
 *  5. deleteAvis         : Supprime un avis
 */
#[Route('/api/admin/avis')]
final class AvisController extends BaseController
{
    /**
     * @description Récupère tous les avis avec pagination
     * @return JsonResponse
     */
    #[Route('', name: 'api_admin_avis_list', methods: ['GET'])]
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
     * @description Récupère un avis par son identifiant
     * @param int $id Identifiant de l'avis
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'api_admin_avis_show', methods: ['GET'])]
    public function getAvisById(int $id, AvisRepository $avisRepository): JsonResponse
    {
        $avis = $avisRepository->find($id);

        if (!$avis) {
            return $this->json(
                ['success' => false, 'error' => 'Avis not found'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        return $this->json([
            'success' => true,
            'data' => $avis,
        ]);
    }

    /**
     * @description Crée un nouvel avis
     * Corps JSON attendu :
     * {
     *   "note": 5,
     *   "description": "Excellent service",
     *   "statut": "approuvé",
     *   "utilisateur_id": 1,
     *   "commande_id": 1
     * }
     * @return JsonResponse
     */
    #[Route('', name: 'api_admin_avis_create', methods: ['POST'])]
    public function createAvis(
        Request $request,
        AvisRepository $avisRepository,
        UtilisateurRepository $utilisateurRepository,
        CommandeRepository $commandeRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        
        $data = $this->getDataFromRequest($request);

        // Validation des données requises
        $requiredFields = ['note', 'description', 'statut', 'utilisateur_id', 'commande_id'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                return $this->json(
                    ['success' => false, 'error' => "Missing required field: $field"],
                    JsonResponse::HTTP_BAD_REQUEST
                );
            }
        }

        // Vérifier que l'utilisateur existe
        $utilisateur = $utilisateurRepository->find($data['utilisateur_id']);
        if (!$utilisateur) {
            return $this->json(
                ['success' => false, 'error' => 'Utilisateur not found'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        // Vérifier que la commande existe
        $commande = $commandeRepository->find($data['commande_id']);
        if (!$commande) {
            return $this->json(
                ['success' => false, 'error' => 'Commande not found'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        // Créer le nouvel avis
        $avis = new Avis();
        $avis->setNote((int) $data['note']);
        $avis->setDescription($data['description']);
        $avis->setStatut($data['statut']);
        $avis->setUtilisateur($utilisateur);
        $avis->setCommande($commande);

        $entityManager->persist($avis);
        $entityManager->flush();

        return $this->json(
            [
                'success' => true,
                'message' => 'Avis created successfully',
                'data' => $avis,
            ],
            JsonResponse::HTTP_CREATED
        );
    }

    /**
     * @description Met à jour un avis existant
     * @param int $id Identifiant de l'avis
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'api_admin_avis_update', methods: ['PUT'])]
    public function updateAvis(
        int $id,
        Request $request,
        AvisRepository $avisRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $avis = $avisRepository->find($id);

        if (!$avis) {
            return $this->json(
                ['success' => false, 'error' => 'Avis not found'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        $data = $this->getDataFromRequest($request);

        // Mise à jour des champs optionnels
        if (isset($data['note'])) {
            $avis->setNote((int) $data['note']);
        }
        if (isset($data['description'])) {
            $avis->setDescription($data['description']);
        }
        if (isset($data['statut'])) {
            $avis->setStatut($data['statut']);
        }

        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Avis updated successfully',
            'data' => $avis,
        ]);
    }

    /**
     * @description Supprime un avis
     * @param int $id Identifiant de l'avis
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'api_admin_avis_delete', methods: ['DELETE'])]
    public function deleteAvis(
        int $id,
        AvisRepository $avisRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $avis = $avisRepository->find($id);

        if (!$avis) {
            return $this->json(
                ['success' => false, 'error' => 'Avis not found'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        $entityManager->remove($avis);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Avis deleted successfully',
        ]);
    }
}
