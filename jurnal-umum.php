<?php
session_start();

// Pastikan admin sudah login
if (!isset($_SESSION['id_user'])) {
    header('Location: index.php');
    exit();
}

include 'config/koneksi.php';

// Set timezone (misalnya Asia/Jakarta)
date_default_timezone_set('Asia/Jakarta');

// Ambil data akun dari database untuk dropdown
$query_akun = "SELECT id_akun, nama_akun FROM akun";
$result_akun = $conn->query($query_akun);

// Pencarian
$search = '';
if (isset($_POST['search'])) {
    $search = $_POST['search'];
}

// Ambil data jurnal umum dari database
$query = "SELECT ju.id_jurnal, ju.tanggal_jurnal, ju.deskripsi, ju.debit, ju.kredit, ju.id_akun, a.nama_akun 
        FROM jurnal_umum ju 
        LEFT JOIN akun a ON ju.id_akun = a.id_akun 
        WHERE ju.deskripsi LIKE ? ORDER BY ju.tanggal_jurnal DESC";
$stmt = $conn->prepare($query);
$search_param = "%$search%";
$stmt->bind_param('s', $search_param);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jurnal Umum - Satria Bima Wash (SBW)</title>
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
            background-color: #f8f9fa;
            padding: 20px;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .card-header {
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
            border-radius: 10px 10px 0 0;
            padding: 15px 20px;
        }

        .card-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .btn-primary {
            background-color: #4e73df;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
        }

        .btn-primary:hover {
            background-color: #2e59d9;
        }

        .btn-secondary {
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
        }

        .btn-outline-primary {
            border-color: #4e73df;
        }

        .btn-outline-primary:hover {
            background-color: #4e73df;
        }

        .table {
            margin-top: 20px;
        }

        .table th {
            background-color: #4e73df;
            color: white;
            font-weight: 500;
            vertical-align: middle;
        }

        .table td {
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-book me-2"></i>Jurnal Umum</h5>
        </div>

        <div class="card-body">
            <!-- Tombol Kembali ke Dashboard -->
            <div class="d-flex justify-content-end mb-3">
                <a href="dashboard.php" class="btn btn-secondary mb-3">
                    <i class="fas fa-arrow-left me-2"></i> Kembali ke Dashboard
                </a>
            </div>

            <!-- Form Pencarian -->
            <form action="" method="POST" class="mb-3">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($search); ?>" placeholder="Cari berdasarkan deskripsi...">
                    <button class="btn btn-outline-primary" type="submit">Cari</button>
                </div>
            </form>

            <!-- Tombol Tambah Jurnal -->
            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#tambahModal">
                <i class="fas fa-plus me-2"></i>Tambah Jurnal
            </button>

            <!-- Tabel untuk Menampilkan Data Jurnal Umum -->
            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered">
                    <thead class="text-center">
                        <tr>
                            <th>ID Jurnal</th>
                            <th>Tanggal Jurnal</th>
                            <th>Deskripsi</th>
                            <th>ID Akun</th>
                            <th>Nama Akun</th>
                            <th>Debit</th>
                            <th>Kredit</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-center">
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['id_jurnal']; ?></td>
                                <td><?= $row['tanggal_jurnal']; ?></td>
                                <td><?= htmlspecialchars($row['deskripsi']); ?></td>
                                <td><?= $row['id_akun']; ?></td>
                                <td><?= htmlspecialchars($row['nama_akun']); ?></td>
                                <td>Rp <?= number_format($row['debit'], 0, ',', '.'); ?></td>
                                <td>Rp <?= number_format($row['kredit'], 0, ',', '.'); ?></td>
                                <td>
                                    <button class="btn btn-warning btn-edit" 
                                            data-id="<?= $row['id_jurnal']; ?>" 
                                            data-tanggal="<?= $row['tanggal_jurnal']; ?>" 
                                            data-deskripsi="<?= htmlspecialchars($row['deskripsi']); ?>" 
                                            data-debet="<?= $row['debit']; ?>" 
                                            data-kredit="<?= $row['kredit']; ?>" 
                                            data-akun="<?= $row['id_akun']; ?>" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editModal">Edit</button>
                                    <a href="hapus-jurnal.php?id=<?= $row['id_jurnal']; ?>" class="btn btn-danger" onclick="return confirm('Yakin ingin menghapus data ini?');">Hapus</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Tambah -->
    <div class="modal fade" id="tambahModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Jurnal Umum</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="tambah-jurnal.php" method="POST">
                        <div class="mb-3">
                            <label for="tanggal_jurnal" class="form-label">Tanggal Jurnal</label>
                            <input type="date" class="form-control" id="tanggal_jurnal" name="tanggal_jurnal" required>
                        </div>
                        <div class="mb-3">
                            <label for="deskripsi" class="form-label">Deskripsi</label>
                            <input type="text" class="form-control" id="deskripsi" name="deskripsi" required>
                        </div>
                        <div class="mb-3">
                            <label for="id_akun" class="form-label">ID Akun</label>
                            <select class="form-control" id="id_akun" name="id_akun" required>
                                <option value="">Pilih Akun</option>
                                <?php while ($akun = $result_akun->fetch_assoc()): ?>
                                    <option value="<?= $akun['id_akun']; ?>"><?= $akun['nama_akun']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="debit" class="form-label">Debit</label>
                            <input type="number" class="form-control" id="debit" name="debit" required>
                        </div>
                        <div class="mb-3">
                            <label for="kredit" class="form-label">Kredit</label>
                            <input type="number" class="form-control" id="kredit" name="kredit" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Simpan Jurnal</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit -->
    <div class="modal fade" id="editModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Jurnal Umum</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm" action="edit-jurnal.php" method="POST">
                        <input type="hidden" id="edit_id_jurnal" name="id_jurnal">
                        <div class="mb-3">
                            <label for="edit_tanggal" class="form-label">Tanggal Jurnal</label>
                            <input type="date" class="form-control" id="edit_tanggal" name="tanggal_jurnal" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_deskripsi" class="form-label">Deskripsi</label>
                            <input type="text" class="form-control" id="edit_deskripsi" name="deskripsi" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_id_akun" class="form-label">ID Akun</label>
                            <select class="form-control" id="edit_id_akun" name="id_akun" required>
                                <option value="">Pilih Akun</option>
                                <?php
                                // Reset pointer hasil akun
                                $result_akun->data_seek(0);
                                while ($akun = $result_akun->fetch_assoc()): ?>
                                    <option value="<?= $akun['id_akun']; ?>"><?= $akun['nama_akun']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_debit" class="form-label">Debit</label>
                            <input type="number" class="form-control" id="edit_debit" name="debit" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_kredit" class="form-label">Kredit</label>
                            <input type="number" class="form-control" id="edit_kredit" name="kredit" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    $(document).on('click', '.btn-edit', function() {
        var id_jurnal = $(this).data('id');
        var tanggal = $(this).data('tanggal');
        var deskripsi = $(this).data('deskripsi');
        var debet = $(this).data('debet');
        var kredit = $(this).data('kredit');
        var id_akun = $(this).data('akun');

        $('#edit_id_jurnal').val(id_jurnal);
        $('#edit_tanggal').val(tanggal);
        $('#edit_deskripsi').val(deskripsi);
        $('#edit_debit').val(debet);
        $('#edit_kredit').val(kredit);
        $('#edit_id_akun').val(id_akun);
    });
    </script>
</body>
</html>
