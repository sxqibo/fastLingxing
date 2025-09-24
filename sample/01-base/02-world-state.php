<?php
/**
 * 查询亚马逊国家下地区列表
 * 
 * 接口信息：
 * API Path: /erp/sc/data/worldState/lists
 * 请求协议: HTTPS
 * 请求方式: POST
 * 令牌桶容量: 1
 * 
 * 使用方法：
 * php 02-world-state.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Sxqibo\FastLingxing\Services\OpenAPIRequestService;

// 加载配置
$config = include __DIR__ . '/../config.php';

// 创建API客户端
$client = new OpenAPIRequestService(
    $config['lingxing']['api_url'],
    $config['lingxing']['app_id'],
    $config['lingxing']['app_secret']
);

echo "=== 查询亚马逊国家下地区列表 ===\n\n";

// 要查询的国家代码列表
$countries = [
    'US' => '美国',
    'DE' => '德国', 
    'UK' => '英国',
    'FR' => '法国',
    'IT' => '意大利',
    'ES' => '西班牙',
    'CA' => '加拿大',
    'JP' => '日本',
    'AU' => '澳大利亚',
    'IN' => '印度'
];

foreach ($countries as $countryCode => $countryName) {
    echo "正在查询 {$countryName} ({$countryCode}) 的地区列表...\n";
    
    try {
        // 调用API
        $response = $client->post('/erp/sc/data/worldState/lists', [
            'country_code' => $countryCode
        ]);
        
        if ($response['code'] === 0) {
            $data = $response['data'] ?? [];
            $total = $response['total'] ?? 0;
            
            echo "✓ 查询成功，共找到 {$total} 个地区\n";
            
            if (!empty($data)) {
                echo "地区列表：\n";
                foreach ($data as $index => $state) {
                    $stateName = $state['state_or_province_name'] ?? '未知';
                    $stateCode = $state['code'] ?? '未知';
                    echo "  " . ($index + 1) . ". {$stateName} ({$stateCode})\n";
                }
            } else {
                echo "  暂无地区数据\n";
            }
        } else {
            echo "✗ 查询失败: " . ($response['message'] ?? '未知错误') . "\n";
        }
        
    } catch (Exception $e) {
        echo "✗ 查询异常: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // 避免请求过于频繁，稍作延迟
    sleep(1);
}

echo "=== 查询完成 ===\n";
