<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260430000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Renomme les valeurs enum statut : en_attente→a_traiter, en_stock→range, supprime epuise';
    }

    public function up(Schema $schema): void
    {
        // Passe en VARCHAR pour accepter les deux jeux de valeurs pendant la transition
        $this->addSql("ALTER TABLE histo_gf_deposee CHANGE statut statut VARCHAR(20) NOT NULL DEFAULT 'a_traiter'");
        // Migration des données existantes
        $this->addSql("UPDATE histo_gf_deposee SET statut = 'a_traiter' WHERE statut = 'en_attente'");
        $this->addSql("UPDATE histo_gf_deposee SET statut = 'range'     WHERE statut = 'en_stock'");
        $this->addSql("UPDATE histo_gf_deposee SET statut = 'range'     WHERE statut = 'epuise'");
        // Retour à l'ENUM avec les nouvelles valeurs
        $this->addSql("ALTER TABLE histo_gf_deposee CHANGE statut statut ENUM('a_traiter','range') NOT NULL DEFAULT 'a_traiter'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE histo_gf_deposee CHANGE statut statut VARCHAR(20) NOT NULL DEFAULT 'en_attente'");
        $this->addSql("UPDATE histo_gf_deposee SET statut = 'en_attente' WHERE statut = 'a_traiter'");
        $this->addSql("UPDATE histo_gf_deposee SET statut = 'en_stock'   WHERE statut = 'range'");
        $this->addSql("ALTER TABLE histo_gf_deposee CHANGE statut statut ENUM('en_attente','en_stock','epuise') NOT NULL DEFAULT 'en_attente'");
    }
}
