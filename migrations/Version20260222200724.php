<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260222200724 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE allergene (allergene_id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(50) NOT NULL, PRIMARY KEY (allergene_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE avis (avis_id INT AUTO_INCREMENT NOT NULL, note INT NOT NULL, description VARCHAR(50) NOT NULL, statut VARCHAR(50) NOT NULL, utilisateur_id INT NOT NULL, INDEX IDX_8F91ABF0FB88E14F (utilisateur_id), PRIMARY KEY (avis_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE commande (commande_id INT AUTO_INCREMENT NOT NULL, numero_commande VARCHAR(50) NOT NULL, date_commande DATETIME NOT NULL, date_prestation DATE NOT NULL, heure_livraison TIME NOT NULL, prix_menu DOUBLE PRECISION NOT NULL, nombre_personne INT NOT NULL, prix_livraison DOUBLE PRECISION NOT NULL, statut VARCHAR(50) NOT NULL, pret_materiel TINYINT NOT NULL, restitution_materiel TINYINT NOT NULL, utilisateur_id INT NOT NULL, menu_id INT NOT NULL, INDEX IDX_6EEAA67DFB88E14F (utilisateur_id), INDEX IDX_6EEAA67DCCD7E912 (menu_id), PRIMARY KEY (commande_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE horaire (horaire_id INT AUTO_INCREMENT NOT NULL, jour VARCHAR(50) NOT NULL, heure_ouverture TIME NOT NULL, heure_fermeture TIME NOT NULL, PRIMARY KEY (horaire_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE menu (menu_id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(50) NOT NULL, nombre_personne_minimum INT NOT NULL, prix_par_personne DOUBLE PRECISION NOT NULL, description VARCHAR(255) NOT NULL, quantite_restante INT NOT NULL, regime_id INT NOT NULL, theme_id INT NOT NULL, INDEX IDX_7D053A9335E7D534 (regime_id), INDEX IDX_7D053A9359027487 (theme_id), PRIMARY KEY (menu_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE propose (menu_id INT NOT NULL, plat_id INT NOT NULL, INDEX IDX_3DF2D060CCD7E912 (menu_id), INDEX IDX_3DF2D060D73DB560 (plat_id), PRIMARY KEY (menu_id, plat_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE plat (plat_id INT AUTO_INCREMENT NOT NULL, titre_plat VARCHAR(50) NOT NULL, photo VARCHAR(255) NOT NULL, PRIMARY KEY (plat_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE contient (plat_id INT NOT NULL, allergene_id INT NOT NULL, INDEX IDX_DC302E56D73DB560 (plat_id), INDEX IDX_DC302E564646AB2 (allergene_id), PRIMARY KEY (plat_id, allergene_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE regime (regime_id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(50) NOT NULL, PRIMARY KEY (regime_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE role (role_id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(50) NOT NULL, PRIMARY KEY (role_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE theme (theme_id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(50) NOT NULL, PRIMARY KEY (theme_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE utilisateur (utilisateur_id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, prenom VARCHAR(50) NOT NULL, telephone VARCHAR(50) NOT NULL, ville VARCHAR(50) NOT NULL, pays VARCHAR(50) NOT NULL, adresse_postale VARCHAR(50) NOT NULL, role_id INT NOT NULL, INDEX IDX_1D1C63B3D60322AC (role_id), PRIMARY KEY (utilisateur_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF0FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (utilisateur_id)');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (utilisateur_id)');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DCCD7E912 FOREIGN KEY (menu_id) REFERENCES menu (menu_id)');
        $this->addSql('ALTER TABLE menu ADD CONSTRAINT FK_7D053A9335E7D534 FOREIGN KEY (regime_id) REFERENCES regime (regime_id)');
        $this->addSql('ALTER TABLE menu ADD CONSTRAINT FK_7D053A9359027487 FOREIGN KEY (theme_id) REFERENCES theme (theme_id)');
        $this->addSql('ALTER TABLE propose ADD CONSTRAINT FK_3DF2D060CCD7E912 FOREIGN KEY (menu_id) REFERENCES menu (menu_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE propose ADD CONSTRAINT FK_3DF2D060D73DB560 FOREIGN KEY (plat_id) REFERENCES plat (plat_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE contient ADD CONSTRAINT FK_DC302E56D73DB560 FOREIGN KEY (plat_id) REFERENCES plat (plat_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE contient ADD CONSTRAINT FK_DC302E564646AB2 FOREIGN KEY (allergene_id) REFERENCES allergene (allergene_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_1D1C63B3D60322AC FOREIGN KEY (role_id) REFERENCES role (role_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF0FB88E14F');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DFB88E14F');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DCCD7E912');
        $this->addSql('ALTER TABLE menu DROP FOREIGN KEY FK_7D053A9335E7D534');
        $this->addSql('ALTER TABLE menu DROP FOREIGN KEY FK_7D053A9359027487');
        $this->addSql('ALTER TABLE propose DROP FOREIGN KEY FK_3DF2D060CCD7E912');
        $this->addSql('ALTER TABLE propose DROP FOREIGN KEY FK_3DF2D060D73DB560');
        $this->addSql('ALTER TABLE contient DROP FOREIGN KEY FK_DC302E56D73DB560');
        $this->addSql('ALTER TABLE contient DROP FOREIGN KEY FK_DC302E564646AB2');
        $this->addSql('ALTER TABLE utilisateur DROP FOREIGN KEY FK_1D1C63B3D60322AC');
        $this->addSql('DROP TABLE allergene');
        $this->addSql('DROP TABLE avis');
        $this->addSql('DROP TABLE commande');
        $this->addSql('DROP TABLE horaire');
        $this->addSql('DROP TABLE menu');
        $this->addSql('DROP TABLE propose');
        $this->addSql('DROP TABLE plat');
        $this->addSql('DROP TABLE contient');
        $this->addSql('DROP TABLE regime');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE theme');
        $this->addSql('DROP TABLE utilisateur');
    }
}