<?php

namespace App\Repository;

use App\Entity\Commande;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commande>
 */
class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }

    // =========================================================================
    // UTILISATEUR
    // =========================================================================

    /**
     * @author Florian Aizac
     * @created 24/02/2026
     * @description fonction personalisé responsable des requêtes SQL sur la table commande.
     *  il  récupère toutes les données d'un utilisateur triées par date décroissante.
     * @param Utilisateur $utilisateur L'utilisateur dont on veut récupérer les commandes
     * @return Commande[] Tableau d'objets Commande
    */
    public function findByUtilisateur($utilisateur): array
    {
        // 1. : Créer un QueryBuilder pour construire la requête SQL en utilisant l'alias 'c' pour la table Commande
        // 1.1:  équivalent à "SELECT * FROM commande c"
        // 2. : Ajouter une condition pour filtrer les commandes par utilisateur avec la variable utilisateur passé en parametre
        // 2.1: équivalent à "WHERE c.utilisateur = :utilisateur
        // 3  : Remplissage des données de la variable utilisateur dans la requete SQL  
        // 3.1: équivalent à "WHERE commande.utilisateur_id = 3"
        // 4 : Trier les valeurs par ordre décroissant par date décroissante
        // 4.1: équivalent à "ORDER BY c.date DESC" ASC pour ordre croissant
        // 5 : transforme la requete SQL construite en une requete exécutable
        // 5.1 : équivalent à "EXECUTE SELECT * FROM commande c WHERE c.utilisateur = 3 ORDER BY c.date DESC"
        // 6 : Exécute la requete et retourne les résultats sous forme d'un tableau d'objets Commande
        // 6.1 : équivalent à "GET ALL"
        return $this->createQueryBuilder('c')
            ->andWhere('c.utilisateur = :utilisateur')
            ->setParameter('utilisateur', $utilisateur)
            ->orderBy('c.date_commande', 'DESC')
            ->getQuery()
            ->getResult();
    }
    
    // =========================================================================
    // COMMANDE
    // =========================================================================

    /**
     * @description Retourne les commandes en cours (tout statut sauf Terminée et Annulée)
     * Sont exclues :
     *  - les commandes avec le statut == "Terminée"
     *  - les commandes avec le statut == "Annulée"
     *
     * Les commandes sont triées par date de commande les plus récentes en premier
     * @return Commande[]
     */
    public function findCommandesEnCours(): array
    {
        return $this->createQueryBuilder('c')
            // Exclut les commandes terminées ou Annulées
            ->where('c.statut NOT IN (:statut)')
            ->setParameter('statut', ['Terminée', 'Annulée'])
            // Trie par date de commande décroissante
            ->orderBy('c.date_commande', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @description Recherche des commandes par numéro de commande.
     * sur le champ numero_commande (ex : CMD-001, CMD-002, etc.).
     * @param string $nom Chaîne de caractères à rechercher dans le numéro de commande
     * @return Commande[] correspondant à la recherche
     */
    public function findByNumeroCommande(string $nom): array
    {
        return $this->createQueryBuilder('c')
            // Recherche partielle sur le numéro de commande
            ->where('c.numero_commande LIKE :nom')
            ->setParameter('nom', '%' . $nom . '%')
            ->getQuery()
            ->getResult();
    }

    // =========================================================================
    // STATISTIQUE POUR LE DOUBLE CHART BAR JS EN FRONT 
    // =========================================================================

    /**
     * @description Fonction de récupération des statistique pour l'affiche avec Chart.js en front
     * @return array les données
     * reponse JSON devrait etre 
     * {
     *   "total_commandes": 150,
     *   "chiffre_affaires_total": 45000.00,
     *           "revenu_moyen_commande": 300.00,
     *   "montant_rembourse_total": 1200.00,
     *   "repartition_statuts": [...],
     *   "chart_commandes_ca_par_menu": [
     *   { "menu": "Classique Bordelais", "nombre_commandes": 45, "chiffre_affaires": 21500.00 },
     *   ...
     *    ],
     *    "top_menus": [...],
     *    "utilisateurs": { "total": 80, "actifs": 72, ... },
     *    "avis": { "total": 30, "en_attente": 5, ... }
     *   }
     */
    public function getStatistiques(): array
    {
        // =====================================================
        // Récupération de toutes les commandes non Annulées
        // =====================================================
        $commandes = $this->createQueryBuilder('c')
            ->where('c.statut != :statut')
            ->setParameter('statut', 'Annulée')
            ->getQuery()
            ->getResult();

        // =====================================================
        // Nombre total de commandes
        // =====================================================
        $totalCommandes = count($commandes);

        // =====================================================
        // Chiffre d'affaires total (commandes non Annulées)
        // =====================================================
        $chiffreAffairesTotal = 0;
        foreach ($commandes as $commande) {
            $chiffreAffairesTotal +=
                $commande->getPrixMenu() + $commande->getPrixLivraison();
        }

        // =====================================================
        // Revenu moyen par commande
        // =====================================================
        if ($totalCommandes > 0) {
            $revenuMoyen = round($chiffreAffairesTotal / $totalCommandes, 2);
        } else {
            $revenuMoyen = 0;
        }

        // =====================================================
        // Montant total remboursé pour les commandes Annulées
        // =====================================================
        $montantRembourseTotal = $this->createQueryBuilder('c')
            ->select('COALESCE(SUM(c.montant_rembourse), 0)')
            ->where('c.statut = :statut')
            ->setParameter('statut', 'Annulée')
            ->getQuery()
            ->getSingleScalarResult();

        // =====================================================
        // Répartition par statut
        // =====================================================
        $repartitionStatuts = $this->createQueryBuilder('c')
            ->select('c.statut, COUNT(c.id) AS nombre')
            ->groupBy('c.statut')
            ->getQuery()
            ->getResult();
        
        // =====================================================
        // Ventes par mois
        // =====================================================

        $ventesParMois = []; // tableau qui contiendra les stat de ventes par mois

        // Parcours de toutes les commandes récupérées depuis la base de données
        foreach ($commandes as $commande) {

            // Récupère la date de la commande et la formate en "mois/année" (ex : 02/2026) afin de regrouper les ventes/mois
            $mois = $commande->getDateCommande()->format('m/Y');

            // Vérifie si les statistiques pour ce mois n'ont pas encore été initialisées
            if (!isset($ventesParMois[$mois])) {
                // Initialisation de la structure de données pour le mois courant
                $ventesParMois[$mois] = [
                    // Mois concerné (ex : 02/2026)
                    'mois'            => $mois,
                    // Nombre total de commandes passées durant ce mois
                    'total_commandes' => 0,
                    // Chiffre d'affaires total du mois (menus + livraisons)
                    'chiffre_affaire' => 0,
                    // Tableau qui contiendra le nombre de commandes par menu
                    'menus'           => []
                ];
            }

            // Incrémente le nombre total de commandes pour le mois courant
            $ventesParMois[$mois]['total_commandes']++;
            // Ajoute le prix du menu et le prix de la livraison pour calculer le chiffre d'affaires mensuel
            $ventesParMois[$mois]['chiffre_affaire'] +=
                $commande->getPrixMenu() + $commande->getPrixLivraison();

            // Vérifie si un menu est bien associé à la commande et si ce menu possède un titre
            if ($commande->getMenu() !== null && $commande->getMenu()->getTitre() !== null) {

                // Si le menu existe et a un titre, on récupère son nom
                $nomMenu = $commande->getMenu()->getTitre();

            } else {

                // Sinon on attribue une valeur par défaut
                $nomMenu = 'Inconnu';
            }

            // Vérifie si ce menu n'a pas encore été comptabilisé pour ce mois
            if (!isset($ventesParMois[$mois]['menus'][$nomMenu])) {
                // Initialise le compteur du menu à 0
                $ventesParMois[$mois]['menus'][$nomMenu] = 0;
            }
            // Incrémente le nombre de fois où ce menu a été commandé durant le mois
            $ventesParMois[$mois]['menus'][$nomMenu]++;
        }

        // Trouve le menu le plus prisé par mois

        // Parcourt les statistiques de ventes pour chaque mois
        foreach ($ventesParMois as &$moisData) {

            // Trie les menus du mois par nombre de commandes décroissant pour récuperer Le menu le plus commandé qui se retrouve en première position
            arsort($moisData['menus']);

            // Récupère le nom du menu le plus prisé du mois
            $moisData['menu_plus_prise'] = array_key_first($moisData['menus']);

            // Supprime le détail des menus et ne conserver que le menu le plus prisé
            unset($moisData['menus']);
        }
        
        // Création d'une requête Doctrine sur l'entité Commande (alias "c")
        $commandesParMenu = $this->createQueryBuilder('c')

            // Sélection des données à récupérer :le titre du menu, le nombre total de commandes pour ce menu, le CA généré par ce menu (menu + livraison)
            ->select(
                'm.titre AS menu',
                'COUNT(c.id) AS nombre_commandes',
                'SUM(c.prix_menu + c.prix_livraison) AS chiffre_affaires'
            )

            // Jointure avec l'entité Menu liée à la commande
            // c.menu correspond à la relation ManyToOne dans l'entité Commande
            ->join('c.menu', 'm')

            // Exclut les commandes Annulées du calcul
            ->where('c.statut != :statut')
            // Valeur du paramètre : statut "Annulée"
            ->setParameter('statut', 'Annulée')
            // Regroupe les résultats par menu
            // Indispensable pour utiliser COUNT et SUM correctement
            ->groupBy('m.id')
            // Trie les menus par nombre de commandes décroissant
            // Le menu le plus commandé apparaîtra en premier
            ->orderBy('nombre_commandes', 'DESC')
            // Génère la requête Doctrine
            ->getQuery()
            // Exécute la requête et retourne le résultat sous forme de tableau
            ->getResult();

        // Formatage pour le chart
        $chartData = array_map(function ($item) {
            return [
                'menu'              => $item['menu'],
                'nombre_commandes'  => (int) $item['nombre_commandes'],
                'chiffre_affaires'  => round((float) $item['chiffre_affaires'], 2),
            ];
        }, $commandesParMenu);

        // =====================================================================
        // Top 3 menus les plus commandés
        // =====================================================================
        $topMenus = array_slice($chartData, 0, 3);

        // =====================================================================
        // RÉSULTAT FINAL
        // =====================================================================
        return [
            // Vue globale
            'total_commandes'        => (int) $totalCommandes,
            'chiffre_affaires_total' => round((float) $chiffreAffairesTotal, 2),
            'revenu_moyen_commande'  => $revenuMoyen,
            'montant_rembourse_total'=> round((float) $montantRembourseTotal, 2),
            
            'ventes_par_mois' => array_values($ventesParMois),
            // Répartition par statut
            'repartition_statuts'    => $repartitionStatuts,

            // Données pour le chart bar commandes & CA par menu
            'chart_commandes_ca_par_menu' => $chartData,

            // Top 3 menus
            'top_menus'              => $topMenus,
        ];
    }


    //    /**
    //     * @return Commande[] Returns an array of Commande objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Commande
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
