<?php

namespace App\Repository;

use App\Entity\Study\ArrayAttribute;
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
        parent::__construct($registry, ArrayAttribute::class);
    }

    public static function createArrayAttr($study, $name, $value)
    {
        $arrayAttr = new ArrayAttribute();
        $arrayAttr->setStudy($study);
        $arrayAttr->setAttribute($name);
        $arrayAttr->setValue($value);
        $study->addArrayAttribute($arrayAttr);

        return $arrayAttr;
    }
}
