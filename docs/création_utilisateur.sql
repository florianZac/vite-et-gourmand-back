USE vite_et_gourmand;
-- retour à l'insertion pas le temps de niaiser

-- NE PAS OUBLIER LORDRE D'INSERTION 
-- 1. Rôles
-- 2. Utilisateurs
-- 3. Régimes
-- 4. Allergènes
-- 5. Thèmes
-- 6. Plats
-- 7. Contient
-- 8. Menus
-- 9. Propose
-- 10. Horaires
-- 11. Commandes
-- 12. Avis
-- 13. Suivi_commande
-- 14. menu_tags

-- =====================================
-- ROLE
-- =====================================
INSERT INTO role (libelle) VALUES
('ROLE_CLIENT'),
('ROLE_EMPLOYE'),
('ROLE_ADMIN');

-- =====================================
-- UTILISATEUR
-- =====================================
INSERT INTO utilisateur 
(nom, prenom, telephone, email, password, pays, ville, adresse_postale, code_postal, statut_compte, role_id)
VALUES
('admin', 'admin', '0123456789', 'admin@vite-et-gourmand.fr', '$2y$13$x8x9WNIvAdUonbLgzVu2Q.qc92sJfN2Oag/8uGMJUE.FADEfncf1G','France', 'Montpellier', '123 Rue de la Gourmandise', '34400', 'actif', 4),
('employe', 'employe', '0600000001', 'employe@vite-et-gourmand.fr', '$2y$13$x8x9WNIvAdUonbLgzVu2Q.qc92sJfN2Oag/8uGMJUE.FADEfncf1G', 'France', 'Montpellier', '1 rue de la Fougasse', '34000', 'actif', 3),
('client', 'client', '0688888888', 'client@gmail.com', '$2y$13$x8x9WNIvAdUonbLgzVu2Q.qc92sJfN2Oag/8uGMJUE.FADEfncf1G', 'France', 'LHERM', '1222', '33000', 'actif', 2),
('Dupont', 'Jean', '0601020304', 'jean.dupont@email.com', '$2y$13$x8x9WNIvAdUonbLgzVu2Q.qc92sJfN2Oag/8uGMJUE.FADEfncf1G', 'France', 'Paris', '1 rue Exemple', '75001', 'Actif', 2),
('Martin', 'Claire', '0605060708', 'claire.martin@email.com', '$2y$13$x8x9WNIvAdUonbLgzVu2Q.qc92sJfN2Oag/8uGMJUE.FADEfncf1G', 'France', 'Lyon', '2 rue Exemple', '69001', 'Actif', 2),
('Durand', 'Paul', '0608091011', 'paul.durand@email.com', '$2y$13$x8x9WNIvAdUonbLgzVu2Q.qc92sJfN2Oag/8uGMJUE.FADEfncf1G', 'France', 'Marseille', '3 rue Exemple', '13001', 'Actif', 2),
('Bernard', 'Sophie', '0612131415', 'sophie.bernard@email.com', '$2y$13$x8x9WNIvAdUonbLgzVu2Q.qc92sJfN2Oag/8uGMJUE.FADEfncf1G', 'France', 'Toulouse', '4 rue Exemple', '31000', 'Actif', 2),
('Moreau', 'Luc', '0616171819', 'luc.moreau@email.com', '$2y$13$x8x9WNIvAdUonbLgzVu2Q.qc92sJfN2Oag/8uGMJUE.FADEfncf1G', 'France', 'Nice', '5 rue Exemple', '06000', 'Actif', 2),
('Lefevre', 'Emma', '0620212223', 'emma.lefevre@email.com', '$2y$13$x8x9WNIvAdUonbLgzVu2Q.qc92sJfN2Oag/8uGMJUE.FADEfncf1G', 'France', 'Bordeaux', '6 rue Exemple', '33000', 'Actif', 2);


--creation d'un mdp hacher php bin/console security:hash-password est le code test 123456 

-- =====================================
-- REGIMES
-- =====================================
INSERT INTO regime (regime_id, libelle) VALUES
(1, 'Classique'),
(2, 'Végétalien'),
(3, 'Vegan'),
(4, 'Carnivore');

-- =====================================
-- ALLERGENES
-- =====================================
INSERT INTO allergene (allergene_id, libelle) VALUES
(1, 'Gluten'),
(2, 'Lactose'),
(3, 'Fruits à coque'),
(4, 'Oeufs'),
(5, 'Poissons'),
(6, 'Crustacés'),
(7, 'Mollusques'),
(8, 'Sésame'),
(9, 'Soja'),
(10, 'Sulfites'),
(11, 'viande'),
(12, 'Arachides'),
(13, 'Lait'),
(14, 'noisettes'),
(15, 'Céleri'),
(16, 'Moutarde'),
(17, 'Lupin'),
(18, 'Champignons');

-- =====================================
-- THEMES
-- =====================================
INSERT INTO theme (theme_id, libelle) VALUES
(1, 'Noël'),
(2, 'Classique'),
(3, 'Asiatique'),
(4, 'Mexicain'),
(5, 'Français'),
(6, 'Italien'),
(7, 'Pâques'),
(8, 'Jour de l''an'),
(9, 'Événement'),
(10, 'Solo');


-- =====================================
-- PLATS
-- =====================================
INSERT INTO plat (plat_id, titre_plat, photo, categorie) VALUES
(1, 'Velouté de châtaigne et foie gras poêlé', 'veloute_chataigne.jpg', 'Entrée'),
(2, 'Magret de canard aux cèpes', 'magret_cepes.jpg', 'Plat'),
(3, 'Bûche à l''Armagnac et marrons glacés', 'buche_armagnac.jpg', 'Dessert'),
(4, 'Saumon fumé maison, blinis et crème citronnée', 'saumon_fume.jpg', 'Entrée'),
(5, 'Dinde de Noël farcie aux marrons et foie gras', 'dinde_noel.jpg', 'Plat'),
(6, 'Bûche Montblanc revisitée', 'buche_montblanc.jpg', 'Dessert'),
(7, 'Verrines de crevettes et avocat', 'verrine_crevette.jpg', 'Entrée'),
(8, 'Chapon rôti aux légumes d''hiver', 'chapon_roti.jpg', 'Plat'),
(9, 'Charlotte aux fraises des bois', 'charlotte_fraise.jpg', 'Dessert'),
(10, 'Soupe de courge butternut épicée', 'soupe_butternut.jpg', 'Entrée'),
(11, 'Wellington de légumes d''hiver', 'wellington_legumes.jpg', 'Plat'),
(12, 'Mousse au chocolat noir et orange confite', 'mousse_chocolat_orange.jpg', 'Dessert'),
(13, 'Foie gras d''Alsace au Gewurztraminer', 'foie_gras_alsace.jpg', 'Entrée'),
(14, 'Choucroute festive aux trois viandes', 'choucroute.jpg', 'Plat'),
(15, 'Bredele et strudel aux pommes', 'bredele_strudel.jpg', 'Dessert'),
(16, 'Œuf en cocotte aux truffes noires', 'oeuf_truffe.jpg', 'Entrée'),
(17, 'Confit de canard maison, sarladaise', 'confit_canard.jpg', 'Plat'),
(18, 'Fondant au chocolat et glace truffe', 'fondant_chocolat.jpg', 'Dessert'),
(19, 'Velouté de panais aux épices de Noël', 'veloute_panais.jpg', 'Entrée'),
(20, 'Rôti de seitan aux herbes et légumes confits', 'seitan_roti.jpg', 'Plat'),
(21, 'Bûche vegan chocolat-noisette', 'buche_vegan.jpg', 'Dessert'),
(22, 'Royale de caviar et homard en gelée', 'caviar_homard.jpg', 'Entrée'),
(23, 'Filet de bœuf Rossini, sauce Périgueux', 'boeuf_rossini.jpg', 'Plat'),
(24, 'Bûche Ispahan framboise rose litchi', 'buche_ispahan.jpg', 'Dessert'),
(25, 'Asperges blanches sauce mousseline', 'asperges_mousseline.jpg', 'Entrée'),
(26, 'Gigot d''agneau de lait rôti aux herbes', 'gigot_agneau.jpg', 'Plat'),
(27, 'Tarte citron meringuée', 'tarte_citron.jpg', 'Dessert'),
(28, 'Tartare de saumon citron-aneth', 'tartare_saumon.jpg', 'Entrée'),
(29, 'Carré d''agneau en croûte de pistaches', 'agneau_pistache.jpg', 'Plat'),
(30, 'Œufs en chocolat surprise', 'oeufs_chocolat.jpg', 'Dessert'),
(31, 'Salade d''asperges et chèvre frais', 'salade_asperges.jpg', 'Entrée'),
(32, 'Tarte tatin aux légumes printaniers', 'tatin_legumes.jpg', 'Plat'),
(33, 'Nid de Pâques en meringue', 'nid_paques.jpg', 'Dessert'),
(34, 'Anchoïade et légumes crus', 'anchoiade.jpg', 'Entrée'),
(35, 'Épaule d''agneau confite 7 heures', 'agneau_confite.jpg', 'Plat'),
(36, 'Calisson glacé au miel de lavande', 'calisson_glace.jpg', 'Dessert'),
(37, 'Gaspacho vert aux herbes fraîches', 'gaspacho_vert.jpg', 'Entrée'),
(38, 'Lentilles du Puy aux légumes racines', 'lentilles_puy.jpg', 'Plat'),
(39, 'Fondant au chocolat noir vegan', 'fondant_vegan.jpg', 'Dessert'),
(40, 'Terrine de campagne printanière', 'terrine_campagne.jpg', 'Entrée'),
(41, 'Blanquette de veau à l''ancienne', 'blanquette_veau.jpg', 'Plat'),
(42, 'Fraisier classique', 'fraisier.jpg', 'Dessert'),
(43, 'Piperade basque et jambon de Bayonne', 'piperade.jpg', 'Entrée'),
(44, 'Agneau du Pays Basque rôti', 'agneau_basque.jpg', 'Plat'),
(45, 'Gâteau basque à la cerise noire', 'gateau_basque.jpg', 'Dessert'),
(46, 'Nachos au fromage et guacamole', 'nachos.jpg', 'Entrée'),
(47, 'Tacos de poulet épicé', 'tacos_poulet.jpg', 'Plat'),
(48, 'Churros au chocolat', 'churros.jpg', 'Dessert'),
(49, 'Rouleaux de printemps aux crevettes', 'rouleaux_printemps.jpg', 'Entrée'),
(50, 'Poulet au curry thaï et riz parfumé', 'poulet_curry.jpg', 'Plat'),
(51, 'Perles de coco', 'perles_coco.jpg', 'Dessert'),
(52, 'Velouté de potiron et paprika', 'veloute_paprika.jpg', 'Entrée'),
(53, 'Filet mignon sauce truffe', 'filet_mignon.jpg', 'Plat'),
(54, 'Tartelette au chocolat et éclats de caramel', 'tartelettes_choco.jpg', 'Dessert'),
(55, 'Antipasti variés', 'antipasti.jpg', 'Entrée'),
(56, 'Risotto aux champignons sauvages', 'risotto.jpg', 'Plat'),
(57, 'Tiramisu classique', 'tiramisu.jpg', 'Dessert'),
(58, 'Soupe à l’oignon gratinée', 'soupe_oignon.jpg', 'Entrée'),
(59, 'Boeuf Bourguignon', 'boeuf_bourguignon.jpg', 'Plat'),
(60, 'Crème brûlée', 'creme_brulee.jpg', 'Dessert'),
(61, 'Saumon mariné à l’aneth', 'saumon_aneth.jpg', 'Entrée'),
(62, 'Poulet rôti à la provençale', 'poulet_provencale.jpg', 'Plat'),
(63, 'Clafoutis aux cerises', 'clafoutis.jpg', 'Dessert'),
(64, 'Soupe de légumes de saison', 'soupe_legumes.jpg', 'Entrée'),
(65, 'Gratin dauphinois et rôti de veau', 'gratin_veau.jpg', 'Plat'),
(66, 'Tarte aux pommes classique', 'tarte_pommes.jpg', 'Dessert');


-- =====================================
-- CONTIENT (Plats <-> Allergènes)
-- =====================================
INSERT INTO contient (plat_id, allergene_id) VALUES
(1, 3), -- Foie gras <-> Fruits à coque
(2, 5), -- Saumon fumé <-> Poissons
(4, 2), -- Bûche chocolat <-> Lactose
(4, 4), -- Bûche chocolat <-> Oeufs
(1, 2),
(1, 4),
(2, 18),
(2, 11),
(3, 12),
(3, 14),
(4, 5), 
(4, 6), 
(4, 8),
(4, 9), 
(5, 4),
(5, 1),
(6, 13), 
(7, 8),
(7, 6),
(7, 4),
(8, 11), 
(8, 15),
(8, 16),
(9, 4),
(9, 17),
(9, 1),
(10, 1), 
(10, 13),
(11, 14),
(11, 2),
(11, 15),
(12, 4), 
(12, 1), 
(13, 18),
(13, 3),
(14, 11), 
(15, 4),
(15, 13),
(15, 14),
(16, 4), 
(16, 18),
(17, 11),
(17, 4),
(18, 4), 
(18, 1), 
(19, 14),
(19, 18),
(20, 11), 
(20, 2),
(21, 13),
(21, 14),
(22, 7), 
(22, 3), 
(22, 6), 
(23, 11),
(24, 4),
(24, 2), 
(25, 16),
(25, 2),
(26, 11), 
(26, 12), 
(27, 13),
(27, 10),
(27, 1),
(28, 6),
(28, 5),
(29, 11), 
(29, 3),
(30, 4), 
(30, 17),
(31, 14),
(32, 18),
(33, 4), 
(33, 1), 
(34, 18),
(34, 12),
(35, 11), 
(35, 3),
(36, 14),
(37, 4), 
(37, 8),
(38, 17),
(38, 12),
(39, 4), 
(40, 18),
(40, 14), 
(41, 11),
(41, 18), 
(41, 2),
(42, 4), 
(42, 1),
(42, 13), 
(43, 11),
(43, 16), 
(44, 11),
(45, 4), 
(45, 1)
-- Mexicain
(46,1),(46,13),        -- Nachos au fromage et guacamole: Gluten, Lait
(47,11),(47,4),        -- Tacos de poulet épicé: Viande, Oeufs
(48,2),(48,12),        -- Churros au chocolat: Lactose, Arachides

-- Asiatique
(49,6),(49,5),         -- Rouleaux de printemps aux crevettes: Crustacés, Poissons
(50,11),(50,9),        -- Poulet au curry thaï et riz parfumé: Viande, Soja
(51,2),(51,4),         -- Perles de coco: Lactose, Oeufs

-- Jour de l’An
(52,1),(52,4),         -- Velouté de potiron et paprika: Gluten, Oeufs
(53,11),(53,18),       -- Filet mignon sauce truffe: Viande, Champignons
(54,2),(54,4),         -- Tartelette au chocolat et éclats de caramel: Lactose, Oeufs

-- Italien
(55,2),(55,4),         -- Antipasti variés: Lactose, Oeufs
(56,18),(56,2),        -- Risotto aux champignons sauvages: Champignons, Lactose
(57,2),(57,4),         -- Tiramisu classique: Lactose, Oeufs

-- Français
(58,2),(58,4),         -- Soupe à l’oignon gratinée: Lactose, Oeufs
(59,11),(59,18),       -- Boeuf Bourguignon: Viande, Champignons
(60,2),(60,4),         -- Crème brûlée: Lactose, Oeufs

-- Classique
(61,5),(61,2),         -- Saumon mariné à l’aneth: Poissons, Lactose
(62,11),(62,1),        -- Poulet rôti à la provençale: Viande, Gluten
(63,2),(63,4),         -- Clafoutis aux cerises: Lactose, Oeufs
(64,1),(64,2),         -- Soupe de légumes de saison: Gluten, Lactose
(65,13),(65,11),       -- Gratin dauphinois et rôti de veau: Lait, Viande
(66,2),(66,1);         -- Tarte aux pommes classique: Lactose, Gluten


-- =====================================
-- MENUS
-- =====================================
INSERT INTO menu 
(menu_id, titre, nombre_personne_minimum, prix_par_personne, description, quantite_restante, conditions, regime_id, theme_id)
VALUES
(1,'Festin du Réveillon',10,58.00,'Menu de fête aux saveurs traditionnelles du Sud-Ouest.',1,'Commande minimum 10 personnes',1,1),
(2,'Réveillon Étoilé',12,72.00,'Menu gastronomique pour les grandes occasions.',1,'Réservation 10 jours avant',1,1),
(3,'Noël en Famille',8,44.00,'Menu chaleureux et généreux pour les repas de famille.',1,'Minimum 8 personnes',1,1),
(4,'Noël Végétarien Festif',6,40.00,'Menu végétarien festif aux légumes de saison.',1,'Minimum 6 personnes',2,1),
(5,'Réveillon Alsacien',10,50.00,'Menu inspiré de la tradition alsacienne.',1,'Disponible en décembre',1,1),
(6,'Noël du Périgord',8,68.00,'Menu gastronomique aux saveurs du Périgord.',1,'Minimum 8 personnes',4,1),
(7,'Noël Vegan Enchanté',6,38.00,'Menu vegan gourmand pour les fêtes.',1,'Minimum 6 personnes',3,1),
(8,'Nuit de Noël Prestige',15,95.00,'Menu haut de gamme avec produits d''exception.',1,'Réservation 21 jours avant',1,1),
(9,'Printemps de Pâques',8,42.00,'Menu léger et printanier pour célébrer Pâques.',1,'Disponible en avril',1,7),
(10,'Pâques Végétarien Fleuri',6,36.00,'Menu végétarien coloré aux légumes de printemps.',1,'Minimum 6 personnes',2,7),
(11,'Table de Pâques Provençale',8,46.00,'Saveurs du sud pour les fêtes de Pâques.',1,'Minimum 8 personnes',1,7),
(12,'Grande Table de Pâques',12,52.00,'Repas généreux pour les grandes familles.',1,'Minimum 12 personnes',1,7),
(13,'Menu Classique du Terroir',6,48.00,'Cuisine traditionnelle française.',1,'Minimum 6 personnes',1,2),
(14,'Menu Vegan Bien-être',4,34.00,'Menu végétal équilibré et savoureux.',1,'Minimum 4 personnes',3,2),
(15,'Menu Solo Gourmet',1,28.00,'Menu individuel préparé par le chef.',1,'Commande la veille',1,10),
(16,'Garbure gasconne revisitée',6,46.00,'Soupe paysanne aux légumes d''hiver et confit de canard.',1,'Minimum 6 personnes',4,2),
(17,'Magret de canard sauce au poivre vert',6,52.00,'Magret saignant, sauce au poivre vert flambée à l''Armagnac.',1,'Minimum 6 personnes',4,2),
(18,'Crème brûlée à l''Armagnac',4,12.00,'Crème vanillée avec caramel croquant.',1,'Minimum 4 personnes',1,2),
(19,'Bistrot Parisien',4,38.00,'Œuf mayonnaise, steak frites et profiteroles pour un déjeuner authentique.',1,'Minimum 4 personnes',1,2),
(20,'Menu Végétarien Méditerranéen',4,34.00,'Salades, moussaka et légumes grillés aux herbes.',1,'Minimum 4 personnes',2,2),
(21,'Vegan Monde',4,36.00,'Voyage végétal autour du monde : mezze, curry thaï, dessert japonais.',1,'Minimum 4 personnes',3,2),
(22,'Brasserie Chic',6,54.00,'Plateau de fruits de mer, sole meunière et dessert classique.',1,'Minimum 6 personnes',1,2),
(23,'Solo Carnivore – Le Bœuf du Boucher',1,32.00,'Pièce de bœuf sélectionnée, cuisson parfaite.',1,'Commande la veille avant 18h',4,10),
(24,'Solo Prestige – Magret & Foie Gras',1,38.00,'Magret et foie gras pour un repas solo raffiné.',1,'Commande la veille avant 18h',4,10),
(25,'Solo Gourmet – Le Midi du Chef',1,28.00,'Menu déjeuner gastronomique pour une personne.',1,'Commande la veille avant 18h',1,10),
(26,'Solo Végétarien – Déjeuner Zen',1,24.00,'Déjeuner végétarien équilibré et savoureux.',1,'Commande la veille avant 18h',2,10),
(27,'Mariage & Événements Premium',20,85.00,'Menu d''exception pour moments inoubliables.',1,'Minimum 20 personnes',1,9),
(28,'Cocktail Dînatoire Élégant',20,45.00,'Verrines et bouchées raffinées pour vos événements.',1,'Minimum 20 personnes',1,9),
(29,'Séminaire d''Entreprise',15,38.00,'Buffet et plats chauds pour restaurer vos équipes.',1,'Minimum 15 personnes',1,9),
(30,'Anniversaire Festif',10,55.00,'Menu festif avec gâteau personnalisé inclus.',1,'Minimum 10 personnes',1,9),
(31,'Menu Mexicain Fiesta',1,28.00,'Entrées, plats et desserts mexicains savoureux.',10,'Commande la veille',1,1),
(32,'Menu Mexicain Caliente',1,32.00,'Saveurs mexicaines épicées et gourmandes.',10,'Commande la veille',1,1),
(33,'Menu Mexicain Tradition',1,30.00,'Authentique cuisine mexicaine.',10,'Commande la veille',1,1),
(34,'Menu Mexicain Gourmet',1,35.00,'Mexique revisité par le chef.',10,'Commande la veille',1,1),
(35,'Menu Mexicain Express',1,25.00,'Menu mexicain rapide et savoureux.',10,'Commande la veille',1,1),
(36,'Menu Asiatique Sushi & Wok',1,28.00,'Plats asiatiques raffinés.',10,'Commande la veille',1,2),
(37,'Menu Asiatique Thaïlandais',1,30.00,'Saveurs exotiques de Thaïlande.',10,'Commande la veille',1,2),
(38,'Menu Asiatique Chinois',1,27.00,'Classiques asiatiques revisités.',10,'Commande la veille',1,2),
(39,'Menu Asiatique Japonais',1,33.00,'Sushis et plats japonais.',10,'Commande la veille',1,2),
(40,'Menu Asiatique Vietnamien',1,29.00,'Cuisine vietnamienne fraîche et légère.',10,'Commande la veille',1,2),
(41,'Menu Jour de l’An Prestige',1,40.00,'Menu festif pour célébrer le Nouvel An.',10,'Commande 48h à l’avance',1,3),
(42,'Menu Jour de l’An Deluxe',1,45.00,'Gastronomie pour le réveillon du Nouvel An.',10,'Commande 48h à l’avance',1,3),
(43,'Menu Italien Classico',1,30.00,'Antipasti, pâtes et desserts italiens.',10,'Commande la veille',1,4),
(44,'Menu Italien Gourmet',1,35.00,'Cuisine italienne revisitée.',10,'Commande la veille',1,4),
(45,'Menu Italien Express',1,28.00,'Menu rapide et italien.',10,'Commande la veille',1,4),
(46,'Menu Français Tradition',1,32.00,'Cuisine française classique.',10,'Commande la veille',1,5),
(47,'Menu Français Gourmet',1,38.00,'Cuisine française raffinée.',10,'Commande la veille',1,5),
(48,'Menu Classique 1',1,25.00,'Menu traditionnel classique.',10,'Commande la veille',1,6),
(49,'Menu Classique 2',1,26.00,'Menu classique pour tous.',10,'Commande la veille',1,6),
(50,'Menu Classique 3',1,27.00,'Plats classiques revisités.',10,'Commande la veille',1,6),
(51,'Menu Classique 4',1,28.00,'Menu classique gourmand.',10,'Commande la veille',1,6);

-- =====================================
-- PROPOSE (Menus <-> Plats)
-- =====================================
-- Table propose : menu_id <-> plat_id
INSERT INTO propose (menu_id, plat_id) VALUES
-- =====================================
-- PROPOSE (Menus <-> Plats) pour Noël (menus 1 à 8)
-- =====================================
INSERT INTO propose (menu_id, plat_id) VALUES
-- Festin du Réveillon
(1,1),(1,2),(1,3),
-- Réveillon Étoilé
(2,4),(2,5),(2,6),
-- Noël en Famille
(3,7),(3,8),(3,9),
-- Noël Végétarien Festif
(4,10),(4,11),(4,12),
-- Réveillon Alsacien
(5,13),(5,14),(5,15),
-- Noël du Périgord
(6,16),(6,17),(6,18),
-- Noël Vegan Enchanté
(7,19),(7,20),(7,21),
-- Nuit de Noël Prestige
(8,22),(8,23),(8,24),
-- Menu Solo
(25,52),(25,53),(25,54),  -- Solo 1
(26,55),(26,56),(26,57),  -- Solo 2
-- Événement & Mariage
(27,58),(27,59),(27,60),  -- Mariage & Événements Premium
(28,61),(28,62),(28,63),  -- Cocktail Dînatoire Élégant
(29,64),(29,65),(29,66),  -- Séminaire d’Entreprise
(30,52),(30,53),(30,54),  -- Anniversaire Festif (réutilisation des plats existants)
-- Mexicain (Menus 31 à 35)
(31,46),(31,47),(31,48),
(32,46),(32,47),(32,48),
(33,46),(33,47),(33,48),
(34,46),(34,47),(34,48),
(35,46),(35,47),(35,48),

-- Asiatique (Menus 36 à 40)
(36,49),(36,50),(36,51),
(37,49),(37,50),(37,51),
(38,49),(38,50),(38,51),
(39,49),(39,50),(39,51),
(40,49),(40,50),(40,51),

-- Jour de l’An (Menus 41 à 42)
(41,52),(41,53),(41,54),
(42,52),(42,53),(42,54),

-- Italien (Menus 43 à 45)
(43,55),(43,56),(43,57),
(44,55),(44,56),(44,57),
(45,55),(45,56),(45,57),

-- Français (Menus 46 à 47)
(46,58),(46,59),(46,60),
(47,58),(47,59),(47,60),

-- Classique (Menus 48 à 51)
(48,61),(48,62),(48,63),
(49,61),(49,62),(49,63),
(50,61),(50,62),(50,63),
(51,61),(51,62),(51,63);

-- =====================================
-- HORAIRES
-- =====================================
INSERT INTO horaire (jour, heure_ouverture, heure_fermeture) VALUES
('Lundi', '09:00:00', '18:00:00'),
('Mardi', '09:00:00', '18:00:00'),
('Mercredi', '09:00:00', '18:00:00'),
('Jeudi', '09:00:00', '18:00:00'),
('Vendredi', '09:00:00', '18:00:00'),
('Samedi', '10:00:00', '16:00:00'),
('Dimanche', NULL, NULL);

-- =====================================
-- COMMANDES
-- =====================================
INSERT INTO commande (
    numero_commande, date_commande, date_prestation, statut, 
    pret_materiel, restitution_materiel, date_statut_retour_materiel, date_statut_livree, 
    mail_penalite_envoye, heure_livraison, prix_menu, nombre_personne, prix_livraison, 
    motif_annulation, montant_rembourse, adresse_livraison, ville_livraison, 
    montant_acompte, distance_km, utilisateur_id, menu_id
) VALUES
('CMD-001', STR_TO_DATE('01-01-2026 10:00:00', '%d-%m-%Y %H:%i:%s'), STR_TO_DATE('15-01-2026', '%d-%m-%Y'), 'Terminée', 1, 1, NULL, STR_TO_DATE('15-01-2026', '%d-%m-%Y'), 0, '12:00:00', 150.00, 10, 20.00, NULL, 0.00, '1 rue Exemple', 'Paris', 50.00, 10, 3, 1),
('CMD-002', STR_TO_DATE('05-01-2026 10:00:00', '%d-%m-%Y %H:%i:%s'), STR_TO_DATE('20-01-2026', '%d-%m-%Y'), 'Acceptée', 1, 1, NULL, STR_TO_DATE('20-01-2026', '%d-%m-%Y'), 0, '12:00:00', 200.00, 15, 25.00, NULL, 0.00, '2 rue Exemple', 'Lyon', 60.00, 15, 3, 1),
('CMD-003', STR_TO_DATE('10-02-2026 10:00:00', '%d-%m-%Y %H:%i:%s'), STR_TO_DATE('25-02-2026', '%d-%m-%Y'), 'En livraison', 1, 1, NULL, NULL, 0, '12:00:00', 180.00, 12, 20.00, NULL, 0.00, '3 rue Exemple', 'Marseille', 40.00, 12, 3, 1),
('CMD-004', STR_TO_DATE('15-02-2026 10:00:00', '%d-%m-%Y %H:%i:%s'), STR_TO_DATE('28-02-2026', '%d-%m-%Y'), 'En attente', 1, 1, NULL, NULL, 0, '12:00:00', 250.00, 20, 30.00, NULL, 0.00, '4 rue Exemple', 'Toulouse', 70.00, 20, 3, 1),
('CMD-005', STR_TO_DATE('15-02-2026 10:00:00', '%d-%m-%Y %H:%i:%s'), STR_TO_DATE('28-02-2026', '%d-%m-%Y'), 'En attente de retour', 0, 0, NULL, NULL, 0, '12:00:00', 250.00, 20, 30.00, NULL, 0.00, '5 rue Exemple', 'Nice', 70.00, 20, 3, 1),
('CMD-006', STR_TO_DATE('15-02-2026 10:00:00', '%d-%m-%Y %H:%i:%s'), STR_TO_DATE('28-02-2026', '%d-%m-%Y'), 'En attente', 1, 1, NULL, NULL, 0, '12:00:00', 250.00, 20, 30.00, NULL, 0.00, '6 rue Exemple', 'Bordeaux', 70.00, 20, 3, 1);


-- =====================================
-- AVIS
-- =====================================
INSERT INTO avis (note, description, statut, utilisateur_id, commande_id) VALUES
INSERT INTO avis (note, description, statut, utilisateur_id, commande_id) VALUES

(5, 'Service impeccable et repas délicieux, très satisfait !', 'Publié', 4, 5),
(4, 'Très bon menu, juste un peu en retard sur la livraison.', 'Publié', 4, 6),
(3, 'Repas correct mais portions un peu petites.', 'Publié', 4, 7),
(5, 'Mariage parfait grâce à la qualité des plats !', 'Publié', 6, 8),
(2, 'Déçu, certains plats manquaient de goût.', 'En attente', 6, 7),
(4, 'Livraison rapide et plats bien présentés.', 'Publié', 6, 8);

-- =====================================
-- SUIVI COMMANDE
-- =====================================
-- Insertion des données pour la table suivi_commande pour l'historique des commandes
-- STR_TO_DATE permet de modifier le format de la date de YYYY-MM-DD à DD-MM-YYYY
INSERT INTO suivi_commande (statut, date_statut, commande_id)
VALUES 
('Commande reçue', STR_TO_DATE('01-01-2026 10:05:00', '%d-%m-%Y %H:%i:%s'), 5),
('En attente', STR_TO_DATE('01-01-2026 10:35:00', '%d-%m-%Y %H:%i:%s'), 5),
('Acceptée', STR_TO_DATE('01-01-2026 11:00:00', '%d-%m-%Y %H:%i:%s'), 5),
('En préparation', STR_TO_DATE('01-01-2026 12:00:00', '%d-%m-%Y %H:%i:%s'), 5),
('En livraison', STR_TO_DATE('15-01-2026 11:00:00', '%d-%m-%Y %H:%i:%s'), 5),
('Livrée', STR_TO_DATE('15-01-2026 12:00:00', '%d-%m-%Y %H:%i:%s'), 5),
('Terminée', STR_TO_DATE('15-01-2026 12:10:00', '%d-%m-%Y %H:%i:%s'), 5),

('Commande reçue', STR_TO_DATE('05-01-2026 10:10:00', '%d-%m-%Y %H:%i:%s'), 6),
('Acceptée', STR_TO_DATE('05-01-2026 12:30:00', '%d-%m-%Y %H:%i:%s'), 6),

('Commande reçue', STR_TO_DATE('10-02-2026 09:00:00', '%d-%m-%Y %H:%i:%s'), 7),
('En attente', STR_TO_DATE('10-02-2026 09:10:00', '%d-%m-%Y %H:%i:%s'), 7),
('Acceptée', STR_TO_DATE('10-02-2026 09:30:00', '%d-%m-%Y %H:%i:%s'), 7),
('En préparation', STR_TO_DATE('10-02-2026 09:45:00', '%d-%m-%Y %H:%i:%s'), 7),
('En livraison', STR_TO_DATE('10-02-2026 10:00:00', '%d-%m-%Y %H:%i:%s'), 7),

('En attente', STR_TO_DATE('25-02-2026 11:00:00', '%d-%m-%Y %H:%i:%s'), 8),

('Commande reçue', STR_TO_DATE('28-03-2026 09:00:00', '%d-%m-%Y %H:%i:%s'), 9),
('En attente', STR_TO_DATE('28-03-2026 09:10:00', '%d-%m-%Y %H:%i:%s'), 9),
('Acceptée', STR_TO_DATE('28-03-2026 09:30:00', '%d-%m-%Y %H:%i:%s'), 9),
('En préparation', STR_TO_DATE('28-03-2026 09:45:00', '%d-%m-%Y %H:%i:%s'), 9),
('En livraison', STR_TO_DATE('28-03-2026 10:00:00', '%d-%m-%Y %H:%i:%s'), 9),
('Livrée', STR_TO_DATE('28-03-2026 12:00:00', '%d-%m-%Y %H:%i:%s'), 9),
('En attente retour materiel', STR_TO_DATE('02-03-2026 12:00:00', '%d-%m-%Y %H:%i:%s'), 9),
('Commande reçue', STR_TO_DATE('08-03-2026 10:00:00', '%d-%m-%Y %H:%i:%s'), 10),
('En attente', STR_TO_DATE('08-03-2026 12:00:00', '%d-%m-%Y %H:%i:%s'), 10);

-- =====================================
-- MENU TAG
-- =====================================
INSERT INTO menu_tags (tag, menu_id) VALUES
-- Solo
('Solo', 25),
('Solo', 26),
('Solo', 25), ('Viande', 25), ('Bœuf', 25), ('Gastronomique',25 ),
('Viande', 26), ('Magret', 26), ('Foie gras',26 ), ('Sud-Ouest', 26),

-- Événement & Mariage
('Mariage', 27),
('Événement', 28),
('Séminaire', 29),
('Anniversaire', 30),
('Homard', 27), 
('Cocktail', 27), ('Bouchées', 24), ('Verrines', 27),
('Anniversaire', 28),
('Mariage', 28),  ('Élégance', 28),

-- Mexicain
('Mexicain', 31),
('Mexicain', 32),
('Mexicain', 33),
('Mexicain', 34),
('Mexicain', 35),

-- Asiatique
('Asiatique', 36),
('Asiatique', 37),
('Asiatique', 38),
('Asiatique', 39),
('Asiatique', 40),

-- Jour de l'An
('Nouvel An', 41),
('Nouvel An', 42),

-- Italien
('Italien', 43),
('Italien', 44),
('Italien', 45),

-- Français
('Français', 46),
('Français', 47),

-- Classique
('Classique', 48),
('Classique', 49),
('Classique', 50),
('Classique', 51),
('Entrecôte', 48), ('Huîtres', 48), ('Bordeaux', 48),
('Légumes BIO',49 ), ('Sans viande', 49), ('Fromages AOP', 49),
('100% Végétal', 50), ('BIO', 50), ('Sans allergènes majeurs', 50),
('Gascogne', 51), ('Canard', 51), ('Armagnac', 51),
-- Noël
('Foie gras', 1), ('Magret', 1), ('Truffe',1 ),
('Saumon fumé', 2), ('Dinde farcie', 2), ('Bûche', 2),
('Convivial', 3), ('Terroir', 3), ('Chapon', 3),
('Végétarien', 4), ('Épices', 4), ('Champignons', 4),
('Alsace', 4), ('Choucroute', 4), ('Bredele', 4),
('Truffe', 5), ('Confit', 5), ('Périgord', 5),
('Vegan', 6), ('BIO', 6), ('Épices de Noël', 6),
('Caviar', 7), ('Homard', 7), ('Prestige', 7),
-- Pâques
('Agneau', 27), ('Légumes primeurs', 27), ('Agrumes', 27),
('Saumon', 28), ('Primeurs', 28), ('Chocolat', 28),
('Végétarien', 29), ('Fleurs comestibles', 29), ('Asperges',29 ),
('Provence', 30), ('Agneau', 30), ('Lavande', 30),
('Vegan', 31), ('Crudités', 31), ('Chocolat noir', 31),
('Veau', 32), ('Asperges', 32), ('Fraises', 32),
('Pays Basque', 33), ('Piment d''Espelette', 33), ('Agneau de lait', 33),
('Famille', 34), ('Enfants', 34), ('Chocolat', 34);


-- =====================================
-- IMAGE A IMPORTER
-- =====================================
'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=800'
'https://images.unsplash.com/photo-1576402187878-974f70c890a5?w=800'
'https://images.unsplash.com/photo-1608039829572-78524f79c4c7?w=800'
'https://images.unsplash.com/photo-1482275548304-a58859dc31b7?w=800'
'https://images.unsplash.com/photo-1606787366850-de6330128bfc?w=800'
'https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=800'
'https://images.unsplash.com/photo-1543362906-acfc16c67564?w=800'
'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=800'
'https://images.unsplash.com/photo-1563245372-f21724e3856d?w=800'
'https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=800'
'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800'
'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800'
'https://images.unsplash.com/photo-1540914124281-342587941389?w=800'
'https://images.unsplash.com/photo-1543362906-acfc16c67564?w=800'
'https://images.unsplash.com/photo-1559339352-11d035aa65de?w=800'
'https://images.unsplash.com/photo-1482275548304-a58859dc31b7?w=800'
-- Pâques
'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=800'
'https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=800'
'https://images.unsplash.com/photo-1490818387583-1baba5e638af?w=800'
'https://images.unsplash.com/photo-1466637574441-749b8f19452f?w=800'
'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=800'
'https://images.unsplash.com/photo-1490818387583-1baba5e638af?w=800'
'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800'
'https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=800'
'https://images.unsplash.com/photo-1540914124281-342587941389?w=800'
'https://images.unsplash.com/photo-1543362906-acfc16c67564?w=800'
'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800'
'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=800'
'https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=800'
'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800'
'https://images.unsplash.com/photo-1606787366850-de6330128bfc?w=800'
'https://images.unsplash.com/photo-1490818387583-1baba5e638af?w=800'
-- Classique
'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800'
'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800'
'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=800'
'https://images.unsplash.com/photo-1540914124281-342587941389?w=800'
'https://images.unsplash.com/photo-1540914124281-342587941389?w=800'
'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=800'
'https://images.unsplash.com/photo-1600891964092-4316c288032e?w=800'
'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800'
'https://images.unsplash.com/photo-1559339352-11d035aa65de?w=800'
'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800'
'https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=800'
'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=800'
'https://images.unsplash.com/photo-1543362906-acfc16c67564?w=800'
'https://images.unsplash.com/photo-1540914124281-342587941389?w=800'
'https://images.unsplash.com/photo-1482275548304-a58859dc31b7?w=800'
'https://images.unsplash.com/photo-1559339352-11d035aa65de?w=8004'
-- Solo
'https://images.unsplash.com/photo-1558030006-450675393462?w=800'
'https://images.unsplash.com/photo-1600891964092-4316c288032e?w=800'
'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800'
'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=800'
'https://images.unsplash.com/photo-1565299585323-38d6b0865b47?w=800'
'https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=800'
'https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=800'
'https://images.unsplash.com/photo-1565299585323-38d6b0865b47?w=800'
-- Événement
'https://images.unsplash.com/photo-1587899897387-091ebd01a6b2?w=800'
'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800'
'https://images.unsplash.com/photo-1551218808-94e220e084d2?w=800'
'https://images.unsplash.com/photo-1559339352-11d035aa65de?w=800'
'https://images.unsplash.com/photo-1600891964092-4316c288032e?w=800'
'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800'
'https://images.unsplash.com/photo-1464349095431-e9a21285b5f3?w=800'
'https://images.unsplash.com/photo-1551218808-94e220e084d2?w=800'
'https://images.unsplash.com/photo-1543362906-acfc16c67564?w=800'
'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=800'
'https://images.unsplash.com/photo-1482275548304-a58859dc31b7?w=800'
'https://images.unsplash.com/photo-1587899897387-091ebd01a6b2?w=800'
'https://images.unsplash.com/photo-1606787366850-de6330128bfc?w=800'
'https://images.unsplash.com/photo-1464349095431-e9a21285b5f3?w=800'
'https://images.unsplash.com/photo-1543362906-acfc16c67564?w=800'
'https://images.unsplash.com/photo-1540914124281-342587941389?w=800'