<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260428065010 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE emplacement DROP FOREIGN KEY `FK_C0CF65F6EBA1D170`');
        $this->addSql('DROP INDEX IDX_C0CF65F6EBA1D170 ON emplacement');
        $this->addSql('DROP INDEX UNIQ_C0CF65F640E5DFEA8283DA76EBA1D170 ON emplacement');
        $this->addSql('ALTER TABLE emplacement ADD id_client INT DEFAULT NULL, DROP id_gf_client, CHANGE lettre_etagere lettre_etagere ENUM(\'A\',\'B\',\'C\',\'D\',\'E\',\'F\') NOT NULL');
        $this->addSql('ALTER TABLE emplacement ADD CONSTRAINT FK_C0CF65F6E173B1B8 FOREIGN KEY (id_client) REFERENCES client (id_client)');
        $this->addSql('CREATE INDEX IDX_C0CF65F6E173B1B8 ON emplacement (id_client)');
        $this->addSql('ALTER TABLE gf_client DROP FOREIGN KEY `FK_6B946D5745864C42`');
        $this->addSql('ALTER TABLE gf_client ADD id_emplacement INT DEFAULT NULL, CHANGE quantite_disponible quantite_disponible INT NOT NULL, CHANGE numero_lot numero_lot VARCHAR(50) NOT NULL, CHANGE seuil_alerte seuil_alerte INT NOT NULL, CHANGE id_plant id_plant INT NOT NULL');
        $this->addSql('ALTER TABLE gf_client ADD CONSTRAINT FK_6B946D5745864C42 FOREIGN KEY (id_plant) REFERENCES plant (id_plant)');
        $this->addSql('ALTER TABLE gf_client ADD CONSTRAINT FK_6B946D57D8DDA801 FOREIGN KEY (id_emplacement) REFERENCES emplacement (id_emplacement)');
        $this->addSql('CREATE INDEX IDX_6B946D57D8DDA801 ON gf_client (id_emplacement)');
        $this->addSql('ALTER TABLE gf_histo_client DROP FOREIGN KEY `FK_4B2FFF73EBA1D170`');
        $this->addSql('ALTER TABLE gf_histo_client ADD CONSTRAINT FK_4B2FFF73EBA1D170 FOREIGN KEY (id_gf_client) REFERENCES gf_client (id_gf_client)');
        $this->addSql('ALTER TABLE histo_gf_deposee DROP FOREIGN KEY `FK_91907AF2EBA1D170`');
        $this->addSql('ALTER TABLE histo_gf_deposee CHANGE statut statut ENUM(\'a_traiter\',\'range\') NOT NULL DEFAULT \'a_traiter\'');
        $this->addSql('ALTER TABLE histo_gf_deposee ADD CONSTRAINT FK_91907AF2EBA1D170 FOREIGN KEY (id_gf_client) REFERENCES gf_client (id_gf_client)');
        $this->addSql('ALTER TABLE log CHANGE date_action date_action DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE emplacement DROP FOREIGN KEY FK_C0CF65F6E173B1B8');
        $this->addSql('DROP INDEX IDX_C0CF65F6E173B1B8 ON emplacement');
        $this->addSql('ALTER TABLE emplacement ADD id_gf_client INT NOT NULL, DROP id_client, CHANGE lettre_etagere lettre_etagere VARCHAR(1) NOT NULL');
        $this->addSql('ALTER TABLE emplacement ADD CONSTRAINT `FK_C0CF65F6EBA1D170` FOREIGN KEY (id_gf_client) REFERENCES gf_client (id_gf_client) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_C0CF65F6EBA1D170 ON emplacement (id_gf_client)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C0CF65F640E5DFEA8283DA76EBA1D170 ON emplacement (lettre_etagere, numero_etage, id_gf_client)');
        $this->addSql('ALTER TABLE gf_client DROP FOREIGN KEY FK_6B946D5745864C42');
        $this->addSql('ALTER TABLE gf_client DROP FOREIGN KEY FK_6B946D57D8DDA801');
        $this->addSql('DROP INDEX IDX_6B946D57D8DDA801 ON gf_client');
        $this->addSql('ALTER TABLE gf_client DROP id_emplacement, CHANGE numero_lot numero_lot VARCHAR(50) DEFAULT NULL, CHANGE quantite_disponible quantite_disponible INT DEFAULT 0 NOT NULL, CHANGE seuil_alerte seuil_alerte INT DEFAULT 0 NOT NULL, CHANGE id_plant id_plant INT DEFAULT NULL');
        $this->addSql('ALTER TABLE gf_client ADD CONSTRAINT `FK_6B946D5745864C42` FOREIGN KEY (id_plant) REFERENCES plant (id_plant) ON UPDATE NO ACTION ON DELETE SET NULL');
        $this->addSql('ALTER TABLE gf_histo_client DROP FOREIGN KEY FK_4B2FFF73EBA1D170');
        $this->addSql('ALTER TABLE gf_histo_client ADD CONSTRAINT `FK_4B2FFF73EBA1D170` FOREIGN KEY (id_gf_client) REFERENCES gf_client (id_gf_client) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE histo_gf_deposee DROP FOREIGN KEY FK_91907AF2EBA1D170');
        $this->addSql('ALTER TABLE histo_gf_deposee CHANGE statut statut VARCHAR(20) DEFAULT \'a_traiter\'');
        $this->addSql('ALTER TABLE histo_gf_deposee ADD CONSTRAINT `FK_91907AF2EBA1D170` FOREIGN KEY (id_gf_client) REFERENCES gf_client (id_gf_client) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE log CHANGE date_action date_action DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
    }
}
