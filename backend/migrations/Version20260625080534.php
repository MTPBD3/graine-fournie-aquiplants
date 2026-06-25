<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260625080534 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Sync ENUM formatting: emplacement.lettre_etagere and histo_gf_deposee.statut';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE emplacement CHANGE lettre_etagere lettre_etagere ENUM(\'A\',\'B\',\'C\',\'D\',\'E\',\'F\') NOT NULL');
        $this->addSql('ALTER TABLE histo_gf_deposee CHANGE statut statut ENUM(\'a_traiter\',\'range\') NOT NULL DEFAULT \'a_traiter\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE emplacement CHANGE lettre_etagere lettre_etagere ENUM(\'A\', \'B\', \'C\', \'D\', \'E\', \'F\') NOT NULL');
        $this->addSql('ALTER TABLE histo_gf_deposee CHANGE statut statut ENUM(\'a_traiter\', \'range\') DEFAULT \'a_traiter\' NOT NULL');
    }
}
