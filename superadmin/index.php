<?php
/*
 * MIKPAY Super Admin Panel
 */
session_start();
error_reporting(0);

include('../include/superadmin.php');
include('../include/subscription.php');
include('../include/business_config.php');

// Handle logout
if (isset($_GET['logout'])) {
    unset($_SESSION['superadmin']);
    header('Location: index.php');
    exit;
}

// Handle login
$loginError = '';
if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (verifySuperAdmin($email, $password)) {
        $_SESSION['superadmin'] = true;
        $_SESSION['superadmin_email'] = $email;
        header('Location: index.php');
        exit;
    } else {
        $loginError = 'Email atau password salah!';
    }
}

// Handle actions
if (isSuperAdmin()) {
    // Approve payment
    if (isset($_POST['approve'])) {
        approvePayment($_POST['payment_id']);
        header('Location: index.php?tab=payments&msg=approved');
        exit;
    }
    // Reject payment
    if (isset($_POST['reject'])) {
        rejectPayment($_POST['payment_id'], isset($_POST['reason']) ? $_POST['reason'] : '');
        header('Location: index.php?tab=payments&msg=rejected');
        exit;
    }
    // Activate user
    if (isset($_POST['activate_user'])) {
        activateUser($_POST['user_id']);
        header('Location: index.php?tab=users&msg=activated');
        exit;
    }
    // Deactivate user
    if (isset($_POST['deactivate_user'])) {
        $reason = isset($_POST['reason']) ? $_POST['reason'] : '';
        deactivateUser($_POST['user_id'], $reason);
        header('Location: index.php?tab=users&msg=deactivated');
        exit;
    }
    // Delete user
    if (isset($_POST['delete_user'])) {
        deleteUser($_POST['user_id']);
        header('Location: index.php?tab=users&msg=deleted');
        exit;
    }
    // Add new user
    if (isset($_POST['add_user'])) {
        $userId = trim($_POST['new_user_id']);
        $password = trim($_POST['new_user_password']);
        
        if (empty($password)) {
            $loginError = 'Password harus diisi!';
        } else {
            // Set trial 5 hari
            $trialStartDate = date('Y-m-d');
            $trialEndDate = date('Y-m-d', strtotime('+5 days'));
            
            $userData = array(
                'id' => $userId,
                'name' => trim($_POST['new_user_name']),
                'email' => trim($_POST['new_user_email']),
                'phone' => trim($_POST['new_user_phone']),
                'package' => 'trial', // Set to trial package
                'password' => $password, // Store password (will be used for login)
                'status' => 'active',
                'subscription_start' => $trialStartDate,
                'subscription_end' => $trialEndDate,
                'subscription_status' => 'trial',
                'notes' => trim($_POST['new_user_notes'])
            );
            saveUser($userId, $userData);
            header('Location: index.php?tab=users&msg=added');
            exit;
        }
    }
    // Update subscription manually
    if (isset($_POST['update_subscription'])) {
        if (function_exists('setUserPackage')) {
            setUserPackage($_POST['sub_package'], intval($_POST['sub_days']));
        }
        header('Location: index.php?tab=subscription&msg=updated');
        exit;
    }
    // Extend subscription
    if (isset($_POST['extend_subscription'])) {
        if (function_exists('extendUserSubscription')) {
            extendUserSubscription('main', intval($_POST['extend_days']));
        }
        header('Location: index.php?tab=subscription&msg=extended');
        exit;
    }
}

$payments = getPendingPayments();
$stats = getPaymentStats();
$subscription = getSubscription();
$packages = getSubscriptionPackages();
$users = getAllUsers();
$userStats = getUserStats();

$currentTab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin - MIKPAY</title>
    <link rel="stylesheet" href="../css/font-awesome.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%);
            min-height: 100vh;
        }
        
        /* Login Page */
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-box {
            background: #FFF;
            border-radius: 24px;
            padding: 50px 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 80px rgba(0,0,0,0.3);
        }
        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-logo img {
            width: 80px;
            height: 80px;
            border-radius: 20px;
        }
        .login-logo h1 {
            color: #1e1b4b;
            font-size: 28px;
            margin-top: 15px;
        }
        .login-logo p {
            color: #64748b;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            color: #1e293b;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #4D44B5;
            box-shadow: 0 0 0 4px rgba(77, 68, 181, 0.1);
        }
        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #4D44B5 0%, #6366f1 100%);
            color: #FFF;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(77, 68, 181, 0.4);
        }
        .error-msg {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        /* Dashboard */
        .admin-container {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .admin-header h1 {
            color: #FFF;
            font-size: 28px;
        }
        .admin-header h1 i {
            color: #f97316;
        }
        .btn-logout {
            background: rgba(255,255,255,0.15);
            color: #FFF;
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        .btn-logout:hover {
            background: rgba(255,255,255,0.25);
        }
        
        /* Tabs Navigation */
        .tabs-nav {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        .tab-btn {
            padding: 12px 24px;
            background: rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.7);
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s;
        }
        .tab-btn:hover {
            background: rgba(255,255,255,0.2);
            color: #FFF;
        }
        .tab-btn.active {
            background: #FFF;
            color: #4D44B5;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #FFF;
            border-radius: 16px;
            padding: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #FFF;
        }
        .stat-icon.pending { background: linear-gradient(135deg, #f97316, #ea580c); }
        .stat-icon.approved { background: linear-gradient(135deg, #22c55e, #16a34a); }
        .stat-icon.rejected { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .stat-icon.revenue { background: linear-gradient(135deg, #4D44B5, #6366f1); }
        .stat-icon.users { background: linear-gradient(135deg, #06b6d4, #0891b2); }
        .stat-icon.inactive { background: linear-gradient(135deg, #94a3b8, #64748b); }
        .stat-info h4 {
            color: #64748b;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 5px;
        }
        .stat-info p {
            color: #1e293b;
            font-size: 28px;
            font-weight: 700;
        }
        
        /* Cards */
        .card {
            background: #FFF;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        .card-header {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
            color: #FFF;
            padding: 20px 25px;
        }
        .card-header h3 {
            font-size: 18px;
            margin: 0;
        }
        .card-body {
            padding: 25px;
        }
        
        /* Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th {
            background: #f8fafc;
            padding: 15px 20px;
            text-align: left;
            font-weight: 600;
            color: #64748b;
            font-size: 12px;
            text-transform: uppercase;
        }
        .data-table td {
            padding: 18px 20px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .data-table tr:hover {
            background: #fafafa;
        }
        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .status-badge.pending { background: #fff7ed; color: #ea580c; }
        .status-badge.approved, .status-badge.active { background: #f0fdf4; color: #16a34a; }
        .status-badge.rejected, .status-badge.inactive { background: #fef2f2; color: #dc2626; }
        
        /* Buttons */
        .action-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s;
            margin-right: 5px;
            text-decoration: none;
            display: inline-block;
        }
        .action-btn.approve, .action-btn.activate { background: linear-gradient(135deg, #22c55e, #16a34a); color: #FFF; }
        .action-btn.reject, .action-btn.deactivate { background: linear-gradient(135deg, #ef4444, #dc2626); color: #FFF; }
        .action-btn.edit { background: linear-gradient(135deg, #3b82f6, #2563eb); color: #FFF; }
        .action-btn.delete { background: linear-gradient(135deg, #94a3b8, #64748b); color: #FFF; }
        .action-btn:hover { transform: scale(1.05); }
        
        .btn-primary {
            padding: 14px 28px;
            background: linear-gradient(135deg, #4D44B5 0%, #6366f1 100%);
            color: #FFF;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(77, 68, 181, 0.4);
        }
        
        .btn-secondary {
            padding: 14px 28px;
            background: #f1f5f9;
            color: #475569;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-secondary:hover {
            background: #e2e8f0;
        }
        
        /* Packages Grid */
        .packages-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }
        .package-card {
            background: #FFF;
            border-radius: 20px;
            padding: 30px;
            border: 2px solid #e2e8f0;
            transition: all 0.3s;
            position: relative;
        }
        .package-card:hover {
            border-color: #4D44B5;
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .package-card.current {
            border-color: #22c55e;
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        }
        .package-badge {
            position: absolute;
            top: -12px;
            right: 20px;
            padding: 6px 16px;
            background: #22c55e;
            color: #FFF;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .package-name {
            font-size: 22px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 10px;
        }
        .package-price {
            font-size: 32px;
            font-weight: 800;
            color: #4D44B5;
            margin-bottom: 5px;
        }
        .package-price span {
            font-size: 14px;
            font-weight: 400;
            color: #64748b;
        }
        .package-limits {
            padding: 15px 0;
            margin: 15px 0;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
        }
        .package-limits p {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 8px;
        }
        .package-limits p strong {
            color: #1e293b;
        }
        .package-features {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .package-features li {
            padding: 8px 0;
            font-size: 14px;
            color: #475569;
        }
        .package-features li i {
            color: #22c55e;
            margin-right: 10px;
        }
        
        /* Subscription Info */
        .sub-info-card {
            background: #FFF;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
        }
        .sub-info-card h3 {
            color: #1e293b;
            margin-bottom: 20px;
            font-size: 18px;
        }
        .sub-detail {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        .sub-detail-item {
            background: #f8fafc;
            padding: 15px 20px;
            border-radius: 12px;
        }
        .sub-detail-item label {
            color: #64748b;
            font-size: 12px;
            display: block;
            margin-bottom: 5px;
        }
        .sub-detail-item span {
            color: #1e293b;
            font-size: 16px;
            font-weight: 600;
        }
        
        .success-msg {
            background: #f0fdf4;
            border: 1px solid #86efac;
            color: #16a34a;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }
        .empty-state i {
            font-size: 60px;
            margin-bottom: 20px;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal.show {
            display: flex;
        }
        .modal-content {
            background: #FFF;
            border-radius: 20px;
            width: 100%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            margin: 20px;
        }
        .modal-header {
            padding: 20px 25px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 {
            font-size: 18px;
            margin: 0;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #94a3b8;
        }
        .modal-body {
            padding: 25px;
        }
        .modal-footer {
            padding: 20px 25px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        /* Form inline */
        .form-inline {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        .form-inline .form-group {
            margin-bottom: 0;
            flex: 1;
            min-width: 150px;
        }
        
        @media (max-width: 768px) {
            .data-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>

<?php if (!isSuperAdmin()): ?>
<!-- Login Form -->
<div class="login-container">
    <div class="login-box">
        <div class="login-logo">
            <?php 
            // Always use logo.png for superadmin
            $logoPath = '../img/logo.png';
            $logoExists = file_exists(__DIR__ . '/../img/logo.png');
            if ($logoExists): ?>
            <img src="<?= $logoPath ?>?v=<?= time() ?>" alt="Logo">
            <?php else: ?>
            <div style="width: 80px; height: 80px; border-radius: 20px; background: linear-gradient(135deg, #4D44B5 0%, #6366f1 100%); display: flex; align-items: center; justify-content: center; margin: 0 auto; font-size: 32px; font-weight: 700; color: #FFF;">M</div>
            <?php endif; ?>
            <h1>Super Admin</h1>
            <p>MIKPAY Management Panel</p>
        </div>
        
        <?php if ($loginError): ?>
        <div class="error-msg">
            <i class="fa fa-exclamation-circle"></i> <?= $loginError ?>
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="admin@email.com" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" name="login" class="btn-login">
                <i class="fa fa-sign-in"></i> Login
            </button>
        </form>
    </div>
</div>

<?php else: ?>
<!-- Admin Dashboard -->
<div class="admin-container">
    <div class="admin-header">
        <h1><i class="fa fa-shield"></i> Super Admin Panel</h1>
        <a href="?logout=1" class="btn-logout"><i class="fa fa-sign-out"></i> Logout</a>
    </div>
    
    <!-- Tabs Navigation -->
    <div class="tabs-nav">
        <a href="?tab=dashboard" class="tab-btn <?= $currentTab == 'dashboard' ? 'active' : '' ?>">
            <i class="fa fa-dashboard"></i> Dashboard
        </a>
        <a href="?tab=users" class="tab-btn <?= $currentTab == 'users' ? 'active' : '' ?>">
            <i class="fa fa-users"></i> Kelola User
        </a>
        <a href="?tab=subscription" class="tab-btn <?= $currentTab == 'subscription' ? 'active' : '' ?>">
            <i class="fa fa-credit-card"></i> Langganan
        </a>
        <a href="?tab=packages" class="tab-btn <?= $currentTab == 'packages' ? 'active' : '' ?>">
            <i class="fa fa-cubes"></i> Paket
        </a>
        <a href="?tab=payments" class="tab-btn <?= $currentTab == 'payments' ? 'active' : '' ?>">
            <i class="fa fa-money"></i> Pembayaran
        </a>
    </div>
    
    <?php if (isset($_GET['msg']) && empty($loginError)): ?>
    <div class="success-msg">
        <i class="fa fa-check-circle"></i> 
        <?php
        $messages = array(
            'approved' => 'Pembayaran berhasil di-approve!',
            'rejected' => 'Pembayaran ditolak.',
            'activated' => 'User berhasil diaktifkan!',
            'deactivated' => 'User berhasil dinonaktifkan.',
            'deleted' => 'User berhasil dihapus.',
            'added' => 'User baru berhasil ditambahkan!',
            'updated' => 'Langganan berhasil diupdate!',
            'extended' => 'Langganan berhasil diperpanjang!'
        );
        echo isset($messages[$_GET['msg']]) ? $messages[$_GET['msg']] : 'Aksi berhasil.';
        ?>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($loginError) && !isset($_GET['msg'])): ?>
    <div class="error-msg" style="background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; padding: 15px 20px; border-radius: 12px; margin-bottom: 20px;">
        <i class="fa fa-exclamation-circle"></i> <?= $loginError ?>
    </div>
    <?php endif; ?>
    
    <?php if ($currentTab == 'dashboard'): ?>
    <!-- ===== DASHBOARD TAB ===== -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon pending"><i class="fa fa-clock-o"></i></div>
            <div class="stat-info">
                <h4>Menunggu Approval</h4>
                <p><?= $stats['pending'] ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon approved"><i class="fa fa-check"></i></div>
            <div class="stat-info">
                <h4>Disetujui</h4>
                <p><?= $stats['approved'] ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon users"><i class="fa fa-users"></i></div>
            <div class="stat-info">
                <h4>Total User</h4>
                <p><?= $userStats['total'] ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon revenue"><i class="fa fa-money"></i></div>
            <div class="stat-info">
                <h4>Total Pendapatan</h4>
                <p>Rp <?= number_format($stats['total_revenue'], 0, ',', '.') ?></p>
            </div>
        </div>
    </div>
    
    <!-- Current Subscription Status -->
    <div class="sub-info-card">
        <h3><i class="fa fa-info-circle"></i> Status Langganan Saat Ini</h3>
        <div class="sub-detail">
            <div class="sub-detail-item">
                <label>Status</label>
                <span style="color: <?= (isset($subscription['status']) && $subscription['status'] == 'active') ? '#16a34a' : '#dc2626' ?>">
                    <?= isset($subscription['status']) ? ucfirst($subscription['status']) : 'N/A' ?>
                </span>
            </div>
            <div class="sub-detail-item">
                <label>Paket</label>
                <span><?= ucfirst(isset($subscription['package']) ? $subscription['package'] : 'N/A') ?></span>
            </div>
            <div class="sub-detail-item">
                <label>Tanggal Mulai</label>
                <span><?= isset($subscription['start_date']) ? date('d M Y', strtotime($subscription['start_date'])) : 'N/A' ?></span>
            </div>
            <div class="sub-detail-item">
                <label>Tanggal Berakhir</label>
                <span><?= isset($subscription['end_date']) ? date('d M Y', strtotime($subscription['end_date'])) : 'N/A' ?></span>
            </div>
            <div class="sub-detail-item">
                <label>Sisa Hari</label>
                <span><?= getRemainingDays() ?> hari</span>
            </div>
        </div>
    </div>
    
    <!-- Recent Payments -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fa fa-history"></i> Pembayaran Terbaru</h3>
        </div>
        <?php 
        $recentPayments = array_slice(array_reverse($payments), 0, 5);
        if (empty($recentPayments)): 
        ?>
        <div class="empty-state">
            <i class="fa fa-inbox"></i>
            <p>Belum ada pembayaran</p>
        </div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tanggal</th>
                    <th>Paket</th>
                    <th>Jumlah</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentPayments as $payment): ?>
                <tr>
                    <td><strong><?= $payment['id'] ?></strong></td>
                    <td><?= date('d M Y H:i', strtotime($payment['created_at'])) ?></td>
                    <td><?= ucfirst($payment['package']) ?></td>
                    <td>Rp <?= number_format($payment['amount'], 0, ',', '.') ?></td>
                    <td>
                        <span class="status-badge <?= $payment['status'] ?>">
                            <?= ucfirst($payment['status']) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    
    <?php elseif ($currentTab == 'users'): ?>
    <!-- ===== USERS TAB ===== -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon users"><i class="fa fa-users"></i></div>
            <div class="stat-info">
                <h4>Total User</h4>
                <p><?= $userStats['total'] ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon approved"><i class="fa fa-check-circle"></i></div>
            <div class="stat-info">
                <h4>User Aktif</h4>
                <p><?= $userStats['active'] ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon inactive"><i class="fa fa-ban"></i></div>
            <div class="stat-info">
                <h4>User Nonaktif</h4>
                <p><?= $userStats['inactive'] ?></p>
            </div>
        </div>
    </div>
    
    <!-- Add User Button -->
    <div style="margin-bottom: 20px;">
        <button class="btn-primary" onclick="openModal('addUserModal')">
            <i class="fa fa-plus"></i> Tambah User Baru
        </button>
    </div>
    
    <!-- Users Table -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fa fa-users"></i> Daftar User</h3>
        </div>
        <?php if (empty($users)): ?>
        <div class="empty-state">
            <i class="fa fa-users"></i>
            <p>Belum ada user terdaftar</p>
        </div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Session ID</th>
                    <th>Nama Router</th>
                    <th>Host/IP</th>
                    <th>Username</th>
                    <th>Password</th>
                    <th>Paket</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                include_once('../lib/routeros_api.class.php');
                foreach ($users as $user): 
                    // Get password and username for config.php users
                    $displayPassword = '-';
                    $displayUsername = '-';
                    $passwordVisible = false;
                    if (isset($user['source']) && $user['source'] == 'config') {
                        // Load from config.php
                        include_once('../include/config.php');
                        include_once('../include/readcfg.php');
                        if (isset($data[$user['id']])) {
                            $session = $user['id'];
                            $iphost = explode('!', $data[$session][1])[1];
                            $userhost = explode('@|@', $data[$session][2])[1];
                            $passwdhost = explode('#|#', $data[$session][3])[1];
                            $displayPassword = decrypt($passwdhost);
                            $displayUsername = $userhost;
                            $passwordVisible = true;
                        }
                    } elseif (isset($user['password'])) {
                        // From JSON users
                        $displayPassword = $user['password'];
                        $displayUsername = isset($user['username']) ? $user['username'] : (isset($user['id']) ? $user['id'] : '-');
                        $passwordVisible = true;
                    } else {
                        // Try to get username from user data even if no password
                        $displayUsername = isset($user['username']) ? $user['username'] : (isset($user['id']) ? $user['id'] : '-');
                    }
                ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($user['id']) ?></strong>
                        <?php if (isset($user['source']) && $user['source'] == 'config'): ?>
                        <span style="font-size:10px; color:#64748b; display:block;">dari config.php</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars(isset($user['router_name']) ? $user['router_name'] : (isset($user['name']) ? $user['name'] : '-')) ?></strong>
                        <?php if (isset($user['email']) && $user['email']): ?>
                        <span style="font-size:12px; color:#64748b; display:block;"><?= htmlspecialchars($user['email']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (isset($user['host']) && $user['host']): ?>
                        <code style="background:#f1f5f9; padding:4px 8px; border-radius:4px; font-size:12px;"><?= htmlspecialchars($user['host']) ?></code>
                        <?php else: ?>
                        <span style="color:#94a3b8;">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (isset($displayUsername) && $displayUsername !== '-'): ?>
                        <code style="background:#e0f2fe; padding:4px 8px; border-radius:4px; font-size:12px;"><?= htmlspecialchars($displayUsername) ?></code>
                        <?php else: ?>
                        <span style="color:#94a3b8;">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($passwordVisible): ?>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span id="pass-<?= htmlspecialchars($user['id']) ?>" style="font-family: monospace; background:#fef3c7; padding:4px 8px; border-radius:4px; font-size:12px;">••••••••</span>
                            <button type="button" onclick="togglePassword('<?= htmlspecialchars($user['id']) ?>', '<?= htmlspecialchars($displayPassword) ?>')" style="background: none; border: none; cursor: pointer; color: #64748b; padding: 4px;">
                                <i id="icon-<?= htmlspecialchars($user['id']) ?>" class="fa fa-eye"></i>
                            </button>
                        </div>
                        <?php else: ?>
                        <span style="color:#94a3b8;">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span style="background:<?= isset($packages[isset($user['package']) ? $user['package'] : 'starter']['color']) ? $packages[isset($user['package']) ? $user['package'] : 'starter']['color'] : '#64748b' ?>; color:#fff; padding:4px 10px; border-radius:12px; font-size:11px; font-weight:600;">
                            <?= ucfirst(isset($user['package']) ? $user['package'] : 'starter') ?>
                        </span>
                    </td>
                    <td>
                        <span class="status-badge <?= (isset($user['status']) ? $user['status'] : 'active') ?>">
                            <?= ucfirst(isset($user['status']) ? $user['status'] : 'Active') ?>
                        </span>
                        <?php if (isset($user['deactivated_reason']) && $user['deactivated_reason']): ?>
                        <span style="font-size:11px; color:#dc2626; display:block;"><?= htmlspecialchars($user['deactivated_reason']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <?php $status = isset($user['status']) ? $user['status'] : 'active'; ?>
                            <?php if ($status === 'active'): ?>
                            <button class="action-btn deactivate" onclick="openDeactivateModal('<?= $user['id'] ?>', '<?= htmlspecialchars(isset($user['router_name']) ? $user['router_name'] : $user['id']) ?>')">
                                <i class="fa fa-ban"></i> Nonaktifkan
                            </button>
                            <?php else: ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <button type="submit" name="activate_user" class="action-btn activate">
                                    <i class="fa fa-check"></i> Aktifkan
                                </button>
                            </form>
                            <?php endif; ?>
                            
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus user <?= htmlspecialchars($user['id']) ?>? Tindakan ini tidak dapat dibatalkan.');">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <button type="submit" name="delete_user" class="action-btn" style="background: #ef4444; color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; font-size: 12px;">
                                    <i class="fa fa-trash"></i> Hapus
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php 
                    unset($userhost, $passwdhost, $iphost);
                endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    
    <!-- Add User Modal -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fa fa-user-plus"></i> Tambah User Baru</h3>
                <button class="modal-close" onclick="closeModal('addUserModal')">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label>ID User *</label>
                        <input type="text" name="new_user_id" placeholder="Contoh: user001" required>
                    </div>
                    <div class="form-group">
                        <label>Password *</label>
                        <input type="password" name="new_user_password" placeholder="Masukkan password" required>
                    </div>
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="new_user_name" placeholder="Nama pengguna">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="new_user_email" placeholder="email@example.com">
                    </div>
                    <div class="form-group">
                        <label>No. WhatsApp</label>
                        <input type="text" name="new_user_phone" placeholder="08xxxxxxxxxx">
                    </div>
                    <div class="form-group">
                        <label>Paket</label>
                        <select name="new_user_package" disabled>
                            <option value="trial" selected>Trial 5 Hari (Otomatis)</option>
                        </select>
                        <small style="color: #64748b; font-size: 12px; display: block; margin-top: 5px;">
                            <i class="fa fa-info-circle"></i> User baru akan mendapatkan paket trial 5 hari secara otomatis
                        </small>
                    </div>
                    <div class="form-group">
                        <label>Catatan</label>
                        <textarea name="new_user_notes" rows="3" placeholder="Catatan tambahan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('addUserModal')">Batal</button>
                    <button type="submit" name="add_user" class="btn-primary">
                        <i class="fa fa-plus"></i> Tambah User
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Deactivate User Modal -->
    <div id="deactivateModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fa fa-ban"></i> Nonaktifkan User</h3>
                <button class="modal-close" onclick="closeModal('deactivateModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="user_id" id="deactivate_user_id">
                <div class="modal-body">
                    <p style="margin-bottom: 20px;">Apakah Anda yakin ingin menonaktifkan user <strong id="deactivate_user_name"></strong>?</p>
                    <div class="form-group">
                        <label>Alasan Nonaktifkan</label>
                        <textarea name="reason" rows="3" placeholder="Contoh: Belum bayar, dll"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('deactivateModal')">Batal</button>
                    <button type="submit" name="deactivate_user" class="btn-primary" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                        <i class="fa fa-ban"></i> Nonaktifkan
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <?php elseif ($currentTab == 'subscription'): ?>
    <!-- ===== SUBSCRIPTION TAB ===== -->
    <div class="sub-info-card">
        <h3><i class="fa fa-credit-card"></i> Status Langganan Aplikasi</h3>
        <div class="sub-detail">
            <div class="sub-detail-item">
                <label>Status</label>
                <span style="color: <?= (isset($subscription['status']) && $subscription['status'] == 'active') ? '#16a34a' : '#dc2626' ?>; font-size: 20px;">
                    <?= isset($subscription['status']) ? ucfirst($subscription['status']) : 'N/A' ?>
                </span>
            </div>
            <div class="sub-detail-item">
                <label>Paket Saat Ini</label>
                <span style="font-size: 20px;"><?= ucfirst(isset($subscription['package']) ? $subscription['package'] : 'N/A') ?></span>
            </div>
            <div class="sub-detail-item">
                <label>Tanggal Mulai</label>
                <span><?= isset($subscription['start_date']) ? date('d M Y', strtotime($subscription['start_date'])) : 'N/A' ?></span>
            </div>
            <div class="sub-detail-item">
                <label>Tanggal Berakhir</label>
                <span><?= isset($subscription['end_date']) ? date('d M Y', strtotime($subscription['end_date'])) : 'N/A' ?></span>
            </div>
            <div class="sub-detail-item">
                <label>Sisa Hari</label>
                <span style="font-size: 24px; color: <?= getRemainingDays() < 7 ? '#dc2626' : '#16a34a' ?>">
                    <?= getRemainingDays() ?> hari
                </span>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fa fa-bolt"></i> Aksi Cepat</h3>
        </div>
        <div class="card-body">
            <!-- Change Package -->
            <h4 style="margin-bottom: 15px; color: #1e293b;">Ubah Paket & Durasi</h4>
            <form method="POST" class="form-inline" style="margin-bottom: 30px;">
                <div class="form-group">
                    <label>Paket</label>
                    <select name="sub_package">
                        <?php foreach ($packages as $key => $pkg): ?>
                        <option value="<?= $key ?>" <?= (isset($subscription['package']) && $subscription['package'] == $key) ? 'selected' : '' ?>>
                            <?= $pkg['name'] ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Durasi (hari)</label>
                    <input type="number" name="sub_days" value="30" min="1" max="365" style="width: 100px;">
                </div>
                <button type="submit" name="update_subscription" class="btn-primary">
                    <i class="fa fa-save"></i> Update
                </button>
            </form>
            
            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 25px 0;">
            
            <!-- Extend Subscription -->
            <h4 style="margin-bottom: 15px; color: #1e293b;">Perpanjang Langganan</h4>
            <form method="POST" class="form-inline">
                <div class="form-group">
                    <label>Tambah Hari</label>
                    <input type="number" name="extend_days" value="30" min="1" max="365" style="width: 100px;">
                </div>
                <button type="submit" name="extend_subscription" class="btn-primary" style="background: linear-gradient(135deg, #22c55e, #16a34a);">
                    <i class="fa fa-plus"></i> Perpanjang
                </button>
            </form>
        </div>
    </div>
    
    <!-- Payment History -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fa fa-history"></i> Riwayat Pembayaran</h3>
        </div>
        <?php 
        $paymentHistory = isset($subscription['payment_history']) ? $subscription['payment_history'] : array();
        if (empty($paymentHistory)): 
        ?>
        <div class="empty-state">
            <i class="fa fa-inbox"></i>
            <p>Belum ada riwayat pembayaran</p>
        </div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Paket</th>
                    <th>Jumlah</th>
                    <th>Transaction ID</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_reverse($paymentHistory) as $ph): ?>
                <tr>
                    <td><?= date('d M Y H:i', strtotime($ph['date'])) ?></td>
                    <td><?= ucfirst($ph['package']) ?></td>
                    <td>Rp <?= number_format($ph['amount'], 0, ',', '.') ?></td>
                    <td><code><?= $ph['transaction_id'] ?></code></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    
    <?php elseif ($currentTab == 'packages'): ?>
    <!-- ===== PACKAGES TAB ===== -->
    <div class="packages-grid">
        <?php foreach ($packages as $key => $pkg): ?>
        <div class="package-card <?= (isset($subscription['package']) && $subscription['package'] == $key) ? 'current' : '' ?>">
            <?php if (isset($subscription['package']) && $subscription['package'] == $key): ?>
            <div class="package-badge">Aktif</div>
            <?php endif; ?>
            
            <div class="package-name" style="color: <?= $pkg['color'] ?>"><?= $pkg['name'] ?></div>
            <div class="package-price">
                Rp <?= number_format($pkg['price'], 0, ',', '.') ?>
                <span>/bulan</span>
            </div>
            
            <div class="package-limits">
                <p><strong><?= $pkg['max_customers'] == -1 ? 'Unlimited' : $pkg['max_customers'] ?></strong> Pelanggan</p>
                <p><strong><?= $pkg['max_routers'] == -1 ? 'Unlimited' : $pkg['max_routers'] ?></strong> Router</p>
                <p><strong><?= $pkg['max_users'] == -1 ? 'Unlimited' : $pkg['max_users'] ?></strong> User Admin</p>
                <p>Support: <strong><?= $pkg['support'] ?></strong></p>
            </div>
            
            <ul class="package-features">
                <?php foreach ($pkg['features'] as $feature): ?>
                <li><i class="fa fa-check-circle"></i> <?= $feature ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php elseif ($currentTab == 'payments'): ?>
    <!-- ===== PAYMENTS TAB ===== -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon pending"><i class="fa fa-clock-o"></i></div>
            <div class="stat-info">
                <h4>Menunggu Approval</h4>
                <p><?= $stats['pending'] ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon approved"><i class="fa fa-check"></i></div>
            <div class="stat-info">
                <h4>Disetujui</h4>
                <p><?= $stats['approved'] ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon rejected"><i class="fa fa-times"></i></div>
            <div class="stat-info">
                <h4>Ditolak</h4>
                <p><?= $stats['rejected'] ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon revenue"><i class="fa fa-money"></i></div>
            <div class="stat-info">
                <h4>Total Pendapatan</h4>
                <p>Rp <?= number_format($stats['total_revenue'], 0, ',', '.') ?></p>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3><i class="fa fa-list"></i> Semua Pembayaran</h3>
        </div>
        
        <?php if (empty($payments)): ?>
        <div class="empty-state">
            <i class="fa fa-inbox"></i>
            <p>Belum ada pembayaran</p>
        </div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tanggal</th>
                    <th>Paket</th>
                    <th>Jumlah</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_reverse($payments) as $payment): ?>
                <tr>
                    <td><strong><?= $payment['id'] ?></strong></td>
                    <td><?= date('d M Y H:i', strtotime($payment['created_at'])) ?></td>
                    <td><?= ucfirst($payment['package']) ?></td>
                    <td>Rp <?= number_format($payment['amount'], 0, ',', '.') ?></td>
                    <td>
                        <span class="status-badge <?= $payment['status'] ?>">
                            <?= ucfirst($payment['status']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($payment['status'] === 'pending'): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="payment_id" value="<?= $payment['id'] ?>">
                            <button type="submit" name="approve" class="action-btn approve">
                                <i class="fa fa-check"></i> Approve
                            </button>
                            <button type="submit" name="reject" class="action-btn reject">
                                <i class="fa fa-times"></i> Tolak
                            </button>
                        </form>
                        <?php else: ?>
                        <span style="color:#94a3b8;">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    
    <?php endif; ?>
</div>

<script>
function openModal(modalId) {
    document.getElementById(modalId).classList.add('show');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('show');
}

function openDeactivateModal(userId, userName) {
    document.getElementById('deactivate_user_id').value = userId;
    document.getElementById('deactivate_user_name').textContent = userName;
    openModal('deactivateModal');
}

// Close modal when clicking outside
document.querySelectorAll('.modal').forEach(function(modal) {
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.classList.remove('show');
        }
    });
});
</script>
<?php endif; ?>

<script>
function togglePassword(userId, password) {
    var passElement = document.getElementById('pass-' + userId);
    var iconElement = document.getElementById('icon-' + userId);
    
    if (passElement && iconElement) {
        if (passElement.textContent === '••••••••') {
            passElement.textContent = password;
            iconElement.className = 'fa fa-eye-slash';
        } else {
            passElement.textContent = '••••••••';
            iconElement.className = 'fa fa-eye';
        }
    }
}
</script>

</body>
</html>
