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
        $repo = $manager->getRepository(Utilisateur::class);

        if (!$repo->findOneBy(['email' => 'admin@aquiplants.fr'])) {
            $admin = new Utilisateur();
            $admin->setNom('Admin')->setPrenom('AQUIPLANTS')
                  ->setEmail('admin@aquiplants.fr')->setRole('admin')
                  ->setMdpCrypte($this->hasher->hashPassword($admin, 'test'));
            $manager->persist($admin);
        }

        if (!$repo->findOneBy(['email' => 'employe@aquiplants.fr'])) {
            $employe = new Utilisateur();
            $employe->setNom('Dupont')->setPrenom('Jean')
                    ->setEmail('employe@aquiplants.fr')->setRole('employe')
                    ->setMdpCrypte($this->hasher->hashPassword($employe, 'test'));
            $manager->persist($employe);
        }

        $manager->flush();
    }
}
