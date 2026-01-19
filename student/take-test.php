<?php
session_start();
// if (!isset($_SESSION['student_logged_in'])) { header("Location: login.php"); exit; }

require_once '../database/db_config.php';

if (!isset($_GET['id'])) {
    header("Location: typing-lessons.php"); // Redirect if no ID
    exit;
}

$test_id = $_GET['id'];

// Fetch Test Data
try {
    $stmt = $conn->prepare("SELECT * FROM typing_tests WHERE id = :id");
    $stmt->execute(['id' => $test_id]);
    $test = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$test) {
        die("Test not found.");
    }

    // SANITIZE CONTENT: CKEditor saves HTML, we need plain text for the typing engine.
    // Convert entities (e.g. &nbsp;) to real spaces/chars and strip tags.
    $raw_content = html_entity_decode($test['test_content']);
    $clean_content = strip_tags($raw_content);
    // Normalize newlines to spaces or consistent breaks if needed, 
    // but for typing tests, preserving explicit newlines is usually better 
    // if the UI handles them. For this engine, we'll normalize multiple spaces 
    // to single spaces to avoid confusion, unless strict formatting is desired.
    // Let's keep it relatively raw but clean up excessive whitespace that might be invisible.
    $clean_content = preg_replace('/\s+/', ' ', trim($clean_content)); 

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Test - <?php echo htmlspecialchars($test['test_title']); ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts: Inter & Roboto Mono -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Inter', sans-serif;
            overflow: hidden; /* Prevent full page scrolling */
            user-select: none; /* Disable text selection */
        }
        
        /* Layout */
        .test-container {
            height: 100vh;
            display: flex;
        }
        
        /* Left: Typing Area */
        .typing-section {
            flex: 7;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow-y: hidden;
            background-color: #ffffff;
            position: relative;
        }

        .typing-area-wrapper {
            position: relative;
            max-width: 900px;
            margin: 0 auto;
            width: 100%;
            height: 45vh; /* Reduced to make room for typed content */
            overflow: hidden; /* Hide overflow, we'll scroll programmatically */
            border-radius: 12px 12px 0 0; /* Top rounded corners only */
            background: #fafafa;
            border: 1px solid #e0e0e0;
            border-bottom: none; /* Connect with typed content area */
            padding: 30px;
            box-shadow: inset 0 2px 10px rgba(0,0,0,0.02);
            cursor: text;
            transition: all 0.3s ease;
        }
        
        /* New Typed Content Area */
        .typed-content-wrapper {
            position: relative;
            max-width: 900px;
            margin: 0 auto 20px; /* specific bottom margin */
            width: 100%;
            min-height: 15vh; /* Visible area for output */
            border-radius: 0 0 12px 12px; /* Bottom rounded corners */
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-top: 1px dashed #ced4da; /* Distinct separator */
            padding: 20px 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.01);
            display: flex;
            flex-direction: column;
        }
        
        .typed-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #adb5bd;
            margin-bottom: 8px;
            font-weight: 600;
        }

        #user-typed-output {
            font-family: 'Roboto Mono', monospace;
            font-size: 1.25rem;
            line-height: 1.8;
            color: #212529;
            white-space: pre-wrap;
            word-break: break-word;
            flex-grow: 1;
            max-height: 5.4em; /* 1.8 line-height * 3 lines = 5.4em */
            overflow-y: hidden; /* Hide scrollbar */
            scroll-behavior: smooth;
        }
        
        .cursor-blink {
            display: inline-block;
            width: 2px;
            height: 1.2em;
            background-color: #0d6efd;
            vertical-align: middle;
            animation: blink 1s step-end infinite;
            margin-left: 2px;
        }
        
        @keyframes blink {
            50% { opacity: 0; }
        }

        /* The actual text container */
        #text-display {
            font-family: 'Roboto Mono', monospace;
            font-size: 1.5rem;
            line-height: 2.2;
            color: #999;
            white-space: pre-wrap;
            word-break: break-word;
        }

        /* Character States */
        .char {
            position: relative;
            transition: color 0.1s;
        }
        .char.correct {
            color: #198754; /* Bootstrap Success Green */
        }
        .char.incorrect {
            color: #dc3545; /* Bootstrap Danger Red */
            background-color: rgba(220, 53, 69, 0.1);
            border-radius: 2px;
        }
        .char.active {
            color: #212529;
            background-color: #e9ecef;
            border-bottom: 2px solid #0d6efd;
            border-radius: 3px;
        }
        
        /* Hidden Input */
        #input-field {
            position: absolute;
            opacity: 0;
            top: 0;
            left: 0;
            pointer-events: none; /* Keep focus but don't interfere visually */
        }

        /* Right: Sidebar */
        .stats-sidebar {
            flex: 3;
            background-color: #ffffff;
            border-left: 1px solid #e0e0e0;
            padding: 30px; /* Reduced from 40px */
            display: flex;
            flex-direction: column;
            box-shadow: -5px 0 20px rgba(0,0,0,0.02);
            z-index: 10;
            overflow-y: auto; /* Allow scroll if screen is very short */
        }

        .stat-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px; /* Reduced from 20px */
            margin-bottom: 12px; /* Reduced from 20px */
            text-align: center;
            border: 1px solid #eee;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .stat-value {
            font-size: 2rem; /* Reduced from 2.5rem */
            font-weight: 700;
            color: #212529;
            font-family: 'Inter', sans-serif;
        }
        .stat-label {
            font-size: 0.75rem; /* Reduced from 0.85rem */
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #6c757d;
            font-weight: 600;
        }

        .timer-card {
            background: #e7f1ff;
            border-color: #cff4fc;
        }
        .timer-value {
            color: #0d6efd;
        }

        /* Controls */
        .controls-area {
            margin-top: auto;
            padding-top: 10px;
        }

        /* Focus Overlay */
        #focus-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255, 0.8);
            backdrop-filter: blur(2px);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 100;
            cursor: pointer;
            border-radius: 12px 12px 0 0; /* Match parent */
        }
        .click-to-focus {
            background: #212529;
            color: white;
            padding: 15px 30px;
            border-radius: 50px;
            font-weight: 600;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(33, 37, 41, 0.4); }
            70% { transform: scale(1.05); box-shadow: 0 0 0 10px rgba(33, 37, 41, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(33, 37, 41, 0); }
        }

    </style>
</head>
<body>

<div class="test-container">
    
    <!-- Left: Typing Area -->
    <div class="typing-section" id="typing-section">
        <header class="mb-4 d-flex justify-content-between align-items-center" style="max-width: 900px; width: 100%; margin: 0 auto;">
            <div>
                <h4 class="fw-bold mb-1"><?php echo htmlspecialchars($test['test_title']); ?></h4>
                <span class="badge bg-secondary"><?php echo htmlspecialchars($test['test_type']); ?></span>
            </div>
            <a href="typing-lessons.php" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="return confirm('Are you sure you want to quit? Progress will be lost.')">
                <i class="fa-solid fa-xmark me-1"></i> Quit Test
            </a>
        </header>

        <div class="typing-area-wrapper" onclick="focusInput()">
            <div id="text-display"></div>
            <div id="focus-overlay">
                <div class="click-to-focus"><i class="fa-solid fa-mouse-pointer me-2"></i> Click here to start typing</div>
            </div>
        </div>
        
        <div class="typed-content-wrapper" onclick="focusInput()">
            <div class="typed-label">Your Output</div>
            <div id="user-typed-output"><span class="cursor-blink"></span></div>
        </div>
        
        <input type="text" id="input-field" autocomplete="off" spellcheck="false">

        <div class="text-center mt-3 text-muted small">
            <i class="fa-solid fa-lock me-1"></i> Anti-Cheat Enabled: Copy, Paste & Mouse Selection Disabled
        </div>
    </div>

    <!-- Right: Sidebar Stats -->
    <div class="stats-sidebar">
        <div class="mb-4 text-center">
            <img src="../assets/images/rgcsm-logo.png" alt="Logo" style="height: 40px;">
        </div>

        <div class="stat-card timer-card">
            <div class="stat-value timer-value" id="timer">00:00</div>
            <div class="stat-label">Time Remaining</div>
        </div>

        <div class="row">
            <div class="col-6">
                 <div class="stat-card">
                    <div class="stat-value text-primary" id="wpm">0</div>
                    <div class="stat-label">WPM</div>
                </div>
            </div>
            <div class="col-6">
                <div class="stat-card">
                    <div class="stat-value text-success" id="accuracy">100%</div>
                    <div class="stat-label">Accuracy</div>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-value text-danger" id="errors">0</div>
            <div class="stat-label">
                Errors 
                <span class="badge bg-light text-dark border ms-1" id="error-unit-label" style="font-size: 0.6rem; vertical-align: middle;">CHARS</span>
            </div>
        </div>

        <div class="controls-area">
            <!-- Error Unit Toggle -->
            <div class="mb-3">
                <label class="small text-muted fw-bold d-block mb-2">Error Display:</label>
                <div class="btn-group w-100" role="group">
                    <input type="radio" class="btn-check" name="errorUnit" id="unit-char" autocomplete="off" checked onchange="updateErrorDisplay()">
                    <label class="btn btn-outline-secondary btn-sm" for="unit-char">Character</label>

                    <input type="radio" class="btn-check" name="errorUnit" id="unit-word" autocomplete="off" onchange="updateErrorDisplay()">
                    <label class="btn btn-outline-secondary btn-sm" for="unit-word">Word</label>
                </div>
            </div>

            <!-- Backspace Toggle -->
            <div class="form-check form-switch mb-3 custom-toggle">
                <input class="form-check-input" type="checkbox" id="backspace-toggle" checked style="cursor: pointer;">
                <label class="form-check-label fw-bold small text-muted" for="backspace-toggle">Allow Backspace</label>
            </div>
            
            <button class="btn btn-dark w-100 py-2 rounded-pill fw-bold disabled" id="submit-btn">
                Submitting...
            </button>
        </div>
    </div>
</div>

<!-- Hidden Form for Submission -->
<form id="submission-form" action="submit-test.php" method="POST" style="display: none;">
    <input type="hidden" name="test_id" value="<?php echo $test_id; ?>">
    <input type="hidden" name="wpm" id="input-wpm">
    <input type="hidden" name="accuracy" id="input-accuracy">
    <input type="hidden" name="errors" id="input-errors">
    <input type="hidden" name="total_typed" id="input-total-typed">
    <input type="hidden" name="duration_seconds" id="input-duration">
</form>


<script>
    // --- Configuration & State ---
    const originalText = `<?php echo addslashes($clean_content); ?>`;
    const durationMinutes = <?php echo $test['duration_minutes']; ?>;
    let timeRemaining = durationMinutes * 60;
    
    let isTestActive = false;
    let isTestFinished = false;
    let timerInterval = null;
    
    let charIndex = 0;
    let errors = 0; // Character errors
    let wordErrors = 0; // Word errors
    let failedWordIndices = new Set(); // Track unique words that had errors
    let charToWordMap = []; // Map char index to word index
    let correctChars = 0;
    let totalTyped = 0;
    
    // DOM Elements
    const textDisplay = document.getElementById('text-display');
    const inputField = document.getElementById('input-field');
    const focusOverlay = document.getElementById('focus-overlay');
    const timerElement = document.getElementById('timer');
    const wpmElement = document.getElementById('wpm');
    const accuracyElement = document.getElementById('accuracy');
    const errorsElement = document.getElementById('errors');
    const errorUnitLabel = document.getElementById('error-unit-label');
    const unitCharRadio = document.getElementById('unit-char');
    const backspaceToggle = document.getElementById('backspace-toggle');
    const submitBtn = document.getElementById('submit-btn');

    // --- Initialization ---
    function init() {
        // Pre-calculate word indices
        let currentWordIndex = 0;
        charToWordMap = originalText.split('').map(char => {
            const index = currentWordIndex;
            if (char === ' ') currentWordIndex++;
            return index;
        });

        // Split text into span characters
        textDisplay.innerHTML = '';
        originalText.split('').forEach(char => {
            const span = document.createElement('span');
            span.innerText = char;
            span.classList.add('char');
            textDisplay.appendChild(span);
        });
        
        // Set Initial Active Char
        if(textDisplay.firstChild) {
            textDisplay.firstChild.classList.add('active');
        }

        updateTimerDisplay();
    }

    // --- Game Logic ---

    let startTime = null;

    // --- Game Logic ---

    function startTest() {
        if (!isTestActive && !isTestFinished) {
            isTestActive = true;
            focusOverlay.style.display = 'none';
            startTime = Date.now();
            
            timerInterval = setInterval(() => {
                timeRemaining--;
                updateTimerDisplay();
                // updateStats(); // Removed from here, called on input instead for smoother updates, 
                // actually we should keep it here too to update even if user stops typing
                updateStats();
                
                if (timeRemaining <= 0) {
                    finishTest();
                }
            }, 1000);
        }
    }

    function finishTest() {
        clearInterval(timerInterval);
        isTestActive = false;
        isTestFinished = true;
        inputField.disabled = true;
        
        // Ensure final stats are calculated
        updateStats();

        // Prepare and Submit Data
        document.getElementById('input-wpm').value = wpmElement.innerText;
        document.getElementById('input-accuracy').value = accuracyElement.innerText.replace('%', '');
        document.getElementById('input-errors').value = errors;
        document.getElementById('input-total-typed').value = totalTyped;
        document.getElementById('input-duration').value = Math.max(1, Math.round((Date.now() - startTime) / 1000)); // Actual seconds taken

        submitBtn.classList.remove('disabled');
        submitBtn.innerHTML = '<i class="fa-solid fa-check me-2"></i> Submit Test';
        
        // Optional: Auto submit after 2 seconds
        // setTimeout(() => document.getElementById('submission-form').submit(), 2000);
        alert("Test Completed! Your WPM: " + wpmElement.innerText);
    }

    // URL for user input display
    const userTypedOutput = document.getElementById('user-typed-output');


    function focusInput() {
        if(!isTestFinished) {
            inputField.focus();
            // detailed UX: Hide overlay immediately so user sees text
            focusOverlay.style.opacity = '0'; 
            setTimeout(() => {
                if(document.activeElement === inputField) {
                    focusOverlay.style.display = 'none';
                }
            }, 200); // smooth fade out
            
            document.querySelector('.typing-area-wrapper').classList.add('focused');
        }
    }
    
    // If user clicks away, show overlay again (pause effect visual only, timer keeps running if started)
    inputField.addEventListener('blur', () => {
        if(!isTestFinished) {
             focusOverlay.style.display = 'flex';
             setTimeout(() => focusOverlay.style.opacity = '1', 10);
             document.querySelector('.typing-area-wrapper').classList.remove('focused');
        }
    });

    // Capture typing
    inputField.addEventListener('keydown', (e) => {
        if (isTestFinished) return;
        
        // Hide overlay securely just in case
        focusOverlay.style.display = 'none';

        // Start on first keypress valid char
        if (!isTestActive) startTest();

        const chars = textDisplay.querySelectorAll('.char');
        const typedChar = e.key;
        
        // Prevent default actions for standard keys to keep focus
        if(['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'Home', 'End', 'PageUp', 'PageDown'].includes(e.key)) {
            e.preventDefault();
            return;
        }

        // Handle Backspace
        if (e.key === 'Backspace') {
            if (!backspaceToggle.checked) {
                e.preventDefault();
                return; // Backspace disabled
            }
            
            if (charIndex > 0) {
                charIndex--;
                chars[charIndex].classList.remove('correct', 'incorrect');
                chars[charIndex].classList.add('active');
                if (chars[charIndex + 1]) chars[charIndex + 1].classList.remove('active');
                
                // Remove last char from UI output (preserve cursor)
                const currentText = userTypedOutput.innerText;
                userTypedOutput.innerHTML = currentText.slice(0, -1) + '<span class="cursor-blink"></span>';
                userTypedOutput.scrollTop = userTypedOutput.scrollHeight;
            }
            return;
        }
        
        // Ignore modifiers
        if (e.key.length > 1) return; 

        // Update User Typed Output
        // Remove cursor, add char, add cursor back
        const currentContent = userTypedOutput.innerText;
        userTypedOutput.innerHTML = currentContent + typedChar + '<span class="cursor-blink"></span>';
        userTypedOutput.scrollTop = userTypedOutput.scrollHeight; // Auto-scroll typed output

        // Main Logic
        if (charIndex < chars.length) {
            const expectedChar = originalText[charIndex];
            
            totalTyped++;
            
            if (typedChar === expectedChar) {
                chars[charIndex].classList.add('correct');
                correctChars++;
            } else {
                chars[charIndex].classList.add('incorrect');
                errors++; // Increment char error
                
                // Track Word Error
                const wordIndex = charToWordMap[charIndex];
                if (!failedWordIndices.has(wordIndex)) {
                    failedWordIndices.add(wordIndex);
                    wordErrors++;
                }
            }
            
            chars[charIndex].classList.remove('active');
            charIndex++;
            
            if (charIndex < chars.length) {
                chars[charIndex].classList.add('active');
                // Auto Scroll Logic
                scrollToActive(chars[charIndex]);
            } else {
                // End of text
                finishTest();
            }
            
            updateStats();
        }
    });

    // --- Helper Functions ---

    function scrollToActive(activeChar) {
        // Robust scrolling: center the active character in the view
        activeChar.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
            inline: 'nearest'
        });
    }

    function updateStats() {
        if (!startTime) return;
        
        const currentTime = Date.now();
        const timeElapsedSeconds = (currentTime - startTime) / 1000;
        const minutes = timeElapsedSeconds / 60;
        
        // WPM = (Total Correct Chars / 5) / Minutes
        let wpm = 0;
        if (minutes > 0.001) { // Avoid division by zero or super spikes at 0.0001s
            wpm = Math.round((correctChars / 5) / minutes);
        }
        
        // Accuracy
        let accuracy = 0;
        if (totalTyped > 0) {
            accuracy = Math.round(((totalTyped - errors) / totalTyped) * 100);
            if (accuracy < 0) accuracy = 0; 
        } else {
             accuracy = 100;
        }

        wpmElement.innerText = wpm;
        accuracyElement.innerText = accuracy + '%';
        
        // Update Errors Display based on Toggle
        updateErrorDisplay();
    }

    function updateErrorDisplay() {
        if (typeof unitCharRadio === 'undefined') return;

        if (unitCharRadio.checked) {
            errorsElement.innerText = errors;
            errorUnitLabel.innerText = "CHARS";
        } else {
            errorsElement.innerText = wordErrors;
            errorUnitLabel.innerText = "WORDS";
        }
    }

    function updateTimerDisplay() {
        const m = Math.floor(timeRemaining / 60);
        const s = timeRemaining % 60;
        timerElement.innerText = `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
        
        // Visual Warning
        if (timeRemaining < 60) {
            timerElement.classList.add('text-danger');
            timerElement.classList.remove('text-primary'); // Assuming default was primary
        }
    }
    
    // --- Security ---
    // Prevent Context Menu
    document.addEventListener('contextmenu', event => event.preventDefault());
    
    // Prevent Copy/Paste overrides
    document.addEventListener('keydown', e => {
        if ((e.ctrlKey || e.metaKey) && (e.key === 'c' || e.key === 'v' || e.key === 'x' || e.key === 'a')) {
            e.preventDefault();
        }
    });

    // Start
    init();

</script>

</body>
</html>
