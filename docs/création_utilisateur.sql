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

