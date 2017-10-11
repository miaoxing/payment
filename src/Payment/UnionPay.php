<?php

namespace Miaoxing\Payment\Payment;

use com\unionpay\acp\sdk\AcpService;
use com\unionpay\acp\sdk\SDKConfig;
use Wei\Env;
use Wei\Request;
use Wei\RetTrait;

/**
 * @property Request request
 * @property Env env
 */
class UnionPay extends Base
{
    use RetTrait;

    protected $merId;

    protected $certPassword;

    /**
     * {@inheritdoc}
     */
    public function submit(array $options)
    {
        $this->initSDkConfig();

        /**
         * 重要：联调测试时请仔细阅读注释！
         *
         * 产品：跳转网关支付产品<br>
         * 交易：消费：前台跳转，有前台通知应答和后台通知应答<br>
         * 日期： 2015-09<br>
         * 版本： 1.0.0
         * 版权： 中国银联<br>
         * 说明：以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己需要，按照技术文档编写。该代码仅供参考，不提供编码性能规范性等方面的保障<br>
         * 提示：该接口参考文档位置：open.unionpay.com帮助中心 下载  产品接口规范  《网关支付产品接口规范》，<br>
         *              《平台接入接口规范-第5部分-附录》（内包含应答码接口规范，全渠道平台银行名称-简码对照表)<br>
         *              《全渠道平台接入接口规范 第3部分 文件接口》（对账文件格式说明）<br>
         * 测试过程中的如果遇到疑问或问题您可以：1）优先在open平台中查找答案：
         *                                    调试过程中的问题或其他问题请在 https://open.unionpay.com/ajweb/help/faq/list 帮助中心 FAQ 搜索解决方案
         *                             测试过程中产生的7位应答码问题疑问请在https://open.unionpay.com/ajweb/help/respCode/respCodeList 输入应答码搜索解决方案
         *                          2） 咨询在线人工支持： open.unionpay.com注册一个用户并登陆在右上角点击“在线客服”，咨询人工QQ测试支持。
         * 交易说明:1）以后台通知或交易状态查询交易确定交易成功,前台通知不能作为判断成功的标准.
         *       2）交易状态查询交易（Form_6_5_Query）建议调用机制：前台类交易建议间隔（5分、10分、30分、60分、120分）发起交易查询，如果查询到结果成功，则不用再查询。（失败，处理中，查询不到订单均可能为中间状态）。也可以建议商户使用payTimeout（支付超时时间），过了这个时间点查询，得到的结果为最终结果。
         */
        $params = [
            // 以下信息非特殊情况不需要改动
            'version' => SDKConfig::getSDKConfig()->version,                 //版本号
            'encoding' => 'utf-8',                  //编码方式
            'txnType' => '01',                      //交易类型
            'txnSubType' => '01',                  //交易子类
            'bizType' => '000201',                  //业务类型
            'frontUrl' => $options['returnUrl'],  //前台通知地址
            'backUrl' => $options['notifyUrl'],      //后台通知地址
            'signMethod' => SDKConfig::getSDKConfig()->signMethod,                  //签名方法
            'channelType' => '08',                  //渠道类型，07-PC，08-手机
            'accessType' => '0',                  //接入类型
            'currencyCode' => '156',              //交易币种，境内商户固定156

            // 以下信息需要填写
            'merId' => $this->merId,        //商户代码，请改自己的测试商户号，此处默认取demo演示页面传递的参数
            'orderId' => $options['orderNo'],    //商户订单号，8-32位数字字母，不能含“-”或“_”，此处默认取demo演示页面传递的参数，可以自行定制规则
            'txnTime' => date('YmdHis'),    //订单发送时间，格式为YYYYMMDDhhmmss，取北京时间，此处默认取demo演示页面传递的参数
            'txnAmt' => (string) ($options['orderAmount'] * 100), //交易金额，单位分，此处默认取demo演示页面传递的参数

            // 订单超时时间。
            // 超过此时间后，除网银交易外，其他交易银联系统会拒绝受理，提示超时。 跳转银行网银交易如果超时后交易成功，会自动退款，大约5个工作日金额返还到持卡人账户。
            // 此时间建议取支付时的北京时间加15分钟。
            // 超过超时时间调查询接口应答origRespCode不是A6或者00的就可以判断为失败。
            //'payTimeout' => date('YmdHis', strtotime('+15 minutes')),

            // 请求方保留域，
            // 透传字段，查询、通知、对账文件中均会原样出现，如有需要请启用并修改自己希望透传的数据。
            // 出现部分特殊字符时可能影响解析，请按下面建议的方式填写：
            // 1. 如果能确定内容不会出现&={}[]"'等符号时，可以直接填写数据，建议的方法如下。
            //    'reqReserved' =>'透传信息1|透传信息2|透传信息3',
            // 2. 内容可能出现&={}[]"'符号时：
            // 1) 如果需要对账文件里能显示，可将字符替换成全角＆＝｛｝【】“‘字符（自己写代码，此处不演示）；
            // 2) 如果对账文件没有显示要求，可做一下base64（如下）。
            //    注意控制数据长度，实际传输的数据长度不能超过1024位。
            //    查询、通知等接口解析时使用base64_decode解base64后再对数据做后续解析。
            //    'reqReserved' => base64_encode('任意格式的信息都可以'),

            // 其他特殊用法请查看 special_use_purchase.php
        ];

        AcpService::sign($params);
        $uri = SDKConfig::getSDKConfig()->frontTransUrl;

        $htmlForm = AcpService::createAutoFormHtml($params, $uri);

        return [
            'html' => $htmlForm,
        ];
    }

    /**
     * 校验订单是否支付成功(支付平台调用Notify URL即触发该方法)
     *
     * @return bool
     */
    public function verifyNotify()
    {
        if (!isset($this->request['signature'])) {
            $this->logger->info('签名为空');

            return false;
        }

        if (!AcpService::validate($this->request->getParameterReference('post'))) {
            $this->logger->warning('验签失败');

            return false;
        }

        $this->outOrderNo = $this->request['queryId'];
        $this->orderNo = $this->request['orderId'];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function verifyReturn()
    {
        return $this->verifyNotify();
    }

    /**
     * {@inheritdoc}
     */
    public function refund($data = [], array $signData = [])
    {
        /**
         * 重要：联调测试时请仔细阅读注释！
         *
         * 产品：跳转网关支付产品<br>
         * 交易：退货交易：后台资金类交易，有同步应答和后台通知应答<br>
         * 日期： 2015-09<br>
         * 版本： 1.0.0
         * 版权： 中国银联<br>
         * 说明：以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己需要，按照技术文档编写。该代码仅供参考，不提供编码性能规范性等方面的保障<br>
         * 该接口参考文档位置：open.unionpay.com帮助中心 下载  产品接口规范  《网关支付产品接口规范》<br>
         *              《平台接入接口规范-第5部分-附录》（内包含应答码接口规范，全渠道平台银行名称-简码对照表）<br>
         * 测试过程中的如果遇到疑问或问题您可以：1）优先在open平台中查找答案：
         *                                    调试过程中的问题或其他问题请在 https://open.unionpay.com/ajweb/help/faq/list 帮助中心 FAQ 搜索解决方案
         *                             测试过程中产生的7位应答码问题疑问请在https://open.unionpay.com/ajweb/help/respCode/respCodeList 输入应答码搜索解决方案
         *                          2） 咨询在线人工支持： open.unionpay.com注册一个用户并登陆在右上角点击“在线客服”，咨询人工QQ测试支持。
         * 交易说明： 1）以后台通知或交易状态查询交易（Form_6_5_Query）确定交易成功，建议发起查询交易的机制：可查询N次（不超过6次），每次时间间隔2N秒发起,即间隔1，2，4，8，16，32S查询（查询到03，04，05继续查询，否则终止查询）
         *        2）退货金额不超过总金额，可以进行多次退货
         *        3）退货能对11个月内的消费做（包括当清算日），支持部分退货或全额退货，到账时间较长，一般1-10个清算日（多数发卡行5天内，但工行可能会10天），所有银行都支持
         */
        $params = [
            //以下信息非特殊情况不需要改动
            'version' => SDKConfig::getSDKConfig()->version,              //版本号
            'encoding' => 'utf-8',              //编码方式
            'signMethod' => SDKConfig::getSDKConfig()->signMethod,              //签名方法
            'txnType' => '04',                  //交易类型
            'txnSubType' => '00',              //交易子类
            'bizType' => '000201',              //业务类型
            'accessType' => '0',              //接入类型
            'channelType' => '07',              //渠道类型
            'backUrl' => SDKConfig::getSDKConfig()->backUrl, //后台通知地址

            // 以下信息需要填写
            'orderId' => $data['refundId'],        //商户订单号，8-32位数字字母，不能含“-”或“_”，可以自行定制规则，重新产生，不同于原消费，此处默认取demo演示页面传递的参数
            'merId' => $this->merId,            //商户代码，请改成自己的测试商户号，此处默认取demo演示页面传递的参数
            'origQryId' => $data['outOrderNo'], //原消费的queryId，可以从查询接口或者通知接口中获取，此处默认取demo演示页面传递的参数
            'txnTime' => date('YmdHis'),        //订单发送时间，格式为YYYYMMDDhhmmss，重新产生，不同于原消费，此处默认取demo演示页面传递的参数
            'txnAmt' => (string) ($data['refundFee'] * 100),       //交易金额，退货总金额需要小于等于原消费

            // 请求方保留域，
            // 透传字段，查询、通知、对账文件中均会原样出现，如有需要请启用并修改自己希望透传的数据。
            // 出现部分特殊字符时可能影响解析，请按下面建议的方式填写：
            // 1. 如果能确定内容不会出现&={}[]"'等符号时，可以直接填写数据，建议的方法如下。
            //    'reqReserved' =>'透传信息1|透传信息2|透传信息3',
            // 2. 内容可能出现&={}[]"'符号时：
            // 1) 如果需要对账文件里能显示，可将字符替换成全角＆＝｛｝【】“‘字符（自己写代码，此处不演示）；
            // 2) 如果对账文件没有显示要求，可做一下base64（如下）。
            //    注意控制数据长度，实际传输的数据长度不能超过1024位。
            //    查询、通知等接口解析时使用base64_decode解base64后再对数据做后续解析。
            //    'reqReserved' => base64_encode('任意格式的信息都可以'),
        ];

        AcpService::sign($params); // 签名
        $url = SDKConfig::getSDKConfig()->backTransUrl;

        $resultArr = AcpService::post($params, $url);
        $this->logger->info('银联退款返回', [$url, $params, $resultArr]);

        if (count($resultArr) <= 0) { //没收到200应答的情况
            return $this->err('很抱歉，网络请求失败，请再试一次');
        }

        if (!AcpService::validate($resultArr)) {
            return $this->err('很抱歉，应答报文验签失败，请检查支付配置是否正确');
        }

        if ($resultArr['respCode'] == '03'
            || $resultArr['respCode'] == '04'
            || $resultArr['respCode'] == '05'
        ) {
            // 后续需发起交易状态查询交易确定交易状态
            return $this->err('银联返回：处理超时，请稍后再查询。');
        }

        if ($resultArr['respCode'] != '00') {
            return $this->err('银联返回：' . $resultArr['respMsg']);
        }

        return $this->suc(['refundOutId' => $this->request['queryId']] + $data);
    }

    protected function initSDkConfig()
    {
        $config = SDKConfig::getSDKConfig();

        // 公用配置
        $config->signCertPwd = $this->certPassword;
        $config->encryptCertPath = $this->plugin->getById($this->app->getNamespace())->getBasePath()
            . '/configs/union-pay/acp_prod_enc.cer';

        // 开发环境特有配置
        if (!$this->env->isDev()) {
            return;
        }

        foreach (get_object_vars($config) as $name => $value) {
            if (substr($name, -3) == 'Url') {
                $config->$name = str_replace('https://gateway.95516.com/', 'https://gateway.test.95516.com/', $value);
            }
        }

        $config->ifValidateCNName = false;
        $config->ifValidateRemoteCert = false;

        $certDir = $this->plugin->getById('union-pay')->getBasePath() . '/resources/certs';
        $config->signCertPath = $certDir . '/acp_test_sign.pfx';
        $config->encryptCertPath = $certDir . '/acp_test_enc.cer';
        $config->middleCertPath = $certDir . '/acp_prod_middle.cer';
        $config->rootCertPath = $certDir . '/acp_prod_root.cer';

        $config->logLevel = 'DEBUG';
    }
}
