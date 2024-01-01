<?php

namespace Miaoxing\Payment\Payment;

use Omnipay\Alipay\AopWapGateway;
use Omnipay\Alipay\Requests\AbstractAopRequest;
use Omnipay\Alipay\Requests\AopTradeRefundRequest;
use Omnipay\Alipay\Responses\AopCompletePurchaseResponse;
use Omnipay\Alipay\Responses\AopTradeRefundResponse;
use Omnipay\Alipay\Responses\AopTradeWapPayResponse;
use Omnipay\Omnipay;
use Wei\Request;
use Wei\Response;
use Wei\RetTrait;

/**
 * @property Request $request
 * @property Response response
 */
class AlipayWap extends Base
{
    use RetTrait;

    /**
     * APPID
     *
     * @var string
     */
    protected $appId;

    /**
     * 应用私钥
     *
     * @var string
     */
    protected $privateKey;

    /**
     * 支付宝公钥
     *
     * @var string
     */
    protected $alipayPublicKey;

    protected function createGateway()
    {
        /** @var AopWapGateway $gateway */
        $gateway = Omnipay::create('Alipay_AopWap');
        $gateway->setSignType('RSA2');
        $gateway->setAppId($this->appId);
        $gateway->setPrivateKey($this->privateKey);
        $gateway->setAlipayPublicKey($this->alipayPublicKey);

        return $gateway;
    }

    /**
     * 提交订单到支付平台
     *
     * @param array $options
     * @return array
     */
    public function submit(array $options)
    {
        $gateway = $this->createGateway();
        $gateway->setNotifyUrl($options['notifyUrl']);
        $gateway->setReturnUrl($options['returnUrl']);

        /** @var AbstractAopRequest $request */
        $request = $gateway->purchase();
        $request->setBizContent([
            'out_trade_no' => $options['orderNo'],
            'total_amount' => $options['orderAmount'],
            'subject' => $options['orderName'],
            'product_code' => 'QUICK_WAP_PAY',
        ]);

        /** @var AopTradeWapPayResponse $response */
        $response = $request->send();

        $url = $response->getRedirectUrl();

        return [
            'html' => $this->view->render('@alipay/alipay/submit.php', [
                'url' => $url,
            ]),
        ];
    }

    /**
     * 校验订单是否支付成功(支付平台调用Notify URL即触发该方法)
     *
     * @return bool
     */
    public function verifyNotify()
    {
        $gateway = $this->createGateway();

        $request = $gateway->completePurchase();
        $params = array_merge($this->request->getParameterReference('post'), $this->request->getQueries());
        $request->setParams($params);

        /** @var AopCompletePurchaseResponse $response */
        $response = $request->send();
        if (!$response->isPaid()) {
            return false;
        }

        $this->orderNo = $response->data('out_trade_no');
        $this->outOrderNo = $response->data('trade_no');

        return true;
    }

    /**
     * 校验支付平台返回到Return URL的数据是否正确
     *
     * @return bool
     */
    public function verifyReturn()
    {
        return $this->verifyNotify();
    }

    /**
     * @param array $data
     * @param array $signData
     * @return array
     * @link https://docs.open.alipay.com/api_1/alipay.trade.refund
     */
    public function refund($data = [], array $signData = [])
    {
        $gateway = $this->createGateway();

        /** @var AopTradeRefundRequest $request */
        $request = $gateway->refund();
        $request->setBizContent([
            'out_trade_no' => $data['orderNo'],
            'trade_no' => $data['outOrderNo'],
            'refund_amount' => $data['refundFee'],
            'out_request_no' => $data['refundId'],
        ]);

        /** @var AopTradeRefundResponse $response */
        $response = $request->send();
        $data = $response->getData();

        // NOTE: 返回值没有文档提到的sub_code,根据实际情况判断
        if (!$response->isSuccessful()) {
            return $this->err(['message' => '支付宝返回错误信息:' . $response->getMessage()] + $data);
        }

        if ('Y' !== $response->getAlipayResponse('fund_change')) {
            return $this->err(['message' => '支付宝返回资金未变化,请检查是否已经退过款'] + $data);
        }

        // 没有外部退款编号
        return $this->suc(['refundOutId' => 0] + $data);
    }
}
