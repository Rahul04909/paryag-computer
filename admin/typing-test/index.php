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

// Handle Delete Request
if (isset($_POST['delete_test'])) {
    $test_id = $_POST['test_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM typing_tests WHERE id = :id");
        $stmt->execute(['id' => $test_id]);
        $message = "Test deleted successfully!";
        $alert_type = "success";
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $alert_type = "danger";
    }
}

// Build Query with Filters
$where_clauses = [];
$params = [];

if (isset($_GET['test_type']) && !empty($_GET['test_type'])) {
    $where_clauses[] = "t.test_type = :test_type";
    $params['test_type'] = $_GET['test_type'];
}
if (isset($_GET['language_id']) && !empty($_GET['language_id'])) {
    $where_clauses[] = "t.language_id = :language_id";
    $params['language_id'] = $_GET['language_id'];
}
if (isset($_GET['level_id']) && !empty($_GET['level_id'])) {
    $where_clauses[] = "t.level_id = :level_id";
    $params['level_id'] = $_GET['level_id'];
}

$query = "SELECT t.*, l.language_name, lvl.level_name 
          FROM typing_tests t 
          LEFT JOIN typing_languages l ON t.language_id = l.id 
          LEFT JOIN typing_levels lvl ON t.level_id = lvl.id";

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

$query .= " ORDER BY t.created_at DESC";

try {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $tests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $tests = []; // Handle error gracefully
}

// Fetch Filter Options
try {
    $languages_opt = $conn->query("SELECT * FROM typing_languages ORDER BY language_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $levels_opt = $conn->query("SELECT * FROM typing_levels ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

include '../../admin/header.php';
?>

<div class="d-flex">
    <?php include '../../admin/sidebar.php'; ?>
    
    <div class="main-content w-100">
        <header class="top-header">
            <h4 class="fw-bold">Manage Typing Tests</h4>
            <a href="create-test.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus me-2"></i> Create New Test</a>
        </header>

        <div class="container-fluid p-4">
             <?php if($message): ?>
                <div class="alert alert-<?php echo $alert_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="card shadow-sm border-0 rounded-4 mb-4">
                <div class="card-body p-4">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Filter by Type</label>
                            <select class="form-select" name="test_type">
                                <option value="">All Types</option>
                                <option value="Lesson" <?php echo (isset($_GET['test_type']) && $_GET['test_type'] == 'Lesson') ? 'selected' : ''; ?>>Lesson</option>
                                <option value="Practice Test" <?php echo (isset($_GET['test_type']) && $_GET['test_type'] == 'Practice Test') ? 'selected' : ''; ?>>Practice Test</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Filter by Language</label>
                            <select class="form-select" name="language_id">
                                <option value="">All Languages</option>
                                <?php foreach($languages_opt as $l): ?>
                                    <option value="<?php echo $l['id']; ?>" <?php echo (isset($_GET['language_id']) && $_GET['language_id'] == $l['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($l['language_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Filter by Level</label>
                            <select class="form-select" name="level_id">
                                <option value="">All Levels</option>
                                <?php foreach($levels_opt as $lv): ?>
                                    <option value="<?php echo $lv['id']; ?>" <?php echo (isset($_GET['level_id']) && $_GET['level_id'] == $lv['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($lv['level_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-dark w-100"><i class="fa-solid fa-filter me-2"></i> Apply Filters</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Data Table -->
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="px-4 py-3">#</th>
                                    <th class="py-3">Title</th>
                                    <th class="py-3">Type</th>
                                    <th class="py-3">Language</th>
                                    <th class="py-3">Level</th>
                                    <th class="py-3">Duration</th>
                                    <th class="py-3 text-end px-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($tests)): ?>
                                    <?php foreach ($tests as $index => $test): ?>
                                    <tr>
                                        <td class="px-4"><?php echo $index + 1; ?></td>
                                        <td>
                                            <span class="fw-bold text-dark"><?php echo htmlspecialchars($test['test_title'] ?? 'Untitled'); ?></span>
                                            <small class="d-block text-muted">ID: <?php echo $test['id']; ?></small>
                                        </td>
                                        <td>
                                            <?php if($test['test_type'] == 'Lesson'): ?>
                                                <span class="badge bg-info bg-opacity-10 text-info">Lesson</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning bg-opacity-10 text-warning">Practice Test</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($test['language_name']); ?></td>
                                        <td>
                                            <?php 
                                            $level_color = 'secondary';
                                            if ($test['level_name'] == 'Easy') $level_color = 'success';
                                            if ($test['level_name'] == 'Medium') $level_color = 'warning';
                                            if ($test['level_name'] == 'Hard') $level_color = 'danger';
                                            ?>
                                            <span class="badge bg-<?php echo $level_color; ?>"><?php echo htmlspecialchars($test['level_name']); ?></span>
                                        </td>
                                        <td><?php echo $test['duration_minutes']; ?> min</td>
                                        <td class="text-end px-4">
                                            <a href="edit-test.php?id=<?php echo $test['id']; ?>" class="btn btn-sm btn-outline-primary me-2"><i class="fa-solid fa-pen-to-square"></i></a>
                                            <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this test?');">
                                                <input type="hidden" name="test_id" value="<?php echo $test['id']; ?>">
                                                <button type="submit" name="delete_test" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <i class="fa-solid fa-folder-open fa-2x mb-3 d-block"></i>
                                            No typing tests found matching your criteria.
                                        </td>
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
<script src="../../admin/assets/js/main.js"></script>
</body>
</html>
