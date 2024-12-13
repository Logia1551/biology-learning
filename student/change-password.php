```php
<?php
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit;
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Verify current password
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!password_verify($currentPassword, $user['password'])) {
        $message = 'Password saat ini tidak sesuai!';
        $messageType = 'error';
    } elseif ($newPassword !== $confirmPassword) {
        $message = 'Password baru tidak cocok dengan konfirmasi password!';
        $messageType = 'error';
    } elseif (strlen($newPassword) < 6) {
        $message = 'Password baru minimal 6 karakter!';
        $messageType = 'error';
    } else {
        // Update password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        
        if ($stmt->execute([$hashedPassword, $_SESSION['user_id']])) {
            $message = 'Password berhasil diperbarui!';
            $messageType = 'success';
        } else {
            $message = 'Gagal memperbarui password!';
            $messageType = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Ganti Password - Biology Learning</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background: #f5f6fa;
            min-height: 100vh;
        }

        .navbar {
            background: #2a5298;
            padding: 15px 0;
            color: white;
            position: fixed;
            width: 100%;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .nav-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .container {
            max-width: 500px;
            margin: 0 auto;
            padding: 90px 20px 20px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .card-header {
            margin-bottom: 25px;
            text-align: center;
        }

        .card-title {
            font-size: 24px;
            color: #2a5298;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #4a5568;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #2a5298;
            box-shadow: 0 0 0 3px rgba(42, 82, 152, 0.1);
        }
        
        .password-input-container {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: none;
            color: #4a5568;
            cursor: pointer;
            padding: 4px;
        }

        .password-requirements {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            font-size: 15px;
            font-weight: 500;
            text-align: center;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-primary {
            background: #2a5298;
            color: white;
            width: 100%;
        }

        .btn-primary:hover {
            background: #1e3c72;
            transform: translateY(-1px);
        }

        .message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #2a5298;
            text-decoration: none;
            font-weight: 500;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .container {
                padding: 80px 15px 20px;
            }
            .card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-content">
            <h1>Ganti Password</h1>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Ganti Password</h2>
            </div>

            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Password Saat Ini</label>
                    <div class="password-input-container">
                        <input type="password" name="current_password" class="form-control" required>
                        <button type="button" class="toggle-password" onclick="togglePassword(this)">Show</button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password Baru</label>
                    <div class="password-input-container">
                        <input type="password" name="new_password" class="form-control" required>
                        <button type="button" class="toggle-password" onclick="togglePassword(this)">Show</button>
                    </div>
                    <div class="password-requirements">
                        Minimal 6 karakter
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Konfirmasi Password Baru</label>
                    <div class="password-input-container">
                        <input type="password" name="confirm_password" class="form-control" required>
                        <button type="button" class="toggle-password" onclick="togglePassword(this)">Show</button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Simpan Password Baru</button>
            </form>

            <a href="dashboard.php" class="back-link">Kembali ke Dashboard</a>
        </div>
    </div>

    <script>
        function togglePassword(button) {
            const input = button.previousElementSibling;
            if (input.type === 'password') {
                input.type = 'text';
                button.textContent = 'Hide';
            } else {
                input.type = 'password';
                button.textContent = 'Show';
            }
        }
    </script>
</body>
</html>
```