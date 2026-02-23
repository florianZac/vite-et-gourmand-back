--RAPPEL : MEMOIRE CELFS ETRANGERES
-- une clé étrangère est une contrainte qui permet de lier deux tables entre elles

-- cas clef étrangère utilisateur 1,1 --- possede --- 0,n role
-- Rappel MEMOIRE
-- ATTENTION AU SENS DE LA RELATION 
-- La clé étrangère va toujours du côté où il peut y avoir plusieurs lignes pour la même entité
-- 1. modifie la table utilisateur et ajoute une colone role_id
-- 2. ajoute une contrainte de clé étrangère entre utilisateur.role_id et role.role_id
-- 3. on ajoute une contrainte de suppression en cascade pour éviter les liaisons orphelines
-- 4. on ajoute une contrainte de mise à jour en cascade pour éviter les liaisons orphelines

-- l'utilisateur peut avoir plusieurs roles mais un role ne peut être attribué qu'à un seul utilisateur

ALTER TABLE utilisateur 
ADD COLUMN role_id INT NOT NULL,
 ADD CONSTRAINT fk_utilisateur_role 
FOREIGN KEY (role_id) REFERENCES role(role_id) 
ON DELETE RESTRICT 
ON UPDATE CASCADE;

-- cas clef étrangère utilisateur 1,1 --- publie --- 0,n avis
-- l'utilisateur peut publier plusieurs avis mais un avis ne peut être publié que par un seul utilisateur

ALTER TABLE avis 
ADD COLUMN utilisateur_id INT NOT NULL,
ADD CONSTRAINT fk_avis_utilisateur
FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(utilisateur_id)
ON DELETE RESTRICT
ON UPDATE CASCADE;

-- cas clef étrangère menu 1,1 --- adapte --- 0,n regime
-- un menu est adapté à un seul régime, mais un régime peut être adapté à plusieurs menus.
-- cas clefs etgrangere dejà cree dans la creation de table pas besoin de les recréer mais on peut les modifier pour ajouter les contraintes de suppression et de mise à jour en cascade

ALTER TABLE menu
ADD CONSTRAINT fk_menu_regime
FOREIGN KEY (regime_id) REFERENCES regime(regime_id)
ON DELETE RESTRICT
ON UPDATE CASCADE;


-- cas clef étrangère menu 1,1 --- appartient --- 0,n theme
-- un menu peut appartenir à plusieurs thèmes mais un thème ne peut appartenir qu'à un seul menu
-- 1. modifie la table menu et ajoute une colone theme_id
-- 2. ajoute une contrainte de clé étrangère entre menu.theme_id et theme.theme_id
-- 3. on ajoute une contrainte de suppression en cascade pour éviter les liaisons orphelines
-- 4. on ajoute une contrainte de mise à jour en cascade pour éviter les liaisons orphelines

ALTER TABLE menu
ADD COLUMN `theme_id` INT NOT NULL,
ADD CONSTRAINT fk_menu_theme
FOREIGN KEY (theme_id) REFERENCES theme(theme_id)
ON DELETE RESTRICT
ON UPDATE CASCADE;

-- cas clef étrangère utilisateur 0,n --- commande --- 0,n menu
-- un utilisateur peut commander plusieurs menus mais un menu peut être commandé par plusieurs utilisateurs

ALTER TABLE commande
ADD COLUMN utilisateur_id INT NOT NULL,
ADD CONSTRAINT fk_commande_utilisateur
FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(utilisateur_id)
ON DELETE RESTRICT
ON UPDATE CASCADE;

-- cas clef étrangère commande 1,1 --- concerne --- 0,n menu
-- une commande concerne un seul menu mais un menu peut être concerné par plusieurs commandes
ALTER TABLE `commande`
ADD COLUMN `menu_id` INT NOT NULL,
ADD CONSTRAINT `fk_commande_menu`
FOREIGN KEY (menu_id) REFERENCES menu(menu_id)
ON DELETE RESTRICT
ON UPDATE CASCADE;