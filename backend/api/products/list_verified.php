<?php
// Set error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set headers first
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only GET allowed
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        "status" => "error",
        "message" => "Method not allowed"
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

try {
    // Include database
    require_once("../../config/database.php");
    
    // Check connection
    if (!isset($conn) || !$conn) {
        throw new Exception("Database connection not available");
    }
    
    // Get adminId
    $adminId = isset($_GET['adminId']) ? (int)$_GET['adminId'] : 0;
    
    // Validate admin if adminId provided
    if ($adminId > 0) {
        $sqlAdmin = "SELECT id, role FROM users WHERE id = ?";
        $stmtAdmin = mysqli_prepare($conn, $sqlAdmin);
        
        if (!$stmtAdmin) {
            throw new Exception("Query preparation failed: " . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmtAdmin, "i", $adminId);
        
        if (!mysqli_stmt_execute($stmtAdmin)) {
            $error = mysqli_stmt_error($stmtAdmin);
            mysqli_stmt_close($stmtAdmin);
            throw new Exception("Query execution failed: " . $error);
        }
        
        $resultAdmin = mysqli_stmt_get_result($stmtAdmin);
        
        if (!$resultAdmin) {
            mysqli_stmt_close($stmtAdmin);
            throw new Exception("Failed to get query result");
        }
        
        if (mysqli_num_rows($resultAdmin) !== 1) {
            mysqli_stmt_close($stmtAdmin);
            http_response_code(404);
            echo json_encode([
                "status" => "error",
                "message" => "Admin user not found"
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }
        
        $admin = mysqli_fetch_assoc($resultAdmin);
        mysqli_stmt_close($stmtAdmin);
        
        if ($admin['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode([
                "status" => "error",
                "message" => "Only admin users can access verified products"
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }
    }
    
    // Check if certification columns exist, if not add them
    $checkCol = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'certification_type'");
    if (mysqli_num_rows($checkCol) == 0) {
        // Add certification_type column if it doesn't exist
        @mysqli_query($conn, "ALTER TABLE products ADD COLUMN certification_type VARCHAR(50) NULL AFTER owner_id");
    }
    
    $checkCol2 = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'certification_link'");
    if (mysqli_num_rows($checkCol2) == 0) {
        // Add certification_link column if it doesn't exist
        @mysqli_query($conn, "ALTER TABLE products ADD COLUMN certification_link TEXT NULL AFTER certification_type");
    }
    
    // Get verified products
    $sql = "SELECT p.id, p.product_code, p.name, p.producer, p.expiry_date, 
                   p.status, p.created_at, 
                   COALESCE(p.certification_type, '') AS certification_type, 
                   COALESCE(p.certification_link, '') AS certification_link,
                   u.name AS owner_name, u.email AS owner_email
            FROM products p
            LEFT JOIN users u ON p.owner_id = u.id
            WHERE p.status = 'verified'
            ORDER BY p.created_at DESC";
    
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        throw new Exception("Query failed: " . mysqli_error($conn));
    }
    
    // Build products array
    $verified = [];
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $verified[] = [
                "id" => (int)$row['id'],
                "productCode" => $row['product_code'],
                "name" => $row['name'],
                "producer" => $row['producer'],
                "expiryDate" => $row['expiry_date'],
                "status" => $row['status'],
                "createdAt" => $row['created_at'],
                "certificationType" => $row['certification_type'] ?? '',
                "certificationLink" => $row['certification_link'] ?? '',
                "owner" => [
                    "name" => $row['owner_name'] ?? '',
                    "email" => $row['owner_email'] ?? '',
                ],
            ];
        }
    }
    
    mysqli_close($conn);
    
    // Send success response
    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "products" => $verified,
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // Close connection if still open
    if (isset($conn) && $conn) {
        mysqli_close($conn);
    }
    
    // Send error response
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
