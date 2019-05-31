<?php

namespace App\Repository;

use App\Entity\OHLCVHistory;
use App\Entity\Instrument;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use App\Exception\PriceHistoryException;

/**
 * @method OHLCVHistory|null find($id, $lockMode = null, $lockVersion = null)
 * @method OHLCVHistory|null findOneBy(array $criteria, array $orderBy = null)
 * @method OHLCVHistory[]    findAll()
 * @method OHLCVHistory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OHLCVHistoryRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, OHLCVHistory::class);
    }

    /**
     * Deletes price history between dates. If both dates are null, all history for a given instrument and period
     *  will be deleted.
     * @param App\Entity\Instrument $instrument
     * @param DateTime $fromDate | null
     * @param DateTime $toDate | null
     * @param DateInterval entries for which time period supposed to be deleted
     * @param string $provider if no provider supplied price records for all providers will be removed
     */
    public function deleteHistory($instrument, $fromDate = null, $toDate = null, $interval, $provider = null)
    {
        if (!$instrument || !($instrument instanceof Instrument)) throw new PriceHistoryException('Parameter $instrument must be instance of App\Entity\Instrument');
        if (!$interval || !($interval instanceof \DateInterval)) throw new PriceHistoryException('Parameter $interval must be instance of \DateInterval');

        $qb = $this->createQueryBuilder('o');
        
        $qb->delete()
            ->where('o.instrument = :instrument')
            ->andWhere('o.timeinterval = :interval')
            ->setParameters(['instrument' => $instrument, 'interval' => $interval]);
        ;
        
        if ($provider) $qb->andWhere('o.provider = :provider')->setParameter('provider', $provider);

        if ($fromDate) $qb->andWhere('o.timestamp >= :fromDate')->setParameter('fromDate', $fromDate);

        if ($toDate) $qb->andWhere('o.timestamp <= :toDate')->setParameter('toDate', $toDate);

        $query = $qb->getQuery();

        // $result = $query->getResult();
        // var_dump($result); 
        $query->execute();
    }


    /**
     * Retrieves price history from storage for given dates. If both dates are null, all history for a given instrument
     *   and period will be retrieved.
     * @param App\Entity\Instrument $instrument
     * @param DateTime $fromDate | null
     * @param DateTime $toDate | null
     * @param DateInterval entries for which time period supposed to be retrieved
     * @param string $provider if no provider supplied price records for all providers will be retrieved
     */
    public function retrieveHistory($instrument, $fromDate = null, $toDate = null, $interval, $provider = null)
    {
        if (!$instrument || !($instrument instanceof Instrument)) throw new PriceHistoryException('Parameter $instrument must be instance of App\Entity\Instrument');
        if (!$interval || !($interval instanceof \DateInterval)) throw new PriceHistoryException('Parameter $interval must be instance of \DateInterval');

        $qb = $this->createQueryBuilder('o');
        
        $qb->where('o.instrument = :instrument')
            ->andWhere('o.timeinterval = :interval')
            ->setParameters(['instrument' => $instrument, 'interval' => $interval]);
        ;
        
        if ($provider) $qb->andWhere('o.provider = :provider')->setParameter('provider', $provider);

        if ($fromDate) $qb->andWhere('o.timestamp >= :fromDate')->setParameter('fromDate', $fromDate);

        if ($toDate) $qb->andWhere('o.timestamp <= :toDate')->setParameter('toDate', $toDate);

        $qb->orderBy('o.timestamp', 'ASC');

        $query = $qb->getQuery();

        return $query->getResult();
    }
    

    /*
    public function findOneBySomeField($value): ?OHLCVHistory
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
