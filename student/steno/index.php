<?php
session_start();
/* if (!isset($_SESSION['student_logged_in']) || $_SESSION['student_logged_in'] !== true) {
    header("Location: ../login.php");
    exit;
} */

require_once '../../database/db_config.php';

// Pagination Setup
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Path Definitions for Sidebar/Header
$assets_path = '../../assets/'; // Pointing to root assets if needed, or if using admin assets: '../../admin/assets/'
// Actually, looking at other student files, they usually use '../admin/assets/' (from student/). 
// From student/steno/, it should be '../../admin/assets/'.
// Let's check how sidebar uses it.
// Sidebar uses it for logo: $assets_path . '../assets/images/...' -> this logic in sidebar is weird: $assets_path . '../assets/images'
// If $assets_path is '../../admin/assets/', then image src = '../../admin/assets/../assets/images/...' = '../../admin/images/...' -> Wrong.
// The sidebar image path was: `../assets/images/paryag-computer-logo.jpeg`. 
// If specific file is `student/index.php`, path is `../assets/...` (up one from student to root).
// If file is `student/steno/index.php`, path should be `../../assets/...`.
// So $assets_path is NOT helpful for the image if it points to 'admin/assets'.
// Let's look at sidebar change again.
// I changed sidebar image to: `src="<?php echo isset($assets_path) ? $assets_path : '../'; ?>../assets/images/...`
// If I leave $assets_path unset in student/index.php, it uses '../'. Result: `../../assets/images...` -> Correct for student/index.php? No. 
// student/index.php -> `../` + `../assets` = `../../assets`. Wait. 
// Original sidebar had `../assets/images/...`. From `student/` this goes to `root/assets`. Correct.
// So default prefix was empty or `./` effectively? No, strictly `../`.
// If I am in `student/steno/`, I need `../../`.
// So I should define a specific variable for "Root Path" or similar.
// But I reused `$assets_path`. 
// Let's define `$base_url` as `../` in `steno/index.php`.
// And for the logo...
// In `steno/index.php`, if I set `$assets_path = '../../admin/assets/';` 
// Then sidebar says: `../../admin/assets/` + `../assets/images/...` -> `../../admin/assets/../assets` -> `../../admin/assets`? No.
// `path/to/dir/../other` = `path/to/other`.
// `../../admin/assets/../assets` = `../../admin/assets` (parent of assets is admin) -> `../../admin/assets` ? No.
// `admin/assets` parent is `admin`. So `admin/assets/../` is `admin/`. 
// `../../admin/assets/../assets` = `../../admin/assets`. This is confusing. 
// Simpler: I should just pass a variable `logo_path_prefix` or similar.
// Or just reuse `$base_url`.
// Sidebar: `<?php echo isset($base_url) ? $base_url : ''; ?>../assets/images/...`
// In `student/index.php`, `$base_url` is empty. Link: `../assets`. Correct.
// In `student/steno/index.php`, `$base_url` is `../`. Link: `../../assets`. Correct.
// So I should fix Sidebar to use `$base_url` for the image too, not `$assets_path`.

$base_url = '../';
$assets_path = '../../admin/assets/'; // For CSS in header.php


// Filters
$category_id = isset($_GET['category_id']) ? $_GET['category_id'] : 'all';
$language_id = isset($_GET['language_id']) ? $_GET['language_id'] : '';

// Fetch Categories
try {
    $catStmt = $conn->query("SELECT * FROM steno_categories ORDER BY category_name ASC");
    $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $categories = []; }

// Fetch Languages
try {
    $langStmt = $conn->query("SELECT * FROM typing_languages ORDER BY language_name ASC");
    $languages = $langStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $languages = []; }

// Build Query for Tests
$sql = "SELECT st.*, sc.category_name, tl.language_name 
        FROM steno_tests st 
        LEFT JOIN steno_categories sc ON st.category_id = sc.id 
        LEFT JOIN typing_languages tl ON st.language_id = tl.id 
        WHERE 1=1";
$params = [];

if ($category_id !== 'all') {
    $sql .= " AND st.category_id = :cat_id";
    $params['cat_id'] = $category_id;
} else {
    // If 'all' is selected, maybe we show all. 
    // Usually tabs imply filtering, but 'All' tab is common.
}

if (!empty($language_id)) {
    $sql .= " AND st.language_id = :lang_id";
    $params['lang_id'] = $language_id;
}

// Count Total for Pagination
$countSql = str_replace("SELECT st.*, sc.category_name, tl.language_name", "SELECT COUNT(*)", $sql);
$countStmt = $conn->prepare($countSql);
$countStmt->execute($params);
$total_records = $countStmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch Data
$sql .= " ORDER BY st.created_at DESC LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue(':' . $key, $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$tests = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../header.php';
?>

<style>
    .category-scroll-container {
        overflow-x: auto;
        white-space: nowrap;
        padding-bottom: 10px;
        -ms-overflow-style: none;  /* IE and Edge */
        scrollbar-width: none;  /* Firefox */
    }
    .category-scroll-container::-webkit-scrollbar {
        display: none;
    }
    
    .category-tab {
        display: inline-block;
        min-width: 120px;
        text-align: center;
        margin-right: 15px;
        cursor: pointer;
        opacity: 0.7;
        transition: all 0.3s;
        border: 2px solid transparent;
        border-radius: 12px;
        padding: 10px;
        background: #fff;
    }
    
    .category-tab.active, .category-tab:hover {
        opacity: 1;
        border-color: #0d6efd;
        background: #f0f7ff;
        transform: translateY(-2px);
    }

    .category-logo {
        width: 50px;
        height: 50px;
        object-fit: contain;
        margin-bottom: 8px;
        display: block;
        margin-left: auto;
        margin-right: auto;
    }

    .test-card {
        transition: all 0.3s;
        border: 1px solid #eee;
    }
    .test-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        border-color: #cce5ff;
    }
</style>

<div class="d-flex">
    <?php include '../sidebar.php'; ?>
    
    <div class="main-content w-100">
        <header class="top-header">
            <h4 class="fw-bold">Steno Practice Tests</h4>
        </header>

        <div class="container-fluid p-4">
            
            <!-- Category Tabs -->
            <div class="mb-4">
                <h6 class="text-muted fw-bold mb-3">SELECT CATEGORY</h6>
                <div class="category-scroll-container">
                    <!-- All Category Tab -->
                    <a href="?category_id=all&language_id=<?php echo $language_id; ?>" class="text-decoration-none text-dark">
                        <div class="category-tab <?php echo $category_id == 'all' ? 'active' : ''; ?>">
                            <div class="d-flex align-items-center justify-content-center bg-light rounded-circle mb-2 mx-auto" style="width: 50px; height: 50px;">
                                <i class="fa-solid fa-layer-group fa-lg text-secondary"></i>
                            </div>
                            <span class="fw-bold small d-block">All</span>
                        </div>
                    </a>

                    <?php foreach ($categories as $cat): ?>
                        <a href="?category_id=<?php echo $cat['id']; ?>&language_id=<?php echo $language_id; ?>" class="text-decoration-none text-dark">
                            <div class="category-tab <?php echo $category_id == $cat['id'] ? 'active' : ''; ?>">
                                <?php if (!empty($cat['category_logo'])): ?>
                                    <img src="../../<?php echo htmlspecialchars($cat['category_logo']); ?>" alt="Logo" class="category-logo">
                                <?php else: ?>
                                    <div class="d-flex align-items-center justify-content-center bg-light rounded-circle mb-2 mx-auto" style="width: 50px; height: 50px;">
                                        <i class="fa-solid fa-keyboard fa-lg text-secondary"></i>
                                    </div>
                                <?php endif; ?>
                                <span class="fw-bold small d-block"><?php echo htmlspecialchars($cat['category_name']); ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Filters & List -->
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0 fw-bold text-primary">Available Tests</h5>
                    
                    <form method="GET" class="d-flex gap-2">
                        <input type="hidden" name="category_id" value="<?php echo htmlspecialchars($category_id); ?>">
                        <select name="language_id" class="form-select form-select-sm" style="min-width: 150px;" onchange="this.form.submit()">
                            <option value="">All Languages</option>
                            <?php foreach ($languages as $lang): ?>
                                <option value="<?php echo $lang['id']; ?>" <?php echo $language_id == $lang['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($lang['language_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
                
                <div class="card-body p-4">
                    <?php if (count($tests) > 0): ?>
                        <div class="row g-3">
                            <?php foreach ($tests as $test): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="card test-card h-100 p-3 rounded-4">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="badge bg-light text-primary border border-primary-subtle">
                                                <?php echo htmlspecialchars($test['language_name'] ?? 'Unknown'); ?>
                                            </span>
                                            <span class="badge bg-secondary">
                                                <i class="fa-solid fa-clock me-1"></i> <?php echo htmlspecialchars($test['duration_minutes']); ?> min
                                            </span>
                                        </div>
                                        
                                        <h5 class="fw-bold mb-2 flex-grow-1"><?php echo htmlspecialchars($test['test_title']); ?></h5>
                                        <p class="text-muted small mb-3">Category: <?php echo htmlspecialchars($cat['category_name'] ?? 'General'); ?></p>
                                        
                                        <div class="d-grid">
                                            <a href="../take-steno-test.php?id=<?php echo $test['id']; ?>" class="btn btn-primary rounded-pill">
                                                <i class="fa-solid fa-play me-2"></i> Start Test
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page-1; ?>&category_id=<?php echo $category_id; ?>&language_id=<?php echo $language_id; ?>">Previous</a>
                                    </li>
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&category_id=<?php echo $category_id; ?>&language_id=<?php echo $language_id; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page+1; ?>&category_id=<?php echo $category_id; ?>&language_id=<?php echo $language_id; ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="text-center py-5">
                            <img src="../../assets/images/no-data.svg" alt="No Data" class="mb-3" style="max-width: 150px; opacity: 0.5;">
                            <h6 class="text-muted">No steno tests found for the selected category/language.</h6>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
