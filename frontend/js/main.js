function checkLoginStatus() {
  // Mengambil data user dari sessionStorage (simulasi sesi)
  const savedUser = JSON.parse(sessionStorage.getItem("currentUser") || "null");
  if (savedUser) {
    // Update global variable 'currentUser' (dari data.js)
    currentUser = savedUser;
    updateUIForLoggedInUser();
  } else {
    updateUIForLoggedOutUser();
  }
}

// Update UI for logged in user
function updateUIForLoggedInUser() {
  const loginOverlay = document.getElementById("loginOverlay");
  const authButton = document.getElementById("authButton");
  const userWelcome = document.getElementById("userWelcome");
  const userName = document.getElementById("userName");
  const verificationContent = document.querySelector(".verification-content");
  const navUmkmItem = document.getElementById("navUmkmItem");
  const navAdminItem = document.getElementById("navAdminItem");
  const adminWelcome = document.getElementById("adminWelcome");
  const adminName = document.getElementById("adminName");

  // Sembunyikan login overlay & hapus blur
  if (loginOverlay) loginOverlay.classList.add("hidden");
  if (verificationContent) verificationContent.classList.remove("blurred");

  // Tampilkan menu khusus berdasarkan role
  if (currentUser && currentUser.role === "umkm") {
    if (navUmkmItem) navUmkmItem.style.display = "block";
  } else if (navUmkmItem) {
    navUmkmItem.style.display = "none";
  }

  if (currentUser && currentUser.role === "admin") {
    if (navAdminItem) navAdminItem.style.display = "block";
    if (adminWelcome && adminName) {
      adminName.textContent = currentUser.name;
      adminWelcome.style.display = "block";
    }
  } else {
    if (navAdminItem) navAdminItem.style.display = "none";
    if (adminWelcome) adminWelcome.style.display = "none";
  }

  // Update auth button (Keluar)
  if (authButton) {
    authButton.textContent = "Keluar";
    authButton.onclick = handleLogout;
  } // Tampilkan user welcome
  if (userWelcome && userName && currentUser) {
    userName.textContent = currentUser.name;
    userWelcome.style.display = "block";
  }
}

// Update UI
function updateUIForLoggedOutUser() {
  const loginOverlay = document.getElementById("loginOverlay");
  const authButton = document.getElementById("authButton");
  const userWelcome = document.getElementById("userWelcome");
  const verificationContent = document.querySelector(".verification-content");
  const navUmkmItem = document.getElementById("navUmkmItem");
  const navAdminItem = document.getElementById("navAdminItem");
  const adminWelcome = document.getElementById("adminWelcome");

  // Tampilkan login overlay & berikan blur
  if (loginOverlay) loginOverlay.classList.remove("hidden");
  if (verificationContent) verificationContent.classList.add("blurred");
  if (authButton) {
    authButton.textContent = "Masuk";
    // Hapus handler lama (logout) dan pasang handler baru (openLoginModal)
    authButton.onclick = openLoginModal;
  }

  // Sembunyikan user welcome & admin banner
  if (userWelcome) userWelcome.style.display = "none";
  if (adminWelcome) adminWelcome.style.display = "none";

  // Sembunyikan menu role khusus
  if (navUmkmItem) navUmkmItem.style.display = "none";
  if (navAdminItem) navAdminItem.style.display = "none";

  // Sembunyikan hasil verifikasi jika ada
  const resultContainer = document.getElementById("resultContainer");
  if (resultContainer) resultContainer.style.display = "none";

  // Reset active nav link
  document
    .querySelectorAll(".nav-link")
    .forEach((l) => l.classList.remove("active"));
  document.querySelector('.nav-link[href="#home"]').classList.add("active");
}

// Logout Handler
function handleLogout() {
  if (confirm("Apakah Anda yakin ingin keluar?")) {
    currentUser = null;
    sessionStorage.removeItem("currentUser");
    updateUIForLoggedOutUser();
    alert("Anda telah keluar. Terima kasih!");
  }
}

// ===============================================
// 2. Modal Logic
// ===============================================

const loginModal = document.getElementById("loginModal");
const loginForm = document.getElementById("loginForm");
const registerForm = document.getElementById("registerForm");

function openLoginModal() {
  if (loginModal) loginModal.classList.add("active");
  showLoginFormInModal(); // Default: tampilkan form login
}

function closeLoginModal() {
  if (loginModal) loginModal.classList.remove("active");
}

function showLoginFormInModal() {
  if (loginForm) loginForm.style.display = "block";
  if (registerForm) registerForm.style.display = "none";
  document.getElementById("modalTitle").textContent = "Masuk ke TrustyID";
  document.getElementById("modalSubtitle").textContent =
    "Silakan masuk untuk mengakses fitur verifikasi";
}

function showRegisterFormInModal() {
  if (loginForm) loginForm.style.display = "none";
  if (registerForm) registerForm.style.display = "block";
  document.getElementById("modalTitle").textContent = "Daftar Akun Baru";
  document.getElementById("modalSubtitle").textContent =
    "Buat akun untuk mulai menggunakan TrustyID";
}

// Login Form Handler
async function handleLoginSubmit(e) {
  e.preventDefault();
  const email = document.getElementById("loginEmail").value.trim();
  const password = document.getElementById("loginPassword").value;

  // Disable button and show loading
  const submitBtn = e.target.querySelector('button[type="submit"]');
  const originalText = submitBtn.textContent;
  submitBtn.disabled = true;
  submitBtn.textContent = "Memproses...";

  try {
    const response = await loginAPI(email, password);

    if (response.status === "success") {
      // Login successful
      currentUser = {
        name: response.user.name,
        email: response.user.email,
        id: response.user.id,
        role: response.user.role,
      };
      sessionStorage.setItem("currentUser", JSON.stringify(currentUser));
      updateUIForLoggedInUser();
      closeLoginModal();
      loginForm.reset();
      alert("Selamat datang, " + currentUser.name + "! Anda berhasil masuk.");

      // Jika admin, tunggu sebentar sebelum load pending products (pastikan DOM siap)
      if (currentUser.role === "admin") {
        setTimeout(() => {
          loadPendingProducts();
        }, 500);
      }
    }
  } catch (error) {
    alert(error.message || "Login gagal. Silakan coba lagi.");
  } finally {
    submitBtn.disabled = false;
    submitBtn.textContent = originalText;
  }
}

// Register Form Handler
async function handleRegisterSubmit(e) {
  e.preventDefault();
  const name = document.getElementById("registerName").value.trim();
  const email = document.getElementById("registerEmail").value.trim();
  const password = document.getElementById("registerPassword").value;
  const confirmPassword = document.getElementById(
    "registerConfirmPassword"
  ).value;
  const role = document.getElementById("registerRole")
    ? document.getElementById("registerRole").value
    : "consumer";

  // Client-side validation
  if (password.length < 6) {
    alert("Password harus minimal 6 karakter.");
    return;
  }
  if (password !== confirmPassword) {
    alert("Password dan konfirmasi password tidak cocok.");
    return;
  }

  // Disable button and show loading
  const submitBtn = e.target.querySelector('button[type="submit"]');
  const originalText = submitBtn.textContent;
  submitBtn.disabled = true;
  submitBtn.textContent = "Memproses...";

  try {
    const response = await registerAPI(
      name,
      email,
      password,
      confirmPassword,
      role
    );

    if (response.status === "success") {
      // Auto login after register
      currentUser = {
        name: response.user.name,
        email: response.user.email,
        id: response.user.id,
        role: response.user.role,
      };
      sessionStorage.setItem("currentUser", JSON.stringify(currentUser));
      updateUIForLoggedInUser();
      closeLoginModal();
      registerForm.reset();
      alert("Pendaftaran berhasil! Selamat datang, " + currentUser.name + "!");
    }
  } catch (error) {
    alert(error.message || "Pendaftaran gagal. Silakan coba lagi.");
  } finally {
    submitBtn.disabled = false;
    submitBtn.textContent = originalText;
  }
}

// ===============================================
// 3. Verification & General Handlers
// ===============================================

async function handleVerificationSubmit(e) {
  e.preventDefault();

  // Cek apakah user sudah login (redundansi untuk keamanan, tapi penting)
  if (!currentUser) {
    alert("Silakan masuk terlebih dahulu untuk menggunakan fitur verifikasi.");
    openLoginModal();
    return;
  }

  const productCode = document
    .getElementById("productCode")
    .value.trim()
    .toUpperCase();

  if (!productCode) {
    alert("Silakan masukkan nomor registrasi produk.");
    return;
  }

  // Disable button and show loading
  const submitBtn = e.target.querySelector('button[type="submit"]');
  const originalText = submitBtn.innerHTML;
  submitBtn.disabled = true;
  submitBtn.innerHTML = "<span>Memverifikasi...</span>";

  try {
    const response = await verifyProductAPI(productCode);

    if (response.status === "pending") {
      alert(
        response.message ||
          "Produk masih menunggu validasi dan persetujuan admin."
      );
      return;
    }

    if (response.status === "success" && response.product) {
      // Format product data untuk displayResult function
      const product = {
        name: response.product.name,
        producer: response.product.producer,
        expiryDate: response.product.expiryDate,
        status: response.product.status,
        certifications: response.product.certifications,
        ratings: response.product.ratings,
      };
      displayResult(product, productCode);
    }
  } catch (error) {
    alert(
      error.message || "Nomor registrasi tidak ditemukan. Silakan coba lagi."
    );
  } finally {
    submitBtn.disabled = false;
    submitBtn.innerHTML = originalText;
  }
}

// UMKM Product Submission Handler
async function handleUmkmProductSubmit(e) {
  e.preventDefault();

  if (!currentUser || currentUser.role !== "umkm") {
    alert("Hanya akun UMKM yang dapat mengajukan produk.");
    return;
  }

  const productCode = document
    .getElementById("umkmProductCode")
    .value.trim()
    .toUpperCase();
  const name = document.getElementById("umkmProductName").value.trim();
  const producer = document.getElementById("umkmProducer").value.trim();
  const expiryDate = document.getElementById("umkmExpiryDate").value;
  const certificationType = document.getElementById("umkmCertificationType").value;
  const certificationLink = document.getElementById("umkmCertificationLink").value.trim();

  if (!productCode || !name || !producer || !expiryDate || !certificationType || !certificationLink) {
    alert("Semua field wajib harus diisi.");
    return;
  }

  const payload = {
    userId: currentUser.id,
    productCode,
    name,
    producer,
    expiryDate,
    certificationType,
    certificationLink,
  };

  const submitBtn = e.target.querySelector('button[type="submit"]');
  const originalText = submitBtn.textContent;
  submitBtn.disabled = true;
  submitBtn.textContent = "Mengirim pengajuan...";

  try {
    const response = await createProductAPI(payload);
    if (response.status === "success") {
      alert(
        "Pengajuan produk berhasil dikirim dan menunggu validasi admin.\nKode: " +
          response.product.productCode
      );
      e.target.reset();
    }
  } catch (error) {
    alert(error.message || "Gagal mengirim pengajuan produk.");
  } finally {
    submitBtn.disabled = false;
    submitBtn.textContent = originalText;
  }
}

// Admin: load pending products
async function loadPendingProducts() {
  if (!currentUser || currentUser.role !== "admin") return;

  const container = document.getElementById("adminPendingContainer");
  const tbody = document.getElementById("adminPendingBody");
  const emptyState = document.getElementById("adminPendingEmpty");

  if (!tbody || !container) return;

  tbody.innerHTML = "";
  if (emptyState) emptyState.style.display = "none";

  try {
    const response = await listPendingProductsAPI(currentUser.id);

    if (response.status === "success" && Array.isArray(response.products)) {
      if (response.products.length === 0) {
        if (emptyState) emptyState.style.display = "block";
        return;
      }

      response.products.forEach((p) => {
        const tr = document.createElement("tr");
        const certLink = p.certificationLink 
          ? `<a href="${p.certificationLink}" target="_blank" rel="noopener noreferrer" style="color: var(--primary); text-decoration: underline;">Lihat</a>`
          : "-";
        tr.innerHTML = `
          <td>${p.productCode}</td>
          <td>${p.name}</td>
          <td>${p.producer || "-"}</td>
          <td>${p.owner?.name || "-"}</td>
          <td>${p.certificationType || "-"}</td>
          <td>${certLink}</td>
          <td>${p.expiryDate}</td>
          <td>${p.createdAt}</td>
          <td>
            <button class="btn btn-primary btn-sm" data-action="approve" data-id="${
              p.id
            }">Setujui</button>
            <button class="btn btn-primary btn-sm" data-action="reject" data-id="${
              p.id
            }" style="background:#ef4444;margin-left:4px;">Tolak</button>
          </td>
        `;
        tbody.appendChild(tr);
      });

      // Tambah event listener untuk tombol approve/reject
      tbody.querySelectorAll("button[data-action]").forEach((btn) => {
        btn.addEventListener("click", async (ev) => {
          const action = ev.currentTarget.getAttribute("data-action");
          const id = Number(ev.currentTarget.getAttribute("data-id"));
          const newStatus = action === "approve" ? "verified" : "rejected";

          if (
            !confirm(
              `Yakin ingin mengubah status produk menjadi '${newStatus}'?`
            )
          ) {
            return;
          }

          try {
            const res = await updateProductStatusAPI({
              adminId: currentUser.id,
              productId: id,
              status: newStatus,
            });
            if (res.status === "success") {
              alert("Status produk berhasil diperbarui.");
              loadPendingProducts();
            } else {
              alert(res.message || "Gagal memperbarui status produk.");
            }
          } catch (err) {
            alert(err.message || "Terjadi kesalahan saat memperbarui status.");
          }
        });
      });
    }
  } catch (error) {
    console.error("Gagal memuat produk pending:", error);
    alert("Gagal memuat pengajuan pending.");
  }
}

// Admin: load verified products
async function loadVerifiedProducts() {
  if (!currentUser || currentUser.role !== "admin") return;

  const container = document.getElementById("adminVerifiedContainer");
  const tbody = document.getElementById("adminVerifiedBody");
  const emptyState = document.getElementById("adminVerifiedEmpty");

  if (!tbody || !container) return;

  tbody.innerHTML = "";
  if (emptyState) emptyState.style.display = "none";

  try {
    const response = await listVerifiedProductsAPI(currentUser.id);

    if (response.status === "success" && Array.isArray(response.products)) {
      if (response.products.length === 0) {
        if (emptyState) emptyState.style.display = "block";
        return;
      }

      response.products.forEach((p) => {
        const tr = document.createElement("tr");
        const certLink = p.certificationLink 
          ? `<a href="${p.certificationLink}" target="_blank" rel="noopener noreferrer" style="color: var(--primary); text-decoration: underline;">Lihat</a>`
          : "-";
        tr.innerHTML = `
          <td>${p.productCode}</td>
          <td>${p.name}</td>
          <td>${p.producer || "-"}</td>
          <td>${p.owner?.name || "-"}</td>
          <td>${p.certificationType || "-"}</td>
          <td>${certLink}</td>
          <td>${p.expiryDate}</td>
          <td><span class="status-badge status-verified">TERVERIFIKASI</span></td>
          <td>
            <button class="btn btn-primary btn-sm" data-action="edit" data-id="${
              p.id
            }" data-product='${JSON.stringify(p).replace(/'/g, "&#39;")}'>Edit</button>
            <button class="btn btn-primary btn-sm" data-action="delete" data-id="${
              p.id
            }" style="background:#ef4444;margin-left:4px;">Delete</button>
          </td>
        `;
        tbody.appendChild(tr);
      });

      // Tambah event listener untuk tombol edit/delete
      tbody.querySelectorAll("button[data-action='edit']").forEach((btn) => {
        btn.addEventListener("click", async (ev) => {
          const id = Number(ev.currentTarget.getAttribute("data-id"));
          const productData = JSON.parse(ev.currentTarget.getAttribute("data-product").replace(/&#39;/g, "'"));
          handleEditProduct(productData);
        });
      });

      tbody.querySelectorAll("button[data-action='delete']").forEach((btn) => {
        btn.addEventListener("click", async (ev) => {
          const id = Number(ev.currentTarget.getAttribute("data-id"));
          
          if (!confirm("Yakin ingin menghapus produk ini? Tindakan ini tidak dapat dibatalkan.")) {
            return;
          }

          try {
            const res = await deleteProductAPI({
              adminId: currentUser.id,
              productId: id,
            });
            if (res.status === "success") {
              alert("Produk berhasil dihapus.");
              loadVerifiedProducts();
            } else {
              alert(res.message || "Gagal menghapus produk.");
            }
          } catch (err) {
            alert(err.message || "Terjadi kesalahan saat menghapus produk.");
          }
        });
      });
    }
  } catch (error) {
    console.error("Gagal memuat produk terverifikasi:", error);
    alert("Gagal memuat produk terverifikasi.");
  }
}

// Admin: handle edit product (modal sederhana dengan prompt)
function handleEditProduct(product) {
  const newName = prompt("Nama Produk:", product.name);
  if (newName === null) return; // User cancel

  const newProducer = prompt("Produsen:", product.producer);
  if (newProducer === null) return;

  const newExpiryDate = prompt("Tanggal Kadaluarsa (YYYY-MM-DD):", product.expiryDate);
  if (newExpiryDate === null) return;

  const newCertType = prompt("Jenis Sertifikasi (BPOM/Halal MUI/SNI):", product.certificationType);
  if (newCertType === null) return;

  const newCertLink = prompt("Link Bukti Sertifikasi:", product.certificationLink);
  if (newCertLink === null) return;

  // Update product
  (async () => {
    try {
      const res = await editProductAPI({
        adminId: currentUser.id,
        productId: product.id,
        name: newName,
        producer: newProducer,
        expiryDate: newExpiryDate,
        certificationType: newCertType,
        certificationLink: newCertLink,
      });
      if (res.status === "success") {
        alert("Produk berhasil diperbarui.");
        loadVerifiedProducts();
      } else {
        alert(res.message || "Gagal memperbarui produk.");
      }
    } catch (err) {
      alert(err.message || "Terjadi kesalahan saat memperbarui produk.");
    }
  })();
}

// Admin: handle tab switching
function handleAdminTabSwitch(tabName) {
  // Update tab buttons
  document.querySelectorAll(".admin-tab-btn").forEach((btn) => {
    btn.classList.remove("active");
    if (btn.getAttribute("data-tab") === tabName) {
      btn.classList.add("active");
    }
  });

  // Update tab content
  document.querySelectorAll(".admin-tab-content").forEach((content) => {
    content.classList.remove("active");
    content.style.display = "none";
  });

  const activeTab = document.getElementById(`adminTab${tabName.charAt(0).toUpperCase() + tabName.slice(1)}`);
  if (activeTab) {
    activeTab.classList.add("active");
    activeTab.style.display = "block";
  }

  // Load data sesuai tab
  if (tabName === "pending") {
    loadPendingProducts();
  } else if (tabName === "verified") {
    loadVerifiedProducts();
  }
}

function handleContactSubmit(e) {
  e.preventDefault();
  alert(
    "Terima kasih! Pesan Anda telah terkirim. Tim kami akan segera menghubungi Anda."
  );
  e.target.reset();
}

// Navigasi dengan smooth scroll
function handleNavScroll(e) {
  const link = e.currentTarget;
  const href = link.getAttribute("href");

  // Abaikan link yang tidak mengarah ke section tertentu (misalnya href="#")
  if (!href || href === "#") {
    return;
  }

  if (href.startsWith("#")) {
    e.preventDefault();
    const targetId = href;
    const targetSection = document.querySelector(targetId);

    // Akses ke Verifikasi (Protected)
    if (targetId === "#verifikasi" && !currentUser) {
      openLoginModal();
      return;
    }

    // Akses ke Admin Panel (Protected - hanya admin)
    if (targetId === "#admin") {
      if (!currentUser) {
        openLoginModal();
        return;
      }
      if (currentUser.role !== "admin") {
        alert("Hanya admin yang dapat mengakses panel ini.");
        return;
      }
      // Sembunyikan section lain yang mungkin terlihat
      const umkmSection = document.getElementById("umkm");
      if (umkmSection) umkmSection.style.display = "none";
      // Tampilkan section admin sebelum scroll
      if (targetSection) {
        targetSection.style.display = "block";
      }
    }

    // Akses ke UMKM Form (Protected - hanya umkm)
    if (targetId === "#umkm") {
      if (!currentUser) {
        openLoginModal();
        return;
      }
      if (currentUser.role !== "umkm") {
        alert("Hanya akun UMKM yang dapat mengakses halaman ini.");
        return;
      }
      // Sembunyikan section lain yang mungkin terlihat
      const adminSection = document.getElementById("admin");
      if (adminSection) adminSection.style.display = "none";
      // Tampilkan section umkm sebelum scroll
      if (targetSection) {
        targetSection.style.display = "block";
      }
    }

    // Update active class
    document
      .querySelectorAll(".nav-link")
      .forEach((l) => l.classList.remove("active"));
    // Find the specific link and make it active (since some links share the same href, e.g., in mobile menu)
    document
      .querySelector(`.nav-menu li a[href="${href}"]`)
      .classList.add("active");

    // Close mobile menu
    const navMenu = document.getElementById("navMenu");
    if (navMenu) navMenu.classList.remove("active");

    // Smooth scroll
    if (targetSection) {
      // Tunggu sebentar agar display:block diterapkan dulu
      setTimeout(() => {
        targetSection.scrollIntoView({
          behavior: "smooth",
          block: "start",
        });
      }, 50);
    }
  }
}

function handleMobileNavToggle() {
  const navToggle = document.getElementById("navToggle");
  const navMenu = document.getElementById("navMenu");
  if (navToggle && navMenu) {
    navToggle.addEventListener("click", () => {
      navMenu.classList.toggle("active");
    });
  }
}

// Highlight active section on scroll
function highlightActiveSection() {
  const sections = document.querySelectorAll("section[id]");
  // Offset untuk memastikan navbar tidak menutupi
  const scrollPosition = window.scrollY + 100;

  sections.forEach((section) => {
    const sectionTop = section.offsetTop;
    const sectionHeight = section.offsetHeight;
    const sectionId = section.getAttribute("id");

    if (
      scrollPosition >= sectionTop &&
      scrollPosition < sectionTop + sectionHeight
    ) {
      document.querySelectorAll(".nav-link").forEach((link) => {
        link.classList.remove("active");
        if (link.getAttribute("href") === `#${sectionId}`) {
          link.classList.add("active");
        }
      });
    }
  });
}

// ===============================================
// 4. Initialization (Event Listeners Setup)
// ===============================================

document.addEventListener("DOMContentLoaded", () => {
  // A. Attach Modal Event Listeners
  const closeModal = document.getElementById("closeModal");
  const showRegisterBtn = document.getElementById("showRegister");
  const showLoginBtn = document.getElementById("showLogin");

  if (closeModal) closeModal.addEventListener("click", closeLoginModal);
  if (loginModal) {
    // Close modal when clicking outside the content
    loginModal.addEventListener("click", (e) => {
      if (e.target === loginModal) {
        closeLoginModal();
      }
    });
  }
  if (showRegisterBtn)
    showRegisterBtn.addEventListener("click", (e) => {
      e.preventDefault();
      showRegisterFormInModal();
    });
  if (showLoginBtn)
    showLoginBtn.addEventListener("click", (e) => {
      e.preventDefault();
      showLoginFormInModal();
    });

  // B. Attach Form Submit Handlers
  const verificationForm = document.getElementById("verificationForm");
  const contactForm = document.getElementById("contactForm");
  const umkmProductForm = document.getElementById("umkmProductForm");

  if (loginForm) loginForm.addEventListener("submit", handleLoginSubmit);
  if (registerForm)
    registerForm.addEventListener("submit", handleRegisterSubmit);
  if (verificationForm)
    verificationForm.addEventListener("submit", handleVerificationSubmit);
  if (contactForm) contactForm.addEventListener("submit", handleContactSubmit);
  if (umkmProductForm)
    umkmProductForm.addEventListener("submit", handleUmkmProductSubmit);

  // Admin: Tab switching dan refresh buttons
  document.querySelectorAll(".admin-tab-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      const tabName = btn.getAttribute("data-tab");
      handleAdminTabSwitch(tabName);
    });
  });

  const refreshPendingBtn = document.getElementById("refreshPendingBtn");
  if (refreshPendingBtn) {
    refreshPendingBtn.addEventListener("click", () => {
      loadPendingProducts();
    });
  }

  const refreshVerifiedBtn = document.getElementById("refreshVerifiedBtn");
  if (refreshVerifiedBtn) {
    refreshVerifiedBtn.addEventListener("click", () => {
      loadVerifiedProducts();
    });
  }

  // C. Attach CTA & Navigation Handlers
  document.getElementById("heroCTA").addEventListener("click", () => {
    if (currentUser) {
      // Scroll ke bagian verifikasi jika sudah login
      document
        .getElementById("verifikasi")
        .scrollIntoView({ behavior: "smooth" });
    } else {
      // Tampilkan modal login jika belum login
      openLoginModal();
    }
  });
  document.getElementById("aboutCTA").addEventListener("click", openLoginModal);
  document
    .getElementById("overlayLoginBtn")
    .addEventListener("click", openLoginModal);

  // Smooth scrolling for all nav links
  document.querySelectorAll(".nav-link").forEach((link) => {
    link.addEventListener("click", handleNavScroll);
  });

  // D. Mobile Menu & Scroll Highlight
  handleMobileNavToggle();
  window.addEventListener("scroll", highlightActiveSection);
  highlightActiveSection(); // Run once on load to set initial active state

  // Jika sudah login sebagai admin, muat data pending setelah DOM siap
  if (currentUser && currentUser.role === "admin") {
    // Default tab adalah pending
    handleAdminTabSwitch("pending");
  }
});

// Initialize on page load
window.addEventListener("load", () => {
  window.scrollTo(0, 0);
  checkLoginStatus(); // Cek status login saat halaman dimuat
});
