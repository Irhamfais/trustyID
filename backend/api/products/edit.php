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

// Only allow POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "status" => "error",
        "message" => "Method not allowed"
    ]);
    exit();
}

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Required fields
if (!isset($data['adminId']) || !isset($data['productId'])) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "adminId and productId are required"
    ]);
    exit();
}

$adminId = (int)$data['adminId'];
$productId = (int)$data['productId'];

// Validasi admin
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
        "message" => "Only admin users can edit products"
    ]);
    exit();
}

// Ambil data produk yang akan di-edit
$sqlProduct = "SELECT id FROM products WHERE id = ?";
$stmtProduct = mysqli_prepare($conn, $sqlProduct);
mysqli_stmt_bind_param($stmtProduct, "i", $productId);
mysqli_stmt_execute($stmtProduct);
$resultProduct = mysqli_stmt_get_result($stmtProduct);

if (mysqli_num_rows($resultProduct) !== 1) {
    http_response_code(404);
    echo json_encode([
        "status" => "error",
        "message" => "Product not found"
    ]);
    mysqli_stmt_close($stmtProduct);
    exit();
}
mysqli_stmt_close($stmtProduct);

// Update fields yang dikirim (opsional)
$updateFields = [];
$updateValues = [];
$types = "";

if (isset($data['name']) && !empty($data['name'])) {
    $updateFields[] = "name = ?";
    $updateValues[] = trim($data['name']);
    $types .= "s";
}

if (isset($data['producer']) && !empty($data['producer'])) {
    $updateFields[] = "producer = ?";
    $updateValues[] = trim($data['producer']);
    $types .= "s";
}

if (isset($data['expiryDate']) && !empty($data['expiryDate'])) {
    $updateFields[] = "expiry_date = ?";
    $updateValues[] = trim($data['expiryDate']);
    $types .= "s";
}

if (isset($data['certificationType']) && !empty($data['certificationType'])) {
    $updateFields[] = "certification_type = ?";
    $updateValues[] = trim($data['certificationType']);
    $types .= "s";
}

if (isset($data['certificationLink']) && !empty($data['certificationLink'])) {
    $updateFields[] = "certification_link = ?";
    $updateValues[] = trim($data['certificationLink']);
    $types .= "s";
}

if (isset($data['status']) && in_array($data['status'], ['pending', 'verified', 'expired', 'rejected'])) {
    $updateFields[] = "status = ?";
    $updateValues[] = $data['status'];
    $types .= "s";
}

if (empty($updateFields)) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "No fields to update"
    ]);
    exit();
}

// Tambah productId ke akhir untuk WHERE clause
$updateValues[] = $productId;
$types .= "i";

$updateSql = "UPDATE products SET " . implode(", ", $updateFields) . " WHERE id = ?";
$stmtUpdate = mysqli_prepare($conn, $updateSql);
mysqli_stmt_bind_param($stmtUpdate, $types, ...$updateValues);

if (mysqli_stmt_execute($stmtUpdate)) {
    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "Product updated successfully"
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Failed to update product"
    ]);
}

mysqli_stmt_close($stmtUpdate);
mysqli_close($conn);
?>


