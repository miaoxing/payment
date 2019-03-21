<?php

namespace Miaoxing\Payment\Payment;

use Miaoxing\Order\Service\Order;
use ReflectionClass;

/**
 * @property \Wei\Request $request
 * @property \Miaoxing\Wechat\Service\wechatApi $wechatApi
 */
class WechatPayV3 extends Base
{
    /**
     * 公众账号ID
     *
     * @var string
     */
    protected $appId;

    /**
     * 商户号
     *
     * @var string
     */
    protected $mchId;

    /**
     * 加密的密钥
     *
     * @var string
     */
    protected $appKey;

    /**
     * 微信支付V3接口
     *
     * @var \Miaoxing\WechatPay\Service\WechatPayApiV3
     */
    protected $api;

    /**
     * 解析微信支付返回的结果
     *
     * @var array
     */
    protected $notifyResult = [];

    protected function getName()
    {
        $parts = explode('\\', get_class($this));

        return lcfirst(end($parts));
    }

    public function getMchId()
    {
        return $this->mchId;
    }

    /**
     * {@inheritdoc}
     */
    public function submit(array $options)
    {
        // 通过前台实现提交到支付平台,此处留空
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function verifyNotify()
    {
        $payApi = wei()->wechatPayApiV3;
        $payApi->setOption('appKey', $this->appKey);

        // 1. 获取并解析请求数据
        $content = $this->request->getContent();
        if (!$content) {
            return false;
        }
        $result = $payApi->xmlToArray($content);

        // 2. 验证签名
        $sign = $result['sign'];
        unset($result['sign']);
        $generatedSign = $payApi->sign($result);
        if ($sign != $generatedSign) {
            $this->notifyResult = ['return_code' => 'FAIL', 'return_msg' => '签名错误'];
            $this->logger->warning('Pay fail, sign error', [
                'content' => $content,
                'generatedSign' => $generatedSign,
            ]);

            return false;
        }

        // 3. 检查业务是否正确
        if ($result['result_code'] != 'SUCCESS') {
            $this->notifyResult = ['return_code' => 'FAIL', 'return_msg' => $result['err_code_des']];
            $this->logger->warning('Pay fail, result code fail', [
                'content' => $content,
            ]);

            return false;
        }

        // Step4 记录双方订单号并返回
        // 忽略订单号后面的更新时间
        $orderNos = explode('-', $result['out_trade_no']);
        $this->orderNo = $orderNos[0];
        $this->fullOrderNo = $result['out_trade_no'];
        $this->outOrderNo = $result['transaction_id'];
        $this->notifyResult = ['return_code' => 'SUCCESS', 'return_msg' => 'OK'];
        $this->logger->info('verify query success');

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function response($result)
    {
        return wei()->wechatPayApiV3->arrayToXml($this->notifyResult)->asXML();
    }

    /**
     * {@inheritdoc}
     */
    public function verifyReturn()
    {
        // 前台直接跳转,无需验证
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function createPayData(Order $order, $testData = [])
    {
        // 1. 初始化数据
        $timestamp = isset($testData['timestamp']) ? $testData['timestamp'] : time();
        $nonceStr = isset($testData['nonceStr']) ? $testData['nonceStr'] : $this->generateNonceStr(32);

        // 2. 生成预支付交易会话标识
        $ret = $this->createPrepayIdFromOrder($order, [], $testData);
        if ($ret['code'] < 1) {
            return $ret;
        }

        // 3. 生成H5支付JSON数据
        $payData = [
            'appId' => $this->appId,
            'timeStamp' => (string) $timestamp,
            'nonceStr' => $nonceStr,
            'package' => 'prepay_id=' . $ret['prepay_id'],
            'signType' => 'MD5',
        ];
        $paySign = wei()->wechatApi->generateSign($payData) . '&key=' . $this->appKey;
        $payData['paySign'] = strtoupper(md5($paySign));

        return [
            'code' => $ret['code'],
            'message' => $ret['message'],
            'js' => $payData,
            'type' => 'js',
            'prepayId' => $ret['prepay_id'],
        ];
    }

    /**
     * 将商品数组,转换为微信支持的商品ID
     *
     * 已知:可以由数字,字母-_.组成,长度2048+
     *
     * @param $products
     * @return string
     */
    public function encodeProductId($products)
    {
        $ids = [];
        foreach ($products as $product) {
            $ids[] = $product['skuId'] . '_' . $product['quantity'];
        }

        return implode('-', $ids);
    }

    /**
     * 将微信商品ID,转换为商品数组
     *
     * @param string $productId
     * @return array
     */
    public function decodeProductId($productId)
    {
        $products = [];
        $ids = explode('-', $productId);
        foreach ($ids as $id) {
            list($skuId, $quantity) = explode('_', $id);
            $products[] = ['skuId' => $skuId, 'quantity' => $quantity];
        }

        return $products;
    }

    /**
     * 处理扫码支付(原生支付)(模式一)
     *
     * @param string $content
     * @return string
     * @link http://pay.weixin.qq.com/wiki/doc/api/native.php?chapter=6_4
     */
    public function executeNativePay($content)
    {
        $wei = wei();
        $api = $this->getApi();

        // Step1 校验签名,获取解析后的数据
        $data = $api->verifyNativePay($content);
        if (!$data) {
            return $api->responseNativePay([
                'return_code' => 'FAIL',
                'return_msg' => '签名校验失败',
            ]);
        }

        // Step2 如果是新用户,记录到用户表,并设置用户登录态
        $isValid = $data['is_subscribe'] === 'Y';
        $wei->curUser->loginBy(['wechatOpenId' => $data['openid']], ['isValid' => $isValid]);

        // Step3 根据数据生成订单
        if ($data['product_id'][0] == '-') {
            // Step3.1 支付的是已有的订单
            $orderId = substr($data['product_id'], 1);
            /** @var \Miaoxing\Order\Service\Order $order */
            $order = wei()->order()->findById($orderId);
            if (!$order) {
                return $api->responseNativePay([
                    'result_code' => 'FAIL',
                    'err_code_des' => '订单不存在',
                ]);
            }

            if ($order['paid']) {
                return $api->responseNativePay([
                    'result_code' => 'FAIL',
                    'err_code_des' => '该订单已支付',
                ]);
            }

            // 将订单和购物车更改为当前用户所属
            if ($order['userId'] != $wei->user['id']) {
                $order->save([
                    'userId' => $wei->user['id'],
                ]);
                $carts = $order->getCarts();
                $carts->setAll('userId', $wei->user['id'])->save();
            }
        } else {
            // Step3.2 支付的是商品
            $skus = $this->decodeProductId($data['product_id']);

            // 将多个商品SKU转换为订单
            $order = wei()->order();
            $result = $order->createFromSkus($skus, [
                'payType' => $this->getName(),
            ], [
                'source' => Order::SOURCE_OFFLINE, // 认为是线下的支付,免邮
                'createPayData' => false, // 无需创建支付数据,避免重复创建,导致微信提示订单已使用
                'requireAddress' => false,
            ]);
            if ($result['code'] < 1) {
                return $api->responseNativePay([
                    'result_code' => 'FAIL',
                    'err_code_des' => $result['message'],
                ]);
            }
        }

        // Step4 生成prepay_id
        $prePayResult = $this->createPrepayIdFromOrder($order, [
            'trade_type' => 'NATIVE',
            'product_id' => $data['product_id'],
        ]);
        if ($prePayResult['code'] < 1) {
            return $api->responseNativePay([
                'result_code' => 'FAIL',
                'err_code_des' => '生成prepay_id失败: ' . $prePayResult['message'],
            ]);
        }

        // Step5 返回要求的XML数据
        return $api->responseNativePay([
            'prepay_id' => $prePayResult['prepay_id'],
        ]);
    }

    /**
     * 通过订单生成prepayId
     *
     * @param \Miaoxing\Order\Service\Order $order
     * @param array $data
     * @param array $testData
     * @return array
     */
    protected function createPrepayIdFromOrder(Order $order, $data = [], $testData = [])
    {
        // 1. 初始化数据
        $user = wei()->curUser;
        $nonceStr = isset($testData['nonceStr']) ? $testData['nonceStr'] : $this->generateNonceStr(32);

        // 2. 生成prepay_id
        $body = mb_strlen($order['name']) > 28 ? mb_substr($order['name'], 0, 28) . '...' : $order['name'];

        $reflection = new ReflectionClass($this);
        $type = lcfirst($reflection->getShortName());

        $data += [
            'appid' => $this->appId,
            'mch_id' => $this->mchId,
            'nonce_str' => $nonceStr,
            'body' => $body,
            'out_trade_no' => $this->getOutTradeNo($order),
            'fee_type' => 'CNY',
            'total_fee' => $order['amount'] * 100,
            'spbill_create_ip' => wei()->request->getServer('SERVER_ADDR', '127.0.0.1'),
            'notify_url' => wei()->url->full('mall/payment/notify/' . $type),
            'trade_type' => 'JSAPI',
            'openid' => $this->getPayUserOpenId(),
        ];
        $sign = wei()->wechatApi->generateSign($data) . '&key=' . $this->appKey;
        $data['sign'] = strtoupper(md5($sign));

        return wei()->wechatPayApiV3->unifiedOrder($data);
    }

    protected function getPayUserOpenId()
    {
        return wei()->curUser['wechatOpenId'];
    }

    /**
     * 创建原生支付的URL地址
     *
     * 原生支付,$productId规则
     * 1. 商品SKU编号_数量-商品SKU编号_数量
     * 2. -订单编号
     *
     * @param string $productId 注意文档说长度32,实际测试到4097仍正常
     * @param bool $shortUrl 是否生成短链接
     * @param array $testSignData 用于单元测试签名数据
     * @return string
     * @throws \Exception
     * @link http://pay.weixin.qq.com/wiki/doc/api/native.php?chapter=6_4
     */
    public function createNativePayUrl($productId, $shortUrl = true, $testSignData = [])
    {
        $api = $this->getApi();

        $signData = $testSignData + [
                'appid' => $this->appId,
                'mch_id' => $this->mchId,
                'time_stamp' => strval(time()),
                'nonce_str' => $this->generateNonceStr(32),
                'product_id' => $productId,
            ];

        $signData['sign'] = $api->sign($signData);
        $url = 'weixin://wxpay/bizpayurl?' . http_build_query($signData);

        // 生成短地址
        if ($shortUrl) {
            $result = $api->shortUrl($url);
            if ($result['code'] !== 1) {
                wei()->logger->alert($result);
                $url = false;
            } else {
                $url = $result['short_url'];
            }
        }

        return $url;
    }

    /**
     * 生成指定长度的随机字符串
     *
     * @param int $length
     * @return string
     */
    protected function generateNonceStr($length = 32)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; ++$i) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }

        return $str;
    }

    /**
     * 获取微信支付V3接口
     *
     * @return \Miaoxing\WechatPay\Service\WechatPayApiV3
     */
    public function getApi()
    {
        if (!$this->api) {
            $certDir = $this->getCertDir();
            $this->api = wei()->wechatPayApiV3;
            $this->api->setOption([
                'appId' => $this->appId,
                'mchId' => $this->mchId,
                'appKey' => $this->appKey,
                'sslCertFile' => $certDir . '/apiclient_cert.pem',
                'sslKeyFile' => $certDir . '/apiclient_key.pem',
            ]);
        }

        return $this->api;
    }

    /**
     * 获取证书所在目录
     *
     * @return string
     */
    public function getCertDir()
    {
        $namespace = $this->app->getNamespace();
        $plugin = $this->plugin->getById($namespace);

        return $plugin->getBasePath() . '/configs';
    }

    /**
     * {@inheritdoc}
     */
    public function refund($data = [], array $signData = [])
    {
        $data += [
            'orderNo' => '', // 订单编号
            'outOrderNo' => '', // 外部订单编号
            'orderAmount' => 0, // 订单金额,单位分
            'refundId' => '', // 退款单编号
            'refundFee' => 0, // 退款金额,单位分
        ];

        $api = $this->getApi();

        $signData += [
            'appid' => $this->appId,
            'mch_id' => $this->mchId,
            //'device_info' => '', 如果是空,不加入签名
            'nonce_str' => $this->generateNonceStr(),
            'transaction_id' => $data['outOrderNo'],
            'out_trade_no' => $data['orderNo'],
            'out_refund_no' => $data['refundId'],
            'total_fee' => $data['orderAmount'] * 100,
            'refund_fee' => $data['refundFee'] * 100,
            'refund_fee_type' => 'CNY',
            'op_user_id' => $this->mchId,
        ];
        $signData['sign'] = $api->sign($signData);
        $ret = $api->refund($signData);

        if ($ret['code'] >= 1) {
            $ret['refundOutId'] = $ret['refund_id'];
        }

        // 如果已在商户平台退款,后台再退款微信会返回状态错误,需要加上更详细的提醒
        if ($ret['err_code'] === 'TRADE_STATE_ERROR') {
            $ret['message'] = '，请检查您是否已在微信商户平台退过款';
        }

        return $ret;
    }

    /**
     * 退款查询
     *
     * @param array $signData
     * @return array
     */
    public function refundQuery($signData = [])
    {
        $api = $this->getApi();

        $signData += [
            'appid' => $this->appId,
            'mch_id' => $this->mchId,
            'nonce_str' => $this->generateNonceStr(),
        ];
        $signData['sign'] = $api->sign($signData);

        return $api->call('pay/refundquery', $signData);
    }

    /**
     * 附加更改时间,每次都是新的ID,解决微信支付不能改价的问题
     *
     * @param \Miaoxing\Order\Service\Order $order
     * @return string
     */
    public function getOutTradeNo(Order $order)
    {
        return $order['id'] . '-' . strtotime($order['updateTime']);
    }
}
