<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260619132946 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Contrainte unique sur gf_client.numero_lot';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX uq_numero_lot ON gf_client (numero_lot)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uq_numero_lot ON gf_client');
    }
}
