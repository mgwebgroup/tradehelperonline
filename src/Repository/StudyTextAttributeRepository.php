<?php

namespace App\Repository;

use App\Entity\Study\TextAttribute;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method StudyTextAttribute|null find($id, $lockMode = null, $lockVersion = null)
 * @method StudyTextAttribute|null findOneBy(array $criteria, array $orderBy = null)
 * @method StudyTextAttribute[]    findAll()
 * @method StudyTextAttribute[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StudyTextAttributeRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, TextAttribute::class);
    }
}
