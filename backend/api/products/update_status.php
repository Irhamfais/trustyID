<?php
require_once("../../config/database.php");

// Set CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "status" => "error",
        "message" => "Method not allowed"
    ]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['adminId']) || empty($data['productId']) || empty($data['status'])) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "adminId, productId, and status are required"
    ]);
    exit();
}

$adminId = (int)$data['adminId'];
$productId = (int)$data['productId'];
$status = $data['status'];

// Valid status values
$allowedStatus = ['pending', 'verified', 'expired', 'rejected'];
if (!in_array($status, $allowedStatus, true)) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Invalid status value"
    ]);
    exit();
}

// Pastikan user adalah admin
$sqlAdmin = "SELECT id, role FROM users WHERE id = ?";
$stmtAdmin = mysqli_prepare($conn, $sqlAdmin);
mysqli_stmt_bind_param($stmtAdmin, "i", $adminId);
mysqli_stmt_execute($stmtAdmin);
$resultAdmin = mysqli_stmt_get_result($stmtAdmin);

if (mysqli_num_rows($resultAdmin) !== 1) {
    http_response_code(404);
    echo json_encode([
        "status" => "error",
        "message" => "Admin user not found"
    ]);
    mysqli_stmt_close($stmtAdmin);
    exit();
}

$admin = mysqli_fetch_assoc($resultAdmin);
mysqli_stmt_close($stmtAdmin);

if ($admin['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode([
        "status" => "error",
        "message" => "Only admin users can update product status"
    ]);
    exit();
}

// Update status produk
$updateSql = "UPDATE products SET status = ? WHERE id = ?";
$stmtUpdate = mysqli_prepare($conn, $updateSql);
mysqli_stmt_bind_param($stmtUpdate, "si", $status, $productId);

if (mysqli_stmt_execute($stmtUpdate)) {
    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "Product status updated successfully"
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Failed to update product status"
    ]);
}

mysqli_stmt_close($stmtUpdate);
mysqli_close($conn);
?>


