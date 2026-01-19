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
$protected_languages = ['English', 'Hindi'];

// Handle Add Language
if (isset($_POST['add_language'])) {
    $language_name = trim($_POST['language_name']);
    if (!empty($language_name)) {
        try {
            $stmt = $conn->prepare("INSERT INTO typing_languages (language_name) VALUES (:name)");
            $stmt->execute(['name' => $language_name]);
            $message = "Language added successfully!";
            $alert_type = "success";
        } catch(PDOException $e) {
            if ($e->getCode() == 23000) {
                $message = "Language already exists!";
            } else {
                $message = "Error: " . $e->getMessage();
            }
            $alert_type = "danger";
        }
    } else {
        $message = "Language name cannot be empty.";
        $alert_type = "warning";
    }
}

// Handle Delete Language
if (isset($_POST['delete_language'])) {
    $id = $_POST['language_id'];
    $name = $_POST['language_name'];
    
    if (in_array($name, $protected_languages)) {
        $message = "Cannot delete default language: $name";
        $alert_type = "danger";
    } else {
        try {
            $stmt = $conn->prepare("DELETE FROM typing_languages WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $message = "Language deleted successfully!";
            $alert_type = "success";
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $alert_type = "danger";
        }
    }
}

// Handle Edit Language
if (isset($_POST['edit_language'])) {
    $id = $_POST['edit_id'];
    $old_name = $_POST['old_name'];
    $new_name = trim($_POST['edit_name']);
    
    if (in_array($old_name, $protected_languages)) {
        $message = "Cannot edit default language: $old_name";
        $alert_type = "danger";
    } elseif (!empty($new_name)) {
        try {
            $stmt = $conn->prepare("UPDATE typing_languages SET language_name = :name WHERE id = :id");
            $stmt->execute(['name' => $new_name, 'id' => $id]);
            $message = "Language updated successfully!";
            $alert_type = "success";
        } catch(PDOException $e) {
             if ($e->getCode() == 23000) {
                $message = "Language name already exists!";
            } else {
                $message = "Error: " . $e->getMessage();
            }
            $alert_type = "danger";
        }
    }
}


// Fetch Languages
$languages = [];
try {
    $stmt = $conn->query("SELECT * FROM typing_languages ORDER BY created_at DESC");
    $languages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    // Table might not exist
}

include '../header.php';
?>

<div class="d-flex">
    <?php include '../sidebar.php'; ?>
    
    <div class="main-content w-100">
        <header class="top-header">
            <h4 class="fw-bold">Manage Typing Languages</h4>
        </header>

        <div class="container-fluid p-4">
             <?php if($message): ?>
                <div class="alert alert-<?php echo $alert_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-header bg-white border-0 pt-4 px-4">
                            <h5 class="mb-0 fw-bold text-primary">Add New Language</h5>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label class="form-label">Language Name</label>
                                    <input type="text" class="form-control" name="language_name" placeholder="e.g. Gujarati" required>
                                </div>
                                <button type="submit" name="add_language" class="btn btn-primary w-100"><i class="fa-solid fa-plus me-2"></i> Add Language</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-header bg-white border-0 pt-4 px-4">
                            <h5 class="mb-0 fw-bold text-dark">Available Languages</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Language Name</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($languages)): ?>
                                            <?php foreach ($languages as $index => $lang): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><span class="fw-medium"><?php echo htmlspecialchars($lang['language_name']); ?></span></td>
                                                <td>
                                                    <?php if (in_array($lang['language_name'], $protected_languages)): ?>
                                                        <span class="badge bg-secondary"><i class="fa-solid fa-lock me-1"></i> Default</span>
                                                    <?php else: ?>
                                                        <!-- Edit Button -->
                                                        <button class="btn btn-sm btn-outline-primary me-2" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editModal" 
                                                                onclick="setEditData('<?php echo $lang['id']; ?>', '<?php echo htmlspecialchars($lang['language_name']); ?>')">
                                                            <i class="fa-solid fa-pen"></i>
                                                        </button>
                                                        
                                                        <!-- Delete Form -->
                                                        <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this language?');">
                                                            <input type="hidden" name="language_id" value="<?php echo $lang['id']; ?>">
                                                            <input type="hidden" name="language_name" value="<?php echo $lang['language_name']; ?>">
                                                            <button type="submit" name="delete_language" class="btn btn-sm btn-outline-danger">
                                                                <i class="fa-solid fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="3" class="text-center text-muted">No languages found. Run the setup script first.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Language</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="edit_id" id="edit_id">
                    <input type="hidden" name="old_name" id="old_name">
                    <div class="mb-3">
                        <label class="form-label">Language Name</label>
                        <input type="text" class="form-control" name="edit_name" id="edit_name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="edit_language" class="btn btn-primary">Update Language</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function setEditData(id, name) {
        document.getElementById('edit_id').value = id;
        document.getElementById('old_name').value = name;
        document.getElementById('edit_name').value = name;
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
