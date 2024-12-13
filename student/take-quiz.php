<?php
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit;
}

$course_id = $_GET['course_id'] ?? 0;

// Cek apakah sudah pernah mengerjakan
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM student_answers sa
    JOIN questions q ON sa.question_id = q.id
    WHERE sa.student_id = ? AND q.course_id = ?
");
$stmt->execute([$_SESSION['user_id'], $course_id]);
$result = $stmt->fetch();

if ($result['count'] > 0) {
    echo "<div class='warning-message'>Anda sudah mengerjakan quiz ini. Silakan hubungi guru untuk mengulang.</div>";
    exit;
}

// Ambil soal essay
$stmt = $pdo->prepare("
    SELECT * FROM questions 
    WHERE course_id = ? AND type = 'essay'
    ORDER BY id
");
$stmt->execute([$course_id]);
$essays = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Take Quiz - Semantic Learning</title>
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
            padding: 20px;
            padding-top: 80px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f1f1;
        }

        .header h1 {
            color: #2a5298;
            font-size: 24px;
        }

        .back-btn {
            padding: 8px 20px;
            background: #f1f1f1;
            color: #333;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: #e1e1e1;
        }

        .question-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #eee;
        }

        .question-text {
            color: #2c3e50;
            font-size: 16px;
            margin-bottom: 15px;
            font-weight: 500;
        }

        textarea {
            width: 100%;
            min-height: 120px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            resize: vertical;
            font-size: 14px;
            line-height: 1.5;
            transition: border-color 0.3s ease;
        }

        textarea:focus {
            outline: none;
            border-color: #2a5298;
        }

        .submit-btn {
            width: 100%;
            padding: 15px;
            background: #2a5298;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease;
            margin-top: 20px;
        }

        .submit-btn:hover {
            background: #1e3c72;
        }

        .timer {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 12px 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            font-weight: bold;
            color: #2a5298;
            z-index: 1000;
        }

        .warning-message {
            background: #fff3cd;
            color: #856404;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 20px auto;
            max-width: 600px;
            border: 1px solid #ffeeba;
        }

        .question-number {
            background: #2a5298;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            margin-right: 10px;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            .timer {
                left: 50%;
                transform: translateX(-50%);
                width: fit-content;
            }
        }
    </style>
</head>
<body>
    <div class="timer" id="timer">Sisa Waktu: 60:00</div>

    <div class="container">
        <div class="header">
            <h1>Quiz Essay</h1>
            <a href="view-course.php?id=<?php echo $course_id; ?>" class="back-btn" id="backBtn">Kembali</a>
        </div>

        <form method="POST" action="submit-quiz.php" id="quizForm">
            <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
            
            <?php foreach($essays as $index => $question): ?>
                <div class="question-item">
                    <p class="question-text">
                        <span class="question-number"><?php echo ($index + 1); ?></span>
                        <?php echo htmlspecialchars($question['question_text']); ?>
                    </p>
                    <textarea 
                        name="essay_<?php echo $question['id']; ?>" 
                        required
                        placeholder="Tulis jawaban Anda di sini..."
                    ></textarea>
                </div>
            <?php endforeach; ?>

            <button type="submit" class="submit-btn">Submit Quiz</button>
        </form>
    </div>

    <script>
        // Timer
        let timeLeft = 60 * 60; // 60 menit
        const timerElement = document.getElementById('timer');
        const quizForm = document.getElementById('quizForm');
        
        function updateTimer() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            timerElement.textContent = `Sisa Waktu: ${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            if (timeLeft <= 0) {
                alert('Waktu habis! Quiz akan dikumpulkan otomatis.');
                quizForm.submit();
            }
            timeLeft--;
        }

        setInterval(updateTimer, 1000);

        // Konfirmasi sebelum meninggalkan halaman
        window.addEventListener('beforeunload', function(e) {
            e.preventDefault();
            e.returnValue = '';
        });

        // Handle tombol kembali
        document.getElementById('backBtn').addEventListener('click', function(e) {
            e.preventDefault();
            if(confirm('Apakah Anda yakin ingin keluar? Jawaban Anda tidak akan tersimpan.')) {
                window.location.href = this.href;
            }
        });
    </script>
</body>
</html>