<?php

namespace App\Repository;

use App\Entity\Study\JsonAttribute;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method StudyJsonAttribute|null find($id, $lockMode = null, $lockVersion = null)
 * @method StudyJsonAttribute|null findOneBy(array $criteria, array $orderBy = null)
 * @method StudyJsonAttribute[]    findAll()
 * @method StudyJsonAttribute[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StudyJsonAttributeRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, JsonAttribute::class);
    }
}
