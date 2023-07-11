<?php

// S3 API compatibility · Cloudflare R2 docs --- S3 接口兼容性 ·Cloudflare R2 文档
// https://developers.cloudflare.com/r2/api/s3/api/

// aws-sdk-php · Cloudflare R2 docs
// https://developers.cloudflare.com/r2/examples/aws/aws-sdk-php/

use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use Aws\S3\S3Exception;

class mz_CloudflareBase_r2
{
  public $account_id         = "<account_id";
  public $access_key_id      = "<access_key_id>";
  public $access_key_secret  = "<access_key_secret>";
  public $bucket_name        = "<bucket_name>";

  public $err = array(
    'code' => 0,
    'msg' => '',
  );

  private $errMap = array(
    1000 => '未知错误',
    1001 => '对象操作失败'
  );

  private function setErr($code, $msg = "")
  {
    $this->err['code'] = $code;
    if (empty($msg)) {
      $this->err['msg'] = $this->errMap[$code];
    } else {
      $this->err['msg'] = $msg;
    }
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

  public function listBuckets()
  {
    return $this->s3_client->listBuckets();
  }

  public function listObjects($bucket_name = "")
  {
    $bucket_name = $this->getBucketName($bucket_name);
    $contents = $this->s3_client->listObjectsV2([
      'Bucket' => $bucket_name
    ]);
    return $contents;
  }

  public function uploadFile(&$object_data, $bucket_name = "")
  {
    $key = $object_data['key'] ?? $object_data['name'];
    $body = $object_data['body'] ?? $object_data['content'];
    $type = $object_data['type'] ?? $this->getContentType($key);
    $acl = $object_data['acl'] ?? 'private';
    try {
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
      $msg = 'Error uploading file: ' . $e->getMessage();
      $this->setErr(1001, $msg);
      return false;
    }
  }
}
