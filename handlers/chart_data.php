<?php
// Use absolute path so includes work no matter where this file is called from
require_once __DIR__ . '/../connections/connection.php';
$conn = connection();

/*
 * Build monthly best-sellers:
 * - labels: ordered list of months that have sales (e.g., Jan, Feb, …)
 * - datasets: one series per product with quantities per month
 */

// Get total sold per product per month (for all years; filter if you want current year only)
$sql = "
    SELECT 
        p.name AS product_name,
        DATE_FORMAT(o.order_date, '%Y-%m') AS ym,
        SUM(oi.quantity) AS total_sold
    FROM order_items oi
    JOIN orders o   ON oi.order_id = o.id
    JOIN products p ON oi.product_id = p.id
    WHERE oi.status = 'completed'   -- ✅ only count completed items
    GROUP BY p.name, ym
    ORDER BY ym ASC, total_sold DESC
";

$res = $conn->query($sql);

// Build a sorted set of months and a product->month->qty map
$monthKeys = [];         // '2025-01', '2025-02', ...
$productMonthQty = [];   // [product][ym] = qty

while ($row = $res->fetch_assoc()) {
    $product = $row['product_name'];
    $ym      = $row['ym'];
    $qty     = (int)$row['total_sold'];

    if (!in_array($ym, $monthKeys, true)) $monthKeys[] = $ym;
    if (!isset($productMonthQty[$product])) $productMonthQty[$product] = [];
    $productMonthQty[$product][$ym] = $qty;
}

// Convert 'YYYY-MM' -> 'Mon' for labels
$labels = array_map(function($ym) {
    $ts = strtotime($ym . '-01');
    return date('M', $ts); // Jan, Feb, …
}, $monthKeys);

// Build datasets (Chart.js expects numeric arrays aligned with $labels)
$palette = ['#4e73df','#1cc88a','#36b9cc','#f6c23e','#e74a3b','#858796','#fd7e14','#20c997'];
$datasets = [];
$pi = 0;
foreach ($productMonthQty as $product => $monthQty) {
    $rowData = [];
    foreach ($monthKeys as $ym) {
        $rowData[] = isset($monthQty[$ym]) ? (int)$monthQty[$ym] : 0;
    }
    $datasets[] = [
        "label" => $product,
        "data"  => $rowData,
        "backgroundColor" => $palette[$pi % count($palette)]
    ];
    $pi++;
}