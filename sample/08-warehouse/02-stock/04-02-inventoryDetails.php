<?php

/**
 * 查询仓库库存明细，汇总：库存成本、调拨在途，并计算「以上两者总和」换算为万单位
 * API: /erp/sc/routing/data/local_inventory/inventoryDetails
 * @URL: https://apidoc.lingxing.com/#/docs/Warehouse/InventoryDetails
 * 详情请到 领星->仓库->库存明细 核对（库存成本+调拨在途总和）
 */

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config.php';

try {
    $client = createOpenAPIClient();

    echo "正在生成 AccessToken...\n";
    $accessTokenDto = $client->generateAccessToken();
    displayAccessTokenInfo($accessTokenDto);

    echo "正在查询仓库库存明细（汇总 库存成本、调拨在途）...\n";

    $params = [
        'wid'    => SAMPLE_WID,
        'offset' => DEFAULT_OFFSET,
        'length' => MAX_LENGTH,
    ];

    $sumStockCostTotal = 0;
    $sumProductOnway   = 0;
    $offset = 0;
    $total  = 0;

    do {
        $params['offset'] = $offset;
        $response = $client->makeRequest('/erp/sc/routing/data/local_inventory/inventoryDetails', 'POST', $params);

        if ($response['code'] != 0) {
            displayErrorInfo($response);
            exit(1);
        }

        $total = (int)($response['total'] ?? 0);
        $list  = $response['data'] ?? [];

        foreach ($list as $inventory) {
            $sumStockCostTotal += (float)($inventory['stock_cost_total'] ?? 0);
            $sumProductOnway   += (float)($inventory['product_onway'] ?? 0);
        }

        if (count($list) < $params['length'] || $offset + count($list) >= $total) {
            break;
        }
        $offset += count($list);
    } while (true);

    displayResponseInfo($response);

    $sumBoth = $sumStockCostTotal + $sumProductOnway;
    $sumBothInWan = round($sumBoth / 10000, 2);  // 换算成万单位

    echo "\n========== 库存汇总 ==========\n";
    echo "明细条数: {$total}\n";
    echo "库存成本总和: {$sumStockCostTotal}\n";
    echo "调拨在途总和: {$sumProductOnway}\n";
    echo "以上两者总和（库存成本+调拨在途总和）: {$sumBothInWan} 万\n";
    echo "\n详情请到 领星->仓库->库存明细 核对（库存成本+调拨在途总和）\n";
    echo "========================================\n";
} catch (Exception $e) {
    echo "发生错误: " . $e->getMessage() . "\n";
    echo "错误类型: " . get_class($e) . "\n";
}
