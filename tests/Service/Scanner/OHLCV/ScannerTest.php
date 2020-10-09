<?php
/**
 * This file is part of the Trade Helper Online package.
 *
 * (c) 2019-2020  Alex Kay <alex110504@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Service\Scanner\OHLCV;

use App\Service\Scanner\OHLCV\Scanner;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\ClosureExpressionVisitor;
use Doctrine\Common\Collections\Expr\Comparison;
use \Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Entity\Instrument;

class ScannerTest extends KernelTestCase
{
    /**
     * @var Scanner;
     */
    private $SUT;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * Details on how to access services in tests:
     * https://symfony.com/blog/new-in-symfony-4-1-simpler-service-testing
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $this->SUT = self::$container->get(Scanner::class);
        $this->em = self::$container->get('doctrine')->getManager();
    }

    /**
     * This function was used to figure out possible approach
     */
//    public function Intro()
//    {
//        $repository = $this->em->getRepository(Instrument::class);
//        $nasdaqInstruments = $repository->findByExchange('NASDAQ');
//        $nyseInstruments = $repository->findByExchange('NYSE');
//        $instrumentList = array_merge($nasdaqInstruments, $nyseInstruments);
//
//        $object1 = new \stdClass();
//        $object1->expression_name = 'expr1';
//        $object1->expression_result = 0;
//        $object1->expression = 'Close(1)';
//        $object1->instrument = new Instrument();
//
//        $object2 = new \stdClass();
//        $object2->expression_name = 'expr1';
//        $object2->expression_result = 1;
//        $object2->expression = 'Close(1)';
//        $object2->instrument = new Instrument();
//
////        $collection = new ArrayCollection([$object1, $object2]);
//        $myArray = [$object1, $object2];
//        $condition = new Comparison('expression_result', '=', 0);
////        $criteria = new Criteria($condition);
//        $visitor  = new ClosureExpressionVisitor();
//        $filter   = $visitor->dispatch($condition);
////        $filtered = array_filter($myArray, $filter);
//        $filteredIterator = new \CallbackFilterIterator(new \ArrayIterator($myArray), $filter);
//        foreach ($filteredIterator as $item) {
//            xdebug_var_dump($item);
//        }
//    }

    /**
     * Test simple condition
     */
    public function testSimpleCondition10()
    {
        $repository = $this->em->getRepository(Instrument::class);
        $nasdaqInstruments = $repository->findByExchange('NASDAQ');
        $nyseInstruments = $repository->findByExchange('NYSE');
        $list = array_merge($nasdaqInstruments, $nyseInstruments);
        $expression = 'Close(1)';
        $comparison = ['=', 110.0];
        $interval = new \DateInterval('P1D');
        $date = new \DateTime('2020-03-06');

        $results = $this->SUT->scan($list, $expression, $comparison, $interval, $date);

        $this->assertCount(1, $results);

        $instrument = array_shift($results);
        $this->assertSame('FB', $instrument->getSymbol());
    }

    /**
     * Test Daily Inside Bar
     * LIN has one on 02/26/2020
     */
    public function testLogicalCondition10()
    {
        $repository = $this->em->getRepository(Instrument::class);
        $nasdaqInstruments = $repository->findByExchange('NASDAQ');
        $nyseInstruments = $repository->findByExchange('NYSE');
        $list = array_merge($nasdaqInstruments, $nyseInstruments);
        $expression = '(Low(0) - Low(1)) > 0 and (High(1) - High(0)) > 0';
        $comparison = ['=', true];
        $interval = new \DateInterval('P1D');
        $date = new \DateTime('2020-02-26');

        $results = $this->SUT->scan($list, $expression, $comparison, $interval, $date);

        $this->assertCount(1, $results);

        $instrument = array_shift($results);
        $this->assertSame('LIN', $instrument->getSymbol());
    }

    /**
     * Test Daily Inside Bar and Down
     * LIN has one on 02/27/2020
     */
    public function testLogicalCondition20()
    {
        $repository = $this->em->getRepository(Instrument::class);
        $nasdaqInstruments = $repository->findByExchange('NASDAQ');
        $nyseInstruments = $repository->findByExchange('NYSE');
        $list = array_merge($nasdaqInstruments, $nyseInstruments);
        $expression = '(High(2) >= High(1)) and (Low(2) <= Low(1)) and (Close(0) <= Low(1))';
        $comparison = ['=', true];
        $interval = new \DateInterval('P1D');
        $date = new \DateTime('2020-02-27');

        $results = $this->SUT->scan($list, $expression, $comparison, $interval, $date);

        $this->assertCount(1, $results);

        $instrument = array_shift($results);
        $this->assertSame('LIN', $instrument->getSymbol());
    }

    /**
     * Test Daily Inside Bar and Up
     * Data fixture sequence for FB has one on 02/24/2020
     */
    public function testLogicalCondition30()
    {
        $repository = $this->em->getRepository(Instrument::class);
        $nasdaqInstruments = $repository->findByExchange('NASDAQ');
        $nyseInstruments = $repository->findByExchange('NYSE');
        $list = array_merge($nasdaqInstruments, $nyseInstruments);
        $expression = '(High(2) >= High(1)) and (Low(2) <= Low(1)) and (Close(0) >= High(1))';
        $comparison = ['=', true];
        $interval = new \DateInterval('P1D');
        $date = new \DateTime('2020-02-24');

        $results = $this->SUT->scan($list, $expression, $comparison, $interval, $date);

        $this->assertCount(1, $results);

        $instrument = array_shift($results);
        $this->assertSame('FB', $instrument->getSymbol());
    }

    /**
     * Test Daily Hammer
     * Data fixture sequence for FB as well as natural data for LIN has one on 02/28/2020
     */
    public function testLogicalCondition40()
    {
        $repository = $this->em->getRepository(Instrument::class);
        $nasdaqInstruments = $repository->findByExchange('NASDAQ');
        $nyseInstruments = $repository->findByExchange('NYSE');
        $list = array_merge($nasdaqInstruments, $nyseInstruments);
        $expression = '(((High(0) - Low(0)) > 3 * (Open(0) - Close(0)) and ((Close(0) - Low(0)) / (0.001 + High(0) - Low(0)) > 0.6) and ((Open(0) - Low(0)) / (0.001 + High(0) - Low(0)) > 0.6)))';
        $comparison = ['=', true];
        $interval = new \DateInterval('P1D');
        $date = new \DateTime('2020-02-28');

        $results = $this->SUT->scan($list, $expression, $comparison, $interval, $date);

        $this->assertCount(2, $results);
    }

    /**
     * Test Daily Shooting Star
     * Data fixture sequence for FB has one on 03/03/2020
     */
    public function testLogicalCondition50()
    {
        $repository = $this->em->getRepository(Instrument::class);
        $nasdaqInstruments = $repository->findByExchange('NASDAQ');
        $nyseInstruments = $repository->findByExchange('NYSE');
        $list = array_merge($nasdaqInstruments, $nyseInstruments);
        $expression = '(((High(0) - Low(0)) > 3 * (Open(0) - Close(0)) and ((High(0) - Close(0)) / (0.001 + High(0) - Low(0)) > 0.6) and ((High(0)-Open(0)) / (0.001 + High(0) - Low(0)) > 0.6)))';
        $comparison = ['=', true];
        $interval = new \DateInterval('P1D');
        $date = new \DateTime('2020-03-03');

        $results = $this->SUT->scan($list, $expression, $comparison, $interval, $date);

        $this->assertCount(1, $results);

        $instrument = array_shift($results);
        $this->assertSame('FB', $instrument->getSymbol());
    }

    /**
     * Test Daily Bullish Engulphing
     * Natural data for LIN has one on 05/07/2020
     */
    public function testLogicalCondition60()
    {
        $repository = $this->em->getRepository(Instrument::class);
//        $nasdaqInstruments = $repository->findByExchange('NASDAQ');
        $nyseInstruments = $repository->findByExchange('NYSE');
        $list = $nyseInstruments;
        $expression = '(Open(0) < Open(1) and Open(0) < Close(1)) and (Close(0) > Open(1) and Close(0) > Close(1))';
        $comparison = ['=', true];
        $interval = new \DateInterval('P1D');
        $date = new \DateTime('2020-05-07');

        $results = $this->SUT->scan($list, $expression, $comparison, $interval, $date);

        $this->assertCount(1, $results);

        $instrument = array_shift($results);
        $this->assertSame('LIN', $instrument->getSymbol());
    }

    /**
     * Test Daily Bearish Engulphing
     * Natural data for LIN has one on 01/02/2020
     */
    public function testLogicalCondition70()
    {
        $repository = $this->em->getRepository(Instrument::class);
//        $nasdaqInstruments = $repository->findByExchange('NASDAQ');
        $nyseInstruments = $repository->findByExchange('NYSE');
        $list = $nyseInstruments;
        $expression = '(Close(0) < Close(1) and Close(0) < Open(1)) and (Open(0) > Close(1) and Open(0) > Open(1))';
        $comparison = ['=', true];
        $interval = new \DateInterval('P1D');
        $date = new \DateTime('2020-01-02');

        $results = $this->SUT->scan($list, $expression, $comparison, $interval, $date);

        $this->assertCount(1, $results);

        $instrument = array_shift($results);
        $this->assertSame('LIN', $instrument->getSymbol());
    }

    /**
     * Test Daily Previous Hammer & Up
     * Data fixture sequence for FB and natural data for LIN has one on 03/02/2020
     */
    public function testLogicalCondition80()
    {
        $repository = $this->em->getRepository(Instrument::class);
        $nasdaqInstruments = $repository->findByExchange('NASDAQ');
        $nyseInstruments = $repository->findByExchange('NYSE');
        $list = array_merge($nasdaqInstruments, $nyseInstruments);;
        $expression = '(((High(1) - Low(1)) > 3 * (Open(1) - Close(1)) and ((Close(1) - Low(1)) / (0.001 + High(1) - Low(1)) > 0.6) and ((Open(1) - Low(1)) / (0.001 + High(1) - Low(1)) > 0.6))) and Close(0) > High(1)';
        $comparison = ['=', true];
        $interval = new \DateInterval('P1D');
        $date = new \DateTime('2020-03-02');

        $results = $this->SUT->scan($list, $expression, $comparison, $interval, $date);

        $this->assertCount(2, $results);

//        $instrument = array_shift($results);
//        $this->assertSame('LIN', $instrument->getSymbol());
    }

    /**
     * Test Daily Previous Shooting Star & Down
     * Data fixture sequence for FB has one on 03/04/2020
     */
    public function testLogicalCondition90()
    {
        $repository = $this->em->getRepository(Instrument::class);
        $nasdaqInstruments = $repository->findByExchange('NASDAQ');
        $nyseInstruments = $repository->findByExchange('NYSE');
        $list = array_merge($nasdaqInstruments, $nyseInstruments);;
        $expression = '(((High(1) - Low(1)) > 3 * (Open(1) - Close(1)) and ((High(1) - Close(1)) / (0.001 + High(1) - Low(1)) > 0.6) and ((High(1)-Open(1)) / (0.001 + High(1) - Low(1)) > 0.6))) and Open(0) < High(1) and Close(0) < Low(1)';
        $comparison = ['=', true];
        $interval = new \DateInterval('P1D');
        $date = new \DateTime('2020-03-04');

        $results = $this->SUT->scan($list, $expression, $comparison, $interval, $date);

        $this->assertCount(1, $results);

        $instrument = array_shift($results);
        $this->assertSame('FB', $instrument->getSymbol());
    }
}
