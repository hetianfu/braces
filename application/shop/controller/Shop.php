<?php

namespace app\shop\controller;

use app\common\controller\Api;
use app\common\library\Ems;
use app\common\library\ShopAuth;
use app\common\library\Sms;
use app\common\model\Stores;
use fast\Random;
use think\Config;
use think\Validate;

/**
 * 会员接口
 */
class Shop extends Api
{
  protected $noNeedLogin = ['login', 'mobilelogin', 'resetpwd', 'changeemail', 'changemobile', 'third'];
  protected $noNeedRight = '*';

  public function _initialize()
  {
    parent::_initialize();

    if (!Config::get('fastadmin.usercenter')) {
      $this->error(__('User center already closed'));
    }

  }

  /**
   * 会员中心
   */
  public function index()
  {
    $this->success('', ['welcome' => $this->auth->nickname]);
  }


  //用户信息
  public function info(){
   $info = $this->auth->getShop();
   $data['username'] = $info['username'];
   $data['id'] = $info['id'];
   $data['mobile'] = $info['mobile'];
   $data['avatar'] = 'https://braces.oss-cn-beijing.aliyuncs.com'.$info['avatar'];
   $data['store_id'] = $info['store_id'];
   $stores = Stores::where('id',$info['store_id'])->find();
   $data['stores']['receiving_address'] = $stores['receiving_address'];
   $data['stores']['name'] = $stores['name'];
   $data['stores']['address'] = $stores['address'];
   $data['stores']['mobile'] = $stores['mobile'];
   $data['stores']['detailed_address'] = $stores['detailed_address'];
   $data['stores']['receiving_detail_address'] = $stores['receiving_detail_address'];
   $data['stores']['receiving_name'] = $stores['receiving_name'];
   $data['stores']['receiving_mobile'] = $stores['receiving_mobile'];
    $this->success('成功', $data);
  }



  /**
   * 会员登录
   *
   * @ApiMethod (POST)
   * @param string $account  账号
   * @param string $password 密码
   */
  public function login()
  {
    $mobile = $this->request->post('mobile');
    $password = $this->request->post('password');

    $userinfo = \app\common\model\Shop::getByMobile($mobile);
    if(!$userinfo){
      $this->error(__('你还未注册账号!请联系客服'),[],10);
    }
    
    $shop = new ShopAuth();
    if (!$mobile || !$password) {
      $this->error(__('Invalid parameters'));
    }
    $ret = $shop->login($mobile, $password);
  
    if ($ret) {
      $data = $shop->getShopinfo();
     
      
      $this->success('登录成功', $data);
    } else {
      $this->error($shop->getError());
    }
  }

  /**
   * 手机验证码登录
   *
   * @ApiMethod (POST)
   * @param string $mobile  手机号
   * @param string $captcha 验证码
   */
  public function mobilelogin()
  {
    $mobile = $this->request->post('mobile');
   
    $captcha = $this->request->post('captcha');
    if (!$mobile || !$captcha) {
      $this->error('参数不能为空');
    }
    if (!Validate::regex($mobile, "^1\d{10}$")) {
      $this->error('手机号有误');
    }
    if (!Sms::check($mobile, $captcha, 'mobilelogin')) {
      //$this->error('验证码错误');
    }
    $shop = new ShopAuth();
    $user = \app\common\model\Shop::getByMobile($mobile);
    if ($user) {
//      if ($user->status != 'normal') {
//        $this->error(__('Account is locked'));
//      }
      //如果已经有账号则直接登录
      $ret =$shop->direct($user->id);
    } else {
     // $ret =$shop->register($mobile, Random::alnum(), '', $mobile, []);
      $this->error('你还未开通账号');
    }
    if ($ret) {
      Sms::flush($mobile, 'mobilelogin');
      $data = $shop->getShopinfo();
      
      
      $data['is_password'] = 0;
      //判断是否设置密码
      if($user['password']){
        $data['is_password'] = 1;
      }
      
      
      $this->success('登录成功', $data);
    } else {
      $this->error($shop->getError());
    }
  }

  /**
   * 新增账号会员
   *
   * @ApiMethod (POST)
   * @param string $username 用户名
   * @param string $password 密码
   * @param string $mobile   手机号
   * @param string $code     验证码
   */
  public function register()
  {
    $username = $this->request->post('username');
    $password = $this->request->post('password');
    $mobile = $this->request->post('mobile');

    $auth = new ShopAuth();

    if (!$username || !$password) {
      $this->error('参数错误');
    }

    if ($mobile && !Validate::regex($mobile, "^1\d{10}$")) {
      $this->error('手机号错误');
    }
    $user = $this->auth->getShop()->toArray();
    $ret =$auth->register($username, $password, $mobile, [],$user['store_id']);
    if ($ret) {
      $data = $auth->getShopinfo();
      $this->success('创建成功', $data);
    } else {
      $this->error($auth->getError());
    }
  }

  /**
   * 退出登录
   * @ApiMethod (POST)
   */
  public function logout()
  {
    if (!$this->request->isPost()) {
      $this->error(__('Invalid parameters'));
    }
    $this->auth->logout();
    $this->success('退出成功');
  }

  /**
   * 修改会员个人信息
   *
   * @ApiMethod (POST)
   * @param string $avatar   头像地址
   * @param string $username 用户名
   * @param string $nickname 昵称
   * @param string $bio      个人简介
   */
  public function profile()
  {
    $shop = $this->auth->getShop();
    $username = $this->request->post('username');
    $nickname = $this->request->post('nickname');
    $bio = $this->request->post('bio');
    $avatar = $this->request->post('avatar', '', 'trim,strip_tags,htmlspecialchars');
    if ($username) {
      $shop->username = $username;
    }
    if ($nickname) {
      $shop->nickname = $nickname;
    }
    $shop->bio = $bio;
    $shop->avatar = $avatar;
    $shop->save();
    $this->success('修改成功');
  }


  /**
   * 修改手机号
   *
   * @ApiMethod (POST)
   * @param string $mobile  手机号
   * @param string $captcha 验证码
   */
  public function changemobile()
  {
    $user = $this->auth->getUser();
    $mobile = $this->request->post('mobile');
    $captcha = $this->request->post('captcha');
    if (!$mobile || !$captcha) {
      $this->error(__('Invalid parameters'));
    }
    if (!Validate::regex($mobile, "^1\d{10}$")) {
      $this->error(__('Mobile is incorrect'));
    }
    if (\app\common\model\User::where('mobile', $mobile)->where('id', '<>', $user->id)->find()) {
      $this->error(__('Mobile already exists'));
    }
    $result = Sms::check($mobile, $captcha, 'changemobile');
    if (!$result) {
      $this->error(__('Captcha is incorrect'));
    }
    $verification = $user->verification;
    $verification->mobile = 1;
    $user->verification = $verification;
    $user->mobile = $mobile;
    $user->save();

    Sms::flush($mobile, 'changemobile');
    $this->success();
  }



  /**
   * 重置密码
   *
   * @ApiMethod (POST)
   * @param string $mobile      手机号
   * @param string $newpassword 新密码
   * @param string $captcha     验证码
   */
  public function resetpwd()
  {
    $mobile = $this->request->post("mobile");
    $newpassword = $this->request->post("newpassword");
    $captcha = $this->request->post("captcha");
    if (!$newpassword || !$captcha) {
      $this->error(__('Invalid parameters'));
    }

      if (!Validate::regex($mobile, "^1\d{10}$")) {
        $this->error(__('Mobile is incorrect'));
      }
      $user = \app\common\model\Shop::getByMobile($mobile);
      if (!$user) {
        $this->error(__('User not found'));
      }
      $ret = Sms::check($mobile, $captcha, 'resetpwd');
      if (!$ret) {
        $this->error(__('Captcha is incorrect'));
      }
      Sms::flush($mobile, 'resetpwd');

    //模拟一次登录
    $this->auth->direct($user->id);
    $ret = $this->auth->changepwd($newpassword, '', true);
    if ($ret) {
      $this->success('密码修改成功');
    } else {
      $this->error($this->auth->getError());
    }
  }
}
