<?php
/**
 * 海外仓SKU配对列表查询示例
 * 
 * 接口说明：
 * - API路径: /basicOpen/overseaWarehouseSetting/matchList
 * - 请求方式: POST
 * - 令牌桶容量: 1
 * 
 * 主要功能：
 * 1. 查询海外仓SKU配对列表
 * 2. 支持按服务商、仓库进行筛选
 * 3. 支持分页查询
 * 4. 支持按配对状态和关键词搜索
 * 
 * 返回数据包含：
 * - 配对信息：本地SKU、本地品名、第三方仓SKU、第三方仓品名等
 * - 仓库信息：仓库名称、仓库代码等
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

    // 查询海外仓SKU配对列表
    echo "正在查询海外仓SKU配对列表...\n";
    
    // 请求参数配置
    $params = [
        'wpId' => 18568,      // 三方服务商id，必填
        // 'twIds' => '123,124',  // 三方仓id，多个之间用逗号隔开，选填
        // 'offset' => DEFAULT_OFFSET,  // 分页偏移量，默认0，选填
        // 'length' => DEFAULT_LENGTH,  // 分页大小，默认20，上限200，选填
        // 'isMatched' => 1,           // 是否配对，0否，1是，选填
        'keyword' => '1代猫砂盆',            // 关键词，搜索sku / 品名 / 第三方产品名 / 产品编码，选填
    ];
    
    echo "查询参数: " . json_encode($params, JSON_UNESCAPED_UNICODE) . "\n\n";
    
    // 发起POST请求
    $response = $client->makeRequest('/basicOpen/overseaWarehouseSetting/matchList', 'POST', $params);
    
    // 输出响应结果
    displayResponseInfo($response);
    
    if ($response['code'] == 0) {
        echo "\n=== 海外仓SKU配对列表查询结果 ===\n";
        echo "请求状态: 成功\n";
        echo "请求ID: " . $response['request_id'] . "\n";
        echo "响应时间: " . $response['response_time'] . "\n";
        echo "数据总数: " . $response['total'] . "\n";
        echo "当前页数据量: " . count($response['data']) . "\n";
        echo "================================\n\n";
        
        if (!empty($response['data'])) {
            foreach ($response['data'] as $index => $matchItem) {
                echo "配对项 " . ($index + 1) . ":\n";
                echo "  === 基本信息 ===\n";
                echo "  - 国家: " . ($matchItem['countryName'] ?: '无') . " 【国家名称】\n";
                echo "  - FNSKU: " . ($matchItem['fnsku'] ?: '无') . " 【FNSKU】\n";
                
                echo "  === 配对状态 ===\n";
                $isMatchedText = $matchItem['isMatched'] ? '已配对' : '未配对';
                echo "  - 是否配对: " . $isMatchedText . " (" . $matchItem['isMatched'] . ") 【配对状态】\n";
                echo "  - 配对状态文本: " . $matchItem['isMatchedText'] . " 【配对状态文本描述】\n";
                echo "  - 配对数量: " . $matchItem['matchNum'] . " 【配对数量】\n";
                echo "  - 配对信息: " . ($matchItem['matchMsg'] ?: '无') . " 【配对相关信息】\n";
                
                echo "  === 本地商品信息 ===\n";
                echo "  - 本地品名: " . $matchItem['localName'] . " 【本地商品品名】\n";
                echo "  - 本地SKU: " . $matchItem['localSku'] . " 【本地商品SKU】\n";
                
                echo "  === 第三方仓商品信息 ===\n";
                echo "  - 第三方仓SKU: " . $matchItem['overseaProductCode'] . " 【第三方仓商品SKU】\n";
                echo "  - 第三方仓品名: " . $matchItem['overseaProductName'] . " 【第三方仓商品品名】\n";
                echo "  - 规格: " . ($matchItem['overseaSpec'] ?: '无') . " 【规格，winit专用】\n";
                echo "  - 三方SKU唯一编码: " . $matchItem['overseaUniqueCode'] . " 【三方SKU唯一编码】\n";
                
                echo "  === 仓库信息 ===\n";
                echo "  - 仓库代码: " . ($matchItem['warehouseCode'] ?: '无') . " 【第三方仓仓库代码】\n";
                echo "  - 仓库名称: " . ($matchItem['warehouseName'] ?: '无') . " 【第三方仓仓库名称】\n";
                echo "  - 本地仓库名称: " . ($matchItem['warehouseNameLocal'] ?: '无') . " 【本地仓库名称】\n";
                
                echo "  === ID信息 ===\n";
                echo "  - 产品ID: " . $matchItem['productId'] . " 【产品ID】\n";
                echo "  - 店铺ID: " . $matchItem['sellerId'] . " 【店铺ID】\n";
                echo "  - 店铺名称: " . $matchItem['sellerName'] . " 【店铺名称】\n";
                echo "  - 同店铺ID: " . $matchItem['sid'] . " 【同sellerId】\n";
                echo "  - 三方仓ID: " . $matchItem['twId'] . " 【三方仓ID】\n";
                echo "  - 三方商品ID: " . $matchItem['twpId'] . " 【三方商品ID】\n";
                echo "  - 仓库ID: " . $matchItem['wid'] . " 【仓库ID】\n";
                echo "  - 配对ID: " . $matchItem['wpmId'] . " 【配对ID】\n";
                
                echo "\n";
            }
            echo "共查询到 " . count($response['data']) . " 个配对项\n";
        } else {
            echo "暂无配对数据\n";
        }
    } else {
        displayErrorInfo($response);
    }

} catch (Exception $e) {
    echo "发生错误: " . $e->getMessage() . "\n";
    echo "错误类型: " . get_class($e) . "\n";
}