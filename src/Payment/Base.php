<?php

namespace Miaoxing\Payment\Payment;

use Miaoxing\Order\Service\Order;

/**
 * TODO Payment服务和Order模型是否要关联起来?
 *
 * @property \Wei\Logger $logger
 */
abstract class Base extends \miaoxing\plugin\BaseService
{
    /**
     * @var \Miaoxing\Payment\Service\Payment
     */
    protected $record;

    protected $status;

    /**
     * 支付平台的订单编号
     *
     * @var string
     */
    protected $outOrderNo;

    /**
     * 订单名称
     *
     * @var string
     */
    protected $orderName;

    /**
     * 订单编号
     *
     * @var string
     */
    protected $orderNo;

    /**
     * 完整的订单号,包含订单编号和更新时间
     *
     * 现在用于微信的不能更改支付金额的处理
     *
     * @var string
     * @todo 待改为id
     */
    protected $fullOrderNo;

    /**
     * 订单金额
     *
     * @var float
     */
    protected $orderAmount;

    /**
     * 支付平台通过*后台*通知支付结果的URL地址
     *
     * @var string
     */
    protected $notifyUrl;

    /**
     * 支付完成后,支付平台通过*前台*跳转回来的URL地址
     *
     * @var string
     */
    protected $returnUrl;

    /**
     * 错误提示信息
     *
     * @var string
     */
    protected $message;

    /**
     * @param $result
     * @return string
     */
    public function response($result)
    {
        return $result ? 'success' : 'fail';
    }

    public function getOrderNo()
    {
        return $this->orderNo;
    }

    public function getOutOrderNo()
    {
        return $this->outOrderNo;
    }

    public function getFullOrderNo()
    {
        return $this->fullOrderNo;
    }

    public function getStatus()
    {
        return $this->status;
    }

    /**
     * 返回错误提示信息
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * 提交订单到支付平台
     *
     * @param array $options
     * @return array
     */
    abstract public function submit(array $options);

    /**
     * 校验订单是否支付成功(支付平台调用Notify URL即触发该方法)
     *
     * @return bool
     */
    abstract public function verifyNotify();

    /**
     * 校验支付平台返回到Return URL的数据是否正确
     *
     * @return bool
     */
    abstract public function verifyReturn();

    /**
     * 创建支付数据,如提交到表单的数据,微信JS支付的数据
     *
     * @param Order $order
     * @return array
     */
    public function createPayData(Order $order)
    {
        return [];
    }

    /**
     * 退款接口
     * @param array $data
     * @param array $signData
     * @return array
     */
    public function refund($data = [], array $signData = [])
    {
        return ['code' => 1, 'message' => '无退款逻辑,操作成功', 'refundOutId' => 0];
    }

    /**
     * 获取所有的退款单
     */
    public function getRefunds()
    {
        return $this;
    }

    /**
     * 获取对账单
     */
    public function getCheckSheet()
    {
        return $this;
    }

    /**
     * 通知支付平台,该订单已经发货
     *
     * @param Order $order
     * @return array
     */
    public function ship(Order $order)
    {
        return ['code' => 1, 'message' => '发货成功'];
    }
}
