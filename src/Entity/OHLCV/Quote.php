<?php

namespace App\Entity\OHLCV;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Instrument;

/**
 * @ORM\Entity(repositoryClass="App\Repository\OHLCVQuoteRepository")
 * @ORM\Table(name="ohlcvquote")
 */
class Quote
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $open;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $high;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $low;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $close;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $volume;

    /**
     * @ORM\Column(type="dateinterval", nullable=true)
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

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Instrument", inversedBy="oHLCVQuote")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $instrument;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOpen(): ?float
    {
        return $this->open;
    }

    public function setOpen(?float $open): self
    {
        $this->open = round($open, 2);

        return $this;
    }

    public function getHigh(): ?float
    {
        return $this->high;
    }

    public function setHigh(?float $high): self
    {
        $this->high = round($high, 2);

        return $this;
    }

    public function getLow(): ?float
    {
        return $this->low;
    }

    public function setLow(float $low): self
    {
        $this->low = round($low, 2);

        return $this;
    }

    public function getClose(): ?float
    {
        return $this->close;
    }

    public function setClose(?float $close): self
    {
        $this->close = round($close, 2);

        return $this;
    }

    public function getVolume(): ?float
    {
        return $this->volume;
    }

    public function setVolume(?float $volume): self
    {
        $this->volume = $volume;

        return $this;
    }

    public function getTimeinterval(): ?\DateInterval
    {
        return $this->timeinterval;
    }

    public function setTimeinterval(?\DateInterval $timeinterval): self
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

    public function getInstrument(): ?Instrument
    {
        return $this->instrument;
    }

    public function setInstrument(Instrument $instrument): self
    {
        $this->instrument = $instrument;
        // $instrument->setOHLCVQuote($this);

        return $this;
    }
}
