<?php

function mz_CloudflareBase_R2($bucket_name = "zbp-def")
{
  global $zbp;
  $account_id = $zbp->Config('mz_CloudflareBase')->account_id;
  $access_key_id = $zbp->Config('mz_CloudflareBase')->access_key_id;
  $access_key_secret = $zbp->Config('mz_CloudflareBase')->access_key_secret;
  return new mz_CloudflareBase_r2($account_id, $access_key_id, $access_key_secret, $bucket_name);
}
