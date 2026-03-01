<?php

namespace App\Service;

/**
 * @author      Florian Aizac
 * @created     01/03/2026
 * @description Service de sanitisation des données entrantes
 * Centralise le nettoyage des données pour éviter les injections XSS et SQL
 * Utilisable dans tous les controllers via injection de dépendance
 *
 * Types supportés :
 *  - email   : nettoie et valide un email
 *  - texte   : nettoie un texte court (sujet, titre...)
 *  - message : nettoie un texte long avec limite de taille
 */
class SanitizerService
{
    /**
     * @description Nettoie une valeur selon son type pour éviter les injections
     * @param string $valeur La valeur à nettoyer
     * @param string $type   Le type de champ : 'email', 'texte', 'message'
     * @return string        La valeur nettoyée, chaîne vide si invalide
     */
    public function sanitize(string $valeur, string $type): string
    {
        // Étape 1 - Supprime les espaces inutiles en début et fin de chaîne
        $valeur = trim($valeur);

        // Étape 2 - Supprime toutes les balises HTML et PHP
        $valeur = strip_tags($valeur);

        // Étape 3 - Convertit les caractères spéciaux HTML en entités
        $valeur = htmlspecialchars($valeur, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Étape 4 - Traitement spécifique selon le type de champ
        switch ($type) {
            case 'email':
                // Supprime tous les caractères non autorisés dans un email
                $valeur = filter_var($valeur, FILTER_SANITIZE_EMAIL);
                // Si l'email n'est pas valide on retourne une chaîne vide
                if (!filter_var($valeur, FILTER_VALIDATE_EMAIL)) {
                    return '';
                }
                break;

            case 'texte':
                // Supprime les caractères de contrôle invisibles (ex: null byte \0)
                $valeur = preg_replace('/[\x00-\x1F\x7F]/u', '', $valeur);
                // Supprime les tentatives d'injection SQL basiques
                $valeur = preg_replace('/(\bunion\b|\bselect\b|\binsert\b|\bdelete\b|\bdrop\b|\bupdate\b)/i', '', $valeur);
                break;

            case 'message':
                // Supprime les caractères de contrôle invisibles
                $valeur = preg_replace('/[\x00-\x1F\x7F]/u', '', $valeur);
                // Supprime les tentatives d'injection SQL
                $valeur = preg_replace('/(\bunion\b|\bselect\b|\binsert\b|\bdelete\b|\bdrop\b|\bupdate\b)/i', '', $valeur);
                // Limite la taille du message à 1000 caractères
                if (strlen($valeur) > 1000) {
                    $valeur = substr($valeur, 0, 1000);
                }
                break;

            default:
                return '';
        }

        return $valeur;
    }
}