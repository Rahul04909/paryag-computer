<?php
session_start();
require_once '../database/db_config.php';

// Redirect if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username_email = trim($_POST['username_email']);
    $password = $_POST['password'];

    if (empty($username_email) || empty($password)) {
        $error = "Please enter both username/email and password.";
    } else {
        try {
            // Check by username or email
            $stmt = $conn->prepare("SELECT id, username, password FROM admins WHERE username = :input OR email = :input");
            $stmt->bindParam(':input', $username_email);
            $stmt->execute();

            if ($stmt->rowCount() == 1) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (password_verify($password, $row['password'])) {
                    // Password is correct, start session
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $row['id'];
                    $_SESSION['admin_username'] = $row['username'];
                    
                    header("Location: index.php");
                    exit;
                } else {
                    $error = "Invalid password.";
                }
            } else {
                $error = "No account found with that username or email.";
            }
        } catch(PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Typing Master</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background-color: #f4f6f9;
        }
        .login-container {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }
        .login-image {
            flex: 1;
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%), url('https://images.unsplash.com/photo-1497215728101-856f4ea42174?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            background-blend-mode: overlay;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            padding: 40px;
            text-align: center;
        }
        .login-form-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            background: white;
            padding: 40px;
        }
        .login-form-wrapper {
            width: 100%;
            max-width: 450px;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-logo img {
            height: 80px;
        }
        .form-floating {
            margin-bottom: 20px;
        }
        .password-field {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            z-index: 10;
        }
        .btn-login {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            border: none;
            color: white;
            padding: 12px;
            font-weight: 600;
            width: 100%;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(37, 117, 252, 0.4);
        }
        @media (max-width: 768px) {
            .login-image {
                display: none;
            }
        }
    </style>
</head>
<body>

<div class="login-container">
    <!-- Left Side: Image -->
    <div class="login-image">
        <h1 class="fw-bold display-4 mb-3">Welcome Back!</h1>
        <p class="fs-5 opacity-75">Connect with your dashboard and manage the Typing Master effectively.</p>
    </div>

    <!-- Right Side: Form -->
    <div class="login-form-container">
        <div class="login-form-wrapper">
            <div class="login-logo">
                <img src="../assets/images/rgcsm-logo.png" alt="Logo">
                <h4 class="mt-3 fw-bold text-dark">Admin Login</h4>
                <p class="text-muted small">Enter your credentials to access your account</p>
            </div>

            <?php if(!empty($error)): ?>
                <div class="alert alert-danger text-center" role="alert">
                    <i class="fa-solid fa-circle-exclamation me-2"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-floating">
                    <input type="text" class="form-control" id="username_email" name="username_email" placeholder="Username or Email" required>
                    <label for="username_email">Username or Email</label>
                </div>
                
                <div class="form-floating password-field">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                    <label for="password">Password</label>
                    <i class="fa-solid fa-eye toggle-password" id="togglePassword"></i>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="rememberMe">
                        <label class="form-check-label text-muted small" for="rememberMe">Remember me</label>
                    </div>
                </div>

                <button type="submit" class="btn btn-login mb-4">LOG IN</button>
            </form>
        </div>
    </div>
</div>

<script>
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');

    togglePassword.addEventListener('click', function (e) {
        // toggle the type attribute
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        
        // toggle the eye slash icon
        this.classList.toggle('fa-eye-slash');
    });
</script>

</body>
</html>
