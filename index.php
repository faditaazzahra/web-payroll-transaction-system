<?php
// Mulai sesi
session_start();

include 'config/koneksi.php';

// Proses jika form dikirim
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Query untuk memeriksa username dan password
    $sql = "SELECT * FROM user WHERE username = ? AND password = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Login sukses, buat sesi
        $user = $result->fetch_assoc();
        $_SESSION['id_user'] = $user['id_user'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        header('Location: dashboard.php'); // Redirect ke halaman dashboard
        exit();
    } else {
        $error_message = "Username atau password salah!";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Satria Bima Wash (SBW) - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            font-family: 'Poppins', sans-serif;
        }
        .login-container {
            background-color: rgba(255, 255, 255, 0.9);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            max-width: 900px;
            overflow: hidden;
        }
        .image-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-radius: 15px;
            margin-right: 40px;
        }
        .image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .image-container:hover img {
            transform: scale(1.05);
        }
        .form-container {
            flex: 1;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .form-container h3 {
            font-weight: 600;
            margin-bottom: 30px;
            color: #333;
            text-align: center;
        }
        .form-control {
            border: none;
            border-bottom: 2px solid #ddd;
            border-radius: 0;
            padding: 10px 5px;
            margin-bottom: 25px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #4e73df;
            box-shadow: none;
        }
        .btn-primary {
            background-color: #4e73df;
            border: none;
            padding: 12px 20px;
            border-radius: 30px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #2e59d9;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(46, 89, 217, 0.4);
        }
        .input-group-text {
            background-color: transparent;
            border: none;
            color: #4e73df;
        }
        .form-floating > label {
            color: #666;
        }
        .error-message {
            color: #dc3545;
            text-align: center;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="image-container">
            <img src="assets/images/login.jpg" alt="Satria Bima Wash">
        </div>
        <div class="form-container">
            <h3>Welcome to Satria Bima Wash</h3>
            <?php if (isset($error_message)): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>
            <form action="" method="POST">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                    <label for="username"><i class="fas fa-user me-2"></i>Username</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                    <label for="password"><i class="fas fa-lock me-2"></i>Password</label>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-sign-in-alt me-2"></i>LOGIN
                </button>
            </form>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add some interactivity
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            input.addEventListener('blur', function() {
                if (this.value === '') {
                    this.parentElement.classList.remove('focused');
                }
            });
        });
    </script>
</body>
</html>