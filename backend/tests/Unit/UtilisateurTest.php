<?php

namespace App\Tests\Unit;

use App\Entity\Utilisateur;
use PHPUnit\Framework\TestCase;

class UtilisateurTest extends TestCase
{
    public function testRoleParDefautEstEmploye(): void
    {
        $u = new Utilisateur();
        $this->assertSame('employe', $u->getRole());
        $this->assertContains('ROLE_EMPLOYE', $u->getRoles());
    }

    public function testRoleAdminExpliciteRetourneRoleAdmin(): void
    {
        $u = new Utilisateur();
        $u->setRole('admin');

        $this->assertSame('admin', $u->getRole());
        $this->assertContains('ROLE_ADMIN', $u->getRoles());
        $this->assertNotContains('ROLE_EMPLOYE', $u->getRoles());
    }

    public function testRoleEmployeExpliciteRetourneRoleEmploye(): void
    {
        $u = new Utilisateur();
        $u->setRole('employe');

        $this->assertContains('ROLE_EMPLOYE', $u->getRoles());
        $this->assertNotContains('ROLE_ADMIN', $u->getRoles());
    }

    public function testValeurRoleEmployeDepuisControllerRetourneRoleEmploye(): void
    {
        // UtilisateurController utilise 'ROLE_EMPLOYE' comme valeur par défaut
        $u = new Utilisateur();
        $u->setRole('ROLE_EMPLOYE');

        $this->assertContains('ROLE_EMPLOYE', $u->getRoles());
        $this->assertNotContains('ROLE_ADMIN', $u->getRoles());
    }

    public function testEmailEstIdentifiantUtilisateur(): void
    {
        $u = new Utilisateur();
        $u->setEmail('admin@aquiplants.fr');

        $this->assertSame('admin@aquiplants.fr', $u->getUserIdentifier());
    }

    public function testNomEtPrenomSontStockes(): void
    {
        $u = new Utilisateur();
        $u->setNom('Dupont')->setPrenom('Marie');

        $this->assertSame('Dupont', $u->getNom());
        $this->assertSame('Marie', $u->getPrenom());
    }
}
