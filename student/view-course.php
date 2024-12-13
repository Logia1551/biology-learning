<?php
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
   header('Location: ../index.php');
   exit;
}

if (!isset($_GET['id'])) {
   header('Location: dashboard.php');
   exit;
}

$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$_GET['id']]);
$course = $stmt->fetch();

if (!$course) {
   header('Location: dashboard.php');
   exit;
}

function getYoutubeId($url) {
   $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i';
   preg_match($pattern, $url, $matches);
   return isset($matches[1]) ? $matches[1] : '';
}
?>

<!DOCTYPE html>
<html>
<head>
   <title>View Course - Semantic Learning</title>
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
           max-width: 1000px;
           margin: 0 auto;
       }

       .navbar {
           background: white;
           padding: 15px 30px;
           box-shadow: 0 2px 10px rgba(0,0,0,0.1);
           position: fixed;
           top: 0;
           left: 0;
           right: 0;
           z-index: 1000;
       }

       .nav-content {
           max-width: 1000px;
           margin: 0 auto;
           display: flex;
           justify-content: space-between;
           align-items: center;
       }

       .course-title {
           color: #2a5298;
           font-size: 24px;
           font-weight: bold;
       }

       .back-btn {
           padding: 8px 20px;
           background: #f1f1f1;
           color: #333;
           text-decoration: none;
           border-radius: 5px;
           transition: all 0.3s ease;
       }

       .content-card {
           background: white;
           padding: 30px;
           border-radius: 15px;
           box-shadow: 0 2px 10px rgba(0,0,0,0.1);
           margin-bottom: 20px;
       }

       .video-container {
           position: relative;
           width: 100%;
           padding-bottom: 56.25%;
           background: #000;
           border-radius: 10px;
           overflow: hidden;
           margin-bottom: 20px;
       }

       .video-container iframe {
           position: absolute;
           top: 0;
           left: 0;
           width: 100%;
           height: 100%;
           border: none;
       }

       .progress-container {
           background: #f8f9fa;
           padding: 20px;
           border-radius: 10px;
           margin-bottom: 20px;
       }

       .progress-title {
           color: #2c3e50;
           margin-bottom: 10px;
           font-size: 16px;
           font-weight: 500;
       }

       .progress-bar {
           width: 100%;
           height: 10px;
           background: #e9ecef;
           border-radius: 5px;
           overflow: hidden;
           margin-bottom: 10px;
       }

       .progress {
           width: 0%;
           height: 100%;
           background: #2a5298;
           transition: width 0.3s ease;
       }

       /* New styles for course description */
       .course-description {
           background: #f8f9fa;
           padding: 20px;
           border-radius: 10px;
           margin-bottom: 20px;
       }

       .description-title {
           color: #2c3e50;
           margin-bottom: 10px;
           font-size: 16px;
           font-weight: 500;
       }

       .description-content {
           color: #4a5568;
           line-height: 1.6;
           font-size: 15px;
       }

       .course-metadata {
           margin-top: 15px;
           padding-top: 15px;
           border-top: 1px solid #e9ecef;
       }

       .metadata-item {
           margin-bottom: 8px;
           color: #4a5568;
           font-size: 14px;
       }

       .metadata-label {
           font-weight: 500;
           color: #2c3e50;
           margin-right: 5px;
       }

       .quiz-button {
           display: block;
           width: 100%;
           padding: 15px;
           background: #2a5298;
           color: white;
           border: none;
           border-radius: 8px;
           font-size: 16px;
           cursor: pointer;
           transition: all 0.3s ease;
           text-align: center;
           text-decoration: none;
       }

       .quiz-button:disabled {
           background: #ccc;
           cursor: not-allowed;
       }

       .quiz-button:not(:disabled):hover {
           background: #1e3c72;
       }

       @media (max-width: 768px) {
           .container {
               padding: 10px;
           }
           
           .content-card {
               padding: 20px;
           }
       }
   </style>
</head>
<body>
   <nav class="navbar">
       <div class="nav-content">
           <h1 class="course-title">Pertemuan <?php echo $course['meeting_number']; ?> - <?php echo htmlspecialchars($course['title']); ?></h1>
           <a href="dashboard.php" class="back-btn">Kembali</a>
       </div>
   </nav>

   <div class="container">
       <div class="content-card">
           <div class="video-container">
               <iframe
                   id="player"
                   src="https://www.youtube.com/embed/<?php echo getYoutubeId($course['video_url']); ?>?enablejsapi=1&autoplay=1&controls=0"
                   allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
                   allowfullscreen>
               </iframe>
           </div>

           <div class="progress-container">
               <h3 class="progress-title">Progress Video</h3>
               <div class="progress-bar">
                   <div class="progress" id="progress"></div>
               </div>
           </div>

           <!-- Added course description section -->
           <div class="course-description">
               <h3 class="description-title">Deskripsi Materi</h3>
               <div class="description-content">
                   <?php echo nl2br(htmlspecialchars($course['description'] ?? 'Deskripsi tidak tersedia')); ?>
               </div>

               <div class="course-metadata">
                   <?php if(!empty($course['duration'])): ?>
                   <div class="metadata-item">
                       <span class="metadata-label">Durasi:</span>
                       <?php echo htmlspecialchars($course['duration']); ?>
                   </div>
                   <?php endif; ?>

                   <?php if(!empty($course['author'])): ?>
                   <div class="metadata-item">
                       <span class="metadata-label">Pengajar:</span>
                       <?php echo htmlspecialchars($course['author']); ?>
                   </div>
                   <?php endif; ?>

                   <?php if(!empty($course['grade'])): ?>
                   <div class="metadata-item">
                       <span class="metadata-label">Kelas:</span>
                       <?php echo htmlspecialchars($course['grade']); ?>
                   </div>
                   <?php endif; ?>

                   <?php if(!empty($course['topic'])): ?>
                   <div class="metadata-item">
                       <span class="metadata-label">Topik:</span>
                       <?php echo htmlspecialchars($course['topic']); ?>
                   </div>
                   <?php endif; ?>

                   <?php if(!empty($course['subtopic'])): ?>
                   <div class="metadata-item">
                       <span class="metadata-label">Subtopik:</span>
                       <?php echo htmlspecialchars($course['subtopic']); ?>
                   </div>
                   <?php endif; ?>

                   <?php if(!empty($course['competency'])): ?>
                   <div class="metadata-item">
                       <span class="metadata-label">Kompetensi:</span>
                       <?php echo nl2br(htmlspecialchars($course['competency'])); ?>
                   </div>
                   <?php endif; ?>
               </div>
           </div>

           <button id="startQuizBtn" class="quiz-button" disabled>
               Selesaikan video untuk memulai quiz
           </button>
       </div>
   </div>
   
<script>
var player;
var videoCompleted = false;
var progressInterval;
var retryCount = 0;
const maxRetries = 3;

// Load YouTube API script
var tag = document.createElement('script');
tag.src = "https://www.youtube.com/iframe_api";
var firstScriptTag = document.getElementsByTagName('script')[0];
firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

// Called automatically by YouTube API when ready
function onYouTubeIframeAPIReady() {
    player = new YT.Player('player', {
        videoId: '<?php echo getYoutubeId($course['video_url']); ?>',
        playerVars: {
            'autoplay': 1,
            'controls': 0,
            'disablekb': 1,
            'enablejsapi': 1,
            'modestbranding': 1,
            'rel': 0,
            'showinfo': 0,
            'fs': 0,
            'playsinline': 1
        },
        events: {
            'onReady': onPlayerReady,
            'onStateChange': onPlayerStateChange,
            'onError': onPlayerError
        }
    });
}

function onPlayerReady(event) {
    console.log('Player ready');
    event.target.playVideo();
    
    // Unmute dan atur volume
    setTimeout(function() {
        player.unMute();
        player.setVolume(100);
        startProgressTracking();
    }, 1000);
}

function startProgressTracking() {
    if (progressInterval) {
        clearInterval(progressInterval);
    }
    
    progressInterval = setInterval(updateProgress, 1000);
}

function updateProgress() {
    if (!player || typeof player.getCurrentTime !== 'function') {
        console.log('Player not ready');
        return;
    }

    try {
        var currentTime = player.getCurrentTime();
        var duration = player.getDuration();
        console.log('Current Time:', currentTime, 'Duration:', duration);

        if (currentTime && duration) {
            var progress = (currentTime / duration) * 100;
            document.getElementById('progress').style.width = progress + '%';
            
            if (progress >= 98) {
                enableQuizButton();
                clearInterval(progressInterval);
            }
        }
    } catch (error) {
        console.error('Error updating progress:', error);
    }
}

function onPlayerStateChange(event) {
    console.log('Player State:', event.data);
    
    switch(event.data) {
        case YT.PlayerState.ENDED:
            console.log('Video ended');
            enableQuizButton();
            clearInterval(progressInterval);
            document.getElementById('progress').style.width = '100%';
            break;
            
        case YT.PlayerState.PLAYING:
            console.log('Video playing');
            if (!progressInterval) {
                startProgressTracking();
            }
            break;
            
        case YT.PlayerState.PAUSED:
            console.log('Video paused - resuming');
            player.playVideo();
            break;
            
        case YT.PlayerState.BUFFERING:
            console.log('Video buffering');
            break;
    }
}

function onPlayerError(event) {
    console.error('Player error:', event.data);
    if (retryCount < maxRetries) {
        console.log('Retrying...');
        retryCount++;
        setTimeout(() => {
            player.loadVideoById('<?php echo getYoutubeId($course['video_url']); ?>');
        }, 1000);
    } else {
        alert('Terjadi masalah saat memuat video. Silakan refresh halaman.');
    }
}

function enableQuizButton() {
    if (!videoCompleted) {
        videoCompleted = true;
        var startQuizBtn = document.getElementById('startQuizBtn');
        startQuizBtn.disabled = false;
        startQuizBtn.textContent = 'Mulai Quiz';
        startQuizBtn.onclick = function() {
            window.location.href = 'take-quiz.php?course_id=<?php echo $course['id']; ?>';
        };
    }
}

// Cleanup when page is unloaded
window.onbeforeunload = function() {
    if (progressInterval) {
        clearInterval(progressInterval);
    }
};
</script>
</body>
</html>