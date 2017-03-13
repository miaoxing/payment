<?php

namespace Miaoxing\Payment\Migration;

use Miaoxing\Plugin\BaseMigration;

class V20170227114929CreatePaymentTable extends BaseMigration
{
    /**
     * {@inheritdoc}
     */
    public function up()
    {
        $this->schema->table('payment')
            ->string('id', 32)
            ->string('name', 16)
            ->string('type', 16)
            ->string('platform', 32)
            ->text('attrs')
            ->bool('enable')
            ->int('sort')
            ->string('image')
            ->decimal('baseMoney', 10)
            ->decimal('scoreRate', 8)->defaults('1.00')->comment('每消费一元获得多少积分')
            ->timestampsV1()
            ->userstampsV1()
            ->softDeletableV1()
            ->primary('id')
            ->exec();

    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        $this->schema->dropIfExists('payment');
    }
}
