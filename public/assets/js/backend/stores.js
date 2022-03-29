define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

  var Controller = {
    index: function () {
      // 初始化表格参数配置
      Table.api.init({
        extend: {
          index_url: 'stores/index' + location.search,
          add_url: 'stores/add',
          edit_url: 'stores/edit',
          del_url: 'stores/del',
          multi_url: 'stores/multi',
          import_url: 'stores/import',
          table: 'stores',
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
            {field: 'id', title: __('Id')},
            {field: 'name', title: __('Name'), operate: 'LIKE'},
            {field: 'address',title:'地区信息', operate: 'LIKE'},

            {field: 'receiving_name', title: '负责人名字', operate: 'LIKE'},
            {field: 'receiving_mobile', title: '负责人电话', operate: 'LIKE'},

            {field: 'order_count', title: '病例总数',formatter:Table.api.formatter.label,operate:false,},
            {field: 'order_mouth_count', title: '本月病例数',formatter:Table.api.formatter.label,operate:false,},
            {field: 'factory', title: '所属工厂',formatter:Table.api.formatter.label,operate:false,},
            {field: 'agent', title: '所属代理商',formatter:Table.api.formatter.label,operate:false,},
            {field: 'account_count', title: '账号数量',formatter:Table.api.formatter.label,operate:false,},

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
