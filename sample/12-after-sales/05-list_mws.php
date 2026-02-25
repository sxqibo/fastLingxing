<?php

/**
 * 查询评价管理 1-3 星 Feedback 列表示例
 *
 * API 1（获取sid）: GET  /erp/sc/data/seller/lists
 * API 2（查询反馈）: POST /erp/sc/cs/feedback/listMws
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

/**
 * 提取全部有效 sid（去重、过滤空值）
 *
 * @param array $sellerList
 * @return int[]
 */
function getAllSids(array $sellerList)
{
    $sidList = [];
    foreach ($sellerList as $row) {
        $sid = (int)($row['sid'] ?? 0);
        if ($sid > 0) {
            $sidList[] = $sid;
        }
    }

    $sidList = array_values(array_unique($sidList));
    sort($sidList);

    return $sidList;
}

/**
 * 构建 sid => 店铺名 的映射
 *
 * @param array $sellerList
 * @return array<int, string>
 */
function getSidNameMap(array $sellerList)
{
    $sidNameMap = [];
    foreach ($sellerList as $row) {
        $sid = (int)($row['sid'] ?? 0);
        $name = (string)($row['name'] ?? '');
        if ($sid > 0) {
            $sidNameMap[$sid] = $name !== '' ? $name : '未知店铺';
        }
    }

    return $sidNameMap;
}

try {
    // 初始化客户端
    $client = createOpenAPIClient();

    // 生成 AccessToken
    echo "正在生成 AccessToken...\n";
    $accessTokenDto = $client->generateAccessToken();
    displayAccessTokenInfo($accessTokenDto);

    // 第一步：查询亚马逊店铺列表，拿到 sid
    echo "正在查询亚马逊店铺列表（获取 sid）...\n";
    $sellerResponse = $client->makeRequest('/erp/sc/data/seller/lists', 'GET');
    displayResponseInfo($sellerResponse);

    if (($sellerResponse['code'] ?? -1) !== 0) {
        displayErrorInfo($sellerResponse);
        exit(1);
    }

    $sellerList = $sellerResponse['data'] ?? [];
    if (empty($sellerList)) {
        echo "未查询到任何已授权亚马逊店铺，无法继续查询 Feedback。\n";
        exit(0);
    }

    $sidList = getAllSids($sellerList);
    if (empty($sidList)) {
        echo "未能从店铺列表中解析到有效 sid，无法继续查询 Feedback。\n";
        exit(1);
    }

    echo "获取到 sid 共 " . count($sidList) . " 个:\n";
    echo implode(', ', $sidList) . "\n\n";

    $sidNameMap = getSidNameMap($sellerList);
    $pendingTotal = 0;        // status=0
    $processingTotal = 0;     // status=1
    $completedTotal = 0;      // status=2
    $pendingByStore = [];     // sid => count
    $processingByStore = [];  // sid => count
    $completedByStore = [];   // sid => count

    // 第二步：遍历全部 sid，分页查询并统计状态
    foreach ($sidList as $sid) {
        $offset = 0;
        $length = 200;
        $sidFetched = 0;

        echo "正在统计 sid={$sid}...\n";
        while (true) {
            $params = [
                'offset' => $offset,
                'length' => $length,
                'sid' => $sid,
                'start_date' => date('Y-m-d', strtotime('-30 days')),
                'end_date' => date('Y-m-d'),
            ];

            $feedbackResponse = $client->makeRequest('/erp/sc/cs/feedback/listMws', 'POST', $params);
            if (($feedbackResponse['code'] ?? -1) !== 0) {
                echo "sid={$sid} 查询失败：\n";
                displayErrorInfo($feedbackResponse);
                echo "\n";
                break;
            }

            $rows = $feedbackResponse['data'] ?? [];
            if (empty($rows)) {
                break;
            }

            foreach ($rows as $item) {
                $status = (int)($item['status'] ?? -1);
                if ($status === 0) {
                    $pendingTotal++;
                    $pendingByStore[$sid] = ($pendingByStore[$sid] ?? 0) + 1;
                } elseif ($status === 1) {
                    $processingTotal++;
                    $processingByStore[$sid] = ($processingByStore[$sid] ?? 0) + 1;
                } elseif ($status === 2) {
                    $completedTotal++;
                    $completedByStore[$sid] = ($completedByStore[$sid] ?? 0) + 1;
                }
            }

            $sidFetched += count($rows);
            if (count($rows) < $length) {
                break;
            }
            $offset += $length;
        }

        echo "sid={$sid} 已统计 {$sidFetched} 条\n";
    }

    echo "\n========== 统计结果 ==========\n";
    echo "待处理(status=0)总数: {$pendingTotal}\n";
    echo "处理中(status=1)总数: {$processingTotal}\n\n";
    // echo "已完成(status=2)总数: {$completedTotal}\n\n";

    echo "待处理对应店铺：\n";
    if (empty($pendingByStore)) {
        echo "- 无\n";
    } else {
        foreach ($pendingByStore as $sid => $count) {
            $storeName = $sidNameMap[$sid] ?? '未知店铺';
            echo "- sid={$sid}，店铺={$storeName}，数量={$count}\n";
        }
    }

    echo "\n处理中对应店铺：\n";
    if (empty($processingByStore)) {
        echo "- 无\n";
    } else {
        foreach ($processingByStore as $sid => $count) {
            $storeName = $sidNameMap[$sid] ?? '未知店铺';
            echo "- sid={$sid}，店铺={$storeName}，数量={$count}\n";
        }
    }

    echo "\n已完成对应店铺：\n";
    if (empty($completedByStore)) {
        echo "- 无\n";
    } else {
        foreach ($completedByStore as $sid => $count) {
            $storeName = $sidNameMap[$sid] ?? '未知店铺';
            echo "- sid={$sid}，店铺={$storeName}，数量={$count}\n";
        }
    }

} catch (Exception $e) {
    echo "发生错误: " . $e->getMessage() . "\n";
    echo "错误类型: " . get_class($e) . "\n";
}
