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

use Doctrine\Common\Collections\Criteria;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Study\Study;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints\Date as DateConstraint;

class MainController extends AbstractController
{
    public function index(Request $request, ValidatorInterface $validator)
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
            $score = $study->getFloatAttributes()->matching($getScore)->first()->getValue();
            $getScoreDelta = new Criteria(Criteria::expr()->eq('attribute', 'score-delta'));
            $scoreDelta = $study->getFloatAttributes()->matching($getScoreDelta)->first()->getValue();

            $getInsDBOBD = new Criteria(Criteria::expr()->eq('attribute', 'bobd-daily'));
            $getInsWkBOBD = new Criteria(Criteria::expr()->eq('attribute', 'bobd-weekly'));
            $getInsMoBOBD = new Criteria(Criteria::expr()->eq('attribute', 'bobd-monthly'));
            $insDBOBD = $study->getArrayAttributes()->matching($getInsDBOBD)->first()->getValue();
            $insWkBOBD = $study->getArrayAttributes()->matching($getInsWkBOBD)->first()->getValue();
            $insMoBOBD = $study->getArrayAttributes()->matching($getInsMoBOBD)->first()->getValue();

            $getASBOBD = new Criteria(Criteria::expr()->eq('attribute', 'as-bobd'));
            $ASBOBD = $study->getArrayAttributes()->matching($getASBOBD)->first()->getValue();

            $getScoreTableRolling = new Criteria(Criteria::expr()->eq('attribute', 'score-table-rolling'));
            $scoreTableRolling = $study->getArrayAttributes()->matching($getScoreTableRolling)->first()->getValue();
            $dateColumn = array_column($scoreTableRolling['table'], 'date');
            array_multisort($dateColumn, SORT_ASC, $scoreTableRolling['table']);

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
              'errors' => $errors ]
            );

        } else {
            return $this->render('@MarketSurvey/main.html.twig', ['date' => $date, 'errors' => $errors]);
        }
    }
}