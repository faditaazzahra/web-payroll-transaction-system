<?php
session_start();

// Pastikan admin sudah login
if (!isset($_SESSION['id_user'])) {
    header('Location: index.php');
    exit();
}

include 'config/koneksi.php';

// Proses jika form ditambahkan atau diupdate
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        // Menambahkan promo baru
        $jenis_kendaraan = $_POST['jenis_kendaraan'];
        $jumlah_kunjungan = $_POST['jumlah_kunjungan'];
        $deskripsi = $_POST['deskripsi'];

        $sql = "INSERT INTO promo (jenis_kendaraan, jumlah_kunjungan, deskripsi) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sis', $jenis_kendaraan, $jumlah_kunjungan, $deskripsi);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['edit'])) {
        // Mengedit promo
        $id_promo = $_POST['id_promo'];
        $jenis_kendaraan = $_POST['jenis_kendaraan'];
        $jumlah_kunjungan = $_POST['jumlah_kunjungan'];
        $deskripsi = $_POST['deskripsi'];

        $sql = "UPDATE promo SET jenis_kendaraan = ?, jumlah_kunjungan = ?, deskripsi = ? WHERE id_promo = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sisi', $jenis_kendaraan, $jumlah_kunjungan, $deskripsi, $id_promo);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['delete'])) {
        // Menghapus promo
        $id_promo = $_POST['id_promo'];

        $sql = "DELETE FROM promo WHERE id_promo = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id_promo);
        $stmt->execute();
        $stmt->close();
    }
}

// Pencarian
$search_query = '';
if (isset($_POST['search'])) {
    $search_query = $_POST['search_query'];
}

// Ambil data promo dari database
$query = "SELECT * FROM promo WHERE deskripsi LIKE ? ORDER BY id_promo";
$stmt = $conn->prepare($query);
$search_param = "%" . $search_query . "%";
$stmt->bind_param('s', $search_param);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Promo - Satria Bima Wash (SBW)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/reminder.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 20px;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        }

        .card-header {
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
            padding: 20px;
            font-weight: 600;
        }

        .btn-primary, .btn-secondary {
            border: none;
            padding: 10px 20px;
            border-radius: 30px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(45deg, #4e73df, #224abe);
        }

        .btn-primary:hover, .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .table {
            border-radius: 10px;
            overflow: hidden;
        }

        .table th {
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
            font-weight: 500;
        }

        .table tr {
            transition: all 0.2s ease;
        }

        .table tr:hover {
            background-color: #f8f9fa;
            transform: scale(1.01);
        }

        .action-buttons button {
            padding: 5px 10px;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .btn-edit {
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
        }

        .btn-delete {
            background: linear-gradient(45deg, #e74a3b, #be2617);
            color: white;
        }

        .modal-content {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .modal-header {
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
            border-radius: 15px 15px 0 0;
        }

        .form-control {
            border-radius: 20px;
            padding: 10px 15px;
        }

        .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(78,115,223,0.25);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        .tooltip-action:before {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 80%;
            left: 95%;
            transform: translateX(-50%);
            padding: 5px 10px;
            background-color: #333;
            color: white;
            font-size: 12px;
            border-radius: 4px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .tooltip-action:hover:before {
            opacity: 1;
            visibility: visible;
            bottom: 100%;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card fade-in">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-tags me-2"></i>Data Promo</h5>
            </div>

            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i> Kembali ke Dashboard
                    </a>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="fas fa-plus me-2"></i> Tambah Promo
                    </button>
                </div>
                
                <!-- Form Pencarian -->
                <form method="POST" class="mb-4">
                    <div class="input-group">
                        <input type="text" name="search_query" class="form-control" placeholder="Cari Deskripsi Promo" value="<?= htmlspecialchars($search_query); ?>">
                        <button class="btn btn-outline-primary" name="search" type="submit">
                            <i class="fas fa-search me-2"></i> Cari
                        </button>
                    </div>
                </form>
                
                <!-- Tabel untuk menampilkan data promo -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="text-center">
                            <tr>
                                <th>ID</th>
                                <th>Jenis Kendaraan</th>
                                <th>Jumlah Kunjungan</th>
                                <th>Deskripsi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td class="text-center"><?= $row['id_promo']; ?></td>
                                    <td class="text-center"><?= $row['jenis_kendaraan']; ?></td>
                                    <td class="text-center"><?= $row['jumlah_kunjungan']; ?></td>
                                    <td><?= $row['deskripsi']; ?></td>
                                    <td class="text-center">
                                        <div class="action-buttons">
                                            <button class="btn btn-edit tooltip-action" data-tooltip="Edit Promo" data-id="<?= $row['id_promo']; ?>" data-jenis="<?= htmlspecialchars($row['jenis_kendaraan']); ?>" data-kunjungan="<?= $row['jumlah_kunjungan']; ?>" data-deskripsi="<?= htmlspecialchars($row['deskripsi']); ?>" data-bs-toggle="modal" data-bs-target="#editModal">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form action="" method="POST" class="d-inline">
                                                <input type="hidden" name="id_promo" value="<?= $row['id_promo']; ?>">
                                                <button type="submit" name="delete" class="btn btn-delete tooltip-action" data-tooltip="Hapus Promo" onclick="return confirm('Yakin ingin menghapus?')">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Promo -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel"><i class="fas fa-plus me-2"></i>Tambah Promo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label for="jenis_kendaraan" class="form-label">Jenis Kendaraan</label>
                            <select class="form-control" id="jenis_kendaraan" name="jenis_kendaraan" required>
                                <option value="Mobil">Mobil</option>
                                <option value="Motor">Motor</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="jumlah_kunjungan" class="form-label">Jumlah Kunjungan</label>
                            <input type="number" class="form-control" id="jumlah_kunjungan" name="jumlah_kunjungan" required min="1">
                        </div>
                        <div class="mb-3">
                            <label for="deskripsi" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="deskripsi" name="deskripsi" required></textarea>
                        </div>
                        <button type="submit" name="add" class="btn btn-primary"><i class="fas fa-save me-2"></i>Simpan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit Promo -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel"><i class="fas fa-edit me-2"></i>Edit Promo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST">
                        <input type="hidden" id="edit_id_promo" name="id_promo">
                        <div class="mb-3">
                            <label for="edit_jenis_kendaraan" class="form-label">Jenis Kendaraan</label>
                            <select class="form-control" id="edit_jenis_kendaraan" name="jenis_kendaraan" required>
                                <option value="Mobil">Mobil</option>
                                <option value="Motor">Motor</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_jumlah_kunjungan" class="form-label">Jumlah Kunjungan</label>
                            <input type="number" class="form-control" id="edit_jumlah_kunjungan" name="jumlah_kunjungan" required min="1">
                        </div>
                        <div class="mb-3">
                            <label for="edit_deskripsi" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="edit_deskripsi" name="deskripsi" required></textarea>
                        </div>
                        <button type="submit" name="edit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Simpan Perubahan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    $(document).on('click', '.btn-edit', function() {
        var id = $(this).data('id');
        var jenis = $(this).data('jenis');
        var kunjungan = $(this).data('kunjungan');
        var deskripsi = $(this).data('deskripsi');

        $('#edit_id_promo').val(id);
        $('#edit_jenis_kendaraan').val(jenis);
        $('#edit_jumlah_kunjungan').val(kunjungan);
        $('#edit_deskripsi').val(deskripsi);
    });
    </script>
</body>
</html>
