<?php
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../index.php');
    exit;
}

$course_id = $_GET['course_id'] ?? 0;

// Validasi course dimiliki oleh guru ini
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND teacher_id = ?");
$stmt->execute([$course_id, $_SESSION['user_id']]);
$course = $stmt->fetch();

if (!$course) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question_text = trim($_POST['question_text']);
    $type = $_POST['type']; // 'essay' atau 'multiple_choice'
    
    if (!empty($question_text)) {
        $stmt = $pdo->prepare("
            INSERT INTO questions (course_id, question_text, type) 
            VALUES (?, ?, ?)
        ");
        
        if ($stmt->execute([$course_id, $question_text, $type])) {
            header('Location: manage-quiz.php?course_id=' . $course_id);
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tambah Soal - Biology Learning</title>
</head>
<body>
    <div class="container">
        <h2>Tambah Soal Quiz</h2>
        <h3><?php echo htmlspecialchars($course['title']); ?></h3>
        
        <form method="POST" class="question-form">
            <div class="form-group">
                <label>Jenis Soal</label>
                <select name="type" required>
                    <option value="essay">Essay</option>
                    <option value="multiple_choice">Pilihan Ganda</option>
                </select>
            </div>

            <div class="form-group">
                <label>Pertanyaan</label>
                <textarea name="question_text" rows="4" required></textarea>
            </div>

            <button type="submit" class="submit-btn">Tambah Soal</button>
        </form>
    </div>
</body>
</html>