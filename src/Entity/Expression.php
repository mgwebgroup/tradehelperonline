<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ExpressionRepository")
 */
class Expression
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\NotNull
     */
    private $created_at;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updated_at;

    /**
     * @ORM\Column(type="dateinterval")
     */
    private $timeinterval;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\Length(min = 1, max = 255)
     * @Assert\Regex("/[[:alnum:]&*_ ()\-]+/i")
     */
    private $name;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * Formula written in Expression Language, i.e. '(Low(0) - Low(1))'
     * @ORM\Column(type="text")
     * @Assert\Length(max = 65535)
     */
    private $formula;

    /**
     * Usually would be only one criterion, i.e. ['>', 0]
     * @ORM\Column(type="array", nullable=true)
     */
    private $criteria = [];

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTimeinterval(): ?\DateInterval
    {
        return $this->timeinterval;
    }

    public function setTimeinterval(string $timeinterval): self
    {
        switch (strtolower($timeinterval)) {
            case 'daily':
                $this->timeinterval = new \DateInterval('P1D');
                break;
            case 'weekly':
                $this->timeinterval = new \DateInterval('P7D');
                break;
            case 'monthly':
                $this->timeinterval = new \DateInterval('P1M');
                break;
            default:
                throw new \Exception(sprintf('Unknown interval `%s`.', $timeinterval));
        }

        return $this;
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

    public function getFormula(): ?string
    {
        return $this->formula;
    }

    public function setFormula(string $formula): self
    {
        $this->formula = $formula;

        return $this;
    }

    public function getCriteria(): ?array
    {
        return $this->criteria;
    }

    public function setCriteria(?array $criteria): self
    {
        $this->criteria = $criteria;

        return $this;
    }
}
