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

$r2Config = $zbp->Config('mz_CloudflareBase');
if (
  trim((string) ($r2Config->account_id ?? '')) === ''
  || trim((string) ($r2Config->access_key_id ?? '')) === ''
  || trim((string) ($r2Config->access_key_secret ?? '')) === ''
) {
  $zbp->ShowError('请先在插件设置中填写完整的 Cloudflare R2 账户 ID 和访问密钥。', __FILE__, __LINE__);
  die();
}

// ---------------

$demoOpts = array(
  "def_bucket_name" => "r2-test",
  // 设置一个 flag，用于记录是否存在默认 bucket
  "def_bucket_flag" => false,
  "list_buckets" => array(),
  "list_objects" => array(),
  "object_data" => array(
    "key" => "test.txt",
    "content" => "Hello World!",
    "type" => "text/plain",
    "acl" => "public-read",
    "url" => "",
  ),
  'error' => array(
    'code' => 0,
    'message' => '',
  ),
);

// 格式化输出
function mz_CloudflareBase_LogData($data, $isSub = false)
{
  global $demoOpts;
  $tpl = "<p>-name-：-value-</p>";
  foreach ($data as $key => $value) {
    if (is_array($value)) {
      mz_CloudflareBase_LogData($value, true);
      echo "---<br>";
    } else {
      echo str_replace("-name-", $key, str_replace("-value-", $value, $tpl));
    }
    if ($key == "Name" && $value == $demoOpts["def_bucket_name"]) {
      $demoOpts["def_bucket_flag"] = true;
    }
  }
}

// 输出 bucket 列表
function mz_CloudflareBase_LogBuckets()
{
  global $demoOpts;
  echo "<h2>Bucket 列表</h2>";
  mz_CloudflareBase_LogData($demoOpts["list_buckets"]);

  if ($demoOpts["def_bucket_flag"]) {
    echo "<p>默认 bucket 存在</p>";
  } else {
    echo "<p>默认 bucket 不存在</p>";
    // 记录错误
    $demoOpts["error"]["code"] = 1;
    $demoOpts["error"]["message"] = "默认 bucket 不存在";
  }
}

// 输出 object 列表
function mz_CloudflareBase_LogObjects()
{
  global $demoOpts;
  echo "<h2>Object 列表</h2>";
  mz_CloudflareBase_LogData($demoOpts["list_objects"]);
}

// 查询 bucket 列表
function mz_CloudflareBase_ListBuckets()
{
  global $demoOpts;
  $demo_r2 = mz_CloudflareBase_R2($demoOpts["def_bucket_name"]);
  $buckets = $demo_r2->listBuckets();

  // // debug
  // // ob_clean();
  // echo __FILE__ . "丨" . __LINE__ . ":<br>\n";
  // var_dump($buckets);
  // echo "<br><br>\n\n";
  // // die();
  // // debug

  $demoOpts["list_buckets"] = $buckets["Buckets"];
}

mz_CloudflareBase_ListBuckets();

// 查询 object 列表
function mz_CloudflareBase_ListObjects()
{
  global $demoOpts;
  $demo_r2 = mz_CloudflareBase_R2($demoOpts["def_bucket_name"]);
  $objects = $demo_r2->listObjects();

  // // debug
  // // ob_clean();
  // echo __FILE__ . "丨" . __LINE__ . ":<br>\n";
  // var_dump($objects["Contents"]);
  // echo "<br><br>\n\n";
  // // die();
  // // debug

  $demoOpts["list_objects"] = $objects["Contents"];
}

mz_CloudflareBase_ListObjects();

// 上传测试函数
function mz_CloudflareBase_UploadTest()
{
  global $demoOpts;
  $demo_r2 = mz_CloudflareBase_R2($demoOpts["def_bucket_name"]);

  $demo_r2->uploadFile($demoOpts["object_data"]);
  // $demo_r2->uploadFile("test.txt", "test.txt");
  return $demo_r2->err;
}

$act = GetVars('act', 'GET');
$suc = GetVars('suc', 'GET');
if ($act == 'upload') {
  CheckIsRefererValid();
  if ($demoOpts["error"]["code"] > 0) {
    $zbp->SetHint("bad", "{$demoOpts["error"]["code"]}: {$demoOpts["error"]["message"]}");
    Redirect('./demo_r2.php');
  }
  $rlt = (object) mz_CloudflareBase_UploadTest();
  if ($rlt->code == 0) {
    $zbp->SetHint("good");
    $url = urlencode(($demoOpts["object_data"]["url"]));
    Redirect("./demo_r2.php?suc={$url}");
  }

  $zbp->SetHint("{$rlt->code}: {$rlt->msg}");
}
if ($suc) {
  $url = urldecode($suc);
  $demoOpts["object_data"]["url"] = $url;
}

$blogtitle = 'Cloudflare 基础封装';
require $blogpath . 'zb_system/admin/admin_header.php';
require $blogpath . 'zb_system/admin/admin_top.php';
?>
<div id="divMain">
  <div class="divHeader"><?php echo $blogtitle; ?></div>
  <div class="SubMenu">
    <a href="main.php" title="首页"><span class="m-left">首页</span></a>
    <a href="demo_r2.php" title="演示-R2"><span class="m-left m-now">演示-R2</span></a>
  </div>
  <div id="divMain2">

    <?php mz_CloudflareBase_LogBuckets(); ?>
    <h3>--------------</h3>
    <p>测试上传：<a class="button" href="<?php echo BuildSafeURL("demo_r2.php?act=upload"); ?>">点击这里</a></p>
    <?php
    if ($demoOpts["object_data"]["url"]) {
      echo "<p>测试上传成功，文件地址：<a href=\"{$demoOpts["object_data"]["url"]}\">{$demoOpts["object_data"]["url"]}</a></p>";
      echo "<p>↑ 这个地址其实并不能直接访问，需要再绑定「自定义域」来实现公开访问；</p>";
    }
    ?>
    <h3>--------------</h3>
    <?php mz_CloudflareBase_LogObjects(); ?>

  </div>
</div>

<?php
require $blogpath . 'zb_system/admin/admin_footer.php';
RunTime();
?>