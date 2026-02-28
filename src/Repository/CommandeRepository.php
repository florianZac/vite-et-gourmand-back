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
        // Étape 1      : Créer un QueryBuilder pour construire la requête SQL en utilisant l'alias 'c' pour la table Commande
        // Étape 1.1    : équivalent à "SELECT * FROM commande c"
        // Étape 2      : Ajouter une condition pour filtrer les commandes par utilisateur avec la variable utilisateur passé en parametre
        // Étape 2.1    : équivalent à "WHERE c.utilisateur = :utilisateur
        // Étape 3      : Remplissage des données de la variable utilisateur dans la requete SQL  
        // Étape 3.1    : équivalent à "WHERE commande.utilisateur_id = 3"
        // Étape 4      : Trier les valeurs par ordre décroissant par date décroissante
        // Étape 4.1    : équivalent à "ORDER BY c.date DESC" ASC pour ordre croissant
        // Étape 5      : transforme la requete SQL construite en une requete exécutable
        // Étape 5.1    : équivalent à "EXECUTE SELECT * FROM commande c WHERE c.utilisateur = 3 ORDER BY c.date DESC"
        // Étape 6      : Exécute la requete et retourne les résultats sous forme d'un tableau d'objets Commande
        // Étape 6.1    : équivalent à "GET ALL"
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

    /**
     * @description Recherche dans les commandes si il y as du materiel en court de pret
     * détection immédiate : 
     * Retourne les commandes dont la prestation était hier, où materiel prété n'a pas été rendu 
     * si pretMateriel == False & restitutionMateriel ==
     * sur le champ numero_commande (ex : CMD-001, CMD-002, etc.).
     * @param string $nom Chaîne de caractères à rechercher dans le numéro de commande
     * @return Commande[] correspondant à la recherche
     */
    public function findCommandesMaterielNonRendu(): array
    {
        $yesterday = new \DateTime('yesterday');

        return $this->createQueryBuilder('c')
            ->where('c.pret_materiel = true')
            ->andWhere('c.restitution_materiel = false')
            ->andWhere('c.date_prestation = :yesterday')
            ->setParameter('yesterday', $yesterday->format('Y-m-d'))
            ->getQuery()
            ->getResult();
    }

    /**
     * @description fonction de récupération des commandes à relancer dans le cas d'un pret de matériel qui n'a pas été restitué
     * Liste de suivi continu pour relancer les clients
     * Donne toutes les commandes au statut Livré où le matos n'a pas été rendu, peu importe quand pour envoyer les relances et ou pénalités)
     * Récupère toutes les commandes où :
     * (pret_materiel,restitution_materiel) 
     * Cas (1,0) 
     *  le matériel à été prêté -> pret_materiel == true &&
     *  le matériel n'a pas été restitué -> restitution_materiel = false &&
     *  le statut de la commande est "En attente du retour matériel"
     * Si date passage au statut Livré > 10 jour ouvré l'envoi du mail de pénalité.
     * Les autres cas (0,0), (0,1), (1,1) seront gérés par le listener
     * @return Commande[] tableau d'entités Commande
     * Rappel Workflow complet 
     * Employé change statut → "Livré"
     *  ├── SI pret_materiel == false
     *  │   └── statut reste "Livré" → prochaine étape = "Terminée"
     *  │
     *  └── SI pret_materiel == true
     *      └── statut passe automatiquement à "En attente du retour matériel"
     *          └── mail envoyé au client
     *  Cron nuit
     *  └── SI statut = "En attente du retour matériel" + restitution = false + > 10 jours
     *      └── mail pénalité
     *  Employé/Admin confirme retour + pénalité payée
     *  └── statut passe à "Terminée"
     */
    public function findCommandesMaterielARelancer(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.pret_materiel = true')
            ->andWhere('c.restitution_materiel = false')
            ->andWhere('c.statut = :statut')
            ->setParameter('statut', 'En attente du retour matériel')
            ->getQuery()
            ->getResult();
    }

    /**
     * @description Recherche des commandes avec filtres optionnels par statut et/ou par client
     * Utilisé par l'employé pour filtrer les commandes dans son tableau de bord
     * @param string|null $statut   Le statut à filtrer (ex: "En attente", "Acceptée"...)
     * @param int|null $utilisateurId  L'id du client à filtrer
     * @return array
     */
    public function findByFiltres(?string $statut = null, ?int $utilisateurId = null): array
    {
        
        // Étape 1 - Crée une requête SQL dynamique
        // 'c' est l'alias pour la table commande (comme "SELECT c FROM commande c")
        $qb = $this->createQueryBuilder('c')
            ->orderBy('c.date_commande', 'DESC'); // plus récente en premier

        // Étape 2 - Filtre par statut si fourni
        if ($statut !== null) {
            $qb->andWhere('c.statut = :statut')
            ->setParameter('statut', $statut);
        }

        // Étape 3 - Filtre par client si fourni
        if ($utilisateurId !== null) {
            $qb->andWhere('c.utilisateur = :utilisateurId')
            ->setParameter('utilisateurId', $utilisateurId);
        }

        // Étape 4 - Retourne le résultat
        return $qb->getQuery()->getResult();
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

            // Étape 1.  Récupère la date de la commande et la formate en "mois/année" (ex : 02/2026) afin de regrouper les ventes/mois
            $mois = $commande->getDateCommande()->format('m/Y');

            // Étape 2.  Vérifie si les statistiques pour ce mois n'ont pas encore été initialisées
            if (!isset($ventesParMois[$mois])) {
                // Étape 2.1 Initialisation de la structure de données pour le mois courant
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

            // Étape 3.   Incrémente le nombre total de commandes pour le mois courant
            $ventesParMois[$mois]['total_commandes']++;
            // Étape 4.    Ajoute le prix du menu et le prix de la livraison pour calculer le chiffre d'affaires mensuel
            $ventesParMois[$mois]['chiffre_affaire'] +=
                $commande->getPrixMenu() + $commande->getPrixLivraison();

            // Étape 5.    Vérifie si un menu est bien associé à la commande et si ce menu possède un titre
            if ($commande->getMenu() !== null && $commande->getMenu()->getTitre() !== null) {

                // Si le menu existe et a un titre, on récupère son nom
                $nomMenu = $commande->getMenu()->getTitre();

            } else {

                // Sinon on attribue une valeur par défaut
                $nomMenu = 'Inconnu';
            }

            // Étape 6.   Vérifie si ce menu n'a pas encore été comptabilisé pour ce mois
            if (!isset($ventesParMois[$mois]['menus'][$nomMenu])) {
                // Initialise le compteur du menu à 0
                $ventesParMois[$mois]['menus'][$nomMenu] = 0;
            }
            // Étape 7.    Incrémente le nombre de fois où ce menu a été commandé durant le mois
            $ventesParMois[$mois]['menus'][$nomMenu]++;
        }

        // Étape 8.   Trouve le menu le plus prisé par mois et Parcourt les statistiques de ventes pour chaque mois
        foreach ($ventesParMois as &$moisData) {

            // Étape 8.1 Trie les menus du mois par nombre de commandes décroissant pour récuperer Le menu le plus commandé qui se retrouve en première position
            arsort($moisData['menus']);

            // Étape 8.2 Récupère le nom du menu le plus prisé du mois
            $moisData['menu_plus_prise'] = array_key_first($moisData['menus']);

            // Étape 8.3 Supprime le détail des menus et ne conserver que le menu le plus prisé
            unset($moisData['menus']);
        }
        
        // Étape 9. Création d'une requête Doctrine sur l'entité Commande (alias "c")
        $commandesParMenu = $this->createQueryBuilder('c')

            // Étape 9.1 Sélection des données à récupérer :le titre du menu, le nombre total de commandes pour ce menu, le CA généré par ce menu (menu + livraison)
            ->select(
                'm.titre AS menu',
                'COUNT(c.id) AS nombre_commandes',
                'SUM(c.prix_menu + c.prix_livraison) AS chiffre_affaires'
            )

            // Étape 9.2 Jointure avec l'entité Menu liée à la commande
            // c.menu correspond à la relation ManyToOne dans l'entité Commande
            ->join('c.menu', 'm')

            // Étape 9.3 Exclut les commandes Annulées du calcul
            ->where('c.statut != :statut')
            // Étape 9.4 Met à jour les Valeur du paramètre : statut "Annulée"
            ->setParameter('statut', 'Annulée')
            // Étape 9.5 Regroupe les résultats par menu
            // Indispensable pour utiliser COUNT et SUM correctement
            ->groupBy('m.id')
            // Étape 9.6 Trie les menus par nombre de commandes décroissant
            // Le menu le plus commandé apparaîtra en premier
            ->orderBy('nombre_commandes', 'DESC')
            // Étape 9.7  Génère la requête Doctrine
            ->getQuery()
            // Étape 9.8  Exécute la requête et retourne le résultat sous forme de tableau
            ->getResult();

        // Étape 10.  Formatage pour le chart
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
