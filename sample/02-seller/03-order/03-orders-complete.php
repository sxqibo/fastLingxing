<?php
/**
 * 亚马逊订单完整查询程序
 * 
 * 功能：
 * 1. 查询指定时间范围的订单
 * 2. 支持多种筛选条件
 * 3. 数据统计和分析
 * 4. 导出Excel功能
 */

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class OrderQueryComplete
{
    private $client;
    private $duGuoPingSid = 6608; // 杜国平德国店铺ID
    
    public function __construct()
    {
        $this->client = createOpenAPIClient();
    }
    
    /**
     * 查询一个月订单
     */
    public function queryMonthlyOrders($months = 1)
    {
        echo "=== 查询 {$months} 个月订单数据 ===\n\n";
        
        // 计算时间范围
        $endDate = date('Y-m-d'); // 今天
        $startDate = date('Y-m-d', strtotime("-{$months} month")); // N个月前
        
        echo "查询时间范围: {$startDate} 到 {$endDate}\n";
        echo "店铺ID: {$this->duGuoPingSid} (杜国平德国店铺)\n\n";
        
        return $this->queryOrders($startDate, $endDate);
    }
    
    /**
     * 查询指定时间范围的订单
     */
    public function queryOrders($startDate, $endDate, $options = [])
    {
        try {
            // 生成AccessToken
            echo "正在生成AccessToken...\n";
            $accessTokenDto = $this->client->generateAccessToken();
            displayAccessTokenInfo($accessTokenDto);
            
            // 构建查询参数
            $params = [
                'sid' => $this->duGuoPingSid,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'date_type' => 1,              // 订购时间
                'sort_desc_by_date_type' => 1, // 降序排序
                'offset' => 0,
                'length' => 5000              // 最大查询5000条
            ];
            
            // 添加可选参数
            if (isset($options['order_status'])) {
                $params['order_status'] = $options['order_status'];
            }
            if (isset($options['fulfillment_channel'])) {
                $params['fulfillment_channel'] = $options['fulfillment_channel'];
            }
            
            echo "\n正在查询订单...\n";
            echo "查询参数: " . json_encode($params, JSON_UNESCAPED_UNICODE) . "\n\n";
            
            $response = $this->client->makeRequest('/erp/sc/data/mws/orders', 'POST', $params);
            
            if ($response['code'] == 0) {
                return $this->processOrders($response, $startDate, $endDate);
            } else {
                throw new Exception("查询失败: " . $response['message']);
            }
            
        } catch (Exception $e) {
            echo "查询订单时发生错误: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * 处理订单数据
     */
    private function processOrders($response, $startDate, $endDate)
    {
        echo "=== 查询结果 ===\n";
        echo "订单总数: " . $response['total'] . "\n";
        echo "当前页订单数: " . count($response['data']) . "\n\n";
        
        if (empty($response['data'])) {
            echo "该时间段内暂无订单数据\n";
            return [];
        }
        
        $orders = $response['data'];
        $this->displayOrderSummary($orders);
        $this->displayOrderDetails($orders);
        
        // 导出Excel
        $this->exportOrdersToExcel($orders, $startDate, $endDate);
        
        return $orders;
    }
    
    /**
     * 显示订单统计信息
     */
    private function displayOrderSummary($orders)
    {
        echo "=== 订单统计 ===\n";
        
        $statusCount = [];
        $channelCount = [];
        $totalAmount = 0;
        $currency = '';
        $productCount = 0;
        
        foreach ($orders as $order) {
            // 统计订单状态
            $status = $order['order_status'];
            $statusCount[$status] = ($statusCount[$status] ?? 0) + 1;
            
            // 统计配送方式
            $channel = $order['fulfillment_channel'];
            $channelCount[$channel] = ($channelCount[$channel] ?? 0) + 1;
            
            // 累计订单金额
            $totalAmount += floatval($order['order_total_amount']);
            $currency = $order['order_total_currency_code'];
            
            // 统计商品数量
            $productCount += count($order['item_list']);
        }
        
        echo "订单状态分布:\n";
        foreach ($statusCount as $status => $count) {
            $percentage = round($count / count($orders) * 100, 1);
            echo "  - {$status}: {$count} 个 ({$percentage}%)\n";
        }
        
        echo "\n配送方式分布:\n";
        foreach ($channelCount as $channel => $count) {
            $channelName = $channel == 'AFN' ? '亚马逊配送' : '自发货';
            $percentage = round($count / count($orders) * 100, 1);
            echo "  - {$channelName} ({$channel}): {$count} 个 ({$percentage}%)\n";
        }
        
        echo "\n财务统计:\n";
        echo "  - 总订单金额: " . number_format($totalAmount, 2) . " " . $currency . "\n";
        echo "  - 平均订单金额: " . number_format($totalAmount / count($orders), 2) . " " . $currency . "\n";
        echo "  - 总商品数量: {$productCount} 个\n";
        echo "  - 平均每单商品数: " . round($productCount / count($orders), 1) . " 个\n\n";
    }
    
    /**
     * 显示订单详情
     */
    private function displayOrderDetails($orders)
    {
        echo "=== 订单详情 ===\n";
        
        foreach ($orders as $index => $order) {
            echo "订单 " . ($index + 1) . ":\n";
            echo "  - 订单号: " . $order['amazon_order_id'] . "\n";
            echo "  - 订单状态: " . $order['order_status'] . "\n";
            echo "  - 订单金额: " . $order['order_total_amount'] . " " . $order['order_total_currency_code'] . "\n";
            echo "  - 配送方式: " . $order['fulfillment_channel'] . "\n";
            echo "  - 订购时间: " . $order['purchase_date_local'] . "\n";
            echo "  - 发货时间: " . ($order['shipment_date_local'] ?: '未发货') . "\n";
            echo "  - 物流单号: " . ($order['tracking_number'] ?: '无') . "\n";
            
            // 显示商品信息
            if (!empty($order['item_list'])) {
                echo "  - 商品信息:\n";
                foreach ($order['item_list'] as $itemIndex => $item) {
                    echo "    " . ($itemIndex + 1) . ". " . ($item['local_name'] ?: $item['asin']) . 
                         " (数量: " . $item['quantity_ordered'] . ", SKU: " . $item['seller_sku'] . ")\n";
                }
            }
            echo "\n";
        }
    }
    
    /**
     * 导出订单到Excel
     */
    private function exportOrdersToExcel($orders, $startDate, $endDate)
    {
        try {
            echo "=== 导出Excel ===\n";
            
            $filename = "orders_{$startDate}_to_{$endDate}_" . date('Y-m-d_H-i-s') . ".xlsx";
            $filepath = __DIR__ . '/' . $filename;
            
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('订单数据');
            
            // 设置表头
            $headers = [
                'A' => '订单号',
                'B' => '订单状态',
                'C' => '订单金额',
                'D' => '币种',
                'E' => '配送方式',
                'F' => '订购时间',
                'G' => '发货时间',
                'H' => '物流单号',
                'I' => '商品ASIN',
                'J' => '商品数量',
                'K' => 'MSKU',
                'L' => '本地SKU',
                'M' => '商品名称'
            ];
            
            $col = 1;
            foreach ($headers as $header) {
                $sheet->setCellValueByColumnAndRow($col, 1, $header);
                $col++;
            }
            
            // 设置表头样式
            $headerRange = 'A1:M1';
            $sheet->getStyle($headerRange)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ]);
            
            // 写入数据
            $row = 2;
            foreach ($orders as $order) {
                if (!empty($order['item_list'])) {
                    foreach ($order['item_list'] as $item) {
                        $sheet->setCellValue('A' . $row, $order['amazon_order_id']);
                        $sheet->setCellValue('B' . $row, $order['order_status']);
                        $sheet->setCellValue('C' . $row, $order['order_total_amount']);
                        $sheet->setCellValue('D' . $row, $order['order_total_currency_code']);
                        $sheet->setCellValue('E' . $row, $order['fulfillment_channel']);
                        $sheet->setCellValue('F' . $row, $order['purchase_date_local']);
                        $sheet->setCellValue('G' . $row, $order['shipment_date_local'] ?: '');
                        $sheet->setCellValue('H' . $row, $order['tracking_number'] ?: '');
                        $sheet->setCellValue('I' . $row, $item['asin']);
                        $sheet->setCellValue('J' . $row, $item['quantity_ordered']);
                        $sheet->setCellValue('K' . $row, $item['seller_sku']);
                        $sheet->setCellValue('L' . $row, $item['local_sku'] ?: '');
                        $sheet->setCellValue('M' . $row, $item['local_name'] ?: '');
                        $row++;
                    }
                } else {
                    // 没有商品信息的订单
                    $sheet->setCellValue('A' . $row, $order['amazon_order_id']);
                    $sheet->setCellValue('B' . $row, $order['order_status']);
                    $sheet->setCellValue('C' . $row, $order['order_total_amount']);
                    $sheet->setCellValue('D' . $row, $order['order_total_currency_code']);
                    $sheet->setCellValue('E' . $row, $order['fulfillment_channel']);
                    $sheet->setCellValue('F' . $row, $order['purchase_date_local']);
                    $sheet->setCellValue('G' . $row, $order['shipment_date_local'] ?: '');
                    $sheet->setCellValue('H' . $row, $order['tracking_number'] ?: '');
                    $row++;
                }
            }
            
            // 设置列宽
            foreach (range('A', 'M') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }
            
            // 保存文件
            $writer = new Xlsx($spreadsheet);
            $writer->save($filepath);
            
            echo "Excel文件已导出: {$filepath}\n";
            
        } catch (Exception $e) {
            echo "导出Excel时发生错误: " . $e->getMessage() . "\n";
        }
    }
}

// 使用示例
try {
    $orderQuery = new OrderQueryComplete();
    
    // 查询最近一个月的订单
    $orders = $orderQuery->queryMonthlyOrders(1);
    
    if ($orders) {
        echo "\n=== 查询完成 ===\n";
        echo "共查询到 " . count($orders) . " 个订单\n";
    }

} catch (Exception $e) {
    echo "发生错误: " . $e->getMessage() . "\n";
    echo "错误类型: " . get_class($e) . "\n";
}
