USE vite_et_gourmand;

--insertion de données dans la table regime

INSERT INTO regime (libelle) VALUES
('Tous'),
('Classique'),
('Végétalien'),
('Vegan'),
('Carnivore');

--insertion de données dans la table allergene

INSERT INTO allergene (libelle) VALUES
('Gluten'),
('Lactose'),
('Fruits à coque'),
('Oeufs'),
('Poissons'),
('Crustacés'),
('Mollusques'),
('Sésame'),
('Soja'),
('Sulfites');

--insertion de données dans la table theme

INSERT INTO theme (libelle) VALUES
('Tous'),
('Italien'),
('Asiatique'),
('Mexicain'),
('Français'),
('Noêl'),
('Pâques'),
("Jour de l'an"),
('événement ');

--insertion de données dans la table menu

INSERT INTO menu 
    (titre, nombre_personne_minimum, prix_par_personne, description, quantite_restante, regime_id, theme_id)

VALUES 
    (
        'Festin de Noël Traditionnel',
        10, 
        45.00, 
        'Un menu raffiné aux saveurs de Gascogne pour célébrer les fêtes de fin d''année dans la pure tradition du Sud-Ouest.', 
        50, 
        1, 
        6
    ),
    (
        'Réveillon Étoilé de Noël',
        8, 
        40.00, 
        'Un repas de Noël gastronomique digne des plus grandes tables, avec des produits d''exception sélectionnés par nos chefs.', 
        30, 
        4, 
        6
    ),
    (
        'Réveillon de Noël Classique',
        12, 
        50.00, 
        'Un menu traditionnel et généreux pour un réveillon de Noël inoubliable, inspiré des recettes classiques de la Gascogne.', 
        40, 
        2, 
        6
    );

--insertion de données dans la table horaire
INSERT INTO horaire (jour, heure_ouverture, heure_fermeture) VALUES
('Lundi', '09:00:00', '18:00:00'),
('Mardi', '09:00:00', '18:00:00'),
('Mercredi', '09:00:00', '18:00:00'),
('Jeudi', '09:00:00', '18:00:00'),
('Vendredi', '09:00:00', '18:00:00'),
('Samedi', '10:00:00', '16:00:00'),
('Dimanche', NULL, NULL);


-- Test de compréhension des requetes SQL

-- Suppression du thème "événement" à des fins de test
DELETE FROM theme WHERE theme_id = 9;

-- supression du régime "Carnivore" à des fins de test
DELETE FROM regime WHERE regime_id = 5;

-- recherche d'un element dans la table theme pour tester et afficher le résultat
SELECT * FROM theme WHERE libelle = 'Tous';

-- recherche et suppresion d'un element si présent plussieurs fois dans la table theme pour tester
DELETE FROM theme WHERE libelle = "Jour de l'an" LIMIT 1;

-- recherche d'un élément pointé par son nom s'il existe ne rien faire sinon l'insérer dans la table theme pour tester
INSERT INTO theme (libelle)
SELECT "Jour de l'an"
WHERE NOT EXISTS (
    SELECT 1 FROM theme WHERE libelle = "Jour de l'an"
);
-- retour à l'insertion pas le temps de niaiser

