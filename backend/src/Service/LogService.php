<?php

namespace App\Service;

use App\Entity\Log;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;

class LogService
{
    public function log(
        EntityManagerInterface $em,
        Utilisateur $user,
        string $action,
        string $detail
    ): void {
        $log = new Log();
        $log->setAction($action);
        $log->setDateAction(new \DateTime());
        $log->setDetail($detail);
        $log->setUtilisateur($user);
        $em->persist($log);
        $em->flush();
    }
}
