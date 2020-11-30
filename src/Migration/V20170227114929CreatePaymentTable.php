<?php

namespace Miaoxing\Payment\Migration;

use Wei\Migration\BaseMigration;

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
            ->uInt('sort')
            ->string('image')
            ->uDecimal('baseMoney', 10)
            ->uDecimal('scoreRate', 8)->defaults('1.00')->comment('每消费一元获得多少积分')
            ->timestamps()
            ->userstamps()
            ->softDeletable()
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
