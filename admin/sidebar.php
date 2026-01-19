<div class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand">
            <img src="../../assets/images/rgcsm-logo.png" alt="RGCSM Logo" style="max-height: 50px; width: auto;" class="img-fluid">
        </div>
        <div class="sidebar-brand-icon" style="display: none;">
             <img src="../../assets/images/rgcsm-logo.png" alt="RGCSM Logo" style="max-height: 30px; width: auto;" class="img-fluid">
        </div>
    </div>
    
    <ul class="sidebar-menu">
        <li>
            <a href="../../admin/index.php" class="active">
                <i class="fa-solid fa-gauge-high"></i>
                <span>Dashboard</span>
            </a>
        </li>
        
        <li class="sidebar-dropdown">
            <a href="#">
                <i class="fa-solid fa-users"></i>
                <span>Users</span>
            </a>
            <div class="sidebar-submenu">
                <ul>
                    <li><a href="#">All Users</a></li>
                    <li><a href="#">Add User</a></li>
                </ul>
            </div>
        </li>

        <li class="sidebar-dropdown">
            <a href="#">
                <i class="fa-solid fa-file-lines"></i>
                <span>Tests</span>
            </a>
            <div class="sidebar-submenu">
                <ul>
                    <li><a href="#">All Tests</a></li>
                    <li><a href="#">Create Test</a></li>
                    <li><a href="../../admin/steno-test/create-steno-category.php">Categories</a></li>
                    <li><a href="../../admin/steno-test/create-steno-test.php">Create Steno Test</a></li>
                </ul>
            </div>
        </li>

        <li>
            <a href="#">
                <i class="fa-solid fa-square-poll-vertical"></i>
                <span>Results</span>
            </a>
        </li>

        <li class="sidebar-dropdown">
            <a href="#">
                <i class="fa-solid fa-gear"></i>
                <span>Settings</span>
            </a>
            <div class="sidebar-submenu">
                <ul>
                    <li><a href="../../admin/settings/smtp-config.php">Smtp Settings</a></li>
                    <li><a href="#">Profile</a></li>
                    <li><a href="#">Security</a></li>
                </ul>
            </div>
        </li>
        
        <li>
            <a href="logout.php">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</div>
