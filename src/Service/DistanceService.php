<?php
namespace App\Service;

class DistanceService {
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
}