<?php
/*
 * MIKPAY Admin - User Management
 * Halaman admin untuk mengelola user/pelanggan
 */
session_start();
error_reporting(0);

if (!isset($_SESSION["mikpay"])) {
    header("Location:../admin.php?id=login");
    exit;
}

// Check if admin
$isAdmin = false;
if (isset($_SESSION["user_role"]) && $_SESSION["user_role"] == 'admin') {
    $isAdmin = true;
} else {
    // Check old admin system
    include('../include/config.php');
    include('../include/readcfg.php');
    if (isset($_SESSION["mikpay"]) && $_SESSION["mikpay"] == $useradm) {
        $isAdmin = true;
    }
}

if (!$isAdmin) {
    die("Access denied. Admin only.");
}

include_once('../include/database.php');
include_once('../include/subscription.php');
include_once('../include/business_config.php');

$successMsg = '';
$errorMsg = '';

// Handle add user
if (isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $email = trim($_POST['email']);
    $fullName = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $package = $_POST['package'] ?? 'trial';
    $days = $_POST['days'] ?? 5;
    
    if (empty($username) || empty($password)) {
        $errorMsg = 'Username dan password harus diisi';
    } elseif (strlen($password) < 6) {
        $errorMsg = 'Password minimal 6 karakter';
    } else {
        $result = registerUser($username, $password, $email, $fullName, $phone);
        
        if ($result['success']) {
            $userId = $result['user_id'];
            
            // Set subscription package if not trial
            if ($package != 'trial') {
                createUserSubscription($userId, $package, $days);
            }
            
            $successMsg = 'User berhasil ditambahkan dengan trial 5 hari';
        } else {
            $errorMsg = $result['message'];
        }
    }
}

// Handle update user
if (isset($_POST['update_user'])) {
    $userId = $_POST['user_id'];
    $data = array(
        'email' => $_POST['email'],
        'full_name' => $_POST['full_name'],
        'phone' => $_POST['phone'],
        'status' => $_POST['status']
    );
    
    if (updateUser($userId, $data)) {
        $successMsg = 'User berhasil diupdate';
    } else {
        $errorMsg = 'Gagal mengupdate user';
    }
}

// Handle delete user
if (isset($_POST['delete_user'])) {
    $userId = $_POST['user_id'];
    if (deleteUser($userId)) {
        $successMsg = 'User berhasil dihapus';
    } else {
        $errorMsg = 'Gagal menghapus user';
    }
}

// Handle extend subscription
if (isset($_POST['extend_subscription'])) {
    $userId = $_POST['user_id'];
    $package = $_POST['package'];
    $days = intval($_POST['days']);
    
    if ($days > 0) {
        createUserSubscription($userId, $package, $days);
        $successMsg = "Subscription berhasil diperpanjang $days hari";
    } else {
        $errorMsg = 'Jumlah hari harus lebih dari 0';
    }
}

// Get all users
$users = getAllUsers(1000, 0);
$subscriptionPackages = getSubscriptionPackages();
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Management - MIKPAY</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="../css/font-awesome/css/font-awesome.min.css" />
    <link rel="stylesheet" href="../css/mikpay-ui.dark.min.css">
    <link rel="stylesheet" href="../css/dashboard-custom.css">
    <link rel="icon" href="../img/favicon.png?v=<?= time() ?>" />
    <script src="../js/jquery.min.js"></script>
</head>
<body>
<?php include_once('../include/headhtml.php'); ?>
<?php include_once('../include/menu.php'); ?>

<div class="main-container">
    <div class="card">
        <div class="card-header">
            <h3><i class="fa fa-users"></i> User Management</h3>
        </div>
        <div class="card-body">
            
            <?php if ($successMsg): ?>
            <div class="success-alert" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 2px solid #22c55e; border-radius: 12px; padding: 15px; margin-bottom: 20px;">
                <i class="fa fa-check-circle" style="color: #22c55e;"></i> <?= $successMsg ?>
            </div>
            <?php endif; ?>
            
            <?php if ($errorMsg): ?>
            <div class="success-alert" style="background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); border: 2px solid #ef4444; border-radius: 12px; padding: 15px; margin-bottom: 20px;">
                <i class="fa fa-exclamation-circle" style="color: #ef4444;"></i> <?= $errorMsg ?>
            </div>
            <?php endif; ?>
            
            <!-- Add User Form -->
            <div class="card" style="margin-bottom: 30px; background: #f8fafc;">
                <div class="card-header" style="background: linear-gradient(135deg, #4D44B5 0%, #6366f1 100%); color: #FFF;">
                    <h3><i class="fa fa-user-plus"></i> Tambah User Baru</h3>
                </div>
                <div class="card-body">
                    <form method="POST" style="max-width: 800px;">
                        <div class="row">
                            <div class="col-6">
                                <div style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Username *</label>
                                    <input type="text" name="username" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <div style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Password *</label>
                                    <input type="password" name="password" class="form-control" required minlength="6">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <div style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Nama Lengkap</label>
                                    <input type="text" name="full_name" class="form-control">
                                </div>
                            </div>
                            <div class="col-6">
                                <div style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Email</label>
                                    <input type="email" name="email" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <div style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">No. HP</label>
                                    <input type="text" name="phone" class="form-control">
                                </div>
                            </div>
                            <div class="col-6">
                                <div style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Paket (Opsional)</label>
                                    <select name="package" class="form-control">
                                        <option value="trial">Trial 5 Hari (Default)</option>
                                        <?php foreach ($subscriptionPackages as $key => $pkg): ?>
                                        <option value="<?= $key ?>"><?= $pkg['name'] ?> - <?= $pkg['duration'] ?> hari</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="add_user" class="btn bg-primary">
                            <i class="fa fa-user-plus"></i> Tambah User
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Users List -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fa fa-list"></i> Daftar User (<?= count($users) ?>)</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Nama Lengkap</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Role</th>
                                    <th>Subscription</th>
                                    <th>Berlaku Hingga</th>
                                    <th>Last Login</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= $user['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
                                    <td><?= htmlspecialchars($user['full_name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($user['email'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($user['phone'] ?? '-') ?></td>
                                    <td>
                                        <?php
                                        $statusClass = 'bg-success';
                                        if ($user['status'] == 'inactive') $statusClass = 'bg-secondary';
                                        if ($user['status'] == 'suspended') $statusClass = 'bg-danger';
                                        ?>
                                        <span class="btn <?= $statusClass ?>" style="padding: 4px 12px; font-size: 12px;">
                                            <?= ucfirst($user['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="btn <?= $user['role'] == 'admin' ? 'bg-danger' : 'bg-info' ?>" style="padding: 4px 12px; font-size: 12px;">
                                            <?= ucfirst($user['role']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $package = $user['subscription_package'] ?? 'trial';
                                        echo ucfirst($package);
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $endDate = $user['subscription_end'] ?? $user['trial_ends'] ?? '-';
                                        if ($endDate != '-') {
                                            $endDateObj = new DateTime($endDate);
                                            $today = new DateTime();
                                            if ($endDateObj < $today) {
                                                echo '<span style="color: #ef4444;">' . date('d M Y', strtotime($endDate)) . '</span>';
                                            } else {
                                                echo date('d M Y', strtotime($endDate));
                                            }
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td><?= $user['last_login'] ? date('d M Y H:i', strtotime($user['last_login'])) : '-' ?></td>
                                    <td>
                                        <button onclick="showEditModal(<?= htmlspecialchars(json_encode($user)) ?>)" class="btn bg-warning" style="padding: 4px 8px; font-size: 12px;">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <button onclick="showExtendModal(<?= $user['id'] ?>)" class="btn bg-success" style="padding: 4px 8px; font-size: 12px;">
                                            <i class="fa fa-calendar-plus-o"></i>
                                        </button>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin hapus user ini?');">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <button type="submit" name="delete_user" class="btn bg-danger" style="padding: 4px 8px; font-size: 12px;">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal-window" id="editModal" aria-hidden="true">
    <div>
        <header><h1>Edit User</h1></header>
        <a href="#" class="modal-close" title="Close">X</a>
        <form method="POST" id="editForm">
            <input type="hidden" name="user_id" id="edit_user_id">
            <div style="margin-bottom: 15px;">
                <label>Email</label>
                <input type="email" name="email" id="edit_email" class="form-control">
            </div>
            <div style="margin-bottom: 15px;">
                <label>Nama Lengkap</label>
                <input type="text" name="full_name" id="edit_full_name" class="form-control">
            </div>
            <div style="margin-bottom: 15px;">
                <label>Phone</label>
                <input type="text" name="phone" id="edit_phone" class="form-control">
            </div>
            <div style="margin-bottom: 15px;">
                <label>Status</label>
                <select name="status" id="edit_status" class="form-control">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="suspended">Suspended</option>
                </select>
            </div>
            <button type="submit" name="update_user" class="btn bg-primary">Update</button>
            <a href="#" class="btn bg-secondary modal-close">Cancel</a>
        </form>
    </div>
</div>

<!-- Extend Subscription Modal -->
<div class="modal-window" id="extendModal" aria-hidden="true">
    <div>
        <header><h1>Perpanjang Subscription</h1></header>
        <a href="#" class="modal-close" title="Close">X</a>
        <form method="POST" id="extendForm">
            <input type="hidden" name="user_id" id="extend_user_id">
            <div style="margin-bottom: 15px;">
                <label>Paket</label>
                <select name="package" class="form-control">
                    <?php foreach ($subscriptionPackages as $key => $pkg): ?>
                    <option value="<?= $key ?>"><?= $pkg['name'] ?> - <?= $pkg['duration'] ?> hari</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin-bottom: 15px;">
                <label>Jumlah Hari</label>
                <input type="number" name="days" class="form-control" value="30" min="1" required>
            </div>
            <button type="submit" name="extend_subscription" class="btn bg-primary">Perpanjang</button>
            <a href="#" class="btn bg-secondary modal-close">Cancel</a>
        </form>
    </div>
</div>

<script>
function showEditModal(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_email').value = user.email || '';
    document.getElementById('edit_full_name').value = user.full_name || '';
    document.getElementById('edit_phone').value = user.phone || '';
    document.getElementById('edit_status').value = user.status || 'active';
    document.getElementById('editModal').setAttribute('aria-hidden', 'false');
}

function showExtendModal(userId) {
    document.getElementById('extend_user_id').value = userId;
    document.getElementById('extendModal').setAttribute('aria-hidden', 'false');
}

// Modal close handlers
document.querySelectorAll('.modal-close').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        this.closest('.modal-window').setAttribute('aria-hidden', 'true');
    });
});
</script>

</body>
</html>
