<?php

namespace App\Controller;

use App\Entity\Menu;
use App\Entity\MenuImage;
use App\Entity\Regime;
use App\Entity\SuiviCommande;
use App\Entity\Theme;
use App\Entity\Allergene;
use App\Entity\Plat;
use App\Enum\CommandeStatut;

use App\Repository\AllergeneRepository;
use App\Repository\AvisRepository;
use App\Repository\CommandeRepository;
use App\Repository\MenuRepository;
use App\Repository\MenuImageRepository;
use App\Repository\PlatRepository;
use App\Repository\RegimeRepository;
use App\Repository\SuiviCommandeRepository;
use App\Repository\ThemeRepository;
use App\Repository\UtilisateurRepository;
use App\Service\CloudinaryService;

use App\Service\MailerService;

use Doctrine\ORM\EntityManagerInterface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

/**
 * @author      Florian Aizac
 * @created     25/02/2026
 * @description Gestions des actions d'un employé connecté
 *
 *  1. getCommandes()        : Afficher toutes les commandes en cours
 *  2. rechercherCommande()  : Rechercher une commande par son numéro de commande
 *  3. changerStatut()       : Modifier le statut d'une commande en respectant le cycle de vie strict
 *  4. getMaterialEnCours()  : Retourne toutes les commandes avec matériel non rendu
 *  5. getMaterielCommande() : Retourne l'état du matériel d'une commande ciblée
 *  6. confirmerRestitution(): Confirme le retour du matériel et le paiement de la pénalité
 *  7. filtrerCommandes()    : Filtrer les commandes par statut et/ou par client
 *  8. getSuiviCommande()    : Afficher le suivi d'une commande
 *
 *  9. getAvisEnAttente()    : Afficher tous les avis en attente de validation
 *  10. approuverAvis()      : Approuver un avis client
 *  11. refuserAvis()        : Refuser un avis client
 * 
 *  12. createMenu()         : Créer un nouveau menu
 *  13. updateMenu()         : Met à jour un menu par son id
 *  14. deleteMenu()         : Supprimer un menu par son id
 *
 *  15. addImageMenu()       : Ajouter une image à la galerie d'un menu
 *  16. deleteImageMenu()    : Supprimer une image de la galerie d'un menu
 *  17. updateOrdreImage()   : Modifier l'ordre d'affichage d'une image
 *
 *  18. createTheme()        : Créer un nouveau thème
 *  19. updateTheme()        : Met à jour un thème par son id
 *  20. deleteTheme()        : Supprimer un thème par son id
 *
 *  21. createRegime()       : Créer un nouveau régime
 *  22. updateRegime()       : Met à jour un régime par son id
 *  23. deleteRegime()       : Supprimer un régime par son id
 *
 *  24. createAllergene()    : Créer un nouvel allergène
 *  25. updateAllergene()    : Met à jour un allergène par son id
 *  26. deleteAllergene()    : Supprimer un allergène par son id
 *
 *  27. createPlat()         : Créer un nouveau plat
 *  28. updatePlat()         : Met à jour un plat par son id
 *  29. deletePlat()         : Supprimer un plat par son id
 */
#[Route('/api/employe')]
final class EmployeController extends AbstractController
{
  // =========================================================================
  // COMMANDES
  // =========================================================================

  /**
   * @description Afficher toutes les commandes en cours
   * @param CommandeRepository $commandeRepository Le repository des commandes
   * @return JsonResponse
   */
  #[Route('/commandes', name: 'api_employe_commandes', methods: ['GET'])]
  #[OA\Get(summary: 'Commandes en cours', description: 'Retourne toutes les commandes en cours (non terminées, non annulées). Réservé aux employés.')]
  #[OA\Tag(name: 'Employé - Commandes')]
  #[OA\Response(response: 200, description: 'Liste des commandes en cours')]
  #[OA\Response(response: 403, description: 'Accès refusé')]
  public function getCommandes(CommandeRepository $commandeRepository): JsonResponse
  {
    // Étape 1 - Vérifier le rôle EMPLOYE
    if (!$this->isGranted('ROLE_EMPLOYE')) {
      return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
    }

    // Étape 2 - Récupérer toutes les commandes en cours
    $commandes = $commandeRepository->findCommandesEnCours();

    // Étape 3 - Formater pour éviter la référence circulaire
    $data = array_map([$this, 'formatCommande'], $commandes);

    // Étape 4 - Retourner les commandes en JSON
    return $this->json(['status' => 'Succès', 'commandes' => $data]);
  }

  /**
   * @description Rechercher une commande par son numéro de commande
   * @param string $nom Le numéro de commande à rechercher
   * @param CommandeRepository $commandeRepository Le repository des commandes
   * @return JsonResponse
   */
  #[Route('/commandes/recherche/{nom}', name: 'api_employe_commandes_recherche', methods: ['GET'])]
  #[OA\Get(summary: 'Rechercher une commande', description: 'Recherche une commande par son numéro. Réservé aux employés.')]
  #[OA\Tag(name: 'Employé - Commandes')]
  #[OA\Parameter(name: 'nom', in: 'path', required: true, description: 'Numéro de commande à rechercher', schema: new OA\Schema(type: 'string'))]
  #[OA\Response(response: 200, description: 'Commande(s) trouvée(s)')]
  #[OA\Response(response: 403, description: 'Accès refusé')]
  #[OA\Response(response: 404, description: 'Aucune commande trouvée')]
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

    // Étape 4 - Formater pour éviter la référence circulaire
    $data = array_map([$this, 'formatCommande'], $commandes);

    // Étape 5 - Retourner les commandes en JSON
    return $this->json(['status' => 'Succès', 'commandes' => $data]);
  }

  /**
   * @description Modifier le statut d'une commande en respectant le cycle de vie strict
   * Cycle autorisé : En attente -> Acceptée -> En préparation -> En livraison -> Livré -> Terminée
   * Un retour en arrière est interdit.
   * Cas spéciaux au statut LIVRE :
   *   - pret_materiel = false -> passage automatique à Terminée
   *   - pret_materiel = true  -> passage automatique à En attente du retour matériel + email client
   */
  #[Route('/commandes/{id}/statut', name: 'api_employe_commande_statut', methods: ['POST'])]
  #[OA\Post(
      summary: 'Changer le statut d\'une commande',
      description: 'Modifie le statut en respectant le cycle strict : En attente → Acceptée → En préparation → En livraison → Livré → Terminée. Retour en arrière interdit. Si Livré + matériel prêté → En attente retour matériel. Si Livré + pas de matériel → Terminée.'
  )]
  #[OA\Tag(name: 'Employé - Commandes')]
  #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID de la commande', schema: new OA\Schema(type: 'integer'))]
  #[OA\RequestBody(required: true, content: new OA\JsonContent(
      properties: [new OA\Property(property: 'statut', type: 'string', example: 'Acceptée', description: 'Nouveau statut à appliquer')]
  ))]
  #[OA\Response(response: 200, description: 'Statut mis à jour')]
  #[OA\Response(response: 400, description: 'Statut invalide, manquant, ou retour en arrière interdit')]
  #[OA\Response(response: 403, description: 'Accès refusé')]
  #[OA\Response(response: 404, description: 'Commande non trouvée')]
  public function changerStatut(
    int $id,
    Request $request,
    CommandeRepository $commandeRepository,
    SuiviCommandeRepository $suiviCommandeRepository,
    EntityManagerInterface $em,
    MailerService $mailerService
  ): JsonResponse {
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
    $data          = json_decode($request->getContent(), true);
    $nouveauStatut = $data['statut'] ?? null;

    if (!$nouveauStatut) {
      return $this->json(['status' => 'Erreur', 'message' => 'Statut obligatoire'], 400);
    }

    // Étape 4 - Récupérer l'ordre strict du cycle de vie
    $ordreStatuts = CommandeStatut::ORDRE;

    // Étape 5 - Vérifier que le nouveau statut existe dans le cycle
    if (!isset($ordreStatuts[$nouveauStatut])) {
      return $this->json(['status' => 'Erreur', 'message' => 'Statut invalide'], 400);
    }

    // Étape 6 - Vérifier qu'on n'essaie pas de revenir en arrière
    $statutActuel = $commande->getStatut();
    if (isset($ordreStatuts[$statutActuel]) && $ordreStatuts[$nouveauStatut] <= $ordreStatuts[$statutActuel]) {
      return $this->json(['status' => 'Erreur', 'message' => 'Retour en arrière interdit dans le cycle de vie'], 400);
    }

    // Étape 7 - Mettre à jour le statut de la commande
    $commande->setStatut($nouveauStatut);

    // Étape 8 - Créer un suivi pour le statut demandé
    $suivi = new SuiviCommande();
    $suivi->setStatut($nouveauStatut);
    $suivi->setDateStatut(new \DateTime());
    $suivi->setCommande($commande);
    $em->persist($suivi);

    // Étape 9 - Envoyer un email selon le statut et gérer les cas spéciaux
    $client = $commande->getUtilisateur();

    if ($nouveauStatut === CommandeStatut::ACCEPTEE) {
      $mailerService->sendCommandeAccepteeEmail($client, $commande);

    } elseif ($nouveauStatut === CommandeStatut::EN_LIVRAISON) {
      $mailerService->sendCommandeLivraisonEmail($client, $commande);

    } elseif ($nouveauStatut === CommandeStatut::LIVRE) {

      // stocker date_statut_livree (utilisée par le cron pour calculer les 10 jours ouvrés)
      $commande->setDateStatutLivree(new \DateTime());

      if ($commande->isPretMateriel() === false) {
        // cas (0,0) et (0,1) → passage automatique à Terminée
        $commande->setStatut(CommandeStatut::TERMINEE);
        $mailerService->sendCommandeTermineeEmail($client, $commande);

        // Suivi pour le statut Terminée
        $suiviTerminee = new SuiviCommande();
        $suiviTerminee->setStatut(CommandeStatut::TERMINEE);
        $suiviTerminee->setDateStatut(new \DateTime());
        $suiviTerminee->setCommande($commande);
        $em->persist($suiviTerminee);

      } else {
        // cas (1,0) → passage automatique à En attente du retour matériel
        $commande->setStatut(CommandeStatut::EN_ATTENTE_RETOUR_MATERIEL);
        $mailerService->sendRetourMaterielEmail($client, $commande);

        // Suivi pour le statut En attente du retour matériel
        $suiviMateriel = new SuiviCommande();
        $suiviMateriel->setStatut(CommandeStatut::EN_ATTENTE_RETOUR_MATERIEL);
        $suiviMateriel->setDateStatut(new \DateTime());
        $suiviMateriel->setCommande($commande);
        $em->persist($suiviMateriel);
      }

    } elseif ($nouveauStatut === CommandeStatut::TERMINEE) {
      $mailerService->sendCommandeTermineeEmail($client, $commande);
    }

    // Étape 10 - Sauvegarder en base
    $em->flush();

    // Étape 11 - Retourner un message de confirmation avec le statut final réel
    return $this->json([
      'status'  => 'Succès',
      'message' => 'Statut mis à jour : ' . $commande->getStatut()
    ]);
  }

  /**
   * @description Retourne toutes les commandes avec matériel non rendu
   * Cible les commandes où :
   *  pret_materiel == true (matériel prêté)
   *  restitution_materiel == false (pas encore rendu)
   *  statut == "En attente du retour matériel"
   * @param CommandeRepository $commandeRepository Le repository des commandes
   * @return JsonResponse
   */
  #[Route('/commandes/materiels-en-cours', name: 'api_employe_materiels_en_cours', methods: ['GET'])]
  #[OA\Get(summary: 'Commandes avec matériel non rendu', description: 'Retourne les commandes où le matériel a été prêté mais pas encore restitué.')]
  #[OA\Tag(name: 'Employé - Matériel')]
  #[OA\Response(response: 200, description: 'Liste retournée')]
  #[OA\Response(response: 403, description: 'Accès refusé')]
  public function getMaterialEnCours(CommandeRepository $commandeRepository): JsonResponse
  {
    // Étape 1 - Vérifier le rôle EMPLOYE
    if (!$this->isGranted('ROLE_EMPLOYE')) {
      return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
    }

    // Étape 2 - Récupérer toutes les commandes avec matériel non rendu
    $commandes = $commandeRepository->findCommandesMaterielARelancer();

    // Étape 3 - Formater pour éviter la référence circulaire
    $data = array_map([$this, 'formatCommande'], $commandes);

    // Étape 4 - Retourner les commandes en JSON
    return $this->json([
      'status'    => 'Succès',
      'total'     => count($data),
      'commandes' => $data
    ]);
  }

  /**
   * @description Retourne l'état du matériel d'une commande ciblée
   * Permet à l'employé ou l'admin de voir :
   *  si le matériel a été prêté (pret_materiel)
   *  si le matériel a été rendu (restitution_materiel)
   *  si le mail de pénalité a été envoyé (mail_penalite_envoye)
   * @param int $id L'id de la commande
   * @param CommandeRepository $commandeRepository Le repository des commandes
   * @return JsonResponse
   */
  #[Route('/commandes/{id}/materiel', name: 'api_employe_materiel_show', methods: ['GET'])]
  #[OA\Get(summary: 'État du matériel d\'une commande', description: 'Retourne si le matériel a été prêté, rendu, et si le mail de pénalité a été envoyé.')]
  #[OA\Tag(name: 'Employé - Matériel')]
  #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID de la commande', schema: new OA\Schema(type: 'integer'))]
  #[OA\Response(response: 200, description: 'État du matériel retourné')]
  #[OA\Response(response: 403, description: 'Accès refusé')]
  #[OA\Response(response: 404, description: 'Commande non trouvée')]
  public function getMaterielCommande(int $id, CommandeRepository $commandeRepository): JsonResponse
  {
    // Étape 1 - Vérifier le rôle EMPLOYE
    if (!$this->isGranted('ROLE_EMPLOYE')) {
      return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
    }

    // Étape 2 - Récupérer la commande par son id
    $commande = $commandeRepository->find($id);
    if (!$commande) {
      return $this->json(['status' => 'Erreur', 'message' => 'Commande non trouvée'], 404);
    }

    // Étape 3 - Retourner uniquement les infos liées au matériel
    return $this->json([
      'status'               => 'Succès',
      'numero_commande'      => $commande->getNumeroCommande(),
      'pret_materiel'        => $commande->isPretMateriel(),
      'restitution_materiel' => $commande->isRestitutionMateriel(),
      'mail_penalite_envoye' => $commande->isMailPenaliteEnvoye(),
      'statut'               => $commande->getStatut(),
    ]);
  }

  /**
   * @description Confirme le retour du matériel et le paiement de la pénalité
   * Employé ou Admin coche sur le front :
   *  "matériel rendu" -> restitution_materiel = true
   *  "pénalité payée" -> confirmé via le JSON
   * Une fois les deux confirmés -> commande passe automatiquement à "Terminée"
   * Corps JSON attendu : { "restitution_materiel": true, "penalite_payee": true }
   * Si les deux conditions sont remplies -> commande passe automatiquement à Terminée
   */
  #[Route('/commandes/{id}/restitution', name: 'api_employe_materiel_restitution', methods: ['PUT'])]
  #[OA\Put(
    summary: 'Confirmer la restitution du matériel',
    description: 'Confirme le retour du matériel et le paiement de la pénalité. Si les deux sont confirmés, la commande passe automatiquement en Terminée.'
  )]
  #[OA\Tag(name: 'Employé - Matériel')]
  #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID de la commande', schema: new OA\Schema(type: 'integer'))]
  #[OA\RequestBody(required: true, content: new OA\JsonContent(
    properties: [
      new OA\Property(property: 'restitution_materiel', type: 'boolean', example: true),
      new OA\Property(property: 'penalite_payee', type: 'boolean', example: true),
    ]
  ))]
  #[OA\Response(response: 200, description: 'Restitution confirmée (passage en Terminée si conditions remplies)')]
  #[OA\Response(response: 400, description: 'Commande pas en attente du retour matériel ou champs manquants')]
  #[OA\Response(response: 403, description: 'Accès refusé')]
  #[OA\Response(response: 404, description: 'Commande non trouvée')]
  public function confirmerRestitution(
    int $id,
    Request $request,
    CommandeRepository $commandeRepository,
    EntityManagerInterface $em,
    MailerService $mailerService
  ): JsonResponse {
    // Étape 1 - Vérifier le rôle EMPLOYE
    if (!$this->isGranted('ROLE_EMPLOYE')) {
      return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
    }

    // Étape 2 - Récupérer la commande
    $commande = $commandeRepository->find($id);
    if (!$commande) {
      return $this->json(['status' => 'Erreur', 'message' => 'Commande non trouvée'], 404);
    }

    // Étape 3 - Vérifier que la commande est bien en attente du retour matériel
    if ($commande->getStatut() !== CommandeStatut::EN_ATTENTE_RETOUR_MATERIEL) {
      return $this->json(['status' => 'Erreur', 'message' => 'Cette commande n\'est pas en attente du retour matériel'], 400);
    }

    // Étape 4 - Récupérer les données JSON
    $data = json_decode($request->getContent(), true);

    // Étape 5 - Vérifier les champs obligatoires
    if (!isset($data['restitution_materiel']) || !isset($data['penalite_payee'])) {
      return $this->json(['status' => 'Erreur', 'message' => 'Les champs restitution_materiel et penalite_payee sont obligatoires'], 400);
    }

    // Étape 6 - Mettre à jour la restitution du matériel
    $commande->setRestitutionMateriel((bool) $data['restitution_materiel']);

    // Étape 7 - Si les deux conditions sont remplies -> passer en Terminée
    if ($commande->isRestitutionMateriel() === true && (bool) $data['penalite_payee'] === true) {
      $commande->setStatut(CommandeStatut::TERMINEE);
      $mailerService->sendCommandeTermineeEmail($commande->getUtilisateur(), $commande);

      // Enregistrer le suivi du passage à Terminée
      $suivi = new SuiviCommande();
      $suivi->setStatut(CommandeStatut::TERMINEE);
      $suivi->setDateStatut(new \DateTime());
      $suivi->setCommande($commande);
      $em->persist($suivi);
    }

    // Étape 8 - Sauvegarder en base
    $em->flush();

    // Étape 9 - Retourner une confirmation
    return $this->json([
      'status'               => 'Succès',
      'message'              => $commande->getStatut() === CommandeStatut::TERMINEE
        ? 'Matériel rendu et pénalité payée : commande passée en Terminée'
        : 'Informations mises à jour',
      'statut'               => $commande->getStatut(),
      'restitution_materiel' => $commande->isRestitutionMateriel(),
    ]);
  }

  /**
   * @description Filtrer les commandes par statut et/ou par client
   * 
   * @param Request $request La requête HTTP avec les filtres
   * @param CommandeRepository $commandeRepository Le repository des commandes
   * @return JsonResponse
   */
  #[Route('/commandes/filtres', name: 'api_employe_commandes_filtres', methods: ['GET'])]
  #[OA\Get(summary: 'Filtrer les commandes', description: 'Filtre les commandes par statut et/ou par ID utilisateur.')]
  #[OA\Tag(name: 'Employé - Commandes')]
  #[OA\Parameter(name: 'statut', in: 'query', required: false, description: 'Filtrer par statut', schema: new OA\Schema(type: 'string'))]
  #[OA\Parameter(name: 'utilisateur_id', in: 'query', required: false, description: 'Filtrer par ID client', schema: new OA\Schema(type: 'integer'))]
  #[OA\Response(response: 200, description: 'Commandes filtrées')]
  #[OA\Response(response: 403, description: 'Accès refusé')]
  public function filtrerCommandes(
    Request $request,
    CommandeRepository $commandeRepository
  ): JsonResponse {
    // Étape 1 - Vérifier le rôle EMPLOYE
    if (!$this->isGranted('ROLE_EMPLOYE')) {
      return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
    }

    // Étape 2 - Récupérer les filtres depuis la query string
    $statut        = $request->query->get('statut');            // ex: ?statut=En attente
    $utilisateurId = $request->query->get('utilisateur_id');    // ex: ?utilisateur_id=3

    // Étape 3 - Convertir utilisateur_id en int si fourni
    $utilisateurId = $utilisateurId !== null ? (int) $utilisateurId : null;

    // Étape 4 - Appeler le repository avec les filtres
    $commandes = $commandeRepository->findByFiltres($statut, $utilisateurId);

    // Étape 5 - Formater pour éviter la référence circulaire
    $data = array_map([$this, 'formatCommande'], $commandes);

    // Étape 6 - Retourner les résultats
    return $this->json([
      'status'    => 'Succès',
      'total'     => count($data),
      'filtres'   => [
          'statut'         => $statut        ?? 'tous',
          'utilisateur_id' => $utilisateurId ?? 'tous',
      ],
      'commandes' => $data,
    ]);
  }

  /**
   * @description Afficher le suivi de toutes les commandes (sans restriction de propriétaire)
   */
  #[Route('/commandes/{id}/suivi', name: 'api_employe_commande_suivi', methods: ['GET'])]
  #[OA\Get(summary: 'Suivi d\'une commande', description: 'Retourne l\'historique des statuts d\'une commande (sans restriction de propriétaire).')]
  #[OA\Tag(name: 'Employé - Commandes')]
  #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID de la commande', schema: new OA\Schema(type: 'integer'))]
  #[OA\Response(response: 200, description: 'Suivi retourné')]
  #[OA\Response(response: 403, description: 'Accès refusé')]
  #[OA\Response(response: 404, description: 'Commande non trouvée')]
  public function getSuiviCommande(
    int $id,
    CommandeRepository $commandeRepository,
    SuiviCommandeRepository $suiviCommandeRepository
  ): JsonResponse {
    // Étape 1 - Vérifier le rôle EMPLOYE
    if (!$this->isGranted('ROLE_EMPLOYE')) {
      return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
    }

    // Étape 2 - Chercher la commande par son id
    $commande = $commandeRepository->find($id);
    if (!$commande) {
      return $this->json(['status' => 'Erreur', 'message' => 'Commande non trouvée'], 404);
    }

    // Étape 3 - Récupérer les suivis triés du plus ancien au plus récent
    $suivis = $suiviCommandeRepository->findBy(
      ['commande' => $commande],
      ['date_statut' => 'ASC']
    );

    // Étape 4 - Formater les données
    $suivisFormates = [];
    foreach ($suivis as $suivi) {
      $suivisFormates[] = [
        'statut'      => $suivi->getStatut(),
        'date_statut' => $suivi->getDateStatut()->format('d/m/Y H:i'),
      ];
    }

    // Étape 5 - Retourner les suivis en JSON
    return $this->json([
      'status'  => 'Succès',
      'message' => 'Suivi retourné avec succès',
      'total'   => count($suivis),
      'suivis'  => $suivisFormates
    ]);
  }

  // =========================================================================
  // AVIS
  // =========================================================================

  /**
   * @description Afficher tous les avis en attente de validation
   * @param AvisRepository $avisRepository Le repository des avis
   * @return JsonResponse
   */
  #[Route('/avis', name: 'api_employe_avis', methods: ['GET'])]
  #[OA\Get(summary: 'Avis en attente de validation', description: 'Retourne tous les avis clients au statut "en_attente".')]
  #[OA\Tag(name: 'Employé - Avis')]
  #[OA\Response(response: 200, description: 'Liste des avis en attente')]
  #[OA\Response(response: 403, description: 'Accès refusé')]

  public function getAvisEnAttente(AvisRepository $avisRepository): JsonResponse
  {
     // Étape 1 - Vérifier le rôle EMPLOYE
    if (!$this->isGranted('ROLE_EMPLOYE')) {
      return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
    }

    // Étape 2 - Récupérer tous les avis en attente
    $avis = $avisRepository->findBy(['statut' => 'en_attente']);

    // Étape 3 - Formater les données pour éviter la référence circulaire
    $data = [];
    foreach ($avis as $a) {
        $data[] = [
            'id' => $a->getId(),
            'note' => $a->getNote(),
            'description' => $a->getDescription(),
            'statut' => $a->getStatut(),
            'date' => $a->getDate()?->format('d/m/Y H:i:s'),
            'utilisateur_id' => $a->getUtilisateur()?->getId(),
            'utilisateur_nom' => $a->getUtilisateur()?->getNom(),
            'commande_id' => $a->getCommande()?->getId(),
        ];
    }

    // Étape 4 - Retourner les avis en JSON
    return $this->json(['status' => 'Succès', 'total' => count($data), 'avis' => $data]);
  }

  /**
   * @description Approuver un avis client
   * @param int $id L'id de l'avis
   * @param AvisRepository $avisRepository Le repository des avis
   * @param EntityManagerInterface $em L'EntityManager
   * @return JsonResponse
   */
  #[Route('/avis/{id}/approuver', name: 'api_employe_avis_approuver', methods: ['PUT'])]
  #[OA\Put(summary: 'Approuver un avis', description: 'Valide un avis client en attente. Le statut passe à "validé".')]
  #[OA\Tag(name: 'Employé - Avis')]
  #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID de l\'avis', schema: new OA\Schema(type: 'integer'))]
  #[OA\Response(response: 200, description: 'Avis approuvé')]
  #[OA\Response(response: 400, description: 'Avis pas en attente')]
  #[OA\Response(response: 403, description: 'Accès refusé')]
  #[OA\Response(response: 404, description: 'Avis non trouvé')]
  public function approuverAvis(int $id, AvisRepository $avisRepository, EntityManagerInterface $em): JsonResponse
  {
    // Étape 1 - Vérifier le rôle EMPLOYE
    if (!$this->isGranted('ROLE_EMPLOYE') && !$this->isGranted('ROLE_ADMIN')) { 
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

  /**
   * @description Refuser un avis client
   * @param int $id L'id de l'avis
   * @param AvisRepository $avisRepository Le repository des avis
   * @param EntityManagerInterface $em L'EntityManager
   * @return JsonResponse
   */
  #[Route('/avis/{id}/refuser', name: 'api_employe_avis_refuser', methods: ['PUT'])]
  #[OA\Put(summary: 'Refuser un avis', description: 'Refuse un avis client en attente. Le statut passe à "refusé".')]
  #[OA\Tag(name: 'Employé - Avis')]
  #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID de l\'avis', schema: new OA\Schema(type: 'integer'))]
  #[OA\Response(response: 200, description: 'Avis refusé')]
  #[OA\Response(response: 400, description: 'Avis pas en attente')]
  #[OA\Response(response: 403, description: 'Accès refusé')]
  #[OA\Response(response: 404, description: 'Avis non trouvé')]
  public function refuserAvis(int $id, AvisRepository $avisRepository, EntityManagerInterface $em): JsonResponse
  {
    // Étape 1 - Vérifier le rôle EMPLOYE
    if (!$this->isGranted('ROLE_EMPLOYE') && !$this->isGranted('ROLE_ADMIN')) {
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

    // Étape 4 - Refuser l'avis
    $avis->setStatut('refusé');
    $em->flush();

    // Étape 5 - Retourner un message de confirmation
    return $this->json(['status' => 'Succès', 'message' => 'Avis refusé avec succès']);
  }

  // =========================================================================
  // MENUS
  // =========================================================================

  /**
   * @description Créer un nouveau menu
   * Corps JSON attendu : { "titre": "...", "nombre_personne_minimum": 10, "prix_par_personne": 45.00,
   *                        "description": "...", "conditions": "...", "quantite_restante": 50,
   *                        "regime_id": 2, "theme_id": 3, "plats": [1, 4, 7] }
   * @param Request $request La requête HTTP contenant les données au format JSON
   * @param MenuRepository $menuRepository Le repository des menus
   * @param PlatRepository $platRepository Le repository des plats
   * @param RegimeRepository $regimeRepository Le repository des régimes
   * @param ThemeRepository $themeRepository Le repository des thèmes
   * @param EntityManagerInterface $em L'EntityManager pour gérer les opérations de base de données
   * @return JsonResponse
   */
  #[Route('/menus', name: 'api_employe_menus_create', methods: ['POST'])]
  #[OA\Post(summary: 'Créer un menu', description: 'Crée un nouveau menu avec régime, thème et plats associés.')]
  #[OA\Tag(name: 'Employé - Menus')]
  #[OA\RequestBody(required: true, content: new OA\JsonContent(
    properties: [
      new OA\Property(property: 'titre', type: 'string', example: 'Menu Prestige'),
      new OA\Property(property: 'nombre_personne_minimum', type: 'integer', example: 10),
      new OA\Property(property: 'prix_par_personne', type: 'number', example: 75.00),
      new OA\Property(property: 'description', type: 'string', example: 'Un menu raffiné...'),
      new OA\Property(property: 'conditions', type: 'string', example: 'Commander 14 jours à l\'avance'),
      new OA\Property(property: 'quantite_restante', type: 'integer', example: 50),
      new OA\Property(property: 'regime_id', type: 'integer', example: 2),
      new OA\Property(property: 'theme_id', type: 'integer', example: 3),
      new OA\Property(property: 'plats', type: 'array', items: new OA\Items(type: 'integer'), example: '[1, 4, 7]'),
    ]
  ))]
  #[OA\Response(response: 201, description: 'Menu créé')]
  #[OA\Response(response: 400, description: 'Champs manquants')]
  #[OA\Response(response: 403, description: 'Accès refusé')]
  #[OA\Response(response: 404, description: 'Régime, thème ou plat non trouvé')]
  #[OA\Response(response: 409, description: 'Titre déjà utilisé')]
  public function createMenu(
    Request $request,
    MenuRepository $menuRepository,
    PlatRepository $platRepository,
    RegimeRepository $regimeRepository,
    ThemeRepository $themeRepository,
    EntityManagerInterface $em
  ): JsonResponse {

    // Étape 1 - Vérifier le rôle EMPLOYE
    if (!$this->isGranted('ROLE_EMPLOYE')) {
      return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
    }

    // Étape 2 - Récupérer les données JSON
    $data = json_decode($request->getContent(), true);
    if ($data === null) {
      return $this->json(['status' => 'Erreur', 'message' => 'JSON invalide'], 400);
    }

    // Étape 3 - Vérifier les champs obligatoires
    $champsObligatoires = ['titre', 'nombre_personne_minimum', 'prix_par_personne', 'description', 'quantite_restante', 'regime_id', 'theme_id'];
    foreach ($champsObligatoires as $champ) {
      if (!isset($data[$champ]) || $data[$champ] === '') {
          return $this->json(['status' => 'Erreur', 'message' => "Le champ $champ est obligatoire"], 400);
      }
    }

    // Validation supplémentaire pour les valeurs
    if ($data['prix_par_personne'] <= 0) {
        return $this->json(['status' => 'Erreur', 'message' => 'Le prix par personne doit être supérieur à 0'], 400);
    }
    if ($data['quantite_restante'] <= 0) {
        return $this->json(['status' => 'Erreur', 'message' => 'La quantité restante doit être supérieure à 0'], 400);
    }

    // Étape 4 - Vérifier que le titre n'existe pas déjà
    $existant = $menuRepository->findOneBy(['titre' => $data['titre']]);
    if ($existant) {
      return $this->json(['status' => 'Erreur', 'message' => 'Un menu avec ce titre existe déjà'], 409);
    }

    // Étape 5 - Récupérer le régime
    $regime = $regimeRepository->find($data['regime_id']);
    if (!$regime) {
      return $this->json(['status' => 'Erreur', 'message' => 'Régime non trouvé'], 404);
    }

    // Étape 6 - Récupérer le thème
    $theme = $themeRepository->find($data['theme_id']);
    if (!$theme) {
      return $this->json(['status' => 'Erreur', 'message' => 'Thème non trouvé'], 404);
    }

    // Étape 7 - Créer le menu
    $menu = new Menu();
    $menu->setTitre($data['titre']);
    $menu->setNombrePersonneMinimum((int) $data['nombre_personne_minimum']);
    $menu->setPrixParPersonne((float) $data['prix_par_personne']);
    $menu->setDescription($data['description']);
    $menu->setQuantiteRestante((int) $data['quantite_restante']);
    $menu->setRegime($regime);
    $menu->setTheme($theme);

    // Étape 7.1 - Conditions du menu (optionnel)
    // Ex: "Commander minimum 14 jours à l'avance. Conserver au frais."
    if (isset($data['conditions'])) {
      $menu->setConditions($data['conditions']);
    }

    // Étape 8 - Associer les plats si fournis
    if (!empty($data['plats']) && is_array($data['plats'])) {
      // Supprimer doublons
      $platsUnique = array_unique($data['plats']);

      // Limite max 3 plats
      if (count($platsUnique) > 3) {
          return $this->json(['status' => 'Erreur', 'message' => 'Un menu ne peut contenir que 3 plats maximum'], 400);
      }
      foreach ($platsUnique as $platId) {
        $plat = $platRepository->find($platId);
        if (!$plat) {
            return $this->json(['status' => 'Erreur', 'message' => "Plat id $platId non trouvé"], 404);
        }
        $menu->addPlat($plat);
          
        $platsRetour[] = [
          'id' => $plat->getId(),
          'titre' => $plat->getTitrePlat(),
          'categorie' => $plat->getCategorie(),
          'photo' => $plat->getPhoto(),
        ];
      }
    }

    // Étape 9 - Persister et sauvegarder
    $em->persist($menu);
    
    // Étape 10 - Sauvegarder
    $em->flush();

    // Étape 11 - Retourner les données
    return $this->json([
      'status' => 'Succès',
      'message' => 'Menu créé avec succès',
      'menu' => [
        'id' => $menu->getId(),
        'titre' => $menu->getTitre(),
        'nombre_personne_minimum' => $menu->getNombrePersonneMinimum(),
        'prix_par_personne' => $menu->getPrixParPersonne(),
        'description' => $menu->getDescription(),
        'conditions' => $menu->getConditions(),
        'quantite_restante' => $menu->getQuantiteRestante(),
        'regime' => $menu->getRegime()->getLibelle(),
        'theme' => $menu->getTheme()->getLibelle(),
        'plats' => $platsRetour
      ]
    ], 201);
}

  /**
   * @description Met à jour un menu par son id
   * Les plats envoyés REMPLACENT les anciens
   * @param int $id L'id du menu à modifier
   * @param Request $request La requête HTTP contenant les données au format JSON
   * @param MenuRepository $menuRepository Le repository des menus
   * @param PlatRepository $platRepository Le repository des plats
   * @param RegimeRepository $regimeRepository Le repository des régimes
   * @param ThemeRepository $themeRepository Le repository des thèmes
   * @param EntityManagerInterface $em L'EntityManager pour gérer les opérations de base de données
   * @return JsonResponse
   */
  #[Route('/menus/{id}', name: 'api_employe_menus_update', methods: ['PUT'])]
  #[OA\Put(summary: 'Modifier un menu', description: 'Met à jour un menu. Les plats envoyés REMPLACENT les anciens.')]
  #[OA\Tag(name: 'Employé - Menus')]
  #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID du menu', schema: new OA\Schema(type: 'integer'))]
  #[OA\RequestBody(required: true, content: new OA\JsonContent(properties: [
    new OA\Property(property: 'titre', type: 'string'), new OA\Property(property: 'nombre_personne_minimum', type: 'integer'),
    new OA\Property(property: 'prix_par_personne', type: 'number'), new OA\Property(property: 'description', type: 'string'),
    new OA\Property(property: 'conditions', type: 'string'), new OA\Property(property: 'quantite_restante', type: 'integer'),
    new OA\Property(property: 'regime_id', type: 'integer'), new OA\Property(property: 'theme_id', type: 'integer'),
    new OA\Property(property: 'plats', type: 'array', items: new OA\Items(type: 'integer')),
  ]))]
  #[OA\Response(response: 200, description: 'Menu mis à jour')]
  #[OA\Response(response: 403, description: 'Accès refusé')]
  #[OA\Response(response: 404, description: 'Menu, régime, thème ou plat non trouvé')]
  #[OA\Response(response: 409, description: 'Titre déjà utilisé')]
  public function updateMenu(
    int $id,
    Request $request,
    MenuRepository $menuRepository,
    PlatRepository $platRepository,
    RegimeRepository $regimeRepository,
    ThemeRepository $themeRepository,
    EntityManagerInterface $em
  ): JsonResponse {
      
    // Étape 1 - Vérifier le rôle EMPLOYE
    if (!$this->isGranted('ROLE_EMPLOYE')) {
      return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
    }

    // Étape 2 - Récupérer les données JSON
    $data = json_decode($request->getContent(), true);
    if ($data === null) {
      return $this->json(['status' => 'Erreur', 'message' => 'JSON invalide'], 400);
    }

    // Étape 3 - Chercher le menu à modifier
    $menu = $menuRepository->find($id);
    if (!$menu) {
      return $this->json(['status' => 'Erreur', 'message' => 'Menu non trouvé'], 404);
    }

    // Étape 4 - Mise à jour des champs
    if (isset($data['titre'])) {
      $existant = $menuRepository->findOneBy(['titre' => $data['titre']]);
      if ($existant && $existant->getId() !== $menu->getId()) {
        return $this->json(['status' => 'Erreur', 'message' => 'Un menu avec ce titre existe déjà'], 409);
      }
      $menu->setTitre($data['titre']);
    }

    if (isset($data['description'])) {
      $menu->setDescription($data['description']);
    }

    if (isset($data['nombre_personne_minimum'])) {
      if (!is_numeric($data['nombre_personne_minimum']) || $data['nombre_personne_minimum'] < 1) {
        return $this->json(['status' => 'Erreur', 'message' => 'Le nombre de personnes doit être un entier ≥ 1'], 400);
      }
      $menu->setNombrePersonneMinimum($data['nombre_personne_minimum']);
    }

    if (isset($data['prix_par_personne'])) {
      if (!is_numeric($data['prix_par_personne']) || $data['prix_par_personne'] <= 0) {
        return $this->json(['status' => 'Erreur', 'message' => 'Le prix par personne doit être > 0'], 400);
      }
      $menu->setPrixParPersonne((int) $data['prix_par_personne']);
    }

    if (isset($data['quantite_restante'])) {
      if (!is_numeric($data['quantite_restante']) || $data['quantite_restante'] < 1) {
        return $this->json(['status' => 'Erreur', 'message' => 'La quantité restante doit être un entier ≥ 1'], 400);
      }
      $menu->setQuantiteRestante($data['quantite_restante']);
    }

    // Étape 5 - Mise à jour des conditions (optionnel)
    // Ex: "Commander minimum 14 jours à l'avance. Conserver au frais."
    if (isset($data['conditions'])) {
      $menu->setConditions($data['conditions']);
    }

    // Étape 6 - Mettre à jour le régime si fourni
    if (isset($data['regime_id'])) {
      $regime = $regimeRepository->find($data['regime_id']);
      if (!$regime) {
        return $this->json(['status' => 'Erreur', 'message' => 'Régime non trouvé'], 404);
      }
      $menu->setRegime($regime);
    }

    // Étape 7 - Mettre à jour le thème si fourni
    if (isset($data['theme_id'])) {
      $theme = $themeRepository->find($data['theme_id']);

      if (!$theme) {
          
        return $this->json(['status' => 'Erreur', 'message' => 'Thème non trouvé'], 404);
      }
      $menu->setTheme($theme);
    }

    // Étape 8 - Mise à jour des plats
    $platsRetour = [];
    if (isset($data['plats']) && is_array($data['plats'])) {
      // S'assurer que ce sont des entiers
      foreach ($data['plats'] as $platId) {
        if (!is_int($platId)) return $this->json(['status'=>'Erreur','message'=>'Chaque plat doit être un entier'], 400);
      }
      // Supprimer doublons
      $platsUnique = array_unique($data['plats']);
      // Vérifier limite max 3 plats
      if (count($platsUnique) > 3) {
        return $this->json(['status' => 'Erreur', 'message' => 'Un menu ne peut contenir que 3 plats maximum'], 400);
      }

      // Remplacer les plats existants
      foreach ($menu->getPlats() as $platExistant) {
        $menu->removePlat($platExistant);
      }

      foreach ($platsUnique as $platId) {
        $plat = $platRepository->find($platId);
        if (!$plat) return $this->json(['status' => 'Erreur', 'message' => "Plat id $platId non trouvé"], 404);
        $menu->addPlat($plat);
        
        $platsRetour[] = [
          'id' => $plat->getId(),
          'titre' => $plat->getTitrePlat(),
          'categorie' => $plat->getCategorie(),
          'photo' => $plat->getPhoto(),
        ];
      }
    }
    // Étape 8 - Sauvegarder
    $em->flush();

    // Étape 9 - Retourner une confirmation
    return $this->json([
        'status' => 'Succès',
        'message' => 'Menu mis à jour avec succès',
        'menu' => [
          'id' => $menu->getId(),
          'titre' => $menu->getTitre(),
          'nombre_personne_minimum' => $menu->getNombrePersonneMinimum(),
          'prix_par_personne' => $menu->getPrixParPersonne(),
          'description' => $menu->getDescription(),
          'conditions' => $menu->getConditions(),
          'quantite_restante' => $menu->getQuantiteRestante(),
          'regime' => $menu->getRegime()->getLibelle(),
          'theme' => $menu->getTheme()->getLibelle(),
          'plats' => $platsRetour
        ]
      ]);
  }

  /**
   * @description Supprimer un menu par son id
   * La suppression ne supprime que le menu et ses images, 
   * mais interdit la suppression si des clients ont déjà commandé ce menu
   * @param int $id L'id du menu à supprimer
   * @param MenuRepository $menuRepository Le repository des menus
   * @param EntityManagerInterface $em L'EntityManager pour gérer les opérations de base de données
   * @return JsonResponse
   */
  #[Route('/menus/{id}', name: 'api_employe_menus_delete', methods: ['DELETE'])]
  #[OA\Delete(summary: 'Supprimer un menu', description: 'Supprime un menu et ses images si aucune commande client n’est associée.')]
  #[OA\Tag(name: 'Employé - Menus')]
  #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
  #[OA\Response(response: 200, description: 'Menu supprimé')]
  #[OA\Response(response: 403, description: 'Accès refusé')]
  #[OA\Response(response: 404, description: 'Menu non trouvé')]
  #[OA\Response(response: 409, description: 'Menu déjà commandé par des clients')]
  public function deleteMenu(int $id, MenuRepository $menuRepository, EntityManagerInterface $em): JsonResponse
  {
      // Étape 1 - Vérifier le rôle EMPLOYE
      if (!$this->isGranted('ROLE_EMPLOYE')) {
        return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
      }

      // Étape 2 - Chercher le menu à supprimer
      $menu = $menuRepository->find($id);
      if (!$menu) {
        return $this->json(['status' => 'Erreur', 'message' => 'Menu non trouvé'], 404);
      }

      // Étape 3 - Vérifier si le menu a déjà été commandé
      if ($menu->getCommandes()->count() > 0) {
        return $this->json([
          'status' => 'Erreur',
          'message' => 'Impossible de supprimer ce menu, il a été commandé par des clients'
        ], 409);
      }

      // Étape 4 - Supprimer le menu et ses images (cascade sur images uniquement)
      $em->remove($menu);

      // Étape 5 - Sauvegarder
      $em->flush();

      // Étape 6 - Retourner une confirmation
      return $this->json(['status' => 'Succès', 'message' => 'Menu supprimé avec succès']);
  }

  /**
   * Fonction pour uploader une image via l'API.
   * 
   * Cette route permet d'envoyer une image qui sera uploadée
   * sur Cloudinary. La fonction vérifie le rôle, le fichier, son type et sa taille,
   * puis renvoie l'URL de l'image en cas de succès.*/
/**
   * @description Upload une image vers Cloudinary via le back
   * Reçoit un fichier en multipart/form-data
   * Retourne l'URL publique Cloudinary
   */
  #[Route('/upload/image', name: 'api_employe_upload_image', methods: ['POST'])]
  #[OA\Post(summary: 'Upload une image', description: 'Upload un fichier image vers Cloudinary. Retourne l\'URL publique.')]
  #[OA\Tag(name: 'Employé - Upload')]
  #[OA\RequestBody(required: true, content: new OA\MediaType(
      mediaType: 'multipart/form-data',
      schema: new OA\Schema(properties: [
          new OA\Property(property: 'image', type: 'string', format: 'binary')
      ])
  ))]
  #[OA\Response(response: 200, description: 'Image uploadée, URL retournée')]
  #[OA\Response(response: 400, description: 'Fichier manquant ou format invalide')]
  #[OA\Response(response: 403, description: 'Accès refusé')]
  public function uploadImage(
      Request $request,
      CloudinaryService $cloudinaryService
  ): JsonResponse {

    // Étape 1 - Vérifier le rôle EMPLOYE
    if (!$this->isGranted('ROLE_EMPLOYE')) {
      return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
    }

    // Étape 2 - Récupérer le fichier
    $fichier = $request->files->get('image');
    if (!$fichier) {
      return $this->json(['status' => 'Erreur', 'message' => 'Aucun fichier reçu'], 400);
    }

    // Étape 3 - Validation type MIME
    $typesAutorises = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($fichier->getMimeType(), $typesAutorises)) {
      return $this->json(['status' => 'Erreur', 'message' => 'Format invalide (JPG, PNG, WebP uniquement)'], 400);
    }

    // Étape 4 - Validation taille (5 Mo max)
    if ($fichier->getSize() > 5 * 1024 * 1024) {
      return $this->json(['status' => 'Erreur', 'message' => 'Fichier trop volumineux (max 5 Mo)'], 400);
    }

    // Étape 5 - Upload vers Cloudinary
    try {
      $url = $cloudinaryService->upload($fichier->getPathname());

      return $this->json([
        'status' => 'Succès',
        'message' => 'Image uploadée avec succès',
        'url' => $url
      ]);
    } catch (\Exception $e) {
      return $this->json([
        'status' => 'Erreur',
        'message' => 'Erreur upload Cloudinary : ' . $e->getMessage()
      ], 500);
    }
  }

  // =========================================================================
  // THEMES
  // =========================================================================

  /**
   * @description Créer un nouveau thème
   * Corps JSON attendu : { "libelle": "Noël" }
   * @param Request $request La requête HTTP contenant les données au format JSON
   * @param ThemeRepository $themeRepository Le repository des thèmes
   * @param EntityManagerInterface $em L'EntityManager pour gérer les opérations de base de données
   * @return JsonResponse
   */

  #[Route('/themes', name: 'api_employe_themes_create', methods: ['POST'])]
  #[OA\Post(summary: 'Créer un thème', description: 'Crée un nouveau thème pour les menus.')]
  #[OA\Tag(name: 'Employé - Référentiels')]
  #[OA\RequestBody(required: true, content: new OA\JsonContent(properties: [new OA\Property(property: 'libelle', type: 'string', example: 'Noël')]))]
  #[OA\Response(response: 201, description: 'Thème créé')]  #[OA\Response(response: 400, description: 'Libellé manquant')]  #[OA\Response(response: 403, description: 'Accès refusé')]  #[OA\Response(response: 409, description: 'Thème déjà existant')]
  public function createTheme(Request $request, ThemeRepository $themeRepository, EntityManagerInterface $em): JsonResponse
  {
    // Étape 1 - Vérifier le rôle EMPLOYE
    if (!$this->isGranted('ROLE_EMPLOYE')) {
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

    // Étape 5 - Créer et persister le nouveau thème
    $theme = new Theme();
    $theme->setLibelle($data['libelle']);
    $em->persist($theme);
    $em->flush();

    // Étape 6 - Retourner une confirmation avec l'id créé
    return $this->json(['status' => 'Succès', 'message' => 'Thème créé avec succès', 'id' => $theme->getId()], 201);
  }

  /**
   * @description Met à jour un thème par son id
   * Corps JSON attendu : { "libelle": "Mariage" }
   * @param int $id L'id du thème à modifier
   * @param Request $request La requête HTTP contenant les données au format JSON
   * @param ThemeRepository $themeRepository Le repository des thèmes
   * @param EntityManagerInterface $em L'EntityManager pour gérer les opérations de base de données
   * @return JsonResponse
   */
  #[Route('/themes/{id}', name: 'api_employe_themes_update', methods: ['PUT'])]
  #[OA\Put(summary: 'Modifier un thème', description: 'Met à jour le libellé d\'un thème.')]
  #[OA\Tag(name: 'Employé - Référentiels')]
  #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
  #[OA\RequestBody(required: true, content: new OA\JsonContent(properties: [new OA\Property(property: 'libelle', type: 'string', example: 'Mariage')]))]
  #[OA\Response(response: 200, description: 'Thème mis à jour')]  
  #[OA\Response(response: 403, description: 'Accès refusé')]  
  #[OA\Response(response: 404, description: 'Non trouvé')]  
  #[OA\Response(response: 409, description: 'Libellé déjà utilisé')]
  public function updateTheme(int $id, Request $request, ThemeRepository $themeRepository, EntityManagerInterface $em): JsonResponse
  {
    // Étape 1 - Vérifier le rôle EMPLOYE
    if (!$this->isGranted('ROLE_EMPLOYE')) {
      return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
    }

    // Étape 2 - Récupérer les données JSON
    $data = json_decode($request->getContent(), true);

    // Étape 3 - Chercher le thème à modifier
    $theme = $themeRepository->find($id);
    if (!$theme) {
      return $this->json(['status' => 'Erreur', 'message' => 'Thème non trouvé'], 404);
    }

    // Étape 4 - Récupérer les données JSON
    $data = json_decode($request->getContent(), true);

    // Étape 5 - Mettre à jour le libellé si fourni
    if (isset($data['libelle'])) {
      $existant = $themeRepository->findOneBy(['libelle' => $data['libelle']]);
      if ($existant && $existant->getId() !== $theme->getId()) {
          return $this->json(['status' => 'Erreur', 'message' => 'Ce libellé est déjà utilisé'], 409);
      }
      $theme->setLibelle($data['libelle']);
    }

    // Étape 6 - Sauvegarder
    $em->flush();

    // Étape 7 - Retourner une confirmation
    return $this->json(['status' => 'Succès', 'message' => 'Thème mis à jour avec succès']);
  }

  /**
   * @description Supprimer un thème par son id
   */
  #[Route('/themes/{id}', name: 'api_employe_themes_delete', methods: ['DELETE'])]
  #[OA\Delete(summary: 'Supprimer un thème')]  
  #[OA\Tag(name: 'Employé - Référentiels')]
  #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
  #[OA\Response(response: 200, description: 'Supprimé')]  
  #[OA\Response(response: 403, description: 'Accès refusé')]  
  #[OA\Response(response: 404, description: 'Non trouvé')]
  public function deleteTheme(int $id, ThemeRepository $themeRepository, EntityManagerInterface $em): JsonResponse
  {
    // Étape 1 - Vérifier le rôle EMPLOYE
    if (!$this->isGranted('ROLE_EMPLOYE')) {
      return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
    }

    // Étape 2 - Chercher le thème à supprimer
    $theme = $themeRepository->find($id);
    if (!$theme) {
      return $this->json(['status' => 'Erreur', 'message' => 'Thème non trouvé'], 404);
    }

    // Étape 3 - Supprimer
    $em->remove($theme);
    $em->flush();

    // Étape 4 - Retourner une confirmation
    return $this->json(['status' => 'Succès', 'message' => 'Thème supprimé avec succès']);
  }

  // =========================================================================
  // REGIMES
  // =========================================================================

  /**
   * @description Créer un nouveau régime
   * Corps JSON attendu : { "libelle": "Végétarien" }
   * @param Request $request La requête HTTP contenant les données au format JSON
   * @param RegimeRepository $regimeRepository Le repository des régimes
   * @param EntityManagerInterface $em L'EntityManager pour gérer les opérations de base de données
   * @return JsonResponse
   */
  #[Route('/regimes', name: 'api_employe_regimes_create', methods: ['POST'])]
  #[OA\Post(summary: 'Créer un régime')]  #[OA\Tag(name: 'Employé - Référentiels')]
  #[OA\RequestBody(required: true, content: new OA\JsonContent(properties: [new OA\Property(property: 'libelle', type: 'string', example: 'Végétarien')]))]
  #[OA\Response(response: 201, description: 'Créé')]  
  #[OA\Response(response: 400, description: 'Libellé manquant')]  
  #[OA\Response(response: 403, description: 'Accès refusé')]  
  #[OA\Response(response: 409, description: 'Déjà existant')]
  public function createRegime(Request $request, RegimeRepository $regimeRepository, EntityManagerInterface $em): JsonResponse
  {
    // Étape 1 - Vérifier le rôle EMPLOYE
    if (!$this->isGranted('ROLE_EMPLOYE')) {
      return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
    }

    // Étape 2 - Récupérer les données JSON
    $data = json_decode($request->getContent(), true);

    // Étape 3 - Vérifier que le libellé est présent
    if (empty($data['libelle'])) {
      return $this->json(['status' => 'Erreur', 'message' => 'Le libellé est obligatoire'], 400);
    }

    // Étape 4 - Vérifier que le libellé n'existe pas déjà
    $existant = $regimeRepository->findOneBy(['libelle' => $data['libelle']]);
    if ($existant) {
      return $this->json(['status' => 'Erreur', 'message' => 'Ce régime existe déjà'], 409);
    }

    // Étape 5 - Créer le nouveau régime
    $regime = new Regime();
    $regime->setLibelle($data['libelle']);
    $em->persist($regime);
    $em->flush();

    // Étape 6 - Retourner une confirmation avec l'id créé
    return $this->json(['status' => 'Succès', 'message' => 'Régime créé avec succès', 'id' => $regime->getId()], 201);
  }

  /**
   * @description Met à jour un régime par son id
   * Corps JSON attendu : { "libelle": "Vegan" }
   * @param int $id L'id du régime à modifier
   * @param Request $request La requête HTTP contenant les données au format JSON
   * @param RegimeRepository $regimeRepository Le repository des régimes
   * @param EntityManagerInterface $em L'EntityManager pour gérer les opérations de base de données
   * @return JsonResponse
   */
  #[Route('/regimes/{id}', name: 'api_employe_regimes_update', methods: ['PUT'])]
  #[OA\Put(summary: 'Modifier un régime')]  #[OA\Tag(name: 'Employé - Référentiels')]
  #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
  #[OA\RequestBody(required: true, content: new OA\JsonContent(properties: [new OA\Property(property: 'libelle', type: 'string', example: 'Vegan')]))]
  #[OA\Response(response: 200, description: 'Mis à jour')]  
  #[OA\Response(response: 403, description: 'Accès refusé')]  
  #[OA\Response(response: 404, description: 'Non trouvé')]  
  #[OA\Response(response: 409, description: 'Libellé déjà utilisé')]
  public function updateRegime(int $id, Request $request, RegimeRepository $regimeRepository, EntityManagerInterface $em): JsonResponse
  {
    // Étape 1 - Vérifier le rôle EMPLOYE
    if (!$this->isGranted('ROLE_EMPLOYE')) {
      return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
    }

    // Étape 2 - Récupérer les données JSON
    $data = json_decode($request->getContent(), true);

    // Étape 3 - Chercher le régime à modifier
    $regime = $regimeRepository->find($id);
    if (!$regime) {
      return $this->json(['status' => 'Erreur', 'message' => 'Régime non trouvé'], 404);
    }

    // Étape 4 - Récupérer les données JSON
    $data = json_decode($request->getContent(), true);

    // Étape 5 - Mettre à jour le libellé si fourni
    if (isset($data['libelle'])) {
      $existant = $regimeRepository->findOneBy(['libelle' => $data['libelle']]);
      if ($existant && $existant->getId() !== $regime->getId()) {
        return $this->json(['status' => 'Erreur', 'message' => 'Ce libellé est déjà utilisé'], 409);
      }
      $regime->setLibelle($data['libelle']);
    }

    // Étape 6 - Sauvegarder
    $em->flush();

    // Étape 7 - Retourner une confirmation
    return $this->json(['status' => 'Succès', 'message' => 'Régime mis à jour avec succès']);
  }

  /**
   * @description Supprimer un régime par son id
   */
  #[Route('/regimes/{id}', name: 'api_employe_regimes_delete', methods: ['DELETE'])]
  #[OA\Delete(summary: 'Supprimer un régime')]  #[OA\Tag(name: 'Employé - Référentiels')]
  #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
  #[OA\Response(response: 200, description: 'Supprimé')]  
  #[OA\Response(response: 403, description: 'Accès refusé')]  
  #[OA\Response(response: 404, description: 'Non trouvé')]
  public function deleteRegime(int $id, RegimeRepository $regimeRepository, EntityManagerInterface $em): JsonResponse
  {
    // Étape 1 - Vérifier le rôle EMPLOYE
    if (!$this->isGranted('ROLE_EMPLOYE')) {
      return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
    }

    // Étape 2 - Chercher le régime à supprimer
    $regime = $regimeRepository->find($id);
    if (!$regime) {
      return $this->json(['status' => 'Erreur', 'message' => 'Régime non trouvé'], 404);
    }

    // Étape 3 - Supprimer
    $em->remove($regime);
    $em->flush();

    // Étape 4 - Retourner une confirmation
    return $this->json(['status' => 'Succès', 'message' => 'Régime supprimé avec succès']);
  }

  // =========================================================================
  // ALLERGENES
  // =========================================================================

  /**
   * @description Créer un nouvel allergène
   * Corps JSON attendu : { "libelle": "Gluten" }
   */
  #[Route('/allergenes', name: 'api_employe_allergenes_create', methods: ['POST'])]
  #[OA\Post(summary: 'Créer un allergène')]  #[OA\Tag(name: 'Employé - Référentiels')]
  #[OA\RequestBody(required: true, content: new OA\JsonContent(properties: [new OA\Property(property: 'libelle', type: 'string', example: 'Gluten')]))]
  #[OA\Response(response: 201, description: 'Créé')]  
  #[OA\Response(response: 400, description: 'Libellé manquant')]  
  #[OA\Response(response: 403, description: 'Accès refusé')]  
  #[OA\Response(response: 409, description: 'Déjà existant')]
  public function createAllergene(Request $request, AllergeneRepository $allergeneRepository, EntityManagerInterface $em): JsonResponse
  {
    // Étape 1 - Vérifier le rôle EMPLOYE
    if (!$this->isGranted('ROLE_EMPLOYE')) {
      return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
    }

    // Étape 2 - Récupérer les données JSON
    $data = json_decode($request->getContent(), true);

    // Étape 3 - Vérifier que le libellé est présent
    if (empty($data['libelle'])) {
      return $this->json(['status' => 'Erreur', 'message' => 'Le libellé est obligatoire'], 400);
    }

    // Étape 4 - Vérifier que le libellé n'existe pas déjà
    $existant = $allergeneRepository->findOneBy(['libelle' => $data['libelle']]);
    if ($existant) {
      return $this->json(['status' => 'Erreur', 'message' => 'Cet allergène existe déjà'], 409);
    }

    // Étape 5 - Créer et persister l'allergène
    $allergene = new Allergene();
    $allergene->setLibelle($data['libelle']);
    $em->persist($allergene);
    $em->flush();

    // Étape 6 - Retourner une confirmation
    return $this->json(['status' => 'Succès', 'message' => 'Allergène créé avec succès', 'id' => $allergene->getId()], 201);
  }

  /**
   * @description Met à jour un allergène par son id
   * Corps JSON attendu : { "libelle": "Lactose" }
   */
  #[Route('/allergenes/{id}', name: 'api_employe_allergenes_update', methods: ['PUT'])]
  #[OA\Put(summary: 'Modifier un allergène')]  #[OA\Tag(name: 'Employé - Référentiels')]
  #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
  #[OA\RequestBody(required: true, content: new OA\JsonContent(properties: [new OA\Property(property: 'libelle', type: 'string', example: 'Lactose')]))]
  #[OA\Response(response: 200, description: 'Mis à jour')]  
  #[OA\Response(response: 403, description: 'Accès refusé')]  
  #[OA\Response(response: 404, description: 'Non trouvé')]  
  #[OA\Response(response: 409, description: 'Libellé déjà utilisé')]
  public function updateAllergene(int $id, Request $request, AllergeneRepository $allergeneRepository, EntityManagerInterface $em): JsonResponse
  {
    // Étape 1 - Vérifier le rôle EMPLOYE
    if (!$this->isGranted('ROLE_EMPLOYE')) {
      return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
    }

    // Étape 2 - Récupérer les données JSON
    $data = json_decode($request->getContent(), true);

    // Étape 3 - Chercher l'allergène à modifier
    $allergene = $allergeneRepository->find($id);
    if (!$allergene) {
      return $this->json(['status' => 'Erreur', 'message' => 'Allergène non trouvé'], 404);
    }

    // Étape 3 - Récupérer les données JSON
    $data = json_decode($request->getContent(), true);

    // Étape 4 - Mettre à jour le libellé si fourni
    if (isset($data['libelle'])) {
      $existant = $allergeneRepository->findOneBy(['libelle' => $data['libelle']]);
      if ($existant && $existant->getId() !== $allergene->getId()) {
          return $this->json(['status' => 'Erreur', 'message' => 'Ce libellé est déjà utilisé'], 409);
      }
      $allergene->setLibelle($data['libelle']);
    }

    // Étape 5 - Sauvegarder
    $em->flush();

    // Étape 6 - Retourner une confirmation
    return $this->json(['status' => 'Succès', 'message' => 'Allergène mis à jour avec succès']);
  }

  /**
   * @description Supprimer un allergène par son id
   *  méthode manquante ajoutée (CDC : employé peut créer, modifier ET supprimer les allergènes)
   */
  #[Route('/allergenes/{id}', name: 'api_employe_allergenes_delete', methods: ['DELETE'])]
  #[OA\Delete(summary: 'Supprimer un allergène')]  
  #[OA\Tag(name: 'Employé - Référentiels')]
  #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
  #[OA\Response(response: 200, description: 'Supprimé')]  
  #[OA\Response(response: 403, description: 'Accès refusé')]  
  #[OA\Response(response: 404, description: 'Non trouvé')]
  public function deleteAllergene(int $id, AllergeneRepository $allergeneRepository, EntityManagerInterface $em): JsonResponse
  {
    // Étape 1 - Vérifier le rôle EMPLOYE
    if (!$this->isGranted('ROLE_EMPLOYE')) {
      return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
    }

    // Étape 2 - Chercher l'allergène à supprimer
    $allergene = $allergeneRepository->find($id);
    if (!$allergene) {
      return $this->json(['status' => 'Erreur', 'message' => 'Allergène non trouvé'], 404);
    }

    // Étape 3 - Supprimer
    $em->remove($allergene);
    $em->flush();

    // Étape 4 - Retourner une confirmation
    return $this->json(['status' => 'Succès', 'message' => 'Allergène supprimé avec succès']);
  }

  // =========================================================================
  // PLATS
  // =========================================================================

  /**
   * @description Créer un nouveau plat
   * Corps JSON attendu : { "titre_plat": "Bœuf bourguignon", "photo": "boeuf.jpg", "categorie": "Plat", "allergenes": [1, 2] }
   */

  #[Route('/plats', name: 'api_employe_plats_create', methods: ['POST'])]
  #[OA\Post(summary: 'Créer un plat')]  #[OA\Tag(name: 'Employé - Plats')]
  #[OA\RequestBody(required: true, content: new OA\JsonContent(properties: [
    new OA\Property(property: 'titre_plat', type: 'string', example: 'Bœuf bourguignon'),
    new OA\Property(property: 'photo', type: 'string', example: 'boeuf.jpg'),
    new OA\Property(property: 'categorie', type: 'string', example: 'Plat'),
    new OA\Property(property: 'allergenes', type: 'array', items: new OA\Items(type: 'integer'), example: '[1, 2]'),
  ]))]
  #[OA\Response(response: 201, description: 'Créé')]  
  #[OA\Response(response: 400, description: 'Champs manquants ou catégorie invalide')]  
  #[OA\Response(response: 403, description: 'Accès refusé')]  
  #[OA\Response(response: 404, description: 'Allergène non trouvé')]  
  #[OA\Response(response: 409, description: 'Titre déjà utilisé')]
  public function createPlat(Request $request, PlatRepository $platRepository, AllergeneRepository $allergeneRepository, EntityManagerInterface $em): JsonResponse
  {
    // Étape 1 - Vérifier le rôle EMPLOYE
    if (!$this->isGranted('ROLE_EMPLOYE')) {
      return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
    }

    // Étape 2 - Récupérer les données JSON
    $data = json_decode($request->getContent(), true);

    // Étape 3 - Vérifier les champs obligatoires
    if (empty($data['titre_plat']) || empty($data['photo']) || empty($data['categorie'])) {
      return $this->json(['status' => 'Erreur', 'message' => 'Les champs titre_plat, photo et categorie sont obligatoires'], 400);
    }

    // Étape 4 - Vérifier que le titre n'existe pas déjà
    $existant = $platRepository->findOneBy(['titre_plat' => $data['titre_plat']]);
    if ($existant) {
      return $this->json(['status' => 'Erreur', 'message' => 'Un plat avec ce titre existe déjà'], 409);
    }

    // Étape 5 - Vérifier que la catégorie est valide
    $categoriesValides = ['Entrée', 'Plat', 'Dessert'];
    if (!in_array($data['categorie'], $categoriesValides)) {
      return $this->json(['status' => 'Erreur', 'message' => 'Catégorie invalide (Entrée, Plat, Dessert)'], 400);
    }

    // Étape 6 - Créer le plat
    $plat = new Plat();
    $plat->setTitrePlat($data['titre_plat']);
    $plat->setPhoto($data['photo']);
    $plat->setCategorie($data['categorie']);
    if (isset($data['description_plat'])) {
      $plat->setDescriptionPlat($data['description_plat']);
    }

    // Étape 7 - Associer les allergènes si fournis
    if (!empty($data['allergenes']) && is_array($data['allergenes'])) {
      foreach ($data['allergenes'] as $allergeneId) {
        $allergene = $allergeneRepository->find($allergeneId);
        if (!$allergene) {
          return $this->json(['status' => 'Erreur', 'message' => "Allergène id $allergeneId non trouvé"], 404);
        }
        $plat->addAllergene($allergene);
      }
    }

    // Étape 8 - Persister et sauvegarder
    $em->persist($plat);
    $em->flush();

    // Étape 9 - Retourner une confirmation
    return $this->json(['status' => 'Succès', 'message' => 'Plat créé avec succès', 'id' => $plat->getId()], 201);
  }

  /**
   * @description Met à jour un plat par son id
   * Corps JSON attendu : { "titre_plat": "...", "photo": "...", "categorie": "...", "allergenes": [1, 2] }
   */
  #[Route('/plats/{id}', name: 'api_employe_plats_update', methods: ['PUT'])]
  #[OA\Put(summary: 'Modifier un plat')]  #[OA\Tag(name: 'Employé - Plats')]
  #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
  #[OA\RequestBody(required: true, content: new OA\JsonContent(properties: [
    new OA\Property(property: 'titre_plat', type: 'string'), new OA\Property(property: 'photo', type: 'string'),
    new OA\Property(property: 'categorie', type: 'string'), new OA\Property(property: 'allergenes', type: 'array', items: new OA\Items(type: 'integer')),
  ]))]
  #[OA\Response(response: 200, description: 'Mis à jour')]  
  #[OA\Response(response: 400, description: 'Catégorie invalide')]  
  #[OA\Response(response: 403, description: 'Accès refusé')]  
  #[OA\Response(response: 404, description: 'Non trouvé')]  
  #[OA\Response(response: 409, description: 'Titre déjà utilisé')]
  public function updatePlat(
    int $id, 
    Request $request, 
    PlatRepository $platRepository, 
    AllergeneRepository $allergeneRepository, 
    EntityManagerInterface $em,
    CloudinaryService $cloudinaryService
  ): JsonResponse
  {
    // Étape 1 - Vérifier le rôle EMPLOYE
    if (!$this->isGranted('ROLE_EMPLOYE')) {
      return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
    }

    // Étape 2 - Récupérer les données JSON
    $data = json_decode($request->getContent(), true);

    // Étape 3 - Chercher le plat à modifier
    $plat = $platRepository->find($id);
    if (!$plat) {
      return $this->json(['status' => 'Erreur', 'message' => 'Plat non trouvé'], 404);
    }

    // Étape 3 - Récupérer les données JSON
    $data = json_decode($request->getContent(), true);

    // Étape 4 - Mettre à jour le titre si fourni
    if (isset($data['titre_plat'])) {
      $existant = $platRepository->findOneBy(['titre_plat' => $data['titre_plat']]);
      if ($existant && $existant->getId() !== $plat->getId()) {
        return $this->json(['status' => 'Erreur', 'message' => 'Un plat avec ce titre existe déjà'], 409);
      }
      $plat->setTitrePlat($data['titre_plat']);
    }

    // Étape 5 - Mise à jour de la description si fournie
    if (isset($data['description_plat'])) {
      $plat->setDescriptionPlat($data['description_plat']);
    }


    // Étape 6 - Mettre à jour la photo si fournie
    if (isset($data['photo'])) {
      // Si l'ancienne photo est différente et existe sur Cloudinary → la supprimer
      $anciennePhoto = $plat->getPhoto();
      if ($anciennePhoto && $anciennePhoto !== $data['photo']) {
        $cloudinaryService->deleteByUrl($anciennePhoto);
      }
      $plat->setPhoto($data['photo']);
    }

    // Étape 7 - Mettre à jour la catégorie si fournie
    if (isset($data['categorie'])) {
      $categoriesValides = ['Entrée', 'Plat', 'Dessert'];
      if (!in_array($data['categorie'], $categoriesValides)) {
        return $this->json(['status' => 'Erreur', 'message' => 'Catégorie invalide (Entrée, Plat, Dessert)'], 400);
      }
      $plat->setCategorie($data['categorie']);
    }

    // Étape 8 - Synchroniser les allergènes si fournis (remplacement complet)
    if (isset($data['allergenes']) && is_array($data['allergenes'])) {
      foreach ($plat->getAllergenes() as $allergeneExistant) {
        $plat->removeAllergene($allergeneExistant);
      }
      foreach ($data['allergenes'] as $allergeneId) {
        $allergene = $allergeneRepository->find($allergeneId);
        if (!$allergene) {
          return $this->json(['status' => 'Erreur', 'message' => "Allergène id $allergeneId non trouvé"], 404);
        }
        $plat->addAllergene($allergene);
      }
    }

    // Étape 9 - Sauvegarder
    $em->flush();

    // Étape 10 - Retourner une confirmation
    return $this->json(['status' => 'Succès', 'message' => 'Plat mis à jour avec succès']);
  }

  /**
   * @description Supprimer un plat par son id
   * @param int $id L'id du plat à supprimer
   * @param PlatRepository $platRepository Le repository des plats
   * @param EntityManagerInterface $em L'EntityManager pour gérer les opérations de base de données
   * @return JsonResponse
   */
  #[Route('/plats/{id}', name: 'api_employe_plats_delete', methods: ['DELETE'])]
  #[OA\Delete(summary: 'Supprimer un plat')]  #[OA\Tag(name: 'Employé - Plats')]
  #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
  #[OA\Response(response: 200, description: 'Supprimé')]  
  #[OA\Response(response: 403, description: 'Accès refusé')]  
  #[OA\Response(response: 404, description: 'Non trouvé')]
  public function deletePlat(
    int $id, 
    PlatRepository $platRepository,
    EntityManagerInterface $em,
    CloudinaryService $cloudinaryService 
  ): JsonResponse
  {
    // Étape 1 - Vérifier le rôle EMPLOYE
    if (!$this->isGranted('ROLE_EMPLOYE')) {
      return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
    }

    // Étape 2 - Chercher le plat à supprimer
    $plat = $platRepository->find($id);
    if (!$plat) {
      return $this->json(['status' => 'Erreur', 'message' => 'Plat non trouvé'], 404);
    }

    // Étape 3 - Supprime l'image sur Cloudinary si elle existe
    if ($plat->getPhoto()) {
      $cloudinaryService->deleteByUrl($plat->getPhoto());
    }

    // Étape 4 - Supprime le plat
    $em->remove($plat);

    // Étape 5 - Sauvegarder       
    $em->flush();

    // Étape 6 - Retourne le résulat
    return $this->json(['status' => 'Succès', 'message' => 'Plat supprimé avec succès']);
  }

	/**
	 * @description Ajoute un plat à un menu
	 * Accessible uniquement pour la gestion des menus (employe)
	 * Un menu ne peut contenir que 3 plats maximum (Entrée, Plat, Dessert)
	 * @param int $menuId ID du menu
	 * @param int $platId ID du plat à ajouter
	 * @param MenuRepository $menuRepository Le repository des menus
	 * @param PlatRepository $platRepository Le repository des plats
	 * @return JsonResponse confirmation de l'ajout ou message d'erreur
	 */
	#[Route('/menus/{menuId}/plats/{platId}', name: 'api_menu_add_plat', methods: ['POST'])]
	#[OA\Post(summary: 'Ajouter un plat à un menu', description: 'Ajoute un plat existant à un menu. Maximum 3 plats par menu.')]
	#[OA\Tag(name: 'Employé - Menus')]
	#[OA\Response(response: 200, description: 'Plat ajouté au menu')]
	#[OA\Response(response: 404, description: 'Menu ou plat non trouvé')]
	public function addPlatToMenu(
		int $menuId,
		int $platId,
		MenuRepository $menuRepository,
		PlatRepository $platRepository
	): JsonResponse
	{
		// Étape 1 - Vérifie le rôle ROLE_EMPLOYE
		if (!$this->isGranted('ROLE_EMPLOYE')) {
			return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
		}

    // Étape 2 - Récupérer le menu
    $menu = $menuRepository->find($menuId);
    if (!$menu) {
      return $this->json(['status' => 'Erreur', 'message' => 'Menu non trouvé'], 404);
    }

    // Étape 3 - Récupérer le plat
    $plat = $platRepository->find($platId);
    if (!$plat) {
      return $this->json(['status' => 'Erreur', 'message' => 'Plat non trouvé'], 404);
    }

    // Étape 4 - Vérifier que le menu n'a pas déjà 3 plats
    if ($menu->getPlats()->count() >= 3) {
      return $this->json([
          'status' => 'Erreur',
          'message' => 'Ce menu contient déjà 3 plats maximum'
      ], 400);
    }

    // Étape 5 - Ajouter le plat au menu
    $menu->addPlat($plat);

    // Étape 6 - Sauvegarder
    $menuRepository->getEntityManager()->flush();

    // Étape 7 - Retourner confirmation
    return $this->json([
      'status' => 'Succès',
      'message' => 'Plat ajouté au menu'
    ]);
	}

	/**
	 * @description Supprime un plat d'un menu
	 * Accessible pour la gestion des menus
	 * @param int $menuId ID du menu
	 * @param int $platId ID du plat à supprimer
	 * @param MenuRepository $menuRepository Le repository des menus
	 * @param PlatRepository $platRepository Le repository des plats
	 * @return JsonResponse confirmation de la suppression
	 */
  #[Route('/menus/{menuId}/plats/{platId}', name: 'api_menu_remove_plat', methods: ['DELETE'])]
  #[OA\Delete(summary: 'Supprimer un plat d\'un menu', description: 'Retire un plat d\'un menu existant')]
  #[OA\Tag(name: 'Employé - Menus')]
  #[OA\Response(response: 200, description: 'Plat supprimé du menu')]
  #[OA\Response(response: 404, description: 'Menu ou plat non trouvé')]
  public function removePlatFromMenu(
      int $menuId,
      int $platId,
      MenuRepository $menuRepository,
      PlatRepository $platRepository
  ): JsonResponse
  {
    // Étape 1 - Vérifie le rôle ROLE_EMPLOYE
    if (!$this->isGranted('ROLE_EMPLOYE')) {
      return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
    }

    // Étape 2 - Récupérer le menu
    $menu = $menuRepository->find($menuId);
    if (!$menu) {
      return $this->json(['status' => 'Erreur', 'message' => 'Menu non trouvé'], 404);
    }

    // Étape 3 - Récupérer le plat
    $plat = $platRepository->find($platId);
    if (!$plat) {
      return $this->json(['status' => 'Erreur', 'message' => 'Plat non trouvé'], 404);
    }

    // Étape 4 - Vérifier que le plat appartient bien au menu
    if (!$menu->getPlats()->contains($plat)) {
      return $this->json(['status' => 'Erreur', 'message' => 'Ce plat n\'est pas dans ce menu'], 404);
    }

    // Étape 5 - Supprimer le plat du menu
    $menu->removePlat($plat);

    // Étape 6 - Sauvegarder
    $menuRepository->getEntityManager()->flush();

    // Étape 7 - Retourner confirmation
    return $this->json([
      'status' => 'Succès',
      'message' => 'Plat supprimé du menu'
    ]);
  }

// =========================================================================
  // MÉTHODE UTILITAIRE - Formatage des commandes
  // =========================================================================

  /**
   * @description Formate une commande en tableau pour éviter la référence circulaire
   * @param Commande $c L'entité Commande à formater
   * @return array Les données formatées
   */
  private function formatCommande(\App\Entity\Commande $c): array
  {
      return [
          'id' => $c->getId(),
          'numero_commande' => $c->getNumeroCommande(),
          'date_commande' => $c->getDateCommande()?->format('d/m/Y H:i'),
          'date_prestation' => $c->getDatePrestation()?->format('d/m/Y'),
          'heure_livraison' => $c->getHeureLivraison()?->format('H:i'),
          'statut' => $c->getStatut(),
          'prix_menu' => $c->getPrixMenu(),
          'nombre_personne' => $c->getNombrePersonne(),
          'prix_livraison' => $c->getPrixLivraison(),
          'montant_acompte' => $c->getMontantAcompte(),
          'montant_rembourse' => $c->getMontantRembourse(),
          'motif_annulation' => $c->getMotifAnnulation(),
          'adresse_livraison' => $c->getAdresseLivraison(),
          'ville_livraison' => $c->getVilleLivraison(),
          'distance_km' => $c->getDistanceKm(),
          'pret_materiel' => $c->isPretMateriel(),
          'restitution_materiel' => $c->isRestitutionMateriel(),
          'mail_penalite_envoye' => $c->isMailPenaliteEnvoye(),
          'etat_materiel' => $c->getEtatMateriel(),
          'utilisateur_id' => $c->getUtilisateur()?->getId(),
          'utilisateur_nom' => $c->getUtilisateur()?->getNom(),
          'utilisateur_prenom' => $c->getUtilisateur()?->getPrenom(),
          'utilisateur_email' => $c->getUtilisateur()?->getEmail(),
          'menu_id' => $c->getMenu()?->getId(),
          'menu_titre' => $c->getMenu()?->getTitre(),
      ];
  }

}
