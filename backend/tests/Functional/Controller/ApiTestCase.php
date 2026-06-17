<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Client;
use App\Entity\Espece;
use App\Entity\GfClient;
use App\Entity\HistoGfDeposee;
use App\Entity\Plant;
use App\Entity\Uv;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

abstract class ApiTestCase extends WebTestCase
{
    protected const ADMIN_EMAIL      = 'tc-admin@test.aquiplants';
    protected const ADMIN_PASSWORD   = 'AdminTest1234';
    protected const EMPLOYE_EMAIL    = 'tc-employe@test.aquiplants';
    protected const EMPLOYE_PASSWORD = 'EmployeTest1234';

    protected KernelBrowser $browser;
    protected ?string $adminToken   = null;
    protected ?string $employeToken = null;

    private array $toDelete = [];

    protected function setUp(): void
    {
        $this->browser  = static::createClient();
        $this->toDelete = [];

        // Vide le rate limiter filesystem entre les tests pour éviter le 429 cross-runs
        try {
            static::getContainer()->get('cache.rate_limiter')->clear();
        } catch (\Throwable) {}

        $this->createSchema();
        $this->createTestUsers();
        $this->adminToken   = $this->loginAs(self::ADMIN_EMAIL,   self::ADMIN_PASSWORD);
        $this->employeToken = $this->loginAs(self::EMPLOYE_EMAIL, self::EMPLOYE_PASSWORD);
    }

    protected function tearDown(): void
    {
        $conn = static::getContainer()->get(EntityManagerInterface::class)->getConnection();
        foreach (array_reverse($this->toDelete) as [$table, $pkCol, $id]) {
            try {
                $conn->executeStatement("DELETE FROM $table WHERE $pkCol = ?", [$id]);
            } catch (\Throwable) {}
        }
        parent::tearDown();
    }

    private function createSchema(): void
    {
        $conn = static::getContainer()->get(EntityManagerInterface::class)->getConnection();

        // Enregistre DATE_FORMAT pour la compatibilité SQLite (utilisé par StatistiquesController)
        $pdo = $conn->getNativeConnection();
        if ($pdo instanceof \PDO) {
            $pdo->sqliteCreateFunction('DATE_FORMAT', static function (string $date, string $format): string {
                $phpFormat = strtr($format, ['%Y' => 'Y', '%m' => 'm', '%d' => 'd', '%H' => 'H', '%i' => 'i', '%s' => 's']);
                return (new \DateTime($date))->format($phpFormat);
            }, 2);
        }

        // Purge les données orphelines de runs précédents (FK order : enfants d'abord)
        foreach ([
            'gf_histo_client', 'histo_gf_deposee', 'gf_client',
            'log', 'emplacement', 'uv', 'plant', 'client', 'espece', 'utilisateur',
        ] as $table) {
            try { $conn->executeStatement("DELETE FROM $table"); } catch (\Throwable) {}
        }

        $conn->executeStatement('
            CREATE TABLE IF NOT EXISTS utilisateur (
                id_utilisateur INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                nom            VARCHAR(100) NOT NULL,
                prenom         VARCHAR(100) NOT NULL,
                email          VARCHAR(150) NOT NULL,
                mdp_crypte     VARCHAR(255) NOT NULL,
                role           VARCHAR(20)  NOT NULL
            )
        ');
        $conn->executeStatement(
            'CREATE UNIQUE INDEX IF NOT EXISTS idx_utilisateur_email ON utilisateur (email)'
        );
        $conn->executeStatement('
            CREATE TABLE IF NOT EXISTS log (
                id_log         INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                action         VARCHAR(100) NOT NULL,
                date_action    DATETIME NOT NULL,
                detail         VARCHAR(255),
                id_utilisateur INTEGER NOT NULL,
                FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id_utilisateur)
            )
        ');
        $conn->executeStatement('
            CREATE TABLE IF NOT EXISTS espece (
                id_espece  INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                nom_espece VARCHAR(150) NOT NULL
            )
        ');
        $conn->executeStatement('
            CREATE TABLE IF NOT EXISTS plant (
                id_plant  INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                nom_plant VARCHAR(150) NOT NULL,
                id_espece INTEGER NULL
            )
        ');
        $conn->executeStatement('
            CREATE TABLE IF NOT EXISTS uv (
                id_uv                     INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                nom_uv                    VARCHAR(100) NOT NULL,
                nombre_graine_par_motte   INTEGER NOT NULL,
                nombre_plant_par_plateaux INTEGER NOT NULL,
                id_espece                 INTEGER NOT NULL
            )
        ');
        $conn->executeStatement('
            CREATE TABLE IF NOT EXISTS client (
                id_client     INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                nom_client    VARCHAR(100) NOT NULL,
                prenom_client VARCHAR(100) NOT NULL
            )
        ');
        $conn->executeStatement('
            CREATE TABLE IF NOT EXISTS emplacement (
                id_emplacement INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                lettre_etagere VARCHAR(1)  NOT NULL,
                numero_etage   INTEGER     NOT NULL,
                id_client      INTEGER     NULL
            )
        ');
        $conn->executeStatement('
            CREATE TABLE IF NOT EXISTS gf_client (
                id_gf_client        INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                numero_lot          VARCHAR(50)  NOT NULL,
                quantite_disponible INTEGER      NOT NULL,
                seuil_alerte        INTEGER      NOT NULL,
                nom_client          VARCHAR(150) NOT NULL,
                id_client           INTEGER      NOT NULL,
                id_plant            INTEGER      NOT NULL,
                id_emplacement      INTEGER      NULL
            )
        ');
        $conn->executeStatement('
            CREATE TABLE IF NOT EXISTS histo_gf_deposee (
                id_histo_depot   INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                quantite_deposee INTEGER NOT NULL,
                date_reception   DATE    NOT NULL,
                statut           VARCHAR(20) NOT NULL DEFAULT \'a_traiter\',
                note             VARCHAR(255) NULL,
                id_gf_client     INTEGER NOT NULL
            )
        ');
        $conn->executeStatement('
            CREATE TABLE IF NOT EXISTS gf_histo_client (
                id_histo            INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                quantite_semee      INTEGER NOT NULL,
                date_semis          DATE    NOT NULL,
                nb_graine_par_motte INTEGER NOT NULL,
                nom_uv              VARCHAR(100) NOT NULL,
                id_gf_client        INTEGER NOT NULL,
                id_uv               INTEGER NOT NULL
            )
        ');
    }

    private function createTestUsers(): void
    {
        $em     = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $repo   = $em->getRepository(Utilisateur::class);

        if (!$repo->findOneBy(['email' => self::ADMIN_EMAIL])) {
            $u = new Utilisateur();
            $u->setNom('Admin')->setPrenom('TC')
              ->setEmail(self::ADMIN_EMAIL)->setRole('admin');
            $u->setMdpCrypte($hasher->hashPassword($u, self::ADMIN_PASSWORD));
            $em->persist($u);
            $em->flush();
        }

        if (!$repo->findOneBy(['email' => self::EMPLOYE_EMAIL])) {
            $u = new Utilisateur();
            $u->setNom('Employe')->setPrenom('TC')
              ->setEmail(self::EMPLOYE_EMAIL)->setRole('employe');
            $u->setMdpCrypte($hasher->hashPassword($u, self::EMPLOYE_PASSWORD));
            $em->persist($u);
            $em->flush();
        }
    }

    protected function loginAs(string $email, string $password): ?string
    {
        $this->browser->request(
            'POST', '/api/login',
            [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email, 'password' => $password])
        );
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        return $data['token'] ?? null;
    }

    protected function jsonHeaders(string $token): array
    {
        return [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE'       => 'application/json',
        ];
    }

    protected function scheduleDelete(string $table, string $pkCol, int $id): void
    {
        $this->toDelete[] = [$table, $pkCol, $id];
    }

    // ── Factory helpers ───────────────────────────────────────────────────────

    protected function makeEspece(string $nom = 'Espece TC'): int
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $e  = new Espece();
        $e->setNomEspece($nom);
        $em->persist($e);
        $em->flush();
        $id = $e->getIdEspece();
        $this->scheduleDelete('espece', 'id_espece', $id);
        return $id;
    }

    protected function makePlant(string $nom = 'Plant TC', ?int $idEspece = null): int
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $p  = new Plant();
        $p->setNomPlant($nom);
        if ($idEspece !== null) {
            $esp = $em->getRepository(Espece::class)->find($idEspece);
            if ($esp) $p->setEspece($esp);
        }
        $em->persist($p);
        $em->flush();
        $id = $p->getIdPlant();
        $this->scheduleDelete('plant', 'id_plant', $id);
        return $id;
    }

    protected function makeUv(int $idEspece, string $nom = 'UV TC', int $graines = 5, int $plants = 10): int
    {
        $em  = static::getContainer()->get(EntityManagerInterface::class);
        $esp = $em->getRepository(Espece::class)->find($idEspece);
        $u   = new Uv();
        $u->setNomUv($nom)->setNombreGraineParMotte($graines)
          ->setNombrePlantParPlateaux($plants)->setEspece($esp);
        $em->persist($u);
        $em->flush();
        $id = $u->getIdUv();
        $this->scheduleDelete('uv', 'id_uv', $id);
        return $id;
    }

    protected function makeClient(string $nom = 'ClientTC', string $prenom = 'Prenom'): int
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $c  = new Client();
        $c->setNomClient($nom)->setPrenomClient($prenom);
        $em->persist($c);
        $em->flush();
        $id = $c->getIdClient();
        $this->scheduleDelete('client', 'id_client', $id);
        return $id;
    }

    protected function makeGfClient(int $idClient, int $idPlant, string $lot = 'LOT-TC', int $qte = 100, int $seuil = 10): int
    {
        $em     = static::getContainer()->get(EntityManagerInterface::class);
        $client = $em->getRepository(Client::class)->find($idClient);
        $plant  = $em->getRepository(Plant::class)->find($idPlant);
        $g      = new GfClient();
        $g->setNumeroLot($lot)->setQuantiteDisponible($qte)->setSeuilAlerte($seuil)
          ->setNomClient($client->getNomClient())->setClient($client)->setPlant($plant);
        $em->persist($g);
        $em->flush();
        $id = $g->getIdGfClient();
        $this->scheduleDelete('gf_client', 'id_gf_client', $id);
        return $id;
    }

    protected function makeHistoDeposee(int $idGfClient, string $statut = 'a_traiter', ?string $date = null): int
    {
        $em  = static::getContainer()->get(EntityManagerInterface::class);
        $gfc = $em->getRepository(GfClient::class)->find($idGfClient);
        $h   = new HistoGfDeposee();
        $h->setQuantiteDeposee(50)->setStatut($statut)
          ->setDateReception(new \DateTime($date ?? 'today'))->setGfClient($gfc);
        $em->persist($h);
        $em->flush();
        $id = $h->getIdHistoDepot();
        $this->scheduleDelete('histo_gf_deposee', 'id_histo_depot', $id);
        return $id;
    }

    protected function makeEmplacement(string $lettre = 'A', int $etage = 1): int
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $e  = new \App\Entity\Emplacement();
        $e->setLettreEtagere($lettre)->setNumeroEtage($etage);
        $em->persist($e);
        $em->flush();
        $id = $e->getIdEmplacement();
        $this->scheduleDelete('emplacement', 'id_emplacement', $id);
        return $id;
    }

    protected function deleteGfHistoClientByGfClient(int $idGfClient): void
    {
        $conn = static::getContainer()->get(EntityManagerInterface::class)->getConnection();
        $conn->executeStatement('DELETE FROM gf_histo_client WHERE id_gf_client = ?', [$idGfClient]);
    }
}
