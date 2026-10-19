<?php
require __DIR__ . '/../../../../zb_system/function/c_system_base.php';
require __DIR__ . '/../../../../zb_system/function/c_system_admin.php';
$zbp->Load();
$action = 'root';
if (!$zbp->CheckRights($action)) {
  $zbp->ShowError(6);
  die();
}
if (!$zbp->CheckPlugin('mz_CloudflareBase')) {
  $zbp->ShowError(48);
  die();
}
