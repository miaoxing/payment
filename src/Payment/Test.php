<?php

namespace Miaoxing\Payment\Payment;

/**
 * @property \Wei\Request $request
 */
class Test extends Base
{
    protected $type = 'test';

    public function submit(array $options)
    {
        $this->setOption($options);

        // 构造请求参数
        $this->request->set([
            'type' => $this->type,
            'orderNo' => $this->orderNo,
        ]);

        // 调用mall/payment/notify接口,通知支付成功,使其更新订单状态为成功
        wei()->app->dispatch('mall/payment', 'notify');

        // 跳转到返回地址
        return wei()->response->redirect($this->returnUrl . '?' . http_build_query(['orderNo' => $this->orderNo]));
    }

    public function verifyNotify()
    {
        $this->orderNo = $this->request['orderNo'];

        return true;
    }

    public function verifyReturn()
    {
        return $this->verifyNotify();
    }

    /**
     * 测试支付返回空数据,避免页面布局改变
     */
    public function response($result)
    {
    }
}
