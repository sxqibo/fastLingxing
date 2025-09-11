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

    // 查询亚马逊Listing
    echo "正在查询亚马逊Listing...\n";
    
    // 请求参数
    $params = [
        'sid' => SAMPLE_SID,  // 店铺ID，必填
        //'is_pair' => 1,   // 是否配对：1 已配对，2 未配对
        'is_delete' => 0, // 是否删除：0 未删除，1 已删除
        'offset' => DEFAULT_OFFSET,    // 分页偏移量，默认0
        'length' => MAX_LENGTH    // 分页长度，默认1000，上限1000
    ];
    
    // 发起POST请求
    $response = $client->makeRequest('/erp/sc/data/mws/listing', 'POST', $params);
    
    // 输出响应结果
    displayResponseInfo($response);
    
    if ($response['code'] == 0) {
        echo "亚马逊Listing数据:\n";
        echo "总数: " . $response['total'] . "\n\n";
        
        if (!empty($response['data'])) {
            foreach ($response['data'] as $index => $listing) {
                echo "Listing " . ($index + 1) . ":\n";
                echo "  - Listing ID: " . ($listing['listing_id'] ?: '无') . "\n";
                echo "  - 店铺ID: " . $listing['sid'] . "\n";
                echo "  - 国家: " . $listing['marketplace'] . "\n";
                echo "  - MSKU: " . $listing['seller_sku'] . "\n";
                echo "  - FNSKU: " . $listing['fnsku'] . "\n";
                echo "  - ASIN: " . $listing['asin'] . "\n";
                echo "  - 父ASIN: " . ($listing['parent_asin'] ?: '无') . "\n";
                echo "  - 标题: " . $listing['item_name'] . "\n";
                echo "  - 本地SKU: " . ($listing['local_sku'] ?: '无') . "\n";
                echo "  - 品名: " . ($listing['local_name'] ?: '无') . "\n";
                echo "  - 币种: " . $listing['currency_code'] . "\n";
                echo "  - 价格: " . $listing['price'] . "\n";
                echo "  - 总价: " . $listing['landed_price'] . "\n";
                echo "  - 优惠价: " . $listing['listing_price'] . "\n";
                echo "  - 运费: " . $listing['shipping'] . "\n";
                echo "  - 积分: " . ($listing['points'] ?: '0') . "\n";
                echo "  - FBM库存: " . $listing['quantity'] . "\n";
                echo "  - FBA可售: " . $listing['afn_fulfillable_quantity'] . "\n";
                echo "  - FBA不可售: " . $listing['afn_unsellable_quantity'] . "\n";
                echo "  - 待调仓: " . $listing['reserved_fc_transfers'] . "\n";
                echo "  - 调仓中: " . $listing['reserved_fc_processing'] . "\n";
                echo "  - 待发货: " . $listing['reserved_customerorders'] . "\n";
                echo "  - 在途: " . $listing['afn_inbound_shipped_quantity'] . "\n";
                echo "  - 计划入库: " . $listing['afn_inbound_working_quantity'] . "\n";
                echo "  - 入库中: " . $listing['afn_inbound_receiving_quantity'] . "\n";
                echo "  - 商品创建时间: " . $listing['open_date_display'] . "\n";
                echo "  - 报表更新时间: " . $listing['listing_update_date'] . "\n";
                echo "  - 排名: " . $listing['seller_rank'] . "\n";
                echo "  - 亚马逊品牌: " . ($listing['seller_brand'] ?: '无') . "\n";
                echo "  - 评论条数: " . $listing['review_num'] . "\n";
                echo "  - 星级评分: " . $listing['last_star'] . "\n";
                echo "  - 配送方式: " . $listing['fulfillment_channel_type'] . "\n";
                echo "  - 商品类型: " . ($listing['store_type'] == 1 ? '非低价商店' : '低价商店商品') . "\n";
                echo "  - 销量-7天: " . $listing['total_volume'] . "\n";
                echo "  - 销量-昨天: " . $listing['yesterday_volume'] . "\n";
                echo "  - 销量-14天: " . $listing['fourteen_volume'] . "\n";
                echo "  - 销量-30天: " . $listing['thirty_volume'] . "\n";
                echo "  - 销售额-昨天: " . $listing['yesterday_amount'] . "\n";
                echo "  - 销售额-7天: " . $listing['seven_amount'] . "\n";
                echo "  - 销售额-14天: " . $listing['fourteen_amount'] . "\n";
                echo "  - 销售额-30天: " . $listing['thirty_amount'] . "\n";
                echo "  - 日均销量-7日: " . $listing['average_seven_volume'] . "\n";
                echo "  - 日均销量-14日: " . $listing['average_fourteen_volume'] . "\n";
                echo "  - 日均销量-30日: " . $listing['average_thirty_volume'] . "\n";
                echo "  - 配对更新时间: " . ($listing['pair_update_time'] ?: '无') . "\n";
                echo "  - 首单时间: " . ($listing['first_order_time'] ?: '无') . "\n";
                echo "  - 开售时间: " . ($listing['on_sale_time'] ?: '无') . "\n";
                
                // 状态说明
                $statusText = $listing['status'] == 1 ? '在售' : '停售';
                $isDeleteText = $listing['is_delete'] == 1 ? '已删除' : '未删除';
                echo "  - 状态: " . $statusText . "\n";
                echo "  - 是否删除: " . $isDeleteText . "\n";
                
                // 负责人信息
                if (!empty($listing['principal_info'])) {
                    echo "  - 负责人信息:\n";
                    foreach ($listing['principal_info'] as $principal) {
                        echo "    * 用户ID: " . $principal['principal_uid'] . "\n";
                        echo "    * 姓名: " . $principal['principal_name'] . "\n";
                    }
                }
                
                // 小类排名信息
                if (!empty($listing['small_rank'])) {
                    echo "  - 小类排名信息:\n";
                    foreach ($listing['small_rank'] as $rank) {
                        echo "    * 类别: " . $rank['category'] . " - 排名: " . $rank['rank'] . "\n";
                    }
                }
                
                // 全局标签
                if (!empty($listing['global_tags'])) {
                    echo "  - 全局标签:\n";
                    foreach ($listing['global_tags'] as $tag) {
                        echo "    * " . $tag['tagName'] . " (ID: " . $tag['globalTagId'] . ", 颜色: " . $tag['color'] . ")\n";
                    }
                }
                
                // 尺寸信息
                if (!empty($listing['dimension_info']) && is_array($listing['dimension_info'])) {
                    echo "  - 尺寸信息:\n";
                    foreach ($listing['dimension_info'] as $dimension) {
                        if (is_array($dimension)) {
                            echo "    * 项目尺寸: " . ($dimension['item_length'] ?? '0.00') . " x " . ($dimension['item_width'] ?? '0.00') . " x " . ($dimension['item_height'] ?? '0.00') . " " . ($dimension['item_length_units_type'] ?? '') . "\n";
                            echo "    * 项目重量: " . ($dimension['item_weight'] ?? '0.00') . " " . ($dimension['item_weight_units_type'] ?? '') . "\n";
                            echo "    * 包装尺寸: " . ($dimension['package_length'] ?? '0.00') . " x " . ($dimension['package_width'] ?? '0.00') . " x " . ($dimension['package_height'] ?? '0.00') . " " . ($dimension['package_length_units_type'] ?? '') . "\n";
                            echo "    * 包装重量: " . ($dimension['package_weight'] ?? '0.00') . " " . ($dimension['package_weight_units_type'] ?? '') . "\n";
                        }
                    }
                }
                
                echo "\n";
            }
            echo "共查询到 " . count($response['data']) . " 个Listing\n";
        } else {
            echo "暂无Listing数据\n";
        }
    } else {
        displayErrorInfo($response);
    }

} catch (Exception $e) {
    echo "发生错误: " . $e->getMessage() . "\n";
    echo "错误类型: " . get_class($e) . "\n";
}