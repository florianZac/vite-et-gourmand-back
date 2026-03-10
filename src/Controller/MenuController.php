<?php

namespace App\Controller;

use App\Repository\AllergeneRepository;
use App\Repository\AvisRepository;
use App\Repository\MenuRepository;
use App\Repository\PlatRepository;
use App\Repository\RegimeRepository;
use App\Repository\ThemeRepository;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

/**
 * @author      Florian Aizac
 * @created     23/02/2026
 * @description Contrôleur gérant les informations publiques accessibles sans authentification.
 *              Toutes les routes de ce contrôleur sont ouvertes au public (visiteurs non connectés).
 *
 *  1. index()              : Retourne la liste de tous les menus
 *  2. show()               : Retourne le détail brut d’un menu par son ID
 *  3. getMenuPlatsImages() : Retourne uniquement les images et catégories des plats d’un menu ciblé pour le carrousel d’images sur le front
 *  4. getAllThemes()       : Retourne la liste de tous les thèmes utilisé pour les filtres front.
 *  5. getAllRegimes()      : Retourne la liste de tous les régimes utilisé pour les filtres front.
 *  6. getAllAllergenes()   : Retourne la liste de tous les allergènes affiché sur les fiches menus.
 *  7. getAvisValides()     : Retourne les avis clients validés affichage public.
 *  8. getAllPlats()        : Retourne les avis clients validés (affichage public).
 *  9. getAllMenus()        : Retourne la liste complète de tous les menus avec leurs plats et images, structuré pour le front. 
 */
#[Route('/api')]
final class MenuController extends AbstractController
{
	// =========================================================================
	// MENUS
	// =========================================================================

	/**
	 * @description Retourne la liste de tous les menus disponibles
	 * Accessible publiquement sans authentification
	 * Utilisé par la page "Nos Menus" pour afficher la grille de menus avec filtres
	 * @param MenuRepository $menuRepository Le repository des menus
	 * @return JsonResponse la liste de tous les menus au format JSON
	 */
	// Sélectionne tous les menus
	// équivalent de SELECT * FROM menu
	#[Route('/menus', name: 'api_menus', methods: ['GET'])]
	#[OA\Get(summary: 'Liste de tous les menus', description: 'Retourne la liste complète des menus disponibles. Accessible publiquement sans authentification.')]
	#[OA\Tag(name: 'Public - Menus')]
	#[OA\Response(response: 200, description: 'Liste des menus retournée avec succès')]
	public function index(MenuRepository $menuRepository): JsonResponse
	{
    // Étape 1 - Récupère tous les menus depuis la base de données
    $menus = $menuRepository->findAll();

    // Étape 2 - Retourne le résultat au format JSON
    return $this->json($menus);
	}

	/**
	 * @description Retourne le détail d'un menu par son id
	 * Accessible publiquement sans authentification
	 * Utilisé par la page fiche menu (composition, allergènes, prix, galerie photos)
	 * @param int $id L'id du menu à afficher
	 * @param MenuRepository $menuRepository Le repository des menus
	 * @return JsonResponse le menu trouvé ou 404 si non trouvé
	 */
	// Sélectionne un menu par son id
	// équivalent de SELECT * FROM menu WHERE menu_id = :id
	#[Route('/menus/{id}', name: 'api_menu_show', methods: ['GET'])]
	#[OA\Get(summary: 'Détail d\'un menu par ID', description: 'Retourne le détail complet d\'un menu (composition, allergènes, prix, galerie photos). Accessible publiquement.')]
	#[OA\Tag(name: 'Public - Menus')]
	#[OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID du menu', schema: new OA\Schema(type: 'integer'))]
	#[OA\Response(response: 200, description: 'Menu trouvé')]
	#[OA\Response(response: 404, description: 'Menu non trouvé')]
	public function show(int $id, MenuRepository $menuRepository): JsonResponse
	{
    // Étape 1 - Récupère le menu par son id
    $menu = $menuRepository->find($id);

    // Étape 2 - Si le menu n'existe pas retourner 404
    if (!$menu) {
        return $this->json(['message' => 'Menu non trouvé'], 404);
    }

    // Étape 3 - Retourne le menu trouvé
    return $this->json($menu);
	}

  /**
   * @description Retourne le détail complet d'un menu par son ID, avec ses plats, images et catégories
   * Accessible publiquement sans authentification
   * Utilisé pour la fiche menu sur le front
   * @param int $id L'ID du menu à afficher
   * @param MenuRepository $menuRepository Le repository des menus
   * @return JsonResponse le menu trouvé ou 404 si non trouvé
   */
  #[Route('/menus/{id}', name: 'api_menu_public_show', methods: ['GET'])]
  #[OA\Get(
      summary: 'Détail d\'un menu par ID',
      description: 'Retourne le détail complet d\'un menu : plats, images, catégorie, prix, quantité. Accessible publiquement.'
  )]
  #[OA\Tag(name: 'Public - Menus')]
  #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID du menu', schema: new OA\Schema(type: 'integer'))]
  #[OA\Response(response: 200, description: 'Menu trouvé')]
  #[OA\Response(response: 404, description: 'Menu non trouvé')]
  public function getMenuById(int $id, MenuRepository $menuRepository): JsonResponse
  {
    // Étape 1 - Chercher le menu par son ID
    $menu = $menuRepository->find($id);
    if (!$menu) {
        return $this->json(['status' => 'Erreur', 'message' => 'Menu non trouvé'], 404);
    }

    // Étape 2 - Construire le tableau des plats avec images et catégories
    $platsArray = [];
    foreach ($menu->getPlats() as $plat) {
      $platsArray[] = [
        'plat_id'  => $plat->getId(),
        'titre'    => $plat->getTitrePlat(),
        'image'    => $plat->getPhoto(),       // image unique du plat
        'categorie'=> $plat->getCategorie(),   // entrée / plat / dessert
      ];
    }

    // Étape 3 - Construire le résultat final
    $result = [
      'id'                     => $menu->getId(),
      'titre'                  => $menu->getTitre(),
      'description'            => $menu->getDescription(),
      'prix_par_personne'      => $menu->getPrixParPersonne(),
      'nombre_personne_minimum'=> $menu->getNombrePersonneMinimum(),
      'quantite_restante'      => $menu->getQuantiteRestante(),
      'theme'                  => $menu->getTheme() ? [
          'id'    => $menu->getTheme()->getId(),
          'titre' => $menu->getTheme()->getLibelle()
      ] : null,
      'regime'                 => $menu->getRegime() ? [
        'id'     => $menu->getRegime()->getId(),
        'libelle'=> $menu->getRegime()->getLibelle()
      ] : null,
      'plats'                  => $platsArray
    ];

    // Étape 4 - Retourner la réponse JSON
    return $this->json(['status' => 'Succès', 'menu' => $result]);
  }

  /**
   * @description Retourne uniquement les images et catégories des plats d'un menu
   * Accessible publiquement sans authentification
   * Utile pour afficher le carrousel d'images d'un menu sur le front
   * @param int $id L'ID du menu ciblé
   * @param MenuRepository $menuRepository Le repository des menus
   * @return JsonResponse tableau des plats avec image et catégorie ou 404 si menu non trouvé
   */
  #[Route('/menus/{id}/plats/images', name: 'api_menu_plats_images', methods: ['GET'])]
  #[OA\Get(
      summary: 'Images et catégories des plats d’un menu',
      description: 'Retourne uniquement les images et catégories des plats d’un menu ciblé. Accessible publiquement.'
  )]
  #[OA\Tag(name: 'Public - Menus')]
  #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID du menu', schema: new OA\Schema(type: 'integer'))]
  #[OA\Response(response: 200, description: 'Liste des images des plats retournée')]
  #[OA\Response(response: 404, description: 'Menu non trouvé')]
  public function getMenuPlatsImages(int $id, MenuRepository $menuRepository): JsonResponse
  {
    // Étape 1 - Chercher le menu
    $menu = $menuRepository->find($id);
    if (!$menu) {
        return $this->json(['status' => 'Erreur', 'message' => 'Menu non trouvé'], 404);
    }

    // Étape 2 - Construire le tableau des images et catégories
    $imagesArray = [];
    foreach ($menu->getPlats() as $plat) {
      $imagesArray[] = [
        'plat_id'  => $plat->getId(),
        'titre'    => $plat->getTitrePlat(),
        'image'    => $plat->getPhoto(),      // image unique du plat
        'categorie'=> $plat->getCategorie(),  // entrée / plat / dessert
      ];
    }

    // Étape 3 - Retourner la réponse JSON
    return $this->json([
      'status' => 'Succès',
      'menu_id' => $menu->getId(),
      'plats_images' => $imagesArray
    ]);
  }


	// =========================================================================
	// THEMES
	// =========================================================================

	/**
	 * @description Retourne la liste de tous les thèmes disponibles
	 * Accessible publiquement sans authentification
	 * Utilisé par les filtres de la page "Nos Menus" (Noël, Pâques, Classique, Événement)
	 * @param ThemeRepository $themeRepository Le repository des thèmes
	 * @return JsonResponse la liste de tous les thèmes au format JSON
	 */
	#[Route('/themes', name: 'api_themes_list', methods: ['GET'])]
	#[OA\Get(summary: 'Liste de tous les thèmes', description: 'Retourne les thèmes disponibles (Noël, Pâques, Classique, Événement). Utilisé par les filtres de la page "Nos Menus".')]
	#[OA\Tag(name: 'Public - Référentiels')]
	#[OA\Response(response: 200, description: 'Liste des thèmes retournée')]
	public function getAllThemes(ThemeRepository $themeRepository): JsonResponse
	{
    // Étape 1 - Récupère tous les thèmes depuis la base de données
    $themes = $themeRepository->findAll();

    // Étape 2 - Retourne le résultat au format JSON
    return $this->json(['status' => 'Succès', 'total' => count($themes), 'themes' => $themes]);
	}

	// =========================================================================
	// REGIMES
	// =========================================================================

	#[Route('/regimes', name: 'api_regimes_list', methods: ['GET'])]
	#[OA\Get(summary: 'Liste de tous les régimes', description: 'Retourne les régimes disponibles (Végétarien, Vegan, Classique, Carnivore). Utilisé par les filtres de la page "Nos Menus".')]
	#[OA\Tag(name: 'Public - Référentiels')]
	#[OA\Response(response: 200, description: 'Liste des régimes retournée')]
	public function getAllRegimes(RegimeRepository $regimeRepository): JsonResponse
	{
    // Étape 1 - Récupère tous les régimes depuis la base de données
    $regimes = $regimeRepository->findAll();

    // Étape 2 - Retourne le résultat au format JSON
    return $this->json(['status' => 'Succès', 'total' => count($regimes), 'regimes' => $regimes]);
	}

	// =========================================================================
	// ALLERGENES
	// =========================================================================

	/**
	 * @description Retourne la liste de tous les allergènes
	 * Accessible publiquement sans authentification
	 * Utilisé sur les fiches menus pour afficher les allergènes de chaque plat (Lait, Gluten, Œufs...)
	 * @param AllergeneRepository $allergeneRepository Le repository des allergènes
	 * @return JsonResponse la liste de tous les allergènes au format JSON
	 */
	#[Route('/allergenes', name: 'api_allergenes_list', methods: ['GET'])]
	#[OA\Get(summary: 'Liste de tous les allergènes', description: 'Retourne les allergènes disponibles (Lait, Gluten, Œufs...). Affiché sur les fiches menus.')]
	#[OA\Tag(name: 'Public - Référentiels')]
	#[OA\Response(response: 200, description: 'Liste des allergènes retournée')]
	public function getAllAllergenes(AllergeneRepository $allergeneRepository): JsonResponse
	{
    // Étape 1 - Récupère tous les allergènes depuis la base de données
    $allergenes = $allergeneRepository->findAll();

    // Étape 2 - Retourne le résultat au format JSON
    return $this->json(['status' => 'Succès', 'total' => count($allergenes), 'allergenes' => $allergenes]);
	}

	// =========================================================================
	// AVIS
	// =========================================================================

	/**
	 * @description Retourne les avis clients validés pour affichage public sur le site
	 * Accessible publiquement sans authentification
	 * Seuls les avis au statut "validé" sont retournés (les avis en attente et refusés sont exclus)
	 * @param AvisRepository $avisRepository Le repository des avis
	 * @return JsonResponse la liste des avis validés au format JSON
	 */
	#[Route('/avis', name: 'api_avis_public', methods: ['GET'])]
	#[OA\Get(summary: 'Avis clients validés', description: 'Retourne uniquement les avis au statut "validé" pour affichage public sur le site.')]
	#[OA\Tag(name: 'Public - Avis')]
	#[OA\Response(response: 200, description: 'Liste des avis validés retournée')]
	public function getAvisValides(AvisRepository $avisRepository): JsonResponse
	{
    // Étape 1 - Récupérer uniquement les avis validés
    $avis = $avisRepository->findBy(['statut' => 'validé']);

    // Étape 2 - Retourner en JSON
    return $this->json(['status' => 'Succès', 'total' => count($avis), 'avis' => $avis]);
	}

	// =========================================================================
	// PLAT
	// =========================================================================

	/**
	 * @description Retourne la liste de tous les plats
	 * Accessible publiquement sans authentification
	 * Utilisé sur les fiches menus pour afficher la composition (Entrée, Plat, Dessert)
	 * @param PlatRepository $platRepository Le repository des plats
	 * @return JsonResponse la liste de tous les plats au format JSON
	 */
	#[Route('/plats', name: 'api_plats_list', methods: ['GET'])]
	#[OA\Get(summary: 'Liste de tous les plats', description: 'Retourne les plats disponibles (Entrée, Plat, Dessert). Affiché dans la composition des menus.')]
	#[OA\Tag(name: 'Public - Référentiels')]
	#[OA\Response(response: 200, description: 'Liste des plats retournée')]
	public function getAllPlats(PlatRepository $platRepository): JsonResponse
	{
    // Étape 1 - Récupère tous les plats depuis la base de données
    $plats = $platRepository->findAll();

    // Étape 2 - Retourne le résultat au format JSON
    return $this->json(['status' => 'Succès', 'total' => count($plats), 'plats' => $plats]);
	}

	/**
	 * @description Retourne la liste complète de tous les menus avec leurs plats et images
	 * Accessible publiquement sans authentification
	 * Utilisé pour afficher la grille de menus sur le front
	 * @param MenuRepository $menuRepository Le repository des menus
	 * @return JsonResponse la liste de tous les menus au format JSON
	 */
	#[Route('/menus', name: 'api_menus_public', methods: ['GET'])]
	#[OA\Get(summary: 'Liste de tous les menus', description: 'Retourne tous les menus disponibles avec leurs plats et leurs images. Accessible publiquement.')]
	#[OA\Tag(name: 'Public - Menus')]
	#[OA\Response(response: 200, description: 'Liste des menus retournée avec succès')]
	public function getAllMenus(MenuRepository $menuRepository): JsonResponse
	{
		// Étape 1 - Récupérer tous les menus depuis la base
		$menus = $menuRepository->findAll();

		// Étape 2 - construction d'un tableau JSON structuré pour le front
		$result = [];
		foreach ($menus as $menu) {
      $platsArray = [];
      foreach ($menu->getPlats() as $plat) {
        $platsArray[] = [
          'id' => $plat->getId(),
          'titre' => $plat->getTitrePlat(),
          'photo' => $plat->getPhoto(),
          'categorie' => $plat->getCategorie()
        ];
      }

      $result[] = [
        'id' => $menu->getId(),
        'titre' => $menu->getTitre(),
        'description' => $menu->getDescription(),
        'prix_par_personne' => $menu->getPrixParPersonne(),
        'nombre_personne_minimum' => $menu->getNombrePersonneMinimum(),
        'quantite_restante' => $menu->getQuantiteRestante(),
        'theme' => $menu->getTheme() ? [
            'id' => $menu->getTheme()->getId(),
            'titre' => $menu->getTheme()->getLibelle()
        ] : null,
        'regime' => $menu->getRegime() ? [
            'id' => $menu->getRegime()->getId(),
            'libelle' => $menu->getRegime()->getLibelle()
        ] : null,

        'plats' => $platsArray
      ];
		}

		// Étape 3 - Retourner la réponse JSON
		return $this->json(['status' => 'Succès', 'total' => count($menus), 'menus' => $result]);
	}

}
