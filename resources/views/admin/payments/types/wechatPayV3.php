<div id="attrs">
  <div class="form-group">
    <label class="col-lg-2 control-label" for="attrs-app-id">
      <span class="text-warning">*</span>
      公众账号ID (appid)
    </label>

    <div class="col-lg-4">
      <!-- htmllint id-class-style="false" -->
      <input type="text" class="form-control appId" name="attrs[appId]" id="attrs-app-id">
      <!-- htmllint id-class-style="$previous" -->
    </div>
  </div>

  <div class="form-group">
    <label class="col-lg-2 control-label" for="attrs-app-key">
      <span class="text-warning">*</span>
      API密钥
    </label>

    <div class="col-lg-4">
      <!-- htmllint id-class-style="false" -->
      <input type="text" class="form-control appKey" name="attrs[appKey]" id="attrs-app-key">
      <!-- htmllint id-class-style="$previous" -->
    </div>
    <label class="col-lg-6 help-text" for="attrs-mch-id">
      商户平台中API密钥
    </label>
  </div>

  <div class="form-group">
    <label class="col-lg-2 control-label" for="attrs-mch-id">
      <span class="text-warning">*</span>
      商户号(mch_id)
    </label>

    <div class="col-lg-4">
      <!-- htmllint id-class-style="false" -->
      <input type="text" class="form-control mchId" name="attrs[mchId]" id="attrs-mch-id">
      <!-- htmllint id-class-style="$previous" -->
    </div>
    <label class="col-lg-6 help-text" for="attrs-mch-id">
      微信支付分配的商户号
    </label>
  </div>

  <div class="form-group">
    <label class="col-lg-2 control-label">
      支付授权目录
    </label>

    <div class="col-lg-4">
      <p class="form-control-plaintext"><?= $req->getUrlFor($url('orders/')) ?></p>
    </div>

    <label class="col-lg-6 help-text">
      填写到微信公众平台 【微信支付】-【开发配置】-【支付授权目录】
    </label>
  </div>
</div>
