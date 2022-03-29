<?php

use \think\Route;



Route::post('upload','shop/common/upload');//图片上传
Route::post('fileUpload','shop/common/fileUpload');//文件上传
Route::get('system','shop/index/system');//系统配置
Route::post('sms-send','shop/sms/send');//短信验证码发送

Route::post('login','shop/shop/login');//登录
Route::post('mobilelogin','shop/shop/mobilelogin');//短信验证码登录
Route::post('register','shop/shop/register');//新建账号
Route::post('resetpwd','shop/shop/resetpwd');//修改密码
Route::post('profile','shop/shop/profile');//修改账户信息
Route::get('shop-info','shop/shop/info');//账户信息

Route::post('save-order','shop/order/index');//创建病例
Route::post('info-order','shop/order/getInfo');//病例编辑详情
Route::get('order-list','shop/order/orderList');//订单列表
Route::get('order-detail','shop/order/info');//订单详情
Route::POST('courier-info','shop/common/getKdi');//快递查询

Route::POST('mail-model','shop/order/mailModel');//邮寄模型 口扫文件
Route::POST('plan-succ','shop/order/userSucc');//方案同意不同意
Route::POST('order-confirm','shop/order/orderConfirm');//订单确认


Route::POST('stores-save','shop/stores/save');//修改门店信息
Route::POST('stores-address-save','shop/stores/AddressSave');//修改门店信息


