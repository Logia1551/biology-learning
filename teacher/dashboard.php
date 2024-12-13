<?php
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../index.php');
    exit;
}

// Ambil semua pertemuan yang dibuat guru ini
$stmt = $pdo->prepare("
    SELECT c.*, 
        (SELECT COUNT(*) FROM questions q WHERE q.course_id = c.id) as question_count,
        (SELECT COUNT(DISTINCT sa.student_id) 
         FROM student_answers sa 
         JOIN questions q ON q.id = sa.question_id 
         WHERE q.course_id = c.id) as student_count
    FROM courses c 
    WHERE teacher_id = ? 
    ORDER BY meeting_number");
$stmt->execute([$_SESSION['user_id']]);
$courses = $stmt->fetchAll();

// Hitung statistik dashboard
$totalQuestions = 0;
$totalStudents = 0;
$activeMeetings = 0;

foreach ($courses as $course) {
    $totalQuestions += $course['question_count'];
    $totalStudents = max($totalStudents, $course['student_count']);
    if ($course['question_count'] > 0) $activeMeetings++;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Guru - Biology Learning</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

        .dashboard-sections {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .content-management {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .content-management h2 {
            color: #2a5298;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }

        .content-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .content-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            text-align: center;
        }

        .scrape-btn {
            background: #2a5298;
            color: white;
        }

        .scrape-btn:hover {
            background: #1e3c72;
            transform: translateY(-2px);
        }

        .manage-btn {
            background: #28a745;
            color: white;
        }

        .manage-btn:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .meetings-section {
            margin-top: 30px;
        }

        .meetings-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .meetings-title {
            color: #2a5298;
            font-size: 1.5rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #2a5298;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .add-course-btn {
            background: #28a745;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .add-course-btn:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .course-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .course-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .course-title {
            color: #2a5298;
            font-size: 18px;
        }

        .meeting-number {
            background: #e9ecef;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 14px;
            color: #495057;
        }

        .stats {
            display: flex;
            gap: 15px;
            margin: 15px 0;
            padding: 10px 0;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
        }

        .course-actions {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            flex: 1;
            padding: 8px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .edit-btn {
            background: #007bff;
            color: white;
        }

        .edit-btn:hover {
            background: #0056b3;
        }

        .quiz-btn {
            background: #17a2b8;
            color: white;
        }

        .quiz-btn:hover {
            background: #138496;
        }

        .delete-btn {
            background: #dc3545;
            color: white;
        }

        .delete-btn:hover {
            background: #c82333;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .empty-state h2 {
            color: #2a5298;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #6c757d;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .course-grid {
                grid-template-columns: 1fr;
            }

            .stats {
                flex-direction: column;
            }
        }
        @media (max-width: 768px) {
            .dashboard-sections {
                grid-template-columns: 1fr;
            }

            .content-actions {
                grid-template-columns: 1fr;
            }

            .meetings-header {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }

            .add-course-btn {
                text-align: center;
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
        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo count($courses); ?></div>
                <div class="stat-label">Total Pertemuan</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $activeMeetings; ?></div>
                <div class="stat-label">Pertemuan Aktif</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $totalQuestions; ?></div>
                <div class="stat-label">Total Soal</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $totalStudents; ?></div>
                <div class="stat-label">Total Siswa</div>
            </div>
        </div>

        <!-- Content Management Section -->
        <div class="dashboard-sections">
            <div class="content-management">
                <h2>Manajemen Konten</h2>
                <div class="content-actions">
                    <a href="../scraper/scrape-cell-biology.php" class="content-btn scrape-btn" 
                       onclick="return confirm('Mulai scraping konten Cell Biology?')">
                        Scrape Cell Biology
                    </a>
                    <a href="manage-content.php" class="content-btn manage-btn">
                        Kelola Konten Biologi
                    </a>
                </div>
            </div>
        </div>

        <!-- Meetings Section -->
        <div class="meetings-section">
            <div class="meetings-header">
                <h2 class="meetings-title">Kelola Pertemuan</h2>
                <a href="add-course.php" class="add-course-btn">+ Tambah Pertemuan</a>
            </div>

            <?php if(empty($courses)): ?>
                <div class="empty-state">
                    <h2>Belum ada pertemuan</h2>
                    <p>Mulai tambahkan pertemuan pertama Anda</p>
                    <a href="add-course.php" class="add-course-btn">Tambah Pertemuan</a>
                </div>
            <?php else: ?>
                <div class="course-grid">
                    <?php foreach($courses as $course): ?>
                        <div class="course-card">
                            <div class="course-header">
                                <h3 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h3>
                                <span class="meeting-number">Pertemuan <?php echo $course['meeting_number']; ?></span>
                            </div>

                            <div class="stats">
                                <div class="stat-item">
                                    <span class="stat-value"><?php echo $course['question_count']; ?></span>
                                    <span class="stat-label">Soal</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-value"><?php echo $course['student_count']; ?></span>
                                    <span class="stat-label">Siswa</span>
                                </div>
                            </div>

                            <div class="course-actions">
                                <a href="edit-course.php?id=<?php echo $course['id']; ?>" class="action-btn edit-btn">
                                    Edit
                                </a>
                                <a href="manage-quiz.php?course_id=<?php echo $course['id']; ?>" class="action-btn quiz-btn">
                                    Soal
                                </a>
                                <button onclick="deleteCourse(<?php echo $course['id']; ?>)" class="action-btn delete-btn">
                                    Hapus
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function deleteCourse(id) {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: "Semua soal dan jawaban siswa untuk pertemuan ini akan ikut terhapus secara permanen!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'delete-course.php?id=' + encodeURIComponent(id);
        }
    });
}
    </script>
</body>
</html>