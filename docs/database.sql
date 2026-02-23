USE vite_et_gourmand;

CREATE TABLE `role` (
  `role_id` INT NOT NULL AUTO_INCREMENT,
  `libelle` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`role_id`)
);

CREATE TABLE `avis` (
  `avis_id` INT NOT NULL AUTO_INCREMENT,
  `note` INT NOT NULL,
  `description` VARCHAR(50) NOT NULL,
  `statut` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`avis_id`)
);

CREATE TABLE `menu` (
  `menu_id` INT NOT NULL AUTO_INCREMENT,
  `titre` VARCHAR(50) NOT NULL,
  `nombre_personne_minimum` INT NOT NULL,
  `prix_par_personne` DOUBLE NOT NULL,
  `regime_id` INT NOT NULL,
  `description` VARCHAR(255) NOT NULL, -- 50 n'est pas assez pour une description d'un plat passage à 255
  `quantite_restante` INT NOT NULL,
  PRIMARY KEY (`menu_id`)
);

CREATE TABLE `utilisateur` (
  `utilisateur_id` INT NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL,    -- 50 n'est pas assez pour stocker une adresse email, passage à 255
  `password` VARCHAR(255) NOT NULL, -- 50 n'est pas assez pour stocker un mot de passe hashé bcrypt, passage à 255
  `prenom` VARCHAR(50) NOT NULL,
  `telephone` VARCHAR(50) NOT NULL,
  `ville` VARCHAR(50) NOT NULL,
  `pays` VARCHAR(50) NOT NULL,
  `adresse_postale` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`utilisateur_id`)
);

CREATE TABLE `regime` (
  `regime_id` INT NOT NULL AUTO_INCREMENT,
  `libelle` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`regime_id`)
);

CREATE TABLE `theme` (
  `theme_id` INT NOT NULL AUTO_INCREMENT,
  `libelle` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`theme_id`)
);
CREATE TABLE `plat` (
  `plat_id` INT NOT NULL AUTO_INCREMENT,
  `titre_plat` VARCHAR(50) NOT NULL,
  `photo` VARCHAR(255) NOT NULL,  -- je ne souhaite pas utilisé BLOB après vérification mettre des images stoker en binaires est une erreur est aussi un mauvaise pratique chemin vers l'image ex: "uploads/plat1.jpg" 
  PRIMARY KEY (`plat_id`)
);

CREATE TABLE `allergene` (
  `allergene_id` INT NOT NULL AUTO_INCREMENT,
  `libelle` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`allergene_id`)
);

CREATE TABLE `horaire` (
    horaire_id INT NOT NULL AUTO_INCREMENT,
    jour VARCHAR(50) NOT NULL,
    heure_ouverture TIME NOT NULL, -- VARCHAR(50) est une érreur lors de l'écriture on pourrait écrire nimporte quoi dans ce cas TIME est plus adapté
    heure_fermeture TIME NOT NULL,
    PRIMARY KEY (`horaire_id`)
);  

CREATE TABLE `commande` (
    commande_id INT NOT NULL AUTO_INCREMENT,
    numero_commande VARCHAR(50) NOT NULL,
    date_commande DATETIME NOT NULL, -- DATE ne semble pas etre bon car on veut la date ET l'heure exacte de la commande du coup DATETIME est plus adapté que DATE
    date_prestation DATE NOT NULL,
    heure_livraison TIME NOT NULL, -- TIME est plus adapté pour stocker une heure de livraison qu'un VARCHAR(50) 
    prix_menu DOUBLE NOT NULL,
    nombre_personne INT NOT NULL,
    prix_livraison DOUBLE NOT NULL,
    statut VARCHAR(50) NOT NULL,
    pret_materiel BOOLEAN NOT NULL,
    restitution_materiel BOOLEAN NOT NULL,
    PRIMARY KEY (`commande_id`)
);


-- Table de liaisons

-- Liaison entre menu et plat (relation ManyToMany)
-- cas menu 1,n --- propose --- 0,n plat


CREATE TABLE `propose` (
    menu_id INT NOT NULL,
    plat_id INT NOT NULL,
    PRIMARY KEY (menu_id, plat_id),
    FOREIGN KEY (menu_id) REFERENCES menu(menu_id) ON DELETE CASCADE,
    FOREIGN KEY (plat_id) REFERENCES plat(plat_id) ON DELETE CASCADE
);

-- Liaison entre le plat et l'allergene

-- cas plat 0,n --- contient --- 0,n allergene

CREATE TABLE `contient` (
    plat_id INT NOT NULL,
    allergene_id INT NOT NULL,
    PRIMARY KEY (plat_id, allergene_id),
    FOREIGN KEY (plat_id) REFERENCES plat(plat_id) ON DELETE CASCADE,
    FOREIGN KEY (allergene_id) REFERENCES allergene(allergene_id) ON DELETE CASCADE
);

