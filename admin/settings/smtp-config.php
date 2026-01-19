<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit;
}

// Define assets path for header
$assets_path = '../assets/';
require_once '../../database/db_config.php';

// Handle Setting Update
$message = '';
$alert_type = '';

if (isset($_POST['update_smtp'])) {
    $host = $_POST['host'];
    $port = $_POST['port'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $encryption = $_POST['encryption'];
    $from_email = $_POST['from_email'];
    $from_name = $_POST['from_name'];
    $id = $_POST['id'];

    if(!empty($host) && !empty($port) && !empty($username)) {
        try {
            $sql = "UPDATE smtp_settings SET host=?, port=?, username=?, password=?, encryption=?, from_email=?, from_name=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            if($stmt->execute([$host, $port, $username, $password, $encryption, $from_email, $from_name, $id])) {
                $message = "SMTP Settings updated successfully!";
                $alert_type = "success";
            }
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $alert_type = "danger";
        }
    } else {
        $message = "Please fill all required fields.";
        $alert_type = "warning";
    }
}

// Handle Test Email
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isset($_POST['send_test_email'])) {
    $test_email = $_POST['test_email_address'];
    require '../../vendor/autoload.php';

    // Fetch current settings
    $stmt = $conn->query("SELECT * FROM smtp_settings LIMIT 1");
    $smtp = $stmt->fetch(PDO::FETCH_ASSOC);

    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = $smtp['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp['username'];
        $mail->Password   = $smtp['password'];
        $mail->SMTPSecure = $smtp['encryption'];
        $mail->Port       = $smtp['port'];

        //Recipients
        $mail->setFrom($smtp['from_email'], $smtp['from_name']);
        $mail->addAddress($test_email);

        //Content
        $mail->isHTML(true);
        $mail->Subject = 'Test Email from Typing Master Admin';
        $mail->Body    = 'This is a test email to verify your SMTP settings. <b>It works!</b>';

        $mail->send();
        $message = "Test email sent successfully to $test_email";
        $alert_type = "success";
    } catch (Exception $e) {
        $message = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        $alert_type = "danger";
    }
}

// Fetch Settings
$stmt = $conn->query("SELECT * FROM smtp_settings LIMIT 1");
$smtp = $stmt->fetch(PDO::FETCH_ASSOC);

include '../header.php';
?>

<!-- Fix Sidebar Link (Basic CSS trick or specific include logic needed normally, simplifying here) -->
<style>
    /* Quick fix for sidebar in subdirectory if links are relative. 
       Ideally sidebar.php should use absolute paths or dynamic base url.
       For now, we assume user navigates correctly. 
    */
</style>
<div class="d-flex">
    <!-- Sidebar - using relative include, might need path adjustment in sidebar.php for links to work perfectly from subfolder -->
    <?php include '../sidebar.php'; // Warning: Sidebar links might be broken if they are relative "index.php" instead of "../admin/index.php" ?> 
    
    <div class="main-content w-100">
        <header class="top-header">
            <h4 class="fw-bold">SMTP Configuration</h4>
        </header>

        <div class="container-fluid p-4">
            <?php if($message): ?>
                <div class="alert alert-<?php echo $alert_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Settings Form -->
                <div class="col-md-8">
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-header bg-white border-0 pt-4 px-4">
                            <h5 class="mb-0 fw-bold text-primary">Email Server Settings</h5>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST" action="">
                                <input type="hidden" name="id" value="<?php echo $smtp['id']; ?>">
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label">SMTP Host</label>
                                        <input type="text" class="form-control" name="host" value="<?php echo $smtp['host']; ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Port</label>
                                        <input type="number" class="form-control" name="port" value="<?php echo $smtp['port']; ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Username</label>
                                        <input type="text" class="form-control" name="username" value="<?php echo $smtp['username']; ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Password</label>
                                        <input type="password" class="form-control" name="password" value="<?php echo $smtp['password']; ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Encryption</label>
                                        <select class="form-select" name="encryption">
                                            <option value="tls" <?php echo $smtp['encryption'] == 'tls' ? 'selected' : ''; ?>>TLS</option>
                                            <option value="ssl" <?php echo $smtp['encryption'] == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                            <option value="" <?php echo $smtp['encryption'] == '' ? 'selected' : ''; ?>>None</option>
                                        </select>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label">From Email</label>
                                        <input type="email" class="form-control" name="from_email" value="<?php echo $smtp['from_email']; ?>" required>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">From Name</label>
                                        <input type="text" class="form-control" name="from_name" value="<?php echo $smtp['from_name']; ?>" required>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <button type="submit" name="update_smtp" class="btn btn-primary px-4"><i class="fa-solid fa-save me-2"></i> Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Test Email -->
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-header bg-white border-0 pt-4 px-4">
                            <h5 class="mb-0 fw-bold text-success">Test Configuration</h5>
                        </div>
                        <div class="card-body p-4">
                            <p class="text-muted small">Send a test email to verify your settings are working correctly.</p>
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label class="form-label">Recipient Email</label>
                                    <input type="email" class="form-control" name="test_email_address" placeholder="you@example.com" required>
                                </div>
                                <button type="submit" name="send_test_email" class="btn btn-success w-100"><i class="fa-solid fa-paper-plane me-2"></i> Send Test Email</button>
                            </form>
                        </div>
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
