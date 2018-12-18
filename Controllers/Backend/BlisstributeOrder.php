<?php

use Shopware\CustomModels\Blisstribute\BlisstributeOrderRepository;

/**
 * blisstribute order controller
 *
 * @author    Julian Engler
 * @copyright Copyright (c) 2016
 *
 * @since     1.0.0
 *
 * @method BlisstributeOrderRepository getRepository()
 */
class Shopware_Controllers_Backend_BlisstributeOrder extends Shopware_Controllers_Backend_Application
{
    /**
     * model class
     *
     * @var string
     */
    protected $model = 'Shopware\CustomModels\Blisstribute\BlisstributeOrder';

    /**
     * controller alias
     *
     * @var string
     */
    protected $alias = 'blisstribute_order';

    /**
     * plugin
     *
     * @var
     */
    private $plugin;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->plugin = $this->get('plugins')->Backend()->ExitBBlisstribute();
        parent::init();
    }

    /**
     * {@inheritdoc}
     */
    public function get($name)
    {
        if (version_compare(Shopware::VERSION, '4.2.0', '<') && Shopware::VERSION != '___VERSION___') {
            $name = ucfirst($name);

            /** @noinspection PhpUndefinedMethodInspection */
            return Shopware()->Bootstrap()->getResource($name);
        }

        return Shopware()->Container()->get($name);
    }

    /**
     * starts syncing of selected articles
     */
    public function syncAction()
    {
        try {
            $blisstributeOrderId = $this->Request()->getParam('id');
            $blisstributeOrder = $this->getRepository()->find($blisstributeOrderId);
            if ($blisstributeOrder === null) {
                $this->View()->assign([
                    'success' => false,
                    'error' => 'unknown blisstribute order',
                ]);

                return;
            }

            require_once __DIR__ . '/../../Components/Blisstribute/Order/Sync.php';

            /** @noinspection PhpUndefinedMethodInspection */
            $orderSync = new Shopware_Components_Blisstribute_Order_Sync($this->plugin->Config());
            $result = $orderSync->processSingleOrderSync($blisstributeOrder);

            $this->View()->assign([
                'success' => $result,
                'error' => (($result == true) ? '' : $orderSync->getLastError()),
            ]);
        } catch (Exception $ex) {
            $this->View()->assign([
                'success' => false,
                'error' => $ex->getMessage(),
            ]);
        }
    }

    /**
     * resets the order sync locks
     */
    public function resetLockAction()
    {
        $sql = 'DELETE FROM s_plugin_blisstribute_task_lock WHERE task_name LIKE :taskName';
        Shopware()->Db()->query($sql, ['taskName' => '%order_sync%']);

        $this->View()->assign([
            'success' => true,
            'message' => 'Transfer-Sperren wurden zurückgesetzt.',
        ]);
    }

    public function resetOrderSyncAction()
    {
        $blisstributeOrderId = $this->Request()->getParam('id');
        $sql = 'UPDATE s_plugin_blisstribute_orders set transfer_status = 1 WHERE id = :btOrderId';
        Shopware()->Db()->query($sql, ['btOrderId' => $blisstributeOrderId]);

        $this->View()->assign([
            'success' => true,
            'btOrderId' => $blisstributeOrderId,
        ]);
    }

    public function updateOrderSyncAction()
    {
        $blisstributeOrderId = $this->Request()->getParam('id');
        $sql = 'UPDATE s_plugin_blisstribute_orders set transfer_status = 3 WHERE id = :btOrderId';
        Shopware()->Db()->query($sql, ['btOrderId' => $blisstributeOrderId]);

        $this->View()->assign([
            'success' => true,
            'btOrderId' => $blisstributeOrderId,
        ]);
    }

    /**
     * checks if the blisstribute plugin is up to date
     */
    public function checkPluginUpToDateAction()
    {
        $currentVersion = $this->plugin->getVersion();
        $latestVersion = json_decode(file_get_contents('https://raw.githubusercontent.com/ccarnivore/sw_blisstribute/master/plugin.json'), true)['currentVersion'];

        $this->View()->assign([
            'success' => true,
            'currentVersion' => $currentVersion,
            'latestVersion' => $latestVersion,
            'outdated' => version_compare($currentVersion, $latestVersion) === -1,
            'downloadLink' => 'https://github.com/ccarnivore/sw_blisstribute/releases',
        ]);
    }

    /**
     * Check for invalid order transfers and return warning
     */
    public function getInvalidOrderTransfersAction()
    {
        $config = $this->plugin->Config();
        $invalidTransfers = [];

        if ($config->get('blisstribute-show-sync-widget')) {
            $sqlSelectInvalidOrderTransfers = 'SELECT s_order.ordernumber AS ordernumber FROM s_plugin_blisstribute_orders 
                                           LEFT JOIN s_order ON s_order.id = s_order_id
                                           WHERE transfer_status in (10,11,20,21)';

            $invalidTransfers = Shopware()->Db()->fetchAll($sqlSelectInvalidOrderTransfers);
        }

        $this->View()->assign([
            'success' => true,
            'data' => $invalidTransfers,
            'count' => count($invalidTransfers),
        ]);
    }

    public function getOrderByNumberAction()
    {
        $orderNumber = $this->Request()->getParam('orderNumber');

        $order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneBy([
            'number' => $orderNumber,
        ]);

        $this->View()->assign([
            'success' => true,
            'data' => $order->getId(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getListQuery()
    {
        $builder = parent::getListQuery();

        $builder->innerJoin('blisstribute_order.order', 'o');
        $builder->addSelect(['o']);
        $builder->addOrderBy('o.id', 'DESC');

        // searching
        $filters = $this->Request()->getParam('filter');

        if (!is_null($filters)) {
            foreach ($filters as $filter) {
                if ($filter['property'] == 'search') {
                    $value = $filter['value'];

                    $search = '%' . $value . '%';

                    if (!is_null($value)) {
                        $builder->andWhere('o.number LIKE :search');

                        $builder->setParameter('search', $search);
                    }
                }
            }
        }

        return $builder;
    }
}
