<?php

namespace app\admin\controller;

use app\admin\model\Admin;
use app\admin\model\AuthGroupAccess;
use app\common\controller\Backend;
use fast\Random;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 商家账号
 *
 * @icon fa fa-circle-o
 */
class Shop extends Backend
{

  /**
   * Shop模型对象
   * @var \app\admin\model\Shop
   */
  protected $model = null;

  public function _initialize()
  {
    parent::_initialize();
    $this->model = new \app\admin\model\Shop;

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
        ->with(['stores'])
        ->where($where);

      $admin_id = $this->auth->id;
      $group_id = AuthGroupAccess::where('uid',$admin_id)->value('group_id');

      if($group_id == 6){
        $stores_ids = \app\admin\model\Stores::where('factory_id',$admin_id)->column('id');
        $query->where('store_id','in',$stores_ids);
      }
      $list = $query->order($sort, $order)
        ->paginate($limit);

      foreach ($list as &$row) {

        $row->visible(['id', 'username', 'mobile', 'avatar', 'gender', 'store_id','is_marage','order_count','order_mouth_count']);
        $row->visible(['stores']);
        $row->getRelation('stores')->visible(['name']);



        $order_count = \app\admin\model\Order::where('shop_id',$row->id)->count();
        $row->order_count = $order_count;//病例总数

        $order_mouth_count = \app\admin\model\Order::where('shop_id',$row->id)->whereTime('createtime', 'month')->count();
        $row->order_mouth_count = $order_mouth_count;//本月病例数

      }

      $result = array("total" => $list->total(), "rows" => $list->items());

      return json($result);
    }
    return $this->view->fetch();
  }


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

          //这里需要针对username和email做唯一验证
          $adminValidate = \think\Loader::validate('Shop');
          $adminValidate->rule([
            'username' => 'require|unique:shop,username,' . $row->id,
            'mobile' => 'require|unique:shop,mobile,' . $row->id,
          ]);
          $result = $row->validate('Shop.edit')->save($params);
          if ($result === false) {
               $this->error( $row->getError());
          }



          //手机号判断去重

          $field = 'username,mobile,avatar,store_id,gender,is_marage';
          if(isset($params['password']) && $params['password']){
            $salt = Random::alnum();
            $newpassword = md5(md5($params['password']) . $salt);
            $params['password'] = $newpassword;
            $params['salt'] = $salt;
            $field = 'username,mobile,avatar,store_id,password,salt,gender,is_marage';
          }



          $result = $row->allowField($field)->save($params);
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

    $this->view->assign("row", $row);
    return $this->view->fetch();
  }




  public function add()
  {
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



          //这里需要针对username和email做唯一验证
          $adminValidate = \think\Loader::validate('Shop');
          $adminValidate->rule([
            'username' => 'require|unique:shop,username,' ,
            'mobile' => 'require|unique:shop,mobile,',
          ]);
          $result = $this->model->validate('Shop.add')->save($params);
          if ($result === false) {
            $this->error( $this->model->getError());
          }




            $field = 'username,mobile,avatar,store_id,gender';
          if(isset($params['password']) && $params['password']){
            $salt = Random::alnum();
            $newpassword = md5(md5($params['password']) . $salt);
            $params['password'] = $newpassword;
            $params['salt'] = $salt;
            $field = 'username,mobile,avatar,store_id,gender,password,salt';
          }
          $result = $this->model->allowField($field)->save($params);

          //新增医生管理后台的账号

          $admin_arr['username'] = $params['mobile'];
          $admin_arr['salt'] = $salt;
          $admin_arr['agent_id'] = $this->model->id;
          $admin_arr['password'] = $newpassword;
          $admin_arr['avatar'] = '/assets/img/avatar.png'; //设置新管理员默认头像。
          $result = model('Admin')->validate('Admin.add')->save($admin_arr);
          if ($result === false) {
            exception(model('Admin')->getError());
          }
          $group[] = 9;

          if (!$group) {
            exception(__('The parent group exceeds permission limit'));
          }
          $dataset = [];
          foreach ($group as $value) {
            $dataset[] = ['uid' => model('Admin')->id, 'group_id' => $value];
          }
          model('AuthGroupAccess')->saveAll($dataset);


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
    return $this->view->fetch();
  }

}

