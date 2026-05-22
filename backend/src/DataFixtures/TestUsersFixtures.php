<?php

namespace App\DataFixtures;

use App\Entity\Utilisateur;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class TestUsersFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $manager): void
    {
        $users = [
            [
                'email'  => 'testadmin@aquiplants.fr',
                'mdp'    => 'admin',
                'role'   => 'admin',
                'nom'    => 'Admin',
                'prenom' => 'Test',
            ],
            [
                'email'  => 'testuser@aquiplants.fr',
                'mdp'    => 'user',
                'role'   => 'employe',
                'nom'    => 'User',
                'prenom' => 'Test',
            ],
        ];

        foreach ($users as $data) {
            if ($manager->getRepository(Utilisateur::class)->findOneBy(['email' => $data['email']])) {
                continue;
            }

            $u = new Utilisateur();
            $u->setNom($data['nom'])
              ->setPrenom($data['prenom'])
              ->setEmail($data['email'])
              ->setRole($data['role'])
              ->setMdpCrypte($this->hasher->hashPassword($u, $data['mdp']));

            $manager->persist($u);
        }

        $manager->flush();
    }
}
