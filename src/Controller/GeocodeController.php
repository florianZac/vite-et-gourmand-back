<?php

namespace App\Controller;

use App\Service\NominatimService; // On importe le service Nominatim
use App\Service\OsrmService; // On importe le service OSRM
use App\Service\SanitizerService;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Json;
use OpenApi\Attributes as OA;
/**
 * @author      Florian Aizac
 * @created     08/03/2026
 * @description Contrôleur pour gérer les requêtes liées au géocodage et le calcul des frais de livraisons lors de la commande d'un utilisateur
 *    
 *  1. geocodeUser()        : Accès à l'api Nominatim et recuperation de la longitude et la latitude de la valeur mis en parametres
 *                doit etre de cette forme -> http://127.0.0.1:8000/geocode?adresse=22++quai+des+Chartrons,+33000+Bordeaux,France
 *  2. distanceHaversine()  : Fonction privée qui calcule la distance entre deux points GPS
 *  3. distance()           : Distance à vol d'oiseau entre les deux adresses celle du restaurant et celle du client
 *  4. deliveryCost()       : Calcule les frais de livraison entre le restaurant (22 quai des Chartrons, Bordeaux) et l\'adresse du client via OSRM  et tarification. 
 *
 */
#[Route('/api')]
class GeocodeController extends AbstractController
{
	// Coordonnées fixes du restaurant Vite et Gourmand // 22 quai des Chartrons, Bordeaux
	private const RESTAURANT_LAT = 44.8562;
	private const RESTAURANT_LON = -0.5709;
	private const RESTAURANT_ADDRESS = '22 quai des Chartrons, 33000 Bordeaux, France';

	private const MAX_DELIVERY_DISTANCE = 200; // distance max de livraison en km	

	// Paramètres de tarification livraison
	private const FREE_DELIVERY_RADIUS_KM = 10;  // distance pour désigné si c'est gratuit ou non 
	private const DELIVERY_BASE_FEE = 5.00;     // Frais fixes en €
	private const DELIVERY_PER_KM_FEE = 0.59;  // Frais par km en €

	/**
	 * @author      Florian Aizac
	 * @created     08/03/2026
	 * @description Fonction perméttant de retourner une  longitude et la latitude d'une adresse mise en parametres
	 * @param NominatimService $nominatimService : le service de nominatim pour l'utilisation de l'api.
	 * @param Request $request : la requete URL.
	 * @return Json : retourne la réponse json d'une adresse passé en parametre.
	 * Exemple d'utilisation : http://127.0.0.1:8000/geocode?adresse=22++quai+des+Chartrons,+33000+Bordeaux,France
	 * Retour 
	 * [
	 *  'adresse',  
	 *  'latitude',
	 *  'longitude';
	 * ]
	 */
	#[Route('/geocode', name: 'geocode_address')]
	#[OA\Get(
		summary: 'Géocoder une adresse',
		description: 'Convertit une adresse en coordonnées GPS (latitude/longitude) via l\'API Nominatim (OpenStreetMap). Exemple : /geocode?adresse=22+quai+des+Chartrons,+33000+Bordeaux,France'
	)]
	#[OA\Tag(name: 'Géocodage & Livraison')]
	#[OA\Parameter(name: 'adresse', in: 'query', required: true, description: 'Adresse complète à géocoder', schema: new OA\Schema(type: 'string', example: '22 quai des Chartrons, 33000 Bordeaux, France'))]
	#[OA\Response(
		response: 200,
		description: 'Coordonnées GPS retournées',
		content: new OA\JsonContent(
			properties: [
				new OA\Property(property: 'adresse', type: 'string', example: '22 quai des Chartrons, 33000 Bordeaux, France'),
				new OA\Property(property: 'latitude', type: 'string', example: '44.8562'),
				new OA\Property(property: 'longitude', type: 'string', example: '-0.5709'),
			]
		)
	)]
	#[OA\Response(response: 400, description: 'Aucune adresse fournie')]
	#[OA\Response(response: 404, description: 'Adresse non trouvée')]
	public function geocodeUser(NominatimService $nominatimService, Request $request, SanitizerService $sanitizer): Response
	{
    // On récupère l'adresse depuis l'URL que l'on souhaite géocoder
    $adresse = $request->query->get('adresse');
    if ($adresse) {
    	$adresse = $sanitizer->sanitize($adresse, 'texte');
	  }


    // Étape 1 - Si le service retourne des coordonnées, on les renvoie en JSON
    if (!$adresse) {
      // Si l'adresse n'a pas été trouvée, on renvoie une erreur 400
      return $this->json([
        'error' => 'Aucune adresse fournie'
      ], 400); // Bad request
    }
    // Étape 2 - Si le service retourne des coordonnées, on les renvoie en JSON
    $coords = $nominatimService->geocode($adresse);

    if ($coords) {
      return $this->json([
        'adresse' => $adresse,      // L'adresse originale
        'latitude' => $coords['lat'], // Latitude retournée par Nominatim
        'longitude' => $coords['lon'] // Longitude retournée par Nominatim
      ]);
    } else {
		// Étape 3 - Si l'adresse n'a pas été trouvée, on renvoie une erreur 404
		return $this->json([
			'error' => 'Adresse non trouvée'
		], 404);
		}
	}

	/**
	 * @author      Florian Aizac
	 * @created     08/03/2026
	 * @description  Fonction privée qui calcule la distance entre deux points GPS
	 * en utilisant la formule de Haversine (distance "à vol d'oiseau")
	 * @param $lat1 valeur de la laitude du restaurant 
	 * @param $lon1 valeur de la longitude du restaurant
	 * @param $lat1 valeur de la laitude du client 
	 * @param $lon1 valeur de la longitude du client
	 * @return float : retourne la distance entre les deux adresses
	 * @see https://www.movable-type.co.uk/scripts/latlong.html
	 * Rappel formule Haversine :
	 * Haversine : 	
	 *          a = sin²(Δφ/2) + cos φ1 ⋅ cos φ2 ⋅ sin²(Δλ/2)
	 *          c = 2 ⋅ atan2( √a, √(1−a) )
	 *          d = R ⋅ c
	 * φ is latitude, λ is longitude, R is earth’s radius (mean radius = 6,371km);
	 */
	public static function distanceHaversine($lat1, $lon1, $lat2, $lon2): float
	{
		// Rayon de la Terre en kilomètres (6371 km)
		$R = 6371;
		
		// Conversion des latitudes en radians
		// φ1 = latitude du point 1 en radians
		$phi1 = $lat1 * M_PI / 180;

		// φ2 = latitude du point 2 en radians
		$phi2 = $lat2 * M_PI / 180;

		// Δφ = différence de latitude entre les deux points (en radians)
		$deltaPhi = ($lat2 - $lat1) * M_PI / 180;

		// Δλ = différence de longitude entre les deux points (en radians)
		$deltaLambda = ($lon2 - $lon1) * M_PI / 180;

		// Première partie de la formule de Haversine
		// a = sin²(Δφ/2) + cos(φ1) * cos(φ2) * sin²(Δλ/2)
		$a =
      sin($deltaPhi / 2) * sin($deltaPhi / 2) +
      cos($phi1) * cos($phi2) *
      sin($deltaLambda / 2) * sin($deltaLambda / 2);

		// Angle central entre les deux points
		// c = 2 * atan2( √a , √(1-a) )
		$c = 2 * atan2(sqrt($a), sqrt(1 - $a));

		// Distance finale = rayon de la Terre × angle central
		// Résultat en kilomètres
		$distance = $R * $c;

		// Retourne la distance calculée
		return $distance;
	}

	/**
	 * @author      Florian Aizac
	 * @created     08/03/2026
	 * @description  Fonction permettant de calculer une distance
	 * @param NominatimService $nominatimService : le service de nominatim pour l'utilisation de l'api.
	 * @param Request $request : la requete URL.
	 * @return Json : retourne la réponse json.
	 * Urilisation : http://127.0.0.1:8000/distance?adresse1=...&adresse2=...
	 */
	#[Route('/distance', name: 'distance_between')]
	#[OA\Get(
		summary: 'Distance à vol d\'oiseau entre deux adresses',
		description: 'Calcule la distance en kilomètres entre deux adresses via la formule de Haversine. Exemple : /distance?adresse1=...&adresse2=...'
	)]
	#[OA\Tag(name: 'Géocodage & Livraison')]
	#[OA\Parameter(name: 'adresse1', in: 'query', required: true, description: 'Première adresse', schema: new OA\Schema(type: 'string'))]
	#[OA\Parameter(name: 'adresse2', in: 'query', required: true, description: 'Deuxième adresse', schema: new OA\Schema(type: 'string'))]
	#[OA\Response(
		response: 200,
		description: 'Distance calculée',
		content: new OA\JsonContent(
			properties: [
				new OA\Property(property: 'adresse1', type: 'string'),
				new OA\Property(property: 'adresse2', type: 'string'),
				new OA\Property(property: 'distance_km', type: 'number', example: 5.23),
			]
		)
	)]
	#[OA\Response(response: 400, description: 'Deux adresses requises')]
	#[OA\Response(response: 404, description: 'Adresse non trouvée')]
	public function distance(NominatimService $nominatimService, Request $request, SanitizerService $sanitizer): Response
	{
		// Étape 1 - Récupération de la première adresse passée dans l'URL
		$adresse1 = $request->query->get('adresse1');

		// Étape 2 - Récupération de la deuxième adresse passée dans l'URL
		$adresse2 = $request->query->get('adresse2');

    if ($adresse1) $adresse1 = $sanitizer->sanitize($adresse1, 'texte');
    if ($adresse2) $adresse2 = $sanitizer->sanitize($adresse2, 'texte');
    
    // Étape 3 - Vérifie que les deux adresses sont bien fournies
		if (!$adresse1 || !$adresse2) {
      // Si ce n'est pas le cas, on retourne une erreur HTTP 400
      return $this->json(['error' => 'Veuillez fournir deux adresses'], 400);
		}

		// Étape 4 - Appel du service Nominatim pour convertir l'adresse 1 en coordonnées GPS
		$coords1 = $nominatimService->geocode($adresse1);

		// Étape 5 - Appel du service Nominatim pour convertir l'adresse 2 en coordonnées GPS
		$coords2 = $nominatimService->geocode($adresse2);

		// Étape 6 - Vérifie si une des deux adresses n'a pas pu être géocodée
		if (!$coords1 || !$coords2) {
      // Si une adresse est introuvable, on retourne une erreur
      return $this->json(['error' => 'Adresse non trouvée'], 404);
		}

		// Étape 7 - Calcul de la distance entre les deux coordonnées avec la fonction Haversine
		$distance = $this->distanceHaversine(
  $coords1['lat'], $coords1['lon'], // coordonnées du point 1
  $coords2['lat'], $coords2['lon']  // coordonnées du point 2
		);

		// Étape 8 - Retour de la réponse au format JSON
		return $this->json([
      'adresse1' => $adresse1,  // première adresse
      'adresse2' => $adresse2,  // deuxième adresse
      'distance_km' => $distance // distance calculée en kilomètres
		]);
	}
	/**
	 * @author      Florian Aizac
	 * @created     08/03/2026
	 * @description  Fonction permettant de calculer le coût de livraison
	 * @param NominatimService $nominatimService : le service permettant de géocoder une adresse
	 * @param OsrmService $osrmService  : le service permettant de calculer une distance routière
	 * @param Request $request : la requete URL.
	 * @return Json : retourne la réponse json.
	 * Urilisation : http://127.0.0.1:8000/delivery-cost?adresse=...
	 */
	#[Route('/delivery-cost', name: 'delivery_cost', methods: ['GET'])]
	#[OA\Get(
		summary: 'Calculer les frais de livraison',
		description: 'Calcule les frais de livraison entre le restaurant (22 quai des Chartrons, Bordeaux) et l\'adresse du client. Utilise OSRM pour la distance routière, Haversine en fallback. Gratuit dans un rayon de 50km, sinon 5€ + 0.59€/km.'
	)]
	#[OA\Tag(name: 'Géocodage & Livraison')]
	#[OA\Parameter(name: 'adresse', in: 'query', required: true, description: 'Adresse complète du client', schema: new OA\Schema(type: 'string', example: '12 rue des Roses, 33000 Bordeaux, France'))]
	#[OA\Response(
		response: 200,
		description: 'Frais de livraison calculés',
		content: new OA\JsonContent(
			properties: [
				new OA\Property(property: 'restaurant', type: 'string', example: '22 quai des Chartrons, 33000 Bordeaux, France'),
				new OA\Property(property: 'client_adresse', type: 'string', example: '12 rue des Roses, 33000 Bordeaux, France'),
				new OA\Property(property: 'client_lat', type: 'string', example: '44.8378'),
				new OA\Property(property: 'client_lon', type: 'string', example: '-0.5792'),
				new OA\Property(property: 'distance_km', type: 'number', example: 5.23),
				new OA\Property(property: 'distance_type', type: 'string', example: 'routiere', description: 'routiere (OSRM) ou vol_oiseau (Haversine fallback)'),
				new OA\Property(property: 'rayon_gratuit_km', type: 'integer', example: 50),
				new OA\Property(property: 'livraison_gratuite', type: 'boolean', example: true),
				new OA\Property(property: 'frais_livraison', type: 'number', example: 0),
			]
		)
	)]
	#[OA\Response(response: 400, description: 'Aucune adresse client fournie')]
	#[OA\Response(response: 404, description: 'Adresse client non trouvée')]
	public function deliveryCost(
    NominatimService $nominatimService,
    OsrmService $osrmService,         
    Request $request,
    SanitizerService $sanitizer
	): Response {

    // Étape 1 - Récupération de l'adresse du client dans les paramètres GET
    $clientAddress = $request->query->get('adresse');

    $clientAddress = $request->query->get('adresse');
    if ($clientAddress) {
        $clientAddress = $sanitizer->sanitize($clientAddress, 'texte');
    }

    // Étape 2 - Vérifie que l'adresse du client est fournie
    if (!$clientAddress) {
      // Si elle est absente, on renvoie une erreur
      return $this->json(['error' => 'Aucune adresse client fournie'], 400);
    }

    // Étape 3 - Conversion de l'adresse client en coordonnées GPS via Nominatim
    $clientCoords = $nominatimService->geocode($clientAddress);

    // Étape 4 - Vérifie si l'adresse du client a pu être trouvée
    if (!$clientCoords) {
      return $this->json(['error' => 'Adresse client non trouvée'], 404);
    }

    // Étape 5 - Calcul de la distance routière entre le restaurant et le client grâce au service OSRM
    $distanceKm = $osrmService->getRouteDistance(
      self::RESTAURANT_LON, self::RESTAURANT_LAT, // coordonnées du restaurant
      (float) $clientCoords['lon'], (float) $clientCoords['lat'] // coordonnées du client
    );

    // Étape 6 - Si l'API OSRM ne répond pas, on utilise la formule Haversine
    if ($distanceKm === null) {

      // Étape 7 - On calcule une distance approximative avec Haversine
      $distanceKm = $this->distanceHaversine(
        self::RESTAURANT_LAT, self::RESTAURANT_LON,
        (float) $clientCoords['lat'],
				(float) $clientCoords['lon']
      );

      // Étape 8 - On indique que la distance est une estimation.
      $distanceType = 'vol_oiseau';

    } else {

      // Étape 9 - Sinon la distance est une distance routière réelle
      $distanceType = 'routiere';
    }

		// Étape 10 - Vérifie si la distance dépasse la distance maximale de livraison
		if ($distanceKm > self::MAX_DELIVERY_DISTANCE) {
			return $this->json([
				'error' => 'Adresse trop éloignée pour la livraison',
				'distance_km' => round($distanceKm, 2),
				'max_distance_livraison_km' => self::MAX_DELIVERY_DISTANCE
			], 422);
		}

    // Étape 11 - Calcul du prix de la livraison, si la distance est dans le rayon gratuit la livraison est gratuite
    // 1. Vérifie si la distance est dans le rayon de livraison gratuite
		if ($distanceKm <= self::FREE_DELIVERY_RADIUS_KM) {

			// Livraison gratuite dans le rayon défini
			$deliveryFee = 0.00;

		} else {

			// On calcule uniquement la distance au-delà du rayon gratuit
			$extraDistance = $distanceKm - self::FREE_DELIVERY_RADIUS_KM;

			$deliveryFee = self::DELIVERY_BASE_FEE + (self::DELIVERY_PER_KM_FEE * $extraDistance);
		}

    // Étape 12 - Retour de toutes les informations en JSON
		return $this->json([
			'restaurant' => self::RESTAURANT_ADDRESS, // adresse du restaurant
			'client_adresse' => $clientAddress,				// adresse du client
			'client_lat' => $clientCoords['lat'],			// latitude du client
			'client_lon' => $clientCoords['lon'],			// longitude du client
			'distance_km' => round($distanceKm, 2),	// distance arrondie à 2 décimales
			'distance_type' => $distanceType,												// type de distance (route ou vol d'oiseau)
			'rayon_gratuit_km' => self::FREE_DELIVERY_RADIUS_KM,		// rayon gratuit
			'max_distance_livraison_km' => self::MAX_DELIVERY_DISTANCE,	// valeur max de la distance de livraison
			'livraison_gratuite' => $distanceKm <= self::FREE_DELIVERY_RADIUS_KM,	//livraison gratuite
			'frais_livraison' => round($deliveryFee, 2)		// coût final de livraison
		]);
	}
}