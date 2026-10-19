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

$r2Config = $zbp->Config('mz_CloudflareBase');
if (
  trim((string) ($r2Config->account_id ?? '')) === ''
  || trim((string) ($r2Config->access_key_id ?? '')) === ''
  || trim((string) ($r2Config->access_key_secret ?? '')) === ''
) {
  $zbp->ShowError('请先在插件设置中填写完整的 Cloudflare R2 账户 ID 和访问密钥。', __FILE__, __LINE__);
  die();
}

$bucketName = 'r2-test';
$verified = false;
$objects = null;
$error = null;
$errorStage = '';

if (GetVars('act', 'POST') === 'test') {
  CheckIsRefererValid();
  $bucketName = trim((string) GetVars('bucket', 'POST'));

  if ($bucketName === '') {
    $error = '请输入 Bucket 名称。';
    $errorStage = 'verify';
  } else {
    $r2 = mz_CloudflareBase_R2($bucketName);
    $result = $r2->headBucket();
    if ($result === false || $r2->err['code'] !== 0) {
      $error = $r2->err['raw_msg'] ?: $r2->err['msg'];
      $errorStage = 'verify';
    } else {
      $verified = true;
      $objects = $r2->listObjects();
      if ($objects === false || $r2->err['code'] !== 0) {
        $error = $r2->err['raw_msg'] ?: $r2->err['msg'];
        $errorStage = 'list';
      }
    }
  }
}

$codeExample = <<<'PHP'
<?php
// ----
$bucketName = 'r2-test';
$r2 = mz_CloudflareBase_R2($bucketName);

// ----
$result = $r2->headBucket();
if ($result === false || $r2->err['code'] !== 0) {
  die('验证失败：' . ($r2->err['raw_msg'] ?: $r2->err['msg']));
}

// ----
$objects = $r2->listObjects();
if ($objects === false || $r2->err['code'] !== 0) {
  die('读取对象列表失败：' . ($r2->err['raw_msg'] ?: $r2->err['msg']));
}

foreach (($objects['Contents'] ?? array()) as $object) {
  echo ($object['Key'] ?? '') . PHP_EOL;
}

// ----
$uploadData = array(
  'key' => 'demo/hello.txt',
  'body' => 'Hello, Cloudflare R2!',
  'type' => 'text/plain; charset=UTF-8',
  'acl' => 'private',
);
$uploadResult = $r2->uploadFile($uploadData);
if ($uploadResult === false || $r2->err['code'] !== 0) {
  die('上传失败：' . ($r2->err['raw_msg'] ?: $r2->err['msg']));
}

echo '上传成功：' . $uploadData['key'] . PHP_EOL;
PHP;

?>
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>R2 Bucket 鉴权验证</title>
  <link rel="stylesheet" href="style_r2.css">
  </head>
  <body>
    <main class="r2-check-page">
      <h1 class="r2-check-title">Cloudflare R2</h1>
      <section class="r2-check-card">
        <h2>Bucket 鉴权验证</h2>
        <p>通过 HeadBucket 检查当前配置的密钥是否可以访问指定 Bucket，并列出当前可见对象，不读取或修改对象内容。</p>
        <form class="r2-check-form" action="<?php echo BuildSafeURL('demo_r2.php'); ?>" method="post">
          <input type="hidden" name="act" value="test">
          <input type="text" name="bucket" value="<?php echo htmlspecialchars($bucketName, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Bucket 名称" aria-label="Bucket 名称" required>
          <input type="submit" value="验证访问权限">
        </form>
        <?php if ($verified) { ?>
          <div class="r2-check-result is-good">验证成功：当前密钥可以访问 Bucket「<?php echo htmlspecialchars($bucketName, ENT_QUOTES, 'UTF-8'); ?>」。</div>
        <?php } ?>
        <?php if ($error !== null) { ?>
          <div class="r2-check-result is-bad"><?php echo $errorStage === 'list' ? '读取对象列表失败：' : '验证失败：'; ?><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php } elseif ($objects !== null) { ?>
          <?php $objectList = $objects['Contents'] ?? array(); ?>
          <?php if (count($objectList) === 0) { ?>
            <p>该 Bucket 当前没有可见对象。</p>
          <?php } else { ?>
            <h3>对象列表（<?php echo count($objectList); ?> 个）</h3>
            <div class="r2-check-result">
              <table>
                <thead>
                  <tr>
                    <th>对象 Key</th>
                    <th>大小</th>
                    <th>最后修改时间</th>
                    <th>存储类型</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($objectList as $object) { ?>
                    <tr>
                      <td><?php echo htmlspecialchars((string) ($object['Key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo number_format((int) ($object['Size'] ?? 0)); ?> B</td>
                      <td><?php echo htmlspecialchars(isset($object['LastModified']) ? $object['LastModified']->format('Y-m-d H:i:s') : '', ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars((string) ($object['StorageClass'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                  <?php } ?>
                </tbody>
              </table>
            </div>
            <?php if (!empty($objects['IsTruncated'])) { ?>
              <p>对象超过单次返回上限，当前仅显示本次返回的部分对象。</p>
            <?php } ?>
          <?php } ?>
        <?php } ?>
      </section>
      <section class="r2-check-card">
        <h2>调用代码示例</h2>
        <p>以下示例演示先验证 Bucket 权限、读取现有对象列表，再上传文本对象。</p>
        <pre class="r2-check-code"><code><?php echo htmlspecialchars($codeExample, ENT_QUOTES, 'UTF-8'); ?></code></pre>
      </section>
    </main>
  </body>
</html>
