<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../../admin/login.php");
    exit;
}

require_once '../../database/db_config.php';
$assets_path = '../../admin/assets/';

$message = '';
$alert_type = '';

// Handle Form Submission
if (isset($_POST['save_test'])) {
    $test_title = trim($_POST['test_title']);
    $test_type = $_POST['test_type'];
    $language_id = $_POST['language_id'];
    $level_id = $_POST['level_id'];
    $duration = $_POST['duration'];
    $content = $_POST['test_content'];

    if (!empty($test_title) && !empty($test_type) && !empty($language_id) && !empty($level_id) && !empty($duration) && !empty($content)) {
        try {
            $sql = "INSERT INTO typing_tests (test_title, test_type, language_id, level_id, duration_minutes, test_content) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt->execute([$test_title, $test_type, $language_id, $level_id, $duration, $content])) {
                $message = "Typing Test created successfully!";
                $alert_type = "success";
            }
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $alert_type = "danger";
        }
    } else {
        $message = "All fields are required.";
        $alert_type = "warning";
    }
}

// Fetch Languages
$languages = [];
try {
    $stmt = $conn->query("SELECT * FROM typing_languages ORDER BY language_name ASC");
    $languages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { }

// Fetch Levels
$levels = [];
try {
    $stmt = $conn->query("SELECT * FROM typing_levels ORDER BY id ASC");
    $levels = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { }

include '../../admin/header.php';
?>

<div class="d-flex">
    <?php include '../../admin/sidebar.php'; ?>
    
    <div class="main-content w-100">
        <header class="top-header">
            <h4 class="fw-bold">Create New Typing Test</h4>
        </header>

        <div class="container-fluid p-4">
             <?php if($message): ?>
                <div class="alert alert-<?php echo $alert_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h5 class="mb-0 fw-bold text-primary">Test Details</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Test Title</label>
                                <input type="text" class="form-control" name="test_title" placeholder="e.g. Basic Typing Lesson 1" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Test Type</label>
                                <select class="form-select" name="test_type" required>
                                    <option value="">Select Type</option>
                                    <option value="Lesson">Lesson</option>
                                    <option value="Practice Test">Practice Test</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Typing Language</label>
                                <select class="form-select" name="language_id" required>
                                    <option value="">Select Language</option>
                                    <?php foreach($languages as $lang): ?>
                                        <option value="<?php echo $lang['id']; ?>"><?php echo htmlspecialchars($lang['language_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Difficulty Level</label>
                                <select class="form-select" name="level_id" required>
                                    <option value="">Select Level</option>
                                    <?php foreach($levels as $lvl): ?>
                                        <option value="<?php echo $lvl['id']; ?>"><?php echo htmlspecialchars($lvl['level_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Duration (Minutes)</label>
                                <input type="number" class="form-control" name="duration" placeholder="e.g. 10" min="1" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Test Content</label>
                                <textarea name="test_content" id="test_content" class="form-control" rows="10" required></textarea>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" name="save_test" class="btn btn-primary px-4"><i class="fa-solid fa-save me-2"></i> Create Test</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../admin/assets/js/main.js"></script>

<!-- CKEditor Integration -->
<!-- CKEditor Integration -->
<script src="../../vendor/ckeditor/ckeditor/ckeditor.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if(typeof CKEDITOR !== 'undefined') {
            CKEDITOR.replace('test_content', {
                height: 300,
                versionCheck: false, // Disable new version warning
                removePlugins: 'elementspath,resize', 
                resize_enabled: false,
                entities: false, // Important for Hindi/Unicode
                basicEntities: false // Important for Hindi/Unicode
            });
            console.log("CKEditor 4.22.1 initialized successfully.");
        } else {
            console.error("CKEditor failed to load from ../../vendor/ckeditor/ckeditor/ckeditor.js");
            // Visual feedback for admin
            var textarea = document.getElementById('test_content');
            textarea.style.border = "2px solid red";
            textarea.setAttribute("placeholder", "Error: CKEditor script missing. Please check vendor folder.");
        }
    });
</script>

</body>
</html>
