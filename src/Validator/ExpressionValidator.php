<?php

namespace App\Validator;

use App\Entity\Expression;
use App\Entity\Instrument;
use App\Entity\OHLCV\History;
use App\Exception\PriceHistoryException;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use App\Service\ExpressionHandler\OHLCV\Calculator;

class ExpressionValidator extends ConstraintValidator
{
    /**
     * @var Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var App\Service\ExpressionHandler\OHLCV\Calculator
     */
    protected $calculator;


    public function __construct(
      RegistryInterface $doctrine,
      Calculator $calculator
    )
    {
        $this->em = $doctrine->getManager();
        $this->calculator = $calculator;
    }

    /**
     * @param Expression $value
     * @param Constraint $constraint
     * @throws \Exception
     */
    public function validate($value, Constraint $constraint)
    {
        /* @var $constraint \App\Validator\Expression */

        if (null === $value || '' === $value) {
            return;
        }

        if (!$value instanceof Expression) {
            return;
        }

        // expression by this name already exists?
        if ($this->em->getRepository(Expression::class)->findOneBy(['name' => $value->getName()])) {
            $this->context->buildViolation($constraint->name)
              ->setParameter('{{ name }}', $value->getName())
              ->addViolation();
        }

        // check if Expression's formula is valid
        if ($constraint->payload) {
            $instrument = $constraint->payload;
        } else {
            $repository = $this->em->getRepository(Instrument::class);
            $nasdaqInstruments = $repository->findByExchange('NASDAQ');
            $nyseInstruments = $repository->findByExchange('NYSE');
            $list = array_merge($nasdaqInstruments, $nyseInstruments);
            if (empty($list)) {
                throw new \Exception('Could not find any instruments');
            }

            $instrument = $list[array_rand($list)];
        }

        $priceHistory = $this->em->getRepository(History::class)->findBy(
          ['instrument' => $instrument, 'timeinterval' => $value->getTimeinterval()],
          ['timestamp' => 'desc'],
          1
        );

        $latestRecord = array_shift($priceHistory);
        if ($latestRecord) {
            $date = clone $latestRecord->getTimestamp();
        } else {
            throw new \Exception(sprintf('No price data for `%s` and interval %s', $instrument->getSymbol(),
                                         $value->getTimeinterval()->format('P%Y%M%DT%Imin%Ss')));
        }

        try {
            $result = $this->calculator->evaluate($value->getFormula(), [
              'instrument' => $instrument,
              'interval' => $value->getTimeinterval(),
              'date' => $date
            ]);

            if (is_float($result) || is_bool($result)) {
                return;
            } else {
                $this->context->buildViolation($constraint->message)
                  ->setParameter('{{ formula }}', $value->getFormula())
                  ->addViolation();
            }
        } catch (PriceHistoryException | SyntaxError $e) {
            $this->context->buildViolation($e->getMessage())->addViolation();
        }
    }
}
