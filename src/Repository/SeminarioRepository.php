<?php

namespace App\Repository;

use App\Entity\Seminario;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Seminario>
 */
class SeminarioRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Seminario::class);
    }

    public function findOtherSeminarios(Seminario $seminario): array
    {
        return $this->createQueryBuilder('s')
            ->where('s != :current')
            ->setParameter('current', $seminario)
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Seminario[] Returns an array of Seminario objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Seminario
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
