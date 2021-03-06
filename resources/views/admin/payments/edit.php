<?php $view->layout() ?>

<?= $block('header-actions') ?>
<a class="btn btn-secondary" href="<?= $url('admin/payments') ?>">返回列表</a>
<?= $block->end() ?>

<div class="row">
  <div class="col-12">
    <!-- PAGE CONTENT BEGINS -->
    <form id="payment-form" class="form-horizontal" method="post" role="form"
      action="<?= $url('admin/payments/' . $payment->getFormAction()) ?>">
      <div class="form-group hide">
        <label class="col-lg-2 control-label" for="id">
          <span class="text-warning">*</span>
          标识
        </label>

        <div class="col-lg-4">
          <input type="text" class="form-control" name="id" id="id">
        </div>
      </div>

      <div class="form-group">
        <label class="col-lg-2 control-label" for="name">
          <span class="text-warning">*</span>
          名称
        </label>

        <div class="col-lg-4">
          <input type="text" class="form-control" name="name" id="name">
        </div>
      </div>

      <div class="form-group">
        <label class="col-lg-2 control-label" for="image">
          <span class="text-warning">*</span>
          图标
        </label>

        <div class="col-lg-4">
          <input type="text" class="form-control" id="image" name="image">
        </div>
      </div>

      <?php require $view->getFile($paymentService->getFormFile()) ?>

      <div class="form-group">
        <label class="col-lg-2 control-label" for="base-money">
          积分比率
        </label>

        <div class="col-lg-8 form-row align-items-center">
          <div class="col-auto">
            每购买
          </div>
          <div class="col-auto">
            <input type="text" class="form-control float-left text-center t-3" name="baseMoney" id="base-money">
          </div>
          <div class="col-auto">
            元商品,可得积分
          </div>
          <div class="col-auto">
            <input type="text" class="form-control float-left text-center t-3" name="scoreRate" id="score-rate">
          </div>
          <div class="col-auto">
            (不满部分则去除不计)
          </div>
        </div>
      </div>

      <div class="form-group">
        <label class="col-lg-2 control-label" for="sort">
          顺序
        </label>

        <div class="col-lg-4">
          <input type="text" class="form-control" name="sort" id="sort">
        </div>

        <label class="col-lg-6 help-text" for="sort">
          大的显示在前面,按从大到小排列.
        </label>
      </div>

      <div class="form-group">
        <label class="col-lg-2 control-label" for="enable">
          <span class="text-warning">*</span>
          状态
        </label>

        <div class="col-lg-4">
          <label class="radio-inline">
            <input type="radio" name="enable" class="enable" value="1"> 启用
          </label>
          <label class="radio-inline">
            <input type="radio" name="enable" class="enable" value="0"> 禁用
          </label>

        </div>
      </div>

      <input type="hidden" name="type" id="type">

      <div class="clearfix form-actions form-group">
        <div class="offset-lg-2">
          <button class="btn btn-primary" type="submit">
            <i class="fa fa-check bigger-110"></i>
            提交
          </button>
          &nbsp; &nbsp; &nbsp;
          <a class="btn btn-secondary" href="<?= $url('admin/payments') ?>">
            <i class="fa fa-undo bigger-110"></i>
            返回列表
          </a>
        </div>
      </div>
    </form>
  </div>
  <!-- PAGE CONTENT ENDS -->
</div><!-- /.col -->
<!-- /.row -->

<?= $block->js() ?>
<script>
  require(['plugins/payment/js/admin/payments', 'plugins/admin/js/form', 'ueditor'], function (payment) {
    payment.edit({
      data: <?= $payment->toJson() ?>
    });
  });
</script>
<?= $block->end() ?>
