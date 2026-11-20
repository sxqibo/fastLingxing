<?php

/**
 * 查询补货列表（补货建议汇总）示例
 *
 * API: POST /erp/sc/routing/restocking/analysis/getSummaryList
 *
 * 本示例：查询维度 asin（data_type=1），并按 asin=B0CG62N4FH 筛选
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

try {
    // 初始化客户端
    $client = createOpenAPIClient();

    // 生成 AccessToken
    echo "正在生成 AccessToken...\n";
    $accessTokenDto = $client->generateAccessToken();
    displayAccessTokenInfo($accessTokenDto);

    echo "正在查询补货列表（维度：ASIN）...\n";

    $asin = 'B0CG62N4FH';

    $params = [
        'sid_list' => ['11533'],            // 可选：按店铺筛选，不需要可删除该参数
        'data_type' => 1,                  // 1 asin，2 msku
        'asin_list' => [$asin],            // 按 ASIN 列表筛选
        'offset' => DEFAULT_OFFSET,
        'length' => 20,                    // 上限 50
        // 'mode' => 0,                     // 可选：0普通 1海外仓中转；不传默认取 ERP 当前设置
        // 'listing_date_range' => ['2023-06-06', '2024-07-13'], // 可选：listing创建时间范围
    ];

    $response = $client->makeRequest('/erp/sc/routing/restocking/analysis/getSummaryList', 'POST', $params);
    displayResponseInfo($response);

    if (($response['code'] ?? -1) !== 0) {
        displayErrorInfo($response);
        exit(1);
    }

    echo "\n========== 完整返回（JSON） ==========\n";
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n";

    $total = (int)($response['total'] ?? 0);
    $list = $response['data'] ?? [];

    echo "筛选 ASIN: {$asin}\n";
    echo "总数: {$total}\n";
    echo "当前页条数: " . count($list) . "\n\n";

    if (empty($list)) {
        echo "当前条件下无补货数据。\n";
        exit(0);
    }

    foreach ($list as $index => $row) {
        $basic = $row['basic_info'] ?? [];
        $amazonQty = $row['amazon_quantity_info'] ?? [];
        $scmQty = $row['scm_quantity_info'] ?? [];
        $sales = $row['sales_info'] ?? [];
        $suggest = $row['suggest_info'] ?? [];
        $ext = $row['ext_info'] ?? [];

        echo "---- 记录 " . ($index + 1) . " ----\n";
        echo "hash_id: " . ($basic['hash_id'] ?? 'N/A') . "\n";
        echo "sid: " . ($basic['sid'] ?? 'N/A') . "\n";
        echo "asin: " . ($basic['asin'] ?? 'N/A') . "\n";
        echo "数据类型(data_type): " . ($basic['data_type'] ?? 'N/A') . "\n";
        echo "节点类型(node_type): " . ($basic['node_type'] ?? 'N/A') . "\n";
        echo "数据更新时间: " . ($basic['sync_time'] ?? 'N/A') . "\n";

        // 亚马逊数量（FBA）
        echo "FBA可售: " . ($amazonQty['amazon_quantity_valid'] ?? 0) . "\n";
        echo "FBA在途: " . ($amazonQty['amazon_quantity_shipping'] ?? 0) . "\n";

        // 供应链数量
        echo "本地仓可用: " . ($scmQty['sc_quantity_local_valid'] ?? 0) . "\n";
        echo "海外仓可用: " . ($scmQty['sc_quantity_oversea_valid'] ?? 0) . "\n";
        echo "采购计划: " . ($scmQty['sc_quantity_purchase_plan'] ?? 0) . "\n";

        // 销量（展示 7/30 日均与总销量）
        echo "日均销量(7天): " . ($sales['sales_avg_7'] ?? 0) . "\n";
        echo "日均销量(30天): " . ($sales['sales_avg_30'] ?? 0) . "\n";
        echo "总销量(7天): " . ($sales['sales_total_7'] ?? 0) . "\n";
        echo "总销量(30天): " . ($sales['sales_total_30'] ?? 0) . "\n";

        // 建议信息
        echo "断货标记: " . ($suggest['out_stock_flag'] ?? 0) . "\n";
        echo "断货日期: " . ($suggest['out_stock_date'] ?? '') . "\n";
        echo "预测可售天数: " . ($suggest['available_sale_days'] ?? 0) . "\n";
        echo "建议采购量: " . ($suggest['quantity_sug_purchase'] ?? 0) . "\n";
        echo "建议本地发海外仓量: " . ($suggest['quantity_sug_local_to_oversea'] ?? 0) . "\n";
        echo "建议海外仓发FBA量: " . ($suggest['quantity_sug_oversea_to_fba'] ?? 0) . "\n";

        // 附加信息
        echo "无需补货标识(restock_status): " . ($ext['restock_status'] ?? 'N/A') . "\n";
        echo "备注: " . ($ext['remark'] ?? '') . "\n";
        echo "\n";
    }

} catch (Exception $e) {
    echo "发生错误: " . $e->getMessage() . "\n";
    echo "错误类型: " . get_class($e) . "\n";
}
