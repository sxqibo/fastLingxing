<?php
/**
 * 查询多平台店铺信息示例
 * 
 * 接口说明：
 * - API路径: /pb/mp/shop/v2/getSellerList
 * - 请求方式: POST
 * - 令牌桶容量: 10
 * - 支持查询多平台店铺基础信息，其中store_id为多平台店铺唯一标识
 * 
 * 主要功能：
 * 1. 查询多平台店铺基础信息
 * 2. 支持按平台筛选（Amazon、Shopify、eBay、Wish、AliExpress、Shopee、Lazada、Walmart、Temu等）
 * 3. 支持按同步状态和授权状态筛选
 * 4. 返回店铺详细信息
 * 
 * 返回数据包含：
 * - 店铺基本信息：店铺ID、店铺名称、平台信息等
 * - 店铺状态：同步状态、授权状态
 * - 其他信息：币种、sid（对应亚马逊店铺接口的sid）等
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

try {
    // 初始化客户端
    $client = createOpenAPIClient();

    // 生成AccessToken
    echo "正在生成AccessToken...\n";
    $accessTokenDto = $client->generateAccessToken();
    displayAccessTokenInfo($accessTokenDto);

    // 查询多平台店铺信息
    echo "正在查询多平台店铺信息...\n";
    
    // 请求参数配置
    // 注意：以下参数根据需要取消注释使用
    $params = [
        // === 分页参数 ===
        'offset' => DEFAULT_OFFSET,    // 分页偏移量，默认0
        'length' => 200,               // 分页长度，上限200，默认200
        
        // === 平台筛选 ===
        // 平台code列表：
        // 10001 AMAZON, 10002 Shopify, 10003 eBay, 10004 Wish, 10005 AliExpress
        // 10006 Shopee, 10007 Lazada, 10008 Walmart, 10009 自定义平台
        // 10010 Wayfair, 10011 TikTok, 10012 MERCADO, 10013 CDISCOUNT
        // 10014 NEWEGG, 10015 RAKUTEN, 10016 SHOPLINE, 10017 TEAPPLIX
        // 10018 SHOPLAZZA, 10019 UEESHOP, 10020 COUPANG, 10021 SHEIN
        // 10022 Temu全托管, 10024 Temu半托管, 10025 OTTO, 10026 OZON
        // 10027 SHEIN全托管, 10028 SHEIN半托管, 10029 AliExpress半托管
        // 10030 AliExpress全托管, 10033 Qoo10, 10034 Mirakl, 10038 line shopping
        'platform_code' => [10024],  // TEMU平台：10022-全托管，10024-半托管
        
        // === 状态筛选 ===
        //'is_sync' => 1,                // 店铺同步状态：1-启用，0-停用
        //'status' => 1                 // 店铺授权状态：1-正常授权，0-授权失败
    ];
    
    // 发起POST请求
    $response = $client->makeRequest('/pb/mp/shop/v2/getSellerList', 'POST', $params);
    
    // 输出响应结果
    displayResponseInfo($response);
    
    if ($response['code'] == 0) {
        echo "\n=== 多平台店铺信息查询结果 ===\n";
        echo "请求状态: 成功\n";
        echo "请求ID: " . $response['request_id'] . "\n";
        echo "响应时间: " . $response['response_time'] . "\n";
        echo "数据总数: " . $response['data']['total'] . "\n";
        echo "当前页数据量: " . count($response['data']['list']) . "\n";
        echo "================================\n\n";
        
        if (!empty($response['data']['list'])) {
            foreach ($response['data']['list'] as $index => $store) {
                echo "店铺 " . ($index + 1) . ":\n";
                
                // 基本信息
                echo "  === 基本信息 ===\n";
                echo "  - 店铺ID (store_id): " . $store['store_id'] . " 【多平台店铺唯一标识】\n";
                echo "  - 店铺名称: " . $store['store_name'] . " 【店铺显示名称】\n";
                echo "  - 平台Code: " . $store['platform_code'] . " 【平台代码】\n";
                echo "  - 平台名称: " . $store['platform_name'] . " 【平台显示名称】\n";
                echo "  - 店铺币种: " . $store['currency'] . " 【店铺使用的货币】\n";
                
                // 关联信息
                if (!empty($store['sid'])) {
                    echo "  - 店铺ID (sid): " . $store['sid'] . " 【对应获取亚马逊店铺接口的sid】\n";
                } else {
                    echo "  - 店铺ID (sid): 无 【非亚马逊店铺或无关联】\n";
                }
                
                // 状态信息
                echo "  === 状态信息 ===\n";
                $syncStatus = $store['is_sync'] == 1 ? '启用' : '停用';
                $authStatus = $store['status'] == 1 ? '正常授权' : '授权失败';
                echo "  - 同步状态: " . $syncStatus . " 【" . $store['is_sync'] . "】\n";
                echo "  - 授权状态: " . $authStatus . " 【" . $store['status'] . "】\n";
                
                echo "\n";
            }
            echo "共查询到 " . count($response['data']['list']) . " 个店铺\n";
        } else {
            echo "暂无店铺数据\n";
        }
    } else {
        displayErrorInfo($response);
    }

} catch (Exception $e) {
    echo "发生错误: " . $e->getMessage() . "\n";
    echo "错误类型: " . get_class($e) . "\n";
}
