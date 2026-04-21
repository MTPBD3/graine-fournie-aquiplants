<?php

namespace App\Service;

use App\Entity\Log;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;

class LogService
{
    public function log(EntityManagerInterface $em, ?Utilisateur $user, string $action, string $detail = ''): void
    {
        if (!$user) return;

        $log = new Log();
        $log->setAction($action);
        $log->setDetail($detail);
        $log->setDateAction(new \DateTime());
        $log->setUtilisateur($user);

        $em->persist($log);
        $em->flush();
    }
}
