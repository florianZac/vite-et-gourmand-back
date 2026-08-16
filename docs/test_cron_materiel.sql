
-- =====================================================
-- étape n° 1 : 
-- Savoir quels IDs existe déjà en base avant d'insérer les commandes de test, 
-- pour éviter une erreur de clé étrangère.
-- =====================================================

SELECT utilisateur_id FROM utilisateur LIMIT 1;
SELECT menu_id FROM menu LIMIT 1;

-- =====================================================
-- résultat: 
-- utilisateur_id = 6
-- menu_id = 1
-- =====================================================

-- =====================================================
-- Pour le test de la commande app:check-retour-materiel je vais tester 5 cas
-- utilisateur_id = 6, menu_id = 1
-- =====================================================

-- CAS 1 DOIT envoyer le mail
-- pret=1, restitution=0, statut=Livré, livré il y a 15 jours → dépasse les 10 jours ouvrés
INSERT INTO commande (numero_commande, date_commande, date_prestation, statut, pret_materiel, restitution_materiel, mail_penalite_envoye, date_statut_livree, heure_livraison, prix_menu, nombre_personne, prix_livraison, utilisateur_id, menu_id)
VALUES ('CMD-TEST-001', NOW(), NOW(), 'Livré', 1, 0, 0, DATE_SUB(NOW(), INTERVAL 15 DAY), '12:00:00', 500.00, 10, 50.00, 6, 1);

-- CAS 2 NE DOIT PAS envoyer le mail
-- pret=1, restitution=0, statut=Livré, livré il y a 3 jours → pas encore 10 jours ouvrés
INSERT INTO commande (numero_commande, date_commande, date_prestation, statut, pret_materiel, restitution_materiel, mail_penalite_envoye, date_statut_livree, heure_livraison, prix_menu, nombre_personne, prix_livraison, utilisateur_id, menu_id)
VALUES ('CMD-TEST-002', NOW(), NOW(), 'Livré', 1, 0, 0, DATE_SUB(NOW(), INTERVAL 3 DAY), '12:00:00', 500.00, 10, 50.00, 6, 1);

-- CAS 3 MAIL DÉJÀ ENVOYÉ → doit être ignoré
-- pret=1, restitution=0, statut=Livré, mail_penalite_envoye=1
INSERT INTO commande (numero_commande, date_commande, date_prestation, statut, pret_materiel, restitution_materiel, mail_penalite_envoye, date_statut_livree, heure_livraison, prix_menu, nombre_personne, prix_livraison, utilisateur_id, menu_id)
VALUES ('CMD-TEST-003', NOW(), NOW(), 'Livré', 1, 0, 1, DATE_SUB(NOW(), INTERVAL 15 DAY), '12:00:00', 500.00, 10, 50.00, 6, 1);

-- CAS 4 MATÉRIEL RENDU → ne doit pas apparaître dans findCommandesMaterielARelancer()
-- pret=1, restitution=1 → matériel rendu, cas (1,1)
INSERT INTO commande (numero_commande, date_commande, date_prestation, statut, pret_materiel, restitution_materiel, mail_penalite_envoye, date_statut_livree, heure_livraison, prix_menu, nombre_personne, prix_livraison, utilisateur_id, menu_id)
VALUES ('CMD-TEST-004', NOW(), NOW(), 'Livré', 1, 1, 0, DATE_SUB(NOW(), INTERVAL 15 DAY), '12:00:00', 500.00, 10, 50.00, 6, 1);

-- CAS 5 STATUT PAS "Livré" → ne doit pas apparaître dans findCommandesMaterielARelancer()
-- statut = 'En cours', pret=1, restitution=0
INSERT INTO commande (numero_commande, date_commande, date_prestation, statut, pret_materiel, restitution_materiel, mail_penalite_envoye, date_statut_livree, heure_livraison, prix_menu, nombre_personne, prix_livraison, utilisateur_id, menu_id)
VALUES ('CMD-TEST-005', NOW(), NOW(), 'En cours', 1, 0, 0, DATE_SUB(NOW(), INTERVAL 15 DAY), '12:00:00', 500.00, 10, 50.00, 6, 1);

-- =====================================================
-- Résultat attendu lors du lancement de la commande -> php bin/console app:check-retour-materiel
-- CMD-TEST-001 → "Mail pénalité envoyé"
-- CMD-TEST-002 → "Pas encore de mail... date limite: XX/XX/XXXX"
-- CMD-TEST-003 → "Mail déjà envoyé"
-- CMD-TEST-004 → n'apparaît pas (restitution_materiel=1)
-- CMD-TEST-005 → n'apparaît pas (statut != Livré)
-- =====================================================
-- =====================================================
-- Résultat : correct mail reçut, et cmd4 et 5 ne sont pas 
-- PS D:\wamp64\www\vite-et-gourmand-back> php bin/console app:check-retour-materiel 
-- ===== Début du check des commandes matériel non restitué =====
-- Mail déjà envoyé pour commande CMD-TEST-001
-- Pas encore de mail pour commande CMD-TEST-002 (attente 10 jours ouvrés, date limite: 10/03/2026)
-- Mail déjà envoyé pour commande CMD-TEST-003
-- ===== Fin du check des commandes =====





-- NETTOYAGE après test
-- DELETE FROM commande WHERE numero_commande LIKE 'CMD-TEST-%';