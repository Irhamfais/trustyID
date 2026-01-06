// api.js - API Configuration and Functions

// API Base URL - Sesuaikan dengan path backend Anda
// Karena frontend diakses via: http://localhost/umkm/Tugas_irham/frontend/
// maka backend API berada di:  http://localhost/umkm/Tugas_irham/backend/api
// Menggunakan path relatif ke origin agar tidak masalah CORS/port.
const API_BASE_URL = "/umkm/Tugas_irham/backend/api";

/**
 * Helper function untuk membuat API request
 */
async function apiRequest(endpoint, method = "GET", data = null) {
  const url = `${API_BASE_URL}${endpoint}`;
  const options = {
    method: method,
    headers: {
      "Content-Type": "application/json",
    },
  };

  if (data && (method === "POST" || method === "PUT")) {
    options.body = JSON.stringify(data);
  }

  try {
    const response = await fetch(url, options);
    
    // Cek apakah response adalah JSON atau HTML (error page)
    const contentType = response.headers.get("content-type");
    if (!contentType || !contentType.includes("application/json")) {
      const text = await response.text();
      console.error("API returned non-JSON response:", text.substring(0, 200));
      throw new Error("Server returned an error. Please check the console for details.");
    }
    
    const result = await response.json();

    if (!response.ok) {
      throw new Error(result.message || "Request failed");
    }

    return result;
  } catch (error) {
    console.error("API Error:", error);
    throw error;
  }
}

/**
 * Login API
 */
async function loginAPI(email, password) {
  return await apiRequest("/auth/login.php", "POST", { email, password });
}

/**
 * Register API
 */
async function registerAPI(name, email, password, confirmPassword, role) {
  return await apiRequest("/auth/register.php", "POST", {
    name,
    email,
    password,
    confirmPassword,
    role,
  });
}

/**
 * Verify Product API
 */
async function verifyProductAPI(productCode) {
  return await apiRequest("/products/verify.php", "POST", { productCode });
}

/**
 * Create Product (UMKM) API
 */
async function createProductAPI(payload) {
  return await apiRequest("/products/create.php", "POST", payload);
}

/**
 * List pending products for admin validation
 */
async function listPendingProductsAPI(adminId) {
  const query = adminId ? `?adminId=${encodeURIComponent(adminId)}` : "";
  return await apiRequest(`/products/list_pending.php${query}`, "GET");
}

/**
 * Update product status (admin)
 */
async function updateProductStatusAPI(payload) {
  return await apiRequest("/products/update_status.php", "POST", payload);
}

/**
 * List verified products (admin)
 */
async function listVerifiedProductsAPI(adminId) {
  const query = adminId ? `?adminId=${encodeURIComponent(adminId)}` : "";
  return await apiRequest(`/products/list_verified.php${query}`, "GET");
}

/**
 * Edit product (admin)
 */
async function editProductAPI(payload) {
  return await apiRequest("/products/edit.php", "POST", payload);
}

/**
 * Delete product (admin)
 */
async function deleteProductAPI(payload) {
  return await apiRequest("/products/delete.php", "POST", payload);
}
