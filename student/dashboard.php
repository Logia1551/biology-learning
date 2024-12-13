<?php
require_once '../config/database.php';

// Cek login dan role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit;
}

// Ambil data pertemuan
$stmt = $pdo->query("SELECT * FROM courses ORDER BY meeting_number");
$courses = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM courses ORDER BY meeting_number");
$stmt->execute();
$courses = $stmt->fetchAll();

// Fungsi untuk mengecek status quiz
function checkQuizStatus($pdo, $course_id, $student_id) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM student_answers sa
        JOIN questions q ON sa.question_id = q.id
        WHERE sa.student_id = ? AND q.course_id = ?
    ");
    $stmt->execute([$student_id, $course_id]);
    $result = $stmt->fetch();
    return $result['count'] > 0;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Dashboard - Biology Learning</title>
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

        .nav-brand {
            color: white;
            font-size: 24px;
            font-weight: bold;
            text-decoration: none;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .profile-menu {
            position: relative;
            display: inline-block;
        }

        .profile-toggle {
            background: rgba(255,255,255,0.1);
            padding: 8px 15px;
            border-radius: 5px;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            border: none;
            font-size: 15px;
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 8px 0;
            min-width: 200px;
            display: none;
            margin-top: 5px;
        }

        .dropdown-menu.active {
            display: block;
        }

        .menu-item {
            padding: 10px 15px;
            color: #333;
            text-decoration: none;
            display: block;
            transition: background 0.3s ease;
        }

        .menu-item:hover {
            background: #f8f9fa;
        }

        .menu-divider {
            height: 1px;
            background: #eee;
            margin: 8px 0;
        }

        .logout-item {
            color: #dc3545;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 90px 20px 20px;
        }

        .section-title {
            color: #2a5298;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 25px;
        }

        .meeting-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .meeting-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .meeting-card:hover {
            transform: translateY(-5px);
        }

        .meeting-header {
            color: #2a5298;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .meeting-title {
            color: #4a5568;
            margin-bottom: 15px;
            font-size: 15px;
            line-height: 1.5;
        }

        .progress-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 12px;
        }

        .not-started {
            background: #e9ecef;
            color: #495057;
        }

        .completed {
            background: #d4edda;
            color: #155724;
        }

        .view-btn {
            display: inline-block;
            padding: 8px 16px;
            background: #2a5298;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .view-btn:hover {
            background: #1e3c72;
            transform: translateY(-1px);
        }

        .no-content {
            color: #6c757d;
            font-size: 14px;
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .completed-text {
            color: #155724;
            font-weight: 500;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 80px 15px 20px;
            }

            .meeting-grid {
                grid-template-columns: 1fr;
            }

            .nav-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .nav-right {
                width: 100%;
                justify-content: center;
            }

            .dropdown-menu {
                right: 50%;
                transform: translateX(50%);
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-content">
            <a href="dashboard.php" class="nav-brand">Biology Learning</a>
            <div class="nav-right">
                <div class="profile-menu">
                    <button class="profile-toggle" onclick="toggleDropdown()">
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <div class="dropdown-menu" id="profileMenu">
                        <a href="edit-profile.php" class="menu-item">Edit Profil</a>
                        <a href="change-password.php" class="menu-item">Ganti Password</a>
                        <div class="menu-divider"></div>
                        <a href="../logout.php" class="menu-item logout-item">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <h1 class="section-title">Daftar Pertemuan</h1>
        
        <div class="meeting-grid">
            <?php for($i = 1; $i <= 16; $i++): ?>
                <div class="meeting-card">
                    <h3 class="meeting-header">Pertemuan <?php echo $i; ?></h3>
                    <?php
                    $courseFound = false;
                    foreach($courses as $course) {
                        if($course['meeting_number'] == $i) {
                            $courseFound = true;
                            $quizCompleted = checkQuizStatus($pdo, $course['id'], $_SESSION['user_id']);
                            ?>
                            <div class="progress-badge <?php echo $quizCompleted ? 'completed' : 'not-started'; ?>">
                                <?php echo $quizCompleted ? 'Selesai' : 'Belum selesai'; ?>
                            </div>
                            <p class="meeting-title"><?php echo htmlspecialchars($course['title']); ?></p>
                            <?php if(!$quizCompleted): ?>
                                <a href="view-course.php?id=<?php echo $course['id']; ?>" class="view-btn">Lihat Materi</a>
                            <?php else: ?>
                                <span class="completed-text">Quiz telah selesai</span>
                            <?php endif; ?>
                            <?php
                            break;
                        }
                    }
                    if(!$courseFound) {
                        echo '<div class="progress-badge not-started">Belum tersedia</div>';
                        echo '<p class="no-content">Belum ada materi</p>';
                    }
                    ?>
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <script>
        function toggleDropdown() {
            const menu = document.getElementById('profileMenu');
            menu.classList.toggle('active');
            
            // Close when clicking outside
            document.addEventListener('click', function(event) {
                const isClickInside = menu.contains(event.target) || 
                                    event.target.closest('.profile-toggle');
                if (!isClickInside) {
                    menu.classList.remove('active');
                }
            });
        }
    </script>
</body>
</html>