<?php

namespace App\Controller;

use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @author      Florian Aizac
 * @created     25/02/2026
 * @description Contrôleur gérant les opérations sur les commandes côté administrateur
 *  1. getAllAllergenes : Annule une commande avec remboursement 100%
 *  2. getAllergeneById : Lliste de toutes les commandes
 *  3. createAllergene :  Affiche une commande par son id
 */
#[Route('/api/admin/commandes')]
final class CommandeController extends BaseController
{
    /**
     * @description Retourne la liste de toutes les commandes
     * @param CommandeRepository $commandeRepository Le repository des commandes
     * @return JsonResponse
     */
    #[Route('', name: 'api_admin_commandes_list', methods: ['GET'])]
    public function getAllCommandes(CommandeRepository $commandeRepository): JsonResponse
    {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer toutes les commandes
        $commandes = $commandeRepository->findAll();

        // Étape 3 - Retourner la liste en JSON
        return $this->json(['status' => 'Succès', 'total' => count($commandes), 'commandes' => $commandes]);
    }

    /**
     * @description Retourne une commande par son id
     * @param int $id L'id de la commande
     * @param CommandeRepository $commandeRepository Le repository des commandes
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'api_admin_commandes_show', methods: ['GET'])]
    public function getCommandeById(int $id, CommandeRepository $commandeRepository): JsonResponse
    {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer la commande par son id
        $commande = $commandeRepository->find($id);
        if (!$commande) {
            return $this->json(['status' => 'Erreur', 'message' => 'Commande non trouvée'], 404);
        }

        // Étape 3 - Retourner la commande en JSON
        return $this->json(['status' => 'Succès', 'commande' => $commande]);
    }

    /**
     * @description Annule une commande avec un remboursement de 100% du montant total (prix menu + livraison)
     * Peu importe la date de prestation, le remboursement est toujours intégral.
     * Corps JSON attendu  : { "motif_annulation": "Rupture de stock " }
     * @param int $id L'id de la commande à annuler
     * @param Request $request La requête HTTP contenant le motif d'annulation
     * @param CommandeRepository $commandeRepository Le repository des commandes
     * @param EntityManagerInterface $em L'EntityManager pour gérer les opérations de base de données
     * @return JsonResponse
     */
    #[Route('/{id}/annuler', name: 'api_admin_commandes_annuler', methods: ['PUT'])]
    public function annulerCommande(
        int $id,
        Request $request,
        CommandeRepository $commandeRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer la commande par son id
        $commande = $commandeRepository->find($id);
        if (!$commande) {
            return $this->json(['status' => 'Erreur', 'message' => 'Commande non trouvée'], 404);
        }

        // Étape 3 - Vérifier que la commande n'est pas déjà annulée
        if ($commande->getStatut() === 'annulée') {
            return $this->json(['status' => 'Erreur', 'message' => 'Cette commande est déjà annulée'], 400);
        }

        // Étape 4 - Récupérer le motif d'annulation depuis le corps de la requête (optionnel)
        $data = json_decode($request->getContent(), true);
        $motif = $data['motif_annulation'] ?? 'Annulée par l\'administrateur';

        // Étape 5 - Calculer le montant remboursé (100% du total : prix menu + livraison)
        $montantRembourse = $commande->getPrixMenu() + $commande->getPrixLivraison();

        // Étape 6 - Mettre à jour la commande
        $commande->setStatut('annulée');
        $commande->setMotifAnnulation($motif);
        $commande->setMontantRembourse($montantRembourse);

        // Étape 7 - Sauvegarder en base
        $em->flush();

        // Étape 8 - Retourner une confirmation avec le détail du remboursement
        return $this->json([
            'status'           => 'Succès',
            'message'          => 'Commande annulée avec succès',
            'numero_commande'  => $commande->getNumeroCommande(),
            'motif_annulation' => $commande->getMotifAnnulation(),
            'montant_rembourse' => $montantRembourse,
        ]);
    }
}