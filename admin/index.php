<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
include 'header.php'; ?>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <!-- Top Header -->
        <header class="top-header">
            <button class="toggle-sidebar-btn">
                <i class="fa-solid fa-bars"></i>
            </button>
            
            <div class="user-profile dropdown">
                <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle text-dark" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="https://via.placeholder.com/40" alt="admin" width="40" height="40" class="rounded-circle me-2">
                    <strong>Admin</strong>
                </a>
                <ul class="dropdown-menu dropdown-menu-end text-small shadow" aria-labelledby="dropdownUser1">
                    <li><a class="dropdown-item" href="#">Profile</a></li>
                    <li><a class="dropdown-item" href="#">Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#">Sign out</a></li>
                </ul>
            </div>
        </header>

        <!-- Main Content Inner -->
        <div class="container-fluid p-4">
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="mb-0 text-dark fw-bold">Dashboard</h2>
                    <p class="text-muted">Welcome back, Admin</p>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row g-4 mb-4">
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="card card-stat bg-gradient-primary text-white h-100 p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase mb-2" style="opacity: 0.8;">Total Users</h6>
                                <h3 class="mb-0 fw-bold">1,250</h3>
                            </div>
                            <div class="fs-1" style="opacity: 0.3;"><i class="fa-solid fa-users"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="card card-stat bg-gradient-success text-white h-100 p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase mb-2" style="opacity: 0.8;">Active Tests</h6>
                                <h3 class="mb-0 fw-bold">45</h3>
                            </div>
                            <div class="fs-1" style="opacity: 0.3;"><i class="fa-solid fa-keyboard"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="card card-stat bg-gradient-warning text-white h-100 p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase mb-2" style="opacity: 0.8;">Tests Taken</h6>
                                <h3 class="mb-0 fw-bold">8,932</h3>
                            </div>
                            <div class="fs-1" style="opacity: 0.3;"><i class="fa-solid fa-chart-line"></i></div>
                        </div>
                    </div>
                </div>
                <!-- Add more cards as needed -->
            </div>

            <!-- Recent Activity / Table Section Placeholder -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm border-0" style="border-radius: 15px;">
                        <div class="card-body">
                            <h5 class="card-title fw-bold mb-4">Recent Activity</h5>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>User</th>
                                            <th>Test Name</th>
                                            <th>Date</th>
                                            <th>Score</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>John Doe</td>
                                            <td>Speed Test #1</td>
                                            <td>Oct 24, 2023</td>
                                            <td>95 WPM</td>
                                            <td><span class="badge bg-success rounded-pill">Passed</span></td>
                                        </tr>
                                        <tr>
                                            <td>Jane Smith</td>
                                            <td>Accuracy Drill</td>
                                            <td>Oct 23, 2023</td>
                                            <td>88 WPM</td>
                                            <td><span class="badge bg-primary rounded-pill">Completed</span></td>
                                        </tr>
                                        <tr>
                                            <td>Robert Johnson</td>
                                            <td>Typing Basics</td>
                                            <td>Oct 22, 2023</td>
                                            <td>45 WPM</td>
                                            <td><span class="badge bg-warning text-dark rounded-pill">Pending</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Footer -->
        <footer>
            &copy; 2023 Typing Master Admin. All rights reserved.
        </footer>
    </div>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
</body>
</html>
