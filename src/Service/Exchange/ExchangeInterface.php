<?php
/**
 * This file is part of the Trade Helper package.
 *
 * (c) Alex Kay <alex110504@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\Exchange;

/**
 * Defines functions relevant to each trading exchange
 */
interface ExchangeInterface
{
	/**
	 * @param \DateTime $date
	 * @return bool
	 */
	public function isTradingDay($date);

	/**
	 * @param \DateTime $datetime
	 * @return bool
	 */
	public function isOpen($datetime);

	/**
	 * Queries Instruments table for which Exchange they belong to
	 * @param string $exchange name ('NYSE|NASDAQ|AMEX')
	 * @return App\Entity\Instrument[]
	 */
	public function getTradedInstruments($exchange);

	/**
	 * Looks up given symbol
	 * @param string $symbol
	 * @param string $exchange
	 * @return bool
	 */
	public function isTraded($symbol, $exchange);

	/**
	 * Iterates back one day until finds a trading day
	 * @param \DateTime $date
	 * @throws Exception if max number of iterations reached without finding a trading day
	 * @return \DateTime previous T from $date
	 */
	public function calcPreviousTradingDay($date);

}
