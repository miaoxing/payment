<?php

namespace MiaoxingTest\Payment;

class PluginTest extends \Miaoxing\Plugin\Test\BaseTestCase
{
    protected $sku;

    public static function setUpBeforeClass()
    {
        wei()->setting('payments.balance', true);
    }

    public static function tearDownAfterClass()
    {
        wei()->setting('payments.balance', false);
    }

    public function testOnPostOrdersNewPayment()
    {
        $user = wei()->user()->save([
            'money' => '99.99',
        ]);

        wei()->curUser->loginById($user['id']);

        $order = wei()->order();
        $ret = $order->createFromSkus([[
            'skuId' => $this->getSku()['id'],
            'quantity' => 1,
        ]], [
            'payType' => 'test',
        ]);

        $this->assertSame(1, $ret['code'], $ret['message']);

        $this->expectOutputRegex('/余额99.99元/');

        // TODO 自动载入
        wei()->view->assign('block', wei()->block);
        wei()->view->assign('user', $user);

        wei()->event->trigger('postOrdersNewPayment', [$order]);
    }

    public function testOnPreOrderCreateWithNegativeBalanceAmount()
    {
        $order = wei()->order();
        $address = wei()->address();
        $data = [
            'balanceAmount' => '-2.22',
        ];

        $ret = wei()->event->until('preOrderCreate', [$order, $address, $data]);

        $this->assertSame(['code' => -15001, 'message' => '使用金额不能小于0'], $ret);
    }

    public function testOnPreOrderCreateMoneyWithOverBalanceAmount()
    {
        $user = wei()->user()->save([
            'money' => '1.99',
        ]);
        wei()->curUser->loginById($user['id']);

        $order = wei()->order();
        $address = wei()->address();
        $data = [
            'balanceAmount' => '2.22',
        ];

        $ret = wei()->event->until('preOrderCreate', [$order, $address, $data]);

        $this->assertSame(['code' => -15002, 'message' => '使用金额不能大于您当前的余额'], $ret);
    }

    public function testOnPreOrderCreate()
    {
        $user = wei()->user()->save([
            'money' => '1.99',
        ]);
        wei()->curUser->loginById($user['id']);

        $order = wei()->order();
        $address = wei()->address();
        $data = [
            'balanceAmount' => '1.22',
        ];

        $ret = wei()->event->until('preOrderCreate', [$order, $address, $data]);

        $this->assertNull($ret);

        $this->assertEquals('1.22', $order['balanceAmount']);
        $this->assertEquals(['balance' => ['name' => '余额支付', 'amountOff' => '1.22']], $order->getAmountRules());
    }

    public function testOnPostOrderCreate()
    {
        $user = wei()->user()->save([
            'money' => '1.99',
        ]);
        wei()->curUser->loginById($user['id']);

        $order = wei()->order();
        $ret = $order->createFromSkus([[
            'skuId' => $this->getSku()['id'],
            'quantity' => 1,
        ]], [
            'balanceAmount' => '1.22',
            'payType' => 'test',
        ]);

        $this->assertSame(1, $ret['code'], $ret['message']);

        $ret = wei()->event->until('postOrderCreate', [$order]);

        $this->assertNull($ret);

        $transaction = wei()->transaction()->find(['recordId' => $order['id']]);

        $this->assertNotNull($transaction);

        $this->assertEquals('-1.22', $transaction['amount']);
    }

    public function testOnOrdersShowAmount()
    {
        $order = wei()->order()->fromArray([
            'balanceAmount' => '1.22',
        ]);

        $this->expectOutputRegex('/余额支付/');

        $this->expectOutputRegex('/￥1.22/');

        wei()->event->trigger('ordersShowAmount', [$order]);
    }

    protected function getSku()
    {
        if (!$this->sku) {
            $product = wei()->product();
            $ret = $product->create([
                'name' => '用余额支付的商品',
                'price' => '100',
                'quantity' => 10,
                'images' => [
                    '/assets/images/car1.png',
                ],
            ]);
            $this->assertRetSuc($ret);
            $this->sku = $product->getFirstSku();
        }

        return $this->sku;
    }
}
