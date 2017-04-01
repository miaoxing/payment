<?php

namespace Miaoxing\Payment\Payment;

/**
 * 测试商户号: 1900000109
 * 密钥: 8934e7d15453e97507ef794cf7b0519d
 */
class TenpayPc extends Base
{
    /**
     * 商户号，上线时务必将测试商户号替换为正式商户号
     *
     * @var string
     */
    protected $partner = '1217806801';

    /**
     * 密钥
     *
     * @var string
     */
    protected $key = 'f2998a4eaa81b65011c2ba00e6866706';

    public function submit(array $options = [])
    {
        $this->setOption($options);

        require_once('vendor/tenpay_api_b2c/classes/RequestHandler.class.php');

        // 创建支付请求对象

        $reqHandler = new \RequestHandler();
        $reqHandler->init();
        $reqHandler->setKey($this->key);
        $reqHandler->setGateUrl('https://gw.tenpay.com/gateway/pay.htm');

        //----------------------------------------
        //设置支付参数
        //----------------------------------------
        $reqHandler->setParameter('total_fee', $this->orderAmount * 100); //总金额
        //用户ip
        $reqHandler->setParameter('spbill_create_ip', $_SERVER['REMOTE_ADDR']); //客户端IP
        $reqHandler->setParameter('return_url', $this->returnUrl); //支付成功后返回
        $reqHandler->setParameter('partner', $this->partner);
        $reqHandler->setParameter('out_trade_no', $this->orderNo);
        $reqHandler->setParameter('notify_url', $this->notifyUrl);
        $reqHandler->setParameter('body', $this->orderName);
        $reqHandler->setParameter('bank_type', 'DEFAULT'); //银行类型，默认为财付通
        $reqHandler->setParameter('fee_type', '1'); //币种
        //系统可选参数
        $reqHandler->setParameter('sign_type', 'MD5'); //签名方式，默认为MD5，可选RSA
        $reqHandler->setParameter('service_version', '1.0'); //接口版本号
        $reqHandler->setParameter('input_charset', 'UTF-8'); //字符集
        $reqHandler->setParameter('sign_key_index', '1'); //密钥序号

        //业务可选参数
        $reqHandler->setParameter('attach', ''); //附件数据，原样返回就可以了
        $reqHandler->setParameter('product_fee', ''); //商品费用
        $reqHandler->setParameter('transport_fee', ''); //物流费用
        $reqHandler->setParameter('time_start', date('YmdHis')); //订单生成时间
        $reqHandler->setParameter('time_expire', ''); //订单失效时间

        $reqHandler->setParameter('buyer_id', ''); //买方财付通帐号
        $reqHandler->setParameter('goods_tag', ''); //商品标记

        //请求的URL
        $reqUrl = $reqHandler->getRequestURL();

        //获取debug信息,建议把请求和debug信息写入日志，方便定位问题
        $debugInfo = $reqHandler->getDebugInfo();
        $this->logger->debug('Request URL:' . $reqUrl);
        $this->logger->debug($debugInfo);

        // 渲染跳转页面的视图
        return wei()->view->render('pay/tenpay.php', get_defined_vars());
    }

    public function verifyNotify()
    {
        require('vendor/tenpay_api_b2c/classes/ResponseHandler.class.php');
        require('vendor/tenpay_api_b2c/classes/RequestHandler.class.php');
        require('vendor/tenpay_api_b2c/classes/client/ClientResponseHandler.class.php');
        require('vendor/tenpay_api_b2c/classes/client/TenpayHttpClient.class.php');

        // 创建支付应答对象

        $resHandler = new \ResponseHandler();
        $resHandler->setKey($this->key);

        // 判断签名
        $isTenpaySign = $resHandler->isTenpaySign();

        // 获取debug信息,建议把debug信息写入日志，方便定位问题
        $this->logger->debug($resHandler->getDebugInfo());

        if ($isTenpaySign) {
            // 通知id
            $notifyId = $resHandler->getParameter('notify_id');

            // 通过通知ID查询，确保通知来至财付通
            // 创建查询请求
            $queryReq = new \RequestHandler();
            $queryReq->init();
            $queryReq->setKey($this->key);
            $queryReq->setGateUrl('https://gw.tenpay.com/gateway/verifynotifyid.xml');
            $queryReq->setParameter('partner', $this->partner);
            $queryReq->setParameter('notify_id', $notifyId);

            // 通信对象
            $httpClient = new \TenpayHttpClient();
            $httpClient->setTimeOut(5);

            // 设置请求内容
            $httpClient->setReqContent($queryReq->getRequestURL());

            // 后台调用
            if ($httpClient->call()) {
                // 设置结果参数
                $queryRes = new \ClientResponseHandler();
                $queryRes->setContent($httpClient->getResContent());
                $queryRes->setKey($this->key);

                // 获取查询的debug信息,建议把请求、应答内容、debug信息，通信返回码写入日志，方便定位问题
                $message = 'http res:' . $httpClient->getResponseCode() . ',' . $httpClient->getErrInfo() . "\n";
                $message .= 'query req:' . $queryReq->getRequestURL() . "\n\n";
                $message .= 'query res:' . $queryRes->getContent() . "\n\n";
                $message .= 'query req debug:' . $queryReq->getDebugInfo() . "\n\n";
                $message .= 'query res debug:' . $queryRes->getDebugInfo() . "\n\n";
                $this->logger->debug($message);

                // 判断签名及结果
                // 只有签名正确,retcode为0，trade_state为0才是支付成功
                if ($queryRes->isTenpaySign()
                    && $queryRes->getParameter('retcode') == '0'
                    && $queryRes->getParameter('trade_state') == '0'
                    && $queryRes->getParameter('trade_mode') == '1'
                ) {
                    // 取结果参数做业务处理
                    $this->orderNo = $queryRes->getParameter('out_trade_no');
                    // 财付通订单号
                    $this->outOrderNo = $queryRes->getParameter('transaction_id');

                    // 金额,以分为单位
                    $totalFee = $queryRes->getParameter('total_fee');
                    // 如果有使用折扣券，discount有值，total_fee+discount=原请求的total_fee
                    $discount = $queryRes->getParameter('discount');

                    // 计算总的金额
                    $this->orderAmount = ($totalFee + $discount) / 100;

                    //处理数据库逻辑
                    //注意交易单不要重复处理
                    //注意判断返回金额
                    return true;
                } else {
                    // 错误时，返回结果可能没有签名，写日志trade_state、retcode、retmsg看失败详情。
                    $this->logger->error(
                        '验证签名失败 或 业务错误信息:trade_state=' .
                        $queryRes->getParameter('trade_state') .
                        ',retcode=' . $queryRes->getParameter('retcode').
                        ',retmsg=' . $queryRes->getParameter('retmsg')
                    );

                    return false;
                }
            } else {
                // 后台调用通信失败,写日志，方便定位问题
                $this->logger->error('call err:' . $httpClient->getResponseCode() . ',' . $httpClient->getErrInfo());

                return false;
            }
        } else {
            // 回调签名错误
            $this->logger->info('Sign error');

            return false;
        }
    }

    public function verifyReturn()
    {
        return $this->verifyNotify();
    }
}
