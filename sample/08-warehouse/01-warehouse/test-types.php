<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config.php';

try {
    // 初始化客户端
    $client = createOpenAPIClient();

    // 生成AccessToken
    echo "正在生成AccessToken...\n";
    $accessTokenDto = $client->generateAccessToken();
    displayAccessTokenInfo($accessTokenDto);

    // 测试不同的仓库类型
    $types = [
        1 => '本地仓',
        3 => '海外仓', 
        4 => '平台仓',
        6 => 'AWD仓'
    ];

    foreach ($types as $type => $typeName) {
        echo "\n" . str_repeat("-", 50) . "\n";
        echo "查询 {$typeName} (类型: {$type})\n";
        echo str_repeat("-", 50) . "\n";
        
        $params = [
            'type' => $type,
            'is_delete' => '0,1',
            'offset' => 0,
            'length' => 1000
        ];
        
        echo "参数: " . json_encode($params) . "\n";
        
        $response = $client->makeRequest('/erp/sc/data/local_inventory/warehouse', 'POST', $params);
        
        if ($response['code'] == 0) {
            echo "总数: " . $response['total'] . "\n";
            echo "返回: " . count($response['data']) . " 条\n";
            
            if (!empty($response['data'])) {
                echo "仓库列表:\n";
                foreach ($response['data'] as $warehouse) {
                    echo "  - ID:{$warehouse['wid']} 名称:{$warehouse['name']} 删除:{$warehouse['is_delete']}\n";
                }
            }
        } else {
            echo "错误: " . $response['message'] . "\n";
        }
    }

} catch (Exception $e) {
    echo "发生错误: " . $e->getMessage() . "\n";
}
