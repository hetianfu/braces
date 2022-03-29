<?php

namespace app\common\library;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use think\Env;

class Sample
{

  public static function ali_sms($phone,$code)
  {

    $accessKeyId = Env::get('oss.accessKeyId');
    $accessKeySecret = Env::get('oss.accessKeySecret');

    AlibabaCloud::accessKeyClient($accessKeyId, $accessKeySecret)
      ->regionId('cn-qingdao')
      ->asDefaultClient();

    try {

      $data['code'] = $code;
      $result = AlibabaCloud::rpc()
        ->product('Dysmsapi')
        ->version('2017-05-25')
        ->action('SendSms')
        ->method('POST')
        ->host('dysmsapi.aliyuncs.com')
        ->options([
          'query' => [
            'PhoneNumbers' => $phone,
            'SignName' => "点讯",
            'TemplateCode' => "SMS_222585043",
            'TemplateParam' => json_encode($data),
          ],
        ])
        ->request();
      $res =  $result->toArray();
      if($res['Code'] == 'OK'){
          return true;
      }else{
        return false;
      }
    } catch (ClientException $e) {
      return false;
    } catch (ServerException $e) {
      return false;
    }

  }

}

