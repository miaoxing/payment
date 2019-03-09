<?= $block->css() ?>
<link rel="stylesheet" href="<?= $asset('plugins/payment/css/payments.css') ?>">
<?= $block->end() ?>

<span class="payment-title">请选择支付方式</span>

<ul class="list list-indented payment-list">
  <?php foreach ($payments as $i => $payment) : ?>
  <li>
    <a class="js-payment-item list-item" href="javascript:;">
      <div class="list-col align-self-center payment-checkbox">
        <div class="custom-control custom-checkbox custom-checkbox-success">
          <input class="js-payment-id payType custom-control-input" type="checkbox" name="payType"
            id="pay-type-<?= $payment['id'] ?>"
            value="<?= $payment['id'] ?>" <?= $i == 0 ? 'checked' : '' ?>>
          <label class="custom-control-label" for="pay-type-<?= $payment['id'] ?>"></label>
        </div>
      </div>
      <div class="list-col payment-image">
        <img src="<?= $payment['image'] ?>">
      </div>
      <div class="list-col align-self-center">
        <h4 class="list-title">
          <?= $payment['name'] ?>
        </h4>
      </div>
    </a>
  </li>
  <?php endforeach ?>
</ul>

<?= $block->js() ?>
<script>
  $('.js-payment-item').click(function () {
    $('.js-payment-id').prop('checked', false);
    $(this).find('.js-payment-id').prop('checked', true);
  });
</script>
<?= $block->end() ?>
