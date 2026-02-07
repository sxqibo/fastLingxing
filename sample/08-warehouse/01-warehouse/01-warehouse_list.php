<?php
/**
 * 仓库列表查询示例
 * 
 * 接口说明：
 * - API路径: /erp/sc/data/local_inventory/warehouse
 * - 请求方式: POST
 * - 令牌桶容量: 1
 * 
 * 主要功能：
 * 1. 查询本地仓库列表信息
 * 2. 支持按仓库类型筛选（本地仓、海外仓、平台仓、AWD仓）
 * 3. 支持按删除状态筛选
 * 4. 支持分页查询
 * 
 * 返回数据包含：
 * - 基本信息：仓库ID、仓库名称、仓库类型等
 * - 第三方仓库信息：国家/地区、状态、仓库代码、仓库名称等
 * - 服务商信息：服务商ID、服务商名称等
 */

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config.php';

try {
    // 初始化客户端
    $client = createOpenAPIClient();

    // 生成AccessToken
    echo "正在生成AccessToken...\n";
    $accessTokenDto = $client->generateAccessToken();
    displayAccessTokenInfo($accessTokenDto);

    // 查询仓库列表
    echo "正在查询仓库列表...\n";
    
    // 请求参数配置
    $params = [
        'type' => 3,          // 仓库类型：1 本地仓，3 海外仓，4 亚马逊平台仓，6 AWD仓
        // 'sub_type' => 2,      // 海外仓子类型：1 无API海外仓，2 有API海外仓（只在type=3生效）
        'is_delete' => '0',     // 是否删除：0 未删除，1 已删除
        'offset' => DEFAULT_OFFSET,    // 分页偏移量，默认0
        'length' => MAX_LENGTH         // 分页长度，默认1000条
    ];
    
    echo "查询参数: " . json_encode($params, JSON_UNESCAPED_UNICODE) . "\n\n";
    
    // 发起POST请求
    $response = $client->makeRequest('/erp/sc/data/local_inventory/warehouse', 'POST', $params);
    
    // 输出响应结果
    displayResponseInfo($response);
    
    if ($response['code'] == 0) {
        echo "\n=== 仓库列表查询结果 ===\n";
        echo "请求状态: 成功\n";
        echo "请求ID: " . $response['request_id'] . "\n";
        echo "响应时间: " . $response['response_time'] . "\n";
        echo "数据总数: " . $response['total'] . "\n";
        echo "当前页数据量: " . count($response['data']) . "\n";
        echo "================================\n\n";
        
        if (!empty($response['data'])) {
            foreach ($response['data'] as $index => $warehouse) {
                echo "仓库 " . ($index + 1) . ":\n";
                echo "  === 基本信息 ===\n";
                echo "  - 系统仓库ID: " . $warehouse['wid'] . " 【系统仓库唯一标识】\n";
                echo "  - 仓库名: " . $warehouse['name'] . " 【仓库名称】\n";
                
                // 仓库类型说明
                $typeMap = [
                    1 => '本地仓',
                    3 => '海外仓',
                    4 => '平台仓',
                    6 => 'AWD仓'
                ];
                $typeText = $typeMap[$warehouse['type']] ?? '未知类型';
                echo "  - 仓库类型: " . $typeText . " (" . $warehouse['type'] . ") 【仓库类型：1 本地仓，3 海外仓，4 平台仓，6 AWD仓】\n";
                
                // 删除状态说明
                $deleteText = $warehouse['is_delete'] == '1' ? '已删除' : '未删除';
                echo "  - 是否删除: " . $deleteText . " 【删除状态：0 未删除，1 已删除】\n";
                
                echo "  === 第三方仓库信息 ===\n";
                
                // 第三方仓库信息
                if (isset($warehouse['t_country_area_name']) && !empty($warehouse['t_country_area_name'])) {
                    echo "  - 第三方仓库国家/地区: " . $warehouse['t_country_area_name'] . " 【第三方仓库国家/地区】\n";
                }
                
                if (isset($warehouse['t_status'])) {
                    $statusText = $warehouse['t_status'] == 1 ? '启用' : '未启用';
                    echo "  - 状态: " . $statusText . " 【状态：0 未启用，1 启用】\n";
                }
                
                if (isset($warehouse['t_warehouse_code']) && !empty($warehouse['t_warehouse_code'])) {
                    echo "  - 第三方仓库代码: " . $warehouse['t_warehouse_code'] . " 【第三方仓库代码】\n";
                }
                
                if (isset($warehouse['t_warehouse_name']) && !empty($warehouse['t_warehouse_name'])) {
                    echo "  - 第三方仓库名: " . $warehouse['t_warehouse_name'] . " 【第三方仓库名称】\n";
                }
                
                if (isset($warehouse['country_code']) && !empty($warehouse['country_code'])) {
                    echo "  - 国家代码: " . $warehouse['country_code'] . " 【国家代码】\n";
                }
                
                echo "  === 服务商信息 ===\n";
                
                // 服务商信息（仅type=3且仓库为第三方海外仓时有值）
                if (isset($warehouse['wp_id']) && !empty($warehouse['wp_id'])) {
                    echo "  - 服务商ID: " . $warehouse['wp_id'] . " 【服务商ID，仅海外仓有值】\n";
                }
                
                if (isset($warehouse['wp_name']) && !empty($warehouse['wp_name'])) {
                    echo "  - 系统服务商名称: " . $warehouse['wp_name'] . " 【系统服务商名称，仅海外仓有值】\n";
                }
                
                echo "\n";
            }
            echo "共查询到 " . count($response['data']) . " 个仓库\n";
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
