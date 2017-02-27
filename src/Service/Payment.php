<?php

namespace Miaoxing\Payment\Service;

use plugins\mall\services\Order;

class Payment extends \miaoxing\plugin\BaseModel
{
    protected $data = [
        'sort' => 50,
        'enable' => 1,
        'attrs' => [],
    ];

    protected $types = [
        'wechatPayV3' => [
            'id' => 'wechatPayV3',
            'type' => 'wechatPayV3',
            'name' => '微信支付',
            'displayName' => '微信支付V3版(最新)',
            'image' => '/assets/images/payments/v2/wechat.png',
        ],
        'weChatPay' => [
            'id' => 'weChatPay',
            'type' => 'weChatPay',
            'name' => '微信支付',
            'displayName' => '微信支付V2版',
            'image' => '/assets/images/payments/v2/wechat.png',
        ],
        // wechat pay cross country
        'wechatPayCc' => [
            'id' => 'wechatPayCc',
            'type' => 'wechatPayCc',
            'name' => '微信跨境支付',
            'displayName' => '微信跨境支付',
            'image' => '/assets/images/payments/v2/wechat.png',
        ],
        'alipay' => [
            'id' => 'alipay',
            'type' => 'alipay',
            'name' => '支付宝',
            'displayName' => '支付宝',
            'image' => '/assets/images/payments/v2/alipay.png',
        ],
        'tenpay' => [
            'id' => 'tenpay',
            'type' => 'tenpay',
            'name' => '财付通',
            'displayName' => '财付通',
            'image' => '/assets/images/payments/v2/tenpay.png',
        ],
        'cashOnDelivery' => [
            'id' => 'cashOnDelivery',
            'type' => 'cashOnDelivery',
            'name' => '货到付款',
            'displayName' => '货到付款',
            'image' => '/assets/images/payments/cashOnDelivery.png',
        ],
        // 用于只在该平台预订,但是在线下或其他渠道交易的订单
        // TODO 正常使用后替代测试支付
        'external' => [
            'id' => 'external',
            'type' => 'external',
            'name' => '外部支付',
            'displayName' => '外部支付',
            'image' => '/assets/images/payments/v2/testpay.png',
        ],
        'test' => [
            'id' => 'test',
            'type' => 'test',
            'name' => '测试支付',
            'displayName' => '测试支付',
            'image' => '/assets/images/payments/v2/testpay.png',
        ],
    ];

    public function __invoke($id = null)
    {
        if ($id) {
            return $this->createService($id);
        } else {
            return parent::__invoke();
        }
    }

    /**
     * 创建新的支付服务
     *
     * @param string $id
     * @return \services\Payments\Base
     */
    public function createService($id)
    {
        if ($id == 'none') {
            // 无支付方式无需从数据库读取
            return $this()->fromArray(['id' => 'none'])->getService();
        } else {
            return $this()->cache()
                ->setCacheKey($this->getRecordCacheKey($id))
                ->tags(false)
                ->findOneById($id)
                ->getService();
        }
    }

    /**
     * 创建微信支付V2版服务
     *
     * @return \services\payments\WeChatPay
     */
    public function createWeChatPayService()
    {
        return $this->createService('weChatPay');
    }

    /**
     * 创建微信支付服务,自动识别V2,V3版
     *
     * @return \services\payments\WeChatPay|\services\payments\WechatPayV3
     */
    public function createCurrentWechatPayService()
    {
        return $this()->where("id IN ('wechatPayV3', 'weChatPay')")->findOne()->getService();
    }

    /**
     * 创建支付宝支付服务
     *
     * @return \services\payments\WeChatPay|\services\payments\WechatPayV3
     */
    public function createAlipayService()
    {
        return $this()->where("id='alipay'")->findOne()->getService();
    }

    /**
     * 获取当前支付方式的支付服务对象
     *
     * @return \services\payments\Base
     */
    public function getService()
    {
        $class = 'services\\payments\\' . ucfirst($this['id']);

        return new $class(['wei' => $this->wei] + $this['attrs']);
    }

    public function afterFind()
    {
        parent::afterFind();
        $this['attrs'] = (array) json_decode($this['attrs'], true);
    }

    public function beforeSave()
    {
        parent::beforeSave();
        $this['attrs'] = json_encode((array) $this['attrs']);
    }

    public function isSupport($type)
    {
        return isset($this->types[$type]);
    }

    public function getTypes()
    {
        return $this->types;
    }

    public function loadDefaultDataByType()
    {
        $this->fromArray($this->types[$this['type']]);

        return $this;
    }

    /**
     * Repo: 获取指定订单支持的付款方式
     *
     * @param Order $order
     * @return $this[]
     */
    public function getByOrder(Order $order)
    {
        $payments = $this->notDeleted()->enabled()->desc('sort');

        // 如果订单不支持货到付款
        if (!$order->isAllowCashOnDelivery()) {
            $payments->andWhere("type != 'cashOnDelivery'");
        }

        wei()->event->trigger('preFindPayments', [$payments]);

        $payments->findAll();

        wei()->event->trigger('postFindPayments', [$payments, $order]);

        return $payments;
    }

    public function afterSave()
    {
        parent::afterSave();
        $this->clearRecordCache();
    }

    public function afterDestroy()
    {
        parent::afterDestroy();
        $this->clearRecordCache();
    }

    /**
     * Repo: 获取通过余额消费的金额
     *
     * @param \Miaoxing\Plugin\Service\User $user
     * @return string
     */
    public function getConsumeMoney(\Miaoxing\Plugin\Service\User $user = null)
    {
        $user || $user = wei()->curUser;
        $consumeMoney = wei()->order()
            ->andWhere(['userId' => $user['id']])
            ->paid()
            ->select('SUM(balanceAmount)')
            ->fetchColumn();

        return sprintf('%.2f', $consumeMoney);
    }
}
