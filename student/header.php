<?php
if (!isset($assets_path)) {
    // Default relative path if accessed directly from student/ root
    $assets_path = '../admin/assets/'; 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | RGCSM</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS (Reusing Admin Premium Style for Consistency) -->
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/style.css">
    
    <!-- CKEditor Script for later use -->
    <script>
        // Define global assets path for JS if needed
        const ASSETS_PATH = "<?php echo $assets_path; ?>";
    </script>
</head>
<body class="bg-light">

<!-- Mobile Toggle Button (Visible only on mobile) -->
<button class="btn btn-primary d-md-none position-fixed top-0 start-0 m-3 z-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
    <i class="fa-solid fa-bars"></i>
</button>
