<?php

namespace app\admin\controller;

use app\admin\model\Admin;
use app\admin\model\AuthGroupAccess;
use app\admin\model\User;
use app\common\controller\Backend;
use app\common\model\Attachment;
use fast\Date;
use think\Db;

/**
 * 控制台
 *
 * @icon   fa fa-dashboard
 * @remark 用于展示当前系统中的统计数据、统计报表及重要实时数据
 */
class Dashboard extends Backend
{

    /**
     * 查看
     */
    public function index()
    {
      //判断权限  超级管理员1  代理商2  工厂6
      $admin_id = $this->auth->id;
      $group_id = AuthGroupAccess::where('uid',$admin_id)->value('group_id');
      if($group_id == 1){
        $agent = AuthGroupAccess::where('group_id',2)->count();
        $factory = AuthGroupAccess::where('group_id',6)->count();
        $clinic = \app\admin\model\Stores::count();
        $order = \app\admin\model\Order::count();
        $order_day = \app\admin\model\Order::whereTime('createtime', 'today')->count();
      }elseif ($group_id == 2){
        $agent = 0;
        $factory = Admin::where('agent_id',$admin_id)->count();
        $factory_ids = Admin::where('agent_id',$admin_id)->column('id');
        $clinic = \app\admin\model\Stores::where('factory_id','in',$factory_ids)->count();
        $order = \app\admin\model\Order::where('agent_id',$admin_id)->count();
        $order_day = \app\admin\model\Order::where('agent_id',$admin_id)->whereTime('createtime', 'today')->count();
      }else{
        $agent = 0;
        $factory = 0;
        $clinic = \app\admin\model\Stores::where('factory_id',$admin_id)->count();
        $order = \app\admin\model\Order::where('factory_id',$admin_id)->count();
        $order_day = \app\admin\model\Order::where('factory_id',$admin_id)->whereTime('createtime', 'today')->count();
      }


      $this->view->assign([
            'agent'       => $agent,//代理商
            'factory'       => $factory,//工厂
            'clinic'       => $clinic,//诊所
            'order'       => $order,//病例
            'order_day'       => $order_day,//今日病例
        ]);


        return $this->view->fetch();
    }

}
