<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260330181625 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande ADD code_postal_livraison VARCHAR(100) DEFAULT NULL');
        $this->addSql('DROP INDEX IDX_9B2879B15A0520A ON menu_tag');
        $this->addSql('ALTER TABLE menu_tag CHANGE menu_tags_id menutags_id INT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (menu_id, menutags_id)');
        $this->addSql('ALTER TABLE menu_tag ADD CONSTRAINT FK_9B2879B1CCD7E912 FOREIGN KEY (menu_id) REFERENCES menu (menu_id)');
        $this->addSql('ALTER TABLE menu_tag ADD CONSTRAINT FK_9B2879B1A70FF0EF FOREIGN KEY (menutags_id) REFERENCES menu_tags (id)');
        $this->addSql('CREATE INDEX IDX_9B2879B1A70FF0EF ON menu_tag (menutags_id)');
        $this->addSql('ALTER TABLE menu_tags DROP FOREIGN KEY `FK_A7F36468CCD7E912`');
        $this->addSql('DROP INDEX IDX_A7F36468CCD7E912 ON menu_tags');
        $this->addSql('ALTER TABLE menu_tags DROP menu_id');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A7F36468389B783 ON menu_tags (tag)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande DROP code_postal_livraison');
        $this->addSql('ALTER TABLE menu_tag DROP FOREIGN KEY FK_9B2879B1CCD7E912');
        $this->addSql('ALTER TABLE menu_tag DROP FOREIGN KEY FK_9B2879B1A70FF0EF');
        $this->addSql('DROP INDEX IDX_9B2879B1A70FF0EF ON menu_tag');
        $this->addSql('ALTER TABLE menu_tag CHANGE menutags_id menu_tags_id INT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (menu_id, menu_tags_id)');
        $this->addSql('CREATE INDEX IDX_9B2879B15A0520A ON menu_tag (menu_tags_id)');
        $this->addSql('DROP INDEX UNIQ_A7F36468389B783 ON menu_tags');
        $this->addSql('ALTER TABLE menu_tags ADD menu_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE menu_tags ADD CONSTRAINT `FK_A7F36468CCD7E912` FOREIGN KEY (menu_id) REFERENCES menu (menu_id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_A7F36468CCD7E912 ON menu_tags (menu_id)');
    }
}
