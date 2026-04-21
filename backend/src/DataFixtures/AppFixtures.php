<?php

namespace App\DataFixtures;

use App\Entity\Utilisateur;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $manager): void
    {
        // Admin
        $admin = new Utilisateur();
        $admin->setNom('Admin');
        $admin->setPrenom('AQUIPLANTS');
        $admin->setEmail('admin@aquiplants.fr');
        $admin->setRole('admin');
        $admin->setMdpCrypte($this->hasher->hashPassword($admin, 'admin1234'));
        $manager->persist($admin);

        // Employé de test
        $employe = new Utilisateur();
        $employe->setNom('Dupont');
        $employe->setPrenom('Jean');
        $employe->setEmail('employe@aquiplants.fr');
        $employe->setRole('employe');
        $employe->setMdpCrypte($this->hasher->hashPassword($employe, 'employe1234'));
        $manager->persist($employe);

        $manager->flush();
    }
}
