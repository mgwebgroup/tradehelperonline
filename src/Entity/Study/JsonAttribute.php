<?php

namespace App\Entity\Study;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\StudyJsonAttributeRepository")
 * @ORM\Table(name="study_attribute_json")
 */
class JsonAttribute
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Study\Study", inversedBy="jsonAttributes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $study;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $attribute;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $_value = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStudy(): ?Study
    {
        return $this->study;
    }

    public function setStudy(?Study $study): self
    {
        $this->study = $study;

        return $this;
    }

    public function getAttribute(): ?string
    {
        return $this->attribute;
    }

    public function setAttribute(string $attribute): self
    {
        $this->attribute = $attribute;

        return $this;
    }

    public function getValue(): ?array
    {
        return $this->_value;
    }

    public function setValue(?array $_value): self
    {
        $this->_value = $_value;

        return $this;
    }
}
