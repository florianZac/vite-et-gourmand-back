<?php

namespace App\Service;

use App\Document\LogActivite;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * @author      Florian Aizac
 * @created     28/02/2026
 * @description Service gérant l'enregistrement des logs d'activité dans MongoDB
 * 
 * Pourquoi un service dédié ?
 *      Centralise toute la logique de logging en un seul endroit
 *      S'injecte facilement dans n'importe quel controller via l'autowiring Symfony
 *      Facile à étendre si on veut ajouter de nouveaux types de logs
 * 
 * Utilisation dans un controller :
 *   $this->logService->log(
 *         'commande_creee', // type
 *         $utilisateur->getEmail() // email,  
 *         $utilisateur->getRole()->getLibelle() // rôle,
 *             [  
 *                 'numero_commande' => $commande->getNumeroCommande(), 
 *                 'montant'         => $commande->getPrixMenu(),
 *             ]);
 */
class LogService
{
    // Injection du DocumentManager MongoDB (équivalent de l'EntityManager pour MySQL)
    public function __construct(private DocumentManager $dm) {}

    /**
     * @description Enregistre un log d'activité dans MongoDB
     * 
     * @param string $type    Type d'action : connexion, commande_creee, commande_annulee, statut_change, inscription
     * @param string $email   Email de l'utilisateur concerné
     * @param string $role    Rôle de l'utilisateur : ROLE_CLIENT, ROLE_EMPLOYE, ROLE_ADMIN
     * @param array  $contexte Données supplémentaires variables selon le type de log
     * @return void
     */
    public function log(string $type, string $email, string $role, array $contexte = []): void
    {
        // Étape 1 - Générer le message automatiquement selon le type
        $message = $this->genererMessage($type, $email, $contexte);

        // Étape 2 - Créer le document MongoDB
        $log = new LogActivite();
        $log->setType($type);
        $log->setMessage($message);
        $log->setEmail($email);
        $log->setRole($role);
        $log->setContexte($contexte);

        // Étape 3 - Persister et sauvegarder dans MongoDB
        // persist() prépare l'insertion
        $this->dm->persist($log);
        // flush() exécute l'insertion dans MongoDB
        $this->dm->flush();
    }

    /**
     * @description Génère automatiquement un message lisible selon le type de log
     * @param string $type    Le type d'action
     * @param string $email   L'email de l'utilisateur
     * @param array  $contexte Les données contextuelles
     * @return string Le message formaté
     */
    private function genererMessage(string $type, string $email, array $contexte): string
    {
        return match($type) {
            'connexion'         => "L'utilisateur $email s'est connecté",
            'inscription'       => "Nouvel utilisateur inscrit : $email",
            'commande_creee'    => "Commande {$contexte['numero_commande']} créée par $email (montant : {$contexte['montant']} €)",
            'commande_annulee'  => "Commande {$contexte['numero_commande']} annulée par $email",
            'statut_change'     => "Commande {$contexte['numero_commande']} : statut changé en '{$contexte['nouveau_statut']}' par $email",
            default             => "Action '$type' effectuée par $email",
        };
    }
}