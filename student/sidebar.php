<?php
// Determine current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar -->
<div class="sidebar offcanvas-md offcanvas-start" id="sidebarMenu">
    <div class="sidebar-header">
        <div class="sidebar-brand">
            <!-- Ensure path to logo is correct relative to student/ folder -->
            <img src="<?php echo isset($base_url) ? $base_url : ''; ?>../assets/images/paryag-computer-logo.jpeg" alt="RGCSM Logo" style="max-height: 50px; width: auto;" class="img-fluid">
        </div>
    </div>
    
    <div class="sidebar-menu">
        <ul class="list-unstyled">
            <li class="menu-header">Main</li>
            <li>
                <a href="<?php echo isset($base_url) ? $base_url : ''; ?>index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-gauge-high"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li class="menu-header">Learning</li>
            <li>
                <a href="<?php echo isset($base_url) ? $base_url : ''; ?>typing-lessons.php" class="<?php echo ($current_page == 'typing-lessons.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-keyboard"></i>
                    <span>Typing Lessons</span>
                </a>
            </li>
            <li>
                <a href="<?php echo isset($base_url) ? $base_url : ''; ?>practice-tests.php" class="<?php echo ($current_page == 'practice-tests.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-stopwatch"></i>
                    <span>Practice Test</span>
                </a>
            </li>
             <li>
                <a href="<?php echo isset($base_url) ? $base_url : ''; ?>typing-classes.php" class="<?php echo ($current_page == 'typing-classes.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-video"></i>
                    <span>Typing Classes</span>
                </a>
            </li>

            <li class="menu-header">Account</li>
            <li>
                <a href="<?php echo isset($base_url) ? $base_url : ''; ?>profile.php" class="<?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-user-circle"></i>
                    <span>My Profile</span>
                </a>
            </li>
            <li>
                <a href="<?php echo isset($base_url) ? $base_url : ''; ?>fees.php" class="<?php echo ($current_page == 'fees.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-receipt"></i>
                    <span>Manage Fees</span>
                </a>
            </li>

            <li class="mt-4">
                <a href="<?php echo isset($base_url) ? $base_url : ''; ?>logout.php" class="text-danger">
                    <i class="fa-solid fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>
</div>
