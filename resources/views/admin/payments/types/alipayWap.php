<div id="attrs">
  <div class="form-group">
    <label class="col-lg-2 control-label" for="attrs-app-id">
      <span class="text-warning">*</span>
      APPID
    </label>
    <div class="col-lg-4">
      <!-- htmllint id-class-style="false" -->
      <input type="text" class="form-control appId" name="attrs[appId]" id="attrs-app-id">
      <!-- htmllint id-class-style="$previous" -->
    </div>
    <label class="col-lg-6">
      通过<a href="https://docs.open.alipay.com/200/105310" target="_blank">蚂蚁金服开放平台</a>申请
    </label>
  </div>

  <div class="form-group">
    <label class="col-lg-2 control-label" for="attrs-private-key">
      <span class="text-warning">*</span>
      应用私钥
    </label>
    <div class="col-lg-4">
      <!-- htmllint id-class-style="false" -->
      <textarea type="text" class="form-control privateKey" name="attrs[privateKey]" id="attrs-private-key"></textarea>
      <!-- htmllint id-class-style="$previous" -->
    </div>
  </div>

  <div class="form-group">
    <label class="col-lg-2 control-label" for="attrs-alipay-public-key">
      <span class="text-warning">*</span>
      支付宝公钥
    </label>
    <div class="col-lg-4">
      <!-- htmllint id-class-style="false" -->
      <textarea class="form-control alipayPublicKey" name="attrs[alipayPublicKey]"
        id="attrs-alipay-public-key"></textarea>
      <!-- htmllint id-class-style="$previous" -->
    </div>
  </div>
</div>
