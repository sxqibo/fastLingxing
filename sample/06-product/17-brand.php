<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

try {
    // 初始化客户端
    $client = createOpenAPIClient();

    // 生成AccessToken
    echo "正在生成AccessToken...\n";
    $accessTokenDto = $client->generateAccessToken();
    displayAccessTokenInfo($accessTokenDto);

    // 查询产品品牌列表
    echo "正在查询产品品牌列表...\n";
    
    // 请求参数
    $params = [
        'offset' => 0,    // 分页偏移量，默认0
        'length' => 100   // 分页长度，默认1000，上限1000
    ];
    
    // 发起POST请求
    $response = $client->makeRequest('/erp/sc/data/local_inventory/brand', 'POST', $params);
    
    // 输出响应结果
    displayResponseInfo($response);
    
    if ($response['code'] == 0) {
        echo "品牌列表数据:\n";
        if (!empty($response['data'])) {
            foreach ($response['data'] as $index => $brand) {
                echo "品牌 " . ($index + 1) . ":\n";
                echo "  - 品牌ID: " . $brand['bid'] . "\n";
                echo "  - 品牌名称: " . $brand['title'] . "\n";
                echo "  - 品牌简码: " . ($brand['brand_code'] ?? '无') . "\n";
                echo "\n";
            }
            echo "共查询到 " . count($response['data']) . " 个品牌\n";
        } else {
            echo "暂无品牌数据\n";
        }
    } else {
        displayErrorInfo($response);
    }

} catch (Exception $e) {
    echo "发生错误: " . $e->getMessage() . "\n";
    echo "错误类型: " . get_class($e) . "\n";
}
