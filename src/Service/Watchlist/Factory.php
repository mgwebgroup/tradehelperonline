<?php
/*
 * Copyright (c) Art Kurbakov <alex110504@gmail.com>
 *
 * For the full copyright and licence information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Service\Watchlist;

use App\Entity\Watchlist;
use Symfony\Bridge\Doctrine\RegistryInterface;
use App\Exception\WatchlistException;


class Factory
{
//    /**
//     * @var \Doctrine\Common\Persistence\ObjectManager
//     */
//    static $em;

//    public function __construct(
//      RegistryInterface $registry
//    )
//    {
//        $this->em = $registry;
//    }

    /**
     * @param string $name
     * @param \App\Entity\Instrument[] $list
     * @param \App\Entity\Formula[] $formulas
     * @param \Doctrine\Bundle\DoctrineBundle\Registry $em
     * @return Watchlist
     * @throws WatchlistException
     */
    public static function create($name, $list, $formulas, $em)
    {
        if ($watchlist = $em->getRepository(Watchlist::class)->findByName($name)) {
            throw new WatchlistException(sprintf('Watchlist %s already exists', $name));
        }

        $watchlist = new Watchlist();
        $watchlist->setName($name);
        foreach ($list as $instrument) {
            $watchlist->addInstrument($instrument);
        }
        foreach ($formulas as $formula) {
            $watchlist->addFormula($formula);
        }

        $watchlist->setCreatedAt(new \DateTime());

        return $watchlist;
    }

    public function addInstrument()
    {}

    public function removeInstrument()
    {}

    public function addFormula($formula)
    {}

    public function removeFormula($formula)
    {}

    public function delete($name)
    {}

    public function update($name)
    {}
}