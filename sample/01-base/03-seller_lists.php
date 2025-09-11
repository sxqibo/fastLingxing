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

    // 查询亚马逊店铺列表
    echo "正在查询亚马逊店铺列表...\n";
    
    // 发起GET请求（此接口不需要额外参数）
    $response = $client->makeRequest('/erp/sc/data/seller/lists', 'GET');
    
    // 输出响应结果
    displayResponseInfo($response);
    
    if ($response['code'] == 0) {
        echo "亚马逊店铺列表数据:\n";
        if (!empty($response['data'])) {
            foreach ($response['data'] as $index => $seller) {
                echo "店铺 " . ($index + 1) . ":\n";
                echo "  - 店铺ID (sid): " . $seller['sid'] . "\n";
                echo "  - 站点ID (mid): " . $seller['mid'] . "\n";
                echo "  - 店铺名: " . $seller['name'] . "\n";
                echo "  - 亚马逊店铺ID: " . $seller['seller_id'] . "\n";
                echo "  - 店铺账户名称: " . ($seller['account_name'] ?: '无') . "\n";
                echo "  - 店铺账号ID: " . $seller['seller_account_id'] . "\n";
                echo "  - 站点简称: " . $seller['region'] . "\n";
                echo "  - 商城所在国家: " . $seller['country'] . "\n";
                echo "  - 是否授权广告: " . ($seller['has_ads_setting'] ? '是' : '否') . "\n";
                echo "  - 市场ID: " . $seller['marketplace_id'] . "\n";
                
                // 店铺状态说明
                $statusMap = [
                    0 => '停止同步',
                    1 => '正常',
                    2 => '授权异常',
                    3 => '欠费停服'
                ];
                $statusText = $statusMap[$seller['status']] ?? '未知状态';
                echo "  - 店铺状态: " . $statusText . " (" . $seller['status'] . ")\n";
                echo "\n";
            }
            echo "共查询到 " . count($response['data']) . " 个亚马逊店铺\n";
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
