<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\SuiviCommande;
use App\Repository\MenuRepository;
use App\Repository\UtilisateurRepository;
use App\Service\MailerService;
use App\Service\LogService; // import du LogService MongoDB
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @author      Florian Aizac
 * @created     25/02/2026
 * @description Contrôleur gérant les opérations sur les commandes côté administrateur
 *  1. createCommande         : Créer une nouvelle commande avec toutes les règles métier
 *  2. getAllCommandes        : Retourne la liste de toutes les commandes
 *  3. getCommandeById       : Retourne une commande par son id
 *  4. annulerCommande        : Annule une commande avec un remboursement de 100% du montant total (prix menu + livraison)
 */
#[Route('/api/admin/commandes')]
final class CommandeController extends BaseController
{
    // =========================================================================
    // COMMANDE
    // =========================================================================

    /**
     * @description Créer une nouvelle commande avec toutes les règles métier :
     *  - Délai minimum 3 jours ouvrables (14 jours si > 20 personnes)
     *  - Acompte 30% (standard) ou 50% (événement)
     *  - Livraison gratuite Bordeaux, sinon 5€ + 0,59€/km
     *  - Réduction -10% si nombre de personnes > minimum + 5
     *  - pret_materiel : true/false selon la checkbox front (défaut false)
     * Corps JSON attendu :
     * {
     *   "utilisateur_id": 3,
     *   "menu_id": 1,
     *   "date_prestation": "2026-04-01",
     *   "nombre_personnes": 15,
     *   "adresse_livraison": "12 rue des fleurs",
     *   "ville_livraison": "Bordeaux",
     *   "distance_km": 0,
     *   "pret_materiel": false
     * }
     */
    #[Route('', name: 'api_admin_commandes_create', methods: ['POST'])]
    public function createCommande(
        Request $request,
        CommandeRepository $commandeRepository,
        MenuRepository $menuRepository,
        UtilisateurRepository $utilisateurRepository,
        EntityManagerInterface $em,
        MailerService $mailerService,
        LogService $logService              // AJOUT : injection du LogService MongoDB
    ): JsonResponse {

        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer les données JSON
        $data = json_decode($request->getContent(), true);

        // Étape 3 - Vérifier les champs obligatoires
        // utilisateur_id ajouté aux champs obligatoires
        $champsObligatoires = ['utilisateur_id', 'menu_id', 'date_prestation', 'nombre_personnes', 'ville_livraison'];
        foreach ($champsObligatoires as $champ) {
            if (empty($data[$champ])) {
                return $this->json(['status' => 'Erreur', 'message' => "Le champ $champ est obligatoire"], 400);
            }
        }

        // Étape 4 - Récupérer l'utilisateur
        // $utilisateur doit être récupéré depuis le repository avant de l'associer à la commande
        $utilisateur = $utilisateurRepository->find($data['utilisateur_id']);
        if (!$utilisateur) {
            return $this->json(['status' => 'Erreur', 'message' => 'Utilisateur non trouvé'], 404);
        }

        // Étape 5 - Récupérer le menu
        $menu = $menuRepository->find($data['menu_id']);
        if (!$menu) {
            return $this->json(['status' => 'Erreur', 'message' => 'Menu non trouvé'], 404);
        }

        // Étape 6 - Valider la date de prestation
        try {
            $datePrestation = new \DateTime($data['date_prestation']);
        } catch (\Exception $e) {
            return $this->json(['status' => 'Erreur', 'message' => 'Date de prestation invalide'], 400);
        }

        // Étape 7 - Calculer le délai minimum en jours ouvrables
        $nombrePersonnes = (int) $data['nombre_personnes'];
        $delaiMinimum = $nombrePersonnes > 20 ? 14 : 3;

        // Étape 8 - Calcul des jours ouvrables entre aujourd'hui et la date de prestation
        $aujourdhui = new \DateTime();
        $joursOuvrables = 0;
        $dateCourante = clone $aujourdhui;

        // Étape 9 - Calcul tant que la date courante est inférieur à la date de presation
        while ($dateCourante < $datePrestation) {
            $dateCourante->modify('+1 day');
            // 1 = lundi ... 5 = vendredi (jours ouvrables)
            if ((int) $dateCourante->format('N') <= 5) {
                $joursOuvrables++;
            }
        }

        // Étape 10 - Vérifie que le delais n'est pas dépassé
        if ($joursOuvrables < $delaiMinimum) {
            return $this->json([
                'status'  => 'Erreur',
                'message' => "Délai minimum non respecté : $delaiMinimum jours ouvrables requis, seulement $joursOuvrables disponibles"
            ], 400);
        }

        // Étape 10 - Calcule le prix de base
        $prixParPersonne = $menu->getPrixParPersonne();
        $prixMenu = $prixParPersonne * $nombrePersonnes;

        // Étape 11 - Applique la réduction de -10% si nécessaire
        // si le nombre de personnes dépasse le minimum requis de plus de 5
        $minimumPersonnes = $menu->getNombrePersonneMinimum();
        if ($nombrePersonnes > ($minimumPersonnes + 5)) {
            $prixMenu = $prixMenu * 0.90;
        }

        // Étape 12 - Calculer le prix de livraison
        // Gratuit à Bordeaux, sinon 5€ + 0,59€/km
        $villeLivraison = strtolower(trim($data['ville_livraison']));
        $distanceKm = (float) ($data['distance_km'] ?? 0);

        if ($villeLivraison === 'bordeaux') {
            $prixLivraison = 0;
        } else {
            $prixLivraison = 5 + (0.59 * $distanceKm);
        }

        // Étape 13 - Calculer l'acompte
        // 50% si thème Événement, 30% sinon
        $libelleTheme = strtolower($menu->getTheme()->getLibelle());
        $tauxAcompte = ($libelleTheme === 'événement') ? 0.50 : 0.30;
        $montantAcompte = ($prixMenu + $prixLivraison) * $tauxAcompte;

        // Étape 14 - Générer le numéro de commande unique
        $numeroCommande = 'CMD-' . strtoupper(bin2hex(random_bytes(4)));

        // Étape 15 - Créer la commande
        $commande = new Commande();
        $commande->setNumeroCommande($numeroCommande);
        $commande->setUtilisateur($utilisateur); // CORRECTION : association de l'utilisateur à la commande
        $commande->setMenu($menu);
        $commande->setDatePrestation($datePrestation);
        $commande->setNombrePersonne($nombrePersonnes);
        $commande->setAdresseLivraison($data['adresse_livraison'] ?? '');
        $commande->setVilleLivraison($data['ville_livraison']);
        $commande->setPrixMenu(round($prixMenu, 2));
        $commande->setPrixLivraison(round($prixLivraison, 2));
        $commande->setMontantAcompte(round($montantAcompte, 2));
        $commande->setStatut('En attente');
        $commande->setDateCommande(new \DateTime());

        // Étape 15 - met à jour la variable de pret de matériel 
        $commande->setPretMateriel((bool) ($data['pret_materiel'] ?? false));

        // Étape 16 - Créer le suivi
        $suivi = new SuiviCommande();
        $suivi->setStatut('En attente');
        $suivi->setDateStatut(new \DateTime());
        $suivi->setCommande($commande);

        // Étape 17 - Persister et sauvegarder
        $em->persist($commande);
        $em->persist($suivi);
        $em->flush();

        // Étape 18 - Envoyer un mail de confirmation de commande au client
        $mailerService->sendCommandeCreeeEmail($utilisateur, $commande);

        // Étape 19 - AJOUT : Enregistrer le log de création de commande dans MongoDB
        $logService->log(
            'commande_creee',               // type de l'action
            $utilisateur->getEmail(),        // email du client concerné
            'ROLE_ADMIN',                    // c'est l'admin qui crée la commande
            [
                'numero_commande' => $numeroCommande,
                'montant'         => round($prixMenu + $prixLivraison, 2),
                'menu'            => $menu->getTitre(),
                'ville_livraison' => $data['ville_livraison'],
                'pret_materiel'   => $commande->isPretMateriel(),
            ]
        );

        // Étape 20 - Retourner la confirmation
        return $this->json([
            'status'              => 'Succès',
            'message'             => 'Commande créée avec succès',
            'numero_commande'     => $numeroCommande,
            'prix_menu'           => round($prixMenu, 2),
            'prix_livraison'      => round($prixLivraison, 2),
            'montant_acompte'     => round($montantAcompte, 2),
            'pret_materiel'       => $commande->isPretMateriel(),
            'reduction_appliquee' => $nombrePersonnes > ($minimumPersonnes + 5) ? '-10%' : 'aucune',
        ], 201);
    }

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
     * @param LogService $logService pour enregistrer le log d'annulation dans MongoDB
     * @return JsonResponse
     */
    #[Route('/{id}/annuler', name: 'api_admin_commandes_annuler', methods: ['PUT'])]
    public function annulerCommande(
        int $id,
        Request $request,
        CommandeRepository $commandeRepository,
        EntityManagerInterface $em,
        LogService $logService              // injection du LogService MongoDB
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

        // Étape 8 - AJOUT : Enregistrer le log d'annulation dans MongoDB
        // Récupère l'admin connecté pour l'email du log
        $admin = $this->getUser();
        $logService->log(
            'commande_annulee',                                             // type de l'action
            $admin ? $admin->getUserIdentifier() : 'admin_inconnu',         // email de l'admin qui annule
            'ROLE_ADMIN',                                                    // c'est l'admin qui annule
            [                                                               // contexte libre pour l'audit
                'numero_commande'   => $commande->getNumeroCommande(),
                'motif'             => $motif,
                'montant_rembourse' => $montantRembourse,
                'client_email'      => $commande->getUtilisateur()->getEmail(),
            ]
        );

        // Étape 9 - Retourner une confirmation avec le détail du remboursement
        return $this->json([
            'status'            => 'Succès',
            'message'           => 'Commande annulée avec succès',
            'numero_commande'   => $commande->getNumeroCommande(),
            'motif_annulation'  => $commande->getMotifAnnulation(),
            'montant_rembourse' => $montantRembourse,
        ]);
    }
}
