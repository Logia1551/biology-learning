<?php
ini_set('max_execution_time', 0);
set_time_limit(0);
ini_set('memory_limit', '256M');

require_once '../vendor/autoload.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
   die('Akses ditolak');
} 

class BiologyScraper {
   private $urls = [
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

   private $pdo;
   private $logs = [];

   public function __construct($pdo) {
       $this->pdo = $pdo;
   }

   public function getAvailableUrls() {
       return $this->urls;
   }

   public function startScraping($selectedUrl = null) {
       $this->logProcess("Memulai proses scraping...");
       
       if ($selectedUrl) {
           if (isset($this->urls[$selectedUrl])) {
               $this->logProcess("Target: " . $selectedUrl);
               return $this->scrapeSingleLesson($this->urls[$selectedUrl], $selectedUrl);
           }
           return false;
       }

       $results = [];
       $total = count($this->urls);
       $current = 0;

       foreach ($this->urls as $key => $url) {
           $current++;
           $this->logProcess("Processing {$current}/{$total}: {$key}");
           
           $lessonData = $this->scrapeSingleLesson($url, $key);
           if ($lessonData) {
               $results[$key] = $lessonData;
               $this->logSuccess("Berhasil scrape konten: {$key}");
           }
           sleep(2);
       }

       return $results;
   }

   private function scrapeSingleLesson($url, $key) {
       try {
           $this->logProcess("Mengakses URL: {$url}");
           
           $ch = curl_init();
           curl_setopt_array($ch, [
               CURLOPT_URL => $url,
               CURLOPT_RETURNTRANSFER => true,
               CURLOPT_FOLLOWLOCATION => true,
               CURLOPT_SSL_VERIFYPEER => false,
               CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/91.0.4472.124',
               CURLOPT_TIMEOUT => 30
           ]);

           $html = curl_exec($ch);
           
           if (curl_errno($ch)) {
               throw new Exception('Curl error: ' . curl_error($ch));
           }
           
           curl_close($ch);

           $dom = new DOMDocument();
           @$dom->loadHTML($html, LIBXML_NOERROR);
           $xpath = new DOMXPath($dom);

           // Ambil deskripsi dari paragraf pertama yang relevan
           $description = $this->getDescription($xpath);
           
           $data = [
               'title' => $this->getTitle($xpath),
               'description' => $description,
               'video_url' => $this->getVideo($xpath),
               'original_url' => $url
           ];

           $this->logProcess("Menyimpan ke database...");
           if ($this->saveToDatabase($data)) {
               $this->logSuccess("Konten berhasil disimpan ke database");
           }

           return $data;

       } catch (Exception $e) {
           $this->logError("Error pada {$key}: " . $e->getMessage());
           return null;
       }
   }

   private function getDescription($xpath) {
       // Cari paragraf yang mengandung konten substantif
       $paragraphs = $xpath->query("//div[contains(@class, 'entry-content')]//p");
       $description = '';
       
       foreach ($paragraphs as $p) {
           $text = trim($p->textContent);
           // Skip paragraf kosong atau terlalu pendek
           if (strlen($text) > 50 && !strpos(strtolower($text), 'copyright')) {
               $description = $text;
               break;
           }
       }
       
       // Jika tidak menemukan paragraf yang sesuai, ambil paragraf pertama saja
       if (empty($description) && $paragraphs->length > 0) {
           $description = trim($paragraphs->item(0)->textContent);
       }

       return $description;
   }

   private function getTitle($xpath) {
       $node = $xpath->query("//h1[contains(@class, 'entry-title')]")->item(0);
       return $node ? trim($node->textContent) : '';
   }

   private function getVideo($xpath) {
       $node = $xpath->query("//iframe[contains(@src, 'youtube.com')]")->item(0);
       return $node ? $node->getAttribute('src') : '';
   }

   private function saveToDatabase($data) {
       try {
           $stmt = $this->pdo->prepare("
               INSERT INTO biology_content 
               (title, description, video_url, topic, original_url) 
               VALUES (?, ?, ?, 'Cell Biology', ?)
           ");
           
           $stmt->execute([
               $data['title'],
               $data['description'],
               $data['video_url'],
               $data['original_url']
           ]);
           
           return true;
       } catch (PDOException $e) {
           $this->logError("Database error: " . $e->getMessage());
           return false;
       }
   }

   private function logProcess($message) {
       $this->logs[] = [
           'type' => 'process',
           'time' => date('H:i:s'),
           'message' => $message
       ];
   }

   private function logSuccess($message) {
       $this->logs[] = [
           'type' => 'success',
           'time' => date('H:i:s'),
           'message' => $message
       ];
   }

   private function logError($message) {
       $this->logs[] = [
           'type' => 'error',
           'time' => date('H:i:s'),
           'message' => $message
       ];
   }

   public function getLogs() {
       return $this->logs;
   }
}

// Bagian HTML
?>
<!DOCTYPE html>
<html>
<head>
   <title>Biology Content Scraper</title>
   <style>
       body {
           font-family: Arial, sans-serif;
           margin: 0;
           padding: 20px;
           background: #f5f6fa;
       }
       .container {
           max-width: 1200px;
           margin: 0 auto;
           display: grid;
           grid-template-columns: 300px 1fr;
           gap: 20px;
       }
       .sidebar {
           background: white;
           padding: 20px;
           border-radius: 8px;
           box-shadow: 0 2px 4px rgba(0,0,0,0.1);
       }
       .main-content {
           background: white;
           padding: 20px;
           border-radius: 8px;
           box-shadow: 0 2px 4px rgba(0,0,0,0.1);
       }
       .url-list {
           list-style: none;
           padding: 0;
       }
       .url-list li {
           margin: 10px 0;
       }
       .url-list a {
           display: block;
           padding: 10px;
           background: #f8f9fa;
           border-radius: 4px;
           text-decoration: none;
           color: #2a5298;
       }
       .url-list a:hover {
           background: #e9ecef;
       }
       .log {
           padding: 8px;
           margin: 5px 0;
           border-radius: 4px;
           font-family: monospace;
       }
       .log.process { background: #e3f2fd; }
       .log.success { background: #e8f5e9; color: #1b5e20; }
       .log.error { background: #ffebee; color: #b71c1c; }
       .timestamp {
           color: #666;
           font-size: 0.9em;
           margin-right: 10px;
       }
       .back-btn {
           display: inline-block;
           padding: 10px 20px;
           background: #2a5298;
           color: white;
           text-decoration: none;
           border-radius: 5px;
           margin-top: 20px;
       }
   </style>
</head>
<body>
   <div class="container">
       <div class="sidebar">
           <h3>Lesson List</h3>
           <div class="progress-info">
               Pilih lesson untuk scraping spesifik atau biarkan untuk scraping semua konten.
           </div>
           <ul class="url-list">
               <?php 
               $scraper = new BiologyScraper($pdo);
               $urls = $scraper->getAvailableUrls();
               foreach (array_keys($urls) as $key): 
               ?>
                   <li>
                       <a href="?scrape=<?php echo $key; ?>">
                           <?php echo ucwords(str_replace('-', ' ', $key)); ?>
                       </a>
                   </li>
               <?php endforeach; ?>
           </ul>
           <a href="../teacher/dashboard.php" class="back-btn">Kembali ke Dashboard</a>
       </div>

       <div class="main-content">
           <h2>Proses Scraping</h2>
           
           <?php
           $startTime = microtime(true);
           
           if (isset($_GET['scrape'])) {
               $result = $scraper->startScraping($_GET['scrape']);
           } else {
               $result = $scraper->startScraping();
           }

           $endTime = microtime(true);
           $executionTime = round($endTime - $startTime, 2);
           
           echo "<div class='progress-info'>";
           echo "Waktu eksekusi: " . $executionTime . " detik";
           echo "</div>";

           foreach ($scraper->getLogs() as $log) {
               echo "<div class='log {$log['type']}'>";
               echo "<span class='timestamp'>{$log['time']}</span>";
               echo htmlspecialchars($log['message']);
               echo "</div>";
           }
           ?>
       </div>
   </div>
</body>
</html>