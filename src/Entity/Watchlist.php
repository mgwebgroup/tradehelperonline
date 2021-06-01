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
     * @ORM\ManyToMany(targetEntity="App\Entity\Expression")
     */
    private $expressions;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created_at;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updated_at;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $calculated_formulas;


    public function __construct()
    {
        $this->instruments = new ArrayCollection();
        $this->expressions = new ArrayCollection();
        $this->calculated_formulas = [];
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
    public function getInstruments(): Collection
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
     * @return Collection|expression[]
     */
    public function getExpressions(): Collection
    {
        return $this->expressions;
    }

    public function addExpression(Expression $expression): self
    {
        if (!$this->expressions->contains($expression)) {
            $this->expressions[] = $expression;
        }

        return $this;
    }

    public function removeExpression(Expression $expression): self
    {
        if ($this->expressions->contains($expression)) {
            $this->expressions->removeElement($expression);
        }

        return $this;
    }

    /**
     * Calculates associated expressions for associated instruments. Result is stored in $calculated_formulas property
     * @param Calculator $calculator
     * @param \DateTime | null $date
     * @return $this
     */
    public function update(Calculator $calculator, $date = null)
    {
        foreach ($this->instruments as $instrument) {
            $value = [];
            foreach ($this->expressions as $expression) {
                $data = [
                  'instrument' => $instrument,
                  'interval' => $expression->getTimeInterval(),
                ];
                if ($date) {
                    $data['date'] = $date;
                }

                $value[$expression->getName()] = $calculator->evaluate($expression->getFormula(), $data);
            }
            $this->calculated_formulas[$instrument->getSymbol()] = $value;
        }

        return $this;
    }

    /**
     * Sorts symbols in $calculated_formulas property according to values in formulas. Can take up to 2 columns to sort by.
     * @param String $columnName1
     * @param Integer $order1 SORT_ASC | SORT_DESC
     * @param String | null $columnName2
     * @param Integer $order2 SORT_ASC | SORT_DESC
     */
    public function sortValuesBy($columnName1, $order1, $columnName2 = null, $order2 = SORT_ASC)
    {
        $column1 = array_column($this->calculated_formulas, $columnName1);

        if ($columnName2) {
            $column2 = array_column($this->calculated_formulas, $columnName1);
            array_multisort($column1, $order1, $column2, $order2, $this->calculated_formulas);
        } else {
            array_multisort($column1, $order1, $this->calculated_formulas);
        }
    }

    /**
     * @return array
     */
    public function getCalculatedFormulas(): array
    {
        return (null === $this->calculated_formulas) ? [] : $this->calculated_formulas;
    }
}
