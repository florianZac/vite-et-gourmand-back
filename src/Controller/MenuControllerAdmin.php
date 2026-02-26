<?php

namespace App\Controller;

use App\Entity\Menu;
use App\Repository\MenuRepository;
use App\Repository\PlatRepository;
use App\Repository\RegimeRepository;
use App\Repository\ThemeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @author      Florian Aizac
 * @created     25/02/2026
 * @description CRUD des menus (réservé aux administrateurs)
 * 
 *  1. getAllMenus : Retourne la liste de tous les menus avec leurs plats, régime et thème 
 *  2. getMenuById : Retourne un menu par son id avec ses plats, régime et thème
 *  3. createMenu  : Crée un nouveau menu avec son régime, son thème et ses plats associés
 *  4. updateMenu  : Met à jour un menu par son id
 *  5. deleteMenu  : Supprime un menu par son id
 * Corps JSON pour POST/PUT :
 * {
 *   "titre": "Menu Noël Prestige",
 *   "nombre_personne_minimum": 10,
 *   "prix_par_personne": 45.00,
 *   "description": "Un menu festif...",
 *   "quantite_restante": 50,
 *   "regime_id": 2,
 *   "theme_id": 3,
 *   "menus": [1, 4, 7] // tableau d'ids de Menus (optionnel)
 * }
 */

#[Route('/api/admin/menus')]
final class MenuControllerAdmin extends BaseController
{
    /**
     * @description Retourne la liste de tous les menus avec leurs plats, régime et thème
     * @param MenuRepository $menuRepository Le repository des menus
     * @return JsonResponse
     */
    #[Route('', name: 'api_admin_menus_list', methods: ['GET'])]
    public function getAllMenus(MenuRepository $menuRepository): JsonResponse
    {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer tous les menus
        $menus = $menuRepository->findAll();

        // Étape 3 - Retourner la liste en JSON
        return $this->json(['status' => 'Succès', 'total' => count($menus), 'menus' => $menus]);
    }

    /**
     * @description Retourne un menu par son id avec ses plats, régime et thème
     * @param int $id L'id du menu
     * @param MenuRepository $menuRepository Le repository des menus
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'api_admin_menus_show', methods: ['GET'])]
    public function getMenuById(int $id, MenuRepository $menuRepository): JsonResponse
    {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer le menu par son id
        $menu = $menuRepository->find($id);
        if (!$menu) {
            return $this->json(['status' => 'Erreur', 'message' => 'Menu non trouvé'], 404);
        }

        // Étape 3 - Retourner le menu en JSON
        return $this->json(['status' => 'Succès', 'menu' => $menu]);
    }

    /**
     * @description Crée un nouveau menu avec son régime, son thème et ses plats associés
     * @param Request $request La requête HTTP contenant les données au format JSON
     * @param MenuRepository $menuRepository Le repository des menus
     * @param PlatRepository $platRepository Le repository des plats
     * @param RegimeRepository $regimeRepository Le repository des régimes
     * @param ThemeRepository $themeRepository Le repository des thèmes
     * @param EntityManagerInterface $em L'EntityManager pour gérer les opérations de base de données
     * @return JsonResponse
     */
    #[Route('', name: 'api_admin_menus_create', methods: ['POST'])]
    public function createMenu(
        Request $request,
        MenuRepository $menuRepository,
        PlatRepository $platRepository,
        RegimeRepository $regimeRepository,
        ThemeRepository $themeRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer les données JSON
        $data = json_decode($request->getContent(), true);

        // Étape 3 - Vérifier les champs obligatoires
        $champsObligatoires = ['titre', 'nombre_personne_minimum', 'prix_par_personne', 'description', 'quantite_restante', 'regime_id', 'theme_id'];
        foreach ($champsObligatoires as $champ) {
            if (!isset($data[$champ]) || $data[$champ] === '') {
                return $this->json(['status' => 'Erreur', 'message' => "Le champ $champ est obligatoire"], 400);
            }
        }

        // Étape 4 - Vérifier que le titre n'existe pas déjà
        $existant = $menuRepository->findOneBy(['titre' => $data['titre']]);
        if ($existant) {
            return $this->json(['status' => 'Erreur', 'message' => 'Un menu avec ce titre existe déjà'], 409);
        }

        // Étape 5 - Récupérer le régime et le thème
        $regime = $regimeRepository->find($data['regime_id']);
        if (!$regime) {
            return $this->json(['status' => 'Erreur', 'message' => 'Régime non trouvé'], 404);
        }

        $theme = $themeRepository->find($data['theme_id']);
        if (!$theme) {
            return $this->json(['status' => 'Erreur', 'message' => 'Thème non trouvé'], 404);
        }

        // Étape 6 - Créer le menu
        $menu = new Menu();
        $menu->setTitre($data['titre']);
        $menu->setNombrePersonneMinimum((int) $data['nombre_personne_minimum']);
        $menu->setPrixParPersonne((float) $data['prix_par_personne']);
        $menu->setDescription($data['description']);
        $menu->setQuantiteRestante((int) $data['quantite_restante']);
        $menu->setRegime($regime);
        $menu->setTheme($theme);

        // Étape 7 - Associer les plats si fournis
        if (!empty($data['plats']) && is_array($data['plats'])) {
            foreach ($data['plats'] as $platId) {
                $plat = $platRepository->find($platId);
                if (!$plat) {
                    return $this->json(['status' => 'Erreur', 'message' => "Plat id $platId non trouvé"], 404);
                }
                $menu->addPlat($plat);
            }
        }

        // Étape 8 - Persister et sauvegarder
        $em->persist($menu);
        $em->flush();

        // Étape 9 - Retourner une confirmation avec l'id créé
        return $this->json(['status' => 'Succès', 'message' => 'Menu créé avec succès', 'id' => $menu->getId()], 201);
    }

    /**
     * @description Met à jour un menu par son id
     * Les plats envoyés REMPLACENT les anciens (synchronisation complète)
     * @param int $id L'id du menu à modifier
     * @param Request $request La requête HTTP contenant les données au format JSON
     * @param MenuRepository $menuRepository Le repository des menus
     * @param PlatRepository $platRepository Le repository des plats
     * @param RegimeRepository $regimeRepository Le repository des régimes
     * @param ThemeRepository $themeRepository Le repository des thèmes
     * @param EntityManagerInterface $em L'EntityManager pour gérer les opérations de base de données
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'api_admin_menus_update', methods: ['PUT'])]
    public function updateMenu(
        int $id,
        Request $request,
        MenuRepository $menuRepository,
        PlatRepository $platRepository,
        RegimeRepository $regimeRepository,
        ThemeRepository $themeRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer les données JSON
        $data = json_decode($request->getContent(), true);

        // Étape 3 - Chercher le menu à modifier
        $menu = $menuRepository->find($id);
        if (!$menu) {
            return $this->json(['status' => 'Erreur', 'message' => 'Menu non trouvé'], 404);
        }

        // Étape 4 - Mettre à jour les champs simples si fournis
        if (isset($data['titre'])) {
            $existant = $menuRepository->findOneBy(['titre' => $data['titre']]);
            if ($existant && $existant->getId() !== $menu->getId()) {
                return $this->json(['status' => 'Erreur', 'message' => 'Un menu avec ce titre existe déjà'], 409);
            }
            $menu->setTitre($data['titre']);
        }

        if (isset($data['nombre_personne_minimum'])) {
            $menu->setNombrePersonneMinimum((int) $data['nombre_personne_minimum']);
        }

        if (isset($data['prix_par_personne'])) {
            $menu->setPrixParPersonne((float) $data['prix_par_personne']);
        }

        if (isset($data['description'])) {
            $menu->setDescription($data['description']);
        }

        if (isset($data['quantite_restante'])) {
            $menu->setQuantiteRestante((int) $data['quantite_restante']);
        }

        // Étape 5 - Mettre à jour le régime si fourni
        if (isset($data['regime_id'])) {
            $regime = $regimeRepository->find($data['regime_id']);
            if (!$regime) {
                return $this->json(['status' => 'Erreur', 'message' => 'Régime non trouvé'], 404);
            }
            $menu->setRegime($regime);
        }

        // Étape 6 - Mettre à jour le thème si fourni
        if (isset($data['theme_id'])) {
            $theme = $themeRepository->find($data['theme_id']);
            if (!$theme) {
                return $this->json(['status' => 'Erreur', 'message' => 'Thème non trouvé'], 404);
            }
            $menu->setTheme($theme);
        }

        // Étape 7 - Synchroniser les plats si fournis
        // On retire tous les plats actuels et on remet les nouveaux
        if (isset($data['plats']) && is_array($data['plats'])) {
            foreach ($menu->getPlats() as $platExistant) {
                $menu->removePlat($platExistant);
            }
            foreach ($data['plats'] as $platId) {
                $plat = $platRepository->find($platId);
                if (!$plat) {
                    return $this->json(['status' => 'Erreur', 'message' => "Plat id $platId non trouvé"], 404);
                }
                $menu->addPlat($plat);
            }
        }

        // Étape 8 - Sauvegarder (pas besoin de persist() pour une mise à jour)
        $em->flush();

        // Étape 9 - Retourner une confirmation
        return $this->json(['status' => 'Succès', 'message' => 'Menu mis à jour avec succès']);
    }

    /**
     * @description Supprime un menu par son id
     * @param int $id L'id du menu à supprimer
     * @param MenuRepository $menuRepository Le repository des menus
     * @param EntityManagerInterface $em L'EntityManager pour gérer les opérations de base de données
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'api_admin_menus_delete', methods: ['DELETE'])]
    public function deleteMenu(int $id, MenuRepository $menuRepository, EntityManagerInterface $em): JsonResponse
    {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Chercher le menu à supprimer
        $menu = $menuRepository->find($id);
        if (!$menu) {
            return $this->json(['status' => 'Erreur', 'message' => 'Menu non trouvé'], 404);
        }

        // Étape 3 - Supprimer le menu
        $em->remove($menu);
        $em->flush();

        // Étape 4 - Retourner une confirmation
        return $this->json(['status' => 'Succès', 'message' => 'Menu supprimé avec succès']);
    }
}