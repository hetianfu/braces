define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

  var Controller = {
    index: function () {
      // 初始化表格参数配置
      Table.api.init({
        extend: {
          index_url: 'order/index' + location.search,
          add_url: 'order/add',
          edit_url: 'order/edit',
          del_url: 'order/del',
          multi_url: 'order/multi',
          import_url: 'order/import',
          table: 'order',
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
            {field: 'order_id', title: __('Order_id'), operate: 'LIKE'},
            {field: 'user.username', title: '客户姓名', operate: 'LIKE',operate:false},
            {field: 'user.mobile', title:'客户电话', operate: 'LIKE',operate:false},

            {field: 'status', title:'状态', searchList: {'1':'待寄模具', 2:'待出方案',3:'待客户确认',4:'待付款',5:'周期发货',6:'订单完成'},formatter: Table.api.formatter.label},



            {field: 'agent_id', title: '代理商', align: 'left',searchList: $.getJSON("order/agent_list") },
            {field: 'factory_id', title: '工厂', align: 'left', searchList: $.getJSON("order/factory_list")},
            {field: 'stores.name', title: '门诊', operate: 'LIKE',operate:false},
            {field: 'cycle_text', title: '周期/已发周期', operate: 'LIKE',operate:false,formatter: Table.api.formatter.label},
            {field: 'shop.username', title: '所属医生', operate: 'LIKE',operate:false},
            {field: 'shop.mobile', title:'医生电话', operate: 'LIKE',operate:false},
            {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
            {
              field: 'buttons',
              width: "320px",
              title: '详情',
              table: table,
              events: Table.api.events.operate,
              buttons: [
                {
                  name: '查看',
                  text: '详情',
                  title: '详情',
                  classname: 'btn btn-xs btn-primary btn-dialog',
                  icon: 'fa fa-list',
                  url: 'order/detail',
                  callback: function (data) {
                  },
                  visible: function (row) {
                    //返回true时按钮显示,返回false隐藏
                    return true;
                  }
                }
              ],
              formatter: Table.api.formatter.buttons
            }
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
    detail: function () {
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
