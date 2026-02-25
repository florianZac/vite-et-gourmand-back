<?php

namespace App\Controller;

use App\Repository\HoraireRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;


/**
 * @author : florian Aizac
 * @create  : 23/02/2026
 * @description Contrôleur gérant le retour des informations qui concerne les horaires d'ouverture de l'entreprise vite & gourmand.
 * index -> retourne tous les horaires sous forme de tableau JSON
 * show -> retourne un horaire d'ouverture de l'entreprise en fonction de son id sous forme de JSON ou une erreur 404 si l'horaire n'est pas trouvé
 * @param Request $request requête HTTP reçue
 * @param 
 * @return JsonResponse retourne la réponse JSON ou 404 si le horaire n'est pas trouvé 
*/

final class HoraireController extends AbstractController
{
  
    #[Route('/api/horaires', name: 'api_horaires', methods: ['GET'])]

    // équivalent de SELECT * FROM horaire tous les horaires.
    // permet de récupérer tous les horaires et de les retourner au format JSON
    public function indexHoraire(HoraireRepository $horaireRepository): JsonResponse
    {
        // Récupère tous les horaires à partir de la BDD et les retourne en JSON
        $horaires = $horaireRepository->findAll();
        // Mise en forme de la données
        // surout pour formater les heures d'ouverture et de fermeture au format 'H:i' (heures:minutes) avant de les retourner en JSON
        // car de base il retourne les heures au format DateTime
        // sa fait un rendue deguelasse 1970-01-01T09:00:00+00:00
        $data = array_map(function ($horaire) {

            // gestion du formatage et de null pour les heures d'ouvertures 
            if ($horaire->getHeureOuverture() === null) {
                $heureOuverture = 'Fermé';
            } else {
                $heureOuverture = $horaire->getHeureOuverture()->format('H:i');
            }
            // gestion du formatage et de null pour les heures de fermetures
            if ($horaire->getHeureFermeture() === null) {
                $heureFermeture = 'Fermé';
            } else {
                $heureFermeture = $horaire->getHeureFermeture()->format('H:i');
            }
            // Retourne le résulat de la mise en forme de la données
            return [
                'horaire_id' => $horaire->getId(),
                'jour' => $horaire->getJour(),
                'heureOuverture' => $heureOuverture,
                'heureFermeture' => $heureFermeture,
            ];
        }, $horaires);

        return $this->json($data);
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

    // Fonction permétant de récupérer un horaire en fonction de son index dans la liste des horaires et de le retourner au format JSON
    // l'index permet de cibler les ligne de table dans l'ordre de la BDD, index 1 = première ligne de la table, index 2 = deuxième ligne de la table etc...
    #[Route('/api/horairesIndexTable/{index}', name: 'api_horaire_index_table', methods: ['GET'])]
    public function getByIndex(int $index, HoraireRepository $horaireRepository): JsonResponse
    {
        // 1. Récupère tous les horaires
        $horaires = $horaireRepository->findAll();

        // 2. Retourne le nombre total de lignes
        $total = count($horaires);

        // 3. Vérifie que l'index demandé existe (commence à 1) et retourne le total de lignes.
        if ($index < 1 || $index > $total) {
            return $this->json([
                'message' => 'Index invalide',
                'total_lignes' => $total
            ], 404);
        }

        // 4. Cible la ligne par rapport à l'index (index 1 = position 0 dans le tableau)
        $horaire = $horaires[$index - 1];

        return $this->json([
            'total_lignes' => $total,
            'index'        => $index,
            'horaire_id'   => $horaire->getId(),
            'jour'         => $horaire->getJour(),
        ]);
    }

}
