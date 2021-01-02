<?php

namespace App\Repository;

use App\Entity\Instrument;
use App\Service\Exchange\ExchangeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Class InstrumentRepository
 * @package App\Repository
 * @method Instrument findOneBySymbol(string $symbol) Finds instrument by its symbol name
 */
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
}
