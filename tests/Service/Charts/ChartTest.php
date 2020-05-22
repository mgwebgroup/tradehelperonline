<?php
/**
 * This file is part of the Trade Helper Online package.
 *
 * (c) 2019-2020  Alex Kay <alex110504@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Service\Charts;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Entity\OHLCVHistory;
use App\Entity\Instrument;
use App\Service\Charting\OHLCV\ChartFactory;
use App\Service\Charting\OHLCV\Style;

class ChartTest extends KernelTestCase
{
    /**
     * @var App\Service\Charts\Chart
     */
    private $SUT;

    private $em;

    private $instrument;

    /**
     * Details on how to access services in tests:
     * https://symfony.com/blog/new-in-symfony-4-1-simpler-service-testing
     */
    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = self::$container->get('doctrine')->getManager();
        $this->instrument = $this->em->getRepository(Instrument::class)->findOneBySymbol('LIN');

        $catalog = self::$container->get('App\Service\Exchange\Catalog');
        $exchange = $catalog->getExchangeFor($this->instrument);
        $tradingCalendar = $exchange->getTradingCalendar();
        $today = new \DateTime('2020-05-15');
        $tradingCalendar->getInnerIterator()->setStartDate($today)->setDirection(-1);
        $offset = 295;
        $limitIterator = new \LimitIterator($tradingCalendar, $offset, 1);
        $limitIterator->rewind();
        $fromDate = $limitIterator->current();

        $OHLCVHistoryRepository = $this->em->getRepository(OHLCVHistory::class);
        $priceProvider = null;
        $interval = new \DateInterval('P1D');
        $toDate = $today;
        $history = $OHLCVHistoryRepository->retrieveHistory($this->instrument, $interval, $fromDate, $toDate, $priceProvider);

        $style = new Style();

        $this->SUT = ChartFactory::create($style, $history);

    }

    public function testIntro()
    {
        $this->SUT->save_chart();
    }
}