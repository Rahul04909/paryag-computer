<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit;
}

// Define assets path for header
$assets_path = '../assets/';
require_once '../../database/db_config.php';

$message = '';
$alert_type = '';

// Define upload directory for audio
$audio_dir = '../../assets/audio/steno/';
if (!file_exists($audio_dir)) {
    mkdir($audio_dir, 0777, true);
}

// Check for post_max_size violation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST) && empty($_FILES) && $_SERVER['CONTENT_LENGTH'] > 0) {
    $message = "The uploaded file exceeds the post_max_size directive in php.ini.";
    $alert_type = "danger";
}

// Handle Form Submission
if (isset($_POST['save_test'])) {
    $category_id = $_POST['category_id'];
    $language_id = $_POST['language_id'];
    $test_title = trim($_POST['test_title']);
    $content = $_POST['test_content'];
    
    // Handle File Upload
    $audio_path = '';
    
    // Check if file was sent but errored
    if (isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] !== UPLOAD_ERR_OK) {
        $uploadError = $_FILES['audio_file']['error'];
        switch ($uploadError) {
            case UPLOAD_ERR_INI_SIZE:
                $message = "The uploaded file exceeds the upload_max_filesize directive in php.ini.";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.";
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = "The uploaded file was only partially uploaded.";
                break;
            case UPLOAD_ERR_NO_FILE:
                $message = "No file was uploaded.";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $message = "Missing a temporary folder.";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $message = "Failed to write file to disk.";
                break;
            case UPLOAD_ERR_EXTENSION:
                $message = "A PHP extension stopped the file upload.";
                break;
            default:
                $message = "Unknown upload error (Code: $uploadError).";
                break;
        }
        $alert_type = "danger";
    }
    elseif (isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] == 0) {
        $file_ext = strtolower(pathinfo($_FILES['audio_file']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['mp3', 'wav', 'ogg', 'm4a'];
        
        if (in_array($file_ext, $allowed_ext)) {
            $new_filename = uniqid('steno_audio_', true) . '.' . $file_ext;
            $target_file = $audio_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['audio_file']['tmp_name'], $target_file)) {
                $audio_path = 'assets/audio/steno/' . $new_filename;
            } else {
                $message = "Failed to move uploaded file. Check folder permissions.";
                $alert_type = "danger";
            }
        } else {
            $message = "Invalid audio format. Only MP3, WAV, OGG, M4A allowed.";
            $alert_type = "danger";
        }
    } else {
        // Fallback if not set (should be caught by NO_FILE case above usually)
        if (empty($message)) {
             $message = "Audio file is required.";
             $alert_type = "danger";
        }
    }

    if (empty($message) && !empty($category_id) && !empty($language_id) && !empty($test_title) && !empty($_POST['duration']) && !empty($content) && !empty($audio_path)) {
        try {
            $stmt = $conn->prepare("INSERT INTO steno_tests (category_id, language_id, test_title, duration_minutes, test_content, audio_file) 
                                    VALUES (:cat, :lang, :title, :duration, :content, :audio)");
            $stmt->execute([
                'cat' => $category_id,
                'lang' => $language_id,
                'title' => $test_title,
                'duration' => $_POST['duration'],
                'content' => $content,
                'audio' => $audio_path
            ]);
            $message = "Steno Test created successfully!";
            $alert_type = "success";
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $alert_type = "danger";
             // cleanup uploaded file if db insert fails
            if(file_exists('../../' . $audio_path)) {
                unlink('../../' . $audio_path);
            }
        }
    } elseif (empty($message)) {
    } elseif (empty($message)) {
        $missing = [];
        if(empty($category_id)) $missing[] = "Category";
        if(empty($language_id)) $missing[] = "Language";
        if(empty($test_title)) $missing[] = "Title";
        if(empty($_POST['duration'])) $missing[] = "Duration";
        if(empty($content)) $missing[] = "Content";
        if(empty($audio_path)) $missing[] = "Audio File";
        
        $message = "All fields are required. Missing: " . implode(', ', $missing);
        $alert_type = "warning";
    }
}

// Fetch Categories
$categories = [];
try {
    $stmt = $conn->query("SELECT * FROM steno_categories ORDER BY category_name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { }

// Fetch Languages
$languages = [];
try {
    $stmt = $conn->query("SELECT * FROM typing_languages ORDER BY language_name ASC");
    $languages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { }


include '../header.php';
?>

<div class="d-flex">
    <?php include '../sidebar.php'; ?>
    
    <div class="main-content w-100">
        <header class="top-header">
            <h4 class="fw-bold">Create New Steno Test</h4>
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
                    <h5 class="mb-0 fw-bold text-primary">Steno Test Details</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="" enctype="multipart/form-data" onsubmit="if(typeof CKEDITOR !== 'undefined') { for(var instanceName in CKEDITOR.instances) { CKEDITOR.instances[instanceName].updateElement(); } }">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Select Category</label>
                                <select class="form-select" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php foreach($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Select Language</label>
                                <select class="form-select" name="language_id" required>
                                    <option value="">Select Language</option>
                                    <?php foreach($languages as $lang): ?>
                                        <option value="<?php echo $lang['id']; ?>"><?php echo htmlspecialchars($lang['language_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Test Title</label>
                                <input type="text" class="form-control" name="test_title" placeholder="e.g. Dictation 1 (80 WPM)" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Duration (Minutes)</label>
                                <input type="number" class="form-control" name="duration" placeholder="e.g. 10" min="1" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Upload Audio File (MP3/WAV/OGG/M4A)</label>
                                <input type="file" class="form-control" name="audio_file" accept=".mp3, .wav, .ogg, .m4a" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Original Paragraph</label>
                                <textarea name="test_content" id="test_content" class="form-control" rows="10" required></textarea>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" name="save_test" class="btn btn-primary px-4"><i class="fa-solid fa-save me-2"></i> Save Test</button>
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
        } else {
            console.error("CKEditor failed to load.");
        }
    });
</script>

</body>
</html>
