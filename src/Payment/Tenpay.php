<?php

namespace Miaoxing\Payment\Payment;

class Tenpay extends Base
{
    /**
     * 商户号，上线时务必将测试商户号替换为正式商户号
     *
     * @var string
     */
    protected $partner;

    /**
     * 密钥
     *
     * @var string
     */
    protected $key;

    public function submit(array $options)
    {
        $this->setOption($options);

        /* 创建支付请求对象 */
        $reqHandler = new \RequestHandler();
        $reqHandler->init();
        $reqHandler->setKey($this->key);

        // 设置初始化请求接口，以获得token_id
        $reqHandler->setGateUrl("http://wap.tenpay.com/cgi-bin/wappayv2.0/wappay_init.cgi");


        $httpClient = new \TenpayHttpClient();
        //应答对象
        $resHandler = new \ClientResponseHandler();
        //----------------------------------------
        //设置支付参数
        //----------------------------------------
        $reqHandler->setParameter("total_fee", $this->orderAmount * 100); //总金额
        //用户ip
        $reqHandler->setParameter("spbill_create_ip", $_SERVER['REMOTE_ADDR']); //客户端IP
        $reqHandler->setParameter("ver", "2.0"); //版本类型
        $reqHandler->setParameter("bank_type", "0"); //银行类型，财付通填写0
        $reqHandler->setParameter("callback_url", $this->returnUrl); //交易完成后跳转的URL
        $reqHandler->setParameter("bargainor_id", $this->partner); //商户号
        $reqHandler->setParameter("sp_billno", $this->orderNo); //商户订单号
        $reqHandler->setParameter("notify_url", $this->notifyUrl); //接收财付通通知的URL，需绝对路径
        $reqHandler->setParameter("desc", $this->orderName);
        $reqHandler->setParameter("desc", $this->orderName);
        $reqHandler->setParameter("attach", "");

        $reqUrl = $reqHandler->getRequestURL();

        // 获取debug信息,建议把请求和debug信息写入日志，方便定位问题
        $debugInfo = $reqHandler->getDebugInfo();
        $this->logger->info('Request URL:' . $reqUrl, $debugInfo);

        $httpClient->setReqContent($reqUrl);

        // 后台调用
        if ($httpClient->call()) {

            $resHandler->setContent($httpClient->getResContent());
            //获得的token_id，用于支付请求
            $token_id = $resHandler->getParameter('token_id');
            $reqHandler->setParameter("token_id", $token_id);

            // 获取查询的debug信息,建议把请求、应答内容、debug信息，通信返回码写入日志，方便定位问题
            $message = "http res:" . $httpClient->getResponseCode() . "," . $httpClient->getErrInfo() . "\n";
            $message .= "query req:" . $reqHandler->getRequestURL() . "\n\n";
            $message .= "query res:" . $resHandler->getContent() . "\n\n";
            $message .= "query req debug:" . $reqHandler->getDebugInfo() . "\n\n";
            $message .= "query res debug:" . $resHandler->getDebugInfo() . "\n\n";
            $message .= "return url: " . $this->returnUrl . "\n\n";
            $message .= "notify url: " . $this->notifyUrl . "\n\n";
            $this->logger->log($token_id ? 'info' : 'alert', $message);

            //请求的URL
            //$reqHandler->setGateUrl("https://wap.tenpay.com/cgi-bin/wappayv2.0/wappay_gate.cgi");
            //此次请求只需带上参数token_id就可以了，$reqUrl和$reqUrl2效果是一样的
            //$reqUrl = $reqHandler->getRequestURL();
            $reqUrl = "http://wap.tenpay.com/cgi-bin/wappayv2.0/wappay_gate.cgi?token_id=" . $token_id;

            // 渲染跳转页面的视图
            return wei()->view->render('mall:mall/payment/tenpay.php', get_defined_vars());
        } else {
            // 后台调用通信失败,写日志，方便定位问题
            $this->logger->error("call err:" . $httpClient->getResponseCode() . "," . $httpClient->getErrInfo());
            throw new \RuntimeException('Request error');
        }
    }

    public function verifyNotify()
    {
        /* 创建支付应答对象 */
        $resHandler = new \WapNotifyResponseHandler();
        $resHandler->setKey($this->key);

        // 判断签名
        $isTenpaySign = $resHandler->isTenpaySign();

        // 获取debug信息,建议把debug信息写入日志，方便定位问题
        $this->logger->debug($resHandler->getDebugInfo());

        //判断签名
        if ($isTenpaySign) {

            //商户订单号
            $this->orderNo = $resHandler->getParameter("sp_billno");

            //财付通交易单号
            $this->outOrderNo = $resHandler->getParameter("transaction_id");

            //金额,以分为单位
            $this->orderAmount = $resHandler->getParameter("total_fee") / 100;

            //支付结果
            $pay_result = $resHandler->getParameter("pay_result");

            return "0" == $pay_result;
        } else {
            //回调签名错误
            $this->logger->info('Sign error');
            return false;
        }
    }

    public function verifyReturn()
    {
        return $this->verifyNotify();
    }
}
