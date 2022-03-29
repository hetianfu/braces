<?php

namespace app\admin\controller;

use app\admin\model\Admin;
use app\admin\model\AuthGroupAccess;
use app\common\controller\Backend;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 门店管理
 *
 * @icon fa fa-circle-o
 */
class Stores extends Backend
{
    
    /**
     * Stores模型对象
     * @var \app\admin\model\Stores
     */
    protected $model = null;

  protected $noNeedRight = ['stores_factor'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Stores;

      $admin_id = $this->auth->id;
      $group_id = AuthGroupAccess::where('uid',$admin_id)->value('group_id');



      $admin_list = [];
        $this->view->assign("admin_list", $admin_list);
        $admin_data = [];
        if($group_id == 6){
          $uids =  AuthGroupAccess::where('group_id',6)->where('uid',$admin_id)->column('uid');
        }else{
          $uids =  AuthGroupAccess::where('group_id',6)->column('uid');
        }
        $agent = \app\admin\model\Admin::where('id','in',$uids)->select();
        foreach ($agent as $row1){
        $admin_data[$row1['id']] = $row1['username'];
        }
        $this->view->assign("admin_data", $admin_data);

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
      $admin_id = $this->auth->id;
      $group_id = AuthGroupAccess::where('uid',$admin_id)->value('group_id');


      //当前是否为关联查询
        $this->relationSearch = false;
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

          $query = $this->model
                    
                    ->where($where);

          if($group_id == 6){
            //工厂
            $query->where('factory_id',$admin_id);
          }

          if($group_id == 2){
            //代理商
            $factory_ids = Admin::where('agent_id',$admin_id)->column('id');
            $query->where('factory_id','in',$factory_ids);
          }

          $list =  $query  ->order($sort, $order)
            ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id','name','receiving_address','address','createtime',
                  'mobile','detailed_address','receiving_detail_address',
                  'receiving_name','receiving_mobile','order_count','order_mouth_count','factory','agent','account_count']);

                $order_count = \app\admin\model\Order::where('stores_id',$row->id)->count();
              $row->order_count = $order_count;//病例总数

              $order_mouth_count = \app\admin\model\Order::where('stores_id',$row->id)->whereTime('createtime', 'month')->count();
              $row->order_mouth_count = $order_mouth_count;//本月病例数

              $factory_admin = Admin::where('id',$row->factory_id)->find();
              $row->factory = $factory_admin['username'];//所属工厂


              $agent_admin = Admin::where('id',$factory_admin->agent_id)->find();
              $row->agent = $agent_admin['username'];//所属代理商

              $account_count = \app\admin\model\Shop::where('store_id',$row->id)->count();
              $row->account_count = $account_count;//账号数量

            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }





  /**
   * 添加
   */
  public function add()
  {
    $admin_id = $this->auth->id;
    $fa_id = 0;
    $group_id = AuthGroupAccess::where('uid',$admin_id)->value('group_id');
    if($group_id == 6){
      //登录账号是工厂
      $fa_id =  $admin_id;
    }


    if ($this->request->isPost()) {
      $params = $this->request->post("row/a");
      if ($params) {
        $params = $this->preExcludeFields($params);


        if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
          $params[$this->dataLimitField] = $this->auth->id;
        }
        $result = false;
        Db::startTrans();
        try {
          //是否采用模型验证
          if ($this->modelValidate) {
            $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
            $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
            $this->model->validateFailException(true)->validate($validate);
          }

          $result = $this->model->allowField(true)->save($params);
          Db::commit();
        } catch (ValidateException $e) {
          Db::rollback();
          $this->error($e->getMessage());
        } catch (PDOException $e) {
          Db::rollback();
          $this->error($e->getMessage());
        } catch (Exception $e) {
          Db::rollback();
          $this->error($e->getMessage());
        }
        if ($result !== false) {
          $this->success();
        } else {
          $this->error(__('No rows were inserted'));
        }
      }
      $this->error(__('Parameter %s can not be empty', ''));
    }
    $this->assign('fa_id',$fa_id);
    return $this->view->fetch();
  }

  /**
   * 编辑
   */
  public function edit($ids = null)
  {
    $row = $this->model->get($ids);
    if (!$row) {
      $this->error(__('No Results were found'));
    }
    $adminIds = $this->getDataLimitAdminIds();
    if (is_array($adminIds)) {
      if (!in_array($row[$this->dataLimitField], $adminIds)) {
        $this->error(__('You have no permission'));
      }
    }
    if ($this->request->isPost()) {
      $params = $this->request->post("row/a");
      if ($params) {
        $params = $this->preExcludeFields($params);
        $result = false;
        Db::startTrans();
        try {
          //是否采用模型验证
          if ($this->modelValidate) {
            $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
            $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
            $row->validateFailException(true)->validate($validate);
          }
          $result = $row->allowField(true)->save($params);
          Db::commit();
        } catch (ValidateException $e) {
          Db::rollback();
          $this->error($e->getMessage());
        } catch (PDOException $e) {
          Db::rollback();
          $this->error($e->getMessage());
        } catch (Exception $e) {
          Db::rollback();
          $this->error($e->getMessage());
        }
        if ($result !== false) {
          $this->success();
        } else {
          $this->error(__('No rows were updated'));
        }
      }
      $this->error(__('Parameter %s can not be empty', ''));
    }


    $admin_list = [$row['factory_id']];
    $this->view->assign("admin_list", $admin_list);

    $this->view->assign("row", $row);
    return $this->view->fetch();
  }



  public function stores_factor(){
    $admin_id = $this->auth->id;
    $group_id = AuthGroupAccess::where('uid',$admin_id)->value('group_id');



    $list = [];
    $total = 0;
    if($group_id == 1){
      $all = \app\admin\model\Stores::select();
    }else{
      $all = \app\admin\model\Stores::where('factory_id',$admin_id)->select();
    }
    foreach ($all as $row){
      $total ++;
      $list[] = array('id'=>$row['id'],'name'=>$row['name'],'pid'=>0);
    }
    $da['list'] = $list;
    $da['total'] = $total;
    return $da;
  }

}
