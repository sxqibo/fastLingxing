<?php

/**
 * 查询FBA库存列表-v2 示例
 * 对应系统【仓库】>【FBA库存明细】，数量维度展示
 * API: POST /basicOpen/openapi/storage/fbaWarehouseDetail
 */

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config.php';

try {
    // 初始化客户端
    $client = createOpenAPIClient();

    // 生成 AccessToken
    echo "正在生成 AccessToken...\n";
    $accessTokenDto = $client->generateAccessToken();
    displayAccessTokenInfo($accessTokenDto);

    // 查询 FBA 库存明细（全量分页汇总）
    echo "正在查询 FBA 库存明细（全量，不按分页）...\n";

    $pageSize = 200;   // 每页条数，接口上限 200
    $offset = 0;
    $totalFbaFulfillable = 0;   // FBA可售 汇总
    $totalReservedTransfers = 0; // 待调仓 汇总
    $totalReservedProcessing = 0; // 调仓中 汇总
    $totalRecords = 0;           // 已处理条数
    $apiTotal = null;            // 接口返回的总条数

    do {
        $params = [
            'offset' => $offset,
            'length' => $pageSize,
            'fulfillment_channel_type' => 'FBA',
            'query_fba_storage_quantity_list' => true,
        ];

        $response = $client->makeRequest('/basicOpen/openapi/storage/fbaWarehouseDetail', 'POST', $params);

        if ($response['code'] != 0) {
            displayErrorInfo($response);
            break;
        }

        if ($apiTotal === null) {
            displayResponseInfo($response);
            $apiTotal = (int)($response['total'] ?? 0);
            echo "接口总条数: {$apiTotal}\n\n";
        }

        $list = $response['data'] ?? [];
        if (empty($list)) {
            break;
        }

        foreach ($list as $row) {
            $totalFbaFulfillable += (int)($row['afn_fulfillable_quantity'] ?? 0);
            $totalReservedTransfers += (int)($row['reserved_fc_transfers'] ?? 0);
            $totalReservedProcessing += (int)($row['reserved_fc_processing'] ?? 0);
        }

        $totalRecords += count($list);
        echo "已处理: {$totalRecords} / {$apiTotal} 条\n";

        if (count($list) < $pageSize || $totalRecords >= $apiTotal) {
            break;
        }
        $offset += $pageSize;

    } while (true);

    // 输出全量汇总结果
    echo "\n========== 全量汇总 ==========\n";
    echo "FBA可售：{$totalFbaFulfillable}\n";
    echo "待调仓：{$totalReservedTransfers}\n";
    echo "调仓中：{$totalReservedProcessing}\n";
    echo "（共 {$totalRecords} 条明细参与汇总）\n";
} catch (Exception $e) {
    echo "发生错误: " . $e->getMessage() . "\n";
    echo "错误类型: " . get_class($e) . "\n";
}
