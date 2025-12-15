# Web-Based Transaction and Payroll Information System

Sistem Informasi Transaksi dan Penggajian Berbasis Web ini dikembangkan untuk mendigitalisasi proses pencatatan transaksi jasa serta mengotomatisasi perhitungan upah harian karyawan pada UKM **Satria Bima Wash**.

Proyek ini merupakan **Tugas Akhir** untuk memperoleh gelar **Ahli Madya Komputer** pada Program Studi **Komputerisasi Akuntansi**, **STMIK IKMI Cirebon**.

---

## 📌 Latar Belakang

Satria Bima Wash menghadapi kendala dalam pencatatan transaksi yang belum terintegrasi, di mana proses pencatatan masih dilakukan secara bergantian antara buku besar dan Microsoft Excel. Selain itu, perhitungan upah karyawan dilakukan secara manual sehingga berpotensi menimbulkan kesalahan.

Sistem ini dikembangkan sebagai solusi terpusat untuk menyimpan data transaksi secara akurat dan mengotomatisasi perhitungan upah karyawan berbasis sistem bagi hasil yang terintegrasi dengan data absensi.

---

## 🚀 Fitur Utama

- **Pengelolaan Transaksi Jasa**  \
  Pencatatan detail kendaraan (plat nomor, merek, warna), jenis layanan, serta tarif secara real-time.

- **Sistem Absensi Karyawan**  \
  Pencatatan jam masuk dan jam pulang karyawan sebagai dasar perhitungan upah harian.

- **Otomasi Perhitungan Upah**  \
  Perhitungan pendapatan karyawan secara otomatis berdasarkan sistem bagi hasil untuk meminimalisir kesalahan manual.

- **Manajemen Laporan**  \
  Pembuatan laporan penggajian yang dapat diekspor ke format **PDF**, **Excel**, dan **CSV**.

- **Manajemen Data Master**  \
  Fitur CRUD (*Create, Read, Update, Delete*) untuk data karyawan, jenis layanan, dan program promo.

---

## 🛠️ Stack Teknologi

- **Bahasa Pemrograman**: PHP  
- **Database**: MySQL  
- **Antarmuka**: HTML, Bootstrap (CSS), JavaScript  
- **Metodologi Pengembangan**: SDLC – Rational Unified Process (RUP)  
- **Web Server Lokal**: XAMPP

---

## 📊 Pemodelan Sistem & Data

Sistem dirancang menggunakan pemodelan UML untuk memastikan struktur dan alur sistem yang stabil:

- **Use Case Diagram**  \
  Menjelaskan hak akses pengguna:
  - **Admin**: Akses penuh terhadap sistem
  - **Pemilik**: Akses terbatas pada laporan

- **Entity Relationship Diagram (ERD)**  \
  Terdiri dari tabel utama:
  - `user`
  - `karyawan`
  - `absensi`
  - `jenis_layanan`
  - `kendaraan`
  - `pendapatan_harian`
  - `promo`
  - `upah_harian`
  - `transaksi_karyawan`

---

## ⚙️ Instalasi & Penggunaan

### 1. Persiapan
Pastikan **XAMPP** (direkomendasikan versi 8.2.4) telah terinstal pada perangkat.

### 2. Konfigurasi Database
1. Buka **phpMyAdmin**
2. Buat database baru dengan nama:
   ```
   db_sbw_management
   ```
3. Impor file `.sql` yang tersedia di dalam folder proyek

### 3. Deploy Aplikasi
Pindahkan seluruh folder proyek ke direktori:
```
C:/xampp/htdocs/
```

### 4. Akses Sistem
Buka browser dan akses:
```
http://localhost/sbw
```
Login menggunakan akun **Admin** untuk mendapatkan akses penuh ke sistem.

---

## 📈 Hasil Implementasi

Berdasarkan pengujian menggunakan metode **Black Box Testing**, sistem ini berhasil menjalankan seluruh fungsi dengan status **"OK"**, meliputi:

- Login pengguna
- Input dan pengelolaan transaksi
- Pencatatan absensi
- Perhitungan upah otomatis
- Pembuatan dan pencetakan laporan penggajian

---

## 📄 Lisensi

Proyek ini dikembangkan untuk keperluan akademik dan internal UKM Satria Bima Wash.

---

## 📸 Dokumentasi Antarmuka (UI)
Sistem ini dirancang dengan antarmuka yang intuitif untuk memudahkan pengelolaan operasional harian:

| Login Page | Dashboard Utama |
|---|---|
| ![Login](img/Login%20Page.png) | ![Dashboard](img/Dashboard%20Utama.png) |

| Transaksi Jasa | Absensi Karyawan |
|---|---|
| ![Transaksi](img/Halaman%20Transaksi%20Jasa%20Pencucian%20Kendaraan.png) | ![Absensi](img/Halaman%20Absensi%20Karyawan.png) |

| Perhitungan Upah | Laporan Penggajian |
|---|---|
| ![Upah](img/Halaman%20Perhitungan%20Upah%20Harian%20Karyawan.png) | ![Laporan](img/Halaman%20Laporan%20Penggajian%20Karyawan.png) |
