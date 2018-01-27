<?php $view->layout() ?>

<div class="page-header">
  <div class="btn-group pull-right">

    <button data-toggle="dropdown" class="btn btn-success dropdown-toggle">
      添加支付接口
      <span class="fa fa-caret-down icon-on-right"></span>
    </button>

    <ul class="dropdown-menu">
      <?php foreach (wei()->payment->getTypes() as $type => $data) : ?>
        <li>
          <a href="<?= $url('admin/payments/new', ['type' => $type]) ?>"><?= $data['displayName'] ?></a>
        </li>
      <?php endforeach ?>
    </ul>

  </div>
  <!-- /btn-group -->

  <h1>
    微商城
    <small>
      <i class="fa fa-angle-double-right"></i>
      支付接口设置
    </small>
  </h1>
</div>

<!-- /.page-header -->

<div class="row">
  <div class="col-xs-12">
    <!-- PAGE CONTENT BEGINS -->
    <div class="table-responsive">
      <table id="record-table" class="record-table table table-bordered table-hover">
        <thead>
        <tr>
          <th>名称</th>
          <th class="t-6">启用</th>
          <th class="t-6">顺序</th>
          <th class="t-6">操作</th>
        </tr>
        </thead>
        <tbody>
        </tbody>
      </table>
    </div>
    <!-- /.table-responsive -->
    <!-- PAGE CONTENT ENDS -->
  </div>
  <!-- /col -->
</div>
<!-- /row -->

<script id="enable-row-tpl" type="text/html">
  <label><input type="checkbox" class="ace table-input" name="enable" data-id="<%= id %>"
      value="<%= enable %>" <% if (enable == 1) { %>checked<% } %>>
    <span class="lbl"></span>
  </label>
</script>

<script id="table-actions" type="text/html">
  <div class="action-buttons">
    <a href="<%= $.url('admin/payments/edit', {id: id}) %>" title="编辑">
      配置
    </a>
  </div>
</script>
<?php require $this->getFile('product:admin/products/richInfo.php') ?>

<?= $block->js() ?>
<script>
  require(['dataTable', 'form', 'jquery-deparam'], function () {
    var recordTable = $('#record-table').dataTable({
      ajax: {
        url: $.url('admin/payments.json')
      },
      columns: [
        {
          data: 'name',
          sClass: 'text-left'
        },
        {
          data: 'enable',
          render: function (data, type, full) {
            return template.render('enable-row-tpl', full);
          }
        },
        {
          data: 'sort',
          sClass: 'text-center'
        },
        {
          data: 'id',
          sClass: 'text-center',
          render: function (data, type, full) {
            return template.render('table-actions', full)
          }
        }
      ]
    });

    $('#search-form').update(function () {
      recordTable.reload($(this).serialize());
    });

    recordTable.inlineEdit(function (data) {
      $.post($.url('admin/payments/update', data), function (result) {
        $.msg(result);
      }, 'json');
    });
  });
</script>
<?= $block->end() ?>
