<?php
require_once(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/wp-admin/includes/plugin.php');
// 获取要下载的文件的 URL
$url = $_GET['url'];

// 解析文件名
$filename = basename(parse_url($url, PHP_URL_PATH));

// 设置文件保存路径
$filepath = '/path/to/your/directory/' . $filename;

// 使用 cURL 下载文件
$ch = curl_init($url);
$fp = fopen($filepath, 'wb');
curl_setopt($ch, CURLOPT_FILE, $fp);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_exec($ch);
curl_close($ch);
fclose($fp);

// 返回成功响应
echo json_encode(array('message' => 'File downloaded successfully.'));
?>
