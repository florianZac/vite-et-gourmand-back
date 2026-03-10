<?php

namespace App\Service; // Namespace du service dans Symfony
/**
 * @author      Florian Aizac
 * @created     23/02/2026
 * @description Service communiquer avec l'API Nominatim.
 * Son but d'implantation et de retourner la longitude et la latitude d'une adresse données 
 * Retour attendue :
 * {
 *   "adresse": "22 quai des Chartrons, 33000 Bordeaux, France",
 *   "latitude": "43.5242831",
 *   "longitude": "1.3564827"
 * }
 * Le Service NominatimService.php Nominatim : encapsule la logique pour géocoder une adresse.
 * Le Contrôleur GeocodeController.php Symfony : appelle le service et renvoie un JSON.
 * l'Autowiring : Symfony injecte automatiquement le service dans le contrôleur.
 * User-Agent : obligatoire pour Nominatim. permet de ciblé l'utilisateur de l'api.
 */

// Service pour communiquer avec l'API Nominatim
class NominatimService
{
	private string $userAgent; // On stocke l'identifiant de l'application Nominatim

	// Constructeur du service, permet de définir un User-Agent personnalisé
	public function __construct(string $userAgent = 'MySymfonyApp/1.0')
	{
			$this->userAgent = $userAgent; // On initialise la propriété
	}

	/**
	 * Géocode une adresse et retourne latitude et longitude
	 * @param string $adresse L'adresse à géocoder
	 * @return array|null Retourne ['lat' => ..., 'lon' => ...] ou null si non trouvée
	 */
	public function geocode(string $adresse): ?array
	{
		// URL de base pour l'API Nominatim
		$url = 'https://nominatim.openstreetmap.org/search';

		// Paramètres de la requête HTTP
		$params = http_build_query([
			'q' => $adresse,       // L'adresse à rechercher
			'format' => 'json',    // On veut le résultat au format JSON
			'addressdetails' => 1, // Fournit des détails sur l'adresse
			'limit' => 1           // Limite la réponse au résultat le plus pertinent
		]);

		// Initialisation pour envoyer la requête HTTP
		$ch = curl_init();

		// Définir l'URL complète avec paramètres GET
		curl_setopt($ch, CURLOPT_URL, $url . '?' . $params);

		// On eécupére la réponse dans une variable
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		// User-Agent obligatoire pour Nominatim, sinon la requête peut être rejetée
		curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);

		// Exécute la requête et stockage de la réponse JSON
		$response = curl_exec($ch);

		// Fermeture de la connection HTTP
		curl_close($ch);

		// Conversion du JSON en tableau PHP
		$data = json_decode($response, true);

		// Si on a trouvé  un résultat, on retourne latitude et longitude
		// Sinon on retourne null 
		if (!empty($data)) {
			return [
				'lat' => $data[0]['lat'], // Latitude
				'lon' => $data[0]['lon']  // Longitude
			];
		}
		else{
			return null;  
		} 
		
	}
}