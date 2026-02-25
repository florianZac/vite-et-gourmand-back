<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

// Fonction utilisée redéfinir ou surcharger la méthode getUser()
// j'avait des érreurs avec la classe ClientController qui extend AbstractController 
// le problème vennait de la déclaration de getUser() car il ne s'avait pas reconnaitre que UserInterface  récuperer l'utilisateur avec des classes custom
// du coup en changeant le type de retour de ?UserInterface à ?Utilisateur cela corrige le problème

abstract class BaseController extends AbstractController
{
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
}