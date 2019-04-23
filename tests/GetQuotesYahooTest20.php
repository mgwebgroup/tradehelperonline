<?php
use PHPUnit\Framework\TestCase;
use MGWebGroup\GetQuotes;
use SgCsv\CsvMappedReader;
use Yasumi\Yasumi, Yasumi\Holiday, Yasumi\Filters\BankHolidaysFilter;
use Faker\Factory;

/**
 * Test case: timeOfInquiry = not T Sat-06-Aug-2016 0000-2359 or Sun-07-Aug-2016 0000-2359, timeOfLastUpdateOfTheQuoteFile = no_quote_file
 * Mock historical data is used. No internet connection required.
 */

class GetQuotesYahooTest20 extends TestCase
{
    /**
     * object that stores the class under test
     */
    protected $CUT;
    /**
     * Name of the quotes provider
     */
    const PROVIDER = 'yahoo';
    /**
     * array sample historical quote array that can be downloaded from the yahoo finance api
     */
    protected $testOHLCVArray = array(
        'query' => array(
            'count' => 7,
            'created' => '2016-08-06T20:35:30Z',
            'lang' => 'en-US',
            'results' => array(
                    'quote' => array(
                            0 => array(
                                    'Symbol' => 'TST',
                                    'Date' => '2016-08-05',
                                    'Open' => '38.599998',
                                    'High' => '38.669998',
                                    'Low' => '38.259998',
                                    'Close' => '38.57',
                                    'Volume' => '7501600',
                                    'Adj_Close' => '38.57',
                                ),
                            1 => array(
                                    'Symbol' => 'TST',
                                    'Date' => '2016-08-04',
                                    'Open' => '38.599998',
                                    'High' => '38.669998',
                                    'Low' => '38.259998',
                                    'Close' => '38.57',
                                    'Volume' => '7501600',
                                    'Adj_Close' => '38.57',
                                ),
                            2 => array(
                                    'Symbol' => 'TST',
                                    'Date' => '2016-08-03',
                                    'Open' => '38.599998',
                                    'High' => '38.669998',
                                    'Low' => '38.259998',
                                    'Close' => '38.57',
                                    'Volume' => '7501600',
                                    'Adj_Close' => '38.57',
                                ),
                            3 => array(
                                    'Symbol' => 'TST',
                                    'Date' => '2016-08-02',
                                    'Open' => '38.599998',
                                    'High' => '38.669998',
                                    'Low' => '38.259998',
                                    'Close' => '38.57',
                                    'Volume' => '7501600',
                                    'Adj_Close' => '38.57',
                                ),

                            4 => array(
                                    'Symbol' => 'TST',
                                    'Date' => '2016-08-01',
                                    'Open' => '38.18',
                                    'High' => '38.889999',
                                    'Low' => '38.099998',
                                    'Close' => '38.799999',
                                    'Volume' => '9390600',
                                    'Adj_Close' => '38.799999',
                                ),

                            5 => array(
                                    'Symbol' => 'TST',
                                    'Date' => '2016-07-29',
                                    'Open' => '38.470001',
                                    'High' => '38.52',
                                    'Low' => '38.080002',
                                    'Close' => '38.189999',
                                    'Volume' => '13173300',
                                    'Adj_Close' => '38.189999',
                                ),

                            6 => array(
                                    'Symbol' => 'TST',
                                    'Date' => '2016-07-28',
                                    'Open' => '38.580002',
                                    'High' => '38.639999',
                                    'Low' => '38.23',
                                    'Close' => '38.52',
                                    'Volume' => '7404800',
                                    'Adj_Close' => '38.52',
                                ),
                            ),
                        ),
                    ),
            );
    /**
     * array sample current quote array that can be downloaded from the yahoo web page
     */    
    protected $testCurrentQuote = array(
        'Symbol' => 'TST',
        'Date' => '2016-08-05',
        'Open' => '38.599998',
        'High' => '38.669998',
        'Low' => '38.259998',
        'Close' => '38.57',
        'Volume' => '7501600',
        'Adj_Close' => '38.57',
    ); 
 

    use MGWebGroup\tests\ServiceFunctions;


    protected function setUp() 
    {
 
        $symbol = 'TST';

        $pathSpec = GetQuotes::PATH_TO_OHLCV_FILES . '/' . GetQuotes::createNameOfOHLCVFile($symbol);
        // fwrite(STDOUT, "\nQuotes will be downloaded into file: " . $pathSpec . "\n");

        if (file_exists($pathSpec)) unlink($pathSpec);

        $timeZoneNYC = new DateTimeZone('America/New_York');
        $fileSystemTimeZone = new DateTimeZone('America/Denver');

        $this->CUT = $this->getMockBuilder(GetQuotes::class)
                ->setMockClassName('GetQuotes')
                ->setConstructorArgs([$symbol, $fileSystemTimeZone])
                ->setMethods(['createDate','downloadOHLCV','getCurrentQuote'])
                ->getMock();

        $faker = Faker\Factory::create();
        $now = $faker->dateTimeBetween($startDate = '2016-08-06 00:00:00', $endDate = '2016-08-07 23:59:59', $timezone = 'America/New_York');
        $start = new DateTime('2016-07-28', $timeZoneNYC);
        $map = [
            ['now', $now],
            ['start', $start]
        ];        
        $this->CUT->expects($this->any())
                ->method('createDate')
                ->will($this->returnValueMap($map));

        $this->CUT->expects($this->any())
                ->method('downloadOHLCV')
                ->will($this->returnValue($this->testOHLCVArray));

        $this->CUT->expects($this->never())
                ->method('getCurrentQuote')
                ->will($this->returnValue($this->testCurrentQuote));


    }

    /**
     * Tests that the saved OHLCV file is for the given symbol and is from STARTDATE to T-1.
     * Also tests a random line inside the file to match downloaded quote.
     */
    public function testOHLCV()
    {
        fwrite(STDOUT,"\nTest case for when No OHLCV file present. Testing download of historical quotes for inquiry occurring at not-T any time between 0000 and 2359: ");

        $this->CUT->updateQuotes(self::PROVIDER);
        
        fwrite(STDOUT, $this->CUT->getDate('query')->format(DateTime::W3C) . "\n");

        $pathSpec = $this->CUT->getOHLCVPathSpec();
        $this->assertFileExists($pathSpec);

        $csv = new CsvMappedReader($pathSpec);

        $lastLineIndex = $csv->count() - 1;
        $csv->seek($lastLineIndex);
        $line = $csv->current();
        $this->assertArraySubset($line, $this->testOHLCVArray['query']['results']['quote'][$lastLineIndex]);

        $csv->rewind();
        $line = $csv->current();
        $this->assertArraySubset($line, $this->testOHLCVArray['query']['results']['quote'][0]);

    }

    protected function tearDown()
    {
        unset($this->CUT);
    }

}