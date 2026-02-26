<?php

namespace App\Service;


/**
 * @author      Florian Aizac
 * @created     26/02/2026
 * @description Service gérant les jours ouvrés à une date donnée
 */
class DateService
{
    /**
     * @description Ajoute un nombre de jours ouvrés à une date donnée.
     *
     * - Les jours ouvrés étant du lundi à vendredi
     * - Le samedi et le dimanche sont ignorés
     * - Les jours fériés ne sont pas pris en compte peut etre plus tard à voir
     *
     * @param \DateTime $date Date de départ
     * @param int $days Nombre de jours ouvrés à ajouter
     * @return \DateTime Nouvelle date après ajout des jours ouvrés
     */
    public function addOpenDay(\DateTime $date, int $days): \DateTime
    {
        // Étape 1 - On clone la date pour ne pas modifier la date d’origine passée en paramètre
        $result = clone $date;
        // Étape 2 - On Initialise la variable de stockage du nombre de jour ouvré ajouté 
        $added = 0;
        // Étape 3 -  continue à incrémenter du lundi à vendredi tant que $added != $days
        while ($added < $days) {
            $result->modify('+1 day'); // avancer d'un jour
            $weekday = (int) $result->format('N'); // 1 = lundi, 7 = dimanche

            if ($weekday < 6) { // lundi à vendredi
                $added++;
            }
        }
        // Étape 4 - retournes un DateTime modifié
        return $result;
    }
}