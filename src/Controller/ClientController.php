<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use App\Repository\CommandeRepository;
use App\Repository\SuiviCommandeRepository;
use App\Service\MailerService;
use App\Service\LogService; // import du LogService MongoDB
use App\Entity\Avis;
use App\Repository\AvisRepository;
use App\Enum\CommandeStatut;

use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

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

	#[Route('/profil', name: 'api_client_profil', methods: ['GET'])]
	#[OA\Get(
			summary: 'Profil du client connecté',
			description: 'Retourne les informations du profil du client authentifié via le token JWT.'
	)]
	#[OA\Tag(name: 'Client - Profil')]
	#[OA\Response(response: 200, description: 'Données du profil retournées avec succès')]
	#[OA\Response(response: 401, description: 'Utilisateur non connecté')]
	#[OA\Response(response: 403, description: 'Accès refusé - Rôle CLIENT requis')]
	/**
	 * @description Retourne les informations du profil client connecté
	 * @param '' auncun parametre requis
	 * @return JsonResponse une réponse JSON avec les données de son profil
	 */
	// Récupère les données du profil du client connecté
	public function getProfil(): JsonResponse
	{
			
		// Étape 1 - Vérifie que l'utilisateur a le rôle CLIENT
		if (!$this->isGranted('ROLE_CLIENT')) {
			return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
		}

		// Étape 2 - Récupère l'utilisateur connecté via le token JWT
		$utilisateur = $this->getUser();

		// Étape 3 - Vérifie que l'utilisateur est bien une instance de l'entité Utilisateur
		if (!$utilisateur instanceof Utilisateur) {
			return $this->json(['status' => 'Erreur', 'message' => 'Utilisateur non connecté'], 401);
		}

		// Étape 4 - Formater les données utilisateur pour éviter la référence circulaire
		$data = [
			'id' => $utilisateur->getId(),
			'nom' => $utilisateur->getNom(),
			'prenom' => $utilisateur->getPrenom(),
			'email' => $utilisateur->getEmail(),
			'telephone' => $utilisateur->getTelephone(),
			'role' => $utilisateur->getRole()?->getLibelle(),
			'adresse_postale' => $utilisateur->getAdressePostale(),
			'ville' => $utilisateur->getVille(),
			'code_postal' => $utilisateur->getCodePostal(),
			'pays' => $utilisateur->getPays(),
			'statut_compte' => $utilisateur->getStatutCompte(),
		];

		// Étape 5 - Retour JSON
		return $this->json([
      'status' => 'Succès',
      'utilisateur' => $data
		]);
	}

	#[Route('/profil', name: 'api_client_update_profil', methods: ['PUT'])]
	#[OA\Put(
			summary: 'Modifier le profil client',
			description: 'Met à jour les informations du client connecté. Tous les champs sont optionnels.'
	)]
	#[OA\Tag(name: 'Client - Profil')]
	#[OA\RequestBody(
		required: true,
		content: new OA\JsonContent(
			properties: [
				new OA\Property(property: 'nom', type: 'string', example: 'Dupont'),
				new OA\Property(property: 'prenom', type: 'string', example: 'Marie'),
				new OA\Property(property: 'email', type: 'string', example: 'marie.dupont@email.com'),
				new OA\Property(property: 'telephone', type: 'string', example: '0612345678'),
				new OA\Property(property: 'password', type: 'string', example: 'NouveauMdp123!'),
				new OA\Property(property: 'ville', type: 'string', example: 'Bordeaux'),
				new OA\Property(property: 'code_postal', type: 'string', example: '33000'),
				new OA\Property(property: 'adresse_postale', type: 'string', example: '12 rue des Roses'),
				new OA\Property(property: 'pays', type: 'string', example: 'France'),
			]
		)
	)]
	#[OA\Response(response: 200, description: 'Profil mis à jour avec succès')]
	#[OA\Response(response: 400, description: 'Mot de passe invalide')]
	#[OA\Response(response: 401, description: 'Utilisateur non connecté')]
	#[OA\Response(response: 403, description: 'Accès refusé')]
	#[OA\Response(response: 409, description: 'Email ou téléphone déjà utilisé')]
	/**
	 * @description Met à jour les informations du profil du client connecté
	 */
	public function updateUserById(
		Request $request,
		EntityManagerInterface $em,
		UserPasswordHasherInterface $passwordHasher,
		UtilisateurRepository $utilisateurRepository
	): JsonResponse {
		// Étape 1 - Vérifier le rôle CLIENT
		if (!$this->isGranted('ROLE_CLIENT')) {
			return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
		}

		// Étape 2 - Récupérer l'utilisateur connecté
		$utilisateur = $this->getUser();

		// Étape 3 - Vérifie que l'utilisateur est bien une instance de l'entité Utilisateur
		if (!$utilisateur instanceof Utilisateur) {
			return $this->json(['status' => 'Erreur', 'message' => 'Utilisateur non connecté'], 401);
		}

		// Étape 4 - Récupérer les données JSON
		$data = json_decode($request->getContent(), true);

		// Étape 5 - Mise à jour de l'email et vérification doublon
		if (isset($data['email'])) {
			$emailExistant = $utilisateurRepository->findOneBy(['email' => $data['email']]);
			if ($emailExistant && $emailExistant->getId() !== $utilisateur->getId()) {
					return $this->json(['status' => 'Erreur', 'message' => 'Cet email est déjà utilisé'], 409);
			}
			$utilisateur->setEmail($data['email']);
		}

		// Étape 6 - Validation et modification du mot de passe
		if (isset($data['password'])) {
			// Mêmes règles qu'à l'inscription : 10 car. min, 1 majuscule, 1 minuscule, 1 chiffre, 1 spécial
			if (strlen($data['password']) < 10 ||
				!preg_match('/[A-Z]/', $data['password']) ||
				!preg_match('/[a-z]/', $data['password']) ||
				!preg_match('/[0-9]/', $data['password']) ||
				!preg_match('/[\W_]/', $data['password'])) {
				return $this->json([
					'status'  => 'Erreur',
					'message' => 'Mot de passe invalide : 10 caractères minimum, 1 majuscule, 1 minuscule, 1 chiffre, 1 caractère spécial'
				], 400);
			}
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

		// Étape 10 - Mise à jour de la ville
		if (isset($data['ville'])) {
			$utilisateur->setVille($data['ville']);
		}

		// Étape 11 - Mise à jour du code postal
		if (isset($data['code_postal'])) {
			$utilisateur->setCodePostal($data['code_postal']);
		}

		// Étape 12 - Mise à jour de l'adresse postale
		if (isset($data['adresse_postale'])) {
			$utilisateur->setAdressePostale($data['adresse_postale']);
		}

		// Étape 13 - Mise à jour du pays
		if (isset($data['pays'])) {
			$utilisateur->setPays($data['pays']);
		}

		// Étape 14 - Sauvegarder en base
		$em->flush();

		// Étape 15 - Retourner un message de confirmation
		return $this->json(['status' => 'Succès', 'message' => 'Profil mis à jour avec succès']);
	}

	#[Route('/compte/desactivation', name: 'api_client_compte_desactivation', methods: ['POST'])]
	#[OA\Post(
		summary: 'Demander la désactivation du compte',
		description: 'Le client demande la désactivation de son compte. Le statut passe à "en_attente_desactivation" et un email est envoyé à l\'administrateur.'
	)]
	#[OA\Tag(name: 'Client - Profil')]
	#[OA\Response(response: 200, description: 'Demande de désactivation prise en compte')]
	#[OA\Response(response: 400, description: 'Demande déjà en cours ou compte déjà désactivé')]
	#[OA\Response(response: 401, description: 'Utilisateur non connecté')]
	#[OA\Response(response: 403, description: 'Accès refusé')]
	/**
	 * @description Demande de désactivation du compte client et envois d'un mail à l'admin
	 */
	public function demandeDesactivation(EntityManagerInterface $em, MailerService $mailerService): JsonResponse
	{
		// Étape 1 - Vérifier le rôle CLIENT
		if (!$this->isGranted('ROLE_CLIENT')) {
			return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
		}

		// Étape 2 - Récupérer l'utilisateur connecté
		$utilisateur = $this->getUser();

		// Étape 3 - Vérifie que l'utilisateur est bien une instance de l'entité Utilisateur
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

		// Étape 6 - Modification du statut du compte
		$utilisateur->setStatutCompte('en_attente_desactivation');

		// Étape 7 - Sauvegarder en base
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

	#[Route('/commandes', name: 'api_client_commandes', methods: ['GET'])]
	#[OA\Get(
		summary: 'Liste des commandes du client',
		description: 'Retourne toutes les commandes passées par le client connecté avec le titre du menu, la réduction appliquée et l\'avis si existant.'
	)]
	#[OA\Tag(name: 'Client - Commandes')]
	#[OA\Response(response: 200, description: 'Liste des commandes retournée')]
	#[OA\Response(response: 401, description: 'Utilisateur non connecté')]
	#[OA\Response(response: 403, description: 'Accès refusé')]
	/**
	 * @description Retourne la liste des commandes du client connecté
	 * Inclut : titre du menu, montant de la réduction, avis si existant
	 */
	public function getCommandes(CommandeRepository $commandeRepository, AvisRepository $avisRepository): JsonResponse
	{
		// Étape 1 - Vérifie le rôle CLIENT
		if (!$this->isGranted('ROLE_CLIENT')) {
			return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
		}

		// Étape 2 - Récupère l'utilisateur connecté
		$utilisateur = $this->getUser();

		// Étape 3 - Vérifie que l'utilisateur est bien une instance de l'entité Utilisateur
		if (!$utilisateur instanceof Utilisateur) {
			return $this->json(['status' => 'Erreur', 'message' => 'Utilisateur non connecté'], 401);
		}

		// Étape 4 - Récupère ses commandes via le repository
		$commandes = $commandeRepository->findByUtilisateur($utilisateur);

		// Étape 5 - Formater les commandes pour éviter la référence circulaire
		$data = [];
		foreach ($commandes as $commande) {

			// Calcul de la réduction (prix sans réduction - prix avec réduction appliquée)
			// Si le prix_menu est inférieur au prix théorique, c'est qu'une réduction -10% a été appliquée
			$menu = $commande->getMenu();
			$prixSansReduction = $menu ? $menu->getPrixParPersonne() * $commande->getNombrePersonne() : 0;
			$reductionMontant = round($prixSansReduction - $commande->getPrixMenu(), 2);

			// Chercher si un avis existe pour cette commande
			$avisExistant = $avisRepository->findOneBy([
				'commande' => $commande,
				'utilisateur' => $utilisateur
			]);
			$avisData = null;
			if ($avisExistant) {
				$avisData = [
					'id' => $avisExistant->getId(),
					'note' => $avisExistant->getNote(),
					'statut' => $avisExistant->getStatut(),
				];
			}

			$data[] = [
				'id' => $commande->getId(),
				'numero_commande' => $commande->getNumeroCommande(),
				'date_commande' => $commande->getDateCommande()?->format('d/m/Y H:i'),
				'date_prestation' => $commande->getDatePrestation()?->format('d/m/Y'),
				'heure_livraison' => $commande->getHeureLivraison()?->format('H:i'),
				'statut' => $commande->getStatut(),
				'nombre_personne' => $commande->getNombrePersonne(),
				'prix_menu' => $commande->getPrixMenu(),
				'prix_livraison' => $commande->getPrixLivraison(),
				'distance_km' => $commande->getDistanceKm(),
				'etat_materiel' => $commande->getEtatMateriel(),
				'menu_titre' => $commande->getMenu()?->getTitre(),
				'reduction_montant' => $reductionMontant > 0 ? $reductionMontant : 0,
				'avis' => $avisData,
			];
		}

		// Étape 6 - Retour JSON
		return $this->json([
			'status' => 'Succès',
			'total' => count($data),
			'commandes' => $data
		]);
	}

	#[Route('/commandes/{id}', name: 'api_client_commande_modifier', methods: ['PUT'])]
	#[OA\Put(
		summary: 'Modifier une commande',
		description: 'Modifie une commande en statut modifiable. Si nombre_personnes ou ville_livraison change, les prix sont recalculés automatiquement.'
	)]
	#[OA\Tag(name: 'Client - Commandes')]
	#[OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID de la commande', schema: new OA\Schema(type: 'integer'))]
	#[OA\RequestBody(
		required: true,
		content: new OA\JsonContent(
			properties: [
				new OA\Property(property: 'date_prestation', type: 'string', example: '2026-05-01'),
				new OA\Property(property: 'nombre_personnes', type: 'integer', example: 20),
				new OA\Property(property: 'adresse_livraison', type: 'string', example: '15 rue de la paix'),
				new OA\Property(property: 'ville_livraison', type: 'string', example: 'Mérignac'),
				new OA\Property(property: 'distance_km', type: 'number', example: 8),
			]
		)
	)]
	#[OA\Response(response: 200, description: 'Commande modifiée avec succès (prix recalculés si applicable)')]
	#[OA\Response(response: 400, description: 'Modification impossible (statut non modifiable ou date invalide)')]
	#[OA\Response(response: 401, description: 'Utilisateur non connecté')]
	#[OA\Response(response: 403, description: 'Accès refusé ou commande non autorisée')]
	#[OA\Response(response: 404, description: 'Commande non trouvée')]
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

		// Étape 5 - Vérifier que la commande est bien en statut MODIFIABLES
		// La modification n'est possible que si la commande n'est pas dans un status MODIFIABLES
		if (!in_array($commande->getStatut(), CommandeStatut::MODIFIABLES, true)) {
			return $this->json(['status' => 'Erreur', 'message' => 'Modification impossible : la commande ne peut plus être modifiée'], 400);
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
			// Étape 9.2 - Récupérer le menu associé à la commande
			$menu = $commande->getMenu();

			// Étape 9.3 - Recalcul du prix menu avec éventuelle réduction -10%
			$prixMenu = $menu->getPrixParPersonne() * $nombrePersonnes;
			if ($nombrePersonnes > ($menu->getNombrePersonneMinimum() + 5)) {
				$prixMenu = $prixMenu * 0.90;
			}

			// Étape 9.4 : Recalcul du prix de livraison
			// Gratuit à Bordeaux, sinon 5€ + 0,59€/km
			if ($villeLivraison === 'bordeaux') {
				$prixLivraison = 0;
			} else {
				$prixLivraison = 5 + (0.59 * $distanceKm);
			}

			// Étape 9.5 - Recalcul de l'acompte
			$libelleTheme   = strtolower($menu->getTheme()->getLibelle());
			$tauxAcompte    = ($libelleTheme === 'événement') ? 0.50 : 0.30;
			$montantAcompte = ($prixMenu + $prixLivraison) * $tauxAcompte;

			// Étape 9.6 - Mise à jour des champs recalculés
			$commande->setNombrePersonne($nombrePersonnes);
			$commande->setVilleLivraison($data['ville_livraison'] ?? $commande->getVilleLivraison());
			$commande->setPrixMenu(round($prixMenu, 2));
			$commande->setPrixLivraison(round($prixLivraison, 2));
			$commande->setMontantAcompte(round($montantAcompte, 2));
		}

		// Étape 10 - Sauvegarder en base
		$em->flush();

		// Étape 11 - Retourner une confirmation avec les nouveaux prix si recalcul
		$reponse = ['status' => 'Succès', 'message' => 'Commande modifiée avec succès'];

		if ($recalcul) {
			$reponse['prix_menu']           = $commande->getPrixMenu();
			$reponse['prix_livraison']      = $commande->getPrixLivraison();
			$reponse['montant_acompte']     = $commande->getMontantAcompte();
			$reponse['reduction_appliquee'] = $nombrePersonnes > ($commande->getMenu()->getNombrePersonneMinimum() + 5) ? '-10%' : 'aucune';
		}

		return $this->json($reponse);
	}

	#[Route('/commandes/{id}/annuler', name: 'api_client_commande_annuler', methods: ['POST'])]
	#[OA\Post(
		summary: 'Annuler une commande',
		description: 'Annule une commande du client. Remboursement dégressif : >7j = 100%, 3-7j = 50%, <3j = 0%. Un motif est obligatoire.'
	)]
	#[OA\Tag(name: 'Client - Commandes')]
	#[OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID de la commande', schema: new OA\Schema(type: 'integer'))]
	#[OA\RequestBody(
		required: true,
		content: new OA\JsonContent(
			properties: [
				new OA\Property(property: 'motif_annulation', type: 'string', example: 'Changement de programme'),
			]
		)
	)]
	#[OA\Response(response: 200, description: 'Commande annulée avec montant remboursé')]
	#[OA\Response(response: 400, description: 'Annulation impossible, déjà annulée, ou motif manquant')]
	#[OA\Response(response: 401, description: 'Utilisateur non connecté')]
	#[OA\Response(response: 403, description: 'Accès refusé ou commande non autorisée')]
	#[OA\Response(response: 404, description: 'Commande non trouvée')]
	/**
	 * @description Annule une commande passée par le client
	 * Remboursement dégressif selon le délai avant prestation :
	 *  - > 7 jours   : 100%
	 *  - 3 à 7 jours : 50%
	 *  - < 3 jours   : 0%
	 */
	public function annulerCommande(
		int $id,
		Request $request,
		CommandeRepository $commandeRepository,
		EntityManagerInterface $em,
		MailerService $mailerService,
		LogService $logService
	): JsonResponse {
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
		if ($commande->getStatut() === CommandeStatut::ANNULEE) {
			return $this->json(['status' => 'Erreur', 'message' => 'Commande déjà annulée'], 400);
		}

		// Étape 7 - Vérifier que la commande est en statut annulable
		if (!in_array($commande->getStatut(), CommandeStatut::ANNULABLES_CLIENT, true)) {
			return $this->json(['status' => 'Erreur', 'message' => 'Annulation impossible, la commande n\'est plus en attente'], 400);
		}

		// Étape 8 - Récupérer le motif d'annulation (obligatoire)
		$data            = json_decode($request->getContent(), true);
		$motifAnnulation = $data['motif_annulation'] ?? null;

		if (empty($motifAnnulation)) {
			return $this->json(['status' => 'Erreur', 'message' => 'Le motif d\'annulation est obligatoire'], 400);
		}

		// Étape 9 - Calculer le nombre de jours avant la prestation
		$datePrestation = $commande->getDatePrestation();
		$aujourdhui     = new \DateTime();
		$diff           = $aujourdhui->diff($datePrestation)->days;

		// Étape 10 - Calcul du montant remboursé selon les règles métier
		$montantTotal         = $commande->getPrixMenu() + $commande->getPrixLivraison();
		$montantRembourse     = 0;
		$pourcentageRembourse = 0;

		if ($diff > 7) {
			// Plus de 7 jours : remboursement intégral
			$montantRembourse     = $montantTotal;
			$pourcentageRembourse = 100;
		} elseif ($diff >= 3 && $diff <= 7) {
			// Entre 3 et 7 jours : remboursement à 50%
			$montantRembourse     = $montantTotal / 2;
			$pourcentageRembourse = 50;
		}
		// Moins de 3 jours : aucun remboursement (montantRembourse = 0)

		// Étape 11 - Construire le message de remboursement
		switch ($pourcentageRembourse) {
			case 100:
					$messageRemboursement = 'Vous avez été remboursé à 100%';
					break;
			case 50:
					$messageRemboursement = 'Vous avez été remboursé à 50%';
					break;
			default:
					$messageRemboursement = 'Vous n\'avez pas été remboursé';
		}

		// Étape 12 - Mettre à jour la commande
		$commande->setStatut(CommandeStatut::ANNULEE);
		$commande->setMotifAnnulation($motifAnnulation);
		$commande->setMontantRembourse($montantRembourse);

		// Étape 13 - Sauvegarder en base
		$em->flush();

		// Étape 14 - Envoyer un email de confirmation d'annulation
		$mailerService->sendAnnulationEmail($utilisateur, $commande, $pourcentageRembourse, $montantRembourse);

		// Étape 15 - Enregistrer le log dans MongoDB
		$logService->log(
			'commande_annulee',
			$utilisateur->getEmail(),
			'ROLE_CLIENT',
			[
				'numero_commande'       => $commande->getNumeroCommande(),
				'motif'                 => $motifAnnulation,
				'montant_rembourse'     => $montantRembourse,
				'pourcentage_rembourse' => $pourcentageRembourse,
			]
		);

		// Étape 16 - Retourner un message de confirmation
		return $this->json([
			'status'            => 'Succès',
			'message'           => $messageRemboursement,
			'montant_rembourse' => $montantRembourse
		]);
	}

	// =========================================================================
	// SUIVIS
	// =========================================================================

	#[Route('/commandes/{id}/suivi', name: 'api_client_commande_suivi', methods: ['GET'])]
	#[OA\Get(
		summary: 'Suivi d\'une commande',
		description: 'Retourne l\'historique des statuts d\'une commande du client, trié du plus ancien au plus récent.'
	)]
	#[OA\Tag(name: 'Client - Commandes')]
	#[OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID de la commande', schema: new OA\Schema(type: 'integer'))]
	#[OA\Response(response: 200, description: 'Suivis retournés avec succès')]
	#[OA\Response(response: 401, description: 'Utilisateur non connecté')]
	#[OA\Response(response: 403, description: 'Accès refusé ou commande non autorisée')]
	#[OA\Response(response: 404, description: 'Commande non trouvée')]
	/**
	 * @description Afficher le suivis de commande du client
	 * L'utilisateur doit être authentifié et avoir le rôle CLIENT pour accéder à cette route. 
	 * @param int id  correspond à commande_id id de la commande à annuler
	 * @param CommandeRepository $commandeRepository Le repository des commandes
	 * @param SuiviCommandeRepository $suiviCommandeRepository les methodes de suivis de commandes
	 * @return JsonResponse reponse JSON
	 */
	public function getSuiviCommande(
		int $id,
		CommandeRepository $commandeRepository,
		SuiviCommandeRepository $suiviCommandeRepository
	): JsonResponse {
		// Étape 1 - Vérifie le rôle CLIENT
		if (!$this->isGranted('ROLE_CLIENT')) {
			return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
		}

		// Étape 2 - Récupère l'utilisateur connecté
		$utilisateur = $this->getUser();
		// Étape 2.1 Vérifie que l'utilisateur est connecté et est bien une instance de l'entité Utilisateur
		if (!$utilisateur instanceof Utilisateur) {
			return $this->json(['status' => 'Erreur', 'message' => 'Utilisateur non connecté'], 401);
		}

		// Étape 3 - Cherche la commande par son id
		$commande = $commandeRepository->find($id);

		// Étape 4 - Si non trouvée retourner 404
		if (!$commande) {
			return $this->json(['status' => 'Erreur', 'message' => 'Commande non trouvée'], 404);
		}

		// Étape 5 - Vérifier que la commande appartient au client connecté
		if ($commande->getUtilisateur()->getId() !== $utilisateur->getId()) {
			return $this->json(['status' => 'Erreur', 'message' => 'Commande non autorisée'], 403);
		}

		// Étape 6 - Récupérer les suivis triés du plus ancien au plus récent
		$suivis = $suiviCommandeRepository->findBy(
			['commande' => $commande],
			['date_statut' => 'ASC']
		);

		// Étape 7 - Formater les données pour éviter la référence circulaire
		$suivisFormates = [];
		foreach ($suivis as $suivi) {
			$suivisFormates[] = [
					'statut'      => $suivi->getStatut(),
					'date_statut' => $suivi->getDateStatut()->format('d/m/Y H:i'),
			];
		}

		// Étape 8 - Retourner les suivis en JSON
		return $this->json([
			'status'  => 'Succès',
			'message' => 'Suivis retournés avec succès',
			'total'   => count($suivis),
			'suivis'  => $suivisFormates
		]);
	}

	// =========================================================================
	// AVIS
	// =========================================================================

	#[Route('/avis', name: 'api_client_avis_list', methods: ['GET'])]
	#[OA\Get(
		summary: 'Liste des avis du client',
		description: 'Retourne tous les avis déposés par le client connecté, triés du plus récent au plus ancien.'
	)]
	#[OA\Tag(name: 'Client - Avis')]
	#[OA\Response(response: 200, description: 'Liste des avis retournée')]
	#[OA\Response(response: 401, description: 'Utilisateur non connecté')]
	#[OA\Response(response: 403, description: 'Accès refusé')]
	/**
	 * @description Afficher la liste des avis du client connecté, triés du plus récent au plus ancien
	 */
	public function getAvis(AvisRepository $avisRepository): JsonResponse
	{
		// Étape 1 - Vérifier le rôle CLIENT
		if (!$this->isGranted('ROLE_CLIENT')) {
			return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
		}

		// Étape 2 - Récupérer l'utilisateur connecté
		$utilisateur = $this->getUser();
		if (!$utilisateur instanceof Utilisateur) {
			return $this->json(['status' => 'Erreur', 'message' => 'Utilisateur non connecté'], 401);
		}

		// Étape 3 - Récupérer ses avis triés du plus récent au plus ancien
		$avis = $avisRepository->findBy(
			['utilisateur' => $utilisateur],
			['id' => 'DESC']
		);

		// Étape 4 - Formater les avis pour éviter la référence circulaire
		$data = [];
		foreach ($avis as $unAvis) {
			$data[] = [
				'id' => $unAvis->getId(),
				'note' => $unAvis->getNote(),
				'description' => $unAvis->getDescription(),
				'statut' => $unAvis->getStatut(),
				'date' => $unAvis->getDate()?->format('d/m/Y H:i:s'),
				'commande_id' => $unAvis->getCommande()?->getId(),
				'commande_numero' => $unAvis->getCommande()?->getNumeroCommande(),
			];
		}

		// Étape 5 - Retour JSON
		return $this->json([
			'status' => 'Succès',
			'total' => count($data),
			'avis' => $data
		]);
	}

	#[Route('/commandes/{id}/avis', name: 'api_client_avis', methods: ['POST'])]
	#[OA\Post(
		summary: 'Poster un avis sur une commande terminée',
		description: 'Permet au client de déposer un avis (note + description) sur une commande au statut "Terminée". Un seul avis par commande.'
	)]
	#[OA\Tag(name: 'Client - Avis')]
	#[OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID de la commande', schema: new OA\Schema(type: 'integer'))]
	#[OA\RequestBody(
		required: true,
		content: new OA\JsonContent(
			properties: [
				new OA\Property(property: 'note', type: 'integer', example: 5, description: 'Note de 1 à 5'),
				new OA\Property(property: 'description', type: 'string', example: 'Excellent repas, service impeccable !', description: '255 caractères max'),
			]
		)
	)]
	#[OA\Response(response: 201, description: 'Avis soumis avec succès')]
	#[OA\Response(response: 400, description: 'Champs manquants, note invalide, description trop longue, ou commande non terminée')]
	#[OA\Response(response: 401, description: 'Utilisateur non connecté')]
	#[OA\Response(response: 403, description: 'Accès refusé ou commande non autorisée')]
	#[OA\Response(response: 404, description: 'Commande non trouvée')]
	#[OA\Response(response: 409, description: 'Avis déjà déposé pour cette commande')]
	/**
	 * @description Permettre à un client de poster un avis sur une commande au statut "Terminée"
	 */
	public function createAvis(
		int $id,
		Request $request,
		CommandeRepository $commandeRepository,
		AvisRepository $avisRepository,
		EntityManagerInterface $em
	): JsonResponse {
		// Étape 1 - Vérifie le rôle CLIENT
		if (!$this->isGranted('ROLE_CLIENT')) {
			return $this->json(['status' => 'Erreur', 'message' => 'Accès refusé'], 403);
		}

		// Étape 2 - Récupère l'utilisateur connecté
		$utilisateur = $this->getUser();
		if (!$utilisateur instanceof Utilisateur) {
			return $this->json(['status' => 'Erreur', 'message' => 'Utilisateur non connecté'], 401);
		}

		// Étape 3 - Récupère les données JSON
		$data = json_decode($request->getContent(), true);

		// Étape 4 - Vérifier les champs obligatoires
		if (empty($data['note']) || empty($data['description'])) {
			return $this->json(['status' => 'Erreur', 'message' => 'Note et description sont obligatoires'], 400);
		}

		// Étape 5 - Vérifier que la note est entre 1 et 5
		if ($data['note'] < 1 || $data['note'] > 5) {
			return $this->json(['status' => 'Erreur', 'message' => 'La note doit être entre 1 et 5'], 400);
		}

		// Étape 6 - Vérification de la taille de la description (255 caractères max)
		if (strlen($data['description']) > 255) {
			return $this->json(['status' => 'Erreur', 'message' => 'La description est trop longue (255 caractères max)'], 400);
		}

		// Étape 7 - Vérifier que la commande existe et appartient au client
		$commande = $commandeRepository->find($id);
		if (!$commande) {
				return $this->json(['status' => 'Erreur', 'message' => 'Commande non trouvée'], 404);
		}
		if ($commande->getUtilisateur()->getId() !== $utilisateur->getId()) {
			return $this->json(['status' => 'Erreur', 'message' => 'Commande non autorisée'], 403);
		}

		// Étape 8 - Vérifier que la commande est bien au statut Terminée
		if ($commande->getStatut() !== CommandeStatut::TERMINEE) {
			return $this->json(['status' => 'Erreur', 'message' => 'Vous ne pouvez laisser un avis que sur une commande terminée'], 400);
		}

		// Étape 9 - Vérifier qu'il n'y a pas déjà un avis pour cette commande
		$avisExistant = $avisRepository->findOneBy(['commande' => $commande, 'utilisateur' => $utilisateur]);
		if ($avisExistant) {
			return $this->json(['status' => 'Erreur', 'message' => 'Vous avez déjà laissé un avis pour cette commande'], 409);
		}

		// Étape 10 - Créer l'avis
		$avis = new Avis();
		$avis->setNote($data['note']);
		$avis->setDescription($data['description']);
		$avis->setStatut('en_attente');
		$avis->setUtilisateur($utilisateur);
		$avis->setCommande($commande);

		// Étape 11 - Persister et sauvegarder en base
		$em->persist($avis);
		$em->flush();

		// Étape 12 - Retourner un message de confirmation
		return $this->json([
			'status'   => 'Succès',
			'message'  => 'Avis soumis avec succès, il sera validé prochainement',
			'commande' => $commande->getNumeroCommande()
		], 201);
	}
}
