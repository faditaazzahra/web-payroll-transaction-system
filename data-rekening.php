<?php
session_start();
include 'config/koneksi.php';

// Tambah Rekening
if (isset($_POST['add'])) {
    $nama_rekening = $_POST['nama_rekening'];
    $saldo = $_POST['saldo'];

    try {
        $conn->begin_transaction();

        // Insert ke tabel rekening
        $sql_add = "INSERT INTO rekening (nama_rekening, saldo) VALUES (?, ?)";
        $stmt = $conn->prepare($sql_add);
        $stmt->bind_param('sd', $nama_rekening, $saldo);
        $stmt->execute();
        
        $id_rekening = $conn->insert_id; // Ambil ID rekening yang baru dibuat
        
        // Insert transaksi kas untuk saldo awal
        if ($saldo != 0) {
            $jenis_transaksi = ($saldo > 0) ? 'Pemasukan' : 'Pengeluaran';
            $jumlah = abs($saldo);
            
            $sql_trans = "INSERT INTO transaksi_kas (tanggal, jumlah, deskripsi, jenis_transaksi, id_user) 
                        VALUES (CURRENT_DATE, ?, 'Saldo awal rekening', ?, ?)";
            $stmt = $conn->prepare($sql_trans);
            $stmt->bind_param('dsi', $jumlah, $jenis_transaksi, $_SESSION['user_id']);
            $stmt->execute();
            
            $id_transaksi_kas = $conn->insert_id;
            
            // Insert ke transaksi_rekening
            $sql_trans_rek = "INSERT INTO transaksi_rekening (id_transaksi_kas, id_rekening, saldo_setelah_transaksi) 
                            VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql_trans_rek);
            $stmt->bind_param('iid', $id_transaksi_kas, $id_rekening, $saldo);
            $stmt->execute();
        }

        $conn->commit();
        $success_message = "Rekening berhasil ditambahkan.";
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Error: " . $e->getMessage();
    }
}

// Edit Rekening
if (isset($_POST['edit'])) {
    $id_rekening = $_POST['id_rekening'];
    $nama_rekening = $_POST['nama_rekening'];
    
    try {
        $sql_edit = "UPDATE rekening SET nama_rekening = ? WHERE id_rekening = ?";
        $stmt = $conn->prepare($sql_edit);
        $stmt->bind_param('si', $nama_rekening, $id_rekening);
        
        if ($stmt->execute()) {
            $success_message = "Rekening berhasil diperbarui.";
        } else {
            throw new Exception($stmt->error);
        }
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Hapus Rekening
if (isset($_POST['delete'])) {
    $id_rekening = $_POST['id_rekening'];
    
    try {
        $conn->begin_transaction();
        
        // Hapus transaksi_rekening terkait
        $sql_delete_trans_rek = "DELETE FROM transaksi_rekening WHERE id_rekening = ?";
        $stmt = $conn->prepare($sql_delete_trans_rek);
        $stmt->bind_param('i', $id_rekening);
        $stmt->execute();
        
        // Hapus rekening
        $sql_delete = "DELETE FROM rekening WHERE id_rekening = ?";
        $stmt = $conn->prepare($sql_delete);
        $stmt->bind_param('i', $id_rekening);
        
        if ($stmt->execute()) {
            $conn->commit();
            $success_message = "Rekening berhasil dihapus.";
        } else {
            throw new Exception($stmt->error);
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Error: " . $e->getMessage();
    }
}

// Update saldo rekening berdasarkan transaksi terakhir
$update_saldo_query = "
    UPDATE rekening r
    LEFT JOIN (
        SELECT tr.id_rekening, tr.saldo_setelah_transaksi as saldo_akhir
        FROM transaksi_rekening tr
        WHERE tr.id_transaksi_rekening = (
            SELECT MAX(tr2.id_transaksi_rekening)
            FROM transaksi_rekening tr2
            WHERE tr2.id_rekening = tr.id_rekening
        )
    ) saldo ON r.id_rekening = saldo.id_rekening
    SET r.saldo = COALESCE(saldo.saldo_akhir, 0)";

try {
    $conn->query($update_saldo_query);
} catch (Exception $e) {
    $error_message = "Error updating saldo: " . $e->getMessage();
}

// Pencarian
$search_query = '';
if (isset($_GET['search'])) {
    $search_query = $_GET['search'];
}

// Ambil data rekening dari database dengan saldo terkini
try {
    $query = "
        SELECT r.*, 
            COALESCE((
                SELECT tr.saldo_setelah_transaksi 
                FROM transaksi_rekening tr
                WHERE tr.id_rekening = r.id_rekening
                ORDER BY tr.id_transaksi_rekening DESC
                LIMIT 1
            ), 0) as saldo_terkini
        FROM rekening r
        WHERE r.nama_rekening LIKE ?
        ORDER BY r.id_rekening";
        
    $stmt = $conn->prepare($query);
    $search_param = "%" . $search_query . "%";
    $stmt->bind_param('s', $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
} catch (Exception $e) {
    $error_message = "Error retrieving data: " . $e->getMessage();
}

// Debug query untuk memeriksa transaksi
$debug_query = "
    SELECT r.id_rekening, r.nama_rekening, r.saldo,
        tk.jenis_transaksi, tk.jumlah,
        tr.saldo_setelah_transaksi
    FROM rekening r
    LEFT JOIN transaksi_rekening tr ON r.id_rekening = tr.id_rekening
    LEFT JOIN transaksi_kas tk ON tr.id_transaksi_kas = tk.id_transaksi_kas
    ORDER BY r.id_rekening, tr.id_transaksi_rekening";

try {
    $debug_result = $conn->query($debug_query);
    // untuk debugging
    /*
    while ($row = $debug_result->fetch_assoc()) {
        echo "<pre>";
        print_r($row);
        echo "</pre>";
    }
    */
} catch (Exception $e) {
    $error_message = "Error in debug query: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Rekening - Satria Bima Wash (SBW)</title>
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
            left: 90%;
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

        .alert {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1050;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Alert untuk pesan sukses atau error -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $success_message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $error_message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card fade-in">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-money-check-alt me-2"></i>Data Rekening</h5>
            </div>

            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i> Kembali ke Dashboard
                    </a>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="fas fa-plus me-2"></i> Tambah Rekening
                    </button>
                </div>
                
                <!-- Form Pencarian -->
                <form method="POST" class="mb-4">
                    <div class="input-group">
                        <input type="text" name="search_query" class="form-control" placeholder="Cari Rekening" value="<?= htmlspecialchars($search_query); ?>">
                        <button class="btn btn-outline-primary" name="search" type="submit">
                            <i class="fas fa-search me-2"></i> Cari
                        </button>
                    </div>
                </form>
                
                <!-- Tabel untuk menampilkan data rekening -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="text-center">
                            <tr>
                                <th>ID</th>
                                <th>Nama Rekening</th>
                                <th>Saldo</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td class="text-center"><?= $row['id_rekening']; ?></td>
                                    <td><?= $row['nama_rekening']; ?></td>
                                    <td class="text-end">Rp <?= number_format($row['saldo'], 0, ',', '.'); ?></td>
                                    <td class="text-center">
                                        <div class="action-buttons">
                                            <button class="btn btn-edit tooltip-action" data-tooltip="Edit Rekening" 
                                                    data-id="<?= $row['id_rekening']; ?>" 
                                                    data-nama="<?= htmlspecialchars($row['nama_rekening']); ?>" 
                                                    data-saldo="<?= $row['saldo']; ?>" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editModal">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form action="" method="POST" class="d-inline">
                                                <input type="hidden" name="id_rekening" value="<?= $row['id_rekening']; ?>">
                                                <button type="submit" name="delete" class="btn btn-delete tooltip-action" data-tooltip="Hapus Rekening" onclick="return confirm('Yakin ingin menghapus rekening?')">
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

    <!-- Modal Tambah Rekening -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel"><i class="fas fa-plus me-2"></i>Tambah Rekening</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label for="nama_rekening" class="form-label">Nama Rekening</label>
                            <input type="text" class="form-control" id="nama_rekening" name="nama_rekening" required>
                        </div>
                        <div class="mb-3">
                            <label for="saldo" class="form-label">Saldo Awal</label>
                            <input type="number" class="form-control" id="saldo" name="saldo" required>
                        </div>
                        <button type="submit" name="add" class="btn btn-primary"><i class="fas fa-save me-2"></i>Simpan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit Rekening -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel"><i class="fas fa-edit me-2"></i>Edit Rekening</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST">
                        <input type="hidden" id="edit_id_rekening" name="id_rekening">
                        <div class="mb-3">
                            <label for="edit_nama_rekening" class="form-label">Nama Rekening</label>
                            <input type="text" class="form-control" id="edit_nama_rekening" name="nama_rekening" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_saldo" class="form-label">Saldo</label>
                            <input type="number" class="form-control" id="edit_saldo" name="saldo" required>
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
        var nama = $(this).data('nama');
        var saldo = $(this).data('saldo');

        $('#edit_id_rekening').val(id);
        $('#edit_nama_rekening').val(nama);
        $('#edit_saldo').val(saldo);
    });

    // Automatically hide alert messages after 5 seconds
    $(document).ready(function() {
        setTimeout(function() {
            $('.alert').alert('close');
        }, 5000);
    });
    </script>
</body>
</html>

<?php
// Menutup koneksi
$stmt->close();
$conn->close();
?>