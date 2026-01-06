<?php
require_once("../../config/database.php");

// Set CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST and GET methods
if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'GET'])) {
    http_response_code(405);
    echo json_encode([
        "status" => "error",
        "message" => "Method not allowed"
    ]);
    exit();
}

// Get product code from input
$productCode = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $productCode = isset($data['productCode']) ? trim(strtoupper($data['productCode'])) : '';
} else {
    $productCode = isset($_GET['code']) ? trim(strtoupper($_GET['code'])) : '';
}

// Validate input
if (empty($productCode)) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Product code is required"
    ]);
    exit();
}

// Use prepared statement to prevent SQL injection
// Ambil sertifikasi dari kolom certification_type di products dan dari product_certifications
$sql = "SELECT p.*, 
        GROUP_CONCAT(DISTINCT pc.certification_type ORDER BY pc.certification_type SEPARATOR ', ') as certs_from_table
        FROM products p
        LEFT JOIN product_certifications pc ON p.id = pc.product_id
        WHERE p.product_code = ?
        GROUP BY p.id";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $productCode);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 1) {
    $product = mysqli_fetch_assoc($result);

    // Jika status masih pending, kembalikan pesan bahwa produk menunggu verifikasi admin
    if ($product['status'] === 'pending') {
        http_response_code(202);
        echo json_encode([
            "status" => "pending",
            "message" => "Produk ini masih menunggu validasi dan persetujuan admin."
        ]);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        exit();
    }

    // Jika status sudah verified, cek kedaluwarsa dan ubah ke expired jika perlu
    if ($product['status'] === 'verified') {
        $expiryDate = new DateTime($product['expiry_date']);
        $today = new DateTime();
        $status = ($expiryDate >= $today) ? 'verified' : 'expired';

        if ($product['status'] !== $status) {
            $updateSql = "UPDATE products SET status = ? WHERE id = ?";
            $updateStmt = mysqli_prepare($conn, $updateSql);
            mysqli_stmt_bind_param($updateStmt, "si", $status, $product['id']);
            mysqli_stmt_execute($updateStmt);
            mysqli_stmt_close($updateStmt);
            $product['status'] = $status;
        }
    }

    // Gabungkan sertifikasi dari kolom certification_type dan dari product_certifications
    $certifications = [];
    
    // Ambil dari kolom certification_type (untuk produk baru)
    if (!empty($product['certification_type'])) {
        $certifications[] = trim($product['certification_type']);
    }
    
    // Ambil dari product_certifications (untuk produk lama)
    if (!empty($product['certs_from_table'])) {
        $certsFromTable = explode(', ', $product['certs_from_table']);
        foreach ($certsFromTable as $cert) {
            $cert = trim($cert);
            if (!empty($cert) && !in_array($cert, $certifications)) {
                $certifications[] = $cert;
            }
        }
    }
    
    // Hapus duplikat dan urutkan
    $certifications = array_unique($certifications);
    sort($certifications);
    
    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "product" => [
            "name" => $product['name'],
            "producer" => $product['producer'],
            "expiryDate" => $product['expiry_date'],
            "status" => $product['status'],
            "certifications" => $certifications,
            "ratings" => [
                "hygiene" => (int)$product['hygiene_score'],
                "quality" => (int)$product['quality_score'],
                "trust" => (int)$product['trust_score']
            ]
        ]
    ]);
} else {
    http_response_code(404);
    echo json_encode([
        "status" => "error",
        "message" => "Product registration number not found. Please try again.\n\nValid example codes:\n- BPOM-12345\n- HALAL-67890\n- BPOM-99999 (Expired)\n- HALAL-11111"
    ]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>

