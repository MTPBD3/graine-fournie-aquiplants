<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260416133008 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE client (id_client INT AUTO_INCREMENT NOT NULL, nom_client VARCHAR(150) NOT NULL, prenom_client VARCHAR(150) NOT NULL, PRIMARY KEY (id_client)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE commande_a_semer (id_commande INT AUTO_INCREMENT NOT NULL, quantite_a_semer INT NOT NULL, date_semis DATE NOT NULL, date_livraison DATE NOT NULL, id_uv INT NOT NULL, id_client INT NOT NULL, INDEX IDX_EFCA1A026A8AB242 (id_uv), INDEX IDX_EFCA1A02E173B1B8 (id_client), PRIMARY KEY (id_commande)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE emplacement (id_emplacement INT AUTO_INCREMENT NOT NULL, lettre_etagere VARCHAR(1) NOT NULL, numero_etage INT NOT NULL, id_gf_client INT NOT NULL, INDEX IDX_C0CF65F6EBA1D170 (id_gf_client), UNIQUE INDEX UNIQ_C0CF65F640E5DFEA8283DA76EBA1D170 (lettre_etagere, numero_etage, id_gf_client), PRIMARY KEY (id_emplacement)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE gf_client (id_gf_client INT AUTO_INCREMENT NOT NULL, reference_gf VARCHAR(50) NOT NULL, quantite_disponible INT DEFAULT 0 NOT NULL, numero_lot VARCHAR(50) DEFAULT NULL, seuil_alerte INT DEFAULT 0 NOT NULL, nom_client VARCHAR(150) NOT NULL, id_client INT NOT NULL, id_plant INT DEFAULT NULL, UNIQUE INDEX UNIQ_6B946D5766C8920B (reference_gf), INDEX IDX_6B946D57E173B1B8 (id_client), INDEX IDX_6B946D5745864C42 (id_plant), PRIMARY KEY (id_gf_client)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE gf_histo_client (id_histo INT AUTO_INCREMENT NOT NULL, quantite_semee INT NOT NULL, date_semis DATE NOT NULL, nom_uv VARCHAR(100) NOT NULL, nb_graine_par_motte INT NOT NULL, id_gf_client INT NOT NULL, id_uv INT NOT NULL, INDEX IDX_4B2FFF73EBA1D170 (id_gf_client), INDEX IDX_4B2FFF736A8AB242 (id_uv), PRIMARY KEY (id_histo)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE histo_gf_deposee (id_histo_depot INT AUTO_INCREMENT NOT NULL, quantite_deposee INT NOT NULL, date_reception DATE NOT NULL, statut VARCHAR(20) DEFAULT \'a_traiter\', note VARCHAR(255) DEFAULT NULL, id_gf_client INT NOT NULL, INDEX IDX_91907AF2EBA1D170 (id_gf_client), PRIMARY KEY (id_histo_depot)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE log (id_log INT AUTO_INCREMENT NOT NULL, action VARCHAR(100) NOT NULL, date_action DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, detail VARCHAR(255) DEFAULT NULL, id_utilisateur INT NOT NULL, INDEX IDX_8F3F68C550EAE44 (id_utilisateur), PRIMARY KEY (id_log)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE plant (id_plant INT AUTO_INCREMENT NOT NULL, nom_plant VARCHAR(150) NOT NULL, nom_espece VARCHAR(150) NOT NULL, PRIMARY KEY (id_plant)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE utilisateur (id_utilisateur INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, prenom VARCHAR(100) NOT NULL, email VARCHAR(150) NOT NULL, mdp_crypte VARCHAR(255) NOT NULL, role VARCHAR(20) NOT NULL, UNIQUE INDEX UNIQ_1D1C63B3E7927C74 (email), PRIMARY KEY (id_utilisateur)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE uv (id_uv INT AUTO_INCREMENT NOT NULL, nom_uv VARCHAR(100) NOT NULL, nb_graine_par_motte INT NOT NULL, PRIMARY KEY (id_uv)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE commande_a_semer ADD CONSTRAINT FK_EFCA1A026A8AB242 FOREIGN KEY (id_uv) REFERENCES uv (id_uv) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE commande_a_semer ADD CONSTRAINT FK_EFCA1A02E173B1B8 FOREIGN KEY (id_client) REFERENCES client (id_client) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE emplacement ADD CONSTRAINT FK_C0CF65F6EBA1D170 FOREIGN KEY (id_gf_client) REFERENCES gf_client (id_gf_client) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE gf_client ADD CONSTRAINT FK_6B946D57E173B1B8 FOREIGN KEY (id_client) REFERENCES client (id_client) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE gf_client ADD CONSTRAINT FK_6B946D5745864C42 FOREIGN KEY (id_plant) REFERENCES plant (id_plant) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE gf_histo_client ADD CONSTRAINT FK_4B2FFF73EBA1D170 FOREIGN KEY (id_gf_client) REFERENCES gf_client (id_gf_client) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE gf_histo_client ADD CONSTRAINT FK_4B2FFF736A8AB242 FOREIGN KEY (id_uv) REFERENCES uv (id_uv) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE histo_gf_deposee ADD CONSTRAINT FK_91907AF2EBA1D170 FOREIGN KEY (id_gf_client) REFERENCES gf_client (id_gf_client) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE log ADD CONSTRAINT FK_8F3F68C550EAE44 FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur) ON DELETE RESTRICT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande_a_semer DROP FOREIGN KEY FK_EFCA1A026A8AB242');
        $this->addSql('ALTER TABLE commande_a_semer DROP FOREIGN KEY FK_EFCA1A02E173B1B8');
        $this->addSql('ALTER TABLE emplacement DROP FOREIGN KEY FK_C0CF65F6EBA1D170');
        $this->addSql('ALTER TABLE gf_client DROP FOREIGN KEY FK_6B946D57E173B1B8');
        $this->addSql('ALTER TABLE gf_client DROP FOREIGN KEY FK_6B946D5745864C42');
        $this->addSql('ALTER TABLE gf_histo_client DROP FOREIGN KEY FK_4B2FFF73EBA1D170');
        $this->addSql('ALTER TABLE gf_histo_client DROP FOREIGN KEY FK_4B2FFF736A8AB242');
        $this->addSql('ALTER TABLE histo_gf_deposee DROP FOREIGN KEY FK_91907AF2EBA1D170');
        $this->addSql('ALTER TABLE log DROP FOREIGN KEY FK_8F3F68C550EAE44');
        $this->addSql('DROP TABLE client');
        $this->addSql('DROP TABLE commande_a_semer');
        $this->addSql('DROP TABLE emplacement');
        $this->addSql('DROP TABLE gf_client');
        $this->addSql('DROP TABLE gf_histo_client');
        $this->addSql('DROP TABLE histo_gf_deposee');
        $this->addSql('DROP TABLE log');
        $this->addSql('DROP TABLE plant');
        $this->addSql('DROP TABLE utilisateur');
        $this->addSql('DROP TABLE uv');
    }
}
