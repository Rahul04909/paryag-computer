<?php
session_start();
require_once '../database/db_config.php';

if (!isset($_GET['id'])) {
    die("Result ID missing.");
}

$result_id = $_GET['id'];

// Fetch Result & Test Info
$stmt = $conn->prepare("SELECT sr.*, st.test_title 
                        FROM steno_results sr 
                        JOIN steno_tests st ON sr.test_id = st.id 
                        WHERE sr.id = :id");
$stmt->execute(['id' => $result_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$result) {
    die("Result not found.");
}

// Diff Logic for Display
$original = preg_split('/\s+/', trim($result['original_content_snapshot']));
$typed = preg_split('/\s+/', trim($result['typed_content']));

// LCS Algorithm for Diff
function compute_diff($from, $to) {
    $matrix = [];
    $n = count($from);
    $m = count($to);
    
    // Initialize matrix
    for ($i = 0; $i <= $n; $i++) $matrix[$i][0] = 0;
    for ($j = 0; $j <= $m; $j++) $matrix[0][$j] = 0;
    
    // Fill
    for ($i = 1; $i <= $n; $i++) {
        for ($j = 1; $j <= $m; $j++) {
            if ($from[$i-1] === $to[$j-1]) {
                $matrix[$i][$j] = $matrix[$i-1][$j-1] + 1;
            } else {
                $matrix[$i][$j] = max($matrix[$i-1][$j], $matrix[$i][$j-1]);
            }
        }
    }
    
    // Backtrack to find path
    $diff = [];
    $i = $n; $j = $m;
    while ($i > 0 || $j > 0) {
        if ($i > 0 && $j > 0 && $from[$i-1] === $to[$j-1]) {
            array_unshift($diff, ['type' => 'match', 'content' => $from[$i-1]]);
            $i--; $j--;
        } elseif ($j > 0 && ($i == 0 || $matrix[$i][$j-1] >= $matrix[$i-1][$j])) {
            array_unshift($diff, ['type' => 'extra', 'content' => $to[$j-1]]);
            $j--;
        } elseif ($i > 0 && ($j == 0 || $matrix[$i][$j-1] < $matrix[$i-1][$j])) {
            array_unshift($diff, ['type' => 'missing', 'content' => $from[$i-1]]);
            $i--;
        }
    }
    return $diff;
}

$diff_result = compute_diff($original, $typed);

include 'header.php';
?>

<style>
    .diff-content {
        font-family: 'Courier New', Courier, monospace;
        line-height: 1.8;
        font-size: 1.05rem;
    }
    .word-match { color: #212529; }
    .word-missing { 
        color: #dc3545; 
        text-decoration: line-through; 
        background-color: #ffe6e6;
        padding: 0 2px;
        margin: 0 1px;
    }
    .word-extra { 
        color: #ffc107; /* Yellowish for extra */
        /* per request: Yellow for extra */
        background-color: #fff3cd;
        border-bottom: 2px solid #ffc107;
        padding: 0 2px;
        margin: 0 1px;
        color: #856404;
    }
    
    /* 
       Request said:
       Red for wrong words (Missing in logic often covers 'wrong' as Missing + Extra combo)
       Yellow for extra
       Underline for missing (optional)
       
       My Diff output has:
       Match
       Missing (In original, not typed) -> User missed this.
       Extra (In typed, not original) -> User added this.
       
       If user typed "wrod" instead of "word":
       It shows as Missing "word" AND Extra "wrod".
       This is mathematically correct. Visually it will look like [word] [wrod].
    */

    .stat-card {
        transition: transform 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-5px);
    }
</style>

    <div class="main-content w-100 bg-light">
        <div class="container p-4">
            
            <!-- Result Header -->
            <div class="card shadow-sm border-0 rounded-4 mb-4">
                <div class="card-body p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h6 class="text-uppercase text-muted fw-bold mb-1">Test Result</h6>
                        <h4 class="fw-bold mb-0 text-primary"><?php echo htmlspecialchars($result['test_title']); ?></h4>
                        <small class="text-muted">Taken on <?php echo date('d M Y, h:i A', strtotime($result['created_at'])); ?></small>
                    </div>
                    <div class="d-flex gap-3">
                        <a href="steno/index.php" class="btn btn-light rounded-pill">
                            <i class="fa-solid fa-arrow-left me-2"></i> Back to List
                        </a>
                        <a href="download-steno-report.php?id=<?php echo $result_id; ?>" class="btn btn-primary rounded-pill shadow-sm" target="_blank">
                            <i class="fa-solid fa-file-pdf me-2"></i> Download Report
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Row -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm border-0 rounded-4 bg-white h-100">
                        <div class="card-body p-4 text-center">
                            <div class="icon-box bg-primary bg-opacity-10 text-primary rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                <i class="fa-solid fa-gauge-high fa-lg"></i>
                            </div>
                            <h3 class="fw-bold mb-0"><?php echo $result['wpm']; ?></h3>
                            <small class="text-muted fw-bold">WPM</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm border-0 rounded-4 bg-white h-100">
                        <div class="card-body p-4 text-center">
                            <div class="icon-box bg-success bg-opacity-10 text-success rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                <i class="fa-solid fa-bullseye fa-lg"></i>
                            </div>
                            <h3 class="fw-bold mb-0"><?php echo $result['accuracy']; ?>%</h3>
                            <small class="text-muted fw-bold">Accuracy</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm border-0 rounded-4 bg-white h-100">
                        <div class="card-body p-4 text-center">
                            <div class="icon-box bg-info bg-opacity-10 text-info rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                <i class="fa-solid fa-keyboard fa-lg"></i>
                            </div>
                            <h3 class="fw-bold mb-0"><?php echo $result['typed_words']; ?></h3>
                            <small class="text-muted fw-bold">Words Typed</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm border-0 rounded-4 bg-white h-100">
                        <div class="card-body p-4 text-center">
                            <div class="icon-box bg-warning bg-opacity-10 text-warning rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                <i class="fa-solid fa-triangle-exclamation fa-lg"></i>
                            </div>
                            <h3 class="fw-bold mb-0"><?php echo $result['mistakes']; ?></h3>
                            <small class="text-muted fw-bold">Approx Mistakes</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Comparison View -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0">
                            <h5 class="fw-bold mb-0">Analysis</h5>
                            <div class="d-flex gap-3 mt-2 small">
                                <span class="d-flex align-items-center"><span class="bg-light border px-2 me-1 rounded">Text</span> Perfect Match</span>
                                <span class="d-flex align-items-center"><span class="bg-danger bg-opacity-10 text-danger border border-danger px-2 me-1 rounded" style="text-decoration: line-through;">Text</span> Missing/Wrong</span>
                                <span class="d-flex align-items-center"><span class="bg-warning bg-opacity-25 text-warning border border-warning px-2 me-1 rounded">Text</span> Extra</span>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="row">
                                <!-- Original Column (Optional, usually we merge views, but requested Two Columns) -->
                                <!-- Request: "Column 1: Original Content", "Column 2: Typed Content (Highlighted)" -->
                                
                                <div class="col-md-6 border-end">
                                    <h6 class="text-uppercase text-muted fw-bold mb-3 border-bottom pb-2">Original Content</h6>
                                    <div class="diff-content text-muted">
                                        <?php echo nl2br(htmlspecialchars($result['original_content_snapshot'])); ?>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <h6 class="text-uppercase text-muted fw-bold mb-3 border-bottom pb-2">Your Typed Content (Analyzed)</h6>
                                    <div class="diff-content">
                                        <?php 
                                        foreach ($diff_result as $chunk) {
                                            $content = htmlspecialchars($chunk['content']) . ' ';
                                            if ($chunk['type'] == 'match') {
                                                echo '<span class="word-match">' . $content . '</span>';
                                            } elseif ($chunk['type'] == 'missing') {
                                                // Missing in typed = Shown as missing
                                                echo '<span class="word-missing">' . $content . '</span>';
                                            } elseif ($chunk['type'] == 'extra') {
                                                // Extra in typed
                                                echo '<span class="word-extra">' . $content . '</span>';
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
