<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:import-csv', description: 'Importe les données Plant et UV depuis les fichiers CSV dans var/data/')]
class ImportCsvCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '512M');

        $io = new SymfonyStyle($input, $output);
        $dataDir = dirname(__DIR__, 2) . '/var/data';

        $this->importEspeces($io, $dataDir . '/R_export_espece.csv');
        $this->importPlants($io, $dataDir . '/R__Export_Plant.csv');
        $this->importUvs($io, $dataDir . '/R__Export_UV.csv');

        return Command::SUCCESS;
    }

    // ── Lecture CSV latin1 → UTF-8 ─────────────────────────────────────────────
    private function readCsv(string $path): array
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("Fichier introuvable : $path");
        }

        $handle = fopen($path, 'r');
        $header = null;
        $rows   = [];

        while (($raw = fgets($handle)) !== false) {
            $line = rtrim(mb_convert_encoding($raw, 'UTF-8', 'ISO-8859-1'));
            if ($line === '') continue;

            $cols = str_getcsv($line, ';');

            if ($header === null) {
                $header = $cols;
                continue;
            }

            if (count($cols) !== count($header)) continue;

            $rows[] = array_combine($header, $cols);
        }

        fclose($handle);
        return $rows;
    }

    // ── Import espèces seules (R_export_espece.csv) ────────────────────────────
    private function importEspeces(SymfonyStyle $io, string $path): void
    {
        $io->section('Import espèces (' . basename($path) . ')');

        $rows = $this->readCsv($path);
        $io->writeln(count($rows) . ' lignes lues.');

        $inserted = 0;
        $updated  = 0;

        foreach ($rows as $i => $row) {
            $idEspece  = (int) ($row['id_espece']  ?? 0);
            $nomEspece = trim($row['nom_espece'] ?? '');

            if ($idEspece === 0 || $nomEspece === '') {
                $io->writeln("<comment>Ligne " . ($i + 2) . " ignorée (id ou nom vide)</comment>");
                continue;
            }

            $exists = $this->connection->fetchOne(
                'SELECT 1 FROM espece WHERE id_espece = ?', [$idEspece]
            );

            if ($exists) {
                $this->connection->executeStatement(
                    'UPDATE espece SET nom_espece = ? WHERE id_espece = ?',
                    [$nomEspece, $idEspece]
                );
                $updated++;
            } else {
                $this->connection->executeStatement(
                    'INSERT INTO espece (id_espece, nom_espece) VALUES (?, ?)',
                    [$idEspece, $nomEspece]
                );
                $inserted++;
            }
        }

        $maxId = (int) $this->connection->fetchOne('SELECT MAX(id_espece) FROM espece');
        $this->connection->executeStatement('ALTER TABLE espece AUTO_INCREMENT = ' . ($maxId + 1));

        $io->success(sprintf('%d espèce(s) insérée(s), %d mise(s) à jour.', $inserted, $updated));
    }

    // ── Import espèces + plants ────────────────────────────────────────────────
    private function importPlants(SymfonyStyle $io, string $path): void
    {
        $io->section('Import espèces + plants (' . basename($path) . ')');

        $rows = $this->readCsv($path);
        $io->writeln(count($rows) . ' lignes lues.');

        $especesDone  = [];
        $especesNew   = 0;
        $plantsOk     = 0;

        foreach ($rows as $i => $row) {
            $idEspece  = (int)  ($row['id_espece']  ?? 0);
            $nomEspece = trim($row['nom_espece'] ?? '');
            $idPlant   = (int)  ($row['id_plant']   ?? 0);
            $nomPlant  = trim($row['nom_plant']  ?? '');

            if ($idPlant === 0 || $nomPlant === '') {
                $io->writeln("<comment>Ligne " . ($i + 2) . " ignorée (id_plant ou nom_plant vide)</comment>");
                continue;
            }

            // ── Upsert espèce ─────────────────────────────────────────────────
            if ($idEspece > 0 && !isset($especesDone[$idEspece])) {
                $exists = $this->connection->fetchOne(
                    'SELECT 1 FROM espece WHERE id_espece = ?', [$idEspece]
                );
                if (!$exists) {
                    $this->connection->executeStatement(
                        'INSERT INTO espece (id_espece, nom_espece) VALUES (?, ?)',
                        [$idEspece, $nomEspece]
                    );
                    $especesNew++;
                } else {
                    $this->connection->executeStatement(
                        'UPDATE espece SET nom_espece = ? WHERE id_espece = ?',
                        [$nomEspece, $idEspece]
                    );
                }
                $especesDone[$idEspece] = true;
            }

            // ── Upsert plant ──────────────────────────────────────────────────
            $this->connection->executeStatement(
                'INSERT INTO plant (id_plant, nom_plant, id_espece)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE nom_plant = VALUES(nom_plant), id_espece = VALUES(id_espece)',
                [$idPlant, $nomPlant, $idEspece ?: null]
            );
            $plantsOk++;
        }

        // Réinitialise l'auto_increment pour ne pas écraser les nouvelles insertions
        $maxId = (int) $this->connection->fetchOne('SELECT MAX(id_plant) FROM plant');
        $this->connection->executeStatement('ALTER TABLE plant AUTO_INCREMENT = ' . ($maxId + 1));

        $maxIdEspece = (int) $this->connection->fetchOne('SELECT MAX(id_espece) FROM espece');
        $this->connection->executeStatement('ALTER TABLE espece AUTO_INCREMENT = ' . ($maxIdEspece + 1));

        $io->success(sprintf(
            '%d espèce(s) créées (%d total), %d plant(s) importées.',
            $especesNew, count($especesDone), $plantsOk
        ));
    }

    // ── Import UVs ─────────────────────────────────────────────────────────────
    private function importUvs(SymfonyStyle $io, string $path): void
    {
        $io->section('Import UVs (' . basename($path) . ')');

        $rows = $this->readCsv($path);
        $io->writeln(count($rows) . ' lignes lues.');

        $inserted = 0;
        $updated  = 0;
        $skipped  = 0;

        foreach ($rows as $i => $row) {
            $idEspece              = (int)  ($row['id_espece']              ?? 0);
            $nomUv                 = trim($row['nom_uv']                 ?? '');
            $nombrePlantParPlateaux = (int) ($row['nombre_plant_par_plateaux'] ?? 0);
            $nombreGraineParMotte  = (int)  ($row['nombre_graine_par_motte']  ?? 0);

            if ($nomUv === '' || $idEspece === 0) {
                $io->writeln("<comment>Ligne " . ($i + 2) . " ignorée (nom_uv ou id_espece vide)</comment>");
                $skipped++;
                continue;
            }

            // Vérifie que l'espèce existe
            $especeOk = $this->connection->fetchOne(
                'SELECT 1 FROM espece WHERE id_espece = ?', [$idEspece]
            );
            if (!$especeOk) {
                $io->writeln(sprintf(
                    '<comment>Ligne %d — UV "%s" ignorée : espèce %d introuvable</comment>',
                    $i + 2, $nomUv, $idEspece
                ));
                $skipped++;
                continue;
            }

            // Upsert par (nom_uv, id_espece)
            $existing = $this->connection->fetchOne(
                'SELECT id_uv FROM uv WHERE nom_uv = ? AND id_espece = ?',
                [$nomUv, $idEspece]
            );

            if ($existing) {
                $this->connection->executeStatement(
                    'UPDATE uv SET nombre_graine_par_motte = ?, nombre_plant_par_plateaux = ? WHERE id_uv = ?',
                    [$nombreGraineParMotte, $nombrePlantParPlateaux, $existing]
                );
                $updated++;
            } else {
                $this->connection->executeStatement(
                    'INSERT INTO uv (nom_uv, nombre_graine_par_motte, nombre_plant_par_plateaux, id_espece)
                     VALUES (?, ?, ?, ?)',
                    [$nomUv, $nombreGraineParMotte, $nombrePlantParPlateaux, $idEspece]
                );
                $inserted++;
            }
        }

        $io->success(sprintf(
            '%d UV insérée(s), %d mise(s) à jour, %d ignorée(s) (espèce manquante).',
            $inserted, $updated, $skipped
        ));
    }
}
