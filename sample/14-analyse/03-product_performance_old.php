<?php
/**
 * 产品表现查询示例（旧版接口）
 * 
 * 接口说明：
 * - API路径: /erp/sc/data/sales_report/asinList
 * - 请求方式: POST
 * - 令牌桶容量: 1
 * - 支持查询系统【统计】>【产品表现】数据
 * 
 * 主要功能：
 * 1. 查询产品在指定时间范围内的表现数据（旧版接口）
 * 2. 支持ASIN和父ASIN两种维度查询
 * 3. 返回详细的销售、库存、广告、排名等数据
 * 
 * 返回数据包含：
 * - 基本信息：ASIN、标题、图片、价格等
 * - 销售数据：销量、订单量、销售额等
 * - 库存信息：FBA各种状态库存
 * - 广告数据：广告花费、CTR、CPC、ACOS等
 * - 排名评价：大类排名、小类排名、评分、评论数等
 * - 流量数据：Sessions、PV、Buybox等
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

    // 查询产品表现（旧版）
    echo "正在查询产品表现（旧版）...\n";
    
    // 请求参数配置
    // 注意：以下参数根据需要取消注释使用
    $params = [
        // === 必填参数 ===
        'sid' => (int)SAMPLE_SID,      // 店铺ID，必填，需要是数字类型
        
        // === 可选参数 ===
        'asin_type' => 0,              // 产品表现维度：0-ASIN，1-父ASIN
        'start_date' => '2025-08-01',  // 报表时间开始，格式：Y-m-d，闭区间
        'end_date' => '2025-08-31',    // 报表时间结束，格式：Y-m-d，开区间
        
        // === 分页参数 ===
        'offset' => DEFAULT_OFFSET,    // 分页偏移量，默认0
        'length' => DEFAULT_LENGTH     // 分页长度，默认1000
    ];
    
    // 发起POST请求
    $response = $client->makeRequest('/erp/sc/data/sales_report/asinList', 'POST', $params);
    
    // 输出响应结果
    displayResponseInfo($response);
    
    if ($response['code'] == 0) {
        echo "\n=== 产品表现查询结果（旧版）===\n";
        echo "请求状态: 成功\n";
        echo "请求ID: " . $response['request_id'] . "\n";
        echo "响应时间: " . $response['response_time'] . "\n";
        echo "数据总数: " . $response['total'] . "\n";
        echo "当前页数据量: " . count($response['data']) . "\n";
        echo "================================\n\n";
        
        if (!empty($response['data'])) {
            foreach ($response['data'] as $index => $product) {
                echo "产品 " . ($index + 1) . ":\n";
                
                // 基本信息
                echo "  === 基本信息 ===\n";
                echo "  - ID: " . $product['id'] . " 【产品ID】\n";
                echo "  - 店铺ID: " . $product['sid'] . " 【店铺唯一标识】\n";
                echo "  - ASIN: " . $product['asin'] . " 【亚马逊标准识别号】\n";
                echo "  - 标题: " . $product['item_name'] . " 【商品标题】\n";
                echo "  - 价格: " . ($product['price'] ?: '无') . " 【商品价格】\n";
                echo "  - 币种: " . ($product['currency_code'] ?: '无') . " 【价格币种】\n";
                echo "  - 商品图片: " . ($product['small_image_url'] ?: '无') . " 【商品缩略图】\n";
                echo "  - 更新时间: " . $product['gmt_modified'] . " 【最后更新时间】\n";
                echo "  - 商品ID: " . $product['pid'] . " 【商品唯一标识】\n";
                echo "  - 种类ID: " . ($product['cid'] ?: '无') . " 【商品分类ID】\n";
                echo "  - 品牌ID: " . ($product['bid'] ?: '无') . " 【品牌ID】\n";
                echo "  - 类别: " . ($product['category'] ?: '无') . " 【商品类别】\n";
                
                // 销售数据
                echo "  === 销售数据 ===\n";
                echo "  - 销量: " . $product['volume'] . " 【总销量】\n";
                echo "  - 订单量: " . $product['order_items'] . " 【总订单量】\n";
                echo "  - 销售额: " . $product['amount'] . " 【总销售额】\n";
                echo "  - 可售天数预估: " . $product['avaiable_days'] . " 【基于当前销量预估的可售天数】\n";
                
                // 库存数据
                echo "  === 库存数据 ===\n";
                echo "  - FBA可售: " . $product['afn_fulfillable_quantity'] . " 【FBA可售库存】\n";
                echo "  - 待调仓: " . $product['reserved_fc_transfers'] . " 【待调仓库存】\n";
                echo "  - 调仓中: " . $product['reserved_fc_processing'] . " 【调仓中库存】\n";
                echo "  - 待发货: " . $product['reserved_customerorders'] . " 【待发货库存】\n";
                echo "  - 在途: " . $product['afn_inbound_shipped_quantity'] . " 【在途库存】\n";
                echo "  - 不可售: " . $product['afn_unsellable_quantity'] . " 【FBA不可售库存】\n";
                echo "  - 入库中: " . $product['afn_inbound_receiving_quantity'] . " 【入库中库存】\n";
                echo "  - 计划入库: " . $product['afn_inbound_working_quantity'] . " 【计划入库库存】\n";
                
                // 广告数据
                echo "  === 广告数据 ===\n";
                echo "  - 广告花费: " . $product['total_spend'] . " 【总广告花费】\n";
                echo "  - 广告销售额: " . $product['sales_amount'] . " 【广告带来的销售额】\n";
                echo "  - 广告订单量: " . $product['order_quantity'] . " 【广告带来的订单量】\n";
                echo "  - CTR: " . $product['ctr'] . " 【点击率：点击量/展示量】\n";
                echo "  - CPC: " . $product['avg_cpc'] . " 【平均点击成本】\n";
                echo "  - ACOS: " . $product['acos'] . " 【广告花费/广告销售额】\n";
                echo "  - ACoAS: " . ($product['acoas'] ?: '无') . " 【广告花费/总销售额】\n";
                echo "  - ASoAS: " . ($product['asoas'] ?: '无') . " 【广告销售额/总销售额】\n";
                echo "  - 广告CVR: " . $product['ad_cvr'] . " 【广告转化率】\n";
                echo "  - 广告订单量占比: " . ($product['adv_rate'] ?: '无') . " 【广告订单量占总订单量的比例】\n";
                echo "  - 总转化率: " . $product['total_spend_rate'] . " 【总转化率】\n";
                
                // 流量数据
                echo "  === 流量数据 ===\n";
                echo "  - Sessions-Total: " . ($product['sessionsTotal'] ?? $product['sessions_total'] ?? '0') . " 【总会话数】\n";
                echo "  - Sessions-Browser: " . ($product['sessionsBrowser'] ?? $product['sessions_browser'] ?? '0') . " 【浏览器会话数】\n";
                echo "  - Sessions-Mobile: " . ($product['sessionsMobile'] ?? $product['sessions_mobile'] ?? '0') . " 【移动端会话数】\n";
                echo "  - PV-Total: " . ($product['PVTotal'] ?? $product['page_views_total'] ?? '0') . " 【总页面浏览量】\n";
                echo "  - PV-Browser: " . ($product['PVBrowser'] ?? $product['page_views_browser'] ?? '0') . " 【浏览器页面浏览量】\n";
                echo "  - PV-Mobile: " . ($product['PVMobile'] ?? $product['page_views_mobile'] ?? '0') . " 【移动端页面浏览量】\n";
                echo "  - Buybox: " . $product['buy_box_percentage'] . " 【Buybox占有率】\n";
                echo "  - 点击量: " . $product['clicks'] . " 【广告点击量】\n";
                echo "  - 展示量: " . $product['impressions'] . " 【广告展示量】\n";
                echo "  - 转化率: " . $product['conversion_rate'] . " 【整体转化率】\n";
                
                // 排名与评价
                echo "  === 排名与评价 ===\n";
                echo "  - 大类排名: " . ($product['rank'] ?: '无') . " 【商品在大类中的排名】\n";
                echo "  - 评论数: " . $product['reviews'] . " 【客户评论数量】\n";
                echo "  - 平均评分: " . ($product['avg_star'] ?: '无') . " 【平均星级评分】\n";
                
                // 小类排名
                if (!empty($product['smallRankList'])) {
                    echo "  === 小类排名详情 ===\n";
                    foreach ($product['smallRankList'] as $rank) {
                        echo "  - 小类: " . $rank['smallRankName'] . " 【小类名称】\n";
                        echo "  - 排名: " . $rank['rankValue'] . " 【该小类中的排名】\n";
                    }
                }
                
                // ASIN备注
                if (!empty($product['remark'])) {
                    echo "  === ASIN备注 ===\n";
                    foreach ($product['remark'] as $remark) {
                        echo "  - 日期: " . $remark['date'] . " 【备注日期】\n";
                        echo "  - 内容: " . $remark['content'] . " 【备注内容】\n";
                    }
                }
                
                echo "\n";
            }
            echo "共查询到 " . count($response['data']) . " 个产品\n";
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
