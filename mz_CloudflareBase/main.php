<?php
require '../../../zb_system/function/c_system_base.php';
require '../../../zb_system/function/c_system_admin.php';
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

$act = GetVars('act', 'GET');
$suc = GetVars('suc', 'GET');
if ($act == 'save') {
  CheckIsRefererValid();
  foreach ($_POST as $key => $val) {
    if (substr($key, 0, 5) == 'read_') {
      continue;
    }
    $zbp->Config('mz_CloudflareBase')->$key = trim($val);
  }
  $zbp->SaveConfig('mz_CloudflareBase');
  $zbp->BuildTemplate();
  $zbp->SetHint('good');
  Redirect('./main.php' . ($suc == null ? '' : "?act={$suc}"));
}

$blogtitle = 'Cloudflare 基础封装';
require $blogpath . 'zb_system/admin/admin_header.php';
require $blogpath . 'zb_system/admin/admin_top.php';
?>
<div id="divMain">
  <div class="divHeader"><?php echo $blogtitle; ?></div>
  <div class="SubMenu">
    <a href="main.php" title="首页"><span class="m-left m-now">首页</span></a>
    <a href="demo_r2.php" title="演示-R2"><span class="m-left">演示-R2</span></a>
  </div>
  <div id="divMain2">
     <form action="<?php echo BuildSafeURL("main.php?act=save");?>" method="post">
       <table width="100%" class="tableBorder">
       <tr>
         <th width="10%">项目</th>
         <th>内容</th>
         <th width="45%">说明</th>
       </tr>
       <tr>
         <td>R2: 账户 ID</td>
         <td><?php zbpform::text("account_id", $zbp->Config("mz_CloudflareBase")->account_id, "90%"); ?></td>
         <td></td>
       </tr>
       <tr>
         <td>R2: 访问密钥 ID</td>
         <td><?php zbpform::text("access_key_id", $zbp->Config("mz_CloudflareBase")->access_key_id, "90%"); ?></td>
         <td></td>
       </tr>
       <tr>
         <td>R2: 机密访问密钥</td>
         <td><?php zbpform::text("access_key_secret", $zbp->Config("mz_CloudflareBase")->access_key_secret, "90%"); ?></td>
         <td></td>
       </tr>
       <tr>
       <td></td>
       <td colspan="2"><input type="submit" value="提交" /></td>
       </tr>
       </table>
     </form>
  </div>
</div>

<?php
require $blogpath . 'zb_system/admin/admin_footer.php';
RunTime();
?>
