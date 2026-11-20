<?php

/**
 * 查询评论管理 - Review(新) 示例
 *
 * API: POST /basicOpen/openapi/service/v3/data/mws/reviews
 * 令牌桶容量: 1
 *
 * 必填参数: date_field, start_date, end_date
 * sids 可选，对应查询亚马逊店铺列表接口的 sid；数量多时分批请求后合并结果
 * 示例仅查询：最近一个月、1 星和 2 星、状态为待处理和处理中
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

try {
    $client = createOpenAPIClient();

    echo "正在生成 AccessToken...\n";
    $accessTokenDto = $client->generateAccessToken();
    displayAccessTokenInfo($accessTokenDto);

    // 查询亚马逊店铺列表，获取全部 sid
    $sidList = [];
    echo "正在查询亚马逊店铺列表（获取全部 sid）...\n";
    $sellerResponse = $client->makeRequest('/erp/sc/data/seller/lists', 'GET');
    if (($sellerResponse['code'] ?? -1) === 0 && !empty($sellerResponse['data'])) {
        foreach ($sellerResponse['data'] as $row) {
            $sid = (int)($row['sid'] ?? 0);
            if ($sid > 0) {
                $sidList[] = $sid;
            }
        }
        $sidList = array_values(array_unique($sidList));
        echo "获取到店铺数量: " . count($sidList) . "\n";
    }
    echo "\n";

    $sidChunkSize = 30; // 每批 sid 数量，避免「参数有误」；若仍 400 可改为 20
    $startDate = date('Y-m-d', strtotime('-30 days'));
    $endDate = date('Y-m-d');
    echo "查询时间范围（最近30天）: {$startDate} 至 {$endDate}\n";
    $allList = [];
    $totalFromApi = 0;
    $chunks = array_chunk($sidList, $sidChunkSize);
    $totalChunks = count($chunks);

    // 按批请求 Review，合并结果
    foreach ($chunks as $chunkIndex => $chunk) {
        $sidsStr = implode(',', $chunk);
        echo "正在查询评论管理 Review 第 " . ($chunkIndex + 1) . "/{$totalChunks} 批（" . count($chunk) . " 个店铺，1/2星、待处理/处理中）...\n";

        $params = [
            'date_field' => 'review_time',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'star' => '1,2',
            'status' => '0,1',   // 0 待处理 1 处理中（不查 2 已完成）
            'sort_field' => 'review_date',
            'sort_type' => 'desc',
            'offset' => 0,
            'length' => min(200, MAX_LENGTH ?: 200),
            'sids' => $sidsStr,
        ];

        $response = $client->makeRequest('/basicOpen/openapi/service/v3/data/mws/reviews', 'POST', $params);

        if (($response['code'] ?? -1) !== 0) {
            echo "本批请求失败: " . ($response['message'] ?? '参数有误') . "\n";
            if (!empty($response['error_details'])) {
                echo "错误详情: " . json_encode($response['error_details'], JSON_UNESCAPED_UNICODE) . "\n";
            }
            continue;
        }

        $list = $response['data'] ?? [];
        $totalFromApi += (int)($response['total'] ?? 0);
        foreach ($list as $item) {
            $allList[] = $item;
        }
    }

    if ($totalChunks > 0) {
        echo "全部批次请求完成。\n";
    }
    if (empty($sidList)) {
        echo "未获取到店铺，无法查询 Review。\n";
        exit(0);
    }

    $list = $allList;
    // 按 review_id 去重：同一评价可能在不同 sid 批次中重复返回，与界面「共22条」一致
    $byReviewId = [];
    foreach ($allList as $item) {
        $rid = $item['review_id'] ?? '';
        if ($rid !== '' && !isset($byReviewId[$rid])) {
            $byReviewId[$rid] = $item;
        }
    }
    $list = array_values($byReviewId);
    $total = count($list);
    if (count($allList) > $total) {
        echo "合并后按 review_id 去重: " . count($allList) . " 条 -> {$total} 条\n";
    }

    echo "总数: {$total}\n";
    echo "当前页条数: " . count($list) . "\n\n";

    if (empty($list)) {
        echo "当前条件下无 Review 数据。\n";
        exit(0);
    }

    $statusMap = [0 => '待处理', 1 => '处理中', 2 => '已完成'];
    foreach ($list as $index => $item) {
        $status = (int)($item['status'] ?? -1);
        $statusText = $statusMap[$status] ?? '未知';

        echo "---- Review " . ($index + 1) . " ----\n";
        echo "Review ID: " . ($item['review_id'] ?? 'N/A') . "\n";
        echo "ASIN: " . ($item['asin'] ?? 'N/A') . "\n";
        echo "星级: " . ($item['last_star'] ?? 'N/A') . " | 标签: " . ($item['last_title'] ?? '') . "\n";
        echo "评价内容: " . ($item['last_content'] ?? '') . "\n";
        echo "买家: " . ($item['author'] ?? 'N/A') . "\n";
        echo "店铺: " . (is_array($item['seller_name'] ?? null) ? implode(', ', $item['seller_name']) : ($item['seller_name'] ?? 'N/A')) . "\n";
        echo "国家: " . ($item['marketplace'] ?? 'N/A') . "\n";
        echo "评价时间: " . ($item['review_date'] ?? 'N/A') . "\n";
        echo "处理状态: {$statusText} ({$status})\n";
        echo "备注: " . ($item['remark'] ?? '') . "\n";

        $itemNames = $item['item_name'] ?? [];
        if (!empty($itemNames) && is_array($itemNames)) {
            echo "标题: " . implode(' | ', $itemNames) . "\n";
        }
        $sellerSku = $item['seller_sku'] ?? [];
        if (!empty($sellerSku) && is_array($sellerSku)) {
            echo "MSKU: " . implode(', ', $sellerSku) . "\n";
        }
        $orderList = $item['amazon_order_list'] ?? [];
        if (!empty($orderList)) {
            echo "订单号: ";
            $orderIds = array_column($orderList, 'amazon_order_id');
            echo implode(', ', $orderIds) . "\n";
        }
        echo "\n";
    }

} catch (Exception $e) {
    echo "发生错误: " . $e->getMessage() . "\n";
    echo "错误类型: " . get_class($e) . "\n";
}
