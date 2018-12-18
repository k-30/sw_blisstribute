<?php

namespace Shopware\CustomModels\Blisstribute;

use Shopware\Components\Model\ModelRepository;

/**
 * database repository for article type entity
 *
 * @author    Julian Engler
 * @copyright Copyright (c) 2016
 *
 * @since     1.0.0
 */
class BlisstributeArticleTypeRepository extends ModelRepository
{
    /**
     * get article type for filter
     *
     * @param int $filterId
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return null|BlisstributeArticleType
     */
    public function fetchByFilterType($filterId)
    {
        return $this->createQueryBuilder('at')
            ->where('at.filter = :filterId')
            ->setParameter('filterId', $filterId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
