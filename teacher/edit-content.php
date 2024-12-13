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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("
            UPDATE biology_content 
            SET title = ?, description = ?, video_url = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $_POST['title'],
            $_POST['description'],
            $_POST['video_url'],
            $id
        ]);

        header('Location: view-content.php?id=' . $id);
        exit;
    } catch (PDOException $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Content - Biology Learning</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background: #f5f6fa;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #444;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        textarea {
            height: 200px;
            resize: vertical;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            color: white;
        }
        .btn-primary {
            background: #2a5298;
        }
        .btn-secondary {
            background: #6c757d;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit Konten</h2>
        
        <form method="POST">
            <div class="form-group">
                <label>Judul</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($content['title']); ?>" required>
            </div>

            <div class="form-group">
                <label>Video URL</label>
                <input type="text" name="video_url" value="<?php echo htmlspecialchars($content['video_url']); ?>">
            </div>

            <div class="form-group">
                <label>Deskripsi</label>
                <textarea name="description" required><?php echo htmlspecialchars($content['description']); ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="manage-content.php" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</body>
</html>