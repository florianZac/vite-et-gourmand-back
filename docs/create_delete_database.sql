--RAPPEL : MEMOIRE
-- CREATE -> crée une base de données
-- DROP -> supprime une base de données
-- ALTER -> modifie une base de données
-- USE -> sélectionne une base de données
-- DELETE -> supprime des lignes dans une table impossible à utiliser pour supprimer une base de données

-- création de la base de données vite_et_gourmand

CREATE DATABASE IF NOT EXISTS vite_et_gourmand CHARACTER SET utf8 COLLATE utf8_general_ci;

USE vite_et_gourmand;

-- suppression de la base de données vite_et_gourmand
DROP DATABASE IF EXISTS vite_et_gourmand;
