<?php
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../index.php');
    exit;
}

if (!isset($_GET['course_id'])) {
    header('Location: dashboard.php');
    exit;
}

$course_id = $_GET['course_id'];

// Ambil data course
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND teacher_id = ?");
$stmt->execute([$course_id, $_SESSION['user_id']]);
$course = $stmt->fetch();

if (!$course) {
    header('Location: dashboard.php');
    exit;
}

// Ambil semua soal
$stmt = $pdo->prepare("SELECT * FROM questions WHERE course_id = ?");
$stmt->execute([$course_id]);
$questions = $stmt->fetchAll();

// Tambahan: Ambil konten biologi
$stmt = $pdo->query("SELECT * FROM biology_content ORDER BY id DESC");
$biology_contents = $stmt->fetchAll();

$message = '';

// Handle aksi mengizinkan mengulang
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'allow_retake') {
        $student_id = $_POST['student_id'];
        
        // Hapus jawaban sebelumnya
        $stmt = $pdo->prepare("
            DELETE sa FROM student_answers sa
            JOIN questions q ON sa.question_id = q.id
            WHERE sa.student_id = ? AND q.course_id = ?
        ");
        
        if ($stmt->execute([$student_id, $course_id])) {
            $message = 'Siswa diizinkan untuk mengulang quiz!';
        } else {
            $message = 'Gagal mengatur ulang quiz!';
        }
    }
    // Handle penambahan soal essay
    elseif ($_POST['action'] == 'add_essay') {
        $question_text = trim($_POST['question_text']);
        $keywords = trim($_POST['keywords']);
        $content_id = isset($_POST['content_id']) ? $_POST['content_id'] : null;

        $stmt = $pdo->prepare("INSERT INTO questions (course_id, question_text, type, keywords, content_id) VALUES (?, ?, 'essay', ?, ?)");
        if ($stmt->execute([$course_id, $question_text, $keywords, $content_id])) {
            $message = 'Soal essay berhasil ditambahkan!';
        }
    }
    // Handle penghapusan soal
    elseif ($_POST['action'] == 'delete_question') {
        $question_id = $_POST['question_id'];

        $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ? AND course_id = ?");
        if ($stmt->execute([$question_id, $course_id])) {
            $message = 'Soal berhasil dihapus!';
        } else {
            $message = 'Gagal menghapus soal!';
        }
    }
}
?>
<?php
// [Previous PHP code remains exactly the same until HTML part]

$course_id = $_GET['course_id'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Quiz - Biology Learning</title>
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 90px 20px 20px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .card-title {
            color: #2a5298;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2c3e50;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #2a5298;
            box-shadow: 0 0 0 3px rgba(42, 82, 152, 0.1);
        }

        select.form-control {
            background-color: white;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: #2a5298;
            color: white;
        }

        .btn-primary:hover {
            background: #1e3c72;
            transform: translateY(-1px);
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .btn-info:hover {
            background: #138496;
        }

        .content-preview {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 15px 0;
            border: 1px solid #e9ecef;
        }

        .preview-title {
            font-weight: 600;
            color: #2a5298;
            margin-bottom: 10px;
        }

        .preview-description {
            color: #4a5568;
            line-height: 1.6;
            font-size: 14px;
        }

        .questions-list {
            display: grid;
            gap: 15px;
        }

        .question-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }

        .question-number {
            font-weight: 600;
            color: #2a5298;
        }

        .question-text {
            margin-bottom: 10px;
            line-height: 1.5;
        }

        .student-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            overflow: hidden;
        }

        .student-table th,
        .student-table td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
        }

        .student-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
            text-align: left;
        }

        .student-table tr:last-child td {
            border-bottom: none;
        }

        .table-actions {
            display: flex;
            gap: 10px;
        }

        .message {
            padding: 15px;
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

        @media (max-width: 768px) {
            .container {
                padding: 80px 15px 20px;
            }

            .card {
                padding: 20px;
            }

            .card-header {
                flex-direction: column;
                align-items: start;
                gap: 10px;
            }

            .table-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
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
        <?php if($message): ?>
            <div class="message <?php echo strpos($message, 'berhasil') !== false ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Form Tambah Soal -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Tambah Soal Essay</h2>
                <a href="dashboard.php" class="back-btn">Kembali</a>
            </div>
            
            <form method="POST" id="questionForm">
                <input type="hidden" name="action" value="add_essay">
                

                <?php foreach($biology_contents as $content): ?>
                    <div class="content-preview" id="preview-<?php echo $content['id']; ?>" style="display: none;">
                        <div class="preview-title"><?php echo htmlspecialchars($content['title']); ?></div>
                        <div class="preview-description"><?php echo htmlspecialchars($content['description']); ?></div>
                    </div>
                <?php endforeach; ?>

                <div class="form-group">
                    <label>Pertanyaan</label>
                    <input type="text" name="question_text" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Kata Kunci (pisahkan dengan koma)</label>
                    <input type="text" name="keywords" class="form-control" 
                           placeholder="contoh: semantic web, rdf, ontology" required>
                </div>

                <button type="submit" class="btn btn-primary">Simpan Soal</button>
            </form>
        </div>

        <!-- Daftar Soal -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Daftar Soal</h2>
            </div>
            
            <div class="questions-list">
                <?php foreach($questions as $index => $question): ?>
                    <div class="question-item">
                        <div class="question-header">
                            <div class="question-number">Soal <?php echo $index + 1; ?></div>
                            <form method="POST" style="display: inline;" 
                                  onsubmit="return confirm('Yakin ingin menghapus soal ini?');">
                                <input type="hidden" name="action" value="delete_question">
                                <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                                <button type="submit" class="btn btn-danger">Hapus</button>
                            </form>
                        </div>
                        
                        <div class="question-text">
                            <?php echo htmlspecialchars($question['question_text']); ?>
                        </div>
                        
                        <?php if($question['type'] == 'essay'): ?>
                            <div class="preview-description">
                                <strong>Kata Kunci:</strong> <?php echo htmlspecialchars($question['keywords']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Daftar Siswa -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Daftar Siswa yang Sudah Mengerjakan</h2>
            </div>
            
            <table class="student-table">
                <thead>
                    <tr>
                        <th>Nama Siswa</th>
                        <th>Nilai</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->prepare("
                        SELECT DISTINCT 
                            u.id as student_id,
                            u.username,
                            AVG(sa.score) as average_score
                        FROM users u
                        JOIN student_answers sa ON u.id = sa.student_id
                        JOIN questions q ON sa.question_id = q.id
                        WHERE q.course_id = ?
                        GROUP BY u.id, u.username
                    ");
                    $stmt->execute([$course_id]);
                    $students = $stmt->fetchAll();

                    foreach($students as $student):
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['username']); ?></td>
                        <td><?php echo number_format($student['average_score'], 2); ?></td>
                        <td>
                            <div class="table-actions">
                                <form method="POST">
                                    <input type="hidden" name="action" value="allow_retake">
                                    <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                                    <button type="submit" class="btn btn-info">Izinkan Mengulang</button>
                                </form>
                                <a href="view-student-answers.php?student_id=<?php echo $student['student_id']; ?>&course_id=<?php echo $course_id; ?>" 
                                   class="btn btn-primary">Lihat Jawaban</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.getElementById('contentSelect').addEventListener('change', function() {
            document.querySelectorAll('.content-preview').forEach(preview => {
                preview.style.display = 'none';
            });
            
            const selectedId = this.value;
            if (selectedId) {
                const preview = document.getElementById('preview-' + selectedId);
                if (preview) {
                    preview.style.display = 'block';
                }
            }
        });
    </script>
</body>
</html>