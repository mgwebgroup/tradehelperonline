<?php
/**
 * This file is part of the Trade Helper Online package.
 *
 * (c) 2019-2020  Alex Kay <alex110504@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\Scanner\OHLCV;

use App\Service\Scanner\ScannerInterface;
use Doctrine\Common\Collections\Expr\ClosureExpressionVisitor;
use Doctrine\Common\Collections\Expr\Comparison;
use App\Service\Scanner\ExpressionValue;

/**
 * Class Scanner
 * The class relies on Doctrine's Doctrine\Common\Collections\Expr\ClosureExpressionVisitor and
 * Doctrine\Common\Collections\Expr\Comparison. I did not want to write my own comparison apparatus, and classes
 * provided by Doctrine work just fine, For the list of available comparison operators see Doctrine\Common\Collections\Expr
 * class.
 * @package App\Service\Scanner\OHLCV
 */
class Scanner implements ScannerInterface
{
    /**
     * @var \App\Service\Formula\OHLCV\Formula
     */
    protected $expr;


    public function __construct(
      \App\Service\Formula\OHLCV\Formula $expr
    )
    {
        $this->expr = $expr;
    }

    /**
     * @inheritDoc
     */
    public function scan($list, $expression, $comparison, $interval, $date = null)
    {
        $evaluated = [];
        foreach ($list as $instrument) {
            $data = ['instrument' => $instrument, 'interval' => $interval,];
            if ($date instanceof \DateTime) {
                $data['date'] = $date;
            }
            $expressionValue = new \StdClass();
            $expressionValue->value = $this->getExpressionInstance()->evaluate($expression, $data);
            $expressionValue->instrument = $instrument;
            $evaluated[] = $expressionValue;
        }

        $condition = new Comparison('value', ...$comparison);
        $visitor  = new ClosureExpressionVisitor();
        $filter   = $visitor->dispatch($condition);
        $filteredIterator = new \CallbackFilterIterator(new \ArrayIterator($evaluated), $filter);

        $result = [];
        foreach ($filteredIterator as $item) {
            $result[] = $item->instrument;
        }
        // the following will not work, as array_map needs Array for the second argument but not \Iterable
//        return array_map(function ($object) { return $object->getInstrument(); }, $filteredIterator);

        return $result;
    }

    public function getExpressionInstance()
    {
        return $this->expr;
    }
}
