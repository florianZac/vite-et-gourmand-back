<?php

namespace App\Controller;

use App\Entity\Plat;
use App\Entity\Allergene;
use App\Repository\PlatRepository;
use App\Repository\AllergeneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

/**
 * @author      Florian Aizac
 * @created     25/02/2026
 * @description CRUD des plats (réservé aux administrateurs)
 * 
 *  1. getAllPlats : Retourner la liste de tous les plats avec ses allergènes associés
 *  2. getPlatById : Retourner un plat par son id avec ses allergènes associés
 *  3. createPlat : Créer un nouveau plat avec ses allergènes associés
 *  4. updatePlat : Met à jour un plat en le ciblant par son id avec ses allergènes associés
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
  #[OA\Get(summary: 'Liste de tous les plats', description: 'Retourne tous les plats avec leurs allergènes associés. Réservé aux administrateurs.')]
  #[OA\Tag(name: 'Admin - Plats')]
  #[OA\Response(response: 200, description: 'Liste des plats retournée')]
  #[OA\Response(response: 403, description: 'Accès refusé')]
  public function getAllPlats(PlatRepository $platRepository): JsonResponse
  {
   // Étape 1 - Vérifier le rôle ADMIN
    if (!$this->isGranted('ROLE_ADMIN')) {
      return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
    }

    // Étape 2 - Récupérer tous les plats
    $plats = $platRepository->findAll();

    // Étape 3 - Formate les données
    $result = array_map(function($plat) {
      return [
        'id' => $plat->getId(),
        'titre_plat' => $plat->getTitrePlat(),
        'photo' => $plat->getPhoto(),
        'categorie' => $plat->getCategorie(),
        'description' => $plat->getDescriptionPlat(), // <-- ajout
        'allergenes' => array_map(fn($a) => [
          'id' => $a->getId(),
          'libelle' => $a->getLibelle()
        ], $plat->getAllergenes()->toArray())
      ];
    }, $plats);

    // Étape 3 - Retourner la liste en JSON
    return $this->json(['status' => 'Succès', 'total' => count($plats), 'plats' => $result]);
  }

  /**
   * @description Retourner un plat par son id avec ses allergènes associés
   * @param int $id L'id du plat
   * @param PlatRepository $platRepository Le repository des plats
   * @return JsonResponse
   */
  #[Route('/{id}', name: 'api_admin_plats_show', methods: ['GET'])]
  #[OA\Get(summary: 'Détail d\'un plat par ID', description: 'Retourne un plat avec ses allergènes associés. Réservé aux administrateurs.')]
  #[OA\Tag(name: 'Admin - Plats')]
  #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID du plat', schema: new OA\Schema(type: 'integer'))]
  #[OA\Response(response: 200, description: 'Plat trouvé')]
  #[OA\Response(response: 403, description: 'Accès refusé')]
  #[OA\Response(response: 404, description: 'Plat non trouvé')]
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

    // Étape 3 - Formate les données
    $result = [
      'id' => $plat->getId(),
      'titre_plat' => $plat->getTitrePlat(),
      'photo' => $plat->getPhoto(),
      'categorie' => $plat->getCategorie(),
      'description' => $plat->getDescriptionPlat(),
      'allergenes' => array_map(fn($a) => [
        'id' => $a->getId(),
        'libelle' => $a->getLibelle()
      ], $plat->getAllergenes()->toArray())
    ];

    // Étape 4 - Retourner le plat en JSON
    return $this->json(['status' => 'Succès', 'plat' => $result]);

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
  #[OA\Post(summary: 'Créer un plat', description: 'Crée un nouveau plat avec ses allergènes. Réservé aux administrateurs.')]
  #[OA\Tag(name: 'Admin - Plats')]
  #[OA\RequestBody(required: true, content: new OA\JsonContent(
  properties: [
    new OA\Property(property: 'titre_plat', type: 'string', example: 'Bœuf bourguignon'),
    new OA\Property(property: 'photo', type: 'string', example: 'boeuf.jpg'),
    new OA\Property(property: 'categorie', type: 'string', example: 'Plat', description: 'Entrée, Plat ou Dessert'),
    new OA\Property(property: 'allergenes', type: 'array', items: new OA\Items(type: 'integer'), example: '[1, 3]', description: 'IDs des allergènes (optionnel)'),
  ]
  ))]
  #[OA\Response(response: 201, description: 'Plat créé avec succès')]
  #[OA\Response(response: 400, description: 'Champs manquants ou catégorie invalide')]
  #[OA\Response(response: 403, description: 'Accès refusé')]
  #[OA\Response(response: 404, description: 'Allergène non trouvé')]
  #[OA\Response(response: 409, description: 'Titre déjà utilisé')]
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
    if (!is_array($data)) {
      return $this->json(['status'=>'Erreur','message'=>'JSON invalide'],400);
    } 

    // Étape 3 - Vérifier les champs obligatoires
    if (empty($data['titre_plat']) || empty($data['photo']) || empty($data['categorie'])) {
      return $this->json(['status' => 'Erreur', 'message' => 'certains champs sont manquant'], 400);
    }

    // Étape 4 - Vérifier que le titre n'existe pas déjà
    $existant = $platRepository->findOneBy(['titre_plat' => $data['titre_plat']]);
    if ($existant) {
      return $this->json(['status' => 'Erreur', 'message' => 'Un plat avec ce titre existe déjà'], 409);
    }

    // Étape 5 - Vérifier que la catégorie est valide
    $categoriesValides = ['Entrée', 'Plat', 'Dessert'];
    if (!in_array($data['categorie'], $categoriesValides)) {
      return $this->json(['status' => 'Erreur', 'message' => 'Catégorie invalide (Entrée, Plat, Dessert)'], 400);
    }

    // Étape 6 - Créer le plat
    $plat = new Plat();
    $plat->setTitrePlat($data['titre_plat']);
    $plat->setPhoto($data['photo']);
    $plat->setCategorie($data['categorie']);

    // Ajouter la description si fournie
    if (!empty($data['description'])) {
      $plat->setDescriptionPlat($data['description']);
    }

    // Étape 7 - Associer les allergènes si fournis
    if (!empty($data['allergenes']) && is_array($data['allergenes'])) {
      foreach ($data['allergenes'] as $allergeneId) {
        $allergene = $allergeneRepository->find($allergeneId);
        if (!$allergene) {
          return $this->json(['status' => 'Erreur', 'message' => "Allergène id $allergeneId non trouvé"], 404);
        }
        $plat->addAllergene($allergene);
      }
    }

    // Étape 8 - Persister et sauvegarder
    $em->persist($plat);
    $em->flush();

    // Étape 9 - Retourner une confirmation avec l'id créé
    return $this->json(['status' => 'Succès', 'message' => 'Plat créé avec succès', 'id' => $plat->getId()], 201);
  }

  /**
   * @description Met à jour un plat en le ciblant par son id avec ses allergènes associés
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
  #[OA\Put(summary: 'Modifier un plat', description: 'Met à jour un plat. Les allergènes envoyés REMPLACENT les anciens. Réservé aux administrateurs.')]
  #[OA\Tag(name: 'Admin - Plats')]
  #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID du plat', schema: new OA\Schema(type: 'integer'))]
  #[OA\RequestBody(required: true, content: new OA\JsonContent(
  properties: [
      new OA\Property(property: 'titre_plat', type: 'string', example: 'Nouveau titre'),
      new OA\Property(property: 'photo', type: 'string', example: 'photo.jpg'),
      new OA\Property(property: 'categorie', type: 'string', example: 'Dessert'),
      new OA\Property(property: 'allergenes', type: 'array', items: new OA\Items(type: 'integer'), example: '[1, 2]'),
  ]
  ))]
  #[OA\Response(response: 200, description: 'Plat mis à jour')]
  #[OA\Response(response: 400, description: 'Catégorie invalide')]
  #[OA\Response(response: 403, description: 'Accès refusé')]
  #[OA\Response(response: 404, description: 'Plat ou allergène non trouvé')]
  #[OA\Response(response: 409, description: 'Titre déjà utilisé')]
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

  // Étape 2 - Chercher le plat à modifier
  $plat = $platRepository->find($id);
  if (!$plat) {
    return $this->json(['status' => 'Erreur', 'message' => 'Plat non trouvé'], 404);
  }

  // Étape 3 - Récupérer les données JSON
  $data = json_decode($request->getContent(), true);
  if (!is_array($data)) {
    return $this->json(['status'=>'Erreur','message'=>'JSON invalide'],400);
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

  // Étape 6 - Mettre à jour la categorie
  if (isset($data['categorie'])) {
    $categoriesValides = ['Entrée', 'Plat', 'Dessert'];
    if (!in_array($data['categorie'], $categoriesValides)) {
        return $this->json(['status' => 'Erreur', 'message' => 'Catégorie invalide (Entrée, Plat, Dessert)'], 400);
    }
    $plat->setCategorie($data['categorie']);
  }

  // Étape 7 - Mise à jour de la description si fournie
  if (isset($data['description'])) {
      $plat->setDescriptionPlat($data['description']);
  }

  // Étape 8 - Synchroniser les allergènes si fournis
  // On retire tous les anciens et on remet les nouveaux
  if (isset($data['allergenes']) && is_array($data['allergenes'])) {
    foreach ($plat->getAllergenes() as $allergeneExistant) {
      $plat->removeAllergene($allergeneExistant);
    }
    foreach ($data['allergenes'] as $allergeneId) {
      $allergeneId = (int)$allergeneId;
      if ($allergeneId <= 0) continue;
      $allergene = $allergeneRepository->find($allergeneId);
      if ($allergene) {
        $plat->addAllergene($allergene);
      }
    }
  }

  // Étape 9 - Sauvegarder (pas besoin de persist() pour une mise à jour)
  $em->flush();

  // Étape 10 - Retourner une confirmation
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
  #[OA\Delete(summary: 'Supprimer un plat', description: 'Supprime un plat par son ID. Réservé aux administrateurs.')]
  #[OA\Tag(name: 'Admin - Plats')]
  #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID du plat', schema: new OA\Schema(type: 'integer'))]
  #[OA\Response(response: 200, description: 'Plat supprimé avec succès')]
  #[OA\Response(response: 403, description: 'Accès refusé')]
  #[OA\Response(response: 404, description: 'Plat non trouvé')]
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

    // Étape 3 - Protection du plat à supprimer
    if ($plat->getMenus()->count() > 0) {
      return $this->json([
        'status' => 'Erreur',
        'message' => 'Impossible de supprimer ce plat car il est utilisé dans un menu'
      ], 409);
    }
    // Étape 4 - Supprimer le plat
    $em->remove($plat);
    $em->flush();

    // Étape 5 - Retourner une confirmation
    return $this->json(['status' => 'Succès', 'message' => 'Plat supprimé avec succès']);
  }

  
}

