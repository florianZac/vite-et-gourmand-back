<?php

namespace App\Controller;

use App\Entity\SuiviCommande;
use App\Repository\CommandeRepository;
use App\Repository\SuiviCommandeRepository;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\AvisRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @author      Florian Aizac
 * @created     25/02/2026
 * @description Contrôleur gérant les actions de l'employé connecté
 */

#[Route('/api/employe')]
final class EmployeController extends AbstractController
{
    /**
     * @description Affiche toutes les commandes en cours (tout statut sauf Terminée et Annulée)
     * @param CommandeRepository $commandeRepository Le repository des commandes
     * @return JsonResponse
     */
    #[Route('/commandes', name: 'api_employe_commandes', methods: ['GET'])]
    public function getCommandes(CommandeRepository $commandeRepository): JsonResponse
    {
        // Étape 1 - Vérifier le rôle EMPLOYE
        if (!$this->isGranted('ROLE_EMPLOYE')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer toutes les commandes en cours
        $commandes = $commandeRepository->findCommandesEnCours();

        // Étape 3 - Retourner les commandes en JSON
        return $this->json(['status' => 'Succès', 'commandes' => $commandes]);
    }

    /**
     * @description Recherche une commande par son numéro de commande
     * @param string $nom Le numéro de commande à rechercher
     * @param CommandeRepository $commandeRepository Le repository des commandes
     * @return JsonResponse
     */
    #[Route('/commandes/recherche/{nom}', name: 'api_employe_commandes_recherche', methods: ['GET'])]
    public function rechercherCommande(string $nom, CommandeRepository $commandeRepository): JsonResponse
    {
        // Étape 1 - Vérifier le rôle EMPLOYE
        if (!$this->isGranted('ROLE_EMPLOYE')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Rechercher la commande par son numéro
        $commandes = $commandeRepository->findByNumeroCommande($nom);

        // Étape 3 - Si aucune commande trouvée
        if (empty($commandes)) {
            return $this->json(['status' => 'Erreur', 'message' => 'Aucune commande trouvée'], 404);
        }

        // Étape 4 - Retourner les commandes en JSON
        return $this->json(['status' => 'Succès', 'commandes' => $commandes]);
    }

    /**
     * @description Modifie le statut d'une commande en respectant le cycle de vie strict
     * @param int $id L'id de la commande
     * @param Request $request La requête HTTP contenant le nouveau statut
     * @param CommandeRepository $commandeRepository Le repository des commandes
     * @param EntityManagerInterface $em L'EntityManager
     * @param MailerService $mailerService Le service d'envoi d'emails
     * @return JsonResponse
     */
    #[Route('/commandes/{id}/statut', name: 'api_employe_commande_statut', methods: ['POST'])]
    public function changerStatut(
        int $id,
        Request $request,
        CommandeRepository $commandeRepository,
        SuiviCommandeRepository $suiviCommandeRepository,
        EntityManagerInterface $em,
        MailerService $mailerService
    ): JsonResponse
    {
        // Étape 1 - Vérifier le rôle EMPLOYE
        if (!$this->isGranted('ROLE_EMPLOYE')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer la commande
        $commande = $commandeRepository->find($id);
        if (!$commande) {
            return $this->json(['status' => 'Erreur', 'message' => 'Commande non trouvée'], 404);
        }

        // Étape 3 - Récupérer le nouveau statut depuis le JSON
        $data = json_decode($request->getContent(), true);
        $nouveauStatut = $data['statut'] ?? null;

        if (!$nouveauStatut) {
            return $this->json(['status' => 'Erreur', 'message' => 'Statut obligatoire'], 400);
        }

        // Étape 4 - Vérifier le cycle de vie strict
        $ordreStatuts = [
            'En attente'      => 1,
            'Acceptée'        => 2,
            'En préparation'  => 3,
            'En livraison'    => 4,
            'Terminée'        => 5,
        ];

        // Vérifier que le nouveau statut existe
        if (!isset($ordreStatuts[$nouveauStatut])) {
            return $this->json(['status' => 'Erreur', 'message' => 'Statut invalide'], 400);
        }

        // Vérifier qu'on n'essaie pas de revenir en arrière
        $statutActuel = $commande->getStatut();
        if (isset($ordreStatuts[$statutActuel]) && $ordreStatuts[$nouveauStatut] <= $ordreStatuts[$statutActuel]) {
            return $this->json(['status' => 'Erreur', 'message' => 'Retour en arrière interdit dans le cycle de vie'], 400);
        }

        // Étape 5 - Mettre à jour le statut de la commande
        $commande->setStatut($nouveauStatut);

        // Étape 6 - Créer un suivi de commande
        $suivi = new SuiviCommande();
        $suivi->setStatut($nouveauStatut);
        $suivi->setDateStatut(new \DateTime());
        $suivi->setCommande($commande);
        $em->persist($suivi);

        // Étape 7 - Envoyer un email selon le statut
        $client = $commande->getUtilisateur();
        if ($nouveauStatut === 'Acceptée') {
            $mailerService->sendCommandeAccepteeEmail($client, $commande);
        } elseif ($nouveauStatut === 'En livraison') {
            $mailerService->sendCommandeLivraisonEmail($client, $commande);
        } elseif ($nouveauStatut === 'Terminée') {
            $mailerService->sendCommandeTermineeEmail($client, $commande);
        }

        // Étape 8 - Sauvegarder en base
        $em->flush();

        // Étape 9 - Retourner un message de confirmation
        return $this->json([
            'status'  => 'Succès',
            'message' => 'Statut mis à jour : ' . $nouveauStatut
        ]);
    }

    /**
     * @description Affiche tous les avis en attente de validation
     * @param AvisRepository $avisRepository Le repository des avis
     * @return JsonResponse
     */
    #[Route('/avis', name: 'api_employe_avis', methods: ['GET'])]
    public function getAvisEnAttente(AvisRepository $avisRepository): JsonResponse
    {
        // Étape 1 - Vérifier le rôle EMPLOYE
        if (!$this->isGranted('ROLE_EMPLOYE')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer tous les avis en attente
        $avis = $avisRepository->findBy(['statut' => 'en_attente']);

        // Étape 3 - Retourner les avis en JSON
        return $this->json(['status' => 'Succès', 'total' => count($avis), 'avis' => $avis]);
    }

    /**
     * @description Approuve un avis client
     * @param int $id L'id de l'avis
     * @param AvisRepository $avisRepository Le repository des avis
     * @param EntityManagerInterface $em L'EntityManager
     * @return JsonResponse
     */
    #[Route('/avis/{id}/approuver', name: 'api_employe_avis_approuver', methods: ['PUT'])]
    public function approuverAvis(int $id, AvisRepository $avisRepository, EntityManagerInterface $em): JsonResponse
    {
        // Étape 1 - Vérifier le rôle EMPLOYE
        if (!$this->isGranted('ROLE_EMPLOYE')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer l'avis
        $avis = $avisRepository->find($id);
        if (!$avis) {
            return $this->json(['status' => 'Erreur', 'message' => 'Avis non trouvé'], 404);
        }

        // Étape 3 - Vérifier que l'avis est en attente
        if ($avis->getStatut() !== 'en_attente') {
            return $this->json(['status' => 'Erreur', 'message' => 'Cet avis n\'est pas en attente'], 400);
        }

        // Étape 4 - Approuver l'avis
        $avis->setStatut('validé');

        // Étape 5 - Sauvegarder en base
        $em->flush();

        // Étape 6 - Retourner un message de confirmation
        return $this->json(['status' => 'Succès', 'message' => 'Avis approuvé avec succès']);
    }
}