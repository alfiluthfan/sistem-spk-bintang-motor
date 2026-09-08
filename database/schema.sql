-- =====================================================================
-- Database: db_spk_bintang_motor
-- Sistem Pendukung Keputusan Penentuan Produk Unggulan
-- Dealer Bintang Motor Cinere (Metode AHP - TOPSIS)
-- Dibuat berdasarkan ERD & Struktur Tabel (Subbab 3.5.6)
-- DBMS: MySQL (XAMPP / phpMyAdmin)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS db_spk_bintang_motor
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE db_spk_bintang_motor;

-- Nonaktifkan sementara pengecekan FK agar urutan drop table aman saat re-run
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS detail_hasil_topsis;
DROP TABLE IF EXISTS hasil_topsis;
DROP TABLE IF EXISTS detail_bobot_ahp;
DROP TABLE IF EXISTS perbandingan_ahp;
DROP TABLE IF EXISTS hasil_ahp;
DROP TABLE IF EXISTS nilai_alternatif;
DROP TABLE IF EXISTS alternatif;
DROP TABLE IF EXISTS kriteria;
DROP TABLE IF EXISTS periode_penilaian;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- 1. Tabel users
-- =====================================================================
CREATE TABLE users (
    id_user     INT(11) NOT NULL AUTO_INCREMENT,
    nama        VARCHAR(100) NOT NULL,
    username    VARCHAR(50) NOT NULL,
    password    VARCHAR(255) NOT NULL,
    role        VARCHAR(30) NOT NULL COMMENT 'Staf/Admin IT atau Manajemen',
    PRIMARY KEY (id_user),
    UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- 2. Tabel periode_penilaian
-- =====================================================================
CREATE TABLE periode_penilaian (
    id_periode       INT(11) NOT NULL AUTO_INCREMENT,
    nama_periode     VARCHAR(100) NOT NULL,
    tanggal_mulai    DATE NOT NULL,
    tanggal_selesai  DATE NOT NULL,
    status           VARCHAR(20) NOT NULL COMMENT 'Aktif / Selesai',
    PRIMARY KEY (id_periode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- 3. Tabel kriteria
-- =====================================================================
CREATE TABLE kriteria (
    id_kriteria     INT(11) NOT NULL AUTO_INCREMENT,
    kode_kriteria   VARCHAR(10) NOT NULL COMMENT 'Contoh: C1, C2, C3, C4',
    nama_kriteria   VARCHAR(100) NOT NULL,
    jenis_kriteria  VARCHAR(10) NOT NULL COMMENT 'Benefit atau Cost',
    PRIMARY KEY (id_kriteria),
    UNIQUE KEY uq_kriteria_kode (kode_kriteria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- 4. Tabel alternatif
-- =====================================================================
CREATE TABLE alternatif (
    id_alternatif    INT(11) NOT NULL AUTO_INCREMENT,
    kode_alternatif  VARCHAR(10) NOT NULL,
    nama_alternatif  VARCHAR(100) NOT NULL,
    merek            VARCHAR(50) NULL COMMENT 'Contoh: Honda, Yamaha',
    tipe             VARCHAR(30) NULL COMMENT 'Contoh: Matik, Sport, Bebek',
    PRIMARY KEY (id_alternatif),
    UNIQUE KEY uq_alternatif_kode (kode_alternatif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- 5. Tabel nilai_alternatif
-- =====================================================================
CREATE TABLE nilai_alternatif (
    id_nilai       INT(11) NOT NULL AUTO_INCREMENT,
    id_periode     INT(11) NOT NULL,
    id_alternatif  INT(11) NOT NULL,
    id_kriteria    INT(11) NOT NULL,
    nilai          DECIMAL(18,2) NOT NULL,
    PRIMARY KEY (id_nilai),
    KEY idx_nilai_periode (id_periode),
    KEY idx_nilai_alternatif (id_alternatif),
    KEY idx_nilai_kriteria (id_kriteria),
    CONSTRAINT fk_nilai_periode
        FOREIGN KEY (id_periode) REFERENCES periode_penilaian (id_periode)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_nilai_alternatif
        FOREIGN KEY (id_alternatif) REFERENCES alternatif (id_alternatif)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_nilai_kriteria
        FOREIGN KEY (id_kriteria) REFERENCES kriteria (id_kriteria)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- 6. Tabel hasil_ahp
-- =====================================================================
CREATE TABLE hasil_ahp (
    id_hasil_ahp        INT(11) NOT NULL AUTO_INCREMENT,
    id_periode          INT(11) NOT NULL,
    id_user             INT(11) NOT NULL,
    lambda_max          DECIMAL(18,10) NULL,
    consistency_index   DECIMAL(18,10) NULL,
    consistency_ratio   DECIMAL(18,10) NULL,
    status_konsistensi  VARCHAR(20) NULL COMMENT 'Konsisten / Tidak Konsisten',
    tanggal_proses      DATETIME NOT NULL,
    PRIMARY KEY (id_hasil_ahp),
    KEY idx_ahp_periode (id_periode),
    KEY idx_ahp_user (id_user),
    CONSTRAINT fk_ahp_periode
        FOREIGN KEY (id_periode) REFERENCES periode_penilaian (id_periode)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_ahp_user
        FOREIGN KEY (id_user) REFERENCES users (id_user)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- 7. Tabel perbandingan_ahp
-- =====================================================================
CREATE TABLE perbandingan_ahp (
    id_perbandingan     INT(11) NOT NULL AUTO_INCREMENT,
    id_hasil_ahp        INT(11) NOT NULL,
    id_kriteria_1       INT(11) NOT NULL,
    id_kriteria_2       INT(11) NOT NULL,
    nilai_perbandingan  DECIMAL(10,6) NOT NULL,
    PRIMARY KEY (id_perbandingan),
    KEY idx_pb_hasil_ahp (id_hasil_ahp),
    KEY idx_pb_kriteria1 (id_kriteria_1),
    KEY idx_pb_kriteria2 (id_kriteria_2),
    CONSTRAINT fk_pb_hasil_ahp
        FOREIGN KEY (id_hasil_ahp) REFERENCES hasil_ahp (id_hasil_ahp)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_pb_kriteria1
        FOREIGN KEY (id_kriteria_1) REFERENCES kriteria (id_kriteria)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_pb_kriteria2
        FOREIGN KEY (id_kriteria_2) REFERENCES kriteria (id_kriteria)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- 8. Tabel detail_bobot_ahp
-- =====================================================================
CREATE TABLE detail_bobot_ahp (
    id_detail_bobot  INT(11) NOT NULL AUTO_INCREMENT,
    id_hasil_ahp     INT(11) NOT NULL,
    id_kriteria      INT(11) NOT NULL,
    bobot            DECIMAL(18,10) NOT NULL,
    PRIMARY KEY (id_detail_bobot),
    KEY idx_bobot_hasil_ahp (id_hasil_ahp),
    KEY idx_bobot_kriteria (id_kriteria),
    CONSTRAINT fk_bobot_hasil_ahp
        FOREIGN KEY (id_hasil_ahp) REFERENCES hasil_ahp (id_hasil_ahp)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_bobot_kriteria
        FOREIGN KEY (id_kriteria) REFERENCES kriteria (id_kriteria)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- 9. Tabel hasil_topsis
-- =====================================================================
CREATE TABLE hasil_topsis (
    id_hasil_topsis  INT(11) NOT NULL AUTO_INCREMENT,
    id_periode       INT(11) NOT NULL,
    id_hasil_ahp     INT(11) NOT NULL,
    tanggal_proses   DATETIME NOT NULL,
    PRIMARY KEY (id_hasil_topsis),
    KEY idx_topsis_periode (id_periode),
    KEY idx_topsis_hasil_ahp (id_hasil_ahp),
    CONSTRAINT fk_topsis_periode
        FOREIGN KEY (id_periode) REFERENCES periode_penilaian (id_periode)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_topsis_hasil_ahp
        FOREIGN KEY (id_hasil_ahp) REFERENCES hasil_ahp (id_hasil_ahp)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- 10. Tabel detail_hasil_topsis
-- =====================================================================
CREATE TABLE detail_hasil_topsis (
    id_detail_topsis  INT(11) NOT NULL AUTO_INCREMENT,
    id_hasil_topsis   INT(11) NOT NULL,
    id_alternatif     INT(11) NOT NULL,
    jarak_positif     DECIMAL(18,10) NOT NULL,
    jarak_negatif     DECIMAL(18,10) NOT NULL,
    nilai_preferensi  DECIMAL(18,10) NOT NULL,
    peringkat         INT(11) NOT NULL,
    PRIMARY KEY (id_detail_topsis),
    KEY idx_detail_topsis_hasil (id_hasil_topsis),
    KEY idx_detail_topsis_alternatif (id_alternatif),
    CONSTRAINT fk_detail_topsis_hasil
        FOREIGN KEY (id_hasil_topsis) REFERENCES hasil_topsis (id_hasil_topsis)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_detail_topsis_alternatif
        FOREIGN KEY (id_alternatif) REFERENCES alternatif (id_alternatif)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- (Opsional) Data awal contoh: akun admin & kriteria dasar
-- Hapus/ubah sesuai kebutuhan sebelum dijalankan di production
-- =====================================================================
-- INSERT INTO users (nama, username, password, role)
-- VALUES ('Administrator', 'admin', '$2y$10$contohHashPasswordDisiniYaXXXXXXXXXXXXXXXX', 'Admin IT');

-- INSERT INTO kriteria (kode_kriteria, nama_kriteria, jenis_kriteria) VALUES
-- ('C1', 'Harga', 'Cost'),
-- ('C2', 'Kualitas', 'Benefit'),
-- ('C3', 'Penjualan', 'Benefit'),
-- ('C4', 'Stok', 'Benefit');
