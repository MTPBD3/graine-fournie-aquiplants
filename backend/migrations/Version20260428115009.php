<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260428115009 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE espece (id_espece INT AUTO_INCREMENT NOT NULL, nom_espece VARCHAR(150) NOT NULL, PRIMARY KEY (id_espece)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE emplacement CHANGE lettre_etagere lettre_etagere ENUM(\'A\',\'B\',\'C\',\'D\',\'E\',\'F\') NOT NULL');
        $this->addSql('ALTER TABLE histo_gf_deposee CHANGE statut statut ENUM(\'a_traiter\',\'range\') NOT NULL DEFAULT \'a_traiter\'');
        $this->addSql('ALTER TABLE plant ADD id_espece INT DEFAULT NULL, DROP nom_espece');
        $this->addSql('ALTER TABLE plant ADD CONSTRAINT FK_AB030D722795145C FOREIGN KEY (id_espece) REFERENCES espece (id_espece)');
        $this->addSql('CREATE INDEX IDX_AB030D722795145C ON plant (id_espece)');
        $this->addSql('ALTER TABLE uv ADD nombre_graine_par_motte INT NOT NULL, ADD nombre_plant_par_plateaux INT NOT NULL, ADD id_espece INT NOT NULL, DROP nb_graine_par_motte, DROP nombre_plant_par_plateau');
        $this->addSql('ALTER TABLE uv ADD CONSTRAINT FK_AAF74B452795145C FOREIGN KEY (id_espece) REFERENCES espece (id_espece)');
        $this->addSql('CREATE INDEX IDX_AAF74B452795145C ON uv (id_espece)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE espece');
        $this->addSql('ALTER TABLE emplacement CHANGE lettre_etagere lettre_etagere ENUM(\'A\', \'B\', \'C\', \'D\', \'E\', \'F\') NOT NULL');
        $this->addSql('ALTER TABLE histo_gf_deposee CHANGE statut statut ENUM(\'a_traiter\', \'range\') DEFAULT \'a_traiter\' NOT NULL');
        $this->addSql('ALTER TABLE plant DROP FOREIGN KEY FK_AB030D722795145C');
        $this->addSql('DROP INDEX IDX_AB030D722795145C ON plant');
        $this->addSql('ALTER TABLE plant ADD nom_espece VARCHAR(150) NOT NULL, DROP id_espece');
        $this->addSql('ALTER TABLE uv DROP FOREIGN KEY FK_AAF74B452795145C');
        $this->addSql('DROP INDEX IDX_AAF74B452795145C ON uv');
        $this->addSql('ALTER TABLE uv ADD nb_graine_par_motte INT NOT NULL, ADD nombre_plant_par_plateau INT NOT NULL, DROP nombre_graine_par_motte, DROP nombre_plant_par_plateaux, DROP id_espece');
    }
}
