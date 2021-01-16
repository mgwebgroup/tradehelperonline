<?php

namespace App\Entity\Study;

use App\Entity\Watchlist;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\StudyRepository")
 */
class Study
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Watchlist", cascade={"persist"})
     */
    private $watchlists;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Study\TextAttribute", mappedBy="study", orphanRemoval=true, cascade={"persist"})
     */
    private $textAttributes;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Study\ArrayAttribute", mappedBy="study", orphanRemoval=true, cascade={"persist"})
     */
    private $arrayAttributes;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Study\JsonAttribute", mappedBy="study", orphanRemoval=true, cascade={"persist"})
     */
    private $jsonAttributes;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Study\FloatAttribute", mappedBy="study", orphanRemoval=true,
     *     cascade={"persist"})
     */
    private $floatAttributes;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $version;


    public function __construct()
    {
        $this->watchlists = new ArrayCollection();
        $this->textAttributes = new ArrayCollection();
        $this->arrayAttributes = new ArrayCollection();
        $this->jsonAttributes = new ArrayCollection();
        $this->floatAttributes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

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

    /**
     * @return Collection|Watchlist[]
     */
    public function getWatchlists(): Collection
    {
        return $this->watchlists;
    }

    public function addWatchlist(Watchlist $watchlist): self
    {
        if (!$this->watchlists->contains($watchlist)) {
            $this->watchlists[] = $watchlist;
        }

        return $this;
    }

    public function removeWatchlist(Watchlist $watchlist): self
    {
        if ($this->watchlists->contains($watchlist)) {
            $this->watchlists->removeElement($watchlist);
        }

        return $this;
    }

    /**
     * @return Collection|TextAttribute[]
     */
    public function getTextAttributes(): Collection
    {
        return $this->textAttributes;
    }

    public function addTextAttribute(TextAttribute $textAttribute): self
    {
        if (!$this->textAttributes->contains($textAttribute)) {
            $this->textAttributes[] = $textAttribute;
            $textAttribute->setStudy($this);
        }

        return $this;
    }

    public function removeTextAttribute(TextAttribute $textAttribute): self
    {
        if ($this->textAttributes->contains($textAttribute)) {
            $this->textAttributes->removeElement($textAttribute);
            // set the owning side to null (unless already changed)
            if ($textAttribute->getStudy() === $this) {
                $textAttribute->setStudy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|ArrayAttribute[]
     */
    public function getArrayAttributes(): Collection
    {
        return $this->arrayAttributes;
    }

    public function addArrayAttribute(ArrayAttribute $arrayAttribute): self
    {
        if (!$this->arrayAttributes->contains($arrayAttribute)) {
            $this->arrayAttributes[] = $arrayAttribute;
            $arrayAttribute->setStudy($this);
        }

        return $this;
    }

    public function removeArrayAttribute(ArrayAttribute $arrayAttribute): self
    {
        if ($this->arrayAttributes->contains($arrayAttribute)) {
            $this->arrayAttributes->removeElement($arrayAttribute);
            // set the owning side to null (unless already changed)
            if ($arrayAttribute->getStudy() === $this) {
                $arrayAttribute->setStudy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|JsonAttribute[]
     */
    public function getJsonAttributes(): Collection
    {
        return $this->jsonAttributes;
    }

    public function addJsonAttribute(JsonAttribute $jsonAttribute): self
    {
        if (!$this->jsonAttributes->contains($jsonAttribute)) {
            $this->jsonAttributes[] = $jsonAttribute;
            $jsonAttribute->setStudy($this);
        }

        return $this;
    }

    public function removeJsonAttribute(JsonAttribute $jsonAttribute): self
    {
        if ($this->jsonAttributes->contains($jsonAttribute)) {
            $this->jsonAttributes->removeElement($jsonAttribute);
            // set the owning side to null (unless already changed)
            if ($jsonAttribute->getStudy() === $this) {
                $jsonAttribute->setStudy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|FloatAttribute[]
     */
    public function getFloatAttributes(): Collection
    {
        return $this->floatAttributes;
    }

    public function addFloatAttribute(FloatAttribute $floatAttribute): self
    {
        if (!$this->floatAttributes->contains($floatAttribute)) {
            $this->floatAttributes[] = $floatAttribute;
            $floatAttribute->setStudy($this);
        }

        return $this;
    }

    public function removeFloatAttribute(FloatAttribute $floatAttribute): self
    {
        if ($this->floatAttributes->contains($floatAttribute)) {
            $this->floatAttributes->removeElement($floatAttribute);
            // set the owning side to null (unless already changed)
            if ($floatAttribute->getStudy() === $this) {
                $floatAttribute->setStudy(null);
            }
        }

        return $this;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(?string $version): self
    {
        $this->version = $version;

        return $this;
    }
}
