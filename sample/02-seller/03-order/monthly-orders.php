<?php
/**
 * 查询一个月订单数据
 * 专门用于查询杜国平德国店铺最近一个月的订单
 */

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config.php';

try {
    // 初始化客户端
    $client = createOpenAPIClient();

    // 生成AccessToken
    echo "=== 查询一个月订单数据 ===\n\n";
    echo "正在生成AccessToken...\n";
    $accessTokenDto = $client->generateAccessToken();
    displayAccessTokenInfo($accessTokenDto);

    // 计算时间范围（最近一个月）
    $endDate = date('Y-m-d'); // 今天
    $startDate = date('Y-m-d', strtotime('-1 month')); // 一个月前
    
    echo "\n查询时间范围: {$startDate} 到 {$endDate}\n";
    echo "店铺ID: 6608 (杜国平德国店铺)\n\n";

    // 查询亚马逊订单
    echo "正在查询订单...\n";
    
    $params = [
        'sid' => 6608,  // 杜国平德国店铺ID
        'start_date' => $startDate,
        'end_date' => $endDate,
        'date_type' => 1,              // 查询日期类型：1-订购时间
        'sort_desc_by_date_type' => 1, // 按订购时间降序排序
        'offset' => 0,                 // 分页偏移量
        'length' => 5000              // 最大查询5000条记录
    ];
    
    // 发起POST请求
    $response = $client->makeRequest('/erp/sc/data/mws/orders', 'POST', $params);
    
    // 输出响应结果
    displayResponseInfo($response);
    
    if ($response['code'] == 0) {
        echo "\n=== 查询成功 ===\n";
        echo "订单总数: " . $response['total'] . "\n";
        echo "当前页订单数: " . count($response['data']) . "\n";
        
        if (!empty($response['data'])) {
            echo "\n=== 订单详情 ===\n";
            
            // 统计信息
            $statusCount = [];
            $channelCount = [];
            $totalAmount = 0;
            $currency = '';
            
            foreach ($response['data'] as $index => $order) {
                // 统计订单状态
                $status = $order['order_status'];
                $statusCount[$status] = ($statusCount[$status] ?? 0) + 1;
                
                // 统计配送方式
                $channel = $order['fulfillment_channel'];
                $channelCount[$channel] = ($channelCount[$channel] ?? 0) + 1;
                
                // 累计订单金额
                $totalAmount += floatval($order['order_total_amount']);
                $currency = $order['order_total_currency_code'];
                
                // 显示订单信息
                echo "订单 " . ($index + 1) . ":\n";
                echo "  - 订单号: " . $order['amazon_order_id'] . "\n";
                echo "  - 订单状态: " . $order['order_status'] . "\n";
                echo "  - 订单金额: " . $order['order_total_amount'] . " " . $order['order_total_currency_code'] . "\n";
                echo "  - 配送方式: " . $order['fulfillment_channel'] . "\n";
                echo "  - 订购时间: " . $order['purchase_date_local'] . "\n";
                echo "  - 发货时间: " . ($order['shipment_date_local'] ?: '未发货') . "\n";
                
                // 显示商品信息
                if (!empty($order['item_list'])) {
                    echo "  - 商品信息:\n";
                    foreach ($order['item_list'] as $itemIndex => $item) {
                        echo "    " . ($itemIndex + 1) . ". " . ($item['local_name'] ?: $item['asin']) . 
                             " (数量: " . $item['quantity_ordered'] . ", SKU: " . $item['seller_sku'] . ")\n";
                    }
                }
                echo "\n";
            }
            
            // 显示统计信息
            echo "=== 统计信息 ===\n";
            echo "订单状态分布:\n";
            foreach ($statusCount as $status => $count) {
                echo "  - {$status}: {$count} 个\n";
            }
            
            echo "\n配送方式分布:\n";
            foreach ($channelCount as $channel => $count) {
                $channelName = $channel == 'AFN' ? '亚马逊配送' : '自发货';
                echo "  - {$channelName} ({$channel}): {$count} 个\n";
            }
            
            echo "\n总订单金额: " . number_format($totalAmount, 2) . " " . $currency . "\n";
            echo "平均订单金额: " . number_format($totalAmount / count($response['data']), 2) . " " . $currency . "\n";
            
        } else {
            echo "\n该时间段内暂无订单数据\n";
        }
    } else {
        echo "\n=== 查询失败 ===\n";
        displayErrorInfo($response);
    }

} catch (Exception $e) {
    echo "\n=== 发生错误 ===\n";
    echo "错误信息: " . $e->getMessage() . "\n";
    echo "错误类型: " . get_class($e) . "\n";
    
    if ($e instanceof Sxqibo\FastLingxing\Exception\RequestException) {
        echo "\n可能的解决方案:\n";
        echo "1. 检查网络连接\n";
        echo "2. 确认API服务是否正常\n";
        echo "3. 检查AccessToken是否有效\n";
    }
}
