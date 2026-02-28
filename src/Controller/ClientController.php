<?php
namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use App\Repository\CommandeRepository;
use App\Repository\SuiviCommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\MailerService;
use App\Service\LogService; // import du LogService MongoDB
use App\Entity\Avis;
use App\Repository\AvisRepository;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @author      Florian Aizac
 * @created     24/02/2026
 * @description Contrôleur gérant les actions du client connecté
 * 
 *  1. getProfil             : Retourne les informations du profil client connecté
 *  2. updateUserById        : Met à jour les informations d'un client par son id
 *  3. demandeDesactivation  : Demande de désactivation du compte client et envois d'un mail a l'admin
 *  4. getCommandes          : Retourne la liste de ses commandes
 *  5. modifierCommande      : Modifier une commande en statut "En attente"
 *  6. annulerCommande       : Annule une commandes passée par le client en fournissant son ID
 *  7. getSuiviCommande      : Afficher le suivis de commande du client
 *  8. getAvis               : Afficher la liste des avis d'un client connecté
 *  9. createAvis            : Permettre a un client de poster un avis lorsque sa commande est en statut "terminée"
 */

#[Route('/api/client')]

final class ClientController extends BaseController
{

    // =========================================================================
    // UTILISATEUR
    // =========================================================================

    /**
     * @description Retourne les informations du profil client connecté
     * @param '' auncun parametre requis
     * @return JsonResponse une réponse JSON avec les données de son profil
     */
    #[Route('/profil', name: 'api_client_profil', methods: ['GET'])]
    // Récupère les données du profil du client connecté
    public function getProfil(): JsonResponse
    {
        
        // Étape 1 - Vérifie que l'utilisateur a le rôle CLIENT
        if (!$this->isGranted('ROLE_CLIENT')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupère l'utilisateur connecté via le token JWT
        $utilisateur = $this->getUser();

        // Étape 3 - Vérifie que l'utilisateur qui est connecté et est bien une instance de l'entité Utilisateur
        if (!$utilisateur instanceof Utilisateur) {
            return $this->json(['status' => 'Erreur', 'message' => 'Utilisateur non connecté'], 401);
        }

        // Étape 4 - Retourne ses données en JSON
        return $this->json($utilisateur);
    }

    #[Route('/profil', name: 'api_client_update_profil', methods: ['PUT'])]
    /**
     * @description Met à jour les informations d'un client par son id
     * @param Request $request la requête HTTP contenant les données à mettre à jour au format JSON
     * @param EntityManagerInterface $em l'EntityManager pour gérer les opérations de base de données
     * @param UserPasswordHasherInterface $passwordHasher le service pour hasher les mots de passe de l'utilisateur
     * @param UtilisateurRepository $utilisateurRepository le repository pour accéder aux données de l'utilisateurs
     * @return JsonResponse une réponse JSON indiquant le succès ou l'échec de l'opération de mise à jour
     */
    public function updateUserById(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        UtilisateurRepository $utilisateurRepository
    ): JsonResponse
    {
        // Étape 1 - Vérifier le rôle CLIENT
        if (!$this->isGranted('ROLE_CLIENT')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer l'utilisateur connecté
        $utilisateur = $this->getUser();

        // Étape 3 - Vérifie que l'utilisateur qui est connecté est bien une instance de l'entité Utilisateur
        if (!$utilisateur instanceof Utilisateur) {
            return $this->json(['status' => 'Erreur', 'message' => 'Utilisateur non connecté'], 401);
        }

        // Étape 4 - Récupérer les données JSON
        $data = json_decode($request->getContent(), true);

        // Étape 5 - Mise à jour de l'email et vérification doublon email
        if (isset($data['email'])) {
            $emailExistant = $utilisateurRepository->findOneBy(['email' => $data['email']]);
            if ($emailExistant && $emailExistant->getId() !== $utilisateur->getId()) {
                return $this->json(['status' => 'Erreur', 'message' => 'Cet email est déjà utilisé'], 409);
            }
            $utilisateur->setEmail($data['email']);
        }
        
        // Étape 6 - Modification du mot de passe
        if (isset($data['password'])) {
            $motDePasseHashe = $passwordHasher->hashPassword($utilisateur, $data['password']);
            $utilisateur->setPassword($motDePasseHashe);
        }

        // Étape 7 - Mise à jour du nom
        if (isset($data['nom'])) {
            $utilisateur->setNom($data['nom']);
        }

        // Étape 8 - Mise à jour du prénom
        if (isset($data['prenom'])) {
            $utilisateur->setPrenom($data['prenom']);
        }

        // Étape 9 - Vérification doublon téléphone
        if (isset($data['telephone'])) {
            $telephoneExistant = $utilisateurRepository->findOneBy(['telephone' => $data['telephone']]);
            if ($telephoneExistant && $telephoneExistant->getId() !== $utilisateur->getId()) {
                return $this->json(['status' => 'Erreur', 'message' => 'Ce téléphone est déjà utilisé'], 409);
            }
            $utilisateur->setTelephone($data['telephone']);
        }

        // Étape 9 - Mise à jour de la ville
        if (isset($data['ville'])) {
            $utilisateur->setVille($data['ville']);
        }

        // Étape 10 - Mise à jour du code postal
        if (isset($data['code_postal'])) {
            $utilisateur->setCodePostal($data['code_postal']);
        }

        // Étape 11 - Mise à jour de l'adresse postale
        if (isset($data['adresse_postale'])) {
            $utilisateur->setAdressePostale($data['adresse_postale']);
        }

        // Étape 12 - Mise à jour du pays
        if (isset($data['pays'])) {
            $utilisateur->setPays($data['pays']);
        }

        // Étape 13 - Sauvegarder en base
        $em->flush();

        // Étape 14 - Retourner un message de confirmation
        return $this->json(['status' => 'Succès', 'message' => 'Profil mis à jour avec succès']);
    }

    /**
     * @description Demande de désactivation du compte client et envois d'un mail a l'admin
     * L'utilisateur doit être authentifié et avoir le rôle CLIENT pour accéder à cette route. 
     * Ensuite génére un mail à l'admin pour l'informer que le client souhaite désactiver son compte
     * @param int $id l'id de la commande sur laquelle le client veut laisser un avis
     * @param EntityManagerInterface $em l'EntityManager pour gérer les opérations de base de données
     * @param MailerService $mailerService l'MailerService pour gérer les échange de mail
     * @return JsonResponse une réponse JSON indiquant le succès ou l'échec de l'opération.
     */

    /**
     *  A MODIFIER fonction qui fonctionne mais il faudrait générer un mail à l'admin pour l'informer que le client souhaite désactiver son compte
     */
    #[Route('/compte/desactivation', name: 'api_client_compte_desactivation', methods: ['POST'])]
    public function demandeDesactivation(EntityManagerInterface $em, MailerService $mailerService): JsonResponse
    {
        // Étape 1 - Vérifier le rôle CLIENT
        if (!$this->isGranted('ROLE_CLIENT')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer l'utilisateur connecté
        $utilisateur = $this->getUser();

        // Étape 3 - Vérifie que l'utilisateur est connecté et est bien une instance de l'entité Utilisateur
        if (!$utilisateur instanceof Utilisateur) {
            return $this->json(['status' => 'Erreur', 'message' => 'Utilisateur non connecté'], 401);
        }

        // Étape 4 - Vérifier que le compte n'est pas déjà en attente de désactivation
        if ($utilisateur->getStatutCompte() === 'en_attente_desactivation') {
            return $this->json(['status' => 'Erreur', 'message' => 'Demande de désactivation déjà en cours'], 400);
        }

        // Étape 5 - Vérifier que le compte n'est pas déjà inactif
        if ($utilisateur->getStatutCompte() === 'inactif') {
            return $this->json(['status' => 'Erreur', 'message' => 'Compte déjà désactivé'], 400);
        }

        // Étape 6 - modification du statut du compte
        $utilisateur->setStatutCompte('en_attente_desactivation');

        // Étape 7 - Sauvegarder en base de donnée
        $em->flush();

        // Étape 8 - Envoyer un email à l'admin
        $mailerService->sendDemandeDesactivationEmail($utilisateur);

        // Étape 9 - Retourner un message de confirmation
        return $this->json([
            'status'  => 'Succès',
            'message' => 'Votre demande de désactivation a été prise en compte. Un administrateur la traitera prochainement.'
        ]);
    }

    // =========================================================================
    // COMMANDE
    // =========================================================================

    /**
     * @description Retourne la liste de ses commandes
     * L'utilisateur doit être authentifié et avoir le rôle CLIENT pour accéder à cette route. 
     * @param CommandeRepository $commandeRepository Le repository des commandes
     * @return JsonResponse reponse JSON
     */
    #[Route('/commandes', name: 'api_client_commandes', methods: ['GET'])]
    public function getCommandes(CommandeRepository $commandeRepository): JsonResponse
    {
        // Étape 1 - Vérifie le rôle CLIENT
        if (!$this->isGranted('ROLE_CLIENT')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupére l'utilisateur connecté
        $utilisateur = $this->getUser();

        // Étape 3 - Vérifie que l'utilisateur est connecté et est bien une instance de l'entité Utilisateur
        if (!$utilisateur instanceof Utilisateur) {
            return $this->json(['status' => 'Erreur', 'message' => 'Utilisateur non connecté'], 401);
        }

        // Étape 4 - Récupére ses commandes via le repository
        $commandes = $commandeRepository->findByUtilisateur($utilisateur);
        
        // Étape 5 - Retourne les commandes en JSON
        return $this->json(['status' => 'Succès', 'commandes' => $commandes]);
    }
    /**
     * @description Modifier une commande existante
     * Modification possible uniquement si la commande est en statut "En attente"
     * Si nombre_personnes ou ville_livraison change -> recalcul automatique des prix
     * Champs modifiables : date_prestation, nombre_personnes, adresse_livraison, ville_livraison, distance_km
     * Corps JSON attendu (tous optionnels) :
     * {
     *   "date_prestation": "2026-05-01",
     *   "nombre_personnes": 20,
     *   "adresse_livraison": "15 rue de la paix",
     *   "ville_livraison": "Mérignac",
     *   "distance_km": 8
     * }
     * @param int $id L'id de la commande à modifier
     * @param Request $request La requête HTTP contenant les données au format JSON
     * @param CommandeRepository $commandeRepository Le repository des commandes
     * @param EntityManagerInterface $em L'EntityManager
     * @return JsonResponse
     */
    #[Route('/commandes/{id}', name: 'api_client_commande_modifier', methods: ['PUT'])]
    public function modifierCommande(
        int $id,
        Request $request,
        CommandeRepository $commandeRepository,
        EntityManagerInterface $em
    ): JsonResponse {

        // Étape 1 - Vérifier le rôle CLIENT
        if (!$this->isGranted('ROLE_CLIENT')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer l'utilisateur connecté
        $utilisateur = $this->getUser();
        if (!$utilisateur instanceof Utilisateur) {
            return $this->json(['status' => 'Erreur', 'message' => 'Utilisateur non connecté'], 401);
        }

        // Étape 3 - Récupérer la commande
        $commande = $commandeRepository->find($id);
        if (!$commande) {
            return $this->json(['status' => 'Erreur', 'message' => 'Commande non trouvée'], 404);
        }

        // Étape 4 - Vérifier que la commande appartient au client connecté
        if ($commande->getUtilisateur()->getId() !== $utilisateur->getId()) {
            return $this->json(['status' => 'Erreur', 'message' => 'Commande non autorisée'], 403);
        }

        // Étape 5 - Vérifier que la commande est bien en statut "En attente"
        // La modification n'est possible que si la commande n'a pas encore été acceptée
        if ($commande->getStatut() !== 'En attente') {
            return $this->json(['status' => 'Erreur', 'message' => 'Modification impossible : la commande n\'est plus en attente'], 400);
        }

        // Étape 6 - Récupérer les données JSON
        $data = json_decode($request->getContent(), true);

        // Étape 7 - Mettre à jour la date de prestation si fournie
        if (isset($data['date_prestation'])) {
            try {
                $datePrestation = new \DateTime($data['date_prestation']);
                $commande->setDatePrestation($datePrestation);
            } catch (\Exception $e) {
                return $this->json(['status' => 'Erreur', 'message' => 'Date de prestation invalide'], 400);
            }
        }

        // Étape 8 - Mettre à jour l'adresse de livraison si fournie
        if (isset($data['adresse_livraison'])) {
            $commande->setAdresseLivraison($data['adresse_livraison']);
        }

        // Étape 9 - Recalculer les prix si nombre_personnes ou ville_livraison change
        // On récupère les valeurs actuelles ou les nouvelles selon ce qui est fourni
        $nombrePersonnes = isset($data['nombre_personnes']) ? (int) $data['nombre_personnes'] : $commande->getNombrePersonne();
        $villeLivraison  = isset($data['ville_livraison'])  ? strtolower(trim($data['ville_livraison'])) : strtolower(trim($commande->getVilleLivraison()));
        $distanceKm      = isset($data['distance_km'])      ? (float) $data['distance_km'] : 0;

        // Étape 9.1 : Flag pour savoir si un recalcul est nécessaire
        $recalcul = isset($data['nombre_personnes']) || isset($data['ville_livraison']);

        if ($recalcul) {
            // Étape 9.2 : Récupérer le menu associé à la commande pour les calculs
            $menu = $commande->getMenu();

            // Étape 9.3 : Recalcul du prix menu avec éventuelle réduction -10%
            $prixMenu = $menu->getPrixParPersonne() * $nombrePersonnes;
            if ($nombrePersonnes > ($menu->getNombrePersonneMinimum() + 5)) {
                $prixMenu = $prixMenu * 0.90;
            }

            // Étape 9.3 : Recalcul du prix de livraison
            // Gratuit à Bordeaux, sinon 5€ + 0,59€/km
            if ($villeLivraison === 'bordeaux') {
                $prixLivraison = 0;
            } else {
                $prixLivraison = 5 + (0.59 * $distanceKm);
            }

            // Étape 9.4 : Recalcul de l'acompte
            // 50% si thème Événement, 30% sinon
            $libelleTheme = strtolower($menu->getTheme()->getLibelle());
            $tauxAcompte = ($libelleTheme === 'événement') ? 0.50 : 0.30;
            $montantAcompte = ($prixMenu + $prixLivraison) * $tauxAcompte;

            // Étape 9.5 : Mise à jour des champs recalculés
            $commande->setNombrePersonne($nombrePersonnes);
            $commande->setVilleLivraison($data['ville_livraison'] ?? $commande->getVilleLivraison());
            $commande->setPrixMenu(round($prixMenu, 2));
            $commande->setPrixLivraison(round($prixLivraison, 2));
            $commande->setMontantAcompte(round($montantAcompte, 2));
        }

        // Étape 10 - Sauvegarder en base
        $em->flush();

        // Étape 11 - Retourner une confirmation avec les nouveaux prix si recalcul
        $reponse = [
            'status'  => 'Succès',
            'message' => 'Commande modifiée avec succès',
        ];

        // Si recalcul effectué → afficher les nouveaux prix dans la réponse
        if ($recalcul) {
            $reponse['prix_menu']           = $commande->getPrixMenu();
            $reponse['prix_livraison']      = $commande->getPrixLivraison();
            $reponse['montant_acompte']     = $commande->getMontantAcompte();
            $reponse['reduction_appliquee'] = $nombrePersonnes > ($commande->getMenu()->getNombrePersonneMinimum() + 5) ? '-10%' : 'aucune';
        }

        return $this->json($reponse);
    }

    /**
     * @description Annule une commandes passée par le client en fournissant son ID
     * L'utilisateur doit être authentifié et avoir le rôle CLIENT pour accéder à cette route.      
     * @param int id correspond à commande_id id de la commande à annuler
     * @param CommandeRepository $commandeRepository Le repository des commandes
     * @param EntityManagerInterface $em pour gérer les opérations de base de données
     * @param MailerService $mailerService pour envoyer un email de confirmation d'annulation
     * @param LogService $logService pour enregistrer le log d'annulation dans MongoDB
     * @return JsonResponse reponse JSON
     */
    #[Route('/commandes/{id}/annuler', name: 'api_client_commande_annuler', methods: ['POST'])]
    public function annulerCommande(
        int $id,
        Request $request,
        CommandeRepository $commandeRepository,
        EntityManagerInterface $em,
        MailerService $mailerService,
        LogService $logService              // AJOUT : injection du LogService MongoDB
    ): JsonResponse
    {
        // Étape 1 - Vérifie le rôle CLIENT
        if (!$this->isGranted('ROLE_CLIENT')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer l'utilisateur connecté
        $utilisateur = $this->getUser();
        // Vérifie que l'utilisateur est connecté et est bien une instance de l'entité Utilisateur
        if (!$utilisateur instanceof Utilisateur) {
            return $this->json(['status' => 'Erreur', 'message' => 'Utilisateur non connecté'], 401);
        }

        // Étape 3 - Chercher la commande par son id
        $commande = $commandeRepository->find($id);

        // Étape 4 - Si non trouvée retourner 404
        if (!$commande) {
            return $this->json(['status' => 'Erreur', 'message' => 'Commande non trouvée'], 404);
        }

        // Étape 5 - Vérifier que la commande appartient au client connecté
        if ($commande->getUtilisateur()->getId() !== $utilisateur->getId()) {
            return $this->json(['status' => 'Erreur', 'message' => 'Commande non autorisée'], 403);
        }
        
        // Étape 6 - Vérifier que la commande n'est pas déjà annulée
        if ($commande->getStatut() === 'annulée') {
            return $this->json(['status' => 'Erreur', 'message' => 'Commande déjà annulée'], 400);
        }

        // Étape 7 - Vérifier que la commande est bien en statut "En attente"
        if ($commande->getStatut() !== 'En attente') {
            return $this->json(['status' => 'Erreur', 'message' => 'Annulation impossible, la commande n\'est plus en attente'], 400);
        }

        // Étape 8 - Récupérer la justification depuis le JSON
        $data = json_decode($request->getContent(), true);
        $motifAnnulation = $data['motif_annulation'] ?? null;

        // Étape 9 - Calculer le nombre de jours avant la prestation
        // Étape 9.1 : Récupérer la date de prestation de la commande
        $datePrestation = $commande->getDatePrestation();

        // Étape 9.2 : Récupérer la date actuelle
        $aujourdhui = new \DateTime();

        // Étape9.3 : Calculer la différence en jours entre les deux dates
        $diff = $aujourdhui->diff($datePrestation)->days;

        /*
        Logique de remboursement :
        - Si la prestation est dans plus de 7 jours, le client est remboursé à 100%
        - Si la prestation est dans 3 à 7 jours, le client est remboursé à 50%
        - Si la prestation est dans moins de 3 jours, le client n'est pas remboursé
        */   

        // Étape 10 Mise en place et calcul du montant remboursé selon les règles sitée ci-dessus
        $montantRembourse = 0;

        // Étape 10.1 spécifier la cas 50 100 ou 0 pour le message de confirmation
        $pourcentageRembourse = 0;

        // Étape 10.2 Calcul du montant total de la commande (prix du menu + prix de la livraison)
        $montantTotal = $commande->getPrixMenu() + $commande->getPrixLivraison();

        // Étape 10.3 si la prestation est dans plus de 7 jours, le client est remboursé à 100%
        if ($diff > 7) {
            $montantRembourse = $montantTotal;
            $pourcentageRembourse = 100;

        // Étape 10.4 si la prestation est dans 3 à 7 jours, le client est remboursé à 50%
        } elseif ($diff >= 3 && $diff <= 7) {
            $montantRembourse = $montantTotal / 2;
            $pourcentageRembourse = 50;
        }
        // Étape 10.5 sinon si la prestation est dans moins de 3 jours, le client n'est pas remboursé
        else {
            $montantRembourse = 0;
            $pourcentageRembourse = 0;
        }   

        // Étape 11 mise à jour de la commande
        switch ($pourcentageRembourse) {
            case 100:
                    $messageRemboursement = 'Vous avez été remboursé à 100%';
                    break;
            case 50:
                $messageRemboursement = 'Vous avez été remboursé à 50%';
                break;
            default:
                $messageRemboursement = 'Vous n\'avez pas été remboursé';
                $pourcentageRembourse = 0;
        }
        // Étape 11.1 Mise à jour du statut à annulée
        $commande->setStatut('annulée');
        // Étape 11.2 Mise à jour du motif d'annulation
        $commande->setMotifAnnulation($motifAnnulation);
        // Étape 11.3 Mise à jour du montant rembourser
        $commande->setMontantRembourse($montantRembourse);

        // Étape 12 - Sauvegarder en base de données
        $em->flush();

        // Étape 13 - Envoyer un email de confirmation
        $mailerService->sendAnnulationEmail($utilisateur, $commande, $pourcentageRembourse, $montantRembourse);

        // Étape 14 - Enregistrer le log d'annulation dans MongoDB
        // Après le flush() pour garantir que la commande est bien mise à jour en MySQL avant de logger
        $logService->log(
            'commande_annulee',             // type de l'action
            $utilisateur->getEmail(),        // email du client qui annule
            'ROLE_CLIENT',                   // c'est le client qui annule
            [                               // contexte libre : infos clés pour l'audit
                'numero_commande'      => $commande->getNumeroCommande(),
                'motif'                => $motifAnnulation ?? 'non renseigné',
                'montant_rembourse'    => $montantRembourse,
                'pourcentage_rembourse'=> $pourcentageRembourse,
            ]
        );

        // Étape 15 - Retourner un message de confirmation avec le montant remboursé
        return $this->json([
            'status' => 'Succès',
            'message' => $messageRemboursement,
            'montant_rembourse' => $montantRembourse
        ]);
    }

    // =========================================================================
    // SUIVIS
    // =========================================================================

    /**
     * @description Afficher le suivis de commande du client
     * L'utilisateur doit être authentifié et avoir le rôle CLIENT pour accéder à cette route. 
     * @param int id  correspond à commande_id id de la commande à annuler
     * @param CommandeRepository $commandeRepository Le repository des commandes
     * @param SuiviCommandeRepository $suiviCommandeRepository les methodes de suivis de commandes
     * @return JsonResponse reponse JSON
     */
    #[Route('/commandes/{id}/suivi', name: 'api_client_commande_suivi', methods: ['GET'])]
    public function getSuiviCommande(int $id, CommandeRepository $commandeRepository, SuiviCommandeRepository $suiviCommandeRepository): JsonResponse
    {
        // Étape 1 - Vérifie le rôle CLIENT
        if (!$this->isGranted('ROLE_CLIENT')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupére  l'utilisateur connecté
        $utilisateur = $this->getUser();
        // Étape 2.1 Vérifie que l'utilisateur est connecté et est bien une instance de l'entité Utilisateur
        if (!$utilisateur instanceof Utilisateur) {
            return $this->json(['status' => 'Erreur', 'message' => 'Utilisateur non connecté'], 401);
        }

        // Étape 3 - Cherche la commande par son id
        $commande = $commandeRepository->find($id);

        // Étape 4 - Si non trouvée retourner 404
        if (!$commande) {
            return $this->json(['status' => 'Erreur', 'message' => 'Suivis de commande non trouvée'], 404);
        }

        // Étape 5 - Vérifie que le suivis de la commande appartient au client connecté
        if ($commande->getUtilisateur()->getId() !== $utilisateur->getId()) {
            return $this->json(['status' => 'Erreur', 'message' => 'Commande non autorisée'], 403);
        }

        // Étape 6 - Récupére les suivis de la commande
        $suivis = $suiviCommandeRepository->findBy(
            ['commande' => $commande],
            ['date_statut' => 'ASC'] // trié du plus ancien au plus récent
        );

        $total_data=count($suivis); // retourne le nombre d'éléments

        // Étape 7 - Formatage des données en version 20/02/2026 02:00        
        $suivisFormates = [];
        foreach ($suivis as $suivi) {
            $suivisFormates[] = [
                'statut'      => $suivi->getStatut(),
                'date_statut' => $suivi->getDateStatut()->format('d/m/Y H:i'),
            ];
        }
        // Étape 8 - Retourne les suivis en JSON
        return $this->json([
            'status'  => 'Succès',
            'message' => 'Suivis retournée avec succès',
            'total'   => $total_data,
            'suivis'  => $suivisFormates
        ]);
    }

    // =========================================================================
    // AVIS
    // =========================================================================

    /**
     * @description Afficher la liste des avis d'un client connecté, triés du plus récent au plus ancien
     * @param AvisRepository $avisRepository Le repository des avis
     * @return JsonResponse
     */
    #[Route('/avis', name: 'api_client_avis_list', methods: ['GET'])]
    public function getAvis(AvisRepository $avisRepository): JsonResponse
    {
        // Étape 1 - Vérifier le rôle CLIENT
        if (!$this->isGranted('ROLE_CLIENT')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer l'utilisateur connecté
        $utilisateur = $this->getUser();
        // Étape 2.1 Vérifie que l'utilisateur est connecté et est bien une instance de l'entité Utilisateur
        if (!$utilisateur instanceof Utilisateur) {
            return $this->json(['status' => 'Erreur', 'message' => 'Utilisateur non connecté'], 401);
        }

        // Étape 3 - Récupérer ses avis triés du plus récent au plus ancien
        $avis = $avisRepository->findBy(
            ['utilisateur' => $utilisateur],
            ['id' => 'DESC']
        );

        // Étape 4 - Retourner les avis en JSON
        return $this->json([
            'status' => 'Succès',
            'total'  => count($avis),
            'avis'   => $avis
        ]);
    }

    /**
     * @description Permettre a un client de poster un avis lorsque sa commande est en statut "terminée"
     * L'utilisateur doit être authentifié et avoir le rôle CLIENT pour accéder à cette route. 
     * @param int $id l'id de la commande sur laquelle le client veut laisser un avis
     * @param Request $request la requête HTTP contenant note et description au format JSON
     * @param CommandeRepository $commandeRepository Le repository des commandes
     * @param AvisRepository $avisRepository Le repository des avis
     * @param EntityManagerInterface $em l'EntityManager pour gérer les opérations de base de données
     * @return JsonResponse une réponse JSON indiquant le succès ou l'échec de l'opération.
     */
    #[Route('/commandes/{id}/avis', name: 'api_client_avis', methods: ['POST'])]
    public function createAvis(int $id, Request $request, CommandeRepository $commandeRepository, AvisRepository $avisRepository, EntityManagerInterface $em): JsonResponse
    {
        // Étape 1 - Vérifie le rôle CLIENT
        if (!$this->isGranted('ROLE_CLIENT')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupére  l'utilisateur connecté
        $utilisateur = $this->getUser();

        // Étape 2.1 Vérifie que l'utilisateur est connecté et est bien une instance de l'entité Utilisateur
        if (!$utilisateur instanceof Utilisateur) {
            return $this->json(['status' => 'Erreur', 'message' => 'Utilisateur non connecté'], 401);
        }

        // Étape 3 - Récupére les données JSON
        $data = json_decode($request->getContent(), true);

        // Étape 4 - Vérifier les champs obligatoires
        if (empty($data['note']) || empty($data['description'])) {
            return $this->json(['status' => 'Erreur', 'message' => 'Note et description sont obligatoires'], 400);
        }

        // Étape 5 - Vérifier que la note est entre 0 et 5
        if ($data['note'] < 1 || $data['note'] > 5) {
            return $this->json(['status' => 'Erreur', 'message' => 'La note doit être entre 1 et 5'], 400);
        }
          
        // Étape 6 - Vérification de la taille de la description   
        if (strlen($data['description']) > 255) {
            return $this->json(['status' => 'Erreur', 'message' => 'La description est trop longue'], 400);
        }  

        // Étape 7 - Vérifie que la commande existe et appartient au client
        $commande = $commandeRepository->find($id);
        if (!$commande) {
            return $this->json(['status' => 'Erreur', 'message' => 'Commande non trouvée'], 404);
        }
        if ($commande->getUtilisateur()->getId() !== $utilisateur->getId()) {
            return $this->json(['status' => 'Erreur', 'message' => 'Commande non autorisée'], 403);
        }

        // Étape 8 - Vérifie que la commande pointé par le client sur lequel il veut poser un avis est en statut terminée
        if ($commande->getStatut() !== 'Terminée') {
            return $this->json(['status' => 'Erreur', 'message' => 'Vous ne pouvez laisser un avis que sur une commande terminée'], 400);
        }

        // Étape 9 - Vérifier qu'il n'y a pas déjà un avis pour cette commande
        $avisExistant = $avisRepository->findOneBy(['commande' => $commande, 'utilisateur' => $utilisateur]);
        if ($avisExistant) {
            return $this->json(['status' => 'Erreur', 'message' => 'Vous avez déjà laissé un avis pour cette commande'], 409);
        }

        // Étape 10 - Crée l'avis
        $avis = new Avis();
        $avis->setNote($data['note']);
        $avis->setDescription($data['description']);
        $avis->setStatut('en_attente');
        $avis->setUtilisateur($utilisateur);
        $avis->setCommande($commande);

        // Étape 11 - Persiste et sauvegarde en base
        $em->persist($avis);
        $em->flush();

        // Étape 12 - Retourner un message de confirmation
        return $this->json([
            'status'  => 'Succès', 
            'message' => 'Avis soumis avec succès, il sera validé prochainement',
            'commande' => $commande->getNumeroCommande()
        ], 201);
    }

}
