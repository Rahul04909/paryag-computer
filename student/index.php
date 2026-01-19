<?php
session_start();
// Placeholder: Checking if student is logged in. 
// if (!isset($_SESSION['student_logged_in'])) { header("Location: login.php"); exit; }

include 'header.php';
?>

<div class="d-flex">
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content w-100">
        <header class="top-header">
            <h4 class="fw-bold">Student Dashboard</h4>
            <div class="d-flex align-items-center">
                <div class="dropdown">
                    <button class="btn btn-white border-0 p-0" type="button" data-bs-toggle="dropdown">
                        <img src="https://ui-avatars.com/api/?name=Student+User&background=random" class="rounded-circle" width="40" height="40">
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg">
                        <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <div class="container-fluid p-4">
            <!-- Welcome Section -->
            <div class="alert alert-primary border-0 shadow-sm rounded-4 d-flex align-items-center p-4 mb-4" role="alert">
                <div class="me-3">
                    <div class="icon-box bg-white text-primary rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fa-solid fa-graduation-cap fa-2x"></i>
                    </div>
                </div>
                <div>
                    <h4 class="alert-heading fw-bold mb-1">Welcome back, Student!</h4>
                    <p class="mb-0 text-primary-emphasis">Ready to improve your typing speed today?</p>
                </div>
            </div>

            <!-- Stats Row -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 card-animate h-100">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="text-muted text-uppercase fw-bold mb-0" style="font-size: 0.8rem; letter-spacing: 1px;">Tests Taken</h6>
                                <div class="icon-shape bg-primary bg-opacity-10 text-primary rounded-circle p-2">
                                    <i class="fa-solid fa-list-check"></i>
                                </div>
                            </div>
                            <h2 class="display-6 fw-bold mb-0">0</h2>
                            <small class="text-success"><i class="fa-solid fa-arrow-up"></i> Keep going!</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 card-animate h-100">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="text-muted text-uppercase fw-bold mb-0" style="font-size: 0.8rem; letter-spacing: 1px;">Avg Speed</h6>
                                <div class="icon-shape bg-success bg-opacity-10 text-success rounded-circle p-2">
                                    <i class="fa-solid fa-gauge-high"></i>
                                </div>
                            </div>
                            <h2 class="display-6 fw-bold mb-0">0 <span class="fs-6 text-muted">WPM</span></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 card-animate h-100">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="text-muted text-uppercase fw-bold mb-0" style="font-size: 0.8rem; letter-spacing: 1px;">Accuracy</h6>
                                <div class="icon-shape bg-info bg-opacity-10 text-info rounded-circle p-2">
                                    <i class="fa-solid fa-bullseye"></i>
                                </div>
                            </div>
                            <h2 class="display-6 fw-bold mb-0">0%</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                     <div class="card border-0 shadow-sm rounded-4 card-animate h-100">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="text-muted text-uppercase fw-bold mb-0" style="font-size: 0.8rem; letter-spacing: 1px;">Pending Fees</h6>
                                <div class="icon-shape bg-warning bg-opacity-10 text-warning rounded-circle p-2">
                                    <i class="fa-solid fa-indian-rupee-sign"></i>
                                </div>
                            </div>
                            <h2 class="display-6 fw-bold mb-0">0</h2>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
             <div class="row">
                 <div class="col-md-6">
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-header bg-white border-0 pt-4 px-4">
                            <h5 class="mb-0 fw-bold">Recent Activity</h5>
                        </div>
                        <div class="card-body p-4">
                            <p class="text-muted text-center py-5">No recent activity found.</p>
                        </div>
                    </div>
                 </div>
                  <div class="col-md-6">
                    <div class="card shadow-sm border-0 rounded-4">
                         <div class="card-body p-4 text-center">
                            <h5 class="fw-bold mb-3">Start Practice</h5>
                            <p class="text-muted mb-4">Improve your typing skills by taking a practice test now.</p>
                            <a href="practice-tests.php" class="btn btn-primary px-4 rounded-pill">Take a Test</a>
                         </div>
                    </div>
                 </div>
             </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo $assets_path; ?>js/main.js"></script>
</body>
</html>
