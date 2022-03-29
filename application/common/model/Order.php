<?php

namespace app\common\model;

use think\Model;

/**
 * 订单
 */
class Order Extends Model
{

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    // 追加属性
  

  protected $append = [
    'state'
  ];


  public function getStateAttr($value, $data)
  {
    return $data['status'];
  }


    public static function getOrderStatus($value = -1){
      //0创建病例    1待寄模具  2待出方案  3待客户确认  4待付款  5周期发货  6订单完成
      $arr = [
        1=>'待寄模具',
        2=>'待出方案',
        3=>'待客户确认',
        4=>'待确认',
        5=>'周期发货',
        6=>'订单完成',
      ];

      if($value == -1){
        return $arr;
      }

      if(isset($arr[$value])){
        return $arr[$value];
      }
      return $value;

    }


  public function user()
  {
    return $this->hasOne(User::class, 'id','user_id');
  }



}
