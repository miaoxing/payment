<div id="attrs">
  <div class="form-group">
    <label class="col-lg-2 control-label" for="attrs-mer-id">
      <span class="text-warning">*</span>
      商户号
    </label>
    <div class="col-lg-4">
      <!-- htmllint id-class-style="false" -->
      <input type="text" class="form-control merId" name="attrs[merId]" id="attrs-mer-id">
      <!-- htmllint id-class-style="$previous" -->
    </div>
    <label class="col-lg-6">
      通过<a href="https://open.unionpay.com/ajweb/product/detail?id=66" target="_blank">银联</a>申请
    </label>
  </div>

  <div class="form-group">
    <label class="col-lg-2 control-label" for="attrs-cert-password">
      <span class="text-warning">*</span>
      证书密码
    </label>
    <div class="col-lg-4">
      <!-- htmllint id-class-style="false" -->
      <input type="text" class="form-control certPassword" name="attrs[certPassword]" id="attrs-cert-password">
      <!-- htmllint id-class-style="$previous" -->
    </div>
  </div>

  <div class="form-group">
    <label class="col-lg-2 control-label" for="attrs-test-mode">
      <span class="text-warning">*</span>
      测试模式
    </label>
    <div class="col-lg-4">
      <!-- htmllint id-class-style="false" -->
      <label class="radio-inline">
        <input type="radio" class="testMode" name="attrs[testMode]" id="attrs-test-mode" value="1"> 开启
      </label>
      <label class="radio-inline">
        <input type="radio" class="testMode" name="attrs[testMode]" id="attrs-test-mode-2" value="0" checked> 关闭
      </label>
      <!-- htmllint id-class-style="$previous" -->
    </div>
    <label for="attrs-test-mode">
      开启后，可以使用银联提供的<a href="https://open.unionpay.com/ajweb/account/testPara" target="_blank">测试参数</a>来体验支付流程
    </label>
  </div>

  <div class="form-group">
    <label class="col-lg-2 control-label">
      <span class="text-warning">*</span>
      商户私钥证书
    </label>
    <div class="col-lg-4">
      <p class="form-control-static">请将"商户私钥证书"(pfx格式)提供给开发人员部署</p>
    </div>
  </div>
</div>
