<?php

namespace App\Studies\MGWebGroup\MarketSurvey\Tests;

use App\Entity\Expression;
use App\Repository\WatchlistRepository;
use App\Service\Exchange\Equities\NASDAQ;
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
     * @var array
     */
    private $metric;

    /**
     * @var App\Repository\Studies\MGWebGroup\MarketSurvey\Entity\StudyRepository
     */
    private $studyRepository;

    /**
     * @var App\Repository\WatchlistRepository
     */
    private $watchlistRepository;

    /**
     * @var App\Service\Exchange\Equities\Exchange
     */
    private $exchange;

    /**
     * Details on how to access services in tests:
     * https://symfony.com/blog/new-in-symfony-4-1-simpler-service-testing
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::$container->get('doctrine')->getManager();
        $this->scanner = self::$container->get(Scanner::class);

        $this->watchlistRepository = $this->em->getRepository(Watchlist::class);
        $this->watchlist = $this->watchlistRepository->findOneBy(['name' => 'y_universe']);
        $UTX = $this->em->getRepository(Instrument::class)->findOneBy(['symbol' => 'UTX']);
        $this->watchlist->removeInstrument($UTX);
        $RTN = $this->em->getRepository(Instrument::class)->findOneBy(['symbol' => 'RTN']);
        $this->watchlist->removeInstrument($RTN);

        $this->calculator = self::$container->get(Calculator::class);
        $this->metric = self::$container->getParameter('market-score');
        $this->studyRepository = $this->em->getRepository(Study::class);
        $this->exchange = self::$container->get(NASDAQ::class);
    }

    public function testMarketBreadth_15May2020_MarketScore()
    {
        $date = new \DateTime('2020-05-15');

        $marketBreadth = $this->studyRepository->getMarketBreadth($date, $this->watchlist, $this->metric);
    }

    public function testInsideBarBOBD_15May2020_BreakoutTable()
    {
        $date = new \DateTime('2020-05-15');

        // see if Ins Bar watchlist was saved for the previous T
        $prevT = $this->exchange->calcPreviousTradingDay($date);
        $watchlistName = sprintf('ins_bar_bobd_%s', $prevT->format('ymd'));
        $prevTInsBarWatchlist = $this->watchlistRepository->findOneBy(['name' => $watchlistName]);
        if (!$prevTInsBarWatchlist) {
            $expressionList = [ 'Ins D BO', 'Ins D BD', 'Pos on D', 'Neg on D', 'Ins Wk BO', 'Ins Wk BD', 'Pos on Wk',
              'Neg on Wk', 'Ins Mo BO', 'Ins Mo BD', 'Pos on Mo', 'Neg on Mo'];
            foreach ($expressionList as $exprName) {
                $expressions[] = $this->em->getRepository(Expression::class)->findOneBy(['name' => $exprName]);
            }

            $prevTInsBarWatchlist = WatchlistRepository::createWatchlist($watchlistName, null, $expressions);
        }


        $breakoutTable = $this->studyRepository->figureInsideBarBOBD();
    }

    public function tearDown()
    {

    }
}
