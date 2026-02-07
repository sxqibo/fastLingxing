<?php

/**
 * 查询销售出库单列表
 * 接口默认返回一个月内审核出库的数据，超过一个月请加上 start_date 和 end_date 参数
 * 本示例查询物流状态为 4-下单异常 的出库单
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

    // 查询销售出库单列表（物流状态：4 下单异常）
    echo "正在查询销售出库单列表（物流状态：下单异常）...\n";

    // 时间范围：默认最近1个月，超过1个月需传入时间参数
    $endDate = date('Y-m-d');
    $startDate = date('Y-m-d', strtotime('-1 month'));

    // 请求参数
    $params = [
        'page' => 1,
        'page_size' => 200,             // 分页长度，上限200
        'status_arr' => [1,2,3],        // 状态：1 物流下单,2 发货中, 3 已发货
        'logistics_status_arr' => [4],  // 物流状态：4 下单异常
        'time_type' => 'create_at',     // 创建时间
        'start_date' => $startDate,
        'end_date' => $endDate,
    ];

    // 可选：指定店铺ID
    // $params['sid_arr'] = [26];

    echo "查询参数: " . json_encode($params, JSON_UNESCAPED_UNICODE) . "\n\n";

    // 发起POST请求
    $response = $client->makeRequest('/erp/sc/routing/wms/order/wmsOrderList', 'POST', $params);

    // 输出响应结果
    displayResponseInfo($response);

    if ($response['code'] == 0) {
        echo "销售出库单数据:\n";
        echo "总数: " . ($response['total'] ?? 0) . "\n\n";

        if (!empty($response['data'])) {
            foreach ($response['data'] as $index => $order) {
                echo "出库单 " . ($index + 1) . ":\n";
                echo "  - 出库单号: " . ($order['wo_number'] ?? '') . "\n";
                echo "  - 系统单号: " . ($order['order_number'] ?? '') . "\n";
                echo "  - 平台单号: " . json_encode($order['platform_order_no'] ?? [], JSON_UNESCAPED_UNICODE) . "\n";
                echo "  - 店铺: " . ($order['seller_name'] ?? '') . "\n";
                echo "  - 仓库: " . ($order['warehouse_name'] ?? '') . "\n";
                echo "  - 物流状态: " . ($order['logistics_status_name'] ?? '') . "\n";
                echo "  - 物流消息: " . ($order['logistics_message'] ?? '') . "\n";
                echo "  - 创建时间: " . ($order['create_at'] ?? '') . "\n";
                echo "  - 收件人: " . ($order['consignee'] ?? '') . "\n";
                echo "  - 收货地址: " . ($order['consignee_full_address'] ?? '') . "\n";
                echo "\n";
            }
            echo "本页共 " . count($response['data']) . " 条出库单\n";
        } else {
            echo "暂无物流状态为「下单异常」的出库单数据\n";
        }
    } else {
        displayErrorInfo($response);
    }
} catch (Exception $e) {
    echo "发生错误: " . $e->getMessage() . "\n";
    echo "错误类型: " . get_class($e) . "\n";
}
