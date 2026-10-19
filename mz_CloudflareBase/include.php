<?php
require 'vendor/autoload.php';
require __DIR__ . '/class/r2.php';
RegisterPlugin("mz_CloudflareBase", "ActivePlugin_mz_CloudflareBase");

function ActivePlugin_mz_CloudflareBase()
{
}
function InstallPlugin_mz_CloudflareBase()
{
}
function UninstallPlugin_mz_CloudflareBase()
{
}

// ---------------

function mz_CloudflareBase_R2($bucket_name= "zbp-def")
{
  global $zbp;
  $account_id = $zbp->Config('mz_CloudflareBase')->account_id;
  // R2 用不到 API 令牌
  // $api_token = $zbp->Config('mz_CloudflareBase')->api_token;
  $access_key_id = $zbp->Config('mz_CloudflareBase')->access_key_id;
  $access_key_secret = $zbp->Config('mz_CloudflareBase')->access_key_secret;
  return new mz_CloudflareBase_r2($account_id, $access_key_id, $access_key_secret, $bucket_name);
}

