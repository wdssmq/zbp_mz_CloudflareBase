<?php

// S3 API compatibility · Cloudflare R2 docs --- S3 接口兼容性 ·Cloudflare R2 文档
// https://developers.cloudflare.com/r2/api/s3/api/

// aws-sdk-php · Cloudflare R2 docs
// https://developers.cloudflare.com/r2/examples/aws/aws-sdk-php/

use Aws\Credentials\Credentials;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;

class mz_CloudflareBase_r2
{
  public $account_id         = "<account_id";
  public $access_key_id      = "<access_key_id>";
  public $access_key_secret  = "<access_key_secret>";
  public $bucket_name        = "<bucket_name>";

  private $errDef = array(
    'code' => 0,
    'msg' => '',
    'raw_msg' => '',
    'func' => '',
    'type' => 'other',
  );
  public $err;

  private $errMap = array(
    99 => '未知错误',
    98 => '没有权限'
  );

  private function setErr($code, $msg = "", $func = "", $type = 'other')
  {
    $this->err = $this->errDef;
    $this->err['code'] = $code;
    $this->err['msg'] = $this->errMap[$code] ?? ($msg ?: $this->errDef['msg']);
    $this->err['func'] = $func;
    $this->err['type'] = $type;
    $this->err['raw_msg'] = $msg;
  }

  private $credentials;
  private $s3_client;

  private function BuildS3Opts()
  {
    return array(
      'region' => 'auto',
      'endpoint' => "https://{$this->account_id}.r2.cloudflarestorage.com",
      'version' => 'latest',
      'credentials' => $this->credentials
    );
  }

  private function getBucketName($bucket_name)
  {
    return empty($bucket_name) ? $this->bucket_name : $bucket_name;
  }

  public function __construct($account_id, $access_key_id, $access_key_secret, $bucket_name)
  {
    $this->account_id = $account_id;
    $this->access_key_id = $access_key_id;
    $this->access_key_secret = $access_key_secret;
    $this->bucket_name = $bucket_name;
    $this->err = $this->errDef;

    $this->credentials = new Credentials($access_key_id, $access_key_secret);
    $this->s3_client = new S3Client($this->BuildS3Opts());
  }

  public function getContentType($file)
  {
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    $ext = strtolower($ext);
    switch ($ext) {
      case 'jpg':
      case 'jpeg':
        return 'image/jpeg';
        break;
      case 'png':
        return 'image/png';
        break;
      case 'gif':
        return 'image/gif';
        break;
      case 'bmp':
        return 'image/bmp';
        break;
      case 'ico':
        return 'image/x-icon';
        break;
      case 'mp3':
        return 'audio/mpeg';
        break;
      case 'mp4':
        return 'video/mp4';
        break;
      case 'webm':
        return 'video/webm';
        break;
      default:
        return 'application/octet-stream';
        break;
    }
  }

  // 新令牌机制下已废弃，只能显式指定 bucket 名称
  public function listBuckets()
  {
    try {
      $result = $this->s3_client->listBuckets();
      return $result;
    } catch (S3Exception $e) {
      $this->setErr($e->getCode() ?: 98, $e->getMessage(), 'listBuckets', 's3');
      return false;
    } catch (\Throwable $e) {
      $this->setErr($e->getCode() ?: 98, $e->getMessage(), 'listBuckets', 'other');
      return false;
    }
  }

  public function headBucket($bucket_name = "")
  {
    try {
      $bucket_name = $this->getBucketName($bucket_name);
      $result = $this->s3_client->headBucket([
        'Bucket' => $bucket_name
      ]);
      return $result;
    } catch (S3Exception $e) {
      $this->setErr($e->getCode() ?: 99, $e->getMessage(), 'headBucket', 's3');
      return false;
    } catch (\Throwable $e) {
      $this->setErr($e->getCode() ?: 99, $e->getMessage(), 'headBucket', 'other');
      return false;
    }
  }

  public function listObjects($bucket_name = "")
  {
    try {
      $bucket_name = $this->getBucketName($bucket_name);
      $result = $this->s3_client->listObjectsV2([
        'Bucket' => $bucket_name
      ]);
      return $result;
    } catch (S3Exception $e) {
      $this->setErr($e->getCode() ?: 99, $e->getMessage(), 'listObjects', 's3');
      return false;
    } catch (\Throwable $e) {
      $this->setErr($e->getCode() ?: 99, $e->getMessage(), 'listObjects', 'other');
      return false;
    }
  }

  public function uploadFile(&$object_data, $bucket_name = "")
  {
    try {
      $key = $object_data['key'] ?? $object_data['name'];
      $body = $object_data['body'] ?? $object_data['content'];
      $type = $object_data['type'] ?? $this->getContentType($key);
      $acl = $object_data['acl'] ?? 'private';
      $bucket_name = $this->getBucketName($bucket_name);
      $result = $this->s3_client->putObject([
        'Bucket' => $bucket_name,
        'Key' => $key,
        // 'SourceFile' => $file,
        'Body' => $body,
        'ContentType' => $type,
        'ACL' => $acl,
      ]);
      $object_data['url'] = $result['ObjectURL'];
      return $result;
    } catch (S3Exception $e) {
      $this->setErr($e->getCode() ?: 99, $e->getMessage(), 'uploadFile', 's3');
      return false;
    } catch (\Throwable $e) {
      $this->setErr($e->getCode() ?: 99, $e->getMessage(), 'uploadFile', 'other');
      return false;
    }
  }
}
