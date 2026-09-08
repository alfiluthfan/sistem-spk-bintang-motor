-- =====================================================================
-- Migrasi: 002_add_merek_tipe_to_alternatif
-- Tujuan : Menambahkan kolom `merek` dan `tipe` pada tabel `alternatif`.
--
-- Kenapa perlu?
-- Dashboard Staf menampilkan kolom "Merek" dan "Tipe" pada ringkasan
-- data alternatif (mis. Honda / Beat / Matik), namun skema awal
-- (Tabel 3.41) hanya menyimpan kode_alternatif & nama_alternatif.
-- Kolom dibuat NULLABLE agar migrasi aman dijalankan di database yang
-- sudah berisi data tanpa menyebabkan error NOT NULL.
--
-- Jalankan file ini via phpMyAdmin (tab Import/SQL) pada database
-- db_spk_bintang_motor yang sudah ada. Jika kamu baru mulai dari nol,
-- cukup jalankan db_spk_bintang_motor.sql yang sudah memuat kolom ini.
-- =====================================================================

USE db_spk_bintang_motor;

ALTER TABLE alternatif
    ADD COLUMN merek VARCHAR(50) NULL AFTER nama_alternatif,
    ADD COLUMN tipe  VARCHAR(30) NULL AFTER merek;
