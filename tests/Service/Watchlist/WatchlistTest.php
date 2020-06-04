<?php

namespace App\Tests\Service\Watchlist;

use App\Entity\Formula;
use App\Entity\Instrument;
use App\Entity\Watchlist;
use App\Exception\PriceHistoryException;
use App\Service\Watchlist\Factory;
use \Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Service\ExpressionHandler\OHLCV\Calculator;

class WatchlistTest extends KernelTestCase
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
     * @var \App\Entity\Formula[]
     */
    private $formulas;

    /**
     * @var App\Service\ExpressionHandler\OHLCV\Calculator
     */
    private $calculator;

    /**
     * Details on how to access services in tests:
     * https://symfony.com/blog/new-in-symfony-4-1-simpler-service-testing
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $this->SUT = self::$container->get(Factory::class);
        $this->em = self::$container->get('doctrine')->getManager();
        $this->calculator = self::$container->get(Calculator::class);
        $formula1 = new Formula();
        $formula1->setName('Closing P');
        $formula1->setContent('Close(0)');
        $formula1->setCreatedAt(new \DateTime());
//        $formula1->setUpdatedAt(new \DateTime());
        $formula1->setTimeInterval(new \DateInterval('P1D'));
        $this->em->persist($formula1);
        $this->formulas[] = $formula1;

        $formula2 = new Formula();
        $formula2->setName('Closing V');
        $formula2->setContent('Volume(0)');
        $formula2->setCreatedAt(new \DateTime());
//        $formula2->setUpdatedAt(new \DateTime());
        $formula2->setTimeInterval(new \DateInterval('P1D'));
        $this->em->persist($formula2);
        $this->formulas[] = $formula2;

        $this->em->flush();
    }

    public function testCreate10()
    {
        $name = 'test_list';
        $instruments = $this->em->getRepository(Instrument::class)->findAll();
        $formulas = $this->em->getRepository(Formula::class)->findByName('Closing P');
        $watchlist = $this->SUT::create($name, $instruments, $formulas, $this->em);

        $this->assertInstanceOf(Watchlist::class, $watchlist);
    }

    /**
     * Tests for last Closing P and Closing V values regardless of date.
     * If date is not specified, today's date will be used.
     */
//    public function testUpdate10()
//    {
//        $name = 'test_list';
//        $instruments = $this->em->getRepository(Instrument::class)->findAll();
//        $formula1 = $this->em->getRepository(Formula::class)->findOneByName('Closing P');
//        $formula2 = $this->em->getRepository(Formula::class)->findOneByName('Closing V');
//        $watchlist = $this->SUT::create($name, $instruments, [$formula1], $this->em);
//        $watchlist->addFormula($formula2);
//        $watchlist->update($this->calculator);
//
//        $this->assertArraySubset(['LIN' => ['Closing P' => 182.59, 'Closing V' => 2829400]], $watchlist->getValues());
//        $this->assertArraySubset(['FB' => ['Closing P' => 101, 'Closing V' => 1001]], $watchlist->getValues());
//    }

    /**
     * Takes date into account
     */
    public function testUpdate20()
    {
        $name = 'test_list';
        $instruments = $this->em->getRepository(Instrument::class)->findBySymbol('LIN');
        $formula1 = $this->em->getRepository(Formula::class)->findOneByName('Closing P');
        $formula2 = $this->em->getRepository(Formula::class)->findOneByName('Closing V');
        $watchlist = $this->SUT::create($name, $instruments, [$formula1], $this->em);
        $watchlist->addFormula($formula2);
        $watchlist->update($this->calculator, new \DateTime('2020-05-16'));

        $this->assertArraySubset(['LIN' => ['Closing P' => 182.59, 'Closing V' => 2829400]], $watchlist->getValues());
    }

    /**
     * Takes date into account, but OHLCV data for it does not exist
     */
    public function testUpdate30()
    {
        $name = 'test_list';
        $instruments = $this->em->getRepository(Instrument::class)->findBySymbol('FB');
        $formula1 = $this->em->getRepository(Formula::class)->findOneByName('Closing P');
        $formula2 = $this->em->getRepository(Formula::class)->findOneByName('Closing V');
        $watchlist = $this->SUT::create($name, $instruments, [$formula1], $this->em);
        $watchlist->addFormula($formula2);
        $this->expectException(PriceHistoryException::class);
        $watchlist->update($this->calculator, new \DateTime());
    }

    protected function tearDown(): void
    {
        foreach ($this->formulas as $formula) {
            $this->em->remove($formula);
        }
        $this->em->flush();
    }
}
