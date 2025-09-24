<?php
/**
 * 批量获取Listing费用
 * 支持获取Listing-FBA预估费
 * 
 * 接口信息：
 * API Path: /listing/listing/open/api/listing/getPrices
 * 请求协议: HTTPS
 * 请求方式: POST
 * 令牌桶容量: 10
 * 
 * 使用方法：
 * php 05-get-price.php
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use Sxqibo\FastLingxing\Services\OpenAPIRequestService;

// 加载配置
require_once __DIR__ . '/../../../sample/config.php';

// 创建API客户端
$client = createOpenAPIClient();

echo "=== 批量获取Listing费用 ===\n\n";

// 生成AccessToken
echo "正在生成AccessToken...\n";
$accessTokenDto = $client->generateAccessToken();
displayAccessTokenInfo($accessTokenDto);

// 测试数据 - 可以修改这里的店铺ID和MSKU
$testData = [
    [
        'sid' => 6584,
        'msku' => 'KN-1DD4-592V'
    ],
    // 可以添加更多测试数据
    // [
    //     'sid' => 10742,
    //     'msku' => 'TEST-MSKU-001'
    // ],
    // [
    //     'sid' => 10743,
    //     'msku' => 'TEST-MSKU-002'
    // ]
];

echo "准备查询 " . count($testData) . " 个Listing的费用信息...\n\n";

try {
    // 调用API
    $response = $client->makeRequest('/listing/listing/open/api/listing/getPrices', 'POST', [
        'data' => $testData
    ]);
    
    if ($response['code'] === 0) {
        echo "✓ 查询成功\n";
        echo "请求ID: " . ($response['request_id'] ?? 'N/A') . "\n";
        echo "响应时间: " . ($response['response_time'] ?? 'N/A') . "\n\n";
        
        // 显示成功的数据
        $data = $response['data'] ?? [];
        if (!empty($data)) {
            echo "=== 成功获取费用的Listing ===\n";
            foreach ($data as $index => $item) {
                echo ($index + 1) . ". 店铺ID: {$item['sid']}\n";
                echo "   MSKU: {$item['msku']}\n";
                echo "   FBA预估费: {$item['fba_fee']} {$item['fba_fee_currency_code']}\n";
                echo "\n";
            }
        } else {
            echo "暂无成功获取费用的数据\n\n";
        }
        
        // 显示错误信息
        $errorDetails = $response['error_details'] ?? [];
        if (!empty($errorDetails)) {
            echo "=== 获取费用失败的Listing ===\n";
            foreach ($errorDetails as $index => $error) {
                echo ($index + 1) . ". 店铺ID: {$error['sid']}\n";
                echo "   MSKU: {$error['msku']}\n";
                echo "   错误信息: {$error['message']}\n";
                echo "\n";
            }
        }
        
        echo "总计: " . ($response['total'] ?? 0) . " 条记录\n";
        
    } else {
        echo "✗ 查询失败\n";
        echo "错误码: " . ($response['code'] ?? 'N/A') . "\n";
        echo "错误信息: " . ($response['message'] ?? '未知错误') . "\n";
        
        // 显示详细错误信息
        $errorDetails = $response['error_details'] ?? [];
        if (!empty($errorDetails)) {
            echo "\n详细错误信息:\n";
            foreach ($errorDetails as $error) {
                if (is_string($error)) {
                    echo "- {$error}\n";
                } else {
                    echo "- 店铺ID: {$error['sid']}, MSKU: {$error['msku']}, 错误: {$error['message']}\n";
                }
            }
        }
    }
    
} catch (Exception $e) {
    echo "✗ 查询异常: " . $e->getMessage() . "\n";
    echo "异常类型: " . get_class($e) . "\n";
    echo "文件: " . $e->getFile() . " 行号: " . $e->getLine() . "\n";
    echo "堆栈跟踪:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== 查询完成 ===\n";
