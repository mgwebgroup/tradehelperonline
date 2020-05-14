<?php
/**
 * This file is part of the Trade Helper Online package.
 *
 * (c) 2019-2020  Alex Kay <alex110504@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\Scanner;

interface ScannerInterface
{
    /**
     * Scans a given list of instruments using a formula
     * Example:
     * $expression = '(Close(0)+Close(1)) / 2';
     * $comparison = ['=', 1.02];  // second item in the comparison array must be float or 0
     * $interval = new \DateInterval('P1D');
     * $results = scan($list, $expression, $comparison, $interval)
     * @param \App\Entity\Instruments[] $list
     * @param string $expression
     * @param array $comparison
     * @param \DateInterval $interval
     * @param \DateTime $date
     * @return \Iterable $result
     */
    public function scan($list, $expression, $comparison, $interval, $date);

}