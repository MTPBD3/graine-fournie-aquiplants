<?php

namespace App\Repository;

use App\Entity\HistoGfDeposee;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class HistoGfDeposeeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HistoGfDeposee::class);
    }

    public function findEnAttenteSince(\DateTimeInterface $limite): array
    {
        return $this->createQueryBuilder('h')
            ->where('h.statut = :statut')
            ->andWhere('h.dateReception <= :limite')
            ->setParameter('statut', 'en_attente')
            ->setParameter('limite', $limite)
            ->orderBy('h.dateReception', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
