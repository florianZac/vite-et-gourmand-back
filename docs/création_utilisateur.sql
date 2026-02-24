USE vite_et_gourmand;


INSERT INTO `utilisateur` (`email`, `password`, `prenom`, `telephone`, `ville`, `pays`, `adresse_postale`, `role_id`) 
VALUES(
        'admin@vite-et-gourmand.fr', 
        '$2y$13$x8x9WNIvAdUonbLgzVu2Q.qc92sJfN2Oag/8uGMJUE.FADEfncf1G',
        'Admin', 
        '0123456789', 
        'Montpellier', 
        'France', 
        '123 Rue de la Gourmandise',
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