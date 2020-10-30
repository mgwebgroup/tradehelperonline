<?php

namespace App\Studies\MGWebGroup\MarketSurvey\Tests;

use App\Entity\Instrument;
use App\Entity\Watchlist;
use App\Studies\MGWebGroup\MarketSurvey\StudyBuilder;
use \Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Service\Exchange\Equities\NASDAQ;

class StudyBuilderTest extends KernelTestCase
{
    /**
     * @var
     */
    private $SUT;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;


    private $watchlistRepository;

    /**
     * Details on how to access services in tests:
     * https://symfony.com/blog/new-in-symfony-4-1-simpler-service-testing
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $this->SUT = self::$container->get(StudyBuilder::class);
        $this->em = self::$container->get('doctrine')->getManager();

        $this->watchlistRepository = $this->em->getRepository(Watchlist::class);
    }

    public function testGetStudy_15May2020_newStudyObject()
    {
        $date = new \DateTime('2020-05-15');
        $name = 'market_study';
        $study = $this->SUT->getStudy($date, $name);
    }

    public function testMarketBreadth_15May2020_MarketBreadth()
    {
        $date = new \DateTime('2020-05-15');

        $watchlist = $this->watchlistRepository->findOneBy(['name' => 'short']);
        $UTX = $this->em->getRepository(Instrument::class)->findOneBy(['symbol' => 'UTX']);
        $watchlist->removeInstrument($UTX);
        $RTN = $this->em->getRepository(Instrument::class)->findOneBy(['symbol' => 'RTN']);
        $watchlist->removeInstrument($RTN);

        $marketBreadth = $this->SUT->calculateMarketBreadth($date, $watchlist);

        $this->SUT->saveInsideBarWatchlists($date, $marketBreadth[1]);

        $insideDayWatchlist = $this->watchlistRepository->findOneBy(['name' => 'Ins D_200515']);
    }

    public function testFigureInsideBarBOBD_18May2020_BOBDTable()
    {
        $date = new \DateTime('2020-05-18');

        $exchange = self::$container->get(NASDAQ::class);

        $bobdTable = $this->SUT->figureInsideBarBOBD($date, $exchange);
    }
}