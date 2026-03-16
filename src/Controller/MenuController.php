<?php

namespace App\Controller;

use App\Repository\AllergeneRepository;
use App\Repository\AvisRepository;
use App\Repository\MenuRepository;
use App\Repository\PlatRepository;
use App\Repository\RegimeRepository;
use App\Repository\ThemeRepository;
use App\Repository\MenuTagsRepository;

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
 *  8. getAllPlats()        : Retourne la liste des plats.
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
	public function index(MenuRepository $menuRepository, MenuTagsRepository $menuTagsRepository): JsonResponse
	{
    // Étape 1 - Récupère tous les menus depuis la base de données
    $menus = $menuRepository->findAll();

    // Étape 2 - Formate les données pour chaque menus
    $result = [];
    foreach ($menus as $menu) {
      $platsArray = [];
      foreach ($menu->getPlats() as $plat) {
        $platsArray[] = [
          'id' => $plat->getId(),
          'titre' => $plat->getTitrePlat(),
          'categorie' => $plat->getCategorie(),
          'photo' => $plat->getPhoto(),
        ];
      }
      // Récupère les tags associés au menu **une seule fois par menu**
      $tagsArray = [];
      $tags = $menuTagsRepository->findBy(['menu' => $menu]);
      foreach ($tags as $menuTag) {
          $tagsArray[] = $menuTag->getTag();
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
        'plats' => $platsArray,
        'tags' => $tagsArray,
      ];
    }

    // Étape 3 - Retourne les résultats
    return $this->json(['status' => 'Succès', 'total' => count($menus), 'menus' => $result]);
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
	public function show(int $id, MenuRepository $menuRepository, MenuTagsRepository $menuTagsRepository): JsonResponse
	{
    // Étape 1 - Récupère le menu par son id
    $menu = $menuRepository->find($id);

    // Étape 2 - Si le menu n'existe pas retourner 404
    if (!$menu) {
      return $this->json(['message' => 'Menu non trouvé'], 404);
    }

    // Étape 3 - Formate les données du menu
    $platsArray = [];
    foreach ($menu->getPlats() as $plat) {
      $platsArray[] = [
        'id' => $plat->getId(),
        'titre' => $plat->getTitrePlat(),
        'categorie' => $plat->getCategorie(),
        'photo' => $plat->getPhoto(),
      ];
    }
    // Récupère les tags associés au menu
    $tagsArray = [];
    $tags = $menuTagsRepository->findBy(['menu' => $menu]);
    foreach ($tags as $menuTag) {
      $tagsArray[] = $menuTag->getTag();
    }

    $result = [
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
      'plats' => $platsArray,
      'tags' => $tagsArray,
    ];

    // Étape 4 - Retourne le menu
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

    // Étape 2 - Formate les données
    $result = array_map(fn($t) => [
      'id' => $t->getId(),
      'titre' => $t->getLibelle()
    ], $themes);

    // Étape 3 - Retourne le résultat au format JSON
    return $this->json(['status' => 'Succès', 'total' => count($themes), 'themes' => $result]);
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

    // Étape 2 - Formate les données
    $result = array_map(fn($r) => [
      'id' => $r->getId(),
      'libelle' => $r->getLibelle()
    ], $regimes);

    // Étape 3 - Retourne le résultat au format JSON
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

    // Étape 2 - Formate les données
    $result = array_map(fn($a) => [
      'id' => $a->getId(),
      'libelle' => $a->getLibelle()
    ], $allergenes);

    // Étape 3 - Retourne le résultat au format JSON
    return $this->json(['status' => 'Succès', 'total' => count($allergenes), 'allergenes' => $allergenes]);
	}

	// =========================================================================
	// AVIS
	// =========================================================================

	/**
	 * @description Retourne les avis clients Publié qui ont était validés pour affichage public sur le site
	 * Accessible publiquement sans authentification
	 * Seuls les avis au statut "validé" sont retournés (les avis en attente et refusés sont exclus)
	 * @param AvisRepository $avisRepository Le repository des avis
	 * @return JsonResponse la liste des avis Publié au format JSON
	 */
  #[Route('/avis', name: 'api_avis_public', methods: ['GET'])]
  #[OA\Get(
      summary: 'Récupérer les avis clients publiés',
      description: 'Retourne les derniers avis clients ayant le statut "Publié" pour affichage public.'
  )]
  #[OA\Tag(name: 'Public - Avis')]
  #[OA\Response(
      response: 200,
      description: 'Liste des avis publiés',
      content: new OA\JsonContent(
          type: "object",
          properties: [
              new OA\Property(property: "status", type: "string", example: "Succès"),
              new OA\Property(property: "total", type: "integer", example: 5),
              new OA\Property(
                  property: "avis",
                  type: "array",
                  items: new OA\Items(
                      type: "object",
                      properties: [
                          new OA\Property(property: "id", type: "integer", example: 57),
                          new OA\Property(property: "stars", type: "integer", example: 5),
                          new OA\Property(property: "text", type: "string", example: "Service impeccable et repas délicieux."),
                          new OA\Property(property: "author", type: "string", example: "Florian A."),
                          new OA\Property(property: "date", type: "string", example: "2026-03-11 21:10:30")
                      ]
                  )
              )
          ]
      )
  )]
	public function getAvisValides(AvisRepository $avisRepository): JsonResponse
	{
    // Étape 1 - Récupérer les 5 derniers avis au statut "Publié"
    $avis = $avisRepository->findAvis('Publié', 5);

    // Étape 2 - Conversion en tableau simple
    $data = array_map(function($user) {

    // Étape 3 - On récupère l'utilisateur 
    $utilisateur = $user->getUtilisateur();
    // Étape 4 - Si il existe on récupere la premiere lettre du lastname 
    // Ensuite on le récupere d'après l'utilisateur actuel est ensuite
    // On met en majuscule sa première lettre et l'ajoute à author. 
    if ($utilisateur) {
      $nomInitiale = strtoupper(substr($utilisateur->getNom(), 0, 1)) . '.'; // première lettre + point
      $prenom = $utilisateur->getPrenom();  // Récupération de l'user
      $author = $nomInitiale . ' ' . $prenom;
    } else {
      $author = 'Anonyme';
    }
    return [
        'id' => $user->getId(),
        'stars' => $user->getNote(),
        'text' => $user->getDescription(),
        'author' => $author,
        'date' => $user->getDate() ? $user->getDate()->format('Y-m-d H:i:s') : null,
      ];
    }, $avis);

    // Étape 2 - Retourner en JSON
    return $this->json([
        'status' => 'Succès',
        'total' => count($avis),
        'avis' => $data
    ]);
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

    // Étape 2 - Formate les données
    $result = array_map(fn($plat) => [
      'id' => $plat->getId(),
      'titre' => $plat->getTitrePlat(),
      'categorie' => $plat->getCategorie(),
      'photo' => $plat->getPhoto()
    ], $plats);

    // Étape 3 - Retourne le résultat au format JSON
    return $this->json(['status' => 'Succès', 'total' => count($plats), 'plats' => $result]);
  }

	/**
	 * @description Retourne la liste complète de tous les menus avec leurs plats et images
	 * Accessible publiquement sans authentification
	 * Utilisé pour afficher la grille de menus sur le front
	 * @param MenuRepository $menuRepository Le repository des menus
	 * @return JsonResponse la liste de tous les menus au format JSON
	 */
	#[Route('/menus/full', name: 'api_menus_public', methods: ['GET'])]
	#[OA\Get(summary: 'Liste de tous les menus', description: 'Retourne tous les menus disponibles avec leurs plats et leurs images. Accessible publiquement.')]
	#[OA\Tag(name: 'Public - Menus')]
	#[OA\Response(response: 200, description: 'Liste des menus retournée avec succès')]
  public function getAllMenus(MenuRepository $menuRepository, MenuTagsRepository $menuTagsRepository): JsonResponse
  {
    try {
      $menus = $menuRepository->findAll();
      $result = [];

      foreach ($menus ?? [] as $menu) {
        // Plats
        $platsArray = [];
        foreach ($menu->getPlats() ?? [] as $plat) {
          $platsArray[] = [
            'id' => $plat->getId() ?? 0,
            'titre' => $plat->getTitrePlat() ?? 'N/A',
            'photo' => $plat->getPhoto() ?? '',
            'categorie' => $plat->getCategorie() ?? 'Inconnu',
          ];
        }

        // Tags (sécurisé)
        $tagsArray = [];
        foreach ($menuTagsRepository->findBy(['menu' => $menu]) ?? [] as $menuTag) {
          if ($menuTag && $menuTag->getTag() !== null) {
            $tagsArray[] = [
              'id' => $menuTag->getId() ?? 0, // id du MenuTags
              'libelle' => $menuTag->getTag(), // string
            ];
          }
        }

        $result[] = [
          'id' => $menu->getId() ?? 0,
          'titre' => $menu->getTitre() ?? 'N/A',
          'description' => $menu->getDescription() ?? '',
          'prix_par_personne' => $menu->getPrixParPersonne() ?? 0,
          'nombre_personne_minimum' => $menu->getNombrePersonneMinimum() ?? 0,
          'quantite_restante' => $menu->getQuantiteRestante() ?? 0,
          'theme' => $menu->getTheme() ? [
              'id' => $menu->getTheme()->getId() ?? 0,
              'titre' => $menu->getTheme()->getLibelle() ?? 'N/A',
          ] : null,
          'regime' => $menu->getRegime() ? [
              'id' => $menu->getRegime()->getId() ?? 0,
              'libelle' => $menu->getRegime()->getLibelle() ?? 'N/A',
          ] : null,
          'plats' => $platsArray,
          'tags' => $tagsArray,
        ];
      }

      return $this->json([
          'status' => 'Succès',
          'total' => count($menus),
          'menus' => $result,
      ]);

    } catch (\Throwable $e) {
    return $this->json([
        'status' => 'Erreur',
        'message' => $e->getMessage(),
        'class' => get_class($e),
        'trace' => explode("\n", $e->getTraceAsString())
    ], 500);
    }
  }
  /**
   * @description Retourne le détail complet d'un menu par son ID, avec ses plats, images et catégories
   * Accessible publiquement sans authentification
   * Utilisé pour la fiche menu sur le front
   * @param int $id L'ID du menu à afficher
   * @param MenuRepository $menuRepository Le repository des menus
   * @return JsonResponse le menu trouvé ou 404 si non trouvé
   */
  #[Route('/menus/{id}/details', name: 'api_menu_public_show', methods: ['GET'])]
  #[OA\Get(
      summary: 'Détail complet d’un menu par ID',
      description: 'Retourne le détail complet d’un menu : plats, images, catégories, allergènes, tags, prix, quantité. Accessible publiquement.'
  )]
  #[OA\Tag(name: 'Public - Menus')]
  #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID du menu', schema: new OA\Schema(type: 'integer'))]
  #[OA\Response(response: 200, description: 'Menu trouvé')]
  #[OA\Response(response: 404, description: 'Menu non trouvé')]
  public function getMenuById(int $id, MenuRepository $menuRepository, MenuTagsRepository $menuTagsRepository): JsonResponse
  {
    // Étape 1 - Chercher le menu par son ID
    $menu = $menuRepository->find($id);
    if (!$menu) {
      return $this->json(['status' => 'Erreur', 'message' => 'Menu non trouvé'], 404);
    }

    // Étape 2 - Construire le tableau des plats avec images et catégories
    $platsArray = [];
    foreach ($menu->getPlats() as $plat) {
      $allergenesArray = [];
      foreach ($plat->getAllergenes() as $allergene) {
        $allergenesArray[] = [
          'id'      => $allergene->getId(),
          'libelle' => $allergene->getLibelle()
        ];
      }

      $platsArray[] = [
        'plat_id'   => $plat->getId(),
        'titre'     => $plat->getTitrePlat(),
        'image'     => $plat->getPhoto(),
        'categorie' => $plat->getCategorie(),
        'allergenes'=> $allergenesArray
      ];
    }

    // Étape 3 - Récupérer les tags du menu
    $tagsArray = [];
    $tags = $menuTagsRepository->findBy(['menu' => $menu]);
    foreach ($tags as $menuTag) {
      $tagsArray[] = $menuTag->getTag();
    }

    // Étape 4 - Construire le résultat final
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
      'plats'                  => $platsArray,
      'tags'                   => $tagsArray
    ];

    // Étape 5 - Retourner la réponse JSON
    return $this->json(['status' => 'Succès', 'menu' => $result]);
  }
}
