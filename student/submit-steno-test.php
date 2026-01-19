<?php
session_start();
require_once '../database/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: steno/index.php");
    exit;
}

// 1. Get Data
$test_id = $_POST['test_id'] ?? null;
$typed_content = $_POST['typed_content'] ?? '';
$duration_taken = $_POST['duration_taken'] ?? 0; // Seconds
$student_id = $_SESSION['student_id'] ?? 1; // Default to 1 if auth disabled for testing

if (!$test_id) {
    die("Invalid request.");
}

// 2. Fetch Original Content
$stmt = $conn->prepare("SELECT test_content FROM steno_tests WHERE id = :id");
$stmt->execute(['id' => $test_id]);
$test = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$test) {
    die("Test not found.");
}

$original_content = $test['test_content'];

// 3. Comparison Logic (Simple Diff)
// Normalize text: remove html tags if any, strip extra spaces, lowercase for matching? 
// Steno usually requires exact case unless specified. Let's keep case but trim.
$original_text = trim(strip_tags($original_content)); // CKEditor might save HTML
$typed_text = trim($typed_content);

// Split into arrays of words
// Handling punctuation: "word," should match "word,"? Or "word" and ","?
// Ideally, simpler split by space.
$original_words = preg_split('/\s+/', $original_text);
$typed_words = preg_split('/\s+/', $typed_text);

// We need to generate a diff to identify correct, wrong, extra, missing.
// We can use a simple algorithm or just store the raw words and let the Result Page do the visual diff.
// BUT we need stats now: Mistakes count.

function calculate_accuracy($original, $typed) {
    // This is a complex problem (Longest Common Subsequence).
    // For simplicity, we'll try to align words.
    // However, implementing a full diff in PHP here might be overkill if we just want counts.
    // Let's use a simplified approach:
    // Total Error = Levenshtein distance at word level? No.
    // Let's count "Wrong" (Mismatch), "Missing" (In original, not in typed), "Extra" (In typed, not in original).
    
    // A quick way for stats:
    // 1. Total Words in Original
    // 2. Count Matching words (regardless of position? No, order matters).
    
    // Let's stick to a robust Diff approach for highlighting later (Result Page).
    // Here we just need approximate numbers if we want to save them.
    // Actually, saving the counts is useful.
    
    // Simpler Algo for now:
    // Iterate typed and try to match with original[current_index...lookahead]
    
    $n = count($original);
    $m = count($typed);
    $errors = 0;
    
    // Simple Levenshtein on strings (at character level) is standard for typing tests?
    // Usually Typing Tests use: Characters Typed Correctly / Total Characters.
    // But Steno is often Word based.
    // User requested: "Wrong words", "Missing words", "Extra words".
    
    // Let's assume the comparison engine will be used on the result page too.
    // We will save 'mistakes' count roughly here.
    
    // Quick diff calculation for stats:
    // Similar_text function in PHP?
    $sim = similar_text(implode(' ', $original), implode(' ', $typed), $percent);
    
    // Mistakes could be approximated as: Total Words - Matching Words.
    // Let's do a strict counts calc.
    // Note: Accurate word-diff requires LCS.
    
    // Using a PHP implementation of Myers diff algorithm (condensed)
    // Or just save the content and calculate exact stats on display?
    // The DB table has 'mistakes', 'total_words', 'typed_words'.
    // We should populate them.
    
    // Let's do a simple check:
    $matches = 0;
    $o_idx = 0;
    $t_idx = 0;
    
    while ($o_idx < $n && $t_idx < $m) {
        if ($original[$o_idx] === $typed[$t_idx]) {
            $matches++;
            $o_idx++;
            $t_idx++;
        } else {
            // Mismatch
            // Try to look ahead in original (Missing word in typed)
            // Try to look ahead in typed (Extra word in typed)
            
            // Heuristic: If next word in original matches current typed, then current original was skipped (Missing).
            if (isset($original[$o_idx+1]) && $original[$o_idx+1] === $typed[$t_idx]) {
                 // Missing word
                 $o_idx++; // Skip original
            }
            // Else if next word in typed matches current original, then current typed was extra.
            elseif (isset($typed[$t_idx+1]) && $original[$o_idx] === $typed[$t_idx+1]) {
                // Extra word
                $t_idx++; // Skip typed
            } 
            else {
                // Wrong word (Replacement)
                $o_idx++;
                $t_idx++;
            }
        }
    }
    
    $total_words = $n;
    $typed_count = $m;
    $mistakes = max(0, $total_words - $matches); // Rough estimate
    // Or closer define: matches is accurate count of correct words in sequence.
    // Mistakes = (Total Original - Matches) + (Total Typed - Matches)? 
    // No.
    // Mistakes = (Wrong) + (Missing) + (Extra).
    // Let's just store "Mistakes" as Total Errors.
    // If we use the simple loop above:
    // Accuracy % = (Matches / Total Original) * 100 ?
    
    return [
        'matches' => $matches,
        'mistakes' => ($n - $matches) + ($m - $matches) // Very rough: Missing + Extra/Wrong
    ];
}

$analysis = calculate_accuracy($original_words, $typed_words);
$matches = $analysis['matches'];
// Let's trust similar_text slightly more for 'Character Accuracy' if needed, but for Words:
// User requested specific Error types. We will implement detailed Diff in Result Page.
// For DB 'mistakes' column, let's store (Total Length of Diff).
// Or just: Total Words - Matches.
$mistakes_count = count($original_words) - $matches; // Only counting missed/wrong original words?
// What if user typed junk?
// Proper grading needs full LCS.
// Let's use a simpler metric for "Quick Stats" and do full viz later.
// Accuracy formula: (Correct Words / Total Words) * 100.
$accuracy = (count($original_words) > 0) ? ($matches / count($original_words)) * 100 : 0;

// WPM Calculation
// Standard: (Characters / 5) / Minutes
$char_count = strlen($typed_text);
$minutes = $duration_taken / 60;
$wpm = ($minutes > 0) ? round(($char_count / 5) / $minutes) : 0;

if ($wpm > 200) $wpm = 0; // Sanity check for cheats or bugs

// 4. Save to DB
$stmt = $conn->prepare("INSERT INTO steno_results 
    (student_id, test_id, total_words, typed_words, mistakes, accuracy, wpm, duration_taken, original_content_snapshot, typed_content)
    VALUES 
    (:sid, :tid, :tw, :tyw, :mis, :acc, :wpm, :dur, :orig, :typ)");

$stmt->execute([
    'sid' => $student_id,
    'tid' => $test_id,
    'tw' => count($original_words),
    'tyw' => count($typed_words),
    'mis' => $mistakes_count,
    'acc' => round($accuracy, 2),
    'wpm' => $wpm,
    'dur' => $duration_taken,
    'orig' => $original_text, // Store snapshot in case test changes
    'typ' => $typed_text
]);

$result_id = $conn->lastInsertId();

header("Location: steno-result.php?id=" . $result_id);
exit;
?>
