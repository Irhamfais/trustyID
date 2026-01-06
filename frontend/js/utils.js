function formatDate(dateString) {
  const options = { year: "numeric", month: "long", day: "numeric" };
  const date = new Date(dateString);
  return date.toLocaleDateString("id-ID", options);
}

function animateValue(element, start, end, duration) {
  let startTimestamp = null;
  const step = (timestamp) => {
    if (!startTimestamp) startTimestamp = timestamp;
    const progress = Math.min((timestamp - startTimestamp) / duration, 1);
    const value = Math.floor(progress * (end - start) + start);
    element.textContent = value + "%";
    if (progress < 1) {
      window.requestAnimationFrame(step);
    }
  };
  window.requestAnimationFrame(step);
}

function updateQualityBar(barId, scoreId, value) {
  const bar = document.getElementById(barId);
  const scoreElement = document.getElementById(scoreId);

  // Jika elemen tidak ada (misalnya markup sederhana tanpa progress bar),
  // cukup keluar tanpa error agar fungsi lain tetap berjalan.
  if (!bar || !scoreElement) {
    return;
  }

  // Reset bar untuk animasi
  bar.style.width = "0%";
  scoreElement.textContent = "0%";

  // Animasi setelah delay
  setTimeout(() => {
    bar.style.width = value + "%";
    animateValue(scoreElement, 0, value, 1000);
  }, 100);
}

function displayResult(product, code) {
  // Ambil elemen yang diperlukan
  const resultContainer = document.getElementById("resultContainer");
  const statusBadge = document.getElementById("statusBadge");

  if (!resultContainer || !statusBadge) {
    console.error("Elemen hasil verifikasi tidak ditemukan di halaman.");
    alert("Terjadi kesalahan pada tampilan hasil verifikasi.");
    return;
  }

  // Tampilkan container hasil
  resultContainer.style.display = "block";

  // Update status badge
  if (product.status === "verified") {
    statusBadge.textContent = "TERVERIFIKASI";
    statusBadge.className = "status-badge status-verified";
  } else {
    statusBadge.textContent = "KEDALUWARSA";
    statusBadge.className = "status-badge status-expired";
  }

  // Update informasi produk dasar dengan pengecekan elemen
  const productNameEl = document.getElementById("productName");
  const regNumberEl = document.getElementById("regNumber");
  const producerEl = document.getElementById("producer");
  const expiryDateEl = document.getElementById("expiryDate");

  if (productNameEl) productNameEl.textContent = product.name || "";
  if (regNumberEl) regNumberEl.textContent = code || "";
  if (producerEl) producerEl.textContent = product.producer || "";
  if (expiryDateEl)
    expiryDateEl.textContent = product.expiryDate
      ? formatDate(product.expiryDate)
      : "";

  // Elemen sertifikasi: dukung id lama 'certifications' dan id baru 'certification'
  const certificationsElement =
    document.getElementById("certifications") ||
    document.getElementById("certification");
  if (certificationsElement) {
    // Terima data dalam bentuk array maupun string (fallback)
    let certs = [];
    if (Array.isArray(product.certifications)) {
      certs = product.certifications;
    } else if (product.certifications) {
      certs = String(product.certifications)
        .split(",")
        .map((c) => c.trim())
        .filter(Boolean);
    }

    certificationsElement.textContent = certs.length
      ? certs.join(", ")
      : "Tidak ada sertifikasi terdaftar";
  }

  // Update quality bars hanya jika data & elemen tersedia
  if (product.ratings) {
    updateQualityBar("hygieneBar", "hygieneScore", product.ratings.hygiene);
    updateQualityBar("qualityBar", "qualityScore", product.ratings.quality);
    updateQualityBar("trustBar", "trustScore", product.ratings.trust);
  }

  // Scroll ke hasil
  setTimeout(() => {
    resultContainer.scrollIntoView({
      behavior: "smooth",
      block: "nearest",
    });
  }, 100);
}
