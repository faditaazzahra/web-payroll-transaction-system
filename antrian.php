<?php 
session_start();

// Pastikan admin sudah login
if (!isset($_SESSION['id_user'])) {
    header('Location: index.php');
    exit();
}

include 'config/koneksi.php';

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Data kendaraan
    $plat_nomor = $_POST['plat_nomor'];
    $merk = $_POST['merk'];
    $warna = $_POST['warna'];
    $jenis_kendaraan = $_POST['jenis_kendaraan'];
    $jenis_penggunaan = $_POST['jenis_penggunaan'];
    $ukuran = $_POST['ukuran'];
    $no_hp = $_POST['no_hp'];

    // Data pemesanan
    $tanggal_booking = $_POST['tanggal_booking'];
    $jam_booking = $_POST['jam_booking'];
    $status_booking = 'Menunggu'; // Status awal
    $status_reminder = 'belum';

    // Cek apakah kendaraan dengan plat_nomor yang sama sudah ada
    $cek_kendaraan = $conn->prepare("SELECT id_kendaraan, jumlah_kedatangan FROM kendaraan WHERE plat_nomor = ?");
    $cek_kendaraan->bind_param("s", $plat_nomor);
    $cek_kendaraan->execute();
    $result_kendaraan = $cek_kendaraan->get_result();

    if ($result_kendaraan->num_rows > 0) {
        // Kendaraan sudah ada, tingkatkan jumlah kedatangan
        $kendaraan_data = $result_kendaraan->fetch_assoc();
        $id_kendaraan = $kendaraan_data['id_kendaraan'];
        $jumlah_kedatangan = $kendaraan_data['jumlah_kedatangan'] + 1;

        // Update jumlah kedatangan
        $update_kendaraan = $conn->prepare("UPDATE kendaraan SET jumlah_kedatangan = ? WHERE id_kendaraan = ?");
        $update_kendaraan->bind_param("ii", $jumlah_kedatangan, $id_kendaraan);
        $update_kendaraan->execute();
    } else {
        // Kendaraan belum ada, set jumlah_kedatangan ke 1
        $jumlah_kedatangan = 1;

        // Insert data kendaraan baru
        $stmt_kendaraan = $conn->prepare("INSERT INTO kendaraan (plat_nomor, merk, warna, jenis_kendaraan, jenis_penggunaan, ukuran, no_hp, jumlah_kedatangan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_kendaraan->bind_param('sssssssi', $plat_nomor, $merk, $warna, $jenis_kendaraan, $jenis_penggunaan, $ukuran, $no_hp, $jumlah_kedatangan);
        $stmt_kendaraan->execute();
        $id_kendaraan = $conn->insert_id;
    }

    // Insert data pemesanan ke tabel booking_kendaraan
    $stmt_booking = $conn->prepare("INSERT INTO booking_kendaraan (id_kendaraan, tanggal_booking, jam_booking, status_booking, status_reminder) VALUES (?, ?, ?, ?, ?)");
    $stmt_booking->bind_param('issss', $id_kendaraan, $tanggal_booking, $jam_booking, $status_booking, $status_reminder);
    $stmt_booking->execute();

    // Redirect ke halaman daftar antrian harian
    header('Location: antrian.php');
    exit();
}

// Ambil daftar antrian untuk hari ini
$today = date('Y-m-d');
$result = $conn->query("SELECT * FROM booking_kendaraan JOIN kendaraan ON booking_kendaraan.id_kendaraan = kendaraan.id_kendaraan WHERE tanggal_booking = '$today' ORDER BY status_booking, jam_booking");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Antrian Kendaraan</title>
    <!-- Memuat CSS Eksternal -->
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/antrian.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/reminder.js"></script>
</head>

<body>
    <div class="main-container">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="fa fa-car me-2"></i>Antrian Kendaraan
                </h5>
            </div>

            <div class="card-body">
                <div class="d-flex justify-content-end mb-3">
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
                    </a>
                </div>
                
                <form method="POST" action="">
                    <!-- Form input data kendaraan -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="plat_nomor" class="form-label"><i class="fa fa-car me-2"></i>Plat Nomor</label>
                                <input type="text" class="form-control" id="plat_nomor" name="plat_nomor" required>
                            </div>
                            <div class="mb-3">
                                <label for="merk" class="form-label"><i class="fa fa-tag me-2"></i>Merk Kendaraan</label>
                                <input type="text" class="form-control" id="merk" name="merk" required>
                            </div>
                            <div class="mb-3">
                                <label for="warna" class="form-label"><i class="fas fa-palette me-2"></i>Warna Kendaraan</label>
                                <input type="text" class="form-control" id="warna" name="warna" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="jenis_kendaraan" class="form-label"><i class="fas fa-car me-2"></i>Jenis Kendaraan</label>
                                <select class="form-select" id="jenis_kendaraan" name="jenis_kendaraan" required>
                                    <option value="Mobil">Mobil</option>
                                    <option value="Motor">Motor</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="jenis_penggunaan" class="form-label"><i class="fas fa-users me-2"></i>Jenis Penggunaan</label>
                                <select class="form-select" id="jenis_penggunaan" name="jenis_penggunaan" required>
                                    <option value="Umum">Umum</option>
                                    <option value="Online">Online</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="ukuran" class="form-label"><i class="fas fa-expand-arrows-alt me-2"></i>Ukuran Kendaraan</label>
                                <select class="form-select" id="ukuran" name="ukuran" required>
                                    <option value="Kecil">Kecil</option>
                                    <option value="Medium">Medium</option>
                                    <option value="Besar">Besar</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="no_hp" class="form-label"><i class="fas fa-phone me-2"></i>Nomor HP</label>
                                <input type="text" class="form-control" id="no_hp" name="no_hp" required>
                            </div>
                
                            <!-- Form input data pemesanan -->
                            <div class="mb-3">
                                <label for="tanggal_booking" class="form-label"><i class="fas fa-calendar-alt me-2"></i>Tanggal Booking</label>
                                <input type="date" class="form-control" id="tanggal_booking" name="tanggal_booking" required>
                            </div>
                            <div class="mb-3">
                                <label for="jam_booking" class="form-label"><i class="fas fa-clock me-2"></i>Jam Booking</label>
                                <input type="time" class="form-control" id="jam_booking" name="jam_booking" required>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save me-2"></i>Kirim
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="main-container">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="fa fa-list me-2"></i>Daftar Antrian Hari Ini
                </h5>
            </div>

            <div class="card-body">
                <table class="table table-bordered text-center">
                    <thead>
                        <tr>
                            <th>Plat Nomor</th>
                            <th>Merk</th>
                            <th>Warna</th>
                            <th>Jenis Kendaraan</th>
                            <th>Tanggal Booking</th>
                            <th>Jam Booking</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['plat_nomor'] ?></td>
                            <td><?= $row['merk'] ?></td>
                            <td><?= $row['warna'] ?></td>
                            <td><?= $row['jenis_kendaraan'] ?></td>
                            <td><?= $row['tanggal_booking'] ?></td>
                            <td><?= $row['jam_booking'] ?></td>
                            <td><?= $row['status_booking'] ?></td>
                            <td>
                                <?php if ($row['status_booking'] == 'Menunggu'): ?>
                                    <a href="process/update-status-antrian.php?id=<?= $row['id_booking'] ?>&status=Diproses" class="btn btn-warning btn-sm">Diproses</a>
                                <?php elseif ($row['status_booking'] == 'Diproses'): ?>
                                    <a href="process/update-status-antrian.php?id=<?= $row['id_booking'] ?>&status=Selesai&redirect=transaksi-harian.php?id_kendaraan=<?= $row['id_kendaraan'] ?>" class="btn btn-success btn-sm">Selesai</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        setInterval(function() {
            fetch('process/cek-pengingat-antrian.php')
            .then(response => response.json())
            .then(data => {
                if (data.reminders.length > 0) {
                    let message = "Reminder Antrian:\n";
                    
                    data.reminders.forEach(reminder => {
                        message += `- ${reminder['plat_nomor']} (${reminder['merk']}, ${reminder['warna']}) Jam: ${reminder['jam_booking']}\n`;
                    });

                    alert(message); // Menampilkan popup alert
                }
            });
        }, 300000); // 300000ms = 5 menit
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
