<?php

namespace App\Tests\Command;

use App\Command\ConvertOhlcvCommand;
use App\Entity\Instrument;
use App\Entity\OHLCV\History;
use App\Exception\PriceHistoryException;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\NullOutput;

class ConvertOhlcvCommandTest extends KernelTestCase
{
    /**
     * @var ConvertOhlcvCommand
     */
    private $SUT;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Instrument
     */
    private $instrument;

    /**
     * Details on how to access services in tests:
     * https://symfony.com/blog/new-in-symfony-4-1-simpler-service-testing
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $this->SUT = self::$container->get(ConvertOhlcvCommand::class);
        $this->em = self::$container->get('doctrine')->getManager();
        $this->instrument = $this->em->getRepository(Instrument::class)->findOneBy(['symbol' => 'LIN']);
        // remove prices in all superlative time frames
        $prices = $this->em->getRepository(History::class)->findBy(['instrument' => $this->instrument, 'timeinterval'
        => History::getOHLCVInterval(History::INTERVAL_WEEKLY)]);
        foreach ($prices as $price) {
            $this->em->remove($price);
        }
        $this->em->flush();

        $prices = $this->em->getRepository(History::class)->findBy(['instrument' => $this->instrument, 'timeinterval'
        => History::getOHLCVInterval(History::INTERVAL_MONTHLY)]);
        foreach ($prices as $price) {
            $this->em->remove($price);
        }
        $this->em->flush();

        $prices = $this->em->getRepository(History::class)->findBy(['instrument' => $this->instrument, 'timeinterval'
        => History::getOHLCVInterval(History::INTERVAL_QUARTERLY)]);
        foreach ($prices as $price) {
            $this->em->remove($price);
        }
        $this->em->flush();

        $prices = $this->em->getRepository(History::class)->findBy(['instrument' => $this->instrument, 'timeinterval'
        => History::getOHLCVInterval(History::INTERVAL_YEARLY)]);
        foreach ($prices as $price) {
            $this->em->remove($price);
        }
        $this->em->flush();
    }


    /**
     * Test all weekly prices created
     * @throws PriceHistoryException
     */
    public function testWeekly()
    {
        $input = new ArgvInput(['th:convert-ohlcv', '--symbol=LIN', '--weekly']);
        $output = new NullOutput();

        $this->SUT->run($input, $output);
        $prices = $this->em->getRepository(History::class)->findBy(['instrument' => $this->instrument, 'timeinterval'
        => History::getOHLCVInterval(History::INTERVAL_WEEKLY)], ['timestamp' => 'desc']);

        $this->assertCount(489, $prices);

        $latestCandle = array_shift($prices);

        $this->assertSame('2020-05-11 00:00:00', $latestCandle->getTimestamp()->format('Y-m-d H:i:s'));
        $this->assertEquals(
            184.57,
            $latestCandle->getOpen(),
            'Open price on latest weekly candle does not match',
            0.001
        );
        $this->assertEquals(
            187.38,
            $latestCandle->getHigh(),
            'High price on latest weekly candle does not match',
            0.001
        );
        $this->assertEquals(
            172.76,
            $latestCandle->getLow(),
            'Low price on latest weekly candle does not match',
            0.001
        );
        $this->assertEquals(
            182.59,
            $latestCandle->getClose(),
            'Close price on latest weekly candle does not match',
            0.001
        );
        $this->assertEquals(
            10764800,
            $latestCandle->getVolume(),
            'Volume on latest weekly candle does not match',
            1
        );

        $earliestCandle = array_pop($prices);

        $this->assertSame('2011-01-03 00:00:00', $earliestCandle->getTimestamp()->format('Y-m-d H:i:s'));
        $this->assertEquals(
            95.99,
            $earliestCandle->getOpen(),
            'Open price on latest weekly candle does not match',
            0.001
        );
        $this->assertEquals(
            95.99,
            $earliestCandle->getHigh(),
            'High price on latest weekly candle does not match',
            0.001
        );
        $this->assertEquals(
            92.88,
            $earliestCandle->getLow(),
            'Low price on latest weekly candle does not match',
            0.001
        );
        $this->assertEquals(
            94.29,
            $earliestCandle->getClose(),
            'Close price on latest weekly candle does not match',
            0.001
        );
        $this->assertEquals(
            8228600,
            $earliestCandle->getVolume(),
            'Volume on latest weekly candle does not match',
            1
        );
    }


    /**
     * Test all monthly prices created
     * @throws PriceHistoryException
     */
    public function testMonthly()
    {
        $input = new ArgvInput(['th:convert-ohlcv', '--symbol=LIN', '--monthly']);
        $output = new NullOutput();

        $this->SUT->run($input, $output);
        $prices = $this->em->getRepository(History::class)->findBy(['instrument' => $this->instrument, 'timeinterval'
        => History::getOHLCVInterval(History::INTERVAL_MONTHLY)], ['timestamp' => 'desc']);

        $this->assertCount(113, $prices);

        $latestCandle = array_shift($prices);

        $this->assertSame('2020-05-01 00:00:00', $latestCandle->getTimestamp()->format('Y-m-d H:i:s'));
        $this->assertEquals(
            184.25,
            $latestCandle->getOpen(),
            'Open price on latest weekly candle does not match',
            0.001
        );
        $this->assertEquals(
            188.41,
            $latestCandle->getHigh(),
            'High price on latest weekly candle does not match',
            0.001
        );
        $this->assertEquals(
            172.76,
            $latestCandle->getLow(),
            'Low price on latest weekly candle does not match',
            0.001
        );
        $this->assertEquals(
            182.59,
            $latestCandle->getClose(),
            'Close price on latest weekly candle does not match',
            0.001
        );
        $this->assertEquals(
            20408200,
            $latestCandle->getVolume(),
            'Volume on latest weekly candle does not match',
            1
        );

        $earliestCandle = array_pop($prices);

        $this->assertSame('2011-01-03 00:00:00', $earliestCandle->getTimestamp()->format('Y-m-d H:i:s'));
        $this->assertEquals(
            95.99,
            $earliestCandle->getOpen(),
            'Open price on latest weekly candle does not match',
            0.001
        );
        $this->assertEquals(
            95.99,
            $earliestCandle->getHigh(),
            'High price on latest weekly candle does not match',
            0.001
        );
        $this->assertEquals(
            90.04,
            $earliestCandle->getLow(),
            'Low price on latest weekly candle does not match',
            0.001
        );
        $this->assertEquals(
            93.04,
            $earliestCandle->getClose(),
            'Close price on latest weekly candle does not match',
            0.001
        );
        $this->assertEquals(
            32542600,
            $earliestCandle->getVolume(),
            'Volume on latest weekly candle does not match',
            1
        );
    }


    /**
     * Test all quarterly prices created
     * @throws PriceHistoryException
     */
    public function testQuarterly()
    {
        $input = new ArgvInput(['th:convert-ohlcv', '--symbol=LIN', '--quarterly']);
        $output = new NullOutput();

        $this->SUT->run($input, $output);
        $prices = $this->em->getRepository(History::class)->findBy(['instrument' => $this->instrument, 'timeinterval'
        => History::getOHLCVInterval(History::INTERVAL_QUARTERLY)], ['timestamp' => 'desc']);

        $this->assertCount(38, $prices);

        $latestCandle = array_shift($prices);

        $this->assertSame('2020-04-01 00:00:00', $latestCandle->getTimestamp()->format('Y-m-d H:i:s'));
        $this->assertEquals(
            165.62,
            $latestCandle->getOpen(),
            'Open price on latest weekly candle does not match',
            0.001
        );
        $this->assertEquals(
            190.91,
            $latestCandle->getHigh(),
            'High price on latest weekly candle does not match',
            0.001
        );
        $this->assertEquals(
            159.41,
            $latestCandle->getLow(),
            'Low price on latest weekly candle does not match',
            0.001
        );
        $this->assertEquals(
            182.59,
            $latestCandle->getClose(),
            'Close price on latest weekly candle does not match',
            0.001
        );
        $this->assertEquals(
            67661233,
            $latestCandle->getVolume(),
            'Volume on latest weekly candle does not match',
            1
        );

        $earliestCandle = array_pop($prices);

        $this->assertSame('2011-01-03 00:00:00', $earliestCandle->getTimestamp()->format('Y-m-d H:i:s'));
        $this->assertEquals(
            95.99,
            $earliestCandle->getOpen(),
            'Open price on latest weekly candle does not match',
            0.001
        );
        $this->assertEquals(
            102.19,
            $earliestCandle->getHigh(),
            'High price on latest weekly candle does not match',
            0.001
        );
        $this->assertEquals(
            90.04,
            $earliestCandle->getLow(),
            'Low price on latest weekly candle does not match',
            0.001
        );
        $this->assertEquals(
            101.6,
            $earliestCandle->getClose(),
            'Close price on latest weekly candle does not match',
            0.001
        );
        $this->assertEquals(
            95114400,
            $earliestCandle->getVolume(),
            'Volume on latest weekly candle does not match',
            1
        );
    }


    /**
     * Test all yearly prices created
     * @throws PriceHistoryException
     */
    public function testYearly()
    {
        $input = new ArgvInput(['th:convert-ohlcv', '--symbol=LIN', '--yearly']);
        $output = new NullOutput();

        $this->SUT->run($input, $output);
        $prices = $this->em->getRepository(History::class)->findBy(['instrument' => $this->instrument, 'timeinterval'
        => History::getOHLCVInterval(History::INTERVAL_YEARLY)], ['timestamp' => 'desc']);

        $this->assertCount(10, $prices);

        $latestCandle = array_shift($prices);

        $this->assertSame('2020-01-02 00:00:00', $latestCandle->getTimestamp()->format('Y-m-d H:i:s'));
        $this->assertEquals(
            213.58,
            $latestCandle->getOpen(),
            'Open price on latest weekly candle does not match',
            0.001
        );
        $this->assertEquals(
            227.85,
            $latestCandle->getHigh(),
            'High price on latest weekly candle does not match',
            0.001
        );
        $this->assertEquals(
            146.71,
            $latestCandle->getLow(),
            'Low price on latest weekly candle does not match',
            0.001
        );
        $this->assertEquals(
            182.59,
            $latestCandle->getClose(),
            'Close price on latest weekly candle does not match',
            0.001
        );
        $this->assertEquals(
            237190833,
            $latestCandle->getVolume(),
            'Volume on latest weekly candle does not match',
            1
        );

        $earliestCandle = array_pop($prices);

        $this->assertSame('2011-01-03 00:00:00', $earliestCandle->getTimestamp()->format('Y-m-d H:i:s'));
        $this->assertEquals(
            95.99,
            $earliestCandle->getOpen(),
            'Open price on latest weekly candle does not match',
            0.001
        );
        $this->assertEquals(
            111.74,
            $earliestCandle->getHigh(),
            'High price on latest weekly candle does not match',
            0.001
        );
        $this->assertEquals(
            88.64,
            $earliestCandle->getLow(),
            'Low price on latest weekly candle does not match',
            0.001
        );
        $this->assertEquals(
            106.9,
            $earliestCandle->getClose(),
            'Close price on latest weekly candle does not match',
            0.001
        );
        $this->assertEquals(
            422160100,
            $earliestCandle->getVolume(),
            'Volume on latest weekly candle does not match',
            1
        );
    }
}
