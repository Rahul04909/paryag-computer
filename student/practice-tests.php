<?php
session_start();
// if (!isset($_SESSION['student_logged_in'])) { header("Location: login.php"); exit; }

require_once '../database/db_config.php';

// Pagination Setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Filter Setup
$where_clauses = ["t.test_type = 'Practice Test'"];
$params = [];

if (isset($_GET['language_id']) && !empty($_GET['language_id'])) {
    $where_clauses[] = "t.language_id = :language_id";
    $params['language_id'] = $_GET['language_id'];
}
if (isset($_GET['level_id']) && !empty($_GET['level_id'])) {
    $where_clauses[] = "t.level_id = :level_id";
    $params['level_id'] = $_GET['level_id'];
}

$where_sql = implode(" AND ", $where_clauses);

// Count Total for Pagination
$count_sql = "SELECT COUNT(*) FROM typing_tests t WHERE $where_sql";
$stmt = $conn->prepare($count_sql);
foreach ($params as $key => $val) {
    $stmt->bindValue(":$key", $val);
}
$stmt->execute();
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch Data
$query = "SELECT t.*, l.language_name, lvl.level_name 
          FROM typing_tests t 
          LEFT JOIN typing_languages l ON t.language_id = l.id 
          LEFT JOIN typing_levels lvl ON t.level_id = lvl.id
          WHERE $where_sql 
          ORDER BY t.created_at DESC 
          LIMIT $start, $limit";

$stmt = $conn->prepare($query);
foreach ($params as $key => $val) {
    $stmt->bindValue(":$key", $val);
}
$stmt->execute();
$tests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Filter Options
try {
    $languages_opt = $conn->query("SELECT * FROM typing_languages ORDER BY language_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $levels_opt = $conn->query("SELECT * FROM typing_levels ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

include 'header.php';
?>

<div class="d-flex">
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content w-100">
        <header class="top-header">
            <h4 class="fw-bold">Practice Tests</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Practice Tests</li>
                </ol>
            </nav>
        </header>

        <div class="container-fluid p-4">
            
            <!-- Filters -->
            <div class="card shadow-sm border-0 rounded-4 mb-4">
                <div class="card-body p-4">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small text-muted">Filter by Language</label>
                            <select class="form-select" name="language_id">
                                <option value="">All Languages</option>
                                <?php foreach($languages_opt as $l): ?>
                                    <option value="<?php echo $l['id']; ?>" <?php echo (isset($_GET['language_id']) && $_GET['language_id'] == $l['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($l['language_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted">Filter by Difficulty</label>
                            <select class="form-select" name="level_id">
                                <option value="">All Levels</option>
                                <?php foreach($levels_opt as $lv): ?>
                                    <option value="<?php echo $lv['id']; ?>" <?php echo (isset($_GET['level_id']) && $_GET['level_id'] == $lv['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($lv['level_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-filter me-2"></i> Apply Filters</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tests Grid -->
            <div class="row g-4">
                <?php if (!empty($tests)): ?>
                    <?php foreach ($tests as $test): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card border-0 shadow-sm h-100 rounded-4 card-hover">
                                <div class="card-body p-4 d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-3 py-2"><?php echo htmlspecialchars($test['language_name']); ?></span>
                                        <?php 
                                        $level_class = 'secondary';
                                        if ($test['level_name'] == 'Easy') $level_class = 'success';
                                        if ($test['level_name'] == 'Medium') $level_class = 'warning';
                                        if ($test['level_name'] == 'Hard') $level_class = 'danger';
                                        ?>
                                        <span class="badge bg-<?php echo $level_class; ?> bg-opacity-10 text-<?php echo $level_class; ?> rounded-pill px-3 py-2"><?php echo htmlspecialchars($test['level_name']); ?></span>
                                    </div>
                                    
                                    <h5 class="fw-bold mb-2 text-dark"><?php echo htmlspecialchars($test['test_title']); ?></h5>
                                    
                                    <div class="mt-auto pt-3">
                                        <div class="d-flex justify-content-between align-items-center mb-3 text-muted small">
                                            <span><i class="fa-regular fa-clock me-1"></i> <?php echo $test['duration_minutes']; ?> Min</span>
                                        </div>
                                        <a href="take-test.php?id=<?php echo $test['id']; ?>" class="btn btn-primary w-100 rounded-pill">Start Test <i class="fa-solid fa-arrow-right ms-1"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="fa-solid fa-keyboard fa-3x text-muted mb-3 opacity-50"></i>
                            <h5 class="text-muted">No practice tests found matching your filters.</h5>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
             <?php if ($total_pages > 1): ?>
            <div class="d-flex justify-content-center mt-5">
                <nav>
                    <ul class="pagination">
                         <li class="page-item <?php if($page <= 1) echo 'disabled'; ?>">
                            <a class="page-link" href="?page=<?php echo $page-1; ?>&language_id=<?php echo isset($_GET['language_id'])?$_GET['language_id']:''; ?>&level_id=<?php echo isset($_GET['level_id'])?$_GET['level_id']:''; ?>">Previous</a>
                        </li>
                        
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php if($page == $i) echo 'active'; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&language_id=<?php echo isset($_GET['language_id'])?$_GET['language_id']:''; ?>&level_id=<?php echo isset($_GET['level_id'])?$_GET['level_id']:''; ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php if($page >= $total_pages) echo 'disabled'; ?>">
                            <a class="page-link" href="?page=<?php echo $page+1; ?>&language_id=<?php echo isset($_GET['language_id'])?$_GET['language_id']:''; ?>&level_id=<?php echo isset($_GET['level_id'])?$_GET['level_id']:''; ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo $assets_path; ?>js/main.js"></script>
</body>
</html>
