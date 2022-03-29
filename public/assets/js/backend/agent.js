define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

  var Controller = {
    index: function () {
      // 初始化表格参数配置
      Table.api.init({
        extend: {
          index_url: 'agent/index',
          add_url: 'agent/add',
          edit_url: 'agent/edit',
          del_url: 'agent/del',
          multi_url: 'agent/multi',
        }
      });

      var table = $("#table");

      //在表格内容渲染完成后回调的事件
      table.on('post-body.bs.table', function (e, json) {
        $("tbody tr[data-index]", this).each(function () {
          if (parseInt($("td:eq(1)", this).text()) == Config.admin.id) {
            $("input[type=checkbox]", this).prop("disabled", true);
          }
        });
      });

      // 初始化表格
      table.bootstrapTable({
        url: $.fn.bootstrapTable.defaults.extend.index_url,
        columns: [
          [
            {field: 'state', checkbox: true, },
            {field: 'username', title: __('Username')},
            {field: 'factory_name', title: '公司名称'},
            {field: 'factory_head', title:'联系人姓名'},
            {field: 'factory_phone', title: '电话'},
            {field: 'address', title: '地址'},
            {field: 'detailed_address', title: '详细地址'},
            {field: 'factory', title: '工厂数量', operate:false, formatter: Table.api.formatter.label},
            {field: 'clinic', title: '诊所数量', operate:false, formatter: Table.api.formatter.label},
            {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}

          ]
        ]
      });

      // 为表格绑定事件
      Table.api.bindevent(table);
    },
    add: function () {
      $(document).on("change", ".dianji", function(){
        //变更后的回调事件
        var group = $(this).val();
        if(group){
          if(group == 6){
            $('.agent').css('display','block');
          }else{
            $('.agent').css('display','none');
          }
        }

      });
      Form.api.bindevent($("form[role=form]"));
    },
    edit: function () {
      $(document).on("change", ".dianji", function(){
        //变更后的回调事件
        var group = $(this).val();
        if(group){
          if(group == 6){
            $('.agent').css('display','block');
          }else{
            $('.agent').css('display','none');
          }
        }

      });
      Form.api.bindevent($("form[role=form]"));
    }
  };
  return Controller;
});
