<?php

namespace App\Controller;

use App\Enum\CommandeStatut;
use App\Entity\Commande;
use App\Entity\SuiviCommande;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use App\Repository\MenuRepository;
use App\Repository\CommandeRepository;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use OpenApi\Attributes as OA;

use App\Service\NominatimService;
use App\Service\OsrmService;
use App\Service\DistanceService;
use App\Service\MailerService;
use App\Service\LogService;

/**
 * @author      Florian Aizac
 * @created     25/02/2026
 * @description Contrôleur gérant les opérations sur les commandes côté administrateur
 *  1. createCommande  : Créer une nouvelle commande avec toutes les règles métier
 *  2. getAllCommandes : Retourne la liste de toutes les commandes
 *  3. getCommandeById : Retourne une commande par son id
 *  4. annulerCommande : Annule une commande avec un remboursement de 100%
 */
#[Route('/api/commandes')] 
final class CommandeController extends BaseController
{

    private DistanceService $distanceService;

    public function __construct(DistanceService $distanceService)
    {
        $this->distanceService = $distanceService;
    }

  // ADRESSE DU RESTAURANT VITE ET GOURMAND
  private const RESTAURANT_ADDRESS = '22 quai des Chartrons, Bordeaux, France';
  private const RESTAURANT_LAT = 44.8562;
  private const RESTAURANT_LON = -0.5709;


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

    $paques = new \DateTime("$annee-$moisPaques-$jourPaques");
    $lundiPaques = (clone $paques)->modify('+1 day');
    $ascension = (clone $paques)->modify('+39 days');
    $lundiPentec = (clone $paques)->modify('+50 days');

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
    $aujourdhui = new \DateTime();
    $joursOuvrables = 0;
    $dateCourante = clone $aujourdhui;
    // Précalculer les jours fériés des années concernées
    $annees = array_unique([(int)$aujourdhui->format('Y'), (int)$datePrestation->format('Y')]);
    $joursFeries = [];
    foreach ($annees as $annee) {
        $joursFeries = array_merge($joursFeries, $this->getJoursFeries($annee));
    }

    while ($dateCourante < $datePrestation) {
      $dateCourante->modify('+1 day');
      $jourSemaine = (int)$dateCourante->format('N'); // 1=lundi, 7=dimanche
      $dateStr = $dateCourante->format('Y-m-d');
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
   */
  #[Route('', name: 'api_client_commandes_create', methods: ['POST'])]
  #[OA\Post(
      summary: 'Créer une commande',
      description: 'Crée une nouvelle commande avec toutes les règles métier : délai minimum (3j ouvrables, 14j si >20 pers.), acompte 30% ou 50% (événement), livraison gratuite Bordeaux sinon 5€+0.59€/km, réduction -10% si pers. > min+5, vérification stock.'
  )]
  #[OA\Tag(name: 'Client - Commandes')]
  #[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(
      properties: [
        new OA\Property(property: 'menu_id', type: 'integer', example: 1),
        new OA\Property(property: 'date_prestation', type: 'string', example: '2026-04-01'),
        new OA\Property(property: 'nombre_personnes', type: 'integer', example: 15),
        new OA\Property(property: 'adresse_livraison', type: 'string', example: '12 rue des fleurs'),
        new OA\Property(property: 'heure_livraison', type: 'string', example: '12:30'),
        new OA\Property(property: 'ville_livraison', type: 'string', example: 'Bordeaux'),
        new OA\Property(property: 'distance_km', type: 'number', example: 0),
        new OA\Property(property: 'pret_materiel', type: 'boolean', example: false),
      ]
    )
  )]
  #[OA\Response(response: 201, description: 'Commande créée avec succès (prix, acompte, réduction, stock retournés)')]
  #[OA\Response(response: 400, description: 'Champs manquants, stock épuisé, nombre de personnes insuffisant, délai non respecté, ou date invalide')]
  #[OA\Response(response: 403, description: 'Accès refusé')]
  #[OA\Response(response: 404, description: 'Menu non trouvé')]
  public function createCommande(
    Request $request,
    CommandeRepository $commandeRepository,
    MenuRepository $menuRepository,
    EntityManagerInterface $em,
    MailerService $mailerService,
    LogService $logService,
    NominatimService $nominatimService,
    OsrmService $osrmService
  ): JsonResponse {

    // Étape 1 - Vérifier que l'utilisateur est connecté (ROLE_CLIENT)
    $utilisateur = $this->getUser();
    if (!$utilisateur instanceof Utilisateur) {
      return $this->json(['status' => 'Erreur', 'message' => 'Utilisateur non connecté'], 401);
    }

    // Étape 2 - Vérifier le rôle
    if (!$this->isGranted('ROLE_CLIENT')) {
      return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
    }

    // Étape 3 - Récupérer les données JSON
    $data = json_decode($request->getContent(), true);
    if ($data === null) {
      return $this->json(['status' => 'Erreur', 'message' => 'JSON invalide'], 400);
    }

    // Étape 4 - Vérifier les champs obligatoires
    $champsObligatoires = ['menu_id', 'date_prestation', 'heure_livraison', 'nombre_personnes', 'ville_livraison'];
    foreach ($champsObligatoires as $champ) {
      if (empty($data[$champ])) {
        return $this->json(['status' => 'Erreur', 'message' => "Le champ $champ est obligatoire"], 400);
      }
    }

    // Étape 5 - Récupérer l'adresse de l'utilisateur
    $adresseClient = $utilisateur->getAdressePostale() ?? '';
    $villeClient = $utilisateur->getVille() ?? '';
    $adresseCompleteClient = $adresseClient . ', ' . $villeClient . ', France';

    // Étape 6 - Géocodage
    $clientCoords = $nominatimService->geocode($adresseCompleteClient);
    if (!$clientCoords) {
      return $this->json(['status' => 'Erreur', 'message' => 'Adresse client introuvable'], 400);
    }

    // Étape 8 - Calcule de la distance entre le restaurant et le client
    $distanceKm = $osrmService->getRouteDistance(
      self::RESTAURANT_LON,
      self::RESTAURANT_LAT,
      (float)$clientCoords['lon'],
      (float)$clientCoords['lat']
    );

    // Étape 9 - Fallback Haversine si OSRM déconne
    if ($distanceKm === null) {
      $distanceKm = $this->distanceService->distanceHaversine(
        self::RESTAURANT_LAT,
        self::RESTAURANT_LON,
        (float)$clientCoords['lat'],
        (float)$clientCoords['lon']
      );
    }

    // Étape 10 - Récupérer le menu
    $menu = $menuRepository->find($data['menu_id']);
    if (!$menu) {
      return $this->json(['status' => 'Erreur', 'message' => 'Menu non trouvé'], 404);
    }

    // Étape 11 - Vérifier stock
    if ($menu->getQuantiteRestante() <= 0) {
      return $this->json(['status' => 'Erreur', 'message' => 'Ce menu n\'est plus disponible (stock épuisé)'], 400);
    }

    // Étape 12 - Vérifier le nombre minimum de personnes requis
    $nombrePersonnes = (int)$data['nombre_personnes'];
    $minimumPersonnes = $menu->getNombrePersonneMinimum();
    if ($nombrePersonnes < $minimumPersonnes) {
      return $this->json([
        'status' => 'Erreur',
        'message' => "Nombre de personnes insuffisant : ce menu requiert un minimum de $minimumPersonnes personnes (demandé : $nombrePersonnes)"
      ], 400);
    }

    // Étape 13 - Vérification stock suffisant pour le nombre de personnes commandées
    $decrement = max($nombrePersonnes, $minimumPersonnes);
    if ($menu->getQuantiteRestante() < $decrement) {
        return $this->json([
            'status' => 'Erreur',
            'message' => "Stock insuffisant : il reste {$menu->getQuantiteRestante()} disponibles, {$decrement} requis"
        ], 400);
    }

    // Étape 14 - Valider la date de prestation
    try {
      $datePrestation = new \DateTime($data['date_prestation']);
    } catch (\Exception $e) {
      return $this->json(['status' => 'Erreur', 'message' => 'Date de prestation invalide'], 400);
    }

    // Étape 15 - Valider l'heure de livraison strictement (HH:mm)
    $heureLivraison = \DateTime::createFromFormat('H:i', $data['heure_livraison']);
    if (!$heureLivraison) {
      return $this->json(['status' => 'Erreur', 'message' => 'Heure de livraison invalide (format attendu : HH:mm)'], 400);
    }

    // Étape 16 - Vérifier que l'heure est dans le créneau d'ouverture
    $heureMin = new \DateTime('10:00');
    $heureMax = new \DateTime('20:00');
    if ($heureLivraison < $heureMin || $heureLivraison > $heureMax) {
        return $this->json([
            'status' => 'Erreur',
            'message' => 'Heure de livraison en dehors des horaires d\'ouverture (10:00-20:00)'
        ], 400);
    }

    // Étape 17 - Calculer le délai minimum
    $delaiMinimum = $nombrePersonnes > 20 ? 14 : 3;

    // Étape 18 - Calcul des jours ouvrables
    $joursOuvrables = $this->calculerJoursOuvrables($datePrestation);

    // Étape 19 - Vérifier délai minimum
    if ($joursOuvrables < $delaiMinimum) {
      return $this->json([
        'status' => 'Erreur',
        'message' => "Délai minimum non respecté : $delaiMinimum jours ouvrables requis, seulement $joursOuvrables disponibles"
      ], 400);
    }

    // Étape 20 - Calcul du prix de base
    $prixParPersonne = $menu->getPrixParPersonne();
    $prixMenu = $prixParPersonne * $nombrePersonnes;

    // Étape 21 - Appliquer réduction si > min+5
    if ($nombrePersonnes > ($minimumPersonnes + 5)) {
      $prixMenu *= 0.90;
    }

    // Étape 22 - Calculer le prix de livraison
    $prixLivraison = 0;
    if ($distanceKm > 50) {
      $prixLivraison = 5 + 0.59 * ($distanceKm - 50);
      $prixLivraison = round($prixLivraison, 2); // arrondi à 2 décimales
    }

    // Étape 23 - Calcul de l'acompte
    $libelleTheme = strtolower($menu->getTheme()->getLibelle());
    $tauxAcompte = in_array($libelleTheme, ['événement', 'mariage']) ? 0.50 : 0.30;
    $montantAcompte = ($prixMenu + $prixLivraison) * $tauxAcompte;
    $prixTotal = round($prixMenu + $prixLivraison, 2);
    // Étape 24 - Générer numéro de commande
    $numeroCommande = 'CMD-' . strtoupper(bin2hex(random_bytes(4)));

    // Étape 25 - Créer la commande
    $commande = new Commande();
    $commande->setUtilisateur($utilisateur)
            ->setMenu($menu)
            ->setDatePrestation($datePrestation)
            ->setHeureLivraison($heureLivraison)
            ->setNombrePersonne($nombrePersonnes)
            ->setAdresseLivraison($data['adresse_livraison'] ?? '')
            ->setVilleLivraison($data['ville_livraison'])
            ->setPrixMenu(round($prixMenu, 2))
            ->setPrixLivraison(round($prixLivraison, 2))
            ->setMontantAcompte(round($montantAcompte, 2))
            ->setDistanceKm($distanceKm)
            ->setPrixTotal($prixTotal) 
            ->setStatut(CommandeStatut::EN_ATTENTE)
            ->setDateCommande(new \DateTime())
            ->setPretMateriel((bool)($data['pret_materiel'] ?? false))
            ->setNumeroCommande($numeroCommande);

    // Étape 26 - Créer le suivi initial
    $suivi = new SuiviCommande();
    $suivi->setStatut(CommandeStatut::EN_ATTENTE)
      ->setDateStatut(new \DateTime())
      ->setCommande($commande);

    // Étape 27 - Persister et sauvegarder
    $em->persist($commande);
    $em->persist($suivi);
    $em->flush();

    // Étape 28 - Décrémenter le stock
    // Le décrement est toujours au moins le minimum du menu
    $decrement = max($nombrePersonnes, $minimumPersonnes);
    $menu->setQuantiteRestante($menu->getQuantiteRestante() - $decrement);
    $em->flush();

    // Étape 29 - Envoyer mail de confirmation
    $mailerService->sendCommandeCreeeEmail($utilisateur, $commande);

    // Étape 30 - Log MongoDB
    $logService->log(
      'commande_creee',
      $utilisateur->getEmail(),
      'ROLE_CLIENT',
      [
        'numero_commande' => $numeroCommande,
        'montant' => round($prixMenu + $prixLivraison, 2),
        'menu' => $menu->getTitre(),
        'ville_livraison' => $data['ville_livraison'],
        'pret_materiel' => $commande->isPretMateriel(),
        'stock_restant' => $menu->getQuantiteRestante(),
        'distanceKm' => round($distanceKm, 2),
      ]
    );
    
    // Étape 31 - Retourner la confirmation
    return $this->json([
        'status' => 'Succès',
        'message' => 'Commande créée avec succès',
        'numero_commande' => $numeroCommande,
        'email' => $utilisateur->getEmail(),
        'prix_menu' => round($prixMenu, 2),
        'prix_livraison' => round($prixLivraison, 2),
        'prix_total' => $prixTotal,
        'montant_acompte' => round($montantAcompte, 2),
        'heure_livraison' => $heureLivraison->format('H:i'),
        'pret_materiel' => $commande->isPretMateriel(),
        'reduction_appliquee' => $nombrePersonnes > ($minimumPersonnes + 5) ? '-10%' : 'aucune',
        'stock_restant' => $menu->getQuantiteRestante(),
        'distanceKm' => round($distanceKm, 2),
    ], 201);
  }

  /**
   * @description Retourne la liste de toutes les commandes
   */
  #[Route('/admin', name: 'api_admin_commandes_list', methods: ['GET'])]
  #[OA\Get(summary: 'Liste de toutes les commandes', description: 'Retourne la liste complète des commandes. Réservé aux administrateurs.')]
  #[OA\Tag(name: 'Admin - Commandes')]
  #[OA\Response(response: 200, description: 'Liste des commandes retournée')]
  #[OA\Response(response: 403, description: 'Accès refusé')]
  public function getAllCommandes(CommandeRepository $commandeRepository): JsonResponse
  {
    // Étape 1 - Vérifier le rôle ADMIN
    if (!$this->isGranted('ROLE_ADMIN')) {
      return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
    }

    // Étape 2 - Récupérer toutes les commandes
    $commandes = $commandeRepository->findAll();

    // Étape 3 - Formater pour éviter la référence circulaire
    $data = [];
    foreach ($commandes as $commande) {
      $data[] = [
        'id' => $commande->getId(),
        'numero_commande' => $commande->getNumeroCommande(),
        'date_commande' => $commande->getDateCommande()?->format('Y-m-d H:i:s'),
        'date_prestation' => $commande->getDatePrestation()?->format('Y-m-d'),
        'heure_livraison' => $commande->getHeureLivraison()?->format('H:i'),
        'statut' => $commande->getStatut(),
        'nombre_personne' => $commande->getNombrePersonne(),
        'prix_menu' => $commande->getPrixMenu(),
        'prix_livraison' => $commande->getPrixLivraison(),
        'distance_km' => $commande->getDistanceKm(),

        'adresse_livraison' => $commande->getAdresseLivraison(),
        'ville_livraison' => $commande->getVilleLivraison(),

        'pret_materiel' => $commande->isPretMateriel(),
        'restitution_materiel' => $commande->isRestitutionMateriel(),
        'etat_materiel' => $commande->getEtatMateriel(),

        'utilisateur' => [
            'id' => $commande->getUtilisateur()?->getId(),
            'nom' => $commande->getUtilisateur()?->getNom(),
            'prenom' => $commande->getUtilisateur()?->getPrenom(),
            'telephone' => $commande->getUtilisateur()?->getTelephone(),        
            'adresse_postale' => $commande->getUtilisateur()?->getAdressePostale(),
        ],

        'menu' => [
            'id' => $commande->getMenu()?->getId(),
            'titre' => $commande->getMenu()?->getTitre(),

        ]
      ];
    }

    // Étape 4 - Retourne les résultats
    return $this->json([
        'status' => 'Succès',
        'total' => count($data),
        'commandes' => $data
    ]);

  }

  /**
   * @description Retourne une commande par son id
   * @param int $id L'id de la commande
   * @param CommandeRepository $commandeRepository Le repository des commandes
   * @return JsonResponse
   */
  #[Route('/admin/{id}', name: 'api_admin_commandes_show', methods: ['GET'])]
  #[OA\Get(summary: 'Détail d\'une commande par ID', description: 'Retourne une commande par son ID. Réservé aux administrateurs.')]
  #[OA\Tag(name: 'Admin - Commandes')]
  #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID de la commande', schema: new OA\Schema(type: 'integer'))]
  #[OA\Response(response: 200, description: 'Commande trouvée')]
  #[OA\Response(response: 403, description: 'Accès refusé')]
  #[OA\Response(response: 404, description: 'Commande non trouvée')]
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

    // Étape 3 - Formater la commande
    $data = [
      'id' => $commande->getId(),
      'numero_commande' => $commande->getNumeroCommande(),

      'date_commande' => $commande->getDateCommande()?->format('Y-m-d H:i:s'),
      'date_prestation' => $commande->getDatePrestation()?->format('Y-m-d'),
      'heure_livraison' => $commande->getHeureLivraison()?->format('H:i'),

      'statut' => $commande->getStatut(),

      'nombre_personne' => $commande->getNombrePersonne(),
      'prix_menu' => $commande->getPrixMenu(),
      'prix_livraison' => $commande->getPrixLivraison(),
      'distance_km' => $commande->getDistanceKm(),

      'adresse_livraison' => $commande->getAdresseLivraison(),
      'ville_livraison' => $commande->getVilleLivraison(),

      'pret_materiel' => $commande->isPretMateriel(),
      'restitution_materiel' => $commande->isRestitutionMateriel(),
      'etat_materiel' => $commande->getEtatMateriel(),

      'montant_acompte' => $commande->getMontantAcompte(),
      'montant_rembourse' => $commande->getMontantRembourse(),
      'motif_annulation' => $commande->getMotifAnnulation(),

      'date_statut_livree' => $commande->getDateStatutLivree()?->format('Y-m-d H:i:s'),
      'date_statut_retour_materiel' => $commande->getDateStatutRetourMateriel()?->format('Y-m-d H:i:s'),

      'mail_penalite_envoye' => $commande->isMailPenaliteEnvoye(),

      'utilisateur' => [
          'id' => $commande->getUtilisateur()?->getId(),
          'nom' => $commande->getUtilisateur()?->getNom(),
          'prenom' => $commande->getUtilisateur()?->getPrenom(),
          'telephone' => $commande->getUtilisateur()?->getTelephone(),        
          'adresse_postale' => $commande->getUtilisateur()?->getAdressePostale(),
      ],

      'menu' => [
          'id' => $commande->getMenu()?->getId(),
          'titre' => $commande->getMenu()?->getTitre(),

      ]
    ];

    // Étape 4 - Retourner la réponse
    return $this->json([
        'status' => 'Succès',
        'commande' => $data
    ]);
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
  #[Route('/admin/{id}/annuler', name: 'api_admin_commandes_annuler', methods: ['PUT'])]
  #[OA\Put(
    summary: 'Annuler une commande (admin)',
    description: 'Annule une commande avec remboursement intégral (100%), quel que soit le délai. Un motif optionnel peut être fourni.'
  )]
  #[OA\Tag(name: 'Admin - Commandes')]
  #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID de la commande', schema: new OA\Schema(type: 'integer'))]
  #[OA\RequestBody(required: false, content: new OA\JsonContent(
    properties: [new OA\Property(property: 'motif_annulation', type: 'string', example: 'Rupture de stock')]
  ))]
  #[OA\Response(response: 200, description: 'Commande annulée avec succès')]
  #[OA\Response(response: 400, description: 'Commande déjà annulée')]
  #[OA\Response(response: 403, description: 'Accès refusé')]
  #[OA\Response(response: 404, description: 'Commande non trouvée')]
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
      'admin@vite-et-gourmand.fr',
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
