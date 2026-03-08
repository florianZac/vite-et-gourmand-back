<?php

namespace App\Service;

/**
 * @author      Florian Aizac
 * @created     08/03/2026
 * @description Service pour calculer la distance routière via l'API OSRM (gratuit).
 *              Utilise le serveur public : router.project-osrm.org
 *              Retourne la distance en km entre deux points GPS.
 */
class OsrmService
{
  /**
   * Calcule la distance routière entre deux points GPS
   * @param float $lon1 Longitude du point de départ
   * @param float $lat1 Latitude du point de départ
   * @param float $lon2 Longitude du point d'arrivée
   * @param float $lat2 Latitude du point d'arrivée
   * @return float|null Distance en kilomètres, ou null si erreur
   * @see https://project-osrm.org/docs/v5.5.1/api/#general-options
   * Utilisation : 
   * http://127.0.0.1:8000/distance?adresse1=15+route+de+Berat,31600+Lherm&adresse2=22+quai+des+Chartrons,33000+Bordeaux
   * Type de retour :
   * {
   *  "routes": [
   *   {
   *     "distance": 12345.6,  // distance en mètres
   *     "duration": 890       // durée en secondes
   *   }
   *  ]
   * }
   */
  public function getRouteDistance(float $lon1, float $lat1, float $lon2, float $lat2): ?float
  {
    // OSRM attend les coordonnées au format : longitude,latitude
    $url = sprintf(
        'http://router.project-osrm.org/route/v1/driving/%s,%s;%s,%s?overview=false',
        $lon1, $lat1, $lon2, $lat2
    );

    // Initialisation de la requête cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'ViteEtGourmand/1.0');
    // Timeout pour éviter de bloquer si le serveur OSRM ne répond pas
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Vérification de la réponse
    if ($httpCode !== 200 || !$response) {
        return null;
    }

    $data = json_decode($response, true);

    // OSRM retourne la distance en mètres dans routes[0]['distance']
    if (isset($data['routes'][0]['distance'])) {
        // Conversion mètres en  kilomètres, arrondi à 2 décimales
        return round($data['routes'][0]['distance'] / 1000, 2);
    }

    return null;
  }
}