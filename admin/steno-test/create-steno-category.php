<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit;
}

// Define assets path for header (Admin assets)
$assets_path = '../assets/';
require_once '../../database/db_config.php';

$message = '';
$alert_type = '';

// Define upload directory
$upload_dir = '../../assets/images/steno_category/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Handle Add Category
if (isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name']);
    
    // Handle File Upload
    $logo_path = '';
    if (isset($_FILES['category_logo']) && $_FILES['category_logo']['error'] == 0) {
        $file_ext = strtolower(pathinfo($_FILES['category_logo']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_ext, $allowed_ext)) {
            $new_filename = uniqid('steno_', true) . '.' . $file_ext;
            $target_file = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['category_logo']['tmp_name'], $target_file)) {
                $logo_path = 'assets/images/steno_category/' . $new_filename; // Store relative path from root
            } else {
                $message = "Failed to upload logo.";
                $alert_type = "danger";
            }
        } else {
            $message = "Invalid file type. Only JPG, PNG, GIF allowed.";
            $alert_type = "danger";
        }
    } else {
         $message = "Category logo is required.";
         $alert_type = "danger";
    }

    if (empty($message) && !empty($category_name) && !empty($logo_path)) {
        try {
            $stmt = $conn->prepare("INSERT INTO steno_categories (category_name, category_logo) VALUES (:name, :logo)");
            $stmt->execute(['name' => $category_name, 'logo' => $logo_path]);
            $message = "Category added successfully!";
            $alert_type = "success";
        } catch(PDOException $e) {
            if ($e->getCode() == 23000) {
                $message = "Category already exists!";
            } else {
                $message = "Error: " . $e->getMessage();
            }
            $alert_type = "danger";
            // cleanup uploaded file if db insert fails
            if(file_exists('../../' . $logo_path)) {
                unlink('../../' . $logo_path);
            }
        }
    } elseif (empty($message)) {
        $message = "Category name and logo are required.";
        $alert_type = "warning";
    }
}

// Handle Delete Category
if (isset($_POST['delete_category'])) {
    $id = $_POST['category_id'];
    
    // First get the logo path to delete file
    try {
        $stmt = $conn->prepare("SELECT category_logo FROM steno_categories WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($category) {
            // Delete record
            $delStmt = $conn->prepare("DELETE FROM steno_categories WHERE id = :id");
            $delStmt->execute(['id' => $id]);
            
            // Delete file
            if (!empty($category['category_logo'])) {
                $file_to_delete = '../../' . $category['category_logo'];
                if (file_exists($file_to_delete)) {
                    unlink($file_to_delete);
                }
            }
            
            $message = "Category deleted successfully!";
            $alert_type = "success";
        }
    } catch(PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $alert_type = "danger";
    }
}

// Handle Edit Category
if (isset($_POST['edit_category'])) {
    $id = $_POST['edit_id'];
    $new_name = trim($_POST['edit_name']);
    $old_logo_path = $_POST['old_logo_path']; // Not strictly safe to trust post but for path deletion we re-fetch usually. 
    // Better to re-fetch path from DB during update to be safe, or just update if new file.
    
    $logo_path = $old_logo_path;
    $upload_success = true;

    // Check if new file uploaded
    if (isset($_FILES['edit_category_logo']) && $_FILES['edit_category_logo']['error'] == 0) {
         $file_ext = strtolower(pathinfo($_FILES['edit_category_logo']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_ext, $allowed_ext)) {
            $new_filename = uniqid('steno_', true) . '.' . $file_ext;
            $target_file = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['edit_category_logo']['tmp_name'], $target_file)) {
                $logo_path = 'assets/images/steno_category/' . $new_filename;
                // Delete old file
                if (!empty($old_logo_path) && file_exists('../../' . $old_logo_path)) {
                    unlink('../../' . $old_logo_path);
                }
            } else {
                $message = "Failed to upload new logo.";
                $alert_type = "danger";
                $upload_success = false;
            }
        } else {
            $message = "Invalid file type.";
            $alert_type = "danger";
             $upload_success = false;
        }
    }

    if ($upload_success && !empty($new_name)) {
        try {
            $stmt = $conn->prepare("UPDATE steno_categories SET category_name = :name, category_logo = :logo WHERE id = :id");
            $stmt->execute(['name' => $new_name, 'logo' => $logo_path, 'id' => $id]);
            $message = "Category updated successfully!";
            $alert_type = "success";
        } catch(PDOException $e) {
             if ($e->getCode() == 23000) {
                $message = "Category name already exists!";
            } else {
                $message = "Error: " . $e->getMessage();
            }
            $alert_type = "danger";
        }
    }
}


// Fetch Categories
$categories = [];
try {
    $stmt = $conn->query("SELECT * FROM steno_categories ORDER BY created_at DESC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    // Table might not exist
}

include '../header.php';
?>

<div class="d-flex">
    <?php include '../sidebar.php'; ?>
    
    <div class="main-content w-100">
        <header class="top-header">
            <h4 class="fw-bold">Manage Steno Categories</h4>
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
                            <h5 class="mb-0 fw-bold text-primary">Add New Category</h5>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST" action="" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label class="form-label">Category Name</label>
                                    <input type="text" class="form-control" name="category_name" placeholder="e.g. English Steno" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Category Logo</label>
                                    <input type="file" class="form-control" name="category_logo" accept="image/*" required>
                                    <div class="form-text">Recommended size: 100x100px</div>
                                </div>
                                <button type="submit" name="add_category" class="btn btn-primary w-100"><i class="fa-solid fa-plus me-2"></i> Add Category</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-header bg-white border-0 pt-4 px-4">
                            <h5 class="mb-0 fw-bold text-dark">Available Categories</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Logo</th>
                                            <th>Category Name</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($categories)): ?>
                                            <?php foreach ($categories as $index => $cat): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td>
                                                    <?php if(!empty($cat['category_logo'])): ?>
                                                        <img src="../../<?php echo htmlspecialchars($cat['category_logo']); ?>" alt="Logo" style="width: 40px; height: 40px; object-fit: contain;">
                                                    <?php else: ?>
                                                        <span class="text-muted">No Logo</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><span class="fw-medium"><?php echo htmlspecialchars($cat['category_name']); ?></span></td>
                                                <td>
                                                    <!-- Edit Button -->
                                                    <button class="btn btn-sm btn-outline-primary me-2" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#editModal" 
                                                            onclick="setEditData('<?php echo $cat['id']; ?>', '<?php echo htmlspecialchars($cat['category_name']); ?>', '<?php echo htmlspecialchars($cat['category_logo']); ?>')">
                                                        <i class="fa-solid fa-pen"></i>
                                                    </button>
                                                    
                                                    <!-- Delete Form -->
                                                    <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                                        <input type="hidden" name="category_id" value="<?php echo $cat['id']; ?>">
                                                        <button type="submit" name="delete_category" class="btn btn-sm btn-outline-danger">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">No categories found.</td>
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
                <h5 class="modal-title">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="edit_id" id="edit_id">
                    <input type="hidden" name="old_logo_path" id="old_logo_path">
                    
                    <div class="mb-3 text-center">
                        <img id="current_logo_preview" src="" alt="Current Logo" style="max-width: 100px; max-height: 100px; display: none;" class="mb-2">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Category Name</label>
                        <input type="text" class="form-control" name="edit_name" id="edit_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Change Logo</label>
                        <input type="file" class="form-control" name="edit_category_logo" accept="image/*">
                        <div class="form-text">Leave empty to keep current logo</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="edit_category" class="btn btn-primary">Update Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function setEditData(id, name, logoPath) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_name').value = name;
        document.getElementById('old_logo_path').value = logoPath;
        
        const preview = document.getElementById('current_logo_preview');
        if (logoPath) {
            preview.src = '../../' + logoPath;
            preview.style.display = 'inline-block';
        } else {
            preview.style.display = 'none';
        }
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
