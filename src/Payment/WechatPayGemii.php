<?php

namespace Miaoxing\Payment\Payment;

class WechatPayGemii extends WechatPayV3
{
    /**
     * {@inheritdoc}
     */
    public function getCertDir()
    {
        return parent::getCertDir() . '/wechatPayGemii';
    }

    protected function getPayUserOpenId()
    {
        return wei()->userGemii()->find(['userId' => wei()->curUser['id']])->get('gemiiOpenId');
    }
}
