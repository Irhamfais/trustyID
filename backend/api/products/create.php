<?php
// Start output buffering
ob_start();

// Set error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set headers first
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// Helper function to send JSON and exit
function sendJson($statusCode, $data) {
    ob_end_clean();
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    sendJson(200, ["status" => "ok"]);
}

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(405, [
        "status" => "error",
        "message" => "Method not allowed"
    ]);
}

try {
    // Include database
    require_once("../../config/database.php");
    
    // Check connection
    if (!isset($conn) || !$conn) {
        sendJson(500, [
            "status" => "error",
            "message" => "Database connection not available"
        ]);
    }
    
    // Get JSON input
    $input = file_get_contents("php://input");
    
    if ($input === false || empty($input)) {
        sendJson(400, [
            "status" => "error",
            "message" => "No input data received"
        ]);
    }
    
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendJson(400, [
            "status" => "error",
            "message" => "Invalid JSON input: " . json_last_error_msg()
        ]);
    }
    
    if (!is_array($data)) {
        sendJson(400, [
            "status" => "error",
            "message" => "Invalid data format"
        ]);
    }
    
    // Required fields
    $requiredFields = ['userId', 'productCode', 'name', 'producer', 'expiryDate'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            sendJson(400, [
                "status" => "error",
                "message" => "Field '{$field}' is required"
            ]);
        }
    }
    
    $userId = (int)$data['userId'];
    $productCode = strtoupper(trim($data['productCode']));
    $name = trim($data['name']);
    $producer = trim($data['producer']);
    $expiryDate = trim($data['expiryDate']);
    $certificationType = isset($data['certificationType']) ? trim($data['certificationType']) : '';
    $certificationLink = isset($data['certificationLink']) ? trim($data['certificationLink']) : '';
    
    // Validasi sertifikasi
    if (empty($certificationType)) {
        sendJson(400, [
            "status" => "error",
            "message" => "Jenis sertifikasi harus dipilih"
        ]);
    }
    
    if (empty($certificationLink)) {
        sendJson(400, [
            "status" => "error",
            "message" => "Link bukti sertifikasi harus diisi"
        ]);
    }
    
    // Optional scores (default 0)
    $hygieneScore = isset($data['hygieneScore']) ? (int)$data['hygieneScore'] : 0;
    $qualityScore = isset($data['qualityScore']) ? (int)$data['qualityScore'] : 0;
    $trustScore = isset($data['trustScore']) ? (int)$data['trustScore'] : 0;
    
    // Ensure certification columns exist
    $checkCol = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'certification_type'");
    if (mysqli_num_rows($checkCol) == 0) {
        @mysqli_query($conn, "ALTER TABLE products ADD COLUMN certification_type VARCHAR(50) NULL AFTER owner_id");
    }
    
    $checkCol2 = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'certification_link'");
    if (mysqli_num_rows($checkCol2) == 0) {
        @mysqli_query($conn, "ALTER TABLE products ADD COLUMN certification_link TEXT NULL AFTER certification_type");
    }
    
    // 1) Pastikan user adalah UMKM
    $sqlUser = "SELECT id, role FROM users WHERE id = ?";
    $stmtUser = mysqli_prepare($conn, $sqlUser);
    
    if (!$stmtUser) {
        sendJson(500, [
            "status" => "error",
            "message" => "Query preparation failed: " . mysqli_error($conn)
        ]);
    }
    
    mysqli_stmt_bind_param($stmtUser, "i", $userId);
    
    if (!mysqli_stmt_execute($stmtUser)) {
        $error = mysqli_stmt_error($stmtUser);
        mysqli_stmt_close($stmtUser);
        sendJson(500, [
            "status" => "error",
            "message" => "Query execution failed: " . $error
        ]);
    }
    
    $resultUser = mysqli_stmt_get_result($stmtUser);
    
    if (!$resultUser) {
        mysqli_stmt_close($stmtUser);
        sendJson(500, [
            "status" => "error",
            "message" => "Failed to get query result"
        ]);
    }
    
    if (mysqli_num_rows($resultUser) !== 1) {
        mysqli_stmt_close($stmtUser);
        sendJson(404, [
            "status" => "error",
            "message" => "User not found"
        ]);
    }
    
    $user = mysqli_fetch_assoc($resultUser);
    mysqli_stmt_close($stmtUser);
    
    if ($user['role'] !== 'umkm') {
        sendJson(403, [
            "status" => "error",
            "message" => "Only UMKM users can submit products"
        ]);
    }
    
    // 2) Pastikan product_code belum dipakai
    $sqlCheck = "SELECT id FROM products WHERE product_code = ?";
    $stmtCheck = mysqli_prepare($conn, $sqlCheck);
    
    if (!$stmtCheck) {
        sendJson(500, [
            "status" => "error",
            "message" => "Query preparation failed: " . mysqli_error($conn)
        ]);
    }
    
    mysqli_stmt_bind_param($stmtCheck, "s", $productCode);
    
    if (!mysqli_stmt_execute($stmtCheck)) {
        $error = mysqli_stmt_error($stmtCheck);
        mysqli_stmt_close($stmtCheck);
        sendJson(500, [
            "status" => "error",
            "message" => "Query execution failed: " . $error
        ]);
    }
    
    $resultCheck = mysqli_stmt_get_result($stmtCheck);
    
    if (!$resultCheck) {
        mysqli_stmt_close($stmtCheck);
        sendJson(500, [
            "status" => "error",
            "message" => "Failed to get query result"
        ]);
    }
    
    if (mysqli_num_rows($resultCheck) > 0) {
        mysqli_stmt_close($stmtCheck);
        sendJson(409, [
            "status" => "error",
            "message" => "Product code already exists. Please use a different code."
        ]);
    }
    
    mysqli_stmt_close($stmtCheck);
    
    // 3) Insert product dengan status 'pending'
    // Query: VALUES (?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?)
    // 10 placeholders: product_code(s), name(s), producer(s), expiry_date(s), 
    //                  owner_id(i), certification_type(s), certification_link(s),
    //                  hygiene_score(i), quality_score(i), trust_score(i)
    $insertSql = "INSERT INTO products 
        (product_code, name, producer, expiry_date, status, owner_id, certification_type, certification_link, hygiene_score, quality_score, trust_score)
        VALUES (?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?)";
    
    $stmtInsert = mysqli_prepare($conn, $insertSql);
    
    if (!$stmtInsert) {
        sendJson(500, [
            "status" => "error",
            "message" => "Insert query preparation failed: " . mysqli_error($conn)
        ]);
    }
    
    // Bind 10 parameters: ssss (4 strings) + i (1 int) + ss (2 strings) + iii (3 ints) = 10 total
    mysqli_stmt_bind_param(
        $stmtInsert,
        "ssssissiii",
        $productCode,      // s - product_code
        $name,            // s - name
        $producer,        // s - producer
        $expiryDate,      // s - expiry_date
        $userId,          // i - owner_id
        $certificationType,   // s - certification_type
        $certificationLink,   // s - certification_link
        $hygieneScore,    // i - hygiene_score
        $qualityScore,    // i - quality_score
        $trustScore       // i - trust_score
    );
    
    if (!mysqli_stmt_execute($stmtInsert)) {
        $error = mysqli_stmt_error($stmtInsert);
        mysqli_stmt_close($stmtInsert);
        sendJson(500, [
            "status" => "error",
            "message" => "Failed to insert product: " . $error
        ]);
    }
    
    $productId = mysqli_insert_id($conn);
    mysqli_stmt_close($stmtInsert);
    mysqli_close($conn);
    
    // Send success response
    sendJson(201, [
        "status" => "success",
        "message" => "Product submitted successfully. Waiting for admin verification.",
        "product" => [
            "id" => $productId,
            "productCode" => $productCode,
            "name" => $name,
            "producer" => $producer,
            "expiryDate" => $expiryDate,
            "status" => "pending",
            "ownerId" => $userId,
            "certificationType" => $certificationType,
            "certificationLink" => $certificationLink
        ]
    ]);
    
} catch (Throwable $e) {
    // Close connection if still open
    if (isset($conn) && $conn) {
        mysqli_close($conn);
    }
    
    // Send error response
    sendJson(500, [
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>
