// data.js

// Database produk simulasi
const productDatabase = {
  "BPOM-12345": {
    name: "Keripik Singkong Original",
    producer: "UMKM Berkah Jaya",
    expiryDate: "2026-12-31",
    status: "verified",
    certifications: ["BPOM", "Halal MUI"],
    ratings: {
      hygiene: 95,
      quality: 92,
      trust: 98,
    },
  },
  "HALAL-67890": {
    name: "Sambal Khas Nusantara",
    producer: "UMKM Pedas Mantap",
    expiryDate: "2026-08-15",
    status: "verified",
    certifications: ["Halal MUI", "BPOM"],
    ratings: {
      hygiene: 88,
      quality: 90,
      trust: 85,
    },
  },
  "BPOM-99999": {
    name: "Kue Tradisional Kering",
    producer: "UMKM Manis Lezat",
    expiryDate: "2024-06-30",
    status: "expired",
    certifications: ["BPOM"],
    ratings: {
      hygiene: 75,
      quality: 70,
      trust: 65,
    },
  },
  "HALAL-11111": {
    name: "Dodol Durian Premium",
    producer: "UMKM Durian Sejati",
    expiryDate: "2027-03-20",
    status: "verified",
    certifications: ["Halal MUI", "BPOM"],
    ratings: {
      hygiene: 97,
      quality: 95,
      trust: 96,
    },
  },
};

// Database user simulasi: Email sebagai key
const usersDatabase = {
  // AKUN DUMMY
  "user@test.com": {
    name: "Konsumen Uji Coba",
    password: "123456",
    email: "user@test.com",
  },
  "umkm@berkah.com": {
    name: "UMKM Berkah",
    password: "passwordumkm",
    email: "umkm@berkah.com",
  },
};

// Session Management (Variabel Global)
let currentUser = null;
