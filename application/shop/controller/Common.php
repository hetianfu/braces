<?php

namespace app\shop\controller;

use app\common\controller\Api;
use app\common\exception\UploadException;
use app\common\library\Upload;
use app\common\model\Area;
use app\common\model\OrderLog;
use app\common\model\User;
use app\common\model\Version;
use fast\Courier;
use fast\Random;
use OSS\Core\OssException;
use OSS\OssClient;
use think\Cache;
use think\Config;
use think\Env;
use think\Hook;

/**
 * 公共接口
 */
class Common extends Api
{
    protected $noNeedLogin = ['init'];
    protected $noNeedRight = '*';


    /**
     * 上传文件
     * @ApiMethod (POST)
     * @param File $file 文件流
     */
    public function upload()
    {
      $attachment = null;
      //默认普通上传文件
      $file = $this->request->file('file');
      try {
        $upload = new Upload($file);
        $attachment = $upload->upload();
      } catch (UploadException $e) {
        $this->error($e->getMessage());
      }

      $file_url = ROOT_PATH.'public'.$attachment->url;


      // 阿里云主账号AccessKey拥有所有API的访问权限，风险很高。强烈建议您创建并使用RAM账号进行API访问或日常运维，请登录RAM控制台创建RAM账号。
      $accessKeyId = Env::get('oss.accessKeyId');
      $accessKeySecret = Env::get('oss.accessKeySecret');
      // Endpoint以杭州为例，其它Region请按实际情况填写。
      $endpoint = Env::get('oss.endpoint');
      // 设置存储空间名称。
      $bucket= Env::get('oss.bucket');
      // 设置文件名称。
      $file_name = date('YmdHi').uniqid().'.'.$attachment->imagetype;
      $object = 'app/'.$file_name;
      // 配置文件内容。
      $filePath = $file_url;
      try{
        $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
        $res = $ossClient->uploadFile($bucket, $object, $filePath);
        unlink($file_url);
        $data['url'] = $res['info']['url'];
        $data['key'] = $file_name;
        $data['name'] = $attachment->filename;
        $this->success('上传成功',$data);
      } catch(OssException $e) {
        $this->error($e->getMessage());
      }



    }





    //查询快递信息
    public  function getKdi(){
       $id = $this->request->param('id');
       $kusidi = new Courier();
       $log = OrderLog::where('id',$id)->find();
       $order = \app\common\model\Order::where('order_id',$log['order_id'])->find();
       $user = User::where('id',$order['user_id'])->find();

      $case_info = Cache::get($log['courier_code']);
        if(!$log['courier_code']){
        $this->error('没有快递单号');
      }
      
       if($case_info){
         $info = $case_info;
       }else{
         if($log['courier_text']){
           $info = json_decode($log['courier_text'],true);
           Cache::set($log['courier_code'],$info);
         }else{
           $info = $kusidi->getKdi($log['courier_code'].':'.substr($user['mobile'], 7));
           Cache::set($log['courier_code'],$info,3600*5);
         }
       }




      //['deliverystatus']  3 已签收不需要掉接口
      if($info['deliverystatus'] == 3){
        //签收
        OrderLog::update(['courier_text'=>json_encode($info)],['id'=>$id]);
      }
      $this->success('查询成功',$info);
    }


  /**
   * 文件上传
   */
    public  function fileUpload(){
      $uploadFile = $this->request->file('file');

      var_dump($uploadFile);die;
      $accessKeyId = Env::get('oss.accessKeyId');
      $accessKeySecret = Env::get('oss.accessKeySecret');
      // Endpoint以杭州为例，其它Region请按实际情况填写。
      $endpoint = Env::get('oss.endpoint');
      // 设置存储空间名称。
      $bucket= Env::get('oss.bucket');
      // 设置文件名称。
      $file_name = date('YmdHi').uniqid().'.'.$attachment->imagetype;
      $object = 'app/'.$file_name;
      // 配置文件内容。

      /**
       *  步骤1：初始化一个分片上传事件，获取uploadId。
       */
      try {
        $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);

        //返回uploadId。uploadId是分片上传事件的唯一标识，您可以根据uploadId发起相关的操作，如取消分片上传、查询分片上传等。
        $uploadId = $ossClient->initiateMultipartUpload($bucket, $object);
      } catch (OssException $e) {
        printf(__FUNCTION__ . ": initiateMultipartUpload FAILED\n");
        printf($e->getMessage() . "\n");
        return;
      }
      print(__FUNCTION__ . ": initiateMultipartUpload OK" . "\n");
      /*
       * 步骤2：上传分片。
       */
      $partSize = 10 * 1024 * 1024;
      $uploadFileSize = filesize($uploadFile);
      $pieces = $ossClient->generateMultiuploadParts($uploadFileSize, $partSize);
      $responseUploadPart = array();
      $uploadPosition = 0;
      $isCheckMd5 = true;
      foreach ($pieces as $i => $piece) {
        $fromPos = $uploadPosition + (integer)$piece[$ossClient::OSS_SEEK_TO];
        $toPos = (integer)$piece[$ossClient::OSS_LENGTH] + $fromPos - 1;
        $upOptions = array(
          // 上传文件。
          $ossClient::OSS_FILE_UPLOAD => $uploadFile,
          // 设置分片号。
          $ossClient::OSS_PART_NUM => ($i + 1),
          // 指定分片上传起始位置。
          $ossClient::OSS_SEEK_TO => $fromPos,
          // 指定文件长度。
          $ossClient::OSS_LENGTH => $toPos - $fromPos + 1,
          // 是否开启MD5校验，true为开启。
          $ossClient::OSS_CHECK_MD5 => $isCheckMd5,
        );
        // 开启MD5校验。
        if ($isCheckMd5) {
          $contentMd5 = OssUtil::getMd5SumForFile($uploadFile, $fromPos, $toPos);
          $upOptions[$ossClient::OSS_CONTENT_MD5] = $contentMd5;
        }
        try {
          // 上传分片。
          $responseUploadPart[] = $ossClient->uploadPart($bucket, $object, $uploadId, $upOptions);
        } catch (OssException $e) {
          printf(__FUNCTION__ . ": initiateMultipartUpload, uploadPart - part#{$i} FAILED\n");
          printf($e->getMessage() . "\n");
          return;
        }
        printf(__FUNCTION__ . ": initiateMultipartUpload, uploadPart - part#{$i} OK\n");
      }
// $uploadParts是由每个分片的ETag和分片号（PartNumber）组成的数组。
      $uploadParts = array();
      foreach ($responseUploadPart as $i => $eTag) {
        $uploadParts[] = array(
          'PartNumber' => ($i + 1),
          'ETag' => $eTag,
        );
      }
      /**
       * 步骤3：完成上传。
       */
      try {
        // 执行completeMultipartUpload操作时，需要提供所有有效的$uploadParts。OSS收到提交的$uploadParts后，会逐一验证每个分片的有效性。当所有的数据分片验证通过后，OSS将把这些分片组合成一个完整的文件。
        $ossClient->completeMultipartUpload($bucket, $object, $uploadId, $uploadParts);
      } catch (OssException $e) {
        printf(__FUNCTION__ . ": completeMultipartUpload FAILED\n");
        printf($e->getMessage() . "\n");
        return;
      }
      printf(__FUNCTION__ . ": completeMultipartUpload OK\n");

    }



}
