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
     * Scans a given list of instruments using a formula while doing direct reads in db
     * @param \Doctrine\Common\Collections\Collection $list \App\Entity\Instruments[]
     * @param \Symfony\Component\ExpressionLanguage $expression
     * @return \Iterable $result
     */
    public function scan($list, $expression);

//    /**
//     * Creates a list of instruments
//     * @param \Symfony\Component\ExpressionLanguage $formula
//     * @return \Doctrine\Common\Collections $list \App\Entity\Instruments[]
//     */
//    public function createList($formula);

//    /**
//     * This func should be contained in Instruments List repository
//     * Stores list of instruments in database
//     * @param \Doctrine\Common\Collections $list
//     * @return bool
//     */
//    public function storeList($list);
}