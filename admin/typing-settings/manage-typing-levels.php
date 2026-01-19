<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../../admin/login.php");
    exit;
}

// Correct path to db_config from admin/typing-settings/
require_once '../../database/db_config.php';

// Define assets path for header (going up 2 levels)
$assets_path = '../../admin/assets/';

$message = '';
$alert_type = '';

// Handle Add Level
if (isset($_POST['add_level'])) {
    $level_name = trim($_POST['level_name']);
    if (!empty($level_name)) {
        try {
            $stmt = $conn->prepare("INSERT INTO typing_levels (level_name) VALUES (:name)");
            $stmt->execute(['name' => $level_name]);
            $message = "Level added successfully!";
            $alert_type = "success";
        } catch(PDOException $e) {
            if ($e->getCode() == 23000) {
                $message = "Level already exists!";
            } else {
                $message = "Error: " . $e->getMessage();
            }
            $alert_type = "danger";
        }
    } else {
        $message = "Level name cannot be empty.";
        $alert_type = "warning";
    }
}

// Handle Delete Level
if (isset($_POST['delete_level'])) {
    $id = $_POST['level_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM typing_levels WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $message = "Level deleted successfully!";
        $alert_type = "success";
    } catch(PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $alert_type = "danger";
    }
}

// Handle Edit Level
if (isset($_POST['edit_level'])) {
    $id = $_POST['edit_id'];
    $new_name = trim($_POST['edit_name']);
    
    if (!empty($new_name)) {
        try {
            $stmt = $conn->prepare("UPDATE typing_levels SET level_name = :name WHERE id = :id");
            $stmt->execute(['name' => $new_name, 'id' => $id]);
            $message = "Level updated successfully!";
            $alert_type = "success";
        } catch(PDOException $e) {
             if ($e->getCode() == 23000) {
                $message = "Level name already exists!";
            } else {
                $message = "Error: " . $e->getMessage();
            }
            $alert_type = "danger";
        }
    }
}

// Fetch Levels
$levels = [];
try {
    $stmt = $conn->query("SELECT * FROM typing_levels ORDER BY id ASC");
    $levels = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    // Table might not exist
}

// Include header with adjusted paths
// We need to be careful about including a file that might have relative paths inside it
// admin/header.php usually expects to be in admin root or uses $assets_path.
// Since we are in admin/typing-settings/, we rely on $assets_path set above.
include '../../admin/header.php';
?>

<div class="d-flex">
    <?php include '../../admin/sidebar.php'; ?>
    
    <div class="main-content w-100">
        <header class="top-header">
            <h4 class="fw-bold">Manage Typing Levels</h4>
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
                            <h5 class="mb-0 fw-bold text-primary">Add New Level</h5>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label class="form-label">Level Name</label>
                                    <input type="text" class="form-control" name="level_name" placeholder="e.g. Expert" required>
                                </div>
                                <button type="submit" name="add_level" class="btn btn-primary w-100"><i class="fa-solid fa-plus me-2"></i> Add Level</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-header bg-white border-0 pt-4 px-4">
                            <h5 class="mb-0 fw-bold text-dark">Available Levels</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Level Name</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($levels)): ?>
                                            <?php foreach ($levels as $index => $row): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><span class="fw-medium"><?php echo htmlspecialchars($row['level_name']); ?></span></td>
                                                <td>
                                                    <!-- Edit Button -->
                                                    <button class="btn btn-sm btn-outline-primary me-2" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#editModal" 
                                                            onclick="setEditData('<?php echo $row['id']; ?>', '<?php echo htmlspecialchars($row['level_name']); ?>')">
                                                        <i class="fa-solid fa-pen"></i>
                                                    </button>
                                                    
                                                    <!-- Delete Form -->
                                                    <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this level?');">
                                                        <input type="hidden" name="level_id" value="<?php echo $row['id']; ?>">
                                                        <button type="submit" name="delete_level" class="btn btn-sm btn-outline-danger">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="3" class="text-center text-muted">No levels found. Run the setup script first.</td>
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
                <h5 class="modal-title">Edit Level</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="edit_id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label">Level Name</label>
                        <input type="text" class="form-control" name="edit_name" id="edit_name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="edit_level" class="btn btn-primary">Update Level</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function setEditData(id, name) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_name').value = name;
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../admin/assets/js/main.js"></script>
</body>
</html>
