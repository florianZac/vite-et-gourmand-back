<?php

namespace App\Controller;

use App\Repository\HoraireRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @author : florian Aizac
 * @create  : 23/02/2026
 * @description Contrôleur gérant le retour des informations qui concerne les horaires d'ouverture de l'entreprise vite & gourmand.
 * index -> retourne tous les horaires sous forme de tableau JSON
 * show -> retourne un horaire d'ouverture de l'entreprise en fonction de son id sous forme de JSON ou une erreur 404 si l'horaire n'est pas trouvé
 * @param Request $request requête HTTP reçue
 * @param aucun
 * @return JsonResponse retourne la réponse JSON ou 404 si le horaire n'est pas trouvé 
*/

final class HoraireController extends AbstractController
{
  
    #[Route('/api/horaires', name: 'api_horaires', methods: ['GET'])]

    // équivalent de SELECT * FROM horaire tous les horaires.
    // permet de récupérer tous les horaires et de les retourner au format JSON
    public function indexHoraire(HoraireRepository $horaireRepository): JsonResponse
    {
        $horaires = $horaireRepository->findAll();
        return $this->json($horaires);
    }

    #[Route('/api/horaires/{id}', name: 'api_horaire_show', methods: ['GET'])]
    // Cette méthode séléctionne un horaire par son id meme chose que SELECT * FROM horaire WHERE horaire_id = :id
    // fonction qui permet de récupérer un horaire en fonction de son id et de le retourner au format JSON
    public function showHoraire(int $id, HoraireRepository $horaireRepository): JsonResponse
    {
        $horaire = $horaireRepository->find($id);
        // Si l'horaire n'existe pas, on retourne une réponse JSON avec un message d'erreur et un code HTTP 404 correspondant à Not Found
        if (!$horaire) {
            return $this->json(['message' => 'Horaire non trouvé'], 404);
        }
        // Si l'horaire est trouvé, on le retourne en JSON
        return $this->json($horaire);
    }
}
