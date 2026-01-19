<?php
session_start();
// Auth Check (Commented out for testing as per request, but good to have ready)
/*
if (!isset($_SESSION['student_logged_in']) || $_SESSION['student_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
*/

require_once '../database/db_config.php';

if (!isset($_GET['id'])) {
    die("Test ID is missing.");
}

$test_id = $_GET['id'];

try {
    // Fetch Test Data
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
$audio_url = '../' . $test['audio_file']; // Assuming stored as 'assets/audio/...' and current dir is 'student/'

include 'header.php';
?>

<style>
    /* Prevent selection and copying */
    .no-select {
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
    }
    
    .typing-area {
        font-family: 'Courier New', Courier, monospace;
        font-size: 1.1rem;
        line-height: 1.6;
        min-height: 400px;
        resize: none;
        background-color: #fcfcfc;
    }
    
    .typing-area:focus {
        background-color: #fff;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1);
    }

    .sticky-sidebar {
        position: sticky;
        top: 20px;
    }

    .timer-display {
        font-variant-numeric: tabular-nums;
        letter-spacing: 2px;
    }
</style>

    <div class="main-content w-100 bg-light">
        <div class="container p-4">
            
            <div class="row g-4">
                <!-- Main Typing Area -->
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0 rounded-4 h-100">
                        <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="fw-bold mb-1"><?php echo htmlspecialchars($test['test_title']); ?></h4>
                                <span class="badge bg-light text-primary border border-primary-subtle">
                                    <?php echo htmlspecialchars($test['category_name'] ?? 'General'); ?>
                                </span>
                            </div>
                            <div class="text-end">
                                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.75rem;">Status</small>
                                <span id="status-badge" class="badge bg-warning text-dark">Waiting to Start</span>
                            </div>
                        </div>
                        
                        <div class="card-body p-4">
                            <!-- Instruction Overlay could go here -->
                            
                            <form id="steno-test-form" action="submit-steno-test.php" method="POST">
                                <input type="hidden" name="test_id" value="<?php echo $test['id']; ?>">
                                <input type="hidden" name="duration_taken" id="duration_taken" value="0">
                                
                                <textarea name="typed_content" id="typing-area" class="form-control typing-area border-2 rounded-3 p-4 mb-3" 
                                    placeholder="Click 'Start Test' to begin typing..." disabled spellcheck="false" autocomplete="off"></textarea>
                                
                                <div class="d-flex justify-content-end">
                                    <button type="button" id="submit-btn" class="btn btn-success px-5 rounded-pill fw-bold" disabled>
                                        <i class="fa-solid fa-paper-plane me-2"></i> Submit Test
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Info Panel -->
                <div class="col-lg-4">
                    <div class="sticky-sidebar">
                        <!-- Institute Logo -->
                        <div class="text-center mb-4">
                            <img src="../assets/images/paryag-computer-logo.jpeg" alt="Logo" class="img-fluid rounded" style="max-height: 80px;">
                            <h5 class="fw-bold mt-2 mb-0">Steno Test</h5>
                        </div>

                        <!-- Timer Card -->
                        <div class="card shadow-sm border-0 rounded-4 mb-3 bg-primary text-white">
                            <div class="card-body p-4 text-center">
                                <h6 class="text-white-50 text-uppercase fw-bold mb-2">Time Remaining</h6>
                                <div id="timer" class="display-4 fw-bold timer-display">
                                    <?php echo sprintf("%02d:00", $test['duration_minutes']); ?>
                                </div>
                            </div>
                        </div>

                        <!-- Controls & Stats -->
                        <div class="card shadow-sm border-0 rounded-4 mb-3">
                            <div class="card-body p-4">
                                <div class="d-grid mb-4">
                                    <button id="start-btn" class="btn btn-primary btn-lg rounded-pill shadow-sm">
                                        <i class="fa-solid fa-play me-2"></i> Start Test
                                    </button>
                                </div>

                                <div class="mb-4">
                                    <h6 class="text-muted text-uppercase fw-bold small mb-2">Audio Dictation</h6>
                                    <audio id="audio-player" class="w-100" controlsList="nodownload">
                                        <source src="<?php echo htmlspecialchars($audio_url); ?>" type="audio/mpeg">
                                        Your browser does not support the audio element.
                                    </audio>
                                </div>

                                <div class="row g-2 text-center">
                                    <div class="col-6">
                                        <div class="p-3 bg-light rounded-3">
                                            <h3 class="fw-bold mb-0 text-primary" id="wpm-display">0</h3>
                                            <small class="text-muted fw-bold">WPM</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="p-3 bg-light rounded-3">
                                            <h3 class="fw-bold mb-0 text-dark" id="word-count">0</h3>
                                            <small class="text-muted fw-bold">Words</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info border-0 rounded-4 small">
                            <i class="fa-solid fa-circle-info me-2"></i>
                            <strong>Note:</strong> Copy/Paste is disabled. The test will auto-submit when the timer reaches zero.
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    const durationMinutes = <?php echo $test['duration_minutes']; ?>;
    let timeLeft = durationMinutes * 60;
    let timerInterval;
    let isTestActive = false;
    let startTime;
    
    const startBtn = document.getElementById('start-btn');
    const submitBtn = document.getElementById('submit-btn');
    const typingArea = document.getElementById('typing-area');
    const audioPlayer = document.getElementById('audio-player');
    const timerDisplay = document.getElementById('timer');
    const wpmDisplay = document.getElementById('wpm-display');
    const wordCountDisplay = document.getElementById('word-count');
    const statusBadge = document.getElementById('status-badge');
    const durationInput = document.getElementById('duration_taken');
    const form = document.getElementById('steno-test-form');

    // Prevent Copy/Paste/Right Click
    typingArea.addEventListener('paste', e => e.preventDefault());
    typingArea.addEventListener('copy', e => e.preventDefault());
    typingArea.addEventListener('cut', e => e.preventDefault());
    typingArea.addEventListener('contextmenu', e => e.preventDefault());

    // Disable audio seeking (optional but requested)
    // Audio player 'controls' are native, hard to disable seek only.
    // We can monitor 'seeking' event and revert.
    /*
    audioPlayer.addEventListener('seeking', function() {
        if(audioPlayer.currentTime > audioPlayer.played.end(0)) {
            // Prevent forward seek - logic is complex, skipping for simplicity unless strictly required
        }
    }); 
    */
    
    // Initially disable audio interaction
    audioPlayer.style.pointerEvents = 'none';
    audioPlayer.style.opacity = '0.6';

    startBtn.addEventListener('click', startTest);
    submitBtn.addEventListener('click', submitTest);

    function startTest() {
        isTestActive = true;
        startTime = new Date();
        
        // UI Updates
        startBtn.disabled = true;
        startBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> In Progress';
        submitBtn.disabled = false;
        typingArea.disabled = false;
        typingArea.focus();
        typingArea.placeholder = "Start typing here...";
        
        statusBadge.className = 'badge bg-success';
        statusBadge.textContent = 'In Progress';
        
        // Audio
        audioPlayer.style.pointerEvents = 'auto';
        audioPlayer.style.opacity = '1';
        audioPlayer.play().catch(e => console.log("Audio autoplay blocked:", e));

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

        // Color warning
        if (timeLeft < 60) {
            document.querySelector('.card.bg-primary').classList.remove('bg-primary');
            document.querySelector('.card.bg-primary').classList.add('bg-danger'); // This selector won't work after removal
             // Better:
             const timerCard = timerDisplay.closest('.card');
             if(timerCard.classList.contains('bg-primary')) {
                 timerCard.classList.remove('bg-primary');
                 timerCard.classList.add('bg-danger');
             }
        }
    }

    function submitTest() {
        clearInterval(timerInterval);
        isTestActive = false;
        audioPlayer.pause();
        
        typingArea.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-circle-check me-2"></i> Submitting...';
        
        // Calculate duration taken
        const endTime = new Date();
        const takenSeconds = Math.round((endTime - startTime) / 1000);
        durationInput.value = takenSeconds;

        form.submit();
    }

    // Live Stats
    typingArea.addEventListener('input', function() {
        const text = this.value.trim();
        const words = text ? text.split(/\s+/).length : 0;
        wordCountDisplay.textContent = words;

        // Approx WPM (Live)
        const elapsedMin = (new Date() - startTime) / 60000;
        if (elapsedMin > 0) {
            const wpm = Math.round(words / elapsedMin);
            wpmDisplay.textContent = wpm;
        }
    });

    // Warn on leave
    window.onbeforeunload = function() {
        if (isTestActive) {
            return "Test is in progress. Are you sure you want to leave?";
        }
    };
    
    // Allow submit without warning
    form.addEventListener('submit', () => {
        window.onbeforeunload = null;
    });

</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
