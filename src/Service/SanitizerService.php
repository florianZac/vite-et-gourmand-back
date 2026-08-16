<?php

namespace App\Service;

/**
 * @author      Florian Aizac
 * @created     01/03/2026
 * @description Service de sanitisation des données entrantes
 * Centralise le nettoyage des données pour éviter les injections XSS
 * Utilisable dans tous les controllers via injection de dépendance
 *
 * Note : Les injections SQL sont déjà gérées par Doctrine (requêtes préparées)
 *        Ce service se concentre uniquement sur le nettoyage XSS et la validation
 * 
 *  1. sanitize()       : Nettoie une valeur selon son type pour éviter les injections XSS
 *  2. escapeForHtml()  : Échappe une valeur pour l'affichage HTML à utiliser côté sortie/API
 * 
 */
class SanitizerService
{
    public function sanitizeSpace(string $input): string
    {
        return trim(strip_tags($input));
    }

    /**
     * @description Nettoie une valeur selon son type pour éviter les injections XSS
     * @param string $valeur La valeur à nettoyer
     * @param string $type   Le type de champ : 'email', 'texte', 'message', 'telephone'
     * @return string        La valeur nettoyée, chaîne vide si invalide
     */
    public function sanitize(string $valeur, string $type): string
    {
        // Étape 1 - Supprime les espaces inutiles en début et fin de chaîne
        $valeur = trim($valeur);

        // Étape 2 - Supprime toutes les balises HTML et PHP protection XSS principale
        $valeur = strip_tags($valeur);

        // Étape 3 - Supprime les caractères de contrôle invisibles null byte, etc.
        $valeur = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $valeur);

        // Étape 4 - Traitement spécifique selon le type de champ
        switch ($type) {
            case 'email':
                $valeur = filter_var($valeur, FILTER_SANITIZE_EMAIL);
                if (!filter_var($valeur, FILTER_VALIDATE_EMAIL)) {
                    return '';
                }
                break;

            case 'texte':
                // Limite la taille d'un texte court nom, prénom, ville...
                if (mb_strlen($valeur) > 255) {
                    $valeur = mb_substr($valeur, 0, 255);
                }
                break;

            case 'message':
                // Limite la taille du message à 1000 caractères
                if (mb_strlen($valeur) > 1000) {
                    $valeur = mb_substr($valeur, 0, 1000);
                }
                break;

            case 'telephone':
                // Ne garde que les chiffres, +, espaces et tirets
                $valeur = preg_replace('/[^0-9+\-\s()]/', '', $valeur);
                break;

            case 'code_postal':
                // Ne garde que les chiffres
                $valeur = preg_replace('/[^0-9]/', '', $valeur);
                break;

            default:
                // Type inconnu : on retourne le texte nettoyé des étapes 1-3
                break;
        }

        return $valeur;
    }

    /**
     * @description Échappe une valeur pour l'affichage HTML à utiliser côté sortie/API
     * @param string $valeur La valeur à échapper
     * @return string La valeur échappée safe pour le HTML
     */
    public function escapeForHtml(string $valeur): string
    {
        return htmlspecialchars($valeur, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}