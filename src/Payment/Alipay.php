<?php

namespace Miaoxing\Payment\Payment;

use Wei\Request;

/**
 * @property Request $request
 */
class Alipay extends Base
{
    /**
     * 卖家支付宝帐户
     *
     * @var string
     */
    protected $sellerEmail;

    /**
     * 合作身份者id
     *
     * @var string
     */
    public $partner;

    /**
     * 安全检验码
     *
     * @var string
     */
    public $key;

    /**
     * 用户付款中途退出返回商户的地址。需http://格式的完整路径，不允许加?id=123这类自定义参数
     *
     * @var string
     */
    protected $errorUrl;

    /**
     * 提交订单
     *
     * 功能：即时到账交易接口接入页
     * 版本：3.3
     * 修改日期：2012-07-23
     * 说明：
     * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
     * 该代码仅供学习和研究支付宝接口使用，只是提供一个参考。
     *
     * ************************注意*************************
     * 如果您在接口集成过程中遇到问题，可以按照下面的途径来解决
     * 1、商户服务中心（https://b.alipay.com/support/helperApply.htm?action=consultationApply），提交申请集成协助，我们会有专业的技术工程师主动联系您协助解决
     * 2、商户帮助中心（http://help.alipay.com/support/232511-16307/0-16307.htm?sh=Y&info_type=9）
     * 3、支付宝论坛（http://club.alipay.com/read-htm-tid-8681712.html）
     * 如果不想使用扩展功能请把扩展功能参数赋空值。
     */
    public function submit(array $options = [])
    {
        $this->setOption($options);

        $alipayConfig = $this->getConfig();

        // 调用授权接口alipay.wap.trade.create.direct获取授权码token

        // 返回格式
        $format = 'xml';
        // 必填，不需要修改

        // 返回格式
        $v = '2.0';
        // 必填，不需要修改

        // 请求号
        $reqId = date('Ymdhis');
        // 必填，须保证每次请求都是唯一

        // **req_data详细信息**

        //服务器异步通知页面路径
        $notifyUrl = $this->notifyUrl;
        //需http://格式的完整路径，不允许加?id=123这类自定义参数

        //页面跳转同步通知页面路径
        $callbackUrl = $this->returnUrl;
        //需http://格式的完整路径，不允许加?id=123这类自定义参数

        //操作中断返回地址
        $merchantUrl = $this->errorUrl;
        //用户付款中途退出返回商户的地址。需http://格式的完整路径，不允许加?id=123这类自定义参数

        //卖家支付宝帐户
        $sellerEmail = $this->sellerEmail;
        //必填

        //商户订单号
        $outTradeNo = $this->orderNo;
        //商户网站订单系统中唯一订单号，必填

        // 订单名称 替换&<>为下划线
        // 如果不转义,拼出来的xml结构错误,如果转义,支付宝会返回结构错误的xml
        $subject = strtr($this->orderName, ['&' => '_', '<' => '_', '>' => '_']);
        //必填

        //付款金额
        $totalFee = $this->orderAmount;
        //必填

        //请求业务参数详细
        $reqData = '<direct_trade_create_req><notify_url>'
            . $notifyUrl . '</notify_url><call_back_url>'
            . $callbackUrl . '</call_back_url><seller_account_name>'
            . $sellerEmail . '</seller_account_name><out_trade_no>'
            . $outTradeNo . '</out_trade_no><subject>'
            . $subject . '</subject><total_fee>'
            . $totalFee . '</total_fee><merchant_url>'
            . $merchantUrl . '</merchant_url></direct_trade_create_req>';
        //必填

        //构造要请求的参数数组，无需改动
        $paraToken = [
            'service' => 'alipay.wap.trade.create.direct',
            'partner' => trim($alipayConfig['partner']),
            'sec_id' => trim($alipayConfig['sign_type']),
            'format' => $format,
            'v' => $v,
            'req_id' => $reqId,
            'req_data' => $reqData,
            '_input_charset' => trim(strtolower($alipayConfig['input_charset'])),
        ];

        //建立请求
        $alipaySubmit = new \AlipaySubmit($alipayConfig);
        $htmlText = $alipaySubmit->buildRequestHttp($paraToken);

        //URLDECODE返回的信息
        $htmlText = urldecode($htmlText);

        //解析远程模拟提交后返回的信息
        $paraHtmlText = $alipaySubmit->parseResponse($htmlText);

        //获取request_token
        $requestToken = $paraHtmlText['request_token'];

        // 根据授权码token调用交易接口alipay.wap.auth.authAndExecute

        //业务详细
        $reqData = '<auth_and_execute_req><request_token>' . $requestToken . '</request_token></auth_and_execute_req>';
        //必填

        //构造要请求的参数数组，无需改动
        $parameter = [
            'service' => 'alipay.wap.auth.authAndExecute',
            'partner' => trim($alipayConfig['partner']),
            'sec_id' => trim($alipayConfig['sign_type']),
            'format' => $format,
            'v' => $v,
            'req_id' => $reqId,
            'req_data' => $reqData,
            '_input_charset' => trim(strtolower($alipayConfig['input_charset'])),
        ];

        // 建立请求
        $alipaySubmit = new \AlipaySubmit($alipayConfig);
        $htmlText = $alipaySubmit->buildRequestForm($parameter, 'get', '确认');

        return [
            'html' => $htmlText,
        ];
    }

    /**
     * 批量有密退款接口
     *
     * @param array $data
     * @param array $signData
     * @return array
     */
    public function refund($data, $signData = [])
    {
        //服务器异步通知页面路径
        $notifyUrl = $this->url->full('alipay/refund-notify');
        //需http://格式的完整路径，不允许加?id=123这类自定义参数

        $alipayConfig = wei()->alipaySubmit->getDefaultConfig();
        $detailData = $data['outOrderNo'].'^'.$data['refundFee'].'^系统退款';

        //构造要请求的参数数组，无需改动
        $parameter = [
            'service' => 'refund_fastpay_by_platform_pwd',
            'partner' => trim($this->partner),
            'notify_url' => $notifyUrl,
            '_input_charset' => trim(strtolower($alipayConfig['input_charset'])),
            'seller_email' => trim($this->sellerEmail),
            'refund_date' => date('Y-m-d h:i:s'),
            'batch_no' => date('Ymd').rand(100, 999).$data['refundId'],
            'batch_num' => 1,
            'detail_data' => $detailData,
        ];

        //建立请求
        $alipaySubmit = wei()->alipaySubmit;

        //更新独立的配置
        $alipayConfig['partner'] = $this->partner;
        $alipayConfig['key'] = $this->key;

        $alipaySubmit->setAlipayConfig($alipayConfig);
        $para = $alipaySubmit->buildRequestPara($parameter);
        $url = wei()->url->append($alipaySubmit->alipayGatewayNew, $para);

        return ['code' => -1, 'message' => '等待手动支付宝退款操作', 'url' => $url];
    }

    public function verifyNotify()
    {
        $alipayConfig = $this->getConfig();

        //  计算得出通知验证结果
        $alipayNotify = new \AlipayNotify($alipayConfig);
        $verifyResult = $alipayNotify->verifyNotify();
        //$verify_result = true;
        if ($verifyResult) { //验证成功
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            //请在这里加上商户的业务逻辑程序代

            //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——

            //解密（如果是RSA签名需要解密，如果是MD5签名则下面一行清注释掉）
            if (strtoupper($alipayConfig['sign_type']) != 'MD5') {
                $notifyData = decrypt($this->request->getPost('notify_data'));
            } else {
                $notifyData = $this->request->getPost('notify_data');
            }

            //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表

            //解析notify_data
            //注意：该功能PHP5环境及以上支持，需开通curl、SSL等PHP配置环境。建议本地调试时使用PHP开发软件
            $doc = new \DOMDocument();
            $doc->loadXML($notifyData);

            if (!empty($doc->getElementsByTagName('notify')->item(0)->nodeValue)) {
                //商户订单号
                $this->orderNo = $doc->getElementsByTagName('out_trade_no')->item(0)->nodeValue;
                //支付宝交易号
                $this->outOrderNo = $doc->getElementsByTagName('trade_no')->item(0)->nodeValue;
                //交易状态
                $this->status = $doc->getElementsByTagName('trade_status')->item(0)->nodeValue;

                return true;

//                    if($_POST['trade_status'] == 'TRADE_FINISHED') {
//                    //判断该笔订单是否在商户网站中已经做过处理
//                    //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
//                    //如果有做过处理，不执行商户的业务程序
//
//                    //注意：
//                    //该种交易状态只在两种情况下出现
//                    //1、开通了普通即时到账，买家付款成功后。
//                    //2、开通了高级即时到账，从该笔交易成功时间算起，过了签约时的可退款时限（如：三个月以内可退款、一年以内可退款等）后。
//
//                    //调试用，写文本函数记录程序运行情况是否正常
//                    //logResult('这里写入想要调试的代码变量值，或其他运行的结果记录');
//
//                    echo 'success';		//请不要修改或删除
//                    }
//                    else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
//                    //判断该笔订单是否在商户网站中已经做过处理
//                    //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
//                    //如果有做过处理，不执行商户的业务程序
//
//                    //注意：
//                    //该种交易状态只在一种情况下出现——开通了高级即时到账，买家付款成功后。
//
//                    //调试用，写文本函数记录程序运行情况是否正常
//                    //logResult('这里写入想要调试的代码变量值，或其他运行的结果记录');
//
//                    echo 'success';		//请不要修改或删除
            } else {
                return false;
            }

            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——

            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        } else {
            //验证失败
            return false;
        }
    }

    public function verifyReturn()
    {
        $alipayConfig = $this->getConfig();

        // 计算得出通知验证结果
        $alipayNotify = new \AlipayNotify($alipayConfig);
        $verifyResult = $alipayNotify->verifyReturn();
        if ($verifyResult) {
            // 验证成功
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            //请在这里加上商户的业务逻辑程序代码

            //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
            //获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表
            //商户订单号
            $this->orderNo = $this->request->getQuery('out_trade_no');

            //支付宝交易号
            $this->outOrderNo = $this->request->getQuery('trade_no');

            //交易状态
            $result = $this->request->getQuery('result');

            //判断该笔订单是否在商户网站中已经做过处理
            //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
            //如果有做过处理，不执行商户的业务程序
            return true;
        } else {
            return false;
        }
    }

    /**
     * 配置文件
     * 版本：3.3
     * 日期：2012-07-19
     * 说明：
     * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
     * 该代码仅供学习和研究支付宝接口使用，只是提供一个参考。

     * 提示：如何获取安全校验码和合作身份者id
     * 1.用您的签约支付宝账号登录支付宝网站(www.alipay.com)
     * 2.点击“商家服务”(https://b.alipay.com/order/myorder.htm)
     * 3.点击“查询合作者身份(pid)”、“查询安全校验码(key)”

     * 安全校验码查看时，输入支付密码后，页面呈灰色的现象，怎么办？
     * 解决方法：
     * 1、检查浏览器配置，不让浏览器做弹框屏蔽设置
     * 2、更换浏览器或电脑，重新登录查询。
     */
    protected function getConfig()
    {
        //↓↓↓↓↓↓↓↓↓↓请在这里配置您的基本信息↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
        //合作身份者id，以2088开头的16位纯数字
        $alipayConfig['partner'] = $this->partner;

        //安全检验码，以数字和字母组成的32位字符
        //如果签名方式设置为“MD5”时，请设置该参数
        $alipayConfig['key'] = $this->key;

        //商户的私钥（后缀是.pen）文件相对路径
        //如果签名方式设置为“0001”时，请设置该参数
        $alipayConfig['private_key_path'] = 'key/rsa_private_key.pem';

        //支付宝公钥（后缀是.pen）文件相对路径
        //如果签名方式设置为“0001”时，请设置该参数
        $alipayConfig['ali_public_key_path'] = 'key/alipay_public_key.pem';

        //↑↑↑↑↑↑↑↑↑↑请在这里配置您的基本信息↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑

        //签名方式 不需修改
        $alipayConfig['sign_type'] = 'MD5';

        //字符编码格式 目前支持 gbk 或 utf-8
        $alipayConfig['input_charset'] = 'utf-8';

        //ca证书路径地址，用于curl中ssl校验
        //请保证cacert.pem文件在当前文件夹目录中
        $alipayConfig['cacert'] = getcwd().'\\cacert.pem';

        //访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
        $alipayConfig['transport'] = 'http';

        return $alipayConfig;
    }

    public function isComplete()
    {
        return $this->status == 'TRADE_FINISHED' || $this->status == 'TRADE_SUCCESS';
    }

    public function isPending()
    {
        return $this->status == 'WAIT_BUYER_PAY';
    }
}
