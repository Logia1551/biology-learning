<?php
require_once 'config/database.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'teacher') {
        header('Location: teacher/dashboard.php');
    } else {
        header('Location: student/dashboard.php');
    }
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error = 'Semua field harus diisi';
    } elseif ($password !== $confirm_password) {
        $error = 'Password tidak cocok';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter';
    } else {
        try {
            // Check if username exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = 'Username sudah digunakan';
            } else {
                // Create new student account
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'student')");
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                if ($stmt->execute([$username, $hashedPassword])) {
                    $success = 'Registrasi berhasil! Silakan login.';
                } else {
                    $error = 'Gagal membuat akun';
                }
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan sistem';
            error_log($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - Biology Learning</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- [Same styles as index.php plus additional styles for success message] -->
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
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .auth-container {
            width: 100%;
            max-width: 400px;
            margin: 20px;
        }

        .auth-card {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .auth-header h1 {
            color: #2a5298;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .auth-header p {
            color: #6b7280;
            font-size: 16px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #4b5563;
            font-size: 14px;
            font-weight: 500;
        }

        .form-input {
            width: 100%;
            padding: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #2a5298;
            box-shadow: 0 0 0 3px rgba(42, 82, 152, 0.1);
        }

        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #2a5298;
            color: white;
        }

        .btn-primary:hover {
            background: #1e3c72;
            transform: translateY(-1px);
        }

        .error-message {
            background: #fef2f2;
            color: #991b1b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
            text-align: center;
        }

        .auth-footer {
            text-align: center;
            margin-top: 20px;
        }

        .auth-footer p {
            color: #6b7280;
            font-size: 14px;
        }

        .auth-footer a {
            color: #2a5298;
            text-decoration: none;
            font-weight: 500;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }
        .success-message {
            background: #f0fdf4;
            color: #166534;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
            text-align: center;
        }

        .password-requirements {
            margin-top: 4px;
            font-size: 12px;
            color: #6b7280;
        }
        .password-field {
        position: relative;
    }

    .toggle-password {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        border: none;
        background: none;
        color: #6b7280;
        cursor: pointer;
        font-size: 14px;
        padding: 4px 8px;
    }

    .toggle-password:hover {
        color: #2a5298;
    }
        @media (max-width: 480px) {
            .auth-card {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Registrasi Siswa</h1>
                <p>Buat akun baru untuk mengakses platform</p>
            </div>

            <?php if ($error): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-input"
                        required 
                        autofocus
                        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                    >
                </div>

                <div class="form-group">
    <label class="form-label" for="password">Password</label>
    <div class="password-field">
        <input 
            type="password" 
            id="password" 
            name="password" 
            class="form-input"
            required
        >
        <button type="button" class="toggle-password" onclick="togglePassword('password')">Show</button>
    </div>
    <div class="password-requirements">
        Minimal 6 karakter
    </div>
</div>

                <div class="form-group">
    <label class="form-label" for="confirm_password">Konfirmasi Password</label>
    <div class="password-field">
        <input 
            type="password" 
            id="confirm_password" 
            name="confirm_password" 
            class="form-input"
            required
        >
        <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">Show</button>
    </div>
</div>

                <button type="submit" class="btn btn-primary">Daftar</button>
            </form>

            <div class="auth-footer">
                <p>Sudah punya akun? <a href="index.php">Login di sini</a></p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const messages = document.querySelectorAll('.error-message, .success-message');
            messages.forEach(message => {
                if (message.classList.contains('error-message')) {
                    setTimeout(() => {
                        message.style.transition = 'opacity 0.5s ease';
                        message.style.opacity = '0';
                        setTimeout(() => message.remove(), 500);
                    }, 5000);
                }
                if (message.classList.contains('success-message')) {
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 2000);
                }
            });
        });

        function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const button = input.nextElementSibling;
    
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