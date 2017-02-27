<div id="attrs">
  <div class="form-group">
    <label class="col-lg-2 control-label" for="attrs-appId">
      <span class="text-warning">*</span>
      公众账号ID (appid)
    </label>

    <div class="col-lg-4">
      <input type="text" class="form-control appId" name="attrs[appId]" id="attrs-appId">
    </div>
  </div>

  <div class="form-group">
    <label class="col-lg-2 control-label" for="attrs-appKey">
      <span class="text-warning">*</span>
      API密钥
    </label>

    <div class="col-lg-4">
      <input type="text" class="form-control appKey" name="attrs[appKey]" id="attrs-appKey">
    </div>
    <label class="col-lg-6 help-text" for="attrs-mchId">
      商户平台中API密钥
    </label>
  </div>

  <div class="form-group">
    <label class="col-lg-2 control-label" for="attrs-mchId">
      <span class="text-warning">*</span>
      商户号(mch_id)
    </label>

    <div class="col-lg-4">
      <input type="text" class="form-control mchId" name="attrs[mchId]" id="attrs-mchId">
    </div>
    <label class="col-lg-6 help-text" for="attrs-mchId">
      微信支付分配的商户号
    </label>
  </div>

  <div class="form-group">
    <label class="col-lg-2 control-label">
      支付授权目录
    </label>

    <div class="col-lg-4">
      <p class="form-control-static"><?= $req->getUrlFor($url('orders/')) ?></p>
    </div>

    <label class="col-lg-6 help-text">
      填写到微信公众平台 【微信支付】-【开发配置】-【支付授权目录】
    </label>
  </div>
</div>
