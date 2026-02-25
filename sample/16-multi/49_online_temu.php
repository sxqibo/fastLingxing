<?php

/**
 * 查询 Temu 在线商品列表示例
 *
 * API: POST /basicOpen/multiplatform/temu/list
 * 令牌桶容量: 1
 *
 * 请求参数说明：
 * - searchField 必填，搜索维度：1标题 2品名 4SKC货号 5平台SPU 6平台SKC 7MSKU ID 8SKU 9MSKU
 * - storeIds 可选，可从多平台店铺列表接口（getSellerList）获取 Temu 店铺 store_id
 * - status 可选：0删除 2正常
 * - pairingStatus 可选：0未配对 1已配对
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

try {
    $client = createOpenAPIClient();

    echo "正在生成 AccessToken...\n";
    $accessTokenDto = $client->generateAccessToken();
    displayAccessTokenInfo($accessTokenDto);

    // 可选：先获取 Temu 店铺列表，得到 storeIds 再按店铺筛选
    $storeIds = [];
    echo "正在查询 Temu 店铺列表（用于 storeIds）...\n";
    $sellerResponse = $client->makeRequest('/pb/mp/shop/v2/getSellerList', 'POST', [
        'offset' => 0,
        'length' => 200,
        'platform_code' => [10024], // 10024 Temu半托管, 10022 Temu全托管
    ]);
    if (($sellerResponse['code'] ?? -1) === 0 && !empty($sellerResponse['data']['list'])) {
        foreach ($sellerResponse['data']['list'] as $store) {
            if (!empty($store['store_id'])) {
                $storeIds[] = (string)$store['store_id'];
            }
        }
        echo "获取到 Temu 店铺数: " . count($storeIds) . "\n";
    }
    echo "\n";

    // 查询 Temu 在线商品（不传 status 以包含全部状态：正常、已下架等）
    echo "正在查询 Temu 在线商品...\n";
    $params = [
        'offset' => DEFAULT_OFFSET,
        'length' => min(1000, MAX_LENGTH ?: 1000),
        'searchField' => '9',    // 必填，9=MSKU
    ];
    if (!empty($storeIds)) {
        $params['storeIds'] = $storeIds;
    }
    // 按需启用：品牌、分类、配对状态、精确/模糊搜索
    // $params['brandIds'] = [1,2,3];
    // $params['categoryIds'] = [10,9,8];
    // $params['pairingStatus'] = 1;
    // $params['searchValues'] = ['某个MSKU'];
    // $params['searchSingleValue'] = '模糊关键词';

    $response = $client->makeRequest('/basicOpen/multiplatform/temu/list', 'POST', $params);
    displayResponseInfo($response);

    if (($response['code'] ?? -1) !== 0) {
        displayErrorInfo($response);
        exit(1);
    }

    $data = $response['data'] ?? [];
    $list = $data['list'] ?? [];
    $count = (int)($data['count'] ?? 0);

    // selectStatus: 12=已加入站点(已发布到站点), 13=已下架, 其他=未发布到站点
    // 按店铺分组，再按发布状态分组，品名统计件数
    $byStore = [];
    foreach ($list as $item) {
        $storeId = (string)($item['storeId'] ?? '');
        $storeName = trim((string)($item['storeName'] ?? ''));
        $principalName = trim((string)($item['principalName'] ?? ''));
        $pname = trim((string)($item['pname'] ?? ''));
        if ($pname === '') {
            $pname = '未知品名';
        }
        $storeKey = $storeId !== '' ? $storeId : $storeName;
        if ($storeKey === '') {
            $storeKey = '未知店铺';
        }
        $selectStatus = (int)($item['selectStatus'] ?? -1);

        if (!isset($byStore[$storeKey])) {
            $byStore[$storeKey] = [
                'storeName' => $storeName ?: '未知店铺',
                'storeId' => $storeId,
                'principalName' => $principalName ?: '-',
                'published' => [],
                'unpublished' => [],
                'off_shelf' => [],
            ];
        }
        $row = &$byStore[$storeKey];
        if ($selectStatus === 12) {
            $row['published'][$pname] = ($row['published'][$pname] ?? 0) + 1;
        } elseif ($selectStatus === 13) {
            $row['off_shelf'][$pname] = ($row['off_shelf'][$pname] ?? 0) + 1;
        } else {
            $row['unpublished'][$pname] = ($row['unpublished'][$pname] ?? 0) + 1;
        }
    }
    unset($row);

    if (empty($byStore)) {
        echo "当前无 Temu 商品数据。\n";
        exit(0);
    }

    foreach ($byStore as $storeKey => $row) {
        $storeName = $row['storeName'];
        $storeId = $row['storeId'];
        $principalName = $row['principalName'];
        $idLabel = $storeId !== '' ? " (ID: {$storeId})" : '';
        $principalPart = ($principalName !== '' && $principalName !== '-') ? " - 负责人: " . $principalName : '';
        echo "店铺: " . $storeName . $idLabel . $principalPart . "\n";

        $published = $row['published'];
        $unpublished = $row['unpublished'];
        $offShelf = $row['off_shelf'];

        $totalPub = array_sum($published);
        $totalUnpub = array_sum($unpublished);
        $totalOff = array_sum($offShelf);

        if ($totalPub > 0) {
            echo "已发布到站点 - 总数量: " . $totalPub . "\n";
            foreach ($published as $pname => $cnt) {
                echo "• " . $pname . ": " . $cnt . " 件\n";
            }
        }
        if ($totalUnpub > 0) {
            echo "未发布到站点 - 总数量: " . $totalUnpub . "\n";
            foreach ($unpublished as $pname => $cnt) {
                echo "• " . $pname . ": " . $cnt . " 件\n";
            }
        }
        if ($totalOff > 0) {
            echo "已下架 - 总数量: " . $totalOff . "\n";
            foreach ($offShelf as $pname => $cnt) {
                echo "• " . $pname . ": " . $cnt . " 件\n";
            }
        }
        echo "\n";
    }

} catch (Exception $e) {
    echo "发生错误: " . $e->getMessage() . "\n";
    echo "错误类型: " . get_class($e) . "\n";
}
