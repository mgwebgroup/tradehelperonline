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
    public function Intro()
    {
        $repository = $this->em->getRepository(Instrument::class);
        $nasdaqInstruments = $repository->findByExchange('NASDAQ');
        $nyseInstruments = $repository->findByExchange('NYSE');
        $instrumentList = array_merge($nasdaqInstruments, $nyseInstruments);

        $object1 = new \stdClass();
        $object1->expression_name = 'expr1';
        $object1->expression_result = 0;
        $object1->expression = 'Close(1)';
        $object1->instrument = new Instrument();

        $object2 = new \stdClass();
        $object2->expression_name = 'expr1';
        $object2->expression_result = 1;
        $object2->expression = 'Close(1)';
        $object2->instrument = new Instrument();

//        $collection = new ArrayCollection([$object1, $object2]);
        $myArray = [$object1, $object2];
        $condition = new Comparison('expression_result', '=', 0);
//        $criteria = new Criteria($condition);
        $visitor  = new ClosureExpressionVisitor();
        $filter   = $visitor->dispatch($condition);
//        $filtered = array_filter($myArray, $filter);
        $filteredIterator = new \CallbackFilterIterator(new \ArrayIterator($myArray), $filter);
        foreach ($filteredIterator as $item) {
            xdebug_var_dump($item);
        }
    }

    public function testSimpleCondition10()
    {
        $repository = $this->em->getRepository(Instrument::class);
        $nasdaqInstruments = $repository->findByExchange('NASDAQ');
//        $nyseInstruments = $repository->findByExchange('NYSE');
//        $list = array_merge($nasdaqInstruments, $nyseInstruments);
        $expression = 'Close(1)';
        $comparison = ['=', 110.0];
        $interval = new \DateInterval('P1D');
        $date = new \DateTime('2020-03-06');

        $results = $this->SUT->scan($nasdaqInstruments, $expression, $comparison, $interval, $date);

        $this->assertCount(1, $results);

        $instrument = array_shift($results);
        $this->assertSame('FB', $instrument->getSymbol());
    }
}
