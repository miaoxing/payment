define(['jquery-form'], function () {
  var Payments = function () {

  };

  /**
   * 支付失败的跳转地址
   */
  Payments.prototype.errorUrl = '';

  /**
   * 创建订单的结果
   */
  Payments.prototype.result = null;

  /**
   * 用于控制订单只提交一次
   */
  Payments.prototype.beforeSubmit = function () {
    if (this.result != null) {
      this.submit(this.result);
      return false;
    }
  };

  /**
   * 用于订单创建后,发起支付请求
   */
  Payments.prototype.success = function (ret) {
    if (ret.code === 1) {
      this.result = ret;
    } else {
      this.result = null;
    }
    this.submit(ret);
  };

  /**
   * 提交订单结果到支付平台
   */
  Payments.prototype.submit = function (ret) {
    if (typeof ret.errorUrl != 'undefined') {
      this.errorUrl = ret.errorUrl;
    }

    // 提交订单失败,提示错误信息
    if (ret.code < 1) {
      return $.err(ret.message);
    }

    var payType = ret.order.payType;
    if (payType == 'weChatPay' || payType == 'wechatPayV3') {
      // 微信支付,调用支付接口
      this.weChatPay(ret.payment, ret.id, payType);
    } else {
      // 其他支付,跳转到提交支付页面
      window.location = $.url('mall/payment/submit', {
        id: ret.id,
        errorUrl: this.errorUrl
      });
    }
  };

  /**
   * 同步检查订单是否已支付
   */
  Payments.prototype.isPaid = function (id) {
    var paid = false;
    $.ajax({
      async: false,
      dataType: 'json',
      global: false,
      url: $.url('orders/%s.json', id),
      success: function (ret) {
        paid = (ret.data.paid == '1');
      }
    });
    return paid;
  };

  Payments.prototype.weChatPay = function (options, id, payType) {
    var self = this;

    // 统一下单可能出错,因此增加提示,同时兼容旧版没有code字段
    if (typeof options.code != 'undefined' && options.code < 1) {
      alert(options.message);
      return;
    }

    if (typeof WeixinJSBridge == 'undefined') {
      alert('很抱歉,检测不到微信支付接口,请刷新页面,再试一次');
      return;
    }

    if (options.type == 'js') {
      WeixinJSBridge.invoke('getBrandWCPayRequest', options.js, function (res) {
        if (res.err_msg == 'get_brand_wcpay_request:ok') {
          self.weChatPaySuc(id, 5, true, payType);
        } else {
          $.log(res.err_msg);
          self.weChatPayErr(id);
        }
      });
    } else {
      window.location = options.native;
      this.weChatPaySuc(id, 1000, false, payType);
    }
  };

  /**
   * 循环向后台发送请求,检查是否支付成功
   */
  Payments.prototype.weChatPaySuc = function (id, maxTimes, showMessage, payType) {
    var self = this;
    var time = 1;
    maxTimes = maxTimes || 5;

    var checkPay = function () {
      // Loading效果
      if (showMessage !== false) {
        $.loading({content: '从微信获取支付结果...第' + time + '次'}).loading('show');
      }

      // 支付成功
      if (self.isPaid(id) == true) {
        $.loading('hide');
        window.location = $.url('mall/payment/result/' + payType, {orderNo: id});
        return;
      }

      // 还未支付成功,继续检查
      if (time < maxTimes) {
        setTimeout(checkPay, 2000);
        time++;
        return;
      }

      // 支付失败
      $.loading('hide');
      if (showMessage !== false) {
        $.alert('获取微信支付结果失败,请稍后再查询订单或联系我们');
      }
    };

    checkPay();
  };

  /**
   * 支付失败,跳转到错误提醒页面
   */
  Payments.prototype.weChatPayErr = function (id) {
    window.location = this.errorUrl || $.url('orders/%s', id);
  };

  return new Payments();
});
