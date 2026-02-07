<?php
/**
 * 查询TEMU半托管店铺待审核发货订单
 * 
 * 功能说明：
 * 1. 获取TEMU半托管店铺列表
 * 2. 查询每个店铺的待审核发货订单
 * 3. 统计总数量和显示明细
 * 
 * 注意：如果订单查询接口路径不正确，请根据实际API文档修改接口路径
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

    // 第一步：获取TEMU半托管店铺列表
    echo "\n=== 第一步：获取TEMU半托管店铺列表 ===\n";
    $sellerParams = [
        'offset' => 0,
        'length' => 200,
        'platform_code' => [10024],  // TEMU半托管平台code
        'is_sync' => 1,              // 只查询启用的店铺
        'status' => 1                 // 只查询正常授权的店铺
    ];
    
    $sellerResponse = $client->makeRequest('/pb/mp/shop/v2/getSellerList', 'POST', $sellerParams);
    
    if ($sellerResponse['code'] != 0) {
        throw new Exception("获取店铺列表失败: " . $sellerResponse['message']);
    }
    
    $stores = $sellerResponse['data']['list'] ?? [];
    echo "找到 " . count($stores) . " 个TEMU半托管店铺\n\n";
    
    if (empty($stores)) {
        echo "没有找到TEMU半托管店铺，请检查店铺配置\n";
        exit;
    }
    
    // 第二步：查询待审核发货订单
    echo "=== 第二步：查询待审核发货订单 ===\n\n";
    
    // 初始化变量
    $totalOrders = 0;
    $allOrders = [];
    
    // 收集所有店铺ID
    $storeIds = [];
    foreach ($stores as $store) {
        $storeIds[] = $store['store_id'];
    }
    
    echo "查询 " . count($storeIds) . " 个店铺的待审核发货订单...\n\n";
    
    // 构建查询参数（根据接口文档）
    // 订单状态：4 待审核
    // 注意：查询时间跨度不能超过31天
    $endTime = time();  // 当前时间戳（秒）
    $startTime = $endTime - 30 * 24 * 3600;  // 30天前的时间戳（秒）
    
    $orderParams = [
        'offset' => 0,
        'length' => 500,  // 上限500
        'store_id' => $storeIds,  // 店铺ID数组
        'platform_code' => ['10024'],  // TEMU半托管平台code
        'order_status' => 4,  // 4 待审核
        'date_type' => 'update_time',  // 时间类型：更新时间
        'start_time' => $startTime,  // 开始时间，时间戳格式（秒）
        'end_time' => $endTime,  // 结束时间，时间戳格式（秒）
        'include_delete' => false  // 不包含已删除订单
    ];
    
    echo "查询时间范围: " . date('Y-m-d H:i:s', $startTime) . " 至 " . date('Y-m-d H:i:s', $endTime) . "\n";
    echo "查询参数: store_id=" . count($storeIds) . "个店铺, platform_code=10024(TEMU半托管), order_status=4(待审核)\n\n";
    
    try {
        $orderResponse = $client->makeRequest('/pb/mp/order/v2/list', 'POST', $orderParams);
        
        if ($orderResponse['code'] == 0) {
            $orders = $orderResponse['data']['list'] ?? [];
            $totalOrders = (int)($orderResponse['data']['total'] ?? count($orders));
            
            echo "查询成功！\n";
            echo "待审核发货订单总数: " . $totalOrders . "\n";
            echo "当前页订单数: " . count($orders) . "\n\n";
            
            // 按店铺分组统计
            $storeOrderCount = [];
            foreach ($orders as $order) {
                $storeId = $order['store_id'] ?? '未知';
                if (!isset($storeOrderCount[$storeId])) {
                    $storeOrderCount[$storeId] = 0;
                }
                $storeOrderCount[$storeId]++;
            }
            
            if (!empty($storeOrderCount)) {
                echo "各店铺订单数量统计:\n";
                foreach ($storeOrderCount as $storeId => $count) {
                    $storeName = '';
                    foreach ($stores as $store) {
                        if ($store['store_id'] == $storeId) {
                            $storeName = $store['store_name'];
                            break;
                        }
                    }
                    echo "  - " . $storeName . " (store_id: " . $storeId . "): " . $count . " 个订单\n";
                }
                echo "\n";
            }
            
            // 保存订单信息
            foreach ($orders as $order) {
                // 找到对应的店铺名称
                $storeName = '';
                foreach ($stores as $store) {
                    if ($store['store_id'] == $order['store_id']) {
                        $storeName = $store['store_name'];
                        break;
                    }
                }
                $order['store_name'] = $storeName;
                $allOrders[] = $order;
            }
            
            // 如果订单数量超过500，需要分页查询
            if ($totalOrders > 500) {
                echo "注意：订单总数超过500，当前仅显示前500条。如需查询全部，请使用分页查询。\n\n";
            }
        } else {
            echo "查询失败，错误信息: " . ($orderResponse['message'] ?? '未知错误') . "\n";
            if (!empty($orderResponse['error_details'])) {
                echo "错误详情: " . json_encode($orderResponse['error_details'], JSON_UNESCAPED_UNICODE) . "\n";
            }
            throw new Exception("查询失败: " . ($orderResponse['message'] ?? '未知错误'));
        }
    } catch (Exception $e) {
        echo "查询订单时发生错误: " . $e->getMessage() . "\n";
        echo "提示：请检查参数格式是否正确\n";
    }
    
    // 第三步：显示统计结果和明细
    echo "=== 第三步：统计结果 ===\n";
    echo "TEMU半托管店铺总数: " . count($stores) . "\n";
    echo "待审核发货订单总数: " . $totalOrders . "\n";
    echo "================================\n\n";
    
    if ($totalOrders > 0 && !empty($allOrders)) {
        echo "=== 订单明细 ===\n\n";
        
        foreach ($allOrders as $index => $order) {
            echo "订单 " . ($index + 1) . ":\n";
            
            // 基本信息
            echo "  === 基本信息 ===\n";
            echo "  - 店铺名称: " . ($order['store_name'] ?? '未知') . "\n";
            echo "  - 店铺ID: " . ($order['store_id'] ?? '未知') . "\n";
            echo "  - 系统单号: " . ($order['global_order_no'] ?? '未知') . " 【系统本地唯一订单号】\n";
            echo "  - 订单来源: " . ($order['order_from_name'] ?? '未知') . "\n";
            
            // 订单状态
            $statusMap = [
                1 => '同步中',
                2 => '已同步',
                3 => '待付款',
                4 => '待审核',
                5 => '待发货',
                6 => '已发货',
                7 => '已取消/不发货',
                8 => '不显示',
                9 => '平台发货'
            ];
            $status = $order['status'] ?? 0;
            $statusText = $statusMap[$status] ?? '未知状态(' . $status . ')';
            echo "  - 订单状态: " . $statusText . " 【" . $status . "】\n";
            
            // 发货方式
            $deliveryTypeMap = [
                1 => '混合方式',
                2 => '自发货',
                3 => '平台发货'
            ];
            $deliveryType = $order['delivery_type'] ?? 0;
            $deliveryTypeText = $deliveryTypeMap[$deliveryType] ?? '未知';
            echo "  - 发货方式: " . $deliveryTypeText . " 【" . $deliveryType . "】\n";
            
            // 拆分单类型
            $splitTypeMap = [
                1 => '原始单',
                2 => '合并单',
                3 => '拆分单'
            ];
            $splitType = $order['split_type'] ?? 0;
            $splitTypeText = $splitTypeMap[$splitType] ?? '未知';
            echo "  - 拆分单类型: " . $splitTypeText . " 【" . $splitType . "】\n";
            
            // 时间信息
            echo "  === 时间信息 ===\n";
            if (!empty($order['global_purchase_time'])) {
                echo "  - 订购时间: " . date('Y-m-d H:i:s', $order['global_purchase_time']) . " 【" . $order['global_purchase_time'] . "】\n";
            }
            if (!empty($order['global_payment_time'])) {
                echo "  - 付款时间: " . date('Y-m-d H:i:s', $order['global_payment_time']) . " 【" . $order['global_payment_time'] . "】\n";
            }
            if (!empty($order['global_review_time'])) {
                echo "  - 审核时间: " . date('Y-m-d H:i:s', $order['global_review_time']) . " 【" . $order['global_review_time'] . "】\n";
            }
            if (!empty($order['update_time'])) {
                echo "  - 更新时间: " . $order['update_time'] . "\n";
            }
            if (!empty($order['global_latest_ship_time'])) {
                echo "  - 发货时限: " . $order['global_latest_ship_time'] . "\n";
            }
            
            // 平台单信息
            if (!empty($order['platform_info']) && is_array($order['platform_info'])) {
                echo "  === 平台单信息 ===\n";
                foreach ($order['platform_info'] as $platformInfo) {
                    echo "  - 平台订单号: " . ($platformInfo['platform_order_no'] ?? '未知') . "\n";
                    echo "  - 平台订单名称: " . ($platformInfo['platform_order_name'] ?? '未知') . "\n";
                    echo "  - 平台Code: " . ($platformInfo['platform_code'] ?? '未知') . "\n";
                    echo "  - 订单国家: " . ($platformInfo['store_Country_code'] ?? '未知') . "\n";
                    if (!empty($platformInfo['purchase_time'])) {
                        echo "  - 平台订购时间: " . date('Y-m-d H:i:s', $platformInfo['purchase_time']) . "\n";
                    }
                    echo "  - 支付状态: " . ($platformInfo['payment_status'] ?? '未知') . "\n";
                    echo "  - 发货状态: " . ($platformInfo['shipping_status'] ?? '未知') . "\n";
                }
            }
            
            // 商品信息
            if (!empty($order['item_info']) && is_array($order['item_info'])) {
                echo "  === 商品信息 ===\n";
                echo "  - 商品数量: " . count($order['item_info']) . "\n";
                foreach ($order['item_info'] as $itemIndex => $item) {
                    echo "    商品 " . ($itemIndex + 1) . ":\n";
                    
                    // 品名（优先显示 local_product_name，如果没有则显示 title）
                    $productName = $item['local_product_name'] ?? $item['title'] ?? '未知';
                    echo "      - 品名: " . $productName . "\n";
                    
                    // 商品标题（如果有且与品名不同）
                    if (!empty($item['title']) && $item['title'] != $productName) {
                        echo "      - 商品标题: " . $item['title'] . "\n";
                    }
                    
                    echo "      - SKU: " . ($item['local_sku'] ?? '无') . "\n";
                    echo "      - MSKU: " . ($item['msku'] ?? '无') . "\n";
                    echo "      - 平台商品ID: " . ($item['product_no'] ?? '无') . " 【亚马逊返回asin，多平台返回平台商品id】\n";
                    echo "      - 数量: " . ($item['quantity'] ?? '0') . "\n";
                    echo "      - 单价: " . ($item['unit_price_amount'] ?? '0.00') . " " . ($order['amount_currency'] ?? '') . "\n";
                    echo "      - 商品金额: " . ($item['item_price_amount'] ?? '0.00') . " " . ($order['amount_currency'] ?? '') . "\n";
                    
                    // 变体属性
                    if (!empty($item['variant_attr'])) {
                        echo "      - 变体属性: " . $item['variant_attr'] . "\n";
                    }
                    
                    // 平台单号（商品级别的）
                    if (!empty($item['platform_order_no'])) {
                        echo "      - 平台单号: " . $item['platform_order_no'] . "\n";
                    }
                    
                    // 订单明细单号
                    if (!empty($item['order_item_no'])) {
                        echo "      - 订单明细单号: " . $item['order_item_no'] . "\n";
                    }
                    
                    // 备货店铺信息
                    if (!empty($item['stock_deduct_name'])) {
                        echo "      - 备货店铺: " . $item['stock_deduct_name'] . " 【店铺ID: " . ($item['stock_deduct_id'] ?? '无') . "】\n";
                    }
                    
                    // 商品备注
                    if (!empty($item['remark'])) {
                        echo "      - 商品备注: " . $item['remark'] . "\n";
                    }
                }
            }
            
            // 交易信息
            if (!empty($order['transaction_info']) && is_array($order['transaction_info'])) {
                echo "  === 交易信息 ===\n";
                $transaction = $order['transaction_info'][0] ?? [];
                echo "  - 订单总额: " . ($transaction['order_total_amount'] ?? '0.00') . "\n";
                echo "  - 商品金额: " . ($transaction['order_item_amount'] ?? '0.00') . "\n";
                echo "  - 折扣: " . ($transaction['discount_amount'] ?? '0.00') . "\n";
                echo "  - 预估毛利润: " . ($transaction['profit_amount'] ?? '0.00') . "\n";
            }
            
            // 物流信息
            if (!empty($order['logistics_info'])) {
                $logistics = $order['logistics_info'];
                echo "  === 物流信息 ===\n";
                $logisticsStatusMap = [
                    0 => '待物流下单',
                    1 => '物流下单中',
                    2 => '成功',
                    3 => '失败',
                    4 => '已取消'
                ];
                $logisticsStatus = $logistics['status'] ?? 0;
                $logisticsStatusText = $logisticsStatusMap[$logisticsStatus] ?? '未知';
                echo "  - 物流状态: " . $logisticsStatusText . " 【" . $logisticsStatus . "】\n";
                if (!empty($logistics['tracking_no'])) {
                    echo "  - 跟踪号: " . $logistics['tracking_no'] . "\n";
                }
                if (!empty($logistics['logistics_type_name'])) {
                    echo "  - 物流方式: " . $logistics['logistics_type_name'] . "\n";
                }
            }
            
            // 备注
            if (!empty($order['remark'])) {
                echo "  - 客服备注: " . $order['remark'] . "\n";
            }
            
            // 订单标签
            if (!empty($order['order_tag']) && is_array($order['order_tag'])) {
                echo "  === 订单标签 ===\n";
                foreach ($order['order_tag'] as $tag) {
                    echo "  - " . ($tag['tag_name'] ?? '未知') . " 【" . ($tag['tag_type'] ?? '') . "】\n";
                }
            }
            
            echo "\n";
        }
    } else {
        echo "暂无待审核发货订单\n";
    }

} catch (Exception $e) {
    echo "发生错误: " . $e->getMessage() . "\n";
    echo "错误类型: " . get_class($e) . "\n";
    echo "\n提示：\n";
    echo "1. 请检查订单查询接口路径是否正确\n";
    echo "2. 请检查请求参数格式是否正确\n";
    echo "3. 请参考API文档确认正确的接口路径和参数\n";
}
