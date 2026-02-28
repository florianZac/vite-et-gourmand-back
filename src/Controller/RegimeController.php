<?php

namespace App\Controller;

use App\Entity\Regime;
use App\Repository\RegimeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @author      Florian Aizac
 * @created     25/02/2026
 * @description CRUD des régimes alimentaires (réservé aux administrateurs)
 * 
 *  1. getAllRegimes : Retourner la liste de tous les régimes 
 *  2. getRegimeById : Retourner un régimes par son id
 *  3. createRegime : Création d'un régimes
 *  4. updateRegime : Met à jour un régimes en le ciblant par son id
 *  5. deleteRegime : Supprimer un régimes en le ciblant par son id 
 */
#[Route('/api/admin/regimes')]
final class RegimeController extends BaseController
{
    /**
     * @description Retourner la liste de tous les régimes 
     * @param RegimeRepository $regimeRepository Le repository des régimes
     * @return JsonResponse
     */
    #[Route('', name: 'api_admin_regimes_list', methods: ['GET'])]
    public function getAllRegimes(RegimeRepository $regimeRepository): JsonResponse
    {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer tous les régimes
        $regimes = $regimeRepository->findAll();

        // Étape 3 - Retourner la liste en JSON
        return $this->json(['status' => 'Succès', 'total' => count($regimes), 'regimes' => $regimes]);
    }

    /**
     * @description Retourner un régimes par son id
     * @param int $id L'id du régime
     * @param RegimeRepository $regimeRepository Le repository des régimes
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'api_admin_regimes_show', methods: ['GET'])]
    public function getRegimeById(int $id, RegimeRepository $regimeRepository): JsonResponse
    {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer le régime par son id
        $regime = $regimeRepository->find($id);
        if (!$regime) {
            return $this->json(['status' => 'Erreur', 'message' => 'Régime non trouvé'], 404);
        }

        // Étape 3 - Retourner le régime en JSON
        return $this->json(['status' => 'Succès', 'regime' => $regime]);
    }

    /**
     * @description Création d'un régimes
     * Corps JSON attendu : { "libelle": "Végétarien" }
     * @param Request $request La requête HTTP contenant les données au format JSON
     * @param RegimeRepository $regimeRepository Le repository des régimes
     * @param EntityManagerInterface $em L'EntityManager pour gérer les opérations de base de données
     * @return JsonResponse
     */
    #[Route('', name: 'api_admin_regimes_create', methods: ['POST'])]
    public function createRegime(Request $request, RegimeRepository $regimeRepository, EntityManagerInterface $em): JsonResponse
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
        $existant = $regimeRepository->findOneBy(['libelle' => $data['libelle']]);
        if ($existant) {
            return $this->json(['status' => 'Erreur', 'message' => 'Ce régime existe déjà'], 409);
        }

        // Étape 5 - Créer un nouvelle objet régime
        $regime = new Regime();
        // Étape 5.1 - met à jour ces données
        $regime->setLibelle($data['libelle']);
        // Étape 5.2 - persiste la donnée
        $em->persist($regime);
        // Étape 5.3 - sauvegarde la donnée
        $em->flush();

        // Étape 6 - Retourner une confirmation avec l'id créé
        return $this->json(['status' => 'Succès', 'message' => 'Régime créé avec succès', 'id' => $regime->getId()], 201);
    }

    /**
     * @description Met à jour un régimes en le ciblant par son id
     * Corps JSON attendu : { "libelle": "Vegan" }
     * @param int $id L'id du régime à modifier
     * @param Request $request La requête HTTP contenant les données au format JSON
     * @param RegimeRepository $regimeRepository Le repository des régimes
     * @param EntityManagerInterface $em L'EntityManager pour gérer les opérations de base de données
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'api_admin_regimes_update', methods: ['PUT'])]
    public function updateRegime(int $id, Request $request, RegimeRepository $regimeRepository, EntityManagerInterface $em): JsonResponse
    {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer les données JSON
        $data = json_decode($request->getContent(), true);

        // Étape 3 - Chercher le régime à modifier
        $regime = $regimeRepository->find($id);
        if (!$regime) {
            return $this->json(['status' => 'Erreur', 'message' => 'Régime non trouvé'], 404);
        }

        // Étape 4 - Mettre à jour le libellé si fourni
        if (isset($data['libelle'])) {
            $existant = $regimeRepository->findOneBy(['libelle' => $data['libelle']]);
            if ($existant && $existant->getId() !== $regime->getId()) {
                return $this->json(['status' => 'Erreur', 'message' => 'Ce libellé est déjà utilisé'], 409);
            }
            $regime->setLibelle($data['libelle']);
        }

        // Étape 5 - Sauvegarder (pas besoin de persist() pour une mise à jour)
        $em->flush();

        // Étape 6 - Retourner une confirmation
        return $this->json(['status' => 'Succès', 'message' => 'Régime mis à jour avec succès']);
    }

    /**
     * @description Supprimer un régimes en le ciblant par son id 
     * @param int $id L'id du régime à supprimer
     * @param RegimeRepository $regimeRepository Le repository des régimes
     * @param EntityManagerInterface $em L'EntityManager pour gérer les opérations de base de données
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'api_admin_regimes_delete', methods: ['DELETE'])]
    public function deleteRegime(int $id, RegimeRepository $regimeRepository, EntityManagerInterface $em): JsonResponse
    {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Chercher le régime à supprimer
        $regime = $regimeRepository->find($id);
        if (!$regime) {
            return $this->json(['status' => 'Erreur', 'message' => 'Régime non trouvé'], 404);
        }

        // Étape 3 - Supprimer le régime
        $em->remove($regime);
        $em->flush();

        // Étape 4 - Retourner une confirmation
        return $this->json(['status' => 'Succès', 'message' => 'Régime supprimé avec succès']);
    }
}
