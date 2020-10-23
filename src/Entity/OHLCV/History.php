<?php

namespace App\Entity\OHLCV;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Instrument;
use App\Exception\PriceHistoryException;

/**
 * @ORM\Entity(repositoryClass="App\Repository\OHLCVHistoryRepository")
 * @ORM\Table(name="ohlcvhistory")
 */
class History
{
    const INTERVAL_DAILY = 'daily';
    const INTERVAL_WEEKLY = 'weekly';
    const INTERVAL_MONTHLY = 'monthly';
    const INTERVAL_QUARTERLY = 'quarterly';
    const INTERVAL_YEARLY = 'yearly';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="float")
     */
    private $open;

    /**
     * @ORM\Column(type="float")
     */
    private $high;

    /**
     * @ORM\Column(type="float")
     */
    private $low;

    /**
     * @ORM\Column(type="float")
     */
    private $close;

    /**
     * @ORM\Column(type="bigint", nullable=true)
     */
    private $volume;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Instrument", inversedBy="oHLCVHistories", cascade={"detach"})
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $instrument;

    /**
     * @ORM\Column(type="dateinterval")
     */
    private $timeinterval;

    /**
     * @ORM\Column(type="datetime")
     */
    private $timestamp;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $provider;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOpen(): ?float
    {
        return $this->open;
    }

    public function setOpen(float $open): self
    {
        $this->open = round($open,2);

        return $this;
    }

    public function getHigh(): ?float
    {
        return $this->high;
    }

    public function setHigh(float $high): self
    {
        $this->high = round($high,2);

        return $this;
    }

    public function getLow(): ?float
    {
        return $this->low;
    }

    public function setLow(float $low): self
    {
        $this->low = round($low,2);

        return $this;
    }

    public function getClose(): ?float
    {
        return $this->close;
    }

    public function setClose(float $close): self
    {
        $this->close = round($close, 2);

        return $this;
    }

    public function getVolume(): ?int
    {
        return $this->volume;
    }

    public function setVolume(?int $volume): self
    {
        $this->volume = $volume;

        return $this;
    }

    public function getInstrument(): ?Instrument
    {
        return $this->instrument;
    }

    public function setInstrument(?Instrument $instrument): self
    {
        $this->instrument = $instrument;

        return $this;
    }

    public function getTimeinterval(): ?\DateInterval
    {
        return $this->timeinterval;
    }

    public function setTimeinterval(\DateInterval $timeinterval): self
    {
        $this->timeinterval = $timeinterval;

        return $this;
    }

    public function getTimestamp(): ?\DateTimeInterface
    {
        return $this->timestamp;
    }

    public function setTimestamp(\DateTimeInterface $timestamp): self
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function setProvider(?string $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * Standardizes generation of supported time interval objects
     * @param string $interval
     * @return \DateInterval $interval
     * @throws PriceHistoryException
     */
    public static function getOHLCVInterval(String $interval): \DateInterval
    {
        switch($interval) {
            case self::INTERVAL_DAILY:
                return new \DateInterval('P1D');
            case self::INTERVAL_WEEKLY:
                return new \DateInterval('P7D');
            case self::INTERVAL_MONTHLY:
                return new \DateInterval('P1M');
            case self::INTERVAL_QUARTERLY:
                return new \DateInterval('P3M');
            case self::INTERVAL_YEARLY:
                return new \DateInterval('P1Y');
            default:
                throw new PriceHistoryException('Unsupported time interval');
        }
    }

    /**
     * Expands candle for superlative time frame given a daily candle
     * @param History $dailyItem
     */
    public function expandCandle($dailyItem)
    {
        $this->setHigh(max($this->getHigh(), $dailyItem->getHigh()));
        $this->setLow(min($this->getLow(), $dailyItem->getLow()));
        $this->setVolume($this->getVolume() + $dailyItem->getVolume());
        $this->setClose($dailyItem->getClose());
    }
}
