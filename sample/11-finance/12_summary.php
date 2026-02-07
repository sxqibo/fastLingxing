<?php

/**
 * 查询利润报表-店铺月度汇总（按天）
 * API Path: /bd/profit/report/open/report/seller/summary/list
 * 查询昨天的销量、广告、销售额、退款、费用、成本、毛利润、毛利率、ROI 等（人民币 CNY），并推送到钉钉（dingtalk_robot id=42）
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/dingtalk_helper.php';

try {
    $client = createOpenAPIClient();

    echo "正在生成 AccessToken...\n";
    $accessTokenDto = $client->generateAccessToken();
    displayAccessTokenInfo($accessTokenDto);

    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $params = [
        'startDate'    => $yesterday,
        'endDate'      => $yesterday,
        'monthlyQuery' => false,
        'currencyCode' => 'CNY',
        'orderStatus'  => 'Disbursed',
    ];

    echo "正在查询利润报表-店铺汇总（昨天 {$yesterday}，币种 CNY）...\n";
    echo "请求参数: " . json_encode($params, JSON_UNESCAPED_UNICODE) . "\n\n";

    $response = $client->makeRequest('/bd/profit/report/open/report/seller/summary/list', 'POST', $params);
    displayResponseInfo($response);

    if ($response['code'] != 0) {
        displayErrorInfo($response);
        exit(1);
    }

    $list = $response['data'] ?? [];
    if (empty($list)) {
        echo "昨天暂无汇总数据\n";
        exit(0);
    }

    $fields = [
        'fbaSalesQuantity'      => 'FBA销量',
        'fbmSalesQuantity'      => 'FBM销量',
        'totalAdsSales'         => '广告销售额',
        'totalAdsSalesQuantity' => '广告销量',
        'totalSalesAmount'      => '销售额',
        'totalSalesRefunds'     => '收入退款额',
        'totalFeeRefunds'       => '费用退款额',
        'fbaReturnsQuantity'    => '退货量',
        'fbaDeliveryFee'        => 'FBA发货费',
        'totalAdsCost'          => '广告费',
        'totalSalesTax'         => '销售税',
        'salesTaxRefund'        => '销售税退款额',
        'totalCost'             => '合计成本',
        'grossProfit'           => '毛利润',
        'grossRate'             => '毛利率',
        'roi'                   => 'ROI',
    ];

    $totals = array_fill_keys(array_keys($fields), null);
    $isNumericSum = [
        'fbaSalesQuantity', 'fbmSalesQuantity', 'totalAdsSalesQuantity', 'fbaReturnsQuantity',
        'totalAdsSales', 'totalSalesAmount', 'totalSalesRefunds', 'totalFeeRefunds',
        'fbaDeliveryFee', 'totalAdsCost', 'totalSalesTax', 'salesTaxRefund', 'totalCost', 'grossProfit',
    ];
    $rateFields = ['grossRate', 'roi'];

    foreach ($list as $index => $row) {
        $storeName = $row['storeName'] ?? ('店铺' . ($row['sids'] ?? ($index + 1)));
        $date = $row['date'] ?? $row['postedDateLocale'] ?? $yesterday;
        echo "========== {$storeName} | {$date} ==========\n";
        foreach ($fields as $key => $label) {
            $val = $row[$key] ?? null;
            if ($val !== null && $val !== '') {
                if (in_array($key, $rateFields, true)) {
                    echo "  {$label}: " . (is_numeric($val) ? round((float)$val * 100, 2) . '%' : $val) . "\n";
                } else {
                    echo "  {$label}: {$val}\n";
                }
            } else {
                echo "  {$label}: -\n";
            }
            if (in_array($key, $isNumericSum, true) && isset($row[$key]) && is_numeric($row[$key])) {
                $totals[$key] = ($totals[$key] ?? 0) + (float)$row[$key];
            } elseif (in_array($key, $rateFields, true) && isset($row[$key]) && is_numeric($row[$key])) {
                $totals[$key] = $row[$key];
            }
        }
        echo "\n";
    }

    if (count($list) > 1) {
        echo "========== 合计（多店铺汇总）==========\n";
        foreach ($fields as $key => $label) {
            if (in_array($key, $isNumericSum, true) && $totals[$key] !== null) {
                echo "  {$label}: {$totals[$key]}\n";
            } elseif (in_array($key, $rateFields, true)) {
                echo "  {$label}: （多店铺请以各店或加权方式计算）\n";
            }
        }
    }

    // 组装 Markdown 推送到钉钉（dingtalk_robot id=42）
    $title = "利润报表-店铺汇总（{$yesterday}）";
    $mdLines = ["### 利润报表-店铺汇总（{$yesterday}）\n"];
    foreach ($list as $index => $row) {
        $storeName = $row['storeName'] ?? ('店铺' . ($row['sids'] ?? ($index + 1)));
        $date = $row['date'] ?? $row['postedDateLocale'] ?? $yesterday;
        $mdLines[] = "**{$storeName} | {$date}**";
        foreach ($fields as $key => $label) {
            $val = $row[$key] ?? null;
            if ($val !== null && $val !== '') {
                if (in_array($key, $rateFields, true)) {
                    $mdLines[] = "- {$label}: " . (is_numeric($val) ? round((float)$val * 100, 2) . '%' : $val);
                } else {
                    $mdLines[] = "- {$label}: {$val}";
                }
            } else {
                $mdLines[] = "- {$label}: -";
            }
        }
        $mdLines[] = "";
    }
    if (count($list) > 1) {
        $mdLines[] = "**合计（多店铺）**";
        foreach ($fields as $key => $label) {
            if (in_array($key, $isNumericSum, true) && $totals[$key] !== null) {
                $mdLines[] = "- {$label}: {$totals[$key]}";
            }
        }
    }
    $markdownText = implode("\n", $mdLines);

    try {
        $dingResp = sendDingtalkMarkdownByRobotId(42, $title, $markdownText);
        if (isset($dingResp['errcode']) && (int)$dingResp['errcode'] === 0) {
            echo "\n钉钉推送成功（dingtalk_robot id=42）\n";
        } else {
            echo "\n钉钉推送失败: " . ($dingResp['errmsg'] ?? json_encode($dingResp, JSON_UNESCAPED_UNICODE)) . "\n";
        }
    } catch (Exception $e) {
        echo "\n钉钉推送异常: " . $e->getMessage() . "\n";
    }
} catch (Exception $e) {
    echo "发生错误: " . $e->getMessage() . "\n";
    echo "错误类型: " . get_class($e) . "\n";
}
