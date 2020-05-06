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


class Scanner implements ScannerInterface
{
    /**
     * @var \Symfony\Component\ExpressionLanguage\ExpressionLanguage
     */
    protected $expr;

    public function __construct(
      \App\Service\Scanner\OHLCV\ScannerExpression $expr
    )
    {
        $this->expr = $expr;
    }

    /**
     * @inheritDoc
     */
    public function scan($list, $formula)
    {
        // TODO: Implement scan() method.

    }

//    /**
//     * @inheritDoc
//     */
//    public function createList($formula)
//    {
//        //TODO: Implement createList method
//    }

//    /**
//     * @inheritDoc
//     */
//    public function storeList($list)
//    {
//        // TODO: Implement storeList method
//    }
//
    public function getExpressionInstance()
    {
        return $this->expr;
    }
}
