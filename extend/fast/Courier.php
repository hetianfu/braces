<?php

namespace fast;

use DateTime;
use DateTimeZone;
use think\Env;

/**
 * 日期时间处理类
 */
class Courier
{

  public function getKdi($no,$type = '')
  {
    $host = "https://wuliu.market.alicloudapi.com";//api访问链接
    $path = "/kdi";//API访问后缀
    $method = "GET";
    $appcode = Env::get('courier.AppCode');//开通服务后 买家中心-查看AppCode
    $headers = array();
    array_push($headers, "Authorization:APPCODE " . $appcode);
    $querys = "no=" . $no . "&type=" . $type;  //参数写在这里
    $bodys = "";
    $url = $host . $path . "?" . $querys;

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_FAILONERROR, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, true);
    if (1 == strpos("$" . $host, "https://")) {
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    }
    $out_put = curl_exec($curl);

    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    list($header, $body) = explode("\r\n\r\n", $out_put, 2);

    if ($httpCode == 200) {
      $body = json_decode($body,true);
      // $body['result']['deliverystatus']  3 已签收不需要掉接口
      return $body['result'];
    } else {
       return false;
    }
  }



  public function aa(){
    $host = "https://wuliu.market.alicloudapi.com";//api访问链接
    $path = "/getExpressList";//API访问后缀
    $method = "GET";
    $appcode = Env::get('courier.AppCode');//开通服务后 买家中心-查看AppCode
    $headers = array();
    array_push($headers, "Authorization:APPCODE " . $appcode);
    $querys = "";
    $url = $host . $path . "?" . $querys;

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_FAILONERROR, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, true);
    if (1 == strpos("$" . $host, "https://")) {
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    }
    $out_put = curl_exec($curl);

    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    list($header, $body) = explode("\r\n\r\n", $out_put, 2);
    if ($httpCode == 200) {
      $body = json_decode($body,true);
      // $body['result']['deliverystatus']  3 已签收不需要掉接口
      return $body['result'];
    } else {
      return false;
    }
  }

}
