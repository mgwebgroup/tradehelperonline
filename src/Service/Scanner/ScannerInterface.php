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
     * @param \Doctrine\Common\Collections\Collection $list
     * @param $criteria
     * @return mixed
     */
    public function scan($list, $criteria);
}