<?php

namespace App\Repository;

use App\Entity\Instrument;
use App\Service\Exchange\ExchangeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;


class InstrumentRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Instrument::class);
    }

    /**
     * Deletes all instruments by exchange
     * @param ExchangeInterface $exchange
     */
    public function deleteInstruments($exchange = null)
    {
        $qb = $this->createQueryBuilder('i');
        if ($exchange) {
            $qb->andWhere('i.exchange = :exchange');
            $qb->setParameter('exchange', $exchange->getExchangeName());
        }
        $qb->delete();

        $query = $qb->getQuery();
        $query->execute();
    }

//    public function removeQuote($instrument)
//    {
//        $instrument = $this->find($instrument->getId());
//
//        $instrument->unsetOHLCVQuote();
//    }

    // /**
    //  * @return Instrument[] Returns an array of Instrument objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('i.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Instrument
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
