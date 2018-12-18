<?php

namespace Shopware\CustomModels\Blisstribute;

use Doctrine\ORM\NonUniqueResultException;
use Shopware\Components\Model\ModelRepository;

/**
 * blisstribute shipment mapping entity repository
 *
 * @author    Julian Engler
 * @copyright Copyright (c) 2016
 *
 * @since     1.0.0
 */
class BlisstributeShipmentRepository extends ModelRepository
{
    /**
     * get shipment mapping by shopware shipment id
     *
     * @param int $shipmentId
     *
     * @throws NonUniqueResultException
     *
     * @return null|BlisstributeShipment
     */
    public function findOneByShipment($shipmentId)
    {
        return $this->createQueryBuilder('bs')
            ->where('bs.shipment = :shipmentId')
            ->setParameter('shipmentId', $shipmentId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
