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

// Handle Delete
if (isset($_POST['delete_test'])) {
    $test_id = $_POST['test_id'];
    try {
        // Get audio file path first
        $stmt = $conn->prepare("SELECT audio_file FROM steno_tests WHERE id = :id");
        $stmt->execute(['id' => $test_id]);
        $test = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($test) {
            // Delete record
            $delStmt = $conn->prepare("DELETE FROM steno_tests WHERE id = :id");
            $delStmt->execute(['id' => $test_id]);

            // Delete file
            if (!empty($test['audio_file']) && file_exists('../../' . $test['audio_file'])) {
                unlink('../../' . $test['audio_file']);
            }

            $message = "Test deleted successfully.";
            $alert_type = "success";
        }
    } catch (PDOException $e) {
        $message = "Error deleting test: " . $e->getMessage();
        $alert_type = "danger";
    }
}

// Fetch Categories and Languages for filters
try {
    $cats = $conn->query("SELECT * FROM steno_categories ORDER BY category_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $langs = $conn->query("SELECT * FROM typing_languages ORDER BY language_name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $cats = [];
    $langs = [];
}

// Build Query
$query = "SELECT st.*, sc.category_name, tl.language_name 
          FROM steno_tests st 
          LEFT JOIN steno_categories sc ON st.category_id = sc.id 
          LEFT JOIN typing_languages tl ON st.language_id = tl.id 
          WHERE 1=1";
$params = [];

if (isset($_GET['category_id']) && !empty($_GET['category_id'])) {
    $query .= " AND st.category_id = :cat_id";
    $params['cat_id'] = $_GET['category_id'];
}

if (isset($_GET['language_id']) && !empty($_GET['language_id'])) {
    $query .= " AND st.language_id = :lang_id";
    $params['lang_id'] = $_GET['language_id'];
}

$query .= " ORDER BY st.created_at DESC";

try {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $tests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $tests = [];
    $message = "Error fetching tests: " . $e->getMessage();
    $alert_type = "danger";
}

include '../header.php';
?>

<div class="d-flex">
    <?php include '../sidebar.php'; ?>
    
    <div class="main-content w-100">
        <header class="top-header">
            <h4 class="fw-bold">Manage Steno Tests</h4>
        </header>

        <div class="container-fluid p-4">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $alert_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0 fw-bold text-primary">Steno Tests List</h5>
                    <a href="create-steno-test.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus me-2"></i>Create New Test</a>
                </div>
                
                <div class="card-body p-4">
                    <!-- Filters -->
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-4">
                            <select name="category_id" class="form-select">
                                <option value="">All Categories</option>
                                <?php foreach ($cats as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo isset($_GET['category_id']) && $_GET['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select name="language_id" class="form-select">
                                <option value="">All Languages</option>
                                <?php foreach ($langs as $lang): ?>
                                    <option value="<?php echo $lang['id']; ?>" <?php echo isset($_GET['language_id']) && $_GET['language_id'] == $lang['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($lang['language_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-secondary w-100"><i class="fa-solid fa-filter me-2"></i>Filter</button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Language</th>
                                    <th>Duration</th>
                                    <th>Audio</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($tests) > 0): ?>
                                    <?php foreach ($tests as $index => $test): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td class="fw-bold"><?php echo htmlspecialchars($test['test_title']); ?></td>
                                            <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($test['category_name'] ?? 'N/A'); ?></span></td>
                                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($test['language_name'] ?? 'N/A'); ?></span></td>
                                            <td><?php echo htmlspecialchars($test['duration_minutes']); ?> min</td>
                                            <td>
                                                <audio controls class="w-100" style="min-width: 150px; height: 30px;">
                                                    <source src="../../<?php echo htmlspecialchars($test['audio_file']); ?>" type="audio/mpeg">
                                                </audio>
                                            </td>
                                            <td class="small text-muted"><?php echo date('M d, Y', strtotime($test['created_at'])); ?></td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="edit.php?id=<?php echo $test['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-edit"></i></a>
                                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this test?');">
                                                        <input type="hidden" name="test_id" value="<?php echo $test['id']; ?>">
                                                        <button type="submit" name="delete_test" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">No steno tests found.</td>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
