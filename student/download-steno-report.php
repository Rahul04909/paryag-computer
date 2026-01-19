<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../database/db_config.php';
require_once '../vendor/autoload.php';

if (!isset($_GET['id'])) {
    die("Result ID missing.");
}

$result_id = $_GET['id'];
$student_id = $_SESSION['student_id'] ?? 1;

// Fetch Data
$stmt = $conn->prepare("SELECT sr.*, st.test_title,
                        s.name as student_name, s.father_name, s.mother_name, s.contact_number
                        FROM steno_results sr 
                        JOIN steno_tests st ON sr.test_id = st.id 
                        LEFT JOIN students s ON sr.student_id = s.id
                        WHERE sr.id = :id");
$stmt->execute(['id' => $result_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) die("Data not found.");

// Diff Logic
$original = preg_split('/\s+/', trim($data['original_content_snapshot']));
$typed = preg_split('/\s+/', trim($data['typed_content']));

function compute_diff_pdf($from, $to) {
    $matrix = [];
    $n = count($from);
    $m = count($to);
    for ($i = 0; $i <= $n; $i++) $matrix[$i][0] = 0;
    for ($j = 0; $j <= $m; $j++) $matrix[0][$j] = 0;
    for ($i = 1; $i <= $n; $i++) {
        for ($j = 1; $j <= $m; $j++) {
            if ($from[$i-1] === $to[$j-1]) $matrix[$i][$j] = $matrix[$i-1][$j-1] + 1;
            else $matrix[$i][$j] = max($matrix[$i-1][$j], $matrix[$i][$j-1]);
        }
    }
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
$diff_result = compute_diff_pdf($original, $typed);

try {
    // Check if tmp dir exists, valid for WAMP
    $tmpDir = __DIR__ . '/../tmp';
    if (!file_exists($tmpDir)) {
        mkdir($tmpDir, 0777, true);
    }

    use Mpdf\Mpdf;
    // Explicitly setting tempDir often fixes 500 errors on Windows/WAMP
    $mpdf = new Mpdf([
        'mode' => 'utf-8', 
        'format' => 'A4',
        'tempDir' => $tmpDir
    ]);

    // Styles
    $css = '
    body { font-family: sans-serif; }
    .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 20px; }
    .logo { width: 80px; }
    .institute-name { font-size: 24px; font-weight: bold; margin-top: 10px; color: #0d6efd; }
    .report-title { font-size: 18px; font-weight: bold; margin-top: 5px; text-transform: uppercase; }
    .section-title { font-size: 14px; font-weight: bold; background-color: #f0f0f0; padding: 5px; margin-bottom: 10px; margin-top: 10px; border-left: 4px solid #0d6efd; }
    .table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    .table td { padding: 8px; border: 1px solid #ddd; }
    .label { font-weight: bold; width: 30%; background-color: #f9fafb; }
    .content-area { font-family: monospace; font-size: 11px; line-height: 1.6; text-align: justify; }
    .word-match { color: #000; }
    .word-missing { color: red; text-decoration: line-through; background-color: #ffe6e6; }
    .word-extra { color: #ae8a02; background-color: #fff3cd; text-decoration: underline; }
    .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 10px; color: #777; border-top: 1px solid #eee; padding-top: 10px; }
    ';

    // Build HTML
    $student_name = $data['student_name'] ?? 'Demo Student';
    $father_name = $data['father_name'] ?? 'Demo Father';
    $mother_name = $data['mother_name'] ?? 'Demo Mother';
    $contact = $data['contact_number'] ?? '9876543210';

    $html = '
    <div class="header">
        <img src="../assets/images/paryag-computer-logo.jpeg" class="logo">
        <div class="institute-name">PARYAG COMPUTER</div>
        <div class="report-title">Stenography Test Report</div>
    </div>

    <div class="section-title">Student Details</div>
    <table class="table">
        <tr>
            <td class="label">Name:</td><td>' . htmlspecialchars($student_name) . '</td>
            <td class="label">Father Name:</td><td>' . htmlspecialchars($father_name) . '</td>
        </tr>
        <tr>
            <td class="label">Mother Name:</td><td>' . htmlspecialchars($mother_name) . '</td>
            <td class="label">Mobile:</td><td>' . htmlspecialchars($contact) . '</td>
        </tr>
    </table>

    <div class="section-title">Test Performance</div>
    <table class="table">
        <tr>
            <td class="label">Test Name:</td><td>' . htmlspecialchars($data['test_title']) . '</td>
            <td class="label">Date:</td><td>' . date('d-M-Y h:i A', strtotime($data['created_at'])) . '</td>
        </tr>
        <tr>
            <td class="label">WPM:</td><td>' . $data['wpm'] . '</td>
            <td class="label">Accuracy:</td><td>' . $data['accuracy'] . '%</td>
        </tr>
        <tr>
            <td class="label">Total Words:</td><td>' . $data['total_words'] . '</td>
            <td class="label">Mistakes:</td><td>' . $data['mistakes'] . '</td>
        </tr>
    </table>

    <div class="section-title">Content Analysis</div>
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 10px; border-right: 1px solid #ccc;">
                <strong>Original Text</strong><br><br>
                <div class="content-area">
                    ' . nl2br(htmlspecialchars($data['original_content_snapshot'])) . '
                </div>
            </td>
            <td style="width: 50%; vertical-align: top; padding-left: 10px;">
                <strong>Typed & Checked</strong><br><br>
                <div class="content-area">';
                
                foreach ($diff_result as $chunk) {
                    $content = htmlspecialchars($chunk['content']) . ' ';
                    if ($chunk['type'] == 'match') {
                        $html .= '<span class="word-match">' . $content . '</span>';
                    } elseif ($chunk['type'] == 'missing') {
                        $html .= '<span class="word-missing">' . $content . '</span>';
                    } elseif ($chunk['type'] == 'extra') {
                        $html .= '<span class="word-extra">' . $content . '</span>';
                    }
                }

    $html .= '  </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Report Generated on ' . date('d-M-Y H:i:s') . ' | Paryag Computer Steno System
    </div>
    ';

    $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
    $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);

    // Output
    $mpdf->Output('Steno_Report_' . $result_id . '.pdf', 'D'); // D for Download

} catch (\Mpdf\MpdfException $e) {
    echo "mPDF Error: " . $e->getMessage();
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage();
}
?>
