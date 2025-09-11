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

    // 查询所有类型仓库列表（只查询未删除的）
    echo "正在查询所有类型仓库列表（只查询未删除的）...\n";
    
    // 请求参数 - 查询所有类型的仓库，只查询未删除的
    $params = [
        // 不设置type参数，让接口返回所有类型的仓库
        // 'type' => 1,        // 仓库类型：1 本地仓，3 海外仓，4 亚马逊平台仓，6 AWD仓
        // 'sub_type' => 2,  // 海外仓子类型：1 无API海外仓，2 有API海外仓（只在type=3时生效）
        'is_delete' => '0', // 只查询未删除的仓库
        'offset' => DEFAULT_OFFSET,    // 分页偏移量，默认0
        'length' => MAX_LENGTH,   // 分页长度，默认1000条
    ];
    
    echo "查询参数: " . json_encode($params, JSON_UNESCAPED_UNICODE) . "\n\n";
    
    // 发起POST请求
    $response = $client->makeRequest('/erp/sc/data/local_inventory/warehouse', 'POST', $params);
    
    // 输出响应结果
    displayResponseInfo($response);
    
    if ($response['code'] == 0) {
        echo "仓库列表数据:\n";
        echo "总数: " . $response['total'] . "\n\n";
        
        if (!empty($response['data'])) {
            // 按类型分组统计
            $typeStats = [];
            $typeMap = [
                1 => '本地仓',
                3 => '海外仓',
                4 => '平台仓',
                6 => 'AWD仓'
            ];
            
            foreach ($response['data'] as $warehouse) {
                $type = $warehouse['type'];
                if (!isset($typeStats[$type])) {
                    $typeStats[$type] = [];
                }
                $typeStats[$type][] = $warehouse;
            }
            
            echo "类型统计:\n";
            foreach ($typeStats as $type => $warehouses) {
                $typeName = $typeMap[$type] ?? '未知类型';
                echo "  - {$typeName} (类型{$type}): " . count($warehouses) . " 个\n";
            }
            echo "\n";
            
            // 按类型分组显示详细信息
            foreach ($typeStats as $type => $warehouses) {
                $typeName = $typeMap[$type] ?? '未知类型';
                echo str_repeat("=", 60) . "\n";
                echo "{$typeName} (类型{$type}) - 共 " . count($warehouses) . " 个\n";
                echo str_repeat("=", 60) . "\n";
                
                foreach ($warehouses as $index => $warehouse) {
                    echo "仓库 " . ($index + 1) . ":\n";
                    echo "  - 系统仓库ID: " . $warehouse['wid'] . "\n";
                    echo "  - 仓库名: " . $warehouse['name'] . "\n";
                    echo "  - 仓库类型: " . $typeName . " (" . $warehouse['type'] . ")\n";
                    
                    // 删除状态说明
                    $deleteText = $warehouse['is_delete'] == '1' ? '已删除' : '未删除';
                    echo "  - 是否删除: " . $deleteText . "\n";
                    
                    // 第三方仓库信息
                    if (!empty($warehouse['t_country_area_name'])) {
                        echo "  - 第三方仓库国家/地区: " . $warehouse['t_country_area_name'] . "\n";
                    }
                    
                    if (!empty($warehouse['t_status'])) {
                        $statusText = $warehouse['t_status'] == 1 ? '启用' : '停用';
                        echo "  - 状态: " . $statusText . "\n";
                    }
                    
                    if (!empty($warehouse['t_warehouse_code'])) {
                        echo "  - 第三方仓库代码: " . $warehouse['t_warehouse_code'] . "\n";
                    }
                    
                    if (!empty($warehouse['t_warehouse_name'])) {
                        echo "  - 第三方仓库名: " . $warehouse['t_warehouse_name'] . "\n";
                    }
                    
                    if (!empty($warehouse['country_code'])) {
                        echo "  - 国家代码: " . $warehouse['country_code'] . "\n";
                    }
                    
                    // 服务商信息（仅type=3且仓库为第三方海外仓时有值）
                    if (!empty($warehouse['wp_id'])) {
                        echo "  - 服务商ID: " . $warehouse['wp_id'] . "\n";
                    }
                    
                    if (!empty($warehouse['wp_name'])) {
                        echo "  - 系统服务商名称: " . $warehouse['wp_name'] . "\n";
                    }
                    
                    echo "\n";
                }
            }
            
            echo "总计查询到 " . count($response['data']) . " 个仓库\n";
        } else {
            echo "暂无仓库数据\n";
        }
    } else {
        displayErrorInfo($response);
    }

} catch (Exception $e) {
    echo "发生错误: " . $e->getMessage() . "\n";
    echo "错误类型: " . get_class($e) . "\n";
}
