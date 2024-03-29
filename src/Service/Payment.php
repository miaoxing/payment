<?php

namespace Miaoxing\Payment\Service;

use Miaoxing\Order\Service\Order;

class Payment extends \Miaoxing\Plugin\BaseService
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
            'displayName' => '微信支付',
            'image' => '/plugins/payment/images/wechat.png',
        ],
        // wechat pay cross country
        'wechatPayCc' => [
            'id' => 'wechatPayCc',
            'type' => 'wechatPayCc',
            'name' => '微信跨境支付',
            'displayName' => '微信跨境支付',
            'image' => '/plugins/payment/images/wechat.png',
        ],
        'alipayWap' => [
            'id' => 'alipayWap',
            'type' => 'alipayWap',
            'name' => '支付宝',
            'displayName' => '支付宝(新版)',
            'image' => '/plugins/payment/images/alipay.png',
        ],
        'alipay' => [
            'id' => 'alipay',
            'type' => 'alipay',
            'name' => '支付宝',
            'displayName' => '支付宝(旧版)',
            'image' => '/plugins/payment/images/alipay.png',
        ],
        'unionPay' => [
            'id' => 'unionPay',
            'type' => 'unionPay',
            'name' => '银联支付',
            'displayName' => '银联支付',
            'image' => 'https://image-10001577.image.myqcloud.com/upload/10/20171011/1507718737880973.png',
        ],
        'tenpay' => [
            'id' => 'tenpay',
            'type' => 'tenpay',
            'name' => '财付通',
            'displayName' => '财付通',
            'image' => '/plugins/payment/images/tenpay.png',
        ],
        'cashOnDelivery' => [
            'id' => 'cashOnDelivery',
            'type' => 'cashOnDelivery',
            'name' => '货到付款',
            'displayName' => '货到付款',
            'image' => '/plugins/payment/images/testpay.png',
        ],
        // 用于只在该平台预订,但是在线下或其他渠道交易的订单
        // TODO 正常使用后替代测试支付
        'external' => [
            'id' => 'external',
            'type' => 'external',
            'name' => '外部支付',
            'displayName' => '外部支付',
            'image' => '/plugins/payment/images/testpay.png',
        ],
        'test' => [
            'id' => 'test',
            'type' => 'test',
            'name' => '测试支付',
            'displayName' => '测试支付',
            'image' => '/plugins/payment/images/testpay.png',
        ],
    ];

    protected $allNames = [
        'alipay' => '支付宝',
        'alipayWap' => '支付宝',
        'tenpay' => '财付通',
        'cashOnDelivery' => '货到付款',
        'test' => '测试支付',
        'wechatPayV3' => '微信支付',
        'wechatPayCc' => '微信跨境支付',
        'unionPay' => '银联支付',
        'external' => '外部支付',
        // 以下无需配置
        'wxaPay' => '微信支付',
        'balance' => '零钱',
        'none' => '无',
    ];

    protected $processedTypes = [];

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
     * @return \Miaoxing\Payment\Payment\Base
     */
    public function createService($id)
    {
        if ('none' == $id) {
            // 无支付方式无需从数据库读取
            return $this()->fromArray(['id' => 'none'])->getService();
        } else {
            return $this()
                ->setCacheKey($this->getRecordCacheKey($id))
                ->findOneById($id)
                ->getService();
        }
    }

    /**
     * 创建微信支付服务,自动识别V2,V3版
     *
     * @return \Miaoxing\Payment\Payment\WechatPayV3
     */
    public function createCurrentWechatPayService()
    {
        return $this()->where(['id' => ['wechatPayV3', 'wxaPay']])->findOne()->getService();
    }

    /**
     * 创建支付宝支付服务
     *
     * @return \Miaoxing\Payment\Payment\Alipay
     */
    public function createAlipayService()
    {
        return $this()->where("id='alipay'")->findOne()->getService();
    }

    /**
     * 获取当前支付方式的支付服务对象
     *
     * @return \Miaoxing\Payment\Payment\Base
     */
    public function getService()
    {
        $types = $this->getTypes();
        if (isset($types[$this['id']]['class'])) {
            $class = $types[$this['id']]['class'];
        } else {
            $class = 'Miaoxing\\Payment\\Payment\\' . ucfirst($this['id']);
        }

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
        return isset($this->getTypes()[$type]);
    }

    public function getTypes()
    {
        if (!$this->processedTypes) {
            $types = $this->types;
            wei()->event->trigger('paymentGetTypes', [&$types]);
            $this->processedTypes = $types;
        }

        return $this->processedTypes;
    }

    public function loadDefaultDataByType()
    {
        $this->fromArray($this->getTypes()[$this['type']]);

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

    public function getName($type)
    {
        return $this->allNames[$type];
    }
}
