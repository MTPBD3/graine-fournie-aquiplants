<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260421120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove reference_gf column from gf_client table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE gf_client DROP COLUMN reference_gf');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE gf_client ADD reference_gf VARCHAR(50) NOT NULL DEFAULT ''");
    }
}
