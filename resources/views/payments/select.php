<?php $isWechat = wei()->ua->isWeChat() ?>

<?= $block('css') ?>
<link rel="stylesheet" href="<?= $asset('plugins/payment/css/payments.css') ?>">
<?= $block->end() ?>

<span class="payment-title">请选择支付方式</span>

<ul class="list list-link list-indented payment-list">
  <?php foreach ($payments as $i => $payment):
  if (isset($isWechat) && !$isWechat && $payment['name'] == '微信支付'):
    continue;
  endif
  ?>
  <li>
    <a class="js-payment-item list-item" href="javascript:;">
      <div class="list-col list-middle payment-checkbox">
        <div class="checkbox checkbox-circle checkbox-success">
          <label>

            <input class="js-payment-id payType" type="checkbox" name="payType"
                   value="<?= $payment['id'] ?>" <?= $i == 0 ? 'checked' : '' ?>>
            <span class="checkbox-label"></span>
          </label>
        </div>
      </div>
      <div class="list-col list-col-left payment-image">
        <img src="<?= $payment['image'] ?>">
      </div>
      <div class="list-col list-middle">
        <h4 class="list-heading">
          <?= $payment['name'] ?>
        </h4>
      </div>
    </a>
  </li>
  <?php endforeach ?>
</ul>

<?= $block('js') ?>
<script>
  $('.js-payment-item').click(function () {
    $('.js-payment-id').prop('checked', false);
    $(this).find('.js-payment-id').prop('checked', true);
  });
</script>
<?= $block->end() ?>
