<?php

namespace Miaoxing\Payment;

use Miaoxing\Address\Service\Address;
use Miaoxing\Order\Service\Order;

class PaymentPlugin extends \Miaoxing\Plugin\BasePlugin
{
    protected $name = '支付系统';

    protected $version = '1.0.0';

    protected $description = '提供基本的支付功能和基础类库';

    protected $id = 14;

    public function onAdminNavGetNavs(&$navs, &$categories, &$subCategories)
    {
        $navs[] = [
            'parentId' => 'orders-service',
            'url' => 'admin/payments',
            'name' => '支付接口管理',
        ];
    }

    public function onPostOrdersNewPayment(Order $order)
    {
        if (!wei()->setting('payments.balance')) {
            return;
        }

        if (wei()->curUser['money'] <= 0) {
            return;
        }

        $this->view->display('@payment/payments/postOrdersNewPayment.php', get_defined_vars());
    }

    /**
     * @param Order $order
     * @param Address|null $address
     * @param array $data
     * @return array|void
     */
    public function onPreOrderCreate(Order $order, Address $address = null, $data)
    {
        $balanceAmount = (float) $data['balanceAmount'];
        if (!$balanceAmount) {
            return;
        }

        if ($balanceAmount < 0) {
            return ['code' => -15001, 'message' => '使用金额不能小于0'];
        }

        if ($balanceAmount > wei()->curUser['money']) {
            return ['code' => -15002, 'message' => '使用金额不能大于您当前的余额'];
        }

        $order['balanceAmount'] = $balanceAmount;
        $order->setAmountRule('balance', [
            'name' => '余额支付',
            'amountOff' => $balanceAmount,
        ]);
    }

    public function onPostOrderCreate(Order $order)
    {
        if (isset($order['balanceAmount']) && (float) $order['balanceAmount']) {
            wei()->transaction->pay(-$order['balanceAmount'], [
                'recordId' => $order['id'],
            ], $order->getUser());
        }
    }

    /**
     * 订单详情,显示金额
     *
     * @param Order $order
     */
    public function onOrdersShowAmount(Order $order)
    {
        if (isset($order['balanceAmount']) && (float) $order['balanceAmount']) {
            $this->view->display('@payment/payments/ordersShowAmount.php', get_defined_vars());
        }
    }

    /**
     * 如果是超时取消订单,退还余额支付的金额
     *
     * 如果是申请退款的,走退款渠道返还
     *
     * @param Order $order
     * @param string $source
     */
    public function onPostOrderCancel(Order $order, $source)
    {
        if ($source == 'timeout' && (float) $order['balanceAmount']) {
            wei()->transaction->refund($order['balanceAmount'], [
                'recordId' => $order['id'],
                'note' => '订单超时取消,返还支付余额',
            ], $order->getUser());
        }
    }
}
