<?php
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../index.php');
    exit;
}

// Ambil semua konten biologi
$stmt = $pdo->query("
    SELECT * FROM biology_content 
    ORDER BY id DESC
");
$contents = $stmt->fetchAll();

// Array URL untuk re-scraping
$scrapeUrls = [
    'biological-energy' => 'https://www.biologyonline.com/tutorials/biological-energy-adp-atp',
    'cell-respiration' => 'https://www.biologyonline.com/tutorials/cell-respiration',
    'photosynthesis' => 'https://www.biologyonline.com/tutorials/photosynthesis-photolysis-and-carbon-fixation',
    'dna-structure' => 'https://www.biologyonline.com/tutorials/dna-structure-dna-replication',
    'protein-synthesis' => 'https://www.biologyonline.com/tutorials/protein-synthesis',
    'golgi-apparatus' => 'https://www.biologyonline.com/tutorials/role-of-golgi-apparatus-endoplasmic-reticulum-in-protein-synthesis',
    'protein-variety' => 'https://www.biologyonline.com/tutorials/protein-variety',
    'biological-viruses' => 'https://www.biologyonline.com/tutorials/biological-viruses',
    'cell-defense' => 'https://www.biologyonline.com/tutorials/biological-cell-defense',
    'immunity' => 'https://www.biologyonline.com/tutorials/passive-and-active-types-of-immunity',
    'plant-defense' => 'https://www.biologyonline.com/tutorials/plant-cell-defense',
    'cell-intro' => 'https://www.biologyonline.com/tutorials/biological-cell-introduction'
];

// Handle delete
if(isset($_POST['delete']) && isset($_POST['id'])) {
    $id = $_POST['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM biology_content WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Konten berhasil dihapus'];
    } catch(PDOException $e) {
        $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Gagal menghapus konten'];
    }
    header('Location: manage-content.php');
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manajemen Konten Biologi</title> 
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link ref="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            background: #f0f2f5;
            color: #1a1a1a;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .header {
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2a5298;
        }

        .add-btn {
            background: #2a5298;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .add-btn:hover {
            background: #1e3c72;
            transform: translateY(-1px);
        }

        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 1.5rem;
        }

        .content-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .content-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .card-header {
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: start;
            border-bottom: 1px solid #eee;
        }

        .content-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2a5298;
            margin: 0;
        }

        .rescrape-btn {
            background: #f8f9fa;
            color: #495057;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .rescrape-btn:hover {
            background: #e9ecef;
            color: #212529;
        }

        .video-container {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            margin-bottom: 1rem;
            background: #f8f9fa;
        }

        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }

        .card-body {
            padding: 1.5rem;
        }

        .description {
            color: #4a5568;
            font-size: 0.925rem;
            max-height: 100px;
            overflow: hidden;
            position: relative;
            margin-bottom: 1.5rem;
        }

        .description::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 40px;
            background: linear-gradient(transparent, white);
        }

        .btn-group {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem;
        }

        .btn {
            padding: 0.75rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            color: white;
        }

        .btn-view { background: #2a5298; }
        .btn-view:hover { background: #1e3c72; }
        
        .btn-edit { background: #28a745; }
        .btn-edit:hover { background: #218838; }
        
        .btn-delete { 
            background: #dc3545;
            border: none;
            width: 100%;
            font-family: inherit;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .btn-delete:hover { background: #c82333; }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .delete-form {
            margin: 0;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['flash_message']['type']; ?>">
                <i class="fas fa-<?php echo $_SESSION['flash_message']['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php 
                echo $_SESSION['flash_message']['message'];
                unset($_SESSION['flash_message']);
                ?>
            </div>
        <?php endif; ?>

        <div class="header">
        <a href="../teacher/dashboard.php" class="add-btn">
                <i class="fas fa-plus"></i>
                Back
            </a>
            <h2>Manajemen Konten Biologi</h2>
            <a href="../scraper/scrape-cell-biology.php" class="add-btn">
                <i class="fas fa-plus"></i>
                Scrape Konten Baru
            </a>
        </div>

        <div class="content-grid">
            <?php foreach ($contents as $content): ?>
                <div class="content-card">
                    <div class="card-header">
                        <h3 class="content-title"><?php echo htmlspecialchars($content['title']); ?></h3>
                        <a href="../scraper/scrape-cell-biology.php?scrape=<?php 
                            $key = array_search($content['original_url'] ?? '', $scrapeUrls);
                            echo $key !== false ? $key : '';
                        ?>" class="rescrape-btn">
                            <i class="fas fa-sync-alt"></i>
                            Re-scrape
                        </a>
                    </div>
                    
                    <?php if (!empty($content['video_url'])): ?>
                        <div class="video-container">
                            <iframe src="<?php echo htmlspecialchars($content['video_url']); ?>" allowfullscreen></iframe>
                        </div>
                    <?php endif; ?>

                    <div class="card-body">
                        <div class="description">
                            <?php echo nl2br(htmlspecialchars($content['description'])); ?>
                        </div>

                        <div class="btn-group">
                            <a href="view-content.php?id=<?php echo $content['id']; ?>" class="btn btn-view">
                                <i class="fas fa-eye"></i>
                                Lihat
                            </a>
                            <a href="edit-content.php?id=<?php echo $content['id']; ?>" class="btn btn-edit">
                                <i class="fas fa-edit"></i>
                                Edit
                            </a>
                            <form action="" method="POST" class="delete-form" onsubmit="return confirm('Yakin ingin menghapus konten ini?');">
                                <input type="hidden" name="id" value="<?php echo $content['id']; ?>">
                                <button type="submit" name="delete" class="btn btn-delete">
                                    <i class="fas fa-trash-alt"></i>
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>