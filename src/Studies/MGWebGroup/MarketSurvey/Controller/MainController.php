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
            // run a scan for y_universe
        }

        $getMarketBreadth = new Criteria(Criteria::expr()->eq('attribute', 'market-breadth'));
        $survey = $study->getArrayAttributes()->matching($getMarketBreadth)->first()->getValue();

        $getScore = new Criteria(Criteria::expr()->eq('attribute', 'market-score'));
        $score = $study->getFloatAttributes()->matching($getScore)->first()->getValue();

        return $this->render('@MarketSurvey/main.html.twig', ['date' => $date, 'survey' => $survey, 'score' => $score]);
    }
}