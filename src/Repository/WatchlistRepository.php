<?php

namespace App\Repository;

use App\Entity\Watchlist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Watchlist|null find($id, $lockMode = null, $lockVersion = null)
 * @method Watchlist|null findOneBy(array $criteria, array $orderBy = null)
 * @method Watchlist[]    findAll()
 * @method Watchlist[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WatchlistRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Watchlist::class);
    }

    public static function createWatchlist($name, $description = null, $expressions = [], $instruments = [])
    {
        $watchlist = new Watchlist();
        $watchlist->setCreatedAt(new \DateTime())
          ->setName($name)
          ->setDescription($description)
        ;

        foreach ($expressions as $expression) {
            $watchlist->addExpression($expression);
        }

        foreach ($instruments as $instrument) {
            $watchlist->addInstrument($instrument);
        }

        return $watchlist;
    }
}
