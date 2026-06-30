<?php

namespace App\Repository;

use App\Entity\EventSeminar;
use App\Entity\Seminario;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EventSeminar>
 */
class EventSeminarRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventSeminar::class);
    }

    public function findEventsBetweenDates(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.start >= :start')
            ->andWhere('e.end <= :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->orderBy('e.start', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAllQueryBuilder(): \Doctrine\ORM\QueryBuilder
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.start', 'DESC');
    }

    public function searchQueryBuilder(string $q): \Doctrine\ORM\QueryBuilder
    {
        return $this->createQueryBuilder('e')
            ->where('e.title LIKE :q OR e.speaker LIKE :q OR e.institution LIKE :q')
            ->setParameter('q', '%' . $q . '%')
            ->orderBy('e.start', 'DESC');
    }

    public function findBySeminarioQueryBuilder(Seminario $seminario, ?int $year = null): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.seminar = :seminario')
            ->setParameter('seminario', $seminario)
            ->orderBy('e.start', 'DESC');

        if ($year !== null) {
            $qb->andWhere('e.start >= :start AND e.start <= :end')
               ->setParameter('start', new \DateTime("{$year}-01-01 00:00:00"))
               ->setParameter('end', new \DateTime("{$year}-12-31 23:59:59"));
        }

        return $qb;
    }

    public function findYearsBySeminario(Seminario $seminario): array
    {
        $events = $this->createQueryBuilder('e')
            ->where('e.seminar = :seminario')
            ->setParameter('seminario', $seminario)
            ->getQuery()
            ->getResult();

        $years = array_unique(array_map(
            fn(EventSeminar $e) => (int) $e->getStart()->format('Y'),
            $events
        ));

        rsort($years);
        return $years;
    }


//    /**
//     * @return EventSeminar[] Returns an array of EventSeminar objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('e.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?EventSeminar
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
