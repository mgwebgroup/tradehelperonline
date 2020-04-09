<?php

namespace App\Tests\Service\PriceHistory\OHLCV;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Entity\OHLCVHistory;
use App\Entity\Instrument;
use App\Entity\OHLCVQuote;
use App\Exception\PriceHistoryException;

class YahooTest extends KernelTestCase
{
    const TEST_SYMBOL = 'TEST';

    /**
     * @var \App\Service\PriceHistory\OHLCV\Yahoo
     */
    private $SUT;

    /**
     * @var \Faker\Generator
     */
    private $faker;

    /**
     * @var App\Service\Exchange\ExchangeInterface
     */
    private $exchange;

    /**
     * @var App\Entity\Instrument
     */
    private $instrument;

    /** @var App\Entity\Instrument[] */
    private $instruments;

    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * Details on how to access services in tests:
     * https://symfony.com/blog/new-in-symfony-4-1-simpler-service-testing
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $this->SUT = self::$container->get(\App\Service\PriceHistory\OHLCV\Yahoo::class);

        $this->faker = \Faker\Factory::create();
        $exchanges = ['NYSE', 'NASDAQ'];
        $exchangeName = $this->faker->randomElement($exchanges);
        $className = $exchangeName;
        $this->exchange = self::$container->get('App\Service\Exchange\\'.$className);
        $this->instruments = $this->exchange->getTradedInstruments($className);
        $this->instrument = $this->faker->randomElement($this->instruments);

        $this->em = self::$container->get('doctrine')->getManager();
    }

    public function testIntro()
    {
//        $reflection = new \ReflectionClass($this->SUT);
//    	fwrite(STDOUT, sprintf('Testing %s\%s', $reflection->getNamespaceName(), $reflection->getName()) . PHP_EOL);
        $this->assertTrue(true);
    }

    /**
     * test downloadHistory:
     * Check if downloads at least 4 historical records for an instrument
     * Test for daily, weekly, monthly
     */
    public function testDownloadHistory10()
    {
        $toDate = new \DateTime();
        // daily
        $fromDate = new \DateTime('1 week ago');
        $options = ['interval' => 'P1D'];
        $history = $this->SUT->downloadHistory($this->instrument, $fromDate, $toDate, $options);
        $this->assertGreaterThanOrEqual(4, count($history));
        foreach ($history as $item) {
            $this->assertInstanceOf(OHLCVHistory::class, $item);
        }
        $firstElement = array_shift($history);
        $lastElement = array_pop($history);
        $this->assertGreaterThan($firstElement->getTimestamp()->format('U'), $lastElement->getTimestamp()->format('U'));

        // weekly
        $fromDate = new \DateTime('5 weeks ago');
        $options = ['interval' => 'P1W'];
        $history = $this->SUT->downloadHistory($this->instrument, $fromDate, $toDate, $options);
        $this->assertGreaterThanOrEqual(4, count($history));
        foreach ($history as $item) {
            $this->assertInstanceOf(OHLCVHistory::class, $item);
        }
        $firstElement = array_shift($history);
        $lastElement = array_pop($history);
        $this->assertGreaterThan($firstElement->getTimestamp()->format('U'), $lastElement->getTimestamp()->format('U'));

        // monthly
        $fromDate = new \DateTime('5 months ago');
        $options = ['interval' => 'P1M'];
        $history = $this->SUT->downloadHistory($this->instrument, $fromDate, $toDate, $options);
        $this->assertGreaterThanOrEqual(4, count($history));
        foreach ($history as $item) {
            $this->assertInstanceOf(OHLCVHistory::class, $item);
        }
        $firstElement = array_shift($history);
        $lastElement = array_pop($history);
        $this->assertGreaterThan($firstElement->getTimestamp()->format('U'), $lastElement->getTimestamp()->format('U'));

        // check for correct exception if wrong interval is specified
        $this->expectException(PriceHistoryException::class);
        $options = ['interval' => 'P1Y'];
        $history = $this->SUT->downloadHistory($this->instrument, $fromDate, $toDate, $options);
    }

    /**
     * test downloadHistory:
     * Today is a trading day Monday, 2019-05-20, $toDate is set for today,
     * Check if downloads history with the last date as last T from today
     */
    public function testDownloadHistory20()
    {
        $_SERVER['TODAY'] = '2019-05-20'; // Monday, May 20, 2019 is a T
        $toDate = new \DateTime($_SERVER['TODAY']);
        $fromDate = clone $toDate;
        $fromDate->sub(new \DateInterval('P1W'));
        $options = ['interval' => 'P1D'];

        $history = $this->SUT->downloadHistory($this->instrument, $fromDate, $toDate, $options);

        $latestItem = array_pop($history);  // pop element off the end (last element) of array

        $this->assertSame('2019-05-17', $latestItem->getTimestamp()->format('Y-m-d'));
    }

    /**
     * test downloadHistory:
     * today is not a trading day, it is Sunday, May 19, 2019, $toDate is set for today,
     * check if downloads history with the last date as last T from today
     */
    public function testDownloadHistory30()
    {
        $_SERVER['TODAY'] = '2019-05-19'; // Sunday, May 19, 2019
        $toDate = new \DateTime($_SERVER['TODAY']);
        $fromDate = clone $toDate;
        $fromDate->sub(new \DateInterval('P1W'));
        $options = ['interval' => 'P1D'];

        $history = $this->SUT->downloadHistory($this->instrument, $fromDate, $toDate, $options);

        $latestItem = array_pop($history);  // pop element off the end (last element) of array
        // var_dump($latestItem); exit();
        $this->assertSame('2019-05-17', $latestItem->getTimestamp()->format('Y-m-d'));
    }

    /**
     * test downloadHistory:
     * $toDate is set for some day in the past, and it is a T
     *  check that history downloads excluding $toDate
     * In this way we are asserting that toDate does not mean through Date
     */
    public function testDownloadHistory40()
    {
        $toDate = new \DateTime('2019-05-15'); // Wednesday, May 15, 2019
        $fromDate = clone $toDate;
        $fromDate->sub(new \DateInterval('P1W'));
        $options = ['interval' => 'P1D'];

        $history = $this->SUT->downloadHistory($this->instrument, $fromDate, $toDate, $options);

        $latestItem = array_pop($history);  // pop element off the end (last element) of array
        // var_dump($latestItem); exit();
        $this->assertSame('2019-05-14', $latestItem->getTimestamp()->format('Y-m-d'));
    }

    /**
     * test downloadHistory:
     * $toDate is set for some date in the past, and it is not a T
     * check that history downloads excluding up to $toDate
     */
    public function testDownloadHistory50()
    {
        // $this->markTestSkipped();
        $toDate = new \DateTime('2019-04-19'); // Friday, April 19. Good Friday
        $fromDate = clone $toDate;
        $fromDate->sub(new \DateInterval('P1W'));
        $options = ['interval' => 'P1D'];

        $history = $this->SUT->downloadHistory($this->instrument, $fromDate, $toDate, $options);

        $latestItem = array_pop($history);  // pop element off the end (last element) of array
        // var_dump($latestItem); exit();
        $this->assertSame('2019-04-18', $latestItem->getTimestamp()->format('Y-m-d'));
    }

    /**
     * test downloadHistory:
     * $toDate is set for future
     * today is a trading day (T)
     * check if downloads history with the last date as last T-1
     */
    public function testDownloadHistory60()
    {
        $_SERVER['TODAY'] = '2019-05-20'; // Monday, May 20, 2019 is a T
        $toDate = new \DateTime('2019-05-20');
        $toDate->add(new \DateInterval('P1W'));
        $fromDate = clone $toDate;
        $fromDate->sub(new \DateInterval('P2W'));
        $options = ['interval' => 'P1D'];

        $history = $this->SUT->downloadHistory($this->instrument, $fromDate, $toDate, $options);

        $latestItem = array_pop($history);  // pop element off the end (last element) of array

        $this->assertSame('2019-05-17', $latestItem->getTimestamp()->format('Y-m-d'));
    }

    /**
     * test downloadHistory:
     * $toDate is set for future
     * today is not a trading day (not T)
     * check if downloads history with the last date as last T from today
     */
    public function testDownloadHistory70()
    {
        $_SERVER['TODAY'] = '2019-05-19'; // Sunday, May 19, 2019
        $toDate = new \DateTime('2019-05-20');
        $toDate->add(new \DateInterval('P1W'));
        $fromDate = clone $toDate;
        $fromDate->sub(new \DateInterval('P2W'));
        $options = ['interval' => 'P1D'];

        $history = $this->SUT->downloadHistory($this->instrument, $fromDate, $toDate, $options);

        $latestItem = array_pop($history);  // pop element off the end (last element) of array

        $this->assertSame('2019-05-17', $latestItem->getTimestamp()->format('Y-m-d'));
    }

    /**
     * test downloadHistory:
     * $fromDate = $toDate
     * will throw PriceHistoryException
     */
    public function testDownloadHistory80()
    {
        $toDate = new \DateTime('2019-05-20');
        $fromDate = new \DateTime('2019-05-20');
        $options = ['interval' => 'P1D'];

        $this->expectException(PriceHistoryException::class);

        $history = $this->SUT->downloadHistory($this->instrument, $fromDate, $toDate, $options);
    }

    /**
     * test downloadHistory:
     * When $fromDate is a Saturday, $toDate is a Sunday Yahoo API will return error
     * My code supposed to return PriceHistoryException
     */
    public function testDownloadHistory90()
    {
        $toDate = new \DateTime('2019-05-19');
        $fromDate = new \DateTime('2019-05-18');
        $options = ['interval' => 'P1D'];

        $this->expectException(PriceHistoryException::class);

        $history = $this->SUT->downloadHistory($this->instrument, $fromDate, $toDate, $options);
    }

    /**
     * test downloadHistory:
     * Check for a long weekend
     * My code supposed to return PriceHistoryException
     */
    public function testDownloadHistory100()
    {
        $fromDate = new \DateTime('2019-05-25');
        $toDate = new \DateTime('2019-05-27');
        $options = ['interval' => 'P1D'];

        $this->expectException(PriceHistoryException::class);

        $history = $this->SUT->downloadHistory($this->instrument, $fromDate, $toDate, $options);
    }

    /**
     * Test addHistory:
     * Simulated downloaded 3 records partially overlap original values
     * Check that only new values are overwritten
     */
    public function testAddHistory110()
    {
        // will commit to db temporarily to perform the test
        // $this->em->getConnection()->beginTransaction();

        // store 5 records for a week
        $startDate1 = new \DateTime('2018-05-14'); // Monday
        $interval = new \DateInterval('P1D');
        list($instrument, $saved) = $this->createMockHistory(clone $startDate1, $numberOfRecords = 5, $interval);

        // simulate downloaded 3 records with different values
        $startDate2 = new \DateTime('2018-05-16'); // Wednesday
        $addedHistory = $this->createSimulatedDownload($instrument, clone $startDate2, $numberOfRecords = 3, $interval);

        // add to history
        $this->SUT->addHistory($instrument, $addedHistory);

        // $this->em->getConnection()->commit();

        // check
        $OHLCVRepository = $this->em->getRepository(OHLCVHistory::class);

        $qb = $OHLCVRepository->createQueryBuilder('o');
        $qb->where('o.instrument = :instrument')
            ->andWhere('o.timeinterval = :interval')
            ->setParameters(['instrument' => $instrument, 'interval' => $interval])
            ->andWhere('o.timestamp >= :fromDate')->setParameter('fromDate', $startDate1)
            // ->andWhere('o.timestamp <= :toDate')->setParameter('toDate', $endDate)
            ->andWhere('o.provider = :provider')->setParameter('provider', $this->SUT::PROVIDER_NAME)
            ->orderBy('o.timestamp', 'ASC');
        $query = $qb->getQuery();
        $result = $query->getResult();

        $this->assertCount(5, $result);

        $this->assertSame($startDate1->format('Y-m-d'), $result[0]->getTimestamp()->format('Y-m-d'));
        $this->assertSame($startDate2->format('Y-m-d'), $result[2]->getTimestamp()->format('Y-m-d'));

        for ($i = 0; $i < 2; $i++) {
            $this->assertEquals($this->computeControlSum($saved[$i]), $this->computeControlSum($result[$i]));
        }

        for ($i = 2; $i <= 4; $i++) {
            $this->assertEquals($this->computeControlSum($addedHistory[$i - 2]), $this->computeControlSum($result[$i]));
        }

        // rollback db storage
        // $this->em->getConnection()->rollBack();
        // exit();
    }

    /**
     * Test addHistory:
     * Simulated downloaded 3 records do not overlap original values
     * Check that all original values are intact
     */
    public function testAddHistory120()
    {
        // store 5 records for a week
        $startDate1 = new \DateTime('2018-05-14'); // Monday
        $interval = new \DateInterval('P1D');
        list($instrument, $saved) = $this->createMockHistory(clone $startDate1, $numberOfRecords = 5, $interval);

        // simulate downloaded 3 records with different values
        $startDate2 = new \DateTime('2018-05-28'); // Monday
        $addedHistory = $this->createSimulatedDownload($instrument, clone $startDate2, $numberOfRecords = 3, $interval);

        // add to history
        $this->SUT->addHistory($instrument, $addedHistory);

        // check
        $OHLCVRepository = $this->em->getRepository(OHLCVHistory::class);

        $qb = $OHLCVRepository->createQueryBuilder('o');
        $qb->where('o.instrument = :instrument')
            ->andWhere('o.timeinterval = :interval')
            ->setParameters(['instrument' => $instrument, 'interval' => $interval])
            ->andWhere('o.timestamp >= :fromDate')->setParameter('fromDate', $startDate1)
            // ->andWhere('o.timestamp <= :toDate')->setParameter('toDate', $endDate)
            ->andWhere('o.provider = :provider')->setParameter('provider', $this->SUT::PROVIDER_NAME)
            ->orderBy('o.timestamp', 'ASC');

        $query = $qb->getQuery();

        $result = $query->getResult();

        $this->assertCount(8, $result);

        $this->assertSame($startDate1->format('Y-m-d'), $result[0]->getTimestamp()->format('Y-m-d'));
        $this->assertSame($startDate2->format('Y-m-d'), $result[5]->getTimestamp()->format('Y-m-d'));

        for ($i = 0; $i < 5; $i++) {
            $this->assertEquals($this->computeControlSum($saved[$i]), $this->computeControlSum($result[$i]));
        }

        for ($i = 5; $i <= 7; $i++) {
            $this->assertEquals($this->computeControlSum($addedHistory[$i - 5]), $this->computeControlSum($result[$i]));
        }
    }

    /**
     * Test retrieveHistory
     */
    public function testRetrieveHistory130()
    {
        // fwrite(STDOUT, $this->instrument->getSymbol());
        // store 5 records for a week
        $startDate = new \DateTime('2018-05-14'); // Monday
        $endDate = clone $startDate; // this one will be changed inside createMockHistory, and when done will have $endDate
        $interval = 'P1D';
        $options = ['interval' => $interval];
        $interval = new \DateInterval($interval);
        list($instrument, $saved) = $this->createMockHistory($endDate, $numberOfRecords = 5, $interval);

        // retrieve history
        $history = $this->SUT->retrieveHistory($instrument, $startDate, $endDate, $options);

        // check
        $OHLCVRepository = $this->em->getRepository(OHLCVHistory::class);

        $qb = $OHLCVRepository->createQueryBuilder('o');
        $qb->where('o.instrument = :instrument')
            ->andWhere('o.timeinterval = :interval')
            ->setParameters(['instrument' => $instrument, 'interval' => $interval])
            ->andWhere('o.timestamp >= :fromDate')->setParameter('fromDate', $startDate)
            ->andWhere('o.timestamp <= :endDate')->setParameter('endDate', $endDate)
            ->andWhere('o.provider = :provider')->setParameter('provider', $this->SUT::PROVIDER_NAME)
            ->orderBy('o.timestamp', 'ASC');

        $query = $qb->getQuery();

        $result = $query->getResult();

        $this->assertCount(5, $result);

        $this->assertSame($startDate->format('Y-m-d'), $result[0]->getTimestamp()->format('Y-m-d'));
        // $this->assertSame($endDate->format('Y-m-d'), $result[4]->getTimestamp()->format('Y-m-d'));

        for ($i = 0; $i <= 4; $i++) {
            $this->assertEquals($this->computeControlSum($result[$i]), $this->computeControlSum($history[$i]));
        }
        // exit();
    }

    /**
     * Test downloadQuote
     */
    public function testDownloadQuote140()
    {
        // market is open:
        // a quote is downloaded
        $_SERVER['TODAY'] = '2019-05-20 09:30:01'; // Monday, May 20, 2019
        $quote = $this->SUT->downloadQuote($this->instrument);

        $this->assertInstanceOf(OHLCVQuote::class, $quote);

        // market is closed:
        // null is returned for quote
        $_SERVER['TODAY'] = '2019-05-19'; // Sunday, May 19, 2019
        $quote = $this->SUT->downloadQuote($this->instrument);

        $this->assertNull($quote);
    }

    /**
     * Test saveQuote
     * Quote is already saved.  Only one quote supposed to remain in storage. Existing quote must be removed, and new one returned.
     */
    public function testSaveQuote150()
    {
        $_SERVER['TODAY'] = '2019-05-20 09:30:01'; // Monday, May 20, 2019
        $date = new \DateTime($_SERVER['TODAY']);
        $interval = new \DateInterval('P1D');
        // $interval = 'P1D';

        $OHLCVQuoteRepository = $this->em->getRepository(OHLCVQuote::class);

        $qb = $OHLCVQuoteRepository->createQueryBuilder('q');
        $qb->delete()->where('q.instrument = :instrument')->setParameter('instrument', $this->instrument);
        $query = $qb->getQuery();
        $query->execute();
        $this->instrument->unsetOHLCVQuote();

        $quote = new OHLCVQuote();
        $quote->setInstrument($this->instrument);
        $quote->setProvider($this->SUT::PROVIDER_NAME);
        $quote->setTimestamp($date);
        $quote->setTimeinterval($interval);
        $quote->setOpen(102);
        $quote->setHigh(202);
        $quote->setLow(302);
        $quote->setClose(402);
        $quote->setVolume(502);

        $this->instrument->setOHLCVQuote($quote);
        $this->em->persist($this->instrument);
        $this->em->flush();

        // $quote = [
        //     'timestamp' => $date,
        //     'open' => 103,
        //     'high' => 203,
        //     'low' => 303,
        //     'close' => 403,
        //     'volume' => 503,
        //     'interval' => $interval
        // ];
        $quote = new OHLCVQuote();
        $quote->setInstrument($this->instrument);
        $quote->setProvider($this->SUT::PROVIDER_NAME);
        $quote->setTimestamp($date);
        $quote->setTimeinterval($interval);
        $quote->setOpen(103);
        $quote->setHigh(203);
        $quote->setLow(303);
        $quote->setClose(403);
        $quote->setVolume(503);

        $this->SUT->saveQuote($this->instrument, $quote);

        $results = $OHLCVQuoteRepository->findBy(['instrument' => $this->instrument]);

        $this->assertCount(1, $results);
        // // $this->assertSame($quote->getTimestamp()->format('Y-m-d'), $results[0]->getTimestamp()->format('Y-m-d'));
        // unset($quote['timestamp'], $quote['interval']);
        // $this->assertEquals(array_sum($quote), $this->computeControlSum2($results[0]));
        $this->assertEquals($this->computeControlSum2($quote), $this->computeControlSum2($results[0]));
        // // $this->assertSame($this->instrument->getOHLCVQuote()->getId(), $results[0]->getId());
    }

    /**
     * Test saveQuote
     * Quote is not already saved.  Only one quote supposed to remain in storage. Existing quote must be removed and new one returned.
     */
    public function testSaveQuote155()
    {
        $_SERVER['TODAY'] = '2019-05-20 09:30:01'; // Monday, May 20, 2019
        $date = new \DateTime($_SERVER['TODAY']);
        $interval = new \DateInterval('P1D');
        // $interval = 'P1D';

        $OHLCVQuoteRepository = $this->em->getRepository(OHLCVQuote::class);

        $qb = $OHLCVQuoteRepository->createQueryBuilder('q');
        $qb->delete()->where('q.instrument = :instrument')->setParameter('instrument', $this->instrument);
        $query = $qb->getQuery();
        $query->execute();
        $this->instrument->unsetOHLCVQuote();

        // $quote = [
        //     'timestamp' => $date,
        //     'open' => 103,
        //     'high' => 203,
        //     'low' => 303,
        //     'close' => 403,
        //     'volume' => 503,
        //     'interval' => $interval
        // ];

        $quote = new OHLCVQuote();
        $quote->setInstrument($this->instrument);
        $quote->setProvider($this->SUT::PROVIDER_NAME);
        $quote->setTimestamp($date);
        $quote->setTimeinterval($interval);
        $quote->setOpen(103);
        $quote->setHigh(203);
        $quote->setLow(303);
        $quote->setClose(403);
        $quote->setVolume(503);

        $this->SUT->saveQuote($this->instrument, $quote);

        $results = $OHLCVQuoteRepository->findBy(['instrument' => $this->instrument]);

        $this->assertCount(1, $results);
        // $this->assertEquals(array_sum($quote), $this->computeControlSum2($results[0]));
        $this->assertEquals($this->computeControlSum2($quote), $this->computeControlSum2($results[0]));
    }

    /**
     * Test addQuoteToHistory
     * Daily interval
     * History is passed as array
     * Market is open
     */
    public function testAddQuoteToHistory160()
    {
        $_SERVER['TODAY'] = '2018-05-18 15:59:00';
        $startDate = new \DateTime('2018-05-14'); // Monday;
        $interval = new \DateInterval('P1D');
        $history = $this->createSimulatedDownload($this->instrument, $startDate, $numberOfRecords = 5, $interval);

        // Quote is the same date as last date in history
        $today = new \DateTime($_SERVER['TODAY']);
        $quote = new OHLCVQuote();
        $quote->setInstrument($this->instrument);
        $quote->setProvider($this->SUT::PROVIDER_NAME);
        $quote->setTimestamp($today);
        $quote->setTimeinterval($interval);
        $quote->setOpen(103);
        $quote->setHigh(203);
        $quote->setLow(303);
        $quote->setClose(403);
        $quote->setVolume(503);

        $newHistory = $this->SUT->addQuoteToHistory($quote, $history);

        $this->assertInternalType('array', $newHistory);
        $this->assertCount(5, $newHistory);
        $element = array_pop($newHistory);
        $this->assertSame($element->getTimestamp()->format('Y-m-d'), $quote->getTimestamp()->format('Y-m-d'));
        $this->assertSame($element->getInstrument()->getSymbol(), $quote->getInstrument()->getSymbol());
        $this->assertEquals($this->computeControlSum($element), $this->computeControlSum2($quote));

        // Quote is next T from last day in history
        $_SERVER['TODAY'] = '2018-05-21 15:59:00';
        $today = new \DateTime($_SERVER['TODAY']);
        $quote->setTimestamp($today);

        $newHistory = $this->SUT->addQuoteToHistory($quote, $history);
        $this->assertInternalType('array', $newHistory);
        $this->assertCount(6, $newHistory);
        $element = array_pop($newHistory);
        $this->assertSame($element->getTimestamp()->format('Y-m-d'), $quote->getTimestamp()->format('Y-m-d'));
        $this->assertEquals($this->computeControlSum($element), $this->computeControlSum2($quote));
        array_map(
            function ($h, $nh) {
                $this->assertEquals($this->computeControlSum($h), $this->computeControlSum($nh));
            },
            $history,
            $newHistory
        );

        // Quote is a gap from history
        $_SERVER['TODAY'] = '2018-05-22 15:59:00';
        $today = new \DateTime($_SERVER['TODAY']);
        $quote->setTimestamp($today);

        $newHistory = $this->SUT->addQuoteToHistory($quote, $history);
        $this->assertFalse($newHistory);

        // Quote is inside history:
        // This should not happen, as history must always have dates in past, and quote always newer than history.
        //corresponding record in history will be overwritten with info from quote, if their dates match.
    }

    /**
     * Test addQuoteToHistory
     * Daily interval
     * History is passed as array
     * Market is closed
     */
    public function testAddQuoteToHistory170()
    {
        $_SERVER['TODAY'] = '2018-05-14 16:00:00';
        $startDate = new \DateTime('2018-05-14'); // Monday;
        $interval = new \DateInterval('P1D');
        $history = $this->createSimulatedDownload($this->instrument, $startDate, $numberOfRecords = 5, $interval);

        // Quote is the same date as last date in history
        $endDate = $startDate->sub($interval); // will be 2018-05-18

        $quote = new OHLCVQuote();
        $quote->setInstrument($this->instrument);
        $quote->setProvider($this->SUT::PROVIDER_NAME);
        $quote->setTimestamp($endDate);
        $quote->setTimeinterval($interval);
        $quote->setOpen(103);
        $quote->setHigh(203);
        $quote->setLow(303);
        $quote->setClose(403);
        $quote->setVolume(503);

        $newHistory = $this->SUT->addQuoteToHistory($quote, $history);

        $this->assertNull($newHistory);

        // Quote is next T from last day in history
        $endDate = new \DateTime('2018-05-21');
        $quote->setTimestamp($endDate);

        $newHistory = $this->SUT->addQuoteToHistory($quote, $history);

        $this->assertNull($newHistory);

        // Quote is a gap from history
        $endDate = new \DateTime('2018-05-22');
        $quote->setTimestamp($endDate);

        $newHistory = $this->SUT->addQuoteToHistory($quote, $history);
        $this->assertNull($newHistory);

        // Quote is inside history
        $endDate = new \DateTime('2018-05-14');
        $quote->setTimestamp($endDate);

        $newHistory = $this->SUT->addQuoteToHistory($quote, $history);
        $this->assertNull($newHistory);
    }

    /**
     * Test addQuoteToHistory
     * Daily interval
     * History is not passed and is in storage
     * Market open
     */
    public function testAddQuoteToHistory180()
    {
        $startDate = new \DateTime('2018-05-14'); // Monday;
        $interval = new \DateInterval('P1D');

        list($instrument, $saved) = $this->createMockHistory($startDate, $numberOfRecords = 5, $interval);

        // Quote is the same date as last date in history
        $_SERVER['TODAY'] = '2018-05-18 09:30:01';
        $today = new \DateTime($_SERVER['TODAY']);

        $quote = new OHLCVQuote();
        $quote->setInstrument($instrument);
        $quote->setProvider($this->SUT::PROVIDER_NAME);
        $quote->setTimestamp($today);
        $quote->setTimeinterval($interval);
        $quote->setOpen(103);
        $quote->setHigh(203);
        $quote->setLow(303);
        $quote->setClose(403);
        $quote->setVolume(503);

        $newHistory = $this->SUT->addQuoteToHistory($quote);

        $this->assertTrue($newHistory);

        $repository = $this->em->getRepository(OHLCVHistory::class);
        $startDate = new \DateTime('2018-05-14');
        $today = new \DateTime('2018-05-19');
        $history = $repository->retrieveHistory($instrument, $interval, $startDate, $today, $this->SUT::PROVIDER_NAME);
        array_pop($saved);
        $lastElement = array_pop($history);
        $this->assertArraySubset($saved, $history);

        $this->assertEquals($this->computeControlSum($lastElement), $this->computeControlSum2($quote));


        // Quote is next T from last day in history
        $_SERVER['TODAY'] = '2018-05-21 09:30:01';
        $today = new \DateTime($_SERVER['TODAY']);

        $quote = new OHLCVQuote();
        $quote->setInstrument($instrument);
        $quote->setProvider($this->SUT::PROVIDER_NAME);
        $quote->setTimestamp($today);
        $quote->setTimeinterval($interval);
        $quote->setOpen(104);
        $quote->setHigh(204);
        $quote->setLow(304);
        $quote->setClose(404);
        $quote->setVolume(504);

        $newHistory = $this->SUT->addQuoteToHistory($quote);

        $this->assertTrue($newHistory);

        $repository = $this->em->getRepository(OHLCVHistory::class);
        $startDate = new \DateTime('2018-05-14');
        $today = new \DateTime('2018-05-21 23:59:59');
        $history = $repository->retrieveHistory($instrument, $interval, $startDate, $today, $this->SUT::PROVIDER_NAME);
        $lastElement = array_pop($history);
        $this->assertArraySubset($saved, $history);

        $this->assertEquals($this->computeControlSum($lastElement), $this->computeControlSum2($quote));

        // Quote is a gap from history
        $_SERVER['TODAY'] = '2018-05-23 09:30:01';
        $today = new \DateTime($_SERVER['TODAY']);

        $quote = new OHLCVQuote();
        $quote->setInstrument($instrument);
        $quote->setProvider($this->SUT::PROVIDER_NAME);
        $quote->setTimestamp($today);
        $quote->setTimeinterval($interval);
        $quote->setOpen(105);
        $quote->setHigh(205);
        $quote->setLow(305);
        $quote->setClose(405);
        $quote->setVolume(505);

        $newHistory = $this->SUT->addQuoteToHistory($quote);

        $this->assertFalse($newHistory);

        // Quote is inside history:
        // This should not happen, as history must always have dates in past, and quote always newer than history.
        //corresponding record in history will be overwritten with info from quote, if their dates match.
    }

    /**
     * Test addQuoteToHistory
     * Daily interval
     * History is not passed and is in storage
     * Market closed
     */
    public function testAddQuoteToHistory190()
    {
        // Quote is the same date as last date in history
        $startDate = new \DateTime('2018-05-14'); // T Monday;
        $interval = new \DateInterval('P1D');

        list($instrument, $saved) = $this->createMockHistory($startDate, $numberOfRecords = 5, $interval);

        // Quote is the same date as last date in history
        $_SERVER['TODAY'] = '2018-05-18 16:00:00';
        $today = new \DateTime($_SERVER['TODAY']);

        $quote = new OHLCVQuote();
        $quote->setInstrument($instrument);
        $quote->setProvider($this->SUT::PROVIDER_NAME);
        $quote->setTimestamp($today);
        $quote->setTimeinterval($interval);
        $quote->setOpen(103);
        $quote->setHigh(203);
        $quote->setLow(303);
        $quote->setClose(403);
        $quote->setVolume(503);

        $newHistory = $this->SUT->addQuoteToHistory($quote);

        $this->assertNull($newHistory);

        $repository = $this->em->getRepository(OHLCVHistory::class);
        $startDate = new \DateTime('2018-05-14');
        $today = new \DateTime('2018-05-18 23:59:59');
        $history = $repository->retrieveHistory($instrument, $interval, $startDate, $today, $this->SUT::PROVIDER_NAME);

        $this->assertArraySubset($saved, $history);

        // Quote is next T from last day in history
        $_SERVER['TODAY'] = '2018-05-21 16:00:00';
        $today = new \DateTime($_SERVER['TODAY']);

        $quote = new OHLCVQuote();
        $quote->setInstrument($instrument);
        $quote->setProvider($this->SUT::PROVIDER_NAME);
        $quote->setTimestamp($today);
        $quote->setTimeinterval($interval);
        $quote->setOpen(103);
        $quote->setHigh(203);
        $quote->setLow(303);
        $quote->setClose(403);
        $quote->setVolume(503);

        $newHistory = $this->SUT->addQuoteToHistory($quote);

        $this->assertNull($newHistory);

        $repository = $this->em->getRepository(OHLCVHistory::class);
        $startDate = new \DateTime('2018-05-14');
        $today = new \DateTime('2018-05-18 23:59:59');
        $history = $repository->retrieveHistory($instrument, $interval, $startDate, $today, $this->SUT::PROVIDER_NAME);

        $this->assertArraySubset($saved, $history);

        // Quote is a gap from history
        $_SERVER['TODAY'] = '2018-05-22 16:00:00';
        $today = new \DateTime($_SERVER['TODAY']);

        $quote = new OHLCVQuote();
        $quote->setInstrument($instrument);
        $quote->setProvider($this->SUT::PROVIDER_NAME);
        $quote->setTimestamp($today);
        $quote->setTimeinterval($interval);
        $quote->setOpen(103);
        $quote->setHigh(203);
        $quote->setLow(303);
        $quote->setClose(403);
        $quote->setVolume(503);

        $newHistory = $this->SUT->addQuoteToHistory($quote);

        $this->assertNull($newHistory);

        $repository = $this->em->getRepository(OHLCVHistory::class);
        $startDate = new \DateTime('2018-05-14');
        $today = new \DateTime('2018-05-22 16:00:00');
        $history = $repository->retrieveHistory($instrument, $interval, $startDate, $today, $this->SUT::PROVIDER_NAME);

        $this->assertArraySubset($saved, $history);

        // Quote is inside history:
        // This should not happen, as history must always have dates in past, and quote always newer than history.
        //corresponding record in history will be overwritten with info from quote, if their dates match.
    }

    /**
     * Test addQuoteToHistory
     * Daily interval
     * History is not passed and is not in storage
     * Market open
     */
    public function testAddQuoteToHistory200()
    {
        $interval = new \DateInterval('P1D');

        $instrument = new Instrument();
        $instrument->setSymbol('TEST');
        $instrument->setName('Instrument for testing purposes');
        $instrument->setExchange(\App\Service\Exchange\NYSE::getExchangeName());

        $this->em->persist($instrument);
        $this->em->flush();

        // Quote is the same date as last date in history
        $_SERVER['TODAY'] = '2018-05-18 09:30:01';
        $today = new \DateTime($_SERVER['TODAY']);

        $quote = new OHLCVQuote();
        $quote->setInstrument($instrument);
        $quote->setProvider($this->SUT::PROVIDER_NAME);
        $quote->setTimestamp($today);
        $quote->setTimeinterval($interval);
        $quote->setOpen(103);
        $quote->setHigh(203);
        $quote->setLow(303);
        $quote->setClose(403);
        $quote->setVolume(503);

        $newHistory = $this->SUT->addQuoteToHistory($quote);

        $this->assertNull($newHistory);
    }

    /**
     * Test addQuoteToHistory
     * Daily interval
     * History is not passed and is not in storage
     * Market closed
     */
    public function testAddQuoteToHistory210()
    {
        $interval = new \DateInterval('P1D');

        $instrument = new Instrument();
        $instrument->setSymbol('TEST');
        $instrument->setName('Instrument for testing purposes');
        $instrument->setExchange(\App\Service\Exchange\NYSE::getExchangeName());

        $this->em->persist($instrument);
        $this->em->flush();

        // Quote is the same date as last date in history
        $_SERVER['TODAY'] = '2018-05-18 16:00:00';
        $today = new \DateTime($_SERVER['TODAY']);

        $quote = new OHLCVQuote();
        $quote->setInstrument($instrument);
        $quote->setProvider($this->SUT::PROVIDER_NAME);
        $quote->setTimestamp($today);
        $quote->setTimeinterval($interval);
        $quote->setOpen(103);
        $quote->setHigh(203);
        $quote->setLow(303);
        $quote->setClose(403);
        $quote->setVolume(503);

        $newHistory = $this->SUT->addQuoteToHistory($quote);

        $this->assertNull($newHistory);
    }

    /**
     * Test retrieveQuote
     */
    public function testRetrieveQuote220()
    {
        $interval = new \DateInterval('P1D');

        $instrument = new Instrument();
        $instrument->setSymbol('TEST');
        $instrument->setName('Instrument for testing purposes');
        $instrument->setExchange(\App\Service\Exchange\NYSE::getExchangeName());

        $this->em->persist($instrument);

        // Quote is the same date as last date in history
        $_SERVER['TODAY'] = '2018-05-18 09:30:01';
        $today = new \DateTime($_SERVER['TODAY']);

        $quote = new OHLCVQuote();
        $quote->setInstrument($instrument);
        $quote->setProvider($this->SUT::PROVIDER_NAME);
        $quote->setTimestamp($today);
        $quote->setTimeinterval($interval);
        $quote->setOpen(103);
        $quote->setHigh(203);
        $quote->setLow(303);
        $quote->setClose(403);
        $quote->setVolume(503);

        $instrument->setOHLCVQuote($quote);

        $this->em->flush();

        $retrievedQuote = $this->SUT->retrieveQuote($instrument);

        $this->assertEquals($this->computeControlSum2($retrievedQuote), $this->computeControlSum2($quote));
    }

    /**
     * Test downloadClosingPrice
     * Today is T
     */
    public function testDownloadClosingPrice230()
    {
        // market open
        $_SERVER['TODAY'] = '2018-05-14 09:30:01';

        $closingPrice = $this->SUT->downloadClosingPrice($this->instrument);

        $this->assertNull($closingPrice);

        // market closed
        $_SERVER['TODAY'] = '2018-05-14 16:00:00';
        $today = new \DateTime($_SERVER['TODAY']);

        $closingPrice = $this->SUT->downloadClosingPrice($this->instrument);

        $this->assertInstanceOf(\App\Entity\OHLCVHistory::class, $closingPrice);
        $this->assertSame($closingPrice->getTimeStamp()->format('Ymd'), $today->format('Ymd'));
    }

    /**
     * Test downloadClosingPrice
     * Today is not T
     */
    public function testDownloadClosingPrice240()
    {
        $_SERVER['TODAY'] = '2018-05-13 09:30:01'; // Sunday
        $prevT = new \DateTime('2018-05-11');

        $closingPrice = $this->SUT->downloadClosingPrice($this->instrument);

        $this->assertInstanceOf(\App\Entity\OHLCVHistory::class, $closingPrice);
        $this->assertSame($closingPrice->getTimeStamp()->format('Ymd'), $prevT->format('Ymd'));
    }

    /**
     * Test retrieveClosingPrice
     * History exists for an instrument
     */
    public function testRetrieveClosingPrice250()
    {
        $startDate = new \DateTime('2018-05-14'); // T Monday;
        $interval = new \DateInterval('P1D');
        list($instrument, $saved) = $this->createMockHistory($startDate, $numberOfRecords = 5, $interval);

        /** @var App\Entity\OHLCVHistory $closingPrice */
        $closingPrice = $this->SUT->retrieveClosingPrice($instrument);
        $lastSaved = array_pop($saved);

        $this->assertEquals($this->computeControlSum($closingPrice), $this->computeControlSum($lastSaved));
    }

    /**
     * Test retrieveClosingPrice
     * History does not exist for an instrument
     */
    public function testRetrieveClosingPrice260()
    {
        $instrument = new Instrument();
        $instrument->setSymbol('TEST');
        $instrument->setName('Instrument for testing purposes');
        $instrument->setExchange(\App\Service\Exchange\NYSE::getExchangeName());
        $this->em->persist($instrument);
        $this->em->flush($instrument);

        /** @var App\Entity\OHLCVHistory $closingPrice */
        $closingPrice = $this->SUT->retrieveClosingPrice($instrument);

        $this->assertNull($closingPrice);
    }

    /**
     * Test addClosingPriceToHistory
     * History is passed as non-empty array
     */
    public function testAddClosingPriceToHistory270()
    {
        // closingPrice coincides with last date in $history
        $startDate = new \DateTime('2018-05-14'); // Monday;
        $interval = new \DateInterval('P1D');
        $history = $this->createSimulatedDownload($this->instrument, $startDate, $numberOfRecords = 5, $interval);

        $endDate = (clone $startDate)->sub($interval);
        $closingPrice = new OHLCVHistory();
        $closingPrice->setInstrument($this->instrument);
        $closingPrice->setProvider($this->SUT::PROVIDER_NAME);
        $closingPrice->setTimestamp($endDate);
        $closingPrice->setTimeinterval($interval);
        $closingPrice->setOpen(rand(0, 100));
        $closingPrice->setHigh(rand(0, 100));
        $closingPrice->setLow(rand(0, 100));
        $closingPrice->setClose(rand(0, 100));
        $closingPrice->setVolume(rand(0, 100));

        $expected = $history;
        array_pop($expected);

        $newHistory = $this->SUT->addClosingPriceToHistory($closingPrice, $history);

        $this->assertArraySubset($expected, $newHistory);

        $lastElement = array_pop($newHistory);

        $this->assertSame($lastElement->getTimestamp()->format('Ymd'), $closingPrice->getTimestamp()->format('Ymd'));
        $this->assertEquals($this->computeControlSum($lastElement), $this->computeControlSum($closingPrice));

        // closingPrice is on nextT, no gap
        $endDate->setDate(2018, 5, 21); // Monday

        $newHistory = $this->SUT->addClosingPriceToHistory($closingPrice, $history);

        $this->assertCount(6, $newHistory);
        $this->assertArraySubset($history, $newHistory);

        $lastElement = array_pop($newHistory);

        $this->assertSame($lastElement->getTimestamp()->format('Ymd'), $closingPrice->getTimestamp()->format('Ymd'));
        $this->assertEquals($this->computeControlSum($lastElement), $this->computeControlSum($closingPrice));

        // closingPrice is on nextT with gap
        $endDate->setDate(2018, 5, 22); // Tuesday

        $newHistory = $this->SUT->addClosingPriceToHistory($closingPrice, $history);

        $this->assertFalse($newHistory);

        // closingPrice earlier than history or within the history but the last record
        $endDate->setDate(2018, 5, 14);

        $newHistory = $this->SUT->addClosingPriceToHistory($closingPrice, $history);

        $this->assertFalse($newHistory);
    }

    /**
     * Test addClosingPriceToHistory
     * History is not passed, or passed as empty array and is in storage
     */
    public function testAddClosingPriceToHistory280()
    {
        // closingPrice coincides with last date in $history
        $startDate = new \DateTime('2018-05-14'); // Monday;
        $interval = new \DateInterval('P1D');

        list($instrument, $saved) = $this->createMockHistory($startDate, $numberOfRecords = 5, $interval);

        $date = (clone $startDate)->sub($interval);
        $closingPrice = new OHLCVHistory();
        $closingPrice->setInstrument($instrument);
        $closingPrice->setProvider($this->SUT::PROVIDER_NAME);
        $closingPrice->setTimestamp($date);
        $closingPrice->setTimeinterval($interval);
        $closingPrice->setOpen(rand(0, 100));
        $closingPrice->setHigh(rand(0, 100));
        $closingPrice->setLow(rand(0, 100));
        $closingPrice->setClose(rand(0, 100));
        $closingPrice->setVolume(rand(0, 100));

        $newHistory = $this->SUT->addClosingPriceToHistory($closingPrice);

        $this->assertTrue($newHistory);

        $expected = $saved;
        array_pop($expected);

        $repository = $this->em->getRepository(OHLCVHistory::class);
        $history = $repository->retrieveHistory(
            $instrument,
            $interval,
            new \DateTime('2018-05-14'),
            null,
            $this->SUT::PROVIDER_NAME
        );

        $lastElement = array_pop($history);
        $this->assertArraySubset($expected, $history);
        $this->assertEquals($this->computeControlSum($lastElement), $this->computeControlSum($closingPrice));

        // closingPrice is on nextT no gap
        $date = new \DateTime('2018-05-21');
        $closingPrice = new OHLCVHistory();
        $closingPrice->setInstrument($instrument);
        $closingPrice->setProvider($this->SUT::PROVIDER_NAME);
        $closingPrice->setTimestamp($date);
        $closingPrice->setTimeinterval($interval);
        $closingPrice->setOpen(rand(0, 100));
        $closingPrice->setHigh(rand(0, 100));
        $closingPrice->setLow(rand(0, 100));
        $closingPrice->setClose(rand(0, 100));
        $closingPrice->setVolume(rand(0, 100));

        $newHistory = $this->SUT->addClosingPriceToHistory($closingPrice);

        $this->assertTrue($newHistory);

        $history = $repository->retrieveHistory(
            $instrument,
            $interval,
            new \DateTime('2018-05-14'),
            null,
            $this->SUT::PROVIDER_NAME
        );

        $this->assertCount(6, $history);

        $lastElement = array_pop($history);
        $this->assertEquals($this->computeControlSum($lastElement), $this->computeControlSum($closingPrice));

        // closingPrice is on nextT with gap
        $date = new \DateTime('2018-05-23');
        $closingPrice = new OHLCVHistory();
        $closingPrice->setInstrument($instrument);
        $closingPrice->setProvider($this->SUT::PROVIDER_NAME);
        $closingPrice->setTimestamp($date);
        $closingPrice->setTimeinterval($interval);
        $closingPrice->setOpen(rand(0, 100));
        $closingPrice->setHigh(rand(0, 100));
        $closingPrice->setLow(rand(0, 100));
        $closingPrice->setClose(rand(0, 100));
        $closingPrice->setVolume(rand(0, 100));

        $newHistory = $this->SUT->addClosingPriceToHistory($closingPrice);

        $this->assertFalse($newHistory);

        // closingPrice earlier than history or within the history but the last record
        $date = new \DateTime('2018-05-14');
        $closingPrice = new OHLCVHistory();
        $closingPrice->setInstrument($instrument);
        $closingPrice->setProvider($this->SUT::PROVIDER_NAME);
        $closingPrice->setTimestamp($date);
        $closingPrice->setTimeinterval($interval);
        $closingPrice->setOpen(rand(0, 100));
        $closingPrice->setHigh(rand(0, 100));
        $closingPrice->setLow(rand(0, 100));
        $closingPrice->setClose(rand(0, 100));
        $closingPrice->setVolume(rand(0, 100));

        $newHistory = $this->SUT->addClosingPriceToHistory($closingPrice);

        $this->assertFalse($newHistory);
    }

    /**
     * Test addClosingPriceToHistory
     * History is not passed, or passed as empty array and is not in storage
     */
    public function testAddClosingPriceToHistory290()
    {
        $instrument = new Instrument();
        $instrument->setSymbol('TEST');
        $instrument->setName('Instrument for testing purposes');
        $instrument->setExchange(\App\Service\Exchange\NYSE::getExchangeName());
        $this->em->persist($instrument);
        $this->em->flush($instrument);

        $interval = new \DateInterval('P1D');

        $date = new \DateTime('2018-05-14');
        $closingPrice = new OHLCVHistory();
        $closingPrice->setInstrument($instrument);
        $closingPrice->setProvider($this->SUT::PROVIDER_NAME);
        $closingPrice->setTimestamp($date);
        $closingPrice->setTimeinterval($interval);
        $closingPrice->setOpen(rand(0, 100));
        $closingPrice->setHigh(rand(0, 100));
        $closingPrice->setLow(rand(0, 100));
        $closingPrice->setClose(rand(0, 100));
        $closingPrice->setVolume(rand(0, 100));

        $newHistory = $this->SUT->addClosingPriceToHistory($closingPrice);

        $this->assertTrue($newHistory);

        $repository = $this->em->getRepository(OHLCVHistory::class);
        $history = $repository->retrieveHistory(
            $instrument,
            $interval,
            new \DateTime('2018-05-14'),
            null,
            $this->SUT::PROVIDER_NAME
        );

        $this->assertCount(1, $history);

        $lastElement = array_pop($history);
        $this->assertSame($lastElement->getTimestamp()->format('Ymd'), $closingPrice->getTimestamp()->format('Ymd'));
        $this->assertEquals($this->computeControlSum($lastElement), $this->computeControlSum($closingPrice));
    }

    public function testGetQuotes10()
    {
        // prepare array of instrument objects
        $instrumentList = [];
        for ($i=0; $i < 3; $i++) {
            $instrumentList[] = $this->faker->randomElement($this->instruments);
        }

        // retrieve quotes
        $quotes = $this->SUT->getQuotes($instrumentList);

        // check that each has a date and values for the price as well as the volume
        foreach ($quotes as $quote) {
            $this->assertInstanceOf(OHLCVQuote::class, $quote);
            $this->assertTrue(in_array($quote->getInstrument(), $instrumentList));
            $this->assertInternalType('float', $quote->getClose());
            $this->assertInternalType('float', $quote->getVolume());
            $this->assertInstanceOf(\DateTime::class, $quote->getTimestamp());
        }
    }

    private function createMockHistory($startDate, $numberOfRecords, $interval)
    {
        $instrument = new Instrument();
        $instrument->setSymbol('TEST');
        $instrument->setName('Instrument for testing purposes');
        $instrument->setExchange(\App\Service\Exchange\NYSE::getExchangeName());

        $this->em->persist($instrument);

        for ($i = 0; $i < $numberOfRecords; $i++, $startDate->add($interval)) {
            $record = new OHLCVHistory();
            $record->setInstrument($instrument);
            $record->setProvider($this->SUT::PROVIDER_NAME);
            $record->setTimestamp(clone $startDate);
            $record->setTimeinterval($interval);
            $record->setOpen(rand(0, 100));
            $record->setHigh(rand(0, 100));
            $record->setLow(rand(0, 100));
            $record->setClose(rand(0, 100));
            $record->setVolume(rand(0, 100));

            $this->em->persist($record);

            $saved[] = $record;
        }

        $this->em->flush();

        return [$instrument, $saved];
    }

    private function createSimulatedDownload($instrument, $startDate, $numberOfRecords, $interval)
    {
        $out = [];
        for ($i = 0; $i < $numberOfRecords; $i++, $startDate->add($interval)) {
            $record = new OHLCVHistory();
            $record->setInstrument($instrument);
            $record->setProvider($this->SUT::PROVIDER_NAME);
            $record->setTimestamp(clone $startDate);
            $record->setTimeinterval($interval);
            $record->setOpen(rand(100, 1000));
            $record->setHigh(rand(100, 1000));
            $record->setLow(rand(100, 1000));
            $record->setClose(rand(100, 1000));
            $record->setVolume(rand(100, 10000));
            $out[] = $record;
        }

        return $out;
    }

    private function computeControlSum(OHLCVHistory $ohlcvHistory)
    {
        return $ohlcvHistory->getOpen() + $ohlcvHistory->getHigh() + $ohlcvHistory->getLow() + $ohlcvHistory->getClose(
            ) + $ohlcvHistory->getVolume();
    }

    private function computeControlSum2(OHLCVQuote $quote)
    {
        return $quote->getOpen() + $quote->getHigh() + $quote->getLow() + $quote->getClose() + $quote->getVolume();
    }

    protected function tearDown(): void
    {
        $instrumentRepository = $this->em->getRepository(Instrument::class);
        $qb = $instrumentRepository->createQueryBuilder('i');
        $qb->delete()->where('i.symbol = :symbol')->setParameter('symbol', self::TEST_SYMBOL);
        $query = $qb->getQuery();
        $query->execute();

        $this->em->close();
    }
}