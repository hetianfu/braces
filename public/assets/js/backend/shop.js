define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

  var Controller = {
    index: function () {
      // 初始化表格参数配置
      Table.api.init({
        extend: {
          index_url: 'shop/index' + location.search,
          add_url: 'shop/add',
          edit_url: 'shop/edit',
          del_url: 'shop/del',
          multi_url: 'shop/multi',
          import_url: 'shop/import',
          table: 'shop',
        }
      });

      var table = $("#table");

      // 初始化表格
      table.bootstrapTable({
        url: $.fn.bootstrapTable.defaults.extend.index_url,
        pk: 'id',
        sortName: 'id',
        columns: [
          [
            {checkbox: true},

            {field: 'username', title: __('Username'), operate: 'LIKE'},
            {field: 'mobile', title: __('Mobile'), operate: 'LIKE'},
            {field: 'avatar', title: __('Avatar'), operate: 'LIKE', events: Table.api.events.image, formatter: Table.api.formatter.image},
            {field: 'order_count', title: '订单总数',formatter:Table.api.formatter.label,operate:false,},
            {field: 'order_mouth_count', title: '当月订单数',formatter:Table.api.formatter.label,operate:false,},

            {field: 'stores.name', title: __('Stores.name'), operate: 'LIKE'},
            {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
          ]
        ]
      });

      // 为表格绑定事件
      Table.api.bindevent(table);
    },
    add: function () {
      Controller.api.bindevent();
    },
    edit: function () {
      Controller.api.bindevent();
    },
    api: {
      bindevent: function () {
        Form.api.bindevent($("form[role=form]"));
      }
    }
  };
  return Controller;
});
