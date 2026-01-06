-- Update schema untuk menambahkan kolom certification_type dan certification_link
-- Jalankan file ini jika database sudah ada dan perlu diupdate

USE umkm_checker;

-- Tambah kolom certification_type jika belum ada
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS certification_type VARCHAR(50) NULL AFTER owner_id;

-- Tambah kolom certification_link jika belum ada
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS certification_link TEXT NULL AFTER certification_type;

