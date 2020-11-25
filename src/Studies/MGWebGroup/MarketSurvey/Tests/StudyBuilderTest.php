<?php

namespace App\Studies\MGWebGroup\MarketSurvey\Tests;

use App\Entity\Instrument;
use App\Entity\Study\ArrayAttribute;
use App\Entity\Watchlist;
use App\Entity\Study\Study;
use App\Studies\MGWebGroup\MarketSurvey\StudyBuilder;
use DoctrineExtensions\Query\Mysql\StrToDate;
use League\Csv\Reader;
use \Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\Common\Collections\Criteria;

class StudyBuilderTest extends KernelTestCase
{
    use Formulas;

    const INTERVAL_DAILY =  '+P00Y00M01DT00H00M00S';
    const INTERVAL_WEEKLY = '+P00Y00M07DT00H00M00S';
    const INTERVAL_MONTHLY = '+P00Y01M00DT00H00M00S';

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

        $date = new \DateTime('2020-05-15');
        $name = 'test_market_study';
        $this->SUT->createStudy($date, $name);
    }

    public function testMarketBreadth_15May2020_MarketBreadth()
    {
        $date = new \DateTime('2020-05-15');

        $csv = Reader::createFromPath('src/Studies/MGWebGroup/MarketSurvey/DataFixtures/watchlist_test.csv');
        $csv->setHeaderOffset(0);

        $insideDAndUp = [];
        $DBearishEng = [];
        $DShtngStarAndDown = [];
        $insWkAndUp = [];
        $WkBullishEng = [];
        $MoShtngStar = [];
        $MoBullishEng = [];

        fwrite(STDOUT, 'Scanning market breadth using QueryBuilder...');
        foreach ($csv->getRecords() as $record) {
            $instrument = $this->em->getRepository(Instrument::class)->findOneBy(['symbol' => $record['Symbol']]);

            if ($this->insideDayAndUp($instrument, $date)) {
                $insideDAndUp[] = $instrument;
            }

            if ($this->dBearishEng($instrument, $date)) {
                $DBearishEng[] = $instrument;
            }

            if ($this->dShtngStarAndDown($instrument, $date)) {
                $DShtngStarAndDown[] = $instrument;
            }

            if ($this->insideWkAndUp($instrument, $date)) {
                $insWkAndUp[] = $instrument;
            }

            if ($this->wkBullishEng($instrument, $date)) {
                $WkBullishEng[] = $instrument;
            }

            if($this->moShtngStar($instrument, $date)) {
                $MoShtngStar[] = $instrument;
            }

            if ($this->moBullishEng($instrument, $date)) {
                $MoBullishEng[] = $instrument;
            }
        }
        fwrite(STDOUT, 'complete.'.PHP_EOL);

        $watchlist = $this->watchlistRepository->findOneBy(['name' => 'watchlist_test']);

        fwrite(STDOUT, 'Calculating Market Breadth. This will take a while...');
        $this->SUT->calculateMarketBreadth($watchlist);
        fwrite(STDOUT, 'complete.'.PHP_EOL);

        $getMarketBreadth = new Criteria(Criteria::expr()->eq('attribute', 'market-breadth'));
        $survey = $this->SUT->getStudy()->getArrayAttributes()->matching($getMarketBreadth)->first()->getValue();

        $this->assertCount(1, $survey['Ins D & Up']);
        $this->assertArraySubset($insideDAndUp, $survey['Ins D & Up']);
        $this->assertCount(0, $survey['D Bearish Eng']);
        $this->assertCount(0, $survey['D Shtng Star & Down']);
        $this->assertCount(2, $survey['Ins Wk & Up']);
        $this->assertArraySubset($insWkAndUp, $survey['Ins Wk & Up']);
        $this->assertCount(1, $survey['Wk Bullish Eng']);
        $this->assertArraySubset($WkBullishEng, $survey['Wk Bullish Eng']);
        $this->assertCount(2, $survey['Mo Shtng Star']);
        $this->assertArraySubset($MoShtngStar, $survey['Mo Shtng Star']);
        $this->assertCount(1, $survey['Mo Bullish Eng']);
        $this->assertArraySubset($MoBullishEng, $survey['Mo Bullish Eng']);

        $this->em->persist($this->SUT->getStudy());
        $this->em->flush();

        $study = $this->em->getRepository(Study::class)->findOneBy(['name' => 'test_market_study']);
        $this->assertInstanceOf('App\Entity\Study\Study', $study);

        /** @var ArrayAttribute $survey */
        $survey = $study->getArrayAttributes()->matching($getMarketBreadth)->first()->getValue();
        $this->assertCount(23, $survey);

        $this->em->remove($study);
        $this->em->flush();
    }

    public function testFigureInsideBarBOBD_14May2020_BOBDTable()
    {
        $date1 = new \DateTime('2020-05-14');
        $this->SUT->getStudy()->setDate($date1);

        $watchlist = $this->watchlistRepository->findOneBy(['name' => 'watchlist_test']);

        fwrite(STDOUT, 'Calculating Market Breadth. This will take a while...');
        $this->SUT->calculateMarketBreadth($watchlist);
        fwrite(STDOUT, 'complete.'.PHP_EOL);

        $pastStudy = $this->SUT->getStudy();

        $date2 = new \DateTime('2020-05-15');
        $name = 'test_market_study';
        $this->SUT->createStudy($date2, $name);

        $this->SUT->figureInsideBarBOBD($pastStudy, $date2);

        $comparison = Criteria::expr()->eq('name', StudyBuilder::INSIDE_BAR_DAY);
        $insideBarWatchlistCriterion = new Criteria($comparison);
        $insideBarWatchlist = $pastStudy->getWatchlists()->matching($insideBarWatchlistCriterion)->first();
        $insDBO = [];
        $insDBD = [];
        $posOnD = [];
        $negOnD = [];
        foreach ($insideBarWatchlist->getInstruments() as $instrument) {
            if ($this->insDBO($instrument, $date2)) {
                $insDBO[] = $instrument;
            }
            if ($this->insDBD($instrument, $date2)) {
                $insDBD[] = $instrument;
            }
            if ($this->posOnD($instrument, $date2)) {
                $posOnD[] = $instrument;
            }
            if ($this->negOnD($instrument, $date2)) {
                $negOnD[] = $instrument;
            }
        }

        $comparison = Criteria::expr()->eq('name', StudyBuilder::INSIDE_BAR_WK);
        $insideBarWatchlistCriterion = new Criteria($comparison);
        $insideBarWatchlist = $pastStudy->getWatchlists()->matching($insideBarWatchlistCriterion)->first();
        $insWkBO = [];
        $negOnWk = [];
        foreach ($insideBarWatchlist->getInstruments() as $instrument) {
            if ($this->insWkBO($instrument, $date2)) {
                $insWkBO[] = $instrument;
            }
            if ($this->negOnWk($instrument, $date2)) {
                $negOnWk[] = $instrument;
            }
        }

        $comparison = Criteria::expr()->eq('name', StudyBuilder::INSIDE_BAR_MO);
        $insideBarWatchlistCriterion = new Criteria($comparison);
        $insideBarWatchlist = $pastStudy->getWatchlists()->matching($insideBarWatchlistCriterion)->first();
        $insMoBO = [];
        $insMoBD = [];
        foreach ($insideBarWatchlist->getInstruments() as $instrument) {
            if ($this->insMoBO($instrument, $date2)) {
                $insMoBO[] = $instrument;
            }
            if ($this->insMoBD($instrument, $date2)) {
                $insMoBD[] = $instrument;
            }
        }

        $getBOBDDaily = new Criteria(Criteria::expr()->eq('attribute', 'bobd-daily'));
        $bobdDaily = $this->SUT->getStudy()->getArrayAttributes()->matching($getBOBDDaily)->first()->getValue();
        $getBOBDWeekly = new Criteria(Criteria::expr()->eq('attribute', 'bobd-weekly'));
        $bobdWeekly = $this->SUT->getStudy()->getArrayAttributes()->matching($getBOBDWeekly)->first()->getValue();
        $getBOBDMonthly = new Criteria(Criteria::expr()->eq('attribute', 'bobd-monthly'));
        $bobdMonthly = $this->SUT->getStudy()->getArrayAttributes()->matching($getBOBDMonthly)->first()->getValue();


        $this->assertCount(count($insDBO), $bobdDaily['survey'][StudyBuilder::INS_D_BO]);
        $this->assertArraySubset($insDBO, $bobdDaily['survey'][StudyBuilder::INS_D_BO]);
        $this->assertCount(count($insDBD), $bobdDaily['survey'][StudyBuilder::INS_D_BD]);
        $this->assertArraySubset($insDBD, $bobdDaily['survey'][StudyBuilder::INS_D_BD]);
        $this->assertCount(count($posOnD), $bobdDaily['survey'][StudyBuilder::POS_ON_D]);
        $this->assertArraySubset($posOnD, $bobdDaily['survey'][StudyBuilder::POS_ON_D]);
        $this->assertCount(count($negOnD), $bobdDaily['survey'][StudyBuilder::NEG_ON_D]);
        $this->assertArraySubset($negOnD, $bobdDaily['survey'][StudyBuilder::NEG_ON_D]);

        $this->assertCount(count($insWkBO), $bobdWeekly['survey'][StudyBuilder::INS_WK_BO]);
        $this->assertArraySubset($insWkBO, $bobdWeekly['survey'][StudyBuilder::INS_WK_BO]);
        $this->assertCount(count($negOnWk), $bobdWeekly['survey'][StudyBuilder::NEG_ON_WK]);
        $this->assertArraySubset($negOnWk, $bobdWeekly['survey'][StudyBuilder::NEG_ON_WK]);

        $this->assertCount(count($insMoBO), $bobdMonthly['survey'][StudyBuilder::INS_MO_BO]);
        $this->assertArraySubset($insMoBO, $bobdMonthly['survey'][StudyBuilder::INS_MO_BO]);
        $this->assertCount(count($insMoBD), $bobdMonthly['survey'][StudyBuilder::INS_MO_BD]);
        $this->assertArraySubset($insMoBD, $bobdMonthly['survey'][StudyBuilder::INS_MO_BD]);
    }

    public function testMarketScoreDelta()
    {
        $watchlist = $this->watchlistRepository->findOneBy(['name' => 'watchlist_test']);
        $getScore = new Criteria(Criteria::expr()->eq('attribute', 'market-score'));

        $date1 = new \DateTime('2020-05-14');
        $this->SUT->getStudy()->setDate($date1);
        fwrite(STDOUT, 'Calculating Market Breadth. This will take a while...');
        $this->SUT->calculateMarketBreadth($watchlist);
        fwrite(STDOUT, 'complete.'.PHP_EOL);
        $pastStudy = $this->SUT->getStudy();
        $pastScore = $pastStudy->getFloatAttributes()->matching($getScore)->first()->getValue();

        $date2 = new \DateTime('2020-05-15');
        $name = 'test_market_study';
        $this->SUT->createStudy($date2, $name);

        fwrite(STDOUT, 'Calculating Market Breadth. This will take a while...');
        $this->SUT->calculateMarketBreadth($watchlist);
        fwrite(STDOUT, 'complete.'.PHP_EOL);

        $this->SUT->calculateScoreDelta($pastStudy);

        $currentStudy = $this->SUT->getStudy();
        $currentScore = $currentStudy->getFloatAttributes()->matching($getScore)->first()->getValue();

        $scoreDelta = $currentScore - $pastScore;

        $getScoreDelta = new Criteria(Criteria::expr()->eq('attribute', 'score-delta'));
        $actualScoreDelta = $currentStudy->getFloatAttributes()->matching($getScoreDelta)->first()->getValue();

        $this->assertEquals($scoreDelta, $actualScoreDelta);
    }

    public function testActionableSymbolsWatchlist()
    {
        $date = new \DateTime('2020-05-15');
        $watchlist = $this->watchlistRepository->findOneBy(['name' => 'watchlist_test']);

        fwrite(STDOUT, 'Calculating Market Breadth. This will take a while...');
        $this->SUT->calculateMarketBreadth($watchlist);
        fwrite(STDOUT, 'complete.'.PHP_EOL);

        $this->SUT->buildActionSymbolsWatchlist();

        $getASWatchilst = new Criteria(Criteria::expr()->eq('name', 'AS'));
        $ASWatchlist = $this->SUT->getStudy()->getWatchlists()->matching($getASWatchilst)->first();
        $ASInstruments = $ASWatchlist->getInstruments()->toArray();

        $watchlistsOfInterestVolumeOnly = [StudyBuilder::INSIDE_BAR_DAY, StudyBuilder::D_BULLISH_ENG, StudyBuilder::D_BEARISH_ENG];
        $getWatchlistsOfInterestVolumeOnly = new Criteria(Criteria::expr()->in('name', $watchlistsOfInterestVolumeOnly));
        $watchlists = $this->SUT->getStudy()->getWatchlists()->matching($getWatchlistsOfInterestVolumeOnly);
        foreach ($watchlists as $watchlist) {
            $dataSet = [];
            foreach ($watchlist->getInstruments() as $instrument) {
                $dataSet['instrument'][] = $instrument;
                list($h0, $h1, $h2) = $this->getDailyPrices($instrument, $date);
                $dataSet['volume'][] = $h0->getVolume();
            }
            array_multisort($dataSet['volume'], SORT_DESC, $dataSet['instrument']);
            $top10 = array_slice($dataSet['instrument'], 0, 10);
            foreach ($top10 as $instrument) {
                $this->assertContains($instrument, $ASInstruments);
            }
        }

        $watchlistsOfInterestPAndVolume = [StudyBuilder::INS_D_AND_UP, StudyBuilder::D_HAMMER, StudyBuilder::D_HAMMER_AND_UP];
        $getWatchlistsOfInterestPAndVolume = new Criteria(Criteria::expr()->in('name', $watchlistsOfInterestPAndVolume));
        $watchlists = $this->SUT->getStudy()->getWatchlists()->matching($getWatchlistsOfInterestPAndVolume);
        foreach ($watchlists as $watchlist) {
            $dataSet = [];
            foreach ($watchlist->getInstruments() as $instrument) {
                $dataSet['instrument'][] = $instrument;
                list($h0, $h1, $h2) = $this->getDailyPrices($instrument, $date);
                $dataSet['Pos on D'][] = $h0->getClose() - $h0->getOpen();
                $dataSet['volume'][] = $h0->getVolume();
            }
            array_multisort($dataSet['Pos on D'], SORT_DESC, $dataSet['volume'], SORT_DESC, $dataSet['instrument']);
            $top10 = array_slice($dataSet['instrument'], 0, 10);
            foreach ($top10 as $instrument) {
                $this->assertContains($instrument, $ASInstruments);
            }
        }

        $watchlistsOfInterestPAndVolume = [StudyBuilder::INS_D_AND_DWN, StudyBuilder::D_SHTNG_STAR, StudyBuilder::D_SHTNG_STAR_AND_DWN];
        $getWatchlistsOfInterestPAndVolume = new Criteria(Criteria::expr()->in('name', $watchlistsOfInterestPAndVolume));
        $watchlists = $this->SUT->getStudy()->getWatchlists()->matching($getWatchlistsOfInterestPAndVolume);
        foreach ($watchlists as $watchlist) {
            $dataSet = [];
            foreach ($watchlist->getInstruments() as $instrument) {
                $dataSet['instrument'][] = $instrument;
                list($h0, $h1, $h2) = $this->getDailyPrices($instrument, $date);
                $dataSet['Neg on D'][] = $h0->getClose() - $h0->getOpen();
                $dataSet['volume'][] = $h0->getVolume();
            }
            array_multisort($dataSet['Neg on D'], SORT_ASC, $dataSet['volume'], SORT_DESC, $dataSet['instrument']);
            $top10 = array_slice($dataSet['instrument'], 0, 10);
            foreach ($top10 as $instrument) {
                $this->assertContains($instrument, $ASInstruments);
            }
        }
    }

    public function testASBOBD()
    {

    }

    public function testCreateFullStudy_15May2020_FullStudySaved()
    {
        $watchlist = $this->watchlistRepository->findOneBy(['name' => 'watchlist_test']);

        $date1 = new \DateTime('2020-05-14');
        $this->SUT->getStudy()->setDate($date1);
        fwrite(STDOUT, 'Calculating Market Breadth. This will take a while...');
        $this->SUT->calculateMarketBreadth($watchlist);
        fwrite(STDOUT, 'complete.'.PHP_EOL);
        $this->SUT->buildActionSymbolsWatchlist($watchlist);
        $pastStudy = $this->SUT->getStudy();

        $date2 = new \DateTime('2020-05-15');
        $name = 'test_market_study';
        $this->SUT->createStudy($date2, $name);
        fwrite(STDOUT, 'Calculating Market Breadth. This will take a while...');
        $this->SUT->calculateMarketBreadth($watchlist);
        fwrite(STDOUT, 'complete.'.PHP_EOL);
        $this->SUT->calculateScoreDelta($pastStudy);
        $this->SUT->figureInsideBarBOBD($pastStudy, $date2);
        $this->SUT->figureASBOBD($pastStudy, $date2);

        // Figure current study's Actionable Symbols list
        $this->SUT->buildActionSymbolsWatchlist();

        // Create Market Score table for 21 days


        $this->em->persist($this->SUT->getStudy());
        $this->em->flush();
    }
}