<?php

namespace App\Repository;

use App\Entity\StudyArrayAttribute;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method StudyArrayAttribute|null find($id, $lockMode = null, $lockVersion = null)
 * @method StudyArrayAttribute|null findOneBy(array $criteria, array $orderBy = null)
 * @method StudyArrayAttribute[]    findAll()
 * @method StudyArrayAttribute[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StudyArrayAttributeRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, StudyArrayAttribute::class);
    }
}
