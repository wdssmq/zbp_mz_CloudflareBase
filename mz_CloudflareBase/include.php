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
  $access_key_id = $zbp->Config('mz_CloudflareBase')->access_key_id;
  $access_key_secret = $zbp->Config('mz_CloudflareBase')->access_key_secret;
  return new mz_CloudflareBase_r2($account_id, $access_key_id, $access_key_secret, $bucket_name);
}

function mz_CloudflareBase_demo()
{
  $demo_r2 = mz_CloudflareBase_R2("r2-test");
  $buckets = $demo_r2->listBuckets();

  // debug
  // ob_clean();
  echo __FILE__ . "丨" . __LINE__ . ":<br>\n";
  var_dump($buckets["Buckets"]);
  echo "<br><br>\n\n";
  // die();
  // debug

}

// mz_CloudflareBase_demo();
