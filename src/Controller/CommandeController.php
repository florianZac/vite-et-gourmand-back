<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\SuiviCommande;
use App\Enum\CommandeStatut;
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
 *  1. createCommande  : Créer une nouvelle commande avec toutes les règles métier
 *  2. getAllCommandes : Retourne la liste de toutes les commandes
 *  3. getCommandeById : Retourne une commande par son id
 *  4. annulerCommande : Annule une commande avec un remboursement de 100%
 */
#[Route('/api/admin/commandes')]
final class CommandeController extends BaseController
{
    /**
     * @description Retourne la liste des jours fériés français pour une année donnée
     * Utilisé pour exclure les jours fériés du calcul des jours ouvrables
     * @param int $annee L'année pour laquelle calculer les jours fériés
     * @return array Tableau de dates au format Y-m-d
     */
    private function getJoursFeries(int $annee): array
    {
        // Calcul de Pâques via l'algorithme de Butcher
        $a = $annee % 19;
        $b = (int) ($annee / 100);
        $c = $annee % 100;
        $d = (int) ($b / 4);
        $e = $b % 4;
        $f = (int) (($b + 8) / 25);
        $g = (int) (($b - $f + 1) / 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = (int) ($c / 4);
        $k = $c % 4;
        $l = (36 - $e - $i + $k + $h) % 7;
        $m = (int) (($a + 11 * $h + 22 * $l) / 451);
        $moisPaques = (int) (($h + $l - 7 * $m + 114) / 31);
        $jourPaques = (($h + $l - 7 * $m + 114) % 31) + 1;

        $paques      = new \DateTime("$annee-$moisPaques-$jourPaques");
        $lundiPaques = (clone $paques)->modify('+1 day');
        $ascension   = (clone $paques)->modify('+39 days');
        $lundiPentec = (clone $paques)->modify('+50 days');

        return [
            // Jours fériés fixes
            "$annee-01-01", // Jour de l'an
            "$annee-05-01", // Fête du travail
            "$annee-05-08", // Victoire 1945
            "$annee-07-14", // Fête nationale
            "$annee-08-15", // Assomption
            "$annee-11-01", // Toussaint
            "$annee-11-11", // Armistice
            "$annee-12-25", // Noël
            // Jours fériés mobiles (basés sur Pâques)
            $paques->format('Y-m-d'),
            $lundiPaques->format('Y-m-d'),
            $ascension->format('Y-m-d'),
            $lundiPentec->format('Y-m-d'),
        ];
    }

    /**
     * @description Calcule le nombre de jours ouvrables entre aujourd'hui et une date cible
     * Exclut les samedis, dimanches ET jours fériés français
     * @param \DateTime $datePrestation La date cible
     * @return int Le nombre de jours ouvrables
     */
    private function calculerJoursOuvrables(\DateTime $datePrestation): int
    {
        $aujourdhui   = new \DateTime();
        $joursOuvrables = 0;
        $dateCourante = clone $aujourdhui;

        // Précalculer les jours fériés des années concernées
        $annees      = array_unique([(int) $aujourdhui->format('Y'), (int) $datePrestation->format('Y')]);
        $joursFeries = [];
        foreach ($annees as $annee) {
            $joursFeries = array_merge($joursFeries, $this->getJoursFeries($annee));
        }

        while ($dateCourante < $datePrestation) {
            $dateCourante->modify('+1 day');
            $jourSemaine = (int) $dateCourante->format('N'); // 1=lundi, 7=dimanche
            $dateStr     = $dateCourante->format('Y-m-d');

            // Compter uniquement lundi-vendredi hors jours fériés
            if ($jourSemaine <= 5 && !in_array($dateStr, $joursFeries)) {
                $joursOuvrables++;
            }
        }

        return $joursOuvrables;
    }

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
     *  - Vérification stock (quantite_restante > 0) avant création
     *  - Vérification nombre minimum de personnes (nombre_personnes >= nombrePersonneMinimum)
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
        LogService $logService
    ): JsonResponse {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer les données JSON
        $data = json_decode($request->getContent(), true);

        // Étape 3 - Vérifier les champs obligatoires
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

        // Étape 5.1 - Vérifier que le menu est disponible (quantite_restante > 0)
        // Si le stock est épuisé, on refuse la commande avant tout calcul
        if ($menu->getQuantiteRestante() <= 0) {
            return $this->json([
                'status'  => 'Erreur',
                'message' => 'Ce menu n\'est plus disponible (stock épuisé)'
            ], 400);
        }

        // Étape 5.2 - Vérifier le nombre minimum de personnes requis par le menu
        // Ex: si le menu requiert minimum 10 personnes, on ne peut pas commander pour 5
        $nombrePersonnes  = (int) $data['nombre_personnes'];
        $minimumPersonnes = $menu->getNombrePersonneMinimum();
        if ($nombrePersonnes < $minimumPersonnes) {
            return $this->json([
                'status'  => 'Erreur',
                'message' => "Nombre de personnes insuffisant : ce menu requiert un minimum de $minimumPersonnes personnes (demandé : $nombrePersonnes)"
            ], 400);
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

        // Étape 9 - Vérifier que le délai minimum est respecté
        if ($joursOuvrables < $delaiMinimum) {
            return $this->json([
                'status'  => 'Erreur',
                'message' => "Délai minimum non respecté : $delaiMinimum jours ouvrables requis, seulement $joursOuvrables disponibles"
            ], 400);
        }

        // Étape 10 - Calculer le prix de base
        $prixParPersonne = $menu->getPrixParPersonne();
        $prixMenu        = $prixParPersonne * $nombrePersonnes;

        // Étape 11 - Appliquer la réduction de -10% si nécessaire
        if ($nombrePersonnes > ($minimumPersonnes + 5)) {
            $prixMenu = $prixMenu * 0.90;
        }

        // Étape 12 - Calculer le prix de livraison
        $villeLivraison = strtolower(trim($data['ville_livraison']));
        $distanceKm     = (float) ($data['distance_km'] ?? 0);
        if ($villeLivraison === 'bordeaux') {
            $prixLivraison = 0;
        } else {
            $prixLivraison = 5 + (0.59 * $distanceKm);
        }

        // Étape 13 - Calculer l'acompte (50% événement, 30% sinon)
        $libelleTheme   = strtolower($menu->getTheme()->getLibelle());
        $tauxAcompte    = ($libelleTheme === 'événement') ? 0.50 : 0.30;
        $montantAcompte = ($prixMenu + $prixLivraison) * $tauxAcompte;

        // Étape 14 - Générer le numéro de commande unique
        $numeroCommande = 'CMD-' . strtoupper(bin2hex(random_bytes(4)));

        // Étape 15 - Créer la commande
        $commande = new Commande();
        $commande->setNumeroCommande($numeroCommande);
        $commande->setUtilisateur($utilisateur);
        $commande->setMenu($menu);
        $commande->setDatePrestation($datePrestation);
        $commande->setNombrePersonne($nombrePersonnes);
        $commande->setAdresseLivraison($data['adresse_livraison'] ?? '');
        $commande->setVilleLivraison($data['ville_livraison']);
        $commande->setPrixMenu(round($prixMenu, 2));
        $commande->setPrixLivraison(round($prixLivraison, 2));
        $commande->setMontantAcompte(round($montantAcompte, 2));
        $commande->setStatut(CommandeStatut::EN_ATTENTE);
        $commande->setDateCommande(new \DateTime());
        $commande->setPretMateriel((bool) ($data['pret_materiel'] ?? false));

        // Étape 16 - Créer le suivi initial
        $suivi = new SuiviCommande();
        $suivi->setStatut(CommandeStatut::EN_ATTENTE);
        $suivi->setDateStatut(new \DateTime());
        $suivi->setCommande($commande);

        // Étape 17 - Persister et sauvegarder
        $em->persist($commande);
        $em->persist($suivi);
        $em->flush();

        // Étape 18 - Décrémenter le stock du menu
        $menu->setQuantiteRestante($menu->getQuantiteRestante() - 1);
        $em->flush();

        // Étape 19 - Envoyer un mail de confirmation au client
        $mailerService->sendCommandeCreeeEmail($utilisateur, $commande);

        // Étape 20 - Enregistrer le log MongoDB
        $logService->log(
            'commande_creee',
            $utilisateur->getEmail(),
            'ROLE_ADMIN',
            [
                'numero_commande' => $numeroCommande,
                'montant'         => round($prixMenu + $prixLivraison, 2),
                'menu'            => $menu->getTitre(),
                'ville_livraison' => $data['ville_livraison'],
                'pret_materiel'   => $commande->isPretMateriel(),
                'stock_restant'   => $menu->getQuantiteRestante(),
            ]
        );

        // Étape 21 - Retourner la confirmation
        return $this->json([
            'status'              => 'Succès',
            'message'             => 'Commande créée avec succès',
            'numero_commande'     => $numeroCommande,
            'prix_menu'           => round($prixMenu, 2),
            'prix_livraison'      => round($prixLivraison, 2),
            'montant_acompte'     => round($montantAcompte, 2),
            'pret_materiel'       => $commande->isPretMateriel(),
            'reduction_appliquee' => $nombrePersonnes > ($minimumPersonnes + 5) ? '-10%' : 'aucune',
            'stock_restant'       => $menu->getQuantiteRestante(),
        ], 201);
    }

    /**
     * @description Retourne la liste de toutes les commandes
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

        // Étape 2 - Récupérer la commande
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
     * Corps JSON attendu  : { "motif_annulation": "Rupture de stock" }
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
        MailerService $mailerService,
        LogService $logService
    ): JsonResponse {
        // Étape 1 - Vérifier le rôle ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer la commande
        $commande = $commandeRepository->find($id);
        if (!$commande) {
            return $this->json(['status' => 'Erreur', 'message' => 'Commande non trouvée'], 404);
        }

        // Étape 3 - Vérifier que la commande n'est pas déjà annulée
        if ($commande->getStatut() === CommandeStatut::ANNULEE) {
            return $this->json(['status' => 'Erreur', 'message' => 'Cette commande est déjà annulée'], 400);
        }

        // Étape 4 - Récupérer le motif d'annulation (optionnel pour l'admin)
        $data  = json_decode($request->getContent(), true);
        $motif = $data['motif_annulation'] ?? 'Annulée par l\'administrateur';

        // Étape 5 - Calculer le montant remboursé (100% : prix menu + livraison)
        $montantRembourse = $commande->getPrixMenu() + $commande->getPrixLivraison();

        // Étape 6 - Mettre à jour la commande
        $commande->setStatut(CommandeStatut::ANNULEE);
        $commande->setMotifAnnulation($motif);
        $commande->setMontantRembourse($montantRembourse);

        // Étape 7 - Sauvegarder en base
        $em->flush();

        // Étape 8 - Envoyer l'email de confirmation d'annulation au client
        $mailerService->sendCommandeAnnuleeEmail($commande->getUtilisateur(), $commande, $montantRembourse);

        // Étape 9 - Enregistrer le log MongoDB
        $admin = $this->getUser();
        $logService->log(
            'commande_annulee',
            $admin ? $admin->getUserIdentifier() : 'admin_inconnu',
            'ROLE_ADMIN',
            [
                'numero_commande'   => $commande->getNumeroCommande(),
                'motif'             => $motif,
                'montant_rembourse' => $montantRembourse,
                'client_email'      => $commande->getUtilisateur()->getEmail(),
            ]
        );

        // Étape 10 - Retourner une confirmation
        return $this->json([
            'status'            => 'Succès',
            'message'           => 'Commande annulée avec succès',
            'numero_commande'   => $commande->getNumeroCommande(),
            'motif_annulation'  => $commande->getMotifAnnulation(),
            'montant_rembourse' => $montantRembourse,
        ]);
    }
}
