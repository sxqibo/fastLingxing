<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config.php';

try {
    // 初始化客户端
    $client = createOpenAPIClient();

    // 生成AccessToken
    echo "正在生成AccessToken...\n";
    $accessTokenDto = $client->generateAccessToken();
    displayAccessTokenInfo($accessTokenDto);

    // 查询仓库库存明细
    echo "正在查询仓库库存明细...\n";
    
    // 请求参数
    $params = [
        'wid' => SAMPLE_WID,  // 仓库ID，多个使用英文逗号分隔（可选）
        'offset' => DEFAULT_OFFSET,    // 分页偏移量，默认0
        'length' => MAX_LENGTH,   // 分页长度，默认20，上限800
        // 'sku' => 'Test01'      // SKU，单个（可选）
    ];
    
    // 发起POST请求
    $response = $client->makeRequest('/erp/sc/routing/data/local_inventory/inventoryDetails', 'POST', $params);
    
    // 输出响应结果
    displayResponseInfo($response);
    
    if ($response['code'] == 0) {
        echo "仓库库存明细数据:\n";
        echo "总数: " . $response['total'] . "\n\n";
        
        if (!empty($response['data'])) {
            foreach ($response['data'] as $index => $inventory) {
                echo "库存明细 " . ($index + 1) . ":\n";
                echo "  - 仓库ID: " . $inventory['wid'] . "\n";
                echo "  - 本地产品ID: " . $inventory['product_id'] . "\n";
                echo "  - SKU: " . $inventory['sku'] . "\n";
                echo "  - 店铺ID: " . ($inventory['seller_id'] ?: '无') . "\n";
                echo "  - FNSKU: " . ($inventory['fnsku'] ?: '无') . "\n";
                echo "  - 实际库存总量: " . $inventory['product_total'] . "\n";
                echo "  - 可用量: " . $inventory['product_valid_num'] . "\n";
                echo "  - 次品量: " . $inventory['product_bad_num'] . "\n";
                echo "  - 待检待上架量: " . $inventory['product_qc_num'] . "\n";
                echo "  - 锁定量: " . $inventory['product_lock_num'] . "\n";
                echo "  - 库存成本: " . $inventory['stock_cost_total'] . "\n";
                echo "  - 待到货量: " . $inventory['quantity_receive'] . "\n";
                echo "  - 单位库存成本: " . $inventory['stock_cost'] . "\n";
                echo "  - 调拨在途: " . $inventory['product_onway'] . "\n";
                echo "  - 调拨在途头程成本: " . $inventory['transit_head_cost'] . "\n";
                echo "  - 平均库龄: " . $inventory['average_age'] . " 天\n";
                echo "  - 采购单价: " . ($inventory['purchase_price'] ?: '无') . "\n";
                echo "  - 单位费用: " . ($inventory['price'] ?: '无') . "\n";
                echo "  - 单位头程: " . ($inventory['head_stock_price'] ?: '无') . "\n";
                echo "  - 单位库存成本: " . ($inventory['stock_price'] ?: '无') . "\n";
                
                // 海外仓第三方库存信息
                if (!empty($inventory['third_inventory']) && is_array($inventory['third_inventory'])) {
                    echo "  - 海外仓第三方库存信息:\n";
                    foreach ($inventory['third_inventory'] as $third) {
                        if (is_array($third)) {
                            echo "    * 可用量: " . ($third['qty_sellable'] ?? '0') . "\n";
                            echo "    * 待上架库存: " . ($third['qty_pending'] ?? '0') . "\n";
                            echo "    * 锁定量: " . ($third['qty_reserved'] ?? '0') . "\n";
                            echo "    * 第三方海外仓备货在途: " . ($third['qty_onway'] ?? '0') . "\n";
                        }
                    }
                }
                
                // 库龄信息
                if (!empty($inventory['stock_age_list']) && is_array($inventory['stock_age_list'])) {
                    echo "  - 库龄信息:\n";
                    foreach ($inventory['stock_age_list'] as $age) {
                        if (is_array($age)) {
                            echo "    * " . ($age['name'] ?? '未知库龄') . ": " . ($age['qty'] ?? '0') . " 件\n";
                        }
                    }
                }
                
                echo "\n";
            }
            echo "共查询到 " . count($response['data']) . " 条库存明细\n";
        } else {
            echo "暂无库存明细数据\n";
        }
    } else {
        displayErrorInfo($response);
    }

} catch (Exception $e) {
    echo "发生错误: " . $e->getMessage() . "\n";
    echo "错误类型: " . get_class($e) . "\n";
}