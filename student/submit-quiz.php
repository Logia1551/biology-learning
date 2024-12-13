<?php
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['course_id'])) {
    header('Location: dashboard.php');
    exit;
}

$course_id = $_POST['course_id'];
$student_id = $_SESSION['user_id'];
$total_score = 0;
$total_questions = 0;

try {
    $pdo->beginTransaction();
    
    // Proses jawaban essay
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'essay_') === 0) {
            $question_id = substr($key, 6);
            
            // Ambil kata kunci
            $stmt = $pdo->prepare("
                SELECT keywords FROM questions 
                WHERE id = ? AND course_id = ?
            ");
            $stmt->execute([$question_id, $course_id]);
            $question = $stmt->fetch();
            
            // Hitung skor berdasarkan kata kunci
            $keywords = explode(',', $question['keywords']);
            $score = 0;
            $answer_text = strtolower(trim($value)); 
            
            foreach ($keywords as $keyword) {
                $keyword = strtolower(trim($keyword));
                if (strpos($answer_text, $keyword) !== false) {
                    $score += 10/count($keywords);
                }
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO student_answers 
                (student_id, question_id, answer_text, score) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$student_id, $question_id, $value, $score]);
            
            $total_score += $score;
            $total_questions++;
        }
    }

    $pdo->commit();
    $final_score = $total_score / $total_questions;
    header("Location: quiz-result.php?course_id=$course_id&score=" . number_format($final_score, 2));
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Submitting Quiz - Semantic Learning</title>
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
        }

        .loading-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #2a5298;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        .loading-text {
            color: #2a5298;
            font-size: 18px;
            margin-top: 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="loading-container">
        <div class="loading-spinner"></div>
        <div class="loading-text">Memproses jawaban Anda...</div>
    </div>
</body>
</html>