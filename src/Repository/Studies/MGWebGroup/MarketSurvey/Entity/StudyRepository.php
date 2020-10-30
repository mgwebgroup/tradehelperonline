<?php

namespace App\Repository\Studies\MGWebGroup\MarketSurvey\Entity;

use App\Studies\MGWebGroup\MarketSurvey\Entity\Study;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Repository contains methods specifically for each study element
 * @method Study|null find($id, $lockMode = null, $lockVersion = null)
 * @method Study|null findOneBy(array $criteria, array $orderBy = null)
 * @method Study[]    findAll()
 * @method Study[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StudyRepository extends ServiceEntityRepository
{
    public function __construct(
      RegistryInterface $registry
    )
    {
        parent::__construct($registry, Study::class);
    }
}
