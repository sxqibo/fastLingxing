<?php

/**
 * 查询规则 - ASIN（FBA补货建议：ASIN补货规则）示例
 *
 * API: POST /erp/sc/routing/fbaSug/asin/getConfig
 *
 * 本示例固定参数：
 * - sid = 11533
 * - asin = B0CG62N4FH
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

    $sid = 11533;
    $asin = 'B0CG62N4FH';

    echo "正在查询 ASIN 补货规则...\n";
    echo "sid={$sid}, asin={$asin}\n\n";

    $params = [
        'sid' => $sid,
        'asin' => $asin,
    ];

    $response = $client->makeRequest('/erp/sc/routing/fbaSug/asin/getConfig', 'POST', $params);
    displayResponseInfo($response);

    if (($response['code'] ?? -1) !== 0) {
        displayErrorInfo($response);
        exit(1);
    }

    echo "\n========== 完整返回（JSON） ==========\n";
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";

    $data = $response['data'] ?? [];
    $info = $data['info'] ?? [];
    $list = $data['list'] ?? [];
    $denoise = $data['denoise'] ?? [];

    echo "\n========== 规则信息 info ==========\n";
    if (empty($info)) {
        echo "无\n";
    } else {
        echo "总备货时长(普通): " . ($info['days_total'] ?? 'N/A') . "\n";
        echo "总备货时长(海外仓中转): " . ($info['days_total2'] ?? 'N/A') . "\n";
        echo "采购计划时长: " . ($info['days_plan'] ?? 'N/A') . "\n";
        echo "采购时长: " . ($info['days_purchase'] ?? 'N/A') . "\n";
        echo "质检时长: " . ($info['days_qc'] ?? 'N/A') . "\n";
        echo "海外仓至FBA天数: " . ($info['days_oversea_to_fba'] ?? 'N/A') . "\n";
        echo "安全天数: " . ($info['safe_day'] ?? 'N/A') . "\n";
        echo "建议量扣除必断货量: " . ($info['is_ignore_certainly_short'] ?? 'N/A') . "\n";
        echo "历史销量排除断货数据: " . ($info['is_ignore_history_out_stock'] ?? 'N/A') . "\n";

        $smFbaList = $info['sm_fba_list'] ?? [];
        if (!empty($smFbaList)) {
            echo "\n本地仓至FBA时效(sm_fba_list):\n";
            foreach ($smFbaList as $sm) {
                echo "- {$sm['name']} (sm_id: {$sm['sm_id']}, code: {$sm['code']}): {$sm['days']} 天\n";
            }
        }

        $smOverseaList = $info['sm_oversea_list'] ?? [];
        if (!empty($smOverseaList)) {
            echo "\n本地仓至海外仓时效(sm_oversea_list):\n";
            foreach ($smOverseaList as $sm) {
                echo "- {$sm['name']} (sm_id: {$sm['sm_id']}, code: {$sm['code']}): {$sm['days']} 天\n";
            }
        }
    }

    echo "\n========== 日销量设置 list ==========\n";
    if (empty($list)) {
        echo "无\n";
    } else {
        foreach ($list as $idx => $cfg) {
            echo "---- 配置 " . ($idx + 1) . " ----\n";
            echo "标题: " . ($cfg['title'] ?? '') . "\n";
            echo "是否默认: " . ($cfg['is_default'] ?? 'N/A') . "\n";
            echo "类型(type): " . ($cfg['type'] ?? 'N/A') . "\n";
            echo "起始日期: " . ($cfg['date_start'] ?? '') . "\n";
            echo "结束日期: " . ($cfg['date_end'] ?? '') . "\n";
            echo "日销量(volume): " . ($cfg['volume'] ?? 'N/A') . "\n";
            echo "权重(3/7/14/30/60/90): "
                . ($cfg['weigth_3'] ?? 0) . "/"
                . ($cfg['weigth_7'] ?? 0) . "/"
                . ($cfg['weigth_14'] ?? 0) . "/"
                . ($cfg['weigth_30'] ?? 0) . "/"
                . ($cfg['weigth_60'] ?? 0) . "/"
                . ($cfg['weigth_90'] ?? 0) . "\n\n";
        }
    }

    echo "========== 去噪设置 denoise ==========\n";
    if (empty($denoise)) {
        echo "无\n";
    } else {
        foreach ($denoise as $idx => $d) {
            echo "---- 去噪配置 " . ($idx + 1) . " ----\n";
            echo "名称: " . ($d['title'] ?? '') . "\n";
            echo "起始日期: " . ($d['date_start'] ?? '') . "\n";
            echo "结束日期: " . ($d['date_end'] ?? '') . "\n";
            echo "类型(type): " . ($d['type'] ?? 'N/A') . "\n";
            echo "去噪百分比(percent): " . ($d['percent'] ?? 'N/A') . "\n";
            echo "日销量(volume): " . ($d['volume'] ?? 'N/A') . "\n\n";
        }
    }

} catch (Exception $e) {
    echo "发生错误: " . $e->getMessage() . "\n";
    echo "错误类型: " . get_class($e) . "\n";
}
