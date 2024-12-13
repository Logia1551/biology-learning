<?php
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../index.php');
    exit;
}

$id = $_GET['id'] ?? 0;
$content = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM biology_content WHERE id = ?");
    $stmt->execute([$id]);
    $content = $stmt->fetch();
}

if (!$content) {
    header('Location: manage-content.php');
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($content['title']); ?> - Biology Learning</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background: #f5f6fa;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .content-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .content-title {
            color: #2a5298;
            font-size: 24px;
            margin-bottom: 10px;
        }
        .video-container {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
            margin: 20px 0;
            border-radius: 10px;
        }
        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        .content-description {
            margin: 20px 0;
            line-height: 1.8;
            color: #444;
        }
        .back-btn, .edit-btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            color: white;
            margin-right: 10px;
        }
        .back-btn {
            background: #2a5298;
        }
        .edit-btn {
            background: #28a745;
        }
        .actions {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="content-header">
            <h1 class="content-title"><?php echo htmlspecialchars($content['title']); ?></h1>
            <div class="meta">
                Topic: <?php echo htmlspecialchars($content['topic']); ?>
            </div>
        </div>

        <?php if (!empty($content['video_url'])): ?>
            <div class="video-container">
                <iframe 
                    src="<?php echo htmlspecialchars($content['video_url']); ?>"
                    frameborder="0"
                    allowfullscreen>
                </iframe>
            </div>
        <?php endif; ?>

        <div class="content-description">
            <?php echo nl2br(htmlspecialchars($content['description'])); ?>
        </div>

        <div class="actions">
            <a href="manage-content.php" class="back-btn">Kembali</a>
            <a href="edit-content.php?id=<?php echo $content['id']; ?>" class="edit-btn">Edit Konten</a>
        </div>
    </div>
</body>
</html>