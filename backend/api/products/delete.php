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
        "message" => "Only admin users can delete products"
    ]);
    exit();
}

// Hapus produk (akan cascade ke product_certifications karena foreign key)
$deleteSql = "DELETE FROM products WHERE id = ?";
$stmtDelete = mysqli_prepare($conn, $deleteSql);
mysqli_stmt_bind_param($stmtDelete, "i", $productId);

if (mysqli_stmt_execute($stmtDelete)) {
    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "Product deleted successfully"
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Failed to delete product"
    ]);
}

mysqli_stmt_close($stmtDelete);
mysqli_close($conn);
?>


