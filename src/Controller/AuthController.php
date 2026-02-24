<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\RoleRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @author      Florian Aizac
 * @created     24/02/2026
 * @description Contrôleur gérant l'authentification (login géré par Symfony, register géré ici)
 */
final class AuthController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        // Symfony gère le login automatiquement via json_login
        // Cette méthode ne sera jamais appelée directement
        throw new \Exception('Ne devrait pas être appelé directement');
    }

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    // utilisation de l'injection de dépendances pour récupérer les services nécessaires à la fonction register()

    public function register(
        Request $request,                            // la requête HTTP entrante (contenant les données JSON du client)
        UserPasswordHasherInterface $passwordHasher, // service qui hashe les mots de passe
        EntityManagerInterface $em,                  // service qui communique avec la BDD
        UtilisateurRepository $utilisateurRepository,// service qui fait les SELECT sur utilisateur
        RoleRepository $roleRepository               // service qui fait les SELECT sur role
    ): JsonResponse
    {
        // Étape 1 - Récupère les données JSON envoyées par le client
        $data = json_decode($request->getContent(), true);
        //dump($data); // version du printf affiche le tableau $data

        // Étape 2 - Vérifie que les champs obligatoires sont présents
        // il faudra que je fasse un regex pour vérifier que l'email est au bon format et que le mot de passe est assez fort, mais pour l'instant on se contente de vérifier qu'ils sont présents
        if (empty($data['email']) || empty($data['password']) || empty($data['prenom'])) {
            return $this->json(['status' => 'Erreur', 'message' => 'Email, password et prénom obligatoires'], 400);
        }

        // Étape 3 - Vérifie que l'email n'existe pas déjà en base de données
        // équivalent de SELECT * FROM utilisateur WHERE email = :email
        if ($utilisateurRepository->findOneBy(['email' => $data['email']])) {
            return $this->json(['status' => 'Erreur', 'message' => 'Cet email est déjà utilisé'], 409);
        }

        // Étape 4 - Récupère le rôle ROLE_CLIENT par défaut
        // équivalent de SELECT * FROM role WHERE libelle = 'ROLE_CLIENT'
        $role = $roleRepository->findOneBy(['libelle' => 'ROLE_CLIENT']);

        // Étape 5 - Création et remplissage d'un objet nouvel utilisateur
        $utilisateur = new Utilisateur();

        // Rappel de la notion : value ?? "" signifie "si value existe et n'est pas null, 
        // alors prends sa valeur, sinon prends une chaîne vide"
        $utilisateur->setEmail($data['email']);
        $utilisateur->setPrenom($data['prenom']);
        $utilisateur->setTelephone($data['telephone'] ?? ''); // si le champ telephone n'est pas présent dans les données, on met une chaîne vide par défaut
                                                                         //     
        $utilisateur->setVille($data['ville'] ?? '');
        $utilisateur->setPays($data['pays'] ?? '');
        $utilisateur->setAdressePostale($data['adresse_postale'] ?? '');
        $utilisateur->setRole($role );

        // Étape 6 - Hash le mot de passe avant de le stocker en base
        // On ne stocke JAMAIS un mot de passe en clair
        $motDePasseHashe = $passwordHasher->hashPassword($utilisateur, $data['password']);
        $utilisateur->setPassword($motDePasseHashe);

        // Étape 7 - Sauvegarde en base de données
        // persist() prépare l'insertion
        $em->persist($utilisateur);
        // flush() exécute réellement la requête SQL INSERT
        $em->flush();

        // Étape 8 - Retourne une réponse de succès avec le code 201 Created
        return $this->json([
            'status'  => 'Succès',
            'message' => 'Compte créé avec succès',
        ], 201);
    }
}