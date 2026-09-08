-- =====================================================================
-- Seed contoh (opsional) — sesuai skenario pada wireframe Dashboard Staf.
-- Jalankan setelah schema.sql (dan migrasi 002) diimpor.
-- Password contoh: "rahasia123" (sudah di-hash dengan password_hash()).
-- =====================================================================

USE db_spk_bintang_motor;

INSERT INTO users (nama, username, password, role) VALUES
('Muhammad Zaenun', 'staf', 'rahasia123', 'Staf Penjualan'),
('Kepala Cabang', 'manajemen', 'rahasia123', 'Manajemen');

-- INSERT INTO periode_penilaian (nama_periode, tanggal_mulai, tanggal_selesai, status) VALUES
-- ('Penilaian Triwulan III 2026', '2026-07-01', '2026-09-30', 'Aktif');

-- INSERT INTO kriteria (kode_kriteria, nama_kriteria, jenis_kriteria) VALUES
-- ('C1', 'Harga', 'Cost'),
-- ('C2', 'Kualitas', 'Benefit'),
-- ('C3', 'Penjualan', 'Benefit'),
-- ('C4', 'Stok', 'Benefit');

-- INSERT INTO alternatif (kode_alternatif, nama_alternatif, merek, tipe) VALUES
-- ('A1', 'Beat', 'Honda', 'Matik'),
-- ('A2', 'Vario', 'Yamaha', 'Matik'),
-- ('A3', 'Scoopy', 'Honda', 'Matik'),
-- ('A4', 'PCX', 'Yamaha', 'Matik');

-- -- Nilai lengkap untuk Beat, Vario, PCX (semua kriteria terisi)
-- INSERT INTO nilai_alternatif (id_periode, id_alternatif, id_kriteria, nilai)
-- SELECT 1, a.id_alternatif, k.id_kriteria, 75.00
-- FROM alternatif a
-- JOIN kriteria k
-- WHERE a.kode_alternatif IN ('A1', 'A2', 'A4');

-- -- Nilai belum lengkap untuk Scoopy (baru 2 dari 4 kriteria terisi)
-- INSERT INTO nilai_alternatif (id_periode, id_alternatif, id_kriteria, nilai)
-- SELECT 1, a.id_alternatif, k.id_kriteria, 70.00
-- FROM alternatif a
-- JOIN kriteria k
-- WHERE a.kode_alternatif = 'A3' AND k.kode_kriteria IN ('C1', 'C2');
