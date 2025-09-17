<?php
/**
 * 亚马逊订单查询示例
 * 
 * 接口说明：
 * - API路径: /erp/sc/data/mws/orders
 * - 请求方式: POST
 * - 令牌桶容量: 1
 * 
 * 主要功能：
 * 1. 查询亚马逊店铺的订单数据
 * 2. 支持多种筛选条件（订单状态、配送方式、时间范围等）
 * 3. 支持分页查询
 * 4. 返回详细的订单信息、商品信息、时间信息等
 * 
 * 返回数据包含：
 * - 基本信息：订单号、店铺ID、订单状态、订单金额等
 * - 配送信息：配送方式、物流运单号、邮编等
 * - 商品信息：ASIN、SKU、数量、品名等
 * - 时间信息：订购时间、发货时间、更新时间等
 * - 其他信息：退款状态、推广订单、换货订单等
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

    // 查询亚马逊订单
    echo "正在查询亚马逊订单...\n";
    
    // 请求参数配置
    $params = [
        'sid' => 6608,  // 杜国平德国店铺ID
        'start_date' => '2025-09-16',  // 查询开始时间
        'end_date' => '2025-09-15',    // 查询结束时间
        'date_type' => 1,              // 查询日期类型：1-订购时间
        // 'order_status' => ['Shipped', 'Unshipped', 'PartiallyShipped'], // 订单状态（可选）
        'sort_desc_by_date_type' => 1, // 按订购时间降序排序
        // 'fulfillment_channel' => 2,    // 配送方式：2-自发货-MFN（可选）
        'offset' => 0,                 // 分页偏移量
        'length' => 100                // 分页长度（先查询少量数据测试）
    ];
    
    // 发起POST请求
    $response = $client->makeRequest('/erp/sc/data/mws/orders', 'POST', $params);
    
    // 输出响应结果
    displayResponseInfo($response);
    
    if ($response['code'] == 0) {
        echo "\n=== 亚马逊订单查询结果 ===\n";
        echo "请求状态: 成功\n";
        echo "请求ID: " . $response['request_id'] . "\n";
        echo "响应时间: " . $response['response_time'] . "\n";
        echo "订单总数: " . $response['total'] . "\n";
        echo "当前页订单数: " . count($response['data']) . "\n";
        echo "================================\n\n";
        
        if (!empty($response['data'])) {
            foreach ($response['data'] as $index => $order) {
                echo "订单 " . ($index + 1) . ":\n";
                echo "  === 基本信息 ===\n";
                echo "  - 店铺ID: " . $order['sid'] . " 【店铺唯一标识】\n";
                echo "  - 店铺名称: " . $order['seller_name'] . " 【店铺名称】\n";
                echo "  - 亚马逊订单号: " . $order['amazon_order_id'] . " 【亚马逊订单唯一标识】\n";
                echo "  - 订单状态: " . $order['order_status'] . " 【订单当前状态】\n";
                echo "  - 订单金额: " . $order['order_total_amount'] . " " . $order['order_total_currency_code'] . " 【订单总金额】\n";
                echo "  - 配送方式: " . $order['fulfillment_channel'] . " 【AFN-亚马逊配送，MFN-自发货】\n";
                echo "  - 邮编: " . ($order['postal_code'] ?: '无') . " 【收货地址邮编】\n";
                echo "  - 物流运单号: " . ($order['tracking_number'] ?: '无') . " 【物流跟踪号】\n";
                echo "  - 销售渠道: " . $order['sales_channel'] . " 【销售平台】\n";
                
                echo "  === 订单状态信息 ===\n";
                $isReturnMap = [
                    0 => '未退款',
                    1 => '退款中',
                    2 => '退款完成'
                ];
                echo "  - 退款状态: " . $isReturnMap[$order['is_return']] . " (" . $order['is_return'] . ")\n";
                echo "  - 是否多渠道订单: " . ($order['is_mcf_order'] ? '是' : '否') . "\n";
                echo "  - 是否推广订单: " . ($order['is_assessed'] ? '是' : '否') . "\n";
                echo "  - 是否换货订单: " . ($order['is_replaced_order'] ? '是' : '否') . "\n";
                echo "  - 是否已换货订单: " . ($order['is_replacement_order'] ? '是' : '否') . "\n";
                echo "  - 是否退货订单: " . ($order['is_return_order'] ? '是' : '否') . "\n";
                echo "  - 退款金额: " . $order['refund_amount'] . " 【退款金额】\n";
                
                echo "  === 时间信息 ===\n";
                echo "  - 订购时间(站点): " . $order['purchase_date_local'] . " 【订购时间，站点时间】\n";
                echo "  - 订购时间(UTC): " . $order['purchase_date_utc'] . " 【订购时间，UTC时间】\n";
                echo "  - 发货时间: " . ($order['shipment_date_local'] ?: '未发货') . " 【发货时间，站点时间】\n";
                echo "  - 订单更新时间: " . $order['last_update_date'] . " 【订单最后更新时间，站点时间】\n";
                echo "  - 付款时间: " . ($order['posted_date_utc'] ?: '未付款') . " 【付款时间，UTC时间】\n";
                echo "  - 发货时限: " . ($order['earliest_ship_date_utc'] ?: '无') . " 【发货时限，UTC时间】\n";
                echo "  - 订单修改时间: " . $order['gmt_modified'] . " 【订单修改时间，北京时间】\n";
                
                echo "  === 商品信息 ===\n";
                if (!empty($order['item_list'])) {
                    foreach ($order['item_list'] as $itemIndex => $item) {
                        echo "  商品 " . ($itemIndex + 1) . ":\n";
                        echo "    - ASIN: " . $item['asin'] . " 【亚马逊标准识别号】\n";
                        echo "    - 数量: " . $item['quantity_ordered'] . " 【订购数量】\n";
                        echo "    - MSKU: " . $item['seller_sku'] . " 【卖家SKU】\n";
                        echo "    - 本地SKU: " . ($item['local_sku'] ?: '无') . " 【本地产品SKU】\n";
                        echo "    - 品名: " . ($item['local_name'] ?: '无') . " 【本地产品名称】\n";
                    }
                } else {
                    echo "  无商品信息\n";
                }
                
                echo "\n";
            }
            echo "共查询到 " . count($response['data']) . " 个订单\n";
        } else {
            echo "暂无订单数据\n";
        }
    } else {
        displayErrorInfo($response);
    }

} catch (Exception $e) {
    echo "发生错误: " . $e->getMessage() . "\n";
    echo "错误类型: " . get_class($e) . "\n";
}