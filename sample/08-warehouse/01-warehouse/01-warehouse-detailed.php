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

    // 测试不同的参数组合
    $testCases = [
        [
            'name' => '默认参数（无任何过滤）',
            'params' => []
        ],
        [
            'name' => '查询所有类型和状态',
            'params' => [
                'is_delete' => '0,1',
                'offset' => 0,
                'length' => 1000
            ]
        ],
        [
            'name' => '只查询本地仓',
            'params' => [
                'type' => 1,
                'is_delete' => '0,1',
                'offset' => 0,
                'length' => 1000
            ]
        ],
        [
            'name' => '只查询海外仓',
            'params' => [
                'type' => 3,
                'is_delete' => '0,1',
                'offset' => 0,
                'length' => 1000
            ]
        ],
        [
            'name' => '只查询平台仓',
            'params' => [
                'type' => 4,
                'is_delete' => '0,1',
                'offset' => 0,
                'length' => 1000
            ]
        ],
        [
            'name' => '只查询AWD仓',
            'params' => [
                'type' => 6,
                'is_delete' => '0,1',
                'offset' => 0,
                'length' => 1000
            ]
        ]
    ];

    foreach ($testCases as $index => $testCase) {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "测试 " . ($index + 1) . ": " . $testCase['name'] . "\n";
        echo str_repeat("=", 60) . "\n";
        
        echo "查询参数: " . json_encode($testCase['params'], JSON_UNESCAPED_UNICODE) . "\n\n";
        
        // 发起POST请求
        $response = $client->makeRequest('/erp/sc/data/local_inventory/warehouse', 'POST', $testCase['params']);
        
        // 输出响应结果
        displayResponseInfo($response);
        
        if ($response['code'] == 0) {
            echo "仓库列表数据:\n";
            echo "总数: " . $response['total'] . "\n";
            
            if (!empty($response['data'])) {
                echo "返回数据条数: " . count($response['data']) . "\n\n";
                
                // 按类型分组统计
                $typeStats = [];
                foreach ($response['data'] as $warehouse) {
                    $type = $warehouse['type'];
                    if (!isset($typeStats[$type])) {
                        $typeStats[$type] = 0;
                    }
                    $typeStats[$type]++;
                }
                
                echo "类型统计:\n";
                $typeMap = [
                    1 => '本地仓',
                    3 => '海外仓',
                    4 => '平台仓',
                    6 => 'AWD仓'
                ];
                foreach ($typeStats as $type => $count) {
                    $typeName = $typeMap[$type] ?? '未知类型';
                    echo "  - {$typeName} (类型{$type}): {$count} 个\n";
                }
                
                // 显示前3个仓库的详细信息
                echo "\n前3个仓库详情:\n";
                $showCount = min(3, count($response['data']));
                for ($i = 0; $i < $showCount; $i++) {
                    $warehouse = $response['data'][$i];
                    echo "  " . ($i + 1) . ". ID:{$warehouse['wid']} 名称:{$warehouse['name']} 类型:{$warehouse['type']} 删除:{$warehouse['is_delete']}\n";
                }
            } else {
                echo "暂无仓库数据\n";
            }
        } else {
            displayErrorInfo($response);
        }
    }

} catch (Exception $e) {
    echo "发生错误: " . $e->getMessage() . "\n";
    echo "错误类型: " . get_class($e) . "\n";
}
