<?php

namespace App\Studies\MGWebGroup\MarketSurvey\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Studies\MGWebGroup\MarketSurvey\Entity\StudyRepository")
 * @ORM\Table(name="market_survey_study")
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
     * Contains saved scans with the market score
     * @ORM\Column(type="json", nullable=true)
     */
    private $market_score = [];

    /**
     * @ORM\Column(type="datetime")
     */
    private $timestamp;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMarketScore(): ?array
    {
        return $this->market_score;
    }

    public function setMarketScore(?array $market_score): self
    {
        $this->market_score = $market_score;

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


}
