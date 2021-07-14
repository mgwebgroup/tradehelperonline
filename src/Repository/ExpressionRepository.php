<?php

namespace App\Repository;

use App\Entity\Expression;
use App\Exception\ExpressionException;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Expression|null find($id, $lockMode = null, $lockVersion = null)
 * @method Expression|null findOneBy(array $criteria, array $orderBy = null)
 * @method Expression[]    findAll()
 * @method Expression[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExpressionRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Expression::class);
    }

    /**
     * @param string $interval daily | weekly | monthly
     * @param string $name
     * @param string $formula i.e: Close(0)-Low(1)
     * @param array $criterion i.e.: ['=', true]
     * @param string|null $description
     */
    public static function createExpression(
        string $interval,
        string $name,
        string $formula,
        array $criterion,
        string $description = null
    ): Expression {
        $expression = new Expression();
        $expression->setCreatedAt(new DateTime())
          ->setTimeinterval($interval)
          ->setName($name)
          ->setFormula($formula)
          ->setCriteria($criterion)
          ->setDescription($description)
        ;
        return $expression;
    }

    /**
     * Finds several expressions using list of expression names
     * @param $exprList String[]
     * @return array Expression[]
     * @throws ExpressionException
     */
    public function findExpressions($exprList)
    {
        $expressions = [];
        foreach ($exprList as $name) {
            $expression = $this->findOneBy(['name' => $name]);
            if (!$expression) {
                throw new ExpressionException(sprintf('Could not find expression named %s', $name));
                break;
            }
            $expressions[] = $expression;
        }

        return $expressions;
    }
}
