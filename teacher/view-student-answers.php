<?php
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../index.php');
    exit;
}

if (!isset($_GET['student_id']) || !isset($_GET['course_id'])) {
    header('Location: dashboard.php');
    exit;
}

$student_id = $_GET['student_id'];
$course_id = $_GET['course_id'];

// Ambil data siswa
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$student_id]);
$student_name = $stmt->fetchColumn();

// Ambil data course
$stmt = $pdo->prepare("SELECT title, meeting_number FROM courses WHERE id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch();

// Ambil jawaban dan nilai siswa
$stmt = $pdo->prepare("
    SELECT 
        q.question_text, 
        q.keywords,
        sa.score
    FROM student_answers sa
    JOIN questions q ON sa.question_id = q.id
    WHERE sa.student_id = ? AND q.course_id = ?
    ORDER BY q.id
");
$stmt->execute([$student_id, $course_id]);
$student_answers = $stmt->fetchAll();

// Hitung nilai rata-rata
$total_score = 0;
$answer_count = count($student_answers);
if ($answer_count > 0) {
    foreach ($student_answers as $answer) {
        $total_score += $answer['score'];
    }
    $average_score = $total_score / $answer_count;
} else {
    $average_score = 0;
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>View Student Answers - Semantic Learning</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        /* New styles for student info */
        .student-info {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }

        .info-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }

        .info-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 18px;
            font-weight: 600;
            color: #2a5298;
        }

        .question-item {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .question-number {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }

        .question-text {
            font-size: 16px;
            font-weight: 600;
            color: #2a5298;
            margin-bottom: 15px;
        }

        .keywords {
            background: #e9ecef;
            padding: 10px;
            border-radius: 6px;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .keyword-label {
            font-weight: 600;
            color: #495057;
            margin-right: 5px;
        }

        .answer-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
        }

        .answer-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 5px;
        }

        .answer-text {
            color: #212529;
            line-height: 1.5;
        }

        .score-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            margin-top: 10px;
        }

        .score-high {
            background: #d4edda;
            color: #155724;
        }

        .score-medium {
            background: #fff3cd;
            color: #856404;
        }

        .score-low {
            background: #f8d7da;
            color: #721c24;
        }

        .back-btn {
            background: #f1f1f1;
            color: #333;
            padding: 12px 24px;
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

        .quiz-section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .question-item {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 4px;
        }

        .question-text {
            font-weight: bold;
            margin-bottom: 10px;
        }

        .answer-item {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }

        .answer-icon {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }

        .answer-icon.correct {
            background-color: #28a745;
        }

        .answer-icon.incorrect {
            background-color: #dc3545;
        }

        .answer-text {
            flex-grow: 1;
        }

        @media (max-width: 768px) {
            .container {
                padding: 90px 15px 20px;
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
        <div class="dashboard-header">
            <h2>Review Jawaban Quiz</h2>
            <a href="manage-quiz.php?course_id=<?php echo $course_id; ?>" class="back-btn">Kembali</a>
        </div>

        <div class="student-info">
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Nama Siswa</div>
                    <div class="info-value"><?php echo htmlspecialchars($student_name); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Pertemuan</div>
                    <div class="info-value"><?php echo $course['meeting_number']; ?> - <?php echo htmlspecialchars($course['title']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Nilai Rata-rata</div>
                    <div class="info-value"><?php echo number_format($average_score, 2); ?></div>
                </div>
            </div>
        </div>

        <?php foreach ($student_answers as $index => $answer): ?>
            <div class="question-item">
                <div class="question-number">Soal <?php echo $index + 1; ?></div>
                <div class="question-text"><?php echo htmlspecialchars($answer['question_text']); ?></div>
                
                <div class="keywords">
                    <span class="keyword-label">Kata Kunci:</span>
                    <?php echo htmlspecialchars($answer['keywords']); ?>
                </div>

                <div class="answer-section">
                    <?php
                    $scoreClass = '';
                    if ($answer['score'] >= 80) $scoreClass = 'score-high';
                    else if ($answer['score'] >= 60) $scoreClass = 'score-medium';
                    else $scoreClass = 'score-low';
                    ?>
                    <div class="score-badge <?php echo $scoreClass; ?>">
                        Nilai: <?php echo number_format($answer['score'], 2); ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>