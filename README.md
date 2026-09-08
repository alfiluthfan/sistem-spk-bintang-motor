# Sistem Pendukung Keputusan — Halaman Login

Implementasi halaman login (PHP + Tailwind CSS lokal) untuk SPK Penentuan
Produk Unggulan, Dealer Bintang Motor Cinere.

## Struktur Proyek

```
sistem-spk/
├── assets/css/
│   ├── input.css              # sumber Tailwind (@tailwind base/components/utilities)
│   └── app.css                # hasil build (JANGAN diedit manual)
├── config/
│   └── Database.php           # koneksi PDO ke MySQL (XAMPP)
├── src/
│   ├── Auth.php                # logika autentikasi, session, & role
│   └── DashboardStafService.php # query ringkasan untuk Dashboard Staf
├── partials/
│   ├── sidebar_staf.php        # sidebar navigasi area Staf
│   └── topbar.php              # breadcrumb + info pengguna
├── dashboard/
│   ├── staf/
│   │   ├── index.php            # Dashboard Staf (implementasi penuh)
│   │   ├── periode.php          # stub Data Periode Penilaian
│   │   ├── kriteria.php         # stub Data Kriteria
│   │   ├── alternatif.php       # stub Data Alternatif
│   │   ├── nilai.php            # stub Nilai Alternatif
│   │   └── hasil.php            # stub Hasil Perangkingan
│   └── manajemen/
│       └── index.php            # placeholder dashboard Manajemen
├── database/
│   ├── schema.sql               # skema lengkap (untuk instalasi baru)
│   ├── migrations/
│   │   └── 002_add_merek_tipe_to_alternatif.sql
│   └── seed_contoh.sql          # data contoh sesuai skenario wireframe
├── login.php                    # halaman + proses login
├── logout.php
├── package.json
└── tailwind.config.js
```

Halaman `periode.php`, `kriteria.php`, `alternatif.php`, `nilai.php`, dan
`hasil.php` sengaja dibuat sebagai stub (placeholder) agar seluruh menu di
sidebar bisa diklik tanpa error 404, dan siap diisi implementasinya secara
bertahap di percakapan selanjutnya.

## 1. Siapkan Basis Data

**Instalasi baru:** import `database/schema.sql` lewat phpMyAdmin — kolom
`merek` dan `tipe` pada tabel `alternatif` sudah termasuk di dalamnya (kolom
ini ditambahkan karena dibutuhkan tampilan Dashboard Staf, namun belum ada
pada rancangan tabel awal di Bab 3).

**Sudah pernah import sebelumnya?** cukup jalankan migrasi tambahan:

```
database/migrations/002_add_merek_tipe_to_alternatif.sql
```

Setelah itu, jalankan `database/seed_contoh.sql` (opsional) untuk mengisi
data contoh — 1 periode aktif, 4 kriteria, 4 alternatif motor, dan nilai yang
sengaja dibuat belum lengkap untuk salah satu alternatif (Scoopy), persis
seperti skenario pada wireframe Dashboard Staf. Akun contoh yang dibuat:

| Username     | Password      | Role            |
|--------------|---------------|-----------------|
| `staf`       | `rahasia123`  | Staf Penjualan  |
| `manajemen`  | `rahasia123`  | Manajemen       |

Password **wajib** disimpan dalam bentuk hash (`password_hash`), bukan teks
biasa. Untuk membuat akun sendiri, hash password lewat PHP:

```bash
php -r "echo password_hash('rahasia123', PASSWORD_DEFAULT);"
```

Lalu insert manual, misalnya:

```sql
INSERT INTO users (nama, username, password, role)
VALUES ('Admin IT', 'admin', '<hasil_hash_disini>', 'Admin IT');
```

## 2. Sesuaikan Koneksi Database

Buka `config/Database.php` dan sesuaikan konstanta `HOST`, `NAME`, `USERNAME`,
`PASSWORD` jika konfigurasi MySQL XAMPP kamu berbeda dari default
(`root` tanpa password).

## 3. Install & Build Tailwind CSS (lokal, bukan CDN)

Pastikan Node.js sudah terpasang, lalu dari folder proyek jalankan:

```bash
npm install
npm run build     # build sekali (minified) -> assets/css/app.css
# atau, saat development:
npm run watch      # rebuild otomatis setiap file PHP berubah
```

`tailwind.config.js` sudah diarahkan untuk memindai seluruh file `.php` di
root, `dashboard/`, dan `src/`, jadi class Tailwind yang kamu tambahkan di
file-file itu akan otomatis ikut ter-build.

## 4. Jalankan di XAMPP

1. Salin folder `sistem-spk/` ke dalam `htdocs/`.
2. Jalankan Apache & MySQL dari XAMPP Control Panel.
3. Akses `http://localhost/sistem-spk/login.php`.

## Alur Login

1. Pengguna mengisi username & password pada `login.php`.
2. `Auth::attemptLogin()` mencocokkan data ke tabel `users` (password dicek
   dengan `password_verify`).
3. Jika cocok, session dibuat dan pengguna diarahkan sesuai role:
   - Role mengandung "Manajemen"/"Pimpinan"/"Kepala Cabang" → `dashboard/manajemen.php`
   - Role lainnya (Staf Penjualan/Admin IT) → `dashboard/staf.php`
4. Jika tidak cocok, pesan **"Username atau password salah"** ditampilkan
   kembali di halaman login tanpa membocorkan field mana yang salah.

## Alur Dashboard Staf

`dashboard/staf/index.php` menampilkan:

- **Total Alternatif** & **Total Kriteria** — hasil `COUNT(*)` dari tabel
  `alternatif` dan `kriteria`.
- **Nilai Terisi** — jumlah baris `nilai_alternatif` pada periode berstatus
  `Aktif`, dibandingkan dengan total yang seharusnya (`total_alternatif Ã—
  total_kriteria`).
- **Ringkasan Data Alternatif** — status *Lengkap* jika seluruh kriteria untuk
  alternatif tersebut sudah punya nilai pada periode aktif, selain itu
  *Belum Lengkap*.

Jika belum ada periode berstatus `Aktif`, dashboard menampilkan peringatan dan
seluruh status nilai dianggap belum lengkap (karena tidak ada periode acuan).
Setiap halaman staf (`dashboard/staf/*.php`) memeriksa role: pengguna dengan
role Manajemen otomatis diarahkan ke `dashboard/manajemen/index.php`, karena
staf tidak diberi akses ke pembobotan AHP (sesuai batasan hak akses pada Bab 4).

## Catatan Keamanan

- Query menggunakan **prepared statement** (PDO) untuk mencegah SQL Injection.
- Password disimpan dan diverifikasi dengan `password_hash` / `password_verify`,
  bukan disimpan sebagai teks biasa maupun di-hash manual dengan MD5/SHA1.
- `session_regenerate_id(true)` dipanggil setelah login berhasil untuk
  mencegah session fixation.
- Output ke HTML (`nama`, pesan error, dll.) selalu melalui `htmlspecialchars()`
  untuk mencegah XSS.
