<?php

namespace App\Controller;

use App\Repository\HoraireRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

/**
 * @author : florian Aizac
 * @create  : 23/02/2026
 * @description Contrôleur gérant le retour des informations qui concerne les horaires d'ouverture de l'entreprise vite & gourmand.
 * 
 *  1. getHoraire()      : Afficher la liste des horaires d'ouverture de l'entreprise vite & gourmand.
*/
#[Route('/api')]
final class HoraireController extends AbstractController
{
    // Afficher la liste des horaires d'ouverture de l'entreprise vite & gourmand.
    #[Route('/horaires', name: 'api_horaires', methods: ['GET'])]
    #[OA\Get(
        summary: 'Liste des horaires d\'ouverture',
        description: 'Retourne les horaires d\'ouverture du restaurant pour chaque jour de la semaine. Accessible publiquement. Les heures sont formatées en HH:MM, "Fermé" si le restaurant est fermé ce jour.'
    )]
    #[OA\Tag(name: 'Public - Horaires')]
    #[OA\Response(
        response: 200,
        description: 'Liste des horaires retournée',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'horaire_id', type: 'integer', example: 1),
                    new OA\Property(property: 'jour', type: 'string', example: 'Lundi'),
                    new OA\Property(property: 'heureOuverture', type: 'string', example: '09:00'),
                    new OA\Property(property: 'heureFermeture', type: 'string', example: '18:00'),
                ]
            )
        )
    )]

    // équivalent de SELECT * FROM horaire tous les horaires.
    // permet de récupérer tous les horaires et de les retourner au format JSON
    public function getHoraire(HoraireRepository $horaireRepository): JsonResponse
    {
        // Étape 1 - Récupère tous les horaires à partir de la BDD et les retourne en JSON
        $horaires = $horaireRepository->findAll();

        // Étape 2 - Mise en forme de la données
        // surout pour formater les heures d'ouverture et de fermeture au format 'H:i' (heures:minutes) avant de les retourner en JSON
        // car de base il retourne les heures au format DateTime
        // sa fait un rendue deguelasse 1970-01-01T09:00:00+00:00
        $data = array_map(function ($horaire) {

            // Étape 2.1 - gestion du formatage et de null pour les heures d'ouvertures 
            if ($horaire->getHeureOuverture() === null) {
                $heureOuverture = 'Fermé';
            } else {
                $heureOuverture = $horaire->getHeureOuverture()->format('H:i');
            }
            // Étape 2.2 - gestion du formatage et de null pour les heures de fermetures
            if ($horaire->getHeureFermeture() === null) {
                $heureFermeture = 'Fermé';
            } else {
                $heureFermeture = $horaire->getHeureFermeture()->format('H:i');
            }
            // Étape 2.3 - Retourne le résulat de la mise en forme de la données
            return [
                'horaire_id' => $horaire->getId(),
                'jour' => $horaire->getJour(),
                'heureOuverture' => $heureOuverture,
                'heureFermeture' => $heureFermeture,
            ];
        }, $horaires);

        // Étape 3 - Retourne le résultat
        return $this->json($data);
    }
}
