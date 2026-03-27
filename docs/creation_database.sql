-- =====================================
-- FICHIER D'INSERTION - Vite & Gourmand
-- =====================================
-- ORDRE D'INSERTION :
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
-- 15. liason avec menus
-- 16. Mise à jour

-- =====================================
-- SÉCURITÉ : désactivation des contraintes
-- =====================================
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================
--  CLEAN (décommente si reset total)
-- ============================================
-- TRUNCATE TABLE contient;
-- TRUNCATE TABLE propose;
-- TRUNCATE TABLE commande;
-- TRUNCATE TABLE utilisateur;
-- TRUNCATE TABLE menu;
-- TRUNCATE TABLE plat;
-- TRUNCATE TABLE role;
-- TRUNCATE TABLE regime;
-- TRUNCATE TABLE allergene;
-- TRUNCATE TABLE theme;
-- TRUNCATE TABLE menu_tags;
-- TRUNCATE TABLE avis;
-- TRUNCATE TABLE suivi_commande;

-- =====================================
-- ROLE
-- =====================================
INSERT IGNORE INTO role (role_id, libelle) VALUES
(1, 'ROLE_CLIENT'),
(2, 'ROLE_EMPLOYE'),
(3, 'ROLE_ADMIN');

-- =====================================
-- UTILISATEUR
-- =====================================
-- ATTENTION : role_id doit correspondre aux IDs auto-générés :
-- 1 = ROLE_CLIENT, 2 = ROLE_EMPLOYE, 3 = ROLE_ADMIN
-- creation d'un mdp hacher php bin/console security:hash-password 

-- mdp hashé = Moncode23+
INSERT IGNORE INTO utilisateur
(nom, prenom, telephone, email, password, pays, ville, adresse_postale, code_postal, statut_compte, role_id)
VALUES
('admin', 'admin', '0123456789', 'admin@vite-et-gourmand.fr', '$2y$13$wjlZHiTr40IOymvkXskeCeZ.3hJGVM2acU3lsL9fbniwGo4GAZNX.', 'France', 'Montpellier', '123 Rue de la Gourmandise', '34400', 'actif', 3),
('employe', 'employe', '0600000001', 'employe@vite-et-gourmand.fr', '$2y$13$wjlZHiTr40IOymvkXskeCeZ.3hJGVM2acU3lsL9fbniwGo4GAZNX.', 'France', 'Montpellier', '1 rue de la Fougasse', '34000', 'actif', 2),
('client', 'client', '0688888888', 'client@gmail.com', '$2y$13$wjlZHiTr40IOymvkXskeCeZ.3hJGVM2acU3lsL9fbniwGo4GAZNX.', 'France', 'Lherm', '1222 Rue Exemple', '33000', 'actif', 1),
('Dupont', 'Jean', '0601020304', 'jean.dupont@email.com', '$2y$13$wjlZHiTr40IOymvkXskeCeZ.3hJGVM2acU3lsL9fbniwGo4GAZNX.', 'France', 'Paris', '1 rue Exemple', '75001', 'actif', 1),
('Martin', 'Claire', '0605060708', 'claire.martin@email.com', '$2y$13$wjlZHiTr40IOymvkXskeCeZ.3hJGVM2acU3lsL9fbniwGo4GAZNX.', 'France', 'Lyon', '2 rue Exemple', '69001', 'actif', 1),
('Durand', 'Paul', '0608091011', 'paul.durand@email.com', '$2y$13$wjlZHiTr40IOymvkXskeCeZ.3hJGVM2acU3lsL9fbniwGo4GAZNX.', 'France', 'Marseille', '3 rue Exemple', '13001', 'actif', 1),
('Bernard', 'Sophie', '0612131415', 'sophie.bernard@email.com', '$2y$13$wjlZHiTr40IOymvkXskeCeZ.3hJGVM2acU3lsL9fbniwGo4GAZNX.', 'France', 'Toulouse', '4 rue Exemple', '31000', 'actif', 1),
('Moreau', 'Luc', '0616171819', 'luc.moreau@email.com', '$2y$13$wjlZHiTr40IOymvkXskeCeZ.3hJGVM2acU3lsL9fbniwGo4GAZNX.', 'France', 'Nice', '5 rue Exemple', '06000', 'actif', 1),
('Lefevre', 'Emma', '0620212223', 'emma.lefevre@email.com', '$2y$13$wjlZHiTr40IOymvkXskeCeZ.3hJGVM2acU3lsL9fbniwGo4GAZNX.', 'France', 'Bordeaux', '6 rue Exemple', '33000', 'inactif', 1);

-- =====================================
-- REGIMES
-- =====================================
INSERT IGNORE INTO regime (regime_id, libelle) VALUES
(1, 'Classique'),
(2, 'Végétalien'),
(3, 'Vegan'),
(4, 'Carnivore');

-- =====================================
-- ALLERGENES
-- =====================================
INSERT IGNORE INTO allergene (allergene_id, libelle) VALUES
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
(11, 'Viande'),
(12, 'Arachides'),
(13, 'Lait'),
(14, 'Noisettes'),
(15, 'Céleri'),
(16, 'Moutarde'),
(17, 'Lupin'),
(18, 'Champignons');

-- =====================================
-- THEMES
-- =====================================
INSERT IGNORE INTO theme (theme_id, libelle) VALUES
(1, 'Noël'),
(2, 'Classique'),
(3, 'Asiatique'),
(4, 'Mexicain'),
(5, 'Français'),
(6, 'Italien'),
(7, 'Pâques'),
(8, 'Jour de l''An'),
(9, 'Événement'),
(10, 'Solo');

-- =====================================
-- PLATS
-- =====================================
INSERT IGNORE INTO plat (plat_id, titre_plat, photo, categorie,description_plat) VALUES
-- Noël (plats 1-24)
(1, 'Velouté de châtaigne et foie gras poêlé', 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=800', 'Entrée','Crème de châtaignes onctueuse avec morceaux de foie gras poêlé.'),
(2, 'Magret de canard aux cèpes', 'https://images.unsplash.com/photo-1576402187878-974f70c890a5?w=800', 'Plat', 'Magret de canard rôti accompagné de cèpes.'),
(3, 'Bûche à l''Armagnac et marrons glacés', 'https://images.unsplash.com/photo-1608039829572-78524f79c4c7?w=800', 'Dessert','Bûche à l''Armagnac et marrons glacés.'),
(4, 'Saumon fumé maison, blinis et crème citronnée', 'https://images.unsplash.com/photo-1482275548304-a58859dc31b7?w=800', 'Entrée', 'Tranches fines de saumon fumé, servies sur blinis avec crème citronnée.'),
(5, 'Dinde de Noël farcie aux marrons et foie gras', 'https://images.unsplash.com/photo-1606787366850-de6330128bfc?w=800', 'Plat','Dinde de Noël farcie aux marrons et foie gras. '),
(6, 'Bûche Montblanc revisitée', 'https://images.unsplash.com/photo-1563245372-f21724e3856d?w=800', 'Dessert', 'Bûche Montblanc revisitée, douce et légère.'),
(7, 'Verrines de crevettes et avocat', 'https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=800', 'Entrée', 'Verrines fraîches composées de crevettes et purée d’avocat.'),
(8, 'Chapon rôti aux légumes d''hiver', 'https://images.unsplash.com/photo-1543362906-acfc16c67564?w=800', 'Plat', 'Chapon rôti servi avec légumes d''hiver. '),
(9, 'Charlotte aux fraises des bois', 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=800', 'Dessert', 'Charlotte aux fraises des bois, mousse légère.'),
(10, 'Soupe de courge butternut épicée', 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800', 'Entrée', 'Soupe veloutée de courge butternut légèrement épicée'),
(11, 'Wellington de légumes d''hiver', 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800', 'Plat', 'Wellington de légumes d''hiver savoureux et croustillant.'),
(12, 'Mousse au chocolat noir et orange confite', 'https://images.unsplash.com/photo-1540914124281-342587941389?w=800', 'Dessert', 'Mousse au chocolat noir et orange confite.' ),
(13, 'Foie gras d''Alsace au Gewurztraminer', 'https://images.unsplash.com/photo-1559339352-11d035aa65de?w=800', 'Entrée', 'Velouté de panais relevé avec des épices de Noël.'),
(14, 'Choucroute festive aux trois viandes', 'https://images.unsplash.com/photo-1600891964092-4316c288032e?w=800', 'Plat', 'Foie gras d''Alsace servi avec Gewurztraminer aromatique.'),
(15, 'Bredele et strudel aux pommes', 'https://images.unsplash.com/photo-1482275548304-a58859dc31b7?w=800', 'Dessert', 'Choucroute festive aux trois viandes et choux parfumés.'),
(16, 'Œuf en cocotte aux truffes noires', 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=800', 'Entrée', 'Œuf en cocotte délicatement parfumé aux truffes noires.'),
(17, 'Confit de canard maison, sarladaise', 'https://images.unsplash.com/photo-1576402187878-974f70c890a5?w=800', 'Plat', "Confit de canard maison servi à la sarladaise. "),
(18, 'Fondant au chocolat et glace truffe', 'https://images.unsplash.com/photo-1608039829572-78524f79c4c7?w=800', 'Dessert', "Fondant au chocolat et glace truffe."),
(19, 'Velouté de panais aux épices de Noël', 'https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=800', 'Entrée', "Velouté de panais relevé avec des épices de Noël."),
(20, 'Rôti de seitan aux herbes et légumes confits', 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=800', 'Plat', "Rôti de seitan aux herbes, accompagné de légumes confits."),
(21, 'Bûche vegan chocolat-noisette', 'https://images.unsplash.com/photo-1563245372-f21724e3856d?w=800', 'Dessert', "Bûche vegan chocolat-noisette. "),
(22, 'Royale de caviar et homard en gelée', 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800', 'Entrée', "Royale de caviar et homard présentée en gelée raffinée."),
(23, 'Filet de bœuf Rossini, sauce Périgueux', 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800', 'Plat', "Filet de bœuf Rossini nappé de sauce Périgueux."),
(24, 'Bûche Ispahan framboise rose litchi', 'https://images.unsplash.com/photo-1540914124281-342587941389?w=800', 'Dessert', "Bûche Ispahan framboise, rose et litchi."),
-- Pâques (plats 25-45)
(25, 'Asperges blanches sauce mousseline', 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=800', 'Entrée', "Asperges blanches servies avec une sauce mousseline légère."),
(26, 'Gigot d''agneau de lait rôti aux herbes', 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=800', 'Plat', "Gigot d'agneau de lait rôti aux herbes et épices."),
(27, 'Tarte citron meringuée', 'https://images.unsplash.com/photo-1490818387583-1baba5e638af?w=800', 'Dessert', "Tarte citron meringuée."),
(28, 'Tartare de saumon citron-aneth', 'https://images.unsplash.com/photo-1466637574441-749b8f19452f?w=800', 'Entrée', " Tartare de saumon frais parfumé au citron et à l'aneth."),
(29, 'Carré d''agneau en croûte de pistaches', 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=800', 'Plat',"Carré d'agneau en croûte de pistaches, tendre et parfumé." ),
(30, 'Œufs en chocolat surprise', 'https://images.unsplash.com/photo-1490818387583-1baba5e638af?w=800', 'Dessert', "Œufs en chocolat surprise pour Pâques."),
(31, 'Salade d''asperges et chèvre frais', 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800', 'Entrée', "Salade d'asperges croquantes et chèvre frais."),
(32, 'Tarte tatin aux légumes printaniers', 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=800', 'Plat', "Tarte tatin aux légumes printaniers, caramélisée."),
(33, 'Nid de Pâques en meringue', 'https://images.unsplash.com/photo-1540914124281-342587941389?w=800', 'Dessert', "Nid de Pâques en meringue."),
(34, 'Anchoïade et légumes crus', 'https://images.unsplash.com/photo-1543362906-acfc16c67564?w=800', 'Entrée', "Anchoïade accompagnée de légumes crus frais."),
(35, 'Épaule d''agneau confite 7 heures', 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800', 'Plat', "Épaule d'agneau confite 7 heures, fondante et parfumée."),
(36, 'Calisson glacé au miel de lavande', 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=800', 'Dessert', "Calisson glacé au miel de lavande."),
(37, 'Gaspacho vert aux herbes fraîches', 'https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=800', 'Entrée', "Gaspacho vert rafraîchissant aux herbes fraîches."),
(38, 'Lentilles du Puy aux légumes racines', 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800', 'Plat', "Lentilles du Puy servies avec légumes racines."),
(39, 'Fondant au chocolat noir vegan', 'https://images.unsplash.com/photo-1606787366850-de6330128bfc?w=800', 'Dessert', "Fondant au chocolat noir vegan."),
(40, 'Terrine de campagne printanière', 'https://images.unsplash.com/photo-1490818387583-1baba5e638af?w=800', 'Entrée', "Terrine de campagne aromatisée aux herbes printanières."),
(41, 'Blanquette de veau à l''ancienne', 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800', 'Plat', "Blanquette de veau à l'ancienne, sauce onctueuse."),
(42, 'Fraisier classique', 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=800', 'Dessert', "Fraisier classique, crème légère et fraises."),
(43, 'Piperade basque et jambon de Bayonne', 'https://images.unsplash.com/photo-1540914124281-342587941389?w=800', 'Entrée',"Piperade basque colorée avec jambon de Bayonne."),
(44, 'Agneau du Pays Basque rôti', 'https://images.unsplash.com/photo-1543362906-acfc16c67564?w=800', 'Plat', "Agneau du Pays Basque rôti et parfumé aux herbes."),
(45, 'Gâteau basque à la cerise noire', 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=800', 'Dessert', "Gâteau basque à la cerise noire."),
-- Mexicain (plats 46-48)
(46, 'Nachos au fromage et guacamole', 'https://images.unsplash.com/photo-1565299585323-38d6b0865b47?w=800', 'Entrée', "Nachos au fromage fondant et guacamole maison."),
(47, 'Tacos de poulet épicé', 'https://images.unsplash.com/photo-1551504734-5ee1c4a1479b?w=800', 'Plat', "Tacos de poulet épicé, tendre et savoureux."),
(48, 'Churros au chocolat', 'https://images.unsplash.com/photo-1559339352-11d035aa65de?w=800', 'Dessert', "Churros au chocolat, croustillants et gourmands."),
-- Asiatique (plats 49-51)
(49, 'Rouleaux de printemps aux crevettes', 'https://images.unsplash.com/photo-1563245372-f21724e3856d?w=800', 'Entrée', "Rouleaux de printemps aux crevettes fraîches et herbes."),
(50, 'Poulet au curry thaï et riz parfumé', 'https://images.unsplash.com/photo-1455619452474-d2be8b1e70cd?w=800', 'Plat', "Poulet au curry thaï servi avec riz parfumé."),
(51, 'Perles de coco', 'https://images.unsplash.com/photo-1482275548304-a58859dc31b7?w=800', 'Dessert', "Perles de coco moelleuses."),
-- Jour de l''An (plats 52-54)
(52, 'Velouté de potiron et paprika', 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=800', 'Entrée', "Velouté de potiron crémeux relevé au paprika."),
(53, 'Filet mignon sauce truffe', 'https://images.unsplash.com/photo-1600891964092-4316c288032e?w=800', 'Plat', "Filet mignon sauce truffe, tendre et raffiné."),
(54, 'Tartelette au chocolat et éclats de caramel', 'https://images.unsplash.com/photo-1608039829572-78524f79c4c7?w=800', 'Dessert', "Tartelette au chocolat et éclats de caramel."),
-- Italien (plats 55-57)
(55, 'Antipasti variés', 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=800', 'Entrée', "Antipasti variés aux légumes grillés et charcuterie."),
(56, 'Risotto aux champignons sauvages', 'https://images.unsplash.com/photo-1476124369491-e7addf5db371?w=800', 'Plat', "Risotto crémeux aux champignons sauvages."),
(57, 'Tiramisu classique', 'https://images.unsplash.com/photo-1571877227200-a0d98ea607e9?w=800', 'Dessert', "Tiramisu classique, café et mascarpone."),
-- Français (plats 58-60)
(58, 'Soupe à l''oignon gratinée', 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=800', 'Entrée', "Soupe à l'oignon gratinée, dorée et savoureuse."),
(59, 'Boeuf Bourguignon', 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800', 'Plat', "Boeuf Bourguignon mijoté longuement, fondant."),
(60, 'Crème brûlée', 'https://images.unsplash.com/photo-1470124182917-cc6e71b22ecc?w=800', 'Dessert', "Crème brûlée, croûte caramélisée et onctueuse."),
-- Classique (plats 61-66)
(61, 'Saumon mariné à l''aneth', 'https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=800', 'Entrée', "Saumon mariné à l'aneth, finement assaisonné."),
(62, 'Poulet rôti à la provençale', 'https://images.unsplash.com/photo-1598103442097-8b74f568ef73?w=800', 'Plat', "Poulet rôti à la provençale avec herbes et légumes."),
(63, 'Clafoutis aux cerises', 'https://images.unsplash.com/photo-1490818387583-1baba5e638af?w=800', 'Dessert', "Clafoutis aux cerises."),
(64, 'Soupe de légumes de saison', 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=800', 'Entrée', "Soupe de légumes de saison, chaude et réconfortante."),
(65, 'Gratin dauphinois et rôti de veau', 'https://images.unsplash.com/photo-1600891964092-4316c288032e?w=800', 'Plat', "Gratin dauphinois accompagné de rôti de veau. "),
(66, 'Tarte aux pommes classique', 'https://images.unsplash.com/photo-1568571780765-9276ac8b75a2?w=800', 'Dessert', "Tarte aux pommes classique, pâte croustillante.");


-- =====================================
-- CONTIENT (Plats <-> Allergènes)
-- =====================================
INSERT IGNORE INTO contient (plat_id, allergene_id) VALUES
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
(45, 1),
(46, 1), (46, 13), -- Mexicain Nachos au fromage et guacamole: Gluten, Lait
(47, 11), (47, 4), -- Mexicain Tacos de poulet épicé: Viande, Oeufs
(48, 2), (48, 12), -- Mexicain Churros au chocolat: Lactose, Arachides
(49, 6), (49, 5),  -- Asiatique Rouleaux de printemps aux crevettes: Crustacés, Poissons
(50, 11), (50, 9), -- Asiatique Poulet au curry thaï et riz parfumé: Viande, Soja
(51, 2), (51, 4),  -- Asiatique Perles de coco: Lactose, Oeufs
(52, 1), (52, 4),  -- Jour de l'An Velouté de potiron et paprika: Gluten, Oeufs
(53, 11), (53, 18),-- Jour de l'An Filet mignon sauce truffe: Viande, Champignons
(54, 2), (54, 4),  -- Jour de l'An Tartelette au chocolat et éclats de caramel: Lactose, Oeufs
(55, 2), (55, 4),  -- Italien Antipasti variés: Lactose, Oeufs
(56, 18), (56, 2), -- Italien Risotto aux champignons sauvages: Champignons, Lactose
(57, 2), (57, 4),  -- Italien Tiramisu classique: Lactose, Oeufs
(58, 2), (58, 4),  -- Français Soupe à l’oignon gratinée: Lactose, Oeufs
(59, 11), (59, 18),-- Français Boeuf Bourguignon: Viande, Champignons
(60, 2), (60, 4),  -- Français Crème brûlée: Lactose, Oeufs
(61, 5),(61, 2),   -- Classique Saumon mariné à l’aneth: Poissons, Lactose
(62, 11),(62, 1),  -- Classique Poulet rôti à la provençale: Viande, Gluten
(63, 2),(63, 4),   -- Classique Clafoutis aux cerises: Lactose, Oeufs
(64, 1),(64, 2),   -- Classique Soupe de légumes de saison: Gluten, Lactose
(65, 13),(65, 11), -- Classique Gratin dauphinois et rôti de veau: Lait, Viande
(66, 2),(66,1 );   -- Classique Tarte aux pommes classique: Lactose, Gluten

-- =====================================
-- MENUS
-- =====================================
INSERT IGNORE INTO menu
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
(31,'Menu Mexicain Fiesta',1,28.00,'Entrées, plats et desserts mexicains savoureux.',10,'Commande la veille',1,4),
(32,'Menu Mexicain Caliente',1,32.00,'Saveurs mexicaines épicées et gourmandes.',10,'Commande la veille',1,4),
(33,'Menu Mexicain Tradition',1,30.00,'Authentique cuisine mexicaine.',10,'Commande la veille',1,4),
(34,'Menu Mexicain Gourmet',1,35.00,'Mexique revisité par le chef.',10,'Commande la veille',1,4),
(35,'Menu Mexicain Express',1,25.00,'Menu mexicain rapide et savoureux.',10,'Commande la veille',1,4),
(36,'Menu Asiatique Sushi & Wok',1,28.00,'Plats asiatiques raffinés.',10,'Commande la veille',1,3),
(37,'Menu Asiatique Thaïlandais',1,30.00,'Saveurs exotiques de Thaïlande.',10,'Commande la veille',1,3),
(38,'Menu Asiatique Chinois',1,27.00,'Classiques asiatiques revisités.',10,'Commande la veille',1,3),
(39,'Menu Asiatique Japonais',1,33.00,'Sushis et plats japonais.',10,'Commande la veille',1,3),
(40,'Menu Asiatique Vietnamien',1,29.00,'Cuisine vietnamienne fraîche et légère.',10,'Commande la veille',1,3),
(41,'Menu Jour de l''An Prestige',1,40.00,'Menu festif pour célébrer le Nouvel An.',10,'Commande 48h à l''avance',1,8),
(42,'Menu Jour de l''An Deluxe',1,45.00,'Gastronomie pour le réveillon du Nouvel An.',10,'Commande 48h à l''avance',1,8),
(43,'Menu Italien Classico',1,30.00,'Antipasti, pâtes et desserts italiens.',10,'Commande la veille',1,6),
(44,'Menu Italien Gourmet',1,35.00,'Cuisine italienne revisitée.',10,'Commande la veille',1,6),
(45,'Menu Italien Express',1,28.00,'Menu rapide et italien.',10,'Commande la veille',1,6),
(46,'Menu Français Tradition',1,32.00,'Cuisine française classique.',10,'Commande la veille',1,5),
(47,'Menu Français Gourmet',1,38.00,'Cuisine française raffinée.',10,'Commande la veille',1,5),
(48,'Menu Classique 1',1,25.00,'Menu traditionnel classique.',10,'Commande la veille',1,2),
(49,'Menu Classique 2',1,26.00,'Menu classique pour tous.',10,'Commande la veille',1,2),
(50,'Menu Classique 3',1,27.00,'Plats classiques revisités.',10,'Commande la veille',1,2),
(51,'Menu Classique 4',1,28.00,'Menu classique gourmand.',10,'Commande la veille',1,2);

-- =====================================
-- PROPOSE (Menus <-> Plats)
-- =====================================
-- Table propose : menu_id <-> plat_id
-- PROPOSE (Menus <-> Plats) pour Noël (menus 1 à 8)
-- =====================================
INSERT IGNORE INTO propose (menu_id, plat_id) VALUES

(1,1),(1,2),(1,3), -- Noël
(2,4),(2,5),(2,6),
(3,7),(3,8),(3,9),
(4,10),(4,11),(4,12),
(5,13),(5,14),(5,15),
(6,16),(6,17),(6,18),
(7,19),(7,20),(7,21),
(8,22),(8,23),(8,24),
(9,25),(9,26),(9,27), -- Pâques
(10,28),(10,29),(10,30),
(11,31),(11,32),(11,33),
(12,34),(12,35),(12,36),
(13,40),(13,41),(13,42), -- Classique / Vegan / Solo
(14,43),(14,44),(14,45),
(15,46),(15,47),(15,48),
(16,49),(16,50),(16,51),
(17,52),(17,53),(17,54),
(18,55),(18,56),(18,57),
(19,58),(19,59),(19,60),
(20,61),(20,62),(20,63),
(21,64),(21,65),(21,66),
(25,52),(25,53),(25,54), -- Solo
(26,55),(26,56),(26,57),
(27,58),(27,59),(27,60), -- Événement & Mariage
(28,61),(28,62),(28,63),
(29,64),(29,65),(29,66),
(30,52),(30,53),(30,54),
(31,46),(31,47),(31,48), -- Mexicain
(32,46),(32,47),(32,48),
(33,46),(33,47),(33,48),
(34,46),(34,47),(34,48),
(35,46),(35,47),(35,48),
(36,49),(36,50),(36,51), -- Asiatique
(37,49),(37,50),(37,51),
(38,49),(38,50),(38,51),
(39,49),(39,50),(39,51),
(40,49),(40,50),(40,51),
(41,52),(41,53),(41,54), -- Jour de l'An
(42,52),(42,53),(42,54),
(43,55),(43,56),(43,57), -- Italien
(44,55),(44,56),(44,57),
(45,55),(45,56),(45,57),
(46,58),(46,59),(46,60), -- Français
(47,58),(47,59),(47,60),
(48,61),(48,62),(48,63), -- Classique
(49,61),(49,62),(49,63),
(50,61),(50,62),(50,63),
(51,61),(51,62),(51,63);

-- =====================================
-- HORAIRES
-- =====================================
INSERT IGNORE INTO horaire (jour, heure_ouverture, heure_fermeture) VALUES
('Lundi','09:00:00','18:00:00'),
('Mardi','09:00:00','18:00:00'),
('Mercredi','09:00:00','18:00:00'),
('Jeudi','09:00:00','18:00:00'),
('Vendredi','09:00:00','18:00:00'),
('Samedi','10:00:00','14:00:00'),
('Dimanche',NULL,NULL);

-- =====================================
-- AVIS
-- =====================================
INSERT IGNORE INTO avis (note, description, statut, date, utilisateur_id, commande_id) VALUES
(5, 'Excellent repas, très satisfait !', 'validé', NOW(), 3, 1),
(4, 'Plats bons mais service lent.', 'validé', NOW(), 4, 2),
(3, 'Correct mais peu copieux.', 'validé', NOW(), 5, 3),
(5, 'Service impeccable et repas délicieux, très satisfait !', 'Publié', NOW(), 4, 1),
(4, 'Très bon menu, juste un peu en retard sur la livraison.', 'Publié', NOW(), 4, 2),
(3, 'Repas correct mais portions un peu petites.', 'Publié', NOW(), 5, 3),
(5, 'Mariage parfait grâce à la qualité des plats !', 'En attente', NOW(), 6, 4),
(2, 'Déçu, certains plats manquaient de goût.', 'En attente', NOW(), 7, 5),
(4, 'Livraison rapide et plats bien présentés.', 'Publié', NOW(), 8, 6);

-- =====================================
-- SUIVI COMMANDE
-- =====================================
-- Insertion des données pour la table suivi_commande pour l'historique des commandes
-- STR_TO_DATE permet de modifier le format de la date de YYYY-MM-DD à DD-MM-YYYY
-- =====================================
INSERT IGNORE INTO suivi_commande (statut, date_statut, commande_id) VALUES
-- Commande 1 (Terminée)
('Commande reçue', STR_TO_DATE('01-01-2026 10:05:00', '%d-%m-%Y %H:%i:%s'), 1),
('En attente', STR_TO_DATE('01-01-2026 10:35:00', '%d-%m-%Y %H:%i:%s'), 1),
('Acceptée', STR_TO_DATE('01-01-2026 11:00:00', '%d-%m-%Y %H:%i:%s'), 1),
('En préparation', STR_TO_DATE('01-01-2026 12:00:00', '%d-%m-%Y %H:%i:%s'), 1),
('En livraison', STR_TO_DATE('15-01-2026 11:00:00', '%d-%m-%Y %H:%i:%s'), 1),
('Livrée', STR_TO_DATE('15-01-2026 12:00:00', '%d-%m-%Y %H:%i:%s'), 1),
('Terminée', STR_TO_DATE('15-01-2026 12:10:00', '%d-%m-%Y %H:%i:%s'), 1),
-- Commande 2 (Acceptée)
('Commande reçue', STR_TO_DATE('05-01-2026 10:10:00', '%d-%m-%Y %H:%i:%s'), 2),
('Acceptée', STR_TO_DATE('05-01-2026 12:30:00', '%d-%m-%Y %H:%i:%s'), 2),
-- Commande 3 (En livraison)
('Commande reçue', STR_TO_DATE('10-02-2026 09:00:00', '%d-%m-%Y %H:%i:%s'), 3),
('En attente', STR_TO_DATE('10-02-2026 09:10:00', '%d-%m-%Y %H:%i:%s'), 3),
('Acceptée', STR_TO_DATE('10-02-2026 09:30:00', '%d-%m-%Y %H:%i:%s'), 3),
('En préparation', STR_TO_DATE('10-02-2026 09:45:00', '%d-%m-%Y %H:%i:%s'), 3),
('En livraison', STR_TO_DATE('10-02-2026 10:00:00', '%d-%m-%Y %H:%i:%s'), 3),
-- Commande 4 (En attente)
('En attente', STR_TO_DATE('25-02-2026 11:00:00', '%d-%m-%Y %H:%i:%s'), 4),
-- Commande 5 (En attente de retour)
('Commande reçue', STR_TO_DATE('28-02-2026 09:00:00', '%d-%m-%Y %H:%i:%s'), 5),
('En attente', STR_TO_DATE('28-02-2026 09:10:00', '%d-%m-%Y %H:%i:%s'), 5),
('Acceptée', STR_TO_DATE('28-02-2026 09:30:00', '%d-%m-%Y %H:%i:%s'), 5),
('En préparation', STR_TO_DATE('28-02-2026 09:45:00', '%d-%m-%Y %H:%i:%s'), 5),
('En livraison', STR_TO_DATE('28-02-2026 10:00:00', '%d-%m-%Y %H:%i:%s'), 5),
('Livrée', STR_TO_DATE('28-02-2026 12:00:00', '%d-%m-%Y %H:%i:%s'), 5),
('En attente retour materiel', STR_TO_DATE('02-03-2026 12:00:00', '%d-%m-%Y %H:%i:%s'), 5),
-- Commande 6 (En attente)
('Commande reçue', STR_TO_DATE('08-03-2026 10:00:00', '%d-%m-%Y %H:%i:%s'), 6),
('En attente', STR_TO_DATE('08-03-2026 12:00:00', '%d-%m-%Y %H:%i:%s'), 6);

-- =====================================
-- MENU TAGS AJOUT DES VALEURS
-- =====================================
INSERT IGNORE INTO menu_tags (tag) VALUES
('Foie gras'), ('Magret'), ('Truffe'), ('Saumon fumé'), ('Dinde farcie'), 
('Bûche'), ('Convivial'), ('Terroir'), ('Chapon'), ('Végétarien'),
('Épices'), ('Champignons'), ('Alsace'), ('Choucroute'), ('Bredele'),
('Confit'), ('Périgord'), ('Vegan'), ('BIO'), ('Épices de Noël'),
('Caviar'), ('Homard'), ('Prestige'), ('Agneau'), ('Légumes primeurs'),
('Agrumes'), ('Saumon'), ('Primeurs'), ('Chocolat'), ('Provence'),
('Lavande'), ('Famille'), ('Enfants'), ('Solo'), ('Viande'), ('Bœuf'),
('Gastronomique'), ('Magret'), ('Sud-Ouest'), ('Mariage'), ('Verrines'),
('Chitake'), ('Anniversaire'), ('Élégance'), ('Séminaire'), ('Mexicain'),
('Asiatique'), ('Nouvel An'), ('Italien'), ('Tofu'), ('Classique'),
('Huîtres'), ('Bordeaux'), ('Légumes BIO'), ('Sans viande'), ('100% Végétal'),
('Gascogne'), ('Canard'), ('Pate fraiche'), ('gorgonzola'), ('Gnocchi'), 
('carpaccio'), ('parmigiana') , ('pepperoni'), ('Bresaola'), ('Bruschette');

-- =====================================
-- AJOUT DES RELATIONS AVEC LES MENUS 
-- menu <-> menu_tags
-- =====================================
-- Menu 1
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 1, id FROM menu_tags WHERE tag IN ('Foie gras', 'Magret', 'Truffe');
-- Menu 2
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 2, id FROM menu_tags WHERE tag IN ('Saumon fumé', 'Dinde farcie', 'Bûche');

-- Menu 3
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 3, id FROM menu_tags WHERE tag IN ('Convivial', 'Terroir', 'Chapon');

-- Menu 4
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 4, id FROM menu_tags WHERE tag IN ('Végétarien', 'Épices', 'Champignons');

-- Menu 5
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 5, id FROM menu_tags WHERE tag IN ('Alsace', 'Choucroute', 'Bredele');

-- Menu 6
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 6, id FROM menu_tags WHERE tag IN ('Truffe', 'Confit', 'Périgord');

-- Menu 7
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 7, id FROM menu_tags WHERE tag IN ('Vegan', 'BIO', 'Épices de Noël');

-- Menu 8
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 8, id FROM menu_tags WHERE tag IN ('Caviar', 'Homard', 'Prestige');

-- Menu 9
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 9, id FROM menu_tags WHERE tag IN ('Agneau', 'Légumes primeurs', 'Agrumes');

-- Menu 10
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 10, id FROM menu_tags WHERE tag IN ('Saumon', 'Primeurs', 'Chocolat');

-- Menu 11
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 11, id FROM menu_tags WHERE tag IN ('Provence', 'Agneau', 'Lavande');

-- Menu 12
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 12, id FROM menu_tags WHERE tag IN ('Famille', 'Enfants', 'Chocolat');

-- Menu 15
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 15, id FROM menu_tags WHERE tag IN ('Solo', 'Chocolat');

-- Menu 23
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 23, id FROM menu_tags WHERE tag IN ('Solo', 'Viande', 'Bœuf');

-- Menu 24
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 24, id FROM menu_tags WHERE tag IN ('Solo', 'Viande', 'Magret');

-- Menu 25
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 25, id FROM menu_tags WHERE tag IN ('Solo', 'Sud-Ouest', 'Gastronomique');

-- Menu 26
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 26, id FROM menu_tags WHERE tag IN ('Solo', 'Champignons', 'Épices');

-- Menu 27
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 27, id FROM menu_tags WHERE tag IN ('Mariage', 'Homard', 'Verrines');

-- Menu 28
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 28, id FROM menu_tags WHERE tag IN ('Événement', 'Anniversaire', 'Élégance');

-- Menu 29
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 29, id FROM menu_tags WHERE tag IN ('Séminaire', 'Convivial', 'Terroir');

-- Menu 30
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 30, id FROM menu_tags WHERE tag IN ('Anniversaire', 'Foie gras', 'Saumon fumé');

-- Menu 31 à 35 (Mexicain)
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 31, id FROM menu_tags WHERE tag IN ('Mexicain', 'BIO', 'Agrumes');
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 32, id FROM menu_tags WHERE tag IN ('Mexicain', 'Légumes primeurs');
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 33, id FROM menu_tags WHERE tag IN ('Épices', 'Famille');
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 34, id FROM menu_tags WHERE tag IN ('Provence', 'BIO');
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 35, id FROM menu_tags WHERE tag IN ('Élégance', 'Viande');

-- Menu 36 à 40 (Asiatique)
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 36, id FROM menu_tags WHERE tag IN ('Tofu', '100% Végétal');
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 37, id FROM menu_tags WHERE tag IN ('Chitake', 'Champignons');
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 38, id FROM menu_tags WHERE tag IN ('BIO', 'Champignons');
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 39, id FROM menu_tags WHERE tag IN ('Asiatique', 'Vegan');
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 40, id FROM menu_tags WHERE tag IN ('Edamame', 'Tokimeki');

-- Menu 41 à 42 (Jour de l'An)
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 41, id FROM menu_tags WHERE tag IN ('Huîtres', 'Truffe', 'Dinde farcie');
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 42, id FROM menu_tags WHERE tag IN ('Prestige', 'Homard', 'Caviar');

-- Menu 43 à 45 (Italien)
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 43, id FROM menu_tags WHERE tag IN ('Pate fraiche', 'gorgonzola');
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 44, id FROM menu_tags WHERE tag IN ('parmigiana', 'carpaccio', 'Gnocchi');
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 45, id FROM menu_tags WHERE tag IN ('pepperoni' , 'Bresaola' , 'Bruschette');

-- Menu 46 à 47 (Français)
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 46, id FROM menu_tags WHERE tag IN ('Sud-Ouest', 'Confit', 'Agrumes');
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 47, id FROM menu_tags WHERE tag IN ('Légumes BIO', 'Terroir', 'Gascogne');

-- Menu 48 à 51 (Classique)
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 48, id FROM menu_tags WHERE tag IN ('Canard', 'Huîtres', 'Bordeaux');
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 49, id FROM menu_tags WHERE tag IN ('Convivial', 'Légumes BIO', 'Sans viande');
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 50, id FROM menu_tags WHERE tag IN ('Champignons', '100% Végétal', 'BIO');
INSERT INTO menu_tag (menu_id, menutags_id)
SELECT 51, id FROM menu_tags WHERE tag IN ('Chapon', 'Gascogne', 'Canard');

-- =====================================
-- COMMANDES
-- utilisateur_id 3 = client
-- =====================================
INSERT IGNORE INTO commande (
    commande_id,
    numero_commande,
    date_commande,
    date_prestation,
    statut,
    pret_materiel,
    restitution_materiel,
    date_statut_retour_materiel,
    date_statut_livree,
    mail_penalite_envoye,
    heure_livraison,
    prix_menu,
    nombre_personne,
    prix_livraison,
    montant_acompte,
    prix_total,
    distance_km,
    motif_annulation,
    montant_rembourse,
    adresse_livraison,
    ville_livraison,
    utilisateur_id,
    menu_id
) VALUES
(1, 'CMD-001', '2026-01-01 10:00:00', '2026-01-15', 'Terminée', 1, 1, NULL, '2026-01-15 00:00:00', 0, '12:00:00', 150, 10, 20, 50, 120, 10, NULL, 0, '1 rue Exemple', 'Paris', 3, 1),
(2, 'CMD-002', '2026-01-05 10:00:00', '2026-01-20', 'Acceptée', 1, 1, NULL, '2026-01-20 00:00:00', 0, '12:00:00', 200, 15, 25, 60, 165, 15, NULL, 0, '2 rue Exemple', 'Lyon', 3, 1),
(3, 'CMD-003', '2026-02-10 10:00:00', '2026-02-25', 'En livraison', 1, 1, NULL, NULL, 0, '12:00:00', 180, 12, 20, 40, 160, 12, NULL, 0, '3 rue Exemple', 'Marseille', 3, 1),
(4, 'CMD-004', '2026-02-15 10:00:00', '2026-02-28', 'En attente', 1, 1, NULL, NULL, 0, '12:00:00', 250, 20, 30, 70, 210, 20, NULL, 0, '4 rue Exemple', 'Toulouse', 3, 1),
(5, 'CMD-005', '2026-02-15 10:00:00', '2026-02-28', 'En attente de retour', 0, 0, NULL, NULL, 0, '12:00:00', 250, 20, 30, 70, 210, 20, NULL, 0, '5 rue Exemple', 'Nice', 3, 1),
(6, 'CMD-006', '2026-02-15 10:00:00', '2026-02-28', 'En attente', 1, 1, NULL, NULL, 0, '12:00:00', 250, 20, 30, 70, 210, 20, NULL, 0, '6 rue Exemple', 'Bordeaux', 3, 1),
(7, 'CMD-TEST01', '2026-03-17 17:01:54', '2026-04-20', 'EN_ATTENTE', 0, 0, NULL, NULL, 0, '12:30:00', 120, 0, 0, 36, 84, 5, NULL, NULL, '12 rue des fleurs', 'Bordeaux', 1, 1);

-- ============================================
-- MISE A JOUR POUR CORRECTION DE BUG
-- ============================================
UPDATE avis SET statut = 'en_attente' WHERE statut = 'En attente';
UPDATE avis SET statut = 'validé' WHERE statut = 'Publié';
UPDATE avis SET statut = 'validé' WHERE statut = 'Validé';
UPDATE avis SET statut = 'refusé' WHERE statut = 'Refusé';
-- Livrée passe à Livré
UPDATE suivi_commande SET statut = 'Livré' WHERE statut = 'Livrée';
-- En attente retour materiel mise à jour de la valeur complète avec accents
UPDATE suivi_commande SET statut = 'En attente de retour matériel' WHERE statut = 'En attente retour materiel';
-- Commande reçue pas dans le cycle de vie, on remplace par En attente
UPDATE suivi_commande SET statut = 'En attente' WHERE statut = 'Commande reçue';
UPDATE commande SET statut = 'En attente de retour matériel' WHERE commande_id = 5;
UPDATE commande SET statut = 'En attente' WHERE commande_id = 7;
UPDATE commande SET statut = 'Terminée' WHERE commande_id = 2; 

UPDATE commande SET restitution_materiel = '0' WHERE commande_id = 3;
UPDATE commande SET restitution_materiel = '0' WHERE commande_id = 4;

UPDATE commande SET pret_materiel = '1' WHERE commande_id = 5;

UPDATE commande SET numero_commande = 'CMD-007' WHERE commande_id = 7;
UPDATE commande SET numero_commande = 'CMD-008' WHERE commande_id = 8;
UPDATE commande SET numero_commande = 'CMD-009' WHERE commande_id = 9;
UPDATE commande SET numero_commande = 'CMD-010' WHERE commande_id = 10;
UPDATE commande SET numero_commande = 'CMD-011' WHERE commande_id = 11;
UPDATE commande SET numero_commande = 'CMD-012' WHERE commande_id = 12;
UPDATE commande SET numero_commande = 'CMD-013' WHERE commande_id = 13;
UPDATE commande SET numero_commande = 'CMD-014' WHERE commande_id = 14;
UPDATE commande SET numero_commande = 'CMD-015' WHERE commande_id = 15;
UPDATE commande SET numero_commande = 'CMD-016' WHERE commande_id = 16;
UPDATE commande SET numero_commande = 'CMD-017' WHERE commande_id = 17;
UPDATE commande SET numero_commande = 'CMD-018' WHERE commande_id = 18;
UPDATE commande SET numero_commande = 'CMD-019' WHERE commande_id = 19;
UPDATE commande SET numero_commande = 'CMD-020' WHERE commande_id = 20;
UPDATE commande SET numero_commande = 'CMD-021' WHERE commande_id = 21;
UPDATE commande SET numero_commande = 'CMD-022' WHERE commande_id = 22;
UPDATE commande SET statut = 'En attente du retour matériel' WHERE commande_id = 5;


-- ============================================
-- RÉACTIVATION DES CONTRAINTES
-- ============================================
SET FOREIGN_KEY_CHECKS = 1;