<?php
namespace App\Studies\MGWebGroup\MarketSurvey;

use App\Entity\Expression;
use App\Entity\Instrument;
use App\Entity\OHLCV\History;
use App\Entity\Study\Study;
use App\Repository\StudyArrayAttributeRepository;
use App\Repository\StudyFloatAttributeRepository;
use App\Repository\WatchlistRepository;
use App\Service\Exchange\Equities\TradingCalendar;
use App\Service\Exchange\MonthlyIterator;
use App\Service\Exchange\WeeklyIterator;
use App\Service\ExpressionHandler\OHLCV\Calculator;
use DateTime;
use Symfony\Bridge\Doctrine\RegistryInterface;
use App\Service\Scanner\ScannerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\Collections\Criteria;
use App\Studies\MGWebGroup\MarketSurvey\Exception\StudyException;
use MathPHP\Statistics\Descriptive;
use Doctrine\ORM\PersistentCollection;

/**
 * Implements all calculations necessary for the Market Survey. Based on procedures of October 2018.
 * The Market Survey study has 6 areas:
 *   - Market Breadth table (is a basis for Inside Bar watchlists, Market Score and Actionable Symbols list)
 *   - Inside Bar Breakout/Breakdown table
 *   - Actionable Symbols list
 *   - Actionable Symbols Breakout/Breakdown table
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

    const D_BO = 'D BO';
    const D_BD = 'D BD';
    const POS_ON_D = 'Pos on D';
    const NEG_ON_D = 'Neg on D';
    const WK_BO = 'Wk BO';
    const WK_BD = 'Wk BD';
    const POS_ON_WK = 'Pos on Wk';
    const NEG_ON_WK = 'Neg on Wk';
    const MO_BO = 'Mo BO';
    const MO_BD = 'Mo BD';
    const POS_ON_MO = 'Pos on Mo';
    const NEG_ON_MO = 'Neg on Mo';

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var \App\Service\Scanner\OHLCV\Scanner;
     */
    private $scanner;

    /**
     * @var \Symfony\Component\DependencyInjection\Container
     */
    private $container;

    /**
     * @var \App\Service\ExpressionHandler\OHLCV\Calculator
     */
    private $calculator;

    /**
     * @var \App\Entity\Study\Study
     */
    private $study;

    /**
     * @var \App\Service\Exchange\Equities\TradingCalendar
     */
    private $tradeDayIterator;

    /**
     * @var \App\Service\Exchange\WeeklyIterator
     */
    private $weeklyIterator;

    /**
     * @var \App\Service\Exchange\MonthlyIterator
     */
    private $monthlyIterator;


    public function __construct(
        RegistryInterface $registry,
        ScannerInterface $scanner,
        ContainerInterface $container,
        Calculator $calculator,
        TradingCalendar $tradingCalendar,
        WeeklyIterator $weeklyIterator,
        MonthlyIterator $monthlyIterator
    )
    {
        $this->em = $registry->getManager();
        $this->scanner = $scanner;
        $this->container = $container;
        $this->calculator = $calculator;
        $this->tradeDayIterator = $tradingCalendar;
        $this->weeklyIterator = $weeklyIterator;
        $this->monthlyIterator = $monthlyIterator;
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

        return $this->study;
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
     * and saves it as 'market-breadth' array attribute in $this->study.
     *
     * Calculates market score according to the metric stored in mgweb.yaml parameters, and saves it as 'market-score'
     * float attribute in $this->study.
     *
     * Creates Inside Bar Daily, Weekly, Monthly watchlists. Also creates bullish and bearish watchlists, which are
     * later used in Inside Bar Breakouts/Breakdowns analysis as well as to build Actionable Symbols lists in
     * other functions of the StudyBuilder. These watchlists are added to $this->study into its $watchlists property.
     *
     * @param \App\Entity\Watchlist $watchlist must have expressions associated with it for daily breakouts,
     * breakdowns, and volume:
     *   D BO:D BD:V
     * These expressions are used in figuring out of Actionable Symbols list
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
     * Takes 'market-score' attribute in the $pastStudy, figures delta from the current score in current study and
     * saves this delta as 'score-delta' float attribute
     * @param App\Entity\Study\Study $pastStudy past study which has its Market Score stored in float attribute
     * 'market-score'
     * @return StudyBuilder
     */
    public function calculateScoreDelta($pastStudy = null)
    {
        $getScore = new Criteria(Criteria::expr()->eq('attribute', 'market-score'));
        if (!$pastStudy) {
            $pastScore = 0;
        } else {
            $pastScore = $pastStudy->getFloatAttributes()->matching($getScore)->first()->getValue();
        }
        $score = $this->study->getFloatAttributes()->matching($getScore)->first()->getValue();

        $scoreDelta = $score - $pastScore;
        StudyFloatAttributeRepository::createFloatAttr($this->study, 'score-delta', $scoreDelta);

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
     * @param DateTime $date Date for which to run the scan
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
     * Given the study, scans its Inside Bar watchlists for Breakouts/Breakdowns (formula
     * quartets) for the $effectiveDate. Saves results as array attributes 'bobd-daily', 'bobd-weekly',
     * 'bobd-monthly' in $this->study.
     *
     * @param App\Entity\Study\Study $study past study which has Inside Bar watchlists.
     * @param DateTime $effectiveDate date for which to run expressions on Inside Bar watchlists
     * @return StudyBuilder
     */
    public function figureInsideBarBOBD($study, $effectiveDate)
    {
        if ($study->getWatchlists() instanceof PersistentCollection ) {
            $study->getWatchlists()->initialize();
        }

        $comparison = Criteria::expr()->in('name', [self::INSIDE_BAR_DAY, self::INSIDE_BAR_WK, self::INSIDE_BAR_MO]);
        $insideBarWatchlistsCriterion = new Criteria($comparison);
        $insideBarWatchlists = $study->getWatchlists()->matching($insideBarWatchlistsCriterion);
        foreach ($insideBarWatchlists as $insideBarWatchlist) {
            $exprName = $insideBarWatchlist->getName();
            switch ($exprName) {
                case self::INSIDE_BAR_DAY:
                    $exprList = [self::D_BO, self::D_BD, self::POS_ON_D, self::NEG_ON_D];
                    $attribute = 'bobd-daily';
                    break;
                case self::INSIDE_BAR_WK:
                    $exprList = [self::WK_BO, self::WK_BD, self::POS_ON_WK, self::NEG_ON_WK];
                    $attribute = 'bobd-weekly';
                    break;
                case self::INSIDE_BAR_MO:
                    $exprList = [self::MO_BO, self::MO_BD, self::POS_ON_MO, self::NEG_ON_MO];
                    $attribute = 'bobd-monthly';
                    break;
                default:
                    $exprList = [];
                    $attribute = null;
            }

            $bobdTable = $this->makeSurvey($effectiveDate, $insideBarWatchlist, $exprList);

            StudyArrayAttributeRepository::createArrayAttr($this->study, $attribute, $bobdTable);
        }

        return $this;
    }

    /**
     * Given the study, scans its Actionable Symbols (AS) watchlist for Breakouts/Breakdowns (formula
     * quartets) for the $effectiveDate. Saves results as array attribute 'as-bobd' in $this->study.
     * @param App\Entity\Study\Study $study past study which has Actionable Symbols watchlist.
     * @param DateTime $effectiveDate date for which to run expressions on AS watchlist
     * @return StudyBuilder
     */
    public function figureASBOBD($study, $effectiveDate)
    {
        if ($study->getWatchlists() instanceof PersistentCollection ) {
            $study->getWatchlists()->initialize();
        }

        $comparison = Criteria::expr()->eq('name', 'AS');
        $getASWatchlist = new Criteria($comparison);
        $ASWatchlist = $study->getWatchlists()->matching($getASWatchlist)->first();

        $exprList = [
            self::D_BO, self::D_BD, self::POS_ON_D, self::NEG_ON_D,
            self::WK_BO, self::WK_BD, self::POS_ON_WK, self::NEG_ON_WK,
            self::MO_BO, self::MO_BD, self::POS_ON_MO, self::NEG_ON_MO
        ];
        $attribute = 'as-bobd';

        $bobdTable = $this->makeSurvey($effectiveDate, $ASWatchlist, $exprList);

        StudyArrayAttributeRepository::createArrayAttr($this->study, $attribute, $bobdTable);

        return $this;
    }

    /**
     * Performs watchlist scan using list of expression names as strings.
     * @param DateTime $date
     * @param App\Entity\Watchlist $watchlist
     * @param array $exprList String[]
     * @return array $bobdTable = [
     *      'survey' => [
     *          <exprName1> => App\Entity\Instrument[],
     *          <exprName2> => App\Entity/Instrument[], ...
     *      ],
     *      'count' => integer
     *   ]
     */
    private function makeSurvey($date, $watchlist, $exprList)
    {
        $bobdTable = [];
        $expressions = [];
        foreach ($exprList as $name) {
            $expression = $this->em->getRepository(Expression::class)->findOneBy(['name' => $name]);
            $expressions[] = $expression;
        }
        $bobdTable['survey'] = $this->doScan($date, $watchlist, $expressions);
        $bobdTable['count'] = $watchlist->getInstruments()->count();

        return $bobdTable;
    }

    /**
     * Takes specific watchlists already attached to the study and selects top 10 instruments from some lists by price
     * and from other lists by price and volume to formulate the Actionable Symbols (AS) watchlist. This AS watchlist is
     * attached to the study.
     * @return StudyBuilder
     */
    public function buildActionableSymbolsWatchlist()
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
            $top10 = array_slice($watchlist->getCalculatedFormulas(), 0, 10);
            foreach ($top10 as $symbol => $values) {
                $actionableInstrumentsArray[] = $this->em->getRepository(Instrument::class)->findOneBy(['symbol' =>
                  $symbol]);
            }
        }
        $actionableSymbolsWatchlist = WatchlistRepository::createWatchlist('AS', null, [], $actionableInstrumentsArray);
        $this->study->addWatchlist($actionableSymbolsWatchlist);

        return $this;
    }

    /**
     * Score table contains current score with historical market scores, score deltas, prices for SPY and how many
     * standard deviations from average the score and the score deltas are for each day. These numbers are used to
     * mark significant levels for SPY, called the Levels Map.
     * In order to calculate the table correctly, current study must already have attributes 'market-score' and 'score-delta'
     * calculated. Also, studies for the $daysBack must already be saved in database.
     * Function attaches new array attribute 'score-table-rolling' to the $study.
     * @param integer $daysBack
     * @return StudyBuilder
     * @throws StudyException
     * @throws \App\Exception\PriceHistoryException
     * @throws \MathPHP\Exception\BadDataException
     * @throws \MathPHP\Exception\OutOfBoundsException
     */
    public function buildMarketScoreTableForRollingPeriod($daysBack)
    {
        /** @var App\Entity\Instrument | null $SPY */
        $SPY = $this->em->getRepository(Instrument::class)->findOneBy(['symbol' => 'SPY']);
        if (!$SPY) {
            throw new StudyException('Could not find instrument for `SPY`');
        }

        /** @var \DateInterval $interval */
        $interval = History::getOHLCVInterval(History::INTERVAL_DAILY);

        $scoreTableRolling = ['table' => [], 'summary' => []];

        $date = clone $this->study->getDate();

        $studyParams = $this->getScoreTableParams($this->study, $SPY, $interval);
//        $studyParams = ['score' => 238.75, 'delta' => -51.75, 'P' => 286.28];
        $studyParams['date'] = $date;
        $scoreTableRolling['table'][] = $studyParams;

        $this->tradeDayIterator->getInnerIterator()->setStartDate($date)->setDirection(-1);
        $this->tradeDayIterator->getInnerIterator()->rewind();
        while ($daysBack > 0) {
            $this->tradeDayIterator->next();
            $date = $this->tradeDayIterator->current();

            $study = $this->em->getRepository(Study::class)->findOneBy(['name' => $this->study->getName(), 'date' => $date]);
            if (!$study) {
                throw new StudyException(sprintf('Could not find study for date %s', $date->format('Y-m-d H:i:s')));
            }

            $studyParams = $this->getScoreTableParams($study, $SPY, $interval);
            $studyParams['date'] = clone $date;
            $scoreTableRolling['table'][] = $studyParams;

            $daysBack--;
        }

        if ( count($scoreTableRolling['table']) > 0) {
            $scoreTableRolling = $this->addScoreTableSummary($scoreTableRolling);

            StudyArrayAttributeRepository::createArrayAttr($this->study, 'score-table-rolling', $scoreTableRolling);
        }

        return $this;
    }

    private function addScoreTableSummary($scoreTable)
    {
        $count = count($scoreTable['table']);
        $scoreColumn = array_column($scoreTable['table'], 'score');
        $scoreTable['summary']['score-avg'] = array_sum($scoreColumn) / $count;
        $scoreTable['summary']['score-max'] = max($scoreColumn);
        $scoreTable['summary']['score-min'] = min($scoreColumn);
        $deltaColumn = array_column($scoreTable['table'], 'delta');
        $scoreTable['summary']['delta-avg'] = array_sum($deltaColumn) / $count;
        $scoreTable['summary']['delta-max'] = max($deltaColumn);
        $scoreTable['summary']['delta-min'] = min($deltaColumn);
        $PColumn = array_column($scoreTable['table'], 'P');
        $scoreTable['summary']['P-avg'] = array_sum($PColumn) / $count;

        $scoreTable['summary']['score-std_div'] = Descriptive::sd($scoreColumn);
        $scoreTable['summary']['delta-std_div'] = Descriptive::sd($deltaColumn);

        $scoreTable['summary']['score-days_pos'] = array_reduce($scoreColumn, function($carry, $item) {
            if ($item > 0) { $carry++;} return $carry; }, 0);
        $scoreTable['summary']['score-days_neg'] = array_reduce($scoreColumn, function($carry, $item) {
            if ($item < 0) { $carry++;} return $carry; }, 0);

        $scoreAvg = $scoreTable['summary']['score-avg'];
        $scoreStdDev = $scoreTable['summary']['score-std_div'];
        $deltaAvg = $scoreTable['summary']['delta-avg'];
        $deltaStdDev = $scoreTable['summary']['delta-std_div'];
        $updatedTable =  array_map(
          function($record) use ($scoreAvg, $scoreStdDev, $deltaAvg, $deltaStdDev) {
              $record['score-std_div_qty'] = ($record['score'] - $scoreAvg) / $scoreStdDev;
              $record['delta-std_div_qty'] = ($record['delta'] - $deltaAvg) / $deltaStdDev;
              return $record;
          }, $scoreTable['table']);

        $scoreTable['table'] = $updatedTable;

        return $scoreTable;
    }

    /**
     * @param Study $study
     * @param Instrument $instrument
     * @param \DateInterval $interval
     * @return array
     * @throws StudyException
     */
    private function getScoreTableParams($study, $instrument, $interval)
    {
        $date = $study->getDate();

        $getScore = new Criteria(Criteria::expr()->eq('attribute', 'market-score'));
        /** @var App\Entity\Study\FloatAttribute | false  $scoreFloatAttr */
        $scoreFloatAttr = $study->getFloatAttributes()->matching($getScore)->first();
        if ($scoreFloatAttr) {
            $score = $scoreFloatAttr->getValue();
        } else {
            throw new StudyException(sprintf('Study with id=%d is missing its current score.', $study->getId()));
        }

        $getScoreDelta = new Criteria(Criteria::expr()->eq('attribute', 'score-delta'));
        $scoreDeltaFloatAttr = $study->getFloatAttributes()->matching($getScoreDelta)->first();
        if ($scoreDeltaFloatAttr) {
            $scoreDelta = $scoreDeltaFloatAttr->getValue();
        } else {
            throw new StudyException('Study in the StudyBuilder is missing current score delta');
        }

        $h = $this->em->getRepository(History::class)->findOneBy(['instrument' => $instrument, 'timestamp' => $date, 'timeinterval' => $interval]);
        if (!$h) {
            throw new StudyException(sprintf('Price history for instrument `%s` and date `%s` could not be found',
                                             $instrument->getSymbol(), $date->format('Y-m-d H:i:s')));
        }

        return ['score' => $score, 'delta' => $scoreDelta, 'P' => $h->getClose()];
    }

    public function buildMarketScoreTableForMTD()
    {
        /** @var App\Entity\Instrument | null $SPY */
        $SPY = $this->em->getRepository(Instrument::class)->findOneBy(['symbol' => 'SPY']);
        if (!$SPY) {
            throw new StudyException('Could not find instrument for `SPY`');
        }

        /** @var \DateInterval $interval */
        $interval = History::getOHLCVInterval(History::INTERVAL_DAILY);

        $scoreTableMTD = ['table' => [], 'summary' => []];

        $date = clone $this->study->getDate();

        $studyParams = $this->getScoreTableParams($this->study, $SPY, $interval);
//        $studyParams = ['score' => 238.75, 'delta' => -51.75, 'P' => 286.28];
        $studyParams['date'] = $date;
        $scoreTableMTD['table'][] = $studyParams;

        $this->tradeDayIterator->getInnerIterator()->setStartDate($date)->setDirection(-1);
        $this->tradeDayIterator->getInnerIterator()->rewind();
        while ($this->tradeDayIterator->current()->format('d') > 1) {
            $this->tradeDayIterator->next();
            $date = $this->tradeDayIterator->current();

            $study = $this->em->getRepository(Study::class)->findOneBy(['name' => $this->study->getName(), 'date' => $date]);
            if (!$study) {
                throw new StudyException(sprintf('Could not find study for date %s', $date->format('Y-m-d H:i:s')));
            }

            $studyParams = $this->getScoreTableParams($study, $SPY, $interval);
            $studyParams['date'] = clone $date;
            $scoreTableMTD['table'][] = $studyParams;
        }

        if ( count($scoreTableMTD['table']) > 0) {
            $scoreTableMTD = $this->addScoreTableSummary($scoreTableMTD);

            StudyArrayAttributeRepository::createArrayAttr($this->study, 'score-table-mtd', $scoreTableMTD);
        }

        return $this;
    }

    /**
     * Takes sector watchlist and uses prices for sectors to figure various parameters.
     * The sector watchlist must have P:delta P expressions associated with it. File sectors.csv already comes with
     * the sectors and formulas in it. Please refer to the study README.md file on how how to import it.
     * Saves new array attribute for the study titled 'sector-table'.
     * @param $watchlist
     * @param \DateTime $date
     * @throws StudyException
     */
    public function buildSectorTable($watchlist, $date)
    {
        $watchlist->update($this->calculator, $date);

        $watchlist->sortValuesBy('delta P(5) prcnt', SORT_DESC);

        try {
            // create sector table with week delta P, Month delta P, Quarter, etc..
            $sectorTable = $watchlist->getCalculatedFormulas();
            $beginningOfWeek = clone $date;
            $this->weeklyIterator->toBeginning($beginningOfWeek);
            $beginningOfMonth = clone $date;
            $this->monthlyIterator->toBeginning($beginningOfMonth);
            $beginningOfQuarter = clone $beginningOfMonth;
            while (($beginningOfQuarter->format('n')-1)%3 > 0) {
                $beginningOfQuarter->sub(new \DateInterval('P1M'));
            }
            $this->monthlyIterator->toBeginning($beginningOfQuarter);
            $beginningOfYear = clone $beginningOfQuarter;
            $beginningOfYear->setDate($date->format('Y'), 1, 3);

            $this->monthlyIterator->toBeginning($beginningOfYear);
            foreach ($sectorTable as $symbol => $record) {

            }
        } catch (\Exception $e) {
            throw new StudyException($e->getMessage());
        }

        // Figure sector positions

        // Add Summary

    }
}