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
 * @description Contrôleur gérant l'authentification 
 * utilisation : tous les rôle sont concerné.
 *  1. login              : Log tous les utilisateurs
 *  2. register           : Inscription de tous les utilisateurs
 */
final class AuthController extends AbstractController
{
    // Fonction qui log tous les utilisateurs
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        // Symfony gère le login automatiquement via json_login
        // Cette méthode ne sera jamais appelée directement
        throw new \Exception('Ne devrait pas être appelé directement');
    }

    // Fonction qui inscris de tous les utilisateurs
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
        if ( empty($data['nom']) || empty($data['prenom']) || empty($data['telephone']) ||
            empty($data['email']) || empty($data['password']) || empty($data['pays']) ||
            empty($data['ville']) || empty($data['code_postal'])) {
            return $this->json(['status' => 'Erreur', 'message' => 'Toutes les données doivent etre remplis'], 400);
        }

        // Étape 3 - Honeypot : si le champ site_web est rempli c'est un bot
        // On retourne un faux succès pour tromper le bot sans traiter la demande
        if (!empty($data['site_web'])) {
            return $this->json(['status' => 'Succès', 'message' => 'Compte créé avec succès'], 201);
        }

        // Étape 4 - Validation de l'email (.com et .fr uniquement)
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL) || 
            !preg_match('/\.(com|fr)$/i', $data['email'])) {
            return $this->json(['status' => 'Erreur', 'message' => 'Email invalide (extensions .com et .fr uniquement)'], 400);
        }

        // Étape 5 - Validation du téléphone (format français 06 ou 07)
        if (!preg_match('/^(\+33|0)(6|7)[0-9]{8}$/', $data['telephone'])) {
            return $this->json(['status' => 'Erreur', 'message' => 'Téléphone invalide (format 06 ou 07 requis)'], 400);
        }

        // Étape 6 - Validation du mot de passe
        // Règles : 10 caractères minimum, 1 majuscule, 1 minuscule, 1 chiffre, 1 caractère spécial
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
        

        // Étape 7 - Vérifie que l'email n'existe pas déjà en base de données
        // équivalent de SELECT * FROM utilisateur WHERE email = :email
        if ($utilisateurRepository->findOneBy(['email' => $data['email']])) {
            return $this->json(['status' => 'Erreur', 'message' => 'Cet email est déjà utilisé'], 409);
        }

        // Étape 8 - Récupère le rôle ROLE_CLIENT par défaut
        // équivalent de SELECT * FROM role WHERE libelle = 'ROLE_CLIENT'
        $role = $roleRepository->findOneBy(['libelle' => 'ROLE_CLIENT']);

        // Étape 9 - Création et remplissage d'un objet nouvel utilisateur
        $utilisateur = new Utilisateur();

        // Rappel de la notion : value ?? "" signifie "si value existe et n'est pas null, 
        // alors prends sa valeur, sinon prends une chaîne vide"

        $utilisateur->setNom($data['nom']);
        $utilisateur->setPrenom($data['prenom']);
        $utilisateur->setTelephone($data['telephone'] ?? ''); // si le champ telephone n'est pas présent dans les données, on met une chaîne vide par défaut
        $utilisateur->setEmail($data['email']);      
        $utilisateur->setPays($data['pays'] ?? '');
        $utilisateur->setVille($data['ville'] ?? '');
        $utilisateur->setAdressePostale($data['adresse_postale'] ?? '');
        $utilisateur->setCodePostal($data['code_postal'] ?? '');
        $utilisateur->setStatutCompte('actif');     
        $utilisateur->setRole($role );

        // Étape 10 - Hash le mot de passe avant de le stocker en base
        // On ne stocke JAMAIS un mot de passe en clair
        $motDePasseHashe = $passwordHasher->hashPassword($utilisateur, $data['password']);
        $utilisateur->setPassword($motDePasseHashe);

        // Étape 11 - Sauvegarde en base de données
        // persist() prépare l'insertion
        $em->persist($utilisateur);
        // flush() exécute réellement la requête SQL INSERT
        $em->flush();

        // Étape 12 - Retourne une réponse de succès avec le code 201 Created
        return $this->json([
            'status'  => 'Succès',
            'message' => 'Compte créé avec succès',
        ], 201);
    }
}