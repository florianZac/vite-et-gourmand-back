<?php

namespace App\Repository;

use App\Entity\PasswordResetToken;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PasswordResetToken>
 */
class PasswordResetTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasswordResetToken::class);
    }

    /**
     * @description Récupère un token valide non expiré et non utilisé par sa valeur
     * 
     * Lors de la validation du lien envoyé par email,
     * on doit vérifier que le token existe, appartient à un utilisateur,
     * Et n'a pas expiré et n'a pas déjà été utilisé 
     * 
     * @param string $tokenValue le token envoyé par l'utilisateur
     * @return PasswordResetToken|null le token si valide, null sinon
     */
    public function findValidToken(string $tokenValue): ?PasswordResetToken
    {
        // Recherche le token dans la base de données
        $token = $this->findOneBy(['token' => $tokenValue]);

        // Si le token n'existe pas, retourne null
        if (!$token) {
            return null;
        }

        // Utilise la méthode isValid() de l'entité pour vérifier sa validité
        // Vérifie que:
        // 1. Le token n'a pas expiré
        // 2. Le token n'a pas déjà été utilisé
        if ($token->isValid()) {
            return $token;
        }

        return null;
    }

    /**
     * @description Supprime tous les tokens expirés ou utilisés d'un utilisateur
     * 
     * Netoyage de la Base de donnée pour ne pas avoir trop de données
     * On supprime les anciens tokens pour garder une base la plus courte possible
     * 
     * @param Utilisateur $utilisateur l'utilisateur dont on veut nettoyer les tokens
     * @return int le nombre de tokens supprimés
     */
    public function deleteOldTokens(Utilisateur $utilisateur): int
    {
        return $this->createQueryBuilder('t')
            ->delete()
            ->where('t.utilisateur = :utilisateur')
            ->andWhere('t.isUsed = true OR t.expiresAt <= :now')
            ->setParameter('utilisateur', $utilisateur)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->execute();
    }
}
