<?php

namespace app\shop\controller;

use addons\kdniao\Kdniao;
use app\admin\model\Admin;
use app\common\controller\Api;
use app\common\model\OrderLog;
use app\common\model\User;
use fast\Courier;
use think\Db;
use think\Exception;

/**
 * 订单
 */
class Order extends Api
{
  protected $noNeedLogin = [];
  protected $noNeedRight = ['*'];

  /**
   * 创建病例
   *
   */
  public function index()
  {
    $shop = $this->auth->getShop();
    $shop_id = $shop->id;
    $store_id = $shop->store_id;



    $username = $this->request->noEmptyPost('username');
    $gender = $this->request->noEmptyPost('gender');
    $mobile = $this->request->noEmptyPost('mobile');
    // $address = $this->request->post('address');

    $oriented_zm = $this->request->noEmptyPost('oriented_zm');
    $oriented_zmwx = $this->request->noEmptyPost('oriented_zmwx');
    $oriented_cm = $this->request->noEmptyPost('oriented_cm');

    $oral_sehm = $this->request->noEmptyPost('oral_sehm');
    $oral_xehm = $this->request->noEmptyPost('oral_xehm');
    $oral_zm = $this->request->noEmptyPost('oral_zm');
    $instructions = $this->request->post('instructions');//说明

    $oral_cm = '';

    $oral_yc = $this->request->noEmptyPost('oral_yc');
    $oral_zc = $this->request->noEmptyPost('oral_zc');


    $x_zm = $this->request->post('x_zm');
    $x_cm = $this->request->post('x_cm');


    $order_id = $this->request->post('order_id');//修改的时候传


    Db::startTrans();
    try{
      $ip = request()->ip();
      $user_data =  array(
        'store_id'=>$shop_id,
        'username'=>$username,
        'mobile'=>$mobile,
        'joinip'=>$ip,
        'gender'=>$gender,
      );


      $order_data = array(
        'oriented_zm'=>$oriented_zm,
        'oriented_zmwx'=>$oriented_zmwx,
        'oriented_cm'=>$oriented_cm,
        'oral_sehm'=>$oral_sehm,
        'oral_xehm'=>$oral_xehm,
        'oral_zm'=>$oral_zm,
        'oral_cm'=>$oral_cm,
        'oral_yc'=>$oral_yc,
        'oral_zc'=>$oral_zc,
        'x_zm'=>$x_zm,
        'x_cm'=>$x_cm,
        'instructions'=>$instructions,
      );
      if($order_id){
        //修改

        $order_info = \app\common\model\Order::where('order_id',$order_id)->find();
        if($order_info['status'] > 3){
          $this->error('当前状态修改');
        }


        $user = User::update($user_data,['id'=>$order_info['user_id']]);
        $order =  \app\common\model\Order::update($order_data,['order_id'=>$order_id]);
      }else{
        //创建
        //新增用户
        $order_id = date('YmdHi').substr(microtime(), 2, 5) . mt_rand(10000,99999);
        $user_res = User::create($user_data);
        $user_id = $user_res->id;
        $order_data['user_id'] = $user_id;
        $order_data['order_id'] = $order_id;
        $order_data['shop_id'] = $shop_id;
        $order_data['stores_id'] = $store_id;

        $stores = \app\common\model\Stores::where('id',$store_id)->find();
        $order_data['factory_id'] = $stores['factory_id'];

        $agent = Admin::where('id',$stores['factory_id'])->find();
        //代理商id
        $order_data['agent_id'] = $agent['agent_id'];

        $order_res = \app\common\model\Order::create($order_data);
      }


      Db::commit();
      $this->success('操作成功');
    }catch (Exception $e){
      Db::rollback();
      $this->error($e->getMessage());
    }

  }




  public  function getInfo(){
    $order_id = $this->request->noEmptyPost('order_id');//修改的时候传

    $order_info = \app\common\model\Order::where('order_id',$order_id)->find();
    if(!$order_info){
      $this->error('订单不存在');
    }


    $user = User::where('id',$order_info['user_id'])->find();
    if(!$user){
      $this->error('用户不存在');
    }

    $order_data = array(
      'oriented_zm'=>$order_info['oriented_zm'],
      'oriented_zmwx'=>$order_info['oriented_zmwx'],
      'oriented_cm'=>$order_info['oriented_cm'],
      'oral_sehm'=>$order_info['oral_sehm'],
      'oral_xehm'=>$order_info['oral_xehm'],
      'oral_zm'=>$order_info['oral_zm'],
      'oral_cm'=>$order_info['oral_cm'],
      'oral_yc'=>$order_info['oral_yc'],
      'oral_zc'=>$order_info['oral_zc'],
      'order_id'=>$order_info['order_id'],
      'instructions'=>$order_info['instructions'],
    );

    $user_data =  array(
      'username'=>$user['username'],
      'mobile'=>$user['mobile'],
      'gender'=>$user['gender'],
    );

    $data['user'] = $user_data;
    $data['order_info'] = $order_data;

    $this->success('查询成功',$data);
  }




  //订单列表
  public function orderList(){
    $limit = $this->request->param('limit',15);
    $key = $this->request->param('key','');
    $class_id = $this->request->param('class_id','');


    $shop = $this->auth->getShop();
    $shop_id = $shop->id;
    $store_id = $shop->store_id;


    $where = ' 1=1 ';

    if($key){
      //搜索订单标号或者客户名 手机号
      $where = 'order.order_id like "%'.$key.'%"  or user.username  like "%'.$key.'%"  or user.mobile  like "%'.$key.'%"  ';
    }

    $cla_where = [];

    if(!$shop->is_marage){
      //不是店长 只能看到自己的
      $cla_where['shop_id'] = $shop_id;
    }
    $cla_where['stores_id'] = $store_id;


    if($class_id){
      $cla_where['order.status'] = $class_id;
    }
    //'id,username,mobile,gender'
    $data = \app\common\model\Order::alias('order')->where('stores_id',$store_id)
      ->where($where)
      ->where($cla_where)
      ->join('user','user.id=order.user_id','left')
      ->field('order.id,username,mobile,gender,current_cycle,order.user_id,order.status,order_id,order.amount,cycle,order.createtime')
      ->order('order.id','desc')
      ->paginate($limit)
      ->each(function($item, $key){
        $item['status_text'] = \app\common\model\Order::getOrderStatus($item['status']);
        $item['time_text'] = date('Y-m-d H:i',$item['createtime']);
        return $item;
      });

    $list['list'] = $data;
    $list['class_arr'] = \app\common\model\Order::getOrderStatus(-1);
    $this->success('查询成功',$list);

  }






  //订单病例详情
  public function info(){
    $order_id = $this->request->param('order_id');//修改的时候传



    $shop = $this->auth->getShop();
    $shop_id = $shop->id;
    $store_id = $shop->store_id;

    $order_info = \app\common\model\Order::where('order_id',$order_id)->find();

    if(!$order_info){
      $this->error('订单不存在');
    }


    $user = User::where('id',$order_info['user_id'])->find();
    if(!$user){
      $this->error('用户不存在');
    }

    $order_data = array(
      'oriented_zm'=>$order_info['oriented_zm'],
      'oriented_zmwx'=>$order_info['oriented_zmwx'],
      'oriented_cm'=>$order_info['oriented_cm'],
      'oral_sehm'=>$order_info['oral_sehm'],
      'oral_xehm'=>$order_info['oral_xehm'],
      'oral_zm'=>$order_info['oral_zm'],
      'oral_cm'=>$order_info['oral_cm'],
      'oral_yc'=>$order_info['oral_yc'],
      'oral_zc'=>$order_info['oral_zc'],
    
      'x_zm'=>$order_info['x_zm'],
      'x_cm'=>$order_info['x_cm'],
      
      'order_id'=>$order_info['order_id'],
      'state'=>$order_info['state'],
      'status_text'=>\app\common\model\Order::getOrderStatus($order_info['status'])
    );

    $user_data =  array(
      'username'=>$user['username'],
      'mobile'=>$user['mobile'],
      'gender'=>$user['gender'],
    );

    $data['user'] = $user_data;//用户信息

    //查询周期信息   type为4 的时候的数据
    $cycle_arr = OrderLog::where('order_id',$order_id)
      ->where('type',5)
      ->field('id,order_id,node,courier_code,courier_id')
      ->order('id desc')
      ->select();

    $cycle_count = count($cycle_arr);
    foreach ($cycle_arr as $key=>&$row){
      $row['courier_name'] = \app\common\model\Kdniao::where('id',$row['courier_id'])->value('company');
      $row['cycle'] = $cycle_count-$key;
    }
    $data['cycle_arr'] = $cycle_arr;//周期信息

    //设计方案  type为1的时候
    $plan_info = OrderLog::where('order_id',$order_id)
      ->where('type',2)
      ->field('id,order_id,node,images')
      ->find();
    $plan_info ? $plan_info['amount'] = $order_info['amount'] : '';
    $plan_info['images'] = json_decode($plan_info['images']);
    $data['plan_info'] = $plan_info;//设计方案


    //type为1的时候 邮寄文件
    $mail_file = OrderLog::where('order_id',$order_id)
      ->where('type',1)
      ->field('id,order_id,node,courier_code,courier_id,file_url')
      ->find();
    $mail_file ? $mail_file['courier_name'] = \app\common\model\Kdniao::where('id',$mail_file['courier_id'])->value('company') : '';
    $data['mail_file'] =$mail_file;//邮寄模型 或者口扫文件

    $data['cases_image'] = $order_data;
    $data['status_text'] = $order_data['status_text'];
    $data['state'] = $order_data['state'];

    $data['instructions'] = $order_info['instructions'];


    $courier_arr =  \app\common\model\Kdniao::select();
    $data['courier_arr'] = $courier_arr;

    $this->success('查询成功',$data);


  }





  //邮寄模型 上传口扫文件
  public function mailModel(){
    $courier_id = $this->request->param('courier_id');
    $courier_code = $this->request->param('courier_code');
    $file_url = $this->request->param('file_url');
    $order_id = $this->request->param('order_id');

    $order = \app\admin\model\Order::where('order_id',$order_id)->find();

//       if($order['state'] != 1){
//         $this->error('当前状态不可以邮寄模型');
//       }
    //新增
    $log = OrderLog::where('order_id',$order_id)->where('type',1)->find();
    if($log){
      OrderLog::update([
        'courier_code'=>$courier_code,
        'file_url'=>$file_url,
        'courier_id'=>$courier_id,
      ],['order_id'=>$order_id,'type'=>1]);

      $id = $log->id;
    }else{
      \app\admin\model\Order::update(['status'=>2],['order_id'=>$order_id]);
      $log = OrderLog::create([
        'order_id'=>$order_id,
        'shop_id'=>$order['shop_id'],
        'stores_id'=>$order['stores_id'],
        'type'=>1,
        'courier_code'=>$courier_code,
        'file_url'=>$file_url,
        'courier_id'=>$courier_id,
      ]);
      $id = $log->id;
    }


    $data['id'] = $id;
    $this->success('操作成功',$data);
  }


  /**
   * 客户确认(同意) or 不同意方案
   */
  public  function userSucc(){
    $type = $this->request->param('type');//1同意  2不同意
    $order_id = $this->request->param('order_id');
    $log = OrderLog::where('order_id',$order_id)->where('type',2)->find();
    if(!$log){
      $this->error('还没有给出方案哦！');
    }

    $order = \app\admin\model\Order::where('order_id',$order_id)->find();

    if($type == 1){
      //同意
      \app\admin\model\Order::update(['status'=>4],['id'=>$order['id']]);
    }else{
      //不同意
      \app\admin\model\Order::update(['status'=>2],['id'=>$order['id']]);
    }

    $this->success('操作成功');
  }


  /**
   * 订单确认
   */
  public  function orderConfirm(){
    $order_id = $this->request->param('order_id');
    $order = \app\admin\model\Order::where('order_id',$order_id)->find();


       if($order['status'] != 4){
         $this->error('当前状态不能确认收款');
       }
    \app\admin\model\Order::update(['status'=>5],['id'=>$order['id']]);

    $this->success('操作成功');
  }



}
