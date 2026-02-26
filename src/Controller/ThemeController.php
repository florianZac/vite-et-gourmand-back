<?php

namespace App\Controller;

use App\Entity\Theme;
use App\Repository\ThemeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @author      Florian Aizac
 * @created     25/02/2026
 * @description CRUD des thèmes (réservé aux administrateurs)
 * 
 *  1. getAllThemes : Retourner la liste de tous les thèmes 
 *  2. getThemeById : Retourner un thèmes en le ciblant par son id
 *  3. createTheme  : Création d'un thèmes
 *  4. updateTheme : Met à jour un thème en le ciblant par son id
 *  5. deleteTheme : Supprimer un thèmes en le ciblant par son id 
 */

#[Route('/api/admin/themes')]
final class ThemeController extends BaseController
{
    /**
     * @description Retourner la liste de tous les thèmes
     * @param ThemeRepository $themeRepository Le repository des thèmes
     * @return JsonResponse
     */
    #[Route('', name: 'api_admin_themes_list', methods: ['GET'])]
    public function getAllThemes(ThemeRepository $themeRepository): JsonResponse
    {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer tous les thèmes
        $themes = $themeRepository->findAll();

        // Étape 3 - Retourner la liste en JSON
        return $this->json(['status' => 'Succès', 'total' => count($themes), 'themes' => $themes]);
    }

    /**
     * @description Retourner un thèmes en le ciblant par son id
     * @param int $id L'id du thème
     * @param ThemeRepository $themeRepository Le repository des thèmes
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'api_admin_themes_show', methods: ['GET'])]
    public function getThemeById(int $id, ThemeRepository $themeRepository): JsonResponse
    {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer le thème par son id
        $theme = $themeRepository->find($id);
        if (!$theme) {
            return $this->json(['status' => 'Erreur', 'message' => 'Thème non trouvé'], 404);
        }

        // Étape 3 - Retourner le thème en JSON
        return $this->json(['status' => 'Succès', 'theme' => $theme]);
    }

    /**
     * @description Création d'un thèmes
     * Corps JSON attendu : { "libelle": "Noël" }
     * @param Request $request La requête HTTP contenant les données au format JSON
     * @param ThemeRepository $themeRepository Le repository des thèmes
     * @param EntityManagerInterface $em L'EntityManager pour gérer les opérations de base de données
     * @return JsonResponse
     */
    #[Route('', name: 'api_admin_themes_create', methods: ['POST'])]
    public function createTheme(Request $request, ThemeRepository $themeRepository, EntityManagerInterface $em): JsonResponse
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
        $existant = $themeRepository->findOneBy(['libelle' => $data['libelle']]);
        if ($existant) {
            return $this->json(['status' => 'Erreur', 'message' => 'Ce thème existe déjà'], 409);
        }

        // Étape 5 - Création d'un nouveau thème
        $theme = new Theme();
        $theme->setLibelle($data['libelle']);
        $em->persist($theme);
        $em->flush();

        // Étape 6 - Retourner une confirmation avec l'id créé
        return $this->json(['status' => 'Succès', 'message' => 'Thème créé avec succès', 'id' => $theme->getId()], 201);
    }

    /**
     * @description Met à jour un thème en le ciblant par son id
     * Corps JSON attendu : { "libelle": "Mariage" }
     * @param int $id L'id du thème à modifier
     * @param Request $request La requête HTTP contenant les données au format JSON
     * @param ThemeRepository $themeRepository Le repository des thèmes
     * @param EntityManagerInterface $em L'EntityManager pour gérer les opérations de base de données
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'api_admin_themes_update', methods: ['PUT'])]
    public function updateTheme(int $id, Request $request, ThemeRepository $themeRepository, EntityManagerInterface $em): JsonResponse
    {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer les données JSON
        $data = json_decode($request->getContent(), true);

        // Étape 3 - Chercher le thème à modifier
        $theme = $themeRepository->find($id);
        if (!$theme) {
            return $this->json(['status' => 'Erreur', 'message' => 'Thème non trouvé'], 404);
        }

        // Étape 4 - Mettre à jour le libellé si fourni
        if (isset($data['libelle'])) {
            $existant = $themeRepository->findOneBy(['libelle' => $data['libelle']]);
            if ($existant && $existant->getId() !== $theme->getId()) {
                return $this->json(['status' => 'Erreur', 'message' => 'Ce libellé est déjà utilisé'], 409);
            }
            $theme->setLibelle($data['libelle']);
        }

        // Étape 5 - Sauvegarder (pas besoin de persist() pour une mise à jour)
        $em->flush();

        // Étape 6 - Retourner une confirmation
        return $this->json(['status' => 'Succès', 'message' => 'Thème mis à jour avec succès']);
    }

    /**
     * @description Supprimer un thèmes en le ciblant par son id 
     * @param int $id L'id du thème à supprimer
     * @param ThemeRepository $themeRepository Le repository des thèmes
     * @param EntityManagerInterface $em L'EntityManager pour gérer les opérations de base de données
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'api_admin_themes_delete', methods: ['DELETE'])]
    public function deleteTheme(int $id, ThemeRepository $themeRepository, EntityManagerInterface $em): JsonResponse
    {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Chercher le thème à supprimer
        $theme = $themeRepository->find($id);
        if (!$theme) {
            return $this->json(['status' => 'Erreur', 'message' => 'Thème non trouvé'], 404);
        }

        // Étape 3 - Supprimer le thème
        $em->remove($theme);
        $em->flush();

        // Étape 4 - Retourner une confirmation
        return $this->json(['status' => 'Succès', 'message' => 'Thème supprimé avec succès']);
    }
}
