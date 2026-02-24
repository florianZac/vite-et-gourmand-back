<?php
namespace App\Controller;

use App\Repository\UtilisateurRepository;
use App\Repository\CommandeRepository;
use App\Repository\SuiviCommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\MailerService;
use App\Entity\Avis;
use App\Repository\AvisRepository;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @author      Florian Aizac
 * @created     24/02/2026
 * @description Contrôleur gérant les actions du client connecté
 */

#[Route('/api/client')]

final class ClientController extends AbstractController
{
    /**
     * @description Cette fonction permet à un client connecté voir les informations de son profil..
     * @param void auncun parametre requis
     * @return JsonResponse une réponse JSON avec les données de son profil
     */
    #[Route('/profil', name: 'api_client_profil', methods: ['GET'])]
    // Récupère les données du profil du client connecté
    public function getProfil(): JsonResponse
    {
        // Vérifie que l'utilisateur a le rôle CLIENT
        if (!$this->isGranted('ROLE_CLIENT')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Récupère l'utilisateur connecté via le token JWT
        $utilisateur = $this->getUser();

        // Vérifie que l'utilisateur est connecté
        if (!$utilisateur) {
            return $this->json(['status' => 'Erreur', 'message' => 'Utilisateur non connecté'], 401);
        }

        // Retourne ses données en JSON
        return $this->json($utilisateur);
    }

    #[Route('/profil', name: 'api_client_update_profil', methods: ['PUT'])]
    /**
     * @description Cette fonction permet à un client connecté de mettre à jour les informations de son profil.
     * L'utilisateur peut mettre à jour les champs suivants : email, prenom, telephone, ville, adresse_postale et mot de passe.
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
        if (!$utilisateur) {
            return $this->json(['status' => 'Erreur', 'message' => 'Utilisateur non connecté'], 401);
        }

        // Étape 3 - Récupérer les données JSON
        $data = json_decode($request->getContent(), true);

        // Étape 4 - Mise à jour des champs

        // Vérification doublon email
        if (isset($data['email'])) {
            $emailExistant = $utilisateurRepository->findOneBy(['email' => $data['email']]);
            if ($emailExistant && $emailExistant->getId() !== $utilisateur->getId()) {
                return $this->json(['status' => 'Erreur', 'message' => 'Cet email est déjà utilisé'], 409);
            }
            $utilisateur->setEmail($data['email']);
        }

        // Modification du mot de passe
        if (isset($data['password'])) {
            $motDePasseHashe = $passwordHasher->hashPassword($utilisateur, $data['password']);
            $utilisateur->setPassword($motDePasseHashe);
        }

        // Mise à jour du prénom
        if (isset($data['prenom'])) {
            $utilisateur->setPrenom($data['prenom']);
        }

        // Vérification doublon téléphone
        if (isset($data['telephone'])) {
            $telephoneExistant = $utilisateurRepository->findOneBy(['telephone' => $data['telephone']]);
            if ($telephoneExistant && $telephoneExistant->getId() !== $utilisateur->getId()) {
                return $this->json(['status' => 'Erreur', 'message' => 'Ce téléphone est déjà utilisé'], 409);
            }
            $utilisateur->setTelephone($data['telephone']);
        }

        // Mise à jour de la ville
        if (isset($data['ville'])) {
            $utilisateur->setVille($data['ville']);
        }

        // Mise à jour de l'adresse postale
        if (isset($data['adresse_postale'])) {
            $utilisateur->setAdressePostale($data['adresse_postale']);
        }

        // Étape 5 - Sauvegarder en base
        $em->flush();

        // Étape 6 - Retourner un message de confirmation
        return $this->json(['status' => 'Succès', 'message' => 'Profil mis à jour avec succès']);
    }

    /**
     * @description Cette fonction permet à un client connecté de récupérer la liste de ses commandes passées.
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
        if (!$utilisateur) {
            return $this->json(['status' => 'Erreur', 'message' => 'Utilisateur non connecté'], 401);
        }

        // Étape 3 - Récupére ses commandes via le repository
        $commandes = $commandeRepository->findByUtilisateur($utilisateur);
        
        // Étape 4 - Retourne les commandes en JSON
        return $this->json(['status' => 'Succès', 'commandes' => $commandes]);
    }

    /**
     * @description Cette fonction permet à un client connecté d'annuler une commande passée en fournissant son ID.'
     * L'utilisateur doit être authentifié et avoir le rôle CLIENT pour accéder à cette route. 
     * @param int id  correspond à commande_id id de la commande à annuler
     * @param CommandeRepository $commandeRepository Le repository des commandes
     * @param EntityManagerInterface $em pour gérer les opérations de base de données
     * @param MailerService $mailerService pour envoyer un email de confirmation d'annulation
     * @return JsonResponse reponse JSON
     */
    #[Route('/commandes/{id}/annuler', name: 'api_client_commande_annuler', methods: ['POST'])]
    public function annulerCommande(int $id, Request $request, CommandeRepository $commandeRepository, EntityManagerInterface $em, MailerService $mailerService): JsonResponse
    {
        /*
            - Vérifier que la commande appartient au client
            - Récupérer la justification
            - Calculer le remboursement selon la date
            - Changer le statut à 'annulée'
            - Envoyer un email de confirmation
        */

        // Étape 1 - Vérifie le rôle CLIENT
        if (!$this->isGranted('ROLE_CLIENT')) {
            return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
        }

        // Étape 2 - Récupérer l'utilisateur connecté
        $utilisateur = $this->getUser();
        if (!$utilisateur) {
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

        // Étape 7 - Récupérer la justification depuis le JSON
        $data = json_decode($request->getContent(), true);
        $motifAnnulation = $data['motif_annulation'] ?? null;

        // Étape 8 - Calculer le nombre de jours avant la prestation
        // 8.1 : Récupérer la date de prestation de la commande
        $datePrestation = $commande->getDatePrestation();
        // 8.2 : Récupérer la date actuelle
        $aujourdhui = new \DateTime();
        // 8.3 : Calculer la différence en jours entre les deux dates
        $diff = $aujourdhui->diff($datePrestation)->days;

        /*
        Logique de remboursement :
        - Si la prestation est dans plus de 7 jours, le client est remboursé à 100%
        - Si la prestation est dans 3 à 7 jours, le client est remboursé à 50%
        - Si la prestation est dans moins de 3 jours, le client n'est pas remboursé
        */   

        // Étape 9 Mise en place et calcul du montant remboursé selon les règles sitée ci-dessus
        $montantRembourse = 0;
        // variable pour spécifier la cas 50 100 ou 0 pour le message de confirmation
        $pourcentageRembourse = 0;

        // Calcul du montant total de la commande (prix du menu + prix de la livraison)
        $montantTotal = $commande->getPrixMenu() + $commande->getPrixLivraison();

        // si la prestation est dans plus de 7 jours, le client est remboursé à 100%
        if ($diff > 7) {
            $montantRembourse = $montantTotal;
            $pourcentageRembourse = 100;
        // si la prestation est dans 3 à 7 jours, le client est remboursé à 50%
        } elseif ($diff >= 3 && $diff <= 7) {
            $montantRembourse = $montantTotal / 2;
            $pourcentageRembourse = 50;
        }
        // sinon si la prestation est dans moins de 3 jours, le client n'est pas remboursé
        else {
            $montantRembourse = 0;
            $pourcentageRembourse = 0;
        }   

        // Étape 10 mise à jour de la commande
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
        $commande->setStatut('annulée');
        $commande->setMotifAnnulation($motifAnnulation);
        $commande->setMontantRembourse($montantRembourse);

        // Étape 11 - Sauvegarder en base de données
        $em->flush();

        // Étape 12 - Envoyer un email de confirmation
        $mailerService->sendAnnulationEmail($utilisateur, $commande, $pourcentageRembourse, $montantRembourse);

        // Étape 13 - Retourner un message de confirmation avec le montant remboursé
        return $this->json([
            'status' => 'Succès',
            'message' => $messageRemboursement,
            'montant_rembourse' => $montantRembourse
        ]);
    }

    /**
     * @description Cette fonction permet à un client connecté d'afficher le suivis de commande d'après son ID.'
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
        if (!$utilisateur) {
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

    /**
     * @description Cette fonction permet à un client de poster un avis lorsque sa commande est en statut terminée'
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
        if (!$utilisateur) {
            return $this->json(['status' => 'Erreur', 'message' => 'Utilisateur non connecté'], 401);
        }

        // Étape 3 - Récupére les données JSON
        $data = json_decode($request->getContent(), true);

        // Étape 4 - Vérifier les champs obligatoires
        if (empty($data['note']) || empty($data['description'])) {
            return $this->json(['status' => 'Erreur', 'message' => 'Note et description sont obligatoires'], 400);
        }

        // Étape 5 - Vérifier que la note est entre 0 et 5
        if ($data['note'] < 0 || $data['note'] > 5) {
            return $this->json(['status' => 'Erreur', 'message' => 'La note doit être entre 0 et 5'], 400);
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

        // Étape 11 - Sauvegarder en base
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