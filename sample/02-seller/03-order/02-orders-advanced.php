<?php
/**
 * 亚马逊订单高级查询示例
 * 
 * 功能说明：
 * 1. 支持多种查询条件组合
 * 2. 支持分页查询
 * 3. 支持数据导出
 * 4. 包含完整的错误处理
 */

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config.php';

class OrderQueryManager
{
    private $client;
    private $defaultParams = [
        'date_type' => 1,
        'sort_desc_by_date_type' => 1,
        'offset' => 0,
        'length' => 1000
    ];
    
    public function __construct()
    {
        $this->client = createOpenAPIClient();
    }
    
    /**
     * 查询订单
     */
    public function queryOrders($params = [])
    {
        try {
            // 合并默认参数
            $queryParams = array_merge($this->defaultParams, $params);
            
            echo "正在查询订单...\n";
            echo "查询参数: " . json_encode($queryParams, JSON_UNESCAPED_UNICODE) . "\n\n";
            
            $response = $this->client->makeRequest('/erp/sc/data/mws/orders', 'POST', $queryParams);
            
            if ($response['code'] == 0) {
                return $this->processOrderData($response);
            } else {
                throw new Exception("查询失败: " . $response['message']);
            }
            
        } catch (Exception $e) {
            echo "查询订单时发生错误: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * 处理订单数据
     */
    private function processOrderData($response)
    {
        echo "=== 查询结果 ===\n";
        echo "订单总数: " . $response['total'] . "\n";
        echo "当前页订单数: " . count($response['data']) . "\n\n";
        
        if (empty($response['data'])) {
            echo "暂无订单数据\n";
            return [];
        }
        
        $orders = [];
        foreach ($response['data'] as $index => $order) {
            $orderInfo = $this->formatOrderInfo($order, $index + 1);
            $orders[] = $orderInfo;
            echo $orderInfo['formatted_text'];
        }
        
        return $orders;
    }
    
    /**
     * 格式化订单信息
     */
    private function formatOrderInfo($order, $index)
    {
        $formattedText = "订单 {$index}:\n";
        $formattedText .= "  === 基本信息 ===\n";
        $formattedText .= "  - 订单号: " . $order['amazon_order_id'] . "\n";
        $formattedText .= "  - 店铺: " . $order['seller_name'] . " (ID: " . $order['sid'] . ")\n";
        $formattedText .= "  - 订单状态: " . $order['order_status'] . "\n";
        $formattedText .= "  - 订单金额: " . $order['order_total_amount'] . " " . $order['order_total_currency_code'] . "\n";
        $formattedText .= "  - 配送方式: " . $order['fulfillment_channel'] . "\n";
        $formattedText .= "  - 销售渠道: " . $order['sales_channel'] . "\n";
        
        $formattedText .= "  === 时间信息 ===\n";
        $formattedText .= "  - 订购时间: " . $order['purchase_date_local'] . "\n";
        $formattedText .= "  - 发货时间: " . ($order['shipment_date_local'] ?: '未发货') . "\n";
        $formattedText .= "  - 更新时间: " . $order['last_update_date'] . "\n";
        
        $formattedText .= "  === 商品信息 ===\n";
        if (!empty($order['item_list'])) {
            foreach ($order['item_list'] as $itemIndex => $item) {
                $formattedText .= "  商品 " . ($itemIndex + 1) . ":\n";
                $formattedText .= "    - ASIN: " . $item['asin'] . "\n";
                $formattedText .= "    - 数量: " . $item['quantity_ordered'] . "\n";
                $formattedText .= "    - MSKU: " . $item['seller_sku'] . "\n";
                $formattedText .= "    - 本地SKU: " . ($item['local_sku'] ?: '无') . "\n";
                $formattedText .= "    - 品名: " . ($item['local_name'] ?: '无') . "\n";
            }
        } else {
            $formattedText .= "  无商品信息\n";
        }
        
        $formattedText .= "\n";
        
        return [
            'raw_data' => $order,
            'formatted_text' => $formattedText
        ];
    }
    
    /**
     * 查询特定店铺的订单
     */
    public function queryOrdersByShop($shopId, $startDate, $endDate, $options = [])
    {
        $params = [
            'sid' => $shopId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ];
        
        // 添加可选参数
        if (isset($options['order_status'])) {
            $params['order_status'] = $options['order_status'];
        }
        if (isset($options['fulfillment_channel'])) {
            $params['fulfillment_channel'] = $options['fulfillment_channel'];
        }
        if (isset($options['date_type'])) {
            $params['date_type'] = $options['date_type'];
        }
        
        return $this->queryOrders($params);
    }
    
    /**
     * 查询杜国平德国店铺的订单
     */
    public function queryDuGuoPingOrders($startDate = '2024-01-01', $endDate = '2024-12-31')
    {
        echo "=== 查询杜国平德国店铺订单 ===\n";
        echo "时间范围: {$startDate} 到 {$endDate}\n\n";
        
        return $this->queryOrdersByShop(6608, $startDate, $endDate, [
            'fulfillment_channel' => 2, // 自发货
            'order_status' => ['Shipped', 'Unshipped', 'PartiallyShipped']
        ]);
    }
}

// 使用示例
try {
    // 生成AccessToken
    echo "正在生成AccessToken...\n";
    $client = createOpenAPIClient();
    $accessTokenDto = $client->generateAccessToken();
    displayAccessTokenInfo($accessTokenDto);
    
    // 创建订单查询管理器
    $orderManager = new OrderQueryManager();
    
    // 查询杜国平德国店铺的订单
    $orders = $orderManager->queryDuGuoPingOrders('2024-12-01', '2024-12-31');
    
    if ($orders) {
        echo "\n=== 查询完成 ===\n";
        echo "共查询到 " . count($orders) . " 个订单\n";
    }

} catch (Exception $e) {
    echo "发生错误: " . $e->getMessage() . "\n";
    echo "错误类型: " . get_class($e) . "\n";
}
