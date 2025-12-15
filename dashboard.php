<?php
session_start();

// Memastikan admin sudah login
if (!isset($_SESSION['id_user'])) {
    header('Location: index.php');
    exit();
}

include 'config/koneksi.php';

// Mendapatkan total karyawan yang masuk hari ini
$result = $conn->query("SELECT COUNT(DISTINCT id_karyawan) AS total_karyawan FROM absensi WHERE tanggal = CURDATE()");
$total_karyawan = $result->fetch_assoc()['total_karyawan'] ?? 0;

// Mendapatkan total transaksi jasa (kendaraan yang dicuci) hari ini
$result = $conn->query("SELECT COUNT(*) AS total_transaksi FROM pendapatan_harian WHERE tanggal_transaksi = CURDATE()");
$total_kendaraan = $result->fetch_assoc()['total_transaksi'] ?? 0;

// Mendapatkan total pendapatan hari ini
$result = $conn->query("SELECT SUM(total_pendapatan) AS total_pendapatan FROM pendapatan_harian WHERE tanggal_transaksi = CURDATE()");
$total_pendapatan = $result->fetch_assoc()['total_pendapatan'] ?? 0;

// Mendapatkan nama lengkap dan role pengguna dari sesi
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];

// Tentukan aksesibilitas menu berdasarkan role
$canAccessDataMaster = ($role === 'admin' || $role === 'pemilik');
$canAccessLaporan = ($role === 'admin' || $role === 'pemilik');
$canAccessTransaksi = ($role === 'admin');

// // Mendapatkan 5 antrian terbaru untuk hari ini
// $today = date('Y-m-d');
// $latest_antrian = $conn->query("
//     SELECT k.plat_nomor, k.merk, k.warna, bk.tanggal_booking, bk.jam_booking, bk.status_booking
//     FROM booking_kendaraan bk
//     JOIN kendaraan k ON bk.id_kendaraan = k.id_kendaraan
//     WHERE bk.tanggal_booking = '$today'
//     ORDER BY bk.jam_booking DESC
//     LIMIT 5
// ");

// Mendapatkan data pemasukan dan pengeluaran harian untuk grafik (selama 1 bulan)
// $sql = "
//     SELECT 
//         DATE(tanggal) AS tanggal,
//         SUM(CASE WHEN jenis_transaksi = 'Pemasukan' THEN jumlah ELSE 0 END) AS pemasukan,
//         SUM(CASE WHEN jenis_transaksi = 'Pengeluaran' THEN jumlah ELSE 0 END) AS pengeluaran
//     FROM transaksi_kas
//     WHERE tanggal BETWEEN CURDATE() - INTERVAL 1 MONTH AND CURDATE()
//     GROUP BY DATE(tanggal)
//     ORDER BY tanggal ASC
// ";
// $query = $conn->query($sql);

// // Menyiapkan data untuk grafik
// $tanggal = [];
// $pemasukan = [];
// $pengeluaran = [];

// while ($row = $query->fetch_assoc()) {
//     $tanggal[] = $row['tanggal'];
//     $pemasukan[] = $row['pemasukan'];
//     $pengeluaran[] = $row['pengeluaran'];
// }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Satria Bima Wash (SBW)</title>
    <!--- Memuat CSS Eksternal --->
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/reminder.js"></script>
</head>
<body>
    <!-- Sidebar Navigasi -->
    <div class="sidebar">
        <div class="logo-text text-white mb-2">
            SBW ADMIN
        </div>

        <!-- Informasi Pengguna yang Login -->
        <div class="d-flex align-items-center">
            <i class="fas fa-user-circle fa-2x mb-2"></i>
            <p class="mb-0"><?= htmlspecialchars($full_name); ?></p>
            <a href="process/logout.php" class="text-decoration-none text-center tooltip-action" data-tooltip="Logout">
                <i class="fas fa-sign-out-alt me-2"></i>
            </a>
        </div>

        <div class="nav-divider"></div>

        <?php if ($canAccessTransaksi): ?>
            <a href="absensi.php">Absensi</a>
            <div class="nav-divider"></div>
        <?php endif; ?>

        <div class="accordion" id="dataMasterAccordion">
        <!-- Transaksi Dropdown Menu -->
            <?php if ($canAccessTransaksi): ?>
            <div class="accordion-item bg-transparent border-0">
                <h2 class="accordion-header" id="headingTransaksi">
                    <button class="accordion-button collapsed bg-transparent ps-0" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTransaksi" aria-expanded="false" aria-controls="collapseTransaksi">
                        Transaksi
                    </button>
                </h2>
                <div id="collapseTransaksi" class="accordion-collapse collapse" aria-labelledby="headingTransaksi" data-bs-parent="#dataMasterAccordion">
                    <div class="accordion-body p-0">
                        <a href="transaksi-harian.php"><i class="fas fa-money-check-alt me-2"></i> Transaksi Jasa</a>
                        <!-- <a href="transaksi-kas.php"><i class="fas fa-book me-2"></i> Transaksi Kas</a> -->
                        <a href="upah-harian.php"><i class="fas fa-calculator me-2"></i> Hitung Upah Harian</a>
                    </div>
                </div>
            </div>
            <div class="nav-divider"></div>
            <?php endif; ?>

            <!-- Laporan Dropdown Menu -->
            <?php if ($canAccessLaporan): ?>
            <div class="accordion-item bg-transparent border-0">
                <h2 class="accordion-header" id="headingLaporan">
                    <button class="accordion-button collapsed bg-transparent ps-0" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLaporan" aria-expanded="false" aria-controls="collapseLaporan">
                        Laporan
                    </button>
                </h2>
                <div id="collapseLaporan" class="accordion-collapse collapse" aria-labelledby="headingLaporan" data-bs-parent="#dataMasterAccordion">
                    <div class="accordion-body p-0">
                        <!-- <a href="laporan-arus-kas.php"><i class="fas fa-chart-line me-2"></i> Laporan Arus Kas</a> -->
                        <a href="laporan-gaji-karyawan.php"><i class="fas fa-file-invoice-dollar me-2"></i> Laporan Penggajian</a>
                    </div>
                </div>
            </div>
            <div class="nav-divider"></div>
            <?php endif; ?>

            <!-- Data Master Dropdown Menu -->
            <?php if ($canAccessDataMaster): ?>
            <div class="accordion-item bg-transparent border-0">
                <h2 class="accordion-header" id="headingMaster">
                    <button class="accordion-button collapsed bg-transparent ps-0" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMaster" aria-expanded="false" aria-controls="collapseMaster">
                        Data Master
                    </button>
                </h2>
                <div id="collapseMaster" class="accordion-collapse collapse" aria-labelledby="headingMaster" data-bs-parent="#dataMasterAccordion">
                    <div class="accordion-body p-0">
                        <a href="data-karyawan.php" class="d-block ps-4 py-2"><i class="fas fa-users me-2"></i>Data Karyawan</a>
                        <!-- <a href="data-akun.php" class="d-block ps-4 py-2"><i class="fas fa-th-list me-2"></i>Data Akun</a> -->
                        <a href="daftar-layanan.php" class="d-block ps-4 py-2"><i class="fas fa-cogs me-2"></i>Data Layanan</a>
                        <!-- <a href="data-rekening.php" class="d-block ps-4 py-2"><i class="fas fa-credit-card me-2"></i>Data Rekening</a> -->
                        <a href="data-promo.php" class="d-block ps-4 py-2"><i class="fas fa-tags me-2"></i>Data Promo</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Konten Utama -->
    <div class="content">
        <div class="container-fluid">
            <div class="welcome-section">
                <h2>Selamat Datang, <?= htmlspecialchars($full_name); ?>!</h2>
                <p class="text-muted">Dashboard Overview - <?= date('d F Y'); ?></p>
            </div>
            <!-- Ringkasan Statistik -->
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-users me-2"></i> Total Karyawan Hari Ini
                        </div>
                        <div class="card-body">
                            <h5 class="card-title text-center fs-1"><?= $total_karyawan; ?></h5>
                            <p class="card-text text-center text-muted">Karyawan masuk hari ini</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-car me-2"></i> Total Kendaraan Hari Ini
                        </div>
                        <div class="card-body">
                            <h5 class="card-title text-center fs-1"><?= $total_kendaraan; ?></h5>
                            <p class="card-text text-center text-muted">Kendaraan dicuci hari ini</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-money-check-alt me-2"></i> Total Pendapatan Hari Ini
                        </div>
                        <div class="card-body">
                            <h5 class="card-title text-center fs-1">Rp <?= number_format($total_pendapatan, 0, ',', '.'); ?></h5>
                            <p class="card-text text-center text-muted">Pendapatan hari ini</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel Antrian Terbaru
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="card small-table">
                        <div class="card-header">
                            <i class="fas fa-list-alt me-2"></i> 5 Antrian Terbaru Hari Ini
                        </div>
                        <div class="card-body table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Plat Nomor</th>
                                        <th>Merk</th>
                                        <th>Warna</th>
                                        <th>Tanggal Booking</th>
                                        <th>Jam Booking</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($latest_antrian->num_rows > 0): ?>
                                        <?php while ($row = $latest_antrian->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['plat_nomor']); ?></td>
                                                <td><?= htmlspecialchars($row['merk']); ?></td>
                                                <td><?= htmlspecialchars($row['warna']); ?></td>
                                                <td><?= htmlspecialchars($row['tanggal_booking']); ?></td>
                                                <td><?= htmlspecialchars($row['jam_booking']); ?></td>
                                                <td><?= htmlspecialchars($row['status_booking']); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">Tidak ada antrian untuk hari ini.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div> -->

            <!-- <div class="row">
                <div class="col-md-12 mb-4"> -->
                    <!-- Grafik Line Chart Pemasukan dan Pengeluaran -->
                    <!-- <div class="card">
                        <div class="card-header">
                            <i class="fas fa-chart-line me-2"></i>Grafik Pemasukan & Pengeluaran Harian
                        </div>
                        <div class="card-body">
                            <canvas id="lineChart"></canvas>
                        </div>
                    </div> -->

                    <!-- <script>
                        // Menyiapkan data untuk grafik
                        var ctx = document.getElementById('lineChart').getContext('2d');
                        var lineChart = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: <?php echo json_encode($tanggal); ?>,
                                datasets: [{
                                    label: 'Pemasukan',
                                    data: <?php echo json_encode($pemasukan); ?>,
                                    borderColor: 'green',
                                    backgroundColor: 'rgba(0, 255, 0, 0.2)',
                                    fill: true
                                }, {
                                    label: 'Pengeluaran',
                                    data: <?php echo json_encode($pengeluaran); ?>,
                                    borderColor: 'red',
                                    backgroundColor: 'rgba(255, 0, 0, 0.2)',
                                    fill: true
                                }]
                            },
                            options: {
                                responsive: true,
                                scales: {
                                    x: {
                                        title: {
                                            display: true,
                                            text: 'Tanggal'
                                        }
                                    },
                                    y: {
                                        title: {
                                            display: true,
                                            text: 'Jumlah (Rp)'
                                        },
                                        beginAtZero: true
                                    }
                                }
                            }
                        });
                    </script> -->
                <!-- </div> -->
            <!-- </div> -->

            <!-- Copyright notice and help icon -->
            <div class="position-fixed bottom-0 end-0 p-3" style="font-size: 12px; opacity: 0.7;">
                <span class="text-muted me-2">© 2024 Satria Bima Wash</span>
                <i class="fas fa-question-circle text-primary cursor-pointer" data-bs-toggle="modal" data-bs-target="#helpModal" style="cursor: pointer;"></i>
            </div>

                <!-- Help Modal -->
            <div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="helpModalLabel">Panduan Penggunaan Sistem</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-4">
                                <h6 class="fw-bold">1. Menu-Menu yang Tersedia</h6>
                                <ul class="list-unstyled ms-3">
                                    <li class="mb-2"><i class="fas fa-money-check-alt me-2"></i>Transaksi Jasa: Pencatatan transaksi layanan</li>
                                    <li class="mb-2"><i class="fas fa-book me-2"></i>Transaksi Kas: Pencatatan transaksi kas</li>
                                    <li class="mb-2"><i class="fas fa-user-clock me-2"></i>Absensi: Pencatatan jam masuk dan pulang karyawan</li>
                                    <li class="mb-2"><i class="fas fa-calculator me-2"></i>Hitung Upah Harian: Perhitungan upah harian karyawan</li>
                                    <li class="mb-2"><i class="fas fa-chart-line me-2"></i>Laporan Arus Kas: Laporan pemasukan dan pengeluaran</li>
                                    <li class="mb-2"><i class="fas fa-file-invoice-dollar me-2"></i>Laporan Penggajian: Rincian upah harian karyawan</li>
                                </ul>
                            </div>
                            
                            <div class="mb-4">
                                <h6 class="fw-bold">2. Alur Penggunaan Harian</h6>
                                <p class="ms-3">Admin wajib memasukkan jam masuk karyawan sebelum mencatat transaksi jasa. Jam pulang karyawan harus dicatat sebelum melakukan perhitungan upah harian.</p>
                            </div>

                            <div class="mb-4">
                                <h6 class="fw-bold">3. Mengunduh Laporan</h6>
                                <p class="ms-3">Untuk mengunduh laporan penggajian dan arus kas:<br>
                                1. Klik tombol "Ekspor Data"<br>
                                2. Pilih format file yang diinginkan</p>
                            </div>

                            <div class="mb-4">
                                <h6 class="fw-bold">4. Logout Sistem</h6>
                                <p class="ms-3">Untuk keluar dari sistem, klik icon Logout yang terletak di sebelah kanan nama pengguna pada dashboard utama.</p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript untuk Bootstrap -->
    <script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>

    <script>
        // Pastikan dokumen sudah dimuat sebelum menjalankan script
        document.addEventListener('DOMContentLoaded', function () {
            // Ambil semua tombol accordion
            const accordionButtons = document.querySelectorAll('.accordion-button');

            accordionButtons.forEach(button => {
                button.addEventListener('click', function () {
                    // Tutup semua accordion kecuali yang diklik
                    accordionButtons.forEach(btn => {
                        if (btn !== button) {
                            const collapseElement = document.querySelector(btn.getAttribute('data-bs-target'));
                            if (collapseElement && collapseElement.classList.contains('show')) {
                                const bootstrapCollapse = bootstrap.Collapse.getInstance(collapseElement);
                                bootstrapCollapse.hide();
                            }
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>