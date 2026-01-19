<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit;
}

// Define assets path for header
$assets_path = '../assets/';
// Correct path to db_config
require_once '../../database/db_config.php';

$message = '';
$alert_type = '';

// Handle Settings Update
if (isset($_POST['save_payment_settings'])) {
    $bank_name = $_POST['bank_name'];
    $account_no = $_POST['account_no'];
    $holder_name = $_POST['holder_name'];
    $ifsc_code = $_POST['ifsc_code'];
    $branch_address = $_POST['branch_address'];
    $id = isset($_POST['id']) ? $_POST['id'] : null;

    // File Upload Handler
    $upload_dir = '../../admin/assets/uploads/qr/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $qr_code_1 = isset($_POST['existing_qr_1']) ? $_POST['existing_qr_1'] : '';
    $qr_code_2 = isset($_POST['existing_qr_2']) ? $_POST['existing_qr_2'] : '';

    $upload_ok = true;

    // QR Code 1
    if (isset($_FILES['qr_code_1']) && $_FILES['qr_code_1']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $filename = $_FILES['qr_code_1']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $new_filename = 'qr1_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['qr_code_1']['tmp_name'], $upload_dir . $new_filename)) {
                $qr_code_1 = $new_filename;
            } else {
                $message = "Failed to upload QR Code 1.";
                $alert_type = "danger";
                $upload_ok = false;
            }
        } else {
            $message = "Invalid file type for QR Code 1. Allowed: JPG, JPEG, PNG.";
            $alert_type = "danger";
            $upload_ok = false;
        }
    } else {
        // If it's a new insert, this is required
        if (!$id && empty($qr_code_1)) {
            $message = "QR Code 1 is required.";
            $alert_type = "warning";
            $upload_ok = false;
        }
    }

    // QR Code 2
    if ($upload_ok && isset($_FILES['qr_code_2']) && $_FILES['qr_code_2']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $filename = $_FILES['qr_code_2']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $new_filename = 'qr2_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['qr_code_2']['tmp_name'], $upload_dir . $new_filename)) {
                $qr_code_2 = $new_filename;
            } else {
                $message = "Failed to upload QR Code 2.";
                $alert_type = "danger";
                $upload_ok = false;
            }
        } else {
            $message = "Invalid file type for QR Code 2. Allowed: JPG, JPEG, PNG.";
            $alert_type = "danger";
            $upload_ok = false;
        }
    } else {
        // If it's a new insert, this is required
        if ($upload_ok && !$id && empty($qr_code_2)) {
             $message = "QR Code 2 is required.";
             $alert_type = "warning";
             $upload_ok = false;
        }
    }


    if ($upload_ok) {
        try {
            if ($id) {
                // Update
                $sql = "UPDATE bank_settings SET bank_name=?, account_no=?, holder_name=?, ifsc_code=?, branch_address=?, qr_code_1=?, qr_code_2=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$bank_name, $account_no, $holder_name, $ifsc_code, $branch_address, $qr_code_1, $qr_code_2, $id]);
            } else {
                // Insert
                $sql = "INSERT INTO bank_settings (bank_name, account_no, holder_name, ifsc_code, branch_address, qr_code_1, qr_code_2) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$bank_name, $account_no, $holder_name, $ifsc_code, $branch_address, $qr_code_1, $qr_code_2]);
            }
            $message = "Payment settings saved successfully!";
            $alert_type = "success";
        } catch(PDOException $e) {
            $message = "Database Error: " . $e->getMessage();
             $alert_type = "danger";
        }
    }
}

// Fetch Settings
$settings = null;
try {
    $stmt = $conn->query("SELECT * FROM bank_settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    // Table might not exist yet
}

include '../header.php';
?>

<div class="d-flex">
    <?php include '../sidebar.php'; ?>
    
    <div class="main-content w-100">
        <header class="top-header">
            <h4 class="fw-bold">Payment Configuration</h4>
        </header>

        <div class="container-fluid p-4">
             <?php if($message): ?>
                <div class="alert alert-<?php echo $alert_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h5 class="mb-0 fw-bold text-primary">Bank Account Details</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo $settings ? $settings['id'] : ''; ?>">
                        <input type="hidden" name="existing_qr_1" value="<?php echo $settings ? $settings['qr_code_1'] : ''; ?>">
                         <input type="hidden" name="existing_qr_2" value="<?php echo $settings ? $settings['qr_code_2'] : ''; ?>">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Bank Name</label>
                                <input type="text" class="form-control" name="bank_name" value="<?php echo $settings ? $settings['bank_name'] : ''; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Account Holder Name</label>
                                <input type="text" class="form-control" name="holder_name" value="<?php echo $settings ? $settings['holder_name'] : ''; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Account Number</label>
                                <input type="text" class="form-control" name="account_no" value="<?php echo $settings ? $settings['account_no'] : ''; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">IFSC Code</label>
                                <input type="text" class="form-control" name="ifsc_code" value="<?php echo $settings ? $settings['ifsc_code'] : ''; ?>" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Branch Address</label>
                                <textarea class="form-control" name="branch_address" rows="2" required><?php echo $settings ? $settings['branch_address'] : ''; ?></textarea>
                            </div>

                            <hr class="my-4">
                            <h5 class="fw-bold text-primary mb-3">QR Codes</h5>

                            <div class="col-md-6">
                                <label class="form-label">Payment QR Code 1 (Required)</label>
                                <input type="file" class="form-control" name="qr_code_1" accept="image/*" <?php echo ($settings && $settings['qr_code_1']) ? '' : 'required'; ?>>
                                <?php if($settings && $settings['qr_code_1']): ?>
                                    <div class="mt-2">
                                        <p class="text-muted small mb-1">Current QR 1:</p>
                                        <img src="../assets/uploads/qr/<?php echo $settings['qr_code_1']; ?>" alt="QR 1" style="height: 100px; border: 1px solid #ddd; padding: 5px; border-radius: 5px;">
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Payment QR Code 2 (Required)</label>
                                <input type="file" class="form-control" name="qr_code_2" accept="image/*" <?php echo ($settings && $settings['qr_code_2']) ? '' : 'required'; ?>>
                                <?php if($settings && $settings['qr_code_2']): ?>
                                    <div class="mt-2">
                                        <p class="text-muted small mb-1">Current QR 2:</p>
                                        <img src="../assets/uploads/qr/<?php echo $settings['qr_code_2']; ?>" alt="QR 2" style="height: 100px; border: 1px solid #ddd; padding: 5px; border-radius: 5px;">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" name="save_payment_settings" class="btn btn-primary px-4"><i class="fa-solid fa-save me-2"></i> Save Bank Details</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
