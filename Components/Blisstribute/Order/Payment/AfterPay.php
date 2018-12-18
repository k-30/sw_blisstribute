<?php

require_once __DIR__ . '/AbstractExternalPayment.php';

/**
 * afterpay payment implementation
 *
 * @copyright Copyright (c) 2016
 *
 * @since     1.0.0
 */
class Shopware_Components_Blisstribute_Order_Payment_AfterPay extends Shopware_Components_Blisstribute_Order_Payment_AbstractExternalPayment
{
    /**
     * {@inheritdoc}
     */
    protected $code = 'afterPay';
}
