<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($conn) || !($conn instanceof mysqli)) {
    $conn = new mysqli("localhost", "root", "", "login");
    if ($conn->connect_error) {
        $lowStockCount = 0;
        return;
    }
}

$sql = "SELECT COUNT(*) AS c FROM ingredients WHERE stock <= low_stock_threshold";
$res = $conn->query($sql);
$lowStockCount = 0;

if ($res) {
    $row = $res->fetch_assoc();
    $lowStockCount = intval($row['c'] ?? 0);
}
// Clean Up Result
if (isset($res) && $res instanceof mysqli_result) {
    $res->free();
}

