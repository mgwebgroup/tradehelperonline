<?php
namespace App\Studies\MGWebGroup\MarketSurvey;

use App\Entity\Expression;
use App\Entity\Watchlist;
use App\Entity\Study\Study;
use App\Repository\WatchlistRepository;
use App\Service\ExpressionHandler\OHLCV\Calculator;
use Symfony\Bridge\Doctrine\RegistryInterface;
use App\Service\Scanner\ScannerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Implements all calculations necessary for the Market Survey. Based on procedures of October 2018.
 * The Market Survey study has 6 areas:
 *   - Market Breadth table (is a basis for Inside Bar watchlists, Market Score and Actionable Symbols list)
 *   - Inside Bar Breakout/Breakdown table
 *   - Actionable Symbols list
 *   - Market Score statistic
 *   - Sectors table
 *   - Y-Universe scoring table
 *
 */
class StudyBuilder
{
    const INSIDE_BAR_DAY = 'Ins D';
    const INSIDE_BAR_WK = 'Ins Wk';
    const INSIDE_BAR_MO = 'Ins Mo';

    const INS_D_AND_UP = 'Ins D & Up';
    const D_HAMMER = 'D Hammer';
    const D_HAMMER_AND_UP = 'D Hammer & Up';
    const D_BULLISH_ENG = 'D Bullish Eng';

    const INS_D_AND_DWN = 'Ins D & Dwn';
    const D_SHTNG_STAR = 'D Shtng Star';
    const D_SHTNG_STAR_AND_DWN = 'D Shtng Star & Down';
    const D_BEARISH_ENG = 'D Bearish Eng';

    const INS_D_BO = 'Ins D BO';
    const INS_D_BD = 'Ins D BD';
    const POS_ON_D = 'Pos on D';
    const NEG_ON_D = 'Neg on D';
    const INS_WK_BO = 'Ins Wk BO';
    const INS_WK_BD = 'Ins Wk BD';
    const POS_ON_WK = 'Pos on Wk';
    const NEG_ON_WK = 'Neg on Wk';
    const INS_MO_BO = 'Ins Mo BO';
    const INS_MO_BD = 'Ins Mo BD';
    const POS_ON_MO = 'Pos on Mo';
    const NEG_ON_MO = 'Neg on Mo';


    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var App\Service\Scanner\OHLCV\Scanner;
     */
    private $scanner;

    /**
     * @var Symfony\Component\DependencyInjection\Container
     */
    private $container;

    /**
     * @var App\Service\ExpressionHandler\OHLCV\Calculator
     */
    private $calculator;

    /**
     * @var App\Entity\Study\Study
     */
    private $study;


    public function __construct(
        RegistryInterface $registry,
        ScannerInterface $scanner,
        ContainerInterface $container,
        Calculator $calculator
    )
    {
        $this->em = $registry->getManager();
        $this->scanner = $scanner;
        $this->container = $container;
        $this->calculator = $calculator;
    }

    public function getStudy($date, $name)
    {
        $study = $this->em->getRepository(Study::class)->findOneBy(['date' => $date, 'name' => $name]);

        if (!$this->study) {
            $this->study = new Study();
        }

        return $this->study;
    }

    /**
     * Calculates Market Score and returns Market Breadth table with instruments sorted by Price and Volume, suitable
     * for composition of Actionable Symbols Lists as well as inside bar watchlists.
     * @param \DateTime $date
     * @param \App\Entity\Watchlist $watchlist must have expressions for daily, weekly and monthly breakouts:
     *   Ins D BO:Ins D BD:Pos on D:Neg on D:Ins Wk BO:Ins Wk BD:Pos on Wk:Neg on Wk:Ins Mo BO:Ins Mo BD:Pos on Mo:Neg
     * on Mo:V
     * @return array $marketBreadth = [float $score, array $payload, array $survey]
     *   $payload is array of watchlists that are necessary for saving or using in other parts of the study.
     *   $payload = [
     *      'Ins D' => App\Entity\Watchlist, $calculated_formulas property has symbols sorted by 'Volume'
     *      'Ins Wk' => App\Entity\Watchlist,
     *      'Ins Mo' => App\Entity\Watchlist,
     *      'Ins D & Up' => App\Entity\Watchlist, $calculated_formulas property has symbols sorted by 'Pos on D' and 'Volume' formulas
     *      'D Hammer' => App\Entity\Watchlist, same as above
     *      'D Hammer & Up' => App\Entity\Watchlist, same as above
     *      'D Bullish Eng' => App\Entity\Watchlist, $calculated_formulas property has symbols sorted by 'Volume'
     *      ...
     *      similar for the bearish (see constants of this class).
     *      ]
     * If no instruments met the criteria in the formulas contained in $metric, corresponding keys in payload will
     * not be set.
     *   $survey uses all scan formulas from the $metric and presents all instruments that matched each formula.
     *   $survey = [
     *      'Ins D & Up' => App\Entity\Instrument[],
     *      'Ins Wk & Up' => App\Entity\Instrument[],
     *      ...
     *      the rest of the keys are coming from the $metric parameter
     *    ]
     */
    public function calculateMarketBreadth($date, $watchlist)
    {
        $metric = $this->container->getParameter('market-score');
        $expressions = [];
        foreach ($metric as $exprName => $score) {
            $expression = $this->em->getRepository(Expression::class)->findOneBy(['name' => $exprName]);
            $expressions[] = $expression;
        }

        $survey = $this->doScan($date, $watchlist, $expressions);

        $score = $this->calculateScore($survey, $metric);

        // further process the survey to sort instruments according to volume and expressions
        $payload = [];
        $insideBars = [self::INSIDE_BAR_DAY, self::INSIDE_BAR_WK, self::INSIDE_BAR_MO];
        foreach ($insideBars as $insideBar) {
            if (!empty($survey[$insideBar])) {
                $newWatchlist[$insideBar] = WatchlistRepository::createWatchlist($insideBar, null, $watchlist->getExpressions(), $survey[$insideBar]);
                if (self::INSIDE_BAR_DAY == $insideBar) {
                    $newWatchlist[$insideBar]->update($this->calculator, $date)->sortValuesBy(...['V', SORT_DESC]);
                }
                $payload[$insideBar] = $newWatchlist[$insideBar];
            }
        }

        $bullish = [self::INS_D_AND_UP, self::D_HAMMER, self::D_HAMMER_AND_UP, self::D_BULLISH_ENG];
        foreach ($bullish as $signal) {
            if (!empty($survey[$signal])) {
                $newWatchlist[$signal] = WatchlistRepository::createWatchlist($signal, null, $watchlist->getExpressions(), $survey[$signal]);
                $newWatchlist[$signal]->update($this->calculator, $date);
                if (self::D_BULLISH_ENG == $signal) {
                    $newWatchlist[$signal]->sortValuesBy(...['V', SORT_DESC]);
                } else {
                    $newWatchlist[$signal]->sortValuesBy(...['Pos on D', SORT_DESC, 'V', SORT_DESC]);
                }
                $payload[$signal] = $newWatchlist[$signal];
            }
        }

        $bearish = [self::INS_D_AND_DWN, self::D_SHTNG_STAR, self::D_SHTNG_STAR_AND_DWN, self::D_BEARISH_ENG];
        foreach ($bearish as $signal) {
            if (!empty($survey[$signal])) {
                $newWatchlist[$signal] = WatchlistRepository::createWatchlist($signal, null, $watchlist->getExpressions(), $survey[$signal]);
                $newWatchlist[$signal]->update($this->calculator, $date);
                if (self::D_BEARISH_ENG == $signal) {
                    $newWatchlist[$signal]->sortValuesBy(...['V', SORT_DESC]);
                } else {
                    $newWatchlist[$signal]->sortValuesBy(...['Neg on D', SORT_ASC, 'V', SORT_DESC]);
                }
                $payload[$signal] = $newWatchlist[$signal];
            }
        }

        return [$score, $payload, $survey];
    }

    /**
     * @param $survey = [ string exprName => App\Entity\Instrument[]]
     * @param $metric = [string exprName => float value]
     * @return float|int
     */
    private function calculateScore($survey, $metric)
    {
        $score = $survey;
        array_walk($score, function(&$v, $k, $metric) { $v = count($v) * $metric[$k]; }, $metric);
        return array_sum($score);
    }

    /**
     * Takes a Watchlist entity and runs scans for each expression in $expressions returning summary of
     * instruments matching each.
     * @param \DateTime $date Date for which to run the scan
     * @param App\Entity\Watchlist $watchlist
     * @param array $expressions App\Entity\Expression[]
     * @return array $survey = [ string exprName => App\Entity\Instrument[], ...]
     */
    private function doScan($date, $watchlist, $expressions)
    {
        $survey = [];
        foreach ($expressions as $expression) {
            $survey[$expression->getName()] = $this->scanner->scan(
              $watchlist->getInstruments(),
              $expression->getFormula(),
              $expression->getCriteria(),
              $expression->getTimeinterval(),
              $date
            );
        }

        return $survey;
    }

    /**
     * Saves each watchlist under its name + date
     * @param \DateTime $date for which the payload is for
     * @param array $payload Payload generated by calculateMarketBreadth() method
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveInsideBarWatchlists($date, $payload)
    {
        $watchlistsToSave = [self::INSIDE_BAR_DAY, self::INSIDE_BAR_WK, self::INSIDE_BAR_MO];
        foreach ($watchlistsToSave as $exprName) {
            $watchlistName = sprintf('%s_%s', $exprName, $date->format('ymd'));
            if (isset($payload[$exprName])) {
                $payload[$exprName]->setName($watchlistName);
                $this->em->persist($payload[$exprName]);
            }
        }
        $this->em->flush();
    }

    /**
     * This function will work only if Inside Bar watchlists were saved for T-1. It retrieves Inside Bar watchlists
     * for T-1, returns counts for Daily, Weekly and Monthly formula quartets.
     * A formula quartet example for daily timeframe is:
     *   'Ins D BO', 'Ins D BD', 'Pos on D', 'Neg on D'
     * @param \DateTime $date for T
     * @param App\Service\Exchange\Equities\Exchange $exchange either NYSE or NASDAQ
     * @return array $bobdTable [
     *   'Ins D' => ['count' => $count, 'survey' => $survey]
     *     $survey = [
     *      'Ins D BO' => App\Entity\Instrument[], 'Ins D BD' => App\Entity\Instrument[],
     *      'Pos on D' => App\Entity\Instrument[], 'Neg on D' => App\Entity\Instrument[]
     *     ]
     *   'Ins Wk' => similar to above
     *   'Ins Mo' => similar to above
     * ]
     */
    public function figureInsideBarBOBD($date, $exchange)
    {
        $watchlistsToRetrieve = [self::INSIDE_BAR_DAY, self::INSIDE_BAR_WK, self::INSIDE_BAR_MO];
        $prevT = $exchange->calcPreviousTradingDay($date);

        $bobdTable = [];
        foreach ($watchlistsToRetrieve as $exprName) {
            $watchlistName = sprintf('%s_%s', $exprName, $prevT->format('ymd'));
            $prevTInsBarWatchlist = $this->em->getRepository(Watchlist::class)->findOneBy(['name' => $watchlistName]);
            if ($prevTInsBarWatchlist) {
                $bobdTable[$exprName]['count'] = $prevTInsBarWatchlist->getInstruments()->count();
                $expressions = [];
                switch ($exprName) {
                    case self::INSIDE_BAR_DAY:
                        $exprList = [self::INS_D_BO, self::INS_D_BD, self::POS_ON_D, self::NEG_ON_D];
                        break;
                    case self::INSIDE_BAR_WK:
                        $exprList = [self::INS_WK_BO, self::INS_WK_BD, self::POS_ON_WK, self::NEG_ON_WK];
                        break;
                    case self::INSIDE_BAR_MO:
                        $exprList = [self::INS_MO_BO, self::INS_MO_BD, self::POS_ON_MO, self::NEG_ON_MO];
                        break;
                    default:
                        $exprList = [];
                }
                foreach ($exprList as $exprQuartetName) {
                    $expression = $this->em->getRepository(Expression::class)->findOneBy(['name' => $exprQuartetName]);
                    $expressions[] = $expression;
                }
                $bobdTable[$exprName]['survey'] = $this->doScan($date, $prevTInsBarWatchlist, $expressions);
            }
        }

        return $bobdTable;
    }

    /**
     * Will select top 10 symbols from each actionable signals column
     * @param array $payload
     */
    public function buildActionSymbolsWatchlist($payload)
    {

    }

    public function buildMarketScoreTableForRollingPeriod($date, $daysBack, $score)
    {

    }

    public function buildMarketScoreTableForMTD($date, $score)
    {

    }

    /**
     * Creates Inside Bar daily, weekly, monthly, watchlists for the next T
     * @param array $marketBreadth @see getMarketBreadth()
     * @param App\Entity\Expression[] $xpressions
     * @param \DateTime $date Date for which to create watchlists. Will be part of their name
     * @return integer $counter number of watchlists saved
     */
//    public function feedNextDaysInsideBarWatchlists($marketBreadth, $expressions, $date)
//    {
//        $counter = 0;
//        $timeframes = ['d' => 'Ins D', 'wk' => 'Ins Wk', 'mo' => 'Ins Mo'];
//        foreach ($timeframes as $timeframe => $exprName) {
//            $watchlistName = sprintf('ins_%s_bobd_%s', $timeframe, $date->format('ymd'));
//            $newWatchlist = WatchlistRepository::createWatchlist($watchlistName, null, $expressions,
//                                                                 $marketBreadth[0][$exprName]);
//            $this->em->persist($newWatchlist);
//            $this->em->flush();
//            unset($newWatchlist);
//            $counter++;
//        }
//        return $counter;
//    }

    public function buildSectorTable() {}
}