<?php
/**
 * 系统产品与第三方海外仓产品映射列表查询示例
 * 
 * 接口说明：
 * - API路径: /erp/sc/routing/owms/inbound/matchSkuList
 * - 请求方式: POST
 * - 令牌桶容量: 1
 * 
 * 主要功能：
 * 1. 查询本地产品与第三方海外仓产品映射列表
 * 2. 支持按仓库ID筛选
 * 3. 支持按配对状态筛选
 * 4. 支持分页查询
 * 
 * 返回数据包含：
 * - 仓库信息：仓库名称、仓库代码、仓库ID等
 * - 本地产品信息：本地SKU、本地名称等
 * - 第三方产品信息：第三方产品名称、编码、唯一码等
 * - 配对状态：是否已配对、配对数量等
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

    // 查询系统产品与第三方海外仓产品映射列表
    echo "正在查询系统产品与第三方海外仓产品映射列表...\n";
    
    // 请求参数配置
    $params = [
        'wid' => '18568',      // 仓库id，多个用英文逗号分隔，必填
        // 'is_matched' => 1,      // 是否配对：0 未配对，1 配对，选填
        'offset' => DEFAULT_OFFSET,    // 分页偏移量，默认0
        'length' => DEFAULT_LENGTH     // 分页长度，默认20
    ];
    
    echo "查询参数: " . json_encode($params, JSON_UNESCAPED_UNICODE) . "\n\n";
    
    // 发起POST请求
    $response = $client->makeRequest('/erp/sc/routing/owms/inbound/matchSkuList', 'POST', $params);
    
    // 输出响应结果
    displayResponseInfo($response);
    
    if ($response['code'] == 0) {
        echo "\n=== 系统产品与第三方海外仓产品映射列表查询结果 ===\n";
        echo "请求状态: 成功\n";
        echo "请求ID: " . $response['request_id'] . "\n";
        echo "响应时间: " . $response['response_time'] . "\n";
        echo "数据总数: " . $response['total'] . "\n";
        echo "当前页数据量: " . count($response['data']) . "\n";
        echo "================================\n\n";
        
        if (!empty($response['data'])) {
            foreach ($response['data'] as $index => $mappingItem) {
                echo "映射项 " . ($index + 1) . ":\n";
                echo "  === 仓库信息 ===\n";
                echo "  - 仓库ID: " . $mappingItem['wid'] . " 【仓库唯一标识】\n";
                echo "  - 仓库名称: " . $mappingItem['warehouse_name'] . " 【仓库名称】\n";
                echo "  - 仓库代码: " . $mappingItem['warehouse_code'] . " 【仓库代码】\n";
                echo "  - 本地仓库名称: " . $mappingItem['warehouse_name_local'] . " 【本地仓库名称】\n";
                
                echo "  === 本地产品信息 ===\n";
                echo "  - 本地SKU: " . $mappingItem['local_sku'] . " 【本地SKU编码】\n";
                echo "  - 本地名称: " . $mappingItem['local_name'] . " 【本地SKU名称】\n";
                
                echo "  === 第三方产品信息 ===\n";
                echo "  - 三方产品名称: " . $mappingItem['oversea_product_name'] . " 【第三方产品名称】\n";
                echo "  - 三方产品唯一码: " . ($mappingItem['oversea_unique_code'] ?: '无') . " 【第三方产品唯一码，可能为空】\n";
                echo "  - 三方SKU编码: " . $mappingItem['oversea_product_code'] . " 【第三方SKU编码】\n";
                echo "  - 三方规格: " . ($mappingItem['oversea_spec'] ?: '无') . " 【规格，可能为空】\n";
                
                echo "  === 配对状态 ===\n";
                $isMatchedText = $mappingItem['is_matched'] ? '已配对' : '未配对';
                echo "  - 是否已配对: " . $isMatchedText . " (" . ($mappingItem['is_matched'] ? 'true' : 'false') . ") 【配对状态】\n";
                echo "  - 配对状态文本: " . $mappingItem['is_matched_text'] . " 【配对状态文本展示】\n";
                echo "  - 配对数量: " . $mappingItem['match_num'] . " 【配对数量，默认为1】\n";
                echo "  - 配对信息: " . ($mappingItem['match_msg'] ?: '无') . " 【配对相关信息】\n";
                
                echo "  === 其他信息 ===\n";
                echo "  - 三方产品ID: " . $mappingItem['twp_id'] . " 【第三方产品ID】\n";
                echo "  - 产品ID: " . $mappingItem['product_id'] . " 【产品ID】\n";
                echo "  - FNSKU: " . $mappingItem['fnsku'] . " 【FNSKU】\n";
                echo "  - 店铺ID: " . $mappingItem['sid'] . " 【店铺ID】\n";
                echo "  - 店铺名称: " . $mappingItem['seller_name'] . " 【店铺名称】\n";
                echo "  - 国家名称: " . $mappingItem['country_name'] . " 【国家名称】\n";
                
                echo "\n";
            }
            echo "共查询到 " . count($response['data']) . " 个映射项\n";
        } else {
            echo "暂无映射数据\n";
        }
    } else {
        displayErrorInfo($response);
    }

} catch (Exception $e) {
    echo "发生错误: " . $e->getMessage() . "\n";
    echo "错误类型: " . get_class($e) . "\n";
}
