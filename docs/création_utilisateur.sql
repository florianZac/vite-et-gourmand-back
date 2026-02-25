USE vite_et_gourmand;


INSERT INTO `utilisateur` (`nom`, `prenom`, `telephone`, `email`, `password`, `pays`, `ville`, `adresse_postale`, `code_postale`, `role_id`) 
VALUES(
        'admin',
        'admin',
        '0123456789',
        'admin@vite-et-gourmand.fr', 
        '$2y$13$x8x9WNIvAdUonbLgzVu2Q.qc92sJfN2Oag/8uGMJUE.FADEfncf1G',
        'France', 
        'Montpellier', 
        'France', 
        '123 Rue de la Gourmandise',
        '34400',
        '7'
    );

--creation d'un mdp hacher php bin/console security:hash-password est le code test 123456 


INSERT INTO `role` (`libelle`) VALUES
('ROLE_VISITEUR'),
('ROLE_CLIENT'),
('ROLE_EMPLOYE'),
('ROLE_ADMIN');



-- Insertion des données pour la table suivi_commande pour l'historique des commandes
-- STR_TO_DATE permet de modifier le format de la date de YYYY-MM-DD à DD-MM-YYYY
INSERT INTO suivi_commande (statut, date_statut, commande_id)
VALUES 
('En attente',     STR_TO_DATE('20-02-2026 10:00:00', '%d-%m-%Y %H:%i:%s'), 2),
('Acceptée',       STR_TO_DATE('21-02-2026 14:30:00', '%d-%m-%Y %H:%i:%s'), 2),
('En préparation', STR_TO_DATE('22-02-2026 09:00:00', '%d-%m-%Y %H:%i:%s'), 2),
('En livraison',   STR_TO_DATE('23-02-2026 12:00:00', '%d-%m-%Y %H:%i:%s'), 2),
('Terminée',       STR_TO_DATE('24-02-2026 12:00:00', '%d-%m-%Y %H:%i:%s'), 2);


-- Commandes terminées pour les testée les statistiques
INSERT INTO commande (numero_commande, date_commande, date_prestation, heure_livraison, prix_menu, nombre_personne, prix_livraison, statut, pret_materiel, restitution_materiel, utilisateur_id, menu_id)
VALUES 
('CMD-001', STR_TO_DATE('01-01-2026 10:00:00', '%d-%m-%Y %H:%i:%s'), STR_TO_DATE('15-01-2026', '%d-%m-%Y'), '12:00:00', 150.00, 10, 20.00, 'Terminée', 1, 1, 6, 1),
('CMD-002', STR_TO_DATE('05-01-2026 10:00:00', '%d-%m-%Y %H:%i:%s'), STR_TO_DATE('20-01-2026', '%d-%m-%Y'), '12:00:00', 200.00, 15, 25.00, 'Terminée', 1, 1, 6, 1),
('CMD-003', STR_TO_DATE('10-02-2026 10:00:00', '%d-%m-%Y %H:%i:%s'), STR_TO_DATE('25-02-2026', '%d-%m-%Y'), '12:00:00', 180.00, 12, 20.00, 'Terminée', 1, 1, 6, 1),
('CMD-004', STR_TO_DATE('15-02-2026 10:00:00', '%d-%m-%Y %H:%i:%s'), STR_TO_DATE('28-02-2026', '%d-%m-%Y'), '12:00:00', 250.00, 20, 30.00, 'Terminée', 1, 1, 6, 1);