<?php

namespace Miaoxing\Payment\Payment;

use Miaoxing\Order\Service\Order;
use Miaoxing\Wechat\Service\WechatApi;

/**
 * @property \Wei\Request $request
 * @property WechatApi $wechatApi
 */
class WeChatPay extends Base
{
    /**
     * 公众号id
     *
     * @var string
     */
    protected $appId;

    /**
     * 获取API权限所需密钥
     *
     * @var string
     */
    protected $appSecret;

    /**
     * 加密的密钥
     *
     * @var string
     */
    protected $appKey;

    /**
     * 财付通商户身份的标识
     *
     * @var string
     */
    protected $partnerId;

    /**
     * 财付通商户权限密钥
     *
     * @var string
     */
    protected $partnerKey;

    /**
     * 微信POST到通知URL的数据
     *
     * @var \SimpleXMLElement
     */
    protected $postData;

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
        if (!$this->verifyPostData()) {
            return false;
        }

        if (!$this->verifyQuery()) {
            return false;
        }

        return true;
    }

    /**
     * 校验通知URL的请求数据(GET)是否合法
     *
     * @return bool
     */
    protected function verifyQuery()
    {
        $queries = $this->request->getParameterReference('get');

        // 存储提交过来的签名
        $origSign = $queries['sign'];

        // Step1 除sign字段外，所有参数按照字段名的ascii码从小到大排序后使用QueryString 的格式（即 key1=value1&key2=value2…）拼接而成字符串 string1，空值不传递，不参与签名组串
        unset($queries['sign']);
        //array_filter($queries);
        $string1 = $this->wechatApi->generateSign($queries);

        // Step2 在 string1 最后拼接上 key=paternerKey 得到 stringSignTemp 字符串，幵对stringSignTemp 进行 md5 运算，再将得到的字符串所有字符转换为大写，得到 sign 值signValue。
        $sign = strtoupper(md5($string1 . '&key=' . $this->partnerKey));

        // 支付结果信息不为空时,表示支付失败
        if (isset($queries['pay_info']) && $queries['pay_info']) {
            $this->logger->info('Pay fail, result is ' . $queries['pay_info']);
            return false;
        }

        // 生成的签名相等
        if ($sign != $origSign) {
            $this->logger->info('Pay fail, sign error', [
                $queries,
                'origSign' => $origSign,
                'sign' => $sign
            ]);
            return false;
        }

        // 商户订单号
        $this->orderNo = $queries['out_trade_no'];
        // 微信订单号
        $this->outOrderNo = $queries['transaction_id'];

        $this->logger->info('verify query success');
        return true;
    }

    /**
     * 校验POST到通知URL的数据是否合法
     *
     * @return bool
     */
    protected function verifyPostData()
    {
        $data = $GLOBALS['HTTP_RAW_POST_DATA'];
        $data = $this->postData = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);

        // 构造用于生成签名的数组,键名转小写
        $signData = [
            'appId' => (string)$data->AppId,
            'appKey' => $this->appKey,
            'timestamp' => (string)$data->TimeStamp,
            'nonceStr' => (string)$data->NonceStr,
            'openId' => (string)$data->OpenId,
            'isSubscribe' => (string)$data->IsSubscribe
        ];
        $signData = array_change_key_case((array)$signData);

        // 按键名排序
        $sign = sha1($this->wechatApi->generateSign($signData));
        $result = $sign == (string)$data->AppSignature;

        $this->logger->info('verify post data ' . var_export($result, true));
        return $result;
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
     * 创建JS支付的JSON数据
     *
     * @param array $params
     * @param array $_json 用于单元测试覆盖已有数据
     * @return array
     */
    public function createJsPayJson(array $params = [], array $_json = [])
    {
        // Step1 合并参数
        $defaultParams = [
            'bank_type' => 'WX',
            'body' => '', // 必填
            'partner' => $this->partnerId,
            'out_trade_no' => '', // 必填
            'total_fee' => '', // 必填
            'fee_type' => '1',
            'notify_url' => wei()->url->full('mall/payment/notify/weChatPay'),
            'spbill_create_ip' => $this->request->getIp(),
            'input_charset' => 'UTF-8',
        ];
        $params += $defaultParams;

        // Step2 生成订单详情（package）

        // a.对所有传入参数按照字段名的 ASCII 码从小到大排序（字典序）后，使用 URL 键值对的格式（即key1=value1&key2=value2…）拼接成字符串 string1
        $string1 = $this->wechatApi->generateSign($params);

        // b.在 string1 最后拼接上 key=paternerKey 得到 stringSignTemp 字符串，并对signValue。stringSignTemp 进行 md5 运算，再将得到的字符串所有字符转换为大写，得到 sign 值
        $sign = strtoupper(md5($string1 . '&key=' . $this->partnerKey));

        // c.对 string1 中的所有键值对中的 value 进行 urlencode 转码，按照 a 步骤重新拼接成字符串，得到string2。对亍JS 前端程序，一定要使用函数encodeURIComponent进行 urlencode编码（注意！进行urlencode 时要将空格转化为%20而不是+）
        $string2 = $this->wechatApi->generateSign($params, true);

        // d.将sign=signValue 拼接到string2后面得到最终的 package字符串
        $package = $string2 . '&sign=' . $sign;

        // 参与 paySign 签名的字段包括：appid、timestamp、noncestr、package 以及 appkey（即paySignkey） 。这里signType 幵丌参不签名。
        $json = $_json + [
                'appId' => $this->appId,
                'appKey' => $this->appKey,
                'nonceStr' => $this->generateNonceStr(),
                'package' => $package,
                'timeStamp' => strval(time())
            ];

        $signData = array_change_key_case($json);
        $string1 = $this->wechatApi->generateSign($signData);
        $paySign = sha1($string1);

        unset($json['appKey']);
        $json['signType'] = 'sha1';
        $json['paySign'] = $paySign;

        return $json;
    }

    /**
     * 从订单创建JS支付的JSON数据
     *
     * @param \Miaoxing\Order\Service\Order $order
     * @return array
     */
    public function createJsPayJsonFromOrder(Order $order)
    {
        return $this->createJsPayJson([
            'body' => $order['name'],
            'out_trade_no' => $order['id'],
            'total_fee' => $order['amount'] * 100
        ]);
    }

    /**
     * 创建原生支付的URL地址
     *
     * 原生支付,$orderId规则
     * 1. 商品SKU编号_数量-商品SKU编号_数量
     * 2. -订单编号
     *
     * @param int $orderId
     * @param array $_signData 用于单元测试签名数据
     * @return string
     */
    public function createNativePayUrl($orderId, $_signData = [])
    {
        $signData = $_signData + [
            'appid' => $this->appId,
            'appkey' => $this->appKey,
            'noncestr' => $this->generateNonceStr(),
            'productid' => $orderId,
            'timestamp' => strval(time()),
        ];

        // 生成签名
        $string1 = $this->wechatApi->generateSign($signData);
        $sign = sha1($string1);

        $url = http_build_query([
            'appid' => $this->appId,
            'noncestr' => $signData['noncestr'],
            'productid' => $signData['productid'],
            'sign' => $sign,
            'timestamp' => $signData['timestamp'],
        ]);

        return 'weixin://wxpay/bizpayurl?' . $url;
    }

    /**
     * 处理原生支付
     *
     * @param string $content
     * @return string
     * @link https://mp.weixin.qq.com/htmledition/res/bussiness-course2/wxpay-payment-api.pdf
     */
    public function executeNativePay($content)
    {
        $wei = wei();

        // Step1 校验签名,获取解析后的数据
        $data = $this->verifyNativePay($content);
        if (!$data) {
            return $this->responseNativePay([
                'RetCode' => -1,
                'RetErrMsg' => '签名校验失败',
            ]);
        }

        // Step2 如果是新用户,记录到用户表,并设置用户登录态
        $wei->curUser->loginBy(
            ['wechatOpenId' => $data->OpenId],
            ['isValid' => $data->IsSubscribe]
        );

        // Step3 根据数据生成订单
        if (substr($data->ProductId, 0, 1) == '-') {
            // Step3.1 支付的是已有的订单
            $orderId = substr($data->ProductId, 1);
            $order = $wei->order()->findById($orderId);
            if (!$order) {
                return $this->responseNativePay([
                    'RetCode' => 404,
                    'RetErrMsg' => '订单不存在',
                ]);
            }
        } else {
            // Step3.2 支付的是商品
            $skus = $this->decodeProductId($data->ProductId);

            // 将商品逐个加入购物车,失败则返回错误提示
            $cartIds = [];
            foreach ($skus as $sku) {
                $cart = $wei->cart();
                $result = $cart->create($sku);
                if ($result['code'] < 1) {
                    return $this->responseNativePay([
                        'RetCode' => $result['code'],
                        'RetErrMsg' => $result['message'],
                    ]);
                } else {
                    $cartIds[] = $cart['id'];
                }
            };

            // 将购物车转换为订单
            $order = $wei->order();
            $result = $order->create([
                'cartId' => $cartIds,
                'payType' => 'weChatPay',
            ], [
                'source' => Order::SOURCE_OFFLINE
            ]);
            if ($result['code'] < 1) {
                return $this->responseNativePay([
                    'RetCode' => $result['code'],
                    'RetErrMsg' => $result['message'],
                ]);
            }
        }

        // Step4 返回要求的Package数据
        $payJson = $this->createJsPayJsonFromOrder($order);
        return $this->responseNativePay([
            'Package' => $payJson['package']
        ]);
    }

    /**
     * 校验微信POST过来的原生支付数据是否合法
     *
     * @param string $data
     * @return \SimpleXMLElement|false
     */
    public function verifyNativePay($data)
    {
        // Step1, 解析XML数据
        $data = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);

        // Step2
        $signData = [
            'appId' => (string)$data->AppId,
            'appKey' => $this->appKey,
            'productId' => (string)$data->ProductId,
            'timestamp' => (string)$data->TimeStamp,
            'nonceStr' => (string)$data->NonceStr,
            'openId' => (string)$data->OpenId,
            'isSubscribe' => (string)$data->IsSubscribe
        ];
        $signData = array_change_key_case($signData);
        $signData = sha1($this->wechatApi->generateSign($signData));

        if ($signData === (string)$data->AppSignature) {
            return $data;
        } else {
            return false;
        }
    }

    /**
     * 生成原生支付的XML数据,返回给微信
     *
     * @param array $data
     * @return string
     */
    public function responseNativePay($data = [])
    {
        // Step1 合并返回的数据
        $data += [
            'AppId' => $this->appId,
            'Package' => '',
            'TimeStamp' => time(),
            'NonceStr' => $this->generateNonceStr(),
            'RetCode' => 0,
            'RetErrMsg' => 'ok',
            'AppSignature' => '',
            'SignMethod' => 'sha1'
        ];
        // 微信要求RetCode不能小于0
        $data['RetCode'] = abs($data['RetCode']);

        // Step2 构造签名数据
        $signData = [
            'appId' => $this->appId,
            'appKey' => $this->appKey,
            'package' => $data['Package'],
            'timestamp' => $data['TimeStamp'],
            'nonceStr' => $data['NonceStr'],
            'retCode' => $data['RetCode'],
            'retErrMsg' => $data['RetErrMsg'],
        ];
        $signData = array_change_key_case($signData);
        $data['AppSignature'] = sha1($this->wechatApi->generateSign($signData));

        // Step3 输出XML数据
        $xml = new \SimpleXMLElement('<xml/>');
        foreach ($data as $key => $value) {
            $child = $xml->addChild($key);
            $node = dom_import_simplexml($child);
            $node->appendChild($node->ownerDocument->createCDATASection($value));
        }
        return $xml->asXML();
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
        foreach ((array)$products as $product) {
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
     * 生成指定长度的随机字符串
     *
     * @param int $length
     * @return string
     */
    protected function generateNonceStr($length = 16)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    public function ship(Order $order)
    {
        $user = $order->getUser();
        $account = wei()->wechatAccount->getCurrentAccount();
        $wechatApi = $account->createApiService();

        $signData = [
            'appid' => $this->appId,
            'appkey' => $this->appKey,
            'openid' => $user['wechatOpenId'],
            'transid' => $order['outNo'],
            'out_trade_no' => $order['id'],
            'deliver_timestamp' => time(),
            'deliver_status' => 1,
            'deliver_msg' => 'ok',
        ];

        $sign = sha1($wechatApi->generateSign($signData));

        $data = $signData;
        $data['app_signature'] = $sign;
        $data['sign_method'] = 'sha1';
        unset($data['appkey']);

        $result = $wechatApi->ship($data);
        if (!$result) {
            return $wechatApi->getResult();
        } else {
            return ['code' => 1, 'message' => '发货成功'];
        }
    }

    public function createPayData(Order $order)
    {
        return [
            'js' => $this->createJsPayJsonFromOrder($order),
            'type' => 'js'
        ];
    }

    /**
     * @return string
     */
    public function getPartnerId()
    {
        return $this->partnerId;
    }

    /**
     * @return string
     */
    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * @return string
     */
    public function getPartnerKey()
    {
        return $this->partnerKey;
    }
}
