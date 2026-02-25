<?php

namespace App\Controller;

use App\Entity\Allergene;
use App\Repository\AllergeneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @author      Florian Aizac
 * @created     25/02/2026
 * @description CRUD des allergènes (réservé aux administrateurs)
 *  1. getAllAllergenes : Retourne la liste de tous les allergènes 
 *  2. getAllergeneById : Retourne un allergène par son id
 *  3. createAllergene :  création d'un allergène
 *  4. updateAllergene : modifie un allergène en le ciblant par son id
 *  5. deleteAllergene : supprime un allergène en le ciblant par son id 
 */
#[Route('/api/admin/allergenes')]
final class AllergeneController extends BaseController
{
    /**
     * @description Retourne la liste de tous les allergènes
     * @param AllergeneRepository $allergeneRepository Le repository des allergènes
     * @return JsonResponse
     */
    #[Route('', name: 'api_admin_allergenes_list', methods: ['GET'])]
    public function getAllAllergenes(AllergeneRepository $allergeneRepository): JsonResponse
    {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer tous les allergènes
        $allergenes = $allergeneRepository->findAll();

        // Étape 3 - Retourner la liste en JSON
        return $this->json(['status' => 'Succès', 'total' => count($allergenes), 'allergenes' => $allergenes]);
    }

    /**
     * @description Retourne un allergène par son id
     * @param int $id L'id de l'allergène ciblé par l'admin
     * @param AllergeneRepository $allergeneRepository Le repository des allergènes
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'api_admin_allergenes_show', methods: ['GET'])]
    public function getAllergeneById(int $id, AllergeneRepository $allergeneRepository): JsonResponse
    {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer l'allergène par son id
        $allergene = $allergeneRepository->find($id);
        if (!$allergene) {
            return $this->json(['status' => 'Erreur', 'message' => 'Allergène non trouvé'], 404);
        }

        // Étape 3 - Retourner l'allergène en JSON
        return $this->json(['status' => 'Succès', 'allergene' => $allergene]);
    }

    /**
     * @description création d'un nouvel allergène
     * Corps JSON attendu : { "libelle": "Gluten" }
     * @param Request $request La requête HTTP contenant les données au format JSON
     * @param AllergeneRepository $allergeneRepository Le repository des allergènes
     * @param EntityManagerInterface $em L'EntityManager pour gérer les opérations de base de données
     * @return JsonResponse
     */
    #[Route('', name: 'api_admin_allergenes_create', methods: ['POST'])]
    public function createAllergene(Request $request, AllergeneRepository $allergeneRepository, EntityManagerInterface $em): JsonResponse
    {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer les données JSON
        $data = json_decode($request->getContent(), true);

        // Étape 3 - Vérifier que le libellé est présent
        if (empty($data['libelle'])) {
            return $this->json(['status' => 'Erreur', 'message' => 'Le libellé est obligatoire'], 400);
        }

        // Étape 4 - Vérifier que le libellé n'existe pas déjà
        $existant = $allergeneRepository->findOneBy(['libelle' => $data['libelle']]);
        if ($existant) {
            return $this->json(['status' => 'Erreur', 'message' => 'Cet allergène existe déjà'], 409);
        }

        // Étape 5 - Créer le nouvel allergène
        $allergene = new Allergene();
        $allergene->setLibelle($data['libelle']);
        $em->persist($allergene);
        $em->flush();

        // Étape 6 - Retourner une confirmation avec l'id créé
        return $this->json(['status' => 'Succès', 'message' => 'Allergène créé avec succès', 'id' => $allergene->getId()], 201);
    }

    /**
     * @description Met à jour un allergène par son id
     * Corps JSON attendu : { "libelle": "Lait" }
     * @param int $id L'id de l'allergène à modifier
     * @param Request $request La requête HTTP contenant les données au format JSON
     * @param AllergeneRepository $allergeneRepository Le repository des allergènes
     * @param EntityManagerInterface $em L'EntityManager pour gérer les opérations de base de données
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'api_admin_allergenes_update', methods: ['PUT'])]
    public function updateAllergene(int $id, Request $request, AllergeneRepository $allergeneRepository, EntityManagerInterface $em): JsonResponse
    {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer les données JSON
        $data = json_decode($request->getContent(), true);

        // Étape 3 - Chercher l'allergène à modifier
        $allergene = $allergeneRepository->find($id);
        if (!$allergene) {
            return $this->json(['status' => 'Erreur', 'message' => 'Allergène non trouvé'], 404);
        }

        // Étape 4 - Mettre à jour le libellé si fourni
        if (isset($data['libelle'])) {
            // Vérifier que le nouveau libellé n'est pas déjà utilisé par un autre allergène
            $existant = $allergeneRepository->findOneBy(['libelle' => $data['libelle']]);
            if ($existant && $existant->getId() !== $allergene->getId()) {
                return $this->json(['status' => 'Erreur', 'message' => 'Ce libellé est déjà utilisé'], 409);
            }
            $allergene->setLibelle($data['libelle']);
        }

        // Étape 5 - Sauvegarder (pas besoin de persist() pour une mise à jour)
        $em->flush();

        // Étape 6 - Retourner une confirmation
        return $this->json(['status' => 'Succès', 'message' => 'Allergène mis à jour avec succès']);
    }

    /**
     * @description Supprime un allergène par son id
     * @param int $id L'id de l'allergène à supprimer
     * @param AllergeneRepository $allergeneRepository Le repository des allergènes
     * @param EntityManagerInterface $em L'EntityManager pour gérer les opérations de base de données
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'api_admin_allergenes_delete', methods: ['DELETE'])]
    public function deleteAllergene(int $id, AllergeneRepository $allergeneRepository, EntityManagerInterface $em): JsonResponse
    {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Chercher l'allergène à supprimer
        $allergene = $allergeneRepository->find($id);
        if (!$allergene) {
            return $this->json(['status' => 'Erreur', 'message' => 'Allergène non trouvé'], 404);
        }

        // Étape 3 - Supprimer l'allergène
        $em->remove($allergene);
        $em->flush();

        // Étape 4 - Retourner une confirmation
        return $this->json(['status' => 'Succès', 'message' => 'Allergène supprimé avec succès']);
    }
}