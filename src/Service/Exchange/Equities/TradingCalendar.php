<?php
/**
 * This file is part of the Trade Helper Online package.
 *
 * (c) 2019-2020  Alex Kay <alex110504@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace App\Service\Exchange\Equities;

use Yasumi\Yasumi;

/**
 * Class TradingCalendar
 * Removes trading holidays specific to NYSE and NASDAQ from date iterator
 * @package App\Service\Exchange\Equities
 */
class TradingCalendar extends \FilterIterator
{
    const TIMEZONE = 'America/New_York';

    /**
     * @var Yasumi\Provider\USA
     */
    protected $holidaysCalculator;

    public function __construct(
        \Iterator $iterator
    )
    {
        parent::__construct($iterator);
    }

    public function accept()
    {
        $date = $this->getInnerIterator()->current();
        $this->initCalculator((int) $date->format('Y'));

        return $this->holidaysCalculator->isWorkingDay($date);
    }

    /**
     * @param integer $year
     * @throws \ReflectionException
     */
    private function initCalculator($year)
    {
        $this->holidaysCalculator = Yasumi::create('USA', $year);

        $this->holidaysCalculator->addHoliday($this->holidaysCalculator->goodFriday($year, self::TIMEZONE, 'en_US'));
        $this->holidaysCalculator->removeHoliday('columbusDay');
        $this->holidaysCalculator->removeHoliday('veteransDay');
    }
}
