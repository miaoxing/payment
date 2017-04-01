define(function () {
  var Payment = function () {
    // do nothing.
  };

  $.extend(Payment.prototype, {
    data: {},

    // 编辑
    // ====
    edit: function (options) {
      $.extend(this, options);

      $('#payment-form')
        .loadJSON(this.data)
        .loadParams()
        .ajaxForm({
          dataType: 'json'
        });

      // 点击选择图片
      $('#image').imageInput();
    }
  });

  return new Payment();
});
