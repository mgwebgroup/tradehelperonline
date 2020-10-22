<?php

namespace App\Studies\MGWebGroup\MarketSurvey\Tests;

use App\Service\ExpressionHandler\OHLCV\Calculator;
use \Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Service\Scanner\OHLCV\Scanner;
use App\Entity\Watchlist;
use App\Studies\MGWebGroup\MarketSurvey\Entity\Study;
use App\Entity\Instrument;

class StudyTest extends KernelTestCase
{
    /**
     * @var
     */
//    private $SUT;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var \App\Service\Scanner\OHLCV\Scanner
     */
    private $scanner;

    /**
     * @var App\Entity\Watchlist
     */
    private $watchlist;

    /**
     * @var App\Service\ExpressionHandler\OHLCV\Calculator
     */
    private $calculator;

    /**
     * Details on how to access services in tests:
     * https://symfony.com/blog/new-in-symfony-4-1-simpler-service-testing
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::$container->get('doctrine')->getManager();
        $this->scanner = self::$container->get(Scanner::class);

        $this->watchlist = $this->em->getRepository(Watchlist::class)->findOneBy(['name' => 'y_universe']);
        $UTX = $this->em->getRepository(Instrument::class)->findOneBy(['symbol' => 'UTX']);
        $this->watchlist->removeInstrument($UTX);
        $RTN = $this->em->getRepository(Instrument::class)->findOneBy(['symbol' => 'RTN']);
        $this->watchlist->removeInstrument($RTN);

        $this->calculator = self::$container->get(Calculator::class);
    }

    public function testCreateStudy_15May2020_ShapesCount()
    {
        $date = new \DateTime('2020-05-15');

        $metric = [
          'Ins D & Up' => 2,
          'Ins Wk & Up' => 2.5,
          'Ins Mo & Up' => 2.75,
          'Ins D' => 0,
          'Ins Wk' => 0,
          'Ins Mo' => 0,
          'Ins D & Dwn' => -2,
          'Ins Wk & Dwn' => -2.5,
          'Ins Mo & Dwn' => -2.75,
          'D Hammer' => 3,
          'Wk Hammer' => 3.5,
          'Mo Hammer' => 4,
          'D Shtng Star' => -3,
          'Wk Shtng Star' => -3.5,
          'Mo Shtng Star' => -4.,
          'D Bullish Eng' => 1,
          'Wk Bullish Eng' => 1.5,
          'Mo Bullish Eng' => 2,
          'D Bearish Eng' => -1,
          'Wk Bearish Eng' => -1.5,
          'Mo Bearish Eng' => -2,
          'D Hammer & Up' => 3.75,
          'D Shtng Star & Down' => -3.75
        ];

        $score = $this->em->getRepository(Study::class)->calculateMarketScore($date, $this->watchlist, $metric);
    }
}
