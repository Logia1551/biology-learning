```php
<?php
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit;
}

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newUsername = trim($_POST['username']);
    
    if (empty($newUsername)) {
        $message = 'Username tidak boleh kosong!';
        $messageType = 'error';
    } else {
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$newUsername, $_SESSION['user_id']]);
        if ($stmt->fetch()) {
            $message = 'Username sudah digunakan!';
            $messageType = 'error';
        } else {
            // Update username
            $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
            if ($stmt->execute([$newUsername, $_SESSION['user_id']])) {
                $_SESSION['username'] = $newUsername;
                $message = 'Username berhasil diperbarui!';
                $messageType = 'success';
            } else {
                $message = 'Gagal memperbarui username!';
                $messageType = 'error';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Profile - Biology Learning</title>
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
            <h1>Edit Profile</h1>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Edit Profile</h2>
            </div>

            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" 
                           value="<?php echo htmlspecialchars($_SESSION['username']); ?>" required>
                </div>

                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </form>

            <a href="dashboard.php" class="back-link">Kembali ke Dashboard</a>
        </div>
    </div>
</body>
</html>