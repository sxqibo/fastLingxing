<?php
/**
 * 查询亚马逊市场列表示例
 * 
 * 接口说明：
 * - API路径: /erp/sc/data/seller/allMarketplace
 * - 请求方式: GET
 * - 令牌桶容量: 1
 * - 唯一键: marketplace_id或mid
 * 
 * 主要功能：
 * 1. 获取亚马逊所有市场列表数据
 * 2. 显示站点ID与亚马逊市场ID的映射关系
 * 3. 按地区分组显示市场信息
 * 
 * 返回数据包含：
 * - 站点ID (mid)
 * - 地区 (region)
 * - 亚马逊地区 (aws_region)
 * - 商城所在国家名称 (country)
 * - 亚马逊国家code (code)
 * - 亚马逊市场ID (marketplace_id)
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

    // 查询亚马逊市场列表
    echo "正在查询亚马逊市场列表...\n";
    
    // 发起GET请求
    $response = $client->makeRequest('/erp/sc/data/seller/allMarketplace', 'GET');
    
    // 输出响应结果
    displayResponseInfo($response);
    
    if ($response['code'] == 0) {
        echo "\n=== 亚马逊市场列表查询结果 ===\n";
        echo "请求状态: 成功\n";
        echo "请求ID: " . $response['request_id'] . "\n";
        echo "响应时间: " . $response['response_time'] . "\n";
        echo "市场总数: " . count($response['data']) . "\n";
        echo "================================\n\n";
        
        if (!empty($response['data'])) {
            // 按地区分组
            $marketsByRegion = [];
            foreach ($response['data'] as $market) {
                $region = $market['region'];
                if (!isset($marketsByRegion[$region])) {
                    $marketsByRegion[$region] = [];
                }
                $marketsByRegion[$region][] = $market;
            }
            
            // 地区名称映射
            $regionNames = [
                'NA' => '北美地区',
                'EU' => '欧洲地区', 
                'FE' => '远东地区',
                'IN' => '印度地区',
                'JP' => '日本地区',
                'CN' => '中国地区',
                'AU' => '澳洲地区',
                'AE' => '阿联酋地区',
                'SG' => '新加坡地区',
                'SA' => '沙特地区',
                'TR' => '土耳其地区'
            ];
            
            // 按地区显示市场信息
            foreach ($marketsByRegion as $region => $markets) {
                $regionName = isset($regionNames[$region]) ? $regionNames[$region] : $region;
                echo "=== {$regionName} ({$region}) ===\n";
                echo "市场数量: " . count($markets) . "\n\n";
                
                foreach ($markets as $index => $market) {
                    echo "市场 " . ($index + 1) . ":\n";
                    echo "  - 站点ID: " . $market['mid'] . " 【站点唯一标识】\n";
                    echo "  - 地区: " . $market['region'] . " 【地区代码】\n";
                    echo "  - 亚马逊地区: " . $market['aws_region'] . " 【亚马逊AWS地区】\n";
                    echo "  - 国家名称: " . $market['country'] . " 【商城所在国家】\n";
                    echo "  - 国家代码: " . $market['code'] . " 【亚马逊国家代码】\n";
                    echo "  - 市场ID: " . $market['marketplace_id'] . " 【亚马逊市场唯一标识】\n";
                    echo "\n";
                }
                echo str_repeat("-", 50) . "\n\n";
            }
            
            // 显示统计信息
            echo "=== 统计信息 ===\n";
            foreach ($regionNames as $code => $name) {
                if (isset($marketsByRegion[$code])) {
                    echo "{$name}: " . count($marketsByRegion[$code]) . " 个市场\n";
                }
            }
            
            // 显示所有市场ID映射关系
            echo "\n=== 站点ID与市场ID映射关系 ===\n";
            echo "格式: 站点ID => 国家名称 (市场ID)\n";
            echo "----------------------------------------\n";
            foreach ($response['data'] as $market) {
                echo sprintf("%-3d => %s (%s)\n", 
                    $market['mid'], 
                    $market['country'], 
                    $market['marketplace_id']
                );
            }
            
            echo "\n共查询到 " . count($response['data']) . " 个亚马逊市场\n";
        } else {
            echo "暂无市场数据\n";
        }
    } else {
        displayErrorInfo($response);
    }

} catch (Exception $e) {
    echo "发生错误: " . $e->getMessage() . "\n";
    echo "错误类型: " . get_class($e) . "\n";
}