<?php
session_start();
require_once '../database/db_config.php';

if (!isset($_GET['id'])) {
    die("Test ID is missing.");
}

$test_id = $_GET['id'];
$student_id = $_SESSION['student_id'] ?? null;

try {
    $stmt = $conn->prepare("SELECT st.*, sc.category_name 
                            FROM steno_tests st 
                            LEFT JOIN steno_categories sc ON st.category_id = sc.id 
                            WHERE st.id = :id");
    $stmt->execute(['id' => $test_id]);
    $test = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$test) {
        die("Test not found.");
    }
} catch (Exception $e) {
    die("Error fetching test: " . $e->getMessage());
}

$duration_sec = $test['duration_minutes'] * 60;
$audio_url = '../' . $test['audio_file'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($test['test_title']); ?> - Steno Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body, html {
            height: 100%;
            overflow: hidden; /* Prevent body scroll */
            background-color: #f8f9fa;
        }

        /* Top Header */
        .test-header {
            height: 80px;
            background: #fff;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            z-index: 1000;
            position: relative;
        }

        /* Audio Player Styling */
        .audio-wrapper {
            flex: 1;
            max-width: 500px;
            margin: 0 2rem;
            background: #f1f3f5;
            padding: 5px 15px;
            border-radius: 50px;
            display: flex;
            align-items: center;
        }
        audio {
            width: 100%;
            height: 35px;
            outline: none;
        }
        /* Customizing audio player is hard across browsers, usually sticking to default or building custom JS player.
           For "Played vs Pending", default shows visually. 
           User asked to "play separately". 
           We'll keep default controls for reliability but style container.
        */

        /* Typing Area */
        .typing-container {
            height: calc(100vh - 80px);
            position: relative;
            background: #fff;
        }
        
        #typing-area {
            width: 100%;
            height: 100%;
            border: none;
            outline: none;
            resize: none;
            padding: 3rem; /* Generous padding */
            font-family: 'Courier New', Courier, monospace;
            font-size: 1.25rem;
            line-height: 1.8;
            color: #333;
            background: transparent; /* Clean look */
        }
        
        /* Placeholder styling */
        #typing-area::placeholder {
            color: #ccc;
            opacity: 0.5;
        }

        /* Start Overlay */
        #start-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.95);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }

        .timer-badge {
            font-size: 1.5rem;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            min-width: 100px;
            text-align: center;
        }

        /* Custom Scrollbar for textarea if needed */
        textarea::-webkit-scrollbar {
            width: 8px;
        }
        textarea::-webkit-scrollbar-track {
            background: #f1f1f1; 
        }
        textarea::-webkit-scrollbar-thumb {
            background: #ccc; 
            border-radius: 4px;
        }
        textarea::-webkit-scrollbar-thumb:hover {
            background: #bbb; 
        }

        .no-select {
            user-select: none;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <header class="test-header">
        <div class="d-flex align-items-center">
            <img src="../assets/images/paryag-computer-logo.jpeg" alt="Logo" height="40" class="me-3 rounded">
            <div>
                <h5 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($test['test_title']); ?></h5>
                <small class="text-muted text-uppercase" style="font-size: 0.7rem;">Steno Test</small>
            </div>
        </div>

        <!-- Audio Player Center -->
        <div class="audio-wrapper">
             <i class="fa-solid fa-music text-muted me-3"></i>
             <audio id="audio-player" controls controlsList="nodownload">
                <source src="<?php echo htmlspecialchars($audio_url); ?>" type="audio/mpeg">
                Your browser does not support audio.
            </audio>
        </div>

        <!-- Right Stats & Actions -->
        <div class="d-flex align-items-center gap-4">
             <div class="text-center">
                <small class="d-block text-muted fw-bold" style="font-size: 0.65rem;">WPM</small>
                <span id="wpm-display" class="h5 fw-bold mb-0 text-primary">0</span>
             </div>
             
             <div class="text-center">
                <div class="timer-badge text-dark p-2 bg-light rounded" id="timer">
                    <?php echo sprintf("%02d:00", $test['duration_minutes']); ?>
                </div>
             </div>

             <button type="button" id="submit-btn" class="btn btn-success rounded-pill fw-bold px-4" disabled>
                Submit <i class="fa-solid fa-paper-plane ms-2"></i>
             </button>
        </div>
    </header>

    <!-- Main Content -->
    <div class="typing-container">
        
        <!-- Start Overlay -->
        <div id="start-overlay">
            <h1 class="fw-bold mb-3 display-5">Ready to Start?</h1>
            <p class="text-muted mb-4 lead">Duration: <?php echo $test['duration_minutes']; ?> Minutes • Audio Dictation Included</p>
            
            <button id="start-btn" class="btn btn-primary btn-lg rounded-pill px-5 py-3 shadow-lg fs-4 fw-bold transition-all">
                <i class="fa-solid fa-play me-2"></i> Start Test
            </button>
            <p class="mt-4 text-muted small"><i class="fa-solid fa-circle-info me-1"></i> Audio will play automatically. Type what you hear.</p>
        </div>

        <form id="steno-test-form" action="submit-steno-test.php" method="POST" class="h-100">
            <input type="hidden" name="test_id" value="<?php echo $test['id']; ?>">
            <input type="hidden" name="duration_taken" id="duration_taken" value="0">
            
            <textarea name="typed_content" id="typing-area" 
                placeholder="Type here..." spellcheck="false" autocomplete="off"></textarea>
        </form>
    </div>

<script>
    const durationMinutes = <?php echo $test['duration_minutes']; ?>;
    let timeLeft = durationMinutes * 60;
    let timerInterval;
    let isTestActive = false;
    let startTime;
    
    const startBtn = document.getElementById('start-btn');
    const submitBtn = document.getElementById('submit-btn');
    const startOverlay = document.getElementById('start-overlay');
    const typingArea = document.getElementById('typing-area');
    const audioPlayer = document.getElementById('audio-player');
    const timerDisplay = document.getElementById('timer');
    const wpmDisplay = document.getElementById('wpm-display');
    const durationInput = document.getElementById('duration_taken');
    const form = document.getElementById('steno-test-form');

    // Prevent Copy/Paste
    typingArea.addEventListener('paste', e => e.preventDefault());
    typingArea.addEventListener('copy', e => e.preventDefault());
    typingArea.addEventListener('cut', e => e.preventDefault());
    typingArea.addEventListener('contextmenu', e => e.preventDefault());

    // Initially disable area
    typingArea.disabled = true;

    startBtn.addEventListener('click', startTest);
    
    submitBtn.addEventListener('click', function() {
        if(confirm('Are you sure you want to submit the test?')) {
            submitTest();
        }
    });

    function startTest() {
        // Hide Overlay
        startOverlay.style.display = 'none';
        
        isTestActive = true;
        startTime = new Date();
        
        submitBtn.disabled = false;
        typingArea.disabled = false;
        typingArea.focus();
        
        // Audio
        audioPlayer.play().catch(e => console.log("Audio autoplay blocked - User needs to interact first", e));

        // Timer
        timerInterval = setInterval(updateTimer, 1000);
    }

    function updateTimer() {
        if (timeLeft <= 0) {
            submitTest();
            return;
        }

        timeLeft--;
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        timerDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

        if (timeLeft < 60) {
            timerDisplay.classList.remove('bg-light');
            timerDisplay.classList.add('bg-danger', 'text-white');
            timerDisplay.classList.remove('text-dark');
        }
    }

    function submitTest() {
        clearInterval(timerInterval);
        isTestActive = false;
        audioPlayer.pause();
        
        const endTime = new Date();
        const takenSeconds = Math.round((endTime - startTime) / 1000);
        durationInput.value = takenSeconds;

        form.submit();
    }

    // WPM Calc
    typingArea.addEventListener('input', function() {
        const text = this.value.trim();
        const words = text ? text.split(/\s+/).length : 0;
        
        const elapsedMin = (new Date() - startTime) / 60000;
        if (elapsedMin > 0) {
            const wpm = Math.round(words / elapsedMin);
            wpmDisplay.textContent = wpm;
        }
    });

    // Warn on leave
    window.onbeforeunload = function() {
        if (isTestActive) {
            return "Test is in progress.";
        }
    };
    
    form.addEventListener('submit', () => {
        window.onbeforeunload = null;
    });
</script>
</body>
</html>
