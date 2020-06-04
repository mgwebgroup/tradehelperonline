<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Service\ExpressionHandler\OHLCV\Calculator as Calculator;

/**
 * @ORM\Entity(repositoryClass="App\Repository\WatchlistRepository")
 */
class Watchlist
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $description;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Instrument")
     */
    private $instruments;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Formula")
     */
    private $formulas;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created_at;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updated_at;

    /**
     * @var array
     */
    private $values;

    public function __construct()
    {
        $this->instruments = new ArrayCollection();
        $this->formulas = new ArrayCollection();
        $this->values = [];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
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
     * @return Collection|instruments[]
     */
    public function getinstruments(): Collection
    {
        return $this->instruments;
    }

    public function addInstrument(Instrument $instrument): self
    {
        if (!$this->instruments->contains($instrument)) {
            $this->instruments[] = $instrument;
        }

        return $this;
    }

    public function removeInstrument(Instrument $instrument): self
    {
        if ($this->instruments->contains($instrument)) {
            $this->instruments->removeElement($instrument);
        }

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeInterface $updated_at): self
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    /**
     * @return Collection|Formula[]
     */
    public function getFormulas(): Collection
    {
        return $this->formulas;
    }

    public function addFormula(Formula $formula): self
    {
        if (!$this->formulas->contains($formula)) {
            $this->formulas[] = $formula;
        }

        return $this;
    }

    public function removeFormula(Formula $formula): self
    {
        if ($this->formulas->contains($formula)) {
            $this->formulas->removeElement($formula);
        }

        return $this;
    }

    /**
     * Calculates associated formulas for associated instruments. Result is stored in unmapped $values property
     * @param Calculator $calculator
     * @param \DateTime | null $date
     */
    public function update(Calculator $calculator, $date = null)
    {
        foreach ($this->instruments as $instrument) {
            $value = [];
            foreach ($this->formulas as $formula) {
                $data = [
                  'instrument' => $instrument,
                  'interval' => $formula->getTimeInterval(),
                ];
                if ($date) {
                    $data['date'] = $date;
                }

                $value[$formula->getName()] = $calculator->evaluate($formula->getContent(), $data);
            }
            $this->values[$instrument->getSymbol()] = $value;
        }
    }

    public function getValues()
    {
        return $this->values;
    }
}
