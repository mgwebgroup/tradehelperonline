<?php

namespace App\Repository;

use App\Entity\Study\FloatAttribute;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method StudyFloatAttribute|null find($id, $lockMode = null, $lockVersion = null)
 * @method StudyFloatAttribute|null findOneBy(array $criteria, array $orderBy = null)
 * @method StudyFloatAttribute[]    findAll()
 * @method StudyFloatAttribute[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StudyFloatAttributeRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, FloatAttribute::class);
    }

    public static function createFloatAttr($study, $name, $value)
    {
        $floatAttr = new FloatAttribute();
        $floatAttr->setStudy($study);
        $floatAttr->setAttribute($name);
        $floatAttr->setValue($value);
        $study->addFloatAttribute($floatAttr);

        return $floatAttr;
    }
}
