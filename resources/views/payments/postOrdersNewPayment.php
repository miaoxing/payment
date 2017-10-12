<ul class="list">
  <li class="js-balance-item balance-item order-form-group list-item">
    <h4 class="list-heading">
      使用 <input class="js-balance-amount balance-amount order-form-input" type="text" name="balanceAmount"
        value="<?= $balanceAmount ?>"> 元余额支付
      <small class="nowrap">余额<?= $curUser['money'] ?>元</small>
    </h4>
  </li>
</ul>

<?= $block('js') ?>
<script>
  require(['assets/numeric'], function (numeric) {
    // 订单要超过多少才能使用余额
    var minAmount = '1.00';
    var userMoney = <?= $curUser['money'] ?>;
    var $amount = $('.js-balance-amount');

    var setBalanceAmountRule = function (showErr) {
      orders.removeAmountRule('balance');
      var amounts = orders.calAmounts();

      var balance = $amount.val();
      var maxBalance = numeric.subFloat(amounts.amount, minAmount);

      if (balance > maxBalance) {
        if (maxBalance <= 0) {
          showErr && $.err('订单金额需超过' + minAmount + '元才可使用余额');
          balance = '0.00';
        } else {
          balance = maxBalance.toFixed(2);
          showErr && $.err('最多可使用' + balance + '元余额');
        }
        $amount.val(balance);
      }

      orders.setAmountRule('balance', {
        name: '余额支付',
        amountOff: balance
      });
    };

    $(document).on('beforeApplyAmountRule', function () {
      setBalanceAmountRule(false);
    });

    $amount.change(function () {
      var $this = $(this);

      // 1. 检查金额是否合法
      var balance = parseFloat($this.val());
      if (isNaN(balance)) {
        balance = 0;
      }

      // 2. 检查金额不能超过余额
      if (balance > userMoney) {
        $.err('使用金额不能大于您当前的余额');
        balance = userMoney;
      }

      // 3. 更新金额
      $this.val(balance.toFixed(2));
      setBalanceAmountRule(true);
      orders.applyAmountRule();
    });
  });
</script>
<?= $block->end() ?>
