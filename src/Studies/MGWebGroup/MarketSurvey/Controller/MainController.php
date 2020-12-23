<?php
/**
 * This file is part of the Trade Helper Online package.
 *
 * (c) 2019-2020  Alex Kay <alex110504@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Studies\MGWebGroup\MarketSurvey\Controller;

use App\Entity\OHLCV\History;
use App\Service\Charting\ChartBuilderInterface;
use App\Service\ExpressionHandler\OHLCV\Calculator;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Study\Study;
use App\Entity\Instrument;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints\Date as DateConstraint;


class MainController extends AbstractController
{
    public function index(Request $request, ValidatorInterface $validator, Calculator $calculator)
    {
        $dateString = $request->attributes->get('date');

        $errors = [];
        if ($dateString) {
            $violations = $validator->validate($dateString, new DateConstraint());
            if ($violations->count() > 0) {
                // formulate errors array here
                // ...
            } else {
                $date = new \DateTime($dateString);
            }
        } else {
            $date = new \DateTime();
        }

        $studyRepository = $this->getDoctrine()->getRepository(Study::class);
        $study = $studyRepository->findOneBy(['date' => $date]);

        if (empty($study)) {
            // error for now. Later to run a scan for y_universe
            $errors[] = sprintf('Could not find study for date %s', $date->format('M d, Y'));
        }

        if (empty($errors)) {
            $getMarketBreadth = new Criteria(Criteria::expr()->eq('attribute', 'market-breadth'));
            $survey = $study->getArrayAttributes()->matching($getMarketBreadth)->first()->getValue();

            $getScore = new Criteria(Criteria::expr()->eq('attribute', 'market-score'));
            $scoreAttr = $study->getFloatAttributes()->matching($getScore)->first();
            $score = $scoreAttr ? $scoreAttr->getValue() : 'N/A';

            $getScoreDelta = new Criteria(Criteria::expr()->eq('attribute', 'score-delta'));
            $scoreDeltaAttr = $study->getFloatAttributes()->matching($getScoreDelta)->first();
            $scoreDelta = $scoreDeltaAttr ? $scoreDeltaAttr->getValue() : 'N/A';

            $getInsDBOBD = new Criteria(Criteria::expr()->eq('attribute', 'bobd-daily'));
            $getInsWkBOBD = new Criteria(Criteria::expr()->eq('attribute', 'bobd-weekly'));
            $getInsMoBOBD = new Criteria(Criteria::expr()->eq('attribute', 'bobd-monthly'));
            $insDBOBDAttr = $study->getArrayAttributes()->matching($getInsDBOBD)->first();
            $insDBOBD = $insDBOBDAttr ? $insDBOBDAttr->getValue() : ['count' => 0];
            $insWkBOBDAttr = $study->getArrayAttributes()->matching($getInsWkBOBD)->first();
            $insWkBOBD = $insWkBOBDAttr ? $insWkBOBDAttr->getValue() : ['count' => 0];
            $insMoBOBDAttr = $study->getArrayAttributes()->matching($getInsMoBOBD)->first();
            $insMoBOBD = $insMoBOBDAttr ? $insMoBOBDAttr->getValue() : ['count' => 0];

            $getASBOBD = new Criteria(Criteria::expr()->eq('attribute', 'as-bobd'));
            $ASBOBDAttr = $study->getArrayAttributes()->matching($getASBOBD)->first();
            $ASBOBD = $ASBOBDAttr ? $ASBOBDAttr->getValue() : ['count' => 0];

            $getScoreTableRolling = new Criteria(Criteria::expr()->eq('attribute', 'score-table-rolling'));
            $scoreTableRollingAttr = $study->getArrayAttributes()->matching($getScoreTableRolling)->first();
            if ($scoreTableRollingAttr) {
                $scoreTableRolling = $scoreTableRollingAttr->getValue();
                $dateColumn = array_column($scoreTableRolling['table'], 'date');
                array_multisort($dateColumn, SORT_ASC, $scoreTableRolling['table']);
            } else {
                $scoreTableRolling = [];
            }

            $getScoreTableMTD = new Criteria(Criteria::expr()->eq('attribute', 'score-table-mtd'));
            $scoreTableMTDAttr = $study->getArrayAttributes()->matching($getScoreTableMTD)->first();
            if ($scoreTableMTDAttr) {
                $scoreTableMTD = $scoreTableMTDAttr->getValue();
                $dateColumn = array_column($scoreTableMTD['table'], 'date');
                array_multisort($dateColumn, SORT_ASC, $scoreTableMTD['table']);
            } else {
                $scoreTableMTD = [];
            }

            $getSectorTable  = new Criteria(Criteria::expr()->eq('attribute', 'sector-table'));
            $sectorTableAttr = $study->getArrayAttributes()->matching($getSectorTable)->first();
            if ($sectorTableAttr) {
                $sectorTable = $sectorTableAttr->getValue();
            } else {
                $sectorTable = [];
            }

            $getASList = new Criteria(Criteria::expr()->eq('name', 'AS'));
            $ASWatchlist = $study->getWatchlists()->matching($getASList)->first();
            if ($ASWatchlist) {
                $ASWatchlist->update($calculator, $date);
            }


            return $this->render('@MarketSurvey/main.html.twig', [
              'date' => $date,
              'survey' => $survey,
              'score' => $score,
              'score_delta' => $scoreDelta,
              'insd_bobd' => $insDBOBD,
              'inswk_bobd' => $insWkBOBD,
              'insmo_bobd' => $insMoBOBD,
              'as_bobd' => $ASBOBD,
              'score_table_rolling' => $scoreTableRolling,
              'score_table_mtd' => $scoreTableMTD,
              'sector_table' => $sectorTable,
              'as_watchlist' => $ASWatchlist,
              ]
            );

        } else {
            return $this->render('@MarketSurvey/main.html.twig', ['date' => $date, 'errors' => $errors]);
        }
    }

    public function chartWindow(Request $request, ValidatorInterface $validator, Filesystem $filesystem,
      ChartBuilderInterface $chartBuilder)
    {
        $errors = [];
        $dateString = $request->attributes->get('date');
        $violations = $validator->validate($dateString, new DateConstraint());
        if ($violations->count() > 0) {
            // formulate errors array here
            // ...
        } else {
            $date = new \DateTime($dateString);
        }
        $symbol = $request->attributes->get('symbol');
        $instrument = $this->getDoctrine()->getRepository(Instrument::class)->findOneBy(['symbol' => $symbol]);
        if (empty($instrument)) {
            $errors[] = sprintf('Could not find instrument for symbol `%s`', $symbol);
        }
        $chartPath = trim($this->getParameter('chart-path'), '/');
        $chartPathAndName = sprintf('%s/%s_%s.png', $chartPath, $instrument->getSymbol(), $date->format('Ymd'));
        if (!$filesystem->exists(realpath('../'.$chartPathAndName))) {
            $interval = History::getOHLCVInterval(History::INTERVAL_DAILY);
            $chartBuilder->buildMedium($instrument, $date, $interval, $chartPathAndName);
        }
    }
}