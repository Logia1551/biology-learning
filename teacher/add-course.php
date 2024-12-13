<?php
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../index.php');
    exit;
}

// Ambil semua konten yang telah di-scrape
$stmt = $pdo->query("SELECT * FROM biology_content ORDER BY id");
$contents = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $meeting_number = (int)$_POST['meeting_number'];
    $content_id = (int)$_POST['content_id'];
    
    if (!empty($title) && $meeting_number > 0 && $content_id > 0) {
        try {
            // Ambil data konten yang dipilih
            $stmt = $pdo->prepare("SELECT * FROM biology_content WHERE id = ?");
            $stmt->execute([$content_id]);
            $content = $stmt->fetch();

            // Insert ke courses dengan data dari konten
            $stmt = $pdo->prepare("
                INSERT INTO courses 
                (title, description, video_url, meeting_number, teacher_id) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            if ($stmt->execute([
                $title,
                $content['description'],
                $content['video_url'],
                $meeting_number,
                $_SESSION['user_id']
            ])) {
                header('Location: dashboard.php');
                exit;
            }
        } catch (PDOException $e) {
            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tambah Pertemuan - Biology Learning</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f5f6fa;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h2 {
            color: #2a5298;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #374151;
        }
        select, input {
            width: 100%;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            color: #374151;
            background: white;
        }
        select {
            cursor: pointer;
        }
        .content-preview {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid #2a5298;
            display: none;
        }
        .preview-title {
            font-weight: 600;
            color: #2a5298;
            margin-bottom: 10px;
        }
        .preview-description {
            font-size: 14px;
            color: #4b5563;
            margin-bottom: 10px;
        }
        .video-preview {
            width: 100%;
            aspect-ratio: 16/9;
            margin-top: 10px;
            border-radius: 6px;
            overflow: hidden;
        }
        .submit-btn {
            background: #2a5298;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .submit-btn:hover {
            background: #1e3c72;
        }
        .error {
            color: #dc2626;
            font-size: 14px;
            margin-top: 5px;
        }
        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            color: #2a5298;
            text-decoration: none;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-btn">← Kembali ke Dashboard</a>
        
        <h2>Tambah Pertemuan Baru</h2>
        
        <form method="POST" class="course-form">
            <div class="form-group">
                <label>Pertemuan Ke-</label>
                <select name="meeting_number" required>
                    <?php for($i = 1; $i <= 16; $i++): ?>
                        <option value="<?php echo $i; ?>">Pertemuan <?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Judul Pertemuan</label>
                <input type="text" name="title" required>
            </div>

            <div class="form-group">
                <label>Pilih Konten Pembelajaran</label>
                <select name="content_id" required onchange="previewContent(this.value)">
                    <option value="">Pilih Konten</option>
                    <?php foreach($contents as $content): ?>
                        <option value="<?php echo $content['id']; ?>">
                            <?php echo htmlspecialchars($content['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="contentPreview" class="content-preview"></div>

            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>

            <button type="submit" class="submit-btn">Tambah Pertemuan</button>
        </form>
    </div>

    <script>
        function previewContent(contentId) {
            if (!contentId) {
                document.getElementById('contentPreview').style.display = 'none';
                return;
            }

            // Data konten yang sudah di-scrape
            const contents = <?php echo json_encode($contents); ?>;
            const content = contents.find(c => c.id === contentId);

            if (content) {
                const preview = document.getElementById('contentPreview');
                preview.style.display = 'block';
                preview.innerHTML = `
                    <div class="preview-title">${content.title}</div>
                    <div class="preview-description">${content.description}</div>
                    ${content.video_url ? `
                        <iframe 
                            class="video-preview"
                            src="${content.video_url}"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen>
                        </iframe>
                    ` : ''}
                `;
            }
        }
    </script>
</body>
</html>