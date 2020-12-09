<?php

namespace App\Studies\MGWebGroup\MarketSurvey\Tests;

use App\Entity\Instrument;
use App\Entity\Study\ArrayAttribute;
use App\Entity\Watchlist;
use App\Entity\Study\Study;
use App\Studies\MGWebGroup\MarketSurvey\StudyBuilder;
use League\Csv\Reader;
use \Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\Common\Collections\Criteria;

class StudyBuilderTest extends KernelTestCase
{
    use Formulas;

    const INTERVAL_DAILY =  '+P00Y00M01DT00H00M00S';
    const INTERVAL_WEEKLY = '+P00Y00M07DT00H00M00S';
    const INTERVAL_MONTHLY = '+P00Y01M00DT00H00M00S';

    const WATCHLIST_NAME = 'watchlist_test';
    const STUDY_NAME = 'test_market_study';

    /**
     * @var
     */
    private $SUT;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var Watchlist
     */
    private $watchlist;

    /**
     * @var integer
     */
    private $resultCacheLifetime;

    /**
     * Details on how to access services in tests:
     * https://symfony.com/blog/new-in-symfony-4-1-simpler-service-testing
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $this->SUT = self::$container->get(StudyBuilder::class);
        $this->em = self::$container->get('doctrine')->getManager();
        $this->resultCacheLifetime = self::$container->getParameter('result_cache_lifetime');

        $this->watchlist = $this->em->getRepository(Watchlist::class)->findOneBy(['name' => self::WATCHLIST_NAME]);

        $date = new \DateTime('2020-05-15');
        $this->SUT->createStudy($date, self::STUDY_NAME);
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

        fwrite(STDOUT, 'Calculating Market Breadth. This will take a while...');
        $this->SUT->calculateMarketBreadth($this->watchlist);
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

        fwrite(STDOUT, 'Calculating Market Breadth. This will take a while...');
        $this->SUT->calculateMarketBreadth($this->watchlist);
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
            if ($this->dayBO($instrument, $date2)) {
                $insDBO[] = $instrument;
            }
            if ($this->dayBD($instrument, $date2)) {
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
            if ($this->weekBO($instrument, $date2)) {
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
            if ($this->monthBO($instrument, $date2)) {
                $insMoBO[] = $instrument;
            }
            if ($this->monthBD($instrument, $date2)) {
                $insMoBD[] = $instrument;
            }
        }

        $getBOBDDaily = new Criteria(Criteria::expr()->eq('attribute', 'bobd-daily'));
        $bobdDaily = $this->SUT->getStudy()->getArrayAttributes()->matching($getBOBDDaily)->first()->getValue();
        $getBOBDWeekly = new Criteria(Criteria::expr()->eq('attribute', 'bobd-weekly'));
        $bobdWeekly = $this->SUT->getStudy()->getArrayAttributes()->matching($getBOBDWeekly)->first()->getValue();
        $getBOBDMonthly = new Criteria(Criteria::expr()->eq('attribute', 'bobd-monthly'));
        $bobdMonthly = $this->SUT->getStudy()->getArrayAttributes()->matching($getBOBDMonthly)->first()->getValue();


        $this->assertCount(count($insDBO), $bobdDaily['survey'][StudyBuilder::D_BO]);
        $this->assertArraySubset($insDBO, $bobdDaily['survey'][StudyBuilder::D_BO]);
        $this->assertCount(count($insDBD), $bobdDaily['survey'][StudyBuilder::D_BD]);
        $this->assertArraySubset($insDBD, $bobdDaily['survey'][StudyBuilder::D_BD]);
        $this->assertCount(count($posOnD), $bobdDaily['survey'][StudyBuilder::POS_ON_D]);
        $this->assertArraySubset($posOnD, $bobdDaily['survey'][StudyBuilder::POS_ON_D]);
        $this->assertCount(count($negOnD), $bobdDaily['survey'][StudyBuilder::NEG_ON_D]);
        $this->assertArraySubset($negOnD, $bobdDaily['survey'][StudyBuilder::NEG_ON_D]);

        $this->assertCount(count($insWkBO), $bobdWeekly['survey'][StudyBuilder::WK_BO]);
        $this->assertArraySubset($insWkBO, $bobdWeekly['survey'][StudyBuilder::WK_BO]);
        $this->assertCount(count($negOnWk), $bobdWeekly['survey'][StudyBuilder::NEG_ON_WK]);
        $this->assertArraySubset($negOnWk, $bobdWeekly['survey'][StudyBuilder::NEG_ON_WK]);

        $this->assertCount(count($insMoBO), $bobdMonthly['survey'][StudyBuilder::MO_BO]);
        $this->assertArraySubset($insMoBO, $bobdMonthly['survey'][StudyBuilder::MO_BO]);
        $this->assertCount(count($insMoBD), $bobdMonthly['survey'][StudyBuilder::MO_BD]);
        $this->assertArraySubset($insMoBD, $bobdMonthly['survey'][StudyBuilder::MO_BD]);
    }

    public function testMarketScoreDelta()
    {
        $getScore = new Criteria(Criteria::expr()->eq('attribute', 'market-score'));

        $date1 = new \DateTime('2020-05-14');
        $this->SUT->getStudy()->setDate($date1);
        fwrite(STDOUT, 'Calculating Market Breadth. This will take a while...');
        $this->SUT->calculateMarketBreadth($this->watchlist);
        fwrite(STDOUT, 'complete.'.PHP_EOL);
        $pastStudy = $this->SUT->getStudy();
        $pastScore = $pastStudy->getFloatAttributes()->matching($getScore)->first()->getValue();

        $date2 = new \DateTime('2020-05-15');
        $name = 'test_market_study';
        $this->SUT->createStudy($date2, $name);

        fwrite(STDOUT, 'Calculating Market Breadth. This will take a while...');
        $this->SUT->calculateMarketBreadth($this->watchlist);
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

        fwrite(STDOUT, 'Calculating Market Breadth. This will take a while...');
        $this->SUT->calculateMarketBreadth($this->watchlist);
        fwrite(STDOUT, 'complete.'.PHP_EOL);

        $this->SUT->buildActionableSymbolsWatchlist();

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

    public function testASBOBD_14May2020_ASBOBDTable()
    {
        $date1 = new \DateTime('2020-05-14');
        $this->SUT->getStudy()->setDate($date1);

        fwrite(STDOUT, 'Calculating Market Breadth. This will take a while...');
        $this->SUT->calculateMarketBreadth($this->watchlist);
        fwrite(STDOUT, 'complete.'.PHP_EOL);

        $this->SUT->buildActionableSymbolsWatchlist();
        $pastStudy = $this->SUT->getStudy();

        $getASWatchilst = new Criteria(Criteria::expr()->eq('name', 'AS'));
        $ASWatchlist = $pastStudy->getWatchlists()->matching($getASWatchilst)->first();
        $ASInstruments = $ASWatchlist->getInstruments()->toArray();

        $date2 = new \DateTime('2020-05-15');

        $dayBO = [];
        $dayBD = [];
        $posOnD = [];
        $negOnD = [];
        foreach ($ASInstruments as $instrument) {
            if ($this->dayBO($instrument, $date2)) {
                $dayBO[] = $instrument;
            }
            if ($this->dayBD($instrument, $date2)) {
                $dayBD[] = $instrument;
            }
            if ($this->posOnD($instrument, $date2)) {
                $posOnD[] = $instrument;
            }
            if ($this->negOnD($instrument, $date2)) {
                $negOnD[] = $instrument;
            }
        }

        $weekBO = [];
        $weekBD = [];
        $posOnWk = [];
        $negOnWk = [];
        foreach ($ASInstruments as $instrument) {
            if ($this->weekBO($instrument, $date2)) {
                $weekBO[] = $instrument;
            }
            if ($this->weekBD($instrument, $date2)) {
                $weekBD[] = $instrument;
            }
            if ($this->posOnWk($instrument, $date2)) {
                $posOnWk[] = $instrument;
            }
            if ($this->negOnWk($instrument, $date2)) {
                $negOnWk[] = $instrument;
            }
        }

        $monthBO = [];
        $monthBD = [];
        $posOnMo = [];
        $negOnMo = [];
        foreach ($ASInstruments as $instrument) {
            if ($this->monthBO($instrument, $date2)) {
                $monthBO[] = $instrument;
            }
            if ($this->monthBD($instrument, $date2)) {
                $monthBD[] = $instrument;
            }
            if ($this->posOnMo($instrument, $date2)) {
                $posOnMo[] = $instrument;
            }
            if ($this->negOnMo($instrument, $date2)) {
                $negOnMo[] = $instrument;
            }
        }

        $name = 'test_market_study';
        $this->SUT->createStudy($date2, $name);
        $this->SUT->figureASBOBD($pastStudy, $date2);

        $getASBOBD = new Criteria(Criteria::expr()->eq('attribute', 'as-bobd'));
        $ASBOBD = $this->SUT->getStudy()->getArrayAttributes()->matching($getASBOBD)->first()->getValue();

        // Check daily quartets
        $this->assertCount(count($dayBO), $ASBOBD['survey'][StudyBuilder::D_BO]);
        foreach ($dayBO as $instrument) {
            $this->assertContains($instrument, $ASBOBD['survey'][StudyBuilder::D_BO]);
        }
        $this->assertCount(count($dayBD), $ASBOBD['survey'][StudyBuilder::D_BD]);
        foreach ($dayBD as $instrument) {
            $this->assertContains($instrument, $ASBOBD['survey'][StudyBuilder::D_BD]);
        }
        $this->assertCount(count($posOnD), $ASBOBD['survey'][StudyBuilder::POS_ON_D]);
        foreach ($posOnD as $instrument) {
            $this->assertContains($instrument, $ASBOBD['survey'][StudyBuilder::POS_ON_D]);
        }
        $this->assertCount(count($negOnD), $ASBOBD['survey'][StudyBuilder::NEG_ON_D]);
        foreach ($negOnD as $instrument) {
            $this->assertContains($instrument, $ASBOBD['survey'][StudyBuilder::NEG_ON_D]);
        }

        // Check weekly quartets
        $this->assertCount(count($weekBO), $ASBOBD['survey'][StudyBuilder::WK_BO]);
        foreach ($weekBO as $instrument) {
            $this->assertContains($instrument, $ASBOBD['survey'][StudyBuilder::WK_BO]);
        }
        $this->assertCount(count($weekBD), $ASBOBD['survey'][StudyBuilder::WK_BD]);
        foreach ($weekBD as $instrument) {
            $this->assertContains($instrument, $ASBOBD['survey'][StudyBuilder::WK_BD]);
        }
        $this->assertCount(count($posOnWk), $ASBOBD['survey'][StudyBuilder::POS_ON_WK]);
        foreach ($posOnWk as $instrument) {
            $this->assertContains($instrument, $ASBOBD['survey'][StudyBuilder::POS_ON_WK]);
        }
        $this->assertCount(count($negOnWk), $ASBOBD['survey'][StudyBuilder::NEG_ON_WK]);
        foreach ($negOnWk as $instrument) {
            $this->assertContains($instrument, $ASBOBD['survey'][StudyBuilder::NEG_ON_WK]);
        }

        // Check monthly quartets
        $this->assertCount(count($monthBO), $ASBOBD['survey'][StudyBuilder::MO_BO]);
        foreach ($monthBO as $instrument) {
            $this->assertContains($instrument, $ASBOBD['survey'][StudyBuilder::MO_BO]);
        }
        $this->assertCount(count($monthBD), $ASBOBD['survey'][StudyBuilder::MO_BD]);
        foreach ($monthBD as $instrument) {
            $this->assertContains($instrument, $ASBOBD['survey'][StudyBuilder::MO_BD]);
        }
        $this->assertCount(count($posOnMo), $ASBOBD['survey'][StudyBuilder::POS_ON_MO]);
        foreach ($posOnMo as $instrument) {
            $this->assertContains($instrument, $ASBOBD['survey'][StudyBuilder::POS_ON_MO]);
        }
        $this->assertCount(count($negOnMo), $ASBOBD['survey'][StudyBuilder::NEG_ON_MO]);
        foreach ($negOnMo as $instrument) {
            $this->assertContains($instrument, $ASBOBD['survey'][StudyBuilder::NEG_ON_MO]);
        }
    }

    public function testBuildMarketScoreTableForRollingPeriod_start15May2020_MarketScoreRollingAttr()
    {
        $periodDays = 20;
        $pastStudy = $this->em->getRepository(Study::class)->findOneBy(['date' => new \DateTime('2020-05-14')]);
        $this->SUT->calculateMarketBreadth($this->watchlist);
        $this->SUT->calculateScoreDelta($pastStudy);
//        $this->SUT->buildActionableSymbolsWatchlist();
        $this->SUT->buildMarketScoreTableForRollingPeriod($periodDays);

        // test values in table
        // ...
    }

    public function testBuildMarketScoreTableForMTD()
    {
        $pastStudy = $this->em->getRepository(Study::class)->findOneBy(['date' => new \DateTime('2020-05-14')]);
        $this->SUT->calculateMarketBreadth($this->watchlist);
        $this->SUT->calculateScoreDelta($pastStudy);
        $this->SUT->buildMarketScoreTableForMTD();

        // test values in table
        // ...
    }

    public function testBuildSectorTable()
    {

    }
}