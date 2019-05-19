<?php

namespace App\Tests\PriceHistory;

// use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Service\Exchange_NYSE;
// use App\Repository\InstrumentRepository;
// use App\Service\Holidays;
// use App\Service\Yasumi;
// use Yasumi\Yasumi;

class Exchange_NYSETest extends KernelTestCase
{
    private $SUT;

    // private $doctrine;

    /**
     * Details on how to access services in tests:
     * https://symfony.com/blog/new-in-symfony-4-1-simpler-service-testing
     */
	protected function setUp(): void
    {
        self::bootKernel();
        // $container = self::$container;
        // $container = self::$kernel->getContainer();
        $container = self::$container->get('test.service_container');
        // var_dump($container->has('test.service_container')); exit();

        // $this->SUT = $container->get('app.exchange.nyse');
        $this->SUT = $container->get(Exchange_NYSE::class);
        // $this->doctrine = $container->get('doctrine');
    }

    public function testIntro()
    {
    	fwrite(STDOUT, 'Test');
    	$this->assertTrue(true);
    }

    /**
     * Testing isTradingDay
     */
    public function test10()
    {
        $date = new \DateTime();
        $currentYear = $date->format('Y');

        // check for a regular weekend this year
        $date->modify('next Saturday');
        $this->assertFalse($this->SUT->isTradingDay($date));

        // check for a regular holiday this year
        $date->modify('jan 1st');
        // var_dump($date->format('c')); exit();
        $this->assertFalse($this->SUT->isTradingDay($date));

        // check for a regular weekend on a different year
        $years = rand(1,5);
        $interval = new \DateInterval(sprintf('P%sY', $years));
        $date->sub($interval);
        $date->modify('first Saturday');
        $this->assertFalse($this->SUT->isTradingDay($date));

        // check for a regular holiday on a different year
        $date->modify('July 4');
        $this->assertFalse($this->SUT->isTradingDay($date));

        // check for a substitute holiday that falls on Saturday in some year (must occur on Friday).
        $date->modify('July 3 2020');
        $this->assertFalse($this->SUT->isTradingDay($date));

        // check for a substitute holiday that falls on Sunday in some year (must occur on Monday)
        $date->modify('July 5 2021');
        $this->assertFalse($this->SUT->isTradingDay($date));

        // check for a regular trading day this year
        $date->modify('13 May 2019');
        $this->assertTrue($this->SUT->isTradingDay($date));
        
        // check for a regular trading day on a different year
        $date->modify('14 May 2018');
        $this->assertTrue($this->SUT->isTradingDay($date));

        // print holidays
        // foreach ($this->SUT->holidays as $shortName => $date) {
        //     echo sprintf('%s %s', $shortName, $date->format('c')).PHP_EOL;
        // }
        // exit();
    }

    /**
     * Testing isOpen
     * Definitions:
     * For holidays which occur on Saturday or Sunday, a substitute holiday will occur on Friday or Monday respectively.
     */
    public function test20()
    {
        //
        // any hour of the day
        //
        $secondsSinceMidnight = rand(0, 3600*24-1);
        $interval = new \DateInterval(sprintf('PT%dS', $secondsSinceMidnight));

        // check for any holiday which would occur on a regular weekday
        $date = new \DateTime('19-Apr-2019'); // Good Friday
        $date->add($interval);
        $this->assertFalse($this->SUT->isOpen($date));

        // check for any holiday which would be a substitute, i.e. actual holiday is on Saturday or Sunday
        $date = new \DateTime('24-Dec-2021'); // Friday, December 24 2021 is a Christmas holiday observed
        $date->add($interval);
        $this->assertFalse($this->SUT->isOpen($date));

        //
        // insde trading hours 0930-1300:
        //
        $secondsSinceMidnight = rand(9.5*3600+1, 13*3600-1);
        $interval = new \DateInterval(sprintf('PT%dS', $secondsSinceMidnight));

        // check for pre-Indepence-day on a weekday
        $date = new \DateTime('3-Jul-2019'); // Wednesday, July 3 2019
        $date->add($interval);
        $this->assertTrue($this->SUT->isOpen($date));

        // check for post-Thanksgiving day
        $date = new \DateTime('2019-11-29'); // Friday, November 29 2019
        $date->add($interval);
        $this->assertTrue($this->SUT->isOpen($date));

        // check for pre-Christmas day on a weekday
        $date = new \DateTime('2019-12-24'); // Tuesday, December 24 2019
        $date->add($interval);
        $this->assertTrue($this->SUT->isOpen($date));


        //
        // outside trading hours 0930-1300:
        //
        // $secondsSinceMidnight1 = rand(13*3600, 24*3600);
        // $secondsSinceMidnight2 = rand(0, 9.5*3600);
        $secondsSinceMidnight = [rand(13*3600, 24*3600), rand(0, 9.5*3600)];
        shuffle($secondsSinceMidnight);
        $interval = new \DateInterval(sprintf('PT%dS', $secondsSinceMidnight));

        // check for pre-Indepence-day on a weekday
        $date = new \DateTime('3-Jul-2019'); // Wednesday, July 3 2019
        $date->add($interval);
        $this->assertFalse($this->SUT->isOpen($date));

        // check for post-Thanksgiving day
        $date = new \DateTime('2020-11-26'); // Friday, November 29 2019
        $date->add($interval);
        $this->assertFalse($this->SUT->isOpen($date));

        // check for pre-Christmas day on a weekday
        // check for pre-Christmas day on a weekday
        $date = new \DateTime('2018-12-24'); // Monday, December 24 2018
        $date->add($interval);
        $this->assertFalse($this->SUT->isOpen($date));


        //
        // insde trading hours 0930-1600:
        //
        $secondsSinceMidnight = rand(9.5*3600+1, 16*3600-1);
        $interval = new \DateInterval(sprintf('PT%dS', $secondsSinceMidnight));

        // check for any pre-Substitute day out of Independence and Christmas day
        $date = new \DateTime('3-Jul-2020'); // Friday, July 3 2020. July 4th is celebrated on Saturday, with Observance on Friday as well.
        $date->add($interval);
        $this->assertFalse($this->SUT->isOpen($date));

        $date = new \DateTime('2-Jul-2020'); // Thursday, July 2 2020. Market is open
        $date->add($interval);
        $this->assertTrue($this->SUT->isOpen($date));

        $date = new \DateTime('24-Dec-2021'); // Friday, December 24 2021 is observed
        $date->add($interval);
        $this->assertFalse($this->SUT->isOpen($date));

        $date = new \DateTime('23-Dec-2021'); // Thursday, December 23 2021
        $date->add($interval);
        $this->assertTrue($this->SUT->isOpen($date));


        //
        // outside trading hours 0930-1600:
        //
        $secondsSinceMidnight = [rand(16*3600, 24*3600), rand(0, 9.5*3600)];
        shuffle($secondsSinceMidnight);
        $interval = new \DateInterval(sprintf('PT%dS', $secondsSinceMidnight));

        // check for any pre-Substitute Holiday day out of Independence and Christmas day
        $date = new \DateTime('3-Jul-2020'); // Friday, July 3 2020. July 4th is celebrated on Saturday, with Observance on Friday as well.
        $date->add($interval);
        $this->assertFalse($this->SUT->isOpen($date));

        $date = new \DateTime('2-Jul-2020'); // Thursday, July 2 2020. Market is tradable, however we are outside of trading hours
        $date->add($interval);
        $this->assertFalse($this->SUT->isOpen($date));

        $date = new \DateTime('24-Dec-2021'); // Friday, December 24 2021 is observed
        $date->add($interval);
        $this->assertFalse($this->SUT->isOpen($date));

        $date = new \DateTime('23-Dec-2021'); // Thursday, December 23 2021 market is tradable, however we are outside of trading hours
        $date->add($interval);
        $this->assertFalse($this->SUT->isOpen($date));


        //
        // any hour of the day
        //
        $secondsSinceMidnight = rand(0*3600, 24*3600-1);
        $interval = new \DateInterval(sprintf('PT%dS', $secondsSinceMidnight));

        // check for a regular weekend
        $date = new \DateTime('18-May-2019');  // Saturday 18-May-2019
        $date->add($interval);
        $this->assertFalse($this->SUT->isOpen($date));


        //
        // inside trading hours 0930-1600
        //
        $secondsSinceMidnight = rand(9.5*3600+1, 16*3600-1);
        $interval = new \DateInterval(sprintf('PT%dS', $secondsSinceMidnight));

        // check for a regular weekday
        $date = new \DateTime('18-May-2018'); // Friday 18-May-2018 
        $date->add($interval);
        $this->assertTrue($this->SUT->isOpen($date));


        //
        // outside trading hours 0930-1600
        //
        $secondsSinceMidnight = [rand(16*3600, 24*3600), rand(0, 9.5*3600)];
        shuffle($secondsSinceMidnight);
        $interval = new \DateInterval(sprintf('PT%dS', $secondsSinceMidnight));

        // check for a regular weekday        
        $date = new \DateTime('18-May-2018'); // Friday 18-May-2018 
        $date->add($interval);
        $this->assertFalse($this->SUT->isOpen($date));
    }

    /**
     * Testing isTraded
     */
    public function test30()
    {
        $this->assertTrue($this->SUT->isTraded('MCD'));

        $this->assertFalse($this->SUT->isTraded('SPY1'));
    }

    /**
     * Testing getTradedInsruments
     */
    public function test40()
    {
        $result = $this->SUT->getTradedInstruments();
        $nyse = file_get_contents('data/source/nyse_companylist.csv');
        // var_dump($nyse); exit();
        foreach ($result as $instrument) {
            $needle = sprintf('"%s"', $instrument->getSymbol());
            $this->assertTrue(false != strpos($nyse, $needle), sprintf( 'symbol=%s was not found in list of NYSE symbols.', $instrument->getSymbol() ) );
        }

    }


    protected function tearDown(): void
    {
        // fwrite(STDOUT, __METHOD__ . "\n");
    }

    // private function getLines($file) {
    // $f = fopen($file, 'r');
    //     try {
    //         while ($line = fgets($f)) {
    //             yield $line;
    //         }
    //     } finally {
    //         fclose($f);
    //     }
    // }
}