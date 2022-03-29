<?php

namespace app\shop\controller;

use addons\kdniao\Kdniao;
use app\admin\model\System;
use app\common\controller\Api;
use app\common\model\Config;
use fast\Courier;

/**
 * 首页接口
 */
class Index extends Api
{
  protected $noNeedLogin = ['*'];
  protected $noNeedRight = ['*'];

  /**
   * 首页
   *
   */
  public function index()
  {
    $this->success('请求成功1123');

  }


  /**
   * 系统配置
   */
  public function system(){
    $list = System::all();
    $data = [];
    foreach ($list as $row){
      $data[$row['type']] = str_replace("https://braces.oss-cn-beijing.aliyuncs.com","http://oss.kelibiluo.com",$row['file_url']);
    }

    $Config = Config::where('name','customer_service')->value('value');
    $data['customer_service'] = $Config;
    $this->success('成功',$data);
  }
}
