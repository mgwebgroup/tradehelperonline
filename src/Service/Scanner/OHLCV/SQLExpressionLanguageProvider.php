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


use App\Exception\PriceHistoryException;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Component\ExpressionLanguage\SyntaxError;

class SQLExpressionLanguageProvider implements ExpressionFunctionProviderInterface
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    public function __construct(\Doctrine\ORM\EntityManager $em)
    {
        $this->em = $em;
    }

    public function getFunctions()
    {
        return [
          new ExpressionFunction(
            'Close', function ($offset) {
                return "select close from ohlcvhistory where timestamp = datesub($offset) and instrument='$instrument'";
          }, function($arguments, $offset) {
            if (isset($arguments['instrument']) && $arguments['instrument'] instanceof \App\Entity\Instrument) {
                $instrument = $arguments['instrument'];
                $instrumentId = $instrument->getId();
                if ($instrumentId) {
                    $dql = sprintf('select h.close from \App\Entity\OHLCVHistory h join h.instrument i where i.id =  
                        %s and h.timestamp = \'%s\'', $instrumentId, '2011-09-19 00:00:00');
                    $query = $this->em->createQuery($dql);
                    $result = $query->getSingleResult();
                } else {
                    thro new PriceHistoryException('Could not find instrument');
                }

                if (isset($result['close'])) {
                    return $result['close'];
                } else {
                    return new PriceHistoryException('Could not find value');
                }
            } else {
                throw new SyntaxError('Need to pass instrument object as part of the data part');
            }
          }),
        ];
    }
}