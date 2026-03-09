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
 *  1. index()          : Retourne la liste de tous les menus
 *  2. show()           : Retourne un menu par son id
 *  3. getAllThemes()    : Retourne la liste de tous les thèmes   (utilisé pour les filtres front)
 *  4. getAllRegimes()   : Retourne la liste de tous les régimes  (utilisé pour les filtres front)
 *  5. getAllAllergenes(): Retourne la liste de tous les allergènes (affiché sur les fiches menus)
 *  6. getAllPlats()     : Retourne la liste de tous les plats    (affiché sur les fiches menus)
 *  7. getAvisValides() : Retourne les avis clients validés
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

    /**
     * @description Retourne la liste de tous les régimes disponibles
     * Accessible publiquement sans authentification
     * Utilisé par les filtres de la page "Nos Menus" (Végétarien, Vegan, Classique, Carnivore)
     * @param RegimeRepository $regimeRepository Le repository des régimes
     * @return JsonResponse la liste de tous les régimes au format JSON
     */
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
    // PLATS
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
}
