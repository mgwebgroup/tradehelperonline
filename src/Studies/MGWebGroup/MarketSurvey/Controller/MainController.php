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

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Studies\MGWebGroup\MarketSurvey\Entity\Study;

class MainController extends AbstractController
{
    public function index()
    {
        $today = new \DateTime();

        $studyRepository = $this->getDoctrine()->getRepository(Study::class);
        $study = $studyRepository->findBy(['timestamp' => $today], ['timestamp' => 'desc']);

        if (empty($study)) {
            // run a scan for y_universe
        } else {
            // pick the latest
        }

        return $this->render('@MarketSurvey/main.html.twig', []);
    }
}