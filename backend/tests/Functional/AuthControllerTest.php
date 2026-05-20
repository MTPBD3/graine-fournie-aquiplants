<?php

namespace App\Tests\Functional;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthControllerTest extends WebTestCase
{
    private const TEST_EMAIL    = 'test-auth@example.fr';
    private const TEST_PASSWORD = 'motdepasse_test';

    private KernelBrowser $browser;

    protected function setUp(): void
    {
        // createClient() boot le kernel une seule fois par test (Symfony 7)
        $this->browser = static::createClient();

        $em = static::getContainer()->get(EntityManagerInterface::class);

        // Schéma minimal SQLite — évite les colonnes ENUM d'autres entités
        $em->getConnection()->executeStatement('
            CREATE TABLE IF NOT EXISTS utilisateur (
                id_utilisateur INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                nom            VARCHAR(100) NOT NULL,
                prenom         VARCHAR(100) NOT NULL,
                email          VARCHAR(150) NOT NULL,
                mdp_crypte     VARCHAR(255) NOT NULL,
                role           VARCHAR(20)  NOT NULL
            )
        ');
        $em->getConnection()->executeStatement(
            'CREATE UNIQUE INDEX IF NOT EXISTS idx_utilisateur_email ON utilisateur (email)'
        );

        // Table log nécessaire au LoginSuccessListener (audit trail)
        $em->getConnection()->executeStatement('
            CREATE TABLE IF NOT EXISTS log (
                id_log         INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                action         VARCHAR(100) NOT NULL,
                date_action    DATETIME NOT NULL,
                detail         VARCHAR(255),
                id_utilisateur INTEGER NOT NULL,
                FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id_utilisateur)
            )
        ');

        if (!$em->getRepository(Utilisateur::class)->findOneBy(['email' => self::TEST_EMAIL])) {
            /** @var UserPasswordHasherInterface $hasher */
            $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

            $u = new Utilisateur();
            $u->setNom('Test')->setPrenom('Auth')->setEmail(self::TEST_EMAIL)->setRole('employe');
            $u->setMdpCrypte($hasher->hashPassword($u, self::TEST_PASSWORD));

            $em->persist($u);
            $em->flush();
        }
    }

    public function testLoginChampsManquantsRetourne400(): void
    {
        $this->browser->request('POST', '/api/login', [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
        $this->assertResponseStatusCodeSame(400);
    }

    public function testLoginEmailInconnuRetourne401(): void
    {
        $this->browser->request(
            'POST', '/api/login', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'inconnu@example.fr', 'password' => 'nimporte'])
        );
        $this->assertResponseStatusCodeSame(401);
    }

    public function testLoginMotDePasseEroneRetourne401(): void
    {
        $this->browser->request(
            'POST', '/api/login', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => self::TEST_EMAIL, 'password' => 'mauvais_mdp'])
        );
        $this->assertResponseStatusCodeSame(401);
    }

    public function testLoginValideRetourne200AvecToken(): void
    {
        $this->browser->request(
            'POST', '/api/login', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => self::TEST_EMAIL, 'password' => self::TEST_PASSWORD])
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $data);
        $this->assertNotEmpty($data['token']);
    }
}
