<?php

namespace App\Tests\Service\Exchange;


class NASDAQTest extends \Symfony\Bundle\FrameworkBundle\Test\KernelTestCase
{
    private $SUT;

    /**
     * Details on how to access services in tests:
     * https://symfony.com/blog/new-in-symfony-4-1-simpler-service-testing
     */
	protected function setUp(): void
    {
        ini_set('date.timezone', 'America/New_York');
        self::bootKernel();
        $this->SUT = self::$container->get(\App\Service\Exchange\NASDAQ::class);
    }

    public function testIntro()
    {
//    	fwrite(STDOUT, 'Testing NASDAQ symbols'.PHP_EOL);
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
        // inside trading hours 0930-1300:
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
        $this->assertTrue($this->SUT->isTraded('FB', $this->SUT::getExchangeName()));

        $this->assertFalse($this->SUT->isTraded('SPY1', $this->SUT::getExchangeName()));
    }

    /**
     * Testing getTradedInstruments
     */
    public function test40()
    {
        $result = $this->SUT->getTradedInstruments($this->SUT::getExchangeName());
        $nasdaq = file_get_contents($this->SUT::SYMBOLS_LIST);
        // var_dump($nyse); exit();
        foreach ($result as $instrument) {
            $needle = sprintf('%s', $instrument->getSymbol());
            $this->assertTrue(false != strpos($nasdaq, $needle), sprintf( 'symbol=%s was not found in list of NASDAQ symbols.', $instrument->getSymbol() ) );
        }
    }

    /**
     * Test getPreviousTradingDay
     */
    public function test50()
    {
        $date = new \DateTime('26-March-2020');
        // When day is a T on any weekday but Monday
        $prevT = $this->SUT->calcPreviousTradingDay($date);
        $this->assertSame('25-March-2020', $prevT->format('d-F-Y'));

        // When day is a T on Monday
        $date->modify('23-March-2020');
        $prevT = $this->SUT->calcPreviousTradingDay($date);
        $this->assertSame('20-March-2020', $prevT->format('d-F-Y'));

        // When day is any weekend day
        $date->modify('22-March-2020');
        $prevT = $this->SUT->calcPreviousTradingDay($date);
        $this->assertSame('20-March-2020', $prevT->format('d-F-Y'));

        // When day is a holiday
        $date->modify('1-January-2020');
        $prevT = $this->SUT->calcPreviousTradingDay($date);
        $this->assertSame('31-December-2019', $prevT->format('d-F-Y'));
    }

    protected function tearDown(): void
    {
        // fwrite(STDOUT, __METHOD__ . "\n");
    }
}