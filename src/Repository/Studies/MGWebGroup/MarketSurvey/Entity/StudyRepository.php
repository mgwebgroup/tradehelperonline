<?php

namespace App\Repository\Studies\MGWebGroup\MarketSurvey\Entity;

use App\Studies\MGWebGroup\MarketSurvey\Entity\Study;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use App\Service\Scanner\OHLCV\Scanner;

/**
 * @method Study|null find($id, $lockMode = null, $lockVersion = null)
 * @method Study|null findOneBy(array $criteria, array $orderBy = null)
 * @method Study[]    findAll()
 * @method Study[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StudyRepository extends ServiceEntityRepository
{
    /**
     * @var App\Service\Scanner\OHLCV\Scanner;
     */
    private $scanner;


    public function __construct(
      RegistryInterface $registry,
      Scanner $scanner
    )
    {
        $this->scanner = $scanner;
        parent::__construct($registry, Study::class);
    }

    /**
     * Watchlist with associated formulas must already be imported. This watchlist must contain all formulas
     * necessary for calculation of market score
     * @param \DateTime $date
     * @param \App\Entity\Watchlist $watchlist
     * @param array $metric Score values assigned to each type of expression
     * @return array [array $marketSurvey, float $score]. $marketSurvey has arrays if Instruments indexed by formula
     */
    public function getMarketBreadth($date, $watchlist, $metric)
    {
        // perform scan of y_universe for each formula
        $survey = $this->getSurvey($date, $watchlist);

        // score results
        $score = $survey;
        array_walk($score, function(&$v, $k, $metric) { $v = count($v) * $metric[$k]; }, $metric );
        return [$survey, array_sum($score)];
    }

    /**
     * Takes watchlist and a date and runs scans for each formula and symbol in watchlist returning summary of
     * instruments matching each formula and criterion.
     * @param $date
     * @param $watchlist
     * @return array $survey
     */
    public function getSurvey($date, $watchlist)
    {
        $expressions = $watchlist->getExpressions();
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
     * Takes inside bar break out/break down watchlist formulated on previous T and figures percentages of BO's/BD's
     * @param $date
     * @param $watchlist of inside bars for the previous T
     * @return array['IM' => ['count', [by each formula]], 'IW' => ...]
     */
    public function figureInsideBarBOBD($date, $watchlist)
    {

    }

    public function buildMarketScoreTableForRollingPer($date, $daysBack, $score)
    {

    }

    public function buildMarketScoreTableForMTD($date, $score)
    {

    }

    /**
     * Will select top 10 symbols from each actionable signals column
     * @param array $marketSurvey
     */
    public function buildActionSymbolsWatchilst($marketSurvey)
    {

    }

    public function buildSectorTable() {}
}
