# 亚马逊订单查询示例

## 接口信息
- **API路径**: `/erp/sc/data/mws/orders`
- **请求协议**: HTTPS
- **请求方式**: POST
- **令牌桶容量**: 1

## 主要功能
1. 查询亚马逊店铺的订单数据
2. 支持多种筛选条件（订单状态、配送方式、时间范围等）
3. 支持分页查询
4. 返回详细的订单信息、商品信息、时间信息等

## 请求参数说明

| 参数名 | 说明 | 必填 | 类型 | 示例 |
|--------|------|------|------|------|
| sid | 店铺id | 否 | int | 113 |
| sid_list | 店铺id列表，最大长度20 | 否 | array | [113,112] |
| start_date | 查询时间开始，左闭右开 | 是 | string | 2022-04-18 11:23:47 |
| end_date | 查询时间结束，左闭右开 | 是 | string | 2022-05-18 11:23:47 |
| date_type | 查询日期类型 | 否 | int | 1 |
| order_status | 订单状态 | 否 | array | ["Pending"] |
| sort_desc_by_date_type | 是否按查询日期类型排序 | 否 | int | 1 |
| fulfillment_channel | 配送方式 | 否 | int | 1 |
| offset | 分页偏移量 | 否 | int | 0 |
| length | 分页长度 | 否 | int | 1000 |

### date_type 说明
- 1: 订购时间【站点时间】
- 2: 订单修改时间【北京时间】
- 3: 平台更新时间【UTC时间】
- 10: 发货时间【站点时间】

### order_status 说明
- Pending: 待处理
- Unshipped: 未发货
- PartiallyShipped: 部分发货
- Shipped: 已发货
- Canceled: 取消

### fulfillment_channel 说明
- 1: 亚马逊订单-AFN
- 2: 自发货-MFN

## 返回数据说明

### 基本信息
- sid: 店铺id
- seller_name: 店铺名称
- amazon_order_id: 亚马逊订单号
- order_status: 订单状态
- order_total_amount: 订单金额
- fulfillment_channel: 配送方式
- postal_code: 邮编
- tracking_number: 物流运单号

### 订单状态信息
- is_return: 退款状态（0-未退款，1-退款中，2-退款完成）
- is_mcf_order: 是否多渠道订单
- is_assessed: 是否推广订单
- is_replaced_order: 是否换货订单
- is_replacement_order: 是否已换货订单
- is_return_order: 是否退货订单
- refund_amount: 退款金额

### 时间信息
- purchase_date_local: 订购时间【站点时间】
- purchase_date_utc: 订购时间【UTC】
- shipment_date: 发货日期
- last_update_date: 订单更新时间
- posted_date: 付款时间
- earliest_ship_date: 发货时限

### 商品信息
- asin: ASIN
- quantity_ordered: 数量
- seller_sku: MSKU
- local_sku: 本地sku
- local_name: 本地品名

## 使用示例

```php
// 查询杜国平德国店铺的订单
$params = [
    'sid' => 6608,  // 杜国平德国店铺ID
    'start_date' => '2024-01-01',
    'end_date' => '2024-12-31',
    'date_type' => 1,
    'sort_desc_by_date_type' => 1,
    'offset' => 0,
    'length' => 100
];

$response = $client->makeRequest('/erp/sc/data/mws/orders', 'POST', $params);
```

## 注意事项
1. 查询时间范围不超过一年
2. 当date_type=3时，需要传入时间格式为：Y-m-d H:i:s
3. Pending、Unshipped、Canceled没有发货时间，当date_type为10时，传入这三个参数无意义
4. 2023年后的多渠道订单数据均不在此接口返回
