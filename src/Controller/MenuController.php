<?php

namespace App\Controller;

use App\Entity\Avis;

use App\Repository\MenuRepository;
use App\Repository\CommandeRepository;
use App\Repository\UtilisateurRepository;
use App\Repository\AvisRepository;

use Doctrine\ORM\EntityManagerInterface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @author : florian Aizac
 * @create  : 23/02/2026
 * @description Contrôleur gérant les informations publics liés aux menus de l'application.
 * @param Request $request requête HTTP reçue
 * @param 
 * @return JsonResponse retourne la réponse JSON ou 404 si le menu n'est pas trouvé 
 *  1. index()   : Séléctionne tous les menus 
 *  2. show()    : Séléctionne un menu par son id 
 * 
 * 
*/
#[Route('/api')]
final class MenuController extends AbstractController
{
    // Séléctionne tous les menus 
    // meme chose que SELECT * FROM menu
    #[Route('/menus', name: 'api_menus', methods: ['GET'])]
    public function index(MenuRepository $menuRepository): JsonResponse
    {
        // Étape 1 - Récupere les menus par son id
        $menus = $menuRepository->findAll();

        // Étape 2 - Retourne le résultat
        return $this->json($menus);
    }

    // Séléctionne un menu par son id 
    // meme chose que SELECT * FROM menu WHERE menu_id = :id
    #[Route('/menus/{id}', name: 'api_menu_show', methods: ['GET'])]

    public function show(int $id, MenuRepository $menuRepository): JsonResponse
    {
        // Étape 1 - Récupere les menus par son id
        $menu = $menuRepository->find($id);
        // Étape 2 - Si le menu n'existe pas, on retourne une réponse JSON avec un message d'erreur et un code HTTP 404 correspondant à Not Found
        // Sinon le menu est trouvé, on le retourne
        if (!$menu) {
            return $this->json(['message' => 'Menu non trouvé'], 404);
        }
        // Étape 3 - retourne le résulat
        return $this->json($menu);
    }

    
    // =========================================================================
    // AVIS
    // =========================================================================

    /**
     * @description Récupère les avis validés publics pour le site
     * @return JsonResponse
     */
    #[Route('/avis', name: 'api_avis_public', methods: ['GET'])]
    public function getAvisValides(AvisRepository $avisRepository): JsonResponse
    {
        // Étape 1 - Récupérer uniquement les avis validés
        $avis = $avisRepository->findBy(['statut' => 'validé']);

        // Étape 2 - Retourner en JSON
        return $this->json(['status' => 'Succès', 'total' => count($avis), 'avis' => $avis]);
    }

}
