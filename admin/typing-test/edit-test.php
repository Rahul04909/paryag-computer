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
$test_id = isset($_GET['id']) ? $_GET['id'] : null;
$test = null;

if (!$test_id) {
    header("Location: index.php");
    exit;
}

// Fetch Test Data
try {
    $stmt = $conn->prepare("SELECT * FROM typing_tests WHERE id = :id");
    $stmt->execute(['id' => $test_id]);
    $test = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$test) {
        die("Test not found.");
    }
} catch (PDOException $e) {
    die("Error fetching test: " . $e->getMessage());
}

// Handle Update
if (isset($_POST['update_test'])) {
    $test_title = trim($_POST['test_title']);
    $test_type = $_POST['test_type'];
    $language_id = $_POST['language_id'];
    $level_id = $_POST['level_id'];
    $duration = $_POST['duration'];
    $content = $_POST['test_content'];

    if (!empty($test_title) && !empty($test_type) && !empty($language_id) && !empty($level_id) && !empty($duration) && !empty($content)) {
        try {
            $sql = "UPDATE typing_tests SET 
                    test_title = ?, 
                    test_type = ?, 
                    language_id = ?, 
                    level_id = ?, 
                    duration_minutes = ?, 
                    test_content = ? 
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt->execute([$test_title, $test_type, $language_id, $level_id, $duration, $content, $test_id])) {
                $message = "Test updated successfully!";
                $alert_type = "success";
                // Refresh data
                $stmt = $conn->prepare("SELECT * FROM typing_tests WHERE id = :id");
                $stmt->execute(['id' => $test_id]);
                $test = $stmt->fetch(PDO::FETCH_ASSOC);
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

// Fetch Filters (Languages/Levels)
try {
    $languages = $conn->query("SELECT * FROM typing_languages ORDER BY language_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $levels = $conn->query("SELECT * FROM typing_levels ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

include '../../admin/header.php';
?>

<div class="d-flex">
    <?php include '../../admin/sidebar.php'; ?>
    
    <div class="main-content w-100">
        <header class="top-header">
            <h4 class="fw-bold">Edit Typing Test</h4>
            <a href="index.php" class="btn btn-secondary btn-sm"><i class="fa-solid fa-arrow-left me-2"></i> Back to List</a>
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
                    <h5 class="mb-0 fw-bold text-primary">Edit Test Details</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Test Title</label>
                                <input type="text" class="form-control" name="test_title" value="<?php echo htmlspecialchars($test['test_title']); ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Test Type</label>
                                <select class="form-select" name="test_type" required>
                                    <option value="Lesson" <?php echo ($test['test_type'] == 'Lesson') ? 'selected' : ''; ?>>Lesson</option>
                                    <option value="Practice Test" <?php echo ($test['test_type'] == 'Practice Test') ? 'selected' : ''; ?>>Practice Test</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Typing Language</label>
                                <select class="form-select" name="language_id" required>
                                    <option value="">Select Language</option>
                                    <?php foreach($languages as $lang): ?>
                                        <option value="<?php echo $lang['id']; ?>" <?php echo ($test['language_id'] == $lang['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($lang['language_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Difficulty Level</label>
                                <select class="form-select" name="level_id" required>
                                    <option value="">Select Level</option>
                                    <?php foreach($levels as $lvl): ?>
                                        <option value="<?php echo $lvl['id']; ?>" <?php echo ($test['level_id'] == $lvl['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($lvl['level_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Duration (Minutes)</label>
                                <input type="number" class="form-control" name="duration" value="<?php echo $test['duration_minutes']; ?>" min="1" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Test Content</label>
                                <textarea name="test_content" id="test_content" class="form-control" rows="10" required><?php echo htmlspecialchars($test['test_content']); ?></textarea>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" name="update_test" class="btn btn-primary px-4"><i class="fa-solid fa-save me-2"></i> Update Test</button>
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
<script src="../../vendor/ckeditor/ckeditor/ckeditor.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if(typeof CKEDITOR !== 'undefined') {
            CKEDITOR.replace('test_content', {
                height: 300,
                versionCheck: false,
                removePlugins: 'elementspath,resize', 
                resize_enabled: false,
                entities: false, 
                basicEntities: false 
            });
            console.log("CKEditor 4.22.1 initialized successfully.");
        } else {
            console.error("CKEditor failed to load from ../../vendor/ckeditor/ckeditor/ckeditor.js");
            var textarea = document.getElementById('test_content');
            textarea.style.border = "2px solid red";
            textarea.setAttribute("placeholder", "Error: CKEditor script missing. Please check vendor folder.");
        }
    });
</script>

</body>
</html>
