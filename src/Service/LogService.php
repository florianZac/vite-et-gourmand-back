<?php
namespace App\Service;

use App\Entity\LogActivite;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @author      Florian Aizac
 * @created     28/02/2026
 * @description Service gérant l'enregistrement des logs d'activité (MySQL)
 */
class LogService
{
	public function __construct(private EntityManagerInterface $em) {}

	public function log(string $type, string $email, string $role, array $contexte = []): void
	{
		$message = $this->genererMessage($type, $email, $contexte);

		$log = new LogActivite();
		$log->setType($type);
		$log->setMessage($message);
		$log->setEmail($email);
		$log->setRole($role);
		$log->setContexte($contexte);

		$this->em->persist($log);
		$this->em->flush();
	}

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