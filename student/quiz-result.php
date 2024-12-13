<?php
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit;
}

$course_id = $_GET['course_id'] ?? 0;

// Ambil total soal
$stmt = $pdo->prepare("SELECT COUNT(*) as total_questions FROM questions WHERE course_id = ?");
$stmt->execute([$course_id]);
$total_questions = $stmt->fetch()['total_questions'];

// Ambil nilai jawaban siswa
$stmt = $pdo->prepare("
    SELECT SUM(score) as total_score
    FROM student_answers sa
    JOIN questions q ON sa.question_id = q.id
    WHERE sa.student_id = ? AND q.course_id = ?
");
$stmt->execute([$_SESSION['user_id'], $course_id]);
$result = $stmt->fetch();
$total_score = $result['total_score'] ?? 0;

// Hitung jumlah jawaban yang sudah diisi
$stmt = $pdo->prepare("
    SELECT COUNT(*) as answered_questions 
    FROM student_answers sa
    JOIN questions q ON sa.question_id = q.id
    WHERE sa.student_id = ? AND q.course_id = ?
");
$stmt->execute([$_SESSION['user_id'], $course_id]);
$answered_questions = $stmt->fetch()['answered_questions'];

// Hitung nilai akhir (skala 0-100)
// Jika tiap soal bernilai 10, maka nilai maksimal adalah total_questions * 10
$max_possible_score = $total_questions * 10;
if ($max_possible_score > 0) {
    $final_score = ($total_score / $max_possible_score) * 100;
} else {
    $final_score = 0;
}

// Ambil data course
$stmt = $pdo->prepare("SELECT title FROM courses WHERE id = ?");
$stmt->execute([$course_id]);
$course_title = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Quiz Result - Semantic Learning</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .result-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }

        .result-title {
            color: #2a5298;
            font-size: 28px;
            margin-bottom: 30px;
        }

        .course-info {
            color: #666;
            font-size: 16px;
            margin-bottom: 20px;
        }

        .score-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .score {
            font-size: 64px;
            color: #2a5298;
            font-weight: bold;
            margin: 20px 0;
        }

        .questions-info {
            color: #666;
            font-size: 14px;
            margin: 10px 0;
        }

        .message {
            color: #666;
            margin: 20px 0;
            padding: 15px;
            border-radius: 8px;
            font-size: 16px;
            line-height: 1.5;
        }

        .excellent {
            background: #d4edda;
            color: #155724;
        }

        .good {
            background: #cce5ff;
            color: #004085;
        }

        .needs-improvement {
            background: #fff3cd;
            color: #856404;
        }

        .back-btn {
            display: inline-block;
            padding: 12px 30px;
            background: #2a5298;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 16px;
            transition: background 0.3s ease;
            margin-top: 20px;
        }

        .back-btn:hover {
            background: #1e3c72;
        }

        .score-breakdown {
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            text-align: left;
            font-size: 14px;
            color: #666;
        }

        .score-item {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="result-container">
        <h1 class="result-title">Hasil Quiz</h1>
        
        <div class="course-info">
            <?php echo htmlspecialchars($course_title); ?>
        </div>

        <div class="score-details">
            <div class="score"><?php echo number_format($final_score, 1); ?></div>
            <div class="questions-info">
                Menjawab <?php echo $answered_questions; ?> dari <?php echo $total_questions; ?> soal
            </div>
            
            <div class="score-breakdown">
                <div class="score-item">Total Nilai: <?php echo number_format($total_score, 2); ?></div>
                <div class="score-item">Jumlah Soal: <?php echo $total_questions; ?></div>
                <div class="score-item">Nilai Maksimal: <?php echo $max_possible_score; ?></div>
                <div class="score-item">Nilai per Soal: 10</div>
            </div>
        </div>
        
        <div class="message <?php 
            if ($final_score >= 80) echo 'excellent';
            elseif ($final_score >= 60) echo 'good';
            else echo 'needs-improvement';
        ?>">
            <?php
            if ($final_score >= 80) {
                echo "Selamat! Anda mendapatkan nilai yang sangat baik. Terus pertahankan pemahaman Anda!";
            } elseif ($final_score >= 60) {
                echo "Bagus! Anda telah menunjukkan pemahaman yang cukup baik. Terus tingkatkan!";
            } else {
                echo "Jangan menyerah! Cobalah untuk lebih memahami materi dan tingkatkan belajar Anda.";
            }
            ?>
        </div>

        <a href="dashboard.php" class="back-btn">Kembali ke Dashboard</a>
    </div>
</body>
</html>