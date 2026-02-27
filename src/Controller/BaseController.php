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
        $user = parent::getUser();
        if ($user === null) {
            return null;
        }
        if (!$user instanceof Utilisateur) {
            throw new \LogicException('Utilisateur inattendu : ' . get_class($user));
        }
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
        $content = $request->getContent();
        if ('' === \trim($content)) {
            return [];
        }

        $data = json_decode($content, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException(
                'Payload JSON invalide : ' . json_last_error_msg()
            );
        }

        return $data;
    }
}