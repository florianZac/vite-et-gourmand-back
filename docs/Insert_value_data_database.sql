
-- ─── TABLES REQUISES ──────────────────────────────────────────
-- Assurez-vous que ces tables existent avant d'exécuter ce script.
-- Adaptez les types et contraintes selon votre SGBD (MySQL/PostgreSQL/MariaDB).

/*
CREATE TABLE menus (
    id VARCHAR(50) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    theme VARCHAR(50) NOT NULL,
    regime VARCHAR(50) NOT NULL,
    min_persons INT NOT NULL DEFAULT 1,
    price_per_person DECIMAL(10,2) NOT NULL,
    stock_available BOOLEAN NOT NULL DEFAULT TRUE,
    conditions TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE dishes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    menu_id VARCHAR(50) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    course ENUM('Entrée','Plat','Dessert') NOT NULL,
    allergens TEXT,
    FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE
);

CREATE TABLE menu_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    menu_id VARCHAR(50) NOT NULL,
    image_url TEXT NOT NULL,
    position INT NOT NULL DEFAULT 0,
    FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE
);

CREATE TABLE menu_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    menu_id VARCHAR(50) NOT NULL,
    tag VARCHAR(100) NOT NULL,
    FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE
);
*/

-- ══════════════════════════════════════════════════════════════
--  MENUS
-- ══════════════════════════════════════════════════════════════

-- ─── NOËL ─────────────────────────────────────────────────────

INSERT INTO menus (id, title, description, theme, regime, min_persons, price_per_person, stock_available, conditions) VALUES
('menu-001', 'Festin du Réveillon', 'Un menu de fête somptueux mariant les saveurs traditionnelles du Sud-Ouest aux touches contemporaines de nos chefs étoilés.', 'Noël', 'Classique', 10, 58.00, TRUE, 'Commande minimum 10 personnes. Réservation au moins 7 jours avant la prestation. Accomptes de 30% à la commande.'),
('menu-noel-02', 'Réveillon Étoilé', 'Un repas de Noël gastronomique digne des plus grandes tables, avec des produits d''exception sélectionnés par nos chefs.', 'Noël', 'Classique', 12, 72.00, TRUE, 'Minimum 12 personnes. Réservation 10 jours avant. Acompte 40%.'),
('menu-noel-03', 'Noël en Famille', 'Un menu chaleureux et généreux pensé pour rassembler les grandes tablées autour des saveurs réconfortantes des fêtes.', 'Noël', 'Classique', 8, 44.00, TRUE, 'Minimum 8 personnes. Réservation 5 jours avant. Menu disponible du 20 au 31 décembre.'),
('menu-noel-04', 'Noël Végétarien Festif', 'Les saveurs de Noël sans compromis éthique : un menu végétarien raffiné aux couleurs et aux arômes des fêtes.', 'Noël', 'Végétarien', 6, 40.00, TRUE, 'Minimum 6 personnes. Réservation 5 jours avant. Personnalisation possible.'),
('menu-noel-05', 'Réveillon Alsacien', 'Partez pour l''Alsace le temps d''un repas : bredele, choucroute festive et vins d''Alsace pour réchauffer les cœurs.', 'Noël', 'Classique', 10, 50.00, TRUE, 'Minimum 10 personnes. Réservation 7 jours avant. Disponible en décembre uniquement.'),
('menu-noel-06', 'Noël du Périgord', 'Un voyage gourmand au cœur du Périgord : truffes, foie gras, confit de canard pour un Noël de caractère.', 'Noël', 'Carnivore', 8, 68.00, FALSE, 'Minimum 8 personnes. Réservation 14 jours avant en raison de l''approvisionnement en truffes. Acompte 50%.'),
('menu-noel-07', 'Noël Vegan Enchanté', 'Un Noël 100% végétal, festif et gourmand : des plats inventifs qui font honneur aux meilleurs produits de la terre.', 'Noël', 'Vegan', 6, 38.00, TRUE, 'Minimum 6 personnes. Réservation 5 jours avant. Totalement exempt de produits animaux.'),
('menu-noel-08', 'Nuit de Noël Prestige', 'Le summum du luxe pour une nuit de Noël inoubliable : caviar, homard, truffes et champagne pour couronner l''année.', 'Noël', 'Classique', 15, 95.00, TRUE, 'Minimum 15 personnes. Réservation 21 jours avant. Acompte 60%. Service champagne inclus.');

-- ─── PÂQUES ───────────────────────────────────────────────────

INSERT INTO menus (id, title, description, theme, regime, min_persons, price_per_person, stock_available, conditions) VALUES
('menu-002', 'Printemps de Pâques', 'Célébrez Pâques avec légèreté : saveurs d''agrumes, herbes fraîches et douceurs printanières de nos producteurs locaux.', 'Pâques', 'Classique', 8, 42.00, TRUE, 'Commande minimum 8 personnes. Réservation au moins 5 jours avant. Disponible du 1er au 15 avril uniquement.'),
('menu-paques-02', 'Pâques en Jardin', 'Un menu printanier aux saveurs fleuries pour fêter Pâques en plein air avec famille et amis.', 'Pâques', 'Classique', 6, 38.00, TRUE, 'Minimum 6 personnes. Réservation 4 jours avant. Disponible mars-avril.'),
('menu-paques-03', 'Pâques Végétarien Fleuri', 'Célébrez Pâques sans viande avec un menu végétarien coloré aux légumes de printemps et fromages de caractère.', 'Pâques', 'Végétarien', 4, 34.00, TRUE, 'Minimum 4 personnes. Réservation 3 jours avant.'),
('menu-paques-04', 'Table de Pâques Provençale', 'Les saveurs du Midi pour célébrer Pâques : herbes de Provence, agneau du pays et douceurs au miel de lavande.', 'Pâques', 'Classique', 8, 46.00, TRUE, 'Minimum 8 personnes. Réservation 5 jours avant. Disponible avril-mai.'),
('menu-paques-05', 'Pâques Vegan Printanier', 'Un Pâques végétal et joyeux : légumes de saison, protéines végétales et dessert sans produit animal.', 'Pâques', 'Vegan', 4, 32.00, TRUE, 'Minimum 4 personnes. Réservation 3 jours avant.'),
('menu-paques-06', 'Grande Table de Pâques', 'Un repas de fête généreux pour les grandes familles, avec des plats traditionnels revisités et copieux.', 'Pâques', 'Classique', 12, 52.00, TRUE, 'Minimum 12 personnes. Réservation 7 jours avant. Livraison gratuite dès 12 personnes.'),
('menu-paques-07', 'Pâques Basque', 'Fêtez Pâques à la basque : piperade, agneau de lait et gâteau basque pour un repas haut en couleur.', 'Pâques', 'Classique', 6, 44.00, FALSE, 'Minimum 6 personnes. Réservation 5 jours avant. Menu saisonnier disponible à Pâques.'),
('menu-paques-08', 'Pâques des Enfants', 'Un menu festif et ludique pensé pour les petits : des saveurs douces, des présentations amusantes et beaucoup de chocolat !', 'Pâques', 'Classique', 6, 28.00, TRUE, 'Minimum 6 personnes. Réservation 3 jours avant. Idéal pour repas famille avec enfants.');

-- ─── CLASSIQUE ────────────────────────────────────────────────

INSERT INTO menus (id, title, description, theme, regime, min_persons, price_per_person, stock_available, conditions) VALUES
('menu-003', 'Classique Bordelais', 'L''essence de la gastronomie bordelaise : produits nobles du terroir, vins d''exception, tradition savoir-faire depuis 25 ans.', 'Classique', 'Classique', 6, 48.00, TRUE, 'Commande minimum 6 personnes. Réservation 4 jours à l''avance. Accord mets-vins disponible sur demande.'),
('menu-005', 'Menu Végétarien du Jardin', 'Une exploration savoureuse et colorée des légumes de saison, des saveurs végétales raffinées qui font honneur au terroir.', 'Classique', 'Végétarien', 4, 36.00, TRUE, 'Commande minimum 4 personnes. Réservation 3 jours à l''avance. Adaptation pour régimes spéciaux sur demande.'),
('menu-006', 'Saveurs Vegan & Bien-être', 'Un menu 100% végétal élaboré avec soin pour prouver que plaisir gastronomique et éthique alimentaire font bon ménage.', 'Classique', 'Vegan', 4, 34.00, FALSE, 'Commande minimum 4 personnes. Réservation 3 jours à l''avance. Menu non disponible actuellement — revenez bientôt !'),
('menu-classique-04', 'Terroir Gascon', 'Plongez dans la générosité de la cuisine gasconne : cochonnailles, canard et armagnac pour un repas de caractère.', 'Classique', 'Carnivore', 6, 46.00, TRUE, 'Minimum 6 personnes. Réservation 4 jours avant.'),
('menu-classique-05', 'Bistrot Parisien', 'L''âme du bistrot parisien dans votre assiette : œuf mayonnaise, steak frites et profiteroles pour un déjeuner authentique.', 'Classique', 'Classique', 4, 38.00, TRUE, 'Minimum 4 personnes. Réservation 2 jours avant.'),
('menu-classique-06', 'Menu Végétarien Méditerranéen', 'Un voyage méditerranéen sans viande : saveurs ensoleillées, légumes grillés et herbes aromatiques.', 'Classique', 'Végétarien', 4, 34.00, TRUE, 'Minimum 4 personnes. Réservation 3 jours avant.'),
('menu-classique-07', 'Vegan Monde', 'Un tour du monde végétal en trois plats : mezze libanais, curry thaï et dessert japonais pour les amateurs de voyage.', 'Classique', 'Vegan', 4, 36.00, TRUE, 'Minimum 4 personnes. Réservation 3 jours avant.'),
('menu-classique-08', 'Brasserie Chic', 'La finesse d''une brasserie de prestige : plateau de fruits de mer, sole meunière et île flottante pour un déjeuner en élégance.', 'Classique', 'Classique', 6, 54.00, TRUE, 'Minimum 6 personnes. Réservation 5 jours avant. Fruits de mer soumis à disponibilité.');

-- ─── MENUS SOLO ───────────────────────────────────────────────

INSERT INTO menus (id, title, description, theme, regime, min_persons, price_per_person, stock_available, conditions) VALUES
('menu-solo-viande-01', 'Solo Carnivore – Le Bœuf du Boucher', 'Un déjeuner solo généreux et savoureux pour les amateurs de viande : pièce de bœuf sélectionnée par notre boucher partenaire, cuisson à la perfection.', 'Classique', 'Carnivore', 1, 32.00, TRUE, 'Menu pour 1 personne uniquement. Livraison possible. Commande la veille avant 18h.'),
('menu-solo-viande-02', 'Solo Prestige – Magret & Foie Gras', 'Un repas solo d''exception pour se faire plaisir : les grandes saveurs du Sud-Ouest dans une assiette élaborée pour une seule personne.', 'Classique', 'Carnivore', 1, 38.00, TRUE, 'Menu pour 1 personne uniquement. Livraison ou click & collect. Commande la veille avant 18h.'),
('menu-solo-01', 'Solo Gourmet – Le Midi du Chef', 'Pour se faire plaisir seul sans compromis : un menu déjeuner gastronomique spécialement conçu pour une seule personne.', 'Classique', 'Classique', 1, 28.00, TRUE, 'Menu pour 1 personne uniquement. Livraison possible. Commande la veille avant 18h.'),
('menu-solo-02', 'Solo Végétarien – Déjeuner Zen', 'Un déjeuner végétarien équilibré et savoureux pour une seule personne qui prend soin d''elle au quotidien.', 'Classique', 'Végétarien', 1, 24.00, TRUE, 'Menu pour 1 personne uniquement. Livraison ou click & collect. Commande la veille avant 18h.');

-- ─── ÉVÉNEMENT ────────────────────────────────────────────────

INSERT INTO menus (id, title, description, theme, regime, min_persons, price_per_person, stock_available, conditions) VALUES
('menu-004', 'Mariage & Événements Premium', 'Le menu d''exception pour vos moments inoubliables. Raffinement absolu, service sur-mesure, présentation gastronomique.', 'Événement', 'Classique', 20, 85.00, TRUE, 'Minimum 20 personnes. Réservation obligatoire 14 jours avant. Accompte de 50%. Service en salle possible.'),
('menu-event-02', 'Cocktail Dînatoire Élégant', 'Des bouchées raffinées et des verrines créatives pour animer vos cocktails professionnels ou soirées mondaines.', 'Événement', 'Classique', 20, 45.00, TRUE, 'Minimum 20 personnes. Réservation 10 jours avant. Service debout avec plateau.'),
('menu-event-03', 'Séminaire d''Entreprise', 'Restaurez vos équipes avec panache lors de vos séminaires et conventions : un menu professionnel, efficace et délicieux.', 'Événement', 'Classique', 15, 38.00, TRUE, 'Minimum 15 personnes. Réservation 7 jours avant. Service en buffet ou à l''assiette selon demande.'),
('menu-event-04', 'Anniversaire Festif', 'Rendez votre anniversaire inoubliable avec ce menu festif : bulles, saveurs joyeuses et gâteau personnalisé inclus.', 'Événement', 'Classique', 10, 55.00, TRUE, 'Minimum 10 personnes. Réservation 7 jours avant. Gâteau personnalisé inclus.'),
('menu-event-05', 'Réception de Mariage Végétarien', 'Pour les mariés soucieux de l''éthique : un menu de mariage élégant, sans viande, aux saveurs délicates et raffinées.', 'Événement', 'Végétarien', 20, 62.00, TRUE, 'Minimum 20 personnes. Réservation 14 jours avant. Acompte 50%. Service en salle disponible.'),
('menu-event-06', 'Gala de Prestige', 'Pour vos galas, remises de prix et soirées d''exception : un menu qui incarne le luxe à la française dans toute sa splendeur.', 'Événement', 'Classique', 30, 98.00, TRUE, 'Minimum 30 personnes. Réservation 21 jours avant. Acompte 60%. Service en tenue de soirée.'),
('menu-event-07', 'Baptême & Communion', 'Un menu familial festif pour célébrer les grandes étapes de vie : saveurs douces et conviviales, décor élégant.', 'Événement', 'Classique', 15, 42.00, TRUE, 'Minimum 15 personnes. Réservation 7 jours avant. Menu adapté aux enfants disponible.'),
('menu-event-08', 'Événement Vegan Premium', 'Un événement végétal et chic : démontrez qu''un repas de réception peut être haut de gamme et respectueux du vivant.', 'Événement', 'Vegan', 15, 56.00, TRUE, 'Minimum 15 personnes. Réservation 10 jours avant. Service traiteur haut de gamme.');


-- ══════════════════════════════════════════════════════════════
--  PLATS (DISHES)
-- ══════════════════════════════════════════════════════════════

-- ─── NOËL ─────────────────────────────────────────────────────

INSERT INTO dishes (menu_id, name, description, course, allergens) VALUES
-- Festin du Réveillon
('menu-001', 'Velouté de châtaigne et foie gras poêlé', 'Crème onctueuse de châtaigne d''Ardèche, escalope de foie gras dorée, huile de truffe', 'Entrée', 'Lait, Gluten'),
('menu-001', 'Magret de canard aux cèpes', 'Magret rosé, poêlée de cèpes, jus corsé au Madiran, gratin dauphinois', 'Plat', 'Lait'),
('menu-001', 'Bûche à l''Armagnac et marrons glacés', 'Mousse légère à l''Armagnac, éclats de marrons glacés, tuile caramel', 'Dessert', 'Œufs, Lait, Gluten'),
-- Réveillon Étoilé
('menu-noel-02', 'Saumon fumé maison, blinis et crème citronnée', 'Saumon fumé à froid en atelier, blinis moelleux, crème fraîche citron-aneth', 'Entrée', 'Poisson, Gluten, Lait, Œufs'),
('menu-noel-02', 'Dinde de Noël farcie aux marrons et foie gras', 'Dinde label rouge, farce aux marrons et foie gras, sauce aux cèpes', 'Plat', 'Gluten, Lait'),
('menu-noel-02', 'Bûche Montblanc revisitée', 'Crème de marrons, meringue croustillante, gelée de cassis, décorations en sucre', 'Dessert', 'Œufs, Lait, Gluten'),
-- Noël en Famille
('menu-noel-03', 'Verrines de crevettes et avocat', 'Crevettes roses, guacamole maison, citron vert, piment d''Espelette', 'Entrée', 'Crustacés'),
('menu-noel-03', 'Chapon rôti aux légumes d''hiver', 'Chapon fermier, légumes racines confits, jus réduit au vin blanc', 'Plat', 'Lait'),
('menu-noel-03', 'Charlotte aux fraises des bois', 'Biscuits cuillère, mousse mascarpone, fraises des bois, coulis', 'Dessert', 'Œufs, Lait, Gluten'),
-- Noël Végétarien Festif
('menu-noel-04', 'Soupe de courge butternut épicée', 'Courge rôtie, lait de coco, gingembre, graines de courge grillées', 'Entrée', 'Fruits à coque'),
('menu-noel-04', 'Wellington de légumes d''hiver', 'Pithiviers feuilleté aux champignons, épinards, fromage de chèvre, sauce aux herbes', 'Plat', 'Gluten, Lait, Œufs'),
('menu-noel-04', 'Mousse au chocolat noir et orange confite', 'Chocolat noir 70%, zestes d''orange confite, éclats de noisettes', 'Dessert', 'Œufs, Lait, Fruits à coque'),
-- Réveillon Alsacien
('menu-noel-05', 'Foie gras d''Alsace au Gewurztraminer', 'Terrine de foie gras, gelée de Gewurztraminer, brioche toastée', 'Entrée', 'Gluten, Œufs, Lait'),
('menu-noel-05', 'Choucroute festive aux trois viandes', 'Choucroute artisanale, saucisse de Strasbourg, jarret de porc, lard fumé', 'Plat', 'Gluten'),
('menu-noel-05', 'Bredele et strudel aux pommes', 'Assortiment de bredele maison, strudel chaud pomme-cannelle, crème anglaise', 'Dessert', 'Gluten, Lait, Œufs, Fruits à coque'),
-- Noël du Périgord
('menu-noel-06', 'Œuf en cocotte aux truffes noires', 'Œuf fermier en cocotte, lamelles de truffe noire du Périgord, crème et mouillettes', 'Entrée', 'Œufs, Lait, Gluten'),
('menu-noel-06', 'Confit de canard maison, sarladaise', 'Cuisse confite maison, pommes sarladaises à l''ail et au persil', 'Plat', 'Lait'),
('menu-noel-06', 'Fondant au chocolat et glace truffe', 'Coulant au chocolat noir, glace à la truffe noire, caramel fleur de sel', 'Dessert', 'Œufs, Lait, Gluten'),
-- Noël Vegan Enchanté
('menu-noel-07', 'Velouté de panais aux épices de Noël', 'Panais rôti, cannelle, muscade, lait d''amande, croûtons à l''huile d''olive', 'Entrée', 'Fruits à coque, Gluten'),
('menu-noel-07', 'Rôti de seitan aux herbes et légumes confits', 'Seitan maison, carottes et panais confits, jus de légumes corsé', 'Plat', 'Gluten'),
('menu-noel-07', 'Bûche vegan chocolat-noisette', 'Génoise vegan au cacao, ganache noisette, décor en chocolat noir', 'Dessert', 'Gluten, Fruits à coque'),
-- Nuit de Noël Prestige
('menu-noel-08', 'Royale de caviar et homard en gelée', 'Caviar d''Aquitaine, médaillon de homard, gelée de champagne', 'Entrée', 'Crustacés, Poisson'),
('menu-noel-08', 'Filet de bœuf Rossini, sauce Périgueux', 'Filet de bœuf, escalope de foie gras, truffe noire, sauce Périgueux', 'Plat', 'Lait, Gluten'),
('menu-noel-08', 'Bûche Ispahan (framboise, rose, litchi)', 'Création revisitée : mousse rose, litchi et framboise, glaçage nacré', 'Dessert', 'Œufs, Lait, Gluten, Fruits à coque');

-- ─── PÂQUES ───────────────────────────────────────────────────

INSERT INTO dishes (menu_id, name, description, course, allergens) VALUES
-- Printemps de Pâques
('menu-002', 'Asperges blanches sauce mousseline', 'Asperges des Landes tièdes, sauce mousseline à l''estragon, œuf poché', 'Entrée', 'Œufs, Lait'),
('menu-002', 'Gigot d''agneau de lait rôti aux herbes', 'Agneau du Périgord, ail confit, herbes de Provence, légumes primeurs', 'Plat', ''),
('menu-002', 'Tarte citron meringuée façon grand-mère', 'Pâte sucrée croustillante, crème de citron vert, meringue italienne flambée', 'Dessert', 'Œufs, Gluten, Lait'),
-- Pâques en Jardin
('menu-paques-02', 'Tartare de saumon citron-aneth', 'Saumon Atlantique, câpres, aneth frais, zeste de citron, toast de pain de seigle', 'Entrée', 'Poisson, Gluten'),
('menu-paques-02', 'Carré d''agneau en croûte de pistaches', 'Carré d''agneau rosé, croûte pistache-persil, jus réduit au romarin, haricots verts', 'Plat', 'Fruits à coque'),
('menu-paques-02', 'Œufs en chocolat en surprise', 'Coques en chocolat Valrhona, mousse pralinée, éclats de dragées', 'Dessert', 'Lait, Fruits à coque, Œufs'),
-- Pâques Végétarien Fleuri
('menu-paques-03', 'Salade d''asperges et fromage de chèvre frais', 'Asperges vertes et blanches, chèvre frais, fleurs de capucines, vinaigrette au miel', 'Entrée', 'Lait'),
('menu-paques-03', 'Tarte tatin aux légumes printaniers', 'Petits pois, carottes nouvelles, tomates cerise confites, fromage feta', 'Plat', 'Gluten, Lait, Œufs'),
('menu-paques-03', 'Nid de Pâques en meringue', 'Pavlova aux fruits rouges, crème montée à la vanille, œufs en sucre colorés', 'Dessert', 'Œufs, Lait'),
-- Table de Pâques Provençale
('menu-paques-04', 'Anchoïade et légumes du soleil crus', 'Anchoïade maison, crudités colorées, olives de Provence, tapenade', 'Entrée', 'Poisson, Mollusques'),
('menu-paques-04', 'Épaule d''agneau confite 7 heures', 'Agneau confit aux herbes de Provence, gratin de courgettes et tomates', 'Plat', 'Lait'),
('menu-paques-04', 'Calisson glacé au miel de lavande', 'Glace au miel de lavande de Haute-Provence, coulis de fruits rouges', 'Dessert', 'Fruits à coque, Œufs, Lait'),
-- Pâques Vegan Printanier
('menu-paques-05', 'Gaspacho vert aux herbes fraîches', 'Concombre, courgette, épinards, menthe, avocat, huile d''olive extra vierge', 'Entrée', ''),
('menu-paques-05', 'Lentilles du Puy mijotées aux légumes racines', 'Lentilles vertes AOP, carottes, navets, herbes fraîches, cumin', 'Plat', ''),
('menu-paques-05', 'Fondant au chocolat noir vegan', 'Chocolat noir 70%, compote de pomme, huile de coco, coulis de framboise', 'Dessert', 'Gluten'),
-- Grande Table de Pâques
('menu-paques-06', 'Terrine de campagne printanière', 'Terrine de veau aux herbes, cornichons, pain de campagne grillé', 'Entrée', 'Gluten'),
('menu-paques-06', 'Blanquette de veau à l''ancienne', 'Tendron de veau, carottes, champignons de Paris, sauce crémeuse', 'Plat', 'Lait, Œufs'),
('menu-paques-06', 'Fraisier classique façon pâtisserie', 'Génoise moelleuse, crème mousseline vanille, fraises Gariguette', 'Dessert', 'Gluten, Lait, Œufs'),
-- Pâques Basque
('menu-paques-07', 'Piperade basque et jambon de Bayonne', 'Piperade traditionnelle, tranches fines de jambon de Bayonne AOP', 'Entrée', ''),
('menu-paques-07', 'Agneau de lait du Pays Basque rôti', 'Agneau entier rôti au four, pommes de terre à l''ail, persil plat', 'Plat', ''),
('menu-paques-07', 'Gâteau basque à la cerise noire d''Itxassou', 'Pâte sablée, confiture de cerises noires d''Itxassou AOP', 'Dessert', 'Gluten, Lait, Œufs'),
-- Pâques des Enfants
('menu-paques-08', 'Mini-quiches cocottes printanières', 'Petites quiches légumes-fromage, carottes râpées et maïs en salade', 'Entrée', 'Gluten, Lait, Œufs'),
('menu-paques-08', 'Poulet rôti de Pâques et pommes grenailles', 'Poulet fermier doré, pommes grenailles rôties au thym, haricots verts', 'Plat', ''),
('menu-paques-08', 'Chasse aux œufs en chocolat au dessert', 'Assortiment d''œufs en chocolat au lait, blanc et noir, décor festif', 'Dessert', 'Lait, Fruits à coque, Œufs, Gluten');

-- ─── CLASSIQUE ────────────────────────────────────────────────

INSERT INTO dishes (menu_id, name, description, course, allergens) VALUES
-- Classique Bordelais
('menu-003', 'Huîtres du Bassin d''Arcachon', 'Douzaine d''huîtres N°2, mignonette de vinaigre, citron, pain de seigle beurré', 'Entrée', 'Mollusques, Gluten, Lait'),
('menu-003', 'Entrecôte bordelaise à la moelle', 'Entrecôte de bœuf charolais, sauce bordelaise au Merlot, frites maison, salade', 'Plat', 'Lait, Céleri'),
('menu-003', 'Cannelés bordelais caramel-rhum', 'Cannelés artisanaux, caramel ambré, crème anglaise à la vanille Bourbon', 'Dessert', 'Œufs, Lait, Gluten'),
-- Menu Végétarien du Jardin
('menu-005', 'Gaspacho de tomates du terroir, burrata', 'Tomates anciennes du jardin, burrata crémeuse, basilic frais, huile d''olive AOC', 'Entrée', 'Lait'),
('menu-005', 'Risotto de saison aux champignons sauvages', 'Riz Carnaroli, cèpes et girolles, parmesan AOP 24 mois, truffe d''été', 'Plat', 'Lait'),
('menu-005', 'Mille-feuille fraises et crème pâtissière', 'Pâte feuilletée pur beurre, crème pâtissière vanillée, fraises Mara des Bois', 'Dessert', 'Œufs, Lait, Gluten'),
-- Saveurs Vegan & Bien-être
('menu-006', 'Tartare de betteraves et avocat', 'Betteraves multicolores, avocat, câpres, citron vert, graines de sésame grillées', 'Entrée', 'Sésame'),
('menu-006', 'Curry de pois chiches aux épices douces', 'Pois chiches BIO, lait de coco, curry maison, riz basmati, chutney mangue', 'Plat', ''),
('menu-006', 'Tarte tatin aux pommes et caramel de coco', 'Pommes Golden BIO, caramel au lait de coco, pâte brisée vegan', 'Dessert', 'Gluten'),
-- Terroir Gascon
('menu-classique-04', 'Garbure gasconne revisitée', 'Soupe paysanne aux légumes d''hiver, confit de canard effiloché, pain grillé', 'Entrée', 'Gluten'),
('menu-classique-04', 'Magret de canard sauce au poivre vert', 'Magret saignant, sauce au poivre vert flambée à l''Armagnac, gratin de pommes de terre', 'Plat', 'Lait'),
('menu-classique-04', 'Crème brûlée à l''Armagnac', 'Crème vanillée, caramel soufflé à la torche, éclats de noix de Pécan', 'Dessert', 'Œufs, Lait, Fruits à coque'),
-- Bistrot Parisien
('menu-classique-05', 'Œuf mayonnaise et harengs pommes à l''huile', 'Œufs durs, mayo maison, harengs marinés, pommes de terre tièdes', 'Entrée', 'Œufs, Poisson'),
('menu-classique-05', 'Steak-frites béarnaise', 'Rumsteck charolais, frites bistrot croustillantes, sauce béarnaise maison', 'Plat', 'Lait, Œufs'),
('menu-classique-05', 'Profiteroles sauce chocolat chaud', 'Choux à la crème glacée vanille, sauce chocolat Valrhona chaude', 'Dessert', 'Gluten, Lait, Œufs'),
-- Menu Végétarien Méditerranéen
('menu-classique-06', 'Salade grecque traditionnelle', 'Tomates, concombre, olives Kalamata, feta AOP, origan, huile d''olive crétoise', 'Entrée', 'Lait'),
('menu-classique-06', 'Moussaka végétarienne', 'Aubergines, sauce tomate aux lentilles, béchamel crémeuse, fromage gratiné', 'Plat', 'Lait, Œufs, Gluten'),
('menu-classique-06', 'Baklava aux pistaches et miel de thym', 'Pâte filo, pistaches de Bronte, miel de thym, eau de rose', 'Dessert', 'Gluten, Fruits à coque'),
-- Vegan Monde
('menu-classique-07', 'Mezze libanais vegan', 'Houmous maison, tabboulé au boulghour, falafels croustillants, pain pita', 'Entrée', 'Gluten, Sésame'),
('menu-classique-07', 'Curry thaï de légumes au lait de coco', 'Pâte de curry vert, légumes de saison, lait de coco, riz jasmin, coriandre', 'Plat', ''),
('menu-classique-07', 'Mochi glacé mangue-passion', 'Pâte de riz gluant, glace végétale mangue-passion, coulis de fruits exotiques', 'Dessert', 'Gluten'),
-- Brasserie Chic
('menu-classique-08', 'Mini plateau de fruits de mer', 'Huîtres, crevettes, bulots, sauce mignonette, mayonnaise maison', 'Entrée', 'Mollusques, Crustacés'),
('menu-classique-08', 'Sole meunière au beurre noisette', 'Sole de ligne, beurre noisette, câpres, persil plat, pommes vapeur', 'Plat', 'Poisson, Lait'),
('menu-classique-08', 'Île flottante sauce anglaise vanille', 'Blanc en neige moelleux, crème anglaise à la vanille Bourbon, pralin', 'Dessert', 'Œufs, Lait, Fruits à coque');

-- ─── MENUS SOLO ───────────────────────────────────────────────

INSERT INTO dishes (menu_id, name, description, course, allergens) VALUES
-- Solo Carnivore
('menu-solo-viande-01', 'Carpaccio de bœuf à la truffe et parmesan', 'Fines tranches de bœuf Charolais cru, copeaux de parmesan 24 mois, huile de truffe, roquette', 'Entrée', 'Lait'),
('menu-solo-viande-01', 'Entrecôte grillée, sauce au poivre et frites maison', 'Entrecôte Simmental 250g, sauce au poivre vert, frites croustillantes maison, salade verte', 'Plat', 'Lait'),
('menu-solo-viande-01', 'Fondant au chocolat noir, glace vanille', 'Coulant pur beurre au chocolat noir 70%, boule de glace vanille Bourbon', 'Dessert', 'Œufs, Lait, Gluten'),
-- Solo Prestige
('menu-solo-viande-02', 'Escalope de foie gras poêlée, chutney de figues', 'Foie gras de canard des Landes poêlé, chutney maison aux figues et vinaigre balsamique, brioche toastée', 'Entrée', 'Gluten, Lait, Œufs'),
('menu-solo-viande-02', 'Magret de canard rôti, sauce cerises et purée truffée', 'Magret saignant, réduction de cerises au Maury, purée de pommes de terre à l''huile de truffe', 'Plat', 'Lait'),
('menu-solo-viande-02', 'Crème brûlée à l''Armagnac', 'Crème vanillée onctueuse, caramel soufflé à la torche, éclats de noisettes grillées', 'Dessert', 'Œufs, Lait, Fruits à coque'),
-- Solo Gourmet
('menu-solo-01', 'Velouté du jour et sa garniture', 'Velouté de légumes frais du marché, crème fraîche, croûtons', 'Entrée', 'Lait, Gluten'),
('menu-solo-01', 'Pavé de saumon rôti et légumes de saison', 'Saumon Label Rouge, légumes poêlés, sauce citron-câpres', 'Plat', 'Poisson, Lait'),
('menu-solo-01', 'Moelleux au chocolat et sa crème anglaise', 'Coulant pur beurre, crème anglaise à la vanille, amandes effilées', 'Dessert', 'Œufs, Lait, Gluten, Fruits à coque'),
-- Solo Végétarien
('menu-solo-02', 'Salade de quinoa aux légumes rôtis', 'Quinoa, courgettes et poivrons rôtis, feta émiettée, sauce yaourt menthe', 'Entrée', 'Lait'),
('menu-solo-02', 'Curry de légumes et tofu soyeux', 'Tofu soyeux, légumes de saison, curry doux, riz complet basmati', 'Plat', ''),
('menu-solo-02', 'Panna cotta aux fruits rouges', 'Panna cotta crémeuse à la vanille, coulis de framboise-fraise', 'Dessert', 'Lait');

-- ─── ÉVÉNEMENT ────────────────────────────────────────────────

INSERT INTO dishes (menu_id, name, description, course, allergens) VALUES
-- Mariage & Événements Premium
('menu-004', 'Médaillon de homard breton sauce cardinal', 'Homard Bleu de Bretagne, bisque crémée, caviar d''Aquitaine, micro-herbes', 'Entrée', 'Crustacés, Lait, Poisson'),
('menu-004', 'Filet de bœuf Rossini en croûte', 'Filet Wagyu A5, escalope de foie gras, duxelles de champignons, sauce Périgueux', 'Plat', 'Gluten, Lait'),
('menu-004', 'Entremets Royal Chocolat Framboise', 'Mousse chocolat Valrhona 70%, insert framboise, glaçage miroir, feuille d''or', 'Dessert', 'Œufs, Lait, Gluten, Fruits à coque'),
-- Cocktail Dînatoire
('menu-event-02', 'Assortiment de verrines apéritives', 'Verrine avocat-crevettes, betterave-chèvre, gaspacho tomate-basilic', 'Entrée', 'Crustacés, Lait'),
('menu-event-02', 'Bouchées chaudes et froides premium', 'Mini-brochettes de bœuf, blinis saumon-crème, vol-au-vent champignons', 'Plat', 'Gluten, Lait, Poisson, Œufs'),
('menu-event-02', 'Mignardises et macarons assortis', 'Sélection de macarons fins, chocolats d''artisan, fruits déguisés', 'Dessert', 'Gluten, Lait, Œufs, Fruits à coque'),
-- Séminaire d'Entreprise
('menu-event-03', 'Buffet de salades composées', '3 salades au choix : niçoise, César végétarienne, taboulé oriental', 'Entrée', 'Poisson, Œufs, Gluten'),
('menu-event-03', 'Plat chaud au choix (viande ou poisson)', 'Pavé de bœuf sauce poivre ou filet de dorade aux herbes, pommes de terre vapeur', 'Plat', 'Lait, Poisson'),
('menu-event-03', 'Café gourmand et mignardises', 'Café expresso, mini-tartelettes, financiers, truffes au chocolat', 'Dessert', 'Gluten, Lait, Œufs'),
-- Anniversaire Festif
('menu-event-04', 'Verrine de foie gras et chutney de figues', 'Mousse légère de foie gras, chutney figues-gingembre, brioche toastée', 'Entrée', 'Gluten, Lait, Œufs'),
('menu-event-04', 'Filet de veau en croûte d''herbes', 'Médaillon de veau, croûte persil-noisettes, jus truffe et champignons', 'Plat', 'Gluten, Fruits à coque, Lait'),
('menu-event-04', 'Pièce montée ou gâteau d''anniversaire sur mesure', 'Gâteau personnalisé au parfum choisi, écriture au chocolat, décor festif', 'Dessert', 'Gluten, Lait, Œufs, Fruits à coque'),
-- Réception de Mariage Végétarien
('menu-event-05', 'Cappuccino de champignons et truffe', 'Velouté de champignons des bois, émulsion de lait truffée, chips de parmesan', 'Entrée', 'Lait'),
('menu-event-05', 'Ravioles de champignons sauce au vin blanc', 'Ravioles maison aux champignons forestiers, sauce crème-vin blanc, herbes fraîches', 'Plat', 'Gluten, Lait, Œufs'),
('menu-event-05', 'Pièce montée de choux au praliné', 'Tour de choux craquelin, crème pralinée, fils de caramel dorés', 'Dessert', 'Gluten, Lait, Œufs, Fruits à coque'),
-- Gala de Prestige
('menu-event-06', 'Trio de langoustines bretonnes', 'Langoustines en carpaccio, pochées et en bisque, caviar d''esturgeon', 'Entrée', 'Crustacés, Poisson'),
('menu-event-06', 'Tournedos de bœuf Wagyu et foie gras', 'Wagyu A5, escalope de foie gras poêlée, truffe noire, pomme duchesse', 'Plat', 'Lait, Gluten, Œufs'),
('menu-event-06', 'Symphonie de desserts haute couture', 'Sélection de mignardises : macarons, entremets, petits fours, chocolats fins', 'Dessert', 'Gluten, Lait, Œufs, Fruits à coque'),
-- Baptême & Communion
('menu-event-07', 'Plateau charcuterie-fromages régionaux', 'Sélection de charcuteries du terroir, fromages AOP, confiture de figue, pain artisanal', 'Entrée', 'Lait, Gluten'),
('menu-event-07', 'Poulet rôti fermier et gratin dauphinois', 'Poulet Label Rouge rôti au four, gratin dauphinois crémeux, haricots verts', 'Plat', 'Lait'),
('menu-event-07', 'Pièce montée classique ou gâteau thématique', 'Pièce montée traditionnelle ou gâteau thématique selon la cérémonie', 'Dessert', 'Gluten, Lait, Œufs'),
-- Événement Vegan Premium
('menu-event-08', 'Amuse-bouches vegan créatifs', 'Tartare de pastèque façon thon, shot de gaspacho, tartelette champignons', 'Entrée', 'Gluten, Sésame'),
('menu-event-08', 'Rôti de seitan premium en croûte d''herbes', 'Seitan maison, enrobé d''herbes fraîches, légumes confits, jus de légumes corsé', 'Plat', 'Gluten'),
('menu-event-08', 'Gâteau d''anniversaire vegan chocolat', 'Génoise cacao vegan, ganache chocolat noir, fruits rouges frais, décorations véganes', 'Dessert', 'Gluten, Fruits à coque');


-- ══════════════════════════════════════════════════════════════
--  IMAGES DES MENUS
-- ══════════════════════════════════════════════════════════════

INSERT INTO menu_images (menu_id, image_url, position) VALUES
-- Noël
('menu-001', 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=800', 1),
('menu-001', 'https://images.unsplash.com/photo-1576402187878-974f70c890a5?w=800', 2),
('menu-noel-02', 'https://images.unsplash.com/photo-1608039829572-78524f79c4c7?w=800', 1),
('menu-noel-02', 'https://images.unsplash.com/photo-1482275548304-a58859dc31b7?w=800', 2),
('menu-noel-03', 'https://images.unsplash.com/photo-1606787366850-de6330128bfc?w=800', 1),
('menu-noel-03', 'https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=800', 2),
('menu-noel-04', 'https://images.unsplash.com/photo-1543362906-acfc16c67564?w=800', 1),
('menu-noel-04', 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=800', 2),
('menu-noel-05', 'https://images.unsplash.com/photo-1563245372-f21724e3856d?w=800', 1),
('menu-noel-05', 'https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=800', 2),
('menu-noel-06', 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800', 1),
('menu-noel-06', 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800', 2),
('menu-noel-07', 'https://images.unsplash.com/photo-1540914124281-342587941389?w=800', 1),
('menu-noel-07', 'https://images.unsplash.com/photo-1543362906-acfc16c67564?w=800', 2),
('menu-noel-08', 'https://images.unsplash.com/photo-1559339352-11d035aa65de?w=800', 1),
('menu-noel-08', 'https://images.unsplash.com/photo-1482275548304-a58859dc31b7?w=800', 2),
-- Pâques
('menu-002', 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=800', 1),
('menu-002', 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=800', 2),
('menu-paques-02', 'https://images.unsplash.com/photo-1490818387583-1baba5e638af?w=800', 1),
('menu-paques-02', 'https://images.unsplash.com/photo-1466637574441-749b8f19452f?w=800', 2),
('menu-paques-03', 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=800', 1),
('menu-paques-03', 'https://images.unsplash.com/photo-1490818387583-1baba5e638af?w=800', 2),
('menu-paques-04', 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800', 1),
('menu-paques-04', 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=800', 2),
('menu-paques-05', 'https://images.unsplash.com/photo-1540914124281-342587941389?w=800', 1),
('menu-paques-05', 'https://images.unsplash.com/photo-1543362906-acfc16c67564?w=800', 2),
('menu-paques-06', 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800', 1),
('menu-paques-06', 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=800', 2),
('menu-paques-07', 'https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=800', 1),
('menu-paques-07', 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800', 2),
('menu-paques-08', 'https://images.unsplash.com/photo-1606787366850-de6330128bfc?w=800', 1),
('menu-paques-08', 'https://images.unsplash.com/photo-1490818387583-1baba5e638af?w=800', 2),
-- Classique
('menu-003', 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800', 1),
('menu-003', 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800', 2),
('menu-005', 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=800', 1),
('menu-005', 'https://images.unsplash.com/photo-1540914124281-342587941389?w=800', 2),
('menu-006', 'https://images.unsplash.com/photo-1540914124281-342587941389?w=800', 1),
('menu-006', 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=800', 2),
('menu-classique-04', 'https://images.unsplash.com/photo-1600891964092-4316c288032e?w=800', 1),
('menu-classique-04', 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800', 2),
('menu-classique-05', 'https://images.unsplash.com/photo-1559339352-11d035aa65de?w=800', 1),
('menu-classique-05', 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800', 2),
('menu-classique-06', 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=800', 1),
('menu-classique-06', 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=800', 2),
('menu-classique-07', 'https://images.unsplash.com/photo-1543362906-acfc16c67564?w=800', 1),
('menu-classique-07', 'https://images.unsplash.com/photo-1540914124281-342587941389?w=800', 2),
('menu-classique-08', 'https://images.unsplash.com/photo-1482275548304-a58859dc31b7?w=800', 1),
('menu-classique-08', 'https://images.unsplash.com/photo-1559339352-11d035aa65de?w=800', 2),
-- Solo
('menu-solo-viande-01', 'https://images.unsplash.com/photo-1558030006-450675393462?w=800', 1),
('menu-solo-viande-01', 'https://images.unsplash.com/photo-1600891964092-4316c288032e?w=800', 2),
('menu-solo-viande-02', 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800', 1),
('menu-solo-viande-02', 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=800', 2),
('menu-solo-01', 'https://images.unsplash.com/photo-1565299585323-38d6b0865b47?w=800', 1),
('menu-solo-01', 'https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=800', 2),
('menu-solo-02', 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=800', 1),
('menu-solo-02', 'https://images.unsplash.com/photo-1565299585323-38d6b0865b47?w=800', 2),
-- Événement
('menu-004', 'https://images.unsplash.com/photo-1587899897387-091ebd01a6b2?w=800', 1),
('menu-004', 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800', 2),
('menu-event-02', 'https://images.unsplash.com/photo-1551218808-94e220e084d2?w=800', 1),
('menu-event-02', 'https://images.unsplash.com/photo-1559339352-11d035aa65de?w=800', 2),
('menu-event-03', 'https://images.unsplash.com/photo-1600891964092-4316c288032e?w=800', 1),
('menu-event-03', 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800', 2),
('menu-event-04', 'https://images.unsplash.com/photo-1464349095431-e9a21285b5f3?w=800', 1),
('menu-event-04', 'https://images.unsplash.com/photo-1551218808-94e220e084d2?w=800', 2),
('menu-event-05', 'https://images.unsplash.com/photo-1543362906-acfc16c67564?w=800', 1),
('menu-event-05', 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=800', 2),
('menu-event-06', 'https://images.unsplash.com/photo-1482275548304-a58859dc31b7?w=800', 1),
('menu-event-06', 'https://images.unsplash.com/photo-1587899897387-091ebd01a6b2?w=800', 2),
('menu-event-07', 'https://images.unsplash.com/photo-1606787366850-de6330128bfc?w=800', 1),
('menu-event-07', 'https://images.unsplash.com/photo-1464349095431-e9a21285b5f3?w=800', 2),
('menu-event-08', 'https://images.unsplash.com/photo-1543362906-acfc16c67564?w=800', 1),
('menu-event-08', 'https://images.unsplash.com/photo-1540914124281-342587941389?w=800', 2);


-- ══════════════════════════════════════════════════════════════
--  TAGS DES MENUS
-- ══════════════════════════════════════════════════════════════

INSERT INTO menu_tags (menu_id, tag) VALUES
-- Noël
('menu-001', 'Foie gras'), ('menu-001', 'Magret'), ('menu-001', 'Truffe'),
('menu-noel-02', 'Saumon fumé'), ('menu-noel-02', 'Dinde farcie'), ('menu-noel-02', 'Bûche'),
('menu-noel-03', 'Convivial'), ('menu-noel-03', 'Terroir'), ('menu-noel-03', 'Chapon'),
('menu-noel-04', 'Végétarien'), ('menu-noel-04', 'Épices'), ('menu-noel-04', 'Champignons'),
('menu-noel-05', 'Alsace'), ('menu-noel-05', 'Choucroute'), ('menu-noel-05', 'Bredele'),
('menu-noel-06', 'Truffe'), ('menu-noel-06', 'Confit'), ('menu-noel-06', 'Périgord'),
('menu-noel-07', 'Vegan'), ('menu-noel-07', 'BIO'), ('menu-noel-07', 'Épices de Noël'),
('menu-noel-08', 'Caviar'), ('menu-noel-08', 'Homard'), ('menu-noel-08', 'Prestige'),
-- Pâques
('menu-002', 'Agneau'), ('menu-002', 'Légumes primeurs'), ('menu-002', 'Agrumes'),
('menu-paques-02', 'Saumon'), ('menu-paques-02', 'Primeurs'), ('menu-paques-02', 'Chocolat'),
('menu-paques-03', 'Végétarien'), ('menu-paques-03', 'Fleurs comestibles'), ('menu-paques-03', 'Asperges'),
('menu-paques-04', 'Provence'), ('menu-paques-04', 'Agneau'), ('menu-paques-04', 'Lavande'),
('menu-paques-05', 'Vegan'), ('menu-paques-05', 'Crudités'), ('menu-paques-05', 'Chocolat noir'),
('menu-paques-06', 'Veau'), ('menu-paques-06', 'Asperges'), ('menu-paques-06', 'Fraises'),
('menu-paques-07', 'Pays Basque'), ('menu-paques-07', 'Piment d''Espelette'), ('menu-paques-07', 'Agneau de lait'),
('menu-paques-08', 'Famille'), ('menu-paques-08', 'Enfants'), ('menu-paques-08', 'Chocolat'),
-- Classique
('menu-003', 'Entrecôte'), ('menu-003', 'Huîtres'), ('menu-003', 'Bordeaux'),
('menu-005', 'Légumes BIO'), ('menu-005', 'Sans viande'), ('menu-005', 'Fromages AOP'),
('menu-006', '100% Végétal'), ('menu-006', 'BIO'), ('menu-006', 'Sans allergènes majeurs'),
('menu-classique-04', 'Gascogne'), ('menu-classique-04', 'Canard'), ('menu-classique-04', 'Armagnac'),
('menu-classique-05', 'Bistrot'), ('menu-classique-05', 'Paris'), ('menu-classique-05', 'Steak-frites'),
('menu-classique-06', 'Méditerranéen'), ('menu-classique-06', 'Légumes grillés'), ('menu-classique-06', 'Feta'),
('menu-classique-07', 'Fusion'), ('menu-classique-07', 'Vegan'), ('menu-classique-07', 'Épices du monde'),
('menu-classique-08', 'Fruits de mer'), ('menu-classique-08', 'Sole'), ('menu-classique-08', 'Île flottante'),
-- Solo
('menu-solo-viande-01', 'Solo'), ('menu-solo-viande-01', 'Viande'), ('menu-solo-viande-01', 'Bœuf'), ('menu-solo-viande-01', 'Gastronomique'),
('menu-solo-viande-02', 'Solo'), ('menu-solo-viande-02', 'Viande'), ('menu-solo-viande-02', 'Magret'), ('menu-solo-viande-02', 'Foie gras'), ('menu-solo-viande-02', 'Sud-Ouest'),
('menu-solo-01', 'Solo'), ('menu-solo-01', 'Déjeuner'), ('menu-solo-01', 'Rapide'),
('menu-solo-02', 'Solo'), ('menu-solo-02', 'Végétarien'), ('menu-solo-02', 'Équilibré'),
-- Événement
('menu-004', 'Homard'), ('menu-004', 'Truffe noire'), ('menu-004', 'Champagne'),
('menu-event-02', 'Cocktail'), ('menu-event-02', 'Bouchées'), ('menu-event-02', 'Verrines'),
('menu-event-03', 'Pro'), ('menu-event-03', 'Buffet'), ('menu-event-03', 'Équipe'),
('menu-event-04', 'Anniversaire'), ('menu-event-04', 'Gâteau'), ('menu-event-04', 'Fête'),
('menu-event-05', 'Mariage'), ('menu-event-05', 'Végétarien'), ('menu-event-05', 'Élégance'),
('menu-event-06', 'Gala'), ('menu-event-06', 'Luxe'), ('menu-event-06', 'Prestige'),
('menu-event-07', 'Famille'), ('menu-event-07', 'Communion'), ('menu-event-07', 'Festif'),
('menu-event-08', 'Vegan'), ('menu-event-08', 'Premium'), ('menu-event-08', 'Événement');


-- ══════════════════════════════════════════════════════════════
--  UTILISATEURS
-- ══════════════════════════════════════════════════════════════

INSERT INTO users (id, first_name, last_name, email, phone, address, city, zip_code, role, active, created_at) VALUES
('user-001', 'Marie', 'Dupont', 'marie.dupont@email.com', '06 12 34 56 78', '12 rue des Roses', 'Bordeaux', '33000', 'client', TRUE, '2024-03-15'),
('user-002', 'Pierre', 'Martin', 'pierre.martin@email.fr', '07 98 76 54 32', '45 avenue du Médoc', 'Bordeaux', '33100', 'client', TRUE, '2024-05-22'),
('emp-001', 'Sophie', 'Leblanc', 'sophie@vitegourmand.fr', '06 55 44 33 22', '8 cours Victor Hugo', 'Bordeaux', '33000', 'employee', TRUE, '2022-01-10'),
('emp-002', 'Thomas', 'Bernard', 'thomas@vitegourmand.fr', '06 11 22 33 44', '3 rue Sainte-Catherine', 'Bordeaux', '33000', 'employee', FALSE, '2021-06-15'),
('admin-001', 'Julie', 'Moreau', 'julie@vitegourmand.fr', '05 56 12 34 56', 'Vite & Gourmand, 22 quai des Chartrons', 'Bordeaux', '33000', 'admin', TRUE, '2000-01-01');


-- ══════════════════════════════════════════════════════════════
--  FIN DU SCRIPT
-- ══════════════════════════════════════════════════════════════