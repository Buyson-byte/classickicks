<?php
// Fetch brands
$brands = [];
$result = $conn->query("SELECT * FROM brands");
while ($row = $result->fetch_assoc()) {
    $brands[] = $row;
}

// Fetch all products with brand names and total stock from product_sizes
$product_sql = "
    SELECT 
        p.id,
        p.name,
        p.description,
        p.price,
        p.image,
        p.sizes,
        b.name AS brand_name,
        COALESCE(SUM(ps.stock), 0) AS total_stock
    FROM products p
    LEFT JOIN brands b ON p.brand_id = b.id
    LEFT JOIN product_sizes ps ON p.id = ps.product_id
    GROUP BY p.id
    ORDER BY p.id DESC
";

$products_result = $conn->query($product_sql);
?>
