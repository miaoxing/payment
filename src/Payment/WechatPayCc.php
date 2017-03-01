<?php

namespace Miaoxing\Payment\Payment;

class WechatPayCc extends WechatPayV3
{
    /**
     * {@inheritdoc}
     */
    public function getCertDir()
    {
        return parent::getCertDir() . '/wechatPayCc';
    }
}
