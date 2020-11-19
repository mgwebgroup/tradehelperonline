<?php
namespace App\Studies\MGWebGroup\MarketSurvey;

use App\Entity\Expression;
use App\Entity\Instrument;
use App\Entity\Study\Study;
use App\Repository\StudyArrayAttributeRepository;
use App\Repository\StudyFloatAttributeRepository;
use App\Repository\WatchlistRepository;
use App\Service\ExpressionHandler\OHLCV\Calculator;
use Symfony\Bridge\Doctrine\RegistryInterface;
use App\Service\Scanner\ScannerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\Collections\Criteria;


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

    /**
     * Will override existing study if exists
     * @param $date
     * @param $name
     * @return Study|App\Entity\Study\Study
     */
    public function createStudy($date, $name)
    {
        $this->study = new Study();
        $this->study->setDate($date);
        $this->study->setName($name);

        return $this;
    }

    public function getStudy()
    {
        return $this->study;
    }

    /**
     * Creates market-breadth array = [
     *   'Ins D & Up' => App\Entity\Instrument[]
     *   'Ins Wk & Up' => App\Entity\Instrument[]
     *   ...
     * ]
     * and saves it as 'market-breadth' array attribute in $this->study. Sequence of the elements is determined by
     * parameters in config/packages/mgweb.yaml.
     *
     * Calculates market score according to the metric stored in mgweb.yaml parameters, and saves it as 'market-score'
     * float attribute in $this->study.
     *
     * Creates Inside Bar Daily, Weekly, Monthly watchlists. Also creates bullish and bearish actionable signals
     * watchlists, which are later used in Inside Bar Breakouts/Breakdowns analysis as well as to build
     * Actionable Symbols lists. These watchlists are added to $this->study into its $watchlists property.
     *
     * @param \App\Entity\Watchlist $watchlist must have expressions for daily, weekly and monthly breakouts:
     *   Ins D BO:Ins D BD:Pos on D:Neg on D:Ins Wk BO:Ins Wk BD:Pos on Wk:Neg on Wk:Ins Mo BO:Ins Mo BD:Pos on Mo:Neg on Mo:V
     * @return StudyBuilder
     */
    public function calculateMarketBreadth($watchlist)
    {
        $metric = $this->container->getParameter('market-score');
        $expressions = [];
        foreach ($metric as $exprName => $score) {
            $expression = $this->em->getRepository(Expression::class)->findOneBy(['name' => $exprName]);
            $expressions[] = $expression;
        }

        $survey = $this->doScan($this->study->getDate(), $watchlist, $expressions);
        StudyArrayAttributeRepository::createArrayAttr($this->study, 'market-breadth', $survey);

        $score = $this->calculateScore($survey, $metric);
        StudyFloatAttributeRepository::createFloatAttr($this->study, 'market-score', $score);

        $insideBars = [self::INSIDE_BAR_DAY, self::INSIDE_BAR_WK, self::INSIDE_BAR_MO];
        foreach ($insideBars as $insideBar) {
            if (!empty($survey[$insideBar])) {
                $newWatchlist[$insideBar] = WatchlistRepository::createWatchlist($insideBar, null, $watchlist->getExpressions(), $survey[$insideBar]);
                $this->study->addWatchlist($newWatchlist[$insideBar]);
            }
        }

        $bullish = [self::INS_D_AND_UP, self::D_HAMMER, self::D_HAMMER_AND_UP, self::D_BULLISH_ENG];
        foreach ($bullish as $signal) {
            if (!empty($survey[$signal])) {
                $newWatchlist[$signal] = WatchlistRepository::createWatchlist($signal, null, $watchlist->getExpressions(), $survey[$signal]);
                $this->study->addWatchlist($newWatchlist[$signal]);
            }
        }

        $bearish = [self::INS_D_AND_DWN, self::D_SHTNG_STAR, self::D_SHTNG_STAR_AND_DWN, self::D_BEARISH_ENG];
        foreach ($bearish as $signal) {
            if (!empty($survey[$signal])) {
                $newWatchlist[$signal] = WatchlistRepository::createWatchlist($signal, null, $watchlist->getExpressions(), $survey[$signal]);
                $this->study->addWatchlist($newWatchlist[$signal]);
            }
        }

        return $this;
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
     * Retrieves $study for the supplied date. Scans its Inside Bar watchlists for Breakouts/Breakdowns (formula
     * quartets) for the date in $this->study. Saves results as array attributes 'bobd-daily', 'bobd-weekly',
     * 'bobd-monthly' in $this->study.
     * A formula quartet example for daily time frame is:
     *   'Ins D BO', 'Ins D BD', 'Pos on D', 'Neg on D'
     *  For weekly:
     *   'Ins Wk BO', 'Ins Wk BD', 'Pos on Wk', 'Neg on Wk'
     *  For monthly:
     *   'Ins Mo BO', 'Ins Mo BD', 'Pos on Mo', 'Neg on Mo'
     *
     * @param App\Entity\Study\Study $study past study which has Inside Bar watchlists.
     * @param \DateTime $effectiveDate date for which to run expressions on Inside Bar watchlists
     * @return StudyBuilder
     */
    public function figureInsideBarBOBD($study, $effectiveDate)
    {
        $bobdTable = [];

        if ($study->getWatchlists() instanceof Doctrine\ORM\PersistentCollection ) {
            $study->getWatchlists()->initialize();
        }
        $comparison = Criteria::expr()->in('name', [self::INSIDE_BAR_DAY, self::INSIDE_BAR_WK, self::INSIDE_BAR_MO]);
        $insideBarWatchlistsCriterion = new Criteria($comparison);
        $insideBarWatchlists = $study->getWatchlists()->matching($insideBarWatchlistsCriterion);
        foreach ($insideBarWatchlists as $insideBarWatchlist) {
            $exprName = $insideBarWatchlist->getName();
            $expressions = [];
            switch ($exprName) {
                case self::INSIDE_BAR_DAY:
                    $exprList = [self::INS_D_BO, self::INS_D_BD, self::POS_ON_D, self::NEG_ON_D];
                    $attribute = 'bobd-daily';
                    break;
                case self::INSIDE_BAR_WK:
                    $exprList = [self::INS_WK_BO, self::INS_WK_BD, self::POS_ON_WK, self::NEG_ON_WK];
                    $attribute = 'bobd-weekly';
                    break;
                case self::INSIDE_BAR_MO:
                    $exprList = [self::INS_MO_BO, self::INS_MO_BD, self::POS_ON_MO, self::NEG_ON_MO];
                    $attribute = 'bobd-monthly';
                    break;
                default:
                    $exprList = [];
                    $attribute = null;
            }
            foreach ($exprList as $name) {
                $expression = $this->em->getRepository(Expression::class)->findOneBy(['name' => $name]);
                $expressions[] = $expression;
            }
            $bobdTable['survey'] = $this->doScan($effectiveDate, $insideBarWatchlist, $expressions);
            $bobdTable['count'] = $insideBarWatchlist->getInstruments()->count();

            StudyArrayAttributeRepository::createArrayAttr($this->study, $attribute, $bobdTable);
        }


        return $this;
    }

    /**
     * Takes specific watchlists already attached to the study and selects top 10 by price and in some cases volume
     * to formulate the Actionable Symbols watchlist.
     * @return StudyBuilder
     */
    public function buildActionSymbolsWatchlist()
    {
        $watchlistsOfInterest = [
          self::INSIDE_BAR_DAY,
          self::INS_D_AND_UP, self::D_HAMMER, self::D_HAMMER_AND_UP, self::D_BULLISH_ENG,
          self::INS_D_AND_DWN, self::D_SHTNG_STAR, self::D_SHTNG_STAR_AND_DWN, self::D_BEARISH_ENG
          ];
        $comparison = Criteria::expr()->in('name', $watchlistsOfInterest);
        $watchlistsOfInterestCriterion = new Criteria($comparison);
        $actionableInstrumentsArray = [];

        foreach ($this->study->getWatchlists()->matching($watchlistsOfInterestCriterion) as $watchlist) {
            switch ($watchlist->getName()) {
                case self::INSIDE_BAR_DAY:
                case self::D_BULLISH_ENG:
                case self::D_BEARISH_ENG:
                    $watchlist->update($this->calculator, $this->study->getDate())->sortValuesBy(...['V', SORT_DESC]);
                break;
                case self::INS_D_AND_UP:
                case self::D_HAMMER:
                case self::D_HAMMER_AND_UP:
                    $watchlist->update($this->calculator, $this->study->getDate())->sortValuesBy(...['Pos on D', SORT_DESC, 'V', SORT_DESC]);
                break;
                case self::INS_D_AND_DWN:
                    case self::D_SHTNG_STAR:
                case self::D_SHTNG_STAR_AND_DWN:
                    $watchlist->update($this->calculator, $this->study->getDate())->sortValuesBy(...['Neg on D', SORT_ASC, 'V', SORT_DESC]);
                    break;
                default:
            }
            $top10 = array_slice($watchlist->getCalculatedFormulas(), 1, 10);
            foreach ($top10 as $symbol) {
                $actionableInstrumentsArray[] = $this->em->getRepository(Instrument::class)->findOneBy(['symbol' =>
                  $symbol]);
            }
        }
        $actionableSymbolsWatchlist = WatchlistRepository::createWatchlist('AS', null, null, $actionableInstrumentsArray);
        $this->study->addWatchlist($actionableSymbolsWatchlist);

        return $this;
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