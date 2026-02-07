<?php
/**
 * 查询TEMU半托管店铺订单（最近7天）
 * 
 * 功能说明：
 * 1. 获取TEMU半托管店铺列表
 * 2. 查询最近7天的所有订单（所有状态）
 * 3. 统计总数量和显示明细
 * 4. 详细展示一条订单的所有字段说明
 * @doc https://apidoc.lingxing.com/#/docs/MultiPlatform/V2/MultiPlatOrderV2
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

/**
 * 详细展示订单的所有字段说明
 */
function displayOrderDetailExplanation($order) {
    echo "订单字段详细说明:\n";
    echo str_repeat("-", 80) . "\n\n";
    
    // 基本信息
    echo "【基本信息】\n";
    echo "  store_id: " . ($order['store_id'] ?? '无') . " - 店铺ID，多平台店铺唯一标识\n";
    echo "  global_order_no: " . ($order['global_order_no'] ?? '无') . " - 系统单号，系统本地唯一订单号\n";
    echo "  order_from_name: " . ($order['order_from_name'] ?? '无') . " - 订单来源（如：线上订单）\n";
    echo "  wid: " . ($order['wid'] ?? '无') . " - 仓库ID\n";
    echo "  reference_no: " . ($order['reference_no'] ?? '无') . " - 平台参考号\n";
    echo "  amount_currency: " . ($order['amount_currency'] ?? '无') . " - 币种（如：USD、EUR、CNY）\n";
    echo "  remark: " . ($order['remark'] ?? '无') . " - 客服备注\n";
    echo "\n";
    
    // 订单状态
    echo "【订单状态】\n";
    $statusMap = [
        1 => '同步中',
        2 => '已同步',
        3 => '待付款',
        4 => '待审核',
        5 => '待发货',
        6 => '已发货',
        7 => '已取消/不发货',
        8 => '不显示',
        9 => '平台发货'
    ];
    $status = $order['status'] ?? 0;
    $statusText = $statusMap[$status] ?? '未知状态(' . $status . ')';
    echo "  status: " . $status . " - 系统订单状态（" . $statusText . "）\n";
    echo "  split_type: " . ($order['split_type'] ?? '无') . " - 拆分单类型（1-原始单，2-合并单，3-拆分单）\n";
    echo "  delivery_type: " . ($order['delivery_type'] ?? '无') . " - 发货方式（1-混合方式，2-自发货，3-平台发货）\n";
    echo "\n";
    
    // 时间信息
    echo "【时间信息】\n";
    echo "  update_time: " . ($order['update_time'] ?? '无') . " - 订单更新时间（字符串格式）\n";
    if (!empty($order['global_purchase_time'])) {
        echo "  global_purchase_time: " . $order['global_purchase_time'] . " (" . date('Y-m-d H:i:s', $order['global_purchase_time']) . ") - 订购时间（时间戳，秒）\n";
    }
    if (!empty($order['global_payment_time'])) {
        echo "  global_payment_time: " . $order['global_payment_time'] . " (" . date('Y-m-d H:i:s', $order['global_payment_time']) . ") - 付款时间（时间戳，秒）\n";
    }
    if (!empty($order['global_review_time'])) {
        echo "  global_review_time: " . $order['global_review_time'] . " (" . date('Y-m-d H:i:s', $order['global_review_time']) . ") - 审核时间（时间戳，秒）\n";
    }
    if (!empty($order['global_distribution_time'])) {
        echo "  global_distribution_time: " . $order['global_distribution_time'] . " (" . date('Y-m-d H:i:s', $order['global_distribution_time']) . ") - 配货时间（时间戳，秒）\n";
    }
    if (!empty($order['global_delivery_time'])) {
        echo "  global_delivery_time: " . $order['global_delivery_time'] . " (" . date('Y-m-d H:i:s', $order['global_delivery_time']) . ") - 发货时间（时间戳，秒）\n";
    }
    if (!empty($order['global_print_time'])) {
        echo "  global_print_time: " . $order['global_print_time'] . " (" . date('Y-m-d H:i:s', $order['global_print_time']) . ") - 打单时间（时间戳，秒）\n";
    }
    if (!empty($order['global_mark_time'])) {
        echo "  global_mark_time: " . $order['global_mark_time'] . " (" . date('Y-m-d H:i:s', $order['global_mark_time']) . ") - 标发时间（时间戳，秒）\n";
    }
    if (!empty($order['global_cancel_time'])) {
        echo "  global_cancel_time: " . $order['global_cancel_time'] . " (" . date('Y-m-d H:i:s', $order['global_cancel_time']) . ") - 取消时间（时间戳，秒）\n";
    }
    echo "  global_latest_ship_time: " . ($order['global_latest_ship_time'] ?? '无') . " - 发货时限\n";
    echo "\n";
    
    // 平台单信息
    if (!empty($order['platform_info']) && is_array($order['platform_info'])) {
        echo "【平台单信息】\n";
        foreach ($order['platform_info'] as $idx => $platformInfo) {
            echo "  平台单 " . ($idx + 1) . ":\n";
            echo "    platform_order_no: " . ($platformInfo['platform_order_no'] ?? '无') . " - 平台订单号\n";
            echo "    platform_order_name: " . ($platformInfo['platform_order_name'] ?? '无') . " - 平台订单编号（特定平台使用，如Shopify）\n";
            echo "    platform_code: " . ($platformInfo['platform_code'] ?? '无') . " - 平台CODE（10024=TEMU半托管）\n";
            echo "    store_Country_code: " . ($platformInfo['store_Country_code'] ?? '无') . " - 订单国家（二位ISO代码，如US、DE）\n";
            echo "    order_from: " . ($platformInfo['order_from'] ?? '无') . " - 订单来源\n";
            echo "    status: " . ($platformInfo['status'] ?? '无') . " - 平台单状态\n";
            echo "    payment_status: " . ($platformInfo['payment_status'] ?? '无') . " - 平台单支付状态\n";
            echo "    shipping_status: " . ($platformInfo['shipping_status'] ?? '无') . " - 平台单发货状态\n";
            if (!empty($platformInfo['purchase_time'])) {
                echo "    purchase_time: " . $platformInfo['purchase_time'] . " (" . date('Y-m-d H:i:s', $platformInfo['purchase_time']) . ") - 订购时间（时间戳，秒）\n";
            }
            if (!empty($platformInfo['payment_time'])) {
                echo "    payment_time: " . $platformInfo['payment_time'] . " (" . date('Y-m-d H:i:s', $platformInfo['payment_time']) . ") - 支付时间（时间戳，秒）\n";
            }
            if (!empty($platformInfo['delivery_time'])) {
                echo "    delivery_time: " . $platformInfo['delivery_time'] . " (" . date('Y-m-d H:i:s', $platformInfo['delivery_time']) . ") - 平台发货时间（时间戳，秒）\n";
            }
            if (!empty($platformInfo['latest_ship_time'])) {
                echo "    latest_ship_time: " . $platformInfo['latest_ship_time'] . " (" . date('Y-m-d H:i:s', $platformInfo['latest_ship_time']) . ") - 最后发货时间（时间戳，秒）\n";
            }
            if (!empty($platformInfo['cancel_time'])) {
                echo "    cancel_time: " . $platformInfo['cancel_time'] . " (" . date('Y-m-d H:i:s', $platformInfo['cancel_time']) . ") - 取消单时间（时间戳，秒）\n";
            }
        }
        echo "\n";
    }
    
    // 商品信息
    if (!empty($order['item_info']) && is_array($order['item_info'])) {
        echo "【商品信息】\n";
        echo "  商品数量: " . count($order['item_info']) . "\n";
        foreach ($order['item_info'] as $idx => $item) {
            echo "  商品 " . ($idx + 1) . ":\n";
            echo "    id: " . ($item['id'] ?? '无') . " - 系统商品唯一id\n";
            echo "    local_product_name: " . ($item['local_product_name'] ?? '无') . " - 品名\n";
            echo "    title: " . ($item['title'] ?? '无') . " - 商品标题\n";
            echo "    local_sku: " . ($item['local_sku'] ?? '无') . " - SKU（本地SKU）\n";
            echo "    msku: " . ($item['msku'] ?? '无') . " - MSKU（卖家SKU）\n";
            echo "    product_no: " . ($item['product_no'] ?? '无') . " - 平台商品ID（亚马逊返回ASIN，多平台返回平台商品id）\n";
            echo "    order_item_no: " . ($item['order_item_no'] ?? '无') . " - 订单明细单号\n";
            echo "    platform_order_no: " . ($item['platform_order_no'] ?? '无') . " - 平台单号（商品级别）\n";
            echo "    quantity: " . ($item['quantity'] ?? '0') . " - 数量\n";
            echo "    variant_attr: " . ($item['variant_attr'] ?? '无') . " - 变体属性（如：Black、Size-L等）\n";
            echo "    item_from_name: " . ($item['item_from_name'] ?? '无') . " - 商品来源（1-线上，2-本地，3-本地自定义）\n";
            echo "    unit_price_amount: " . ($item['unit_price_amount'] ?? '0.00') . " - 单价（币种：" . ($order['amount_currency'] ?? '') . "）\n";
            echo "    item_price_amount: " . ($item['item_price_amount'] ?? '0.00') . " - 商品金额（币种：" . ($order['amount_currency'] ?? '') . "）\n";
            echo "    discount_amount: " . ($item['discount_amount'] ?? '0.00') . " - 折扣（币种：" . ($order['amount_currency'] ?? '') . "）\n";
            echo "    tax_amount: " . ($item['tax_amount'] ?? '0.00') . " - 商品税费（币种：" . ($order['amount_currency'] ?? '') . "）\n";
            echo "    transaction_fee_amount: " . ($item['transaction_fee_amount'] ?? '0.00') . " - 商品交易费（币种：" . ($order['amount_currency'] ?? '') . "）\n";
            echo "    sales_revenue_amount: " . ($item['sales_revenue_amount'] ?? '0.00') . " - 销售收益\n";
            echo "    customer_shipping_amount: " . ($item['customer_shipping_amount'] ?? '0.00') . " - 客付运费（币种：" . ($order['amount_currency'] ?? '') . "）\n";
            echo "    customer_tip_amount: " . ($item['customer_tip_amount'] ?? '0.00') . " - Shopify小费（币种：" . ($order['amount_currency'] ?? '') . "）\n";
            echo "    cod_amount: " . ($item['cod_amount'] ?? '0.00') . " - COD费用（币种：" . ($order['amount_currency'] ?? '') . "）\n";
            echo "    gift_wrap_amount: " . ($item['gift_wrap_amount'] ?? '0.00') . " - 礼品包装费（币种：" . ($order['amount_currency'] ?? '') . "）\n";
            echo "    platform_tax_amount: " . ($item['platform_tax_amount'] ?? '0.00') . " - 销售税（币种：" . ($order['amount_currency'] ?? '') . "）\n";
            echo "    points_granted_amount: " . ($item['points_granted_amount'] ?? '0.00') . " - 积分成本（币种：" . ($order['amount_currency'] ?? '') . "）\n";
            echo "    other_amount: " . ($item['other_amount'] ?? '0.00') . " - 平台其他费用（币种：" . ($order['amount_currency'] ?? '') . "）\n";
            echo "    other_fee: " . ($item['other_fee'] ?? '0.00') . " - 其他费用（用户手动导入，有正有负）\n";
            echo "    platform_subsidy_amount: " . ($item['platform_subsidy_amount'] ?? '无') . " - 平台补贴（目前仅TikTok、Shopee支持）\n";
            echo "    cg_price_amount: " . ($item['cg_price_amount'] ?? '0.00') . " - 采购成本（币种：CNY，取自产品管理固定值）\n";
            echo "    stock_cost_amount: " . ($item['stock_cost_amount'] ?? '0.00') . " - 库存明细成本（币种：CNY，取自订单创建时库存明细计算成本）\n";
            echo "    wms_outbound_cost_amount: " . ($item['wms_outbound_cost_amount'] ?? '0.00') . " - 实际出库成本（币种：CNY，取自销售出库单出库成本）\n";
            echo "    shipping_amount: " . ($item['shipping_amount'] ?? '0.00') . " - 预估运费（币种：CNY）\n";
            echo "    wms_shipping_price_amount: " . ($item['wms_shipping_price_amount'] ?? '0.00') . " - 实际运费（币种：CNY，取自销售出库单运费）\n";
            echo "    stock_deduct_id: " . ($item['stock_deduct_id'] ?? '无') . " - 备货店铺ID\n";
            echo "    stock_deduct_name: " . ($item['stock_deduct_name'] ?? '无') . " - 备货店铺名称\n";
            echo "    platform_status: " . ($item['platform_status'] ?? '无') . " - 平台订单商品状态\n";
            echo "    type: " . ($item['type'] ?? '无') . " - 商品类型\n";
            echo "    is_bundled: " . ($item['is_bundled'] ?? '0') . " - 是否为捆绑产品（1-是，0-否）\n";
            echo "    remark: " . ($item['remark'] ?? '无') . " - 商品备注\n";
            if (!empty($item['customized_url'])) {
                echo "    customized_url: " . $item['customized_url'] . " - 亚马逊定制商品文件下载链接\n";
            }
            if (!empty($item['sub_products']) && is_array($item['sub_products'])) {
                echo "    sub_products: " . json_encode($item['sub_products'], JSON_UNESCAPED_UNICODE) . " - 子产品（捆绑产品时使用）\n";
            }
            if (!empty($item['delivery_time'])) {
                echo "    delivery_time: " . $item['delivery_time'] . " - 平台发货时间（当前仅支持Shopify平台）\n";
            }
        }
        echo "\n";
    }
    
    // 交易信息
    if (!empty($order['transaction_info']) && is_array($order['transaction_info'])) {
        echo "【交易信息】\n";
        $transaction = $order['transaction_info'][0] ?? [];
        echo "  order_total_amount: " . ($transaction['order_total_amount'] ?? '0.00') . " - 订单总额（币种：" . ($order['amount_currency'] ?? '') . "）\n";
        echo "  order_item_amount: " . ($transaction['order_item_amount'] ?? '0.00') . " - 商品金额（币种：" . ($order['amount_currency'] ?? '') . "）\n";
        echo "  discount_amount: " . ($transaction['discount_amount'] ?? '0.00') . " - 折扣（币种：" . ($order['amount_currency'] ?? '') . "）\n";
        echo "  customer_shipping_amount: " . ($transaction['customer_shipping_amount'] ?? '0.00') . " - 客付运费（币种：" . ($order['amount_currency'] ?? '') . "）\n";
        echo "  customer_tax_amount_show: " . ($transaction['customer_tax_amount_show'] ?? '0.00') . " - 客付税费（币种：" . ($order['amount_currency'] ?? '') . "）\n";
        echo "  customer_tip_amount: " . ($transaction['customer_tip_amount'] ?? '0.00') . " - 小费（币种：" . ($order['amount_currency'] ?? '') . "）\n";
        echo "  transaction_fee_amount: " . ($transaction['transaction_fee_amount'] ?? '0.00') . " - 交易费（币种：" . ($order['amount_currency'] ?? '') . "）\n";
        echo "  other_amount: " . ($transaction['other_amount'] ?? '0.00') . " - 平台其他费\n";
        echo "  cod_amount: " . ($transaction['cod_amount'] ?? '0.00') . " - COD费用（币种：" . ($order['amount_currency'] ?? '') . "）\n";
        echo "  gift_wrap_amount: " . ($transaction['gift_wrap_amount'] ?? '0.00') . " - 礼品包装费（币种：" . ($order['amount_currency'] ?? '') . "）\n";
        echo "  platform_tax_amount: " . ($transaction['platform_tax_amount'] ?? '0.00') . " - 销售税（币种：" . ($order['amount_currency'] ?? '') . "）\n";
        echo "  points_granted_amount: " . ($transaction['points_granted_amount'] ?? '0.00') . " - 积分成本（币种：" . ($order['amount_currency'] ?? '') . "）\n";
        echo "  other_fee: " . ($transaction['other_fee'] ?? '0.00') . " - 其他费用（用户手动导入，有正有负）\n";
        echo "  platform_subsidy_amount: " . ($transaction['platform_subsidy_amount'] ?? '无') . " - 平台补贴（目前仅TikTok、Shopee支持）\n";
        echo "  cg_price_amount: " . ($transaction['cg_price_amount'] ?? '0.00') . " - 采购成本（币种：CNY，取自产品管理固定值）\n";
        echo "  stock_cost_amount: " . ($transaction['stock_cost_amount'] ?? '0.00') . " - 库存明细成本（币种：CNY，取自订单创建时库存明细计算成本）\n";
        echo "  wms_outbound_cost_amount: " . ($transaction['wms_outbound_cost_amount'] ?? '0.00') . " - 实际出库成本（币种：CNY，取自销售出库单出库成本）\n";
        echo "  outbound_cost_amount: " . ($transaction['outbound_cost_amount'] ?? '0.00') . " - 预估出库成本（币种：CNY）\n";
        echo "  pre_cost_amount: " . ($transaction['pre_cost_amount'] ?? '0.00') . " - 预估运费（币种：CNY）\n";
        echo "  wms_shipping_price_amount: " . ($transaction['wms_shipping_price_amount'] ?? '0.00') . " - 实际运费（币种：CNY，取自销售出库单出库运费）\n";
        echo "  profit_amount: " . ($transaction['profit_amount'] ?? '0.00') . " - 预估毛利润（币种：" . ($order['amount_currency'] ?? '') . "）\n";
        echo "\n";
    }
    
    // 物流信息
    if (!empty($order['logistics_info'])) {
        echo "【物流信息】\n";
        $logistics = $order['logistics_info'];
        $logisticsStatusMap = [
            0 => '待物流下单',
            1 => '物流下单中',
            2 => '成功',
            3 => '失败',
            4 => '已取消'
        ];
        $logisticsStatus = $logistics['status'] ?? 0;
        $logisticsStatusText = $logisticsStatusMap[$logisticsStatus] ?? '未知';
        echo "  status: " . $logisticsStatus . " - 物流状态（" . $logisticsStatusText . "）\n";
        echo "  logistics_type_id: " . ($logistics['logistics_type_id'] ?? '无') . " - 物流方式id\n";
        echo "  logistics_type_name: " . ($logistics['logistics_type_name'] ?? '无') . " - 物流方式名称\n";
        echo "  logistics_provider_id: " . ($logistics['logistics_provider_id'] ?? '无') . " - 物流商id\n";
        echo "  logistics_provider_name: " . ($logistics['logistics_provider_name'] ?? '无') . " - 物流商名称\n";
        echo "  actual_carrier: " . ($logistics['actual_carrier'] ?? '无') . " - 实际承运人\n";
        echo "  waybill_no: " . ($logistics['waybill_no'] ?? '无') . " - 物流商返回的物流单号\n";
        echo "  tracking_no: " . ($logistics['tracking_no'] ?? '无') . " - 跟踪号\n";
        if (!empty($logistics['logistics_time'])) {
            echo "  logistics_time: " . $logistics['logistics_time'] . " (" . date('Y-m-d H:i:s', $logistics['logistics_time']) . ") - 物流下单成功时间（时间戳，秒）\n";
        }
        echo "  pre_weight: " . ($logistics['pre_weight'] ?? '0.00') . " - 预估重量（g）\n";
        echo "  weight: " . ($logistics['weight'] ?? '0.00') . " - 实际重量（g）\n";
        echo "  weight_unit: " . ($logistics['weight_unit'] ?? '无') . " - 包裹实际重量单位\n";
        echo "  pre_fee_weight: " . ($logistics['pre_fee_weight'] ?? '0.00') . " - 预估计费重量\n";
        echo "  pre_fee_weight_unit: " . ($logistics['pre_fee_weight_unit'] ?? '无') . " - 预估计费重单位\n";
        echo "  pkg_fee_weight: " . ($logistics['pkg_fee_weight'] ?? '0.00') . " - 实际计费重量\n";
        echo "  pkg_fee_weight_unit: " . ($logistics['pkg_fee_weight_unit'] ?? '无') . " - 实际计费重单位\n";
        echo "  pre_pkg_length: " . ($logistics['pre_pkg_length'] ?? '0.00') . " - 预估包裹长（cm）\n";
        echo "  pre_pkg_width: " . ($logistics['pre_pkg_width'] ?? '0.00') . " - 预估包裹宽（cm）\n";
        echo "  pre_pkg_height: " . ($logistics['pre_pkg_height'] ?? '0.00') . " - 预估包裹高（cm）\n";
        echo "  pkg_length: " . ($logistics['pkg_length'] ?? '0.00') . " - 实际包裹长\n";
        echo "  pkg_width: " . ($logistics['pkg_width'] ?? '0.00') . " - 实际包裹宽\n";
        echo "  pkg_height: " . ($logistics['pkg_height'] ?? '0.00') . " - 实际包裹高\n";
        echo "  pkg_size_unit: " . ($logistics['pkg_size_unit'] ?? '无') . " - 包裹尺寸单位\n";
        echo "  cost_currency_code: " . ($logistics['cost_currency_code'] ?? '无') . " - 物流实际运费币种code\n";
        echo "  pre_cost_amount: " . ($logistics['pre_cost_amount'] ?? '0.00') . " - 预估运费\n";
        echo "  cost_amount: " . ($logistics['cost_amount'] ?? '0.00') . " - 物流实际运费\n";
        echo "\n";
    }
    
    // 订单标签
    if (!empty($order['order_tag']) && is_array($order['order_tag'])) {
        echo "【订单标签】\n";
        foreach ($order['order_tag'] as $idx => $tag) {
            echo "  标签 " . ($idx + 1) . ":\n";
            echo "    tag_name: " . ($tag['tag_name'] ?? '无') . " - 标签名称\n";
            echo "    tag_no: " . ($tag['tag_no'] ?? '无') . " - 标签编号\n";
            $tagTypeMap = [
                1 => '自定义处理类型',
                2 => '自定义订单标签',
                3 => '系统处理类型',
                4 => '系统订单标签'
            ];
            $tagType = $tag['tag_type'] ?? '';
            $tagTypeText = $tagTypeMap[$tagType] ?? '未知类型(' . $tagType . ')';
            echo "    tag_type: " . $tagType . " - 标签类型（" . $tagTypeText . "）\n";
        }
        echo "\n";
    }
    
    // 异常类型标签
    if (!empty($order['exception_order_tag']) && is_array($order['exception_order_tag'])) {
        echo "【异常类型标签】\n";
        foreach ($order['exception_order_tag'] as $tag) {
            echo "  - " . ($tag['tag_name'] ?? '无') . "\n";
        }
        echo "\n";
    }
    
    // 待办类型标签
    if (!empty($order['pending_order_tag']) && is_array($order['pending_order_tag'])) {
        echo "【待办类型标签】\n";
        foreach ($order['pending_order_tag'] as $tag) {
            echo "  - " . ($tag['tag_name'] ?? '无') . "\n";
        }
        echo "\n";
    }
    
    // 其他字段
    echo "【其他字段】\n";
    if (!empty($order['original_global_order_no'])) {
        echo "  original_global_order_no: " . json_encode($order['original_global_order_no'], JSON_UNESCAPED_UNICODE) . " - 补发订单原系统单号（数组）\n";
    }
    if (!empty($order['customer_shipping_list'])) {
        echo "  customer_shipping_list: " . json_encode($order['customer_shipping_list'], JSON_UNESCAPED_UNICODE) . " - 客选物流列表（数组）\n";
    }
    if (!empty($order['supplier_id'])) {
        echo "  supplier_id: " . $order['supplier_id'] . " - Wayfair平台对订单指定的发货仓库ID\n";
    }
}

try {
    // 初始化客户端
    $client = createOpenAPIClient();

    // 生成AccessToken
    echo "正在生成AccessToken...\n";
    $accessTokenDto = $client->generateAccessToken();
    displayAccessTokenInfo($accessTokenDto);

    // 第一步：获取TEMU半托管店铺列表
    echo "\n=== 第一步：获取TEMU半托管店铺列表 ===\n";
    $sellerParams = [
        'offset' => 0,
        'length' => 200,
        'platform_code' => [10024],  // TEMU半托管平台code
        'is_sync' => 1,              // 只查询启用的店铺
        'status' => 1                 // 只查询正常授权的店铺
    ];
    
    $sellerResponse = $client->makeRequest('/pb/mp/shop/v2/getSellerList', 'POST', $sellerParams);
    
    if ($sellerResponse['code'] != 0) {
        throw new Exception("获取店铺列表失败: " . $sellerResponse['message']);
    }
    
    $stores = $sellerResponse['data']['list'] ?? [];
    echo "找到 " . count($stores) . " 个TEMU半托管店铺\n\n";
    
    if (empty($stores)) {
        echo "没有找到TEMU半托管店铺，请检查店铺配置\n";
        exit;
    }
    
    // 第二步：查询指定平台订单号的所有信息
    $targetOrderNo = 'PO-069-07835843881590031';  // 要查询的平台订单号
    echo "=== 第二步：查询指定订单信息 ===\n";
    echo "平台订单号: " . $targetOrderNo . "\n\n";
    
    // 初始化变量
    $targetOrder = null;
    $totalOrders = 0;
    $allOrders = [];
    
    // 收集所有店铺ID
    $storeIds = [];
    foreach ($stores as $store) {
        $storeIds[] = $store['store_id'];
    }
    
    // 构建查询参数（根据接口文档）
    // 使用 platform_order_nos 参数查询指定订单
    // 注意：当传入平台单号查询时，时间参数可不必传
    $orderParams = [
        'offset' => 0,
        'length' => 500,  // 上限500
        'store_id' => $storeIds,  // 店铺ID数组
        'platform_code' => ['10024'],  // TEMU半托管平台code
        'platform_order_nos' => [$targetOrderNo],  // 平台订单号列表
        'include_delete' => false  // 不包含已删除订单
    ];
    
    echo "查询参数: store_id=" . count($storeIds) . "个店铺, platform_code=10024(TEMU半托管), platform_order_nos=" . $targetOrderNo . "\n\n";
    
    try {
        $orderResponse = $client->makeRequest('/pb/mp/order/v2/list', 'POST', $orderParams);
        
        if ($orderResponse['code'] == 0) {
            $orders = $orderResponse['data']['list'] ?? [];
            $totalOrders = (int)($orderResponse['data']['total'] ?? count($orders));
            
            echo "查询成功！\n";
            echo "找到订单数: " . $totalOrders . "\n\n";
            
            if ($totalOrders > 0 && !empty($orders)) {
                // 找到匹配的订单
                foreach ($orders as $order) {
                    // 检查平台订单号是否匹配
                    $matched = false;
                    if (!empty($order['platform_info']) && is_array($order['platform_info'])) {
                        foreach ($order['platform_info'] as $platformInfo) {
                            $platformOrderNo = $platformInfo['platform_order_no'] ?? '';
                            $platformOrderName = $platformInfo['platform_order_name'] ?? '';
                            
                            // 检查订单号是否匹配（可能是 platform_order_no 或 platform_order_name）
                            if ($platformOrderNo == $targetOrderNo || $platformOrderName == $targetOrderNo) {
                                $matched = true;
                                break;
                            }
                        }
                    }
                    
                    // 也检查商品级别的平台订单号
                    if (!$matched && !empty($order['item_info']) && is_array($order['item_info'])) {
                        foreach ($order['item_info'] as $item) {
                            $itemPlatformOrderNo = $item['platform_order_no'] ?? '';
                            if ($itemPlatformOrderNo == $targetOrderNo) {
                                $matched = true;
                                break;
                            }
                        }
                    }
                    
                    if ($matched) {
                        // 找到对应的店铺名称
                        $storeName = '';
                        foreach ($stores as $store) {
                            if ($store['store_id'] == $order['store_id']) {
                                $storeName = $store['store_name'];
                                break;
                            }
                        }
                        $order['store_name'] = $storeName;
                        $targetOrder = $order;
                        $allOrders[] = $order;
                        break;
                    }
                }
                
                if ($targetOrder === null) {
                    echo "警告：未找到匹配的订单号 " . $targetOrderNo . "\n";
                    echo "但查询返回了 " . $totalOrders . " 个订单，可能是订单号格式不匹配\n\n";
                }
            } else {
                echo "未找到订单号: " . $targetOrderNo . "\n\n";
            }
        } else {
            echo "查询失败，错误信息: " . ($orderResponse['message'] ?? '未知错误') . "\n";
            if (!empty($orderResponse['error_details'])) {
                echo "错误详情: " . json_encode($orderResponse['error_details'], JSON_UNESCAPED_UNICODE) . "\n";
            }
            throw new Exception("查询失败: " . ($orderResponse['message'] ?? '未知错误'));
        }
    } catch (Exception $e) {
        echo "查询订单时发生错误: " . $e->getMessage() . "\n";
        echo "提示：请检查参数格式是否正确\n";
    }
    
    // 第三步：显示指定订单的详细信息
    echo "=== 第三步：订单详细信息 ===\n";
    echo "订单号: " . $targetOrderNo . "\n";
    echo str_repeat("=", 80) . "\n\n";
    
    if ($targetOrder !== null) {
        // 详细展示订单的所有字段说明
        displayOrderDetailExplanation($targetOrder);
    } else {
        echo "未找到订单号: " . $targetOrderNo . " 的订单信息\n";
        echo "请检查订单号是否正确，或者订单是否存在于系统中\n";
    }

} catch (Exception $e) {
    echo "发生错误: " . $e->getMessage() . "\n";
    echo "错误类型: " . get_class($e) . "\n";
    echo "\n提示：\n";
    echo "1. 请检查订单查询接口路径是否正确\n";
    echo "2. 请检查请求参数格式是否正确\n";
    echo "3. 请参考API文档确认正确的接口路径和参数\n";
}
