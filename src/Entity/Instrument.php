<?php
/**
 * A note on relations between instrument, OHLCV\History and OHLCV\Quote:
 *  OHLCV\History is supposed to have a lot of data. Having it as a collection
 *  property on instrument is going to affect performance. Having collections
 *  has its benefits in ease of access. Therefore, only OHLCV\Quote[] are
 *  accessible via collection prop on instruments, because only one quote 
 *  per instrument is supposed to be stored.
 */

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\OHLCV\History;
use App\Entity\OHLCV\Quote;

/**
 * @ORM\Table(name="instruments")
 * @ORM\Entity(repositoryClass="App\Repository\InstrumentRepository")
 */
class Instrument
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=21)
     */
    private $symbol;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $description;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\OHLCV\History", mappedBy="instrument", orphanRemoval=true, cascade={"detach"})
     */
    private $oHLCVHistories;

    /**
     * @ORM\Column(type="string", length=80, nullable=true)
     */
    private $exchange;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\OHLCV\Quote", mappedBy="instrument", cascade={"persist", "remove",
     *     "refresh"})
     */
    private $oHLCVQuote;

    public function __construct()
    {
        $this->oHLCVHistories = new ArrayCollection();
        $this->oHLCVQuotes = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSymbol(): ?string
    {
        return $this->symbol;
    }

    public function setSymbol(string $symbol): self
    {
        $this->symbol = $symbol;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection|OHLCV\History[]
     */
    public function getOHLCVHistories(): Collection
    {
        return $this->oHLCVHistories;
    }

    public function addOHLCVHistory(History $oHLCVHistory): self
    {
        if (!$this->oHLCVHistories->contains($oHLCVHistory)) {
            $this->oHLCVHistories[] = $oHLCVHistory;
            $oHLCVHistory->setSymbol($this);
        }

        return $this;
    }

    public function removeOHLCVHistory(History $oHLCVHistory): self
    {
        if ($this->oHLCVHistories->contains($oHLCVHistory)) {
            $this->oHLCVHistories->removeElement($oHLCVHistory);
            // set the owning side to null (unless already changed)
            if ($oHLCVHistory->getSymbol() === $this) {
                $oHLCVHistory->setSymbol(null);
            }
        }

        return $this;
    }

    public function getExchange(): ?string
    {
        return $this->exchange;
    }

    public function setExchange(?string $exchange): self
    {
        $this->exchange = $exchange;

        return $this;
    }

    public function getOHLCVQuote(): ?Quote
    {
        return $this->oHLCVQuote;
    }

    public function setOHLCVQuote(Quote $oHLCVQuote): self
    {
        $this->oHLCVQuote = $oHLCVQuote;

        // set the owning side of the relation if necessary
        if ($this !== $oHLCVQuote->getInstrument()) {
            $oHLCVQuote->setInstrument($this);
        }

        return $this;
    }

    public function unsetOHLCVQuote()
    {
        $this->oHLCVQuote = null;
    }
}
