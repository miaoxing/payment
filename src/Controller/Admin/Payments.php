<?php

namespace Miaoxing\Payment\Controller\Admin;

class Payments extends \miaoxing\plugin\BaseController
{
    protected $adminNavId = 'orders';

    protected $controllerName = '支付接口管理';

    protected $actionPermissions = [
        'index' => '列表',
        'new,create' => '添加',
        'edit,update' => '编辑',
        'destroy' => '删除',
    ];

    public function indexAction($req)
    {
        switch ($req['_format']) {
            case 'json':
                $payments = wei()->payment();

                $payments
                    ->notDeleted()
                    ->limit($req['rows'])
                    ->page($req['page'])
                    ->desc('sort');

                $data = $payments->findAll()->toArray();

                return $this->json('读取列表成功', 1, array(
                    'data' => $data,
                    'page' => $req['page'],
                    'rows' => $req['rows'],
                    'records' => $payments->count(),
                ));

            default:
                return get_defined_vars();
        }
    }

    public function newAction($req)
    {
        return $this->editAction($req);
    }

    public function createAction($req)
    {
        return $this->updateAction($req);
    }

    public function editAction($req)
    {
        $payment = wei()->payment()->findOrInitById($req['id'], $req);

        if (!$payment->isSupport($payment['type'])) {
            throw new \Exception('不支持该支付接口', 404);
        }

        if ($payment->isNew()) {
            $payment->loadDefaultDataByType();
        }

        return get_defined_vars();
    }

    public function updateAction($req)
    {
        $payment = wei()->payment()->findOrInitById($req['id']);

        $payment->save($req);

        return $this->suc();
    }

    public function destroyAction($req)
    {
        $payment = wei()->payment()->findOneById($req['id']);

        $payment->softDelete();

        return $this->suc();
    }
}
