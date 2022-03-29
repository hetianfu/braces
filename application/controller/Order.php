<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\admin\model\AuthGroupAccess;
use app\admin\model\Admin;
use app\common\model\OrderLog;
use app\common\model\User;

/**
 * 订单管理
 *
 * @icon fa fa-circle-o
 */
class Order extends Backend
{

  protected $noNeedRight = ['agent_list','factory_list','cycle','plan'];

    /**
     * Order模型对象
     * @var \app\admin\model\Order
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Order;


      $admin_id = $this->auth->id;
      $group_id = AuthGroupAccess::where('uid',$admin_id)->value('group_id');

      $this->view->assign("group_id", $group_id);
    }

    public function import()
    {
        parent::import();
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    

    /**
     * 查看
     */
    public function index()
    {
              //判断一下角色
      $admin_id = $this->auth->id;
      $group_id = AuthGroupAccess::where('uid',$admin_id)->value('group_id');
      
      if($group_id == 2){
        //查询代理商下有哪些诊所
        $agent_list = Admin::where('agent_id',$admin_id)->column('id');
        $stores_ids = \app\admin\model\Stores::where('factory_id','in',$agent_list)->column('id');
      }

      if($group_id == 6){
        //查询工厂下有哪些诊所
        $stores_ids = \app\admin\model\Stores::where('factory_id','in',$admin_id)->column('id');
      }
      
      
      //分组  1位超级管理员  2为代理商 6为工厂
      
        //当前是否为关联查询
        $this->relationSearch = true;
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

          $query = $this->model
                    ->with(['stores','shop','admin','user'])
                    ->where($where);

          if($group_id != 1){
              $query->where('stores_id','in',$stores_ids);
            }

          $list =  $query->order($sort, $order)
            ->paginate($limit);

          foreach ($list as $row) {
                $row->visible(['id','order_id','shop_id','stores_id','status','cycle','current_cycle','createtime','agent_id','factory_id','time_text','agent','factory','stores_text','cycle_text']);
                $row->visible(['stores']);
				$row->getRelation('stores')->visible(['name']);
				
				 $row->visible(['user']);
				$row->getRelation('user')->visible(['username']);
				$row->getRelation('user')->visible(['mobile']);
				
				
				$row->visible(['shop']);
				$row->getRelation('shop')->visible(['username']);
				$row->getRelation('shop')->visible(['mobile']);
				$row->visible(['admin']);
				$row->getRelation('admin')->visible(['username']);
				
				 $arr = array('1'=>'待寄模具','2'=>'待出方案','3'=>'待客户确认','4'=>'待付款','5'=>'周期发货','6'=>'订单完成',);
                if(isset($arr[$row->status])){
                  $row->status = $arr[$row->status];
                }else{
                  $row->status = $row->status;
                }
               
				
			   $factory_id = \app\admin\model\Stores::where('id',$row->stores_id)->value('factory_id');


               $row->cycle_text = $row->cycle.'/'.$row->current_cycle;

                $factory_info = Admin::where('id',$factory_id)->find();
                $row->factory_id = $factory_info['nickname'];


                $agent_info = Admin::where('id',$factory_info['agent_id'])->find();
                $row->agent_id = $agent_info['nickname'];

               $row->time_text = date('Y-m-d H:i',$row->createtime);
               $row->stores_text = \app\admin\model\Stores::where('id',$row->stores_id)->value('name');
               
               
               
            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }
    
    
    
    
     /**
   * 详情
   */
  public function detail($ids)
  {
    $order_info = \app\common\model\Order::where('id',$ids)->find();
    if(!$order_info){
      $this->error('订单不存在');
    }
    $order_id = $order_info['order_id'];


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
      ->field('id,order_id,node,courier_code,courier_id,createtime')
      ->order('id desc')
      ->select();
    $cycle_count = count($cycle_arr);
    foreach ($cycle_arr as $key=>&$row){
      $row['courier_name'] = \app\common\model\Kdniao::where('id',$row['courier_id'])->value('company');
      $row['cycle'] = $cycle_count-$key;
      $row['time_text'] = date('Y-m-d H:i',$row['createtime']);

      $row['class_name'] = '';
      if($row['cycle']%2){
        $row['class_name'] = 'timeline-inverted';
      }
    }
   // var_dump(2%2);die;
    $data['cycle_arr'] = $cycle_arr;//周期信息

    //设计方案  type为1的时候
    $plan_info = OrderLog::where('order_id',$order_id)
      ->where('type',2)
      ->field('id,order_id,node,images')
      ->find();

    if(!$plan_info){
      $plan_info['node'] = '';
      $plan_info['images'] = '';
      $plan_info['amount'] = 0;
      $plan_info['cycle'] = 0;
      $plan_info['id'] = 0;
    }
    $plan_info ? $plan_info['amount'] = $order_info['amount'] : '';
    $plan_info ? $plan_info['cycle'] = $order_info['cycle'] : '';

    $plan_info['images'] = json_decode($plan_info['images']);
    if($plan_info['images']){
      $plan_info['images'] = implode(',',$plan_info['images']);
    }else{
      $plan_info['images'] = '';
    }
    $data['plan_info'] = $plan_info;//设计方案


    //type为0的时候 邮寄文件
    $data['mail_file']['courier_code'] = '暂无';
    $data['mail_file']['courier_name'] = '暂无';
    $data['mail_file']['file_url'] = '暂无';
    $mail_file = OrderLog::where('order_id',$order_id)
      ->where('type',1)
      ->field('id,order_id,node,courier_code,courier_id,file_url')
      ->find();
    $mail_file ? $mail_file['courier_name'] = \app\common\model\Kdniao::where('id',$mail_file['courier_id'])->value('company') : '';

    if($mail_file){
      $data['mail_file'] = $mail_file;//邮寄模型 或者口扫文件
    }


    $data['cases_image'] = $order_data;

    $courier_arr =  \app\common\model\Kdniao::select();
    $data['courier_arr'] = $courier_arr;



    $this->view->assign("data", $data);
    return $this->view->fetch();
  }




  //周期发货
  public function cycle(){
    $courier_code =$this->request->param('courier_code');
    $courier_id =$this->request->param('courier_id');
    $order_id =$this->request->param('order_id');

    //先查已经发了多少周期
    $order_info = \app\common\model\Order::where('order_id',$order_id)->find();
    $current_cycle = $order_info['current_cycle']+1;

    \app\admin\model\Order::update(['current_cycle'=>$current_cycle],['order_id'=>$order_id]);
    //新增
    OrderLog::create([
      'order_id'=>$order_id,
      'shop_id'=>$order_info['shop_id'],
      'stores_id'=>$order_info['stores_id'],
      'type'=>5,
      'courier_id'=>$courier_id,
      'courier_code'=>$courier_code,
    ]);



    //判断是否已经发货完成
    if($order_info['cycle'] <= $current_cycle){
      //状态改变
      \app\admin\model\Order::update(['status'=>6],['order_id'=>$order_id]);
    }

    return 0;
  }


  /**
   * 保存修改方案
   */
  public function plan(){
    $node =$this->request->param('node');
    $cycle =$this->request->param('cycle');
    $amount =$this->request->param('amount');
    $plan_id =$this->request->param('plan_id');
    $order_id =$this->request->param('order_id');
    $images =$this->request->param('images');

    $images = explode(',',$images);
    try{
      Db::startTrans();
      if($plan_id){
        //修改
        OrderLog::update(['node'=>$node,'images'=>json_encode($images)],['id'=>$plan_id]);
        \app\admin\model\Order::update(['cycle'=>$cycle,'amount'=>$amount,'status'=>3],['order_id'=>$order_id]);
      }else{
        \app\admin\model\Order::update(['cycle'=>$cycle,'amount'=>$amount,'status'=>3],['order_id'=>$order_id]);
        $order = \app\admin\model\Order::where('order_id',$order_id)->find();
        //新增
        OrderLog::create([
          'order_id'=>$order_id,
          'shop_id'=>$order['shop_id'],
          'stores_id'=>$order['stores_id'],
          'type'=>2,
          'node'=>$node,
          'images'=>json_encode($images),
        ]);
      }

      Db::commit();
      return 0;
    }catch (Exception $exception){
      Db::rollback();
       return 1;
    }

  }



  public function factory_list(){
    //判断一下角色
    $admin_id = $this->auth->id;
    $group_id = AuthGroupAccess::where('uid',$admin_id)->value('group_id');
    //分组  1位超级管理员  2为代理商 6为工厂
    $search_factory_list = [];
    $search_factory = [];
    if($group_id == 1){
      //超级管理员

      $admin_uids = AuthGroupAccess::where('group_id',6)->column('uid');
      $search_factory_list = Admin::where('id','in',$admin_uids)->select();

    }
    if($group_id == 2){
      //查询代理商下有哪些诊所
      $agent_list = Admin::where('agent_id',$admin_id)->column('id');
      $stores_ids = \app\admin\model\Stores::where('factory_id','in',$agent_list)->column('id');

      $search_factory_list = Admin::where('agent_id',$admin_id)->select();
    }

    foreach ($search_factory_list as $row){
      $search_factory[$row['id']] = $row['nickname'];
    }

    return json($search_factory);

  }



  public function agent_list(){
    //判断一下角色
    $admin_id = $this->auth->id;
    $group_id = AuthGroupAccess::where('uid',$admin_id)->value('group_id');
    //分组  1位超级管理员  2为代理商 6为工厂
    $search_agent_list = [];
    $search_agent = [];
    if($group_id == 1){
      //超级管理员

      $admin_uids = AuthGroupAccess::where('group_id',2)->column('uid');
      $search_agent_list = Admin::where('id','in',$admin_uids)->select();
    }

    foreach ($search_agent_list as $row){
      $search_agent[$row['id']] = $row['nickname'];
    }

    return json($search_agent);


  }

}
