<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit;
}

$assets_path = '../assets/';
require_once '../../database/db_config.php';

$message = '';
$alert_type = '';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$test_id = $_GET['id'];

// data fetching
try {
    $stmt = $conn->prepare("SELECT * FROM steno_tests WHERE id = :id");
    $stmt->execute(['id' => $test_id]);
    $test = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$test) {
        header("Location: index.php");
        exit;
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Handle Form Submission
if (isset($_POST['update_test'])) {
    $category_id = $_POST['category_id'];
    $language_id = $_POST['language_id'];
    $test_title = trim($_POST['test_title']);
    $content = $_POST['test_content'];
    $duration = $_POST['duration'];
    
    // Handle File Upload
    $audio_path = $test['audio_file']; // Default to existing
    $file_uploaded = false;

    // Check if new file is uploaded
    if (isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] == 0) {
        $file_ext = strtolower(pathinfo($_FILES['audio_file']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['mp3', 'wav', 'ogg', 'm4a'];
        
        if (in_array($file_ext, $allowed_ext)) {
            $audio_dir = '../../assets/audio/steno/';
            if (!file_exists($audio_dir)) {
                mkdir($audio_dir, 0777, true);
            }

            $new_filename = uniqid('steno_audio_', true) . '.' . $file_ext;
            $target_file = $audio_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['audio_file']['tmp_name'], $target_file)) {
                $audio_path = 'assets/audio/steno/' . $new_filename;
                $file_uploaded = true;
            } else {
                $message = "Failed to move uploaded file.";
                $alert_type = "danger";
            }
        } else {
            $message = "Invalid audio format. Only MP3, WAV, OGG, M4A allowed.";
            $alert_type = "danger";
        }
    }

    if (empty($message) && !empty($category_id) && !empty($language_id) && !empty($test_title) && !empty($duration) && !empty($content)) {
        try {
            $sql = "UPDATE steno_tests SET 
                    category_id = :cat, 
                    language_id = :lang, 
                    test_title = :title, 
                    duration_minutes = :duration, 
                    test_content = :content, 
                    audio_file = :audio 
                    WHERE id = :id";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'cat' => $category_id,
                'lang' => $language_id,
                'title' => $test_title,
                'duration' => $duration,
                'content' => $content,
                'audio' => $audio_path,
                'id' => $test_id
            ]);

            // If new file uploaded and update success, delete old file
            if ($file_uploaded && !empty($test['audio_file']) && file_exists('../../' . $test['audio_file'])) {
                unlink('../../' . $test['audio_file']);
            }
            
            // Refresh Data
            $stmt = $conn->prepare("SELECT * FROM steno_tests WHERE id = :id");
            $stmt->execute(['id' => $test_id]);
            $test = $stmt->fetch(PDO::FETCH_ASSOC);

            $message = "Steno Test updated successfully!";
            $alert_type = "success";
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $alert_type = "danger";
        }
    }
}

// Fetch Categories and Languages
try {
    $categories = $conn->query("SELECT * FROM steno_categories ORDER BY category_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $languages = $conn->query("SELECT * FROM typing_languages ORDER BY language_name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { }

include '../header.php';
?>

<div class="d-flex">
    <?php include '../sidebar.php'; ?>
    
    <div class="main-content w-100">
        <header class="top-header">
            <h4 class="fw-bold">Edit Steno Test</h4>
        </header>

        <div class="container-fluid p-4">
             <?php if($message): ?>
                <div class="alert alert-<?php echo $alert_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between">
                    <h5 class="mb-0 fw-bold text-primary">Edit Test Details</h5>
                    <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-left me-2"></i>Back to List</a>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="" enctype="multipart/form-data" onsubmit="if(typeof CKEDITOR !== 'undefined') { for(var instanceName in CKEDITOR.instances) { CKEDITOR.instances[instanceName].updateElement(); } }">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Select Category</label>
                                <select class="form-select" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php foreach($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo $test['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['category_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Select Language</label>
                                <select class="form-select" name="language_id" required>
                                    <option value="">Select Language</option>
                                    <?php foreach($languages as $lang): ?>
                                        <option value="<?php echo $lang['id']; ?>" <?php echo $test['language_id'] == $lang['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($lang['language_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Test Title</label>
                                <input type="text" class="form-control" name="test_title" value="<?php echo htmlspecialchars($test['test_title']); ?>" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Duration (Minutes)</label>
                                <input type="number" class="form-control" name="duration" value="<?php echo htmlspecialchars($test['duration_minutes']); ?>" min="1" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Update Audio File (Optional)</label>
                                <input type="file" class="form-control" name="audio_file" accept=".mp3, .wav, .ogg, .m4a">
                                <small class="text-muted d-block mt-2">Current file: <a href="../../<?php echo htmlspecialchars($test['audio_file']); ?>" target="_blank">Listen</a></small>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Original Paragraph</label>
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
<script src="../assets/js/main.js"></script>

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
        }

        // File Size Validation
        const audioInput = document.querySelector('input[name="audio_file"]');
        if(audioInput) {
            audioInput.addEventListener('change', function() {
                const file = this.files[0];
                if(file) {
                    const fileSize = file.size / 1024 / 1024; // in MB
                    const maxSize = 64; 

                    if(fileSize > maxSize) {
                        alert(`File size exceeds ${maxSize}MB. Please upload a smaller file.`);
                        this.value = ''; 
                    }
                }
            });
        }
    });
</script>

</body>
</html>
