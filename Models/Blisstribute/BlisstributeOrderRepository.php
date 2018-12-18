<?php

namespace Shopware\CustomModels\Blisstribute;

use Doctrine\ORM\NonUniqueResultException;
use Shopware\Components\Model\ModelRepository;
use Shopware\Models\Order\Order;

/**
 * blisstribute order repository
 *
 * @author    Julian Engler
 * @copyright Copyright (c) 2016
 *
 * @since     1.0.0
 *
 * @method BlisstributeOrder find($id, $lockMode = null, $lockVersion = null)
 */
class BlisstributeOrderRepository extends ModelRepository
{
    /**
     * max tries for cron jbos
     *
     * @var int
     */
    const MAX_SYNC_TRIES = 5;

    /**
     * page limit for export
     *
     * @var int
     */
    const PAGE_LIMIT = 150;

    /**
     * get blisstribute order mapping by order number
     *
     * @param string $orderNumber
     *
     * @throws NonUniqueResultException
     *
     * @return BlisstributeOrder
     */
    public function findByOrderNumber($orderNumber)
    {
        return $this->createQueryBuilder('bo')
            ->where('bo.order = :order')
            ->setParameter('order.ordernumber', $orderNumber)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * get blisstribute order mapping by order
     *
     * @param Order $order
     *
     * @throws NonUniqueResultException
     *
     * @return BlisstributeOrder
     */
    public function findByOrder(Order $order)
    {
        return $this->createQueryBuilder('bo')
            ->where('bo.order = :order')
            ->setParameter('order', $order->getId())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * get all orders which are not transferred
     *
     * @param \DateTime $exportDate
     *
     * @return array
     */
    public function findTransferableOrders(\DateTime $exportDate)
    {
        $blisstributeOrderCollection = $this->createQueryBuilder('bo')
            ->where('bo.status IN (:statusNew, :statusValidationError, :statusTransferError)')
            ->andWhere('bo.tries < :tries')
            ->andWhere('bo.lastCronAt <= :lastCronAt')
            ->setParameters([
                'statusNew' => BlisstributeOrder::EXPORT_STATUS_NONE,
                'statusValidationError' => BlisstributeOrder::EXPORT_STATUS_VALIDATION_ERROR,
                'statusTransferError' => BlisstributeOrder::EXPORT_STATUS_TRANSFER_ERROR,
                'tries' => static::MAX_SYNC_TRIES,
                'lastCronAt' => $exportDate->format('Y-m-d H:i:s'),
            ])
            ->setMaxResults(static::PAGE_LIMIT)
            ->getQuery()
            ->getResult();

        return $blisstributeOrderCollection;
    }
}
