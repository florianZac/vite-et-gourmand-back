<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

// Fonction utilisée  pour redéfinir la méthode getUser()
// j'avait des érreurs avec la classe ClientController qui extend AbstractController 
// le problème vennait de la déclaration de getUser() car il ne s'avait pas reconnaitre que UserInterface récupere l'utilisateur avec des classes custom
// du coup en changeant le type de retour de ?UserInterface à ?Utilisateur cela corrige le problème.
// a creuser des que j'ai le temps.

abstract class BaseController extends AbstractController
{
    /**
     * Récupère un utilisateur.
     * Retourne un tableau vide si le corps est vide.
     *
     * @param Request $request
     * @return array
     * @throws \InvalidArgumentException si le JSON est mal formé
     */
    protected function getUser(): ?Utilisateur
    {
        // Étape 1 - Récupére l'utilisateur
        $user = parent::getUser();

        // Étape 2 - Vérifie s'il existe        
        if ($user === null) {
            return null;
        }

        // Étape 3 - Vérifie s'il fait partie de la base     
        if (!$user instanceof Utilisateur) {
            throw new \LogicException('Utilisateur inattendu : ' . get_class($user));
        }
        
        // Étape 4 - Retourne l'utilisateur   
        return $user;
    }
    
    /**
     * Récupère et décode le corps JSON d’une requête.
     * Retourne un tableau vide si le corps est vide.
     * 
     * @param Request $request
     * @return array
     * @throws \InvalidArgumentException si le JSON est mal formé
     */
    protected function getDataFromRequest(Request $request): array
    {
        // Étape 1 - Récupere le contenue d'une requete   
        $content = $request->getContent();

        // Étape 2 - Vérifie si le contenue est vide
        if ('' === \trim($content)) {
            return [];
        }

        // Étape 3 - Décode le Json en données exploitable 
        $data = json_decode($content, true);

        // Étape 4 - Vérifie si le json et valide
        if (JSON_ERROR_NONE !== json_last_error()) {
            // Étape 5 - génére un flag d'exeption pour la gestion d'érreur
            throw new \InvalidArgumentException(
                'JSON invalide : ' . json_last_error_msg()
            );
        }

        // Étape 6 - Retourne les données décodées
        return $data;
    }
}