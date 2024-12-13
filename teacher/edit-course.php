<?php
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../index.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: dashboard.php');
    exit;
}

// Ambil data course
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND teacher_id = ?");
$stmt->execute([$_GET['id'], $_SESSION['user_id']]);
$course = $stmt->fetch();

if (!$course) {
    header('Location: dashboard.php');
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $video_url = trim($_POST['video_url']);
    $meeting_number = (int)$_POST['meeting_number'];
    $duration = trim($_POST['duration']);
    $author = trim($_POST['author']);
    $grade = trim($_POST['grade']);
    $topic = trim($_POST['topic']);
    $subtopic = trim($_POST['subtopic']);
    $competency = trim($_POST['competency']);
    
    if (empty($title) || empty($video_url) || $meeting_number <= 0) {
        $message = 'Judul, URL Video, dan Nomor Pertemuan harus diisi!';
    } else {
        $stmt = $pdo->prepare("
            UPDATE courses 
            SET title = ?, 
                description = ?,
                video_url = ?, 
                meeting_number = ?,
                duration = ?,
                author = ?,
                grade = ?,
                topic = ?,
                subtopic = ?,
                competency = ?
            WHERE id = ? AND teacher_id = ?
        ");
        
        if ($stmt->execute([
            $title, 
            $description,
            $video_url, 
            $meeting_number,
            $duration,
            $author,
            $grade,
            $topic,
            $subtopic,
            $competency,
            $_GET['id'], 
            $_SESSION['user_id']
        ])) {
            $message = 'Pertemuan berhasil diupdate!';
            // Update local data
            $course = array_merge($course, [
                'title' => $title,
                'description' => $description,
                'video_url' => $video_url,
                'meeting_number' => $meeting_number,
                'duration' => $duration,
                'author' => $author,
                'grade' => $grade,
                'topic' => $topic,
                'subtopic' => $subtopic,
                'competency' => $competency
            ]);
        } else {
            $message = 'Gagal mengupdate pertemuan!';
        }
    }
}
?>

```php
<?php
// [Previous PHP code remains exactly the same until HTML part]
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Course - Biology Learning</title>
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

        .welcome-text {
            font-size: 16px;
            opacity: 0.9;
        }

        .logout-btn {
            background: rgba(255,255,255,0.1);
            padding: 8px 20px;
            border-radius: 5px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.2);
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 90px 20px 20px;
        }

        .edit-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .header-title {
            color: #2a5298;
            font-size: 24px;
            font-weight: 600;
        }

        .back-btn {
            background: #f1f1f1;
            color: #333;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: #e1e1e1;
            transform: translateY(-2px);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
            font-size: 14px;
        }

        input[type="text"],
        input[type="number"],
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #e1e1e1;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        textarea:focus {
            outline: none;
            border-color: #2a5298;
            background: white;
            box-shadow: 0 0 0 3px rgba(42, 82, 152, 0.1);
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .submit-btn {
            background: #2a5298;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 20px;
        }

        .submit-btn:hover {
            background: #1e3c72;
            transform: translateY(-2px);
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .container {
                padding: 80px 15px 20px;
            }

            .edit-card {
                padding: 20px;
            }

            .card-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .back-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-content">
            <h1>Dashboard Guru</h1>
            <span class="welcome-text">Selamat datang, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="../logout.php" class="logout-btn">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="edit-card">
            <div class="card-header">
                <h2 class="header-title">Edit Pertemuan</h2>
                <a href="dashboard.php" class="back-btn">Kembali</a>
            </div>

            <?php if($message): ?>
                <div class="message <?php echo strpos($message, 'berhasil') !== false ? 'success' : 'error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Judul Pertemuan</label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($course['title'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group full-width">
                        <label>Deskripsi</label>
                        <textarea name="description"><?php echo htmlspecialchars($course['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group full-width">
                        <label>URL Video</label>
                        <input type="text" name="video_url" value="<?php echo htmlspecialchars($course['video_url'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Pertemuan Ke-</label>
                        <input type="number" name="meeting_number" value="<?php echo $course['meeting_number'] ?? ''; ?>" min="1" max="16" required>
                    </div>

                    <div class="form-group">
                        <label>Durasi</label>
                        <input type="text" name="duration" value="<?php echo htmlspecialchars($course['duration'] ?? ''); ?>" placeholder="Contoh: 45 menit">
                    </div>

                    <div class="form-group">
                        <label>Author</label>
                        <input type="text" name="author" value="<?php echo htmlspecialchars($course['author'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Grade/Kelas</label>
                        <input type="text" name="grade" value="<?php echo htmlspecialchars($course['grade'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Topik</label>
                        <input type="text" name="topic" value="<?php echo htmlspecialchars($course['topic'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Subtopik</label>
                        <input type="text" name="subtopic" value="<?php echo htmlspecialchars($course['subtopic'] ?? ''); ?>">
                    </div>

                    <div class="form-group full-width">
                        <label>Kompetensi</label>
                        <textarea name="competency"><?php echo htmlspecialchars($course['competency'] ?? ''); ?></textarea>
                    </div>
                </div>

                <button type="submit" class="submit-btn">Update</button>
            </form>
        </div>
    </div>
</body>
</html>