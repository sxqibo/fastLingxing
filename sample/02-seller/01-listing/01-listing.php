<?php
/**
 * 亚马逊Listing查询示例
 * 
 * 接口说明：
 * - API路径: /erp/sc/data/mws/listing
 * - 请求方式: POST
 * - 令牌桶容量: 1
 * - 唯一键: sid+seller_sku
 * 
 * 主要功能：
 * 1. 查询亚马逊店铺的Listing数据
 * 2. 支持多种筛选条件（配对状态、删除状态、时间范围等）
 * 3. 支持搜索功能（按ASIN、SKU等）
 * 4. 返回详细的商品信息、库存信息、销售数据等
 * 
 * 返回数据包含：
 * - 基本信息：Listing ID、店铺ID、国家、MSKU、FNSKU、ASIN等
 * - 商品信息：标题、价格、币种、运费等
 * - 库存信息：FBM库存、FBA各种状态库存等
 * - 销售数据：销量、销售额、日均销量等
 * - 排名评价：商品排名、评论数、星级评分等
 * - 其他信息：负责人、标签、尺寸等
 */

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
    
    // 请求参数配置
    // 注意：以下参数根据需要取消注释使用
    $params = [
        'sid' => SAMPLE_SID,  // 店铺ID，必填，多个使用英文逗号分隔
        
        // === 筛选条件 ===
        //'is_pair' => 1,   // 是否配对：1 已配对，2 未配对
        'is_delete' => 0, // 是否删除：0 未删除，1 已删除
        //'store_type' => 1, // 商品类型：1-非低价商店，2-低价商店商品
        
        // === 时间筛选（配对更新时间，北京时间）===
        //'pair_update_start_time' => '2022-01-01 12:01:21', // 配对更新时间开始时间
        //'pair_update_end_time' => '2022-10-01 12:01:21',   // 配对更新时间结束时间
        
        // === 时间筛选（报表更新时间，零时区时间）===
        //'listing_update_start_time' => '2021-09-01 01:22:00', // All Listing报表更新时间开始时间
        //'listing_update_end_time' => '2022-10-01 11:22:00',   // All Listing报表更新时间结束时间
        
        // === 搜索功能 ===
        //'search_field' => 'asin', // 搜索字段：seller_sku、asin、sku
        //'search_value' => ['asin1', 'asin2'], // 搜索值，上限10个
        //'exact_search' => 1, // 搜索模式：0 模糊搜索，1 精确搜索（默认）
        
        // === 分页参数 ===
        'offset' => DEFAULT_OFFSET,    // 分页偏移量，默认0
        'length' => MAX_LENGTH    // 分页长度，默认1000，上限1000
    ];
    
    // 发起POST请求
    $response = $client->makeRequest('/erp/sc/data/mws/listing', 'POST', $params);
    
    // 输出响应结果
    displayResponseInfo($response);
    
    if ($response['code'] == 0) {
        echo "\n=== 亚马逊Listing查询结果 ===\n";
        echo "请求状态: 成功\n";
        echo "请求ID: " . $response['request_id'] . "\n";
        echo "响应时间: " . $response['response_time'] . "\n";
        echo "数据总数: " . $response['total'] . "\n";
        echo "当前页数据量: " . count($response['data']) . "\n";
        echo "================================\n\n";
        
        if (!empty($response['data'])) {
            foreach ($response['data'] as $index => $listing) {
                echo "Listing " . ($index + 1) . ":\n";
                echo "  === 基本信息 ===\n";
                echo "  - Listing ID: " . ($listing['listing_id'] ?: '无') . " 【亚马逊定义的listing的id，可能为空】\n";
                echo "  - 店铺ID: " . $listing['sid'] . " 【店铺唯一标识】\n";
                echo "  - 国家: " . $listing['marketplace'] . " 【亚马逊站点国家】\n";
                echo "  - MSKU: " . $listing['seller_sku'] . " 【卖家SKU，唯一键之一】\n";
                echo "  - FNSKU: " . $listing['fnsku'] . " 【FBA商品标识码】\n";
                echo "  - ASIN: " . $listing['asin'] . " 【亚马逊标准识别号】\n";
                echo "  - 父ASIN: " . ($listing['parent_asin'] ?: '无') . " 【父级ASIN，用于变体商品】\n";
                echo "  - 商品缩略图: " . ($listing['small_image_url'] ?: '无') . " 【商品图片地址】\n";
                
                echo "  === 商品信息 ===\n";
                echo "  - 标题: " . $listing['item_name'] . " 【商品标题】\n";
                echo "  - 本地SKU: " . ($listing['local_sku'] ?: '无') . " 【本地产品SKU】\n";
                echo "  - 品名: " . ($listing['local_name'] ?: '无') . " 【本地产品名称】\n";
                echo "  - 币种: " . $listing['currency_code'] . " 【价格币种】\n";
                echo "  - 价格: " . $listing['price'] . " 【基础价格，不包含促销、运费、积分】\n";
                echo "  - 总价: " . $listing['landed_price'] . " 【落地价，包含促销、运费、积分】\n";
                echo "  - 优惠价: " . $listing['listing_price'] . " 【优惠后的价格】\n";
                echo "  - 运费: " . $listing['shipping'] . " 【运费金额】\n";
                echo "  - 积分: " . ($listing['points'] ?: '0') . " 【积分，日本站才有】\n";
                
                echo "  === 库存信息 ===\n";
                echo "  - FBM库存: " . $listing['quantity'] . " 【自发货库存数量】\n";
                echo "  - FBA可售: " . $listing['afn_fulfillable_quantity'] . " 【FBA可售库存】\n";
                echo "  - FBA不可售: " . $listing['afn_unsellable_quantity'] . " 【FBA不可售库存】\n";
                echo "  - 待调仓: " . $listing['reserved_fc_transfers'] . " 【待调仓库存】\n";
                echo "  - 调仓中: " . $listing['reserved_fc_processing'] . " 【调仓中库存】\n";
                echo "  - 待发货: " . $listing['reserved_customerorders'] . " 【待发货库存】\n";
                echo "  - 在途: " . $listing['afn_inbound_shipped_quantity'] . " 【在途库存】\n";
                echo "  - 计划入库: " . $listing['afn_inbound_working_quantity'] . " 【计划入库库存】\n";
                echo "  - 入库中: " . $listing['afn_inbound_receiving_quantity'] . " 【入库中库存】\n";
                
                echo "  === 时间信息 ===\n";
                echo "  - 商品创建时间: " . $listing['open_date_display'] . " 【商品首次创建时间，格式：Y-m-d H:i:s+时区】\n";
                echo "  - 报表更新时间: " . $listing['listing_update_date'] . " 【All Listing报表更新时间，零时区时间】\n";
                echo "  - 配对更新时间: " . ($listing['pair_update_time'] ?: '无') . " 【配对更新时间，北京时间】\n";
                echo "  - 首单时间: " . ($listing['first_order_time'] ?: '无') . " 【首次订单时间，格式：Y-m-d】\n";
                echo "  - 开售时间: " . ($listing['on_sale_time'] ?: '无') . " 【开售时间，格式：Y-m-d】\n";
                
                echo "  === 排名与评价 ===\n";
                echo "  - 排名: " . $listing['seller_rank'] . " 【商品排名】\n";
                echo "  - 亚马逊品牌: " . ($listing['seller_brand'] ?: '无') . " 【亚马逊品牌信息】\n";
                echo "  - 评论条数: " . $listing['review_num'] . " 【客户评论数量】\n";
                echo "  - 星级评分: " . $listing['last_star'] . " 【平均星级评分】\n";
                
                echo "  === 销售数据 ===\n";
                echo "  - 配送方式: " . $listing['fulfillment_channel_type'] . " 【FBM/FBA配送方式】\n";
                echo "  - 商品类型: " . ($listing['store_type'] == 1 ? '非低价商店' : '低价商店商品') . " 【商品类型】\n";
                echo "  - 销量-7天: " . $listing['total_volume'] . " 【7天销量】\n";
                echo "  - 销量-昨天: " . $listing['yesterday_volume'] . " 【昨天销量】\n";
                echo "  - 销量-14天: " . $listing['fourteen_volume'] . " 【14天销量】\n";
                echo "  - 销量-30天: " . $listing['thirty_volume'] . " 【30天销量】\n";
                echo "  - 销售额-昨天: " . $listing['yesterday_amount'] . " 【昨天销售额】\n";
                echo "  - 销售额-7天: " . $listing['seven_amount'] . " 【7天销售额】\n";
                echo "  - 销售额-14天: " . $listing['fourteen_amount'] . " 【14天销售额】\n";
                echo "  - 销售额-30天: " . $listing['thirty_amount'] . " 【30天销售额】\n";
                echo "  - 日均销量-7日: " . $listing['average_seven_volume'] . " 【7天日均销量】\n";
                echo "  - 日均销量-14日: " . $listing['average_fourteen_volume'] . " 【14天日均销量】\n";
                echo "  - 日均销量-30日: " . $listing['average_thirty_volume'] . " 【30天日均销量】\n";
                
                echo "  === 状态信息 ===\n";
                $statusText = $listing['status'] == 1 ? '在售' : '停售';
                $isDeleteText = $listing['is_delete'] == 1 ? '已删除' : '未删除';
                echo "  - 销售状态: " . $statusText . " 【0-停售，1-在售】\n";
                echo "  - 删除状态: " . $isDeleteText . " 【0-未删除，1-已删除】\n";
                
                // 负责人信息
                if (!empty($listing['principal_info'])) {
                    echo "  === 负责人信息 ===\n";
                    foreach ($listing['principal_info'] as $principal) {
                        echo "  - 用户ID: " . $principal['principal_uid'] . " 【负责人用户ID】\n";
                        echo "  - 姓名: " . $principal['principal_name'] . " 【负责人姓名】\n";
                    }
                }
                
                // 排名类别信息
                if (!empty($listing['seller_category_new'])) {
                    echo "  === 排名类别 ===\n";
                    echo "  - 类别: " . implode(', ', $listing['seller_category_new']) . " 【排名所属的类别】\n";
                }
                
                // 小类排名信息
                if (!empty($listing['small_rank'])) {
                    echo "  === 小类排名详情 ===\n";
                    foreach ($listing['small_rank'] as $rank) {
                        echo "  - 小类: " . $rank['category'] . " 【小类名称】\n";
                        echo "  - 排名: " . $rank['rank'] . " 【该小类中的排名】\n";
                    }
                }
                
                // 全局标签
                if (!empty($listing['global_tags'])) {
                    echo "  === 全局标签 ===\n";
                    foreach ($listing['global_tags'] as $tag) {
                        echo "  - 标签名: " . $tag['tagName'] . " 【标签名称】\n";
                        echo "  - 标签ID: " . $tag['globalTagId'] . " 【全局标签ID】\n";
                        echo "  - 标签颜色: " . $tag['color'] . " 【标签显示颜色】\n";
                    }
                }
                
                // 尺寸信息
                if (!empty($listing['dimension_info']) && is_array($listing['dimension_info'])) {
                    echo "  === 商品尺寸信息 ===\n";
                    foreach ($listing['dimension_info'] as $dimension) {
                        if (is_array($dimension)) {
                            echo "  - 项目尺寸: " . ($dimension['item_length'] ?? '0.00') . " x " . ($dimension['item_width'] ?? '0.00') . " x " . ($dimension['item_height'] ?? '0.00') . " " . ($dimension['item_length_units_type'] ?? '') . " 【商品本身尺寸】\n";
                            echo "  - 项目重量: " . ($dimension['item_weight'] ?? '0.00') . " " . ($dimension['item_weight_units_type'] ?? '') . " 【商品本身重量】\n";
                            echo "  - 包装尺寸: " . ($dimension['package_length'] ?? '0.00') . " x " . ($dimension['package_width'] ?? '0.00') . " x " . ($dimension['package_height'] ?? '0.00') . " " . ($dimension['package_length_units_type'] ?? '') . " 【包装后尺寸】\n";
                            echo "  - 包装重量: " . ($dimension['package_weight'] ?? '0.00') . " " . ($dimension['package_weight_units_type'] ?? '') . " 【包装后重量】\n";
                            echo "  - 单位说明: 长度单位(inches/inch/centimeter/yard/foot)，重量单位(pounds/kg/ounce/gram/carat)\n";
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