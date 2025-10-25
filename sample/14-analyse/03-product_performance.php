<?php
/**
 * 产品表现查询示例
 * 
 * 接口说明：
 * - API路径: /bd/productPerformance/openApi/asinList
 * - 请求方式: POST
 * - 令牌桶容量: 1
 * - 唯一键: asin+sid (asin维度)、parent_asin+sid (父asin维度)、seller_sku+sid (msku维度)、sku+sid (sku维度)
 * 
 * 主要功能：
 * 1. 查询产品在指定时间范围内的表现数据
 * 2. 支持多种维度查询（ASIN、父ASIN、MSKU、SKU）
 * 3. 支持多种排序和筛选条件
 * 4. 返回详细的销售、库存、广告、排名等数据
 * 
 * 返回数据包含：
 * - 基本信息：ASIN、父ASIN、标题、分类、品牌等
 * - 销售数据：销量、订单量、销售额、环比数据等
 * - 库存信息：FBA/FBM库存、在途库存等
 * - 广告数据：广告花费、ROAS、CTR、CVR等
 * - 排名评价：大类排名、小类排名、评分、评论数等
 * - 其他信息：负责人、标签、SPU等
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

try {
    // 初始化客户端
    $client = createOpenAPIClient();

    // 生成AccessToken
    echo "正在生成AccessToken...\n";
    $accessTokenDto = $client->generateAccessToken();
    displayAccessTokenInfo($accessTokenDto);

    // 查询产品表现
    echo "正在查询产品表现...\n";
    
    // 请求参数配置
    // 注意：以下参数根据需要取消注释使用
    $params = [
        // === 分页参数 ===
        'offset' => DEFAULT_OFFSET,    // 分页偏移量，默认0
        'length' => DEFAULT_LENGTH,   // 分页长度，最大10000，默认20
        
        // === 排序参数 ===
        'sort_field' => 'volume',      // 排序字段：volume、order_items、amount、volume_chain_ratio等
        'sort_type' => 'desc',         // 排序方式：desc降序、asc升序
        
        // === 搜索参数 ===
        //'search_field' => 'asin',     // 搜索字段：asin、parent_asin、msku、local_sku、item_name
        //'search_value' => ['B085M7NH7K'], // 搜索值，最多50个
        
        // === 站点和店铺 ===
        //'mid' => 1,                    // 站点ID
        'sid' => SAMPLE_SID,           // 店铺ID，单店铺传字符串，多店铺传数组
        
        // === 时间范围（必填）===
        'start_date' => '2024-08-01',  // 开始日期，格式：YYYY-MM-DD
        'end_date' => '2024-08-07',    // 结束日期，格式：YYYY-MM-DD，范围不超过92天
        
        // === 表头筛选 ===
        //'extend_search' => [
        //    [
        //        'field' => 'volume',           // 筛选字段
        //        'from_value' => 0,             // 比较值或左区间值
        //        'to_value' => 1000,            // 右区间值（仅range时使用）
        //        'exp' => 'range'               // 表达式：range、gt、lt、ge、le、eq
        //    ]
        //],
        
        // === 汇总维度 ===
        'summary_field' => 'asin',     // 汇总维度：asin、parent_asin、msku、sku
        
        // === 其他参数 ===
        //'currency_code' => 'CNY',      // 货币类型：USD、CNY，不传代表原币种
        //'is_recently_enum' => true     // 是否仅查询活跃商品：true仅活跃，false全部商品
    ];
    
    // 发起POST请求
    $response = $client->makeRequest('/bd/productPerformance/openApi/asinList', 'POST', $params);
    
    // 输出响应结果
    displayResponseInfo($response);
    
    if ($response['code'] == 0) {
        echo "\n=== 产品表现查询结果 ===\n";
        echo "请求状态: 成功\n";
        echo "请求ID: " . $response['trace_id'] . "\n";
        echo "数据总数: " . $response['data']['total'] . "\n";
        echo "当前页数据量: " . count($response['data']['list']) . "\n";
        echo "环比时间范围: " . $response['data']['chain_start_date'] . " 至 " . $response['data']['chain_end_date'] . "\n";
        echo "可用库存计算公式: " . $response['data']['available_inventory_formula_zh'] . "\n";
        echo "================================\n\n";
        
        if (!empty($response['data']['list'])) {
            foreach ($response['data']['list'] as $index => $product) {
                echo "产品 " . ($index + 1) . ":\n";
                
                // 基本信息
                echo "  === 基本信息 ===\n";
                echo "  - 标题: " . $product['item_name'] . "\n";
                echo "  - 币种符号: " . $product['currency_icon'] . "\n";
                echo "  - 大类排名: " . $product['cate_rank'] . " 【当前排名】\n";
                echo "  - 上次大类排名: " . $product['prev_cate_rank'] . " 【上次排名】\n";
                echo "  - 排名分类: " . ($product['rank_category'] ?: '无') . "\n";
                echo "  - 平均评分: " . $product['avg_star'] . " 【当前评分】\n";
                echo "  - 上次评分: " . $product['prev_star'] . " 【上次评分】\n";
                echo "  - 评论数: " . $product['reviews_count'] . "\n";
                echo "  - 缩略图: " . ($product['small_image_url'] ?: '无') . "\n";
                
                // ASIN信息
                if (!empty($product['asins'])) {
                    echo "  === ASIN信息 ===\n";
                    foreach ($product['asins'] as $asin) {
                        echo "  - ASIN: " . $asin['asin'] . " 【店铺ID: " . $asin['sid'] . "】\n";
                        echo "  - 亚马逊链接: " . $asin['amazon_url'] . "\n";
                    }
                }
                
                // 父ASIN信息
                if (!empty($product['parent_asins'])) {
                    echo "  === 父ASIN信息 ===\n";
                    foreach ($product['parent_asins'] as $parentAsin) {
                        echo "  - 父ASIN: " . $parentAsin['parent_asin'] . " 【店铺ID: " . $parentAsin['sid'] . "】\n";
                        echo "  - 亚马逊链接: " . $parentAsin['amazon_url'] . "\n";
                    }
                }
                
                // 价格信息
                if (!empty($product['price_list'])) {
                    echo "  === 价格信息 ===\n";
                    foreach ($product['price_list'] as $price) {
                        echo "  - 店铺: " . $price['seller_name'] . " 【" . $price['country'] . "】\n";
                        echo "  - MSKU: " . $price['seller_sku'] . "\n";
                        echo "  - SKU: " . $price['local_sku'] . "\n";
                        echo "  - 品名: " . $price['local_name'] . "\n";
                        echo "  - 价格: " . $price['price'] . "\n";
                        echo "  - 销量: " . $price['volume'] . "\n";
                        echo "  - 状态: " . ($price['status'] == 1 ? 'active' : ($price['status'] == 0 ? 'inactive' : 'incomplete')) . "\n";
                        echo "  - 是否删除: " . ($price['is_delete'] == 1 ? '是' : '否') . "\n";
                        echo "  - 缩略图: " . ($price['small_image_url'] ?: '无') . "\n";
                    }
                }
                
                // 销售数据
                echo "  === 销售数据 ===\n";
                echo "  - 销量: " . $product['volume'] . " 【总销量】\n";
                echo "  - 订单量: " . $product['order_items'] . " 【总订单量】\n";
                echo "  - 销售额: " . $product['amount'] . " 【总销售额】\n";
                echo "  - 净销售额: " . $product['net_amount'] . "\n";
                echo "  - 平均销量: " . $product['avg_volume'] . " 【日均销量】\n";
                echo "  - 销售均价: " . ($product['avg_custom_price'] ?: '无') . "\n";
                
                // 环比数据
                echo "  - 环比销量: " . $product['volume_chain'] . "\n";
                echo "  - 销量环比: " . $product['volume_chain_ratio'] . " 【环比比例】\n";
                echo "  - 环比订单量: " . $product['order_items_chain'] . "\n";
                echo "  - 订单量环比: " . $product['order_chain_ratio'] . " 【环比比例】\n";
                echo "  - 环比销售额: " . $product['amount_chain'] . "\n";
                echo "  - 销售额环比: " . $product['amount_chain_ratio'] . " 【环比比例】\n";
                
                // B2B数据
                echo "  - B2B销量: " . $product['b2b_volume'] . "\n";
                echo "  - B2B订单量: " . $product['b2b_order_items'] . "\n";
                echo "  - B2B销售额: " . $product['b2b_amount'] . "\n";
                
                // 促销数据
                echo "  - 促销销量: " . $product['promotion_volume'] . "\n";
                echo "  - 促销订单量: " . $product['promotion_order_items'] . "\n";
                echo "  - 促销销售额: " . $product['promotion_amount'] . "\n";
                echo "  - 促销折扣: " . $product['promotion_discount'] . "\n";
                
                // 利润数据
                echo "  === 利润数据 ===\n";
                echo "  - 结算毛利润: " . $product['gross_profit'] . "\n";
                echo "  - 订单毛利润: " . $product['predict_gross_profit'] . "\n";
                echo "  - 结算毛利率: " . $product['gross_margin'] . "\n";
                echo "  - 订单毛利率: " . $product['predict_gross_margin'] . "\n";
                echo "  - ROI: " . $product['roi'] . "\n";
                
                // 库存数据
                echo "  === 库存数据 ===\n";
                echo "  - FBA可售: " . $product['afn_fulfillable_quantity'] . "\n";
                echo "  - FBA不可售: " . $product['afn_unsellable_quantity'] . "\n";
                echo "  - FBA在途: " . $product['afn_inbound_shipped_quantity'] . "\n";
                echo "  - FBA计划入库: " . $product['afn_inbound_working_quantity'] . "\n";
                echo "  - FBA入库中: " . $product['afn_inbound_receiving_quantity'] . "\n";
                echo "  - 待调仓: " . $product['reserved_fc_transfers'] . "\n";
                echo "  - 调仓中: " . $product['reserved_fc_processing'] . "\n";
                echo "  - 待发货: " . $product['reserved_customerorders'] . "\n";
                echo "  - FBM可售: " . $product['fbm_quantity'] . "\n";
                echo "  - 实际在途: " . $product['stock_up_num'] . "\n";
                echo "  - 可售预估天数: " . $product['available_days'] . "\n";
                echo "  - FBM可售天数: " . $product['fbm_available_days'] . "\n";
                echo "  - 月库销比: " . ($product['month_stock_sales_ratio'] ?: '无') . "\n";
                
                // 广告数据
                echo "  === 广告数据 ===\n";
                echo "  - 广告花费: " . $product['spend'] . "\n";
                echo "  - 广告销售额: " . $product['ad_sales_amount'] . "\n";
                echo "  - 广告订单量: " . $product['ad_order_quantity'] . "\n";
                echo "  - 直接成交销售额: " . $product['ad_direct_sales_amount'] . "\n";
                echo "  - 直接成交订单量: " . $product['ad_direct_order_quantity'] . "\n";
                echo "  - ROAS: " . $product['roas'] . " 【广告销售额/广告花费】\n";
                echo "  - ASoAS: " . $product['asoas'] . " 【广告销售额/总销售额】\n";
                echo "  - ACoAS: " . $product['acoas'] . " 【广告花费/净销售额】\n";
                echo "  - ACOS: " . $product['acos'] . " 【广告花费/广告销售额】\n";
                echo "  - CTR: " . $product['ctr'] . " 【点击量/展示量】\n";
                echo "  - CVR: " . $product['cvr'] . " 【转化率】\n";
                echo "  - 广告CVR: " . $product['ad_cvr'] . "\n";
                echo "  - 销量CVR: " . $product['volume_cvr'] . "\n";
                echo "  - CPC: " . ($product['cpc'] ?: '无') . " 【花费/点击量】\n";
                echo "  - CPO: " . ($product['cpo'] ?: '无') . " 【花费/订单量】\n";
                echo "  - CPM: " . ($product['cpm'] ?: '无') . " 【花费/(1000*展示量)】\n";
                echo "  - 广告订单量占比: " . $product['adv_rate'] . "\n";
                echo "  - 展示量: " . $product['impressions'] . "\n";
                echo "  - 点击量: " . $product['clicks'] . "\n";
                
                // 广告费用明细
                echo "  - SP广告费: " . $product['ads_sp_cost'] . "\n";
                echo "  - SD广告费: " . $product['ads_sd_cost'] . "\n";
                echo "  - SB广告费: " . $product['shared_ads_sb_cost'] . "\n";
                echo "  - SBV广告费: " . $product['shared_ads_sbv_cost'] . "\n";
                echo "  - 差异分摊: " . ($product['shared_cost_of_advertising'] ?: '0.00') . "\n";
                
                // 广告销售额明细
                echo "  - SP广告销售额: " . $product['ads_sp_sales'] . "\n";
                echo "  - SD广告销售额: " . $product['ads_sd_sales'] . "\n";
                echo "  - SB广告销售额: " . $product['shared_ads_sb_sales'] . "\n";
                echo "  - SBV广告销售额: " . $product['shared_ads_sbv_sales'] . "\n";
                
                // 流量数据
                echo "  === 流量数据 ===\n";
                echo "  - Sessions-Total: " . $product['sessions_total'] . "\n";
                echo "  - Sessions-Browser: " . $product['sessions'] . "\n";
                echo "  - Sessions-Mobile: " . $product['sessions_mobile'] . "\n";
                echo "  - PV-Total: " . $product['page_views_total'] . "\n";
                echo "  - PV-Browser: " . $product['page_views'] . "\n";
                echo "  - PV-Mobile: " . $product['page_views_mobile'] . "\n";
                echo "  - Buybox: " . ($product['buy_box_percentage'] ?: '无') . "\n";
                
                // 退款数据
                echo "  === 退款数据 ===\n";
                echo "  - 退款量: " . $product['return_count'] . "\n";
                echo "  - 退款率: " . $product['return_rate'] . "\n";
                echo "  - 退货量: " . $product['return_goods_count'] . "\n";
                echo "  - 退货率: " . $product['return_goods_rate'] . "\n";
                echo "  - 退款金额: " . $product['return_amount'] . "\n";
                echo "  - 留评率: " . ($product['comment_rate'] ?: '无') . "\n";
                
                // 小类排名
                if (!empty($product['small_cate_rank'])) {
                    echo "  === 小类排名 ===\n";
                    foreach ($product['small_cate_rank'] as $rank) {
                        echo "  - 类别: " . $rank['category'] . "\n";
                        echo "  - 排名: " . $rank['rank'] . " 【当前排名】\n";
                        echo "  - 上次排名: " . $rank['prev_rank'] . " 【上次排名】\n";
                    }
                }
                
                // 店铺/国家信息
                if (!empty($product['seller_store_countries'])) {
                    echo "  === 店铺/国家信息 ===\n";
                    foreach ($product['seller_store_countries'] as $store) {
                        echo "  - 店铺: " . $store['seller_name'] . " 【" . $store['country'] . "】\n";
                    }
                }
                
                // 分类和品牌
                if (!empty($product['categories'])) {
                    echo "  - 分类: " . implode(', ', $product['categories']) . "\n";
                }
                if (!empty($product['brands'])) {
                    echo "  - 品牌: " . implode(', ', $product['brands']) . "\n";
                }
                
                // 负责人和开发人
                if (!empty($product['principal_names'])) {
                    echo "  - 负责人: " . implode(', ', $product['principal_names']) . "\n";
                }
                if (!empty($product['developer_names'])) {
                    echo "  - 开发人: " . implode(', ', $product['developer_names']) . "\n";
                }
                
                // SPU信息
                if (!empty($product['spu_spu_names'])) {
                    echo "  === SPU信息 ===\n";
                    foreach ($product['spu_spu_names'] as $spu) {
                        if (!empty($spu['spu'])) {
                            echo "  - SPU: " . $spu['spu'] . " 【" . $spu['spu_name'] . "】\n";
                        }
                    }
                }
                
                // 标签信息
                if (!empty($product['tag_set'])) {
                    echo "  === 标签信息 ===\n";
                    foreach ($product['tag_set'] as $tag) {
                        echo "  - 标签: " . $tag['tag_name'] . " 【颜色: " . $tag['color'] . "】\n";
                    }
                }
                
                // SKU维度特有数据
                if (isset($product['summary_field']) && $product['summary_field'] == 'sku' || !empty($product['sku'])) {
                    echo "  === SKU维度数据 ===\n";
                    echo "  - SKU: " . ($product['sku'] ?: '无') . "\n";
                    echo "  - 品名: " . ($product['local_name'] ?: '无') . "\n";
                    echo "  - 采购成本: " . ($product['cg_price'] ?: '无') . " " . ($product['cg_price_currency_icon'] ?: '') . "\n";
                    echo "  - 可用货值: " . ($product['whs_value'] ?: '无') . " " . ($product['cg_price_currency_icon'] ?: '') . "\n";
                    echo "  - 本地可用: " . ($product['local_quantity'] ?: '无') . "\n";
                    echo "  - 海外仓可用: " . ($product['oversea_quantity'] ?: '无') . "\n";
                    echo "  - 存销比: " . ($product['inventory_sales_ratio'] ?: '无') . "\n";
                    echo "  - 平均售价: " . ($product['avg_landed_price'] ?: '无') . "\n";
                    echo "  - 产品创建时间: " . ($product['product_create_time'] ?: '无') . "\n";
                    
                    if (!empty($product['suppliers'])) {
                        echo "  - 供应商: " . implode(', ', $product['suppliers']) . "\n";
                    }
                    if (!empty($product['model'])) {
                        echo "  - 型号: " . implode(', ', $product['model']) . "\n";
                    }
                }
                
                // 可用库存详情
                if (!empty($product['available_inventory'])) {
                    echo "  === 可用库存详情 ===\n";
                    $inventory = $product['available_inventory'];
                    echo "  - 可用库存总计: " . ($inventory['available_inventory'] ?: '0') . "\n";
                    echo "  - FBA可售: " . ($inventory['afn_fulfillable_quantity'] ?: '0') . "\n";
                    echo "  - FBM库存: " . (isset($inventory['fbm_quantity']) ? $inventory['fbm_quantity'] : '0') . "\n";
                    echo "  - 待调仓: " . ($inventory['reserved_fc_transfers'] ?: '0') . "\n";
                    echo "  - 调仓中: " . ($inventory['reserved_fc_processing'] ?: '0') . "\n";
                    echo "  - 待发货: " . ($inventory['reserved_customerorders'] ?: '0') . "\n";
                    echo "  - 入库中: " . ($inventory['afn_inbound_receiving_quantity'] ?: '0') . "\n";
                    echo "  - 实际在途: " . (isset($inventory['stock_up_num']) ? $inventory['stock_up_num'] : '0') . "\n";
                }
                
                // 其他信息
                echo "  === 其他信息 ===\n";
                echo "  - 店铺ID列表: " . implode(', ', $product['sids']) . "\n";
                echo "  - 运营日志数量: " . $product['icon_num'] . "\n";
                echo "  - 是否有操作日志: " . ($product['has_oprator_log'] ? '是' : '否') . "\n";
                echo "  - FBM买家运费: " . ($product['fbm_buyer_expenses'] ?: '0.00') . "\n";
                echo "  - 积分收入: " . ($product['points_number'] ?: '0') . "\n";
                
                echo "\n";
            }
            echo "共查询到 " . count($response['data']['list']) . " 个产品\n";
        } else {
            echo "暂无产品表现数据\n";
        }
    } else {
        displayErrorInfo($response);
    }

} catch (Exception $e) {
    echo "发生错误: " . $e->getMessage() . "\n";
    echo "错误类型: " . get_class($e) . "\n";
}
