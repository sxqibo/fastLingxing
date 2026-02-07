<?php

/**
 * 查询亚马逊自发货订单列表（FBM）
 * 1. 先调「查询亚马逊店铺列表」获取企业下全部店铺 sid
 * 2. 用全部 sid 查询订单：/erp/sc/routing/order/Order/getOrderList
 * 查询时间：近一个月（订购时间）；统计 4 待审核 的数量、订单号、涉及国家；
 * 并根据返回的 wid/warehouse_name 统计「未分配仓库」数量；「商品未配对」需接口返回对应字段方可统计（当前文档未列出）。
 */

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config.php';

$statusMap = [
    '4' => '待审核',
];

try {
    $client = createOpenAPIClient();

    echo "正在生成 AccessToken...\n";
    $accessTokenDto = $client->generateAccessToken();
    displayAccessTokenInfo($accessTokenDto);

    // 查询亚马逊店铺列表，获取全部店铺 sid（与界面「所有店铺」一致）
    echo "正在查询亚马逊店铺列表（全部已授权店铺）...\n";
    $sellerResponse = $client->makeRequest('/erp/sc/data/seller/lists', 'GET');
    if ($sellerResponse['code'] != 0 || empty($sellerResponse['data'])) {
        $sid = SAMPLE_SID;
        echo "  店铺列表获取失败或为空，使用 .env 配置 SAMPLE_SID: {$sid}\n\n";
    } else {
        $sids = array_column($sellerResponse['data'], 'sid');
        $sid = implode(',', $sids);
        echo "  共 " . count($sids) . " 个店铺，sid: {$sid}\n\n";
    }

    $pageSize = defined('MAX_LENGTH') && MAX_LENGTH > 0 ? min(MAX_LENGTH, 200) : 100;

    // 近一个月（订购时间），与界面「订购时间」筛选一致
    $startTime = date('Y-m-d', strtotime('-1 month')) . ' 00:00:00';
    $endTime = date('Y-m-d') . ' 23:59:59';
    echo "查询时间范围（订购时间）：{$startTime} ~ {$endTime}\n\n";

    $results = [];
    foreach (['4'] as $orderStatus) {
        echo "正在查询状态：{$statusMap[$orderStatus]}（{$orderStatus}）...\n";

        $allOrders = [];
        $total = 0;
        $page = 1;
        do {
            $params = [
                'sid'          => $sid,
                'order_status' => $orderStatus,
                'page'         => $page,
                'length'       => $pageSize,
                'start_time'   => $startTime,
                'end_time'     => $endTime,
            ];
            $response = $client->makeRequest('/erp/sc/routing/order/Order/getOrderList', 'POST', $params);

            if ($response['code'] != 0) {
                echo "  请求失败: " . ($response['message'] ?? $response['msg'] ?? json_encode($response)) . "\n";
                $results[$orderStatus] = [
                    'count' => 0, 'order_numbers' => [], 'countries' => [],
                    'unassigned_warehouse_count' => 0, 'unassigned_warehouse_numbers' => [],
                    'product_unmatched_count' => 0, 'product_unmatched_numbers' => [],
                ];
                break;
            }

            $total = (int)($response['total'] ?? 0);
            $list = $response['data'] ?? [];
            foreach ($list as $row) {
                $wid = $row['wid'] ?? null;
                $warehouseName = $row['warehouse_name'] ?? '';
                $allOrders[] = [
                    'order_number'   => $row['order_number'] ?? '',
                    'country_code'   => $row['country_code'] ?? '',
                    'wid'            => $wid,
                    'warehouse_name' => $warehouseName,
                    'raw'            => $row, // 保留原始行，便于后续若接口有「商品未配对」等字段可扩展
                ];
            }
            if (count($list) < $pageSize || count($allOrders) >= $total) {
                break;
            }
            $page++;
        } while (true);

        // 未分配仓库：wid 为空/0/null 或 warehouse_name 为空
        $unassignedWarehouseOrders = array_filter($allOrders, function ($o) {
            $wid = $o['wid'];
            $name = $o['warehouse_name'] ?? '';
            return ($wid === null || $wid === '' || $wid === 0) && (string)$name === '';
        });
        $unassignedWarehouseNumbers = array_values(array_unique(array_filter(array_column($unassignedWarehouseOrders, 'order_number'))));

        // 若接口返回了商品未配对相关字段（如 product_unmatched、item_match_status 等），可在此根据 raw 统计
        $productUnmatchedOrders = [];
        $productUnmatchedNumbers = [];
        foreach ($allOrders as $o) {
            $r = $o['raw'] ?? [];
            if (isset($r['product_unmatched']) && $r['product_unmatched']) {
                $productUnmatchedOrders[] = $o;
            } elseif (isset($r['is_product_matched']) && !$r['is_product_matched']) {
                $productUnmatchedOrders[] = $o;
            }
        }
        if (!empty($productUnmatchedOrders)) {
            $productUnmatchedNumbers = array_values(array_unique(array_filter(array_column($productUnmatchedOrders, 'order_number'))));
        }

        $results[$orderStatus] = [
            'count'                        => $total,
            'order_numbers'                => array_values(array_unique(array_filter(array_column($allOrders, 'order_number')))),
            'countries'                    => array_values(array_unique(array_filter(array_column($allOrders, 'country_code')))),
            'unassigned_warehouse_count'   => count($unassignedWarehouseNumbers),
            'unassigned_warehouse_numbers' => $unassignedWarehouseNumbers,
            'product_unmatched_count'      => count($productUnmatchedNumbers),
            'product_unmatched_numbers'    => $productUnmatchedNumbers,
        ];
    }

    displayResponseInfo($response ?? ['code' => 0, 'message' => 'OK']);

    echo "\n========== 自发货订单统计（4 待审核）==========\n\n";
    foreach (['4'] as $orderStatus) {
        $r = $results[$orderStatus] ?? [
            'count' => 0, 'order_numbers' => [], 'countries' => [],
            'unassigned_warehouse_count' => 0, 'unassigned_warehouse_numbers' => [],
            'product_unmatched_count' => 0, 'product_unmatched_numbers' => [],
        ];
        $name = $statusMap[$orderStatus];
        $count = $r['count'];
        $orderNumbers = $r['order_numbers'];
        $countries = $r['countries'];
        $unassignedWarehouseCount = $r['unassigned_warehouse_count'] ?? 0;
        $unassignedWarehouseNumbers = $r['unassigned_warehouse_numbers'] ?? [];
        $productUnmatchedCount = $r['product_unmatched_count'] ?? 0;
        $productUnmatchedNumbers = $r['product_unmatched_numbers'] ?? [];

        echo "【{$name}】共 {$count} 个\n";
        echo "  订单号：" . (count($orderNumbers) > 0 ? implode('、', $orderNumbers) : '无') . "\n";
        echo "  涉及国家：" . (count($countries) > 0 ? implode('、', $countries) : '无') . "\n";
        echo "  未分配仓库：{$unassignedWarehouseCount} 个" . (count($unassignedWarehouseNumbers) > 0 ? '（订单号：' . implode('、', $unassignedWarehouseNumbers) . '）' : '') . "\n";
        echo "  商品未配对：" . ($productUnmatchedCount > 0 ? "{$productUnmatchedCount} 个" . (count($productUnmatchedNumbers) > 0 ? '（订单号：' . implode('、', $productUnmatchedNumbers) . '）' : '') : '接口未返回对应字段，无法统计') . "\n\n";
    }
    echo "========================================\n";
} catch (Exception $e) {
    echo "发生错误: " . $e->getMessage() . "\n";
    echo "错误类型: " . get_class($e) . "\n";
}
