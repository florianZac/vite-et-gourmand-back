<?php

namespace App\Controller;

use App\Entity\Plat;
use App\Repository\PlatRepository;
use App\Repository\AllergeneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @author      Florian Aizac
 * @created     25/02/2026
 * @description CRUD des plats (réservé aux administrateurs)
 * 
 *  1. getAllPlats : Retourner la liste de tous les plats avec ses allergènes associés
 *  2. getPlatById : Retourner un plat par son id avec ses allergènes associés
 *  3. createPlat : Créer un nouveau plat avec ses allergènes associés
 *  4. updatePlat : Met à jour un allergène en le ciblant par son id avec ses allergènes associés
 *  5. deletePlat : Supprimer un plat avec ses allergènes associés en le ciblant par son id 
 *  Corps JSON pour POST/PUT :
 *  {
 *      "titre_plat": "Bœuf bourguignon",
 *      "photo": "boeuf-bourguignon.jpg",
 *      "allergenes": [1, 3, 5]   valeur (optionnel)
 *  }
*/

#[Route('/api/admin/plats')]
final class PlatController extends BaseController
{
    /**
     * @description Retourner la liste de tous les plats avec ses allergènes associés
     * @param PlatRepository $platRepository Le repository des plats
     * @return JsonResponse
     */
    #[Route('', name: 'api_admin_plats_list', methods: ['GET'])]
    public function getAllPlats(PlatRepository $platRepository): JsonResponse
    {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer tous les plats
        $plats = $platRepository->findAll();

        // Étape 3 - Retourner la liste en JSON
        return $this->json(['status' => 'Succès', 'total' => count($plats), 'plats' => $plats]);
    }

    /**
     * @description Retourner un plat par son id avec ses allergènes associés
     * @param int $id L'id du plat
     * @param PlatRepository $platRepository Le repository des plats
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'api_admin_plats_show', methods: ['GET'])]
    public function getPlatById(int $id, PlatRepository $platRepository): JsonResponse
    {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer le plat par son id
        $plat = $platRepository->find($id);
        if (!$plat) {
            return $this->json(['status' => 'Erreur', 'message' => 'Plat non trouvé'], 404);
        }

        // Étape 3 - Retourner le plat en JSON
        return $this->json(['status' => 'Succès', 'plat' => $plat]);
    }

    /**
     * @description Créer un nouveau plat avec ses allergènes associés
     * Corps JSON attendu : { "titre_plat": "Bœuf bourguignon", "photo": "boeuf.jpg", "allergenes": [1, 2] }
     * @param Request $request La requête HTTP contenant les données au format JSON
     * @param PlatRepository $platRepository Le repository des plats
     * @param AllergeneRepository $allergeneRepository Le repository des allergènes
     * @param EntityManagerInterface $em L'EntityManager pour gérer les opérations de base de données
     * @return JsonResponse
     */
    #[Route('', name: 'api_admin_plats_create', methods: ['POST'])]
    public function createPlat(
        Request $request,
        PlatRepository $platRepository,
        AllergeneRepository $allergeneRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer les données JSON
        $data = json_decode($request->getContent(), true);

        // Étape 3 - Vérifier les champs obligatoires
        if (empty($data['titre_plat']) || empty($data['photo'])) {
            return $this->json(['status' => 'Erreur', 'message' => 'Les champs titre_plat et photo sont obligatoires'], 400);
        }

        // Étape 4 - Vérifier que le titre n'existe pas déjà
        $existant = $platRepository->findOneBy(['titre_plat' => $data['titre_plat']]);
        if ($existant) {
            return $this->json(['status' => 'Erreur', 'message' => 'Un plat avec ce titre existe déjà'], 409);
        }

        // Étape 5 - Créer le plat
        $plat = new Plat();
        $plat->setTitrePlat($data['titre_plat']);
        $plat->setPhoto($data['photo']);

        // Étape 6 - Associer les allergènes si fournis
        if (!empty($data['allergenes']) && is_array($data['allergenes'])) {
            foreach ($data['allergenes'] as $allergeneId) {
                $allergene = $allergeneRepository->find($allergeneId);
                if (!$allergene) {
                    return $this->json(['status' => 'Erreur', 'message' => "Allergène id $allergeneId non trouvé"], 404);
                }
                $plat->addAllergene($allergene);
            }
        }

        // Étape 7 - Persister et sauvegarder
        $em->persist($plat);
        $em->flush();

        // Étape 8 - Retourner une confirmation avec l'id créé
        return $this->json(['status' => 'Succès', 'message' => 'Plat créé avec succès', 'id' => $plat->getId()], 201);
    }

    /**
     * @description Met à jour un allergène en le ciblant par son id avec ses allergènes associés
     * Les allergènes envoyés REMPLACENT les anciens (synchronisation complète)
     * Corps JSON attendu : { "titre_plat": "Nouveau titre", "photo": "photo.jpg", "allergenes": [1, 2] }
     * @param int $id L'id du plat à modifier
     * @param Request $request La requête HTTP contenant les données au format JSON
     * @param PlatRepository $platRepository Le repository des plats
     * @param AllergeneRepository $allergeneRepository Le repository des allergènes
     * @param EntityManagerInterface $em L'EntityManager pour gérer les opérations de base de données
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'api_admin_plats_update', methods: ['PUT'])]
    public function updatePlat(
        int $id,
        Request $request,
        PlatRepository $platRepository,
        AllergeneRepository $allergeneRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer les données JSON
        $data = json_decode($request->getContent(), true);

        // Étape 3 - Chercher le plat à modifier
        $plat = $platRepository->find($id);
        if (!$plat) {
            return $this->json(['status' => 'Erreur', 'message' => 'Plat non trouvé'], 404);
        }

        // Étape 4 - Mettre à jour le titre si fourni
        if (isset($data['titre_plat'])) {
            $existant = $platRepository->findOneBy(['titre_plat' => $data['titre_plat']]);
            if ($existant && $existant->getId() !== $plat->getId()) {
                return $this->json(['status' => 'Erreur', 'message' => 'Un plat avec ce titre existe déjà'], 409);
            }
            $plat->setTitrePlat($data['titre_plat']);
        }

        // Étape 5 - Mettre à jour la photo si fournie
        if (isset($data['photo'])) {
            $plat->setPhoto($data['photo']);
        }

        // Étape 6 - Synchroniser les allergènes si fournis
        // On retire tous les anciens et on remet les nouveaux
        if (isset($data['allergenes']) && is_array($data['allergenes'])) {
            foreach ($plat->getAllergenes() as $allergeneExistant) {
                $plat->removeAllergene($allergeneExistant);
            }
            foreach ($data['allergenes'] as $allergeneId) {
                $allergene = $allergeneRepository->find($allergeneId);
                if (!$allergene) {
                    return $this->json(['status' => 'Erreur', 'message' => "Allergène id $allergeneId non trouvé"], 404);
                }
                $plat->addAllergene($allergene);
            }
        }

        // Étape 7 - Sauvegarder (pas besoin de persist() pour une mise à jour)
        $em->flush();

        // Étape 8 - Retourner une confirmation
        return $this->json(['status' => 'Succès', 'message' => 'Plat mis à jour avec succès']);
    }

    /**
     * @description Supprimer un plat avec ses allergènes associés en le ciblant par son id
     * @param int $id L'id du plat à supprimer
     * @param PlatRepository $platRepository Le repository des plats
     * @param EntityManagerInterface $em L'EntityManager pour gérer les opérations de base de données
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'api_admin_plats_delete', methods: ['DELETE'])]
    public function deletePlat(int $id, PlatRepository $platRepository, EntityManagerInterface $em): JsonResponse
    {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Chercher le plat à supprimer
        $plat = $platRepository->find($id);
        if (!$plat) {
            return $this->json(['status' => 'Erreur', 'message' => 'Plat non trouvé'], 404);
        }

        // Étape 3 - Supprimer le plat
        $em->remove($plat);
        $em->flush();

        // Étape 4 - Retourner une confirmation
        return $this->json(['status' => 'Succès', 'message' => 'Plat supprimé avec succès']);
    }
}

