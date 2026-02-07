<?php

/**
 * 钉钉机器人推送辅助
 * 从 dingtalk_robot 表读取配置（需配置 DB_* 环境变量），支持加签
 * 表结构建议：id, webhook_url（或 url）, secret（可选）
 */

require_once __DIR__ . '/../env.php';

function getDingtalkDb()
{
    $host = EnvLoader::get('DB_HOST', '127.0.0.1');
    $port = EnvLoader::get('DB_PORT', '3306');
    $dbname = EnvLoader::get('DB_NAME', '');
    $user = EnvLoader::get('DB_USER', '');
    $pass = EnvLoader::get('DB_PASSWORD', '');
    $charset = EnvLoader::get('DB_CHARSET', 'utf8mb4');
    if ($dbname === '' || $user === '') {
        throw new RuntimeException('请配置 .env 中的 DB_NAME、DB_USER、DB_PASSWORD');
    }
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
    return new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}

function getDingtalkRobotById($id)
{
    $pdo = getDingtalkDb();
    $stmt = $pdo->prepare('SELECT * FROM dingtalk_robot WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        throw new RuntimeException("dingtalk_robot 表中未找到 id={$id} 的记录");
    }
    $url = $row['webhook_url'] ?? $row['url'] ?? $row['webhook'] ?? '';
    if ($url === '') {
        throw new RuntimeException("dingtalk_robot id={$id} 的 webhook 地址为空");
    }
    return ['webhook_url' => $url, 'secret' => $row['secret'] ?? null];
}

function dingtalkSign($webhookUrl, $secret)
{
    $timestamp = (string) (time() * 1000);
    $toSign = $timestamp . "\n" . $secret;
    $sign = base64_encode(hash_hmac('sha256', $toSign, $secret, true));
    $sep = strpos($webhookUrl, '?') !== false ? '&' : '?';
    return $webhookUrl . $sep . 'timestamp=' . $timestamp . '&sign=' . rawurlencode($sign);
}

function sendDingtalkMarkdown($webhookUrl, $title, $text)
{
    $body = [
        'msgtype'  => 'markdown',
        'markdown' => ['title' => $title, 'text' => $text],
    ];
    $ch = curl_init($webhookUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($body),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err) {
        throw new RuntimeException('钉钉请求失败: ' . $err);
    }
    $ret = json_decode($res, true);
    if ($ret === null) {
        throw new RuntimeException('钉钉返回非 JSON: ' . substr($res, 0, 200));
    }
    return $ret;
}

function sendDingtalkMarkdownByRobotId($robotId, $title, $text)
{
    $robot = getDingtalkRobotById($robotId);
    $url = $robot['webhook_url'];
    if (!empty($robot['secret'])) {
        $url = dingtalkSign($url, $robot['secret']);
    }
    return sendDingtalkMarkdown($url, $title, $text);
}
