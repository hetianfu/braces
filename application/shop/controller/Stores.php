<?php

namespace app\shop\controller;

use addons\kdniao\Kdniao;
use app\common\controller\Api;
use fast\Courier;

/**
 * 门店接口
 */
class Stores extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    /**
     * 修改，门诊信息
     *
     */
    public function save()
    {
      $name = $this->request->param('name','');
      $address = $this->request->param('address','');
      $detailed_address = $this->request->param('detailed_address','');
      $mobile = $this->request->param('mobile','');

      $shop = $this->auth->getShop();
      $shop_id = $shop->id;
      $store_id = $shop->store_id;

      $data['name'] = $name;
      $data['mobile'] = $mobile;
     $data['address'] = str_replace(" ","/",$address);
      $data['detailed_address'] = $detailed_address;
      \app\common\model\Stores::update($data,['id'=>$store_id]);

      $this->success('修改成功');

    }


  /**
   *
   * 修改收货地址
   */
  public function AddressSave()
  {
    $receiving_name = $this->request->param('receiving_name','');
    $receiving_mobile = $this->request->param('receiving_mobile','');
    $receiving_address = $this->request->param('receiving_address','');
    $receiving_detail_address = $this->request->param('receiving_detail_address','');

    $shop = $this->auth->getShop();
    $shop_id = $shop->id;
    $store_id = $shop->store_id;

    $data['receiving_name'] = $receiving_name;
    $data['receiving_mobile'] = $receiving_mobile;
    $data['receiving_address'] = $receiving_address;
    $data['receiving_detail_address'] = $receiving_detail_address;
    \app\common\model\Stores::update($data,['id'=>$store_id]);

    $this->success('修改成功');

  }
}
